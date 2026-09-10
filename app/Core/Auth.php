<?php
declare(strict_types=1);

namespace App\Core;

use Database;

/**
 * app/Core/Auth.php
 * Manajemen Sesi & Hak Akses Pengguna Terpadu (Hybrid RBAC + Scope + Smart Overrides).
 * Berbasis Arsitektur Teruji dari Rekap-Mukholif dengan Multi-Layer Security.
 */

class Auth
{
    private const VERSION_FILE = ROOT_PATH . '/cache/permissions_version.txt';

    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax'
            ]);
        }
    }

    public static function check(): bool
    {
        self::init();
        return !empty($_SESSION['user']['id']);
    }

    public static function user(): ?array
    {
        self::init();
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?string
    {
        return self::user()['id'] ?? null;
    }

    public static function name(): string
    {
        return self::user()['nama_lengkap'] ?? 'Pengguna';
    }

    public static function username(): string
    {
        return self::user()['nama_pengguna'] ?? '';
    }

    public static function role(): string
    {
        return strtolower(trim((string)(self::user()['peran'] ?? 'guest')));
    }

    public static function roleId(): ?string
    {
        return self::user()['peran_id'] ?? null;
    }

    public static function employeeId(): ?string
    {
        return self::user()['karyawan_id'] ?? null;
    }

    /**
     * Get the position of the logged-in employee (sales, driver, dll)
     */
    public static function employeePosition(): ?string
    {
        return self::user()['posisi'] ?? null;
    }

    // --- ROLE CHECK HELPERS ---
    public static function isDeveloper(): bool
    {
        return self::role() === 'developer';
    }

    public static function isOwner(): bool
    {
        return self::role() === 'owner' || self::isDeveloper();
    }

    public static function isAdmin(): bool
    {
        return in_array(self::role(), ['developer', 'owner', 'admin'], true);
    }

    public static function isSales(): bool
    {
        return in_array(self::role(), ['developer', 'owner', 'admin', 'sales'], true);
    }

    public static function isDriver(): bool
    {
        return in_array(self::role(), ['developer', 'owner', 'admin', 'sales', 'driver'], true);
    }

    // --- REAL-TIME PERMISSIONS ENGINE (REKAP-MUKHOLIF POLA) ---

    public static function touchVersion(): void
    {
        $dir = dirname(self::VERSION_FILE);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents(self::VERSION_FILE, (string)time(), LOCK_EX);
    }

    /**
     * Alias touchPermissionsCache() untuk sinkronisasi mutasi izin RBAC
     */
    public static function touchPermissionsCache(): void
    {
        self::touchVersion();
        if (isset($_SESSION['permissions_version'])) {
            $_SESSION['permissions_version'] = -1;
        }
        if (isset($_SESSION['permissions'])) {
            unset($_SESSION['permissions']);
        }
    }

    /**
     * Alias clearPermissionsCache()
     */
    public static function clearPermissionsCache(): void
    {
        self::touchPermissionsCache();
    }

    public static function getVersion(): int
    {
        if (file_exists(self::VERSION_FILE)) {
            return (int)@file_get_contents(self::VERSION_FILE);
        }
        return 0;
    }

    /**
     * Hitung izin efektif pengguna: (Izin Role + Allow Overrides) - Deny Overrides
     * HANYA role 'developer' yang memiliki wildcard '*'
     */
    public static function getEffectivePermissions(string $userId, string $peranId, string $peranName): array
    {
        $peranName = strtolower(trim($peranName));

        // ATURAN EMAS: HANYA developer yang punya universal bypass '*'
        if ($peranName === 'developer') {
            return ['*'];
        }

        $effective = [];

        try {
            // 1. Ambil izin dasar dari Peran (public.izin_peran)
            $rolePerms = Database::fetchAll("
                SELECT i.kode_izin 
                FROM public.izin_peran ip
                JOIN public.izin i ON ip.izin_id = i.id
                WHERE ip.peran_id = :peran_id AND ip.diizinkan = TRUE
            ", ['peran_id' => $peranId]);

            foreach ($rolePerms as $rp) {
                $effective[$rp['kode_izin']] = true;
            }

            // 2. Terapkan Override Pengguna (public.izin_pengguna: TRUE = Allow, FALSE = Deny)
            $userOverrides = Database::fetchAll("
                SELECT i.kode_izin, ip.diizinkan
                FROM public.izin_pengguna ip
                JOIN public.izin i ON ip.izin_id = i.id
                WHERE ip.pengguna_id = :user_id
            ", ['user_id' => $userId]);

            foreach ($userOverrides as $uo) {
                $isAllowed = ($uo['diizinkan'] === true || $uo['diizinkan'] === 't' || $uo['diizinkan'] === 1 || $uo['diizinkan'] === '1');
                if ($isAllowed) {
                    $effective[$uo['kode_izin']] = true; // Tambahan Khusus (Allow)
                } else {
                    unset($effective[$uo['kode_izin']]); // Dicabut Khusus (Deny)
                }
            }
        } catch (\Throwable $e) {
            error_log("Gagal menghitung izin efektif: " . $e->getMessage());
        }

        return array_keys($effective);
    }

    /**
     * Sinkronisasi izin dan validasi sesi akun secara real-time pada setiap request HTTP
     */
    public static function syncPermissions(): void
    {
        if (!self::check()) {
            return;
        }

        $userId = self::id();
        if (!$userId) return;

        try {
            // 1. Verifikasi status akun aktif langsung ke Database
            $userDb = Database::fetchOne("
                SELECT p.id, p.status_aktif, p.peran_id, pr.nama_peran as peran
                FROM public.pengguna p
                LEFT JOIN public.peran pr ON p.peran_id = pr.id
                WHERE p.id = :id
                LIMIT 1
            ", ['id' => $userId]);

            if (!$userDb || empty($userDb['status_aktif'])) {
                self::logout();
                header('Location: ' . Router::url('/login?suspended=1'));
                exit;
            }

            // Update role jika peran di database telah diubah admin
            if (isset($_SESSION['user'])) {
                $currentPeranId = (string)($_SESSION['user']['peran_id'] ?? '');
                $dbPeranId = (string)($userDb['peran_id'] ?? '');
                if ($currentPeranId !== $dbPeranId) {
                    $_SESSION['user']['peran_id'] = $userDb['peran_id'];
                    $_SESSION['user']['peran'] = $userDb['peran'];
                    $_SESSION['permissions_version'] = -1; // Paksa refresh izin
                }
            }

            // 2. Real-time Permission Sync
            $currentVersion = self::getVersion();
            $sessionVersion = (int)($_SESSION['permissions_version'] ?? -1);

            if ($sessionVersion < $currentVersion || !isset($_SESSION['permissions'])) {
                $_SESSION['permissions'] = self::getEffectivePermissions(
                    $userId,
                    (string)$userDb['peran_id'],
                    (string)$userDb['peran']
                );
                $_SESSION['permissions_version'] = $currentVersion;
            }
        } catch (\Throwable $e) {
            error_log("Error pada syncPermissions: " . $e->getMessage());
        }
    }

    /**
     * Daftar seluruh kode izin yang dimiliki user saat ini
     */
    public static function permissions(): array
    {
        self::syncPermissions();
        return $_SESSION['permissions'] ?? [];
    }

    /**
     * Cek apakah pengguna memiliki izin tertentu
     * @param string|array $requiredPermissions
     */
    public static function can(string|array $requiredPermissions): bool
    {
        if (!self::check()) {
            return false;
        }

        // Developer role has universal bypass
        if (self::isDeveloper()) {
            return true;
        }

        $userPerms = self::permissions();

        if (in_array('*', $userPerms, true)) {
            return true;
        }

        $required = is_array($requiredPermissions) ? $requiredPermissions : [$requiredPermissions];
        $common = array_intersect($required, $userPerms);

        return !empty($common);
    }

    /**
     * Alias untuk Auth::can()
     */
    public static function hasPermission(string|array $requiredPermissions): bool
    {
        return self::can($requiredPermissions);
    }

    // --- SCOPE & OWNERSHIP CHECKS ---

    /**
     * Cek apakah toko yang dimaksud dialokasikan ke Sales yang sedang login
     */
    public static function isAssignedStore(string $storeId): bool
    {
        if (self::can(['consignment.view_all', 'orders.view_all', 'master.customers_view_all'])) {
            return true;
        }

        $empId = self::employeeId();
        if (!$empId || empty($storeId)) {
            return false;
        }

        $assigned = Database::fetchOne("
            SELECT id FROM public.pelanggan 
            WHERE id = :id AND sales_driver_id = :emp_id
            LIMIT 1
        ", ['id' => $storeId, 'emp_id' => $empId]);

        return !empty($assigned);
    }

    /**
     * Cek apakah tugas surat jalan pengiriman dialokasikan ke pengemudi/sales yang login
     */
    public static function isAssignedDelivery(string $deliveryId): bool
    {
        if (self::can('deliveries.view_all')) {
            return true;
        }

        $empId = self::employeeId();
        if (!$empId || empty($deliveryId)) {
            return false;
        }

        $assigned = Database::fetchOne("
            SELECT id FROM public.surat_jalan 
            WHERE id = :id AND sales_driver_id = :emp_id
            LIMIT 1
        ", ['id' => $deliveryId, 'emp_id' => $empId]);

        return !empty($assigned);
    }

    // --- SESSION CONTROL & GUARDS ---

    public static function login(array $userData): void
    {
        self::init();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user'] = $userData;
        $_SESSION['permissions_version'] = -1; // Akan di-sync pada request berikutnya
        $_SESSION['login_time'] = time();

        self::syncPermissions();
    }

    public static function logout(): void
    {
        self::init();
        unset($_SESSION['user'], $_SESSION['permissions'], $_SESSION['permissions_version'], $_SESSION['login_time']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Middleware check: Wajib login
     */
    public static function requireLogin(): void
    {
        self::init();
        if (!self::check()) {
            header('Location: ' . Router::url('/login?illegal=1'));
            exit;
        }
        self::syncPermissions();
    }

    /**
     * Middleware Guard Berlapis: Wajib memiliki izin tertentu
     */
    public static function requirePermission(string|array $permissions): void
    {
        self::requireLogin();

        // Tambahkan header anti-cache agar halaman terproteksi tidak bisa diakses via tombol Back browser
        if (!headers_sent()) {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        }

        if (!self::can($permissions)) {
            http_response_code(403);

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                   || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Akses ditolak (403 Forbidden). Anda tidak memiliki izin untuk aksi ini.',
                    'required_permissions' => (array)$permissions
                ]);
                exit;
            }

            $posUrl = Router::url('/pos');
            $permStr = is_array($permissions) ? implode(', ', $permissions) : $permissions;
            $userRole = ucfirst(self::role());

            echo "<!DOCTYPE html>
            <html lang='id' class='dark'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>403 - Akses Ditolak | KEREN Snack ERP</title>
                <style>
                    body { margin: 0; padding: 0; background: #090d16; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
                    .card { max-width: 460px; width: 90%; padding: 36px 28px; background: #0f172a; border: 1.5px solid rgba(244,63,94,0.35); border-radius: 24px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.7); }
                    .icon { font-size: 52px; margin-bottom: 14px; }
                    .title { font-size: 22px; font-weight: 800; color: #fb7185; margin: 0 0 10px; letter-spacing: -0.3px; }
                    .desc { font-size: 13.5px; color: #94a3b8; margin: 0 0 20px; line-height: 1.6; }
                    .badge-role { display: inline-block; background: rgba(37,99,235,0.2); color: #60a5fa; border: 1px solid rgba(96,165,250,0.3); padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; margin-bottom: 24px; }
                    .btn-group { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
                    .btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 22px; border-radius: 12px; font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.2s; }
                    .btn-primary { background: #2563eb; color: #fff; }
                    .btn-primary:hover { background: #1d4ed8; }
                    .btn-secondary { background: #1e293b; color: #cbd5e1; border: 1px solid #334155; }
                    .btn-secondary:hover { background: #334155; }
                </style>
            </head>
            <body>
                <div class='card'>
                    <div class='icon'>🛡️</div>
                    <h1 class='title'>Akses Ditolak (403 Forbidden)</h1>
                    <p class='desc'>Akun Anda tidak memiliki tiket izin untuk mengakses halaman atau fitur ini.<br><span style='font-family:monospace;font-size:11px;color:#cbd5e1;background:#1e293b;padding:2px 6px;border-radius:4px;'>Kode Izin: {$permStr}</span></p>
                    <div class='badge-role'>Peran Aktif: {$userRole}</div>
                    <div class='btn-group'>
                        <a href='javascript:history.back()' class='btn btn-secondary'>Kembali</a>
                        <a href='{$posUrl}' class='btn btn-primary'>Layar Utama</a>
                    </div>
                </div>
            </body>
            </html>";
            exit;
        }
    }

    /**
     * Backward-compatible role guard
     */
    public static function requireRole(array $allowedRoles): void
    {
        self::requireLogin();
        if (self::isDeveloper()) {
            return;
        }
        if (!in_array(self::role(), $allowedRoles, true)) {
            self::requirePermission('non_existent_role_permission');
        }
    }
}

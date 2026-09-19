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
    public const MAX_SESSION_LIFETIME = 43200; // 12 jam (12 * 3600 detik)
    private const VERSION_FILE = ROOT_PATH . '/cache/permissions_version.txt';

    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            ini_set('session.gc_maxlifetime', '43200');
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_lifetime' => 43200,
                'gc_maxlifetime'  => 43200
            ]);
        }
    }

    /**
     * Memeriksa apakah sesi login telah melampaui batas waktu maksimal 12 jam
     */
    public static function isSessionExpired(): bool
    {
        self::init();
        if (empty($_SESSION['user']['id'])) {
            return false;
        }
        if (empty($_SESSION['login_time'])) {
            $_SESSION['login_time'] = time();
            return false;
        }
        return (time() - (int)$_SESSION['login_time']) >= self::MAX_SESSION_LIFETIME;
    }

    public static function check(): bool
    {
        self::init();
        if (empty($_SESSION['user']['id'])) {
            return false;
        }
        if (self::isSessionExpired()) {
            self::logout();
            return false;
        }
        return true;
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

    public static function isMandor(): bool
    {
        return in_array(self::role(), ['developer', 'owner', 'admin', 'mandor'], true);
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
            // 1. Verifikasi status akun aktif & ambil data profil terbaru langsung dari Database
            $userDb = Database::fetchOne("
                SELECT p.id, p.nama_lengkap, p.nama_pengguna, p.posisi, p.status_aktif, p.peran_id, pr.nama_peran as peran
                FROM public.pengguna p
                LEFT JOIN public.peran pr ON p.peran_id = pr.id
                WHERE p.id = :id OR LOWER(p.nama_pengguna) = LOWER(:username)
                LIMIT 1
            ", [
                'id' => $userId ?: '00000000-0000-0000-0000-000000000000',
                'username' => $_SESSION['user']['nama_pengguna'] ?? ''
            ]);

            if (!$userDb || empty($userDb['status_aktif'])) {
                self::logout();
                header('Location: ' . Router::url('/login?suspended=1'));
                exit;
            }

            // Sinkronisasi profil & role pengguna jika ada pembaruan di database secara real-time
            if (isset($_SESSION['user']) && $userDb) {
                $_SESSION['user']['id'] = $userDb['id'];
                if (!empty($userDb['nama_lengkap'])) {
                    $_SESSION['user']['nama_lengkap'] = $userDb['nama_lengkap'];
                }
                if (!empty($userDb['nama_pengguna'])) {
                    $_SESSION['user']['nama_pengguna'] = $userDb['nama_pengguna'];
                }
                $_SESSION['user']['posisi'] = $userDb['posisi'];

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
            // 3. Session ID Regeneration — Anti Session Fixation (rotasi setiap 30 menit)
            $lastRegen = (int)($_SESSION['last_regen'] ?? 0);
            if ((time() - $lastRegen) >= 1800 && session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
                session_regenerate_id(true);
                $_SESSION['last_regen'] = time();
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
     * Middleware check: Wajib login & validasi durasi sesi 12 jam
     */
    public static function requireLogin(): void
    {
        self::init();

        // 1. Validasi batas waktu maksimal sesi login (12 jam)
        if (self::isSessionExpired()) {
            $user = self::user();
            $userName = $user['nama_lengkap'] ?? self::name() ?? 'Pengguna';
            $userId   = self::id();
            self::logout();

            // Catat log keamanan audit otomatis
            try {
                \App\Helpers\ActivityLog::log(
                    'keamanan',
                    'LOGOUT_TIMEOUT',
                    "Sesi pengguna {$userName} diakhiri otomatis karena melebihi batas waktu 12 jam",
                    'pengguna',
                    $userId
                );
            } catch (\Throwable $e) {}

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                   || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

            if ($isAjax) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'timeout' => true,
                    'error'   => 'Sesi login telah berakhir secara otomatis karena melebihi batas waktu 12 jam.',
                    'redirect'=> Router::url('/login?timeout=1')
                ]);
                exit;
            }

            header('Location: ' . Router::url('/login?timeout=1'));
            exit;
        }

        // 2. Validasi login biasa
        if (!self::check()) {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                   || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

            if ($isAjax) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error'   => 'Silakan login terlebih dahulu.',
                    'redirect'=> Router::url('/login?illegal=1')
                ]);
                exit;
            }

            header('Location: ' . Router::url('/login?illegal=1'));
            exit;
        }

        self::syncPermissions();
    }

    /**
     * Tolak Akses: Catat audit keamanan, atur status HTTP 403, dan tampilkan halaman kado kejutan interaktif.
     */
    public static function denyAccess(string|array|null $reason = null): never
    {
        $userId = self::id();
        $userName = self::name();
        $userRole = ucfirst(self::role());
        $requestedUri = $_SERVER['REQUEST_URI'] ?? '/';
        $reasonStr = is_array($reason) ? implode(', ', $reason) : (string)($reason ?? 'Akses tanpa izin');

        // 1. Catat Log Audit Keamanan
        try {
            \App\Helpers\ActivityLog::log(
                'keamanan',
                'ACCESS_DENIED',
                "Percobaan bypass akses ilegal oleh {$userName} ({$userRole}) ke '{$requestedUri}'. Alasan/Tiket: {$reasonStr}",
                'pengguna',
                $userId
            );
        } catch (\Throwable $e) {
            error_log("Gagal mencatat log access denied: " . $e->getMessage());
        }

        // 2. Proteksi Header Anti-Cache
        if (!headers_sent()) {
            http_response_code(403);
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        }

        // 3. Response untuk AJAX / JSON Requests
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
               || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'status'  => 403,
                'error'   => 'Akses ditolak (403 Forbidden). Sistem mendeteksi tindakan akses tidak sah.',
                'reason'  => $reasonStr,
                'redirect'=> Router::url('/logout')
            ]);
            exit;
        }

        // 4. Render Halaman 403 Kado Kejutan
        $viewFile = ROOT_PATH . '/views/errors/403.php';
        if (file_exists($viewFile)) {
            $title = '403 – Akses Ditolak | KEREN SNACK ERP';
            $reason = $reasonStr;
            require $viewFile;
            exit;
        }

        // Fallback jika file view tidak sengaja hilang
        echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body style='font-family:sans-serif;text-align:center;padding:50px;'><h1>403 Forbidden</h1><p>Akses Ditolak.</p><a href='" . Router::url('/logout') . "'>Logout</a></body></html>";
        exit;
    }

    /**
     * Middleware Guard Berlapis: Wajib memiliki izin tertentu
     */
    public static function requirePermission(string|array $permissions): void
    {
        self::requireLogin();

        if (!self::can($permissions)) {
            self::denyAccess($permissions);
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
            self::denyAccess($allowedRoles);
        }
    }

    /**
     * Guard Khusus Developer Portal (Murni Berbasis Peran, Tanpa Sistem Tiket Izin)
     */
    public static function requireDeveloper(): void
    {
        self::requireLogin();

        if (!self::isDeveloper()) {
            self::denyAccess('developer_only');
        }
    }
}

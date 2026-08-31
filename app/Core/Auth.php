<?php
declare(strict_types=1);

namespace App\Core;

use Database;

/**
 * app/Core/Auth.php
 * Manajemen Sesi & Hak Akses Pengguna Multi-Peran (Owner, Admin, Mandor, Sales-Driver).
 */

class Auth
{
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    public static function check(): bool
    {
        self::init();
        return !empty($_SESSION['user']);
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

    public static function role(): string
    {
        return self::user()['peran'] ?? 'guest';
    }

    public static function isDeveloper(): bool
    {
        return self::role() === 'developer';
    }

    public static function isOwner(): bool
    {
        return in_array(self::role(), ['developer', 'owner'], true);
    }

    public static function isAdmin(): bool
    {
        return in_array(self::role(), ['developer', 'owner', 'admin'], true);
    }

    public static function isMandor(): bool
    {
        return in_array(self::role(), ['developer', 'owner', 'admin', 'mandor'], true);
    }

    public static function isSalesDriver(): bool
    {
        return in_array(self::role(), ['developer', 'sales_driver', 'sales', 'driver'], true);
    }

    public static function employeeId(): ?string
    {
        return self::user()['karyawan_id'] ?? null;
    }

    public static function employeePosition(): ?string
    {
        $cached = self::user()['posisi_karyawan'] ?? null;
        if ($cached) return $cached;

        $kId = self::employeeId();
        if ($kId) {
            $pos = Database::fetchOne("SELECT posisi FROM public.karyawan WHERE id = :id", ['id' => $kId])['posisi'] ?? null;
            if ($pos && isset($_SESSION['user'])) {
                $_SESSION['user']['posisi_karyawan'] = $pos;
            }
            return $pos;
        }
        return null;
    }

    public static function isSales(): bool
    {
        if (self::isOwner() || self::isAdmin()) return true;
        if (self::role() === 'sales') return true;
        if (self::role() === 'sales_driver') {
            return self::employeePosition() !== 'driver';
        }
        return false;
    }

    public static function isDriver(): bool
    {
        if (self::isOwner() || self::isAdmin()) return true;
        if (self::role() === 'driver') return true;
        if (self::role() === 'sales_driver') {
            return self::employeePosition() === 'driver';
        }
        return false;
    }

    public static function login(array $userData): void
    {
        self::init();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user'] = $userData;
    }

    public static function logout(): void
    {
        self::init();
        unset($_SESSION['user']);
        session_destroy();
    }

    /**
     * Middleware check: Wajib login
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . Router::url('/login'));
            exit;
        }
    }

    /**
     * Middleware check: Wajib memiliki peran tertentu
     */
    public static function requireRole(array $allowedRoles): void
    {
        self::requireLogin();
        // Developer role has universal superuser bypass to all modules and actions
        if (self::role() === 'developer') {
            return;
        }
        if (!in_array(self::role(), $allowedRoles, true)) {
            http_response_code(403);
            $posUrl = Router::url('/pos');
            echo "<!DOCTYPE html><html lang='id' class='dark'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>403 - Akses Ditolak</title><style>body{margin:0;padding:0;background:#090d16;color:#f8fafc;font-family:system-ui,-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;}.card{max-width:420px;padding:32px;background:#0f172a;border:1px solid rgba(244,63,94,0.3);border-radius:24px;text-align:center;box-shadow:0 20px 40px rgba(0,0,0,0.5);}.icon{font-size:48px;margin-bottom:12px;}.title{font-size:20px;font-weight:700;color:#fb7185;margin:0 0 8px;}.desc{font-size:13px;color:#94a3b8;margin:0 0 24px;line-height:1.5;}.btn{display:inline-block;padding:10px 20px;border-radius:12px;background:#059669;color:#fff;font-size:13px;font-weight:600;text-decoration:none;transition:background 0.15s;}.btn:hover{background:#047857;}</style></head><body><div class='card'><div class='icon'>🛡️</div><h1 class='title'>403 - Akses Ditolak</h1><p class='desc'>Peran <strong>" . htmlspecialchars(ucfirst(self::role())) . "</strong> Anda tidak memiliki izin mengakses modul ini.</p><a href='{$posUrl}' class='btn'>Kembali ke Layar Utama</a></div></body></html>";
            exit;
        }
    }
}

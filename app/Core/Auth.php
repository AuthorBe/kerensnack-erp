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
        if (session_status() === PHP_SESSION_NONE) {
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
        return in_array(self::role(), ['developer', 'sales_driver'], true);
    }

    public static function login(array $userData): void
    {
        self::init();
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
            echo "<!DOCTYPE html><html class='dark'><head><title>Akses Ditolak</title><script src='https://cdn.tailwindcss.com'></script></head><body class='bg-slate-950 text-white min-h-screen flex items-center justify-center font-sans'><div class='text-center space-y-4 max-w-md p-8 bg-slate-900 border border-rose-500/30 rounded-3xl'><div class='text-4xl'>🛡️</div><h1 class='text-xl font-bold text-rose-400'>403 - Akses Ditolak</h1><p class='text-xs text-slate-400'>Peran <strong>" . ucfirst(self::role()) . "</strong> Anda tidak memiliki izin mengakses modul ini.</p><a href='{$posUrl}' class='inline-block px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-semibold text-white'>Kembali ke Layar Kasir</a></div></body></html>";
            exit;
        }
    }
}

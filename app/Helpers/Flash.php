<?php
namespace App\Helpers;

class Flash
{
    /**
     * Set a flash notification in session
     * 
     * @param string $type 'success' | 'error' | 'warning' | 'info'
     * @param string $message
     * @param string|null $title
     */
    public static function set(string $type, string $message, ?string $title = null): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['app_flash_notification'] = [
            'type' => $type,
            'title' => $title ?: self::defaultTitle($type),
            'message' => $message,
            'id' => uniqid('flash_')
        ];
    }

    public static function success(string $message, ?string $title = null): void
    {
        self::set('success', $message, $title);
    }

    public static function error(string $message, ?string $title = null): void
    {
        self::set('error', $message, $title);
    }

    public static function warning(string $message, ?string $title = null): void
    {
        self::set('warning', $message, $title);
    }

    public static function info(string $message, ?string $title = null): void
    {
        self::set('info', $message, $title);
    }

    /**
     * Get and clear the flash notification
     */
    public static function get(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['app_flash_notification'])) {
            $flash = $_SESSION['app_flash_notification'];
            unset($_SESSION['app_flash_notification']);
            return $flash;
        }

        return null;
    }

    /**
     * Check if a flash notification exists
     */
    public static function has(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['app_flash_notification']);
    }

    private static function defaultTitle(string $type): string
    {
        return match ($type) {
            'success' => 'Berhasil',
            'error' => 'Gagal / Kesalahan',
            'warning' => 'Perhatian',
            'info' => 'Informasi',
            default => 'Notifikasi',
        };
    }
}

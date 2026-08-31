<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * app/Helpers/CSRF.php
 * Proteksi Cross-Site Request Forgery (CSRF) Token untuk Seluruh Form & AJAX Request.
 */
class CSRF
{
    /**
     * Dapatkan atau buat token CSRF baru di sesi
     */
    public static function token(): string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Render hidden input field untuk form HTML
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Validasi token CSRF dari POST request atau header X-CSRF-TOKEN
     */
    public static function validate(?string $token = null): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (empty($sessionToken)) {
            return false;
        }

        $inputToken = $token ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return hash_equals($sessionToken, (string)$inputToken);
    }
}

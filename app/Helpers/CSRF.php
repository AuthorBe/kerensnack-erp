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
     * Validasi token CSRF dari POST request, JSON body, atau header HTTP (X-CSRF-TOKEN / X-XSRF-TOKEN)
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

        $inputToken = $token 
            ?? $_POST['csrf_token'] 
            ?? $_POST['_csrf_token'] 
            ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
            ?? $_SERVER['HTTP_X_XSRF_TOKEN'] 
            ?? null;

        // Jika request berupa payload JSON dan token belum ditemukan di $_POST/Header
        if (empty($inputToken) && isset($_SERVER['CONTENT_TYPE']) && str_contains(strtolower($_SERVER['CONTENT_TYPE']), 'application/json')) {
            $raw = file_get_contents('php://input');
            if (!empty($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $inputToken = $decoded['csrf_token'] ?? $decoded['_csrf_token'] ?? null;
                }
            }
        }

        return !empty($inputToken) && hash_equals($sessionToken, (string)$inputToken);
    }

    /**
     * Alias untuk validasi token CSRF
     */
    public static function validateToken(?string $token = null): bool
    {
        return self::validate($token);
    }
}

if (!class_exists('App\Helpers\Csrf', false)) {
    class_alias(CSRF::class, 'App\Helpers\Csrf');
}

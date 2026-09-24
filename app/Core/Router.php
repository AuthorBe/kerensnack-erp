<?php
declare(strict_types=1);

namespace App\Core;

/**
 * app/Core/Router.php
 * Smart Dynamic Router (Enterprise VPS Standard with Public DocumentRoot)
 */

class Router
{
    private static array $routes = [];

    public static function get(string $path, array|callable $handler): void
    {
        self::$routes['GET'][$path] = $handler;
    }

    public static function post(string $path, array|callable $handler): void
    {
        self::$routes['POST'][$path] = $handler;
    }

    public static function getBasePath(): string
    {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '\\') {
            return '';
        }

        // Selalu buang suffix '/public' agar base path aplikasi dijamin 100% bersih di semua environment
        if (str_ends_with($scriptDir, '/public')) {
            $scriptDir = substr($scriptDir, 0, -strlen('/public'));
        }

        return ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
    }

    public static function url(string $path = ''): string
    {
        $base = self::getBasePath();
        $cleanPath = '/' . ltrim($path, '/');
        return $base . $cleanPath;
    }

    /**
     * Generate URL for static assets in public/assets/
     * Usage: Router::asset('css/app.css') → /kerensnack-erp/assets/css/app.css
     */
    public static function asset(string $path): string
    {
        $base = self::getBasePath();
        $cleanPath = '/' . ltrim($path, '/');
        return $base . '/assets' . $cleanPath;
    }

    public static function redirect(string $path, int $statusCode = 302): void
    {
        $target = self::url($path);
        header("Location: {$target}", true, $statusCode);
        exit;
    }

    public static function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // 1. Ambil path URL murni
        $parsedUri = parse_url($uri, PHP_URL_PATH) ?? '/';

        // 2. Hilangkan base path dinamis jika ada
        $basePath = self::getBasePath();
        if (!empty($basePath) && str_starts_with($parsedUri, $basePath)) {
            $parsedUri = substr($parsedUri, strlen($basePath));
        }

        // 3. Tangani jika ada /public di URL (Auto-Redirect 301 ke Canonical Clean URL)
        if (str_starts_with($parsedUri, '/public')) {
            $cleanUri = substr($parsedUri, strlen('/public'));
            if ($cleanUri === '' || $cleanUri === '/') {
                $cleanUri = '/';
            }
            if ($method === 'GET') {
                $queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
                self::redirect($cleanUri . $queryString, 301);
                return;
            }
            $parsedUri = $cleanUri;
        }

        $path = '/' . trim($parsedUri, '/');
        if ($path === '//' || $path === '') {
            $path = '/';
        }

        // 4. Cari handler route
        $handler = self::$routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            $faviconUrl = self::url('/assets/images/favicon.png');
            $dashboardUrl = self::url('/dashboard');
            echo "<!DOCTYPE html><html lang='id' class='dark'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>404 - Halaman Tidak Ditemukan</title><link rel='icon' type='image/png' href='{$faviconUrl}'><style>body{margin:0;padding:0;background:#090d16;color:#f8fafc;font-family:system-ui,-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;}.card{max-width:420px;padding:32px;background:#0f172a;border:1px solid rgba(255,255,255,0.1);border-radius:24px;text-align:center;box-shadow:0 20px 40px rgba(0,0,0,0.5);}.code{font-size:48px;font-weight:900;color:#fb7185;font-family:monospace;margin-bottom:8px;}.title{font-size:20px;font-weight:700;margin:0 0 8px;}.desc{font-size:13px;color:#94a3b8;margin:0 0 24px;line-height:1.5;}.btn{display:inline-block;padding:10px 20px;border-radius:12px;background:#e11d48;color:#fff;font-size:13px;font-weight:600;text-decoration:none;transition:background 0.15s;}.btn:hover{background:#be123c;}</style></head><body><div class='card'><div class='code'>404</div><h1 class='title'>Halaman Tidak Ditemukan</h1><p class='desc'>Rute <code>" . htmlspecialchars($path) . "</code> tidak terdaftar.</p><a href='{$dashboardUrl}' class='btn'>Kembali ke Dashboard Utama</a></div></body></html>";
            return;
        }

        // 5. Proteksi Global CSRF untuk seluruh mutasi HTTP (POST, PUT, PATCH, DELETE)
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            if (!\App\Helpers\CSRF::validate()) {
                $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
                    || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

                if ($isAjax) {
                    http_response_code(419);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Sesi keamanan kadaluarsa (CSRF Mismatch). Silakan muat ulang halaman dan coba lagi.'
                    ]);
                    exit;
                }

                if ($path === '/login') {
                    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                        session_start();
                    }
                    $_SESSION['auth_error'] = 'Sesi formulir telah berakhir. Silakan coba masuk kembali.';
                    self::redirect('/login');
                    return;
                }

                if ($path === '/logout') {
                    self::redirect('/logout');
                    return;
                }

                \App\Helpers\Flash::error('Sesi formulir kadaluarsa (CSRF Mismatch). Silakan ulangi aksi Anda.');
                $referer = $_SERVER['HTTP_REFERER'] ?? self::url('/');
                header("Location: {$referer}");
                exit;
            }
        }

        if (is_callable($handler)) {
            call_user_func($handler);
            return;
        }

        if (is_array($handler)) {
            [$controllerClass, $action] = $handler;
            if (class_exists($controllerClass)) {
                $controllerInstance = new $controllerClass();
                if (method_exists($controllerInstance, $action)) {
                    $controllerInstance->$action();
                    return;
                }
            }
        }

        http_response_code(500);
        echo "Handler tidak valid untuk route {$path}.";
    }
}

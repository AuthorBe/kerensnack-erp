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

        // Jika diakses lewat Root Bridge (.htaccess) tanpa kata 'public' di URL
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        if (str_ends_with($scriptDir, '/public') && !str_contains($requestUri, '/public')) {
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

    public static function redirect(string $path): void
    {
        $target = self::url($path);
        header("Location: {$target}");
        exit;
    }

    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // 1. Ambil path URL murni
        $parsedUri = parse_url($uri, PHP_URL_PATH) ?? '/';

        // 2. Hilangkan base path dinamis jika ada
        $basePath = self::getBasePath();
        if (!empty($basePath) && str_starts_with($parsedUri, $basePath)) {
            $parsedUri = substr($parsedUri, strlen($basePath));
        }

        // 3. Tangani jika ada /public di URL
        if (str_starts_with($parsedUri, '/public')) {
            $parsedUri = substr($parsedUri, strlen('/public'));
        }

        $path = '/' . trim($parsedUri, '/');
        if ($path === '//' || $path === '') {
            $path = '/';
        }

        // 4. Cari handler route
        $handler = self::$routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            $posUrl = self::url('/pos');
            echo "<!DOCTYPE html><html class='dark'><head><title>404 Not Found</title><script src='https://cdn.tailwindcss.com'></script></head><body class='bg-[#0b0f19] text-white min-h-screen flex items-center justify-center font-sans'><div class='text-center space-y-4 max-w-md p-8 bg-[#111827] border border-slate-800 rounded-3xl'><div class='text-5xl font-black text-emerald-500 font-mono'>404</div><h1 class='text-xl font-bold'>Halaman Tidak Ditemukan</h1><p class='text-xs text-slate-400 font-mono'>Rute <code>{$path}</code> tidak terdaftar.</p><a href='{$posUrl}' class='inline-block px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs font-semibold text-white transition'>Buka Layar Kasir POS</a></div></body></html>";
            return;
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

<?php
declare(strict_types=1);

/**
 * config/env.php
 * Sederhana & Cepat: Membaca file .env dan memasukkannya ke getenv() dan $_ENV.
 */

if (!function_exists('loadEnv')) {
    function loadEnv(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Hapus tanda kutip jika ada
                $value = trim($value, '"\'');

                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Auto-load dari root aplikasi
loadEnv(dirname(__DIR__) . '/.env');

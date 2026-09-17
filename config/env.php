<?php
declare(strict_types=1);

/**
 * config/env.php
 * Sederhana & Cepat: Membaca file .env dan memasukkannya ke getenv() dan $_ENV.
 */

// Sederhana & Cepat: Membaca file .env dan selalu menginjeksi nilai ke $_ENV, $_SERVER, dan putenv()
(function(string $path): void {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (str_contains($line, '=')) {
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
})(dirname(__DIR__) . '/.env');

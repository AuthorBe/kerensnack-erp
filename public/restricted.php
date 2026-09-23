<?php
declare(strict_types=1);

/**
 * public/restricted.php
 * Standalone entrypoint for HTTP 403 / 404 ErrorDocument handlers
 * Keren One
 */

http_response_code(403);

// Include standard router bootstrap if available for base path resolution
$rootPath = dirname(__DIR__);
if (file_exists($rootPath . '/app/Core/Router.php')) {
    require_once $rootPath . '/app/Core/Router.php';
}

$viewFile = $rootPath . '/views/errors/restricted.php';
if (file_exists($viewFile)) {
    require $viewFile;
} else {
    echo "<!DOCTYPE html><html><head><title>403 Forbidden</title><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, viewport-fit=cover\"><meta name=\"theme-color\" content=\"#881337\"></head><body style='font-family:sans-serif;text-align:center;padding:50px;background:#090d16;color:#f8fafc;'><h1>403 - Akses Dibatasi</h1><p>Halaman atau berkas tidak dapat diakses.</p><a href='/' style='color:#881337;'>Kembali ke Beranda</a></body></html>";
}

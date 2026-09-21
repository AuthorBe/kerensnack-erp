<?php
declare(strict_types=1);

/**
 * public/restricted.php
 * Standalone entrypoint for HTTP 403 / 404 ErrorDocument handlers
 * KEREN SNACK ERP
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
    // Ultimate fallback if views/ is somehow unreachable
    echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body style='font-family:sans-serif;text-align:center;padding:50px;background:#090d16;color:#f8fafc;'><h1>403 - Akses Dibatasi</h1><p>Halaman atau berkas tidak dapat diakses.</p><a href='/' style='color:#e11d48;'>Kembali ke Beranda</a></body></html>";
}

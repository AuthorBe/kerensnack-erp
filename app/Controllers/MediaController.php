<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\R2StorageService;
use App\Services\MediaCacheService;

/**
 * app/Controllers/MediaController.php
 * Endpoint Media Proxy & Caching Lokal Server (Zero Class B R2 requests on cache hit).
 * Menjamin keamanan autentikasi, penyajian instan, dan auto-cleanup berkas 14 hari.
 */
class MediaController extends Controller
{
    /**
     * Kategori folder yang diizinkan untuk diakses via proxy media.
     */
    private const ALLOWED_PREFIXES = [
        'receipts/',
        'deliveries/',
        'delivery_issues/',
        'consignments/',
        'expenses/',
        'company/',
    ];

    /**
     * GET /media/view?path=receipts/2026/09/xxx.webp
     * Menyajikan berkas gambar dari cache lokal server (<= 14 hari).
     * Jika belum ada di cache atau sudah > 14 hari, unduh 1x dari R2, simpan di cache, lalu sajikan.
     */
    public function serveFile(): void
    {
        // 1. Validasi Autentikasi Pengguna
        if (!Auth::check()) {
            http_response_code(401);
            echo 'Autentikasi diperlukan untuk melihat berkas.';
            exit;
        }

        $rawPath = trim($_GET['path'] ?? '');
        if ($rawPath === '') {
            http_response_code(400);
            echo 'Parameter path berkas wajib disertakan.';
            exit;
        }

        // 2. Sanitasi Ketat (Anti Directory Traversal & Arbitrary File Read)
        $cleanPath = ltrim(trim(str_replace('\\', '/', $rawPath)), '/');
        $cleanPath = str_replace('..', '', $cleanPath);
        $cleanPath = preg_replace('#/+#', '/', $cleanPath);

        // Jika path mengarah ke aset publik statis lokal
        if (str_starts_with($cleanPath, 'assets/') || str_starts_with($cleanPath, 'favicon/')) {
            $localStatic = rtrim(ROOT_PATH, '/\\') . '/public/' . $cleanPath;
            if (file_exists($localStatic) && is_readable($localStatic)) {
                $this->outputLocalFile($localStatic);
                return;
            }
        }

        // Validasi ekstensi yang diizinkan
        $ext = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));
        $allowedExts = ['webp', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'pdf'];
        if (!in_array($ext, $allowedExts, true)) {
            http_response_code(403);
            echo 'Format berkas tidak diizinkan.';
            exit;
        }

        // 3. Probabilistic Garbage Collector (1 dari 50 request) untuk auto-cleanup berkas > 14 hari
        if (mt_rand(1, 50) === 1) {
            MediaCacheService::cleanExpired(MediaCacheService::DEFAULT_EXPIRY_DAYS);
        }

        // 4. Periksa apakah berkas cache lokal masih valid (ada dan <= 14 hari)
        if (MediaCacheService::hasValidCache($cleanPath, MediaCacheService::DEFAULT_EXPIRY_DAYS)) {
            $cachedFile = MediaCacheService::getFilePath($cleanPath);
            $this->outputLocalFile($cachedFile);
            return;
        }

        // 5. Cek apakah berkas ada di direktori legacy public/uploads lokal (transisi/fallback)
        $legacyPath = rtrim(ROOT_PATH, '/\\') . '/public/uploads/' . $cleanPath;
        if (file_exists($legacyPath) && is_readable($legacyPath)) {
            $this->outputLocalFile($legacyPath);
            return;
        }

        // 6. Berkas belum ada di cache lokal atau sudah kedaluwarsa (> 14 hari).
        // Unduh 1x dari Cloudflare R2 privat dan simpan ke folder cache server
        $targetCachePath = MediaCacheService::getFilePath($cleanPath);
        $r2Service = R2StorageService::getInstance();

        if ($r2Service->isConfigured()) {
            $downloaded = $r2Service->downloadFile($cleanPath, $targetCachePath);
            if ($downloaded && file_exists($targetCachePath) && filesize($targetCachePath) > 0) {
                // Berhasil diunduh dan tersimpan di cache lokal!
                @touch($targetCachePath, time()); // Reset timer 14 hari
                $this->outputLocalFile($targetCachePath);
                return;
            }
        }

        // 7. Jika berkas benar-benar tidak ditemukan di R2 maupun lokal
        http_response_code(404);
        header('Content-Type: text/plain');
        echo 'Berkas media tidak ditemukan di server maupun Cloudflare R2.';
        exit;
    }

    /**
     * Endpoint API JSON Presigned URL On-Demand (Kompatibilitas Backward).
     */
    public function getPresignedUrl(): void
    {
        if (!Auth::check()) {
            $this->json(['success' => false, 'message' => 'Autentikasi diperlukan'], 401);
            return;
        }

        $path = trim($_GET['path'] ?? '');
        if ($path === '') {
            $this->json(['success' => false, 'message' => 'Parameter path wajib diisi'], 400);
            return;
        }

        if (str_contains($path, '..')) {
            $this->json(['success' => false, 'message' => 'Path tidak valid'], 400);
            return;
        }

        // Kembalikan URL proxy internal agar frontend selalu lewat cache lokal server
        $proxyUrl = \App\Core\Router::url('/media/view?path=' . urlencode($path));

        $this->json([
            'success' => true,
            'path'    => $path,
            'url'     => $proxyUrl,
            'cached'  => MediaCacheService::hasValidCache($path),
        ]);
    }

    /**
     * GET /api/media/cache-stats
     * Dapatkan informasi ukuran cache lokal server dan jumlah file kedaluwarsa.
     */
    public function cacheStats(): void
    {
        if (!Auth::check()) {
            $this->json(['success' => false, 'message' => 'Autentikasi diperlukan'], 401);
            return;
        }

        $stats = MediaCacheService::getStats(MediaCacheService::DEFAULT_EXPIRY_DAYS);
        $this->json(['success' => true, 'data' => $stats]);
    }

    /**
     * POST /api/media/clear-cache
     * Pembersihan manual seluruh berkas cache lokal oleh Admin/Owner.
     */
    public function clearCache(): void
    {
        Auth::requireRole(['owner', 'admin']);

        $deleted = MediaCacheService::clearAll();
        $this->json([
            'success' => true,
            'message' => "Berhasil membersihkan {$deleted} berkas cache media lokal.",
            'deleted' => $deleted
        ]);
    }

    /**
     * Sajikan berkas fisik lokal ke browser pengguna dengan header caching HTTP optimal.
     */
    private function outputLocalFile(string $filePath): void
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            echo 'Berkas tidak ditemukan.';
            exit;
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeMap = [
            'webp' => 'image/webp',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'pdf'  => 'application/pdf',
        ];

        $mime = $mimeMap[$ext] ?? 'application/octet-stream';
        $size = filesize($filePath);
        $mtime = filemtime($filePath);

        // Header HTTP Caching untuk browser pengguna
        header("Content-Type: {$mime}");
        header("Content-Length: {$size}");
        header("Last-Modified: " . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
        header("Cache-Control: private, max-age=86400, stale-while-revalidate=604800");
        header("Pragma: cache");

        // Dukungan 304 Not Modified jika browser sudah memiliki salinan yang sama
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            if ($ifModifiedSince !== false && $ifModifiedSince >= $mtime) {
                http_response_code(304);
                exit;
            }
        }

        // Stream file langsung ke client
        readfile($filePath);
        exit;
    }
}

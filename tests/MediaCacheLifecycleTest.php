<?php
/**
 * tests/MediaCacheLifecycleTest.php
 * Script Verifikasi Komprehensif Media Cache Proxy & Siklus Pembersihan 14 Hari
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/config/env.php';
require_once ROOT_PATH . '/vendor/autoload.php';

use App\Services\MediaCacheService;
use App\Helpers\Upload;

$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$name}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$name} - {$detail}\n";
        $failed++;
    }
}

echo "====================================================================\n";
echo "  TEST SUITE: MEDIA CACHE PROXY & 14-DAY AUTO-CLEANUP LIFECYCLE\n";
echo "====================================================================\n\n";

// TEST 1: Direktori Cache & Proteksi .htaccess
echo "--- TEST 1: CACHE DIRECTORY & SECURITY INFRASTRUCTURE ---\n";
$cacheDir = MediaCacheService::ensureCacheDirectory();
assertTest("Direktori storage/cache/media terbuat", is_dir($cacheDir));
assertTest("File storage/cache/.htaccess terbuat untuk proteksi eksekusi skrip", file_exists(dirname($cacheDir) . '/.htaccess'));

$tempFile = sys_get_temp_dir() . '/test_media_' . bin2hex(random_bytes(8)) . '.txt';
file_put_contents($tempFile, 'TEST_IMAGE_CONTENT_SAMPLE');

register_shutdown_function(function() use ($tempFile) {
    if (file_exists($tempFile)) {
        @unlink($tempFile);
    }
    if (class_exists('App\Services\MediaCacheService')) {
        MediaCacheService::clearAll();
    }
});

$testKey = 'receipts/2026/09/test_receipt_sample.webp';
$saved = MediaCacheService::saveToCache($testKey, $tempFile);
assertTest("Berhasil menyimpan file ke cache lokal", $saved);

$cachedPath = MediaCacheService::getFilePath($testKey);
assertTest("File cache fisik ada di harddisk server", file_exists($cachedPath));
assertTest("MediaCacheService::hasValidCache() mengembalikan true untuk file baru", MediaCacheService::hasValidCache($testKey, 14));

// TEST 3: Simulasi & Jaminan Penghapusan 14 Hari (On-the-fly)
echo "\n--- TEST 3: ON-THE-FLY CLEANUP UNTUK BERKAS KEDALUWARSA (> 14 HARI) ---\n";
$expiredKey = 'receipts/2026/09/expired_receipt_15days.webp';
MediaCacheService::saveToCache($expiredKey, $tempFile);
$expiredPath = MediaCacheService::getFilePath($expiredKey);

// Manipulasi waktu modifikasi berkas menjadi 15 hari yang lalu (15 * 86400 detik)
touch($expiredPath, time() - (15 * 86400));
assertTest("Simulasi berkas usia 15 hari berhasil disetel", (time() - filemtime($expiredPath)) >= (15 * 86400));

// Panggil hasValidCache() — harus mendeteksi kedaluwarsa dan langsung menghapus fisik berkasnya
$isValid = MediaCacheService::hasValidCache($expiredKey, 14);
assertTest("hasValidCache() mengembalikan false untuk berkas usia 15 hari", $isValid === false);
assertTest("Berkas kedaluwarsa BENAR-BENAR TERHAPUS secara fisik dari harddisk", !file_exists($expiredPath));

// TEST 4: Garbage Collection Bersih-Bersih Massal (cleanExpired)
echo "\n--- TEST 4: BATCH GARBAGE COLLECTOR (cleanExpired) ---\n";
$batchExpiredKey1 = 'deliveries/2026/09/expired_deliv_1.webp';
$batchExpiredKey2 = 'deliveries/2026/09/expired_deliv_2.webp';
$batchFreshKey    = 'deliveries/2026/09/fresh_deliv.webp';

MediaCacheService::saveToCache($batchExpiredKey1, $tempFile);
MediaCacheService::saveToCache($batchExpiredKey2, $tempFile);
MediaCacheService::saveToCache($batchFreshKey, $tempFile);

$p1 = MediaCacheService::getFilePath($batchExpiredKey1);
$p2 = MediaCacheService::getFilePath($batchExpiredKey2);
$pFresh = MediaCacheService::getFilePath($batchFreshKey);

// Buat 2 berkas berusia 16 hari & 20 hari
touch($p1, time() - (16 * 86400));
touch($p2, time() - (20 * 86400));

$deletedCount = MediaCacheService::cleanExpired(14);
assertTest("cleanExpired(14) menghapus minimal 2 berkas kedaluwarsa", $deletedCount >= 2);
assertTest("Berkas kedaluwarsa 1 terhapus fisik", !file_exists($p1));
assertTest("Berkas kedaluwarsa 2 terhapus fisik", !file_exists($p2));
assertTest("Berkas baru (< 14 hari) tetap aman dan tidak terhapus", file_exists($pFresh));

// TEST 5: Upload::presignedUrl Rerouting ke Media Proxy
echo "\n--- TEST 5: UPLOAD HELPER ROUTING VIA PROXY ---\n";
$proxyUrl = Upload::presignedUrl('receipts/2026/09/nota_123.webp');
assertTest("Upload::presignedUrl mengembalikan link ke proxy internal /media/view", str_contains($proxyUrl ?? '', '/media/view?path=receipts%2F2026%2F09%2Fnota_123.webp'));

$externalUrl = 'https://example.com/gambar.jpg';
assertTest("Upload::presignedUrl mempertahankan URL HTTPS eksternal", Upload::presignedUrl($externalUrl) === $externalUrl);

// TEST 6: Statistik Cache & Pembersihan Total (clearAll)
echo "\n--- TEST 6: STATS & CLEAR ALL ---\n";
$stats = MediaCacheService::getStats(14);
assertTest("getStats() mengembalikan array informasi lengkap", isset($stats['total_files'], $stats['total_bytes'], $stats['expired_files']));
assertTest("total_files > 0 saat masih ada berkas", $stats['total_files'] > 0);

$cleared = MediaCacheService::clearAll();
assertTest("clearAll() berhasil membersihkan seluruh berkas cache", $cleared > 0);

$statsAfter = MediaCacheService::getStats(14);
assertTest("total_files menjadi 0 setelah clearAll()", $statsAfter['total_files'] === 0);

// Bersihkan file temp
@unlink($tempFile);

echo "\n====================================================================\n";
echo "  HASIL PENGUJIAN: {$passed} BERHASIL, {$failed} GAGAL\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}

<?php
declare(strict_types=1);

namespace App\Services;

/**
 * app/Services/MediaCacheService.php
 * Layanan Pengelolaan Cache Media Lokal Server ERP & Auto-Cleanup 14 Hari.
 *
 * Menyimpan salinan sementara berkas Cloudflare R2 di harddisk server lokal
 * untuk memangkas Class B Request ke 0, dan menjamin pembersihan berkas
 * secara fisik setelah 14 hari agar server tidak penuh.
 */
class MediaCacheService
{
    public const DEFAULT_EXPIRY_DAYS = 14;

    /**
     * Dapatkan path absolut direktori root cache media lokal.
     */
    public static function getCacheDirectory(): string
    {
        $baseRoot = rtrim(str_replace('\\', '/', ROOT_PATH), '/');
        return $baseRoot . '/storage/cache/media';
    }

    /**
     * Pastikan direktori cache media dan proteksi keamanannya tersedia.
     */
    public static function ensureCacheDirectory(): string
    {
        $dir = self::getCacheDirectory();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $gitkeep = dirname($dir) . '/.gitkeep';
        if (!file_exists($gitkeep)) {
            @file_put_contents($gitkeep, '');
        }

        // Proteksi .htaccess di root storage/cache agar file cache tidak dapat dieksekusi sebagai skrip
        $storageCache = dirname($dir);
        $htaccess = $storageCache . '/.htaccess';
        if (!file_exists($htaccess)) {
            $htContent = "# Block direct script execution in storage/cache\n"
                       . "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|php8|phps|phar|sh|pl|cgi|py)$\">\n"
                       . "    Require all denied\n"
                       . "</FilesMatch>\n"
                       . "Options -Indexes\n";
            @file_put_contents($htaccess, $htContent);
        }

        return $dir;
    }

    /**
     * Dapatkan path absolut file cache lokal dari sebuah object key R2.
     *
     * @param string $objectKey Misal: receipts/2026/09/a1b2c3d4.webp
     * @return string Path absolut file cache di harddisk server
     */
    public static function getFilePath(string $objectKey): string
    {
        $cleanKey = ltrim(trim(str_replace('\\', '/', $objectKey)), '/');
        // Sanitasi cegah path traversal
        $cleanKey = str_replace('..', '', $cleanKey);
        $cleanKey = preg_replace('#/+#', '/', $cleanKey);

        return self::getCacheDirectory() . '/' . $cleanKey;
    }

    /**
     * Periksa apakah berkas cache tersedia dan masih dalam masa berlaku (<= 14 hari).
     * Jika berkas sudah lewat dari 14 hari, berkas langsung dihapus secara fisik saat itu juga (On-the-fly cleanup).
     *
     * @param string $objectKey
     * @param int $maxDays Batas usia berkas dalam hari (default: 14 hari)
     * @return bool True jika berkas ada dan masih berlaku
     */
    public static function hasValidCache(string $objectKey, int $maxDays = self::DEFAULT_EXPIRY_DAYS): bool
    {
        $localPath = self::getFilePath($objectKey);
        if (!file_exists($localPath) || !is_file($localPath)) {
            return false;
        }

        $mtime = @filemtime($localPath);
        if ($mtime === false) {
            return false;
        }

        $ageSeconds = time() - $mtime;
        $maxSeconds = $maxDays * 86400;

        // Jika sudah melewati batas 14 hari, hapus berkas fisik seketika
        if ($ageSeconds > $maxSeconds) {
            @unlink($localPath);
            return false;
        }

        return true;
    }

    /**
     * Simpan file lokal ke direktori cache media.
     *
     * @param string $objectKey Kunci objek (misal: receipts/2026/09/xxx.webp)
     * @param string $sourceFilePath Path file sumber yang akan disalin ke cache
     * @return bool True jika berhasil disimpan
     */
    public static function saveToCache(string $objectKey, string $sourceFilePath): bool
    {
        if (!file_exists($sourceFilePath) || !is_readable($sourceFilePath)) {
            return false;
        }

        self::ensureCacheDirectory();
        $targetPath = self::getFilePath($objectKey);
        $targetDir = dirname($targetPath);

        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        if (@copy($sourceFilePath, $targetPath)) {
            // Set waktu modifikasi saat ini agar usia 14 hari dihitung mulai dari sekarang
            @touch($targetPath, time());
            return true;
        }

        return false;
    }

    /**
     * Hapus berkas cache tertentu secara fisik dari server.
     */
    public static function deleteCache(string $objectKey): bool
    {
        $targetPath = self::getFilePath($objectKey);
        if (file_exists($targetPath) && is_file($targetPath)) {
            return @unlink($targetPath);
        }
        return true;
    }

    /**
     * Pembersihan Otomatis Berkas Kedaluwarsa (> 14 Hari).
     * Memindai seluruh folder storage/cache/media dan menghapus berkas-berkas yang melewati 14 hari.
     * Juga merapikan folder-folder kosong yang ditinggalkan.
     *
     * @param int $maxDays Batas hari kedaluwarsa (default 14 hari)
     * @return int Jumlah berkas fisik yang berhasil dihapus
     */
    public static function cleanExpired(int $maxDays = self::DEFAULT_EXPIRY_DAYS): int
    {
        $baseDir = self::getCacheDirectory();
        if (!is_dir($baseDir)) {
            return 0;
        }

        $now = time();
        $maxSeconds = $maxDays * 86400;
        $deletedCount = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                /** @var \SplFileInfo $item */
                $pathname = $item->getPathname();

                if ($item->isFile()) {
                    // Abaikan file penanda git/htaccess
                    $filename = $item->getFilename();
                    if ($filename === '.gitkeep' || $filename === '.htaccess') {
                        continue;
                    }

                    $mtime = $item->getMTime();
                    if (($now - $mtime) > $maxSeconds) {
                        if (@unlink($pathname)) {
                            $deletedCount++;
                        }
                    }
                } elseif ($item->isDir()) {
                    // Bersihkan subdirektori jika sudah kosong
                    @rmdir($pathname);
                }
            }
        } catch (\Throwable $e) {
            error_log('[MediaCacheService::cleanExpired] Error during cache cleanup: ' . $e->getMessage());
        }

        return $deletedCount;
    }

    /**
     * Hapus SEMUA berkas cache secara instan (Manual Clear).
     *
     * @return int Jumlah berkas yang dihapus
     */
    public static function clearAll(): int
    {
        $baseDir = self::getCacheDirectory();
        if (!is_dir($baseDir)) {
            return 0;
        }

        $deletedCount = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                /** @var \SplFileInfo $item */
                $pathname = $item->getPathname();
                if ($item->isFile()) {
                    $filename = $item->getFilename();
                    if ($filename === '.gitkeep' || $filename === '.htaccess') {
                        continue;
                    }
                    if (@unlink($pathname)) {
                        $deletedCount++;
                    }
                } elseif ($item->isDir()) {
                    @rmdir($pathname);
                }
            }
        } catch (\Throwable $e) {
            error_log('[MediaCacheService::clearAll] Error during clearAll: ' . $e->getMessage());
        }

        return $deletedCount;
    }

    /**
     * Dapatkan statistik penggunaan cache media lokal.
     *
     * @param int $maxDays
     * @return array
     */
    public static function getStats(int $maxDays = self::DEFAULT_EXPIRY_DAYS): array
    {
        $baseDir = self::getCacheDirectory();
        if (!is_dir($baseDir)) {
            return [
                'total_files'    => 0,
                'total_bytes'    => 0,
                'total_size_mb'  => 0.0,
                'expired_files'  => 0,
                'cache_dir'      => $baseDir,
                'max_days'       => $maxDays,
            ];
        }

        $totalFiles = 0;
        $totalBytes = 0;
        $expiredFiles = 0;
        $now = time();
        $maxSeconds = $maxDays * 86400;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                if ($file->isFile()) {
                    $fn = $file->getFilename();
                    if ($fn === '.gitkeep' || $fn === '.htaccess') {
                        continue;
                    }

                    $totalFiles++;
                    $size = $file->getSize();
                    $totalBytes += $size;

                    if (($now - $file->getMTime()) > $maxSeconds) {
                        $expiredFiles++;
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('[MediaCacheService::getStats] Error reading cache stats: ' . $e->getMessage());
        }

        return [
            'total_files'    => $totalFiles,
            'total_bytes'    => $totalBytes,
            'total_size_mb'  => round($totalBytes / (1024 * 1024), 2),
            'expired_files'  => $expiredFiles,
            'cache_dir'      => $baseDir,
            'max_days'       => $maxDays,
        ];
    }
}

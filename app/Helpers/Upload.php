<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * app/Helpers/Upload.php
 * Helper manajemen berkas unggahan (uploads) dan auto-infrastruktur direktori di server.
 * Menjamin zero double-folder bug, cross-platform path safety, dan proteksi eksekusi skrip.
 */
class Upload
{
    /**
     * Subdirektori unggahan bawaan dalam aplikasi.
     */
    public const SUBDIRS = [
        'purchases',
        'delivery_proofs',
        'company',
    ];

    /**
     * Dapatkan path absolut direktori unggahan dengan sanitasi penuh (Anti Dobel Folder).
     *
     * @param string $subdir Nama subdirektori (misal: 'purchases', 'delivery_proofs', atau 'uploads/purchases')
     * @return string Path absolut yang dinormalisasi (contoh: 'D:/laragon/www/kerensnack-erp/public/uploads/purchases')
     */
    public static function getDirectory(string $subdir = ''): string
    {
        $baseRoot = rtrim(str_replace('\\', '/', ROOT_PATH), '/');
        $baseUploads = $baseRoot . '/public/uploads';

        if (trim($subdir) === '') {
            return $baseUploads;
        }

        // Sanitasi parameter input:
        // Hapus prefix variatif yang mungkin tidak sengaja dikirim oleh developer,
        // seperti 'public/uploads/', 'uploads/', '/uploads/', '\uploads\', dll.
        $cleanSubdir = str_replace('\\', '/', $subdir);
        $cleanSubdir = trim($cleanSubdir, '/');
        $cleanSubdir = preg_replace('#^(public/)?uploads/?#i', '', $cleanSubdir);
        $cleanSubdir = trim($cleanSubdir, '/');

        return $cleanSubdir !== '' ? ($baseUploads . '/' . $cleanSubdir) : $baseUploads;
    }

    /**
     * Pastikan direktori unggahan dan infrastruktur keamanannya (.htaccess & .gitkeep) telah terbuat.
     * Dijamin aman dari race condition konkurensi dan error folder dobel.
     *
     * @param string $subdir Subdirektori spesifik atau kosong untuk root uploads
     * @return string Path absolut direktori yang sudah dipastikan siap
     */
    public static function ensureDirectory(string $subdir = ''): string
    {
        $targetDir = self::getDirectory($subdir);

        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        // Pastikan file .gitkeep ada agar struktur folder dapat terlacak oleh Git jika kosong
        $gitkeepFile = $targetDir . '/.gitkeep';
        if (!file_exists($gitkeepFile)) {
            @file_put_contents($gitkeepFile, '');
        }

        // Pastikan root uploads memiliki proteksi .htaccess untuk memblokir eksekusi skrip berbahaya
        $rootUploads = self::getDirectory('');
        $rootHtaccess = $rootUploads . '/.htaccess';
        if (!file_exists($rootHtaccess)) {
            $htaccessContent = "# Disable direct script execution in uploads directory\n"
                             . "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|php8|phps|phar|sh|pl|cgi|py)$\">\n"
                             . "    Require all denied\n"
                             . "</FilesMatch>\n"
                             . "Options -Indexes\n";
            @file_put_contents($rootHtaccess, $htaccessContent);
        }

        return $targetDir;
    }

    /**
     * Inisialisasi direktori aplikasi yang diperlukan (aset logo lokal & media cache).
     */
    public static function initDirectories(): void
    {
        $logoDir = ROOT_PATH . '/public/assets/img/logo';
        if (!is_dir($logoDir)) {
            @mkdir($logoDir, 0755, true);
        }

        // Pastikan direktori storage/cache/media dan keamanannya (.htaccess) auto-generate saat deploy
        \App\Services\MediaCacheService::ensureCacheDirectory();
    }

    /**
     * Kompresi dan simpan gambar dengan resolusi teroptimasi, auto-rotate EXIF, dan format WebP/JPEG.
     *
     * @param string $tmpPath Path sementara file upload
     * @param string $targetPath Path absolut file tujuan penyimpanan
     * @param int $maxDimension Batas dimensi maksimal sisi terpanjang (default: 1600px)
     * @param int $quality Kualitas kompresi 0-100 (default: 80)
     * @return bool True jika berhasil dikompres dan disimpan
     */
    public static function compressAndSaveImage(string $tmpPath, string $targetPath, int $maxDimension = 1600, int $quality = 80): bool
    {
        $imgInfo = @getimagesize($tmpPath);
        if ($imgInfo === false) {
            return false;
        }

        $mime = $imgInfo['mime'] ?? '';
        $srcImage = null;

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $srcImage = @imagecreatefromjpeg($tmpPath);
                break;
            case 'image/png':
                $srcImage = @imagecreatefrompng($tmpPath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $srcImage = @imagecreatefromwebp($tmpPath);
                }
                break;
        }

        if (!$srcImage) {
            return false;
        }

        // Auto-rotate berdasarkan EXIF Orientation (khusus foto langsung dari kamera smartphone)
        if (function_exists('exif_read_data') && ($mime === 'image/jpeg' || $mime === 'image/jpg')) {
            $exif = @exif_read_data($tmpPath);
            if (!empty($exif['Orientation'])) {
                switch ((int)$exif['Orientation']) {
                    case 3:
                        $rotated = @imagerotate($srcImage, 180, 0);
                        if ($rotated) { imagedestroy($srcImage); $srcImage = $rotated; }
                        break;
                    case 6:
                        $rotated = @imagerotate($srcImage, -90, 0);
                        if ($rotated) { imagedestroy($srcImage); $srcImage = $rotated; }
                        break;
                    case 8:
                        $rotated = @imagerotate($srcImage, 90, 0);
                        if ($rotated) { imagedestroy($srcImage); $srcImage = $rotated; }
                        break;
                }
            }
        }

        $origW = imagesx($srcImage);
        $origH = imagesy($srcImage);

        // Hitung skala baru (maksimal $maxDimension px agar tulisan nota tetap sangat tajam tapi file sangat ringan)
        if ($origW > $maxDimension || $origH > $maxDimension) {
            if ($origW >= $origH) {
                $newW = $maxDimension;
                $newH = (int)round(($origH / $origW) * $maxDimension);
            } else {
                $newH = $maxDimension;
                $newW = (int)round(($origW / $origH) * $maxDimension);
            }
        } else {
            $newW = $origW;
            $newH = $origH;
        }

        $targetImage = imagecreatetruecolor($newW, $newH);

        // Isi latar belakang putih bersih
        $whiteBg = imagecolorallocate($targetImage, 255, 255, 255);
        imagefilledrectangle($targetImage, 0, 0, $newW, $newH, $whiteBg);

        imagecopyresampled($targetImage, $srcImage, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        $saved = false;
        $targetExt = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

        if ($targetExt === 'webp' && function_exists('imagewebp')) {
            $saved = @imagewebp($targetImage, $targetPath, $quality);
        } else {
            $saved = @imagejpeg($targetImage, $targetPath, $quality);
        }

        imagedestroy($srcImage);
        imagedestroy($targetImage);

        return $saved;
    }

    /**
     * Simpan file unggahan gambar secara aman dengan kompresi server-side dan penamaan acak (Anti-Tracking).
     *
     * @param array $file Array $_FILES['input_name']
     * @param string $subdir Subdirektori target ('purchases', 'delivery_proofs')
     * @param string $prefix Tidak lagi dipakai sebagai prefix publik (disimpan untuk kompatibilitas fungsi)
     * @param int $maxBytes Ukuran maksimal unggah awal dalam bytes (default 10MB)
     * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
     */
    public static function storeImage(array $file, string $subdir, string $prefix = 'FILE', int $maxBytes = 10485760): array
    {
        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'path' => null, 'error' => 'Tidak ada berkas yang diunggah.'];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'path' => null, 'error' => 'Gagal mengunggah berkas (Kode error: ' . $file['error'] . ').'];
        }

        if ($file['size'] > $maxBytes) {
            $maxMb = round($maxBytes / (1024 * 1024), 1);
            return ['success' => false, 'path' => null, 'error' => "Ukuran berkas melebihi batas maksimal {$maxMb}MB."];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExts, true)) {
            return ['success' => false, 'path' => null, 'error' => 'Format berkas harus berupa gambar (JPG, JPEG, PNG, atau WebP).'];
        }

        $imgInfo = @getimagesize($file['tmp_name']);
        if ($imgInfo === false) {
            return ['success' => false, 'path' => null, 'error' => 'Berkas bukan merupakan gambar valid.'];
        }

        // Normalisasi Kategori R2 berdasarkan subdir
        $categoryMap = [
            'purchases'           => 'receipts',
            'receipts'            => 'receipts',
            'delivery_proofs'     => 'deliveries',
            'deliveries'          => 'deliveries',
            'delivery_issues'     => 'delivery_issues',
            'consignment_returns' => 'consignments',
            'consignments'        => 'consignments',
            'expenses'            => 'expenses',
            'draf_pengeluaran'    => 'expenses',
        ];

        $cleanSubdir = strtolower(trim(str_replace('\\', '/', $subdir), '/'));
        $cleanSubdir = preg_replace('#^(public/)?uploads/?#i', '', $cleanSubdir);
        $cleanSubdir = trim($cleanSubdir, '/');
        $r2Category = $categoryMap[$cleanSubdir] ?? 'receipts';

        // Buat file sementara di temp direktori lokal untuk proses kompresi
        $tempDir = sys_get_temp_dir();
        $randomHash = bin2hex(random_bytes(16));
        $targetExt = function_exists('imagewebp') ? 'webp' : 'jpg';
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . 'r2_upload_' . $randomHash . '.' . $targetExt;

        // Kompresi resolusi dan kualitas gambar secara server-side
        $compressed = self::compressAndSaveImage($file['tmp_name'], $tempPath, 1600, 80);

        if (!$compressed) {
            // Fallback jika kompresi GD terkendala
            $fallbackExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $targetExt = $fallbackExt;
            $tempPath = $tempDir . DIRECTORY_SEPARATOR . 'r2_upload_' . $randomHash . '.' . $fallbackExt;
            if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
                return ['success' => false, 'path' => null, 'error' => 'Gagal memproses berkas sementara di server.'];
            }
        }

        // Upload ke Cloudflare R2 privat
        try {
            $r2Service = \App\Services\R2StorageService::getInstance();
            if ($r2Service->isConfigured()) {
                $objectKey = $r2Service->uploadFile($tempPath, $r2Category, $targetExt);
                // Simpan salinan ke cache lokal server (storage/cache/media) agar saat pertama dibuka staf 0 Class B request
                \App\Services\MediaCacheService::saveToCache($objectKey, $tempPath);
                @unlink($tempPath); // Bersihkan file temp lokal
                return ['success' => true, 'path' => $objectKey, 'error' => null];
            }

            // Fallback jika R2 belum dikonfigurasi di .env (misal masa development lokal)
            $targetDir = self::ensureDirectory($subdir);
            $fileName = $randomHash . '.' . $targetExt;
            $localTarget = $targetDir . '/' . $fileName;
            @copy($tempPath, $localTarget);
            @unlink($tempPath);

            $webPath = ($cleanSubdir !== '' ? $cleanSubdir . '/' : '') . $fileName;
            return ['success' => true, 'path' => $webPath, 'error' => null];
        } catch (\Throwable $e) {
            @unlink($tempPath);
            error_log('[Upload::storeImage] R2 Upload Failure: ' . $e->getMessage());
            return ['success' => false, 'path' => null, 'error' => 'Gagal menyimpan berkas ke Cloudflare R2: ' . $e->getMessage()];
        }
    }

    /**
     * Dapatkan URL penyajian media yang dioptimalkan via Server Cache Proxy (/media/view).
     * Memangkas request Cloudflare R2 Class B ke 0 selama berkas tersimpan di cache lokal (< 14 hari).
     *
     * @param string|null $path Path relatif dari DB (misal: receipts/2026/09/abc.webp)
     * @param int $minutes Disimpan untuk kompatibilitas fungsi
     * @return string|null URL aman untuk ditampilkan di frontend
     */
    public static function presignedUrl(?string $path, int $minutes = 10): ?string
    {
        if (empty($path)) {
            return null;
        }

        $cleanPath = trim(str_replace('\\', '/', $path));
        if ($cleanPath === '') {
            return null;
        }

        // Jika sudah berupa URL lengkap eksternal HTTP(S)
        if (str_starts_with($cleanPath, 'http://') || str_starts_with($cleanPath, 'https://')) {
            return $cleanPath;
        }

        // Jika merupakan aset statis /assets/ atau /favicon/
        if (str_starts_with($cleanPath, '/assets/') || str_starts_with($cleanPath, 'assets/') || str_starts_with($cleanPath, '/favicon/')) {
            $norm = '/' . ltrim($cleanPath, '/');
            return class_exists('\App\Core\Router') ? \App\Core\Router::url($norm) : $norm;
        }

        $cleanPath = ltrim($cleanPath, '/');

        // Salurkan via rute media proxy server-side
        if (class_exists('\App\Core\Router')) {
            return \App\Core\Router::url('/media/view?path=' . urlencode($cleanPath));
        }

        return '/media/view?path=' . urlencode($cleanPath);
    }

    /**
     * Shortcut alias untuk presignedUrl.
     */
    public static function url(?string $path): ?string
    {
        return self::presignedUrl($path);
    }
}
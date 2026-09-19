<?php
declare(strict_types=1);

namespace App\Services;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use RuntimeException;
use InvalidArgumentException;

/**
 * app/Services/R2StorageService.php
 * Layanan Modular Cloudflare R2 (Private Bucket & Presigned URL)
 * 
 * Menggunakan AWS S3 SDK PHP untuk komunikasi privat dengan Cloudflare R2.
 * Menghasilkan path terstruktur kriptografis dan presigned URL bertenggat waktu.
 */
class R2StorageService
{
    private static ?self $instance = null;
    private ?S3Client $s3Client = null;
    private string $bucket;
    private string $endpoint;
    private string $accessKey;
    private string $secretKey;
    private string $region;
    private bool $configured = false;

    /**
     * Kategori folder terstruktur yang diizinkan untuk penyimpanan transaksi.
     */
    public const ALLOWED_CATEGORIES = [
        'receipts',        // Nota pembelian & bon vendor
        'deliveries',      // Bukti serah terima kirim / POD driver
        'delivery_issues', // Bukti kendala / retur pengiriman driver
        'consignments',    // Bukti kunjungan & retur toko konsinyasi
        'expenses',        // Nota bukti draf pengeluaran kas
    ];

    public function __construct()
    {
        $this->accessKey = trim((string) ($_ENV['R2_ACCESS_KEY_ID'] ?? getenv('R2_ACCESS_KEY_ID') ?: ''));
        $this->secretKey = trim((string) ($_ENV['R2_SECRET_ACCESS_KEY'] ?? getenv('R2_SECRET_ACCESS_KEY') ?: ''));
        $this->bucket    = trim((string) ($_ENV['R2_BUCKET_NAME'] ?? getenv('R2_BUCKET_NAME') ?: ''));
        $this->endpoint  = trim((string) ($_ENV['R2_ENDPOINT'] ?? getenv('R2_ENDPOINT') ?: ''));
        $this->region    = trim((string) ($_ENV['R2_REGION'] ?? getenv('R2_REGION') ?: 'auto'));

        if (!empty($this->accessKey) && !empty($this->secretKey) && !empty($this->bucket) && !empty($this->endpoint)) {
            $this->configured = true;
            $this->initClient();
        }
    }

    /**
     * Dapatkan instance tunggal (Singleton).
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Periksa apakah konfigurasi R2 di .env sudah lengkap.
     */
    public function isConfigured(): bool
    {
        return $this->configured && $this->s3Client !== null;
    }

    /**
     * Inisialisasi S3Client dengan kredensial R2.
     */
    private function initClient(): void
    {
        try {
            $this->s3Client = new S3Client([
                'version'                 => 'latest',
                'region'                  => $this->region ?: 'auto',
                'endpoint'                => $this->endpoint,
                'use_path_style_endpoint' => true,
                'credentials'             => [
                    'key'    => $this->accessKey,
                    'secret' => $this->secretKey,
                ],
                'http'                    => [
                    'timeout'         => 15.0,
                    'connect_timeout' => 5.0,
                ],
            ]);
        } catch (\Throwable $e) {
            error_log('[R2StorageService] Gagal inisialisasi S3Client: ' . $e->getMessage());
            $this->configured = false;
            $this->s3Client = null;
        }
    }

    /**
     * Generate path acak terstruktur kriptografis: {category}/{YYYY}/{MM}/{hash32}.{ext}
     *
     * @param string $category Kategori folder (receipts, deliveries, dll)
     * @param string $extension Ekstensi file (default: webp)
     * @return string Contoh: receipts/2026/09/a1b2c3d4e5f60718293a4b5c6d7e8f90.webp
     */
    public function generateStructuredPath(string $category, string $extension = 'webp'): string
    {
        $cat = strtolower(trim($category));
        if (!in_array($cat, self::ALLOWED_CATEGORIES, true)) {
            $cat = 'receipts';
        }

        $year = date('Y');
        $month = date('m');
        $hash = bin2hex(random_bytes(16)); // 32-karakter hexa acak kriptografis
        $ext = ltrim(strtolower(trim($extension)), '.');

        return "{$cat}/{$year}/{$month}/{$hash}.{$ext}";
    }

    /**
     * Unggah file fisik dari server lokal ke bucket Cloudflare R2 privat.
     *
     * @param string $sourceFilePath Path file lokal yang akan diunggah
     * @param string $category Kategori folder (receipts, deliveries, dll)
     * @param string $extension Ekstensi target (default: webp)
     * @param string|null $customKey Kunci objek kustom jika ingin menentukan path sendiri
     * @return string Relatif path / Object Key yang tersimpan di R2 (misal: receipts/2026/09/abc.webp)
     * @throws RuntimeException Jika gagal mengunggah
     */
    public function uploadFile(
        string $sourceFilePath,
        string $category = 'receipts',
        string $extension = 'webp',
        ?string $customKey = null
    ): string {
        if (!file_exists($sourceFilePath) || !is_readable($sourceFilePath)) {
            throw new InvalidArgumentException("Berkas sumber tidak ditemukan atau tidak dapat dibaca: {$sourceFilePath}");
        }

        if (!$this->isConfigured()) {
            throw new RuntimeException("Kredensial Cloudflare R2 belum dikonfigurasi di file .env");
        }

        $targetKey = $customKey ?? $this->generateStructuredPath($category, $extension);
        $mimeType = $this->detectMimeType($sourceFilePath, $extension);

        try {
            $this->s3Client->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $targetKey,
                'SourceFile'  => $sourceFilePath,
                'ContentType' => $mimeType,
                'ACL'         => 'private',
            ]);

            return $targetKey;
        } catch (AwsException $e) {
            error_log("[R2StorageService] AWS SDK PutObject Error: " . $e->getMessage());
            throw new RuntimeException("Gagal mengunggah berkas ke Cloudflare R2: " . $e->getAwsErrorMessage());
        } catch (\Throwable $e) {
            error_log("[R2StorageService] General Upload Error: " . $e->getMessage());
            throw new RuntimeException("Kesalahan saat mengunggah ke Cloudflare R2: " . $e->getMessage());
        }
    }

    /**
     * Unduh file objek dari Cloudflare R2 ke path file lokal server.
     * Digunakan oleh MediaCacheService untuk caching lokal server.
     *
     * @param string $objectKey Path relatif objek di R2 (misal: receipts/2026/09/abc.webp)
     * @param string $targetLocalPath Path absolut penyimpanan di harddisk lokal server
     * @return bool True jika berhasil diunduh dan tersimpan
     */
    public function downloadFile(string $objectKey, string $targetLocalPath): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $cleanKey = ltrim(trim(str_replace('\\', '/', $objectKey)), '/');
        if ($cleanKey === '') {
            return false;
        }

        $dir = dirname($targetLocalPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        try {
            $this->s3Client->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $cleanKey,
                'SaveAs' => $targetLocalPath,
            ]);

            return file_exists($targetLocalPath) && filesize($targetLocalPath) > 0;
        } catch (\Throwable $e) {
            error_log("[R2StorageService] Gagal download object '{$cleanKey}': " . $e->getMessage());
            if (file_exists($targetLocalPath)) {
                @unlink($targetLocalPath);
            }
            return false;
        }
    }

    /**
     * Buat Presigned URL bertenggat waktu (default: 10 menit / 600 detik) untuk melihat gambar privat.
     *
     * @param string|null $objectKey Path relatif objek di R2 (misal: receipts/2026/09/abc.webp)
     * @param int $expiresMinutes Tenggat waktu dalam menit (default 10 menit)
     * @return string|null Presigned URL bertanda tangan atau null jika kosong
     */
    public function getPresignedUrl(?string $objectKey, int $expiresMinutes = 10): ?string
    {
        if ($objectKey === null) {
            return null;
        }

        $cleanKey = trim(str_replace('\\', '/', $objectKey));
        if ($cleanKey === '') {
            return null;
        }

        // 1. Jika sudah berupa full URL HTTPS/HTTP, kembalikan langsung
        if (str_starts_with($cleanKey, 'http://') || str_starts_with($cleanKey, 'https://')) {
            return $cleanKey;
        }

        // 2. Jika merupakan file statis lokal /assets/ atau /uploads/
        if (str_starts_with($cleanKey, '/assets/') || str_starts_with($cleanKey, 'assets/')) {
            $norm = '/' . ltrim($cleanKey, '/');
            return class_exists('\App\Core\Router') ? \App\Core\Router::url($norm) : $norm;
        }

        // Hapus leading slash jika ada (misal "/receipts/..." -> "receipts/...")
        $cleanKey = ltrim($cleanKey, '/');

        if (!$this->isConfigured()) {
            // Fallback gracefully jika kredensial R2 belum diisi (misal masa setup)
            return class_exists('\App\Core\Router') ? \App\Core\Router::url('/' . $cleanKey) : ('/' . $cleanKey);
        }

        try {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key'    => $cleanKey,
            ]);

            $durationStr = "+{$expiresMinutes} minutes";
            $request = $this->s3Client->createPresignedRequest($cmd, $durationStr);

            return (string) $request->getUri();
        } catch (\Throwable $e) {
            error_log("[R2StorageService] Gagal generate Presigned URL untuk '{$cleanKey}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Hapus objek dari Cloudflare R2 bucket.
     *
     * @param string|null $objectKey Path relatif objek
     * @return bool
     */
    public function deleteFile(?string $objectKey): bool
    {
        if ($objectKey === null) {
            return true;
        }

        $cleanKey = ltrim(trim(str_replace('\\', '/', $objectKey)), '/');
        if ($cleanKey === '' || !$this->isConfigured()) {
            return false;
        }

        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $cleanKey,
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log("[R2StorageService] Gagal delete object '{$cleanKey}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deteksi MIME Type berdasarkan file atau ekstensi.
     */
    private function detectMimeType(string $filePath, string $extension): string
    {
        $ext = strtolower($extension);
        $map = [
            'webp' => 'image/webp',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'pdf'  => 'application/pdf',
        ];

        if (isset($map[$ext])) {
            return $map[$ext];
        }

        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($filePath);
            if ($mime) {
                return $mime;
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Shortcut statis untuk generate presigned URL.
     */
    public static function url(?string $objectKey, int $expiresMinutes = 10): ?string
    {
        return self::getInstance()->getPresignedUrl($objectKey, $expiresMinutes);
    }
}

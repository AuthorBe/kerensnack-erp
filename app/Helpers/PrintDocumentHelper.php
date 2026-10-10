<?php
declare(strict_types=1);

namespace App\Helpers;

use Throwable;

/**
 * app/Helpers/PrintDocumentHelper.php
 * Helper terpusat untuk standarisasi format cetak (Standard A4, Dot Matrix Full 9.5"x11", Dot Matrix Half 9.5"x5.5").
 * Menghubungkan controller dengan PdfExport secara konsisten dan aman.
 */
class PrintDocumentHelper
{
    public const FORMAT_STANDARD = 'standard';
    public const FORMAT_DOTMATRIX = 'dotmatrix';
    public const FORMAT_DOTMATRIX_HALF = 'dotmatrix_half';

    /**
     * Resolusi format yang valid dari input / query string
     */
    public static function resolveFormat(?string $format): string
    {
        $f = strtolower(trim((string)$format));
        return match ($f) {
            self::FORMAT_DOTMATRIX => self::FORMAT_DOTMATRIX,
            self::FORMAT_DOTMATRIX_HALF, 'half', 'wartel', 'continuous_half' => self::FORMAT_DOTMATRIX_HALF,
            default => self::FORMAT_STANDARD,
        };
    }

    /**
     * Ambil metadata profil format cetak
     *
     * @return array<string, array{label: string, sublabel: string, icon: string, desc: string}>
     */
    public static function getFormatOptions(): array
    {
        return [
            self::FORMAT_STANDARD => [
                'label' => 'Standar A4 / Laser',
                'sublabel' => 'A4 Portrait',
                'icon' => 'file-text',
                'desc' => 'Kertas A4 / Letter untuk printer Laser / Inkjet'
            ],
            self::FORMAT_DOTMATRIX => [
                'label' => 'Dot Matrix Full',
                'sublabel' => '9.5" x 11"',
                'icon' => 'printer',
                'desc' => 'Continuous Form 1 lembar utuh'
            ],
            self::FORMAT_DOTMATRIX_HALF => [
                'label' => 'Dot Matrix Half (Wartel)',
                'sublabel' => '9.5" x 5.5"',
                'icon' => 'scissors',
                'desc' => 'Continuous Form bagi dua (hemat kertas)'
            ],
        ];
    }

    /**
     * Format baris kontak resmi perusahaan (Telp/WA, Email, dan Website)
     * Menjamin keselarasan tampilan data perusahaan pada semua kop cetak (A4 & Dot Matrix)
     *
     * @param array<string, string> $comp Hasil dari CompanySetting::getAll()
     * @param string $separator Delimiter antar entri (misal: ' &bull; ' atau ' • ')
     * @return string
     */
    public static function formatContactLine(array $comp, string $separator = ' &bull; '): string
    {
        $parts = [];
        if (!empty($comp['telepon'])) {
            $parts[] = 'Telp/WA: ' . htmlspecialchars((string)$comp['telepon']);
        }
        if (!empty($comp['email'])) {
            $parts[] = 'Email: ' . htmlspecialchars((string)$comp['email']);
        }
        if (!empty($comp['website'])) {
            $parts[] = 'Web: ' . htmlspecialchars((string)$comp['website']);
        }
        return implode($separator, $parts);
    }

    /**
     * Ambil konfigurasi kertas & orientasi Dompdf
     *
     * @return array{paper: string|array<int, float|int>, orientation: string}
     */
    public static function getPaperConfig(string $format): array
    {
        $fmt = self::resolveFormat($format);
        return match ($fmt) {
            // Continuous form 9.5" x 11" (Letter portrait 612x792 pt atau 684x792 pt)
            self::FORMAT_DOTMATRIX => [
                'paper' => 'Letter',
                'orientation' => 'portrait'
            ],
            // Continuous form 9.5" x 5.5" (Wartel / Bagi Dua: 684 pt x 396 pt)
            self::FORMAT_DOTMATRIX_HALF => [
                'paper' => [0, 0, 684, 396],
                'orientation' => 'portrait'
            ],
            // Standar Laser / Inkjet A4
            default => [
                'paper' => 'A4',
                'orientation' => 'portrait'
            ],
        };
    }

    /**
     * CSS @page string untuk diinjeksikan secara dinamis
     */
    public static function getDynamicPageCss(string $format): string
    {
        $fmt = self::resolveFormat($format);
        return match ($fmt) {
            self::FORMAT_DOTMATRIX => '@page { size: letter portrait; margin: 6mm 10mm 6mm 10mm; }',
            self::FORMAT_DOTMATRIX_HALF => '@page { size: 9.5in 5.5in portrait; margin: 4mm 8mm 4mm 8mm; }',
            default => '@page { size: A4 portrait; margin: 12mm 15mm 12mm 15mm; }',
        };
    }

    /**
     * Download dokumen sebagai PDF dengan ukuran kertas yang disesuaikan
     */
    public static function downloadPdf(string $html, string $baseFilename, string $format = 'standard'): void
    {
        $config = self::getPaperConfig($format);
        $rawName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], ' ', $baseFilename);
        $baseName = preg_replace('/\.pdf$/i', '', $rawName);
        $cleanName = preg_replace('/[^A-Za-z0-9\(\)\.\-\s]+/', ' ', $baseName);
        $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));
        $fmt = self::resolveFormat($format);
        
        $suffix = match ($fmt) {
            self::FORMAT_DOTMATRIX => ' DotMatrix',
            self::FORMAT_DOTMATRIX_HALF => ' DotMatrixHalf',
            default => ''
        };

        $filename = trim("{$cleanName}{$suffix}") . '.pdf';
        PdfExport::download($html, $filename, $config['paper'], $config['orientation']);
    }

    /**
     * Stream dokumen langsung ke tab browser
     */
    public static function streamPdf(string $html, string $baseFilename, string $format = 'standard'): void
    {
        $config = self::getPaperConfig($format);
        $rawName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], ' ', $baseFilename);
        $baseName = preg_replace('/\.pdf$/i', '', $rawName);
        $cleanName = preg_replace('/[^A-Za-z0-9\(\)\.\-\s]+/', ' ', $baseName);
        $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));
        $fmt = self::resolveFormat($format);

        $suffix = match ($fmt) {
            self::FORMAT_DOTMATRIX => ' DotMatrix',
            self::FORMAT_DOTMATRIX_HALF => ' DotMatrixHalf',
            default => ''
        };

        $filename = trim("{$cleanName}{$suffix}") . '.pdf';
        PdfExport::stream($html, $filename, $config['paper'], $config['orientation']);
    }

    /**
     * Mengambil URL atau Data URI Base64 logo resmi perusahaan untuk disematkan pada dokumen cetak & PDF.
     * Menggunakan Base64 Data URI saat berkas lokal ditemukan agar kompatibel 100% dengan Dompdf & cetak browser.
     *
     * @param array<string, string>|null $comp Hasil dari CompanySetting::getAll()
     * @param bool $allowFallback Jika true, gunakan icon brand default saat logo kustom belum diunggah
     * @return string Data URI base64 atau string URL logo (kosong jika tidak ditemukan)
     */
    public static function getLogoSrc(?array $comp = null, bool $allowFallback = true): string
    {
        $comp = $comp ?? CompanySetting::getAll();
        $raw = trim((string)($comp['logo_url'] ?? ''));
        $appRoot = defined('APP_ROOT') ? APP_ROOT : (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2));

        if ($raw !== '') {
            if (str_starts_with($raw, 'data:image/svg+xml;base64,')) {
                $b64 = substr($raw, strlen('data:image/svg+xml;base64,'));
                $decoded = base64_decode($b64);
                if ($decoded !== false) {
                    return 'data:image/svg+xml;base64,' . base64_encode(self::normalizeSvgForDompdf($decoded));
                }
                return $raw;
            }
            if (str_starts_with($raw, 'data:')) {
                return $raw;
            }
            $cleanPath = ltrim(parse_url($raw, PHP_URL_PATH) ?: $raw, '/');
            $local = $appRoot . '/public/' . $cleanPath;
            if (is_file($local)) {
                $ext = strtolower(pathinfo($local, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'svg' => 'image/svg+xml',
                    'webp' => 'image/webp',
                    default => 'image/png',
                };
                $content = @file_get_contents($local);
                if ($content !== false && $content !== '') {
                    if ($ext === 'svg') {
                        $content = self::normalizeSvgForDompdf($content);
                    }
                    return 'data:' . $mime . ';base64,' . base64_encode($content);
                }
            }
            return $raw;
        }

        if ($allowFallback) {
            $fallback = $appRoot . '/public/assets/favicon/apple-touch-icon.png';
            if (is_file($fallback)) {
                $content = @file_get_contents($fallback);
                if ($content !== false && $content !== '') {
                    return 'data:image/png;base64,' . base64_encode($content);
                }
            }
        }

        return '';
    }

    /**
     * Ambil Data URI logo resmi aplikasi KEREN ONE (icon app bawaan, bukan logo perusahaan).
     * Digunakan khusus untuk dokumen internal payroll (Slip Gaji & Rekapitulasi Penggajian).
     *
     * @return string Data URI base64 logo aplikasi
     */
    public static function getAppLogoSrc(): string
    {
        $appRoot = defined('APP_ROOT') ? APP_ROOT : (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2));
        $appLogoPath = $appRoot . '/public/assets/favicon/apple-touch-icon.png';
        if (is_file($appLogoPath)) {
            $content = @file_get_contents($appLogoPath);
            if ($content !== false && $content !== '') {
                return 'data:image/png;base64,' . base64_encode($content);
            }
        }
        return '';
    }

    /**
     * Normalisasi atribut SVG agar kompatibel 100% dengan Dompdf.
     * Dompdf menghitung skala path SVG dari (target_width / svg_tag_width) tanpa memperhitungkan
     * rasio skala viewBox. Jika width/height berbeda dari viewBox (misal width 992 vs viewBox 491),
     * Dompdf mengecilkan logo hingga separuh ukuran dan menyisakan ruang kosong besar di dalam tabel.
     */
    public static function normalizeSvgForDompdf(string $content): string
    {
        if (preg_match('/viewBox=["\']\s*([0-9.-]+)\s+([0-9.-]+)\s+([0-9.-]+)\s+([0-9.-]+)\s*["\']/i', $content, $vb)) {
            $vbW = $vb[3];
            $vbH = $vb[4];
            if (preg_match('/<svg\b([^>]*)>/i', $content, $svgTag)) {
                $attrs = $svgTag[1];
                if (preg_match('/\bwidth=["\'][^"\']*["\']/i', $attrs)) {
                    $attrs = preg_replace('/\bwidth=["\'][^"\']*["\']/i', 'width="' . $vbW . '"', $attrs);
                } else {
                    $attrs .= ' width="' . $vbW . '"';
                }
                if (preg_match('/\bheight=["\'][^"\']*["\']/i', $attrs)) {
                    $attrs = preg_replace('/\bheight=["\'][^"\']*["\']/i', 'height="' . $vbH . '"', $attrs);
                } else {
                    $attrs .= ' height="' . $vbH . '"';
                }
                $content = str_replace($svgTag[0], '<svg' . $attrs . '>', $content);
            }
        }
        return $content;
    }
}


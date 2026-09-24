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
        $cleanName = preg_replace('/[\-_]+/', ' ', $baseFilename);
        $cleanName = preg_replace('/[^A-Za-z0-9 ]+/', ' ', $cleanName);
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
        $cleanName = preg_replace('/[\-_]+/', ' ', $baseFilename);
        $cleanName = preg_replace('/[^A-Za-z0-9 ]+/', ' ', $cleanName);
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
}

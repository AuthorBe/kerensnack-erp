<?php
declare(strict_types=1);

namespace App\Helpers;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * app/Helpers/PdfExport.php
 * Helper untuk pembuatan dan streaming/download dokumen PDF via Dompdf
 */
class PdfExport
{
    /**
     * Inisialisasi instance Dompdf dengan opsi optimal
     * isPhpEnabled diset false untuk keamanan dan kebal terhadap DISEVAL / disable_functions di VPS server
     */
    public static function createInstance(): Dompdf
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('isPhpEnabled', false); // Zero eval: kebal terhadap DISEVAL di production
        
        return new Dompdf($options);
    }

    /**
     * Terapkan nomor halaman dinamis langsung via Canvas Engine Dompdf (Tanpa eval() / script text/php)
     */
    private static function applyPageNumbersIfRequested(Dompdf $dompdf, string $html, bool $force = false): void
    {
        if ($force || str_contains($html, 'DOMPDF_PAGE_NUMBERS') || str_contains($html, 'DOMPDF DYNAMIC PAGE NUMBERING') || str_contains($html, 'text/php')) {
            $canvas = $dompdf->getCanvas();
            $font = $dompdf->getFontMetrics()->getFont('Helvetica', 'normal');
            $w = $canvas->get_width();
            $h = $canvas->get_height();
            $canvas->page_text($w - 85, $h - 18, "Halaman {PAGE_NUM} dari {PAGE_COUNT}", $font, 6.5, [0.5, 0.5, 0.5]);
        }
    }

    /**
     * Render HTML string menjadi PDF string binary
     * @param string|array<int, float|int> $paper Ukuran kertas standard (misal 'A4') atau koordinat array [0, 0, width, height]
     */
    public static function render(string $html, string|array $paper = 'A4', string $orientation = 'portrait', bool $addPageNumbers = false): string
    {
        $dompdf = self::createInstance();
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        self::applyPageNumbersIfRequested($dompdf, $html, $addPageNumbers);
        
        return $dompdf->output() ?: '';
    }

    /**
     * Stream PDF langsung ke browser (tampil di browser tab)
     * @param string|array<int, float|int> $paper Ukuran kertas standard (misal 'A4') atau koordinat array [0, 0, width, height]
     */
    public static function stream(string $html, string $filename = 'document.pdf', string|array $paper = 'A4', string $orientation = 'portrait', bool $addPageNumbers = false): void
    {
        $cleanFilename = self::sanitizeFilename($filename);
        $dompdf = self::createInstance();
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        self::applyPageNumbersIfRequested($dompdf, $html, $addPageNumbers);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $dompdf->stream($cleanFilename, ['Attachment' => false]);
        exit;
    }

    /**
     * Download PDF langsung sebagai file lampiran
     * @param string|array<int, float|int> $paper Ukuran kertas standard (misal 'A4') atau koordinat array [0, 0, width, height]
     */
    public static function download(string $html, string $filename = 'document.pdf', string|array $paper = 'A4', string $orientation = 'portrait', bool $addPageNumbers = false): void
    {
        $cleanFilename = self::sanitizeFilename($filename);
        $dompdf = self::createInstance();
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        self::applyPageNumbersIfRequested($dompdf, $html, $addPageNumbers);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $dompdf->stream($cleanFilename, ['Attachment' => true]);
        exit;
    }

    /**
     * Bersihkan nama berkas dari karakter ilegal OS dan standarisasi nama berkas PDF
     */
    private static function sanitizeFilename(string $filename): string
    {
        $rawName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], ' ', $filename);
        $baseName = preg_replace('/\.pdf$/i', '', $rawName);
        $cleanName = preg_replace('/[^A-Za-z0-9\(\)\.\-\s]+/', ' ', $baseName);
        $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));
        return ($cleanName !== '' ? $cleanName : 'Document') . '.pdf';
    }
}
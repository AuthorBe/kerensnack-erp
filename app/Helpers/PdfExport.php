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
     */
    public static function createInstance(): Dompdf
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('isPhpEnabled', true);
        
        return new Dompdf($options);
    }

    /**
     * Render HTML string menjadi PDF string binary
     */
    public static function render(string $html, string $paper = 'A4', string $orientation = 'portrait'): string
    {
        $dompdf = self::createInstance();
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        
        return $dompdf->output() ?: '';
    }

    /**
     * Stream PDF langsung ke browser (tampil di browser tab)
     */
    public static function stream(string $html, string $filename = 'document.pdf', string $paper = 'A4', string $orientation = 'portrait'): void
    {
        $dompdf = self::createInstance();
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $dompdf->stream($filename, ['Attachment' => false]);
        exit;
    }

    /**
     * Download PDF langsung sebagai file lampiran
     */
    public static function download(string $html, string $filename = 'document.pdf', string $paper = 'A4', string $orientation = 'portrait'): void
    {
        $dompdf = self::createInstance();
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }
}
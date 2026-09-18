<?php
declare(strict_types=1);

namespace App\Services\Import;

use App\Services\Import\Handlers\EntityImportHandlerInterface;
use PDO;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class TemplateGenerator
{
    /**
     * Membangun objek Spreadsheet lengkap dengan styling, header, catatan, dan baris data
     */
    public static function buildSpreadsheet(EntityImportHandlerInterface $handler, string $mode, PDO $pdo): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $title = $handler->getEntityLabel();
        $sheet->setTitle(substr($title, 0, 31));
        $spreadsheet->getDefaultStyle()->getFont()->setName('Segoe UI')->setSize(10);

        $headers = $handler->getTemplateHeaders();
        $widths  = $handler->getTemplateWidths();
        $examples= $handler->getTemplateExamples();
        $notes   = $handler->getTemplateNotes();

        $themeAccentBg = '1E293B'; // Slate 800 - Header utama
        $themeSubBg    = 'F1F5F9'; // Slate 100 - Sub header panduan
        $themeExBg     = 'F8FAFC'; // Slate 50 - Baris contoh

        $lastColLtr = Coordinate::stringFromColumnIndex(count($headers));

        // Baris 1: Judul Panduan
        $sheet->mergeCells("A1:{$lastColLtr}1");
        $sheet->setCellValue('A1', 'PANDUAN TEMPLATE IMPOR DATA: ' . strtoupper($title));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Baris 2: Sub-judul
        $sheet->mergeCells("A2:{$lastColLtr}2");
        $subText = ($mode === 'current_data') 
            ? "Data diekspor langsung dari live database sistem. Anda dapat mengedit nilai kolom lalu mengunggah kembali untuk sinkronisasi massal."
            : "Isi data mulai dari baris ke-5. Hapus baris contoh sebelum menyimpan berkas jika tidak diperlukan.";
        $sheet->setCellValue('A2', $subText);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '475569']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $themeSubBg]],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(18);

        // Baris 3: Catatan / Petunjuk Teknis Kolom
        $sheet->mergeCells("A3:{$lastColLtr}3");
        $notesText = "Petunjuk: " . implode(" | ", $notes);
        $sheet->setCellValue('A3', $notesText);
        $sheet->getStyle('A3')->applyFromArray([
            'font'      => ['size' => 8.5, 'color' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);

        // Baris 4: Header Kolom
        $headerRow = 4;
        $colMeta = [];
        foreach ($headers as $colIdx => $headerText) {
            $colLtr = Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue($colLtr . $headerRow, $headerText);
            $sheet->getColumnDimension($colLtr)->setWidth($widths[$colIdx] ?? 20);

            // Klasifikasi Tipe Kolom Berdasarkan Nama Header
            $hLower = strtolower(trim($headerText));

            // 1. Kolom WAJIB String/Text murni (mencegah hilangnya awalan nol pada nomor WA, HP, NIK, No Rekening, Kode SKU, serta menjaga kolom kategori/tipe seperti Tipe Penggajian)
            $isForceString = false;
            foreach (['kode', 'sku', 'nik', 'telepon', 'whatsapp', 'wa', 'hp', 'rekening', 'barcode', 'rute', 'npwp', 'ktp', 'pos', 'tipe', 'jenis', 'skema', 'metode'] as $kw) {
                if (str_contains($hLower, $kw)) {
                    $isForceString = true;
                    break;
                }
            }

            // 2. Kolom Angka Mata Uang (Rupiah)
            $isCurrency = false;
            if (!$isForceString) {
                foreach (['harga', 'plafon', 'hpp', 'upah', 'tarif', 'nominal', 'biaya', 'gaji', 'level '] as $kw) {
                    if (str_contains($hLower, $kw)) {
                        $isCurrency = true;
                        break;
                    }
                }
            }

            // 3. Kolom Kuantitas / Stok / Bilangan Bulat
            $isQuantity = false;
            if (!$isForceString && !$isCurrency) {
                foreach (['stok', 'qty', 'jumlah', 'isi per', 'termin'] as $kw) {
                    if (str_contains($hLower, $kw)) {
                        $isQuantity = true;
                        break;
                    }
                }
            }

            // 4. Kolom Persentase
            $isPercent = false;
            if (!$isForceString && !$isCurrency && !$isQuantity) {
                if (str_contains($hLower, 'persen') || str_contains($hLower, '%')) {
                    $isPercent = true;
                }
            }

            $colMeta[$colIdx] = [
                'forceString' => $isForceString,
                'currency'    => $isCurrency,
                'quantity'    => $isQuantity,
                'percent'     => $isPercent,
            ];
        }

        $sheet->getStyle("A{$headerRow}:{$lastColLtr}{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $themeAccentBg]],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '334155']]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(26);

        // Baris 5+: Data Rows (Contoh atau Data Live)
        $dataRows = ($mode === 'current_data')
            ? $handler->getCurrentDataRows($pdo)
            : $handler->getTemplateExamples();

        $startDataRow = $headerRow + 1;
        $currentRow = $startDataRow;

        foreach ($dataRows as $rIdx => $row) {
            foreach ($row as $colIdx => $val) {
                $colLtr = Coordinate::stringFromColumnIndex($colIdx + 1);
                $meta = $colMeta[$colIdx] ?? ['forceString' => true, 'currency' => false, 'quantity' => false, 'percent' => false];
                $cellCoord = $colLtr . $currentRow;

                if ($meta['forceString']) {
                    // String murni (Pertahankan awalan nol seperti '08123...', 'CUST-001')
                    $sheet->setCellValueExplicit(
                        $cellCoord,
                        (string)($val ?? ''),
                        DataType::TYPE_STRING
                    );
                    $sheet->getStyle($cellCoord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                } elseif ($meta['currency']) {
                    // Angka Mata Uang Rupiah Bersih (contoh: 10.000.000 tanpa .00 dan tanpa segitiga hijau peringatan)
                    $cleanVal = SmartReader::normalizeNumeric($val, 0.0);
                    $sheet->setCellValueExplicit(
                        $cellCoord,
                        $cleanVal,
                        DataType::TYPE_NUMERIC
                    );
                    $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle($cellCoord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } elseif ($meta['quantity']) {
                    // Angka Bilangan Bulat / Kuantitas
                    $cleanVal = SmartReader::normalizeNumeric($val, 0.0);
                    $sheet->setCellValueExplicit(
                        $cellCoord,
                        $cleanVal,
                        DataType::TYPE_NUMERIC
                    );
                    $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle($cellCoord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } elseif ($meta['percent']) {
                    // Persentase
                    $cleanVal = SmartReader::normalizeNumeric($val, 0.0);
                    $sheet->setCellValueExplicit(
                        $cellCoord,
                        $cleanVal,
                        DataType::TYPE_NUMERIC
                    );
                    $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode('0.00');
                    $sheet->getStyle($cellCoord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } else {
                    // Teks umum / label
                    $strVal = (string)($val ?? '');
                    $sheet->setCellValueExplicit(
                        $cellCoord,
                        $strVal,
                        DataType::TYPE_STRING
                    );
                    
                    // Kolom status / model / enum pendek di-center agar rapi
                    $lowerStr = strtolower(trim($strVal));
                    if (in_array($lowerStr, ['aktif', 'nonaktif', 'reguler', 'konsinyasi', 'cash', 'qris', 'transfer', 'borongan', 'bulanan', 'harian'], true)) {
                        $sheet->getStyle($cellCoord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    } else {
                        $sheet->getStyle($cellCoord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    }
                }
            }

            // Styling baris contoh jika mode empty
            if ($mode === 'empty') {
                $sheet->getStyle("A{$currentRow}:{$lastColLtr}{$currentRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $themeExBg]],
                    'font' => ['italic' => true, 'color' => ['rgb' => '334155']]
                ]);
            }

            $sheet->getStyle("A{$currentRow}:{$lastColLtr}{$currentRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'CBD5E1']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(20);
            $currentRow++;
        }

        // Frozen panes agar header tetap terlihat saat scroll
        $sheet->freezePane('A5');

        return $spreadsheet;
    }

    /**
     * Helper pembuatan spreadsheet berdasarkan tipe data string
     */
    public static function generate(string $type, bool $isLiveData, PDO $pdo): Spreadsheet
    {
        $handler = ImportProcessor::getHandler($type);
        if (!$handler) {
            throw new \RuntimeException("Handler tipe '{$type}' tidak ditemukan.");
        }
        return self::buildSpreadsheet($handler, $isLiveData ? 'current_data' : 'empty', $pdo);
    }

    /**
     * Menghasilkan file Excel (.xlsx) dan langsung mengirimkannya ke browser untuk diunduh.
     */
    public static function generateAndDownload(EntityImportHandlerInterface $handler, string $mode, PDO $pdo): void
    {
        $spreadsheet = self::buildSpreadsheet($handler, $mode, $pdo);
        $title = $handler->getEntityLabel();

        // Output ke HTTP Response
        $filename = ($mode === 'current_data')
            ? "Data_Terkini_" . str_replace(' ', '_', $title) . "_" . date('Ymd_His') . ".xlsx"
            : "Template_Impor_" . str_replace(' ', '_', $title) . ".xlsx";

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

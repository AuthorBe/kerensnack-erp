<?php
declare(strict_types=1);

namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * app/Helpers/ExcelExport.php
 * Helper untuk pembuatan dan download file Excel (.xlsx) via PhpSpreadsheet
 */
class ExcelExport
{
    /**
     * Membuat dan mengunduh file Excel secara instan dari header dan rows data array
     * 
     * @param string $filename Nama file output (misal: laporan-penjualan.xlsx)
     * @param array<int, string> $headers Array judul kolom, misal ['No', 'Nomor Nota', 'Pelanggan', 'Total']
     * @param array<int, array<int, mixed>> $rows Array data per baris
     * @param string $sheetTitle Nama sheet tab
     */
    public static function download(
        string $filename,
        array $headers,
        array $rows,
        string $sheetTitle = 'Laporan'
    ): void {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31)); // Batas nama sheet Excel 31 char

        // 1. Tulis Header
        $colIndex = 1;
        foreach ($headers as $headerText) {
            $colLetter = self::columnLetter($colIndex);
            $sheet->setCellValue("{$colLetter}1", $headerText);
            $colIndex++;
        }

        $lastCol = self::columnLetter(max(1, count($headers)));

        // Style Header: Background Biru Elegan, Font Putih Tebal, Center
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A8A'] // Deep Navy Blue
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1']
                ]
            ]
        ];
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // 2. Tulis Data Rows
        $rowIndex = 2;
        foreach ($rows as $row) {
            $colIndex = 1;
            foreach ($row as $cellValue) {
                $colLetter = self::columnLetter($colIndex);
                $sheet->setCellValue("{$colLetter}{$rowIndex}", $cellValue);
                $colIndex++;
            }
            $rowIndex++;
        }

        $lastRow = max(1, $rowIndex - 1);

        // Border untuk seluruh tabel
        if ($lastRow > 1) {
            $tableStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ];
            $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray($tableStyle);
        }

        // 3. Auto-fit column widths
        for ($i = 1; $i <= count($headers); $i++) {
            $colLetter = self::columnLetter($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // 4. Output ke Browser untuk Download
        $rawName = str_replace(['/', '\\'], ' ', $filename);
        $baseName = preg_replace('/\.xlsx$/i', '', $rawName);
        $cleanName = preg_replace('/[\-_]+/', ' ', $baseName);
        $cleanName = preg_replace('/[^A-Za-z0-9 ]+/', ' ', $cleanName);
        $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));
        $filename = ($cleanName !== '' ? $cleanName : 'Data Export') . '.xlsx';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . addcslashes($filename, '"\\') . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export Dokumen Bukti Penyesuaian Stok (Berita Acara Bulk Opname) dengan format eksekutif
     */
    public static function downloadOpnameDocument(array $opname, array $items, array $company = []): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bukti Opname');
        $sheet->setShowGridLines(true);

        // 1. KOP RESMI PERUSAHAAN
        $sheet->setCellValue('A1', $company['nama'] ?? 'KEREN SNACK INDONESIA');
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true)->getColor()->setRGB('1E3A8A');

        $sheet->setCellValue('A2', 'BUKTI PENYESUAIAN STOK (BERITA ACARA BULK OPNAME GUDANG)');
        $sheet->getStyle('A2')->getFont()->setSize(10.5)->setBold(true)->getColor()->setRGB('0F172A');

        $tagline = ($company['tagline'] ?? 'Produsen & Distributor Aneka Makanan Ringan Berkualitas') . ' | ' .
                   ($company['alamat'] ?? 'Jl. Industri Snack No. 88, Jawa Barat') . ' | Telp/WA: ' .
                   ($company['telepon'] ?? '0812-3456-7890');
        $sheet->setCellValue('A3', $tagline);
        $sheet->getStyle('A3')->getFont()->setSize(8.5)->setItalic(true)->getColor()->setRGB('64748B');

        // 2. METADATA INFO PANEL (Rows 5 to 7)
        $metaBgStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8FAFC']
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1']
                ]
            ]
        ];
        $sheet->getStyle('A5:N7')->applyFromArray($metaBgStyle);

        // Row 5
        $sheet->setCellValue('A5', 'No. Dokumen:');
        $sheet->setCellValue('B5', $opname['nomor_dokumen']);
        $sheet->setCellValue('E5', 'Petugas Pelaksana:');
        $sheet->setCellValue('F5', $opname['nama_pembuat'] ?? 'Petugas Gudang');
        $sheet->setCellValue('J5', 'Item Disesuaikan:');
        $totalKatalogStr = !empty($opname['total_item_katalog']) ? " (dari {$opname['total_item_katalog']} di katalog)" : '';
        $sheet->setCellValue('K5', count($items) . " Produk" . $totalKatalogStr);

        // Row 6
        $sheet->setCellValue('A6', 'Tanggal Opname:');
        $sheet->setCellValue('B6', date('d F Y', strtotime($opname['tanggal'] ?? date('Y-m-d'))));
        $sheet->setCellValue('E6', 'Catatan Sesi:');
        $sheet->setCellValue('F6', !empty($opname['catatan']) ? $opname['catatan'] : '—');
        $sheet->setCellValue('J6', 'Mutasi Masuk / Keluar:');
        $sheet->setCellValue('K6', '+' . (float)($opname['total_qty_masuk'] ?? 0) . ' / -' . (float)($opname['total_qty_keluar'] ?? 0) . ' pcs');

        // Row 7
        $sheet->setCellValue('A7', 'Waktu Cetak:');
        $sheet->setCellValue('B7', date('d/m/Y H:i') . ' WIB');
        $sheet->setCellValue('E7', 'Status Dokumen:');
        $sheet->setCellValue('F7', 'SELESAI / TERPOSTING');
        $sheet->setCellValue('J7', 'Net Valuasi Selisih:');
        $netVal = (float)($opname['total_nilai_selisih_rp'] ?? 0);
        $sheet->setCellValue('K7', $netVal);

        // Style metadata labels and values
        $labelCells = ['A5', 'A6', 'A7', 'E5', 'E6', 'E7', 'J5', 'J6', 'J7'];
        foreach ($labelCells as $c) {
            $sheet->getStyle($c)->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('475569');
        }
        $sheet->getStyle('B5')->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('1E293B');
        $sheet->getStyle('F5')->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('1E293B');
        $sheet->getStyle('F7')->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('059669');
        $sheet->getStyle('K7')->getNumberFormat()->setFormatCode('"Rp" #,##0;"Rp" -#,##0;"Rp" 0');
        $sheet->getStyle('K7')->getFont()->setBold(true)->setSize(10)->getColor()->setRGB($netVal >= 0 ? '059669' : 'DC2626');

        // 3. TABLE HEADERS (Row 9)
        $headers = [
            'A9' => 'No',
            'B9' => 'Kode SKU',
            'C9' => 'Barcode',
            'D9' => 'Nama Produk',
            'E9' => 'Varian Rasa',
            'F9' => 'Grup Kemasan',
            'G9' => 'Satuan',
            'H9' => 'Stok Sistem',
            'I9' => 'Stok Fisik Realita',
            'J9' => 'Selisih Fisik',
            'K9' => 'Tipe Mutasi',
            'L9' => 'HPP Satuan (Rp)',
            'M9' => 'Subtotal Selisih (Rp)',
            'N9' => 'Catatan Item'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 9.5
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'] // Deep Navy Slate
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '475569']
                ]
            ]
        ];
        $sheet->getStyle('A9:N9')->applyFromArray($headerStyle);
        $sheet->getRowDimension(9)->setRowHeight(28);

        // 4. DATA ROWS (Row 10+)
        $rowIndex = 10;
        $no = 1;

        foreach ($items as $it) {
            $selisih = (float)$it['selisih'];
            $hpp = (float)($it['hpp_efektif'] ?? $it['harga_pokok_saat_opname'] ?? 0.0);
            $subtotalRp = (float)($it['subtotal_rp'] ?? $it['subtotal_nilai_selisih'] ?? ($selisih * $hpp));
            $tipe = $selisih > 0 ? 'MASUK' : ($selisih < 0 ? 'KELUAR' : 'TETAP');

            $sheet->setCellValue("A{$rowIndex}", $no++);
            $sheet->setCellValueExplicit("B{$rowIndex}", $it['kode_sku'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$rowIndex}", !empty($it['barcode']) ? $it['barcode'] : '-', DataType::TYPE_STRING);
            $sheet->setCellValue("D{$rowIndex}", $it['nama_item']);
            $sheet->setCellValue("E{$rowIndex}", !empty($it['varian_rasa']) ? $it['varian_rasa'] : '-');
            $sheet->setCellValue("F{$rowIndex}", !empty($it['nama_grup']) ? $it['nama_grup'] : (!empty($it['kode_grup']) ? $it['kode_grup'] : '-'));
            $sheet->setCellValue("G{$rowIndex}", $it['satuan_dasar'] ?? 'pcs');
            $sheet->setCellValue("H{$rowIndex}", (float)$it['stok_sistem']);
            $sheet->setCellValue("I{$rowIndex}", (float)$it['stok_fisik']);
            $sheet->setCellValue("J{$rowIndex}", $selisih);
            $sheet->setCellValue("K{$rowIndex}", $tipe);
            $sheet->setCellValue("L{$rowIndex}", $hpp);
            $sheet->setCellValue("M{$rowIndex}", $subtotalRp);
            $sheet->setCellValue("N{$rowIndex}", !empty($it['catatan_item']) ? $it['catatan_item'] : '-');

            // Alignments
            $sheet->getStyle("A{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("K{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rowIndex}:J{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("L{$rowIndex}:M{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Number formats
            $sheet->getStyle("H{$rowIndex}:I{$rowIndex}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("J{$rowIndex}")->getNumberFormat()->setFormatCode('+ #,##0;- #,##0;0');
            $sheet->getStyle("L{$rowIndex}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $sheet->getStyle("M{$rowIndex}")->getNumberFormat()->setFormatCode('"Rp" +#,##0;"Rp" -#,##0;"Rp" 0');

            // Fonts & Colors
            $sheet->getStyle("B{$rowIndex}")->getFont()->setBold(true);
            $sheet->getStyle("D{$rowIndex}")->getFont()->setBold(true);
            $sheet->getStyle("I{$rowIndex}")->getFont()->setBold(true);
            $sheet->getStyle("J{$rowIndex}")->getFont()->setBold(true)->getColor()->setRGB($selisih > 0 ? '047857' : ($selisih < 0 ? 'DC2626' : '64748B'));
            $sheet->getStyle("M{$rowIndex}")->getFont()->setBold(true)->getColor()->setRGB($subtotalRp > 0 ? '047857' : ($subtotalRp < 0 ? 'DC2626' : '64748B'));

            // Status pill coloring
            if ($tipe === 'MASUK') {
                $sheet->getStyle("K{$rowIndex}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5');
                $sheet->getStyle("K{$rowIndex}")->getFont()->setBold(true)->getColor()->setRGB('065F46');
            } elseif ($tipe === 'KELUAR') {
                $sheet->getStyle("K{$rowIndex}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF2F2');
                $sheet->getStyle("K{$rowIndex}")->getFont()->setBold(true)->getColor()->setRGB('991B1B');
            } else {
                $sheet->getStyle("K{$rowIndex}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
                $sheet->getStyle("K{$rowIndex}")->getFont()->getColor()->setRGB('475569');
            }

            // Zebra row background
            if ($rowIndex % 2 === 1) {
                $sheet->getStyle("A{$rowIndex}:J{$rowIndex}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                $sheet->getStyle("L{$rowIndex}:N{$rowIndex}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            // Cell borders
            $sheet->getStyle("A{$rowIndex}:N{$rowIndex}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
            $sheet->getRowDimension($rowIndex)->setRowHeight(21);

            $rowIndex++;
        }

        $lastDataRow = max(10, $rowIndex - 1);
        $totalRow = $rowIndex;

        // 5. SUMMARY TOTAL ROW
        $sheet->mergeCells("A{$totalRow}:G{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'TOTAL PENYESUAIAN FISIK:');
        $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A{$totalRow}")->getFont()->setBold(true)->setSize(9.5);

        $sheet->setCellValue("H{$totalRow}", "=SUM(H10:H{$lastDataRow})");
        $sheet->setCellValue("I{$totalRow}", "=SUM(I10:I{$lastDataRow})");
        $sheet->setCellValue("J{$totalRow}", "=SUM(J10:J{$lastDataRow})");
        $sheet->setCellValue("K{$totalRow}", "");
        $sheet->setCellValue("L{$totalRow}", 'NET IMPACT FINANSIAL:');
        $sheet->setCellValue("M{$totalRow}", "=SUM(M10:M{$lastDataRow})");
        $sheet->setCellValue("N{$totalRow}", "");

        // Format total row
        $sheet->getStyle("H{$totalRow}:I{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("J{$totalRow}")->getNumberFormat()->setFormatCode('+ #,##0;- #,##0;0');
        $sheet->getStyle("M{$totalRow}")->getNumberFormat()->setFormatCode('"Rp" +#,##0;"Rp" -#,##0;"Rp" 0');

        $sheet->getStyle("H{$totalRow}:J{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("L{$totalRow}:M{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("L{$totalRow}")->getFont()->setBold(true)->setSize(8.5)->getColor()->setRGB('475569');

        $sheet->getStyle("A{$totalRow}:N{$totalRow}")->getFont()->setBold(true);
        $sheet->getStyle("M{$totalRow}")->getFont()->setSize(10.5)->getColor()->setRGB($netVal < 0 ? 'DC2626' : ($netVal > 0 ? '047857' : '0F172A'));

        $sheet->getStyle("A{$totalRow}:N{$totalRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $sheet->getStyle("A{$totalRow}:N{$totalRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('334155');
        $sheet->getStyle("A{$totalRow}:N{$totalRow}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setRGB('334155');
        $sheet->getRowDimension($totalRow)->setRowHeight(25);

        // 6. SIGNATURE SECTION
        $sigRow = $totalRow + 3;
        $sheet->setCellValue("B{$sigRow}", "Pelaksana Hitung Fisik,");
        $sheet->setCellValue("F{$sigRow}", "Diverifikasi Oleh,");
        $sheet->setCellValue("K{$sigRow}", "Disetujui & Diaudit Oleh,");

        $sheet->setCellValue("B" . ($sigRow + 3), "( " . ($opname['nama_pembuat'] ?? 'Petugas Gudang') . " )");
        $sheet->setCellValue("F" . ($sigRow + 3), "( ........................................ )");
        $sheet->setCellValue("K" . ($sigRow + 3), "( ........................................ )");

        $sheet->setCellValue("B" . ($sigRow + 4), "Staf Logistik / Gudang");
        $sheet->setCellValue("F" . ($sigRow + 4), "Kepala Gudang / Supervisor");
        $sheet->setCellValue("K" . ($sigRow + 4), "Admin / Owner / Keuangan");

        $sheet->getStyle("B{$sigRow}:M" . ($sigRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$sigRow}")->getFont()->setBold(true);
        $sheet->getStyle("F{$sigRow}")->getFont()->setBold(true);
        $sheet->getStyle("K{$sigRow}")->getFont()->setBold(true);
        $sheet->getStyle("B" . ($sigRow + 3) . ":K" . ($sigRow + 3))->getFont()->setBold(true);
        $sheet->getStyle("B" . ($sigRow + 4) . ":K" . ($sigRow + 4))->getFont()->setSize(8.5)->getColor()->setRGB('64748B');

        // 7. FREEZE PANES
        $sheet->freezePane('A10');

        // 8. AUTO FIT COLUMNS
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // 9. PRINT SETUP
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        // 10. DOWNLOAD OUTPUT
        $rawDoc = (string)($opname['nomor_dokumen'] ?? '');
        $cleanDoc = preg_replace('/[\-_]+/', ' ', $rawDoc);
        $cleanDoc = preg_replace('/[^A-Za-z0-9 ]+/', ' ', $cleanDoc);
        $cleanDoc = trim(preg_replace('/\s+/', ' ', $cleanDoc));
        $filename = ($cleanDoc !== '' ? "Opname {$cleanDoc}" : 'Opname') . '.xlsx';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . addcslashes($filename, '"\\') . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Konversi index kolom integer (1-indexed: 1 = A, 2 = B, 27 = AA, dst.)
     */
    private static function columnLetter(int $colIndex): string
    {
        $letter = '';
        while ($colIndex > 0) {
            $p = ($colIndex - 1) % 26;
            $letter = chr(65 + $p) . $letter;
            $colIndex = (int)(($colIndex - $p) / 26);
        }
        return $letter ?: 'A';
    }
}
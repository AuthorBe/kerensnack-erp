<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * app/Helpers/ExcelExport.php
 * Helper untuk pembuatan dan download file Excel (.xlsx) resmi standar korporat via PhpSpreadsheet
 */
class ExcelExport
{
    /**
     * Membuat dan mengunduh file Excel terstandarisasi dengan KOP resmi, metadata, formatting akuntansi, dan formula native
     * 
     * @param string $filename Nama file output (misal: Laporan Arus Kas.xlsx)
     * @param array<int, string> $headers Array judul kolom, misal ['No', 'Nomor Nota', 'Pelanggan', 'Total']
     * @param array<int, array<int, mixed>> $rows Array data per baris
     * @param string $sheetTitle Nama sheet tab
     * @param array<string, mixed> $options Konfigurasi tambahan:
     *        - 'with_kop' => bool (default: true)
     *        - 'report_title' => string
     *        - 'metadata' => array<string, string> (misal: ['Periode' => '...', 'Filter' => '...'])
     *        - 'currency_cols' => array<int|string> (daftar nama kolom atau index 1-based yang diformat Rupiah)
     *        - 'sum_cols' => array<int|string> (daftar kolom yang dihitung baris total =SUM)
     *        - 'company' => array (data company setting)
     */
    public static function download(
        string $filename,
        array $headers,
        array $rows,
        string $sheetTitle = 'Laporan',
        array $options = []
    ): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        self::populateSheet($sheet, $headers, $rows, $sheetTitle, $options);

        self::outputToBrowser($spreadsheet, $filename);
    }

    /**
     * Mengisi satu worksheet dengan styling standar korporat resmi
     */
    public static function populateSheet(
        Worksheet $sheet,
        array $headers,
        array $rows,
        string $sheetTitle = 'Laporan',
        array $options = []
    ): void {
        $sheet->setTitle(substr($sheetTitle, 0, 31));
        $sheet->setShowGridLines(true);

        $withKop = $options['with_kop'] ?? true;
        $company = $options['company'] ?? CompanySetting::getAll();
        $reportTitle = $options['report_title'] ?? $sheetTitle;
        $metadata = $options['metadata'] ?? [];
        $currencyCols = $options['currency_cols'] ?? [];
        $sumCols = $options['sum_cols'] ?? [];

        // Petakan currencyCols dan sumCols dari nama header ke index kolom 1-based
        $currencyIndices = [];
        $sumIndices = [];
        foreach ($headers as $hIdx => $hName) {
            $colNum = $hIdx + 1;
            if (in_array($colNum, $currencyCols, true) || in_array($hName, $currencyCols, true)) {
                $currencyIndices[] = $colNum;
            }
            if (in_array($colNum, $sumCols, true) || in_array($hName, $sumCols, true)) {
                $sumIndices[] = $colNum;
            }
        }

        $lastColNum = max(1, count($headers));
        $lastColLetter = self::columnLetter($lastColNum);

        $headerRow = 1;
        $dataStartRow = 2;

        if ($withKop) {
            // 1. KOP PERUSAHAAN (Merge across all columns so long title/tagline doesn't bloat column A)
            if ($lastColNum > 1) {
                $sheet->mergeCells("A1:{$lastColLetter}1");
                $sheet->mergeCells("A2:{$lastColLetter}2");
                $sheet->mergeCells("A3:{$lastColLetter}3");
            }
            $sheet->setCellValue('A1', $company['nama'] ?? 'KEREN SNACK INDONESIA');
            $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true)->getColor()->setRGB('1E3A8A');

            $sheet->setCellValue('A2', strtoupper((string)$reportTitle));
            $sheet->getStyle('A2')->getFont()->setSize(10.5)->setBold(true)->getColor()->setRGB('0F172A');

            $tagline = ($company['tagline'] ?? 'Produsen & Distributor Aneka Makanan Ringan Berkualitas') . ' | ' .
                       ($company['alamat'] ?? 'Jl. Industri Snack No. 88, Jawa Barat') . ' | ' .
                       PrintDocumentHelper::formatContactLine($company, ' | ');
            $sheet->setCellValue('A3', $tagline);
            $sheet->getStyle('A3')->getFont()->setSize(8.5)->setItalic(true)->getColor()->setRGB('64748B');

            // 2. METADATA INFO PANEL (Row 5 & 6)
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

            $sheet->getStyle("A5:{$lastColLetter}6")->applyFromArray($metaBgStyle);

            $metaEntries = array_merge([
                'Waktu Unduh' => date('d/m/Y H:i') . ' WIB',
                'Dicetak Oleh' => class_exists('App\Core\Auth') && Auth::check() ? Auth::name() . ' (' . ucfirst(Auth::role()) . ')' : 'Sistem ERP',
            ], $metadata);

            $metaKeys = array_keys($metaEntries);
            $metaVals = array_values($metaEntries);

            if ($lastColNum >= 6) {
                $midColNum = max(4, (int)floor($lastColNum / 2) + 1);
                $midColLet = self::columnLetter($midColNum);
                $midValStartLet = self::columnLetter($midColNum + 1);

                // Sisi Kiri
                if (isset($metaKeys[0])) {
                    $sheet->setCellValue('A5', $metaKeys[0] . ':');
                    $leftEndLet = self::columnLetter($midColNum - 1);
                    if ($midColNum > 2) {
                        $sheet->mergeCells("B5:{$leftEndLet}5");
                    }
                    $sheet->setCellValue('B5', $metaVals[0]);
                }
                if (isset($metaKeys[1])) {
                    $sheet->setCellValue('A6', $metaKeys[1] . ':');
                    $leftEndLet = self::columnLetter($midColNum - 1);
                    if ($midColNum > 2) {
                        $sheet->mergeCells("B6:{$leftEndLet}6");
                    }
                    $sheet->setCellValue('B6', $metaVals[1]);
                }

                // Sisi Kanan
                if (isset($metaKeys[2])) {
                    $sheet->setCellValue("{$midColLet}5", $metaKeys[2] . ':');
                    if ($lastColNum > $midColNum + 1) {
                        $sheet->mergeCells("{$midValStartLet}5:{$lastColLetter}5");
                    }
                    $sheet->setCellValue("{$midValStartLet}5", $metaVals[2]);
                }
                if (isset($metaKeys[3])) {
                    $sheet->setCellValue("{$midColLet}6", $metaKeys[3] . ':');
                    if ($lastColNum > $midColNum + 1) {
                        $sheet->mergeCells("{$midValStartLet}6:{$lastColLetter}6");
                    }
                    $sheet->setCellValue("{$midValStartLet}6", $metaVals[3]);
                }
            } else {
                // Sederhana jika kolom sedikit (< 6)
                if (isset($metaKeys[0])) {
                    $sheet->setCellValue('A5', $metaKeys[0] . ':');
                    $sheet->setCellValue('B5', $metaVals[0]);
                }
                if (isset($metaKeys[1])) {
                    $sheet->setCellValue('A6', $metaKeys[1] . ':');
                    $sheet->setCellValue('B6', $metaVals[1]);
                }
                if (isset($metaKeys[2])) {
                    $midColNum = max(3, (int)floor($lastColNum / 2) + 1);
                    $midColLet = self::columnLetter($midColNum);
                    $midValLet = self::columnLetter($midColNum + 1);
                    $sheet->setCellValue("{$midColLet}5", $metaKeys[2] . ':');
                    $sheet->setCellValue("{$midValLet}5", $metaVals[2]);
                }
                if (isset($metaKeys[3])) {
                    $midColNum = max(3, (int)floor($lastColNum / 2) + 1);
                    $midColLet = self::columnLetter($midColNum);
                    $midValLet = self::columnLetter($midColNum + 1);
                    $sheet->setCellValue("{$midColLet}6", $metaKeys[3] . ':');
                    $sheet->setCellValue("{$midValLet}6", $metaVals[3]);
                }
            }

            $sheet->getStyle("A5:{$lastColLetter}6")->getFont()->setSize(9);
            $sheet->getStyle('A5')->getFont()->setBold(true)->getColor()->setRGB('475569');
            $sheet->getStyle('A6')->getFont()->setBold(true)->getColor()->setRGB('475569');
            $sheet->getStyle('B5')->getFont()->setBold(true)->getColor()->setRGB('0F172A');
            $sheet->getStyle('B6')->getFont()->getColor()->setRGB('0F172A');

            $headerRow = 8;
            $dataStartRow = 9;
        }

        // 3. TABLE HEADERS
        $colIndex = 1;
        foreach ($headers as $headerText) {
            $colLetter = self::columnLetter($colIndex);
            $sheet->setCellValue("{$colLetter}{$headerRow}", $headerText);
            $colIndex++;
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
        $sheet->getStyle("A{$headerRow}:{$lastColLetter}{$headerRow}")->applyFromArray($headerStyle);
        $sheet->getRowDimension($headerRow)->setRowHeight(26);

        // 4. DATA ROWS (High-Performance Bulk Rendering)
        $rowCount = count($rows);
        if ($rowCount > 0) {
            $sheet->fromArray($rows, null, "A{$dataStartRow}");

            // Ensure long numeric strings (barcode/nik >= 10 chars) maintain pure string format
            $currRow = $dataStartRow;
            foreach ($rows as $row) {
                $colIndex = 1;
                foreach ($row as $cellValue) {
                    if (is_string($cellValue) && preg_match('/^[0-9]+$/', $cellValue) && strlen($cellValue) >= 10) {
                        $colLetter = self::columnLetter($colIndex);
                        $sheet->setCellValueExplicit("{$colLetter}{$currRow}", $cellValue, DataType::TYPE_STRING);
                    }
                    $colIndex++;
                }
                $currRow++;
            }

            $lastDataRow = $dataStartRow + $rowCount - 1;
            $rowIndex = $lastDataRow + 1;

            // Bulk Cell borders & vertical alignment in ONE CALL
            $sheet->getStyle("A{$dataStartRow}:{$lastColLetter}{$lastDataRow}")->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0']
                    ]
                ]
            ]);

            // Bulk column formatting (Number format & Horizontal alignment per column)
            for ($c = 1; $c <= $lastColNum; $c++) {
                $colLet = self::columnLetter($c);
                $hName = strtolower(trim((string)($headers[$c - 1] ?? '')));

                if (in_array($c, $currencyIndices, true)) {
                    $sheet->getStyle("{$colLet}{$dataStartRow}:{$colLet}{$lastDataRow}")->getNumberFormat()->setFormatCode('"Rp" #,##0;"Rp" -#,##0;"Rp" 0');
                    $sheet->getStyle("{$colLet}{$dataStartRow}:{$colLet}{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } elseif (in_array($hName, ['no', 'nomor', '#', 'urut', 'kode', 'kode sku', 'sku', 'barcode', 'kode toko', 'kode item', 'kode produk', 'tanggal', 'tgl', 'waktu', 'jatuh tempo', 'status', 'tipe', 'satuan', 'sat', 'kategori', 'peran', 'sumber aksi', 'jenis aksi', 'tabel terdampak'], true)) {
                    $sheet->getStyle("{$colLet}{$dataStartRow}:{$colLet}{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                } elseif (in_array($c, $sumIndices, true)) {
                    $sheet->getStyle("{$colLet}{$dataStartRow}:{$colLet}{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("{$colLet}{$dataStartRow}:{$colLet}{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            }

            // Zebra row striping in fast batch
            for ($r = $dataStartRow + 1; $r <= $lastDataRow; $r += 2) {
                $sheet->getStyle("A{$r}:{$lastColLetter}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }
        } else {
            $lastDataRow = $dataStartRow;
            $rowIndex = $dataStartRow;
        }

        // 5. SUMMARY TOTAL ROW (Jika ada sum_cols atau data > 0)
        if (!empty($sumIndices) && $lastDataRow >= $dataStartRow) {
            $totalRow = $rowIndex;
            $sheet->setCellValue("A{$totalRow}", 'TOTAL:');
            $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A{$totalRow}")->getFont()->setBold(true)->setSize(9.5);

            for ($c = 1; $c <= $lastColNum; $c++) {
                $colLet = self::columnLetter($c);
                if (in_array($c, $sumIndices, true)) {
                    $sheet->setCellValue("{$colLet}{$totalRow}", "=SUM({$colLet}{$dataStartRow}:{$colLet}{$lastDataRow})");
                    if (in_array($c, $currencyIndices, true)) {
                        $sheet->getStyle("{$colLet}{$totalRow}")->getNumberFormat()->setFormatCode('"Rp" #,##0;"Rp" -#,##0;"Rp" 0');
                    } else {
                        $sheet->getStyle("{$colLet}{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
                    }
                    $sheet->getStyle("{$colLet}{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            }

            $sheet->getStyle("A{$totalRow}:{$lastColLetter}{$totalRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$totalRow}:{$lastColLetter}{$totalRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
            $sheet->getStyle("A{$totalRow}:{$lastColLetter}{$totalRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('334155');
            $sheet->getStyle("A{$totalRow}:{$lastColLetter}{$totalRow}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setRGB('334155');
            $sheet->getRowDimension($totalRow)->setRowHeight(24);
        }

        // 6. FREEZE PANES
        if ($withKop) {
            $sheet->freezePane("A{$dataStartRow}");
        } else {
            $sheet->freezePane('A2');
        }

        // 7. AUTO-FIT & PROPORTIONAL COLUMN SIZING
        for ($i = 1; $i <= $lastColNum; $i++) {
            $colLetter = self::columnLetter($i);
            $hName = strtolower(trim((string)($headers[$i - 1] ?? '')));

            // 1. Kolom Index / Nomor Urut -> Selalu ramping dan presisi (6-8)
            if ($i === 1 && in_array($hName, ['no', 'nomor', '#', 'no.', 'urut'], true)) {
                $sheet->getColumnDimension($colLetter)->setAutoSize(false)->setWidth(7);
            }
            // 2. Kolom Tanggal / Waktu / Jam / Periode
            elseif (in_array($hName, ['tanggal', 'tgl', 'waktu', 'jatuh tempo', 'tgl faktur', 'tgl bayar', 'tgl kirim', 'tgl opname', 'tgl settle', 'periode kunjungan'], true)) {
                $sheet->getColumnDimension($colLetter)->setAutoSize(false)->setWidth(16);
            }
            elseif (in_array($hName, ['jam', 'waktu kejadian'], true)) {
                $sheet->getColumnDimension($colLetter)->setAutoSize(false)->setWidth(12);
            }
            // 3. Kolom Kode / SKU / Barcode / Nomor Dokumen
            elseif (in_array($hName, ['kode', 'kode sku', 'sku', 'barcode', 'kode toko', 'kode item', 'kode produk', 'kode pelanggan', 'kode vendor', 'kode sales', 'no. faktur', 'nomor faktur', 'nomor nota', 'nomor bukti', 'no. surat jalan', 'nomor surat jalan', 'nomor invoice', 'no. kunjungan', 'no. dokumen', 'nomor berita acara', 'nomor po / pembelian', 'nik', 'no. rekening'], true)) {
                $sheet->getColumnDimension($colLetter)->setAutoSize(false)->setWidth(18);
            }
            // 4. Kolom Satuan / Status / Tipe / Badge / Kanal
            elseif (in_array($hName, ['satuan', 'sat', 'status', 'status bayar', 'status pengiriman', 'status tagihan', 'status piutang', 'status dokumen', 'status approval', 'status penerimaan', 'tipe', 'tipe bayar', 'metode bayar', 'kanal', 'shift', 'kategori aging', 'jenis aksi', 'role'], true)) {
                $sheet->getColumnDimension($colLetter)->setAutoSize(false)->setWidth(15);
            }
            // 5. Kolom Finansial / Nominal Currency
            elseif (in_array($i, $currencyIndices, true)) {
                $sheet->getColumnDimension($colLetter)->setAutoSize(false)->setWidth(19);
            }
            // 6. Kolom Kuantitas / Qty (yang masuk sumIndices tapi bukan currency)
            elseif (in_array($i, $sumIndices, true)) {
                $sheet->getColumnDimension($colLetter)->setAutoSize(false)->setWidth(14);
            }
            // 7. Kolom Deskriptif / Panjang (Nama, Catatan, Keterangan, Alamat)
            elseif (in_array($hName, ['nama', 'nama item', 'nama produk', 'nama toko', 'nama toko pelanggan', 'nama toko mitra', 'nama toko konsinyasi', 'nama supplier / vendor', 'nama tenaga penjual / sales', 'pelanggan', 'supplier', 'keterangan', 'catatan', 'catatan / keterangan', 'catatan lapangan', 'deskripsi', 'deskripsi aktivitas', 'deskripsi / keterangan', 'alamat', 'alamat tujuan'], true)) {
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }
            // 8. Default fallback: AutoSize
            else {
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }
        }

        // 8. PRINT SETUP
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
    }

    /**
     * Membuat dan mengunduh file Excel Multi-Sheet dalam 1 Workbook
     * 
     * @param string $filename Nama file output
     * @param array<int, array{title: string, headers: array, rows: array, options?: array}> $sheetsConfig
     */
    public static function downloadMultiSheet(string $filename, array $sheetsConfig): void
    {
        $spreadsheet = new Spreadsheet();
        
        $sheetIndex = 0;
        foreach ($sheetsConfig as $cfg) {
            if ($sheetIndex === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            self::populateSheet(
                $sheet,
                $cfg['headers'] ?? [],
                $cfg['rows'] ?? [],
                $cfg['title'] ?? ('Sheet ' . ($sheetIndex + 1)),
                $cfg['options'] ?? []
            );

            $sheetIndex++;
        }

        $spreadsheet->setActiveSheetIndex(0);
        self::outputToBrowser($spreadsheet, $filename);
    }

    /**
     * Export Master Laporan Rekap Penjualan Multi-Kanal (POS, B2B Reguler, Konsinyasi Rak & Grand Total)
     * Format 4 Sheet Terstruktur:
     * - Sheet 1: Ringkasan Eksekutif & Komparasi 3 Kanal + Rasio Finansial
     * - Sheet 2: Detail Transaksi POS
     * - Sheet 3: Detail Pesanan Toko Reguler B2B
     * - Sheet 4: Detail Toko Konsinyasi & Kerugian Rusak
     */
    public static function downloadConsolidatedSalesReport(
        string $filename,
        array $summaryData,
        array $posRows,
        array $b2bRows,
        array $consRows,
        array $periodMeta = []
    ): void {
        $spreadsheet = new Spreadsheet();
        $company = CompanySetting::getAll();

        // =========================================================================
        // SHEET 1: RINGKASAN EKSEKUTIF KONSOLIDASI
        // =========================================================================
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Ringkasan Eksekutif');
        $sheet1->setShowGridLines(true);

        // KOP
        $sheet1->mergeCells('A1:H1');
        $sheet1->setCellValue('A1', $company['nama'] ?? 'KEREN SNACK INDONESIA');
        $sheet1->getStyle('A1')->getFont()->setSize(14)->setBold(true)->getColor()->setRGB('1E3A8A');

        $sheet1->mergeCells('A2:H2');
        $sheet1->setCellValue('A2', 'LAPORAN REKAPITULASI PENJUALAN MULTI-KANAL & MARGIN KONSOLIDASI');
        $sheet1->getStyle('A2')->getFont()->setSize(10.5)->setBold(true)->getColor()->setRGB('0F172A');

        $tagline = ($company['tagline'] ?? 'Produsen & Distributor Aneka Makanan Ringan Berkualitas') . ' | ' .
                   ($company['alamat'] ?? 'Jl. Industri Snack No. 88, Jawa Barat') . ' | ' .
                   PrintDocumentHelper::formatContactLine($company, ' | ');
        $sheet1->mergeCells('A3:H3');
        $sheet1->setCellValue('A3', $tagline);
        $sheet1->getStyle('A3')->getFont()->setSize(8.5)->setItalic(true)->getColor()->setRGB('64748B');

        // Metadata Box
        $sheet1->getStyle('A5:H6')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]]
        ]);
        $sheet1->setCellValue('A5', 'Periode Analisis:');
        $sheet1->mergeCells('B5:D5');
        $sheet1->setCellValue('B5', $periodMeta['periode_label'] ?? (date('d M Y') . ' sd ' . date('d M Y')));

        $sheet1->setCellValue('A6', 'Waktu Cetak:');
        $sheet1->mergeCells('B6:D6');
        $sheet1->setCellValue('B6', date('d/m/Y H:i') . ' WIB');

        $sheet1->setCellValue('E5', 'Dicetak Oleh:');
        $sheet1->mergeCells('F5:H5');
        $sheet1->setCellValue('F5', class_exists('App\Core\Auth') && Auth::check() ? Auth::name() . ' (' . ucfirst(Auth::role()) . ')' : 'Direksi');

        $sheet1->setCellValue('E6', 'Status Laporan:');
        $sheet1->mergeCells('F6:H6');
        $sheet1->setCellValue('F6', 'RESMI / AUDITED');

        $sheet1->getStyle('A5:H6')->getFont()->setSize(9);
        $sheet1->getStyle('A5')->getFont()->setBold(true)->getColor()->setRGB('475569');
        $sheet1->getStyle('A6')->getFont()->setBold(true)->getColor()->setRGB('475569');
        $sheet1->getStyle('E5')->getFont()->setBold(true)->getColor()->setRGB('475569');
        $sheet1->getStyle('E6')->getFont()->setBold(true)->getColor()->setRGB('475569');
        $sheet1->getStyle('B5')->getFont()->setBold(true)->getColor()->setRGB('1E293B');
        $sheet1->getStyle('F6')->getFont()->setBold(true)->getColor()->setRGB('059669');

        // TABEL 1: MATRIKS KOMPARASI 3 KANAL
        $headers1 = [
            'A8' => 'No',
            'B8' => 'Kanal Distribusi Penjualan',
            'C8' => 'Total Omzet (Rp)',
            'D8' => 'Sudah Terbayar (Rp)',
            'E8' => 'Sisa Piutang (Rp)',
            'F8' => 'Total HPP / Beban Pokok (Rp)',
            'G8' => 'Beban Rugi Retur (Rp)',
            'H8' => 'Keuntungan Murni (Gross Profit Rp)'
        ];
        foreach ($headers1 as $cell => $text) {
            $sheet1->setCellValue($cell, $text);
        }
        $sheet1->getStyle('A8:H8')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9.5],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '475569']]]
        ]);
        $sheet1->getRowDimension(8)->setRowHeight(26);

        // Data Rows Komparasi (Row 9, 10, 11)
        $cData = [
            ['1', '1. Kasir POS (Penjualan Langsung Walk-in)', (float)($summaryData['pos_omzet'] ?? 0), (float)($summaryData['pos_terbayar'] ?? 0), (float)($summaryData['pos_piutang'] ?? 0), (float)($summaryData['pos_hpp'] ?? 0), 0.0, (float)($summaryData['pos_laba'] ?? 0)],
            ['2', '2. Toko Reguler B2B (Faktur Pesanan Grosir)', (float)($summaryData['b2b_omzet'] ?? 0), (float)($summaryData['b2b_terbayar'] ?? 0), (float)($summaryData['b2b_piutang'] ?? 0), (float)($summaryData['b2b_hpp'] ?? 0), 0.0, (float)($summaryData['b2b_laba'] ?? 0)],
            ['3', '3. Toko Konsinyasi (Titip Jual Rak Mitra)', (float)($summaryData['cons_omzet'] ?? 0), (float)($summaryData['cons_terbayar'] ?? 0), (float)($summaryData['cons_piutang'] ?? 0), (float)($summaryData['cons_hpp'] ?? 0), (float)($summaryData['cons_loss'] ?? 0), (float)($summaryData['cons_laba'] ?? 0)],
        ];

        $rIdx = 9;
        foreach ($cData as $cd) {
            $sheet1->setCellValue("A{$rIdx}", $cd[0]);
            $sheet1->setCellValue("B{$rIdx}", $cd[1]);
            $sheet1->setCellValue("C{$rIdx}", $cd[2]);
            $sheet1->setCellValue("D{$rIdx}", $cd[3]);
            $sheet1->setCellValue("E{$rIdx}", $cd[4]);
            $sheet1->setCellValue("F{$rIdx}", $cd[5]);
            $sheet1->setCellValue("G{$rIdx}", $cd[6]);
            $sheet1->setCellValue("H{$rIdx}", $cd[7]);

            $sheet1->getStyle("A{$rIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("B{$rIdx}")->getFont()->setBold(true);
            $sheet1->getStyle("C{$rIdx}:H{$rIdx}")->getNumberFormat()->setFormatCode('"Rp" #,##0;"Rp" -#,##0;"Rp" 0');
            $sheet1->getStyle("C{$rIdx}:H{$rIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet1->getStyle("H{$rIdx}")->getFont()->setBold(true)->getColor()->setRGB('059669');
            $sheet1->getStyle("E{$rIdx}")->getFont()->getColor()->setRGB($cd[4] > 0 ? 'DC2626' : '64748B');

            $sheet1->getStyle("A{$rIdx}:H{$rIdx}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
            $sheet1->getRowDimension($rIdx)->setRowHeight(22);
            $rIdx++;
        }

        // GRAND TOTAL ROW (Row 12)
        $sheet1->setCellValue('A12', 'GRAND TOTAL:');
        $sheet1->mergeCells('A12:B12');
        $sheet1->getStyle('A12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet1->setCellValue('C12', '=SUM(C9:C11)');
        $sheet1->setCellValue('D12', '=SUM(D9:D11)');
        $sheet1->setCellValue('E12', '=SUM(E9:E11)');
        $sheet1->setCellValue('F12', '=SUM(F9:F11)');
        $sheet1->setCellValue('G12', '=SUM(G9:G11)');
        $sheet1->setCellValue('H12', '=SUM(H9:H11)');

        $sheet1->getStyle('A12:H12')->getFont()->setBold(true)->setSize(10);
        $sheet1->getStyle('C12:H12')->getNumberFormat()->setFormatCode('"Rp" #,##0;"Rp" -#,##0;"Rp" 0');
        $sheet1->getStyle('C12:H12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet1->getStyle('H12')->getFont()->setSize(10.5)->getColor()->setRGB('059669');
        $sheet1->getStyle('A12:H12')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $sheet1->getStyle('A12:H12')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('1E293B');
        $sheet1->getStyle('A12:H12')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setRGB('1E293B');
        $sheet1->getRowDimension(12)->setRowHeight(25);

        // TABEL 2: MATRIKS KESEHATAN FINANSIAL (Row 15 - 19)
        $sheet1->mergeCells('A15:H15');
        $sheet1->setCellValue('A15', 'ANALISIS RASIO & KESEHATAN FINANSIAL');
        $sheet1->getStyle('A15')->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('1E3A8A');

        $totOmzet = max(1.0, (float)($summaryData['total_omzet'] ?? 1.0));
        $totTerbayar = (float)($summaryData['total_terbayar'] ?? 0);
        $totLaba = (float)($summaryData['total_laba'] ?? 0);
        $totLoss = (float)($summaryData['cons_loss'] ?? 0);

        $kolektibilitasPct = round(($totTerbayar / $totOmzet) * 100, 2);
        $marginLabaPct = round(($totLaba / $totOmzet) * 100, 2);
        $lossPct = round(($totLoss / $totOmzet) * 100, 2);

        $sheet1->mergeCells('A16:B16');
        $sheet1->setCellValue('A16', 'Tingkat Kolektibilitas Kas Terbayar:');
        $sheet1->setCellValue('C16', "{$kolektibilitasPct}%");
        $sheet1->mergeCells('D16:H16');
        $sheet1->setCellValue('D16', $kolektibilitasPct >= 70 ? 'SEHAT (Kas Masuk Lancar)' : 'PERHATIAN (Piutang Menumpuk)');

        $sheet1->mergeCells('A17:B17');
        $sheet1->setCellValue('A17', 'Margin Keuntungan Kotor Penjualan:');
        $sheet1->setCellValue('C17', "{$marginLabaPct}%");
        $sheet1->mergeCells('D17:H17');
        $sheet1->setCellValue('D17', 'Rasio Laba Bersih terhadap Omzet');

        $sheet1->mergeCells('A18:B18');
        $sheet1->setCellValue('A18', 'Rasio Kerugian Retur Rusak Konsinyasi:');
        $sheet1->setCellValue('C18', "{$lossPct}%");
        $sheet1->mergeCells('D18:H18');
        $sheet1->setCellValue('D18', $lossPct <= 2.0 ? 'AMAN (Di bawah toleransi 2%)' : 'TINGGI (Perlu evaluasi display rak)');

        $sheet1->getStyle('A16:A18')->getFont()->setBold(true)->getColor()->setRGB('475569');
        $sheet1->getStyle('C16:C18')->getFont()->setBold(true)->getColor()->setRGB('0F172A');
        $sheet1->getStyle('D16:D18')->getFont()->setItalic(true)->setSize(8.5)->getColor()->setRGB('64748B');

        // SIGNATURE SECTION
        $sigRow = 21;
        $sheet1->mergeCells("A{$sigRow}:B{$sigRow}");
        $sheet1->setCellValue("A{$sigRow}", "Disiapkan Oleh,");
        $sheet1->mergeCells("D{$sigRow}:E{$sigRow}");
        $sheet1->setCellValue("D{$sigRow}", "Diperiksa Oleh,");
        $sheet1->mergeCells("G{$sigRow}:H{$sigRow}");
        $sheet1->setCellValue("G{$sigRow}", "Disetujui & Diaudit Oleh,");

        $sheet1->mergeCells("A" . ($sigRow + 3) . ":B" . ($sigRow + 3));
        $sheet1->setCellValue("A" . ($sigRow + 3), "( ........................................ )");
        $sheet1->mergeCells("D" . ($sigRow + 3) . ":E" . ($sigRow + 3));
        $sheet1->setCellValue("D" . ($sigRow + 3), "( ........................................ )");
        $sheet1->mergeCells("G" . ($sigRow + 3) . ":H" . ($sigRow + 3));
        $sheet1->setCellValue("G" . ($sigRow + 3), "( ........................................ )");

        $sheet1->mergeCells("A" . ($sigRow + 4) . ":B" . ($sigRow + 4));
        $sheet1->setCellValue("A" . ($sigRow + 4), "Admin Penjualan / Sales");
        $sheet1->mergeCells("D" . ($sigRow + 4) . ":E" . ($sigRow + 4));
        $sheet1->setCellValue("D" . ($sigRow + 4), "Finance & Accounting");
        $sheet1->mergeCells("G" . ($sigRow + 4) . ":H" . ($sigRow + 4));
        $sheet1->setCellValue("G" . ($sigRow + 4), "Direktur / Owner");

        $sheet1->getStyle("A{$sigRow}:H" . ($sigRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("A{$sigRow}")->getFont()->setBold(true);
        $sheet1->getStyle("D{$sigRow}")->getFont()->setBold(true);
        $sheet1->getStyle("G{$sigRow}")->getFont()->setBold(true);
        $sheet1->getStyle("A" . ($sigRow + 3) . ":H" . ($sigRow + 3))->getFont()->setBold(true);
        $sheet1->getStyle("A" . ($sigRow + 4) . ":H" . ($sigRow + 4))->getFont()->setSize(8.5)->getColor()->setRGB('64748B');

        // Column Dimensions Sheet 1 (Proportional & Balanced)
        $sheet1->getColumnDimension('A')->setAutoSize(false)->setWidth(7);
        $sheet1->getColumnDimension('B')->setAutoSize(false)->setWidth(44);
        $sheet1->getColumnDimension('C')->setAutoSize(false)->setWidth(20);
        $sheet1->getColumnDimension('D')->setAutoSize(false)->setWidth(20);
        $sheet1->getColumnDimension('E')->setAutoSize(false)->setWidth(20);
        $sheet1->getColumnDimension('F')->setAutoSize(false)->setWidth(22);
        $sheet1->getColumnDimension('G')->setAutoSize(false)->setWidth(20);
        $sheet1->getColumnDimension('H')->setAutoSize(false)->setWidth(24);

        // =========================================================================
        // SHEET 2: DETAIL TRANSAKSI POS
        // =========================================================================
        $sheet2 = $spreadsheet->createSheet();
        $posHeaders = ['No', 'Nomor Nota', 'Tanggal', 'Jam', 'Kasir / Petugas', 'Akun Kas', 'Tipe Bayar', 'Total Netto (Rp)', 'Estimasi HPP (Rp)', 'Keuntungan (Rp)'];
        self::populateSheet($sheet2, $posHeaders, $posRows, 'Detail POS', [
            'report_title' => 'RINCIAN TRANSAKSI PENJUALAN KASIR POS',
            'metadata' => ['Kanal' => 'Kasir POS Walk-in', 'Periode' => $periodMeta['periode_label'] ?? '-'],
            'currency_cols' => ['Total Netto (Rp)', 'Estimasi HPP (Rp)', 'Keuntungan (Rp)'],
            'sum_cols' => ['Total Netto (Rp)', 'Estimasi HPP (Rp)', 'Keuntungan (Rp)']
        ]);

        // =========================================================================
        // SHEET 3: DETAIL TOKO REGULER B2B
        // =========================================================================
        $sheet3 = $spreadsheet->createSheet();
        $b2bHeaders = ['No', 'Nomor Faktur', 'Tanggal', 'Kode Toko', 'Nama Toko Pelanggan', 'Sales PIC', 'Tipe Bayar', 'Jatuh Tempo', 'Subtotal (Rp)', 'Diskon (Rp)', 'Total Netto (Rp)', 'Total Bayar (Rp)', 'Sisa Piutang (Rp)', 'Estimasi HPP (Rp)', 'Laba Kotor (Rp)', 'Status Bayar'];
        self::populateSheet($sheet3, $b2bHeaders, $b2bRows, 'Detail Toko Reguler', [
            'report_title' => 'RINCIAN PENJUALAN PESANAN TOKO REGULER B2B',
            'metadata' => ['Kanal' => 'Penjualan Toko Reguler B2B', 'Periode' => $periodMeta['periode_label'] ?? '-'],
            'currency_cols' => ['Subtotal (Rp)', 'Diskon (Rp)', 'Total Netto (Rp)', 'Total Bayar (Rp)', 'Sisa Piutang (Rp)', 'Estimasi HPP (Rp)', 'Laba Kotor (Rp)'],
            'sum_cols' => ['Subtotal (Rp)', 'Diskon (Rp)', 'Total Netto (Rp)', 'Total Bayar (Rp)', 'Sisa Piutang (Rp)', 'Estimasi HPP (Rp)', 'Laba Kotor (Rp)']
        ]);

        // =========================================================================
        // SHEET 4: DETAIL TOKO KONSINYASI
        // =========================================================================
        $sheet4 = $spreadsheet->createSheet();
        $consHeaders = ['No', 'No. Kunjungan', 'Tanggal', 'Toko Mitra', 'Kode SKU', 'Nama Produk', 'Terjual (Pcs)', 'Harga Deal (Rp)', 'Total Penjualan (Rp)', 'Retur Rusak (Pcs)', 'Nilai Rugi Rusak (Rp)', 'HPP Produk Laku (Rp)', 'Laba Murni (Rp)', 'Sales Pembina'];
        self::populateSheet($sheet4, $consHeaders, $consRows, 'Detail Konsinyasi', [
            'report_title' => 'RINCIAN PENJUALAN & KERUGIAN RETUR KONSINYASI RAK',
            'metadata' => ['Kanal' => 'Toko Konsinyasi (Titip Jual)', 'Periode' => $periodMeta['periode_label'] ?? '-'],
            'currency_cols' => ['Harga Deal (Rp)', 'Total Penjualan (Rp)', 'Nilai Rugi Rusak (Rp)', 'HPP Produk Laku (Rp)', 'Laba Murni (Rp)'],
            'sum_cols' => ['Terjual (Pcs)', 'Total Penjualan (Rp)', 'Retur Rusak (Pcs)', 'Nilai Rugi Rusak (Rp)', 'HPP Produk Laku (Rp)', 'Laba Murni (Rp)']
        ]);

        $spreadsheet->setActiveSheetIndex(0);
        self::outputToBrowser($spreadsheet, $filename);
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

        if (empty($company)) {
            $company = CompanySetting::getAll();
        }

        // 1. KOP RESMI PERUSAHAAN
        $sheet->mergeCells('A1:N1');
        $sheet->setCellValue('A1', $company['nama'] ?? 'KEREN SNACK INDONESIA');
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true)->getColor()->setRGB('1E3A8A');

        $sheet->mergeCells('A2:N2');
        $sheet->setCellValue('A2', 'BUKTI PENYESUAIAN STOK (BERITA ACARA BULK OPNAME GUDANG)');
        $sheet->getStyle('A2')->getFont()->setSize(10.5)->setBold(true)->getColor()->setRGB('0F172A');

        $tagline = ($company['tagline'] ?? 'Produsen & Distributor Aneka Makanan Ringan Berkualitas') . ' | ' .
                   ($company['alamat'] ?? 'Jl. Industri Snack No. 88, Jawa Barat') . ' | ' .
                   PrintDocumentHelper::formatContactLine($company, ' | ');
        $sheet->mergeCells('A3:N3');
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
        $sheet->mergeCells('B5:D5');
        $sheet->setCellValue('B5', $opname['nomor_dokumen']);
        $sheet->setCellValue('E5', 'Petugas Pelaksana:');
        $sheet->mergeCells('F5:I5');
        $sheet->setCellValue('F5', $opname['nama_pembuat'] ?? 'Petugas Gudang');
        $sheet->setCellValue('J5', 'Item Disesuaikan:');
        $totalKatalogStr = !empty($opname['total_item_katalog']) ? " (dari {$opname['total_item_katalog']} di katalog)" : '';
        $sheet->mergeCells('K5:N5');
        $sheet->setCellValue('K5', count($items) . " Produk" . $totalKatalogStr);

        // Row 6
        $sheet->setCellValue('A6', 'Tanggal Opname:');
        $sheet->mergeCells('B6:D6');
        $sheet->setCellValue('B6', date('d F Y', strtotime($opname['tanggal'] ?? date('Y-m-d'))));
        $sheet->setCellValue('E6', 'Catatan Sesi:');
        $sheet->mergeCells('F6:I6');
        $sheet->setCellValue('F6', !empty($opname['catatan']) ? $opname['catatan'] : '—');
        $sheet->setCellValue('J6', 'Mutasi Masuk / Keluar:');
        $sheet->mergeCells('K6:N6');
        $sheet->setCellValue('K6', '+' . (float)($opname['total_qty_masuk'] ?? 0) . ' / -' . (float)($opname['total_qty_keluar'] ?? 0) . ' pcs');

        // Row 7
        $sheet->setCellValue('A7', 'Waktu Cetak:');
        $sheet->mergeCells('B7:D7');
        $sheet->setCellValue('B7', date('d/m/Y H:i') . ' WIB');
        $sheet->setCellValue('E7', 'Status Dokumen:');
        $sheet->mergeCells('F7:I7');
        $sheet->setCellValue('F7', 'SELESAI / TERPOSTING');
        $sheet->setCellValue('J7', 'Net Valuasi Selisih:');
        $netVal = (float)($opname['total_nilai_selisih_rp'] ?? 0);
        $sheet->mergeCells('K7:N7');
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
        $sheet->mergeCells("B{$sigRow}:D{$sigRow}");
        $sheet->setCellValue("B{$sigRow}", "Pelaksana Hitung Fisik,");
        $sheet->mergeCells("F{$sigRow}:I{$sigRow}");
        $sheet->setCellValue("F{$sigRow}", "Diverifikasi Oleh,");
        $sheet->mergeCells("K{$sigRow}:N{$sigRow}");
        $sheet->setCellValue("K{$sigRow}", "Disetujui & Diaudit Oleh,");

        $sheet->mergeCells("B" . ($sigRow + 3) . ":D" . ($sigRow + 3));
        $sheet->setCellValue("B" . ($sigRow + 3), "( " . ($opname['nama_pembuat'] ?? 'Petugas Gudang') . " )");
        $sheet->mergeCells("F" . ($sigRow + 3) . ":I" . ($sigRow + 3));
        $sheet->setCellValue("F" . ($sigRow + 3), "( ........................................ )");
        $sheet->mergeCells("K" . ($sigRow + 3) . ":N" . ($sigRow + 3));
        $sheet->setCellValue("K" . ($sigRow + 3), "( ........................................ )");

        $sheet->mergeCells("B" . ($sigRow + 4) . ":D" . ($sigRow + 4));
        $sheet->setCellValue("B" . ($sigRow + 4), "Staf Logistik / Gudang");
        $sheet->mergeCells("F" . ($sigRow + 4) . ":I" . ($sigRow + 4));
        $sheet->setCellValue("F" . ($sigRow + 4), "Kepala Gudang / Supervisor");
        $sheet->mergeCells("K" . ($sigRow + 4) . ":N" . ($sigRow + 4));
        $sheet->setCellValue("K" . ($sigRow + 4), "Admin / Owner / Keuangan");

        $sheet->getStyle("B{$sigRow}:N" . ($sigRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$sigRow}")->getFont()->setBold(true);
        $sheet->getStyle("F{$sigRow}")->getFont()->setBold(true);
        $sheet->getStyle("K{$sigRow}")->getFont()->setBold(true);
        $sheet->getStyle("B" . ($sigRow + 3) . ":N" . ($sigRow + 3))->getFont()->setBold(true);
        $sheet->getStyle("B" . ($sigRow + 4) . ":N" . ($sigRow + 4))->getFont()->setSize(8.5)->getColor()->setRGB('64748B');

        // 7. FREEZE PANES
        $sheet->freezePane('A10');

        // 8. EXPLICIT BALANCED COLUMN WIDTHS
        $opnameColWidths = [
            'A' => 7,   // No
            'B' => 16,  // Kode SKU
            'C' => 16,  // Barcode
            'D' => 32,  // Nama Produk
            'E' => 18,  // Varian Rasa
            'F' => 18,  // Grup Kemasan
            'G' => 10,  // Satuan
            'H' => 14,  // Stok Sistem
            'I' => 15,  // Stok Fisik Realita
            'J' => 14,  // Selisih Fisik
            'K' => 14,  // Tipe Mutasi
            'L' => 18,  // HPP Satuan (Rp)
            'M' => 22,  // Subtotal Selisih (Rp)
            'N' => 25   // Catatan Item
        ];
        foreach ($opnameColWidths as $colLtr => $width) {
            $sheet->getColumnDimension($colLtr)->setAutoSize(false)->setWidth($width);
        }

        // 9. PRINT SETUP
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        // 10. DOWNLOAD OUTPUT
        $rawDoc = (string)($opname['nomor_dokumen'] ?? '');
        $cleanDoc = preg_replace('/[^A-Za-z0-9]/', ' ', $rawDoc);
        $cleanDoc = trim(preg_replace('/\s+/', ' ', $cleanDoc));
        $dateFormatted = !empty($opname['tanggal_opname'])
            ? Format::tanggal($opname['tanggal_opname'], false, true)
            : Format::tanggal(date('Y-m-d'), false, true);
        $filename = ($cleanDoc !== '' ? "Opname {$cleanDoc} ({$dateFormatted})" : "Opname ({$dateFormatted})") . '.xlsx';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        self::outputToBrowser($spreadsheet, $filename);
    }

    /**
     * Helper privat untuk mengalirkan file spreadsheet ke browser
     */
    private static function outputToBrowser(Spreadsheet $spreadsheet, string $filename): void
    {
        $rawName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], ' ', $filename);
        $baseName = preg_replace('/\.xlsx$/i', '', $rawName);
        $cleanName = preg_replace('/[^A-Za-z0-9\(\)\.\-\s]+/', ' ', $baseName);
        $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));
        $finalFilename = ($cleanName !== '' ? $cleanName : 'Laporan') . '.xlsx';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . addcslashes($finalFilename, '"\\') . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Konversi index kolom integer (1-indexed: 1 = A, 2 = B, 27 = AA, dst.)
     */
    public static function columnLetter(int $colIndex): string
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
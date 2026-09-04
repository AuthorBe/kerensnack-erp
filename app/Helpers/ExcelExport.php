<?php
declare(strict_types=1);

namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
        if (!str_ends_with(strtolower($filename), '.xlsx')) {
            $filename .= '.xlsx';
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . rawurlencode($filename) . '"');
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
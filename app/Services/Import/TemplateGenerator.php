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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
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
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $themeSubBg]],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Baris 3: Catatan / Petunjuk Teknis Kolom (Justified, Auto-Wrap & Expanded Row Height)
        $sheet->mergeCells("A3:{$lastColLtr}3");
        $notesText = "Petunjuk: " . implode(" | ", $notes);
        $sheet->setCellValue('A3', $notesText);
        $sheet->getStyle('A3')->applyFromArray([
            'font'      => ['size' => 8.5, 'color' => ['rgb' => '334155']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_JUSTIFY,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
        ]);

        // Hitung tinggi baris secara adaptif agar teks petunjuk tampak utuh dan tidak terpotong
        $totalHeaderWidth = array_sum(array_slice($widths, 0, count($headers)));
        $charsPerLine = max(50, (int)($totalHeaderWidth * 0.90));
        $estimatedLines = max(2, (int)ceil(strlen($notesText) / $charsPerLine));
        $notesRowHeight = max(45, min(140, $estimatedLines * 18 + 14));
        $sheet->getRowDimension(3)->setRowHeight($notesRowHeight);

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

        // Tambahkan Sheet 2: Kamus & Referensi Data Sistem
        self::addReferenceSheet($spreadsheet, $handler, $pdo);

        // Pastikan Sheet 1 adalah sheet aktif saat file dibuka oleh pengguna di Excel
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Membangun Sheet ke-2: Kamus & Referensi Data Sistem untuk mempermudah user mengisi Sheet 1
     * (Hanya dibuat untuk master data yang memiliki relasi / kamus enum di sistem)
     */
    public static function addReferenceSheet(Spreadsheet $spreadsheet, EntityImportHandlerInterface $handler, PDO $pdo): void
    {
        $entityKey = $handler->getEntityKey();
        $entityLabel = $handler->getEntityLabel();

        // Entitas mandiri tanpa ketergantungan relasi (seperti kelompok upah borongan, merek, wilayah) tidak memerlukan sheet kamus
        $supportedRefEntities = [
            'employees',
            'customers',
            'suppliers',
            'products',
            'materials',
            'pricing_matrix',
            'product_groups',
            'customer_groups',
        ];

        if (!in_array($entityKey, $supportedRefEntities, true)) {
            return;
        }

        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Kamus & Referensi Data');
        $refSheet->setShowGridLines(true);

        $currentRow = 4;

        switch ($entityKey) {
            case 'employees':
                // 1. Posisi / Tugas yang Sah
                self::renderReferenceTable(
                    $refSheet,
                    '1. DAFTAR POSISI / TUGAS KARYAWAN YANG SAH DI SISTEM',
                    ['Kode Posisi (Wajib)', 'Nama Peran / Jabatan', 'Uraian Tugas & Tanggung Jawab'],
                    [
                        ['admin', 'Staff Administrasi', 'Pengelolaan data kantor, operasional, pencatatan transaksi & kas'],
                        ['mandor', 'Mandor Produksi', 'Pengawasan lantai produksi, pembagian SPK kemasan, kontrol hasil borongan'],
                        ['pengemasan', 'Tenaga Pengemasan', 'Pekerja borongan repacking / pengemasan produk keripik & snack'],
                        ['sales', 'Sales Distribusi', 'Kunjungan outlet mitra, pembinaan rute toko pelanggan, pencatatan pesanan'],
                        ['driver', 'Driver Pengiriman', 'Pengantaran pesanan toko, serah terima surat jalan & penagihan nota'],
                        ['gudang', 'Staff Gudang & Logistik', 'Pengelolaan stok fisik, penerimaan barang vendor, dan penyiapan pesanan (packing PO)'],
                        ['owner', 'Owner / Pemilik', 'Monitoring ringkasan bisnis, kontrol finansial dan performa menyeluruh'],
                    ],
                    $currentRow,
                    [22, 28, 65]
                );

                // 2. Sistem Penggajian
                self::renderReferenceTable(
                    $refSheet,
                    '2. SISTEM PENGGAJIAN',
                    ['Tipe Penggajian (Wajib)', 'Keterangan Sistem Gaji'],
                    [
                        ['borongan', 'Upah dihitung otomatis berdasarkan jumlah bungkus kemasan yang dikerjakan dikali tarif per pcs'],
                        ['bulanan', 'Gaji pokok bulanan tetap + uang kehadiran harian + tunjangan bulanan'],
                    ],
                    $currentRow,
                    [25, 80]
                );

                // 3. Format Data Standar
                self::renderReferenceTable(
                    $refSheet,
                    '3. PETUNJUK FORMAT ATRIBUT',
                    ['Nama Kolom', 'Aturan & Format Penulisan', 'Contoh Nilai Valid'],
                    [
                        ['NIK Karyawan', 'Wajib 16 digit angka KTP asli dan unik. Tidak boleh kosong.', '3201012345670001'],
                        ['No WhatsApp', 'Nomor WhatsApp aktif format Indonesia (diawali 08xxx)', '081234567890'],
                        ['Tanggal Bergabung', 'Format tanggal standar YYYY-MM-DD', '2023-01-15'],
                        ['Bank & Rekening', 'Nama bank nasional/daerah dan nomor rekening pekerja', 'BCA / 1234567890'],
                    ],
                    $currentRow,
                    [25, 55, 30]
                );
                break;

            case 'customers':
                // 1. Master Grup Pelanggan
                $dbGroups = $pdo->query("SELECT kode_grup, nama_grup, default_level_harga, diskon_persen_default 
                                         FROM public.grup_pelanggan WHERE status_aktif = TRUE ORDER BY kode_grup ASC")->fetchAll(PDO::FETCH_NUM);
                self::renderReferenceTable(
                    $refSheet,
                    '1. DAFTAR GRUP PELANGGAN AKTIF DI SISTEM',
                    ['Kode Grup', 'Nama Grup Pelanggan', 'Default Level Harga (1-30)', 'Diskon (%)'],
                    $dbGroups ?: [['GRP-001', 'Grup Pelanggan Umum', '1', '0.00']],
                    $currentRow,
                    [20, 32, 26, 16]
                );

                // 2. Master Wilayah & Rute Distribusi
                $dbTerritories = $pdo->query("SELECT kode_rute, nama_wilayah, kota_kabupaten, provinsi, COALESCE(sub_wilayah, '') as sub_wilayah 
                                              FROM public.wilayah WHERE status_aktif = TRUE ORDER BY kode_rute ASC")->fetchAll(PDO::FETCH_NUM);
                self::renderReferenceTable(
                    $refSheet,
                    '2. DAFTAR WILAYAH & RUTE DISTRIBUSI AKTIF DI SISTEM (WAJIB SESUAI)',
                    ['Kode Rute', 'Nama Wilayah / Rute', 'Kota / Kabupaten', 'Provinsi', 'Cakupan Sub-Wilayah / Kecamatan'],
                    $dbTerritories ?: [['RUTE-001', 'Tangerang Kota', 'Kota Tangerang', 'Banten', 'Cipondoh, Tangerang']],
                    $currentRow,
                    [18, 30, 24, 20, 45]
                );

                // 3. Master Sales Pembina
                $dbSales = $pdo->query("SELECT COALESCE(nik, '—') as nik, nama_lengkap, COALESCE(nama_pengguna, '—') as username 
                                        FROM public.pengguna WHERE posisi = 'sales' AND status_aktif = TRUE ORDER BY nama_lengkap ASC")->fetchAll(PDO::FETCH_NUM);
                self::renderReferenceTable(
                    $refSheet,
                    '3. DAFTAR SALES PEMBINA TOKO AKTIF DI SISTEM',
                    ['NIK Sales', 'Nama Lengkap Sales', 'Username Akun'],
                    $dbSales ?: [['3201012345670001', 'Budi Santoso', 'budi_sales']],
                    $currentRow,
                    [20, 32, 22]
                );

                // 4. Model Toko & Tipe Bayar
                self::renderReferenceTable(
                    $refSheet,
                    '4. ATRIBUT STANDAR TRANSAKSI TOKO',
                    ['Kategori Atribut', 'Pilihan Nilai yang Sah', 'Keterangan & Implikasi'],
                    [
                        ['Model Toko', 'Reguler', 'Jual beli standar / putus (tunai atau kredit piutang)'],
                        ['Model Toko', 'Konsinyasi', 'Titip jual rak toko mitra dengan rekonsiliasi berkala & sisa stok'],
                        ['Tipe Pembayaran', 'cash', 'Pembayaran tunai langsung saat barang diserahkan'],
                        ['Tipe Pembayaran', 'transfer', 'Pembayaran via transfer rekening bank'],
                        ['Tipe Pembayaran', 'tempo_7_hari', 'Jatuh tempo pembayaran 7 hari setelah pengiriman'],
                        ['Tipe Pembayaran', 'tempo_14_hari', 'Jatuh tempo pembayaran 14 hari setelah pengiriman'],
                        ['Tipe Pembayaran', 'tempo_30_hari', 'Jatuh tempo pembayaran 30 hari setelah pengiriman'],
                    ],
                    $currentRow,
                    [22, 25, 60]
                );
                break;

            case 'suppliers':
                // 1. Master Wilayah Pemasok
                $dbTerritories = $pdo->query("SELECT kode_rute, nama_wilayah, kota_kabupaten, provinsi, COALESCE(sub_wilayah, '') as sub_wilayah 
                                              FROM public.wilayah WHERE status_aktif = TRUE ORDER BY kode_rute ASC")->fetchAll(PDO::FETCH_NUM);
                self::renderReferenceTable(
                    $refSheet,
                    '1. DAFTAR WILAYAH / KOTA PEMASOK AKTIF (WAJIB SESUAI)',
                    ['Kode Rute', 'Nama Wilayah / Rute', 'Kota / Kabupaten', 'Provinsi', 'Cakupan Sub-Wilayah'],
                    $dbTerritories ?: [['RUTE-001', 'Tangerang Kota', 'Kota Tangerang', 'Banten', 'Cipondoh, Tangerang']],
                    $currentRow,
                    [18, 30, 24, 20, 45]
                );

                // 2. Termin Pembayaran
                self::renderReferenceTable(
                    $refSheet,
                    '2. PILIHAN TERMIN PEMBAYARAN PEMASOK YANG SAH',
                    ['Kode Termin (Wajib)', 'Label Termin', 'Keterangan Jatuh Tempo'],
                    [
                        ['cash', 'Tunai / Cash', 'Pembayaran tunai lunas saat barang tiba di gudang atau bayar di muka'],
                        ['transfer', 'Transfer Bank', 'Pembayaran via transfer rekening bank saat penagihan'],
                        ['tempo_7_hari', 'Tempo 7 Hari', 'Jatuh tempo 7 hari kalender setelah barang masuk gudang'],
                        ['tempo_14_hari', 'Tempo 14 Hari', 'Jatuh tempo 14 hari kalender setelah barang masuk gudang'],
                        ['tempo_30_hari', 'Tempo 30 Hari', 'Jatuh tempo 30 hari kalender setelah barang masuk gudang'],
                    ],
                    $currentRow,
                    [22, 25, 60]
                );
                break;

            case 'products':
                // 1. Grup Produk
                $dbGroups = $pdo->query("SELECT gp.kode_grup, gp.nama_grup, COALESCE(m.nama_merek, 'KEREN SNACK') as merek, gp.satuan_dasar 
                                         FROM public.grup_produk gp LEFT JOIN public.merek m ON m.id = gp.merek_id 
                                         WHERE gp.status_aktif = TRUE ORDER BY gp.kode_grup ASC")->fetchAll(PDO::FETCH_NUM);
                self::renderReferenceTable(
                    $refSheet,
                    '1. DAFTAR GRUP PRODUK KEMASAN AKTIF',
                    ['Kode Grup', 'Nama Grup Produk', 'Merek Dagang', 'Satuan Dasar'],
                    $dbGroups ?: [['GRP-001', 'Keripik Singkong 250gr', 'KEREN SNACK', 'pcs']],
                    $currentRow,
                    [20, 35, 22, 16]
                );

                // 2. Kelompok Upah Borongan
                $dbRates = $pdo->query("SELECT nama_kelompok, upah_per_bungkus, COALESCE(keterangan, '') as keterangan 
                                        FROM public.kelompok_upah_borongan WHERE status_aktif = TRUE ORDER BY nama_kelompok ASC")->fetchAll(PDO::FETCH_NUM);
                self::renderReferenceTable(
                    $refSheet,
                    '2. DAFTAR KELOMPOK UPAH BORONGAN AKTIF',
                    ['Nama Kelompok', 'Tarif Upah / Pcs (Rp)', 'Keterangan Spesifikasi Kemasan'],
                    $dbRates ?: [
                        ['Kelompok 300', '300', 'Tarif repacking kemasan kecil 50gr (Rp 300/bungkus)'],
                        ['Kelompok 600', '600', 'Tarif repacking singkong dan makaroni 250gr (Rp 600/bungkus)'],
                    ],
                    $currentRow,
                    [22, 24, 55]
                );

                // 3. Pemasok Utama
                $dbSuppliers = $pdo->query("SELECT p.kode_pemasok, p.nama_pemasok, COALESCE(w.nama_wilayah, '—') as wilayah 
                                            FROM public.pemasok p LEFT JOIN public.wilayah w ON w.id = p.wilayah_id 
                                            WHERE p.status_aktif = TRUE ORDER BY p.kode_pemasok ASC")->fetchAll(PDO::FETCH_NUM);
                self::renderReferenceTable(
                    $refSheet,
                    '3. DAFTAR PEMASOK VENDOR AKTIF',
                    ['Kode Pemasok', 'Nama Pemasok', 'Wilayah / Kota'],
                    $dbSuppliers ?: [['SUPP-0001', 'PT Sumber Rasa Sejahtera', 'Kota Tangerang']],
                    $currentRow,
                    [20, 35, 25]
                );
                break;

            case 'materials':
                // 1. Tipe Bahan
                self::renderReferenceTable(
                    $refSheet,
                    '1. TIPE BAHAN BAKU & KEMASAN',
                    ['Kode Tipe (Wajib)', 'Klasifikasi Bahan', 'Contoh Penggunaan Riil'],
                    [
                        ['bahan_mentah', 'Bahan Baku Mentah / Pangan', 'Singkong basah kupas, bumbu tabur, garam, minyak goreng, cabai bubuk'],
                        ['bahan_kemas', 'Bahan Kemasan / Packaging', 'Plastik PP, standing pouch zipper, kardus karton, stiker label, lakban'],
                    ],
                    $currentRow,
                    [20, 30, 60]
                );

                // 2. Satuan Dasar
                self::renderReferenceTable(
                    $refSheet,
                    '2. SATUAN DASAR INVENTORI',
                    ['Satuan Dasar', 'Keterangan & Penggunaan'],
                    [
                        ['kg', 'Kilogram — untuk bahan curah / timbangan (singkong, bumbu, garam)'],
                        ['pcs', 'Pieces / Satuan — untuk kardus, standing pouch satuan, partisi'],
                        ['roll', 'Roll / Gulungan — untuk plastik roll sablon, stiker rol, lakban'],
                        ['lembar', 'Lembar — untuk stiker label lembaran, karton sheet'],
                        ['liter', 'Liter — untuk minyak goreng curah / cair'],
                    ],
                    $currentRow,
                    [20, 65]
                );

                // 3. Pemasok Utama
                $dbSuppliers = $pdo->query("SELECT kode_pemasok, nama_pemasok FROM public.pemasok WHERE status_aktif = TRUE ORDER BY kode_pemasok ASC")->fetchAll(PDO::FETCH_NUM);
                self::renderReferenceTable(
                    $refSheet,
                    '3. DAFTAR PEMASOK VENDOR AKTIF',
                    ['Kode Pemasok', 'Nama Pemasok'],
                    $dbSuppliers ?: [['SUPP-0001', 'PT Sumber Rasa Sejahtera']],
                    $currentRow,
                    [20, 40]
                );
                break;

            case 'pricing_matrix':
                // 1. Grup Produk
                $dbGroups = $pdo->query("SELECT kode_grup, nama_grup FROM public.grup_produk WHERE status_aktif = TRUE ORDER BY kode_grup ASC")->fetchAll(PDO::FETCH_NUM);
                self::renderReferenceTable(
                    $refSheet,
                    '1. DAFTAR GRUP PRODUK KEMASAN AKTIF',
                    ['Kode Grup Produk', 'Nama Grup Produk'],
                    $dbGroups ?: [['GRP-SINGKONG-250', 'Keripik Singkong 250gr']],
                    $currentRow,
                    [24, 40]
                );

                // 2. Master Level Harga (1-30)
                $dbLevels = $pdo->query("SELECT level_nomor, nama_level, COALESCE(deskripsi, '') as deskripsi 
                                         FROM public.master_level_harga WHERE status_aktif = TRUE ORDER BY level_nomor ASC")->fetchAll(PDO::FETCH_NUM);
                self::renderReferenceTable(
                    $refSheet,
                    '2. DAFTAR 30 LEVEL HARGA ACUAN SISTEM',
                    ['Level Nomor (1-30)', 'Nama Level Harga', 'Deskripsi Segmen Distribusi'],
                    $dbLevels ?: [
                        ['1', 'Level 1 - Ritel Standar (POS)', 'Harga eceran langsung ke konsumen toko / kasir'],
                        ['5', 'Level 5 - Konsinyasi Rak Toko', 'Harga pokok titip jual rak warung / toko oleh-oleh'],
                        ['8', 'Level 8 - Grosir Mitra Warung', 'Harga jual mitra grosir partai besar'],
                    ],
                    $currentRow,
                    [20, 35, 55]
                );
                break;

            case 'product_groups':
                // 1. Merek Dagang
                $dbBrands = $pdo->query("SELECT kode_merek, nama_merek FROM public.merek WHERE status_aktif = TRUE ORDER BY kode_merek ASC")->fetchAll(PDO::FETCH_NUM);
                self::renderReferenceTable(
                    $refSheet,
                    '1. DAFTAR MEREK DAGANG PRODUK AKTIF',
                    ['Kode Merek', 'Nama Merek Dagang'],
                    $dbBrands ?: [['KRN', 'KEREN SNACK']],
                    $currentRow,
                    [20, 35]
                );
                break;

            case 'customer_groups':
                // 1. Level Harga
                $dbLevels = $pdo->query("SELECT level_nomor, nama_level, COALESCE(deskripsi, '') as deskripsi 
                                         FROM public.master_level_harga WHERE status_aktif = TRUE ORDER BY level_nomor ASC")->fetchAll(PDO::FETCH_NUM);
                self::renderReferenceTable(
                    $refSheet,
                    '1. DAFTAR 30 LEVEL HARGA ACUAN SISTEM',
                    ['Level Nomor (1-30)', 'Nama Level Harga', 'Deskripsi'],
                    $dbLevels ?: [['1', 'Level 1 - Ritel Standar (POS)', 'Harga jual eceran reguler']],
                    $currentRow,
                    [20, 35, 55]
                );

                // 2. Master Merek Dagang
                $dbBrands = $pdo->query("SELECT kode_merek, nama_merek FROM public.merek WHERE status_aktif = TRUE ORDER BY kode_merek ASC")->fetchAll(PDO::FETCH_NUM);
                self::renderReferenceTable(
                    $refSheet,
                    '2. DAFTAR MEREK DAGANG AKTIF DI SISTEM',
                    ['Kode Merek', 'Nama Merek Dagang'],
                    $dbBrands ?: [['KRN', 'KEREN SNACK']],
                    $currentRow,
                    [20, 35]
                );
                break;
        }

        // Render Banner Judul Utama Sheet Referensi (Disesuaikan dengan lebar kolom aktual)
        $highestCol = $refSheet->getHighestColumn();
        if ($highestCol === 'A') {
            $highestCol = 'C';
        }

        $refSheet->mergeCells("A1:{$highestCol}1");
        $refSheet->setCellValue('A1', 'KAMUS & REFERENSI DATA SISTEM: ' . strtoupper($entityLabel));
        $refSheet->getStyle("A1:{$highestCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        ]);
        $refSheet->getRowDimension(1)->setRowHeight(28);

        $refSheet->mergeCells("A2:{$highestCol}2");
        $refSheet->setCellValue('A2', 'Gunakan data referensi di bawah ini untuk mengisi kolom master pada Sheet 1 tanpa perlu membuka ulang aplikasi web.');
        $refSheet->getStyle("A2:{$highestCol}2")->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '475569']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
        ]);
        $refSheet->getRowDimension(2)->setRowHeight(18);

        // Freeze pane pada sheet referensi
        $refSheet->freezePane('A3');
    }

    /**
     * Helper merender tabel referensi terformat rapi pada sheet ke-2
     */
    private static function renderReferenceTable(
        Worksheet $sheet,
        string $title,
        array $headers,
        array $rows,
        int &$currentRow,
        array $widths = []
    ): void {
        $colCount = count($headers);
        $lastColLtr = Coordinate::stringFromColumnIndex($colCount);

        // Header Section Banner
        $sheet->mergeCells("A{$currentRow}:{$lastColLtr}{$currentRow}");
        $sheet->setCellValue("A{$currentRow}", $title);
        $sheet->getStyle("A{$currentRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        ]);
        $sheet->getRowDimension($currentRow)->setRowHeight(24);
        $currentRow++;

        // Table Header
        $tableHeaderRow = $currentRow;
        foreach ($headers as $cIdx => $hText) {
            $colLtr = Coordinate::stringFromColumnIndex($cIdx + 1);
            $sheet->setCellValue($colLtr . $tableHeaderRow, $hText);
            
            $customW = $widths[$cIdx] ?? 20;
            $currentW = $sheet->getColumnDimension($colLtr)->getWidth();
            if ($customW > $currentW) {
                $sheet->getColumnDimension($colLtr)->setWidth($customW);
            }
        }

        $sheet->getStyle("A{$tableHeaderRow}:{$lastColLtr}{$tableHeaderRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9.5, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '334155']]],
        ]);
        $sheet->getRowDimension($tableHeaderRow)->setRowHeight(22);
        $currentRow++;

        // Table Data Rows
        foreach ($rows as $rIdx => $row) {
            $rowBg = ($rIdx % 2 === 0) ? 'FFFFFF' : 'F8FAFC';
            foreach ($row as $cIdx => $val) {
                $colLtr = Coordinate::stringFromColumnIndex($cIdx + 1);
                $cellCoord = $colLtr . $currentRow;
                $sheet->setCellValueExplicit($cellCoord, (string)($val ?? ''), DataType::TYPE_STRING);
                $sheet->getStyle($cellCoord)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }

            $sheet->getStyle("A{$currentRow}:{$lastColLtr}{$currentRow}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rowBg]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'CBD5E1']]],
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(19);
            $currentRow++;
        }

        // Spasi antar tabel
        $currentRow += 2;
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

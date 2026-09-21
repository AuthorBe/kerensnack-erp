<?php
declare(strict_types=1);

namespace App\Services\Import\Handlers;

use App\Services\Import\SmartReader;
use PDO;

class CustomerImportHandler implements EntityImportHandlerInterface
{
    public function getEntityKey(): string
    {
        return 'customers';
    }

    public function getEntityLabel(): string
    {
        return 'Toko Pelanggan';
    }

    public function getRequiredPermission(): string
    {
        return 'master.customers_manage';
    }

    public function getRequiredHeaderGroups(): array
    {
        return [
            ['nama_toko', 'toko', 'nama_pelanggan', 'nama'],
            ['alamat', 'alamat_lengkap'],
            ['wilayah', 'rute', 'wilayah_rute', 'nama_wilayah']
        ];
    }

    public function getTemplateHeaders(): array
    {
        return [
            'Kode Pelanggan',
            'Nama Toko',
            'Nama Pemilik',
            'Grup Pelanggan',
            'Wilayah / Rute',
            'Model Kerjasama',
            'Alamat Lengkap',
            'No WhatsApp',
            'Tipe Bayar Default',
            'Plafon Piutang',
            'Sales Pembina',
            'Nama Bank',
            'No Rekening',
            'Atas Nama Rekening',
            'Status Aktif'
        ];
    }

    public function getTemplateWidths(): array
    {
        return [18, 28, 22, 22, 22, 18, 35, 18, 20, 18, 22, 14, 18, 22, 14];
    }

    public function getTemplateExamples(): array
    {
        return [
            ['CUST-0001', 'Toko Sumber Rezeki', 'Ibu Hj. Aminah', 'Grup Ritel A', 'RUTE-TNG-BARAT', 'Reguler', 'Jl. Merdeka No. 12, Tangerang', '081234567890', 'Tempo 14 Hari', 5000000, 'Budi Santoso', 'BCA', '1234567890', 'Aminah', 'Aktif'],
            ['CUST-0002', 'Warung Berkah Jaya', 'Pak Hendra', 'Grup Grosir Pasar', 'RUTE-JAKBAR-1', 'Konsinyasi', 'Pasar Laris Blok B No. 4, Cengkareng', '085678901234', 'Konsinyasi', 2000000, '', 'BRI', '9876543210', 'Hendra', 'Aktif'],
            ['', 'Toko Baru Makmur (Contoh Baru)', 'Bpk Slamet', 'Grup Ritel A', 'RUTE-TNG-TIMUR', 'Reguler', 'Jl. Raya Serpong No. 8', '081399887766', 'Cash', 0, '', '', '', '', 'Aktif'],
        ];
    }

    public function getTemplateNotes(): array
    {
        return [
            'Kolom Kode Pelanggan dapat dikosongkan untuk entri baru (akan dibuatkan otomatis oleh sistem: CUST-0002, dst).',
            'Pelanggan default sistem (CUST-001 / Toko Umum / Walk-in Cash) terkunci permanen dan tidak akan pernah terhapus atau dinonaktifkan pada proses sinkronisasi.',
            'Nama Toko, Wilayah/Rute, dan Alamat Lengkap WAJIB diisi di setiap baris.',
            'Model Kerjasama: isi "Konsinyasi" untuk toko titip jual rak, atau "Reguler" untuk jual putus / tempo.',
            'Tipe Bayar Default: Cash, Transfer, QRIS, Tempo 7 Hari, Tempo 14 Hari, Tempo 30 Hari, atau Konsinyasi (dapat ditulis dengan spasi atau huruf kecil/besar).',
            'Grup Pelanggan, Wilayah/Rute, dan Sales Pembina dapat diisi Kode atau Nama yang sudah terdaftar di sistem.'
        ];
    }

    public function getCurrentDataRows(PDO $pdo): array
    {
        $sql = "SELECT p.kode_pelanggan, p.nama_toko, COALESCE(p.nama_pemilik, '') as nama_pemilik,
                       COALESCE(g.nama_grup, '') as nama_grup,
                       COALESCE(w.nama_wilayah, '') as nama_wilayah,
                       CASE WHEN p.is_konsinyasi THEN 'Konsinyasi' ELSE 'Reguler' END as model_kerjasama,
                       p.alamat_lengkap,
                       COALESCE(p.nomor_whatsapp, '') as nomor_whatsapp,
                       CASE 
                           WHEN p.tipe_pembayaran_default = 'tempo_7_hari' THEN 'Tempo 7 Hari'
                           WHEN p.tipe_pembayaran_default = 'tempo_14_hari' THEN 'Tempo 14 Hari'
                           WHEN p.tipe_pembayaran_default = 'tempo_30_hari' THEN 'Tempo 30 Hari'
                           WHEN p.tipe_pembayaran_default = 'konsinyasi' THEN 'Konsinyasi'
                           WHEN p.tipe_pembayaran_default = 'transfer' THEN 'Transfer'
                           WHEN p.tipe_pembayaran_default = 'qris' THEN 'QRIS'
                           ELSE 'Cash'
                       END as tipe_bayar_label,
                       COALESCE(p.plafon_piutang, 0) as plafon_piutang,
                       COALESCE(k.nama_karyawan, '') as nama_sales,
                       COALESCE(p.nama_bank, '') as nama_bank,
                       COALESCE(p.nomor_rekening, '') as nomor_rekening,
                       COALESCE(p.atas_nama_rekening, '') as atas_nama_rekening,
                       CASE WHEN p.status_aktif THEN 'Aktif' ELSE 'Nonaktif' END as status_aktif_label
                FROM public.pelanggan p
                LEFT JOIN public.grup_pelanggan g ON g.id = p.grup_pelanggan_id
                LEFT JOIN public.wilayah w ON w.id = p.wilayah_id
                LEFT JOIN public.v_karyawan_info k ON k.id = p.sales_driver_id
                ORDER BY p.kode_pelanggan ASC";
        $stmt = $pdo->query($sql);
        $rows = [];
        while ($r = $stmt->fetch(PDO::FETCH_NUM)) {
            $rows[] = $r;
        }
        return $rows;
    }

    /**
     * Normalisasi Tipe Pembayaran (Mendukung spasi, variasi huruf, dan format enum)
     */
    public static function normalizePaymentType(string $raw, bool $isKonsinyasi = false): string
    {
        $clean = strtolower(trim($raw));
        if (empty($clean)) {
            return $isKonsinyasi ? 'konsinyasi' : 'cash';
        }

        if (str_contains($clean, '7')) {
            return 'tempo_7_hari';
        }
        if (str_contains($clean, '14')) {
            return 'tempo_14_hari';
        }
        if (str_contains($clean, '30')) {
            return 'tempo_30_hari';
        }
        if (str_contains($clean, 'konsin') || str_contains($clean, 'titip')) {
            return 'konsinyasi';
        }
        if (str_contains($clean, 'trans') || str_contains($clean, 'trf') || str_contains($clean, 'bank')) {
            return 'transfer';
        }
        if (str_contains($clean, 'qris')) {
            return 'qris';
        }
        if (str_contains($clean, 'cash') || str_contains($clean, 'tunai')) {
            return 'cash';
        }

        // Exact match fallback
        if (in_array($clean, ['cash', 'transfer', 'qris', 'tempo_7_hari', 'tempo_14_hari', 'tempo_30_hari', 'konsinyasi'], true)) {
            return $clean;
        }

        return $isKonsinyasi ? 'konsinyasi' : 'cash';
    }

    public function previewRows(array $rows, array $header, PDO $pdo, string $mode): array
    {
        // 1. Dapatkan Lookup Master Relasi untuk Validasi Cepat
        $groups = $pdo->query("SELECT id, kode_grup, nama_grup FROM public.grup_pelanggan")->fetchAll(PDO::FETCH_ASSOC);
        $groupMap = [];
        foreach ($groups as $g) {
            $groupMap[strtolower(trim($g['kode_grup']))] = $g['id'];
            $groupMap[strtolower(trim($g['nama_grup']))] = $g['id'];
        }
        $defaultGroupId = $groups[0]['id'] ?? null;

        $territories = $pdo->query("SELECT id, kode_rute, nama_wilayah, sub_wilayah FROM public.wilayah WHERE status_aktif = TRUE")->fetchAll(PDO::FETCH_ASSOC);
        $territoryMap = [];
        foreach ($territories as $t) {
            $territoryMap[strtolower(trim((string)$t['kode_rute']))] = $t['id'];
            $territoryMap[strtolower(trim((string)$t['nama_wilayah']))] = $t['id'];
            if (!empty($t['sub_wilayah'])) {
                $subs = explode(',', (string)$t['sub_wilayah']);
                foreach ($subs as $sub) {
                    $sTrim = strtolower(trim($sub));
                    if ($sTrim !== '' && !isset($territoryMap[$sTrim])) {
                        $territoryMap[$sTrim] = $t['id'];
                    }
                }
            }
        }

        $salesStaff = $pdo->query("SELECT id, nama_karyawan, nik, nama_pengguna FROM public.v_karyawan_info WHERE status_aktif = TRUE")->fetchAll(PDO::FETCH_ASSOC);
        $salesMap = [];
        foreach ($salesStaff as $u) {
            if (!empty($u['nama_karyawan'])) {
                $salesMap[strtolower(trim($u['nama_karyawan']))] = $u['id'];
            }
            if (!empty($u['nik'])) {
                $salesMap[strtolower(trim($u['nik']))] = $u['id'];
            }
            if (!empty($u['nama_pengguna'])) {
                $salesMap[strtolower(trim($u['nama_pengguna']))] = $u['id'];
            }
        }

        // Data database saat ini untuk diffing
        $currentDbRows = $pdo->query("SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik,
                                             p.grup_pelanggan_id, p.is_konsinyasi, p.wilayah_id,
                                             p.alamat_lengkap, p.nomor_whatsapp,
                                             p.tipe_pembayaran_default, p.plafon_piutang, p.sales_driver_id,
                                             p.nama_bank, p.nomor_rekening, p.atas_nama_rekening, p.status_aktif,
                                             g.nama_grup, w.nama_wilayah
                                      FROM public.pelanggan p
                                      LEFT JOIN public.grup_pelanggan g ON g.id = p.grup_pelanggan_id
                                      LEFT JOIN public.wilayah w ON w.id = p.wilayah_id")->fetchAll(PDO::FETCH_ASSOC);
        $dbByCode = [];
        $dbByName = [];
        foreach ($currentDbRows as $r) {
            if (!empty($r['kode_pelanggan'])) {
                $dbByCode[strtolower(trim($r['kode_pelanggan']))] = $r;
            }
            if (!empty($r['nama_toko'])) {
                $dbByName[strtolower(trim($r['nama_toko']))] = $r;
            }
        }

        $previewList = [];
        $seenCodesInFile = [];
        $processedDbIds = [];

        foreach ($rows as $idx => $row) {
            $lineNo = $idx + 2;
            $rowData = SmartReader::buildRowData($header, $row);

            $kode = (string)(SmartReader::getSmartValue($rowData, ['kode_pelanggan', 'kode', 'kd_pelanggan']) ?? '');
            $nama = (string)(SmartReader::getSmartValue($rowData, ['nama_toko', 'toko', 'nama_pelanggan', 'nama']) ?? '');
            $pemilik = (string)(SmartReader::getSmartValue($rowData, ['nama_pemilik', 'pemilik']) ?? '');
            $grupRaw = (string)(SmartReader::getSmartValue($rowData, ['grup_pelanggan', 'grup', 'kategori']) ?? '');
            $wilayahRaw = (string)(SmartReader::getSmartValue($rowData, ['wilayah', 'rute', 'wilayah_rute']) ?? '');
            $modelRaw = (string)(SmartReader::getSmartValue($rowData, ['model_kerjasama', 'konsinyasi', 'tipe_toko']) ?? '');
            $alamat = (string)(SmartReader::getSmartValue($rowData, ['alamat_lengkap', 'alamat']) ?? '');
            $whatsapp = (string)(SmartReader::getSmartValue($rowData, ['nomor_whatsapp', 'whatsapp', 'no_wa', 'wa', 'no_hp', 'hp', 'nomor_telepon', 'telepon', 'no_telp', 'telp']) ?? '');
            $tipeBayarRaw = (string)(SmartReader::getSmartValue($rowData, ['tipe_pembayaran_default', 'tipe_bayar', 'pembayaran', 'cara_bayar', 'metode_bayar']) ?? '');
            $plafonRaw = SmartReader::getSmartValue($rowData, ['plafon_piutang', 'plafon', 'limit_piutang']);
            $salesRaw = (string)(SmartReader::getSmartValue($rowData, ['sales_pembina', 'sales', 'sales_pic', 'sales_toko']) ?? '');
            $bankNama = (string)(SmartReader::getSmartValue($rowData, ['nama_bank', 'bank']) ?? '');
            $bankRek = (string)(SmartReader::getSmartValue($rowData, ['nomor_rekening', 'no_rekening', 'rekening']) ?? '');
            $bankAtasNama = (string)(SmartReader::getSmartValue($rowData, ['atas_nama_rekening', 'atas_nama']) ?? '');
            $statusAktifRaw = SmartReader::getSmartValue($rowData, ['status_aktif', 'status', 'aktif']);

            if (empty($nama) && empty($kode)) {
                continue;
            }

            // Normalisasi
            $plafon = SmartReader::normalizeNumeric($plafonRaw, 0.0);
            $isKonsinyasi = SmartReader::normalizeBoolean($modelRaw, false) || str_contains(strtolower($modelRaw), 'konsin');
            $statusAktif = SmartReader::normalizeBoolean($statusAktifRaw, true);
            $tipeBayar = self::normalizePaymentType($tipeBayarRaw, $isKonsinyasi);

            // Proteksi status_aktif untuk pelanggan default POS CUST-001
            if (strtoupper(trim((string)$kode)) === 'CUST-001') {
                $statusAktif = true;
            }

            // Validasi Field Wajib
            if (empty($nama)) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Nama Toko kosong pada baris {$lineNo}. Kolom ini wajib diisi.",
                    'data' => ['kode_pelanggan' => $kode, 'nama_toko' => '—', 'alamat_lengkap' => $alamat]
                ];
                continue;
            }
            if (empty($alamat)) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Alamat Lengkap kosong untuk toko '{$nama}' pada baris {$lineNo}. Wajib diisi.",
                    'data' => ['kode_pelanggan' => $kode, 'nama_toko' => $nama, 'alamat_lengkap' => '—']
                ];
                continue;
            }

            // Cek Duplikasi Kode di dalam File
            if (!empty($kode)) {
                $kKey = strtolower(trim($kode));
                if (isset($seenCodesInFile[$kKey])) {
                    $previewList[] = [
                        'action' => 'ERROR',
                        'error_msg' => "Duplikasi Kode Pelanggan '{$kode}' di dalam file Excel pada baris {$lineNo}.",
                        'data' => ['kode_pelanggan' => $kode, 'nama_toko' => $nama, 'alamat_lengkap' => $alamat]
                    ];
                    continue;
                }
                $seenCodesInFile[$kKey] = true;
            }

            // Resolusi Foreign Keys
            $grupId = null;
            if (!empty($grupRaw)) {
                $gKey = strtolower(trim($grupRaw));
                if (!isset($groupMap[$gKey])) {
                    $previewList[] = [
                        'action' => 'ERROR',
                        'error_msg' => empty($groups)
                            ? "Master Grup Pelanggan masih kosong di sistem. Impor Master Grup Pelanggan (Fase 1) terlebih dahulu."
                            : "Grup Pelanggan '{$grupRaw}' pada baris {$lineNo} tidak ditemukan di master sistem. Pastikan data terdaftar di Master Grup Pelanggan (Fase 1).",
                        'data' => ['kode_pelanggan' => $kode, 'nama_toko' => $nama, 'alamat_lengkap' => $alamat]
                    ];
                    continue;
                }
                $grupId = $groupMap[$gKey];
            } else {
                if (empty($defaultGroupId)) {
                    $previewList[] = [
                        'action' => 'ERROR',
                        'error_msg' => "Master Grup Pelanggan masih kosong (0 data di database). Tabel Pelanggan mewajibkan relasi Grup Pelanggan. Silakan impor data Grup Pelanggan terlebih dahulu (Fase 1).",
                        'data' => ['kode_pelanggan' => $kode, 'nama_toko' => $nama, 'alamat_lengkap' => $alamat]
                    ];
                    continue;
                }
                $grupId = $defaultGroupId;
            }

            if (empty($wilayahRaw)) {
                $previewList[] = [
                    'action'    => 'ERROR',
                    'error_msg' => "Wilayah / Rute kosong untuk toko '{$nama}' pada baris {$lineNo}. Kolom ini wajib diisi dengan wilayah terdaftar di sistem.",
                    'data'      => ['kode_pelanggan' => $kode, 'nama_toko' => $nama, 'alamat_lengkap' => $alamat]
                ];
                continue;
            }

            $wKey = strtolower(trim($wilayahRaw));
            if (!isset($territoryMap[$wKey])) {
                $previewList[] = [
                    'action'    => 'ERROR',
                    'error_msg' => "Wilayah / Rute '{$wilayahRaw}' pada baris {$lineNo} tidak terdaftar di sistem. Daftarkan wilayah di Master Wilayah (Fase 1) atau gunakan Kamus Pencarian Wilayah.",
                    'data'      => ['kode_pelanggan' => $kode, 'nama_toko' => $nama, 'alamat_lengkap' => $alamat]
                ];
                continue;
            }
            $wilayahId = $territoryMap[$wKey];

            $salesId = null;
            if (!empty($salesRaw)) {
                $sKey = strtolower(trim($salesRaw));
                if (!isset($salesMap[$sKey])) {
                    $previewList[] = [
                        'action' => 'ERROR',
                        'error_msg' => "Sales Pembina '{$salesRaw}' pada baris {$lineNo} tidak terdaftar sebagai pengguna posisi sales di sistem. Pastikan nama lengkap, NIK, atau username sales sesuai dengan data Master Karyawan (Fase 2).",
                        'data' => ['kode_pelanggan' => $kode, 'nama_toko' => $nama, 'alamat_lengkap' => $alamat]
                    ];
                    continue;
                }
                $salesId = $salesMap[$sKey];
            }

            // Pencocokan Data ke Database
            $dbRow = null;
            if (!empty($kode) && isset($dbByCode[strtolower(trim($kode))])) {
                $dbRow = $dbByCode[strtolower(trim($kode))];
            } elseif (empty($kode) && isset($dbByName[strtolower(trim($nama))])) {
                $dbRow = $dbByName[strtolower(trim($nama))];
            }

            // Proteksi status_aktif untuk record default CUST-001
            if ($dbRow && strtoupper(trim((string)$dbRow['kode_pelanggan'])) === 'CUST-001') {
                $statusAktif = true;
            }

            $itemData = [
                'id'                      => $dbRow['id'] ?? null,
                'kode_pelanggan'          => !empty($kode) ? $kode : ($dbRow['kode_pelanggan'] ?? ''),
                'nama_toko'               => $nama,
                'nama_pemilik'            => $pemilik,
                'grup_pelanggan_id'       => $grupId,
                'is_konsinyasi'           => $isKonsinyasi,
                'wilayah_id'              => $wilayahId,
                'alamat_lengkap'          => $alamat,
                'nomor_whatsapp'          => $whatsapp,
                'tipe_pembayaran_default' => $tipeBayar,
                'plafon_piutang'          => $plafon,
                'sales_driver_id'         => $salesId,
                'nama_bank'               => $bankNama,
                'nomor_rekening'          => $bankRek,
                'atas_nama_rekening'      => $bankAtasNama,
                'status_aktif'            => $statusAktif,
                // Label pembantu tampilan
                'display_grup'            => $grupRaw ?: ($dbRow['nama_grup'] ?? '—'),
                'display_wilayah'         => $wilayahRaw ?: ($dbRow['nama_wilayah'] ?? '—'),
            ];

            if ($dbRow) {
                $processedDbIds[] = $dbRow['id'];

                // Deteksi Konflik Fatal (Kode sama tapi Nama Toko beda total)
                if (!empty($kode) && !SmartReader::isSimilarName($dbRow['nama_toko'], $nama)) {
                    $previewList[] = [
                        'action'       => 'INSERT',
                        'is_fatal'     => true,
                        'fatal_reason' => "Kode '{$kode}' di database adalah milik toko \"{$dbRow['nama_toko']}\", tapi di Excel tertulis \"{$nama}\". Untuk keselamatan data toko lama, baris ini akan dibuat sebagai Toko Baru dengan Kode otomatis.",
                        'data'         => $itemData,
                        'old_data'     => $dbRow
                    ];
                    continue;
                }

                // Cek Perubahan (Diff)
                $isDiff = trim($nama) !== trim((string)$dbRow['nama_toko'])
                    || trim($pemilik) !== trim((string)($dbRow['nama_pemilik'] ?? ''))
                    || trim($alamat) !== trim((string)$dbRow['alamat_lengkap'])
                    || trim($whatsapp) !== trim((string)($dbRow['nomor_whatsapp'] ?? ''))
                    || $isKonsinyasi !== (bool)$dbRow['is_konsinyasi']
                    || $statusAktif !== (bool)$dbRow['status_aktif']
                    || abs($plafon - (float)$dbRow['plafon_piutang']) > 0.01
                    || $tipeBayar !== $dbRow['tipe_pembayaran_default']
                    || ($grupId && $grupId !== $dbRow['grup_pelanggan_id'])
                    || ($wilayahId && $wilayahId !== $dbRow['wilayah_id'])
                    || ($salesId && $salesId !== $dbRow['sales_driver_id'])
                    || trim($bankNama) !== trim((string)($dbRow['nama_bank'] ?? ''))
                    || trim($bankRek) !== trim((string)($dbRow['nomor_rekening'] ?? ''))
                    || trim($bankAtasNama) !== trim((string)($dbRow['atas_nama_rekening'] ?? ''));

                if ($isDiff) {
                    $previewList[] = [
                        'action'   => 'UPDATE',
                        'data'     => $itemData,
                        'old_data' => $dbRow
                    ];
                }
            } else {
                $previewList[] = [
                    'action' => 'INSERT',
                    'data'   => $itemData
                ];
            }
        }

        // Mode Sinkronisasi Penuh: Cek data database yang hilang di file Excel
        if ($mode === 'full_sync') {
            foreach ($currentDbRows as $c) {
                if (!in_array($c['id'], $processedDbIds, true)) {
                    // Proteksi Pelanggan Default POS (CUST-001): Kebal dari penghapusan Full-Sync
                    if (strtoupper(trim((string)$c['kode_pelanggan'])) === 'CUST-001') {
                        continue;
                    }

                    $previewList[] = [
                        'action' => 'DELETE',
                        'data'   => [
                            'id'             => $c['id'],
                            'kode_pelanggan' => $c['kode_pelanggan'],
                            'nama_toko'      => $c['nama_toko'],
                            'alamat_lengkap' => $c['alamat_lengkap'],
                            'status_aktif'   => $c['status_aktif'],
                            'display_grup'   => $c['nama_grup'] ?? '—',
                            'display_wilayah'=> $c['nama_wilayah'] ?? '—',
                        ]
                    ];
                }
            }
        }

        return $previewList;
    }

    /**
     * Memeriksa apakah pelanggan memiliki riwayat transaksi aktif (pesanan, konsinyasi, dll)
     */
    public function hasTransactionHistory(string $id, PDO $pdo): bool
    {
        $stmt = $pdo->prepare("SELECT 
            (SELECT COUNT(*) FROM public.pesanan WHERE pelanggan_id = ?) +
            (SELECT COUNT(*) FROM public.stok_konsinyasi_toko WHERE pelanggan_id = ?) +
            (SELECT COUNT(*) FROM public.kunjungan_konsinyasi WHERE pelanggan_id = ?) AS total_tx");
        $stmt->execute([$id, $id, $id]);
        return ((int)($stmt->fetch(PDO::FETCH_ASSOC)['total_tx'] ?? 0)) > 0;
    }

    public function applySync(array $previewList, PDO $pdo): array
    {
        $insertCount = 0;
        $updateCount = 0;
        $deleteCount = 0;
        $deactivateCount = 0;

        // Query generator kode baru jika kosong (abaikan CUST-001 agar sekuens rapi)
        $stmtMaxCode = $pdo->query("SELECT MAX(NULLIF(regexp_replace(kode_pelanggan, '[^0-9]', '', 'g'), '')::int) as max_seq FROM public.pelanggan WHERE kode_pelanggan ~ '^CUST-[0-9]+$' AND kode_pelanggan != 'CUST-001'");
        $nextSeq = ((int)($stmtMaxCode->fetch(PDO::FETCH_ASSOC)['max_seq'] ?? 0)) + 1;
        if ($nextSeq <= 1) {
            $nextSeq = 2; // CUST-001 adalah walk-in cash default
        }

        $stmtIns = $pdo->prepare("INSERT INTO public.pelanggan 
            (kode_pelanggan, nama_toko, nama_pemilik, grup_pelanggan_id, is_konsinyasi, wilayah_id, 
             alamat_lengkap, nomor_whatsapp, tipe_pembayaran_default, plafon_piutang, 
             sales_driver_id, nama_bank, nomor_rekening, atas_nama_rekening, status_aktif)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmtUpd = $pdo->prepare("UPDATE public.pelanggan SET 
            nama_toko = ?, nama_pemilik = ?, grup_pelanggan_id = ?, is_konsinyasi = ?, wilayah_id = ?, 
            alamat_lengkap = ?, nomor_whatsapp = ?, tipe_pembayaran_default = ?, 
            plafon_piutang = ?, sales_driver_id = ?, nama_bank = ?, nomor_rekening = ?, atas_nama_rekening = ?, 
            status_aktif = ?, diubah_pada = NOW()
            WHERE id = ?");

        $stmtDeactivate = $pdo->prepare("UPDATE public.pelanggan SET status_aktif = FALSE, diubah_pada = NOW() WHERE id = ?");
        $stmtDel = $pdo->prepare("DELETE FROM public.pelanggan WHERE id = ?");
        // Query default grup jika tidak ada
        $defaultGroupId = $pdo->query("SELECT id FROM public.grup_pelanggan ORDER BY id ASC LIMIT 1")->fetchColumn() ?: null;

        foreach ($previewList as $row) {
            $act = $row['action'];
            $isFatal = !empty($row['is_fatal']);
            $d = $row['data'] ?? [];

            if ($act === 'INSERT' || $isFatal) {
                $kode = $d['kode_pelanggan'] ?? '';
                if (empty($kode) || $isFatal) {
                    $kode = 'CUST-' . str_pad((string)$nextSeq++, 4, '0', STR_PAD_LEFT);
                }

                $stmtIns->execute([
                    $kode,
                    $d['nama_toko'] ?? '',
                    $d['nama_pemilik'] ?? null,
                    $d['grup_pelanggan_id'] ?? $defaultGroupId,
                    !empty($d['is_konsinyasi']) ? 1 : 0,
                    $d['wilayah_id'] ?? null,
                    $d['alamat_lengkap'] ?? '',
                    $d['nomor_whatsapp'] ?? null,
                    $d['tipe_pembayaran_default'] ?? 'cash',
                    $d['plafon_piutang'] ?? 0,
                    $d['sales_driver_id'] ?? null,
                    $d['nama_bank'] ?? null,
                    $d['nomor_rekening'] ?? null,
                    $d['atas_nama_rekening'] ?? null,
                    !empty($d['status_aktif']) ? 1 : 0,
                ]);
                $insertCount++;
            } elseif ($act === 'UPDATE') {
                $isCust001 = strtoupper(trim((string)($d['kode_pelanggan'] ?? ''))) === 'CUST-001';
                $statusAktifVal = $isCust001 ? 1 : (!empty($d['status_aktif']) ? 1 : 0);

                $stmtUpd->execute([
                    $d['nama_toko'] ?? '',
                    $d['nama_pemilik'] ?? null,
                    $d['grup_pelanggan_id'] ?? $defaultGroupId,
                    !empty($d['is_konsinyasi']) ? 1 : 0,
                    $d['wilayah_id'] ?? null,
                    $d['alamat_lengkap'] ?? '',
                    $d['nomor_whatsapp'] ?? null,
                    $d['tipe_pembayaran_default'] ?? 'cash',
                    $d['plafon_piutang'] ?? 0,
                    $d['sales_driver_id'] ?? null,
                    $d['nama_bank'] ?? null,
                    $d['nomor_rekening'] ?? null,
                    $d['atas_nama_rekening'] ?? null,
                    $statusAktifVal,
                    $d['id']
                ]);
                $updateCount++;
            } elseif ($act === 'DELETE') {
                $cid = (string)$d['id'];
                $ckode = strtoupper(trim((string)($d['kode_pelanggan'] ?? '')));
                if ($ckode === 'CUST-001') {
                    continue; // Skip proteksi default customer CUST-001
                }
                if ($this->hasTransactionHistory($cid, $pdo)) {
                    // Sensor cerdas: jika ada transaksi, soft-deactivate
                    $stmtDeactivate->execute([$cid]);
                    $deactivateCount++;
                } else {
                    // Bersih: hapus fisik
                    $stmtDel->execute([$cid]);
                    $deleteCount++;
                }
            }
        }

        return [
            'insert'     => $insertCount,
            'update'     => $updateCount,
            'delete'     => $deleteCount,
            'deactivate' => $deactivateCount
        ];
    }
}

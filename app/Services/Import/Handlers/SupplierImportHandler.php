<?php
declare(strict_types=1);

namespace App\Services\Import\Handlers;

use App\Services\Import\SmartReader;
use PDO;

class SupplierImportHandler implements EntityImportHandlerInterface
{
    public function getEntityKey(): string
    {
        return 'suppliers';
    }

    public function getEntityLabel(): string
    {
        return 'Pemasok Vendor';
    }

    public function getRequiredPermission(): string
    {
        return 'master.suppliers_manage';
    }

    public function getRequiredHeaderGroups(): array
    {
        return [
            ['nama_pemasok', 'pemasok', 'nama_supplier', 'supplier', 'nama_vendor'],
            ['wilayah', 'kota', 'wilayah_kota', 'nama_wilayah', 'rute']
        ];
    }

    public function getTemplateHeaders(): array
    {
        return [
            'Kode Pemasok',
            'Nama Pemasok',
            'Nama PIC / Kontak',
            'Wilayah / Kota',
            'Alamat Lengkap',
            'No WhatsApp',
            'Email',
            'Termin Bayar',
            'Nama Bank',
            'No Rekening',
            'Atas Nama Rekening',
            'Catatan',
            'Status Aktif'
        ];
    }

    public function getTemplateWidths(): array
    {
        return [18, 30, 22, 22, 35, 18, 24, 16, 14, 18, 22, 25, 14];
    }

    public function getTemplateExamples(): array
    {
        return [
            ['SUPP-0001', 'PT Sumber Rasa Sejahtera', 'Bpk. Gunawan', 'Kota Tangerang', 'Kawasan Industri Jatake Blok C No. 5', '081299887766', 'sales@sumberrasa.com', 'tempo_30_hari', 'BCA', '5544332211', 'PT Sumber Rasa Sejahtera', 'Supplier bumbu tabur', 'Aktif'],
            ['SUPP-0002', 'UD Plastik Prima Abadi', 'Ibu Melati', 'Kota Jakarta Barat', 'Jl. Daan Mogot KM 11 No. 88', '081765432109', 'order@primaabadi.com', 'tempo_14_hari', 'Mandiri', '1122334455', 'Melati', 'Supplier roll kemasan foil', 'Aktif'],
            ['', 'Sentra Singkong Subang', 'Kang Asep', 'Subang', 'Desa Cijambe, Subang', '082133445566', '', 'cash', 'BRI', '9988776655', 'Asep Saepudin', 'Supplier singkong basah', 'Aktif'],
        ];
    }

    public function getTemplateNotes(): array
    {
        return [
            'Kode Pemasok dapat dikosongkan untuk entri baru (otomatis di-generate sistem).',
            'Nama Pemasok dan Wilayah/Kota WAJIB diisi di setiap baris.',
            'Wilayah/Kota WAJIB diisi dengan Nama Wilayah, Kode Rute, atau Kecamatan yang sudah terdaftar di Master Wilayah.',
            'Termin Bayar: cash, tempo_7_hari, tempo_14_hari, tempo_30_hari, dll.',
            'Status Aktif diisi "Aktif" atau "Nonaktif".'
        ];
    }

    public function getCurrentDataRows(PDO $pdo): array
    {
        $sql = "SELECT p.kode_pemasok, p.nama_pemasok, COALESCE(p.nama_kontak, '') as nama_kontak,
                       COALESCE(w.nama_wilayah, '') as nama_wilayah,
                       COALESCE(p.alamat_lengkap, '') as alamat_lengkap,
                       COALESCE(p.nomor_whatsapp, '') as nomor_whatsapp,
                       COALESCE(p.email, '') as email,
                       COALESCE(p.termin_bayar, 'cash') as termin_bayar,
                       COALESCE(p.nama_bank, '') as nama_bank,
                       COALESCE(p.nomor_rekening, '') as nomor_rekening,
                       COALESCE(p.atas_nama_rekening, '') as atas_nama_rekening,
                       COALESCE(p.catatan, '') as catatan,
                       CASE WHEN p.status_aktif THEN 'Aktif' ELSE 'Nonaktif' END as status_aktif_label
                FROM public.pemasok p
                LEFT JOIN public.wilayah w ON w.id = p.wilayah_id
                ORDER BY p.kode_pemasok ASC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_NUM);
    }

    public function previewRows(array $rows, array $header, PDO $pdo, string $mode): array
    {
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

        $dbSuppliers = $pdo->query("SELECT p.*, COALESCE(w.nama_wilayah, '') as nama_wilayah FROM public.pemasok p LEFT JOIN public.wilayah w ON w.id = p.wilayah_id")->fetchAll(PDO::FETCH_ASSOC);
        $dbByCode = [];
        $dbByName = [];
        foreach ($dbSuppliers as $s) {
            $dbByCode[strtolower(trim($s['kode_pemasok']))] = $s;
            $dbByName[strtolower(trim($s['nama_pemasok']))] = $s;
        }

        $previewList = [];
        $seenCodes = [];
        $processedDbIds = [];

        foreach ($rows as $idx => $row) {
            $lineNo = $idx + 2;
            $rowData = SmartReader::buildRowData($header, $row);

            $kode = (string)(SmartReader::getSmartValue($rowData, ['kode_pemasok', 'kode', 'kd_pemasok', 'kd_supplier']) ?? '');
            $nama = (string)(SmartReader::getSmartValue($rowData, ['nama_pemasok', 'pemasok', 'nama_supplier', 'supplier', 'nama']) ?? '');
            $kontak = (string)(SmartReader::getSmartValue($rowData, ['nama_pic_kontak', 'nama_kontak', 'nama_pic', 'pic_kontak', 'kontak', 'pic']) ?? '');
            $wilayahRaw = (string)(SmartReader::getSmartValue($rowData, ['wilayah_kota', 'wilayah', 'kota', 'nama_wilayah']) ?? '');
            $alamat = (string)(SmartReader::getSmartValue($rowData, ['alamat_lengkap', 'alamat']) ?? '');
            $whatsapp = (string)(SmartReader::getSmartValue($rowData, ['no_whatsapp', 'nomor_whatsapp', 'whatsapp', 'no_wa', 'wa', 'nomor_telepon', 'telepon', 'no_telp', 'telp', 'no_hp', 'hp']) ?? '');
            $email = (string)(SmartReader::getSmartValue($rowData, ['email', 'surel']) ?? '');
            $termin = (string)(SmartReader::getSmartValue($rowData, ['termin_bayar', 'termin', 'syarat_bayar', 'metode_bayar']) ?? 'cash');
            $bankNama = (string)(SmartReader::getSmartValue($rowData, ['nama_bank', 'bank']) ?? '');
            $bankRek = (string)(SmartReader::getSmartValue($rowData, ['no_rekening', 'nomor_rekening', 'rekening', 'no_rek']) ?? '');
            $bankAtasNama = (string)(SmartReader::getSmartValue($rowData, ['atas_nama_rekening', 'atas_nama', 'a_n_rekening', 'an_rekening', 'nama_rekening']) ?? '');
            $catatan = (string)(SmartReader::getSmartValue($rowData, ['catatan', 'keterangan']) ?? '');
            $statusAktifRaw = SmartReader::getSmartValue($rowData, ['status_aktif', 'status', 'aktif']);

            if (empty($kode) && empty($nama)) {
                continue;
            }

            if (empty($nama)) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Nama Pemasok kosong pada baris {$lineNo}. Wajib diisi.",
                    'data' => ['kode_pemasok' => $kode, 'nama_pemasok' => '—']
                ];
                continue;
            }

            // Validasi Wajib Wilayah
            if (empty($wilayahRaw)) {
                $previewList[] = [
                    'action'    => 'ERROR',
                    'error_msg' => "Wilayah / Kota kosong untuk pemasok '{$nama}' pada baris {$lineNo}. Kolom ini wajib diisi dengan wilayah terdaftar.",
                    'data'      => ['kode_pemasok' => $kode, 'nama_pemasok' => $nama, 'display_wilayah' => '—']
                ];
                continue;
            }

            $wKey = strtolower(trim($wilayahRaw));
            if (!isset($territoryMap[$wKey])) {
                $previewList[] = [
                    'action'    => 'ERROR',
                    'error_msg' => "Wilayah / Kota '{$wilayahRaw}' pada baris {$lineNo} tidak terdaftar di Master Wilayah sistem. Daftarkan di Master Wilayah atau gunakan fitur Cari Wilayah.",
                    'data'      => ['kode_pemasok' => $kode, 'nama_pemasok' => $nama, 'display_wilayah' => $wilayahRaw]
                ];
                continue;
            }
            $wilayahId = $territoryMap[$wKey];

            $statusAktif = SmartReader::normalizeBoolean($statusAktifRaw, true);

            if (!empty($kode)) {
                $kKey = strtolower(trim($kode));
                if (isset($seenCodes[$kKey])) {
                    $previewList[] = [
                        'action' => 'ERROR',
                        'error_msg' => "Duplikasi Kode Pemasok '{$kode}' pada baris {$lineNo}.",
                        'data' => ['kode_pemasok' => $kode, 'nama_pemasok' => $nama]
                    ];
                    continue;
                }
                $seenCodes[$kKey] = true;
            }

            $dbRow = null;
            if (!empty($kode) && isset($dbByCode[strtolower(trim($kode))])) {
                $dbRow = $dbByCode[strtolower(trim($kode))];
            } elseif (empty($kode) && isset($dbByName[strtolower(trim($nama))])) {
                $dbRow = $dbByName[strtolower(trim($nama))];
            }

            $itemData = [
                'id'                 => $dbRow['id'] ?? null,
                'kode_pemasok'       => !empty($kode) ? $kode : ($dbRow['kode_pemasok'] ?? ''),
                'nama_pemasok'       => $nama,
                'nama_kontak'        => $kontak,
                'wilayah_id'         => $wilayahId,
                'alamat_lengkap'     => $alamat,
                'nomor_whatsapp'     => $whatsapp,
                'email'              => $email,
                'termin_bayar'       => $termin ?: 'cash',
                'nama_bank'          => $bankNama,
                'nomor_rekening'     => $bankRek,
                'atas_nama_rekening' => $bankAtasNama,
                'catatan'            => $catatan,
                'status_aktif'       => $statusAktif,
                'display_wilayah'    => $wilayahRaw ?: ($dbRow['nama_wilayah'] ?? '—')
            ];

            if ($dbRow) {
                $processedDbIds[] = $dbRow['id'];

                if (!empty($kode) && !SmartReader::isSimilarName($dbRow['nama_pemasok'], $nama)) {
                    $previewList[] = [
                        'action'       => 'INSERT',
                        'is_fatal'     => true,
                        'fatal_reason' => "Kode '{$kode}' di database adalah \"{$dbRow['nama_pemasok']}\", berbeda dengan \"{$nama}\". Dibuat sebagai pemasok baru.",
                        'data'         => $itemData,
                        'old_data'     => $dbRow
                    ];
                    continue;
                }

                $isDiff = trim($nama) !== trim((string)$dbRow['nama_pemasok'])
                    || trim($kontak) !== trim((string)($dbRow['nama_kontak'] ?? ''))
                    || trim($alamat) !== trim((string)($dbRow['alamat_lengkap'] ?? ''))
                    || trim($whatsapp) !== trim((string)($dbRow['nomor_whatsapp'] ?? ''))
                    || trim($email) !== trim((string)($dbRow['email'] ?? ''))
                    || trim($termin) !== trim((string)($dbRow['termin_bayar'] ?? 'cash'))
                    || ($wilayahId && $wilayahId !== $dbRow['wilayah_id'])
                    || trim($bankNama) !== trim((string)($dbRow['nama_bank'] ?? ''))
                    || trim($bankRek) !== trim((string)($dbRow['nomor_rekening'] ?? ''))
                    || trim($bankAtasNama) !== trim((string)($dbRow['atas_nama_rekening'] ?? ''))
                    || trim($catatan) !== trim((string)($dbRow['catatan'] ?? ''))
                    || $statusAktif !== (bool)$dbRow['status_aktif'];

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

        if ($mode === 'full_sync') {
            foreach ($dbSuppliers as $s) {
                if (!in_array($s['id'], $processedDbIds, true)) {
                    $previewList[] = [
                        'action' => 'DELETE',
                        'data'   => $s
                    ];
                }
            }
        }

        return $previewList;
    }

    public function applySync(array $previewList, PDO $pdo): array
    {
        $insertCount = 0;
        $updateCount = 0;
        $deleteCount = 0;
        $deactivateCount = 0;

        $stmtMaxCode = $pdo->query("SELECT MAX(SUBSTRING(kode_pemasok FROM 6)::int) as max_seq FROM public.pemasok WHERE kode_pemasok ~ '^SUPP-[0-9]+$'");
        $nextSeq = ((int)($stmtMaxCode->fetch(PDO::FETCH_ASSOC)['max_seq'] ?? 0)) + 1;

        $stmtIns = $pdo->prepare("INSERT INTO public.pemasok 
            (kode_pemasok, nama_pemasok, nama_kontak, wilayah_id, alamat_lengkap, nomor_whatsapp, email, termin_bayar, nama_bank, nomor_rekening, atas_nama_rekening, catatan, status_aktif)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmtUpd = $pdo->prepare("UPDATE public.pemasok SET 
            nama_pemasok = ?, nama_kontak = ?, wilayah_id = ?, alamat_lengkap = ?, nomor_whatsapp = ?, email = ?, termin_bayar = ?, nama_bank = ?, nomor_rekening = ?, atas_nama_rekening = ?, catatan = ?, status_aktif = ?, diubah_pada = NOW()
            WHERE id = ?");

        $stmtDeactivate = $pdo->prepare("UPDATE public.pemasok SET status_aktif = FALSE, diubah_pada = NOW() WHERE id = ?");
        $stmtDel = $pdo->prepare("DELETE FROM public.pemasok WHERE id = ?");

        $stmtCheckUsage = $pdo->prepare("SELECT 
            COALESCE((SELECT COUNT(*) FROM public.pembelian WHERE pemasok_id = ?), 0) +
            COALESCE((SELECT COUNT(*) FROM public.item WHERE pemasok_utama_id = ?), 0) AS total_usage");

        foreach ($previewList as $row) {
            $act = $row['action'];
            $isFatal = !empty($row['is_fatal']);
            $d = $row['data'];

            if ($act === 'INSERT' || $isFatal) {
                $kode = $d['kode_pemasok'];
                if (empty($kode) || $isFatal) {
                    $kode = 'SUPP-' . str_pad((string)$nextSeq++, 4, '0', STR_PAD_LEFT);
                }

                $stmtIns->execute([
                    $kode,
                    $d['nama_pemasok'],
                    $d['nama_kontak'] ?: null,
                    $d['wilayah_id'] ?: null,
                    $d['alamat_lengkap'] ?: null,
                    $d['nomor_whatsapp'] ?: null,
                    $d['email'] ?: null,
                    $d['termin_bayar'] ?: 'cash',
                    $d['nama_bank'] ?: null,
                    $d['nomor_rekening'] ?: null,
                    $d['atas_nama_rekening'] ?: null,
                    $d['catatan'] ?: null,
                    $d['status_aktif'] ? 1 : 0
                ]);
                $insertCount++;
            } elseif ($act === 'UPDATE') {
                $stmtUpd->execute([
                    $d['nama_pemasok'],
                    $d['nama_kontak'] ?: null,
                    $d['wilayah_id'] ?: null,
                    $d['alamat_lengkap'] ?: null,
                    $d['nomor_whatsapp'] ?: null,
                    $d['email'] ?: null,
                    $d['termin_bayar'] ?: 'cash',
                    $d['nama_bank'] ?: null,
                    $d['nomor_rekening'] ?: null,
                    $d['atas_nama_rekening'] ?: null,
                    $d['catatan'] ?: null,
                    $d['status_aktif'] ? 1 : 0,
                    $d['id']
                ]);
                $updateCount++;
            } elseif ($act === 'DELETE') {
                $sid = $d['id'];
                $stmtCheckUsage->execute([$sid, $sid]);
                $usage = (int)$stmtCheckUsage->fetchColumn();

                if ($usage > 0) {
                    $stmtDeactivate->execute([$sid]);
                    $deactivateCount++;
                } else {
                    $stmtDel->execute([$sid]);
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

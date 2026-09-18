<?php
declare(strict_types=1);

namespace App\Services\Import\Handlers;

use App\Services\Import\SmartReader;
use PDO;

class MaterialItemImportHandler implements EntityImportHandlerInterface
{
    public function getEntityKey(): string
    {
        return 'materials';
    }

    public function getEntityLabel(): string
    {
        return 'Bahan Baku & Bahan Kemas';
    }

    public function getRequiredPermission(): string
    {
        return 'master.materials_manage';
    }

    public function getRequiredHeaderGroups(): array
    {
        return [
            ['nama_bahan', 'nama_item', 'nama'],
            ['satuan_dasar', 'satuan']
        ];
    }

    public function getTemplateHeaders(): array
    {
        return [
            'Kode Bahan',
            'Nama Bahan Baku / Kemas',
            'Tipe Bahan (bahan_mentah / bahan_kemas)',
            'Satuan Dasar (kg / pcs / lembar / roll)',
            'Pemasok Utama',
            'Harga Beli Pokok (Rp)',
            'Stok Minimum Warning',
            'Status Aktif'
        ];
    }

    public function getTemplateWidths(): array
    {
        return [18, 32, 28, 24, 24, 20, 20, 14];
    }

    public function getTemplateExamples(): array
    {
        return [
            ['BAHAN-SINGKONG-CURAH', 'Singkong Basah Kupas Grade A', 'bahan_mentah', 'kg', 'Sentra Singkong Subang', 3500, 500, 'Aktif'],
            ['BAHAN-BUMBU-BALADO', 'Bumbu Tabur Balado Super 1kg', 'bahan_mentah', 'kg', 'PT Sumber Rasa Sejahtera', 45000, 20, 'Aktif'],
            ['KEMAS-PLASTIK-250', 'Plastik Kemasan Sablon 250gr', 'bahan_kemas', 'lembar', 'UD Plastik Prima Abadi', 650, 2000, 'Aktif'],
        ];
    }

    public function getTemplateNotes(): array
    {
        return [
            'Kode Bahan bersifat unik (contoh: BAHAN-SINGKONG-CURAH). Kosongkan jika ingin auto-code.',
            'Nama Bahan dan Satuan Dasar WAJIB diisi.',
            'Tipe Bahan: isi "bahan_mentah" (singkong, bumbu, minyak) atau "bahan_kemas" (plastik, kardus, lakban).',
            'Pemasok Utama dapat diisi Nama atau Kode Pemasok terdaftar.'
        ];
    }

    public function getCurrentDataRows(PDO $pdo): array
    {
        $sql = "SELECT i.kode_sku, i.nama_item, i.tipe_item, i.satuan_dasar,
                       COALESCE(s.nama_pemasok, '') as nama_pemasok,
                       i.harga_pokok_pembelian, i.stok_minimum_peringatan,
                       CASE WHEN i.status_aktif THEN 'Aktif' ELSE 'Nonaktif' END as status_aktif_label
                FROM public.item i
                LEFT JOIN public.pemasok s ON s.id = i.pemasok_utama_id
                WHERE i.tipe_item IN ('bahan_mentah', 'bahan_kemas')
                ORDER BY i.kode_sku ASC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_NUM);
    }

    public function previewRows(array $rows, array $header, PDO $pdo, string $mode): array
    {
        $suppliers = $pdo->query("SELECT id, kode_pemasok, nama_pemasok FROM public.pemasok")->fetchAll(PDO::FETCH_ASSOC);
        $supplierMap = [];
        foreach ($suppliers as $s) {
            $supplierMap[strtolower(trim($s['kode_pemasok']))] = $s['id'];
            $supplierMap[strtolower(trim($s['nama_pemasok']))] = $s['id'];
        }

        $dbMaterials = $pdo->query("SELECT i.*, COALESCE(s.nama_pemasok, '') as nama_pemasok 
                                    FROM public.item i 
                                    LEFT JOIN public.pemasok s ON s.id = i.pemasok_utama_id 
                                    WHERE i.tipe_item IN ('bahan_mentah', 'bahan_kemas')")->fetchAll(PDO::FETCH_ASSOC);
        $dbByCode = [];
        $dbByName = [];
        foreach ($dbMaterials as $m) {
            $dbByCode[strtolower(trim($m['kode_sku']))] = $m;
            $dbByName[strtolower(trim($m['nama_item']))] = $m;
        }

        $previewList = [];
        $seenCodes = [];
        $processedDbIds = [];

        foreach ($rows as $idx => $row) {
            $lineNo = $idx + 2;
            $rowData = SmartReader::buildRowData($header, $row);

            $kode = (string)(SmartReader::getSmartValue($rowData, ['kode_bahan', 'kode_sku', 'kode', 'kd_bahan']) ?? '');
            $nama = (string)(SmartReader::getSmartValue($rowData, ['nama_bahan_baku_kemas', 'nama_bahan', 'nama_item', 'nama']) ?? '');
            $tipeRaw = (string)(SmartReader::getSmartValue($rowData, ['tipe_bahan', 'tipe', 'jenis_bahan']) ?? 'bahan_mentah');
            $satuan = (string)(SmartReader::getSmartValue($rowData, ['satuan_dasar', 'satuan']) ?? 'kg');
            $pemasokRaw = (string)(SmartReader::getSmartValue($rowData, ['pemasok_utama', 'pemasok', 'supplier']) ?? '');
            $hargaRaw = SmartReader::getSmartValue($rowData, ['harga_beli_pokok', 'harga_pokok_pembelian', 'harga_beli']);
            $stokMinRaw = SmartReader::getSmartValue($rowData, ['stok_minimum_warning', 'stok_minimum_peringatan', 'stok_min']);
            $statusAktifRaw = SmartReader::getSmartValue($rowData, ['status_aktif', 'status', 'aktif']);

            if (empty($kode) && empty($nama)) {
                continue;
            }

            if (empty($nama)) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Nama Bahan kosong pada baris {$lineNo}. Wajib diisi.",
                    'data' => ['kode_sku' => $kode, 'nama_item' => '—']
                ];
                continue;
            }

            $tipe = strtolower(trim($tipeRaw));
            if (!in_array($tipe, ['bahan_mentah', 'bahan_kemas'], true)) {
                $tipe = str_contains($tipe, 'kemas') ? 'bahan_kemas' : 'bahan_mentah';
            }

            $harga = SmartReader::normalizeNumeric($hargaRaw, 0.0);
            $stokMin = SmartReader::normalizeNumeric($stokMinRaw, 10.0);
            $statusAktif = SmartReader::normalizeBoolean($statusAktifRaw, true);

            if (!empty($kode)) {
                $kKey = strtolower(trim($kode));
                if (isset($seenCodes[$kKey])) {
                    $previewList[] = [
                        'action' => 'ERROR',
                        'error_msg' => "Duplikasi Kode Bahan '{$kode}' pada baris {$lineNo}.",
                        'data' => ['kode_sku' => $kode, 'nama_item' => $nama]
                    ];
                    continue;
                }
                $seenCodes[$kKey] = true;
            }

            $pemasokId = null;
            if (!empty($pemasokRaw)) {
                $pKey = strtolower(trim($pemasokRaw));
                if (isset($supplierMap[$pKey])) {
                    $pemasokId = $supplierMap[$pKey];
                }
            }

            $dbRow = null;
            if (!empty($kode) && isset($dbByCode[strtolower(trim($kode))])) {
                $dbRow = $dbByCode[strtolower(trim($kode))];
            } elseif (empty($kode) && isset($dbByName[strtolower(trim($nama))])) {
                $dbRow = $dbByName[strtolower(trim($nama))];
            }

            $itemData = [
                'id'                      => $dbRow['id'] ?? null,
                'kode_sku'                => !empty($kode) ? $kode : ($dbRow['kode_sku'] ?? ''),
                'nama_item'               => $nama,
                'tipe_item'               => $tipe,
                'satuan_dasar'            => $satuan ?: 'kg',
                'pemasok_utama_id'        => $pemasokId ?: ($dbRow['pemasok_utama_id'] ?? null),
                'harga_pokok_pembelian'   => $harga,
                'stok_minimum_peringatan' => $stokMin,
                'status_jual'             => false, // Bahan baku tidak dijual langsung
                'status_aktif'            => $statusAktif,
                'display_pemasok'         => $pemasokRaw ?: ($dbRow['nama_pemasok'] ?? '—')
            ];

            if ($dbRow) {
                $processedDbIds[] = $dbRow['id'];

                if (!empty($kode) && !SmartReader::isSimilarName($dbRow['nama_item'], $nama)) {
                    $previewList[] = [
                        'action'       => 'INSERT',
                        'is_fatal'     => true,
                        'fatal_reason' => "Kode '{$kode}' di database terdaftar atas \"{$dbRow['nama_item']}\", berbeda dengan \"{$nama}\". Dibuat sebagai bahan baru.",
                        'data'         => $itemData,
                        'old_data'     => $dbRow
                    ];
                    continue;
                }

                $isDiff = trim($nama) !== trim((string)$dbRow['nama_item'])
                    || trim($tipe) !== trim((string)$dbRow['tipe_item'])
                    || trim($satuan) !== trim((string)$dbRow['satuan_dasar'])
                    || ($pemasokId && $pemasokId !== $dbRow['pemasok_utama_id'])
                    || abs($harga - (float)$dbRow['harga_pokok_pembelian']) > 0.01
                    || abs($stokMin - (float)$dbRow['stok_minimum_peringatan']) > 0.01
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
            foreach ($dbMaterials as $m) {
                if (!in_array($m['id'], $processedDbIds, true)) {
                    $previewList[] = [
                        'action' => 'DELETE',
                        'data'   => $m
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

        $stmtMaxCode = $pdo->query("SELECT MAX(SUBSTRING(kode_sku FROM 7)::int) as max_seq FROM public.item WHERE kode_sku ~ '^BAHAN-[0-9]+$'");
        $nextSeq = ((int)($stmtMaxCode->fetch(PDO::FETCH_ASSOC)['max_seq'] ?? 0)) + 1;

        $stmtIns = $pdo->prepare("INSERT INTO public.item 
            (kode_sku, nama_item, tipe_item, satuan_dasar, pemasok_utama_id, harga_pokok_pembelian, stok_minimum_peringatan, status_jual, status_aktif)
            VALUES (?, ?, ?, ?, ?, ?, ?, FALSE, ?)");

        $stmtUpd = $pdo->prepare("UPDATE public.item SET 
            nama_item = ?, tipe_item = ?, satuan_dasar = ?, pemasok_utama_id = ?, harga_pokok_pembelian = ?, stok_minimum_peringatan = ?, status_aktif = ?, diubah_pada = NOW()
            WHERE id = ?");

        $stmtDeactivate = $pdo->prepare("UPDATE public.item SET status_aktif = FALSE, diubah_pada = NOW() WHERE id = ?");
        $stmtDel = $pdo->prepare("DELETE FROM public.item WHERE id = ?");

        $stmtCheckUsage = $pdo->prepare("SELECT 
            (SELECT COUNT(*) FROM public.komposisi_item WHERE item_bahan_id = ?) +
            (SELECT COUNT(*) FROM public.rincian_pembelian WHERE item_id = ?) +
            (SELECT COUNT(*) FROM public.riwayat_stok WHERE item_id = ?) AS total_usage");

        foreach ($previewList as $row) {
            $act = $row['action'];
            $isFatal = !empty($row['is_fatal']);
            $d = $row['data'];

            if ($act === 'INSERT' || $isFatal) {
                $kode = $d['kode_sku'];
                if (empty($kode) || $isFatal) {
                    $kode = 'BAHAN-' . str_pad((string)$nextSeq++, 4, '0', STR_PAD_LEFT);
                }

                $stmtIns->execute([
                    $kode,
                    $d['nama_item'],
                    $d['tipe_item'],
                    $d['satuan_dasar'] ?: 'kg',
                    $d['pemasok_utama_id'] ?: null,
                    $d['harga_pokok_pembelian'] ?: 0,
                    $d['stok_minimum_peringatan'] ?: 10,
                    $d['status_aktif'] ? 1 : 0
                ]);
                $insertCount++;
            } elseif ($act === 'UPDATE') {
                $stmtUpd->execute([
                    $d['nama_item'],
                    $d['tipe_item'],
                    $d['satuan_dasar'] ?: 'kg',
                    $d['pemasok_utama_id'] ?: null,
                    $d['harga_pokok_pembelian'] ?: 0,
                    $d['stok_minimum_peringatan'] ?: 10,
                    $d['status_aktif'] ? 1 : 0,
                    $d['id']
                ]);
                $updateCount++;
            } elseif ($act === 'DELETE') {
                $mid = $d['id'];
                $stmtCheckUsage->execute([$mid, $mid, $mid]);
                $usage = (int)$stmtCheckUsage->fetchColumn();

                if ($usage > 0) {
                    $stmtDeactivate->execute([$mid]);
                    $deactivateCount++;
                } else {
                    $stmtDel->execute([$mid]);
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

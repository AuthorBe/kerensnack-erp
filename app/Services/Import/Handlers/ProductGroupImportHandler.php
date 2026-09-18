<?php
declare(strict_types=1);

namespace App\Services\Import\Handlers;

use App\Services\Import\SmartReader;
use PDO;

class ProductGroupImportHandler implements EntityImportHandlerInterface
{
    public function getEntityKey(): string
    {
        return 'product_groups';
    }

    public function getEntityLabel(): string
    {
        return 'Grup Produk & Barcode Universal';
    }

    public function getRequiredPermission(): string
    {
        return 'master.products_manage';
    }

    public function getRequiredHeaderGroups(): array
    {
        return [
            ['nama_grup', 'nama_produk_grup', 'grup'],
            ['konversi', 'konversi_bal_ke_pcs', 'satuan_dasar']
        ];
    }

    public function getTemplateHeaders(): array
    {
        return [
            'Kode Grup',
            'Nama Grup Produk',
            'Barcode Universal',
            'Satuan Dasar',
            'Satuan Distribusi',
            'Isi Bal ke Pcs',
            'Status Aktif'
        ];
    }

    public function getTemplateWidths(): array
    {
        return [20, 32, 22, 16, 18, 16, 14];
    }

    public function getTemplateExamples(): array
    {
        return [
            ['GRP-SINGKONG-250', 'Keripik Singkong 250gr', '8991234567890', 'pcs', 'bal', 20, 'Aktif'],
            ['GRP-BASRENG-150', 'Basreng Pedas Daun Jeruk 150gr', '8999876543210', 'pcs', 'bal', 20, 'Aktif'],
            ['', 'Makaroni Spiral Pedas 100gr', '', 'pcs', 'bal', 25, 'Aktif'],
        ];
    }

    public function getTemplateNotes(): array
    {
        return [
            'Kode Grup unik (contoh: GRP-SINGKONG-250). Kosongkan untuk kode otomatis.',
            'Nama Grup Produk WAJIB diisi.',
            'Barcode Universal dipakai bersama oleh varian rasa yang menggunakan kemasan luar sama.',
            'Isi Bal ke Pcs: jumlah isi bungkus per 1 bal / karton (contoh: 20 pcs).'
        ];
    }

    public function getCurrentDataRows(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT kode_grup, nama_grup, COALESCE(barcode_universal, '') as barcode, 
                                    satuan_dasar, satuan_distribusi, konversi_bal_ke_pcs,
                                    CASE WHEN status_aktif THEN 'Aktif' ELSE 'Nonaktif' END as status_aktif_label
                             FROM public.grup_produk
                             ORDER BY kode_grup ASC");
        return $stmt->fetchAll(PDO::FETCH_NUM);
    }

    public function previewRows(array $rows, array $header, PDO $pdo, string $mode): array
    {
        $dbGroups = $pdo->query("SELECT * FROM public.grup_produk")->fetchAll(PDO::FETCH_ASSOC);
        $dbByCode = [];
        $dbByName = [];
        foreach ($dbGroups as $g) {
            $dbByCode[strtolower(trim($g['kode_grup']))] = $g;
            $dbByName[strtolower(trim($g['nama_grup']))] = $g;
        }

        $previewList = [];
        $seenCodes = [];
        $processedDbIds = [];

        foreach ($rows as $idx => $row) {
            $lineNo = $idx + 2;
            $rowData = SmartReader::buildRowData($header, $row);

            $kode = (string)(SmartReader::getSmartValue($rowData, ['kode_grup', 'kode', 'kd_grup']) ?? '');
            $nama = (string)(SmartReader::getSmartValue($rowData, ['nama_grup', 'grup_produk', 'nama']) ?? '');
            $barcode = (string)(SmartReader::getSmartValue($rowData, ['barcode_universal', 'barcode', 'barcode_kemasan']) ?? '');
            $satuanDasar = (string)(SmartReader::getSmartValue($rowData, ['satuan_dasar', 'satuan']) ?? 'pcs');
            $satuanDist = (string)(SmartReader::getSmartValue($rowData, ['satuan_distribusi', 'satuan_besar']) ?? 'bal');
            $konversiRaw = SmartReader::getSmartValue($rowData, ['isi_bal_ke_pcs', 'konversi_bal_ke_pcs', 'konversi', 'isi_bal']);
            $statusAktifRaw = SmartReader::getSmartValue($rowData, ['status_aktif', 'status', 'aktif']);

            if (empty($kode) && empty($nama)) {
                continue;
            }

            if (empty($nama)) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Nama Grup Produk kosong pada baris {$lineNo}. Wajib diisi.",
                    'data' => ['kode_grup' => $kode, 'nama_grup' => '—']
                ];
                continue;
            }

            $konversi = (int)SmartReader::normalizeNumeric($konversiRaw, 20.0);
            if ($konversi <= 0) $konversi = 20;

            $statusAktif = SmartReader::normalizeBoolean($statusAktifRaw, true);

            if (!empty($kode)) {
                $kKey = strtolower(trim($kode));
                if (isset($seenCodes[$kKey])) {
                    $previewList[] = [
                        'action' => 'ERROR',
                        'error_msg' => "Duplikasi Kode Grup Produk '{$kode}' pada baris {$lineNo}.",
                        'data' => ['kode_grup' => $kode, 'nama_grup' => $nama]
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
                'id'                  => $dbRow['id'] ?? null,
                'kode_grup'           => !empty($kode) ? $kode : ($dbRow['kode_grup'] ?? ''),
                'nama_grup'           => $nama,
                'barcode_universal'   => !empty($barcode) ? $barcode : ($dbRow['barcode_universal'] ?? null),
                'satuan_dasar'        => $satuanDasar ?: 'pcs',
                'satuan_distribusi'   => $satuanDist ?: 'bal',
                'konversi_bal_ke_pcs' => $konversi,
                'status_aktif'        => $statusAktif,
            ];

            if ($dbRow) {
                $processedDbIds[] = $dbRow['id'];

                if (!empty($kode) && !SmartReader::isSimilarName($dbRow['nama_grup'], $nama)) {
                    $previewList[] = [
                        'action'       => 'INSERT',
                        'is_fatal'     => true,
                        'fatal_reason' => "Kode '{$kode}' di database adalah \"{$dbRow['nama_grup']}\", berbeda dengan \"{$nama}\". Dibuat sebagai grup produk baru.",
                        'data'         => $itemData,
                        'old_data'     => $dbRow
                    ];
                    continue;
                }

                $isDiff = trim($nama) !== trim((string)$dbRow['nama_grup'])
                    || trim((string)($barcode)) !== trim((string)($dbRow['barcode_universal'] ?? ''))
                    || trim($satuanDasar) !== trim((string)$dbRow['satuan_dasar'])
                    || trim($satuanDist) !== trim((string)$dbRow['satuan_distribusi'])
                    || (int)$konversi !== (int)$dbRow['konversi_bal_ke_pcs']
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
            foreach ($dbGroups as $g) {
                if (!in_array($g['id'], $processedDbIds, true)) {
                    $previewList[] = [
                        'action' => 'DELETE',
                        'data'   => $g
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

        $stmtMaxCode = $pdo->query("SELECT MAX(SUBSTRING(kode_grup FROM 5)::int) as max_seq FROM public.grup_produk WHERE kode_grup ~ '^GRP-[0-9]+$'");
        $nextSeq = ((int)($stmtMaxCode->fetch(PDO::FETCH_ASSOC)['max_seq'] ?? 0)) + 1;

        $stmtIns = $pdo->prepare("INSERT INTO public.grup_produk 
            (kode_grup, nama_grup, barcode_universal, satuan_dasar, satuan_distribusi, konversi_bal_ke_pcs, status_aktif)
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stmtUpd = $pdo->prepare("UPDATE public.grup_produk SET 
            nama_grup = ?, barcode_universal = ?, satuan_dasar = ?, satuan_distribusi = ?, konversi_bal_ke_pcs = ?, status_aktif = ?, diubah_pada = NOW()
            WHERE id = ?");

        $stmtDeactivate = $pdo->prepare("UPDATE public.grup_produk SET status_aktif = FALSE, diubah_pada = NOW() WHERE id = ?");
        $stmtDel = $pdo->prepare("DELETE FROM public.grup_produk WHERE id = ?");

        $stmtCheckUsage = $pdo->prepare("SELECT COUNT(*) FROM public.item WHERE grup_id = ?");

        foreach ($previewList as $row) {
            $act = $row['action'];
            $isFatal = !empty($row['is_fatal']);
            $d = $row['data'];

            if ($act === 'INSERT' || $isFatal) {
                $kode = $d['kode_grup'];
                if (empty($kode) || $isFatal) {
                    $kode = 'GRP-' . str_pad((string)$nextSeq++, 3, '0', STR_PAD_LEFT);
                }

                $stmtIns->execute([
                    $kode,
                    $d['nama_grup'],
                    $d['barcode_universal'] ?: null,
                    $d['satuan_dasar'] ?: 'pcs',
                    $d['satuan_distribusi'] ?: 'bal',
                    $d['konversi_bal_ke_pcs'] ?: 20,
                    $d['status_aktif'] ? 1 : 0
                ]);
                $insertCount++;
            } elseif ($act === 'UPDATE') {
                $stmtUpd->execute([
                    $d['nama_grup'],
                    $d['barcode_universal'] ?: null,
                    $d['satuan_dasar'] ?: 'pcs',
                    $d['satuan_distribusi'] ?: 'bal',
                    $d['konversi_bal_ke_pcs'] ?: 20,
                    $d['status_aktif'] ? 1 : 0,
                    $d['id']
                ]);
                $updateCount++;
            } elseif ($act === 'DELETE') {
                $gid = $d['id'];
                $stmtCheckUsage->execute([$gid]);
                $count = (int)$stmtCheckUsage->fetchColumn();

                if ($count > 0) {
                    $stmtDeactivate->execute([$gid]);
                    $deactivateCount++;
                } else {
                    $stmtDel->execute([$gid]);
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

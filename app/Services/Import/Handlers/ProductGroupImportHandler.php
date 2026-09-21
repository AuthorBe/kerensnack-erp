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
            ['nama_grup', 'nama_produk_grup', 'grup']
        ];
    }

    public function getTemplateHeaders(): array
    {
        return [
            'Kode Grup',
            'Merek',
            'Nama Grup Produk',
            'Barcode Universal',
            'Satuan Dasar',
            'Status Aktif'
        ];
    }

    public function getTemplateWidths(): array
    {
        return [18, 22, 32, 22, 16, 14];
    }

    public function getTemplateExamples(): array
    {
        return [
            ['GRP-SINGKONG-250', 'KEREN SNACK', 'Keripik Singkong 250gr', '8991234567890', 'pcs', 'Aktif'],
            ['GRP-BASRENG-150', 'KEREN SNACK', 'Basreng Pedas Daun Jeruk 150gr', '8999876543210', 'pcs', 'Aktif'],
            ['', 'KEREN SNACK', 'Makaroni Spiral Pedas 100gr', '', 'pcs', 'Aktif'],
        ];
    }

    public function getTemplateNotes(): array
    {
        return [
            'Kode Grup unik (contoh: GRP-SINGKONG-250). Kosongkan untuk kode otomatis.',
            'Merek dagang produk (contoh: KEREN SNACK). Kosongkan untuk merek default.',
            'Nama Grup Produk WAJIB diisi.',
            'Barcode Universal dipakai bersama oleh varian rasa yang menggunakan kemasan luar sama.',
            'Satuan Dasar: satuan fisik terkecil (default: pcs).'
        ];
    }

    public function getCurrentDataRows(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT gp.kode_grup, COALESCE(m.nama_merek, 'KEREN SNACK') as merek, gp.nama_grup, 
                                     COALESCE(gp.barcode_universal, '') as barcode, 
                                     gp.satuan_dasar,
                                     CASE WHEN gp.status_aktif THEN 'Aktif' ELSE 'Nonaktif' END as status_aktif_label
                              FROM public.grup_produk gp
                              LEFT JOIN public.merek m ON gp.merek_id = m.id
                              ORDER BY gp.kode_grup ASC");
        return $stmt->fetchAll(PDO::FETCH_NUM);
    }

    public function previewRows(array $rows, array $header, PDO $pdo, string $mode): array
    {
        $dbGroups = $pdo->query("SELECT gp.*, m.nama_merek FROM public.grup_produk gp LEFT JOIN public.merek m ON gp.merek_id = m.id")->fetchAll(PDO::FETCH_ASSOC);
        $dbBrands = $pdo->query("SELECT * FROM public.merek")->fetchAll(PDO::FETCH_ASSOC);

        $brandByName = [];
        $brandByCode = [];
        $defaultBrandId = null;
        foreach ($dbBrands as $b) {
            $brandByName[strtolower(trim($b['nama_merek']))] = $b['id'];
            $brandByCode[strtolower(trim($b['kode_merek']))] = $b['id'];
            if ($defaultBrandId === null && (bool)$b['status_aktif']) {
                $defaultBrandId = $b['id'];
            }
        }

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
            $merekRaw = (string)(SmartReader::getSmartValue($rowData, ['merek', 'brand', 'nama_merek', 'kode_merek']) ?? '');
            $nama = (string)(SmartReader::getSmartValue($rowData, ['nama_grup', 'grup_produk', 'nama']) ?? '');
            $barcode = (string)(SmartReader::getSmartValue($rowData, ['barcode_universal', 'barcode', 'barcode_kemasan']) ?? '');
            $satuanDasar = (string)(SmartReader::getSmartValue($rowData, ['satuan_dasar', 'satuan']) ?? 'pcs');
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

            // Resolve Merek ID
            $merekId = $defaultBrandId;
            if (!empty($merekRaw)) {
                $mKey = strtolower(trim($merekRaw));
                if (isset($brandByName[$mKey])) {
                    $merekId = $brandByName[$mKey];
                } elseif (isset($brandByCode[$mKey])) {
                    $merekId = $brandByCode[$mKey];
                }
            }

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
                'merek_id'            => $merekId ?: ($dbRow['merek_id'] ?? $defaultBrandId),
                'nama_grup'           => $nama,
                'barcode_universal'   => !empty($barcode) ? $barcode : ($dbRow['barcode_universal'] ?? null),
                'satuan_dasar'        => $satuanDasar ?: 'pcs',
                'status_aktif'        => $statusAktif,
                'display_merek'       => $merekRaw ?: ($dbRow['nama_merek'] ?? 'KEREN SNACK'),
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
                    || ($itemData['merek_id'] !== ($dbRow['merek_id'] ?? null))
                    || trim((string)($barcode)) !== trim((string)($dbRow['barcode_universal'] ?? ''))
                    || trim($satuanDasar) !== trim((string)$dbRow['satuan_dasar'])
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
            (kode_grup, nama_grup, barcode_universal, satuan_dasar, status_aktif, merek_id)
            VALUES (?, ?, ?, ?, ?, ?)");

        $stmtUpd = $pdo->prepare("UPDATE public.grup_produk SET 
            nama_grup = ?, barcode_universal = ?, satuan_dasar = ?, status_aktif = ?, merek_id = ?, diubah_pada = NOW()
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
                    $d['status_aktif'] ? 1 : 0,
                    $d['merek_id'] ?: null
                ]);
                $insertCount++;
            } elseif ($act === 'UPDATE') {
                $stmtUpd->execute([
                    $d['nama_grup'],
                    $d['barcode_universal'] ?: null,
                    $d['satuan_dasar'] ?: 'pcs',
                    $d['status_aktif'] ? 1 : 0,
                    $d['merek_id'] ?: null,
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

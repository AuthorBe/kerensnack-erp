<?php
declare(strict_types=1);

namespace App\Services\Import\Handlers;

use App\Services\Import\SmartReader;
use PDO;

class BrandImportHandler implements EntityImportHandlerInterface
{
    public function getEntityKey(): string
    {
        return 'brands';
    }

    public function getEntityLabel(): string
    {
        return 'Merek Produk (Brand)';
    }

    public function getRequiredPermission(): string
    {
        return 'master.products_manage';
    }

    public function getRequiredHeaderGroups(): array
    {
        return [
            ['nama_merek', 'merek', 'brand', 'nama', 'nama_brand']
        ];
    }

    public function getTemplateHeaders(): array
    {
        return [
            'Kode Merek',
            'Nama Merek'
        ];
    }

    public function getTemplateWidths(): array
    {
        return [22, 38];
    }

    public function getTemplateExamples(): array
    {
        return [
            ['KRN', 'KEREN SNACK'],
            ['MRK-002', 'SNACK NUSANTARA'],
            ['', 'RAJA KERUPUK'],
        ];
    }

    public function getTemplateNotes(): array
    {
        return [
            'Kode Merek unik (contoh: KRN, MRK-002). Kosongkan untuk kode otomatis.',
            'Nama Merek WAJIB diisi.'
        ];
    }

    public function getCurrentDataRows(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT kode_merek, nama_merek FROM public.merek ORDER BY kode_merek ASC");
        return $stmt->fetchAll(PDO::FETCH_NUM);
    }

    public function previewRows(array $rows, array $header, PDO $pdo, string $mode): array
    {
        $dbBrands = $pdo->query("SELECT * FROM public.merek")->fetchAll(PDO::FETCH_ASSOC);
        $dbByCode = [];
        $dbByName = [];
        foreach ($dbBrands as $b) {
            $dbByCode[strtolower(trim($b['kode_merek']))] = $b;
            $dbByName[strtolower(trim($b['nama_merek']))] = $b;
        }

        $previewList = [];
        $seenCodes = [];
        $processedDbIds = [];

        foreach ($rows as $idx => $row) {
            $lineNo = $idx + 2;
            $rowData = SmartReader::buildRowData($header, $row);

            $kode = (string)(SmartReader::getSmartValue($rowData, ['kode_merek', 'kode_brand', 'kd_merek', 'kd_brand', 'kode']) ?? '');
            $nama = (string)(SmartReader::getSmartValue($rowData, ['nama_merek', 'nama_brand', 'merek_dagang', 'brand_name', 'nama', 'merek', 'brand']) ?? '');
            $statusAktifRaw = SmartReader::getSmartValue($rowData, ['status_aktif', 'status', 'aktif']);

            if (empty($kode) && empty($nama)) {
                continue;
            }

            if (empty($nama)) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Nama Merek kosong pada baris {$lineNo}. Wajib diisi.",
                    'data' => ['kode_merek' => $kode, 'nama_merek' => '—']
                ];
                continue;
            }

            $statusAktif = SmartReader::normalizeBoolean($statusAktifRaw, true);

            if (!empty($kode)) {
                $kKey = strtolower(trim($kode));
                if (isset($seenCodes[$kKey])) {
                    $previewList[] = [
                        'action' => 'ERROR',
                        'error_msg' => "Duplikasi Kode Merek '{$kode}' pada baris {$lineNo}.",
                        'data' => ['kode_merek' => $kode, 'nama_merek' => $nama]
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
                'id'           => $dbRow['id'] ?? null,
                'kode_merek'   => !empty($kode) ? $kode : ($dbRow['kode_merek'] ?? ''),
                'nama_merek'   => $nama,
                'status_aktif' => $statusAktif,
            ];

            if ($dbRow) {
                $processedDbIds[] = $dbRow['id'];

                if (!empty($kode) && !SmartReader::isSimilarName($dbRow['nama_merek'], $nama)) {
                    $previewList[] = [
                        'action'       => 'INSERT',
                        'is_fatal'     => true,
                        'fatal_reason' => "Kode '{$kode}' di database adalah \"{$dbRow['nama_merek']}\", berbeda dengan \"{$nama}\". Dibuat sebagai merek baru.",
                        'data'         => $itemData,
                        'old_data'     => $dbRow
                    ];
                    continue;
                }

                $isDiff = trim($nama) !== trim((string)$dbRow['nama_merek'])
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
            foreach ($dbBrands as $b) {
                if (!in_array($b['id'], $processedDbIds, true)) {
                    $previewList[] = [
                        'action' => 'DELETE',
                        'data'   => $b
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

        $stmtMaxCode = $pdo->query("SELECT MAX(SUBSTRING(kode_merek FROM 5)::int) as max_seq FROM public.merek WHERE kode_merek ~ '^MRK-[0-9]+$'");
        $nextSeq = ((int)($stmtMaxCode->fetch(PDO::FETCH_ASSOC)['max_seq'] ?? 0)) + 1;

        $stmtIns = $pdo->prepare("INSERT INTO public.merek 
            (kode_merek, nama_merek, status_aktif, dibuat_pada, diubah_pada)
            VALUES (?, ?, ?, NOW(), NOW())");

        $stmtUpd = $pdo->prepare("UPDATE public.merek SET 
            nama_merek = ?, status_aktif = ?, diubah_pada = NOW()
            WHERE id = ?");

        $stmtDeactivate = $pdo->prepare("UPDATE public.merek SET status_aktif = FALSE, diubah_pada = NOW() WHERE id = ?");
        $stmtDel = $pdo->prepare("DELETE FROM public.merek WHERE id = ?");

        $stmtCheckUsage = $pdo->prepare("SELECT COUNT(*) FROM public.grup_produk WHERE merek_id = ?");

        foreach ($previewList as $row) {
            $act = $row['action'];
            $isFatal = !empty($row['is_fatal']);
            $d = $row['data'];

            if ($act === 'INSERT' || $isFatal) {
                $kode = $d['kode_merek'];
                if (empty($kode) || $isFatal) {
                    $kode = 'MRK-' . str_pad((string)$nextSeq++, 3, '0', STR_PAD_LEFT);
                }

                $stmtIns->execute([
                    $kode,
                    $d['nama_merek'],
                    $d['status_aktif'] ? 1 : 0
                ]);
                $insertCount++;
            } elseif ($act === 'UPDATE') {
                $stmtUpd->execute([
                    $d['nama_merek'],
                    $d['status_aktif'] ? 1 : 0,
                    $d['id']
                ]);
                $updateCount++;
            } elseif ($act === 'DELETE') {
                $bid = $d['id'];
                $stmtCheckUsage->execute([$bid]);
                $count = (int)$stmtCheckUsage->fetchColumn();

                if ($count > 0) {
                    $stmtDeactivate->execute([$bid]);
                    $deactivateCount++;
                } else {
                    $stmtDel->execute([$bid]);
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

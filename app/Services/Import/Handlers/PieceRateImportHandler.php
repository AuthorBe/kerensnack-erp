<?php
declare(strict_types=1);

namespace App\Services\Import\Handlers;

use App\Services\Import\SmartReader;
use PDO;

class PieceRateImportHandler implements EntityImportHandlerInterface
{
    public function getEntityKey(): string
    {
        return 'piece_rates';
    }

    public function getEntityLabel(): string
    {
        return 'Kelompok Upah Borongan';
    }

    public function getRequiredPermission(): string
    {
        return 'production.bom_manage';
    }

    public function getRequiredHeaderGroups(): array
    {
        return [
            ['nama_kelompok', 'nama', 'kelompok'],
            ['upah_per_bungkus', 'upah', 'tarif']
        ];
    }

    public function getTemplateHeaders(): array
    {
        return [
            'Nama Kelompok Borongan',
            'Upah per Bungkus (Rp)',
            'Keterangan / Deskripsi',
            'Status Aktif'
        ];
    }

    public function getTemplateWidths(): array
    {
        return [28, 24, 35, 16];
    }

    public function getTemplateExamples(): array
    {
        return [
            ['Kelompok 600', 600, 'Tarif repacking singkong dan makaroni 250gr', 'Aktif'],
            ['Kelompok 500', 500, 'Tarif repacking basreng dan kripik kaca 150gr', 'Aktif'],
            ['Kelompok 750', 750, 'Tarif repacking kemasan pouch premium standing zipper', 'Aktif'],
        ];
    }

    public function getTemplateNotes(): array
    {
        return [
            'Nama Kelompok Borongan bersifat unik (contoh: "Kelompok 600").',
            'Upah per Bungkus diisi nominal rupiah upah pekerja per 1 pcs kemasan jadi.',
            'Status Aktif diisi "Aktif" atau "Nonaktif".'
        ];
    }

    public function getCurrentDataRows(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT nama_kelompok, upah_per_bungkus, COALESCE(keterangan, '') as keterangan,
                                    CASE WHEN status_aktif THEN 'Aktif' ELSE 'Nonaktif' END as status_aktif_label
                             FROM public.kelompok_upah_borongan
                             ORDER BY nama_kelompok ASC");
        return $stmt->fetchAll(PDO::FETCH_NUM);
    }

    public function previewRows(array $rows, array $header, PDO $pdo, string $mode): array
    {
        $dbRates = $pdo->query("SELECT * FROM public.kelompok_upah_borongan")->fetchAll(PDO::FETCH_ASSOC);
        $dbByName = [];
        foreach ($dbRates as $r) {
            $dbByName[strtolower(trim($r['nama_kelompok']))] = $r;
        }

        $previewList = [];
        $seenNames = [];
        $processedDbIds = [];

        foreach ($rows as $idx => $row) {
            $lineNo = $idx + 2;
            $rowData = SmartReader::buildRowData($header, $row);

            $nama = (string)(SmartReader::getSmartValue($rowData, ['nama_kelompok_borongan', 'nama_kelompok', 'nama', 'kelompok']) ?? '');
            $upahRaw = SmartReader::getSmartValue($rowData, ['upah_per_bungkus', 'upah', 'tarif_per_bungkus', 'tarif']);
            $keterangan = (string)(SmartReader::getSmartValue($rowData, ['keterangan_deskripsi', 'keterangan', 'deskripsi']) ?? '');
            $statusAktifRaw = SmartReader::getSmartValue($rowData, ['status_aktif', 'status', 'aktif']);

            if (empty($nama)) {
                continue;
            }

            $nKey = strtolower(trim($nama));
            if (isset($seenNames[$nKey])) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Duplikasi Nama Kelompok '{$nama}' di dalam file Excel pada baris {$lineNo}.",
                    'data' => ['nama_kelompok' => $nama, 'upah_per_bungkus' => $upahRaw]
                ];
                continue;
            }
            $seenNames[$nKey] = true;

            $upah = SmartReader::normalizeNumeric($upahRaw, 0.0);
            if ($upah <= 0) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Upah per bungkus harus lebih besar dari 0 pada baris {$lineNo}.",
                    'data' => ['nama_kelompok' => $nama, 'upah_per_bungkus' => $upahRaw]
                ];
                continue;
            }

            $statusAktif = SmartReader::normalizeBoolean($statusAktifRaw, true);

            $dbRow = $dbByName[$nKey] ?? null;

            $itemData = [
                'id'                => $dbRow['id'] ?? null,
                'nama_kelompok'     => $nama,
                'upah_per_bungkus'  => $upah,
                'keterangan'        => $keterangan,
                'status_aktif'      => $statusAktif,
            ];

            if ($dbRow) {
                $processedDbIds[] = $dbRow['id'];
                $isDiff = abs($upah - (float)$dbRow['upah_per_bungkus']) > 0.01
                    || trim($keterangan) !== trim((string)($dbRow['keterangan'] ?? ''))
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
            foreach ($dbRates as $r) {
                if (!in_array($r['id'], $processedDbIds, true)) {
                    $previewList[] = [
                        'action' => 'DELETE',
                        'data'   => $r
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

        $stmtIns = $pdo->prepare("INSERT INTO public.kelompok_upah_borongan 
            (nama_kelompok, upah_per_bungkus, keterangan, status_aktif)
            VALUES (?, ?, ?, ?)");

        $stmtUpd = $pdo->prepare("UPDATE public.kelompok_upah_borongan SET 
            upah_per_bungkus = ?, keterangan = ?, status_aktif = ?, diubah_pada = NOW()
            WHERE id = ?");

        $stmtDeactivate = $pdo->prepare("UPDATE public.kelompok_upah_borongan SET status_aktif = FALSE, diubah_pada = NOW() WHERE id = ?");
        $stmtDel = $pdo->prepare("DELETE FROM public.kelompok_upah_borongan WHERE id = ?");
        $stmtCheckUsage = $pdo->prepare("SELECT COUNT(*) FROM public.item WHERE kelompok_borongan_id = ?");

        foreach ($previewList as $row) {
            $act = $row['action'];
            $d = $row['data'];

            if ($act === 'INSERT') {
                $stmtIns->execute([
                    $d['nama_kelompok'],
                    $d['upah_per_bungkus'],
                    $d['keterangan'] ?: null,
                    $d['status_aktif'] ? 1 : 0
                ]);
                $insertCount++;
            } elseif ($act === 'UPDATE') {
                $stmtUpd->execute([
                    $d['upah_per_bungkus'],
                    $d['keterangan'] ?: null,
                    $d['status_aktif'] ? 1 : 0,
                    $d['id']
                ]);
                $updateCount++;
            } elseif ($act === 'DELETE') {
                $kid = $d['id'];
                $stmtCheckUsage->execute([$kid]);
                $count = (int)$stmtCheckUsage->fetchColumn();

                if ($count > 0) {
                    $stmtDeactivate->execute([$kid]);
                    $deactivateCount++;
                } else {
                    $stmtDel->execute([$kid]);
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

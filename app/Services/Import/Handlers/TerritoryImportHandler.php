<?php
declare(strict_types=1);

namespace App\Services\Import\Handlers;

use App\Services\Import\SmartReader;
use PDO;

class TerritoryImportHandler implements EntityImportHandlerInterface
{
    public function getEntityKey(): string
    {
        return 'territories';
    }

    public function getEntityLabel(): string
    {
        return 'Wilayah & Rute Distribusi';
    }

    public function getRequiredPermission(): string
    {
        return 'master.customers_manage';
    }

    public function getRequiredHeaderGroups(): array
    {
        return [
            ['nama_wilayah', 'wilayah', 'nama_rute', 'rute'],
        ];
    }

    public function getTemplateHeaders(): array
    {
        return [
            'Kode Rute',
            'Nama Wilayah / Rute',
            'Provinsi',
            'Kota / Kabupaten',
            'Cakupan Sub-Wilayah / Kecamatan',
            'Status Aktif'
        ];
    }

    public function getTemplateWidths(): array
    {
        return [20, 30, 20, 24, 38, 16];
    }

    public function getTemplateExamples(): array
    {
        return [
            ['RUTE-TNG-TIMUR', 'Tangerang Timur & Cipondoh', 'Banten', 'Kota Tangerang', 'Cipondoh, Poris, Pinang, Ciledug', 'Aktif'],
            ['RUTE-JAKBAR-1', 'Jakarta Barat - Cengkareng', 'DKI Jakarta', 'Kota Jakarta Barat', 'Cengkareng, Kalideres, Rawa Buaya', 'Aktif'],
            ['', 'Tangerang Selatan - BSD Serpong', 'Banten', 'Kota Tangerang Selatan', 'Serpong, BSD City, Cisauk', 'Aktif'],
        ];
    }

    public function getTemplateNotes(): array
    {
        return [
            'Kode Rute unik (contoh: RUTE-TNG-TIMUR). Kosongkan untuk penomoran otomatis.',
            'Nama Wilayah / Rute WAJIB diisi.',
            'Provinsi & Kota/Kabupaten dapat disesuaikan sesuai area distribusi usaha.',
            'Status Aktif diisi "Aktif" atau "Nonaktif".'
        ];
    }

    public function getCurrentDataRows(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT kode_rute, nama_wilayah, provinsi, kota_kabupaten, COALESCE(sub_wilayah, '') as sub_wilayah,
                                    CASE WHEN status_aktif THEN 'Aktif' ELSE 'Nonaktif' END as status_aktif_label
                             FROM public.wilayah
                             ORDER BY kode_rute ASC");
        return $stmt->fetchAll(PDO::FETCH_NUM);
    }

    public function previewRows(array $rows, array $header, PDO $pdo, string $mode): array
    {
        $dbTerritories = $pdo->query("SELECT * FROM public.wilayah")->fetchAll(PDO::FETCH_ASSOC);
        $dbByCode = [];
        $dbByName = [];
        foreach ($dbTerritories as $t) {
            $dbByCode[strtolower(trim($t['kode_rute']))] = $t;
            $dbByName[strtolower(trim($t['nama_wilayah']))] = $t;
        }

        $previewList = [];
        $seenCodes = [];
        $processedDbIds = [];

        foreach ($rows as $idx => $row) {
            $lineNo = $idx + 2;
            $rowData = SmartReader::buildRowData($header, $row);

            $kode = (string)(SmartReader::getSmartValue($rowData, ['kode_rute', 'kode', 'kd_rute', 'rute']) ?? '');
            $nama = (string)(SmartReader::getSmartValue($rowData, ['nama_wilayah', 'wilayah', 'nama_rute', 'nama']) ?? '');
            $provinsi = (string)(SmartReader::getSmartValue($rowData, ['provinsi', 'prov']) ?? 'Banten');
            $kota = (string)(SmartReader::getSmartValue($rowData, ['kota_kabupaten', 'kota', 'kabupaten']) ?? 'Kota Tangerang');
            $subWilayah = (string)(SmartReader::getSmartValue($rowData, ['sub_wilayah', 'cakupan', 'kecamatan']) ?? '');
            $statusAktifRaw = SmartReader::getSmartValue($rowData, ['status_aktif', 'status', 'aktif']);

            if (empty($kode) && empty($nama)) {
                continue;
            }

            if (empty($nama)) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Nama Wilayah kosong pada baris {$lineNo}. Wajib diisi.",
                    'data' => ['kode_rute' => $kode, 'nama_wilayah' => '—']
                ];
                continue;
            }

            $statusAktif = SmartReader::normalizeBoolean($statusAktifRaw, true);

            if (!empty($kode)) {
                $kKey = strtolower(trim($kode));
                if (isset($seenCodes[$kKey])) {
                    $previewList[] = [
                        'action' => 'ERROR',
                        'error_msg' => "Duplikasi Kode Rute '{$kode}' pada baris {$lineNo}.",
                        'data' => ['kode_rute' => $kode, 'nama_wilayah' => $nama]
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
                'id'             => $dbRow['id'] ?? null,
                'kode_rute'      => !empty($kode) ? $kode : ($dbRow['kode_rute'] ?? ''),
                'nama_wilayah'   => $nama,
                'provinsi'       => $provinsi ?: 'Banten',
                'kota_kabupaten' => $kota ?: 'Kota Tangerang',
                'sub_wilayah'    => $subWilayah,
                'status_aktif'   => $statusAktif,
            ];

            if ($dbRow) {
                $processedDbIds[] = $dbRow['id'];

                if (!empty($kode) && !SmartReader::isSimilarName($dbRow['nama_wilayah'], $nama)) {
                    $previewList[] = [
                        'action'       => 'INSERT',
                        'is_fatal'     => true,
                        'fatal_reason' => "Kode '{$kode}' di database adalah \"{$dbRow['nama_wilayah']}\", berbeda dengan \"{$nama}\". Dibuat sebagai entri wilayah baru.",
                        'data'         => $itemData,
                        'old_data'     => $dbRow
                    ];
                    continue;
                }

                $isDiff = trim($nama) !== trim((string)$dbRow['nama_wilayah'])
                    || trim($provinsi) !== trim((string)$dbRow['provinsi'])
                    || trim($kota) !== trim((string)$dbRow['kota_kabupaten'])
                    || trim($subWilayah) !== trim((string)($dbRow['sub_wilayah'] ?? ''))
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
            foreach ($dbTerritories as $t) {
                if (!in_array($t['id'], $processedDbIds, true)) {
                    $previewList[] = [
                        'action' => 'DELETE',
                        'data'   => $t
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

        $stmtMaxCode = $pdo->query("SELECT MAX(SUBSTRING(kode_rute FROM 6)::int) as max_seq FROM public.wilayah WHERE kode_rute ~ '^RUTE-[0-9]+$'");
        $nextSeq = ((int)($stmtMaxCode->fetch(PDO::FETCH_ASSOC)['max_seq'] ?? 0)) + 1;

        $stmtIns = $pdo->prepare("INSERT INTO public.wilayah (kode_rute, nama_wilayah, provinsi, kota_kabupaten, sub_wilayah, status_aktif) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtUpd = $pdo->prepare("UPDATE public.wilayah SET nama_wilayah = ?, provinsi = ?, kota_kabupaten = ?, sub_wilayah = ?, status_aktif = ?, diubah_pada = NOW() WHERE id = ?");
        $stmtDeactivate = $pdo->prepare("UPDATE public.wilayah SET status_aktif = FALSE, diubah_pada = NOW() WHERE id = ?");
        $stmtDel = $pdo->prepare("DELETE FROM public.wilayah WHERE id = ?");

        $stmtCheckUsage = $pdo->prepare("SELECT 
            (SELECT COUNT(*) FROM public.pelanggan WHERE wilayah_id = ?) +
            (SELECT COUNT(*) FROM public.pemasok WHERE wilayah_id = ?) AS total_usage");

        foreach ($previewList as $row) {
            $act = $row['action'];
            $isFatal = !empty($row['is_fatal']);
            $d = $row['data'];

            if ($act === 'INSERT' || $isFatal) {
                $kode = $d['kode_rute'];
                if (empty($kode) || $isFatal) {
                    $kode = 'RUTE-' . str_pad((string)$nextSeq++, 3, '0', STR_PAD_LEFT);
                }

                $stmtIns->execute([
                    $kode,
                    $d['nama_wilayah'],
                    $d['provinsi'],
                    $d['kota_kabupaten'],
                    $d['sub_wilayah'] ?: null,
                    $d['status_aktif'] ? 1 : 0
                ]);
                $insertCount++;
            } elseif ($act === 'UPDATE') {
                $stmtUpd->execute([
                    $d['nama_wilayah'],
                    $d['provinsi'],
                    $d['kota_kabupaten'],
                    $d['sub_wilayah'] ?: null,
                    $d['status_aktif'] ? 1 : 0,
                    $d['id']
                ]);
                $updateCount++;
            } elseif ($act === 'DELETE') {
                $tid = $d['id'];
                $stmtCheckUsage->execute([$tid, $tid]);
                $usage = (int)$stmtCheckUsage->fetchColumn();

                if ($usage > 0) {
                    $stmtDeactivate->execute([$tid]);
                    $deactivateCount++;
                } else {
                    $stmtDel->execute([$tid]);
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

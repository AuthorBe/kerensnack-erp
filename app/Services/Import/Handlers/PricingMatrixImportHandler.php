<?php
declare(strict_types=1);

namespace App\Services\Import\Handlers;

use App\Services\Import\SmartReader;
use PDO;

class PricingMatrixImportHandler implements EntityImportHandlerInterface
{
    public function getEntityKey(): string
    {
        return 'pricing_matrix';
    }

    public function getEntityLabel(): string
    {
        return 'Matriks Harga Jual 30 Level';
    }

    public function getRequiredPermission(): string
    {
        return 'master.pricing_manage';
    }

    public function getRequiredHeaderGroups(): array
    {
        return [
            ['grup_produk', 'kode_grup', 'grup'],
            ['level_harga', 'level', 'nomor_level'],
            ['harga_jual_pcs', 'harga_jual', 'harga']
        ];
    }

    public function getTemplateHeaders(): array
    {
        return [
            'Kode Grup Produk',
            'Nama Grup Produk',
            'Level Nomor (1-30)',
            'Nama Level Acuan',
            'Harga Jual per Pcs (Rp)'
        ];
    }

    public function getTemplateWidths(): array
    {
        return [22, 32, 18, 32, 24];
    }

    public function getTemplateExamples(): array
    {
        return [
            ['GRP-SINGKONG-250', 'Keripik Singkong 250gr', 1, 'Level 1 - Ritel Standar (POS)', 15000],
            ['GRP-SINGKONG-250', 'Keripik Singkong 250gr', 5, 'Level 5 - Konsinyasi Rak Toko', 12000],
            ['GRP-SINGKONG-250', 'Keripik Singkong 250gr', 8, 'Level 8 - Grosir Mitra Warung', 10500],
            ['GRP-BASRENG-150', 'Basreng Pedas Daun Jeruk 150gr', 1, 'Level 1 - Ritel Standar (POS)', 13000],
            ['GRP-BASRENG-150', 'Basreng Pedas Daun Jeruk 150gr', 5, 'Level 5 - Konsinyasi Rak Toko', 10500],
        ];
    }

    public function getTemplateNotes(): array
    {
        return [
            'Matriks Harga mengatur harga jual per pcs (bungkus) untuk setiap Grup Produk di Level 1 s/d 30.',
            'Kode Grup Produk atau Nama Grup Produk WAJIB sesuai dengan data Grup Produk di ERP.',
            'Level Nomor wajib bernilai 1 sampai 30.',
            'Harga Jual per Pcs berupa angka nominal rupiah murni (mendukung nominal kecil seperti 300, 600, maupun ribuan seperti 12000, 15000).'
        ];
    }

    public function getCurrentDataRows(PDO $pdo): array
    {
        $sql = "SELECT g.kode_grup, g.nama_grup, p.level_harga, COALESCE(m.nama_level, 'Level ' || p.level_harga) as nama_level, p.harga_jual_pcs
                FROM public.grup_produk_harga_level p
                JOIN public.grup_produk g ON g.id = p.grup_produk_id
                LEFT JOIN public.master_level_harga m ON m.level_nomor = p.level_harga
                ORDER BY g.kode_grup ASC, p.level_harga ASC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_NUM);
    }

    public function previewRows(array $rows, array $header, PDO $pdo, string $mode): array
    {
        $groups = $pdo->query("SELECT id, kode_grup, nama_grup FROM public.grup_produk")->fetchAll(PDO::FETCH_ASSOC);
        $groupMap = [];
        foreach ($groups as $g) {
            $groupMap[strtolower(trim($g['kode_grup']))] = $g;
            $groupMap[strtolower(trim($g['nama_grup']))] = $g;
        }

        $masterLevels = $pdo->query("SELECT level_nomor, nama_level FROM public.master_level_harga")->fetchAll(PDO::FETCH_ASSOC);
        $levelMap = [];
        foreach ($masterLevels as $l) {
            $levelMap[(int)$l['level_nomor']] = $l['nama_level'];
        }

        $dbPrices = $pdo->query("SELECT p.*, g.kode_grup, g.nama_grup, COALESCE(m.nama_level, 'Level ' || p.level_harga) as nama_level 
                                 FROM public.grup_produk_harga_level p 
                                 JOIN public.grup_produk g ON g.id = p.grup_produk_id
                                 LEFT JOIN public.master_level_harga m ON m.level_nomor = p.level_harga")->fetchAll(PDO::FETCH_ASSOC);
        $dbPriceMap = [];
        foreach ($dbPrices as $pr) {
            $key = $pr['grup_produk_id'] . '_' . $pr['level_harga'];
            $dbPriceMap[$key] = $pr;
        }

        $previewList = [];
        $seenPairs = [];
        $processedKeys = [];

        foreach ($rows as $idx => $row) {
            $lineNo = $idx + 2;
            $rowData = SmartReader::buildRowData($header, $row);

            $grupRaw = (string)(SmartReader::getSmartValue($rowData, ['kode_grup_produk', 'kode_grup', 'nama_grup_produk', 'nama_grup', 'grup']) ?? '');
            $levelRaw = SmartReader::getSmartValue($rowData, ['level_nomor', 'level_harga', 'level', 'nomor_level']);
            $namaLevel = (string)(SmartReader::getSmartValue($rowData, ['nama_level_acuan', 'nama_level_harga', 'nama_level']) ?? '');
            $hargaRaw = SmartReader::getSmartValue($rowData, ['harga_jual_per_pcs', 'harga_jual_pcs', 'harga_jual', 'harga']);

            if (empty($grupRaw) && empty($levelRaw)) {
                continue;
            }

            $gKey = strtolower(trim($grupRaw));
            if (!isset($groupMap[$gKey])) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Grup Produk '{$grupRaw}' pada baris {$lineNo} tidak ditemukan di sistem.",
                    'data' => ['grup' => $grupRaw, 'level' => $levelRaw, 'harga' => $hargaRaw]
                ];
                continue;
            }
            $targetGroup = $groupMap[$gKey];
            $groupId = $targetGroup['id'];

            $level = (int)SmartReader::normalizeNumeric($levelRaw, 0.0);
            if ($level < 1 || $level > 30) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Level Harga '{$levelRaw}' tidak valid pada baris {$lineNo}. Harus antara 1 dan 30.",
                    'data' => ['grup' => $targetGroup['nama_grup'], 'level' => $levelRaw, 'harga' => $hargaRaw]
                ];
                continue;
            }

            $harga = SmartReader::normalizeNumeric($hargaRaw, 0.0);
            if ($harga <= 0) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Harga jual harus lebih besar dari 0 pada baris {$lineNo}.",
                    'data' => ['grup' => $targetGroup['nama_grup'], 'level' => $level, 'harga' => $harga]
                ];
                continue;
            }

            $pairKey = $groupId . '_' . $level;
            if (isset($seenPairs[$pairKey])) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Duplikasi pasangan Grup '{$targetGroup['nama_grup']}' dan Level {$level} di dalam file Excel pada baris {$lineNo}.",
                    'data' => ['grup' => $targetGroup['nama_grup'], 'level' => $level, 'harga' => $harga]
                ];
                continue;
            }
            $seenPairs[$pairKey] = true;

            $levelNameResolved = !empty($namaLevel) ? $namaLevel : ($levelMap[$level] ?? "Level {$level}");

            $dbRow = $dbPriceMap[$pairKey] ?? null;

            $itemData = [
                'id'              => $dbRow['id'] ?? null,
                'grup_produk_id'  => $groupId,
                'kode_grup'       => $targetGroup['kode_grup'],
                'nama_grup'       => $targetGroup['nama_grup'],
                'level_harga'     => $level,
                'nama_level'      => $levelNameResolved,
                'harga_jual_pcs'  => $harga,
            ];

            if ($dbRow) {
                $processedKeys[] = $pairKey;
                $isDiff = abs($harga - (float)$dbRow['harga_jual_pcs']) > 0.01;

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
            foreach ($dbPrices as $pr) {
                $key = $pr['grup_produk_id'] . '_' . $pr['level_harga'];
                if (!in_array($key, $processedKeys, true)) {
                    $previewList[] = [
                        'action' => 'DELETE',
                        'data'   => $pr
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

        $stmtUpsert = $pdo->prepare("INSERT INTO public.grup_produk_harga_level 
            (grup_produk_id, level_harga, harga_jual_pcs, diubah_pada)
            VALUES (?, ?, ?, NOW())
            ON CONFLICT (grup_produk_id, level_harga) DO UPDATE SET 
                harga_jual_pcs = EXCLUDED.harga_jual_pcs,
                diubah_pada = NOW()");

        $stmtDel = $pdo->prepare("DELETE FROM public.grup_produk_harga_level WHERE id = ?");

        foreach ($previewList as $row) {
            $act = $row['action'];
            $d = $row['data'];

            if ($act === 'INSERT' || $act === 'UPDATE') {
                $stmtUpsert->execute([
                    $d['grup_produk_id'],
                    $d['level_harga'],
                    $d['harga_jual_pcs']
                ]);
                if ($act === 'INSERT') {
                    $insertCount++;
                } else {
                    $updateCount++;
                }
            } elseif ($act === 'DELETE') {
                $stmtDel->execute([$d['id']]);
                $deleteCount++;
            }
        }

        return [
            'insert'     => $insertCount,
            'update'     => $updateCount,
            'delete'     => $deleteCount,
            'deactivate' => 0
        ];
    }
}

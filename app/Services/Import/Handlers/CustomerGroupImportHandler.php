<?php
declare(strict_types=1);

namespace App\Services\Import\Handlers;

use App\Services\Import\SmartReader;
use Database;
use PDO;

class CustomerGroupImportHandler implements EntityImportHandlerInterface
{
    public function getEntityKey(): string
    {
        return 'customer_groups';
    }

    public function getEntityLabel(): string
    {
        return 'Grup Pelanggan';
    }

    public function getRequiredPermission(): string
    {
        return 'master.customers_manage';
    }

    public function getRequiredHeaderGroups(): array
    {
        return [
            ['nama_grup', 'nama', 'grup']
        ];
    }

    private function getActiveBrands(?PDO $pdo = null): array
    {
        try {
            $db = $pdo ?: Database::getConnection();
            return $db->query("SELECT id, kode_merek, nama_merek FROM public.merek WHERE status_aktif = TRUE ORDER BY kode_merek ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [
                ['id' => '00000000-0000-0000-0000-000000000001', 'kode_merek' => 'KRN', 'nama_merek' => 'KEREN SNACK']
            ];
        }
    }

    public function getTemplateHeaders(): array
    {
        $headers = ['Kode Grup', 'Nama Grup'];
        $brands = $this->getActiveBrands();
        foreach ($brands as $b) {
            $headers[] = 'Level ' . $b['nama_merek'];
            $headers[] = 'Diskon % ' . $b['nama_merek'];
            $headers[] = 'Diskon Rp ' . $b['nama_merek'];
        }
        $headers[] = 'Status Aktif';
        return $headers;
    }

    public function getTemplateWidths(): array
    {
        $widths = [18, 30];
        $brands = $this->getActiveBrands();
        foreach ($brands as $b) {
            $widths[] = 22;
            $widths[] = 18;
            $widths[] = 20;
        }
        $widths[] = 16;
        return $widths;
    }

    public function getTemplateExamples(): array
    {
        $brands = $this->getActiveBrands();
        $ex1 = ['GRP-G01', 'KS 14.800 | CQ - | CM -'];
        $ex2 = ['GRP-G02', 'KS 29.900 | CQ 45.900 | CM -'];
        $ex3 = ['GRP-G05', 'KS 16.800 | CQ 16.800 | CM 24.800'];

        foreach ($brands as $idx => $b) {
            if ($idx === 0) {
                $ex1[] = 18; $ex1[] = 0; $ex1[] = 0;
                $ex2[] = 28; $ex2[] = 0; $ex2[] = 0;
                $ex3[] = 22; $ex3[] = 5; $ex3[] = 0;
            } elseif ($idx === 1) {
                $ex1[] = 'Tidak dijual'; $ex1[] = 0; $ex1[] = 0;
                $ex2[] = 18; $ex2[] = 0; $ex2[] = 0;
                $ex3[] = 10; $ex3[] = 0; $ex3[] = 0;
            } else {
                $ex1[] = 'Tidak dijual'; $ex1[] = 0; $ex1[] = 0;
                $ex2[] = 'Tidak dijual'; $ex2[] = 0; $ex2[] = 0;
                $ex3[] = 5; $ex3[] = 0; $ex3[] = 0;
            }
        }
        $ex1[] = 'Aktif';
        $ex2[] = 'Aktif';
        $ex3[] = 'Aktif';

        return [$ex1, $ex2, $ex3];
    }

    public function getTemplateNotes(): array
    {
        return [
            'Kode Grup bersifat unik (Contoh: GRP-G01). Jika dikosongkan pada data baru, sistem akan men-generate otomatis.',
            'Kolom Level Merek diisi angka Level 1 sampai 30, atau diisi "Tidak dijual" / "-" jika merek tersebut tidak dialokasikan untuk grup ini.',
            'Diskon % bernilai 0 - 100, Diskon Rp berupa angka nominal rupiah.',
            'Status Aktif diisi "Aktif" atau "Nonaktif".'
        ];
    }

    public function getCurrentDataRows(PDO $pdo): array
    {
        $brands = $this->getActiveBrands($pdo);
        $groups = $pdo->query("SELECT id, kode_grup, nama_grup, default_level_harga, diskon_persen_default, diskon_nominal_default,
                                      CASE WHEN status_aktif THEN 'Aktif' ELSE 'Nonaktif' END as status_aktif_label
                               FROM public.grup_pelanggan
                               ORDER BY kode_grup ASC")->fetchAll(PDO::FETCH_ASSOC);

        $brandLevelsRaw = $pdo->query("SELECT grup_pelanggan_id, merek_id, level_harga, diskon_persen, diskon_nominal, is_dijual FROM public.grup_pelanggan_level_merek")->fetchAll(PDO::FETCH_ASSOC);
        $blMap = [];
        foreach ($brandLevelsRaw as $bl) {
            $blMap[$bl['grup_pelanggan_id']][$bl['merek_id']] = $bl;
        }

        $rows = [];
        foreach ($groups as $g) {
            $r = [$g['kode_grup'], $g['nama_grup']];
            foreach ($brands as $b) {
                $cfg = $blMap[$g['id']][$b['id']] ?? null;
                if ($cfg && $cfg['is_dijual'] && $cfg['level_harga']) {
                    $r[] = (int)$cfg['level_harga'];
                    $r[] = (float)$cfg['diskon_persen'];
                    $r[] = (float)$cfg['diskon_nominal'];
                } else {
                    $r[] = 'Tidak dijual';
                    $r[] = 0;
                    $r[] = 0;
                }
            }
            $r[] = $g['status_aktif_label'];
            $rows[] = $r;
        }

        return $rows;
    }

    public function previewRows(array $rows, array $header, PDO $pdo, string $mode): array
    {
        $brands = $this->getActiveBrands($pdo);
        $dbGroups = $pdo->query("SELECT * FROM public.grup_pelanggan")->fetchAll(PDO::FETCH_ASSOC);
        $dbByCode = [];
        $dbByName = [];
        foreach ($dbGroups as $g) {
            $dbByCode[strtolower(trim($g['kode_grup']))] = $g;
            $dbByName[strtolower(trim($g['nama_grup']))] = $g;
        }

        $rawBrandLevels = $pdo->query("SELECT * FROM public.grup_pelanggan_level_merek")->fetchAll(PDO::FETCH_ASSOC);
        $dbBrandLevelsMap = [];
        foreach ($rawBrandLevels as $rbl) {
            $dbBrandLevelsMap[$rbl['grup_pelanggan_id']][$rbl['merek_id']] = $rbl;
        }

        $previewList = [];
        $seenCodes = [];
        $processedDbIds = [];

        foreach ($rows as $idx => $row) {
            $lineNo = $idx + 2;
            $rowData = SmartReader::buildRowData($header, $row);

            $kode = (string)(SmartReader::getSmartValue($rowData, ['kode_grup', 'kode', 'kd_grup', 'kode_lengkap']) ?? '');
            $nama = (string)(SmartReader::getSmartValue($rowData, ['nama_grup', 'nama', 'grup', 'nama_grup_pelanggan']) ?? '');
            $statusAktifRaw = SmartReader::getSmartValue($rowData, ['status_aktif', 'status', 'aktif']);

            if (empty($kode) && empty($nama)) {
                continue;
            }

            if (empty($nama)) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Nama Grup kosong pada baris {$lineNo}. Wajib diisi.",
                    'data' => ['kode_grup' => $kode, 'nama_grup' => '—']
                ];
                continue;
            }

            // Parse brand levels
            $brandLevels = [];
            $firstValidLevel = 1;

            foreach ($brands as $b) {
                $bId = $b['id'];
                $bName = strtolower($b['nama_merek']);
                $bCode = strtolower($b['kode_merek']);

                // Find level column
                $levelVal = SmartReader::getSmartValue($rowData, [
                    'level_' . $bName, 'level_' . $bCode,
                    'level_harga_' . $bName, 'level_harga_' . $bCode,
                    'level ' . $bName, 'level ' . $bCode,
                    $bName, $bCode
                ]);

                // Find discount % column
                $discPVal = SmartReader::getSmartValue($rowData, [
                    'diskon_%_' . $bName, 'diskon_%_' . $bCode,
                    'diskon_persen_' . $bName, 'diskon_persen_' . $bCode,
                    'diskon % ' . $bName, 'diskon % ' . $bCode
                ]);

                // Find discount Rp column
                $discNVal = SmartReader::getSmartValue($rowData, [
                    'diskon_rp_' . $bName, 'diskon_rp_' . $bCode,
                    'diskon_nominal_' . $bName, 'diskon_nominal_' . $bCode,
                    'diskon rp ' . $bName, 'diskon rp ' . $bCode
                ]);

                $isDijual = true;
                $level = null;

                if ($levelVal !== null) {
                    $levelStr = strtolower(trim((string)$levelVal));
                    if ($levelStr === '' || str_contains($levelStr, 'tidak') || str_contains($levelStr, 'non') || str_contains($levelStr, 'bukan') || $levelStr === '-') {
                        $isDijual = false;
                        $level = null;
                    } elseif (is_numeric($levelStr)) {
                        $levelNum = (int)$levelStr;
                        if ($levelNum >= 1 && $levelNum <= 30) {
                            $isDijual = true;
                            $level = $levelNum;
                            $firstValidLevel = $levelNum;
                        } else {
                            $previewList[] = [
                                'action' => 'ERROR',
                                'error_msg' => "Level Merek {$b['nama_merek']} '{$levelVal}' tidak valid pada baris {$lineNo}. Harus angka 1-30 atau 'Tidak dijual'.",
                                'data' => ['kode_grup' => $kode, 'nama_grup' => $nama]
                            ];
                            continue 2;
                        }
                    }
                } else {
                    $globalLvl = SmartReader::getSmartValue($rowData, ['default_level_harga', 'level_harga', 'level']);
                    if ($globalLvl !== null && is_numeric($globalLvl)) {
                        $level = max(1, min(30, (int)$globalLvl));
                        $isDijual = true;
                        $firstValidLevel = $level;
                    } else {
                        $isDijual = true;
                        $level = 1;
                    }
                }

                $discP = SmartReader::normalizeNumeric($discPVal, 0.0);
                $discN = SmartReader::normalizeNumeric($discNVal, 0.0);

                $brandLevels[$bId] = [
                    'merek_id' => $bId,
                    'nama_merek' => $b['nama_merek'],
                    'kode_merek' => $b['kode_merek'],
                    'is_dijual' => $isDijual,
                    'level_harga' => $level,
                    'diskon_persen' => $discP,
                    'diskon_nominal' => $discN
                ];
            }

            $statusAktif = SmartReader::normalizeBoolean($statusAktifRaw, true);

            if (!empty($kode)) {
                $kKey = strtolower(trim($kode));
                if (isset($seenCodes[$kKey])) {
                    $previewList[] = [
                        'action' => 'ERROR',
                        'error_msg' => "Duplikasi Kode Grup '{$kode}' pada baris {$lineNo}.",
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
                'id'                     => $dbRow['id'] ?? null,
                'kode_grup'              => !empty($kode) ? $kode : ($dbRow['kode_grup'] ?? ''),
                'nama_grup'              => $nama,
                'default_level_harga'    => $firstValidLevel,
                'diskon_persen_default'  => 0.0,
                'diskon_nominal_default' => 0.0,
                'status_aktif'           => $statusAktif,
                'brand_levels'           => $brandLevels
            ];

            if ($dbRow) {
                $processedDbIds[] = $dbRow['id'];

                if (!empty($kode) && !SmartReader::isSimilarName($dbRow['nama_grup'], $nama)) {
                    $previewList[] = [
                        'action'       => 'INSERT',
                        'is_fatal'     => true,
                        'fatal_reason' => "Kode '{$kode}' terdaftar atas grup \"{$dbRow['nama_grup']}\", berbeda dengan \"{$nama}\". Dibuat sebagai grup baru ber-kode otomatis.",
                        'data'         => $itemData,
                        'old_data'     => $dbRow
                    ];
                    continue;
                }

                $existingBL = $dbBrandLevelsMap[$dbRow['id']] ?? [];
                $isDiff = trim($nama) !== trim((string)$dbRow['nama_grup'])
                    || $statusAktif !== (bool)$dbRow['status_aktif'];

                foreach ($brandLevels as $bId => $bl) {
                    $ex = $existingBL[$bId] ?? null;
                    if (!$ex) {
                        $isDiff = true;
                        break;
                    }
                    if ((bool)$ex['is_dijual'] !== (bool)$bl['is_dijual']
                        || (int)($ex['level_harga'] ?? 0) !== (int)($bl['level_harga'] ?? 0)
                        || abs((float)($ex['diskon_persen'] ?? 0) - (float)$bl['diskon_persen']) > 0.01
                        || abs((float)($ex['diskon_nominal'] ?? 0) - (float)$bl['diskon_nominal']) > 0.01) {
                        $isDiff = true;
                        break;
                    }
                }

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

        $stmtMaxCode = $pdo->query("SELECT MAX(SUBSTRING(kode_grup FROM 5)::int) as max_seq FROM public.grup_pelanggan WHERE kode_grup ~ '^GRP-[0-9]+$'");
        $nextSeq = ((int)($stmtMaxCode->fetch(PDO::FETCH_ASSOC)['max_seq'] ?? 0)) + 1;

        $stmtIns = $pdo->prepare("INSERT INTO public.grup_pelanggan 
            (kode_grup, nama_grup, default_level_harga, diskon_persen_default, diskon_nominal_default, status_aktif)
            VALUES (?, ?, ?, ?, ?, ?) RETURNING id");

        $stmtUpd = $pdo->prepare("UPDATE public.grup_pelanggan SET 
            nama_grup = ?, default_level_harga = ?, status_aktif = ?, diubah_pada = NOW()
            WHERE id = ?");

        $stmtBrandLevel = $pdo->prepare("
            INSERT INTO public.grup_pelanggan_level_merek (
                grup_pelanggan_id, merek_id, level_harga, diskon_persen, diskon_nominal, is_dijual, diubah_pada
            ) VALUES (
                ?, ?, ?, ?, ?, ?, NOW()
            )
            ON CONFLICT (grup_pelanggan_id, merek_id) DO UPDATE SET
                level_harga = EXCLUDED.level_harga,
                diskon_persen = EXCLUDED.diskon_persen,
                diskon_nominal = EXCLUDED.diskon_nominal,
                is_dijual = EXCLUDED.is_dijual,
                diubah_pada = NOW()
        ");

        $stmtDeactivate = $pdo->prepare("UPDATE public.grup_pelanggan SET status_aktif = FALSE, diubah_pada = NOW() WHERE id = ?");
        $stmtDel = $pdo->prepare("DELETE FROM public.grup_pelanggan WHERE id = ?");
        $stmtCheckUsage = $pdo->prepare("SELECT COUNT(*) FROM public.pelanggan WHERE grup_pelanggan_id = ?");

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
                    $d['default_level_harga'],
                    $d['diskon_persen_default'] ?? 0,
                    $d['diskon_nominal_default'] ?? 0,
                    $d['status_aktif'] ? 1 : 0
                ]);
                $groupId = $stmtIns->fetchColumn();

                if ($groupId && !empty($d['brand_levels'])) {
                    foreach ($d['brand_levels'] as $bl) {
                        $stmtBrandLevel->execute([
                            $groupId,
                            $bl['merek_id'],
                            $bl['is_dijual'] ? $bl['level_harga'] : null,
                            $bl['diskon_persen'] ?? 0,
                            $bl['diskon_nominal'] ?? 0,
                            $bl['is_dijual'] ? 1 : 0
                        ]);
                    }
                }

                $insertCount++;
            } elseif ($act === 'UPDATE') {
                $stmtUpd->execute([
                    $d['nama_grup'],
                    $d['default_level_harga'],
                    $d['status_aktif'] ? 1 : 0,
                    $d['id']
                ]);

                if (!empty($d['id']) && !empty($d['brand_levels'])) {
                    foreach ($d['brand_levels'] as $bl) {
                        $stmtBrandLevel->execute([
                            $d['id'],
                            $bl['merek_id'],
                            $bl['is_dijual'] ? $bl['level_harga'] : null,
                            $bl['diskon_persen'] ?? 0,
                            $bl['diskon_nominal'] ?? 0,
                            $bl['is_dijual'] ? 1 : 0
                        ]);
                    }
                }

                $updateCount++;
            } elseif ($act === 'DELETE') {
                $gid = $d['id'];
                $gkode = strtoupper(trim((string)($d['kode_grup'] ?? '')));
                if ($gkode === 'GRP-001') {
                    continue;
                }
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

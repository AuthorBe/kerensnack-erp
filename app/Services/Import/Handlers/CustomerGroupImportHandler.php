<?php
declare(strict_types=1);

namespace App\Services\Import\Handlers;

use App\Services\Import\SmartReader;
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
            ['nama_grup', 'nama', 'grup'],
            ['level_harga', 'level', 'default_level_harga']
        ];
    }

    public function getTemplateHeaders(): array
    {
        return [
            'Kode Grup',
            'Nama Grup',
            'Default Level Harga (1-30)',
            'Diskon Persen (%)',
            'Diskon Nominal (Rp)',
            'Status Aktif'
        ];
    }

    public function getTemplateWidths(): array
    {
        return [18, 30, 26, 20, 22, 16];
    }

    public function getTemplateExamples(): array
    {
        return [
            ['GRP-RITEL-A', 'Grup Ritel A', 1, 0, 0, 'Aktif'],
            ['GRP-GROSIR-PASAR', 'Grup Grosir Pasar', 8, 2.5, 0, 'Aktif'],
            ['GRP-KONSINYASI-TNG', 'Grup Konsinyasi Tangerang', 5, 0, 0, 'Aktif'],
        ];
    }

    public function getTemplateNotes(): array
    {
        return [
            'Kode Grup bersifat unik. Jika dikosongkan pada data baru, sistem akan men-generate otomatis.',
            'Default Level Harga wajib angka antara 1 sampai 30 sesuai Master Level Harga ERP.',
            'Diskon Persen bernilai 0 - 100, Diskon Nominal berupa angka rupiah.',
            'Status Aktif diisi "Aktif" atau "Nonaktif".'
        ];
    }

    public function getCurrentDataRows(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT kode_grup, nama_grup, default_level_harga, diskon_persen_default, diskon_nominal_default,
                                    CASE WHEN status_aktif THEN 'Aktif' ELSE 'Nonaktif' END as status_aktif_label
                             FROM public.grup_pelanggan
                             ORDER BY kode_grup ASC");
        return $stmt->fetchAll(PDO::FETCH_NUM);
    }

    public function previewRows(array $rows, array $header, PDO $pdo, string $mode): array
    {
        $dbGroups = $pdo->query("SELECT * FROM public.grup_pelanggan")->fetchAll(PDO::FETCH_ASSOC);
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
            $nama = (string)(SmartReader::getSmartValue($rowData, ['nama_grup', 'nama', 'grup']) ?? '');
            $levelRaw = SmartReader::getSmartValue($rowData, ['default_level_harga', 'level_harga', 'level', 'tingkat']);
            $diskonPersenRaw = SmartReader::getSmartValue($rowData, ['diskon_persen_default', 'diskon_persen', 'diskon_%']);
            $diskonNominalRaw = SmartReader::getSmartValue($rowData, ['diskon_nominal_default', 'diskon_nominal', 'diskon_rp']);
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

            $level = (int)SmartReader::normalizeNumeric($levelRaw, 1.0);
            if ($level < 1 || $level > 30) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Level Harga '{$levelRaw}' tidak valid pada baris {$lineNo}. Harus antara 1 dan 30.",
                    'data' => ['kode_grup' => $kode, 'nama_grup' => $nama]
                ];
                continue;
            }

            $diskonPersen = SmartReader::normalizeNumeric($diskonPersenRaw, 0.0);
            $diskonNominal = SmartReader::normalizeNumeric($diskonNominalRaw, 0.0);
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
                'default_level_harga'    => $level,
                'diskon_persen_default'  => $diskonPersen,
                'diskon_nominal_default' => $diskonNominal,
                'status_aktif'           => $statusAktif,
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

                $isDiff = trim($nama) !== trim((string)$dbRow['nama_grup'])
                    || (int)$level !== (int)$dbRow['default_level_harga']
                    || abs($diskonPersen - (float)$dbRow['diskon_persen_default']) > 0.01
                    || abs($diskonNominal - (float)$dbRow['diskon_nominal_default']) > 0.01
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

        $stmtMaxCode = $pdo->query("SELECT MAX(SUBSTRING(kode_grup FROM 5)::int) as max_seq FROM public.grup_pelanggan WHERE kode_grup ~ '^GRP-[0-9]+$'");
        $nextSeq = ((int)($stmtMaxCode->fetch(PDO::FETCH_ASSOC)['max_seq'] ?? 0)) + 1;

        $stmtIns = $pdo->prepare("INSERT INTO public.grup_pelanggan 
            (kode_grup, nama_grup, default_level_harga, diskon_persen_default, diskon_nominal_default, status_aktif)
            VALUES (?, ?, ?, ?, ?, ?)");

        $stmtUpd = $pdo->prepare("UPDATE public.grup_pelanggan SET 
            nama_grup = ?, default_level_harga = ?, diskon_persen_default = ?, diskon_nominal_default = ?, status_aktif = ?, diubah_pada = NOW()
            WHERE id = ?");

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
                    $d['diskon_persen_default'],
                    $d['diskon_nominal_default'],
                    $d['status_aktif'] ? 1 : 0
                ]);
                $insertCount++;
            } elseif ($act === 'UPDATE') {
                $stmtUpd->execute([
                    $d['nama_grup'],
                    $d['default_level_harga'],
                    $d['diskon_persen_default'],
                    $d['diskon_nominal_default'],
                    $d['status_aktif'] ? 1 : 0,
                    $d['id']
                ]);
                $updateCount++;
            } elseif ($act === 'DELETE') {
                $gid = $d['id'];
                $gkode = strtoupper(trim((string)($d['kode_grup'] ?? '')));
                if ($gkode === 'GRP-001') {
                    continue; // Lindungi grup pelanggan default GRP-001
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

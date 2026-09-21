<?php
declare(strict_types=1);

namespace App\Services\Import\Handlers;

use App\Services\Import\SmartReader;
use PDO;

class ProductItemImportHandler implements EntityImportHandlerInterface
{
    public function getEntityKey(): string
    {
        return 'products';
    }

    public function getEntityLabel(): string
    {
        return 'Katalog Produk Jual (Barang Jadi)';
    }

    public function getRequiredPermission(): string
    {
        return 'master.products_manage';
    }

    public function getRequiredHeaderGroups(): array
    {
        return [
            ['nama_item', 'nama_produk', 'produk', 'nama'],
            ['kode_sku', 'sku', 'kode']
        ];
    }

    public function getTemplateHeaders(): array
    {
        return [
            'Kode SKU',
            'Nama Item Produk',
            'Grup Produk',
            'Satuan Dasar',
            'Kelompok Upah Borongan',
            'Pemasok Utama',
            'HPP Pokok (Rp)',
            'Stok Minimum Warning',
            'Status Jual (Aktif/Nonaktif)',
            'Status Aktif Master'
        ];
    }

    public function getTemplateWidths(): array
    {
        return [18, 34, 26, 14, 24, 24, 18, 20, 16, 16];
    }

    public function getTemplateExamples(): array
    {
        return [
            ['KS-SK-ASIN-250', 'Keripik Singkong Asin Gurih 250gr', 'Keripik Singkong 250gr', 'pcs', 'Kelompok 600', 'Sentra Singkong Subang', 8500, 50, 'Aktif', 'Aktif'],
            ['KS-SK-PEDAS-250', 'Keripik Singkong Pedas Balado 250gr', 'Keripik Singkong 250gr', 'pcs', 'Kelompok 600', 'Sentra Singkong Subang', 9000, 50, 'Aktif', 'Aktif'],
            ['KS-BRNG-ORI-150', 'Basreng Original Daun Jeruk 150gr', 'Basreng Pedas Daun Jeruk 150gr', 'pcs', 'Kelompok 500', 'UD Plastik Prima Abadi', 7000, 30, 'Aktif', 'Aktif'],
        ];
    }

    public function getTemplateNotes(): array
    {
        return [
            'Kode SKU WAJIB unik (contoh: KS-SK-ASIN-250). Jika dikosongkan untuk produk baru, sistem akan men-generate otomatis.',
            'Nama Item Produk WAJIB diisi.',
            'Grup Produk dapat diisi Nama atau Kode Grup Produk yang sudah terdaftar di sistem.',
            'Kelompok Upah Borongan: isi nama kelompok (contoh: "Kelompok 600") atau kosongkan jika tidak ada upah borongan.',
            'Pemasok Utama: isi nama pemasok atau kosongkan jika diproduksi repacking internal.'
        ];
    }

    public function getCurrentDataRows(PDO $pdo): array
    {
        $sql = "SELECT i.kode_sku, i.nama_item,
                       COALESCE(g.nama_grup, '') as nama_grup, i.satuan_dasar,
                       COALESCE(k.nama_kelompok, '') as kelompok_borongan,
                       COALESCE(s.nama_pemasok, '') as nama_pemasok,
                       i.harga_pokok_pembelian, i.stok_minimum_peringatan,
                       CASE WHEN i.status_jual THEN 'Aktif' ELSE 'Nonaktif' END as status_jual_label,
                       CASE WHEN i.status_aktif THEN 'Aktif' ELSE 'Nonaktif' END as status_aktif_label
                FROM public.item i
                LEFT JOIN public.grup_produk g ON g.id = i.grup_id
                LEFT JOIN public.kelompok_upah_borongan k ON k.id = i.kelompok_borongan_id
                LEFT JOIN public.pemasok s ON s.id = i.pemasok_utama_id
                WHERE i.tipe_item = 'barang_jadi'
                ORDER BY i.kode_sku ASC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_NUM);
    }

    public function previewRows(array $rows, array $header, PDO $pdo, string $mode): array
    {
        $groups = $pdo->query("SELECT id, kode_grup, nama_grup FROM public.grup_produk")->fetchAll(PDO::FETCH_ASSOC);
        $groupMap = [];
        foreach ($groups as $g) {
            $groupMap[strtolower(trim($g['kode_grup']))] = $g['id'];
            $groupMap[strtolower(trim($g['nama_grup']))] = $g['id'];
        }

        $boronganGroups = $pdo->query("SELECT id, nama_kelompok FROM public.kelompok_upah_borongan")->fetchAll(PDO::FETCH_ASSOC);
        $boronganMap = [];
        foreach ($boronganGroups as $b) {
            $boronganMap[strtolower(trim($b['nama_kelompok']))] = $b['id'];
        }

        $suppliers = $pdo->query("SELECT id, kode_pemasok, nama_pemasok FROM public.pemasok")->fetchAll(PDO::FETCH_ASSOC);
        $supplierMap = [];
        foreach ($suppliers as $s) {
            $supplierMap[strtolower(trim($s['kode_pemasok']))] = $s['id'];
            $supplierMap[strtolower(trim($s['nama_pemasok']))] = $s['id'];
        }

        $dbProducts = $pdo->query("SELECT i.*, COALESCE(g.nama_grup, '') as nama_grup, COALESCE(k.nama_kelompok, '') as nama_kelompok, COALESCE(s.nama_pemasok, '') as nama_pemasok 
                                   FROM public.item i 
                                   LEFT JOIN public.grup_produk g ON g.id = i.grup_id 
                                   LEFT JOIN public.kelompok_upah_borongan k ON k.id = i.kelompok_borongan_id 
                                   LEFT JOIN public.pemasok s ON s.id = i.pemasok_utama_id 
                                   WHERE i.tipe_item = 'barang_jadi'")->fetchAll(PDO::FETCH_ASSOC);
        $dbBySku = [];
        $dbByName = [];
        foreach ($dbProducts as $p) {
            $dbBySku[strtolower(trim($p['kode_sku']))] = $p;
            $dbByName[strtolower(trim($p['nama_item']))] = $p;
        }

        $previewList = [];
        $seenSkus = [];
        $processedDbIds = [];

        foreach ($rows as $idx => $row) {
            $lineNo = $idx + 2;
            $rowData = SmartReader::buildRowData($header, $row);

            $sku = (string)(SmartReader::getSmartValue($rowData, ['kode_sku', 'sku', 'kode']) ?? '');
            $nama = (string)(SmartReader::getSmartValue($rowData, ['nama_item_varian', 'nama_varian_item', 'nama_item_produk', 'nama_item', 'nama_produk', 'nama_varian', 'varian', 'nama']) ?? '');
            $grupRaw = (string)(SmartReader::getSmartValue($rowData, ['grup_produk', 'grup', 'kategori']) ?? '');
            $satuanDasar = (string)(SmartReader::getSmartValue($rowData, ['satuan_dasar', 'satuan']) ?? 'pcs');
            $boronganRaw = (string)(SmartReader::getSmartValue($rowData, ['kelompok_upah_borongan', 'kelompok_borongan', 'borongan']) ?? '');
            $pemasokRaw = (string)(SmartReader::getSmartValue($rowData, ['pemasok_utama', 'pemasok', 'supplier']) ?? '');
            $hppRaw = SmartReader::getSmartValue($rowData, ['hpp_pokok', 'harga_pokok_pembelian', 'hpp']);
            $stokMinRaw = SmartReader::getSmartValue($rowData, ['stok_minimum_warning', 'stok_minimum_peringatan', 'stok_min']);
            $statusJualRaw = SmartReader::getSmartValue($rowData, ['status_jual', 'jual']);
            $statusAktifRaw = SmartReader::getSmartValue($rowData, ['status_aktif_master', 'status_aktif', 'status', 'aktif']);

            if (empty($sku) && empty($nama)) {
                continue;
            }

            if (empty($nama)) {
                $previewList[] = [
                    'action' => 'ERROR',
                    'error_msg' => "Nama Item Produk kosong pada baris {$lineNo}. Wajib diisi.",
                    'data' => ['kode_sku' => $sku, 'nama_item' => '—']
                ];
                continue;
            }

            $hpp = SmartReader::normalizeNumeric($hppRaw, 0.0);
            $stokMin = SmartReader::normalizeNumeric($stokMinRaw, 10.0);
            $statusJual = SmartReader::normalizeBoolean($statusJualRaw, true);
            $statusAktif = SmartReader::normalizeBoolean($statusAktifRaw, true);

            if (!empty($sku)) {
                $sKey = strtolower(trim($sku));
                if (isset($seenSkus[$sKey])) {
                    $previewList[] = [
                        'action' => 'ERROR',
                        'error_msg' => "Duplikasi SKU '{$sku}' pada baris {$lineNo}.",
                        'data' => ['kode_sku' => $sku, 'nama_item' => $nama]
                    ];
                    continue;
                }
                $seenSkus[$sKey] = true;
            }

            $grupId = null;
            if (!empty($grupRaw)) {
                $gKey = strtolower(trim($grupRaw));
                if (!isset($groupMap[$gKey])) {
                    $previewList[] = [
                        'action' => 'ERROR',
                        'error_msg' => empty($groups)
                            ? "Master Grup Produk masih kosong di sistem. Impor Master Grup Kemasan Produk (Fase 1) terlebih dahulu."
                            : "Grup Produk '{$grupRaw}' pada baris {$lineNo} tidak ditemukan di sistem. Pastikan nama grup terdaftar di Master Grup Produk (Fase 1).",
                        'data' => ['kode_sku' => $sku, 'nama_item' => $nama]
                    ];
                    continue;
                }
                $grupId = $groupMap[$gKey];
            }

            $boronganId = null;
            if (!empty($boronganRaw)) {
                $bKey = strtolower(trim($boronganRaw));
                if (isset($boronganMap[$bKey])) {
                    $boronganId = $boronganMap[$bKey];
                }
            }

            $pemasokId = null;
            if (!empty($pemasokRaw)) {
                $pKey = strtolower(trim($pemasokRaw));
                if (isset($supplierMap[$pKey])) {
                    $pemasokId = $supplierMap[$pKey];
                }
            }

            $dbRow = null;
            if (!empty($sku) && isset($dbBySku[strtolower(trim($sku))])) {
                $dbRow = $dbBySku[strtolower(trim($sku))];
            } elseif (empty($sku) && isset($dbByName[strtolower(trim($nama))])) {
                $dbRow = $dbByName[strtolower(trim($nama))];
            }

            $itemData = [
                'id'                         => $dbRow['id'] ?? null,
                'kode_sku'                   => !empty($sku) ? $sku : ($dbRow['kode_sku'] ?? ''),
                'nama_item'                  => $nama,
                'tipe_item'                  => 'barang_jadi',
                'satuan_dasar'               => $satuanDasar ?: 'pcs',
                'grup_id'                    => $grupId ?: ($dbRow['grup_id'] ?? null),
                'kelompok_borongan_id'       => $boronganId ?: ($dbRow['kelompok_borongan_id'] ?? null),
                'pemasok_utama_id'           => $pemasokId ?: ($dbRow['pemasok_utama_id'] ?? null),
                'harga_pokok_pembelian'      => $hpp,
                'stok_minimum_peringatan'    => $stokMin,
                'status_jual'                => $statusJual,
                'status_aktif'               => $statusAktif,
                'display_grup'               => $grupRaw ?: ($dbRow['nama_grup'] ?? '—'),
                'display_kelompok_borongan'  => $boronganRaw ?: ($dbRow['nama_kelompok'] ?? '—'),
                'display_pemasok'            => $pemasokRaw ?: ($dbRow['nama_pemasok'] ?? '—')
            ];

            if ($dbRow) {
                $processedDbIds[] = $dbRow['id'];

                if (!empty($sku) && !SmartReader::isSimilarName($dbRow['nama_item'], $nama)) {
                    $previewList[] = [
                        'action'       => 'INSERT',
                        'is_fatal'     => true,
                        'fatal_reason' => "SKU '{$sku}' di database terdaftar sebagai \"{$dbRow['nama_item']}\", berbeda jauh dengan \"{$nama}\". Dibuat sebagai produk baru dengan SKU otomatis.",
                        'data'         => $itemData,
                        'old_data'     => $dbRow
                    ];
                    continue;
                }

                $isDiff = trim($nama) !== trim((string)$dbRow['nama_item'])
                    || trim($satuanDasar) !== trim((string)$dbRow['satuan_dasar'])
                    || ($grupId && $grupId !== $dbRow['grup_id'])
                    || ($boronganId && $boronganId !== $dbRow['kelompok_borongan_id'])
                    || ($pemasokId && $pemasokId !== $dbRow['pemasok_utama_id'])
                    || abs($hpp - (float)$dbRow['harga_pokok_pembelian']) > 0.01
                    || abs($stokMin - (float)$dbRow['stok_minimum_peringatan']) > 0.01
                    || $statusJual !== (bool)$dbRow['status_jual']
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
            foreach ($dbProducts as $p) {
                if (!in_array($p['id'], $processedDbIds, true)) {
                    $previewList[] = [
                        'action' => 'DELETE',
                        'data'   => $p
                    ];
                }
            }
        }

        return $previewList;
    }

    /**
     * Memeriksa apakah item memiliki riwayat transaksi, mutasi stok, BOM produksi, atau konsinyasi
     */
    public function hasTransactionHistory(string $id, PDO $pdo): bool
    {
        $stmt = $pdo->prepare("SELECT 
            COALESCE((SELECT COUNT(*) FROM public.item_pesanan WHERE item_id = ?), 0) +
            COALESCE((SELECT COUNT(*) FROM public.stok_konsinyasi_toko WHERE item_id = ?), 0) +
            COALESCE((SELECT COUNT(*) FROM public.riwayat_stok WHERE item_id = ?), 0) +
            COALESCE((SELECT COUNT(*) FROM public.rincian_pembelian WHERE item_id = ?), 0) +
            COALESCE((SELECT COUNT(*) FROM public.komposisi_item WHERE item_jadi_id = ? OR item_bahan_id = ?), 0) +
            COALESCE((SELECT COUNT(*) FROM public.opname_gudang_item WHERE item_id = ?), 0) +
            COALESCE((SELECT COUNT(*) FROM public.pelanggan_item WHERE item_id = ?), 0) +
            COALESCE((SELECT COUNT(*) FROM public.penyesuaian_stok WHERE item_id = ?), 0) +
            COALESCE((SELECT COUNT(*) FROM public.produksi_harian WHERE item_id = ?), 0) +
            COALESCE((SELECT COUNT(*) FROM public.rincian_kunjungan_konsinyasi WHERE item_id = ?), 0) AS total_usage");
        $stmt->execute([$id, $id, $id, $id, $id, $id, $id, $id, $id, $id, $id]);
        return ((int)($stmt->fetch(PDO::FETCH_ASSOC)['total_usage'] ?? 0)) > 0;
    }

    public function applySync(array $previewList, PDO $pdo): array
    {
        $insertCount = 0;
        $updateCount = 0;
        $deleteCount = 0;
        $deactivateCount = 0;

        // Sequence generator
        $stmtMaxCode = $pdo->query("SELECT MAX(SUBSTRING(kode_sku FROM 6)::int) as max_seq FROM public.item WHERE kode_sku ~ '^PROD-[0-9]+$' AND tipe_item = 'barang_jadi'");
        $nextSeq = ((int)($stmtMaxCode->fetch(PDO::FETCH_ASSOC)['max_seq'] ?? 0)) + 1;

        $stmtIns = $pdo->prepare("INSERT INTO public.item 
            (kode_sku, nama_item, tipe_item, satuan_dasar, grup_id, kelompok_borongan_id, pemasok_utama_id, harga_pokok_pembelian, stok_minimum_peringatan, status_jual, status_aktif)
            VALUES (?, ?, 'barang_jadi', ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmtUpd = $pdo->prepare("UPDATE public.item SET 
            nama_item = ?, satuan_dasar = ?, grup_id = ?, kelompok_borongan_id = ?, pemasok_utama_id = ?, harga_pokok_pembelian = ?, stok_minimum_peringatan = ?, status_jual = ?, status_aktif = ?, diubah_pada = NOW()
            WHERE id = ?");

        $stmtDeactivate = $pdo->prepare("UPDATE public.item SET status_aktif = FALSE, status_jual = FALSE, diubah_pada = NOW() WHERE id = ?");
        $stmtDel = $pdo->prepare("DELETE FROM public.item WHERE id = ?");

        foreach ($previewList as $row) {
            $act = $row['action'];
            $isFatal = !empty($row['is_fatal']);
            $d = $row['data'] ?? [];

            if ($act === 'INSERT' || $isFatal) {
                $sku = $d['kode_sku'] ?? '';
                if (empty($sku) || $isFatal) {
                    $sku = 'PROD-' . str_pad((string)$nextSeq++, 4, '0', STR_PAD_LEFT);
                }

                $stmtIns->execute([
                    $sku,
                    $d['nama_item'] ?? '',
                    $d['satuan_dasar'] ?: 'pcs',
                    $d['grup_id'] ?: null,
                    $d['kelompok_borongan_id'] ?: null,
                    $d['pemasok_utama_id'] ?: null,
                    $d['harga_pokok_pembelian'] ?: 0,
                    $d['stok_minimum_peringatan'] ?: 10,
                    !empty($d['status_jual']) ? 1 : 0,
                    !empty($d['status_aktif']) ? 1 : 0,
                ]);
                $insertCount++;
            } elseif ($act === 'UPDATE') {
                $stmtUpd->execute([
                    $d['nama_item'] ?? '',
                    $d['satuan_dasar'] ?: 'pcs',
                    $d['grup_id'] ?: null,
                    $d['kelompok_borongan_id'] ?: null,
                    $d['pemasok_utama_id'] ?: null,
                    $d['harga_pokok_pembelian'] ?: 0,
                    $d['stok_minimum_peringatan'] ?: 10,
                    !empty($d['status_jual']) ? 1 : 0,
                    !empty($d['status_aktif']) ? 1 : 0,
                    $d['id']
                ]);
                $updateCount++;
            } elseif ($act === 'DELETE') {
                $iid = (string)$d['id'];
                if ($this->hasTransactionHistory($iid, $pdo)) {
                    $stmtDeactivate->execute([$iid]);
                    $deactivateCount++;
                } else {
                    $stmtDel->execute([$iid]);
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

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
use App\Helpers\ExcelExport;
use App\Helpers\PdfExport;
use App\Helpers\CompanySetting;
use Database;
use Throwable;

/**
 * app/Controllers/InventoryController.php
 * Pengendali Katalog 137 SKU Produk, Stok Fisik & Mutasi Gudang.
 */

class InventoryController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission('inventory.view_all');
    }

    public function index(): void
    {
        try {
            $items = Database::fetchAll("
                SELECT i.id, i.kode_sku, i.nama_item,
                       i.stok_fisik_saat_ini, i.stok_minimum_peringatan, i.satuan_dasar,
                       i.harga_pokok_pembelian, gp.nama_grup, gp.kode_grup, gp.barcode_universal
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE i.status_aktif = TRUE
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ");

            // Derive distinct groups in memory to avoid extra database round-trip
            $groups = [];
            $seen = [];
            foreach ($items as $it) {
                $kg = $it['kode_grup'] ?? '';
                if ($kg !== '' && !isset($seen[$kg])) {
                    $seen[$kg] = true;
                    $groups[] = [
                        'id' => $kg,
                        'kode_grup' => $kg,
                        'nama_grup' => $it['nama_grup'] ?? $kg
                    ];
                }
            }
            usort($groups, fn($a, $b) => strcmp($a['kode_grup'], $b['kode_grup']));

            $this->view('inventory.index', [
                'pageTitle' => 'Katalog & Mutasi Stok',
                'pageSubtitle' => 'Monitoring Stok Fisik Gudang & Riwayat Perubahan',
                'items' => $items,
                'groups' => $groups
            ]);

        } catch (Throwable $e) {
            error_log("InventoryController index error: " . $e->getMessage());
            $this->flashError("Gagal memuat katalog persediaan: " . $e->getMessage());
            $this->redirect('/');
        }
    }

    public function adjustStock(): void
    {
        Auth::requirePermission('inventory.opname');

        if (!$this->validateCsrf()) {
            return;
        }

        $itemId = (string)$this->input('item_id');
        $qty = (float)$this->input('kuantitas', 0);
        $tipe = (string)$this->input('tipe_penyesuaian', 'opname_lebih');
        $alasan = trim((string)$this->input('alasan', 'Penyesuaian stok fisik'));

        if (!in_array($tipe, ['opname_lebih', 'opname_hilang', 'retur_masuk_manual'], true)) {
            $this->flashError('Tipe penyesuaian stok fisik tidak valid.');
            $this->redirect('/inventory');
            return;
        }

        if (empty($itemId) || $qty <= 0) {
            $this->flashError('Jumlah kuantitas harus lebih besar dari 0.');
            $this->redirect('/inventory');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $item = Database::fetchOne("SELECT stok_fisik_saat_ini, nama_item FROM public.item WHERE id = :id FOR UPDATE", ['id' => $itemId]);
            if (!$item) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $this->flashError('Produk tidak ditemukan di database.');
                $this->redirect('/inventory');
                return;
            }

            $stokLama = (float)($item['stok_fisik_saat_ini'] ?? 0);

            // Pengaman Anti-Minus: Pengurangan tidak boleh melebihi sisa stok
            if (!in_array($tipe, ['opname_lebih', 'retur_masuk_manual'], true) && $stokLama < $qty) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $this->flashError("Jumlah penyesuaian minus ({$qty} pcs) melebihi stok fisik saat ini ({$stokLama} pcs). Stok tidak boleh minus.");
                $this->redirect('/inventory');
                return;
            }

            $stokBaru = in_array($tipe, ['opname_lebih', 'retur_masuk_manual'], true) ? ($stokLama + $qty) : max(0.0, $stokLama - $qty);

            // Update item
            $pdo->prepare("UPDATE public.item SET stok_fisik_saat_ini = :baru, diubah_pada = NOW() WHERE id = :id")
                ->execute(['baru' => $stokBaru, 'id' => $itemId]);

            // Insert riwayat stok dengan user ID pelaksana & timestamp lengkap
            $userId = Auth::id() ?: null;
            $tipeMutasi = match($tipe) {
                'opname_lebih'       => 'penyesuaian_opname_tambah',
                'retur_masuk_manual' => 'retur_masuk_manual',
                default              => 'penyesuaian_opname_kurang'
            };
            $pdo->prepare("
                INSERT INTO public.riwayat_stok (
                    item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                    referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
                ) VALUES (
                    :item_id, :tipe, :qty, :sebelum, :sesudah,
                    'penyesuaian_stok', '00000000-0000-0000-0000-000000000000', :ket, :user_id, NOW()
                )
            ")->execute([
                'item_id' => $itemId,
                'tipe' => $tipeMutasi,
                'qty' => $qty,
                'sebelum' => $stokLama,
                'sesudah' => $stokBaru,
                'ket' => "Manual Opname: {$alasan}",
                'user_id' => $userId
            ]);

            ActivityLog::log(
                'logistik',
                'UPDATE',
                "Penyesuaian Stok Opname '{$item['nama_item']}' dari {$stokLama} menjadi {$stokBaru} (Alasan: {$alasan})",
                'item',
                $itemId
            );

            $pdo->commit();
            $this->flashSuccess("Opname fisik berhasil! Stok '{$item['nama_item']}' kini menjadi {$stokBaru} pcs.");
            $this->redirect('/inventory');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError("Gagal menyimpan opname: " . $e->getMessage());
            $this->redirect('/inventory');
        }
    }

    /**
     * Catat Pengurangan Stok Akibat Barang Rusak, Bocor, Expired, atau Sampel (Waste)
     */
    public function recordWaste(): void
    {
        Auth::requirePermission('inventory.waste');

        if (!$this->validateCsrf()) {
            return;
        }

        $itemId = (string)$this->input('item_id');
        $qty = (float)$this->input('kuantitas', 0);
        $kategoriWaste = trim((string)$this->input('kategori_waste', 'kemasan_rusak'));
        $allowedWaste = ['kemasan_rusak', 'remuk_hancur', 'expired_kadaluarsa', 'sampel_promosi', 'lainnya'];
        if (!in_array($kategoriWaste, $allowedWaste, true)) {
            $kategoriWaste = 'lainnya';
        }
        $keterangan = trim((string)$this->input('keterangan', 'Barang Rusak / Susut Operasional'));

        if (empty($itemId) || $qty <= 0) {
            $this->flashError('Jumlah kuantitas barang rusak/waste harus lebih dari 0.');
            $this->redirect('/inventory');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $item = Database::fetchOne("SELECT stok_fisik_saat_ini, nama_item FROM public.item WHERE id = :id FOR UPDATE", ['id' => $itemId]);
            if (!$item) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $this->flashError('Item tidak ditemukan.');
                $this->redirect('/inventory');
                return;
            }

            $stokLama = (float)($item['stok_fisik_saat_ini'] ?? 0);
            if ($stokLama < $qty) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $this->flashError("Kuantitas waste ({$qty} pcs) melebihi sisa stok fisik saat ini ({$stokLama} pcs).");
                $this->redirect('/inventory');
                return;
            }

            $stokBaru = max(0.0, $stokLama - $qty);

            // Update item stock
            $pdo->prepare("UPDATE public.item SET stok_fisik_saat_ini = :baru, diubah_pada = NOW() WHERE id = :id")
                ->execute(['baru' => $stokBaru, 'id' => $itemId]);

            $kategoriLabels = [
                'kemasan_rusak' => 'Kemasan Rusak / Gagal Segel',
                'expired_kadaluarsa' => 'Kadaluarsa / Expired',
                'remuk_hancur' => 'Produk Remuk / Hancur',
                'sampel_promosi' => 'Sampel Uji Rasa / Promosi',
                'lainnya' => 'Lain-lain'
            ];
            $labelKategori = $kategoriLabels[$kategoriWaste] ?? $kategoriWaste;
            $catatanLengkap = "[WASTE: {$labelKategori}] {$keterangan}";

            // Insert riwayat stok
            $pdo->prepare("
                INSERT INTO public.riwayat_stok (
                    item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                    referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
                ) VALUES (
                    :item_id, 'item_keluar_waste', :qty, :sebelum, :sesudah,
                    'waste_manual', '00000000-0000-0000-0000-000000000000', :ket, :user_id, NOW()
                )
            ")->execute([
                'item_id' => $itemId,
                'qty' => $qty,
                'sebelum' => $stokLama,
                'sesudah' => $stokBaru,
                'ket' => $catatanLengkap,
                'user_id' => Auth::id() ?: null,
            ]);

            ActivityLog::log(
                'Gudang',
                'WASTE',
                "Catat waste/barang rusak: {$qty} pcs '{$item['nama_item']}' ({$labelKategori})",
                'item',
                $itemId
            );

            $pdo->commit();
            $this->flashSuccess("Pencatatan barang rusak/waste berhasil! Stok '{$item['nama_item']}' terpotong {$qty} pcs (sisa: {$stokBaru} pcs).");
            $this->redirect('/inventory');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError("Gagal mencatat waste: " . $e->getMessage());
            $this->redirect('/inventory');
        }
    }

    /**
     * Export Seluruh Katalog Produk & Stok Gudang ke File Excel (PhpSpreadsheet)
     */
    public function exportExcel(): void
    {
        Auth::requirePermission('inventory.view_all');

        try {
            $items = Database::fetchAll("
                SELECT i.id, i.kode_sku, i.nama_item,
                       i.stok_fisik_saat_ini, i.stok_minimum_peringatan, i.satuan_dasar,
                       i.harga_pokok_pembelian, gp.nama_grup, gp.kode_grup, gp.barcode_universal
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE i.status_aktif = TRUE
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ");

            $headers = ['No', 'Kode SKU', 'Barcode', 'Nama Produk Snack', 'Varian / Rasa', 'Grup Kemasan', 'Stok Fisik Gudang', 'Peringatan Min Stok', 'Satuan Dasar', 'Harga Pokok (HPP)', 'Estimasi Nilai Stok (Rp)'];
            $rows = [];
            $no = 1;
            $totalPcs = 0.0;
            $totalValuation = 0.0;

            foreach ($items as $it) {
                $stok = (float)($it['stok_fisik_saat_ini'] ?? 0);
                $hpp = (float)($it['harga_pokok_pembelian'] ?? 0);
                $valuation = $stok * $hpp;
                $totalPcs += $stok;
                $totalValuation += $valuation;

                $rows[] = [
                    $no++,
                    $it['kode_sku'],
                    $it['barcode_universal'] ?? '-',
                    $it['nama_item'],
                    '-',
                    $it['nama_grup'] ?? '-',
                    $stok,
                    (float)($it['stok_minimum_peringatan'] ?? 0),
                    $it['satuan_dasar'],
                    $hpp,
                    $valuation
                ];
            }

            $rows[] = ['', '', '', '', '', 'TOTAL PERSINGGAHAN STOK GUDANG:', $totalPcs, '', '', 'TOTAL VALUASI (HPP):', $totalValuation];

            ExcelExport::download("Katalog-Stok-Gudang-" . date('Ymd') . ".xlsx", $headers, $rows, "Stok Gudang");
        } catch (Throwable $e) {
            $this->flashError("Gagal export data stok gudang: " . $e->getMessage());
            $this->redirect('/inventory');
        }
    }

    /**
     * Halaman Formulir Bulk Opname Stok Gudang
     */
    public function bulkOpname(): void
    {
        Auth::requirePermission('inventory.opname');

        try {
            $items = Database::fetchAll("
                SELECT i.id, i.kode_sku, i.nama_item,
                       i.stok_fisik_saat_ini, i.stok_minimum_peringatan, i.satuan_dasar,
                       i.harga_pokok_pembelian, gp.nama_grup, gp.kode_grup, gp.barcode_universal
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE i.status_aktif = TRUE
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ");

            // Derive distinct groups in memory to avoid extra database round-trip
            $groups = [];
            $seen = [];
            foreach ($items as $it) {
                $kg = $it['kode_grup'] ?? '';
                if ($kg !== '' && !isset($seen[$kg])) {
                    $seen[$kg] = true;
                    $groups[] = [
                        'id' => $kg,
                        'kode_grup' => $kg,
                        'nama_grup' => $it['nama_grup'] ?? $kg
                    ];
                }
            }
            usort($groups, fn($a, $b) => strcmp($a['kode_grup'], $b['kode_grup']));

            $this->view('inventory.bulk_opname', [
                'pageTitle' => 'Bulk Opname Stok Gudang',
                'pageSubtitle' => 'Penyesuaian Massal Stok Fisik & Audit Mutasi Gudang',
                'items' => $items,
                'groups' => $groups
            ]);
        } catch (Throwable $e) {
            $this->flashError("Gagal memuat formulir bulk opname: " . $e->getMessage());
            $this->redirect('/inventory');
        }
    }

    /**
     * Simpan Transaksi Bulk Opname Stok Gudang
     */
    public function storeBulkOpname(): void
    {
        Auth::requirePermission('inventory.opname');

        if (!$this->validateCsrf()) {
            return;
        }

        $tanggal = trim((string)$this->input('tanggal', date('Y-m-d')));
        if (empty($tanggal)) {
            $tanggal = date('Y-m-d');
        }
        $catatan = trim((string)$this->input('catatan', 'Bulk Opname Stok Fisik Gudang'));
        $totalKatalogInput = (int)$this->input('total_katalog', 0);

        $itemsRaw = $this->input('items_json');
        $items = [];
        if (!empty($itemsRaw)) {
            $items = json_decode((string)$itemsRaw, true) ?: [];
        } elseif (isset($_POST['items']) && is_array($_POST['items'])) {
            $items = $_POST['items'];
        }

        // Filter item yang mengalami selisih (berubah) & cegah duplikasi item_id
        $modifiedItems = [];
        $seenItems = [];
        foreach ($items as $it) {
            $itemId = (string)($it['item_id'] ?? '');
            if (empty($itemId) || isset($seenItems[$itemId])) continue;

            $stokFisik = (float)($it['stok_fisik'] ?? 0);
            $stokSistem = (float)($it['stok_sistem'] ?? 0);
            $selisih = (float)($it['selisih'] ?? ($stokFisik - $stokSistem));

            if (abs($selisih) > 0.0001) {
                $seenItems[$itemId] = true;
                $modifiedItems[] = [
                    'item_id' => $itemId,
                    'stok_sistem' => $stokSistem,
                    'stok_fisik' => $stokFisik,
                    'selisih' => $selisih,
                    'catatan_item' => trim((string)($it['catatan_item'] ?? ''))
                ];
            }
        }

        if (empty($modifiedItems)) {
            $this->flashError('Tidak ada perubahan kuantitas stok yang dimasukkan. Mohon isi minimal 1 item dengan selisih kuantitas.');
            $this->redirect('/inventory/bulk-opname');
            return;
        }

        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();

            // 0. Penguncian Advisory Xact Lock untuk mencegah race condition nomor urut dokumen
            $pdo->query("SELECT pg_advisory_xact_lock(hashtext('opname_gudang_nomor_dokumen'))");

            // Hitung total katalog sesungguhnya untuk keakuratan audit trail
            $totalActiveCatalog = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM public.item WHERE status_aktif = TRUE")['c'] ?? 0);
            $totalKatalog = max($totalKatalogInput, $totalActiveCatalog, count($items));

            // 1. Generate nomor dokumen OPN-YYYYMMDD-XXXX
            $datePrefix = 'OPN-' . date('Ymd', strtotime($tanggal)) . '-';
            $lastDoc = Database::fetchOne("
                SELECT nomor_dokumen 
                FROM public.opname_gudang 
                WHERE nomor_dokumen LIKE :prefix 
                ORDER BY nomor_dokumen DESC 
                LIMIT 1
            ", ['prefix' => $datePrefix . '%']);

            $nextSeq = 1;
            if ($lastDoc && preg_match('/-(\d+)$/', (string)$lastDoc['nomor_dokumen'], $m)) {
                $nextSeq = (int)$m[1] + 1;
            }
            $nomorDokumen = $datePrefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);

            // 2. Insert Header opname_gudang
            $userId = Auth::id() ?: null;
            $stmtHeader = $pdo->prepare("
                INSERT INTO public.opname_gudang (
                    nomor_dokumen, tanggal, total_item_dihitung, total_item_selisih,
                    total_qty_masuk, total_qty_keluar, total_nilai_selisih_rp, total_item_katalog,
                    catatan, dibuat_oleh, dibuat_pada
                ) VALUES (
                    :nomor, :tanggal, :dihitung, 0,
                    0, 0, 0, :katalog,
                    :catatan, :user_id, NOW()
                ) RETURNING id
            ");
            $stmtHeader->execute([
                'nomor' => $nomorDokumen,
                'tanggal' => $tanggal,
                'dihitung' => $totalKatalog,
                'katalog' => $totalKatalog,
                'catatan' => $catatan,
                'user_id' => $userId
            ]);
            $opnameRow = $stmtHeader->fetch(\PDO::FETCH_ASSOC);
            $opnameId = $opnameRow['id'];

            $totalMasuk = 0.0;
            $totalKeluar = 0.0;
            $totalNilaiSelisihRp = 0.0;
            $countSelisih = 0;

            $stmtItemOpname = $pdo->prepare("
                INSERT INTO public.opname_gudang_item (
                    opname_id, item_id, stok_sistem, stok_fisik, selisih, tipe_mutasi,
                    harga_pokok_saat_opname, subtotal_nilai_selisih, catatan_item, dibuat_pada
                ) VALUES (
                    :opname_id, :item_id, :sistem, :fisik, :selisih, :tipe,
                    :hpp, :subtotal_rp, :catatan, NOW()
                )
            ");

            $stmtUpdateItem = $pdo->prepare("
                UPDATE public.item 
                SET stok_fisik_saat_ini = :stok_baru, diubah_pada = NOW() 
                WHERE id = :id
            ");

            $stmtRiwayat = $pdo->prepare("
                INSERT INTO public.riwayat_stok (
                    item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                    referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
                ) VALUES (
                    :item_id, :tipe_mutasi, :qty, :sebelum, :sesudah,
                    'opname_gudang', :ref_id, :ket, :user_id, NOW()
                )
            ");

            foreach ($modifiedItems as $row) {
                $itemId = $row['item_id'];
                $stokFisikInput = (float)$row['stok_fisik'];

                // Proteksi anti-minus
                if ($stokFisikInput < 0) {
                    throw new \Exception("Stok fisik tidak boleh bernilai negatif (kurang dari 0).");
                }

                // Lock row item
                $itemDb = Database::fetchOne("
                    SELECT stok_fisik_saat_ini, nama_item, kode_sku, harga_pokok_pembelian 
                    FROM public.item 
                    WHERE id = :id 
                    FOR UPDATE
                ", ['id' => $itemId]);

                if (!$itemDb) {
                    continue;
                }

                $stokDbSaatIni = (float)$itemDb['stok_fisik_saat_ini'];
                $hpp = (float)($itemDb['harga_pokok_pembelian'] ?? 0.0);
                $selisihNyata = $stokFisikInput - $stokDbSaatIni;

                if (abs($selisihNyata) < 0.0001) {
                    continue;
                }

                $isMasuk = ($selisihNyata > 0);
                $tipeMutasiDok = $isMasuk ? 'masuk' : 'keluar';
                $tipeMutasiRiwayat = $isMasuk ? 'penyesuaian_opname_tambah' : 'penyesuaian_opname_kurang';
                $qtyPerubahan = abs($selisihNyata);
                $subtotalNilai = $selisihNyata * $hpp;

                if ($isMasuk) {
                    $totalMasuk += $qtyPerubahan;
                } else {
                    $totalKeluar += $qtyPerubahan;
                }
                $totalNilaiSelisihRp += $subtotalNilai;
                $countSelisih++;

                $ketKhusus = !empty($row['catatan_item']) ? " [Catatan: {$row['catatan_item']}]" : "";
                $ketMutasi = "Bulk Opname: {$catatan}{$ketKhusus}";

                // 1. Update stok fisik item
                $stmtUpdateItem->execute([
                    'stok_baru' => $stokFisikInput,
                    'id' => $itemId
                ]);

                // 2. Insert rincian opname_gudang_item
                $stmtItemOpname->execute([
                    'opname_id' => $opnameId,
                    'item_id' => $itemId,
                    'sistem' => $stokDbSaatIni,
                    'fisik' => $stokFisikInput,
                    'selisih' => $selisihNyata,
                    'tipe' => $tipeMutasiDok,
                    'hpp' => $hpp,
                    'subtotal_rp' => $subtotalNilai,
                    'catatan' => $row['catatan_item'] ?: null
                ]);

                // 3. Insert ke riwayat_stok ledger
                $stmtRiwayat->execute([
                    'item_id' => $itemId,
                    'tipe_mutasi' => $tipeMutasiRiwayat,
                    'qty' => $qtyPerubahan,
                    'sebelum' => $stokDbSaatIni,
                    'sesudah' => $stokFisikInput,
                    'ref_id' => $opnameId,
                    'ket' => $ketMutasi,
                    'user_id' => $userId
                ]);
            }

            // Update header totals
            $pdo->prepare("
                UPDATE public.opname_gudang 
                SET total_item_selisih = :count_selisih,
                    total_qty_masuk = :masuk,
                    total_qty_keluar = :keluar,
                    total_nilai_selisih_rp = :total_rp
                WHERE id = :id
            ")->execute([
                'count_selisih' => $countSelisih,
                'masuk' => $totalMasuk,
                'keluar' => $totalKeluar,
                'total_rp' => $totalNilaiSelisihRp,
                'id' => $opnameId
            ]);

            ActivityLog::log(
                'logistik',
                'OPNAME',
                "Bulk Opname Gudang #{$nomorDokumen} selesai diproses ({$countSelisih} item disesuaikan: +{$totalMasuk} / -{$totalKeluar} pcs, Net Nilai: Rp " . number_format($totalNilaiSelisihRp, 0, ',', '.') . ").",
                'opname_gudang',
                $opnameId
            );

            $pdo->commit();

            $this->flashSuccess("Bulk Opname #{$nomorDokumen} berhasil disimpan! {$countSelisih} item telah disesuaikan dan dicatat di buku besar.");
            $this->redirect("/inventory/opname/detail?id={$opnameId}");

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError("Gagal memproses bulk opname: " . $e->getMessage());
            $this->redirect('/inventory/bulk-opname');
        }
    }

    /**
     * API AJAX untuk intip riwayat mutasi stok produk (Kartu Stok Mini)
     */
    public function apiItemHistory(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            Auth::requirePermission('inventory.view_all');

            $itemId = (string)($this->input('item_id') ?? '');
            if (empty($itemId)) {
                echo json_encode(['success' => false, 'message' => 'Item ID tidak valid.']);
                exit;
            }

            $item = Database::fetchOne("
                SELECT i.id, i.kode_sku, gp.barcode_universal, i.nama_item, i.stok_fisik_saat_ini, i.satuan_dasar, i.harga_pokok_pembelian
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE i.id = :id
            ", ['id' => $itemId]);

            if (!$item) {
                echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan.']);
                exit;
            }

            $history = Database::fetchAll("
                SELECT rs.id, rs.tipe_mutasi, rs.jumlah_perubahan, rs.stok_sebelum, rs.stok_sesudah,
                       rs.referensi_tabel, rs.referensi_id, rs.keterangan, rs.dibuat_pada,
                       p.nama_lengkap as nama_user
                FROM public.riwayat_stok rs
                LEFT JOIN public.pengguna p ON rs.dibuat_oleh = p.id
                WHERE rs.item_id = :id
                ORDER BY rs.dibuat_pada DESC, rs.id DESC
                LIMIT 30
            ", ['id' => $itemId]);

            echo json_encode([
                'success' => true,
                'item' => $item,
                'history' => $history
            ]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Halaman Rincian Dokumen Sesi Opname Gudang
     */
    public function opnameDetail(): void
    {
        Auth::requirePermission('inventory.view_all');

        $id = (string)($this->input('id') ?? '');
        if (empty($id)) {
            $this->flashError('ID dokumen opname tidak valid.');
            $this->redirect('/inventory');
            return;
        }

        try {
            $opname = Database::fetchOne("
                SELECT og.*, p.nama_lengkap as nama_pembuat, p.nama_pengguna as username_pembuat
                FROM public.opname_gudang og
                LEFT JOIN public.pengguna p ON og.dibuat_oleh = p.id
                WHERE og.id = :id
            ", ['id' => $id]);

            if (!$opname) {
                $this->flashError('Dokumen opname tidak ditemukan.');
                $this->redirect('/inventory');
                return;
            }

            $items = Database::fetchAll("
                SELECT ogi.*, i.kode_sku, gp.barcode_universal, i.nama_item, i.satuan_dasar,
                       i.harga_pokok_pembelian,
                       COALESCE(NULLIF(ogi.harga_pokok_saat_opname, 0), i.harga_pokok_pembelian, 0) as hpp_efektif,
                       COALESCE(NULLIF(ogi.subtotal_nilai_selisih, 0), (ogi.selisih * COALESCE(NULLIF(ogi.harga_pokok_saat_opname, 0), i.harga_pokok_pembelian, 0))) as subtotal_rp,
                       gp.nama_grup, gp.kode_grup
                FROM public.opname_gudang_item ogi
                JOIN public.item i ON ogi.item_id = i.id
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE ogi.opname_id = :id
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ", ['id' => $id]);

            $this->view('inventory.opname_detail', [
                'pageTitle' => 'Dokumen Opname #' . $opname['nomor_dokumen'],
                'pageSubtitle' => 'Rincian Penyesuaian Fisik Gudang & Bukti Mutasi',
                'opname' => $opname,
                'items' => $items
            ]);
        } catch (Throwable $e) {
            $this->flashError("Gagal memuat dokumen opname: " . $e->getMessage());
            $this->redirect('/inventory');
        }
    }

    /**
     * Halaman Riwayat Seluruh Sesi Opname Gudang
     */
    public function opnameHistory(): void
    {
        Auth::requirePermission('inventory.view_all');

        $tglMulai = trim((string)($this->input('tanggal_mulai') ?? ''));
        $tglSelesai = trim((string)($this->input('tanggal_selesai') ?? ''));

        try {
            $where = ["1=1"];
            $params = [];
            if (!empty($tglMulai)) {
                $where[] = "og.tanggal >= :tgl_mulai";
                $params['tgl_mulai'] = $tglMulai;
            }
            if (!empty($tglSelesai)) {
                $where[] = "og.tanggal <= :tgl_selesai";
                $params['tgl_selesai'] = $tglSelesai;
            }
            $whereSql = implode(" AND ", $where);

            $history = Database::fetchAll("
                SELECT og.*, p.nama_lengkap as nama_pembuat
                FROM public.opname_gudang og
                LEFT JOIN public.pengguna p ON og.dibuat_oleh = p.id
                WHERE {$whereSql}
                ORDER BY og.tanggal DESC, og.dibuat_pada DESC
                LIMIT 250
            ", $params);

            $this->view('inventory.opname_history', [
                'pageTitle' => 'Riwayat Dokumen Opname Gudang',
                'pageSubtitle' => 'Arsip Seluruh Sesi Penyesuaian Fisik & Audit Stok Gudang',
                'history' => $history,
                'tglMulai' => $tglMulai,
                'tglSelesai' => $tglSelesai
            ]);
        } catch (Throwable $e) {
            $this->flashError("Gagal memuat riwayat opname: " . $e->getMessage());
            $this->redirect('/inventory');
        }
    }

    /**
     * Export Dokumen Bukti Opname ke Format PDF (Dompdf)
     */
    public function exportOpnamePdf(): void
    {
        Auth::requirePermission('inventory.view_all');

        $id = (string)($this->input('id') ?? '');
        if (empty($id)) {
            $this->flashError('ID dokumen tidak valid.');
            $this->redirect('/inventory');
            return;
        }

        try {
            $opname = Database::fetchOne("
                SELECT og.*, p.nama_lengkap as nama_pembuat
                FROM public.opname_gudang og
                LEFT JOIN public.pengguna p ON og.dibuat_oleh = p.id
                WHERE og.id = :id
            ", ['id' => $id]);

            if (!$opname) {
                $this->flashError('Dokumen opname tidak ditemukan.');
                $this->redirect('/inventory');
                return;
            }

            $items = Database::fetchAll("
                SELECT ogi.*, i.kode_sku, gp.barcode_universal, i.nama_item, i.satuan_dasar,
                       i.harga_pokok_pembelian,
                       COALESCE(NULLIF(ogi.harga_pokok_saat_opname, 0), i.harga_pokok_pembelian, 0) as hpp_efektif,
                       COALESCE(NULLIF(ogi.subtotal_nilai_selisih, 0), (ogi.selisih * COALESCE(NULLIF(ogi.harga_pokok_saat_opname, 0), i.harga_pokok_pembelian, 0))) as subtotal_rp,
                       gp.nama_grup, gp.kode_grup
                FROM public.opname_gudang_item ogi
                JOIN public.item i ON ogi.item_id = i.id
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE ogi.opname_id = :id
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ", ['id' => $id]);

            $company = CompanySetting::getAll();

            ob_start();
            require dirname(__DIR__, 2) . '/views/inventory/pdf_opname.php';
            $html = ob_get_clean();

            $filename = "Opname-{$opname['nomor_dokumen']}.pdf";
            PdfExport::download($html, $filename, 'A4', 'portrait');
        } catch (Throwable $e) {
            $this->flashError("Gagal mencetak PDF opname: " . $e->getMessage());
            $this->redirect("/inventory/opname/detail?id={$id}");
        }
    }

    /**
     * Export Rincian Dokumen Opname ke Excel
     */
    public function exportOpnameExcel(): void
    {
        Auth::requirePermission('inventory.view_all');

        $id = (string)($this->input('id') ?? '');
        if (empty($id)) {
            $this->flashError('ID dokumen tidak valid.');
            $this->redirect('/inventory');
            return;
        }

        try {
            $opname = Database::fetchOne("
                SELECT og.*, p.nama_lengkap as nama_pembuat
                FROM public.opname_gudang og
                LEFT JOIN public.pengguna p ON og.dibuat_oleh = p.id
                WHERE og.id = :id
            ", ['id' => $id]);

            if (!$opname) {
                $this->flashError('Dokumen opname tidak ditemukan.');
                $this->redirect('/inventory');
                return;
            }

            $items = Database::fetchAll("
                SELECT ogi.*, i.kode_sku, gp.barcode_universal, i.nama_item, i.satuan_dasar,
                       i.harga_pokok_pembelian,
                       COALESCE(NULLIF(ogi.harga_pokok_saat_opname, 0), i.harga_pokok_pembelian, 0) as hpp_efektif,
                       COALESCE(NULLIF(ogi.subtotal_nilai_selisih, 0), (ogi.selisih * COALESCE(NULLIF(ogi.harga_pokok_saat_opname, 0), i.harga_pokok_pembelian, 0))) as subtotal_rp,
                       gp.nama_grup, gp.kode_grup
                FROM public.opname_gudang_item ogi
                JOIN public.item i ON ogi.item_id = i.id
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE ogi.opname_id = :id
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ", ['id' => $id]);

            $company = CompanySetting::getAll();
            ExcelExport::downloadOpnameDocument($opname, $items, $company);
        } catch (Throwable $e) {
            $this->flashError("Gagal export Excel opname: " . $e->getMessage());
            $this->redirect("/inventory/opname/detail?id={$id}");
        }
    }
}

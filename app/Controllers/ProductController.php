<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use PDO;
use App\Helpers\ActivityLog;
use Throwable;

/**
 * app/Controllers/ProductController.php
 * Pengendali Master Data Terpadu:
 * 1. Katalog Barang Jadi (Finished Goods - Hasil Repacking/Assembly)
 * 2. Master Bahan Baku & Kemasan (Raw Materials & Packaging)
 * 3. Master Resep & Komposisi (Bill of Materials / BOM)
 */
class ProductController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission('master.products_view');
    }

    public function index(): void
    {
        try {
            // 1. Grup Kemasan Luar (Barcode Universal)
            $groups = Database::fetchAll("
                SELECT gp.id, gp.kode_grup, gp.nama_grup, gp.barcode_universal,
                       gp.satuan_dasar, gp.status_aktif,
                       COUNT(i.id) as total_sku
                FROM public.grup_produk gp
                LEFT JOIN public.item i ON gp.id = i.grup_id AND i.status_aktif = TRUE AND i.tipe_item = 'barang_jadi'
                GROUP BY gp.id
                ORDER BY gp.status_aktif DESC, gp.kode_grup ASC
            ");

            // 2. Katalog Barang Jadi (Finished Goods dengan Paginasi Server)
            $qFg = trim((string)$this->input('q_fg', $this->input('q', '')));
            $groupIdFg = trim((string)$this->input('group_id', ''));
            $pageFg = max(1, (int)$this->input('page_fg', $this->input('page', 1)));
            $perPageFg = max(10, min(200, (int)$this->input('per_page_fg', 50)));
            $offsetFg = ($pageFg - 1) * $perPageFg;

            $whereFg = "WHERE i.tipe_item = 'barang_jadi'";
            $paramsFg = [];
            if (!empty($qFg)) {
                $whereFg .= " AND (i.nama_item ILIKE :q OR i.kode_sku ILIKE :q OR gp.barcode_universal ILIKE :q OR gp.nama_grup ILIKE :q)";
                $paramsFg['q'] = "%{$qFg}%";
            }
            if (!empty($groupIdFg) && $groupIdFg !== 'all') {
                $whereFg .= " AND i.grup_id = :gid";
                $paramsFg['gid'] = $groupIdFg;
            }

            $countFg = (int)(Database::fetchOne("
                SELECT COUNT(*) as total
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                {$whereFg}
            ", $paramsFg)['total'] ?? 0);
            $totalPagesFg = max(1, (int)ceil($countFg / $perPageFg));

            $totalAllFinishedGoods = (int)(Database::fetchOne("
                SELECT COUNT(*) as total FROM public.item WHERE tipe_item = 'barang_jadi'
            ")['total'] ?? 0);

            $finishedGoods = Database::fetchAll("
                SELECT i.id, i.grup_id, i.kode_sku, i.nama_item,
                       i.tipe_item, i.satuan_dasar, i.harga_pokok_pembelian,
                       i.stok_fisik_saat_ini, i.stok_minimum_peringatan, i.status_jual, i.status_aktif,
                       i.kelompok_borongan_id, i.upah_per_bungkus,
                       COALESCE(i.upah_per_bungkus, kub.upah_per_bungkus, 0) as upah_bungkus_efektif,
                       gp.nama_grup, gp.kode_grup, gp.barcode_universal,
                       gphl.harga_jual_pcs as harga_jual_ritel,
                       kub.nama_kelompok, kub.upah_per_bungkus as kelompok_upah_bungkus,
                       (SELECT COUNT(*) FROM public.komposisi_item ki WHERE ki.item_jadi_id = i.id) as total_resep_bahan,
                       COALESCE((
                           SELECT SUM(ki.jumlah_kebutuhan * mat.harga_pokok_pembelian)
                           FROM public.komposisi_item ki
                           JOIN public.item mat ON ki.item_bahan_id = mat.id
                           WHERE ki.item_jadi_id = i.id
                       ), 0) as estimasi_biaya_bahan
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                LEFT JOIN public.grup_produk_harga_level gphl ON gphl.grup_produk_id = i.grup_id AND gphl.level_harga = 1
                LEFT JOIN public.kelompok_upah_borongan kub ON i.kelompok_borongan_id = kub.id
                {$whereFg}
                ORDER BY i.status_aktif DESC, gp.kode_grup ASC, i.nama_item ASC
                LIMIT {$perPageFg} OFFSET {$offsetFg}
            ", $paramsFg);

            // Daftar lengkap seluruh barang jadi aktif (untuk dropdown seleksi Resep BOM Tab 3 agar tidak terpotong paginasi)
            $allFinishedGoodsList = Database::fetchAll("
                SELECT i.id, i.kode_sku, i.nama_item, i.grup_id, i.satuan_dasar,
                       i.harga_pokok_pembelian, i.upah_per_bungkus,
                       COALESCE(i.upah_per_bungkus, kub.upah_per_bungkus, 0) as upah_bungkus_efektif,
                       gp.nama_grup, gp.barcode_universal,
                       gphl.harga_jual_pcs as harga_jual_ritel,
                       (SELECT COUNT(*) FROM public.komposisi_item ki WHERE ki.item_jadi_id = i.id) as total_resep_bahan,
                       COALESCE((
                           SELECT SUM(ki.jumlah_kebutuhan * mat.harga_pokok_pembelian)
                           FROM public.komposisi_item ki
                           JOIN public.item mat ON ki.item_bahan_id = mat.id
                           WHERE ki.item_jadi_id = i.id
                       ), 0) as estimasi_biaya_bahan
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                LEFT JOIN public.grup_produk_harga_level gphl ON gphl.grup_produk_id = i.grup_id AND gphl.level_harga = 1
                LEFT JOIN public.kelompok_upah_borongan kub ON i.kelompok_borongan_id = kub.id
                WHERE i.tipe_item = 'barang_jadi' AND i.status_aktif = TRUE
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ");

            // 3. Master Bahan Baku Curah & Bahan Kemasan
            $materials = Database::fetchAll("
                SELECT i.id, i.kode_sku, i.nama_item, i.tipe_item, i.satuan_dasar,
                       i.harga_pokok_pembelian, i.stok_fisik_saat_ini, i.stok_minimum_peringatan,
                       i.pemasok_utama_id, i.status_aktif,
                       sup.nama_pemasok, sup.kode_pemasok,
                       (SELECT COUNT(*) FROM public.komposisi_item ki WHERE ki.item_bahan_id = i.id) as dipakai_di_resep
                FROM public.item i
                LEFT JOIN public.pemasok sup ON i.pemasok_utama_id = sup.id
                WHERE i.tipe_item IN ('bahan_mentah', 'bahan_kemas')
                ORDER BY i.tipe_item ASC, i.nama_item ASC
            ");

            // 4. Master Resep / BOM (Komposisi Item)
            $recipes = Database::fetchAll("
                SELECT ki.id, ki.item_jadi_id, ki.item_bahan_id, ki.jumlah_kebutuhan,
                       ij.nama_item as item_jadi_nama, ij.kode_sku as item_jadi_sku,
                       ib.nama_item as item_bahan_nama, ib.kode_sku as item_bahan_sku,
                       ib.satuan_dasar as item_bahan_satuan, ib.tipe_item as item_bahan_tipe,
                       ib.harga_pokok_pembelian as item_bahan_hpp,
                       ROUND((ki.jumlah_kebutuhan * ib.harga_pokok_pembelian)::numeric, 2) as subtotal_biaya_bahan
                FROM public.komposisi_item ki
                JOIN public.item ij ON ki.item_jadi_id = ij.id
                JOIN public.item ib ON ki.item_bahan_id = ib.id
                ORDER BY ij.nama_item ASC, ib.tipe_item ASC
            ");

            // Grouping recipes by item_jadi_id
            $recipesByFinishedGood = [];
            foreach ($recipes as $r) {
                $recipesByFinishedGood[$r['item_jadi_id']][] = $r;
            }

            // 5. Data Referensi (Tarif Borongan & Pemasok)
            $wageGroups = Database::fetchAll("
                SELECT kub.id, kub.nama_kelompok, kub.upah_per_bungkus, kub.keterangan, kub.status_aktif,
                       (SELECT COUNT(*) FROM public.item i WHERE i.kelompok_borongan_id = kub.id AND i.status_aktif = TRUE) as total_sku_terhubung
                FROM public.kelompok_upah_borongan kub
                ORDER BY kub.upah_per_bungkus DESC, kub.nama_kelompok ASC
            ");

            $suppliers = Database::fetchAll("
                SELECT id, kode_pemasok, nama_pemasok 
                FROM public.pemasok 
                WHERE status_aktif = TRUE 
                ORDER BY nama_pemasok ASC
            ");

            $selectedRecipeItemId = trim((string)$this->input('item_id', ''));

            $this->view('products.index', [
                'pageTitle' => 'Master Produk, Bahan & Resep',
                'pageSubtitle' => 'Katalog Barang Jadi, Bahan Baku Curah, Kemasan & Resep BOM',
                'groups' => $groups,
                'finishedGoods' => $finishedGoods,
                'allFinishedGoodsList' => $allFinishedGoodsList,
                'materials' => $materials,
                'recipes' => $recipes,
                'recipesByFinishedGood' => $recipesByFinishedGood,
                'wageGroups' => $wageGroups,
                'suppliers' => $suppliers,
                'totalAllFinishedGoods' => $totalAllFinishedGoods,
                'selectedGroupId' => $groupIdFg,
                'selectedRecipeItemId' => $selectedRecipeItemId,
                'paginationFg' => [
                    'page' => $pageFg,
                    'perPage' => $perPageFg,
                    'total' => $countFg,
                    'totalPages' => $totalPagesFg,
                    'q' => $qFg,
                    'group_id' => $groupIdFg
                ]
            ]);

        } catch (Throwable $e) {
            $this->flashError("Terjadi kesalahan saat memuat data produk: " . $e->getMessage());
            $this->view('products.index', [
                'pageTitle' => 'Master Produk, Bahan & Resep',
                'pageSubtitle' => 'Katalog Barang Jadi, Bahan Baku Curah, Kemasan & Resep BOM',
                'groups' => [],
                'finishedGoods' => [],
                'materials' => [],
                'recipes' => [],
                'recipesByFinishedGood' => [],
                'wageGroups' => [],
                'suppliers' => [],
                'paginationFg' => [
                    'page' => 1,
                    'perPage' => 50,
                    'total' => 0,
                    'totalPages' => 1,
                    'q' => ''
                ]
            ]);
        }
    }

    // ==========================================
    // 1. GRUP KEMASAN (BARCODE UNIVERSAL)
    // ==========================================
    public function storeGroup(): void
    {
        Auth::requirePermission('master.products_manage');

        $nama = trim((string)$this->input('nama_grup'));
        $barcode = trim((string)$this->input('barcode_universal'));
        $rawHarga = $this->input('harga_ritel_l1', $this->input('harga_jual_pcs', '15000'));
        $hargaL1 = (float)preg_replace('/[^0-9]/', '', (string)$rawHarga);
        if ($hargaL1 <= 0) {
            $hargaL1 = 15000.0;
        }

        if (empty($nama)) {
            $this->flashError('Nama grup kemasan wajib diisi.');
            $this->redirect('/products');
            return;
        }

        try {
            $maxNum = (int)(Database::fetchOne("
                SELECT COALESCE(MAX(NULLIF(regexp_replace(kode_grup, '^GRP-', ''), '')::integer), 0) as max_num
                FROM public.grup_produk
                WHERE kode_grup ~ '^GRP-[0-9]+$'
            ")['max_num'] ?? 0);
            $kode = 'GRP-' . str_pad((string)($maxNum + 1), 3, '0', STR_PAD_LEFT);

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO public.grup_produk (
                    kode_grup, nama_grup, barcode_universal, satuan_dasar, status_aktif
                ) VALUES (
                    :kode, :nama, :barcode, 'pcs', TRUE
                ) RETURNING id
            ");
            $stmt->execute(['kode' => $kode, 'nama' => $nama, 'barcode' => $barcode ?: null]);
            $grupId = $stmt->fetchColumn();

            // Insert level harga default bawaan (Hanya Level 1 Ritel Standar murni per pcs)
            $pdo->prepare("
                INSERT INTO public.grup_produk_harga_level (grup_produk_id, level_harga, harga_jual_pcs, dibuat_pada, diubah_pada)
                VALUES 
                (:id, 1, :harga, NOW(), NOW())
                ON CONFLICT (grup_produk_id, level_harga) DO UPDATE SET harga_jual_pcs = EXCLUDED.harga_jual_pcs
            ")->execute(['id' => $grupId, 'harga' => $hargaL1]);

            $pdo->commit();

            ActivityLog::log('master_data', 'Tambah Grup Kemasan', "Grup kemasan {$nama} ({$kode}) berhasil ditambahkan.");

            $this->flashSuccess("Grup kemasan {$nama} ({$kode}) berhasil ditambahkan!");
            $this->redirect('/products');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $this->flashError('Gagal menambahkan grup: ' . $e->getMessage());
            $this->redirect('/products');
        }
    }

    public function updateGroup(): void
    {
        Auth::requirePermission('master.products_manage');

        $id = $this->input('id');
        $nama = trim((string)$this->input('nama_grup'));
        $barcode = trim((string)$this->input('barcode_universal'));
        $statusAktif = !empty($this->input('status_aktif'));

        if (empty($id) || empty($nama)) {
            $this->flashError('ID dan Nama grup kemasan wajib diisi.');
            $this->redirect('/products');
            return;
        }

        try {
            Database::execute("
                UPDATE public.grup_produk SET
                    nama_grup = :nama,
                    barcode_universal = :barcode,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id
            ", [
                'id' => $id,
                'nama' => $nama,
                'barcode' => $barcode ?: null,
                'aktif' => $statusAktif ? 'true' : 'false'
            ]);

            ActivityLog::log('master_data', 'Update Grup Kemasan', "Grup kemasan {$nama} berhasil diperbarui.");
            $this->flashSuccess("Grup kemasan {$nama} berhasil diperbarui!");
            $this->redirect('/products');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui grup: ' . $e->getMessage());
            $this->redirect('/products');
        }
    }

    public function deleteGroup(): void
    {
        Auth::requirePermission('master.products_manage');

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID grup tidak valid.');
            $this->redirect('/products');
            return;
        }

        try {
            $linkedItems = (int)(Database::fetchOne("
                SELECT COUNT(*) as total FROM public.item WHERE grup_id = :id
            ", ['id' => $id])['total'] ?? 0);

            if ($linkedItems > 0) {
                $this->flashError("Grup kemasan ini tidak dapat dihapus karena masih menaungi {$linkedItems} SKU barang jadi.");
                $this->redirect('/products');
                return;
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $pdo->prepare("DELETE FROM public.grup_produk_harga_level WHERE grup_produk_id = :id")->execute(['id' => $id]);
            $pdo->prepare("DELETE FROM public.grup_produk WHERE id = :id")->execute(['id' => $id]);

            $pdo->commit();

            ActivityLog::log('master_data', 'Hapus Grup Kemasan', "Grup kemasan ID {$id} berhasil dihapus.");
            $this->flashSuccess('Grup kemasan berhasil dihapus.');
            $this->redirect('/products');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menghapus grup: ' . $e->getMessage());
            $this->redirect('/products');
        }
    }

    // ==========================================
    // 2. BARANG JADI (FINISHED GOODS)
    // ==========================================
    public function storeItem(): void
    {
        Auth::requirePermission('master.products_manage');

        $grupId = $this->input('grup_id') ?: null;
        $namaItem = trim((string)$this->input('nama_item'));
        $hpp = (float)preg_replace('/[^0-9]/', '', (string)$this->input('harga_pokok_pembelian', '0'));
        $stokMin = (int)$this->input('stok_minimum_peringatan', 10);
        $stokAwal = (int)$this->input('stok_awal', 0);
        $kelompokBoronganId = $this->input('kelompok_borongan_id') ?: null;
        $rawUpah = $this->input('upah_per_bungkus');
        $upahPerBungkus = ($rawUpah !== null && $rawUpah !== '') ? (float)preg_replace('/[^0-9]/', '', (string)$rawUpah) : null;

        if (empty($namaItem)) {
            $this->flashError('Nama barang jadi wajib diisi.');
            $this->redirect('/products');
            return;
        }

        if (empty($grupId)) {
            $this->flashError('Grup kemasan wajib dipilih untuk barang jadi.');
            $this->redirect('/products');
            return;
        }

        try {
            $maxSku = (int)(Database::fetchOne("
                SELECT COALESCE(MAX(NULLIF(regexp_replace(kode_sku, '^SUB-', ''), '')::integer), 0) as max_sku
                FROM public.item WHERE kode_sku ~ '^SUB-[0-9]+$'
            ")['max_sku'] ?? 0);
            $kodeSku = 'SUB-' . str_pad((string)($maxSku + 1), 4, '0', STR_PAD_LEFT);

            $pdo = Database::pdo();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO public.item (
                    grup_id, kode_sku, nama_item, tipe_item,
                    satuan_dasar, satuan_distribusi, kelompok_borongan_id, upah_per_bungkus, pemasok_utama_id,
                    harga_pokok_pembelian, stok_minimum_peringatan, stok_fisik_saat_ini,
                    status_jual, status_aktif
                ) VALUES (
                    :grup, :sku, :nama, 'barang_jadi',
                    'pcs', 'bal', :borongan, :upah, NULL,
                    :hpp, :stok_min, :stok_awal,
                    TRUE, TRUE
                ) RETURNING id
            ");
            $stmt->execute([
                'grup' => $grupId,
                'sku' => $kodeSku,
                'nama' => $namaItem,
                'borongan' => $kelompokBoronganId,
                'upah' => $upahPerBungkus,
                'hpp' => $hpp,
                'stok_min' => $stokMin,
                'stok_awal' => $stokAwal
            ]);
            $newItem = $stmt->fetch(PDO::FETCH_ASSOC);
            $newItemId = $newItem['id'] ?? null;

            if ($newItemId && $stokAwal > 0) {
                $stmtHistory = $pdo->prepare("
                    INSERT INTO public.riwayat_stok (
                        item_id, tipe_mutasi, jumlah_perubahan,
                        stok_sebelum, stok_sesudah, referensi_tabel, referensi_id,
                        keterangan, dibuat_oleh, dibuat_pada
                    ) VALUES (
                        :item_id, 'penyesuaian_opname_tambah', :jumlah,
                        0, :stok_sesudah, 'item', :ref_id,
                        :keterangan, :dibuat_oleh, NOW()
                    )
                ");
                $stmtHistory->execute([
                    'item_id' => $newItemId,
                    'jumlah' => $stokAwal,
                    'stok_sesudah' => $stokAwal,
                    'ref_id' => $newItemId,
                    'keterangan' => 'Saldo awal registrasi produk baru ' . $namaItem,
                    'dibuat_oleh' => Auth::id()
                ]);
            }

            $pdo->commit();

            ActivityLog::log('master_data', 'Tambah Produk Baru', "Produk {$namaItem} ({$kodeSku}) berhasil ditambahkan dengan stok awal {$stokAwal}");

            $this->flashSuccess("Barang Jadi {$namaItem} berhasil ditambahkan!");
            $this->redirect('/products');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menambahkan Barang Jadi: ' . $e->getMessage());
            $this->redirect('/products');
        }
    }

    public function updateItem(): void
    {
        Auth::requirePermission('master.products_manage');

        $id = $this->input('id');
        $grupId = $this->input('grup_id') ?: null;
        $namaItem = trim((string)$this->input('nama_item'));
        $hpp = (float)preg_replace('/[^0-9]/', '', (string)$this->input('harga_pokok_pembelian', '0'));
        $stokMin = (int)$this->input('stok_minimum_peringatan', 10);
        $kelompokBoronganId = $this->input('kelompok_borongan_id') ?: null;
        $rawUpah = $this->input('upah_per_bungkus');
        $upahPerBungkus = ($rawUpah !== null && $rawUpah !== '') ? (float)preg_replace('/[^0-9]/', '', (string)$rawUpah) : null;
        $statusJual = !empty($this->input('status_jual'));
        $statusAktif = !empty($this->input('status_aktif'));

        if (empty($id) || empty($namaItem)) {
            $this->flashError('Parameter tidak lengkap.');
            $this->redirect('/products');
            return;
        }

        if (empty($grupId)) {
            $this->flashError('Grup kemasan wajib dipilih untuk barang jadi.');
            $this->redirect('/products');
            return;
        }

        try {
            Database::execute("
                UPDATE public.item SET
                    grup_id = :grup,
                    nama_item = :nama,
                    harga_pokok_pembelian = :hpp,
                    stok_minimum_peringatan = :stok_min,
                    kelompok_borongan_id = :borongan,
                    upah_per_bungkus = :upah,
                    status_jual = :jual,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id AND tipe_item = 'barang_jadi'
            ", [
                'id' => $id,
                'grup' => $grupId,
                'nama' => $namaItem,
                'hpp' => $hpp,
                'stok_min' => $stokMin,
                'borongan' => $kelompokBoronganId,
                'upah' => $upahPerBungkus,
                'jual' => $statusJual ? 'true' : 'false',
                'aktif' => $statusAktif ? 'true' : 'false'
            ]);

            ActivityLog::log('master_data', 'Update Barang Jadi', "Barang Jadi {$namaItem} berhasil diperbarui.");
            $this->flashSuccess("Barang Jadi {$namaItem} berhasil diperbarui!");
            $this->redirect('/products');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui Barang Jadi: ' . $e->getMessage());
            $this->redirect('/products');
        }
    }

    public function deleteItem(): void
    {
        Auth::requirePermission('master.products_manage');

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID barang jadi tidak valid.');
            $this->redirect('/products');
            return;
        }

        try {
            $item = Database::fetchOne("SELECT id, kode_sku, nama_item FROM public.item WHERE id = :id AND tipe_item = 'barang_jadi'", ['id' => $id]);
            if (!$item) {
                $this->flashError('Barang jadi tidak ditemukan.');
                $this->redirect('/products');
                return;
            }

            // Cek riwayat transaksi riil operasional
            $usedOrders = (int)(Database::fetchOne("SELECT count(*) as total FROM public.item_pesanan WHERE item_id = :id", ['id' => $id])['total'] ?? 0);
            $usedPurchases = (int)(Database::fetchOne("SELECT count(*) as total FROM public.rincian_pembelian WHERE item_id = :id", ['id' => $id])['total'] ?? 0);
            $usedConsignment = (int)(Database::fetchOne("SELECT count(*) as total FROM public.stok_konsinyasi_toko WHERE item_id = :id", ['id' => $id])['total'] ?? 0);
            $usedConsignmentVisit = (int)(Database::fetchOne("SELECT count(*) as total FROM public.rincian_kunjungan_konsinyasi WHERE item_id = :id", ['id' => $id])['total'] ?? 0);
            $usedProduction = (int)(Database::fetchOne("SELECT count(*) as total FROM public.produksi_harian WHERE item_id = :id", ['id' => $id])['total'] ?? 0);
            $usedAdjustment = (int)(Database::fetchOne("SELECT count(*) as total FROM public.penyesuaian_stok WHERE item_id = :id", ['id' => $id])['total'] ?? 0);
            $usedOpname = (int)(Database::fetchOne("SELECT count(*) as total FROM public.opname_gudang_item WHERE item_id = :id", ['id' => $id])['total'] ?? 0);
            $usedTarget = (int)(Database::fetchOne("SELECT count(*) as total FROM public.target_produksi WHERE item_id = :id", ['id' => $id])['total'] ?? 0);

            // Cek mutasi stok selain saldo awal registrasi
            $otherStockMutations = (int)(Database::fetchOne("
                SELECT count(*) as total 
                FROM public.riwayat_stok 
                WHERE item_id = :id AND NOT (referensi_tabel = 'item' AND referensi_id = :id)
            ", ['id' => $id])['total'] ?? 0);

            $hasHistory = ($usedOrders + $usedPurchases + $usedConsignment + $usedConsignmentVisit + $usedProduction + $usedAdjustment + $usedOpname + $usedTarget + $otherStockMutations) > 0;

            if ($hasHistory) {
                $this->flashError("Barang jadi '{$item['nama_item']}' sudah memiliki riwayat transaksi/stok operasional dan tidak dapat dihapus demi keutuhan data. Silakan edit dan ubah status menjadi Nonaktif.");
                $this->redirect('/products');
                return;
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Bersihkan saldo awal registrasi jika ada
            $pdo->prepare("DELETE FROM public.riwayat_stok WHERE item_id = :id AND referensi_tabel = 'item' AND referensi_id = :id")->execute(['id' => $id]);
            // Bersihkan dari resep (jika ada)
            $pdo->prepare("DELETE FROM public.komposisi_item WHERE item_jadi_id = :id OR item_bahan_id = :id")->execute(['id' => $id]);
            // Bersihkan dari relasi toko jika ada
            $pdo->prepare("DELETE FROM public.pelanggan_item WHERE item_id = :id")->execute(['id' => $id]);
            // Hapus produk
            $pdo->prepare("DELETE FROM public.item WHERE id = :id AND tipe_item = 'barang_jadi'")->execute(['id' => $id]);

            $pdo->commit();

            ActivityLog::log('master_data', 'Hapus Barang Jadi', "Barang jadi {$item['nama_item']} ({$item['kode_sku']}) berhasil dihapus permanen.");
            $this->flashSuccess("Barang Jadi '{$item['nama_item']}' berhasil dihapus.");
            $this->redirect('/products');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menghapus Barang Jadi: ' . $e->getMessage());
            $this->redirect('/products');
        }
    }

    // ==========================================
    // 3. BAHAN BAKU & KEMASAN (RAW MATERIALS)
    // ==========================================
    public function storeMaterial(): void
    {
        Auth::requirePermission(['master.materials_manage', 'master.products_manage']);

        $namaItem = trim((string)$this->input('nama_item'));
        $tipeItem = in_array($this->input('tipe_item'), ['bahan_mentah', 'bahan_kemas'], true) ? $this->input('tipe_item') : 'bahan_mentah';
        $satuanDasar = trim((string)$this->input('satuan_dasar', 'kg'));
        $pemasokId = $this->input('pemasok_utama_id') ?: null;
        $hpp = (float)preg_replace('/[^0-9]/', '', (string)$this->input('harga_pokok_pembelian', '0'));
        $stokMin = (float)$this->input('stok_minimum_peringatan', 10);
        $stokAwal = (float)$this->input('stok_awal', 0);

        if (empty($namaItem)) {
            $this->flashError('Nama bahan baku / kemasan wajib diisi.');
            $this->redirect('/products?tab=materials');
            return;
        }

        try {
            $prefix = ($tipeItem === 'bahan_mentah') ? 'BAHAN-' : 'KMAS-';
            $maxSku = (int)(Database::fetchOne("
                SELECT COALESCE(MAX(NULLIF(regexp_replace(kode_sku, '^' || :prefix, ''), '')::integer), 0) as max_sku
                FROM public.item WHERE kode_sku ~ ('^' || :prefix || '[0-9]+$')
            ", ['prefix' => $prefix])['max_sku'] ?? 0);
            $kodeSku = $prefix . str_pad((string)($maxSku + 1), 4, '0', STR_PAD_LEFT);

            $pdo = Database::pdo();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO public.item (
                    kode_sku, nama_item, tipe_item, satuan_dasar,
                    pemasok_utama_id, harga_pokok_pembelian, stok_minimum_peringatan,
                    stok_fisik_saat_ini, status_jual, status_aktif
                ) VALUES (
                    :sku, :nama, :tipe, :satuan,
                    :pemasok, :hpp, :stok_min,
                    :stok_awal, FALSE, TRUE
                ) RETURNING id
            ");
            $stmt->execute([
                'sku' => $kodeSku,
                'nama' => $namaItem,
                'tipe' => $tipeItem,
                'satuan' => $satuanDasar,
                'pemasok' => $pemasokId,
                'hpp' => $hpp,
                'stok_min' => $stokMin,
                'stok_awal' => $stokAwal
            ]);
            $newItem = $stmt->fetch(PDO::FETCH_ASSOC);
            $newItemId = $newItem['id'] ?? null;

            if ($newItemId && $stokAwal > 0) {
                $stmtHistory = $pdo->prepare("
                    INSERT INTO public.riwayat_stok (
                        item_id, tipe_mutasi, jumlah_perubahan,
                        stok_sebelum, stok_sesudah, referensi_tabel, referensi_id,
                        keterangan, dibuat_oleh, dibuat_pada
                    ) VALUES (
                        :item_id, 'penyesuaian_opname_tambah', :jumlah,
                        0, :stok_sesudah, 'item', :ref_id,
                        :keterangan, :dibuat_oleh, NOW()
                    )
                ");
                $stmtHistory->execute([
                    'item_id' => $newItemId,
                    'jumlah' => $stokAwal,
                    'stok_sesudah' => $stokAwal,
                    'ref_id' => $newItemId,
                    'keterangan' => 'Saldo awal registrasi bahan ' . $namaItem,
                    'dibuat_oleh' => Auth::id()
                ]);
            }

            $pdo->commit();

            ActivityLog::log('master_data', 'Tambah Bahan Baru', "Bahan {$namaItem} ({$kodeSku}) berhasil ditambahkan dengan stok awal {$stokAwal}");

            $this->flashSuccess("Bahan {$namaItem} ({$kodeSku}) berhasil ditambahkan!");
            $this->redirect('/products?tab=materials');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menambahkan bahan: ' . $e->getMessage());
            $this->redirect('/products?tab=materials');
        }
    }

    public function updateMaterial(): void
    {
        Auth::requirePermission(['master.materials_manage', 'master.products_manage']);

        $id = $this->input('id');
        $namaItem = trim((string)$this->input('nama_item'));
        $tipeItem = in_array($this->input('tipe_item'), ['bahan_mentah', 'bahan_kemas'], true) ? $this->input('tipe_item') : 'bahan_mentah';
        $satuanDasar = trim((string)$this->input('satuan_dasar', 'kg'));
        $pemasokId = $this->input('pemasok_utama_id') ?: null;
        $hpp = (float)preg_replace('/[^0-9]/', '', (string)$this->input('harga_pokok_pembelian', '0'));
        $stokMin = (float)$this->input('stok_minimum_peringatan', 10);
        $statusAktif = !empty($this->input('status_aktif'));

        if (empty($id) || empty($namaItem)) {
            $this->flashError('Parameter tidak lengkap.');
            $this->redirect('/products?tab=materials');
            return;
        }

        try {
            Database::execute("
                UPDATE public.item SET
                    nama_item = :nama,
                    tipe_item = :tipe,
                    satuan_dasar = :satuan,
                    pemasok_utama_id = :pemasok,
                    harga_pokok_pembelian = :hpp,
                    stok_minimum_peringatan = :stok_min,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id AND tipe_item IN ('bahan_mentah', 'bahan_kemas')
            ", [
                'id' => $id,
                'nama' => $namaItem,
                'tipe' => $tipeItem,
                'satuan' => $satuanDasar,
                'pemasok' => $pemasokId,
                'hpp' => $hpp,
                'stok_min' => $stokMin,
                'aktif' => $statusAktif ? 'true' : 'false'
            ]);

            ActivityLog::log('master_data', 'Update Bahan', "Bahan {$namaItem} berhasil diperbarui.");
            $this->flashSuccess("Bahan {$namaItem} berhasil diperbarui!");
            $this->redirect('/products?tab=materials');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui bahan: ' . $e->getMessage());
            $this->redirect('/products?tab=materials');
        }
    }

    public function deleteMaterial(): void
    {
        Auth::requirePermission(['master.materials_manage', 'master.products_manage']);

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID bahan tidak valid.');
            $this->redirect('/products?tab=materials');
            return;
        }

        try {
            $mat = Database::fetchOne("SELECT id, kode_sku, nama_item FROM public.item WHERE id = :id AND tipe_item IN ('bahan_mentah', 'bahan_kemas')", ['id' => $id]);
            if (!$mat) {
                $this->flashError('Bahan baku / kemasan tidak ditemukan.');
                $this->redirect('/products?tab=materials');
                return;
            }

            // 1. Cek apakah dipakai di resep BOM
            $usedRecipes = (int)(Database::fetchOne("SELECT count(*) as total FROM public.komposisi_item WHERE item_bahan_id = :id", ['id' => $id])['total'] ?? 0);
            if ($usedRecipes > 0) {
                $this->flashError("Bahan '{$mat['nama_item']}' tidak dapat dihapus karena sedang digunakan dalam {$usedRecipes} formula resep produk. Hapus keterkaitan resep terlebih dahulu.");
                $this->redirect('/products?tab=materials');
                return;
            }

            // 2. Cek pembelian vendor
            $usedPurchases = (int)(Database::fetchOne("SELECT count(*) as total FROM public.rincian_pembelian WHERE item_id = :id", ['id' => $id])['total'] ?? 0);
            if ($usedPurchases > 0) {
                $this->flashError("Bahan '{$mat['nama_item']}' sudah memiliki riwayat pembelian vendor ({$usedPurchases} transaksi) dan tidak dapat dihapus. Silakan edit dan nonaktifkan statusnya.");
                $this->redirect('/products?tab=materials');
                return;
            }

            // 3. Cek mutasi stok, opname gudang, dan penyesuaian stok
            $usedOpname = (int)(Database::fetchOne("SELECT count(*) as total FROM public.opname_gudang_item WHERE item_id = :id", ['id' => $id])['total'] ?? 0);
            $usedAdjustment = (int)(Database::fetchOne("SELECT count(*) as total FROM public.penyesuaian_stok WHERE item_id = :id", ['id' => $id])['total'] ?? 0);
            $otherMutations = (int)(Database::fetchOne("
                SELECT count(*) as total 
                FROM public.riwayat_stok 
                WHERE item_id = :id AND NOT (referensi_tabel = 'item' AND referensi_id = :id)
            ", ['id' => $id])['total'] ?? 0);

            if ($otherMutations > 0 || $usedOpname > 0 || $usedAdjustment > 0) {
                $this->flashError("Bahan '{$mat['nama_item']}' sudah memiliki riwayat mutasi opname/penyesuaian/produksi dan tidak dapat dihapus. Silakan nonaktifkan statusnya.");
                $this->redirect('/products?tab=materials');
                return;
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Bersihkan saldo awal jika ada
            $pdo->prepare("DELETE FROM public.riwayat_stok WHERE item_id = :id AND referensi_tabel = 'item' AND referensi_id = :id")->execute(['id' => $id]);
            // Hapus bahan
            $pdo->prepare("DELETE FROM public.item WHERE id = :id AND tipe_item IN ('bahan_mentah', 'bahan_kemas')")->execute(['id' => $id]);

            $pdo->commit();

            ActivityLog::log('master_data', 'Hapus Bahan', "Bahan {$mat['nama_item']} ({$mat['kode_sku']}) berhasil dihapus permanen.");
            $this->flashSuccess("Bahan '{$mat['nama_item']}' berhasil dihapus.");
            $this->redirect('/products?tab=materials');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menghapus bahan: ' . $e->getMessage());
            $this->redirect('/products?tab=materials');
        }
    }

    // ==========================================
    // 4. MASTER RESEP (BILL OF MATERIALS / BOM)
    // ==========================================
    public function storeRecipeItem(): void
    {
        Auth::requirePermission('master.products_manage');

        $itemJadiId = $this->input('item_jadi_id');
        $itemBahanId = $this->input('item_bahan_id');
        $jumlahKebutuhan = (float)$this->input('jumlah_kebutuhan', 0);

        if (empty($itemJadiId) || empty($itemBahanId) || $jumlahKebutuhan <= 0) {
            $this->flashError('Pilih barang jadi, bahan baku, dan masukkan jumlah kebutuhan > 0.');
            $this->redirect('/products?tab=recipes' . (!empty($itemJadiId) ? '&item_id=' . urlencode((string)$itemJadiId) : ''));
            return;
        }

        if ($itemJadiId === $itemBahanId) {
            $this->flashError('Barang jadi tidak boleh menjadi bahan baku untuk dirinya sendiri (Circular BOM).');
            $this->redirect('/products?tab=recipes&item_id=' . urlencode((string)$itemJadiId));
            return;
        }

        try {
            // Validasi tipe bahan
            $bahan = Database::fetchOne("SELECT id, nama_item, tipe_item FROM public.item WHERE id = :id", ['id' => $itemBahanId]);
            if (!$bahan || !in_array($bahan['tipe_item'], ['bahan_mentah', 'bahan_kemas'], true)) {
                $this->flashError('Item yang dipilih sebagai komponen harus bertipe bahan mentah atau bahan kemasan.');
                $this->redirect('/products?tab=recipes&item_id=' . urlencode((string)$itemJadiId));
                return;
            }

            Database::execute("
                INSERT INTO public.komposisi_item (
                    item_jadi_id, item_bahan_id, jumlah_kebutuhan, dibuat_pada
                ) VALUES (
                    :jadi, :bahan, :jumlah, NOW()
                )
                ON CONFLICT (item_jadi_id, item_bahan_id)
                DO UPDATE SET 
                    jumlah_kebutuhan = EXCLUDED.jumlah_kebutuhan
            ", [
                'jadi' => $itemJadiId,
                'bahan' => $itemBahanId,
                'jumlah' => $jumlahKebutuhan
            ]);

            ActivityLog::log('master_data', 'Simpan Resep BOM', "Komponen {$bahan['nama_item']} ({$jumlahKebutuhan}) disimpan untuk produk ID {$itemJadiId}");

            $this->flashSuccess('Komposisi resep berhasil disimpan!');
            $this->redirect('/products?tab=recipes&item_id=' . urlencode((string)$itemJadiId));

        } catch (Throwable $e) {
            $this->flashError('Gagal menyimpan resep: ' . $e->getMessage());
            $this->redirect('/products?tab=recipes&item_id=' . urlencode((string)$itemJadiId));
        }
    }

    public function deleteRecipeItem(): void
    {
        Auth::requirePermission('master.products_manage');

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID resep tidak valid.');
            $this->redirect('/products?tab=recipes');
            return;
        }

        try {
            $recipe = Database::fetchOne("SELECT id, item_jadi_id FROM public.komposisi_item WHERE id = :id", ['id' => $id]);
            $itemJadiId = $recipe['item_jadi_id'] ?? '';

            Database::execute("DELETE FROM public.komposisi_item WHERE id = :id", ['id' => $id]);
            $this->flashSuccess('Komponen resep berhasil dihapus.');
            $this->redirect('/products?tab=recipes' . (!empty($itemJadiId) ? '&item_id=' . urlencode((string)$itemJadiId) : ''));

        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus komponen resep: ' . $e->getMessage());
            $this->redirect('/products?tab=recipes');
        }
    }

    public function copyRecipe(): void
    {
        Auth::requirePermission('master.products_manage');

        $sourceId = $this->input('source_item_id');
        $targetIds = $this->input('target_item_ids');

        // Dukung baik array maupun single target_item_id untuk backward compatibility
        if (empty($targetIds)) {
            $singleTarget = $this->input('target_item_id');
            $targetIds = $singleTarget ? [$singleTarget] : [];
        } elseif (!is_array($targetIds)) {
            $targetIds = [$targetIds];
        }

        // Bersihkan array dan buang ID sumber jika ikut tercentang
        $targetIds = array_values(array_filter(array_unique(array_map('trim', $targetIds))));
        $targetIds = array_values(array_diff($targetIds, [$sourceId]));

        if (empty($sourceId) || empty($targetIds)) {
            $this->flashError('Pilih produk sumber resep dan minimal satu produk tujuan.');
            $this->redirect('/products?tab=recipes' . (!empty($sourceId) ? '&item_id=' . urlencode((string)$sourceId) : ''));
            return;
        }

        try {
            $sourceItem = Database::fetchOne("SELECT id, nama_item, kode_sku FROM public.item WHERE id = :id AND tipe_item = 'barang_jadi'", ['id' => $sourceId]);

            if (!$sourceItem) {
                $this->flashError('Produk sumber resep tidak ditemukan.');
                $this->redirect('/products?tab=recipes');
                return;
            }

            $sourceRecipes = Database::fetchAll("SELECT item_bahan_id, jumlah_kebutuhan FROM public.komposisi_item WHERE item_jadi_id = :id", ['id' => $sourceId]);

            if (empty($sourceRecipes)) {
                $this->flashError("Produk sumber '{$sourceItem['nama_item']}' belum memiliki komposisi bahan resep untuk disalin.");
                $this->redirect('/products?tab=recipes&item_id=' . urlencode((string)$sourceId));
                return;
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $delStmt = $pdo->prepare("DELETE FROM public.komposisi_item WHERE item_jadi_id = :target_id");
            $stmt = $pdo->prepare("
                INSERT INTO public.komposisi_item (item_jadi_id, item_bahan_id, jumlah_kebutuhan, dibuat_pada)
                VALUES (:target_id, :bahan_id, :kebutuhan, NOW())
            ");

            foreach ($targetIds as $tId) {
                $delStmt->execute(['target_id' => $tId]);
                foreach ($sourceRecipes as $sr) {
                    $stmt->execute([
                        'target_id' => $tId,
                        'bahan_id' => $sr['item_bahan_id'],
                        'kebutuhan' => $sr['jumlah_kebutuhan']
                    ]);
                }
            }

            $pdo->commit();

            $countTargets = count($targetIds);
            $countBahan = count($sourceRecipes);
            ActivityLog::log('master_data', 'Salin Resep BOM Massal', "Menyalin {$countBahan} bahan dari {$sourceItem['nama_item']} ({$sourceItem['kode_sku']}) ke {$countTargets} produk tujuan.");

            $this->flashSuccess("Berhasil menyalin {$countBahan} bahan resep dari '{$sourceItem['nama_item']}' ke {$countTargets} produk tujuan!");
            $this->redirect('/products?tab=recipes&item_id=' . urlencode((string)$sourceId));

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menyalin resep: ' . $e->getMessage());
            $this->redirect('/products?tab=recipes' . (!empty($sourceId) ? '&item_id=' . urlencode((string)$sourceId) : ''));
        }
    }

    // ==========================================
    // 5. KELOMPOK UPAH BORONGAN PACKING
    // ==========================================
    public function storeBoronganGroup(): void
    {
        Auth::requirePermission('master.products_manage');

        $nama = trim((string)$this->input('nama_kelompok'));
        $upah = (float)preg_replace('/[^0-9]/', '', (string)$this->input('upah_per_bungkus', '0'));
        $keterangan = trim((string)$this->input('keterangan', ''));

        if (empty($nama) || $upah <= 0) {
            $this->flashError('Nama kelompok dan tarif upah per bungkus (> 0) wajib diisi.');
            $this->redirect('/products?tab=borongan');
            return;
        }

        try {
            Database::execute("
                INSERT INTO public.kelompok_upah_borongan (
                    nama_kelompok, upah_per_bungkus, keterangan, status_aktif
                ) VALUES (
                    :nama, :upah, :ket, TRUE
                )
            ", [
                'nama' => $nama,
                'upah' => $upah,
                'ket' => $keterangan ?: "Tarif borongan pack bungkus Rp " . number_format($upah, 0, ',', '.') . "/pcs"
            ]);

            $this->flashSuccess("Kelompok upah borongan '{$nama}' berhasil ditambahkan!");
            $this->redirect('/products?tab=borongan');

        } catch (Throwable $e) {
            $this->flashError('Gagal menambahkan kelompok borongan: ' . $e->getMessage());
            $this->redirect('/products?tab=borongan');
        }
    }

    public function updateBoronganGroup(): void
    {
        Auth::requirePermission('master.products_manage');

        $id = $this->input('id');
        $nama = trim((string)$this->input('nama_kelompok'));
        $upah = (float)preg_replace('/[^0-9]/', '', (string)$this->input('upah_per_bungkus', '0'));
        $keterangan = trim((string)$this->input('keterangan', ''));
        $statusAktif = !empty($this->input('status_aktif'));

        if (empty($id) || empty($nama) || $upah <= 0) {
            $this->flashError('Parameter kelompok borongan tidak lengkap.');
            $this->redirect('/products?tab=borongan');
            return;
        }

        try {
            Database::execute("
                UPDATE public.kelompok_upah_borongan SET
                    nama_kelompok = :nama,
                    upah_per_bungkus = :upah,
                    keterangan = :ket,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id
            ", [
                'id' => $id,
                'nama' => $nama,
                'upah' => $upah,
                'ket' => $keterangan ?: "Tarif borongan pack bungkus Rp " . number_format($upah, 0, ',', '.') . "/pcs",
                'aktif' => $statusAktif ? 'true' : 'false'
            ]);

            $this->flashSuccess("Kelompok upah borongan '{$nama}' berhasil diperbarui!");
            $this->redirect('/products?tab=borongan');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui kelompok borongan: ' . $e->getMessage());
            $this->redirect('/products?tab=borongan');
        }
    }

    public function deleteBoronganGroup(): void
    {
        Auth::requirePermission('master.products_manage');

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID kelompok borongan tidak valid.');
            $this->redirect('/products?tab=borongan');
            return;
        }

        try {
            $linkedItems = (int)(Database::fetchOne("
                SELECT COUNT(*) as total FROM public.item WHERE kelompok_borongan_id = :id
            ", ['id' => $id])['total'] ?? 0);

            if ($linkedItems > 0) {
                $this->flashError("Kelompok borongan ini tidak dapat dihapus karena terhubung dengan {$linkedItems} produk barang jadi.");
                $this->redirect('/products?tab=borongan');
                return;
            }

            Database::execute("DELETE FROM public.kelompok_upah_borongan WHERE id = :id", ['id' => $id]);
            $this->flashSuccess('Kelompok upah borongan berhasil dihapus.');
            $this->redirect('/products?tab=borongan');

        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus kelompok borongan: ' . $e->getMessage());
            $this->redirect('/products?tab=borongan');
        }
    }
}


<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
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

            // 2. Katalog Barang Jadi (Finished Goods)
            $finishedGoods = Database::fetchAll("
                SELECT i.id, i.grup_id, i.kode_sku, i.barcode, i.nama_item, i.varian_rasa,
                       i.tipe_item, i.satuan_dasar, i.harga_pokok_pembelian,
                       i.stok_fisik_saat_ini, i.stok_minimum_peringatan, i.status_jual, i.status_aktif,
                       i.kelompok_borongan_id,
                       gp.nama_grup, gp.kode_grup, gp.barcode_universal,
                       kub.nama_kelompok, kub.upah_per_bungkus,
                       (SELECT COUNT(*) FROM public.komposisi_item ki WHERE ki.item_jadi_id = i.id) as total_resep_bahan
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                LEFT JOIN public.kelompok_upah_borongan kub ON i.kelompok_borongan_id = kub.id
                WHERE i.tipe_item = 'barang_jadi'
                ORDER BY i.status_aktif DESC, gp.kode_grup ASC, i.nama_item ASC
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
                       ib.satuan_dasar as item_bahan_satuan, ib.tipe_item as item_bahan_tipe
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

            $this->view('products.index', [
                'pageTitle' => 'Master Produk, Bahan & Resep',
                'pageSubtitle' => 'Katalog Barang Jadi, Bahan Baku Curah, Kemasan & Resep BOM',
                'groups' => $groups,
                'finishedGoods' => $finishedGoods,
                'materials' => $materials,
                'recipes' => $recipes,
                'recipesByFinishedGood' => $recipesByFinishedGood,
                'wageGroups' => $wageGroups,
                'suppliers' => $suppliers
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    // ==========================================
    // 1. GRUP KEMASAN (BARCODE UNIVERSAL)
    // ==========================================
    public function storeGroup(): void
    {
        $nama = trim((string)$this->input('nama_grup'));
        $barcode = trim((string)$this->input('barcode_universal'));

        if (empty($nama)) {
            $this->flashError('Nama grup kemasan wajib diisi.');
            $this->redirect('/products');
            return;
        }

        try {
            $count = Database::fetchOne("SELECT count(*) as total FROM public.grup_produk")['total'] ?? 0;
            $kode = 'GRP-' . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);

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

            // Insert level harga default bawaan
            $pdo->prepare("
                INSERT INTO public.grup_produk_harga_level (grup_produk_id, level_harga, nama_level, harga_jual_pcs, harga_jual_bal)
                VALUES 
                (:id, 1, 'Level 1 - Ritel Standar', 15000, 0),
                (:id, 5, 'Level 5 - Konsinyasi Rak', 13500, 0),
                (:id, 8, 'Level 8 - Grosir Mitra', 12500, 0)
                ON CONFLICT DO NOTHING
            ")->execute(['id' => $grupId]);

            $pdo->commit();

            $this->flashSuccess("Grup kemasan {$nama} berhasil ditambahkan!");
            $this->redirect('/products');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError('Gagal menambahkan grup: ' . $e->getMessage());
            $this->redirect('/products');
        }
    }

    // ==========================================
    // 2. BARANG JADI (FINISHED GOODS)
    // ==========================================
    public function storeItem(): void
    {
        $grupId = $this->input('grup_id') ?: null;
        $namaItem = trim((string)$this->input('nama_item'));
        $varianRasa = trim((string)$this->input('varian_rasa'));
        $barcode = trim((string)$this->input('barcode'));
        $hpp = (float)preg_replace('/[^0-9]/', '', (string)$this->input('harga_pokok_pembelian', '0'));
        $stokMin = (int)$this->input('stok_minimum_peringatan', 10);
        $stokAwal = (int)$this->input('stok_awal', 0);
        $kelompokBoronganId = $this->input('kelompok_borongan_id') ?: null;

        if (empty($namaItem)) {
            $this->flashError('Nama barang jadi wajib diisi.');
            $this->redirect('/products');
            return;
        }

        try {
            $count = Database::fetchOne("SELECT count(*) as total FROM public.item WHERE tipe_item = 'barang_jadi'")['total'] ?? 0;
            $kodeSku = 'SUB-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);

            Database::execute("
                INSERT INTO public.item (
                    grup_id, kode_sku, barcode, nama_item, varian_rasa, tipe_item,
                    satuan_dasar, satuan_distribusi, kelompok_borongan_id, pemasok_utama_id,
                    harga_pokok_pembelian, stok_minimum_peringatan, stok_fisik_saat_ini,
                    status_jual, status_aktif
                ) VALUES (
                    :grup, :sku, :barcode, :nama, :varian, 'barang_jadi',
                    'pcs', 'bal', :borongan, NULL,
                    :hpp, :stok_min, :stok_awal,
                    TRUE, TRUE
                )
            ", [
                'grup' => $grupId,
                'sku' => $kodeSku,
                'barcode' => $barcode ?: null,
                'nama' => $namaItem,
                'varian' => $varianRasa ?: $namaItem,
                'borongan' => $kelompokBoronganId,
                'hpp' => $hpp,
                'stok_min' => $stokMin,
                'stok_awal' => $stokAwal
            ]);

            $this->flashSuccess("Barang Jadi {$namaItem} berhasil ditambahkan!");
            $this->redirect('/products');

        } catch (Throwable $e) {
            $this->flashError('Gagal menambahkan Barang Jadi: ' . $e->getMessage());
            $this->redirect('/products');
        }
    }

    public function updateItem(): void
    {
        $id = $this->input('id');
        $grupId = $this->input('grup_id') ?: null;
        $namaItem = trim((string)$this->input('nama_item'));
        $varianRasa = trim((string)$this->input('varian_rasa'));
        $barcode = trim((string)$this->input('barcode'));
        $hpp = (float)preg_replace('/[^0-9]/', '', (string)$this->input('harga_pokok_pembelian', '0'));
        $stokMin = (int)$this->input('stok_minimum_peringatan', 10);
        $kelompokBoronganId = $this->input('kelompok_borongan_id') ?: null;
        $statusJual = (bool)$this->input('status_jual', true);
        $statusAktif = (bool)$this->input('status_aktif', true);

        if (empty($id) || empty($namaItem)) {
            $this->flashError('Parameter tidak lengkap.');
            $this->redirect('/products');
            return;
        }

        try {
            Database::execute("
                UPDATE public.item SET
                    grup_id = :grup,
                    barcode = :barcode,
                    nama_item = :nama,
                    varian_rasa = :varian,
                    harga_pokok_pembelian = :hpp,
                    stok_minimum_peringatan = :stok_min,
                    kelompok_borongan_id = :borongan,
                    status_jual = :jual,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id AND tipe_item = 'barang_jadi'
            ", [
                'id' => $id,
                'grup' => $grupId,
                'barcode' => $barcode ?: null,
                'nama' => $namaItem,
                'varian' => $varianRasa ?: $namaItem,
                'hpp' => $hpp,
                'stok_min' => $stokMin,
                'borongan' => $kelompokBoronganId,
                'jual' => $statusJual ? 'true' : 'false',
                'aktif' => $statusAktif ? 'true' : 'false'
            ]);

            $this->flashSuccess("Barang Jadi {$namaItem} berhasil diperbarui!");
            $this->redirect('/products');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui Barang Jadi: ' . $e->getMessage());
            $this->redirect('/products');
        }
    }

    // ==========================================
    // 3. BAHAN BAKU & KEMASAN (RAW MATERIALS)
    // ==========================================
    public function storeMaterial(): void
    {
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
            $count = Database::fetchOne("SELECT count(*) as total FROM public.item WHERE tipe_item = :tipe", ['tipe' => $tipeItem])['total'] ?? 0;
            $kodeSku = $prefix . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);

            Database::execute("
                INSERT INTO public.item (
                    kode_sku, nama_item, varian_rasa, tipe_item, satuan_dasar,
                    pemasok_utama_id, harga_pokok_pembelian, stok_minimum_peringatan,
                    stok_fisik_saat_ini, status_jual, status_aktif
                ) VALUES (
                    :sku, :nama, :nama, :tipe, :satuan,
                    :pemasok, :hpp, :stok_min,
                    :stok_awal, FALSE, TRUE
                )
            ", [
                'sku' => $kodeSku,
                'nama' => $namaItem,
                'tipe' => $tipeItem,
                'satuan' => $satuanDasar,
                'pemasok' => $pemasokId,
                'hpp' => $hpp,
                'stok_min' => $stokMin,
                'stok_awal' => $stokAwal
            ]);

            $this->flashSuccess("Bahan {$namaItem} ({$kodeSku}) berhasil ditambahkan!");
            $this->redirect('/products?tab=materials');

        } catch (Throwable $e) {
            $this->flashError('Gagal menambahkan bahan: ' . $e->getMessage());
            $this->redirect('/products?tab=materials');
        }
    }

    public function updateMaterial(): void
    {
        $id = $this->input('id');
        $namaItem = trim((string)$this->input('nama_item'));
        $tipeItem = in_array($this->input('tipe_item'), ['bahan_mentah', 'bahan_kemas'], true) ? $this->input('tipe_item') : 'bahan_mentah';
        $satuanDasar = trim((string)$this->input('satuan_dasar', 'kg'));
        $pemasokId = $this->input('pemasok_utama_id') ?: null;
        $hpp = (float)preg_replace('/[^0-9]/', '', (string)$this->input('harga_pokok_pembelian', '0'));
        $stokMin = (float)$this->input('stok_minimum_peringatan', 10);
        $statusAktif = (bool)$this->input('status_aktif', true);

        if (empty($id) || empty($namaItem)) {
            $this->flashError('Parameter tidak lengkap.');
            $this->redirect('/products?tab=materials');
            return;
        }

        try {
            Database::execute("
                UPDATE public.item SET
                    nama_item = :nama,
                    varian_rasa = :nama,
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

            $this->flashSuccess("Bahan {$namaItem} berhasil diperbarui!");
            $this->redirect('/products?tab=materials');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui bahan: ' . $e->getMessage());
            $this->redirect('/products?tab=materials');
        }
    }

    public function deleteMaterial(): void
    {
        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID bahan tidak valid.');
            $this->redirect('/products?tab=materials');
            return;
        }

        try {
            // Cek apakah dipakai di resep BOM
            $used = Database::fetchOne("SELECT count(*) as total FROM public.komposisi_item WHERE item_bahan_id = :id", ['id' => $id])['total'] ?? 0;
            if ($used > 0) {
                $this->flashError("Bahan ini tidak dapat dihapus karena sedang digunakan dalam {$used} resep produk.");
                $this->redirect('/products?tab=materials');
                return;
            }

            Database::execute("DELETE FROM public.item WHERE id = :id AND tipe_item IN ('bahan_mentah', 'bahan_kemas')", ['id' => $id]);
            $this->flashSuccess('Bahan berhasil dihapus.');
            $this->redirect('/products?tab=materials');

        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus bahan: ' . $e->getMessage());
            $this->redirect('/products?tab=materials');
        }
    }

    // ==========================================
    // 4. MASTER RESEP (BILL OF MATERIALS / BOM)
    // ==========================================
    public function storeRecipeItem(): void
    {
        $itemJadiId = $this->input('item_jadi_id');
        $itemBahanId = $this->input('item_bahan_id');
        $jumlahKebutuhan = (float)$this->input('jumlah_kebutuhan', 0);

        if (empty($itemJadiId) || empty($itemBahanId) || $jumlahKebutuhan <= 0) {
            $this->flashError('Pilih barang jadi, bahan baku, dan masukkan jumlah kebutuhan > 0.');
            $this->redirect('/products?tab=recipes');
            return;
        }

        try {
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

            $this->flashSuccess('Komposisi resep berhasil disimpan!');
            $this->redirect('/products?tab=recipes');

        } catch (Throwable $e) {
            $this->flashError('Gagal menyimpan resep: ' . $e->getMessage());
            $this->redirect('/products?tab=recipes');
        }
    }

    public function deleteRecipeItem(): void
    {
        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID resep tidak valid.');
            $this->redirect('/products?tab=recipes');
            return;
        }

        try {
            Database::execute("DELETE FROM public.komposisi_item WHERE id = :id", ['id' => $id]);
            $this->flashSuccess('Komponen resep berhasil dihapus.');
            $this->redirect('/products?tab=recipes');

        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus komponen resep: ' . $e->getMessage());
            $this->redirect('/products?tab=recipes');
        }
    }

    // ==========================================
    // 5. KELOMPOK UPAH BORONGAN PACKING
    // ==========================================
    public function storeBoronganGroup(): void
    {
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
        $id = $this->input('id');
        $nama = trim((string)$this->input('nama_kelompok'));
        $upah = (float)preg_replace('/[^0-9]/', '', (string)$this->input('upah_per_bungkus', '0'));
        $keterangan = trim((string)$this->input('keterangan', ''));
        $statusAktif = (bool)$this->input('status_aktif', true);

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


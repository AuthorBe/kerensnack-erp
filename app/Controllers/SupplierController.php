<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Controllers/SupplierController.php
 * Pengendali Master Vendor / Pemasok Bahan Baku Curah & Kemasan.
 */
class SupplierController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission(['master.suppliers_view', 'master.suppliers_manage']);
    }

    public function index(): void
    {
        try {
            $page = max(1, (int)$this->input('page', 1));
            $perPage = max(10, min(200, (int)$this->input('per_page', 50)));
            $offset = ($page - 1) * $perPage;
            $q = trim((string)$this->input('q', ''));

            $where = "WHERE 1=1";
            $params = [];
            if (!empty($q)) {
                $where .= " AND (
                    s.nama_pemasok ILIKE :q 
                    OR s.kode_pemasok ILIKE :q 
                    OR s.nama_kontak ILIKE :q 
                    OR s.nomor_whatsapp ILIKE :q 
                    OR s.email ILIKE :q 
                    OR s.alamat_lengkap ILIKE :q 
                    OR s.nama_bank ILIKE :q 
                    OR s.nomor_rekening ILIKE :q 
                    OR s.atas_nama_rekening ILIKE :q 
                    OR s.termin_bayar ILIKE :q 
                    OR s.catatan ILIKE :q 
                    OR w.nama_wilayah ILIKE :q 
                    OR w.kode_rute ILIKE :q 
                    OR w.kota_kabupaten ILIKE :q
                )";
                $params['q'] = "%{$q}%";
            }

            $totalSuppliers = (int)(Database::fetchOne("
                SELECT COUNT(*) as total
                FROM public.pemasok s
                LEFT JOIN public.wilayah w ON s.wilayah_id = w.id
                {$where}
            ", $params)['total'] ?? 0);
            $totalPages = max(1, (int)ceil($totalSuppliers / $perPage));

            $suppliers = Database::fetchAll("
                SELECT s.id, s.kode_pemasok, s.nama_pemasok, s.nama_kontak, s.alamat_lengkap, s.link_google_maps,
                       s.nomor_whatsapp, s.email, s.termin_bayar, s.catatan,
                       s.nama_bank, s.nomor_rekening, s.atas_nama_rekening,
                       s.status_aktif, s.wilayah_id, w.nama_wilayah
                FROM public.pemasok s
                LEFT JOIN public.wilayah w ON s.wilayah_id = w.id
                {$where}
                ORDER BY s.status_aktif DESC, s.nama_pemasok ASC
                LIMIT {$perPage} OFFSET {$offset}
            ", $params);

            // Respon cepat untuk live search AJAX (hemat resource & ultra-fast)
            if ($this->isAjax() || $this->input('ajax_search') === '1') {
                $this->json([
                    'status' => 'success',
                    'suppliers' => $suppliers,
                    'pagination' => [
                        'page' => $page,
                        'perPage' => $perPage,
                        'total' => $totalSuppliers,
                        'totalPages' => $totalPages,
                        'q' => $q
                    ]
                ]);
                return;
            }

            $territories = Database::fetchAll("SELECT id, kode_rute, nama_wilayah FROM public.wilayah WHERE status_aktif = TRUE ORDER BY nama_wilayah ASC");

            $this->view('suppliers.index', [
                'pageTitle' => 'Master Pemasok',
                'pageSubtitle' => 'Kelola Data Supplier Bahan Baku Curah, Plastik & Bumbu',
                'suppliers' => $suppliers,
                'territories' => $territories,
                'pagination' => [
                    'page' => $page,
                    'perPage' => $perPage,
                    'total' => $totalSuppliers,
                    'totalPages' => $totalPages,
                    'q' => $q
                ]
            ]);

        } catch (Throwable $e) {
            $this->flashError("Gagal memuat pemasok: " . $e->getMessage());
            $this->view('suppliers.index', [
                'pageTitle' => 'Master Pemasok',
                'pageSubtitle' => 'Kelola Data Supplier Bahan Baku Curah, Plastik & Bumbu',
                'suppliers' => [],
                'territories' => [],
                'pagination' => [
                    'page' => 1,
                    'perPage' => 50,
                    'total' => 0,
                    'totalPages' => 1,
                    'q' => ''
                ]
            ]);
        }
    }

    public function store(): void
    {
        Auth::requirePermission('master.suppliers_manage');

        $nama = trim((string)$this->input('nama_pemasok'));
        $kontak = trim((string)$this->input('nama_kontak')) ?: null;
        $whatsapp = trim((string)$this->input('nomor_whatsapp')) ?: null;
        $email = trim((string)$this->input('email')) ?: null;
        $alamat = trim((string)$this->input('alamat_lengkap', '-'));
        $linkMaps = trim((string)$this->input('link_google_maps')) ?: null;
        $terminBayar = trim((string)$this->input('termin_bayar', 'cash'));
        if (!in_array($terminBayar, ['cash', 'transfer', 'tempo_7_hari', 'tempo_14_hari', 'tempo_30_hari'], true)) {
            $terminBayar = 'cash';
        }
        $catatan = trim((string)$this->input('catatan')) ?: null;
        $wilayahId = $this->input('wilayah_id') ?: null;
        $bankNama = trim((string)$this->input('bank_nama'));
        $bankRekening = trim((string)$this->input('bank_rekening'));
        $bankAtasNama = trim((string)$this->input('bank_atas_nama'));

        if (empty($nama)) {
            $this->flashError('Nama pemasok wajib diisi.');
            $this->redirect('/suppliers');
            return;
        }

        if (!empty($bankRekening) && empty($bankAtasNama)) {
            $this->flashError('Pemilik rekening wajib diisi jika nomor rekening diisi.');
            $this->redirect('/suppliers');
            return;
        }

        try {
            $maxNum = (int)(Database::fetchOne("
                SELECT COALESCE(MAX(NULLIF(regexp_replace(kode_pemasok, '^SUP-', ''), '')::integer), 0) as max_num
                FROM public.pemasok
                WHERE kode_pemasok ~ '^SUP-[0-9]+$'
            ")['max_num'] ?? 0);
            $nextNum = $maxNum + 1;
            do {
                $kode = 'SUP-' . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);
                $exists = (int)(Database::fetchOne("SELECT count(*) as total FROM public.pemasok WHERE kode_pemasok = :k", ['k' => $kode])['total'] ?? 0);
                if ($exists > 0) $nextNum++;
            } while ($exists > 0);

            Database::execute("
                INSERT INTO public.pemasok (
                    kode_pemasok, nama_pemasok, nama_kontak, wilayah_id, alamat_lengkap,
                    link_google_maps, nomor_whatsapp, email, termin_bayar, catatan,
                    nama_bank, nomor_rekening, atas_nama_rekening, status_aktif
                ) VALUES (
                    :kode, :nama, :kontak, :wilayah, :alamat,
                    :maps, :wa, :email, :termin, :catatan,
                    :nama_bank, :nomor_rek, :atas_nama, TRUE
                )
            ", [
                'kode' => $kode,
                'nama' => $nama,
                'kontak' => $kontak,
                'wilayah' => $wilayahId,
                'alamat' => $alamat,
                'maps' => $linkMaps,
                'wa' => $whatsapp,
                'email' => $email,
                'termin' => $terminBayar,
                'catatan' => $catatan,
                'nama_bank' => $bankNama ?: null,
                'nomor_rek' => $bankRekening ?: null,
                'atas_nama' => $bankAtasNama ?: null
            ]);

            \App\Helpers\ActivityLog::log(
                'master_data',
                'TAMBAH_PEMASOK',
                "Menambahkan pemasok vendor baru {$nama} ({$kode})",
                'pemasok',
                null
            );

            $this->flashSuccess("Pemasok {$nama} berhasil ditambahkan!");
            $this->redirect('/suppliers');

        } catch (Throwable $e) {
            $this->flashError('Gagal menambahkan pemasok: ' . $e->getMessage());
            $this->redirect('/suppliers');
        }
    }

    public function update(): void
    {
        Auth::requirePermission('master.suppliers_manage');

        $id = $this->input('id');
        $nama = trim((string)$this->input('nama_pemasok'));
        $kontak = trim((string)$this->input('nama_kontak')) ?: null;
        $whatsapp = trim((string)$this->input('nomor_whatsapp')) ?: null;
        $email = trim((string)$this->input('email')) ?: null;
        $alamat = trim((string)$this->input('alamat_lengkap', '-'));
        $linkMaps = trim((string)$this->input('link_google_maps')) ?: null;
        $terminBayar = trim((string)$this->input('termin_bayar', 'cash'));
        if (!in_array($terminBayar, ['cash', 'transfer', 'tempo_7_hari', 'tempo_14_hari', 'tempo_30_hari'], true)) {
            $terminBayar = 'cash';
        }
        $catatan = trim((string)$this->input('catatan')) ?: null;
        $wilayahId = $this->input('wilayah_id') ?: null;
        $bankNama = trim((string)$this->input('bank_nama'));
        $bankRekening = trim((string)$this->input('bank_rekening'));
        $bankAtasNama = trim((string)$this->input('bank_atas_nama'));
        $statusAktif = (bool)$this->input('status_aktif', true);

        if (empty($id) || empty($nama)) {
            $this->flashError('Parameter tidak lengkap.');
            $this->redirect('/suppliers');
            return;
        }

        if (!empty($bankRekening) && empty($bankAtasNama)) {
            $this->flashError('Pemilik rekening wajib diisi jika nomor rekening diisi.');
            $this->redirect('/suppliers');
            return;
        }

        try {
            Database::execute("
                UPDATE public.pemasok SET
                    nama_pemasok = :nama,
                    nama_kontak = :kontak,
                    wilayah_id = :wilayah,
                    alamat_lengkap = :alamat,
                    link_google_maps = :maps,
                    nomor_whatsapp = :wa,
                    email = :email,
                    termin_bayar = :termin,
                    catatan = :catatan,
                    nama_bank = :nama_bank,
                    nomor_rekening = :nomor_rek,
                    atas_nama_rekening = :atas_nama,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id
            ", [
                'id' => $id,
                'nama' => $nama,
                'kontak' => $kontak,
                'wilayah' => $wilayahId,
                'alamat' => $alamat,
                'maps' => $linkMaps,
                'wa' => $whatsapp,
                'email' => $email,
                'termin' => $terminBayar,
                'catatan' => $catatan,
                'nama_bank' => $bankNama ?: null,
                'nomor_rek' => $bankRekening ?: null,
                'atas_nama' => $bankAtasNama ?: null,
                'aktif' => $statusAktif ? 'true' : 'false'
            ]);

            \App\Helpers\ActivityLog::log(
                'master_data',
                'UBAH_PEMASOK',
                "Memperbarui data pemasok vendor {$nama}",
                'pemasok',
                $id
            );

            $this->flashSuccess("Data pemasok {$nama} berhasil diperbarui!");
            $this->redirect('/suppliers');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui pemasok: ' . $e->getMessage());
            $this->redirect('/suppliers');
        }
    }

    public function delete(): void
    {
        Auth::requirePermission('master.suppliers_manage');

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID pemasok tidak valid.');
            $this->redirect('/suppliers');
            return;
        }

        try {
            $pemasok = Database::fetchOne("SELECT id, nama_pemasok FROM public.pemasok WHERE id = :id", ['id' => $id]);
            if (!$pemasok) {
                $this->flashError('Data pemasok tidak ditemukan.');
                $this->redirect('/suppliers');
                return;
            }
            $nama = $pemasok['nama_pemasok'] ?? 'Pemasok';

            // 1. Cek riwayat pembelian / PO
            $usedInPurchases = (int)(Database::fetchOne(
                "SELECT count(*) as total FROM public.pembelian WHERE pemasok_id = :id",
                ['id' => $id]
            )['total'] ?? 0);

            // 2. Cek relasi katalog item bahan (pemasok_item dan item.pemasok_utama_id)
            $usedInCatalog = (int)(Database::fetchOne(
                "SELECT count(*) as total FROM public.pemasok_item WHERE pemasok_id = :id",
                ['id' => $id]
            )['total'] ?? 0);

            $usedInItems = (int)(Database::fetchOne(
                "SELECT count(*) as total FROM public.item WHERE pemasok_utama_id = :id",
                ['id' => $id]
            )['total'] ?? 0);

            $totalCatalog = $usedInCatalog + $usedInItems;

            if ($usedInPurchases > 0 || $totalCatalog > 0) {
                $reasons = [];
                if ($usedInPurchases > 0) $reasons[] = "{$usedInPurchases} transaksi faktur pembelian";
                if ($totalCatalog > 0) $reasons[] = "{$totalCatalog} katalog bahan baku / kemasan";
                $detail = implode(' dan ', $reasons);
                $this->flashError("Pemasok '{$nama}' tidak dapat dihapus karena terhubung dengan {$detail}. Untuk menghentikan kerja sama, silakan ubah status menjadi nonaktif.");
                $this->redirect('/suppliers');
                return;
            }

            Database::execute("DELETE FROM public.pemasok WHERE id = :id", ['id' => $id]);

            \App\Helpers\ActivityLog::log(
                'master_data',
                'HAPUS_PEMASOK',
                "Menghapus master pemasok {$nama}",
                'pemasok',
                $id
            );

            $this->flashSuccess("Pemasok '{$nama}' berhasil dihapus.");
            $this->redirect('/suppliers');

        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus pemasok: ' . $e->getMessage());
            $this->redirect('/suppliers');
        }
    }

    /**
     * AJAX Get Katalog Bahan Baku & Kemasan per Vendor
     */
    public function getCatalog(): void
    {
        try {
            $pemasokId = (string)$this->input('pemasok_id', '');
            if (empty($pemasokId)) {
                $this->json(['status' => 'error', 'message' => 'ID Pemasok wajib disertakan.'], 400);
                return;
            }

            $pemasok = Database::fetchOne("
                SELECT id, kode_pemasok, nama_pemasok, termin_bayar, status_aktif
                FROM public.pemasok 
                WHERE id = :id
            ", ['id' => $pemasokId]);

            if (!$pemasok) {
                $this->json(['status' => 'error', 'message' => 'Pemasok tidak ditemukan.'], 404);
                return;
            }

            // Daftar item yang sudah terhubung di katalog vendor
            $catalogItems = Database::fetchAll("
                SELECT 
                    pi.id as catalog_id,
                    pi.pemasok_id,
                    pi.item_id,
                    pi.harga_beli,
                    pi.kode_sku_vendor,
                    pi.catatan,
                    pi.status_aktif,
                    pi.diubah_pada,
                    i.nama_item,
                    i.kode_sku,
                    i.tipe_item,
                    i.satuan_dasar,
                    i.stok_fisik_saat_ini,
                    i.harga_pokok_pembelian as hpp_master
                FROM public.pemasok_item pi
                JOIN public.item i ON i.id = pi.item_id
                WHERE pi.pemasok_id = :pid
                ORDER BY i.tipe_item ASC, i.nama_item ASC
            ", ['pid' => $pemasokId]);

            // Seluruh master item bahan mentah, kemasan & maklon aktif
            $availableItems = Database::fetchAll("
                SELECT id, kode_sku, nama_item, tipe_item, satuan_dasar, harga_pokok_pembelian, stok_fisik_saat_ini
                FROM public.item
                WHERE status_aktif = TRUE
                ORDER BY 
                    CASE 
                        WHEN tipe_item = 'bahan_mentah' THEN 1 
                        WHEN tipe_item = 'bahan_kemas' THEN 2 
                        ELSE 3 
                    END, 
                    nama_item ASC
            ");

            $this->json([
                'status' => 'success',
                'pemasok' => $pemasok,
                'catalog' => $catalogItems,
                'available_items' => $availableItems
            ]);
        } catch (Throwable $e) {
            $this->json(['status' => 'error', 'message' => 'Gagal memuat katalog vendor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * AJAX / Form Simpan / Update Item Katalog Vendor
     */
    public function saveCatalogItem(): void
    {
        Auth::requirePermission('master.suppliers_manage');

        try {
            $pemasokId = trim((string)$this->input('pemasok_id'));
            $itemId = trim((string)$this->input('item_id'));
            $hargaBeli = max(0, (float)$this->input('harga_beli', 0));
            $kodeSkuVendor = trim((string)$this->input('kode_sku_vendor')) ?: null;
            $catatan = trim((string)$this->input('catatan')) ?: null;
            $statusAktif = (bool)$this->input('status_aktif', true);

            if (empty($pemasokId) || empty($itemId)) {
                $this->json(['status' => 'error', 'message' => 'Pemasok dan Bahan Baku/Kemasan wajib dipilih.'], 400);
                return;
            }

            // Verifikasi pemasok & item ada
            $pemasok = Database::fetchOne("SELECT nama_pemasok FROM public.pemasok WHERE id = :id", ['id' => $pemasokId]);
            $item = Database::fetchOne("SELECT nama_item FROM public.item WHERE id = :id", ['id' => $itemId]);

            if (!$pemasok || !$item) {
                $this->json(['status' => 'error', 'message' => 'Data Pemasok atau Item tidak valid.'], 404);
                return;
            }

            Database::execute("
                INSERT INTO public.pemasok_item (
                    pemasok_id, item_id, harga_beli, kode_sku_vendor, catatan, status_aktif, dibuat_pada, diubah_pada
                ) VALUES (
                    :pemasok_id, :item_id, :harga_beli, :kode_sku, :catatan, :status_aktif, NOW(), NOW()
                )
                ON CONFLICT (pemasok_id, item_id) DO UPDATE SET
                    harga_beli = EXCLUDED.harga_beli,
                    kode_sku_vendor = EXCLUDED.kode_sku_vendor,
                    catatan = EXCLUDED.catatan,
                    status_aktif = EXCLUDED.status_aktif,
                    diubah_pada = NOW()
            ", [
                'pemasok_id' => $pemasokId,
                'item_id' => $itemId,
                'harga_beli' => $hargaBeli,
                'kode_sku' => $kodeSkuVendor,
                'catatan' => $catatan,
                'status_aktif' => $statusAktif ? 'true' : 'false'
            ]);

            // Selaraskan harga pokok pembelian jika vendor ini adalah pemasok utama item
            Database::execute("
                UPDATE public.item 
                SET harga_pokok_pembelian = :harga_beli, diubah_pada = NOW() 
                WHERE id = :item_id AND pemasok_utama_id = :pemasok_id
            ", [
                'harga_beli' => $hargaBeli,
                'item_id'    => $itemId,
                'pemasok_id' => $pemasokId
            ]);

            \App\Helpers\ActivityLog::log(
                'master_data',
                'KATALOG_PEMASOK_SIMPAN',
                "Menetapkan katalog bahan {$item['nama_item']} pada vendor {$pemasok['nama_pemasok']} dengan harga Rp " . number_format($hargaBeli, 0, ',', '.'),
                'pemasok_item',
                $pemasokId
            );

            $this->json([
                'status' => 'success',
                'message' => "Bahan '{$item['nama_item']}' berhasil disimpan di katalog {$pemasok['nama_pemasok']}!"
            ]);
        } catch (Throwable $e) {
            $this->json(['status' => 'error', 'message' => 'Gagal menyimpan katalog: ' . $e->getMessage()], 500);
        }
    }

    /**
     * AJAX Hapus Item dari Katalog Vendor
     */
    public function deleteCatalogItem(): void
    {
        Auth::requirePermission('master.suppliers_manage');

        try {
            $catalogId = trim((string)$this->input('id'));
            $pemasokId = trim((string)$this->input('pemasok_id'));
            $itemId = trim((string)$this->input('item_id'));

            if (!empty($catalogId)) {
                $row = Database::fetchOne("
                    SELECT pi.id, p.nama_pemasok, i.nama_item
                    FROM public.pemasok_item pi
                    JOIN public.pemasok p ON p.id = pi.pemasok_id
                    JOIN public.item i ON i.id = pi.item_id
                    WHERE pi.id = :id
                ", ['id' => $catalogId]);

                if (!$row) {
                    $this->json(['status' => 'error', 'message' => 'Data katalog tidak ditemukan.'], 404);
                    return;
                }

                Database::execute("DELETE FROM public.pemasok_item WHERE id = :id", ['id' => $catalogId]);
            } elseif (!empty($pemasokId) && !empty($itemId)) {
                $row = Database::fetchOne("
                    SELECT pi.id, p.nama_pemasok, i.nama_item
                    FROM public.pemasok_item pi
                    JOIN public.pemasok p ON p.id = pi.pemasok_id
                    JOIN public.item i ON i.id = pi.item_id
                    WHERE pi.pemasok_id = :pid AND pi.item_id = :iid
                ", ['pid' => $pemasokId, 'iid' => $itemId]);

                if (!$row) {
                    $this->json(['status' => 'error', 'message' => 'Data katalog tidak ditemukan.'], 404);
                    return;
                }

                Database::execute("DELETE FROM public.pemasok_item WHERE pemasok_id = :pid AND item_id = :iid", ['pid' => $pemasokId, 'iid' => $itemId]);
            } else {
                $this->json(['status' => 'error', 'message' => 'Parameter ID katalog tidak valid.'], 400);
                return;
            }

            \App\Helpers\ActivityLog::log(
                'master_data',
                'KATALOG_PEMASOK_HAPUS',
                "Menghapus bahan {$row['nama_item']} dari katalog {$row['nama_pemasok']}",
                'pemasok_item',
                $catalogId ?: null
            );

            $this->json([
                'status' => 'success',
                'message' => "Bahan '{$row['nama_item']}' berhasil dihapus dari katalog."
            ]);
        } catch (Throwable $e) {
            $this->json(['status' => 'error', 'message' => 'Gagal menghapus item katalog: ' . $e->getMessage()], 500);
        }
    }
}


<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Controllers/CustomerController.php
 * Pengendali Master Toko Pelanggan Terpadu:
 * 1. Data Toko & Plafon Kredit
 * 2. Grup Harga (Level 1–28)
 * 3. Master Rute / Wilayah (CRUD Terpadu untuk Pelanggan & Vendor Supplier)
 * 4. Tipe Bayar (Cash / Tempo / Konsinyasi)
 * 5. Daftar Item Khusus Toko (Whitelist Katalog Item per Toko)
 */
class CustomerController extends Controller
{
    public function __construct()
    {
        Auth::requireRole(['owner', 'admin']);
    }

    public function index(): void
    {
        try {
            // 1. Ambil data seluruh toko pelanggan beserta relasi & jumlah item khusus
            $customers = Database::fetchAll("
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.is_konsinyasi,
                       p.alamat_lengkap, p.nomor_telepon, p.nomor_whatsapp, p.tipe_pembayaran_default,
                       p.nama_bank, p.nomor_rekening, p.atas_nama_rekening,
                       p.plafon_piutang, p.total_piutang_berjalan, p.override_level_harga,
                       p.override_diskon_persen, p.override_diskon_nominal, p.status_aktif,
                       p.grup_pelanggan_id, p.wilayah_id,
                       gp.nama_grup, gp.default_level_harga,
                       w.nama_wilayah, w.kode_rute,
                       (SELECT COUNT(*) FROM public.pelanggan_item pi WHERE pi.pelanggan_id = p.id) as total_item_khusus
                FROM public.pelanggan p
                LEFT JOIN public.grup_pelanggan gp ON p.grup_pelanggan_id = gp.id
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                ORDER BY p.status_aktif DESC, p.nama_toko ASC
            ");

            // 2. Ambil grup pelanggan (Tier Harga 1–28) beserta detail diskon dan jumlah toko
            $customerGroups = Database::fetchAll("
                SELECT gp.id, gp.kode_grup, gp.nama_grup, gp.default_level_harga,
                       gp.diskon_persen_default, gp.diskon_nominal_default, gp.status_aktif,
                       (SELECT COUNT(*) FROM public.pelanggan p WHERE p.grup_pelanggan_id = gp.id) as total_pelanggan
                FROM public.grup_pelanggan gp
                ORDER BY gp.default_level_harga ASC, gp.nama_grup ASC
            ");

            // 3. Ambil master wilayah / rute logistik
            $territories = Database::fetchAll("
                SELECT w.id, w.kode_rute, w.nama_wilayah, w.provinsi, w.kota_kabupaten, w.sub_wilayah, w.status_aktif,
                       (SELECT COUNT(*) FROM public.pelanggan p WHERE p.wilayah_id = w.id) as total_pelanggan,
                       (SELECT COUNT(*) FROM public.pemasok sup WHERE sup.wilayah_id = w.id) as total_pemasok
                FROM public.wilayah w
                ORDER BY w.status_aktif DESC, w.nama_wilayah ASC
            ");

            // 4. Ambil seluruh Barang Jadi (Finished Goods) untuk modal item whitelist
            $finishedGoods = Database::fetchAll("
                SELECT i.id, i.grup_id, i.kode_sku, i.barcode, i.nama_item, i.varian_rasa,
                       gp.nama_grup, gp.kode_grup
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE i.status_aktif = TRUE AND i.tipe_item = 'barang_jadi'
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ");

            // 5. Ambil pemetaan item khusus per pelanggan
            $rawCustomerItems = Database::fetchAll("
                SELECT pelanggan_id, item_id 
                FROM public.pelanggan_item
            ");
            $customerItemsMap = [];
            foreach ($rawCustomerItems as $ci) {
                $customerItemsMap[$ci['pelanggan_id']][] = $ci['item_id'];
            }

            $this->view('customers.index', [
                'pageTitle' => 'Master Toko Pelanggan & Wilayah',
                'pageSubtitle' => 'Kelola Data Toko, Tier Harga, Rute Logistik & Item Khusus Toko',
                'customers' => $customers,
                'groups' => $customerGroups,
                'customerGroups' => $customerGroups,
                'territories' => $territories,
                'finishedGoods' => $finishedGoods,
                'customerItemsMap' => $customerItemsMap
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    // ==========================================
    // 1. DATA TOKO PELANGGAN (STORE, UPDATE, DELETE)
    // ==========================================
    public function store(): void
    {
        $namaToko = trim((string)$this->input('nama_toko'));
        $namaPemilik = trim((string)$this->input('nama_pemilik'));
        $grupId = $this->input('grup_pelanggan_id');
        $wilayahId = $this->input('wilayah_id') ?: null;
        $alamat = trim((string)$this->input('alamat_lengkap', '-'));
        $telepon = trim((string)$this->input('nomor_telepon'));
        $whatsapp = trim((string)$this->input('nomor_whatsapp'));
        $tipeBayar = $this->input('tipe_pembayaran_default', 'cash');
        $isKonsinyasi = ($tipeBayar === 'konsinyasi') || (bool)$this->input('is_konsinyasi', false);
        $plafon = (float)preg_replace('/[^0-9]/', '', (string)$this->input('plafon_piutang', '0'));
        $overrideLevel = $this->input('override_level_harga') ? (int)$this->input('override_level_harga') : null;
        $overrideDiskonPersen = (float)$this->input('override_diskon_persen', 0);
        $overrideDiskonNominal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('override_diskon_nominal', '0'));
        $namaBank = trim((string)$this->input('nama_bank'));
        $nomorRekening = trim((string)$this->input('nomor_rekening'));
        $atasNamaRekening = trim((string)$this->input('atas_nama_rekening'));

        if (empty($namaToko) || empty($grupId)) {
            $this->flashError('Nama toko dan grup harga pelanggan wajib diisi.');
            $this->redirect('/customers');
            return;
        }

        if (!empty($nomorRekening) && empty($atasNamaRekening)) {
            $this->flashError('Pemilik rekening wajib diisi jika nomor rekening diisi.');
            $this->redirect('/customers');
            return;
        }

        try {
            $count = Database::fetchOne("SELECT count(*) as total FROM public.pelanggan")['total'] ?? 0;
            $kodePelanggan = 'CUST-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);

            Database::execute("
                INSERT INTO public.pelanggan (
                    kode_pelanggan, nama_toko, nama_pemilik, grup_pelanggan_id, is_konsinyasi,
                    wilayah_id, alamat_lengkap, nomor_telepon, nomor_whatsapp,
                    tipe_pembayaran_default, plafon_piutang, override_level_harga,
                    override_diskon_persen, override_diskon_nominal,
                    nama_bank, nomor_rekening, atas_nama_rekening, status_aktif
                ) VALUES (
                    :kode, :nama, :pemilik, :grup, :konsinyasi,
                    :wilayah, :alamat, :telp, :wa,
                    :bayar, :plafon, :level,
                    :disc_persen, :disc_nom,
                    :nama_bank, :nomor_rek, :atas_nama, TRUE
                )
            ", [
                'kode' => $kodePelanggan,
                'nama' => $namaToko,
                'pemilik' => $namaPemilik,
                'grup' => $grupId,
                'konsinyasi' => $isKonsinyasi ? 'true' : 'false',
                'wilayah' => $wilayahId,
                'alamat' => $alamat,
                'telp' => $telepon,
                'wa' => $whatsapp,
                'bayar' => $tipeBayar,
                'plafon' => $plafon,
                'level' => $overrideLevel,
                'disc_persen' => $overrideDiskonPersen,
                'disc_nom' => $overrideDiskonNominal,
                'nama_bank' => $namaBank ?: null,
                'nomor_rek' => $nomorRekening ?: null,
                'atas_nama' => $atasNamaRekening ?: null,
            ]);

            $this->flashSuccess("Toko {$namaToko} ({$kodePelanggan}) berhasil ditambahkan!");
            $this->redirect('/customers');

        } catch (Throwable $e) {
            $this->flashError('Gagal menambahkan toko: ' . $e->getMessage());
            $this->redirect('/customers');
        }
    }

    public function update(): void
    {
        $id = $this->input('id');
        $namaToko = trim((string)$this->input('nama_toko'));
        $namaPemilik = trim((string)$this->input('nama_pemilik'));
        $grupId = $this->input('grup_pelanggan_id');
        $wilayahId = $this->input('wilayah_id') ?: null;
        $alamat = trim((string)$this->input('alamat_lengkap', '-'));
        $telepon = trim((string)$this->input('nomor_telepon'));
        $whatsapp = trim((string)$this->input('nomor_whatsapp'));
        $tipeBayar = $this->input('tipe_pembayaran_default', 'cash');
        $isKonsinyasi = ($tipeBayar === 'konsinyasi') || (bool)$this->input('is_konsinyasi', false);
        $plafon = (float)preg_replace('/[^0-9]/', '', (string)$this->input('plafon_piutang', '0'));
        $overrideLevel = $this->input('override_level_harga') ? (int)$this->input('override_level_harga') : null;
        $overrideDiskonPersen = (float)$this->input('override_diskon_persen', 0);
        $overrideDiskonNominal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('override_diskon_nominal', '0'));
        $statusAktif = (bool)$this->input('status_aktif', true);
        $namaBank = trim((string)$this->input('nama_bank'));
        $nomorRekening = trim((string)$this->input('nomor_rekening'));
        $atasNamaRekening = trim((string)$this->input('atas_nama_rekening'));

        if (empty($id) || empty($namaToko) || empty($grupId)) {
            $this->flashError('Parameter data toko tidak lengkap.');
            $this->redirect('/customers');
            return;
        }

        if (!empty($nomorRekening) && empty($atasNamaRekening)) {
            $this->flashError('Pemilik rekening wajib diisi jika nomor rekening diisi.');
            $this->redirect('/customers');
            return;
        }

        try {
            Database::execute("
                UPDATE public.pelanggan SET
                    nama_toko = :nama,
                    nama_pemilik = :pemilik,
                    grup_pelanggan_id = :grup,
                    is_konsinyasi = :konsinyasi,
                    wilayah_id = :wilayah,
                    alamat_lengkap = :alamat,
                    nomor_telepon = :telp,
                    nomor_whatsapp = :wa,
                    tipe_pembayaran_default = :bayar,
                    plafon_piutang = :plafon,
                    override_level_harga = :level,
                    override_diskon_persen = :disc_persen,
                    override_diskon_nominal = :disc_nom,
                    nama_bank = :nama_bank,
                    nomor_rekening = :nomor_rek,
                    atas_nama_rekening = :atas_nama,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id
            ", [
                'id' => $id,
                'nama' => $namaToko,
                'pemilik' => $namaPemilik,
                'grup' => $grupId,
                'konsinyasi' => $isKonsinyasi ? 'true' : 'false',
                'wilayah' => $wilayahId,
                'alamat' => $alamat,
                'telp' => $telepon,
                'wa' => $whatsapp,
                'bayar' => $tipeBayar,
                'plafon' => $plafon,
                'level' => $overrideLevel,
                'disc_persen' => $overrideDiskonPersen,
                'disc_nom' => $overrideDiskonNominal,
                'nama_bank' => $namaBank ?: null,
                'nomor_rek' => $nomorRekening ?: null,
                'atas_nama' => $atasNamaRekening ?: null,
                'aktif' => $statusAktif ? 'true' : 'false'
            ]);

            $this->flashSuccess("Data toko {$namaToko} berhasil diperbarui!");
            $this->redirect('/customers');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui toko: ' . $e->getMessage());
            $this->redirect('/customers');
        }
    }

    public function delete(): void
    {
        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID toko tidak valid.');
            $this->redirect('/customers');
            return;
        }

        try {
            $orders = Database::fetchOne("SELECT count(*) as total FROM public.pesanan WHERE pelanggan_id = :id", ['id' => $id])['total'] ?? 0;
            if ($orders > 0) {
                $this->flashError("Toko ini tidak dapat dihapus karena memiliki riwayat {$orders} transaksi pesanan/penjualan.");
                $this->redirect('/customers');
                return;
            }

            Database::execute("DELETE FROM public.pelanggan WHERE id = :id", ['id' => $id]);
            $this->flashSuccess('Toko pelanggan berhasil dihapus.');
            $this->redirect('/customers');

        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus toko: ' . $e->getMessage());
            $this->redirect('/customers');
        }
    }

    // ==========================================
    // 2. DAFTAR ITEM KHUSUS TOKO (ITEM WHITELIST)
    // ==========================================
    public function saveCustomerItems(): void
    {
        $pelangganId = $this->input('pelanggan_id');
        $itemIds = $this->input('item_ids') ?? [];

        if (is_string($itemIds)) {
            $itemIds = json_decode($itemIds, true) ?: [];
        }

        if (empty($pelangganId)) {
            $this->flashError('ID toko pelanggan tidak valid.');
            $this->redirect('/customers');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Hapus item khusus sebelumnya
            $pdo->prepare("DELETE FROM public.pelanggan_item WHERE pelanggan_id = :cust_id")
                ->execute(['cust_id' => $pelangganId]);

            // Simpan item khusus baru jika ada yang dipilih
            if (!empty($itemIds) && is_array($itemIds)) {
                $stmt = $pdo->prepare("
                    INSERT INTO public.pelanggan_item (pelanggan_id, item_id, dibuat_pada)
                    VALUES (:cust_id, :item_id, NOW())
                    ON CONFLICT DO NOTHING
                ");

                foreach ($itemIds as $itemId) {
                    if (!empty($itemId)) {
                        $stmt->execute(['cust_id' => $pelangganId, 'item_id' => $itemId]);
                    }
                }
            }

            $pdo->commit();

            $total = count($itemIds);
            $msg = $total > 0 
                ? "Daftar {$total} item khusus toko berhasil disimpan!" 
                : "Semua produk kini diizinkan untuk toko ini (Katalog Default).";

            $this->flashSuccess($msg);
            $this->redirect('/customers');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError('Gagal menyimpan item khusus toko: ' . $e->getMessage());
            $this->redirect('/customers');
        }
    }

    // ==========================================
    // 3. MASTER WILAYAH & RUTE LOGISTIK (CRUD)
    // ==========================================
    public function storeTerritory(): void
    {
        $nama = trim((string)$this->input('nama_wilayah'));
        $kode = trim((string)$this->input('kode_rute'));
        $kota = trim((string)$this->input('kota_kabupaten', 'Bandung'));
        $provinsi = trim((string)$this->input('provinsi', 'Jawa Barat'));
        $sub = trim((string)$this->input('sub_wilayah', ''));

        if (empty($nama)) {
            $this->flashError('Nama wilayah wajib diisi.');
            $this->redirect('/customers?tab=territories');
            return;
        }

        if (empty($kode) || $kode === 'RTE-') {
            $count = Database::fetchOne("SELECT count(*) as total FROM public.wilayah")['total'] ?? 0;
            $kode = 'RTE-' . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
        } else {
            $suffix = strtoupper((string)preg_replace('/[^A-Z0-9-]/', '', (string)preg_replace('/^RTE-?/i', '', $kode)));
            $suffix = substr($suffix, 0, 8);
            $kode = 'RTE-' . ($suffix ?: '001');
        }

        try {
            Database::execute("
                INSERT INTO public.wilayah (
                    kode_rute, nama_wilayah, kota_kabupaten, provinsi, sub_wilayah, status_aktif
                ) VALUES (
                    :kode, :nama, :kota, :provinsi, :sub, TRUE
                )
            ", [
                'kode' => $kode,
                'nama' => $nama,
                'kota' => $kota ?: '-',
                'provinsi' => $provinsi ?: '-',
                'sub' => $sub ?: null
            ]);

            $this->flashSuccess("Wilayah {$nama} ({$kode}) berhasil ditambahkan!");
            $this->redirect('/customers?tab=territories');

        } catch (Throwable $e) {
            $this->flashError('Gagal menambahkan wilayah: ' . $e->getMessage());
            $this->redirect('/customers?tab=territories');
        }
    }

    public function updateTerritory(): void
    {
        $id = $this->input('id');
        $nama = trim((string)$this->input('nama_wilayah'));
        $rawKode = trim((string)$this->input('kode_rute'));
        $suffix = strtoupper((string)preg_replace('/[^A-Z0-9-]/', '', (string)preg_replace('/^RTE-?/i', '', $rawKode)));
        $suffix = substr($suffix, 0, 8);
        $kode = 'RTE-' . ($suffix ?: '001');
        $kota = trim((string)$this->input('kota_kabupaten', 'Bandung'));
        $provinsi = trim((string)$this->input('provinsi', 'Jawa Barat'));
        $sub = trim((string)$this->input('sub_wilayah', ''));
        $statusAktif = (bool)$this->input('status_aktif', true);

        if (empty($id) || empty($nama)) {
            $this->flashError('Parameter wilayah tidak lengkap.');
            $this->redirect('/customers?tab=territories');
            return;
        }

        try {
            Database::execute("
                UPDATE public.wilayah SET
                    kode_rute = :kode,
                    nama_wilayah = :nama,
                    kota_kabupaten = :kota,
                    provinsi = :provinsi,
                    sub_wilayah = :sub,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id
            ", [
                'id' => $id,
                'kode' => $kode,
                'nama' => $nama,
                'kota' => $kota,
                'provinsi' => $provinsi,
                'sub' => $sub ?: null,
                'aktif' => $statusAktif ? 'true' : 'false'
            ]);

            $this->flashSuccess("Wilayah {$nama} berhasil diperbarui!");
            $this->redirect('/customers?tab=territories');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui wilayah: ' . $e->getMessage());
            $this->redirect('/customers?tab=territories');
        }
    }

    public function deleteTerritory(): void
    {
        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID wilayah tidak valid.');
            $this->redirect('/customers?tab=territories');
            return;
        }

        try {
            $usedPelanggan = Database::fetchOne("SELECT count(*) as total FROM public.pelanggan WHERE wilayah_id = :id", ['id' => $id])['total'] ?? 0;
            $usedPemasok = Database::fetchOne("SELECT count(*) as total FROM public.pemasok WHERE wilayah_id = :id", ['id' => $id])['total'] ?? 0;

            if ($usedPelanggan > 0 || $usedPemasok > 0) {
                $this->flashError("Wilayah ini tidak dapat dihapus karena sedang digunakan oleh {$usedPelanggan} toko pelanggan dan {$usedPemasok} vendor pemasok.");
                $this->redirect('/customers?tab=territories');
                return;
            }

            Database::execute("DELETE FROM public.wilayah WHERE id = :id", ['id' => $id]);
            $this->flashSuccess('Wilayah berhasil dihapus.');
            $this->redirect('/customers?tab=territories');

        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus wilayah: ' . $e->getMessage());
            $this->redirect('/customers?tab=territories');
        }
    }

    // ==========================================
    // 4. MASTER GRUP PELANGGAN & TIER (STORE, UPDATE, DELETE)
    // ==========================================
    public function storeGroup(): void
    {
        $nama = trim((string)$this->input('nama_grup'));
        $kode = trim((string)$this->input('kode_grup'));
        $level = (int)$this->input('default_level_harga', 1);
        $discPersen = (float)$this->input('diskon_persen_default', 0);
        $discNominal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('diskon_nominal_default', '0'));

        if (empty($nama)) {
            $this->flashError('Nama grup pelanggan wajib diisi.');
            $this->redirect('/customers?tab=customer_groups');
            return;
        }

        if (empty($kode) || $kode === 'GRP-') {
            $kode = 'GRP-' . strtoupper((string)preg_replace('/[^A-Z0-9]/', '', substr($nama, 0, 6))) . '-' . $level;
        } else {
            $suffix = strtoupper((string)preg_replace('/[^A-Z0-9-]/', '', (string)preg_replace('/^GRP-?/i', '', $kode)));
            $suffix = substr($suffix, 0, 10);
            $kode = 'GRP-' . ($suffix ?: '01');
        }

        try {
            Database::execute("
                INSERT INTO public.grup_pelanggan (
                    kode_grup, nama_grup, default_level_harga,
                    diskon_persen_default, diskon_nominal_default, status_aktif
                ) VALUES (
                    :kode, :nama, :level, :disc_p, :disc_n, TRUE
                )
            ", [
                'kode' => $kode,
                'nama' => $nama,
                'level' => $level,
                'disc_p' => $discPersen,
                'disc_n' => $discNominal
            ]);

            $this->flashSuccess("Grup pelanggan {$nama} berhasil ditambahkan!");
            $this->redirect('/customers?tab=customer_groups');

        } catch (Throwable $e) {
            $this->flashError('Gagal menambahkan grup pelanggan: ' . $e->getMessage());
            $this->redirect('/customers?tab=customer_groups');
        }
    }

    public function updateGroup(): void
    {
        $id = $this->input('id');
        $nama = trim((string)$this->input('nama_grup'));
        $kode = trim((string)$this->input('kode_grup'));
        $level = (int)$this->input('default_level_harga', 1);
        $discPersen = (float)$this->input('diskon_persen_default', 0);
        $discNominal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('diskon_nominal_default', '0'));
        $statusAktif = (bool)$this->input('status_aktif', true);

        if (empty($id) || empty($nama)) {
            $this->flashError('Parameter tidak lengkap.');
            $this->redirect('/customers?tab=customer_groups');
            return;
        }

        if (empty($kode) || $kode === 'GRP-') {
            $kode = 'GRP-' . strtoupper((string)preg_replace('/[^A-Z0-9]/', '', substr($nama, 0, 6))) . '-' . $level;
        } else {
            $suffix = strtoupper((string)preg_replace('/[^A-Z0-9-]/', '', (string)preg_replace('/^GRP-?/i', '', $kode)));
            $suffix = substr($suffix, 0, 10);
            $kode = 'GRP-' . ($suffix ?: '01');
        }

        try {
            Database::execute("
                UPDATE public.grup_pelanggan SET
                    kode_grup = :kode,
                    nama_grup = :nama,
                    default_level_harga = :level,
                    diskon_persen_default = :disc_p,
                    diskon_nominal_default = :disc_n,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id
            ", [
                'id' => $id,
                'kode' => $kode,
                'nama' => $nama,
                'level' => $level,
                'disc_p' => $discPersen,
                'disc_n' => $discNominal,
                'aktif' => $statusAktif ? 'true' : 'false'
            ]);

            $this->flashSuccess("Grup pelanggan {$nama} berhasil diperbarui!");
            $this->redirect('/customers?tab=customer_groups');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui grup pelanggan: ' . $e->getMessage());
            $this->redirect('/customers?tab=customer_groups');
        }
    }

    public function deleteGroup(): void
    {
        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID grup pelanggan tidak valid.');
            $this->redirect('/customers?tab=customer_groups');
            return;
        }

        try {
            $usedCount = Database::fetchOne("SELECT count(*) as total FROM public.pelanggan WHERE grup_pelanggan_id = :id", ['id' => $id])['total'] ?? 0;
            if ($usedCount > 0) {
                $this->flashError("Grup pelanggan ini sedang digunakan oleh {$usedCount} toko pelanggan dan tidak dapat dihapus.");
                $this->redirect('/customers?tab=customer_groups');
                return;
            }

            Database::execute("DELETE FROM public.grup_pelanggan WHERE id = :id", ['id' => $id]);
            $this->flashSuccess('Grup pelanggan berhasil dihapus.');
            $this->redirect('/customers?tab=customer_groups');

        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus grup pelanggan: ' . $e->getMessage());
            $this->redirect('/customers?tab=customer_groups');
        }
    }
}


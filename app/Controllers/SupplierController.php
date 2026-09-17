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
                $where .= " AND (s.nama_pemasok ILIKE :q OR s.kode_pemasok ILIKE :q OR s.nama_kontak ILIKE :q OR s.nomor_telepon ILIKE :q OR s.nomor_whatsapp ILIKE :q OR s.email ILIKE :q OR s.nomor_rekening ILIKE :q OR s.alamat_lengkap ILIKE :q OR w.nama_wilayah ILIKE :q)";
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
                       s.nomor_telepon, s.nomor_whatsapp, s.email, s.termin_bayar, s.catatan,
                       s.nama_bank, s.nomor_rekening, s.atas_nama_rekening,
                       s.status_aktif, s.wilayah_id, w.nama_wilayah
                FROM public.pemasok s
                LEFT JOIN public.wilayah w ON s.wilayah_id = w.id
                {$where}
                ORDER BY s.status_aktif DESC, s.nama_pemasok ASC
                LIMIT {$perPage} OFFSET {$offset}
            ", $params);

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
        $telepon = trim((string)$this->input('nomor_telepon')) ?: null;
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
                    link_google_maps, nomor_telepon, nomor_whatsapp, email, termin_bayar, catatan,
                    nama_bank, nomor_rekening, atas_nama_rekening, status_aktif
                ) VALUES (
                    :kode, :nama, :kontak, :wilayah, :alamat,
                    :maps, :telp, :wa, :email, :termin, :catatan,
                    :nama_bank, :nomor_rek, :atas_nama, TRUE
                )
            ", [
                'kode' => $kode,
                'nama' => $nama,
                'kontak' => $kontak,
                'wilayah' => $wilayahId,
                'alamat' => $alamat,
                'maps' => $linkMaps,
                'telp' => $telepon,
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
        $telepon = trim((string)$this->input('nomor_telepon')) ?: null;
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
                    nomor_telepon = :telp,
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
                'telp' => $telepon,
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

            // 2. Cek relasi sebagai pemasok utama item bahan
            $usedInItems = (int)(Database::fetchOne(
                "SELECT count(*) as total FROM public.item WHERE pemasok_utama_id = :id",
                ['id' => $id]
            )['total'] ?? 0);

            if ($usedInPurchases > 0 || $usedInItems > 0) {
                $reasons = [];
                if ($usedInPurchases > 0) $reasons[] = "{$usedInPurchases} transaksi faktur pembelian";
                if ($usedInItems > 0) $reasons[] = "{$usedInItems} katalog bahan baku / kemasan";
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
}


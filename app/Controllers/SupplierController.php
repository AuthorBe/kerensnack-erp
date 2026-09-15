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
                $where .= " AND (s.nama_pemasok ILIKE :q OR s.kode_pemasok ILIKE :q OR s.nomor_telepon ILIKE :q OR s.nomor_rekening ILIKE :q OR w.nama_wilayah ILIKE :q)";
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
                SELECT s.id, s.kode_pemasok, s.nama_pemasok, s.alamat_lengkap, s.nomor_telepon,
                       s.detail_bank, s.nama_bank, s.nomor_rekening, s.atas_nama_rekening,
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
        $telepon = trim((string)$this->input('nomor_telepon'));
        $alamat = trim((string)$this->input('alamat_lengkap', '-'));
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
            $count = Database::fetchOne("SELECT count(*) as total FROM public.pemasok")['total'] ?? 0;
            $kode = 'SUP-' . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);

            $detailBank = [];
            if (!empty($bankRekening)) {
                $detailBank[] = [
                    'bank' => $bankNama,
                    'nomor_rekening' => $bankRekening,
                    'atas_nama' => $bankAtasNama
                ];
            }

            Database::execute("
                INSERT INTO public.pemasok (
                    kode_pemasok, nama_pemasok, wilayah_id, alamat_lengkap, nomor_telepon,
                    nama_bank, nomor_rekening, atas_nama_rekening, detail_bank, status_aktif
                ) VALUES (
                    :kode, :nama, :wilayah, :alamat, :telp,
                    :nama_bank, :nomor_rek, :atas_nama, :bank, TRUE
                )
            ", [
                'kode' => $kode,
                'nama' => $nama,
                'wilayah' => $wilayahId,
                'alamat' => $alamat,
                'telp' => $telepon,
                'nama_bank' => $bankNama ?: null,
                'nomor_rek' => $bankRekening ?: null,
                'atas_nama' => $bankAtasNama ?: null,
                'bank' => json_encode($detailBank)
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
        $telepon = trim((string)$this->input('nomor_telepon'));
        $alamat = trim((string)$this->input('alamat_lengkap', '-'));
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
            $detailBank = [];
            if (!empty($bankRekening)) {
                $detailBank[] = [
                    'bank' => $bankNama,
                    'nomor_rekening' => $bankRekening,
                    'atas_nama' => $bankAtasNama
                ];
            }

            Database::execute("
                UPDATE public.pemasok SET
                    nama_pemasok = :nama,
                    wilayah_id = :wilayah,
                    alamat_lengkap = :alamat,
                    nomor_telepon = :telp,
                    nama_bank = :nama_bank,
                    nomor_rekening = :nomor_rek,
                    atas_nama_rekening = :atas_nama,
                    detail_bank = :bank,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id
            ", [
                'id' => $id,
                'nama' => $nama,
                'wilayah' => $wilayahId,
                'alamat' => $alamat,
                'telp' => $telepon,
                'nama_bank' => $bankNama ?: null,
                'nomor_rek' => $bankRekening ?: null,
                'atas_nama' => $bankAtasNama ?: null,
                'bank' => json_encode($detailBank),
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


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
        Auth::requireRole(['owner', 'admin']);
    }

    public function index(): void
    {
        try {
            $suppliers = Database::fetchAll("
                SELECT s.id, s.kode_pemasok, s.nama_pemasok, s.alamat_lengkap, s.nomor_telepon,
                       s.detail_bank, s.nama_bank, s.nomor_rekening, s.atas_nama_rekening,
                       s.status_aktif, s.wilayah_id, w.nama_wilayah
                FROM public.pemasok s
                LEFT JOIN public.wilayah w ON s.wilayah_id = w.id
                ORDER BY s.status_aktif DESC, s.nama_pemasok ASC
            ");

            $territories = Database::fetchAll("SELECT id, kode_rute, nama_wilayah FROM public.wilayah WHERE status_aktif = TRUE ORDER BY nama_wilayah ASC");

            $this->view('suppliers.index', [
                'pageTitle' => 'Master Pemasok',
                'pageSubtitle' => 'Kelola Data Supplier Bahan Baku Curah, Plastik & Bumbu',
                'suppliers' => $suppliers,
                'territories' => $territories
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    public function store(): void
    {
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

            $this->flashSuccess("Pemasok {$nama} berhasil ditambahkan!");
            $this->redirect('/suppliers');

        } catch (Throwable $e) {
            $this->flashError('Gagal menambahkan pemasok: ' . $e->getMessage());
            $this->redirect('/suppliers');
        }
    }

    public function update(): void
    {
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

            $this->flashSuccess("Data pemasok {$nama} berhasil diperbarui!");
            $this->redirect('/suppliers');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui pemasok: ' . $e->getMessage());
            $this->redirect('/suppliers');
        }
    }
}


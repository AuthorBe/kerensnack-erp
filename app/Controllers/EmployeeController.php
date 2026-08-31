<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Controllers/EmployeeController.php
 * Pengendali Master Data Karyawan (Admin, Staff Gudang, Pengemasan, Sales-Driver, Mandor).
 */
class EmployeeController extends Controller
{
    public function __construct()
    {
        Auth::requireRole(['owner', 'admin']);
    }

    public function index(): void
    {
        try {
            $employees = Database::fetchAll("
                SELECT k.id, k.nik, k.nama_karyawan, k.posisi, k.tipe_penggajian,
                       k.gaji_pokok_bulanan, k.uang_kehadiran_harian, k.tunjangan_bulanan,
                       k.persentase_komisi_sales, k.nomor_telepon, k.alamat, k.tanggal_bergabung,
                       k.nomor_polisi_kendaraan,
                       k.bank_nama, k.bank_nomor_rekening, k.bank_atas_nama,
                       k.status_aktif,
                       COALESCE(t.saldo, 0) as saldo_tabungan,
                       COALESCE(kb.sisa_kasbon, 0) as sisa_kasbon
                FROM public.karyawan k
                LEFT JOIN public.tabungan t ON k.id = t.karyawan_id
                LEFT JOIN (
                    SELECT karyawan_id, SUM(sisa_pinjaman) as sisa_kasbon
                    FROM public.kasbon 
                    WHERE status_kasbon = 'aktif'
                    GROUP BY karyawan_id
                ) kb ON k.id = kb.karyawan_id
                ORDER BY k.status_aktif DESC, k.posisi ASC, k.nama_karyawan ASC
            ");

            $totalEmployees = count($employees);
            $totalBorongan = count(array_filter($employees, fn($e) => $e['posisi'] === 'pengemasan'));
            $totalSales = count(array_filter($employees, fn($e) => in_array($e['posisi'], ['sales', 'sales_driver'], true)));
            $totalDriver = count(array_filter($employees, fn($e) => $e['posisi'] === 'driver'));
            $totalAdminGudang = count(array_filter($employees, fn($e) => in_array($e['posisi'], ['admin', 'gudang', 'mandor'], true)));

            $this->view('employees.index', [
                'pageTitle' => 'Master Data Karyawan',
                'pageSubtitle' => 'Kelola Data Pegawai Admin, Gudang, Pengemasan, Sales & Driver',
                'employees' => $employees,
                'metrics' => [
                    'total' => $totalEmployees,
                    'borongan' => $totalBorongan,
                    'sales' => $totalSales,
                    'driver' => $totalDriver,
                    'admin_gudang' => $totalAdminGudang
                ]
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    public function store(): void
    {
        $nama = trim((string)$this->input('nama_karyawan'));
        $nik = trim((string)$this->input('nik')) ?: null;
        $posisi = $this->input('posisi', 'pengemasan');
        $tipeGaji = $this->input('tipe_penggajian', 'borongan');
        $gajiPokok = (float)preg_replace('/[^0-9]/', '', (string)$this->input('gaji_pokok_bulanan', '0'));
        $uangHadir = (float)preg_replace('/[^0-9]/', '', (string)$this->input('uang_kehadiran_harian', '0'));
        $tunjangan = (float)preg_replace('/[^0-9]/', '', (string)$this->input('tunjangan_bulanan', '0'));
        $komisi = (float)$this->input('persentase_komisi_sales', 0);
        $telepon = trim((string)$this->input('nomor_telepon'));
        $alamat = trim((string)$this->input('alamat', '-'));
        $nopol = trim((string)$this->input('nomor_polisi_kendaraan', ''));
        $tglBergabung = $this->input('tanggal_bergabung') ?: date('Y-m-d');
        $bankNama = trim((string)$this->input('bank_nama', 'Tunai'));
        $bankRek = trim((string)$this->input('bank_nomor_rekening', ''));
        $bankAn = trim((string)$this->input('bank_atas_nama', ''));

        if (empty($nama)) {
            $this->flashError('Nama karyawan wajib diisi.');
            $this->redirect('/employees');
            return;
        }

        if (!empty($bankRek) && empty($bankAn)) {
            $this->flashError('Pemilik rekening wajib diisi jika nomor rekening diisi.');
            $this->redirect('/employees');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO public.karyawan (
                    nik, nama_karyawan, posisi, tipe_penggajian, gaji_pokok_bulanan,
                    uang_kehadiran_harian, tunjangan_bulanan, persentase_komisi_sales,
                    nomor_telepon, alamat, nomor_polisi_kendaraan, tanggal_bergabung,
                    bank_nama, bank_nomor_rekening, bank_atas_nama,
                    status_aktif
                ) VALUES (
                    :nik, :nama, :posisi, :tipe, :gapok,
                    :hadir, :tunjangan, :komisi,
                    :telp, :alamat, :nopol, :tgl,
                    :bank, :rek, :an,
                    TRUE
                ) RETURNING id
            ");

            $stmt->execute([
                'nik' => $nik,
                'nama' => $nama,
                'posisi' => $posisi,
                'tipe' => $tipeGaji,
                'gapok' => $gajiPokok,
                'hadir' => $uangHadir,
                'tunjangan' => $tunjangan,
                'komisi' => $komisi,
                'telp' => $telepon ?: null,
                'alamat' => $alamat,
                'nopol' => $nopol ?: null,
                'tgl' => $tglBergabung,
                'bank' => $bankNama ?: 'Tunai',
                'rek' => $bankRek ?: null,
                'an' => $bankAn ?: null
            ]);

            $karyawanId = $stmt->fetchColumn();

            // Inisialisasi rekening tabungan
            $pdo->prepare("INSERT INTO public.tabungan (karyawan_id, saldo) VALUES (:id, 0.00) ON CONFLICT DO NOTHING")
                ->execute(['id' => $karyawanId]);

            $pdo->commit();

            $this->flashSuccess("Karyawan {$nama} berhasil ditambahkan!");
            $this->redirect('/employees');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError('Gagal menambahkan karyawan: ' . $e->getMessage());
            $this->redirect('/employees');
        }
    }

    public function update(): void
    {
        $id = $this->input('id');
        $nama = trim((string)$this->input('nama_karyawan'));
        $nik = trim((string)$this->input('nik')) ?: null;
        $posisi = $this->input('posisi', 'pengemasan');
        $tipeGaji = $this->input('tipe_penggajian', 'borongan');
        $gajiPokok = (float)preg_replace('/[^0-9]/', '', (string)$this->input('gaji_pokok_bulanan', '0'));
        $uangHadir = (float)preg_replace('/[^0-9]/', '', (string)$this->input('uang_kehadiran_harian', '0'));
        $tunjangan = (float)preg_replace('/[^0-9]/', '', (string)$this->input('tunjangan_bulanan', '0'));
        $komisi = (float)$this->input('persentase_komisi_sales', 0);
        $telepon = trim((string)$this->input('nomor_telepon'));
        $alamat = trim((string)$this->input('alamat', '-'));
        $nopol = trim((string)$this->input('nomor_polisi_kendaraan', ''));
        $bankNama = trim((string)$this->input('bank_nama', 'Tunai'));
        $bankRek = trim((string)$this->input('bank_nomor_rekening', ''));
        $bankAn = trim((string)$this->input('bank_atas_nama', ''));
        $statusAktif = (bool)$this->input('status_aktif', true);

        if (empty($id) || empty($nama)) {
            $this->flashError('Parameter tidak lengkap.');
            $this->redirect('/employees');
            return;
        }

        if (!empty($bankRek) && empty($bankAn)) {
            $this->flashError('Pemilik rekening wajib diisi jika nomor rekening diisi.');
            $this->redirect('/employees');
            return;
        }

        try {
            Database::execute("
                UPDATE public.karyawan SET
                    nik = :nik,
                    nama_karyawan = :nama,
                    posisi = :posisi,
                    tipe_penggajian = :tipe,
                    gaji_pokok_bulanan = :gapok,
                    uang_kehadiran_harian = :hadir,
                    tunjangan_bulanan = :tunjangan,
                    persentase_komisi_sales = :komisi,
                    nomor_telepon = :telp,
                    alamat = :alamat,
                    nomor_polisi_kendaraan = :nopol,
                    bank_nama = :bank,
                    bank_nomor_rekening = :rek,
                    bank_atas_nama = :an,
                    status_aktif = :aktif,
                    diubah_pada = NOW()
                WHERE id = :id
            ", [
                'id' => $id,
                'nik' => $nik,
                'nama' => $nama,
                'posisi' => $posisi,
                'tipe' => $tipeGaji,
                'gapok' => $gajiPokok,
                'hadir' => $uangHadir,
                'tunjangan' => $tunjangan,
                'komisi' => $komisi,
                'telp' => $telepon ?: null,
                'alamat' => $alamat,
                'nopol' => $nopol ?: null,
                'bank' => $bankNama ?: 'Tunai',
                'rek' => $bankRek ?: null,
                'an' => $bankAn ?: null,
                'aktif' => $statusAktif ? 'true' : 'false'
            ]);

            $this->flashSuccess("Data karyawan {$nama} berhasil diperbarui!");
            $this->redirect('/employees');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui karyawan: ' . $e->getMessage());
            $this->redirect('/employees');
        }
    }

    public function delete(): void
    {
        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID karyawan tidak valid.');
            $this->redirect('/employees');
            return;
        }

        try {
            Database::execute("DELETE FROM public.karyawan WHERE id = :id", ['id' => $id]);
            $this->flashSuccess('Karyawan berhasil dihapus.');
            $this->redirect('/employees');
        } catch (Throwable $e) {
            $this->flashError('Gagal menghapus karyawan: ' . $e->getMessage());
            $this->redirect('/employees');
        }
    }
}

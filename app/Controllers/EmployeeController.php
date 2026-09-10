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
        Auth::requirePermission(['master.employees_view', 'master.employees_manage']);
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
                FROM public.v_karyawan_info k
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
            $totalSales = count(array_filter($employees, fn($e) => $e['posisi'] === 'sales'));
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
                INSERT INTO public.pengguna (
                    nama_lengkap, nik, posisi, nomor_telepon, alamat, nomor_polisi_kendaraan,
                    tanggal_bergabung, bank_nama, bank_nomor_rekening, bank_atas_nama, status_aktif
                ) VALUES (
                    :nama, :nik, :posisi, :telp, :alamat, :nopol, :tgl,
                    :bank, :rek, :an, TRUE
                ) RETURNING id
            ");
            $stmt->execute([
                'nama' => $nama,
                'nik' => $nik,
                'posisi' => $posisi,
                'telp' => $telepon ?: null,
                'alamat' => $alamat,
                'nopol' => $nopol ?: null,
                'tgl' => $tglBergabung,
                'bank' => $bankNama ?: 'Tunai',
                'rek' => $bankRek ?: null,
                'an' => $bankAn ?: null
            ]);
            $penggunaId = $stmt->fetchColumn();

            $stmt = $pdo->prepare("
                INSERT INTO public.karyawan (
                    pengguna_id, tipe_penggajian, gaji_pokok_bulanan,
                    uang_kehadiran_harian, tunjangan_bulanan, persentase_komisi_sales
                ) VALUES (
                    :pengguna_id, :tipe, :gapok, :hadir, :tunjangan, :komisi
                ) RETURNING id
            ");
            $stmt->execute([
                'pengguna_id' => $penggunaId,
                'tipe' => $tipeGaji,
                'gapok' => $gajiPokok,
                'hadir' => $uangHadir,
                'tunjangan' => $tunjangan,
                'komisi' => $komisi
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
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $karyawan = Database::fetchOne("SELECT pengguna_id FROM public.karyawan WHERE id = :id", ['id' => $id]);
            if (!$karyawan) {
                $pdo->rollBack();
                $this->flashError('Data karyawan tidak ditemukan.');
                $this->redirect('/employees');
                return;
            }

            if ($karyawan && $karyawan['pengguna_id']) {
                $stmtP = $pdo->prepare("
                    UPDATE public.pengguna SET
                        nik = :nik,
                        nama_lengkap = :nama,
                        posisi = :posisi,
                        nomor_telepon = :telp,
                        nomor_whatsapp = :telp,
                        alamat = :alamat,
                        nomor_polisi_kendaraan = :nopol,
                        bank_nama = :bank,
                        bank_nomor_rekening = :rek,
                        bank_atas_nama = :an,
                        status_aktif = :aktif,
                        diubah_pada = NOW()
                    WHERE id = :pengguna_id
                ");
                $stmtP->execute([
                    'pengguna_id' => $karyawan['pengguna_id'],
                    'nik' => $nik,
                    'nama' => $nama,
                    'posisi' => $posisi,
                    'telp' => $telepon ?: null,
                    'alamat' => $alamat,
                    'nopol' => $nopol ?: null,
                    'bank' => $bankNama ?: 'Tunai',
                    'rek' => $bankRek ?: null,
                    'an' => $bankAn ?: null,
                    'aktif' => $statusAktif ? 'true' : 'false'
                ]);
            }

            $stmtK = $pdo->prepare("
                UPDATE public.karyawan SET
                    tipe_penggajian = :tipe,
                    gaji_pokok_bulanan = :gapok,
                    uang_kehadiran_harian = :hadir,
                    tunjangan_bulanan = :tunjangan,
                    persentase_komisi_sales = :komisi,
                    diubah_pada = NOW()
                WHERE id = :id
            ");
            $stmtK->execute([
                'id' => $id,
                'tipe' => $tipeGaji,
                'gapok' => $gajiPokok,
                'hadir' => $uangHadir,
                'tunjangan' => $tunjangan,
                'komisi' => $komisi
            ]);

            $pdo->commit();

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
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Cari pengguna yang terhubung dengan karyawan ini
            $karyawan = Database::fetchOne(
                "SELECT pengguna_id FROM public.karyawan WHERE id = :id",
                ['id' => $id]
            );

            // Hapus record karyawan
            $stmtK = $pdo->prepare("DELETE FROM public.karyawan WHERE id = :id");
            $stmtK->execute(['id' => $id]);

            // Jika pengguna terhubung, cek apakah pengguna ini hanya karyawan (tanpa akun login)
            if ($karyawan && $karyawan['pengguna_id']) {
                $penggunaId = $karyawan['pengguna_id'];
                $penggunaData = Database::fetchOne(
                    "SELECT nama_pengguna, peran_id FROM public.pengguna WHERE id = :id",
                    ['id' => $penggunaId]
                );

                // Jika pengguna tidak punya nama_pengguna (akun login), hapus sekalian dari pengguna
                // Jika punya akun login, biarkan pengguna tetap ada (hanya hilangkan data karyawan)
                if ($penggunaData && empty($penggunaData['nama_pengguna'])) {
                    $stmtP = $pdo->prepare("DELETE FROM public.pengguna WHERE id = :id");
                    $stmtP->execute(['id' => $penggunaId]);
                }
            }

            $pdo->commit();
            $this->flashSuccess('Karyawan berhasil dihapus.');
            $this->redirect('/employees');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError('Gagal menghapus karyawan: ' . $e->getMessage());
            $this->redirect('/employees');
        }
    }
}

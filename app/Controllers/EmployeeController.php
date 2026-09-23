<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Controllers/EmployeeController.php
 * Pengendali Master Data Karyawan (Admin, Staff Gudang, Pengemasan, Sales, Driver, Mandor).
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
                SELECT k.id, k.nik, k.nik_pending, k.nama_karyawan, k.posisi, k.tipe_penggajian,
                       k.gaji_pokok_bulanan, k.uang_kehadiran_harian, k.tunjangan_bulanan,
                       k.nomor_telepon, k.nomor_whatsapp, k.alamat, k.tanggal_bergabung,
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
            $totalNikPending = count(array_filter($employees, fn($e) => empty($e['nik']) || !empty($e['nik_pending'])));

            $commissionTiers = Database::fetchAll("
                SELECT id, urutan, nama_tier, omzet_min, omzet_maks, persentase, status_aktif
                FROM public.skema_komisi_sales
                ORDER BY urutan ASC, omzet_min ASC
            ");

            $this->view('employees.index', [
                'pageTitle' => 'Master Data Karyawan',
                'pageSubtitle' => 'Kelola Data Pegawai Admin, Gudang, Pengemasan, Sales & Driver',
                'employees' => $employees,
                'commissionTiers' => $commissionTiers,
                'metrics' => [
                    'total' => $totalEmployees,
                    'borongan' => $totalBorongan,
                    'sales' => $totalSales,
                    'driver' => $totalDriver,
                    'admin_gudang' => $totalAdminGudang,
                    'nik_pending' => $totalNikPending
                ]
            ]);

        } catch (Throwable $e) {
            $this->flashError("Gagal memuat data karyawan: " . $e->getMessage());
            $this->view('employees.index', [
                'pageTitle' => 'Master Data Karyawan',
                'pageSubtitle' => 'Kelola Data Pegawai Admin, Gudang, Pengemasan, Sales & Driver',
                'employees' => [],
                'commissionTiers' => [],
                'metrics' => [
                    'total' => 0,
                    'borongan' => 0,
                    'sales' => 0,
                    'driver' => 0,
                    'admin_gudang' => 0,
                    'nik_pending' => 0
                ]
            ]);
        }
    }

    public function store(): void
    {
        Auth::requirePermission('master.employees_manage');

        $nama = trim((string)$this->input('nama_karyawan'));
        $nikPending = (bool)$this->input('nik_pending', false);
        $nikRaw = trim((string)$this->input('nik'));
        $nik = preg_replace('/[^0-9]/', '', $nikRaw);
        $posisi = $this->input('posisi', 'pengemasan');
        $tipeGaji = strtolower(trim((string)$this->input('tipe_penggajian', 'borongan')));
        if (!in_array($tipeGaji, ['borongan', 'bulanan'], true)) {
            $tipeGaji = ($posisi === 'pengemasan') ? 'borongan' : 'bulanan';
        }
        $gajiPokok = (float)preg_replace('/[^0-9]/', '', (string)$this->input('gaji_pokok_bulanan', '0'));
        $uangHadir = (float)preg_replace('/[^0-9]/', '', (string)$this->input('uang_kehadiran_harian', '0'));
        $tunjangan = (float)preg_replace('/[^0-9]/', '', (string)$this->input('tunjangan_bulanan', '0'));

        $whatsapp = trim((string)($this->input('nomor_whatsapp') ?: $this->input('nomor_telepon')));
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

        if ($nikPending) {
            // Karyawan belum punya NIK — simpan sebagai NULL dengan flag pending
            $nik = null;
        } else {
            // NIK wajib 16 digit angka KTP asli
            if (empty($nik) || strlen($nik) !== 16 || !ctype_digit($nik)) {
                $this->flashError('NIK wajib diisi dengan tepat 16 digit angka KTP asli, atau centang "Belum memiliki NIK".');
                $this->redirect('/employees');
                return;
            }

            $existingNik = Database::fetchOne("SELECT id, nama_lengkap FROM public.pengguna WHERE nik = :nik LIMIT 1", ['nik' => $nik]);
            if ($existingNik) {
                $this->flashError("NIK '{$nik}' sudah terdaftar atas nama {$existingNik['nama_lengkap']}.");
                $this->redirect('/employees');
                return;
            }
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
                    nama_lengkap, nik, nik_pending, posisi, nomor_telepon, nomor_whatsapp, alamat, nomor_polisi_kendaraan,
                    tanggal_bergabung, bank_nama, bank_nomor_rekening, bank_atas_nama, status_aktif
                ) VALUES (
                    :nama, :nik, :nik_pending, :posisi, :wa, :wa, :alamat, :nopol, :tgl,
                    :bank, :rek, :an, TRUE
                ) RETURNING id
            ");
            $stmt->execute([
                'nama'        => $nama,
                'nik'         => $nikPending ? null : $nik,
                'nik_pending' => $nikPending ? 'true' : 'false',
                'posisi'      => $posisi,
                'wa'          => $whatsapp ?: null,
                'alamat'      => $alamat,
                'nopol'       => $nopol ?: null,
                'tgl'         => $tglBergabung,
                'bank'        => $bankNama ?: 'Tunai',
                'rek'         => $bankRek ?: null,
                'an'          => $bankAn ?: null
            ]);
            $penggunaId = $stmt->fetchColumn();

            $stmt = $pdo->prepare("
                INSERT INTO public.karyawan (
                    pengguna_id, tipe_penggajian, gaji_pokok_bulanan,
                    uang_kehadiran_harian, tunjangan_bulanan
                ) VALUES (
                    :pengguna_id, :tipe, :gapok, :hadir, :tunjangan
                ) RETURNING id
            ");
            $stmt->execute([
                'pengguna_id' => $penggunaId,
                'tipe'        => $tipeGaji,
                'gapok'       => $gajiPokok,
                'hadir'       => $uangHadir,
                'tunjangan'   => $tunjangan
            ]);
            
            $karyawanId = $stmt->fetchColumn();

            // Inisialisasi rekening tabungan
            $pdo->prepare("INSERT INTO public.tabungan (karyawan_id, saldo) VALUES (:id, 0.00) ON CONFLICT DO NOTHING")
                ->execute(['id' => $karyawanId]);

            $pdo->commit();

            $nikLabel = $nikPending ? 'NIK Pending (belum ada KTP)' : $nik;
            \App\Helpers\ActivityLog::log(
                'hr_payroll',
                'CREATE',
                "Mendaftarkan karyawan baru: {$nama} ({$posisi}, Tipe: {$tipeGaji}, NIK: {$nikLabel})",
                'karyawan',
                (string)$karyawanId,
                null,
                [
                    'nama_lengkap'        => $nama,
                    'nik'                 => $nikPending ? null : $nik,
                    'nik_pending'         => $nikPending,
                    'posisi'              => $posisi,
                    'tipe_penggajian'     => $tipeGaji,
                    'gaji_pokok_bulanan'  => $gajiPokok,
                    'uang_kehadiran_harian' => $uangHadir,
                    'tunjangan_bulanan'   => $tunjangan
                ]
            );

            $pendingNote = $nikPending ? ' (NIK belum diisi — lengkapi setelah KTP tersedia)' : '';
            $this->flashSuccess("Karyawan {$nama} berhasil ditambahkan!{$pendingNote}");
            $this->redirect('/employees');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError('Gagal menambahkan karyawan: ' . $e->getMessage());
            $this->redirect('/employees');
        }
    }

    public function update(): void
    {
        Auth::requirePermission('master.employees_manage');

        $id = $this->input('id');
        $nama = trim((string)$this->input('nama_karyawan'));
        $nikPending = (bool)$this->input('nik_pending', false);
        $nikRaw = trim((string)$this->input('nik'));
        $nik = preg_replace('/[^0-9]/', '', $nikRaw);
        $posisi = $this->input('posisi', 'pengemasan');
        $tipeGaji = strtolower(trim((string)$this->input('tipe_penggajian', 'borongan')));
        if (!in_array($tipeGaji, ['borongan', 'bulanan'], true)) {
            $tipeGaji = ($posisi === 'pengemasan') ? 'borongan' : 'bulanan';
        }
        $gajiPokok = (float)preg_replace('/[^0-9]/', '', (string)$this->input('gaji_pokok_bulanan', '0'));
        $uangHadir = (float)preg_replace('/[^0-9]/', '', (string)$this->input('uang_kehadiran_harian', '0'));
        $tunjangan = (float)preg_replace('/[^0-9]/', '', (string)$this->input('tunjangan_bulanan', '0'));

        $whatsapp = trim((string)($this->input('nomor_whatsapp') ?: $this->input('nomor_telepon')));
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

        if ($nikPending) {
            // Masih pending — NIK tetap NULL
            $nik = null;
        } else {
            // NIK harus valid 16 digit (bisa digunakan untuk mengisi NIK pending sebelumnya)
            if (empty($nik) || strlen($nik) !== 16 || !ctype_digit($nik)) {
                $this->flashError('NIK wajib diisi dengan tepat 16 digit angka KTP asli, atau centang "Belum memiliki NIK".');
                $this->redirect('/employees');
                return;
            }
        }

        if (!empty($bankRek) && empty($bankAn)) {
            $this->flashError('Pemilik rekening wajib diisi jika nomor rekening diisi.');
            $this->redirect('/employees');
            return;
        }

        try {
            $karyawan = Database::fetchOne("SELECT pengguna_id FROM public.karyawan WHERE id = :id", ['id' => $id]);
            if (!$karyawan) {
                $this->flashError('Data karyawan tidak ditemukan.');
                $this->redirect('/employees');
                return;
            }

            if (!$nikPending && !empty($nik)) {
                $existingNik = Database::fetchOne(
                    "SELECT id, nama_lengkap FROM public.pengguna WHERE nik = :nik AND id != :uid LIMIT 1",
                    ['nik' => $nik, 'uid' => $karyawan['pengguna_id']]
                );
                if ($existingNik) {
                    $this->flashError("NIK '{$nik}' sudah digunakan oleh karyawan lain ({$existingNik['nama_lengkap']}).");
                    $this->redirect('/employees');
                    return;
                }
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            if ($karyawan && $karyawan['pengguna_id']) {
                $stmtP = $pdo->prepare("
                    UPDATE public.pengguna SET
                        nik = :nik,
                        nik_pending = :nik_pending,
                        nama_lengkap = :nama,
                        posisi = :posisi,
                        nomor_telepon = :wa,
                        nomor_whatsapp = :wa,
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
                    'nik'         => $nikPending ? null : $nik,
                    'nik_pending' => $nikPending ? 'true' : 'false',
                    'nama'        => $nama,
                    'posisi'      => $posisi,
                    'wa'          => $whatsapp ?: null,
                    'alamat'      => $alamat,
                    'nopol'       => $nopol ?: null,
                    'bank'        => $bankNama ?: 'Tunai',
                    'rek'         => $bankRek ?: null,
                    'an'          => $bankAn ?: null,
                    'aktif'       => $statusAktif ? 'true' : 'false'
                ]);
            }

            $stmtK = $pdo->prepare("
                UPDATE public.karyawan SET
                    tipe_penggajian = :tipe,
                    gaji_pokok_bulanan = :gapok,
                    uang_kehadiran_harian = :hadir,
                    tunjangan_bulanan = :tunjangan,
                    diubah_pada = NOW()
                WHERE id = :id
            ");
            $stmtK->execute([
                'id'         => $id,
                'tipe'       => $tipeGaji,
                'gapok'      => $gajiPokok,
                'hadir'      => $uangHadir,
                'tunjangan'  => $tunjangan
            ]);

            $oldData = Database::fetchOne("
                SELECT k.tipe_penggajian, k.gaji_pokok_bulanan, k.uang_kehadiran_harian, k.tunjangan_bulanan,
                       p.nik, p.nik_pending, p.nama_lengkap, p.posisi, p.nomor_telepon, p.bank_nama, p.bank_nomor_rekening, p.status_aktif
                FROM public.karyawan k
                LEFT JOIN public.pengguna p ON k.pengguna_id = p.id
                WHERE k.id = :id
            ", ['id' => $id]);

            $pdo->commit();

            $nikLabel = $nikPending ? 'NIK Pending' : $nik;
            \App\Helpers\ActivityLog::log(
                'hr_payroll',
                'UPDATE',
                "Memperbarui data karyawan {$nama} (NIK: {$nikLabel})",
                'karyawan',
                (string)$id,
                $oldData,
                [
                    'nama_lengkap'          => $nama,
                    'nik'                   => $nikPending ? null : $nik,
                    'nik_pending'           => $nikPending,
                    'posisi'                => $posisi,
                    'tipe_penggajian'       => $tipeGaji,
                    'gaji_pokok_bulanan'    => $gajiPokok,
                    'uang_kehadiran_harian' => $uangHadir,
                    'tunjangan_bulanan'     => $tunjangan,
                    'status_aktif'          => $statusAktif
                ]
            );

            $this->flashSuccess("Data karyawan {$nama} berhasil diperbarui!");
            $this->redirect('/employees');

        } catch (Throwable $e) {
            $this->flashError('Gagal memperbarui karyawan: ' . $e->getMessage());
            $this->redirect('/employees');
        }
    }


    public function delete(): void
    {
        Auth::requirePermission('master.employees_manage');

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID karyawan tidak valid.');
            $this->redirect('/employees');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Cari data karyawan & pengguna terhubung
            $karyawan = Database::fetchOne(
                "SELECT k.id, k.pengguna_id, p.nama_lengkap, p.status_aktif 
                 FROM public.karyawan k 
                 JOIN public.pengguna p ON k.pengguna_id = p.id 
                 WHERE k.id = :id",
                ['id' => $id]
            );

            if (!$karyawan) {
                $pdo->rollBack();
                $this->flashError('Data karyawan tidak ditemukan.');
                $this->redirect('/employees');
                return;
            }

            $namaKaryawan = $karyawan['nama_lengkap'] ?? 'Karyawan';

            // SOFT-DELETE: Set status_aktif = FALSE pada pengguna & karyawan
            if (!empty($karyawan['pengguna_id'])) {
                $stmtP = $pdo->prepare("
                    UPDATE public.pengguna 
                    SET status_aktif = FALSE, diubah_pada = NOW() 
                    WHERE id = :pengguna_id
                ");
                $stmtP->execute(['pengguna_id' => $karyawan['pengguna_id']]);
            }

            $stmtK = $pdo->prepare("
                UPDATE public.karyawan 
                SET diubah_pada = NOW() 
                WHERE id = :id
            ");
            $stmtK->execute(['id' => $id]);

            $pdo->commit();

            // Catat log aktivitas
            \App\Helpers\ActivityLog::log(
                'hr_payroll',
                'NONAKTIFKAN_KARYAWAN',
                "Menonaktifkan karyawan {$namaKaryawan} (Soft-delete)",
                'karyawan',
                $id
            );

            $this->flashSuccess("Karyawan {$namaKaryawan} berhasil dinonaktifkan. Seluruh histori transaksi dan penggajian tetap aman tersimpan.");
            $this->redirect('/employees');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError('Gagal menonaktifkan karyawan: ' . $e->getMessage());
            $this->redirect('/employees');
        }
    }

    /**
     * Batch save skema komisi sales bertingkat
     * POST /employees/commission-tiers/batch-save
     */
    public function saveCommissionTiersBatch(): void
    {
        Auth::requirePermission('master.employees_manage');

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

        try {
            $rawJson = file_get_contents('php://input');
            $jsonData = json_decode($rawJson, true);
            $tiers = $jsonData['tiers'] ?? $this->input('tiers', []);

            if (is_string($tiers)) {
                $tiers = json_decode($tiers, true) ?? [];
            }

            if (!is_array($tiers) || empty($tiers)) {
                throw new \InvalidArgumentException("Daftar tier komisi tidak boleh kosong.");
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $cleanedTiers = [];
            foreach ($tiers as $index => $t) {
                $urutan = (int)($t['urutan'] ?? ($index + 1));
                $namaTier = trim((string)($t['nama_tier'] ?? "Tier " . ($index + 1)));
                if (empty($namaTier)) {
                    throw new \InvalidArgumentException("Nama Tier pada urutan #{$urutan} tidak boleh kosong.");
                }

                $minRaw = str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], (string)($t['omzet_min'] ?? '0'));
                $omzetMin = (float)$minRaw;
                if ($omzetMin < 0) {
                    throw new \InvalidArgumentException("Omzet minimum pada {$namaTier} tidak boleh negatif.");
                }

                $omzetMaks = null;
                $tanpaBatas = !empty($t['tanpa_batas']) || !isset($t['omzet_maks']) || $t['omzet_maks'] === '' || $t['omzet_maks'] === null;
                if (!$tanpaBatas) {
                    $maksRaw = str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], (string)$t['omzet_maks']);
                    $omzetMaks = (float)$maksRaw;
                    if ($omzetMaks <= $omzetMin) {
                        throw new \InvalidArgumentException("Omzet maksimum pada {$namaTier} harus lebih besar dari omzet minimum.");
                    }
                }

                $persenRaw = str_replace(['%', ' ', ','], ['', '', '.'], (string)($t['persentase'] ?? '0'));
                $persentase = (float)$persenRaw;
                if ($persentase < 0 || $persentase > 100) {
                    throw new \InvalidArgumentException("Persentase komisi pada {$namaTier} harus berada di antara 0% dan 100%.");
                }

                $statusAktif = isset($t['status_aktif']) ? (bool)$t['status_aktif'] : true;

                $cleanedTiers[] = [
                    'id' => (!empty($t['id']) && preg_match('/^[0-9a-fA-F-]{36}$/', $t['id'])) ? $t['id'] : null,
                    'urutan' => $urutan,
                    'nama_tier' => $namaTier,
                    'omzet_min' => $omzetMin,
                    'omzet_maks' => $omzetMaks,
                    'persentase' => $persentase,
                    'status_aktif' => $statusAktif,
                ];
            }

            // Hapus seluruh konfigurasi lama dan masukkan konfigurasi baru dalam satu transaksi
            $pdo->exec("DELETE FROM public.skema_komisi_sales");

            $stmt = $pdo->prepare("
                INSERT INTO public.skema_komisi_sales 
                    (id, urutan, nama_tier, omzet_min, omzet_maks, persentase, status_aktif, dibuat_pada, diubah_pada)
                VALUES 
                    (COALESCE(:id, gen_random_uuid()), :urutan, :nama_tier, :omzet_min, :omzet_maks, :persentase, :status_aktif, NOW(), NOW())
            ");

            foreach ($cleanedTiers as $tier) {
                $stmt->execute([
                    'id' => $tier['id'],
                    'urutan' => $tier['urutan'],
                    'nama_tier' => $tier['nama_tier'],
                    'omzet_min' => $tier['omzet_min'],
                    'omzet_maks' => $tier['omzet_maks'],
                    'persentase' => $tier['persentase'],
                    'status_aktif' => $tier['status_aktif'] ? 'true' : 'false'
                ]);
            }

            $pdo->commit();

            \App\Helpers\ActivityLog::log(
                'hr_payroll',
                'UPDATE',
                "Memperbarui konfigurasi skema komisi sales bertingkat (" . count($cleanedTiers) . " tingkat tier)",
                'skema_komisi_sales',
                null,
                null,
                ['tiers' => array_map(fn($t) => ['nama' => $t['nama_tier'], 'persen' => $t['persentase'], 'min' => $t['omzet_min'], 'maks' => $t['omzet_maks']], $cleanedTiers)]
            );

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Skema komisi sales bertingkat berhasil diperbarui.'
                ]);
                exit;
            }

            $this->flashSuccess('Skema komisi sales bertingkat berhasil disimpan.');
            $this->redirect('/employees');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }

            $this->flashError("Gagal menyimpan skema komisi: " . $e->getMessage());
            $this->redirect('/employees');
        }
    }
}

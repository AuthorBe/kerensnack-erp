<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
use App\Helpers\CSRF;
use App\Helpers\Format;
use Database;
use Throwable;

/**
 * app/Controllers/PenarikanGajiController.php
 * Pengendali Modul Penarikan Gaji / Uang Kehadiran Harian Karyawan Bulanan Keren One ERP
 */
class PenarikanGajiController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission(['hr.penarikan_view', 'hr.penarikan_manage']);
    }

    /**
     * Halaman Utama Daftar Penarikan Gaji
     */
    public function index(): void
    {
        try {
            $tglAwal = (string)$this->input('tanggal_awal', date('Y-m-01'));
            $tglAkhir = (string)$this->input('tanggal_akhir', date('Y-m-d'));
            $karyawanId = (string)$this->input('karyawan_id', '');
            $status = (string)$this->input('status', 'semua');

            $penarikanList = Database::fetchAll("
                SELECT
                    pg.id, pg.karyawan_id, pg.tanggal, pg.nominal, pg.keterangan, pg.penggajian_id,
                    pg.akun_kas_id, pg.dibuat_pada,
                    v.nama_karyawan, v.nama_panggilan, v.posisi,
                    p.nomor_referensi as nomor_payroll, p.nama_payroll
                FROM public.penarikan_gaji pg
                JOIN public.v_karyawan_info v ON v.id = pg.karyawan_id
                LEFT JOIN public.penggajian p ON p.id = pg.penggajian_id
                WHERE pg.tanggal BETWEEN :tgl_awal AND :tgl_akhir
                  AND (:kid = '' OR pg.karyawan_id = :kid_uuid)
                  AND (
                      :status = 'semua'
                      OR (:status IN ('belum_payroll', 'pending', 'terbuka', 'aktif') AND pg.penggajian_id IS NULL)
                      OR (:status IN ('terkunci', 'locked', 'payroll') AND pg.penggajian_id IS NOT NULL)
                  )
                ORDER BY pg.tanggal DESC, v.nama_karyawan ASC
            ", [
                'tgl_awal' => $tglAwal,
                'tgl_akhir' => $tglAkhir,
                'kid' => $karyawanId,
                'kid_uuid' => !empty($karyawanId) ? $karyawanId : null,
                'status' => $status
            ]);

            // Karyawan Bulanan Aktif untuk Modal Form
            $karyawanBulanan = Database::fetchAll("
                SELECT k.id, v.nama_karyawan, v.posisi, k.uang_kehadiran_harian, k.gaji_pokok_bulanan
                FROM public.karyawan k
                JOIN public.v_karyawan_info v ON v.id = k.id
                WHERE v.status_aktif = TRUE AND k.tipe_penggajian = 'bulanan'
                ORDER BY v.nama_karyawan ASC
            ");

            // Summary metrics
            $totalNominal = array_sum(array_column($penarikanList, 'nominal'));
            $totalBelumPayroll = 0.00;
            $totalTerkunciPayroll = 0.00;
            $countBelumPayroll = 0;
            $countTerkunciPayroll = 0;
            $uniqueEmployees = [];

            foreach ($penarikanList as $p) {
                if (!empty($p['penggajian_id'])) {
                    $totalTerkunciPayroll += (float)$p['nominal'];
                    $countTerkunciPayroll++;
                } else {
                    $totalBelumPayroll += (float)$p['nominal'];
                    $countBelumPayroll++;
                }
                $uniqueEmployees[$p['karyawan_id']] = true;
            }

            // Master Akun Kas Aktif (Non-Escrow) untuk Pilihan Sumber Dana Pengeluaran Uang Harian
            $akunKasList = Database::fetchAll("
                SELECT id, nama_akun, tipe_akun, saldo_saat_ini, is_escrow, is_default_pos
                FROM public.akun_kas
                WHERE status_aktif = TRUE AND is_escrow = FALSE
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            $this->view('penarikan_gaji.index', [
                'pageTitle' => 'Penarikan Gaji Harian',
                'pageSubtitle' => 'Pencatatan & Monitoring Ambil Uang Karyawan Bulanan',
                'tglAwal' => $tglAwal,
                'tglAkhir' => $tglAkhir,
                'karyawanId' => $karyawanId,
                'status' => $status,
                'penarikanList' => $penarikanList,
                'karyawanBulanan' => $karyawanBulanan,
                'akunKasList' => $akunKasList,
                'activeCashAccounts' => $akunKasList,
                'totalNominal' => $totalNominal,
                'totalBelumPayroll' => $totalBelumPayroll,
                'totalTerkunciPayroll' => $totalTerkunciPayroll,
                'totalPending' => $totalBelumPayroll,
                'totalLocked' => $totalTerkunciPayroll,
                'countTotal' => count($penarikanList),
                'countBelumPayroll' => $countBelumPayroll,
                'countTerkunciPayroll' => $countTerkunciPayroll,
                'countPending' => $countBelumPayroll,
                'countLocked' => $countTerkunciPayroll,
                'countEmployees' => count($uniqueEmployees)
            ], 'layouts.master');
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat data penarikan gaji: ' . $e->getMessage());
            $this->redirect('/dashboard');
        }
    }

    /**
     * POST Tambah Penarikan Gaji Manual
     */
    public function store(): void
    {
        Auth::requirePermission('hr.penarikan_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/penarikan-gaji');
            return;
        }

        $karyawanId = (string)$this->input('karyawan_id', '');
        $tanggal = (string)$this->input('tanggal', date('Y-m-d'));
        $nominal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('nominal', '0'));
        $keterangan = trim((string)$this->input('keterangan', 'Ambil Uang Harian'));
        $akunKasId = (string)$this->input('akun_kas_id', '');

        if (empty($karyawanId)) {
            $this->flashError('Karyawan wajib dipilih.');
            $this->redirect('/penarikan-gaji');
            return;
        }

        if ($nominal <= 0) {
            $this->flashError('Nominal penarikan harus lebih dari Rp 0.');
            $this->redirect('/penarikan-gaji');
            return;
        }

        if (empty($akunKasId)) {
            $this->flashError('Akun kas pengeluaran uang harian wajib dipilih.');
            $this->redirect('/penarikan-gaji');
            return;
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $karyawan = Database::fetchOne("
                SELECT k.id, v.nama_karyawan, k.tipe_penggajian 
                FROM public.karyawan k
                JOIN public.v_karyawan_info v ON v.id = k.id
                WHERE k.id = :kid AND v.status_aktif = TRUE
            ", ['kid' => $karyawanId]);

            if (!$karyawan) {
                $pdo->rollBack();
                $this->flashError('Data karyawan tidak ditemukan atau non-aktif.');
                $this->redirect('/penarikan-gaji');
                return;
            }

            // Validasi & Lock Akun Kas
            $stmtKas = $pdo->prepare("
                SELECT id, nama_akun, saldo_saat_ini, is_escrow 
                FROM public.akun_kas 
                WHERE id = :id AND status_aktif = TRUE 
                FOR UPDATE
            ");
            $stmtKas->execute(['id' => $akunKasId]);
            $selectedKas = $stmtKas->fetch(\PDO::FETCH_ASSOC);

            if (!$selectedKas) {
                $pdo->rollBack();
                $this->flashError('Akun kas pengeluaran tidak valid atau non-aktif.');
                $this->redirect('/penarikan-gaji');
                return;
            }

            if (!empty($selectedKas['is_escrow'])) {
                $pdo->rollBack();
                $this->flashError('Akun kas titipan escrow tabungan tidak boleh digunakan untuk penarikan gaji.');
                $this->redirect('/penarikan-gaji');
                return;
            }

            $currentSaldo = (float)$selectedKas['saldo_saat_ini'];
            if ($nominal > $currentSaldo) {
                $pdo->rollBack();
                $this->flashError("Saldo akun kas '{$selectedKas['nama_akun']}' (" . Format::rupiah($currentSaldo) . ") tidak mencukupi untuk penarikan (" . Format::rupiah($nominal) . ").");
                $this->redirect('/penarikan-gaji');
                return;
            }

            // 1. Insert Penarikan Gaji
            $stmtInsert = $pdo->prepare("
                INSERT INTO public.penarikan_gaji (
                    karyawan_id, tanggal, nominal, keterangan, akun_kas_id, dibuat_pada
                ) VALUES (
                    :kid, :tgl, :nominal, :ket, :kas_id, NOW()
                ) RETURNING id
            ");
            $stmtInsert->execute([
                'kid' => $karyawanId,
                'tgl' => $tanggal,
                'nominal' => $nominal,
                'ket' => !empty($keterangan) ? $keterangan : 'Ambil Uang Harian',
                'kas_id' => $akunKasId
            ]);
            $pgId = (string)$stmtInsert->fetchColumn();

            // 2. Potong Saldo Akun Kas
            $saldoBaru = $currentSaldo - $nominal;
            $pdo->prepare("
                UPDATE public.akun_kas 
                SET saldo_saat_ini = saldo_saat_ini - :nom, diubah_pada = NOW() 
                WHERE id = :id
            ")->execute([
                'nom' => $nominal,
                'id' => $akunKasId
            ]);

            // 3. Catat Arus Kas Keluar
            $userId = Auth::user()['id'] ?? null;
            $pdo->prepare("
                INSERT INTO public.arus_kas (
                    akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                    nominal, keterangan, referensi_tabel, referensi_id,
                    saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :kas_id, :tgl, 'keluar', 'penarikan_gaji_harian',
                    :nom, :ket, 'penarikan_gaji', :ref_id,
                    :saldo_berjalan, :uid, NOW()
                )
            ")->execute([
                'kas_id' => $akunKasId,
                'tgl' => $tanggal,
                'nom' => $nominal,
                'ket' => "Penarikan gaji/uang hadir harian {$karyawan['nama_karyawan']}" . (!empty($keterangan) ? " ({$keterangan})" : ''),
                'ref_id' => $pgId,
                'saldo_berjalan' => $saldoBaru,
                'uid' => $userId
            ]);

            $pdo->commit();

            ActivityLog::log(
                'hr_payroll',
                'INPUT_PENARIKAN_GAJI',
                "Mencatat penarikan gaji {$karyawan['nama_karyawan']} sebesar " . Format::rupiah($nominal) . " via {$selectedKas['nama_akun']} tanggal {$tanggal}.",
                'penarikan_gaji',
                $pgId
            );

            $this->flashSuccess("Penarikan gaji berhasil dicatat dan langsung dipotong dari kas {$selectedKas['nama_akun']}.");
            $this->redirect('/penarikan-gaji');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal mencatat penarikan gaji: ' . $e->getMessage());
            $this->redirect('/penarikan-gaji');
        }
    }

    /**
     * POST Perbarui Catatan Penarikan Gaji Manual
     */
    public function update(): void
    {
        Auth::requirePermission('hr.penarikan_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/penarikan-gaji');
            return;
        }

        $id = (string)$this->input('id', '');
        $karyawanId = (string)$this->input('karyawan_id', '');
        $tanggal = (string)$this->input('tanggal', date('Y-m-d'));
        $nominal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('nominal', '0'));
        $keterangan = trim((string)$this->input('keterangan', ''));
        $akunKasId = (string)$this->input('akun_kas_id', '');

        if (empty($id)) {
            $this->flashError('ID penarikan tidak valid.');
            $this->redirect('/penarikan-gaji');
            return;
        }

        if (empty($karyawanId)) {
            $this->flashError('Karyawan wajib dipilih.');
            $this->redirect('/penarikan-gaji');
            return;
        }

        if ($nominal <= 0) {
            $this->flashError('Nominal penarikan harus lebih dari Rp 0.');
            $this->redirect('/penarikan-gaji');
            return;
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $existing = Database::fetchOne("
                SELECT id, nominal, akun_kas_id, penggajian_id 
                FROM public.penarikan_gaji 
                WHERE id = :id FOR UPDATE
            ", ['id' => $id]);

            if (!$existing) {
                $pdo->rollBack();
                $this->flashError('Catatan penarikan gaji tidak ditemukan.');
                $this->redirect('/penarikan-gaji');
                return;
            }

            if (!empty($existing['penggajian_id'])) {
                $pdo->rollBack();
                $this->flashError('Tidak dapat mengubah penarikan gaji yang telah terkunci oleh proses payroll.');
                $this->redirect('/penarikan-gaji');
                return;
            }

            $karyawan = Database::fetchOne("
                SELECT k.id, v.nama_karyawan 
                FROM public.karyawan k
                JOIN public.v_karyawan_info v ON v.id = k.id
                WHERE k.id = :kid AND v.status_aktif = TRUE
            ", ['kid' => $karyawanId]);

            if (!$karyawan) {
                $pdo->rollBack();
                $this->flashError('Data karyawan tidak ditemukan atau non-aktif.');
                $this->redirect('/penarikan-gaji');
                return;
            }

            $targetKasId = !empty($akunKasId) ? $akunKasId : $existing['akun_kas_id'];
            $oldNominal = (float)$existing['nominal'];
            $oldKasId = $existing['akun_kas_id'];

            // Jika kas lama ada, kembalikan dulu saldonya
            if (!empty($oldKasId)) {
                $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + :nom, diubah_pada = NOW() WHERE id = :id")->execute(['nom' => $oldNominal, 'id' => $oldKasId]);
                $pdo->prepare("DELETE FROM public.arus_kas WHERE referensi_tabel = 'penarikan_gaji' AND referensi_id = :id")->execute(['id' => $id]);
            }

            // Jika target kas dipilih, potong saldo target kas
            if (!empty($targetKasId)) {
                $stmtKas = $pdo->prepare("SELECT id, nama_akun, saldo_saat_ini, is_escrow FROM public.akun_kas WHERE id = :id AND status_aktif = TRUE FOR UPDATE");
                $stmtKas->execute(['id' => $targetKasId]);
                $selectedKas = $stmtKas->fetch(\PDO::FETCH_ASSOC);

                if (!$selectedKas || (!empty($selectedKas['is_escrow']))) {
                    $pdo->rollBack();
                    $this->flashError('Akun kas pengeluaran tidak valid atau bertipe escrow.');
                    $this->redirect('/penarikan-gaji');
                    return;
                }

                if ($nominal > (float)$selectedKas['saldo_saat_ini']) {
                    $pdo->rollBack();
                    $this->flashError("Saldo akun kas '{$selectedKas['nama_akun']}' tidak mencukupi untuk update nominal ini.");
                    $this->redirect('/penarikan-gaji');
                    return;
                }

                $saldoBaru = (float)$selectedKas['saldo_saat_ini'] - $nominal;
                $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :nom, diubah_pada = NOW() WHERE id = :id")->execute(['nom' => $nominal, 'id' => $targetKasId]);

                $userId = Auth::user()['id'] ?? null;
                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                        nominal, keterangan, referensi_tabel, referensi_id,
                        saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :kas_id, :tgl, 'keluar', 'penarikan_gaji_harian',
                        :nom, :ket, 'penarikan_gaji', :ref_id,
                        :saldo_berjalan, :uid, NOW()
                    )
                ")->execute([
                    'kas_id' => $targetKasId,
                    'tgl' => $tanggal,
                    'nom' => $nominal,
                    'ket' => "Penarikan gaji {$karyawan['nama_karyawan']}" . (!empty($keterangan) ? " ({$keterangan})" : ''),
                    'ref_id' => $id,
                    'saldo_berjalan' => $saldoBaru,
                    'uid' => $userId
                ]);
            }

            Database::execute("
                UPDATE public.penarikan_gaji
                SET karyawan_id = :kid,
                    tanggal = :tgl,
                    nominal = :nominal,
                    keterangan = :ket,
                    akun_kas_id = :kas_id
                WHERE id = :id AND penggajian_id IS NULL
            ", [
                'id' => $id,
                'kid' => $karyawanId,
                'tgl' => $tanggal,
                'nominal' => $nominal,
                'ket' => !empty($keterangan) ? $keterangan : 'Penarikan manual',
                'kas_id' => $targetKasId
            ]);

            $pdo->commit();

            ActivityLog::log(
                'hr_payroll',
                'UPDATE_PENARIKAN_GAJI',
                "Memperbarui penarikan gaji {$karyawan['nama_karyawan']} menjadi " . Format::rupiah($nominal) . " tanggal {$tanggal}.",
                'penarikan_gaji',
                $id
            );

            $this->flashSuccess('Catatan penarikan gaji berhasil diperbarui.');
            $this->redirect('/penarikan-gaji');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal memperbarui penarikan gaji: ' . $e->getMessage());
            $this->redirect('/penarikan-gaji');
        }
    }

    /**
     * POST Hapus Catatan Penarikan Gaji
     */
    public function delete(): void
    {
        Auth::requirePermission('hr.penarikan_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/penarikan-gaji');
            return;
        }

        $id = (string)$this->input('id', '');
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $pg = Database::fetchOne("
                SELECT id, nominal, akun_kas_id, penggajian_id 
                FROM public.penarikan_gaji 
                WHERE id = :id FOR UPDATE
            ", ['id' => $id]);

            if (!$pg) {
                $pdo->rollBack();
                $this->flashError('Data penarikan tidak ditemukan.');
                $this->redirect('/penarikan-gaji');
                return;
            }

            if (!empty($pg['penggajian_id'])) {
                $pdo->rollBack();
                $this->flashError('Tidak dapat menghapus penarikan yang sudah terkunci oleh payroll.');
                $this->redirect('/penarikan-gaji');
                return;
            }

            // Kembalikan saldo kas jika ada
            if (!empty($pg['akun_kas_id'])) {
                $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + :nom, diubah_pada = NOW() WHERE id = :id")->execute(['nom' => $pg['nominal'], 'id' => $pg['akun_kas_id']]);
                $pdo->prepare("DELETE FROM public.arus_kas WHERE referensi_tabel = 'penarikan_gaji' AND referensi_id = :id")->execute(['id' => $id]);
            }

            $pdo->prepare("DELETE FROM public.penarikan_gaji WHERE id = :id AND penggajian_id IS NULL")->execute(['id' => $id]);

            $pdo->commit();

            ActivityLog::log('hr_payroll', 'DELETE_PENARIKAN_GAJI', "Menghapus penarikan gaji ID {$id} dan mengembalikan saldo kas.", 'penarikan_gaji', $id);

            $this->flashSuccess('Catatan penarikan gaji berhasil dihapus dan saldo kas dikembalikan.');
            $this->redirect('/penarikan-gaji');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menghapus penarikan gaji: ' . $e->getMessage());
            $this->redirect('/penarikan-gaji');
        }
    }
}

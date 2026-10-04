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
 * app/Controllers/KasbonController.php
 * Pengendali Modul Pinjaman / Kasbon Karyawan Keren One ERP
 */
class KasbonController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission(['hr.kasbon_view', 'hr.kasbon_manage']);
    }

    /**
     * Halaman Utama Daftar Kasbon Karyawan
     */
    public function index(): void
    {
        try {
            $status = (string)$this->input('status', 'aktif');
            $karyawanId = (string)$this->input('karyawan_id', '');

            $kasbonList = Database::fetchAll("
                SELECT
                    kb.id, kb.karyawan_id, kb.tanggal_pengajuan,
                    kb.total_pinjaman, kb.potongan_per_periode,
                    kb.sisa_pinjaman, kb.status_kasbon, kb.keterangan, kb.catatan,
                    v.nama_karyawan, v.nama_panggilan, v.posisi, v.tipe_penggajian,
                    (
                        SELECT COUNT(*) FROM public.potongan_kasbon pk WHERE pk.kasbon_id = kb.id
                    ) as jumlah_cicilan,
                    (
                        SELECT COALESCE(SUM(pk.nominal), 0) FROM public.potongan_kasbon pk WHERE pk.kasbon_id = kb.id
                    ) as total_terbayar
                FROM public.kasbon kb
                JOIN public.v_karyawan_info v ON v.id = kb.karyawan_id
                WHERE (:status = 'semua' OR kb.status_kasbon = :status)
                  AND (:kid = '' OR kb.karyawan_id = :kid_uuid)
                ORDER BY 
                    CASE WHEN kb.status_kasbon = 'aktif' THEN 0 WHEN kb.status_kasbon = 'lunas' THEN 1 ELSE 2 END,
                    kb.tanggal_pengajuan DESC
            ", [
                'status' => $status,
                'kid' => $karyawanId,
                'kid_uuid' => !empty($karyawanId) ? $karyawanId : null
            ]);

            // Master Karyawan Aktif untuk Dropdown Form
            $karyawanList = Database::fetchAll("
                SELECT v.id, v.nama_karyawan, v.posisi, v.tipe_penggajian,
                       COALESCE((
                           SELECT SUM(sisa_pinjaman) 
                           FROM public.kasbon 
                           WHERE karyawan_id = v.id AND status_kasbon = 'aktif'
                       ), 0) as total_kasbon_berjalan
                FROM public.v_karyawan_info v
                WHERE v.status_aktif = TRUE
                ORDER BY v.nama_karyawan ASC
            ");

            // KPI Metrics
            $stats = Database::fetchOne("
                SELECT
                    COUNT(CASE WHEN status_kasbon = 'aktif' THEN 1 END) as count_aktif,
                    COALESCE(SUM(CASE WHEN status_kasbon = 'aktif' THEN sisa_pinjaman ELSE 0 END), 0) as total_sisa_aktif,
                    COUNT(CASE WHEN status_kasbon = 'lunas' THEN 1 END) as count_lunas,
                    COALESCE(SUM(CASE WHEN status_kasbon = 'lunas' THEN total_pinjaman ELSE 0 END), 0) as total_nominal_lunas,
                    COALESCE(SUM(CASE WHEN DATE_TRUNC('month', tanggal_pengajuan) = DATE_TRUNC('month', CURRENT_DATE) THEN total_pinjaman ELSE 0 END), 0) as pinjaman_bulan_ini,
                    COALESCE((SELECT SUM(nominal) FROM public.potongan_kasbon), 0) as total_cicilan_terbayar
                FROM public.kasbon
            ");

            // Map Karyawan untuk Searchable Dropdown di Modal Form
            $karyawanMapData = [];
            foreach ($karyawanList as $k) {
                $kid = (string)$k['id'];
                $karyawanMapData[$kid] = [
                    'id' => $kid,
                    'nama' => (string)$k['nama_karyawan'],
                    'posisi' => (string)($k['posisi'] ?? 'Staff'),
                    'tipe_penggajian' => (string)($k['tipe_penggajian'] ?? 'bulanan'),
                    'total_kasbon_berjalan' => (float)$k['total_kasbon_berjalan'],
                    'initials' => $this->getInitials((string)$k['nama_karyawan'])
                ];
            }

            // Master Akun Kas Aktif (Non-Escrow) untuk Pilihan Sumber Dana Pencairan
            $akunKasList = Database::fetchAll("
                SELECT id, nama_akun, tipe_akun, saldo_saat_ini, is_escrow, is_default_pos
                FROM public.akun_kas
                WHERE status_aktif = TRUE AND is_escrow = FALSE
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            $this->view('kasbon.index', [
                'pageTitle' => 'Kasbon Karyawan',
                'pageSubtitle' => 'Pengelolaan Pinjaman & Pelacakan Cicilan Karyawan',
                'status' => $status,
                'karyawanId' => $karyawanId,
                'kasbonList' => $kasbonList,
                'karyawanList' => $karyawanList,
                'karyawanMapData' => $karyawanMapData,
                'akunKasList' => $akunKasList,
                'countAktif' => (int)($stats['count_aktif'] ?? 0),
                'totalSisaAktif' => (float)($stats['total_sisa_aktif'] ?? 0),
                'countLunas' => (int)($stats['count_lunas'] ?? 0),
                'totalNominalLunas' => (float)($stats['total_nominal_lunas'] ?? 0),
                'pinjamanBulanIni' => (float)($stats['pinjaman_bulan_ini'] ?? 0),
                'totalCicilanTerbayar' => (float)($stats['total_cicilan_terbayar'] ?? 0)
            ], 'layouts.master');
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat daftar kasbon: ' . $e->getMessage());
            $this->redirect('/dashboard');
        }
    }

    /**
     * POST Tambah Kasbon Baru
     */
    public function store(): void
    {
        Auth::requirePermission('hr.kasbon_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/kasbon');
            return;
        }

        $karyawanId = (string)$this->input('karyawan_id', '');
        $tanggalPengajuan = (string)$this->input('tanggal_pengajuan', date('Y-m-d'));
        $totalPinjaman = (float)preg_replace('/[^0-9]/', '', (string)$this->input('total_pinjaman', '0'));
        $potonganPerPeriode = (float)preg_replace('/[^0-9]/', '', (string)$this->input('potongan_per_periode', '0'));
        $keterangan = trim((string)$this->input('keterangan', 'Pinjaman kasbon'));
        $catatan = trim((string)$this->input('catatan', ''));
        $akunKasId = (string)$this->input('akun_kas_id', '');
        $bypassKas = !empty($this->input('bypass_kas', ''));

        if (empty($karyawanId)) {
            $this->flashError('Karyawan wajib dipilih.');
            $this->redirect('/kasbon');
            return;
        }

        if ($totalPinjaman <= 0) {
            $this->flashError('Nominal pinjaman kasbon harus lebih dari Rp 0.');
            $this->redirect('/kasbon');
            return;
        }

        if (!$bypassKas && empty($akunKasId)) {
            $this->flashError('Silakan pilih akun kas sumber pengeluaran dana pinjaman, atau centang opsi bypass jika hanya mencatat data lama.');
            $this->redirect('/kasbon');
            return;
        }

        if ($potonganPerPeriode < 0) {
            $potonganPerPeriode = 0.00;
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $karyawan = Database::fetchOne("
                SELECT id, nama_karyawan FROM public.v_karyawan_info WHERE id = :kid AND status_aktif = TRUE
            ", ['kid' => $karyawanId]);

            if (!$karyawan) {
                $pdo->rollBack();
                $this->flashError('Karyawan tidak ditemukan atau non-aktif.');
                $this->redirect('/kasbon');
                return;
            }

            $selectedKas = null;
            if (!$bypassKas) {
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
                    $this->redirect('/kasbon');
                    return;
                }

                if (!empty($selectedKas['is_escrow'])) {
                    $pdo->rollBack();
                    $this->flashError('Akun kas titipan escrow tabungan tidak boleh digunakan untuk pencairan pinjaman.');
                    $this->redirect('/kasbon');
                    return;
                }

                $saldoSaatIni = (float)$selectedKas['saldo_saat_ini'];
                if ($totalPinjaman > $saldoSaatIni) {
                    $pdo->rollBack();
                    $this->flashError("Saldo akun kas '{$selectedKas['nama_akun']}' (" . Format::rupiah($saldoSaatIni) . ") tidak mencukupi untuk pinjaman (" . Format::rupiah($totalPinjaman) . ").");
                    $this->redirect('/kasbon');
                    return;
                }
            }

            // 1. Simpan Data Kasbon
            $stmtInsert = $pdo->prepare("
                INSERT INTO public.kasbon (
                    karyawan_id, tanggal_pengajuan, total_pinjaman,
                    potongan_per_periode, sisa_pinjaman, status_kasbon,
                    akun_kas_id, keterangan, catatan, dibuat_pada, diubah_pada
                ) VALUES (
                    :kid, :tgl, :total,
                    :cicilan, :total, 'aktif',
                    :kas_id, :ket, :catatan, NOW(), NOW()
                ) RETURNING id
            ");
            $stmtInsert->execute([
                'kid' => $karyawanId,
                'tgl' => $tanggalPengajuan,
                'total' => $totalPinjaman,
                'cicilan' => $potonganPerPeriode,
                'kas_id' => !$bypassKas ? $akunKasId : null,
                'ket' => !empty($keterangan) ? $keterangan : 'Pinjaman kasbon',
                'catatan' => !empty($catatan) ? $catatan : null
            ]);
            $kasbonId = (string)$stmtInsert->fetchColumn();

            // 2. Potong Saldo Akun Kas & Catat Arus Kas Keluar (jika bukan bypass)
            if (!$bypassKas && $selectedKas) {
                $saldoBaru = (float)$selectedKas['saldo_saat_ini'] - $totalPinjaman;
                
                $pdo->prepare("
                    UPDATE public.akun_kas 
                    SET saldo_saat_ini = saldo_saat_ini - :total, diubah_pada = NOW() 
                    WHERE id = :id
                ")->execute([
                    'total' => $totalPinjaman,
                    'id' => $akunKasId
                ]);

                $userId = Auth::user()['id'] ?? null;
                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                        nominal, keterangan, referensi_tabel, referensi_id,
                        saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :kas_id, :tgl, 'keluar', 'pencairan_kasbon',
                        :nom, :ket, 'kasbon', :ref_id,
                        :saldo_berjalan, :uid, NOW()
                    )
                ")->execute([
                    'kas_id' => $akunKasId,
                    'tgl' => $tanggalPengajuan,
                    'nom' => $totalPinjaman,
                    'ket' => "Pencairan kasbon karyawan {$karyawan['nama_karyawan']}" . (!empty($keterangan) ? " ({$keterangan})" : ''),
                    'ref_id' => $kasbonId,
                    'saldo_berjalan' => $saldoBaru,
                    'uid' => $userId
                ]);
            }

            $pdo->commit();

            ActivityLog::log(
                'hr_payroll',
                'CREATE_KASBON',
                "Membuat kasbon baru untuk {$karyawan['nama_karyawan']} sebesar " . Format::rupiah($totalPinjaman) . ($bypassKas ? " (Bypass Kas)" : " via {$selectedKas['nama_akun']}") . ".",
                'kasbon',
                $kasbonId
            );

            $this->flashSuccess('Kasbon berhasil didaftarkan' . (!$bypassKas ? ' dan saldo kas berhasil dipotong.' : '.'));
            $this->redirect('/kasbon');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menambahkan kasbon: ' . $e->getMessage());
            $this->redirect('/kasbon');
        }
    }

    /**
     * GET Detail Kasbon & Riwayat Pembayaran Cicilan
     */
    public function detail(): void
    {
        $id = (string)$this->input('id', '');
        if (empty($id)) {
            $this->redirect('/kasbon');
            return;
        }

        try {
            $kasbon = Database::fetchOne("
                SELECT
                    kb.*,
                    v.nama_karyawan, v.nama_panggilan, v.posisi, v.tipe_penggajian
                FROM public.kasbon kb
                JOIN public.v_karyawan_info v ON v.id = kb.karyawan_id
                WHERE kb.id = :id
            ", ['id' => $id]);

            if (!$kasbon) {
                $this->flashError('Data kasbon tidak ditemukan.');
                $this->redirect('/kasbon');
                return;
            }

            $potonganList = Database::fetchAll("
                SELECT
                    pk.id, pk.tanggal, pk.nominal, pk.tipe_potongan, pk.keterangan, pk.dibuat_pada,
                    p.nomor_referensi as nomor_payroll, p.nama_payroll
                FROM public.potongan_kasbon pk
                LEFT JOIN public.rincian_penggajian rp ON rp.id = pk.rincian_penggajian_id
                LEFT JOIN public.penggajian p ON p.id = rp.penggajian_id
                WHERE pk.kasbon_id = :id
                ORDER BY pk.tanggal DESC, pk.dibuat_pada DESC
            ", ['id' => $id]);

            $totalTerbayar = array_sum(array_column($potonganList, 'nominal'));
            $persenLunas = ($kasbon['total_pinjaman'] > 0) 
                ? min(100, round(($totalTerbayar / (float)$kasbon['total_pinjaman']) * 100, 1)) 
                : 100;

            // Master Akun Kas Aktif (Non-Escrow) untuk Pilihan Penampung Pembayaran Manual
            $akunKasList = Database::fetchAll("
                SELECT id, nama_akun, tipe_akun, saldo_saat_ini, is_escrow, is_default_pos
                FROM public.akun_kas
                WHERE status_aktif = TRUE AND is_escrow = FALSE
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            $this->view('kasbon.detail', [
                'pageTitle' => 'Detail Kasbon',
                'pageSubtitle' => 'Rincian Pinjaman & Riwayat Mutasi Pelunasan',
                'kasbon' => $kasbon,
                'potonganList' => $potonganList,
                'totalTerbayar' => $totalTerbayar,
                'persenLunas' => $persenLunas,
                'akunKasList' => $akunKasList
            ], 'layouts.master');
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat rincian kasbon: ' . $e->getMessage());
            $this->redirect('/kasbon');
        }
    }

    /**
     * POST Pembayaran Cicilan / Pelunasan Manual Kasbon
     */
    public function bayar(): void
    {
        Auth::requirePermission('hr.kasbon_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/kasbon');
            return;
        }

        $id = (string)$this->input('kasbon_id', '');
        $nominal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('nominal', '0'));
        $tanggal = (string)$this->input('tanggal', date('Y-m-d'));
        $catatan = trim((string)$this->input('keterangan', 'Pembayaran cicilan manual'));
        $akunKasId = (string)$this->input('akun_kas_id', '');

        if (empty($id) || $nominal <= 0) {
            $this->flashError('Nominal pembayaran harus lebih dari Rp 0.');
            $this->redirect('/kasbon');
            return;
        }

        if (empty($akunKasId)) {
            $this->flashError('Akun kas penampung uang pembayaran cicilan wajib dipilih.');
            $this->redirect('/kasbon/detail?id=' . $id);
            return;
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $kasbon = Database::fetchOne("
                SELECT kb.id, kb.sisa_pinjaman, kb.status_kasbon, kb.total_pinjaman, kb.karyawan_id,
                       v.nama_karyawan
                FROM public.kasbon kb
                JOIN public.v_karyawan_info v ON v.id = kb.karyawan_id
                WHERE kb.id = :id FOR UPDATE
            ", ['id' => $id]);

            if (!$kasbon) {
                $pdo->rollBack();
                $this->flashError('Data kasbon tidak ditemukan.');
                $this->redirect('/kasbon');
                return;
            }

            if ($kasbon['status_kasbon'] !== 'aktif') {
                $pdo->rollBack();
                $this->flashError('Kasbon ini sudah tidak aktif (sudah lunas).');
                $this->redirect('/kasbon/detail?id=' . $id);
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
                $this->flashError('Akun kas penerima tidak valid atau non-aktif.');
                $this->redirect('/kasbon/detail?id=' . $id);
                return;
            }

            if (!empty($selectedKas['is_escrow'])) {
                $pdo->rollBack();
                $this->flashError('Akun kas titipan escrow tabungan tidak boleh digunakan untuk menerima pembayaran kasbon.');
                $this->redirect('/kasbon/detail?id=' . $id);
                return;
            }

            $sisaPinjaman = (float)$kasbon['sisa_pinjaman'];
            if ($nominal > $sisaPinjaman) {
                $nominal = $sisaPinjaman; // Batasi tidak melebihi sisa pinjaman
            }

            // 1. Insert ke potongan_kasbon
            $stmtPotongan = $pdo->prepare("
                INSERT INTO public.potongan_kasbon (
                    kasbon_id, tanggal, nominal, tipe_potongan, akun_kas_id, keterangan, dibuat_pada
                ) VALUES (
                    :id, :tgl, :nominal, 'manual', :kas_id, :ket, NOW()
                ) RETURNING id
            ");
            $stmtPotongan->execute([
                'id' => $id,
                'tgl' => $tanggal,
                'nominal' => $nominal,
                'kas_id' => $akunKasId,
                'ket' => !empty($catatan) ? $catatan : 'Pembayaran cicilan manual'
            ]);
            $potonganId = (string)$stmtPotongan->fetchColumn();

            // 2. Update sisa_pinjaman pada kasbon
            $sisaBaru = $sisaPinjaman - $nominal;
            $statusBaru = ($sisaBaru <= 0.001) ? 'lunas' : 'aktif';

            $pdo->prepare("
                UPDATE public.kasbon 
                SET sisa_pinjaman = :sisa, status_kasbon = :status, diubah_pada = NOW() 
                WHERE id = :id
            ")->execute([
                'sisa' => max(0, $sisaBaru),
                'status' => $statusBaru,
                'id' => $id
            ]);

            // 3. Tambah Saldo Akun Kas & Catat Arus Kas Masuk
            $saldoKasBaru = (float)$selectedKas['saldo_saat_ini'] + $nominal;
            $pdo->prepare("
                UPDATE public.akun_kas 
                SET saldo_saat_ini = saldo_saat_ini + :nom, diubah_pada = NOW() 
                WHERE id = :id
            ")->execute([
                'nom' => $nominal,
                'id' => $akunKasId
            ]);

            $userId = Auth::user()['id'] ?? null;
            $pdo->prepare("
                INSERT INTO public.arus_kas (
                    akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                    nominal, keterangan, referensi_tabel, referensi_id,
                    saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :kas_id, :tgl, 'masuk', 'pembayaran_kasbon',
                    :nom, :ket, 'potongan_kasbon', :ref_id,
                    :saldo_berjalan, :uid, NOW()
                )
            ")->execute([
                'kas_id' => $akunKasId,
                'tgl' => $tanggal,
                'nom' => $nominal,
                'ket' => "Pembayaran cicilan kasbon karyawan {$kasbon['nama_karyawan']}" . (!empty($catatan) ? " ({$catatan})" : ''),
                'ref_id' => $potonganId,
                'saldo_berjalan' => $saldoKasBaru,
                'uid' => $userId
            ]);

            $pdo->commit();

            ActivityLog::log(
                'hr_payroll',
                'BAYAR_KASBON',
                "Mencatat pembayaran manual kasbon ID {$id} sebesar " . Format::rupiah($nominal) . " via {$selectedKas['nama_akun']} (Sisa: " . Format::rupiah(max(0, $sisaBaru)) . ").",
                'kasbon',
                $id
            );

            $this->flashSuccess('Pembayaran kasbon berhasil dicatat dan masuk ke kas ' . $selectedKas['nama_akun'] . '.' . ($statusBaru === 'lunas' ? ' Kasbon telah LUNAS!' : ''));
            $this->redirect('/kasbon/detail?id=' . $id);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal mencatat pembayaran kasbon: ' . $e->getMessage());
            $this->redirect('/kasbon/detail?id=' . $id);
        }
    }

    /**
     * POST Hapus Kasbon (Hanya jika belum ada riwayat pembayaran cicilan)
     */
    public function delete(): void
    {
        Auth::requirePermission('hr.kasbon_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/kasbon');
            return;
        }

        $id = (string)$this->input('id', '');
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $kasbon = Database::fetchOne("
                SELECT id, total_pinjaman, sisa_pinjaman, status_kasbon, akun_kas_id 
                FROM public.kasbon 
                WHERE id = :id FOR UPDATE
            ", ['id' => $id]);

            if (!$kasbon) {
                $pdo->rollBack();
                $this->flashError('Data kasbon tidak ditemukan.');
                $this->redirect('/kasbon');
                return;
            }

            // Safety guard: Sisa pinjaman harus sama persis dengan total pinjaman (belum ada pembayaran)
            if ((float)$kasbon['sisa_pinjaman'] !== (float)$kasbon['total_pinjaman']) {
                $pdo->rollBack();
                $this->flashError('Kasbon yang sudah memiliki riwayat cicilan tidak dapat dihapus.');
                $this->redirect('/kasbon/detail?id=' . $id);
                return;
            }

            // Kembalikan saldo kas jika saat pencairan memotong kas riil
            if (!empty($kasbon['akun_kas_id'])) {
                $totalPinjaman = (float)$kasbon['total_pinjaman'];
                $pdo->prepare("
                    UPDATE public.akun_kas 
                    SET saldo_saat_ini = saldo_saat_ini + :total, diubah_pada = NOW() 
                    WHERE id = :kas_id
                ")->execute([
                    'total' => $totalPinjaman,
                    'kas_id' => $kasbon['akun_kas_id']
                ]);

                // Hapus catatan mutasi arus kas terkait
                $pdo->prepare("
                    DELETE FROM public.arus_kas 
                    WHERE referensi_tabel = 'kasbon' AND referensi_id = :id
                ")->execute(['id' => $id]);
            }

            $pdo->prepare("
                DELETE FROM public.kasbon 
                WHERE id = :id AND sisa_pinjaman = total_pinjaman
            ")->execute(['id' => $id]);

            $pdo->commit();

            ActivityLog::log('hr_payroll', 'DELETE_KASBON', "Menghapus catatan kasbon ID {$id}" . (!empty($kasbon['akun_kas_id']) ? " dan mengembalikan saldo kas." : "."), 'kasbon', $id);

            $this->flashSuccess('Catatan pinjaman kasbon berhasil dihapus' . (!empty($kasbon['akun_kas_id']) ? ' dan saldo kas dikembalikan.' : '.'));
            $this->redirect('/kasbon');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menghapus kasbon: ' . $e->getMessage());
            $this->redirect('/kasbon');
        }
    }

    /**
     * Alias method untuk kompatibilitas rute lama
     */
    public function cancel(): void
    {
        $this->delete();
    }

    /**
     * Helper untuk mengambil 1-2 inisial nama karyawan untuk avatar UI
     */
    private function getInitials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach ($words as $w) {
            if (!empty($w)) {
                $initials .= mb_strtoupper(mb_substr($w, 0, 1));
            }
            if (mb_strlen($initials) >= 2) break;
        }
        return $initials ?: 'KR';
    }
}

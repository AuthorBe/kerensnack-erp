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
 * Transformasi: Model Konsolidasi Per Karyawan & Buku Besar Log Aktivitas Multifungsi
 */
class KasbonController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission(['hr.kasbon_view', 'hr.kasbon_manage']);
    }

    /**
     * Halaman Utama: Daftar Konsolidasi Kasbon Per Karyawan (1 Baris Per Karyawan)
     */
    public function index(): void
    {
        try {
            $status = (string)$this->input('status', 'semua');
            $karyawanId = (string)$this->input('karyawan_id', '');

            // Query Agregasi Kasbon Konsolidasi Per Karyawan
            $kasbonList = Database::fetchAll("
                SELECT
                    k.id as karyawan_id,
                    v.nama_karyawan,
                    v.nama_panggilan,
                    v.posisi,
                    v.tipe_penggajian,
                    v.status_aktif as status_aktif_karyawan,
                    COUNT(kb.id) as frekuensi_pinjaman,
                    COUNT(CASE WHEN kb.status_kasbon = 'aktif' AND kb.sisa_pinjaman > 0 THEN 1 END) as jumlah_pinjaman_aktif,
                    COALESCE(SUM(kb.total_pinjaman), 0) as total_pinjaman_akumulasi,
                    COALESCE(SUM(kb.sisa_pinjaman), 0) as sisa_pinjaman_berjalan,
                    COALESCE(SUM(kb.total_pinjaman) - SUM(kb.sisa_pinjaman), 0) as total_terbayar,
                    MAX(kb.tanggal_pengajuan) as tanggal_pinjaman_terakhir
                FROM public.karyawan k
                JOIN public.v_karyawan_info v ON v.id = k.id
                JOIN public.kasbon kb ON kb.karyawan_id = k.id
                WHERE (:kid = '' OR k.id = :kid_uuid)
                GROUP BY k.id, v.nama_karyawan, v.nama_panggilan, v.posisi, v.tipe_penggajian, v.status_aktif
                HAVING (:status = 'semua')
                    OR (:status = 'aktif' AND SUM(kb.sisa_pinjaman) > 0)
                    OR (:status = 'lunas' AND SUM(kb.sisa_pinjaman) = 0)
                ORDER BY 
                    CASE WHEN SUM(kb.sisa_pinjaman) > 0 THEN 0 ELSE 1 END,
                    SUM(kb.sisa_pinjaman) DESC,
                    v.nama_karyawan ASC
            ", [
                'status' => $status,
                'kid' => $karyawanId,
                'kid_uuid' => !empty($karyawanId) ? $karyawanId : null
            ]);

            // Master Karyawan Aktif untuk Dropdown Form Tambah Kasbon
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

            // KPI Metrics Tingkat Perusahaan & Konsolidasi Karyawan
            $stats = Database::fetchOne("
                WITH emp_summary AS (
                    SELECT karyawan_id, SUM(sisa_pinjaman) as sisa_karyawan
                    FROM public.kasbon
                    GROUP BY karyawan_id
                )
                SELECT
                    COUNT(CASE WHEN sisa_karyawan > 0 THEN 1 END) as count_aktif,
                    COUNT(CASE WHEN sisa_karyawan = 0 THEN 1 END) as count_lunas,
                    COUNT(*) as count_semua,
                    (SELECT COALESCE(SUM(sisa_pinjaman), 0) FROM public.kasbon WHERE status_kasbon = 'aktif') as total_sisa_aktif,
                    (SELECT COALESCE(SUM(total_pinjaman), 0) FROM public.kasbon WHERE DATE_TRUNC('month', tanggal_pengajuan) = DATE_TRUNC('month', CURRENT_DATE)) as pinjaman_bulan_ini,
                    (SELECT COALESCE(SUM(nominal), 0) FROM public.potongan_kasbon) as total_cicilan_terbayar
                FROM emp_summary
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
                'pageSubtitle' => 'Pengelolaan Pinjaman & Buku Besar Kasbon Konsolidasi Per Karyawan',
                'status' => $status,
                'karyawanId' => $karyawanId,
                'kasbonList' => $kasbonList,
                'karyawanList' => $karyawanList,
                'karyawanMapData' => $karyawanMapData,
                'akunKasList' => $akunKasList,
                'countAktif' => (int)($stats['count_aktif'] ?? 0),
                'totalSisaAktif' => (float)($stats['total_sisa_aktif'] ?? 0),
                'countLunas' => (int)($stats['count_lunas'] ?? 0),
                'countSemua' => (int)($stats['count_semua'] ?? 0),
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

        if (empty($akunKasId)) {
            $this->flashError('Akun kas sumber pengeluaran dana pinjaman wajib dipilih.');
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
                'kas_id' => $akunKasId,
                'ket' => !empty($keterangan) ? $keterangan : 'Pinjaman kasbon',
                'catatan' => !empty($catatan) ? $catatan : null
            ]);
            $kasbonId = (string)$stmtInsert->fetchColumn();

            // 2. Potong Saldo Akun Kas & Catat Arus Kas Keluar
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

            $pdo->commit();

            ActivityLog::log(
                'hr_payroll',
                'CREATE_KASBON',
                "Membuat kasbon baru untuk {$karyawan['nama_karyawan']} sebesar " . Format::rupiah($totalPinjaman) . " via {$selectedKas['nama_akun']}.",
                'kasbon',
                $kasbonId
            );

            $this->flashSuccess('Kasbon berhasil didaftarkan dan saldo kas berhasil dipotong.');
            $this->redirect('/kasbon/detail?karyawan_id=' . $karyawanId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menambahkan kasbon: ' . $e->getMessage());
            $this->redirect('/kasbon');
        }
    }

    /**
     * GET Detail Buku Besar Kasbon Konsolidasi Per Karyawan
     * Menampilkan profil karyawan, KPI konsolidasi, dan Tabel Log Aktivitas Multifungsi Kronologis
     */
    public function detail(): void
    {
        $karyawanId = (string)$this->input('karyawan_id', '');

        // Backward compatibility: jika param 'id' kasbon lama diberikan
        if (empty($karyawanId)) {
            $id = (string)$this->input('id', '');
            if (!empty($id)) {
                $kasbonRow = Database::fetchOne("SELECT karyawan_id FROM public.kasbon WHERE id = :id", ['id' => $id]);
                if ($kasbonRow) {
                    $karyawanId = (string)$kasbonRow['karyawan_id'];
                } else {
                    $karyawanRow = Database::fetchOne("SELECT id FROM public.karyawan WHERE id = :id", ['id' => $id]);
                    if ($karyawanRow) {
                        $karyawanId = (string)$karyawanRow['id'];
                    }
                }
            }
        }

        if (empty($karyawanId)) {
            $this->flashError('ID Karyawan wajib ditentukan untuk membuka buku kasbon.');
            $this->redirect('/kasbon');
            return;
        }

        try {
            // Profil Karyawan
            $karyawan = Database::fetchOne("
                SELECT * FROM public.v_karyawan_info WHERE id = :kid
            ", ['kid' => $karyawanId]);

            if (!$karyawan) {
                $this->flashError('Data karyawan tidak ditemukan.');
                $this->redirect('/kasbon');
                return;
            }

            // Ringkasan Agregasi Kasbon Karyawan
            $summary = Database::fetchOne("
                SELECT
                    COUNT(kb.id) as frekuensi_pinjaman,
                    COUNT(CASE WHEN kb.status_kasbon = 'aktif' AND kb.sisa_pinjaman > 0 THEN 1 END) as jumlah_pinjaman_aktif,
                    COALESCE(SUM(kb.total_pinjaman), 0) as total_pinjaman_akumulasi,
                    COALESCE(SUM(kb.sisa_pinjaman), 0) as sisa_pinjaman_berjalan,
                    COALESCE(SUM(kb.total_pinjaman) - SUM(kb.sisa_pinjaman), 0) as total_terbayar
                FROM public.kasbon kb
                WHERE kb.karyawan_id = :kid
            ", ['kid' => $karyawanId]);

            $totalPinjaman = (float)($summary['total_pinjaman_akumulasi'] ?? 0);
            $sisaPinjaman = (float)($summary['sisa_pinjaman_berjalan'] ?? 0);
            $totalTerbayar = max(0.0, $totalPinjaman - $sisaPinjaman);
            $frekuensiPinjaman = (int)($summary['frekuensi_pinjaman'] ?? 0);
            $jumlahPinjamanAktif = (int)($summary['jumlah_pinjaman_aktif'] ?? 0);

            $persenLunas = ($totalPinjaman > 0)
                ? min(100, round(($totalTerbayar / $totalPinjaman) * 100, 1))
                : 100;

            // 1. Ambil Semua Mutasi Pencairan Pinjaman (+)
            $pinjamanRows = Database::fetchAll("
                SELECT
                    kb.id,
                    kb.tanggal_pengajuan as tanggal,
                    kb.dibuat_pada,
                    'pinjaman' as jenis_aktivitas,
                    'Pencairan Pinjaman' as label_aktivitas,
                    kb.total_pinjaman as nominal_pinjaman,
                    0.00 as nominal_bayar,
                    kb.sisa_pinjaman,
                    kb.status_kasbon,
                    kb.keterangan,
                    kb.catatan,
                    ak.nama_akun as nama_akun_kas,
                    NULL as nomor_payroll,
                    NULL as nama_payroll
                FROM public.kasbon kb
                LEFT JOIN public.akun_kas ak ON ak.id = kb.akun_kas_id
                WHERE kb.karyawan_id = :kid
            ", ['kid' => $karyawanId]);

            // 2. Ambil Semua Mutasi Pembayaran / Cicilan (-)
            $bayarRows = Database::fetchAll("
                SELECT
                    pk.id,
                    pk.tanggal,
                    pk.dibuat_pada,
                    CASE WHEN pk.tipe_potongan = 'payroll' THEN 'potongan_payroll' ELSE 'bayar_manual' END as jenis_aktivitas,
                    CASE WHEN pk.tipe_potongan = 'payroll' THEN 'Potongan Payroll' ELSE 'Bayar Manual' END as label_aktivitas,
                    0.00 as nominal_pinjaman,
                    pk.nominal as nominal_bayar,
                    0.00 as sisa_pinjaman,
                    'terbayar' as status_kasbon,
                    pk.keterangan,
                    NULL as catatan,
                    ak.nama_akun as nama_akun_kas,
                    p.nomor_referensi as nomor_payroll,
                    p.nama_payroll
                FROM public.potongan_kasbon pk
                JOIN public.kasbon kb ON pk.kasbon_id = kb.id
                LEFT JOIN public.akun_kas ak ON ak.id = pk.akun_kas_id
                LEFT JOIN public.rincian_penggajian rp ON rp.id = pk.rincian_penggajian_id
                LEFT JOIN public.penggajian p ON p.id = rp.penggajian_id
                WHERE kb.karyawan_id = :kid
            ", ['kid' => $karyawanId]);

            // 3. Gabungkan dan Urutkan Kronologis ASC untuk Perhitungan Saldo Berjalan (Running Balance)
            $allActivities = array_merge($pinjamanRows, $bayarRows);
            usort($allActivities, function($a, $b) {
                $cmp = strcmp($a['tanggal'], $b['tanggal']);
                if ($cmp !== 0) return $cmp;
                // Jika tanggal sama, pencairan pinjaman dicatat lebih dulu agar saldo bertambah sebelum dipotong
                if ($a['jenis_aktivitas'] === 'pinjaman' && $b['jenis_aktivitas'] !== 'pinjaman') return -1;
                if ($a['jenis_aktivitas'] !== 'pinjaman' && $b['jenis_aktivitas'] === 'pinjaman') return 1;
                return strcmp($a['dibuat_pada'], $b['dibuat_pada']);
            });

            $runningBalance = 0.0;
            $countPayroll = 0;
            $countManual = 0;
            $countPinjaman = count($pinjamanRows);

            foreach ($allActivities as &$act) {
                if ($act['jenis_aktivitas'] === 'pinjaman') {
                    $runningBalance += (float)$act['nominal_pinjaman'];
                } else {
                    $runningBalance -= (float)$act['nominal_bayar'];
                    if ($act['jenis_aktivitas'] === 'potongan_payroll') {
                        $countPayroll++;
                    } else {
                        $countManual++;
                    }
                }
                $act['saldo_berjalan'] = max(0.0, $runningBalance);
            }
            unset($act);

            // Balik urutan menjadi DESC untuk tampilan tabel buku besar (transaksi terbaru di atas)
            $activitiesDesc = array_reverse($allActivities);

            // Ambil daftar kasbon aktif untuk referensi jika ada pembayaran manual
            $activeLoans = Database::fetchAll("
                SELECT kb.*, ak.nama_akun as nama_akun_kas
                FROM public.kasbon kb
                LEFT JOIN public.akun_kas ak ON ak.id = kb.akun_kas_id
                WHERE kb.karyawan_id = :kid AND kb.status_kasbon = 'aktif' AND kb.sisa_pinjaman > 0
                ORDER BY kb.tanggal_pengajuan ASC, kb.dibuat_pada ASC
            ", ['kid' => $karyawanId]);

            // Master Akun Kas Aktif (Non-Escrow) untuk Modal
            $akunKasList = Database::fetchAll("
                SELECT id, nama_akun, tipe_akun, saldo_saat_ini, is_escrow, is_default_pos
                FROM public.akun_kas
                WHERE status_aktif = TRUE AND is_escrow = FALSE
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            $this->view('kasbon.detail', [
                'pageTitle' => 'Buku Kasbon Karyawan',
                'pageSubtitle' => 'Rekening Koran & Log Mutasi Pinjaman Konsolidasi',
                'karyawan' => $karyawan,
                'summary' => $summary,
                'totalPinjaman' => $totalPinjaman,
                'totalTerbayar' => $totalTerbayar,
                'sisaPinjaman' => $sisaPinjaman,
                'frekuensiPinjaman' => $frekuensiPinjaman,
                'jumlahPinjamanAktif' => $jumlahPinjamanAktif,
                'persenLunas' => $persenLunas,
                'activities' => $activitiesDesc,
                'activeLoans' => $activeLoans,
                'countAll' => count($allActivities),
                'countPinjaman' => $countPinjaman,
                'countPayroll' => $countPayroll,
                'countManual' => $countManual,
                'akunKasList' => $akunKasList
            ], 'layouts.master');
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat rincian buku kasbon: ' . $e->getMessage());
            $this->redirect('/kasbon');
        }
    }

    /**
     * POST Pembayaran Cicilan / Pelunasan Manual Kasbon (Berbasis FIFO Per Karyawan)
     */
    public function bayar(): void
    {
        Auth::requirePermission('hr.kasbon_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/kasbon');
            return;
        }

        $karyawanId = (string)$this->input('karyawan_id', '');
        $kasbonId = (string)$this->input('kasbon_id', '');
        $nominal = (float)preg_replace('/[^0-9]/', '', (string)$this->input('nominal', '0'));
        $tanggal = (string)$this->input('tanggal', date('Y-m-d'));
        $catatan = trim((string)$this->input('keterangan', 'Pembayaran cicilan manual'));
        $akunKasId = (string)$this->input('akun_kas_id', '');

        // Fallback jika dikirim kasbon_id lama
        if (empty($karyawanId) && !empty($kasbonId)) {
            $row = Database::fetchOne("SELECT karyawan_id FROM public.kasbon WHERE id = :id", ['id' => $kasbonId]);
            if ($row) {
                $karyawanId = (string)$row['karyawan_id'];
            }
        }

        if (empty($karyawanId)) {
            $this->flashError('Karyawan wajib ditentukan untuk pembayaran kasbon.');
            $this->redirect('/kasbon');
            return;
        }

        if ($nominal <= 0) {
            $this->flashError('Nominal pembayaran harus lebih dari Rp 0.');
            $this->redirect('/kasbon/detail?karyawan_id=' . $karyawanId);
            return;
        }

        if (empty($akunKasId)) {
            $this->flashError('Akun kas penampung uang pembayaran cicilan wajib dipilih.');
            $this->redirect('/kasbon/detail?karyawan_id=' . $karyawanId);
            return;
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            // Kunci semua kasbon aktif milik karyawan dalam urutan FIFO (pengajuan terlama duluan)
            $stmtLoans = $pdo->prepare("
                SELECT kb.id, kb.sisa_pinjaman, kb.status_kasbon, kb.total_pinjaman, kb.karyawan_id,
                       v.nama_karyawan
                FROM public.kasbon kb
                JOIN public.v_karyawan_info v ON v.id = kb.karyawan_id
                WHERE kb.karyawan_id = :kid AND kb.status_kasbon = 'aktif' AND kb.sisa_pinjaman > 0
                ORDER BY kb.tanggal_pengajuan ASC, kb.dibuat_pada ASC
                FOR UPDATE
            ");
            $stmtLoans->execute(['kid' => $karyawanId]);
            $activeLoans = $stmtLoans->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($activeLoans)) {
                $pdo->rollBack();
                $this->flashError('Karyawan ini tidak memiliki pinjaman kasbon aktif yang perlu dibayar.');
                $this->redirect('/kasbon/detail?karyawan_id=' . $karyawanId);
                return;
            }

            $karyawanNama = $activeLoans[0]['nama_karyawan'];

            // Validasi & Kunci Akun Kas
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
                $this->redirect('/kasbon/detail?karyawan_id=' . $karyawanId);
                return;
            }

            if (!empty($selectedKas['is_escrow'])) {
                $pdo->rollBack();
                $this->flashError('Akun kas titipan escrow tabungan tidak boleh digunakan untuk menerima pembayaran kasbon.');
                $this->redirect('/kasbon/detail?karyawan_id=' . $karyawanId);
                return;
            }

            // Batasi nominal agar tidak melebihi total seluruh sisa pinjaman aktif karyawan
            $totalSisaSemua = 0.0;
            foreach ($activeLoans as $al) {
                $totalSisaSemua += (float)$al['sisa_pinjaman'];
            }

            if ($nominal > $totalSisaSemua) {
                $nominal = $totalSisaSemua;
            }

            // Alokasikan pembayaran menggunakan metode FIFO (First-In First-Out)
            $sisaBayar = $nominal;
            $firstPotonganId = null;

            foreach ($activeLoans as $loan) {
                if ($sisaBayar <= 0.001) {
                    break;
                }

                $sisaPinjamanLoan = (float)$loan['sisa_pinjaman'];
                $alokasi = min($sisaBayar, $sisaPinjamanLoan);

                // 1. Insert ke potongan_kasbon
                $stmtPotongan = $pdo->prepare("
                    INSERT INTO public.potongan_kasbon (
                        kasbon_id, tanggal, nominal, tipe_potongan, akun_kas_id, keterangan, dibuat_pada
                    ) VALUES (
                        :id, :tgl, :nominal, 'manual', :kas_id, :ket, NOW()
                    ) RETURNING id
                ");
                $stmtPotongan->execute([
                    'id' => $loan['id'],
                    'tgl' => $tanggal,
                    'nominal' => $alokasi,
                    'kas_id' => $akunKasId,
                    'ket' => !empty($catatan) ? $catatan : 'Pembayaran cicilan manual'
                ]);
                $potonganId = (string)$stmtPotongan->fetchColumn();
                if (!$firstPotonganId) {
                    $firstPotonganId = $potonganId;
                }

                // 2. Database trigger trg_potongan_kasbon_update_saldo secara otomatis
                // mengupdate sisa_pinjaman dan status_kasbon ('lunas' / 'aktif') pada public.kasbon.
                $sisaBayar -= $alokasi;
            }

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

            $isMulti = count($activeLoans) > 1 && $sisaBayar < $nominal;
            $ketMulti = $isMulti ? ' (Alokasi FIFO multi-pinjaman)' : '';

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
                'ket' => "Pembayaran cicilan kasbon karyawan {$karyawanNama}" . (!empty($catatan) ? " ({$catatan})" : '') . $ketMulti,
                'ref_id' => $firstPotonganId,
                'saldo_berjalan' => $saldoKasBaru,
                'uid' => $userId
            ]);

            $pdo->commit();

            ActivityLog::log(
                'hr_payroll',
                'BAYAR_KASBON',
                "Mencatat pembayaran manual kasbon FIFO untuk {$karyawanNama} sebesar " . Format::rupiah($nominal) . " via {$selectedKas['nama_akun']}.",
                'karyawan',
                $karyawanId
            );

            $sisaSeluruhnya = max(0, $totalSisaSemua - $nominal);
            $pesanLunas = ($sisaSeluruhnya <= 0.001) ? ' Seluruh kasbon karyawan telah LUNAS!' : ' Sisa kasbon: ' . Format::rupiah($sisaSeluruhnya);

            $this->flashSuccess('Pembayaran kasbon berhasil dicatat dan masuk ke kas ' . $selectedKas['nama_akun'] . '.' . $pesanLunas);
            $this->redirect('/kasbon/detail?karyawan_id=' . $karyawanId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal mencatat pembayaran kasbon: ' . $e->getMessage());
            $this->redirect('/kasbon/detail?karyawan_id=' . $karyawanId);
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
                SELECT id, karyawan_id, total_pinjaman, sisa_pinjaman, status_kasbon, akun_kas_id 
                FROM public.kasbon 
                WHERE id = :id FOR UPDATE
            ", ['id' => $id]);

            if (!$kasbon) {
                $pdo->rollBack();
                $this->flashError('Data kasbon tidak ditemukan.');
                $this->redirect('/kasbon');
                return;
            }

            $karyawanId = (string)$kasbon['karyawan_id'];

            // Safety guard: Sisa pinjaman harus sama persis dengan total pinjaman (belum ada pembayaran)
            if ((float)$kasbon['sisa_pinjaman'] !== (float)$kasbon['total_pinjaman']) {
                $pdo->rollBack();
                $this->flashError('Kasbon yang sudah memiliki riwayat cicilan tidak dapat dihapus.');
                $this->redirect('/kasbon/detail?karyawan_id=' . $karyawanId);
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
            $this->redirect('/kasbon/detail?karyawan_id=' . $karyawanId);
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

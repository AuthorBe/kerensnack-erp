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
 * app/Controllers/TabunganController.php
 * Pengendali Modul Tabungan / Simpanan Karyawan Keren One ERP
 */
class TabunganController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission(['hr.tabungan_view', 'hr.tabungan_manage']);
    }

    /**
     * Halaman Utama Daftar Saldo Tabungan Semua Karyawan
     */
    public function index(): void
    {
        try {
            // Lazy initialization: pastikan seluruh karyawan aktif memiliki akun tabungan
            Database::execute("
                INSERT INTO public.tabungan (karyawan_id, saldo, dibuat_pada, diubah_pada)
                SELECT k.id, 0.00, NOW(), NOW()
                FROM public.karyawan k
                JOIN public.pengguna p ON p.id = k.pengguna_id
                WHERE p.status_aktif = TRUE
                ON CONFLICT (karyawan_id) DO NOTHING
            ");

            $tabunganList = Database::fetchAll("
                SELECT
                    t.id as tabungan_id,
                    t.karyawan_id,
                    t.saldo,
                    v.nama_karyawan, v.nama_panggilan, v.posisi,
                    v.tipe_penggajian,
                    (
                        SELECT COUNT(*) FROM public.transaksi_tabungan tt WHERE tt.karyawan_id = t.karyawan_id
                    ) as jumlah_transaksi,
                    (
                        SELECT MAX(tt.tanggal) FROM public.transaksi_tabungan tt WHERE tt.karyawan_id = t.karyawan_id
                    ) as terakhir_transaksi
                FROM public.tabungan t
                JOIN public.v_karyawan_info v ON v.id = t.karyawan_id
                WHERE v.status_aktif = TRUE
                ORDER BY t.saldo DESC, v.nama_karyawan ASC
            ");

            // Karyawan List untuk Form Setor / Tarik
            $karyawanList = Database::fetchAll("
                SELECT v.id, v.nama_karyawan, v.posisi, v.tipe_penggajian, COALESCE(t.saldo, 0) as saldo_tabungan
                FROM public.v_karyawan_info v
                LEFT JOIN public.tabungan t ON t.karyawan_id = v.id
                WHERE v.status_aktif = TRUE
                ORDER BY v.nama_karyawan ASC
            ");

            // Map Karyawan untuk Searchable Dropdown di Modal
            $karyawanMapData = [];
            foreach ($karyawanList as $k) {
                $kid = (string)$k['id'];
                $karyawanMapData[$kid] = [
                    'id' => $kid,
                    'nama' => (string)$k['nama_karyawan'],
                    'posisi' => (string)($k['posisi'] ?? 'Staff'),
                    'tipe_penggajian' => (string)($k['tipe_penggajian'] ?? 'bulanan'),
                    'saldo' => (float)$k['saldo_tabungan'],
                    'initials' => $this->getInitials((string)$k['nama_karyawan'])
                ];
            }

            // Summary metrics
            $totalSaldo = array_sum(array_column($tabunganList, 'saldo'));
            $karyawanMenabung = count(array_filter($tabunganList, fn($t) => (float)$t['saldo'] > 0));
            $totalKaryawan = count($tabunganList);

            $monthlyStats = Database::fetchOne("
                SELECT
                    COALESCE(SUM(CASE WHEN tipe = 'deposit' THEN jumlah ELSE 0 END), 0) as deposit_bulan_ini,
                    COALESCE(SUM(CASE WHEN tipe = 'withdrawal' THEN jumlah ELSE 0 END), 0) as withdrawal_bulan_ini
                FROM public.transaksi_tabungan
                WHERE DATE_TRUNC('month', tanggal) = DATE_TRUNC('month', CURRENT_DATE)
            ");

            // Master Akun Kas Aktif (Termasuk Escrow Tabungan sebagai Akun Utama Simpanan)
            $akunKasList = Database::fetchAll("
                SELECT id, nama_akun, tipe_akun, saldo_saat_ini, is_escrow, is_default_pos
                FROM public.akun_kas
                WHERE status_aktif = TRUE
                ORDER BY is_escrow DESC, is_default_pos DESC, nama_akun ASC
            ");

            $this->view('tabungan.index', [
                'pageTitle' => 'Tabungan Karyawan',
                'pageSubtitle' => 'Kelola Saldo Simpanan, Setoran & Penarikan Tabungan',
                'tabunganList' => $tabunganList,
                'karyawanList' => $karyawanList,
                'karyawanMapData' => $karyawanMapData,
                'akunKasList' => $akunKasList,
                'totalSaldo' => (float)$totalSaldo,
                'karyawanMenabung' => $karyawanMenabung,
                'totalKaryawan' => $totalKaryawan,
                'depositBulanIni' => (float)($monthlyStats['deposit_bulan_ini'] ?? 0),
                'withdrawalBulanIni' => (float)($monthlyStats['withdrawal_bulan_ini'] ?? 0)
            ], 'layouts.master');
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat daftar tabungan: ' . $e->getMessage());
            $this->redirect('/dashboard');
        }
    }

    /**
     * GET Detail Tabungan & Buku Besar Mutasi Transaksi Karyawan
     */
    public function detail(): void
    {
        $karyawanId = (string)$this->input('karyawan_id', '');
        if (empty($karyawanId)) {
            $this->redirect('/tabungan');
            return;
        }

        try {
            $karyawan = Database::fetchOne("
                SELECT v.*, COALESCE(t.saldo, 0) as saldo, t.id as tabungan_id
                FROM public.v_karyawan_info v
                LEFT JOIN public.tabungan t ON t.karyawan_id = v.id
                WHERE v.id = :kid
            ", ['kid' => $karyawanId]);

            if (!$karyawan) {
                $this->flashError('Data karyawan tidak ditemukan.');
                $this->redirect('/tabungan');
                return;
            }

            $transaksiList = Database::fetchAll("
                SELECT
                    tt.id, tt.tanggal, tt.tipe, tt.jumlah, tt.sumber, tt.keterangan, tt.dibuat_pada,
                    p.nomor_referensi as nomor_payroll, p.nama_payroll
                FROM public.transaksi_tabungan tt
                LEFT JOIN public.rincian_penggajian rp ON rp.id = tt.rincian_penggajian_id
                LEFT JOIN public.penggajian p ON p.id = rp.penggajian_id
                WHERE tt.karyawan_id = :kid
                ORDER BY tt.tanggal DESC, tt.dibuat_pada DESC
            ", ['kid' => $karyawanId]);

            $totalDeposit = 0.00;
            $totalWithdrawal = 0.00;

            foreach ($transaksiList as $t) {
                if ($t['tipe'] === 'deposit') {
                    $totalDeposit += (float)$t['jumlah'];
                } else {
                    $totalWithdrawal += (float)$t['jumlah'];
                }
            }

            // Master Akun Kas Aktif
            $akunKasList = Database::fetchAll("
                SELECT id, nama_akun, tipe_akun, saldo_saat_ini, is_escrow, is_default_pos
                FROM public.akun_kas
                WHERE status_aktif = TRUE
                ORDER BY is_escrow DESC, is_default_pos DESC, nama_akun ASC
            ");

            $this->view('tabungan.detail', [
                'pageTitle' => 'Buku Tabungan',
                'pageSubtitle' => 'Riwayat Mutasi Setor & Tarik Simpanan Karyawan',
                'karyawan' => $karyawan,
                'transaksiList' => $transaksiList,
                'akunKasList' => $akunKasList,
                'totalDeposit' => $totalDeposit,
                'totalWithdrawal' => $totalWithdrawal
            ], 'layouts.master');
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat buku tabungan: ' . $e->getMessage());
            $this->redirect('/tabungan');
        }
    }

    /**
     * POST Setor Tabungan Manual
     */
    public function setor(): void
    {
        Auth::requirePermission('hr.tabungan_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/tabungan');
            return;
        }

        $karyawanId = (string)$this->input('karyawan_id', '');
        $tanggal = (string)$this->input('tanggal', date('Y-m-d'));
        $jumlah = (float)preg_replace('/[^0-9]/', '', (string)$this->input('jumlah', '0'));
        $keterangan = trim((string)$this->input('keterangan', 'Setoran manual'));
        $akunKasId = (string)$this->input('akun_kas_id', '');

        if (empty($karyawanId) || $jumlah <= 0) {
            $this->flashError('Karyawan dan nominal setoran wajib diisi.');
            $this->redirect('/tabungan');
            return;
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            // Default ke Kas Tabungan Escrow jika tidak dipilih
            if (empty($akunKasId)) {
                $stmtEscrow = $pdo->query("SELECT id FROM public.akun_kas WHERE is_escrow = TRUE AND status_aktif = TRUE LIMIT 1");
                $akunKasId = (string)($stmtEscrow->fetchColumn() ?: '');
            }

            // Validasi & Lock Akun Kas
            $selectedKas = null;
            if (!empty($akunKasId)) {
                $stmtKas = $pdo->prepare("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE id = :id AND status_aktif = TRUE FOR UPDATE");
                $stmtKas->execute(['id' => $akunKasId]);
                $selectedKas = $stmtKas->fetch(\PDO::FETCH_ASSOC);
            }

            // Ambil / buat akun tabungan
            $tabungan = Database::fetchOne("
                SELECT id, saldo FROM public.tabungan WHERE karyawan_id = :kid FOR UPDATE
            ", ['kid' => $karyawanId]);

            if (!$tabungan) {
                $stmt = $pdo->prepare("
                    INSERT INTO public.tabungan (karyawan_id, saldo, dibuat_pada, diubah_pada)
                    VALUES (:kid, 0, NOW(), NOW()) RETURNING id, saldo
                ");
                $stmt->execute(['kid' => $karyawanId]);
                $tabungan = $stmt->fetch();
            }

            $tid = $tabungan['id'];

            // 1. Insert transaksi_tabungan
            $stmtInsert = $pdo->prepare("
                INSERT INTO public.transaksi_tabungan (
                    tabungan_id, karyawan_id, tanggal, tipe, jumlah, sumber, akun_kas_id, keterangan, dibuat_pada
                ) VALUES (
                    :tid, :kid, :tgl, 'deposit', :jumlah, 'manual', :kas_id, :ket, NOW()
                ) RETURNING id
            ");
            $stmtInsert->execute([
                'tid' => $tid,
                'kid' => $karyawanId,
                'tgl' => $tanggal,
                'jumlah' => $jumlah,
                'kas_id' => !empty($akunKasId) ? $akunKasId : null,
                'ket' => !empty($keterangan) ? $keterangan : 'Setoran manual'
            ]);
            $ttId = (string)$stmtInsert->fetchColumn();

            // 2. Tambah Saldo Akun Kas & Catat Arus Kas Masuk (jika ada akun kas)
            if ($selectedKas) {
                $saldoKasBaru = (float)$selectedKas['saldo_saat_ini'] + $jumlah;
                $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + :nom, diubah_pada = NOW() WHERE id = :id")->execute(['nom' => $jumlah, 'id' => $akunKasId]);

                $userId = Auth::user()['id'] ?? null;
                $karyawan = Database::fetchOne("SELECT nama_karyawan FROM public.v_karyawan_info WHERE id = :kid", ['kid' => $karyawanId]);

                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                        nominal, keterangan, referensi_tabel, referensi_id,
                        saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :kas_id, :tgl, 'masuk', 'setoran_tabungan',
                        :nom, :ket, 'transaksi_tabungan', :ref_id,
                        :saldo_berjalan, :uid, NOW()
                    )
                ")->execute([
                    'kas_id' => $akunKasId,
                    'tgl' => $tanggal,
                    'nom' => $jumlah,
                    'ket' => "Setoran tabungan {$karyawan['nama_karyawan']}" . (!empty($keterangan) ? " ({$keterangan})" : ''),
                    'ref_id' => $ttId,
                    'saldo_berjalan' => $saldoKasBaru,
                    'uid' => $userId
                ]);
            }

            $pdo->commit();

            $karyawan = Database::fetchOne("SELECT nama_karyawan FROM public.v_karyawan_info WHERE id = :kid", ['kid' => $karyawanId]);

            ActivityLog::log(
                'hr_payroll',
                'SETOR_TABUNGAN',
                "Mencatat setoran tabungan untuk {$karyawan['nama_karyawan']} sebesar " . Format::rupiah($jumlah) . ($selectedKas ? " ke {$selectedKas['nama_akun']}." : "."),
                'tabungan',
                $tid
            );

            $this->flashSuccess('Setoran tabungan berhasil dicatat.');
            $this->redirect('/tabungan/detail?karyawan_id=' . $karyawanId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal mencatat setoran tabungan: ' . $e->getMessage());
            $this->redirect('/tabungan');
        }
    }

    /**
     * POST Penarikan Tabungan Manual
     */
    public function tarik(): void
    {
        Auth::requirePermission('hr.tabungan_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/tabungan');
            return;
        }

        $karyawanId = (string)$this->input('karyawan_id', '');
        $tanggal = (string)$this->input('tanggal', date('Y-m-d'));
        $jumlah = (float)preg_replace('/[^0-9]/', '', (string)$this->input('jumlah', '0'));
        $keterangan = trim((string)$this->input('keterangan', 'Penarikan simpanan manual'));
        $akunKasId = (string)$this->input('akun_kas_id', '');

        if (empty($karyawanId) || $jumlah <= 0) {
            $this->flashError('Karyawan dan nominal penarikan wajib diisi.');
            $this->redirect('/tabungan');
            return;
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $tabungan = Database::fetchOne("
                SELECT id, saldo FROM public.tabungan WHERE karyawan_id = :kid FOR UPDATE
            ", ['kid' => $karyawanId]);

            if (!$tabungan) {
                $pdo->rollBack();
                $this->flashError('Akun tabungan karyawan tidak ditemukan.');
                $this->redirect('/tabungan');
                return;
            }

            $currentSaldo = (float)$tabungan['saldo'];
            if ($jumlah > $currentSaldo) {
                $pdo->rollBack();
                $this->flashError('Saldo tabungan tidak mencukupi (Saldo saat ini: ' . Format::rupiah($currentSaldo) . ').');
                $this->redirect('/tabungan/detail?karyawan_id=' . $karyawanId);
                return;
            }

            // Default ke Kas Tabungan Escrow jika tidak dipilih
            if (empty($akunKasId)) {
                $stmtEscrow = $pdo->query("SELECT id FROM public.akun_kas WHERE is_escrow = TRUE AND status_aktif = TRUE LIMIT 1");
                $akunKasId = (string)($stmtEscrow->fetchColumn() ?: '');
            }

            // Validasi & Lock Akun Kas
            $selectedKas = null;
            if (!empty($akunKasId)) {
                $stmtKas = $pdo->prepare("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE id = :id AND status_aktif = TRUE FOR UPDATE");
                $stmtKas->execute(['id' => $akunKasId]);
                $selectedKas = $stmtKas->fetch(\PDO::FETCH_ASSOC);

                if (!$selectedKas) {
                    $pdo->rollBack();
                    $this->flashError('Akun kas sumber penarikan tabungan tidak valid.');
                    $this->redirect('/tabungan/detail?karyawan_id=' . $karyawanId);
                    return;
                }

                if ($jumlah > (float)$selectedKas['saldo_saat_ini']) {
                    $pdo->rollBack();
                    $this->flashError("Saldo akun kas '{$selectedKas['nama_akun']}' (" . Format::rupiah((float)$selectedKas['saldo_saat_ini']) . ") tidak mencukupi untuk penarikan (" . Format::rupiah($jumlah) . ").");
                    $this->redirect('/tabungan/detail?karyawan_id=' . $karyawanId);
                    return;
                }
            }

            $tid = $tabungan['id'];

            // 1. Insert transaksi_tabungan
            $stmtInsert = $pdo->prepare("
                INSERT INTO public.transaksi_tabungan (
                    tabungan_id, karyawan_id, tanggal, tipe, jumlah, sumber, akun_kas_id, keterangan, dibuat_pada
                ) VALUES (
                    :tid, :kid, :tgl, 'withdrawal', :jumlah, 'manual', :kas_id, :ket, NOW()
                ) RETURNING id
            ");
            $stmtInsert->execute([
                'tid' => $tid,
                'kid' => $karyawanId,
                'tgl' => $tanggal,
                'jumlah' => $jumlah,
                'kas_id' => !empty($akunKasId) ? $akunKasId : null,
                'ket' => !empty($keterangan) ? $keterangan : 'Penarikan simpanan manual'
            ]);
            $ttId = (string)$stmtInsert->fetchColumn();

            // 2. Potong Saldo Akun Kas & Catat Arus Kas Keluar
            if ($selectedKas) {
                $saldoKasBaru = (float)$selectedKas['saldo_saat_ini'] - $jumlah;
                $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :nom, diubah_pada = NOW() WHERE id = :id")->execute(['nom' => $jumlah, 'id' => $akunKasId]);

                $userId = Auth::user()['id'] ?? null;
                $karyawan = Database::fetchOne("SELECT nama_karyawan FROM public.v_karyawan_info WHERE id = :kid", ['kid' => $karyawanId]);

                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                        nominal, keterangan, referensi_tabel, referensi_id,
                        saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :kas_id, :tgl, 'keluar', 'penarikan_tabungan',
                        :nom, :ket, 'transaksi_tabungan', :ref_id,
                        :saldo_berjalan, :uid, NOW()
                    )
                ")->execute([
                    'kas_id' => $akunKasId,
                    'tgl' => $tanggal,
                    'nom' => $jumlah,
                    'ket' => "Penarikan tabungan {$karyawan['nama_karyawan']}" . (!empty($keterangan) ? " ({$keterangan})" : ''),
                    'ref_id' => $ttId,
                    'saldo_berjalan' => $saldoKasBaru,
                    'uid' => $userId
                ]);
            }

            $pdo->commit();

            $karyawan = Database::fetchOne("SELECT nama_karyawan FROM public.v_karyawan_info WHERE id = :kid", ['kid' => $karyawanId]);

            ActivityLog::log(
                'hr_payroll',
                'TARIK_TABUNGAN',
                "Mencatat penarikan tabungan {$karyawan['nama_karyawan']} sebesar " . Format::rupiah($jumlah) . ($selectedKas ? " dari {$selectedKas['nama_akun']}." : "."),
                'tabungan',
                $tid
            );

            $this->flashSuccess('Penarikan tabungan berhasil diproses.');
            $this->redirect('/tabungan/detail?karyawan_id=' . $karyawanId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal memproses penarikan tabungan: ' . $e->getMessage());
            $this->redirect('/tabungan');
        }
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

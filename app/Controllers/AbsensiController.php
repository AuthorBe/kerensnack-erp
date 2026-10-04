<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
use App\Helpers\CSRF;
use App\Helpers\PdfExport;
use App\Helpers\CompanySetting;
use Database;
use PDO;
use Throwable;

/**
 * app/Controllers/AbsensiController.php
 * Pengendali Modul Kehadiran / Absensi Karyawan Harian Keren One ERP
 */
class AbsensiController extends Controller
{
    public function __construct()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_contains($uri, '/absensi/rekap/pdf') || str_contains($uri, '/absensi/rekap')) {
            Auth::requirePermission(['hr.absensi_view', 'hr.absensi_manage', 'reports.download_hub']);
        } else {
            Auth::requirePermission(['hr.absensi_view', 'hr.absensi_manage']);
        }
    }

    /**
     * Halaman Input Presensi Harian Bulk
     */
    public function index(): void
    {
        try {
            $tanggal = (string)$this->input('tanggal', date('Y-m-d'));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
                $tanggal = date('Y-m-d');
            }

            // Karyawan Borongan
            $karyawanBorongan = Database::fetchAll("
                SELECT
                    k.id as karyawan_id,
                    v.nama_karyawan,
                    v.nama_panggilan,
                    v.posisi,
                    k.uang_kehadiran_harian,
                    a.id as absensi_id,
                    COALESCE(a.status_kehadiran, 'hadir') as status_kehadiran,
                    COALESCE(a.telat, FALSE) as telat,
                    COALESCE(a.lembur_nominal, 0) as lembur_nominal,
                    a.catatan,
                    a.penggajian_id
                FROM public.karyawan k
                JOIN public.v_karyawan_info v ON v.id = k.id
                LEFT JOIN public.absensi a ON a.karyawan_id = k.id AND a.tanggal = :tanggal
                WHERE v.status_aktif = TRUE AND k.tipe_penggajian = 'borongan'
                ORDER BY v.nama_karyawan ASC
            ", ['tanggal' => $tanggal]);

            // Karyawan Bulanan
            $karyawanBulanan = Database::fetchAll("
                SELECT
                    k.id as karyawan_id,
                    v.nama_karyawan,
                    v.nama_panggilan,
                    v.posisi,
                    k.uang_kehadiran_harian,
                    k.gaji_pokok_bulanan,
                    a.id as absensi_id,
                    COALESCE(a.status_kehadiran, 'hadir') as status_kehadiran,
                    COALESCE(a.telat, FALSE) as telat,
                    COALESCE(a.lembur_nominal, 0) as lembur_nominal,
                    COALESCE(a.ambil_uang, FALSE) as ambil_uang,
                    a.catatan,
                    a.penggajian_id,
                    pg.nominal as nominal_penarikan
                FROM public.karyawan k
                JOIN public.v_karyawan_info v ON v.id = k.id
                LEFT JOIN public.absensi a ON a.karyawan_id = k.id AND a.tanggal = :tanggal
                LEFT JOIN public.penarikan_gaji pg ON pg.karyawan_id = k.id AND pg.tanggal = :tanggal
                    AND pg.penggajian_id IS NULL
                WHERE v.status_aktif = TRUE AND k.tipe_penggajian = 'bulanan'
                ORDER BY v.nama_karyawan ASC
            ", ['tanggal' => $tanggal]);

            // Metrics calculation
            $totalBorongan = count($karyawanBorongan);
            $totalBulanan = count($karyawanBulanan);
            $hadirBorongan = count(array_filter($karyawanBorongan, fn($k) => $k['status_kehadiran'] === 'hadir'));
            $hadirBulanan = count(array_filter($karyawanBulanan, fn($k) => $k['status_kehadiran'] === 'hadir'));
            $alpaTotal = count(array_filter(array_merge($karyawanBorongan, $karyawanBulanan), fn($k) => $k['status_kehadiran'] === 'alpa'));
            
            $lockedRows = count(array_filter(array_merge($karyawanBorongan, $karyawanBulanan), fn($k) => !empty($k['penggajian_id'])));
            $isTanggalLocked = ($lockedRows > 0 && $lockedRows === ($totalBorongan + $totalBulanan));

            // Master Akun Kas Aktif (Non-Escrow) untuk Pilihan Sumber Uang Hadir Harian
            $akunKasList = Database::fetchAll("
                SELECT id, nama_akun, tipe_akun, saldo_saat_ini, is_escrow, is_default_pos
                FROM public.akun_kas
                WHERE status_aktif = TRUE AND is_escrow = FALSE
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            $this->view('absensi.index', [
                'pageTitle' => 'Kehadiran Karyawan',
                'pageSubtitle' => 'Pencatatan Presensi Harian & Uang Harian',
                'tanggal' => $tanggal,
                'karyawanBorongan' => $karyawanBorongan,
                'karyawanBulanan' => $karyawanBulanan,
                'akunKasList' => $akunKasList,
                'totalBorongan' => $totalBorongan,
                'totalBulanan' => $totalBulanan,
                'hadirBorongan' => $hadirBorongan,
                'hadirBulanan' => $hadirBulanan,
                'alpaTotal' => $alpaTotal,
                'lockedRows' => $lockedRows,
                'isTanggalLocked' => $isTanggalLocked
            ], 'layouts.master');
        } catch (Throwable $e) {
            $this->flashError('Terjadi kesalahan saat memuat data absensi: ' . $e->getMessage());
            $this->redirect('/dashboard');
        }
    }

    /**
     * POST Simpan Presensi Massal (Bulk Store)
     */
    public function bulkStore(): void
    {
        Auth::requirePermission('hr.absensi_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.');
            $this->redirectBack('/absensi');
            return;
        }

        $tanggal = (string)$this->input('tanggal', date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $this->flashError('Format tanggal tidak valid.');
            $this->redirect('/absensi');
            return;
        }

        $absensiData = $this->input('absensi', []);
        $akunKasId = (string)$this->input('akun_kas_id', '');

        if (!is_array($absensiData) || empty($absensiData)) {
            $this->flashError('Tidak ada data absensi yang dikirim.');
            $this->redirect('/absensi?tanggal=' . $tanggal);
            return;
        }

        $pdo = Database::getConnection();
        $processedCount = 0;
        $skippedLockedCount = 0;
        $userId = Auth::user()['id'] ?? null;

        try {
            $pdo->beginTransaction();

            if (empty($akunKasId)) {
                $stmtDef = $pdo->query("SELECT id FROM public.akun_kas WHERE is_default_pos = TRUE AND status_aktif = TRUE LIMIT 1");
                $akunKasId = (string)($stmtDef->fetchColumn() ?: '');
            }

            // Ambil data seluruh karyawan aktif untuk referensi tipe & uang kehadiran
            $karyawanList = Database::fetchAll("
                SELECT v.id, v.tipe_penggajian, v.uang_kehadiran_harian, v.nama_karyawan 
                FROM public.v_karyawan_info v 
                WHERE v.status_aktif = TRUE
            ");
            $karyawanMap = [];
            foreach ($karyawanList as $emp) {
                $karyawanMap[$emp['id']] = $emp;
            }

            foreach ($absensiData as $karyawanId => $row) {
                if (!isset($karyawanMap[$karyawanId])) {
                    continue; // Lewati jika karyawan tidak ada/non-aktif
                }

                $empInfo = $karyawanMap[$karyawanId];
                $tipePenggajian = $empInfo['tipe_penggajian'];
                $uangKehadiranRate = (float)($empInfo['uang_kehadiran_harian'] ?? 0);

                // Cek apakah record absensi sudah ada dan terkunci oleh payroll
                $existingAbsensi = Database::fetchOne("
                    SELECT id, penggajian_id 
                    FROM public.absensi 
                    WHERE karyawan_id = :kid AND tanggal = :tgl
                ", ['kid' => $karyawanId, 'tgl' => $tanggal]);

                if ($existingAbsensi && !empty($existingAbsensi['penggajian_id'])) {
                    $skippedLockedCount++;
                    continue; // Skip karena sudah terkunci
                }

                $statusKehadiran = (string)($row['status_kehadiran'] ?? 'hadir');
                if (!in_array($statusKehadiran, ['hadir', 'izin', 'sakit', 'libur', 'alpa'], true)) {
                    $statusKehadiran = 'hadir';
                }

                // Strict Discipline Guard: Telat hanya berlaku jika karyawan berstatus HADIR
                $telat = ($statusKehadiran === 'hadir') ? !empty($row['telat']) : false;

                // Strict Overtime Guard: Lembur hanya valid untuk bulanan saat hadir atau libur, bukan saat izin/sakit/alpa
                $rawLembur = ($tipePenggajian === 'bulanan') 
                    ? (float)preg_replace('/[^0-9]/', '', (string)($row['lembur_nominal'] ?? '0')) 
                    : 0.00;
                $lemburNominal = in_array($statusKehadiran, ['izin', 'sakit', 'alpa'], true) ? 0.00 : $rawLembur;
                
                // Strict Backend Guard: Hanya karyawan bulanan dengan status HADIR dan uang_kehadiran_harian > 0 yang valid ambil_uang
                $ambilUang = ($tipePenggajian === 'bulanan' && $uangKehadiranRate > 0 && $statusKehadiran === 'hadir') 
                    ? !empty($row['ambil_uang']) 
                    : false;
                
                $catatan = !empty(trim((string)($row['catatan'] ?? ''))) ? trim((string)$row['catatan']) : null;

                // UPSERT Absensi
                $stmt = $pdo->prepare("
                    INSERT INTO public.absensi (
                        karyawan_id, tanggal, status_kehadiran, telat, 
                        lembur_nominal, ambil_uang, catatan, dibuat_pada, diubah_pada
                    ) VALUES (
                        :kid, :tgl, :status, :telat, 
                        :lembur, :ambil, :catatan, NOW(), NOW()
                    )
                    ON CONFLICT (karyawan_id, tanggal) DO UPDATE SET
                        status_kehadiran = EXCLUDED.status_kehadiran,
                        telat = EXCLUDED.telat,
                        lembur_nominal = EXCLUDED.lembur_nominal,
                        ambil_uang = EXCLUDED.ambil_uang,
                        catatan = EXCLUDED.catatan,
                        diubah_pada = NOW()
                    WHERE public.absensi.penggajian_id IS NULL
                ");
                $stmt->execute([
                    'kid' => $karyawanId,
                    'tgl' => $tanggal,
                    'status' => $statusKehadiran,
                    'telat' => $telat ? 'true' : 'false',
                    'lembur' => $lemburNominal,
                    'ambil' => $ambilUang ? 'true' : 'false',
                    'catatan' => $catatan
                ]);

                // Auto-sync penarikan_gaji untuk karyawan bulanan dengan mutasi kas tertutup
                if ($tipePenggajian === 'bulanan') {
                    $existingPenarikan = Database::fetchOne("
                        SELECT id, nominal, akun_kas_id, penggajian_id 
                        FROM public.penarikan_gaji 
                        WHERE karyawan_id = :kid AND tanggal = :tgl
                    ", ['kid' => $karyawanId, 'tgl' => $tanggal]);

                    if ($statusKehadiran === 'hadir' && $ambilUang && $uangKehadiranRate > 0) {
                        if (!$existingPenarikan) {
                            $targetKas = !empty($akunKasId) ? $akunKasId : null;
                            $stmtPg = $pdo->prepare("
                                INSERT INTO public.penarikan_gaji (
                                    karyawan_id, tanggal, nominal, keterangan, akun_kas_id, dibuat_pada
                                ) VALUES (
                                    :kid, :tgl, :nominal, 'Penarikan uang hadir via absensi', :kas_id, NOW()
                                ) RETURNING id
                            ");
                            $stmtPg->execute([
                                'kid' => $karyawanId,
                                'tgl' => $tanggal,
                                'nominal' => $uangKehadiranRate,
                                'kas_id' => $targetKas
                            ]);
                            $newPgId = (string)$stmtPg->fetchColumn();

                            // Potong kas & catat mutasi jika ada akun kas
                            if ($targetKas) {
                                $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :nom, diubah_pada = NOW() WHERE id = :id")->execute(['nom' => $uangKehadiranRate, 'id' => $targetKas]);
                                $saldoNow = (float)Database::fetchValue("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $targetKas]);

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
                                    'kas_id' => $targetKas,
                                    'tgl' => $tanggal,
                                    'nom' => $uangKehadiranRate,
                                    'ket' => "Uang hadir harian {$empInfo['nama_karyawan']} via absensi",
                                    'ref_id' => $newPgId,
                                    'saldo_berjalan' => $saldoNow,
                                    'uid' => $userId
                                ]);
                            }
                        } elseif (empty($existingPenarikan['penggajian_id'])) {
                            // Update nominal jika berubah
                            $oldNom = (float)$existingPenarikan['nominal'];
                            $diff = $uangKehadiranRate - $oldNom;
                            if (abs($diff) > 0.001) {
                                $pdo->prepare("UPDATE public.penarikan_gaji SET nominal = :nom WHERE id = :id AND penggajian_id IS NULL")->execute(['nom' => $uangKehadiranRate, 'id' => $existingPenarikan['id']]);
                                if (!empty($existingPenarikan['akun_kas_id'])) {
                                    $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :diff, diubah_pada = NOW() WHERE id = :id")->execute(['diff' => $diff, 'id' => $existingPenarikan['akun_kas_id']]);
                                    $pdo->prepare("UPDATE public.arus_kas SET nominal = :nom WHERE referensi_tabel = 'penarikan_gaji' AND referensi_id = :id")->execute(['nom' => $uangKehadiranRate, 'id' => $existingPenarikan['id']]);
                                }
                            }
                        }
                    } else {
                        // Jika tidak hadir atau ambil_uang = FALSE, batalkan penarikan & refund kas
                        if ($existingPenarikan && empty($existingPenarikan['penggajian_id'])) {
                            if (!empty($existingPenarikan['akun_kas_id'])) {
                                $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + :nom, diubah_pada = NOW() WHERE id = :id")->execute(['nom' => $existingPenarikan['nominal'], 'id' => $existingPenarikan['akun_kas_id']]);
                                $pdo->prepare("DELETE FROM public.arus_kas WHERE referensi_tabel = 'penarikan_gaji' AND referensi_id = :id")->execute(['id' => $existingPenarikan['id']]);
                            }
                            $pdo->prepare("DELETE FROM public.penarikan_gaji WHERE id = :id AND penggajian_id IS NULL")->execute(['id' => $existingPenarikan['id']]);
                        }
                    }
                }

                $processedCount++;
            }

            $pdo->commit();

            ActivityLog::log(
                'hr_payroll',
                'BULK_ABSENSI',
                "Menyimpan data presensi massal tanggal {$tanggal} untuk {$processedCount} karyawan." . ($skippedLockedCount > 0 ? " ({$skippedLockedCount} baris terkunci dilewati)" : ""),
                'absensi'
            );

            $this->flashSuccess("Data kehadiran tanggal {$tanggal} berhasil disimpan ({$processedCount} diproses).");
            $this->redirect('/absensi?tanggal=' . $tanggal);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menyimpan absensi: ' . $e->getMessage());
            $this->redirect('/absensi?tanggal=' . $tanggal);
        }
    }

    /**
     * Halaman Rekapitulasi Presensi
     */
    public function rekap(): void
    {
        try {
            $tglAwal = (string)$this->input('tanggal_awal', date('Y-m-01'));
            $tglAkhir = (string)$this->input('tanggal_akhir', date('Y-m-d'));
            $tipeGaji = (string)$this->input('tipe_gaji', 'semua');
            $karyawanId = (string)$this->input('karyawan_id', '');

            $where = ["v.status_aktif = TRUE"];
            $params = [
                'tgl_awal' => $tglAwal,
                'tgl_akhir' => $tglAkhir
            ];

            if ($tipeGaji !== 'semua' && in_array($tipeGaji, ['borongan', 'bulanan'], true)) {
                $where[] = "k.tipe_penggajian = :tipe";
                $params['tipe'] = $tipeGaji;
            }

            if (!empty($karyawanId)) {
                $where[] = "k.id = :kid";
                $params['kid'] = $karyawanId;
            }

            $whereClause = implode(' AND ', $where);

            $rekapData = Database::fetchAll("
                SELECT
                    v.id as karyawan_id,
                    v.nama_karyawan,
                    v.nama_panggilan,
                    v.posisi,
                    k.tipe_penggajian,
                    k.uang_kehadiran_harian,
                    COUNT(CASE WHEN a.status_kehadiran = 'hadir' THEN 1 END) as hari_hadir,
                    COUNT(CASE WHEN a.status_kehadiran = 'izin' THEN 1 END) as hari_izin,
                    COUNT(CASE WHEN a.status_kehadiran = 'sakit' THEN 1 END) as hari_sakit,
                    COUNT(CASE WHEN a.status_kehadiran = 'libur' THEN 1 END) as hari_libur,
                    COUNT(CASE WHEN a.status_kehadiran = 'alpa' THEN 1 END) as hari_alpa,
                    COUNT(CASE WHEN a.telat = TRUE THEN 1 END) as hari_telat,
                    COUNT(CASE WHEN a.ambil_uang = TRUE THEN 1 END) as hari_ambil_uang,
                    COALESCE(SUM(a.lembur_nominal), 0) as total_lembur_nominal,
                    COUNT(a.id) as total_tercatat
                FROM public.v_karyawan_info v
                JOIN public.karyawan k ON k.id = v.id
                LEFT JOIN public.absensi a ON a.karyawan_id = k.id
                    AND a.tanggal BETWEEN :tgl_awal AND :tgl_akhir
                WHERE {$whereClause}
                GROUP BY v.id, v.nama_karyawan, v.nama_panggilan, v.posisi, k.tipe_penggajian, k.uang_kehadiran_harian
                ORDER BY k.tipe_penggajian ASC, v.nama_karyawan ASC
            ", $params);

            $karyawanList = Database::fetchAll("
                SELECT id, nama_karyawan, tipe_penggajian 
                FROM public.v_karyawan_info 
                WHERE status_aktif = TRUE 
                ORDER BY nama_karyawan ASC
            ");

            // Summary metrics
            $totalKaryawan = count($rekapData);
            $totalHadir = array_sum(array_column($rekapData, 'hari_hadir'));
            $totalIzinSakit = array_sum(array_column($rekapData, 'hari_izin')) + array_sum(array_column($rekapData, 'hari_sakit'));
            $totalAlpa = array_sum(array_column($rekapData, 'hari_alpa'));
            $totalLembur = array_sum(array_column($rekapData, 'total_lembur_nominal'));

            $this->view('absensi.rekap', [
                'pageTitle' => 'Rekapitulasi Kehadiran',
                'pageSubtitle' => 'Laporan Akumulasi Presensi Periode Kerja',
                'tglAwal' => $tglAwal,
                'tglAkhir' => $tglAkhir,
                'tipeGaji' => $tipeGaji,
                'karyawanId' => $karyawanId,
                'karyawanList' => $karyawanList,
                'rekapData' => $rekapData,
                'totalKaryawan' => $totalKaryawan,
                'totalHadir' => $totalHadir,
                'totalIzinSakit' => $totalIzinSakit,
                'totalAlpa' => $totalAlpa,
                'totalLembur' => $totalLembur
            ], 'layouts.master');
        } catch (Throwable $e) {
            $this->flashError('Terjadi kesalahan saat memuat rekap absensi: ' . $e->getMessage());
            $this->redirect('/absensi');
        }
    }

    /**
     * Export Rekap Kehadiran ke Format PDF
     */
    public function rekapPdf(): void
    {
        try {
            $tglAwal = (string)$this->input('tanggal_awal', date('Y-m-01'));
            $tglAkhir = (string)$this->input('tanggal_akhir', date('Y-m-d'));
            $tipeGaji = (string)$this->input('tipe_gaji', 'semua');
            $karyawanId = (string)$this->input('karyawan_id', '');

            $where = ["v.status_aktif = TRUE"];
            $params = [
                'tgl_awal' => $tglAwal,
                'tgl_akhir' => $tglAkhir
            ];

            if ($tipeGaji !== 'semua' && in_array($tipeGaji, ['borongan', 'bulanan'], true)) {
                $where[] = "k.tipe_penggajian = :tipe";
                $params['tipe'] = $tipeGaji;
            }

            if (!empty($karyawanId)) {
                $where[] = "k.id = :kid";
                $params['kid'] = $karyawanId;
            }

            $whereClause = implode(' AND ', $where);

            $rekapData = Database::fetchAll("
                SELECT
                    v.id as karyawan_id,
                    v.nama_karyawan,
                    v.nama_panggilan,
                    v.posisi,
                    k.tipe_penggajian,
                    k.uang_kehadiran_harian,
                    COUNT(CASE WHEN a.status_kehadiran = 'hadir' THEN 1 END) as hari_hadir,
                    COUNT(CASE WHEN a.status_kehadiran = 'izin' THEN 1 END) as hari_izin,
                    COUNT(CASE WHEN a.status_kehadiran = 'sakit' THEN 1 END) as hari_sakit,
                    COUNT(CASE WHEN a.status_kehadiran = 'libur' THEN 1 END) as hari_libur,
                    COUNT(CASE WHEN a.status_kehadiran = 'alpa' THEN 1 END) as hari_alpa,
                    COUNT(CASE WHEN a.telat = TRUE THEN 1 END) as hari_telat,
                    COUNT(CASE WHEN a.ambil_uang = TRUE THEN 1 END) as hari_ambil_uang,
                    COALESCE(SUM(a.lembur_nominal), 0) as total_lembur_nominal,
                    COUNT(a.id) as total_tercatat
                FROM public.v_karyawan_info v
                JOIN public.karyawan k ON k.id = v.id
                LEFT JOIN public.absensi a ON a.karyawan_id = k.id
                    AND a.tanggal BETWEEN :tgl_awal AND :tgl_akhir
                WHERE {$whereClause}
                GROUP BY v.id, v.nama_karyawan, v.nama_panggilan, v.posisi, k.tipe_penggajian, k.uang_kehadiran_harian
                ORDER BY k.tipe_penggajian ASC, v.nama_karyawan ASC
            ", $params);

            $comp = CompanySetting::getAll();

            // Render view ke HTML buffer
            ob_start();
            $data = [
                'tglAwal' => $tglAwal,
                'tglAkhir' => $tglAkhir,
                'tipeGaji' => $tipeGaji,
                'rekapData' => $rekapData,
                'comp' => $comp,
                'company' => $comp,
                'companyName' => $comp['nama'] ?? 'KEREN SNACK',
                'companyAddress' => $comp['alamat'] ?? ''
            ];
            extract($data);
            require dirname(__DIR__, 2) . '/views/absensi/rekap_pdf.php';
            $html = ob_get_clean();

            $dateRange = ($tglAwal === $tglAkhir)
                ? date('d M Y', strtotime($tglAwal))
                : (date('d M Y', strtotime($tglAwal)) . ' sd ' . date('d M Y', strtotime($tglAkhir)));
            
            $suffixTipe = ($tipeGaji === 'borongan') ? ' Borongan' : (($tipeGaji === 'bulanan') ? ' Bulanan' : '');
            $filename = "Rekapitulasi Kehadiran{$suffixTipe} ({$dateRange}).pdf";
            PdfExport::download($html, $filename, 'A4', 'landscape');
        } catch (Throwable $e) {
            $this->flashError('Gagal mencetak PDF rekap absensi: ' . $e->getMessage());
            $this->redirect('/absensi/rekap');
        }
    }
}

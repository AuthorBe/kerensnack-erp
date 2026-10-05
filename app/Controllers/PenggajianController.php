<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
use App\Helpers\CompanySetting;
use App\Helpers\CSRF;
use App\Helpers\Format;
use App\Helpers\PdfExport;
use Database;
use PDO;
use Throwable;
use DateTime;

/**
 * app/Controllers/PenggajianController.php
 * Payroll Engine & Penggajian Karyawan Keren One ERP
 */
class PenggajianController extends Controller
{
    public function __construct()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_contains($uri, '/penggajian/rekap-pdf')) {
            Auth::requirePermission(['hr.payroll_view', 'hr.payroll_manage', 'hr.payroll_approve', 'reports.download_hub']);
        } else {
            Auth::requirePermission(['hr.payroll_view', 'hr.payroll_manage', 'hr.payroll_approve']);
        }
    }

    /**
     * GET /penggajian — Daftar Payroll Runs
     */
    public function index(): void
    {
        try {
            $payrollList = Database::fetchAll("
                SELECT
                    p.id, p.nomor_referensi, p.nama_payroll, p.periode_awal, p.periode_akhir,
                    p.tipe_penggajian, p.status,
                    COALESCE(
                        NULLIF(p.total_gaji_dikeluarkan, 0),
                        (SELECT SUM(rp.gaji_bersih_diterima) FROM public.rincian_penggajian rp WHERE rp.penggajian_id = p.id AND rp.is_excluded = FALSE),
                        0
                    ) as total_gaji_dikeluarkan,
                    p.disetujui_pada, p.options_json, p.dibuat_pada,
                    pv.nama_lengkap as nama_approver,
                    (SELECT COUNT(*) FROM public.rincian_penggajian rp WHERE rp.penggajian_id = p.id) as total_karyawan,
                    (SELECT COUNT(*) FROM public.rincian_penggajian rp WHERE rp.penggajian_id = p.id AND rp.is_excluded = FALSE) as total_karyawan_terbayar
                FROM public.penggajian p
                LEFT JOIN public.pengguna pv ON pv.id = p.disetujui_oleh
                ORDER BY p.dibuat_pada DESC
            ");

            $hasPendingDraft = false;
            foreach ($payrollList as $p) {
                if ($p['status'] === 'draf') {
                    $hasPendingDraft = true;
                    break;
                }
            }

            // Metrics
            $totalRuns = count($payrollList);
            $totalGajiDisetujui = 0;
            $approvedCount = 0;
            foreach ($payrollList as $p) {
                if (in_array($p['status'], ['disetujui', 'dibayarkan'], true)) {
                    $totalGajiDisetujui += (float)$p['total_gaji_dikeluarkan'];
                    $approvedCount++;
                }
            }

            $this->view('penggajian.index', [
                'pageTitle' => 'Penggajian (Payroll Engine)',
                'pageSubtitle' => 'Kelola Periode Penggajian, Kalkulasi Gaji, & Slip Gaji',
                'payrollList' => $payrollList,
                'hasPendingDraft' => $hasPendingDraft,
                'totalRuns' => $totalRuns,
                'totalGajiDisetujui' => $totalGajiDisetujui,
                'approvedCount' => $approvedCount
            ], 'layouts.master');
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat daftar penggajian: ' . $e->getMessage());
            $this->redirect('/dashboard');
        }
    }

    /**
     * GET /penggajian/create — Form Setup Generate Payroll
     */
    public function create(): void
    {
        Auth::requirePermission('hr.payroll_manage');

        try {
            $editId = (string)($this->input('edit_id', '') ?: $this->input('run_id', ''));
            $isEdit = false;
            $editRun = null;
            $selectedBoronganIds = [];
            $selectedBulananIds = [];
            $initialPayrollName = '';

            // Default dates:
            // Borongan: Senin minggu lalu s/d Minggu minggu lalu
            $mondayLastWeek = (new DateTime())->modify('last week monday')->format('Y-m-d');
            $sundayLastWeek = (new DateTime())->modify('last week sunday')->format('Y-m-d');

            // Bulanan: Awal bulan ini s/d Akhir bulan ini
            $firstDayMonth = date('Y-m-01');
            $lastDayMonth = date('Y-m-t');

            // Cek apakah ada draft yang sedang aktif
            $draftRun = Database::fetchOne("SELECT id, nomor_referensi, nama_payroll, options_json, tipe_penggajian, periode_awal, periode_akhir FROM public.penggajian WHERE status = 'draf' LIMIT 1");

            if (!empty($editId)) {
                $editRun = Database::fetchOne("SELECT * FROM public.penggajian WHERE id = :id AND status = 'draf'", ['id' => $editId]);
                if ($editRun) {
                    $isEdit = true;
                    $initialPayrollName = $editRun['nama_payroll'] ?? '';
                    $options = json_decode((string)$editRun['options_json'], true) ?: [];

                    if (isset($options['borongan']['start']) && isset($options['borongan']['end'])) {
                        $mondayLastWeek = $options['borongan']['start'];
                        $sundayLastWeek = $options['borongan']['end'];
                    } elseif (!empty($editRun['periode_awal']) && !empty($editRun['periode_akhir']) && $editRun['tipe_penggajian'] !== 'bulanan') {
                        $mondayLastWeek = $editRun['periode_awal'];
                        $sundayLastWeek = $editRun['periode_akhir'];
                    }

                    if (isset($options['bulanan']['start']) && isset($options['bulanan']['end'])) {
                        $firstDayMonth = $options['bulanan']['start'];
                        $lastDayMonth = $options['bulanan']['end'];
                    } elseif (!empty($editRun['periode_awal']) && !empty($editRun['periode_akhir']) && $editRun['tipe_penggajian'] === 'bulanan') {
                        $firstDayMonth = $editRun['periode_awal'];
                        $lastDayMonth = $editRun['periode_akhir'];
                    }

                    // Ambil karyawan yang ada di draf saat ini
                    $existingItems = Database::fetchAll("
                        SELECT rp.karyawan_id, k.tipe_penggajian
                        FROM public.rincian_penggajian rp
                        JOIN public.karyawan k ON k.id = rp.karyawan_id
                        WHERE rp.penggajian_id = :id
                    ", ['id' => $editRun['id']]);

                    foreach ($existingItems as $it) {
                        if ($it['tipe_penggajian'] === 'borongan' || $it['tipe_penggajian'] === 'mingguan') {
                            $selectedBoronganIds[] = $it['karyawan_id'];
                        } elseif ($it['tipe_penggajian'] === 'bulanan') {
                            $selectedBulananIds[] = $it['karyawan_id'];
                        }
                    }
                } else {
                    $this->flashWarning('Draf payroll yang ingin diedit tidak ditemukan atau sudah disetujui.');
                    $this->redirect('/penggajian');
                    return;
                }
            }

            // Karyawan list & summary
            $karyawanBorongan = Database::fetchAll("
                SELECT k.id, v.nama_karyawan, v.posisi, k.uang_kehadiran_harian, k.gaji_pokok_bulanan
                FROM public.karyawan k
                JOIN public.v_karyawan_info v ON v.id = k.id
                WHERE v.status_aktif = TRUE AND k.tipe_penggajian = 'borongan'
                ORDER BY v.nama_karyawan ASC
            ");

            $karyawanBulanan = Database::fetchAll("
                SELECT k.id, v.nama_karyawan, v.posisi, k.uang_kehadiran_harian, k.gaji_pokok_bulanan, k.tunjangan_bulanan
                FROM public.karyawan k
                JOIN public.v_karyawan_info v ON v.id = k.id
                WHERE v.status_aktif = TRUE AND k.tipe_penggajian = 'bulanan'
                ORDER BY v.nama_karyawan ASC
            ");

            $boronganCount = count($karyawanBorongan);
            $bulananCount = count($karyawanBulanan);

            $this->view('penggajian.create', [
                'pageTitle' => $isEdit ? 'Regenerasi & Edit Parameter Draf' : 'Generate Payroll Baru',
                'pageSubtitle' => $isEdit ? 'Sesuaikan rentang periode dan daftar karyawan sebelum menghitung ulang' : 'Pilih Karyawan dan Rentang Periode Penggajian',
                'mondayLastWeek' => $mondayLastWeek,
                'sundayLastWeek' => $sundayLastWeek,
                'firstDayMonth' => $firstDayMonth,
                'lastDayMonth' => $lastDayMonth,
                'draftRun' => $draftRun,
                'isEdit' => $isEdit,
                'editRun' => $editRun,
                'selectedBoronganIds' => $selectedBoronganIds,
                'selectedBulananIds' => $selectedBulananIds,
                'initialPayrollName' => $initialPayrollName,
                'karyawanBorongan' => $karyawanBorongan,
                'karyawanBulanan' => $karyawanBulanan,
                'boronganCount' => $boronganCount,
                'bulananCount' => $bulananCount
            ], 'layouts.master');
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat halaman form payroll: ' . $e->getMessage());
            $this->redirect('/penggajian');
        }
    }

    /**
     * POST /penggajian/generate — Eksekusi Generate Draf Payroll
     */
    public function generate(): void
    {
        Auth::requirePermission('hr.payroll_manage');

        $editRunId = (string)$this->input('edit_run_id', '');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirect($editRunId ? '/penggajian/create?edit_id=' . $editRunId : '/penggajian/create');
            return;
        }

        $editRun = null;
        if (!empty($editRunId)) {
            $editRun = Database::fetchOne("SELECT * FROM public.penggajian WHERE id = :id AND status = 'draf'", ['id' => $editRunId]);
            if (!$editRun) {
                $this->flashError('Draf payroll yang ingin di-regenerate tidak valid atau sudah disetujui.');
                $this->redirect('/penggajian');
                return;
            }
        }

        $boronganIds = array_values(array_filter((array)($this->input('karyawan_borongan_ids', []))));
        $bulananIds  = array_values(array_filter((array)($this->input('karyawan_bulanan_ids', []))));
        $selectedEmployeeIds = array_merge($boronganIds, $bulananIds);

        // Fallback for direct types parameter or testing
        $typesInput = (array)($this->input('types', []));
        $types = [];

        if (!empty($boronganIds)) {
            $types[] = 'borongan';
        } elseif (in_array('borongan', $typesInput, true)) {
            $types[] = 'borongan';
        }

        if (!empty($bulananIds)) {
            $types[] = 'bulanan';
        } elseif (in_array('bulanan', $typesInput, true)) {
            $types[] = 'bulanan';
        }

        $types = array_unique($types);

        if (empty($types) && empty($selectedEmployeeIds)) {
            $this->flashError('Pilih minimal satu karyawan (Borongan atau Bulanan) untuk diproses penggajiannya.');
            $this->redirect($editRunId ? '/penggajian/create?edit_id=' . $editRunId : '/penggajian/create');
            return;
        }

        // Toggle Switch Gaji Pokok & Tunjangan Bulanan (Default TRUE if not specified)
        $includeMonthlyBaseParam = $this->input('include_monthly_base');
        $includeMonthlyBase = ($includeMonthlyBaseParam === null || $includeMonthlyBaseParam === '1' || $includeMonthlyBaseParam === true || $includeMonthlyBaseParam === 'true');

        $namaPayrollInput = trim((string)$this->input('nama_payroll', ''));
        if ($namaPayrollInput === '') {
            $this->flashError('Kolom Judul / Nama Payroll wajib diisi.');
            $this->redirect($editRunId ? '/penggajian/create?edit_id=' . $editRunId : '/penggajian/create');
            return;
        }
        $namaPayroll = $namaPayrollInput;
        $boronganStart = (string)$this->input('periode_awal_borongan', '');
        $boronganEnd = (string)$this->input('periode_akhir_borongan', '');
        $bulananStart = (string)$this->input('periode_awal_bulanan', '');
        $bulananEnd = (string)$this->input('periode_akhir_bulanan', '');

        $options = [];
        $runTipe = 'borongan';

        if (in_array('borongan', $types, true)) {
            if (empty($boronganStart) || empty($boronganEnd) || $boronganStart > $boronganEnd) {
                $this->flashError('Rentang periode borongan tidak valid.');
                $this->redirect($editRunId ? '/penggajian/create?edit_id=' . $editRunId : '/penggajian/create');
                return;
            }
            $options['borongan'] = [
                'start' => $boronganStart,
                'end' => $boronganEnd
            ];
        }

        if (in_array('bulanan', $types, true)) {
            if (empty($bulananStart) || empty($bulananEnd) || $bulananStart > $bulananEnd) {
                $this->flashError('Rentang periode bulanan tidak valid.');
                $this->redirect($editRunId ? '/penggajian/create?edit_id=' . $editRunId : '/penggajian/create');
                return;
            }
            $options['bulanan'] = [
                'start' => $bulananStart,
                'end' => $bulananEnd
            ];
        }

        if (isset($options['borongan']) && isset($options['bulanan'])) {
            $runTipe = 'gabungan';
        } elseif (isset($options['bulanan'])) {
            $runTipe = 'bulanan';
        } else {
            $runTipe = 'mingguan';
        }

        // Tentukan min start dan max end untuk header
        $allStarts = [];
        $allEnds = [];
        if (isset($options['borongan'])) {
            $allStarts[] = $options['borongan']['start'];
            $allEnds[] = $options['borongan']['end'];
        }
        if (isset($options['bulanan'])) {
            $allStarts[] = $options['bulanan']['start'];
            $allEnds[] = $options['bulanan']['end'];
        }
        $headerStart = min($allStarts);
        $headerEnd = max($allEnds);

        // 1. Cek jika ada draft payroll yang masih pending (kecuali jika sedang mengedit draf ini)
        $existingDraft = Database::fetchOne("SELECT id, nomor_referensi FROM public.penggajian WHERE status = 'draf' LIMIT 1");
        if ($existingDraft && (!$editRun || $existingDraft['id'] !== $editRun['id'])) {
            $this->flashWarning("Masih ada draf payroll ({$existingDraft['nomor_referensi']}) yang belum disetujui atau dihapus. Harap selesaikan terlebih dahulu.");
            $this->redirect('/penggajian/preview?id=' . $existingDraft['id']);
            return;
        }

        // 2. Cek Overlap dengan payroll yang sudah disetujui
        $approvedRuns = Database::fetchAll("
            SELECT id, nomor_referensi, nama_payroll, options_json 
            FROM public.penggajian 
            WHERE status IN ('disetujui', 'dibayarkan')
        ");

        foreach ($approvedRuns as $ar) {
            $arOpt = json_decode((string)$ar['options_json'], true);
            if (!is_array($arOpt)) continue;

            if (isset($options['borongan']) && isset($arOpt['borongan'])) {
                $bStart = $options['borongan']['start'];
                $bEnd = $options['borongan']['end'];
                $exStart = $arOpt['borongan']['start'];
                $exEnd = $arOpt['borongan']['end'];
                if ($bStart <= $exEnd && $bEnd >= $exStart) {
                    $this->flashError("Periode borongan ({$bStart} s/d {$bEnd}) bentrok (overlap) dengan payroll {$ar['nomor_referensi']} ({$ar['nama_payroll']}).");
                    $this->redirect($editRunId ? '/penggajian/create?edit_id=' . $editRunId : '/penggajian/create');
                    return;
                }
            }

            if (isset($options['bulanan']) && isset($arOpt['bulanan'])) {
                $mStart = $options['bulanan']['start'];
                $mEnd = $options['bulanan']['end'];
                $exStart = $arOpt['bulanan']['start'];
                $exEnd = $arOpt['bulanan']['end'];
                if ($mStart <= $exEnd && $mEnd >= $exStart) {
                    $this->flashError("Periode bulanan ({$mStart} s/d {$mEnd}) bentrok (overlap) dengan payroll {$ar['nomor_referensi']} ({$ar['nama_payroll']}).");
                    $this->redirect($editRunId ? '/penggajian/create?edit_id=' . $editRunId : '/penggajian/create');
                    return;
                }
            }
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            if ($editRun) {
                $runId = $editRun['id'];
                $nomorRef = $editRun['nomor_referensi'];

                // Unlock previous records
                $pdo->prepare("UPDATE public.absensi SET penggajian_id = NULL WHERE penggajian_id = :rid")->execute(['rid' => $runId]);
                $pdo->prepare("UPDATE public.produksi_harian SET penggajian_id = NULL WHERE penggajian_id = :rid")->execute(['rid' => $runId]);
                $pdo->prepare("UPDATE public.penarikan_gaji SET penggajian_id = NULL WHERE penggajian_id = :rid")->execute(['rid' => $runId]);
                $pdo->prepare("DELETE FROM public.rincian_penggajian WHERE penggajian_id = :rid")->execute(['rid' => $runId]);

                // Options payload
                $optionsPayload = $options;
                $optionsPayload['include_monthly_base'] = $includeMonthlyBase;
                if (!empty($selectedEmployeeIds)) {
                    $optionsPayload['selected_karyawan_ids'] = $selectedEmployeeIds;
                }

                // Update Header Penggajian
                $stmtUpdate = $pdo->prepare("
                    UPDATE public.penggajian
                    SET nama_payroll = :nama,
                        periode_awal = :start,
                        periode_akhir = :end,
                        tipe_penggajian = :tipe,
                        options_json = :opt,
                        diubah_pada = NOW()
                    WHERE id = :rid
                ");
                $stmtUpdate->execute([
                    'nama' => $namaPayroll,
                    'start' => $headerStart,
                    'end' => $headerEnd,
                    'tipe' => $runTipe,
                    'opt' => json_encode($optionsPayload),
                    'rid' => $runId
                ]);
            } else {
                // Generate Unique Nomor Referensi: PAY-YYYYMMDD-XXXX
                $nomorRef = '';
                for ($attempt = 0; $attempt < 10; $attempt++) {
                    $candidate = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
                    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM public.penggajian WHERE nomor_referensi = :ref");
                    $stmtCheck->execute(['ref' => $candidate]);
                    if ((int)$stmtCheck->fetchColumn() === 0) {
                        $nomorRef = $candidate;
                        break;
                    }
                }
                if (empty($nomorRef)) {
                    $nomorRef = 'PAY-' . date('Ymd') . '-' . strtoupper(uniqid());
                }

                // Options payload
                $optionsPayload = $options;
                $optionsPayload['include_monthly_base'] = $includeMonthlyBase;
                if (!empty($selectedEmployeeIds)) {
                    $optionsPayload['selected_karyawan_ids'] = $selectedEmployeeIds;
                }

                // Insert Header Penggajian
                $stmtInsert = $pdo->prepare("
                    INSERT INTO public.penggajian (
                        nomor_referensi, nama_payroll, periode_awal, periode_akhir,
                        tipe_penggajian, status, options_json, total_gaji_dikeluarkan,
                        dibuat_pada, diubah_pada
                    ) VALUES (
                        :ref, :nama, :start, :end,
                        :tipe, 'draf', :opt, 0.00,
                        NOW(), NOW()
                    ) RETURNING id
                ");
                $stmtInsert->execute([
                    'ref' => $nomorRef,
                    'nama' => $namaPayroll,
                    'start' => $headerStart,
                    'end' => $headerEnd,
                    'tipe' => $runTipe,
                    'opt' => json_encode($optionsPayload)
                ]);
                $runId = (string)$stmtInsert->fetchColumn();
            }

            // Generate Rincian Item
            $itemCount = 0;
            $preventedDoubleCount = 0;
            $this->generatePayrollItems($pdo, $runId, $options, $itemCount, $preventedDoubleCount, $selectedEmployeeIds, $includeMonthlyBase);

            if ($itemCount === 0) {
                $pdo->rollBack();
                $this->flashWarning('Tidak ada data kehadiran, produksi, gaji pokok, atau penarikan gaji yang ditemukan pada karyawan terpilih di periode tersebut.');
                $this->redirect($editRun ? '/penggajian/create?edit_id=' . $editRun['id'] : '/penggajian/create');
                return;
            }

            // Hitung total draf
            $stmtSum = $pdo->prepare("SELECT COALESCE(SUM(gaji_bersih_diterima), 0) FROM public.rincian_penggajian WHERE penggajian_id = :rid AND is_excluded = FALSE");
            $stmtSum->execute(['rid' => $runId]);
            $totalGajiDraf = (float)$stmtSum->fetchColumn();

            $stmtUpdateTotal = $pdo->prepare("UPDATE public.penggajian SET total_gaji_dikeluarkan = :total WHERE id = :rid");
            $stmtUpdateTotal->execute(['total' => $totalGajiDraf, 'rid' => $runId]);

            $pdo->commit();

            if ($editRun) {
                ActivityLog::log(
                    'hr_payroll',
                    'REGENERATE_PAYROLL',
                    "Melakukan regenerasi & pembaruan draf penggajian {$nomorRef} ({$namaPayroll}) untuk {$itemCount} karyawan dengan estimasi " . Format::rupiah($totalGajiDraf) . ".",
                    'penggajian',
                    $runId
                );
                $this->flashSuccess("Draf payroll {$nomorRef} berhasil di-regenerasi ({$itemCount} karyawan)." . ($preventedDoubleCount > 0 ? " Anti-double pay mendeteksi {$preventedDoubleCount} karyawan yang sudah menerima gaji pokok di bulan ini." : ""));
            } else {
                ActivityLog::log(
                    'hr_payroll',
                    'GENERATE_PAYROLL',
                    "Membuat draf penggajian {$nomorRef} ({$namaPayroll}) untuk {$itemCount} karyawan dengan estimasi " . Format::rupiah($totalGajiDraf) . ".",
                    'penggajian',
                    $runId
                );
                $this->flashSuccess("Draf penggajian {$nomorRef} berhasil di-generate ({$itemCount} karyawan)." . ($preventedDoubleCount > 0 ? " Anti-double pay mendeteksi {$preventedDoubleCount} karyawan yang sudah menerima gaji pokok di bulan ini." : ""));
            }

            $this->redirect('/penggajian/preview?id=' . $runId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal me-generate payroll: ' . $e->getMessage());
            $this->redirect($editRun ? '/penggajian/create?edit_id=' . $editRun['id'] : '/penggajian/create');
        }
    }

    /**
     * Private Helper: Auto-Generate Judul Payroll
     */
    private function generatePayrollTitle(array $options, string $tipe): string
    {
        if ($tipe === 'borongan' && isset($options['borongan'])) {
            return 'Payroll Borongan ' . Format::tanggalIndo($options['borongan']['start']) . ' - ' . Format::tanggalIndo($options['borongan']['end']);
        } elseif ($tipe === 'bulanan' && isset($options['bulanan'])) {
            $bulan = Format::bulanIndo((int)date('m', strtotime($options['bulanan']['end'])));
            $tahun = date('Y', strtotime($options['bulanan']['end']));
            return 'Payroll Bulanan ' . $bulan . ' ' . $tahun;
        } else {
            $end = isset($options['bulanan']) ? $options['bulanan']['end'] : ($options['borongan']['end'] ?? date('Y-m-d'));
            $bulan = Format::bulanIndo((int)date('m', strtotime($end)));
            $tahun = date('Y', strtotime($end));
            return 'Payroll Gabungan ' . $bulan . ' ' . $tahun;
        }
    }

    /**
     * Private Helper: Engine Kalkulasi Item Payroll
     */
    private function generatePayrollItems(
        PDO $pdo,
        string $runId,
        array $options,
        int &$itemCount,
        int &$preventedDoubleCount,
        array $selectedEmployeeIds = [],
        bool $includeMonthlyBase = true
    ): void {
        $targetTipes = [];
        if (isset($options['borongan'])) $targetTipes[] = 'borongan';
        if (isset($options['bulanan'])) $targetTipes[] = 'bulanan';

        $inClause = "'" . implode("','", $targetTipes) . "'";
        $sql = "
            SELECT k.id, k.pengguna_id, k.tipe_penggajian, k.gaji_pokok_bulanan,
                   k.uang_kehadiran_harian, k.tunjangan_bulanan,
                   v.nama_karyawan, v.posisi
            FROM public.karyawan k
            JOIN public.v_karyawan_info v ON v.id = k.id
            WHERE v.status_aktif = TRUE AND k.tipe_penggajian IN ($inClause)
        ";

        if (!empty($selectedEmployeeIds)) {
            $idPlaceholders = [];
            $params = [];
            foreach ($selectedEmployeeIds as $idx => $eid) {
                $pName = ':sel_id_' . $idx;
                $idPlaceholders[] = $pName;
                $params[$pName] = $eid;
            }
            $sql .= " AND k.id IN (" . implode(',', $idPlaceholders) . ")";
            $sql .= " ORDER BY k.tipe_penggajian DESC, v.nama_karyawan ASC";
            $stmtEmp = $pdo->prepare($sql);
            $stmtEmp->execute($params);
        } else {
            $sql .= " ORDER BY k.tipe_penggajian DESC, v.nama_karyawan ASC";
            $stmtEmp = $pdo->query($sql);
        }
        $karyawanList = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);

        foreach ($karyawanList as $emp) {
            $tipeEmp = $emp['tipe_penggajian'];
            if (!isset($options[$tipeEmp])) continue;

            $empStart = $options[$tipeEmp]['start'];
            $empEnd   = $options[$tipeEmp]['end'];

            // A. Kehadiran
            $stmtAtt = $pdo->prepare("
                SELECT
                    COUNT(CASE WHEN status_kehadiran = 'hadir' THEN 1 END) as hari_hadir,
                    COALESCE(SUM(lembur_nominal), 0) as total_lembur_nominal
                FROM public.absensi
                WHERE karyawan_id = :kid AND tanggal BETWEEN :start AND :end
                  AND penggajian_id IS NULL
            ");
            $stmtAtt->execute(['kid' => $emp['id'], 'start' => $empStart, 'end' => $empEnd]);
            $attData = $stmtAtt->fetch(PDO::FETCH_ASSOC);

            $hariHadir = (int)($attData['hari_hadir'] ?? 0);
            $totalUangKehadiran = $hariHadir * (float)$emp['uang_kehadiran_harian'];

            // B. Upah Produksi & Lembur (Borongan)
            $totalUpahBorongan = 0.00;
            $totalUpahLembur   = 0.00;

            if ($tipeEmp === 'borongan') {
                $stmtProd = $pdo->prepare("
                    SELECT
                        COALESCE(SUM(ph.kuantitas_pcs * ph.upah_per_pcs_snapshot), 0) as total_reguler_pcs,
                        COALESCE(SUM((ph.lembur_pcs + ph.lembur_bal) * ph.upah_per_pcs_snapshot), 0) as total_lembur_pcs
                    FROM public.produksi_harian ph
                    WHERE ph.karyawan_id = :kid AND ph.tanggal BETWEEN :start AND :end
                      AND ph.penggajian_id IS NULL
                ");
                $stmtProd->execute(['kid' => $emp['id'], 'start' => $empStart, 'end' => $empEnd]);
                $prodBreak = $stmtProd->fetch(PDO::FETCH_ASSOC);

                $totalUpahBorongan = (float)($prodBreak['total_reguler_pcs'] ?? 0);
                $totalUpahLembur   = (float)($prodBreak['total_lembur_pcs'] ?? 0);
            }

            // C. Lembur Bulanan
            if ($tipeEmp === 'bulanan') {
                $totalUpahLembur = (float)($attData['total_lembur_nominal'] ?? 0);
            }

            // D. Gaji Pokok & Tunjangan Bulanan (Manual Toggle + Anti-Double Pay Guard)
            $gajiPokok = 0.00;
            $tunjanganBulanan = 0.00;

            if ($tipeEmp === 'bulanan') {
                if ($includeMonthlyBase) {
                    $endDateObj = new DateTime($empEnd);
                    $monthYear = $endDateObj->format('Y-m');
                    $stmtCheckPaid = $pdo->prepare("
                        SELECT 1 FROM public.rincian_penggajian rp
                        JOIN public.penggajian p ON p.id = rp.penggajian_id
                        WHERE rp.karyawan_id = :kid
                          AND TO_CHAR(p.periode_akhir, 'YYYY-MM') = :bulan
                          AND (rp.tunjangan_bulanan > 0 OR rp.gaji_pokok > 0)
                          AND p.status IN ('disetujui', 'dibayarkan')
                        LIMIT 1
                    ");
                    $stmtCheckPaid->execute(['kid' => $emp['id'], 'bulan' => $monthYear]);
                    $alreadyPaid = $stmtCheckPaid->fetchColumn();

                    if (!$alreadyPaid) {
                        $gajiPokok = (float)$emp['gaji_pokok_bulanan'];
                        $tunjanganBulanan = (float)$emp['tunjangan_bulanan'];
                    } else {
                        if ((float)$emp['gaji_pokok_bulanan'] > 0 || (float)$emp['tunjangan_bulanan'] > 0) {
                            $preventedDoubleCount++;
                        }
                    }
                }
            }

            // E. Penarikan Gaji (Advance)
            $totalPenarikanGaji = 0.00;
            $penarikanDetails = [];

            $stmtPenarikan = $pdo->prepare("
                SELECT id, tanggal, nominal, keterangan
                FROM public.penarikan_gaji
                WHERE karyawan_id = :kid AND tanggal <= :end AND penggajian_id IS NULL
                ORDER BY tanggal ASC
            ");
            $stmtPenarikan->execute(['kid' => $emp['id'], 'end' => $empEnd]);
            $pendingPenarikan = $stmtPenarikan->fetchAll(PDO::FETCH_ASSOC);

            foreach ($pendingPenarikan as $p) {
                $totalPenarikanGaji += (float)$p['nominal'];
                $penarikanDetails[] = [
                    'id_penarikan' => $p['id'],
                    'tanggal' => $p['tanggal'],
                    'nominal' => (float)$p['nominal'],
                    'keterangan' => $p['keterangan'],
                ];
            }

            // F. Komisi Sales
            $totalKomisiSales = 0.00;
            if ($emp['posisi'] === 'sales' && !empty($emp['pengguna_id'])) {
                $stmtOmzet = $pdo->prepare("
                    SELECT COALESCE(SUM(total_netto), 0) as omzet
                    FROM public.pesanan
                    WHERE sales_driver_id = :pengguna_id
                      AND tanggal_pesanan BETWEEN :start AND :end
                      AND status_pemrosesan IN ('selesai', 'selesai_diterima')
                ");
                $stmtOmzet->execute(['pengguna_id' => $emp['pengguna_id'], 'start' => $empStart, 'end' => $empEnd]);
                $omzetTotal = (float)$stmtOmzet->fetchColumn();

                if ($omzetTotal > 0) {
                    $tiers = $pdo->query("
                        SELECT omzet_min, omzet_maks, persentase
                        FROM public.skema_komisi_sales
                        WHERE status_aktif = TRUE
                        ORDER BY urutan ASC, omzet_min ASC
                    ")->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($tiers as $tier) {
                        if ($omzetTotal >= (float)$tier['omzet_min']) {
                            $maks = ($tier['omzet_maks'] !== null) ? (float)$tier['omzet_maks'] : PHP_FLOAT_MAX;
                            if ($omzetTotal <= $maks) {
                                $totalKomisiSales = $omzetTotal * ((float)$tier['persentase'] / 100);
                                break;
                            }
                        }
                    }
                }
            }

            // G. Kasbon Auto-Deduction
            $pendapatanBruto = $gajiPokok + $totalUangKehadiran + $totalUpahBorongan
                             + $totalUpahLembur + $tunjanganBulanan + $totalKomisiSales;

            // Jika karyawan tidak punya aktivitas atau pendapatan atau penarikan, skip jika tidak dipilih eksplisit
            if (empty($selectedEmployeeIds)) {
                if ($pendapatanBruto <= 0 && $totalPenarikanGaji <= 0 && $hariHadir === 0) {
                    continue;
                }
            }

            $maxForKasbon = max(0, $pendapatanBruto - $totalPenarikanGaji);

            $stmtKasbon = $pdo->prepare("
                SELECT id, keterangan, total_pinjaman, potongan_per_periode, sisa_pinjaman
                FROM public.kasbon
                WHERE karyawan_id = :kid AND status_kasbon = 'aktif'
                ORDER BY tanggal_pengajuan ASC
            ");
            $stmtKasbon->execute(['kid' => $emp['id']]);
            $kasbonAktif = $stmtKasbon->fetchAll(PDO::FETCH_ASSOC);

            $totalPotonganKasbon = 0.00;
            $kasbonDetails = [];
            $kasbonAdjustedDown = false;

            foreach ($kasbonAktif as $kb) {
                $cicilan = (float)$kb['potongan_per_periode'];
                $sisa    = (float)$kb['sisa_pinjaman'];
                if ($cicilan > $sisa) $cicilan = $sisa;
                if ($cicilan > $maxForKasbon) {
                    $cicilan = $maxForKasbon;
                    $kasbonAdjustedDown = true;
                }
                if ($cicilan <= 0) continue;

                $totalPotonganKasbon += $cicilan;
                $maxForKasbon -= $cicilan;
                $kasbonDetails[] = [
                    'kasbon_id'  => $kb['id'],
                    'keterangan' => $kb['keterangan'],
                    'nominal'    => $cicilan,
                ];
            }

            // H. Net Salary
            $gajiBersih = $pendapatanBruto - $totalPotonganKasbon - $totalPenarikanGaji;
            if ($gajiBersih < 0) $gajiBersih = 0;

            // I. Insert rincian_penggajian
            $rincianJson = json_encode([
                'debts' => $kasbonDetails,
                'penarikan' => $penarikanDetails,
                'kasbon_adjusted_down' => $kasbonAdjustedDown,
            ]);

            $stmtInsertRincian = $pdo->prepare("
                INSERT INTO public.rincian_penggajian (
                    penggajian_id, karyawan_id,
                    gaji_pokok, hari_hadir, total_uang_kehadiran,
                    tunjangan_bulanan, tunjangan_lain, catatan_tunjangan_lain,
                    total_upah_borongan, total_upah_lembur, total_komisi_sales,
                    total_potongan_kasbon, potongan_lain, catatan_potongan_lain,
                    nominal_pembulatan, total_potongan_tabungan, penarikan_tabungan,
                    total_penarikan_gaji,
                    is_excluded, catatan_pengecualian,
                    gaji_bersih_diterima, rincian_json, dibuat_pada
                ) VALUES (
                    :run_id, :kid,
                    :gapok, :hadir, :uang_hadir,
                    :tunjangan, 0.00, NULL,
                    :upah_borongan, :upah_lembur, :komisi,
                    :potongan_kasbon, 0.00, NULL,
                    0.00, 0.00, 0.00,
                    :penarikan,
                    FALSE, NULL,
                    :gaji_bersih, :json, NOW()
                )
            ");
            $stmtInsertRincian->execute([
                'run_id'          => $runId,
                'kid'             => $emp['id'],
                'gapok'           => $gajiPokok,
                'hadir'           => $hariHadir,
                'uang_hadir'      => $totalUangKehadiran,
                'tunjangan'       => $tunjanganBulanan,
                'upah_borongan'   => $totalUpahBorongan,
                'upah_lembur'     => $totalUpahLembur,
                'komisi'          => $totalKomisiSales,
                'potongan_kasbon' => $totalPotonganKasbon,
                'penarikan'       => $totalPenarikanGaji,
                'gaji_bersih'     => $gajiBersih,
                'json'            => $rincianJson,
            ]);

            $itemCount++;
        }

        // J. LOCK records absensi + produksi + penarikan
        if (isset($options['borongan'])) {
            $s = $options['borongan']['start'];
            $e = $options['borongan']['end'];

            $pdo->prepare("
                UPDATE public.absensi SET penggajian_id = :rid
                WHERE penggajian_id IS NULL AND tanggal BETWEEN :s AND :e
                  AND karyawan_id IN (
                      SELECT rp.karyawan_id FROM public.rincian_penggajian rp
                      JOIN public.karyawan k ON k.id = rp.karyawan_id
                      WHERE rp.penggajian_id = :rid AND rp.is_excluded = FALSE
                        AND k.tipe_penggajian = 'borongan'
                  )
            ")->execute(['rid' => $runId, 's' => $s, 'e' => $e]);

            $pdo->prepare("
                UPDATE public.produksi_harian SET penggajian_id = :rid
                WHERE penggajian_id IS NULL AND tanggal BETWEEN :s AND :e
                  AND karyawan_id IN (
                      SELECT rp.karyawan_id FROM public.rincian_penggajian rp
                      WHERE rp.penggajian_id = :rid AND rp.is_excluded = FALSE
                  )
            ")->execute(['rid' => $runId, 's' => $s, 'e' => $e]);
        }

        if (isset($options['bulanan'])) {
            $s = $options['bulanan']['start'];
            $e = $options['bulanan']['end'];

            $pdo->prepare("
                UPDATE public.absensi SET penggajian_id = :rid
                WHERE penggajian_id IS NULL AND tanggal BETWEEN :s AND :e
                  AND karyawan_id IN (
                      SELECT rp.karyawan_id FROM public.rincian_penggajian rp
                      JOIN public.karyawan k ON k.id = rp.karyawan_id
                      WHERE rp.penggajian_id = :rid AND rp.is_excluded = FALSE
                        AND k.tipe_penggajian = 'bulanan'
                  )
            ")->execute(['rid' => $runId, 's' => $s, 'e' => $e]);
        }

        // Lock penarikan_gaji
        $pdo->prepare("
            UPDATE public.penarikan_gaji SET penggajian_id = :rid
            WHERE penggajian_id IS NULL
              AND karyawan_id IN (
                  SELECT rp.karyawan_id FROM public.rincian_penggajian rp
                  WHERE rp.penggajian_id = :rid AND rp.is_excluded = FALSE
              )
        ")->execute(['rid' => $runId]);
    }

    /**
     * GET /penggajian/preview — Tinjau & Edit Line Items Payroll Run
     */
    public function preview(): void
    {
        $id = (string)$this->input('id', '');
        if (empty($id)) {
            $this->redirect('/penggajian');
            return;
        }

        try {
            $run = Database::fetchOne("
                SELECT
                    p.*,
                    pv.nama_lengkap as nama_approver
                FROM public.penggajian p
                LEFT JOIN public.pengguna pv ON pv.id = p.disetujui_oleh
                WHERE p.id = :id
            ", ['id' => $id]);

            if (!$run) {
                $this->flashError('Data payroll tidak ditemukan.');
                $this->redirect('/penggajian');
                return;
            }

            $items = Database::fetchAll("
                SELECT
                    rp.*,
                    v.nama_karyawan, v.nama_panggilan, v.posisi,
                    k.tipe_penggajian,
                    COALESCE((SELECT SUM(sisa_pinjaman) FROM public.kasbon WHERE karyawan_id = rp.karyawan_id AND status_kasbon = 'aktif'), 0) as max_kasbon_aktif,
                    COALESCE((SELECT saldo FROM public.tabungan WHERE karyawan_id = rp.karyawan_id), 0) as saldo_tabungan_saat_ini
                FROM public.rincian_penggajian rp
                JOIN public.v_karyawan_info v ON v.id = rp.karyawan_id
                JOIN public.karyawan k ON k.id = rp.karyawan_id
                WHERE rp.penggajian_id = :id
                ORDER BY
                    rp.is_excluded ASC,
                    CASE WHEN k.tipe_penggajian = 'bulanan' THEN 0 ELSE 1 END,
                    v.nama_karyawan ASC
            ", ['id' => $id]);

            // List akun kas operasional aktif (Non-Escrow) untuk modal approval payroll
            $akunKasList = Database::fetchAll("
                SELECT id, nama_akun, nomor_rekening, atas_nama, saldo_saat_ini, tipe_akun, is_default_pos
                FROM public.akun_kas
                WHERE status_aktif = TRUE AND is_escrow = FALSE
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            // Summary data
            $totalGajiBersih = 0.0;
            $totalKotor = 0.0;
            $totalPotongan = 0.0;
            $totalPotonganTabunganPayroll = 0.0;
            $totalPenarikanTabunganPayroll = 0.0;
            $includedCount = 0;
            $excludedCount = 0;

            foreach ($items as $item) {
                if ($item['is_excluded']) {
                    $excludedCount++;
                    continue;
                }
                $includedCount++;
                $kotor = (float)$item['gaji_pokok'] + (float)$item['total_uang_kehadiran']
                       + (float)$item['total_upah_borongan'] + (float)$item['total_upah_lembur']
                       + (float)$item['tunjangan_bulanan'] + (float)$item['tunjangan_lain']
                       + (float)$item['total_komisi_sales'] + (float)$item['penarikan_tabungan'];
                
                $potong = (float)$item['total_potongan_kasbon'] + (float)$item['potongan_lain']
                        + (float)$item['total_potongan_tabungan'] + (float)$item['total_penarikan_gaji'];

                $totalKotor += $kotor;
                $totalPotongan += $potong;
                $totalGajiBersih += (float)$item['gaji_bersih_diterima'];
                $totalPotonganTabunganPayroll += (float)$item['total_potongan_tabungan'];
                $totalPenarikanTabunganPayroll += (float)$item['penarikan_tabungan'];
            }

            // Check if approval can be cancelled (< 24 hours)
            $canCancelApprove = false;
            if ($run['status'] === 'disetujui' && !empty($run['disetujui_pada'])) {
                $approveTime = strtotime($run['disetujui_pada']);
                if (time() - $approveTime <= 86400) {
                    $canCancelApprove = true;
                }
            }

            $this->view('penggajian.preview', [
                'pageTitle' => 'Preview ' . ($run['nama_payroll'] ?: $run['nomor_referensi']),
                'pageSubtitle' => 'Pemeriksaan Rincian Gaji, Edit Koreksi, & Otorisasi Payroll',
                'run' => $run,
                'items' => $items,
                'akunKasList' => $akunKasList,
                'totalGajiBersih' => $totalGajiBersih,
                'totalKotor' => $totalKotor,
                'totalPotongan' => $totalPotongan,
                'totalPotonganTabunganPayroll' => $totalPotonganTabunganPayroll,
                'totalPenarikanTabunganPayroll' => $totalPenarikanTabunganPayroll,
                'totalPotonganTabunganAll' => $totalPotonganTabunganPayroll,
                'totalPenarikanTabunganAll' => $totalPenarikanTabunganPayroll,
                'includedCount' => $includedCount,
                'excludedCount' => $excludedCount,
                'canCancelApprove' => $canCancelApprove
            ], 'layouts.master');
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat preview penggajian: ' . $e->getMessage());
            $this->redirect('/penggajian');
        }
    }

    /**
     * POST /penggajian/update-item — Edit Line Item Rincian Penggajian
     */
    public function updateItem(): void
    {
        Auth::requirePermission('hr.payroll_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/penggajian');
            return;
        }

        $itemId = (string)$this->input('item_id', '');
        $runId = (string)$this->input('run_id', '');

        if (empty($itemId) || empty($runId)) {
            $this->flashError('ID data tidak lengkap.');
            $this->redirectBack('/penggajian');
            return;
        }

        try {
            $run = Database::fetchOne("SELECT status FROM public.penggajian WHERE id = :id", ['id' => $runId]);
            if (!$run || $run['status'] !== 'draf') {
                $this->flashError('Data payroll hanya dapat diedit saat status DRAF.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            $item = Database::fetchOne("
                SELECT rp.*,
                       COALESCE((SELECT SUM(sisa_pinjaman) FROM public.kasbon WHERE karyawan_id = rp.karyawan_id AND status_kasbon = 'aktif'), 0) as sisa_kasbon,
                       COALESCE((SELECT saldo FROM public.tabungan WHERE karyawan_id = rp.karyawan_id), 0) as saldo_tabungan
                FROM public.rincian_penggajian rp
                WHERE rp.id = :id AND rp.penggajian_id = :rid
            ", ['id' => $itemId, 'rid' => $runId]);

            if (!$item) {
                $this->flashError('Line item karyawan tidak ditemukan.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            $tunjanganLain = (float)preg_replace('/[^0-9]/', '', (string)$this->input('tunjangan_lain', '0'));
            $catatanTunjanganLain = trim((string)$this->input('catatan_tunjangan_lain', ''));

            $potonganLain = (float)preg_replace('/[^0-9]/', '', (string)$this->input('potongan_lain', '0'));
            $catatanPotonganLain = trim((string)$this->input('catatan_potongan_lain', ''));

            $potonganKasbon = (float)preg_replace('/[^0-9]/', '', (string)$this->input('total_potongan_kasbon', '0'));
            $potonganTabungan = (float)preg_replace('/[^0-9]/', '', (string)$this->input('total_potongan_tabungan', '0'));
            $penarikanTabungan = (float)preg_replace('/[^0-9]/', '', (string)$this->input('penarikan_tabungan', '0'));
            $pembulatan = (float)str_replace(['.', ','], ['', '.'], (string)$this->input('nominal_pembulatan', '0'));

            // Validations
            $saldoTabungan = (float)$item['saldo_tabungan'];
            $sisaKasbon = (float)$item['sisa_kasbon'];

            if ($penarikanTabungan > $saldoTabungan) {
                $this->flashError('Penarikan tabungan melebihi saldo karyawan saat ini (' . Format::rupiah($saldoTabungan) . ').');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            if ($potonganKasbon > $sisaKasbon) {
                $potonganKasbon = $sisaKasbon;
            }

            // Recalculate Net
            $gajiPokok = (float)$item['gaji_pokok'];
            $uangHadir = (float)$item['total_uang_kehadiran'];
            $upahBorongan = (float)$item['total_upah_borongan'];
            $upahLembur = (float)$item['total_upah_lembur'];
            $tunjanganBulanan = (float)$item['tunjangan_bulanan'];
            $komisiSales = (float)$item['total_komisi_sales'];
            $penarikanGaji = (float)$item['total_penarikan_gaji'];

            $pendapatanTotal = $gajiPokok + $uangHadir + $upahBorongan + $upahLembur
                             + $tunjanganBulanan + $tunjanganLain + $komisiSales + $penarikanTabungan;

            $potonganTotal = $potonganKasbon + $potonganLain + $potonganTabungan + $penarikanGaji;

            $gajiBersih = $pendapatanTotal - $potonganTotal + $pembulatan;
            if ($gajiBersih < 0) $gajiBersih = 0;

            $pdo = Database::getConnection();
            $stmtUpdate = $pdo->prepare("
                UPDATE public.rincian_penggajian
                SET tunjangan_lain = :tunj_lain,
                    catatan_tunjangan_lain = :cat_tunj,
                    potongan_lain = :pot_lain,
                    catatan_potongan_lain = :cat_pot,
                    total_potongan_kasbon = :pot_kasbon,
                    total_potongan_tabungan = :pot_tab,
                    penarikan_tabungan = :tarik_tab,
                    nominal_pembulatan = :bulat,
                    gaji_bersih_diterima = :net
                WHERE id = :id AND penggajian_id = :rid
            ");
            $stmtUpdate->execute([
                'tunj_lain' => $tunjanganLain,
                'cat_tunj' => !empty($catatanTunjanganLain) ? $catatanTunjanganLain : null,
                'pot_lain' => $potonganLain,
                'cat_pot' => !empty($catatanPotonganLain) ? $catatanPotonganLain : null,
                'pot_kasbon' => $potonganKasbon,
                'pot_tab' => $potonganTabungan,
                'tarik_tab' => $penarikanTabungan,
                'bulat' => $pembulatan,
                'net' => $gajiBersih,
                'id' => $itemId,
                'rid' => $runId
            ]);

            // Update header total
            $stmtSum = $pdo->prepare("SELECT COALESCE(SUM(gaji_bersih_diterima), 0) FROM public.rincian_penggajian WHERE penggajian_id = :rid AND is_excluded = FALSE");
            $stmtSum->execute(['rid' => $runId]);
            $newTotal = (float)$stmtSum->fetchColumn();

            $pdo->prepare("UPDATE public.penggajian SET total_gaji_dikeluarkan = :total WHERE id = :rid")->execute(['total' => $newTotal, 'rid' => $runId]);

            $this->flashSuccess('Rincian komponen gaji berhasil diperbarui.');
            $this->redirect('/penggajian/preview?id=' . $runId);
        } catch (Throwable $e) {
            $this->flashError('Gagal mengupdate item penggajian: ' . $e->getMessage());
            $this->redirect('/penggajian/preview?id=' . $runId);
        }
    }

    /**
     * POST /penggajian/toggle-exclude — Kecualikan / Sertakan Karyawan dari Run Ini
     */
    public function toggleExclude(): void
    {
        Auth::requirePermission('hr.payroll_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/penggajian');
            return;
        }

        $itemId = (string)$this->input('item_id', '');
        $runId = (string)$this->input('run_id', '');
        $catatan = trim((string)$this->input('catatan_pengecualian', ''));

        if (empty($itemId) || empty($runId)) {
            $this->flashError('Parameter tidak valid.');
            $this->redirectBack('/penggajian');
            return;
        }

        try {
            $item = Database::fetchOne("SELECT is_excluded, karyawan_id FROM public.rincian_penggajian WHERE id = :id AND penggajian_id = :rid", ['id' => $itemId, 'rid' => $runId]);
            if (!$item) {
                $this->flashError('Data line item tidak ditemukan.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            $newExcluded = !$item['is_excluded'];
            $pdo = Database::getConnection();

            $pdo->prepare("
                UPDATE public.rincian_penggajian
                SET is_excluded = :exc,
                    catatan_pengecualian = :cat
                WHERE id = :id
            ")->execute([
                'exc' => $newExcluded ? 'true' : 'false',
                'cat' => $newExcluded ? ($catatan ?: 'Dikecualikan manual oleh admin') : null,
                'id' => $itemId
            ]);

            // If excluded -> unlock records for this employee
            if ($newExcluded) {
                $pdo->prepare("UPDATE public.absensi SET penggajian_id = NULL WHERE penggajian_id = :rid AND karyawan_id = :kid")->execute(['rid' => $runId, 'kid' => $item['karyawan_id']]);
                $pdo->prepare("UPDATE public.produksi_harian SET penggajian_id = NULL WHERE penggajian_id = :rid AND karyawan_id = :kid")->execute(['rid' => $runId, 'kid' => $item['karyawan_id']]);
                $pdo->prepare("UPDATE public.penarikan_gaji SET penggajian_id = NULL WHERE penggajian_id = :rid AND karyawan_id = :kid")->execute(['rid' => $runId, 'kid' => $item['karyawan_id']]);
            } else {
                // Relock records for this employee
                $run = Database::fetchOne("SELECT options_json FROM public.penggajian WHERE id = :rid", ['rid' => $runId]);
                $options = json_decode((string)$run['options_json'], true);
                if (is_array($options)) {
                    if (isset($options['borongan'])) {
                        $pdo->prepare("UPDATE public.absensi SET penggajian_id = :rid WHERE penggajian_id IS NULL AND tanggal BETWEEN :s AND :e AND karyawan_id = :kid")->execute(['rid' => $runId, 's' => $options['borongan']['start'], 'e' => $options['borongan']['end'], 'kid' => $item['karyawan_id']]);
                        $pdo->prepare("UPDATE public.produksi_harian SET penggajian_id = :rid WHERE penggajian_id IS NULL AND tanggal BETWEEN :s AND :e AND karyawan_id = :kid")->execute(['rid' => $runId, 's' => $options['borongan']['start'], 'e' => $options['borongan']['end'], 'kid' => $item['karyawan_id']]);
                    }
                    if (isset($options['bulanan'])) {
                        $pdo->prepare("UPDATE public.absensi SET penggajian_id = :rid WHERE penggajian_id IS NULL AND tanggal BETWEEN :s AND :e AND karyawan_id = :kid")->execute(['rid' => $runId, 's' => $options['bulanan']['start'], 'e' => $options['bulanan']['end'], 'kid' => $item['karyawan_id']]);
                    }
                    $pdo->prepare("UPDATE public.penarikan_gaji SET penggajian_id = :rid WHERE penggajian_id IS NULL AND karyawan_id = :kid")->execute(['rid' => $runId, 'kid' => $item['karyawan_id']]);
                }
            }

            // Update header total
            $stmtSum = $pdo->prepare("SELECT COALESCE(SUM(gaji_bersih_diterima), 0) FROM public.rincian_penggajian WHERE penggajian_id = :rid AND is_excluded = FALSE");
            $stmtSum->execute(['rid' => $runId]);
            $newTotal = (float)$stmtSum->fetchColumn();

            $pdo->prepare("UPDATE public.penggajian SET total_gaji_dikeluarkan = :total WHERE id = :rid")->execute(['total' => $newTotal, 'rid' => $runId]);

            $this->flashSuccess($newExcluded ? 'Karyawan berhasil dikecualikan dari payroll ini.' : 'Karyawan berhasil disertakan kembali ke payroll.');
            $this->redirect('/penggajian/preview?id=' . $runId);
        } catch (Throwable $e) {
            $this->flashError('Gagal mengubah status pengecualian: ' . $e->getMessage());
            $this->redirect('/penggajian/preview?id=' . $runId);
        }
    }

    /**
     * POST /penggajian/approve — Otorisasi & Eksekusi Pembayaran Payroll (Atomic Ledger)
     */
    public function approve(): void
    {
        Auth::requirePermission('hr.payroll_approve');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/penggajian');
            return;
        }

        $runId = (string)$this->input('penggajian_id', '');
        $akunKasId = (string)$this->input('akun_kas_id', '');

        if (empty($runId) || empty($akunKasId)) {
            $this->flashError('Harap pilih akun kas untuk pencatatan arus kas pengeluaran gaji.');
            $this->redirectBack('/penggajian');
            return;
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $stmtRun = $pdo->prepare("
                SELECT id, nomor_referensi, nama_payroll, status, total_gaji_dikeluarkan
                FROM public.penggajian
                WHERE id = :id FOR UPDATE
            ");
            $stmtRun->execute(['id' => $runId]);
            $run = $stmtRun->fetch(PDO::FETCH_ASSOC);

            if (!$run) {
                $pdo->rollBack();
                $this->flashError('Data payroll tidak ditemukan.');
                $this->redirect('/penggajian');
                return;
            }

            if ($run['status'] !== 'draf') {
                $pdo->rollBack();
                $this->flashError('Payroll ini sudah pernah disetujui atau diproses.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            // Hitung total final payroll dan tabungan
            $stmtTotals = $pdo->prepare("
                SELECT 
                    COALESCE(SUM(gaji_bersih_diterima), 0) AS total_gaji,
                    COALESCE(SUM(total_potongan_tabungan), 0) AS total_potongan_tabungan,
                    COALESCE(SUM(penarikan_tabungan), 0) AS total_penarikan_tabungan
                FROM public.rincian_penggajian 
                WHERE penggajian_id = :rid AND is_excluded = FALSE
            ");
            $stmtTotals->execute(['rid' => $runId]);
            $totalsRow = $stmtTotals->fetch(PDO::FETCH_ASSOC);

            $totalGaji = (float)($totalsRow['total_gaji'] ?? 0);
            $totalPotonganTabunganAll = (float)($totalsRow['total_potongan_tabungan'] ?? 0);
            $totalPenarikanTabunganAll = (float)($totalsRow['total_penarikan_tabungan'] ?? 0);

            // Cek saldo akun kas operasional / payroll
            $stmtKas = $pdo->prepare("SELECT id, nama_akun, saldo_saat_ini, is_escrow FROM public.akun_kas WHERE id = :id FOR UPDATE");
            $stmtKas->execute(['id' => $akunKasId]);
            $akunKas = $stmtKas->fetch(PDO::FETCH_ASSOC);

            if (!$akunKas) {
                $pdo->rollBack();
                $this->flashError('Akun kas terpilih tidak valid.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            if (!empty($akunKas['is_escrow'])) {
                $pdo->rollBack();
                $this->flashError('Akun kas escrow / tabungan karyawan tidak boleh digunakan sebagai sumber pembayaran gaji operasional.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            $currentSaldoKas = (float)$akunKas['saldo_saat_ini'];
            $totalKebutuhanKas = $totalGaji + $totalPotonganTabunganAll;
            if ($totalKebutuhanKas > $currentSaldoKas) {
                $pdo->rollBack();
                $this->flashError("Saldo akun kas '{$akunKas['nama_akun']}' (" . Format::rupiah($currentSaldoKas) . ") tidak mencukupi untuk pembayaran payroll (" . Format::rupiah($totalKebutuhanKas) . ").");
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            // Ambil akun kas escrow tabungan karyawan jika ada tabungan
            $stmtEscrow = $pdo->query("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE is_escrow = TRUE AND status = 'aktif' LIMIT 1");
            $escrowAccount = $stmtEscrow->fetch(PDO::FETCH_ASSOC);
            $escrowKasId = $escrowAccount['id'] ?? null;

            if ($totalPenarikanTabunganAll > 0) {
                if (!$escrowAccount || (float)$escrowAccount['saldo_saat_ini'] < $totalPenarikanTabunganAll) {
                    $saldoEscrow = (float)($escrowAccount['saldo_saat_ini'] ?? 0);
                    $pdo->rollBack();
                    $this->flashError("Saldo kas tabungan karyawan (" . Format::rupiah($saldoEscrow) . ") tidak mencukupi untuk pencairan tabungan karyawan via payroll (" . Format::rupiah($totalPenarikanTabunganAll) . ").");
                    $this->redirect('/penggajian/preview?id=' . $runId);
                    return;
                }
            }

            $userId = Auth::user()['id'] ?? null;

            // 1. Update Status Penggajian
            $pdo->prepare("
                UPDATE public.penggajian
                SET status = 'disetujui',
                    disetujui_oleh = :uid,
                    disetujui_pada = NOW(),
                    total_gaji_dikeluarkan = :total,
                    diubah_pada = NOW()
                WHERE id = :rid
            ")->execute([
                'uid' => $userId,
                'total' => $totalGaji,
                'rid' => $runId
            ]);

            // 2. Ambil semua line items included
            $stmtItems = $pdo->prepare("
                SELECT id, karyawan_id, total_potongan_kasbon, total_potongan_tabungan, penarikan_tabungan, rincian_json
                FROM public.rincian_penggajian
                WHERE penggajian_id = :rid AND is_excluded = FALSE
            ");
            $stmtItems->execute(['rid' => $runId]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $kid = $item['karyawan_id'];
                $rincianId = $item['id'];
                $details = json_decode((string)$item['rincian_json'], true);

                // 2a. Eksekusi Potongan Kasbon
                if ((float)$item['total_potongan_kasbon'] > 0) {
                    $debts = $details['debts'] ?? [];
                    if (!empty($debts)) {
                        foreach ($debts as $debt) {
                            $kbId = $debt['kasbon_id'];
                            $nominalPotong = (float)$debt['nominal'];
                            if ($nominalPotong <= 0) continue;

                            // Insert potongan_kasbon (DB trigger trg_potongan_kasbon_update_saldo auto-reduces sisa_pinjaman)
                            $pdo->prepare("
                                INSERT INTO public.potongan_kasbon (
                                    kasbon_id, rincian_penggajian_id, akun_kas_id, tanggal, nominal, tipe_potongan, keterangan, dibuat_pada
                                ) VALUES (
                                    :kbid, :rpid, :kas_id, CURRENT_DATE, :nom, 'payroll', :ket, NOW()
                                )
                            ")->execute([
                                'kbid' => $kbId,
                                'rpid' => $rincianId,
                                'kas_id' => $akunKasId,
                                'nom' => $nominalPotong,
                                'ket' => 'Potongan Payroll ' . $run['nomor_referensi']
                            ]);
                        }
                    } else {
                        // Fallback manual potongan kasbon jika debts array kosong tapi total > 0
                        $stmtActiveKb = $pdo->prepare("SELECT id, sisa_pinjaman FROM public.kasbon WHERE karyawan_id = :kid AND status_kasbon = 'aktif' ORDER BY tanggal_pengajuan ASC");
                        $stmtActiveKb->execute(['kid' => $kid]);
                        $rem = (float)$item['total_potongan_kasbon'];
                        while ($rowKb = $stmtActiveKb->fetch(PDO::FETCH_ASSOC)) {
                            if ($rem <= 0) break;
                            $cut = min($rem, (float)$rowKb['sisa_pinjaman']);
                            $pdo->prepare("
                                INSERT INTO public.potongan_kasbon (
                                    kasbon_id, rincian_penggajian_id, akun_kas_id, tanggal, nominal, tipe_potongan, keterangan, dibuat_pada
                                ) VALUES (
                                    :kbid, :rpid, :kas_id, CURRENT_DATE, :nom, 'payroll', :ket, NOW()
                                )
                            ")->execute([
                                'kbid' => $rowKb['id'],
                                'rpid' => $rincianId,
                                'kas_id' => $akunKasId,
                                'nom' => $cut,
                                'ket' => 'Potongan Payroll ' . $run['nomor_referensi']
                            ]);
                            $rem -= $cut;
                        }
                    }
                }

                // 2b. Eksekusi Tabungan (Setoran via Payroll)
                if ((float)$item['total_potongan_tabungan'] > 0) {
                    $setorNominal = (float)$item['total_potongan_tabungan'];
                    // Ensure tabungan record exists
                    $pdo->prepare("INSERT INTO public.tabungan (karyawan_id, saldo) VALUES (:kid, 0.00) ON CONFLICT (karyawan_id) DO NOTHING")->execute(['kid' => $kid]);
                    $stmtTab = $pdo->prepare("SELECT id FROM public.tabungan WHERE karyawan_id = :kid");
                    $stmtTab->execute(['kid' => $kid]);
                    $tid = $stmtTab->fetchColumn();

                    // Insert transaksi_tabungan (DB trigger trg_transaksi_tabungan_update_saldo auto-adds to saldo)
                    $pdo->prepare("
                        INSERT INTO public.transaksi_tabungan (
                            tabungan_id, karyawan_id, rincian_penggajian_id, akun_kas_id, tanggal, tipe, jumlah, sumber, keterangan, dibuat_pada
                        ) VALUES (
                            :tid, :kid, :rpid, :kas_id, CURRENT_DATE, 'deposit', :jml, 'payroll', :ket, NOW()
                        )
                    ")->execute([
                        'tid' => $tid,
                        'kid' => $kid,
                        'rpid' => $rincianId,
                        'kas_id' => $escrowKasId,
                        'jml' => $setorNominal,
                        'ket' => 'Setoran via Payroll ' . $run['nomor_referensi']
                    ]);
                }

                // 2c. Eksekusi Penarikan Tabungan (Disbursed via Payroll)
                if ((float)$item['penarikan_tabungan'] > 0) {
                    $tarikNominal = (float)$item['penarikan_tabungan'];
                    $stmtTab = $pdo->prepare("SELECT id FROM public.tabungan WHERE karyawan_id = :kid");
                    $stmtTab->execute(['kid' => $kid]);
                    $tid = $stmtTab->fetchColumn();

                    if ($tid) {
                        // Insert transaksi_tabungan (DB trigger trg_transaksi_tabungan_update_saldo auto-deducts saldo)
                        $pdo->prepare("
                            INSERT INTO public.transaksi_tabungan (
                                tabungan_id, karyawan_id, rincian_penggajian_id, akun_kas_id, tanggal, tipe, jumlah, sumber, keterangan, dibuat_pada
                            ) VALUES (
                                :tid, :kid, :rpid, :kas_id, CURRENT_DATE, 'withdrawal', :jml, 'payroll', :ket, NOW()
                            )
                        ")->execute([
                            'tid' => $tid,
                            'kid' => $kid,
                            'rpid' => $rincianId,
                            'kas_id' => $escrowKasId,
                            'jml' => $tarikNominal,
                            'ket' => 'Pencairan Tabungan via Payroll ' . $run['nomor_referensi']
                        ]);
                    }
                }
            }

            // 3. Catat ke Arus Kas — Pembayaran Gaji Bersih
            $saldoBerjalan = $currentSaldoKas - $totalGaji;
            $pdo->prepare("
                INSERT INTO public.arus_kas (
                    akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                    nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :kas_id, CURRENT_DATE, 'keluar', 'pembayaran_payroll',
                    :nom, :ket, 'penggajian', :rid, :saldo_berjalan, :uid, NOW()
                )
            ")->execute([
                'kas_id' => $akunKasId,
                'nom' => $totalGaji,
                'ket' => 'Pembayaran Gaji ' . $run['nama_payroll'] . ' (' . $run['nomor_referensi'] . ')',
                'rid' => $runId,
                'saldo_berjalan' => $saldoBerjalan,
                'uid' => $userId
            ]);

            // Kurangi Saldo Akun Kas untuk Pembayaran Gaji
            $pdo->prepare("
                UPDATE public.akun_kas 
                SET saldo_saat_ini = saldo_saat_ini - :total, diubah_pada = NOW() 
                WHERE id = :id AND saldo_saat_ini >= :total
            ")->execute([
                'total' => $totalGaji,
                'id' => $akunKasId
            ]);
            $currentSaldoKas = $saldoBerjalan;

            // 4. Auto-transfer Potongan Tabungan ke Akun Kas Tabungan (Escrow)
            if ($totalPotonganTabunganAll > 0 && $escrowKasId) {
                // Outflow dari akun kas operasional
                $currentSaldoKas -= $totalPotonganTabunganAll;
                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                        nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :kas_id, CURRENT_DATE, 'transfer_keluar', 'transfer_keluar',
                        :nom, :ket, 'penggajian', :rid, :saldo_berjalan, :uid, NOW()
                    )
                ")->execute([
                    'kas_id' => $akunKasId,
                    'nom' => $totalPotonganTabunganAll,
                    'ket' => 'Transfer Potongan Tabungan Payroll ' . $run['nomor_referensi'] . ' ke ' . $escrowAccount['nama_akun'],
                    'rid' => $runId,
                    'saldo_berjalan' => $currentSaldoKas,
                    'uid' => $userId
                ]);

                $pdo->prepare("
                    UPDATE public.akun_kas 
                    SET saldo_saat_ini = saldo_saat_ini - :total, diubah_pada = NOW() 
                    WHERE id = :id
                ")->execute(['total' => $totalPotonganTabunganAll, 'id' => $akunKasId]);

                // Inflow ke akun kas escrow tabungan
                $stmtEscrowLock = $pdo->prepare("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE");
                $stmtEscrowLock->execute(['id' => $escrowKasId]);
                $escrowSaldo = (float)$stmtEscrowLock->fetchColumn();
                $escrowSaldoBerjalan = $escrowSaldo + $totalPotonganTabunganAll;

                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                        nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :kas_id, CURRENT_DATE, 'transfer_masuk', 'transfer_masuk',
                        :nom, :ket, 'penggajian', :rid, :saldo_berjalan, :uid, NOW()
                    )
                ")->execute([
                    'kas_id' => $escrowKasId,
                    'nom' => $totalPotonganTabunganAll,
                    'ket' => 'Penerimaan Potongan Tabungan Payroll ' . $run['nomor_referensi'] . ' dari ' . $akunKas['nama_akun'],
                    'rid' => $runId,
                    'saldo_berjalan' => $escrowSaldoBerjalan,
                    'uid' => $userId
                ]);

                $pdo->prepare("
                    UPDATE public.akun_kas 
                    SET saldo_saat_ini = saldo_saat_ini + :total, diubah_pada = NOW() 
                    WHERE id = :id
                ")->execute(['total' => $totalPotonganTabunganAll, 'id' => $escrowKasId]);
            }

            // 5. Auto-reimburse Penarikan Tabungan dari Kas Tabungan (Escrow) ke Kas Payroll
            if ($totalPenarikanTabunganAll > 0 && $escrowKasId) {
                // Outflow dari kas tabungan escrow
                $stmtEscrowLock = $pdo->prepare("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE");
                $stmtEscrowLock->execute(['id' => $escrowKasId]);
                $escrowSaldo = (float)$stmtEscrowLock->fetchColumn();
                $escrowSaldoBerjalan = $escrowSaldo - $totalPenarikanTabunganAll;

                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                        nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :kas_id, CURRENT_DATE, 'transfer_keluar', 'transfer_keluar',
                        :nom, :ket, 'penggajian', :rid, :saldo_berjalan, :uid, NOW()
                    )
                ")->execute([
                    'kas_id' => $escrowKasId,
                    'nom' => $totalPenarikanTabunganAll,
                    'ket' => 'Pencairan Tabungan Payroll ' . $run['nomor_referensi'] . ' untuk ' . $akunKas['nama_akun'],
                    'rid' => $runId,
                    'saldo_berjalan' => $escrowSaldoBerjalan,
                    'uid' => $userId
                ]);

                $pdo->prepare("
                    UPDATE public.akun_kas 
                    SET saldo_saat_ini = saldo_saat_ini - :total, diubah_pada = NOW() 
                    WHERE id = :id
                ")->execute(['total' => $totalPenarikanTabunganAll, 'id' => $escrowKasId]);

                // Inflow ke kas operasional/payroll (reimbursement)
                $currentSaldoKas += $totalPenarikanTabunganAll;
                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                        nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :kas_id, CURRENT_DATE, 'transfer_masuk', 'transfer_masuk',
                        :nom, :ket, 'penggajian', :rid, :saldo_berjalan, :uid, NOW()
                    )
                ")->execute([
                    'kas_id' => $akunKasId,
                    'nom' => $totalPenarikanTabunganAll,
                    'ket' => 'Reimbursement Pencairan Tabungan Payroll ' . $run['nomor_referensi'] . ' dari ' . $escrowAccount['nama_akun'],
                    'rid' => $runId,
                    'saldo_berjalan' => $currentSaldoKas,
                    'uid' => $userId
                ]);

                $pdo->prepare("
                    UPDATE public.akun_kas 
                    SET saldo_saat_ini = saldo_saat_ini + :total, diubah_pada = NOW() 
                    WHERE id = :id
                ")->execute(['total' => $totalPenarikanTabunganAll, 'id' => $akunKasId]);
            }

            $pdo->commit();

            ActivityLog::log(
                'hr_payroll',
                'APPROVE_PAYROLL',
                "Menyetujui dan membayarkan payroll {$run['nomor_referensi']} senilai " . Format::rupiah($totalGaji) . " via {$akunKas['nama_akun']}.",
                'penggajian',
                $runId
            );

            $this->flashSuccess("Payroll {$run['nomor_referensi']} berhasil DISETUJUI dan dicatat ke Arus Kas.");
            $this->redirect('/penggajian/preview?id=' . $runId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menyetujui penggajian: ' . $e->getMessage());
            $this->redirect('/penggajian/preview?id=' . $runId);
        }
    }

    /**
     * POST /penggajian/cancel-approve — Rollback Approval (Batas 24 Jam)
     */
    public function cancelApprove(): void
    {
        Auth::requirePermission('hr.payroll_approve');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/penggajian');
            return;
        }

        $runId = (string)$this->input('penggajian_id', '');
        if (empty($runId)) {
            $this->flashError('ID payroll tidak valid.');
            $this->redirect('/penggajian');
            return;
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $stmtRun = $pdo->prepare("SELECT * FROM public.penggajian WHERE id = :id FOR UPDATE");
            $stmtRun->execute(['id' => $runId]);
            $run = $stmtRun->fetch(PDO::FETCH_ASSOC);

            if (!$run || $run['status'] !== 'disetujui') {
                $pdo->rollBack();
                $this->flashError('Hanya payroll dengan status DISETUJUI yang dapat dibatalkan.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            // Check 24 hour window
            $approveTime = strtotime((string)$run['disetujui_pada']);
            if (time() - $approveTime > 86400) {
                $pdo->rollBack();
                $this->flashError('Pembatalan persetujuan payroll hanya diperbolehkan dalam kurun waktu maksimal 24 jam setelah disetujui.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            // 1. Revert Potongan Kasbon
            $stmtPk = $pdo->prepare("
                SELECT pk.id, pk.kasbon_id, pk.nominal
                FROM public.potongan_kasbon pk
                JOIN public.rincian_penggajian rp ON rp.id = pk.rincian_penggajian_id
                WHERE rp.penggajian_id = :rid
            ");
            $stmtPk->execute(['rid' => $runId]);
            $potonganList = $stmtPk->fetchAll(PDO::FETCH_ASSOC);

            foreach ($potonganList as $pk) {
                $pdo->prepare("
                    UPDATE public.kasbon 
                    SET sisa_pinjaman = sisa_pinjaman + :nom, status_kasbon = 'aktif', diubah_pada = NOW() 
                    WHERE id = :kbid
                ")->execute(['nom' => $pk['nominal'], 'kbid' => $pk['kasbon_id']]);

                // Buka proteksi rincian_penggajian_id terlebih dahulu agar diizinkan oleh trg_guard_locked_hr_potongan_kasbon
                $pdo->prepare("UPDATE public.potongan_kasbon SET rincian_penggajian_id = NULL WHERE id = :pkid")->execute(['pkid' => $pk['id']]);
                $pdo->prepare("DELETE FROM public.potongan_kasbon WHERE id = :pkid")->execute(['pkid' => $pk['id']]);
            }

            // 2. Revert Transaksi Tabungan
            $stmtTb = $pdo->prepare("
                SELECT tt.id, tt.tabungan_id, tt.tipe, tt.jumlah
                FROM public.transaksi_tabungan tt
                JOIN public.rincian_penggajian rp ON rp.id = tt.rincian_penggajian_id
                WHERE rp.penggajian_id = :rid
            ");
            $stmtTb->execute(['rid' => $runId]);
            $tabunganList = $stmtTb->fetchAll(PDO::FETCH_ASSOC);

            foreach ($tabunganList as $tb) {
                $delta = ($tb['tipe'] === 'deposit') ? -((float)$tb['jumlah']) : ((float)$tb['jumlah']);
                $pdo->prepare("UPDATE public.tabungan SET saldo = saldo + :delta, diubah_pada = NOW() WHERE id = :tid")->execute(['delta' => $delta, 'tid' => $tb['tabungan_id']]);

                // Buka proteksi rincian_penggajian_id terlebih dahulu agar diizinkan oleh trg_guard_locked_hr_transaksi_tabungan
                $pdo->prepare("UPDATE public.transaksi_tabungan SET rincian_penggajian_id = NULL WHERE id = :ttid")->execute(['ttid' => $tb['id']]);
                $pdo->prepare("DELETE FROM public.transaksi_tabungan WHERE id = :ttid")->execute(['ttid' => $tb['id']]);
            }

            // 3. Revert Arus Kas & Saldo Akun Kas (Mendukung Multi-Row Escrow Transfers)
            $stmtArus = $pdo->prepare("SELECT id, akun_kas_id, nominal, jenis_kas FROM public.arus_kas WHERE referensi_tabel = 'penggajian' AND referensi_id = :rid ORDER BY id DESC");
            $stmtArus->execute(['rid' => $runId]);
            $arusRows = $stmtArus->fetchAll(PDO::FETCH_ASSOC);

            foreach ($arusRows as $arusRow) {
                if ($arusRow['jenis_kas'] === 'keluar' || $arusRow['jenis_kas'] === 'transfer_keluar') {
                    // Dana keluar dikembalikan ke kas asal
                    $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + :nom, diubah_pada = NOW() WHERE id = :kid")
                        ->execute(['nom' => $arusRow['nominal'], 'kid' => $arusRow['akun_kas_id']]);
                } elseif ($arusRow['jenis_kas'] === 'masuk' || $arusRow['jenis_kas'] === 'transfer_masuk') {
                    // Dana masuk ditarik kembali dari kas tujuan
                    $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :nom, diubah_pada = NOW() WHERE id = :kid")
                        ->execute(['nom' => $arusRow['nominal'], 'kid' => $arusRow['akun_kas_id']]);
                }
                $pdo->prepare("DELETE FROM public.arus_kas WHERE id = :aid")->execute(['aid' => $arusRow['id']]);
            }

            // 4. Reset Status Penggajian ke Draf
            $pdo->prepare("
                UPDATE public.penggajian
                SET status = 'draf',
                    disetujui_oleh = NULL,
                    disetujui_pada = NULL,
                    diubah_pada = NOW()
                WHERE id = :rid
            ")->execute(['rid' => $runId]);

            $pdo->commit();

            ActivityLog::log(
                'hr_payroll',
                'CANCEL_APPROVE_PAYROLL',
                "Membatalkan persetujuan payroll {$run['nomor_referensi']} dan mengembalikan saldo kasbon, tabungan, serta arus kas ke posisi draf.",
                'penggajian',
                $runId
            );

            $this->flashSuccess("Persetujuan payroll {$run['nomor_referensi']} berhasil dibatalkan. Status dikembalikan ke DRAF.");
            $this->redirect('/penggajian/preview?id=' . $runId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal membatalkan approval payroll: ' . $e->getMessage());
            $this->redirect('/penggajian/preview?id=' . $runId);
        }
    }

    /**
     * POST /penggajian/delete — Hapus Draf Payroll
     */
    public function deleteDraft(): void
    {
        Auth::requirePermission('hr.payroll_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/penggajian');
            return;
        }

        $runId = (string)$this->input('penggajian_id', '');
        if (empty($runId)) {
            $this->flashError('ID payroll tidak valid.');
            $this->redirect('/penggajian');
            return;
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $run = Database::fetchOne("SELECT nomor_referensi, status FROM public.penggajian WHERE id = :id FOR UPDATE", ['id' => $runId]);
            if (!$run || $run['status'] !== 'draf') {
                $pdo->rollBack();
                $this->flashError('Hanya payroll berstatus DRAF yang dapat dihapus.');
                $this->redirect('/penggajian');
                return;
            }

            // Unlock absensi, produksi_harian, penarikan_gaji
            $pdo->prepare("UPDATE public.absensi SET penggajian_id = NULL WHERE penggajian_id = :rid")->execute(['rid' => $runId]);
            $pdo->prepare("UPDATE public.produksi_harian SET penggajian_id = NULL WHERE penggajian_id = :rid")->execute(['rid' => $runId]);
            $pdo->prepare("UPDATE public.penarikan_gaji SET penggajian_id = NULL WHERE penggajian_id = :rid")->execute(['rid' => $runId]);

            // Hapus rincian & header
            $pdo->prepare("DELETE FROM public.rincian_penggajian WHERE penggajian_id = :rid")->execute(['rid' => $runId]);
            $pdo->prepare("DELETE FROM public.penggajian WHERE id = :rid")->execute(['rid' => $runId]);

            $pdo->commit();

            ActivityLog::log(
                'hr_payroll',
                'DELETE_PAYROLL_DRAFT',
                "Menghapus draf penggajian {$run['nomor_referensi']} dan membuka kembali lock absensi/produksi/penarikan.",
                'penggajian',
                $runId
            );

            $this->flashSuccess("Draf payroll {$run['nomor_referensi']} berhasil dihapus.");
            $this->redirect('/penggajian');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menghapus draf payroll: ' . $e->getMessage());
            $this->redirect('/penggajian');
        }
    }

    /**
     * POST /penggajian/regenerate — Regenerasi Ulang Data Draf Payroll
     */
    public function regenerate(): void
    {
        Auth::requirePermission('hr.payroll_manage');

        if (!CSRF::verify()) {
            $this->flashError('Token keamanan tidak valid.');
            $this->redirectBack('/penggajian');
            return;
        }

        $runId = (string)$this->input('penggajian_id', '');
        if (empty($runId)) {
            $this->flashError('ID payroll tidak valid.');
            $this->redirect('/penggajian');
            return;
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $run = Database::fetchOne("SELECT * FROM public.penggajian WHERE id = :id FOR UPDATE", ['id' => $runId]);
            if (!$run || $run['status'] !== 'draf') {
                $pdo->rollBack();
                $this->flashError('Hanya payroll berstatus DRAF yang dapat di-regenerate.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            $options = json_decode((string)$run['options_json'], true);
            if (!is_array($options)) {
                $pdo->rollBack();
                $this->flashError('Struktur opsi tanggal payroll tidak valid.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            // 1. Unlock existing records
            $pdo->prepare("UPDATE public.absensi SET penggajian_id = NULL WHERE penggajian_id = :rid")->execute(['rid' => $runId]);
            $pdo->prepare("UPDATE public.produksi_harian SET penggajian_id = NULL WHERE penggajian_id = :rid")->execute(['rid' => $runId]);
            $pdo->prepare("UPDATE public.penarikan_gaji SET penggajian_id = NULL WHERE penggajian_id = :rid")->execute(['rid' => $runId]);

            // 2. Delete existing rincian items
            $pdo->prepare("DELETE FROM public.rincian_penggajian WHERE penggajian_id = :rid")->execute(['rid' => $runId]);

            // 3. Re-run generator
            $itemCount = 0;
            $preventedDoubleCount = 0;
            $this->generatePayrollItems($pdo, $runId, $options, $itemCount, $preventedDoubleCount);

            // 4. Update header total
            $stmtSum = $pdo->prepare("SELECT COALESCE(SUM(gaji_bersih_diterima), 0) FROM public.rincian_penggajian WHERE penggajian_id = :rid AND is_excluded = FALSE");
            $stmtSum->execute(['rid' => $runId]);
            $newTotal = (float)$stmtSum->fetchColumn();

            $pdo->prepare("UPDATE public.penggajian SET total_gaji_dikeluarkan = :total, diubah_pada = NOW() WHERE id = :rid")->execute(['total' => $newTotal, 'rid' => $runId]);

            $pdo->commit();

            ActivityLog::log(
                'hr_payroll',
                'REGENERATE_PAYROLL',
                "Melakukan regenerasi ulang draf payroll {$run['nomor_referensi']} ({$itemCount} karyawan).",
                'penggajian',
                $runId
            );

            $this->flashSuccess("Draf payroll {$run['nomor_referensi']} berhasil di-regenerasi ulang ({$itemCount} karyawan).");
            $this->redirect('/penggajian/preview?id=' . $runId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal me-regenerate payroll: ' . $e->getMessage());
            $this->redirect('/penggajian/preview?id=' . $runId);
        }
    }

    /**
     * GET /penggajian/slip — Download Single Slip Gaji PDF (A5 Portrait)
     */
    public function slip(): void
    {
        $runId = (string)$this->input('run_id', '');
        $rincianId = (string)$this->input('rincian_id', '');

        if (empty($runId) || empty($rincianId)) {
            $this->redirect('/penggajian');
            return;
        }

        try {
            $data = Database::fetchOne("
                SELECT
                    rp.*,
                    v.nama_karyawan, v.nama_panggilan, v.posisi, v.nik,
                    v.bank_nama, v.bank_nomor_rekening, v.bank_atas_nama,
                    k.tipe_penggajian,
                    p.nomor_referensi, p.nama_payroll, p.periode_awal, p.periode_akhir,
                    p.tipe_penggajian as run_tipe, p.options_json, p.disetujui_pada,
                    pv.nama_lengkap as nama_approver
                FROM public.rincian_penggajian rp
                JOIN public.v_karyawan_info v ON v.id = rp.karyawan_id
                JOIN public.karyawan k ON k.id = rp.karyawan_id
                JOIN public.penggajian p ON p.id = rp.penggajian_id
                LEFT JOIN public.pengguna pv ON pv.id = p.disetujui_oleh
                WHERE rp.id = :rpid AND rp.penggajian_id = :rid
            ", ['rpid' => $rincianId, 'rid' => $runId]);

            if (!$data) {
                $this->flashError('Data slip gaji tidak ditemukan.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            $company = CompanySetting::getAll();

            ob_start();
            $items = [$data];
            $isBatch = false;
            require dirname(__DIR__, 2) . '/views/penggajian/slip_pdf.php';
            $html = ob_get_clean();

            $filename = 'Slip_Gaji_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['nama_karyawan']) . '_' . $data['nomor_referensi'] . '.pdf';
            PdfExport::stream($html, $filename, 'A5', 'portrait');
        } catch (Throwable $e) {
            $this->flashError('Gagal mencetak slip gaji: ' . $e->getMessage());
            $this->redirect('/penggajian/preview?id=' . $runId);
        }
    }

    /**
     * GET /penggajian/slip-batch — Download All Slips in Run (A4 Portrait Multi-Page)
     */
    public function slipBatch(): void
    {
        $runId = (string)$this->input('run_id', '');
        if (empty($runId)) {
            $this->redirect('/penggajian');
            return;
        }

        try {
            $items = Database::fetchAll("
                SELECT
                    rp.*,
                    v.nama_karyawan, v.nama_panggilan, v.posisi, v.nik,
                    v.bank_nama, v.bank_nomor_rekening, v.bank_atas_nama,
                    k.tipe_penggajian,
                    p.nomor_referensi, p.nama_payroll, p.periode_awal, p.periode_akhir,
                    p.tipe_penggajian as run_tipe, p.options_json, p.disetujui_pada,
                    pv.nama_lengkap as nama_approver
                FROM public.rincian_penggajian rp
                JOIN public.v_karyawan_info v ON v.id = rp.karyawan_id
                JOIN public.karyawan k ON k.id = rp.karyawan_id
                JOIN public.penggajian p ON p.id = rp.penggajian_id
                LEFT JOIN public.pengguna pv ON pv.id = p.disetujui_oleh
                WHERE rp.penggajian_id = :rid AND rp.is_excluded = FALSE
                ORDER BY
                    CASE WHEN k.tipe_penggajian = 'bulanan' THEN 0 ELSE 1 END,
                    v.nama_karyawan ASC
            ", ['rid' => $runId]);

            if (empty($items)) {
                $this->flashError('Tidak ada data slip gaji aktif.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            $company = CompanySetting::getAll();

            ob_start();
            $isBatch = true;
            require dirname(__DIR__, 2) . '/views/penggajian/slip_pdf.php';
            $html = ob_get_clean();

            $runRef = $items[0]['nomor_referensi'] ?? 'BATCH';
            $filename = 'Batch_Slip_Gaji_' . $runRef . '_' . date('Ymd') . '.pdf';
            PdfExport::stream($html, $filename, 'A4', 'portrait');
        } catch (Throwable $e) {
            $this->flashError('Gagal mencetak batch slip: ' . $e->getMessage());
            $this->redirect('/penggajian/preview?id=' . $runId);
        }
    }

    /**
     * GET /penggajian/rekap-pdf — Cetak Rekapitulasi Payroll (A4 Landscape)
     */
    public function rekapPdf(): void
    {
        $runId = (string)$this->input('run_id', '');
        if (empty($runId)) {
            $this->redirect('/penggajian');
            return;
        }

        try {
            $run = Database::fetchOne("
                SELECT p.*, pv.nama_lengkap as nama_approver
                FROM public.penggajian p
                LEFT JOIN public.pengguna pv ON pv.id = p.disetujui_oleh
                WHERE p.id = :id
            ", ['id' => $runId]);

            if (!$run) {
                $this->flashError('Data payroll tidak ditemukan.');
                $this->redirect('/penggajian');
                return;
            }

            $items = Database::fetchAll("
                SELECT rp.*, v.nama_karyawan, v.posisi, k.tipe_penggajian
                FROM public.rincian_penggajian rp
                JOIN public.v_karyawan_info v ON v.id = rp.karyawan_id
                JOIN public.karyawan k ON k.id = rp.karyawan_id
                WHERE rp.penggajian_id = :id AND rp.is_excluded = FALSE
                ORDER BY
                    CASE WHEN k.tipe_penggajian = 'bulanan' THEN 0 ELSE 1 END,
                    v.nama_karyawan ASC
            ", ['id' => $runId]);

            $company = CompanySetting::getAll();

            ob_start();
            require dirname(__DIR__, 2) . '/views/penggajian/rekap_pdf.php';
            $html = ob_get_clean();

            $filename = 'Rekap_Penggajian_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $run['nomor_referensi']) . '.pdf';
            PdfExport::stream($html, $filename, 'A4', 'landscape');
        } catch (Throwable $e) {
            $this->flashError('Gagal mencetak rekap penggajian: ' . $e->getMessage());
            $this->redirect('/penggajian/preview?id=' . $runId);
        }
    }

    /**
     * GET /penggajian/rekap/karyawan — Rekap Riwayat HR & Payroll Per Karyawan (Web)
     */
    public function rekapKaryawan(): void
    {
        $karyawanId = (string)$this->input('karyawan_id', '');
        $tahun = (int)$this->input('tahun', (int)date('Y'));

        try {
            $karyawanList = Database::fetchAll("
                SELECT id, nama_karyawan, posisi, tipe_penggajian
                FROM public.v_karyawan_info
                ORDER BY status_aktif DESC, nama_karyawan ASC
            ");

            $karyawan = null;
            $payrollHistory = [];
            $absensiMonthly = [];
            $kasbonList = [];
            $tabunganList = [];
            $saldoTabungan = 0.0;
            $sisaKasbon = 0.0;

            if (!empty($karyawanId)) {
                $karyawan = Database::fetchOne("SELECT * FROM public.v_karyawan_info WHERE id = :id", ['id' => $karyawanId]);

                if ($karyawan) {
                    // Riwayat Penggajian
                    $payrollHistory = Database::fetchAll("
                        SELECT
                            rp.*,
                            p.nomor_referensi, p.nama_payroll, p.periode_awal, p.periode_akhir,
                            p.status as status_payroll, p.disetujui_pada
                        FROM public.rincian_penggajian rp
                        JOIN public.penggajian p ON p.id = rp.penggajian_id
                        WHERE rp.karyawan_id = :kid AND EXTRACT(YEAR FROM p.periode_akhir) = :thn
                        ORDER BY p.periode_akhir DESC
                    ", ['kid' => $karyawanId, 'thn' => $tahun]);

                    // Riwayat Absensi Bulanan
                    $absensiMonthly = Database::fetchAll("
                        SELECT
                            TO_CHAR(tanggal, 'YYYY-MM') as bulan,
                            COUNT(CASE WHEN status_kehadiran = 'hadir' THEN 1 END) as hadir,
                            COUNT(CASE WHEN status_kehadiran = 'izin' THEN 1 END) as izin,
                            COUNT(CASE WHEN status_kehadiran = 'sakit' THEN 1 END) as sakit,
                            COUNT(CASE WHEN status_kehadiran = 'libur' THEN 1 END) as libur,
                            COUNT(CASE WHEN status_kehadiran = 'alpa' THEN 1 END) as alpa,
                            COALESCE(SUM(lembur_nominal), 0) as total_lembur
                        FROM public.absensi
                        WHERE karyawan_id = :kid AND EXTRACT(YEAR FROM tanggal) = :thn
                        GROUP BY TO_CHAR(tanggal, 'YYYY-MM')
                        ORDER BY bulan DESC
                    ", ['kid' => $karyawanId, 'thn' => $tahun]);

                    // Riwayat Kasbon
                    $kasbonList = Database::fetchAll("
                        SELECT * FROM public.kasbon 
                        WHERE karyawan_id = :kid AND EXTRACT(YEAR FROM tanggal_pengajuan) = :thn
                        ORDER BY tanggal_pengajuan DESC
                    ", ['kid' => $karyawanId, 'thn' => $tahun]);

                    // Riwayat Tabungan
                    $tabunganList = Database::fetchAll("
                        SELECT * FROM public.transaksi_tabungan
                        WHERE karyawan_id = :kid AND EXTRACT(YEAR FROM tanggal) = :thn
                        ORDER BY tanggal DESC, dibuat_pada DESC
                    ", ['kid' => $karyawanId, 'thn' => $tahun]);

                    $saldoTabungan = (float)Database::fetchValue("SELECT saldo FROM public.tabungan WHERE karyawan_id = :kid", ['kid' => $karyawanId]);
                    $sisaKasbon = (float)Database::fetchValue("SELECT COALESCE(SUM(sisa_pinjaman), 0) FROM public.kasbon WHERE karyawan_id = :kid AND status_kasbon = 'aktif'", ['kid' => $karyawanId]);
                }
            }

            $this->view('penggajian.rekap_karyawan', [
                'pageTitle' => 'Rekap Riwayat Karyawan',
                'pageSubtitle' => 'Laporan Komprehensif Gaji, Kehadiran, Kasbon, & Tabungan',
                'karyawanList' => $karyawanList,
                'karyawanId' => $karyawanId,
                'tahun' => $tahun,
                'karyawan' => $karyawan,
                'payrollHistory' => $payrollHistory,
                'absensiMonthly' => $absensiMonthly,
                'kasbonList' => $kasbonList,
                'tabunganList' => $tabunganList,
                'saldoTabungan' => $saldoTabungan,
                'sisaKasbon' => $sisaKasbon
            ], 'layouts.master');
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat rekap karyawan: ' . $e->getMessage());
            $this->redirect('/penggajian');
        }
    }

    /**
     * GET /penggajian/rekap/karyawan/pdf — Cetak Dokumen PDF Rekap Karyawan (A4 Portrait)
     */
    public function rekapKaryawanPdf(): void
    {
        $karyawanId = (string)$this->input('karyawan_id', '');
        $tahun = (int)$this->input('tahun', (int)date('Y'));

        if (empty($karyawanId)) {
            $this->redirect('/penggajian/rekap/karyawan');
            return;
        }

        try {
            $karyawan = Database::fetchOne("SELECT * FROM public.v_karyawan_info WHERE id = :id", ['id' => $karyawanId]);
            if (!$karyawan) {
                $this->flashError('Karyawan tidak ditemukan.');
                $this->redirect('/penggajian/rekap/karyawan');
                return;
            }

            $payrollHistory = Database::fetchAll("
                SELECT
                    rp.*,
                    p.nomor_referensi, p.nama_payroll, p.periode_awal, p.periode_akhir,
                    p.status as status_payroll, p.disetujui_pada
                FROM public.rincian_penggajian rp
                JOIN public.penggajian p ON p.id = rp.penggajian_id
                WHERE rp.karyawan_id = :kid AND EXTRACT(YEAR FROM p.periode_akhir) = :thn
                ORDER BY p.periode_akhir DESC
            ", ['kid' => $karyawanId, 'thn' => $tahun]);

            $absensiMonthly = Database::fetchAll("
                SELECT
                    TO_CHAR(tanggal, 'YYYY-MM') as bulan,
                    COUNT(CASE WHEN status_kehadiran = 'hadir' THEN 1 END) as hadir,
                    COUNT(CASE WHEN status_kehadiran = 'izin' THEN 1 END) as izin,
                    COUNT(CASE WHEN status_kehadiran = 'sakit' THEN 1 END) as sakit,
                    COUNT(CASE WHEN status_kehadiran = 'libur' THEN 1 END) as libur,
                    COUNT(CASE WHEN status_kehadiran = 'alpa' THEN 1 END) as alpa,
                    COALESCE(SUM(lembur_nominal), 0) as total_lembur
                FROM public.absensi
                WHERE karyawan_id = :kid AND EXTRACT(YEAR FROM tanggal) = :thn
                GROUP BY TO_CHAR(tanggal, 'YYYY-MM')
                ORDER BY bulan DESC
            ", ['kid' => $karyawanId, 'thn' => $tahun]);

            $kasbonList = Database::fetchAll("
                SELECT * FROM public.kasbon 
                WHERE karyawan_id = :kid AND EXTRACT(YEAR FROM tanggal_pengajuan) = :thn
                ORDER BY tanggal_pengajuan DESC
            ", ['kid' => $karyawanId, 'thn' => $tahun]);

            $tabunganList = Database::fetchAll("
                SELECT * FROM public.transaksi_tabungan
                WHERE karyawan_id = :kid AND EXTRACT(YEAR FROM tanggal) = :thn
                ORDER BY tanggal DESC, dibuat_pada DESC
            ", ['kid' => $karyawanId, 'thn' => $tahun]);

            $saldoTabungan = (float)Database::fetchValue("SELECT saldo FROM public.tabungan WHERE karyawan_id = :kid", ['kid' => $karyawanId]);
            $sisaKasbon = (float)Database::fetchValue("SELECT COALESCE(SUM(sisa_pinjaman), 0) FROM public.kasbon WHERE karyawan_id = :kid AND status_kasbon = 'aktif'", ['kid' => $karyawanId]);

            $company = CompanySetting::getAll();

            ob_start();
            require dirname(__DIR__, 2) . '/views/penggajian/rekap_karyawan_pdf.php';
            $html = ob_get_clean();

            $filename = 'Rekap_Karyawan_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $karyawan['nama_karyawan']) . '_' . $tahun . '.pdf';
            PdfExport::download($html, $filename, 'A4', 'portrait');
        } catch (Throwable $e) {
            $this->flashError('Gagal mencetak rekap PDF karyawan: ' . $e->getMessage());
            $this->redirect('/penggajian/rekap/karyawan?karyawan_id=' . $karyawanId);
        }
    }
}

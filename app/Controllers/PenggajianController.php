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
                   v.nama_karyawan, v.posisi,
                   v.bank_nama, v.bank_nomor_rekening, v.bank_atas_nama
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

        $allPenarikanIdsToLock = [];

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
                $allPenarikanIdsToLock[] = $p['id'];
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
            $hasBankRek = !empty(trim((string)($emp['bank_nomor_rekening'] ?? '')));
            $isBankNameTunai = strtolower(trim((string)($emp['bank_nama'] ?? ''))) === 'tunai';
            $metodePembayaran = ($hasBankRek && !$isBankNameTunai) ? 'transfer' : 'tunai';

            $rincianJson = json_encode([
                'debts' => $kasbonDetails,
                'penarikan' => $penarikanDetails,
                'kasbon_adjusted_down' => $kasbonAdjustedDown,
                'metode_pembayaran' => $metodePembayaran,
                'bank_nama' => (string)($emp['bank_nama'] ?? ''),
                'bank_nomor_rekening' => (string)($emp['bank_nomor_rekening'] ?? ''),
                'bank_atas_nama' => (string)($emp['bank_atas_nama'] ?? ''),
            ], JSON_UNESCAPED_UNICODE);

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

        // Lock penarikan_gaji (Only lock the exact advances calculated in this payroll run)
        if (!empty($allPenarikanIdsToLock)) {
            $uniqueLockIds = array_values(array_unique($allPenarikanIdsToLock));
            $placeholders = implode(',', array_fill(0, count($uniqueLockIds), '?'));
            $stmtLockPenarikan = $pdo->prepare("UPDATE public.penarikan_gaji SET penggajian_id = ? WHERE id IN ($placeholders) AND penggajian_id IS NULL");
            $stmtLockPenarikan->execute(array_merge([$runId], $uniqueLockIds));
        }
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
                    v.bank_nama, v.bank_nomor_rekening, v.bank_atas_nama,
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

            // Summary data & Split Kas / Transfer Calculation
            $totalGajiBersih = 0.0;
            $totalKotor = 0.0;
            $totalPotongan = 0.0;
            $totalPotonganTabunganPayroll = 0.0;
            $totalPenarikanTabunganPayroll = 0.0;
            $includedCount = 0;
            $excludedCount = 0;
            $totalGajiTunai = 0.0;
            $countTunai = 0;
            $totalGajiTransfer = 0.0;
            $countTransfer = 0;
            $potonganTabunganTunai = 0.0;
            $potonganTabunganTransfer = 0.0;

            foreach ($items as &$item) {
                $details = json_decode((string)($item['rincian_json'] ?? ''), true) ?: [];
                $masterBankNama = trim((string)($item['bank_nama'] ?? ''));
                $masterBankRek  = trim((string)($item['bank_nomor_rekening'] ?? ''));
                $masterBankAn   = trim((string)($item['bank_atas_nama'] ?? '')) ?: (string)$item['nama_karyawan'];
                $hasMasterBank  = !empty($masterBankRek) && strtolower($masterBankNama) !== 'tunai';

                $item['master_bank_nama'] = $masterBankNama;
                $item['master_bank_nomor_rekening'] = $masterBankRek;
                $item['master_bank_atas_nama'] = $masterBankAn;
                $item['has_master_bank'] = $hasMasterBank;

                $defaultMetode = $hasMasterBank ? 'transfer' : 'tunai';
                $item['metode_pembayaran'] = (string)($details['metode_pembayaran'] ?? $defaultMetode);
                $item['bank_nama'] = (string)($details['bank_nama'] ?? ($hasMasterBank ? $masterBankNama : ''));
                $item['bank_nomor_rekening'] = (string)($details['bank_nomor_rekening'] ?? ($hasMasterBank ? $masterBankRek : ''));
                $item['bank_atas_nama'] = (string)($details['bank_atas_nama'] ?? ($hasMasterBank ? $masterBankAn : ''));

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

                $net = (float)$item['gaji_bersih_diterima'];
                $totalKotor += $kotor;
                $totalPotongan += $potong;
                $totalGajiBersih += $net;
                $totalPotonganTabunganPayroll += (float)$item['total_potongan_tabungan'];
                $totalPenarikanTabunganPayroll += (float)$item['penarikan_tabungan'];

                if ($item['metode_pembayaran'] === 'transfer') {
                    $totalGajiTransfer += $net;
                    $countTransfer++;
                    $potonganTabunganTransfer += (float)$item['total_potongan_tabungan'];
                } else {
                    $totalGajiTunai += $net;
                    $countTunai++;
                    $potonganTabunganTunai += (float)$item['total_potongan_tabungan'];
                }
            }
            unset($item);

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
                'totalGajiTunai' => $totalGajiTunai,
                'countTunai' => $countTunai,
                'totalGajiTransfer' => $totalGajiTransfer,
                'countTransfer' => $countTransfer,
                'potonganTabunganTunai' => $potonganTabunganTunai,
                'potonganTabunganTransfer' => $potonganTabunganTransfer,
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
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $stmtHeader = $pdo->prepare("SELECT status, nomor_referensi FROM public.penggajian WHERE id = :id FOR UPDATE");
            $stmtHeader->execute(['id' => $runId]);
            $run = $stmtHeader->fetch(PDO::FETCH_ASSOC);

            if (!$run || $run['status'] !== 'draf') {
                $pdo->rollBack();
                $this->flashError('Data payroll hanya dapat diedit saat status DRAF.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            $stmtItem = $pdo->prepare("
                SELECT rp.*,
                       COALESCE((SELECT nama_karyawan FROM public.v_karyawan_info WHERE id = rp.karyawan_id), 'Karyawan') as nama_karyawan,
                       COALESCE((SELECT SUM(sisa_pinjaman) FROM public.kasbon WHERE karyawan_id = rp.karyawan_id AND status_kasbon = 'aktif'), 0) as sisa_kasbon,
                       COALESCE((SELECT saldo FROM public.tabungan WHERE karyawan_id = rp.karyawan_id), 0) as saldo_tabungan
                FROM public.rincian_penggajian rp
                WHERE rp.id = :id AND rp.penggajian_id = :rid FOR UPDATE
            ");
            $stmtItem->execute(['id' => $itemId, 'rid' => $runId]);
            $item = $stmtItem->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                $pdo->rollBack();
                $this->flashError('Line item karyawan tidak ditemukan.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            // Input Sanitization & Boundary Guard
            $parseMoney = function($val): float {
                $clean = preg_replace('/[^0-9]/', '', (string)$val);
                return (float)($clean ?: 0);
            };

            $tunjanganLain = $parseMoney($this->input('tunjangan_lain', '0'));
            $catatanTunjanganLain = trim((string)$this->input('catatan_tunjangan_lain', ''));

            $potonganLain = $parseMoney($this->input('potongan_lain', '0'));
            $catatanPotonganLain = trim((string)$this->input('catatan_potongan_lain', ''));

            $potonganKasbon = $parseMoney($this->input('total_potongan_kasbon', '0'));
            $potonganTabungan = $parseMoney($this->input('total_potongan_tabungan', '0'));
            $penarikanTabungan = $parseMoney($this->input('penarikan_tabungan', '0'));

            // Pembulatan can be negative (misal -500 atau 500)
            $rawPembulatan = trim((string)$this->input('nominal_pembulatan', '0'));
            $cleanPembulatan = str_replace(['.', ' ', 'Rp', 'rp'], '', $rawPembulatan);
            if (!preg_match('/^-?\d+$/', $cleanPembulatan)) {
                $pdo->rollBack();
                $this->flashError('Nominal angka pembulatan tidak valid.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }
            $pembulatan = (float)$cleanPembulatan;

            // Maximum numeric boundary guard
            $maxLimit = 9999999999.00;
            if ($tunjanganLain > $maxLimit || $potonganLain > $maxLimit || $potonganKasbon > $maxLimit || 
                $potonganTabungan > $maxLimit || $penarikanTabungan > $maxLimit || abs($pembulatan) > $maxLimit) {
                $pdo->rollBack();
                $this->flashError('Nominal komponen melebihi batas yang diizinkan sistem.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            // Business Validations: Kasbon & Tabungan
            $saldoTabungan = (float)$item['saldo_tabungan'];
            $sisaKasbon = (float)$item['sisa_kasbon'];

            if ($penarikanTabungan > $saldoTabungan) {
                $pdo->rollBack();
                $this->flashError('Penarikan tabungan melebihi saldo tabungan karyawan saat ini (' . Format::rupiah($saldoTabungan) . ').');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            if ($potonganKasbon > $sisaKasbon) {
                $pdo->rollBack();
                $this->flashError('Potongan kasbon melebihi sisa pinjaman kasbon aktif karyawan (' . Format::rupiah($sisaKasbon) . ').');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            // Re-allocate rincian_json debts agar selalu sinkron dengan total_potongan_kasbon
            $existingDetails = json_decode((string)($item['rincian_json'] ?? ''), true) ?: [];
            $newDebts = [];
            if ($potonganKasbon > 0) {
                $stmtActiveKb = $pdo->prepare("
                    SELECT id, keterangan, total_pinjaman, potongan_per_periode, sisa_pinjaman 
                    FROM public.kasbon 
                    WHERE karyawan_id = :kid AND status_kasbon = 'aktif' 
                    ORDER BY tanggal_pengajuan ASC
                ");
                $stmtActiveKb->execute(['kid' => $item['karyawan_id']]);
                $activeKasbons = $stmtActiveKb->fetchAll(PDO::FETCH_ASSOC);

                $remainingToCut = $potonganKasbon;
                foreach ($activeKasbons as $kb) {
                    if ($remainingToCut <= 0) break;
                    $cut = min($remainingToCut, (float)$kb['sisa_pinjaman']);
                    if ($cut <= 0) continue;
                    $newDebts[] = [
                        'kasbon_id'  => $kb['id'],
                        'keterangan' => $kb['keterangan'] ?? 'Kasbon Karyawan',
                        'nominal'    => $cut
                    ];
                    $remainingToCut -= $cut;
                }
            }
            $metodeInput = strtolower(trim((string)$this->input('metode_pembayaran', '')));
            if (in_array($metodeInput, ['tunai', 'transfer'], true)) {
                $existingDetails['metode_pembayaran'] = $metodeInput;
            }
            $inBankNama = trim((string)$this->input('bank_nama', ''));
            $inBankRek  = trim((string)$this->input('bank_nomor_rekening', ''));
            $inBankAn   = trim((string)$this->input('bank_atas_nama', ''));

            if ($metodeInput === 'transfer') {
                if (empty($inBankRek)) {
                    // Fallback otomatis ke data master karyawan jika nomor rekening tidak diinput manual
                    $stmtMaster = $pdo->prepare("SELECT bank_nama, bank_nomor_rekening, bank_atas_nama, nama_karyawan FROM public.v_karyawan_info WHERE id = :kid");
                    $stmtMaster->execute(['kid' => $item['karyawan_id']]);
                    $mRow = $stmtMaster->fetch(PDO::FETCH_ASSOC);
                    if ($mRow && !empty(trim((string)$mRow['bank_nomor_rekening']))) {
                        $inBankNama = trim((string)$mRow['bank_nama']);
                        $inBankRek  = trim((string)$mRow['bank_nomor_rekening']);
                        $inBankAn   = trim((string)$mRow['bank_atas_nama']) ?: (string)$mRow['nama_karyawan'];
                    }
                }
                $existingDetails['bank_nama'] = $inBankNama;
                $existingDetails['bank_nomor_rekening'] = $inBankRek;
                $existingDetails['bank_atas_nama'] = $inBankAn;
            } else {
                if ($this->input('bank_nama') !== null) $existingDetails['bank_nama'] = $inBankNama;
                if ($this->input('bank_nomor_rekening') !== null) $existingDetails['bank_nomor_rekening'] = $inBankRek;
                if ($this->input('bank_atas_nama') !== null) $existingDetails['bank_atas_nama'] = $inBankAn;
            }
            $existingDetails['debts'] = $newDebts;
            $newRincianJson = json_encode($existingDetails, JSON_UNESCAPED_UNICODE);

            // Recalculate Net Salary
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

            // Strict Non-Negative Net Guard (Layer 3)
            if ($gajiBersih < 0) {
                $pdo->rollBack();
                $this->flashError('Total potongan melebihi total pendapatan. Gaji bersih tidak boleh minus (negatif).');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

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
                    gaji_bersih_diterima = :net,
                    rincian_json = :rjson
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
                'rjson' => $newRincianJson,
                'id' => $itemId,
                'rid' => $runId
            ]);

            // Update header total dalam transaksi
            $stmtSum = $pdo->prepare("SELECT COALESCE(SUM(gaji_bersih_diterima), 0) FROM public.rincian_penggajian WHERE penggajian_id = :rid AND is_excluded = FALSE");
            $stmtSum->execute(['rid' => $runId]);
            $newTotal = (float)$stmtSum->fetchColumn();

            $pdo->prepare("UPDATE public.penggajian SET total_gaji_dikeluarkan = :total WHERE id = :rid")->execute(['total' => $newTotal, 'rid' => $runId]);

            $pdo->commit();

            $empName = !empty($item['nama_karyawan']) ? $item['nama_karyawan'] : 'karyawan';
            ActivityLog::log(
                'hr_payroll',
                'UPDATE_PAYROLL_ITEM',
                "Menyesuaikan komponen gaji {$empName} pada {$run['nomor_referensi']} dengan net " . Format::rupiah($gajiBersih) . ".",
                'rincian_penggajian',
                $itemId
            );

            $this->flashSuccess("Rincian penyesuaian gaji {$empName} berhasil diperbarui.");
            $this->redirect('/penggajian/preview?id=' . $runId);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
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

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            // 1. Lock header & verifikasi status masih DRAF
            $stmtRun = $pdo->prepare("SELECT id, nomor_referensi, status, options_json FROM public.penggajian WHERE id = :id FOR UPDATE");
            $stmtRun->execute(['id' => $runId]);
            $run = $stmtRun->fetch(PDO::FETCH_ASSOC);

            if (!$run || $run['status'] !== 'draf') {
                $pdo->rollBack();
                $this->flashError('Pengecualian karyawan hanya dapat diubah saat status payroll masih DRAF.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            // 2. Lock line item
            $stmtItem = $pdo->prepare("
                SELECT rp.id, rp.is_excluded, rp.karyawan_id, rp.rincian_json,
                       COALESCE((SELECT nama_karyawan FROM public.v_karyawan_info WHERE id = rp.karyawan_id), 'Karyawan') as nama_karyawan
                FROM public.rincian_penggajian rp
                WHERE rp.id = :id AND rp.penggajian_id = :rid
                FOR UPDATE
            ");
            $stmtItem->execute(['id' => $itemId, 'rid' => $runId]);
            $item = $stmtItem->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                $pdo->rollBack();
                $this->flashError('Data line item tidak ditemukan.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            $newExcluded = !$item['is_excluded'];

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

            $details = json_decode((string)($item['rincian_json'] ?? ''), true) ?: [];

            // If excluded -> unlock records for this employee
            if ($newExcluded) {
                $pdo->prepare("UPDATE public.absensi SET penggajian_id = NULL WHERE penggajian_id = :rid AND karyawan_id = :kid")->execute(['rid' => $runId, 'kid' => $item['karyawan_id']]);
                $pdo->prepare("UPDATE public.produksi_harian SET penggajian_id = NULL WHERE penggajian_id = :rid AND karyawan_id = :kid")->execute(['rid' => $runId, 'kid' => $item['karyawan_id']]);
                $pdo->prepare("UPDATE public.penarikan_gaji SET penggajian_id = NULL WHERE penggajian_id = :rid AND karyawan_id = :kid")->execute(['rid' => $runId, 'kid' => $item['karyawan_id']]);
            } else {
                // Relock records for this employee within exact options date boundary
                $options = json_decode((string)$run['options_json'], true);
                if (is_array($options)) {
                    if (isset($options['borongan'])) {
                        $pdo->prepare("UPDATE public.absensi SET penggajian_id = :rid WHERE penggajian_id IS NULL AND tanggal BETWEEN :s AND :e AND karyawan_id = :kid")->execute(['rid' => $runId, 's' => $options['borongan']['start'], 'e' => $options['borongan']['end'], 'kid' => $item['karyawan_id']]);
                        $pdo->prepare("UPDATE public.produksi_harian SET penggajian_id = :rid WHERE penggajian_id IS NULL AND tanggal BETWEEN :s AND :e AND karyawan_id = :kid")->execute(['rid' => $runId, 's' => $options['borongan']['start'], 'e' => $options['borongan']['end'], 'kid' => $item['karyawan_id']]);
                    }
                    if (isset($options['bulanan'])) {
                        $pdo->prepare("UPDATE public.absensi SET penggajian_id = :rid WHERE penggajian_id IS NULL AND tanggal BETWEEN :s AND :e AND karyawan_id = :kid")->execute(['rid' => $runId, 's' => $options['bulanan']['start'], 'e' => $options['bulanan']['end'], 'kid' => $item['karyawan_id']]);
                    }
                    
                    // Relock penarikan_gaji strictly based on included IDs
                    $penarikanItems = $details['penarikan'] ?? [];
                    $penarikanIds = array_column($penarikanItems, 'id_penarikan');
                    if (!empty($penarikanIds)) {
                        $placeholders = implode(',', array_fill(0, count($penarikanIds), '?'));
                        $pdo->prepare("UPDATE public.penarikan_gaji SET penggajian_id = ? WHERE id IN ($placeholders) AND penggajian_id IS NULL")
                            ->execute(array_merge([$runId], $penarikanIds));
                    }
                }
            }

            // Update header total dalam transaksi
            $stmtSum = $pdo->prepare("SELECT COALESCE(SUM(gaji_bersih_diterima), 0) FROM public.rincian_penggajian WHERE penggajian_id = :rid AND is_excluded = FALSE");
            $stmtSum->execute(['rid' => $runId]);
            $newTotal = (float)$stmtSum->fetchColumn();

            $pdo->prepare("UPDATE public.penggajian SET total_gaji_dikeluarkan = :total, diubah_pada = NOW() WHERE id = :rid")->execute(['total' => $newTotal, 'rid' => $runId]);

            $pdo->commit();

            ActivityLog::log(
                'hr_payroll',
                'TOGGLE_EXCLUDE_PAYROLL_ITEM',
                ($newExcluded ? "Mengecualikan" : "Menyertakan kembali") . " karyawan {$item['nama_karyawan']} pada draf payroll {$run['nomor_referensi']}.",
                'rincian_penggajian',
                $itemId
            );

            $this->flashSuccess($newExcluded ? 'Karyawan berhasil dikecualikan dari payroll ini.' : 'Karyawan berhasil disertakan kembali ke payroll.');
            $this->redirect('/penggajian/preview?id=' . $runId);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
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
        $akunKasTunaiId = (string)$this->input('akun_kas_tunai_id', '');
        $akunKasTransferId = (string)$this->input('akun_kas_transfer_id', '');
        $akunKasTabunganSumberId = (string)$this->input('akun_kas_tabungan_sumber_id', '');
        $legacyKasId = (string)$this->input('akun_kas_id', '');

        // Fallbacks untuk backward-compatibility jika form lama mengirimkan akun_kas_id tunggal
        if (empty($akunKasTunaiId) && !empty($legacyKasId)) {
            $akunKasTunaiId = $legacyKasId;
        }
        if (empty($akunKasTransferId) && !empty($legacyKasId)) {
            $akunKasTransferId = $legacyKasId;
        }
        if (empty($akunKasTabunganSumberId)) {
            $akunKasTabunganSumberId = $akunKasTransferId ?: $akunKasTunaiId ?: $legacyKasId;
        }

        if (empty($runId)) {
            $this->flashError('ID payroll tidak valid.');
            $this->redirectBack('/penggajian');
            return;
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $stmtRun = $pdo->prepare("
                SELECT id, nomor_referensi, nama_payroll, status, total_gaji_dikeluarkan, options_json
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

            // Layer 4 Guard: Tolak approval jika ada baris included dengan gaji bersih negatif
            $stmtNegCheck = $pdo->prepare("
                SELECT COUNT(*) 
                FROM public.rincian_penggajian 
                WHERE penggajian_id = :rid AND is_excluded = FALSE AND gaji_bersih_diterima < 0
            ");
            $stmtNegCheck->execute(['rid' => $runId]);
            if ((int)$stmtNegCheck->fetchColumn() > 0) {
                $pdo->rollBack();
                $this->flashError('Persetujuan gagal: Terdapat karyawan dengan gaji bersih bernilai negatif. Harap sesuaikan rincian komponen terlebih dahulu.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            $totalGaji = (float)($totalsRow['total_gaji'] ?? 0);
            $totalPotonganTabunganAll = (float)($totalsRow['total_potongan_tabungan'] ?? 0);
            $totalPenarikanTabunganAll = (float)($totalsRow['total_penarikan_tabungan'] ?? 0);

            // Ambil semua line items included beserta data bank karyawan untuk klasifikasi Tunai vs Transfer
            $stmtItems = $pdo->prepare("
                SELECT rp.id, rp.karyawan_id, rp.gaji_bersih_diterima, rp.total_potongan_kasbon,
                       rp.total_potongan_tabungan, rp.penarikan_tabungan, rp.rincian_json,
                       v.bank_nama, v.bank_nomor_rekening, v.bank_atas_nama
                FROM public.rincian_penggajian rp
                JOIN public.v_karyawan_info v ON v.id = rp.karyawan_id
                WHERE rp.penggajian_id = :rid AND rp.is_excluded = FALSE
            ");
            $stmtItems->execute(['rid' => $runId]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            $totalGajiTunai = 0.0;
            $totalGajiTransfer = 0.0;
            foreach ($items as &$item) {
                $details = json_decode((string)($item['rincian_json'] ?? ''), true) ?: [];
                $hasBankRek = !empty(trim((string)($item['bank_nomor_rekening'] ?? '')));
                $isBankNameTunai = strtolower(trim((string)($item['bank_nama'] ?? ''))) === 'tunai';
                $defaultMetode = ($hasBankRek && !$isBankNameTunai) ? 'transfer' : 'tunai';
                $metode = (string)($details['metode_pembayaran'] ?? $defaultMetode);
                $item['resolved_metode'] = $metode;

                $net = (float)$item['gaji_bersih_diterima'];
                if ($metode === 'transfer') {
                    $totalGajiTransfer += $net;
                } else {
                    $totalGajiTunai += $net;
                }
            }
            unset($item);

            // Validasi keberadaan akun kas yang dibutuhkan
            if ($totalGajiTunai > 0 && empty($akunKasTunaiId)) {
                $pdo->rollBack();
                $this->flashError('Harap pilih akun kas untuk pembayaran gaji tunai (kebutuhan: ' . Format::rupiah($totalGajiTunai) . ').');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            if ($totalGajiTransfer > 0 && empty($akunKasTransferId)) {
                $pdo->rollBack();
                $this->flashError('Harap pilih akun bank untuk pembayaran gaji transfer (kebutuhan: ' . Format::rupiah($totalGajiTransfer) . ').');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            if ($totalPotonganTabunganAll > 0 && empty($akunKasTabunganSumberId)) {
                $pdo->rollBack();
                $this->flashError('Harap pilih akun sumber kas untuk setoran tabungan karyawan (kebutuhan: ' . Format::rupiah($totalPotonganTabunganAll) . ').');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            // Hitung akumulasi kebutuhan saldo per akun kas (mendukung skenario jika akun yang sama dipilih untuk keduanya)
            $requiredPerAccount = [];
            if ($totalGajiTunai > 0 && $akunKasTunaiId) {
                $requiredPerAccount[$akunKasTunaiId] = ($requiredPerAccount[$akunKasTunaiId] ?? 0.0) + $totalGajiTunai;
            }
            if ($totalGajiTransfer > 0 && $akunKasTransferId) {
                $requiredPerAccount[$akunKasTransferId] = ($requiredPerAccount[$akunKasTransferId] ?? 0.0) + $totalGajiTransfer;
            }
            if ($totalPotonganTabunganAll > 0 && $akunKasTabunganSumberId) {
                $requiredPerAccount[$akunKasTabunganSumberId] = ($requiredPerAccount[$akunKasTabunganSumberId] ?? 0.0) + $totalPotonganTabunganAll;
            }

            $lockedAccounts = [];
            foreach (array_keys($requiredPerAccount) as $accId) {
                $stmtKas = $pdo->prepare("SELECT id, nama_akun, saldo_saat_ini, is_escrow FROM public.akun_kas WHERE id = :id FOR UPDATE");
                $stmtKas->execute(['id' => $accId]);
                $accRow = $stmtKas->fetch(PDO::FETCH_ASSOC);

                if (!$accRow) {
                    $pdo->rollBack();
                    $this->flashError('Akun kas terpilih tidak ditemukan.');
                    $this->redirect('/penggajian/preview?id=' . $runId);
                    return;
                }

                if (!empty($accRow['is_escrow'])) {
                    $pdo->rollBack();
                    $this->flashError("Akun kas '{$accRow['nama_akun']}' adalah akun escrow tabungan dan tidak boleh digunakan sebagai sumber pembayaran operasional.");
                    $this->redirect('/penggajian/preview?id=' . $runId);
                    return;
                }

                $currentSaldo = (float)$accRow['saldo_saat_ini'];
                $needed = $requiredPerAccount[$accId];
                if ($needed > $currentSaldo) {
                    $pdo->rollBack();
                    $this->flashError("Saldo akun kas '{$accRow['nama_akun']}' (" . Format::rupiah($currentSaldo) . ") tidak mencukupi untuk pembayaran payroll & setoran (" . Format::rupiah($needed) . ").");
                    $this->redirect('/penggajian/preview?id=' . $runId);
                    return;
                }

                $lockedAccounts[$accId] = $accRow;
            }

            // Ambil akun kas escrow tabungan karyawan jika ada transaksi tabungan
            $stmtEscrow = $pdo->query("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE is_escrow = TRUE AND status_aktif = TRUE LIMIT 1");
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

            // Pre-validation: Cek kecukupan saldo tabungan per karyawan yang menarik tabungan
            $stmtCheckEmpTab = $pdo->prepare("
                SELECT rp.karyawan_id, v.nama_karyawan, rp.penarikan_tabungan, COALESCE(t.saldo, 0) as saldo_saat_ini
                FROM public.rincian_penggajian rp
                JOIN public.v_karyawan_info v ON v.id = rp.karyawan_id
                LEFT JOIN public.tabungan t ON t.karyawan_id = rp.karyawan_id
                WHERE rp.penggajian_id = :rid AND rp.is_excluded = FALSE AND rp.penarikan_tabungan > 0
            ");
            $stmtCheckEmpTab->execute(['rid' => $runId]);
            $tabCheckRows = $stmtCheckEmpTab->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tabCheckRows as $tRow) {
                if ((float)$tRow['penarikan_tabungan'] > (float)$tRow['saldo_saat_ini']) {
                    $pdo->rollBack();
                    $this->flashError("Persetujuan dibatalkan: Saldo tabungan {$tRow['nama_karyawan']} (" . Format::rupiah((float)$tRow['saldo_saat_ini']) . ") tidak mencukupi untuk penarikan sebesar " . Format::rupiah((float)$tRow['penarikan_tabungan']) . ".");
                    $this->redirect('/penggajian/preview?id=' . $runId);
                    return;
                }
            }

            // Pre-validation: Cek sisa pinjaman kasbon aktif per karyawan
            $stmtCheckEmpKb = $pdo->prepare("
                SELECT rp.karyawan_id, v.nama_karyawan, rp.total_potongan_kasbon,
                       COALESCE((SELECT SUM(sisa_pinjaman) FROM public.kasbon WHERE karyawan_id = rp.karyawan_id AND status_kasbon = 'aktif'), 0) as sisa_kasbon_aktif
                FROM public.rincian_penggajian rp
                JOIN public.v_karyawan_info v ON v.id = rp.karyawan_id
                WHERE rp.penggajian_id = :rid AND rp.is_excluded = FALSE AND rp.total_potongan_kasbon > 0
            ");
            $stmtCheckEmpKb->execute(['rid' => $runId]);
            $kbCheckRows = $stmtCheckEmpKb->fetchAll(PDO::FETCH_ASSOC);
            foreach ($kbCheckRows as $kbRow) {
                if ((float)$kbRow['total_potongan_kasbon'] > (float)$kbRow['sisa_kasbon_aktif']) {
                    $pdo->rollBack();
                    $this->flashError("Persetujuan dibatalkan: Potongan kasbon {$kbRow['nama_karyawan']} (" . Format::rupiah((float)$kbRow['total_potongan_kasbon']) . ") melebihi sisa pinjaman kasbon aktif (" . Format::rupiah((float)$kbRow['sisa_kasbon_aktif']) . ").");
                    $this->redirect('/penggajian/preview?id=' . $runId);
                    return;
                }
            }

            $userId = Auth::user()['id'] ?? null;

            // Simpan metadata akun pencairan payroll ke dalam options_json
            $opts = json_decode((string)($run['options_json'] ?? ''), true) ?: [];
            $opts['disbursement_accounts'] = [
                'tunai_kas_id' => $akunKasTunaiId ?: null,
                'transfer_kas_id' => $akunKasTransferId ?: null,
                'tabungan_sumber_kas_id' => $akunKasTabunganSumberId ?: null,
            ];

            // 1. Update Status Penggajian
            $pdo->prepare("
                UPDATE public.penggajian
                SET status = 'disetujui',
                    disetujui_oleh = :uid,
                    disetujui_pada = NOW(),
                    total_gaji_dikeluarkan = :total,
                    options_json = :opt,
                    diubah_pada = NOW()
                WHERE id = :rid
            ")->execute([
                'uid' => $userId,
                'total' => $totalGaji,
                'opt' => json_encode($opts, JSON_UNESCAPED_UNICODE),
                'rid' => $runId
            ]);

            // 2. Eksekusi Potongan Kasbon & Tabungan per Karyawan
            foreach ($items as $item) {
                $kid = $item['karyawan_id'];
                $rincianId = $item['id'];
                $details = json_decode((string)$item['rincian_json'], true) ?: [];
                $empKasId = ($item['resolved_metode'] === 'transfer')
                    ? ($akunKasTransferId ?: $akunKasTunaiId)
                    : ($akunKasTunaiId ?: $akunKasTransferId);

                // 2a. Eksekusi Potongan Kasbon
                $targetKasbonCut = (float)$item['total_potongan_kasbon'];
                if ($targetKasbonCut > 0) {
                    $stmtActiveKb = $pdo->prepare("
                        SELECT id, sisa_pinjaman, keterangan 
                        FROM public.kasbon 
                        WHERE karyawan_id = :kid AND status_kasbon = 'aktif' 
                        ORDER BY tanggal_pengajuan ASC 
                        FOR UPDATE
                    ");
                    $stmtActiveKb->execute(['kid' => $kid]);
                    $activeLoans = $stmtActiveKb->fetchAll(PDO::FETCH_ASSOC);

                    $remainingToCut = $targetKasbonCut;
                    $executedDebts = [];
                    foreach ($activeLoans as $kb) {
                        if ($remainingToCut <= 0) break;
                        $cut = min($remainingToCut, (float)$kb['sisa_pinjaman']);
                        if ($cut <= 0) continue;

                        $pdo->prepare("
                            INSERT INTO public.potongan_kasbon (
                                kasbon_id, rincian_penggajian_id, akun_kas_id, tanggal, nominal, tipe_potongan, keterangan, dibuat_pada
                            ) VALUES (
                                :kbid, :rpid, :kas_id, CURRENT_DATE, :nom, 'payroll', :ket, NOW()
                            )
                        ")->execute([
                            'kbid' => $kb['id'],
                            'rpid' => $rincianId,
                            'kas_id' => $empKasId,
                            'nom' => $cut,
                            'ket' => 'Potongan Payroll ' . $run['nomor_referensi']
                        ]);

                        $executedDebts[] = [
                            'kasbon_id'  => $kb['id'],
                            'keterangan' => $kb['keterangan'] ?? 'Kasbon Karyawan',
                            'nominal'    => $cut
                        ];
                        $remainingToCut -= $cut;
                    }

                    $details['debts'] = $executedDebts;
                    $pdo->prepare("UPDATE public.rincian_penggajian SET rincian_json = :json WHERE id = :id")
                        ->execute(['json' => json_encode($details, JSON_UNESCAPED_UNICODE), 'id' => $rincianId]);
                }

                // 2b. Eksekusi Tabungan (Setoran via Payroll)
                if ((float)$item['total_potongan_tabungan'] > 0) {
                    $setorNominal = (float)$item['total_potongan_tabungan'];
                    $pdo->prepare("INSERT INTO public.tabungan (karyawan_id, saldo) VALUES (:kid, 0.00) ON CONFLICT (karyawan_id) DO NOTHING")->execute(['kid' => $kid]);
                    $stmtTab = $pdo->prepare("SELECT id FROM public.tabungan WHERE karyawan_id = :kid");
                    $stmtTab->execute(['kid' => $kid]);
                    $tid = $stmtTab->fetchColumn();

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

            // 3. Catat ke Arus Kas — Pembayaran Gaji Tunai
            if ($totalGajiTunai > 0 && $akunKasTunaiId) {
                $stmtKasLock = $pdo->prepare("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE");
                $stmtKasLock->execute(['id' => $akunKasTunaiId]);
                $saldoCur = (float)$stmtKasLock->fetchColumn();
                $saldoBerjalan = $saldoCur - $totalGajiTunai;

                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                        nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :kas_id, CURRENT_DATE, 'keluar', 'pembayaran_payroll',
                        :nom, :ket, 'penggajian', :rid, :saldo_berjalan, :uid, NOW()
                    )
                ")->execute([
                    'kas_id' => $akunKasTunaiId,
                    'nom' => $totalGajiTunai,
                    'ket' => 'Pembayaran Gaji Tunai ' . $run['nama_payroll'] . ' (' . $run['nomor_referensi'] . ')',
                    'rid' => $runId,
                    'saldo_berjalan' => $saldoBerjalan,
                    'uid' => $userId
                ]);

                $pdo->prepare("
                    UPDATE public.akun_kas 
                    SET saldo_saat_ini = saldo_saat_ini - :total, diubah_pada = NOW() 
                    WHERE id = :id
                ")->execute(['total' => $totalGajiTunai, 'id' => $akunKasTunaiId]);
            }

            // 4. Catat ke Arus Kas — Pembayaran Gaji Transfer Bank
            if ($totalGajiTransfer > 0 && $akunKasTransferId) {
                $stmtKasLock = $pdo->prepare("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE");
                $stmtKasLock->execute(['id' => $akunKasTransferId]);
                $saldoCur = (float)$stmtKasLock->fetchColumn();
                $saldoBerjalan = $saldoCur - $totalGajiTransfer;

                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                        nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :kas_id, CURRENT_DATE, 'keluar', 'pembayaran_payroll',
                        :nom, :ket, 'penggajian', :rid, :saldo_berjalan, :uid, NOW()
                    )
                ")->execute([
                    'kas_id' => $akunKasTransferId,
                    'nom' => $totalGajiTransfer,
                    'ket' => 'Pembayaran Gaji Transfer Bank ' . $run['nama_payroll'] . ' (' . $run['nomor_referensi'] . ')',
                    'rid' => $runId,
                    'saldo_berjalan' => $saldoBerjalan,
                    'uid' => $userId
                ]);

                $pdo->prepare("
                    UPDATE public.akun_kas 
                    SET saldo_saat_ini = saldo_saat_ini - :total, diubah_pada = NOW() 
                    WHERE id = :id
                ")->execute(['total' => $totalGajiTransfer, 'id' => $akunKasTransferId]);
            }

            // 5. Auto-transfer Potongan Tabungan ke Akun Kas Tabungan (Escrow)
            if ($totalPotonganTabunganAll > 0 && $escrowKasId && $akunKasTabunganSumberId) {
                $stmtSrcLock = $pdo->prepare("SELECT saldo_saat_ini, nama_akun FROM public.akun_kas WHERE id = :id FOR UPDATE");
                $stmtSrcLock->execute(['id' => $akunKasTabunganSumberId]);
                $srcRow = $stmtSrcLock->fetch(PDO::FETCH_ASSOC);
                $srcSaldo = (float)($srcRow['saldo_saat_ini'] ?? 0);
                $srcSaldoBerjalan = $srcSaldo - $totalPotonganTabunganAll;

                // Outflow dari akun sumber setoran tabungan
                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                        nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :kas_id, CURRENT_DATE, 'transfer_keluar', 'transfer_keluar',
                        :nom, :ket, 'penggajian', :rid, :saldo_berjalan, :uid, NOW()
                    )
                ")->execute([
                    'kas_id' => $akunKasTabunganSumberId,
                    'nom' => $totalPotonganTabunganAll,
                    'ket' => 'Transfer Potongan Tabungan Payroll ' . $run['nomor_referensi'] . ' ke ' . $escrowAccount['nama_akun'],
                    'rid' => $runId,
                    'saldo_berjalan' => $srcSaldoBerjalan,
                    'uid' => $userId
                ]);

                $pdo->prepare("
                    UPDATE public.akun_kas 
                    SET saldo_saat_ini = saldo_saat_ini - :total, diubah_pada = NOW() 
                    WHERE id = :id
                ")->execute(['total' => $totalPotonganTabunganAll, 'id' => $akunKasTabunganSumberId]);

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
                    'ket' => 'Penerimaan Potongan Tabungan Payroll ' . $run['nomor_referensi'] . ' dari ' . ($srcRow['nama_akun'] ?? 'Kas Operasional'),
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

            // 6. Auto-reimburse Penarikan Tabungan dari Kas Tabungan (Escrow) ke Kas Payroll
            if ($totalPenarikanTabunganAll > 0 && $escrowKasId) {
                $targetReimburseId = $akunKasTransferId ?: $akunKasTunaiId ?: $legacyKasId;

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
                    'ket' => 'Pencairan Tabungan Payroll ' . $run['nomor_referensi'],
                    'rid' => $runId,
                    'saldo_berjalan' => $escrowSaldoBerjalan,
                    'uid' => $userId
                ]);

                $pdo->prepare("
                    UPDATE public.akun_kas 
                    SET saldo_saat_ini = saldo_saat_ini - :total, diubah_pada = NOW() 
                    WHERE id = :id
                ")->execute(['total' => $totalPenarikanTabunganAll, 'id' => $escrowKasId]);

                // Inflow ke kas operasional terpilih (reimbursement)
                $stmtReimbLock = $pdo->prepare("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE");
                $stmtReimbLock->execute(['id' => $targetReimburseId]);
                $reimbSaldoCur = (float)$stmtReimbLock->fetchColumn();
                $reimbSaldoBerjalan = $reimbSaldoCur + $totalPenarikanTabunganAll;

                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
                        nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :kas_id, CURRENT_DATE, 'transfer_masuk', 'transfer_masuk',
                        :nom, :ket, 'penggajian', :rid, :saldo_berjalan, :uid, NOW()
                    )
                ")->execute([
                    'kas_id' => $targetReimburseId,
                    'nom' => $totalPenarikanTabunganAll,
                    'ket' => 'Reimbursement Pencairan Tabungan Payroll ' . $run['nomor_referensi'] . ' dari ' . $escrowAccount['nama_akun'],
                    'rid' => $runId,
                    'saldo_berjalan' => $reimbSaldoBerjalan,
                    'uid' => $userId
                ]);

                $pdo->prepare("
                    UPDATE public.akun_kas 
                    SET saldo_saat_ini = saldo_saat_ini + :total, diubah_pada = NOW() 
                    WHERE id = :id
                ")->execute(['total' => $totalPenarikanTabunganAll, 'id' => $targetReimburseId]);
            }

            $pdo->commit();

            $accNames = array_map(fn($a) => $a['nama_akun'], $lockedAccounts);
            $logAccounts = implode(', ', $accNames);

            ActivityLog::log(
                'hr_payroll',
                'APPROVE_PAYROLL',
                "Menyetujui dan membayarkan payroll {$run['nomor_referensi']} senilai " . Format::rupiah($totalGaji) . " (Tunai: " . Format::rupiah($totalGajiTunai) . ", Transfer: " . Format::rupiah($totalGajiTransfer) . ") via {$logAccounts}.",
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

            if (!$run || !in_array($run['status'], ['disetujui', 'dibayarkan'], true)) {
                $pdo->rollBack();
                $this->flashError('Hanya payroll dengan status DISETUJUI yang dapat dibatalkan.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            // Check 24 hour window
            if (empty($run['disetujui_pada'])) {
                $pdo->rollBack();
                $this->flashError('Waktu persetujuan payroll tidak ditemukan atau tidak valid.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            $approveTime = strtotime((string)$run['disetujui_pada']);
            if ($approveTime === false || (time() - $approveTime > 86400)) {
                $pdo->rollBack();
                $this->flashError('Batas waktu pembatalan approval telah kedaluwarsa (maksimal 24 jam setelah payroll disetujui).');
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
                // Lock kasbon row and restore sisa_pinjaman safely
                $stmtKbLock = $pdo->prepare("SELECT id, total_pinjaman, sisa_pinjaman FROM public.kasbon WHERE id = :kbid FOR UPDATE");
                $stmtKbLock->execute(['kbid' => $pk['kasbon_id']]);
                $kbRow = $stmtKbLock->fetch(PDO::FETCH_ASSOC);

                if ($kbRow) {
                    $restoredSisa = (float)$kbRow['sisa_pinjaman'] + (float)$pk['nominal'];
                    if ($restoredSisa > (float)$kbRow['total_pinjaman']) {
                        $restoredSisa = (float)$kbRow['total_pinjaman'];
                    }
                    $newStatus = ($restoredSisa > 0) ? 'aktif' : 'lunas';
                    $pdo->prepare("
                        UPDATE public.kasbon 
                        SET sisa_pinjaman = :sisa, status_kasbon = :status, diubah_pada = NOW() 
                        WHERE id = :kbid
                    ")->execute(['sisa' => $restoredSisa, 'status' => $newStatus, 'kbid' => $pk['kasbon_id']]);
                }

                // Buka proteksi rincian_penggajian_id terlebih dahulu agar diizinkan oleh trg_guard_locked_hr_potongan_kasbon
                $pdo->prepare("UPDATE public.potongan_kasbon SET rincian_penggajian_id = NULL WHERE id = :pkid")->execute(['pkid' => $pk['id']]);
                $pdo->prepare("DELETE FROM public.potongan_kasbon WHERE id = :pkid")->execute(['pkid' => $pk['id']]);
            }

            // 2. Revert Transaksi Tabungan
            $stmtTb = $pdo->prepare("
                SELECT tt.id, tt.tabungan_id, tt.tipe, tt.jumlah, COALESCE(v.nama_karyawan, 'Karyawan') as nama_karyawan
                FROM public.transaksi_tabungan tt
                JOIN public.rincian_penggajian rp ON rp.id = tt.rincian_penggajian_id
                LEFT JOIN public.v_karyawan_info v ON v.id = tt.karyawan_id
                WHERE rp.penggajian_id = :rid
            ");
            $stmtTb->execute(['rid' => $runId]);
            $tabunganList = $stmtTb->fetchAll(PDO::FETCH_ASSOC);

            foreach ($tabunganList as $tb) {
                $stmtTabLock = $pdo->prepare("SELECT id, saldo FROM public.tabungan WHERE id = :tid FOR UPDATE");
                $stmtTabLock->execute(['tid' => $tb['tabungan_id']]);
                $tabRow = $stmtTabLock->fetch(PDO::FETCH_ASSOC);

                $delta = ($tb['tipe'] === 'deposit') ? -((float)$tb['jumlah']) : ((float)$tb['jumlah']);
                if ($tabRow) {
                    $saldoBaru = (float)$tabRow['saldo'] + $delta;
                    if ($saldoBaru < 0) {
                        throw new Exception("Saldo tabungan {$tb['nama_karyawan']} saat ini (" . Format::rupiah($tabRow['saldo']) . ") tidak mencukupi untuk pembatalan setoran tabungan (" . Format::rupiah($tb['jumlah']) . ").");
                    }
                    $pdo->prepare("UPDATE public.tabungan SET saldo = :saldo, diubah_pada = NOW() WHERE id = :tid")
                        ->execute(['saldo' => $saldoBaru, 'tid' => $tb['tabungan_id']]);
                }

                // Buka proteksi rincian_penggajian_id terlebih dahulu agar diizinkan oleh trg_guard_locked_hr_transaksi_tabungan
                $pdo->prepare("UPDATE public.transaksi_tabungan SET rincian_penggajian_id = NULL WHERE id = :ttid")->execute(['ttid' => $tb['id']]);
                $pdo->prepare("DELETE FROM public.transaksi_tabungan WHERE id = :ttid")->execute(['ttid' => $tb['id']]);
            }

            // 3. Revert Arus Kas & Saldo Akun Kas (Mendukung Multi-Row Escrow Transfers & Split Kas/Bank)
            $stmtArus = $pdo->prepare("
                SELECT id, akun_kas_id, nominal, jenis_kas 
                FROM public.arus_kas 
                WHERE referensi_tabel = 'penggajian' AND referensi_id = :rid 
                ORDER BY id DESC
            ");
            $stmtArus->execute(['rid' => $runId]);
            $arusRows = $stmtArus->fetchAll(PDO::FETCH_ASSOC);

            $kasDeltas = [];
            foreach ($arusRows as $arusRow) {
                $kid = (string)$arusRow['akun_kas_id'];
                $nom = (float)$arusRow['nominal'];
                if (!isset($kasDeltas[$kid])) {
                    $kasDeltas[$kid] = 0.0;
                }
                if ($arusRow['jenis_kas'] === 'keluar' || $arusRow['jenis_kas'] === 'transfer_keluar') {
                    // Dana keluar dikembalikan ke kas asal
                    $kasDeltas[$kid] += $nom;
                } elseif ($arusRow['jenis_kas'] === 'masuk' || $arusRow['jenis_kas'] === 'transfer_masuk') {
                    // Dana masuk ditarik kembali dari kas tujuan
                    $kasDeltas[$kid] -= $nom;
                }
            }

            foreach ($kasDeltas as $kid => $delta) {
                $stmtLockKas = $pdo->prepare("SELECT id, nama_akun, tipe_akun, saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE");
                $stmtLockKas->execute(['id' => $kid]);
                $kasRow = $stmtLockKas->fetch(PDO::FETCH_ASSOC);
                if ($kasRow) {
                    $saldoBaru = (float)$kasRow['saldo_saat_ini'] + $delta;
                    if ($saldoBaru < 0 && !in_array($kasRow['tipe_akun'], ['kartu_kredit', 'giro'], true)) {
                        throw new Exception("Saldo akun kas '{$kasRow['nama_akun']}' tidak mencukupi untuk ditarik kembali saat pembatalan payroll (Saldo saat ini: " . Format::rupiah($kasRow['saldo_saat_ini']) . ", pengurangan: " . Format::rupiah(abs($delta)) . ").");
                    }
                    $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo, diubah_pada = NOW() WHERE id = :id")
                        ->execute(['saldo' => $saldoBaru, 'id' => $kid]);
                }
            }

            $pdo->prepare("DELETE FROM public.arus_kas WHERE referensi_tabel = 'penggajian' AND referensi_id = :rid")
                ->execute(['rid' => $runId]);

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

            $stmtRun = $pdo->prepare("SELECT nomor_referensi, status FROM public.penggajian WHERE id = :id FOR UPDATE");
            $stmtRun->execute(['id' => $runId]);
            $run = $stmtRun->fetch(PDO::FETCH_ASSOC);

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

            $stmtRun = $pdo->prepare("SELECT * FROM public.penggajian WHERE id = :id FOR UPDATE");
            $stmtRun->execute(['id' => $runId]);
            $run = $stmtRun->fetch(PDO::FETCH_ASSOC);

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

            // 3. Re-run generator dengan parameter filter karyawan & base salary yang diawetkan
            $itemCount = 0;
            $preventedDoubleCount = 0;
            $selectedEmployeeIds = (array)($options['selected_karyawan_ids'] ?? []);
            $includeMonthlyBase = isset($options['include_monthly_base']) ? (bool)$options['include_monthly_base'] : true;
            $this->generatePayrollItems($pdo, $runId, $options, $itemCount, $preventedDoubleCount, $selectedEmployeeIds, $includeMonthlyBase);

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
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Parameter slip gaji tidak lengkap.'], 400);
                return;
            }
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
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'message' => 'Data slip gaji tidak ditemukan.'], 404);
                    return;
                }
                $this->flashError('Data slip gaji tidak ditemukan.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            $details = json_decode((string)($data['rincian_json'] ?? ''), true) ?: [];
            $hasBankRek = !empty(trim((string)($data['bank_nomor_rekening'] ?? '')));
            $isBankNameTunai = strtolower(trim((string)($data['bank_nama'] ?? ''))) === 'tunai';
            $defaultMetode = ($hasBankRek && !$isBankNameTunai) ? 'transfer' : 'tunai';
            $data['metode_pembayaran'] = (string)($details['metode_pembayaran'] ?? $defaultMetode);
            $data['bank_nama'] = (string)($details['bank_nama'] ?? $data['bank_nama'] ?? '');
            $data['bank_nomor_rekening'] = (string)($details['bank_nomor_rekening'] ?? $data['bank_nomor_rekening'] ?? '');
            $data['bank_atas_nama'] = (string)($details['bank_atas_nama'] ?? $data['bank_atas_nama'] ?? '');

            $company = CompanySetting::getAll();

            ob_start();
            $items = [$data];
            $isBatch = false;
            require dirname(__DIR__, 2) . '/views/penggajian/slip_pdf.php';
            $html = ob_get_clean();

            $filename = 'Slip_Gaji_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['nama_karyawan']) . '_' . $data['nomor_referensi'] . '.pdf';
            PdfExport::stream($html, $filename, 'A4', 'portrait');
        } catch (Throwable $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Gagal mencetak slip gaji: ' . $e->getMessage()], 500);
                return;
            }
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
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Parameter ID payroll tidak valid.'], 400);
                return;
            }
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
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'message' => 'Tidak ada data slip gaji aktif.'], 404);
                    return;
                }
                $this->flashError('Tidak ada data slip gaji aktif.');
                $this->redirect('/penggajian/preview?id=' . $runId);
                return;
            }

            foreach ($items as &$it) {
                $details = json_decode((string)($it['rincian_json'] ?? ''), true) ?: [];
                $hasBankRek = !empty(trim((string)($it['bank_nomor_rekening'] ?? '')));
                $isBankNameTunai = strtolower(trim((string)($it['bank_nama'] ?? ''))) === 'tunai';
                $defaultMetode = ($hasBankRek && !$isBankNameTunai) ? 'transfer' : 'tunai';
                $it['metode_pembayaran'] = (string)($details['metode_pembayaran'] ?? $defaultMetode);
                $it['bank_nama'] = (string)($details['bank_nama'] ?? $it['bank_nama'] ?? '');
                $it['bank_nomor_rekening'] = (string)($details['bank_nomor_rekening'] ?? $it['bank_nomor_rekening'] ?? '');
                $it['bank_atas_nama'] = (string)($details['bank_atas_nama'] ?? $it['bank_atas_nama'] ?? '');
            }
            unset($it);

            $company = CompanySetting::getAll();

            ob_start();
            $isBatch = true;
            require dirname(__DIR__, 2) . '/views/penggajian/slip_pdf.php';
            $html = ob_get_clean();

            $runRef = $items[0]['nomor_referensi'] ?? 'BATCH';
            $filename = 'Batch_Slip_Gaji_' . $runRef . '_' . date('Ymd') . '.pdf';
            PdfExport::stream($html, $filename, 'A4', 'portrait');
        } catch (Throwable $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Gagal mencetak batch slip: ' . $e->getMessage()], 500);
                return;
            }
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
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Parameter ID payroll tidak valid.'], 400);
                return;
            }
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
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'message' => 'Data payroll tidak ditemukan.'], 404);
                    return;
                }
                $this->flashError('Data payroll tidak ditemukan.');
                $this->redirect('/penggajian');
                return;
            }

            $items = Database::fetchAll("
                SELECT rp.*, v.nama_karyawan, v.posisi, k.tipe_penggajian,
                       v.bank_nama, v.bank_nomor_rekening, v.bank_atas_nama
                FROM public.rincian_penggajian rp
                JOIN public.v_karyawan_info v ON v.id = rp.karyawan_id
                JOIN public.karyawan k ON k.id = rp.karyawan_id
                WHERE rp.penggajian_id = :id AND rp.is_excluded = FALSE
                ORDER BY
                    CASE WHEN k.tipe_penggajian = 'bulanan' THEN 0 ELSE 1 END,
                    v.nama_karyawan ASC
            ", ['id' => $runId]);

            foreach ($items as &$it) {
                $details = json_decode((string)($it['rincian_json'] ?? ''), true) ?: [];
                $hasBankRek = !empty(trim((string)($it['bank_nomor_rekening'] ?? '')));
                $isBankNameTunai = strtolower(trim((string)($it['bank_nama'] ?? ''))) === 'tunai';
                $defaultMetode = ($hasBankRek && !$isBankNameTunai) ? 'transfer' : 'tunai';
                $it['metode_pembayaran'] = (string)($details['metode_pembayaran'] ?? $defaultMetode);
                $it['bank_nama'] = (string)($details['bank_nama'] ?? $it['bank_nama'] ?? '');
                $it['bank_nomor_rekening'] = (string)($details['bank_nomor_rekening'] ?? $it['bank_nomor_rekening'] ?? '');
                $it['bank_atas_nama'] = (string)($details['bank_atas_nama'] ?? $it['bank_atas_nama'] ?? '');
            }
            unset($it);

            $company = CompanySetting::getAll();

            ob_start();
            require dirname(__DIR__, 2) . '/views/penggajian/rekap_pdf.php';
            $html = ob_get_clean();

            $filename = 'Rekap_Penggajian_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $run['nomor_referensi']) . '.pdf';
            PdfExport::stream($html, $filename, 'A4', 'landscape');
        } catch (Throwable $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Gagal mencetak rekap penggajian: ' . $e->getMessage()], 500);
                return;
            }
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

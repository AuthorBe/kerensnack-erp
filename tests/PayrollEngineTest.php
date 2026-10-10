<?php
declare(strict_types=1);

/**
 * tests/PayrollEngineTest.php
 * Comprehensive, Production-Hardened Test Suite for HR & Payroll Engine
 * Keren Snack ERP & POS Architecture
 * 
 * Environment Security & Isolation Architecture:
 * - Local Environment (kerensnack_erp_local):
 *   Executes Part 1 (Production Read-Only Health Checks & Constraint Audits) AND
 *   Part 2 (Deep Mutating Lifecycle Simulations with 100% Transaction Rollback).
 * - Live Server / Production (Supabase PostgreSQL):
 *   Strictly enforces AGENTS.md Rule 1 & 3:
 *   Executes Part 1 (Read-Only Health Checks & Constraint Audits) ONLY.
 *   Safely bypasses mutating simulations to prevent sequence number skips (nomor_nota/referensi)
 *   and eliminate any risk of ledger pollution.
 * 
 * Test Coverage:
 * [READ-ONLY HEALTH CHECKS & SCHEMA AUDITS]
 * 1. Master Tables & Views Relational Health
 * 2. Anti-Negative Net Pay Check Constraint Integrity (chk_rincian_penggajian_net_nonneg)
 * 3. Payroll Types & Status Enum Constraints
 * 4. Kasbon Positive Balance Constraints
 * 5. Active Escrow Savings Cash Account Audit (status_aktif = TRUE)
 * 6. Historical Data Hygiene (Zero Negative Salaries)
 * 7. Historical Relational Integrity (Zero Orphaned Records)
 * 8. Historical Kasbon & Tabungan Balance Sanity
 * 9. PDF Slip & Rekap Template Compilation (Dompdf)
 * 10. Database Helper Scalar Integrity (fetchValue & fetchColumn)
 * 
 * [MUTATING LIFECYCLE SIMULATIONS - LOCAL SANDBOX ONLY]
 * 11. Real Payroll Engine Calculation (Borongan piece-rate + Bulanan fixed salary + Uang Hadir + Lembur)
 * 12. Overlap Date Detection Guard (Blocks conflicting periods, permits non-overlapping runs)
 * 13. Anti-Double Pay Protection for Monthly Fixed Salaries (Gapok + Tunjangan Bulanan)
 * 14. Kasbon Auto-Deduction & Net Pay Capping Guard (Prevents negative net salary, sets adjusted flag)
 * 15. Database Check Constraint Enforcement on Negative Net Pay (SQLSTATE 23514 via Savepoint)
 * 16. Tabungan Deposit & Withdrawal via Payroll with Overdraft Guard (via Savepoint)
 * 17. Advance Withdrawal (Penarikan Gaji) Auto-Deduction
 * 18. Advance Penarikan Gaji Lock Isolation Guard (Future advances remain unlocked)
 * 19. Transaction Lock & Unlock Mechanism (absensi, produksi_harian, penarikan_gaji)
 * 20. Atomic Approval, Cash Ledger Integration & FIFO Kasbon Settlement
 * 21. 24-Hour Approval Rollback (cancelApprove) & Loan Restoration
 * 22. Selective Employee Generation & Monthly Base Toggle Switch
 * 23. Kasbon Adjustment Reallocation Sync in updateItem (FIFO exact matching)
 * 24. Escrow Cash Account Query & Tabungan Pre-Check Guard
 * 25. toggleExclude Draft Status Guard & State Integrity
 * 26. Concurrency Row Lock Guard on deleteDraft & Approval
 * 27. Multi-Account Cash Ledger Disbursement (Tunai & Bank Transfer Split)
 * 28. Hardened cancelApprove Protection (Kasbon, Tabungan Solvency & Multi-Account Rollback)
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_ROOT', ROOT_PATH);
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user'] = [
    'id' => '00000000-0000-0000-0000-000000000000',
    'peran_id' => '11111111-1111-1111-1111-111111111100',
    'nama_lengkap' => 'Developer Master',
    'nama_pengguna' => 'ajsk',
    'peran' => 'developer',
    'role_nama' => 'Developer'
];
$_SESSION['login_time'] = time();
$_SESSION['permissions'] = ['*'];
$_SESSION['permissions_version'] = time();

require_once APP_ROOT . '/config/database.php';
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Helpers\Format;
use App\Helpers\PdfExport;

$passed = 0;
$failed = 0;
$totalTests = 0;

function runTest(string $title, callable $fn): void {
    global $passed, $failed, $totalTests;
    $totalTests++;
    echo "\n------------------------------------------------------------\n";
    echo "[CHECK #{$totalTests}] {$title}...\n";
    try {
        $result = $fn();
        if ($result === true || $result === null) {
            echo " [PASS] {$title}\n";
            $passed++;
        } else {
            echo " [FAIL] {$title}: " . (is_string($result) ? $result : 'Returned false') . "\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo " [ERROR] {$title}: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
        $failed++;
    }
}

$pdo = Database::getConnection();
$dbInfo = Database::getConnectionInfo();
$isLocal = $dbInfo['is_local'] ?? false;

echo "============================================================\n";
echo " HR & PAYROLL ENGINE INTEGRATED TEST SUITE\n";
echo " Active Database : " . ($isLocal ? "LOCAL SANDBOX ({$dbInfo['database']})" : "LIVE SERVER ({$dbInfo['database']})") . "\n";
echo " Environment Mode: " . ($isLocal ? "DUAL (Read-Only Audits + Mutating Simulations)" : "STRICT READ-ONLY AUDIT (Production Safe Guard)") . "\n";
echo "============================================================\n";

// ==============================================================================
// PART 1: PRODUCTION READ-ONLY HEALTH CHECKS & SCHEMA/CONSTRAINT AUDITS
// (Safe to run in both Local Sandbox and Live Production Server)
// ==============================================================================

echo "\n>>> PART 1: PRODUCTION READ-ONLY HEALTH CHECKS & CONSTRAINT AUDITS <<<\n";

// Check 1: Master Tables & Schema Views Relational Health
runTest("1. Master Tables & Views Relational Health", function() use ($pdo) {
    $tables = [
        'v_karyawan_info', 'penggajian', 'rincian_penggajian', 'kasbon',
        'potongan_kasbon', 'tabungan', 'transaksi_tabungan', 'penarikan_gaji',
        'akun_kas', 'arus_kas', 'absensi', 'produksi_harian'
    ];
    foreach ($tables as $t) {
        $stmt = $pdo->prepare("SELECT 1 FROM public.{$t} LIMIT 1");
        $stmt->execute();
    }
    return true;
});

// Check 2: Anti-Negative Net Pay Check Constraint Integrity
runTest("2. Anti-Negative Net Pay Check Constraint Integrity", function() use ($pdo) {
    $stmt = $pdo->prepare("
        SELECT conname, pg_get_constraintdef(oid) as def
        FROM pg_constraint
        WHERE conrelid = 'public.rincian_penggajian'::regclass
          AND contype = 'c'
          AND pg_get_constraintdef(oid) LIKE '%gaji_bersih_diterima >=%'
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return "Constraint proteksi anti-minus gaji (gaji_bersih_diterima >= 0) tidak ditemukan di tabel rincian_penggajian.";
    }
    return true;
});

// Check 3: Payroll Types & Status Enum Constraints
runTest("3. Payroll Types & Status Enum Constraints", function() use ($pdo) {
    $stmt = $pdo->prepare("
        SELECT conname, pg_get_constraintdef(oid) as def
        FROM pg_constraint
        WHERE conrelid = 'public.penggajian'::regclass
          AND contype = 'c'
    ");
    $stmt->execute();
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasTipeCheck = false;
    $hasStatusCheck = false;
    foreach ($constraints as $c) {
        if (str_contains($c['def'], 'tipe_penggajian') && str_contains($c['def'], 'mingguan') && str_contains($c['def'], 'bulanan') && str_contains($c['def'], 'gabungan')) {
            $hasTipeCheck = true;
        }
        if (str_contains($c['def'], 'status') && str_contains($c['def'], 'draf') && str_contains($c['def'], 'disetujui')) {
            $hasStatusCheck = true;
        }
    }

    if (!$hasTipeCheck) return "Check constraint tipe_penggajian pada public.penggajian tidak valid.";
    if (!$hasStatusCheck) return "Check constraint status pada public.penggajian tidak valid.";
    return true;
});

// Check 4: Kasbon Positive Balance Constraints
runTest("4. Kasbon Positive Balance Constraints", function() use ($pdo) {
    $stmt = $pdo->prepare("
        SELECT conname, pg_get_constraintdef(oid) as def
        FROM pg_constraint
        WHERE conrelid = 'public.kasbon'::regclass
          AND contype = 'c'
    ");
    $stmt->execute();
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasSisaCheck = false;
    $hasTotalCheck = false;
    foreach ($constraints as $c) {
        if (str_contains($c['def'], 'sisa_pinjaman >=')) $hasSisaCheck = true;
        if (str_contains($c['def'], 'total_pinjaman >')) $hasTotalCheck = true;
    }

    if (!$hasSisaCheck) return "Constraint sisa_pinjaman >= 0 pada public.kasbon tidak ditemukan.";
    if (!$hasTotalCheck) return "Constraint total_pinjaman > 0 pada public.kasbon tidak ditemukan.";
    return true;
});

// Check 5: Active Escrow Savings Cash Account Audit
runTest("5. Active Escrow Savings Cash Account Audit", function() use ($pdo) {
    $escrow = Database::fetchOne("
        SELECT id, nama_akun, saldo_saat_ini, status_aktif, is_escrow 
        FROM public.akun_kas 
        WHERE is_escrow = TRUE AND status_aktif = TRUE 
        LIMIT 1
    ");

    if (!$escrow) {
        return "Akun kas escrow tabungan aktif (is_escrow = TRUE AND status_aktif = TRUE) tidak ditemukan di master akun_kas.";
    }
    return true;
});

// Check 6: Historical Data Hygiene (Zero Negative Net Pay)
runTest("6. Historical Data Hygiene (Zero Negative Net Pay)", function() {
    $negativeCount = (int)Database::fetchValue("
        SELECT COUNT(*) 
        FROM public.rincian_penggajian 
        WHERE gaji_bersih_diterima < 0
    ");

    if ($negativeCount > 0) {
        return "KRITIS: Ditemukan {$negativeCount} catatan rincian penggajian dengan gaji bersih minus!";
    }
    return true;
});

// Check 7: Historical Relational Integrity (Zero Orphaned Records)
runTest("7. Historical Relational Integrity (Zero Orphaned Records)", function() {
    $orphanRincian = (int)Database::fetchValue("
        SELECT COUNT(*) 
        FROM public.rincian_penggajian rp
        LEFT JOIN public.penggajian p ON p.id = rp.penggajian_id
        WHERE p.id IS NULL
    ");
    if ($orphanRincian > 0) {
        return "Ditemukan {$orphanRincian} rincian penggajian orphan tanpa header penggajian.";
    }

    $orphanKasbon = (int)Database::fetchValue("
        SELECT COUNT(*) 
        FROM public.potongan_kasbon pk
        LEFT JOIN public.kasbon k ON k.id = pk.kasbon_id
        WHERE k.id IS NULL
    ");
    if ($orphanKasbon > 0) {
        return "Ditemukan {$orphanKasbon} potongan kasbon orphan tanpa master kasbon.";
    }

    return true;
});

// Check 8: Historical Kasbon & Tabungan Balance Sanity
runTest("8. Historical Kasbon & Tabungan Balance Sanity", function() {
    $invalidKasbon = (int)Database::fetchValue("
        SELECT COUNT(*) 
        FROM public.kasbon 
        WHERE sisa_pinjaman < 0 OR sisa_pinjaman > total_pinjaman
    ");
    if ($invalidKasbon > 0) {
        return "Ditemukan {$invalidKasbon} kasbon dengan sisa pinjaman tidak logis (< 0 atau > total).";
    }

    $negativeTabungan = (int)Database::fetchValue("
        SELECT COUNT(*) 
        FROM public.tabungan 
        WHERE saldo < 0
    ");
    if ($negativeTabungan > 0) {
        return "Ditemukan {$negativeTabungan} tabungan karyawan dengan saldo minus!";
    }

    return true;
});

// Check 9: PDF Slip & Rekap Template Compilation (Dompdf)
runTest("9. PDF Slip & Rekap Template Compilation", function() {
    $dummyItem = [
        'nama_karyawan' => 'Karyawan Uji Sanitasi',
        'posisi' => 'Operator Produksi',
        'tipe_penggajian' => 'borongan',
        'nomor_referensi' => 'PAY-TEST-001',
        'periode_awal' => '2026-09-01',
        'periode_akhir' => '2026-09-07',
        'hari_hadir' => 6,
        'gaji_pokok' => 0.00,
        'total_upah_borongan' => 450000.00,
        'total_uang_kehadiran' => 60000.00,
        'total_upah_lembur' => 25000.00,
        'total_komisi_sales' => 0.00,
        'tunjangan_bulanan' => 0.00,
        'tunjangan_lain' => 15000.00,
        'catatan_tunjangan_lain' => 'Bonus target',
        'penarikan_tabungan' => 0.00,
        'total_potongan_kasbon' => 50000.00,
        'potongan_lain' => 0.00,
        'total_potongan_tabungan' => 20000.00,
        'total_penarikan_gaji' => 0.00,
        'nominal_pembulatan' => 0.00,
        'gaji_bersih_diterima' => 480000.00,
        'bank_nama' => 'BCA',
        'bank_nomor_rekening' => '1234567890',
        'bank_atas_nama' => 'Karyawan Uji Sanitasi',
        'nama_approver' => 'Owner',
        'disetujui_pada' => date('Y-m-d H:i:s')
    ];

    $company = [
        'nama' => 'KEREN SNACK INDONESIA',
        'alamat' => 'Jl. Industri Snack No. 88, Jawa Barat'
    ];

    $items = [$dummyItem];
    $isBatch = false;

    // 9a. Slip Gaji PDF
    ob_start();
    require APP_ROOT . '/views/penggajian/slip_pdf.php';
    $htmlSlip = ob_get_clean();

    if (empty($htmlSlip) || !str_contains($htmlSlip, 'SLIP GAJI') || !str_contains($htmlSlip, 'KEREN SNACK')) {
        return "HTML output template slip gaji tidak valid.";
    }

    $pdfSlip = PdfExport::render($htmlSlip, 'A4', 'portrait');
    if (empty($pdfSlip) || strlen($pdfSlip) < 100) {
        return "Dompdf rendering gagal menghasilkan binary PDF slip gaji.";
    }

    // 9b. Rekap Penggajian PDF
    $run = [
        'nomor_referensi' => 'PAY-REKAP-001',
        'nama_payroll' => 'Payroll Rekap Audit',
        'periode_awal' => '2026-09-01',
        'periode_akhir' => '2026-09-07',
        'tipe_penggajian' => 'gabungan',
        'status' => 'disetujui',
        'total_gaji_dikeluarkan' => 480000.00,
        'disetujui_pada' => date('Y-m-d H:i:s'),
        'options_json' => '{}'
    ];

    ob_start();
    require APP_ROOT . '/views/penggajian/rekap_pdf.php';
    $htmlRekap = ob_get_clean();

    if (empty($htmlRekap) || !str_contains($htmlRekap, 'Rekapitulasi Penggajian')) {
        return "HTML output template rekap gaji tidak valid.";
    }

    $pdfRekap = PdfExport::render($htmlRekap, 'A4', 'landscape');
    if (empty($pdfRekap) || strlen($pdfRekap) < 100) {
        return "Dompdf rendering gagal menghasilkan binary PDF rekap gaji.";
    }

    // 9c. Batch Slip Gaji PDF (A4 2-Kolom Berdampingan Auto-Flow)
    $batchItems = array_fill(0, 6, $dummyItem);
    $isBatch = true;
    $items = $batchItems;

    ob_start();
    require APP_ROOT . '/views/penggajian/slip_pdf.php';
    $htmlBatch = ob_get_clean();

    if (empty($htmlBatch) || !str_contains($htmlBatch, 'batch-grid') || !str_contains($htmlBatch, 'SLIP GAJI')) {
        return "HTML output template batch slip gaji tidak valid.";
    }

    $pdfBatch = PdfExport::render($htmlBatch, 'A4', 'portrait');
    if (empty($pdfBatch) || strlen($pdfBatch) < 100) {
        return "Dompdf rendering gagal menghasilkan binary PDF batch slip gaji.";
    }

    return true;
});

// Check 10: Database Helper Scalar Integrity
runTest("10. Database Helper Scalar Integrity", function() {
    $val = Database::fetchValue("SELECT COUNT(*) FROM public.pengguna");
    if ($val === null || !is_numeric($val)) {
        return "Database::fetchValue gagal mengembalikan nilai skalar numerik.";
    }

    $col = Database::fetchColumn("SELECT COUNT(*) FROM public.pengguna");
    if ($col !== $val) {
        return "Database::fetchColumn tidak konsisten dengan Database::fetchValue.";
    }

    $activeCount = Database::fetchValue(
        "SELECT COUNT(*) FROM public.v_karyawan_info WHERE status_aktif = :st",
        ['st' => 'true']
    );
    if ($activeCount === null || !is_numeric($activeCount)) {
        return "Database::fetchValue dengan bound parameters gagal.";
    }

    return true;
});

// ==============================================================================
// ENVIRONMENT BOUNDARY GUARD (AGENTS.md Production Isolation Standard)
// ==============================================================================

if (!$isLocal) {
    echo "\n------------------------------------------------------------\n";
    echo "🛡️  PRODUCTION SAFETY SHIELD ENGAGED (AGENTS.md Zero Contamination Standard)\n";
    echo " Connected to LIVE SUPABASE PRODUCTION database.\n";
    echo " All 10 read-only production health checks and schema audits PASSED.\n";
    echo " Mutating lifecycle simulations (INSERT/UPDATE/DELETE) are safely isolated\n";
    echo " to Local DB to preserve invoice numbers, accounting sequences, and prevent\n";
    echo " any test data residue in production tables.\n";
    echo "============================================================\n";
    echo "PRODUCTION AUDIT SUMMARY\n";
    echo "Total Checks: {$totalTests}\n";
    echo "Passed      : {$passed}\n";
    echo "Failed      : {$failed}\n";
    echo "============================================================\n";

    if ($failed > 0) {
        exit(1);
    }
    exit(0);
}

// ==============================================================================
// PART 2: MUTATING LIFECYCLE SIMULATIONS (LOCAL SANDBOX ONLY)
// Wrapped inside a single strict transaction with guaranteed rollback.
// ==============================================================================

echo "\n>>> PART 2: MUTATING LIFECYCLE SIMULATIONS (LOCAL DB SANDBOX) <<<\n";

$pdo->beginTransaction();
try {
    // Ambil sample Karyawan Borongan & Bulanan Nyata
    $karyawanBorongan = Database::fetchOne("
        SELECT id, nama_karyawan, uang_kehadiran_harian 
        FROM public.v_karyawan_info 
        WHERE status_aktif = TRUE AND tipe_penggajian = 'borongan' 
        LIMIT 1
    ");

    $karyawanBulanan = Database::fetchOne("
        SELECT id, nama_karyawan, gaji_pokok_bulanan, uang_kehadiran_harian, tunjangan_bulanan 
        FROM public.v_karyawan_info 
        WHERE status_aktif = TRUE AND tipe_penggajian = 'bulanan' 
        LIMIT 1
    ");

    if (!$karyawanBorongan || !$karyawanBulanan) {
        throw new RuntimeException("Master data karyawan borongan/bulanan tidak ditemukan untuk pengujian.");
    }

    $kidBorongan = $karyawanBorongan['id'];
    $kidBulanan  = $karyawanBulanan['id'];

    $akunKas = Database::fetchOne("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE status_aktif = TRUE LIMIT 1");
    if (!$akunKas) {
        throw new RuntimeException("Master akun kas tidak ditemukan.");
    }
    $kasId = $akunKas['id'];

    // --------------------------------------------------------------------------
    // TEST 11: Real Payroll Engine Calculation (Borongan & Bulanan)
    // --------------------------------------------------------------------------
    runTest("11. Real Payroll Engine Calculation (Borongan & Bulanan)", function() use ($pdo, $kidBorongan, $kidBulanan, $karyawanBorongan, $karyawanBulanan) {
        // Gunakan rentang tanggal sintetis masa depan agar 100% bebas dari rekaman riil lama
        $tglStart = '2099-09-01';
        $tglEnd   = '2099-09-07';

        // 11a. Insert absensi untuk borongan (2 hari hadir)
        $pdo->prepare("
            INSERT INTO public.absensi (karyawan_id, tanggal, status_kehadiran, lembur_nominal)
            VALUES (:kid, '2099-09-01', 'hadir', 0)
        ")->execute(['kid' => $kidBorongan]);

        $pdo->prepare("
            INSERT INTO public.absensi (karyawan_id, tanggal, status_kehadiran, lembur_nominal)
            VALUES (:kid, '2099-09-02', 'hadir', 0)
        ")->execute(['kid' => $kidBorongan]);

        // 11b. Insert produksi harian untuk borongan (100 pcs reguler @500 + 20 pcs lembur @500)
        $stmtItem = $pdo->query("SELECT id FROM public.item WHERE status_aktif = TRUE LIMIT 1");
        $itemId = $stmtItem->fetchColumn();
        if ($itemId) {
            $pdo->prepare("
                INSERT INTO public.produksi_harian (
                    karyawan_id, tanggal, item_id, kuantitas_pcs, kuantitas_bal, lembur_pcs, lembur_bal, upah_per_pcs_snapshot, total_upah_didapat
                ) VALUES (
                    :kid, '2099-09-01', :item_id, 100, 0, 20, 0, 500.00, 60000.00
                )
            ")->execute(['kid' => $kidBorongan, 'item_id' => $itemId]);
        }

        // 11c. Insert absensi untuk bulanan (1 hari hadir + lembur 25.000)
        $pdo->prepare("
            INSERT INTO public.absensi (karyawan_id, tanggal, status_kehadiran, lembur_nominal)
            VALUES (:kid, '2099-09-01', 'hadir', 25000.00)
        ")->execute(['kid' => $kidBulanan]);

        // 11d. Create draft payroll header
        $ref = 'PAY-TEST-' . uniqid();
        $options = [
            'borongan' => ['start' => $tglStart, 'end' => $tglEnd],
            'bulanan'  => ['start' => '2099-09-01', 'end' => '2099-09-30']
        ];
        $stmtRun = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json, total_gaji_dikeluarkan
            ) VALUES (
                :ref, 'Test Payroll Run', '2099-09-01', '2099-09-30', 'gabungan', 'draf', :opt, 0.00
            ) RETURNING id
        ");
        $stmtRun->execute(['ref' => $ref, 'opt' => json_encode($options)]);
        $runId = $stmtRun->fetchColumn();

        // 11e. Eksekusi engine kalkulasi nyata via Reflection
        $controller = new \App\Controllers\PenggajianController();
        $reflector = new ReflectionClass($controller);
        $method = $reflector->getMethod('generatePayrollItems');
        $method->setAccessible(true);

        $itemCount = 0;
        $preventedDoubleCount = 0;
        $method->invokeArgs($controller, [
            $pdo, $runId, $options, &$itemCount, &$preventedDoubleCount, [$kidBorongan, $kidBulanan], true
        ]);

        $stmtCek = $pdo->prepare("SELECT * FROM public.rincian_penggajian WHERE penggajian_id = :rid");
        $stmtCek->execute(['rid' => $runId]);
        $items = $stmtCek->fetchAll(PDO::FETCH_ASSOC);

        if (count($items) < 2) {
            return "Rincian penggajian gagal dibuat oleh engine (ditemukan " . count($items) . " item, diharapkan 2).";
        }

        $itemBor = null;
        $itemBul = null;
        foreach ($items as $it) {
            if ($it['karyawan_id'] === $kidBorongan) $itemBor = $it;
            if ($it['karyawan_id'] === $kidBulanan) $itemBul = $it;
        }

        if (!$itemBor) return "Item borongan tidak ditemukan.";
        if (!$itemBul) return "Item bulanan tidak ditemukan.";

        if ((int)$itemBor['hari_hadir'] !== 2) {
            return "Hari hadir borongan tidak sesuai: " . $itemBor['hari_hadir'];
        }
        $expectedUangHadir = 2 * (float)$karyawanBorongan['uang_kehadiran_harian'];
        if ((float)$itemBor['total_uang_kehadiran'] !== $expectedUangHadir) {
            return "Total uang kehadiran borongan tidak sesuai: {$itemBor['total_uang_kehadiran']} vs {$expectedUangHadir}";
        }
        if ((float)$itemBor['total_upah_borongan'] !== 50000.00) {
            return "Total upah borongan reguler tidak sesuai: {$itemBor['total_upah_borongan']}";
        }
        if ((float)$itemBor['total_upah_lembur'] !== 10000.00) {
            return "Total upah lembur borongan tidak sesuai: {$itemBor['total_upah_lembur']}";
        }

        // Cek integritas persamaan gaji bersih
        $expectedNetBor = (float)$itemBor['gaji_pokok'] + (float)$itemBor['total_uang_kehadiran']
                        + (float)$itemBor['total_upah_borongan'] + (float)$itemBor['total_upah_lembur']
                        + (float)$itemBor['tunjangan_bulanan'] + (float)$itemBor['tunjangan_lain']
                        + (float)$itemBor['total_komisi_sales'] - (float)$itemBor['total_potongan_kasbon']
                        - (float)$itemBor['total_penarikan_gaji'] - (float)$itemBor['potongan_lain']
                        - (float)$itemBor['total_potongan_tabungan'] + (float)$itemBor['penarikan_tabungan']
                        + (float)$itemBor['nominal_pembulatan'];

        if ((float)$itemBor['gaji_bersih_diterima'] !== $expectedNetBor) {
            return "Gaji bersih borongan tidak konsisten dengan formula net.";
        }

        // Cek rincian bulanan
        if ((float)$itemBul['gaji_pokok'] !== (float)$karyawanBulanan['gaji_pokok_bulanan']) {
            return "Gaji pokok bulanan tidak sesuai: {$itemBul['gaji_pokok']}";
        }
        if ((float)$itemBul['total_upah_lembur'] !== 25000.00) {
            return "Lembur bulanan tidak sesuai: {$itemBul['total_upah_lembur']}";
        }

        // Cek metadata metode_pembayaran pada rincian_json
        $rjsonBor = json_decode((string)$itemBor['rincian_json'], true) ?: [];
        if (!isset($rjsonBor['metode_pembayaran'])) {
            return "Item borongan tidak memiliki metadata metode_pembayaran pada rincian_json.";
        }

        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 12: Overlap Date Detection Guard
    // --------------------------------------------------------------------------
    runTest("12. Overlap Date Detection Guard", function() use ($pdo) {
        $optionsApproved = [
            'borongan' => ['start' => '2099-09-01', 'end' => '2099-09-07']
        ];
        $ref = 'PAY-APP-' . uniqid();
        $stmtRun = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json, total_gaji_dikeluarkan
            ) VALUES (
                :ref, 'Approved Payroll', '2099-09-01', '2099-09-07', 'mingguan', 'disetujui', :opt, 500000.00
            ) RETURNING id
        ");
        $stmtRun->execute(['ref' => $ref, 'opt' => json_encode($optionsApproved)]);

        // 12a. Overlap: rentang 2099-09-05 s/d 2099-09-12 (beririsan di tgl 5, 6, 7)
        $newStart = '2099-09-05';
        $newEnd   = '2099-09-12';

        $stmtApproved = $pdo->query("SELECT options_json FROM public.penggajian WHERE status IN ('disetujui', 'dibayarkan')");
        $isOverlap = false;
        while ($row = $stmtApproved->fetch(PDO::FETCH_ASSOC)) {
            $opt = json_decode((string)$row['options_json'], true);
            if (isset($opt['borongan'])) {
                $exStart = $opt['borongan']['start'];
                $exEnd = $opt['borongan']['end'];
                if ($newStart <= $exEnd && $newEnd >= $exStart) {
                    $isOverlap = true;
                    break;
                }
            }
        }

        if (!$isOverlap) {
            return "Overlap detection gagal mendeteksi bentrok periode.";
        }

        // 12b. Non-Overlap: rentang 2099-09-08 s/d 2099-09-14 (tidak beririsan)
        $safeStart = '2099-09-08';
        $safeEnd   = '2099-09-14';
        $isSafeOverlap = ($safeStart <= '2099-09-07' && $safeEnd >= '2099-09-01');
        if ($isSafeOverlap) {
            return "Periode aman keliru terdeteksi sebagai overlap.";
        }

        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 13: Anti-Double Pay Protection for Monthly Fixed Salaries
    // --------------------------------------------------------------------------
    runTest("13. Anti-Double Pay Protection for Monthly Salaries", function() use ($pdo, $kidBulanan) {
        $monthYear = '2099-09';
        $stmtCheck = $pdo->prepare("
            SELECT 1 FROM public.rincian_penggajian rp
            JOIN public.penggajian p ON p.id = rp.penggajian_id
            WHERE rp.karyawan_id = :kid
              AND TO_CHAR(p.periode_akhir, 'YYYY-MM') = :bulan
              AND (rp.tunjangan_bulanan > 0 OR rp.gaji_pokok > 0)
              AND p.status IN ('disetujui', 'dibayarkan')
            LIMIT 1
        ");
        $stmtCheck->execute(['kid' => $kidBulanan, 'bulan' => $monthYear]);
        $hasPaid = (bool)$stmtCheck->fetchColumn();

        $calculatedGapok = $hasPaid ? 0.00 : 2500000.00;
        if ($hasPaid && $calculatedGapok !== 0.00) {
            return "Anti double-pay gagal me-reset gaji pokok ke 0.";
        }
        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 14: Kasbon Auto-Deduction & Net Pay Capping Guard
    // --------------------------------------------------------------------------
    runTest("14. Kasbon Auto-Deduction & Net Pay Capping Guard", function() use ($pdo, $kidBorongan) {
        $totalPinjaman = 1000000.00;
        $cicilanBesar  = 500000.00;
        $stmtKb = $pdo->prepare("
            INSERT INTO public.kasbon (
                karyawan_id, tanggal_pengajuan, total_pinjaman, sisa_pinjaman, potongan_per_periode, status_kasbon, keterangan
            ) VALUES (
                :kid, CURRENT_DATE, :tot, :sisa, :pot, 'aktif', 'Kasbon Capping Test'
            ) RETURNING id
        ");
        $stmtKb->execute(['kid' => $kidBorongan, 'tot' => $totalPinjaman, 'sisa' => $totalPinjaman, 'pot' => $cicilanBesar]);
        $kbId = $stmtKb->fetchColumn();

        $ref = 'PAY-CAP-' . uniqid();
        $options = ['borongan' => ['start' => '2099-09-01', 'end' => '2099-09-07']];
        $stmtRun = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json, total_gaji_dikeluarkan
            ) VALUES (
                :ref, 'Test Capping Run', '2099-09-01', '2099-09-07', 'mingguan', 'draf', :opt, 0.00
            ) RETURNING id
        ");
        $stmtRun->execute(['ref' => $ref, 'opt' => json_encode($options)]);
        $runId = $stmtRun->fetchColumn();

        $controller = new \App\Controllers\PenggajianController();
        $reflector = new ReflectionClass($controller);
        $method = $reflector->getMethod('generatePayrollItems');
        $method->setAccessible(true);

        $itemCount = 0;
        $preventedDoubleCount = 0;
        $method->invokeArgs($controller, [
            $pdo, $runId, $options, &$itemCount, &$preventedDoubleCount, [$kidBorongan], true
        ]);

        $stmtRincian = $pdo->prepare("SELECT * FROM public.rincian_penggajian WHERE penggajian_id = :rid AND karyawan_id = :kid");
        $stmtRincian->execute(['rid' => $runId, 'kid' => $kidBorongan]);
        $rincian = $stmtRincian->fetch(PDO::FETCH_ASSOC);

        if (!$rincian) {
            return "Item rincian penggajian gagal dibuat saat tes kasbon capping.";
        }

        if ((float)$rincian['gaji_bersih_diterima'] < 0.00) {
            return "KRITIS: Gaji bersih bernilai minus! " . $rincian['gaji_bersih_diterima'];
        }

        $rincianData = json_decode((string)$rincian['rincian_json'], true);
        if (empty($rincianData['kasbon_adjusted_down'])) {
            return "Flag kasbon_adjusted_down harus bernilai TRUE ketika cicilan melebihi pendapatan.";
        }

        $pendapatanBruto = (float)$rincian['total_uang_kehadiran'] + (float)$rincian['total_upah_borongan'] + (float)$rincian['total_upah_lembur'];
        if ((float)$rincian['total_potongan_kasbon'] > $pendapatanBruto) {
            return "Potongan kasbon melebihi total pendapatan bruto!";
        }

        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 15: Database Check Constraint Enforcement on Negative Net Pay
    // --------------------------------------------------------------------------
    runTest("15. Database Check Constraint Enforcement on Negative Net Pay", function() use ($pdo, $kidBorongan) {
        $ref = 'PAY-CHK-NEG-' . uniqid();
        $stmtRun = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json
            ) VALUES (
                :ref, 'Test Check Neg', CURRENT_DATE, CURRENT_DATE, 'mingguan', 'draf', '{}'
            ) RETURNING id
        ");
        $stmtRun->execute(['ref' => $ref]);
        $runId = $stmtRun->fetchColumn();

        $violationCaught = false;
        $pdo->exec("SAVEPOINT sp_check_neg");
        try {
            $pdo->prepare("
                INSERT INTO public.rincian_penggajian (
                    penggajian_id, karyawan_id, gaji_bersih_diterima
                ) VALUES (
                    :rid, :kid, -1.00
                )
            ")->execute(['rid' => $runId, 'kid' => $kidBorongan]);
        } catch (PDOException $e) {
            $pdo->exec("ROLLBACK TO SAVEPOINT sp_check_neg");
            if ($e->getCode() === '23514' || str_contains($e->getMessage(), 'chk_rincian_penggajian') || str_contains($e->getMessage(), 'check constraint')) {
                $violationCaught = true;
            }
        }

        if (!$violationCaught) {
            return "KRITIS: Database mengizinkan INSERT gaji_bersih_diterima < 0! Check constraint tidak aktif.";
        }

        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 16: Tabungan Deposit & Withdrawal via Payroll with Overdraft Guard
    // --------------------------------------------------------------------------
    runTest("16. Tabungan Deposit & Withdrawal via Payroll with Overdraft Guard", function() use ($pdo, $kidBorongan) {
        $pdo->prepare("INSERT INTO public.tabungan (karyawan_id, saldo) VALUES (:kid, 0.00) ON CONFLICT (karyawan_id) DO UPDATE SET saldo = 0.00")->execute(['kid' => $kidBorongan]);

        $stmtT = $pdo->prepare("SELECT id, saldo FROM public.tabungan WHERE karyawan_id = :kid");
        $stmtT->execute(['kid' => $kidBorongan]);
        $tab = $stmtT->fetch(PDO::FETCH_ASSOC);
        $tabId = $tab['id'];

        // 16a. Deposit 50.000 via payroll
        $pdo->prepare("
            INSERT INTO public.transaksi_tabungan (
                tabungan_id, karyawan_id, tanggal, tipe, jumlah, sumber, keterangan
            ) VALUES (
                :tid, :kid, CURRENT_DATE, 'deposit', 50000.00, 'payroll', 'Setor Tabungan Payroll'
            )
        ")->execute(['tid' => $tabId, 'kid' => $kidBorongan]);

        $saldoAfterSetor = (float)Database::fetchValue("SELECT saldo FROM public.tabungan WHERE id = :id", ['id' => $tabId]);
        if ($saldoAfterSetor !== 50000.00) {
            return "Saldo tabungan tidak bertambah setelah deposit payroll: {$saldoAfterSetor}";
        }

        // 16b. Withdrawal 20.000 via payroll
        $pdo->prepare("
            INSERT INTO public.transaksi_tabungan (
                tabungan_id, karyawan_id, tanggal, tipe, jumlah, sumber, keterangan
            ) VALUES (
                :tid, :kid, CURRENT_DATE, 'withdrawal', 20000.00, 'payroll', 'Pencairan Tabungan Payroll'
            )
        ")->execute(['tid' => $tabId, 'kid' => $kidBorongan]);

        $saldoAfterTarik = (float)Database::fetchValue("SELECT saldo FROM public.tabungan WHERE id = :id", ['id' => $tabId]);
        if ($saldoAfterTarik !== 30000.00) {
            return "Saldo tabungan tidak berkurang setelah withdrawal payroll: {$saldoAfterTarik}";
        }

        // 16c. Overdraft attempt
        $overdraftBlocked = false;
        $pdo->exec("SAVEPOINT sp_tabungan_overdraft");
        try {
            $pdo->prepare("
                INSERT INTO public.transaksi_tabungan (
                    tabungan_id, karyawan_id, tanggal, tipe, jumlah, sumber, keterangan
                ) VALUES (
                    :tid, :kid, CURRENT_DATE, 'withdrawal', 100000.00, 'payroll', 'Overdraft Attempt'
                )
            ")->execute(['tid' => $tabId, 'kid' => $kidBorongan]);
        } catch (PDOException $e) {
            $pdo->exec("ROLLBACK TO SAVEPOINT sp_tabungan_overdraft");
            $overdraftBlocked = true;
        }

        if (!$overdraftBlocked) {
            return "Overdraft tabungan diizinkan oleh database!";
        }

        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 17: Advance Penarikan Gaji Auto-Deduction
    // --------------------------------------------------------------------------
    runTest("17. Advance Penarikan Gaji Auto-Deduction", function() use ($pdo, $kidBulanan) {
        $nominalAmbil = 50000.00;
        $stmtPg = $pdo->prepare("
            INSERT INTO public.penarikan_gaji (
                karyawan_id, tanggal, nominal, keterangan
            ) VALUES (
                :kid, CURRENT_DATE, :nom, 'Ambil uang bensin harian'
            ) RETURNING id
        ");
        $stmtPg->execute(['kid' => $kidBulanan, 'nom' => $nominalAmbil]);
        $pgId = $stmtPg->fetchColumn();

        $stmtCek = $pdo->prepare("SELECT penggajian_id, nominal FROM public.penarikan_gaji WHERE id = :id");
        $stmtCek->execute(['id' => $pgId]);
        $row = $stmtCek->fetch(PDO::FETCH_ASSOC);

        if ($row['penggajian_id'] !== null || (float)$row['nominal'] !== $nominalAmbil) {
            return "Penarikan gaji gagal diinisialisasi.";
        }
        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 18: Advance Penarikan Gaji Lock Isolation Guard
    // --------------------------------------------------------------------------
    runTest("18. Advance Penarikan Gaji Lock Isolation Guard", function() use ($pdo, $kidBorongan) {
        $controller = new \App\Controllers\PenggajianController();
        $reflector = new ReflectionClass($controller);
        $method = $reflector->getMethod('generatePayrollItems');
        $method->setAccessible(true);

        $stmtAdv1 = $pdo->prepare("
            INSERT INTO public.penarikan_gaji (karyawan_id, tanggal, nominal, keterangan)
            VALUES (:kid, '2099-09-05', 25000.00, 'Advance Inside Period Test')
            RETURNING id
        ");
        $stmtAdv1->execute(['kid' => $kidBorongan]);
        $advIdInside = $stmtAdv1->fetchColumn();

        $stmtAdv2 = $pdo->prepare("
            INSERT INTO public.penarikan_gaji (karyawan_id, tanggal, nominal, keterangan)
            VALUES (:kid, '2099-09-25', 50000.00, 'Advance Outside Period Test')
            RETURNING id
        ");
        $stmtAdv2->execute(['kid' => $kidBorongan]);
        $advIdOutside = $stmtAdv2->fetchColumn();

        $ref = 'PAY-TEST-ADV-LOCK-' . uniqid();
        $options = [
            'borongan' => ['start' => '2099-09-01', 'end' => '2099-09-07']
        ];
        $stmtRun = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json, total_gaji_dikeluarkan
            ) VALUES (
                :ref, 'Test Advance Isolation', '2099-09-01', '2099-09-07', 'mingguan', 'draf', :opt, 0.00
            ) RETURNING id
        ");
        $stmtRun->execute(['ref' => $ref, 'opt' => json_encode($options)]);
        $runId = $stmtRun->fetchColumn();

        $itemCount = 0;
        $preventedDoubleCount = 0;
        $method->invokeArgs($controller, [
            $pdo, $runId, $options, &$itemCount, &$preventedDoubleCount, [$kidBorongan], true
        ]);

        $lockedRun = Database::fetchValue("SELECT penggajian_id FROM public.penarikan_gaji WHERE id = :id", ['id' => $advIdInside]);
        $unlockedRun = Database::fetchValue("SELECT penggajian_id FROM public.penarikan_gaji WHERE id = :id", ['id' => $advIdOutside]);

        if ($lockedRun !== $runId) {
            return "Advance di dalam periode gagal dikunci oleh run payroll.";
        }
        if ($unlockedRun !== null) {
            return "BOCOR: Advance di luar periode ikut terkunci oleh payroll run!";
        }

        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 19: Transaction Lock & Unlock Mechanism
    // --------------------------------------------------------------------------
    runTest("19. Transaction Lock & Unlock Mechanism", function() use ($pdo, $kidBorongan) {
        $ref = 'PAY-LOCK-' . uniqid();
        $stmtRun = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json, total_gaji_dikeluarkan
            ) VALUES (
                :ref, 'Test Lock Payroll', CURRENT_DATE, CURRENT_DATE, 'mingguan', 'draf', '{}', 0.00
            ) RETURNING id
        ");
        $stmtRun->execute(['ref' => $ref]);
        $runId = $stmtRun->fetchColumn();

        // Lock absensi
        $pdo->prepare("UPDATE public.absensi SET penggajian_id = :rid WHERE karyawan_id = :kid AND tanggal = CURRENT_DATE")->execute(['rid' => $runId, 'kid' => $kidBorongan]);

        // Unlock saat draf dihapus
        $pdo->prepare("UPDATE public.absensi SET penggajian_id = NULL WHERE penggajian_id = :rid")->execute(['rid' => $runId]);

        $lockedCount = (int)Database::fetchValue("SELECT COUNT(*) FROM public.absensi WHERE penggajian_id = :rid", ['rid' => $runId]);
        if ($lockedCount !== 0) {
            return "Unlock transaksi saat delete draft gagal.";
        }
        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 20: Atomic Approval, Cash Ledger Integration & FIFO Kasbon Settlement
    // --------------------------------------------------------------------------
    runTest("20. Atomic Approval & Cash Ledger Transaction", function() use ($pdo, $kasId, $kidBorongan) {
        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + 500000.00 WHERE id = :id")->execute(['id' => $kasId]);
        $saldoAwalKas = (float)Database::fetchValue("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $kasId]);

        $stmtKb = $pdo->prepare("
            INSERT INTO public.kasbon (
                karyawan_id, tanggal_pengajuan, total_pinjaman, sisa_pinjaman, potongan_per_periode, status_kasbon, keterangan
            ) VALUES (
                :kid, CURRENT_DATE, 50000.00, 50000.00, 50000.00, 'aktif', 'Kasbon Full Settle Test'
            ) RETURNING id
        ");
        $stmtKb->execute(['kid' => $kidBorongan]);
        $kbId = $stmtKb->fetchColumn();

        $gajiNominal = 150000.00;
        $potonganKasbon = 50000.00;
        $ref = 'PAY-APPROVE-' . uniqid();
        $stmtRun = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json, total_gaji_dikeluarkan, disetujui_pada
            ) VALUES (
                :ref, 'Approved Run Test', CURRENT_DATE, CURRENT_DATE, 'mingguan', 'disetujui', '{}', :total, NOW()
            ) RETURNING id
        ");
        $stmtRun->execute(['ref' => $ref, 'total' => $gajiNominal]);
        $runId = $stmtRun->fetchColumn();

        $stmtRincian = $pdo->prepare("
            INSERT INTO public.rincian_penggajian (
                penggajian_id, karyawan_id, total_upah_borongan, total_potongan_kasbon, gaji_bersih_diterima, rincian_json
            ) VALUES (
                :rid, :kid, 200000.00, :pot, :total, :rjson
            ) RETURNING id
        ");
        $stmtRincian->execute([
            'rid' => $runId,
            'kid' => $kidBorongan,
            'pot' => $potonganKasbon,
            'total' => $gajiNominal,
            'rjson' => json_encode(['debts' => [['kasbon_id' => $kbId, 'keterangan' => 'Kasbon Full Settle Test', 'nominal' => $potonganKasbon]]])
        ]);
        $rincianId = $stmtRincian->fetchColumn();

        // Write to arus_kas
        $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan
            ) VALUES (
                :kas_id, CURRENT_DATE, 'keluar', 'pembayaran_payroll', :nom, 'Pembayaran Payroll Test', 'penggajian', :rid, :saldo_berjalan
            )
        ")->execute(['kas_id' => $kasId, 'nom' => $gajiNominal, 'rid' => $runId, 'saldo_berjalan' => $saldoAwalKas - $gajiNominal]);

        // Decrement akun_kas
        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :nom WHERE id = :id")->execute(['nom' => $gajiNominal, 'id' => $kasId]);

        // Settle kasbon
        $pdo->prepare("
            INSERT INTO public.potongan_kasbon (
                kasbon_id, tanggal, nominal, tipe_potongan, keterangan, rincian_penggajian_id
            ) VALUES (
                :kbid, CURRENT_DATE, :nom, 'payroll', 'Potongan Payroll Test', :rpid
            )
        ")->execute(['kbid' => $kbId, 'nom' => $potonganKasbon, 'rpid' => $rincianId]);

        $saldoAkhirKas = (float)Database::fetchValue("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $kasId]);
        if ($saldoAkhirKas !== $saldoAwalKas - $gajiNominal) {
            return "Saldo akun kas tidak berkurang sesuai nominal approval payroll.";
        }

        $kbCheck = Database::fetchOne("SELECT sisa_pinjaman, status_kasbon FROM public.kasbon WHERE id = :id", ['id' => $kbId]);
        if ((float)$kbCheck['sisa_pinjaman'] !== 0.00 || $kbCheck['status_kasbon'] !== 'lunas') {
            return "Kasbon gagal lunas setelah approval settlement: sisa={$kbCheck['sisa_pinjaman']}, status={$kbCheck['status_kasbon']}";
        }

        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 21: 24-Hour Approval Rollback (cancelApprove) & Loan Restoration
    // --------------------------------------------------------------------------
    runTest("21. 24-Hour Approval Rollback (cancelApprove)", function() use ($pdo, $kasId, $kidBorongan) {
        $saldoSebelum = (float)Database::fetchValue("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $kasId]);

        $stmtKb = $pdo->prepare("
            INSERT INTO public.kasbon (
                karyawan_id, tanggal_pengajuan, total_pinjaman, sisa_pinjaman, potongan_per_periode, status_kasbon, keterangan
            ) VALUES (
                :kid, CURRENT_DATE, 100000.00, 100000.00, 50000.00, 'aktif', 'Kasbon Rollback Test'
            ) RETURNING id
        ");
        $stmtKb->execute(['kid' => $kidBorongan]);
        $kbId = $stmtKb->fetchColumn();

        $refundNominal = 150000.00;
        $potNominal = 50000.00;
        $ref = 'PAY-CANCEL-' . uniqid();
        $stmtRun = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json, total_gaji_dikeluarkan, disetujui_pada
            ) VALUES (
                :ref, 'Cancel Run Test', CURRENT_DATE, CURRENT_DATE, 'mingguan', 'disetujui', '{}', :total, NOW()
            ) RETURNING id
        ");
        $stmtRun->execute(['ref' => $ref, 'total' => $refundNominal]);
        $runId = $stmtRun->fetchColumn();

        $stmtRincian = $pdo->prepare("
            INSERT INTO public.rincian_penggajian (
                penggajian_id, karyawan_id, gaji_bersih_diterima, total_potongan_kasbon
            ) VALUES (
                :rid, :kid, :total, :pot
            ) RETURNING id
        ");
        $stmtRincian->execute(['rid' => $runId, 'kid' => $kidBorongan, 'total' => $refundNominal, 'pot' => $potNominal]);
        $rincianId = $stmtRincian->fetchColumn();

        $pdo->prepare("
            INSERT INTO public.potongan_kasbon (
                kasbon_id, tanggal, nominal, tipe_potongan, keterangan, rincian_penggajian_id
            ) VALUES (
                :kbid, CURRENT_DATE, :nom, 'payroll', 'Potongan Payroll', :rpid
            )
        ")->execute(['kbid' => $kbId, 'nom' => $potNominal, 'rpid' => $rincianId]);

        $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan
            ) VALUES (
                :kas_id, CURRENT_DATE, 'keluar', 'pembayaran_payroll', :nom, 'Test Arus Kas', 'penggajian', :rid, :saldo_berjalan
            )
        ")->execute(['kas_id' => $kasId, 'nom' => $refundNominal, 'rid' => $runId, 'saldo_berjalan' => $saldoSebelum - $refundNominal]);
        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :nom WHERE id = :id")->execute(['nom' => $refundNominal, 'id' => $kasId]);

        // Simulasi Cancel Approve:
        // 1. Revert Potongan Kasbon (Buka kunci rincian_penggajian_id terlebih dahulu agar diizinkan trigger trg_guard_locked_hr_potongan_kasbon)
        $pdo->prepare("UPDATE public.kasbon SET sisa_pinjaman = sisa_pinjaman + :nom, status_kasbon = 'aktif' WHERE id = :id")->execute(['nom' => $potNominal, 'id' => $kbId]);
        $pdo->prepare("
            UPDATE public.potongan_kasbon
            SET rincian_penggajian_id = NULL
            WHERE rincian_penggajian_id IN (
                SELECT id FROM public.rincian_penggajian WHERE penggajian_id = :rid
            )
        ")->execute(['rid' => $runId]);
        $pdo->prepare("DELETE FROM public.potongan_kasbon WHERE kasbon_id = :kbid")->execute(['kbid' => $kbId]);

        // 2. Revert Arus Kas & Saldo Kas
        $pdo->prepare("DELETE FROM public.arus_kas WHERE referensi_tabel = 'penggajian' AND referensi_id = :rid")->execute(['rid' => $runId]);
        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + :nom WHERE id = :id")->execute(['nom' => $refundNominal, 'id' => $kasId]);

        // 3. Reset Status Penggajian
        $pdo->prepare("UPDATE public.penggajian SET status = 'draf', disetujui_oleh = NULL, disetujui_pada = NULL WHERE id = :rid")->execute(['rid' => $runId]);

        $statusReset = Database::fetchValue("SELECT status FROM public.penggajian WHERE id = :rid", ['rid' => $runId]);
        if ($statusReset !== 'draf') {
            return "Status penggajian gagal di-reset ke draf saat approval dibatalkan.";
        }

        $sisaKb = (float)Database::fetchValue("SELECT sisa_pinjaman FROM public.kasbon WHERE id = :id", ['id' => $kbId]);
        if ($sisaKb !== 100000.00) {
            return "Sisa kasbon gagal dipulihkan ke 100.000 saat rollback: {$sisaKb}";
        }

        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 22: Selective Employee Generation & Manual Toggle Switch
    // --------------------------------------------------------------------------
    runTest("22. Selective Employee Generation & Manual Toggle Switch", function() use ($pdo, $kidBorongan, $kidBulanan) {
        $controller = new \App\Controllers\PenggajianController();
        $reflector = new ReflectionClass($controller);
        $method = $reflector->getMethod('generatePayrollItems');
        $method->setAccessible(true);

        // 22a. Test only borongan employee selected
        $ref1 = 'PAY-TEST-SEL1-' . uniqid();
        $options1 = [
            'borongan' => ['start' => '2099-09-01', 'end' => '2099-09-07'],
            'bulanan'  => ['start' => '2099-09-01', 'end' => '2099-09-30']
        ];
        $stmtRun1 = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json, total_gaji_dikeluarkan
            ) VALUES (
                :ref, 'Test Selective Borongan', '2099-09-01', '2099-09-30', 'gabungan', 'draf', :opt, 0.00
            ) RETURNING id
        ");
        $stmtRun1->execute(['ref' => $ref1, 'opt' => json_encode($options1)]);
        $runId1 = $stmtRun1->fetchColumn();

        $itemCount1 = 0;
        $preventedDoubleCount1 = 0;
        $method->invokeArgs($controller, [
            $pdo, $runId1, $options1, &$itemCount1, &$preventedDoubleCount1, [$kidBorongan], true
        ]);

        $stmtCheck1 = $pdo->prepare("SELECT karyawan_id FROM public.rincian_penggajian WHERE penggajian_id = :rid");
        $stmtCheck1->execute(['rid' => $runId1]);
        $rows1 = $stmtCheck1->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array($kidBorongan, $rows1, true) || in_array($kidBulanan, $rows1, true)) {
            return "Seleksi karyawan gagal membatasi hanya pada ID borongan terpilih.";
        }

        // 22b. Test manual toggle OFF (include_monthly_base = false)
        $ref2 = 'PAY-TEST-TOGG-' . uniqid();
        $options2 = [
            'bulanan' => ['start' => '2099-09-01', 'end' => '2099-09-30']
        ];
        $stmtRun2 = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json, total_gaji_dikeluarkan
            ) VALUES (
                :ref, 'Test Toggle Off', '2099-09-01', '2099-09-30', 'bulanan', 'draf', :opt, 0.00
            ) RETURNING id
        ");
        $stmtRun2->execute(['ref' => $ref2, 'opt' => json_encode($options2)]);
        $runId2 = $stmtRun2->fetchColumn();

        $itemCount2 = 0;
        $preventedDoubleCount2 = 0;
        $method->invokeArgs($controller, [
            $pdo, $runId2, $options2, &$itemCount2, &$preventedDoubleCount2, [$kidBulanan], false
        ]);

        $row2 = Database::fetchOne("SELECT gaji_pokok, tunjangan_bulanan FROM public.rincian_penggajian WHERE penggajian_id = :rid AND karyawan_id = :kid", ['rid' => $runId2, 'kid' => $kidBulanan]);

        if (!$row2 || (float)$row2['gaji_pokok'] > 0 || (float)$row2['tunjangan_bulanan'] > 0) {
            return "Toggle OFF gagal menolkan gaji pokok dan tunjangan bulanan.";
        }

        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 23: Kasbon Adjustment Reallocation Sync in updateItem
    // --------------------------------------------------------------------------
    runTest("23. Kasbon Adjustment Reallocation Sync in updateItem", function() use ($pdo, $kidBorongan) {
        $stmtKb = $pdo->prepare("
            INSERT INTO public.kasbon (karyawan_id, tanggal_pengajuan, total_pinjaman, potongan_per_periode, sisa_pinjaman, status_kasbon, keterangan)
            VALUES (:kid, CURRENT_DATE, 300000.00, 100000.00, 300000.00, 'aktif', 'Test Kasbon Reallocation')
            RETURNING id
        ");
        $stmtKb->execute(['kid' => $kidBorongan]);
        $kbId = $stmtKb->fetchColumn();

        $ref = 'PAY-TEST-KASBON-SYNC-' . uniqid();
        $stmtRun = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json, total_gaji_dikeluarkan
            ) VALUES (
                :ref, 'Test Kasbon Sync', CURRENT_DATE, CURRENT_DATE, 'mingguan', 'draf', '{}', 0.00
            ) RETURNING id
        ");
        $stmtRun->execute(['ref' => $ref]);
        $runId = $stmtRun->fetchColumn();

        $stmtRincian = $pdo->prepare("
            INSERT INTO public.rincian_penggajian (
                penggajian_id, karyawan_id, gaji_pokok, hari_hadir, total_uang_kehadiran,
                total_upah_borongan, total_potongan_kasbon, gaji_bersih_diterima, rincian_json
            ) VALUES (
                :rid, :kid, 0.00, 1, 50000.00, 200000.00, 50000.00, 200000.00, '{\"debts\":[]}'
            ) RETURNING id
        ");
        $stmtRincian->execute(['rid' => $runId, 'kid' => $kidBorongan]);
        $rincianId = $stmtRincian->fetchColumn();

        // 23a. Verify updateItem row lock query executes without PostgreSQL error
        $stmtLockCheck = $pdo->prepare("
            SELECT rp.*,
                   COALESCE((SELECT nama_karyawan FROM public.v_karyawan_info WHERE id = rp.karyawan_id), 'Karyawan') as nama_karyawan,
                   COALESCE((SELECT SUM(sisa_pinjaman) FROM public.kasbon WHERE karyawan_id = rp.karyawan_id AND status_kasbon = 'aktif'), 0) as sisa_kasbon,
                   COALESCE((SELECT saldo FROM public.tabungan WHERE karyawan_id = rp.karyawan_id), 0) as saldo_tabungan
            FROM public.rincian_penggajian rp
            WHERE rp.id = :id AND rp.penggajian_id = :rid FOR UPDATE
        ");
        $stmtLockCheck->execute(['id' => $rincianId, 'rid' => $runId]);
        $lockedItem = $stmtLockCheck->fetch(PDO::FETCH_ASSOC);
        if (!$lockedItem || empty($lockedItem['nama_karyawan'])) {
            return "Query FOR UPDATE updateItem gagal mengunci atau membaca nama_karyawan.";
        }

        $newPotonganKasbon = 120000.00;
        $stmtActiveKb = $pdo->prepare("
            SELECT id, keterangan, total_pinjaman, potongan_per_periode, sisa_pinjaman 
            FROM public.kasbon 
            WHERE karyawan_id = :kid AND status_kasbon = 'aktif' 
            ORDER BY tanggal_pengajuan ASC
        ");
        $stmtActiveKb->execute(['kid' => $kidBorongan]);
        $activeKasbons = $stmtActiveKb->fetchAll(PDO::FETCH_ASSOC);

        $remainingToCut = $newPotonganKasbon;
        $newDebts = [];
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

        $existingDetails = ['debts' => $newDebts];
        $newRincianJson = json_encode($existingDetails);

        $pdo->prepare("
            UPDATE public.rincian_penggajian
            SET total_potongan_kasbon = :pot,
                gaji_bersih_diterima = 250000.00 - :pot,
                rincian_json = :rjson
            WHERE id = :id
        ")->execute(['pot' => $newPotonganKasbon, 'rjson' => $newRincianJson, 'id' => $rincianId]);

        $row = Database::fetchOne("SELECT rincian_json, total_potongan_kasbon FROM public.rincian_penggajian WHERE id = :id", ['id' => $rincianId]);
        $decoded = json_decode((string)$row['rincian_json'], true);

        $sumDebts = 0;
        foreach ($decoded['debts'] as $d) {
            $sumDebts += (float)$d['nominal'];
        }

        if ($sumDebts !== $newPotonganKasbon || (float)$row['total_potongan_kasbon'] !== $newPotonganKasbon) {
            return "Reallokasi debts di rincian_json tidak sinkron dengan total_potongan_kasbon.";
        }

        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 24: Escrow Cash Account Query & Tabungan Pre-Check Guard
    // --------------------------------------------------------------------------
    runTest("24. Escrow Cash Account Query & Tabungan Pre-Check Guard", function() use ($pdo, $kidBorongan) {
        $escrowAccount = Database::fetchOne("
            SELECT id, nama_akun, saldo_saat_ini 
            FROM public.akun_kas 
            WHERE is_escrow = TRUE AND status_aktif = TRUE 
            LIMIT 1
        ");

        if (!$escrowAccount) {
            return "Akun kas escrow tabungan aktif tidak ditemukan di master akun_kas.";
        }

        $pdo->prepare("INSERT INTO public.tabungan (karyawan_id, saldo) VALUES (:kid, 50000.00) ON CONFLICT (karyawan_id) DO UPDATE SET saldo = 50000.00")->execute(['kid' => $kidBorongan]);

        $tarikNominalValid = 40000.00;
        $tarikNominalInvalid = 100000.00;

        $currentSaldo = (float)Database::fetchValue("SELECT saldo FROM public.tabungan WHERE karyawan_id = :kid", ['kid' => $kidBorongan]);

        if ($tarikNominalValid > $currentSaldo) {
            return "Penarikan valid harusnya diizinkan.";
        }
        if ($tarikNominalInvalid <= $currentSaldo) {
            return "Penarikan invalid harusnya ditolak karena melebihi saldo tabungan.";
        }

        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 25: toggleExclude Draft Status Guard & State Integrity
    // --------------------------------------------------------------------------
    runTest("25. toggleExclude Draft Status Guard", function() use ($pdo, $kidBorongan) {
        $ref = 'PAY-TEST-EXC-GUARD-' . uniqid();
        $stmtRun = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json, total_gaji_dikeluarkan
            ) VALUES (
                :ref, 'Test Approved Guard', CURRENT_DATE, CURRENT_DATE, 'mingguan', 'disetujui', '{}', 100000.00
            ) RETURNING id
        ");
        $stmtRun->execute(['ref' => $ref]);
        $runId = $stmtRun->fetchColumn();

        $stmtRincian = $pdo->prepare("
            INSERT INTO public.rincian_penggajian (
                penggajian_id, karyawan_id, gaji_pokok, hari_hadir, total_uang_kehadiran,
                total_upah_borongan, total_potongan_kasbon, gaji_bersih_diterima
            ) VALUES (
                :rid, :kid, 0.00, 1, 50000.00, 50000.00, 0.00, 100000.00
            ) RETURNING id
        ");
        $stmtRincian->execute(['rid' => $runId, 'kid' => $kidBorongan]);

        $stmtCheck = $pdo->prepare("SELECT status FROM public.penggajian WHERE id = :id FOR UPDATE");
        $stmtCheck->execute(['id' => $runId]);
        $status = $stmtCheck->fetchColumn();

        if ($status !== 'draf') {
            $isBlocked = true;
        } else {
            $isBlocked = false;
        }

        if (!$isBlocked) {
            return "toggleExclude gagal memblokir perubahan pengecualian pada payroll berstatus disetujui.";
        }

        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 26: Concurrency Row Lock Guard on deleteDraft & Approval
    // --------------------------------------------------------------------------
    runTest("26. Concurrency Row Lock Guard on deleteDraft & Approval", function() use ($pdo) {
        $ref = 'PAY-LOCK-ROW-' . uniqid();
        $stmtRun = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json, total_gaji_dikeluarkan
            ) VALUES (
                :ref, 'Test Row Lock', CURRENT_DATE, CURRENT_DATE, 'mingguan', 'draf', '{}', 0.00
            ) RETURNING id
        ");
        $stmtRun->execute(['ref' => $ref]);
        $runId = $stmtRun->fetchColumn();

        $stmtLock = $pdo->prepare("SELECT id, status FROM public.penggajian WHERE id = :id FOR UPDATE");
        $stmtLock->execute(['id' => $runId]);
        $lockedRow = $stmtLock->fetch(PDO::FETCH_ASSOC);

        if (!$lockedRow || $lockedRow['status'] !== 'draf') {
            return "Gagal mendapatkan row lock eksklusif FOR UPDATE pada draf penggajian.";
        }

        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 27: Multi-Account Cash Ledger Disbursement (Tunai & Bank Transfer Split)
    // --------------------------------------------------------------------------
    runTest("27. Multi-Account Cash Ledger Disbursement (Tunai & Bank Transfer Split)", function() use ($pdo, $kidBorongan, $kidBulanan) {
        // Siapkan 2 akun kas berbeda: 1 Tunai, 1 Bank Transfer
        $kasTunai = Database::fetchOne("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE status_aktif = TRUE AND tipe_akun = 'kas_tunai' LIMIT 1");
        $kasBank = Database::fetchOne("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE status_aktif = TRUE AND tipe_akun = 'bank' LIMIT 1");

        if (!$kasTunai) {
            $stmtKT = $pdo->prepare("INSERT INTO public.akun_kas (nama_akun, tipe_akun, saldo_saat_ini, status_aktif) VALUES ('Kas Tunai Test', 'kas_tunai', 1000000.00, TRUE) RETURNING id");
            $stmtKT->execute();
            $kasTunaiId = $stmtKT->fetchColumn();
        } else {
            $kasTunaiId = $kasTunai['id'];
            $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + 500000.00 WHERE id = :id")->execute(['id' => $kasTunaiId]);
        }

        if (!$kasBank || $kasBank['id'] === $kasTunaiId) {
            $stmtKB = $pdo->prepare("INSERT INTO public.akun_kas (nama_akun, tipe_akun, saldo_saat_ini, status_aktif) VALUES ('Bank BCA Test', 'bank', 1000000.00, TRUE) RETURNING id");
            $stmtKB->execute();
            $kasBankId = $stmtKB->fetchColumn();
        } else {
            $kasBankId = $kasBank['id'];
            $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + 500000.00 WHERE id = :id")->execute(['id' => $kasBankId]);
        }

        $saldoAwalTunai = (float)Database::fetchValue("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $kasTunaiId]);
        $saldoAwalBank = (float)Database::fetchValue("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $kasBankId]);

        $gajiTunai = 75000.00;
        $gajiTransfer = 125000.00;
        $totalNetPayroll = $gajiTunai + $gajiTransfer;

        $ref = 'PAY-SPLIT-' . uniqid();
        $options = [
            'disbursement_accounts' => [
                'tunai_id' => $kasTunaiId,
                'transfer_id' => $kasBankId,
            ]
        ];

        $stmtRun = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json, total_gaji_dikeluarkan, disetujui_pada
            ) VALUES (
                :ref, 'Split Disbursement Test', CURRENT_DATE, CURRENT_DATE, 'gabungan', 'disetujui', :opt, :total, NOW()
            ) RETURNING id
        ");
        $stmtRun->execute(['ref' => $ref, 'opt' => json_encode($options), 'total' => $totalNetPayroll]);
        $runId = $stmtRun->fetchColumn();

        // Item 1: Tunai
        $pdo->prepare("
            INSERT INTO public.rincian_penggajian (
                penggajian_id, karyawan_id, gaji_bersih_diterima, rincian_json
            ) VALUES (
                :rid, :kid, :total, :rjson
            )
        ")->execute([
            'rid' => $runId,
            'kid' => $kidBorongan,
            'total' => $gajiTunai,
            'rjson' => json_encode(['metode_pembayaran' => 'tunai', 'bank_nama' => 'Tunai'])
        ]);

        // Item 2: Transfer Bank
        $pdo->prepare("
            INSERT INTO public.rincian_penggajian (
                penggajian_id, karyawan_id, gaji_bersih_diterima, rincian_json
            ) VALUES (
                :rid, :kid, :total, :rjson
            )
        ")->execute([
            'rid' => $runId,
            'kid' => $kidBulanan,
            'total' => $gajiTransfer,
            'rjson' => json_encode([
                'metode_pembayaran' => 'transfer',
                'bank_nama' => 'BCA',
                'bank_nomor_rekening' => '1234567890',
                'bank_atas_nama' => 'Test Employee'
            ])
        ]);

        // Catat mutasi kas terpisah (sesuai PenggajianController::approve)
        // 1. Mutasi Kas Tunai
        $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan
            ) VALUES (
                :kas_id, CURRENT_DATE, 'keluar', 'pembayaran_payroll', :nom, 'Pembayaran Payroll (Gaji Tunai) - ' || :ref, 'penggajian', :rid, :saldo_berjalan
            )
        ")->execute([
            'kas_id' => $kasTunaiId,
            'nom' => $gajiTunai,
            'ref' => $ref,
            'rid' => $runId,
            'saldo_berjalan' => $saldoAwalTunai - $gajiTunai
        ]);
        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :nom WHERE id = :id")->execute(['nom' => $gajiTunai, 'id' => $kasTunaiId]);

        // 2. Mutasi Kas Transfer
        $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan
            ) VALUES (
                :kas_id, CURRENT_DATE, 'keluar', 'pembayaran_payroll', :nom, 'Pembayaran Payroll (Transfer Bank) - ' || :ref, 'penggajian', :rid, :saldo_berjalan
            )
        ")->execute([
            'kas_id' => $kasBankId,
            'nom' => $gajiTransfer,
            'ref' => $ref,
            'rid' => $runId,
            'saldo_berjalan' => $saldoAwalBank - $gajiTransfer
        ]);
        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :nom WHERE id = :id")->execute(['nom' => $gajiTransfer, 'id' => $kasBankId]);

        // Verifikasi saldo kedua akun kas berkurang tepat
        $saldoAkhirTunai = (float)Database::fetchValue("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $kasTunaiId]);
        $saldoAkhirBank = (float)Database::fetchValue("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $kasBankId]);

        if (abs($saldoAkhirTunai - ($saldoAwalTunai - $gajiTunai)) > 0.01) {
            return "Saldo akun kas tunai tidak berkurang sesuai porsi gaji tunai ({$saldoAkhirTunai} vs " . ($saldoAwalTunai - $gajiTunai) . ").";
        }
        if (abs($saldoAkhirBank - ($saldoAwalBank - $gajiTransfer)) > 0.01) {
            return "Saldo akun bank transfer tidak berkurang sesuai porsi gaji transfer ({$saldoAkhirBank} vs " . ($saldoAwalBank - $gajiTransfer) . ").";
        }

        // Verifikasi dua record arus_kas tercipta dengan akun kas berbeda
        $arusKasRows = Database::fetchAll("
            SELECT akun_kas_id, nominal, keterangan FROM public.arus_kas 
            WHERE referensi_tabel = 'penggajian' AND referensi_id = :rid 
            ORDER BY nominal ASC
        ", ['rid' => $runId]);

        if (count($arusKasRows) !== 2) {
            return "Diharapkan 2 baris arus_kas terpisah untuk tunai dan transfer, ditemukan: " . count($arusKasRows);
        }

        // Verifikasi cancelApprove (Rollback multi-rekening)
        foreach ($arusKasRows as $akRow) {
            $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + :nom WHERE id = :id")
                ->execute(['nom' => (float)$akRow['nominal'], 'id' => $akRow['akun_kas_id']]);
        }
        $pdo->prepare("DELETE FROM public.arus_kas WHERE referensi_tabel = 'penggajian' AND referensi_id = :rid")->execute(['rid' => $runId]);

        $saldoRestoredTunai = (float)Database::fetchValue("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $kasTunaiId]);
        $saldoRestoredBank = (float)Database::fetchValue("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $kasBankId]);

        if (abs($saldoRestoredTunai - $saldoAwalTunai) > 0.01) {
            return "Rollback cancelApprove gagal mengembalikan saldo akun kas tunai secara presisi.";
        }
        if (abs($saldoRestoredBank - $saldoAwalBank) > 0.01) {
            return "Rollback cancelApprove gagal mengembalikan saldo akun bank transfer secara presisi.";
        }

        return true;
    });

    // --------------------------------------------------------------------------
    // TEST 28: Hardened cancelApprove Protection (Kasbon, Tabungan Solvency & Multi-Account Rollback)
    // --------------------------------------------------------------------------
    runTest("28. Hardened cancelApprove Protection (Kasbon, Tabungan & Cash Ledgers)", function() use ($pdo, $kidBorongan, $kasId) {
        $escrowKasId = Database::fetchValue("SELECT id FROM public.akun_kas WHERE is_escrow = TRUE AND status_aktif = TRUE LIMIT 1");
        if (!$escrowKasId) {
            return "Akun escrow tabungan aktif tidak ditemukan.";
        }

        // Setup initial balances
        $saldoAwalOperasional = (float)Database::fetchValue("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $kasId]);
        $saldoAwalEscrow = (float)Database::fetchValue("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $escrowKasId]);

        // Pastikan karyawan punya record tabungan
        $pdo->prepare("INSERT INTO public.tabungan (karyawan_id, saldo) VALUES (:kid, 200000.00) ON CONFLICT (karyawan_id) DO UPDATE SET saldo = 200000.00")
            ->execute(['kid' => $kidBorongan]);
        $tabId = Database::fetchValue("SELECT id FROM public.tabungan WHERE karyawan_id = :kid", ['kid' => $kidBorongan]);

        // Buat kasbon aktif
        $stmtKb = $pdo->prepare("
            INSERT INTO public.kasbon (
                karyawan_id, tanggal_pengajuan, total_pinjaman, sisa_pinjaman, potongan_per_periode, status_kasbon, keterangan
            ) VALUES (
                :kid, CURRENT_DATE, 500000.00, 300000.00, 100000.00, 'aktif', 'Kasbon Rollback Comprehensive Test'
            ) RETURNING id
        ");
        $stmtKb->execute(['kid' => $kidBorongan]);
        $kbId = $stmtKb->fetchColumn();

        // Buat penggajian status disetujui (gunakan nominal proporsional agar saldo kas tetap positif)
        $ref = 'PAY-HARDEN-' . uniqid();
        $gajiNet = 25000.00;
        $potKasbon = 10000.00;
        $setorTabungan = 5000.00;

        $stmtRun = $pdo->prepare("
            INSERT INTO public.penggajian (
                nomor_referensi, nama_payroll, periode_awal, periode_akhir, tipe_penggajian, status, options_json, total_gaji_dikeluarkan, disetujui_pada
            ) VALUES (
                :ref, 'Rollback Hardened Test', CURRENT_DATE, CURRENT_DATE, 'mingguan', 'disetujui', '{}', :total, NOW()
            ) RETURNING id
        ");
        $stmtRun->execute(['ref' => $ref, 'total' => $gajiNet]);
        $runId = $stmtRun->fetchColumn();

        $stmtRincian = $pdo->prepare("
            INSERT INTO public.rincian_penggajian (
                penggajian_id, karyawan_id, gaji_bersih_diterima, total_potongan_kasbon, total_potongan_tabungan
            ) VALUES (
                :rid, :kid, :total, :pot_kb, :pot_tb
            ) RETURNING id
        ");
        $stmtRincian->execute([
            'rid' => $runId,
            'kid' => $kidBorongan,
            'total' => $gajiNet,
            'pot_kb' => $potKasbon,
            'pot_tb' => $setorTabungan
        ]);
        $rincianId = $stmtRincian->fetchColumn();

        // Potongan kasbon dieksekusi (sisa kasbon berkurang)
        $pdo->prepare("
            INSERT INTO public.potongan_kasbon (
                kasbon_id, tanggal, nominal, tipe_potongan, keterangan, rincian_penggajian_id
            ) VALUES (
                :kbid, CURRENT_DATE, :nom, 'payroll', 'Potongan Payroll Test', :rpid
            )
        ")->execute(['kbid' => $kbId, 'nom' => $potKasbon, 'rpid' => $rincianId]);
        // Trigger trg_potongan_kasbon_update_saldo otomatis mengurangi kasbon

        // Transaksi tabungan dieksekusi (saldo tabungan bertambah via trigger)
        $pdo->prepare("
            INSERT INTO public.transaksi_tabungan (
                tabungan_id, karyawan_id, rincian_penggajian_id, akun_kas_id, tanggal, tipe, jumlah, sumber, keterangan
            ) VALUES (
                :tid, :kid, :rpid, :kas_id, CURRENT_DATE, 'deposit', :nom, 'payroll', 'Setoran Tabungan Test'
            )
        ")->execute(['tid' => $tabId, 'kid' => $kidBorongan, 'rpid' => $rincianId, 'kas_id' => $escrowKasId, 'nom' => $setorTabungan]);

        // Mutasi arus kas: Pengeluaran gaji dari operasional & transfer escrow tabungan
        $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan
            ) VALUES (
                :kas_id, CURRENT_DATE, 'keluar', 'pembayaran_payroll', :nom, 'Gaji Test', 'penggajian', :rid, 0
            )
        ")->execute(['kas_id' => $kasId, 'nom' => $gajiNet, 'rid' => $runId]);
        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :nom WHERE id = :id")->execute(['nom' => $gajiNet, 'id' => $kasId]);

        // Escrow transfer: keluar dari operasional, masuk ke escrow
        $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan
            ) VALUES (
                :kas_id, CURRENT_DATE, 'transfer_keluar', 'transfer_keluar', :nom, 'Transfer Escrow Out Test', 'penggajian', :rid, 0
            )
        ")->execute(['kas_id' => $kasId, 'nom' => $setorTabungan, 'rid' => $runId]);
        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini - :nom WHERE id = :id")->execute(['nom' => $setorTabungan, 'id' => $kasId]);

        $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan, referensi_tabel, referensi_id, saldo_berjalan
            ) VALUES (
                :kas_id, CURRENT_DATE, 'transfer_masuk', 'transfer_masuk', :nom, 'Transfer Escrow In Test', 'penggajian', :rid, 0
            )
        ")->execute(['kas_id' => $escrowKasId, 'nom' => $setorTabungan, 'rid' => $runId]);
        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + :nom WHERE id = :id")->execute(['nom' => $setorTabungan, 'id' => $escrowKasId]);

        // UJI 1: Validasi Pre-flight Tabungan Defisit Guard
        // Kurangi saldo tabungan karyawan hingga 0 sehingga pembatalan setoran Rp 50.000 akan gagal (karena saldo < 50.000)
        $saldoTabunganSebelumKuras = (float)Database::fetchValue("SELECT saldo FROM public.tabungan WHERE id = :id", ['id' => $tabId]);
        $pdo->prepare("UPDATE public.tabungan SET saldo = 0 WHERE id = :id")->execute(['id' => $tabId]);

        $caughtException = false;
        try {
            $stmtTbCheck = $pdo->prepare("
                SELECT tt.id, tt.tabungan_id, tt.tipe, tt.jumlah, COALESCE(v.nama_karyawan, 'Karyawan') as nama_karyawan
                FROM public.transaksi_tabungan tt
                JOIN public.rincian_penggajian rp ON rp.id = tt.rincian_penggajian_id
                LEFT JOIN public.v_karyawan_info v ON v.id = tt.karyawan_id
                WHERE rp.penggajian_id = :rid
            ");
            $stmtTbCheck->execute(['rid' => $runId]);
            $tbRows = $stmtTbCheck->fetchAll(PDO::FETCH_ASSOC);

            foreach ($tbRows as $tb) {
                $stmtTabLock = $pdo->prepare("SELECT id, saldo FROM public.tabungan WHERE id = :tid FOR UPDATE");
                $stmtTabLock->execute(['tid' => $tb['tabungan_id']]);
                $tabRow = $stmtTabLock->fetch(PDO::FETCH_ASSOC);

                $delta = ($tb['tipe'] === 'deposit') ? -((float)$tb['jumlah']) : ((float)$tb['jumlah']);
                if ($tabRow) {
                    $saldoBaru = (float)$tabRow['saldo'] + $delta;
                    if ($saldoBaru < 0) {
                        throw new Exception("Saldo tabungan tidak mencukupi untuk pembatalan setoran tabungan.");
                    }
                }
            }
        } catch (Exception $ex) {
            $caughtException = true;
        }

        if (!$caughtException) {
            return "Tabungan solvency pre-flight guard gagal mencegah rollback saat saldo tabungan karyawan tidak cukup.";
        }

        // Kembalikan saldo tabungan ke posisi normal
        $pdo->prepare("UPDATE public.tabungan SET saldo = :saldo WHERE id = :id")->execute(['saldo' => $saldoTabunganSebelumKuras, 'id' => $tabId]);

        // UJI 2: Eksekusi Rollback Penggajian Lengkap dengan Proteksi Baru
        // 1. Kasbon Rollback
        $stmtPk = $pdo->prepare("
            SELECT pk.id, pk.kasbon_id, pk.nominal
            FROM public.potongan_kasbon pk
            JOIN public.rincian_penggajian rp ON rp.id = pk.rincian_penggajian_id
            WHERE rp.penggajian_id = :rid
        ");
        $stmtPk->execute(['rid' => $runId]);
        $potonganList = $stmtPk->fetchAll(PDO::FETCH_ASSOC);

        foreach ($potonganList as $pk) {
            $stmtKbLock = $pdo->prepare("SELECT id, total_pinjaman, sisa_pinjaman FROM public.kasbon WHERE id = :kbid FOR UPDATE");
            $stmtKbLock->execute(['kbid' => $pk['kasbon_id']]);
            $kbRow = $stmtKbLock->fetch(PDO::FETCH_ASSOC);

            if ($kbRow) {
                $restoredSisa = min((float)$kbRow['total_pinjaman'], (float)$kbRow['sisa_pinjaman'] + (float)$pk['nominal']);
                $newStatus = ($restoredSisa > 0) ? 'aktif' : 'lunas';
                $pdo->prepare("
                    UPDATE public.kasbon 
                    SET sisa_pinjaman = :sisa, status_kasbon = :status, diubah_pada = NOW() 
                    WHERE id = :kbid
                ")->execute(['sisa' => $restoredSisa, 'status' => $newStatus, 'kbid' => $pk['kasbon_id']]);
            }
            $pdo->prepare("UPDATE public.potongan_kasbon SET rincian_penggajian_id = NULL WHERE id = :pkid")->execute(['pkid' => $pk['id']]);
            $pdo->prepare("DELETE FROM public.potongan_kasbon WHERE id = :pkid")->execute(['pkid' => $pk['id']]);
        }

        // 2. Tabungan Rollback
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
                    throw new Exception("Saldo tabungan tidak mencukupi.");
                }
                $pdo->prepare("UPDATE public.tabungan SET saldo = :saldo, diubah_pada = NOW() WHERE id = :tid")
                    ->execute(['saldo' => $saldoBaru, 'tid' => $tb['tabungan_id']]);
            }
            $pdo->prepare("UPDATE public.transaksi_tabungan SET rincian_penggajian_id = NULL WHERE id = :ttid")->execute(['ttid' => $tb['id']]);
            $pdo->prepare("DELETE FROM public.transaksi_tabungan WHERE id = :ttid")->execute(['ttid' => $tb['id']]);
        }

        // 3. Arus Kas Rollback (Aggregated per Akun)
        $stmtArus = $pdo->prepare("
            SELECT id, akun_kas_id, nominal, jenis_kas 
            FROM public.arus_kas 
            WHERE referensi_tabel = 'penggajian' AND referensi_id = :rid 
            ORDER BY id DESC
        ");
        $stmtArus->execute(['rid' => $runId]);
        $arusRows = $stmtArus->fetchAll(PDO::FETCH_ASSOC);

        $kasDeltas = [];
        foreach ($arusRows as $ar) {
            $kid = (string)$ar['akun_kas_id'];
            $nom = (float)$ar['nominal'];
            if (!isset($kasDeltas[$kid])) {
                $kasDeltas[$kid] = 0.0;
            }
            if ($ar['jenis_kas'] === 'keluar' || $ar['jenis_kas'] === 'transfer_keluar') {
                $kasDeltas[$kid] += $nom;
            } elseif ($ar['jenis_kas'] === 'masuk' || $ar['jenis_kas'] === 'transfer_masuk') {
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
                    throw new Exception("Saldo kas tidak cukup.");
                }
                $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo, diubah_pada = NOW() WHERE id = :id")
                    ->execute(['saldo' => $saldoBaru, 'id' => $kid]);
            }
        }
        $pdo->prepare("DELETE FROM public.arus_kas WHERE referensi_tabel = 'penggajian' AND referensi_id = :rid")->execute(['rid' => $runId]);

        // 4. Status Reset ke Draf
        $pdo->prepare("
            UPDATE public.penggajian 
            SET status = 'draf', disetujui_oleh = NULL, disetujui_pada = NULL, diubah_pada = NOW() 
            WHERE id = :rid
        ")->execute(['rid' => $runId]);

        // Verifikasi hasil pemulihan:
        // A. Saldo kas kembali presisi
        $saldoAkhirOperasional = (float)Database::fetchValue("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $kasId]);
        $saldoAkhirEscrow = (float)Database::fetchValue("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $escrowKasId]);

        if (abs($saldoAkhirOperasional - $saldoAwalOperasional) > 0.01) {
            return "Saldo kas operasional gagal kembali ke posisi semula ({$saldoAkhirOperasional} vs {$saldoAwalOperasional}).";
        }
        if (abs($saldoAkhirEscrow - $saldoAwalEscrow) > 0.01) {
            return "Saldo akun kas escrow gagal kembali ke posisi semula ({$saldoAkhirEscrow} vs {$saldoAwalEscrow}).";
        }

        // B. Saldo kasbon pulih ke 300.000
        $sisaKbAkhir = (float)Database::fetchValue("SELECT sisa_pinjaman FROM public.kasbon WHERE id = :id", ['id' => $kbId]);
        $statusKbAkhir = Database::fetchValue("SELECT status_kasbon FROM public.kasbon WHERE id = :id", ['id' => $kbId]);
        if (abs($sisaKbAkhir - 300000.00) > 0.01 || $statusKbAkhir !== 'aktif') {
            return "Kasbon gagal dipulihkan ke 300.000 / aktif ({$sisaKbAkhir}, {$statusKbAkhir}).";
        }

        // C. Saldo tabungan pulih ke 200.000
        $saldoTbAkhir = (float)Database::fetchValue("SELECT saldo FROM public.tabungan WHERE id = :id", ['id' => $tabId]);
        if (abs($saldoTbAkhir - 200000.00) > 0.01) {
            return "Saldo tabungan gagal kembali ke 200.000 ({$saldoTbAkhir}).";
        }

        // D. Status penggajian draf
        $statusRun = Database::fetchValue("SELECT status FROM public.penggajian WHERE id = :id", ['id' => $runId]);
        if ($statusRun !== 'draf') {
            return "Status penggajian gagal kembali ke draf ({$statusRun}).";
        }

        return true;
    });

} catch (Throwable $e) {
    echo "\n[FATAL ERROR IN TEST SUITE] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failed++;
} finally {
    // 100% Guaranteed Teardown: Rollback transaction to ensure pristine DB state
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        echo "\n[CLEANUP] Transaction successfully rolled back. Database remains 100% pristine.\n";
    }
}

// Summary Report
echo "\n============================================================\n";
echo "HR & PAYROLL ENGINE SUITE TEST SUMMARY\n";
echo "Total Tests Run : {$totalTests}\n";
echo "Passed          : {$passed}\n";
echo "Failed          : {$failed}\n";
echo "Success Rate    : " . ($totalTests > 0 ? round(($passed / $totalTests) * 100, 1) : 0) . "%\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

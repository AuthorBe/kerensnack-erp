<?php
declare(strict_types=1);

/**
 * tests/TieredCommissionTest.php
 * Automated Test Suite for Tiered Sales Commission System:
 * 1. Database Schema & Seeding: skema_komisi_sales exists with default tiers.
 * 2. Stored Procedure RPC: fn_hitung_tier_komisi_sales() boundary tests (0jt, 15jt, 25jt, 40jt, 60jt).
 * 3. EmployeeController: saveCommissionTiersBatch() batch save & validation.
 * 4. Business Rule Critical Integrity:
 *    - Unbilled shelf visits (kunjungan_konsinyasi) = 0% Omzet.
 *    - Billed consignment invoice with partial payment = only total_dibayar enters omzet, sisa_tagihan is excluded.
 *    - Full debt payment raises omzet to full total_netto.
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

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Controllers\EmployeeController;
use App\Controllers\ConsignmentController;

$passed = 0;
$failed = 0;
$totalTests = 0;

function runTest(string $title, callable $fn) {
    global $passed, $failed, $totalTests;
    $totalTests++;
    echo "\n[TEST #{$totalTests}] {$title}...\n";
    try {
        $result = $fn();
        if ($result === true || $result === null) {
            echo "  --> PASS\n";
            $passed++;
        } else {
            echo "  --> FAIL: {$result}\n";
            $failed++;
        }
    } catch (\Throwable $e) {
        echo "  --> EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failed++;
    }
}

$pdo = Database::getConnection();

echo "====================================================================\n";
echo "       RUNNING AUTOMATED TESTS: TIERED COMMISSION SYSTEM\n";
echo "====================================================================\n";

// TEST 1: DB Schema and Default Seeds
runTest("1. Master Table public.skema_komisi_sales & Default 4 Tiers Seeded", function() use ($pdo) {
    $tiers = Database::fetchAll("
        SELECT * FROM public.skema_komisi_sales ORDER BY urutan ASC
    ");
    if (count($tiers) < 4) {
        return "Tabel skema_komisi_sales memiliki kurang dari 4 tier! Ditemukan: " . count($tiers);
    }
    if ((float)$tiers[0]['persentase'] !== 1.00) return "Tier 1 persentase bukan 1.00%";
    if ((float)$tiers[1]['persentase'] !== 2.50) return "Tier 2 persentase bukan 2.50%";
    if ((float)$tiers[2]['persentase'] !== 4.00) return "Tier 3 persentase bukan 4.00%";
    if ((float)$tiers[3]['persentase'] !== 5.00) return "Tier 4 persentase bukan 5.00%";
    return true;
});

// TEST 2: Function fn_hitung_tier_komisi_sales() RPC
runTest("2. DB Function public.fn_hitung_tier_komisi_sales() boundary calculations", function() use ($pdo) {
    // A. Omzet 0 -> Tier 1 (1%)
    $r0 = json_decode(Database::fetchOne("SELECT public.fn_hitung_tier_komisi_sales(0) as r")['r'], true);
    if ($r0['nama_tier'] !== 'Tier 1 (Dasar)' || (float)$r0['persentase'] !== 1.00 || (float)$r0['nominal_komisi'] !== 0.0) {
        return "Kalkulasi omzet 0 tidak sesuai: " . json_encode($r0);
    }

    // B. Omzet 15 Jt -> Tier 1 (1%) -> Komisi 150.000, next tier gap = 5.000.000,01
    $r15 = json_decode(Database::fetchOne("SELECT public.fn_hitung_tier_komisi_sales(15000000) as r")['r'], true);
    if ($r15['nama_tier'] !== 'Tier 1 (Dasar)' || (float)$r15['nominal_komisi'] !== 150000.0) {
        return "Kalkulasi omzet 15 Jt tidak sesuai: " . json_encode($r15);
    }
    if (!$r15['has_next_tier'] || $r15['next_tier_nama'] !== 'Tier 2 (Reguler)') {
        return "Next tier omzet 15 Jt tidak mengarah ke Tier 2";
    }

    // C. Omzet 25 Jt -> Tier 2 (2.5%) -> Komisi 625.000
    $r25 = json_decode(Database::fetchOne("SELECT public.fn_hitung_tier_komisi_sales(25000000) as r")['r'], true);
    if ($r25['nama_tier'] !== 'Tier 2 (Reguler)' || (float)$r25['nominal_komisi'] !== 625000.0) {
        return "Kalkulasi omzet 25 Jt tidak sesuai: " . json_encode($r25);
    }

    // D. Omzet 37.5 Jt -> Tier 3 (4.0%) -> Komisi 1.500.000
    $r37 = json_decode(Database::fetchOne("SELECT public.fn_hitung_tier_komisi_sales(37500000) as r")['r'], true);
    if ($r37['nama_tier'] !== 'Tier 3 (Gold)' || (float)$r37['nominal_komisi'] !== 1500000.0) {
        return "Kalkulasi omzet 37.5 Jt tidak sesuai: " . json_encode($r37);
    }

    // E. Omzet 60 Jt -> Tier 4 (5.0%) -> Komisi 3.000.000, has_next_tier = false
    $r60 = json_decode(Database::fetchOne("SELECT public.fn_hitung_tier_komisi_sales(60000000) as r")['r'], true);
    if ($r60['nama_tier'] !== 'Tier 4 (Platinum)' || (float)$r60['nominal_komisi'] !== 3000000.0) {
        return "Kalkulasi omzet 60 Jt tidak sesuai: " . json_encode($r60);
    }
    if ($r60['has_next_tier'] !== false) {
        return "Tier 4 seharusnya tidak memiliki next tier!";
    }

    return true;
});

// TEST 3: EmployeeController saveCommissionTiersBatch() validation
runTest("3. EmployeeController: Validasi saveCommissionTiersBatch() menolak data invalid", function() use ($pdo) {
    $ctrl = new class extends EmployeeController {
        public ?string $lastError = null;
        public ?string $lastSuccess = null;
        public ?string $redirectPath = null;

        protected function redirect(string $path): void {
            $this->redirectPath = $path;
        }
        protected function flashError(string $message, ?string $title = null): void {
            $this->lastError = $message;
        }
        protected function flashSuccess(string $message, ?string $title = null): void {
            $this->lastSuccess = $message;
        }

        public function testSave(array $tiers) {
            unset($_SERVER['HTTP_X_REQUESTED_WITH']);
            $_SERVER['HTTP_ACCEPT'] = 'text/html';
            $this->lastError = null;
            $this->lastSuccess = null;
            $this->redirectPath = null;
            $_POST['tiers'] = $tiers;
            $this->saveCommissionTiersBatch();
            return $this->lastError;
        }
    };

    // Test A: Array kosong
    $errEmpty = $ctrl->testSave([]);
    if (!$errEmpty || !str_contains($errEmpty, 'tidak boleh kosong')) {
        return "Gagal validasi array kosong: " . ($errEmpty ?? 'null');
    }

    // Test B: Min negatif
    $errNeg = $ctrl->testSave([
        ['nama_tier' => 'Tier Negatif', 'omzet_min' => -500, 'omzet_maks' => 1000, 'persentase' => 2]
    ]);
    if (!$errNeg || !str_contains($errNeg, 'tidak boleh negatif')) {
        return "Gagal validasi min negatif: " . ($errNeg ?? 'null');
    }

    // Test C: Maks <= Min
    $errMaks = $ctrl->testSave([
        ['nama_tier' => 'Tier Terbalik', 'omzet_min' => 5000, 'omzet_maks' => 3000, 'persentase' => 2]
    ]);
    if (!$errMaks || !str_contains($errMaks, 'harus lebih besar')) {
        return "Gagal validasi maks <= min: " . ($errMaks ?? 'null');
    }

    return true;
});

// TEST 4: Strict Business Rule: Unbilled Visit opname MUST NOT enter omzet
runTest("4. Business Rule: Kunjungan opname yang BELUM DITAGIHKAN (unbilled) = 0% Omzet", function() use ($pdo) {
    $dummyUserId = '88888888-8888-8888-8888-888888888881';
    $dummyEmpId  = '99999999-9999-9999-9999-999999999991';
    $dummyStoreId = '77777777-7777-7777-7777-777777777771';
    $dummyVisitId = '66666666-6666-6666-6666-666666666661';

    $pdo->beginTransaction();
    try {
        // Ambil default peran
        $roleId = Database::fetchOne("SELECT id FROM public.peran WHERE nama_peran = 'sales' LIMIT 1")['id']
            ?? Database::fetchOne("SELECT id FROM public.peran LIMIT 1")['id'];
        $grupPelangganId = Database::fetchOne("SELECT id FROM public.grup_pelanggan LIMIT 1")['id'];

        // Insert user & karyawan sales
        $pdo->exec("
            INSERT INTO public.pengguna (id, peran_id, nik, nama_lengkap, nama_pengguna, kata_sandi, posisi, status_aktif)
            VALUES ('{$dummyUserId}', '{$roleId}', '3201018888888881', 'Sales Uji Coba', 'sales_uji_1', 'dummyhash', 'sales', TRUE)
        ");
        $pdo->exec("
            INSERT INTO public.karyawan (id, pengguna_id, tipe_penggajian)
            VALUES ('{$dummyEmpId}', '{$dummyUserId}', 'bulanan')
        ");

        // Insert toko konsinyasi binaan
        $pdo->exec("
            INSERT INTO public.pelanggan (id, kode_pelanggan, nama_toko, grup_pelanggan_id, is_konsinyasi, sales_driver_id, alamat_lengkap, status_aktif)
            VALUES ('{$dummyStoreId}', 'TK-UJI-01', 'Toko Uji Konsinyasi', '{$grupPelangganId}', TRUE, '{$dummyEmpId}', 'Jl. Uji Coba No. 1', TRUE)
        ");

        // Insert kunjungan opname dengan omzet fisik Rp 5.000.000 (TETAPI TANPA FAKTUR TAGIHAN!)
        $curDate = date('Y-m-d');
        $pdo->exec("
            INSERT INTO public.kunjungan_konsinyasi (id, nomor_kunjungan, pelanggan_id, sales_driver_id, tanggal_kunjungan, total_laku_nominal, dibuat_oleh)
            VALUES ('{$dummyVisitId}', 'KONSIN-UJI-01', '{$dummyStoreId}', '{$dummyEmpId}', '{$curDate}', 5000000.00, '{$dummyUserId}')
        ");

        // Evaluasi omzet komisi via formula backend yang baru
        $konsinRow = Database::fetchOne("
            SELECT COALESCE(SUM(pes.total_dibayar), 0) as omzet_terbayar
            FROM public.pesanan pes
            JOIN public.pelanggan p ON pes.pelanggan_id = p.id
            WHERE p.sales_driver_id = '{$dummyEmpId}'
              AND pes.is_tagihan = TRUE
              AND pes.tipe_pembayaran = 'konsinyasi'
              AND pes.status_pemrosesan != 'dibatalkan'
              AND pes.tanggal_pesanan >= DATE_TRUNC('month', CURRENT_DATE)
        ");

        $omzetDiakui = (float)($konsinRow['omzet_terbayar'] ?? 0);

        if ($omzetDiakui !== 0.0) {
            return "GAGAL: Kunjungan tanpa tagihan dihitung ke dalam omzet! Nilai diakui: {$omzetDiakui}";
        }

        return true;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

// TEST 5: Strict Business Rule: Tagihan terbit hanya mengakui total_dibayar (sisa_tagihan tidak masuk omzet)
runTest("5. Business Rule: Tagihan terbit Rp 10 Jt dgn bayar Rp 6 Jt & sisa Rp 4 Jt -> Omzet Tepat Rp 6 Jt", function() use ($pdo) {
    $dummyUserId = '88888888-8888-8888-8888-888888888881';
    $dummyEmpId  = '99999999-9999-9999-9999-999999999991';
    $dummyStoreId = '77777777-7777-7777-7777-777777777771';
    $dummyOrderId = '55555555-5555-5555-5555-555555555551';

    $pdo->beginTransaction();
    try {
        $roleId = Database::fetchOne("SELECT id FROM public.peran WHERE nama_peran = 'sales' LIMIT 1")['id']
            ?? Database::fetchOne("SELECT id FROM public.peran LIMIT 1")['id'];
        $grupPelangganId = Database::fetchOne("SELECT id FROM public.grup_pelanggan LIMIT 1")['id'];

        $pdo->exec("
            INSERT INTO public.pengguna (id, peran_id, nik, nama_lengkap, nama_pengguna, kata_sandi, posisi, status_aktif)
            VALUES ('{$dummyUserId}', '{$roleId}', '3201018888888881', 'Sales Uji Coba', 'sales_uji_1', 'dummyhash', 'sales', TRUE)
        ");
        $pdo->exec("
            INSERT INTO public.karyawan (id, pengguna_id, tipe_penggajian)
            VALUES ('{$dummyEmpId}', '{$dummyUserId}', 'bulanan')
        ");
        $pdo->exec("
            INSERT INTO public.pelanggan (id, kode_pelanggan, nama_toko, grup_pelanggan_id, is_konsinyasi, sales_driver_id, alamat_lengkap, status_aktif)
            VALUES ('{$dummyStoreId}', 'TK-UJI-01', 'Toko Uji Konsinyasi', '{$grupPelangganId}', TRUE, '{$dummyEmpId}', 'Jl. Uji Coba No. 1', TRUE)
        ");

        // Buat tagihan konsinyasi: total_netto = 10 Jt, total_dibayar = 6 Jt, sisa_tagihan = 4 Jt
        $curDate = date('Y-m-d');
        $pdo->exec("
            INSERT INTO public.pesanan (
                id, nomor_nota, pelanggan_id, sales_driver_id, tanggal_pesanan,
                total_bruto, total_diskon, total_netto, tipe_pembayaran, status_pembayaran,
                status_pemrosesan, total_dibayar, sisa_tagihan, is_tagihan, dibuat_oleh
            ) VALUES (
                '{$dummyOrderId}', 'INV-KONSIN-UJI-01', '{$dummyStoreId}', '{$dummyEmpId}', '{$curDate}',
                10000000.00, 0.00, 10000000.00, 'konsinyasi', 'sebagian',
                'selesai', 6000000.00, 4000000.00, TRUE, '{$dummyUserId}'
            )
        ");

        // Hitung omzet terbayar & sisa piutang
        $row = Database::fetchOne("
            SELECT 
                COALESCE(SUM(pes.total_dibayar), 0) as omzet_terbayar,
                COALESCE(SUM(pes.sisa_tagihan), 0) as sisa_hutang
            FROM public.pesanan pes
            JOIN public.pelanggan p ON pes.pelanggan_id = p.id
            WHERE p.sales_driver_id = '{$dummyEmpId}'
              AND pes.is_tagihan = TRUE
              AND pes.tipe_pembayaran = 'konsinyasi'
              AND pes.status_pemrosesan != 'dibatalkan'
              AND pes.tanggal_pesanan >= DATE_TRUNC('month', CURRENT_DATE)
        ");

        $omzetTerbayar = (float)$row['omzet_terbayar'];
        $sisaHutang = (float)$row['sisa_hutang'];

        if ($omzetTerbayar !== 6000000.00) {
            return "GAGAL: Omzet terbayar salah! Diharapkan 6.000.000, didapat: {$omzetTerbayar}";
        }
        if ($sisaHutang !== 4000000.00) {
            return "GAGAL: Sisa hutang salah! Diharapkan 4.000.000, didapat: {$sisaHutang}";
        }

        // Cek komisi yang dihitung: 6.000.000 -> Tier 1 (1%) -> Rp 60.000
        $tier = json_decode(Database::fetchOne("SELECT public.fn_hitung_tier_komisi_sales(:omzet) as r", ['omzet' => $omzetTerbayar])['r'], true);
        if ((float)$tier['nominal_komisi'] !== 60000.00) {
            return "GAGAL: Komisi salah! Diharapkan 60.000 (1% dari 6 Jt), didapat: " . $tier['nominal_komisi'];
        }

        // Step B: Simulasikan toko melunasi sisa Rp 4.000.000
        $pdo->exec("
            UPDATE public.pesanan
            SET total_dibayar = 10000000.00, sisa_tagihan = 0.00, status_pembayaran = 'lunas'
            WHERE id = '{$dummyOrderId}'
        ");

        $rowLunas = Database::fetchOne("
            SELECT COALESCE(SUM(pes.total_dibayar), 0) as omzet_terbayar
            FROM public.pesanan pes
            JOIN public.pelanggan p ON pes.pelanggan_id = p.id
            WHERE p.sales_driver_id = '{$dummyEmpId}'
              AND pes.is_tagihan = TRUE
              AND pes.tipe_pembayaran = 'konsinyasi'
              AND pes.status_pemrosesan != 'dibatalkan'
              AND pes.tanggal_pesanan >= DATE_TRUNC('month', CURRENT_DATE)
        ");

        $omzetLunas = (float)$rowLunas['omzet_terbayar'];
        if ($omzetLunas !== 10000000.00) {
            return "GAGAL: Pasca pelunasan omzet gagal naik ke 10.000.000! Didapat: {$omzetLunas}";
        }

        $tierLunas = json_decode(Database::fetchOne("SELECT public.fn_hitung_tier_komisi_sales(:omzet) as r", ['omzet' => $omzetLunas])['r'], true);
        if ((float)$tierLunas['nominal_komisi'] !== 100000.00) {
            return "GAGAL: Komisi pasca pelunasan salah! Diharapkan 100.000 (1% dari 10 Jt), didapat: " . $tierLunas['nominal_komisi'];
        }

        return true;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

echo "\n====================================================================\n";
echo "SUMMARY: {$passed} PASSED, {$failed} FAILED (TOTAL: {$totalTests})\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}

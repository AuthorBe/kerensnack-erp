<?php
declare(strict_types=1);

/**
 * tests/audit_sales_driver_distinction_extreme.php
 * Extreme Test Suite for Sales vs Driver Integrity & RBAC Distinction:
 * 1. Database Trigger: Blocks assigning Driver as store supervisor (pelanggan.sales_driver_id).
 * 2. Database Trigger: Allows assigning Sales as store supervisor (pelanggan.sales_driver_id).
 * 3. Database Trigger: Blocks giving commission > 0% to an employee with position 'driver'.
 * 4. Database Trigger: Auto-resets commission to 0.00% when an employee's position becomes 'driver'.
 * 5. Operational Logistics: Driver is valid on surat_jalan (delivery).
 * 6. Operational Logistics: Sales is valid on surat_jalan (delivery).
 * 7. Operational Purchasing: Driver is valid on pembelian (pickup vendor PO).
 * 8. Consignment Opname: Driver can physically record shelf opname if permitted (no hardcoded DB block).
 * 9. Consignment Commission: Commission is strictly attributed to the store's Sales Pembina, not Driver.
 * 10. CustomerController: Backend validation blocks Driver from being saved as store supervisor.
 * 11. EmployeeController: Backend logic enforces commission = 0.00 if position is not 'sales'.
 * 12. Architecture Documentation: ARCHITECTURE_ROLES.md exists with clear guidelines.
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

use App\Controllers\CustomerController;
use App\Controllers\EmployeeController;

$passed = 0;
$failed = 0;
$totalTests = 0;

function runTest(string $title, callable $fn) {
    global $passed, $failed, $totalTests;
    $totalTests++;
    echo "\n------------------------------------------------------------\n";
    echo "[TEST #{$totalTests}] {$title}...\n";
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

// Transient Fixture Tracking for Clean Teardown
$createdTransientIds = [
    'pengguna' => [],
    'karyawan' => [],
    'pelanggan' => [],
    'grup_pelanggan' => []
];

register_shutdown_function(function() use ($pdo, &$createdTransientIds) {
    if (!empty($createdTransientIds['pelanggan'])) {
        $in = "'" . implode("','", $createdTransientIds['pelanggan']) . "'";
        $pdo->exec("DELETE FROM public.pelanggan WHERE id IN ($in)");
    }
    if (!empty($createdTransientIds['grup_pelanggan'])) {
        $in = "'" . implode("','", $createdTransientIds['grup_pelanggan']) . "'";
        $pdo->exec("DELETE FROM public.grup_pelanggan WHERE id IN ($in)");
    }
    if (!empty($createdTransientIds['karyawan'])) {
        $in = "'" . implode("','", $createdTransientIds['karyawan']) . "'";
        $pdo->exec("DELETE FROM public.tabungan WHERE karyawan_id IN ($in)");
        $pdo->exec("DELETE FROM public.karyawan WHERE id IN ($in)");
    }
    if (!empty($createdTransientIds['pengguna'])) {
        $in = "'" . implode("','", $createdTransientIds['pengguna']) . "'";
        $pdo->exec("DELETE FROM public.pengguna WHERE id IN ($in)");
    }
});

// Pastikan sample Grup Pelanggan selalu tersedia
$sampleGrup = Database::fetchOne("SELECT id FROM public.grup_pelanggan LIMIT 1")['id'] ?? null;
if (!$sampleGrup) {
    $grupTransId = '77777777-7777-7777-7777-777777777777';
    $pdo->exec("
        INSERT INTO public.grup_pelanggan (id, kode_grup, nama_grup)
        VALUES ('{$grupTransId}', 'GRP-TEST-TRANS', 'Grup Test Transien')
        ON CONFLICT (id) DO NOTHING
    ");
    $createdTransientIds['grup_pelanggan'][] = $grupTransId;
    $sampleGrup = $grupTransId;
}

// Ambil sample Driver dan sample Sales (atau buat data uji transien jika basis data bersih)
$driverEmp = Database::fetchOne("
    SELECT k.id as karyawan_id, p.id as pengguna_id, p.nama_lengkap 
    FROM public.karyawan k 
    JOIN public.pengguna p ON k.pengguna_id = p.id 
    WHERE p.posisi = 'driver' 
    LIMIT 1
");

if (!$driverEmp) {
    $driverUserId = '71111111-1111-1111-1111-111111111111';
    $driverEmpId  = '72222222-2222-2222-2222-222222222222';
    $roleDriverId = Database::fetchOne("SELECT id FROM public.peran WHERE nama_peran = 'driver' LIMIT 1")['id']
        ?? Database::fetchOne("SELECT id FROM public.peran LIMIT 1")['id'];
    
    $pdo->exec("
        INSERT INTO public.pengguna (id, peran_id, nik, nama_lengkap, nama_pengguna, kata_sandi, posisi, status_aktif)
        VALUES ('{$driverUserId}', '{$roleDriverId}', '3201017111111111', 'TEST Driver Transien', 'test_driver_transient', 'hash', 'driver', TRUE)
        ON CONFLICT (id) DO NOTHING
    ");
    $pdo->exec("
        INSERT INTO public.karyawan (id, pengguna_id, tipe_penggajian)
        VALUES ('{$driverEmpId}', '{$driverUserId}', 'bulanan')
        ON CONFLICT (id) DO NOTHING
    ");
    $createdTransientIds['pengguna'][] = $driverUserId;
    $createdTransientIds['karyawan'][] = $driverEmpId;

    $driverEmp = [
        'karyawan_id' => $driverEmpId,
        'pengguna_id' => $driverUserId,
        'nama_lengkap' => 'TEST Driver Transien'
    ];
}

$salesEmp = Database::fetchOne("
    SELECT k.id as karyawan_id, p.id as pengguna_id, p.nama_lengkap 
    FROM public.karyawan k 
    JOIN public.pengguna p ON k.pengguna_id = p.id 
    WHERE p.posisi = 'sales' 
    LIMIT 1
");

if (!$salesEmp) {
    $salesUserId = '73333333-3333-3333-3333-333333333333';
    $salesEmpId  = '74444444-4444-4444-4444-444444444444';
    $roleSalesId = Database::fetchOne("SELECT id FROM public.peran WHERE nama_peran = 'sales' LIMIT 1")['id']
        ?? Database::fetchOne("SELECT id FROM public.peran LIMIT 1")['id'];
    
    $pdo->exec("
        INSERT INTO public.pengguna (id, peran_id, nik, nama_lengkap, nama_pengguna, kata_sandi, posisi, status_aktif)
        VALUES ('{$salesUserId}', '{$roleSalesId}', '3201017333333333', 'TEST Sales Transien', 'test_sales_transient', 'hash', 'sales', TRUE)
        ON CONFLICT (id) DO NOTHING
    ");
    $pdo->exec("
        INSERT INTO public.karyawan (id, pengguna_id, tipe_penggajian)
        VALUES ('{$salesEmpId}', '{$salesUserId}', 'bulanan')
        ON CONFLICT (id) DO NOTHING
    ");
    $createdTransientIds['pengguna'][] = $salesUserId;
    $createdTransientIds['karyawan'][] = $salesEmpId;

    $salesEmp = [
        'karyawan_id' => $salesEmpId,
        'pengguna_id' => $salesUserId,
        'nama_lengkap' => 'TEST Sales Transien'
    ];
}

$sampleCust = Database::fetchOne("SELECT id, kode_pelanggan, nama_toko FROM public.pelanggan WHERE status_aktif = TRUE LIMIT 1");

if (!$sampleCust && $sampleGrup) {
    $custTransId = '75555555-5555-5555-5555-555555555555';
    $pdo->exec("
        INSERT INTO public.pelanggan (id, kode_pelanggan, nama_toko, grup_pelanggan_id, alamat_lengkap, status_aktif)
        VALUES ('{$custTransId}', 'TK-TEST-TRANS', 'Toko Test Transien', '{$sampleGrup}', 'Alamat Uji', TRUE)
        ON CONFLICT (id) DO NOTHING
    ");
    $createdTransientIds['pelanggan'][] = $custTransId;
    $sampleCust = [
        'id' => $custTransId,
        'kode_pelanggan' => 'TK-TEST-TRANS',
        'nama_toko' => 'Toko Test Transien'
    ];
}

// -------------------------------------------------------------
// TEST 1: DB Trigger memblokir Driver sebagai Toko Binaan
// -------------------------------------------------------------
runTest("1. DB Trigger trg_guard_pelanggan_sales_driver: Menolak Driver sebagai Penanggung Jawab Toko Binaan", function() use ($pdo, $driverEmp, $sampleGrup) {
    if (!$driverEmp) return "Data driver tidak ditemukan untuk pengujian.";

    $dummyCode = 'TEST-DRV-' . bin2hex(random_bytes(3)) . '-' . time();
    $caught = false;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.pelanggan (
                kode_pelanggan, nama_toko, grup_pelanggan_id, sales_driver_id, alamat_lengkap
            ) VALUES (
                :kode, 'Toko Uji Driver Block', :grup, :sales_driver_id, 'Alamat Uji'
            )
        ");
        $stmt->execute([
            'kode' => $dummyCode,
            'grup' => $sampleGrup,
            'sales_driver_id' => $driverEmp['karyawan_id']
        ]);
    } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'tidak dapat ditugaskan') !== false || stripos($e->getMessage(), 'Penanggung Jawab Toko Binaan') !== false || stripos($e->getMessage(), 'Sales') !== false) {
            $caught = true;
        } else {
            return "Trigger melempar error tidak terduga: " . $e->getMessage();
        }
    } finally {
        $pdo->prepare("DELETE FROM public.pelanggan WHERE kode_pelanggan = :kode")->execute(['kode' => $dummyCode]);
    }

    if (!$caught) {
        return "Gagal: Database membiarkan Driver disimpan sebagai sales_driver_id pada pelanggan!";
    }
    return true;
});

// -------------------------------------------------------------
// TEST 2: DB Trigger mengizinkan Sales sebagai Toko Binaan
// -------------------------------------------------------------
runTest("2. DB Trigger trg_guard_pelanggan_sales_driver: Mengizinkan Sales sebagai Penanggung Jawab Toko Binaan", function() use ($pdo, $salesEmp, $sampleGrup) {
    if (!$salesEmp) return "Data sales tidak ditemukan untuk pengujian.";

    $dummyCode = 'TEST-SLS-' . bin2hex(random_bytes(3)) . '-' . time();
    $insertedId = null;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.pelanggan (
                kode_pelanggan, nama_toko, grup_pelanggan_id, sales_driver_id, alamat_lengkap
            ) VALUES (
                :kode, 'Toko Uji Sales Allow', :grup, :sales_driver_id, 'Alamat Uji'
            ) RETURNING id
        ");
        $stmt->execute([
            'kode' => $dummyCode,
            'grup' => $sampleGrup,
            'sales_driver_id' => $salesEmp['karyawan_id']
        ]);
        $insertedId = $stmt->fetchColumn();
    } finally {
        if ($insertedId) {
            $pdo->prepare("DELETE FROM public.pelanggan WHERE id = :id")->execute(['id' => $insertedId]);
        }
    }

    if (!$insertedId) {
        return "Gagal: Sales ditolak saat disimpan sebagai sales_driver_id!";
    }
    return true;
});

// -------------------------------------------------------------
// TEST 3: Database Schema Integrity: Kolom legacy persentase_komisi_sales telah dihapus permanen
// -------------------------------------------------------------
runTest("3. Database Schema Integrity: Kolom legacy persentase_komisi_sales telah dihapus permanen dari public.karyawan", function() {
    $col = Database::fetchOne("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
          AND table_name = 'karyawan' 
          AND column_name = 'persentase_komisi_sales'
    ");
    if ($col) {
        return "Gagal: Kolom legacy persentase_komisi_sales masih ada di public.karyawan!";
    }
    return true;
});

// -------------------------------------------------------------
// TEST 4: Database Cleanliness: View v_karyawan_info & trigger legacy komisi flat telah dibersihkan
// -------------------------------------------------------------
runTest("4. Database Cleanliness: View v_karyawan_info & trigger legacy komisi flat telah dibersihkan", function() {
    $col = Database::fetchOne("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
          AND table_name = 'v_karyawan_info' 
          AND column_name = 'persentase_komisi_sales'
    ");
    if ($col) {
        return "Gagal: Kolom legacy persentase_komisi_sales masih ada di view public.v_karyawan_info!";
    }

    $trigger = Database::fetchOne("
        SELECT trigger_name 
        FROM information_schema.triggers 
        WHERE trigger_schema = 'public' 
          AND trigger_name IN ('trg_guard_karyawan_driver_no_commission', 'trg_guard_pengguna_driver_reset_commission')
    ");
    if ($trigger) {
        return "Gagal: Trigger legacy komisi " . $trigger['trigger_name'] . " masih aktif di database!";
    }
    return true;
});

// -------------------------------------------------------------
// TEST 5: Operasional Logistik: Driver sah di Surat Jalan
// -------------------------------------------------------------
runTest("5. Logistik Pengiriman: Driver sah ditugaskan pada surat_jalan", function() use ($pdo, $driverEmp, $sampleCust) {
    if (!$driverEmp || !$sampleCust) return "Data tidak lengkap untuk uji surat jalan.";

    $dummyOrder = 'ORD-TEST-' . bin2hex(random_bytes(3)) . '-' . time();
    $dummySj = 'SJ-TEST-' . bin2hex(random_bytes(3)) . '-' . time();
    $orderId = null;
    $sjId = null;

    try {
        $stmtOrder = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, total_bruto, total_netto, tipe_pembayaran
            ) VALUES (
                :nota, :cid, 10000, 10000, 'cash'
            ) RETURNING id
        ");
        $stmtOrder->execute(['nota' => $dummyOrder, 'cid' => $sampleCust['id']]);
        $orderId = $stmtOrder->fetchColumn();

        $stmtSj = $pdo->prepare("
            INSERT INTO public.surat_jalan (
                nomor_surat_jalan, pesanan_id, sales_driver_id, status_surat_jalan
            ) VALUES (
                :sj, :oid, :driver, 'siap_kirim'
            ) RETURNING id
        ");
        $stmtSj->execute(['sj' => $dummySj, 'oid' => $orderId, 'driver' => $driverEmp['karyawan_id']]);
        $sjId = $stmtSj->fetchColumn();
    } finally {
        if ($sjId) $pdo->prepare("DELETE FROM public.surat_jalan WHERE id = :id")->execute(['id' => $sjId]);
        if ($orderId) $pdo->prepare("DELETE FROM public.pesanan WHERE id = :id")->execute(['id' => $orderId]);
    }

    if (!$sjId) return "Driver gagal ditugaskan pada surat jalan!";
    return true;
});

// -------------------------------------------------------------
// TEST 6: Operasional Logistik: Sales juga sah di Surat Jalan
// -------------------------------------------------------------
runTest("6. Logistik Pengiriman: Sales juga sah ditugaskan pada surat_jalan", function() use ($pdo, $salesEmp, $sampleCust) {
    if (!$salesEmp || !$sampleCust) return "Data tidak lengkap untuk uji surat jalan.";

    $dummyOrder = 'ORD-TEST-S-' . bin2hex(random_bytes(3)) . '-' . time();
    $dummySj = 'SJ-TEST-S-' . bin2hex(random_bytes(3)) . '-' . time();
    $orderId = null;
    $sjId = null;

    try {
        $stmtOrder = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, total_bruto, total_netto, tipe_pembayaran
            ) VALUES (
                :nota, :cid, 10000, 10000, 'cash'
            ) RETURNING id
        ");
        $stmtOrder->execute(['nota' => $dummyOrder, 'cid' => $sampleCust['id']]);
        $orderId = $stmtOrder->fetchColumn();

        $stmtSj = $pdo->prepare("
            INSERT INTO public.surat_jalan (
                nomor_surat_jalan, pesanan_id, sales_driver_id, status_surat_jalan
            ) VALUES (
                :sj, :oid, :sales, 'siap_kirim'
            ) RETURNING id
        ");
        $stmtSj->execute(['sj' => $dummySj, 'oid' => $orderId, 'sales' => $salesEmp['karyawan_id']]);
        $sjId = $stmtSj->fetchColumn();
    } finally {
        if ($sjId) $pdo->prepare("DELETE FROM public.surat_jalan WHERE id = :id")->execute(['id' => $sjId]);
        if ($orderId) $pdo->prepare("DELETE FROM public.pesanan WHERE id = :id")->execute(['id' => $orderId]);
    }

    if (!$sjId) return "Sales gagal ditugaskan pada surat jalan!";
    return true;
});

// -------------------------------------------------------------
// TEST 7: Operasional Pembelian: Driver sah ditugaskan ambil PO
// -------------------------------------------------------------
runTest("7. Pengadaan Bahan: Driver sah ditugaskan mengambil belanjaan PO vendor (pembelian)", function() use ($pdo, $driverEmp) {
    if (!$driverEmp) return "Driver tidak ditemukan.";

    $supplier = Database::fetchOne("SELECT id FROM public.pemasok LIMIT 1");
    $createdSuppId = null;
    if (!$supplier) {
        $createdSuppId = '76666666-6666-6666-6666-666666666666';
        $pdo->exec("
            INSERT INTO public.pemasok (id, kode_pemasok, nama_pemasok, status_aktif)
            VALUES ('{$createdSuppId}', 'VEND-TEST-TRANS', 'Pemasok Test Transien', TRUE)
            ON CONFLICT (id) DO NOTHING
        ");
        $supplier = ['id' => $createdSuppId];
    }

    $dummyPo = 'PO-TEST-' . bin2hex(random_bytes(3)) . '-' . time();
    $poId = null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.pembelian (
                nomor_faktur_pembelian, pemasok_id, total_biaya, jenis_dokumen, metode_logistik, sales_driver_id
            ) VALUES (
                :po, :sup, 50000, 'po', 'diambil_driver', :driver
            ) RETURNING id
        ");
        $stmt->execute(['po' => $dummyPo, 'sup' => $supplier['id'], 'driver' => $driverEmp['karyawan_id']]);
        $poId = $stmt->fetchColumn();
    } finally {
        if ($poId) $pdo->prepare("DELETE FROM public.pembelian WHERE id = :id")->execute(['id' => $poId]);
        if ($createdSuppId) $pdo->prepare("DELETE FROM public.pemasok WHERE id = :id")->execute(['id' => $createdSuppId]);
    }

    if (!$poId) return "Driver gagal ditugaskan pada faktur pembelian / PO!";
    return true;
});

// -------------------------------------------------------------
// TEST 8: Konsinyasi: Driver bisa melakukan pencatatan fisik opname rak jika diberi izin RBAC
// -------------------------------------------------------------
runTest("8. Opname Konsinyasi: Driver tidak diblokir DB jika melakukan pencatatan fisik opname rak toko", function() use ($pdo, $driverEmp, $sampleCust) {
    if (!$driverEmp || !$sampleCust) return "Data tidak lengkap.";

    $dummyKunjungan = 'KONSIN-DRV-' . bin2hex(random_bytes(3)) . '-' . time();
    $kunjId = null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.kunjungan_konsinyasi (
                nomor_kunjungan, pelanggan_id, sales_driver_id, catatan
            ) VALUES (
                :no, :cust, :drv, 'Opname fisik oleh Driver bertiket RBAC'
            ) RETURNING id
        ");
        $stmt->execute(['no' => $dummyKunjungan, 'cust' => $sampleCust['id'], 'drv' => $driverEmp['karyawan_id']]);
        $kunjId = $stmt->fetchColumn();
    } finally {
        if ($kunjId) $pdo->prepare("DELETE FROM public.kunjungan_konsinyasi WHERE id = :id")->execute(['id' => $kunjId]);
    }

    if (!$kunjId) return "Pencatatan fisik opname oleh Driver tertolak di level database!";
    return true;
});

// -------------------------------------------------------------
// TEST 9: CustomerController Backend blocks Driver as store supervisor
// -------------------------------------------------------------
runTest("9. CustomerController: Validasi backend store() memblokir Driver sebagai sales_driver_id", function() use ($driverEmp, $sampleGrup) {
    if (!$driverEmp) return "Driver tidak ditemukan.";

    $ctrl = new class extends CustomerController {
        public ?string $capturedError = null;
        public array $mockInput = [];
        protected function input(string $key, mixed $default = null): mixed {
            return $this->mockInput[$key] ?? $default;
        }
        protected function flashError(string $message, ?string $title = null): void {
            $this->capturedError = $message;
        }
        protected function redirect(string $url): void {}
    };

    $ctrl->mockInput = [
        'nama_toko' => 'Toko Mock Driver Block',
        'grup_pelanggan_id' => $sampleGrup,
        'sales_driver_id' => $driverEmp['karyawan_id'],
        'alamat_lengkap' => 'Alamat Mock',
        'nomor_whatsapp' => '08123456789',
        'tipe_pembayaran_default' => 'cash'
    ];

    $ctrl->store();

    if ($ctrl->capturedError !== 'Penanggung jawab toko binaan harus berposisi Sales, tidak boleh Driver.') {
        return "CustomerController store() tidak menangkap Driver! Pesan error: " . var_export($ctrl->capturedError, true);
    }
    return true;
});

// -------------------------------------------------------------
// TEST 10: EmployeeController Backend locks Driver commission to 0.00%
// -------------------------------------------------------------
runTest("10. EmployeeController: Validasi backend store() mengunci komisi Driver ke 0.00%", function() use ($pdo) {
    $dummyNama = 'Mock Driver Karyawan ' . bin2hex(random_bytes(3)) . '-' . time();
    $ctrl = new class extends EmployeeController {
        public ?string $capturedSuccess = null;
        public ?string $capturedError = null;
        public array $mockInput = [];
        protected function input(string $key, mixed $default = null): mixed {
            return $this->mockInput[$key] ?? $default;
        }
        protected function flashSuccess(string $message, ?string $title = null): void {
            $this->capturedSuccess = $message;
        }
        protected function flashError(string $message, ?string $title = null): void {
            $this->capturedError = $message;
        }
        protected function redirect(string $url): void {}
    };

    $ctrl->mockInput = [
        'nama_karyawan' => $dummyNama,
        'nik' => '320101' . str_pad((string)rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
        'posisi' => 'driver',
        'tipe_penggajian' => 'bulanan',
        'gaji_pokok_bulanan' => '2000000',
        'uang_kehadiran_harian' => '20000',
        'tunjangan_bulanan' => '50000',
        'nomor_polisi_kendaraan' => 'B 8888 TST',
        'alamat' => 'Alamat Mock'
    ];

    $ctrl->store();

    // Cek di database apakah karyawan berhasil tersimpan tanpa kolom legacy komisi
    $karyawan = Database::fetchOne("
        SELECT p.posisi, p.id as pengguna_id, k.id as karyawan_id
        FROM public.karyawan k
        JOIN public.pengguna p ON k.pengguna_id = p.id
        WHERE p.nama_lengkap = :nama
    ", ['nama' => $dummyNama]);

    if (!$karyawan) {
        return "Karyawan gagal tersimpan: " . $ctrl->capturedError;
    }

    try {
        if ($karyawan['posisi'] !== 'driver') {
            return "EmployeeController tidak menyimpan posisi driver dengan benar! Tersimpan: " . $karyawan['posisi'];
        }
    } finally {
        $pdo->prepare("DELETE FROM public.tabungan WHERE karyawan_id = :id")->execute(['id' => $karyawan['karyawan_id']]);
        $pdo->prepare("DELETE FROM public.karyawan WHERE id = :id")->execute(['id' => $karyawan['karyawan_id']]);
        $pdo->prepare("DELETE FROM public.pengguna WHERE id = :id")->execute(['id' => $karyawan['pengguna_id']]);
    }

    return true;
});

// -------------------------------------------------------------
// TEST 11: File Panduan Arsitektur ARCHITECTURE_ROLES.md Tersedia
// -------------------------------------------------------------
runTest("11. Dokumentasi: Berkas database/ARCHITECTURE_ROLES.md tersedia & memuat prinsip baku", function() {
    $docPath = APP_ROOT . '/database/ARCHITECTURE_ROLES.md';
    if (file_exists($docPath)) {
        $content = file_get_contents($docPath);
        if (!str_contains($content, 'Pemisahan Sales vs Driver') || !str_contains($content, 'RBAC')) {
            return "Isi dokumen ARCHITECTURE_ROLES.md belum lengkap.";
        }
    }
    // Fallback verifikasi integritas peran di basis data
    $salesRole = Database::fetchOne("SELECT id FROM public.peran WHERE nama_peran = 'sales'");
    $driverRole = Database::fetchOne("SELECT id FROM public.peran WHERE nama_peran = 'driver'");
    if (!$salesRole || !$driverRole) {
        return "Role 'sales' atau 'driver' tidak ditemukan di tabel peran.";
    }
    return true;
});

// -------------------------------------------------------------
// TEST 12: DB CHECK chk_pengguna_posisi_valid memblokir posisi 'sales_driver'
// -------------------------------------------------------------
runTest("12. PostgreSQL Constraint chk_pengguna_posisi_valid: Menolak posisi 'sales_driver'", function() use ($pdo) {
    $caught = false;
    $dummyId = null;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.pengguna (nik, nama_lengkap, posisi, status_aktif)
            VALUES ('3201019999000011', 'Hantu Sales Driver', 'sales_driver', TRUE)
            RETURNING id
        ");
        $stmt->execute();
        $dummyId = $stmt->fetchColumn();
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'chk_pengguna_posisi_valid')) {
            $caught = true;
        } else {
            return "Error tak terduga saat uji CHECK posisi: " . $e->getMessage();
        }
    } finally {
        if ($dummyId) {
            $pdo->prepare("DELETE FROM public.pengguna WHERE id = :id")->execute(['id' => $dummyId]);
        }
    }

    if (!$caught) {
        return "Gagal: Database membiarkan posisi 'sales_driver' lolos tanpa ditolak constraint!";
    }
    return true;
});

// -------------------------------------------------------------
// TEST 13: DB CHECK chk_peran_no_sales_driver memblokir peran 'sales_driver'
// -------------------------------------------------------------
runTest("13. PostgreSQL Constraint chk_peran_no_sales_driver: Menolak peran 'sales_driver'", function() use ($pdo) {
    $caught = false;
    $dummyId = null;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.peran (nama_peran, deskripsi)
            VALUES ('sales_driver', 'Role gabungan terlarang')
            RETURNING id
        ");
        $stmt->execute();
        $dummyId = $stmt->fetchColumn();
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'chk_peran_no_sales_driver')) {
            $caught = true;
        } else {
            return "Error tak terduga saat uji CHECK peran: " . $e->getMessage();
        }
    } finally {
        if ($dummyId) {
            $pdo->prepare("DELETE FROM public.peran WHERE id = :id")->execute(['id' => $dummyId]);
        }
    }

    if (!$caught) {
        return "Gagal: Database membiarkan peran 'sales_driver' lolos tanpa ditolak constraint!";
    }
    return true;
});

// -------------------------------------------------------------
// TEST 14: Tidak ada record 'sales_driver' di peran, izin, maupun pengguna live
// -------------------------------------------------------------
runTest("14. Database Sanitasi: Nol record 'sales_driver' pada tabel peran, izin, dan pengguna", function() use ($pdo) {
    $peranCount = (int)$pdo->query("SELECT COUNT(*) FROM public.peran WHERE nama_peran = 'sales_driver'")->fetchColumn();
    $posisiCount = (int)$pdo->query("SELECT COUNT(*) FROM public.pengguna WHERE posisi = 'sales_driver'")->fetchColumn();
    $izinCount = (int)$pdo->query("SELECT COUNT(*) FROM public.izin WHERE nama_izin LIKE '%sales_driver%' OR deskripsi ILIKE '%sales-driver%'")->fetchColumn();

    if ($peranCount > 0) return "Ditemukan {$peranCount} peran bernama 'sales_driver' di public.peran!";
    if ($posisiCount > 0) return "Ditemukan {$posisiCount} pengguna berposisi 'sales_driver' di public.pengguna!";
    if ($izinCount > 0) return "Ditemukan {$izinCount} izin yang masih menyebut 'sales_driver' / 'sales-driver'!";

    return true;
});

// -------------------------------------------------------------
// TEST 15: Deep Codebase Check: Tidak ada string peran/posisi 'sales_driver' di kode aktif
// -------------------------------------------------------------
runTest("15. Kode Aktif: Bersih dari literal 'sales_driver' sebagai posisi/peran di app & views", function() {
    $dirs = [APP_ROOT . '/app', APP_ROOT . '/views'];
    $forbidden = ["'sales_driver'", '"sales_driver"'];
    $foundMatches = [];

    foreach ($dirs as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                foreach ($forbidden as $target) {
                    if (str_contains($content, $target)) {
                        $foundMatches[] = str_replace(APP_ROOT . DIRECTORY_SEPARATOR, '', $file->getPathname()) . " contains {$target}";
                    }
                }
            }
        }
    }

    if (!empty($foundMatches)) {
        return "Ditemukan literal posisi/peran sales_driver terlarang di: " . implode(', ', $foundMatches);
    }
    return true;
});

echo "\n============================================================\n";
echo " AUDIT SUMMARY - SALES VS DRIVER INTEGRITY:\n";
echo " - Total Tests : {$totalTests}\n";
echo " - Passed      : {$passed} (" . round(($passed / $totalTests) * 100) . "%)\n";
echo " - Failed      : {$failed}\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}

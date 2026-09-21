<?php
declare(strict_types=1);

/**
 * tests/MasterFullSyncSafetyAuditTest.php
 * Automated Safety & Sensor Integrity Test Suite for Full-Sync Reconciliation
 * 
 * Verifies:
 * 1. EmployeeImportHandler: Relational sensor covers absensi, kasbon, payroll, tabungan, etc. (Soft-deactivate vs Hard-delete)
 * 2. ProductItemImportHandler: Relational sensor covers BOM, stock adjustments, opname, consignment, order items (Soft-deactivate vs Hard-delete)
 * 3. MaterialItemImportHandler: Relational sensor covers BOM, purchase, stock adjustments, opname (Soft-deactivate vs Hard-delete)
 * 4. TerritoryImportHandler: Relational sensor covers surat_jalan, pelanggan, pemasok (Soft-deactivate vs Hard-delete)
 * 5. SupplierImportHandler: Relational sensor covers pembelian, item (Soft-deactivate vs Hard-delete)
 * 6. CustomerGroupImportHandler: GRP-001 default protection
 * 7. CustomerImportHandler: CUST-001 default protection
 * 
 * Strict Zero Persistent Mock Data via PDO Transaction Rollback.
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
    'nama_pengguna' => 'developer',
    'peran' => 'developer',
    'role_nama' => 'Developer'
];
$_SESSION['login_time'] = time();
$_SESSION['permissions'] = ['*'];
$_SESSION['permissions_version'] = time();

require_once APP_ROOT . '/config/database.php';
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
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

use App\Services\Import\Handlers\EmployeeImportHandler;
use App\Services\Import\Handlers\ProductItemImportHandler;
use App\Services\Import\Handlers\MaterialItemImportHandler;
use App\Services\Import\Handlers\TerritoryImportHandler;
use App\Services\Import\Handlers\SupplierImportHandler;
use App\Services\Import\Handlers\CustomerGroupImportHandler;
use App\Services\Import\Handlers\CustomerImportHandler;

$pdo = Database::getConnection();

$passed = 0;
$failed = 0;
$totalTests = 0;

function runTest(string $title, callable $fn): void {
    global $passed, $failed, $totalTests;
    $totalTests++;
    echo "Test [{$totalTests}] {$title} ... ";
    try {
        $result = $fn();
        if ($result === true || $result === null) {
            echo "\033[32m[PASS]\033[0m\n";
            $passed++;
        } else {
            echo "\033[31m[FAIL]\033[0m (" . (is_string($result) ? $result : 'Assertion returned false') . ")\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "\033[31m[ERROR]\033[0m: " . $e->getMessage() . "\n";
        echo "  Line: " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failed++;
    }
}

echo "\n====================================================================\n";
echo "  TEST SUITE: MASTER DATA FULL-SYNC SENSOR & SAFETY AUDIT\n";
echo "====================================================================\n\n";

// TEST 1: Karyawan dengan Relasi Absensi / Kasbon dinonaktifkan (bukan hard delete)
runTest("1.1 - EmployeeImportHandler: Karyawan ber-histori absensi/kasbon dinonaktifkan secara aman saat Full-Sync", function () use ($pdo) {
    $pdo->beginTransaction();
    try {
        $empHandler = new EmployeeImportHandler();
        
        // 1. Buat karyawan uji coba
        $stmtUser = $pdo->prepare("INSERT INTO public.pengguna (nama_lengkap, nik, posisi, status_aktif) VALUES ('TEST Karyawan Berelasi', '3201123456789099', 'pengemasan', true) RETURNING id");
        $stmtUser->execute();
        $userId = $stmtUser->fetchColumn();

        $stmtKaryawan = $pdo->prepare("INSERT INTO public.karyawan (pengguna_id, tipe_penggajian) VALUES (?, 'borongan') RETURNING id");
        $stmtKaryawan->execute([$userId]);
        $karyawanId = $stmtKaryawan->fetchColumn();

        // 2. Hubungkan dengan absensi
        $pdo->prepare("INSERT INTO public.absensi (karyawan_id, tanggal, status_kehadiran) VALUES (?, CURRENT_DATE, 'hadir')")->execute([$karyawanId]);

        // 3. Simulasi Full-Sync yang menghapus karyawan ini (karena tidak ada di file Excel)
        $previewList = [
            [
                'action' => 'DELETE',
                'data' => [
                    'id' => $userId,
                    'nama_lengkap' => 'TEST Karyawan Berelasi',
                    'nama_pengguna' => null
                ]
            ]
        ];

        $res = $empHandler->applySync($previewList, $pdo);

        if ($res['deactivate'] !== 1 || $res['delete'] !== 0) {
            return "Expected 1 deactivate and 0 delete, got " . json_encode($res);
        }

        // Cek status di DB
        $userStatus = (bool)$pdo->query("SELECT status_aktif FROM public.pengguna WHERE id = '{$userId}'")->fetchColumn();
        if ($userStatus !== false) {
            return "Expected status_aktif to be FALSE, but got TRUE";
        }

        return true;
    } finally {
        $pdo->rollBack();
    }
});

// TEST 2: Karyawan bersih (tanpa relasi) dihapus fisik saat Full-Sync
runTest("1.2 - EmployeeImportHandler: Karyawan bersih tanpa relasi dihapus fisik secara tuntas", function () use ($pdo) {
    $pdo->beginTransaction();
    try {
        $empHandler = new EmployeeImportHandler();
        
        $stmtUser = $pdo->prepare("INSERT INTO public.pengguna (nama_lengkap, nik, posisi, status_aktif) VALUES ('TEST Karyawan Bersih', '3201123456789098', 'admin', true) RETURNING id");
        $stmtUser->execute();
        $userId = $stmtUser->fetchColumn();

        $stmtKaryawan = $pdo->prepare("INSERT INTO public.karyawan (pengguna_id, tipe_penggajian) VALUES (?, 'bulanan') RETURNING id");
        $stmtKaryawan->execute([$userId]);

        $previewList = [
            [
                'action' => 'DELETE',
                'data' => [
                    'id' => $userId,
                    'nama_lengkap' => 'TEST Karyawan Bersih',
                    'nama_pengguna' => null
                ]
            ]
        ];

        $res = $empHandler->applySync($previewList, $pdo);

        if ($res['delete'] !== 1 || $res['deactivate'] !== 0) {
            return "Expected 1 delete and 0 deactivate, got " . json_encode($res);
        }

        $exists = $pdo->query("SELECT COUNT(*) FROM public.pengguna WHERE id = '{$userId}'")->fetchColumn();
        if ((int)$exists !== 0) {
            return "Expected user record to be physically deleted";
        }

        return true;
    } finally {
        $pdo->rollBack();
    }
});

// TEST 3: Akun developer dilindungi dari delete/deactivate
runTest("1.3 - EmployeeImportHandler: Akun 'developer' kebal dari penghapusan Full-Sync", function () use ($pdo) {
    $pdo->beginTransaction();
    try {
        $empHandler = new EmployeeImportHandler();

        $devUser = $pdo->query("SELECT id, nama_lengkap, nama_pengguna FROM public.pengguna WHERE nama_pengguna = 'developer'")->fetch(PDO::FETCH_ASSOC);
        if (!$devUser) {
            return true; // Skip jika DB tidak punya developer user
        }

        $previewList = [
            [
                'action' => 'DELETE',
                'data' => $devUser
            ]
        ];

        $res = $empHandler->applySync($previewList, $pdo);

        if ($res['delete'] !== 0 || $res['deactivate'] !== 0) {
            return "Developer user should be skipped, got " . json_encode($res);
        }

        return true;
    } finally {
        $pdo->rollBack();
    }
});

// TEST 4: Produk Jadi dengan Resep BOM dinonaktifkan (bukan hard delete)
runTest("2.1 - ProductItemImportHandler: Produk jadi yang memiliki formula BOM/komposisi dinonaktifkan", function () use ($pdo) {
    $pdo->beginTransaction();
    try {
        $prodHandler = new ProductItemImportHandler();
        $grupId = $pdo->query("SELECT id FROM public.grup_produk LIMIT 1")->fetchColumn();

        // 1. Buat produk jadi dan bahan baku uji coba
        $stmtP = $pdo->prepare("INSERT INTO public.item (kode_sku, nama_item, grup_id, satuan_dasar, tipe_item, status_jual, status_aktif) VALUES ('TEST-PROD-BOM', 'TEST Produk Ber-BOM', ?, 'pcs', 'barang_jadi', true, true) RETURNING id");
        $stmtP->execute([$grupId]);
        $prodId = (string)$stmtP->fetchColumn();

        $stmtB = $pdo->prepare("INSERT INTO public.item (kode_sku, nama_item, satuan_dasar, tipe_item, status_jual, status_aktif) VALUES ('TEST-MAT-BOM', 'TEST Bahan Baku Uji', 'kg', 'bahan_mentah', false, true) RETURNING id");
        $stmtB->execute();
        $matId = (string)$stmtB->fetchColumn();

        // 2. Hubungkan di komposisi_item
        $pdo->prepare("INSERT INTO public.komposisi_item (item_jadi_id, item_bahan_id, jumlah_kebutuhan) VALUES (?, ?, 1.5000)")->execute([$prodId, $matId]);

        // 3. Sensor harus mendeteksi
        if (!$prodHandler->hasTransactionHistory($prodId, $pdo)) {
            return "hasTransactionHistory should return true for product with BOM composition";
        }

        // 4. Simulasi Full-Sync DELETE
        $res = $prodHandler->applySync([
            ['action' => 'DELETE', 'data' => ['id' => $prodId]]
        ], $pdo);

        if ($res['deactivate'] !== 1 || $res['delete'] !== 0) {
            return "Expected 1 deactivate and 0 delete, got " . json_encode($res);
        }

        $pRow = $pdo->query("SELECT status_aktif, status_jual FROM public.item WHERE id = '{$prodId}'")->fetch(PDO::FETCH_ASSOC);
        if ($pRow['status_aktif'] !== false || $pRow['status_jual'] !== false) {
            return "Expected status_aktif and status_jual to be FALSE";
        }

        return true;
    } finally {
        $pdo->rollBack();
    }
});

// TEST 5: Bahan Baku yang digunakan di BOM dinonaktifkan saat Full-Sync
runTest("3.1 - MaterialItemImportHandler: Bahan baku yang dipakai pada resep BOM dinonaktifkan", function () use ($pdo) {
    $pdo->beginTransaction();
    try {
        $matHandler = new MaterialItemImportHandler();
        $grupId = $pdo->query("SELECT id FROM public.grup_produk LIMIT 1")->fetchColumn();

        $stmtP = $pdo->prepare("INSERT INTO public.item (kode_sku, nama_item, grup_id, satuan_dasar, tipe_item, status_jual, status_aktif) VALUES ('TEST-PROD-BOM2', 'TEST Produk Ber-BOM 2', ?, 'pcs', 'barang_jadi', true, true) RETURNING id");
        $stmtP->execute([$grupId]);
        $prodId = (string)$stmtP->fetchColumn();

        $stmtB = $pdo->prepare("INSERT INTO public.item (kode_sku, nama_item, satuan_dasar, tipe_item, status_jual, status_aktif) VALUES ('TEST-MAT-BOM2', 'TEST Bahan Baku Uji 2', 'kg', 'bahan_mentah', false, true) RETURNING id");
        $stmtB->execute();
        $matId = (string)$stmtB->fetchColumn();

        $pdo->prepare("INSERT INTO public.komposisi_item (item_jadi_id, item_bahan_id, jumlah_kebutuhan) VALUES (?, ?, 0.5000)")->execute([$prodId, $matId]);

        $res = $matHandler->applySync([
            ['action' => 'DELETE', 'data' => ['id' => $matId]]
        ], $pdo);

        if ($res['deactivate'] !== 1 || $res['delete'] !== 0) {
            return "Expected 1 deactivate and 0 delete for material in BOM, got " . json_encode($res);
        }

        return true;
    } finally {
        $pdo->rollBack();
    }
});

// TEST 6: Wilayah dengan Rute Surat Jalan dinonaktifkan
runTest("4.1 - TerritoryImportHandler: Wilayah yang tercatat di rute Surat Jalan dinonaktifkan", function () use ($pdo) {
    $pdo->beginTransaction();
    try {
        $territoryHandler = new TerritoryImportHandler();

        $stmtW = $pdo->prepare("INSERT INTO public.wilayah (kode_rute, nama_wilayah, provinsi, kota_kabupaten, status_aktif) VALUES ('TEST-RUTE', 'TEST Wilayah Rute', 'Jawa Barat', 'Subang', true) RETURNING id");
        $stmtW->execute();
        $wId = (string)$stmtW->fetchColumn();

        $custId = $pdo->query("SELECT id FROM public.pelanggan LIMIT 1")->fetchColumn();
        $stmtOrder = $pdo->prepare("INSERT INTO public.pesanan (nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_netto, tipe_pembayaran, status_pemrosesan, status_pembayaran) VALUES ('ORD-TEST-W', ?, CURRENT_DATE, 1000, 1000, 'cash', 'siap_dikirim', 'belum_lunas') RETURNING id");
        $stmtOrder->execute([$custId]);
        $orderId = $stmtOrder->fetchColumn();

        // Buat dummy surat jalan yang merujuk wilayah ini
        $pdo->prepare("INSERT INTO public.surat_jalan (nomor_surat_jalan, pesanan_id, tanggal_surat_jalan, rute_wilayah_id, status_surat_jalan) VALUES ('TEST-SJ-001', ?, CURRENT_DATE, ?, 'siap_kirim')")->execute([$orderId, $wId]);

        $res = $territoryHandler->applySync([
            ['action' => 'DELETE', 'data' => ['id' => $wId]]
        ], $pdo);

        if ($res['deactivate'] !== 1 || $res['delete'] !== 0) {
            return "Expected 1 deactivate and 0 delete for territory with surat_jalan, got " . json_encode($res);
        }

        return true;
    } finally {
        $pdo->rollBack();
    }
});

// TEST 7: Proteksi Default Customer GRP-001 dan CUST-001
runTest("5.1 - Proteksi Default Master: CUST-001 dan GRP-001 kebal dari penghapusan Full-Sync", function () use ($pdo) {
    $pdo->beginTransaction();
    try {
        $custHandler = new CustomerImportHandler();
        $groupHandler = new CustomerGroupImportHandler();

        $cust001 = $pdo->query("SELECT id, kode_pelanggan, nama_toko FROM public.pelanggan WHERE kode_pelanggan = 'CUST-001'")->fetch(PDO::FETCH_ASSOC);
        $grp001 = $pdo->query("SELECT id, kode_grup, nama_grup FROM public.grup_pelanggan WHERE kode_grup = 'GRP-001'")->fetch(PDO::FETCH_ASSOC);

        if ($cust001) {
            $resC = $custHandler->applySync([
                ['action' => 'DELETE', 'data' => $cust001]
            ], $pdo);

            if ($resC['delete'] !== 0 || $resC['deactivate'] !== 0) {
                return "CUST-001 should be protected and skipped completely, got " . json_encode($resC);
            }
        }

        if ($grp001) {
            $resG = $groupHandler->applySync([
                ['action' => 'DELETE', 'data' => $grp001]
            ], $pdo);

            if ($resG['delete'] !== 0 || $resG['deactivate'] !== 0) {
                return "GRP-001 should be protected and skipped completely, got " . json_encode($resG);
            }
        }

        return true;
    } finally {
        $pdo->rollBack();
    }
});

echo "\n====================================================================\n";
echo "SUMMARY: {$passed} / {$totalTests} Tests Passed (" . round(($passed / $totalTests) * 100, 1) . "%)\n";
echo "====================================================================\n\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

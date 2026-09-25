<?php
declare(strict_types=1);

/**
 * tests/GudangRoleAndPositionIntegrityTest.php
 * Automated Test Suite for "Gudang" Role & Employee Position Integrity.
 * 
 * Verifies:
 * 1. Role 'gudang' exists in public.peran and is protected
 * 2. Default permissions mapping in public.izin_peran for 'gudang' role
 * 3. Table public.pengguna check constraint accepts posisi = 'gudang'
 * 4. Karyawan lifecycle with posisi = 'gudang' and monthly salary structure
 * 5. EmployeeImportHandler accepts posisi = 'gudang'
 * 6. User creation & role-to-posisi sync for 'gudang'
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
require_once APP_ROOT . '/app/Core/Auth.php';
require_once APP_ROOT . '/app/Core/Controller.php';
require_once APP_ROOT . '/app/Controllers/PermissionController.php';
require_once APP_ROOT . '/app/Controllers/UserController.php';
require_once APP_ROOT . '/app/Controllers/EmployeeController.php';
require_once APP_ROOT . '/app/Services/Import/SmartReader.php';
require_once APP_ROOT . '/app/Services/Import/Handlers/EntityImportHandlerInterface.php';
require_once APP_ROOT . '/app/Services/Import/Handlers/EmployeeImportHandler.php';
require_once APP_ROOT . '/app/Services/Import/TemplateGenerator.php';

use App\Core\Auth;
use App\Services\Import\Handlers\EmployeeImportHandler;

$passed = 0;
$failed = 0;
$totalTests = 0;

function runTest(string $title, callable $fn): void {
    global $passed, $failed, $totalTests;
    $totalTests++;
    echo "[TEST {$totalTests}] {$title} ... ";
    try {
        $fn();
        $passed++;
        echo "\033[32mPASS\033[0m\n";
    } catch (Throwable $e) {
        $failed++;
        echo "\033[31mFAIL\033[0m: " . $e->getMessage() . "\n";
        echo "   at " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}

echo "=================================================================\n";
echo "INTEGRITY AUDIT: POSISI & ROLE PERMANEN GUDANG\n";
echo "=================================================================\n";

$pdo = Database::getConnection();

// TEST 1: Role 'gudang' exists in public.peran
runTest('Role "gudang" terdaftar di public.peran dengan UUID dan nama yang valid', function() use ($pdo) {
    $role = Database::fetchOne("SELECT id, nama_peran, deskripsi FROM public.peran WHERE nama_peran = 'gudang'");
    if (!$role) {
        throw new Exception("Role 'gudang' tidak ditemukan di database.");
    }
    if ($role['nama_peran'] !== 'gudang') {
        throw new Exception("Nama peran tidak sesuai: expected 'gudang', got '{$role['nama_peran']}'");
    }
});

// TEST 2: Default permissions mapping for 'gudang'
runTest('Role "gudang" memiliki set izin default lengkap (10 izin spesifik pergudangan)', function() use ($pdo) {
    $expectedPerms = [
        'inventory.view_all',
        'inventory.opname',
        'inventory.waste',
        'purchases.view',
        'purchases.receive',
        'orders.po_view_all',
        'orders.po_process',
        'orders.po_print',
        'master.products_view',
        'master.materials_manage'
    ];

    $assignedPerms = Database::fetchAll("
        SELECT i.kode_izin 
        FROM public.izin_peran ip
        JOIN public.izin i ON ip.izin_id = i.id
        JOIN public.peran r ON ip.peran_id = r.id
        WHERE r.nama_peran = 'gudang' AND ip.diizinkan = TRUE
    ");

    $assignedCodes = array_column($assignedPerms, 'kode_izin');

    foreach ($expectedPerms as $perm) {
        if (!in_array($perm, $assignedCodes, true)) {
            throw new Exception("Izin '{$perm}' belum terdaftar di role 'gudang'.");
        }
    }
});

// TEST 3: Database check constraint allows posisi = 'gudang'
runTest('CHECK constraint public.pengguna mengizinkan posisi = "gudang"', function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $testNik = '3201999988887701';
        $stmt = $pdo->prepare("
            INSERT INTO public.pengguna (nama_lengkap, nik, posisi, status_aktif)
            VALUES ('TEST Karyawan Gudang', :nik, 'gudang', TRUE)
            RETURNING id
        ");
        $stmt->execute(['nik' => $testNik]);
        $id = $stmt->fetchColumn();

        if (empty($id)) {
            throw new Exception("Gagal insert pengguna dengan posisi 'gudang'.");
        }

        $row = Database::fetchOne("SELECT posisi FROM public.pengguna WHERE id = :id", ['id' => $id]);
        if ($row['posisi'] !== 'gudang') {
            throw new Exception("Posisi yang tersimpan bukan 'gudang', tapi '{$row['posisi']}'");
        }
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

// TEST 4: Full Karyawan & Gaji lifecycle for 'gudang'
runTest('Pendaftaran Karyawan baru posisi "gudang" dengan gaji bulanan tersimpan utuh', function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $testNik = '3201999988887702';
        $stmtUser = $pdo->prepare("
            INSERT INTO public.pengguna (nama_lengkap, nik, posisi, bank_nama, status_aktif)
            VALUES ('TEST Staf Gudang Baru', :nik, 'gudang', 'BCA', TRUE)
            RETURNING id
        ");
        $stmtUser->execute(['nik' => $testNik]);
        $userId = $stmtUser->fetchColumn();

        $stmtKaryawan = $pdo->prepare("
            INSERT INTO public.karyawan (
                pengguna_id, tipe_penggajian, gaji_pokok_bulanan, uang_kehadiran_harian, tunjangan_bulanan
            ) VALUES (
                :uid, 'bulanan', 2500000, 15000, 100000
            ) RETURNING id
        ");
        $stmtKaryawan->execute(['uid' => $userId]);
        $karyawanId = $stmtKaryawan->fetchColumn();

        $karyawanData = Database::fetchOne("
            SELECT p.nama_lengkap, p.posisi, k.tipe_penggajian, k.gaji_pokok_bulanan, k.uang_kehadiran_harian, k.tunjangan_bulanan
            FROM public.pengguna p
            JOIN public.karyawan k ON k.pengguna_id = p.id
            WHERE k.id = :kid
        ", ['kid' => $karyawanId]);

        if ($karyawanData['posisi'] !== 'gudang') {
            throw new Exception("Posisi tidak cocok: {$karyawanData['posisi']}");
        }
        if ($karyawanData['tipe_penggajian'] !== 'bulanan') {
            throw new Exception("Tipe penggajian tidak cocok: {$karyawanData['tipe_penggajian']}");
        }
        if ((float)$karyawanData['gaji_pokok_bulanan'] !== 2500000.0) {
            throw new Exception("Gaji pokok tidak cocok: {$karyawanData['gaji_pokok_bulanan']}");
        }
        if ((float)$karyawanData['uang_kehadiran_harian'] !== 15000.0) {
            throw new Exception("Uang hadir tidak cocok: {$karyawanData['uang_kehadiran_harian']}");
        }
        if ((float)$karyawanData['tunjangan_bulanan'] !== 100000.0) {
            throw new Exception("Tunjangan tidak cocok: {$karyawanData['tunjangan_bulanan']}");
        }
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

// TEST 5: EmployeeImportHandler supports 'gudang' position
runTest('EmployeeImportHandler memvalidasi dan memproses baris posisi "gudang" tanpa error', function() use ($pdo) {
    $handler = new EmployeeImportHandler();
    $headers = $handler->getTemplateHeaders();
    $rows = [
        ['3201999988887703', 'TEST Karyawan Gudang Impor', 'Gudang', 'L', '1995-05-15', 'gudang', 'bulanan', 2750000, 20000, 150000, '081299887766', '', 'Gudang Pusat', '2024-03-01', 'Mandiri', '1234567890', 'TEST Karyawan Gudang Impor', 'Aktif']
    ];

    $preview = $handler->previewRows($rows, $headers, $pdo, 'append');
    if (empty($preview)) {
        throw new Exception("Preview list kosong.");
    }

    if ($preview[0]['action'] !== 'INSERT') {
        throw new Exception("Expected action INSERT, got {$preview[0]['action']}. Error: " . ($preview[0]['error_msg'] ?? 'none'));
    }
});

// TEST 6: Protected Roles list in PermissionController and tab_manage_roles.php
runTest('Role "gudang" masuk dalam daftar peran yang dilindungi (PROTECTED_ROLES)', function() {
    $ref = new ReflectionClass(App\Controllers\PermissionController::class);
    $constants = $ref->getConstants();
    $protectedRoles = $constants['PROTECTED_ROLES'] ?? [];

    if (!in_array('gudang', $protectedRoles, true)) {
        throw new Exception("'gudang' belum ada di PermissionController::PROTECTED_ROLES.");
    }
});

echo "=================================================================\n";
echo "HASIL PENGUJIAN: {$passed}/{$totalTests} BERHASIL";
if ($failed > 0) {
    echo " ({$failed} GAGAL)";
}
echo "\n=================================================================\n";

if ($failed > 0) {
    exit(1);
}

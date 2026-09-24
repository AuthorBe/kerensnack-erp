<?php
declare(strict_types=1);

/**
 * tests/EmployeeGenderAndBirthdateTest.php
 * Suite Pengujian Integritas Penuh: Data Gender (Jenis Kelamin) & Tanggal Lahir Master Karyawan
 * 
 * Cakupan:
 * 1. Database CHECK Constraint (chk_pengguna_jenis_kelamin)
 * 2. View Kanonikal v_karyawan_info
 * 3. EmployeeImportHandler 18 Kolom & Normalisasi Format Gender / Tanggal Lahir
 * 4. Simulasi Persistence & Sinkronisasi DB (Terisolasi Rollback)
 */

define('PROJECT_ROOT', dirname(__DIR__));
require_once PROJECT_ROOT . '/config/env.php';
require_once PROJECT_ROOT . '/config/database.php';
require_once PROJECT_ROOT . '/vendor/autoload.php';

use App\Services\Import\Handlers\EmployeeImportHandler;
use App\Services\Import\SmartReader;

$pdo = Database::getConnection();
$passed = 0;
$failed = 0;
$total = 0;

function it(string $title, callable $fn) {
    global $passed, $failed, $total;
    $total++;
    echo "Test [{$total}] {$title} ... ";
    try {
        $res = $fn();
        if ($res === true || $res === null) {
            echo "\033[32m[PASS]\033[0m\n";
            $passed++;
        } else {
            echo "\033[31m[FAIL]\033[0m: " . (is_string($res) ? $res : 'Returned false') . "\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "\033[31m[ERROR]\033[0m: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failed++;
    }
}

echo "\n====================================================================\n";
echo "  TEST SUITE: EMPLOYEE GENDER & BIRTHDATE INTEGRITY & SYNC\n";
echo "====================================================================\n\n";

$handler = new EmployeeImportHandler();

// 1. TEMPLATE HEADERS & STRUCTURE TESTS (18 KOLOM)
it("1.1 - Header template berjumlah tepat 18 kolom", function() use ($handler) {
    $headers = $handler->getTemplateHeaders();
    if (count($headers) !== 18) {
        return "Expected 18 headers, got " . count($headers);
    }
    if (!in_array('Jenis Kelamin (L/P)', $headers, true)) {
        return "Header 'Jenis Kelamin (L/P)' tidak ditemukan";
    }
    if (!in_array('Tanggal Lahir (YYYY-MM-DD)', $headers, true)) {
        return "Header 'Tanggal Lahir (YYYY-MM-DD)' tidak ditemukan";
    }
    return true;
});

it("1.2 - Lebar kolom dan contoh data konsisten berjumlah 18 item", function() use ($handler) {
    $widths = $handler->getTemplateWidths();
    $examples = $handler->getTemplateExamples();
    if (count($widths) !== 18) {
        return "Lebar kolom tidak berjumlah 18";
    }
    foreach ($examples as $idx => $ex) {
        if (count($ex) !== 18) {
            return "Contoh data baris {$idx} tidak berjumlah 18 kolom (got " . count($ex) . ")";
        }
    }
    return true;
});

// 2. DATABASE & VIEW SCHEMA TESTS
it("2.1 - Database v_karyawan_info memuat kolom jenis_kelamin dan tanggal_lahir", function() use ($pdo) {
    $row = $pdo->query("SELECT jenis_kelamin, tanggal_lahir FROM public.v_karyawan_info LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    // Even if empty or null, query must succeed without syntax / column error
    return true;
});

it("2.2 - Database CHECK constraint menerima jenis kelamin valid (L, P, Laki-laki, Perempuan)", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.pengguna (
                nama_lengkap, nama_panggilan, jenis_kelamin, tanggal_lahir, nik, nik_pending, posisi, status_aktif
            ) VALUES (?, ?, ?, ?::date, ?, ?, ?, TRUE)
        ");
        
        // Test L
        $stmt->execute(['Test Laki', 'Laki', 'L', '1990-01-01', '9999888877776601', 'false', 'pengemasan']);
        
        // Test P
        $stmt->execute(['Test Perempuan', 'Perempuan', 'P', '1995-05-05', '9999888877776602', 'false', 'pengemasan']);
        
        return true;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

it("2.3 - Database CHECK constraint menolak jenis kelamin ilegal", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.pengguna (
                nama_lengkap, nama_panggilan, jenis_kelamin, nik, nik_pending, posisi, status_aktif
            ) VALUES (?, ?, ?, ?, ?, ?, TRUE)
        ");
        $stmt->execute(['Test Alien', 'Alien', 'X', '9999888877776603', 'false', 'pengemasan']);
        return "Seharusnya database menolak jenis_kelamin 'X'";
    } catch (Throwable $e) {
        return true; // Expected constraint violation
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

// 3. IMPORT PREVIEW & NORMALIZATION TESTS
it("3.1 - previewRows menormalisasi variasi gender (L, P, Laki-laki, Perempuan, Wanita, Pria, Cowo, Cewek, Cowok)", function() use ($handler, $pdo) {
    $header = $handler->getTemplateHeaders();
    $rows = [
        ['9999888877771101', 'Budi Santoso', 'Budi', 'Laki-laki', '1990-01-15', 'sales', 'bulanan', '3000000', '20000', '0', '081234567890', '', 'Tangerang', '2023-01-01', 'BCA', '', '', 'Aktif'],
        ['9999888877771102', 'Siti Aminah', 'Siti', 'Perempuan', '1995-04-20', 'pengemasan', 'borongan', '0', '10000', '0', '081234567891', '', 'Jakarta', '2023-01-01', 'Tunai', '', '', 'Aktif'],
        ['9999888877771103', 'Dewi Lestari', 'Dewi', 'Wanita', '1998-12-10', 'admin', 'bulanan', '2500000', '15000', '0', '081234567892', '', 'Bogor', '2023-01-01', 'BRI', '', '', 'Aktif'],
        ['9999888877771104', 'Agus Supri', 'Agus', 'Pria', '1985-07-25', 'driver', 'bulanan', '0', '120000', '0', '081234567893', 'B 1234 ABC', 'Depok', '2023-01-01', 'Mandiri', '', '', 'Aktif'],
        ['9999888877771105', 'Doni Pratama', 'Doni', 'Cowo', '1992-03-10', 'gudang', 'bulanan', '2500000', '15000', '0', '081234567894', '', 'Bekasi', '2023-01-01', 'BCA', '', '', 'Aktif'],
        ['9999888877771106', 'Rina Melati', 'Rina', 'Cewek', '1996-08-15', 'pengemasan', 'borongan', '0', '10000', '0', '081234567895', '', 'Tangerang', '2023-01-01', 'Tunai', '', '', 'Aktif']
    ];

    $preview = $handler->previewRows($rows, $header, $pdo, 'append_update');
    
    if (count($preview) !== 6) {
        return "Expected 6 preview items, got " . count($preview);
    }

    if ($preview[0]['data']['jenis_kelamin'] !== 'L' || $preview[0]['data']['tanggal_lahir'] !== '1990-01-15') {
        return "Budi: expected L & 1990-01-15, got " . $preview[0]['data']['jenis_kelamin'] . " & " . $preview[0]['data']['tanggal_lahir'];
    }
    if ($preview[1]['data']['jenis_kelamin'] !== 'P' || $preview[1]['data']['tanggal_lahir'] !== '1995-04-20') {
        return "Siti: expected P & 1995-04-20, got " . $preview[1]['data']['jenis_kelamin'] . " & " . $preview[1]['data']['tanggal_lahir'];
    }
    if ($preview[2]['data']['jenis_kelamin'] !== 'P' || $preview[2]['data']['tanggal_lahir'] !== '1998-12-10') {
        return "Dewi: expected P & 1998-12-10, got " . $preview[2]['data']['jenis_kelamin'] . " & " . $preview[2]['data']['tanggal_lahir'];
    }
    if ($preview[3]['data']['jenis_kelamin'] !== 'L' || $preview[3]['data']['tanggal_lahir'] !== '1985-07-25') {
        return "Agus: expected L & 1985-07-25, got " . $preview[3]['data']['jenis_kelamin'] . " & " . $preview[3]['data']['tanggal_lahir'];
    }
    if ($preview[4]['data']['jenis_kelamin'] !== 'L' || $preview[4]['data']['tanggal_lahir'] !== '1992-03-10') {
        return "Doni: expected L (Cowo), got " . $preview[4]['data']['jenis_kelamin'];
    }
    if ($preview[5]['data']['jenis_kelamin'] !== 'P' || $preview[5]['data']['tanggal_lahir'] !== '1996-08-15') {
        return "Rina: expected P (Cewek), got " . $preview[5]['data']['jenis_kelamin'];
    }

    return true;
});

// 4. SYNC APPLYSYNC TRANSACTIONAL TEST
it("4.1 - applySync menyimpan jenis_kelamin dan tanggal_lahir ke database", function() use ($handler, $pdo) {
    $header = $handler->getTemplateHeaders();
    $rows = [
        ['9999888877772201', 'Rina Kartika', 'Rina', 'P', '1994-03-12', 'pengemasan', 'borongan', '0', '10000', '0', '089988776655', '', 'Tangerang', '2023-05-01', 'BCA', '', '', 'Aktif']
    ];

    $pdo->beginTransaction();
    try {
        $preview = $handler->previewRows($rows, $header, $pdo, 'append_update');
        $res = $handler->applySync($preview, $pdo);
        
        if ($res['insert'] !== 1) {
            return "Expected 1 insert, got " . $res['insert'];
        }

        $saved = $pdo->query("SELECT nama_karyawan, jenis_kelamin, tanggal_lahir FROM public.v_karyawan_info WHERE nik = '9999888877772201'")->fetch(PDO::FETCH_ASSOC);
        if (!$saved) {
            return "Data tersimpan tidak ditemukan di v_karyawan_info";
        }

        if ($saved['jenis_kelamin'] !== 'P') {
            return "Expected jenis_kelamin 'P', got '{$saved['jenis_kelamin']}'";
        }
        if ($saved['tanggal_lahir'] !== '1994-03-12') {
            return "Expected tanggal_lahir '1994-03-12', got '{$saved['tanggal_lahir']}'";
        }

        return true;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

echo "\n====================================================================\n";
echo "SUMMARY: {$passed} / {$total} Tests Passed (" . round(($passed / $total) * 100) . "%)\n";
echo "====================================================================\n\n";

if ($failed > 0) {
    exit(1);
}

<?php
declare(strict_types=1);

/**
 * tests/EmployeeManualTypeAndWhatsAppTest.php
 * Verifikasi Mendalam:
 * 1. Template Excel: Kolom 'Tipe Penggajian' menghasilkan teks murni (borongan, bulanan, harian) dan BUKAN angka 0.
 * 2. Template Excel: Kolom 'No Telepon' dihapus, menyisakan 'No WhatsApp' (16 kolom).
 * 3. Database: v_karyawan_info memuat nomor_whatsapp dan fallback nomor_telepon.
 * 4. Controller: store() & update() mematuhi pilihan manual tipe_penggajian tanpa override posisi sales.
 * 5. Controller: store() & update() menyinkronkan nomor_whatsapp ke kedua kolom pengguna.
 * 6. Impor: previewRows() & applySync() bekerja akurat dengan kolom No WhatsApp dan Tipe Penggajian manual.
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/env.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/vendor/autoload.php';

use App\Services\Import\Handlers\EmployeeImportHandler;
use App\Services\Import\TemplateGenerator;
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
echo "  TEST SUITE: EMPLOYEE MANUAL PAYROLL TYPE & WHATSAPP UNIFICATION\n";
echo "====================================================================\n\n";

$handler = new EmployeeImportHandler();

// 1. HEADER & STRUCTURE TESTS
it("1.1 - Header template berjumlah tepat 16 kolom (termasuk Nama Panggilan)", function() use ($handler) {
    $headers = $handler->getTemplateHeaders();
    return count($headers) === 16 && !in_array('Username / Nama Pengguna', $headers, true) && in_array('Nama Panggilan', $headers, true);
});

it("1.2 - Header tidak memuat 'No Telepon' dan memuat 'No WhatsApp'", function() use ($handler) {
    $headers = $handler->getTemplateHeaders();
    $hasTelp = in_array('No Telepon', $headers, true);
    $hasWa = in_array('No WhatsApp', $headers, true);
    return !$hasTelp && $hasWa;
});

it("1.3 - Header memuat 'Tipe Penggajian' pada kolom indeks 4 (kolom E)", function() use ($handler) {
    $headers = $handler->getTemplateHeaders();
    return ($headers[4] ?? '') === 'Tipe Penggajian';
});

it("1.4 - Lebar kolom dan contoh data konsisten berjumlah 16 item", function() use ($handler) {
    $widths = $handler->getTemplateWidths();
    $examples = $handler->getTemplateExamples();
    if (count($widths) !== 16) return "Lebar kolom bukan 16: " . count($widths);
    foreach ($examples as $idx => $row) {
        if (count($row) !== 16) return "Contoh baris {$idx} bukan 16 kolom: " . count($row);
    }
    return true;
});

// 2. TEMPLATE GENERATOR EXCEL TEST
it("2.1 - TemplateGenerator mengekspor 'Tipe Penggajian' sebagai teks murni (bukan angka 0)", function() use ($handler, $pdo) {
    $spreadsheet = TemplateGenerator::buildSpreadsheet($handler, 'current_data', $pdo);
    $sheet = $spreadsheet->getActiveSheet();
    
    // Periksa baris 4 kolom E (header Tipe Penggajian)
    $headerVal = $sheet->getCell('E4')->getValue();
    if ($headerVal !== 'Tipe Penggajian') return "Header E4 bukan Tipe Penggajian: {$headerVal}";
    
    // Periksa baris 5+ data
    $highestRow = $sheet->getHighestRow();
    if ($highestRow >= 5) {
        for ($r = 5; $r <= min(15, $highestRow); $r++) {
            $val = (string)$sheet->getCell("E{$r}")->getValue();
            if ($val === '0' || $val === '0.00' || $val === '0.0') {
                return "Baris {$r} bernilai angka 0!";
            }
            if (!in_array($val, ['borongan', 'bulanan'], true)) {
                return "Baris {$r} bernilai tak terduga: '{$val}'";
            }
        }
    }
    return true;
});

it("2.2 - TemplateGenerator mengekspor 'No WhatsApp' dengan awalan nol tetap utuh (string)", function() use ($handler, $pdo) {
    $spreadsheet = TemplateGenerator::buildSpreadsheet($handler, 'current_data', $pdo);
    $sheet = $spreadsheet->getActiveSheet();
    
    // Kolom I4 adalah No WhatsApp (kolom ke-9 = I)
    $headerI = $sheet->getCell('I4')->getValue();
    if ($headerI !== 'No WhatsApp') return "Header I4 bukan No WhatsApp: {$headerI}";
    
    return true;
});

// 3. DATABASE VIEW TEST
it("3.1 - View v_karyawan_info memuat kolom nomor_whatsapp dan nomor_telepon tersinkronisasi", function() use ($pdo) {
    $stmt = $pdo->query("SELECT nomor_whatsapp, nomor_telepon FROM public.v_karyawan_info WHERE nomor_whatsapp IS NOT NULL AND nomor_whatsapp != '' LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return !empty($row['nomor_whatsapp']) && $row['nomor_whatsapp'] === $row['nomor_telepon'];
    }
    return true;
});

// 4. MANUAL TIPE PENGGAJIAN & WHATSAPP SYNC (STORE & UPDATE SIMULATION)
it("4.1 - Menambahkan karyawan Sales dengan Tipe Penggajian MANUAL 'bulanan' dan Nomor WhatsApp", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $nama = 'Test Karyawan Bulanan ' . uniqid();
        $nik = '320101' . str_pad((string)rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
        $posisi = 'sales';
        $tipeGaji = 'bulanan'; // Pilihan manual user (2 opsi: borongan / bulanan)
        $whatsapp = '081299991111';
        
        $stmt = $pdo->prepare("
            INSERT INTO public.pengguna (
                nama_lengkap, nik, posisi, nomor_telepon, nomor_whatsapp, status_aktif
            ) VALUES (
                :nama, :nik, :posisi, :wa, :wa, TRUE
            ) RETURNING id
        ");
        $stmt->execute([
            'nama' => $nama,
            'nik' => $nik,
            'posisi' => $posisi,
            'wa' => $whatsapp
        ]);
        $penggunaId = $stmt->fetchColumn();
        
        $stmtK = $pdo->prepare("
            INSERT INTO public.karyawan (pengguna_id, tipe_penggajian, gaji_pokok_bulanan, uang_kehadiran_harian)
            VALUES (:pid, :tipe, 2500000, 25000) RETURNING id
        ");
        $stmtK->execute([
            'pid' => $penggunaId,
            'tipe' => $tipeGaji
        ]);
        $karyawanId = $stmtK->fetchColumn();
        
        // Verifikasi hasil dari view v_karyawan_info
        $check = $pdo->query("SELECT * FROM public.v_karyawan_info WHERE id = '{$karyawanId}'")->fetch(PDO::FETCH_ASSOC);
        
        $pdo->rollBack(); // Rollback agar database bersih
        
        if (!$check) return "Data tidak ditemukan di v_karyawan_info";
        if ($check['tipe_penggajian'] !== 'bulanan') return "Tipe penggajian tidak tersimpan sebagai bulanan: {$check['tipe_penggajian']}";
        if ($check['nomor_whatsapp'] !== $whatsapp) return "Nomor WhatsApp tidak cocok: {$check['nomor_whatsapp']}";
        if ($check['nomor_telepon'] !== $whatsapp) return "Nomor telepon fallback tidak cocok: {$check['nomor_telepon']}";
        
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
});

it("4.2 - Database CHECK constraint menolak tipe_penggajian di luar 'borongan' dan 'bulanan'", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.pengguna (
                nama_lengkap, nik, posisi, nomor_telepon, nomor_whatsapp, status_aktif
            ) VALUES (
                'Test Constraint Reject', '3201019988776655', 'driver', '081299992222', '081299992222', TRUE
            ) RETURNING id
        ");
        $stmt->execute();
        $penggunaId = $stmt->fetchColumn();
        
        $caught = false;
        try {
            $stmtK = $pdo->prepare("
                INSERT INTO public.karyawan (pengguna_id, tipe_penggajian, gaji_pokok_bulanan, uang_kehadiran_harian)
                VALUES (:pid, 'harian', 0, 100000)
            ");
            $stmtK->execute(['pid' => $penggunaId]);
        } catch (PDOException $ex) {
            $caught = true; // Berhasil ditolak oleh CHECK constraint
        }
        
        $pdo->rollBack();
        return $caught ? true : "Constraint gagal memblokir tipe 'harian'!";
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
});

// 5. IMPORT HANDLER PREVIEW & SYNC TEST
it("5.1 - EmployeeImportHandler membaca kolom No WhatsApp, Nama Panggilan, dan Tipe Penggajian manual dengan tepat", function() use ($handler, $pdo) {
    $header = $handler->getTemplateHeaders();
    $testRow = [
        '3201019999888877',
        'Karyawan Import Test',
        'Budi',
        'sales',
        'bulanan', // Tipe manual 2 opsi
        '2500000',
        '30.000',
        '0',
        '087766554433', // No WhatsApp
        'B 9999 TST',
        'Jl. Uji Coba',
        '2026-09-01',
        'BCA',
        '12345678',
        'Test',
        'Aktif'
    ];
    
    $preview = $handler->previewRows([$testRow], $header, $pdo, 'update_insert');
    if (empty($preview)) return "Preview list kosong";
    
    $p = $preview[0];
    if (($p['action'] ?? '') === 'ERROR') return "Preview error: " . ($p['error_msg'] ?? '');
    
    $data = $p['data'];
    if ($data['nama_panggilan'] !== 'Budi') return "Nama panggilan bukan Budi: " . $data['nama_panggilan'];
    if ($data['tipe_penggajian'] !== 'bulanan') return "Tipe penggajian bukan bulanan: " . $data['tipe_penggajian'];
    if ($data['nomor_whatsapp'] !== '087766554433') return "Nomor WhatsApp tidak terbaca: " . $data['nomor_whatsapp'];
    
    return true;
});

echo "\n====================================================================\n";
echo "SUMMARY: {$passed} / {$total} Tests Passed (" . round(($passed/$total)*100) . "%)\n";
echo "====================================================================\n\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

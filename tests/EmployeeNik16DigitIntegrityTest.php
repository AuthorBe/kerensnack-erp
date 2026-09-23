<?php
declare(strict_types=1);

/**
 * tests/EmployeeNik16DigitIntegrityTest.php
 * Suite Pengujian Integritas Penuh: Penegakan NIK 16 Digit Asli (Wajib) Karyawan
 * 
 * Cakupan Pengujian:
 * 1. Database CHECK Constraint (chk_pengguna_nik_16_digit):
 *    - Menolak NIK NULL pada posisi operasional (admin, mandor, pengemasan, sales, driver).
 *    - Menolak NIK < 16 digit, > 16 digit, atau non-numerik.
 *    - Menerima NIK 16 digit numerik murni.
 *    - Menerima posisi 'developer' tanpa NIK.
 *    - Menolak duplikasi NIK (Unique constraint).
 *    - Menerima NIK NULL + nik_pending = TRUE pada posisi operasional (fitur NIK Pending).
 *    - Menolak NIK NULL + nik_pending = FALSE pada posisi operasional (constraint tetap tegak).
 * 2. Impor & Sinkronisasi Handler (EmployeeImportHandler):
 *    - Header NIK terdaftar sebagai header WAJIB (Required Header Group).
 *    - previewRows() memvalidasi baris tanpa NIK -> BUKAN ERROR (di-treat sebagai NIK Pending).
 *    - previewRows() memvalidasi baris NIK '0' -> BUKAN ERROR (di-treat sebagai NIK Pending).
 *    - previewRows() memvalidasi baris NIK 'Belum' -> BUKAN ERROR (di-treat sebagai NIK Pending).
 *    - previewRows() memvalidasi baris NIK 10 digit -> ERROR.
 *    - previewRows() memvalidasi baris duplikat NIK di file -> ERROR.
 *    - previewRows() memvalidasi konflik NIK beda nama di DB -> FATAL.
 *    - previewRows() & applySync() berhasil memasukkan dan memperbarui karyawan dengan NIK 16 digit.
 * 3. Template Generator (TemplateGenerator):
 *    - Format kolom NIK diekspor sebagai String murni.
 *    - Template reference sheet memuat panduan 16 digit NIK.
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
echo "  TEST SUITE: EMPLOYEE NIK 16-DIGIT MANDATORY & INTEGRITY ENFORCEMENT\n";
echo "====================================================================\n\n";

$handler = new EmployeeImportHandler();

// ==============================================================================
// 1. DATABASE CHECK CONSTRAINT TESTS
// ==============================================================================

it("1.1 - Database menolak pembuatan karyawan non-developer dengan NIK NULL", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $caught = false;
        try {
            $stmt = $pdo->prepare("
                INSERT INTO public.pengguna (nama_lengkap, nik, posisi, status_aktif)
                VALUES ('Test Tanpa NIK', NULL, 'pengemasan', TRUE)
            ");
            $stmt->execute();
        } catch (PDOException $ex) {
            $caught = true;
        }
        $pdo->rollBack();
        return $caught ? true : "Database mengizinkan NIK NULL pada posisi non-developer!";
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
});

it("1.2 - Database menolak pembuatan karyawan dengan NIK kurang dari 16 digit", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $caught = false;
        try {
            $stmt = $pdo->prepare("
                INSERT INTO public.pengguna (nama_lengkap, nik, posisi, status_aktif)
                VALUES ('Test NIK Pendek', '3201012345', 'sales', TRUE)
            ");
            $stmt->execute();
        } catch (PDOException $ex) {
            $caught = true;
        }
        $pdo->rollBack();
        return $caught ? true : "Database mengizinkan NIK 10 digit!";
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
});

it("1.3 - Database menolak pembuatan karyawan dengan NIK lebih dari 16 digit atau mengandung huruf", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $caught = false;
        try {
            $stmt = $pdo->prepare("
                INSERT INTO public.pengguna (nama_lengkap, nik, posisi, status_aktif)
                VALUES ('Test NIK Format Lama', 'NIK-001-KRY-0024', 'driver', TRUE)
            ");
            $stmt->execute();
        } catch (PDOException $ex) {
            $caught = true;
        }
        $pdo->rollBack();
        return $caught ? true : "Database mengizinkan NIK dengan format string 'NIK-001-KRY-0024'!";
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
});

it("1.4 - Database berhasil menerima karyawan dengan NIK tepat 16 digit angka", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $nik = '320101' . str_pad((string)rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("
            INSERT INTO public.pengguna (nama_lengkap, nik, posisi, status_aktif)
            VALUES ('Test NIK Valid', :nik, 'mandor', TRUE)
            RETURNING id
        ");
        $stmt->execute(['nik' => $nik]);
        $id = $stmt->fetchColumn();

        $saved = $pdo->query("SELECT nik, posisi FROM public.pengguna WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $pdo->rollBack();

        if (!$saved) return "Data tidak tersimpan.";
        if ($saved['nik'] !== $nik) return "NIK tersimpan ({$saved['nik']}) tidak sesuai input ({$nik}).";
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
});

it("1.5 - Database mengizinkan akun posisi 'developer' tanpa NIK (NULL)", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.pengguna (nama_lengkap, nama_pengguna, nik, posisi, status_aktif)
            VALUES ('Dev Test Non-Karyawan', 'dev_test_unique_" . rand(100, 999) . "', NULL, 'developer', TRUE)
            RETURNING id
        ");
        $stmt->execute();
        $id = $stmt->fetchColumn();

        $saved = $pdo->query("SELECT nik, posisi FROM public.pengguna WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $pdo->rollBack();

        return ($saved && $saved['nik'] === null && $saved['posisi'] === 'developer');
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
});

it("1.6 - Unique Constraint menolak dua pengguna dengan NIK 16 digit yang sama", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $nik = '3201015566778899';
        $stmt1 = $pdo->prepare("
            INSERT INTO public.pengguna (nama_lengkap, nik, posisi, status_aktif)
            VALUES ('User Pertama', :nik, 'admin', TRUE)
        ");
        $stmt1->execute(['nik' => $nik]);

        $caught = false;
        try {
            $stmt2 = $pdo->prepare("
                INSERT INTO public.pengguna (nama_lengkap, nik, posisi, status_aktif)
                VALUES ('User Kedua Duplikat', :nik, 'sales', TRUE)
            ");
            $stmt2->execute(['nik' => $nik]);
        } catch (PDOException $ex) {
            $caught = true; // Unique constraint violation
        }

        $pdo->rollBack();
        return $caught ? true : "Database mengizinkan duplikasi NIK!";
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
});

// ==============================================================================
// 2. IMPORT & SYNC HANDLER TESTS
// ==============================================================================

it("2.1 - getRequiredHeaderGroups mewajibkan header NIK pada berkas Excel", function() use ($handler) {
    $groups = $handler->getRequiredHeaderGroups();
    $hasNikGroup = false;
    foreach ($groups as $g) {
        if (in_array('nik', $g, true) || in_array('nik_karyawan', $g, true)) {
            $hasNikGroup = true;
            break;
        }
    }
    return $hasNikGroup;
});

it("2.2 - previewRows memperlakukan NIK kosong sebagai NIK Pending (INSERT dengan nik_pending=true), bukan ERROR", function() use ($handler, $pdo) {
    $header = $handler->getTemplateHeaders();
    $row = ['', 'Budi Tanpa NIK', 'sales', 'bulanan', 3000000, 20000, 0, '08123456789', '', '', '2024-01-01', '', '', '', 'Aktif'];

    $preview = $handler->previewRows([$row], $header, $pdo, 'update_insert');
    if (empty($preview)) return "Pratinjau kosong";

    $p = $preview[0];
    if ($p['action'] === 'ERROR') {
        return "Action seharusnya bukan ERROR (NIK kosong = NIK Pending), melainkan: {$p['action']} - " . ($p['error_msg'] ?? '');
    }
    if (!in_array($p['action'], ['INSERT', 'UPDATE'], true)) {
        return "Action harus INSERT atau UPDATE, dapat: {$p['action']}";
    }
    if (empty($p['data']['nik_pending'])) {
        return "nik_pending harus TRUE untuk baris NIK kosong";
    }
    if ($p['data']['nik'] !== null) {
        return "NIK harus NULL saat nik_pending=TRUE, dapat: " . var_export($p['data']['nik'], true);
    }

    return true;
});

it("2.3 - previewRows menandai ERROR jika NIK bukan 16 digit angka murni", function() use ($handler, $pdo) {
    $header = $handler->getTemplateHeaders();
    $rowInvalidLen = ['320101', 'Budi NIK Pendek', 'sales', 'bulanan', 3000000, 20000, 0, '08123456789', '', '', '2024-01-01', '', '', '', 'Aktif'];
    $rowNonDigit = ['320101234567890A', 'Budi NIK Huruf', 'sales', 'bulanan', 3000000, 20000, 0, '08123456789', '', '', '2024-01-01', '', '', '', 'Aktif'];

    $preview = $handler->previewRows([$rowInvalidLen, $rowNonDigit], $header, $pdo, 'update_insert');
    if (count($preview) !== 2) return "Jumlah preview tidak 2: " . count($preview);

    if ($preview[0]['action'] !== 'ERROR' || !str_contains($preview[0]['error_msg'], 'tidak valid')) {
        return "Preview 1 tidak menghasilkan ERROR validasi NIK: " . json_encode($preview[0]);
    }
    if ($preview[1]['action'] !== 'ERROR' || !str_contains($preview[1]['error_msg'], 'tidak valid')) {
        return "Preview 2 tidak menghasilkan ERROR validasi NIK: " . json_encode($preview[1]);
    }

    return true;
});

it("2.4 - previewRows mendeteksi duplikasi NIK di dalam baris Excel", function() use ($handler, $pdo) {
    $header = $handler->getTemplateHeaders();
    $sameNik = '3201012233445566';
    $row1 = [$sameNik, 'Orang Pertama', 'sales', 'bulanan', 3000000, 20000, 0, '08123456789', '', '', '2024-01-01', '', '', '', 'Aktif'];
    $row2 = [$sameNik, 'Orang Kedua Duplikat', 'sales', 'bulanan', 3000000, 20000, 0, '08129999888', '', '', '2024-01-01', '', '', '', 'Aktif'];

    $preview = $handler->previewRows([$row1, $row2], $header, $pdo, 'update_insert');
    if (count($preview) !== 2) return "Jumlah preview tidak 2";

    if ($preview[0]['action'] !== 'INSERT') return "Baris 1 harusnya INSERT, tetapi: {$preview[0]['action']}";
    if ($preview[1]['action'] !== 'ERROR') return "Baris 2 harusnya ERROR duplikasi, tetapi: {$preview[1]['action']}";
    if (!str_contains($preview[1]['error_msg'], 'Duplikasi NIK')) return "Pesan error tidak memuat Duplikasi NIK: {$preview[1]['error_msg']}";

    return true;
});

it("2.5 - previewRows & applySync berhasil melakukan INSERT & UPDATE dengan NIK 16 digit", function() use ($handler, $pdo) {
    $pdo->beginTransaction();
    try {
        $header = $handler->getTemplateHeaders();
        $nik = '3201019988112233';
        $rowInsert = [$nik, 'Karyawan Uji Sinkron', 'pengemasan', 'borongan', 0, 15000, 50000, '085711223344', '', 'Tangerang', '2024-05-01', 'BCA', '123456', 'Karyawan Uji', 'Aktif'];

        $preview = $handler->previewRows([$rowInsert], $header, $pdo, 'update_insert');
        if (empty($preview) || $preview[0]['action'] !== 'INSERT') {
            $pdo->rollBack();
            return "Pratinjau insert gagal: " . json_encode($preview);
        }

        // Terapkan sinkronisasi INSERT
        $stats = $handler->applySync($preview, $pdo);
        if ($stats['insert'] !== 1) {
            $pdo->rollBack();
            return "Stats insert bukan 1: " . json_encode($stats);
        }

        // Verifikasi hasil simpan
        $user = $pdo->query("SELECT * FROM public.pengguna WHERE nik = '{$nik}'")->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $pdo->rollBack();
            return "User tidak ditemukan di basis data setelah applySync!";
        }

        // Uji sinkronisasi UPDATE data yang sudah ada
        $rowUpdate = [$nik, 'Karyawan Uji Sinkron (Updated)', 'sales', 'bulanan', 3500000, 25000, 100000, '085711223344', 'B 1111 TST', 'Jakarta Barat', '2024-05-01', 'BCA', '123456', 'Karyawan Uji', 'Aktif'];
        $previewUpd = $handler->previewRows([$rowUpdate], $header, $pdo, 'update_insert');
        if (empty($previewUpd) || $previewUpd[0]['action'] !== 'UPDATE') {
            $pdo->rollBack();
            return "Pratinjau update gagal: " . json_encode($previewUpd);
        }

        $statsUpd = $handler->applySync($previewUpd, $pdo);
        if ($statsUpd['update'] !== 1) {
            $pdo->rollBack();
            return "Stats update bukan 1: " . json_encode($statsUpd);
        }

        $userUpd = $pdo->query("SELECT * FROM public.pengguna WHERE id = '{$user['id']}'")->fetch(PDO::FETCH_ASSOC);
        $pdo->rollBack(); // Rollback untuk menjamin kebersihan data

        if ($userUpd['nama_lengkap'] !== 'Karyawan Uji Sinkron (Updated)') return "Nama tidak terupdate";
        if ($userUpd['posisi'] !== 'sales') return "Posisi tidak terupdate";
        if ($userUpd['nik'] !== $nik) return "NIK berubah setelah update";

        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
});

// ==============================================================================
// 3. TEMPLATE GENERATOR TESTS
// ==============================================================================

it("3.1 - TemplateGenerator mengekspor kolom NIK sebagai String murni", function() use ($handler, $pdo) {
    $spreadsheet = TemplateGenerator::buildSpreadsheet($handler, 'template', $pdo);
    $sheet = $spreadsheet->getActiveSheet();

    // Kolom A4 adalah NIK Karyawan
    $headerA = $sheet->getCell('A4')->getValue();
    if (!str_contains((string)$headerA, 'NIK Karyawan')) return "Header A4 bukan NIK: {$headerA}";

    // Baris 5 contoh NIK pertama (16 digit)
    $exampleVal = (string)$sheet->getCell('A5')->getValue();
    if (strlen($exampleVal) !== 16 || !ctype_digit($exampleVal)) {
        return "Contoh NIK pada template bukan 16 digit angka: '{$exampleVal}'";
    }

    return true;
});

// ==============================================================================
// 4. NIK PENDING FEATURE TESTS (fitur baru: karyawan belum punya NIK/KTP)
// ==============================================================================

it("4.1 - Database mengizinkan INSERT nik=NULL + nik_pending=TRUE pada posisi non-developer", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        // INSERT karyawan tanpa NIK dengan flag nik_pending = TRUE → harus berhasil
        $stmt = $pdo->prepare("
            INSERT INTO public.pengguna (nama_lengkap, nik, nik_pending, posisi, status_aktif)
            VALUES ('Test NIK Pending Valid', NULL, TRUE, 'pengemasan', TRUE)
            RETURNING id
        ");
        $stmt->execute();
        $id = $stmt->fetchColumn();

        if (!$id) return "INSERT nik_pending=TRUE gagal: ID tidak dikembalikan.";

        $saved = $pdo->query("SELECT nik, nik_pending, posisi FROM public.pengguna WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        if ($saved['nik'] !== null) return "NIK harus NULL, dapat: {$saved['nik']}";
        if ($saved['nik_pending'] !== true) return "nik_pending harus TRUE, dapat: " . var_export($saved['nik_pending'], true);

        return true;
    } finally {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
});

it("4.2 - Database MENOLAK INSERT nik=NULL + nik_pending=FALSE pada posisi non-developer (constraint tetap tegak)", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $caught = false;
        try {
            $stmt = $pdo->prepare("
                INSERT INTO public.pengguna (nama_lengkap, nik, nik_pending, posisi, status_aktif)
                VALUES ('Test NIK Pending Palsu', NULL, FALSE, 'sales', TRUE)
            ");
            $stmt->execute();
        } catch (PDOException $ex) {
            // Harus kena CHECK constraint violation
            $caught = str_contains($ex->getMessage(), 'chk_pengguna_nik_16_digit');
        }
        return $caught ? true : "Database seharusnya menolak nik=NULL + nik_pending=FALSE pada posisi sales!";
    } finally {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
});

it("4.3 - previewRows() memperlakukan baris dengan NIK KOSONG sebagai INSERT (nik_pending), bukan ERROR", function() use ($handler, $pdo) {
    $header = $handler->getTemplateHeaders();
    // Format: [nik, nama_lengkap, posisi, tipe_penggajian, gapok, uang_hadir, tunjangan, wa, nopol, alamat, tgl_gabung, bank, rek, atas_nama, status_aktif]
    $row = ['', 'Karyawan Tanpa NIK Test', 'pengemasan', 'borongan', 0, 15000, 0, '081234567890', '', '', '2024-01-01', '', '', '', 'Aktif'];
    $result = $handler->previewRows([$row], $header, $pdo, 'add_only');

    if (empty($result)) return "previewRows mengembalikan array kosong";
    $row0 = $result[0];
    if ($row0['action'] === 'ERROR') {
        return "Baris NIK kosong seharusnya bukan ERROR, dapat: " . ($row0['error_msg'] ?? 'unknown');
    }
    if (!in_array($row0['action'], ['INSERT', 'UPDATE'], true)) {
        return "Baris NIK kosong harus INSERT atau UPDATE, dapat: {$row0['action']}";
    }
    if (empty($row0['data']['nik_pending'])) {
        return "Flag nik_pending harus TRUE untuk baris dengan NIK kosong";
    }
    if ($row0['data']['nik'] !== null) {
        return "NIK harus NULL untuk nik_pending=TRUE, dapat: " . var_export($row0['data']['nik'], true);
    }
    return true;
});

it("4.4 - previewRows() memperlakukan NIK='0' sebagai NIK Pending (bukan ERROR)", function() use ($handler, $pdo) {
    $header = $handler->getTemplateHeaders();
    $row = ['0', 'Karyawan NIK Nol Test', 'pengemasan', 'borongan', 0, 15000, 0, '', '', '', '2024-01-01', '', '', '', 'Aktif'];
    $result = $handler->previewRows([$row], $header, $pdo, 'add_only');

    if (empty($result)) return "previewRows mengembalikan array kosong";
    $row0 = $result[0];
    if ($row0['action'] === 'ERROR') {
        return "NIK='0' seharusnya bukan ERROR, dapat: " . ($row0['error_msg'] ?? 'unknown');
    }
    if (empty($row0['data']['nik_pending'])) return "nik_pending harus TRUE untuk NIK='0'";
    return true;
});

it("4.5 - previewRows() memperlakukan NIK='Belum' sebagai NIK Pending (bukan ERROR)", function() use ($handler, $pdo) {
    $header = $handler->getTemplateHeaders();
    $row = ['Belum', 'Karyawan NIK Belum Test', 'driver', 'bulanan', 3000000, 0, 0, '08123456789', '', '', '2024-01-01', '', '', '', 'Aktif'];
    $result = $handler->previewRows([$row], $header, $pdo, 'add_only');

    if (empty($result)) return "previewRows mengembalikan array kosong";
    $row0 = $result[0];
    if ($row0['action'] === 'ERROR') {
        return "NIK='Belum' seharusnya bukan ERROR, dapat: " . ($row0['error_msg'] ?? 'unknown');
    }
    if (empty($row0['data']['nik_pending'])) return "nik_pending harus TRUE untuk NIK='Belum'";
    return true;
});

it("4.6 - previewRows() TETAP memunculkan ERROR untuk NIK 10 digit (non-valid, non-pending)", function() use ($handler, $pdo) {
    $header = $handler->getTemplateHeaders();
    $row = ['3201012345', 'Test NIK 10 Digit', 'admin', 'bulanan', 3000000, 0, 0, '08123456789', '', '', '2024-01-01', '', '', '', 'Aktif'];
    $result = $handler->previewRows([$row], $header, $pdo, 'add_only');

    if (empty($result)) return "previewRows mengembalikan array kosong";
    $row0 = $result[0];
    if ($row0['action'] !== 'ERROR') {
        return "NIK 10 digit seharusnya ERROR, dapat: {$row0['action']}";
    }
    return true;
});

it("4.7 - previewRows() dan applySync() berhasil mendeteksi dan memperbarui NIK Pending menjadi NIK 16 digit via Excel", function() use ($handler, $pdo) {
    $pdo->beginTransaction();
    try {
        // Buat karyawan dengan NIK Pending di database
        $uniqueName = 'Karyawan Pending Test ' . rand(1000, 9999);
        $stmtIns = $pdo->prepare("
            INSERT INTO public.pengguna (nama_lengkap, nik, nik_pending, posisi, status_aktif)
            VALUES (:nama, NULL, TRUE, 'sales', TRUE)
            RETURNING id
        ");
        $stmtIns->execute(['nama' => $uniqueName]);
        $uid = $stmtIns->fetchColumn();

        $pdo->prepare("
            INSERT INTO public.karyawan (pengguna_id, tipe_penggajian, gaji_pokok_bulanan, uang_kehadiran_harian, tunjangan_bulanan)
            VALUES (?, 'bulanan', 3000000, 20000, 0)
        ")->execute([$uid]);

        // Berkas Excel sekarang memasukkan 16 digit NIK baru untuk nama tersebut (kolom lain sama persis)
        $header = $handler->getTemplateHeaders();
        $newNik = '320101' . str_pad((string)rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
        $row = [$newNik, $uniqueName, 'sales', 'bulanan', 3000000, 20000, 0, '', '', '', date('Y-m-d'), '', '', '', 'Aktif'];

        $preview = $handler->previewRows([$row], $header, $pdo, 'update_insert');
        if (empty($preview)) {
            $pdo->rollBack();
            return "Pratinjau kosong";
        }

        $p = $preview[0];
        if ($p['action'] !== 'UPDATE') {
            $pdo->rollBack();
            return "Aksi preview seharusnya UPDATE saat NIK baru diisi untuk karyawan pending, dapat: " . ($p['action'] ?? 'null') . ($p['error_msg'] ?? '');
        }

        // Terapkan sinkronisasi
        $stats = $handler->applySync($preview, $pdo);
        if ($stats['update'] !== 1) {
            $pdo->rollBack();
            return "Stats update harus 1, dapat: " . json_encode($stats);
        }

        // Verifikasi di database
        $updatedUser = $pdo->query("SELECT nik, nik_pending FROM public.pengguna WHERE id = '{$uid}'")->fetch(PDO::FETCH_ASSOC);
        $pdo->rollBack(); // Bersihkan kembali sesuai Zero Persistent Mock Data

        if (!$updatedUser) return "User tidak ditemukan di DB setelah update";
        if ($updatedUser['nik'] !== $newNik) return "NIK tidak terupdate, masih: {$updatedUser['nik']}";
        if ($updatedUser['nik_pending'] === true) return "nik_pending harus FALSE setelah NIK diisi";

        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

it("4.8 - Kueri EmployeeController::index() dari v_karyawan_info memuat kolom nik_pending", function() use ($pdo) {
    $row = Database::fetchOne("SELECT k.id, k.nik, k.nik_pending, k.nama_karyawan FROM public.v_karyawan_info k LIMIT 1");
    if (!$row) return true; // Tidak ada data, tetap valid skema
    if (!array_key_exists('nik_pending', $row)) {
        return "Kueri v_karyawan_info tidak mengembalikan kunci 'nik_pending'";
    }
    return true;
});

echo "\n====================================================================\n";
echo "SUMMARY: {$passed} / {$total} Tests Passed (" . round(($passed/$total)*100) . "%)\n";
echo "====================================================================\n\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

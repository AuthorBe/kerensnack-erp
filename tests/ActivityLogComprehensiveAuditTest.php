<?php
declare(strict_types=1);

/**
 * tests/ActivityLogComprehensiveAuditTest.php
 * Keren Snack ERP - Automated Extreme Audit Test Suite
 * Module: Settings / Activity Logs Modernization & Enterprise Audit Trail
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/env.php';
require_once ROOT_PATH . '/config/database.php';

// Autoloader untuk class App\*
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = ROOT_PATH . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Helpers\ActivityLog;
use App\Core\Auth;

$passCount = 0;
$failCount = 0;

function assertTest(string $title, bool $condition, string $details = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "✅ [PASS] {$title}" . ($details ? " ({$details})" : "") . PHP_EOL;
    } else {
        $failCount++;
        echo "❌ [FAIL] {$title}" . ($details ? " - {$details}" : "") . PHP_EOL;
    }
}

echo "====================================================================\n";
echo " KEREN SNACK ERP - ACTIVITY LOG AUDIT & INTEGRITY TEST SUITE\n";
echo "====================================================================\n\n";

// ============================================================================
// 1. UNIT TESTS: Smart Delta Diffing & Anti-Bloat Mechanism
// ============================================================================
echo "--- 1. SMART DELTA DIFFING & STORAGE OPTIMIZATION ---\n";

$beforeState = [
    'nama' => 'Budi Santoso',
    'email' => 'budi@kerensnack.com',
    'telepon' => '08123456789',
    'alamat' => 'Jl. Merdeka No. 10 Jakarta',
    'gaji_pokok' => 5000000,
    'status_aktif' => true,
    'jabatan' => 'Staff Gudang'
];

// Kasus 1: Tidak ada perubahan sama sekali
[$noDeltaB, $noDeltaA] = ActivityLog::computeDelta($beforeState, $beforeState);
assertTest("computeDelta: Menghasilkan [null, null] jika tidak ada field yang berubah", $noDeltaB === null && $noDeltaA === null);

// Kasus 2: Hanya 1 field berubah dari 7 field
$afterState1 = $beforeState;
$afterState1['gaji_pokok'] = 5500000;
[$deltaBefore1, $deltaAfter1] = ActivityLog::computeDelta($beforeState, $afterState1);

assertTest("computeDelta: Menangkap perubahan field tunggal di state sebelum & sesudah", isset($deltaBefore1['gaji_pokok']) && isset($deltaAfter1['gaji_pokok']));
assertTest("computeDelta: Nilai lama tercatat akurat", ($deltaBefore1['gaji_pokok'] ?? null) === 5000000);
assertTest("computeDelta: Nilai baru tercatat akurat", ($deltaAfter1['gaji_pokok'] ?? null) === 5500000);
assertTest("computeDelta: Field yang tidak berubah dibuang (Anti-Bloat)", count($deltaBefore1) === 1 && count($deltaAfter1) === 1);

// Kasus 3: Efisiensi kompresi payload pada objek besar (Simulasi 50 fields)
$largeBefore = [];
for ($i = 1; $i <= 50; $i++) {
    $largeBefore["field_{$i}"] = "Value persistent {$i} - " . str_repeat('X', 50);
}
$largeAfter = $largeBefore;
$largeAfter['field_25'] = 'Updated Specific Value';

[$largeDeltaB, $largeDeltaA] = ActivityLog::computeDelta($largeBefore, $largeAfter);
$fullSize = strlen((string)json_encode(['lama' => $largeBefore, 'baru' => $largeAfter]));
$deltaSize = strlen((string)json_encode(['sebelum' => $largeDeltaB, 'sesudah' => $largeDeltaA]));
$savings = round((1 - ($deltaSize / $fullSize)) * 100, 1);

assertTest(
    "computeDelta: Mengurangi ukuran payload secara drastis",
    $savings >= 90.0,
    "Hemat {$savings}% penyimpanan (Full: {$fullSize} bytes vs Delta: {$deltaSize} bytes)"
);

// Kasus 4: Record baru (before null)
[$newB, $newA] = ActivityLog::computeDelta(null, ['nama' => 'Produk Baru', 'harga' => 15000]);
assertTest("computeDelta: Mendukung snapshot record baru tanpa error", $newB === null && ($newA['nama'] ?? '') === 'Produk Baru');


// ============================================================================
// 2. UNIT TESTS: Sensitive Payload Sanitization & Token Stripping
// ============================================================================
echo "\n--- 2. SENSITIVE DATA SANITIZATION & LEAK PREVENTION ---\n";

$dirtyPayload = [
    'username' => 'superadmin',
    'password' => 'RahasiaSuper123!',
    'password_hash' => '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890',
    'token' => 'jwt.token.secret.value',
    'pin' => '123456',
    'avatar_base64' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
    'catatan_sangat_panjang' => str_repeat('LOREM_IPSUM_', 50),
    'nama_asli' => 'Ahmad Kasir'
];

$cleanPayload = ActivityLog::sanitizePayload($dirtyPayload);

assertTest("sanitizePayload: Menghapus / menyensor field 'password'", ($cleanPayload['password'] ?? null) === '[TERPROTEKSI]');
assertTest("sanitizePayload: Menghapus / menyensor field 'password_hash'", ($cleanPayload['password_hash'] ?? null) === '[TERPROTEKSI]');
assertTest("sanitizePayload: Menghapus / menyensor field 'token'", ($cleanPayload['token'] ?? null) === '[TERPROTEKSI]');
assertTest("sanitizePayload: Menghapus / menyensor field 'pin'", ($cleanPayload['pin'] ?? null) === '[TERPROTEKSI]');
assertTest("sanitizePayload: Mencegah bloat data gambar base64", str_contains((string)($cleanPayload['avatar_base64'] ?? ''), '[GAMBAR_BASE64:'));
assertTest(
    "sanitizePayload: Memotong string raksasa (> 300 karakter)",
    str_contains((string)($cleanPayload['catatan_sangat_panjang'] ?? ''), '... [Dipotong:')
);
assertTest("sanitizePayload: Mempertahankan field operasional non-sensitif", ($cleanPayload['nama_asli'] ?? null) === 'Ahmad Kasir');


// ============================================================================
// 3. UNIT TESTS: UUID Validation & User-Agent Parser
// ============================================================================
echo "\n--- 3. UUID VALIDATION & CLIENT USER-AGENT PARSING ---\n";

assertTest("isValidUuid: Mengenali UUID v4 valid", ActivityLog::isValidUuid('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'));
assertTest("isValidUuid: Menolak string teks biasa ('perusahaan')", !ActivityLog::isValidUuid('perusahaan'));
assertTest("isValidUuid: Menolak integer string ('12345')", !ActivityLog::isValidUuid('12345'));
assertTest("isValidUuid: Menolak null", !ActivityLog::isValidUuid(null));
assertTest("isValidUuid: Menolak string kosong", !ActivityLog::isValidUuid(''));

$uaChromeWin = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";
$uaSafariIPhone = "Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1";
$uaFirefoxLinux = "Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/119.0";

$pChromeWin = ActivityLog::parseUserAgent($uaChromeWin);
$pSafariIPhone = ActivityLog::parseUserAgent($uaSafariIPhone);
$pFirefoxLinux = ActivityLog::parseUserAgent($uaFirefoxLinux);
$pEmpty = ActivityLog::parseUserAgent(null);

assertTest("parseUserAgent: Windows Chrome", $pChromeWin['browser'] === 'Chrome' && str_contains($pChromeWin['os'], 'Windows'));
assertTest("parseUserAgent: iPhone Safari", $pSafariIPhone['browser'] === 'Safari' && $pSafariIPhone['os'] === 'iPhone' && $pSafariIPhone['is_mobile'] === true);
assertTest("parseUserAgent: Linux Firefox", $pFirefoxLinux['browser'] === 'Firefox' && $pFirefoxLinux['os'] === 'Linux');
assertTest("parseUserAgent: Fallback untuk User-Agent kosong", $pEmpty['label'] === 'Sistem Otomatis');


// ============================================================================
// 4. DATABASE INTEGRATION TESTS: Non-UUID Safety & Category Fallbacks
// ============================================================================
echo "\n--- 4. DATABASE INTEGRATION & POSTGRESQL CONSTRAINTS ---\n";

try {
    $db = Database::getConnection();

    // Test 4.1: Penanganan String Non-UUID ('perusahaan') yang sebelumnya menyebabkan SQLSTATE[22P02]
    $testDesc = "Test audit non-uuid safety " . uniqid();
    $logged = ActivityLog::log(
        'master_data',
        'UPDATE',
        $testDesc,
        'pengaturan_sistem',
        'perusahaan' // String non-UUID
    );

    assertTest("ActivityLog::log: Tidak melempar SQLSTATE[22P02] pada id_referensi non-UUID", $logged === true);

    // Verifikasi di DB bahwa id_referensi bernilai NULL dan deskripsi mencatat reference string
    $stmt = $db->prepare("SELECT id_referensi, deskripsi_aktivitas, kategori_aktivitas FROM public.log_aktivitas WHERE deskripsi_aktivitas LIKE :desc LIMIT 1");
    $stmt->execute(['desc' => "%{$testDesc}%"]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    assertTest("ActivityLog::log: PostgreSQL menyimpan id_referensi sebagai NULL", $row !== false && $row['id_referensi'] === null);
    assertTest("ActivityLog::log: Reference string diamankan ke dalam deskripsi", str_contains($row['deskripsi_aktivitas'] ?? '', '[Ref: perusahaan]'));

    // Test 4.2: Pemetaan Kategori 'keamanan' -> 'keamanan_auth'
    $testSecDesc = "Test security auth category mapping " . uniqid();
    $loggedSec = ActivityLog::log(
        'keamanan', // Shorthand yang sebelumnya melanggar constraint chk_log_kategori
        'LOGIN',
        $testSecDesc,
        'pengguna',
        null
    );
    assertTest("ActivityLog::log: Kategori 'keamanan' berhasil dipetakan tanpa constraint violation", $loggedSec === true);

    $stmtSec = $db->prepare("SELECT kategori_aktivitas FROM public.log_aktivitas WHERE deskripsi_aktivitas = :desc LIMIT 1");
    $stmtSec->execute(['desc' => $testSecDesc]);
    $secRow = $stmtSec->fetch(PDO::FETCH_ASSOC);
    assertTest("ActivityLog::log: Tersimpan di DB sebagai 'keamanan_auth'", ($secRow['kategori_aktivitas'] ?? '') === 'keamanan_auth');

    // Test 4.3: Pemetaan Kategori Tak Dikenal -> Fallback 'master_data'
    $testFallbackDesc = "Test invalid category fallback " . uniqid();
    $loggedFallback = ActivityLog::log(
        'kategori_sembarangan_123',
        'CUSTOM',
        $testFallbackDesc,
        'unknown',
        null
    );
    assertTest("ActivityLog::log: Kategori invalid tidak merusak query", $loggedFallback === true);

    $stmtFallback = $db->prepare("SELECT kategori_aktivitas FROM public.log_aktivitas WHERE deskripsi_aktivitas = :desc LIMIT 1");
    $stmtFallback->execute(['desc' => $testFallbackDesc]);
    $fallbackRow = $stmtFallback->fetch(PDO::FETCH_ASSOC);
    assertTest("ActivityLog::log: Kategori invalid otomatis fallback ke 'master_data'", ($fallbackRow['kategori_aktivitas'] ?? '') === 'master_data');

    // Test 4.4: log dengan dataSebelum & dataSesudah terintegrasi dengan delta diffing & sanitasi data
    $testDeltaDesc = "Test log with delta & sanitization " . uniqid();
    $deltaLogged = ActivityLog::log(
        'keamanan_auth',
        'UPDATE',
        $testDeltaDesc,
        'pengguna',
        null,
        ['username' => 'testuser', 'password' => 'secret123', 'status' => 'aktif'],
        ['username' => 'testuser', 'password' => 'newsecret456', 'status' => 'nonaktif']
    );
    assertTest("ActivityLog::log: Berhasil menyimpan log dengan delta diff", $deltaLogged === true);

    $stmtDelta = $db->prepare("SELECT data_sebelum, data_sesudah FROM public.log_aktivitas WHERE deskripsi_aktivitas = :desc LIMIT 1");
    $stmtDelta->execute(['desc' => $testDeltaDesc]);
    $deltaRow = $stmtDelta->fetch(PDO::FETCH_ASSOC);

    $dataSebelum = json_decode($deltaRow['data_sebelum'] ?? '{}', true);
    $dataSesudah = json_decode($deltaRow['data_sesudah'] ?? '{}', true);

    assertTest("log di DB: Field yang tidak berubah ('username') diabaikan", !isset($dataSebelum['username']) && !isset($dataSesudah['username']));
    assertTest("log di DB: Password lama tersensor [TERPROTEKSI]", ($dataSebelum['password'] ?? '') === '[TERPROTEKSI]');
    assertTest("log di DB: Password baru tersensor [TERPROTEKSI]", ($dataSesudah['password'] ?? '') === '[TERPROTEKSI]');
    assertTest("log di DB: Perubahan status tercatat akurat", ($dataSebelum['status'] ?? '') === 'aktif' && ($dataSesudah['status'] ?? '') === 'nonaktif');

} catch (\Throwable $e) {
    assertTest("Database integration tests", false, $e->getMessage());
}


// ============================================================================
// 5. SECURITY TESTS: Developer Pruning Protection
// ============================================================================
echo "\n--- 5. DEVELOPER PRUNING AUTHENTICATION & RESTRICTION ---\n";

// 5.1 Non-developer user diblokir
$_SESSION['user'] = [
    'id' => '11111111-1111-1111-1111-111111111111',
    'peran' => 'staff_gudang',
    'nama_lengkap' => 'Staff Biasa'
];
$staffPrune = ActivityLog::pruneLogs(30, 'PasswordSembarang');
assertTest(
    "pruneLogs: Menolak eksekusi jika bukan akun peran developer",
    ($staffPrune['success'] ?? false) === false && str_contains($staffPrune['message'] ?? '', 'Developer')
);

// 5.2 Cari akun developer aktif di DB
$devUser = Database::fetchOne("
    SELECT p.id, p.nama_lengkap, p.nama_pengguna, p.kata_sandi, pr.nama_peran as peran 
    FROM public.pengguna p 
    JOIN public.peran pr ON p.peran_id = pr.id 
    WHERE pr.nama_peran = 'developer' AND p.status_aktif = true 
    LIMIT 1
");
if ($devUser) {
    assertTest("Developer verification: Akun developer terdeteksi di DB", true, "Username: {$devUser['nama_pengguna']}");

    // Simulasikan login sebagai developer
    $_SESSION['user'] = [
        'id' => $devUser['id'],
        'peran' => 'developer',
        'nama_lengkap' => $devUser['nama_lengkap']
    ];

    // Coba prune dengan password salah
    $fakePrune = ActivityLog::pruneLogs(90, 'PasswordSalahRandom999!');
    assertTest(
        "pruneLogs: Menolak eksekusi jika kata sandi developer salah",
        ($fakePrune['success'] ?? false) === false && str_contains($fakePrune['message'] ?? '', 'salah')
    );

    // Coba prune dengan retensi di bawah batas aman (< 7 hari)
    $unsafePrune = ActivityLog::pruneLogs(3, 'any');
    assertTest(
        "pruneLogs: Menolak retensi di bawah 7 hari (Anti-Accidental-Wipe)",
        ($unsafePrune['success'] ?? false) === false && str_contains($unsafePrune['message'] ?? '', '7 hari')
    );
} else {
    echo "ℹ️ [INFO] Tidak ada akun peran 'developer' aktif di database lokal.\n";
}


// ============================================================================
// 6. ROUTING & CONTROLLER ARCHITECTURE AUDIT
// ============================================================================
echo "\n--- 6. ROUTING & CONTROLLER STATIC AUDIT ---\n";

$cIndex = file_get_contents(ROOT_PATH . '/public/index.php');
$cActivityLog = file_get_contents(ROOT_PATH . '/app/Controllers/ActivityLogController.php');
$vLogsIndex = file_get_contents(ROOT_PATH . '/views/settings/logs/index.php');

assertTest("Router: Mendaftarkan route GET /settings/activity-logs", str_contains($cIndex, "Router::get('/settings/activity-logs', [ActivityLogController::class, 'index'])"));
assertTest("Router: Mendaftarkan route GET /settings/activity-logs/export", str_contains($cIndex, "Router::get('/settings/activity-logs/export', [ActivityLogController::class, 'exportExcel'])"));
assertTest("Router: Mendaftarkan route POST /settings/activity-logs/prune", str_contains($cIndex, "Router::post('/settings/activity-logs/prune', [ActivityLogController::class, 'prune'])"));

assertTest("ActivityLogController: Mengimplementasikan telemetri database size", str_contains($cActivityLog, "pg_total_relation_size('public.log_aktivitas')"));
assertTest("ActivityLogController: Memiliki proteksi otorisasi Developer pada prune()", str_contains($cActivityLog, "Auth::isDeveloper()"));
assertTest("ActivityLogController: Memiliki validasi CSRF pada prune()", str_contains($cActivityLog, "CSRF::validate"));
assertTest("ActivityLogController: Menggunakan ExcelExport helper pada exportExcel()", str_contains($cActivityLog, "ExcelExport::download"));

assertTest("UI View: Memiliki 4 KPI summary cards", str_contains($vLogsIndex, "Aktivitas Hari Ini") && str_contains($vLogsIndex, "Keamanan &amp; Auth") && str_contains($vLogsIndex, "Mutasi Bisnis Hari Ini") && str_contains($vLogsIndex, "Kondisi Storage DB"));
assertTest("UI View: Memiliki Side-by-side Visual Diff Inspector modal", str_contains($vLogsIndex, "showDiffModal") && str_contains($vLogsIndex, "diffRows") && str_contains($vLogsIndex, "buildDiffRows"));
assertTest("UI View: Memiliki Developer-only Retention & Prune modal", str_contains($vLogsIndex, "showPruneModal") && str_contains($vLogsIndex, "developer_password"));
assertTest("UI View: Memiliki quick date filter preset buttons", str_contains($vLogsIndex, "'preset' => 'today'") && str_contains($vLogsIndex, "'preset' => '7days'") && str_contains($vLogsIndex, "'preset' => 'this_month'"));
assertTest("UI View: Menampilkan browser & OS client badge", str_contains($vLogsIndex, "parseUserAgent"));


// ============================================================================
// 7. CROSS-MODULE AUDIT TRAIL COVERAGE
// ============================================================================
echo "\n--- 7. CROSS-MODULE AUDIT TRAIL REFACTORING & COVERAGE ---\n";

$cSettings = file_get_contents(ROOT_PATH . '/app/Controllers/SettingsController.php');
$cPricing = file_get_contents(ROOT_PATH . '/app/Controllers/PricingController.php');
$cEmployee = file_get_contents(ROOT_PATH . '/app/Controllers/EmployeeController.php');
$cUser = file_get_contents(ROOT_PATH . '/app/Controllers/UserController.php');
$cProfile = file_get_contents(ROOT_PATH . '/app/Controllers/ProfileController.php');
$cPos = file_get_contents(ROOT_PATH . '/app/Controllers/PosController.php');
$cPurchase = file_get_contents(ROOT_PATH . '/app/Controllers/PurchaseController.php');

assertTest("SettingsController: updateCompany menggunakan id_referensi null & master_data", str_contains($cSettings, "ActivityLog::log(") && str_contains($cSettings, "pengaturan_sistem"));
assertTest("PricingController: Mencatat penambahan/perubahan level harga dengan diffing", str_contains($cPricing, "ActivityLog::log(") && str_contains($cPricing, "grup_produk_harga_level"));
assertTest("PricingController: Mencatat penghapusan level harga", str_contains($cPricing, "ActivityLog::log(") && str_contains($cPricing, "Menghapus harga jual Level"));
assertTest("EmployeeController: Mencatat penambahan karyawan baru", str_contains($cEmployee, "ActivityLog::log(") && str_contains($cEmployee, "karyawan"));
assertTest("EmployeeController: Mencatat pembaruan profil & gaji karyawan dengan delta diff", str_contains($cEmployee, "ActivityLog::log(") && str_contains($cEmployee, "Memperbarui data karyawan"));
assertTest("EmployeeController: Mencatat pembaruan skema tier komisi", str_contains($cEmployee, "ActivityLog::log(") && str_contains($cEmployee, "skema_komisi_sales"));
assertTest("UserController: update mencatat delta perubahan hak akses / pengguna", str_contains($cUser, "ActivityLog::record(") && str_contains($cUser, "UPDATE_USER"));
assertTest("UserController: toggleStatus mencatat pengaktifan/penonaktifan user", str_contains($cUser, "ActivityLog::record(") && str_contains($cUser, "TOGGLE_USER_STATUS"));
assertTest("UserController: revokeAccess mencatat pencabutan token sesi", str_contains($cUser, "ActivityLog::record(") && str_contains($cUser, "REVOKE_USER_ACCESS"));
assertTest("UserController: delete mencatat penghapusan user", str_contains($cUser, "ActivityLog::record(") && str_contains($cUser, "DELETE_USER"));
assertTest("ProfileController: ubah_nama_pengguna menggunakan ActivityLog helper standar", str_contains($cProfile, "ActivityLog::log(") && str_contains($cProfile, "ubah_nama_pengguna"));
assertTest("ProfileController: ubah_kata_sandi menggunakan ActivityLog helper standar", str_contains($cProfile, "ActivityLog::log(") && str_contains($cProfile, "ubah_kata_sandi"));
assertTest("PosController: checkout menggunakan ActivityLog helper standar", str_contains($cPos, "ActivityLog::log(") && str_contains($cPos, "Transaksi POS Kasir"));
assertTest("PurchaseController: pembuatan PO & Faktur menggunakan ActivityLog helper standar", str_contains($cPurchase, "ActivityLog::log(") && str_contains($cPurchase, "pembelian"));
assertTest("PurchaseController: pelunasan faktur menggunakan ActivityLog helper standar", str_contains($cPurchase, "ActivityLog::log(") && str_contains($cPurchase, "Pelunasan Faktur Vendor"));
assertTest("PurchaseController: pembatalan faktur menggunakan ActivityLog helper standar", str_contains($cPurchase, "ActivityLog::log(") && str_contains($cPurchase, "Pembatalan Faktur Vendor"));


// ============================================================================
// SUMMARY
// ============================================================================
echo "\n====================================================================\n";
echo " TEST SUMMARY: Total: " . ($passCount + $failCount) . " | Passed: {$passCount} | Failed: {$failCount}\n";
echo "====================================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);

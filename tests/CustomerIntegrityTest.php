<?php
declare(strict_types=1);

/**
 * tests/audit_customer_integrity_extreme.php
 * Automated Extreme Test Suite for Customer Master Data Integrity:
 * 1. CSRF Protection on all forms (4 modal forms + 3 delete hidden forms)
 * 2. UI/UX completeness (WhatsApp link, switchTab, searchQuery sync, global metrics cards)
 * 3. Anti-collision sequential code generation (CUST-xxx sequence & gap resilience)
 * 4. Default POS walk-in customer CUST-001 protection (deletion & deactivation guard)
 * 5. Consignment store active shelf stock conversion guard (stok_titip_saat_ini > 0)
 * 6. Customer Group duplicate code check & last group deletion guard
 * 7. Territory / Route duplicate code guard
 * 8. Payment type constraint whitelist adherence
 * 9. ActivityLog audit trail verification in public.log_aktivitas
 * 10. Live Database integrity & Foreign Key consistency check
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_ROOT', ROOT_PATH);
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session user Developer (bypass RBAC guards untuk CLI unit testing)
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

// Autoloader untuk class App\*
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/app/';

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

use App\Controllers\CustomerController;
use App\Helpers\ActivityLog;

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

$db = Database::getConnection();

// Helper factory to create mockable CustomerController
function createMockCustomerController(array $inputs = []) {
    return new class($inputs) extends CustomerController {
        public ?string $capturedError = null;
        public ?string $capturedSuccess = null;
        public ?string $capturedRedirect = null;
        public array $mockInput = [];

        public function __construct(array $inputs = []) {
            $this->mockInput = $inputs;
        }

        public function input(string $key, mixed $default = null): mixed {
            return array_key_exists($key, $this->mockInput) ? $this->mockInput[$key] : $default;
        }

        protected function flashError(string $message, ?string $title = null): void {
            $this->capturedError = $message;
        }

        protected function flashSuccess(string $message, ?string $title = null): void {
            $this->capturedSuccess = $message;
        }

        protected function redirect(string $url): void {
            $this->capturedRedirect = $url;
        }
    };
}

// ============================================================
// TEST 1: CSRF Protection Verification in views/customers/index.php
// ============================================================
runTest('CSRF Field Presence Across All 4 Modals and 3 Delete Forms in View', function () {
    $viewContent = file_get_contents(APP_ROOT . '/views/customers/index.php');

    // 1. Modal 1: store/update customer
    if (!str_contains($viewContent, "action=\"isEdit ? '<?= Router::url('/customers/update') ?>' : '<?= Router::url('/customers/store') ?>'\"")
        || !preg_match('/action="isEdit \? \'<\?= Router::url\(\'\/customers\/update\'\) \?>\' : \'<\?= Router::url\(\'\/customers\/store\'\) \?>\'"[^>]*>\s*<\?=\s*\\\\App\\\\Helpers\\\\CSRF::field\(\)\s*\?>/s', $viewContent)) {
        return 'Modal 1 (Customer store/update form) is missing CSRF::field() directly below form tag';
    }

    // 2. Modal 2: save-items (whitelist)
    if (!preg_match('/action="<\?=\s*Router::url\(\'\/customers\/save-items\'\)\s*\?>"[^>]*>\s*<\?=\s*\\\\App\\\\Helpers\\\\CSRF::field\(\)\s*\?>/s', $viewContent)) {
        return 'Modal 2 (Save-items form) is missing CSRF::field() directly below form tag';
    }

    // 3. Modal 3: store/update group
    if (!preg_match('/action="isEditCustomerGroup \? \'<\?= Router::url\(\'\/customers\/update-group\'\) \?>\' : \'<\?= Router::url\(\'\/customers\/store-group\'\) \?>\'"[^>]*>\s*<\?=\s*\\\\App\\\\Helpers\\\\CSRF::field\(\)\s*\?>/s', $viewContent)) {
        return 'Modal 3 (Customer group form) is missing CSRF::field() directly below form tag';
    }

    // 4. Modal 4: store/update territory
    if (!preg_match('/action="isEditTerritory \? \'<\?= Router::url\(\'\/customers\/update-territory\'\) \?>\' : \'<\?= Router::url\(\'\/customers\/store-territory\'\) \?>\'"[^>]*>\s*<\?=\s*\\\\App\\\\Helpers\\\\CSRF::field\(\)\s*\?>/s', $viewContent)) {
        return 'Modal 4 (Territory form) is missing CSRF::field() directly below form tag';
    }

    // 5. Hidden delete customer form
    if (!preg_match('/<form[^>]*id="delete-customer-form"[^>]*>[\s\S]*?App\\\\Helpers\\\\CSRF::field\(\)/i', $viewContent)) {
        return 'delete-customer-form is missing CSRF::field()';
    }

    // 6. Hidden delete group form
    if (!preg_match('/<form[^>]*id="delete-group-form"[^>]*>[\s\S]*?App\\\\Helpers\\\\CSRF::field\(\)/i', $viewContent)) {
        return 'delete-group-form is missing CSRF::field()';
    }

    // 7. Hidden delete territory form
    if (!preg_match('/<form[^>]*id="delete-territory-form"[^>]*>[\s\S]*?App\\\\Helpers\\\\CSRF::field\(\)/i', $viewContent)) {
        return 'delete-territory-form is missing CSRF::field()';
    }

    return true;
});

// ============================================================
// TEST 2: UI/UX Completeness (WhatsApp Link, switchTab, searchQuery sync, global metrics)
// ============================================================
runTest('UI/UX Logic & Elements in views/customers/index.php', function () {
    $viewContent = file_get_contents(APP_ROOT . '/views/customers/index.php');

    // 1. switchTab method
    if (!str_contains($viewContent, 'switchTab(tab)')) {
        return 'switchTab(tab) method is missing in customerApp';
    }

    // 2. cleanWa helper method
    if (!str_contains($viewContent, 'cleanWa(num)')) {
        return 'cleanWa(num) method is missing in customerApp';
    }

    // 3. wa.me link
    if (!str_contains($viewContent, 'wa.me')) {
        return 'Direct WhatsApp wa.me link is missing in customer table';
    }

    // 4. searchQuery initialized from pagination
    if (!preg_match('/searchQuery:\s*<\?=\s*json_encode\(\$pagination\[\'q\'\]\s*\?\?\s*\'\'\)\s*\?>/', $viewContent)) {
        return 'searchQuery is not initialized with server-side query string';
    }

    // 5. Global metrics cards
    if (!str_contains($viewContent, '$totalGlobalCustomers') ||
        !str_contains($viewContent, '$totalActiveCustomers') ||
        !str_contains($viewContent, '$totalGlobalPiutang')) {
        return 'Global metrics variables are missing in view stat cards';
    }

    return true;
});

// ============================================================
// TEST 3: Anti-Collision Sequential Code Generator
// ============================================================
runTest('Anti-Collision Sequential Code Generation Logic', function () use ($db) {
    $controller = new CustomerController();

    // Call generator
    $code1 = $controller->generateCustomerCode();
    if (!preg_match('/^CUST-\d{3,}$/', $code1)) {
        return "Generated code '{$code1}' does not match pattern CUST-xxx";
    }

    // Verify it does not already exist in DB
    $stmt = $db->prepare("SELECT COUNT(*) FROM public.pelanggan WHERE kode_pelanggan = ?");
    $stmt->execute([$code1]);
    $count = (int)$stmt->fetchColumn();
    if ($count > 0) {
        return "Generated code '{$code1}' already exists in database!";
    }

    // Test sequential logic based on max numeric ID
    $stmtMax = $db->query("
        SELECT COALESCE(
            MAX(CAST(SUBSTRING(kode_pelanggan FROM 6) AS INTEGER)),
            0
        ) FROM public.pelanggan 
        WHERE kode_pelanggan ~ '^CUST-[0-9]+$'
    ");
    $currentMax = (int)$stmtMax->fetchColumn();
    $expectedNum = $currentMax + 1;
    $expectedCode = sprintf("CUST-%04d", $expectedNum);

    if ($code1 !== $expectedCode) {
        return "Expected code {$expectedCode}, but generated {$code1}";
    }

    return true;
});

// ============================================================
// TEST 4: Protection of Default Walk-in Customer CUST-001
// ============================================================
runTest('Default POS Customer CUST-001 Protected Against Deletion & Deactivation', function () use ($db) {
    // Find CUST-001
    $stmt = $db->prepare("SELECT id, kode_pelanggan, nama_toko, status_aktif FROM public.pelanggan WHERE kode_pelanggan = 'CUST-001'");
    $stmt->execute();
    $cust001 = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cust001) {
        return "Default customer CUST-001 not found in database!";
    }

    // 1. Attempt delete CUST-001 via controller
    $ctrlDelete = createMockCustomerController(['id' => $cust001['id']]);
    $ctrlDelete->delete();

    // Verify CUST-001 STILL EXISTS in DB
    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM public.pelanggan WHERE id = ?");
    $stmtCheck->execute([$cust001['id']]);
    if ((int)$stmtCheck->fetchColumn() !== 1) {
        return "CUST-001 was erroneously deleted from database!";
    }

    if (empty($ctrlDelete->capturedError) || !str_contains($ctrlDelete->capturedError, 'CUST-001')) {
        return "Expected error message mentioning CUST-001, got: " . var_export($ctrlDelete->capturedError, true);
    }

    // 2. Attempt deactivating CUST-001
    $grupId = $db->query("SELECT id FROM public.grup_pelanggan LIMIT 1")->fetchColumn();
    $wilId = $db->query("SELECT id FROM public.wilayah LIMIT 1")->fetchColumn();

    $ctrlUpdate = createMockCustomerController([
        'id' => $cust001['id'],
        'nama_toko' => $cust001['nama_toko'],
        'nama_pemilik' => 'Umum',
        'grup_pelanggan_id' => $grupId,
        'wilayah_id' => $wilId,
        'tipe_pembayaran_default' => 'cash',
        'plafon_piutang' => '0',
        'status_aktif' => false // Try deactivating
    ]);
    $ctrlUpdate->update();

    // Verify CUST-001 is still active
    $stmtActive = $db->prepare("SELECT status_aktif FROM public.pelanggan WHERE id = ?");
    $stmtActive->execute([$cust001['id']]);
    $isActive = (bool)$stmtActive->fetchColumn();
    if (!$isActive) {
        return "CUST-001 was allowed to be deactivated!";
    }

    if (empty($ctrlUpdate->capturedError) || !str_contains($ctrlUpdate->capturedError, 'CUST-001')) {
        return "Expected error message protecting CUST-001 status, got: " . var_export($ctrlUpdate->capturedError, true);
    }

    return true;
});

// ============================================================
// TEST 5: Consignment Active Shelf Stock Conversion Guard
// ============================================================
runTest('Consignment Store with Active Shelf Stock Cannot be Converted to Non-Consignment', function () use ($db) {
    // Check if there is an existing consignment customer with active stock
    $stmt = $db->query("
        SELECT p.id, p.nama_toko, p.grup_pelanggan_id, p.wilayah_id, COALESCE(SUM(sk.stok_titip_saat_ini), 0) as total_titip
        FROM public.pelanggan p
        JOIN public.stok_konsinyasi_toko sk ON sk.pelanggan_id = p.id
        WHERE p.is_konsinyasi = true
        GROUP BY p.id, p.nama_toko, p.grup_pelanggan_id, p.wilayah_id
        HAVING SUM(sk.stok_titip_saat_ini) > 0
        LIMIT 1
    ");
    $consCust = $stmt->fetch(PDO::FETCH_ASSOC);

    $tempInserted = false;
    $tempCustomerId = null;

    if (!$consCust) {
        $tempCustomerId = 'a0000000-0000-0000-0000-000000000099';
        $grupId = $db->query("SELECT id FROM public.grup_pelanggan LIMIT 1")->fetchColumn();
        $wilId = $db->query("SELECT id FROM public.wilayah LIMIT 1")->fetchColumn();
        $itemId = $db->query("SELECT id FROM public.item_master WHERE tipe = 'barang_jadi' LIMIT 1")->fetchColumn();

        $db->exec("
            INSERT INTO public.pelanggan (id, kode_pelanggan, nama_toko, grup_pelanggan_id, wilayah_id, is_konsinyasi, status_aktif)
            VALUES ('{$tempCustomerId}', 'CUST-TEST-CONS', 'Toko Titip Test', '{$grupId}', '{$wilId}', true, true)
            ON CONFLICT (id) DO UPDATE SET is_konsinyasi = true
        ");

        $db->exec("
            INSERT INTO public.stok_konsinyasi_toko (id, pelanggan_id, item_id, stok_titip_saat_ini)
            VALUES ('b0000000-0000-0000-0000-000000000099', '{$tempCustomerId}', '{$itemId}', 15)
            ON CONFLICT (pelanggan_id, item_id) DO UPDATE SET stok_titip_saat_ini = 15
        ");

        $consCust = [
            'id' => $tempCustomerId,
            'nama_toko' => 'Toko Titip Test',
            'grup_pelanggan_id' => $grupId,
            'wilayah_id' => $wilId,
            'total_titip' => 15
        ];
        $tempInserted = true;
    }

    // Now attempt to update this customer with is_konsinyasi turned off (cash, is_konsinyasi false)
    $ctrl = createMockCustomerController([
        'id' => $consCust['id'],
        'nama_toko' => $consCust['nama_toko'],
        'grup_pelanggan_id' => $consCust['grup_pelanggan_id'],
        'wilayah_id' => $consCust['wilayah_id'],
        'tipe_pembayaran_default' => 'cash',
        'plafon_piutang' => '1.000.000',
        'status_aktif' => true,
        'is_konsinyasi' => false
    ]);
    $ctrl->update();

    // Verify in DB that is_konsinyasi is STILL true!
    $stmtCheck = $db->prepare("SELECT is_konsinyasi FROM public.pelanggan WHERE id = ?");
    $stmtCheck->execute([$consCust['id']]);
    $stillConsignment = (bool)$stmtCheck->fetchColumn();

    // Clean up temporary data if inserted
    if ($tempInserted && $tempCustomerId) {
        $db->exec("DELETE FROM public.stok_konsinyasi_toko WHERE pelanggan_id = '{$tempCustomerId}'");
        $db->exec("DELETE FROM public.pelanggan WHERE id = '{$tempCustomerId}'");
    }

    if (!$stillConsignment) {
        return "Consignment flag was wrongly removed despite active shelf stock!";
    }

    if (empty($ctrl->capturedError) || !str_contains($ctrl->capturedError, 'stok konsinyasi')) {
        return "Expected error regarding active consignment stock, got: " . var_export($ctrl->capturedError, true);
    }

    return true;
});

// ============================================================
// TEST 6: Customer Group Duplicate Code & Last Group Guard
// ============================================================
runTest('Customer Group Duplicate Code Check & Last Group Protection', function () use ($db) {
    $existingGroup = $db->query("SELECT id, kode_grup, nama_grup FROM public.grup_pelanggan LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if (!$existingGroup) {
        return "No customer group found in DB!";
    }

    // 1. Attempt to store duplicate kode_grup
    $ctrlStore = createMockCustomerController([
        'nama_grup' => 'Grup Duplikat Test',
        'kode_grup' => $existingGroup['kode_grup'],
        'default_level_harga' => '1',
        'diskon_persen_default' => '0',
        'diskon_nominal_default' => '0',
        'status_aktif' => '1'
    ]);
    $ctrlStore->storeGroup();

    $err = $ctrlStore->capturedError ?? '';
    if (!str_contains($err, 'sudah terdaftar') && !str_contains($err, 'sudah digunakan')) {
        return "Expected duplicate error message for kode_grup, got: " . var_export($err, true);
    }

    // 2. Test last group protection logic
    $totalGroups = (int)$db->query("SELECT COUNT(*) FROM public.grup_pelanggan")->fetchColumn();
    if ($totalGroups === 1) {
        $ctrlDelete = createMockCustomerController(['id' => $existingGroup['id']]);
        $ctrlDelete->deleteGroup();

        if (empty($ctrlDelete->capturedError) || !str_contains($ctrlDelete->capturedError, 'satu-satunya')) {
            return "Expected last group protection warning, got: " . var_export($ctrlDelete->capturedError, true);
        }
    }

    return true;
});

// ============================================================
// TEST 7: Territory / Route Duplicate Code Guard
// ============================================================
runTest('Territory Duplicate Route Code Guard', function () use ($db) {
    $existingRoute = $db->query("SELECT id, kode_rute, nama_wilayah FROM public.wilayah LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if (!$existingRoute) {
        return "No route found in DB!";
    }

    $ctrl = createMockCustomerController([
        'kode_rute' => $existingRoute['kode_rute'],
        'nama_wilayah' => 'Jalur Duplikat Test',
        'kota_kabupaten' => 'Bandung',
        'provinsi' => 'Jawa Barat',
        'sub_wilayah' => 'Cimahi'
    ]);
    $ctrl->storeTerritory();

    $err = $ctrl->capturedError ?? '';
    if (!str_contains($err, 'sudah terdaftar') && !str_contains($err, 'sudah digunakan')) {
        return "Expected duplicate error message for kode_rute, got: " . var_export($err, true);
    }

    return true;
});

// ============================================================
// TEST 8: Payment Type Whitelist Constraint Adherence
// ============================================================
runTest('Payment Type Whitelist Validation', function () use ($db) {
    $expectedTypes = ['cash', 'qris', 'transfer', 'tempo_7_hari', 'tempo_14_hari', 'tempo_30_hari', 'konsinyasi'];

    $constant = CustomerController::ALLOWED_TIPE_BAYAR;

    if (!is_array($constant)) {
        return "CustomerController::ALLOWED_TIPE_BAYAR constant is missing or not an array";
    }

    foreach ($expectedTypes as $vt) {
        if (!in_array($vt, $constant, true)) {
            return "Valid type '{$vt}' missing from ALLOWED_TIPE_BAYAR";
        }
    }

    $stmt = $db->query("
        SELECT conname, pg_get_constraintdef(oid) as def
        FROM pg_constraint
        WHERE conrelid = 'public.pelanggan'::regclass
          AND conname = 'pelanggan_tipe_pembayaran_default_check'
    ");
    $chk = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($chk) {
        foreach ($constant as $cVal) {
            if (!str_contains($chk['def'], "'{$cVal}'")) {
                return "DB constraint does not contain allowed type '{$cVal}'";
            }
        }
    }

    return true;
});

// ============================================================
// TEST 9: ActivityLog Audit Logging for Customer Operations
// ============================================================
runTest('ActivityLog Audit Logging for Customer Operations', function () use ($db) {
    $testAction = 'audit_customer_test_' . time();
    ActivityLog::log('master_data', $testAction, 'Audit test customer integrity', 'pelanggan', null, null, ['test' => true]);

    $stmt = $db->prepare("
        SELECT deskripsi_aktivitas, data_sesudah 
        FROM public.log_aktivitas 
        WHERE kategori_aktivitas = 'master_data' AND jenis_aksi = ? 
        ORDER BY waktu_kejadian DESC LIMIT 1
    ");
    $stmt->execute([$testAction]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$log) {
        return "ActivityLog was not written to public.log_aktivitas";
    }

    return true;
});

// ============================================================
// TEST 10: Live Database Foreign Key Consistency & Integrity
// ============================================================
runTest('Live PostgreSQL Foreign Keys & Data Consistency', function () use ($db) {
    // 1. Check for orphaned customers without valid grup_pelanggan_id
    $orphanedGroup = $db->query("
        SELECT COUNT(*) FROM public.pelanggan p
        LEFT JOIN public.grup_pelanggan g ON p.grup_pelanggan_id = g.id
        WHERE p.grup_pelanggan_id IS NOT NULL AND g.id IS NULL
    ")->fetchColumn();

    if ((int)$orphanedGroup > 0) {
        return "Found {$orphanedGroup} customers with orphaned grup_pelanggan_id!";
    }

    // 2. Check for orphaned customers without valid wilayah_id
    $orphanedWilayah = $db->query("
        SELECT COUNT(*) FROM public.pelanggan p
        LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
        WHERE p.wilayah_id IS NOT NULL AND w.id IS NULL
    ")->fetchColumn();

    if ((int)$orphanedWilayah > 0) {
        return "Found {$orphanedWilayah} customers with orphaned wilayah_id!";
    }

    // 3. Check for orphaned sales_driver_id
    $orphanedSales = $db->query("
        SELECT COUNT(*) FROM public.pelanggan p
        LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
        WHERE p.sales_driver_id IS NOT NULL AND k.id IS NULL
    ")->fetchColumn();

    if ((int)$orphanedSales > 0) {
        return "Found {$orphanedSales} customers with orphaned sales_driver_id!";
    }

    // 4. Check for duplicate kode_pelanggan
    $dupKode = $db->query("
        SELECT kode_pelanggan, COUNT(*) FROM public.pelanggan
        GROUP BY kode_pelanggan HAVING COUNT(*) > 1
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($dupKode)) {
        return "Found duplicate kode_pelanggan: " . json_encode($dupKode);
    }

    // 5. Check for duplicate kode_rute
    $dupRute = $db->query("
        SELECT kode_rute, COUNT(*) FROM public.wilayah
        GROUP BY kode_rute HAVING COUNT(*) > 1
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($dupRute)) {
        return "Found duplicate kode_rute: " . json_encode($dupRute);
    }

    // 6. Check for duplicate kode_grup
    $dupGrup = $db->query("
        SELECT kode_grup, COUNT(*) FROM public.grup_pelanggan
        GROUP BY kode_grup HAVING COUNT(*) > 1
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($dupGrup)) {
        return "Found duplicate kode_grup: " . json_encode($dupGrup);
    }

    return true;
});

echo "\n============================================================\n";
echo "AUDIT RESULTS: {$passed} / {$totalTests} PASSED (" . ($totalTests > 0 ? round(($passed / $totalTests) * 100, 1) : 0) . "%)\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

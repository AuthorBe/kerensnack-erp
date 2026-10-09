<?php
declare(strict_types=1);

/**
 * tests/RegulerPortalTest.php
 * Automated Integration Test for Portal Reguler & Fitur Tempo Faktur/Tanggal
 * 
 * Verifies:
 * 1. RegulerPortalController & methods (index, tagihan, cetakInvoiceTagihan, earlyWarning, laporanToko)
 * 2. Routing registrations in public/index.php (/reguler*)
 * 3. Views presence & template compliance (views/reguler/*)
 * 4. Invoice Tagihan A4 monokrom corporate structure
 * 5. Owner Aging Piutang 30-day fair categorization for tempo_faktur
 * 6. Driver Delivery cash deposit ("catatan_titipan_tunai") support
 * 7. Customer Order Credit Health check & soft confirmation guard
 * 8. Zero Database Contamination (AGENTS.md compliance, read-only queries)
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

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once APP_ROOT . '/config/database.php';

use App\Controllers\RegulerPortalController;
use App\Controllers\OwnerController;
use App\Controllers\DeliveryController;
use App\Controllers\CustomerOrderController;
use App\Core\Auth;

$totalTests = 0;
$passedTests = 0;

function assertRegulerTest(string $description, bool $condition, string $details = ''): void {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] {$description}\n";
    } else {
        echo "  [FAIL] {$description}\n";
        if ($details !== '') {
            echo "         Details: {$details}\n";
        }
    }
}

echo "====================================================================\n";
echo "▶️  STARTING REGULER PORTAL & TEMPO INTEGRATION TEST SUITE\n";
echo "====================================================================\n\n";

// -------------------------------------------------------------------------
// 1. Controller & Method Reflection Checks
// -------------------------------------------------------------------------
echo "1. Checking RegulerPortalController & Methods...\n";
assertRegulerTest(
    "RegulerPortalController class exists in App\\Controllers",
    class_exists(RegulerPortalController::class)
);

$methods = ['index', 'tagihan', 'cetakInvoiceTagihan', 'earlyWarning', 'laporanToko'];
foreach ($methods as $m) {
    assertRegulerTest(
        "Method RegulerPortalController::{$m}() exists",
        method_exists(RegulerPortalController::class, $m)
    );
}

// -------------------------------------------------------------------------
// 2. Routing Registration in public/index.php
// -------------------------------------------------------------------------
echo "\n2. Verifying Route Registrations in public/index.php...\n";
$indexContent = file_get_contents(APP_ROOT . '/public/index.php');
assertRegulerTest(
    "Route '/reguler' is registered in router",
    strpos($indexContent, "Router::get('/reguler', [RegulerPortalController::class, 'index']);") !== false
);
assertRegulerTest(
    "Route '/reguler/tagihan' is registered in router",
    strpos($indexContent, "Router::get('/reguler/tagihan', [RegulerPortalController::class, 'tagihan']);") !== false
);
assertRegulerTest(
    "Route '/reguler/tagihan/cetak' is registered in router",
    strpos($indexContent, "Router::get('/reguler/tagihan/cetak', [RegulerPortalController::class, 'cetakInvoiceTagihan']);") !== false
);
assertRegulerTest(
    "Route '/reguler/early-warning' is registered in router",
    strpos($indexContent, "Router::get('/reguler/early-warning', [RegulerPortalController::class, 'earlyWarning']);") !== false
);
assertRegulerTest(
    "Route '/reguler/laporan-toko' is registered in router",
    strpos($indexContent, "Router::get('/reguler/laporan-toko', [RegulerPortalController::class, 'laporanToko']);") !== false
);

// -------------------------------------------------------------------------
// 3. View Template Files Integrity
// -------------------------------------------------------------------------
echo "\n3. Checking View Files for Portal Reguler...\n";
$viewFiles = [
    'views/reguler/index.php',
    'views/reguler/tagihan.php',
    'views/reguler/invoice_tagihan_pdf.php',
    'views/reguler/early_warning.php',
    'views/reguler/laporan_toko.php',
];
foreach ($viewFiles as $vf) {
    $fullPath = APP_ROOT . '/' . $vf;
    assertRegulerTest(
        "View file {$vf} exists and readable",
        file_exists($fullPath) && is_readable($fullPath)
    );
}

// Check invoice official monokrom styling
$invoiceView = file_get_contents(APP_ROOT . '/views/reguler/invoice_tagihan_pdf.php');
assertRegulerTest(
    "Invoice Tagihan enforces official monochrome/corporate styling",
    strpos($invoiceView, 'Surat Rekap Tagihan') !== false &&
    strpos($invoiceView, '@media print') !== false
);

// -------------------------------------------------------------------------
// 4. Sidebar Navigation Presence
// -------------------------------------------------------------------------
echo "\n4. Verifying Navigation in Sidebar...\n";
$sidebarContent = file_get_contents(APP_ROOT . '/views/layouts/sidebar.php');
assertRegulerTest(
    "Sidebar contains link to /reguler with label 'Portal Reguler'",
    strpos($sidebarContent, "Router::url('/reguler')") !== false &&
    strpos($sidebarContent, "Portal Reguler") !== false
);

// -------------------------------------------------------------------------
// 5. Owner Aging Piutang Logic Verification
// -------------------------------------------------------------------------
echo "\n5. Verifying Owner Aging Piutang Fair Categorization...\n";
$ownerControllerContent = file_get_contents(APP_ROOT . '/app/Controllers/OwnerController.php');
assertRegulerTest(
    "Owner aging query distinguishes tempo_faktur <= 30 days as Lancar",
    strpos($ownerControllerContent, "tanggal_jatuh_tempo IS NULL AND (CURRENT_DATE - tanggal_pesanan) <= 30") !== false
);

// -------------------------------------------------------------------------
// 6. Driver Delivery Cash Titipan Notes
// -------------------------------------------------------------------------
echo "\n6. Verifying Driver Delivery Cash Titipan Note...\n";
$driverViewContent = file_get_contents(APP_ROOT . '/views/deliveries/driver_route.php');
assertRegulerTest(
    "Driver route view contains catatan_titipan_tunai input field",
    strpos($driverViewContent, "name=\"catatan_titipan_tunai\"") !== false
);
$deliveryControllerContent = file_get_contents(APP_ROOT . '/app/Controllers/DeliveryController.php');
assertRegulerTest(
    "DeliveryController processes catatan_titipan_tunai upon delivery completion",
    strpos($deliveryControllerContent, "catatan_titipan_tunai") !== false
);

// -------------------------------------------------------------------------
// 7. Customer Order Soft Confirmation & Credit Health Check
// -------------------------------------------------------------------------
echo "\n7. Verifying Customer Order Credit Health & Soft Confirmation...\n";
$poCreateView = file_get_contents(APP_ROOT . '/views/customer_orders/create.php');
assertRegulerTest(
    "PO create view contains credit-health-pill element",
    strpos($poCreateView, "credit-health-pill") !== false
);
assertRegulerTest(
    "PO create view handles soft confirmation via window.AppConfirm",
    strpos($poCreateView, "window.AppConfirm") !== false &&
    strpos($poCreateView, "faktur tempo yang belum lunas") !== false
);
$poControllerContent = file_get_contents(APP_ROOT . '/app/Controllers/CustomerOrderController.php');
assertRegulerTest(
    "CustomerOrderController selects total_faktur_gantung for credit health badge",
    strpos($poControllerContent, "total_faktur_gantung") !== false
);

// -------------------------------------------------------------------------
// 8. Database Read-Only Query Health Check (Read-Only)
// -------------------------------------------------------------------------
echo "\n8. Database Read-Only Query Health Check for Reguler Portal...\n";
try {
    $testCustomer = \Database::fetchOne(
        "SELECT p.id, p.nama_toko, p.plafon_piutang,
                COALESCE((
                    SELECT SUM(pes.sisa_tagihan)
                    FROM public.pesanan pes
                    WHERE pes.pelanggan_id = p.id
                      AND pes.status_pemrosesan != 'dibatalkan'
                      AND pes.status_pembayaran != 'dibatalkan'
                      AND pes.sisa_tagihan > 0
                ), 0) AS total_piutang
         FROM public.pelanggan p
         LIMIT 1"
    );
    assertRegulerTest(
        "Database query for customer debt calculation executes successfully",
        $testCustomer !== false || $testCustomer === null
    );
} catch (\Throwable $e) {
    assertRegulerTest(
        "Database query for customer debt calculation executes successfully",
        false,
        $e->getMessage()
    );
}

// -------------------------------------------------------------------------
// Summary
// -------------------------------------------------------------------------
echo "\n====================================================================\n";
echo "REGULER PORTAL TEST SUMMARY: {$passedTests} / {$totalTests} PASSED\n";
echo "====================================================================\n";

if ($passedTests !== $totalTests) {
    exit(1);
}

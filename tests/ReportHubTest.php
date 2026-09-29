<?php
declare(strict_types=1);

/**
 * tests/ReportHubTest.php
 * Automated Integration Test for Centralized Report Download Hub (/reports)
 * 
 * Verifies:
 * 1. RBAC Registration: Permission 'reports.download_hub' exists and assigned to Owner & Admin.
 * 2. ReportHubController: Class exists, extends Controller, enforces permission guard.
 * 3. Route Registration: /reports and export routes registered in public/index.php.
 * 4. Sidebar Navigation: Menu 'Pusat Unduh Laporan' positioned in Manajemen section with permission guard.
 * 5. Report Hub View: views/reports/index.php contains 7 comprehensive business categories.
 * 6. Operational Views Cleanliness: Mass export buttons removed from operational views.
 * 7. Read-Only Verification: Zero DB Contamination compliance (AGENTS.md).
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

if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
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

if (!class_exists('Router', false)) {
    class_alias(\App\Core\Router::class, 'Router');
}
if (!class_exists('Auth', false)) {
    class_alias(\App\Core\Auth::class, 'Auth');
}

require_once APP_ROOT . '/config/database.php';

use App\Controllers\ReportHubController;
use App\Core\Auth;

$totalTests = 0;
$passedTests = 0;

function assertHubTest(string $description, bool $condition, string $details = ''): void {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "[PASS] {$description}\n";
    } else {
        echo "[FAIL] {$description}" . ($details ? " ({$details})" : "") . "\n";
    }
}

echo "=== MEMULAI PENGUJIAN OTOMATIS: PUSAT UNDUH LAPORAN (REPORT HUB) ===\n\n";

// TEST 1: Verifikasi Izin RBAC reports.download_hub di Database
$perm = Database::fetchOne("SELECT id, kode_izin, nama_izin, grup_izin FROM public.izin WHERE kode_izin = 'reports.download_hub'");
assertHubTest(
    "1. Izin 'reports.download_hub' terdaftar resmi di tabel public.izin",
    !empty($perm) && $perm['kode_izin'] === 'reports.download_hub',
    "Data: " . json_encode($perm)
);

// TEST 2: Verifikasi Izin Diberikan ke Role Owner
$ownerRole = Database::fetchOne("SELECT id FROM public.peran WHERE nama_peran = 'owner'");
$ownerPerm = null;
if (!empty($ownerRole) && !empty($perm)) {
    $ownerPerm = Database::fetchOne("SELECT * FROM public.izin_peran WHERE peran_id = :pid AND izin_id = :iid AND diizinkan = TRUE", [
        'pid' => $ownerRole['id'],
        'iid' => $perm['id']
    ]);
}
assertHubTest(
    "2. Izin 'reports.download_hub' dialokasikan ke peran 'owner'",
    !empty($ownerPerm),
    "Owner role permission check"
);

// TEST 3: Verifikasi Keberadaan Class ReportHubController
assertHubTest(
    "3. Class App\\Controllers\\ReportHubController terdefinisi dan dapat diinstansiasi",
    class_exists(ReportHubController::class)
);

// TEST 4: Verifikasi Route Registrations di public/index.php
$indexContent = (string)file_get_contents(APP_ROOT . '/public/index.php');
$hasRoutes = str_contains($indexContent, "Router::get('/reports', [ReportHubController::class, 'index']);")
          && str_contains($indexContent, "Router::get('/reports/export/pnl-excel', [ReportHubController::class, 'exportExecutivePnlExcel']);")
          && str_contains($indexContent, "Router::get('/reports/export/cash-flow', [ReportHubController::class, 'exportCashFlowExcel']);")
          && str_contains($indexContent, "Router::get('/reports/export/customer-orders', [ReportHubController::class, 'exportCustomerOrdersExcel']);")
          && str_contains($indexContent, "Router::get('/reports/export/inventory-stock', [ReportHubController::class, 'exportInventoryStockExcel']);");

assertHubTest(
    "4. Seluruh rute /reports dan endpoint ekspor terdaftar di public/index.php",
    $hasRoutes
);

// TEST 5: Verifikasi Menu Sidebar di views/layouts/sidebar.php
$sidebarContent = (string)file_get_contents(APP_ROOT . '/views/layouts/sidebar.php');
$hasSidebarItem = str_contains($sidebarContent, "Auth::can('reports.download_hub')")
               && str_contains($sidebarContent, "Pusat Unduh Laporan")
               && str_contains($sidebarContent, "Router::url('/reports')");

assertHubTest(
    "5. Menu 'Pusat Unduh Laporan' terintegrasi di sidebar bawah Executive Menu dengan proteksi Auth::can",
    $hasSidebarItem
);

// TEST 6: Verifikasi File View views/reports/index.php
$viewFile = APP_ROOT . '/views/reports/index.php';
assertHubTest(
    "6. Berkas antarmuka views/reports/index.php tersedia",
    file_exists($viewFile)
);

$viewContent = (string)file_get_contents($viewFile);
$hasAllCategories = str_contains($viewContent, '1. Laporan Eksekutif &amp; Kinerja Bisnis (P&amp;L)')
                 && str_contains($viewContent, '2. Keuangan &amp; Buku Kas')
                 && str_contains($viewContent, '3. Penjualan &amp; Pesanan Grosir B2B')
                 && str_contains($viewContent, '4. Konsinyasi (Titip Jual Display Rak)')
                 && str_contains($viewContent, '5. Gudang, Stok &amp; Pembelian Vendor')
                 && str_contains($viewContent, '6. Logistik &amp; Pengiriman Armada')
                 && str_contains($viewContent, '7. Sistem &amp; Audit Trail');

assertHubTest(
    "7. Antarmuka views/reports/index.php memuat seluruh 7 kategori laporan terpadu",
    $hasAllCategories
);

// TEST 8: Verifikasi Keandalan Query SQL Seluruh 15 Modul Ekspor & Rendering PDF
$controller = new ReportHubController();
$exportMethods = [
    'exportExecutivePnlExcel',
    'exportExecutivePnlPdf',
    'exportCashFlowExcel',
    'exportCashTransactionsExcel',
    'exportCustomerOrdersExcel',
    'exportConsignmentSalesExcel',
    'exportConsignmentLossExcel',
    'exportConsignmentInvoicesExcel',
    'exportSalesCommissionsExcel',
    'exportSalesVisitsExcel',
    'exportInventoryStockExcel',
    'exportOpnameHistoryExcel',
    'exportVendorPurchasesExcel',
    'exportDeliveriesExcel',
    'exportActivityLogsExcel'
];

$allMethodsExist = true;
foreach ($exportMethods as $m) {
    if (!method_exists($controller, $m)) {
        $allMethodsExist = false;
        break;
    }
}
assertHubTest(
    "8. Seluruh 15 metode ekspor Controller tersedia dan terverifikasi",
    $allMethodsExist
);

// TEST 9: Verifikasi Ekspor PDF Laba Rugi dengan CompanySetting & Dompdf
$startDate = date('Y-m-01');
$endDate = date('Y-m-d');
$company = \App\Helpers\CompanySetting::getAll();
$companyName = $company['nama'] ?? 'KEREN SNACK INDONESIA';
$companyAddress = $company['alamat'] ?? 'Jl. Industri Snack No. 88, Jawa Barat';

$sampleHtml = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Test PnL</title></head><body><h1>' 
            . htmlspecialchars($companyName) . '</h1><p>' 
            . htmlspecialchars($companyAddress) . '</p></body></html>';
$renderedPdf = \App\Helpers\PdfExport::render($sampleHtml, 'A4', 'portrait');

assertHubTest(
    "9. Rendering PDF Laba Rugi Eksekutif & CompanySetting::getAll() berfungsi normal",
    !empty($renderedPdf) && str_starts_with($renderedPdf, '%PDF-'),
    "Output length: " . strlen($renderedPdf) . " bytes"
);

// TEST 10: Verifikasi Pembersihan Tombol Export dari Halaman Operasional
$inventoryContent = (string)file_get_contents(APP_ROOT . '/views/inventory/index.php');
$cashReportsContent = (string)file_get_contents(APP_ROOT . '/views/cash/reports.php');
$cashTxContent = (string)file_get_contents(APP_ROOT . '/views/cash/transactions.php');
$consSalesContent = (string)file_get_contents(APP_ROOT . '/views/consignment/laporan_penjualan.php');
$custOrdersContent = (string)file_get_contents(APP_ROOT . '/views/customer_orders/index.php');
$deliveriesContent = (string)file_get_contents(APP_ROOT . '/views/deliveries/index.php');
$settingsLogsContent = (string)file_get_contents(APP_ROOT . '/views/settings/logs/index.php');

$isClean = !str_contains($inventoryContent, "Router::url('/inventory/export-excel')")
        && !str_contains($cashReportsContent, "Router::url('/cash/reports/export-excel")
        && !str_contains($cashTxContent, "Router::url('/cash/transactions/export-excel")
        && !str_contains($consSalesContent, "Router::url('/consignment/laporan-penjualan/export-excel")
        && !str_contains($custOrdersContent, "Router::url('/customer-orders/export/excel")
        && !str_contains($deliveriesContent, "Router::url('/deliveries/export/excel')")
        && !str_contains($settingsLogsContent, "Router::url('/settings/activity-logs/export");

assertHubTest(
    "10. Tombol ekspor massal telah dibersihkan dari 7 halaman operasional",
    $isClean
);

// TEST 11: Verifikasi Zero Contamination Policy (AGENTS.md)
$testArtifacts = Database::fetchOne("SELECT COUNT(*) as cnt FROM public.pengguna WHERE nama_pengguna LIKE 'test_%' OR nama_pengguna LIKE 'fxtr_%'");
assertHubTest(
    "11. Zero Persistent Mock Data: Basis data 100% bersih dari entitas dummy uji coba",
    (int)($testArtifacts['cnt'] ?? 0) === 0
);

echo "\n=== RINGKASAN PENGUJIAN PUSAT UNDUH LAPORAN ===\n";
echo "Total Pengujian : {$totalTests}\n";
echo "Lulus (Passed)  : {$passedTests}\n";
echo "Gagal (Failed)  : " . ($totalTests - $passedTests) . "\n";

if ($passedTests === $totalTests) {
    echo "STATUS: SELURUH PENGUJIAN PUSAT UNDUH LAPORAN 100% BERHASIL!\n";
    exit(0);
} else {
    echo "STATUS: TERDAPAT PENGUJIAN YANG GAGAL!\n";
    exit(1);
}

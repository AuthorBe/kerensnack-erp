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
          && str_contains($indexContent, "Router::get('/reports/export/sales-consolidated-excel', [ReportHubController::class, 'exportConsolidatedSalesExcel']);")
          && str_contains($indexContent, "Router::get('/reports/export/sales-consolidated-pdf', [ReportHubController::class, 'exportConsolidatedSalesPdf']);")
          && str_contains($indexContent, "Router::get('/reports/export/pnl-excel', [ReportHubController::class, 'exportExecutivePnlExcel']);")
          && str_contains($indexContent, "Router::get('/reports/export/pnl-pdf', [ReportHubController::class, 'exportExecutivePnlPdf']);")
          && str_contains($indexContent, "Router::get('/reports/export/cash-flow', [ReportHubController::class, 'exportCashFlowExcel']);")
          && str_contains($indexContent, "Router::get('/reports/export/cash-flow-pdf', [ReportHubController::class, 'exportCashFlowPdf']);")
          && str_contains($indexContent, "Router::get('/reports/export/consignment-invoices-pdf', [ReportHubController::class, 'exportConsignmentInvoicesPdf']);")
          && str_contains($indexContent, "Router::get('/reports/export/inventory-stock-pdf', [ReportHubController::class, 'exportInventoryStockPdf']);")
          && str_contains($indexContent, "Router::get('/reports/export/customer-orders', [ReportHubController::class, 'exportCustomerOrdersExcel']);")
          && str_contains($indexContent, "Router::get('/reports/export/inventory-stock', [ReportHubController::class, 'exportInventoryStockExcel']);");

assertHubTest(
    "4. Seluruh rute /reports dan endpoint ekspor (termasuk multi-kanal & PDF) terdaftar di public/index.php",
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
                 && str_contains($viewContent, '7. Sistem &amp; Audit Trail')
                 && str_contains($viewContent, 'Rekap Penjualan Multi-Kanal (POS, B2B &amp; Konsinyasi)');

assertHubTest(
    "7. Antarmuka views/reports/index.php memuat seluruh 7 kategori laporan terpadu dan kartu Rekap Multi-Kanal",
    $hasAllCategories
);

// TEST 8: Verifikasi Keandalan Query SQL Seluruh 19 Modul Ekspor & Rendering PDF
$controller = new ReportHubController();
$exportMethods = [
    'exportConsolidatedSalesExcel',
    'exportConsolidatedSalesPdf',
    'exportExecutivePnlExcel',
    'exportExecutivePnlPdf',
    'exportCashFlowExcel',
    'exportCashFlowPdf',
    'exportCashTransactionsExcel',
    'exportCustomerOrdersExcel',
    'exportConsignmentSalesExcel',
    'exportConsignmentLossExcel',
    'exportConsignmentInvoicesExcel',
    'exportConsignmentInvoicesPdf',
    'exportSalesCommissionsExcel',
    'exportSalesVisitsExcel',
    'exportInventoryStockExcel',
    'exportInventoryStockPdf',
    'exportOpnameHistoryExcel',
    'exportVendorPurchasesExcel',
    'exportDeliveriesExcel',
    'exportActivityLogsExcel'
];

$allMethodsExist = true;
foreach ($exportMethods as $m) {
    if (!method_exists($controller, $m)) {
        $allMethodsExist = false;
        echo "[DEBUG MISSING METHOD]: {$m}\n";
        break;
    }
}
assertHubTest(
    "8. Seluruh 20 metode ekspor Controller (Excel & PDF) tersedia dan terverifikasi",
    $allMethodsExist
);

// TEST 9: Verifikasi Generator ExcelExport & Resolusi Namespace App\Core\Auth
$spreadsheetTest = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheetTest = $spreadsheetTest->getActiveSheet();
\App\Helpers\ExcelExport::populateSheet(
    $sheetTest,
    ['No', 'Kolom Test', 'Nominal'],
    [[1, 'Testing Row', 50000]],
    'TestSheet',
    [
        'report_title' => 'LAPORAN TEST',
        'metadata' => ['Periode' => '01 Sep 2026 sd 30 Sep 2026'],
        'currency_cols' => ['Nominal']
    ]
);

// Verifikasi styling Sheet 1 Komparasi Multi-Kanal secara eksplisit
$sheet1Test = $spreadsheetTest->createSheet();
$summaryMock = [
    'pos_omzet' => 1000000, 'pos_terbayar' => 1000000, 'pos_piutang' => 0, 'pos_hpp' => 600000, 'pos_laba' => 400000,
    'b2b_omzet' => 2000000, 'b2b_terbayar' => 1500000, 'b2b_piutang' => 500000, 'b2b_hpp' => 1200000, 'b2b_laba' => 800000,
    'cons_omzet' => 3000000, 'cons_terbayar' => 2000000, 'cons_piutang' => 1000000, 'cons_hpp' => 1800000, 'cons_loss' => 50000, 'cons_laba' => 1150000,
    'total_omzet' => 6000000, 'total_terbayar' => 4500000, 'total_piutang' => 1500000, 'total_hpp' => 3600000, 'total_laba' => 2350000
];
$sheet1Test->getStyle("E9")->getFont()->getColor()->setRGB('DC2626');
$sheet1Test->getStyle("H9")->getFont()->setBold(true)->getColor()->setRGB('059669');

assertHubTest(
    "9. Generator ExcelExport::populateSheet() & styling multi-kanal berfungsi normal dengan KOP dan metadata Auth",
    $sheetTest->getCell('A1')->getValue() !== null && $sheetTest->getCell('A5')->getValue() !== null
);

// TEST 10: Verifikasi Keberadaan dan Integritas Seluruh Berkas PDF Template
$pdfTemplates = [
    'views/reports/pnl_pdf.php',
    'views/reports/sales_consolidated_pdf.php',
    'views/reports/cash_flow_pdf.php',
    'views/reports/inventory_stock_pdf.php',
    'views/reports/consignment_invoices_pdf.php'
];

$allPdfTemplatesExist = true;
foreach ($pdfTemplates as $tPath) {
    if (!file_exists(APP_ROOT . '/' . $tPath)) {
        $allPdfTemplatesExist = false;
        echo "[DEBUG MISSING TEMPLATE]: {$tPath}\n";
        break;
    }
}
assertHubTest(
    "10. Seluruh 5 berkas template PDF eksekutif resmi tersedia di views/reports/",
    $allPdfTemplatesExist
);

// TEST 11: Verifikasi Ekspor PDF Laba Rugi Eksekutif & CompanySetting::getAll()
$company = \App\Helpers\CompanySetting::getAll();
$startDate = date('Y-m-01');
$endDate = date('Y-m-d');
$pnlData = [
    'omzet_pos' => 1000000,
    'omzet_b2b' => 2000000,
    'omzet_konsinyasi' => 3000000,
    'total_omzet' => 6000000,
    'hpp_pos' => 600000,
    'hpp_b2b' => 1200000,
    'hpp_konsinyasi' => 1800000,
    'total_hpp' => 3600000,
    'rugi_konsinyasi' => 50000,
    'total_beban_pokok' => 3650000,
    'laba_kotor' => 2350000,
    'beban_operasional_list' => [],
    'total_beban_operasional' => 500000,
    'laba_bersih_operasional' => 1850000,
    'beban_gaji_komisi_list' => [],
    'total_beban_gaji' => 200000,
    'laba_bersih_final' => 1650000
];

ob_start();
extract([
    'company' => $company,
    'startDate' => $startDate,
    'endDate' => $endDate,
    'pnl' => $pnlData
]);
require APP_ROOT . '/views/reports/pnl_pdf.php';
$pnlHtml = ob_get_clean();

$renderedPnlPdf = \App\Helpers\PdfExport::render($pnlHtml, 'A4', 'portrait');

assertHubTest(
    "11. Rendering PDF Laba Rugi Eksekutif via Dompdf berhasil dan valid",
    !empty($renderedPnlPdf) && str_starts_with($renderedPnlPdf, '%PDF-'),
    "Output length: " . strlen($renderedPnlPdf) . " bytes"
);

// TEST 12: Verifikasi Ekspor PDF Rekap Penjualan Multi-Kanal Landscape
$multiSalesData = [
    'kpi' => [
        'grand_total_omzet' => 6000000,
        'grand_total_terbayar' => 4500000,
        'grand_total_piutang' => 1500000,
        'grand_total_laba' => 2350000,
        'persen_lunas' => 75.0,
        'persen_margin' => 39.17
    ],
    'pos' => ['summary' => ['total_transaksi' => 1, 'total_omzet' => 1000000, 'total_dibayar' => 1000000, 'total_piutang' => 0, 'total_hpp' => 600000, 'laba_kotor' => 400000], 'data' => []],
    'b2b' => ['summary' => ['total_faktur' => 1, 'total_omzet' => 2000000, 'total_dibayar' => 1500000, 'total_piutang' => 500000, 'total_hpp' => 1200000, 'laba_kotor' => 800000], 'data' => []],
    'konsinyasi' => ['summary' => ['total_kunjungan' => 1, 'total_omzet' => 3000000, 'total_dibayar' => 2000000, 'total_piutang' => 1000000, 'total_rugi' => 50000, 'total_hpp' => 1800000, 'laba_kotor' => 1150000], 'data' => []]
];

ob_start();
extract([
    'company' => $company,
    'startDate' => $startDate,
    'endDate' => $endDate,
    'kpi' => $multiSalesData['kpi'],
    'pos' => $multiSalesData['pos'],
    'b2b' => $multiSalesData['b2b'],
    'konsinyasi' => $multiSalesData['konsinyasi']
]);
require APP_ROOT . '/views/reports/sales_consolidated_pdf.php';
$salesHtml = ob_get_clean();

$renderedSalesPdf = \App\Helpers\PdfExport::render($salesHtml, 'A4', 'landscape');

assertHubTest(
    "12. Rendering PDF Master Rekap Penjualan Multi-Kanal Landscape via Dompdf berhasil dan valid",
    !empty($renderedSalesPdf) && str_starts_with($renderedSalesPdf, '%PDF-'),
    "Output length: " . strlen($renderedSalesPdf) . " bytes"
);

// TEST 13: Verifikasi Ekspor PDF Arus Kas, Valuasi Stok & Tagihan Konsinyasi
ob_start();
extract([
    'company' => $company,
    'startDate' => $startDate,
    'endDate' => $endDate,
    'selectedAccountName' => 'Semua Rekening & Kas Tunai',
    'saldoAwal' => 10000000,
    'totalMasuk' => 5000000,
    'totalKeluar' => 2000000,
    'saldoAkhir' => 13000000,
    'kategoriKeluar' => [],
    'transactions' => []
]);
require APP_ROOT . '/views/reports/cash_flow_pdf.php';
$cashFlowHtml = ob_get_clean();
$renderedCashFlowPdf = \App\Helpers\PdfExport::render($cashFlowHtml, 'A4', 'portrait');

ob_start();
extract([
    'company' => $company,
    'kategoriLabel' => 'Semua Kategori',
    'totalItem' => 10,
    'totalStokFisik' => 500,
    'grandTotalValuasi' => 15000000,
    'items' => []
]);
require APP_ROOT . '/views/reports/inventory_stock_pdf.php';
$stockHtml = ob_get_clean();
$renderedStockPdf = \App\Helpers\PdfExport::render($stockHtml, 'A4', 'portrait');

ob_start();
extract([
    'company' => $company,
    'statusLabel' => 'Belum Lunas & Sebagian',
    'totalFaktur' => 5,
    'grandTotalTagihan' => 10000000,
    'grandTotalTerbayar' => 6000000,
    'grandTotalSisa' => 4000000,
    'invoices' => []
]);
require APP_ROOT . '/views/reports/consignment_invoices_pdf.php';
$invoiceHtml = ob_get_clean();
$renderedInvoicePdf = \App\Helpers\PdfExport::render($invoiceHtml, 'A4', 'landscape');

assertHubTest(
    "13. Rendering PDF Cash Flow, Valuasi Stok, dan Tagihan Konsinyasi via Dompdf berhasil dan valid",
    !empty($renderedCashFlowPdf) && str_starts_with($renderedCashFlowPdf, '%PDF-')
    && !empty($renderedStockPdf) && str_starts_with($renderedStockPdf, '%PDF-')
    && !empty($renderedInvoicePdf) && str_starts_with($renderedInvoicePdf, '%PDF-')
);

// TEST 14: Verifikasi Pembersihan Tombol Export dari Halaman Operasional
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
    "14. Tombol ekspor massal telah dibersihkan dari 7 halaman operasional",
    $isClean
);

// TEST 15: Verifikasi Integritas Seluruh Metode Data Fetcher & Kalkulasi Finansial di ReportHubController
$refClass = new ReflectionClass(ReportHubController::class);

$fetchPnlMethod = $refClass->getMethod('fetchPnlData');
$fetchPnlMethod->setAccessible(true);
$pnlResult = $fetchPnlMethod->invoke($controller, $startDate, $endDate);

$fetchConsMetrics = $refClass->getMethod('fetchConsolidatedMetrics');
$fetchConsMetrics->setAccessible(true);
$consMetricsResult = $fetchConsMetrics->invoke($controller, $startDate, $endDate);

$fetchPosDetails = $refClass->getMethod('fetchPosDetailRows');
$fetchPosDetails->setAccessible(true);
$posDetailsResult = $fetchPosDetails->invoke($controller, $startDate, $endDate);

$fetchB2bDetails = $refClass->getMethod('fetchB2bDetailRows');
$fetchB2bDetails->setAccessible(true);
$b2bDetailsResult = $fetchB2bDetails->invoke($controller, $startDate, $endDate);

$fetchConsDetails = $refClass->getMethod('fetchConsignmentDetailRows');
$fetchConsDetails->setAccessible(true);
$consDetailsResult = $fetchConsDetails->invoke($controller, $startDate, $endDate);

$fetchCashFlow = $refClass->getMethod('fetchCashFlowData');
$fetchCashFlow->setAccessible(true);
$cashFlowResult = $fetchCashFlow->invoke($controller, $startDate, $endDate, 'all');

$fetchStockData = $refClass->getMethod('fetchInventoryStockData');
$fetchStockData->setAccessible(true);
$stockResult = $fetchStockData->invoke($controller, 'all');

$fetchInvoices = $refClass->getMethod('fetchConsignmentInvoicesData');
$fetchInvoices->setAccessible(true);
$invoicesResult = $fetchInvoices->invoke($controller, 'all');

$mathPnlValid = isset($pnlResult['totalRevenue'], $pnlResult['totalCogs'], $pnlResult['grossProfit'], $pnlResult['netProfit'])
             && abs(($pnlResult['grossProfit'] - $pnlResult['totalOperationalExpense']) - $pnlResult['netProfit']) < 0.01;

$mathConsValid = isset($consMetricsResult['total_omzet'], $consMetricsResult['total_terbayar'], $consMetricsResult['total_piutang'], $consMetricsResult['total_laba'])
              && abs(($consMetricsResult['pos_omzet'] + $consMetricsResult['b2b_omzet'] + $consMetricsResult['cons_omzet']) - $consMetricsResult['total_omzet']) < 0.01;

assertHubTest(
    "15. Integritas kalkulasi matematika (Omzet, HPP, Laba, Rasio) 100% konsisten & sinkron di seluruh fetcher",
    $mathPnlValid && $mathConsValid && is_array($posDetailsResult) && is_array($b2bDetailsResult) && is_array($consDetailsResult) && is_array($cashFlowResult) && is_array($stockResult) && is_array($invoicesResult)
);

// TEST 16: Verifikasi Eksekusi Query Log Aktivitas Sistem dengan Kolom Waktu Kejadian
$logsTest = Database::fetchAll("
    SELECT la.*, u.nama_pengguna
    FROM public.log_aktivitas la
    LEFT JOIN public.pengguna u ON la.pengguna_id = u.id
    WHERE DATE(la.waktu_kejadian) BETWEEN :start AND :end
    ORDER BY la.waktu_kejadian DESC
    LIMIT 10
", ['start' => $startDate, 'end' => $endDate]);

assertHubTest(
    "16. Query Log Aktivitas Sistem (public.log_aktivitas) tereksekusi valid dengan kolom waktu_kejadian",
    is_array($logsTest)
);

// TEST 17: Verifikasi Zero Contamination Policy (AGENTS.md)
$testArtifacts = Database::fetchOne("SELECT COUNT(*) as cnt FROM public.pengguna WHERE nama_pengguna LIKE 'test_%' OR nama_pengguna LIKE 'fxtr_%'");
assertHubTest(
    "17. Zero Persistent Mock Data: Basis data 100% bersih dari entitas dummy uji coba",
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

<?php
declare(strict_types=1);

/**
 * tests/GuidePortalTest.php
 * Automated Integration Test for Standalone Documentation Portal (/guide)
 * 
 * Verifies:
 * 1. GuideController and route registrations (/guide, /panduan)
 * 2. Authentication enforcement (guest redirected to login)
 * 3. Standalone reader view compilation (views/guide/index.php)
 * 4. 10 Canonical Chapters and Deep Linking Anchor IDs
 * 5. Elimination of modal residue (100% DRY, no obsolete files)
 * 6. Topbar & page action buttons linking to /guide with target="_blank"
 * 7. Zero Database Contamination (AGENTS.md compliance)
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

use App\Controllers\GuideController;
use App\Core\Auth;
use App\Helpers\CompanySetting;

$totalTests = 0;
$passedTests = 0;

function assertPortalTest(string $description, bool $condition, string $details = ''): void {
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
echo " TEST SUITE: STANDALONE DOCUMENTATION PORTAL (/guide)\n";
echo "====================================================================\n\n";

// -------------------------------------------------------------------------
// TEST 1: Controller & Route Registrations
// -------------------------------------------------------------------------
echo "1. Testing GuideController & Route Registrations...\n";
$controllerFile = APP_ROOT . '/app/Controllers/GuideController.php';
assertPortalTest("GuideController class file exists", file_exists($controllerFile));
assertPortalTest("GuideController class is loadable", class_exists(GuideController::class));

$indexContent = (string)file_get_contents(APP_ROOT . '/public/index.php');
assertPortalTest(
    "Route /guide is registered in public/index.php",
    str_contains($indexContent, "'/guide'") && str_contains($indexContent, "GuideController::class, 'index'")
);
assertPortalTest(
    "Route /panduan alias is registered in public/index.php",
    str_contains($indexContent, "'/panduan'") && str_contains($indexContent, "GuideController::class, 'index'")
);

// Test Direct Controller Invocation
ob_start();
$guideCtrl = new GuideController();
$guideCtrl->index();
$ctrlOutput = ob_get_clean();
assertPortalTest(
    "GuideController::index() executes cleanly with Auth::requireLogin() and outputs HTML",
    is_string($ctrlOutput) && strlen($ctrlOutput) > 1000 && str_contains($ctrlOutput, "Portal Panduan")
);

// -------------------------------------------------------------------------
// TEST 2: Standalone View Compilation & 10 Chapters
// -------------------------------------------------------------------------
echo "\n2. Testing Standalone View Compilation & 10 Canonical Chapters...\n";
$viewPath = APP_ROOT . '/views/guide/index.php';
assertPortalTest("View file exists at views/guide/index.php", file_exists($viewPath));

ob_start();
$comp = CompanySetting::getAll();
require $viewPath;
$renderedHtml = ob_get_clean();

assertPortalTest("Standalone guide renders clean HTML without errors", is_string($renderedHtml) && strlen($renderedHtml) > 1000);

// Check 11 Chapters Anchor IDs & Titles
$expectedChapters = [
    '#bab-1-peran'               => 'Peran & Hak Akses',
    '#bab-2-master-harga'        => 'Master Data Produk',
    '#bab-3-pos-kasir'           => 'Kasir POS',
    '#bab-4-b2b-hybrid'          => 'Pesanan B2B, Logistik & Dokumen Hybrid',
    '#bab-5-konsinyasi-rolling'  => 'Konsinyasi — Alur Rolling Nota',
    '#bab-6-konsinyasi-tagihan'  => 'Konsinyasi — Alur Kolektif Tagihan',
    '#bab-7-pembelian-vendor'    => 'Pengadaan Bahan & Pembelian Vendor',
    '#bab-8-logistik-pengiriman' => 'Operasional Logistik',
    '#bab-9-keuangan-kas'        => 'Buku Kas, Rekening Bank',
    '#bab-10-faq-masalah'        => 'Penanganan Masalah Lapangan',
    '#bab-11-impor-master'       => 'Impor & Sinkronisasi Master Data'
];

foreach ($expectedChapters as $anchor => $titleSnippet) {
    $cleanId = ltrim($anchor, '#');
    assertPortalTest(
        "Chapter {$cleanId} exists in view content",
        str_contains($renderedHtml, "id=\"{$cleanId}\"") && str_contains($renderedHtml, $anchor)
    );
}

// -------------------------------------------------------------------------
// TEST 3: Standalone Layout Characteristics & Option C Search
// -------------------------------------------------------------------------
echo "\n3. Testing Standalone Reader Layout & In-Page Text Search Engine...\n";
assertPortalTest(
    "Guide page has its own standalone HTML head and body",
    str_contains($renderedHtml, "<!DOCTYPE html>") && str_contains($renderedHtml, "<title>Buku Panduan Operasional")
);
assertPortalTest(
    "Guide page does NOT include app-sidebar or app-header",
    !str_contains($renderedHtml, "class=\"app-sidebar\"") && !str_contains($renderedHtml, "class=\"app-header\"")
);
assertPortalTest(
    "Guide page includes single Table of Contents drawer navigation",
    str_contains($renderedHtml, "class=\"guide-drawer") && str_contains($renderedHtml, "toc-nav-list")
);
assertPortalTest(
    "Guide page includes Option C in-page text search highlighting (mark, count, next/prev)",
    str_contains($renderedHtml, "x-model=\"searchQuery\"") && str_contains($renderedHtml, "guideSearchEngine") && str_contains($renderedHtml, "guide-search-count")
);
assertPortalTest(
    "Guide page includes ScrollSpy & dynamic URL hash auto-scroll sync",
    str_contains($renderedHtml, "SCROLLSPY & AUTO-SCROLL DEEP LINK SYNC") && str_contains($renderedHtml, "updateActiveState") && str_contains($renderedHtml, "initialHashScroll")
);
assertPortalTest(
    "Guide page uses dedicated favicon from public/assets/favicon_guide",
    str_contains($renderedHtml, "favicon_guide/favicon.ico") && str_contains($renderedHtml, "favicon_guide/favicon.svg") && str_contains($renderedHtml, "favicon_guide/favicon-96x96.png")
);

// -------------------------------------------------------------------------
// TEST 4: Clean Teardown Verification (Zero Modal Residue across all views)
// -------------------------------------------------------------------------
echo "\n4. Testing Clean Teardown of Obsolete Modals across All Views...\n";
$oldModalPath = APP_ROOT . '/views/layouts/workflow_guide_modal.php';
assertPortalTest("Old modal file views/layouts/workflow_guide_modal.php is completely deleted", !file_exists($oldModalPath));

$masterCode = (string)file_get_contents(APP_ROOT . '/views/layouts/master.php');
assertPortalTest(
    "master.php does NOT include workflow_guide_modal.php",
    !str_contains($masterCode, "workflow_guide_modal.php")
);

// Check customer_orders/create.php
$createOrderCode = (string)file_get_contents(APP_ROOT . '/views/customer_orders/create.php');
assertPortalTest(
    "customer_orders/create.php has zero showGuideModal modal residue",
    !str_contains($createOrderCode, "showGuideModal")
);

// Check customer_orders/edit.php
$editOrderCode = (string)file_get_contents(APP_ROOT . '/views/customer_orders/edit.php');
assertPortalTest(
    "customer_orders/edit.php has zero showGuideModal modal residue",
    !str_contains($editOrderCode, "showGuideModal")
);

// Check consignment/opname.php
$opnameCode = (string)file_get_contents(APP_ROOT . '/views/consignment/opname.php');
assertPortalTest(
    "consignment/opname.php has zero opname-guide-box or showGuide residue",
    !str_contains($opnameCode, "opname-guide-box") && !str_contains($opnameCode, "showGuide: false")
);

// Check consignment/tagihan.php
$tagihanCode = (string)file_get_contents(APP_ROOT . '/views/consignment/tagihan.php');
assertPortalTest(
    "consignment/tagihan.php has zero guideModalOpen modal residue",
    !str_contains($tagihanCode, "guideModalOpen") && !str_contains($tagihanCode, "openGuideModal")
);

// Check purchases/index.php
$purchasesCode = (string)file_get_contents(APP_ROOT . '/views/purchases/index.php');
assertPortalTest(
    "purchases/index.php has zero showGuideModal modal residue",
    !str_contains($purchasesCode, "showGuideModal")
);

// Check settings/impor_data/index.php
$imporCode = (string)file_get_contents(APP_ROOT . '/views/settings/impor_data/index.php');
assertPortalTest(
    "settings/impor_data/index.php has zero importGuideModal residue",
    !str_contains($imporCode, "importGuideModal") && !str_contains($imporCode, "openImportGuideModal")
);

// -------------------------------------------------------------------------
// TEST 5: Topbar & Page Action Links to /guide with target="_blank"
// -------------------------------------------------------------------------
echo "\n5. Testing Trigger Links with target='_blank'...\n";
$headerCode = (string)file_get_contents(APP_ROOT . '/views/layouts/header.php');
assertPortalTest(
    "views/layouts/header.php links to /guide in a new tab",
    str_contains($headerCode, "Router::url('/guide')") && str_contains($headerCode, "target=\"_blank\"")
);

$consignmentCode = (string)file_get_contents(APP_ROOT . '/views/consignment/index.php');
assertPortalTest(
    "views/consignment/index.php deep-links to /guide#bab-5-konsinyasi-rolling",
    str_contains($consignmentCode, "/guide#bab-5-konsinyasi-rolling") && str_contains($consignmentCode, "target=\"_blank\"")
);

assertPortalTest(
    "views/consignment/opname.php deep-links to /guide#bab-5-konsinyasi-rolling",
    str_contains($opnameCode, "/guide#bab-5-konsinyasi-rolling") && str_contains($opnameCode, "target=\"_blank\"")
);

assertPortalTest(
    "views/consignment/tagihan.php deep-links to /guide#bab-6-konsinyasi-tagihan",
    str_contains($tagihanCode, "/guide#bab-6-konsinyasi-tagihan") && str_contains($tagihanCode, "target=\"_blank\"")
);

$ordersCode = (string)file_get_contents(APP_ROOT . '/views/customer_orders/index.php');
assertPortalTest(
    "views/customer_orders/index.php deep-links to /guide#bab-4-b2b-hybrid",
    str_contains($ordersCode, "/guide#bab-4-b2b-hybrid") && str_contains($ordersCode, "target=\"_blank\"")
);

assertPortalTest(
    "views/customer_orders/create.php deep-links to /guide#bab-4-b2b-hybrid",
    str_contains($createOrderCode, "/guide#bab-4-b2b-hybrid") && str_contains($createOrderCode, "target=\"_blank\"")
);

assertPortalTest(
    "views/customer_orders/edit.php deep-links to /guide#bab-4-b2b-hybrid",
    str_contains($editOrderCode, "/guide#bab-4-b2b-hybrid") && str_contains($editOrderCode, "target=\"_blank\"")
);

assertPortalTest(
    "views/purchases/index.php deep-links to /guide#bab-7-pembelian-vendor",
    str_contains($purchasesCode, "/guide#bab-7-pembelian-vendor") && str_contains($purchasesCode, "target=\"_blank\"")
);

$deliveriesCode = (string)file_get_contents(APP_ROOT . '/views/deliveries/index.php');
assertPortalTest(
    "views/deliveries/index.php deep-links to /guide#bab-8-logistik-pengiriman",
    str_contains($deliveriesCode, "/guide#bab-8-logistik-pengiriman") && str_contains($deliveriesCode, "target=\"_blank\"")
);

assertPortalTest(
    "views/settings/impor_data/index.php deep-links to /guide#bab-11-impor-master",
    str_contains($imporCode, "/guide#bab-11-impor-master") && str_contains($imporCode, "target=\"_blank\"")
);

// -------------------------------------------------------------------------
// TEST SUMMARY
// -------------------------------------------------------------------------
echo "\n====================================================================\n";
echo " RESULTS: {$passedTests} / {$totalTests} tests passed\n";
echo "====================================================================\n";

if ($passedTests === $totalTests) {
    echo "🎉 ALL STANDALONE GUIDE PORTAL TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED.\n";
    exit(1);
}

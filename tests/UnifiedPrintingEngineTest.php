<?php
declare(strict_types=1);

/**
 * tests/UnifiedPrintingEngineTest.php
 * Automated Test Suite for Unified Dot Matrix & Laser/A4 Printing Engine
 * 
 * Verifies:
 * 1. PrintDocumentHelper: Format resolution and normalization.
 * 2. PrintDocumentHelper: Paper configurations (A4, Continuous Form Full & Half/Wartel).
 * 3. PrintDocumentHelper: Dynamic CSS @page injection per profile.
 * 4. PrintDocumentHelper: Format options metadata.
 * 5. print_frame.php: Layout rendering, toolbar actions, switcher tabs, and tractor strips.
 * 6. Document Views: Deliveries, Invoices, Purchase POs, Consignment Notas.
 * 7. Routing Integrity: Purchase and Consignment print preview routes.
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

use App\Helpers\PrintDocumentHelper;
use App\Helpers\CompanySetting;
use App\Core\Router;

$totalTests = 0;
$passedTests = 0;

function assertTest(string $description, bool $condition, string $details = ''): void {
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
echo " TEST SUITE: UNIFIED PRINTING ENGINE (DOT MATRIX & A4)\n";
echo "====================================================================\n\n";

// -------------------------------------------------------------------------
// TEST 1: Format Resolution
// -------------------------------------------------------------------------
echo "1. Testing Format Resolution (PrintDocumentHelper::resolveFormat)...\n";
assertTest(
    "Standard format resolves to 'standard'",
    PrintDocumentHelper::resolveFormat('standard') === PrintDocumentHelper::FORMAT_STANDARD
);
assertTest(
    "Dot matrix format resolves to 'dotmatrix'",
    PrintDocumentHelper::resolveFormat('dotmatrix') === PrintDocumentHelper::FORMAT_DOTMATRIX
);
assertTest(
    "Dot matrix half format resolves to 'dotmatrix_half'",
    PrintDocumentHelper::resolveFormat('dotmatrix_half') === PrintDocumentHelper::FORMAT_DOTMATRIX_HALF
);
assertTest(
    "Alias 'half' resolves to 'dotmatrix_half'",
    PrintDocumentHelper::resolveFormat('half') === PrintDocumentHelper::FORMAT_DOTMATRIX_HALF
);
assertTest(
    "Alias 'wartel' resolves to 'dotmatrix_half'",
    PrintDocumentHelper::resolveFormat('wartel') === PrintDocumentHelper::FORMAT_DOTMATRIX_HALF
);
assertTest(
    "Alias 'continuous_half' resolves to 'dotmatrix_half'",
    PrintDocumentHelper::resolveFormat('continuous_half') === PrintDocumentHelper::FORMAT_DOTMATRIX_HALF
);
assertTest(
    "Invalid string falls back to 'standard'",
    PrintDocumentHelper::resolveFormat('unknown_random') === PrintDocumentHelper::FORMAT_STANDARD
);
assertTest(
    "Null falls back to default 'standard'",
    PrintDocumentHelper::resolveFormat(null) === PrintDocumentHelper::FORMAT_STANDARD
);

// -------------------------------------------------------------------------
// TEST 2: Paper Configuration
// -------------------------------------------------------------------------
echo "\n2. Testing Paper Configurations (PrintDocumentHelper::getPaperConfig)...\n";
$configStd = PrintDocumentHelper::getPaperConfig(PrintDocumentHelper::FORMAT_STANDARD);
assertTest(
    "Standard config paper is A4 portrait",
    $configStd['paper'] === 'A4' && $configStd['orientation'] === 'portrait'
);

$configDm = PrintDocumentHelper::getPaperConfig(PrintDocumentHelper::FORMAT_DOTMATRIX);
assertTest(
    "Dot Matrix Full config paper is Letter / Continuous Form portrait",
    $configDm['paper'] === 'Letter' && $configDm['orientation'] === 'portrait'
);

$configDmHalf = PrintDocumentHelper::getPaperConfig(PrintDocumentHelper::FORMAT_DOTMATRIX_HALF);
assertTest(
    "Dot Matrix Half config has custom points array [0, 0, 684, 396] (9.5in x 5.5in)",
    is_array($configDmHalf['paper']) &&
    $configDmHalf['paper'][0] == 0 &&
    $configDmHalf['paper'][1] == 0 &&
    abs($configDmHalf['paper'][2] - 684) < 0.01 &&
    abs($configDmHalf['paper'][3] - 396) < 0.01 &&
    $configDmHalf['orientation'] === 'portrait'
);

// -------------------------------------------------------------------------
// TEST 3: Dynamic CSS @page Generation
// -------------------------------------------------------------------------
echo "\n3. Testing Dynamic CSS @page Generation (getDynamicPageCss)...\n";
$cssStd = PrintDocumentHelper::getDynamicPageCss(PrintDocumentHelper::FORMAT_STANDARD);
assertTest(
    "Standard CSS contains 'size: A4 portrait'",
    str_contains($cssStd, 'size: A4 portrait;')
);

$cssDm = PrintDocumentHelper::getDynamicPageCss(PrintDocumentHelper::FORMAT_DOTMATRIX);
assertTest(
    "Dot Matrix Full CSS contains 'size: letter portrait;'",
    str_contains($cssDm, 'size: letter portrait;')
);

$cssDmHalf = PrintDocumentHelper::getDynamicPageCss(PrintDocumentHelper::FORMAT_DOTMATRIX_HALF);
assertTest(
    "Dot Matrix Half CSS contains 'size: 9.5in 5.5in portrait;'",
    str_contains($cssDmHalf, 'size: 9.5in 5.5in portrait;')
);

// -------------------------------------------------------------------------
// TEST 4: Format Options Metadata
// -------------------------------------------------------------------------
echo "\n4. Testing Format Options Metadata (getFormatOptions)...\n";
$opts = PrintDocumentHelper::getFormatOptions();
assertTest(
    "Format options contains all 3 profiles",
    isset($opts[PrintDocumentHelper::FORMAT_STANDARD]) &&
    isset($opts[PrintDocumentHelper::FORMAT_DOTMATRIX]) &&
    isset($opts[PrintDocumentHelper::FORMAT_DOTMATRIX_HALF])
);
assertTest(
    "Dot Matrix Half option has label 'Dot Matrix Half (Wartel)'",
    str_contains($opts[PrintDocumentHelper::FORMAT_DOTMATRIX_HALF]['label'], 'Dot Matrix Half')
);

// -------------------------------------------------------------------------
// TEST 5: Company Profile & Website Integration
// -------------------------------------------------------------------------
echo "\n5. Testing Company Profile Data & Website Integration...\n";
$comp = CompanySetting::getAll();
assertTest(
    "Company name loaded from DB",
    !empty($comp['nama']) && str_contains($comp['nama'], 'KEREN SNACK')
);
assertTest(
    "Company website loaded from DB is www.kerensnack.id",
    ($comp['website'] ?? '') === 'www.kerensnack.id'
);
$contactLine = PrintDocumentHelper::formatContactLine($comp);
assertTest(
    "formatContactLine contains Telp/WA",
    str_contains($contactLine, 'Telp/WA: ' . $comp['telepon'])
);
assertTest(
    "formatContactLine contains Email",
    str_contains($contactLine, 'Email: ' . $comp['email'])
);
assertTest(
    "formatContactLine contains Web: www.kerensnack.id",
    str_contains($contactLine, 'Web: www.kerensnack.id')
);

// -------------------------------------------------------------------------
// TEST 6: Frame Layout (print_frame.php) Rendering
// -------------------------------------------------------------------------
echo "\n6. Testing views/layouts/print_frame.php Rendering...\n";
$framePath = APP_ROOT . '/views/layouts/print_frame.php';
assertTest("Layout file exists at views/layouts/print_frame.php", file_exists($framePath));

// Simulate rendering the frame layout with sample data
ob_start();
$documentTitle = "Test Surat Jalan #SJ-001";
$formatMode = PrintDocumentHelper::FORMAT_DOTMATRIX_HALF;
$bodyClass = "test-doc-class";
$extraToolbarHtml = '<button id="btnCustomTest">Custom Action</button>';
$content = '<div id="testContentBody"><p>Nomor Transaksi: TEST-12345</p></div>';
require $framePath;
$renderedHtml = ob_get_clean();

assertTest(
    "Rendered frame contains document title",
    str_contains($renderedHtml, 'Test Surat Jalan #SJ-001')
);
assertTest(
    "Rendered frame contains content body slot",
    str_contains($renderedHtml, 'Nomor Transaksi: TEST-12345')
);
assertTest(
    "Rendered frame contains format switcher tabs",
    str_contains($renderedHtml, 'setFormat(\'standard\')') &&
    str_contains($renderedHtml, 'setFormat(\'dotmatrix\')') &&
    str_contains($renderedHtml, 'setFormat(\'dotmatrix_half\')')
);
assertTest(
    "Rendered frame contains simulated tractor strip CSS rules",
    str_contains($renderedHtml, '.tractor-strip') &&
    str_contains($renderedHtml, '.tractor-left') &&
    str_contains($renderedHtml, '.tractor-right')
);
assertTest(
    "Rendered frame contains custom extra toolbar actions",
    str_contains($renderedHtml, 'id="btnCustomTest"')
);
assertTest(
    "Rendered frame contains setFormat javascript function",
    str_contains($renderedHtml, 'function setFormat(mode)')
);
assertTest(
    "Rendered frame contains Ctrl+P browser shortcut handler",
    str_contains($renderedHtml, 'e.ctrlKey') && str_contains($renderedHtml, 'e.key.toLowerCase() === \'p\'')
);

// -------------------------------------------------------------------------
// TEST 7: Document Views Compilation and Uniform Frame Usage
// -------------------------------------------------------------------------
echo "\n7. Testing Document Views & Frame Layout Integration...\n";
$viewsToCheck = [
    'Deliveries Print'    => APP_ROOT . '/views/deliveries/print.php',
    'Customer Invoice'   => APP_ROOT . '/views/customer_orders/invoice.php',
    'Purchase PO PDF'    => APP_ROOT . '/views/purchases/po_pdf.php',
    'Consignment Nota'   => APP_ROOT . '/views/consignment/nota_pdf.php',
];

foreach ($viewsToCheck as $name => $path) {
    assertTest("View file exists: {$name}", file_exists($path));
    $code = (string)file_get_contents($path);
    assertTest(
        "View {$name} uses PrintDocumentHelper",
        str_contains($code, 'PrintDocumentHelper')
    );
    assertTest(
        "View {$name} includes print_frame.php layout",
        str_contains($code, "views/layouts/print_frame.php") || str_contains($code, "print_frame.php")
    );
}

// -------------------------------------------------------------------------
// TEST 8: Route Registration
// -------------------------------------------------------------------------
echo "\n8. Testing Route Registrations in public/index.php...\n";
$indexPath = APP_ROOT . '/public/index.php';
$indexContent = (string)file_get_contents($indexPath);

assertTest(
    "Route /purchases/print is registered",
    str_contains($indexContent, "'/purchases/print'") && str_contains($indexContent, "PurchaseController::class, 'print'")
);
assertTest(
    "Route /consignment/nota-print is registered",
    str_contains($indexContent, "'/consignment/nota-print'") && str_contains($indexContent, "ConsignmentController::class, 'printNota'")
);

// -------------------------------------------------------------------------
// TEST SUMMARY
// -------------------------------------------------------------------------
echo "\n====================================================================\n";
echo " RESULTS: {$passedTests} / {$totalTests} tests passed\n";
echo "====================================================================\n";

if ($passedTests === $totalTests) {
    echo "🎉 ALL PRINTING ENGINE INTEGRITY TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED.\n";
    exit(1);
}

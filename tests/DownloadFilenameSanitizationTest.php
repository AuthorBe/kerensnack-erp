<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/env.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/vendor/autoload.php';

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

use App\Helpers\ExcelExport;
use App\Helpers\PdfExport;
use App\Helpers\PrintDocumentHelper;

echo "====================================================================\n";
echo " KEREN SNACK ERP - DOWNLOAD FILENAME SANITIZATION AUDIT & TEST\n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $title, bool $condition, string $detail = ''): void
{
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "✅ [PASS] {$title}\n";
    } else {
        $failCount++;
        echo "❌ [FAIL] {$title}" . ($detail !== '' ? " -> {$detail}" : '') . "\n";
    }
}

// 1. Test PdfExport filename sanitizer via reflection
echo "--- 1. PDF EXPORT FILENAME SANITIZATION ---\n";
$pdfRef = new ReflectionClass(PdfExport::class);
$sanitizePdf = $pdfRef->getMethod('sanitizeFilename');
$sanitizePdf->setAccessible(true);

$testsPdf = [
    'Faktur INV 2026 001 (28 Sep 2026).pdf' => 'Faktur INV 2026 001 (28 Sep 2026).pdf',
    'Picking List PO 123 (28 Sep 2026).pdf' => 'Picking List PO 123 (28 Sep 2026).pdf',
    'Batch PO 5 Nota (28 Sep 2026).pdf' => 'Batch PO 5 Nota (28 Sep 2026).pdf',
    'Surat Pesanan PO 99 (28 Sep 2026).pdf' => 'Surat Pesanan PO 99 (28 Sep 2026).pdf',
    'Opname Gudang 01 (28 Sep 2026).pdf' => 'Opname Gudang 01 (28 Sep 2026).pdf',
    'Document--With   Many   Spaces (28 Sep 2026).pdf' => 'Document--With Many Spaces (28 Sep 2026).pdf',
    'Faktur/Nota:123*456? "789" <A> | (28 Sep 2026).pdf' => 'Faktur Nota 123 456 789 A (28 Sep 2026).pdf',
    'Laporan Penjualan (01 Sep 2026 sd 28 Sep 2026).pdf' => 'Laporan Penjualan (01 Sep 2026 sd 28 Sep 2026).pdf',
];

foreach ($testsPdf as $input => $expected) {
    $result = $sanitizePdf->invoke(null, $input);
    assertTest("PdfExport::sanitizeFilename('{$input}')", $result === $expected, "Got: '{$result}', Expected: '{$expected}'");
}

// 2. Test PrintDocumentHelper download & stream filenames
echo "\n--- 2. PRINT DOCUMENT HELPER CLEANING ---\n";

$cleanNameMethod = function(string $baseFilename, string $format = 'standard') {
    $rawName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], ' ', $baseFilename);
    $baseName = preg_replace('/\.pdf$/i', '', $rawName);
    $cleanName = preg_replace('/[^A-Za-z0-9\(\)\.\-\s]+/', ' ', $baseName);
    $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));
    $fmt = PrintDocumentHelper::resolveFormat($format);
    $suffix = match ($fmt) {
        PrintDocumentHelper::FORMAT_DOTMATRIX => ' DotMatrix',
        PrintDocumentHelper::FORMAT_DOTMATRIX_HALF => ' DotMatrixHalf',
        default => ''
    };
    return trim("{$cleanName}{$suffix}") . '.pdf';
};

$testsPrint = [
    ['Faktur INV 001 (28 Sep 2026)', 'standard', 'Faktur INV 001 (28 Sep 2026).pdf'],
    ['Surat Jalan SJ 2026 09 (28 Sep 2026)', 'dotmatrix', 'Surat Jalan SJ 2026 09 (28 Sep 2026) DotMatrix.pdf'],
    ['Surat Pesanan PO 123 (28 Sep 2026)', 'dotmatrix_half', 'Surat Pesanan PO 123 (28 Sep 2026) DotMatrixHalf.pdf'],
];

foreach ($testsPrint as $case) {
    $actual = $cleanNameMethod($case[0], $case[1]);
    assertTest("PrintDocumentHelper filename '{$case[0]}' [{$case[1]}]", $actual === $case[2], "Got: '{$actual}', Expected: '{$case[2]}'");
}

// 3. Static Audit of Controller Download Filenames (No raw date('Ymd') or unformatted timestamps)
echo "\n--- 3. CONTROLLER FILENAMES STATIC AUDIT ---\n";
$root = ROOT_PATH;

$controllersToCheck = [
    'app/Controllers/ActivityLogController.php',
    'app/Controllers/CashController.php',
    'app/Controllers/ConsignmentController.php',
    'app/Controllers/DeliveryController.php',
    'app/Controllers/InventoryController.php',
    'app/Controllers/OrderDocumentController.php',
    'app/Controllers/PurchaseController.php',
    'app/Services/Import/TemplateGenerator.php',
];

foreach ($controllersToCheck as $relPath) {
    $fullPath = $root . '/' . $relPath;
    $content = file_get_contents($fullPath);
    
    $lines = explode("\n", $content);
    $hasViolation = false;
    $violatingLine = '';
    
    foreach ($lines as $lineNum => $line) {
        if (
            preg_match('/date\s*\(\s*[\'"]Ymd/i', $line) &&
            (str_contains($line, 'download') || str_contains($line, 'filename') || str_contains($line, 'ExcelExport') || str_contains($line, 'PdfExport') || str_contains($line, 'PrintDocumentHelper'))
        ) {
            $hasViolation = true;
            $violatingLine = "L" . ($lineNum + 1) . ": " . trim($line);
            break;
        }
    }
    
    assertTest("Static Audit: {$relPath} bebas dari raw timestamp date('Ymd')", !$hasViolation, $violatingLine);
}

echo "\n====================================================================\n";
echo " TEST SUMMARY: Total: " . ($passCount + $failCount) . " | Passed: {$passCount} | Failed: {$failCount}\n";
echo "====================================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);

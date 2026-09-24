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
    'Faktur-INV-2026-001.pdf' => 'Faktur INV 2026 001.pdf',
    'PickingList-PO_123_456.pdf' => 'PickingList PO 123 456.pdf',
    'Batch_PO_5Nota_20260924_1200.pdf' => 'Batch PO 5Nota 20260924 1200.pdf',
    'SURAT_PESANAN_PO-99.pdf' => 'SURAT PESANAN PO 99.pdf',
    'Opname-Gudang-01.pdf' => 'Opname Gudang 01.pdf',
    'Document--With___Many---Dashes.pdf' => 'Document With Many Dashes.pdf',
    'Faktur/Nota#123_456-789.pdf' => 'Faktur Nota 123 456 789.pdf',
    'Laporan-Penjualan_2026-09-24_Final-Banget---v1.pdf' => 'Laporan Penjualan 2026 09 24 Final Banget v1.pdf',
];

foreach ($testsPdf as $input => $expected) {
    $result = $sanitizePdf->invoke(null, $input);
    assertTest("PdfExport::sanitizeFilename('{$input}')", $result === $expected, "Got: '{$result}', Expected: '{$expected}'");
}

// 2. Test PrintDocumentHelper download & stream filenames
echo "\n--- 2. PRINT DOCUMENT HELPER CLEANING ---\n";

$cleanNameMethod = function(string $baseFilename, string $format = 'standard') {
    $cleanName = preg_replace('/[\-_]+/', ' ', $baseFilename);
    $cleanName = preg_replace('/[^A-Za-z0-9 ]+/', ' ', $cleanName);
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
    ['Faktur-INV-001', 'standard', 'Faktur INV 001.pdf'],
    ['SuratJalan-SJ_2026_09', 'dotmatrix', 'SuratJalan SJ 2026 09 DotMatrix.pdf'],
    ['SURAT_PESANAN_PO-123', 'dotmatrix_half', 'SURAT PESANAN PO 123 DotMatrixHalf.pdf'],
];

foreach ($testsPrint as $case) {
    $actual = $cleanNameMethod($case[0], $case[1]);
    assertTest("PrintDocumentHelper filename '{$case[0]}' [{$case[1]}]", $actual === $case[2], "Got: '{$actual}', Expected: '{$case[2]}'");
}

// 3. Static Audit of Controller Download Filenames
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
    'views/settings/impor_data/index.php'
];

foreach ($controllersToCheck as $relPath) {
    $fullPath = $root . '/' . $relPath;
    $content = file_get_contents($fullPath);
    
    // Check for any remaining dash/underscore patterns in download filename arguments
    $lines = explode("\n", $content);
    $hasViolation = false;
    $violatingLine = '';
    
    foreach ($lines as $lineNum => $line) {
        if (
            preg_match('/ExcelExport::download\s*\(\s*["\']([^"\']+)["\']/i', $line, $m) ||
            preg_match('/PdfExport::download\s*\([^,]+,\s*["\']([^"\']+)["\']/i', $line, $m) ||
            preg_match('/PrintDocumentHelper::downloadPdf\s*\([^,]+,\s*["\']([^"\']+)["\']/i', $line, $m)
        ) {
            $fn = $m[1];
            if (preg_match('/[-_]/', $fn)) {
                $hasViolation = true;
                $violatingLine = "L" . ($lineNum + 1) . ": " . trim($line);
                break;
            }
        }
    }
    
    assertTest("Static Audit: {$relPath} bebas dari '-' dan '_'", !$hasViolation, $violatingLine);
}

echo "\n====================================================================\n";
echo " TEST SUMMARY: Total: " . ($passCount + $failCount) . " | Passed: {$passCount} | Failed: {$failCount}\n";
echo "====================================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);

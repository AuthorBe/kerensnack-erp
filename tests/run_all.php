<?php
declare(strict_types=1);

/**
 * tests/run_all.php
 * Keren Snack ERP - Automated Test Suite Runner
 * 
 * Usage:
 *   php tests/run_all.php
 */

$phpBinary = PHP_BINARY;
$testDir = __DIR__;

$testSuites = [
    'SalesDriverIntegrityTest.php'        => 'Sales vs Driver Role & Guard Integrity',
    'CustomerIntegrityTest.php'           => 'Customer Master Data & POS Protections',
    'ConsignmentConversionTest.php'       => 'Consignment Shelf Stock & Conversion',
    'MasterDataCoreTest.php'              => 'Master Data Core & Unique Validations',
    'PricingAndStockIntegrationTest.php'  => 'Pricing Matrix & Stock Synchronization',
    'MasterRelationIntegrityTest.php'     => 'Master Entity Relational Integrity',
    'DataHygieneAndSettingsTest.php'      => 'Company Settings & Supplier Hygiene',
    'SecurityAndReconciliationTest.php'   => 'CSRF Security & Account Reconciliation',
    'InventoryAndLedgerPrecisionTest.php' => 'Inventory & Numerical Ledger Precision',
    'PricingSystemReconciliationTest.php' => 'Pricing System & Master Price Levels',
    'SupplierMasterUpgradeTest.php'       => 'Supplier Master Upgrade & Bank Ledger',
    'ProductMasterModuleTest.php'         => 'Product Master, BOM & Single Level 1 Default',
    'TieredCommissionTest.php'            => 'Tiered Sales Commission & Thresholds',
];

echo "====================================================================\n";
echo " KEREN SNACK ERP - AUTOMATED TEST SUITE RUNNER\n";
echo "====================================================================\n\n";

$results = [];
$allPassed = true;
$startTimeTotal = microtime(true);

foreach ($testSuites as $file => $description) {
    $filePath = $testDir . DIRECTORY_SEPARATOR . $file;
    if (!file_exists($filePath)) {
        echo "⚠️  [SKIP] {$file} (File not found)\n";
        continue;
    }

    echo "▶️  Running {$file} ({$description})...\n";
    $startTime = microtime(true);

    $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($filePath);
    $output = [];
    $returnVar = 0;
    exec($command, $output, $returnVar);

    $duration = round(microtime(true) - $startTime, 2);
    $isPass = ($returnVar === 0);

    if ($isPass) {
        echo "   ✅ PASSED ({$duration}s)\n\n";
        $results[] = ['file' => $file, 'desc' => $description, 'status' => 'PASS', 'time' => $duration];
    } else {
        echo "   ❌ FAILED ({$duration}s)\n\n";
        $results[] = ['file' => $file, 'desc' => $description, 'status' => 'FAIL', 'time' => $duration];
        $allPassed = false;
    }
}

$totalDuration = round(microtime(true) - $startTimeTotal, 2);

echo "====================================================================\n";
echo " TEST SUITE EXECUTION SUMMARY\n";
echo "====================================================================\n";
printf("%-36s | %-6s | %-8s\n", "Test Suite", "Status", "Duration");
echo str_repeat("-", 68) . "\n";

foreach ($results as $res) {
    $icon = $res['status'] === 'PASS' ? '✅ PASS' : '❌ FAIL';
    printf("%-36s | %-6s | %6.2fs\n", $res['file'], $icon, $res['time']);
}

echo str_repeat("-", 68) . "\n";
echo "Total Execution Time: {$totalDuration}s\n";

if ($allPassed) {
    echo "🎉 ALL TEST SUITES PASSED (100%)! Codebase is healthy.\n";
    exit(0);
} else {
    echo "⚠️ SOME TEST SUITES FAILED. Please review the errors above.\n";
    exit(1);
}

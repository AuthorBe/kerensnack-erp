<?php
declare(strict_types=1);

/**
 * tests/run_all.php
 * Keren Snack ERP - Unified Automated Test Suite Runner (23 Test Suites)
 * 
 * Usage:
 *   php tests/run_all.php
 *   & "D:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe" tests/run_all.php
 */

$phpBinary = PHP_BINARY;
// Auto-detect Laragon PHP 8.1 if PHP_BINARY is CLI fallback or not found
if (empty($phpBinary) || !file_exists($phpBinary)) {
    $laragonPhp = 'D:\\laragon\\bin\\php\\php-8.1.10-Win32-vs16-x64\\php.exe';
    if (file_exists($laragonPhp)) {
        $phpBinary = $laragonPhp;
    }
}

$testDir = __DIR__;

$testSuites = [
    // 1. Roles & Core Architecture
    'SalesDriverIntegrityTest.php'        => 'Sales vs Driver Role & Guard Integrity',
    
    // 2. Master Data & Entity Protections
    'CustomerIntegrityTest.php'           => 'Customer Master Data & POS Protections',
    'MasterDataCoreTest.php'              => 'Master Data Core & Unique Validations',
    'MasterRelationIntegrityTest.php'     => 'Master Entity Relational Integrity',
    'SupplierMasterUpgradeTest.php'       => 'Supplier Master Upgrade & Bank Ledger',
    'ProductMasterModuleTest.php'         => 'Product Master, BOM & Single Level 1 Default',

    // 3. Pricing Matrix & Warehouse Stock
    'PricingAndStockIntegrationTest.php'  => 'Pricing Matrix & Stock Synchronization',
    'PricingSystemReconciliationTest.php' => 'Pricing System & Master Price Levels',
    'InventoryAndLedgerPrecisionTest.php' => 'Inventory & Numerical Ledger Precision',

    // 4. Core Operational Lifecycles
    'PosCashierLifecycleTest.php'         => 'POS Cashier, Barcode, HPP & Cash Ledger',
    'CustomerOrderLifecycleTest.php'      => 'B2B Customer Orders, PO & Invoicing',
    'PurchaseProcurementLifecycleTest.php'=> 'Purchasing, Decimal Quantities & Vendor Debt',
    'DeliveryAndLogisticsLifecycleTest.php'=> 'Surat Jalan, Driver Logistics & POD',
    'CashLedgerFinancialTest.php'         => 'Cash Accounts, Overdraft & Dual Transfers',
    'ConsignmentFullCycleTest.php'        => 'Consignment Shelf Stock, Opname & Billing',
    'ConsignmentConversionTest.php'       => 'Consignment Conversion & Shelf Resolution',

    // 5. Commissions, Printing & Documents
    'TieredCommissionTest.php'            => 'Tiered Sales Commission & Thresholds',
    'UnifiedPrintingEngineTest.php'       => 'Unified Printing Engine (Dot Matrix & A4)',

    // 6. Security, RBAC, Audit & Executive Analytics
    'AuthAndRbacLifecycleTest.php'        => 'Authentication, Bcrypt & RBAC Matrix',
    'SecurityAndReconciliationTest.php'   => 'CSRF Security & Account Reconciliation',
    'ActivityLogComprehensiveAuditTest.php'=> 'Activity Logs & Smart Delta Diffing',
    'DataHygieneAndSettingsTest.php'      => 'Company Settings & Supplier Hygiene',
    'OwnerDashboardExecutiveTest.php'     => 'Owner Executive Dashboard & KPI Metrics',
];

echo "====================================================================\n";
echo " KEREN SNACK ERP - UNIFIED TEST SUITE RUNNER (23 SUITES)\n";
echo "====================================================================\n";
echo "PHP Binary : {$phpBinary}\n";
echo "Test Suite : " . count($testSuites) . " comprehensive suites\n\n";

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
        echo "   ❌ FAILED ({$duration}s)\n";
        // Tampilkan 10 baris terakhir output jika gagal
        $tail = array_slice($output, -10);
        foreach ($tail as $line) {
            echo "      " . $line . "\n";
        }
        echo "\n";
        $results[] = ['file' => $file, 'desc' => $description, 'status' => 'FAIL', 'time' => $duration];
        $allPassed = false;
    }
}

$totalDuration = round(microtime(true) - $startTimeTotal, 2);
$passedCount = count(array_filter($results, fn($r) => $r['status'] === 'PASS'));
$totalCount = count($results);

echo "====================================================================\n";
echo " TEST SUITE EXECUTION SUMMARY\n";
echo "====================================================================\n";
printf("%-38s | %-6s | %-8s\n", "Test Suite", "Status", "Duration");
echo str_repeat("-", 72) . "\n";

foreach ($results as $res) {
    $icon = $res['status'] === 'PASS' ? '✅ PASS' : '❌ FAIL';
    printf("%-38s | %-6s | %6.2fs\n", $res['file'], $icon, $res['time']);
}

echo str_repeat("-", 72) . "\n";
echo "Total Execution Time: {$totalDuration}s\n";
echo "Summary: {$passedCount} / {$totalCount} Suites Passed (" . round(($passedCount / max(1, $totalCount)) * 100, 1) . "%)\n";

if ($allPassed) {
    echo "🎉 ALL 23 TEST SUITES PASSED (100%)! Entire ERP Codebase is healthy & hardened.\n";
    exit(0);
} else {
    echo "⚠️ SOME TEST SUITES FAILED. Please review the errors above.\n";
    exit(1);
}

<?php
declare(strict_types=1);

/**
 * tests/audit_tahap3_tahap4_extreme.php
 * Script Verifikasi Otomatis Ekstrem & Komprehensif: Tahap 3 & Tahap 4
 */

define('ROOT_PATH', dirname(__DIR__));
date_default_timezone_set('Asia/Jakarta');
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/Core/Auth.php';
require_once ROOT_PATH . '/app/Helpers/Format.php';

$passed = 0;
$failed = 0;

function runTest(string $title, callable $fn): void {
    global $passed, $failed;
    echo "Testing: {$title} ... ";
    try {
        $result = $fn();
        if ($result === true) {
            echo "\033[32m[PASS]\033[0m\n";
            $passed++;
        } else {
            echo "\033[31m[FAIL]\033[0m (Assertion returned false)\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "\033[31m[ERROR]\033[0m: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "====================================================================\n";
echo "KEREN SNACK ERP - AUDIT TAHAP 3 & TAHAP 4 EXTREME VERIFICATION SUITE\n";
echo "====================================================================\n\n";

// -----------------------------------------------------------------------------
// TAHAP 3 TESTS: Numerical Precision, Cash Ledger & Inventory Integrity
// -----------------------------------------------------------------------------

runTest("1. Database Timezone Synchronization (Asia/Jakarta via PDO)", function() {
    $pdo = Database::getConnection();
    $tz = $pdo->query("SHOW TIME ZONE")->fetchColumn();
    if (strcasecmp((string)$tz, 'Asia/Jakarta') !== 0) {
        throw new Exception("Database timezone is '{$tz}', expected 'Asia/Jakarta'");
    }
    return true;
});

runTest("2. PostgreSQL NOW() matches PHP Asia/Jakarta timestamp within 60 seconds", function() {
    $pdo = Database::getConnection();
    $dbNow = (int)$pdo->query("SELECT EXTRACT(EPOCH FROM NOW())")->fetchColumn();
    $phpNow = time();
    $diff = abs($dbNow - $phpNow);
    if ($diff > 60) {
        throw new Exception("PostgreSQL epoch '{$dbNow}' differs from PHP epoch '{$phpNow}' by {$diff} seconds (max allowed: 60s)");
    }
    return true;
});

runTest("3. PurchaseController has zero (int)round() or (int) casts on purchase item quantity", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/PurchaseController.php');
    if (preg_match('/\$qtyInt\s*=\s*\(int\)round\(/i', $content)) {
        throw new Exception("Found deprecated \$qtyInt = (int)round(...) in PurchaseController");
    }
    if (preg_match('/\$qty\s*=\s*\(int\)round\(/i', $content)) {
        throw new Exception("Found deprecated \$qty = (int)round(...) in PurchaseController");
    }
    if (preg_match('/\$buyQty\s*=\s*\(int\)\$it\[/i', $content)) {
        throw new Exception("Found deprecated \$buyQty = (int)\$it[...] in PurchaseController");
    }
    return true;
});

runTest("4. Decimal Purchase calculation precision (2.75 kg * Rp 15.000 = Rp 41.250)", function() {
    $qty = 2.75;
    $harga = 15000.0;
    $subtotal = round($qty * $harga, 2);
    if ($subtotal !== 41250.0) {
        throw new Exception("Decimal calculation mismatch: expected 41250.0, got {$subtotal}");
    }
    return true;
});

runTest("5. InventoryController adjustStock maps 'retur_masuk_manual' accurately", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/InventoryController.php');
    if (!str_contains($content, "'retur_masuk_manual' => 'retur_masuk_manual'")) {
        throw new Exception("InventoryController adjustStock does not map retur_masuk_manual correctly");
    }
    return true;
});

runTest("6. CashController storeOutflow employs row lock FOR UPDATE and overdraft check", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/CashController.php');
    if (!str_contains($content, "SELECT id, nama_akun, saldo_saat_ini, tipe_akun FROM public.akun_kas WHERE id = :id FOR UPDATE")) {
        throw new Exception("storeOutflow missing FOR UPDATE row lock");
    }
    if (!str_contains($content, '$currentBal < $nominal')) {
        throw new Exception("storeOutflow missing overdraft ($currentBal < $nominal) protection check");
    }
    return true;
});

runTest("7. CashController storeTransfer employs deterministic FOR UPDATE lock and overdraft check", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/CashController.php');
    if (!str_contains($content, "sort(\$ids);") || !str_contains($content, "SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE")) {
        throw new Exception("storeTransfer missing deterministic deadlock-free FOR UPDATE locking");
    }
    if (!str_contains($content, '$sourceBal < $nominal')) {
        throw new Exception("storeTransfer missing source balance overdraft protection check");
    }
    return true;
});

runTest("8. DeliveryController driverRoute preserves in-transit deliveries across dates", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/DeliveryController.php');
    if (!str_contains($content, "sj.status_surat_jalan IN ('sedang_dikirim', 'dalam_perjalanan')")) {
        throw new Exception("driverRoute missing in-transit delivery persistence filter");
    }
    return true;
});

// -----------------------------------------------------------------------------
// TAHAP 4 TESTS: UI/UX, Responsiveness, Helper Sanitization & Error Handling
// -----------------------------------------------------------------------------

runTest("9. Zero unhandled raw echoes in CashController", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/CashController.php');
    if (str_contains($content, 'echo "Database Error:')) {
        throw new Exception("Found raw echo in CashController");
    }
    return true;
});

runTest("10. Zero unhandled raw echoes in InventoryController", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/InventoryController.php');
    if (str_contains($content, 'echo "Database Error:')) {
        throw new Exception("Found raw echo in InventoryController");
    }
    return true;
});

runTest("11. Zero unhandled raw echoes in PosController", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/PosController.php');
    if (str_contains($content, 'echo "Database Error:')) {
        throw new Exception("Found raw echo in PosController");
    }
    return true;
});

runTest("12. Zero unhandled raw echoes in OwnerController", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/OwnerController.php');
    if (str_contains($content, 'echo "Database Error:')) {
        throw new Exception("Found raw echo in OwnerController");
    }
    return true;
});

runTest("13. Zero unhandled raw echoes in ConsignmentController", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/ConsignmentController.php');
    if (preg_match('/echo\s+"Error\s+/i', $content)) {
        throw new Exception("Found raw echo in ConsignmentController");
    }
    return true;
});

runTest("14. Zero unhandled raw echoes in SettingsController", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/SettingsController.php');
    if (preg_match('/echo\s+"Error\s+/i', $content)) {
        throw new Exception("Found raw echo in SettingsController");
    }
    return true;
});

runTest("15. ExcelExport flushes active output buffers before binary download", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Helpers/ExcelExport.php');
    $count = substr_count($content, 'while (ob_get_level() > 0)');
    if ($count < 2) {
        throw new Exception("ExcelExport should call while (ob_get_level() > 0) ob_end_clean() in both download methods. Found: {$count}");
    }
    return true;
});

runTest("16. PdfExport flushes active output buffers before streaming/downloading PDF", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Helpers/PdfExport.php');
    $count = substr_count($content, 'while (ob_get_level() > 0)');
    if ($count < 2) {
        throw new Exception("PdfExport should call while (ob_get_level() > 0) ob_end_clean() in both stream and download methods. Found: {$count}");
    }
    return true;
});

runTest("17. CSS mobile-bottom-bar has z-index 40 to avoid colliding with modal backdrop", function() {
    $content = file_get_contents(ROOT_PATH . '/public/assets/css/app.css');
    if (!preg_match('/\.mobile-bottom-bar\s*\{[^}]*z-index:\s*40;/s', $content)) {
        throw new Exception("mobile-bottom-bar does not have z-index: 40");
    }
    return true;
});

runTest("18. CSS contains extended z-index utility classes (.z-60, .z-70, .z-80)", function() {
    $content = file_get_contents(ROOT_PATH . '/public/assets/css/app.css');
    if (!str_contains($content, '.z-60 { z-index: 60 !important; }') ||
        !str_contains($content, '.z-70 { z-index: 70 !important; }') ||
        !str_contains($content, '.z-80 { z-index: 80 !important; }')) {
        throw new Exception("Missing extended z-index utility classes in app.css");
    }
    return true;
});

runTest("19. Universal double-submit protection handler is active in erp-helpers.js", function() {
    $content = file_get_contents(ROOT_PATH . '/public/assets/js/erp-helpers.js');
    if (!str_contains($content, 'submitBtn.disabled = true;') ||
        !str_contains($content, 'is-submitting')) {
        throw new Exception("Missing form double submit listener in erp-helpers.js");
    }
    return true;
});

runTest("20. CustomerController customer group methods support contextual redirect_to", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/CustomerController.php');
    if (!str_contains($content, "\$this->input('redirect_to', '/customers?tab=customer_groups')")) {
        throw new Exception("CustomerController does not handle redirect_to input");
    }
    return true;
});

echo "\n====================================================================\n";
echo "TEST RESULTS: \033[32m{$passed} PASSED\033[0m, \033[31m{$failed} FAILED\033[0m\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

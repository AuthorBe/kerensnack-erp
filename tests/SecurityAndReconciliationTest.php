<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/env.php';
require_once ROOT_PATH . '/config/database.php';

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

$passCount = 0;
$failCount = 0;

function assertTest(string $title, bool $condition, string $details = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "✅ [PASS] {$title}" . ($details ? " ({$details})" : "") . PHP_EOL;
    } else {
        $failCount++;
        echo "❌ [FAIL] {$title}" . ($details ? " - {$details}" : "") . PHP_EOL;
    }
}

echo "========================================================\n";
echo "AUDIT EKSTREM TAHAP 1 & TAHAP 2 - KEREN SNACK ERP\n";
echo "========================================================\n\n";

// --- 1. CSRF TEST ---
$token = \App\Helpers\CSRF::token();
assertTest("CSRF::token() menghasilkan hex 64 karakter", strlen($token) === 64);
assertTest("CSRF::validate() menolak token kosong", !\App\Helpers\CSRF::validate(''));
assertTest("CSRF::validate() menolak token salah", !\App\Helpers\CSRF::validate('invalid_token_12345'));
assertTest("CSRF::validate() menerima token valid", \App\Helpers\CSRF::validate($token));

$_POST['csrf_token'] = $token;
assertTest("CSRF::validate() mendeteksi \$_POST['csrf_token']", \App\Helpers\CSRF::validate());
unset($_POST['csrf_token']);

$_POST['_csrf_token'] = $token;
assertTest("CSRF::validate() mendeteksi \$_POST['_csrf_token']", \App\Helpers\CSRF::validate());
unset($_POST['_csrf_token']);

$_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
assertTest("CSRF::validate() mendeteksi HTTP_X_CSRF_TOKEN", \App\Helpers\CSRF::validate());
unset($_SERVER['HTTP_X_CSRF_TOKEN']);

$_SERVER['HTTP_X_XSRF_TOKEN'] = $token;
assertTest("CSRF::validate() mendeteksi HTTP_X_XSRF_TOKEN", \App\Helpers\CSRF::validate());
unset($_SERVER['HTTP_X_XSRF_TOKEN']);

// --- 2. DATABASE RECONCILIATION ---
try {
    $res = Database::fetchOne("SELECT public.fn_rekonsiliasi_piutang_pelanggan(NULL) as json_res");
    $data = json_decode($res['json_res'] ?? '{}', true);
    assertTest(
        "RPC public.fn_rekonsiliasi_piutang_pelanggan berjalan sukses",
        ($data['success'] ?? false) === true,
        "mode=" . ($data['mode'] ?? '') . ", terupdate=" . ($data['pelanggan_terupdate'] ?? 0) . ", total=Rp " . number_format((float)($data['total_piutang_nasional'] ?? 0), 2)
    );

    $negatives = Database::fetchOne("SELECT COUNT(*) as cnt FROM public.pelanggan WHERE total_piutang_berjalan < 0");
    assertTest("Zero negative customer receivables", (int)($negatives['cnt'] ?? 0) === 0);

    $sumPiutang = Database::fetchOne("SELECT COALESCE(SUM(total_piutang_berjalan), 0) as total FROM public.pelanggan");
    assertTest(
        "Total piutang nasional konsisten dengan agregat pelanggan",
        (float)$sumPiutang['total'] === (float)($data['total_piutang_nasional'] ?? 0),
        "Rp " . number_format((float)$sumPiutang['total'], 2)
    );
} catch (\Throwable $e) {
    assertTest("RPC rekonsiliasi piutang", false, $e->getMessage());
}

// --- 3. CONTROLLER STATIC AUDIT ---
$cCustomerOrder = file_get_contents(ROOT_PATH . '/app/Controllers/CustomerOrderController.php');
$cDelivery = file_get_contents(ROOT_PATH . '/app/Controllers/DeliveryController.php');
$cPurchase = file_get_contents(ROOT_PATH . '/app/Controllers/PurchaseController.php');
$cOrderDoc = file_get_contents(ROOT_PATH . '/app/Controllers/OrderDocumentController.php');
$cCustomer = file_get_contents(ROOT_PATH . '/app/Controllers/CustomerController.php');
$cProduct = file_get_contents(ROOT_PATH . '/app/Controllers/ProductController.php');
$cSupplier = file_get_contents(ROOT_PATH . '/app/Controllers/SupplierController.php');
$cPricing = file_get_contents(ROOT_PATH . '/app/Controllers/PricingController.php');
$cEmployee = file_get_contents(ROOT_PATH . '/app/Controllers/EmployeeController.php');
$cRouter = file_get_contents(ROOT_PATH . '/app/Core/Router.php');

// PESSIMISTIC LOCKING
assertTest("CustomerOrderController::pay mengunci pesanan FOR UPDATE", str_contains($cCustomerOrder, "SELECT * FROM public.pesanan WHERE id = :id FOR UPDATE"));
assertTest("CustomerOrderController::pay mengunci akun_kas FOR UPDATE", str_contains($cCustomerOrder, "FOR UPDATE"));
assertTest("CustomerOrderController::pay memotong total_piutang_berjalan", str_contains($cCustomerOrder, "GREATEST(0, COALESCE(total_piutang_berjalan, 0) - :nominal_bayar)"));

assertTest("DeliveryController::completeDelivery mengunci surat_jalan FOR UPDATE", str_contains($cDelivery, "SELECT status_surat_jalan FROM public.surat_jalan WHERE id = :id FOR UPDATE"));
assertTest("DeliveryController::updateStatus mengunci surat_jalan FOR UPDATE", str_contains($cDelivery, "SELECT id, status_surat_jalan, nomor_surat_jalan, pesanan_id FROM public.surat_jalan WHERE id = :id FOR UPDATE"));
assertTest("DeliveryController::updateStatus mengunci pesanan FOR UPDATE", str_contains($cDelivery, "WHERE p.id = :id FOR UPDATE"));
assertTest("DeliveryController::completeDelivery menambah piutang tempo", str_contains($cDelivery, "SET total_piutang_berjalan = COALESCE(total_piutang_berjalan, 0) + :sisa"));

assertTest("PurchaseController::receiveGoods mengunci pembelian FOR UPDATE", str_contains($cPurchase, "SELECT * FROM public.pembelian WHERE id = :id FOR UPDATE"));
assertTest("PurchaseController::receiveGoods mendukung kuantitas desimal float", str_contains($cPurchase, "round((float)("));

// IDOR & SALES SCOPING
assertTest("CustomerOrderController::invoice memeriksa scoping sales binaan", str_contains($cCustomerOrder, "\$order['sales_driver_id'] !== \$myEmpId && (\$order['pelanggan_sales_id'] ?? null) !== \$myEmpId"));
assertTest("CustomerOrderController::detailAjax memeriksa scoping sales binaan", str_contains($cCustomerOrder, "if (!Auth::can('orders.view_all') && !Auth::can('orders.po_view_all'))"));
assertTest("OrderDocumentController::printPickingList memeriksa scoping sales", str_contains($cOrderDoc, "if (!Auth::can('orders.po_view_all'))"));
assertTest("OrderDocumentController::pickingListPdf memeriksa scoping sales", str_contains($cOrderDoc, "if (!Auth::can('orders.po_view_all'))"));
assertTest("OrderDocumentController::invoicePdf memeriksa scoping sales", str_contains($cOrderDoc, "if (!Auth::can('orders.view_all'))"));
assertTest("OrderDocumentController::exportExcel menerapkan filter scoping sales", str_contains($cOrderDoc, "p.sales_driver_id = :my_emp_id OR pel.sales_driver_id = :my_emp_id"));

// ZERO RAW ECHOES IN TAHAP 1 & 2 FILES
$controllersToCheck = [
    'CustomerOrderController' => $cCustomerOrder,
    'DeliveryController' => $cDelivery,
    'PurchaseController' => $cPurchase,
    'CustomerController' => $cCustomer,
    'OrderDocumentController' => $cOrderDoc,
    'ProductController' => $cProduct,
    'SupplierController' => $cSupplier,
    'PricingController' => $cPricing,
    'EmployeeController' => $cEmployee,
];

foreach ($controllersToCheck as $name => $code) {
    $hasRawDbError = str_contains($code, 'echo "Database Error') || str_contains($code, "echo 'Database Error");
    assertTest("{$name} bebas dari raw 'Database Error' echo", !$hasRawDbError);
}

// RBAC MASTER CONTROLLERS
assertTest("ProductController melindungi produk dan bahan baku", str_contains($cProduct, "'master.products_manage'") && str_contains($cProduct, "'master.materials_manage'"));
assertTest("SupplierController melindungi master.suppliers_manage", str_contains($cSupplier, "'master.suppliers_manage'"));
assertTest("CustomerController melindungi master.customers_manage", str_contains($cCustomer, "'master.customers_manage'"));
assertTest("PricingController melindungi master.pricing_manage", str_contains($cPricing, "'master.pricing_manage'"));
assertTest("EmployeeController melindungi master.employees_manage", str_contains($cEmployee, "'master.employees_manage'"));

// ROUTER CSRF DISPATCH
assertTest("Router::dispatch mengaktifkan proteksi CSRF untuk POST/PUT/PATCH/DELETE", str_contains($cRouter, "\App\Helpers\CSRF::validate()"));

echo "\n========================================================\n";
echo "HASIL AKHIR: {$passCount} LULUS, {$failCount} GAGAL\n";
echo "PERSENTASE KELULUSAN: " . round(($passCount / ($passCount + $failCount)) * 100, 2) . "%\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}

<?php
/**
 * KEREN SNACK ERP — Front Controller (Enterprise VPS Standard)
 * Location: public/index.php
 */

declare(strict_types=1);

// Reset OPcache in development / Laragon Apache
if (function_exists('opcache_reset')) {
    @opcache_reset();
}

// Error Reporting Configuration
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Root Path Constant
define('ROOT_PATH', dirname(__DIR__));

// Gzip Output Compression (Reduces HTML/JSON bandwidth by ~80%+)
if (!ob_get_level() && extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
}

// Security & Performance Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// 1. Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

// 2. Autoloader
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

// 3. Database Connection
require_once ROOT_PATH . '/config/database.php';

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\PosController;
use App\Controllers\PricingController;
use App\Controllers\InventoryController;
use App\Controllers\ConsignmentController;
use App\Controllers\OwnerController;
use App\Controllers\ProfileController;
use App\Controllers\CustomerController;
use App\Controllers\SupplierController;
use App\Controllers\EmployeeController;
use App\Controllers\ProductController;
use App\Controllers\PurchaseController;
use App\Controllers\DeliveryController;
use App\Controllers\CashController;
use App\Controllers\CustomerOrderController;
use App\Controllers\SalesOrderController;
use App\Controllers\DeveloperController;

// =========================================================================
// ROUTE REGISTRATION (Enterprise Router)
// =========================================================================

// --- AUTHENTICATION ---
Router::get('/login', [AuthController::class, 'showLogin']);
Router::post('/login', [AuthController::class, 'login']);
Router::get('/logout', [AuthController::class, 'logout']);

// --- ROOT REDIRECT ---
Router::get('/', function () {
    Router::redirect('/pos');
});

// --- TRANSAKSI 1: POS KASIR (RITEL UMUM) ---
Router::get('/pos', [PosController::class, 'index']);
Router::get('/api/pos/calculate-price', [PosController::class, 'calculatePrice']);
Router::get('/api/pos/search-barcode', [PosController::class, 'searchBarcode']);
Router::post('/api/pos/checkout', [PosController::class, 'checkout']);

// --- TRANSAKSI 2: PESANAN PELANGGAN (FAKTUR B2B) ---
Router::get('/customer-orders', [CustomerOrderController::class, 'index']);
Router::get('/customer-orders/create', [CustomerOrderController::class, 'create']);
Router::post('/customer-orders/store', [CustomerOrderController::class, 'store']);
Router::get('/customer-orders/detail-ajax', [CustomerOrderController::class, 'detailAjax']);
Router::get('/customer-orders/invoice', [CustomerOrderController::class, 'invoice']);
Router::post('/customer-orders/pay', [CustomerOrderController::class, 'pay']);
Router::post('/customer-orders/cancel', [CustomerOrderController::class, 'cancel']);
Router::post('/customer-orders/update-delivery-status', [CustomerOrderController::class, 'updateDeliveryStatus']);

// Alias / Compatibility Routes untuk Sales Orders
Router::get('/sales-orders', [CustomerOrderController::class, 'index']);
Router::get('/sales-orders/create', [CustomerOrderController::class, 'create']);
Router::post('/sales-orders/store', [CustomerOrderController::class, 'store']);
Router::get('/sales-orders/detail-ajax', [CustomerOrderController::class, 'detailAjax']);
Router::get('/sales-orders/invoice', [CustomerOrderController::class, 'invoice']);
Router::post('/sales-orders/pay', [CustomerOrderController::class, 'pay']);
Router::post('/sales-orders/cancel', [CustomerOrderController::class, 'cancel']);
Router::post('/sales-orders/update-delivery-status', [CustomerOrderController::class, 'updateDeliveryStatus']);

// --- TRANSAKSI 2: MATRIKS HARGA 28 LEVEL & TIER PELANGGAN ---
Router::get('/pricing', [PricingController::class, 'index']);
Router::post('/pricing/update-level', [PricingController::class, 'updateLevelPrice']);
Router::post('/pricing/delete-level', [PricingController::class, 'deleteLevelPrice']);
Router::post('/pricing/store-group', [PricingController::class, 'storeCustomerGroup']);
Router::post('/pricing/update-group', [PricingController::class, 'updateCustomerGroup']);
Router::post('/pricing/delete-group', [PricingController::class, 'deleteCustomerGroup']);

// --- GUDANG 1: KATALOG & OPNAME STOK FISIK ---
Router::get('/inventory', [InventoryController::class, 'index']);
Router::post('/inventory/adjust', [InventoryController::class, 'adjustStock']);

// --- GUDANG 2: PEMBELIAN / PO MASUK VENDOR ---
Router::get('/purchases', [PurchaseController::class, 'index']);
Router::post('/purchases/store', [PurchaseController::class, 'store']);

// --- LOGISTIK 1: SURAT JALAN PENGIRIMAN ---
Router::get('/deliveries', [DeliveryController::class, 'index']);
Router::get('/deliveries/print', [DeliveryController::class, 'print']);
Router::post('/deliveries/store', [DeliveryController::class, 'store']);
Router::post('/deliveries/update-status', [DeliveryController::class, 'updateStatus']);

// --- LOGISTIK 2: TITIP JUAL KONSINYASI RAK (ADMIN & SALES MOBILE) ---
Router::get('/consignment', [ConsignmentController::class, 'index']);
Router::get('/consignment/opname', [ConsignmentController::class, 'opname']);
Router::post('/consignment/opname', [ConsignmentController::class, 'processOpname']);
Router::get('/consignment/store-items', [ConsignmentController::class, 'getStoreItems']);
Router::get('/consignment/billing-report', [ConsignmentController::class, 'getStoreBillingReport']);
Router::get('/consignment/print-billing', [ConsignmentController::class, 'printBilling']);
Router::post('/consignment/settle-billing', [ConsignmentController::class, 'settleBilling']);
Router::post('/consignment/assign-driver', [ConsignmentController::class, 'assignDriver']);
Router::post('/consignment/cancel-delivery', [ConsignmentController::class, 'cancelDelivery']);
Router::post('/consignment/pay-invoice', [ConsignmentController::class, 'payInvoice']);

// --- FASE 2: SALES MOBILE KONSINYASI (DEPRECATED) ---
Router::get('/consignment/summary', [ConsignmentController::class, 'summary']);

// --- KEUANGAN & KAS: BUKU KAS, TRANSAKSI & LAPORAN ARUS KAS ---
Router::get('/cash', [CashController::class, 'index']);
Router::get('/cash/transactions', [CashController::class, 'transactions']);
Router::get('/cash/reports', [CashController::class, 'reports']);
Router::post('/cash/store-account', [CashController::class, 'storeAccount']);
Router::post('/cash/update-account', [CashController::class, 'updateAccount']);
Router::post('/cash/store-inflow', [CashController::class, 'storeInflow']);
Router::post('/cash/store-outflow', [CashController::class, 'storeOutflow']);
Router::post('/cash/store-transfer', [CashController::class, 'storeTransfer']);
Router::post('/cash/set-default-pos', [CashController::class, 'setDefaultPos']);

// --- MASTER DATA 1: TOKO PELANGGAN & WILAYAH ---
Router::get('/customers', [CustomerController::class, 'index']);
Router::post('/customers/store', [CustomerController::class, 'store']);
Router::post('/customers/update', [CustomerController::class, 'update']);
Router::post('/customers/delete', [CustomerController::class, 'delete']);
Router::post('/customers/save-items', [CustomerController::class, 'saveCustomerItems']);
Router::post('/customers/store-territory', [CustomerController::class, 'storeTerritory']);
Router::post('/customers/update-territory', [CustomerController::class, 'updateTerritory']);
Router::post('/customers/delete-territory', [CustomerController::class, 'deleteTerritory']);
Router::post('/customers/store-group', [CustomerController::class, 'storeGroup']);
Router::post('/customers/update-group', [CustomerController::class, 'updateGroup']);
Router::post('/customers/delete-group', [CustomerController::class, 'deleteGroup']);

// --- MASTER DATA 2: PEMASOK VENDOR ---
Router::get('/suppliers', [SupplierController::class, 'index']);
Router::post('/suppliers/store', [SupplierController::class, 'store']);
Router::post('/suppliers/update', [SupplierController::class, 'update']);

// --- MASTER DATA 3: MASTER DATA KARYAWAN ---
Router::get('/employees', [EmployeeController::class, 'index']);
Router::post('/employees/store', [EmployeeController::class, 'store']);
Router::post('/employees/update', [EmployeeController::class, 'update']);
Router::post('/employees/delete', [EmployeeController::class, 'delete']);

// --- MASTER DATA 4: PRODUK, BAHAN BAKU, RESEP BOM & UPAH BORONGAN ---
Router::get('/products', [ProductController::class, 'index']);
Router::post('/products/store-group', [ProductController::class, 'storeGroup']);
Router::post('/products/store-item', [ProductController::class, 'storeItem']);
Router::post('/products/update-item', [ProductController::class, 'updateItem']);
Router::post('/products/store-material', [ProductController::class, 'storeMaterial']);
Router::post('/products/update-material', [ProductController::class, 'updateMaterial']);
Router::post('/products/delete-material', [ProductController::class, 'deleteMaterial']);
Router::post('/products/store-recipe-item', [ProductController::class, 'storeRecipeItem']);
Router::post('/products/delete-recipe-item', [ProductController::class, 'deleteRecipeItem']);
Router::post('/products/store-borongan-group', [ProductController::class, 'storeBoronganGroup']);
Router::post('/products/update-borongan-group', [ProductController::class, 'updateBoronganGroup']);
Router::post('/products/delete-borongan-group', [ProductController::class, 'deleteBoronganGroup']);

// --- MANAJEMEN 1: OWNER COMMAND CENTER & LIVE STREAM ---
Router::get('/owner', [OwnerController::class, 'index']);
Router::post('/owner/approve-draft', [OwnerController::class, 'approveDraft']);
Router::post('/owner/reject-draft', [OwnerController::class, 'rejectDraft']);
Router::post('/owner/consignment/approve-delivery', [OwnerController::class, 'approveConsignmentDelivery']);
Router::post('/owner/consignment/reject-delivery', [OwnerController::class, 'rejectConsignmentDelivery']);

// --- MANAJEMEN 2: PROFIL PENGGUNA & PENGATURAN AKUN ---
Router::get('/profile', [ProfileController::class, 'index']);
Router::post('/profile/update-username', [ProfileController::class, 'updateUsername']);
Router::post('/profile/update-password', [ProfileController::class, 'updatePassword']);

// --- DEVELOPER EXCLUSIVE: VISUAL ARCHITECTURE & AI BLUEPRINT ---
Router::get('/developer/architecture', [DeveloperController::class, 'architecture']);

// Dispatch the incoming HTTP request
Router::dispatch();

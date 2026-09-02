<?php
/**
 * KEREN SNACK ERP — Front Controller (Enterprise VPS Standard)
 * Location: public/index.php
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

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
use App\Controllers\UserController;
use App\Controllers\PermissionController;
use App\Controllers\SettingsController;
use App\Controllers\ActivityLogController;

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

// --- TRANSAKSI 2: PESANAN PELANGGAN (FAKTUR B2B & DAFTAR PO) ---
Router::get('/customer-orders', [CustomerOrderController::class, 'index']);
Router::get('/customer-orders/po-list', [CustomerOrderController::class, 'poList']);
Router::post('/customer-orders/process-po', [CustomerOrderController::class, 'processPoToReady']);
Router::get('/customer-orders/picking-list', [CustomerOrderController::class, 'printPickingList']);
Router::post('/customer-orders/retry-delivery', [CustomerOrderController::class, 'retryDelivery']);
Router::get('/customer-orders/create', [CustomerOrderController::class, 'create']);
Router::post('/customer-orders/store', [CustomerOrderController::class, 'store']);
Router::get('/customer-orders/edit', [CustomerOrderController::class, 'edit']);
Router::post('/customer-orders/update', [CustomerOrderController::class, 'update']);
Router::get('/customer-orders/detail-ajax', [CustomerOrderController::class, 'detailAjax']);
Router::get('/customer-orders/invoice', [CustomerOrderController::class, 'invoice']);
Router::post('/customer-orders/pay', [CustomerOrderController::class, 'pay']);
Router::post('/customer-orders/cancel', [CustomerOrderController::class, 'cancel']);
Router::post('/customer-orders/update-delivery-status', [CustomerOrderController::class, 'updateDeliveryStatus']);

// Alias / Compatibility Routes untuk Sales Orders
Router::get('/sales-orders', [CustomerOrderController::class, 'index']);
Router::get('/sales-orders/create', [CustomerOrderController::class, 'create']);
Router::post('/sales-orders/store', [CustomerOrderController::class, 'store']);
Router::get('/sales-orders/edit', [CustomerOrderController::class, 'edit']);
Router::post('/sales-orders/update', [CustomerOrderController::class, 'update']);
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
Router::post('/inventory/waste', [InventoryController::class, 'recordWaste']);

// --- GUDANG 2: PEMBELIAN / PO MASUK VENDOR ---
Router::get('/purchases', [PurchaseController::class, 'index']);
Router::post('/purchases/store', [PurchaseController::class, 'store']);

// --- DELIVERY 1: PORTAL PENGIRIMAN DRIVER ---
Router::get('/driver-deliveries', [DeliveryController::class, 'driverRoute']);
Router::post('/driver-deliveries/start', [DeliveryController::class, 'startTrip']);
Router::post('/driver-deliveries/complete', [DeliveryController::class, 'completeDelivery']);
Router::post('/driver-deliveries/fail', [DeliveryController::class, 'failDelivery']);

// --- DELIVERY 2: SURAT JALAN PENGIRIMAN ---
Router::get('/deliveries', [DeliveryController::class, 'index']);
Router::get('/deliveries/print', [DeliveryController::class, 'print']);
Router::post('/deliveries/store', [DeliveryController::class, 'store']);
Router::post('/deliveries/approve', [DeliveryController::class, 'approve']);
Router::post('/deliveries/update-status', [DeliveryController::class, 'updateStatus']);

// --- LOGISTIK 2: TITIP JUAL KONSINYASI RAK (PORTAL TERPADU) ---
Router::get('/consignment', [ConsignmentController::class, 'portal']);
Router::get('/consignment/stok-rak', [ConsignmentController::class, 'stokRak']);
Router::get('/consignment/opname', [ConsignmentController::class, 'opname']);
Router::post('/consignment/opname/proses', [ConsignmentController::class, 'opnameProses']);
Router::get('/consignment/opname/hasil', [ConsignmentController::class, 'hasilKunjungan']);
Router::post('/consignment/konfirmasi-terima', [ConsignmentController::class, 'konfirmasiTerima']);
Router::get('/consignment/laporan-penjualan', [ConsignmentController::class, 'laporanPenjualan']);
Router::get('/consignment/piutang', [ConsignmentController::class, 'piutang']);
Router::post('/consignment/piutang/bayar', [ConsignmentController::class, 'catatPembayaran']);
Router::get('/consignment/assignment-sales', [ConsignmentController::class, 'assignmentSales']);
Router::post('/consignment/assignment-sales/save', [ConsignmentController::class, 'saveAssignment']);
Router::get('/consignment/riwayat-kunjungan', [ConsignmentController::class, 'riwayatKunjungan']);
Router::get('/consignment/komisi-sales', [ConsignmentController::class, 'komisiSales']);
Router::get('/consignment/kerugian-rusak', [ConsignmentController::class, 'kerugianRusak']);
Router::get('/consignment/early-warning', [ConsignmentController::class, 'earlyWarning']);

// Legacy / Compatibility Redirects
Router::get('/consignment/sales', function () {
    Router::redirect('/consignment');
});
Router::get('/consignment/summary', function () {
    $kId = $_GET['kunjungan_id'] ?? '';
    Router::redirect('/consignment/opname/hasil' . (!empty($kId) ? '?kunjungan_id=' . urlencode($kId) : ''));
});

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

// --- MANAJEMEN 3: HAK AKSES, PENGGUNA & RBAC (5-TAB MASTER) ---
Router::get('/users', [UserController::class, 'index']);
Router::get('/users/create', [UserController::class, 'create']);
Router::post('/users/store', [UserController::class, 'store']);
Router::get('/users/edit', [UserController::class, 'edit']);
Router::post('/users/update', [UserController::class, 'update']);
Router::post('/users/toggle-status', [UserController::class, 'toggleStatus']);
Router::post('/users/delete', [UserController::class, 'delete']);

Router::get('/permissions', [PermissionController::class, 'index']);
Router::post('/permissions/save-user-overrides', [PermissionController::class, 'saveUserOverrides']);
Router::post('/permissions/reset-user-overrides', [PermissionController::class, 'resetUserOverrides']);
Router::get('/permissions/roles', [PermissionController::class, 'roles']);
Router::post('/permissions/save-role-permissions', [PermissionController::class, 'saveRolePermissions']);
Router::get('/permissions/manage-roles', [PermissionController::class, 'manageRoles']);
Router::post('/permissions/store-role', [PermissionController::class, 'storeRole']);
Router::post('/permissions/update-role', [PermissionController::class, 'updateRole']);
Router::post('/permissions/delete-role', [PermissionController::class, 'deleteRole']);
Router::get('/permissions/bulk', [PermissionController::class, 'bulk']);
Router::post('/permissions/save-bulk-overrides', [PermissionController::class, 'saveBulkOverrides']);
Router::get('/permissions/matrix', [PermissionController::class, 'matrix']);

// --- PENGATURAN SISTEM: PORTAL HUB ---
Router::get('/settings', [SettingsController::class, 'index']);
Router::get('/pengaturan', [SettingsController::class, 'index']);
Router::get('/settings/activity-logs', [ActivityLogController::class, 'index']);
Router::get('/settings/logs', [ActivityLogController::class, 'index']);

// --- DEVELOPER EXCLUSIVE: VISUAL ARCHITECTURE & AI BLUEPRINT ---
Router::get('/developer/architecture', [DeveloperController::class, 'architecture']);

// Dispatch the incoming HTTP request
Router::dispatch();

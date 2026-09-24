<?php
/**
 * KEREN SNACK ERP — Front Controller (Enterprise VPS Standard)
 * Location: public/index.php
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

// Root Path Constant
define('ROOT_PATH', dirname(__DIR__));

// Load Environment Variables (.env)
require_once ROOT_PATH . '/config/env.php';

$isDev = (getenv('APP_ENV') === 'local' || getenv('APP_DEBUG') === 'true');

// Reset OPcache only in development environment
if ($isDev && function_exists('opcache_reset')) {
    @opcache_reset();
}

// Error Reporting Configuration
if ($isDev) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Gzip Output Compression (Reduces HTML/JSON bandwidth by ~80%+)
if (!ob_get_level() && extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
}

// Security & Performance Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; img-src 'self' data: blob: https:; font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; connect-src 'self'; frame-ancestors 'self';");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()");

// 1. Session Initialization (Sliding Inactivity Timeout 1 Jam / 3.600 detik)
ini_set('session.gc_maxlifetime', '86400');
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_lifetime' => 0,
        'gc_maxlifetime'  => 86400
    ]);
}

// 2. Autoloader (Composer Vendor & App PSR-4)
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

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

// Global Class Aliases for Views & Helpers
if (!class_exists('Router', false)) {
    class_alias(\App\Core\Router::class, 'Router');
}
if (!class_exists('Auth', false)) {
    class_alias(\App\Core\Auth::class, 'Auth');
}
if (!class_exists('Csrf', false)) {
    class_alias(\App\Helpers\Csrf::class, 'Csrf');
}
if (!class_exists('Flash', false)) {
    class_alias(\App\Helpers\Flash::class, 'Flash');
}

// 3. Database Connection
require_once ROOT_PATH . '/config/database.php';

// 4. Pastikan Seluruh Folder Unggahan & Proteksi (.htaccess & .gitkeep) Terbuat Otomatis
\App\Helpers\Upload::initDirectories();

use App\Core\Router;
use App\Controllers\DashboardController;
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
use App\Controllers\OrderDocumentController;
use App\Controllers\SalesOrderController;
use App\Controllers\DeveloperController;
use App\Controllers\UserController;
use App\Controllers\PermissionController;
use App\Controllers\SettingsController;
use App\Controllers\ActivityLogController;
use App\Controllers\ImportDataController;
use App\Controllers\MediaController;

// =========================================================================
// ROUTE REGISTRATION (Enterprise Router)
// =========================================================================

// --- AUTHENTICATION ---
Router::get('/login', [AuthController::class, 'showLogin']);
Router::post('/login', [AuthController::class, 'login']);
Router::get('/logout', [AuthController::class, 'logout']);
Router::post('/logout', [AuthController::class, 'logout']);
Router::post('/api/auth/heartbeat', [AuthController::class, 'heartbeat']);
Router::get('/restricted', function() {
    http_response_code(403);
    require ROOT_PATH . '/views/errors/restricted.php';
});

// --- CLOUDFLARE R2 & LOCAL CACHE MEDIA PROXY ---
Router::get('/media/view', [MediaController::class, 'serveFile']);
Router::get('/api/media/presigned', [MediaController::class, 'getPresignedUrl']);
Router::get('/api/media/cache-stats', [MediaController::class, 'cacheStats']);
Router::post('/api/media/clear-cache', [MediaController::class, 'clearCache']);

// --- ROOT REDIRECT & DASHBOARD UTAMA ---
Router::get('/', function () {
    if (\App\Core\Auth::check()) {
        Router::redirect('/dashboard');
    }
    Router::redirect('/login');
});

Router::get('/dashboard', [DashboardController::class, 'index']);

// --- TRANSAKSI 1: POS KASIR (RITEL UMUM) ---
Router::get('/pos', [PosController::class, 'index']);
Router::get('/api/pos/calculate-price', [PosController::class, 'calculatePrice']);
Router::get('/api/pos/search-barcode', [PosController::class, 'searchBarcode']);
Router::post('/api/pos/checkout', [PosController::class, 'checkout']);

// --- TRANSAKSI 2: PESANAN PELANGGAN (FAKTUR B2B & DAFTAR PO) ---
Router::get('/customer-orders', [CustomerOrderController::class, 'index']);
Router::get('/customer-orders/export/excel', [OrderDocumentController::class, 'exportExcel']);
Router::get('/customer-orders/po-list', [CustomerOrderController::class, 'poList']);
Router::get('/customer-orders/po-list/batch-pdf', [OrderDocumentController::class, 'batchPickingListPdf']);
Router::post('/customer-orders/po-list/batch-pdf', [OrderDocumentController::class, 'batchPickingListPdf']);
Router::post('/customer-orders/process-po', [CustomerOrderController::class, 'processPoToReady']);
Router::get('/customer-orders/picking-list', [OrderDocumentController::class, 'printPickingList']);
Router::get('/customer-orders/picking-list/pdf', [OrderDocumentController::class, 'pickingListPdf']);
Router::get('/customer-orders/picking-list/batch-pdf', [OrderDocumentController::class, 'batchPickingListPdf']);
Router::post('/customer-orders/picking-list/batch-pdf', [OrderDocumentController::class, 'batchPickingListPdf']);
Router::post('/customer-orders/retry-delivery', [CustomerOrderController::class, 'retryDelivery']);
Router::get('/customer-orders/create', [CustomerOrderController::class, 'create']);
Router::post('/customer-orders/store', [CustomerOrderController::class, 'store']);
Router::get('/customer-orders/edit', [CustomerOrderController::class, 'edit']);
Router::post('/customer-orders/update', [CustomerOrderController::class, 'update']);
Router::get('/customer-orders/detail-ajax', [CustomerOrderController::class, 'detailAjax']);
Router::get('/customer-orders/invoice', [CustomerOrderController::class, 'invoice']);
Router::get('/customer-orders/invoice/pdf', [OrderDocumentController::class, 'invoicePdf']);
Router::get('/customer-orders/invoice/excel', [OrderDocumentController::class, 'invoiceExcel']);
Router::get('/customer-orders/print', [CustomerOrderController::class, 'invoice']);
Router::post('/customer-orders/pay', [CustomerOrderController::class, 'pay']);
Router::post('/customer-orders/cancel', [CustomerOrderController::class, 'cancel']);
Router::post('/customer-orders/update-delivery-status', [CustomerOrderController::class, 'updateDeliveryStatus']);

// Alias / Compatibility Routes untuk Sales Orders
Router::get('/sales-orders', [CustomerOrderController::class, 'index']);
Router::get('/sales-orders/export/excel', [OrderDocumentController::class, 'exportExcel']);
Router::get('/sales-orders/create', [CustomerOrderController::class, 'create']);
Router::post('/sales-orders/store', [CustomerOrderController::class, 'store']);
Router::get('/sales-orders/edit', [CustomerOrderController::class, 'edit']);
Router::post('/sales-orders/update', [CustomerOrderController::class, 'update']);
Router::get('/sales-orders/detail-ajax', [CustomerOrderController::class, 'detailAjax']);
Router::get('/sales-orders/invoice', [CustomerOrderController::class, 'invoice']);
Router::get('/sales-orders/invoice/pdf', [OrderDocumentController::class, 'invoicePdf']);
Router::get('/sales-orders/invoice/excel', [OrderDocumentController::class, 'invoiceExcel']);
Router::get('/sales-orders/picking-list/pdf', [OrderDocumentController::class, 'pickingListPdf']);
Router::get('/sales-orders/print', [CustomerOrderController::class, 'invoice']);
Router::post('/sales-orders/pay', [CustomerOrderController::class, 'pay']);
Router::post('/sales-orders/cancel', [CustomerOrderController::class, 'cancel']);
Router::post('/sales-orders/update-delivery-status', [CustomerOrderController::class, 'updateDeliveryStatus']);

// --- TRANSAKSI 2: MATRIKS HARGA 30 LEVEL & TIER PELANGGAN ---
Router::get('/pricing', [PricingController::class, 'index']);
Router::post('/pricing/update-level', [PricingController::class, 'updateLevelPrice']);
Router::post('/pricing/update-master-level', [PricingController::class, 'updateMasterLevel']);
Router::post('/pricing/delete-level', [PricingController::class, 'deleteLevelPrice']);

// --- GUDANG 1: KATALOG & OPNAME STOK FISIK ---
Router::get('/inventory', [InventoryController::class, 'index']);
Router::get('/inventory/export-excel', [InventoryController::class, 'exportExcel']);
Router::post('/inventory/adjust', [InventoryController::class, 'adjustStock']);
Router::post('/inventory/waste', [InventoryController::class, 'recordWaste']);
Router::get('/inventory/bulk-opname', [InventoryController::class, 'bulkOpname']);
Router::post('/inventory/bulk-opname/store', [InventoryController::class, 'storeBulkOpname']);
Router::get('/inventory/api/item-history', [InventoryController::class, 'apiItemHistory']);
Router::get('/inventory/opname/detail', [InventoryController::class, 'opnameDetail']);
Router::get('/inventory/opname/history', [InventoryController::class, 'opnameHistory']);
Router::get('/inventory/opname/pdf', [InventoryController::class, 'exportOpnamePdf']);
Router::get('/inventory/opname/excel', [InventoryController::class, 'exportOpnameExcel']);

// --- GUDANG 2: PEMBELIAN / PO MASUK VENDOR ---
Router::get('/purchases', [PurchaseController::class, 'index']);
Router::get('/purchases/detail', [PurchaseController::class, 'detailAjax']);
Router::post('/purchases/store', [PurchaseController::class, 'store']);
Router::post('/purchases/update-po', [PurchaseController::class, 'updatePo']);
Router::post('/purchases/receive', [PurchaseController::class, 'receiveGoods']);
Router::get('/purchases/print', [PurchaseController::class, 'print']);
Router::get('/purchases/pdf', [PurchaseController::class, 'pdf']);
Router::post('/purchases/pay', [PurchaseController::class, 'payDebt']);
Router::post('/purchases/cancel', [PurchaseController::class, 'cancel']);

// --- DELIVERY 1: PORTAL PENGIRIMAN DRIVER ---
Router::get('/driver-deliveries', [DeliveryController::class, 'driverRoute']);
Router::post('/driver-deliveries/start', [DeliveryController::class, 'startTrip']);
Router::post('/driver-deliveries/complete', [DeliveryController::class, 'completeDelivery']);
Router::post('/driver-deliveries/fail', [DeliveryController::class, 'failDelivery']);
Router::post('/driver-deliveries/shopping/complete', [DeliveryController::class, 'completeShoppingTask']);
Router::post('/driver-deliveries/shopping/report-issue', [DeliveryController::class, 'reportShoppingIssue']);

// --- DELIVERY 2: SURAT JALAN PENGIRIMAN ---
Router::get('/deliveries', [DeliveryController::class, 'index']);
Router::get('/deliveries/export/excel', [DeliveryController::class, 'exportExcel']);
Router::get('/deliveries/print', [DeliveryController::class, 'print']);
Router::get('/deliveries/pdf', [DeliveryController::class, 'pdf']);
Router::post('/deliveries/store', [DeliveryController::class, 'store']);
Router::post('/deliveries/update', [DeliveryController::class, 'update']);
Router::post('/deliveries/update-status', [DeliveryController::class, 'updateStatus']);

// --- LOGISTIK 2: TITIP JUAL KONSINYASI RAK (PORTAL TERPADU) ---
Router::get('/consignment', [ConsignmentController::class, 'portal']);
Router::get('/consignment/stok-rak', [ConsignmentController::class, 'stokRak']);
Router::get('/consignment/opname', [ConsignmentController::class, 'opname']);
Router::post('/consignment/opname/proses', [ConsignmentController::class, 'opnameProses']);
Router::get('/consignment/opname/hasil', [ConsignmentController::class, 'hasilKunjungan']);
Router::post('/consignment/opname/bayar-langsung', [ConsignmentController::class, 'bayarLangsungKunjungan']);
Router::get('/consignment/opname/hasil/pdf', [ConsignmentController::class, 'notaPdf']);
Router::get('/consignment/nota-pdf', [ConsignmentController::class, 'notaPdf']);
Router::get('/consignment/nota-print', [ConsignmentController::class, 'printNota']);
Router::post('/consignment/konfirmasi-terima', [ConsignmentController::class, 'konfirmasiTerima']);
Router::get('/consignment/laporan-penjualan', [ConsignmentController::class, 'laporanPenjualan']);
Router::get('/consignment/laporan-penjualan/detail-toko', [ConsignmentController::class, 'detailTokoAjax']);
Router::get('/consignment/laporan-penjualan/export-excel', [ConsignmentController::class, 'exportSalesExcel']);
Router::get('/consignment/tagihan', [ConsignmentController::class, 'tagihanIndex']);
Router::post('/consignment/tagihan/generate', [ConsignmentController::class, 'tagihanGenerate']);
Router::post('/consignment/tagihan/bayar', [ConsignmentController::class, 'tagihanBayar']);
Router::get('/consignment/tagihan/export-excel', [ConsignmentController::class, 'tagihanExportExcel']);
Router::get('/consignment/assignment-sales', [ConsignmentController::class, 'assignmentSales']);
Router::post('/consignment/assignment-sales/save', [ConsignmentController::class, 'saveAssignment']);
Router::get('/consignment/riwayat-kunjungan', [ConsignmentController::class, 'riwayatKunjungan']);
Router::get('/consignment/komisi-sales', [ConsignmentController::class, 'komisiSales']);
Router::get('/consignment/kerugian-rusak', [ConsignmentController::class, 'kerugianRusak']);
Router::get('/consignment/kerugian-rusak/export-excel', [ConsignmentController::class, 'exportKerugianExcel']);
Router::get('/consignment/early-warning', [ConsignmentController::class, 'earlyWarning']);

// Legacy / Compatibility Redirects
Router::get('/consignment/sales', function () {
    Router::redirect('/consignment');
});
Router::get('/consignment/summary', function () {
    $kId = $_GET['kunjungan_id'] ?? '';
    Router::redirect('/consignment/opname/hasil' . (!empty($kId) ? '?kunjungan_id=' . urlencode($kId) : ''));
});
// Redirect lama /consignment/piutang → /consignment/tagihan
Router::get('/consignment/piutang', function () {
    Router::redirect('/consignment/tagihan');
});


// --- KEUANGAN & KAS: BUKU KAS, TRANSAKSI & LAPORAN ARUS KAS ---
Router::get('/cash', [CashController::class, 'index']);
Router::get('/cash/transactions', [CashController::class, 'transactions']);
Router::get('/cash/transactions/export-excel', [CashController::class, 'exportTransactionsExcel']);
Router::get('/cash/reports', [CashController::class, 'reports']);
Router::get('/cash/reports/export-excel', [CashController::class, 'exportReportsExcel']);
Router::post('/cash/store-account', [CashController::class, 'storeAccount']);
Router::post('/cash/update-account', [CashController::class, 'updateAccount']);
Router::post('/cash/store-inflow', [CashController::class, 'storeInflow']);
Router::post('/cash/store-outflow', [CashController::class, 'storeOutflow']);
Router::post('/cash/store-transfer', [CashController::class, 'storeTransfer']);
Router::post('/cash/set-default-pos', [CashController::class, 'setDefaultPos']);
Router::post('/cash/delete-account', [CashController::class, 'deleteAccount']);

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
Router::post('/customers/duplicate-group', [CustomerController::class, 'duplicateGroup']);
Router::post('/customers/delete-group', [CustomerController::class, 'deleteGroup']);

// --- MASTER DATA 2: PEMASOK VENDOR ---
Router::get('/suppliers', [SupplierController::class, 'index']);
Router::post('/suppliers/store', [SupplierController::class, 'store']);
Router::post('/suppliers/update', [SupplierController::class, 'update']);
Router::post('/suppliers/delete', [SupplierController::class, 'delete']);

// --- MASTER DATA 3: MASTER DATA KARYAWAN ---
Router::get('/employees', [EmployeeController::class, 'index']);
Router::post('/employees/store', [EmployeeController::class, 'store']);
Router::post('/employees/update', [EmployeeController::class, 'update']);
Router::post('/employees/delete', [EmployeeController::class, 'delete']);
Router::post('/employees/commission-tiers/batch-save', [EmployeeController::class, 'saveCommissionTiersBatch']);

// --- MASTER DATA 4: PRODUK, BAHAN BAKU, RESEP BOM & UPAH BORONGAN ---
Router::get('/products', [ProductController::class, 'index']);
Router::post('/products/store-brand', [ProductController::class, 'storeBrand']);
Router::post('/products/update-brand', [ProductController::class, 'updateBrand']);
Router::post('/products/delete-brand', [ProductController::class, 'deleteBrand']);
Router::post('/products/store-group', [ProductController::class, 'storeGroup']);
Router::post('/products/update-group', [ProductController::class, 'updateGroup']);
Router::post('/products/delete-group', [ProductController::class, 'deleteGroup']);
Router::post('/products/store-item', [ProductController::class, 'storeItem']);
Router::post('/products/update-item', [ProductController::class, 'updateItem']);
Router::post('/products/delete-item', [ProductController::class, 'deleteItem']);
Router::post('/products/store-material', [ProductController::class, 'storeMaterial']);
Router::post('/products/update-material', [ProductController::class, 'updateMaterial']);
Router::post('/products/delete-material', [ProductController::class, 'deleteMaterial']);
Router::post('/products/store-recipe-item', [ProductController::class, 'storeRecipeItem']);
Router::post('/products/delete-recipe-item', [ProductController::class, 'deleteRecipeItem']);
Router::post('/products/copy-recipe', [ProductController::class, 'copyRecipe']);
Router::post('/products/store-borongan-group', [ProductController::class, 'storeBoronganGroup']);
Router::post('/products/update-borongan-group', [ProductController::class, 'updateBoronganGroup']);
Router::post('/products/delete-borongan-group', [ProductController::class, 'deleteBoronganGroup']);

// --- MANAJEMEN 1: OWNER EXECUTIVE DASHBOARD (BUSINESS PERFORMANCE) ---
Router::get('/owner', [OwnerController::class, 'index']);


// --- MANAJEMEN 2: PROFIL PENGGUNA & PENGATURAN AKUN ---
Router::get('/profile', [ProfileController::class, 'index']);
Router::post('/profile/update', [ProfileController::class, 'update']);
Router::post('/profile/update-employee', [ProfileController::class, 'updateEmployee']);
Router::post('/profile/update-username', [ProfileController::class, 'update']);
Router::post('/profile/update-password', [ProfileController::class, 'update']);

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

// --- PENGATURAN SISTEM: PORTAL HUB & PROFIL PERUSAHAAN ---
Router::get('/settings', [SettingsController::class, 'index']);
Router::get('/pengaturan', [SettingsController::class, 'index']);
Router::get('/settings/company', [SettingsController::class, 'company']);
Router::post('/settings/company', [SettingsController::class, 'updateCompany']);
Router::get('/pengaturan/perusahaan', [SettingsController::class, 'company']);
Router::post('/pengaturan/perusahaan', [SettingsController::class, 'updateCompany']);
Router::get('/settings/activity-logs', [ActivityLogController::class, 'index']);
Router::get('/settings/logs', [ActivityLogController::class, 'index']);
Router::get('/settings/activity-logs/export', [ActivityLogController::class, 'exportExcel']);
Router::post('/settings/activity-logs/prune', [ActivityLogController::class, 'prune']);
Router::get('/settings/impor-data', [ImportDataController::class, 'index']);
Router::get('/pengaturan/impor-data', [ImportDataController::class, 'index']);
Router::get('/settings/impor-data/download-template', [ImportDataController::class, 'downloadTemplate']);
Router::post('/settings/impor-data/preview', [ImportDataController::class, 'preview']);
Router::post('/settings/impor-data/confirm', [ImportDataController::class, 'confirm']);
Router::post('/settings/impor-data/cancel', [ImportDataController::class, 'cancel']);
Router::get('/settings/impor-data/cancel', [ImportDataController::class, 'cancel']);

// --- DEVELOPER EXCLUSIVE: PORTAL HUB, ARCHITECTURE, TEST DB & TEST SOURCE ---
Router::get('/developer', [DeveloperController::class, 'index']);
Router::get('/developer/index', [DeveloperController::class, 'index']);
Router::get('/developer/portal', [DeveloperController::class, 'index']);
Router::get('/developer/architecture', [DeveloperController::class, 'architecture']);
Router::get('/developer/test-db', [DeveloperController::class, 'testDb']);
Router::get('/developer/test_db.php', [DeveloperController::class, 'testDb']);
Router::get('/test_db.php', [DeveloperController::class, 'testDb']);
Router::post('/developer/test-db', [DeveloperController::class, 'testDb']);
Router::post('/developer/test_db.php', [DeveloperController::class, 'testDb']);
Router::post('/test_db.php', [DeveloperController::class, 'testDb']);
Router::get('/developer/tests', [DeveloperController::class, 'tests']);
Router::get('/developer/run-all', [DeveloperController::class, 'tests']);
Router::post('/developer/tests/run-single', [DeveloperController::class, 'runSingleTest']);
Router::post('/developer/tests/clear-locks', [DeveloperController::class, 'clearTestLocks']);
Router::get('/developer/preview-403', [DeveloperController::class, 'preview403']);
Router::get('/preview-403', [DeveloperController::class, 'preview403']);
Router::get('/developer/preview-restricted', [DeveloperController::class, 'previewRestricted']);
Router::get('/preview-restricted', [DeveloperController::class, 'previewRestricted']);

// Dispatch the incoming HTTP request
Router::dispatch();

<?php
/**
 * KEREN SNACK ERP — Front Controller (Enterprise VPS Standard)
 * Location: public/index.php
 */

declare(strict_types=1);

// Error Reporting Configuration
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Root Path Constant
define('ROOT_PATH', dirname(__DIR__));

// 1. Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

// --- MODUL 1: POS KASIR ---
Router::get('/pos', [PosController::class, 'index']);
Router::get('/api/pos/calculate-price', [PosController::class, 'calculatePrice']);
Router::get('/api/pos/search-barcode', [PosController::class, 'searchBarcode']);
Router::post('/api/pos/checkout', [PosController::class, 'checkout']);

// --- MODUL 2: MATRIKS HARGA 28 LEVEL ---
Router::get('/pricing', [PricingController::class, 'index']);
Router::post('/pricing/update-level', [PricingController::class, 'updateLevel']);

// --- MODUL 3: KATALOG 137 SKU & OPNAME GUDANG ---
Router::get('/inventory', [InventoryController::class, 'index']);
Router::post('/inventory/adjust', [InventoryController::class, 'adjustStock']);

// --- MODUL 4: TITIP JUAL KONSINYASI RAK ---
Router::get('/consignment', [ConsignmentController::class, 'index']);
Router::post('/consignment/opname', [ConsignmentController::class, 'processOpname']);

// --- MODUL 5: OWNER COMMAND CENTER & LIVE STREAM ---
Router::get('/owner', [OwnerController::class, 'index']);
Router::post('/owner/approve-draft', [OwnerController::class, 'approveDraft']);
Router::post('/owner/reject-draft', [OwnerController::class, 'rejectDraft']);

// --- MODUL 6: PROFIL PENGGUNA & PENGATURAN AKUN ---
Router::get('/profile', [ProfileController::class, 'index']);
Router::post('/profile/update-username', [ProfileController::class, 'updateUsername']);
Router::post('/profile/update-password', [ProfileController::class, 'updatePassword']);

// Dispatch the incoming HTTP request
Router::dispatch();

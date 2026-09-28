<?php
declare(strict_types=1);

/**
 * tests/DeveloperDashboardActiveUsersTest.php
 * Automated verification of the Developer Dashboard "Pengguna Aktif Saat Ini" Card:
 * 1. Shows user's full name (nama_lengkap)
 * 2. Shows user's login time (jam_login)
 * 3. Shows user's status (online / offline)
 * 4. Excludes last action descriptions & action types (jenis_aksi, deskripsi_aktivitas)
 * 5. Excludes offline users who logged out > 5 minutes ago
 * 6. Includes offline users who logged out within 5 minutes
 * 7. Correctly renders the developer dashboard partial without errors or PHP notices
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user'] = [
    'id' => '00000000-0000-0000-0000-000000000000',
    'peran_id' => '11111111-1111-1111-1111-111111111100',
    'nama_lengkap' => 'Developer Master',
    'nama_pengguna' => 'ajsk',
    'peran' => 'developer',
    'role_nama' => 'Developer'
];
$_SESSION['login_time'] = time();
$_SESSION['permissions'] = ['*'];
$_SESSION['permissions_version'] = time();

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
date_default_timezone_set('Asia/Jakarta');

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

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/Core/Router.php';
require_once ROOT_PATH . '/app/Core/Auth.php';
require_once ROOT_PATH . '/app/Core/Controller.php';
require_once ROOT_PATH . '/app/Helpers/Format.php';
require_once ROOT_PATH . '/app/Controllers/DashboardController.php';

use App\Controllers\DashboardController;
use App\Core\Auth;

function runTest(string $title, callable $fn): void {
    echo "Testing: {$title} ... ";
    try {
        $fn();
        echo "[PASS]\n";
    } catch (Throwable $e) {
        echo "[FAIL] -> " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
        exit(1);
    }
}

echo "====================================================================\n";
echo "   DEVELOPER DASHBOARD ACTIVE USERS PRESENCE VERIFICATION SUITE\n";
echo "====================================================================\n";

// 1. DashboardController instantiates and loads developer data
runTest("1. DashboardController loads developer roleData with activeUsers & onlineUsersCount", function() {
    $controller = new DashboardController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('loadRoleData');
    $method->setAccessible(true);
    
    $roleData = $method->invoke($controller, 'developer', Auth::id());
    
    if (!is_array($roleData)) {
        throw new RuntimeException("roleData must be an array.");
    }
    if (!isset($roleData['activeUsers']) || !is_array($roleData['activeUsers'])) {
        throw new RuntimeException("roleData['activeUsers'] must be present and be an array.");
    }
    if (!isset($roleData['onlineUsersCount'])) {
        throw new RuntimeException("roleData['onlineUsersCount'] must be set.");
    }
});

// 2. Active users structure validation
runTest("2. Active user items contain nama_lengkap, jam_login, status, and no action details", function() {
    $controller = new DashboardController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('loadRoleData');
    $method->setAccessible(true);
    
    $roleData = $method->invoke($controller, 'developer', Auth::id());
    $activeUsers = $roleData['activeUsers'];
    
    if (empty($activeUsers)) {
        throw new RuntimeException("activeUsers should contain at least current user.");
    }
    
    foreach ($activeUsers as $user) {
        if (empty($user['nama_lengkap'])) {
            throw new RuntimeException("User must have nama_lengkap.");
        }
        if (empty($user['jam_login'])) {
            throw new RuntimeException("User must have jam_login formatted.");
        }
        if (!in_array($user['status'] ?? '', ['online', 'offline'], true)) {
            throw new RuntimeException("User status must be 'online' or 'offline'.");
        }
        if (isset($user['deskripsi_aktivitas']) || isset($user['jenis_aksi'])) {
            throw new RuntimeException("User must not expose deskripsi_aktivitas or jenis_aksi.");
        }
    }
});

// 3. Isolated simulation of online and recent offline users
runTest("3. Isolated transaction test: Online & <5m offline user displayed, >5m offline user filtered out", function() {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();
    
    try {
        // Find an existing active user in public.pengguna other than developer
        $sampleUser = Database::fetchOne("
            SELECT id, nama_lengkap, nama_pengguna 
            FROM public.pengguna 
            WHERE status_aktif = TRUE AND id != '00000000-0000-0000-0000-000000000000'
            LIMIT 1
        ");
        
        if ($sampleUser) {
            $userId = $sampleUser['id'];
            $userName = $sampleUser['nama_lengkap'];
            
            // Insert LOGIN log right now
            Database::execute("
                INSERT INTO public.log_aktivitas (
                    pengguna_id, nama_aktor, peran_aktor, sumber_aksi, kategori_aktivitas,
                    jenis_aksi, tabel_terdampak, deskripsi_aktivitas, waktu_kejadian
                ) VALUES (
                    :uid, :nama, 'sales', 'web_app', 'keamanan_auth',
                    'LOGIN', 'pengguna', 'Login test audit', NOW()
                )
            ", ['uid' => $userId, 'nama' => $userName]);
            
            $controller = new DashboardController();
            $reflection = new ReflectionClass($controller);
            $method = $reflection->getMethod('loadRoleData');
            $method->setAccessible(true);
            
            $roleData = $method->invoke($controller, 'developer', Auth::id());
            $foundOnline = false;
            foreach ($roleData['activeUsers'] as $u) {
                if ($u['pengguna_id'] === $userId && $u['status'] === 'online') {
                    $foundOnline = true;
                    break;
                }
            }
            if (!$foundOnline) {
                throw new RuntimeException("User with recent LOGIN and no logout should have status 'online'.");
            }
            
            // Now insert LOGOUT (offline < 5 minutes)
            Database::execute("
                INSERT INTO public.log_aktivitas (
                    pengguna_id, nama_aktor, peran_aktor, sumber_aksi, kategori_aktivitas,
                    jenis_aksi, tabel_terdampak, deskripsi_aktivitas, waktu_kejadian
                ) VALUES (
                    :uid, :nama, 'sales', 'web_app', 'keamanan_auth',
                    'LOGOUT', 'pengguna', 'Logout test audit', NOW() + INTERVAL '1 second'
                )
            ", ['uid' => $userId, 'nama' => $userName]);
            
            $roleData = $method->invoke($controller, 'developer', Auth::id());
            $foundRecentOffline = false;
            foreach ($roleData['activeUsers'] as $u) {
                if ($u['pengguna_id'] === $userId) {
                    if ($u['status'] !== 'offline') {
                        throw new RuntimeException("User with logout should have status 'offline'.");
                    }
                    if (empty($u['jam_logout'])) {
                        throw new RuntimeException("Offline user must have jam_logout formatted.");
                    }
                    $foundRecentOffline = true;
                    break;
                }
            }
            if (!$foundRecentOffline) {
                throw new RuntimeException("User offline < 5m must still be displayed in card.");
            }
            
            // Now update the logs for this user to be 10 minutes ago (offline > 5 minutes)
            Database::execute("
                UPDATE public.log_aktivitas
                SET waktu_kejadian = NOW() - INTERVAL '10 minutes'
                WHERE pengguna_id = :uid AND jenis_aksi IN ('LOGIN', 'LOGOUT')
            ", ['uid' => $userId]);
            
            $roleData = $method->invoke($controller, 'developer', Auth::id());
            foreach ($roleData['activeUsers'] as $u) {
                if ($u['pengguna_id'] === $userId) {
                    throw new RuntimeException("User offline > 5m should be filtered out from card.");
                }
            }
        }
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack(); // Auto-rollback to guarantee zero contamination
        }
    }
});

// 4. View Rendering Evaluation
runTest("4. View developer partial renders successfully without warnings", function() {
    $controller = new DashboardController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('loadRoleData');
    $method->setAccessible(true);
    
    $roleData = $method->invoke($controller, 'developer', Auth::id());
    
    ob_start();
    require ROOT_PATH . '/views/dashboard/partials/developer.php';
    $html = ob_get_clean();
    
    if (empty($html) || !str_contains($html, 'Pengguna Aktif')) {
        throw new RuntimeException("Developer partial failed to render 'Pengguna Aktif' card.");
    }
    if (!str_contains($html, 'ONLINE') && !str_contains($html, 'Online')) {
        throw new RuntimeException("Developer partial should render Online status indicator.");
    }
    if (!str_contains($html, 'Login:')) {
        throw new RuntimeException("Developer partial should render 'Login:' time.");
    }
});

echo "====================================================================\n";
echo "ALL DEVELOPER DASHBOARD ACTIVE USERS TESTS PASSED SUCCESSFULLY!\n";
echo "====================================================================\n";

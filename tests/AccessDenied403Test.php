<?php
declare(strict_types=1);

/**
 * tests/AccessDenied403Test.php
 * Automated Unit & Integration Tests for Access Denied (403 Kado Kejutan)
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_ROOT', ROOT_PATH);
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user'] = [
    'id' => '00000000-0000-0000-0000-000000000000',
    'peran_id' => '22222222-2222-2222-2222-222222222222',
    'nama_lengkap' => 'Staff Kasir Uji',
    'nama_pengguna' => 'kasir_uji',
    'peran' => 'kasir',
    'role_nama' => 'Kasir'
];
$_SESSION['login_time'] = time();
$_SESSION['permissions'] = ['pos.view', 'pos.checkout'];
$_SESSION['permissions_version'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/app/Core/Router.php';
require_once APP_ROOT . '/app/Core/Auth.php';
require_once APP_ROOT . '/app/Core/Controller.php';
require_once APP_ROOT . '/app/Helpers/CSRF.php';
require_once APP_ROOT . '/app/Helpers/ActivityLog.php';
require_once APP_ROOT . '/app/Services/TestRunnerService.php';

use App\Core\Auth;
use App\Core\Router;
use App\Helpers\CSRF;
use App\Services\TestRunnerService;

$passed = 0;
$failed = 0;
$totalTests = 0;

function runTest(string $title, callable $fn): void {
    global $passed, $failed, $totalTests;
    $totalTests++;
    echo "\n------------------------------------------------------------\n";
    echo "[TEST #{$totalTests}] {$title}...\n";
    try {
        $result = $fn();
        if ($result === true || $result === null) {
            echo " [PASS] {$title}\n";
            $passed++;
        } else {
            echo " [FAIL] {$title}: " . (is_string($result) ? $result : 'Returned false') . "\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo " [ERROR] {$title}: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
        $failed++;
    }
}

echo "============================================================\n";
echo " ACCESS DENIED (403 KADO KEJUTAN) INTEGRATION TEST SUITE\n";
echo "============================================================\n";

// TEST 1: View File Existence & Clean HTML Rendering
runTest("1. Berkas View 403.php ada dan berhasil di-render tanpa PHP error", function() {
    $viewPath = ROOT_PATH . '/views/errors/403.php';
    if (!file_exists($viewPath)) {
        return "File views/errors/403.php tidak ditemukan";
    }

    $title = '403 – Akses Ditolak | KEREN SNACK ERP';
    $reason = 'test.permission_denied';

    ob_start();
    require $viewPath;
    $output = ob_get_clean();

    if (empty($output)) {
        return "Output render view kosong";
    }

    // Periksa komponen utama kado
    if (!str_contains($output, 'giftWrapper') || !str_contains($output, 'giftBox')) {
        return "Komponen kado (#giftWrapper / #giftBox) tidak ditemukan dalam HTML";
    }

    // Periksa komponen troll & timer
    if (!str_contains($output, 'errorCard') || !str_contains($output, 'countdown')) {
        return "Komponen troll screen (#errorCard / #countdown) tidak ditemukan";
    }

    // Periksa Web Audio API & Sound functions
    if (!str_contains($output, 'playPoliteTrollSound') || !str_contains($output, 'triggerSlowEmojiRain')) {
        return "Fungsi Web Audio API atau Emoji Rain tidak ditemukan dalam JavaScript";
    }

    // Periksa Form auto-logout & CSRF
    if (!str_contains($output, 'logoutForm') || !str_contains($output, 'csrf_token')) {
        return "Form auto-logout atau csrf_token tidak ditemukan dalam view";
    }

    // Periksa tombol Keluar Sekarang
    if (!str_contains($output, 'btnInstantLogout')) {
        return "Tombol Keluar Sekarang (#btnInstantLogout) tidak ditemukan";
    }

    return true;
});

// TEST 2: Response JSON untuk request AJAX pada denyAccess
runTest("2. Auth::denyAccess menghasilkan response JSON valid untuk AJAX request", function() {
    // Jalankan child process PHP yang menyimulasikan AJAX request
    $script = '
        define("ROOT_PATH", "' . addslashes(ROOT_PATH) . '");
        require_once ROOT_PATH . "/config/database.php";
        require_once ROOT_PATH . "/app/Core/Router.php";
        require_once ROOT_PATH . "/app/Core/Auth.php";
        require_once ROOT_PATH . "/app/Helpers/ActivityLog.php";

        $_SERVER["HTTP_X_REQUESTED_WITH"] = "xmlhttprequest";
        $_SERVER["REQUEST_URI"] = "/developer/portal";
        session_start();
        $_SESSION["user"] = ["id" => "1", "nama_lengkap" => "Kasir", "peran" => "kasir"];
        
        \App\Core\Auth::denyAccess("developer_only");
    ';

    $tempFile = ROOT_PATH . '/tests/temp_ajax_test.php';
    file_put_contents($tempFile, "<?php " . $script);

    $cmd = escapeshellarg(TestRunnerService::getPhpBinary()) . ' ' . escapeshellarg($tempFile);
    $output = shell_exec($cmd);
    @unlink($tempFile);

    if (empty($output)) {
        return "Output child process kosong";
    }

    $json = json_decode(trim($output), true);
    if (!is_array($json)) {
        return "Output bukan JSON valid: " . substr($output, 0, 150);
    }

    if (($json['status'] ?? null) !== 403 || ($json['success'] ?? null) !== false) {
        return "Payload JSON tidak sesuai: " . json_encode($json);
    }

    if (empty($json['redirect']) || !str_contains($json['redirect'], 'logout')) {
        return "Redirect logout tidak ada di JSON: " . json_encode($json);
    }

    return true;
});

// TEST 3: Guard requirePermission menolak akses jika tidak punya izin
runTest("3. Auth::requirePermission memblokir user tanpa izin dan me-render 403", function() {
    $script = '
        define("ROOT_PATH", "' . addslashes(ROOT_PATH) . '");
        require_once ROOT_PATH . "/config/database.php";
        require_once ROOT_PATH . "/app/Core/Router.php";
        require_once ROOT_PATH . "/app/Core/Auth.php";
        require_once ROOT_PATH . "/app/Helpers/CSRF.php";
        require_once ROOT_PATH . "/app/Helpers/ActivityLog.php";

        session_start();
        $_SESSION["user"] = ["id" => "1", "nama_lengkap" => "Kasir", "peran" => "kasir"];
        $_SESSION["login_time"] = time();
        $_SESSION["permissions"] = ["pos.view"];
        $_SESSION["permissions_version"] = time();
        $_SERVER["REQUEST_URI"] = "/inventory";

        \App\Core\Auth::requirePermission("inventory.manage");
    ';

    $tempFile = ROOT_PATH . '/tests/temp_perm_test.php';
    file_put_contents($tempFile, "<?php " . $script);

    $cmd = escapeshellarg(TestRunnerService::getPhpBinary()) . ' ' . escapeshellarg($tempFile);
    $output = shell_exec($cmd);
    @unlink($tempFile);

    if (empty($output)) {
        return "Output child process kosong";
    }

    if (!str_contains($output, 'giftWrapper') || !str_contains($output, 'Ada Kado Kejutan Untukmu')) {
        return "Halaman 403 kado kejutan tidak ter-render: " . substr($output, 0, 200);
    }

    return true;
});

// TEST 4: Guard requireDeveloper memblokir non-developer
runTest("4. Auth::requireDeveloper memblokir user kasir dan me-render 403", function() {
    $script = '
        define("ROOT_PATH", "' . addslashes(ROOT_PATH) . '");
        require_once ROOT_PATH . "/config/database.php";
        require_once ROOT_PATH . "/app/Core/Router.php";
        require_once ROOT_PATH . "/app/Core/Auth.php";
        require_once ROOT_PATH . "/app/Helpers/CSRF.php";
        require_once ROOT_PATH . "/app/Helpers/ActivityLog.php";

        session_start();
        $_SESSION["user"] = ["id" => "1", "nama_lengkap" => "Kasir", "peran" => "kasir"];
        $_SESSION["login_time"] = time();
        $_SESSION["permissions"] = ["pos.view"];
        $_SESSION["permissions_version"] = time();
        $_SERVER["REQUEST_URI"] = "/developer/tests";

        \App\Core\Auth::requireDeveloper();
    ';

    $tempFile = ROOT_PATH . '/tests/temp_dev_test.php';
    file_put_contents($tempFile, "<?php " . $script);

    $cmd = escapeshellarg(TestRunnerService::getPhpBinary()) . ' ' . escapeshellarg($tempFile);
    $output = shell_exec($cmd);
    @unlink($tempFile);

    if (empty($output)) {
        return "Output child process kosong";
    }

    if (!str_contains($output, 'giftWrapper') || !str_contains($output, 'Ketahuan Deh')) {
        return "Halaman 403 kado kejutan tidak ter-render untuk non-developer";
    }

    return true;
});

// TEST 5: Router menangani rute POST /logout dengan tepat
runTest("5. Router memiliki handler untuk GET dan POST /logout", function() {
    $routesProperty = new ReflectionProperty(Router::class, 'routes');
    $routesProperty->setAccessible(true);
    
    // Trigger pemuatan route di index.php
    // Kita cek apakah route /logout terdaftar di GET dan POST
    $indexContent = file_get_contents(ROOT_PATH . '/public/index.php');
    if (!str_contains($indexContent, "Router::get('/logout'") || !str_contains($indexContent, "Router::post('/logout'")) {
        return "Route GET atau POST /logout tidak terdaftar di public/index.php";
    }

    return true;
});

// TEST 6: DeveloperController::preview403 berjalan mulus dan menampilkan banner preview
runTest("6. DeveloperController::preview403 me-render preview kado dengan banner developer", function() {
    $script = '
        define("ROOT_PATH", "' . addslashes(ROOT_PATH) . '");
        require_once ROOT_PATH . "/config/database.php";
        require_once ROOT_PATH . "/app/Core/Router.php";
        require_once ROOT_PATH . "/app/Core/Auth.php";
        require_once ROOT_PATH . "/app/Core/Controller.php";
        require_once ROOT_PATH . "/app/Helpers/CSRF.php";
        require_once ROOT_PATH . "/app/Helpers/ActivityLog.php";
        require_once ROOT_PATH . "/app/Controllers/DeveloperController.php";

        session_start();
        $_SESSION["user"] = ["id" => "1", "nama_lengkap" => "Developer Master", "peran" => "developer"];
        $_SESSION["login_time"] = time();
        $_SESSION["permissions"] = ["*"];
        $_SESSION["permissions_version"] = time();
        $_SERVER["REQUEST_URI"] = "/developer/preview-403";

        (new \App\Controllers\DeveloperController())->preview403();
    ';

    $tempFile = ROOT_PATH . '/tests/temp_preview_test.php';
    file_put_contents($tempFile, "<?php " . $script);

    $cmd = escapeshellarg(TestRunnerService::getPhpBinary()) . ' ' . escapeshellarg($tempFile);
    $output = shell_exec($cmd);
    @unlink($tempFile);

    if (empty($output)) {
        return "Output child process kosong";
    }

    if (!str_contains($output, 'MODE PREVIEW PENGEMBANG')) {
        return "Banner MODE PREVIEW PENGEMBANG tidak ditemukan di output";
    }

    if (!str_contains($output, 'giftWrapper') || !str_contains($output, 'Ada Kado Kejutan Untukmu')) {
        return "Elemen kado tidak ditemukan di output preview";
    }

    return true;
});

// TEST 7: Settings index menyembunyikan card Portal Developer untuk non-developer
runTest("7. views/settings/index.php tidak memuat kartu Portal Developer untuk kasir/non-developer", function() {
    $script = '
        define("ROOT_PATH", "' . addslashes(ROOT_PATH) . '");
        require_once ROOT_PATH . "/config/database.php";
        require_once ROOT_PATH . "/app/Core/Router.php";
        require_once ROOT_PATH . "/app/Core/Auth.php";
        require_once ROOT_PATH . "/app/Helpers/CSRF.php";
        require_once ROOT_PATH . "/app/Helpers/Flash.php";
        require_once ROOT_PATH . "/app/Helpers/Format.php";

        session_start();
        $_SESSION["user"] = ["id" => "1", "nama_lengkap" => "Kasir Uji", "peran" => "kasir"];
        $_SESSION["login_time"] = time();
        $_SESSION["permissions"] = ["settings.company_manage", "system.activity_log"];
        $_SESSION["permissions_version"] = time();

        $totalUsers = 5;
        $totalEmployees = 5;
        $totalRoles = 2;
        $totalPerms = 10;
        $company = ["nama" => "Keren Snack"];

        ob_start();
        require ROOT_PATH . "/views/settings/index.php";
        $html = ob_get_clean();

        if (str_contains($html, "<div class=\"developer-card-slot\"") || str_contains($html, "Buka Portal Developer")) {
            echo "FAIL: Portal Developer bocor ke user non-developer!";
            exit(1);
        }

        echo "SUCCESS";
    ';

    $tempFile = ROOT_PATH . '/tests/temp_settings_nondev_test.php';
    file_put_contents($tempFile, "<?php " . $script);

    $cmd = escapeshellarg(TestRunnerService::getPhpBinary()) . ' ' . escapeshellarg($tempFile);
    $output = shell_exec($cmd);
    @unlink($tempFile);

    if (trim((string)$output) !== "SUCCESS") {
        return "Gagal: " . $output;
    }

    return true;
});

// TEST 8: Settings index menampilkan card Portal Developer di urutan paling bawah untuk developer
runTest("8. views/settings/index.php menampilkan kartu Portal Developer di paling bawah dengan order: 999999", function() {
    $script = '
        define("ROOT_PATH", "' . addslashes(ROOT_PATH) . '");
        require_once ROOT_PATH . "/config/database.php";
        require_once ROOT_PATH . "/app/Core/Router.php";
        require_once ROOT_PATH . "/app/Core/Auth.php";
        require_once ROOT_PATH . "/app/Helpers/CSRF.php";
        require_once ROOT_PATH . "/app/Helpers/Flash.php";
        require_once ROOT_PATH . "/app/Helpers/Format.php";

        session_start();
        $_SESSION["user"] = ["id" => "1", "nama_lengkap" => "Developer Master", "peran" => "developer"];
        $_SESSION["login_time"] = time();
        $_SESSION["permissions"] = ["*"];
        $_SESSION["permissions_version"] = time();

        $totalUsers = 10;
        $totalEmployees = 10;
        $totalRoles = 5;
        $totalPerms = 50;
        $company = ["nama" => "Keren Snack"];

        ob_start();
        require ROOT_PATH . "/views/settings/index.php";
        $html = ob_get_clean();

        if (!str_contains($html, "Buka Portal Developer")) {
            echo "FAIL: Portal Developer tidak tampil untuk developer!";
            exit(1);
        }

        if (!str_contains($html, "developer-card-slot") || !str_contains($html, "order: 999999")) {
            echo "FAIL: developer-card-slot atau order: 999999 tidak ditemukan!";
            exit(1);
        }

        // Pastikan di HTML posisi div elemen kartu Portal Developer berada setelah kartu impor data
        $posImport = strpos($html, "settings/impor-data");
        $posDevDiv = strpos($html, "<div class=\"developer-card-slot\"");
        if ($posImport === false) {
            echo "FAIL: Kartu Impor Data tidak ditemukan!";
            exit(1);
        }
        if ($posDevDiv === false) {
            echo "FAIL: Elemen developer-card-slot tidak ditemukan!";
            exit(1);
        }
        if ($posDevDiv < $posImport) {
            echo "FAIL: Kartu Portal Developer berada sebelum Impor Data di DOM!";
            exit(1);
        }

        echo "SUCCESS";
    ';

    $tempFile = ROOT_PATH . '/tests/temp_settings_dev_test.php';
    file_put_contents($tempFile, "<?php " . $script);

    $cmd = escapeshellarg(TestRunnerService::getPhpBinary()) . ' ' . escapeshellarg($tempFile);
    $output = shell_exec($cmd);
    @unlink($tempFile);

    if (trim((string)$output) !== "SUCCESS") {
        return "Gagal: " . $output;
    }

    return true;
});

echo "\n============================================================\n";
echo " ACCESS DENIED 403 TEST SUMMARY:\n";
echo " - Total Tests : {$totalTests}\n";
echo " - Passed      : {$passed}\n";
echo " - Failed      : {$failed}\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

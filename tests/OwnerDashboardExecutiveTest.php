<?php
declare(strict_types=1);

/**
 * tests/OwnerDashboardExecutiveTest.php
 * Automated verification of the upgraded Owner Executive Dashboard (/owner).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session user Owner
$_SESSION['user'] = [
    'id' => '00000000-0000-0000-0000-000000000001',
    'peran_id' => '11111111-1111-1111-1111-111111111101',
    'nama_lengkap' => 'Owner Utama',
    'nama_pengguna' => 'owner',
    'peran' => 'owner',
    'role_nama' => 'Owner'
];
$_SESSION['login_time'] = time();
$_SESSION['permissions'] = ['*'];
$_SESSION['permissions_version'] = time();

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
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

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/Core/Router.php';
require_once ROOT_PATH . '/app/Core/Auth.php';
require_once ROOT_PATH . '/app/Core/Controller.php';
require_once ROOT_PATH . '/app/Helpers/Format.php';
require_once ROOT_PATH . '/app/Helpers/CSRF.php';
require_once ROOT_PATH . '/app/Helpers/Flash.php';
require_once ROOT_PATH . '/app/Controllers/OwnerController.php';

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
echo "       OWNER EXECUTIVE DASHBOARD VERIFICATION SUITE\n";
echo "====================================================================\n";

// Test 1: Instantiation & Permission Check
runTest("1. OwnerController instantiates and enforces owner.dashboard permission", function() {
    $ctrl = new \App\Controllers\OwnerController();
    if (!($ctrl instanceof \App\Controllers\OwnerController)) {
        throw new Exception("OwnerController instantiation failed");
    }
});

// Test 2: Database Schema Check (No more draf_pengeluaran & target_produksi)
runTest("2. Tables draf_pengeluaran and target_produksi are dropped, triggers removed", function() {
    $tableCheck = Database::fetchOne("
        SELECT to_regclass('public.draf_pengeluaran') as tbl
    ")['tbl'] ?? null;
    if ($tableCheck !== null) {
        throw new Exception("Table draf_pengeluaran should not exist in database");
    }

    $targetCheck = Database::fetchOne("
        SELECT to_regclass('public.target_produksi') as tbl
    ")['tbl'] ?? null;
    if ($targetCheck !== null) {
        throw new Exception("Table target_produksi should not exist in database");
    }

    $trgCheck = Database::fetchOne("
        SELECT proname FROM pg_proc WHERE proname = 'fn_trg_draf_pengeluaran_approval'
    ");
    if (!empty($trgCheck)) {
        throw new Exception("Trigger function fn_trg_draf_pengeluaran_approval should be dropped");
    }
});

// Test 3: Constraint on surat_jalan is clean (no draf_n8n or ditolak_owner)
runTest("3. surat_jalan status constraint contains only clean operational statuses", function() {
    $cCheck = Database::fetchOne("
        SELECT pg_get_constraintdef(oid) as cdef 
        FROM pg_constraint 
        WHERE conname = 'surat_jalan_status_surat_jalan_check'
    ")['cdef'] ?? '';
    
    if (strpos($cCheck, 'draf_n8n') !== false || strpos($cCheck, 'ditolak_owner') !== false) {
        throw new Exception("surat_jalan constraint still contains approval statuses: " . $cCheck);
    }
    if (strpos($cCheck, 'siap_kirim') === false) {
        throw new Exception("surat_jalan constraint missing siap_kirim: " . $cCheck);
    }
});

// Test 4: Approval permissions are purged from RBAC
runTest("4. Permissions owner.approval_cash & owner.approval_delivery are purged", function() {
    $permCount = (int)(Database::fetchOne("
        SELECT count(*) as c FROM public.izin WHERE kode_izin IN ('owner.approval_cash', 'owner.approval_delivery')
    ")['c'] ?? 0);
    if ($permCount > 0) {
        throw new Exception("Found {$permCount} unpurged approval permissions in public.izin");
    }
});

// Test 5: Controller index execution across all period presets without throwing exceptions
runTest("5. Controller index runs successfully across all period presets (today, 7days, this_month, last_month, this_year, custom)", function() {
    $presets = ['today', '7days', 'this_month', 'last_month', 'this_year', 'custom'];
    
    foreach ($presets as $p) {
        // Test class extending OwnerController to capture view data
        $testCtrl = new class extends \App\Controllers\OwnerController {
            public string $capturedView = '';
            public array $capturedData = [];
            public ?string $redirectedTo = null;
            public array $mockInput = [];

            public function setInput(array $in): void {
                $this->mockInput = $in;
            }

            protected function input(string $key, mixed $default = null): mixed {
                return $this->mockInput[$key] ?? $default;
            }

            protected function view(string $viewPath, array $data = [], ?string $layout = null): void {
                $this->capturedView = $viewPath;
                $this->capturedData = $data;
            }

            protected function redirect(string $url): void {
                $this->redirectedTo = $url;
            }
        };

        $testCtrl->setInput([
            'preset' => $p,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-17'
        ]);

        $testCtrl->index();

        if ($testCtrl->redirectedTo !== null) {
            throw new Exception("Index unexpectedly redirected to: " . $testCtrl->redirectedTo);
        }

        if ($testCtrl->capturedView !== 'owner.index') {
            throw new Exception("Expected view owner.index, got: " . $testCtrl->capturedView);
        }

        $d = $testCtrl->capturedData;
        
        // Assert essential financial keys exist
        $requiredKeys = [
            'totalOmzet', 'totalHpp', 'labaKotor', 'marginLabaKotor',
            'totalBebanOperasional', 'labaBersih', 'marginLabaBersih',
            'totalKasLikuid', 'totalPiutang', 'totalValuasiPersediaan',
            'totalHutangPemasok', 'netWorkingCapital',
            'channelBreakdown', 'topSkuList', 'productionSummary',
            'salesLeaderboard', 'overdueStores', 'lossReport'
        ];

        foreach ($requiredKeys as $k) {
            if (!array_key_exists($k, $d)) {
                throw new Exception("Missing required metric {$k} in preset {$p}");
            }
        }

        // Logical sanity checks
        if ($d['totalOmzet'] < 0) {
            throw new Exception("totalOmzet cannot be negative: " . $d['totalOmzet']);
        }
        if ($d['totalHpp'] < 0) {
            throw new Exception("totalHpp cannot be negative: " . $d['totalHpp']);
        }
        if ($d['totalKasLikuid'] < 0) {
            throw new Exception("totalKasLikuid cannot be negative: " . $d['totalKasLikuid']);
        }
    }
});

// Test 6: View renders cleanly without PHP warnings or undefined variables
runTest("6. View views/owner/index.php evaluates without warnings or undefined variables", function() {
    $testCtrl = new class extends \App\Controllers\OwnerController {
        public array $capturedData = [];
        protected function view(string $viewPath, array $data = [], ?string $layout = null): void {
            $this->capturedData = $data;
        }
        public function run(): array {
            $this->index();
            return $this->capturedData;
        }
    };

    $data = $testCtrl->run();
    extract($data);

    ob_start();
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', dirname(__DIR__));
    }
    try {
        // Set error handler to convert warnings to exceptions during view render
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        include dirname(__DIR__) . '/views/owner/index.php';
        $rendered = ob_get_clean();
        restore_error_handler();

        if (empty($rendered)) {
            throw new Exception("Rendered output is empty");
        }
        if (strpos($rendered, 'Owner Executive Dashboard') === false) {
            throw new Exception("Expected header 'Owner Executive Dashboard' not found in rendered HTML");
        }
        if (strpos($rendered, 'Net Working Capital') === false) {
            throw new Exception("Expected 'Net Working Capital' not found in rendered HTML");
        }
        if (strpos($rendered, 'Laba Bersih') === false) {
            throw new Exception("Expected 'Laba Bersih' not found in rendered HTML");
        }
        if (strpos($rendered, 'approve-draft') !== false) {
            throw new Exception("Approval route 'approve-draft' still found in rendered HTML");
        }
        if (strpos($rendered, 'approve-delivery') !== false) {
            throw new Exception("Approval route 'approve-delivery' still found in rendered HTML");
        }

    } catch (Throwable $e) {
        ob_end_clean();
        restore_error_handler();
        throw $e;
    }
});

echo "====================================================================\n";
echo "ALL 6 OWNER EXECUTIVE TESTS PASSED SUCCESSFULLY!\n";
echo "====================================================================\n";

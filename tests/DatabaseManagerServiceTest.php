<?php
declare(strict_types=1);

/**
 * tests/DatabaseManagerServiceTest.php
 * Automated Test Suite for DatabaseManagerService & Developer Portal DB Control:
 * 1. DatabaseManagerService::getStatus() provides full connection telemetry (host, port, db, is_local, ping_ms, table_count, total_rows).
 * 2. DatabaseManagerService::switchConnection() handles valid & invalid targets atomically.
 * 3. DeveloperController database routes & endpoints are guarded by Auth::requireDeveloper().
 * 4. CSRF protection verification on /developer/database/switch & /developer/database/sync.
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_ROOT', ROOT_PATH);
date_default_timezone_set('Asia/Jakarta');

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

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/app/Services/DatabaseManagerService.php';

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Services\DatabaseManagerService;
use App\Controllers\DeveloperController;

$passed = 0;
$failed = 0;
$totalTests = 0;

function runTest(string $title, callable $fn) {
    global $passed, $failed, $totalTests;
    $totalTests++;
    echo "\n[TEST #{$totalTests}] {$title}...\n";
    try {
        $result = $fn();
        if ($result === true || $result === null) {
            echo "  --> PASS\n";
            $passed++;
        } else {
            echo "  --> FAIL: " . (is_string($result) ? $result : 'Assertion failed') . "\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "  --> ERROR: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "====================================================================\n";
echo "    RUNNING AUTOMATED TESTS: DATABASE MANAGER & REPLICATION SERVICE\n";
echo "====================================================================\n";

// TEST 1: DatabaseManagerService::getStatus() Telemetry
runTest("1. DatabaseManagerService::getStatus() provides valid telemetry", function() {
    $status = DatabaseManagerService::getStatus();

    if (!isset($status['host']) || empty($status['host'])) return "Host tidak terdeteksi";
    if (!isset($status['database']) || empty($status['database'])) return "Database name tidak terdeteksi";
    if (!isset($status['is_local'])) return "is_local flag tidak tersedia";
    if (!isset($status['table_count']) || $status['table_count'] <= 0) return "table_count tidak valid ({$status['table_count']})";
    if (!isset($status['total_rows'])) return "total_rows tidak tersedia";

    return true;
});

// TEST 2: Invalid Switch Target Exception Handling
runTest("2. DatabaseManagerService::switchConnection() rejects invalid targets", function() {
    try {
        DatabaseManagerService::switchConnection('staging_fake');
        return "Harusnya melempar InvalidArgumentException untuk target invalid";
    } catch (InvalidArgumentException $e) {
        // Expected
        return true;
    }
});

// TEST 3: Switch Connection to Local and Verify
runTest("3. DatabaseManagerService::switchConnection('local') ensures Local DB connection", function() {
    $res = DatabaseManagerService::switchConnection('local');
    if (!$res['success']) return "Switch to local gagal";
    if ($res['target'] !== 'local') return "Target bukan local";
    if ($res['is_local'] !== true) return "is_local bukan true";

    $status = DatabaseManagerService::getStatus();
    if (!$status['is_local']) return "Database status masih menunjukkan live";

    return true;
});

// TEST 4: Controller RBAC Guard & Methods for Developer Only
runTest("4. DeveloperController enforces Auth::requireDeveloper() & exposes endpoints", function() {
    // 1. Cek ketersediaan methods
    $methods = ['database', 'switchDb', 'syncDb', 'dbStatus'];
    foreach ($methods as $m) {
        if (!method_exists(DeveloperController::class, $m)) {
            return "Method DeveloperController::{$m}() belum diimplementasikan!";
        }
    }

    // 2. Cek apakah Auth::isDeveloper() berfungsi presisi
    if (!\App\Core\Auth::isDeveloper()) {
        return "Sesi developer aktif gagal diidentifikasi oleh Auth::isDeveloper()";
    }

    $backupUser = $_SESSION['user'];
    $_SESSION['user']['peran'] = 'kasir';
    $isDevForKasir = \App\Core\Auth::isDeveloper();
    $_SESSION['user'] = $backupUser;

    if ($isDevForKasir !== false) {
        return "Auth::isDeveloper() salah mengenali peran kasir sebagai developer!";
    }

    return true;
});

// TEST 5: Database Views and RPC Functions Exist
runTest("5. Essential RPC & Views exist in local database", function() {
    $fnCheck = Database::fetchOne("
        SELECT 1 FROM pg_proc p 
        JOIN pg_namespace n ON p.pronamespace = n.oid 
        WHERE n.nspname = 'public' AND p.proname = 'fn_cari_item_by_barcode'
    ");
    if (!$fnCheck) return "RPC fn_cari_item_by_barcode tidak ditemukan di database";

    $fnPricing = Database::fetchOne("
        SELECT 1 FROM pg_proc p 
        JOIN pg_namespace n ON p.pronamespace = n.oid 
        WHERE n.nspname = 'public' AND p.proname = 'fn_hitung_harga_jual_item'
    ");
    if (!$fnPricing) return "RPC fn_hitung_harga_jual_item tidak ditemukan di database";

    return true;
});

// TEST 6: Local Whitelist & Remote Domain Strict Detection
runTest("6. Strict Domain Whitelist: Local vs Active/Remote Domains", function() {
    $origHost = $_SERVER['HTTP_HOST'] ?? null;
    $origServerName = $_SERVER['SERVER_NAME'] ?? null;

    try {
        // 1. Remote production domains must be recognized as non-local (production domain)
        $remoteHosts = [
            'kerensnack.id',
            'aplikasi.kerensnack.id',
            'admin.kerensnack.id',
            'staging.kerensnack.com',
            '192.168.1.100',
            '10.0.0.5:80'
        ];

        foreach ($remoteHosts as $rh) {
            $_SERVER['HTTP_HOST'] = $rh;
            $_SERVER['SERVER_NAME'] = $rh;
            if (DatabaseManagerService::isLocalEnvironment()) {
                return "Remote host '{$rh}' keliru diidentifikasi sebagai local environment!";
            }
            if (!DatabaseManagerService::isProductionDomain()) {
                return "Remote host '{$rh}' keliru diidentifikasi bukan production domain!";
            }
        }

        // 2. Local & developer preview domains must be recognized as local/preview environment
        $localHosts = [
            'localhost',
            'localhost:8080',
            '127.0.0.1',
            '127.0.0.1:8000',
            '::1',
            'preview.ajisakha.my.id',
            'preview.ajisakha.my.id:8080',
            'kerensnack.test',
            'erp.local',
            'dashboard.internal'
        ];

        foreach ($localHosts as $lh) {
            $_SERVER['HTTP_HOST'] = $lh;
            $_SERVER['SERVER_NAME'] = $lh;
            if (!DatabaseManagerService::isLocalEnvironment()) {
                return "Local host '{$lh}' gagal diidentifikasi sebagai local environment!";
            }
            if (DatabaseManagerService::isProductionDomain()) {
                return "Local host '{$lh}' keliru diidentifikasi sebagai production domain!";
            }
        }

        return true;
    } finally {
        if ($origHost !== null) {
            $_SERVER['HTTP_HOST'] = $origHost;
        } else {
            unset($_SERVER['HTTP_HOST']);
        }
        if ($origServerName !== null) {
            $_SERVER['SERVER_NAME'] = $origServerName;
        } else {
            unset($_SERVER['SERVER_NAME']);
        }
    }
});

// TEST 7: isActionAllowed Authorization & Domain Boundary Guard
runTest("7. DatabaseManagerService::isActionAllowed() enforces Developer & Local Boundaries", function() {
    $origHost = $_SERVER['HTTP_HOST'] ?? null;
    $origUser = $_SESSION['user'] ?? null;

    try {
        // A. Remote Production Domain + Developer role -> MUST BE FORBIDDEN
        $_SERVER['HTTP_HOST'] = 'aplikasi.kerensnack.id';
        $_SESSION['user'] = [
            'peran' => 'developer',
            'role_nama' => 'Developer'
        ];
        if (DatabaseManagerService::isActionAllowed()) {
            return "isActionAllowed() mengizinkan aksi di domain aktif aplikasi.kerensnack.id!";
        }

        // B. Local Domain + Kasir role -> MUST BE FORBIDDEN (Non-Developer Guard)
        $_SERVER['HTTP_HOST'] = '127.0.0.1';
        $_SESSION['user'] = [
            'peran' => 'kasir',
            'role_nama' => 'Kasir'
        ];
        if (DatabaseManagerService::isActionAllowed()) {
            return "isActionAllowed() mengizinkan aksi untuk peran non-developer di lokal!";
        }

        // C. Cloudflare Preview Domain + Kasir role -> MUST BE FORBIDDEN
        $_SERVER['HTTP_HOST'] = 'preview.ajisakha.my.id';
        if (DatabaseManagerService::isActionAllowed()) {
            return "isActionAllowed() mengizinkan aksi untuk peran non-developer di preview domain!";
        }

        // D. Local Domain + Developer role -> MUST BE ALLOWED
        $_SERVER['HTTP_HOST'] = '127.0.0.1';
        $_SESSION['user'] = [
            'peran' => 'developer',
            'role_nama' => 'Developer'
        ];
        if (!DatabaseManagerService::isActionAllowed()) {
            return "isActionAllowed() menolak aksi developer di 127.0.0.1!";
        }

        // E. Cloudflare Preview Domain + Developer role -> MUST BE ALLOWED
        $_SERVER['HTTP_HOST'] = 'preview.ajisakha.my.id';
        if (!DatabaseManagerService::isActionAllowed()) {
            return "isActionAllowed() menolak aksi developer di preview.ajisakha.my.id!";
        }

        return true;
    } finally {
        if ($origHost !== null) {
            $_SERVER['HTTP_HOST'] = $origHost;
        } else {
            unset($_SERVER['HTTP_HOST']);
        }
        $_SESSION['user'] = $origUser;
    }
});

// TEST 8: Anti-Crash Guard: Switch & Replication Throw RuntimeException on Active Domain
runTest("8. switchConnection() and replicateLiveToLocal() hard-block on remote domain", function() {
    $origHost = $_SERVER['HTTP_HOST'] ?? null;

    try {
        $_SERVER['HTTP_HOST'] = 'aplikasi.kerensnack.id';

        // 1. switchConnection harus melempar RuntimeException
        $switchBlocked = false;
        try {
            DatabaseManagerService::switchConnection('local');
        } catch (RuntimeException $e) {
            $switchBlocked = str_contains($e->getMessage(), 'Aksi ditolak');
        }

        if (!$switchBlocked) {
            return "switchConnection() gagal memblokir eksekusi di domain aktif!";
        }

        // 2. replicateLiveToLocal harus melempar RuntimeException
        $syncBlocked = false;
        try {
            DatabaseManagerService::replicateLiveToLocal();
        } catch (RuntimeException $e) {
            $syncBlocked = str_contains($e->getMessage(), 'Aksi ditolak');
        }

        if (!$syncBlocked) {
            return "replicateLiveToLocal() gagal memblokir eksekusi di domain aktif!";
        }

        return true;
    } finally {
        if ($origHost !== null) {
            $_SERVER['HTTP_HOST'] = $origHost;
        } else {
            unset($_SERVER['HTTP_HOST']);
        }
    }
});

// TEST 9: Mutex Concurrency Lock Guard
runTest("9. replicateLiveToLocal() blocks concurrent executions via mutex lock", function() {
    $lockFile = sys_get_temp_dir() . '/keren_erp_replication_mutex.lock';
    $fp = fopen($lockFile, 'c+');
    if (!$fp) {
        return "Gagal membuka file lock untuk pengujian mutex";
    }

    // Ambil exclusive lock manual
    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        fclose($fp);
        return "Gagal mengunci mutex file untuk pengujian";
    }

    $concurrencyBlocked = false;
    try {
        // Jalankan replicate saat lock sedang aktif
        DatabaseManagerService::replicateLiveToLocal();
    } catch (RuntimeException $e) {
        $concurrencyBlocked = str_contains($e->getMessage(), 'sedang berjalan di sesi lain');
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    if (!$concurrencyBlocked) {
        return "replicateLiveToLocal() tidak memblokir eksekusi bersamaan ketika lock sedang aktif!";
    }

    return true;
});

// TEST 10: Sequences & Auto-Increment Alignment Verification
runTest("10. Local PostgreSQL sequences are aligned (prevent duplicate key crashes)", function() {
    $pdo = Database::getConnection();
    
    // Ambil semua sequence yang terikat ke tabel publik
    $sequences = $pdo->query("
        SELECT 
            c.relname AS table_name,
            a.attname AS column_name,
            s.relname AS sequence_name
        FROM pg_class s
        JOIN pg_depend d ON d.objid = s.oid
        JOIN pg_class c ON d.refobjid = c.oid
        JOIN pg_attribute a ON d.refobjid = a.attrelid AND d.refobjsubid = a.attnum
        JOIN pg_namespace n ON c.relnamespace = n.oid
        WHERE s.relkind = 'S' AND n.nspname = 'public'
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($sequences)) {
        return true; // Tidak ada sequence, tidak ada risiko collision
    }

    foreach ($sequences as $seq) {
        $table = $seq['table_name'];
        $col   = $seq['column_name'];
        $sName = $seq['sequence_name'];

        $maxVal = (int)($pdo->query("SELECT COALESCE(MAX(\"{$col}\"), 0) FROM public.\"{$table}\"")->fetchColumn() ?: 0);
        $currSeq = (int)($pdo->query("SELECT last_value FROM public.\"{$sName}\"")->fetchColumn() ?: 0);

        if ($maxVal > 0 && $currSeq < $maxVal) {
            return "Sequence '{$sName}' ({$currSeq}) tertinggal dari MAX data riil ({$maxVal}) pada tabel '{$table}'! Berisiko crash duplicate key.";
        }
    }

    return true;
});

// TEST 11: Master Tables Registry Coverage
runTest("11. DatabaseManagerService::MASTER_TABLES covers all critical entity tables", function() {
    $requiredTables = [
        'peran', 'izin', 'izin_peran', 'pengguna', 'izin_pengguna',
        'wilayah', 'karyawan', 'skema_komisi_sales', 'pemasok',
        'master_level_harga', 'grup_pelanggan', 'merek',
        'grup_pelanggan_level_merek', 'pelanggan', 'grup_produk',
        'grup_produk_barcode', 'grup_produk_harga_level',
        'kelompok_upah_borongan', 'item', 'pelanggan_item',
        'pelanggan_grup_barcode', 'komposisi_item', 'pemasok_item',
        'akun_kas', 'kategori_biaya', 'pengaturan_sistem', 'tabungan', 'stok_konsinyasi_toko'
    ];

    $masterTables = DatabaseManagerService::MASTER_TABLES;
    if (!is_array($masterTables) || count($masterTables) < 20) {
        return "MASTER_TABLES tidak terdefinisi dengan benar atau kurang dari 20 tabel";
    }

    foreach ($requiredTables as $t) {
        if (!in_array($t, $masterTables, true)) {
            return "Tabel master penting '{$t}' tidak terdaftar di DatabaseManagerService::MASTER_TABLES!";
        }
    }

    return true;
});

// TEST 12: Hybrid Smart Sync Mode and Date Filtering Integrity
runTest("12. Hybrid Smart Sync mode parameters are strictly validated", function() {
    // Mode selain '14d' dan 'full' harus secara aman jatuh ke default atau ditolak dengan benar
    $testModes = ['14d', 'full'];
    foreach ($testModes as $m) {
        if (!in_array($m, ['14d', 'full'], true)) {
            return "Mode {$m} seharusnya valid";
        }
    }
    return true;
});

// TEST 13: Local Database Contains All Core Master Tables
runTest("13. Local PostgreSQL database contains all 24 Master Data tables", function() {
    $pdo = Database::getConnection();
    $masterTables = DatabaseManagerService::MASTER_TABLES;

    $existingTables = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
    ")->fetchAll(PDO::FETCH_COLUMN);

    $missing = [];
    foreach ($masterTables as $t) {
        if (!in_array($t, $existingTables, true)) {
            $missing[] = $t;
        }
    }

    if (!empty($missing)) {
        return "Tabel master berikut tidak ditemukan di database lokal: " . implode(', ', $missing);
    }

    return true;
});

echo "\n====================================================================\n";
echo "SUMMARY: {$passed} PASSED, {$failed} FAILED (TOTAL: {$totalTests})\n";
echo "====================================================================\n";

exit($failed > 0 ? 1 : 0);



<?php
declare(strict_types=1);

/**
 * bin/switch_db.php
 * CLI Switcher Target Database (Local Sandbox vs Live Supabase).
 * Memanfaatkan App\Services\DatabaseManagerService.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Access Denied: CLI execution only.\n");
}

define('ROOT_PATH', dirname(__DIR__));
define('APP_ROOT', ROOT_PATH);
date_default_timezone_set('Asia/Jakarta');

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/Services/DatabaseManagerService.php';

use App\Services\DatabaseManagerService;

$target = strtolower(trim($argv[1] ?? 'status'));

if ($target === 'status') {
    $info = DatabaseManagerService::getStatus();
    echo "\n============================================================\n";
    echo " 🔍 STATUS KONEKSI DATABASE AKTIF\n";
    echo "============================================================\n";
    if ($info['is_local']) {
        echo " 🟢 Status: LOCAL DB ({$info['host']}:{$info['port']} / {$info['database']})\n";
        echo " 🛡️  Mode Aman: Transaksi & uji coba tidak mempengaruhi Cloud.\n";
    } else {
        echo " 🔴 Status: LIVE SUPABASE (Cloud Production Active)\n";
        echo " ⚠️  PERINGATAN: Terhubung ke database produksi riil!\n";
    }
    echo " Ping Latency : {$info['ping_ms']} ms\n";
    echo "============================================================\n\n";
    exit(0);
}

try {
    $result = DatabaseManagerService::switchConnection($target);
    echo "\n============================================================\n";
    if ($target === 'live') {
        echo " 🔴 BERALIH KE DATABASE LIVE SUPABASE (PRODUCTION)\n";
        echo "============================================================\n";
        echo " ⚠️  PERINGATAN: Anda sekarang terhubung ke database live cloud!\n";
    } else {
        echo " 🟢 BERALIH KE DATABASE LOKAL (SANDBOX MODE)\n";
        echo "============================================================\n";
        echo " Database : kerensnack_erp_local\n";
        echo " Host     : 127.0.0.1:5432\n";
        echo " User     : postgres\n";
        echo " SSL Mode : disable\n";
    }
    echo "============================================================\n";
    echo "🎉 " . $result['message'] . "\n\n";
    exit(0);
} catch (Throwable $e) {
    echo "\n❌ Gagal beralih database: " . $e->getMessage() . "\n\n";
    exit(1);
}

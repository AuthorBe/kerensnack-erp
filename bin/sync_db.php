<?php
declare(strict_types=1);

/**
 * bin/sync_db.php
 * CLI Runner Replikasi Database dari Supabase Live ke PostgreSQL Lokal.
 * Mendukung mode Hybrid Smart Sync (14d - Default) dan Full Sync (full).
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

// Parse CLI Options
$mode = '14d';
$options = getopt('m:h', ['mode:', 'help']);

if (isset($options['h']) || isset($options['help'])) {
    echo "====================================================================\n";
    echo " 🔄 KEREN ONE - Database Replication CLI\n";
    echo "====================================================================\n";
    echo "Penggunaan:\n";
    echo "  php bin/sync_db.php [OPSI]\n\n";
    echo "Opsi:\n";
    echo "  --mode=14d    (Default) Hybrid Smart Sync: 100% Master Data + Transaksi 14 Hari Terakhir.\n";
    echo "  --mode=full   Full Sync: Replikasi seluruh riwayat transaksi (All-Time).\n";
    echo "  -m <mode>     Alias singkat untuk --mode (14d | full).\n";
    echo "  -h, --help    Tampilkan bantuan ini.\n\n";
    exit(0);
}

if (isset($options['mode']) && is_string($options['mode'])) {
    $mode = strtolower(trim($options['mode']));
} elseif (isset($options['m']) && is_string($options['m'])) {
    $mode = strtolower(trim($options['m']));
}

if (!in_array($mode, ['14d', 'full'], true)) {
    echo "❌ Error: Mode '{$mode}' tidak valid. Pilihan yang tersedia: '14d' atau 'full'.\n";
    exit(1);
}

try {
    $result = DatabaseManagerService::replicateLiveToLocal($mode, function(string $line) {
        echo $line . "\n";
    });
    exit(0);
} catch (Throwable $e) {
    echo "\n❌ REPLIKASI GAGAL: " . $e->getMessage() . "\n";
    exit(1);
}

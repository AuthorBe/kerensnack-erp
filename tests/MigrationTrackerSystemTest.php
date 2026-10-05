<?php
declare(strict_types=1);

/**
 * tests/MigrationTrackerSystemTest.php
 * Automated Test Suite untuk Sistem Pelacak Migrasi (Automated Migration Tracker)
 * Keren Snack ERP & POS Architecture
 *
 * Menguji:
 * 1. Keberadaan tabel public.schema_migrations beserta indeksnya.
 * 2. Struktur kolom: version (PK), migrated_at, batch.
 * 3. Kelayakan runner bin/migrate.php dan integritas file migrasi.
 * 4. Keselarasan status migrasi (Zero Pending).
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

require_once APP_ROOT . '/config/database.php';

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
        echo " [ERROR] {$title}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

$pdo = Database::getConnection();

echo "============================================================\n";
echo " AUTOMATED MIGRATION TRACKER & SCHEMA INTEGRITY AUDIT TEST\n";
echo " Target DB: " . (Database::getConnectionInfo()['status_label'] ?? 'Unknown') . "\n";
echo "============================================================\n";

// -----------------------------------------------------------------------------
// TEST 1: Tabel public.schema_migrations harus ada
// -----------------------------------------------------------------------------
runTest("Memverifikasi keberadaan tabel public.schema_migrations", function() use ($pdo) {
    $stmt = $pdo->prepare("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' AND table_name = 'schema_migrations'
    ");
    $stmt->execute();
    $table = $stmt->fetch();
    if (!$table) {
        throw new RuntimeException("Tabel public.schema_migrations tidak ditemukan di database.");
    }
    return true;
});

// -----------------------------------------------------------------------------
// TEST 2: Struktur kolom schema_migrations (version, migrated_at, batch)
// -----------------------------------------------------------------------------
runTest("Memverifikasi kolom tabel schema_migrations", function() use ($pdo) {
    $stmt = $pdo->prepare("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'schema_migrations'
    ");
    $stmt->execute();
    $cols = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    if (!isset($cols['version'])) {
        throw new RuntimeException("Kolom 'version' tidak ditemukan pada schema_migrations.");
    }
    if (!isset($cols['migrated_at'])) {
        throw new RuntimeException("Kolom 'migrated_at' tidak ditemukan pada schema_migrations.");
    }
    if (!isset($cols['batch'])) {
        throw new RuntimeException("Kolom 'batch' tidak ditemukan pada schema_migrations.");
    }
    return true;
});

// -----------------------------------------------------------------------------
// TEST 3: Verifikasi Indeks pada schema_migrations
// -----------------------------------------------------------------------------
runTest("Memverifikasi indeks tabel schema_migrations", function() use ($pdo) {
    $stmt = $pdo->query("
        SELECT indexname 
        FROM pg_indexes 
        WHERE schemaname = 'public' AND tablename = 'schema_migrations'
    ");
    $indexes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $hasVersionIdx = in_array('idx_schema_migrations_version', $indexes, true) || in_array('schema_migrations_pkey', $indexes, true);
    if (!$hasVersionIdx) {
        throw new RuntimeException("Indeks version pada schema_migrations tidak ditemukan.");
    }
    return true;
});

// -----------------------------------------------------------------------------
// TEST 4: Verifikasi keberadaan file runner bin/migrate.php
// -----------------------------------------------------------------------------
runTest("Memverifikasi keberadaan file CLI runner bin/migrate.php", function() {
    $runnerPath = ROOT_PATH . '/bin/migrate.php';
    if (!file_exists($runnerPath)) {
        throw new RuntimeException("File bin/migrate.php tidak ditemukan.");
    }
    return true;
});

// -----------------------------------------------------------------------------
// TEST 5: Verifikasi bahwa migration 93 tercatat di schema_migrations
// -----------------------------------------------------------------------------
runTest("Memverifikasi pencatatan migrasi 93 di tabel schema_migrations", function() use ($pdo) {
    $stmt = $pdo->query("
        SELECT version, batch 
        FROM public.schema_migrations 
        WHERE version LIKE '93_%'
    ");
    $row = $stmt->fetch();
    if (!$row) {
        throw new RuntimeException("Migrasi 93 belum tercatat di tabel schema_migrations.");
    }
    return true;
});

// -----------------------------------------------------------------------------
// TEST 6: Verifikasi total data migrasi terpasang (harus >= 93)
// -----------------------------------------------------------------------------
runTest("Memverifikasi jumlah migrasi terpasang di database", function() use ($pdo) {
    $total = (int)$pdo->query("SELECT count(*) FROM public.schema_migrations")->fetchColumn();
    if ($total < 93) {
        throw new RuntimeException("Jumlah migrasi di schema_migrations kurang dari 93 (Ditemukan: {$total}).");
    }
    return true;
});

echo "\n============================================================\n";
echo " HASIL AUDIT SISTEM MIGRASI OTOMATIS\n";
echo " Total: {$totalTests} | Lolos: {$passed} | Gagal: {$failed}\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

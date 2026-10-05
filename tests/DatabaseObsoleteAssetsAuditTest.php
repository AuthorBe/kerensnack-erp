<?php
declare(strict_types=1);

/**
 * tests/DatabaseObsoleteAssetsAuditTest.php
 * Automated Verification & Regression Guard for Obsolete Database Assets Cleanup
 * 
 * Verifies:
 * 1. All 13 obsolete columns (10 legacy IDs & 3 dead features) are absent from information_schema.columns.
 * 2. Obsolete / duplicate functions are completely removed from pg_proc.
 * 3. fn_catat_log_aktivitas has been modernized without id_pesan_telegram.
 * 4. Duplicate index idx_pengguna_nama_pengguna_lower is removed from pg_indexes.
 * 5. View v_karyawan_info operates without id_legacy dependency.
 * 6. Constraint pengguna_karyawan_legacy_id_key is completely gone.
 * 
 * 100% Read-Only, Zero Persistent Mock Data.
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
    'nama_pengguna' => 'developer',
    'peran' => 'developer',
    'role_nama' => 'Developer'
];
$_SESSION['login_time'] = time();
$_SESSION['permissions'] = ['*'];
$_SESSION['permissions_version'] = time();

require_once APP_ROOT . '/config/database.php';
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

$pdo = Database::getConnection();

echo "====================================================================\n";
echo " 🛡️ KEREN ONE - Database Obsolete Assets Cleanup Audit Suite\n";
echo "====================================================================\n\n";

$passed = 0;
$failed = 0;
$totalTests = 0;

function runTest(string $title, callable $fn): void {
    global $passed, $failed, $totalTests;
    $totalTests++;
    echo "[TEST {$totalTests}] {$title} ... ";
    try {
        $result = $fn();
        if ($result === true) {
            echo "PASSED ✅\n";
            $passed++;
        } else {
            echo "FAILED ❌\n";
            echo "  Reason: " . ($result ?: 'Unknown failure') . "\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "FAILED ❌ (Exception)\n";
        echo "  Error: " . $e->getMessage() . "\n";
        $failed++;
    }
}

// TEST 1: 10 Kolom Legacy ID Harus Hilang dari Database
runTest("1.1 - Verifikasi 10 Kolom Legacy ID telah dihapus permanen", function () use ($pdo) {
    $legacyCols = [
        ['absensi', 'id_legacy'],
        ['kasbon', 'id_legacy'],
        ['penarikan_gaji', 'id_legacy'],
        ['penggajian', 'id_legacy'],
        ['potongan_kasbon', 'id_legacy'],
        ['produksi_harian', 'id_legacy'],
        ['rincian_penggajian', 'id_legacy'],
        ['tabungan', 'id_legacy'],
        ['transaksi_tabungan', 'id_legacy'],
        ['pengguna', 'karyawan_legacy_id']
    ];

    $found = [];
    foreach ($legacyCols as [$table, $col]) {
        $st = $pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
              AND table_name = :t 
              AND column_name = :c
        ");
        $st->execute(['t' => $table, 'c' => $col]);
        if ((int)$st->fetchColumn() > 0) {
            $found[] = "{$table}.{$col}";
        }
    }

    if (!empty($found)) {
        return "Kolom legacy masih ditemukan: " . implode(', ', $found);
    }
    return true;
});

// TEST 2: 3 Kolom Dead Feature Harus Hilang dari Database
runTest("1.2 - Verifikasi 3 Kolom Dead Feature telah dihapus permanen", function () use ($pdo) {
    $deadCols = [
        ['log_aktivitas', 'id_pesan_telegram'],
        ['surat_jalan', 'url_pdf_dokumen'],
        ['rincian_penggajian', 'total_tunjangan']
    ];

    $found = [];
    foreach ($deadCols as [$table, $col]) {
        $st = $pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
              AND table_name = :t 
              AND column_name = :c
        ");
        $st->execute(['t' => $table, 'c' => $col]);
        if ((int)$st->fetchColumn() > 0) {
            $found[] = "{$table}.{$col}";
        }
    }

    if (!empty($found)) {
        return "Kolom dead feature masih ditemukan: " . implode(', ', $found);
    }
    return true;
});

// TEST 3: Fungsi-Fungsi Obsolete Telah Dihapus dari pg_proc
runTest("2.1 - Verifikasi Fungsi Duplikat/Usang telah di-drop dari pg_proc", function () use ($pdo) {
    $obsoleteFuncs = [
        'fn_revisi_dan_rekonsiliasi_piutang_pelanggan',
        'fn_buat_tagihan_kunjungan_konsinyasi'
    ];

    $found = [];
    foreach ($obsoleteFuncs as $fn) {
        $st = $pdo->prepare("
            SELECT COUNT(*) 
            FROM pg_proc p 
            JOIN pg_namespace n ON n.oid = p.pronamespace 
            WHERE n.nspname = 'public' AND p.proname = :fn
        ");
        $st->execute(['fn' => $fn]);
        if ((int)$st->fetchColumn() > 0) {
            $found[] = $fn;
        }
    }

    if (!empty($found)) {
        return "Fungsi usang masih ada di pg_proc: " . implode(', ', $found);
    }
    return true;
});

// TEST 4: fn_catat_log_aktivitas Bebas dari Parameter Telegram
runTest("2.2 - Verifikasi Signature fn_catat_log_aktivitas tidak membawa id_pesan_telegram", function () use ($pdo) {
    $st = $pdo->prepare("
        SELECT pg_get_function_identity_arguments(p.oid) as args
        FROM pg_proc p 
        JOIN pg_namespace n ON n.oid = p.pronamespace 
        WHERE n.nspname = 'public' AND p.proname = 'fn_catat_log_aktivitas'
    ");
    $st->execute();
    $args = $st->fetchColumn();

    if ($args === false) {
        return "Fungsi fn_catat_log_aktivitas tidak ditemukan di pg_proc";
    }

    if (stripos((string)$args, 'id_pesan_telegram') !== false || stripos((string)$args, 'bigint') !== false) {
        return "Signature fn_catat_log_aktivitas masih membawa parameter telegram/bigint: {$args}";
    }
    return true;
});

// TEST 5: Indeks Duplikat idx_pengguna_nama_pengguna_lower Telah Dihapus
runTest("3.1 - Verifikasi Indeks Duplikat idx_pengguna_nama_pengguna_lower telah di-drop", function () use ($pdo) {
    $st = $pdo->prepare("
        SELECT COUNT(*) 
        FROM pg_indexes 
        WHERE schemaname = 'public' 
          AND tablename = 'pengguna' 
          AND indexname = 'idx_pengguna_nama_pengguna_lower'
    ");
    $st->execute();
    if ((int)$st->fetchColumn() > 0) {
        return "Indeks duplikat idx_pengguna_nama_pengguna_lower masih ada di pg_indexes";
    }

    // Pastikan indeks utama idx_pengguna_nama_pengguna tetap ada
    $st2 = $pdo->prepare("
        SELECT COUNT(*) 
        FROM pg_indexes 
        WHERE schemaname = 'public' 
          AND tablename = 'pengguna' 
          AND indexname = 'idx_pengguna_nama_pengguna'
    ");
    $st2->execute();
    if ((int)$st2->fetchColumn() === 0) {
        return "Indeks utama idx_pengguna_nama_pengguna tidak ditemukan";
    }

    return true;
});

// TEST 6: Constraint Unik pengguna_karyawan_legacy_id_key Telah Dihapus
runTest("3.2 - Verifikasi Constraint Unik pengguna_karyawan_legacy_id_key telah hilang", function () use ($pdo) {
    $st = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.table_constraints 
        WHERE table_schema = 'public' 
          AND table_name = 'pengguna' 
          AND constraint_name = 'pengguna_karyawan_legacy_id_key'
    ");
    $st->execute();
    if ((int)$st->fetchColumn() > 0) {
        return "Constraint pengguna_karyawan_legacy_id_key masih ada";
    }
    return true;
});

// TEST 7: View v_karyawan_info Tetap Beroperasi Normal Tanpa id_legacy
runTest("4.1 - Verifikasi Integritas View v_karyawan_info (Tanpa id_legacy)", function () use ($pdo) {
    // 1. Cek kolom di view
    $stCols = $pdo->prepare("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
          AND table_name = 'v_karyawan_info' 
          AND column_name = 'id_legacy'
    ");
    $stCols->execute();
    if ($stCols->fetchColumn()) {
        return "View v_karyawan_info masih memiliki kolom id_legacy";
    }

    // 2. Cek apakah view dapat di-query dengan normal
    $stQuery = $pdo->query("SELECT id, nama_karyawan, posisi, status_aktif FROM public.v_karyawan_info LIMIT 5");
    $rows = $stQuery->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        return "Query v_karyawan_info tidak mengembalikan baris data";
    }

    return true;
});

echo "\n====================================================================\n";
echo "SUMMARY: {$passed} / {$totalTests} Tests Passed (" . round(($passed / $totalTests) * 100, 1) . "%)\n";
echo "====================================================================\n\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

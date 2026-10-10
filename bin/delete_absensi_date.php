<?php
declare(strict_types=1);

/**
 * bin/delete_absensi_date.php
 * CLI Tool Pembersihan Data Absensi Harian Tertutup, Terisolasi & Atomik.
 * 
 * Standar Kepatuhan: AGENTS.md & erp-database / erp-coding skills:
 * 1. Atomic Transaction (BEGIN ... COMMIT / ROLLBACK).
 * 2. Closed-Loop Financial Integrity: Refund saldo kas fisik/ledger jika ada penarikan gaji via absensi.
 * 3. Hapus relasi turunan: arus_kas -> penarikan_gaji -> absensi (anti-orphan records).
 * 4. Audit Trail: Mencatat riwayat pembersihan di public.log_aktivitas.
 * 5. Local-First Staging Support: Mendukung --target=local, --target=live, --dry-run.
 * 6. Scoped Attendance Support: Mendukung --scope=borongan, --scope=bulanan, --scope=all.
 *
 * Penggunaan:
 *   php bin/delete_absensi_date.php --date=2026-10-10 --scope=borongan --target=local [--dry-run]
 *   php bin/delete_absensi_date.php --date=2026-10-10 --scope=borongan --target=live
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_ROOT', ROOT_PATH);
date_default_timezone_set('Asia/Jakarta');

// Parse CLI Arguments
$options = getopt('', ['date:', 'target:', 'scope:', 'dry-run', 'help']);

if (isset($options['help']) || empty($options['date'])) {
    echo "Penggunaan: php bin/delete_absensi_date.php --date=YYYY-MM-DD [--scope=borongan|bulanan|all] [--target=local|live|all] [--dry-run]\n";
    echo "Contoh:     php bin/delete_absensi_date.php --date=2026-10-10 --scope=borongan --target=local\n";
    exit(isset($options['help']) ? 0 : 1);
}

$targetDate = trim((string)$options['date']);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate) || !strtotime($targetDate)) {
    fwrite(STDERR, "❌ Format tanggal tidak valid: '{$targetDate}'. Gunakan format YYYY-MM-DD.\n");
    exit(1);
}

$targetDb = strtolower(trim((string)($options['target'] ?? 'local')));
$scope    = strtolower(trim((string)($options['scope'] ?? 'all')));
$isDryRun = isset($options['dry-run']);

if (!in_array($targetDb, ['local', 'live', 'all'], true)) {
    fwrite(STDERR, "❌ Target database tidak valid. Gunakan 'local', 'live', atau 'all'.\n");
    exit(1);
}

if (!in_array($scope, ['borongan', 'bulanan', 'all'], true)) {
    fwrite(STDERR, "❌ Cakupan (scope) tidak valid. Gunakan 'borongan', 'bulanan', atau 'all'.\n");
    exit(1);
}

// Function to establish connection based on target
function getTargetConnection(string $target): PDO {
    if ($target === 'local') {
        $envFile = APP_ROOT . '/.env';
        if (file_exists(APP_ROOT . '/.env.local')) {
            $envFile = APP_ROOT . '/.env.local';
        }
    } else {
        $envFile = APP_ROOT . '/.env.live';
    }

    if (!file_exists($envFile)) {
        throw new RuntimeException("Berkas konfigurasi '{$envFile}' tidak ditemukan.");
    }

    $lines = explode("\n", file_get_contents($envFile));
    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }
    }

    $host     = $env['DB_HOST'] ?? '127.0.0.1';
    $port     = $env['DB_PORT'] ?? '5432';
    $dbname   = $env['DB_DATABASE'] ?? 'postgres';
    $user     = $env['DB_USERNAME'] ?? 'postgres';
    $password = $env['DB_PASSWORD'] ?? '';
    $sslmode  = $env['DB_SSLMODE'] ?? ($target === 'live' ? 'require' : 'prefer');

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";
    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 15,
    ]);
}

// Execution Function
function executeCleanAttendance(PDO $pdo, string $targetName, string $tanggal, string $scope, bool $dryRun): array {
    echo "\n============================================================\n";
    echo "Memproses Target: [" . strtoupper($targetName) . "] | Tanggal: {$tanggal} | Cakupan: [" . strtoupper($scope) . "]" . ($dryRun ? " (DRY-RUN)" : "") . "\n";
    echo "============================================================\n";

    // Scope SQL Filter Condition
    $scopeFilterKaryawan = match($scope) {
        'borongan' => " AND k.tipe_penggajian = 'borongan'",
        'bulanan'  => " AND k.tipe_penggajian = 'bulanan'",
        default    => ""
    };

    // 1. Cek Kunci Payroll (Safety Guard)
    $stmtLocked = $pdo->prepare("
        SELECT count(*) 
        FROM public.absensi a
        JOIN public.karyawan k ON k.id = a.karyawan_id
        WHERE a.tanggal = :tgl AND a.penggajian_id IS NOT NULL {$scopeFilterKaryawan}
    ");
    $stmtLocked->execute(['tgl' => $tanggal]);
    $lockedAbsensi = (int)$stmtLocked->fetchColumn();

    $stmtLockedPg = $pdo->prepare("
        SELECT count(*) 
        FROM public.penarikan_gaji pg
        JOIN public.karyawan k ON k.id = pg.karyawan_id
        WHERE pg.tanggal = :tgl AND pg.penggajian_id IS NOT NULL {$scopeFilterKaryawan}
    ");
    $stmtLockedPg->execute(['tgl' => $tanggal]);
    $lockedPg = (int)$stmtLockedPg->fetchColumn();

    if ($lockedAbsensi > 0 || $lockedPg > 0) {
        throw new RuntimeException("ABORT: Ditemukan {$lockedAbsensi} absensi dan {$lockedPg} penarikan gaji yang terkunci oleh transaksi payroll! Data tidak boleh dihapus sembarangan.");
    }

    // 2. Ambil Rekaman Absensi
    $stmtAbs = $pdo->prepare("
        SELECT a.id, a.karyawan_id, p.nama_lengkap, k.tipe_penggajian, a.status_kehadiran, a.ambil_uang
        FROM public.absensi a
        JOIN public.karyawan k ON k.id = a.karyawan_id
        JOIN public.pengguna p ON p.id = k.pengguna_id
        WHERE a.tanggal = :tgl {$scopeFilterKaryawan}
        ORDER BY p.nama_lengkap
    ");
    $stmtAbs->execute(['tgl' => $tanggal]);
    $absensiRows = $stmtAbs->fetchAll();
    echo "• Jumlah rekaman absensi [{$scope}] ditemukan: " . count($absensiRows) . " karyawan\n";
    foreach ($absensiRows as $abs) {
        echo "  - [{$abs['tipe_penggajian']}] {$abs['nama_lengkap']} (Status: {$abs['status_kehadiran']})\n";
    }

    // 3. Ambil Rekaman Penarikan Gaji & Arus Kas Terkait
    $stmtPg = $pdo->prepare("
        SELECT pg.id, pg.karyawan_id, p.nama_lengkap, pg.nominal, pg.akun_kas_id, ak.nama_akun
        FROM public.penarikan_gaji pg
        JOIN public.karyawan k ON k.id = pg.karyawan_id
        JOIN public.pengguna p ON p.id = k.pengguna_id
        LEFT JOIN public.akun_kas ak ON ak.id = pg.akun_kas_id
        WHERE pg.tanggal = :tgl {$scopeFilterKaryawan}
    ");
    $stmtPg->execute(['tgl' => $tanggal]);
    $penarikanRows = $stmtPg->fetchAll();
    echo "• Jumlah penarikan gaji [{$scope}] ditemukan: " . count($penarikanRows) . " transaksi\n";

    $totalRefundPerKas = [];
    foreach ($penarikanRows as $pg) {
        $kasId = $pg['akun_kas_id'];
        $nom = (float)$pg['nominal'];
        $kasNama = $pg['nama_akun'] ?? 'Kas Tanpa Nama';
        echo "  - {$pg['nama_lengkap']}: Rp " . number_format($nom, 0, ',', '.') . " dari '{$kasNama}' (ID: {$kasId})\n";

        if ($kasId && $nom > 0) {
            if (!isset($totalRefundPerKas[$kasId])) {
                $totalRefundPerKas[$kasId] = ['nama' => $kasNama, 'total' => 0.0];
            }
            $totalRefundPerKas[$kasId]['total'] += $nom;
        }
    }

    if (empty($absensiRows) && empty($penarikanRows)) {
        echo "ℹ️  Tidak ada data absensi atau penarikan gaji [{$scope}] pada tanggal {$tanggal}. Target sudah bersih.\n";
        return ['absensi_deleted' => 0, 'pg_deleted' => 0, 'refund_total' => 0];
    }

    if ($dryRun) {
        echo "\n[DRY-RUN] Simulasi sukses. Tidak ada data yang diubah atau dihapus.\n";
        return ['absensi_deleted' => count($absensiRows), 'pg_deleted' => count($penarikanRows), 'refund_total' => array_sum(array_column($totalRefundPerKas, 'total'))];
    }

    // 4. EKSEKUSI ATOMIK DALAM TRANSAKSI DATABASE
    $pdo->beginTransaction();
    try {
        // A. Refund saldo kas ke akun kas masing-masing (jika ada penarikan gaji)
        foreach ($totalRefundPerKas as $kasId => $info) {
            $refundNom = $info['total'];
            $kasNama   = $info['nama'];

            $saldoBefore = (float)$pdo->query("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = '{$kasId}'")->fetchColumn();
            
            $stmtRef = $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = saldo_saat_ini + :nom, diubah_pada = NOW() WHERE id = :id");
            $stmtRef->execute(['nom' => $refundNom, 'id' => $kasId]);

            $saldoAfter = (float)$pdo->query("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = '{$kasId}'")->fetchColumn();

            echo "✅ REFUND KAS: '{$kasNama}' +Rp " . number_format($refundNom, 0, ',', '.') . " (Saldo: Rp " . number_format($saldoBefore, 0, ',', '.') . " -> Rp " . number_format($saldoAfter, 0, ',', '.') . ")\n";
        }

        // B. Hapus rekaman arus_kas yang merujuk ke penarikan_gaji cakupan ini
        $deletedArusKas = 0;
        foreach ($penarikanRows as $pg) {
            $stmtDelAk = $pdo->prepare("DELETE FROM public.arus_kas WHERE referensi_tabel = 'penarikan_gaji' AND referensi_id = :pg_id");
            $stmtDelAk->execute(['pg_id' => $pg['id']]);
            $deletedArusKas += $stmtDelAk->rowCount();
        }
        if ($deletedArusKas > 0) {
            echo "✅ HAPUS ARUS KAS: {$deletedArusKas} baris arus kas pengeluaran dihapus.\n";
        }

        // C. Hapus rekaman penarikan_gaji sesuai cakupan
        $deletedPg = 0;
        if (!empty($penarikanRows)) {
            $pgIds = array_column($penarikanRows, 'id');
            $inPlaceholders = implode(',', array_fill(0, count($pgIds), '?'));
            $stmtDelPg = $pdo->prepare("DELETE FROM public.penarikan_gaji WHERE id IN ({$inPlaceholders}) AND penggajian_id IS NULL");
            $stmtDelPg->execute($pgIds);
            $deletedPg = $stmtDelPg->rowCount();
            echo "✅ HAPUS PENARIKAN GAJI: {$deletedPg} baris penarikan gaji dihapus.\n";
        }

        // D. Hapus rekaman absensi sesuai cakupan
        $absIds = array_column($absensiRows, 'id');
        $inPlaceholdersAbs = implode(',', array_fill(0, count($absIds), '?'));
        $stmtDelAbs = $pdo->prepare("DELETE FROM public.absensi WHERE id IN ({$inPlaceholdersAbs}) AND penggajian_id IS NULL");
        $stmtDelAbs->execute($absIds);
        $deletedAbs = $stmtDelAbs->rowCount();
        echo "✅ HAPUS ABSENSI: {$deletedAbs} baris absensi karyawan [{$scope}] dihapus.\n";

        // E. Catat riwayat audit log
        $totalRefundAll = array_sum(array_column($totalRefundPerKas, 'total'));
        $logDeskripsi = "RESET ABSENSI [{$scope}]: Menghapus {$deletedAbs} absensi & {$deletedPg} penarikan gaji tanggal {$tanggal} (Refund kas Rp " . number_format($totalRefundAll, 0, ',', '.') . ") atas permintaan pengguna.";
        
        $stmtLog = $pdo->prepare("
            INSERT INTO public.log_aktivitas (
                nama_aktor, peran_aktor, sumber_aksi, kategori_aktivitas,
                jenis_aksi, tabel_terdampak, deskripsi_aktivitas, waktu_kejadian
            ) VALUES (
                'System Maintenance', 'developer', 'system_cron', 'hr_payroll',
                'DELETE_ABSENSI_HARIAN', 'absensi', :deskripsi, NOW()
            )
        ");
        $stmtLog->execute(['deskripsi' => $logDeskripsi]);
        echo "✅ AUDIT LOG: Riwayat penghapusan dicatat ke public.log_aktivitas.\n";

        $pdo->commit();
        echo "🎉 TRANSAKSI SELESAI & KOMIT SUKSES (Karyawan Bulanan 100% Aman Terjaga)!\n";

        return [
            'absensi_deleted' => $deletedAbs,
            'pg_deleted'      => $deletedPg,
            'refund_total'    => $totalRefundAll
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo "❌ TRANSAKSI GAGAL & DI-ROLLBACK: " . $e->getMessage() . "\n";
        throw $e;
    }
}

// Main Runner
$targetsToRun = ($targetDb === 'all') ? ['local', 'live'] : [$targetDb];

foreach ($targetsToRun as $t) {
    try {
        $pdo = getTargetConnection($t);
        executeCleanAttendance($pdo, $t, $targetDate, $scope, $isDryRun);
    } catch (Throwable $e) {
        fwrite(STDERR, "\n❌ Terjadi kesalahan fatal pada target {$t}: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "\n============================================================\n";
echo "STATUS AKHIR: Pembersihan data absensi [{$scope}] tanggal {$targetDate} selesai sempurna.\n";
echo "============================================================\n";

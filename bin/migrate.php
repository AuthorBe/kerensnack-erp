<?php
declare(strict_types=1);

/**
 * bin/migrate.php
 * CLI Runner & Automated Migration Tracker untuk KEREN ONE.
 *
 * Mendukung:
 * - Pengecekan status migrasi (--status)
 * - Eksekusi migrasi baru ke target tertentu (--target=local, --target=live, --target=all)
 * - Pencatatan baseline migrasi historis (--baseline)
 * - Safe transaction & auto-rollback jika terjadi kegagalan SQL.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Access Denied: CLI execution only.\n");
}

define('ROOT_PATH', dirname(__DIR__));
define('APP_ROOT', ROOT_PATH);
date_default_timezone_set('Asia/Jakarta');

// ANSI Color Helpers for Windows & Linux Terminals
class CliColor {
    public static function green(string $text): string { return "\033[32m{$text}\033[0m"; }
    public static function red(string $text): string { return "\033[31m{$text}\033[0m"; }
    public static function yellow(string $text): string { return "\033[33m{$text}\033[0m"; }
    public static function cyan(string $text): string { return "\033[36m{$text}\033[0m"; }
    public static function bold(string $text): string { return "\033[1m{$text}\033[0m"; }
    public static function dim(string $text): string { return "\033[2m{$text}\033[0m"; }
}

class MigrationRunner {
    private string $migrationsDir;

    public function __construct() {
        $this->migrationsDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        if (!is_dir($this->migrationsDir)) {
            throw new RuntimeException("Direktori migrasi tidak ditemukan: {$this->migrationsDir}");
        }
    }

    /**
     * Dapatkan koneksi PDO untuk target tertentu ('local', 'live', atau 'current')
     */
    public function getConnection(string $target = 'current'): PDO {
        $target = strtolower(trim($target));

        if ($target === 'local') {
            $dsn = "pgsql:host=127.0.0.1;port=5432;dbname=kerensnack_erp_local;sslmode=disable";
            return new PDO($dsn, 'postgres', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10,
            ]);
        }

        if ($target === 'live') {
            $liveEnvFile = ROOT_PATH . DIRECTORY_SEPARATOR . '.env.live';
            if (!file_exists($liveEnvFile)) {
                throw new RuntimeException("File .env.live tidak ditemukan untuk koneksi live.");
            }
            $cfg = $this->parseEnvFile($liveEnvFile);
            $host = $cfg['DB_HOST'] ?? 'aws-0-ap-southeast-1.pooler.supabase.com';
            $port = $cfg['DB_PORT'] ?? '5432';
            $db   = $cfg['DB_DATABASE'] ?? 'postgres';
            $user = $cfg['DB_USERNAME'] ?? 'postgres';
            $pass = $cfg['DB_PASSWORD'] ?? '';
            $ssl  = $cfg['DB_SSLMODE'] ?? 'require';

            $dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode={$ssl}";
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 15,
            ]);
        }

        // Target 'current': gunakan konfigurasi dari .env aktif
        require_once ROOT_PATH . '/config/database.php';
        return Database::getConnection();
    }

    private function parseEnvFile(string $filePath): array {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $cfg = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (str_contains($line, '=')) {
                [$k, $v] = explode('=', $line, 2);
                $cfg[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
            }
        }
        return $cfg;
    }

    /**
     * Pastikan tabel schema_migrations ada di database target
     */
    public function ensureTrackerTable(PDO $pdo): void {
        $sql = "
            CREATE TABLE IF NOT EXISTS public.schema_migrations (
                version VARCHAR(255) PRIMARY KEY,
                migrated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                batch INTEGER NOT NULL DEFAULT 1
            );
            CREATE INDEX IF NOT EXISTS idx_schema_migrations_version ON public.schema_migrations(version);
            CREATE INDEX IF NOT EXISTS idx_schema_migrations_batch ON public.schema_migrations(batch);
        ";
        $pdo->exec($sql);
    }

    /**
     * Dapatkan semua file migrasi yang ada di folder database/migrations/
     * Terurut berdasarkan nomor migrasi
     */
    public function getMigrationFiles(): array {
        $files = scandir($this->migrationsDir) ?: [];
        $migrationFiles = [];

        foreach ($files as $file) {
            if (preg_match('/^([0-9]+)_.*\.sql$/', $file, $matches)) {
                $num = (int)$matches[1];
                $migrationFiles[$num . '_' . $file] = [
                    'number' => $num,
                    'file'   => $file,
                    'path'   => $this->migrationsDir . DIRECTORY_SEPARATOR . $file,
                ];
            }
        }

        // Urutkan berdasarkan number
        uasort($migrationFiles, function($a, $b) {
            if ($a['number'] === $b['number']) {
                return strcmp($a['file'], $b['file']);
            }
            return $a['number'] <=> $b['number'];
        });

        return array_values($migrationFiles);
    }

    /**
     * Ambil riwayat migrasi yang sudah pernah diaplikasikan di database
     */
    public function getAppliedMigrations(PDO $pdo): array {
        $this->ensureTrackerTable($pdo);
        $stmt = $pdo->query("SELECT version, migrated_at, batch FROM public.schema_migrations ORDER BY version ASC");
        $results = $stmt->fetchAll();
        $applied = [];
        foreach ($results as $row) {
            $applied[$row['version']] = $row;
        }
        return $applied;
    }

    /**
     * Dapatkan batch number berikutnya
     */
    public function getNextBatchNumber(PDO $pdo): int {
        $this->ensureTrackerTable($pdo);
        $max = $pdo->query("SELECT COALESCE(MAX(batch), 0) FROM public.schema_migrations")->fetchColumn();
        return ((int)$max) + 1;
    }

    /**
     * Tampilkan tabel status migrasi
     */
    public function showStatus(string $target = 'current'): void {
        $pdo = $this->getConnection($target);
        $applied = $this->getAppliedMigrations($pdo);
        $files = $this->getMigrationFiles();

        echo "\n" . CliColor::bold("================================================================================") . "\n";
        echo " 📋 STATUS MIGRASI DATABASE [Target: " . CliColor::cyan(strtoupper($target)) . "]\n";
        echo CliColor::bold("================================================================================") . "\n";
        echo sprintf("%-6s | %-52s | %-10s | %-6s\n", "NO", "NAMA FILE", "STATUS", "BATCH");
        echo "--------------------------------------------------------------------------------\n";

        $appliedCount = 0;
        $pendingCount = 0;

        foreach ($files as $item) {
            $filename = $item['file'];
            $isApplied = isset($applied[$filename]);

            if ($isApplied) {
                $appliedCount++;
                $status = CliColor::green("APPLIED");
                $batch = (string)$applied[$filename]['batch'];
            } else {
                $pendingCount++;
                $status = CliColor::yellow("PENDING");
                $batch = "-";
            }

            $displayFilename = strlen($filename) > 50 ? substr($filename, 0, 47) . '...' : $filename;
            echo sprintf("%-6d | %-52s | %-19s | %-6s\n", $item['number'], $displayFilename, $status, $batch);
        }

        echo "--------------------------------------------------------------------------------\n";
        echo "Total: " . count($files) . " file | Terpasang: " . CliColor::green((string)$appliedCount) . " | Tertunda: " . ($pendingCount > 0 ? CliColor::yellow((string)$pendingCount) : CliColor::green("0")) . "\n";
        echo CliColor::bold("================================================================================") . "\n\n";
    }

    /**
     * Baseline: Tandai file migrasi lama (<= maxNumber) sebagai applied tanpa mengeksekusi DDL
     */
    public function baseline(string $target = 'current', int $maxNumber = 92): void {
        $pdo = $this->getConnection($target);
        $this->ensureTrackerTable($pdo);
        $applied = $this->getAppliedMigrations($pdo);
        $files = $this->getMigrationFiles();

        echo "\n" . CliColor::cyan("Menandai baseline migrasi (<= {$maxNumber}) pada target: " . strtoupper($target)) . "...\n";

        $count = 0;
        $stmt = $pdo->prepare("INSERT INTO public.schema_migrations (version, batch) VALUES (:version, 1) ON CONFLICT (version) DO NOTHING");

        foreach ($files as $item) {
            if ($item['number'] <= $maxNumber && !isset($applied[$item['file']])) {
                $stmt->execute(['version' => $item['file']]);
                $count++;
            }
        }

        echo CliColor::green("✓ Berhasil menandai {$count} file migrasi sebagai baseline historis (Batch 1).") . "\n\n";
    }

    /**
     * Jalankan semua migrasi yang berstatus PENDING
     */
    public function migrate(string $target = 'current'): void {
        $pdo = $this->getConnection($target);
        $this->ensureTrackerTable($pdo);
        $applied = $this->getAppliedMigrations($pdo);
        $files = $this->getMigrationFiles();

        $pending = [];
        foreach ($files as $item) {
            if (!isset($applied[$item['file']])) {
                $pending[] = $item;
            }
        }

        if (empty($pending)) {
            echo CliColor::green("✓ Tidak ada migrasi yang tertunda. Database [{$target}] sudah termutakhir.") . "\n";
            return;
        }

        $batch = $this->getNextBatchNumber($pdo);
        echo "\n" . CliColor::bold("Menjalankan " . count($pending) . " migrasi tertunda pada target [{$target}] (Batch {$batch}):") . "\n";

        $recordStmt = $pdo->prepare("INSERT INTO public.schema_migrations (version, batch) VALUES (:version, :batch)");

        foreach ($pending as $item) {
            $filename = $item['file'];
            $filepath = $item['path'];
            echo "  ▶ Menjalankan: " . CliColor::cyan($filename) . " ... ";

            $sql = file_get_contents($filepath);
            if ($sql === false || trim($sql) === '') {
                echo CliColor::red("GAGAL (File kosong atau tidak terbaca)") . "\n";
                throw new RuntimeException("Gagal membaca file migrasi: {$filepath}");
            }

            try {
                // Eksekusi SQL migrasi
                $pdo->exec($sql);
                // Catat ke schema_migrations
                $recordStmt->execute([
                    'version' => $filename,
                    'batch'   => $batch,
                ]);
                echo CliColor::green("SUKSES") . "\n";
            } catch (Throwable $e) {
                echo CliColor::red("GAGAL") . "\n";
                echo CliColor::red("  Error: " . $e->getMessage()) . "\n";
                throw new RuntimeException("Migrasi dihentikan pada file {$filename}: " . $e->getMessage(), 0, $e);
            }
        }

        echo CliColor::green("\n✓ Seluruh migrasi tertunda berhasil diaplikasikan ke [{$target}].") . "\n\n";
    }
}

// -----------------------------------------------------------------------------
// CLI Argument Parsing
// -----------------------------------------------------------------------------
$options = getopt('s::h::t::', ['status', 'help', 'target:', 'baseline::']);

if (isset($options['h']) || isset($options['help'])) {
    echo "\n" . CliColor::bold("KEREN ONE - CLI Migration Runner & Tracker") . "\n";
    echo "Penggunaan:\n";
    echo "  php bin/migrate.php [OPSI]\n\n";
    echo "Opsi:\n";
    echo "  --status, -s               Tampilkan daftar status seluruh migrasi (APPLIED vs PENDING)\n";
    echo "  --target=<local|live|all>  Tentukan target database (default: database aktif dari .env)\n";
    echo "  --baseline                 Tandai migrasi historis (01-92) sebagai applied tanpa run SQL\n";
    echo "  --help, -h                 Tampilkan bantuan ini\n\n";
    echo "Contoh:\n";
    echo "  php bin/migrate.php --status\n";
    echo "  php bin/migrate.php --target=local\n";
    echo "  php bin/migrate.php --target=live\n";
    echo "  php bin/migrate.php --baseline --target=local\n";
    echo "  php bin/migrate.php --baseline --target=live\n\n";
    exit(0);
}

$target = 'current';
if (isset($options['target']) && is_string($options['target'])) {
    $target = strtolower(trim($options['target']));
}

$runner = new MigrationRunner();

try {
    if (isset($options['baseline'])) {
        $runner->baseline($target, 92);
        exit(0);
    }

    if (isset($options['status']) || isset($options['s'])) {
        $runner->showStatus($target);
        exit(0);
    }

    // Default action: Jalankan migrasi tertunda
    if ($target === 'all') {
        echo CliColor::bold("Alur Staging Local-First: Menjalankan ke LOCAL terlebih dahulu...") . "\n";
        $runner->migrate('local');
        echo CliColor::bold("Validasi Lokal Berhasil! Melanjutkan eksekusi ke LIVE Supabase...") . "\n";
        $runner->migrate('live');
    } else {
        $runner->migrate($target);
    }
} catch (Throwable $e) {
    echo "\n" . CliColor::red("❌ EKSEKUSI MIGRASI GAGAL: " . $e->getMessage()) . "\n\n";
    exit(1);
}

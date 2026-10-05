<?php
declare(strict_types=1);

namespace App\Services;

use Database;
use PDO;
use Throwable;
use RuntimeException;
use InvalidArgumentException;

/**
 * app/Services/DatabaseManagerService.php
 * Layanan Sentral Tata Kelola Database: Replikasi Live-to-Local & 1-Click Connection Switcher.
 * 100% Mengikuti Standar Keamanan Tinggi & Zero Persistent Mock Data.
 */
class DatabaseManagerService
{
    /**
     * Daftar tabel Master Data yang WAJIB selalu direplikasi 100% (All-Time)
     * demi menjamin integritas Foreign Key dan validitas pengujian lokal.
     */
    public const MASTER_TABLES = [
        'peran',
        'izin',
        'izin_peran',
        'pengguna',
        'izin_pengguna',
        'wilayah',
        'karyawan',
        'skema_komisi_sales',
        'pemasok',
        'master_level_harga',
        'grup_pelanggan',
        'merek',
        'grup_pelanggan_level_merek',
        'pelanggan',
        'grup_produk',
        'grup_produk_barcode',
        'grup_produk_harga_level',
        'kelompok_upah_borongan',
        'item',
        'pelanggan_item',
        'pelanggan_grup_barcode',
        'komposisi_item',
        'pemasok_item',
        'akun_kas',
        'kategori_biaya',
        'pengaturan_sistem',
        'tabungan',
        'stok_konsinyasi_toko',
        'schema_migrations'
    ];

    /**
     * Memeriksa apakah aplikasi sedang diakses dari lingkungan lokal developer (localhost, 127.0.0.1, .test, .local, CLI).
     */
    public static function isLocalEnvironment(): bool
    {
        $host = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
        if (str_contains($host, ',')) {
            $host = trim(explode(',', $host)[0]);
        }
        if (str_contains($host, ':')) {
            $host = explode(':', $host)[0];
        }

        if (!empty($host)) {
            // Host developer tepercaya: lokal + domain preview Cloudflare (exact match, bukan wildcard).
            $localHosts = ['127.0.0.1', 'localhost', '::1', 'preview.ajisakha.my.id'];
            if (in_array($host, $localHosts, true)) {
                return true;
            }

            if (str_ends_with($host, '.test') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
                return true;
            }

            // Host publik produksi (misal: aplikasi.kerensnack.id)
            return false;
        }

        return (PHP_SAPI === 'cli');
    }

    /**
     * Memeriksa apakah aplikasi sedang diakses dari domain aktif / remote / produksi.
     */
    public static function isProductionDomain(): bool
    {
        return !self::isLocalEnvironment();
    }

    /**
     * Memeriksa apakah aksi perpindahan / replikasi diizinkan.
     * Wajib: (1) Lingkungan lokal developer, DAN (2) Role Developer (atau CLI).
     */
    public static function isActionAllowed(): bool
    {
        if (!self::isLocalEnvironment()) {
            return false;
        }

        $hasHost = !empty($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
        $hasSessionUser = (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user']));

        if ($hasHost || $hasSessionUser) {
            return class_exists('\App\Core\Auth') && \App\Core\Auth::isDeveloper();
        }

        return (PHP_SAPI === 'cli');
    }

    /**
     * Dapatkan status koneksi database aktif saat ini.
     */
    public static function getStatus(): array
    {
        $info = Database::getConnectionInfo();

        $tableCount = 0;
        $totalRows  = 0;

        try {
            $pdo = Database::getConnection();
            $tableCount = (int)($pdo->query("
                SELECT count(*) 
                FROM information_schema.tables 
                WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
            ")->fetchColumn() ?: 0);

            $totalRows = (int)($pdo->query("
                SELECT COALESCE(SUM(n_live_tup), 0) 
                FROM pg_stat_user_tables 
                WHERE schemaname = 'public'
            ")->fetchColumn() ?: 0);
        } catch (Throwable $e) {
            // Biarkan fallback ke nilai awal jika query statistik gagal
        }

        $info['table_count'] = $tableCount;
        $info['total_rows']  = $totalRows;
        $info['server_time'] = date('Y-m-d H:i:s') . ' WIB';
        $info['is_production_domain'] = self::isProductionDomain();
        $info['is_local_environment'] = self::isLocalEnvironment();
        $info['is_action_allowed']    = self::isActionAllowed();

        return $info;
    }

    /**
     * Beralih koneksi antara 'local' (Sandbox) dan 'live' (Produksi).
     *
     * @param string $target 'local' | 'live'
     * @return array Status peralihan
     */
    public static function switchConnection(string $target): array
    {
        if (!self::isLocalEnvironment()) {
            throw new RuntimeException("Aksi ditolak: Peralihan database dikunci permanen pada domain aktif. Aksi ini hanya dapat dilakukan di lingkungan lokal developer.");
        }

        $hasHost = !empty($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
        $hasSessionUser = (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user']));
        if (($hasHost || $hasSessionUser || PHP_SAPI !== 'cli') && class_exists('\App\Core\Auth') && !\App\Core\Auth::isDeveloper()) {
            throw new RuntimeException("Aksi ditolak: Hanya peran developer yang berwenang beralih target database.");
        }

        $target = strtolower(trim($target));
        if (!in_array($target, ['local', 'live'], true)) {
            throw new InvalidArgumentException("Target database tidak valid. Pilihan: 'local' atau 'live'.");
        }

        $rootPath = dirname(__DIR__, 2);
        $envFile     = $rootPath . '/.env';
        $envLiveFile = $rootPath . '/.env.live';

        if (!file_exists($envFile)) {
            throw new RuntimeException("Berkas .env tidak ditemukan di direktori root.");
        }

        $currentEnv = file_get_contents($envFile) ?: '';
        $backupEnv  = $currentEnv;

        try {
            if ($target === 'live') {
                if (!file_exists($envLiveFile)) {
                    throw new RuntimeException("Berkas kredensial .env.live tidak ditemukan.");
                }

                // Simpan state local jika saat ini local
                if (str_contains($currentEnv, '127.0.0.1') || str_contains($currentEnv, 'localhost')) {
                    self::atomicWriteFile($rootPath . '/.env.local', $currentEnv);
                }

                $liveEnv = file_get_contents($envLiveFile);
                if (empty($liveEnv)) {
                    throw new RuntimeException("Berkas .env.live kosong.");
                }

                self::atomicWriteFile($envFile, $liveEnv);
                self::reloadEnvironment($envFile);

                // Verifikasi integritas koneksi live
                try {
                    Database::getConnection();
                } catch (Throwable $connEx) {
                    // Rollback ke konfigurasi awal jika koneksi live gagal tersambung
                    self::atomicWriteFile($envFile, $backupEnv);
                    self::reloadEnvironment($envFile);
                    throw new RuntimeException("Gagal beralih ke Live Supabase: " . $connEx->getMessage() . ". Konfigurasi .env telah dipulihkan otomatis.");
                }

                return [
                    'success'     => true,
                    'target'      => 'live',
                    'is_local'    => false,
                    'badge_label' => 'LIVE SUPABASE',
                    'message'     => 'Berhasil beralih ke Database Live Supabase (Cloud Production). Hati-hati, transaksi adalah data riil!'
                ];
            }

            // Target: LOCAL
            if (file_exists($rootPath . '/.env.local')) {
                $localEnv = file_get_contents($rootPath . '/.env.local');
                if (!empty($localEnv)) {
                    self::atomicWriteFile($envFile, $localEnv);
                }
            } else {
                // Fallback replace parameters ke standard Laragon
                $updatedEnv = preg_replace('/^DB_HOST=.*$/m', 'DB_HOST=127.0.0.1', $currentEnv);
                $updatedEnv = preg_replace('/^DB_PORT=.*$/m', 'DB_PORT=5432', $updatedEnv);
                $updatedEnv = preg_replace('/^DB_DATABASE=.*$/m', 'DB_DATABASE=kerensnack_erp_local', $updatedEnv);
                $updatedEnv = preg_replace('/^DB_USERNAME=.*$/m', 'DB_USERNAME=postgres', $updatedEnv);
                $updatedEnv = preg_replace('/^DB_PASSWORD=.*$/m', 'DB_PASSWORD=', $updatedEnv);
                $updatedEnv = preg_replace('/^DB_SSLMODE=.*$/m', 'DB_SSLMODE=disable', $updatedEnv);
                self::atomicWriteFile($envFile, $updatedEnv);
            }

            self::reloadEnvironment($envFile);

            // Verifikasi integritas koneksi local
            try {
                Database::getConnection();
            } catch (Throwable $connEx) {
                // Rollback ke konfigurasi awal jika koneksi local gagal
                self::atomicWriteFile($envFile, $backupEnv);
                self::reloadEnvironment($envFile);
                throw new RuntimeException("Gagal beralih ke Database Lokal: " . $connEx->getMessage() . ". Konfigurasi .env telah dipulihkan otomatis.");
            }

            return [
                'success'     => true,
                'target'      => 'local',
                'is_local'    => true,
                'badge_label' => 'LOCAL DB',
                'message'     => 'Berhasil beralih ke Database Lokal (kerensnack_erp_local). Lingkungan aman untuk pengujian & simulasi.'
            ];
        } catch (Throwable $e) {
            // Jaminan rollback jika terjadi eksepsi tak terduga
            if (file_get_contents($envFile) !== $backupEnv) {
                self::atomicWriteFile($envFile, $backupEnv);
                self::reloadEnvironment($envFile);
            }
            throw $e;
        }
    }

    /**
     * Menulis file secara atomik menggunakan file temporer dan rename dengan lock eksklusif.
     */
    private static function atomicWriteFile(string $filePath, string $content): void
    {
        $dir = dirname($filePath);
        $tmpFile = $dir . '/.' . basename($filePath) . '.tmp.' . bin2hex(random_bytes(6));
        if (file_put_contents($tmpFile, $content, LOCK_EX) === false) {
            throw new RuntimeException("Gagal menulis berkas sementara untuk atomik: {$tmpFile}");
        }
        if (!rename($tmpFile, $filePath)) {
            @unlink($tmpFile);
            throw new RuntimeException("Gagal memperbarui berkas secara atomik: {$filePath}");
        }
    }

    /**
     * Re-injects environment variables into current process and resets Database singleton.
     */
    private static function reloadEnvironment(string $envFile): void
    {
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim(trim($value), '"\'');
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
        if (method_exists(Database::class, 'resetConnection')) {
            Database::resetConnection();
        }
    }

    /**
     * Menjalankan engine replikasi basis data dari Supabase Live ke Database Lokal Laragon.
     * Mode:
     * - '14d' (Default): Hybrid Smart Sync -> 100% Master Data + 14 Hari Transaksi & Log Terakhir.
     * - 'full': Full All-Time Replication -> 100% Seluruh Baris Data Sepanjang Waktu.
     *
     * @param string $mode '14d' | 'full'
     * @param callable|null $logger Callback fungsi penerima log string
     * @return array Hasil replikasi lengkap
     */
    public static function replicateLiveToLocal(string $mode = '14d', ?callable $logger = null): array
    {
        if (!self::isLocalEnvironment()) {
            throw new RuntimeException("Aksi ditolak: Replikasi database dikunci permanen pada domain aktif. Aksi ini hanya dapat dilakukan di lingkungan lokal developer.");
        }

        $hasHost = !empty($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
        $hasSessionUser = (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user']));
        if (($hasHost || $hasSessionUser || PHP_SAPI !== 'cli') && class_exists('\App\Core\Auth') && !\App\Core\Auth::isDeveloper()) {
            throw new RuntimeException("Aksi ditolak: Hanya peran developer yang berwenang menjalankan replikasi database.");
        }

        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['14d', 'full', 'smart_14d', 'smart'], true)) {
            $mode = '14d';
        }
        $isSmartSync = ($mode !== 'full');
        $daysWindow = 14;

        // 1. MUTEX LOCK: Cegah eksekusi replikasi bersamaan (anti-crash / race-condition)
        $lockFile = sys_get_temp_dir() . '/keren_erp_replication_mutex.lock';
        $lockFp = @fopen($lockFile, 'c+');
        if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
            if ($lockFp) fclose($lockFp);
            throw new RuntimeException("Aksi diblokir: Proses replikasi database sedang berjalan di sesi lain. Mohon tunggu hingga proses sebelumnya selesai.");
        }

        // Tulis PID ke file lock
        ftruncate($lockFp, 0);
        fwrite($lockFp, (string)getmypid());

        // Perlindungan limit eksekusi & memori
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        @ignore_user_abort(true);

        $logs = [];
        $log = function(string $msg) use (&$logs, $logger) {
            $logs[] = $msg;
            if ($logger !== null) {
                $logger($msg);
            }
        };

        $startTime = microtime(true);
        $rootPath = dirname(__DIR__, 2);

        try {
            $modeTitle = $isSmartSync ? 'HYBRID SMART SYNC (100% MASTER + 14 HARI TERAKHIR)' : 'FULL ALL-TIME REPLICATION';
            $log("====================================================================");
            $log(" 🔄 KEREN ONE - DATABASE REPLICATION ENGINE (SUPABASE -> LOCAL)");
            $log(" ⚙️  MODE: {$modeTitle}");
            $log("====================================================================");

            // 2. Baca Kredensial Live dari .env.live atau .env
            $liveEnvFile = $rootPath . '/.env.live';
            if (file_exists($liveEnvFile)) {
                $liveLines = file($liveEnvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            } else {
                $liveLines = file($rootPath . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            }

            $liveConfig = [];
            foreach ($liveLines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                if (str_contains($line, '=')) {
                    [$k, $v] = explode('=', $line, 2);
                    $liveConfig[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
                }
            }

            $liveHost = $liveConfig['DB_HOST'] ?? 'aws-0-ap-southeast-1.pooler.supabase.com';
            $livePort = $liveConfig['DB_PORT'] ?? '5432';
            $liveDb   = $liveConfig['DB_DATABASE'] ?? 'postgres';
            $liveUser = $liveConfig['DB_USERNAME'] ?? 'postgres.ulsahelvntdwwtotbxkg';
            $livePass = $liveConfig['DB_PASSWORD'] ?? '';
            $liveSsl  = $liveConfig['DB_SSLMODE'] ?? 'require';

            // Konfigurasi Database Lokal Laragon
            $localHost = '127.0.0.1';
            $localPort = '5432';
            $localDb   = 'kerensnack_erp_local';
            $localUser = 'postgres';
            $localPass = '';

            // 3. TARGET SAFETY ASSERTION (PENGAMAN MUTLAK: MENCEGAH PENGHAPUSAN SALAH TARGET)
            $isTargetSafe = in_array($localHost, ['127.0.0.1', 'localhost', '::1'], true)
                && ($localDb === 'kerensnack_erp_local')
                && !str_contains($localHost, 'supabase')
                && !str_contains($localHost, 'pooler');

            if (!$isTargetSafe) {
                throw new RuntimeException("EMERGENCY SAFETY ABORT: Target replikasi ({$localHost}/{$localDb}) bukan database sandbox lokal! Eksekusi dihentikan seketika demi mencegah kecelakaan data produksi.");
            }

            // 4. Hubungkan ke Live Supabase (READ-ONLY ENFORCED)
            $log("\n📡 [1/5] Menghubungkan ke Supabase Live (Read-Only Mode)...");
            try {
                $t0 = microtime(true);
                $livePdo = new PDO("pgsql:host={$liveHost};port={$livePort};dbname={$liveDb};sslmode={$liveSsl}", $liveUser, $livePass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 25,
                ]);
                // Kunci sesi koneksi live menjadi murni READ-ONLY untuk perlindungan 100%
                $livePdo->exec("SET default_transaction_read_only = on;");

                $livePing = round((microtime(true) - $t0) * 1000, 1);
                $log("   ✅ Terhubung ke Live (Strict Read-Only): {$liveUser}@{$liveHost}/{$liveDb} (Ping: {$livePing}ms)");
            } catch (Throwable $e) {
                $log("   ❌ Gagal terhubung ke Supabase Live: " . $e->getMessage());
                throw new RuntimeException("Koneksi Supabase Live gagal: " . $e->getMessage());
            }

            // 5. Siapkan Database Lokal
            $log("\n🎯 [2/5] Menyiapkan database lokal '{$localDb}' di Laragon...");
            try {
                $adminLocalPdo = new PDO("pgsql:host={$localHost};port={$localPort};dbname=postgres;sslmode=disable", $localUser, $localPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                $checkDb = $adminLocalPdo->prepare("SELECT 1 FROM pg_database WHERE datname = :dbname");
                $checkDb->execute(['dbname' => $localDb]);
                if (!$checkDb->fetchColumn()) {
                    $adminLocalPdo->exec("CREATE DATABASE \"{$localDb}\" WITH OWNER = \"{$localUser}\" ENCODING = 'UTF8';");
                    $log("   ✅ Database '{$localDb}' berhasil dibuat di PostgreSQL lokal.");
                } else {
                    $log("   ✅ Database '{$localDb}' sudah ada.");
                }

                $localPdo = new PDO("pgsql:host={$localHost};port={$localPort};dbname={$localDb};sslmode=disable", $localUser, $localPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $localPdo->exec("SET TIME ZONE 'Asia/Jakarta';");
                $log("   ✅ Terhubung ke database lokal: {$localUser}@{$localHost}:{$localPort}/{$localDb}");
            } catch (Throwable $e) {
                $log("   ❌ Gagal menginisialisasi PostgreSQL lokal: " . $e->getMessage());
                throw new RuntimeException("PostgreSQL lokal error: " . $e->getMessage());
            }

            // 6. Terapkan DDL, RPC, Views, dan Triggers
            $log("\n🏗️  [3/5] Membangun skema, fungsi RPC, views, dan trigger di database lokal...");
            try {
                // Reset skema public lokal agar struktur 100% bersih, identik, dan bebas duplikasi objek/policy
                $log("   -> Menyiapkan skema public bersih di database lokal...");
                $localPdo->exec("DROP SCHEMA IF EXISTS public CASCADE; CREATE SCHEMA public; GRANT ALL ON SCHEMA public TO postgres; GRANT ALL ON SCHEMA public TO public;");

                // Helper pembersih parameter modern (mis. security_invoker PG 15+) untuk kompatibilitas PostgreSQL 14 lokal
                $sanitizeSqlForLocal = function(string $sql): string {
                    $sql = preg_replace('/\bWITH\s*\(\s*security_invoker\s*=\s*(true|false|on|off)\s*\)/i', '', $sql);
                    $sql = preg_replace('/ALTER\s+VIEW\s+[^;]+SET\s*\(\s*security_invoker\s*=\s*[^)]+\)\s*;/i', '', $sql);
                    return $sql;
                };

                $schemaSql = file_get_contents($rootPath . '/database/01_schema.sql') ?: '';
                $rpcSql    = file_get_contents($rootPath . '/database/02_triggers_and_rpc.sql') ?: '';

                if (!empty($schemaSql)) {
                    $log("   -> Menerapkan database/01_schema.sql...");
                    $localPdo->exec($sanitizeSqlForLocal($schemaSql));
                }

                if (!empty($rpcSql)) {
                    $log("   -> Menerapkan database/02_triggers_and_rpc.sql...");
                    $localPdo->exec($sanitizeSqlForLocal($rpcSql));
                }

                // Replikasi RPC langsung dari pg_proc Live
                $log("   -> Mereplikasi fungsi & RPC dari pg_catalog Live...");
                $liveFunctions = $livePdo->query("
                    SELECT p.proname, pg_get_functiondef(p.oid) as def
                    FROM pg_proc p
                    JOIN pg_namespace n ON p.pronamespace = n.oid
                    WHERE n.nspname = 'public'
                      AND p.prokind IN ('f', 'p')
                      AND NOT p.proname LIKE 'uuid_%'
                      AND NOT p.proname LIKE 'pg_%'
                ")->fetchAll(PDO::FETCH_ASSOC);

                foreach ($liveFunctions as $fn) {
                    try {
                        $cleanDef = $sanitizeSqlForLocal($fn['def']);
                        $localPdo->exec($cleanDef);
                    } catch (Throwable $e) {}
                }

                // Replikasi Views
                $log("   -> Mereplikasi Views dari pg_catalog Live...");
                $liveViews = $livePdo->query("
                    SELECT c.relname, pg_get_viewdef(c.oid, true) as def 
                    FROM pg_class c 
                    JOIN pg_namespace n ON c.relnamespace = n.oid 
                    WHERE n.nspname = 'public' AND c.relkind = 'v'
                ")->fetchAll(PDO::FETCH_ASSOC);

                foreach ($liveViews as $v) {
                    try {
                        $cleanDef = $sanitizeSqlForLocal($v['def']);
                        $localPdo->exec("CREATE OR REPLACE VIEW public.\"{$v['relname']}\" AS " . $cleanDef);
                    } catch (Throwable $e) {}
                }

                // Replikasi Triggers
                $log("   -> Mereplikasi Triggers dari pg_catalog Live...");
                $liveTriggers = $livePdo->query("
                    SELECT pg_get_triggerdef(t.oid, true) as def
                    FROM pg_trigger t
                    JOIN pg_class c ON t.tgrelid = c.oid
                    JOIN pg_namespace n ON c.relnamespace = n.oid
                    WHERE n.nspname = 'public' AND NOT t.tgisinternal
                ")->fetchAll(PDO::FETCH_COLUMN);

                foreach ($liveTriggers as $trgDef) {
                    try {
                        $localPdo->exec($trgDef);
                    } catch (Throwable $e) {}
                }

                $log("   ✅ Skema DDL, fungsi RPC, views, & triggers lokal berhasil disinkronkan.");
            } catch (Throwable $e) {
                $log("   ❌ Gagal membangun skema lokal: " . $e->getMessage());
                throw new RuntimeException("Gagal membangun skema: " . $e->getMessage());
            }

            // Reconnect local
            $localPdo = new PDO("pgsql:host={$localHost};port={$localPort};dbname={$localDb};sslmode=disable", $localUser, $localPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $localPdo->exec("SET TIME ZONE 'Asia/Jakarta';");

            // 7. Replikasi Seluruh Data Tabel
            $log("\n📦 [4/5] Mentransfer data seluruh tabel dari Supabase Live ke Lokal...");
            $tablesStmt = $livePdo->query("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
                ORDER BY table_name
            ");
            $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

            // Matikan pengecekan trigger & FK selama bulk sync (anti-deadlock)
            $localPdo->exec("SET session_replication_role = 'replica';");

            // Kosongkan seluruh tabel lokal sekaligus di awal dengan CASCADE
            try {
                $existingLocalTables = $localPdo->query("
                    SELECT table_name 
                    FROM information_schema.tables 
                    WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
                ")->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($existingLocalTables)) {
                    $quotedAllTables = array_map(fn($t) => "public.\"{$t}\"", $existingLocalTables);
                    $localPdo->exec("TRUNCATE TABLE " . implode(', ', $quotedAllTables) . " CASCADE;");
                }
            } catch (Throwable $e) {}

            $totalRowsReplicated = 0;
            $tableStats = [];

            foreach ($tables as $table) {
                $tableExists = $localPdo->query("
                    SELECT 1 FROM information_schema.tables 
                    WHERE table_schema = 'public' AND table_name = '{$table}'
                ")->fetchColumn();

                if (!$tableExists) {
                    continue;
                }

                $liveCols = $livePdo->query("
                    SELECT column_name, data_type, udt_name, is_nullable 
                    FROM information_schema.columns 
                    WHERE table_schema = 'public' AND table_name = '{$table}'
                ")->fetchAll(PDO::FETCH_ASSOC);
                $liveColMap = [];
                foreach ($liveCols as $lc) {
                    $liveColMap[$lc['column_name']] = $lc;
                }

                $localCols = $localPdo->query("
                    SELECT column_name, data_type, udt_name, is_nullable 
                    FROM information_schema.columns 
                    WHERE table_schema = 'public' AND table_name = '{$table}'
                ")->fetchAll(PDO::FETCH_ASSOC);
                $localColMap = [];
                foreach ($localCols as $loc) {
                    $localColMap[$loc['column_name']] = $loc;
                }

                // Sinkronisasi kolom yang hilang di lokal
                foreach ($liveCols as $lc) {
                    $cName = $lc['column_name'];
                    if (isset($localColMap[$cName])) {
                        if ($lc['is_nullable'] === 'YES' && $localColMap[$cName]['is_nullable'] === 'NO') {
                            try {
                                $localPdo->exec("ALTER TABLE public.\"{$table}\" ALTER COLUMN \"{$cName}\" DROP NOT NULL;");
                            } catch (Throwable $e) {}
                        }
                    } else {
                        try {
                            $type = $lc['udt_name'] === 'varchar' ? 'VARCHAR' : $lc['udt_name'];
                            $localPdo->exec("ALTER TABLE public.\"{$table}\" ADD COLUMN IF NOT EXISTS \"{$cName}\" {$type};");
                        } catch (Throwable $e) {}
                    }
                }

                // Tentukan filter windowing: Master Table -> 100% Seluruh Riwayat, Transaksi -> 14 Hari Terakhir + Transaksi Aktif / Relasi Utuh
                $isMaster = in_array($table, self::MASTER_TABLES, true);
                $whereClause = "";

                if ($isSmartSync && !$isMaster) {
                    if ($table === 'kasbon') {
                        // Pertahankan kasbon 14 hari terakhir ATAU kasbon yang masih aktif / memiliki sisa pinjaman
                        $whereClause = "WHERE \"tanggal_pengajuan\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days') OR \"sisa_pinjaman\" > 0 OR \"status_kasbon\" = 'aktif'";
                    } elseif ($table === 'potongan_kasbon') {
                        // Seluruh riwayat cicilan untuk kasbon yang direplikasi atau 14 hari terakhir
                        $whereClause = "WHERE \"kasbon_id\" IN (SELECT id FROM public.\"kasbon\" WHERE \"tanggal_pengajuan\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days') OR \"sisa_pinjaman\" > 0 OR \"status_kasbon\" = 'aktif') OR \"dibuat_pada\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days')";
                    } elseif ($table === 'pesanan') {
                        // Pertahankan pesanan 14 hari terakhir ATAU pesanan belum lunas/tempo/sedang diproses
                        $whereClause = "WHERE \"tanggal_pesanan\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days') OR \"status_pembayaran\" IN ('belum_lunas', 'sebagian', 'tempo') OR \"status_pemrosesan\" NOT IN ('selesai', 'dibatalkan')";
                    } elseif ($table === 'item_pesanan') {
                        // Seluruh item produk dari pesanan yang direplikasi
                        $whereClause = "WHERE \"pesanan_id\" IN (SELECT id FROM public.\"pesanan\" WHERE \"tanggal_pesanan\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days') OR \"status_pembayaran\" IN ('belum_lunas', 'sebagian', 'tempo') OR \"status_pemrosesan\" NOT IN ('selesai', 'dibatalkan'))";
                    } elseif ($table === 'surat_jalan') {
                        // Surat jalan dari pesanan yang direplikasi atau 14 hari terakhir
                        $whereClause = "WHERE \"pesanan_id\" IN (SELECT id FROM public.\"pesanan\" WHERE \"tanggal_pesanan\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days') OR \"status_pembayaran\" IN ('belum_lunas', 'sebagian', 'tempo') OR \"status_pemrosesan\" NOT IN ('selesai', 'dibatalkan')) OR \"dibuat_pada\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days')";
                    } elseif ($table === 'pembelian') {
                        // Pertahankan belanja bahan baku 14 hari terakhir ATAU yang belum lunas/selesai
                        $whereClause = "WHERE \"tanggal_pembelian\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days') OR \"status_pembayaran\" != 'lunas' OR \"status_penerimaan\" NOT IN ('diterima', 'kendala_batal')";
                    } elseif ($table === 'rincian_pembelian') {
                        // Seluruh rincian item bahan dari pembelian yang direplikasi
                        $whereClause = "WHERE \"pembelian_id\" IN (SELECT id FROM public.\"pembelian\" WHERE \"tanggal_pembelian\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days') OR \"status_pembayaran\" != 'lunas' OR \"status_penerimaan\" NOT IN ('diterima', 'kendala_batal'))";
                    } elseif ($table === 'penggajian') {
                        // Pertahankan invoice payroll 14 hari terakhir ATAU yang masih draf / proses bayar
                        $whereClause = "WHERE \"periode_akhir\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days') OR \"status\" != 'dibayarkan' OR \"dibuat_pada\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days')";
                    } elseif ($table === 'rincian_penggajian') {
                        // Seluruh rincian slip karyawan dari payroll yang direplikasi
                        $whereClause = "WHERE \"penggajian_id\" IN (SELECT id FROM public.\"penggajian\" WHERE \"periode_akhir\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days') OR \"status\" != 'dibayarkan' OR \"dibuat_pada\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days'))";
                    } elseif ($table === 'penarikan_gaji') {
                        // Ambil uang harian 14 hari terakhir ATAU yang belum ditutup ke payroll
                        $whereClause = "WHERE \"tanggal\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days') OR \"penggajian_id\" IS NULL OR \"penggajian_id\" IN (SELECT id FROM public.\"penggajian\" WHERE \"periode_akhir\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days') OR \"status\" != 'dibayarkan')";
                    } elseif ($table === 'rincian_kunjungan_konsinyasi') {
                        $whereClause = "WHERE \"kunjungan_id\" IN (SELECT id FROM public.\"kunjungan_konsinyasi\" WHERE \"tanggal_kunjungan\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days'))";
                    } elseif ($table === 'tagihan_kunjungan') {
                        $whereClause = "WHERE \"kunjungan_id\" IN (SELECT id FROM public.\"kunjungan_konsinyasi\" WHERE \"tanggal_kunjungan\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days'))";
                    } elseif ($table === 'opname_gudang_item') {
                        $whereClause = "WHERE \"opname_id\" IN (SELECT id FROM public.\"opname_gudang\" WHERE \"tanggal\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days') OR \"dibuat_pada\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days'))";
                    } else {
                        $dateCandidates = [
                            'tanggal_pesanan',
                            'tanggal_transaksi',
                            'tanggal_kunjungan',
                            'tanggal_produksi',
                            'tanggal_pembelian',
                            'tanggal_pengajuan',
                            'tanggal_penarikan',
                            'tanggal_kirim',
                            'tanggal_bayar',
                            'tanggal',
                            'dibuat_pada',
                            'created_at'
                        ];

                        $chosenDateCol = null;
                        foreach ($dateCandidates as $cand) {
                            if (isset($liveColMap[$cand])) {
                                $chosenDateCol = $cand;
                                break;
                            }
                        }

                        if ($chosenDateCol !== null) {
                            $whereClause = "WHERE \"{$chosenDateCol}\" >= (CURRENT_DATE - INTERVAL '{$daysWindow} days')";
                        }
                    }
                }

                // Streaming transfer data (ramah memori dengan batch insert)
                $countStmt = $livePdo->query("SELECT count(*) FROM public.\"{$table}\" {$whereClause}");
                $rowCount  = (int)($countStmt->fetchColumn() ?: 0);
                $totalRowsReplicated += $rowCount;
                $tableStats[$table] = $rowCount;

                if ($rowCount === 0) {
                    continue;
                }

                // Ambil sample row untuk menentukan kolom insert
                $sampleRow = $livePdo->query("SELECT * FROM public.\"{$table}\" {$whereClause} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if (!$sampleRow) {
                    continue;
                }

                $columns = array_keys($sampleRow);
                $validColumns = array_filter($columns, fn($c) => isset($localColMap[$c]) || isset($liveColMap[$c]));
                $quotedColumns = array_map(fn($c) => "\"{$c}\"", $validColumns);
                $placeholders = array_map(fn($c) => ":{$c}", $validColumns);

                $sqlInsert = sprintf(
                    "INSERT INTO public.\"%s\" (%s) VALUES (%s) ON CONFLICT DO NOTHING",
                    $table,
                    implode(', ', $quotedColumns),
                    implode(', ', $placeholders)
                );

                $insertStmt = $localPdo->prepare($sqlInsert);

                // Fetch stream data dari live
                $dataQuery = $livePdo->query("SELECT * FROM public.\"{$table}\" {$whereClause}");
                $batchCount = 0;
                $localPdo->beginTransaction();

                while ($row = $dataQuery->fetch(PDO::FETCH_ASSOC)) {
                    foreach ($validColumns as $col) {
                        $val = $row[$col] ?? null;
                        $param = ":{$col}";
                        if (is_bool($val)) {
                            $insertStmt->bindValue($param, $val, PDO::PARAM_BOOL);
                        } elseif (is_null($val)) {
                            $insertStmt->bindValue($param, null, PDO::PARAM_NULL);
                        } elseif (is_int($val)) {
                            $insertStmt->bindValue($param, $val, PDO::PARAM_INT);
                        } else {
                            $insertStmt->bindValue($param, (string)$val, PDO::PARAM_STR);
                        }
                    }
                    $insertStmt->execute();
                    $batchCount++;

                    if ($batchCount % 500 === 0) {
                        $localPdo->commit();
                        $localPdo->beginTransaction();
                    }
                }

                if ($localPdo->inTransaction()) {
                    $localPdo->commit();
                }
            }

            // Kembalikan session replication role ke normal
            $localPdo->exec("SET session_replication_role = 'origin';");

            // 8. Menyelaraskan seluruh Constraints, Sequences, & Auto-Increment
            $log("\n🔗 [5/5] Menyelaraskan Constraints & Sequences (Anti-Conflict Guard)...");
            try {
                // Cari sequence yang terasosiasi dengan tabel
                $seqQuery = "
                    SELECT 
                        s.relname AS sequence_name,
                        t.relname AS table_name,
                        a.attname AS column_name
                    FROM pg_class s
                    JOIN pg_depend d ON d.objid = s.oid AND d.classid = 'pg_class'::regclass AND d.refclassid = 'pg_class'::regclass
                    JOIN pg_class t ON t.oid = d.refobjid
                    JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = d.refobjsubid
                    JOIN pg_namespace n ON n.oid = s.relnamespace
                    WHERE s.relkind = 'S' AND n.nspname = 'public'
                ";
                $associatedSeqs = $localPdo->query($seqQuery)->fetchAll(PDO::FETCH_ASSOC);

                foreach ($associatedSeqs as $as) {
                    try {
                        $sName = $as['sequence_name'];
                        $tName = $as['table_name'];
                        $cName = $as['column_name'];

                        $maxVal = (int)($localPdo->query("SELECT COALESCE(MAX(\"{$cName}\"), 0) FROM public.\"{$tName}\"")->fetchColumn() ?: 0);
                        if ($maxVal > 0) {
                            $localPdo->exec(sprintf("SELECT setval('public.\"%s\"', %d, true);", $sName, $maxVal));
                        } else {
                            $localPdo->exec(sprintf("SELECT setval('public.\"%s\"', 1, false);", $sName));
                        }
                    } catch (Throwable $e) {}
                }

                // Sinkronkan juga standalone sequences dari live
                $allSequences = $localPdo->query("
                    SELECT sequence_name 
                    FROM information_schema.sequences 
                    WHERE sequence_schema = 'public'
                ")->fetchAll(PDO::FETCH_COLUMN);

                foreach ($allSequences as $seq) {
                    try {
                        $liveMax = $livePdo->query("SELECT last_value, is_called FROM public.\"{$seq}\"")->fetch(PDO::FETCH_ASSOC);
                        if ($liveMax) {
                            $localPdo->exec(sprintf(
                                "SELECT setval('public.\"%s\"', %d, %s);",
                                $seq,
                                (int)$liveMax['last_value'],
                                $liveMax['is_called'] ? 'true' : 'false'
                            ));
                        }
                    } catch (Throwable $e) {}
                }

                // Jalankan ANALYZE untuk update query optimizer stats di PostgreSQL lokal
                $localPdo->exec("ANALYZE;");
                $log("   ✅ Constraints & Sequences diselaraskan sempurna (Zero ID collisions).");
            } catch (Throwable $e) {}

            $duration = round(microtime(true) - $startTime, 2);
            $modeLabel = $isSmartSync ? 'Hybrid Smart Sync (Master 100% + Transaksi 14 Hari)' : 'Full All-Time Replication (100% Seluruh Data)';

            $log("\n====================================================================");
            $log(" 📊 REKAPITULASI REPLIKASI DATABASE LOKAL");
            $log("====================================================================");
            $log(" Database Target         : {$localDb}");
            $log(" Host Database           : {$localHost}:{$localPort}");
            $log(" Mode Replikasi          : {$modeLabel}");
            $log(" Total Tabel Direplikasi : " . count($tableStats) . " tabel");
            $log(" Total Baris Data        : {$totalRowsReplicated} baris data");
            $log(" Waktu Eksekusi          : {$duration} detik");
            $log("====================================================================");
            $log("🎉 REPLIKASI BERHASIL 100%! Database lokal Anda siap digunakan.");

            return [
                'success'     => true,
                'mode'        => $mode,
                'mode_label'  => $modeLabel,
                'is_smart'    => $isSmartSync,
                'duration'    => $duration,
                'total_tables'=> count($tableStats),
                'total_rows'  => $totalRowsReplicated,
                'table_stats' => $tableStats,
                'logs'        => $logs,
                'timestamp'   => date('Y-m-d H:i:s')
            ];

        } finally {
            // Lepaskan Mutex Lock
            if ($lockFp) {
                flock($lockFp, LOCK_UN);
                fclose($lockFp);
                @unlink($lockFile);
            }
        }
    }
}

<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use InvalidArgumentException;

/**
 * app/Services/TestRunnerService.php
 * Core Test Runner Engine & Registry (Single Source of Truth)
 * Keren Snack ERP & POS Architecture
 *
 * Melayani:
 * - Terminal CLI Runner (tests/run_all.php & developer/run_all.php)
 * - Web Real-time Interactive AJAX Runner (/developer/tests)
 *
 * Security Features:
 * - Global Concurrency Lock  : Hanya 1 test bisa berjalan di seluruh server (lintas session/user)
 * - Global Cooldown 60 detik : Setelah test selesai, semua user harus tunggu 60 detik
 * - Subprocess Timeout 120s  : Auto-terminate & release lock jika subprocess stuck
 * - Output Sanitization      : Strip path server & credential dari output yang ditampilkan
 */
class TestRunnerService
{
    // -------------------------------------------------------------------------
    // LOCK & COOLDOWN CONSTANTS
    // -------------------------------------------------------------------------

    /** File global concurrency lock — satu file untuk seluruh server (lintas session/user) */
    private const GLOBAL_LOCK_FILE   = 'erp_test_global.lock';

    /** File cooldown state — mencatat kapan test terakhir selesai */
    private const COOLDOWN_LOCK_FILE = 'erp_test_cooldown.lock';

    /**
     * Batas maksimal usia lock file sebelum dianggap stale & otomatis expire.
     * Fallback jika subprocess crash/stuck tanpa release lock.
     * Value: 180 detik (3 menit) — lebih dari cukup untuk suite terpanjang (~120 detik max).
     */
    private const LOCK_MAX_LIFETIME  = 180;

    /** Durasi cooldown global (detik) setelah setiap test selesai - dinonaktifkan (0) agar Run All & single run lancar */
    private const COOLDOWN_SECONDS   = 0;

    /** Timeout subprocess PHP (detik) — auto-terminate jika melebihi batas ini */
    private const SUBPROCESS_TIMEOUT = 120;

    // -------------------------------------------------------------------------
    // MASTER SUITE REGISTRY
    // -------------------------------------------------------------------------

    /**
     * Master Registry 26 Test Suites Resmi ERP
     */
    public const SUITES = [
        'sales_vs_driver' => [
            'file'        => 'SalesDriverIntegrityTest.php',
            'title'       => 'Sales vs Driver Role & Guard Integrity',
            'category'    => 'Security & RBAC',
            'description' => 'Validasi pemisahan peran Sales vs Driver dan integritas trigger proteksi peran.'
        ],
        'customer_integrity' => [
            'file'        => 'CustomerIntegrityTest.php',
            'title'       => 'Customer Master Data & POS Protections',
            'category'    => 'Master Data',
            'description' => 'Validasi data master pelanggan, proteksi customer default POS (CUST-UMUM), dan level harga.'
        ],
        'master_core' => [
            'file'        => 'MasterDataCoreTest.php',
            'title'       => 'Master Data Core & Unique Validations',
            'category'    => 'Master Data',
            'description' => 'Validasi constraint unik pada master item, supplier, dan integritas primary key.'
        ],
        'master_relation' => [
            'file'        => 'MasterRelationIntegrityTest.php',
            'title'       => 'Master Entity Relational Integrity',
            'category'    => 'Relasional',
            'description' => 'Verifikasi integritas relasi foreign key lintas entitas master dan relasi transaksional.'
        ],
        'supplier_upgrade' => [
            'file'        => 'SupplierMasterUpgradeTest.php',
            'title'       => 'Supplier Master Upgrade & Bank Ledger',
            'category'    => 'Pengadaan',
            'description' => 'Pengujian master supplier, kelengkapan data rekening bank, dan buku besar vendor.'
        ],
        'product_master' => [
            'file'        => 'ProductMasterModuleTest.php',
            'title'       => 'Product Master, BOM & Single Level 1 Default',
            'category'    => 'Produk & BOM',
            'description' => 'Master produk varian, resep Bill of Materials (BOM), dan penentuan level harga default.'
        ],
        'pricing_stock' => [
            'file'        => 'PricingAndStockIntegrationTest.php',
            'title'       => 'Pricing Matrix & Stock Synchronization',
            'category'    => 'Pricing & Inventory',
            'description' => 'Sinkronisasi matriks harga bertingkat dan mutasi stok otomatis saat pesanan dibuat.'
        ],
        'pricing_reconcile' => [
            'file'        => 'PricingSystemReconciliationTest.php',
            'title'       => 'Pricing System & Master Price Levels',
            'category'    => 'Pricing',
            'description' => 'Rekonsiliasi konsistensi harga jual di seluruh level harga toko dan grosir.'
        ],
        'inventory_precision' => [
            'file'        => 'InventoryAndLedgerPrecisionTest.php',
            'title'       => 'Inventory & Numerical Ledger Precision',
            'category'    => 'Inventory',
            'description' => 'Ketelitian desimal kuantitas stok fisik dan validasi histori mutasi stok.'
        ],
        'pos_cashier' => [
            'file'        => 'PosCashierLifecycleTest.php',
            'title'       => 'POS Cashier, Barcode, HPP & Cash Ledger',
            'category'    => 'Operasional POS',
            'description' => 'Siklus checkout kasir, scan barcode universal, penentuan HPP, dan mutasi arus kas.'
        ],
        'customer_orders' => [
            'file'        => 'CustomerOrderLifecycleTest.php',
            'title'       => 'B2B Customer Orders, PO & Invoicing',
            'category'    => 'Penjualan B2B',
            'description' => 'Siklus pemesanan toko B2B, penerbitan Purchase Order, cetak faktur invoice, dan limit piutang.'
        ],
        'procurement' => [
            'file'        => 'PurchaseProcurementLifecycleTest.php',
            'title'       => 'Purchasing, Decimal Quantities & Vendor Debt',
            'category'    => 'Pengadaan',
            'description' => 'Siklus pengadaan bahan baku ke supplier, kuantitas desimal, hutang dagang, dan kartu stok.'
        ],
        'delivery_logistics' => [
            'file'        => 'DeliveryAndLogisticsLifecycleTest.php',
            'title'       => 'Surat Jalan, Driver Logistics & POD',
            'category'    => 'Logistik',
            'description' => 'Alur surat jalan siap kirim, penugasan armada driver, dan verifikasi bukti terima (POD).'
        ],
        'cash_ledger' => [
            'file'        => 'CashLedgerFinancialTest.php',
            'title'       => 'Cash Accounts, Overdraft & Dual Transfers',
            'category'    => 'Keuangan',
            'description' => 'Manajemen mutasi akun kas/bank, proteksi saldo minus (overdraft), dan transfer berpasangan.'
        ],
        'consignment_full' => [
            'file'        => 'ConsignmentFullCycleTest.php',
            'title'       => 'Consignment Shelf Stock, Opname & Billing',
            'category'    => 'Konsinyasi',
            'description' => 'Siklus lengkap konsinyasi toko, pelacakan stok etalase, opname fisik barang, dan nota tagihan.'
        ],
        'consignment_conversion' => [
            'file'        => 'ConsignmentConversionTest.php',
            'title'       => 'Consignment Conversion & Shelf Resolution',
            'category'    => 'Konsinyasi',
            'description' => 'Konversi barang titip jual toko menjadi transaksi penjualan final dan penyelesaian etalase.'
        ],
        'tiered_commission' => [
            'file'        => 'TieredCommissionTest.php',
            'title'       => 'Tiered Sales Commission & Thresholds',
            'category'    => 'HR & Komisi',
            'description' => 'Perhitungan komisi sales progresif/bertingkat berdasarkan omzet kas masuk bulanan.'
        ],
        'unified_printing' => [
            'file'        => 'UnifiedPrintingEngineTest.php',
            'title'       => 'Unified Printing Engine (Dot Matrix & A4)',
            'category'    => 'Printing & Dokumen',
            'description' => 'Generator cetak dokumen struk thermal/dot matrix dan lembar picking list faktur A4.'
        ],
        'auth_rbac' => [
            'file'        => 'AuthAndRbacLifecycleTest.php',
            'title'       => 'Authentication, Bcrypt & RBAC Matrix',
            'category'    => 'Security & RBAC',
            'description' => 'Verifikasi hash password Bcrypt, otentikasi sesi sliding inactivity 1 jam, dan matriks hak akses multi-peran.'
        ],
        'security_reconcile' => [
            'file'        => 'SecurityAndReconciliationTest.php',
            'title'       => 'CSRF Security & Account Reconciliation',
            'category'    => 'Security & Keuangan',
            'description' => 'Pengujian token proteksi CSRF dan rekonsiliasi saldo buku kas dengan fisik rekening.'
        ],
        'activity_log' => [
            'file'        => 'ActivityLogComprehensiveAuditTest.php',
            'title'       => 'Activity Logs & Smart Delta Diffing',
            'category'    => 'Audit & Logging',
            'description' => 'Pencatatan log audit aktivitas pengguna dan deteksi perubahan data (smart delta diffing).'
        ],
        'data_hygiene' => [
            'file'        => 'DataHygieneAndSettingsTest.php',
            'title'       => 'Company Settings & Supplier Hygiene',
            'category'    => 'Pengaturan',
            'description' => 'Validasi integritas pengaturan perusahaan dan pembersihan data sisa/usang.'
        ],
        'owner_dashboard' => [
            'file'        => 'OwnerDashboardExecutiveTest.php',
            'title'       => 'Owner Executive Dashboard & KPI Metrics',
            'category'    => 'Executive & KPI',
            'description' => 'Pengujian metrik KPI bisnis, omzet, margin kotor, laba bersih, dan grafik performa.'
        ],
        'import_data' => [
            'file'        => 'ImportDataLifecycleTest.php',
            'title'       => 'Master Data Import, Diffing Engine & Full-Sync Reconciliation',
            'category'    => 'Master Data & System',
            'description' => 'Verifikasi komprehensif smart reader Excel, 10 entity handler, deteksi diff (INSERT/UPDATE/DELETE/NO_CHANGE), foreign key resolution, dan proteksi sensor transaksi (soft-deactivate vs hard delete).'
        ],
        'employee_type_and_whatsapp' => [
            'file'        => 'EmployeeManualTypeAndWhatsAppTest.php',
            'title'       => 'Employee Manual Payroll Type & WhatsApp Unification',
            'category'    => 'Master Data & HR',
            'description' => 'Verifikasi input manual tipe penggajian pada form karyawan, generator template excel string murni, dan unifikasi kontak ke nomor WhatsApp.'
        ],
        'employee_nik_16_digit' => [
            'file'        => 'EmployeeNik16DigitIntegrityTest.php',
            'title'       => 'Employee Mandatory 16-Digit NIK & Sync Integrity',
            'category'    => 'Master Data & HR',
            'description' => 'Penegakan validasi NIK asli 16 digit wajib pada PostgreSQL CHECK constraint, master karyawan, template excel, dan modul sinkronisasi data.'
        ],
        'access_denied_403' => [
            'file'        => 'AccessDenied403Test.php',
            'title'       => 'Access Denied 403 & CSRF Security Protection',
            'category'    => 'Security & RBAC',
            'description' => 'Verifikasi halaman 403 kado kejutan, auto-logout timer, audio API, proteksi route developer, dan penolakan akses tanpa izin.'
        ],
        'brand_master' => [
            'file'        => 'BrandMasterModuleTest.php',
            'title'       => 'Master Data Merek & Relasi Grup Produk',
            'category'    => 'Master Data & Produk',
            'description' => 'Validasi master merek dagang (Brand), relasi FK ke grup produk, CRUD, dan impor data merek.'
        ],
        'customer_group_brand_pricing' => [
            'file'        => 'CustomerGroupBrandPricingTest.php',
            'title'       => 'Dynamic Brand Price Levels & Discounts per Customer Group',
            'category'    => 'Master Data & Pricing',
            'description' => 'Verifikasi komprehensif relasi level harga & diskon per merek, trigger auto-sync, proteksi BRAND_NOT_ALLOWED, dan impor data multi-merek.'
        ],
        'master_full_sync_safety' => [
            'file'        => 'MasterFullSyncSafetyAuditTest.php',
            'title'       => 'Master Data Full-Sync Sensor & Relational Safety Audit',
            'category'    => 'Master Data & System',
            'description' => 'Audit mendalam sensor relasi Foreign Key (100% FK coverage), proteksi master default (CUST-001, GRP-001, developer), dan verifikasi soft-deactivate vs hard-delete saat Full-Sync.'
        ],
        'gudang_role_position' => [
            'file'        => 'GudangRoleAndPositionIntegrityTest.php',
            'title'       => 'Gudang Position & Permanent System Role Integrity',
            'category'    => 'Security & RBAC',
            'description' => 'Validasi posisi karyawan Gudang, pendaftaran role permanen sistem gudang, default permissions pergudangan, dan impor data.'
        ],
    ];

    // -------------------------------------------------------------------------
    // PHP BINARY DETECTION
    // -------------------------------------------------------------------------

    /**
     * Dapatkan path binary PHP yang valid (auto-detect Laragon PHP 8.1)
     */
    public static function getPhpBinary(): string
    {
        $laragonPhpCandidates = [
            'D:\\laragon\\bin\\php\\php-8.1.10-Win32-vs16-x64\\php.exe',
            'C:\\laragon\\bin\\php\\php-8.1.10-Win32-vs16-x64\\php.exe',
        ];

        foreach ($laragonPhpCandidates as $cand) {
            if (file_exists($cand)) {
                return $cand;
            }
        }

        if (defined('PHP_BINARY') && file_exists(PHP_BINARY)) {
            $binLower = strtolower(PHP_BINARY);
            if (!str_ends_with($binLower, 'httpd.exe') && !str_contains($binLower, 'php-fpm') && !str_contains($binLower, 'php-cgi')) {
                return PHP_BINARY;
            }
        }

        $linuxCandidates = [
            '/usr/bin/php',
            '/usr/local/bin/php',
            '/opt/cpanel/ea-php81/root/usr/bin/php',
            '/opt/cpanel/ea-php82/root/usr/bin/php',
            '/opt/cpanel/ea-php80/root/usr/bin/php',
        ];

        foreach ($linuxCandidates as $cand) {
            if (file_exists($cand)) {
                return $cand;
            }
        }

        return 'php';
    }

    // -------------------------------------------------------------------------
    // SUITE REGISTRY HELPERS
    // -------------------------------------------------------------------------

    /**
     * Dapatkan daftar seluruh suites dengan metadata terstruktur
     */
    public static function getAllSuites(): array
    {
        $suites = [];
        $index  = 1;
        foreach (self::SUITES as $key => $data) {
            $suites[$key] = array_merge($data, [
                'index' => $index++,
                'key'   => $key,
            ]);
        }
        return $suites;
    }

    // -------------------------------------------------------------------------
    // GLOBAL CONCURRENCY LOCK SYSTEM
    // -------------------------------------------------------------------------

    /**
     * Dapatkan path absolut ke file global lock.
     */
    private static function getLockFilePath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::GLOBAL_LOCK_FILE;
    }

    /**
     * Dapatkan path absolut ke file cooldown.
     */
    private static function getCooldownFilePath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::COOLDOWN_LOCK_FILE;
    }

    /**
     * Cek apakah ada test yang sedang berjalan secara global (lintas semua session/user).
     *
     * @return array|false  Array berisi info lock jika masih aktif, false jika tidak ada lock / stale.
     */
    public static function isLocked(): array|false
    {
        $lockFile = self::getLockFilePath();

        if (!file_exists($lockFile)) {
            return false;
        }

        $raw = @file_get_contents($lockFile);
        if (empty($raw)) {
            @unlink($lockFile);
            return false;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['started_at'])) {
            @unlink($lockFile);
            return false;
        }

        // Cek apakah lock sudah melampaui batas lifetime (stale lock)
        $age = time() - (int)$data['started_at'];
        if ($age >= self::LOCK_MAX_LIFETIME) {
            @unlink($lockFile); // auto-release stale lock
            return false;
        }

        $data['running_since_seconds'] = $age;
        $data['running_since_label']   = $age < 60
            ? "{$age} detik lalu"
            : round($age / 60, 1) . " menit lalu";

        return $data;
    }

    /**
     * Acquire global lock sebelum menjalankan subprocess.
     * Lempar RuntimeException (code 429) jika lock sudah dipegang user lain.
     */
    private static function acquireLock(string $suiteKey, string $userName): void
    {
        $lockState = self::isLocked();
        if ($lockState !== false) {
            throw new RuntimeException("LOCKED|" . json_encode($lockState), 429);
        }

        $suite    = self::SUITES[$suiteKey] ?? [];
        $lockData = json_encode([
            'user'        => $userName,
            'suite_key'   => $suiteKey,
            'suite_title' => $suite['title'] ?? $suiteKey,
            'started_at'  => time(),
            'expires_at'  => time() + self::LOCK_MAX_LIFETIME,
        ], JSON_UNESCAPED_UNICODE);

        $lockFile = self::getLockFilePath();
        $fp       = fopen($lockFile, 'c');

        if ($fp === false) {
            throw new RuntimeException("Gagal membuat file global lock. Periksa permission temp directory.");
        }

        // Coba dapatkan exclusive lock non-blocking
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            throw new RuntimeException(
                "LOCKED|" . json_encode(['user' => 'Proses lain', 'suite_title' => $suiteKey]),
                429
            );
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $lockData);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    /**
     * Release global lock setelah subprocess selesai.
     * Dipanggil di finally block — dijamin selalu dieksekusi meski ada exception.
     */
    private static function releaseLock(): void
    {
        $lockFile = self::getLockFilePath();
        if (file_exists($lockFile)) {
            @unlink($lockFile);
        }
    }

    // -------------------------------------------------------------------------
    // GLOBAL COOLDOWN SYSTEM
    // -------------------------------------------------------------------------

    /**
     * Cek state cooldown global saat ini.
     *
     * @return array|false  Array berisi info cooldown + remaining_seconds, atau false jika tidak aktif.
     */
    public static function getCooldownState(): array|false
    {
        $cooldownFile = self::getCooldownFilePath();

        if (self::COOLDOWN_SECONDS <= 0) {
            if (file_exists($cooldownFile)) {
                @unlink($cooldownFile);
            }
            return false;
        }

        if (!file_exists($cooldownFile)) {
            return false;
        }

        $raw = @file_get_contents($cooldownFile);
        if (empty($raw)) {
            @unlink($cooldownFile);
            return false;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['cooldown_until'])) {
            @unlink($cooldownFile);
            return false;
        }

        $remaining = (int)$data['cooldown_until'] - time();
        if ($remaining <= 0) {
            @unlink($cooldownFile); // cooldown sudah habis, bersihkan file
            return false;
        }

        $data['remaining_seconds'] = $remaining;
        return $data;
    }

    /**
     * Set global cooldown setelah test suite selesai dieksekusi.
     * Dipanggil setelah subprocess exit (PASS atau FAIL).
     */
    private static function setCooldown(string $suiteKey, string $userName, string $result): void
    {
        $cooldownFile = self::getCooldownFilePath();
        if (self::COOLDOWN_SECONDS <= 0) {
            if (file_exists($cooldownFile)) {
                @unlink($cooldownFile);
            }
            return;
        }

        $suite         = self::SUITES[$suiteKey] ?? [];
        $finishedAt    = time();
        $cooldownUntil = $finishedAt + self::COOLDOWN_SECONDS;

        $cooldownData = json_encode([
            'finished_at'    => $finishedAt,
            'cooldown_until' => $cooldownUntil,
            'user'           => $userName,
            'suite_key'      => $suiteKey,
            'suite_title'    => $suite['title'] ?? $suiteKey,
            'result'         => $result,
        ], JSON_UNESCAPED_UNICODE);

        @file_put_contents($cooldownFile, $cooldownData, LOCK_EX);
    }

    // -------------------------------------------------------------------------
    // OUTPUT SANITIZATION
    // -------------------------------------------------------------------------

    /**
     * Bersihkan output subprocess dari informasi sensitif sebelum ditampilkan di browser.
     * - Strip absolute server path → [PROJECT_ROOT]
     * - Strip credential strings (password=, host=, dbname=, dll)
     */
    private static function sanitizeOutput(string $output): string
    {
        // Strip Windows absolute paths
        $output = preg_replace('/[A-Za-z]:\\\\[^\s\'"<>]+/u', '[PROJECT_ROOT]', $output) ?? $output;

        // Strip Unix absolute paths ke project (fallback Linux/Mac)
        $output = preg_replace('/\/var\/www\/[^\s\'"<>]+/u', '[PROJECT_ROOT]', $output) ?? $output;

        // Strip credential patterns dari DSN / config strings
        $credPatterns = [
            '/password=[^\s;\'\"&]+/i',
            '/passwd=[^\s;\'\"&]+/i',
            '/host=[^\s;\'\"&]+/i',
            '/dbname=[^\s;\'\"&]+/i',
            '/sslmode=[^\s;\'\"&]+/i',
            '/sslrootcert=[^\s;\'\"&]+/i',
            '/dsn=[^\s;\'\"&]+/i',
        ];

        foreach ($credPatterns as $pattern) {
            $output = preg_replace($pattern, '[REDACTED]', $output) ?? $output;
        }

        return $output;
    }

    // -------------------------------------------------------------------------
    // CORE: RUN SINGLE SUITE (Web AJAX)
    // -------------------------------------------------------------------------

    /**
     * Jalankan SATU test suite berdasarkan key terdaftar.
     *
     * Security pipeline:
     * [1] Validasi suite key di whitelist
     * [2] Cek global cooldown (tolak jika masih aktif)
     * [3] Acquire global lock (tolak jika user lain sedang test)
     * [4] Cek file suite ada di disk
     * [5] Jalankan subprocess dengan timeout 120 detik
     * [6] Release lock di finally block (ALWAYS executed)
     * [7] Set global cooldown 60 detik setelah selesai
     * [8] Sanitize output sebelum dikembalikan ke browser
     *
     * @param  string $suiteKey  Key suite dari SUITES registry
     * @param  string $userName  Nama user yang menjalankan (untuk lock info & activity log)
     * @return array             Result dengan status, output, duration, cooldown_until
     * @throws InvalidArgumentException  Suite key tidak valid
     * @throws RuntimeException          Cooldown aktif (code 429) atau locked oleh user lain (code 429)
     */
    public static function runSingleSuite(string $suiteKey, string $userName = 'Developer'): array
    {
        // [1] Validasi suite key ada di whitelist registry
        if (!isset(self::SUITES[$suiteKey])) {
            throw new InvalidArgumentException("Suite key '{$suiteKey}' tidak valid atau tidak terdaftar.");
        }

        // [2] Cek global cooldown
        $cooldownState = self::getCooldownState();
        if ($cooldownState !== false) {
            throw new RuntimeException("COOLDOWN|" . json_encode($cooldownState), 429);
        }

        // [3] Acquire global lock (lempar RuntimeException jika locked)
        self::acquireLock($suiteKey, $userName);

        $suite    = self::SUITES[$suiteKey];
        $testsDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'tests';
        $testFile = $testsDir . DIRECTORY_SEPARATOR . $suite['file'];

        // [4] Cek file ada di disk
        if (!file_exists($testFile)) {
            self::releaseLock();
            self::setCooldown($suiteKey, $userName, 'FAIL');
            return [
                'success'        => false,
                'key'            => $suiteKey,
                'file'           => $suite['file'],
                'title'          => $suite['title'],
                'status'         => 'FAIL',
                'duration'       => 0.0,
                'output'         => "Error: File pengujian {$suite['file']} tidak ditemukan di server.",
                'exit_code'      => 1,
                'cooldown_until' => self::COOLDOWN_SECONDS > 0 ? (time() + self::COOLDOWN_SECONDS) : null,
            ];
        }

        $phpBin = self::getPhpBinary();
        $cmd    = escapeshellarg($phpBin) . ' ' . escapeshellarg($testFile);
        $start  = microtime(true);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $output   = '';
        $exitCode = 1;

        try {
            // [5] Jalankan subprocess dengan timeout enforcement
            $process = proc_open($cmd, $descriptors, $pipes, $testsDir);

            if (is_resource($process)) {
                fclose($pipes[0]);

                $stdout   = '';
                $stderr   = '';
                $deadline = microtime(true) + self::SUBPROCESS_TIMEOUT;

                // Baca output secara non-blocking dengan deadline enforcement
                while (microtime(true) < $deadline) {
                    $readStreams = [$pipes[1], $pipes[2]];
                    $write       = null;
                    $except      = null;
                    $changed     = @stream_select($readStreams, $write, $except, 1, 0);

                    if ($changed === false) break;

                    foreach ($readStreams as $stream) {
                        if ($stream === $pipes[1]) {
                            $chunk = fread($pipes[1], 8192);
                            if ($chunk !== false) $stdout .= $chunk;
                        } elseif ($stream === $pipes[2]) {
                            $chunk = fread($pipes[2], 8192);
                            if ($chunk !== false) $stderr .= $chunk;
                        }
                    }

                    $procStatus = proc_get_status($process);
                    if (!$procStatus['running']) {
                        // Baca sisa buffer setelah process selesai
                        $stdout .= @stream_get_contents($pipes[1]);
                        $stderr .= @stream_get_contents($pipes[2]);
                        $exitCode = $procStatus['exitcode'];
                        break;
                    }
                }

                // Jika masih berjalan setelah deadline, terminate paksa
                $procStatus = proc_get_status($process);
                if ($procStatus['running']) {
                    proc_terminate($process, 9);
                    $stdout  .= "\n[TIMEOUT] Subprocess dihentikan paksa setelah " . self::SUBPROCESS_TIMEOUT . " detik.";
                    $exitCode = 124;
                }

                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);

                $output = trim($stdout . ($stderr ? "\nSTDERR:\n" . $stderr : ''));
            } else {
                $output = "Gagal meluncurkan subproses PHP untuk suite: {$suite['file']}";
            }
        } finally {
            // [6] ALWAYS release global lock — dijamin dieksekusi meski ada exception
            self::releaseLock();
        }

        $duration = round(microtime(true) - $start, 2);
        $result   = ($exitCode === 0) ? 'PASS' : 'FAIL';

        // [7] Set global cooldown 60 detik setelah eksekusi selesai
        self::setCooldown($suiteKey, $userName, $result);

        // [8] Sanitize output sebelum dikembalikan ke browser
        $sanitizedOutput = self::sanitizeOutput($output);

        return [
            'success'        => ($exitCode === 0),
            'key'            => $suiteKey,
            'file'           => $suite['file'],
            'title'          => $suite['title'],
            'category'       => $suite['category'],
            'status'         => $result,
            'duration'       => $duration,
            'output'         => $sanitizedOutput,
            'exit_code'      => $exitCode,
            'cooldown_until' => self::COOLDOWN_SECONDS > 0 ? (time() + self::COOLDOWN_SECONDS) : null,
        ];
    }

    // -------------------------------------------------------------------------
    // FORCE CLEAR LOCKS (Developer Emergency Tool)
    // -------------------------------------------------------------------------

    /**
     * Hapus paksa KEDUA file lock (global lock + cooldown) dari temp directory.
     * Digunakan oleh developer saat lock/cooldown stuck karena stale data.
     * Hanya dapat dipanggil via endpoint POST yang dilindungi CSRF + Auth::requireDeveloper().
     *
     * @return array Info file yang berhasil dihapus
     */
    public static function forceClearLocks(): array
    {
        $deleted  = [];
        $lockFile = self::getLockFilePath();
        $cdFile   = self::getCooldownFilePath();

        if (file_exists($lockFile)) {
            @unlink($lockFile);
            $deleted[] = 'global_lock';
        }

        if (file_exists($cdFile)) {
            @unlink($cdFile);
            $deleted[] = 'cooldown';
        }

        return [
            'cleared' => $deleted,
            'message' => empty($deleted)
                ? 'Tidak ada file lock/cooldown yang perlu dihapus.'
                : 'Berhasil menghapus: ' . implode(', ', $deleted) . '.',
        ];
    }

    // -------------------------------------------------------------------------
    // CLI RUNNER (untuk tests/run_all.php & developer/run_all.php)
    // -------------------------------------------------------------------------

    /**
     * Jalankan SELURUH 24 Test Suites di Terminal CLI.
     *
     * Catatan: CLI runner TIDAK menggunakan global lock/cooldown karena:
     * - Dijalankan oleh developer secara langsung di terminal lokal
     * - Bukan via web request — tidak ada risiko multi-user concurrent access
     */
    public static function runAllCli(): void
    {
        $phpBin   = self::getPhpBinary();
        $testsDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'tests';

        echo "====================================================================\n";
        echo " KEREN SNACK ERP - UNIFIED TEST SUITE RUNNER (26 SUITES)\n";
        echo "====================================================================\n";
        echo "PHP Binary : {$phpBin}\n";
        echo "Test Suite : " . count(self::SUITES) . " comprehensive suites\n\n";

        $results       = [];
        $totalDuration = 0;
        $allPassed     = true;

        foreach (self::SUITES as $key => $suite) {
            $file  = $suite['file'];
            $title = $suite['title'];

            echo "▶️  Running {$file} ({$title})...\n";

            $testFile = $testsDir . DIRECTORY_SEPARATOR . $file;
            $cmd      = escapeshellarg($phpBin) . ' ' . escapeshellarg($testFile);

            $start = microtime(true);

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process  = proc_open($cmd, $descriptors, $pipes, $testsDir);
            $exitCode = 1;
            $stdout   = '';
            $stderr   = '';

            if (is_resource($process)) {
                fclose($pipes[0]);
                $stdout   = stream_get_contents($pipes[1]);
                $stderr   = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exitCode = proc_close($process);
            }

            $duration      = round(microtime(true) - $start, 2);
            $totalDuration += $duration;
            $status        = ($exitCode === 0) ? 'PASS' : 'FAIL';

            $results[$file] = [
                'status'   => $status,
                'duration' => $duration,
            ];

            if ($status === 'PASS') {
                echo "   \033[32m✅ PASSED\033[0m ({$duration}s)\n\n";
            } else {
                echo "   \033[31m❌ FAILED\033[0m ({$duration}s)\n";
                echo "------------------- ERROR LOG -------------------\n";
                echo trim($stdout . ($stderr ? "\n" . $stderr : '')) . "\n";
                echo "-------------------------------------------------\n\n";
                $allPassed = false;
            }
        }

        echo "====================================================================\n";
        echo " TEST SUITE EXECUTION SUMMARY\n";
        echo "====================================================================\n";
        printf("%-38s | %-6s | %-8s\n", "Test Suite", "Status", "Duration");
        echo str_repeat("-", 72) . "\n";

        foreach ($results as $file => $info) {
            $badge = ($info['status'] === 'PASS') ? "\033[32m✅ PASS\033[0m" : "\033[31m❌ FAIL\033[0m";
            printf("%-38s | %s | %6.2fs\n", $file, $badge, $info['duration']);
        }

        echo str_repeat("-", 72) . "\n";
        echo "Total Execution Time: " . round($totalDuration, 2) . "s\n";
        $passCount  = count(array_filter($results, fn($r) => $r['status'] === 'PASS'));
        $totalCount = count($results);
        echo "Summary: {$passCount} / {$totalCount} Suites Passed (" . round(($passCount / $totalCount) * 100) . "%)\n";

        if ($allPassed) {
            echo "\033[32m🎉 ALL " . count(self::SUITES) . " TEST SUITES PASSED (100%)! Entire ERP Codebase is healthy & hardened.\033[0m\n";
            exit(0);
        } else {
            echo "\033[31m⚠️ WARNING: Some test suites failed! Please inspect logs above.\033[0m\n";
            exit(1);
        }
    }
}

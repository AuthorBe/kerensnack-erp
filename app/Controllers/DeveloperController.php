<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\TestRunnerService;
use App\Helpers\CSRF;
use App\Helpers\ActivityLog;
use Database;
use Throwable;
use RuntimeException;
use InvalidArgumentException;

/**
 * app/Controllers/DeveloperController.php
 * Portal Terpadu Developer: Architecture Blueprint, Database Diagnostics & Automated Test Runner.
 * Khusus Peran Developer (Auth::requireDeveloper()).
 */
class DeveloperController extends Controller
{
    public function __construct()
    {
        Auth::requireDeveloper();
    }

    /**
     * Portal Utama Hub Developer (3 Pilihan: Arsitektur, Test DB, Test Source)
     */
    public function index(): void
    {
        try {
            $tablesCount = (int)(Database::fetchOne("
                SELECT count(*) as total 
                FROM information_schema.tables 
                WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
            ")['total'] ?? 0);

            $proceduresCount = (int)(Database::fetchOne("
                SELECT count(*) as total
                FROM information_schema.routines
                WHERE routine_schema = 'public'
            ")['total'] ?? 0);

            $telemetry = [
                'php_version'      => PHP_VERSION,
                'db_driver'        => 'PostgreSQL 17 (Supabase SSL Pooler)',
                'total_tables'     => $tablesCount,
                'total_procedures' => $proceduresCount,
                'total_suites'     => count(TestRunnerService::SUITES),
                'active_user'      => Auth::name(),
                'active_role'      => Auth::role(),
                'server_time'      => date('d M Y H:i:s') . ' WIB',
                'session_timeout'  => \App\Core\Auth::INACTIVITY_TIMEOUT . ' detik (1 Jam)',
                'last_activity'    => date('d M Y H:i:s', (int)($_SESSION['last_activity'] ?? time())) . ' WIB'
            ];

            $this->view('developer.index', [
                'pageTitle'    => 'Developer Command Center',
                'pageSubtitle' => 'Portal Terpadu Arsitektur, Diagnostik Database, dan Verifikasi Otomatis',
                'telemetry'    => $telemetry,
                'csrfToken'    => CSRF::token()
            ]);
        } catch (Throwable $e) {
            echo "Developer Portal Error: " . htmlspecialchars($e->getMessage());
        }
    }

    /**
     * Menu 1: Visual Architecture Blueprint & AI Assistant Tool
     */
    public function architecture(): void
    {
        try {
            // Ambil seluruh 45 tabel skema public lengkap dengan perkiraan live row count
            $tables = Database::fetchAll("
                SELECT relname as table_name, COALESCE(n_live_tup, 0) as row_count 
                FROM pg_stat_user_tables 
                WHERE schemaname = 'public' 
                ORDER BY relname ASC
            ");

            // Ambil seluruh stored procedure dan fungsi skema public
            $procedures = Database::fetchAll("
                SELECT routine_name, routine_type
                FROM information_schema.routines
                WHERE routine_schema = 'public'
                ORDER BY routine_name ASC
            ");

            $controllersCount = count(glob(ROOT_PATH . '/app/Controllers/*.php'));
            $helpersCount     = count(glob(ROOT_PATH . '/app/Helpers/*.php'));

            $systemInfo = [
                'php_version'       => PHP_VERSION,
                'db_driver'         => 'PostgreSQL 17 (Supabase SSL Pooler)',
                'total_tables'      => count($tables),
                'total_procedures'  => count($procedures),
                'total_controllers' => $controllersCount ?: 24,
                'total_helpers'     => $helpersCount ?: 13,
                'total_suites'      => count(TestRunnerService::SUITES),
                'active_user'       => Auth::name() ?? 'Developer',
                'active_role'       => Auth::role() ?? 'developer',
                'server_time'       => date('d M Y H:i:s') . ' WIB',
            ];

            $this->view('developer.architecture', [
                'pageTitle'    => 'System Architecture Blueprint & AI Studio',
                'pageSubtitle' => 'Pusat Kendali Arsitektur & Generator Prompt Konteks AI Programming Logic',
                'tables'       => $tables,
                'procedures'   => $procedures,
                'systemInfo'   => $systemInfo,
            ]);

        } catch (Throwable $e) {
            echo "Developer Architecture Error: " . htmlspecialchars($e->getMessage());
        }
    }

    /**
     * Menu 2: Diagnostik Database (Test DB & Healthcheck)
     */
    public function testDb(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $_SESSION['healthcheck_authenticated'] = true;

        $testDbPath = dirname(__DIR__, 2) . '/developer/test_db.php';
        if (file_exists($testDbPath)) {
            require $testDbPath;
            exit;
        } else {
            echo "File diagnostik developer/test_db.php tidak ditemukan.";
        }
    }

    /**
     * Menu 3: Test Source / Run-All Console
     * Pass lock state & cooldown state ke view agar UI bisa render banner status secara tepat
     */
    public function tests(): void
    {
        $suites = TestRunnerService::getAllSuites();

        // Cek state global — apakah ada test yang sedang berjalan atau dalam cooldown
        $lockState     = TestRunnerService::isLocked();       // false atau array info lock
        $cooldownState = TestRunnerService::getCooldownState(); // false atau array {remaining_seconds, cooldown_until, ...}

        $telemetry = [
            'php_version'  => PHP_VERSION,
            'total_suites' => count($suites),
            'active_user'  => Auth::name(),
            'server_time'  => date('d M Y H:i:s') . ' WIB'
        ];

        $this->view('developer.tests', [
            'pageTitle'     => 'Test Source & Lifecycle Console',
            'pageSubtitle'  => 'Eksekusi Real-time ' . count($suites) . ' Test Suites ERP (Anti-Timeout, Rollback Aman)',
            'suites'        => $suites,
            'telemetry'     => $telemetry,
            'csrfToken'     => CSRF::token(),
            'lockState'     => $lockState,
            'cooldownState' => $cooldownState,
        ]);
    }

    /**
     * AJAX Endpoint: Jalankan Satu Test Suite Secara Terisolasi
     *
     * Security pipeline:
     * [1] Validasi CSRF token secara eksplisit
     * [2] Validasi suite_key tidak kosong
     * [3] Delegasi ke TestRunnerService::runSingleSuite() — lock/cooldown dicek di sana
     * [4] Catat ActivityLog setiap request (termasuk yang ditolak cooldown/lock)
     */
    public function runSingleTest(): void
    {
        $userName = Auth::name() . ' (' . Auth::role() . ')';

        // [1] Validasi CSRF token secara eksplisit — tolak jika token tidak valid
        if (!CSRF::validate()) {
            $this->logTestActivity($userName, 'BLOCKED_CSRF', 'unknown', 'BLOCK', 0.0);
            $this->json([
                'success' => false,
                'error'   => 'Token keamanan tidak valid (CSRF). Muat ulang halaman dan coba kembali.'
            ], 403);
            return;
        }

        // [2] Validasi suite_key tidak kosong
        $suiteKey = trim((string)($_POST['suite_key'] ?? ''));

        if (empty($suiteKey)) {
            $this->json([
                'success' => false,
                'error'   => 'Parameter suite_key tidak boleh kosong.'
            ], 400);
            return;
        }

        // [3] Jalankan suite via TestRunnerService
        try {
            $result = TestRunnerService::runSingleSuite($suiteKey, $userName);

            // [4] Catat ke ActivityLog — test berhasil dieksekusi
            $this->logTestActivity(
                $userName,
                'RUN_TEST_SUITE',
                $suiteKey,
                $result['status'],
                $result['duration'],
                $result['title'] ?? $suiteKey
            );

            $this->json($result);

        } catch (RuntimeException $e) {
            $msg  = $e->getMessage();
            $code = $e->getCode();

            // Tangani Cooldown Exception
            if (str_starts_with($msg, 'COOLDOWN|')) {
                $cooldownData = json_decode(substr($msg, 9), true) ?? [];
                $this->logTestActivity($userName, 'BLOCKED_COOLDOWN', $suiteKey, 'BLOCK', 0.0);
                $this->json([
                    'success'                  => false,
                    'blocked'                  => 'cooldown',
                    'error'                    => 'Sistem sedang dalam periode cooldown. Harap tunggu sebelum menjalankan test berikutnya.',
                    'cooldown_remaining'        => $cooldownData['remaining_seconds'] ?? 60,
                    'cooldown_until'           => $cooldownData['cooldown_until'] ?? (time() + 60),
                    'last_suite_title'         => $cooldownData['suite_title'] ?? '',
                    'last_suite_result'        => $cooldownData['result'] ?? '',
                    'last_run_user'            => $cooldownData['user'] ?? '',
                ], 429);
                return;
            }

            // Tangani Lock Exception (user lain sedang test)
            if (str_starts_with($msg, 'LOCKED|')) {
                $lockData = json_decode(substr($msg, 7), true) ?? [];
                $this->logTestActivity($userName, 'BLOCKED_LOCK', $suiteKey, 'BLOCK', 0.0);
                $this->json([
                    'success'             => false,
                    'blocked'             => 'lock',
                    'error'               => 'Test sedang berjalan oleh pengguna lain. Harap tunggu hingga proses selesai.',
                    'running_user'        => $lockData['user'] ?? 'Developer lain',
                    'running_suite_title' => $lockData['suite_title'] ?? '',
                    'running_since'       => $lockData['running_since_label'] ?? '',
                ], 429);
                return;
            }

            // RuntimeException lain
            $this->logTestActivity($userName, 'ERROR_RUNTIME', $suiteKey, 'FAIL', 0.0);
            $this->json(['success' => false, 'error' => $e->getMessage()], $code > 0 ? (int)$code : 500);

        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);

        } catch (Throwable $e) {
            $this->logTestActivity($userName, 'ERROR_UNEXPECTED', $suiteKey, 'FAIL', 0.0);
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper: Catat aktivitas test ke log_aktivitas database.
     * Tidak lempar exception agar tidak menginterupsi response utama.
     */
    private function logTestActivity(
        string $userName,
        string $action,
        string $suiteKey,
        string $result,
        float  $duration,
        string $suiteTitle = ''
    ): void {
        try {
            $desc = match ($action) {
                'RUN_TEST_SUITE'    => "Developer {$userName} menjalankan suite [{$suiteKey}] '{$suiteTitle}' — {$result} ({$duration}s)",
                'BLOCKED_CSRF'      => "Akses endpoint test ditolak karena token CSRF tidak valid. User: {$userName}",
                'BLOCKED_COOLDOWN'  => "Test [{$suiteKey}] ditolak karena cooldown global masih aktif. User: {$userName}",
                'BLOCKED_LOCK'      => "Test [{$suiteKey}] ditolak karena global lock aktif (user lain sedang test). User: {$userName}",
                'ERROR_RUNTIME'     => "Test [{$suiteKey}] gagal dengan RuntimeException. User: {$userName}",
                'ERROR_UNEXPECTED'  => "Test [{$suiteKey}] gagal dengan exception tidak terduga. User: {$userName}",
                default             => "Aktivitas test: {$action} — suite [{$suiteKey}]. User: {$userName}",
            };

            ActivityLog::log('developer', $action, $desc, 'test_runner', Auth::id());
        } catch (Throwable) {
            // Silent fail — jangan interupsi response utama
        }
    }

    /**
     * AJAX Endpoint: Force Clear Stale Lock & Cooldown Files
     * Digunakan developer saat banner lock/cooldown stuck tidak mau hilang.
     * Dilindungi: CSRF + requireDeveloper (sudah di constructor).
     */
    public function clearTestLocks(): void
    {
        if (!CSRF::validate()) {
            $this->json(['success' => false, 'error' => 'Token CSRF tidak valid.'], 403);
            return;
        }

        $result = TestRunnerService::forceClearLocks();

        $this->json([
            'success' => true,
            'cleared' => $result['cleared'],
            'message' => $result['message'],
        ]);
    }

    /**
     * Preview Halaman 403 Access Denied (Kado Kejutan) Khusus Developer
     */
    public function preview403(): void
    {
        $viewFile = ROOT_PATH . '/views/errors/403.php';
        if (file_exists($viewFile)) {
            $title = 'Preview: 403 – Akses Ditolak (Kado Kejutan)';
            $reason = 'developer_preview_mode';
            $isPreview = true;
            require $viewFile;
            exit;
        }
        $this->denyAccess('preview_not_found');
    }
}



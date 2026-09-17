<?php
declare(strict_types=1);

/**
 * tests/run_all.php
 * Keren Snack ERP - Unified Automated Test Suite Runner (23 Test Suites)
 * Thin wrapper delegating to App\Services\TestRunnerService (Single Source of Truth)
 * 
 * Usage:
 *   php tests/run_all.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Direct web access forbidden. Run via terminal: php tests/run_all.php\n");
}

require_once dirname(__DIR__) . '/app/Services/TestRunnerService.php';

use App\Services\TestRunnerService;

TestRunnerService::runAllCli();

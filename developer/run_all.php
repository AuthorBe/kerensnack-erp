<?php
declare(strict_types=1);

/**
 * developer/run_all.php
 * Developer Console CLI Shortcut for Unified Test Suite Runner (23 Suites)
 * Delegates to App\Services\TestRunnerService (Single Source of Truth)
 * 
 * Usage:
 *   php developer/run_all.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Direct web access forbidden. Run via terminal: php developer/run_all.php\n");
}

require_once dirname(__DIR__) . '/app/Services/TestRunnerService.php';

use App\Services\TestRunnerService;

TestRunnerService::runAllCli();


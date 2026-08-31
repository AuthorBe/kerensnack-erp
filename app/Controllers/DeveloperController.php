<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Controllers/DeveloperController.php
 * Portal Khusus Developer / Owner: Interactive Visual Architecture & AI Prompt Studio.
 */
class DeveloperController extends Controller
{
    public function __construct()
    {
        Auth::requireRole(['developer', 'owner']);
    }

    /**
     * Tampilan Utama Visual Architecture Blueprint & AI Assistant Tool
     */
    public function architecture(): void
    {
        try {
            // Ambil metadata tabel PostgreSQL secara dinamis
            $tables = Database::fetchAll("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
                ORDER BY table_name ASC
            ");

            // Ambil stored procedures & triggers
            $procedures = Database::fetchAll("
                SELECT routine_name, routine_type
                FROM information_schema.routines
                WHERE routine_schema = 'public'
                ORDER BY routine_name ASC
            ");

            // Sistem Telemetri & Info Lingkungan
            $systemInfo = [
                'php_version' => PHP_VERSION,
                'db_driver' => 'PostgreSQL (PDO SSL)',
                'total_tables' => count($tables),
                'total_procedures' => count($procedures),
                'active_user' => Auth::user()['nama_lengkap'] ?? Auth::name() ?? 'Developer',
                'active_role' => Auth::role() ?? 'developer',
                'server_time' => date('d M Y H:i:s'),
            ];

            $this->view('developer.architecture', [
                'pageTitle' => 'System Blueprint & AI Studio',
                'pageSubtitle' => 'Pusat Kendali Arsitektur & Generator Prompt Konteks AI Programming Logic',
                'tables' => $tables,
                'procedures' => $procedures,
                'systemInfo' => $systemInfo,
            ]);

        } catch (Throwable $e) {
            echo "Developer Portal Error: " . $e->getMessage();
        }
    }
}

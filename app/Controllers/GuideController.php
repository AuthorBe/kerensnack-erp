<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\CompanySetting;

/**
 * app/Controllers/GuideController.php
 * Portal Dokumentasi & Buku Panduan Operasional Menyeluruh (Standalone Reader Portal)
 */
class GuideController extends Controller
{
    /**
     * Menampilkan Halaman Buku Panduan Operasional Mandiri
     * (GET /guide atau GET /panduan)
     */
    public function index(): void
    {
        Auth::requireLogin();

        $comp = CompanySetting::getAll();
        $user = Auth::user();
        $userRole = Auth::role() ?? 'user';

        // Render standalone view tanpa master template layout
        require ROOT_PATH . '/views/guide/index.php';
    }
}

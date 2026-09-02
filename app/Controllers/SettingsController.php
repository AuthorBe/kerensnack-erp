<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Controllers/SettingsController.php
 * Pengendali Portal Pengaturan Sistem Terpadu (Settings Hub).
 * Menampilkan 2 Kartu Inti Utama (Hak Akses & Pengguna) + 1 Kartu Developer.
 */
class SettingsController extends Controller
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Dashboard Portal Pengaturan Sistem
     */
    public function index(): void
    {
        try {
            $totalUsers = (int)(Database::fetchOne("SELECT count(*) as total FROM public.pengguna WHERE status_aktif = TRUE")['total'] ?? 0);
            $totalRoles = (int)(Database::fetchOne("SELECT count(*) as total FROM public.peran")['total'] ?? 0);
            $totalPerms = (int)(Database::fetchOne("SELECT count(*) as total FROM public.izin")['total'] ?? 0);

            $this->view('settings.index', [
                'pageTitle' => 'Pengaturan Sistem',
                'pageSubtitle' => 'Pusat Manajemen Konfigurasi Aplikasi, Hak Akses & Akun Pengguna',
                'totalUsers' => $totalUsers,
                'totalRoles' => $totalRoles,
                'totalPerms' => $totalPerms,
                'userRole' => Auth::role(),
            ]);

        } catch (Throwable $e) {
            echo "Error Settings: " . $e->getMessage();
        }
    }
}

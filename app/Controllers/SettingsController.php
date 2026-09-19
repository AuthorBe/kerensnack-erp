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
            $totalUsers = (int)(Database::fetchOne("SELECT count(*) as total FROM public.pengguna WHERE nama_pengguna IS NOT NULL AND status_aktif = TRUE")['total'] ?? 0);
            $totalEmployees = (int)(Database::fetchOne("SELECT count(*) as total FROM public.karyawan")['total'] ?? 0);
            $totalRoles = (int)(Database::fetchOne("SELECT count(*) as total FROM public.peran")['total'] ?? 0);
            $totalPerms = (int)(Database::fetchOne("SELECT count(*) as total FROM public.izin")['total'] ?? 0);

            $company = \App\Helpers\CompanySetting::getAll();

            $this->view('settings.index', [
                'pageTitle' => 'Pengaturan Sistem',
                'pageSubtitle' => 'Pusat Manajemen Konfigurasi Aplikasi, Hak Akses & Akun Pengguna',
                'totalUsers' => $totalUsers,
                'totalEmployees' => $totalEmployees,
                'totalRoles' => $totalRoles,
                'totalPerms' => $totalPerms,
                'company' => $company,
                'userRole' => Auth::role(),
            ]);

        } catch (Throwable $e) {
            error_log("SettingsController index error: " . $e->getMessage());
            $this->flashError("Gagal memuat pengaturan sistem: " . $e->getMessage());
            $this->redirect('/');
        }
    }

    /**
     * Halaman Konfigurasi Profil & Informasi Perusahaan
     */
    public function company(): void
    {
        Auth::requirePermission('settings.company_manage');

        $company = \App\Helpers\CompanySetting::getAll();

        $this->view('settings.company.index', [
            'pageTitle' => 'Informasi Perusahaan',
            'pageSubtitle' => 'Konfigurasi Identitas Resmi Usaha, Kontak & Kop Dokumen Cetak',
            'company' => $company,
        ]);
    }

    /**
     * Simpan Perubahan Profil & Informasi Perusahaan
     */
    public function updateCompany(): void
    {
        Auth::requirePermission('settings.company_manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            \App\Core\Router::redirect('/settings/company');
            return;
        }

        if (!\App\Helpers\CSRF::validate($_POST['csrf_token'] ?? null)) {
            \App\Helpers\Flash::error('Sesi formulir telah kadaluarsa. Silakan coba kembali.');
            \App\Core\Router::redirect('/settings/company');
            return;
        }

        $oldData = \App\Helpers\CompanySetting::getAll();

        $nama = trim((string)($_POST['nama'] ?? ''));
        if ($nama === '') {
            \App\Helpers\Flash::error('Nama resmi usaha / perusahaan tidak boleh kosong.');
            \App\Core\Router::redirect('/settings/company');
            return;
        }

        $tagline = trim((string)($_POST['tagline'] ?? ''));
        $alamat = trim((string)($_POST['alamat'] ?? ''));
        $telepon = trim((string)($_POST['telepon'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $website = trim((string)($_POST['website'] ?? ''));
        $catatanFaktur = trim((string)($_POST['catatan_faktur'] ?? ''));
        $namaBank = trim((string)($_POST['nama_bank'] ?? ''));
        $nomorRekening = trim((string)($_POST['nomor_rekening'] ?? ''));
        $atasNamaBank = trim((string)($_POST['atas_nama_bank'] ?? ''));

        // Handle logo
        $logoUrl = $oldData['logo_url'] ?? '';
        if (!empty($_POST['hapus_logo']) && $_POST['hapus_logo'] === '1') {
            $logoUrl = '';
        }

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes, true)) {
                \App\Helpers\Flash::error('Format logo tidak didukung. Harap unggah gambar PNG, JPG, WEBP, atau SVG.');
                \App\Core\Router::redirect('/settings/company');
                return;
            }

            if ($file['size'] > 2 * 1024 * 1024) {
                \App\Helpers\Flash::error('Ukuran berkas logo maksimal 2MB.');
                \App\Core\Router::redirect('/settings/company');
                return;
            }

            $ext = match ($mimeType) {
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg',
                default => 'png'
            };

            $targetDir = ROOT_PATH . '/public/assets/img/logo';
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
            $fileName = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $targetPath = $targetDir . '/' . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $logoUrl = '/assets/img/logo/' . $fileName;
            } else {
                \App\Helpers\Flash::error('Gagal menyimpan berkas logo ke server.');
                \App\Core\Router::redirect('/settings/company');
                return;
            }
        }

        $newData = [
            'nama'           => $nama,
            'tagline'        => $tagline,
            'alamat'         => $alamat,
            'telepon'        => $telepon,
            'email'          => $email,
            'website'        => $website,
            'catatan_faktur' => $catatanFaktur,
            'nama_bank'      => $namaBank,
            'nomor_rekening' => $nomorRekening,
            'atas_nama_bank' => $atasNamaBank,
            'logo_url'       => $logoUrl,
        ];

        $success = \App\Helpers\CompanySetting::save($newData);

        if ($success) {
            \App\Helpers\ActivityLog::log(
                'master_data',
                'UPDATE',
                "Memperbarui profil & informasi perusahaan: '{$nama}'",
                'pengaturan_sistem',
                null,
                $oldData,
                $newData
            );

            \App\Helpers\Flash::success('Profil dan informasi perusahaan berhasil diperbarui.');
        } else {
            \App\Helpers\Flash::error('Terjadi kesalahan saat menyimpan pengaturan perusahaan.');
        }

        \App\Core\Router::redirect('/settings/company');
    }
}

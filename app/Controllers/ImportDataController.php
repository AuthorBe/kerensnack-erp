<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Router;
use App\Helpers\CSRF;
use App\Helpers\Flash;
use App\Services\Import\ImportProcessor;
use App\Services\Import\TemplateGenerator;
use Database;
use Exception;
use PDO;

class ImportDataController
{
    /**
     * Memastikan hanya pengguna dengan izin sah yang dapat mengakses fitur impor
     */
    private static function checkAccess(?string $entityPermission = null): void
    {
        if (!Auth::check()) {
            Router::redirect('/login');
            exit;
        }

        // Izin sistem utama (Developer otomatis diizinkan via Auth::can)
        if (!Auth::can('system.import_data')) {
            Flash::error('Anda tidak memiliki izin untuk mengakses fitur Impor & Sinkronisasi Data.', 'Akses Ditolak');
            Router::redirect('/settings');
            exit;
        }

        // Izin modular entitas spesifik (jika ada)
        if ($entityPermission && !Auth::can($entityPermission)) {
            Flash::error('Anda tidak memiliki hak akses untuk mengelola tipe data ini.', 'Akses Ditolak');
            Router::redirect('/settings/impor-data');
            exit;
        }
    }

    /**
     * Halaman Utama / Panel Pratinjau Sinkronisasi Data
     */
    public function index(): void
    {
        self::checkAccess();

        $pdo = Database::pdo();
        $handlers = ImportProcessor::getHandlers();

        $previewData = null;
        $syncType    = $_SESSION['ks_sync_type'] ?? null;
        $syncMode    = $_SESSION['ks_sync_mode'] ?? 'update_insert';
        $previewFile = $_SESSION['ks_sync_preview_file'] ?? null;

        if (!empty($previewFile) && file_exists($previewFile)) {
            $previewData = json_decode((string)file_get_contents($previewFile), true);
            if (!is_array($previewData)) {
                $previewData = null;
            }
        }

        $activeHandler = $syncType ? ImportProcessor::getHandler($syncType) : null;

        // Statistik ringkas master data terkini di database
        $entityStats = [
            'brands'          => (int)$pdo->query("SELECT COUNT(*) FROM public.merek")->fetchColumn(),
            'territories'     => (int)$pdo->query("SELECT COUNT(*) FROM public.wilayah")->fetchColumn(),
            'customer_groups' => (int)$pdo->query("SELECT COUNT(*) FROM public.grup_pelanggan")->fetchColumn(),
            'product_groups'  => (int)$pdo->query("SELECT COUNT(*) FROM public.grup_produk")->fetchColumn(),
            'piece_rates'     => (int)$pdo->query("SELECT COUNT(*) FROM public.kelompok_upah_borongan")->fetchColumn(),
            'employees'       => (int)$pdo->query("SELECT COUNT(*) FROM public.pengguna WHERE posisi NOT IN ('developer')")->fetchColumn(),
            'suppliers'       => (int)$pdo->query("SELECT COUNT(*) FROM public.pemasok")->fetchColumn(),
            'pricing_matrix'  => (int)$pdo->query("SELECT COUNT(*) FROM public.grup_produk_harga_level")->fetchColumn(),
            'materials'       => (int)$pdo->query("SELECT COUNT(*) FROM public.item WHERE tipe_item != 'barang_jadi'")->fetchColumn(),
            'products'        => (int)$pdo->query("SELECT COUNT(*) FROM public.item WHERE tipe_item = 'barang_jadi'")->fetchColumn(),
            'customers'       => (int)$pdo->query("SELECT COUNT(*) FROM public.pelanggan")->fetchColumn(),
        ];

        // Daftar master wilayah aktif untuk fitur Kamus / Pencarian Referensi Wilayah
        $activeTerritories = $pdo->query("
            SELECT id, kode_rute, nama_wilayah, provinsi, kota_kabupaten, COALESCE(sub_wilayah, '') as sub_wilayah
            FROM public.wilayah
            WHERE status_aktif = TRUE
            ORDER BY kode_rute ASC, nama_wilayah ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle    = 'Impor & Sinkronisasi Data';
        $pageSubtitle = 'Pusat Pembaruan Massal Data Master via Excel / CSV sebagai Sumber Kebenaran';

        if ($previewData !== null) {
            $pageTitle    = 'Pratinjau Sinkronisasi — ' . ($activeHandler ? $activeHandler->getEntityLabel() : 'Master Data');
            $pageSubtitle = 'Verifikasi dan tinjau perbedaan data (Diff) sebelum diterapkan ke basis data';
            require ROOT_PATH . '/views/settings/impor_data/preview.php';
            return;
        }

        require ROOT_PATH . '/views/settings/impor_data/index.php';
    }

    /**
     * Unduh Template Excel (Template Kosong atau Data Terkini)
     */
    public function downloadTemplate(): void
    {
        $type = $_GET['tipe'] ?? '';
        $mode = $_GET['mode'] ?? 'empty'; // 'empty' atau 'current_data'

        $handler = ImportProcessor::getHandler($type);
        if (!$handler) {
            Flash::error('Tipe master data tidak valid.');
            Router::redirect('/settings/impor-data');
            return;
        }

        self::checkAccess($handler->getRequiredPermission());

        $pdo = Database::pdo();
        TemplateGenerator::generateAndDownload($handler, $mode, $pdo);
    }

    /**
     * Memproses unggahan file Excel dan menampilkan pratinjau perbandingan (Diff)
     */
    public function preview(): void
    {
        self::checkAccess();

        if (!CSRF::validate()) {
            Flash::error('Sesi halaman telah kedaluwarsa. Silakan muat ulang halaman dan coba lagi.', 'Token Tidak Valid');
            Router::redirect('/settings/impor-data');
            return;
        }

        $type = $_POST['tipe_data'] ?? '';
        $mode = $_POST['mode_sinkronisasi'] ?? 'update_insert';

        $handler = ImportProcessor::getHandler($type);
        if (!$handler) {
            Flash::error('Tipe master data yang dipilih tidak valid.');
            Router::redirect('/settings/impor-data');
            return;
        }

        self::checkAccess($handler->getRequiredPermission());

        if (!isset($_FILES['file_impor']) || $_FILES['file_impor']['error'] !== UPLOAD_ERR_OK) {
            Flash::error('Gagal mengunggah file. Pastikan Anda memilih file yang valid.', 'Berkas Tidak Ditemukan');
            Router::redirect('/settings/impor-data');
            return;
        }

        $fileExt = strtolower(pathinfo($_FILES['file_impor']['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, ['xlsx', 'xls', 'csv'], true)) {
            Flash::error('Format file tidak didukung. Harap gunakan format .xlsx atau .csv.', 'Format Tidak Sesuai');
            Router::redirect('/settings/impor-data');
            return;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'ks_sync_') . '.' . $fileExt;
        if (!move_uploaded_file($_FILES['file_impor']['tmp_name'], $tempFile)) {
            Flash::error('Gagal memindahkan file ke direktori sementara server.');
            Router::redirect('/settings/impor-data');
            return;
        }

        try {
            $pdo = Database::pdo();
            $result = ImportProcessor::processPreview($tempFile, $type, $mode, $pdo);

            // Simpan info sesi
            $_SESSION['ks_sync_temp_file']    = $tempFile;
            $_SESSION['ks_sync_preview_file'] = $result['preview_json_file'];
            $_SESSION['ks_sync_type']         = $type;
            $_SESSION['ks_sync_mode']         = $mode;
            $_SESSION['ks_sync_filename']     = $_FILES['file_impor']['name'];

            Router::redirect('/settings/impor-data');
        } catch (Exception $e) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
            Flash::error($e->getMessage(), 'Kendala Ekstraksi File');
            Router::redirect('/settings/impor-data');
        }
    }

    /**
     * Konfirmasi dan terapkan perubahan ke database dalam satu transaksi aman
     */
    public function confirm(): void
    {
        self::checkAccess();

        if (!CSRF::validate()) {
            Flash::error('Sesi verifikasi keamanan berakhir. Muat ulang dan coba kembali.', 'Sesi Berakhir');
            Router::redirect('/settings/impor-data');
            return;
        }

        $previewFile = $_SESSION['ks_sync_preview_file'] ?? '';
        $type        = $_SESSION['ks_sync_type'] ?? '';

        if (empty($previewFile) || !file_exists($previewFile) || empty($type)) {
            Flash::error('Sesi pratinjau sinkronisasi tidak valid atau sudah kedaluwarsa.');
            Router::redirect('/settings/impor-data');
            return;
        }

        $handler = ImportProcessor::getHandler($type);
        if ($handler) {
            self::checkAccess($handler->getRequiredPermission());
        }

        try {
            $pdo = Database::pdo();
            $filename = $_SESSION['ks_sync_filename'] ?? null;
            $mode     = $_SESSION['ks_sync_mode'] ?? 'update_insert';
            $result   = ImportProcessor::applySyncFromPreview($previewFile, $type, $pdo, $filename, $mode);

            $stats = $result['stats'];
            $label = $result['handler']->getEntityLabel();

            $msg = "Sinkronisasi <strong>{$label}</strong> berhasil diterapkan!<br>"
                 . "<span class='text-emerald-600 font-bold'>+{$stats['insert']}</span> data baru masuk, "
                 . "<span class='text-amber-600 font-bold'>~{$stats['update']}</span> diperbarui, "
                 . "<span class='text-rose-600 font-bold'>-{$stats['delete']}</span> dihapus permanen";

            if ($stats['deactivate'] > 0) {
                $msg .= ", dan <span class='text-purple-600 font-bold'>{$stats['deactivate']}</span> dinonaktifkan (karena memiliki riwayat transaksi/stok).";
            } else {
                $msg .= ".";
            }

            Flash::success($msg, 'Sinkronisasi Berhasil');
        } catch (Exception $e) {
            Flash::error($e->getMessage(), 'Sinkronisasi Dibatalkan');
        } finally {
            $this->cleanupSession();
        }

        Router::redirect('/settings/impor-data');
    }

    /**
     * Batalkan pratinjau dan bersihkan file sementara
     */
    public function cancel(): void
    {
        self::checkAccess();
        $this->cleanupSession();
        Flash::info('Pratinjau sinkronisasi data telah dibatalkan.', 'Dibatalkan');
        Router::redirect('/settings/impor-data');
    }

    /**
     * Membersihkan berkas sementara & variabel sesi
     */
    private function cleanupSession(): void
    {
        if (!empty($_SESSION['ks_sync_temp_file']) && file_exists($_SESSION['ks_sync_temp_file'])) {
            @unlink($_SESSION['ks_sync_temp_file']);
        }
        if (!empty($_SESSION['ks_sync_preview_file']) && file_exists($_SESSION['ks_sync_preview_file'])) {
            @unlink($_SESSION['ks_sync_preview_file']);
        }
        unset(
            $_SESSION['ks_sync_temp_file'],
            $_SESSION['ks_sync_preview_file'],
            $_SESSION['ks_sync_type'],
            $_SESSION['ks_sync_mode'],
            $_SESSION['ks_sync_filename']
        );
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Controllers/ActivityLogController.php
 * Pengendali Audit Trail & Log Aktivitas Staf Sistem ERP Keren Snack.
 */
class ActivityLogController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission('system.activity_log');
    }

    public function index(): void
    {
        try {
            $search = trim((string)$this->input('q', ''));
            $kategori = trim((string)$this->input('kategori', ''));
            $jenisAksi = trim((string)$this->input('jenis_aksi', ''));
            $startDate = trim((string)$this->input('start_date', ''));
            $endDate = trim((string)$this->input('end_date', ''));
            $page = max(1, (int)$this->input('page', 1));
            $perPage = 25;
            $offset = ($page - 1) * $perPage;

            $where = ["1=1"];
            $params = [];

            if (!empty($search)) {
                $where[] = "(
                    la.deskripsi_aktivitas ILIKE :search OR 
                    la.nama_aktor ILIKE :search OR 
                    la.peran_aktor ILIKE :search OR 
                    la.ip_address ILIKE :search OR
                    la.tabel_terdampak ILIKE :search
                )";
                $params['search'] = "%{$search}%";
            }

            if (!empty($kategori)) {
                $where[] = "LOWER(la.kategori_aktivitas) = LOWER(:kategori)";
                $params['kategori'] = $kategori;
            }

            if (!empty($jenisAksi)) {
                $where[] = "LOWER(la.jenis_aksi) = LOWER(:jenis_aksi)";
                $params['jenis_aksi'] = $jenisAksi;
            }

            if (!empty($startDate)) {
                $where[] = "la.waktu_kejadian >= :start_date";
                $params['start_date'] = $startDate . " 00:00:00";
            }

            if (!empty($endDate)) {
                $where[] = "la.waktu_kejadian <= :end_date";
                $params['end_date'] = $endDate . " 23:59:59";
            }

            $whereSql = implode(" AND ", $where);

            // Total logs
            $countSql = "SELECT COUNT(*) as total FROM public.log_aktivitas la WHERE {$whereSql}";
            $totalLogs = (int)(Database::fetchOne($countSql, $params)['total'] ?? 0);
            $totalPages = max(1, (int)ceil($totalLogs / $perPage));

            // Fetch page logs
            $sql = "
                SELECT la.id, la.nama_aktor, la.peran_aktor, la.sumber_aksi,
                       la.kategori_aktivitas, la.jenis_aksi, la.tabel_terdampak, la.id_referensi,
                       la.deskripsi_aktivitas, la.data_sebelum, la.data_sesudah,
                       la.ip_address, la.user_agent, la.waktu_kejadian
                FROM public.log_aktivitas la
                WHERE {$whereSql}
                ORDER BY la.waktu_kejadian DESC
                LIMIT {$perPage} OFFSET {$offset}
            ";
            $logs = Database::fetchAll($sql, $params);

            // Distinct categories & actions for filter dropdowns
            $categories = Database::fetchAll("SELECT DISTINCT kategori_aktivitas FROM public.log_aktivitas WHERE kategori_aktivitas IS NOT NULL ORDER BY kategori_aktivitas ASC");
            $actions = Database::fetchAll("SELECT DISTINCT jenis_aksi FROM public.log_aktivitas WHERE jenis_aksi IS NOT NULL ORDER BY jenis_aksi ASC");

            $this->view('settings.logs.index', [
                'pageTitle' => 'Audit Trail & Log Aktivitas Sistem',
                'pageSubtitle' => 'Histori mutasi data, perubahan harga, void faktur, dan rekam jejak pengguna',
                'logs' => $logs,
                'totalLogs' => $totalLogs,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'search' => $search,
                'kategori' => $kategori,
                'jenisAksi' => $jenisAksi,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'categories' => array_column($categories, 'kategori_aktivitas'),
                'actions' => array_column($actions, 'jenis_aksi'),
            ]);

        } catch (Throwable $e) {
            $this->flashError('Gagal memuat log aktivitas: ' . $e->getMessage());
            $this->redirect('/settings');
        }
    }
}

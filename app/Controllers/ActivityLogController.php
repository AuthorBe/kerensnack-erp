<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
use App\Helpers\CSRF;
use App\Helpers\ExcelExport;
use Database;
use Throwable;

/**
 * app/Controllers/ActivityLogController.php
 * Pengendali Audit Trail & Log Aktivitas Staf Sistem ERP Keren Snack.
 * Dilengkapi KPI Dashboard Telemetri, Filter Canggih, Ekspor Excel, dan Retensi Log Terproteksi Developer.
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
            $aktor = trim((string)$this->input('aktor', ''));
            $sumberAksi = trim((string)$this->input('sumber_aksi', ''));
            $preset = trim((string)$this->input('preset', ''));
            $startDate = trim((string)$this->input('start_date', ''));
            $endDate = trim((string)$this->input('end_date', ''));
            $page = max(1, (int)$this->input('page', 1));
            $perPage = max(10, min(100, (int)$this->input('per_page', 25)));
            $offset = ($page - 1) * $perPage;

            // Handle Quick Date Presets
            $today = date('Y-m-d');
            if ($preset === 'today') {
                $startDate = $today;
                $endDate = $today;
            } elseif ($preset === '7days') {
                $startDate = date('Y-m-d', strtotime('-6 days'));
                $endDate = $today;
            } elseif ($preset === '30days') {
                $startDate = date('Y-m-d', strtotime('-29 days'));
                $endDate = $today;
            } elseif ($preset === 'this_month') {
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
            }

            [$whereSql, $params] = $this->buildFilterQuery($search, $kategori, $jenisAksi, $aktor, $sumberAksi, $startDate, $endDate);

            // Total logs based on active filter
            $countSql = "SELECT COUNT(*) as total FROM public.log_aktivitas la WHERE {$whereSql}";
            $totalLogs = (int)(Database::fetchOne($countSql, $params)['total'] ?? 0);
            $totalPages = max(1, (int)ceil($totalLogs / $perPage));

            // Fetch page logs
            $sql = "
                SELECT la.id, la.pengguna_id, la.nama_aktor, la.peran_aktor, la.sumber_aksi,
                       la.kategori_aktivitas, la.jenis_aksi, la.tabel_terdampak, la.id_referensi,
                       la.deskripsi_aktivitas, la.data_sebelum, la.data_sesudah,
                       la.ip_address, la.user_agent, la.waktu_kejadian
                FROM public.log_aktivitas la
                WHERE {$whereSql}
                ORDER BY la.waktu_kejadian DESC
                LIMIT {$perPage} OFFSET {$offset}
            ";
            $rawLogs = Database::fetchAll($sql, $params);

            // Enrich logs with parsed user-agent
            $logs = [];
            foreach ($rawLogs as $log) {
                $log['client_info'] = ActivityLog::parseUserAgent($log['user_agent'] ?? '');
                $logs[] = $log;
            }

            // 1. Telemetri DB Storage
            $dbTelemetry = Database::fetchOne("
                SELECT 
                    COUNT(*) as total_rows,
                    pg_size_pretty(pg_total_relation_size('public.log_aktivitas')) as total_size,
                    pg_size_pretty(pg_relation_size('public.log_aktivitas')) as table_size,
                    pg_size_pretty(pg_indexes_size('public.log_aktivitas')) as indexes_size
                FROM public.log_aktivitas
            ") ?: ['total_rows' => 0, 'total_size' => '0 kB', 'table_size' => '0 kB', 'indexes_size' => '0 kB'];

            // 2. Metrik KPI Ringkasan
            $todayStart = date('Y-m-d 00:00:00');
            $logsToday = (int)(Database::fetchOne("
                SELECT COUNT(*) as total 
                FROM public.log_aktivitas 
                WHERE waktu_kejadian >= :today
            ", ['today' => $todayStart])['total'] ?? 0);

            $securityLogsToday = (int)(Database::fetchOne("
                SELECT COUNT(*) as total 
                FROM public.log_aktivitas 
                WHERE kategori_aktivitas = 'keamanan_auth' AND waktu_kejadian >= :today
            ", ['today' => $todayStart])['total'] ?? 0);

            $mutationsToday = (int)(Database::fetchOne("
                SELECT COUNT(*) as total 
                FROM public.log_aktivitas 
                WHERE kategori_aktivitas IN ('keuangan', 'penjualan', 'gudang_stok', 'master_data', 'produksi_bom') 
                  AND waktu_kejadian >= :today
            ", ['today' => $todayStart])['total'] ?? 0);

            // 3. Dropdown Options (Curated & Clean)
            $categoriesRaw = Database::fetchAll("
                SELECT DISTINCT kategori_aktivitas 
                FROM public.log_aktivitas 
                WHERE kategori_aktivitas IS NOT NULL 
                ORDER BY kategori_aktivitas ASC
            ");

            $actors = Database::fetchAll("
                SELECT DISTINCT nama_aktor, peran_aktor 
                FROM public.log_aktivitas 
                WHERE nama_aktor IS NOT NULL AND nama_aktor NOT ILIKE 'audit_%'
                ORDER BY nama_aktor ASC
            ");

            // Curated clean actions
            $standardActions = [
                'LOGIN' => 'Login Sistem',
                'LOGIN_FAILED' => 'Login Gagal',
                'LOGOUT' => 'Logout',
                'LOGOUT_TIMEOUT' => 'Sesi Timeout',
                'CREATE' => 'Tambah Data (Create)',
                'UPDATE' => 'Ubah Data (Update)',
                'DELETE' => 'Hapus Data (Delete)',
                'PRICE_CHANGE' => 'Perubahan Harga',
                'APPROVE' => 'Persetujuan (Approve)',
                'VOID' => 'Pembatalan / Void',
                'OPNAME' => 'Stok Opname',
                'WASTE' => 'Barang Rusak / Waste',
                'PRUNE_LOGS' => 'Pembersihan Log Usang',
            ];

            $this->view('settings.logs.index', [
                'pageTitle' => 'Audit Trail & Log Aktivitas Sistem',
                'pageSubtitle' => 'Histori mutasi data, perubahan harga, void faktur, dan rekam jejak pengguna',
                'logs' => $logs,
                'totalLogs' => $totalLogs,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'perPage' => $perPage,
                'search' => $search,
                'kategori' => $kategori,
                'jenisAksi' => $jenisAksi,
                'aktor' => $aktor,
                'sumberAksi' => $sumberAksi,
                'preset' => $preset,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'categories' => array_column($categoriesRaw, 'kategori_aktivitas'),
                'standardActions' => $standardActions,
                'actors' => $actors,
                'dbTelemetry' => $dbTelemetry,
                'kpi' => [
                    'today' => $logsToday,
                    'security' => $securityLogsToday,
                    'mutations' => $mutationsToday,
                    'totalAll' => (int)($dbTelemetry['total_rows'] ?? 0),
                    'totalSize' => $dbTelemetry['total_size'] ?? '0 kB',
                ],
                'isDeveloper' => Auth::isDeveloper(),
            ]);

        } catch (Throwable $e) {
            error_log("ActivityLogController index error: " . $e->getMessage());
            $this->flashError('Gagal memuat log aktivitas: ' . $e->getMessage());
            $this->redirect('/settings');
        }
    }

    /**
     * Ekspor Laporan Log Aktivitas ke Excel (.xlsx)
     * Menggunakan PhpSpreadsheet via App\Helpers\ExcelExport
     */
    public function exportExcel(): void
    {
        try {
            $search = trim((string)$this->input('q', ''));
            $kategori = trim((string)$this->input('kategori', ''));
            $jenisAksi = trim((string)$this->input('jenis_aksi', ''));
            $aktor = trim((string)$this->input('aktor', ''));
            $sumberAksi = trim((string)$this->input('sumber_aksi', ''));
            $startDate = trim((string)$this->input('start_date', ''));
            $endDate = trim((string)$this->input('end_date', ''));

            [$whereSql, $params] = $this->buildFilterQuery($search, $kategori, $jenisAksi, $aktor, $sumberAksi, $startDate, $endDate);

            // Batasi maksimal 5.000 rekaman agar memori aman
            $maxExport = 5000;
            $sql = "
                SELECT la.waktu_kejadian, la.nama_aktor, la.peran_aktor, la.sumber_aksi,
                       la.kategori_aktivitas, la.jenis_aksi, la.tabel_terdampak, la.id_referensi,
                       la.deskripsi_aktivitas, la.ip_address, la.user_agent
                FROM public.log_aktivitas la
                WHERE {$whereSql}
                ORDER BY la.waktu_kejadian DESC
                LIMIT {$maxExport}
            ";
            $data = Database::fetchAll($sql, $params);

            $headers = [
                'No',
                'Tanggal & Waktu (WIB)',
                'Nama Aktor',
                'Peran',
                'Sumber Aksi',
                'Kategori',
                'Jenis Aksi',
                'Tabel Target',
                'ID Referensi',
                'Deskripsi Aktivitas',
                'IP Address',
                'Perangkat / Browser'
            ];

            $rows = [];
            $no = 1;
            foreach ($data as $item) {
                $client = ActivityLog::parseUserAgent($item['user_agent'] ?? '');
                $rows[] = [
                    $no++,
                    date('d/m/Y H:i:s', strtotime($item['waktu_kejadian'])),
                    $item['nama_aktor'] ?: 'Sistem',
                    ucfirst(str_replace('_', ' ', (string)($item['peran_aktor'] ?: 'Staf'))),
                    strtoupper(str_replace('_', ' ', (string)($item['sumber_aksi'] ?: 'Web'))),
                    ucwords(str_replace('_', ' ', (string)($item['kategori_aktivitas'] ?: 'Umum'))),
                    strtoupper((string)($item['jenis_aksi'] ?: 'ACTION')),
                    $item['tabel_terdampak'] ?: '-',
                    $item['id_referensi'] ?: '-',
                    $item['deskripsi_aktivitas'] ?: '-',
                    $item['ip_address'] ?: '127.0.0.1',
                    $client['label'] ?? '-'
                ];
            }

            $filename = 'audit-trail-kerensnack-' . date('Ymd-His') . '.xlsx';
            ExcelExport::download($filename, $headers, $rows, 'Audit Trail');
            exit;

        } catch (Throwable $e) {
            error_log("ExportExcel Error: " . $e->getMessage());
            $this->flashError('Gagal mengunduh audit log: ' . $e->getMessage());
            $this->redirect('/settings/activity-logs');
        }
    }

    /**
     * Pembersihan / Retensi Log Usang
     * Eksklusif Developer dengan Verifikasi Kata Sandi Database
     */
    public function prune(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/settings/activity-logs');
            return;
        }

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flashError('Sesi keamanan kadaluarsa. Silakan muat ulang halaman dan coba kembali.');
            $this->redirect('/settings/activity-logs');
            return;
        }

        if (!Auth::isDeveloper()) {
            $this->flashError('Akses Ditolak: Fitur pembersihan log hanya dapat dieksekusi oleh Developer.');
            $this->redirect('/settings/activity-logs');
            return;
        }

        $days = (int)$this->input('days', 90);
        $password = (string)$this->input('developer_password', '');

        if (empty($password)) {
            $this->flashError('Kata sandi Developer wajib diisi untuk mengonfirmasi pembersihan.');
            $this->redirect('/settings/activity-logs');
            return;
        }

        $result = ActivityLog::pruneLogs($days, $password);

        if ($result['success']) {
            $this->flashSuccess($result['message']);
        } else {
            $this->flashError($result['message']);
        }

        $this->redirect('/settings/activity-logs');
    }

    /**
     * Helper privat untuk merangkai kondisi WHERE dan parameter filter
     */
    private function buildFilterQuery(
        string $search,
        string $kategori,
        string $jenisAksi,
        string $aktor,
        string $sumberAksi,
        string $startDate,
        string $endDate
    ): array {
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

        if (!empty($aktor)) {
            $where[] = "la.nama_aktor = :aktor";
            $params['aktor'] = $aktor;
        }

        if (!empty($sumberAksi)) {
            $where[] = "LOWER(la.sumber_aksi) = LOWER(:sumber_aksi)";
            $params['sumber_aksi'] = $sumberAksi;
        }

        if (!empty($startDate)) {
            $where[] = "la.waktu_kejadian >= :start_date";
            $params['start_date'] = $startDate . " 00:00:00";
        }

        if (!empty($endDate)) {
            $where[] = "la.waktu_kejadian <= :end_date";
            $params['end_date'] = $endDate . " 23:59:59";
        }

        return [implode(" AND ", $where), $params];
    }
}


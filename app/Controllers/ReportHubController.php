<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\Format;
use App\Helpers\ExcelExport;
use App\Helpers\PdfExport;
use App\Helpers\CompanySetting;
use Database;
use Throwable;
use PDO;

/**
 * app/Controllers/ReportHubController.php
 * Pengendali Pusat Unduh Laporan & Ekspor Terpusat (Report Download Hub).
 * Seluruh ekspor data agregat dan laporan rekapitulasi dipusatkan di sini dengan proteksi izin 'reports.download_hub'.
 */
class ReportHubController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission('reports.download_hub');
    }

    /**
     * Dashboard Utama Pusat Unduh Laporan
     */
    public function index(): void
    {
        try {
            // Master referensi untuk filter dinamis per kartu
            $stores = Database::fetchAll("SELECT id, nama_toko, kode_pelanggan FROM public.pelanggan WHERE status_aktif = TRUE ORDER BY nama_toko ASC");
            $accounts = Database::fetchAll("SELECT id, nama_akun, tipe_akun, COALESCE(is_escrow, FALSE) as is_escrow FROM public.akun_kas WHERE status_aktif = TRUE ORDER BY COALESCE(is_escrow, FALSE) ASC, nama_akun ASC");
            $suppliers = Database::fetchAll("SELECT id, nama_pemasok, kode_pemasok FROM public.pemasok WHERE status_aktif = TRUE ORDER BY nama_pemasok ASC");
            $salesUsers = Database::fetchAll("
                SELECT p.id, p.nama_lengkap, p.nama_panggilan
                FROM public.pengguna p
                JOIN public.peran pr ON p.peran_id = pr.id
                WHERE p.status_aktif = TRUE AND pr.nama_peran IN ('sales', 'owner', 'admin')
                ORDER BY p.nama_lengkap ASC
            ");
            $driverUsers = Database::fetchAll("
                SELECT p.id, p.nama_lengkap, p.nama_panggilan
                FROM public.pengguna p
                JOIN public.peran pr ON p.peran_id = pr.id
                WHERE p.status_aktif = TRUE AND pr.nama_peran IN ('driver', 'owner', 'admin')
                ORDER BY p.nama_lengkap ASC
            ");
            $expenseCategories = Database::fetchAll("SELECT id, nama_kategori FROM public.kategori_biaya WHERE status_aktif = TRUE ORDER BY nama_kategori ASC");
            $employees = Database::fetchAll("SELECT id, nama_karyawan, tipe_penggajian FROM public.v_karyawan_info WHERE status_aktif = TRUE ORDER BY nama_karyawan ASC");
            $payrollRuns = Database::fetchAll("SELECT id, nomor_referensi, nama_payroll, periode_awal, periode_akhir, status FROM public.penggajian WHERE status IN ('disetujui', 'dibayarkan') ORDER BY periode_akhir DESC");

            $this->view('reports.index', [
                'pageTitle' => 'Pusat Unduh Laporan',
                'pageSubtitle' => 'Portal Terpadu Unduh Laporan & Rekapitulasi Bisnis Format Excel & PDF',
                'stores' => $stores,
                'accounts' => $accounts,
                'suppliers' => $suppliers,
                'salesUsers' => $salesUsers,
                'driverUsers' => $driverUsers,
                'expenseCategories' => $expenseCategories,
                'employees' => $employees,
                'payrollRuns' => $payrollRuns,
                'today' => date('Y-m-d'),
                'thisMonthStart' => date('Y-m-01'),
                'thisMonthEnd' => date('Y-m-t'),
                'lastMonthStart' => date('Y-m-01', strtotime('first day of last month')),
                'lastMonthEnd' => date('Y-m-t', strtotime('last day of last month')),
                'thisYearStart' => date('Y-01-01'),
                'thisYearEnd' => date('Y-12-31')
            ]);
        } catch (Throwable $e) {
            error_log("ReportHubController index error: " . $e->getMessage());
            $this->flashError("Gagal memuat portal laporan: " . $e->getMessage());
            $this->redirect('/dashboard');
        }
    }

    /**
     * Helper untuk menangani exception ekspor agar memberikan respon JSON jika diakses via AJAX/Fetch
     */
    private function handleExportError(string $title, Throwable $e): void
    {
        error_log("{$title} error: " . $e->getMessage());
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
               || isset($_GET['ajax']);

        if ($isAjax) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => "{$title}: " . $e->getMessage()
            ]);
            exit;
        }

        $this->flashError("{$title}: " . $e->getMessage());
        $this->redirect('/reports');
    }

    /**
     * Helper sanitasi rentang tanggal yang paten & defensif
     * @return array{0: string, 1: string} [startDate, endDate]
     */
    private function sanitizeDateRange(?string $start, ?string $end): array
    {
        $startDate = !empty($start) && strtotime($start) ? date('Y-m-d', strtotime($start)) : date('Y-m-01');
        $endDate = !empty($end) && strtotime($end) ? date('Y-m-d', strtotime($end)) : date('Y-m-d');
        
        if ($startDate > $endDate) {
            $tmp = $startDate;
            $startDate = $endDate;
            $endDate = $tmp;
        }

        return [$startDate, $endDate];
    }

    // =========================================================================
    // 0. MASTER REKAP PENJUALAN MULTI-KANAL (POS, B2B, KONSINYASI)
    // =========================================================================

    public function exportConsolidatedSalesExcel(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));

            $summaryData = $this->fetchConsolidatedMetrics($startDate, $endDate);
            $posRows = $this->fetchPosDetailRows($startDate, $endDate);
            $b2bRows = $this->fetchB2bDetailRows($startDate, $endDate);
            $consRows = $this->fetchConsignmentDetailRows($startDate, $endDate);

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Rekapitulasi Penjualan Multi Kanal ({$dateRange}).xlsx";

            ExcelExport::downloadConsolidatedSalesReport($filename, $summaryData, $posRows, $b2bRows, $consRows, [
                'periode_label' => $dateRange
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh rekapitulasi penjualan multi-kanal Excel", $e);
        }
    }

    public function exportConsolidatedSalesPdf(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));

            $summaryData = $this->fetchConsolidatedMetrics($startDate, $endDate);
            $company = CompanySetting::getAll();

            ob_start();
            extract([
                'startDate' => $startDate,
                'endDate' => $endDate,
                'summaryData' => $summaryData,
                'company' => $company
            ]);
            require ROOT_PATH . '/views/reports/sales_consolidated_pdf.php';
            $html = ob_get_clean();

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Rekapitulasi Penjualan Multi Kanal ({$dateRange}).pdf";
            PdfExport::download($html, $filename, 'A4', 'landscape');
        } catch (Throwable $e) {
            $this->handleExportError("Gagal membuat PDF rekapitulasi penjualan multi-kanal", $e);
        }
    }

    // =========================================================================
    // 1. EKSEKUTIF & LABA RUGI (P&L SUMMARY)
    // =========================================================================

    public function exportExecutivePnlExcel(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));

            $metrics = $this->fetchPnlData($startDate, $endDate);

            $headers = ['Kategori Akun / Metrik', 'Keterangan Analitik', 'Nominal (Rp)'];
            $rows = [
                ['PENDAPATAN USAHA (REVENUE)', '', ''],
                ['1. Penjualan Kasir POS', 'Penjualan ritel langsung kasir', $metrics['posRevenue']],
                ['2. Penjualan Pesanan Pelanggan (B2B)', 'Faktur penjualan grosir reguler', $metrics['b2bRevenue']],
                ['3. Penjualan Konsinyasi (Titip Jual)', 'Total barang laku di rak toko mitra', $metrics['consRevenue']],
                ['TOTAL PENDAPATAN (OMZET KOTOR)', 'Total seluruh kanal penjualan', $metrics['totalRevenue']],
                ['', '', ''],
                ['HARGA POKOK PENJUALAN (HPP / COGS)', '', ''],
                ['1. HPP Penjualan POS', 'Beban pokok produk kasir POS', $metrics['posHpp']],
                ['2. HPP Penjualan B2B', 'Beban pokok pesanan B2B', $metrics['b2bHpp']],
                ['3. HPP Penjualan Konsinyasi', 'Beban pokok barang laku konsinyasi', $metrics['consHpp']],
                ['TOTAL BEBAN POKOK (HPP)', 'Total modal produk terjual', $metrics['totalCogs']],
                ['', '', ''],
                ['LABA KOTOR (GROSS PROFIT)', 'Total Omzet dikurangi Total HPP', $metrics['grossProfit']],
                ['', '', ''],
                ['BEBAN OPERASIONAL (EXPENSES)', '', ''],
            ];

            if (!empty($metrics['expenseRows'])) {
                foreach ($metrics['expenseRows'] as $idx => $er) {
                    $rows[] = [($idx + 1) . '. Beban: ' . ucwords(str_replace('_', ' ', (string)$er['kategori'])), 'Pengeluaran kas operasional', (float)$er['total_beban']];
                }
            } else {
                $rows[] = ['Beban Operasional', 'Tidak ada pengeluaran kas operasional pada periode ini', 0];
            }

            $rows[] = ['TOTAL BEBAN OPERASIONAL', 'Total pengeluaran kas operasional periode ini', $metrics['totalOperationalExpense']];
            $rows[] = ['', '', ''];
            $rows[] = ['ESTIMASI LABA BERSIH (NET PROFIT)', 'Laba Kotor dikurangi Total Beban Operasional', $metrics['netProfit']];
            if ($metrics['consLoss'] > 0) {
                $rows[] = ['', '', ''];
                $rows[] = ['CATATAN KHUSUS / ANALITIK', '', ''];
                $rows[] = ['* Estimasi Kerugian Retur Rusak Konsinyasi', 'Kerugian produk rusak/kedaluwarsa di rak mitra', $metrics['consLoss']];
            }

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Laporan Laba Rugi Eksekutif ({$dateRange}).xlsx";
            
            ExcelExport::download($filename, $headers, $rows, 'Ringkasan PnL', [
                'report_title' => 'LAPORAN KINERJA KEUANGAN & LABA RUGI (P&L)',
                'metadata' => ['Periode Analisis' => $dateRange, 'Status' => 'Dokumen Resmi Manajemen'],
                'currency_cols' => ['Nominal (Rp)']
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh laporan laba rugi", $e);
        }
    }

    public function exportExecutivePnlPdf(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));

            $metrics = $this->fetchPnlData($startDate, $endDate);
            $company = CompanySetting::getAll();

            ob_start();
            extract(array_merge($metrics, [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'company' => $company
            ]));
            require ROOT_PATH . '/views/reports/pnl_pdf.php';
            $html = ob_get_clean();

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Laporan Laba Rugi Eksekutif ({$dateRange}).pdf";
            PdfExport::download($html, $filename, 'A4', 'portrait');
        } catch (Throwable $e) {
            $this->handleExportError("Gagal membuat PDF laba rugi", $e);
        }
    }

    // =========================================================================
    // 2. KEUANGAN & KAS
    // =========================================================================

    public function exportCashFlowExcel(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));
            $accountId = (string)$this->input('account_id', 'all');

            $cfData = $this->fetchCashFlowData($startDate, $endDate, $accountId);

            $headers = ['Kategori / Deskripsi Arus Kas', 'Jumlah Transaksi', 'Nominal (Rp)'];
            $rows = [
                ['SALDO AWAL KAS & BANK', '-', $cfData['begBalance']],
                ['', '', ''],
                ['PENERIMAAN KAS MASUK (INFLOW)', '', '']
            ];

            foreach ($cfData['inflowBreakdown'] as $ib) {
                $rows[] = ['Pemasukan: ' . ucwords(str_replace('_', ' ', (string)$ib['kategori'])), (int)$ib['jml'], (float)$ib['total']];
            }
            $rows[] = ['TOTAL PENERIMAAN KAS', '-', $cfData['totalIn']];
            $rows[] = ['', '', ''];
            $rows[] = ['PENGELUARAN KAS KELUAR (OUTFLOW)', '', ''];

            foreach ($cfData['outflowBreakdown'] as $ob) {
                $rows[] = ['Beban: ' . ucwords(str_replace('_', ' ', (string)$ob['kategori'])), (int)$ob['jml'], (float)$ob['total']];
            }
            $rows[] = ['TOTAL PENGELUARAN KAS', '-', $cfData['totalOut']];
            $rows[] = ['', '', ''];
            $rows[] = ['ARUS KAS BERSIH (NET CASH FLOW)', '-', $cfData['netCashFlow']];
            if ($accountId !== 'all') {
                $rows[] = ['MUTASI TRANSFER DANA BERSIH', '-', $cfData['netTransfer']];
            }
            $rows[] = ['SALDO AKHIR KAS & BANK', '-', $cfData['endingBalance']];

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Laporan Arus Kas ({$dateRange}).xlsx";
            
            ExcelExport::download($filename, $headers, $rows, 'Arus Kas', [
                'report_title' => 'LAPORAN ARUS KAS (CASH FLOW STATEMENT)',
                'metadata' => ['Periode' => $dateRange, 'Akun Kas' => $cfData['accountName']],
                'currency_cols' => ['Nominal (Rp)']
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh arus kas", $e);
        }
    }

    public function exportCashFlowPdf(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));
            $accountId = (string)$this->input('account_id', 'all');

            $cfData = $this->fetchCashFlowData($startDate, $endDate, $accountId);
            $company = CompanySetting::getAll();

            ob_start();
            extract(array_merge($cfData, [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'accountId' => $accountId,
                'company' => $company
            ]));
            require ROOT_PATH . '/views/reports/cash_flow_pdf.php';
            $html = ob_get_clean();

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Laporan Arus Kas ({$dateRange}).pdf";
            PdfExport::download($html, $filename, 'A4', 'portrait');
        } catch (Throwable $e) {
            $this->handleExportError("Gagal membuat PDF arus kas", $e);
        }
    }

    public function exportCashTransactionsExcel(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));
            $accountId = (string)$this->input('account_id', 'all');
            $type = (string)$this->input('type', 'all');

            $params = ['start' => $startDate, 'end' => $endDate];
            $whereSql = "WHERE ark.tanggal_transaksi BETWEEN :start AND :end";
            $accountLabel = 'Semua Kas Operasional (Bebas Escrow)';

            if ($accountId === 'all' || $accountId === 'all_with_escrow') {
                $accountLabel = 'Konsolidasi Seluruh Akun (Termasuk Escrow)';
            } elseif ($accountId === 'escrow') {
                $whereSql .= " AND ak.is_escrow = TRUE";
                $accountLabel = 'Khusus Kas Tabungan Karyawan (Escrow Terkunci)';
            } elseif ($accountId !== 'operational' && !empty($accountId)) {
                $whereSql .= " AND ark.akun_kas_id = :acc";
                $params['acc'] = $accountId;
                $accRow = Database::fetchOne("SELECT nama_akun, COALESCE(is_escrow, FALSE) as is_escrow FROM public.akun_kas WHERE id = :id", ['id' => $accountId]);
                $accountLabel = $accRow ? (string)$accRow['nama_akun'] . (!empty($accRow['is_escrow']) ? ' [Escrow]' : '') : "Akun: {$accountId}";
            } else {
                // Default: kas operasional usaha (bebas escrow)
                $whereSql .= " AND COALESCE(ak.is_escrow, FALSE) = FALSE";
                $accountLabel = 'Semua Kas Operasional (Bebas Escrow)';
            }

            if ($type !== 'all' && !empty($type)) {
                $whereSql .= " AND ark.jenis_kas = :type";
                $params['type'] = $type;
            }

            $txs = Database::fetchAll("
                SELECT ark.*, ak.nama_akun, u.nama_lengkap as nama_user
                FROM public.arus_kas ark
                JOIN public.akun_kas ak ON ark.akun_kas_id = ak.id
                LEFT JOIN public.pengguna u ON ark.dicatat_oleh = u.id
                {$whereSql}
                ORDER BY ark.tanggal_transaksi ASC, ark.dibuat_pada ASC
            ", $params);

            $headers = ['No', 'No. Bukti Kas', 'Tanggal', 'Akun Kas/Bank', 'Jenis Mutasi', 'Kategori', 'Keterangan', 'Nominal (Rp)', 'Dicatat Oleh'];
            $rows = [];
            foreach ($txs as $idx => $t) {
                $rows[] = [
                    $idx + 1,
                    $t['nomor_transaksi'],
                    $t['tanggal_transaksi'],
                    $t['nama_akun'],
                    strtoupper((string)$t['jenis_kas']),
                    ucwords(str_replace('_', ' ', (string)($t['kategori'] ?? '-'))),
                    $t['keterangan'] ?? '-',
                    (float)$t['nominal'],
                    $t['nama_user'] ?? 'Sistem'
                ];
            }

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Rekap Mutasi Kas ({$dateRange}).xlsx";
            
            ExcelExport::download($filename, $headers, $rows, 'Mutasi Kas', [
                'report_title' => 'REKAPITULASI MUTASI TRANSAKSI KAS & BANK',
                'metadata' => ['Periode' => $dateRange, 'Filter Akun' => $accountLabel, 'Jenis Mutasi' => strtoupper($type)],
                'currency_cols' => ['Nominal (Rp)'],
                'sum_cols' => ['Nominal (Rp)']
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh mutasi kas", $e);
        }
    }

    // =========================================================================
    // 3. PENJUALAN & PESANAN PELANGGAN B2B
    // =========================================================================

    public function exportCustomerOrdersExcel(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));
            $statusBayar = (string)$this->input('status_bayar', 'all');
            $statusKirim = (string)$this->input('status_kirim', 'all');

            $params = ['start' => $startDate, 'end' => $endDate];
            $whereSql = "WHERE p.tanggal_pesanan BETWEEN :start AND :end AND p.status_pemrosesan != 'dibatalkan'";

            if ($statusBayar !== 'all' && !empty($statusBayar)) {
                $whereSql .= " AND p.status_pembayaran = :sb";
                $params['sb'] = $statusBayar;
            }
            if ($statusKirim !== 'all' && !empty($statusKirim)) {
                $whereSql .= " AND p.status_pemrosesan = :sk";
                $params['sk'] = $statusKirim;
            }

            $orders = Database::fetchAll("
                SELECT p.*, pel.nama_toko, pel.nama_pemilik, u.nama_lengkap as nama_sales
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.pengguna u ON p.sales_driver_id = u.id
                {$whereSql}
                ORDER BY p.tanggal_pesanan DESC, p.dibuat_pada DESC
            ", $params);

            $headers = ['No', 'Nomor Faktur', 'Tanggal', 'Toko Pelanggan', 'Pemilik', 'Sales PIC', 'Tipe Bayar', 'Subtotal Bruto (Rp)', 'Diskon (Rp)', 'Total Netto (Rp)', 'Total Bayar (Rp)', 'Sisa Piutang (Rp)', 'Status Bayar', 'Status Pemrosesan'];
            $rows = [];
            foreach ($orders as $idx => $o) {
                $rows[] = [
                    $idx + 1,
                    $o['nomor_nota'],
                    $o['tanggal_pesanan'],
                    $o['nama_toko'],
                    $o['nama_pemilik'] ?? '-',
                    $o['nama_sales'] ?? '-',
                    strtoupper((string)$o['tipe_pembayaran']),
                    (float)$o['total_bruto'],
                    (float)$o['total_diskon'],
                    (float)$o['total_netto'],
                    (float)$o['total_dibayar'],
                    (float)$o['sisa_tagihan'],
                    strtoupper((string)$o['status_pembayaran']),
                    strtoupper((string)$o['status_pemrosesan'])
                ];
            }

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Rekap Pesanan Pelanggan B2B ({$dateRange}).xlsx";
            
            ExcelExport::download($filename, $headers, $rows, 'Pesanan B2B', [
                'report_title' => 'REKAPITULASI FAKTUR & PESANAN PELANGGAN B2B',
                'metadata' => ['Periode' => $dateRange, 'Status' => "Bayar: {$statusBayar}, Kirim: {$statusKirim}"],
                'currency_cols' => ['Subtotal Bruto (Rp)', 'Diskon (Rp)', 'Total Netto (Rp)', 'Total Bayar (Rp)', 'Sisa Piutang (Rp)'],
                'sum_cols' => ['Subtotal Bruto (Rp)', 'Diskon (Rp)', 'Total Netto (Rp)', 'Total Bayar (Rp)', 'Sisa Piutang (Rp)']
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh pesanan", $e);
        }
    }

    // =========================================================================
    // 4. KONSINYASI (TITIP JUAL RAK)
    // =========================================================================

    public function exportConsignmentSalesExcel(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));
            $storeId = (string)$this->input('store_id', 'all');

            $params = ['start' => $startDate, 'end' => $endDate];
            $storeSql = "";
            if ($storeId !== 'all' && !empty($storeId)) {
                $storeSql = " AND kk.pelanggan_id = :store";
                $params['store'] = $storeId;
            }

            $items = Database::fetchAll("
                SELECT rk.*, kk.tanggal_kunjungan, kk.nomor_kunjungan, pel.nama_toko, it.nama_item, it.kode_sku,
                       COALESCE(k.nama_karyawan, u.nama_lengkap, '-') as nama_sales
                FROM public.rincian_kunjungan_konsinyasi rk
                JOIN public.kunjungan_konsinyasi kk ON rk.kunjungan_id = kk.id
                JOIN public.pelanggan pel ON kk.pelanggan_id = pel.id
                JOIN public.item it ON rk.item_id = it.id
                LEFT JOIN public.v_karyawan_info k ON kk.sales_driver_id = k.id
                LEFT JOIN public.pengguna u ON kk.dibuat_oleh = u.id
                WHERE kk.tanggal_kunjungan BETWEEN :start AND :end {$storeSql}
                ORDER BY kk.tanggal_kunjungan DESC, pel.nama_toko ASC
            ", $params);

            $headers = ['No', 'No. Kunjungan', 'Tanggal', 'Toko Mitra', 'Kode SKU', 'Nama Produk', 'Stok Awal', 'Tambah Baru', 'Sisa di Rak', 'Terjual (Pcs)', 'Harga Satuan (Rp)', 'Total Penjualan (Rp)', 'Retur Rusak (Pcs)', 'Retur Bagus (Pcs)', 'Sales Pembina'];
            $rows = [];
            foreach ($items as $idx => $i) {
                $rows[] = [
                    $idx + 1,
                    $i['nomor_kunjungan'],
                    $i['tanggal_kunjungan'],
                    $i['nama_toko'],
                    $i['kode_sku'],
                    $i['nama_item'],
                    (int)$i['stok_titip_awal'],
                    (int)$i['tambah_titip_baru'],
                    (int)$i['sisa_fisik_di_rak'],
                    (int)$i['jumlah_laku_terjual'],
                    (float)$i['harga_satuan_deal'],
                    (float)$i['subtotal_laku'],
                    (int)$i['retur_rusak'],
                    (int)$i['retur_bagus'],
                    $i['nama_sales'] ?? '-'
                ];
            }

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Laporan Penjualan Konsinyasi ({$dateRange}).xlsx";
            
            ExcelExport::download($filename, $headers, $rows, 'Penjualan Konsinyasi', [
                'report_title' => 'LAPORAN PENJUALAN KONSINYASI (TITIP JUAL RAK)',
                'metadata' => ['Periode' => $dateRange, 'Filter Toko' => $storeId],
                'currency_cols' => ['Harga Satuan (Rp)', 'Total Penjualan (Rp)'],
                'sum_cols' => ['Terjual (Pcs)', 'Total Penjualan (Rp)', 'Retur Rusak (Pcs)', 'Retur Bagus (Pcs)']
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh penjualan konsinyasi", $e);
        }
    }

    public function exportConsignmentLossExcel(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));

            $items = Database::fetchAll("
                SELECT rk.*, kk.tanggal_kunjungan, kk.nomor_kunjungan, pel.nama_toko, it.nama_item, it.kode_sku
                FROM public.rincian_kunjungan_konsinyasi rk
                JOIN public.kunjungan_konsinyasi kk ON rk.kunjungan_id = kk.id
                JOIN public.pelanggan pel ON kk.pelanggan_id = pel.id
                JOIN public.item it ON rk.item_id = it.id
                WHERE kk.tanggal_kunjungan BETWEEN :start AND :end AND rk.retur_rusak > 0
                ORDER BY kk.tanggal_kunjungan DESC, rk.nilai_kerugian_rusak DESC
            ", ['start' => $startDate, 'end' => $endDate]);

            $headers = ['No', 'No. Kunjungan', 'Tanggal', 'Toko Mitra', 'Kode SKU', 'Nama Produk', 'Jumlah Rusak/Basi (Pcs)', 'Nilai Kerugian (Rp)', 'Keterangan'];
            $rows = [];
            foreach ($items as $idx => $i) {
                $rows[] = [
                    $idx + 1,
                    $i['nomor_kunjungan'],
                    $i['tanggal_kunjungan'],
                    $i['nama_toko'],
                    $i['kode_sku'],
                    $i['nama_item'],
                    (int)$i['retur_rusak'],
                    (float)$i['nilai_kerugian_rusak'],
                    'Barang retur rusak/kedaluwarsa di rak'
                ];
            }

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Laporan Kerugian Rusak Konsinyasi ({$dateRange}).xlsx";
            
            ExcelExport::download($filename, $headers, $rows, 'Kerugian Konsinyasi', [
                'report_title' => 'LAPORAN KERUGIAN RETUR BARANG RUSAK & KEDALUWARSA KONSINYASI',
                'metadata' => ['Periode' => $dateRange],
                'currency_cols' => ['Nilai Kerugian (Rp)'],
                'sum_cols' => ['Jumlah Rusak/Basi (Pcs)', 'Nilai Kerugian (Rp)']
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh laporan kerugian", $e);
        }
    }

    public function exportConsignmentInvoicesExcel(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));
            $status = (string)$this->input('status', 'all');
            $invoices = $this->fetchConsignmentInvoicesData($status, $startDate, $endDate);

            $headers = ['No', 'Nomor Faktur / Tagihan', 'Toko Mitra', 'Pemilik', 'No. WhatsApp', 'Sales Pembina', 'Tanggal Faktur', 'Jatuh Tempo', 'Total Tagihan (Rp)', 'Sudah Dibayar (Rp)', 'Sisa Piutang (Rp)', 'Status Pembayaran'];
            $rows = [];
            foreach ($invoices as $idx => $inv) {
                $rows[] = [
                    $idx + 1,
                    $inv['nomor_nota'],
                    $inv['nama_toko'],
                    $inv['nama_pemilik'] ?? '-',
                    $inv['nomor_whatsapp'] ?? '-',
                    $inv['nama_sales'] ?? '-',
                    $inv['tanggal_pesanan'],
                    $inv['tanggal_jatuh_tempo'] ?? '-',
                    (float)$inv['total_netto'],
                    (float)$inv['total_dibayar'],
                    (float)$inv['sisa_tagihan'],
                    strtoupper((string)$inv['status_pembayaran'])
                ];
            }

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Rekap Tagihan Piutang Konsinyasi ({$dateRange}).xlsx";
            
            ExcelExport::download($filename, $headers, $rows, 'Piutang Konsinyasi', [
                'report_title' => 'REKAPITULASI TAGIHAN & AGING PIUTANG KONSINYASI',
                'metadata' => ['Status Filter' => $status, 'Periode' => $dateRange],
                'currency_cols' => ['Total Tagihan (Rp)', 'Sudah Dibayar (Rp)', 'Sisa Piutang (Rp)'],
                'sum_cols' => ['Total Tagihan (Rp)', 'Sudah Dibayar (Rp)', 'Sisa Piutang (Rp)']
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh tagihan", $e);
        }
    }

    public function exportConsignmentInvoicesPdf(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));
            $status = (string)$this->input('status', 'all');
            $invoices = $this->fetchConsignmentInvoicesData($status, $startDate, $endDate);
            $company = CompanySetting::getAll();

            $totalPiutang = 0;
            foreach ($invoices as $inv) {
                $totalPiutang += (float)$inv['sisa_tagihan'];
            }

            ob_start();
            extract([
                'status' => $status,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'invoices' => $invoices,
                'totalPiutang' => $totalPiutang,
                'company' => $company
            ]);
            require ROOT_PATH . '/views/reports/consignment_invoices_pdf.php';
            $html = ob_get_clean();

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Rekap Tagihan Piutang Konsinyasi ({$dateRange}).pdf";
            PdfExport::download($html, $filename, 'A4', 'portrait');
        } catch (Throwable $e) {
            $this->handleExportError("Gagal membuat PDF tagihan piutang", $e);
        }
    }

    public function exportSalesCommissionsExcel(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));

            $salesList = Database::fetchAll("
                SELECT p.id, p.nama_lengkap, p.nama_panggilan,
                       COALESCE((
                            SELECT SUM(rk.subtotal_laku)
                            FROM public.kunjungan_konsinyasi kk
                            JOIN public.rincian_kunjungan_konsinyasi rk ON kk.id = rk.kunjungan_id
                            WHERE (kk.sales_driver_id = p.id OR kk.dibuat_oleh = p.id) 
                              AND kk.tanggal_kunjungan BETWEEN :start AND :end
                       ), 0) as total_omzet_konsinyasi,
                       COALESCE((
                            SELECT SUM(pes.total_netto)
                            FROM public.pesanan pes
                            WHERE pes.sales_driver_id = p.id 
                              AND pes.tanggal_pesanan BETWEEN :start AND :end
                              AND pes.status_pemrosesan != 'dibatalkan'
                              AND pes.status_pembayaran != 'dibatalkan'
                       ), 0) as total_omzet_b2b
                FROM public.pengguna p
                JOIN public.peran pr ON p.peran_id = pr.id
                WHERE p.status_aktif = TRUE AND pr.nama_peran IN ('sales', 'owner', 'admin')
                ORDER BY p.nama_lengkap ASC
            ", ['start' => $startDate, 'end' => $endDate]);

            $headers = ['No', 'Nama Salesman', 'Panggilan', 'Omzet Konsinyasi (Rp)', 'Omzet B2B (Rp)', 'Total Omzet (Rp)', 'Tier Komisi', 'Rate Komisi (%)', 'Estimasi Komisi (Rp)'];
            $rows = [];
            foreach ($salesList as $idx => $s) {
                $omzetKons = (float)$s['total_omzet_konsinyasi'];
                $omzetB2B = (float)$s['total_omzet_b2b'];
                $totalOmzet = $omzetKons + $omzetB2B;

                $tierRpc = Database::fetchOne("
                    SELECT public.fn_hitung_tier_komisi_sales(:omzet) as r
                ", ['omzet' => $totalOmzet])['r'] ?? '{}';
                $tierInfo = json_decode((string)$tierRpc, true) ?? [];

                $tierName = $tierInfo['nama_tier'] ?? 'Tier 1';
                $tierPct = (float)($tierInfo['persentase'] ?? 0);
                $komisiEst = (float)($tierInfo['nominal_komisi'] ?? ($totalOmzet * ($tierPct / 100)));

                $rows[] = [
                    $idx + 1,
                    $s['nama_lengkap'],
                    $s['nama_panggilan'] ?? '-',
                    $omzetKons,
                    $omzetB2B,
                    $totalOmzet,
                    $tierName,
                    $tierPct,
                    $komisiEst
                ];
            }

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Rekap Komisi Salesman ({$dateRange}).xlsx";
            
            ExcelExport::download($filename, $headers, $rows, 'Komisi Sales', [
                'report_title' => 'REKAPITULASI PERFORMA & ESTIMASI KOMISI SALESMAN',
                'metadata' => ['Periode' => $dateRange, 'Metode Perhitungan' => 'Tier Progresif Resmi Sesuai Sistem'],
                'currency_cols' => ['Omzet Konsinyasi (Rp)', 'Omzet B2B (Rp)', 'Total Omzet (Rp)', 'Estimasi Komisi (Rp)'],
                'sum_cols' => ['Omzet Konsinyasi (Rp)', 'Omzet B2B (Rp)', 'Total Omzet (Rp)', 'Estimasi Komisi (Rp)']
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh komisi", $e);
        }
    }

    public function exportSalesVisitsExcel(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));
            $salesId = (string)$this->input('sales_id', 'all');

            $params = ['start' => $startDate, 'end' => $endDate];
            $salesSql = "";
            if ($salesId !== 'all' && !empty($salesId)) {
                $salesSql = " AND (kk.sales_driver_id = :sid OR kk.dibuat_oleh = :sid)";
                $params['sid'] = $salesId;
            }

            $visits = Database::fetchAll("
                SELECT kk.*, pel.nama_toko, pel.alamat_lengkap as alamat, 
                       COALESCE(k.nama_karyawan, u.nama_lengkap, '-') as nama_sales,
                       COALESCE((SELECT SUM(rk.subtotal_laku) FROM public.rincian_kunjungan_konsinyasi rk WHERE rk.kunjungan_id = kk.id), 0) as omzet_kunjungan
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan pel ON kk.pelanggan_id = pel.id
                LEFT JOIN public.v_karyawan_info k ON kk.sales_driver_id = k.id
                LEFT JOIN public.pengguna u ON kk.dibuat_oleh = u.id
                WHERE kk.tanggal_kunjungan BETWEEN :start AND :end {$salesSql}
                ORDER BY kk.tanggal_kunjungan DESC, kk.dibuat_pada DESC
            ", $params);

            $headers = ['No', 'No. Kunjungan', 'Tanggal Kunjungan', 'Toko Mitra', 'Sales PIC', 'Total Omzet Laku (Rp)', 'Catatan'];
            $rows = [];
            foreach ($visits as $idx => $v) {
                $rows[] = [
                    $idx + 1,
                    $v['nomor_kunjungan'],
                    $v['tanggal_kunjungan'],
                    $v['nama_toko'],
                    $v['nama_sales'] ?? '-',
                    (float)$v['omzet_kunjungan'],
                    $v['catatan'] ?? '-'
                ];
            }

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Rekap Riwayat Kunjungan Sales ({$dateRange}).xlsx";
            
            ExcelExport::download($filename, $headers, $rows, 'Riwayat Kunjungan', [
                'report_title' => 'LOG RIWAYAT KUNJUNGAN SALES LAPANGAN',
                'metadata' => ['Periode' => $dateRange, 'Sales Filter' => $salesId],
                'currency_cols' => ['Total Omzet Laku (Rp)'],
                'sum_cols' => ['Total Omzet Laku (Rp)']
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh kunjungan", $e);
        }
    }

    // =========================================================================
    // 5. GUDANG & PERSEDIAAN
    // =========================================================================

    public function exportInventoryStockExcel(): void
    {
        try {
            $kategori = (string)$this->input('kategori', 'all');
            $stockData = $this->fetchInventoryStockData($kategori);

            $headers = ['No', 'Kode SKU', 'Nama Item / Produk', 'Tipe Item', 'Grup Produk', 'Satuan', 'Stok Fisik', 'Batas Min Stok', 'HPP Satuan (Rp)', 'Total Nilai Valuasi (Rp)', 'Status Stok'];
            $rows = [];
            foreach ($stockData['items'] as $idx => $it) {
                $stok = (float)$it['stok_fisik_saat_ini'];
                $min = (float)$it['stok_minimum_peringatan'];
                $hpp = (float)$it['harga_pokok_pembelian'];
                $valuasi = $stok * $hpp;
                $status = ($stok <= 0) ? 'HABIS' : (($stok <= $min) ? 'MENIPIS' : 'AMAN');

                $rows[] = [
                    $idx + 1,
                    $it['kode_sku'],
                    $it['nama_item'],
                    strtoupper((string)$it['tipe_item']),
                    $it['nama_grup_produk'] ?? '-',
                    $it['satuan_dasar'] ?? 'pcs',
                    $stok,
                    $min,
                    $hpp,
                    $valuasi,
                    $status
                ];
            }

            $dateFormatted = date('d M Y');
            $filename = "Katalog dan Valuasi Stok Gudang ({$dateFormatted}).xlsx";
            
            ExcelExport::download($filename, $headers, $rows, 'Valuasi Stok', [
                'report_title' => 'KATALOG & VALUASI STOK PERSEDIAAN GUDANG PUSAT',
                'metadata' => ['Kategori Filter' => $kategori, 'Per Tanggal' => $dateFormatted],
                'currency_cols' => ['HPP Satuan (Rp)', 'Total Nilai Valuasi (Rp)'],
                'sum_cols' => ['Stok Fisik', 'Total Nilai Valuasi (Rp)']
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh stok", $e);
        }
    }

    public function exportInventoryStockPdf(): void
    {
        try {
            $kategori = (string)$this->input('kategori', 'all');
            $stockData = $this->fetchInventoryStockData($kategori);
            $company = CompanySetting::getAll();

            ob_start();
            extract([
                'kategori' => $kategori,
                'items' => $stockData['items'],
                'totalValuasi' => $stockData['totalValuasi'],
                'company' => $company
            ]);
            require ROOT_PATH . '/views/reports/inventory_stock_pdf.php';
            $html = ob_get_clean();

            $dateFormatted = date('d M Y');
            $filename = "Katalog dan Valuasi Stok Gudang ({$dateFormatted}).pdf";
            PdfExport::download($html, $filename, 'A4', 'portrait');
        } catch (Throwable $e) {
            $this->handleExportError("Gagal membuat PDF valuasi stok", $e);
        }
    }

    public function exportOpnameHistoryExcel(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));

            $opnames = Database::fetchAll("
                SELECT og.*, u.nama_lengkap as nama_petugas
                FROM public.opname_gudang og
                LEFT JOIN public.pengguna u ON COALESCE(og.dibuat_oleh, og.petugas_id) = u.id
                WHERE COALESCE(og.tanggal, og.tanggal_opname) BETWEEN :start AND :end
                ORDER BY COALESCE(og.tanggal, og.tanggal_opname) DESC, og.dibuat_pada DESC
            ", ['start' => $startDate, 'end' => $endDate]);

            $headers = ['No', 'Nomor Dokumen Opname', 'Tanggal Audit', 'Petugas Pemeriksa', 'Total SKU Diperiksa', 'SKU Selisih', 'Total Nilai Selisih HPP (Rp)', 'Status', 'Keterangan'];
            $rows = [];
            foreach ($opnames as $idx => $op) {
                $rows[] = [
                    $idx + 1,
                    $op['nomor_dokumen'] ?? $op['nomor_opname'] ?? '-',
                    $op['tanggal'] ?? $op['tanggal_opname'] ?? '-',
                    $op['nama_petugas'] ?? '-',
                    (int)($op['total_item_dihitung'] ?? $op['total_sku_diperiksa'] ?? 0),
                    (int)($op['total_item_selisih'] ?? $op['total_sku_selisih'] ?? 0),
                    (float)($op['total_nilai_selisih_rp'] ?? 0),
                    strtoupper((string)($op['status_opname'] ?? 'selesai')),
                    $op['catatan'] ?? $op['keterangan'] ?? '-'
                ];
            }

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Rekap Riwayat Stock Opname ({$dateRange}).xlsx";
            
            ExcelExport::download($filename, $headers, $rows, 'Riwayat Opname', [
                'report_title' => 'REKAPITULASI RIWAYAT AUDIT STOCK OPNAME GUDANG',
                'metadata' => ['Periode' => $dateRange],
                'currency_cols' => ['Total Nilai Selisih HPP (Rp)'],
                'sum_cols' => ['Total SKU Diperiksa', 'SKU Selisih', 'Total Nilai Selisih HPP (Rp)']
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh riwayat opname", $e);
        }
    }

    public function exportVendorPurchasesExcel(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));
            $supplierId = (string)$this->input('supplier_id', 'all');

            $params = ['start' => $startDate, 'end' => $endDate];
            $suppSql = "";
            if ($supplierId !== 'all' && !empty($supplierId)) {
                $suppSql = " AND p.pemasok_id = :sup";
                $params['sup'] = $supplierId;
            }

            $purchases = Database::fetchAll("
                SELECT p.*, pem.nama_pemasok, pem.kode_pemasok
                FROM public.pembelian p
                JOIN public.pemasok pem ON p.pemasok_id = pem.id
                WHERE p.tanggal_pembelian BETWEEN :start AND :end {$suppSql}
                ORDER BY p.tanggal_pembelian DESC, p.dibuat_pada DESC
            ", $params);

            $headers = ['No', 'Nomor Faktur Pembelian', 'Tanggal PO', 'Pemasok / Vendor', 'Kode Vendor', 'Total Biaya (Rp)', 'Status Bayar', 'Status Penerimaan', 'Catatan'];
            $rows = [];
            foreach ($purchases as $idx => $p) {
                $rows[] = [
                    $idx + 1,
                    $p['nomor_faktur_pembelian'],
                    $p['tanggal_pembelian'],
                    $p['nama_pemasok'],
                    $p['kode_pemasok'] ?? '-',
                    (float)$p['total_biaya'],
                    strtoupper((string)$p['status_pembayaran']),
                    strtoupper((string)$p['status_penerimaan']),
                    $p['catatan'] ?? '-'
                ];
            }

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Rekap Pembelian Vendor ({$dateRange}).xlsx";
            
            ExcelExport::download($filename, $headers, $rows, 'Pembelian Vendor', [
                'report_title' => 'REKAPITULASI PEMBELIAN BAHAN BAKU & PENGADAAN VENDOR',
                'metadata' => ['Periode' => $dateRange, 'Supplier Filter' => $supplierId],
                'currency_cols' => ['Total Biaya (Rp)'],
                'sum_cols' => ['Total Biaya (Rp)']
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh pembelian vendor", $e);
        }
    }

    // =========================================================================
    // 6. LOGISTIK & PENGIRIMAN
    // =========================================================================

    public function exportDeliveriesExcel(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));
            $driverId = (string)$this->input('driver_id', 'all');

            $params = ['start' => $startDate, 'end' => $endDate];
            $driverSql = "";
            if ($driverId !== 'all' && !empty($driverId)) {
                $driverSql = " AND sj.sales_driver_id = :drv";
                $params['drv'] = $driverId;
            }

            $deliveries = Database::fetchAll("
                SELECT sj.*, u.nama_lengkap as nama_driver, pes.nomor_nota, pel.nama_toko
                FROM public.surat_jalan sj
                LEFT JOIN public.pengguna u ON sj.sales_driver_id = u.id
                LEFT JOIN public.pesanan pes ON sj.pesanan_id = pes.id
                LEFT JOIN public.pelanggan pel ON pes.pelanggan_id = pel.id
                WHERE sj.tanggal_surat_jalan BETWEEN :start AND :end {$driverSql}
                ORDER BY sj.tanggal_surat_jalan DESC, sj.dibuat_pada DESC
            ", $params);

            $headers = ['No', 'Nomor Surat Jalan', 'Tanggal Surat Jalan', 'Nama Driver', 'No. Faktur Pesanan', 'Toko Tujuan', 'Status Pengiriman', 'Penerima Toko', 'Waktu Sampai'];
            $rows = [];
            foreach ($deliveries as $idx => $d) {
                $rows[] = [
                    $idx + 1,
                    $d['nomor_surat_jalan'],
                    $d['tanggal_surat_jalan'],
                    $d['nama_driver'] ?? '-',
                    $d['nomor_nota'] ?? '-',
                    $d['nama_toko'] ?? '-',
                    strtoupper((string)$d['status_surat_jalan']),
                    $d['nama_penerima_toko'] ?? '-',
                    $d['waktu_sampai'] ? date('Y-m-d H:i', strtotime((string)$d['waktu_sampai'])) : '-'
                ];
            }

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Rekap Surat Jalan Pengiriman ({$dateRange}).xlsx";
            
            ExcelExport::download($filename, $headers, $rows, 'Surat Jalan', [
                'report_title' => 'LOG REKAPITULASI SURAT JALAN & PENGIRIMAN ARMADA',
                'metadata' => ['Periode' => $dateRange, 'Driver Filter' => $driverId]
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh surat jalan", $e);
        }
    }

    // =========================================================================
    // 7. SISTEM & AUDIT LOG
    // =========================================================================

    public function exportActivityLogsExcel(): void
    {
        try {
            [$startDate, $endDate] = $this->sanitizeDateRange((string)$this->input('start_date'), (string)$this->input('end_date'));

            $logs = Database::fetchAll("
                SELECT la.*, u.nama_pengguna
                FROM public.log_aktivitas la
                LEFT JOIN public.pengguna u ON la.pengguna_id = u.id
                WHERE DATE(la.waktu_kejadian) BETWEEN :start AND :end
                ORDER BY la.waktu_kejadian DESC
                LIMIT 5000
            ", ['start' => $startDate, 'end' => $endDate]);

            $headers = ['No', 'Timestamp (WIB)', 'Nama Aktor', 'Username', 'Peran', 'Sumber Aksi', 'Kategori', 'Jenis Aksi', 'Tabel Terdampak', 'Rincian Deskripsi'];
            $rows = [];
            foreach ($logs as $idx => $l) {
                $rows[] = [
                    $idx + 1,
                    date('Y-m-d H:i:s', strtotime((string)$l['waktu_kejadian'])),
                    $l['nama_aktor'] ?? 'Sistem',
                    $l['nama_pengguna'] ?? '-',
                    strtoupper((string)($l['peran_aktor'] ?? '-')),
                    strtoupper((string)($l['sumber_aksi'] ?? '-')),
                    ucwords(str_replace('_', ' ', (string)($l['kategori_aktivitas'] ?? '-'))),
                    strtoupper((string)$l['jenis_aksi']),
                    $l['tabel_terdampak'] ?? '-',
                    $l['deskripsi_aktivitas'] ?? '-'
                ];
            }

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Log Aktivitas Sistem ({$dateRange}).xlsx";
            
            ExcelExport::download($filename, $headers, $rows, 'Activity Logs', [
                'report_title' => 'LOG AUDIT AKTIVITAS & JEJAK DIGITAL PENGGUNA',
                'metadata' => ['Periode' => $dateRange, 'Maksimal Ekspor' => '5.000 Rekaman']
            ]);
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh log", $e);
        }
    }

    // =========================================================================
    // PRIVATE DATA FETCHER HELPERS
    // =========================================================================

    private function fetchPnlData(string $startDate, string $endDate): array
    {
        // 1. Partisi Pendapatan Usaha (Revenue) 100% selaras dengan Owner Dashboard & Pesanan Bertagihan Riil
        $channelRow = Database::fetchOne("
            SELECT 
                COALESCE(SUM(CASE 
                    WHEN (pl.is_konsinyasi = FALSE OR pl.is_konsinyasi IS NULL) 
                         AND pes.tipe_pembayaran != 'konsinyasi'
                         AND (pl.kode_pelanggan = 'CUST-001' OR pes.catatan ILIKE '%POS%' OR pes.catatan ILIKE '%kasir%' OR COALESCE(pes.uang_diterima, 0) > 0)
                    THEN pes.total_netto 
                    ELSE 0 
                END), 0) as pos_omzet,
                COALESCE(SUM(CASE 
                    WHEN (pl.is_konsinyasi = FALSE OR pl.is_konsinyasi IS NULL) 
                         AND pes.tipe_pembayaran != 'konsinyasi'
                         AND (pl.kode_pelanggan != 'CUST-001' OR pl.kode_pelanggan IS NULL)
                         AND (pes.catatan NOT ILIKE '%POS%' AND pes.catatan NOT ILIKE '%kasir%' OR pes.catatan IS NULL)
                         AND COALESCE(pes.uang_diterima, 0) = 0
                    THEN pes.total_netto 
                    ELSE 0 
                END), 0) as b2b_omzet,
                COALESCE(SUM(CASE 
                    WHEN pl.is_konsinyasi = TRUE OR pes.tipe_pembayaran = 'konsinyasi'
                    THEN pes.total_netto 
                    ELSE 0 
                END), 0) as cons_omzet,
                COALESCE(SUM(pes.total_netto), 0) as total_omzet
            FROM public.pesanan pes
            LEFT JOIN public.pelanggan pl ON pes.pelanggan_id = pl.id
            WHERE pes.tanggal_pesanan BETWEEN :start AND :end
              AND pes.status_pemrosesan != 'dibatalkan'
              AND pes.status_pembayaran != 'dibatalkan'
              AND pes.is_tagihan = TRUE
        ", ['start' => $startDate, 'end' => $endDate]) ?? [];

        // 2. Partisi HPP (COGS) Barang Terjual 100% selaras dengan Pesanan Bertagihan Riil
        $cogsRow = Database::fetchOne("
            SELECT 
                COALESCE(SUM(CASE 
                    WHEN (pl.is_konsinyasi = FALSE OR pl.is_konsinyasi IS NULL) 
                         AND p.tipe_pembayaran != 'konsinyasi'
                         AND (pl.kode_pelanggan = 'CUST-001' OR p.catatan ILIKE '%POS%' OR p.catatan ILIKE '%kasir%' OR COALESCE(p.uang_diterima, 0) > 0)
                    THEN ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0)
                    ELSE 0 
                END), 0) as pos_hpp,
                COALESCE(SUM(CASE 
                    WHEN (pl.is_konsinyasi = FALSE OR pl.is_konsinyasi IS NULL) 
                         AND p.tipe_pembayaran != 'konsinyasi'
                         AND (pl.kode_pelanggan != 'CUST-001' OR pl.kode_pelanggan IS NULL)
                         AND (p.catatan NOT ILIKE '%POS%' AND p.catatan NOT ILIKE '%kasir%' OR p.catatan IS NULL)
                         AND COALESCE(p.uang_diterima, 0) = 0
                    THEN ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0)
                    ELSE 0 
                END), 0) as b2b_hpp,
                COALESCE(SUM(CASE 
                    WHEN pl.is_konsinyasi = TRUE OR p.tipe_pembayaran = 'konsinyasi'
                    THEN ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0)
                    ELSE 0 
                END), 0) as cons_hpp,
                COALESCE(SUM(ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0)), 0) as total_hpp
            FROM public.item_pesanan ip
            JOIN public.pesanan p ON ip.pesanan_id = p.id
            LEFT JOIN public.pelanggan pl ON p.pelanggan_id = pl.id
            LEFT JOIN public.item i ON ip.item_id = i.id
            WHERE p.tanggal_pesanan BETWEEN :start AND :end
              AND p.status_pemrosesan != 'dibatalkan'
              AND p.status_pembayaran != 'dibatalkan'
              AND p.is_tagihan = TRUE
        ", ['start' => $startDate, 'end' => $endDate]) ?? [];

        // 3. Estimasi Kerugian Produk Rusak/Kedaluwarsa Konsinyasi (Sebagai data analitik pelengkap)
        $consLossRow = Database::fetchOne("
            SELECT COALESCE(SUM(rk.nilai_kerugian_rusak), 0) as total_kerugian
            FROM public.kunjungan_konsinyasi kk
            JOIN public.rincian_kunjungan_konsinyasi rk ON kk.id = rk.kunjungan_id
            WHERE kk.tanggal_kunjungan BETWEEN :start AND :end
        ", ['start' => $startDate, 'end' => $endDate]) ?? [];

        // 4. Beban Pengeluaran Operasional (Beban Kas Operasional Usaha Bersih)
        // Mengecualikan transaksi akun tabungan escrow karyawan, penarikan tabungan, transfer keluar, dan pembelian bahan baku
        $expenseRows = Database::fetchAll("
            SELECT ark.kategori, COALESCE(SUM(ark.nominal), 0) as total_beban
            FROM public.arus_kas ark
            JOIN public.akun_kas ak ON ark.akun_kas_id = ak.id
            WHERE ark.tanggal_transaksi BETWEEN :start AND :end
              AND ark.jenis_kas = 'keluar'
              AND COALESCE(ak.is_escrow, FALSE) = FALSE
              AND ark.kategori NOT IN ('penarikan_tabungan', 'transfer_keluar', 'pembelian_bahan')
            GROUP BY ark.kategori
            ORDER BY total_beban DESC
        ", ['start' => $startDate, 'end' => $endDate]);

        $posRevenue = (float)($channelRow['pos_omzet'] ?? 0);
        $b2bRevenue = (float)($channelRow['b2b_omzet'] ?? 0);
        $consRevenue = (float)($channelRow['cons_omzet'] ?? 0);
        $totalRevenue = (float)($channelRow['total_omzet'] ?? 0);

        $posHpp = (float)($cogsRow['pos_hpp'] ?? 0);
        $b2bHpp = (float)($cogsRow['b2b_hpp'] ?? 0);
        $consHpp = (float)($cogsRow['cons_hpp'] ?? 0);
        $totalCogs = (float)($cogsRow['total_hpp'] ?? 0);
        $consLoss = (float)($consLossRow['total_kerugian'] ?? 0);

        $grossProfit = $totalRevenue - $totalCogs;

        $totalOperationalExpense = 0.0;
        foreach ($expenseRows as $er) {
            $totalOperationalExpense += (float)$er['total_beban'];
        }

        $netProfit = $grossProfit - $totalOperationalExpense;

        return [
            'posRevenue' => $posRevenue,
            'b2bRevenue' => $b2bRevenue,
            'consRevenue' => $consRevenue,
            'totalRevenue' => $totalRevenue,
            'posHpp' => $posHpp,
            'b2bHpp' => $b2bHpp,
            'consHpp' => $consHpp,
            'consLoss' => $consLoss,
            'totalCogs' => $totalCogs,
            'grossProfit' => $grossProfit,
            'expenseRows' => $expenseRows,
            'totalOperationalExpense' => $totalOperationalExpense,
            'netProfit' => $netProfit
        ];
    }

    private function fetchCashFlowData(string $startDate, string $endDate, string $accountId): array
    {
        $params = ['start' => $startDate, 'end' => $endDate];
        $startParams = ['start' => $startDate];
        $accSql = "";
        $joinAkun = "";
        $accountName = 'Semua Kas Operasional Usaha (Tanpa Tabungan Escrow)';

        if ($accountId === 'all' || $accountId === 'all_with_escrow') {
            $accountName = 'Konsolidasi Seluruh Rekening & Kas (Termasuk Tabungan Escrow)';
        } elseif ($accountId === 'escrow') {
            $accountName = 'Kas Tabungan Karyawan (Rekening Escrow Terkunci)';
            $joinAkun = " JOIN public.akun_kas ak ON ark.akun_kas_id = ak.id ";
            $accSql = " AND ak.is_escrow = TRUE ";
        } elseif ($accountId !== 'operational' && !empty($accountId)) {
            // Akun spesifik via UUID
            $accSql = " AND ark.akun_kas_id = :acc";
            $params['acc'] = $accountId;
            $startParams['acc'] = $accountId;
            $accRow = Database::fetchOne("SELECT nama_akun, COALESCE(is_escrow, FALSE) as is_escrow FROM public.akun_kas WHERE id = :id", ['id' => $accountId]);
            if ($accRow) {
                $accountName = (string)$accRow['nama_akun'] . (!empty($accRow['is_escrow']) ? ' [Tabungan Escrow Terkunci]' : '');
            }
        } else {
            // Default: 'operational'
            $accountName = 'Seluruh Kas Operasional Usaha (Tanpa Tabungan Escrow)';
            $joinAkun = " JOIN public.akun_kas ak ON ark.akun_kas_id = ak.id ";
            $accSql = " AND COALESCE(ak.is_escrow, FALSE) = FALSE ";
        }

        $begRow = Database::fetchOne("
            SELECT COALESCE(SUM(CASE WHEN ark.jenis_kas IN ('masuk', 'transfer_masuk') THEN ark.nominal ELSE -ark.nominal END), 0) as saldo_awal
            FROM public.arus_kas ark
            {$joinAkun}
            WHERE ark.tanggal_transaksi < :start {$accSql}
        ", $startParams);
        $begBalance = (float)($begRow['saldo_awal'] ?? 0);

        $txRows = Database::fetchAll("
            SELECT ark.*, ak.nama_akun
            FROM public.arus_kas ark
            JOIN public.akun_kas ak ON ark.akun_kas_id = ak.id
            WHERE ark.tanggal_transaksi BETWEEN :start AND :end {$accSql}
            ORDER BY ark.tanggal_transaksi ASC, ark.dibuat_pada ASC
        ", $params);

        $inflowBreakdown = Database::fetchAll("
            SELECT ark.kategori, SUM(ark.nominal) as total, COUNT(*) as jml
            FROM public.arus_kas ark
            JOIN public.akun_kas ak ON ark.akun_kas_id = ak.id
            WHERE ark.tanggal_transaksi BETWEEN :start AND :end {$accSql} AND ark.jenis_kas = 'masuk'
            GROUP BY ark.kategori ORDER BY total DESC
        ", $params);

        $outflowBreakdown = Database::fetchAll("
            SELECT ark.kategori, SUM(ark.nominal) as total, COUNT(*) as jml
            FROM public.arus_kas ark
            JOIN public.akun_kas ak ON ark.akun_kas_id = ak.id
            WHERE ark.tanggal_transaksi BETWEEN :start AND :end {$accSql} AND ark.jenis_kas = 'keluar'
            GROUP BY ark.kategori ORDER BY total DESC
        ", $params);

        $totalIn = 0; $totalOut = 0; $netTransfer = 0;
        foreach ($txRows as $t) {
            if ($t['jenis_kas'] === 'masuk') $totalIn += (float)$t['nominal'];
            elseif ($t['jenis_kas'] === 'keluar') $totalOut += (float)$t['nominal'];
            elseif ($t['jenis_kas'] === 'transfer_masuk') $netTransfer += (float)$t['nominal'];
            elseif ($t['jenis_kas'] === 'transfer_keluar') $netTransfer -= (float)$t['nominal'];
        }

        $netCashFlow = $totalIn - $totalOut;
        $isSingleAccount = ($accountId !== 'all' && $accountId !== 'all_with_escrow' && $accountId !== 'operational' && !empty($accountId));
        $endingBalance = $begBalance + $netCashFlow + ($isSingleAccount ? $netTransfer : 0);

        return [
            'accountName' => $accountName,
            'begBalance' => $begBalance,
            'inflowBreakdown' => $inflowBreakdown,
            'outflowBreakdown' => $outflowBreakdown,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'netCashFlow' => $netCashFlow,
            'netTransfer' => $netTransfer,
            'endingBalance' => $endingBalance
        ];
    }

    private function fetchConsignmentInvoicesData(string $status, ?string $startDate = null, ?string $endDate = null): array
    {
        $whereSql = "";
        $params = [];

        if ($status === 'belum_lunas') {
            $whereSql .= " AND pes.status_pembayaran IN ('belum_lunas', 'sebagian', 'tempo')";
        } elseif ($status === 'lunas') {
            $whereSql .= " AND pes.status_pembayaran = 'lunas'";
        }

        if (!empty($startDate) && !empty($endDate)) {
            $whereSql .= " AND pes.tanggal_pesanan BETWEEN :start AND :end";
            $params['start'] = $startDate;
            $params['end'] = $endDate;
        }

        return Database::fetchAll("
            SELECT pes.*, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, 
                   COALESCE(k.nama_karyawan, u.nama_lengkap, '-') as nama_sales
            FROM public.pesanan pes
            JOIN public.pelanggan pel ON pes.pelanggan_id = pel.id
            LEFT JOIN public.v_karyawan_info k ON pes.sales_driver_id = k.id
            LEFT JOIN public.pengguna u ON pes.sales_driver_id = u.id
            WHERE (pes.tipe_pembayaran = 'konsinyasi' OR pel.is_konsinyasi = TRUE)
              AND pes.status_pemrosesan != 'dibatalkan'
              {$whereSql}
            ORDER BY pes.tanggal_jatuh_tempo ASC NULLS LAST, pes.tanggal_pesanan DESC
        ", $params);
    }

    private function fetchInventoryStockData(string $kategori): array
    {
        $whereSql = "WHERE i.status_aktif = TRUE";
        if ($kategori !== 'all' && !empty($kategori)) {
            if ($kategori === 'produk') {
                $whereSql .= " AND i.tipe_item = 'barang_jadi'";
            } elseif ($kategori === 'bahan_baku') {
                $whereSql .= " AND i.tipe_item = 'bahan_mentah'";
            } elseif ($kategori === 'kemasan') {
                $whereSql .= " AND i.tipe_item = 'bahan_kemas'";
            }
        }

        $items = Database::fetchAll("
            SELECT i.*, gp.nama_grup as nama_grup_produk
            FROM public.item i
            LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
            {$whereSql}
            ORDER BY i.tipe_item ASC, i.nama_item ASC
        ");

        $totalValuasi = 0.0;
        foreach ($items as $it) {
            $stok = (float)$it['stok_fisik_saat_ini'];
            $hpp = (float)$it['harga_pokok_pembelian'];
            $totalValuasi += ($stok * $hpp);
        }

        return [
            'items' => $items,
            'totalValuasi' => $totalValuasi
        ];
    }

    private function fetchConsolidatedMetrics(string $startDate, string $endDate): array
    {
        // 1. POS Metrics (Walk-in Kasir Ritel)
        $posRow = Database::fetchOne("
            SELECT 
                COALESCE(SUM(p.total_netto), 0) as omzet,
                COALESCE(SUM(p.total_dibayar), 0) as terbayar,
                COALESCE(SUM(p.sisa_tagihan), 0) as piutang,
                COALESCE(SUM(ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0)), 0) as hpp
            FROM public.pesanan p
            LEFT JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
            LEFT JOIN public.item_pesanan ip ON p.id = ip.pesanan_id
            LEFT JOIN public.item i ON ip.item_id = i.id
            WHERE p.tanggal_pesanan BETWEEN :start AND :end
              AND p.status_pemrosesan != 'dibatalkan'
              AND p.status_pembayaran != 'dibatalkan'
              AND p.is_tagihan = TRUE
              AND (pel.is_konsinyasi = FALSE OR pel.is_konsinyasi IS NULL)
              AND p.tipe_pembayaran != 'konsinyasi'
              AND (pel.kode_pelanggan = 'CUST-001' OR p.catatan ILIKE '%POS%' OR p.catatan ILIKE '%kasir%' OR COALESCE(p.uang_diterima, 0) > 0)
        ", ['start' => $startDate, 'end' => $endDate]);

        // 2. B2B Regular Store Metrics (Grosir Direct)
        $b2bRow = Database::fetchOne("
            SELECT 
                COALESCE(SUM(p.total_netto), 0) as omzet,
                COALESCE(SUM(p.total_dibayar), 0) as terbayar,
                COALESCE(SUM(p.sisa_tagihan), 0) as piutang,
                COALESCE(SUM(ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0)), 0) as hpp
            FROM public.pesanan p
            LEFT JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
            LEFT JOIN public.item_pesanan ip ON p.id = ip.pesanan_id
            LEFT JOIN public.item i ON ip.item_id = i.id
            WHERE p.tanggal_pesanan BETWEEN :start AND :end
              AND p.status_pemrosesan != 'dibatalkan'
              AND p.status_pembayaran != 'dibatalkan'
              AND p.is_tagihan = TRUE
              AND (pel.is_konsinyasi = FALSE OR pel.is_konsinyasi IS NULL)
              AND p.tipe_pembayaran != 'konsinyasi'
              AND (pel.kode_pelanggan != 'CUST-001' OR pel.kode_pelanggan IS NULL)
              AND (p.catatan NOT ILIKE '%POS%' AND p.catatan NOT ILIKE '%kasir%' OR p.catatan IS NULL)
              AND COALESCE(p.uang_diterima, 0) = 0
        ", ['start' => $startDate, 'end' => $endDate]);

        // 3. Consignment Metrics (Titip Jual Rak Mitra Resmi Bertagihan)
        $consRow = Database::fetchOne("
            SELECT 
                COALESCE(SUM(p.total_netto), 0) as omzet,
                COALESCE(SUM(p.total_dibayar), 0) as terbayar,
                COALESCE(SUM(p.sisa_tagihan), 0) as piutang,
                COALESCE(SUM(ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0)), 0) as hpp
            FROM public.pesanan p
            LEFT JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
            LEFT JOIN public.item_pesanan ip ON p.id = ip.pesanan_id
            LEFT JOIN public.item i ON ip.item_id = i.id
            WHERE p.tanggal_pesanan BETWEEN :start AND :end
              AND p.status_pemrosesan != 'dibatalkan'
              AND p.status_pembayaran != 'dibatalkan'
              AND p.is_tagihan = TRUE
              AND (pel.is_konsinyasi = TRUE OR p.tipe_pembayaran = 'konsinyasi')
        ", ['start' => $startDate, 'end' => $endDate]);

        // Kerugian retur rusak dari kunjungan konsinyasi
        $consLossRow = Database::fetchOne("
            SELECT COALESCE(SUM(rk.nilai_kerugian_rusak), 0) as kerugian_rusak
            FROM public.kunjungan_konsinyasi kk
            JOIN public.rincian_kunjungan_konsinyasi rk ON kk.id = rk.kunjungan_id
            WHERE kk.tanggal_kunjungan BETWEEN :start AND :end
        ", ['start' => $startDate, 'end' => $endDate]);

        $posOmzet = (float)($posRow['omzet'] ?? 0);
        $posTerbayar = (float)($posRow['terbayar'] ?? 0);
        $posPiutang = (float)($posRow['piutang'] ?? 0);
        $posHpp = (float)($posRow['hpp'] ?? 0);
        $posLaba = $posOmzet - $posHpp;

        $b2bOmzet = (float)($b2bRow['omzet'] ?? 0);
        $b2bTerbayar = (float)($b2bRow['terbayar'] ?? 0);
        $b2bPiutang = (float)($b2bRow['piutang'] ?? 0);
        $b2bHpp = (float)($b2bRow['hpp'] ?? 0);
        $b2bLaba = $b2bOmzet - $b2bHpp;

        $consOmzet = (float)($consRow['omzet'] ?? 0);
        $consTerbayar = (float)($consRow['terbayar'] ?? 0);
        $consPiutang = (float)($consRow['piutang'] ?? 0);
        $consHpp = (float)($consRow['hpp'] ?? 0);
        $consLoss = (float)($consLossRow['kerugian_rusak'] ?? 0);
        $consLaba = $consOmzet - $consHpp;

        $totalOmzet = $posOmzet + $b2bOmzet + $consOmzet;
        $totalTerbayar = $posTerbayar + $b2bTerbayar + $consTerbayar;
        $totalPiutang = $posPiutang + $b2bPiutang + $consPiutang;
        $totalHpp = $posHpp + $b2bHpp + $consHpp;
        $totalLaba = $totalOmzet - $totalHpp;

        return [
            'pos_omzet' => $posOmzet,
            'pos_terbayar' => $posTerbayar,
            'pos_piutang' => $posPiutang,
            'pos_hpp' => $posHpp,
            'pos_laba' => $posLaba,

            'b2b_omzet' => $b2bOmzet,
            'b2b_terbayar' => $b2bTerbayar,
            'b2b_piutang' => $b2bPiutang,
            'b2b_hpp' => $b2bHpp,
            'b2b_laba' => $b2bLaba,

            'cons_omzet' => $consOmzet,
            'cons_terbayar' => $consTerbayar,
            'cons_piutang' => $consPiutang,
            'cons_hpp' => $consHpp,
            'cons_loss' => $consLoss,
            'cons_laba' => $consLaba,

            'total_omzet' => $totalOmzet,
            'total_terbayar' => $totalTerbayar,
            'total_piutang' => $totalPiutang,
            'total_hpp' => $totalHpp,
            'total_laba' => $totalLaba
        ];
    }

    private function fetchPosDetailRows(string $startDate, string $endDate): array
    {
        $rows = Database::fetchAll("
            SELECT p.nomor_nota, p.tanggal_pesanan, p.dibuat_pada, p.tipe_pembayaran, p.total_netto,
                   u.nama_lengkap as nama_kasir, ak.nama_akun,
                   COALESCE((
                       SELECT SUM(ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, it.harga_pokok_pembelian, 0))
                       FROM public.item_pesanan ip
                       JOIN public.item it ON ip.item_id = it.id
                       WHERE ip.pesanan_id = p.id
                   ), 0) as total_hpp
            FROM public.pesanan p
            LEFT JOIN public.pengguna u ON p.dibuat_oleh = u.id
            LEFT JOIN public.akun_kas ak ON p.akun_kas_id = ak.id
            WHERE p.tanggal_pesanan BETWEEN :start AND :end
              AND p.tipe_pembayaran IN ('cash', 'qris')
              AND p.status_pemrosesan != 'dibatalkan'
              AND p.status_pembayaran != 'dibatalkan'
            ORDER BY p.tanggal_pesanan DESC, p.dibuat_pada DESC
        ", ['start' => $startDate, 'end' => $endDate]);

        $formatted = [];
        foreach ($rows as $idx => $r) {
            $netto = (float)$r['total_netto'];
            $hpp = (float)$r['total_hpp'];
            $laba = $netto - $hpp;
            $formatted[] = [
                $idx + 1,
                $r['nomor_nota'],
                $r['tanggal_pesanan'],
                date('H:i', strtotime((string)$r['dibuat_pada'])),
                $r['nama_kasir'] ?? 'Kasir',
                $r['nama_akun'] ?? 'Kas Utama',
                strtoupper((string)$r['tipe_pembayaran']),
                $netto,
                $hpp,
                $laba
            ];
        }
        return $formatted;
    }

    private function fetchB2bDetailRows(string $startDate, string $endDate): array
    {
        $rows = Database::fetchAll("
            SELECT p.*, pel.nama_toko, pel.kode_pelanggan, u.nama_lengkap as nama_sales,
                   COALESCE((
                       SELECT SUM(ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, it.harga_pokok_pembelian, 0))
                       FROM public.item_pesanan ip
                       JOIN public.item it ON ip.item_id = it.id
                       WHERE ip.pesanan_id = p.id
                   ), 0) as total_hpp
            FROM public.pesanan p
            JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
            LEFT JOIN public.pengguna u ON p.sales_driver_id = u.id
            WHERE p.tanggal_pesanan BETWEEN :start AND :end
              AND p.tipe_pembayaran NOT IN ('cash', 'qris', 'konsinyasi')
              AND pel.is_konsinyasi = FALSE
              AND p.status_pemrosesan != 'dibatalkan'
              AND p.status_pembayaran != 'dibatalkan'
            ORDER BY p.tanggal_pesanan DESC, p.dibuat_pada DESC
        ", ['start' => $startDate, 'end' => $endDate]);

        $formatted = [];
        foreach ($rows as $idx => $r) {
            $netto = (float)$r['total_netto'];
            $hpp = (float)$r['total_hpp'];
            $laba = $netto - $hpp;
            $formatted[] = [
                $idx + 1,
                $r['nomor_nota'],
                $r['tanggal_pesanan'],
                $r['kode_pelanggan'] ?? '-',
                $r['nama_toko'],
                $r['nama_sales'] ?? '-',
                strtoupper((string)$r['tipe_pembayaran']),
                $r['tanggal_jatuh_tempo'] ?? '-',
                (float)$r['total_bruto'],
                (float)$r['total_diskon'],
                $netto,
                (float)$r['total_dibayar'],
                (float)$r['sisa_tagihan'],
                $hpp,
                $laba,
                strtoupper((string)$r['status_pembayaran'])
            ];
        }
        return $formatted;
    }

    private function fetchConsignmentDetailRows(string $startDate, string $endDate): array
    {
        $rows = Database::fetchAll("
            SELECT rk.*, kk.nomor_kunjungan, kk.tanggal_kunjungan, pel.nama_toko, it.nama_item, it.kode_sku,
                   COALESCE(k.nama_karyawan, u.nama_lengkap, '-') as nama_sales,
                   COALESCE(rk.harga_pokok_satuan, it.harga_pokok_pembelian, 0) as hpp_satuan
            FROM public.rincian_kunjungan_konsinyasi rk
            JOIN public.kunjungan_konsinyasi kk ON rk.kunjungan_id = kk.id
            JOIN public.pelanggan pel ON kk.pelanggan_id = pel.id
            JOIN public.item it ON rk.item_id = it.id
            LEFT JOIN public.v_karyawan_info k ON kk.sales_driver_id = k.id
            LEFT JOIN public.pengguna u ON kk.dibuat_oleh = u.id
            WHERE kk.tanggal_kunjungan BETWEEN :start AND :end
            ORDER BY kk.tanggal_kunjungan DESC, pel.nama_toko ASC
        ", ['start' => $startDate, 'end' => $endDate]);

        $formatted = [];
        foreach ($rows as $idx => $r) {
            $qtyLaku = (int)$r['jumlah_laku_terjual'];
            $hargaDeal = (float)$r['harga_satuan_deal'];
            $subtotalLaku = (float)$r['subtotal_laku'];
            $returRusak = (int)$r['retur_rusak'];
            $rugiRusak = (float)$r['nilai_kerugian_rusak'];
            $hppSatuan = (float)$r['hpp_satuan'];
            $hppTotal = $qtyLaku * $hppSatuan;
            $labaMurni = $subtotalLaku - ($hppTotal + $rugiRusak);

            $formatted[] = [
                $idx + 1,
                $r['nomor_kunjungan'],
                $r['tanggal_kunjungan'],
                $r['nama_toko'],
                $r['kode_sku'],
                $r['nama_item'],
                $qtyLaku,
                $hargaDeal,
                $subtotalLaku,
                $returRusak,
                $rugiRusak,
                $hppTotal,
                $labaMurni,
                $r['nama_sales'] ?? '-'
            ];
        }
        return $formatted;
    }
}

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
            $accounts = Database::fetchAll("SELECT id, nama_akun, tipe_akun FROM public.akun_kas WHERE status_aktif = TRUE ORDER BY nama_akun ASC");
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

            $this->view('reports.index', [
                'pageTitle' => 'Pusat Unduh Laporan',
                'pageSubtitle' => 'Portal Terpadu Unduh Laporan & Rekapitulasi Bisnis Format Excel & PDF',
                'stores' => $stores,
                'accounts' => $accounts,
                'suppliers' => $suppliers,
                'salesUsers' => $salesUsers,
                'driverUsers' => $driverUsers,
                'expenseCategories' => $expenseCategories,
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

    // =========================================================================
    // 1. EKSEKUTIF & LABA RUGI (P&L SUMMARY)
    // =========================================================================

    public function exportExecutivePnlExcel(): void
    {
        try {
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));

            // 1. Omzet Penjualan (POS & B2B) dari tabel pesanan
            $salesRow = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(CASE WHEN tipe_pembayaran IN ('cash', 'qris') THEN total_netto ELSE 0 END), 0) as pos_omzet,
                    COALESCE(SUM(CASE WHEN tipe_pembayaran NOT IN ('cash', 'qris') THEN total_netto ELSE 0 END), 0) as b2b_omzet,
                    COALESCE(SUM(total_netto), 0) as total_omzet
                FROM public.pesanan
                WHERE tanggal_pesanan BETWEEN :start AND :end
                  AND status_pemrosesan != 'dibatalkan'
                  AND status_pembayaran != 'dibatalkan'
                  AND is_tagihan = TRUE
            ", ['start' => $startDate, 'end' => $endDate]);

            // 2. HPP Barang Terjual (COGS) dari item_pesanan
            $cogsRow = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(CASE WHEN p.tipe_pembayaran IN ('cash', 'qris') THEN ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0) ELSE 0 END), 0) as pos_hpp,
                    COALESCE(SUM(CASE WHEN p.tipe_pembayaran NOT IN ('cash', 'qris') THEN ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0) ELSE 0 END), 0) as b2b_hpp,
                    COALESCE(SUM(ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0)), 0) as total_hpp
                FROM public.item_pesanan ip
                JOIN public.pesanan p ON ip.pesanan_id = p.id
                LEFT JOIN public.item i ON ip.item_id = i.id
                WHERE p.tanggal_pesanan BETWEEN :start AND :end
                  AND p.status_pemrosesan != 'dibatalkan'
                  AND p.status_pembayaran != 'dibatalkan'
                  AND p.is_tagihan = TRUE
            ", ['start' => $startDate, 'end' => $endDate]);

            // 3. Omzet & Kerugian Konsinyasi (Titip Jual Rak Toko)
            $consRow = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(rk.subtotal_laku), 0) as total_omzet, 
                    COALESCE(SUM(rk.nilai_kerugian_rusak), 0) as total_kerugian
                FROM public.kunjungan_konsinyasi kk
                JOIN public.rincian_kunjungan_konsinyasi rk ON kk.id = rk.kunjungan_id
                WHERE kk.tanggal_kunjungan BETWEEN :start AND :end
            ", ['start' => $startDate, 'end' => $endDate]);

            // 4. Biaya Pengeluaran Kas (Beban Operasional)
            $expenseRows = Database::fetchAll("
                SELECT ark.kategori, COALESCE(SUM(ark.nominal), 0) as total_beban
                FROM public.arus_kas ark
                WHERE ark.jenis_kas = 'keluar' AND ark.tanggal_transaksi BETWEEN :start AND :end
                GROUP BY ark.kategori
                ORDER BY total_beban DESC
            ", ['start' => $startDate, 'end' => $endDate]);

            $posRevenue = (float)($salesRow['pos_omzet'] ?? 0);
            $b2bRevenue = (float)($salesRow['b2b_omzet'] ?? 0);
            $consRevenue = (float)($consRow['total_omzet'] ?? 0);

            $posHpp = (float)($cogsRow['pos_hpp'] ?? 0);
            $b2bHpp = (float)($cogsRow['b2b_hpp'] ?? 0);
            $consLoss = (float)($consRow['total_kerugian'] ?? 0);

            $totalRevenue = $posRevenue + $b2bRevenue + $consRevenue;
            $totalCogs = $posHpp + $b2bHpp + $consLoss;
            $grossProfit = $totalRevenue - $totalCogs;

            $totalOperationalExpense = 0;
            foreach ($expenseRows as $er) {
                $totalOperationalExpense += (float)$er['total_beban'];
            }

            $netProfit = $grossProfit - $totalOperationalExpense;

            $headers = ['Kategori Akun / Metrik', 'Keterangan Analitik', 'Nominal (Rp)'];
            $rows = [
                ['PENDAPATAN USAHA (REVENUE)', '', ''],
                ['1. Penjualan Kasir POS', 'Penjualan ritel langsung kasir', $posRevenue],
                ['2. Penjualan Pesanan Pelanggan (B2B)', 'Faktur penjualan grosir reguler', $b2bRevenue],
                ['3. Penjualan Konsinyasi (Titip Jual)', 'Total barang laku di rak toko mitra', $consRevenue],
                ['TOTAL PENDAPATAN (OMZET KOTOR)', 'Total seluruh kanal penjualan', $totalRevenue],
                ['', '', ''],
                ['HARGA POKOK PENJUALAN & KERUGIAN (HPP / COGS)', '', ''],
                ['1. HPP Penjualan POS', 'Beban pokok produk kasir POS', $posHpp],
                ['2. HPP Penjualan B2B', 'Beban pokok pesanan B2B', $b2bHpp],
                ['3. Estimasi Kerugian Produk Rusak/Basi', 'Kerugian retur kedaluwarsa konsinyasi', $consLoss],
                ['TOTAL BEBAN POKOK (HPP)', 'Total modal produk terjual', $totalCogs],
                ['', '', ''],
                ['LABA KOTOR (GROSS PROFIT)', 'Total Omzet dikurangi Total HPP', $grossProfit],
                ['', '', ''],
                ['BEBAN OPERASIONAL (EXPENSES)', '', ''],
            ];

            if (!empty($expenseRows)) {
                foreach ($expenseRows as $idx => $er) {
                    $rows[] = [($idx + 1) . '. Beban: ' . ucwords(str_replace('_', ' ', (string)$er['kategori'])), 'Pengeluaran kas operasional', (float)$er['total_beban']];
                }
            } else {
                $rows[] = ['Beban Operasional', 'Tidak ada pengeluaran kas pada periode ini', 0];
            }

            $rows[] = ['TOTAL BEBAN OPERASIONAL', 'Total pengeluaran kas periode ini', $totalOperationalExpense];
            $rows[] = ['', '', ''];
            $rows[] = ['ESTIMASI LABA BERSIH (NET PROFIT)', 'Laba Kotor dikurangi Total Beban Operasional', $netProfit];

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Laporan Laba Rugi Eksekutif ({$dateRange}).xlsx";
            ExcelExport::download($filename, $headers, $rows, 'Ringkasan PnL');
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh laporan laba rugi", $e);
        }
    }

    public function exportExecutivePnlPdf(): void
    {
        try {
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));

            $salesRow = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(CASE WHEN tipe_pembayaran IN ('cash', 'qris') THEN total_netto ELSE 0 END), 0) as pos_omzet,
                    COALESCE(SUM(CASE WHEN tipe_pembayaran NOT IN ('cash', 'qris') THEN total_netto ELSE 0 END), 0) as b2b_omzet,
                    COALESCE(SUM(total_netto), 0) as total_omzet
                FROM public.pesanan
                WHERE tanggal_pesanan BETWEEN :start AND :end
                  AND status_pemrosesan != 'dibatalkan'
                  AND status_pembayaran != 'dibatalkan'
                  AND is_tagihan = TRUE
            ", ['start' => $startDate, 'end' => $endDate]);

            $cogsRow = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(CASE WHEN p.tipe_pembayaran IN ('cash', 'qris') THEN ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0) ELSE 0 END), 0) as pos_hpp,
                    COALESCE(SUM(CASE WHEN p.tipe_pembayaran NOT IN ('cash', 'qris') THEN ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0) ELSE 0 END), 0) as b2b_hpp,
                    COALESCE(SUM(ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0)), 0) as total_hpp
                FROM public.item_pesanan ip
                JOIN public.pesanan p ON ip.pesanan_id = p.id
                LEFT JOIN public.item i ON ip.item_id = i.id
                WHERE p.tanggal_pesanan BETWEEN :start AND :end
                  AND p.status_pemrosesan != 'dibatalkan'
                  AND p.status_pembayaran != 'dibatalkan'
                  AND p.is_tagihan = TRUE
            ", ['start' => $startDate, 'end' => $endDate]);

            $consRow = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(rk.subtotal_laku), 0) as total_omzet, 
                    COALESCE(SUM(rk.nilai_kerugian_rusak), 0) as total_kerugian
                FROM public.kunjungan_konsinyasi kk
                JOIN public.rincian_kunjungan_konsinyasi rk ON kk.id = rk.kunjungan_id
                WHERE kk.tanggal_kunjungan BETWEEN :start AND :end
            ", ['start' => $startDate, 'end' => $endDate]);

            $expenseRows = Database::fetchAll("
                SELECT ark.kategori, COALESCE(SUM(ark.nominal), 0) as total_beban
                FROM public.arus_kas ark
                WHERE ark.jenis_kas = 'keluar' AND ark.tanggal_transaksi BETWEEN :start AND :end
                GROUP BY ark.kategori
                ORDER BY total_beban DESC
            ", ['start' => $startDate, 'end' => $endDate]);

            $posRevenue = (float)($salesRow['pos_omzet'] ?? 0);
            $b2bRevenue = (float)($salesRow['b2b_omzet'] ?? 0);
            $consRevenue = (float)($consRow['total_omzet'] ?? 0);

            $posHpp = (float)($cogsRow['pos_hpp'] ?? 0);
            $b2bHpp = (float)($cogsRow['b2b_hpp'] ?? 0);
            $consLoss = (float)($consRow['total_kerugian'] ?? 0);

            $totalRevenue = $posRevenue + $b2bRevenue + $consRevenue;
            $totalCogs = $posHpp + $b2bHpp + $consLoss;
            $grossProfit = $totalRevenue - $totalCogs;

            $totalOperationalExpense = 0;
            foreach ($expenseRows as $er) {
                $totalOperationalExpense += (float)$er['total_beban'];
            }
            $netProfit = $grossProfit - $totalOperationalExpense;

            $company = CompanySetting::getAll();
            $companyName = $company['nama'] ?? 'KEREN SNACK INDONESIA';
            $companyAddress = $company['alamat'] ?? 'Jl. Industri Snack No. 88, Jawa Barat';

            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Laporan Kinerja Keuangan & Laba Rugi Eksekutif</title>
                <style>
                    body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #1e293b; line-height: 1.5; margin: 20px; }
                    .header { text-align: center; border-bottom: 2px solid #881337; padding-bottom: 12px; margin-bottom: 20px; }
                    .header h1 { margin: 0; font-size: 18px; color: #881337; text-transform: uppercase; }
                    .header p { margin: 3px 0 0; font-size: 11px; color: #64748b; }
                    .meta-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 15px; margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    th, td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
                    th { background: #881337; color: #ffffff; text-align: left; font-size: 11px; text-transform: uppercase; }
                    .section-title { font-weight: bold; background: #f1f5f9; color: #0f172a; }
                    .total-row { font-weight: bold; background: #fff1f2; color: #881337; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                    .footer { text-align: right; font-size: 10px; color: #94a3b8; margin-top: 30px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>' . htmlspecialchars($companyName) . '</h1>
                    <p>' . htmlspecialchars($companyAddress) . '</p>
                    <p style="margin-top: 6px; font-weight: bold; color: #0f172a;">RINGKASAN KINERJA KEUANGAN &amp; LABA RUGI (EXECUTIVE SUMMARY)</p>
                </div>

                <div class="meta-box">
                    <table style="margin: 0; width: 100%; border: none;">
                        <tr>
                            <td style="border:none; padding:2px 0;"><strong>Periode Analisis:</strong> ' . date('d M Y', strtotime($startDate)) . ' s/d ' . date('d M Y', strtotime($endDate)) . '</td>
                            <td style="border:none; padding:2px 0; text-align:right;"><strong>Dicetak Pada:</strong> ' . date('d M Y H:i') . ' WIB</td>
                        </tr>
                        <tr>
                            <td style="border:none; padding:2px 0;"><strong>Dicetak Oleh:</strong> ' . htmlspecialchars(Auth::name()) . ' (' . ucfirst(Auth::role()) . ')</td>
                            <td style="border:none; padding:2px 0; text-align:right;"><strong>Status:</strong> Dokumen Resmi Manajemen</td>
                        </tr>
                    </table>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Keterangan Komponen Bisnis</th>
                            <th class="text-right">Nominal (Rp)</th>
                            <th class="text-right">Rasio (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="section-title">
                            <td colspan="3">1. PENDAPATAN USAHA (REVENUE)</td>
                        </tr>
                        <tr>
                            <td>&bull; Penjualan Kasir POS (Ritel Walk-in)</td>
                            <td class="text-right">' . Format::rupiah($posRevenue) . '</td>
                            <td class="text-right">' . ($totalRevenue > 0 ? number_format(($posRevenue / $totalRevenue) * 100, 1) : '0') . '%</td>
                        </tr>
                        <tr>
                            <td>&bull; Penjualan Pesanan Pelanggan B2B / Grosir</td>
                            <td class="text-right">' . Format::rupiah($b2bRevenue) . '</td>
                            <td class="text-right">' . ($totalRevenue > 0 ? number_format(($b2bRevenue / $totalRevenue) * 100, 1) : '0') . '%</td>
                        </tr>
                        <tr>
                            <td>&bull; Penjualan Konsinyasi (Titip Jual Rak)</td>
                            <td class="text-right">' . Format::rupiah($consRevenue) . '</td>
                            <td class="text-right">' . ($totalRevenue > 0 ? number_format(($consRevenue / $totalRevenue) * 100, 1) : '0') . '%</td>
                        </tr>
                        <tr class="total-row">
                            <td>TOTAL PENDAPATAN (OMZET KOTOR)</td>
                            <td class="text-right">' . Format::rupiah($totalRevenue) . '</td>
                            <td class="text-right">100.0%</td>
                        </tr>

                        <tr class="section-title">
                            <td colspan="3">2. BEBAN POKOK PRODUK &amp; KERUGIAN (HPP / COGS)</td>
                        </tr>
                        <tr>
                            <td>&bull; HPP Penjualan POS</td>
                            <td class="text-right">' . Format::rupiah($posHpp) . '</td>
                            <td class="text-right">-</td>
                        </tr>
                        <tr>
                            <td>&bull; HPP Penjualan Pesanan B2B</td>
                            <td class="text-right">' . Format::rupiah($b2bHpp) . '</td>
                            <td class="text-right">-</td>
                        </tr>
                        <tr>
                            <td>&bull; Estimasi Kerugian Produk Rusak/Basi Konsinyasi</td>
                            <td class="text-right">' . Format::rupiah($consLoss) . '</td>
                            <td class="text-right">-</td>
                        </tr>
                        <tr class="total-row">
                            <td>TOTAL BEBAN POKOK (HPP)</td>
                            <td class="text-right">' . Format::rupiah($totalCogs) . '</td>
                            <td class="text-right">' . ($totalRevenue > 0 ? number_format(($totalCogs / $totalRevenue) * 100, 1) : '0') . '%</td>
                        </tr>

                        <tr style="background:#f8fafc; font-weight:bold;">
                            <td>MARGIN LABA KOTOR (GROSS PROFIT)</td>
                            <td class="text-right" style="color:#059669;">' . Format::rupiah($grossProfit) . '</td>
                            <td class="text-right" style="color:#059669;">' . ($totalRevenue > 0 ? number_format(($grossProfit / $totalRevenue) * 100, 1) : '0') . '%</td>
                        </tr>

                        <tr class="section-title">
                            <td colspan="3">3. BEBAN OPERASIONAL KAS (EXPENSES)</td>
                        </tr>';

            if (!empty($expenseRows)) {
                foreach ($expenseRows as $er) {
                    $html .= '
                    <tr>
                        <td>&bull; Beban ' . htmlspecialchars(ucwords(str_replace('_', ' ', (string)$er['kategori']))) . '</td>
                        <td class="text-right">' . Format::rupiah((float)$er['total_beban']) . '</td>
                        <td class="text-right">' . ($totalRevenue > 0 ? number_format(((float)$er['total_beban'] / $totalRevenue) * 100, 1) : '0') . '%</td>
                    </tr>';
                }
            } else {
                $html .= '<tr><td colspan="3" class="text-center" style="color:#94a3b8;">Tidak ada pengeluaran kas pada periode ini</td></tr>';
            }

            $html .= '
                        <tr class="total-row">
                            <td>TOTAL BEBAN OPERASIONAL</td>
                            <td class="text-right">' . Format::rupiah($totalOperationalExpense) . '</td>
                            <td class="text-right">' . ($totalRevenue > 0 ? number_format(($totalOperationalExpense / $totalRevenue) * 100, 1) : '0') . '%</td>
                        </tr>

                        <tr style="background:' . ($netProfit >= 0 ? '#ecfdf5' : '#fef2f2') . '; font-weight:bold; font-size:13px;">
                            <td>ESTIMASI LABA BERSIH (NET PROFIT)</td>
                            <td class="text-right" style="color:' . ($netProfit >= 0 ? '#059669' : '#dc2626') . ';">' . Format::rupiah($netProfit) . '</td>
                            <td class="text-right" style="color:' . ($netProfit >= 0 ? '#059669' : '#dc2626') . ';">' . ($totalRevenue > 0 ? number_format(($netProfit / $totalRevenue) * 100, 1) : '0') . '%</td>
                        </tr>
                    </tbody>
                </table>

                <div class="footer">
                    Dokumen ini digenerate secara otomatis oleh sistem <strong>KEREN ONE ERP</strong> &bull; ' . date('Y-m-d H:i:s') . '
                </div>
            </body>
            </html>';

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
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));
            $accountId = (string)$this->input('account_id', 'all');

            $params = ['start' => $startDate, 'end' => $endDate];
            $accSql = "";
            if ($accountId !== 'all' && !empty($accountId)) {
                $accSql = " AND ark.akun_kas_id = :acc";
                $params['acc'] = $accountId;
            }

            // Saldo Awal
            $startParams = ['start' => $startDate];
            if ($accountId !== 'all' && !empty($accountId)) {
                $startParams['acc'] = $accountId;
            }
            $begRow = Database::fetchOne("
                SELECT COALESCE(SUM(CASE WHEN ark.jenis_kas IN ('masuk', 'transfer_masuk') THEN ark.nominal ELSE -ark.nominal END), 0) as saldo_awal
                FROM public.arus_kas ark
                WHERE ark.tanggal_transaksi < :start {$accSql}
            ", $startParams);
            $begBalance = (float)($begRow['saldo_awal'] ?? 0);

            // Inflow, Outflow, Transfer
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
                WHERE ark.tanggal_transaksi BETWEEN :start AND :end {$accSql} AND ark.jenis_kas = 'masuk'
                GROUP BY ark.kategori ORDER BY total DESC
            ", $params);

            $outflowBreakdown = Database::fetchAll("
                SELECT ark.kategori, SUM(ark.nominal) as total, COUNT(*) as jml
                FROM public.arus_kas ark
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
            $endingBalance = $begBalance + $netCashFlow + ($accountId !== 'all' ? $netTransfer : 0);

            $headers = ['Kategori / Deskripsi Arus Kas', 'Jumlah Transaksi', 'Nominal (Rp)'];
            $rows = [
                ['SALDO AWAL KAS & BANK', '-', $begBalance],
                ['', '', ''],
                ['PENERIMAAN KAS MASUK (INFLOW)', '', '']
            ];

            foreach ($inflowBreakdown as $ib) {
                $rows[] = ['Pemasukan: ' . ucwords(str_replace('_', ' ', (string)$ib['kategori'])), (int)$ib['jml'], (float)$ib['total']];
            }
            $rows[] = ['TOTAL PENERIMAAN KAS', '-', $totalIn];
            $rows[] = ['', '', ''];
            $rows[] = ['PENGELUARAN KAS KELUAR (OUTFLOW)', '', ''];

            foreach ($outflowBreakdown as $ob) {
                $rows[] = ['Beban: ' . ucwords(str_replace('_', ' ', (string)$ob['kategori'])), (int)$ob['jml'], (float)$ob['total']];
            }
            $rows[] = ['TOTAL PENGELUARAN KAS', '-', $totalOut];
            $rows[] = ['', '', ''];
            $rows[] = ['ARUS KAS BERSIH (NET CASH FLOW)', '-', $netCashFlow];
            if ($accountId !== 'all') {
                $rows[] = ['MUTASI TRANSFER DANA BERSIH', '-', $netTransfer];
            }
            $rows[] = ['SALDO AKHIR KAS & BANK', '-', $endingBalance];

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Laporan Arus Kas ({$dateRange}).xlsx";
            ExcelExport::download($filename, $headers, $rows, 'Arus Kas');
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh arus kas", $e);
        }
    }

    public function exportCashTransactionsExcel(): void
    {
        try {
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));
            $accountId = (string)$this->input('account_id', 'all');
            $type = (string)$this->input('type', 'all');

            $params = ['start' => $startDate, 'end' => $endDate];
            $whereSql = "WHERE ark.tanggal_transaksi BETWEEN :start AND :end";

            if ($accountId !== 'all' && !empty($accountId)) {
                $whereSql .= " AND ark.akun_kas_id = :acc";
                $params['acc'] = $accountId;
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
            ExcelExport::download($filename, $headers, $rows, 'Mutasi Kas');
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
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));
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
            ExcelExport::download($filename, $headers, $rows, 'Pesanan B2B');
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
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));
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
            ExcelExport::download($filename, $headers, $rows, 'Penjualan Konsinyasi');
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh penjualan konsinyasi", $e);
        }
    }

    public function exportConsignmentLossExcel(): void
    {
        try {
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));

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
            ExcelExport::download($filename, $headers, $rows, 'Kerugian Konsinyasi');
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh laporan kerugian", $e);
        }
    }

    public function exportConsignmentInvoicesExcel(): void
    {
        try {
            $status = (string)$this->input('status', 'all');
            $params = [];
            $whereSql = "";
            if ($status === 'belum_lunas') {
                $whereSql = " AND pes.status_pembayaran IN ('belum_lunas', 'sebagian', 'tempo')";
            } elseif ($status === 'lunas') {
                $whereSql = " AND pes.status_pembayaran = 'lunas'";
            }

            $invoices = Database::fetchAll("
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

            $dateFormatted = date('d M Y');
            $filename = "Rekap Tagihan Piutang Konsinyasi ({$dateFormatted}).xlsx";
            ExcelExport::download($filename, $headers, $rows, 'Piutang Konsinyasi');
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh tagihan", $e);
        }
    }

    public function exportSalesCommissionsExcel(): void
    {
        try {
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));

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

            $headers = ['No', 'Nama Salesman', 'Panggilan', 'Omzet Konsinyasi (Rp)', 'Omzet B2B (Rp)', 'Total Omzet (Rp)', 'Estimasi Komisi (Rp)'];
            $rows = [];
            foreach ($salesList as $idx => $s) {
                $omzetKons = (float)$s['total_omzet_konsinyasi'];
                $omzetB2B = (float)$s['total_omzet_b2b'];
                $totalOmzet = $omzetKons + $omzetB2B;
                // Estimasi tier standar 2.5%
                $komisiEst = $totalOmzet * 0.025;

                $rows[] = [
                    $idx + 1,
                    $s['nama_lengkap'],
                    $s['nama_panggilan'] ?? '-',
                    $omzetKons,
                    $omzetB2B,
                    $totalOmzet,
                    $komisiEst
                ];
            }

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Rekap Komisi Salesman ({$dateRange}).xlsx";
            ExcelExport::download($filename, $headers, $rows, 'Komisi Sales');
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh komisi", $e);
        }
    }

    public function exportSalesVisitsExcel(): void
    {
        try {
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));
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
            ExcelExport::download($filename, $headers, $rows, 'Riwayat Kunjungan');
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
            $params = [];
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
            ", $params);

            $headers = ['No', 'Kode SKU', 'Nama Item / Produk', 'Tipe Item', 'Grup Produk', 'Satuan', 'Stok Fisik', 'Batas Min Stok', 'HPP Satuan (Rp)', 'Total Nilai Valuasi (Rp)', 'Status Stok'];
            $rows = [];
            foreach ($items as $idx => $it) {
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
            ExcelExport::download($filename, $headers, $rows, 'Valuasi Stok');
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh stok", $e);
        }
    }

    public function exportOpnameHistoryExcel(): void
    {
        try {
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));

            $opnames = Database::fetchAll("
                SELECT og.*, u.nama_lengkap as nama_petugas
                FROM public.opname_gudang og
                LEFT JOIN public.pengguna u ON og.petugas_id = u.id
                WHERE og.tanggal_opname BETWEEN :start AND :end
                ORDER BY og.tanggal_opname DESC, og.dibuat_pada DESC
            ", ['start' => $startDate, 'end' => $endDate]);

            $headers = ['No', 'Nomor Dokumen Opname', 'Tanggal Audit', 'Petugas Pemeriksa', 'Total SKU Diperiksa', 'SKU Selisih', 'Total Nilai Selisih HPP (Rp)', 'Status', 'Keterangan'];
            $rows = [];
            foreach ($opnames as $idx => $op) {
                $rows[] = [
                    $idx + 1,
                    $op['nomor_opname'],
                    $op['tanggal_opname'],
                    $op['nama_petugas'] ?? '-',
                    (int)($op['total_sku_diperiksa'] ?? 0),
                    (int)($op['total_sku_selisih'] ?? 0),
                    (float)($op['total_nilai_selisih_rp'] ?? 0),
                    strtoupper((string)($op['status_opname'] ?? 'selesai')),
                    $op['keterangan'] ?? '-'
                ];
            }

            $dateRange = date('d M Y', strtotime($startDate)) . ' sd ' . date('d M Y', strtotime($endDate));
            $filename = "Rekap Riwayat Stock Opname ({$dateRange}).xlsx";
            ExcelExport::download($filename, $headers, $rows, 'Riwayat Opname');
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh riwayat opname", $e);
        }
    }

    public function exportVendorPurchasesExcel(): void
    {
        try {
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));
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
            ExcelExport::download($filename, $headers, $rows, 'Pembelian Vendor');
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
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));
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
            ExcelExport::download($filename, $headers, $rows, 'Surat Jalan');
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
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));

            $logs = Database::fetchAll("
                SELECT la.*, u.nama_pengguna
                FROM public.log_aktivitas la
                LEFT JOIN public.pengguna u ON la.pengguna_id = u.id
                WHERE DATE(la.dibuat_pada) BETWEEN :start AND :end
                ORDER BY la.dibuat_pada DESC
                LIMIT 5000
            ", ['start' => $startDate, 'end' => $endDate]);

            $headers = ['No', 'Timestamp (WIB)', 'Nama Aktor', 'Username', 'Peran', 'Sumber Aksi', 'Kategori', 'Jenis Aksi', 'Tabel Terdampak', 'Rincian Deskripsi'];
            $rows = [];
            foreach ($logs as $idx => $l) {
                $rows[] = [
                    $idx + 1,
                    date('Y-m-d H:i:s', strtotime((string)$l['dibuat_pada'])),
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
            ExcelExport::download($filename, $headers, $rows, 'Activity Logs');
        } catch (Throwable $e) {
            $this->handleExportError("Gagal mengunduh log", $e);
        }
    }
}

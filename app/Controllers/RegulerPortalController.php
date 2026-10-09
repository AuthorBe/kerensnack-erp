<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;
use Throwable;

class RegulerPortalController extends Controller
{
    /**
     * Dashboard Hub Utama Portal Reguler (B2B Grosir)
     */
    public function index(): void
    {
        Auth::requirePermission(['orders.view_all', 'orders.view_assigned']);

        try {
            $isRestricted = !Auth::can('orders.view_all');
            $myEmpId = Auth::employeeId();

            // 1. KPI Finansial & Risiko Piutang Reguler
            $kpi = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(p.total_piutang_berjalan), 0) as total_piutang_nasional,
                    COUNT(p.id) as total_toko_aktif,
                    COUNT(CASE WHEN p.total_piutang_berjalan > 0 THEN 1 END) as total_toko_berhutang,
                    COUNT(CASE WHEN p.plafon_piutang > 0 AND p.total_piutang_berjalan > p.plafon_piutang THEN 1 END) as total_toko_over_plafon
                FROM public.pelanggan p
                WHERE p.status_aktif = TRUE AND p.is_konsinyasi = FALSE
                  AND p.kode_pelanggan != 'CUST-001'
                  AND p.nama_toko NOT ILIKE '%UMUM%'
            ") ?? [];

            // 2. Faktur Gantung & Peringatan Tempo
            $fakturKpi = Database::fetchOne("
                SELECT 
                    COUNT(pes.id) as total_faktur_gantung,
                    COALESCE(SUM(pes.sisa_tagihan), 0) as total_nominal_gantung,
                    COUNT(CASE WHEN pes.tipe_pembayaran = 'tempo_faktur' THEN 1 END) as total_faktur_tempo_faktur,
                    COUNT(CASE WHEN pes.tipe_pembayaran = 'tempo_tanggal' THEN 1 END) as total_faktur_tempo_tanggal
                FROM public.pesanan pes
                JOIN public.pelanggan pel ON pes.pelanggan_id = pel.id
                WHERE pes.status_pemrosesan != 'dibatalkan'
                  AND pes.status_pembayaran != 'dibatalkan'
                  AND pes.is_tagihan = TRUE
                  AND pes.status_pembayaran != 'lunas'
                  AND pes.tipe_pembayaran != 'konsinyasi'
                  AND pel.is_konsinyasi = FALSE
            ") ?? [];

            // 3. Toko Tempo Faktur dengan >= 2 Faktur Gantung
            $menumpukCount = (int)Database::fetchOne("
                SELECT COUNT(*) as cnt FROM (
                    SELECT pes.pelanggan_id
                    FROM public.pesanan pes
                    JOIN public.pelanggan pel ON pes.pelanggan_id = pel.id
                    WHERE pes.status_pemrosesan != 'dibatalkan'
                      AND pes.status_pembayaran != 'dibatalkan'
                      AND pes.is_tagihan = TRUE
                      AND pes.status_pembayaran != 'lunas'
                      AND pes.tipe_pembayaran = 'tempo_faktur'
                      AND pel.is_konsinyasi = FALSE
                    GROUP BY pes.pelanggan_id
                    HAVING COUNT(pes.id) >= 2
                ) sub
            ")['cnt'] ?? 0;

            // 4. Toko Tempo Tanggal yang Overdue (Jatuh Tempo Lewat)
            $overdueCount = (int)Database::fetchOne("
                SELECT COUNT(DISTINCT pes.pelanggan_id) as cnt
                FROM public.pesanan pes
                JOIN public.pelanggan pel ON pes.pelanggan_id = pel.id
                WHERE pes.status_pemrosesan != 'dibatalkan'
                  AND pes.status_pembayaran != 'dibatalkan'
                  AND pes.is_tagihan = TRUE
                  AND pes.status_pembayaran != 'lunas'
                  AND pes.tipe_pembayaran = 'tempo_tanggal'
                  AND pes.tanggal_jatuh_tempo IS NOT NULL
                  AND pes.tanggal_jatuh_tempo < CURRENT_DATE
                  AND pel.is_konsinyasi = FALSE
            ")['cnt'] ?? 0;

            // 5. Top 5 Toko dengan Piutang Terbesar (Sinkron Pesanan Riil & Saldo Berjalan)
            $topDebtors = Database::fetchAll("
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp,
                       COALESCE(p.plafon_piutang, 0) as plafon_piutang,
                       COALESCE(SUM(pes.sisa_tagihan), p.total_piutang_berjalan, 0) as total_piutang_berjalan,
                       p.tipe_pembayaran_default,
                       w.nama_wilayah,
                       COUNT(pes.id) as total_faktur_gantung,
                       MIN(pes.tanggal_pesanan) as faktur_tertua
                FROM public.pelanggan p
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                LEFT JOIN public.pesanan pes ON (
                    pes.pelanggan_id = p.id 
                    AND pes.status_pemrosesan != 'dibatalkan' 
                    AND pes.status_pembayaran != 'dibatalkan'
                    AND pes.status_pembayaran != 'lunas'
                    AND pes.is_tagihan = TRUE
                )
                WHERE p.status_aktif = TRUE 
                  AND p.is_konsinyasi = FALSE
                GROUP BY p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp,
                         p.plafon_piutang, p.total_piutang_berjalan, p.tipe_pembayaran_default, w.nama_wilayah
                HAVING (COALESCE(SUM(pes.sisa_tagihan), p.total_piutang_berjalan, 0) > 0 OR COUNT(pes.id) > 0)
                ORDER BY total_piutang_berjalan DESC
                LIMIT 5
            ");

            $this->view('reguler.index', [
                'pageTitle' => 'Portal Pesanan Reguler',
                'pageSubtitle' => 'Pusat Manajemen Piutang Grosir B2B, Penagihan Tempo & Monitoring Risiko Kredit Toko',
                'kpi' => $kpi,
                'fakturKpi' => $fakturKpi,
                'menumpukCount' => $menumpukCount,
                'overdueCount' => $overdueCount,
                'topDebtors' => $topDebtors,
                'isRestricted' => $isRestricted
            ]);
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat Portal Reguler: ' . $e->getMessage());
            $this->redirect('/customer-orders');
        }
    }

    /**
     * Buku Piutang per Toko & Penagihan Tagihan Tempo
     */
    public function tagihan(): void
    {
        Auth::requirePermission(['orders.view_all', 'orders.view_assigned']);

        try {
            $q = trim((string)$this->input('q', ''));
            $wilayahId = $this->input('wilayah_id', '');
            $tipeBayar = $this->input('tipe_bayar', 'semua');
            $statusPiutang = $this->input('status_piutang', 'berhutang'); // berhutang | over_plafon | semua

            $sql = "
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp, p.alamat_lengkap,
                       p.tipe_pembayaran_default, p.plafon_piutang, p.total_piutang_berjalan,
                       COALESCE(w.nama_wilayah, '—') as nama_wilayah,
                       COALESCE(k.nama_karyawan, '—') as nama_sales,
                       COUNT(pes.id) as total_faktur_gantung,
                       COALESCE(SUM(pes.sisa_tagihan), 0) as sisa_tagihan_total,
                       MIN(pes.tanggal_pesanan) as tgl_faktur_tertua,
                       COALESCE(
                           JSON_AGG(
                               JSON_BUILD_OBJECT(
                                   'id', pes.id,
                                   'nomor_nota', pes.nomor_nota,
                                   'tanggal_pesanan', pes.tanggal_pesanan,
                                   'tipe_pembayaran', pes.tipe_pembayaran,
                                   'tanggal_jatuh_tempo', pes.tanggal_jatuh_tempo,
                                   'total_netto', pes.total_netto,
                                   'total_dibayar', pes.total_dibayar,
                                   'sisa_tagihan', pes.sisa_tagihan,
                                   'status_pembayaran', pes.status_pembayaran
                               ) ORDER BY pes.tanggal_pesanan ASC
                           ) FILTER (WHERE pes.id IS NOT NULL), '[]'::json
                       ) as rincian_faktur_json
                FROM public.pelanggan p
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                LEFT JOIN public.pesanan pes ON (
                    pes.pelanggan_id = p.id 
                    AND pes.status_pemrosesan != 'dibatalkan' 
                    AND pes.status_pembayaran != 'dibatalkan'
                    AND pes.status_pembayaran != 'lunas'
                    AND pes.is_tagihan = TRUE
                )
                WHERE p.status_aktif = TRUE 
                  AND p.is_konsinyasi = FALSE
                  AND p.kode_pelanggan != 'CUST-001'
                  AND p.nama_toko NOT ILIKE '%UMUM%'
            ";

            $params = [];

            if (!empty($q)) {
                $sql .= " AND (p.nama_toko ILIKE :q OR p.kode_pelanggan ILIKE :q OR p.nama_pemilik ILIKE :q)";
                $params['q'] = "%{$q}%";
            }

            if (!empty($wilayahId)) {
                $sql .= " AND p.wilayah_id = :wilayah_id";
                $params['wilayah_id'] = $wilayahId;
            }

            if ($tipeBayar !== 'semua') {
                $sql .= " AND p.tipe_pembayaran_default = :tipe_bayar";
                $params['tipe_bayar'] = $tipeBayar;
            }

            $sql .= " GROUP BY p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp, p.alamat_lengkap,
                               p.tipe_pembayaran_default, p.plafon_piutang, p.total_piutang_berjalan, w.nama_wilayah, k.nama_karyawan";

            if ($statusPiutang === 'berhutang') {
                $sql .= " HAVING p.total_piutang_berjalan > 0";
            } elseif ($statusPiutang === 'over_plafon') {
                $sql .= " HAVING p.plafon_piutang > 0 AND p.total_piutang_berjalan > p.plafon_piutang";
            }

            $sql .= " ORDER BY p.total_piutang_berjalan DESC, p.nama_toko ASC";

            $stores = Database::fetchAll($sql, $params);
            $wilayahList = Database::fetchAll("SELECT id, nama_wilayah FROM public.wilayah ORDER BY nama_wilayah ASC");

            // Format JSON decode untuk frontend Alpine.js
            foreach ($stores as &$st) {
                $st['faktur_list'] = json_decode($st['rincian_faktur_json'] ?? '[]', true) ?: [];
                unset($st['rincian_faktur_json']);
            }
            unset($st);

            $this->view('reguler.tagihan', [
                'pageTitle' => 'Buku Piutang & Penagihan Toko',
                'pageSubtitle' => 'Rekapitulasi Kewajiban Tagihan Tempo per Toko Mitra & Penerbitan Surat Tagihan Resmi',
                'stores' => $stores,
                'wilayahList' => $wilayahList,
                'filter' => [
                    'q' => $q,
                    'wilayah_id' => $wilayahId,
                    'tipe_bayar' => $tipeBayar,
                    'status_piutang' => $statusPiutang
                ]
            ]);
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat Buku Piutang: ' . $e->getMessage());
            $this->redirect('/reguler');
        }
    }

    /**
     * Cetak Lembar Surat Tagihan / Invoice Rekap Jatuh Tempo Resmi (Format A4 Monokrom Korporat)
     */
    public function cetakInvoiceTagihan(): void
    {
        Auth::requirePermission(['orders.view_all', 'orders.view_assigned']);

        try {
            $pelangganId = $this->input('pelanggan_id');
            $orderIdsStr = (string)$this->input('order_ids', '');

            if (empty($pelangganId)) {
                $this->flashError('Toko pelanggan wajib dipilih.');
                $this->redirect('/reguler/tagihan');
                return;
            }

            $store = Database::fetchOne("
                SELECT p.*, w.nama_wilayah, COALESCE(k.nama_karyawan, 'Sales Kantor') as nama_sales
                FROM public.pelanggan p
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.id = :id
            ", ['id' => $pelangganId]);

            if (!$store) {
                $this->flashError('Data toko pelanggan tidak ditemukan.');
                $this->redirect('/reguler/tagihan');
                return;
            }

            // Ambil faktur yang ditagihkan
            $sqlInvoices = "
                SELECT pes.*,
                       (CURRENT_DATE - pes.tanggal_pesanan) as usia_hari_nota
                FROM public.pesanan pes
                WHERE pes.pelanggan_id = :pelanggan_id
                  AND pes.status_pemrosesan != 'dibatalkan'
                  AND pes.status_pembayaran != 'dibatalkan'
                  AND pes.is_tagihan = TRUE
                  AND pes.status_pembayaran != 'lunas'
            ";
            $params = ['pelanggan_id' => $pelangganId];

            if (!empty($orderIdsStr)) {
                $rawIds = array_filter(array_map('trim', explode(',', $orderIdsStr)));
                if (!empty($rawIds)) {
                    $inPlaceholders = [];
                    foreach ($rawIds as $idx => $oid) {
                        $pName = ":oid_{$idx}";
                        $inPlaceholders[] = $pName;
                        $params["oid_{$idx}"] = $oid;
                    }
                    $sqlInvoices .= " AND pes.id IN (" . implode(',', $inPlaceholders) . ")";
                }
            }

            $sqlInvoices .= " ORDER BY pes.tanggal_pesanan ASC";
            $invoices = Database::fetchAll($sqlInvoices, $params);

            if (empty($invoices)) {
                $this->flashError('Tidak ada faktur tempo belum lunas yang dipilih untuk toko ini.');
                $this->redirect('/reguler/tagihan');
                return;
            }

            $comp = CompanySetting::getAll();
            $logoSrc = PrintDocumentHelper::getLogoSrc($comp);

            $totalNetto = array_sum(array_column($invoices, 'total_netto'));
            $totalDibayar = array_sum(array_column($invoices, 'total_dibayar'));
            $totalSisaTagihan = array_sum(array_column($invoices, 'sisa_tagihan'));

            $noSuratTagihan = 'TAG-B2B-' . date('Ymd') . '-' . substr(strtoupper(md5($pelangganId . time())), 0, 4);

            $this->view('reguler.invoice_tagihan_pdf', [
                'pageTitle' => "Surat Rekap Tagihan #{$noSuratTagihan} - {$store['nama_toko']}",
                'store' => $store,
                'invoices' => $invoices,
                'comp' => $comp,
                'logoSrc' => $logoSrc,
                'noSuratTagihan' => $noSuratTagihan,
                'totalNetto' => $totalNetto,
                'totalDibayar' => $totalDibayar,
                'totalSisaTagihan' => $totalSisaTagihan,
                'tanggalCetak' => date('d/m/Y')
            ]);
        } catch (Throwable $e) {
            $this->flashError('Gagal mencetak tagihan: ' . $e->getMessage());
            $this->redirect('/reguler/tagihan');
        }
    }

    /**
     * Radar Early Warning Risiko Kredit Toko Grosir B2B
     */
    public function earlyWarning(): void
    {
        Auth::requirePermission(['orders.view_all', 'orders.view_assigned']);

        try {
            // 1. Toko Over-Plafon
            $overPlafonStores = Database::fetchAll("
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp,
                       p.plafon_piutang, p.total_piutang_berjalan,
                       (p.total_piutang_berjalan - p.plafon_piutang) as kelebihan_plafon,
                       w.nama_wilayah, k.nama_karyawan as nama_sales
                FROM public.pelanggan p
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.status_aktif = TRUE AND p.is_konsinyasi = FALSE
                  AND p.plafon_piutang > 0 AND p.total_piutang_berjalan > p.plafon_piutang
                ORDER BY (p.total_piutang_berjalan - p.plafon_piutang) DESC
            ");

            // 2. Toko Menumpuk Faktur Tempo (>= 2 Nota Gantung)
            $menumpukStores = Database::fetchAll("
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp,
                       p.tipe_pembayaran_default, p.total_piutang_berjalan,
                       COUNT(pes.id) as total_nota_gantung,
                       MIN(pes.tanggal_pesanan) as nota_tertua,
                       MAX(CURRENT_DATE - pes.tanggal_pesanan) as usia_nota_terlama,
                       w.nama_wilayah, k.nama_karyawan as nama_sales
                FROM public.pelanggan p
                JOIN public.pesanan pes ON (
                    pes.pelanggan_id = p.id 
                    AND pes.status_pemrosesan != 'dibatalkan' 
                    AND pes.status_pembayaran != 'dibatalkan'
                    AND pes.status_pembayaran != 'lunas'
                    AND pes.is_tagihan = TRUE
                )
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.status_aktif = TRUE AND p.is_konsinyasi = FALSE
                  AND pes.tipe_pembayaran = 'tempo_faktur'
                GROUP BY p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp,
                         p.tipe_pembayaran_default, p.total_piutang_berjalan, w.nama_wilayah, k.nama_karyawan
                HAVING COUNT(pes.id) >= 2
                ORDER BY COUNT(pes.id) DESC, p.total_piutang_berjalan DESC
            ");

            // 3. Toko Overdue Kalender (Tempo Tanggal yang Melewati Due Date)
            $overdueStores = Database::fetchAll("
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp,
                       pes.nomor_nota, pes.tanggal_pesanan, pes.tanggal_jatuh_tempo,
                       pes.sisa_tagihan, (CURRENT_DATE - pes.tanggal_jatuh_tempo) as hari_terlambat,
                       w.nama_wilayah, k.nama_karyawan as nama_sales
                FROM public.pesanan pes
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE pes.status_pemrosesan != 'dibatalkan'
                  AND pes.status_pembayaran != 'dibatalkan'
                  AND pes.status_pembayaran != 'lunas'
                  AND pes.is_tagihan = TRUE
                  AND pes.tipe_pembayaran = 'tempo_tanggal'
                  AND pes.tanggal_jatuh_tempo IS NOT NULL
                  AND pes.tanggal_jatuh_tempo < CURRENT_DATE
                  AND p.is_konsinyasi = FALSE
                ORDER BY (CURRENT_DATE - pes.tanggal_jatuh_tempo) DESC
            ");

            // 4. Toko Pasif Berhutang (> 30 Hari Tanpa Order Baru)
            $dormantStores = Database::fetchAll("
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp,
                       p.total_piutang_berjalan,
                       MAX(pes.tanggal_pesanan) as tanggal_order_terakhir,
                       (CURRENT_DATE - MAX(pes.tanggal_pesanan)) as hari_sejak_order_terakhir,
                       w.nama_wilayah, k.nama_karyawan as nama_sales
                FROM public.pelanggan p
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                LEFT JOIN public.pesanan pes ON (pes.pelanggan_id = p.id AND pes.status_pemrosesan != 'dibatalkan')
                WHERE p.status_aktif = TRUE AND p.is_konsinyasi = FALSE
                  AND p.total_piutang_berjalan > 0
                GROUP BY p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp,
                         p.total_piutang_berjalan, w.nama_wilayah, k.nama_karyawan
                HAVING (CURRENT_DATE - COALESCE(MAX(pes.tanggal_pesanan), p.dibuat_pada::date)) > 30
                ORDER BY (CURRENT_DATE - COALESCE(MAX(pes.tanggal_pesanan), p.dibuat_pada::date)) DESC
            ");

            $this->view('reguler.early_warning', [
                'pageTitle' => 'Early Warning Kredit Toko',
                'pageSubtitle' => 'Deteksi Dini Toko Over-Plafon, Faktur Menumpuk, Tagihan Overdue & Toko Pasif Berhutang',
                'overPlafonStores' => $overPlafonStores,
                'menumpukStores' => $menumpukStores,
                'overdueStores' => $overdueStores,
                'dormantStores' => $dormantStores
            ]);
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat Early Warning: ' . $e->getMessage());
            $this->redirect('/reguler');
        }
    }

    /**
     * Laporan Penjualan & Analitik Performa per Toko Grosir B2B
     */
    public function laporanToko(): void
    {
        Auth::requirePermission(['orders.view_all', 'orders.view_assigned']);

        try {
            $startDate = $this->input('start_date', date('Y-m-01'));
            $endDate = $this->input('end_date', date('Y-m-d'));
            $wilayahId = $this->input('wilayah_id', '');

            $sql = "
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp,
                       p.plafon_piutang, p.total_piutang_berjalan,
                       COALESCE(w.nama_wilayah, '—') as nama_wilayah,
                       COALESCE(k.nama_karyawan, '—') as nama_sales,
                       COUNT(pes.id) as total_orders,
                       COALESCE(SUM(pes.total_bruto), 0) as total_omzet_bruto,
                       COALESCE(SUM(pes.total_diskon), 0) as total_diskon,
                       COALESCE(SUM(pes.total_netto), 0) as total_omzet_netto,
                       COALESCE(SUM(pes.total_dibayar), 0) as total_terbayar,
                       COALESCE(SUM(pes.sisa_tagihan), 0) as sisa_tagihan_periode,
                       COALESCE(SUM(
                           (SELECT SUM(kuantitas_satuan_dasar) FROM public.item_pesanan ip WHERE ip.pesanan_id = pes.id)
                       ), 0) as total_pcs_terjual
                FROM public.pelanggan p
                JOIN public.pesanan pes ON pes.pelanggan_id = p.id
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.status_aktif = TRUE AND p.is_konsinyasi = FALSE
                  AND pes.status_pemrosesan != 'dibatalkan'
                  AND pes.status_pembayaran != 'dibatalkan'
                  AND pes.tanggal_pesanan BETWEEN :start AND :end
            ";

            $params = [
                'start' => $startDate,
                'end' => $endDate
            ];

            if (!empty($wilayahId)) {
                $sql .= " AND p.wilayah_id = :wilayah_id";
                $params['wilayah_id'] = $wilayahId;
            }

            $sql .= " GROUP BY p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp,
                               p.plafon_piutang, p.total_piutang_berjalan, w.nama_wilayah, k.nama_karyawan
                      ORDER BY total_omzet_netto DESC";

            $storeAnalytics = Database::fetchAll($sql, $params);
            $wilayahList = Database::fetchAll("SELECT id, nama_wilayah FROM public.wilayah ORDER BY nama_wilayah ASC");

            // Agregat Ringkasan Periode
            $totalOmzet = array_sum(array_column($storeAnalytics, 'total_omzet_netto'));
            $totalPcs = array_sum(array_column($storeAnalytics, 'total_pcs_terjual'));
            $totalTransaksi = array_sum(array_column($storeAnalytics, 'total_orders'));
            $avgOrderValue = $totalTransaksi > 0 ? round($totalOmzet / $totalTransaksi) : 0;

            $this->view('reguler.laporan_toko', [
                'pageTitle' => 'Laporan Penjualan per Toko B2B',
                'pageSubtitle' => 'Analitik Omzet, Frekuensi Pemesanan Grosir & Kontribusi Penjualan per Toko Mitra',
                'storeAnalytics' => $storeAnalytics,
                'wilayahList' => $wilayahList,
                'summary' => [
                    'total_omzet' => $totalOmzet,
                    'total_pcs' => $totalPcs,
                    'total_transaksi' => $totalTransaksi,
                    'avg_order_value' => $avgOrderValue,
                    'toko_aktif_belanja' => count($storeAnalytics)
                ],
                'filter' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'wilayah_id' => $wilayahId
                ]
            ]);
        } catch (Throwable $e) {
            $this->flashError('Gagal memuat Laporan Penjualan per Toko: ' . $e->getMessage());
            $this->redirect('/reguler');
        }
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\Format;
use App\Helpers\ActivityLog;
use Database;
use Throwable;

/**
 * app/Controllers/OwnerController.php
 * Pengendali Owner Executive Dashboard (Pusat Kendali Performa Bisnis & Finansial Perusahaan).
 * Fokus 100% pada performa perusahaan: Omzet, HPP, Laba Bersih, Modal Kerja, Produksi Pabrik, dan Distribusi.
 */
class OwnerController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission('owner.dashboard');
    }

    /**
     * Dashboard Utama Eksekutif
     */
    public function index(): void
    {
        try {
            // =========================================================================
            // 1. FILTER PERIODE DINAMIS
            // =========================================================================
            $preset = trim((string)$this->input('preset', 'this_month'));
            $inputStart = trim((string)$this->input('start_date', ''));
            $inputEnd = trim((string)$this->input('end_date', ''));
            $tab = trim((string)$this->input('tab', 'finance'));
            if (!in_array($tab, ['finance', 'sales', 'factory', 'consignment'], true)) {
                $tab = 'finance';
            }

            $today = date('Y-m-d');

            switch ($preset) {
                case 'today':
                    $startDate = $today;
                    $endDate = $today;
                    $periodLabel = 'Hari Ini (' . date('d M Y') . ')';
                    break;
                case '7days':
                    $startDate = date('Y-m-d', strtotime('-6 days'));
                    $endDate = $today;
                    $periodLabel = '7 Hari Terakhir (' . date('d M', strtotime($startDate)) . ' - ' . date('d M Y') . ')';
                    break;
                case 'last_month':
                    $startDate = date('Y-m-01', strtotime('first day of last month'));
                    $endDate = date('Y-m-t', strtotime('last day of last month'));
                    $periodLabel = 'Bulan Lalu (' . date('M Y', strtotime($startDate)) . ')';
                    break;
                case 'this_year':
                    $startDate = date('Y-01-01');
                    $endDate = $today;
                    $periodLabel = 'Tahun Ini (' . date('Y') . ')';
                    break;
                case 'custom':
                    $startDate = !empty($inputStart) ? $inputStart : date('Y-m-01');
                    $endDate = !empty($inputEnd) ? $inputEnd : $today;
                    $periodLabel = 'Periode ' . date('d M Y', strtotime($startDate)) . ' s/d ' . date('d M Y', strtotime($endDate));
                    break;
                case 'this_month':
                default:
                    $preset = 'this_month';
                    $startDate = date('Y-m-01');
                    $endDate = date('Y-m-t');
                    $periodLabel = 'Bulan Ini (' . date('F Y') . ')';
                    break;
            }

            // =========================================================================
            // 2. MATRIKS UTAMA: PROFITABILITAS, OMZET RIIL, HPP & LABA OPERASIONAL
            // =========================================================================

            // A. Omzet Penjualan Riil (Hanya pesanan valid bertagihan, bukan draf konsinyasi titip rak)
            $salesSummary = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(total_netto), 0) as total_omzet,
                    COALESCE(SUM(total_bruto), 0) as total_bruto,
                    COALESCE(SUM(total_diskon), 0) as total_diskon,
                    COUNT(id) as total_transaksi
                FROM public.pesanan
                WHERE tanggal_pesanan BETWEEN :start AND :end
                  AND status_pemrosesan != 'dibatalkan'
                  AND status_pembayaran != 'dibatalkan'
                  AND adalah_tagihan = TRUE
            ", ['start' => $startDate, 'end' => $endDate]) ?? [];

            $totalOmzet = (float)($salesSummary['total_omzet'] ?? 0);
            $totalBruto = (float)($salesSummary['total_bruto'] ?? 0);
            $totalDiskon = (float)($salesSummary['total_diskon'] ?? 0);
            $totalTransaksi = (int)($salesSummary['total_transaksi'] ?? 0);

            // B. HPP Barang Terjual (COGS)
            $cogsRow = Database::fetchOne("
                SELECT COALESCE(SUM(ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0)), 0) as total_hpp
                FROM public.item_pesanan ip
                JOIN public.pesanan p ON ip.pesanan_id = p.id
                LEFT JOIN public.item i ON ip.item_id = i.id
                WHERE p.tanggal_pesanan BETWEEN :start AND :end
                  AND p.status_pemrosesan != 'dibatalkan'
                  AND p.status_pembayaran != 'dibatalkan'
                  AND p.adalah_tagihan = TRUE
            ", ['start' => $startDate, 'end' => $endDate]) ?? [];
            $totalHpp = (float)($cogsRow['total_hpp'] ?? 0);

            // C. Laba Kotor (Gross Profit) & Margin
            $labaKotor = $totalOmzet - $totalHpp;
            $marginLabaKotor = ($totalOmzet > 0) ? round(($labaKotor / $totalOmzet) * 100, 1) : 0.0;

            // D. Beban Pengeluaran Operasional (Arus Kas Keluar)
            $expenseRow = Database::fetchOne("
                SELECT COALESCE(SUM(nominal), 0) as total_beban
                FROM public.arus_kas
                WHERE tanggal_transaksi BETWEEN :start AND :end
                  AND jenis_kas = 'keluar'
            ", ['start' => $startDate, 'end' => $endDate]) ?? [];
            $totalBebanOperasional = (float)($expenseRow['total_beban'] ?? 0);

            // Breakdown Top 5 Kategori Beban
            $expenseBreakdown = Database::fetchAll("
                SELECT kategori, COALESCE(SUM(nominal), 0) as total_nominal, COUNT(id) as total_tx
                FROM public.arus_kas
                WHERE tanggal_transaksi BETWEEN :start AND :end
                  AND jenis_kas = 'keluar'
                GROUP BY kategori
                ORDER BY total_nominal DESC
                LIMIT 5
            ", ['start' => $startDate, 'end' => $endDate]);

            // E. Estimasi Laba Bersih Operasional & Net Margin
            $labaBersih = $labaKotor - $totalBebanOperasional;
            $marginLabaBersih = ($totalOmzet > 0) ? round(($labaBersih / $totalOmzet) * 100, 1) : 0.0;

            // F. Omzet Hari Ini (Khusus untuk benchmark cepat)
            $omzetToday = (float)(Database::fetchOne("
                SELECT COALESCE(SUM(total_netto), 0) as total 
                FROM public.pesanan 
                WHERE tanggal_pesanan = CURRENT_DATE 
                  AND status_pemrosesan != 'dibatalkan'
                  AND status_pembayaran != 'dibatalkan'
                  AND adalah_tagihan = TRUE
            ")['total'] ?? 0);

            // =========================================================================
            // 3. NERACA MODAL KERJA & KESEHATAN KEUANGAN (CURRENT LIVE SNAPSHOT)
            // =========================================================================

            // A. Kas Cair & Rekening Bank
            $totalKasLikuid = (float)(Database::fetchOne("
                SELECT COALESCE(SUM(saldo_saat_ini), 0) as total 
                FROM public.akun_kas 
                WHERE status_aktif = TRUE
            ")['total'] ?? 0);

            $kasDetail = Database::fetchAll("
                SELECT nama_akun, tipe_akun, saldo_saat_ini
                FROM public.akun_kas
                WHERE status_aktif = TRUE
                ORDER BY saldo_saat_ini DESC
            ");

            // B. Total Piutang Usaha & Aging (Berdasarkan Jatuh Tempo Riil)
            $totalPiutang = (float)(Database::fetchOne("
                SELECT COALESCE(SUM(total_piutang_berjalan), 0) as total 
                FROM public.pelanggan 
                WHERE status_aktif = TRUE
            ")['total'] ?? 0);

            $agingSummary = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(CASE WHEN CURRENT_DATE <= COALESCE(tanggal_jatuh_tempo, tanggal_pesanan) THEN sisa_tagihan ELSE 0 END), 0) as piutang_lancar,
                    COALESCE(SUM(CASE WHEN CURRENT_DATE > COALESCE(tanggal_jatuh_tempo, tanggal_pesanan) AND CURRENT_DATE - COALESCE(tanggal_jatuh_tempo, tanggal_pesanan) <= 14 THEN sisa_tagihan ELSE 0 END), 0) as overdue_1_14,
                    COALESCE(SUM(CASE WHEN CURRENT_DATE - COALESCE(tanggal_jatuh_tempo, tanggal_pesanan) BETWEEN 15 AND 30 THEN sisa_tagihan ELSE 0 END), 0) as overdue_15_30,
                    COALESCE(SUM(CASE WHEN CURRENT_DATE - COALESCE(tanggal_jatuh_tempo, tanggal_pesanan) > 30 THEN sisa_tagihan ELSE 0 END), 0) as overdue_over_30,
                    COALESCE(SUM(sisa_tagihan), 0) as total_outstanding
                FROM public.pesanan
                WHERE status_pemrosesan != 'dibatalkan'
                  AND status_pembayaran != 'dibatalkan'
                  AND adalah_tagihan = TRUE
                  AND status_pembayaran != 'lunas'
            ") ?? [];

            // Top Toko dengan Piutang Terbesar
            $unpaidStoreList = Database::fetchAll("
                SELECT p.id, p.nama_toko, p.kode_pelanggan, p.nomor_whatsapp, p.nomor_whatsapp as nomor_telepon,
                       COALESCE(k.nama_karyawan, '—') as nama_sales,
                       SUM(pes.sisa_tagihan) as total_sisa_tagihan,
                       COUNT(pes.id) as total_nota_belum_lunas
                FROM public.pesanan pes
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                LEFT JOIN public.v_karyawan_info k ON pes.sales_driver_id = k.id
                WHERE pes.adalah_tagihan = TRUE 
                  AND pes.status_pembayaran != 'lunas' 
                  AND pes.status_pemrosesan != 'dibatalkan'
                  AND pes.status_pembayaran != 'dibatalkan'
                GROUP BY p.id, p.nama_toko, p.kode_pelanggan, p.nomor_whatsapp, k.nama_karyawan
                ORDER BY total_sisa_tagihan DESC
                LIMIT 5
            ");

            // C. Valuasi Persediaan Total (HPP Gudang & Rak Konsinyasi)
            $stokGudangBahan = (float)(Database::fetchOne("
                SELECT COALESCE(SUM(stok_fisik_saat_ini * harga_pokok_pembelian), 0) as total
                FROM public.item
                WHERE tipe_item = 'bahan_mentah' AND status_aktif = TRUE
            ")['total'] ?? 0);

            $stokGudangKemas = (float)(Database::fetchOne("
                SELECT COALESCE(SUM(stok_fisik_saat_ini * harga_pokok_pembelian), 0) as total
                FROM public.item
                WHERE tipe_item = 'bahan_kemas' AND status_aktif = TRUE
            ")['total'] ?? 0);

            $stokGudangJadi = (float)(Database::fetchOne("
                SELECT COALESCE(SUM(stok_fisik_saat_ini * harga_pokok_pembelian), 0) as total
                FROM public.item
                WHERE tipe_item = 'barang_jadi' AND status_aktif = TRUE
            ")['total'] ?? 0);

            $stokRakKonsinyasi = (float)(Database::fetchOne("
                SELECT COALESCE(SUM(skt.stok_titip_saat_ini * COALESCE(i.harga_pokok_pembelian, 0)), 0) as total
                FROM public.stok_konsinyasi_toko skt
                JOIN public.item i ON skt.item_id = i.id
                WHERE skt.stok_titip_saat_ini > 0
            ")['total'] ?? 0);

            $totalValuasiPersediaan = $stokGudangBahan + $stokGudangKemas + $stokGudangJadi + $stokRakKonsinyasi;

            // D. Kewajiban Hutang Pemasok (Accounts Payable)
            $totalHutangPemasok = (float)(Database::fetchOne("
                SELECT COALESCE(SUM(total_biaya), 0) as total
                FROM public.pembelian
                WHERE status_pembayaran = 'belum_lunas'
            ")['total'] ?? 0);

            // E. Modal Kerja Bersih (Net Working Capital)
            $netWorkingCapital = ($totalKasLikuid + $totalPiutang + $totalValuasiPersediaan) - $totalHutangPemasok;

            // =========================================================================
            // 4. DISTRIBUSI MULTI-CHANNEL PENJUALAN & TOP SKU PRODUK
            // =========================================================================

            // Breakdown Omzet per Channel (Partisi Ritel POS, Grosir Direct & Konsinyasi)
            $channelBreakdown = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(CASE 
                        WHEN pl.is_konsinyasi = TRUE OR pes.tipe_pembayaran = 'konsinyasi' THEN pes.total_netto 
                        ELSE 0 
                    END), 0) as omzet_konsinyasi,
                    COALESCE(SUM(CASE 
                        WHEN (pl.is_konsinyasi = FALSE OR pl.is_konsinyasi IS NULL) 
                             AND pes.tipe_pembayaran != 'konsinyasi'
                             AND (pl.kode_pelanggan = 'CUST-001' OR pes.catatan ILIKE '%POS%' OR pes.catatan ILIKE '%kasir%' OR COALESCE(pes.uang_diterima, 0) > 0)
                        THEN pes.total_netto 
                        ELSE 0 
                    END), 0) as omzet_pos,
                    COALESCE(SUM(CASE 
                        WHEN (pl.is_konsinyasi = FALSE OR pl.is_konsinyasi IS NULL) 
                             AND pes.tipe_pembayaran != 'konsinyasi'
                             AND (pl.kode_pelanggan != 'CUST-001' OR pl.kode_pelanggan IS NULL)
                             AND (pes.catatan NOT ILIKE '%POS%' AND pes.catatan NOT ILIKE '%kasir%' OR pes.catatan IS NULL)
                             AND COALESCE(pes.uang_diterima, 0) = 0
                        THEN pes.total_netto 
                        ELSE 0 
                    END), 0) as omzet_grosir
                FROM public.pesanan pes
                LEFT JOIN public.pelanggan pl ON pes.pelanggan_id = pl.id
                WHERE pes.tanggal_pesanan BETWEEN :start AND :end
                  AND pes.status_pemrosesan != 'dibatalkan'
                  AND pes.status_pembayaran != 'dibatalkan'
                  AND pes.adalah_tagihan = TRUE
            ", ['start' => $startDate, 'end' => $endDate]) ?? [];

            // Top 5 SKU Terlaris & Margin
            $topSkuList = Database::fetchAll("
                SELECT i.nama_item, i.kode_sku, i.satuan_dasar,
                       SUM(ip.kuantitas_satuan_dasar) as total_qty,
                       SUM(ip.subtotal) as total_omzet,
                       SUM(ip.subtotal - (ip.kuantitas_satuan_dasar * COALESCE(ip.harga_pokok_satuan, i.harga_pokok_pembelian, 0))) as laba_kotor_sku
                FROM public.item_pesanan ip
                JOIN public.pesanan p ON ip.pesanan_id = p.id
                JOIN public.item i ON ip.item_id = i.id
                WHERE p.tanggal_pesanan BETWEEN :start AND :end
                  AND p.status_pemrosesan != 'dibatalkan'
                  AND p.status_pembayaran != 'dibatalkan'
                  AND p.adalah_tagihan = TRUE
                GROUP BY i.id, i.nama_item, i.kode_sku, i.satuan_dasar
                ORDER BY total_omzet DESC
                LIMIT 5
            ", ['start' => $startDate, 'end' => $endDate]);

            // =========================================================================
            // 5. KINERJA PRODUKSI PABRIK KERENSNACK (MANUFACTURING KPI)
            // =========================================================================
            $productionSummary = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(kuantitas_pcs), 0) as total_pcs,
                    COALESCE(SUM(kuantitas_bal), 0) as total_bal,
                    COALESCE(SUM(lembur_pcs), 0) as total_lembur_pcs,
                    COALESCE(SUM(total_upah_didapat), 0) as total_upah_borongan,
                    COUNT(DISTINCT tanggal) as hari_produksi_aktif,
                    COUNT(DISTINCT karyawan_id) as total_pekerja_aktif,
                    COUNT(DISTINCT item_id) as total_varian_sku
                FROM public.produksi_harian
                WHERE tanggal BETWEEN :start AND :end
            ", ['start' => $startDate, 'end' => $endDate]) ?? [];

            $totalPcs = (int)($productionSummary['total_pcs'] ?? 0);
            $totalUpah = (float)($productionSummary['total_upah_borongan'] ?? 0);
            $totalPekerjaAktif = (int)($productionSummary['total_pekerja_aktif'] ?? 0);
            $rataRataOutputPerPekerja = ($totalPekerjaAktif > 0 && $totalPcs > 0) ? round($totalPcs / $totalPekerjaAktif) : 0;
            $rataRataUpahPerPcs = ($totalPcs > 0) ? round($totalUpah / $totalPcs, 1) : 0.0;

            $productionSummary['total_pekerja_aktif'] = $totalPekerjaAktif;
            $productionSummary['rata_rata_output_per_pekerja'] = $rataRataOutputPerPekerja;
            $productionSummary['rata_rata_upah_per_pcs'] = $rataRataUpahPerPcs;

            // Top 5 Produk yang Diproduksi
            $topProducedItems = Database::fetchAll("
                SELECT i.nama_item, i.kode_sku,
                       SUM(ph.kuantitas_pcs) as total_pcs,
                       SUM(ph.kuantitas_bal) as total_bal,
                       SUM(ph.total_upah_didapat) as total_upah
                FROM public.produksi_harian ph
                JOIN public.item i ON ph.item_id = i.id
                WHERE ph.tanggal BETWEEN :start AND :end
                GROUP BY i.id, i.nama_item, i.kode_sku
                ORDER BY total_pcs DESC
                LIMIT 5
            ", ['start' => $startDate, 'end' => $endDate]);

            // =========================================================================
            // 6. MITRA KONSINYASI, SALES LEADERBOARD & RETUR RUSAK
            // =========================================================================

            // A. Top 5 Toko Konsinyasi Kontributor Omzet
            $topStores = Database::fetchAll("
                SELECT p.id, p.nama_toko, p.kode_pelanggan,
                       COALESCE(SUM(kk.total_laku_nominal), 0) as total_omzet,
                       COUNT(kk.id) as total_kunjungan
                FROM public.pelanggan p
                JOIN public.kunjungan_konsinyasi kk ON kk.pelanggan_id = p.id
                WHERE kk.tanggal_kunjungan BETWEEN :start AND :end
                  AND p.is_konsinyasi = TRUE 
                  AND p.status_aktif = TRUE
                GROUP BY p.id, p.nama_toko, p.kode_pelanggan
                HAVING SUM(kk.total_laku_nominal) > 0
                ORDER BY total_omzet DESC
                LIMIT 5
            ", ['start' => $startDate, 'end' => $endDate]);

            // B. Leaderboard Sales (Kombinasi Omzet Binaan & Tagihan Terbayar)
            $salesListRaw = Database::fetchAll("
                SELECT k.id as sales_id, k.nama_karyawan, k.nomor_telepon
                FROM public.v_karyawan_info k
                WHERE k.posisi = 'sales' AND k.status_aktif = TRUE
                ORDER BY k.nama_karyawan ASC
            ");

            $salesLeaderboard = [];
            foreach ($salesListRaw as $s) {
                $tokoCount = (int)(Database::fetchOne("
                    SELECT COUNT(id) as c FROM public.pelanggan 
                    WHERE sales_driver_id = :sid AND is_konsinyasi = TRUE AND status_aktif = TRUE
                ", ['sid' => $s['sales_id']])['c'] ?? 0);

                // Hitung omzet tertagih yang dibayar pada pesanan milik binaan sales ini
                $omzetRow = Database::fetchOne("
                    SELECT COALESCE(SUM(pes.total_dibayar), 0) as paid_omzet
                    FROM public.pesanan pes
                    JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                    WHERE p.sales_driver_id = :sid
                      AND pes.adalah_tagihan = TRUE
                      AND pes.status_pemrosesan != 'dibatalkan'
                      AND pes.status_pembayaran != 'dibatalkan'
                      AND pes.tanggal_pesanan BETWEEN :start AND :end
                ", ['sid' => $s['sales_id'], 'start' => $startDate, 'end' => $endDate]);

                $totalPaid = (float)($omzetRow['paid_omzet'] ?? 0);
                $tierRpc = Database::fetchOne("
                    SELECT public.fn_hitung_tier_komisi_sales(:omzet) as r
                ", ['omzet' => $totalPaid])['r'] ?? '{}';
                $tierInfo = json_decode((string)$tierRpc, true) ?? [];

                $salesLeaderboard[] = [
                    'sales_id' => $s['sales_id'],
                    'nama_karyawan' => $s['nama_karyawan'],
                    'nomor_telepon' => $s['nomor_telepon'],
                    'total_toko_binaan' => $tokoCount,
                    'total_omzet_laku' => $totalPaid,
                    'persentase_komisi' => (float)($tierInfo['persentase'] ?? 0),
                    'estimasi_komisi_rp' => (float)($tierInfo['nominal_komisi'] ?? 0),
                    'nama_tier' => $tierInfo['nama_tier'] ?? 'Tier 1'
                ];
            }
            usort($salesLeaderboard, fn($a, $b) => $b['total_omzet_laku'] <=> $a['total_omzet_laku']);

            // C. Early Warning: Toko Konsinyasi > 14 Hari Tidak Dikunjungi / Opname (CTE Query Optimal)
            $overdueStores = Database::fetchAll("
                WITH last_opname AS (
                    SELECT pelanggan_id, MAX(terakhir_opname_pada) as terakhir_opname
                    FROM public.stok_konsinyasi_toko
                    GROUP BY pelanggan_id
                )
                SELECT p.id, p.nama_toko, p.kode_pelanggan, p.nomor_whatsapp, p.nomor_whatsapp as nomor_telepon, p.alamat_lengkap,
                       COALESCE(k.nama_karyawan, '—') as nama_sales,
                       lo.terakhir_opname,
                       CASE 
                           WHEN lo.terakhir_opname IS NULL THEN 999
                           ELSE EXTRACT(DAY FROM NOW() - lo.terakhir_opname)::int
                       END as hari_tidak_dikunjungi
                FROM public.pelanggan p
                LEFT JOIN last_opname lo ON lo.pelanggan_id = p.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                  AND (lo.terakhir_opname IS NULL OR lo.terakhir_opname < NOW() - INTERVAL '14 days')
                ORDER BY hari_tidak_dikunjungi DESC, p.nama_toko ASC
                LIMIT 8
            ");

            // D. Laporan Kerugian Retur Rusak (BS) Konsinyasi
            $lossReport = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(rkk.nilai_kerugian_rusak), 0) as total_kerugian_rusak,
                    COALESCE(SUM(rkk.retur_rusak), 0) as total_pcs_rusak
                FROM public.rincian_kunjungan_konsinyasi rkk
                JOIN public.kunjungan_konsinyasi kk ON rkk.kunjungan_id = kk.id
                WHERE kk.tanggal_kunjungan BETWEEN :start AND :end
            ", ['start' => $startDate, 'end' => $endDate]) ?? [];

            $topDamagedItems = Database::fetchAll("
                SELECT i.nama_item, i.kode_sku, i.satuan_dasar,
                       SUM(rkk.retur_rusak) as total_pcs_rusak,
                       SUM(rkk.nilai_kerugian_rusak) as total_nominal_kerugian
                FROM public.rincian_kunjungan_konsinyasi rkk
                JOIN public.item i ON rkk.item_id = i.id
                JOIN public.kunjungan_konsinyasi kk ON rkk.kunjungan_id = kk.id
                WHERE kk.tanggal_kunjungan BETWEEN :start AND :end
                GROUP BY i.nama_item, i.kode_sku, i.satuan_dasar
                HAVING SUM(rkk.retur_rusak) > 0
                ORDER BY total_nominal_kerugian DESC
                LIMIT 5
            ", ['start' => $startDate, 'end' => $endDate]);

            // =========================================================================
            // 7. RENDER VIEW EXECUTIVE
            // =========================================================================
            $this->view('owner.index', [
                'pageTitle' => 'Owner Executive Dashboard',
                'pageSubtitle' => 'Pusat Analisis Performa Finansial & Bisnis Perusahaan',
                // Filter
                'preset' => $preset,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'periodLabel' => $periodLabel,
                'activeTab' => $tab,
                // Finansial & Laba Rugi
                'omzetToday' => $omzetToday,
                'totalOmzet' => $totalOmzet,
                'totalBruto' => $totalBruto,
                'totalDiskon' => $totalDiskon,
                'totalTransaksi' => $totalTransaksi,
                'totalHpp' => $totalHpp,
                'labaKotor' => $labaKotor,
                'marginLabaKotor' => $marginLabaKotor,
                'totalBebanOperasional' => $totalBebanOperasional,
                'expenseBreakdown' => $expenseBreakdown,
                'labaBersih' => $labaBersih,
                'marginLabaBersih' => $marginLabaBersih,
                // Neraca Modal Kerja
                'totalKasLikuid' => $totalKasLikuid,
                'kasDetail' => $kasDetail,
                'totalPiutang' => $totalPiutang,
                'agingSummary' => $agingSummary,
                'unpaidStoreList' => $unpaidStoreList,
                'stokGudangBahan' => $stokGudangBahan,
                'stokGudangKemas' => $stokGudangKemas,
                'stokGudangJadi' => $stokGudangJadi,
                'stokRakKonsinyasi' => $stokRakKonsinyasi,
                'totalValuasiPersediaan' => $totalValuasiPersediaan,
                'totalHutangPemasok' => $totalHutangPemasok,
                'netWorkingCapital' => $netWorkingCapital,
                // Multi-Channel & Top SKU
                'channelBreakdown' => $channelBreakdown,
                'topSkuList' => $topSkuList,
                // Pabrik & Produksi
                'productionSummary' => $productionSummary,
                'totalPekerjaAktif' => $totalPekerjaAktif,
                'rataRataOutputPerPekerja' => $rataRataOutputPerPekerja,
                'rataRataUpahPerPcs' => $rataRataUpahPerPcs,
                'topProducedItems' => $topProducedItems,
                // Konsinyasi & Sales
                'topStores' => $topStores,
                'salesLeaderboard' => $salesLeaderboard,
                'overdueStores' => $overdueStores,
                'lossReport' => $lossReport,
                'topDamagedItems' => $topDamagedItems,
            ]);

        } catch (Throwable $e) {
            error_log("OwnerController index error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            $this->flashError("Gagal memuat dashboard eksekutif: " . $e->getMessage());
            $this->redirect('/');
        }
    }
}

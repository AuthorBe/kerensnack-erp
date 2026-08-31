<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\Format;
use App\Helpers\ActivityLog;
use App\Core\Router;
use Database;
use Throwable;

/**
 * app/Controllers/ConsignmentController.php
 * Pengendali Modul Titip Jual (Konsinyasi) Rak Toko: Opname Mingguan & Rekap Tagihan Penjualan.
 */
class ConsignmentController extends Controller
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Halaman Utama Konsinyasi Ã¢â‚¬â€ Otomatis sesuai Role:
     * Admin/Owner/Developer Ã¢â€ â€™ Portal Admin 5-Tab
     * Sales/Driver          Ã¢â€ â€™ Dashboard Toko Binaan
     */
    public function index(): void
    {
        // Ã¢â€â‚¬Ã¢â€â‚¬ BRANCH: Sales / Driver Ã¢â€ â€™ Tampilkan Dashboard Sales Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
        if (!Auth::isAdmin() && !Auth::isOwner() && !Auth::isDeveloper()) {
            $this->salesDashboard();
            return;
        }

        // Ã¢â€â‚¬Ã¢â€â‚¬ BRANCH: Admin / Owner / Developer Ã¢â€ â€™ Portal Admin 5-Tab Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
        try {
            $selectedStoreId = (string)$this->input('pelanggan_id', '');
            $startDate = (string)$this->input('start_date', date('Y-m-01'));
            $endDate = (string)$this->input('end_date', date('Y-m-d'));
            $activeTab = (string)$this->input('tab', 'dashboard');

            // 1. Ambil daftar semua toko konsinyasi aktif + Sales Pemegang
            $consignmentStores = Database::fetchAll("
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp, p.nomor_telepon, p.alamat_lengkap,
                       p.total_piutang_berjalan, p.plafon_piutang, p.sales_driver_id,
                       k.nama_karyawan as nama_sales,
                       w.nama_wilayah as rute,
                       (SELECT MAX(skt.terakhir_opname_pada) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id) as terakhir_opname,
                       (SELECT COUNT(*) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id AND skt.stok_titip_saat_ini > 0) as total_sku_titip,
                       (SELECT COALESCE(SUM(skt.stok_titip_saat_ini), 0) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id) as total_pcs_titip
                FROM public.pelanggan p
                LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
                LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                ORDER BY p.nama_toko ASC
            ");

            if (empty($selectedStoreId) && !empty($consignmentStores)) {
                $selectedStoreId = $consignmentStores[0]['id'];
            }

            // 2. Metrik Finansial Konsinyasi Global
            $totalStores = count($consignmentStores);
            $totalPcsTitip = (int)(Database::fetchOne("
                SELECT COALESCE(SUM(stok_titip_saat_ini), 0) as total 
                FROM public.stok_konsinyasi_toko skt
                JOIN public.pelanggan p ON skt.pelanggan_id = p.id
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
            ")['total'] ?? 0);

            $totalLakuBulanIni = (float)(Database::fetchOne("
                SELECT COALESCE(SUM(total_laku_nominal), 0) as total 
                FROM public.kunjungan_konsinyasi 
                WHERE tanggal_kunjungan >= DATE_TRUNC('month', CURRENT_DATE)
            ")['total'] ?? 0);

            $totalPiutangKonsinyasi = (float)(Database::fetchOne("
                SELECT COALESCE(SUM(sisa_tagihan), 0) as total 
                FROM public.pesanan 
                WHERE tipe_pembayaran = 'konsinyasi' AND adalah_tagihan = TRUE AND status_pembayaran != 'lunas' AND status_pembayaran != 'dibatalkan'
            ")['total'] ?? 0);

            // 3. Saldo Stok di Seluruh Rak Toko (Layar B1)
            $shelfStocks = Database::fetchAll("
                SELECT skt.id, skt.pelanggan_id, skt.item_id, skt.stok_titip_saat_ini, skt.terakhir_opname_pada,
                       i.nama_item, i.kode_sku, i.satuan_dasar, p.nama_toko, p.kode_pelanggan, p.sales_driver_id,
                       k.nama_karyawan as nama_sales
                FROM public.stok_konsinyasi_toko skt
                JOIN public.item i ON skt.item_id = i.id
                JOIN public.pelanggan p ON skt.pelanggan_id = p.id
                LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
                ORDER BY p.nama_toko ASC, (skt.stok_titip_saat_ini > 0) DESC, i.nama_item ASC
            ");

            // 4. Daftar Pengiriman Berjalan (Layar B3)
            $deliveries = Database::fetchAll("
                SELECT sj.id as surat_jalan_id, sj.nomor_surat_jalan, sj.status_surat_jalan, sj.dibuat_pada, sj.waktu_berangkat, sj.waktu_sampai,
                       pes.id as pesanan_id, pes.nomor_nota, pes.catatan,
                       p.nama_toko, p.kode_pelanggan,
                       COALESCE(k.nama_karyawan, 'Belum Di-assign') as nama_sales,
                       COUNT(ip.id) as total_sku,
                       COALESCE(SUM(ip.kuantitas_satuan_dasar), 0) as total_pcs
                FROM public.surat_jalan sj
                JOIN public.pesanan pes ON sj.pesanan_id = pes.id
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                LEFT JOIN public.karyawan k ON sj.sales_driver_id = k.id
                LEFT JOIN public.item_pesanan ip ON ip.pesanan_id = pes.id
                WHERE pes.tipe_pembayaran = 'konsinyasi' AND pes.adalah_tagihan = FALSE
                GROUP BY sj.id, sj.nomor_surat_jalan, sj.status_surat_jalan, sj.dibuat_pada, sj.waktu_berangkat, sj.waktu_sampai, pes.id, pes.nomor_nota, pes.catatan, p.nama_toko, p.kode_pelanggan, k.nama_karyawan
                ORDER BY sj.dibuat_pada DESC
                LIMIT 50
            ");

            // 5. Daftar Piutang & Faktur Konsinyasi Belum Lunas (Layar B4)
            $unpaidInvoices = Database::fetchAll("
                SELECT pes.id as pesanan_id, pes.nomor_nota, pes.tanggal_pesanan, pes.total_netto, pes.total_dibayar, pes.sisa_tagihan,
                       pes.status_pembayaran, pes.catatan,
                       p.id as pelanggan_id, p.nama_toko, p.kode_pelanggan, p.nomor_whatsapp,
                       k.nama_karyawan as nama_sales
                FROM public.pesanan pes
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                LEFT JOIN public.karyawan k ON pes.sales_driver_id = k.id
                WHERE pes.tipe_pembayaran = 'konsinyasi'
                  AND pes.adalah_tagihan = TRUE
                  AND pes.status_pembayaran != 'lunas'
                  AND pes.status_pembayaran != 'dibatalkan'
                ORDER BY pes.tanggal_pesanan DESC, pes.dibuat_pada DESC
            ");

            // 6. Riwayat Kunjungan Opname Konsinyasi Seluruh Sales (Layar B5)
            $recentVisits = Database::fetchAll("
                SELECT kk.id, kk.nomor_kunjungan, kk.tanggal_kunjungan, kk.total_laku_nominal, kk.catatan,
                       p.nama_toko, p.kode_pelanggan, 
                       COALESCE(peng.nama_lengkap, k.nama_karyawan, 'Petugas ERP') as sales_driver,
                       pes.nomor_nota as nota_faktur, pes.status_pembayaran,
                       (SELECT COUNT(*) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_sku_diperiksa,
                       (SELECT COALESCE(SUM(jumlah_laku_terjual), 0) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_qty_laku,
                       (SELECT COALESCE(SUM(retur_bagus), 0) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_qty_retur_bagus,
                       (SELECT COALESCE(SUM(retur_rusak), 0) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_qty_retur_rusak,
                       (SELECT COALESCE(SUM(nilai_kerugian_rusak), 0) FROM public.rincian_kunjungan_konsinyasi rkk WHERE rkk.kunjungan_id = kk.id) as total_kerugian_rusak
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.pengguna peng ON kk.dibuat_oleh = peng.id
                LEFT JOIN public.karyawan k ON kk.sales_driver_id = k.id
                LEFT JOIN public.pesanan pes ON kk.pesanan_id = pes.id
                ORDER BY kk.tanggal_kunjungan DESC, kk.dibuat_pada DESC
                LIMIT 50
            ");

            // 7. Master Data Karyawan Sales (Penanggung Jawab Toko Binaan)
            $drivers = Database::fetchAll("
                SELECT id, nama_karyawan, nomor_telepon, posisi 
                FROM public.karyawan 
                WHERE posisi IN ('sales', 'sales_driver') AND status_aktif = TRUE 
                ORDER BY (posisi = 'sales') DESC, nama_karyawan ASC
            ");

            $cashAccounts = Database::fetchAll("
                SELECT id, nama_akun, tipe_akun, saldo_saat_ini 
                FROM public.akun_kas 
                WHERE status_aktif = TRUE 
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            $billingReport = $this->fetchBillingReportData($selectedStoreId, $startDate, $endDate);

            $this->view('consignment.index', [
                'pageTitle' => 'Konsinyasi',
                'pageSubtitle' => 'Saldo Rak, Assignment Sales, Monitoring Kiriman & Penagihan Piutang',
                'consignmentStores' => $consignmentStores,
                'selectedStoreId' => $selectedStoreId,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'activeTab' => $activeTab,
                'totalStores' => $totalStores,
                'totalPcsTitip' => $totalPcsTitip,
                'totalLakuBulanIni' => $totalLakuBulanIni,
                'totalPiutangKonsinyasi' => $totalPiutangKonsinyasi,
                'shelfStocks' => $shelfStocks,
                'deliveries' => $deliveries,
                'unpaidInvoices' => $unpaidInvoices,
                'recentVisits' => $recentVisits,
                'drivers' => $drivers,
                'cashAccounts' => $cashAccounts,
                'billingReport' => $billingReport,
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    /**
     * Dashboard Sales Mobile (dipanggil dari index() berdasarkan role)
     */
    private function salesDashboard(): void
    {
        try {
            $currentUser = Auth::user();
            $driverId = $currentUser['karyawan_id'] ?? null;
            
            // 1. Ambil daftar toko konsinyasi binaan sales yang login
            $queryStores = "
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp, p.nomor_telepon, p.alamat_lengkap,
                       p.sales_driver_id, k.nama_karyawan as nama_sales,
                       (SELECT MAX(skt.terakhir_opname_pada) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id) as terakhir_opname,
                       (SELECT COUNT(*) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id AND skt.stok_titip_saat_ini > 0) as total_sku_titip,
                       (SELECT COALESCE(SUM(skt.stok_titip_saat_ini), 0) FROM public.stok_konsinyasi_toko skt WHERE skt.pelanggan_id = p.id) as total_pcs_titip
                FROM public.pelanggan p
                LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
                WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
            ";

            $paramsStores = [];
            if ($driverId) {
                $queryStores .= " AND p.sales_driver_id = :driver_id";
                $paramsStores['driver_id'] = $driverId;
            }

            $queryStores .= " ORDER BY p.nama_toko ASC";
            $stores = Database::fetchAll($queryStores, $paramsStores);

            // 2. Ambil pengiriman yang sedang berjalan menuju toko binaan sales
            $queryDeliveries = "
                SELECT sj.id as surat_jalan_id, sj.nomor_surat_jalan, sj.status_surat_jalan, sj.waktu_berangkat,
                       pes.id as pesanan_id, pes.nomor_nota, pes.pelanggan_id,
                       p.nama_toko,
                       COUNT(ip.id) as total_item_count,
                       COALESCE(SUM(ip.kuantitas_satuan_dasar), 0) as total_pcs
                FROM public.surat_jalan sj
                JOIN public.pesanan pes ON sj.pesanan_id = pes.id
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                LEFT JOIN public.item_pesanan ip ON ip.pesanan_id = pes.id
                WHERE sj.status_surat_jalan = 'sedang_dikirim'
                  AND pes.tipe_pembayaran = 'konsinyasi'
            ";

            $paramsDeliv = [];
            if ($driverId) {
                $queryDeliveries .= " AND (p.sales_driver_id = :driver_id OR sj.sales_driver_id = :driver_id2)";
                $paramsDeliv['driver_id'] = $driverId;
                $paramsDeliv['driver_id2'] = $driverId;
            }

            $queryDeliveries .= " GROUP BY sj.id, sj.nomor_surat_jalan, sj.status_surat_jalan, sj.waktu_berangkat, pes.id, pes.nomor_nota, pes.pelanggan_id, p.nama_toko ORDER BY sj.waktu_berangkat DESC";
            $incomingDeliveries = Database::fetchAll($queryDeliveries, $paramsDeliv);

            // 3. Hitung Omzet Laku Bulan Ini & Estimasi Komisi
            $totalOmzetBinaan = 0.0;
            $commissionRate = 5.0;

            if ($driverId) {
                $driverRow = Database::fetchOne("
                    SELECT persentase_komisi_sales 
                    FROM public.karyawan 
                    WHERE id = :id
                ", ['id' => $driverId]);

                if (!empty($driverRow['persentase_komisi_sales']) && (float)$driverRow['persentase_komisi_sales'] > 0) {
                    $commissionRate = (float)$driverRow['persentase_komisi_sales'];
                }

                $totalOmzetBinaan = (float)(Database::fetchOne("
                    SELECT COALESCE(SUM(kk.total_laku_nominal), 0) as total
                    FROM public.kunjungan_konsinyasi kk
                    JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                    WHERE p.sales_driver_id = :d
                      AND kk.tanggal_kunjungan >= DATE_TRUNC('month', CURRENT_DATE)
                ", ['d' => $driverId])['total'] ?? 0);
            } else {
                $totalOmzetBinaan = (float)(Database::fetchOne("
                    SELECT COALESCE(SUM(total_laku_nominal), 0) as total
                    FROM public.kunjungan_konsinyasi
                    WHERE tanggal_kunjungan >= DATE_TRUNC('month', CURRENT_DATE)
                ")['total'] ?? 0);
            }

            $estimasiKomisi = ($totalOmzetBinaan * $commissionRate) / 100.0;

            $this->view('consignment.sales.index', [
                'pageTitle' => 'Konsinyasi',
                'pageSubtitle' => 'Daftar Toko Binaan, Komisi & Pengiriman Masuk',
                'stores' => $stores,
                'incomingDeliveries' => $incomingDeliveries,
                'totalOmzetBinaan' => $totalOmzetBinaan,
                'commissionRate' => $commissionRate,
                'estimasiKomisi' => $estimasiKomisi,
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    /**
     * Layar B2: Assignment Toko ke Sales-Driver (Single & Bulk)
     */
    public function assignDriver(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/consignment?tab=assignment');
            return;
        }

        $storeIds = (array)$this->input('store_ids', []);
        $singleStoreId = (string)$this->input('pelanggan_id', '');
        $driverId = (string)$this->input('sales_driver_id', '');

        if (!empty($singleStoreId)) {
            $storeIds[] = $singleStoreId;
        }

        $storeIds = array_filter(array_unique($storeIds));

        if (empty($storeIds)) {
            $this->flashError('Pilih minimal satu toko konsinyasi.');
            $this->redirect('/consignment?tab=assignment');
            return;
        }

        $driverUuid = !empty($driverId) ? $driverId : null;

        try {
            $count = 0;
            foreach ($storeIds as $sid) {
                Database::execute("
                    UPDATE public.pelanggan 
                    SET sales_driver_id = :d, diubah_pada = NOW() 
                    WHERE id = :c
                ", ['d' => $driverUuid, 'c' => $sid]);
                $count++;
            }

            $driverName = 'Tidak Ada (Unassigned)';
            if ($driverUuid) {
                $driverName = Database::fetchOne("SELECT nama_karyawan FROM public.karyawan WHERE id = :id", ['id' => $driverUuid])['nama_karyawan'] ?? 'Sales';
            }

            ActivityLog::log(
                'master_data',
                'UPDATE',
                "Admin meng-assign {$count} toko konsinyasi ke sales: {$driverName}.",
                'pelanggan',
                $storeIds[0] ?? null
            );

            $this->flashSuccess("Berhasil meng-assign {$count} toko konsinyasi ke {$driverName}!");
            $this->redirect('/consignment?tab=assignment');

        } catch (Throwable $e) {
            $this->flashError('Gagal meng-assign toko ke sales: ' . $e->getMessage());
            $this->redirect('/consignment?tab=assignment');
        }
    }

    /**
     * Layar B3: Batalkan Draft Pengiriman
     */
    public function cancelDelivery(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/consignment?tab=deliveries');
            return;
        }

        $suratJalanId = (string)$this->input('surat_jalan_id');
        $alasan = trim((string)$this->input('alasan', 'Dibatalkan oleh Admin'));

        if (empty($suratJalanId)) {
            $this->flashError('Surat jalan tidak ditemukan.');
            $this->redirect('/consignment?tab=deliveries');
            return;
        }

        try {
            $sj = Database::fetchOne("
                SELECT sj.*, p.nama_toko 
                FROM public.surat_jalan sj
                JOIN public.pesanan pes ON sj.pesanan_id = pes.id
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                WHERE sj.id = :id AND sj.status_surat_jalan = 'draf_n8n'
            ", ['id' => $suratJalanId]);

            if (!$sj) {
                $this->flashError('Pengiriman tidak dapat dibatalkan (hanya berstatus draft yang bisa dibatalkan).');
                $this->redirect('/consignment?tab=deliveries');
                return;
            }

            Database::execute("
                UPDATE public.surat_jalan 
                SET status_surat_jalan = 'ditolak_owner', diubah_pada = NOW() 
                WHERE id = :id
            ", ['id' => $suratJalanId]);

            Database::execute("
                UPDATE public.pesanan 
                SET status_pemrosesan = 'dibatalkan', catatan = catatan || ' [Dibatalkan: ' || :alasan || ']', diubah_pada = NOW() 
                WHERE id = :id
            ", ['id' => $sj['pesanan_id'], 'alasan' => $alasan]);

            ActivityLog::log(
                'logistik',
                'UPDATE',
                "Admin membatalkan draft pengiriman {$sj['nomor_surat_jalan']} ke toko {$sj['nama_toko']}. Alasan: {$alasan}",
                'surat_jalan',
                $suratJalanId
            );

            $this->flashSuccess("Draft pengiriman {$sj['nomor_surat_jalan']} berhasil dibatalkan!");
            $this->redirect('/consignment?tab=deliveries');

        } catch (Throwable $e) {
            $this->flashError('Gagal membatalkan pengiriman: ' . $e->getMessage());
            $this->redirect('/consignment?tab=deliveries');
        }
    }

    /**
     * Layar B4: Catat Pembayaran Piutang Konsinyasi (Panggil fn_catat_pembayaran_konsinyasi)
     */
    public function payInvoice(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/consignment?tab=piutang');
            return;
        }

        $pesananId = (string)$this->input('pesanan_id');
        $accountId = (string)$this->input('akun_kas_id');
        $nominal = (float)$this->input('nominal', 0);
        $keterangan = trim((string)$this->input('keterangan', ''));

        if (empty($pesananId) || empty($accountId) || $nominal <= 0) {
            $this->flashError('Pilih nota pesanan, rekening kas penerima, dan masukkan nominal pembayaran yang valid.');
            $this->redirect('/consignment?tab=piutang');
            return;
        }

        try {
            $res = Database::fetchOne("
                SELECT public.fn_catat_pembayaran_konsinyasi(:p, :a, :nom, :user_id, :ket) as json_res
            ", [
                'p' => $pesananId,
                'a' => $accountId,
                'nom' => $nominal,
                'user_id' => Auth::id(),
                'ket' => !empty($keterangan) ? $keterangan : null
            ]);

            $jsonResult = json_decode($res['json_res'] ?? '{}', true);

            if (empty($jsonResult['success'])) {
                throw new \Exception('Pembayaran ditolak oleh database.');
            }

            $statusText = $jsonResult['status_pembayaran'] === 'lunas' ? 'LUNAS' : 'SEBAGIAN (Cicil)';
            $sisaRp = Format::rupiah((float)($jsonResult['sisa_tagihan'] ?? 0));

            ActivityLog::log(
                'keuangan',
                'INSERT',
                "Pencatatan pembayaran nota konsinyasi sebesar " . Format::rupiah($nominal) . " disetorkan ke kas. Status: {$statusText}.",
                'pesanan',
                $pesananId
            );

            $this->flashSuccess("Pembayaran sebesar " . Format::rupiah($nominal) . " berhasil dicatat! Status: {$statusText} (Sisa: {$sisaRp}).");
            $this->redirect('/consignment?tab=piutang');

        } catch (Throwable $e) {
            $this->flashError('Gagal mencatat pembayaran: ' . $e->getMessage());
            $this->redirect('/consignment?tab=piutang');
        }
    }

    /**
     * AJAX Endpoint: Ambil daftar item rak dan harga deal untuk toko yang dipilih
     */
    public function getStoreItems(): void
    {
        header('Content-Type: application/json');
        $storeId = (string)$this->input('pelanggan_id');

        if (empty($storeId)) {
            echo json_encode(['success' => false, 'items' => []]);
            return;
        }

        try {
            // Ambil semua item yang ada di rak toko ini
            $shelfItems = Database::fetchAll("
                SELECT skt.item_id, skt.stok_titip_saat_ini,
                       i.nama_item, i.kode_sku, i.satuan_dasar, COALESCE(gphl.harga_jual_pcs, 15000) as harga_jual_satuan, i.stok_fisik_saat_ini as stok_gudang
                FROM public.stok_konsinyasi_toko skt
                JOIN public.item i ON skt.item_id = i.id
                LEFT JOIN public.grup_produk_harga_level gphl ON gphl.grup_produk_id = i.grup_id AND gphl.level_harga = 1
                WHERE skt.pelanggan_id = :pelanggan_id
                ORDER BY i.nama_item ASC
            ", ['pelanggan_id' => $storeId]);

            $result = [];
            foreach ($shelfItems as $si) {
                // Hitung harga jual deal spesifik toko
                $priceInfo = Database::fetchOne("
                    SELECT public.fn_hitung_harga_jual_item(:item_id, :pelanggan_id) AS json_res
                ", ['item_id' => $si['item_id'], 'pelanggan_id' => $storeId]);

                $priceJson = json_decode($priceInfo['json_res'] ?? '{}', true);
                $dealPrice = (float)($priceJson['harga_pcs_netto'] ?? $si['harga_jual_satuan']);

                $result[] = [
                    'item_id' => $si['item_id'],
                    'nama_item' => $si['nama_item'],
                    'kode_sku' => $si['kode_sku'],
                    'satuan' => $si['satuan_dasar'] ?? 'pcs',
                    'stok_titip_saat_ini' => (int)$si['stok_titip_saat_ini'],
                    'stok_gudang' => (int)$si['stok_gudang'],
                    'harga_deal' => $dealPrice,
                    'sisa_fisik_di_rak' => (int)$si['stok_titip_saat_ini'],
                    'jumlah_laku' => 0,
                    'retur_bagus' => 0,
                    'retur_rusak' => 0,
                    'selisih' => 0,
                    'tambah_titip_baru' => 0,
                ];
            }

            echo json_encode([
                'success' => true,
                'items' => $result
            ]);

        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Proses Opname Rak Multi-Item Kunjungan Toko Konsinyasi
     */
    public function processOpname(): void
    {
        $customerId = $this->input('pelanggan_id');
        $itemsJson = $this->input('items_json');
        $catatan = trim((string)$this->input('catatan', 'Opname Kunjungan Rak Konsinyasi'));

        // Otomatis gunakan identitas user yang sedang aktif login
        $currentUserId = Auth::id();
        $currentUser = Auth::user();
        $driverId = $currentUser['karyawan_id'] ?? null;

        if (empty($customerId)) {
            $this->flashError('Pilih toko konsinyasi terlebih dahulu.');
            $this->redirect('/consignment');
            return;
        }

        $items = json_decode((string)$itemsJson, true);
        if (empty($items) || !is_array($items)) {
            $this->flashError('Mohon periksa dan masukkan minimal 1 rincian produk yang di-opname.');
            $this->redirect('/consignment?pelanggan_id=' . urlencode($customerId));
            return;
        }

        try {
            // Format array rincian untuk fungsi PostgreSQL dan pastikan ada mutasi nyata
            $rincianFormatted = [];
            $hasActualChanges = false;

            foreach ($items as $it) {
                $itemId = $it['item_id'] ?? null;
                if (!$itemId) continue;

                $sisaFisik = max(0, (int)($it['sisa_fisik_di_rak'] ?? 0));
                $laku = max(0, (int)($it['jumlah_laku'] ?? 0));
                $returBagus = max(0, (int)($it['retur_bagus'] ?? 0));
                $returRusak = max(0, (int)($it['retur_rusak'] ?? 0));
                $selisih = (int)($it['selisih'] ?? 0);
                $stokAwal = (int)($it['stok_titip_saat_ini'] ?? 0);

                if ($laku > 0 || $returBagus > 0 || $returRusak > 0 || $sisaFisik !== $stokAwal || $selisih !== 0) {
                    $hasActualChanges = true;
                }

                $rincianFormatted[] = [
                    'item_id' => $itemId,
                    'sisa_fisik_di_rak' => $sisaFisik,
                    'jumlah_laku' => $laku,
                    'retur_bagus' => $returBagus,
                    'retur_rusak' => $returRusak,
                    'selisih_qty' => $selisih,
                ];
            }

            if (empty($rincianFormatted)) {
                $this->flashError('Rincian opname tidak valid.');
                $this->redirect('/consignment?pelanggan_id=' . urlencode($customerId));
                return;
            }

            if (!$hasActualChanges) {
                $this->flashWarning('Tidak ada penjualan (laku), retur, selisih hilang/ketemu, atau perubahan saldo rak yang diisi. Opname tidak disimpan agar tidak mengotori data.');
                $this->redirect('/consignment?pelanggan_id=' . urlencode($customerId));
                return;
            }

            $res = Database::fetchOne("
                SELECT public.fn_proses_kunjungan_konsinyasi(:cust_id, :driver_id, :rincian::jsonb, :user_id) AS json_res
            ", [
                'cust_id' => $customerId,
                'driver_id' => $driverId,
                'rincian' => json_encode($rincianFormatted),
                'user_id' => $currentUserId
            ]);

            $jsonResult = json_decode($res['json_res'] ?? '{}', true);

            // Simpan catatan jika ada
            if (!empty($catatan) && !empty($jsonResult['nomor_kunjungan'])) {
                Database::execute("
                    UPDATE public.kunjungan_konsinyasi 
                    SET catatan = :catatan 
                    WHERE nomor_kunjungan = :nomor
                ", ['catatan' => $catatan, 'nomor' => $jsonResult['nomor_kunjungan']]);
            }

            // Catat log aktivitas
            $userName = Auth::user()['nama_lengkap'] ?? Auth::name() ?? 'Staff ERP';
            $custName = Database::fetchOne("SELECT nama_toko FROM public.pelanggan WHERE id = :id", ['id' => $customerId])['nama_toko'] ?? 'Toko Mitra';
            $totalLakuRp = Format::rupiah((float)($jsonResult['total_laku_netto'] ?? 0));
            $kunjunganId = $jsonResult['kunjungan_id'] ?? null;

            ActivityLog::log(
                'logistik',
                'INSERT',
                "Opname Konsinyasi Toko {$custName} ({$jsonResult['nomor_kunjungan']}) oleh {$userName}. Total Laku Terjual: {$totalLakuRp}",
                'kunjungan_konsinyasi',
                $kunjunganId
            );

            $msg = "Opname kunjungan konsinyasi berhasil diproses! Saldo rak tersinkronisasi. Total laku terjual: {$totalLakuRp}";
            if (!empty($jsonResult['nomor_nota_laku'])) {
                $msg .= " (Faktur Otomatis: {$jsonResult['nomor_nota_laku']})";
            }

            $this->flashSuccess($msg);
            $this->redirect('/consignment?tab=opname&pelanggan_id=' . urlencode($customerId));

        } catch (Throwable $e) {
            $this->flashError('Gagal memproses opname konsinyasi: ' . $e->getMessage());
            $this->redirect('/consignment?pelanggan_id=' . urlencode($customerId));
        }
    }

    /**
     * Ambil Data Rekap Laporan Tagihan Konsinyasi
     */
    private function fetchBillingReportData(string $storeId, string $startDate, string $endDate): array
    {
        if (empty($storeId)) {
            return [
                'store' => null,
                'items' => [],
                'unpaid_orders' => [],
                'grand_total_laku' => 0,
                'total_qty_laku' => 0,
                'total_retur' => 0,
                'total_kunjungan' => 0,
            ];
        }

        // Data Toko
        $store = Database::fetchOne("
            SELECT p.*, w.nama_wilayah as rute
            FROM public.pelanggan p
            LEFT JOIN public.wilayah w ON p.wilayah_id = w.id
            WHERE p.id = :id
        ", ['id' => $storeId]);

        // Rekap Item Terjual dari Kunjungan Opname pada rentang tanggal
        $items = Database::fetchAll("
            SELECT rkk.item_id, i.nama_item, i.kode_sku, i.satuan_dasar,
                   SUM(rkk.jumlah_laku_terjual) as total_laku,
                   SUM(rkk.retur_bagus) as total_retur_bagus,
                   SUM(rkk.retur_rusak) as total_retur_rusak,
                   SUM(rkk.tambah_titip_baru) as total_drop_baru,
                   AVG(rkk.harga_satuan_deal) as harga_satuan_deal,
                   SUM(rkk.subtotal_laku) as total_subtotal
            FROM public.rincian_kunjungan_konsinyasi rkk
            JOIN public.kunjungan_konsinyasi kk ON rkk.kunjungan_id = kk.id
            JOIN public.item i ON rkk.item_id = i.id
            WHERE kk.pelanggan_id = :pelanggan_id
              AND kk.tanggal_kunjungan >= :start_date
              AND kk.tanggal_kunjungan <= :end_date
            GROUP BY rkk.item_id, i.nama_item, i.kode_sku, i.satuan_dasar
            HAVING SUM(rkk.jumlah_laku_terjual) > 0 OR SUM(rkk.retur_bagus + rkk.retur_rusak) > 0
            ORDER BY total_subtotal DESC, i.nama_item ASC
        ", [
            'pelanggan_id' => $storeId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        // Faktur Pesanan Konsinyasi pada rentang tanggal
        $unpaidOrders = Database::fetchAll("
            SELECT p.id, p.nomor_nota, p.tanggal_pesanan, p.total_netto, p.total_dibayar, p.sisa_tagihan,
                   p.status_pembayaran, p.catatan, k.nama_karyawan as sales_driver
            FROM public.pesanan p
            LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
            WHERE p.pelanggan_id = :pelanggan_id
              AND p.tipe_pembayaran = 'konsinyasi'
              AND p.tanggal_pesanan >= :start_date
              AND p.tanggal_pesanan <= :end_date
            ORDER BY p.tanggal_pesanan DESC
        ", [
            'pelanggan_id' => $storeId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $grandTotalLaku = 0;
        $totalQtyLaku = 0;
        $totalRetur = 0;

        foreach ($items as $it) {
            $grandTotalLaku += (float)$it['total_subtotal'];
            $totalQtyLaku += (int)$it['total_laku'];
            $totalRetur += (int)($it['total_retur_bagus'] + $it['total_retur_rusak']);
        }

        $totalKunjungan = (int)(Database::fetchOne("
            SELECT COUNT(*) as count 
            FROM public.kunjungan_konsinyasi 
            WHERE pelanggan_id = :pelanggan_id 
              AND tanggal_kunjungan >= :start_date 
              AND tanggal_kunjungan <= :end_date
        ", [
            'pelanggan_id' => $storeId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ])['count'] ?? 0);

        return [
            'store' => $store,
            'items' => $items,
            'unpaid_orders' => $unpaidOrders,
            'grand_total_laku' => $grandTotalLaku,
            'total_qty_laku' => $totalQtyLaku,
            'total_retur' => $totalRetur,
            'total_kunjungan' => $totalKunjungan,
        ];
    }

    /**
     * Cetak Lembar Faktur / Laporan Rekap Tagihan Konsinyasi (Print Layout)
     */
    public function printBilling(): void
    {
        $storeId = (string)$this->input('pelanggan_id');
        $startDate = (string)$this->input('start_date', date('Y-m-01'));
        $endDate = (string)$this->input('end_date', date('Y-m-d'));

        if (empty($storeId)) {
            $this->flashError('Pilih toko konsinyasi untuk dicetak.');
            $this->redirect('/consignment?tab=billing');
            return;
        }

        try {
            $billingData = $this->fetchBillingReportData($storeId, $startDate, $endDate);

            if (!$billingData['store']) {
                $this->flashError('Toko tidak ditemukan.');
                $this->redirect('/consignment?tab=billing');
                return;
            }

            $this->view('consignment.print_billing', [
                'pageTitle' => 'Faktur Tagihan Konsinyasi Ã¢â‚¬â€ ' . ($billingData['store']['nama_toko'] ?? 'Toko'),
                'store' => $billingData['store'],
                'items' => $billingData['items'],
                'unpaidOrders' => $billingData['unpaid_orders'],
                'grandTotalLaku' => $billingData['grand_total_laku'],
                'totalQtyLaku' => $billingData['total_qty_laku'],
                'totalRetur' => $billingData['total_retur'],
                'totalKunjungan' => $billingData['total_kunjungan'],
                'startDate' => $startDate,
                'endDate' => $endDate,
                'autoPrint' => (bool)$this->input('autoprint', true),
            ]);

        } catch (Throwable $e) {
            echo "Print Error: " . $e->getMessage();
        }
    }

    /**
     * Catat Pelunasan Tagihan Konsinyasi ke Kas / Bank
     */
    public function confirmDelivery(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/consignment');
            return;
        }

        $suratJalanId = (string)$this->input('surat_jalan_id');

        if (empty($suratJalanId)) {
            $this->flashError('Surat jalan tidak ditemukan.');
            $this->redirect('/consignment');
            return;
        }

        try {
            $sj = Database::fetchOne("
                SELECT sj.id, sj.nomor_surat_jalan, sj.pesanan_id, p.nama_toko, pes.pelanggan_id
                FROM public.surat_jalan sj
                JOIN public.pesanan pes ON sj.pesanan_id = pes.id
                JOIN public.pelanggan p ON pes.pelanggan_id = p.id
                WHERE sj.id = :id AND sj.status_surat_jalan = 'sedang_dikirim'
            ", ['id' => $suratJalanId]);

            if (!$sj) {
                $this->flashError('Surat jalan tidak valid atau sudah selesai.');
                $this->redirect('/consignment');
                return;
            }

            // 1. Update status SJ Ã¢â€ â€™ selesai_diterima
            Database::execute("
                UPDATE public.surat_jalan 
                SET status_surat_jalan = 'selesai_diterima',
                    waktu_sampai = NOW(),
                    diubah_pada = NOW()
                WHERE id = :id
            ", ['id' => $suratJalanId]);

            // 2. Sync stok rak konsinyasi via PHP
            if (!empty($sj['pesanan_id']) && !empty($sj['pelanggan_id'])) {
                $orderedItems = Database::fetchAll("
                    SELECT item_id, kuantitas_satuan_dasar 
                    FROM public.item_pesanan 
                    WHERE pesanan_id = :id
                ", ['id' => $sj['pesanan_id']]);

                foreach ($orderedItems as $oit) {
                    Database::execute("
                        INSERT INTO public.stok_konsinyasi_toko (
                            pelanggan_id, item_id, stok_titip_saat_ini, terakhir_opname_pada, dibuat_pada, diubah_pada
                        ) VALUES (
                            :pelanggan_id, :item_id, :qty, NOW(), NOW(), NOW()
                        )
                        ON CONFLICT (pelanggan_id, item_id) DO UPDATE SET
                            stok_titip_saat_ini = public.stok_konsinyasi_toko.stok_titip_saat_ini + EXCLUDED.stok_titip_saat_ini,
                            diubah_pada = NOW()
                    ", [
                        'pelanggan_id' => $sj['pelanggan_id'],
                        'item_id' => $oit['item_id'],
                        'qty' => (int)$oit['kuantitas_satuan_dasar']
                    ]);
                }
            }

            ActivityLog::log(
                'logistik',
                'UPDATE',
                "Sales mengonfirmasi pengiriman {$sj['nomor_surat_jalan']} telah selesai diterima oleh toko {$sj['nama_toko']}. Stok rak disinkronisasi.",
                'surat_jalan',
                $suratJalanId
            );

            $this->flashSuccess("Pengiriman {$sj['nomor_surat_jalan']} berhasil dikonfirmasi! Saldo rak toko {$sj['nama_toko']} sudah bertambah.");
            $this->redirect('/consignment');

        } catch (Throwable $e) {
            $this->flashError('Gagal mengonfirmasi pengiriman: ' . $e->getMessage());
            $this->redirect('/consignment');
        }
    }
    public function opname(): void
    {
        $storeId = (string)$this->input('pelanggan_id');

        if (empty($storeId)) {
            $this->flashError('Pilih toko konsinyasi terlebih dahulu.');
            $this->redirect('/consignment');
            return;
        }

        try {
            $customer = Database::fetchOne("
                SELECT * FROM public.pelanggan WHERE id = :id AND is_konsinyasi = TRUE
            ", ['id' => $storeId]);

            if (!$customer) {
                $this->flashError('Toko konsinyasi tidak ditemukan.');
                $this->redirect('/consignment');
                return;
            }

            // Ambil semua item yang ada di rak toko (termasuk yang 0 pcs)
            $shelfItems = Database::fetchAll("
                SELECT skt.item_id, skt.stok_titip_saat_ini, skt.terakhir_opname_pada,
                       i.nama_item, i.kode_sku, i.satuan_dasar, COALESCE(gphl.harga_jual_pcs, 15000) as harga_jual_satuan
                FROM public.stok_konsinyasi_toko skt
                JOIN public.item i ON skt.item_id = i.id
                LEFT JOIN public.grup_produk_harga_level gphl ON gphl.grup_produk_id = i.grup_id AND gphl.level_harga = 1
                WHERE skt.pelanggan_id = :pelanggan_id
                ORDER BY (skt.stok_titip_saat_ini > 0) DESC, i.nama_item ASC
            ", ['pelanggan_id' => $storeId]);

            $savedInput = $_SESSION['_old_opname_input'][$storeId] ?? null;

            $items = [];
            foreach ($shelfItems as $si) {
                // Ambil harga deal toko
                $priceInfo = Database::fetchOne("
                    SELECT public.fn_hitung_harga_jual_item(:item_id, :pelanggan_id) AS json_res
                ", ['item_id' => $si['item_id'], 'pelanggan_id' => $storeId]);

                $priceJson = json_decode($priceInfo['json_res'] ?? '{}', true);
                $dealPrice = (float)($priceJson['harga_pcs_netto'] ?? $si['harga_jual_satuan']);

                $sisaFisik = (int)$si['stok_titip_saat_ini'];
                $rBagus = 0;
                $rRusak = 0;
                $isTouched = false;

                if ($savedInput && isset($savedInput[$si['item_id']])) {
                    $sisaFisik = (int)($savedInput[$si['item_id']]['sisa_fisik_di_rak'] ?? $sisaFisik);
                    $rBagus = (int)($savedInput[$si['item_id']]['retur_bagus'] ?? 0);
                    $rRusak = (int)($savedInput[$si['item_id']]['retur_rusak'] ?? 0);
                    $isTouched = true;
                }

                $items[] = [
                    'item_id' => $si['item_id'],
                    'nama_item' => $si['nama_item'],
                    'kode_sku' => $si['kode_sku'],
                    'satuan_dasar' => $si['satuan_dasar'] ?? 'pcs',
                    'stok_titip_saat_ini' => (int)$si['stok_titip_saat_ini'],
                    'harga_deal' => $dealPrice,
                    'sisa_fisik_di_rak' => $sisaFisik,
                    'retur_bagus' => $rBagus,
                    'retur_rusak' => $rRusak,
                    'is_touched' => $isTouched
                ];
            }

            $this->view('consignment.opname', [
                'pageTitle' => 'Form Opname Rak Toko',
                'pageSubtitle' => 'Hitung Sisa Fisik & Retur Kunjungan Toko',
                'customer' => $customer,
                'items' => $items,
            ]);

        } catch (Throwable $e) {
            echo "Error Sales Opname: " . $e->getMessage();
        }
    }

    /**
     * Action A2 POST: Proses Form Opname Sales
     */
    public function processOpname(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/consignment');
            return;
        }

        $customerId = (string)$this->input('pelanggan_id');
        $itemsJson = (string)$this->input('items_json');
        $catatan = trim((string)$this->input('catatan', 'Opname Kunjungan Sales Mobile'));

        $currentUserId = Auth::id();
        $currentUser = Auth::user();
        $driverId = $currentUser['karyawan_id'] ?? null;

        if (empty($customerId)) {
            $this->flashError('Pilih toko konsinyasi terlebih dahulu.');
            $this->redirect('/consignment');
            return;
        }

        $items = json_decode($itemsJson, true);
        if (empty($items) || !is_array($items)) {
            $this->flashError('Mohon periksa dan masukkan minimal 1 rincian produk yang di-opname.');
            $this->redirect('/consignment/opname?pelanggan_id=' . urlencode($customerId));
            return;
        }

        // Simpan sementara input ke sesi untuk recovery jika gagal
        $_SESSION['_old_opname_input'][$customerId] = [];
        foreach ($items as $it) {
            if (!empty($it['item_id'])) {
                $_SESSION['_old_opname_input'][$customerId][$it['item_id']] = $it;
            }
        }

        try {
            $rincianFormatted = [];
            foreach ($items as $it) {
                $itemId = $it['item_id'] ?? null;
                if (!$itemId) continue;

                $sisaFisik = max(0, (int)($it['sisa_fisik_di_rak'] ?? 0));
                $laku = max(0, (int)($it['jumlah_laku'] ?? 0));
                $returBagus = max(0, (int)($it['retur_bagus'] ?? 0));
                $returRusak = max(0, (int)($it['retur_rusak'] ?? 0));

                $rincianFormatted[] = [
                    'item_id' => $itemId,
                    'sisa_fisik_di_rak' => $sisaFisik,
                    'jumlah_laku' => $laku,
                    'retur_bagus' => $returBagus,
                    'retur_rusak' => $returRusak,
                    'selisih_qty' => 0,
                ];
            }

            $res = Database::fetchOne("
                SELECT public.fn_proses_kunjungan_konsinyasi(:cust_id, :driver_id, :rincian::jsonb, :user_id) AS json_res
            ", [
                'cust_id' => $customerId,
                'driver_id' => $driverId,
                'rincian' => json_encode($rincianFormatted),
                'user_id' => $currentUserId
            ]);

            $jsonResult = json_decode($res['json_res'] ?? '{}', true);

            if (empty($jsonResult['success'])) {
                throw new \Exception('Proses opname ditolak oleh database.');
            }

            // Hapus old input sesi karena sudah sukses
            unset($_SESSION['_old_opname_input'][$customerId]);

            $kunjunganId = $jsonResult['kunjungan_id'] ?? null;

            // Simpan catatan
            if (!empty($catatan) && !empty($jsonResult['nomor_kunjungan'])) {
                Database::execute("
                    UPDATE public.kunjungan_konsinyasi 
                    SET catatan = :catatan 
                    WHERE id = :id
                ", ['catatan' => $catatan, 'id' => $kunjunganId]);
            }

            $custName = Database::fetchOne("SELECT nama_toko FROM public.pelanggan WHERE id = :id", ['id' => $customerId])['nama_toko'] ?? 'Toko';
            ActivityLog::log(
                'logistik',
                'INSERT',
                "Sales menyelesaikan kunjungan opname di {$custName} ({$jsonResult['nomor_kunjungan']}).",
                'kunjungan_konsinyasi',
                $kunjunganId
            );

            $this->redirect('/consignment/summary?kunjungan_id=' . urlencode((string)$kunjunganId));

        } catch (Throwable $e) {
            $this->flashError('Gagal memproses opname: ' . $e->getMessage() . ' (Data formulir Anda tetap tersimpan, silakan coba kirim lagi)');
            $this->redirect('/consignment/opname?pelanggan_id=' . urlencode($customerId));
        }
    }

    /**
     * Layar A3: Hasil Kunjungan & Faktur Otomatis
     */
    public function summary(): void
    {
        $kunjunganId = (string)$this->input('kunjungan_id');

        if (empty($kunjunganId)) {
            $this->flashError('ID kunjungan tidak ditemukan.');
            $this->redirect('/consignment');
            return;
        }

        try {
            $visit = Database::fetchOne("
                SELECT kk.*, p.nama_toko, p.alamat_lengkap, p.nomor_whatsapp,
                       COALESCE(peng.nama_lengkap, k.nama_karyawan, 'Sales') as sales_name,
                       pes.nomor_nota, pes.total_netto, pes.status_pembayaran
                FROM public.kunjungan_konsinyasi kk
                JOIN public.pelanggan p ON kk.pelanggan_id = p.id
                LEFT JOIN public.pengguna peng ON kk.dibuat_oleh = peng.id
                LEFT JOIN public.karyawan k ON kk.sales_driver_id = k.id
                LEFT JOIN public.pesanan pes ON kk.pesanan_id = pes.id
                WHERE kk.id = :id
            ", ['id' => $kunjunganId]);

            if (!$visit) {
                $this->flashError('Data kunjungan tidak ditemukan.');
                $this->redirect('/consignment');
                return;
            }

            $details = Database::fetchAll("
                SELECT rkk.*, i.nama_item, i.kode_sku, i.satuan_dasar
                FROM public.rincian_kunjungan_konsinyasi rkk
                JOIN public.item i ON rkk.item_id = i.id
                WHERE rkk.kunjungan_id = :id
                ORDER BY rkk.subtotal_laku DESC, i.nama_item ASC
            ", ['id' => $kunjunganId]);

            $this->view('consignment.summary', [
                'pageTitle' => 'Hasil Kunjungan Konsinyasi',
                'pageSubtitle' => 'Ringkasan Opname & Nota Penjualan',
                'visit' => $visit,
                'details' => $details,
            ]);

        } catch (Throwable $e) {
            echo "Error Sales Summary: " . $e->getMessage();
        }
    }

    /**
     * Layar A4: Buat Pengajuan Pengiriman Titip Baru
     */
    public function salesRequestDelivery(): void
    {
        try {
            $selectedStoreId = (string)$this->input('pelanggan_id', '');
            $currentUser = Auth::user();
            $driverId = $currentUser['karyawan_id'] ?? null;
            $isAdmin = Auth::isAdmin() || Auth::isOwner() || Auth::isDeveloper();

            // 1. Ambil daftar toko konsinyasi aktif
            $queryStores = "
                SELECT id, kode_pelanggan, nama_toko, alamat_lengkap
                FROM public.pelanggan
                WHERE is_konsinyasi = TRUE AND status_aktif = TRUE
            ";
            $paramsStores = [];
            if (!$isAdmin && $driverId) {
                $queryStores .= " AND sales_driver_id = :driver_id";
                $paramsStores['driver_id'] = $driverId;
            }
            $queryStores .= " ORDER BY nama_toko ASC";
            $stores = Database::fetchAll($queryStores, $paramsStores);

            // 2. Ambil seluruh SKU barang jadi yang ready di gudang (TIDAK MENAMPILKAN HARGA KE SALES)
            $items = Database::fetchAll("
                SELECT id, kode_sku, nama_item, satuan_dasar, stok_fisik_saat_ini
                FROM public.item
                WHERE tipe_item = 'barang_jadi' AND status_aktif = TRUE
                ORDER BY nama_item ASC
            ");

            $this->view('consignment.sales.delivery', [
                'pageTitle' => 'Pengajuan Titip Baru',
                'pageSubtitle' => 'Formulir Permintaan Drop Barang Konsinyasi',
                'stores' => $stores,
                'items' => $items,
                'selectedStoreId' => $selectedStoreId,
            ]);

        } catch (Throwable $e) {
            echo "Error Sales Request Delivery: " . $e->getMessage();
        }
    }

    /**
     * Action A4 POST: Submit Pengajuan Pengiriman Titip Baru
     */
    public function submitSalesDelivery(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/consignment/sales/request-delivery');
            return;
        }

        $customerId = (string)$this->input('pelanggan_id');
        $itemsJson = (string)$this->input('items_json');
        $catatan = trim((string)$this->input('catatan', 'Pengajuan Titip Baru Sales Mobile'));

        $currentUserId = Auth::id();
        $currentUser = Auth::user();
        $driverId = $currentUser['karyawan_id'] ?? null;

        if (empty($customerId)) {
            $this->flashError('Pilih toko konsinyasi tujuan terlebih dahulu.');
            $this->redirect('/consignment/sales/request-delivery');
            return;
        }

        $items = json_decode($itemsJson, true);
        if (empty($items) || !is_array($items)) {
            $this->flashError('Masukkan minimal 1 produk dan kuantitas yang diajukan.');
            $this->redirect('/consignment/sales/request-delivery?pelanggan_id=' . urlencode($customerId));
            return;
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $nomorNota = 'SJ-KONSIN-' . date('Ymd-His') . '-' . rand(100, 999);
            $nomorSJ = 'SJ-' . date('Ymd-His') . '-' . rand(100, 999);

            // Bikin pesanan non-tagihan (adalah_tagihan = FALSE)
            $stmtPes = $pdo->prepare("
                INSERT INTO public.pesanan (
                    nomor_nota, pelanggan_id, sales_driver_id, tanggal_pesanan,
                    tipe_pembayaran, status_pembayaran, status_pemrosesan,
                    total_bruto, total_netto, adalah_tagihan, catatan,
                    dibuat_oleh, dibuat_pada, diubah_pada
                ) VALUES (
                    :nota, :pelanggan_id, :driver_id, CURRENT_DATE,
                    'konsinyasi', 'belum_lunas', 'menunggu_approval',
                    0.00, 0.00, FALSE, :catatan,
                    :user_id, NOW(), NOW()
                ) RETURNING id
            ");
            $stmtPes->execute([
                'nota' => $nomorNota,
                'pelanggan_id' => $customerId,
                'driver_id' => $driverId,
                'catatan' => $catatan,
                'user_id' => $currentUserId
            ]);
            $pesananId = $stmtPes->fetchColumn();

            // Insert rincian item_pesanan dengan HPP snapshot
            $stmtItem = $pdo->prepare("
                INSERT INTO public.item_pesanan (
                    pesanan_id, item_id, kuantitas_satuan_dasar, kuantitas_satuan_distribusi,
                    harga_satuan_deal, is_bonus, subtotal, dibuat_pada
                ) VALUES (
                    :pesanan_id, :item_id, :qty, 0,
                    :hpp, FALSE, :subtotal, NOW()
                )
            ");

            foreach ($items as $it) {
                $itemId = $it['item_id'] ?? null;
                $qty = max(1, (int)($it['qty'] ?? 1));
                if (!$itemId) continue;

                // Ambil HPP internal
                $stmtHpp = $pdo->prepare("SELECT COALESCE(harga_pokok_pembelian, 0.00) FROM public.item WHERE id = :id");
                $stmtHpp->execute(['id' => $itemId]);
                $hpp = (float)($stmtHpp->fetchColumn() ?? 0);
                $subtotal = $qty * $hpp;

                $stmtItem->execute([
                    'pesanan_id' => $pesananId,
                    'item_id' => $itemId,
                    'qty' => $qty,
                    'hpp' => $hpp,
                    'subtotal' => $subtotal
                ]);
            }

            // Bikin surat jalan status 'draf_n8n' (menunggu approval owner)
            $stmtSj = $pdo->prepare("
                INSERT INTO public.surat_jalan (
                    nomor_surat_jalan, pesanan_id, sales_driver_id, status_surat_jalan, dibuat_pada, diubah_pada
                ) VALUES (
                    :nomor_sj, :pesanan_id, :driver_id, 'draf_n8n', NOW(), NOW()
                )
            ");
            $stmtSj->execute([
                'nomor_sj' => $nomorSJ,
                'pesanan_id' => $pesananId,
                'driver_id' => $driverId
            ]);

            $pdo->commit();

            $custName = Database::fetchOne("SELECT nama_toko FROM public.pelanggan WHERE id = :id", ['id' => $customerId])['nama_toko'] ?? 'Toko';
            ActivityLog::log(
                'logistik',
                'INSERT',
                "Sales mengajukan pengiriman titip baru untuk toko {$custName} ({$nomorSJ}). Menunggu approval owner.",
                'surat_jalan',
                $pesananId
            );

            $this->flashSuccess("Pengiriman titip baru untuk toko {$custName} berhasil diajukan! Menunggu approval Owner sebelum diberangkatkan.");
            $this->redirect('/consignment');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal mengajukan pengiriman titip baru: ' . $e->getMessage());
            $this->redirect('/consignment/sales/request-delivery?pelanggan_id=' . urlencode($customerId));
        }
    }
}


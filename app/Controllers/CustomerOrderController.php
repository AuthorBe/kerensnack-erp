<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\Format;
use App\Helpers\ActivityLog;
use App\Helpers\PdfExport;
use App\Helpers\ExcelExport;
use App\Helpers\StockHelper;
use App\Helpers\PaymentHelper;
use App\Helpers\DocumentNumber;
use App\Helpers\CashVoucher;
use App\Core\Router;
use Database;
use Throwable;

/**
 * app/Controllers/CustomerOrderController.php
 * Pengendali Transaksi Penjualan Toko Mitra B2B & Faktur Pesanan Pelanggan.
 */
class CustomerOrderController extends Controller
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Tampilkan Daftar Pesanan Pelanggan / Penjualan Toko
     */
    public function index(): void
    {
        Auth::requirePermission(['orders.view_all', 'orders.view_assigned']);

        try {
            // Handle Reset Filter
            if (isset($_GET['reset']) && (string)$_GET['reset'] === '1') {
                unset($_SESSION['orders_filter']);
                $this->redirect('/customer-orders');
                return;
            }

            // Cek apakah ada filter eksplisit di URL
            $hasExplicitFilter = isset($_GET['start_date']) || isset($_GET['end_date']) || isset($_GET['pelanggan_id']) || isset($_GET['sales_driver_id']) || isset($_GET['status_pembayaran']) || isset($_GET['tipe_transaksi']) || isset($_GET['q']);

            if ($hasExplicitFilter) {
                $startDate = $this->input('start_date', date('Y-m-01'));
                $endDate = $this->input('end_date', date('Y-m-d'));
                $pelangganId = $this->input('pelanggan_id', '');
                $salesDriverId = $this->input('sales_driver_id', '');
                $statusBayar = $this->input('status_pembayaran', 'semua');
                $tipeTransaksi = $this->input('tipe_transaksi', 'semua');
                $q = trim((string)$this->input('q', ''));

                // Simpan ke sesi
                $_SESSION['orders_filter'] = [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'pelanggan_id' => $pelangganId,
                    'sales_driver_id' => $salesDriverId,
                    'status_pembayaran' => $statusBayar,
                    'tipe_transaksi' => $tipeTransaksi,
                    'q' => $q,
                ];
            } elseif (!empty($_SESSION['orders_filter'])) {
                // Pulihkan filter dari sesi
                $saved = $_SESSION['orders_filter'];
                $startDate = $saved['start_date'] ?? date('Y-m-01');
                $endDate = $saved['end_date'] ?? date('Y-m-d');
                $pelangganId = $saved['pelanggan_id'] ?? '';
                $salesDriverId = $saved['sales_driver_id'] ?? '';
                $statusBayar = $saved['status_pembayaran'] ?? 'semua';
                $tipeTransaksi = $saved['tipe_transaksi'] ?? 'semua';
                $q = $saved['q'] ?? '';
            } else {
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-d');
                $pelangganId = '';
                $salesDriverId = '';
                $statusBayar = 'semua';
                $tipeTransaksi = 'semua';
                $q = '';
            }

            // Query Dasar Pesanan Toko Pelanggan
            $sql = "
                SELECT p.id, p.nomor_nota, p.tanggal_pesanan, p.total_bruto, p.total_diskon, p.total_netto,
                       p.total_dibayar, p.sisa_tagihan, p.tipe_pembayaran, p.tanggal_jatuh_tempo,
                       p.status_pembayaran, p.status_pemrosesan, p.catatan, p.is_tagihan, p.dibuat_pada,
                       p.waktu_gagal_kirim, p.diubah_pada,
                       CASE WHEN p.catatan ILIKE '%Beli putus%' THEN TRUE ELSE FALSE END as is_beli_putus,
                       pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.is_konsinyasi,
                       CASE 
                           WHEN sj.id IS NOT NULL THEN COALESCE(k_sj.nama_karyawan, k_p.nama_karyawan)
                           WHEN p.status_pemrosesan = 'po' THEN NULL
                           ELSE k_p.nama_karyawan
                       END as nama_sales,
                       CASE 
                           WHEN sj.id IS NOT NULL THEN COALESCE(k_sj.nomor_polisi_kendaraan, k_p.nomor_polisi_kendaraan, 'Armada Toko')
                           WHEN p.status_pemrosesan = 'po' THEN NULL
                           WHEN k_p.id IS NOT NULL THEN COALESCE(k_p.nomor_polisi_kendaraan, 'Armada Toko')
                           ELSE NULL
                       END as nopol_driver,
                       ak.nama_akun as nama_akun_kas,
                       sj.id as surat_jalan_id, sj.nomor_surat_jalan, sj.status_surat_jalan,
                       sj.dibuat_pada as waktu_surat_jalan, sj.waktu_berangkat, sj.waktu_sampai,
                       w.nama_wilayah,
                       (SELECT COUNT(*) FROM public.item_pesanan ip WHERE ip.pesanan_id = p.id) as total_sku_items,
                       (SELECT COALESCE(SUM(kuantitas_satuan_dasar), 0) FROM public.item_pesanan ip WHERE ip.pesanan_id = p.id) as total_pcs_items
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN (
                    SELECT DISTINCT ON (pesanan_id) id, nomor_surat_jalan, status_surat_jalan, sales_driver_id, pesanan_id,
                           dibuat_pada, waktu_berangkat, waktu_sampai, rute_wilayah_id
                    FROM public.surat_jalan
                    ORDER BY pesanan_id, (status_surat_jalan NOT IN ('gagal_kirim', 'dibatalkan')) DESC, dibuat_pada DESC
                ) sj ON sj.pesanan_id = p.id
                LEFT JOIN public.v_karyawan_info k_p ON p.sales_driver_id = k_p.id
                LEFT JOIN public.v_karyawan_info k_sj ON sj.sales_driver_id = k_sj.id
                LEFT JOIN public.akun_kas ak ON p.akun_kas_id = ak.id
                LEFT JOIN public.wilayah w ON COALESCE(sj.rute_wilayah_id, pel.wilayah_id) = w.id
                WHERE p.tanggal_pesanan >= :start_date AND p.tanggal_pesanan <= :end_date
            ";

            $params = [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];

            // Scope Check: Jika hanya punya hak lihat toko binaan (orders.view_assigned)
            if (!Auth::can('orders.view_all')) {
                $myEmpId = Auth::employeeId();
                if ($myEmpId) {
                    $sql .= " AND (p.sales_driver_id = :my_emp_id OR pel.sales_driver_id = :my_emp_id)";
                    $params['my_emp_id'] = $myEmpId;
                } else {
                    $sql .= " AND 1=0"; // Tidak ada karyawan tertaut -> kosongkan data
                }
            }

            if (!empty($pelangganId)) {
                $sql .= " AND p.pelanggan_id = :pelanggan_id";
                $params['pelanggan_id'] = $pelangganId;
            }

            if (!empty($salesDriverId)) {
                $sql .= " AND p.sales_driver_id = :sales_driver_id";
                $params['sales_driver_id'] = $salesDriverId;
            }

            if (!empty($statusBayar) && $statusBayar !== 'semua') {
                $sql .= " AND p.status_pembayaran = :status_pembayaran";
                $params['status_pembayaran'] = $statusBayar;
            }

            if (!empty($tipeTransaksi) && $tipeTransaksi !== 'semua') {
                if ($tipeTransaksi === 'beli_putus') {
                    $sql .= " AND p.catatan ILIKE '%Beli putus%'";
                } elseif ($tipeTransaksi === 'reguler') {
                    $sql .= " AND (p.catatan NOT ILIKE '%Beli putus%' OR p.catatan IS NULL) AND p.tipe_pembayaran != 'konsinyasi' AND (p.is_tagihan = TRUE OR p.is_tagihan IS NULL)";
                } elseif ($tipeTransaksi === 'konsinyasi') {
                    $sql .= " AND (p.tipe_pembayaran = 'konsinyasi' OR p.is_tagihan = FALSE)";
                }
            }

            if (!empty($q)) {
                $sql .= " AND (p.nomor_nota ILIKE :q OR pel.nama_toko ILIKE :q OR pel.kode_pelanggan ILIKE :q OR p.catatan ILIKE :q)";
                $params['q'] = "%{$q}%";
            }

            $sql .= " ORDER BY p.tanggal_pesanan DESC, p.dibuat_pada DESC";

            $orders = Database::fetchAll($sql, $params);

            // Perkaya data order dengan stempel waktu tahapan logistik
            foreach ($orders as &$o) {
                $waktuPacking = $o['waktu_surat_jalan'] ?? null;
                if (!$waktuPacking && !in_array($o['status_pemrosesan'] ?? '', ['po'])) {
                    $waktuPacking = $o['diubah_pada'] ?? null;
                }
                $o['waktu_packing'] = $waktuPacking;

                $waktuKirim = $o['waktu_berangkat'] ?? null;
                if (!$waktuKirim && ($o['status_pemrosesan'] ?? '') === 'gagal_dikirim') {
                    $waktuKirim = $o['waktu_gagal_kirim'] ?? $o['diubah_pada'] ?? null;
                } elseif (!$waktuKirim && in_array($o['status_pemrosesan'] ?? '', ['sedang_dikirim', 'selesai_dikirim', 'selesai_diterima', 'selesai'])) {
                    $waktuKirim = $o['waktu_surat_jalan'] ?? $o['diubah_pada'] ?? null;
                }
                $o['waktu_pengiriman'] = $waktuKirim;

                $waktuSelesai = $o['waktu_sampai'] ?? null;
                if (!$waktuSelesai && in_array($o['status_pemrosesan'] ?? '', ['selesai_dikirim', 'selesai_diterima', 'selesai'])) {
                    $waktuSelesai = $o['diubah_pada'] ?? null;
                }
                $o['waktu_selesai'] = $waktuSelesai;
            }
            unset($o);

            // Metrik Ringkasan
            $totalOmset = 0;
            $totalPiutang = 0;
            $countLunas = 0;
            $countTotal = 0;

            foreach ($orders as $o) {
                if ($o['status_pembayaran'] === 'dibatalkan') {
                    continue; // Lewati pesanan batal dari perhitungan omset & piutang
                }

                $countTotal++;
                $totalOmset += (float)$o['total_netto'];
                if ($o['status_pembayaran'] !== 'lunas') {
                    $totalPiutang += (float)$o['total_netto'] - (float)$o['total_dibayar'];
                } else {
                    $countLunas++;
                }
            }

            // Master Filter
            $customers = Database::fetchAll("SELECT id, kode_pelanggan, nama_toko FROM public.pelanggan WHERE status_aktif = TRUE ORDER BY nama_toko ASC");
            $drivers = Database::fetchAll("SELECT id, nama_karyawan, nomor_polisi_kendaraan FROM public.v_karyawan_info WHERE posisi IN ('sales', 'driver') AND status_aktif = TRUE ORDER BY nama_karyawan ASC");
            $cashAccounts = Database::fetchAll("SELECT id, nama_akun, saldo_saat_ini, is_default_pos FROM public.akun_kas WHERE status_aktif = TRUE ORDER BY is_default_pos DESC, nama_akun ASC");

            $this->view('customer_orders.index', [
                'pageTitle' => 'Pesanan Pelanggan',
                'pageSubtitle' => 'Daftar Transaksi & Faktur Penjualan Toko Mitra',
                'orders' => $orders,
                'customers' => $customers,
                'drivers' => $drivers,
                'cashAccounts' => $cashAccounts,
                'metrics' => [
                    'total_omset' => $totalOmset,
                    'total_piutang' => $totalPiutang,
                    'count_total' => $countTotal,
                    'count_lunas' => $countLunas,
                ],
                'filter' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'pelanggan_id' => $pelangganId,
                    'sales_driver_id' => $salesDriverId,
                    'status_pembayaran' => $statusBayar,
                    'tipe_transaksi' => $tipeTransaksi,
                    'q' => $q,
                ]
            ]);

        } catch (Throwable $e) {
            $this->flashError("Gagal memuat daftar pesanan: " . $e->getMessage());
            $this->redirect('/dashboard');
        }
    }

    /**
     * API JSON Detail Pesanan Pelanggan Lengkap (Items, Pelanggan, Driver/Nopol, Surat Jalan, Kas)
     */
    public function detailAjax(): void
    {
        header('Content-Type: application/json');
        try {
            $id = $this->input('id');
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID pesanan tidak ditemukan.']);
                exit;
            }

            // 1. Data Pesanan Lengkap
            $sqlOrder = "
                SELECT p.id, p.nomor_nota, p.tanggal_pesanan, p.total_bruto, p.total_diskon, p.total_netto,
                       p.total_dibayar, p.sisa_tagihan, p.tipe_pembayaran, p.tanggal_jatuh_tempo,
                       p.status_pembayaran, p.status_pemrosesan, p.catatan, p.is_tagihan, p.dibuat_pada,
                       p.waktu_gagal_kirim, p.diubah_pada,
                       CASE WHEN p.catatan ILIKE '%Beli putus%' THEN TRUE ELSE FALSE END as is_beli_putus,
                       pel.id as pelanggan_id, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
                       pel.sales_driver_id as pelanggan_sales_id,
                       p.sales_driver_id,
                       CASE 
                           WHEN sj.id IS NOT NULL THEN COALESCE(k_sj.nama_karyawan, k_p.nama_karyawan)
                           WHEN p.status_pemrosesan = 'po' THEN NULL
                           ELSE k_p.nama_karyawan
                       END as nama_sales,
                       CASE 
                           WHEN sj.id IS NOT NULL THEN COALESCE(k_sj.nomor_polisi_kendaraan, k_p.nomor_polisi_kendaraan, 'Armada Toko')
                           WHEN p.status_pemrosesan = 'po' THEN NULL
                           WHEN k_p.id IS NOT NULL THEN COALESCE(k_p.nomor_polisi_kendaraan, 'Armada Toko')
                           ELSE NULL
                       END as nopol_driver,
                       ak.id as akun_kas_id, ak.nama_akun as nama_akun_kas,
                       sj.id as surat_jalan_id, sj.nomor_surat_jalan, sj.status_surat_jalan,
                       sj.dibuat_pada as waktu_surat_jalan, sj.waktu_berangkat, sj.waktu_sampai
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN (
                    SELECT DISTINCT ON (pesanan_id) id, nomor_surat_jalan, status_surat_jalan, sales_driver_id, pesanan_id,
                           dibuat_pada, waktu_berangkat, waktu_sampai
                    FROM public.surat_jalan
                    ORDER BY pesanan_id, (status_surat_jalan NOT IN ('gagal_kirim', 'dibatalkan')) DESC, dibuat_pada DESC
                ) sj ON sj.pesanan_id = p.id
                LEFT JOIN public.v_karyawan_info k_p ON p.sales_driver_id = k_p.id
                LEFT JOIN public.v_karyawan_info k_sj ON sj.sales_driver_id = k_sj.id
                LEFT JOIN public.akun_kas ak ON p.akun_kas_id = ak.id
                WHERE p.id = :id
            ";
            $order = Database::fetchOne($sqlOrder, ['id' => $id]);

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
                exit;
            }

            // Scope Check: Jika user hanya punya hak akses assigned, larang IDOR melihat pesanan orang lain
            if (!Auth::can('orders.view_all') && !Auth::can('orders.po_view_all')) {
                $myEmpId = Auth::employeeId();
                if ($order['sales_driver_id'] !== $myEmpId && ($order['pelanggan_sales_id'] ?? null) !== $myEmpId) {
                    echo json_encode(['success' => false, 'message' => 'Akses ditolak: Anda hanya dapat melihat detail pesanan toko binaan Anda.']);
                    exit;
                }
            }

            // Perkaya data detail dengan waktu tahapan logistik
            $waktuPacking = $order['waktu_surat_jalan'] ?? null;
            if (!$waktuPacking && !in_array($order['status_pemrosesan'] ?? '', ['po'])) {
                $waktuPacking = $order['diubah_pada'] ?? null;
            }
            $order['waktu_packing'] = $waktuPacking;

            $waktuKirim = $order['waktu_berangkat'] ?? null;
            if (!$waktuKirim && ($order['status_pemrosesan'] ?? '') === 'gagal_dikirim') {
                $waktuKirim = $order['waktu_gagal_kirim'] ?? $order['diubah_pada'] ?? null;
            } elseif (!$waktuKirim && in_array($order['status_pemrosesan'] ?? '', ['sedang_dikirim', 'selesai_dikirim', 'selesai_diterima', 'selesai'])) {
                $waktuKirim = $order['waktu_surat_jalan'] ?? $order['diubah_pada'] ?? null;
            }
            $order['waktu_pengiriman'] = $waktuKirim;

            $waktuSelesai = $order['waktu_sampai'] ?? null;
            if (!$waktuSelesai && in_array($order['status_pemrosesan'] ?? '', ['selesai_dikirim', 'selesai_diterima', 'selesai'])) {
                $waktuSelesai = $order['diubah_pada'] ?? null;
            }
            $order['waktu_selesai'] = $waktuSelesai;

            // 2. Daftar Item Pesanan
            $sqlItems = "
                SELECT ip.id, ip.item_id, ip.kuantitas_satuan_dasar, 
                       ip.harga_satuan_deal as harga_satuan_dasar, 
                       ip.diskon_item_persen as diskon_persen, 
                       ip.diskon_item_nominal as diskon_nominal, 
                       ip.is_bonus, ip.catatan_bonus, ip.subtotal,
                       i.kode_sku, i.nama_item, i.satuan_dasar,
                       gp.nama_grup as nama_grup_produk
                FROM public.item_pesanan ip
                JOIN public.item i ON ip.item_id = i.id
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE ip.pesanan_id = :id
                ORDER BY i.nama_item ASC
            ";
            $items = Database::fetchAll($sqlItems, ['id' => $id]);

            // 3. Driver & Kas Master
            $drivers = Database::fetchAll("
                SELECT id, nama_karyawan, nomor_polisi_kendaraan, nomor_telepon
                FROM public.v_karyawan_info 
                WHERE posisi IN ('sales', 'driver') AND status_aktif = TRUE 
                ORDER BY nama_karyawan ASC
            ");

            $cashAccounts = Database::fetchAll("
                SELECT id, nama_akun, saldo_saat_ini, is_default_pos 
                FROM public.akun_kas 
                WHERE status_aktif = TRUE 
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            // 4. Riwayat Pembayaran Terkait Pesanan Ini (dari Arus Kas)
            $sqlPayments = "
                SELECT ak.id, ak.tanggal_transaksi, ak.nominal, ak.keterangan, ak.saldo_berjalan, ak.dibuat_pada,
                       kas.id as akun_kas_id, kas.nama_akun as akun_kas_nama,
                       COALESCE(p.nama_lengkap, 'Petugas Kasir') as dicatat_oleh_nama
                FROM public.arus_kas ak
                JOIN public.akun_kas kas ON ak.akun_kas_id = kas.id
                LEFT JOIN public.pengguna p ON ak.dicatat_oleh = p.id
                WHERE ak.referensi_tabel = 'pesanan' AND ak.referensi_id = :id AND ak.jenis_kas = 'masuk'
                ORDER BY ak.dibuat_pada ASC
            ";
            $payments = Database::fetchAll($sqlPayments, ['id' => $id]);

            // 5. Riwayat Seluruh Surat Jalan Terkait Pesanan Ini (Termasuk Arsip Gagal Kirim)
            $sqlShipping = "
                SELECT sj.id, sj.nomor_surat_jalan, sj.status_surat_jalan, sj.waktu_berangkat, sj.waktu_sampai,
                       sj.bukti_terima_foto, sj.foto_bukti_gagal, sj.nama_penerima_toko, sj.dibuat_pada,
                       k.nama_karyawan as nama_driver, k.nomor_polisi_kendaraan as nopol_driver, k.nomor_telepon as telp_driver,
                       w.nama_wilayah
                FROM public.surat_jalan sj
                LEFT JOIN public.v_karyawan_info k ON sj.sales_driver_id = k.id
                LEFT JOIN public.wilayah w ON sj.rute_wilayah_id = w.id
                WHERE sj.pesanan_id = :id
                ORDER BY sj.dibuat_pada DESC
            ";
            $shippingHistory = Database::fetchAll($sqlShipping, ['id' => $id]);

            // Generate presigned/proxy URLs untuk foto pengiriman di setiap riwayat surat jalan
            foreach ($shippingHistory as &$sj) {
                if (!empty($sj['bukti_terima_foto'])) {
                    $sj['bukti_terima_foto'] = \App\Helpers\Upload::presignedUrl($sj['bukti_terima_foto']) ?: $sj['bukti_terima_foto'];
                }
                if (!empty($sj['foto_bukti_gagal'])) {
                    $sj['foto_bukti_gagal'] = \App\Helpers\Upload::presignedUrl($sj['foto_bukti_gagal']) ?: $sj['foto_bukti_gagal'];
                }
            }
            unset($sj);

            // 6. Audit Trail Aktivitas Terkait Pesanan Ini
            $sqlLogs = "
                SELECT la.id, la.nama_aktor, la.peran_aktor, la.kategori_aktivitas, la.jenis_aksi,
                       la.deskripsi_aktivitas, la.waktu_kejadian, la.tabel_terdampak
                FROM public.log_aktivitas la
                WHERE (la.tabel_terdampak = 'pesanan' AND la.id_referensi = :id)
                   OR (la.tabel_terdampak = 'surat_jalan' AND la.id_referensi IN (SELECT id FROM public.surat_jalan WHERE pesanan_id = :id_sj))
                ORDER BY la.waktu_kejadian DESC
                LIMIT 50
            ";
            $activityLogs = Database::fetchAll($sqlLogs, ['id' => (string)$id, 'id_sj' => $id]);

            echo json_encode([
                'success' => true,
                'order' => $order,
                'items' => $items,
                'drivers' => $drivers,
                'cashAccounts' => $cashAccounts,
                'payments' => $payments,
                'shippingHistory' => $shippingHistory,
                'activityLogs' => $activityLogs,
            ]);
            exit;

        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Halaman Input Pesanan Pelanggan / Faktur Toko Baru
     */
    public function create(): void
    {
        Auth::requirePermission('orders.create');

        try {
            // 1. Ambil Master Pelanggan Toko (Exclude Pelanggan Kasir Ritel UMUM/CASH)
            $custSql = "
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.nomor_whatsapp, 
                       p.alamat_lengkap, p.tipe_pembayaran_default, p.is_konsinyasi, p.sales_driver_id,
                       COALESCE(gp.default_level_harga, 1) as level_harga,
                       gp.nama_grup as nama_grup_harga,
                       COALESCE(gp.diskon_persen_default, 0) as grup_diskon_persen,
                       COALESCE(gp.diskon_nominal_default, 0) as grup_diskon_nominal
                FROM public.pelanggan p
                JOIN public.grup_pelanggan gp ON p.grup_pelanggan_id = gp.id
                WHERE p.status_aktif = TRUE 
                  AND p.kode_pelanggan != 'CUST-001'
                  AND p.nama_toko NOT ILIKE '%UMUM%'
                  AND p.nama_toko NOT ILIKE '%CASH%'
            ";
            $custParams = [];

            if (!Auth::can('orders.view_all')) {
                $myEmpId = Auth::employeeId();
                if ($myEmpId) {
                    $custSql .= " AND p.sales_driver_id = :emp_id";
                    $custParams['emp_id'] = $myEmpId;
                }
            }

            $custSql .= " ORDER BY p.nama_toko ASC";
            $customers = Database::fetchAll($custSql, $custParams);

            // 2. Ambil Master Petugas Pengantar (Driver & Sales) Lengkap dengan Plat Nomor
            $drivers = Database::fetchAll("
                SELECT id, nik, nama_karyawan, nomor_telepon, nomor_polisi_kendaraan
                FROM public.v_karyawan_info
                WHERE status_aktif = TRUE
                ORDER BY nama_karyawan ASC
            ");

            // 3. Ambil Master Akun Kas Aktif
            $cashAccounts = Database::fetchAll("
                SELECT id, nama_akun, saldo_saat_ini, is_default_pos
                FROM public.akun_kas
                WHERE status_aktif = TRUE
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            // 4. Ambil Katalog Barang Jadi (137 SKU) dengan Relasi Merek
            $products = Database::fetchAll("
                SELECT i.id, i.grup_id, i.kode_sku, i.nama_item,
                       i.satuan_dasar, i.stok_fisik_saat_ini,
                       gp.nama_grup, gp.kode_grup, gp.barcode_universal,
                       gp.merek_id, m.nama_merek
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                LEFT JOIN public.merek m ON gp.merek_id = m.id
                WHERE i.status_aktif = TRUE AND i.status_jual = TRUE AND i.tipe_item = 'barang_jadi'
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ");

            // 5. Ambil Matriks Harga Level Grup Produk (Murni per pcs)
            $rawLevelPrices = Database::fetchAll("
                SELECT grup_produk_id, level_harga, harga_jual_pcs
                FROM public.grup_produk_harga_level
            ");
            $priceMatrix = [];
            foreach ($rawLevelPrices as $lp) {
                $priceMatrix[$lp['grup_produk_id']][$lp['level_harga']] = [
                    'pcs' => (float)$lp['harga_jual_pcs']
                ];
            }

            // 5b. Ambil Matriks Level Harga & Diskon per Merek untuk Grup Pelanggan
            $rawGroupBrandLevels = Database::fetchAll("
                SELECT grup_pelanggan_id, merek_id, level_harga, diskon_persen, diskon_nominal, is_dijual
                FROM public.grup_pelanggan_level_merek
            ");
            $groupBrandLevelsMap = [];
            foreach ($rawGroupBrandLevels as $gbl) {
                $groupBrandLevelsMap[$gbl['grup_pelanggan_id']][$gbl['merek_id']] = [
                    'level_harga' => (int)$gbl['level_harga'],
                    'diskon_persen' => (float)$gbl['diskon_persen'],
                    'diskon_nominal' => (float)$gbl['diskon_nominal'],
                    'is_dijual' => (bool)$gbl['is_dijual']
                ];
            }

            // 6. Whitelist item pelanggan
            $rawWhitelist = Database::fetchAll("SELECT pelanggan_id, item_id FROM public.pelanggan_item");
            $whitelistMap = [];
            foreach ($rawWhitelist as $w) {
                $whitelistMap[$w['pelanggan_id']][] = $w['item_id'];
            }

            // 7. Auto Generate Nomor Faktur Format: KRS-YYMM-XXXX
            $autoNota = DocumentNumber::suggestOrderNumber();

            $this->view('customer_orders.create', [
                'pageTitle' => 'Input Pesanan Pelanggan Baru',
                'pageSubtitle' => 'Penerbitan Faktur Penjualan Reguler Toko Mitra',
                'customers' => $customers,
                'drivers' => $drivers,
                'cashAccounts' => $cashAccounts,
                'products' => $products,
                'priceMatrix' => $priceMatrix,
                'groupBrandLevelsMap' => $groupBrandLevelsMap,
                'whitelistMap' => $whitelistMap,
                'autoNota' => $autoNota,
            ]);

        } catch (Throwable $e) {
            $this->flashError("Gagal memuat formulir pesanan baru: " . $e->getMessage());
            $this->redirect('/customer-orders');
        }
    }

    /**
     * Simpan Transaksi Pesanan Pelanggan (Atomic Transaction)
     */
    public function store(): void
    {
        Auth::requirePermission('orders.create');

        $nomorNota = trim((string)$this->input('nomor_nota'));
        $pelangganId = $this->input('pelanggan_id');
        $tanggalPesanan = $this->input('tanggal_pesanan', date('Y-m-d'));
        $tipePembayaran = $this->input('tipe_pembayaran', 'cash');
        $tanggalJatuhTempo = $this->input('tanggal_jatuh_tempo') ?: null;
        if ($tipePembayaran === 'tempo_tanggal' && empty($tanggalJatuhTempo)) {
            $this->flashError('Untuk tipe pembayaran Tempo Tanggal, Tanggal Jatuh Tempo wajib diisi.');
            $this->redirect('/customer-orders/create');
            return;
        }
        if ($tipePembayaran === 'tempo_faktur') {
            $tanggalJatuhTempo = null;
        }
        $akunKasId = $this->input('akun_kas_id') ?: null;
        $nominalDibayarInput = (float)preg_replace('/[^0-9]/', '', (string)$this->input('nominal_dibayar', '0'));
        $catatan = trim((string)$this->input('catatan', ''));

        $itemsJson = $this->input('items_json');
        $items = json_decode((string)$itemsJson, true);

        if (empty($nomorNota) || empty($pelangganId) || empty($items) || !is_array($items)) {
            $this->flashError('Mohon lengkapi data Toko dan minimal 1 produk.');
            $this->redirect('/customer-orders/create');
            return;
        }

        // Filter item yang valid (item_id terisi dan qty > 0)
        $validItems = [];
        foreach ($items as $it) {
            $qty = (int)($it['qty'] ?? 0);
            if (!empty($it['item_id']) && $qty > 0) {
                $validItems[] = $it;
            }
        }

        if (empty($validItems)) {
            $this->flashError('Pesanan harus memiliki minimal 1 produk dengan kuantitas lebih dari 0.');
            $this->redirect('/customer-orders/create');
            return;
        }

        $items = $validItems;

        try {
            $pdo = Database::getConnection();
            
            // Cek apakah pelanggan ini adalah konsinyasi
            $stmtPelanggan = $pdo->prepare("SELECT nama_toko, is_konsinyasi, sales_driver_id, wilayah_id FROM public.pelanggan WHERE id = :id");
            $stmtPelanggan->execute(['id' => $pelangganId]);
            $pelangganInfo = $stmtPelanggan->fetch(\PDO::FETCH_ASSOC);
            
            $isKonsinyasi = false;
            $salesDriverId = null;
            $ruteWilayahId = null;
            
            if ($pelangganInfo) {
                $isKonsinyasi = (bool)$pelangganInfo['is_konsinyasi'];
                $driverInput = $this->input('sales_driver_id') ?: null;
                $salesDriverId = $driverInput ?: ($pelangganInfo['sales_driver_id'] ?? null);
                $ruteWilayahId = $pelangganInfo['wilayah_id'];
            }
            
            // Atur atribut sesuai tipe pesanan
            $isTagihan = true;
            $statusSuratJalanAwal = 'siap_kirim';
            
            if ($isKonsinyasi) {
                $tipePembayaran = 'konsinyasi';
                $isTagihan = false; // PRD: Kiriman konsinyasi bukan tagihan riil
                $tanggalJatuhTempo = null;
                $statusSuratJalanAwal = 'siap_kirim'; // Langsung siap kirim
            }

            $pdo->beginTransaction();

            // Anti-Collision: Advisory lock & sequential numbering
            $checkNota = $pdo->prepare("SELECT count(*) FROM public.pesanan WHERE nomor_nota = :nota");
            $checkNota->execute(['nota' => $nomorNota]);
            if ((int)$checkNota->fetchColumn() > 0 || empty($nomorNota)) {
                $nomorNota = DocumentNumber::nextOrderNumber($pdo);
            } else {
                $pdo->query("SELECT pg_advisory_xact_lock(hashtext('pesanan_nomor_nota'))");
            }

            // 1. Hitung total bruto & netto (Untuk Konsinyasi, pakai HPP)
            $totalBruto = 0;
            $totalDiskonItem = 0;

            foreach ($items as &$it) {
                $qty = (int)($it['qty'] ?? 1);
                $diskon = (float)($it['diskon'] ?? 0);
                
                // Ambil data HPP untuk snapshot historis margin
                $hppData = Database::fetchOne("SELECT harga_pokok_pembelian FROM public.item WHERE id = :id", ['id' => $it['item_id']]);
                $hppSatuan = (float)($hppData['harga_pokok_pembelian'] ?? 0);
                $it['hpp'] = $hppSatuan;

                if ($isKonsinyasi) {
                    // Pakai HPP untuk valuasi internal konsinyasi
                    $harga = $hppSatuan;
                    $it['harga'] = $harga;
                } else {
                    // Validasi Server-Side Anti-Tampering: Hitung harga jual resmi dari Stored Procedure
                    $pricingRow = Database::fetchOne(
                        "SELECT public.fn_hitung_harga_jual_item(:item_id, :pelanggan_id) AS pricing",
                        ['item_id' => $it['item_id'], 'pelanggan_id' => $pelangganId]
                    );
                    $pricingData = json_decode($pricingRow['pricing'] ?? '{}', true);

                    // PILIHAN B (Strict Rejection): Tolak jika harga level belum diset di /pricing
                    if (!empty($pricingData['error'])) {
                        $pdo->rollBack();
                        $errMsg = $pricingData['message'] ?? 'Harga level untuk produk ini belum dikonfigurasi di /pricing.';
                        $this->flashError("Gagal memproses pesanan: {$errMsg}");
                        $this->redirect('/customer-orders/create');
                        return;
                    }

                    $hargaResmi = (float)($pricingData['harga_pcs_bruto'] ?? $pricingData['harga_pcs_netto'] ?? 0);

                    $hargaInput = (float)($it['harga'] ?? 0);

                    // Anti-Tampering: jika hargaInput <= 0 atau sengaja diturunkan di bawah harga resmi, paksa pakai hargaResmi
                    if ($hargaResmi > 0 && ($hargaInput < $hargaResmi || $hargaInput <= 0)) {
                        $harga = $hargaResmi;
                    } else {
                        $harga = ($hargaInput > 0) ? $hargaInput : $hargaResmi;
                    }
                    $it['harga'] = $harga;
                }
                
                $subtotal = max(0, ($qty * $harga) - $diskon);
                $it['subtotal'] = $subtotal;
                $totalBruto += ($qty * $harga);
                $totalDiskonItem += $diskon;
            }
            unset($it);

            $diskonFaktur = (float)preg_replace('/[^0-9]/', '', (string)$this->input('diskon_faktur', '0'));
            $totalDiskon = $totalDiskonItem + $diskonFaktur;
            $totalNetto = max(0, $totalBruto - $totalDiskon);

            // 2. Skema Pembayaran
            if ($isKonsinyasi) {
                $totalDibayar = 0.0;
                $sisaTagihan = $totalNetto; // Nilai HPP internal
                $statusBayar = 'belum_lunas';
            } elseif ($tipePembayaran === 'cash' || $tipePembayaran === 'qris' || $tipePembayaran === 'transfer') {
                $totalDibayar = $totalNetto;
                $settlement = PaymentHelper::calculateSettlement($totalNetto, $totalDibayar);
                $sisaTagihan = $settlement['sisa_tagihan'];
                $statusBayar = $settlement['status_pembayaran'];
            } elseif ($tipePembayaran === 'sebagian') {
                // Pengaman Finansial Kuat: Validasi Ketat Nominal Uang Muka (DP)
                if ($nominalDibayarInput <= 0) {
                    $pdo->rollBack();
                    $this->flashError('Transaksi ditolak: Skema Pembayaran Sebagian (DP) mewajibkan nominal uang muka lebih dari Rp 0.');
                    $this->redirect('/customer-orders/create');
                    return;
                }
                if ($nominalDibayarInput > $totalNetto) {
                    $pdo->rollBack();
                    $this->flashError(sprintf(
                        'Transaksi ditolak: Nominal DP (Rp %s) melebihi Total Nilai PO (Rp %s). Penginputan uang muka tidak valid.',
                        number_format($nominalDibayarInput, 0, ',', '.'),
                        number_format($totalNetto, 0, ',', '.')
                    ));
                    $this->redirect('/customer-orders/create');
                    return;
                }
                $totalDibayar = $nominalDibayarInput;
                $settlement = PaymentHelper::calculateSettlement($totalNetto, $totalDibayar);
                $sisaTagihan = $settlement['sisa_tagihan'];
                $statusBayar = $settlement['status_pembayaran'];
            } else {
                $totalDibayar = 0.0;
                $settlement = PaymentHelper::calculateSettlement($totalNetto, $totalDibayar);
                $sisaTagihan = $settlement['sisa_tagihan'];
                $statusBayar = $settlement['status_pembayaran'];
            }

            // 3. Insert Header Pesanan (Tahap 1: Status PO)
            $stmt = $pdo->prepare("
                INSERT INTO public.pesanan (
                    nomor_nota, pelanggan_id, sales_driver_id, tanggal_pesanan,
                    total_bruto, total_diskon, total_netto, total_dibayar, sisa_tagihan,
                    tipe_pembayaran, tanggal_jatuh_tempo, akun_kas_id,
                    status_pembayaran, status_pemrosesan, catatan, is_tagihan,
                    dibuat_pada
                ) VALUES (
                    :nota, :pelanggan, :driver, :tgl,
                    :bruto, :diskon, :netto, :dibayar, :sisa,
                    :tipe, :tempo, :akun_kas,
                    :status_bayar, 'po', :catatan, :is_tagihan,
                    NOW()
                ) RETURNING id
            ");
            $stmt->execute([
                'nota' => $nomorNota,
                'pelanggan' => $pelangganId,
                'driver' => $salesDriverId,
                'tgl' => $tanggalPesanan,
                'bruto' => $totalBruto,
                'diskon' => $totalDiskon,
                'netto' => $totalNetto,
                'dibayar' => $totalDibayar,
                'sisa' => $sisaTagihan,
                'tipe' => $tipePembayaran,
                'tempo' => $tanggalJatuhTempo,
                'akun_kas' => ($totalDibayar > 0) ? $akunKasId : null,
                'status_bayar' => $statusBayar,
                'catatan' => $catatan ?: 'Pesanan Toko Mitra (PO)',
                'is_tagihan' => $isTagihan ? 'true' : 'false'
            ]);

            $orderId = $stmt->fetchColumn();

            // 4. Insert Detail Items (Stok gudang belum dipotong di tahap PO)
            $stmtItem = $pdo->prepare("
                INSERT INTO public.item_pesanan (
                    pesanan_id, item_id, kuantitas_satuan_dasar,
                    harga_satuan_deal, diskon_item_nominal, is_bonus, subtotal, harga_pokok_satuan, dibuat_pada
                ) VALUES (
                    :pesanan_id, :item_id, :qty_dasar,
                    :harga, :diskon, :bonus, :subtotal, :hpp, NOW()
                )
            ");

            foreach ($items as $it) {
                $itemId = $it['item_id'];
                $qtyPcs = (int)($it['qty'] ?? 1);
                $harga = (float)($it['harga'] ?? 0);
                $diskon = (float)($it['diskon'] ?? 0);
                $subtotal = $it['subtotal'];
                $isBonus = !empty($it['is_bonus']);
                $hpp = (float)($it['hpp'] ?? 0);

                $stmtItem->execute([
                    'pesanan_id' => $orderId,
                    'item_id' => $itemId,
                    'qty_dasar' => $qtyPcs,
                    'harga' => $harga,
                    'diskon' => $diskon,
                    'bonus' => $isBonus ? 'true' : 'false',
                    'subtotal' => $subtotal,
                    'hpp' => $hpp,
                ]);
            }

            // 5. Catat Penerimaan Kas Masuk (Uang Muka / Pelunasan Langsung) ke Buku Kas Arus Kas
            if ($totalDibayar > 0 && !empty($akunKasId)) {
                $stmtKas = $pdo->prepare("SELECT saldo_saat_ini, nama_akun FROM public.akun_kas WHERE id = :id FOR UPDATE");
                $stmtKas->execute(['id' => $akunKasId]);
                $akunKas = $stmtKas->fetch(\PDO::FETCH_ASSOC);

                if ($akunKas) {
                    $saldoLama = (float)($akunKas['saldo_saat_ini'] ?? 0);
                    $saldoBaru = $saldoLama + $totalDibayar;

                    $pdo->prepare("
                        UPDATE public.akun_kas
                        SET saldo_saat_ini = :saldo,
                            diubah_pada = NOW()
                        WHERE id = :akun_kas
                    ")->execute([
                        'saldo' => $saldoBaru,
                        'akun_kas' => $akunKasId,
                    ]);

                    $tokoName = $pelangganInfo['nama_toko'] ?? '';
                    $keteranganKas = ($statusBayar === 'lunas')
                        ? "Penerimaan Pembayaran Lunas Pesanan Toko #{$nomorNota}" . ($tokoName ? " ({$tokoName})" : "")
                        : "Penerimaan Uang Muka (DP) Pesanan Toko #{$nomorNota}" . ($tokoName ? " ({$tokoName})" : "");

                    $voucherNo = CashVoucher::generate('masuk', $tanggalPesanan, $pdo);

                    $pdo->prepare("
                        INSERT INTO public.arus_kas (
                            nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                            keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                        ) VALUES (
                            :nomor_tx, :akun_kas, :tgl, 'masuk', 'penjualan', :nominal,
                            :ket, 'pesanan', :ref_id, :saldo_berjalan, :user_id, NOW()
                        )
                    ")->execute([
                        'nomor_tx' => $voucherNo,
                        'akun_kas' => $akunKasId,
                        'tgl' => $tanggalPesanan,
                        'nominal' => $totalDibayar,
                        'ket' => $keteranganKas,
                        'ref_id' => $orderId,
                        'saldo_berjalan' => $saldoBaru,
                        'user_id' => Auth::id() ?: null,
                    ]);
                }
            }

            $pdo->commit();

            $this->flashSuccess("Purchase Order (PO) #{$nomorNota} berhasil diterbitkan dan masuk ke antrean Daftar PO Gudang.");
            $this->redirect('/customer-orders');
        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menerbitkan PO: ' . $e->getMessage());
            $this->redirect('/customer-orders/create');
        }
    }
    /**
     * Tampilkan Halaman Edit Pesanan Pelanggan
     */
    public function edit(): void
    {
        Auth::requirePermission(['orders.edit_all', 'orders.edit_assigned']);

        $id = (string)$this->input('id', '');
        if (empty($id)) {
            $this->flashError('ID Pesanan tidak valid.');
            $this->redirectBack('/customer-orders');
            return;
        }

        try {
            $order = Database::fetchOne("
                SELECT p.*, pel.nama_toko, pel.kode_pelanggan, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap,
                       pel.sales_driver_id as pel_sales_id,
                       COALESCE(gp.default_level_harga, 1) as level_harga,
                       gp.nama_grup as nama_grup_harga,
                       COALESCE(gp.diskon_persen_default, 0) as grup_diskon_persen,
                       COALESCE(gp.diskon_nominal_default, 0) as grup_diskon_nominal
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                JOIN public.grup_pelanggan gp ON pel.grup_pelanggan_id = gp.id
                WHERE p.id = :id
            ", ['id' => $id]);

            if (!$order) {
                $this->flashError('Pesanan tidak ditemukan.');
                $this->redirectBack('/customer-orders');
                return;
            }

            // Scope Check: Jika hanya punya edit_assigned, cek toko binaan
            if (!Auth::can('orders.edit_all')) {
                $myEmpId = Auth::employeeId();
                if ($myEmpId && $order['pel_sales_id'] !== $myEmpId && $order['sales_driver_id'] !== $myEmpId) {
                    $this->flashError('Akses Ditolak: Anda hanya diperbolehkan mengedit pesanan toko binaan Anda.');
                    $this->redirectBack('/customer-orders');
                    return;
                }
            }

            // Validasi Surat Jalan: Jika sudah dibuat surat jalan aktif, tolak edit (abaikan surat jalan gagal_kirim atau dibatalkan)
            $sj = Database::fetchOne("
                SELECT id, nomor_surat_jalan, status_surat_jalan 
                FROM public.surat_jalan 
                WHERE pesanan_id = :id AND status_surat_jalan NOT IN ('dibatalkan', 'gagal_kirim')
                LIMIT 1
            ", ['id' => $id]);

            if ($sj) {
                $this->flashError("Pesanan ini sedang memiliki Surat Jalan aktif (#{$sj['nomor_surat_jalan']}). Untuk mengedit pesanan, silakan selesaikan atau batalkan Surat Jalan tersebut terlebih dahulu.");
                $this->redirectBack('/customer-orders');
                return;
            }

            // Validasi Status: Izinkan edit jika pesanan berstatus draf PO atau Gagal Dikirim (untuk kirim ulang)
            $isAllowedStatus = in_array($order['status_pemrosesan'] ?? '', ['po', 'gagal_dikirim'], true);
            if (!$isAllowedStatus) {
                $statusLabel = strtoupper(str_replace('_', ' ', $order['status_pemrosesan'] ?? ''));
                $this->flashError("Hanya pesanan berstatus 'PO' atau 'Gagal Dikirim' yang dapat diedit. Pesanan ini sudah berstatus '{$statusLabel}'.");
                $this->redirectBack('/customer-orders');
                return;
            }

            // Ambil Detail Items yang sudah ada
            $existingItems = Database::fetchAll("
                SELECT ip.id, ip.item_id, ip.kuantitas_satuan_dasar as qty, ip.harga_satuan_deal as harga,
                       ip.diskon_item_nominal as diskon, ip.subtotal, ip.is_bonus,
                       i.nama_item, i.kode_sku, i.stok_fisik_saat_ini,
                       gp.nama_grup, gp.kode_grup, gp.barcode_universal
                FROM public.item_pesanan ip
                JOIN public.item i ON ip.item_id = i.id
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE ip.pesanan_id = :id
                ORDER BY i.nama_item ASC
            ", ['id' => $id]);

            // Ambil Master Petugas Pengantar (Driver & Sales)
            $drivers = Database::fetchAll("
                SELECT id, nik, nama_karyawan, nomor_telepon, nomor_polisi_kendaraan
                FROM public.v_karyawan_info
                WHERE status_aktif = TRUE
                ORDER BY nama_karyawan ASC
            ");

            // Ambil Master Akun Kas Aktif
            $cashAccounts = Database::fetchAll("
                SELECT id, nama_akun, saldo_saat_ini, is_default_pos
                FROM public.akun_kas
                WHERE status_aktif = TRUE
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            // Ambil Katalog Barang Jadi dengan Relasi Merek
            $products = Database::fetchAll("
                SELECT i.id, i.grup_id, i.kode_sku, i.nama_item,
                       i.satuan_dasar, i.stok_fisik_saat_ini,
                       gp.nama_grup, gp.kode_grup, gp.barcode_universal,
                       gp.merek_id, m.nama_merek
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                LEFT JOIN public.merek m ON gp.merek_id = m.id
                WHERE i.status_aktif = TRUE AND i.status_jual = TRUE AND i.tipe_item = 'barang_jadi'
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ");

            // Ambil Matriks Harga Level Grup Produk (Murni per pcs)
            $rawLevelPrices = Database::fetchAll("
                SELECT grup_produk_id, level_harga, harga_jual_pcs
                FROM public.grup_produk_harga_level
            ");
            $priceMatrix = [];
            foreach ($rawLevelPrices as $lp) {
                $priceMatrix[$lp['grup_produk_id']][$lp['level_harga']] = [
                    'pcs' => (float)$lp['harga_jual_pcs']
                ];
            }

            // Ambil Matriks Level Harga & Diskon per Merek untuk Grup Pelanggan
            $rawGroupBrandLevels = Database::fetchAll("
                SELECT grup_pelanggan_id, merek_id, level_harga, diskon_persen, diskon_nominal, is_dijual
                FROM public.grup_pelanggan_level_merek
            ");
            $groupBrandLevelsMap = [];
            foreach ($rawGroupBrandLevels as $gbl) {
                $groupBrandLevelsMap[$gbl['grup_pelanggan_id']][$gbl['merek_id']] = [
                    'level_harga' => (int)$gbl['level_harga'],
                    'diskon_persen' => (float)$gbl['diskon_persen'],
                    'diskon_nominal' => (float)$gbl['diskon_nominal'],
                    'is_dijual' => (bool)$gbl['is_dijual']
                ];
            }

            // Whitelist item pelanggan
            $rawWhitelist = Database::fetchAll("SELECT pelanggan_id, item_id FROM public.pelanggan_item WHERE pelanggan_id = :pid", ['pid' => $order['pelanggan_id']]);
            $whitelistMap = [$order['pelanggan_id'] => array_column($rawWhitelist, 'item_id')];

            $isRetryEdit = ($order['status_pemrosesan'] === 'gagal_dikirim') || ($this->input('retry') === '1');

            $this->view('customer_orders.edit', [
                'pageTitle' => ($isRetryEdit ? 'Kirim Ulang Pesanan #' : 'Edit Pesanan Pelanggan #') . $order['nomor_nota'],
                'pageSubtitle' => $isRetryEdit ? 'Sesuaikan rincian produk sebelum dijadwalkan kirim ulang ke gudang' : 'Perbarui rincian produk, kuantiti, dan skema harga pesanan',
                'order' => $order,
                'existingItems' => $existingItems,
                'drivers' => $drivers,
                'cashAccounts' => $cashAccounts,
                'products' => $products,
                'priceMatrix' => $priceMatrix,
                'groupBrandLevelsMap' => $groupBrandLevelsMap,
                'whitelistMap' => $whitelistMap,
                'isRetryEdit' => $isRetryEdit,
            ]);

        } catch (Throwable $e) {
            $this->flashError('Terjadi kesalahan saat membuka halaman edit: ' . $e->getMessage());
            $this->redirect('/customer-orders');
        }
    }

    /**
     * Proses Simpan Pembaruan Pesanan (Update)
     */
    public function update(): void
    {
        Auth::requirePermission(['orders.edit_all', 'orders.edit_assigned']);

        $id = (string)$this->input('id', '');
        $tanggalPesanan = $this->input('tanggal_pesanan', date('Y-m-d'));
        $tipePembayaran = $this->input('tipe_pembayaran', 'cash');
        $tanggalJatuhTempo = $this->input('tanggal_jatuh_tempo') ?: null;
        if ($tipePembayaran === 'tempo_tanggal' && empty($tanggalJatuhTempo)) {
            $this->flashError('Untuk tipe pembayaran Tempo Tanggal, Tanggal Jatuh Tempo wajib diisi.');
            $this->redirect('/customer-orders/edit?id=' . urlencode($id));
            return;
        }
        if ($tipePembayaran === 'tempo_faktur') {
            $tanggalJatuhTempo = null;
        }
        $catatan = trim((string)$this->input('catatan', ''));
        $driverId = $this->input('sales_driver_id') ?: null;

        $itemsJson = $this->input('items_json');
        $items = json_decode((string)$itemsJson, true);

        if (empty($id) || empty($items) || !is_array($items)) {
            $this->flashError('Mohon masukkan minimal 1 produk yang valid.');
            $this->redirect('/customer-orders/edit?id=' . urlencode($id));
            return;
        }

        // Filter valid items
        $validItems = [];
        foreach ($items as $it) {
            $qty = (int)($it['qty'] ?? 0);
            if (!empty($it['item_id']) && $qty > 0) {
                $validItems[] = $it;
            }
        }

        if (empty($validItems)) {
            $this->flashError('Pesanan harus memiliki minimal 1 produk dengan kuantitas lebih dari 0.');
            $this->redirect('/customer-orders/edit?id=' . urlencode($id));
            return;
        }

        $items = $validItems;

        try {
            $pdo = Database::getConnection();

            // Ambil order saat ini
            $order = Database::fetchOne("
                SELECT p.*, pel.nama_toko, pel.sales_driver_id as pel_sales_id, pel.is_konsinyasi
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                WHERE p.id = :id
            ", ['id' => $id]);

            if (!$order) {
                $this->flashError('Pesanan tidak ditemukan.');
                $this->redirect('/customer-orders');
                return;
            }

            // Scope Check
            if (!Auth::can('orders.edit_all')) {
                $myEmpId = Auth::employeeId();
                if ($myEmpId && $order['pel_sales_id'] !== $myEmpId && $order['sales_driver_id'] !== $myEmpId) {
                    $this->flashError('Akses Ditolak: Anda hanya diperbolehkan mengedit pesanan toko binaan Anda.');
                    $this->redirect('/customer-orders');
                    return;
                }
            }

            // Surat Jalan Check (abaikan surat jalan gagal_kirim atau dibatalkan)
            $sj = Database::fetchOne("
                SELECT id, nomor_surat_jalan 
                FROM public.surat_jalan 
                WHERE pesanan_id = :id AND status_surat_jalan NOT IN ('dibatalkan', 'gagal_kirim')
                LIMIT 1
            ", ['id' => $id]);

            if ($sj) {
                $this->flashError("Pesanan ini sedang memiliki Surat Jalan aktif (#{$sj['nomor_surat_jalan']}). Untuk mengedit pesanan, silakan selesaikan atau batalkan Surat Jalan tersebut terlebih dahulu.");
                $this->redirect('/customer-orders');
                return;
            }

            // Validasi Status: Hanya izinkan edit jika pesanan masih berstatus draf PO atau gagal dikirim
            $isAllowedStatus = in_array($order['status_pemrosesan'] ?? '', ['po', 'gagal_dikirim'], true);
            if (!$isAllowedStatus) {
                $statusLabel = strtoupper(str_replace('_', ' ', $order['status_pemrosesan'] ?? ''));
                $this->flashError("Hanya pesanan berstatus 'PO' atau 'Gagal Dikirim' yang dapat diedit. Pesanan ini sudah berstatus '{$statusLabel}'.");
                $this->redirect('/customer-orders');
                return;
            }

            $pdo->beginTransaction();

            $isKonsinyasi = (bool)$order['is_konsinyasi'];
            $isRetryFromFailed = ($order['status_pemrosesan'] === 'gagal_dikirim');
            $totalDibayarLama = (float)($order['total_dibayar'] ?? 0);
            $refundAkunKasId = $this->input('refund_akun_kas_id');
            $nominalRefundDilakukan = 0;

            // 1. Hapus detail item lama (Stok belum terpotong di tahap PO)
            $pdo->prepare("DELETE FROM public.item_pesanan WHERE pesanan_id = :id")->execute(['id' => $id]);

            // 2. Hitung total bruto & netto baru
            $totalBruto = 0;
            $totalDiskonItem = 0;

            foreach ($items as &$it) {
                $qty = (int)($it['qty'] ?? 1);
                $diskon = (float)($it['diskon'] ?? 0);

                // Ambil data HPP untuk snapshot historis margin
                $hppData = Database::fetchOne("SELECT harga_pokok_pembelian FROM public.item WHERE id = :id", ['id' => $it['item_id']]);
                $hppSatuan = (float)($hppData['harga_pokok_pembelian'] ?? 0);
                $it['hpp'] = $hppSatuan;

                if ($isKonsinyasi) {
                    $harga = $hppSatuan;
                    $it['harga'] = $harga;
                } else {
                    // Validasi Server-Side Anti-Tampering: Hitung harga jual resmi dari Stored Procedure
                    $pricingRow = Database::fetchOne(
                        "SELECT public.fn_hitung_harga_jual_item(:item_id, :pelanggan_id) AS pricing",
                        ['item_id' => $it['item_id'], 'pelanggan_id' => $order['pelanggan_id']]
                    );
                    $pricingData = json_decode($pricingRow['pricing'] ?? '{}', true);

                    // PILIHAN B (Strict Rejection): Tolak jika harga level belum diset di /pricing
                    if (!empty($pricingData['error'])) {
                        $pdo->rollBack();
                        $errMsg = $pricingData['message'] ?? 'Harga level untuk produk ini belum dikonfigurasi di /pricing.';
                        $this->flashError("Gagal memperbarui pesanan: {$errMsg}");
                        $this->redirect('/customer-orders/edit?id=' . urlencode((string)$orderId));
                        return;
                    }

                    $hargaResmi = (float)($pricingData['harga_pcs_bruto'] ?? $pricingData['harga_pcs_netto'] ?? 0);

                    $hargaInput = (float)($it['harga'] ?? 0);

                    // Anti-Tampering: jika hargaInput <= 0 atau sengaja diturunkan di bawah harga resmi, paksa pakai hargaResmi
                    if ($hargaResmi > 0 && ($hargaInput < $hargaResmi || $hargaInput <= 0)) {
                        $harga = $hargaResmi;
                    } else {
                        $harga = ($hargaInput > 0) ? $hargaInput : $hargaResmi;
                    }
                    $it['harga'] = $harga;
                }

                $subtotal = max(0, ($qty * $harga) - $diskon);
                $it['subtotal'] = $subtotal;
                $totalBruto += ($qty * $harga);
                $totalDiskonItem += $diskon;
            }
            unset($it);

            $diskonFaktur = (float)preg_replace('/[^0-9]/', '', (string)$this->input('diskon_faktur', '0'));
            $totalDiskon = $totalDiskonItem + $diskonFaktur;
            $totalNetto = max(0, $totalBruto - $totalDiskon);

            // Hitung status dan nominal pembayaran via PaymentHelper
            if ($isKonsinyasi) {
                $totalDibayar = 0.0;
                $sisaTagihan = $totalNetto;
                $statusBayar = 'belum_lunas';
            } elseif ($isRetryFromFailed) {
                // Skema pesanan kirim ulang yang diedit
                if ($totalNetto < $totalDibayarLama) {
                    $selisihRefund = $totalDibayarLama - $totalNetto;
                    if (!empty($refundAkunKasId)) {
                        $akunKasRefund = Database::fetchOne("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE id = :id AND status_aktif = TRUE FOR UPDATE", ['id' => $refundAkunKasId]);
                        if (!$akunKasRefund) {
                            $pdo->rollBack();
                            $this->flashError("Akun kas pengembalian dana (refund) tidak valid atau nonaktif.");
                            $this->redirect('/customer-orders/edit?id=' . urlencode($id));
                            return;
                        }
                        $saldoKasBaru = max(0, (float)$akunKasRefund['saldo_saat_ini'] - $selisihRefund);
                        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo, diubah_pada = NOW() WHERE id = :id")->execute(['saldo' => $saldoKasBaru, 'id' => $refundAkunKasId]);
                        $pdo->prepare("
                            INSERT INTO public.arus_kas (
                                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                                keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                            ) VALUES (
                                :akun_kas, CURRENT_DATE, 'keluar', 'koreksi', :nominal,
                                :ket, 'pesanan', :ref_id, :saldo_berjalan, :user_id, NOW()
                            )
                        ")->execute([
                            'akun_kas' => $refundAkunKasId,
                            'nominal' => $selisihRefund,
                            'ket' => "Refund Kelebihan Bayar Pesanan Gagal Kirim #{$order['nomor_nota']} ({$order['nama_toko']})",
                            'ref_id' => $id,
                            'saldo_berjalan' => $saldoKasBaru,
                            'user_id' => Auth::id() ?: null
                        ]);
                        ActivityLog::log(
                            'keuangan',
                            'REFUND',
                            "Pengembalian dana kelebihan bayar pesanan #{$order['nomor_nota']} sebesar " . Format::rupiah($selisihRefund) . " dari {$akunKasRefund['nama_akun']}",
                            'pesanan',
                            (string)$id
                        );
                        $nominalRefundDilakukan = $selisihRefund;
                    }
                    $totalDibayar = $totalNetto;
                    $settlement = PaymentHelper::calculateSettlement($totalNetto, $totalDibayar);
                    $sisaTagihan = $settlement['sisa_tagihan'];
                    $statusBayar = $settlement['status_pembayaran'];
                } else {
                    $totalDibayar = $totalDibayarLama;
                    $settlement = PaymentHelper::calculateSettlement($totalNetto, $totalDibayar);
                    $sisaTagihan = $settlement['sisa_tagihan'];
                    $statusBayar = $settlement['status_pembayaran'];
                }
            } elseif ($tipePembayaran === 'cash' || $tipePembayaran === 'qris' || $tipePembayaran === 'transfer') {
                $totalDibayar = $totalNetto;
                $settlement = PaymentHelper::calculateSettlement($totalNetto, $totalDibayar);
                $sisaTagihan = $settlement['sisa_tagihan'];
                $statusBayar = $settlement['status_pembayaran'];
            } elseif ($tipePembayaran === 'sebagian') {
                $nominalDibayar = (float)preg_replace('/[^0-9]/', '', (string)$this->input('nominal_dibayar', '0'));
                if ($nominalDibayar <= 0) {
                    $pdo->rollBack();
                    $this->flashError('Pembaruan pesanan ditolak: Skema Pembayaran Sebagian (DP) mewajibkan nominal uang muka lebih dari Rp 0.');
                    $this->redirect('/customer-orders/edit?id=' . urlencode($id));
                    return;
                }
                if ($nominalDibayar > $totalNetto) {
                    $pdo->rollBack();
                    $this->flashError(sprintf(
                        'Pembaruan pesanan ditolak: Nominal DP (Rp %s) melebihi Total Nilai PO (Rp %s). Penginputan uang muka tidak valid.',
                        number_format($nominalDibayar, 0, ',', '.'),
                        number_format($totalNetto, 0, ',', '.')
                    ));
                    $this->redirect('/customer-orders/edit?id=' . urlencode($id));
                    return;
                }
                $totalDibayar = $nominalDibayar;
                $settlement = PaymentHelper::calculateSettlement($totalNetto, $totalDibayar);
                $sisaTagihan = $settlement['sisa_tagihan'];
                $statusBayar = $settlement['status_pembayaran'];
            } else {
                $totalDibayar = 0.0;
                $settlement = PaymentHelper::calculateSettlement($totalNetto, $totalDibayar);
                $sisaTagihan = $settlement['sisa_tagihan'];
                $statusBayar = $settlement['status_pembayaran'];
            }

            // Driver tetap dari pesanan / profil toko (tidak diubah di form PO edit)
            $driverId = $order['sales_driver_id'];
            $isTagihan = $isKonsinyasi ? false : true;

            $catatanFinal = $catatan ?: 'Pesanan Toko Mitra (Diperbarui)';
            if ($isRetryFromFailed && strpos($catatanFinal, '[Kirim Ulang]') === false) {
                $catatanFinal .= "\n[Kirim Ulang: Diedit]";
            }

            // 3. Update Header Pesanan (kembalikan status_pemrosesan ke 'po')
            $stmtUpdateOrder = $pdo->prepare("
                UPDATE public.pesanan SET
                    tanggal_pesanan = :tgl,
                    total_bruto = :bruto,
                    total_diskon = :diskon,
                    total_netto = :netto,
                    total_dibayar = :dibayar,
                    sisa_tagihan = :sisa,
                    status_pembayaran = :status_bayar,
                    tipe_pembayaran = :tipe,
                    tanggal_jatuh_tempo = :tempo,
                    sales_driver_id = :driver_id,
                    catatan = :catatan,
                    is_tagihan = :is_tagihan,
                    status_pemrosesan = 'po',
                    waktu_gagal_kirim = NULL,
                    diubah_pada = NOW()
                WHERE id = :id
            ");
            $stmtUpdateOrder->execute([
                'tgl' => $tanggalPesanan,
                'bruto' => $totalBruto,
                'diskon' => $totalDiskon,
                'netto' => $totalNetto,
                'dibayar' => $totalDibayar,
                'sisa' => $sisaTagihan,
                'status_bayar' => $statusBayar,
                'tipe' => $tipePembayaran,
                'tempo' => $tanggalJatuhTempo,
                'driver_id' => $driverId,
                'catatan' => $catatanFinal,
                'is_tagihan' => $isTagihan ? 'true' : 'false',
                'id' => $id,
            ]);

            // 4. Insert Detail Items Baru (Stok gudang belum dipotong di tahap PO)
            $stmtItem = $pdo->prepare("
                INSERT INTO public.item_pesanan (
                    pesanan_id, item_id, kuantitas_satuan_dasar,
                    harga_satuan_deal, diskon_item_nominal, is_bonus, subtotal, harga_pokok_satuan, dibuat_pada
                ) VALUES (
                    :pesanan_id, :item_id, :qty_dasar,
                    :harga, :diskon, :bonus, :subtotal, :hpp, NOW()
                )
            ");

            foreach ($items as $it) {
                $itemId = $it['item_id'];
                $qtyPcs = (int)($it['qty'] ?? 1);
                $harga = (float)($it['harga'] ?? 0);
                $diskon = (float)($it['diskon'] ?? 0);
                $subtotal = $it['subtotal'];
                $isBonus = !empty($it['is_bonus']);
                $hpp = (float)($it['hpp'] ?? 0);

                $stmtItem->execute([
                    'pesanan_id' => $id,
                    'item_id' => $itemId,
                    'qty_dasar' => $qtyPcs,
                    'harga' => $harga,
                    'diskon' => $diskon,
                    'bonus' => $isBonus ? 'true' : 'false',
                    'subtotal' => $subtotal,
                    'hpp' => $hpp,
                ]);
            }

            $pdo->commit();

            $actionDesc = $isRetryFromFailed
                ? "Pesanan #{$order['nomor_nota']} ({$order['nama_toko']}) diedit dan dijadwalkan KIRIM ULANG ke antrean PO Gudang. Total Netto: Rp " . number_format($totalNetto, 0, ',', '.') . ($nominalRefundDilakukan > 0 ? " (Refund Kas: Rp " . number_format($nominalRefundDilakukan, 0, ',', '.') . ")" : "")
                : "Memperbarui rincian pesanan #{$order['nomor_nota']} ({$order['nama_toko']}) - Total Netto: Rp " . number_format($totalNetto, 0, ',', '.');

            ActivityLog::log(
                'pesanan',
                'UPDATE',
                $actionDesc,
                'pesanan',
                (string)$id
            );

            $successMsg = $isRetryFromFailed
                ? "Pesanan #{$order['nomor_nota']} berhasil diedit dan dijadwalkan ulang ke antrean Daftar PO Gudang!" . ($nominalRefundDilakukan > 0 ? " Kelebihan bayar sebesar " . Format::rupiah($nominalRefundDilakukan) . " telah dikembalikan (refund)." : "")
                : "Pesanan #{$order['nomor_nota']} berhasil diperbarui!";

            $this->flashSuccess($successMsg);
            $this->redirect('/customer-orders');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal memperbarui pesanan: ' . $e->getMessage());
            $this->redirect('/customer-orders/edit?id=' . urlencode($id));
        }
    }

    public function invoice(): void
    {
        Auth::requirePermission(['orders.print_invoice', 'orders.view_all', 'orders.view_assigned']);

        $id = $this->input('id');
        if (empty($id)) {
            $this->redirect('/customer-orders');
            return;
        }

        try {
            $order = Database::fetchOne("
                SELECT p.*, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
                       pel.sales_driver_id as pelanggan_sales_id,
                       k.nama_karyawan as nama_sales,
                       ak.nama_akun as nama_akun_kas
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                LEFT JOIN public.akun_kas ak ON p.akun_kas_id = ak.id
                WHERE p.id = :id
            ", ['id' => $id]);

            if (!$order) {
                $this->flashError('Faktur pesanan tidak ditemukan.');
                $this->redirect('/customer-orders');
                return;
            }

            if (!Auth::can('orders.view_all')) {
                $myEmpId = Auth::employeeId();
                if ($order['sales_driver_id'] !== $myEmpId && ($order['pelanggan_sales_id'] ?? null) !== $myEmpId) {
                    $this->flashError('Akses Ditolak: Anda hanya dapat melihat faktur untuk toko binaan Anda.');
                    $this->redirect('/customer-orders');
                    return;
                }
            }

            $items = Database::fetchAll("
                SELECT ip.*, i.nama_item, i.kode_sku, i.satuan_dasar, gp.nama_grup
                FROM public.item_pesanan ip
                JOIN public.item i ON ip.item_id = i.id
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE ip.pesanan_id = :id
                ORDER BY ip.dibuat_pada ASC
            ", ['id' => $id]);

            $this->view('customer_orders.nota_reguler', [
                'order' => $order,
                'items' => $items,
            ]);

        } catch (Throwable $e) {
            $this->flashError('Terjadi kesalahan saat memuat faktur: ' . $e->getMessage());
            $this->redirect('/customer-orders');
        }
    }

    /**
     * Catat Pelunasan Piutang Pesanan Toko
     */
    public function pay(): void
    {
        Auth::requirePermission('orders.pay');

        $id = $this->input('id');
        $akunKasId = $this->input('akun_kas_id');
        $nominalBayar = (float)preg_replace('/[^0-9]/', '', (string)$this->input('nominal_bayar', '0'));
        $tanggalBayar = $this->input('tanggal_bayar', date('Y-m-d'));
        $keterangan = trim((string)$this->input('keterangan', 'Pelunasan Faktur Toko'));

        if (empty($id) || empty($akunKasId) || $nominalBayar <= 0) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Mohon pilih akun kas dan masukkan nominal pembayaran yang valid (lebih dari Rp 0).'], 400);
                return;
            }
            $this->flashError('Mohon pilih akun kas dan masukkan nominal pembayaran yang valid (lebih dari Rp 0).');
            $this->redirect('/customer-orders');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $order = Database::fetchOne("SELECT * FROM public.pesanan WHERE id = :id FOR UPDATE", ['id' => $id]);
            if (!$order) {
                throw new \Exception('Data faktur tidak ditemukan.');
            }

            // 1. Validasi Status Pesanan
            if (($order['status_pemrosesan'] ?? '') === 'dibatalkan') {
                throw new \Exception("Pesanan #{$order['nomor_nota']} telah dibatalkan. Pembayaran tidak dapat diproses.");
            }

            if (in_array($order['status_pemrosesan'] ?? '', ['gagal_dikirim', 'gagal_kembali', 'gagal_kirim'], true)) {
                throw new \Exception("Pesanan #{$order['nomor_nota']} dalam status Gagal Kirim. Stok produk telah berada di rak gudang. Harap selesaikan jadwal Kirim Ulang terlebih dahulu sebelum mencatat pembayaran.");
            }

            if (($order['status_pembayaran'] ?? '') === 'lunas') {
                throw new \Exception("Faktur #{$order['nomor_nota']} sudah lunas sepenuhnya. Tidak ada tagihan tersisa.");
            }

            if (($order['tipe_pembayaran'] ?? '') === 'konsinyasi' || !empty($order['is_konsinyasi']) || (isset($order['is_tagihan']) && ($order['is_tagihan'] === false || $order['is_tagihan'] === 'false'))) {
                throw new \Exception("Pesanan konsinyasi bukan merupakan faktur tagihan langsung. Pembayaran diproses melalui Form Opname Kunjungan Sales.");
            }

            // 2. Validasi Batas Nominal (Anti-Overpayment / Proteksi Lebih Bayar)
            $totalNetto = (float)$order['total_netto'];
            $totalDibayarLama = (float)$order['total_dibayar'];
            $sisaTagihanSaatIni = max(0, $totalNetto - $totalDibayarLama);

            if ($sisaTagihanSaatIni <= 0) {
                throw new \Exception("Faktur #{$order['nomor_nota']} tidak memiliki sisa tagihan.");
            }

            if ($nominalBayar > $sisaTagihanSaatIni) {
                throw new \Exception("Nominal pembayaran (" . Format::rupiah($nominalBayar) . ") melebihi sisa tagihan yang belum lunas (" . Format::rupiah($sisaTagihanSaatIni) . ").");
            }

            // 3. Validasi & Lock Akun Kas Tujuan (Pencegahan Race Condition & Akun Nonaktif)
            $akunKas = Database::fetchOne("
                SELECT id, nama_akun, saldo_saat_ini 
                FROM public.akun_kas 
                WHERE id = :id AND status_aktif = TRUE 
                FOR UPDATE
            ", ['id' => $akunKasId]);

            if (!$akunKas) {
                throw new \Exception("Akun kas / bank penerima tidak ditemukan atau dalam status nonaktif.");
            }

            $saldoKasAwal = (float)$akunKas['saldo_saat_ini'];
            $saldoKasBaru = $saldoKasAwal + $nominalBayar;
            $namaAkunKas = $akunKas['nama_akun'];

            // 4. Hitung Nilai Baru Pesanan via PaymentHelper
            $totalDibayarBaru = $totalDibayarLama + $nominalBayar;
            $settlement = PaymentHelper::calculateSettlement($totalNetto, $totalDibayarBaru);
            $sisaTagihanBaru = $settlement['sisa_tagihan'];
            $statusBaru = $settlement['status_pembayaran'];

            // Update Pesanan
            $stmt = $pdo->prepare("
                UPDATE public.pesanan
                SET total_dibayar = :dibayar,
                    sisa_tagihan = :sisa,
                    status_pembayaran = :status,
                    akun_kas_id = COALESCE(akun_kas_id, :akun_kas),
                    diubah_pada = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'dibayar' => $totalDibayarBaru,
                'sisa' => $sisaTagihanBaru,
                'status' => $statusBaru,
                'akun_kas' => $akunKasId,
                'id' => $id,
            ]);

            // Kurangi Saldo Piutang Berjalan Pelanggan (Jika pesanan sudah pernah diakui sebagai piutang setelah barang diserahkan)
            if (in_array($order['status_pemrosesan'] ?? '', ['selesai_dikirim', 'selesai', 'selesai_diterima'], true) && !empty($order['pelanggan_id'])) {
                $stmtDecPiutang = $pdo->prepare("
                    UPDATE public.pelanggan
                    SET total_piutang_berjalan = GREATEST(0, COALESCE(total_piutang_berjalan, 0) - :nominal_bayar),
                        diubah_pada = NOW()
                    WHERE id = :pelanggan_id
                ");
                $stmtDecPiutang->execute([
                    'nominal_bayar' => $nominalBayar,
                    'pelanggan_id' => $order['pelanggan_id'],
                ]);
            }

            // Update Saldo Kas Penerima
            $stmtKasAkun = $pdo->prepare("
                UPDATE public.akun_kas
                SET saldo_saat_ini = :saldo,
                    diubah_pada = NOW()
                WHERE id = :akun_kas
            ");
            $stmtKasAkun->execute([
                'saldo' => $saldoKasBaru,
                'akun_kas' => $akunKasId,
            ]);

            // Catat Arus Kas Masuk
            $voucherNo = CashVoucher::generate('masuk', $tanggalBayar, $pdo);
            $stmtKas = $pdo->prepare("
                INSERT INTO public.arus_kas (
                    nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                    keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :nomor_tx, :akun_kas, :tgl, 'masuk', 'penjualan', :nominal,
                    :ket, 'pesanan', :ref_id, :saldo_berjalan, :user_id, NOW()
                )
            ");
            $userId = Auth::id() ?: null;
            $stmtKas->execute([
                'nomor_tx' => $voucherNo,
                'akun_kas' => $akunKasId,
                'tgl' => $tanggalBayar,
                'nominal' => $nominalBayar,
                'ket' => "{$keterangan} - Faktur #{$order['nomor_nota']}",
                'ref_id' => $id,
                'saldo_berjalan' => $saldoKasBaru,
                'user_id' => $userId,
            ]);

            ActivityLog::log(
                'keuangan',
                'INSERT',
                "Penerimaan Pembayaran Piutang Faktur #{$order['nomor_nota']} sebesar " . Format::rupiah($nominalBayar) . " ke {$namaAkunKas}",
                'pesanan',
                $id
            );

            $pdo->commit();

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => "Pembayaran sebesar " . Format::rupiah($nominalBayar) . " untuk Faktur {$order['nomor_nota']} berhasil dicatat!",
                    'data' => [
                        'order_id' => $id,
                        'nominal_dibayar' => $nominalBayar,
                        'total_dibayar' => $totalDibayarBaru,
                        'sisa_tagihan' => $sisaTagihanBaru,
                        'status_pembayaran' => $statusBaru,
                        'akun_kas_nama' => $namaAkunKas
                    ]
                ]);
                return;
            }

            $this->flashSuccess("Pembayaran sebesar <strong>" . Format::rupiah($nominalBayar) . "</strong> untuk Faktur <strong>{$order['nomor_nota']}</strong> berhasil dicatat!");
            $this->redirect('/customer-orders');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 422);
                return;
            }
            $this->flashError("Gagal mencatat pembayaran: " . $e->getMessage());
            $this->redirect('/customer-orders');
        }
    }

    /**
     * Batalkan Transaksi Faktur (Kembalikan Stok Gudang)
     */
    public function cancel(): void
    {
        Auth::requirePermission('orders.cancel');

        $id = $this->input('id');
        if (empty($id)) {
            $this->redirect('/customer-orders');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $order = Database::fetchOne("SELECT * FROM public.pesanan WHERE id = :id FOR UPDATE", ['id' => $id]);
            if (!$order) {
                throw new \Exception('Data faktur tidak ditemukan.');
            }

            // Validasi Status: Tolak pembatalan jika pesanan sudah selesai diterima
            if (in_array($order['status_pemrosesan'] ?? '', ['selesai_dikirim', 'selesai_diterima', 'selesai'], true)) {
                $this->flashError("Pesanan #{$order['nomor_nota']} sudah selesai diterima oleh toko mitra. Pembatalan langsung dikunci untuk menjaga integritas kas & piutang. Silakan proses melalui menu Retur Penjualan atau Opname Konsinyasi.");
                $this->redirect('/customer-orders');
                return;
            }

            // 1. Kembalikan stok fisik ke gudang HANYA JIKA pesanan sudah pernah diproses potong stok dan belum dikembalikan (status gagal_dikirim sudah dikembalikan saat delivery gagal)
            $isPhysicalStockCut = StockHelper::isPhysicalStockCut($order['status_pemrosesan'] ?? '');

            if ($isPhysicalStockCut) {
                StockHelper::revertOrderStockToWarehouse(
                    $pdo,
                    $id,
                    "Pembatalan Pesanan #{$order['nomor_nota']} ({$order['status_pemrosesan']})",
                    Auth::id() ?: null
                );
            }

            // 2. Jika kas pernah masuk, kurangi kembali saldo kas
            if ((float)$order['total_dibayar'] > 0 && !empty($order['akun_kas_id'])) {
                $akunKas = Database::fetchOne("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE", ['id' => $order['akun_kas_id']]);
                $saldoLama = (float)($akunKas['saldo_saat_ini'] ?? 0);
                $saldoBaru = max(0, $saldoLama - (float)$order['total_dibayar']);

                $stmtKurangiKas = $pdo->prepare("
                    UPDATE public.akun_kas
                    SET saldo_saat_ini = :saldo,
                        diubah_pada = NOW()
                    WHERE id = :akun_kas
                ");
                $stmtKurangiKas->execute([
                    'saldo' => $saldoBaru,
                    'akun_kas' => $order['akun_kas_id'],
                ]);

                $stmtArusKas = $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                        keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :akun_kas, CURRENT_DATE, 'keluar', 'koreksi', :nominal,
                        :ket, 'pesanan', :ref_id, :saldo_berjalan, :user_id, NOW()
                    )
                ");
                $userId = Auth::id() ?: null;
                $stmtArusKas->execute([
                    'akun_kas' => $order['akun_kas_id'],
                    'nominal' => (float)$order['total_dibayar'],
                    'ket' => "Koreksi Pembatalan Faktur Toko #{$order['nomor_nota']}",
                    'ref_id' => $id,
                    'saldo_berjalan' => $saldoBaru,
                    'user_id' => $userId,
                ]);
            }

            // 3. Hapus surat jalan terkait terlebih dahulu untuk mencegah pelanggaran foreign key
            $stmtDelSj = $pdo->prepare("DELETE FROM public.surat_jalan WHERE pesanan_id = :id");
            $stmtDelSj->execute(['id' => $id]);

            // 4. Hapus detail item dan pesanan
            $stmtDelItem = $pdo->prepare("DELETE FROM public.item_pesanan WHERE pesanan_id = :id");
            $stmtDelItem->execute(['id' => $id]);

            $stmtDelPesanan = $pdo->prepare("DELETE FROM public.pesanan WHERE id = :id");
            $stmtDelPesanan->execute(['id' => $id]);

            ActivityLog::log(
                'penjualan',
                'DELETE',
                "Pembatalan Pesanan #{$order['nomor_nota']} ({$order['status_pemrosesan']})" . ($isPhysicalStockCut ? " dan pengembalian stok ke rak gudang" : ""),
                'pesanan',
                (string)$id
            );

            $pdo->commit();

            $msg = $isPhysicalStockCut
                ? "Pesanan <strong>#{$order['nomor_nota']}</strong> berhasil dibatalkan dan seluruh stok produk telah dikembalikan ke rak gudang!"
                : "Draf PO <strong>#{$order['nomor_nota']}</strong> berhasil dibatalkan dan dihapus dari antrean!";

            $this->flashSuccess($msg);
            $this->redirect('/customer-orders');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError("Gagal membatalkan transaksi: " . $e->getMessage());
            $this->redirect('/customer-orders');
        }
    }

    /**
     * Update Status Pengiriman & Driver Surat Jalan dari Modul Pesanan Pelanggan
     */
    public function updateDeliveryStatus(): void
    {
        Auth::requirePermission('orders.delivery_status');

        $orderId = $this->input('order_id');
        $statusBaru = $this->input('status_surat_jalan');
        $driverId = $this->input('sales_driver_id');

        if (empty($orderId) || empty($statusBaru)) {
            $this->flashError('Parameter update pengiriman tidak lengkap.');
            $this->redirect('/customer-orders');
            return;
        }

        $validStatuses = ['siap_kirim', 'sedang_dikirim', 'selesai_diterima', 'gagal_kembali', 'gagal_kirim'];
        if (!in_array($statusBaru, $validStatuses, true)) {
            $statusBaru = 'siap_kirim';
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $sj = Database::fetchOne("SELECT * FROM public.surat_jalan WHERE pesanan_id = :order_id", ['order_id' => $orderId]);
            if (!$sj) {
                // Auto create surat jalan
                $pesanan = Database::fetchOne("
                    SELECT p.*, pel.wilayah_id 
                    FROM public.pesanan p 
                    JOIN public.pelanggan pel ON p.pelanggan_id = pel.id 
                    WHERE p.id = :id
                ", ['id' => $orderId]);

                $nomorSj = DocumentNumber::nextDeliveryNumber($pdo);
                $dId = !empty($driverId) ? $driverId : ($pesanan['sales_driver_id'] ?: null);

                $wilayahInfo = null;
                if (!empty($pesanan['wilayah_id'])) {
                    $wilayahInfo = Database::fetchOne("SELECT nama_wilayah, kode_rute FROM public.wilayah WHERE id = :id", ['id' => $pesanan['wilayah_id']]);
                }

                $stmtNew = $pdo->prepare("
                    INSERT INTO public.surat_jalan (
                        nomor_surat_jalan, pesanan_id, sales_driver_id, rute_wilayah_id,
                        nama_wilayah_snapshot, kode_rute_snapshot,
                        status_surat_jalan, disetujui_oleh, dibuat_pada
                    ) VALUES (
                        :no_sj, :pesanan_id, :driver_id, :wilayah_id,
                        :wilayah_snap, :rute_snap,
                        :status, :user_id, NOW()
                    )
                ");
                $stmtNew->execute([
                    'no_sj' => $nomorSj,
                    'pesanan_id' => $orderId,
                    'driver_id' => $dId,
                    'wilayah_id' => $pesanan['wilayah_id'] ?? null,
                    'wilayah_snap' => $wilayahInfo['nama_wilayah'] ?? null,
                    'rute_snap' => $wilayahInfo['kode_rute'] ?? null,
                    'status' => $statusBaru,
                    'user_id' => Auth::id() ?: null,
                ]);
            } else {
                $stmtUpdate = $pdo->prepare("
                    UPDATE public.surat_jalan
                    SET status_surat_jalan = :status,
                        sales_driver_id = COALESCE(:driver_id, sales_driver_id)
                    WHERE pesanan_id = :order_id
                ");
                $stmtUpdate->execute([
                    'status' => $statusBaru,
                    'driver_id' => !empty($driverId) ? $driverId : null,
                    'order_id' => $orderId,
                ]);
            }

            // Sync driver to pesanan if updated
            if (!empty($driverId)) {
                $pdo->prepare("UPDATE public.pesanan SET sales_driver_id = :driver_id WHERE id = :id")
                    ->execute(['driver_id' => $driverId, 'id' => $orderId]);
            }

            // Sync status pesanan dan piutang/rak konsinyasi jika status selesai_diterima
            if ($statusBaru === 'selesai_diterima') {
                $stmtOrder = $pdo->prepare("
                    SELECT p.*, pel.is_konsinyasi, pel.nama_toko 
                    FROM public.pesanan p 
                    JOIN public.pelanggan pel ON p.pelanggan_id = pel.id 
                    WHERE p.id = :id
                    FOR UPDATE
                ");
                $stmtOrder->execute(['id' => $orderId]);
                $orderData = $stmtOrder->fetch(\PDO::FETCH_ASSOC);

                if ($orderData) {
                    $alreadyDelivered = in_array($orderData['status_pemrosesan'] ?? '', ['selesai_dikirim', 'selesai', 'selesai_diterima'], true);
                    $pdo->prepare("UPDATE public.pesanan SET status_pemrosesan = 'selesai_dikirim', diubah_pada = NOW() WHERE id = :id")->execute(['id' => $orderId]);

                    $isKonsinyasi = (bool)$orderData['is_konsinyasi'] || ($orderData['tipe_pembayaran'] === 'konsinyasi');
                    if ($isKonsinyasi) {
                        $orderedItems = Database::fetchAll("
                            SELECT item_id, kuantitas_satuan_dasar 
                            FROM public.item_pesanan 
                            WHERE pesanan_id = :id
                        ", ['id' => $orderId]);

                        foreach ($orderedItems as $oit) {
                            $pdo->prepare("
                                INSERT INTO public.stok_konsinyasi_toko (
                                    pelanggan_id, item_id, stok_titip_saat_ini, terakhir_opname_pada, dibuat_pada, diubah_pada
                                ) VALUES (
                                    :pelanggan_id, :item_id, :qty, NOW(), NOW(), NOW()
                                )
                                ON CONFLICT (pelanggan_id, item_id) DO UPDATE SET
                                    stok_titip_saat_ini = public.stok_konsinyasi_toko.stok_titip_saat_ini + EXCLUDED.stok_titip_saat_ini,
                                    diubah_pada = NOW()
                            ")->execute([
                                'pelanggan_id' => $orderData['pelanggan_id'],
                                'item_id' => $oit['item_id'],
                                'qty' => (int)$oit['kuantitas_satuan_dasar']
                            ]);
                        }
                    } else {
                        // Regular Order: Akumulasi piutang pelanggan jika belum pernah selesai dikirim sebelumnya
                        if (!$alreadyDelivered) {
                            $sisaTagihan = (float)$orderData['sisa_tagihan'];
                            if ($sisaTagihan > 0) {
                                $pdo->prepare("
                                    UPDATE public.pelanggan
                                    SET total_piutang_berjalan = COALESCE(total_piutang_berjalan, 0) + :sisa,
                                        diubah_pada = NOW()
                                    WHERE id = :pelanggan_id
                                ")->execute([
                                    'sisa' => $sisaTagihan,
                                    'pelanggan_id' => $orderData['pelanggan_id']
                                ]);
                            }

                            // Anti-Duplikasi: Cek apakah pembayaran pesanan ini sudah pernah dicatat di arus kas (misal saat input PO)
                            $stmtCheckKas = $pdo->prepare("SELECT COUNT(*) FROM public.arus_kas WHERE referensi_tabel = 'pesanan' AND referensi_id = :id AND jenis_kas = 'masuk'");
                            $stmtCheckKas->execute(['id' => $orderId]);
                            $alreadyInCash = (int)$stmtCheckKas->fetchColumn() > 0;

                            $totalDibayar = (float)$orderData['total_dibayar'];
                            $akunKasId = $orderData['akun_kas_id'] ?? null;

                            if (!$alreadyInCash && $totalDibayar > 0 && !empty($akunKasId)) {
                                $stmtKas = $pdo->prepare("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE");
                                $stmtKas->execute(['id' => $akunKasId]);
                                $akunKas = $stmtKas->fetch(\PDO::FETCH_ASSOC);

                                if ($akunKas) {
                                    $saldoLama = (float)($akunKas['saldo_saat_ini'] ?? 0);
                                    $saldoBaru = $saldoLama + $totalDibayar;

                                    $pdo->prepare("
                                        UPDATE public.akun_kas
                                        SET saldo_saat_ini = :saldo,
                                            diubah_pada = NOW()
                                        WHERE id = :akun_kas
                                    ")->execute([
                                        'saldo' => $saldoBaru,
                                        'akun_kas' => $akunKasId,
                                    ]);

                                    $keteranganKas = ($orderData['status_pembayaran'] === 'lunas')
                                        ? "Penerimaan Pembayaran Lunas Pesanan Toko #{$orderData['nomor_nota']} ({$orderData['nama_toko']})"
                                        : "Penerimaan DP/Sebagian Pesanan Toko #{$orderData['nomor_nota']} ({$orderData['nama_toko']})";

                                    $voucherNo = CashVoucher::generate('masuk', date('Y-m-d'), $pdo);

                                    $pdo->prepare("
                                        INSERT INTO public.arus_kas (
                                            nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                                            keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                                        ) VALUES (
                                            :nomor_tx, :akun_kas, CURRENT_DATE, 'masuk', 'penjualan', :nominal,
                                            :ket, 'pesanan', :ref_id, :saldo_berjalan, :user_id, NOW()
                                        )
                                    ")->execute([
                                        'nomor_tx' => $voucherNo,
                                        'akun_kas' => $akunKasId,
                                        'nominal' => $totalDibayar,
                                        'ket' => $keteranganKas,
                                        'ref_id' => $orderId,
                                        'saldo_berjalan' => $saldoBaru,
                                        'user_id' => Auth::id() ?: null,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            $pdo->commit();
            $this->flashSuccess("Status pengiriman surat jalan pesanan berhasil diperbarui!");
            $this->redirect('/customer-orders');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal memperbarui status pengiriman: ' . $e->getMessage());
            $this->redirect('/customer-orders');
        }
    }

    /**
     * Halaman Khusus Manajemen Daftar PO (Purchase Orders) untuk Staf Gudang & Admin
     */
    public function poList(): void
    {
        Auth::requirePermission(['orders.po_view_all', 'orders.po_view_assigned']);

        try {
            $tab = $this->input('tab', 'pending'); // 'pending' (Menunggu Packing), 'ready' (Siap Dikirim), 'all' (Semua)
            $q = trim((string)$this->input('q', ''));
            $pelangganId = $this->input('pelanggan_id', '');
            $sort = strtolower(trim((string)$this->input('sort', 'terbaru')));
            if ($sort !== 'terlama') {
                $sort = 'terbaru';
            }

            $sql = "
                SELECT p.id, p.nomor_nota, p.tanggal_pesanan, p.total_bruto, p.total_diskon, p.total_netto,
                       p.total_dibayar, p.sisa_tagihan, p.tipe_pembayaran, p.status_pembayaran, p.status_pemrosesan,
                       p.catatan, p.dibuat_pada, p.waktu_gagal_kirim,
                       pel.id as pelanggan_id, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
                       CASE 
                           WHEN sj.id IS NOT NULL THEN COALESCE(k_sj.nama_karyawan, k_p.nama_karyawan)
                           WHEN p.status_pemrosesan = 'po' THEN NULL
                           ELSE k_p.nama_karyawan
                       END as nama_sales,
                       CASE 
                           WHEN sj.id IS NOT NULL THEN COALESCE(k_sj.nomor_polisi_kendaraan, k_p.nomor_polisi_kendaraan, 'Armada Toko')
                           WHEN p.status_pemrosesan = 'po' THEN NULL
                           WHEN k_p.id IS NOT NULL THEN COALESCE(k_p.nomor_polisi_kendaraan, 'Armada Toko')
                           ELSE NULL
                       END as nopol_driver,
                       sj.id as surat_jalan_id, sj.nomor_surat_jalan, sj.status_surat_jalan,
                       w.nama_wilayah,
                       (SELECT COUNT(*) FROM public.item_pesanan ip WHERE ip.pesanan_id = p.id) as total_sku,
                       (SELECT COALESCE(SUM(kuantitas_satuan_dasar), 0) FROM public.item_pesanan ip WHERE ip.pesanan_id = p.id) as total_pcs
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN LATERAL (
                    SELECT id, nomor_surat_jalan, status_surat_jalan, sales_driver_id, rute_wilayah_id
                    FROM public.surat_jalan
                    WHERE pesanan_id = p.id
                    ORDER BY dibuat_pada DESC
                    LIMIT 1
                ) sj ON true
                LEFT JOIN public.v_karyawan_info k_p ON p.sales_driver_id = k_p.id
                LEFT JOIN public.v_karyawan_info k_sj ON sj.sales_driver_id = k_sj.id
                LEFT JOIN public.wilayah w ON COALESCE(sj.rute_wilayah_id, pel.wilayah_id) = w.id
                WHERE p.status_pembayaran != 'dibatalkan'
            ";

            $params = [];

            // Scope Check: Jika hanya punya hak lihat toko binaan (orders.po_view_assigned)
            if (!Auth::can('orders.po_view_all')) {
                $myEmpId = Auth::employeeId();
                if ($myEmpId) {
                    $sql .= " AND (p.sales_driver_id = :my_emp_id OR pel.sales_driver_id = :my_emp_id)";
                    $params['my_emp_id'] = $myEmpId;
                } else {
                    $sql .= " AND 1=0";
                }
            }

            if ($tab === 'pending') {
                $sql .= " AND p.status_pemrosesan = 'po'";
            } elseif ($tab === 'ready') {
                $sql .= " AND p.status_pemrosesan IN ('siap_dikirim', 'siap_kirim')";
            } elseif ($tab === 'failed') {
                $sql .= " AND p.status_pemrosesan = 'gagal_dikirim'";
            }

            if (!empty($pelangganId)) {
                $sql .= " AND p.pelanggan_id = :pelanggan_id";
                $params['pelanggan_id'] = $pelangganId;
            }

            if (!empty($q)) {
                $sql .= " AND (p.nomor_nota ILIKE :q OR pel.nama_toko ILIKE :q OR pel.kode_pelanggan ILIKE :q OR p.catatan ILIKE :q)";
                $params['q'] = "%{$q}%";
            }

            if ($sort === 'terlama') {
                $sql .= " ORDER BY p.tanggal_pesanan ASC, p.dibuat_pada ASC, p.id ASC";
            } else {
                $sql .= " ORDER BY p.tanggal_pesanan DESC, p.dibuat_pada DESC, p.id DESC";
            }
            $poList = Database::fetchAll($sql, $params);

            // Fetch items for each PO to evaluate physical warehouse stock readiness
            $orderIds = array_column($poList, 'id');
            $itemsByOrder = [];
            if (!empty($orderIds)) {
                $itemParams = [];
                $itemPlaceholders = [];
                foreach (array_values($orderIds) as $idx => $oid) {
                    $key = 'oid_' . $idx;
                    $itemPlaceholders[] = ':' . $key;
                    $itemParams[$key] = (string)$oid;
                }
                $rawItems = Database::fetchAll("
                    SELECT ip.pesanan_id, ip.item_id, ip.kuantitas_satuan_dasar, ip.harga_satuan_deal, ip.diskon_item_nominal, ip.subtotal,
                           ip.is_bonus, ip.catatan_bonus,
                           it.nama_item, it.kode_sku, it.stok_fisik_saat_ini, it.satuan_dasar
                    FROM public.item_pesanan ip
                    JOIN public.item it ON ip.item_id = it.id
                    WHERE ip.pesanan_id IN (" . implode(', ', $itemPlaceholders) . ")
                    ORDER BY it.nama_item ASC
                ", $itemParams);
                foreach ($rawItems as $ri) {
                    $itemsByOrder[$ri['pesanan_id']][] = $ri;
                }
            }

            // Decorate PO list with stock status info
            foreach ($poList as &$po) {
                $po['items'] = $itemsByOrder[$po['id']] ?? [];
                $po['is_stock_sufficient'] = true;
                $po['stock_deficit_count'] = 0;

                foreach ($po['items'] as $item) {
                    $reqQty = (int)$item['kuantitas_satuan_dasar'];
                    $curStock = (float)$item['stok_fisik_saat_ini'];
                    if ($po['status_pemrosesan'] === 'po' && $curStock < $reqQty) {
                        $po['is_stock_sufficient'] = false;
                        $po['stock_deficit_count']++;
                    }
                }
            }
            unset($po);

            // Ringkasan Statistik Logistik Gudang
            $scopeWhere = "";
            $scopeParam = [];
            if (!Auth::can('orders.po_view_all')) {
                $myEmpId = Auth::employeeId();
                if ($myEmpId) {
                    $scopeWhere = " AND (sales_driver_id = :my_emp_id OR pelanggan_id IN (SELECT id FROM public.pelanggan WHERE sales_driver_id = :my_emp_id))";
                    $scopeParam['my_emp_id'] = $myEmpId;
                } else {
                    $scopeWhere = " AND 1=0";
                }
            }

            $countPending = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM public.pesanan WHERE status_pemrosesan = 'po' AND status_pembayaran != 'dibatalkan' {$scopeWhere}", $scopeParam)['cnt'] ?? 0);
            $countReady = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM public.pesanan WHERE status_pemrosesan IN ('siap_dikirim', 'siap_kirim') AND status_pembayaran != 'dibatalkan' {$scopeWhere}", $scopeParam)['cnt'] ?? 0);
            $countFailed = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM public.pesanan WHERE status_pemrosesan = 'gagal_dikirim' AND status_pembayaran != 'dibatalkan' {$scopeWhere}", $scopeParam)['cnt'] ?? 0);

            // Hitung PO Siap Dikirim yang BELUM diterbitkan Surat Jalan
            $countReadyNoSj = (int)(Database::fetchOne("
                SELECT COUNT(*) as cnt 
                FROM public.pesanan p
                LEFT JOIN public.surat_jalan sj ON (p.id = sj.pesanan_id AND sj.status_surat_jalan NOT IN ('gagal_kirim', 'dibatalkan'))
                WHERE p.status_pemrosesan IN ('siap_dikirim', 'siap_kirim') 
                  AND sj.id IS NULL 
                  AND p.status_pembayaran != 'dibatalkan' {$scopeWhere}
            ", $scopeParam)['cnt'] ?? 0);

            // Hitung total PO berstatus PO yang mengalami defisit stok fisik
            $countDeficit = 0;
            $pendingWithItems = Database::fetchAll("
                SELECT ip.pesanan_id, ip.kuantitas_satuan_dasar, it.stok_fisik_saat_ini
                FROM public.item_pesanan ip
                JOIN public.pesanan p ON ip.pesanan_id = p.id
                JOIN public.item it ON ip.item_id = it.id
                WHERE p.status_pemrosesan = 'po' AND p.status_pembayaran != 'dibatalkan' {$scopeWhere}
            ", $scopeParam);
            $deficitOrders = [];
            foreach ($pendingWithItems as $pwi) {
                if ((float)$pwi['stok_fisik_saat_ini'] < (int)$pwi['kuantitas_satuan_dasar']) {
                    $deficitOrders[$pwi['pesanan_id']] = true;
                }
            }
            $countDeficit = count($deficitOrders);

            $customers = Database::fetchAll("SELECT id, kode_pelanggan, nama_toko FROM public.pelanggan WHERE status_aktif = TRUE ORDER BY nama_toko ASC");
            $drivers = Database::fetchAll("SELECT id, nama_karyawan, nomor_polisi_kendaraan, posisi FROM public.v_karyawan_info WHERE posisi IN ('sales', 'driver') AND status_aktif = TRUE ORDER BY (posisi = 'driver') DESC, nama_karyawan ASC");
            $availableItems = Database::fetchAll("
                SELECT id, kode_sku, nama_item, stok_fisik_saat_ini, satuan_dasar, harga_pokok_pembelian
                FROM public.item
                WHERE status_aktif = TRUE AND tipe_item = 'barang_jadi'
                ORDER BY nama_item ASC
            ");

            $this->view('customer_orders.po_list', [
                'pageTitle' => 'Daftar PO Pelanggan',
                'pageSubtitle' => 'Verifikasi Kesiapan Stok & Penyiapan Barang Gudang',
                'poList' => $poList,
                'tab' => $tab,
                'q' => $q,
                'pelangganId' => $pelangganId,
                'sort' => $sort,
                'countPending' => $countPending,
                'countReady' => $countReady,
                'countReadyNoSj' => $countReadyNoSj,
                'countFailed' => $countFailed,
                'countDeficit' => $countDeficit,
                'customers' => $customers,
                'drivers' => $drivers,
                'availableItems' => $availableItems,
            ]);

        } catch (Throwable $e) {
            $this->flashError("Gagal memuat daftar PO gudang: " . $e->getMessage());
            $this->redirect('/customer-orders');
        }
    }

    /**
     * Proses PO menjadi Siap Kirim (Potong Stok Fisik Gudang & Tambah Item Bonus jika ada)
     */
    public function processPoToReady(): void
    {
        Auth::requirePermission('orders.po_process');

        $orderId = trim((string)$this->input('order_id'));

        if (empty($orderId)) {
            $this->flashError('ID Pesanan tidak valid.');
            $this->redirect('/customer-orders/po-list');
            return;
        }

        // Parsing data bonus dari modal (JSON string atau array)
        $bonusesRaw = $this->input('bonuses_json') ?: $this->input('bonuses');
        $bonuses = [];
        if (!empty($bonusesRaw)) {
            if (is_string($bonusesRaw)) {
                $bonuses = json_decode($bonusesRaw, true) ?: [];
            } elseif (is_array($bonusesRaw)) {
                $bonuses = $bonusesRaw;
            }
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $stmtOrder = $pdo->prepare("
                SELECT p.*, pel.nama_toko 
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                WHERE p.id = :id FOR UPDATE OF p
            ");
            $stmtOrder->execute(['id' => $orderId]);
            $order = $stmtOrder->fetch(\PDO::FETCH_ASSOC);

            if (!$order) {
                throw new \Exception('Data PO tidak ditemukan.');
            }

            if ($order['status_pemrosesan'] !== 'po') {
                throw new \Exception("PO ini sudah diproses sebelumnya (Status saat ini: {$order['status_pemrosesan']}).");
            }

            // Ambil semua item reguler dalam PO
            $stmtItems = $pdo->prepare("
                SELECT ip.*, it.nama_item 
                FROM public.item_pesanan ip
                JOIN public.item it ON ip.item_id = it.id
                WHERE ip.pesanan_id = :id
            ");
            $stmtItems->execute(['id' => $orderId]);
            $orderItems = $stmtItems->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($orderItems)) {
                throw new \Exception('PO tidak memiliki item produk.');
            }

            $userId = Auth::id() ?: null;
            $stmtStok = $pdo->prepare("UPDATE public.item SET stok_fisik_saat_ini = stok_fisik_saat_ini - :qty, diubah_pada = NOW() WHERE id = :item_id");
            $stmtRiwayat = $pdo->prepare("
                INSERT INTO public.riwayat_stok (
                    item_id, tipe_mutasi, jumlah_perubahan,
                    stok_sebelum, stok_sesudah, referensi_tabel, referensi_id,
                    keterangan, dibuat_oleh, dibuat_pada
                ) VALUES (
                    :item_id, 'penjualan_keluar', :qty,
                    :stok_sebelum, :stok_sesudah, 'pesanan', :ref_id,
                    :ket, :user_id, NOW()
                )
            ");

            // 1. Validasi & Potong Stok untuk setiap item reguler PO
            foreach ($orderItems as $it) {
                $itemId = $it['item_id'];
                $qty = (int)$it['kuantitas_satuan_dasar'];

                $stmtLockItem = $pdo->prepare("SELECT stok_fisik_saat_ini, nama_item FROM public.item WHERE id = :id FOR UPDATE");
                $stmtLockItem->execute(['id' => $itemId]);
                $itemData = $stmtLockItem->fetch(\PDO::FETCH_ASSOC);

                $stokSebelum = $itemData ? (float)$itemData['stok_fisik_saat_ini'] : 0;
                if ($stokSebelum < $qty) {
                    $namaItem = $itemData['nama_item'] ?? $it['nama_item'];
                    throw new \Exception("Stok {$namaItem} tidak mencukupi. Tersedia di gudang: {$stokSebelum}, Dibutuhkan PO: {$qty}.");
                }

                $stokSesudah = $stokSebelum - $qty;
                $stmtStok->execute(['qty' => $qty, 'item_id' => $itemId]);
                $stmtRiwayat->execute([
                    'item_id' => $itemId,
                    'qty' => $qty,
                    'stok_sebelum' => $stokSebelum,
                    'stok_sesudah' => $stokSesudah,
                    'ref_id' => $orderId,
                    'ket' => "Penyiapan Barang PO #{$order['nomor_nota']} ({$order['nama_toko']})",
                    'user_id' => $userId
                ]);
            }

            // 2. Validasi, Simpan, dan Potong Stok untuk Item Bonus Tambahan (jika ada)
            $stmtInsertBonus = $pdo->prepare("
                INSERT INTO public.item_pesanan (
                    pesanan_id, item_id, kuantitas_satuan_dasar,
                    harga_satuan_deal, diskon_item_persen, diskon_item_nominal,
                    is_bonus, catatan_bonus, subtotal, harga_pokok_satuan, dibuat_pada
                ) VALUES (
                    :pesanan_id, :item_id, :qty,
                    0.00, 0.00, 0.00,
                    TRUE, :catatan_bonus, 0.00, :hpp, NOW()
                )
            ");

            $validBonusCount = 0;
            $bonusNames = [];

            if (!empty($bonuses)) {
                foreach ($bonuses as $b) {
                    $bonusItemId = trim((string)($b['item_id'] ?? ''));
                    $bonusQty = (int)($b['qty'] ?? 0);

                    if (empty($bonusItemId) || $bonusQty <= 0) {
                        continue;
                    }

                    // Tentukan alasan / catatan bonus
                    $reasonDropdown = trim((string)($b['reason'] ?? 'Bonus Toko'));
                    $customReason = trim((string)($b['custom_reason'] ?? ''));
                    $finalBonusReason = ($reasonDropdown === 'Lainnya')
                        ? ($customReason !== '' ? $customReason : 'Bonus Khusus')
                        : ($reasonDropdown !== '' ? $reasonDropdown : 'Bonus Toko');

                    // Lock item bonus & cek ketersediaan stok fisik terkini
                    $stmtLockBonusItem = $pdo->prepare("SELECT id, nama_item, stok_fisik_saat_ini, harga_pokok_pembelian FROM public.item WHERE id = :id FOR UPDATE");
                    $stmtLockBonusItem->execute(['id' => $bonusItemId]);
                    $bonusItemData = $stmtLockBonusItem->fetch(\PDO::FETCH_ASSOC);

                    if (!$bonusItemData) {
                        throw new \Exception("Produk bonus dengan ID '{$bonusItemId}' tidak ditemukan di sistem.");
                    }

                    $bonusStokSebelum = (float)$bonusItemData['stok_fisik_saat_ini'];
                    if ($bonusStokSebelum < $bonusQty) {
                        throw new \Exception("Stok bonus {$bonusItemData['nama_item']} tidak mencukupi. Tersedia di gudang: {$bonusStokSebelum}, Diminta Bonus: {$bonusQty}.");
                    }

                    $bonusStokSesudah = $bonusStokSebelum - $bonusQty;
                    $hppBonus = (float)($bonusItemData['harga_pokok_pembelian'] ?? 0.00);

                    // Insert ke item_pesanan sebagai bonus
                    $stmtInsertBonus->execute([
                        'pesanan_id' => $orderId,
                        'item_id' => $bonusItemId,
                        'qty' => $bonusQty,
                        'catatan_bonus' => $finalBonusReason,
                        'hpp' => $hppBonus
                    ]);

                    // Potong stok fisik gudang
                    $stmtStok->execute(['qty' => $bonusQty, 'item_id' => $bonusItemId]);

                    // Catat ke riwayat stok
                    $stmtRiwayat->execute([
                        'item_id' => $bonusItemId,
                        'qty' => $bonusQty,
                        'stok_sebelum' => $bonusStokSebelum,
                        'stok_sesudah' => $bonusStokSesudah,
                        'ref_id' => $orderId,
                        'ket' => "Bonus Gudang ({$finalBonusReason}) - PO #{$order['nomor_nota']} ({$order['nama_toko']})",
                        'user_id' => $userId
                    ]);

                    $validBonusCount++;
                    $bonusNames[] = "{$bonusItemData['nama_item']} ({$bonusQty} pcs - {$finalBonusReason})";
                }
            }

            // Update status pesanan ke siap_dikirim (Surat Jalan dibuat terpisah di menu Deliveries)
            $stmtUpdateOrder = $pdo->prepare("
                UPDATE public.pesanan 
                SET status_pemrosesan = 'siap_dikirim',
                    diubah_pada = NOW() 
                WHERE id = :id
            ");
            $stmtUpdateOrder->execute([
                'id' => $orderId
            ]);

            $auditMsg = "Petugas gudang menyelesaikan penyiapan barang PO #{$order['nomor_nota']} ({$order['nama_toko']}). Stok fisik gudang terpotong, status pesanan menjadi Siap Dikirim.";
            if ($validBonusCount > 0) {
                $auditMsg .= " Termasuk {$validBonusCount} item bonus tambahan: " . implode(', ', $bonusNames) . ".";
            }

            ActivityLog::log(
                'gudang',
                'UPDATE',
                $auditMsg,
                'pesanan',
                (string)$orderId
            );

            $pdo->commit();

            if ($validBonusCount > 0) {
                $this->flashSuccess("PO #{$order['nomor_nota']} ({$order['nama_toko']}) berhasil disiapkan bersama {$validBonusCount} item bonus tambahan! Stok fisik gudang telah terpotong.");
            } else {
                $this->flashSuccess("PO #{$order['nomor_nota']} ({$order['nama_toko']}) berhasil disiapkan! Stok fisik gudang telah terpotong.");
            }
            $this->redirect('/customer-orders/po-list?tab=ready');

        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal memproses PO: ' . $e->getMessage());
            $this->redirect('/customer-orders/po-list');
        }
    }

    /**
     * Cetak Lembar Ambil Barang (Picking / Packing List) untuk Staf Gudang
     * Didelegasikan ke OrderDocumentController (TASK-015 / Fase 4).
     */
    public function printPickingList(): void
    {
        (new OrderDocumentController())->printPickingList();
    }

    /**
     * Jadwalkan Kirim Ulang Pesanan yang Gagal (Batas Waktu <= 7 Hari)
     */
    public function retryDelivery(): void
    {
        Auth::requirePermission('orders.retry_delivery');

        $orderId = trim((string)$this->input('order_id'));

        if (empty($orderId)) {
            $this->flashError('ID Pesanan tidak valid.');
            $this->redirect('/customer-orders');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $stmtOrder = $pdo->prepare("
                SELECT p.*, pel.nama_toko
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                WHERE p.id = :id FOR UPDATE OF p
            ");
            $stmtOrder->execute(['id' => $orderId]);
            $order = $stmtOrder->fetch(\PDO::FETCH_ASSOC);

            if (!$order) {
                throw new \Exception('Data pesanan tidak ditemukan.');
            }

            if ($order['status_pemrosesan'] !== 'gagal_dikirim') {
                throw new \Exception("Hanya pesanan berstatus Gagal Dikirim yang dapat dijadwalkan kirim ulang.");
            }

            // Validasi masa tenggang 7 hari
            if (!empty($order['waktu_gagal_kirim'])) {
                $failedTime = strtotime($order['waktu_gagal_kirim']);
                $diffDays = (time() - $failedTime) / 86400;
                if ($diffDays > 7) {
                    // Otomatis batalkan pesanan kedaluwarsa
                    $catatanBatal = trim((string)$order['catatan'] . "\n[Dibatalkan Otomatis: Masa tenggang kirim ulang > 7 hari]");
                    $pdo->prepare("
                        UPDATE public.pesanan 
                        SET status_pemrosesan = 'dibatalkan',
                            status_pembayaran = 'dibatalkan',
                            catatan = :catatan,
                            diubah_pada = NOW()
                        WHERE id = :id
                    ")->execute([
                        'catatan' => $catatanBatal,
                        'id' => $orderId
                    ]);

                    $pdo->commit();

                    ActivityLog::log(
                        'penjualan',
                        'CANCEL',
                        "Pesanan #{$order['nomor_nota']} ({$order['nama_toko']}) otomatis DIBATALKAN oleh sistem karena telah melewati batas tenggang kirim ulang (7 hari).",
                        'pesanan',
                        (string)$orderId
                    );

                    if ($this->isAjax()) {
                        $this->json([
                            'success' => false,
                            'expired' => true,
                            'message' => "Masa tenggang kirim ulang (7 hari) telah habis. Pesanan #{$order['nomor_nota']} kedaluwarsa dan otomatis DIBATALKAN oleh sistem."
                        ], 422);
                        return;
                    }

                    $this->flashError("Masa tenggang kirim ulang (7 hari) telah habis. Pesanan #{$order['nomor_nota']} ({$order['nama_toko']}) kedaluwarsa dan otomatis dibatalkan oleh sistem.");
                    $this->redirect('/customer-orders');
                    return;
                }
            }

            // Arsipkan surat jalan lama menjadi status gagal_kirim (tidak dihapus)
            $stmtArchive = $pdo->prepare("UPDATE public.surat_jalan SET status_surat_jalan = 'gagal_kirim', diubah_pada = NOW() WHERE pesanan_id = :id AND status_surat_jalan != 'gagal_kirim'");
            $stmtArchive->execute(['id' => $orderId]);

            // Kembalikan status pesanan ke 'po' (antrean daftar PO gudang)
            $catatanBaru = (strpos((string)$order['catatan'], '[Kirim Ulang]') === false)
                ? trim((string)$order['catatan'] . "\n[Kirim Ulang]")
                : (string)$order['catatan'];

            $stmtUpdateOrder = $pdo->prepare("
                UPDATE public.pesanan 
                SET status_pemrosesan = 'po',
                    waktu_gagal_kirim = NULL,
                    catatan = :catatan,
                    diubah_pada = NOW()
                WHERE id = :id
            ");
            $stmtUpdateOrder->execute([
                'catatan' => $catatanBaru,
                'id' => $orderId
            ]);

            $pdo->commit();

            ActivityLog::log(
                'penjualan',
                'UPDATE',
                "Pesanan #{$order['nomor_nota']} ({$order['nama_toko']}) dijadwalkan KIRIM ULANG dan masuk ke antrean Daftar PO Gudang.",
                'pesanan',
                (string)$orderId
            );

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => "Pesanan #{$order['nomor_nota']} ({$order['nama_toko']}) berhasil dijadwalkan ulang! Status kini 'PO' dan masuk antrean penyiapan barang gudang.",
                    'redirect' => Router::url('/customer-orders')
                ]);
                return;
            }

            $this->flashSuccess("Pesanan #{$order['nomor_nota']} ({$order['nama_toko']}) berhasil dijadwalkan ulang! Status kini 'PO' dan masuk ke antrean penyiapan barang gudang.");
            $this->redirect('/customer-orders');

        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menjadwalkan kirim ulang: ' . $e->getMessage());
            $this->redirect('/customer-orders');
        }
    }

    /**
     * Unduh Faktur Pesanan dalam Format PDF (Dompdf Library)
     * Didelegasikan ke OrderDocumentController (TASK-015 / Fase 4).
     */
    public function invoicePdf(): void
    {
        (new OrderDocumentController())->invoicePdf();
    }

    /**
     * Unduh Faktur Rincian Item dalam Format Excel (PhpSpreadsheet Library)
     * Didelegasikan ke OrderDocumentController (TASK-015 / Fase 4).
     */
    public function invoiceExcel(): void
    {
        (new OrderDocumentController())->invoiceExcel();
    }

    /**
     * Export Seluruh Daftar Pesanan ke File Excel (PhpSpreadsheet)
     * Didelegasikan ke OrderDocumentController (TASK-015 / Fase 4).
     */
    public function exportExcel(): void
    {
        (new OrderDocumentController())->exportExcel();
    }

    /**
     * Unduh Lembar Ambil Barang (Picking List) dalam Format PDF (Dompdf Library)
     * Didelegasikan ke OrderDocumentController (TASK-015 / Fase 4).
     */
    public function pickingListPdf(): void
    {
        (new OrderDocumentController())->pickingListPdf();
    }

    /**
     * Batch export multiple PO item lists into a single consolidated PDF document.
     * Didelegasikan ke OrderDocumentController (TASK-015 / Fase 4).
     */
    public function batchPickingListPdf(): void
    {
        (new OrderDocumentController())->batchPickingListPdf();
    }
}


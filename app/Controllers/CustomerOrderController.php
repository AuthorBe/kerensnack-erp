<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\Format;
use App\Helpers\ActivityLog;
use App\Helpers\PdfExport;
use App\Helpers\ExcelExport;
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
            $hasExplicitFilter = isset($_GET['start_date']) || isset($_GET['end_date']) || isset($_GET['pelanggan_id']) || isset($_GET['sales_driver_id']) || isset($_GET['status_pembayaran']) || isset($_GET['q']);

            if ($hasExplicitFilter) {
                $startDate = $this->input('start_date', date('Y-m-01'));
                $endDate = $this->input('end_date', date('Y-m-d'));
                $pelangganId = $this->input('pelanggan_id', '');
                $salesDriverId = $this->input('sales_driver_id', '');
                $statusBayar = $this->input('status_pembayaran', 'semua');
                $q = trim((string)$this->input('q', ''));

                // Simpan ke sesi
                $_SESSION['orders_filter'] = [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'pelanggan_id' => $pelangganId,
                    'sales_driver_id' => $salesDriverId,
                    'status_pembayaran' => $statusBayar,
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
                $q = $saved['q'] ?? '';
            } else {
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-d');
                $pelangganId = '';
                $salesDriverId = '';
                $statusBayar = 'semua';
                $q = '';
            }

            // Query Dasar Pesanan Toko Pelanggan
            $sql = "
                SELECT p.id, p.nomor_nota, p.tanggal_pesanan, p.total_bruto, p.total_diskon, p.total_netto,
                       p.total_dibayar, p.sisa_tagihan, p.tipe_pembayaran, p.tanggal_jatuh_tempo,
                       p.status_pembayaran, p.status_pemrosesan, p.catatan, p.adalah_tagihan, p.dibuat_pada,
                       p.waktu_gagal_kirim, p.diubah_pada,
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
                    'q' => $q,
                ]
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
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
                       p.status_pembayaran, p.status_pemrosesan, p.catatan, p.adalah_tagihan, p.dibuat_pada,
                       p.waktu_gagal_kirim, p.diubah_pada,
                       pel.id as pelanggan_id, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
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
                       ip.kuantitas_satuan_distribusi as jumlah_bal,
                       ip.harga_satuan_deal as harga_satuan_dasar, 
                       ip.diskon_item_persen as diskon_persen, 
                       ip.diskon_item_nominal as diskon_nominal, 
                       ip.is_bonus, ip.subtotal,
                       i.kode_sku, i.nama_item, i.varian_rasa, i.satuan_dasar, i.satuan_distribusi,
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
                       sj.bukti_terima_foto, sj.nama_penerima_toko, sj.dibuat_pada,
                       k.nama_karyawan as nama_driver, k.nomor_polisi_kendaraan as nopol_driver, k.nomor_telepon as telp_driver,
                       w.nama_wilayah
                FROM public.surat_jalan sj
                LEFT JOIN public.v_karyawan_info k ON sj.sales_driver_id = k.id
                LEFT JOIN public.wilayah w ON sj.rute_wilayah_id = w.id
                WHERE sj.pesanan_id = :id
                ORDER BY sj.dibuat_pada DESC
            ";
            $shippingHistory = Database::fetchAll($sqlShipping, ['id' => $id]);

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
                       COALESCE(p.override_level_harga, gp.default_level_harga, 1) as level_harga,
                       gp.nama_grup as nama_grup_harga
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

            // 2. Ambil Master Sales-Driver Lengkap dengan Plat Nomor
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

            // 4. Ambil Katalog Barang Jadi (137 SKU)
            $products = Database::fetchAll("
                SELECT i.id, i.grup_id, i.kode_sku, i.barcode, i.nama_item, i.varian_rasa,
                       i.satuan_dasar, i.satuan_distribusi, i.stok_fisik_saat_ini,
                       gp.nama_grup, gp.kode_grup
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE i.status_aktif = TRUE AND i.tipe_item = 'barang_jadi'
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ");

            // 5. Ambil Matriks Harga Level Grup Produk
            $rawLevelPrices = Database::fetchAll("
                SELECT grup_produk_id, level_harga, harga_jual_pcs, harga_jual_bal
                FROM public.grup_produk_harga_level
            ");
            $priceMatrix = [];
            foreach ($rawLevelPrices as $lp) {
                $priceMatrix[$lp['grup_produk_id']][$lp['level_harga']] = [
                    'pcs' => (float)$lp['harga_jual_pcs'],
                    'bal' => (float)$lp['harga_jual_bal']
                ];
            }

            // 6. Whitelist item pelanggan
            $rawWhitelist = Database::fetchAll("SELECT pelanggan_id, item_id FROM public.pelanggan_item");
            $whitelistMap = [];
            foreach ($rawWhitelist as $w) {
                $whitelistMap[$w['pelanggan_id']][] = $w['item_id'];
            }

            // 7. Auto Generate Nomor Faktur Format: KRS-YYMM-XXXX
            $yearMonth = date('ym');
            $latestNota = Database::fetchOne("
                SELECT nomor_nota FROM public.pesanan 
                WHERE nomor_nota LIKE 'KRS-{$yearMonth}-%'
                ORDER BY nomor_nota DESC LIMIT 1
            ");

            $nextSeq = 1;
            if ($latestNota && !empty($latestNota['nomor_nota'])) {
                $parts = explode('-', $latestNota['nomor_nota']);
                if (isset($parts[2])) {
                    $nextSeq = ((int)$parts[2]) + 1;
                }
            }
            $autoNota = sprintf("KRS-%s-%04d", $yearMonth, $nextSeq);

            $this->view('customer_orders.create', [
                'pageTitle' => 'Input Pesanan Pelanggan Baru',
                'pageSubtitle' => 'Penerbitan Faktur Penjualan Reguler Toko Mitra',
                'customers' => $customers,
                'drivers' => $drivers,
                'cashAccounts' => $cashAccounts,
                'products' => $products,
                'priceMatrix' => $priceMatrix,
                'whitelistMap' => $whitelistMap,
                'autoNota' => $autoNota,
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
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
            $stmtPelanggan = $pdo->prepare("SELECT is_konsinyasi, sales_driver_id, wilayah_id FROM public.pelanggan WHERE id = :id");
            $stmtPelanggan->execute(['id' => $pelangganId]);
            $pelangganInfo = $stmtPelanggan->fetch(\PDO::FETCH_ASSOC);
            
            $isKonsinyasi = false;
            $salesDriverId = null;
            $ruteWilayahId = null;
            
            if ($pelangganInfo) {
                $isKonsinyasi = (bool)$pelangganInfo['is_konsinyasi'];
                $driverInput = $this->input('sales_driver_id') ?: null;
                $salesDriverId = $driverInput ?: null; // PO baru belum memiliki driver (penugasan dilakukan saat pembuatan Surat Jalan / Siap Kirim)
                $ruteWilayahId = $pelangganInfo['wilayah_id'];
            }
            
            // Atur atribut sesuai tipe pesanan
            $adalahTagihan = true;
            $statusSuratJalanAwal = 'siap_kirim';
            
            if ($isKonsinyasi) {
                $tipePembayaran = 'konsinyasi';
                $adalahTagihan = false; // PRD: Kiriman konsinyasi bukan tagihan riil
                $statusSuratJalanAwal = 'draf_n8n'; // PRD: Butuh approval owner
            }

            $pdo->beginTransaction();

            // Anti-Collision: Cek dan generate sequence unik nomor faktur di dalam transaksi
            $checkNota = $pdo->prepare("SELECT count(*) FROM public.pesanan WHERE nomor_nota = :nota");
            $checkNota->execute(['nota' => $nomorNota]);
            if ((int)$checkNota->fetchColumn() > 0) {
                $yearMonth = date('ym');
                $stmtLatestNota = $pdo->prepare("SELECT nomor_nota FROM public.pesanan WHERE nomor_nota LIKE :pattern ORDER BY nomor_nota DESC LIMIT 1");
                $stmtLatestNota->execute(['pattern' => "KRS-{$yearMonth}-%"]);
                $latestNota = $stmtLatestNota->fetchColumn();
                $seq = 1;
                if ($latestNota) {
                    $parts = explode('-', (string)$latestNota);
                    if (isset($parts[2])) {
                        $seq = ((int)$parts[2]) + 1;
                    }
                }
                $nomorNota = sprintf("KRS-%s-%04d", $yearMonth, $seq);
            }

            // 1. Hitung total bruto & netto (Untuk Konsinyasi, pakai HPP)
            $totalBruto = 0;
            $totalDiskonItem = 0;

            foreach ($items as &$it) {
                $qty = (int)($it['qty'] ?? 1);
                $diskon = (float)($it['diskon'] ?? 0);
                
                if ($isKonsinyasi) {
                    // Pakai HPP untuk valuasi internal
                    $hppData = Database::fetchOne("SELECT harga_pokok_pembelian FROM public.item WHERE id = :id", ['id' => $it['item_id']]);
                    $harga = (float)($hppData['harga_pokok_pembelian'] ?? 0);
                    $it['harga'] = $harga;
                } else {
                    $harga = (float)($it['harga'] ?? 0);
                }
                
                $subtotal = ($qty * $harga) - $diskon;
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
                $totalDibayar = 0;
                $sisaTagihan = $totalNetto; // Nilai HPP internal
                $statusBayar = 'belum_lunas';
            } else if ($tipePembayaran === 'cash' || $tipePembayaran === 'qris' || $tipePembayaran === 'transfer') {
                $totalDibayar = $totalNetto;
                $sisaTagihan = 0;
                $statusBayar = 'lunas';
            } else if ($tipePembayaran === 'sebagian') {
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
                $sisaTagihan = max(0, $totalNetto - $totalDibayar);
                $statusBayar = ($sisaTagihan <= 0) ? 'lunas' : 'belum_lunas';
            } else {
                $totalDibayar = 0;
                $sisaTagihan = $totalNetto;
                $statusBayar = 'belum_lunas';
            }

            // 3. Insert Header Pesanan (Tahap 1: Status PO)
            $stmt = $pdo->prepare("
                INSERT INTO public.pesanan (
                    nomor_nota, pelanggan_id, sales_driver_id, tanggal_pesanan,
                    total_bruto, total_diskon, total_netto, total_dibayar, sisa_tagihan,
                    tipe_pembayaran, tanggal_jatuh_tempo, akun_kas_id,
                    status_pembayaran, status_pemrosesan, catatan, adalah_tagihan,
                    dibuat_pada
                ) VALUES (
                    :nota, :pelanggan, :driver, :tgl,
                    :bruto, :diskon, :netto, :dibayar, :sisa,
                    :tipe, :tempo, :akun_kas,
                    :status_bayar, 'po', :catatan, :adalah_tagihan,
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
                'adalah_tagihan' => $adalahTagihan ? 'true' : 'false'
            ]);

            $orderId = $stmt->fetchColumn();

            // 4. Insert Detail Items (Stok gudang belum dipotong di tahap PO)
            $stmtItem = $pdo->prepare("
                INSERT INTO public.item_pesanan (
                    pesanan_id, item_id, kuantitas_satuan_dasar, kuantitas_satuan_distribusi,
                    harga_satuan_deal, diskon_item_nominal, is_bonus, subtotal, dibuat_pada
                ) VALUES (
                    :pesanan_id, :item_id, :qty_dasar, :qty_dist,
                    :harga, :diskon, :bonus, :subtotal, NOW()
                )
            ");

            foreach ($items as $it) {
                $itemId = $it['item_id'];
                $qtyPcs = (int)($it['qty'] ?? 1);
                $harga = (float)($it['harga'] ?? 0);
                $diskon = (float)($it['diskon'] ?? 0);
                $subtotal = $it['subtotal'];
                $isBonus = !empty($it['is_bonus']);

                $stmtItem->execute([
                    'pesanan_id' => $orderId,
                    'item_id' => $itemId,
                    'qty_dasar' => $qtyPcs,
                    'qty_dist' => 0,
                    'harga' => $harga,
                    'diskon' => $diskon,
                    'bonus' => $isBonus ? 'true' : 'false',
                    'subtotal' => $subtotal,
                ]);
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
                       COALESCE(pel.override_level_harga, gp.default_level_harga, 1) as level_harga,
                       gp.nama_grup as nama_grup_harga
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
                       i.nama_item, i.kode_sku, i.barcode, i.varian_rasa, i.stok_fisik_saat_ini,
                       gp.nama_grup, gp.kode_grup
                FROM public.item_pesanan ip
                JOIN public.item i ON ip.item_id = i.id
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE ip.pesanan_id = :id
                ORDER BY i.nama_item ASC
            ", ['id' => $id]);

            // Ambil Master Sales-Driver
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

            // Ambil Katalog Barang Jadi
            $products = Database::fetchAll("
                SELECT i.id, i.grup_id, i.kode_sku, i.barcode, i.nama_item, i.varian_rasa,
                       i.satuan_dasar, i.satuan_distribusi, i.stok_fisik_saat_ini,
                       gp.nama_grup, gp.kode_grup
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE i.status_aktif = TRUE AND i.tipe_item = 'barang_jadi'
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ");

            // Ambil Matriks Harga Level Grup Produk
            $rawLevelPrices = Database::fetchAll("
                SELECT grup_produk_id, level_harga, harga_jual_pcs, harga_jual_bal
                FROM public.grup_produk_harga_level
            ");
            $priceMatrix = [];
            foreach ($rawLevelPrices as $lp) {
                $priceMatrix[$lp['grup_produk_id']][$lp['level_harga']] = [
                    'pcs' => (float)$lp['harga_jual_pcs'],
                    'bal' => (float)$lp['harga_jual_bal']
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

                if ($isKonsinyasi) {
                    $hppData = Database::fetchOne("SELECT harga_pokok_pembelian FROM public.item WHERE id = :id", ['id' => $it['item_id']]);
                    $harga = (float)($hppData['harga_pokok_pembelian'] ?? 0);
                    $it['harga'] = $harga;
                } else {
                    $harga = (float)($it['harga'] ?? 0);
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

            // Hitung status dan nominal pembayaran
            if ($isKonsinyasi) {
                $totalDibayar = 0;
                $sisaTagihan = 0;
                $statusBayar = 'lunas';
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
                    $sisaTagihan = 0;
                    $statusBayar = 'lunas';
                } else {
                    $totalDibayar = $totalDibayarLama;
                    $sisaTagihan = max(0, $totalNetto - $totalDibayar);
                    $statusBayar = ($sisaTagihan <= 0) ? 'lunas' : (($totalDibayar > 0) ? 'sebagian' : 'belum_lunas');
                }
            } elseif ($tipePembayaran === 'cash' || $tipePembayaran === 'qris' || $tipePembayaran === 'transfer') {
                $totalDibayar = $totalNetto;
                $sisaTagihan = 0;
                $statusBayar = 'lunas';
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
                $sisaTagihan = max(0, $totalNetto - $totalDibayar);
                $statusBayar = ($sisaTagihan == 0) ? 'lunas' : (($totalDibayar > 0) ? 'sebagian' : 'belum_lunas');
            } else {
                $totalDibayar = 0;
                $sisaTagihan = $totalNetto;
                $statusBayar = 'belum_lunas';
            }

            // Driver tetap dari pesanan / profil toko (tidak diubah di form PO edit)
            $driverId = $order['sales_driver_id'];
            $adalahTagihan = $isKonsinyasi ? false : true;

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
                    adalah_tagihan = :adalah_tagihan,
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
                'adalah_tagihan' => $adalahTagihan ? 'true' : 'false',
                'id' => $id,
            ]);

            // 4. Insert Detail Items Baru (Stok gudang belum dipotong di tahap PO)
            $stmtItem = $pdo->prepare("
                INSERT INTO public.item_pesanan (
                    pesanan_id, item_id, kuantitas_satuan_dasar, kuantitas_satuan_distribusi,
                    harga_satuan_deal, diskon_item_nominal, is_bonus, subtotal, dibuat_pada
                ) VALUES (
                    :pesanan_id, :item_id, :qty_dasar, :qty_dist,
                    :harga, :diskon, :bonus, :subtotal, NOW()
                )
            ");

            foreach ($items as $it) {
                $itemId = $it['item_id'];
                $qtyPcs = (int)($it['qty'] ?? 1);
                $harga = (float)($it['harga'] ?? 0);
                $diskon = (float)($it['diskon'] ?? 0);
                $subtotal = $it['subtotal'];
                $isBonus = !empty($it['is_bonus']);

                $stmtItem->execute([
                    'pesanan_id' => $id,
                    'item_id' => $itemId,
                    'qty_dasar' => $qtyPcs,
                    'qty_dist' => $qtyPcs,
                    'harga' => $harga,
                    'diskon' => $diskon,
                    'bonus' => $isBonus ? 'true' : 'false',
                    'subtotal' => $subtotal,
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

            $items = Database::fetchAll("
                SELECT ip.*, i.nama_item, i.kode_sku, i.satuan_dasar, gp.nama_grup
                FROM public.item_pesanan ip
                JOIN public.item i ON ip.item_id = i.id
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE ip.pesanan_id = :id
                ORDER BY ip.dibuat_pada ASC
            ", ['id' => $id]);

            $this->view('customer_orders.invoice', [
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

            if (($order['tipe_pembayaran'] ?? '') === 'konsinyasi' || !empty($order['is_konsinyasi']) || (isset($order['adalah_tagihan']) && ($order['adalah_tagihan'] === false || $order['adalah_tagihan'] === 'false'))) {
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

            // 4. Hitung Nilai Baru Pesanan
            $totalDibayarBaru = $totalDibayarLama + $nominalBayar;
            $sisaTagihanBaru = max(0, $totalNetto - $totalDibayarBaru);
            $statusBaru = ($sisaTagihanBaru <= 0) ? 'lunas' : 'belum_lunas';

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
            $stmtKas = $pdo->prepare("
                INSERT INTO public.arus_kas (
                    akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                    keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                ) VALUES (
                    :akun_kas, :tgl, 'masuk', 'penjualan', :nominal,
                    :ket, 'pesanan', :ref_id, :saldo_berjalan, :user_id, NOW()
                )
            ");
            $userId = Auth::id() ?: null;
            $stmtKas->execute([
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
            $isPhysicalStockCut = in_array($order['status_pemrosesan'] ?? '', ['siap_dikirim', 'siap_kirim', 'sedang_dikirim'], true);

            if ($isPhysicalStockCut) {
                $items = Database::fetchAll("SELECT * FROM public.item_pesanan WHERE pesanan_id = :id", ['id' => $id]);

                $stmtStok = $pdo->prepare("
                    UPDATE public.item 
                    SET stok_fisik_saat_ini = stok_fisik_saat_ini + :qty,
                        diubah_pada = NOW()
                    WHERE id = :item_id
                ");

                $stmtRiwayat = $pdo->prepare("
                    INSERT INTO public.riwayat_stok (
                        item_id, tipe_mutasi, jumlah_perubahan,
                        stok_sebelum, stok_sesudah, referensi_tabel, referensi_id,
                        keterangan, dibuat_oleh, dibuat_pada
                    ) VALUES (
                        :item_id, 'penyesuaian_opname_tambah', :qty,
                        :stok_sebelum, :stok_sesudah, 'pesanan', :ref_id,
                        :ket, :user_id, NOW()
                    )
                ");

                $userId = Auth::id() ?: null;

                foreach ($items as $it) {
                    $qtyPcs = (int)$it['kuantitas_satuan_dasar'];
                    $itemId = $it['item_id'];

                    $itemData = Database::fetchOne("SELECT stok_fisik_saat_ini FROM public.item WHERE id = :id FOR UPDATE", ['id' => $itemId]);
                    $stokSebelum = $itemData ? (int)$itemData['stok_fisik_saat_ini'] : 0;
                    $stokSesudah = $stokSebelum + $qtyPcs;

                    $stmtStok->execute([
                        'qty' => $qtyPcs,
                        'item_id' => $itemId,
                    ]);

                    $stmtRiwayat->execute([
                        'item_id' => $itemId,
                        'qty' => $qtyPcs,
                        'stok_sebelum' => $stokSebelum,
                        'stok_sesudah' => $stokSesudah,
                        'ref_id' => $id,
                        'ket' => "Pembatalan Pesanan #{$order['nomor_nota']} ({$order['status_pemrosesan']})",
                        'user_id' => $userId,
                    ]);
                }
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

                $count = Database::fetchOne("SELECT count(*) as total FROM public.surat_jalan")['total'] ?? 0;
                $nomorSj = 'SJ-' . date('Ymd') . '-' . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
                $dId = !empty($driverId) ? $driverId : ($pesanan['sales_driver_id'] ?: null);

                $stmtNew = $pdo->prepare("
                    INSERT INTO public.surat_jalan (
                        nomor_surat_jalan, pesanan_id, sales_driver_id, rute_wilayah_id,
                        status_surat_jalan, disetujui_oleh, dibuat_pada
                    ) VALUES (
                        :no_sj, :pesanan_id, :driver_id, :wilayah_id,
                        :status, :user_id, NOW()
                    )
                ");
                $stmtNew->execute([
                    'no_sj' => $nomorSj,
                    'pesanan_id' => $orderId,
                    'driver_id' => $dId,
                    'wilayah_id' => $pesanan['wilayah_id'] ?? null,
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

            // Sync stok rak konsinyasi jika pesanan ini adalah titip konsinyasi dan status selesai_diterima
            if ($statusBaru === 'selesai_diterima') {
                $orderData = Database::fetchOne("
                    SELECT p.pelanggan_id, pel.is_konsinyasi, p.tipe_pembayaran 
                    FROM public.pesanan p 
                    JOIN public.pelanggan pel ON p.pelanggan_id = pel.id 
                    WHERE p.id = :id
                ", ['id' => $orderId]);

                if ($orderData && ($orderData['is_konsinyasi'] || $orderData['tipe_pembayaran'] === 'konsinyasi')) {
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
                LEFT JOIN public.surat_jalan sj ON sj.pesanan_id = p.id
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

            $sql .= " ORDER BY p.dibuat_pada DESC";
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

            $this->view('customer_orders.po_list', [
                'pageTitle' => 'Daftar PO Pelanggan',
                'pageSubtitle' => 'Verifikasi Kesiapan Stok & Penyiapan Barang Gudang',
                'poList' => $poList,
                'tab' => $tab,
                'q' => $q,
                'pelangganId' => $pelangganId,
                'countPending' => $countPending,
                'countReady' => $countReady,
                'countReadyNoSj' => $countReadyNoSj,
                'countFailed' => $countFailed,
                'countDeficit' => $countDeficit,
                'customers' => $customers,
                'drivers' => $drivers,
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    /**
     * Proses PO menjadi Siap Kirim (Potong Stok Fisik Gudang)
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

            // Ambil semua item dalam PO
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

            // Validasi & Potong Stok untuk setiap item
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

            ActivityLog::log(
                'gudang',
                'UPDATE',
                "Petugas gudang menyelesaikan penyiapan barang PO #{$order['nomor_nota']} ({$order['nama_toko']}). Stok fisik gudang terpotong, status pesanan menjadi Siap Dikirim.",
                'pesanan',
                (string)$orderId
            );

            $pdo->commit();

            $this->flashSuccess("PO #{$order['nomor_nota']} ({$order['nama_toko']}) berhasil disiapkan! Stok fisik gudang telah terpotong.");
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
     */
    public function printPickingList(): void
    {
        Auth::requirePermission(['orders.po_print', 'orders.po_view_all', 'orders.po_view_assigned']);

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID Pesanan tidak valid.');
            $this->redirect('/customer-orders/po-list');
            return;
        }

        try {
            $order = Database::fetchOne("
                SELECT p.*, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
                       COALESCE(k_sj.nama_karyawan, k_p.nama_karyawan) as nama_driver,
                       COALESCE(k_sj.nomor_polisi_kendaraan, k_p.nomor_polisi_kendaraan) as nopol_driver,
                       w.nama_wilayah, w.kode_rute,
                       sj.nomor_surat_jalan
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.surat_jalan sj ON sj.pesanan_id = p.id
                LEFT JOIN public.v_karyawan_info k_p ON p.sales_driver_id = k_p.id
                LEFT JOIN public.v_karyawan_info k_sj ON sj.sales_driver_id = k_sj.id
                LEFT JOIN public.wilayah w ON COALESCE(sj.rute_wilayah_id, pel.wilayah_id) = w.id
                WHERE p.id = :id
            ", ['id' => $id]);

            if (!$order) {
                $this->flashError('Pesanan tidak ditemukan.');
                $this->redirect('/customer-orders/po-list');
                return;
            }

            $items = Database::fetchAll("
                SELECT ip.*, it.nama_item, it.kode_sku, it.stok_fisik_saat_ini, it.satuan_dasar
                FROM public.item_pesanan ip
                JOIN public.item it ON ip.item_id = it.id
                WHERE ip.pesanan_id = :id
                ORDER BY it.nama_item ASC
            ", ['id' => $id]);

            $this->view('customer_orders.picking_list', [
                'pageTitle' => 'Picking List #' . $order['nomor_nota'],
                'order' => $order,
                'items' => $items
            ]);

        } catch (Throwable $e) {
            echo "Error: " . $e->getMessage();
        }
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
     */
    public function invoicePdf(): void
    {
        Auth::requirePermission(['orders.view_all', 'orders.view_assigned']);
        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID Pesanan tidak valid.');
            $this->redirect('/customer-orders');
            return;
        }

        try {
            $order = Database::fetchOne("
                SELECT p.*, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
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

            $items = Database::fetchAll("
                SELECT ip.*, i.nama_item, i.kode_sku, i.satuan_dasar, gp.nama_grup
                FROM public.item_pesanan ip
                JOIN public.item i ON ip.item_id = i.id
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE ip.pesanan_id = :id
                ORDER BY ip.dibuat_pada ASC
            ", ['id' => $id]);

            ob_start();
            extract(['order' => $order, 'items' => $items, 'isPdf' => true]);
            require ROOT_PATH . '/views/customer_orders/invoice.php';
            $html = ob_get_clean();

            $cleanNota = preg_replace('/[^A-Za-z0-9\-]/', '_', (string)$order['nomor_nota']);
            PdfExport::download($html, "Faktur-{$cleanNota}.pdf", 'A4', 'portrait');
        } catch (Throwable $e) {
            $this->flashError('Gagal membuat PDF: ' . $e->getMessage());
            $this->redirect('/customer-orders/invoice?id=' . urlencode((string)$id));
        }
    }

    /**
     * Unduh Faktur Rincian Item dalam Format Excel (PhpSpreadsheet Library)
     */
    public function invoiceExcel(): void
    {
        Auth::requirePermission(['orders.view_all', 'orders.view_assigned']);
        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID Pesanan tidak valid.');
            $this->redirect('/customer-orders');
            return;
        }

        try {
            $order = Database::fetchOne("
                SELECT p.*, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
                       k.nama_karyawan as nama_sales
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.id = :id
            ", ['id' => $id]);

            if (!$order) {
                $this->flashError('Faktur pesanan tidak ditemukan.');
                $this->redirect('/customer-orders');
                return;
            }

            $items = Database::fetchAll("
                SELECT ip.*, i.nama_item, i.kode_sku, i.satuan_dasar, gp.nama_grup
                FROM public.item_pesanan ip
                JOIN public.item i ON ip.item_id = i.id
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE ip.pesanan_id = :id
                ORDER BY ip.dibuat_pada ASC
            ", ['id' => $id]);

            $headers = ['No', 'Kode SKU', 'Nama Produk Snack', 'Kategori Kemasan', 'Harga Satuan (Rp)', 'Qty (Pcs)', 'Diskon (Rp)', 'Subtotal (Rp)'];
            $rows = [];
            $no = 1;
            foreach ($items as $it) {
                $rows[] = [
                    $no++,
                    $it['kode_sku'] ?? '-',
                    $it['nama_item'] ?? '-',
                    $it['nama_grup'] ?? '-',
                    (float)($it['harga_satuan'] ?? 0),
                    (int)($it['kuantitas_satuan_dasar'] ?? 0),
                    (float)($it['diskon_nominal'] ?? 0),
                    (float)($it['subtotal'] ?? 0)
                ];
            }

            // Tambahkan baris total
            $rows[] = ['', '', '', '', '', '', 'Total Bruto (Rp):', (float)($order['total_bruto'] ?? 0)];
            $rows[] = ['', '', '', '', '', '', 'Total Diskon (Rp):', (float)($order['total_diskon'] ?? 0)];
            $rows[] = ['', '', '', '', '', '', 'TOTAL NETTO (Rp):', (float)($order['total_netto'] ?? 0)];
            $rows[] = ['', '', '', '', '', '', 'Telah Dibayar (Rp):', (float)($order['total_dibayar'] ?? 0)];
            $rows[] = ['', '', '', '', '', '', 'Sisa Tagihan (Rp):', (float)($order['sisa_tagihan'] ?? 0)];

            $cleanNota = preg_replace('/[^A-Za-z0-9\-]/', '_', (string)$order['nomor_nota']);
            ExcelExport::download("Faktur-{$cleanNota}.xlsx", $headers, $rows, "Faktur {$cleanNota}");
        } catch (Throwable $e) {
            $this->flashError('Gagal export Excel: ' . $e->getMessage());
            $this->redirect('/customer-orders/invoice?id=' . urlencode((string)$id));
        }
    }

    /**
     * Export Seluruh Daftar Pesanan ke File Excel (PhpSpreadsheet)
     */
    public function exportExcel(): void
    {
        Auth::requirePermission(['orders.view_all', 'orders.view_assigned']);

        try {
            $startDate = $this->input('start_date', date('Y-m-01'));
            $endDate = $this->input('end_date', date('Y-m-d'));
            $pelangganId = $this->input('pelanggan_id');
            $statusBayar = $this->input('status_pembayaran');
            $q = trim((string)$this->input('q', ''));

            $sql = "
                SELECT p.*, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.is_konsinyasi,
                       k.nama_karyawan as nama_sales
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
                WHERE p.tanggal_pesanan >= :start AND p.tanggal_pesanan <= :end
            ";
            $params = ['start' => $startDate, 'end' => $endDate];

            if (!empty($pelangganId)) {
                $sql .= " AND p.pelanggan_id = :pelanggan_id";
                $params['pelanggan_id'] = $pelangganId;
            }
            if (!empty($statusBayar)) {
                $sql .= " AND p.status_pembayaran = :status_bayar";
                $params['status_bayar'] = $statusBayar;
            }
            if (!empty($q)) {
                $sql .= " AND (p.nomor_nota ILIKE :q OR pel.nama_toko ILIKE :q OR pel.kode_pelanggan ILIKE :q)";
                $params['q'] = "%{$q}%";
            }

            $sql .= " ORDER BY p.tanggal_pesanan DESC, p.dibuat_pada DESC";
            $orders = Database::fetchAll($sql, $params);

            $headers = ['No', 'Nomor Nota', 'Tanggal', 'Kode Toko', 'Nama Toko Pelanggan', 'Sales / PIC', 'Tipe Pembayaran', 'Total Bruto (Rp)', 'Total Diskon (Rp)', 'Total Netto (Rp)', 'Dibayar (Rp)', 'Sisa Tagihan (Rp)', 'Status Bayar', 'Status Proses'];
            $rows = [];
            $no = 1;
            foreach ($orders as $o) {
                $rows[] = [
                    $no++,
                    $o['nomor_nota'],
                    date('d/m/Y', strtotime($o['tanggal_pesanan'])),
                    $o['kode_pelanggan'] ?? '-',
                    $o['nama_toko'],
                    $o['nama_sales'] ?? 'Armada / Toko',
                    ucfirst(str_replace('_', ' ', (string)$o['tipe_pembayaran'])),
                    (float)$o['total_bruto'],
                    (float)$o['total_diskon'],
                    (float)$o['total_netto'],
                    (float)$o['total_dibayar'],
                    (float)$o['sisa_tagihan'],
                    strtoupper(str_replace('_', ' ', (string)$o['status_pembayaran'])),
                    strtoupper(str_replace('_', ' ', (string)$o['status_pemrosesan']))
                ];
            }

            ExcelExport::download("Daftar-Pesanan-{$startDate}-sd-{$endDate}.xlsx", $headers, $rows, "Daftar Pesanan");
        } catch (Throwable $e) {
            $this->flashError('Gagal export data pesanan: ' . $e->getMessage());
            $this->redirect('/customer-orders');
        }
    }

    /**
     * Unduh Lembar Ambil Barang (Picking List) dalam Format PDF (Dompdf Library)
     */
    public function pickingListPdf(): void
    {
        Auth::requirePermission(['orders.po_print', 'orders.po_view_all', 'orders.po_view_assigned']);

        $id = $this->input('id');
        if (empty($id)) {
            $this->flashError('ID Pesanan tidak valid.');
            $this->redirect('/customer-orders/po-list');
            return;
        }

        try {
            $order = Database::fetchOne("
                SELECT p.*, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
                       COALESCE(k_sj.nama_karyawan, k_p.nama_karyawan) as nama_driver,
                       COALESCE(k_sj.nomor_polisi_kendaraan, k_p.nomor_polisi_kendaraan) as nopol_driver,
                       w.nama_wilayah, w.kode_rute,
                       sj.nomor_surat_jalan
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.surat_jalan sj ON sj.pesanan_id = p.id
                LEFT JOIN public.v_karyawan_info k_p ON p.sales_driver_id = k_p.id
                LEFT JOIN public.v_karyawan_info k_sj ON sj.sales_driver_id = k_sj.id
                LEFT JOIN public.wilayah w ON COALESCE(sj.rute_wilayah_id, pel.wilayah_id) = w.id
                WHERE p.id = :id
            ", ['id' => $id]);

            if (!$order) {
                $this->flashError('Pesanan tidak ditemukan.');
                $this->redirect('/customer-orders/po-list');
                return;
            }

            $items = Database::fetchAll("
                SELECT ip.*, it.nama_item, it.kode_sku, it.stok_fisik_saat_ini, it.satuan_dasar
                FROM public.item_pesanan ip
                JOIN public.item it ON ip.item_id = it.id
                WHERE ip.pesanan_id = :id
                ORDER BY it.nama_item ASC
            ", ['id' => $id]);

            ob_start();
            extract(['pageTitle' => 'Picking List #' . $order['nomor_nota'], 'order' => $order, 'items' => $items, 'isPdf' => true]);
            require ROOT_PATH . '/views/customer_orders/picking_list.php';
            $html = ob_get_clean();

            $cleanNota = preg_replace('/[^A-Za-z0-9\-]/', '_', (string)$order['nomor_nota']);
            PdfExport::download($html, "PickingList-{$cleanNota}.pdf", 'A4', 'portrait');
        } catch (Throwable $e) {
            $this->flashError('Gagal membuat PDF Picking List: ' . $e->getMessage());
            $this->redirect('/customer-orders/picking-list?id=' . urlencode((string)$id));
        }
    }

    /**
     * Batch export multiple PO item lists into a single consolidated PDF document.
     */
    public function batchPickingListPdf(): void
    {
        Auth::requirePermission(['orders.po_print', 'orders.po_view_all', 'orders.po_view_assigned']);

        try {
            $idsParam = $this->input('ids');
            $orderIdsInput = $this->input('order_ids');
            $tab = $this->input('tab', 'pending');
            $q = trim((string)$this->input('q', ''));
            $pelangganId = $this->input('pelanggan_id', '');

            $targetIds = [];
            if (!empty($idsParam)) {
                $targetIds = array_filter(array_map('trim', explode(',', (string)$idsParam)));
            } elseif (!empty($orderIdsInput) && is_array($orderIdsInput)) {
                $targetIds = array_filter(array_map('trim', $orderIdsInput));
            }

            $sql = "
                SELECT p.id, p.nomor_nota, p.tanggal_pesanan, p.total_bruto, p.total_diskon, p.total_netto,
                       p.status_pembayaran, p.status_pemrosesan, p.catatan, p.dibuat_pada,
                       pel.id as pelanggan_id, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
                       (SELECT COUNT(*) FROM public.item_pesanan ip WHERE ip.pesanan_id = p.id) as total_sku,
                       (SELECT COALESCE(SUM(kuantitas_satuan_dasar), 0) FROM public.item_pesanan ip WHERE ip.pesanan_id = p.id) as total_pcs
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                WHERE p.status_pembayaran != 'dibatalkan'
            ";

            $params = [];

            // Permission scoping for sales/driver
            if (!Auth::can('orders.po_view_all')) {
                $myEmpId = Auth::employeeId();
                if ($myEmpId) {
                    $sql .= " AND (p.sales_driver_id = :my_emp_id OR pel.sales_driver_id = :my_emp_id)";
                    $params['my_emp_id'] = $myEmpId;
                } else {
                    $sql .= " AND 1=0";
                }
            }

            if (!empty($targetIds)) {
                $placeholders = [];
                foreach ($targetIds as $idx => $tId) {
                    $key = 'target_id_' . $idx;
                    $placeholders[] = ':' . $key;
                    $params[$key] = $tId;
                }
                $sql .= " AND p.id IN (" . implode(',', $placeholders) . ")";
            } else {
                // Filter by tab and criteria
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
            }

            $sql .= " ORDER BY p.dibuat_pada ASC";
            $orders = Database::fetchAll($sql, $params);

            if (empty($orders)) {
                $this->flashError('Tidak ada data PO yang sesuai untuk diunduh sebagai PDF.');
                $this->redirect('/customer-orders/po-list');
                return;
            }

            // Batch fetch items for all selected orders
            $orderIds = array_column($orders, 'id');
            $itemParams = [];
            $itemPlaceholders = [];
            foreach ($orderIds as $idx => $oId) {
                $key = 'ord_id_' . $idx;
                $itemPlaceholders[] = ':' . $key;
                $itemParams[$key] = $oId;
            }

            $rawItems = Database::fetchAll("
                SELECT ip.*, it.nama_item, it.kode_sku, it.stok_fisik_saat_ini, it.satuan_dasar, it.barcode
                FROM public.item_pesanan ip
                JOIN public.item it ON ip.item_id = it.id
                WHERE ip.pesanan_id IN (" . implode(',', $itemPlaceholders) . ")
                ORDER BY it.nama_item ASC
            ", $itemParams);

            $itemsByOrder = [];
            foreach ($rawItems as $ri) {
                $itemsByOrder[$ri['pesanan_id']][] = $ri;
            }

            foreach ($orders as &$ord) {
                $ord['items'] = $itemsByOrder[$ord['id']] ?? [];
            }
            unset($ord);

            ob_start();
            extract([
                'pageTitle' => 'Batch Item Pesanan PO (' . count($orders) . ' Nota)',
                'orders' => $orders,
                'isPdf' => true
            ]);
            require ROOT_PATH . '/views/customer_orders/batch_picking_list.php';
            $html = ob_get_clean();

            $dateSuffix = date('Ymd_Hi');
            $countSuffix = count($orders);
            PdfExport::download($html, "Batch_PO_{$countSuffix}Nota_{$dateSuffix}.pdf", 'A4', 'portrait');
        } catch (Throwable $e) {
            $this->flashError('Gagal membuat PDF Batch PO: ' . $e->getMessage());
            $this->redirect('/customer-orders/po-list');
        }
    }
}


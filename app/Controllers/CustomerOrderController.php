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
                       p.status_pembayaran, p.status_pemrosesan, p.catatan, p.dibuat_pada,
                       pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp,
                       COALESCE(k_sj.nama_karyawan, k_p.nama_karyawan) as nama_sales,
                       COALESCE(k_sj.nomor_polisi_kendaraan, k_p.nomor_polisi_kendaraan) as nopol_driver,
                       ak.nama_akun as nama_akun_kas,
                       sj.id as surat_jalan_id, sj.nomor_surat_jalan, sj.status_surat_jalan,
                       w.nama_wilayah,
                       (SELECT COUNT(*) FROM public.item_pesanan ip WHERE ip.pesanan_id = p.id) as total_sku_items,
                       (SELECT COALESCE(SUM(kuantitas_satuan_dasar), 0) FROM public.item_pesanan ip WHERE ip.pesanan_id = p.id) as total_pcs_items
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.surat_jalan sj ON sj.pesanan_id = p.id
                LEFT JOIN public.karyawan k_p ON p.sales_driver_id = k_p.id
                LEFT JOIN public.karyawan k_sj ON sj.sales_driver_id = k_sj.id
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
            $drivers = Database::fetchAll("SELECT id, nama_karyawan, nomor_polisi_kendaraan FROM public.karyawan WHERE posisi = 'sales_driver' AND status_aktif = TRUE ORDER BY nama_karyawan ASC");
            $cashAccounts = Database::fetchAll("SELECT id, nama_akun, saldo_saat_ini, is_default_pos FROM public.akun_kas WHERE status_aktif = TRUE ORDER BY is_default_pos DESC, nama_akun ASC");

            $this->view('customer_orders.index', [
                'pageTitle' => 'Pesanan Pelanggan',
                'pageSubtitle' => 'Daftar Transaksi & Faktur B2B Toko Mitra',
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
                       p.status_pembayaran, p.status_pemrosesan, p.catatan, p.dibuat_pada,
                       pel.id as pelanggan_id, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap,
                       p.sales_driver_id,
                       COALESCE(k_sj.nama_karyawan, k_p.nama_karyawan) as nama_sales,
                       COALESCE(k_sj.nomor_polisi_kendaraan, k_p.nomor_polisi_kendaraan) as nopol_driver,
                       ak.id as akun_kas_id, ak.nama_akun as nama_akun_kas,
                       sj.id as surat_jalan_id, sj.nomor_surat_jalan, sj.status_surat_jalan
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.surat_jalan sj ON sj.pesanan_id = p.id
                LEFT JOIN public.karyawan k_p ON p.sales_driver_id = k_p.id
                LEFT JOIN public.karyawan k_sj ON sj.sales_driver_id = k_sj.id
                LEFT JOIN public.akun_kas ak ON p.akun_kas_id = ak.id
                WHERE p.id = :id
            ";
            $order = Database::fetchOne($sqlOrder, ['id' => $id]);

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
                exit;
            }

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
                FROM public.karyawan 
                WHERE posisi = 'sales_driver' AND status_aktif = TRUE 
                ORDER BY nama_karyawan ASC
            ");

            $cashAccounts = Database::fetchAll("
                SELECT id, nama_akun, saldo_saat_ini, is_default_pos 
                FROM public.akun_kas 
                WHERE status_aktif = TRUE 
                ORDER BY is_default_pos DESC, nama_akun ASC
            ");

            echo json_encode([
                'success' => true,
                'order' => $order,
                'items' => $items,
                'drivers' => $drivers,
                'cashAccounts' => $cashAccounts,
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
                       p.alamat_lengkap, p.tipe_pembayaran_default, p.is_konsinyasi,
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
                FROM public.karyawan
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
                'pageSubtitle' => 'Penerbitan Faktur Penjualan B2B Toko Mitra',
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
        $printDirect = (bool)$this->input('print_direct', false);

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
                $salesDriverId = $pelangganInfo['sales_driver_id']; // PRD: otomatis assign ke sales tetap
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
            } else if ($tipePembayaran === 'cash') {
                $totalDibayar = $totalNetto;
                $sisaTagihan = 0;
                $statusBayar = 'lunas';
            } else if ($tipePembayaran === 'sebagian') {
                $totalDibayar = min($totalNetto, max(0, $nominalDibayarInput));
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

            if ($printDirect) {
                $this->redirect("/customer-orders/picking-list?id={$orderId}");
                return;
            }

            $this->flashSuccess("Purchase Order (PO) #{$nomorNota} berhasil diterbitkan dan masuk ke antrean Daftar PO Gudang.");
            $this->redirect('/customer-orders/po-list');
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

            // Validasi Surat Jalan: Jika sudah dibuat surat jalan, tolak edit
            $sj = Database::fetchOne("
                SELECT id, nomor_surat_jalan, status_surat_jalan 
                FROM public.surat_jalan 
                WHERE pesanan_id = :id AND status_surat_jalan NOT IN ('dibatalkan')
                LIMIT 1
            ", ['id' => $id]);

            if ($sj) {
                $this->flashError("Pesanan ini sudah memiliki Surat Jalan aktif (#{$sj['nomor_surat_jalan']}). Untuk mengedit pesanan, silakan batalkan/hapus Surat Jalan terlebih dahulu.");
                $this->redirectBack('/customer-orders');
                return;
            }

            // Validasi Status: Jangan izinkan edit jika pesanan sudah dikirim/selesai/dibatalkan
            if (in_array($order['status_pemrosesan'] ?? '', ['dikirim', 'selesai', 'selesai_diterima', 'dibatalkan'], true)) {
                $this->flashError("Pesanan dengan status '" . ($order['status_pemrosesan'] ?? '') . "' tidak dapat diedit lagi.");
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
                FROM public.karyawan
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

            $this->view('customer_orders.edit', [
                'pageTitle' => 'Edit Pesanan Pelanggan #' . $order['nomor_nota'],
                'pageSubtitle' => 'Perbarui rincian produk, kuantiti, dan skema harga pesanan',
                'order' => $order,
                'existingItems' => $existingItems,
                'drivers' => $drivers,
                'cashAccounts' => $cashAccounts,
                'products' => $products,
                'priceMatrix' => $priceMatrix,
                'whitelistMap' => $whitelistMap,
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

            // Surat Jalan Check
            $sj = Database::fetchOne("
                SELECT id, nomor_surat_jalan 
                FROM public.surat_jalan 
                WHERE pesanan_id = :id AND status_surat_jalan NOT IN ('dibatalkan')
                LIMIT 1
            ", ['id' => $id]);

            if ($sj) {
                $this->flashError("Pesanan ini sudah memiliki Surat Jalan aktif (#{$sj['nomor_surat_jalan']}). Untuk mengedit pesanan, silakan batalkan/hapus Surat Jalan terlebih dahulu.");
                $this->redirect('/customer-orders');
                return;
            }

            if (in_array($order['status_pemrosesan'] ?? '', ['dikirim', 'selesai', 'selesai_diterima', 'dibatalkan'], true)) {
                $this->flashError("Pesanan dengan status '" . ($order['status_pemrosesan'] ?? '') . "' tidak dapat diedit lagi.");
                $this->redirect('/customer-orders');
                return;
            }

            $pdo->beginTransaction();

            $isKonsinyasi = (bool)$order['is_konsinyasi'];

            // 1. Kembalikan stok item lama ke gudang
            $oldItems = Database::fetchAll("SELECT item_id, kuantitas_satuan_dasar FROM public.item_pesanan WHERE pesanan_id = :id", ['id' => $id]);
            foreach ($oldItems as $oit) {
                $pdo->prepare("UPDATE public.item SET stok_fisik_saat_ini = stok_fisik_saat_ini + :qty, diubah_pada = NOW() WHERE id = :item_id")
                    ->execute(['qty' => (int)$oit['kuantitas_satuan_dasar'], 'item_id' => $oit['item_id']]);
            }

            // 2. Hapus detail item lama
            $pdo->prepare("DELETE FROM public.item_pesanan WHERE pesanan_id = :id")->execute(['id' => $id]);

            // 3. Hitung total bruto & netto baru
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

                $subtotal = ($qty * $harga) - $diskon;
                $it['subtotal'] = $subtotal;
                $totalBruto += ($qty * $harga);
                $totalDiskonItem += $diskon;
            }
            unset($it);

            $diskonFaktur = (float)preg_replace('/[^0-9]/', '', (string)$this->input('diskon_faktur', '0'));
            $totalDiskon = $totalDiskonItem + $diskonFaktur;
            $totalNetto = max(0, $totalBruto - $totalDiskon);
            $totalDibayar = (float)($order['total_dibayar'] ?? 0);
            $sisaTagihan = max(0, $totalNetto - $totalDibayar);
            $statusBayar = ($totalDibayar >= $totalNetto) ? 'lunas' : (($totalDibayar > 0) ? 'sebagian' : 'belum_lunas');

            // 4. Update Header Pesanan
            $stmtUpdateOrder = $pdo->prepare("
                UPDATE public.pesanan SET
                    tanggal_pesanan = :tgl,
                    total_bruto = :bruto,
                    total_diskon = :diskon,
                    total_netto = :netto,
                    sisa_tagihan = :sisa,
                    status_pembayaran = :status_bayar,
                    tipe_pembayaran = :tipe,
                    tanggal_jatuh_tempo = :tempo,
                    sales_driver_id = :driver_id,
                    catatan = :catatan,
                    diubah_pada = NOW()
                WHERE id = :id
            ");
            $stmtUpdateOrder->execute([
                'tgl' => $tanggalPesanan,
                'bruto' => $totalBruto,
                'diskon' => $totalDiskon,
                'netto' => $totalNetto,
                'sisa' => $sisaTagihan,
                'status_bayar' => $statusBayar,
                'tipe' => $tipePembayaran,
                'tempo' => $tanggalJatuhTempo,
                'driver_id' => $driverId,
                'catatan' => $catatan ?: 'Pesanan Toko Mitra (Diperbarui)',
                'id' => $id,
            ]);

            // 5. Insert Detail Items Baru & Potong Stok Baru
            $stmtItem = $pdo->prepare("
                INSERT INTO public.item_pesanan (
                    pesanan_id, item_id, kuantitas_satuan_dasar, kuantitas_satuan_distribusi,
                    harga_satuan_deal, diskon_item_nominal, is_bonus, subtotal, dibuat_pada
                ) VALUES (
                    :pesanan_id, :item_id, :qty_dasar, :qty_dist,
                    :harga, :diskon, :bonus, :subtotal, NOW()
                )
            ");

            $stmtStok = $pdo->prepare("
                UPDATE public.item 
                SET stok_fisik_saat_ini = stok_fisik_saat_ini - :qty,
                    diubah_pada = NOW()
                WHERE id = :item_id
            ");

            $stmtRiwayatStok = $pdo->prepare("
                INSERT INTO public.riwayat_stok (
                    item_id, tipe_mutasi, jumlah_perubahan,
                    stok_sebelum, stok_sesudah, referensi_tabel, referensi_id,
                    keterangan, dibuat_oleh, dibuat_pada
                ) VALUES (
                    :item_id, 'penyesuaian_stok', :qty,
                    :stok_sebelum, :stok_sesudah, 'pesanan', :ref_id,
                    :ket, :user_id, NOW()
                )
            ");

            $userId = Auth::id() ?: null;

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

                if (!$isKonsinyasi) {
                    $itemData = Database::fetchOne("SELECT stok_fisik_saat_ini, nama_item FROM public.item WHERE id = :id FOR UPDATE", ['id' => $itemId]);
                    $stokSebelum = $itemData ? (float)$itemData['stok_fisik_saat_ini'] : 0;

                    if ($stokSebelum < $qtyPcs) {
                        $namaItem = $itemData['nama_item'] ?? 'Produk';
                        throw new \Exception("Stok {$namaItem} tidak mencukupi setelah pembaruan. Tersedia: {$stokSebelum}, Diminta: {$qtyPcs}");
                    }

                    $stokSesudah = $stokSebelum - $qtyPcs;

                    $stmtStok->execute(['qty' => $qtyPcs, 'item_id' => $itemId]);

                    $stmtRiwayatStok->execute([
                        'item_id' => $itemId,
                        'qty' => $qtyPcs,
                        'stok_sebelum' => $stokSebelum,
                        'stok_sesudah' => $stokSesudah,
                        'ref_id' => $id,
                        'ket' => "Pembaruan Pesanan #{$order['nomor_nota']}",
                        'user_id' => $userId,
                    ]);
                }
            }

            $pdo->commit();

            ActivityLog::log(
                'Pesanan',
                'UPDATE',
                "Memperbarui rincian pesanan #{$order['nomor_nota']} ({$order['nama_toko']}) - Total Netto: Rp " . number_format($totalNetto, 0, ',', '.'),
                'pesanan',
                (string)$id
            );

            $this->flashSuccess("Pesanan #{$order['nomor_nota']} berhasil diperbarui!");
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
                SELECT p.*, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap,
                       k.nama_karyawan as nama_sales,
                       ak.nama_akun as nama_akun_kas
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
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
            $this->flashError('Mohon pilih akun kas dan nominal pembayaran yang valid.');
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

            $totalNetto = (float)$order['total_netto'];
            $totalDibayarLama = (float)$order['total_dibayar'];
            $totalDibayarBaru = $totalDibayarLama + $nominalBayar;
            $sisaTagihanBaru = max(0, $totalNetto - $totalDibayarBaru);
            $statusBaru = ($sisaTagihanBaru <= 0) ? 'lunas' : 'belum_lunas';

            // Update Pesanan
            $stmt = $pdo->prepare("
                UPDATE public.pesanan
                SET total_dibayar = :dibayar,
                    sisa_tagihan = :sisa,
                    status_pembayaran = :status,
                    akun_kas_id = :akun_kas,
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

            // Tambah Saldo Akun Kas
            $akunKas = Database::fetchOne("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $akunKasId]);
            $saldoLama = (float)($akunKas['saldo_saat_ini'] ?? 0);
            $saldoBaru = $saldoLama + $nominalBayar;

            $stmtAkun = $pdo->prepare("
                UPDATE public.akun_kas
                SET saldo_saat_ini = :saldo,
                    diubah_pada = NOW()
                WHERE id = :akun_kas
            ");
            $stmtAkun->execute([
                'saldo' => $saldoBaru,
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
                'saldo_berjalan' => $saldoBaru,
                'user_id' => $userId,
            ]);

            ActivityLog::log(
                'keuangan',
                'INSERT',
                "Penerimaan Pembayaran Piutang Faktur #{$order['nomor_nota']} sebesar " . Format::rupiah($nominalBayar),
                'pesanan',
                $id
            );

            $pdo->commit();

            $this->flashSuccess("Pembayaran sebesar <strong>" . Format::rupiah($nominalBayar) . "</strong> untuk Faktur <strong>{$order['nomor_nota']}</strong> berhasil dicatat!");
            $this->redirect('/customer-orders');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
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

            // 1. Ambil list item untuk dikembalikan ke stok
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

                $itemData = Database::fetchOne("SELECT stok_fisik_saat_ini FROM public.item WHERE id = :id", ['id' => $itemId]);
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
                    'ket' => "Pembatalan Faktur Pesanan Pelanggan #{$order['nomor_nota']}",
                    'user_id' => $userId,
                ]);
            }

            // 2. Jika kas pernah masuk, kurangi kembali saldo kas
            if ((float)$order['total_dibayar'] > 0 && !empty($order['akun_kas_id'])) {
                $akunKas = Database::fetchOne("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $order['akun_kas_id']]);
                $saldoLama = (float)($akunKas['saldo_saat_ini'] ?? 0);
                $saldoBaru = $saldoLama - (float)$order['total_dibayar'];

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
                "Pembatalan Faktur Pesanan Pelanggan #{$order['nomor_nota']} dan pengembalian stok ke gudang",
                'pesanan',
                $id
            );

            $pdo->commit();

            $this->flashSuccess("Faktur <strong>{$order['nomor_nota']}</strong> berhasil dibatalkan dan stok produk telah dikembalikan ke gudang!");
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
                       COALESCE(k_sj.nama_karyawan, k_p.nama_karyawan) as nama_sales,
                       COALESCE(k_sj.nomor_polisi_kendaraan, k_p.nomor_polisi_kendaraan) as nopol_driver,
                       sj.id as surat_jalan_id, sj.nomor_surat_jalan, sj.status_surat_jalan,
                       w.nama_wilayah,
                       (SELECT COUNT(*) FROM public.item_pesanan ip WHERE ip.pesanan_id = p.id) as total_sku,
                       (SELECT COALESCE(SUM(kuantitas_satuan_dasar), 0) FROM public.item_pesanan ip WHERE ip.pesanan_id = p.id) as total_pcs
                FROM public.pesanan p
                JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
                LEFT JOIN public.surat_jalan sj ON sj.pesanan_id = p.id
                LEFT JOIN public.karyawan k_p ON p.sales_driver_id = k_p.id
                LEFT JOIN public.karyawan k_sj ON sj.sales_driver_id = k_sj.id
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
                $placeholders = implode("','", array_map('addslashes', $orderIds));
                $rawItems = Database::fetchAll("
                    SELECT ip.pesanan_id, ip.item_id, ip.kuantitas_satuan_dasar, ip.harga_satuan_deal, ip.diskon_item_nominal, ip.subtotal,
                           it.nama_item, it.kode_sku, it.stok_fisik_saat_ini, it.satuan_dasar
                    FROM public.item_pesanan ip
                    JOIN public.item it ON ip.item_id = it.id
                    WHERE ip.pesanan_id IN ('{$placeholders}')
                    ORDER BY it.nama_item ASC
                ");
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
            $drivers = Database::fetchAll("SELECT id, nama_karyawan, nomor_polisi_kendaraan, posisi FROM public.karyawan WHERE posisi IN ('sales', 'driver', 'sales_driver') AND status_aktif = TRUE ORDER BY (posisi = 'driver') DESC, nama_karyawan ASC");

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
                LEFT JOIN public.karyawan k_p ON p.sales_driver_id = k_p.id
                LEFT JOIN public.karyawan k_sj ON sj.sales_driver_id = k_sj.id
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
                    throw new \Exception("Masa tenggang kirim ulang (7 hari) telah habis. Pesanan ini sudah kedaluwarsa.");
                }
            }

            // Arsipkan surat jalan lama menjadi status gagal_kirim
            $stmtArchive = $pdo->prepare("UPDATE public.surat_jalan SET status_surat_jalan = 'gagal_kirim', diubah_pada = NOW() WHERE pesanan_id = :id AND status_surat_jalan != 'gagal_kirim'");
            $stmtArchive->execute(['id' => $orderId]);

            // Kembalikan status pesanan ke siap_dikirim (Surat Jalan baru dapat diterbitkan di menu Deliveries)
            $stmtUpdateOrder = $pdo->prepare("
                UPDATE public.pesanan 
                SET status_pemrosesan = 'siap_dikirim',
                    waktu_gagal_kirim = NULL,
                    diubah_pada = NOW()
                WHERE id = :id
            ");
            $stmtUpdateOrder->execute(['id' => $orderId]);

            $pdo->commit();

            $this->flashSuccess("Pesanan #{$order['nomor_nota']} ({$order['nama_toko']}) berhasil dijadwalkan ulang! Status kini 'Siap Dikirim' dan siap diterbitkan Surat Jalan baru di menu Pengiriman.");
            $this->redirect('/customer-orders');

        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menjadwalkan kirim ulang: ' . $e->getMessage());
            $this->redirect('/customer-orders');
        }
    }
}

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
        try {
            $startDate = $this->input('start_date', date('Y-m-01'));
            $endDate = $this->input('end_date', date('Y-m-d'));
            $pelangganId = $this->input('pelanggan_id');
            $salesDriverId = $this->input('sales_driver_id');
            $statusBayar = $this->input('status_pembayaran');
            $q = trim((string)$this->input('q'));

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
            $countTotal = count($orders);

            foreach ($orders as $o) {
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
        try {
            // 1. Ambil Master Pelanggan Toko (Exclude Pelanggan Kasir Ritel UMUM/CASH)
            $customers = Database::fetchAll("
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
                ORDER BY p.nama_toko ASC
            ");

            // 2. Ambil Master Sales-Driver Lengkap dengan Plat Nomor
            $drivers = Database::fetchAll("
                SELECT id, nik, nama_karyawan, nomor_telepon, nomor_polisi_kendaraan
                FROM public.karyawan
                WHERE posisi = 'sales_driver' AND status_aktif = TRUE
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
            $pdo = \App\Core\Database::getConnection();
            
            // Cek apakah pelanggan ini adalah konsinyasi
            $stmtPelanggan = $pdo->prepare("SELECT is_konsinyasi, sales_driver_id, rute_wilayah_id FROM public.pelanggan WHERE id = :id");
            $stmtPelanggan->execute(['id' => $pelangganId]);
            $pelangganInfo = $stmtPelanggan->fetch(\PDO::FETCH_ASSOC);
            
            $isKonsinyasi = false;
            $salesDriverId = null;
            $ruteWilayahId = null;
            
            if ($pelangganInfo) {
                $isKonsinyasi = (bool)$pelangganInfo['is_konsinyasi'];
                $salesDriverId = $pelangganInfo['sales_driver_id']; // PRD: otomatis assign ke sales tetap
                $ruteWilayahId = $pelangganInfo['rute_wilayah_id'];
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
                    $hppData = \App\Core\Database::fetchOne("SELECT harga_pokok_pembelian FROM public.item WHERE id = :id", ['id' => $it['item_id']]);
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

            // 3. Insert Header Pesanan
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
                    :status_bayar, 'siap_kirim', :catatan, :adalah_tagihan,
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
                'catatan' => $catatan ?: 'Pesanan Toko Mitra',
                'adalah_tagihan' => $adalahTagihan ? 'true' : 'false'
            ]);

            $orderId = $stmt->fetchColumn();

            // 4. Insert Detail Items & Potong Stok Real-Time
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
                    :item_id, 'penjualan_keluar', :qty,
                    :stok_sebelum, :stok_sesudah, 'pesanan', :ref_id,
                    :ket, :user_id, NOW()
                )
            ");

            $userId = \App\Core\Auth::id() ?: null;

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

                // Hanya potong stok sekarang JIKA INI PENJUALAN REGULER.
                // Jika konsinyasi, stok dipotong NANTI oleh trigger pas Surat Jalan sampai (selesai_diterima).
                if (!$isKonsinyasi) {
                    $itemData = \App\Core\Database::fetchOne("SELECT stok_fisik_saat_ini, nama_item FROM public.item WHERE id = :id", ['id' => $itemId]);
                    $stokSebelum = $itemData ? (int)$itemData['stok_fisik_saat_ini'] : 0;

                    if ($stokSebelum < $qtyPcs) {
                        $namaItem = $itemData['nama_item'] ?? 'Produk';
                        throw new \Exception("Stok {$namaItem} tidak mencukupi. Tersedia: {$stokSebelum}, Diminta: {$qtyPcs}");
                    }

                    $stokSesudah = $stokSebelum - $qtyPcs;

                    $stmtStok->execute(['qty' => $qtyPcs, 'item_id' => $itemId]);

                    $stmtRiwayatStok->execute([
                        'item_id' => $itemId,
                        'qty' => $qtyPcs,
                        'stok_sebelum' => $stokSebelum,
                        'stok_sesudah' => $stokSesudah,
                        'ref_id' => $orderId,
                        'ket' => "Penjualan Toko Mitra #{$nomorNota}",
                        'user_id' => $userId,
                    ]);
                }
            }

            // 5. Update Piutang & Arus Kas (HANYA JIKA REGULER)
            if (!$isKonsinyasi) {
                if ($sisaTagihan > 0) {
                    $stmtUpdatePiutang = $pdo->prepare("
                        UPDATE public.pelanggan
                        SET total_piutang_berjalan = COALESCE(total_piutang_berjalan, 0) + :sisa,
                            diubah_pada = NOW()
                        WHERE id = :pelanggan_id
                    ");
                    $stmtUpdatePiutang->execute([
                        'sisa' => $sisaTagihan,
                        'pelanggan_id' => $pelangganId
                    ]);
                }

                if ($totalDibayar > 0 && !empty($akunKasId)) {
                    $keteranganKas = ($statusBayar === 'lunas')
                        ? "Penerimaan Tunai Lunas Pesanan Toko #{$nomorNota}"
                        : "Penerimaan DP/Sebagian Pesanan Toko #{$nomorNota}";

                    $akunKas = \App\Core\Database::fetchOne("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $akunKasId]);
                    $saldoLama = (float)($akunKas['saldo_saat_ini'] ?? 0);
                    $saldoBaru = $saldoLama + $totalDibayar;

                    $stmtUpdateKas = $pdo->prepare("
                        UPDATE public.akun_kas
                        SET saldo_saat_ini = :saldo,
                            diubah_pada = NOW()
                        WHERE id = :akun_kas
                    ");
                    $stmtUpdateKas->execute([
                        'saldo' => $saldoBaru,
                        'akun_kas' => $akunKasId,
                    ]);

                    $stmtKas = $pdo->prepare("
                        INSERT INTO public.arus_kas (
                            akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                            keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                        ) VALUES (
                            :akun_kas, :tgl, 'masuk', 'penjualan', :nominal,
                            :ket, 'pesanan', :ref_id, :saldo_berjalan, :user_id, NOW()
                        )
                    ");

                    $stmtKas->execute([
                        'akun_kas' => $akunKasId,
                        'tgl' => $tanggalPesanan,
                        'nominal' => $totalDibayar,
                        'ket' => $keteranganKas,
                        'ref_id' => $orderId,
                        'saldo_berjalan' => $saldoBaru,
                        'user_id' => $userId,
                    ]);
                }
            }

            // 6. Otomatis terbitkan Surat Jalan Pengiriman Toko
            $datePrefix = date('Ymd');
            $stmtLatestSj = $pdo->prepare("SELECT nomor_surat_jalan FROM public.surat_jalan WHERE nomor_surat_jalan LIKE :pattern ORDER BY nomor_surat_jalan DESC LIMIT 1");
            $stmtLatestSj->execute(['pattern' => "SJ-{$datePrefix}-%"]);
            $latestSj = $stmtLatestSj->fetchColumn();
            $sjSeq = 1;
            if ($latestSj) {
                $sjParts = explode('-', (string)$latestSj);
                if (isset($sjParts[2])) {
                    $sjSeq = ((int)$sjParts[2]) + 1;
                }
            }
            $nomorSj = sprintf("SJ-%s-%03d", $datePrefix, $sjSeq);

            $stmtSj = $pdo->prepare("
                INSERT INTO public.surat_jalan (
                    nomor_surat_jalan, pesanan_id, sales_driver_id, rute_wilayah_id,
                    status_surat_jalan, disetujui_oleh, dibuat_pada
                ) VALUES (
                    :no_sj, :pesanan_id, :driver_id, :wilayah_id,
                    :status_sj, :user_id, NOW()
                )
            ");
            $stmtSj->execute([
                'no_sj' => $nomorSj,
                'pesanan_id' => $orderId,
                'driver_id' => $salesDriverId, // null (reguler) atau sesuai assigned (konsinyasi)
                'wilayah_id' => $ruteWilayahId,
                'status_sj' => $statusSuratJalanAwal,
                'user_id' => $userId,
            ]);

            $pdo->commit();

            if ($printDirect) {
                $this->redirect("/customer-orders/invoice/{$orderId}");
                return;
            }

            $this->flashSuccess("Pesanan berhasil disimpan. Surat Jalan telah digenerate (Status: {$statusSuratJalanAwal}).");
            $this->redirect('/customer-orders');
        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError('Gagal menyimpan pesanan: ' . $e->getMessage());
            $this->redirect('/customer-orders/create');
        }
    }
    public function invoice(): void
    {
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
                'autoPrint' => (bool)$this->input('autoprint', false),
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    /**
     * Catat Pelunasan Piutang Pesanan Toko
     */
    public function pay(): void
    {
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
}

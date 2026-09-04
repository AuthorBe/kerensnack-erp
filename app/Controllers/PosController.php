<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Controllers/PosController.php
 * Pengendali Layar Kasir Cepat POS, Scan Barcode Multi-Rasa & Checkout Nota.
 */

class PosController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission('pos.pos');
    }

    /**
     * Tampilkan Layar Kasir POS
     */
    public function index(): void
    {
        try {
            // 1. Ambil pelanggan khusus UMUM / CASH (Konsumen Ritel Walk-in)
            $customers = Database::fetchAll("
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.is_konsinyasi, 
                       gp.nama_grup as grup_nama, gp.default_level_harga
                FROM public.pelanggan p
                JOIN public.grup_pelanggan gp ON p.grup_pelanggan_id = gp.id
                WHERE p.status_aktif = TRUE
                  AND (p.kode_pelanggan = 'CUST-001' OR LOWER(p.nama_toko) LIKE '%umum%' OR LOWER(p.nama_toko) LIKE '%cash%')
                ORDER BY p.kode_pelanggan ASC
            ");

            if (empty($customers)) {
                $customers = Database::fetchAll("
                    SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, false as is_konsinyasi, 
                           'Grup Ritel Standar (Level 1)' as grup_nama, 1 as default_level_harga 
                    FROM public.pelanggan p 
                    WHERE p.kode_pelanggan = 'CUST-001'
                ");
            }

            // 2. Ambil master grup produk & item SKU lengkap dengan Harga Retail Level 1
            $groups = Database::fetchAll("
                SELECT id, kode_grup, nama_grup, barcode_universal, satuan_dasar, satuan_distribusi, konversi_bal_ke_pcs
                FROM public.grup_produk
                WHERE status_aktif = TRUE
                ORDER BY kode_grup ASC
            ");

            $items = Database::fetchAll("
                SELECT i.id, i.grup_id, i.kode_sku, i.barcode, i.nama_item, i.varian_rasa,
                       i.satuan_dasar, i.satuan_distribusi, i.stok_fisik_saat_ini, i.harga_pokok_pembelian,
                       gp.nama_grup, gp.kode_grup,
                       COALESCE(gphl.harga_jual_pcs, 15000) AS harga_jual_satuan
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                LEFT JOIN public.grup_produk_harga_level gphl ON gphl.grup_produk_id = i.grup_id AND gphl.level_harga = 1
                WHERE i.status_aktif = TRUE AND i.tipe_item = 'barang_jadi'
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ");

            // 3. Ambil pemetaan item khusus pelanggan
            $rawCustomerItems = Database::fetchAll("
                SELECT pelanggan_id, item_id 
                FROM public.pelanggan_item
            ");
            $customerItemsMap = [];
            foreach ($rawCustomerItems as $ci) {
                $customerItemsMap[$ci['pelanggan_id']][] = $ci['item_id'];
            }

            // 4. Ambil akun kas aktif
            $cashAccounts = Database::fetchAll("
                SELECT id, nama_akun, saldo_saat_ini 
                FROM public.akun_kas 
                WHERE status_aktif = TRUE
            ");

            $this->view('pos.index', [
                'pageTitle' => 'Kasir POS',
                'pageSubtitle' => 'Layar Transaksi Penjualan Ritel / Umum',
                'customers' => $customers,
                'groups' => $groups,
                'items' => $items,
                'customerItemsMap' => $customerItemsMap,
                'cashAccounts' => $cashAccounts,
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    /**
     * API: Hitung harga dinamis dari PostgreSQL RPC
     */
    public function calculatePrice(): void
    {
        $itemId = $this->input('item_id');
        $customerId = $this->input('customer_id');

        if (empty($itemId) || empty($customerId)) {
            $this->json(['success' => false, 'message' => 'Parameter tidak lengkap'], 400);
        }

        try {
            $row = Database::fetchOne("
                SELECT public.fn_hitung_harga_jual_item(:item_id, :cust_id) AS json_res
            ", [
                'item_id' => $itemId,
                'cust_id' => $customerId
            ]);

            $data = json_decode($row['json_res'] ?? '{}', true);
            $this->json(['success' => true, 'data' => $data]);

        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Pencarian Barcode Kemasan Universal
     */
    public function searchBarcode(): void
    {
        $barcode = $this->input('barcode');

        if (empty($barcode)) {
            $this->json(['success' => false, 'message' => 'Barcode wajib diisi'], 400);
        }

        try {
            $row = Database::fetchOne("
                SELECT public.fn_cari_item_by_barcode(:barcode) AS json_res
            ", ['barcode' => $barcode]);

            $data = json_decode($row['json_res'] ?? '{}', true);
            $this->json(['success' => true, 'data' => $data]);

        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Simpan Transaksi Penjualan (Checkout)
     */
    public function checkout(): void
    {
        Auth::requirePermission('pos.pos');

        $payload = json_decode(file_get_contents('php://input'), true);

        if (empty($payload) || empty($payload['customer_id']) || empty($payload['cart']) || !is_array($payload['cart'])) {
            $this->json(['success' => false, 'message' => 'Keranjang belanja kosong'], 400);
            return;
        }

        $rawCart = $payload['cart'];
        $validCart = [];
        $totalNetto = 0.0;
        $totalQty = 0;

        foreach ($rawCart as $c) {
            $itemId = $c['item_id'] ?? null;
            $qtyPcs = (int)($c['qty_pcs'] ?? 0);
            $qtyBal = (int)($c['qty_bal'] ?? 0);
            $subtotal = (float)($c['subtotal'] ?? 0);

            if (!empty($itemId) && ($qtyPcs > 0 || $qtyBal > 0)) {
                $validCart[] = $c;
                $totalNetto += $subtotal;
                $totalQty += ($qtyPcs + $qtyBal);
            }
        }

        if (empty($validCart) || $totalQty <= 0 || $totalNetto <= 0) {
            $this->json(['success' => false, 'message' => 'Keranjang belanja kosong atau seluruh kuantitas bernilai 0.'], 400);
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $nomorNota = 'INV-' . date('Ymd-His') . '-' . strtoupper(substr(uniqid(), -4));
            $customerId = $payload['customer_id'];
            $paymentType = ($payload['payment_type'] === 'qris') ? 'qris' : 'cash';
            $catatan = $payload['notes'] ?? 'Transaksi Kasir POS';
            $cart = $validCart;

            $totalBruto = $totalNetto; // Dihitung dari akumulasi netto item
            $totalDiskon = 0.0;

            // Cari Akun Kas Tujuan berdasarkan Tipe Pembayaran (Cash vs QRIS)
            $requestedKasId = $payload['cash_account_id'] ?? $payload['akun_kas_id'] ?? null;
            $akunKas = null;

            if (!empty($requestedKasId)) {
                $akunKas = Database::fetchOne("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE id = :id AND status_aktif = TRUE", ['id' => $requestedKasId]);
            }

            if (!$akunKas) {
                if ($paymentType === 'qris') {
                    // Masuk ke Kantong Kas QRIS
                    $akunKas = Database::fetchOne("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE status_aktif = TRUE AND LOWER(nama_akun) LIKE '%qris%' ORDER BY dibuat_pada ASC LIMIT 1");
                } else {
                    // Masuk ke Kasir Utama Toko (Tunai)
                    $akunKas = Database::fetchOne("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE status_aktif = TRUE AND (is_default_pos = TRUE OR LOWER(nama_akun) LIKE '%kasir%' OR LOWER(nama_akun) LIKE '%tunai%') ORDER BY is_default_pos DESC, dibuat_pada ASC LIMIT 1");
                }

                if (!$akunKas) {
                    $akunKas = Database::fetchOne("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE status_aktif = TRUE ORDER BY is_default_pos DESC, dibuat_pada ASC LIMIT 1");
                }
            }

            $akunKasId = $akunKas['id'] ?? null;

            // 1. Ambil data pelanggan & toko
            $customer = Database::fetchOne("
                SELECT p.nama_toko, p.nama_pemilik, gp.nama_grup 
                FROM public.pelanggan p
                LEFT JOIN public.grup_pelanggan gp ON p.grup_pelanggan_id = gp.id
                WHERE p.id = :id
            ", ['id' => $customerId]);

            $storeSettings = [];
            $settingsRows = Database::fetchAll("SELECT kunci, nilai FROM public.pengaturan_sistem");
            foreach ($settingsRows as $sr) {
                $storeSettings[$sr['kunci']] = $sr['nilai'];
            }

            // 2. Insert ke tabel pesanan (Langsung Lunas dengan Akun Kas Tercatat)
            $stmtPesanan = $pdo->prepare("
                INSERT INTO public.pesanan (
                    nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_diskon, total_netto,
                    tipe_pembayaran, tanggal_jatuh_tempo, status_pembayaran, status_pemrosesan, catatan, dibuat_oleh,
                    akun_kas_id, total_dibayar, sisa_tagihan
                ) VALUES (
                    :nomor_nota, :pelanggan_id, CURRENT_DATE, :total_bruto, :total_diskon, :total_netto,
                    :tipe_pembayaran, NULL, 'lunas', 'selesai', :catatan, :dibuat_oleh,
                    :akun_kas_id, :total_dibayar, 0
                ) RETURNING id
            ");

            $userId = Auth::id() ?: null;
            $stmtPesanan->execute([
                'nomor_nota' => $nomorNota,
                'pelanggan_id' => $customerId,
                'total_bruto' => $totalBruto,
                'total_diskon' => $totalDiskon,
                'total_netto' => $totalNetto,
                'tipe_pembayaran' => $paymentType,
                'catatan' => $catatan,
                'dibuat_oleh' => $userId,
                'akun_kas_id' => $akunKasId,
                'total_dibayar' => $totalNetto
            ]);

            $pesananId = $stmtPesanan->fetchColumn();

            // 3. Insert ke item_pesanan + POTONG STOK FISIK & CATAT RIWAYAT MUTASI STOK
            $stmtItem = $pdo->prepare("
                INSERT INTO public.item_pesanan (
                    pesanan_id, item_id, kuantitas_satuan_dasar, kuantitas_satuan_distribusi,
                    harga_satuan_deal, diskon_item_persen, diskon_item_nominal, is_bonus, subtotal
                ) VALUES (
                    :pesanan_id, :item_id, :qty_pcs, :qty_bal, :harga, :disc_persen, :disc_nom, FALSE, :subtotal
                )
            ");

            $stmtUpdateStock = $pdo->prepare("
                UPDATE public.item 
                SET stok_fisik_saat_ini = :stok_baru, diubah_pada = NOW() 
                WHERE id = :item_id
            ");

            $stmtRiwayatStok = $pdo->prepare("
                INSERT INTO public.riwayat_stok (
                    item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                    referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
                ) VALUES (
                    :item_id, 'penjualan_keluar', :perubahan, :sebelum, :sesudah,
                    'pesanan', :pesanan_id, :keterangan, :dibuat_oleh, NOW()
                )
            ");

            $receiptItems = [];

            foreach ($cart as $c) {
                $itemId = $c['item_id'];
                $qtyPcs = (int)($c['qty_pcs'] ?? 0);
                $qtyBal = (int)($c['qty_bal'] ?? 0);
                $hargaDeal = (float)($c['price'] ?? 0);
                $discPersen = (float)($c['discount_percent'] ?? 0);
                $discNom = (float)($c['discount_nominal'] ?? 0);
                $itemSubtotal = (float)($c['subtotal'] ?? 0);

                // Ambil data item & rasio konversi bal ke pcs
                $itemData = Database::fetchOne("
                    SELECT i.id, i.nama_item, i.kode_sku, i.stok_fisik_saat_ini, i.satuan_dasar, 
                           COALESCE(gp.konversi_bal_ke_pcs, 20) as konversi_bal
                    FROM public.item i
                    LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                    WHERE i.id = :id FOR UPDATE OF i
                ", ['id' => $itemId]);

                $konversi = (int)($itemData['konversi_bal'] ?? 20);
                $totalPcsKeluar = $qtyPcs + ($qtyBal * $konversi);
                $stokSebelum = (float)($itemData['stok_fisik_saat_ini'] ?? 0);

                // Pengaman Anti-Minus: Validasi ketersediaan stok fisik
                if ($stokSebelum < $totalPcsKeluar) {
                    $namaItem = $c['nama_item'] ?? ($itemData['nama_item'] ?? 'Produk');
                    throw new \Exception("Stok produk '{$namaItem}' tidak mencukupi! (Sisa stok fisik: {$stokSebelum} pcs, diminta: {$totalPcsKeluar} pcs). Transaksi dibatalkan untuk mencegah stok minus.");
                }

                $stokSesudah = $stokSebelum - $totalPcsKeluar;

                // Simpan item pesanan
                $stmtItem->execute([
                    'pesanan_id' => $pesananId,
                    'item_id' => $itemId,
                    'qty_pcs' => $qtyPcs,
                    'qty_bal' => $qtyBal,
                    'harga' => $hargaDeal,
                    'disc_persen' => $discPersen,
                    'disc_nom' => $discNom,
                    'subtotal' => $itemSubtotal
                ]);

                // Potong stok fisik produk
                $stmtUpdateStock->execute([
                    'stok_baru' => $stokSesudah,
                    'item_id' => $itemId
                ]);

                // Catat ke buku besar riwayat mutasi stok
                $stmtRiwayatStok->execute([
                    'item_id' => $itemId,
                    'perubahan' => -$totalPcsKeluar,
                    'sebelum' => $stokSebelum,
                    'sesudah' => $stokSesudah,
                    'pesanan_id' => $pesananId,
                    'keterangan' => "Penjualan kasir POS: {$nomorNota}",
                    'dibuat_oleh' => $userId
                ]);

                $receiptItems[] = [
                    'item_id' => $itemId,
                    'nama_item' => $c['nama_item'] ?? ($itemData['nama_item'] ?? 'Item'),
                    'kode_sku' => $c['kode_sku'] ?? ($itemData['kode_sku'] ?? ''),
                    'qty_pcs' => $qtyPcs,
                    'qty_bal' => $qtyBal,
                    'harga' => $hargaDeal,
                    'subtotal' => $itemSubtotal
                ];
            }

            // 4. Catat Kas Masuk & Update Saldo Kas Toko (Cash -> Kasir Utama Toko, QRIS -> Kantong Kas QRIS)
            $saldoKasAkhir = 0;
            if ($akunKas) {
                $saldoLama = (float)($akunKas['saldo_saat_ini'] ?? 0);
                $saldoKasAkhir = $saldoLama + $totalNetto;

                $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo, diubah_pada = NOW() WHERE id = :id")
                    ->execute(['saldo' => $saldoKasAkhir, 'id' => $akunKasId]);

                $ketTipe = ($paymentType === 'qris') ? 'QRIS' : 'Tunai';
                $stmtKas = $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan,
                        referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :akun_id, CURRENT_DATE, 'masuk', 'penjualan', :nominal, :ket,
                        'pesanan', :pesanan_id, :saldo_berjalan, :dibuat_oleh, NOW()
                    )
                ");
                $stmtKas->execute([
                    'akun_id' => $akunKasId,
                    'nominal' => $totalNetto,
                    'ket' => "Pelunasan {$ketTipe} Kasir POS: {$nomorNota}",
                    'pesanan_id' => $pesananId,
                    'saldo_berjalan' => $saldoKasAkhir,
                    'dibuat_oleh' => $userId
                ]);
            }

            // 5. Catat Audit Trail
            $stmtLog = $pdo->prepare("
                INSERT INTO public.log_aktivitas (
                    nama_aktor, peran_aktor, sumber_aksi, kategori_aktivitas, jenis_aksi,
                    tabel_terdampak, id_referensi, deskripsi_aktivitas, data_sesudah
                ) VALUES (
                    :nama, :peran, 'web_app', 'penjualan', 'INSERT',
                    'pesanan', :pesanan_id, :desc, :data_json
                )
            ");

            $namaKas = $akunKas['nama_akun'] ?? 'Kasir';
            $stmtLog->execute([
                'nama' => Auth::name(),
                'peran' => Auth::role(),
                'pesanan_id' => $pesananId,
                'desc' => "Transaksi POS Kasir ({$paymentType} -> {$namaKas}): {$nomorNota} total Rp " . number_format($totalNetto, 0, ',', '.'),
                'data_json' => json_encode(['nomor_nota' => $nomorNota, 'total' => $totalNetto, 'tipe_pembayaran' => $paymentType, 'akun_kas' => $namaKas, 'items_count' => count($cart)])
            ]);

            $pdo->commit();

            $this->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan!',
                'data' => [
                    'order_id' => $pesananId,
                    'nomor_nota' => $nomorNota,
                    'tanggal' => date('d/m/Y H:i'),
                    'customer_name' => $customer['nama_toko'] ?? 'Umum',
                    'customer_group' => $customer['nama_grup'] ?? 'Ritel',
                    'cashier_name' => Auth::name(),
                    'payment_type' => $paymentType,
                    'akun_kas_nama' => $namaKas,
                    'total_bruto' => $totalBruto,
                    'total_diskon' => $totalDiskon,
                    'total_netto' => $totalNetto,
                    'notes' => $catatan,
                    'items' => $receiptItems,
                    'store' => [
                        'nama' => $storeSettings['nama_toko'] ?? 'KEREN SNACK',
                        'alamat' => 'Sentra Distribusi & Manufaktur Snack',
                        'kontak' => 'WhatsApp: 0812-xxxx-xxxx'
                    ]
                ]
            ]);

        } catch (Throwable $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            $this->json(['success' => false, 'message' => 'Gagal simpan transaksi: ' . $e->getMessage()], 500);
        }
    }
}

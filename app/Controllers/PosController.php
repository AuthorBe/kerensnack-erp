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
        Auth::requireLogin();
    }

    /**
     * Tampilkan Layar Kasir POS
     */
    public function index(): void
    {
        try {
            // 1. Ambil seluruh master toko pelanggan
            $customers = Database::fetchAll("
                SELECT p.id, p.kode_pelanggan, p.nama_toko, p.nama_pemilik, p.is_konsinyasi, 
                       gp.nama_grup as grup_nama, gp.default_level_harga
                FROM public.pelanggan p
                JOIN public.grup_pelanggan gp ON p.grup_pelanggan_id = gp.id
                WHERE p.status_aktif = TRUE
                ORDER BY p.nama_toko ASC
            ");

            // 2. Ambil master grup produk & item SKU
            $groups = Database::fetchAll("
                SELECT id, kode_grup, nama_grup, barcode_universal, satuan_dasar, satuan_distribusi, konversi_bal_ke_pcs
                FROM public.grup_produk
                WHERE status_aktif = TRUE
                ORDER BY kode_grup ASC
            ");

            $items = Database::fetchAll("
                SELECT i.id, i.grup_id, i.kode_sku, i.barcode, i.nama_item, i.varian_rasa,
                       i.satuan_dasar, i.satuan_distribusi, i.stok_fisik_saat_ini, i.harga_pokok_pembelian,
                       gp.nama_grup, gp.kode_grup
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE i.status_aktif = TRUE AND i.tipe_item = 'barang_jadi'
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ");

            // 3. Ambil akun kas aktif
            $cashAccounts = Database::fetchAll("
                SELECT id, nama_akun, saldo_saat_ini 
                FROM public.akun_kas 
                WHERE status_aktif = TRUE
            ");

            $this->view('pos.index', [
                'pageTitle' => 'Kasir POS (Point of Sale)',
                'pageSubtitle' => 'Layar Transaksi Penjualan Cepat',
                'customers' => $customers,
                'groups' => $groups,
                'items' => $items,
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
        $payload = json_decode(file_get_contents('php://input'), true);

        if (empty($payload) || empty($payload['customer_id']) || empty($payload['cart'])) {
            $this->json(['success' => false, 'message' => 'Keranjang belanja kosong'], 400);
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $nomorNota = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            $customerId = $payload['customer_id'];
            $paymentType = $payload['payment_type'] ?? 'cash';
            $catatan = $payload['notes'] ?? 'Transaksi Kasir POS';
            $cart = $payload['cart'];

            $totalBruto = 0.0;
            $totalDiskon = 0.0;
            $totalNetto = 0.0;

            foreach ($cart as $c) {
                $subtotal = (float)($c['subtotal'] ?? 0);
                $totalNetto += $subtotal;
            }
            $totalBruto = $totalNetto; // Dihitung dari akumulasi netto item

            $statusPembayaran = ($paymentType === 'cash') ? 'lunas' : 'tempo';
            $jatuhTempo = ($paymentType === 'tempo_7_hari') ? date('Y-m-d', strtotime('+7 days')) :
                          (($paymentType === 'tempo_14_hari') ? date('Y-m-d', strtotime('+14 days')) : null);

            // 1. Insert ke tabel pesanan
            $stmtPesanan = $pdo->prepare("
                INSERT INTO public.pesanan (
                    nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_diskon, total_netto,
                    tipe_pembayaran, tanggal_jatuh_tempo, status_pembayaran, status_pemrosesan, catatan
                ) VALUES (
                    :nomor_nota, :pelanggan_id, CURRENT_DATE, :total_bruto, :total_diskon, :total_netto,
                    :tipe_pembayaran, :jatuh_tempo, :status_pembayaran, 'selesai', :catatan
                ) RETURNING id
            ");

            $stmtPesanan->execute([
                'nomor_nota' => $nomorNota,
                'pelanggan_id' => $customerId,
                'total_bruto' => $totalBruto,
                'total_diskon' => $totalDiskon,
                'total_netto' => $totalNetto,
                'tipe_pembayaran' => $paymentType,
                'jatuh_tempo' => $jatuhTempo,
                'status_pembayaran' => $statusPembayaran,
                'catatan' => $catatan
            ]);

            $pesananId = $stmtPesanan->fetchColumn();

            // 2. Insert ke item_pesanan
            $stmtItem = $pdo->prepare("
                INSERT INTO public.item_pesanan (
                    pesanan_id, item_id, kuantitas_satuan_dasar, kuantitas_satuan_distribusi,
                    harga_satuan_deal, diskon_item_persen, diskon_item_nominal, is_bonus, subtotal
                ) VALUES (
                    :pesanan_id, :item_id, :qty_pcs, :qty_bal, :harga, :disc_persen, :disc_nom, FALSE, :subtotal
                )
            ");

            foreach ($cart as $c) {
                $stmtItem->execute([
                    'pesanan_id' => $pesananId,
                    'item_id' => $c['item_id'],
                    'qty_pcs' => (int)($c['qty_pcs'] ?? 0),
                    'qty_bal' => (int)($c['qty_bal'] ?? 0),
                    'harga' => (float)($c['price'] ?? 0),
                    'disc_persen' => (float)($c['discount_percent'] ?? 0),
                    'disc_nom' => (float)($c['discount_nominal'] ?? 0),
                    'subtotal' => (float)($c['subtotal'] ?? 0)
                ]);
            }

            // 3. Catat Kas Masuk jika Cash
            if ($paymentType === 'cash') {
                $akunKasId = Database::fetchOne("SELECT id FROM public.akun_kas LIMIT 1")['id'] ?? null;
                if ($akunKasId) {
                    $stmtKas = $pdo->prepare("
                        INSERT INTO public.arus_kas (
                            akun_kas_id, jenis_kas, kategori, nominal, keterangan,
                            referensi_tabel, referensi_id, saldo_berjalan
                        ) VALUES (
                            :akun_id, 'masuk', 'penjualan', :nominal, :ket,
                            'pesanan', :pesanan_id, 0
                        )
                    ");
                    $stmtKas->execute([
                        'akun_id' => $akunKasId,
                        'nominal' => $totalNetto,
                        'ket' => "Pelunasan Nota POS Kasir: {$nomorNota}",
                        'pesanan_id' => $pesananId
                    ]);
                }
            }

            // 4. Catat Audit Trail
            $stmtLog = $pdo->prepare("
                INSERT INTO public.log_aktivitas (
                    nama_aktor, peran_aktor, sumber_aksi, kategori_aktivitas, jenis_aksi,
                    tabel_terdampak, id_referensi, deskripsi_aktivitas, data_sesudah
                ) VALUES (
                    :nama, :peran, 'web_app', 'penjualan', 'INSERT',
                    'pesanan', :pesanan_id, :desc, :data_json
                )
            ");

            $stmtLog->execute([
                'nama' => Auth::name(),
                'peran' => Auth::role(),
                'pesanan_id' => $pesananId,
                'desc' => "Transaksi POS Kasir: {$nomorNota} total Rp " . number_format($totalNetto, 0, ',', '.'),
                'data_json' => json_encode(['nomor_nota' => $nomorNota, 'total' => $totalNetto, 'items_count' => count($cart)])
            ]);

            $pdo->commit();

            $this->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan!',
                'data' => [
                    'order_id' => $pesananId,
                    'nomor_nota' => $nomorNota,
                    'total_netto' => $totalNetto
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

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Controllers/PurchaseController.php
 * Pengendali Faktur Pembelian Bahan Mentah & Kemasan dari Vendor Pemasok.
 */
class PurchaseController extends Controller
{
    public function __construct()
    {
        Auth::requirePermission('purchases.view');
    }

    public function index(): void
    {
        try {
            $purchases = Database::fetchAll("
                SELECT pb.id, pb.nomor_faktur_pembelian, pb.tanggal_pembelian, pb.total_biaya,
                       pb.status_pembayaran, pb.status_penerimaan, pb.catatan, pb.dibuat_pada,
                       sup.nama_pemasok, sup.kode_pemasok,
                       p.nama_lengkap as pembuat
                FROM public.pembelian pb
                LEFT JOIN public.pemasok sup ON pb.pemasok_id = sup.id
                LEFT JOIN public.pengguna p ON pb.dibuat_oleh = p.id
                ORDER BY pb.tanggal_pembelian DESC, pb.dibuat_pada DESC
            ");

            $suppliers = Database::fetchAll("SELECT id, kode_pemasok, nama_pemasok FROM public.pemasok WHERE status_aktif = TRUE ORDER BY nama_pemasok ASC");
            $items = Database::fetchAll("
                SELECT id, kode_sku, nama_item, satuan_dasar, tipe_item, harga_pokok_pembelian, stok_fisik_saat_ini 
                FROM public.item 
                WHERE status_aktif = TRUE AND tipe_item IN ('bahan_mentah', 'bahan_kemas') 
                ORDER BY tipe_item ASC, nama_item ASC
            ");
            $cashAccounts = Database::fetchAll("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE status_aktif = TRUE");

            $this->view('purchases.index', [
                'pageTitle' => 'Pembelian & Faktur Vendor',
                'pageSubtitle' => 'Penerimaan Bahan Mentah, Bumbu & Kemasan dari Supplier',
                'purchases' => $purchases,
                'suppliers' => $suppliers,
                'items' => $items,
                'cashAccounts' => $cashAccounts
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    public function store(): void
    {
        Auth::requirePermission('purchases.create');

        $payload = json_decode(file_get_contents('php://input'), true);

        if (empty($payload) || empty($payload['pemasok_id']) || empty($payload['items']) || !is_array($payload['items'])) {
            $this->json(['success' => false, 'message' => 'Data faktur pembelian dan item tidak lengkap'], 400);
            return;
        }

        $pemasokId = $payload['pemasok_id'];
        $tanggal = $payload['tanggal_pembelian'] ?? date('Y-m-d');
        $rawItems = $payload['items'];

        // Filter valid items with item_id and qty > 0
        $validItems = [];
        $totalBiaya = 0.0;
        foreach ($rawItems as $it) {
            $itemId = $it['item_id'] ?? null;
            $qty = (float)($it['qty'] ?? 0);
            $harga = (float)($it['harga_satuan'] ?? 0);
            $subtotal = (float)($it['subtotal'] ?? ($qty * $harga));

            if (!empty($itemId) && $qty > 0) {
                $it['qty'] = $qty;
                $it['harga_satuan'] = $harga;
                $it['subtotal'] = $subtotal;
                $validItems[] = $it;
                $totalBiaya += $subtotal;
            }
        }

        if (empty($validItems) || $totalBiaya <= 0) {
            $this->json(['success' => false, 'message' => 'Faktur pembelian harus memiliki minimal 1 item dengan kuantitas > 0 dan total nominal > Rp 0.'], 400);
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $rawFaktur = trim($payload['nomor_faktur'] ?? '');
            if (empty($rawFaktur) || $rawFaktur === 'PO-') {
                $nomorFaktur = 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            } else {
                $suffix = strtoupper((string)preg_replace('/[^A-Z0-9-]/', '', (string)preg_replace('/^PO-?/i', '', $rawFaktur)));
                $suffix = substr($suffix, 0, 16);
                $nomorFaktur = 'PO-' . ($suffix ?: date('Ymd') . '-001');
            }
            $statusBayar = $payload['status_pembayaran'] ?? 'lunas';
            $akunKasId = $payload['akun_kas_id'] ?? null;
            $catatan = trim($payload['catatan'] ?? 'Penerimaan barang dari supplier');
            $items = $validItems;
            $userId = Auth::id() ?: null;

            // 1. Insert Header Pembelian
            $stmtPb = $pdo->prepare("
                INSERT INTO public.pembelian (
                    nomor_faktur_pembelian, pemasok_id, tanggal_pembelian, total_biaya,
                    status_pembayaran, status_penerimaan, catatan, dibuat_oleh, dibuat_pada
                ) VALUES (
                    :no_faktur, :pemasok, :tgl, :total,
                    :bayar, 'diterima', :catatan, :user_id, NOW()
                ) RETURNING id
            ");

            $stmtPb->execute([
                'no_faktur' => $nomorFaktur,
                'pemasok' => $pemasokId,
                'tgl' => $tanggal,
                'total' => $totalBiaya,
                'bayar' => $statusBayar,
                'catatan' => $catatan,
                'user_id' => $userId
            ]);

            $pembelianId = $stmtPb->fetchColumn();

            // 2. Insert Items & Auto Increment Stok Fisik
            $stmtItem = $pdo->prepare("
                INSERT INTO public.rincian_pembelian (
                    pembelian_id, item_id, kuantitas, satuan, harga_satuan, subtotal
                ) VALUES (
                    :pb_id, :item_id, :qty, :satuan, :harga, :subtotal
                )
            ");

            $stmtUpdateStock = $pdo->prepare("
                UPDATE public.item 
                SET stok_fisik_saat_ini = stok_fisik_saat_ini + :qty,
                    harga_pokok_pembelian = :harga_baru,
                    diubah_pada = NOW() 
                WHERE id = :item_id
            ");

            $stmtRiwayat = $pdo->prepare("
                INSERT INTO public.riwayat_stok (
                    item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                    referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
                ) VALUES (
                    :item_id, 'pembelian_masuk', :qty, :sebelum, :sesudah,
                    'pembelian', :pb_id, :ket, :user_id, NOW()
                )
            ");

            foreach ($items as $it) {
                $itemId = $it['item_id'];
                $qty = (float)($it['qty'] ?? 0);
                $harga = (float)($it['harga_satuan'] ?? 0);
                $subtotal = (float)($it['subtotal'] ?? ($qty * $harga));

                if ($qty <= 0) continue;

                $current = Database::fetchOne("SELECT stok_fisik_saat_ini, satuan_dasar, harga_pokok_pembelian FROM public.item WHERE id = :id", ['id' => $itemId]);
                $stokSebelum = (float)($current['stok_fisik_saat_ini'] ?? 0);
                $stokSesudah = $stokSebelum + $qty;
                $satuan = $current['satuan_dasar'] ?? 'pcs';
                $hppLama = (float)($current['harga_pokok_pembelian'] ?? 0);

                // Hitung Weighted Moving Average HPP
                if ($harga > 0) {
                    if ($stokSebelum > 0 && $hppLama > 0) {
                        $hppBaru = round((($stokSebelum * $hppLama) + ($qty * $harga)) / $stokSesudah, 2);
                    } else {
                        $hppBaru = $harga;
                    }
                } else {
                    $hppBaru = $hppLama;
                }

                $stmtItem->execute([
                    'pb_id' => $pembelianId,
                    'item_id' => $itemId,
                    'qty' => $qty,
                    'satuan' => $satuan,
                    'harga' => $harga,
                    'subtotal' => $subtotal
                ]);

                $stmtUpdateStock->execute([
                    'qty' => $qty,
                    'harga_baru' => $hppBaru,
                    'item_id' => $itemId
                ]);

                $stmtRiwayat->execute([
                    'item_id' => $itemId,
                    'qty' => $qty,
                    'sebelum' => $stokSebelum,
                    'sesudah' => $stokSesudah,
                    'pb_id' => $pembelianId,
                    'ket' => "Penerimaan barang vendor faktur: {$nomorFaktur}",
                    'user_id' => $userId
                ]);
            }

            // 3. Catat Kas Keluar jika Lunas
            if ($statusBayar === 'lunas' && $akunKasId) {
                $akunKas = Database::fetchOne("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $akunKasId]);
                $saldoLama = (float)($akunKas['saldo_saat_ini'] ?? 0);
                $saldoBaru = $saldoLama - $totalBiaya;

                $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo, diubah_pada = NOW() WHERE id = :id")
                    ->execute(['saldo' => $saldoBaru, 'id' => $akunKasId]);

                $pdo->prepare("
                    INSERT INTO public.arus_kas (
                        akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan,
                        referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
                    ) VALUES (
                        :akun_id, :tgl, 'keluar', 'pembelian_bahan', :nominal, :ket,
                        'pembelian', :pb_id, :saldo_berjalan, :user_id, NOW()
                    )
                ")->execute([
                    'akun_id' => $akunKasId,
                    'tgl' => $tanggal,
                    'nominal' => $totalBiaya,
                    'ket' => "Pembayaran faktur pembelian vendor: {$nomorFaktur}",
                    'pb_id' => $pembelianId,
                    'saldo_berjalan' => $saldoBaru,
                    'user_id' => $userId
                ]);
            }

            // 4. Catat Audit Trail
            $pdo->prepare("
                INSERT INTO public.log_aktivitas (
                    nama_aktor, peran_aktor, sumber_aksi, kategori_aktivitas, jenis_aksi,
                    tabel_terdampak, id_referensi, deskripsi_aktivitas, data_sesudah
                ) VALUES (
                    :nama, :peran, 'web_app', 'gudang_stok', 'INSERT',
                    'pembelian', :pb_id, :desc, :data_json
                )
            ")->execute([
                'nama' => Auth::name(),
                'peran' => Auth::role(),
                'pb_id' => $pembelianId,
                'desc' => "Faktur Pembelian Vendor: {$nomorFaktur} total Rp " . number_format($totalBiaya, 0, ',', '.'),
                'data_json' => json_encode(['nomor_faktur' => $nomorFaktur, 'total' => $totalBiaya, 'items_count' => count($items)])
            ]);

            $pdo->commit();

            $this->json([
                'success' => true,
                'message' => 'Faktur pembelian berhasil disimpan dan stok otomatis bertambah!',
                'data' => ['pembelian_id' => $pembelianId, 'nomor_faktur' => $nomorFaktur]
            ]);

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->json(['success' => false, 'message' => 'Gagal simpan pembelian: ' . $e->getMessage()], 500);
        }
    }
}

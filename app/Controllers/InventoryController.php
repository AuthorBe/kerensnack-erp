<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Controllers/InventoryController.php
 * Pengendali Katalog 137 SKU Produk, Stok Fisik & Mutasi Gudang.
 */

class InventoryController extends Controller
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    public function index(): void
    {
        try {
            $items = Database::fetchAll("
                SELECT i.id, i.kode_sku, i.barcode, i.nama_item, i.varian_rasa,
                       i.stok_fisik_saat_ini, i.stok_minimum_peringatan, i.satuan_dasar, i.satuan_distribusi,
                       i.harga_pokok_pembelian, gp.nama_grup, gp.kode_grup, gp.barcode_universal
                FROM public.item i
                LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
                WHERE i.status_aktif = TRUE
                ORDER BY gp.kode_grup ASC, i.nama_item ASC
            ");

            $recentLogs = Database::fetchAll("
                SELECT rs.id, rs.tipe_mutasi, rs.jumlah_perubahan, rs.stok_sebelum, rs.stok_sesudah,
                       rs.keterangan, rs.dibuat_pada, i.nama_item
                FROM public.riwayat_stok rs
                JOIN public.item i ON rs.item_id = i.id
                ORDER BY rs.dibuat_pada DESC
                LIMIT 15
            ");

            $this->view('inventory.index', [
                'pageTitle' => 'Katalog & Mutasi Stok (137 SKU)',
                'pageSubtitle' => 'Monitoring Stok Fisik Gudang & Riwayat Perubahan',
                'items' => $items,
                'recentLogs' => $recentLogs
            ]);

        } catch (Throwable $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }

    public function adjustStock(): void
    {
        $itemId = $this->input('item_id');
        $qty = (int)$this->input('kuantitas', 0);
        $tipe = $this->input('tipe_penyesuaian', 'opname_lebih');
        $alasan = $this->input('alasan', 'Penyesuaian stok fisik');

        if (empty($itemId) || $qty <= 0) {
            $this->flashError('Jumlah kuantitas harus lebih besar dari 0.');
            $this->redirect('/inventory');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $item = Database::fetchOne("SELECT stok_fisik_saat_ini, nama_item FROM public.item WHERE id = :id", ['id' => $itemId]);
            $stokLama = (int)($item['stok_fisik_saat_ini'] ?? 0);
            $stokBaru = in_array($tipe, ['opname_lebih', 'retur_masuk_manual']) ? ($stokLama + $qty) : max(0, $stokLama - $qty);

            // Update item
            $pdo->prepare("UPDATE public.item SET stok_fisik_saat_ini = :baru, diubah_pada = NOW() WHERE id = :id")
                ->execute(['baru' => $stokBaru, 'id' => $itemId]);

            // Insert riwayat stok
            $pdo->prepare("
                INSERT INTO public.riwayat_stok (
                    item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                    referensi_tabel, referensi_id, keterangan
                ) VALUES (
                    :item_id, :tipe, :qty, :sebelum, :sesudah,
                    'penyesuaian_stok', '00000000-0000-0000-0000-000000000000', :ket
                )
            ")->execute([
                'item_id' => $itemId,
                'tipe' => $tipe === 'opname_lebih' ? 'penyesuaian_opname_tambah' : 'penyesuaian_opname_kurang',
                'qty' => $qty,
                'sebelum' => $stokLama,
                'sesudah' => $stokBaru,
                'ket' => "Manual Opname: {$alasan}"
            ]);

            $pdo->commit();
            $this->flashSuccess("Opname fisik berhasil! Stok '{$item['nama_item']}' kini menjadi {$stokBaru} pcs.");
            $this->redirect('/inventory');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError("Gagal menyimpan opname: " . $e->getMessage());
            $this->redirect('/inventory');
        }
    }
}

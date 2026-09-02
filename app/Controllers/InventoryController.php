<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
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
        Auth::requirePermission('inventory.view_all');
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
                'pageTitle' => 'Katalog & Mutasi Stok',
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
        Auth::requirePermission('inventory.opname');

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

            // Pengaman Anti-Minus: Pengurangan tidak boleh melebihi sisa stok
            if (!in_array($tipe, ['opname_lebih', 'retur_masuk_manual'], true) && $stokLama < $qty) {
                $this->flashError("Jumlah penyesuaian minus ({$qty} pcs) melebihi stok fisik saat ini ({$stokLama} pcs). Stok tidak boleh minus.");
                $this->redirect('/inventory');
                return;
            }

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

            ActivityLog::log(
                'logistik',
                'UPDATE',
                "Penyesuaian Stok Opname '{$item['nama_item']}' dari {$stokLama} pcs menjadi {$stokBaru} pcs (Alasan: {$alasan})",
                'item',
                $itemId
            );

            $pdo->commit();
            $this->flashSuccess("Opname fisik berhasil! Stok '{$item['nama_item']}' kini menjadi {$stokBaru} pcs.");
            $this->redirect('/inventory');

        } catch (Throwable $e) {
            if (isset($pdo)) $pdo->rollBack();
            $this->flashError("Gagal menyimpan opname: " . $e->getMessage());
            $this->redirect('/inventory');
        }
    }

    /**
     * Catat Pengurangan Stok Akibat Barang Rusak, Bocor, Expired, atau Sampel (Waste)
     */
    public function recordWaste(): void
    {
        Auth::requirePermission('inventory.waste');

        $itemId = (string)$this->input('item_id');
        $qty = (int)$this->input('kuantitas', 0);
        $kategoriWaste = trim((string)$this->input('kategori_waste', 'kemasan_rusak'));
        $keterangan = trim((string)$this->input('keterangan', 'Barang Rusak / Susut Operasional'));

        if (empty($itemId) || $qty <= 0) {
            $this->flashError('Jumlah kuantitas barang rusak/waste harus lebih dari 0.');
            $this->redirect('/inventory');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $item = Database::fetchOne("SELECT stok_fisik_saat_ini, nama_item FROM public.item WHERE id = :id FOR UPDATE", ['id' => $itemId]);
            if (!$item) {
                $this->flashError('Item tidak ditemukan.');
                $this->redirect('/inventory');
                return;
            }

            $stokLama = (int)($item['stok_fisik_saat_ini'] ?? 0);
            if ($stokLama < $qty) {
                $this->flashError("Kuantitas waste ({$qty} pcs) melebihi sisa stok fisik saat ini ({$stokLama} pcs).");
                $this->redirect('/inventory');
                return;
            }

            $stokBaru = $stokLama - $qty;

            // Update item stock
            $pdo->prepare("UPDATE public.item SET stok_fisik_saat_ini = :baru, diubah_pada = NOW() WHERE id = :id")
                ->execute(['baru' => $stokBaru, 'id' => $itemId]);

            $kategoriLabels = [
                'kemasan_rusak' => 'Kemasan Rusak / Gagal Segel',
                'expired_kadaluarsa' => 'Kadaluarsa / Expired',
                'remuk_hancur' => 'Produk Remuk / Hancur',
                'sampel_promosi' => 'Sampel Uji Rasa / Promosi',
                'lainnya' => 'Lain-lain'
            ];
            $labelKategori = $kategoriLabels[$kategoriWaste] ?? $kategoriWaste;
            $catatanLengkap = "[WASTE: {$labelKategori}] {$keterangan}";

            // Insert riwayat stok
            $pdo->prepare("
                INSERT INTO public.riwayat_stok (
                    item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                    referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
                ) VALUES (
                    :item_id, 'item_keluar_waste', :qty, :sebelum, :sesudah,
                    'waste_manual', '00000000-0000-0000-0000-000000000000', :ket, :user_id, NOW()
                )
            ")->execute([
                'item_id' => $itemId,
                'qty' => $qty,
                'sebelum' => $stokLama,
                'sesudah' => $stokBaru,
                'ket' => $catatanLengkap,
                'user_id' => Auth::id() ?: null,
            ]);

            ActivityLog::log(
                'Gudang',
                'WASTE',
                "Catat waste/barang rusak: {$qty} pcs '{$item['nama_item']}' ({$labelKategori})",
                'item',
                $itemId
            );

            $pdo->commit();
            $this->flashSuccess("Pencatatan barang rusak/waste berhasil! Stok '{$item['nama_item']}' terpotong {$qty} pcs (sisa: {$stokBaru} pcs).");
            $this->redirect('/inventory');

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flashError("Gagal mencatat waste: " . $e->getMessage());
            $this->redirect('/inventory');
        }
    }
}

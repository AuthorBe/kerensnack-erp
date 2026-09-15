<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Auth;
use Database;
use PDO;

/**
 * app/Helpers/StockHelper.php
 * Helper terpusat untuk operasi kartu stok dan inventaris gudang.
 */
class StockHelper
{
    /**
     * Cek apakah status pemrosesan pesanan sudah memotong stok fisik gudang.
     */
    public static function isPhysicalStockCut(?string $statusPemrosesan): bool
    {
        return in_array($statusPemrosesan ?? '', ['siap_dikirim', 'siap_kirim', 'sedang_dikirim'], true);
    }

    /**
     * Mengembalikan kuantitas fisik item pesanan ke gudang dan mencatat mutasi riwayat_stok.
     * Wajib dipanggil di dalam blok transaksi PDO aktif untuk menjamin konsistensi ACID.
     *
     * @param PDO $pdo Koneksi PDO dengan transaksi aktif
     * @param int|string $orderId ID pesanan yang dibatalkan / gagal kirim
     * @param string $reason Keterangan audit mutasi (misal: "Pembatalan Pesanan #KRS-..." atau "Pengembalian Barang Gagal Kirim #KRS-...")
     * @param int|string|null $userId ID pengguna pencatat mutasi (default Auth::id())
     * @return int Jumlah baris item yang berhasil dikembalikan ke rak gudang
     */
    public static function revertOrderStockToWarehouse(
        PDO $pdo,
        int|string $orderId,
        string $reason,
        int|string|null $userId = null
    ): int {
        $actorId = $userId ?: (Auth::id() ?: null);

        $orderedItems = Database::fetchAll("
            SELECT item_id, kuantitas_satuan_dasar 
            FROM public.item_pesanan 
            WHERE pesanan_id = :id
        ", ['id' => $orderId]);

        if (empty($orderedItems)) {
            return 0;
        }

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

        $count = 0;
        foreach ($orderedItems as $oit) {
            $qtyPcs = (int)($oit['kuantitas_satuan_dasar'] ?? 0);
            if ($qtyPcs <= 0) {
                continue;
            }

            $itemId = $oit['item_id'];
            $itemData = Database::fetchOne("
                SELECT stok_fisik_saat_ini 
                FROM public.item 
                WHERE id = :id 
                FOR UPDATE
            ", ['id' => $itemId]);

            $stokSebelum = $itemData ? (float)$itemData['stok_fisik_saat_ini'] : 0.0;
            $stokSesudah = $stokSebelum + $qtyPcs;

            $stmtStok->execute([
                'qty' => $qtyPcs,
                'item_id' => $itemId
            ]);

            $stmtRiwayat->execute([
                'item_id' => $itemId,
                'qty' => $qtyPcs,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
                'ref_id' => $orderId,
                'ket' => $reason,
                'user_id' => $actorId
            ]);

            $count++;
        }

        return $count;
    }
}

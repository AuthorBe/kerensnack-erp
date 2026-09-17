<?php
declare(strict_types=1);

namespace App\Helpers;

use Database;
use PDO;

/**
 * app/Helpers/CashVoucher.php
 * Generator Nomor Bukti Transaksi Kas Resmi (Voucher Kas):
 * - BKM-YYMM-XXXX (Bukti Kas Masuk)
 * - BKK-YYMM-XXXX (Bukti Kas Keluar)
 * - TRF-YYMM-XXXX (Bukti Transfer Dana Antar Kas)
 */
class CashVoucher
{
    /**
     * Generate nomor bukti transaksi kas berikutnya berdasarkan jenis kas & tanggal transaksi.
     *
     * @param string $jenisKas 'masuk', 'keluar', 'transfer_masuk', 'transfer_keluar', dll.
     * @param string|null $tanggal YYYY-MM-DD
     * @param PDO|null $pdo
     * @return string
     */
    public static function generate(string $jenisKas, ?string $tanggal = null, ?PDO $pdo = null): string
    {
        $db = $pdo ?? Database::getConnection();
        $date = !empty($tanggal) ? $tanggal : date('Y-m-d');
        $yearMonth = date('ym', strtotime($date)); // Contoh: 2609

        $prefix = match($jenisKas) {
            'masuk' => 'BKM',
            'keluar' => 'BKK',
            'transfer_masuk', 'transfer_keluar', 'transfer' => 'TRF',
            default => 'BKM'
        };

        $codePattern = "{$prefix}-{$yearMonth}-%";

        // Ambil nomor urut tertinggi untuk prefix dan bulan tersebut
        $stmt = $db->prepare("
            SELECT nomor_transaksi 
            FROM public.arus_kas 
            WHERE nomor_transaksi LIKE :pattern 
            ORDER BY nomor_transaksi DESC 
            LIMIT 1
        ");
        $stmt->execute(['pattern' => $codePattern]);
        $lastCode = $stmt->fetchColumn();

        $nextNumber = 1;
        if ($lastCode) {
            $parts = explode('-', (string)$lastCode);
            $lastSeq = (int)end($parts);
            $nextNumber = $lastSeq + 1;
        }

        return sprintf("%s-%s-%04d", $prefix, $yearMonth, $nextNumber);
    }
}

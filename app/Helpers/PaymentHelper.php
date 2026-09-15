<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * app/Helpers/PaymentHelper.php
 * Helper terpusat untuk kalkulasi pelunasan, sisa tagihan piutang, dan standarisasi enum status pembayaran.
 */
class PaymentHelper
{
    /**
     * Menghitung sisa tagihan dan menentukan status pembayaran pesanan secara konsisten.
     * Nilai status pembayaran wajib mematuhi check constraint PostgreSQL:
     * 'belum_lunas', 'sebagian', 'lunas', 'tempo', 'dibatalkan'.
     *
     * @param float $totalNetto Total nilai transaksi / tagihan yang harus dibayar
     * @param float $totalDibayar Total nominal yang sudah dibayarkan
     * @return array{sisa_tagihan: float, status_pembayaran: string}
     */
    public static function calculateSettlement(float $totalNetto, float $totalDibayar): array
    {
        $netto = max(0.0, round($totalNetto, 2));
        $dibayar = max(0.0, round($totalDibayar, 2));
        $sisaTagihan = max(0.0, round($netto - $dibayar, 2));

        if ($sisaTagihan <= 0.0) {
            $status = 'lunas';
        } elseif ($dibayar > 0.0) {
            $status = 'sebagian';
        } else {
            $status = 'belum_lunas';
        }

        return [
            'sisa_tagihan' => $sisaTagihan,
            'status_pembayaran' => $status
        ];
    }
}

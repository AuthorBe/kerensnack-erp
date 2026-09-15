<?php
declare(strict_types=1);

namespace App\Helpers;

use Database;
use PDO;

/**
 * app/Helpers/DocumentNumber.php
 * Generator nomor dokumen transaksi berurutan (Sequential Document Numbering)
 * dilengkapi transaksi advisory lock PostgreSQL (Anti-Collision & Race Condition Proof).
 */
class DocumentNumber
{
    /**
     * Generate Nomor Faktur Pesanan Pelanggan (Format: KRS-YYMM-XXXX).
     * Wajib dipanggil di dalam blok transaksi PDO aktif.
     */
    public static function nextOrderNumber(PDO $pdo): string
    {
        // Advisory Lock transaksi PostgreSQL (otomatis release saat commit/rollback)
        $pdo->query("SELECT pg_advisory_xact_lock(hashtext('pesanan_nomor_nota'))");

        $yearMonth = date('ym');
        $prefix = 'KRS-' . $yearMonth . '-';

        $stmt = $pdo->prepare("
            SELECT nomor_nota 
            FROM public.pesanan 
            WHERE nomor_nota LIKE :pref 
            ORDER BY LENGTH(nomor_nota) DESC, nomor_nota DESC 
            LIMIT 1
        ");
        $stmt->execute(['pref' => $prefix . '%']);
        $latest = $stmt->fetch();

        if ($latest && !empty($latest['nomor_nota'])) {
            $parts = explode('-', (string)$latest['nomor_nota']);
            $seq = (int)end($parts);
            return $prefix . str_pad((string)($seq + 1), 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '0001';
    }

    /**
     * Dapatkan saran nomor nota untuk form tampilan (Create View).
     */
    public static function suggestOrderNumber(): string
    {
        $pdo = Database::getConnection();
        $yearMonth = date('ym');
        $prefix = 'KRS-' . $yearMonth . '-';

        $stmt = $pdo->prepare("
            SELECT nomor_nota 
            FROM public.pesanan 
            WHERE nomor_nota LIKE :pref 
            ORDER BY LENGTH(nomor_nota) DESC, nomor_nota DESC 
            LIMIT 1
        ");
        $stmt->execute(['pref' => $prefix . '%']);
        $latest = $stmt->fetch();

        if ($latest && !empty($latest['nomor_nota'])) {
            $parts = explode('-', (string)$latest['nomor_nota']);
            $seq = (int)end($parts);
            return $prefix . str_pad((string)($seq + 1), 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '0001';
    }

    /**
     * Generate Nomor Surat Jalan Logistik (Format: SJ-YYYYMMDD-XXX).
     * Wajib dipanggil di dalam blok transaksi PDO aktif.
     */
    public static function nextDeliveryNumber(PDO $pdo): string
    {
        $pdo->query("SELECT pg_advisory_xact_lock(hashtext('surat_jalan_nomor'))");

        $todayDate = date('Ymd');
        $prefix = 'SJ-' . $todayDate . '-';

        $stmt = $pdo->prepare("
            SELECT nomor_surat_jalan 
            FROM public.surat_jalan 
            WHERE nomor_surat_jalan LIKE :pref 
            ORDER BY LENGTH(nomor_surat_jalan) DESC, nomor_surat_jalan DESC 
            LIMIT 1
        ");
        $stmt->execute(['pref' => $prefix . '%']);
        $latest = $stmt->fetch();

        if ($latest && !empty($latest['nomor_surat_jalan'])) {
            $parts = explode('-', (string)$latest['nomor_surat_jalan']);
            $seq = (int)end($parts);
            return $prefix . str_pad((string)($seq + 1), 3, '0', STR_PAD_LEFT);
        }

        return $prefix . '001';
    }

    /**
     * Generate Nomor Faktur Pembelian Supplier (Format: PB-YYYYMMDD-XXX).
     * Wajib dipanggil di dalam blok transaksi PDO aktif.
     */
    public static function nextPurchaseNumber(PDO $pdo): string
    {
        $pdo->query("SELECT pg_advisory_xact_lock(hashtext('pembelian_nomor_faktur'))");

        $todayDate = date('Ymd');
        $prefix = 'PB-' . $todayDate . '-';

        $stmt = $pdo->prepare("
            SELECT nomor_faktur_pembelian 
            FROM public.pembelian 
            WHERE nomor_faktur_pembelian LIKE :pref 
            ORDER BY LENGTH(nomor_faktur_pembelian) DESC, nomor_faktur_pembelian DESC 
            LIMIT 1
        ");
        $stmt->execute(['pref' => $prefix . '%']);
        $latest = $stmt->fetch();

        if ($latest && !empty($latest['nomor_faktur_pembelian'])) {
            $parts = explode('-', (string)$latest['nomor_faktur_pembelian']);
            $seq = (int)end($parts);
            return $prefix . str_pad((string)($seq + 1), 3, '0', STR_PAD_LEFT);
        }

        return $prefix . '001';
    }

    /**
     * Dapatkan saran nomor faktur pembelian untuk tampilan form input.
     */
    public static function suggestPurchaseNumber(): string
    {
        $pdo = Database::getConnection();
        $todayDate = date('Ymd');
        $prefix = 'PB-' . $todayDate . '-';

        $stmt = $pdo->prepare("
            SELECT nomor_faktur_pembelian 
            FROM public.pembelian 
            WHERE nomor_faktur_pembelian LIKE :pref 
            ORDER BY LENGTH(nomor_faktur_pembelian) DESC, nomor_faktur_pembelian DESC 
            LIMIT 1
        ");
        $stmt->execute(['pref' => $prefix . '%']);
        $latest = $stmt->fetch();

        if ($latest && !empty($latest['nomor_faktur_pembelian'])) {
            $parts = explode('-', (string)$latest['nomor_faktur_pembelian']);
            $seq = (int)end($parts);
            return $prefix . str_pad((string)($seq + 1), 3, '0', STR_PAD_LEFT);
        }

        return $prefix . '001';
    }
}

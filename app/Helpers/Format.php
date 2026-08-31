<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * app/Helpers/Format.php
 * Helper format angka, rupiah, tanggal lokal Indonesia, dan badge Material Design 3.
 */

class Format
{
    /**
     * Format angka ke Rupiah (Rp 15.000)
     */
    public static function rupiah(float|int|string|null $angka, bool $withPrefix = true): string
    {
        $val = (float)($angka ?? 0);
        $formatted = number_format($val, 0, ',', '.');
        return $withPrefix ? 'Rp ' . $formatted : $formatted;
    }

    /**
     * Format tanggal ke format lokal Indonesia (26 Agustus 2026 atau 26 Agu 2026)
     */
    public static function tanggal(string|null $datetime, bool $withTime = false, bool $shortMonth = true): string
    {
        if (empty($datetime)) {
            return '-';
        }

        $timestamp = strtotime($datetime);
        if (!$timestamp) {
            return $datetime;
        }

        $bulanFull = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $bulanShort = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        $d = (int)date('d', $timestamp);
        $m = (int)date('m', $timestamp);
        $y = (int)date('Y', $timestamp);

        $namaBulan = $shortMonth ? ($bulanShort[$m] ?? '') : ($bulanFull[$m] ?? '');
        $hasil = "{$d} {$namaBulan} {$y}";

        if ($withTime) {
            $hasil .= ' ' . date('H:i', $timestamp);
        }

        return $hasil;
    }

    /**
     * Generate HTML Badge status dengan token Material Design 3
     */
    public static function badgeStatus(string $status): string
    {
        $statusLower = strtolower($status);

        switch ($statusLower) {
            case 'lunas':
            case 'selesai':
            case 'disetujui':
            case 'aktif':
            case 'diterima':
                return '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950/80 text-emerald-300 border border-emerald-500/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> ' . ucfirst($status) . '
                </span>';

            case 'belum_lunas':
            case 'menunggu':
            case 'menunggu_approval':
            case 'draf':
            case 'draf_n8n':
                return '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-950/80 text-amber-300 border border-amber-500/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span> ' . str_replace('_', ' ', ucfirst($status)) . '
                </span>';

            case 'tempo':
            case 'sedang_dikirim':
            case 'siap_kirim':
                return '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-cyan-950/80 text-cyan-300 border border-cyan-500/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span> ' . str_replace('_', ' ', ucfirst($status)) . '
                </span>';

            case 'batal':
            case 'dibatalkan':
            case 'ditolak':
            case 'nonaktif':
                return '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-950/80 text-rose-300 border border-rose-500/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> ' . ucfirst($status) . '
                </span>';

            default:
                return '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                    ' . ucfirst($status) . '
                </span>';
        }
    }

    /**
     * Konversi angka nominal ke kalimat Terbilang Bahasa Indonesia
     * Contoh: 67500 -> "Enam Puluh Tujuh Ribu Lima Ratus Rupiah"
     */
    public static function terbilang(float|int|string|null $angka, bool $withRupiah = true): string
    {
        $val = (int)floor(abs((float)($angka ?? 0)));
        if ($val === 0) {
            return $withRupiah ? 'Nol Rupiah' : '';
        }

        $baca = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        $hasil = '';

        if ($val < 12) {
            $hasil = $baca[$val];
        } elseif ($val < 20) {
            $hasil = self::terbilang($val - 10, false) . ' Belas';
        } elseif ($val < 100) {
            $sisa = $val % 10;
            $hasil = self::terbilang((int)($val / 10), false) . ' Puluh' . ($sisa > 0 ? ' ' . self::terbilang($sisa, false) : '');
        } elseif ($val < 200) {
            $sisa = $val - 100;
            $hasil = 'Seratus' . ($sisa > 0 ? ' ' . self::terbilang($sisa, false) : '');
        } elseif ($val < 1000) {
            $sisa = $val % 100;
            $hasil = self::terbilang((int)($val / 100), false) . ' Ratus' . ($sisa > 0 ? ' ' . self::terbilang($sisa, false) : '');
        } elseif ($val < 2000) {
            $sisa = $val - 1000;
            $hasil = 'Seribu' . ($sisa > 0 ? ' ' . self::terbilang($sisa, false) : '');
        } elseif ($val < 1000000) {
            $sisa = $val % 1000;
            $hasil = self::terbilang((int)($val / 1000), false) . ' Ribu' . ($sisa > 0 ? ' ' . self::terbilang($sisa, false) : '');
        } elseif ($val < 1000000000) {
            $sisa = (int)fmod((float)$val, 1000000);
            $hasil = self::terbilang((int)($val / 1000000), false) . ' Juta' . ($sisa > 0 ? ' ' . self::terbilang($sisa, false) : '');
        } elseif ($val < 1000000000000) {
            $sisa = (int)fmod((float)$val, 1000000000);
            $hasil = self::terbilang((int)($val / 1000000000), false) . ' Milyar' . ($sisa > 0 ? ' ' . self::terbilang($sisa, false) : '');
        } else {
            $sisa = (int)fmod((float)$val, 1000000000000);
            $hasil = self::terbilang((int)($val / 1000000000000), false) . ' Triliun' . ($sisa > 0 ? ' ' . self::terbilang($sisa, false) : '');
        }

        $hasil = trim(preg_replace('/\s+/', ' ', $hasil));
        return $withRupiah ? $hasil . ' Rupiah' : $hasil;
    }
}

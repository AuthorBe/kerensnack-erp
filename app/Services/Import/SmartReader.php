<?php
declare(strict_types=1);

namespace App\Services\Import;

/**
 * SmartReader
 * AI-Like Smart Data Reader for Excel/CSV Import.
 * Diadaptasi dan disempurnakan dari modul arsitektur rekap-mukholif.
 */
class SmartReader
{
    /**
     * Membersihkan dan menstandarisasi satu nama header kolom Excel.
     */
    public static function cleanHeader(string $header): string
    {
        $res = self::standardizeHeaders([$header]);
        return $res[0] ?? '';
    }

    /**
     * Mengekstrak kode entitas dari string gabungan nama (contoh: "[PEL-001] Toko Abadi" atau "SKU-100 - Keripik")
     */
    public static function extractCodeFromName(string $text): ?string
    {
        $text = trim($text);
        // Format [KODE] ...
        if (preg_match('/^\[([A-Za-z0-9_\-]+)\]/', $text, $m)) {
            return trim($m[1]);
        }
        // Format KODE - Nama
        if (preg_match('/^([A-Za-z0-9_\-]+)\s*[\-:]\s+/', $text, $m)) {
            return trim($m[1]);
        }
        // Format Nama (KODE)
        if (preg_match('/\(([A-Za-z0-9_\-]+)\)$/', $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /**
     * Alias untuk normalisasi numerik (cleanNumber)
     */
    public static function cleanNumber($val, float $default = 0.0): float
    {
        return self::normalizeNumeric($val, $default);
    }

    /**
     * Alias untuk normalisasi boolean (cleanBoolean)
     */
    public static function cleanBoolean($val, bool $default = false): bool
    {
        return self::normalizeBoolean($val, $default);
    }

    /**
     * Membersihkan dan menstandarisasi nama header kolom Excel.
     */
    public static function standardizeHeaders(array $headers): array
    {
        return array_map(function ($h) {
            $replaced = str_replace([' ', '-', '/'], '_', (string)$h);
            $cleaned  = preg_replace('/[^a-zA-Z0-9_]/', '', $replaced) ?? '';
            $deduped  = preg_replace('/_+/', '_', $cleaned) ?? '';
            return strtolower(trim($deduped, "_ \t\n\r\0\x0B"));
        }, $headers);
    }

    /**
     * Mencari index kolom terbaik yang cocok dengan salah satu alias yang diberikan
     */
    public static function findBestMatch(array $headers, array $aliases): ?int
    {
        $stdHeaders = self::standardizeHeaders($headers);

        // 1. Exact match
        foreach ($aliases as $alias) {
            $aNorm = str_replace([' ', '-'], '_', strtolower(trim((string)$alias)));
            foreach ($stdHeaders as $idx => $h) {
                if ($h === $aNorm) {
                    return $idx;
                }
            }
        }

        // 2. Partial match (skip kunci pendek/generik)
        foreach ($aliases as $alias) {
            $aNorm = str_replace([' ', '-'], '_', strtolower(trim((string)$alias)));
            if (in_array($aNorm, ['id', 'no', 'kd', 'nama', 'kode'], true) || strlen($aNorm) <= 4) {
                continue;
            }
            foreach ($stdHeaders as $idx => $h) {
                if (str_contains($h, $aNorm) || str_contains($aNorm, $h)) {
                    return $idx;
                }
            }
        }

        return null;
    }

    /**
     * Mencari nilai dalam satu baris data berdasarkan kecocokan alias kunci secara cerdas.
     * Mendukung exact match dan partial match dengan normalisasi spasi/underscore.
     */
    public static function getSmartValue(array $row, array $keys)
    {
        // 1. Exact match (case-insensitive & normalisasi spasi/underscore)
        foreach ($keys as $k) {
            $kNorm = str_replace([' ', '-'], '_', strtolower(trim((string)$k)));
            foreach ($row as $rowKey => $rowVal) {
                $rKeyNorm = str_replace([' ', '-'], '_', strtolower(trim((string)$rowKey)));
                if ($rKeyNorm === $kNorm && $rowVal !== '' && $rowVal !== null) {
                    return is_string($rowVal) ? trim($rowVal) : $rowVal;
                }
            }
        }

        // 2. Partial match (skip kunci pendek atau generik seperti 'id', 'no', 'kd', 'nama', 'kode', 'merek' agar tidak salah tangkap)
        foreach ($keys as $k) {
            $kNorm = str_replace([' ', '-'], '_', strtolower(trim((string)$k)));
            if (in_array($kNorm, ['id', 'no', 'kd', 'nama', 'kode', 'merek', 'brand'], true) || strlen($kNorm) <= 4) {
                continue;
            }
            $isCodeKey = str_contains($kNorm, 'kode') || str_contains($kNorm, 'kd');
            foreach ($row as $rowKey => $rowVal) {
                $rKeyNorm = str_replace([' ', '-'], '_', strtolower(trim((string)$rowKey)));
                $isCodeCol = str_contains($rKeyNorm, 'kode') || str_contains($rKeyNorm, 'kd');
                
                // Jangan cocokkan pencarian nama parsial ke kolom kode, atau sebaliknya
                if ($isCodeKey !== $isCodeCol) {
                    continue;
                }

                if (str_contains($rKeyNorm, $kNorm) && $rowVal !== '' && $rowVal !== null) {
                    return is_string($rowVal) ? trim($rowVal) : $rowVal;
                }
            }
        }

        return null;
    }

    /**
     * Memeriksa apakah salah satu kata kunci terdeteksi dalam array header Excel.
     */
    public static function hasSmartColumn(array $headers, array $keys): bool
    {
        foreach ($keys as $k) {
            $kNorm = strtolower(trim((string)$k));
            foreach ($headers as $h) {
                if (strtolower(trim((string)$h)) === $kNorm) {
                    return true;
                }
            }
        }

        foreach ($keys as $k) {
            $kNorm = strtolower(trim((string)$k));
            if (in_array($kNorm, ['id', 'no', 'kd'], true)) {
                continue;
            }
            foreach ($headers as $h) {
                if (str_contains(strtolower(trim((string)$h)), $kNorm)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Membandingkan dua nama secara cerdas:
     * Nama mirip = UPDATE sah, Nama sangat berbeda = Potensi Konflik Fatal.
     */
    public static function isSimilarName(string $name1, string $name2): bool
    {
        $n1 = strtolower(trim($name1));
        $n2 = strtolower(trim($name2));

        if ($n1 === $n2) {
            return true;
        }

        $n1 = preg_replace('/[^a-z0-9 ]/', '', $n1) ?? '';
        $n2 = preg_replace('/[^a-z0-9 ]/', '', $n2) ?? '';
        $n1 = preg_replace('/\s+/', ' ', $n1) ?? '';
        $n2 = preg_replace('/\s+/', ' ', $n2) ?? '';

        if ($n1 === $n2) {
            return true;
        }

        $words1 = array_values(array_filter(explode(' ', $n1)));
        $words2 = array_values(array_filter(explode(' ', $n2)));

        if (empty($words1) || empty($words2)) {
            return false;
        }

        $shorter = count($words1) <= count($words2) ? $words1 : $words2;
        $longer  = count($words1) <= count($words2) ? $words2 : $words1;

        $allMatch = true;
        foreach ($shorter as $sw) {
            if (strlen($sw) <= 1) {
                continue;
            }
            $found = false;
            foreach ($longer as $lw) {
                if ($lw === $sw || str_starts_with($lw, $sw) || str_starts_with($sw, $lw)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $allMatch = false;
                break;
            }
        }

        if ($allMatch) {
            return true;
        }

        $maxLen = max(strlen($n1), strlen($n2));
        if ($maxLen === 0) {
            return false;
        }

        $dist = levenshtein($n1, $n2);

        if ($maxLen <= 4) {
            return $dist === 0;
        }

        return ($dist <= 2 || ($dist / $maxLen) < 0.20);
    }

    /**
     * Mencari baris header yang valid secara otomatis berdasarkan kumpulan kolom wajib.
     */
    public static function extractSmartHeader(array $originalRows, array $requiredColumnGroups): array
    {
        $header = [];
        $headerIndex = -1;

        foreach ($originalRows as $idx => $r) {
            if (!is_array($r)) {
                continue;
            }
            $testHeader = self::standardizeHeaders($r);
            $filledCols = count(array_filter($testHeader, fn($h) => trim((string)$h) !== ''));

            if ($filledCols >= 2) {
                $matchesAllGroups = true;
                foreach ($requiredColumnGroups as $group) {
                    if (!self::hasSmartColumn($testHeader, $group)) {
                        $matchesAllGroups = false;
                        break;
                    }
                }

                if ($matchesAllGroups) {
                    $header = $testHeader;
                    $headerIndex = $idx;
                    break;
                }
            }
        }

        return ['header' => $header, 'index' => $headerIndex];
    }

    /**
     * Menyaring baris data mentah untuk membuang baris kosong atau baris footer laporan.
     */
    public static function filterSmartDataRows(array $rowsRaw): array
    {
        $rows = [];
        foreach ($rowsRaw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $filledCells = array_filter($row, fn($c) => trim((string)$c) !== '');
            if (empty($filledCells)) {
                continue;
            }

            // Filter catatan footer satu kolom
            if (count($filledCells) === 1) {
                $text = strtolower(trim((string)reset($filledCells)));
                if (strlen($text) > 70 || preg_match('/(dokumen|rahasia|dicetak|halaman|total|catatan|petunjuk|keterangan|ringkasan)/i', $text)) {
                    continue;
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Menggabungkan header dan row menjadi associative array secara aman.
     */
    public static function buildRowData(array $header, array $rawRow): array
    {
        $stdHeaders = self::standardizeHeaders($header);
        $rowData = [];
        foreach ($stdHeaders as $colIdx => $colName) {
            if ($colName !== '') {
                $rowData[$colName] = $rawRow[$colIdx] ?? '';
            }
        }
        return $rowData;
    }

    /**
     * Normalisasi nilai boolean dari Excel ('ya', 'aktif', '1', 'true', 'konsinyasi', dll.)
     */
    public static function normalizeBoolean($val, bool $default = false): bool
    {
        if ($val === null || $val === '') {
            return $default;
        }
        if (is_bool($val)) {
            return $val;
        }
        $s = strtolower(trim((string)$val));
        if (in_array($s, ['1', 'true', 'ya', 'yes', 'aktif', 'active', 'konsinyasi', 'y'], true)) {
            return true;
        }
        if (in_array($s, ['0', 'false', 'tidak', 'no', 'nonaktif', 'bukan', 'n', 'reguler'], true)) {
            return false;
        }
        return $default;
    }

    /**
     * Normalisasi nilai numerik/mata uang dari Excel ('Rp 15.000', '1,500,000.50', '1.500.000,50', '15000')
     */
    public static function normalizeNumeric($val, float $default = 0.0): float
    {
        if ($val === null || $val === '') {
            return $default;
        }
        if (is_numeric($val)) {
            return (float)$val;
        }

        $s = trim((string)$val);
        // Tangani dash atau strip kosong
        if ($s === '-' || $s === '—' || $s === '–') {
            return $default;
        }

        // Hapus prefix mata uang dan spasi
        $s = preg_replace('/^[^\d\-]+/', '', $s) ?? '';
        $s = preg_replace('/[^\d,\.\-]/', '', $s) ?? '';

        if ($s === '' || $s === '-') {
            return $default;
        }

        $lastDot = strrpos($s, '.');
        $lastComma = strrpos($s, ',');

        if ($lastDot !== false && $lastComma !== false) {
            if ($lastDot > $lastComma) {
                // Format US: 1,500,000.50 -> buang koma
                $s = str_replace(',', '', $s);
            } else {
                // Format ID: 1.500.000,50 -> buang titik, ganti koma jadi titik
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            }
        } elseif ($lastDot !== false) {
            // Hanya ada titik. Cek apakah pemisah ribuan (misal 25.000 atau 1.500.000)
            // Jika ada lebih dari satu titik, atau titik diikuti tepat 3 digit dan tidak ada digit lain
            $parts = explode('.', $s);
            if (count($parts) > 2) {
                // Beberapa titik -> pasti ribuan (1.500.000)
                $s = str_replace('.', '', $s);
            } elseif (count($parts) === 2 && strlen($parts[1]) === 3 && (int)$parts[0] > 0) {
                // Contoh '25.000' -> ribuan rupiah
                $s = str_replace('.', '', $s);
            }
        } elseif ($lastComma !== false) {
            // Hanya ada koma
            $parts = explode(',', $s);
            if (count($parts) > 2) {
                $s = str_replace(',', '', $s);
            } elseif (count($parts) === 2) {
                if (strlen($parts[1]) === 3 && (int)$parts[0] > 0) {
                    $s = str_replace(',', '', $s);
                } else {
                    $s = str_replace(',', '.', $s);
                }
            }
        }

        return is_numeric($s) ? (float)$s : $default;
    }
}

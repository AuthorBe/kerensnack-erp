<?php
declare(strict_types=1);

namespace App\Helpers;

use Database;
use Throwable;

/**
 * app/Helpers/CompanySetting.php
 * Helper manajemen profil dan konfigurasi resmi perusahaan (KEREN SNACK).
 * Digunakan secara luas untuk Kop Faktur, Surat Jalan, Nota Konsinyasi, dan Struk POS.
 */
class CompanySetting
{
    /**
     * Default fallbacks yang aman jika belum tersimpan di database.
     */
    public const DEFAULTS = [
        'nama'            => 'KEREN SNACK INDONESIA',
        'tagline'         => 'Produsen & Distributor Aneka Makanan Ringan Berkualitas',
        'alamat'          => 'Jl. Industri Snack No. 88, Jawa Barat',
        'telepon'         => '0812-3456-7890',
        'email'           => 'admin@kerensnack.com',
        'website'         => 'www.kerensnack.id',
        'catatan_faktur'  => 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan tanpa persetujuan tertulis.',
        'nama_bank'       => 'BCA',
        'nomor_rekening'  => '8820-123-4567',
        'atas_nama_bank'  => 'KEREN SNACK INDONESIA',
        'logo_url'        => '',
    ];

    /**
     * Cache memori per-request untuk optimasi performa.
     */
    private static ?array $cache = null;

    /**
     * Ambil seluruh konfigurasi profil perusahaan.
     *
     * @return array<string, string>
     */
    public static function getAll(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $settings = self::DEFAULTS;

        try {
            $rows = Database::fetchAll(
                "SELECT kunci, nilai FROM public.pengaturan_sistem WHERE kunci LIKE 'perusahaan_%'"
            );

            foreach ($rows as $row) {
                $rawKey = (string)($row['kunci'] ?? '');
                $cleanKey = preg_replace('/^perusahaan_/', '', $rawKey);
                if ($cleanKey !== null && array_key_exists($cleanKey, self::DEFAULTS)) {
                    $val = trim((string)($row['nilai'] ?? ''));
                    // Gunakan nilai DB jika tidak kosong, atau default jika kosong
                    $settings[$cleanKey] = ($val !== '') ? $val : self::DEFAULTS[$cleanKey];
                }
            }
        } catch (Throwable $e) {
            error_log("CompanySetting::getAll error: " . $e->getMessage());
        }

        self::$cache = $settings;
        return self::$cache;
    }

    /**
     * Ambil salah satu nilai konfigurasi profil perusahaan.
     *
     * @param string $key Contoh: 'nama', 'tagline', 'alamat', 'telepon', 'email', 'website', dll.
     * @param string $default Fallback kustom jika kunci tidak ditemukan.
     * @return string
     */
    public static function get(string $key, string $default = ''): string
    {
        $all = self::getAll();
        if (array_key_exists($key, $all)) {
            return (string)$all[$key];
        }
        return $default !== '' ? $default : (self::DEFAULTS[$key] ?? '');
    }

    /**
     * Simpan / perbarui konfigurasi profil perusahaan secara atomik (UPSERT).
     *
     * @param array<string, string|null> $data Key tanpa prefix 'perusahaan_'
     * @return bool
     */
    public static function save(array $data): bool
    {
        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO public.pengaturan_sistem (kunci, nilai, diubah_pada)
                VALUES (:kunci, :nilai, NOW())
                ON CONFLICT (kunci) DO UPDATE 
                SET nilai = EXCLUDED.nilai, diubah_pada = NOW()
            ");

            foreach (self::DEFAULTS as $key => $defaultVal) {
                if (array_key_exists($key, $data)) {
                    $val = trim((string)($data[$key] ?? ''));
                    $dbKey = 'perusahaan_' . $key;
                    $stmt->execute([
                        ':kunci' => $dbKey,
                        ':nilai' => $val,
                    ]);
                }
            }

            $pdo->commit();
            self::clearCache();
            return true;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("CompanySetting::save error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Bersihkan cache memori profil perusahaan.
     */
    public static function clearCache(): void
    {
        self::$cache = null;
    }
}

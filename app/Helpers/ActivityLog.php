<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Helpers/ActivityLog.php
 * Helper universal untuk mencatat seluruh audit trail aktivitas pengguna ke tabel public.log_aktivitas.
 * Dilengkapi Smart Delta Diffing, Data Hygiene (Anti-Bloat), dan Proteksi Kredensial Developer.
 */
class ActivityLog
{
    /**
     * Catat aktivitas audit trail ke public.log_aktivitas
     */
    public static function log(
        string $kategori,
        string $jenisAksi,
        string $deskripsi,
        ?string $tabelTerdampak = null,
        ?string $idReferensi = null,
        array|object|null $dataSebelum = null,
        array|object|null $dataSesudah = null,
        string $sumberAksi = 'web_app',
        ?string $userId = null,
        ?string $userName = null,
        ?string $userRole = null
    ): bool {
        try {
            $validKategori = [
                'keuangan', 'penjualan', 'logistik', 'gudang_stok',
                'produksi_bom', 'hr_payroll', 'master_data', 'keamanan_auth', 'ai_interaction'
            ];

            $kategoriLower = strtolower(trim($kategori));
            $kategoriFinal = match(true) {
                in_array($kategoriLower, $validKategori, true) => $kategoriLower,
                str_contains($kategoriLower, 'keamanan') || str_contains($kategoriLower, 'security') || str_contains($kategoriLower, 'auth') || str_contains($kategoriLower, 'user') || str_contains($kategoriLower, 'pengguna') || str_contains($kategoriLower, 'peran') || str_contains($kategoriLower, 'role') || str_contains($kategoriLower, 'perm') || str_contains($kategoriLower, 'akses') || str_contains($kategoriLower, 'login') || str_contains($kategoriLower, 'sandi') || str_contains($kategoriLower, 'password') => 'keamanan_auth',
                str_contains($kategoriLower, 'uang') || str_contains($kategoriLower, 'kas') || str_contains($kategoriLower, 'biaya') || str_contains($kategoriLower, 'refund') => 'keuangan',
                str_contains($kategoriLower, 'jual') || str_contains($kategoriLower, 'order') || str_contains($kategoriLower, 'pesanan') || str_contains($kategoriLower, 'pos') => 'penjualan',
                str_contains($kategoriLower, 'kirim') || str_contains($kategoriLower, 'jalan') || str_contains($kategoriLower, 'antar') || str_contains($kategoriLower, 'driver') => 'logistik',
                str_contains($kategoriLower, 'stok') || str_contains($kategoriLower, 'gudang') || str_contains($kategoriLower, 'opname') || str_contains($kategoriLower, 'beli') || str_contains($kategoriLower, 'bahan') => 'gudang_stok',
                str_contains($kategoriLower, 'gaji') || str_contains($kategoriLower, 'payroll') || str_contains($kategoriLower, 'karyawan') => 'hr_payroll',
                str_contains($kategoriLower, 'produksi') || str_contains($kategoriLower, 'bom') || str_contains($kategoriLower, 'resep') => 'produksi_bom',
                str_contains($kategoriLower, 'ai') || str_contains($kategoriLower, 'gemini') => 'ai_interaction',
                default => 'master_data'
            };

            $validSumber = ['telegram_bot', 'whatsapp_bot', 'web_app', 'n8n_automation', 'database_trigger', 'system_cron'];
            $sumberFinal = in_array($sumberAksi, $validSumber, true) ? $sumberAksi : 'web_app';

            $uid = $userId ?? (Auth::id() ?: null);
            $nama = $userName ?? (Auth::user()['nama_lengkap'] ?? Auth::name() ?? 'Staff ERP');
            $peran = $userRole ?? (Auth::role() ?? 'admin');

            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if (str_contains($ip, ',')) {
                $ip = trim(explode(',', $ip)[0]);
            }
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

            // 1. UUID Safety: Pastikan id_referensi valid UUID v4
            $cleanIdRef = null;
            if (!empty($idReferensi)) {
                if (self::isValidUuid((string)$idReferensi)) {
                    $cleanIdRef = (string)$idReferensi;
                } else {
                    // Jika bukan UUID valid, sematkan ke deskripsi agar data referensi tidak hilang
                    $deskripsi .= " [Ref: {$idReferensi}]";
                }
            }

            // 2. Smart Delta Diffing & Anti-Bloat Data Hygiene
            [$deltaSebelum, $deltaSesudah] = self::computeDelta($dataSebelum, $dataSesudah);

            Database::execute("
                INSERT INTO public.log_aktivitas (
                    pengguna_id, nama_aktor, peran_aktor, sumber_aksi, kategori_aktivitas,
                    jenis_aksi, tabel_terdampak, id_referensi, deskripsi_aktivitas,
                    data_sebelum, data_sesudah, ip_address, user_agent, waktu_kejadian
                ) VALUES (
                    :uid, :nama, :peran, :sumber, :kategori,
                    :jenis, :tabel, :id_ref, :deskripsi,
                    :sebelum, :sesudah, :ip, :ua, NOW()
                )
            ", [
                'uid' => $uid,
                'nama' => $nama,
                'peran' => $peran,
                'sumber' => $sumberFinal,
                'kategori' => $kategoriFinal,
                'jenis' => $jenisAksi,
                'tabel' => $tabelTerdampak,
                'id_ref' => $cleanIdRef,
                'deskripsi' => $deskripsi,
                'sebelum' => $deltaSebelum ? json_encode($deltaSebelum, JSON_UNESCAPED_UNICODE) : null,
                'sesudah' => $deltaSesudah ? json_encode($deltaSesudah, JSON_UNESCAPED_UNICODE) : null,
                'ip' => $ip,
                'ua' => $ua,
            ]);

            return true;
        } catch (Throwable $e) {
            error_log("ActivityLog Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper record() untuk kompatibilitas mutasi RBAC & User Management
     */
    public static function record(
        ?string $userId,
        string $jenisAksi,
        string $deskripsi,
        ?string $tabelTerdampak = null,
        ?string $idReferensi = null,
        array|object|null $dataSebelum = null,
        array|object|null $dataSesudah = null
    ): bool {
        $upperAction = strtoupper($jenisAksi);

        if (str_contains($upperAction, 'ROLE') || str_contains($upperAction, 'USER') || str_contains($upperAction, 'PERM') || str_contains($upperAction, 'AUTH') || str_contains($upperAction, 'SYSTEM') || str_contains($upperAction, 'BACKUP') || str_contains($upperAction, 'SECURITY') || str_contains($upperAction, 'PASSWORD') || str_contains($upperAction, 'SANDI')) {
            $kategori = 'keamanan_auth';
        } else {
            $kategori = 'master_data';
        }

        return self::log(
            $kategori,
            $jenisAksi,
            $deskripsi,
            $tabelTerdampak,
            $idReferensi,
            $dataSebelum,
            $dataSesudah,
            'web_app',
            $userId
        );
    }

    /**
     * Validasi apakah string adalah format UUID standar
     */
    public static function isValidUuid(?string $uuid): bool
    {
        if (empty($uuid)) {
            return false;
        }
        return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Smart Delta Diffing:
     * Menghitung perbedaan nilai antara state sebelum dan sesudah.
     * Hanya field yang BERUBAH yang disimpan ke payload JSON, menghemat 80-95% ukuran data di database.
     *
     * @return array{0: ?array, 1: ?array} [deltaSebelum, deltaSesudah]
     */
    public static function computeDelta(array|object|null $before, array|object|null $after): array
    {
        $arrBefore = is_object($before) ? (array)$before : $before;
        $arrAfter  = is_object($after)  ? (array)$after  : $after;

        // Kasus 1: Keduanya null
        if ($arrBefore === null && $arrAfter === null) {
            return [null, null];
        }

        // Kasus 2: Penambahan baru (before null)
        if ($arrBefore === null) {
            return [null, self::sanitizePayload($arrAfter)];
        }

        // Kasus 3: Penghapusan total (after null)
        if ($arrAfter === null) {
            return [self::sanitizePayload($arrBefore), null];
        }

        // Kasus 4: Update (Keduanya array) -> Hitung selisih kunci
        $diffBefore = [];
        $diffAfter = [];

        $allKeys = array_unique(array_merge(array_keys($arrBefore), array_keys($arrAfter)));

        foreach ($allKeys as $key) {
            $hasBefore = array_key_exists($key, $arrBefore);
            $hasAfter  = array_key_exists($key, $arrAfter);

            if ($hasBefore && !$hasAfter) {
                // Kunci dihapus
                $diffBefore[$key] = $arrBefore[$key];
            } elseif (!$hasBefore && $hasAfter) {
                // Kunci ditambahkan
                $diffAfter[$key] = $arrAfter[$key];
            } else {
                // Keduanya ada: bandingkan nilai
                $valB = $arrBefore[$key];
                $valA = $arrAfter[$key];

                // Normalisasi tipe boolean dan numerik agar tidak false positive
                if ($valB !== $valA && json_encode($valB) !== json_encode($valA)) {
                    $diffBefore[$key] = $valB;
                    $diffAfter[$key]  = $valA;
                }
            }
        }

        // Jika tidak ada perubahan sama sekali
        if (empty($diffBefore) && empty($diffAfter)) {
            return [null, null];
        }

        return [self::sanitizePayload($diffBefore), self::sanitizePayload($diffAfter)];
    }

    /**
     * Sanitasi Data Sensitif & Truncation Guard:
     * Menghapus password, hash, token, serta memotong string raksasa/gambar base64.
     */
    public static function sanitizePayload(mixed $data): ?array
    {
        if ($data === null) {
            return null;
        }

        if (!is_array($data)) {
            return ['_value' => (string)$data];
        }

        $forbiddenKeys = [
            'kata_sandi', 'password', 'kata_sandi_hash', 'password_hash',
            'token', 'remember_token', 'api_key', 'secret', 'pin', 'refresh_token'
        ];

        $cleaned = [];

        foreach ($data as $key => $val) {
            $keyStr = (string)$key;
            $lowerKey = strtolower($keyStr);

            // Buang field rahasia/kredensial
            if (in_array($lowerKey, $forbiddenKeys, true) || str_contains($lowerKey, 'password') || str_contains($lowerKey, 'sandi') || str_contains($lowerKey, 'token')) {
                $cleaned[$keyStr] = '[TERPROTEKSI]';
                continue;
            }

            if (is_array($val)) {
                $cleaned[$keyStr] = self::sanitizePayload($val);
            } elseif (is_string($val)) {
                // Proteksi gambar base64
                if (str_starts_with($val, 'data:image/')) {
                    $cleaned[$keyStr] = '[GAMBAR_BASE64: ' . strlen($val) . ' bytes]';
                } elseif (strlen($val) > 300) {
                    $cleaned[$keyStr] = mb_substr($val, 0, 300) . '... [Dipotong: ' . strlen($val) . ' char]';
                } else {
                    $cleaned[$keyStr] = $val;
                }
            } else {
                $cleaned[$keyStr] = $val;
            }
        }

        return $cleaned;
    }

    /**
     * Parser User Agent Cerdas:
     * Mengubah string User-Agent teknis menjadi ringkasan yang ramah pengguna.
     */
    public static function parseUserAgent(?string $ua): array
    {
        if (empty($ua)) {
            return [
                'browser'   => 'Sistem / Bot',
                'os'        => 'Server',
                'device'    => 'Server',
                'label'     => 'Sistem Otomatis',
                'icon'      => 'cpu',
                'is_mobile' => false,
            ];
        }

        // Deteksi Bot
        if (preg_match('/(bot|crawl|spider|slurp|curl|postman|insomnia)/i', $ua)) {
            return [
                'browser'   => 'Bot / Crawler',
                'os'        => 'API / Cron',
                'device'    => 'Bot',
                'label'     => 'Bot / Automation',
                'icon'      => 'bot',
                'is_mobile' => false,
            ];
        }

        // Deteksi OS
        $os = 'Lainnya';
        if (preg_match('/windows nt 10/i', $ua)) $os = 'Windows 10/11';
        elseif (preg_match('/windows/i', $ua)) $os = 'Windows';
        elseif (preg_match('/iphone/i', $ua)) $os = 'iPhone';
        elseif (preg_match('/ipad/i', $ua)) $os = 'iPad';
        elseif (preg_match('/macintosh|mac os x/i', $ua)) $os = 'macOS';
        elseif (preg_match('/android/i', $ua)) $os = 'Android';
        elseif (preg_match('/linux/i', $ua)) $os = 'Linux';

        // Deteksi Browser
        $browser = 'Browser';
        if (preg_match('/edg\//i', $ua)) $browser = 'Edge';
        elseif (preg_match('/chrome\//i', $ua) && !preg_match('/edg\//i', $ua)) $browser = 'Chrome';
        elseif (preg_match('/safari\//i', $ua) && !preg_match('/chrome\//i', $ua)) $browser = 'Safari';
        elseif (preg_match('/firefox\//i', $ua)) $browser = 'Firefox';
        elseif (preg_match('/opera|opr\//i', $ua)) $browser = 'Opera';

        $isMobile = (bool)preg_match('/(mobile|android|iphone|ipad|ipod)/i', $ua);

        return [
            'browser'   => $browser,
            'os'        => $os,
            'device'    => $isMobile ? 'Mobile' : 'Desktop',
            'label'     => "{$browser} • {$os}",
            'icon'      => $isMobile ? 'smartphone' : 'monitor',
            'is_mobile' => $isMobile,
        ];
    }

    /**
     * Pembersihan & Retensi Log Usang (Pruning):
     * Khusus peran Developer dengan verifikasi kata sandi database.
     */
    public static function pruneLogs(int $daysToKeep, string $developerPassword): array
    {
        if (!Auth::isDeveloper()) {
            return [
                'success' => false,
                'message' => 'Akses Ditolak: Fitur ini eksklusif untuk peran Developer.'
            ];
        }

        if ($daysToKeep < 7) {
            return [
                'success' => false,
                'message' => 'Batas retensi minimal adalah 7 hari untuk mencegah kehilangan data kritis.'
            ];
        }

        $userId = Auth::id();
        if (empty($userId)) {
            return [
                'success' => false,
                'message' => 'Sesi login tidak valid.'
            ];
        }

        // 1. Ambil hash kata sandi developer saat ini langsung dari DB
        $userRow = Database::fetchOne("
            SELECT id, nama_lengkap, nama_pengguna, kata_sandi 
            FROM public.pengguna 
            WHERE id = :id
        ", ['id' => $userId]);

        if (!$userRow || empty($userRow['kata_sandi'])) {
            return [
                'success' => false,
                'message' => 'Akun Developer tidak ditemukan atau belum memiliki kata sandi.'
            ];
        }

        // 2. Verifikasi kata sandi developer
        if (!password_verify($developerPassword, (string)$userRow['kata_sandi'])) {
            // Catat upaya verifikasi gagal ke log keamanan
            self::log(
                'keamanan_auth',
                'PRUNE_FAILED_AUTH',
                "Upaya pembersihan log ditolak: Kata sandi Developer salah.",
                'log_aktivitas',
                null,
                null,
                null,
                'web_app',
                $userId,
                $userRow['nama_lengkap'] ?? Auth::name(),
                'developer'
            );

            return [
                'success' => false,
                'message' => 'Kata sandi Developer salah. Tindakan dibatalkan demi keamanan data.'
            ];
        }

        try {
            // 3. Eksekusi penghapusan log lama
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));

            $countSql = "SELECT COUNT(*) as total FROM public.log_aktivitas WHERE waktu_kejadian < :cutoff";
            $totalToDelete = (int)(Database::fetchOne($countSql, ['cutoff' => $cutoffDate])['total'] ?? 0);

            if ($totalToDelete === 0) {
                return [
                    'success' => true,
                    'deleted' => 0,
                    'message' => "Tidak ada rekaman log yang lebih tua dari {$daysToKeep} hari (sebelum {$cutoffDate})."
                ];
            }

            Database::execute("
                DELETE FROM public.log_aktivitas 
                WHERE waktu_kejadian < :cutoff
            ", ['cutoff' => $cutoffDate]);

            // 4. Rekam aktivitas pembersihan ini ke audit trail
            self::log(
                'keamanan_auth',
                'PRUNE_LOGS',
                "Developer " . Auth::name() . " berhasil membersihkan {$totalToDelete} baris log usang (> {$daysToKeep} hari, batas: {$cutoffDate}).",
                'log_aktivitas',
                null,
                null,
                ['days_retention' => $daysToKeep, 'deleted_rows' => $totalToDelete, 'cutoff_date' => $cutoffDate],
                'web_app',
                $userId,
                $userRow['nama_lengkap'] ?? Auth::name(),
                'developer'
            );

            return [
                'success' => true,
                'deleted' => $totalToDelete,
                'message' => "Berhasil membersihkan {$totalToDelete} rekaman log usang (> {$daysToKeep} hari). Database telah dioptimalkan."
            ];

        } catch (Throwable $e) {
            error_log("PruneLogs Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat membersihkan log: ' . $e->getMessage()
            ];
        }
    }
}
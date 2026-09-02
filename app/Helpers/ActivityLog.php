<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Auth;
use Database;
use Throwable;

/**
 * app/Helpers/ActivityLog.php
 * Helper universal untuk mencatat seluruh audit trail aktivitas pengguna ke tabel public.log_aktivitas.
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
            $uid = $userId ?? (Auth::id() ?: null);
            $nama = $userName ?? (Auth::user()['nama_lengkap'] ?? Auth::name() ?? 'Staff ERP');
            $peran = $userRole ?? (Auth::role() ?? 'admin');

            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if (str_contains($ip, ',')) {
                $ip = trim(explode(',', $ip)[0]);
            }
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

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
                'sumber' => $sumberAksi,
                'kategori' => $kategori,
                'jenis' => $jenisAksi,
                'tabel' => $tabelTerdampak,
                'id_ref' => $idReferensi,
                'deskripsi' => $deskripsi,
                'sebelum' => $dataSebelum ? json_encode($dataSebelum) : null,
                'sesudah' => $dataSesudah ? json_encode($dataSesudah) : null,
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
        $kategori = 'Hak Akses & Sistem';
        $upperAction = strtoupper($jenisAksi);

        if (str_contains($upperAction, 'ROLE')) {
            $kategori = 'Manajemen Peran';
        } elseif (str_contains($upperAction, 'USER')) {
            $kategori = 'Manajemen Pengguna';
        } elseif (str_contains($upperAction, 'PERM')) {
            $kategori = 'Hak Akses';
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
}
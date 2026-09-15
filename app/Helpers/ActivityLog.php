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
            $validKategori = [
                'keuangan', 'penjualan', 'logistik', 'gudang_stok',
                'produksi_bom', 'hr_payroll', 'master_data', 'keamanan_auth', 'ai_interaction'
            ];

            $kategoriLower = strtolower(trim($kategori));
            $kategoriFinal = match(true) {
                in_array($kategoriLower, $validKategori, true) => $kategoriLower,
                str_contains($kategoriLower, 'auth') || str_contains($kategoriLower, 'user') || str_contains($kategoriLower, 'pengguna') || str_contains($kategoriLower, 'peran') || str_contains($kategoriLower, 'role') || str_contains($kategoriLower, 'perm') || str_contains($kategoriLower, 'akses') || str_contains($kategoriLower, 'login') => 'keamanan_auth',
                str_contains($kategoriLower, 'uang') || str_contains($kategoriLower, 'kas') || str_contains($kategoriLower, 'biaya') || str_contains($kategoriLower, 'refund') => 'keuangan',
                str_contains($kategoriLower, 'jual') || str_contains($kategoriLower, 'order') || str_contains($kategoriLower, 'pesanan') || str_contains($kategoriLower, 'pos') => 'penjualan',
                str_contains($kategoriLower, 'kirim') || str_contains($kategoriLower, 'jalan') || str_contains($kategoriLower, 'antar') || str_contains($kategoriLower, 'driver') => 'logistik',
                str_contains($kategoriLower, 'stok') || str_contains($kategoriLower, 'gudang') || str_contains($kategoriLower, 'opname') || str_contains($kategoriLower, 'beli') || str_contains($kategoriLower, 'bahan') => 'gudang_stok',
                str_contains($kategoriLower, 'gaji') || str_contains($kategoriLower, 'payroll') || str_contains($kategoriLower, 'karyawan') => 'hr_payroll',
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
        $upperAction = strtoupper($jenisAksi);

        if (str_contains($upperAction, 'ROLE') || str_contains($upperAction, 'USER') || str_contains($upperAction, 'PERM') || str_contains($upperAction, 'AUTH') || str_contains($upperAction, 'SYSTEM') || str_contains($upperAction, 'BACKUP') || str_contains($upperAction, 'SECURITY')) {
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
}
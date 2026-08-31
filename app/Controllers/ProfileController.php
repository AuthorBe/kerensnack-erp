<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\Flash;
use Database;

/**
 * app/Controllers/ProfileController.php
 * Pengendali Halaman Profil Pengguna, Ganti Username (dengan batas 2x/bulan untuk non-developer),
 * dan Pembaruan Kata Sandi.
 */
class ProfileController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $currentUser = Auth::user();
        $userId = Auth::id();
        $username = $currentUser['nama_pengguna'] ?? '';
        $role = Auth::role();
        $isDeveloper = Auth::isDeveloper();

        // 1. Ambil data pengguna terbaru dari Database
        $userDb = null;
        try {
            $userDb = Database::fetchOne("
                SELECT p.id, p.nama_lengkap, p.nama_pengguna, p.kata_sandi, pr.nama_peran as peran, p.status_aktif, p.dibuat_pada, p.diubah_pada
                FROM public.pengguna p
                JOIN public.peran pr ON p.peran_id = pr.id
                WHERE p.id = :id OR LOWER(p.nama_pengguna) = LOWER(:username)
                LIMIT 1
            ", [
                'id' => $userId ?: '00000000-0000-0000-0000-000000000000',
                'username' => $username
            ]);
        } catch (\Throwable $e) {
            error_log("Profile load DB error: " . $e->getMessage());
        }

        if (!$userDb) {
            $userDb = [
                'id' => $userId,
                'nama_lengkap' => Auth::name(),
                'nama_pengguna' => $username,
                'peran' => $role,
                'status_aktif' => true,
                'dibuat_pada' => date('Y-m-d H:i:s'),
                'has_password' => false
            ];
        } else {
            $userDb['has_password'] = !empty($userDb['kata_sandi']);
            unset($userDb['kata_sandi']);
        }

        // 2. Hitung riwayat pergantian nama pengguna dalam 30 hari terakhir dari log_aktivitas
        $usedUsernameChanges = 0;
        try {
            $countRow = Database::fetchOne("
                SELECT COUNT(*) as total
                FROM public.log_aktivitas
                WHERE (pengguna_id = :id OR nama_aktor = :username)
                  AND jenis_aksi = 'ubah_nama_pengguna'
                  AND waktu_kejadian >= NOW() - INTERVAL '30 days'
            ", [
                'id' => $userDb['id'] ?: '00000000-0000-0000-0000-000000000000',
                'username' => $username
            ]);
            $usedUsernameChanges = (int)($countRow['total'] ?? 0);
        } catch (\Throwable $e) {
            error_log("Log count error: " . $e->getMessage());
        }

        $maxMonthlyChanges = 2;
        $remainingChanges = $isDeveloper ? 999 : max(0, $maxMonthlyChanges - $usedUsernameChanges);
        $canChangeUsername = $isDeveloper || ($remainingChanges > 0);

        // 3. Ambil riwayat audit log keamanan akun pengguna ini
        $auditLogs = [];
        try {
            $auditLogs = Database::fetchAll("
                SELECT id, deskripsi_aktivitas, jenis_aksi, waktu_kejadian, ip_address
                FROM public.log_aktivitas
                WHERE (pengguna_id = :id OR nama_aktor = :username)
                  AND jenis_aksi IN ('ubah_nama_pengguna', 'ubah_kata_sandi')
                ORDER BY waktu_kejadian DESC
                LIMIT 5
            ", [
                'id' => $userDb['id'] ?: '00000000-0000-0000-0000-000000000000',
                'username' => $username
            ]);
        } catch (\Throwable $e) {
            error_log("Audit log fetch error: " . $e->getMessage());
        }

        $this->view('profile.index', [
            'user' => $userDb,
            'isDeveloper' => $isDeveloper,
            'usedUsernameChanges' => $usedUsernameChanges,
            'maxMonthlyChanges' => $maxMonthlyChanges,
            'remainingChanges' => $remainingChanges,
            'canChangeUsername' => $canChangeUsername,
            'auditLogs' => $auditLogs,
            'pageTitle' => 'Profil Pengguna'
        ]);
    }

    /**
     * Update Username dengan batas kuota 2x/30 hari untuk non-developer
     */
    public function updateUsername(): void
    {
        Auth::requireLogin();

        $newUsername = strtolower(trim((string)$this->input('new_username')));
        $currentUsername = strtolower(Auth::user()['nama_pengguna'] ?? '');
        $userId = Auth::id();
        $isDeveloper = Auth::isDeveloper();

        if (empty($newUsername)) {
            Flash::error('Nama pengguna baru tidak boleh kosong.');
            $this->redirect('/profile');
            return;
        }

        // Validasi format: alphanumeric dan underscore (3 - 30 karakter)
        if (!preg_match('/^[a-z0-9_]{3,30}$/', $newUsername)) {
            Flash::error('Format nama pengguna tidak valid. Gunakan 3-30 karakter huruf kecil, angka, atau underscore.');
            $this->redirect('/profile');
            return;
        }

        if ($newUsername === $currentUsername) {
            Flash::info('Nama pengguna tidak berubah.');
            $this->redirect('/profile');
            return;
        }

        try {
            // 1. Cek apakah username baru sudah digunakan oleh akun lain
            $existing = Database::fetchOne("
                SELECT id FROM public.pengguna 
                WHERE LOWER(nama_pengguna) = LOWER(:uname) AND id != :id
                LIMIT 1
            ", [
                'uname' => $newUsername,
                'id' => $userId ?: '00000000-0000-0000-0000-000000000000'
            ]);

            if ($existing) {
                Flash::error("Nama pengguna '{$newUsername}' sudah digunakan oleh akun lain.");
                $this->redirect('/profile');
                return;
            }

            // 2. Cek kuota 2x dalam 30 hari untuk non-developer
            if (!$isDeveloper) {
                $countRow = Database::fetchOne("
                    SELECT COUNT(*) as total
                    FROM public.log_aktivitas
                    WHERE (pengguna_id = :id OR nama_aktor = :username)
                      AND jenis_aksi = 'ubah_nama_pengguna'
                      AND waktu_kejadian >= NOW() - INTERVAL '30 days'
                ", [
                    'id' => $userId ?: '00000000-0000-0000-0000-000000000000',
                    'username' => $currentUsername
                ]);
                $used = (int)($countRow['total'] ?? 0);

                if ($used >= 2) {
                    Flash::error("Batas penggantian nama pengguna telah tercapai (maksimal 2 kali dalam 30 hari). Silakan coba lagi bulan depan atau hubungi Developer.");
                    $this->redirect('/profile');
                    return;
                }
            }

            // 3. Ambil ID pengguna yang valid dari DB
            $userDb = Database::fetchOne("
                SELECT id, nama_pengguna FROM public.pengguna 
                WHERE id = :id OR LOWER(nama_pengguna) = LOWER(:old_uname)
                LIMIT 1
            ", [
                'id' => $userId ?: '00000000-0000-0000-0000-000000000000',
                'old_uname' => $currentUsername
            ]);
            $actualUid = $userDb['id'] ?? ($userId ?: null);

            // 4. Update nama_pengguna di Database
            Database::execute("
                UPDATE public.pengguna 
                SET nama_pengguna = :new_uname, diubah_pada = NOW() 
                WHERE id = :id OR LOWER(nama_pengguna) = LOWER(:old_uname)
            ", [
                'new_uname' => $newUsername,
                'id' => $userId ?: '00000000-0000-0000-0000-000000000000',
                'old_uname' => $currentUsername
            ]);

            // 5. Catat riwayat perubahan ke tabel log_aktivitas
            Database::execute("
                INSERT INTO public.log_aktivitas (
                    pengguna_id, nama_aktor, peran_aktor, sumber_aksi, 
                    kategori_aktivitas, jenis_aksi, tabel_terdampak, id_referensi,
                    deskripsi_aktivitas, data_sebelum, data_sesudah, ip_address
                ) VALUES (
                    :uid, :aktor, :peran, 'web_app',
                    'keamanan_auth', 'ubah_nama_pengguna', 'pengguna', :uid,
                    :deskripsi, :sebelum, :sesudah, :ip
                )
            ", [
                'uid' => $actualUid,
                'aktor' => Auth::name(),
                'peran' => Auth::role(),
                'deskripsi' => "Mengubah nama pengguna dari '{$currentUsername}' menjadi '{$newUsername}'",
                'sebelum' => json_encode(['nama_pengguna' => $currentUsername]),
                'sesudah' => json_encode(['nama_pengguna' => $newUsername]),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);

            // 6. Update session aktif
            $_SESSION['user']['nama_pengguna'] = $newUsername;

            Flash::success("Nama pengguna berhasil diubah menjadi '{$newUsername}'!");
            $this->redirect('/profile');

        } catch (\Throwable $e) {
            Flash::error("Gagal mengubah nama pengguna: " . $e->getMessage());
            $this->redirect('/profile');
        }
    }

    /**
     * Update Password
     */
    public function updatePassword(): void
    {
        Auth::requireLogin();

        $currentPassword = (string)$this->input('current_password');
        $newPassword = (string)$this->input('new_password');
        $confirmPassword = (string)$this->input('confirm_password');

        $userId = Auth::id();
        $username = Auth::user()['nama_pengguna'] ?? '';
        $isDeveloper = Auth::isDeveloper();

        if (empty($newPassword) || empty($confirmPassword)) {
            Flash::error('Kata sandi baru dan konfirmasi wajib diisi.');
            $this->redirect('/profile');
            return;
        }

        if (strlen($newPassword) < 6) {
            Flash::error('Kata sandi baru minimal 6 karakter.');
            $this->redirect('/profile');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            Flash::error('Konfirmasi kata sandi baru tidak cocok.');
            $this->redirect('/profile');
            return;
        }

        try {
            // Cek kata sandi lama jika tersimpan di database dan bukan developer bypass
            $userDb = Database::fetchOne("
                SELECT id, kata_sandi FROM public.pengguna
                WHERE id = :id OR LOWER(nama_pengguna) = LOWER(:username)
                LIMIT 1
            ", [
                'id' => $userId ?: '00000000-0000-0000-0000-000000000000',
                'username' => $username
            ]);

            $actualUid = $userDb['id'] ?? ($userId ?: null);

            if ($userDb && !empty($userDb['kata_sandi']) && !$isDeveloper) {
                $isOldMatch = password_verify($currentPassword, $userDb['kata_sandi']) || ($currentPassword === $userDb['kata_sandi']);
                if (!$isOldMatch) {
                    Flash::error('Kata sandi saat ini tidak sesuai.');
                    $this->redirect('/profile');
                    return;
                }
            }

            // Hash kata sandi baru dengan Bcrypt
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

            // Update ke database
            Database::execute("
                UPDATE public.pengguna 
                SET kata_sandi = :pw, diubah_pada = NOW() 
                WHERE id = :id OR LOWER(nama_pengguna) = LOWER(:username)
            ", [
                'pw' => $hashedPassword,
                'id' => $userId ?: '00000000-0000-0000-0000-000000000000',
                'username' => $username
            ]);

            // Catat log aktivitas
            Database::execute("
                INSERT INTO public.log_aktivitas (
                    pengguna_id, nama_aktor, peran_aktor, sumber_aksi, 
                    kategori_aktivitas, jenis_aksi, tabel_terdampak, id_referensi,
                    deskripsi_aktivitas, ip_address
                ) VALUES (
                    :uid, :aktor, :peran, 'web_app',
                    'keamanan_auth', 'ubah_kata_sandi', 'pengguna', :uid,
                    'Memperbarui kata sandi akun', :ip
                )
            ", [
                'uid' => $actualUid,
                'aktor' => Auth::name(),
                'peran' => Auth::role(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);

            Flash::success('Kata sandi berhasil diperbarui!');
            $this->redirect('/profile');

        } catch (\Throwable $e) {
            Flash::error('Gagal memperbarui kata sandi: ' . $e->getMessage());
            $this->redirect('/profile');
        }
    }
}

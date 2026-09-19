<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\Flash;
use App\Helpers\ActivityLog;
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
                SELECT p.id, p.nama_lengkap, p.nama_pengguna, p.kata_sandi, pr.nama_peran as peran, 
                       p.status_aktif, p.dibuat_pada, p.diubah_pada,
                       p.nik, p.posisi, p.nomor_telepon, p.alamat, p.tanggal_bergabung,
                       p.bank_nama, p.bank_nomor_rekening, p.bank_atas_nama,
                       p.nomor_polisi_kendaraan, p.nomor_whatsapp, p.id_telegram,
                       k.id as karyawan_id, k.tipe_penggajian
                FROM public.pengguna p
                LEFT JOIN public.peran pr ON p.peran_id = pr.id
                LEFT JOIN public.karyawan k ON k.pengguna_id = p.id
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
     * Unified Update: Menyimpan Perubahan Profil, Username, dan/atau Kata Sandi Sekaligus
     */
    public function update(): void
    {
        Auth::requireLogin();

        if (!$this->validateCsrf()) {
            $this->redirect('/profile');
            return;
        }

        $userId = Auth::id();
        $isDeveloper = Auth::isDeveloper();
        $currentUsername = strtolower(Auth::user()['nama_pengguna'] ?? '');
        $currentNamaLengkap = trim(Auth::name() ?: (Auth::user()['nama_lengkap'] ?? ''));

        // 1. Nama Lengkap: Non-developer terkunci mengikuti Master Karyawan (HRD)
        $namaLengkap = $isDeveloper ? trim((string)$this->input('nama_lengkap')) : $currentNamaLengkap;
        if (empty($namaLengkap)) {
            $namaLengkap = $currentNamaLengkap;
        }

        // 2. Nama Pengguna (Username)
        $newUsername = strtolower(trim((string)$this->input('new_username')));
        if (empty($newUsername)) {
            $newUsername = $currentUsername;
        }

        // 3. Password (Opsional)
        $currentPassword = (string)$this->input('current_password');
        $newPassword = (string)$this->input('new_password');
        $confirmPassword = (string)$this->input('confirm_password');

        $isNameChanged = ($isDeveloper && $namaLengkap !== $currentNamaLengkap);
        $isUsernameChanged = ($newUsername !== $currentUsername);
        $isPasswordChanged = (!empty($newPassword) || !empty($confirmPassword));

        // Cek jika tidak ada perubahan sama sekali
        if (!$isNameChanged && !$isUsernameChanged && !$isPasswordChanged) {
            Flash::info('Tidak ada perubahan pada data profil atau kata sandi yang disimpan.');
            $this->redirect('/profile');
            return;
        }

        // Validasi format Username jika diubah
        if ($isUsernameChanged) {
            if (!preg_match('/^[a-z0-9_]{3,30}$/', $newUsername)) {
                Flash::error('Format nama pengguna tidak valid. Gunakan 3-30 karakter huruf kecil, angka, atau underscore.');
                $this->redirect('/profile');
                return;
            }
        }

        // Validasi Password jika diisi
        if ($isPasswordChanged) {
            if (empty($newPassword) || empty($confirmPassword)) {
                Flash::error('Kata sandi baru dan konfirmasi kata sandi wajib diisi jika ingin memperbarui kata sandi.');
                $this->redirect('/profile');
                return;
            }

            if (strlen($newPassword) < 6) {
                Flash::error('Kata sandi baru minimal 6 karakter.');
                $this->redirect('/profile');
                return;
            }

            if ($newPassword !== $confirmPassword) {
                Flash::error('Konfirmasi kata sandi baru tidak cocok dengan kata sandi baru.');
                $this->redirect('/profile');
                return;
            }
        }

        try {
            // Ambil data pengguna dari database
            $userDb = Database::fetchOne("
                SELECT id, nama_pengguna, nama_lengkap, kata_sandi FROM public.pengguna 
                WHERE id = :id OR LOWER(nama_pengguna) = LOWER(:old_uname)
                LIMIT 1
            ", [
                'id' => $userId ?: '00000000-0000-0000-0000-000000000000',
                'old_uname' => $currentUsername
            ]);
            $actualUid = $userDb['id'] ?? ($userId ?: null);

            // Validasi Keunikan & Kuota Username
            if ($isUsernameChanged) {
                $existing = Database::fetchOne("
                    SELECT id FROM public.pengguna 
                    WHERE LOWER(nama_pengguna) = LOWER(:uname) AND id != :id
                    LIMIT 1
                ", [
                    'uname' => $newUsername,
                    'id' => $actualUid ?: '00000000-0000-0000-0000-000000000000'
                ]);

                if ($existing) {
                    Flash::error("Nama pengguna '{$newUsername}' sudah digunakan oleh akun lain.");
                    $this->redirect('/profile');
                    return;
                }

                if (!$isDeveloper) {
                    $countRow = Database::fetchOne("
                        SELECT COUNT(*) as total
                        FROM public.log_aktivitas
                        WHERE (pengguna_id = :id OR nama_aktor = :username)
                          AND jenis_aksi = 'ubah_nama_pengguna'
                          AND waktu_kejadian >= NOW() - INTERVAL '30 days'
                    ", [
                        'id' => $actualUid ?: '00000000-0000-0000-0000-000000000000',
                        'username' => $currentUsername
                    ]);
                    $used = (int)($countRow['total'] ?? 0);

                    if ($used >= 2) {
                        Flash::error("Batas penggantian nama pengguna telah tercapai (maksimal 2 kali dalam 30 hari). Silakan coba lagi bulan depan atau hubungi Developer.");
                        $this->redirect('/profile');
                        return;
                    }
                }
            }

            // Validasi Password Lama
            $newPasswordHash = null;
            if ($isPasswordChanged) {
                if ($userDb && !empty($userDb['kata_sandi']) && !$isDeveloper) {
                    $isOldMatch = password_verify($currentPassword, $userDb['kata_sandi']) || ($currentPassword === $userDb['kata_sandi']);
                    if (!$isOldMatch) {
                        Flash::error('Kata sandi saat ini tidak sesuai.');
                        $this->redirect('/profile');
                        return;
                    }
                }
                $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            }

            // Eksekusi Update ke Database
            if ($newPasswordHash !== null) {
                Database::execute("
                    UPDATE public.pengguna 
                    SET nama_lengkap = :nama_lengkap,
                        nama_pengguna = :new_uname,
                        kata_sandi = :pw,
                        diubah_pada = NOW() 
                    WHERE id = :id OR LOWER(nama_pengguna) = LOWER(:old_uname)
                ", [
                    'nama_lengkap' => $namaLengkap,
                    'new_uname' => $newUsername,
                    'pw' => $newPasswordHash,
                    'id' => $actualUid ?: '00000000-0000-0000-0000-000000000000',
                    'old_uname' => $currentUsername
                ]);
            } else {
                Database::execute("
                    UPDATE public.pengguna 
                    SET nama_lengkap = :nama_lengkap,
                        nama_pengguna = :new_uname,
                        diubah_pada = NOW() 
                    WHERE id = :id OR LOWER(nama_pengguna) = LOWER(:old_uname)
                ", [
                    'nama_lengkap' => $namaLengkap,
                    'new_uname' => $newUsername,
                    'id' => $actualUid ?: '00000000-0000-0000-0000-000000000000',
                    'old_uname' => $currentUsername
                ]);
            }

            // Catat Log Aktivitas
            if ($isUsernameChanged) {
                ActivityLog::log(
                    'keamanan_auth',
                    'ubah_nama_pengguna',
                    "Mengubah nama pengguna dari '{$currentUsername}' menjadi '{$newUsername}'",
                    'pengguna',
                    $actualUid,
                    ['nama_pengguna' => $currentUsername],
                    ['nama_pengguna' => $newUsername],
                    'web_app',
                    $actualUid,
                    $namaLengkap,
                    Auth::role()
                );
            }

            if ($isPasswordChanged) {
                ActivityLog::log(
                    'keamanan_auth',
                    'ubah_kata_sandi',
                    'Memperbarui kata sandi akun',
                    'pengguna',
                    $actualUid,
                    null,
                    ['kata_sandi_diubah' => true],
                    'web_app',
                    $actualUid,
                    $namaLengkap,
                    Auth::role()
                );
            }

            if ($isNameChanged && !$isUsernameChanged) {
                ActivityLog::log(
                    'keamanan_auth',
                    'ubah_profil',
                    "Mengubah nama profil dari '{$currentNamaLengkap}' menjadi '{$namaLengkap}'",
                    'pengguna',
                    $actualUid,
                    ['nama_lengkap' => $currentNamaLengkap],
                    ['nama_lengkap' => $namaLengkap],
                    'web_app',
                    $actualUid,
                    $namaLengkap,
                    Auth::role()
                );
            }

            // Update Sesi Aktif
            $_SESSION['user']['nama_lengkap'] = $namaLengkap;
            $_SESSION['user']['nama_pengguna'] = $newUsername;

            // Flash Message Informatif
            if ($isUsernameChanged && $isPasswordChanged) {
                Flash::success("Nama pengguna (@{$newUsername}) dan kata sandi berhasil diperbarui!");
            } elseif ($isUsernameChanged) {
                Flash::success("Nama pengguna berhasil diubah menjadi '@{$newUsername}'!");
            } elseif ($isPasswordChanged) {
                Flash::success("Kata sandi berhasil diperbarui!");
            } else {
                Flash::success("Data profil berhasil diperbarui!");
            }

            $this->redirect('/profile');

        } catch (\Throwable $e) {
            Flash::error("Gagal memperbarui pengaturan profil: " . $e->getMessage());
            $this->redirect('/profile');
        }
    }

    /**
     * Backward-compatible handler untuk update username
     */
    public function updateUsername(): void
    {
        $this->update();
    }

    /**
     * Backward-compatible handler untuk update password
     */
    public function updatePassword(): void
    {
        $this->update();
    }
}

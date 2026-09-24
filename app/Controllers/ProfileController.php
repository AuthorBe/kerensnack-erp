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
        $activeTab = in_array($_GET['tab'] ?? '', ['karyawan', 'keamanan'], true) ? $_GET['tab'] : 'karyawan';

        // 1. Ambil data pengguna & karyawan terbaru dari Database
        $userDb = null;
        try {
            $userDb = Database::fetchOne("
                SELECT p.id, p.nama_lengkap, p.nama_panggilan, p.jenis_kelamin, p.tanggal_lahir, p.nama_pengguna, p.kata_sandi, pr.nama_peran as peran, 
                       p.status_aktif, p.dibuat_pada, p.diubah_pada,
                       p.nik, p.nik_pending, p.posisi, p.alamat, p.tanggal_bergabung,
                       p.bank_nama, p.bank_nomor_rekening, p.bank_atas_nama,
                       p.nomor_polisi_kendaraan, COALESCE(p.nomor_whatsapp, p.nomor_telepon) AS nomor_whatsapp, p.id_telegram,
                       k.id as karyawan_id, k.tipe_penggajian,
                       COALESCE(k.gaji_pokok_bulanan, 0) as gaji_pokok_bulanan,
                       COALESCE(k.uang_kehadiran_harian, 0) as uang_kehadiran_harian,
                       COALESCE(k.tunjangan_bulanan, 0) as tunjangan_bulanan
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
                'nama_panggilan' => null,
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

        // 2. Hitung riwayat pergantian nama pengguna (username) dalam 30 hari terakhir (maks 2x / 30 hari)
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

        // 3. Hitung riwayat pergantian Nama Karyawan (Nama Lengkap & Panggilan) dalam 90 hari terakhir (maks 1x / 3 bulan)
        $usedNameChanges = 0;
        $lastFullNameChangeTime = null;
        try {
            $lastLog = Database::fetchOne("
                SELECT waktu_kejadian
                FROM public.log_aktivitas
                WHERE (pengguna_id = :id OR nama_aktor = :username)
                  AND jenis_aksi = 'ubah_nama_karyawan'
                  AND waktu_kejadian >= NOW() - INTERVAL '90 days'
                ORDER BY waktu_kejadian DESC
                LIMIT 1
            ", [
                'id' => $userDb['id'] ?: '00000000-0000-0000-0000-000000000000',
                'username' => $username
            ]);
            if ($lastLog) {
                $usedNameChanges = 1;
                $lastFullNameChangeTime = $lastLog['waktu_kejadian'];
            }
        } catch (\Throwable $e) {
            error_log("Name change count error: " . $e->getMessage());
        }

        $canChangeName = $isDeveloper || ($usedNameChanges === 0);
        $nextAllowedNameDate = null;
        if (!$canChangeName && $lastFullNameChangeTime) {
            $nextAllowedNameDate = date('d M Y', strtotime($lastFullNameChangeTime . ' +90 days'));
        }

        // 4. Ambil riwayat audit log keamanan akun & profil pengguna ini
        $auditLogs = [];
        try {
            $auditLogs = Database::fetchAll("
                SELECT id, deskripsi_aktivitas, jenis_aksi, waktu_kejadian, ip_address
                FROM public.log_aktivitas
                WHERE (pengguna_id = :id OR nama_aktor = :username)
                  AND jenis_aksi IN ('ubah_nama_pengguna', 'ubah_kata_sandi', 'ubah_data_karyawan', 'ubah_nama_karyawan', 'ubah_profil')
                ORDER BY waktu_kejadian DESC
                LIMIT 8
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
            'canChangeName' => $canChangeName,
            'usedNameChanges' => $usedNameChanges,
            'nextAllowedNameDate' => $nextAllowedNameDate,
            'auditLogs' => $auditLogs,
            'activeTab' => $activeTab,
            'pageTitle' => 'Profil & Akun Pengguna'
        ]);
    }

    /**
     * Memperbarui Data Profil Karyawan (Nama Lengkap, Nama Panggilan, Alamat, Kontak WA, ID Telegram, Rekening Bank, Nopol)
     */
    public function updateEmployee(): void
    {
        Auth::requireLogin();

        if (!$this->validateCsrf()) {
            $this->redirect('/profile?tab=karyawan');
            return;
        }

        $userId = Auth::id();
        $isDeveloper = Auth::isDeveloper();
        $username = Auth::user()['nama_pengguna'] ?? '';

        try {
            $userDb = Database::fetchOne("
                SELECT id, nama_lengkap, nama_panggilan, nama_pengguna, alamat, 
                       COALESCE(nomor_whatsapp, nomor_telepon) AS nomor_whatsapp, id_telegram,
                       bank_nama, bank_nomor_rekening, bank_atas_nama, nomor_polisi_kendaraan
                FROM public.pengguna 
                WHERE id = :id OR LOWER(nama_pengguna) = LOWER(:old_uname)
                LIMIT 1
            ", [
                'id' => $userId ?: '00000000-0000-0000-0000-000000000000',
                'old_uname' => $username
            ]);

            if (!$userDb) {
                Flash::error('Data pengguna tidak ditemukan.');
                $this->redirect('/profile?tab=karyawan');
                return;
            }

            $actualUid = $userDb['id'];

            // Sanitasi input form karyawan
            $namaLengkap = trim((string)$this->input('nama_lengkap', ''));
            $namaPanggilan = trim((string)$this->input('nama_panggilan', ''));
            $alamat = trim((string)$this->input('alamat', ''));
            $nomorWhatsapp = trim((string)$this->input('nomor_whatsapp', ''));
            $idTelegramRaw = trim((string)$this->input('id_telegram', ''));
            $idTelegram = (!empty($idTelegramRaw) && is_numeric($idTelegramRaw)) ? (int)$idTelegramRaw : null;
            $bankNama = trim((string)$this->input('bank_nama', ''));
            $bankNomorRekening = trim((string)$this->input('bank_nomor_rekening', ''));
            $bankAtasNama = trim((string)$this->input('bank_atas_nama', ''));
            $nomorPolisi = strtoupper(trim((string)$this->input('nomor_polisi_kendaraan', '')));

            // Validasi wajib isi (Required fields)
            if (empty($namaLengkap)) {
                Flash::error('Nama Lengkap wajib diisi.');
                $this->redirect('/profile?tab=karyawan');
                return;
            }

            if (empty($namaPanggilan)) {
                Flash::error('Nama Panggilan wajib diisi.');
                $this->redirect('/profile?tab=karyawan');
                return;
            }

            // Cek perubahan nama
            $oldNamaLengkap = trim((string)($userDb['nama_lengkap'] ?? ''));
            $oldNamaPanggilan = trim((string)($userDb['nama_panggilan'] ?? ''));
            $isNameChanged = ($namaLengkap !== $oldNamaLengkap) || ($namaPanggilan !== $oldNamaPanggilan);

            // Validasi Kuota Ganti Nama (Maksimal 1x per 3 bulan / 90 hari untuk Non-Developer)
            if ($isNameChanged && !$isDeveloper) {
                $lastLog = Database::fetchOne("
                    SELECT waktu_kejadian
                    FROM public.log_aktivitas
                    WHERE (pengguna_id = :id OR nama_aktor = :username)
                      AND jenis_aksi = 'ubah_nama_karyawan'
                      AND waktu_kejadian >= NOW() - INTERVAL '90 days'
                    ORDER BY waktu_kejadian DESC
                    LIMIT 1
                ", [
                    'id' => $actualUid,
                    'username' => $username
                ]);

                if ($lastLog) {
                    $nextDate = date('d M Y', strtotime($lastLog['waktu_kejadian'] . ' +90 days'));
                    Flash::error("Batas penggantian nama telah tercapai (maksimal 1 kali dalam 3 bulan). Anda baru dapat mengubah nama kembali pada {$nextDate}.");
                    $this->redirect('/profile?tab=karyawan');
                    return;
                }
            }

            // Cek perubahan data lainnya
            $oldAlamat = trim((string)($userDb['alamat'] ?? ''));
            $oldWhatsapp = trim((string)($userDb['nomor_whatsapp'] ?? ''));
            $oldTelegram = !empty($userDb['id_telegram']) ? (int)$userDb['id_telegram'] : null;
            $oldBankNama = trim((string)($userDb['bank_nama'] ?? ''));
            $oldBankRek = trim((string)($userDb['bank_nomor_rekening'] ?? ''));
            $oldBankAn = trim((string)($userDb['bank_atas_nama'] ?? ''));
            $oldNopol = trim((string)($userDb['nomor_polisi_kendaraan'] ?? ''));

            $isDataChanged = ($alamat !== $oldAlamat)
                || ($nomorWhatsapp !== $oldWhatsapp)
                || ($idTelegram !== $oldTelegram)
                || ($bankNama !== $oldBankNama)
                || ($bankNomorRekening !== $oldBankRek)
                || ($bankAtasNama !== $oldBankAn)
                || ($nomorPolisi !== $oldNopol);

            if (!$isNameChanged && !$isDataChanged) {
                Flash::info('Tidak ada perubahan pada data profil karyawan yang disimpan.');
                $this->redirect('/profile?tab=karyawan');
                return;
            }

            // Eksekusi Update ke Database
            Database::execute("
                UPDATE public.pengguna 
                SET nama_lengkap = :nama_lengkap,
                    nama_panggilan = :nama_panggilan,
                    alamat = :alamat,
                    nomor_whatsapp = :nomor_whatsapp,
                    nomor_telepon = :nomor_whatsapp,
                    id_telegram = :id_telegram,
                    bank_nama = :bank_nama,
                    bank_nomor_rekening = :bank_nomor_rekening,
                    bank_atas_nama = :bank_atas_nama,
                    nomor_polisi_kendaraan = :nomor_polisi_kendaraan,
                    diubah_pada = NOW() 
                WHERE id = :id
            ", [
                'nama_lengkap' => $namaLengkap,
                'nama_panggilan' => $namaPanggilan,
                'alamat' => $alamat ?: null,
                'nomor_whatsapp' => $nomorWhatsapp ?: null,
                'id_telegram' => $idTelegram,
                'bank_nama' => $bankNama ?: null,
                'bank_nomor_rekening' => $bankNomorRekening ?: null,
                'bank_atas_nama' => $bankAtasNama ?: null,
                'nomor_polisi_kendaraan' => $nomorPolisi ?: null,
                'id' => $actualUid
            ]);

            // Catat Log Aktivitas Audit jika nama berubah
            if ($isNameChanged) {
                ActivityLog::log(
                    'hr_payroll',
                    'ubah_nama_karyawan',
                    "Mengubah nama karyawan dari '{$oldNamaLengkap}' (" . ($oldNamaPanggilan ?: '-') . ") menjadi '{$namaLengkap}' ({$namaPanggilan})",
                    'pengguna',
                    $actualUid,
                    ['nama_lengkap' => $oldNamaLengkap, 'nama_panggilan' => $oldNamaPanggilan],
                    ['nama_lengkap' => $namaLengkap, 'nama_panggilan' => $namaPanggilan],
                    'web_app',
                    $actualUid,
                    $namaLengkap,
                    Auth::role()
                );
            }

            // Catat Log Aktivitas Audit jika data kontak/rekening/alamat/nopol berubah
            if ($isDataChanged) {
                $oldData = [
                    'alamat' => $oldAlamat,
                    'nomor_whatsapp' => $oldWhatsapp,
                    'bank_nomor_rekening' => $oldBankRek,
                    'nomor_polisi_kendaraan' => $oldNopol
                ];
                $newData = [
                    'alamat' => $alamat,
                    'nomor_whatsapp' => $nomorWhatsapp,
                    'bank_nomor_rekening' => $bankNomorRekening,
                    'nomor_polisi_kendaraan' => $nomorPolisi
                ];

                ActivityLog::log(
                    'hr_payroll',
                    'ubah_data_karyawan',
                    "Memperbarui data kontak/domisili/rekening karyawan",
                    'pengguna',
                    $actualUid,
                    $oldData,
                    $newData,
                    'web_app',
                    $actualUid,
                    $namaLengkap,
                    Auth::role()
                );
            }

            // Perbarui data nama dan kontak di sesi
            $_SESSION['user']['nama_lengkap'] = $namaLengkap;
            $_SESSION['user']['nama_panggilan'] = $namaPanggilan;
            $_SESSION['user']['nomor_whatsapp'] = $nomorWhatsapp;

            if ($isNameChanged && $isDataChanged) {
                Flash::success('Nama dan data profil karyawan berhasil diperbarui!');
            } elseif ($isNameChanged) {
                Flash::success('Nama karyawan berhasil diperbarui!');
            } else {
                Flash::success('Data profil karyawan berhasil diperbarui!');
            }

            $this->redirect('/profile?tab=karyawan');

        } catch (\Throwable $e) {
            Flash::error('Gagal memperbarui data karyawan: ' . $e->getMessage());
            $this->redirect('/profile?tab=karyawan');
        }
    }

    /**
     * Unified Update: Menyimpan Perubahan Kredensial Username dan/atau Kata Sandi Akun
     */
    public function update(): void
    {
        Auth::requireLogin();

        if (!$this->validateCsrf()) {
            $this->redirect('/profile?tab=keamanan');
            return;
        }

        $userId = Auth::id();
        $isDeveloper = Auth::isDeveloper();
        $currentUsername = strtolower(Auth::user()['nama_pengguna'] ?? '');

        // 1. Nama Pengguna (Username)
        $newUsername = strtolower(trim((string)$this->input('new_username')));
        if (empty($newUsername)) {
            $newUsername = $currentUsername;
        }

        // 2. Password (Opsional)
        $currentPassword = (string)$this->input('current_password');
        $newPassword = (string)$this->input('new_password');
        $confirmPassword = (string)$this->input('confirm_password');

        $isUsernameChanged = ($newUsername !== $currentUsername);
        $isPasswordChanged = (!empty($newPassword) || !empty($confirmPassword));

        // Cek jika tidak ada perubahan sama sekali
        if (!$isUsernameChanged && !$isPasswordChanged) {
            Flash::info('Tidak ada perubahan pada pengaturan akun atau kata sandi yang disimpan.');
            $this->redirect('/profile?tab=keamanan');
            return;
        }

        // Validasi format Username jika diubah
        if ($isUsernameChanged) {
            if (!preg_match('/^[a-z0-9_]{3,30}$/', $newUsername)) {
                Flash::error('Format nama pengguna tidak valid. Gunakan 3-30 karakter huruf kecil, angka, atau underscore.');
                $this->redirect('/profile?tab=keamanan');
                return;
            }
        }

        // Validasi Password jika diisi
        if ($isPasswordChanged) {
            if (empty($newPassword) || empty($confirmPassword)) {
                Flash::error('Kata sandi baru dan konfirmasi kata sandi wajib diisi jika ingin memperbarui kata sandi.');
                $this->redirect('/profile?tab=keamanan');
                return;
            }

            if (strlen($newPassword) < 6) {
                Flash::error('Kata sandi baru minimal 6 karakter.');
                $this->redirect('/profile?tab=keamanan');
                return;
            }

            if ($newPassword !== $confirmPassword) {
                Flash::error('Konfirmasi kata sandi baru tidak cocok dengan kata sandi baru.');
                $this->redirect('/profile?tab=keamanan');
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
            $namaLengkap = $userDb['nama_lengkap'] ?? Auth::name();

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
                    $this->redirect('/profile?tab=keamanan');
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
                        $this->redirect('/profile?tab=keamanan');
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
                        $this->redirect('/profile?tab=keamanan');
                        return;
                    }
                }
                $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            }

            // Eksekusi Update ke Database
            if ($newPasswordHash !== null) {
                Database::execute("
                    UPDATE public.pengguna 
                    SET nama_pengguna = :new_uname,
                        kata_sandi = :pw,
                        diubah_pada = NOW() 
                    WHERE id = :id OR LOWER(nama_pengguna) = LOWER(:old_uname)
                ", [
                    'new_uname' => $newUsername,
                    'pw' => $newPasswordHash,
                    'id' => $actualUid ?: '00000000-0000-0000-0000-000000000000',
                    'old_uname' => $currentUsername
                ]);
            } else {
                Database::execute("
                    UPDATE public.pengguna 
                    SET nama_pengguna = :new_uname,
                        diubah_pada = NOW() 
                    WHERE id = :id OR LOWER(nama_pengguna) = LOWER(:old_uname)
                ", [
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

            // Update Sesi Aktif
            $_SESSION['user']['nama_pengguna'] = $newUsername;

            // Flash Message Informatif
            if ($isUsernameChanged && $isPasswordChanged) {
                Flash::success("Nama pengguna (@{$newUsername}) dan kata sandi berhasil diperbarui!");
            } elseif ($isUsernameChanged) {
                Flash::success("Nama pengguna berhasil diubah menjadi '@{$newUsername}'!");
            } elseif ($isPasswordChanged) {
                Flash::success("Kata sandi berhasil diperbarui!");
            } else {
                Flash::success("Pengaturan akun berhasil diperbarui!");
            }

            $this->redirect('/profile?tab=keamanan');

        } catch (\Throwable $e) {
            Flash::error("Gagal memperbarui pengaturan akun: " . $e->getMessage());
            $this->redirect('/profile?tab=keamanan');
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

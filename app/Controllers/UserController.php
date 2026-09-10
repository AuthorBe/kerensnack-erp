<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
use App\Helpers\Flash;
use Database;
use Throwable;

/**
 * app/Controllers/UserController.php
 * Pengendali Manajemen Akun Pengguna Sistem (User Management).
 * Arsitektur Single-Page dengan Dialog Modal Tambah / Edit M3.
 */
class UserController extends Controller
{
    public function index(): void
    {
        Auth::requirePermission(['rbac.users_view', 'rbac.users_manage']);

        $search = trim((string)$this->input('search', ''));
        $roleFilter = trim((string)$this->input('role_filter', ''));
        $statusFilter = $this->input('status_filter', '');

        // Ambil daftar peran untuk filter & dropdown modal
        $roles = Database::fetchAll("SELECT id, nama_peran, deskripsi FROM public.peran ORDER BY nama_peran ASC");

        // Ambil daftar karyawan aktif yang belum memiliki akun login sistem
        $availableEmployees = Database::fetchAll("
            SELECT id, nama_lengkap, posisi, nomor_whatsapp, nik
            FROM public.pengguna
            WHERE nama_pengguna IS NULL AND status_aktif = TRUE
            ORDER BY nama_lengkap ASC
        ");

        $sql = "
            SELECT p.id, p.nama_lengkap, p.nama_pengguna, p.id_telegram, p.nomor_whatsapp, 
                   p.status_aktif, p.dibuat_pada,
                   pr.id as peran_id, pr.nama_peran as peran,
                   p.posisi as posisi_karyawan
            FROM public.pengguna p
            JOIN public.peran pr ON p.peran_id = pr.id
            WHERE 1=1
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (p.nama_lengkap ILIKE :search OR p.nama_pengguna ILIKE :search OR p.posisi ILIKE :search)";
            $params['search'] = "%{$search}%";
        }

        if ($roleFilter !== '') {
            $sql .= " AND pr.nama_peran = :role";
            $params['role'] = $roleFilter;
        }

        if ($statusFilter !== '' && $statusFilter !== null) {
            $sql .= " AND p.status_aktif = :status";
            $params['status'] = ($statusFilter === '1' || $statusFilter === 'true');
        }

        $sql .= " ORDER BY p.dibuat_pada DESC, p.nama_lengkap ASC";
        $users = Database::fetchAll($sql, $params);

        $this->view('settings.users.index', [
            'pageTitle' => 'Manajemen Pengguna',
            'pageSubtitle' => 'Kelola Akun Pengguna, Kredensial, Tautan Pegawai & Status Akses Sistem',
            'users' => $users,
            'roles' => $roles,
            'availableEmployees' => $availableEmployees,
            'employees' => $availableEmployees,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'statusFilter' => $statusFilter,
            'canManage' => Auth::can('rbac.users_manage'),
        ]);
    }

    public function create(): void
    {
        $this->redirect('/users');
    }

    public function store(): void
    {
        Auth::requirePermission('rbac.users_manage');

        $penggunaId = trim((string)$this->input('pengguna_id'));
        $username = strtolower(trim((string)$this->input('nama_pengguna')));
        $password = (string)$this->input('password');
        $peranId = trim((string)$this->input('peran_id'));
        $nomorWa = trim((string)$this->input('nomor_whatsapp')) ?: null;
        $idTelegram = trim((string)$this->input('id_telegram')) ?: null;

        if (empty($penggunaId) || empty($username) || empty($password) || empty($peranId)) {
            Flash::danger('Mohon pilih Karyawan, lengkapi Username, Kata Sandi, dan Peran Jabatan.');
            $this->redirect('/users');
            return;
        }

        // Ambil data karyawan yang dipilih
        $emp = Database::fetchOne("SELECT id, nama_lengkap, nama_pengguna, nomor_whatsapp FROM public.pengguna WHERE id = :id", ['id' => $penggunaId]);
        if (!$emp) {
            Flash::danger('Data karyawan tidak ditemukan.');
            $this->redirect('/users');
            return;
        }

        if (!empty($emp['nama_pengguna'])) {
            Flash::danger("Karyawan {$emp['nama_lengkap']} sudah memiliki akun login sistem (@{$emp['nama_pengguna']}).");
            $this->redirect('/users');
            return;
        }

        // Proteksi 4: Larang pembuatan/pemberian akun developer baru
        $targetRole = Database::fetchOne("SELECT nama_peran FROM public.peran WHERE id = :id", ['id' => $peranId]);
        if (($targetRole['nama_peran'] ?? '') === 'developer') {
            Flash::danger('Akses Ditolak: Tidak diperbolehkan membuat akun Developer baru (Single Root Account).');
            $this->redirect('/users');
            return;
        }

        // Cek username unik (case-insensitive)
        $existing = Database::fetchOne("SELECT id FROM public.pengguna WHERE LOWER(nama_pengguna) = :username", ['username' => $username]);
        if ($existing) {
            Flash::danger("Username '{$username}' sudah digunakan. Silakan pilih username lain.");
            $this->redirect('/users');
            return;
        }

        try {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            Database::execute("
                UPDATE public.pengguna 
                SET nama_pengguna = :username,
                    kata_sandi = :pass,
                    peran_id = :peran_id,
                    nomor_whatsapp = COALESCE(:wa, nomor_whatsapp),
                    id_telegram = COALESCE(:telegram, id_telegram),
                    status_aktif = TRUE,
                    diubah_pada = NOW()
                WHERE id = :id
            ", [
                'id' => $penggunaId,
                'username' => $username,
                'pass' => $hashedPassword,
                'peran_id' => $peranId,
                'wa' => $nomorWa,
                'telegram' => $idTelegram ? (int)$idTelegram : null,
            ]);

            Auth::touchPermissionsCache();
            ActivityLog::record(
                Auth::id(),
                'CREATE_USER',
                "Mengaktifkan akun login sistem untuk karyawan: {$emp['nama_lengkap']} (@{$username})"
            );

            Flash::success("Akun login @{$username} untuk {$emp['nama_lengkap']} berhasil diaktifkan.");
        } catch (Throwable $e) {
            Flash::danger('Gagal mengaktifkan akun pengguna: ' . $e->getMessage());
        }

        $this->redirect('/users');
    }

    public function edit(): void
    {
        $this->redirect('/users');
    }

    public function update(): void
    {
        Auth::requirePermission('rbac.users_manage');

        $id = trim((string)$this->input('id'));
        $namaLengkap = trim((string)$this->input('nama_lengkap'));
        $username = strtolower(trim((string)$this->input('nama_pengguna')));
        $password = (string)$this->input('password');
        $peranId = trim((string)$this->input('peran_id'));
        $idTelegram = trim((string)$this->input('id_telegram'));
        $idTelegram = ($idTelegram !== '' && is_numeric($idTelegram)) ? (int)$idTelegram : null;
        $statusAktif = isset($_POST['status_aktif']) && in_array($_POST['status_aktif'], [1, '1', 'true', true, 'on'], true);

        if (empty($id) || empty($namaLengkap) || empty($username)) {
            Flash::danger('Mohon lengkapi seluruh kolom wajib.');
            $this->redirect('/users');
            return;
        }

        // Ambil data akun yang sedang diedit
        $targetUser = Database::fetchOne("
            SELECT p.id, p.nama_lengkap, p.nama_pengguna, p.peran_id, p.status_aktif, pr.nama_peran as peran 
            FROM public.pengguna p
            JOIN public.peran pr ON p.peran_id = pr.id
            WHERE p.id = :id
        ", ['id' => $id]);

        if (!$targetUser) {
            Flash::danger('Pengguna tidak ditemukan.');
            $this->redirect('/users');
            return;
        }

        $isTargetDev = ($targetUser['peran'] === 'developer');

        // Nama lengkap selalu mengikuti data master karyawan (hanya dapat diubah via modul Karyawan)
        if (!$isTargetDev) {
            $namaLengkap = $targetUser['nama_lengkap'];
        }

        // Proteksi 1: Akun developer hanya bisa diedit oleh developer itu sendiri
        if ($isTargetDev) {
            if (!Auth::isDeveloper() || Auth::id() !== $id) {
                Flash::danger('Akses Ditolak: Akun Developer hanya dapat diedit secara langsung oleh Developer itu sendiri.');
                $this->redirect('/users');
                return;
            }
            // Proteksi 2: Developer tidak ditautkan ke karyawan
            $karyawanId = null;
            // Proteksi 3: Role developer tidak dapat diubah
            $peranId = $targetUser['peran_id'];
            // Proteksi 5: Akun developer selalu aktif
            $statusAktif = true;
        } else {
            // Proteksi 4: User biasa tidak dapat diubah rolenya menjadi developer
            if (empty($peranId)) {
                Flash::danger('Peran pengguna wajib dipilih.');
                $this->redirect('/users');
                return;
            }

            $newRole = Database::fetchOne("SELECT nama_peran FROM public.peran WHERE id = :id", ['id' => $peranId]);
            if (($newRole['nama_peran'] ?? '') === 'developer') {
                Flash::danger('Akses Ditolak: Tidak dapat mengubah peran pengguna menjadi Developer.');
                $this->redirect('/users');
                return;
            }
        }

        // Cek username unik untuk id lain
        $existing = Database::fetchOne("
            SELECT id FROM public.pengguna 
            WHERE LOWER(nama_pengguna) = :username AND id != :id
        ", ['username' => $username, 'id' => $id]);

        if ($existing) {
            Flash::danger("Username '{$username}' sudah digunakan oleh pengguna lain.");
            $this->redirect('/users');
            return;
        }

        // Cek Telegram ID unik untuk akun lain (jika diisi)
        if ($idTelegram !== null) {
            $existingTg = Database::fetchOne("
                SELECT id, nama_pengguna FROM public.pengguna 
                WHERE id_telegram = :telegram AND id != :id
            ", ['telegram' => $idTelegram, 'id' => $id]);

            if ($existingTg) {
                Flash::danger("ID Telegram '{$idTelegram}' sudah digunakan oleh akun @{$existingTg['nama_pengguna']}.");
                $this->redirect('/users');
                return;
            }
        }

        try {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                Database::execute("
                    UPDATE public.pengguna 
                    SET nama_lengkap = :nama,
                        nama_pengguna = :username,
                        kata_sandi = :pass,
                        peran_id = :peran_id,
                        id_telegram = :telegram,
                        status_aktif = :status,
                        diubah_pada = NOW()
                    WHERE id = :id
                ", [
                    'id' => $id,
                    'nama' => $namaLengkap,
                    'username' => $username,
                    'pass' => $hashedPassword,
                    'peran_id' => $peranId,
                    'telegram' => $idTelegram,
                    'status' => $statusAktif,
                ]);
            } else {
                Database::execute("
                    UPDATE public.pengguna 
                    SET nama_lengkap = :nama,
                        nama_pengguna = :username,
                        peran_id = :peran_id,
                        id_telegram = :telegram,
                        status_aktif = :status,
                        diubah_pada = NOW()
                    WHERE id = :id
                ", [
                    'id' => $id,
                    'nama' => $namaLengkap,
                    'username' => $username,
                    'peran_id' => $peranId,
                    'telegram' => $idTelegram,
                    'status' => $statusAktif,
                ]);
            }

            Auth::touchPermissionsCache();
            ActivityLog::record(
                Auth::id(),
                'UPDATE_USER',
                "Memperbarui data akun pengguna @{$username}"
            );

            Flash::success("Data akun @{$username} berhasil diperbarui.");
        } catch (Throwable $e) {
            Flash::danger('Gagal memperbarui pengguna: ' . $e->getMessage());
        }

        $this->redirect('/users');
    }

    public function toggleStatus(): void
    {
        Auth::requirePermission('rbac.users_manage');

        $id = trim((string)$this->input('id'));
        if (empty($id) || $id === Auth::id()) {
            Flash::danger('Aksi tidak diizinkan pada akun Anda sendiri.');
            $this->redirect('/users');
            return;
        }

        $user = Database::fetchOne("
            SELECT p.id, p.nama_pengguna, p.status_aktif, pr.nama_peran as peran 
            FROM public.pengguna p
            JOIN public.peran pr ON p.peran_id = pr.id
            WHERE p.id = :id
        ", ['id' => $id]);

        if (!$user) {
            Flash::danger('Pengguna tidak ditemukan.');
            $this->redirect('/users');
            return;
        }

        // Proteksi 5: Akun developer tidak dapat dinonaktifkan
        if ($user['peran'] === 'developer') {
            Flash::danger('Akses Ditolak: Akun Developer adalah root sistem dan tidak dapat dinonaktifkan.');
            $this->redirect('/users');
            return;
        }

        $currentStatus = in_array($user['status_aktif'], [true, 1, '1', 't', 'true'], true);
        $newStatus = !$currentStatus;
        Database::execute("UPDATE public.pengguna SET status_aktif = :status, diubah_pada = NOW() WHERE id = :id", [
            'id' => $id,
            'status' => $newStatus,
        ]);

        Auth::touchPermissionsCache();
        $statusLabel = $newStatus ? 'diaktifkan' : 'disuspend (dinonaktifkan)';
        ActivityLog::record(Auth::id(), 'TOGGLE_USER_STATUS', "Status user @{$user['nama_pengguna']} diubah menjadi: {$statusLabel}");
        Flash::success("Akun @{$user['nama_pengguna']} berhasil {$statusLabel}.");
        $this->redirect('/users');
    }

    public function delete(): void
    {
        Auth::requirePermission('rbac.users_manage');

        $id = trim((string)$this->input('id'));
        if (empty($id) || $id === Auth::id()) {
            Flash::danger('Anda tidak dapat menghapus akun Anda sendiri.');
            $this->redirect('/users');
            return;
        }

        $user = Database::fetchOne("
            SELECT p.id, p.nama_pengguna, pr.nama_peran as peran 
            FROM public.pengguna p
            JOIN public.peran pr ON p.peran_id = pr.id
            WHERE p.id = :id
        ", ['id' => $id]);

        if (!$user) {
            Flash::danger('Pengguna tidak ditemukan.');
            $this->redirect('/users');
            return;
        }

        // Proteksi Anti-Delete Akun Developer
        if ($user['peran'] === 'developer') {
            Flash::danger('Akses Ditolak: Akun Developer dilindungi secara permanen dan tidak dapat dihapus.');
            $this->redirect('/users');
            return;
        }

        try {
            // Hapus izin kustom pengguna jika ada
            Database::execute("DELETE FROM public.izin_pengguna WHERE pengguna_id = :id", ['id' => $id]);

            // Cek apakah user terdaftar sebagai karyawan di modul HR
            $isEmployee = (bool)Database::fetchOne("SELECT id FROM public.karyawan WHERE pengguna_id = :id", ['id' => $id]);

            if ($isEmployee) {
                // Cabut akses login saja, data identitas dan riwayat penggajian karyawan tetap tersimpan utuh
                Database::execute("
                    UPDATE public.pengguna 
                    SET nama_pengguna = NULL, 
                        kata_sandi = NULL, 
                        peran_id = NULL, 
                        diubah_pada = NOW() 
                    WHERE id = :id
                ", ['id' => $id]);

                Auth::touchPermissionsCache();
                ActivityLog::record(Auth::id(), 'REVOKE_USER_ACCESS', "Mencabut akses login pengguna: @{$user['nama_pengguna']}");
                Flash::success("Akses login untuk @{$user['nama_pengguna']} berhasil dicabut. Profil karyawan tetap tersimpan aman.");
            } else {
                // Hapus pengguna murni jika bukan karyawan
                Database::execute("DELETE FROM public.pengguna WHERE id = :id", ['id' => $id]);

                Auth::touchPermissionsCache();
                ActivityLog::record(Auth::id(), 'DELETE_USER', "Menghapus akun pengguna: @{$user['nama_pengguna']}");
                Flash::success("Akun @{$user['nama_pengguna']} berhasil dihapus.");
            }
        } catch (Throwable $e) {
            Flash::danger('Gagal memproses pencabutan pengguna: ' . $e->getMessage());
        }

        $this->redirect('/users');
    }
}

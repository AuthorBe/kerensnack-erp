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
 * app/Controllers/PermissionController.php
 * Pengendali Pusat Single-Page 5-Tab Master RBAC & Loket Izin Pengguna.
 * Berdasarkan Pola Rekap-Mukholif dengan Smart 3-State Contextual Controls & Material Design 3.
 */
class PermissionController extends Controller
{
    private const PROTECTED_ROLES = ['developer', 'owner', 'admin', 'sales', 'driver'];

    public function __construct()
    {
        Auth::requireLogin();
        Auth::requirePermission('rbac.permissions_manage');
    }

    /**
     * Master Single-Page Container 5-Tab RBAC
     */
    public function index(): void
    {
        $activeTab = (string)$this->input('tab', 'user');
        $isAjax = ($this->input('ajax') === '1');
        $loggedInUserId = (string)Auth::id();

        // 1. Ambil semua izin dari database (Grup & Kode)
        $allPerms = Database::fetchAll("
            SELECT id, kode_izin, nama_izin, grup_izin, deskripsi 
            FROM public.izin 
            ORDER BY grup_izin ASC, kode_izin ASC
        ");

        $permissionsGrouped = [];
        $totalPermCount = 0;
        foreach ($allPerms as $perm) {
            $permissionsGrouped[$perm['grup_izin']][] = $perm;
            $totalPermCount++;
        }

        // 2. Ambil master Roles
        $roles = Database::fetchAll("SELECT id, nama_peran, deskripsi FROM public.peran ORDER BY nama_peran ASC");

        // 3. DATA TAB 1: Izin User
        $selectedUserId = (string)$this->input('user_id', '');
        $usersRaw = Database::fetchAll("
            SELECT u.id, u.nama_lengkap, u.nama_pengguna, u.peran_id, r.nama_peran, r.deskripsi as deskripsi_peran
            FROM public.pengguna u
            JOIN public.peran r ON u.peran_id = r.id
            WHERE u.id != :logged_id AND r.nama_peran != 'developer'
            ORDER BY r.nama_peran ASC, u.nama_lengkap ASC
        ", ['logged_id' => $loggedInUserId]);

        $groupedUsers = [];
        foreach ($usersRaw as $u) {
            $roleLabel = ucfirst($u['nama_peran']);
            $groupedUsers[$roleLabel][] = $u;
        }

        $selectedUserData = null;
        $userRolePermissions = [];
        $userOverrides = [];
        $customOverrideCount = 0;

        if (!empty($selectedUserId)) {
            $selectedUserData = Database::fetchOne("
                SELECT u.id, u.nama_lengkap, u.nama_pengguna, u.peran_id, r.nama_peran, r.deskripsi as deskripsi_peran
                FROM public.pengguna u
                JOIN public.peran r ON u.peran_id = r.id
                WHERE u.id = :id
            ", ['id' => $selectedUserId]);

            if ($selectedUserData && $selectedUserData['nama_peran'] === 'developer') {
                Flash::info('Akun Developer memiliki hak akses universal bypass dan tidak memerlukan pengaturan izin khusus.');
                $this->redirect('/permissions?tab=user');
                return;
            }

            if ($selectedUserData) {
                // Izin default dari role user
                $rolePermsRaw = Database::fetchAll("
                    SELECT izin_id FROM public.izin_peran 
                    WHERE peran_id = :peran_id AND diizinkan = TRUE
                ", ['peran_id' => $selectedUserData['peran_id']]);
                foreach ($rolePermsRaw as $rp) {
                    $userRolePermissions[] = (string)$rp['izin_id'];
                }

                // Override khusus milik user
                $userOverridesRaw = Database::fetchAll("
                    SELECT izin_id, diizinkan FROM public.izin_pengguna WHERE pengguna_id = :user_id
                ", ['user_id' => $selectedUserId]);
                foreach ($userOverridesRaw as $uo) {
                    $isAllowed = ($uo['diizinkan'] === true || $uo['diizinkan'] === 't' || $uo['diizinkan'] === 1 || $uo['diizinkan'] === '1');
                    $userOverrides[(string)$uo['izin_id']] = $isAllowed ? 1 : 0;
                }
                $customOverrideCount = count($userOverrides);
            }
        }

        // Jika request AJAX (misal onchange dropdown user di Tab 1)
        if ($isAjax) {
            $this->view('settings.izin.user_content', [
                'selectedUserData' => $selectedUserData,
                'permissions' => $permissionsGrouped,
                'rolePermissions' => $userRolePermissions,
                'userOverrides' => $userOverrides,
                'customOverrideCount' => $customOverrideCount,
                'totalPermCount' => $totalPermCount,
                'selectedUserId' => $selectedUserId,
            ]);
            exit;
        }

        // 4. DATA TAB 2: Default Role
        $selectedRoleId = (string)$this->input('role_id', '');
        // Tidak ada auto-select — biarkan dropdown kosong (placeholder) jika user belum memilih

        $selectedRole = null;
        $tabRoleActivePerms = [];
        $tabRoleActiveCount = 0;

        if (!empty($selectedRoleId)) {
            $selectedRole = Database::fetchOne("
                SELECT id, nama_peran, deskripsi FROM public.peran WHERE id = :id
            ", ['id' => $selectedRoleId]);

            if ($selectedRole) {
                $rolePermsRaw = Database::fetchAll("
                    SELECT izin_id FROM public.izin_peran WHERE peran_id = :peran_id AND diizinkan = TRUE
                ", ['peran_id' => $selectedRoleId]);
                foreach ($rolePermsRaw as $rp) {
                    $tabRoleActivePerms[] = (string)$rp['izin_id'];
                }
                $tabRoleActiveCount = count($tabRoleActivePerms);
            }
        }

        // 5. DATA TAB 3: Kelola Role
        $rolesWithStats = Database::fetchAll("
            SELECT pr.id, pr.nama_peran, pr.deskripsi, pr.dibuat_pada,
                   count(DISTINCT p.id) as total_pengguna,
                   count(DISTINCT ip.id) as total_izin
            FROM public.peran pr
            LEFT JOIN public.pengguna p ON pr.id = p.peran_id
            LEFT JOIN public.izin_peran ip ON pr.id = ip.peran_id AND ip.diizinkan = TRUE
            GROUP BY pr.id, pr.nama_peran, pr.deskripsi, pr.dibuat_pada
            ORDER BY pr.dibuat_pada ASC
        ");

        // 6. DATA TAB 3 (tambahan): Daftar user per role untuk modal
        $usersPerRoleRaw = Database::fetchAll("
            SELECT p.peran_id, p.id, p.nama_lengkap, p.nama_pengguna, p.status_aktif
            FROM public.pengguna p
            ORDER BY p.nama_lengkap ASC
        ");
        $usersPerRole = [];
        foreach ($usersPerRoleRaw as $u) {
            $rid = (string)$u['peran_id'];
            $usersPerRole[$rid][] = [
                'id'            => $u['id'],
                'nama_lengkap'  => $u['nama_lengkap'],
                'nama_pengguna' => $u['nama_pengguna'],
                'status_aktif'  => ($u['status_aktif'] === true || $u['status_aktif'] === 't' || $u['status_aktif'] === '1' || $u['status_aktif'] === 1),
            ];
        }

        // 7. DATA TAB 4: Edit Massal
        $bulkUsers = $usersRaw;

        // 7. DATA TAB 5: Matriks Audit
        $allUsersList = Database::fetchAll("SELECT id, nama_lengkap, nama_pengguna, peran_id FROM public.pengguna ORDER BY nama_lengkap ASC");
        $rolePermsMatrix = Database::fetchAll("
            SELECT ip.izin_id, r.nama_peran 
            FROM public.izin_peran ip
            JOIN public.peran r ON ip.peran_id = r.id
            WHERE ip.diizinkan = TRUE
        ");
        $rolePermMap = [];
        foreach ($rolePermsMatrix as $rp) {
            $rolePermMap[(string)$rp['izin_id']][] = $rp['nama_peran'];
        }

        $userOverridesMatrix = Database::fetchAll("
            SELECT up.izin_id, up.diizinkan, u.nama_pengguna 
            FROM public.izin_pengguna up
            JOIN public.pengguna u ON up.pengguna_id = u.id
        ");
        $userAllowMap = [];
        $userDenyMap = [];
        foreach ($userOverridesMatrix as $uo) {
            $isAllowed = ($uo['diizinkan'] === true || $uo['diizinkan'] === 't' || $uo['diizinkan'] === 1 || $uo['diizinkan'] === '1');
            if ($isAllowed) {
                $userAllowMap[(string)$uo['izin_id']][] = ['nama_pengguna' => $uo['nama_pengguna']];
            } else {
                $userDenyMap[(string)$uo['izin_id']][] = ['nama_pengguna' => $uo['nama_pengguna']];
            }
        }

        $matrixGroupedPerms = [];
        foreach ($allPerms as $p) {
            $pId = (string)$p['id'];
            $p['roles'] = $rolePermMap[$pId] ?? [];
            $p['users_allow'] = $userAllowMap[$pId] ?? [];
            $p['users_deny'] = $userDenyMap[$pId] ?? [];
            $matrixGroupedPerms[$p['grup_izin']][] = $p;
        }

        // Render Master Single-Page Container
        $this->view('settings.izin.index', [
            'pageTitle' => 'Manajemen Hak Akses & Izin',
            'pageSubtitle' => 'Pusat konfigurasi hak akses, matriks peran bawaan, dan audit perizinan sistem',
            'activeTab' => $activeTab === 'bulk' ? 'role' : $activeTab,

            // Tab 1 Data
            'groupedUsers' => $groupedUsers,
            'selectedUserId' => $selectedUserId,
            'selectedUserData' => $selectedUserData,
            'permissions' => $permissionsGrouped,
            'rolePermissions' => $userRolePermissions,
            'userOverrides' => $userOverrides,
            'customOverrideCount' => $customOverrideCount,
            'totalPermCount' => $totalPermCount,

            // Tab 2 Data
            'roles' => $roles,
            'selectedRoleId' => $selectedRoleId,
            'selectedRole' => $selectedRole,
            'tabRoleActivePerms' => $tabRoleActivePerms,
            'tabRoleActiveCount' => $tabRoleActiveCount,

            // Tab 3 Data
            'rolesWithStats' => $rolesWithStats,
            'usersPerRole'   => $usersPerRole,
            'protectedRoles' => self::PROTECTED_ROLES,

            // Tab 4 Data (Matriks Audit)
            'matrixGroupedPerms' => $matrixGroupedPerms,
            'totalRolesCount' => count($roles),
            'totalUsersCount' => count($allUsersList),
        ]);
    }

    // =========================================================================
    // AKSI TAB 1: SIMPAN OVERRIDES USER
    // =========================================================================

    public function saveUserOverrides(): void
    {
        $userId = trim((string)$this->input('user_id'));
        if (empty($userId)) {
            Flash::danger('ID Pengguna tidak valid.');
            $this->redirect('/permissions?tab=user');
            return;
        }

        if ($userId === Auth::id()) {
            Flash::danger('Anda tidak dapat mengubah izin akun Anda sendiri.');
            $this->redirect('/permissions?tab=user&user_id=' . urlencode($userId));
            return;
        }

        $user = Database::fetchOne("
            SELECT u.id, u.nama_pengguna, u.peran_id, r.nama_peran 
            FROM public.pengguna u
            JOIN public.peran r ON u.peran_id = r.id
            WHERE u.id = :id
        ", ['id' => $userId]);

        if (!$user) {
            Flash::danger('Pengguna tidak ditemukan.');
            $this->redirect('/permissions?tab=user');
            return;
        }

        if ($user['nama_peran'] === 'developer') {
            Flash::danger('Akses Ditolak: Akun Developer memiliki hak akses universal bypass dan tidak dapat dikonfigurasi izin khususnya.');
            $this->redirect('/permissions?tab=user');
            return;
        }

        $submittedOverrides = $_POST['overrides'] ?? []; // [izin_id => 'default'|'allow'|'deny']

        // Ambil izin default dari Role pengguna
        $rolePermsRaw = Database::fetchAll("
            SELECT izin_id FROM public.izin_peran 
            WHERE peran_id = :peran_id AND diizinkan = TRUE
        ", ['peran_id' => $user['peran_id']]);
        $rolePermissionIds = array_map(fn($r) => (string)$r['izin_id'], $rolePermsRaw);

        try {
            // Hapus override lama
            Database::execute("DELETE FROM public.izin_pengguna WHERE pengguna_id = :user_id", ['user_id' => $userId]);

            $allowCount = 0;
            $denyCount = 0;

            foreach ($submittedOverrides as $permId => $choice) {
                $permId = (string)$permId;
                $hasRoleDefault = in_array($permId, $rolePermissionIds, true);

                if ($choice === 'allow' && !$hasRoleDefault) {
                    Database::execute("
                        INSERT INTO public.izin_pengguna (pengguna_id, izin_id, diizinkan, dibuat_pada)
                        VALUES (:uid, :pid, TRUE, NOW())
                    ", ['uid' => $userId, 'pid' => $permId]);
                    $allowCount++;
                } elseif ($choice === 'deny' && $hasRoleDefault) {
                    Database::execute("
                        INSERT INTO public.izin_pengguna (pengguna_id, izin_id, diizinkan, dibuat_pada)
                        VALUES (:uid, :pid, FALSE, NOW())
                    ", ['uid' => $userId, 'pid' => $permId]);
                    $denyCount++;
                }
            }

            Auth::touchPermissionsCache();
            ActivityLog::record(
                Auth::id(),
                'USER_PERMISSIONS_UPDATE',
                "Memperbarui izin khusus @{$user['nama_pengguna']} (+{$allowCount} Allow, -{$denyCount} Deny)"
            );

            Flash::success("Izin khusus untuk @{$user['nama_pengguna']} berhasil disimpan (+{$allowCount} Allow, -{$denyCount} Deny).");
        } catch (Throwable $e) {
            Flash::danger('Gagal menyimpan izin: ' . $e->getMessage());
        }

        $this->redirect('/permissions?tab=user&user_id=' . urlencode($userId));
    }

    public function resetUserOverrides(): void
    {
        $userId = trim((string)$this->input('user_id'));
        if (empty($userId) || $userId === Auth::id()) {
            Flash::danger('Aksi reset tidak valid.');
            $this->redirect('/permissions?tab=user');
            return;
        }

        try {
            Database::execute("DELETE FROM public.izin_pengguna WHERE pengguna_id = :user_id", ['user_id' => $userId]);
            Auth::touchPermissionsCache();
            ActivityLog::record(Auth::id(), 'USER_PERMISSIONS_RESET', "Mereset semua izin khusus user ID: {$userId} ke default role");
            Flash::success("Semua izin khusus berhasil direset. Pengguna kini 100% mengikuti default role.");
        } catch (Throwable $e) {
            Flash::danger('Gagal mereset izin: ' . $e->getMessage());
        }

        $this->redirect('/permissions?tab=user&user_id=' . urlencode($userId));
    }

    // =========================================================================
    // AKSI TAB 2: SIMPAN DEFAULT ROLE MATRIX
    // =========================================================================

    public function saveRolePermissions(): void
    {
        $roleId = trim((string)$this->input('role_id'));
        if (empty($roleId)) {
            Flash::danger('Peran tidak valid.');
            $this->redirect('/permissions?tab=role');
            return;
        }

        $role = Database::fetchOne("SELECT id, nama_peran FROM public.peran WHERE id = :id", ['id' => $roleId]);
        if (!$role) {
            Flash::danger('Peran tidak ditemukan.');
            $this->redirect('/permissions?tab=role');
            return;
        }

        $submittedPerms = $_POST['permissions'] ?? []; // array of izin_id UUIDs
        $submittedPerms = $this->sanitizeScopedPermissions($submittedPerms);

        try {
            Database::execute("DELETE FROM public.izin_peran WHERE peran_id = :role_id", ['role_id' => $roleId]);

            if (!empty($submittedPerms)) {
                foreach ($submittedPerms as $permId) {
                    Database::execute("
                        INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan, dibuat_pada)
                        VALUES (:rid, :iid, TRUE, NOW())
                    ", ['rid' => $roleId, 'iid' => (string)$permId]);
                }
            }

            // Bersihkan baris override user_permissions yang kini redundan
            Database::execute("
                DELETE FROM public.izin_pengguna ip
                USING public.pengguna p, public.izin_peran ir
                WHERE ip.pengguna_id = p.id 
                  AND ir.peran_id = p.peran_id 
                  AND ir.izin_id = ip.izin_id 
                  AND ir.diizinkan = ip.diizinkan
                  AND p.peran_id = :role_id
            ", ['role_id' => $roleId]);

            Auth::touchPermissionsCache();

            $totalActive = count($submittedPerms);
            ActivityLog::record(
                Auth::id(),
                'UPDATE_ROLE_PERMISSIONS',
                "Memperbarui izin default peran {$role['nama_peran']} ({$totalActive} izin aktif)"
            );

            Flash::success("Izin default untuk role " . ucfirst($role['nama_peran']) . " berhasil disimpan ({$totalActive} izin aktif).");

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => "Izin default untuk role " . ucfirst($role['nama_peran']) . " berhasil disimpan ({$totalActive} izin aktif)."
                ]);
                return;
            }
        } catch (Throwable $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Gagal menyimpan izin peran: ' . $e->getMessage()], 500);
                return;
            }
            Flash::danger('Gagal menyimpan izin peran: ' . $e->getMessage());
        }

        $this->redirect('/permissions?tab=role&role_id=' . urlencode($roleId));
    }

    // =========================================================================
    // AKSI TAB 3: KELOLA MASTER ROLE (CRUD)
    // =========================================================================

    public function storeRole(): void
    {
        Auth::requirePermission('rbac.roles_manage');

        $namaPeran = strtolower(trim((string)$this->input('nama_peran', '')));
        $deskripsi = trim((string)$this->input('deskripsi', ''));

        if (empty($namaPeran)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Nama peran wajib diisi.'], 400);
                return;
            }
            Flash::danger('Nama peran wajib diisi.');
            $this->redirect('/permissions?tab=manage_roles');
            return;
        }

        $namaPeran = preg_replace('/[^a-z0-9_]/', '', str_replace(' ', '_', $namaPeran));

        $exists = Database::fetchOne("SELECT id FROM public.peran WHERE nama_peran = :nama", ['nama' => $namaPeran]);
        if ($exists) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => "Peran '{$namaPeran}' sudah terdaftar."], 400);
                return;
            }
            Flash::danger("Peran '{$namaPeran}' sudah terdaftar.");
            $this->redirect('/permissions?tab=manage_roles');
            return;
        }

        Database::execute("
            INSERT INTO public.peran (nama_peran, deskripsi, dibuat_pada, diubah_pada)
            VALUES (:nama_peran, :deskripsi, NOW(), NOW())
        ", [
            'nama_peran' => $namaPeran,
            'deskripsi' => $deskripsi,
        ]);

        ActivityLog::record(Auth::id(), 'ROLE_CREATE', "Membuat peran baru: {$namaPeran}");
        Flash::success("Peran '{$namaPeran}' berhasil dibuat.");

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => "Peran '{$namaPeran}' berhasil dibuat."]);
            return;
        }

        $this->redirect('/permissions?tab=manage_roles');
    }

    public function updateRole(): void
    {
        Auth::requirePermission('rbac.roles_manage');

        $id = trim((string)$this->input('id', ''));
        $deskripsi = trim((string)$this->input('deskripsi', ''));

        if (empty($id)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'ID Peran tidak valid.'], 400);
                return;
            }
            Flash::danger('ID Peran tidak valid.');
            $this->redirect('/permissions?tab=manage_roles');
            return;
        }

        $role = Database::fetchOne("SELECT id, nama_peran FROM public.peran WHERE id = :id", ['id' => $id]);
        if (!$role) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Peran tidak ditemukan.'], 404);
                return;
            }
            Flash::danger('Peran tidak ditemukan.');
            $this->redirect('/permissions?tab=manage_roles');
            return;
        }

        Database::execute("
            UPDATE public.peran 
            SET deskripsi = :deskripsi, diubah_pada = NOW() 
            WHERE id = :id
        ", [
            'deskripsi' => $deskripsi,
            'id' => $id,
        ]);

        ActivityLog::record(Auth::id(), 'ROLE_UPDATE', "Memperbarui deskripsi peran {$role['nama_peran']}");
        Flash::success("Deskripsi peran '{$role['nama_peran']}' berhasil diperbarui.");

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => "Deskripsi peran '{$role['nama_peran']}' berhasil diperbarui."]);
            return;
        }

        $this->redirect('/permissions?tab=manage_roles');
    }

    public function deleteRole(): void
    {
        Auth::requirePermission('rbac.roles_manage');

        $id = trim((string)$this->input('id', ''));
        if (empty($id)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'ID Peran tidak valid.'], 400);
                return;
            }
            Flash::danger('ID Peran tidak valid.');
            $this->redirect('/permissions?tab=manage_roles');
            return;
        }

        $role = Database::fetchOne("SELECT id, nama_peran FROM public.peran WHERE id = :id", ['id' => $id]);
        if (!$role) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Peran tidak ditemukan.'], 404);
                return;
            }
            Flash::danger('Peran tidak ditemukan.');
            $this->redirect('/permissions?tab=manage_roles');
            return;
        }

        if (in_array($role['nama_peran'], self::PROTECTED_ROLES, true)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => "Peran sistem '{$role['nama_peran']}' dilindungi dan tidak dapat dihapus."], 403);
                return;
            }
            Flash::danger("Peran sistem '{$role['nama_peran']}' dilindungi dan tidak dapat dihapus.");
            $this->redirect('/permissions?tab=manage_roles');
            return;
        }

        $userCount = Database::fetchOne("SELECT count(*) as total FROM public.pengguna WHERE peran_id = :id", ['id' => $id])['total'] ?? 0;
        if ((int)$userCount > 0) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => "Tidak dapat menghapus peran '{$role['nama_peran']}' karena masih digunakan oleh {$userCount} akun pengguna."], 400);
                return;
            }
            Flash::danger("Tidak dapat menghapus peran '{$role['nama_peran']}' karena masih digunakan oleh {$userCount} akun pengguna.");
            $this->redirect('/permissions?tab=manage_roles');
            return;
        }

        Database::execute("DELETE FROM public.izin_peran WHERE peran_id = :peran_id", ['peran_id' => $id]);
        Database::execute("DELETE FROM public.peran WHERE id = :id", ['id' => $id]);

        Auth::touchPermissionsCache();
        ActivityLog::record(Auth::id(), 'ROLE_DELETE', "Menghapus peran: {$role['nama_peran']}");
        Flash::success("Peran '{$role['nama_peran']}' berhasil dihapus.");

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => "Peran '{$role['nama_peran']}' berhasil dihapus."]);
            return;
        }

        $this->redirect('/permissions?tab=manage_roles');
    }

    // =========================================================================
    // AKSI TAB 4: SIMPAN BULK OVERRIDES
    // =========================================================================

    public function saveBulkOverrides(): void
    {
        $userIds = $this->input('user_ids', []);
        $permIds = $this->input('perm_ids', []);
        $actionType = (string)$this->input('action_type', 'allow'); // 'allow', 'deny', 'reset'

        if (!is_array($userIds) || empty($userIds)) {
            Flash::danger('Pilih minimal satu pengguna target.');
            $this->redirect('/permissions?tab=bulk');
            return;
        }

        if (!is_array($permIds) || empty($permIds)) {
            Flash::danger('Pilih minimal satu izin target.');
            $this->redirect('/permissions?tab=bulk');
            return;
        }

        $totalAffected = 0;
        foreach ($userIds as $userId) {
            $userId = (string)$userId;
            if ($userId === (string)Auth::id()) continue;

            $user = Database::fetchOne("SELECT peran_id FROM public.pengguna WHERE id = :id", ['id' => $userId]);
            if (!$user) continue;

            $rolePermsRaw = Database::fetchAll("
                SELECT izin_id FROM public.izin_peran 
                WHERE peran_id = :pid AND diizinkan = TRUE
            ", ['pid' => $user['peran_id']]);
            $rolePermissionIds = array_map(fn($r) => (string)$r['izin_id'], $rolePermsRaw);

            foreach ($permIds as $permId) {
                $permId = (string)$permId;
                $hasRoleDefault = in_array($permId, $rolePermissionIds, true);

                // Hapus override lama untuk izin ini
                Database::execute("
                    DELETE FROM public.izin_pengguna WHERE pengguna_id = :uid AND izin_id = :pid
                ", ['uid' => $userId, 'pid' => $permId]);

                if ($actionType === 'allow' && !$hasRoleDefault) {
                    Database::execute("
                        INSERT INTO public.izin_pengguna (pengguna_id, izin_id, diizinkan, dibuat_pada)
                        VALUES (:uid, :pid, TRUE, NOW())
                    ", ['uid' => $userId, 'pid' => $permId]);
                } elseif ($actionType === 'deny' && $hasRoleDefault) {
                    Database::execute("
                        INSERT INTO public.izin_pengguna (pengguna_id, izin_id, diizinkan, dibuat_pada)
                        VALUES (:uid, :pid, FALSE, NOW())
                    ", ['uid' => $userId, 'pid' => $permId]);
                }
            }
            $totalAffected++;
        }

        Auth::touchPermissionsCache();
        ActivityLog::record(Auth::id(), 'PERMISSIONS_BULK', "Menerapkan bulk update aksi '{$actionType}' pada " . count($userIds) . " user dan " . count($permIds) . " izin");
        Flash::success("Aksi massal berhasil diterapkan pada {$totalAffected} pengguna.");
        $this->redirect('/permissions?tab=bulk');
    }

    // =========================================================================
    // REDIRECT ALIASES FOR BACKWARD COMPATIBILITY
    // =========================================================================

    public function roles(): void
    {
        $roleId = (string)$this->input('role_id', '');
        $this->redirect('/permissions?tab=role' . (!empty($roleId) ? '&role_id=' . urlencode($roleId) : ''));
    }

    public function manageRoles(): void
    {
        $this->redirect('/permissions?tab=manage_roles');
    }

    public function bulk(): void
    {
        $this->redirect('/permissions?tab=role');
    }

    public function matrix(): void
    {
        $this->redirect('/permissions?tab=matrix');
    }

    /**
     * Sanitasi Mutual Exclusion: Jika _all dan _assigned sama-sama dikirim, pertahankan _all
     */
    private function sanitizeScopedPermissions(array $permIds): array
    {
        if (empty($permIds)) return [];

        $allPerms = Database::fetchAll("SELECT id, kode_izin FROM public.izin WHERE id IN ('" . implode("','", array_map('addslashes', $permIds)) . "')");
        $codeToId = [];
        foreach ($allPerms as $p) {
            $codeToId[$p['kode_izin']] = (string)$p['id'];
        }

        $scopePairs = [
            ['orders.po_view_all', 'orders.po_view_assigned'],
            ['orders.view_all', 'orders.view_assigned'],
            ['orders.edit_all', 'orders.edit_assigned'],
            ['consignment.view_all', 'consignment.view_assigned'],
            ['consignment.opname_all', 'consignment.opname_assigned'],
            ['consignment.reports_all', 'consignment.reports_assigned'],
            ['consignment.komisi_all', 'consignment.komisi_self'],
            ['deliveries.view_all', 'deliveries.view_assigned'],
            ['deliveries.update_all', 'deliveries.update_assigned'],
            ['master.customers_view_all', 'master.customers_view_assigned'],
        ];

        $idsToRemove = [];
        foreach ($scopePairs as [$allCode, $assignedCode]) {
            if (isset($codeToId[$allCode]) && isset($codeToId[$assignedCode])) {
                $idsToRemove[] = $codeToId[$assignedCode];
            }
        }

        return array_values(array_diff($permIds, $idsToRemove));
    }
}

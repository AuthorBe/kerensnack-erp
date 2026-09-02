<?php 
use App\Core\Router; 

$totalRoles = count($rolesWithStats ?? $roles ?? []);
$defaultRolesCount = 0;
$customRolesCount = 0;
$protectedList = $protectedRoles ?? ['developer', 'owner', 'admin', 'sales', 'driver'];

foreach (($rolesWithStats ?? $roles ?? []) as $r) {
    if (in_array($r['nama_peran'], $protectedList, true)) {
        $defaultRolesCount++;
    } else {
        $customRolesCount++;
    }
}
?>
<script>
// Map: peran_id -> array of users — diakses dari Alpine @click handler
window._roleUsersMap = <?= json_encode($usersPerRole ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>

<!-- ========================================================================= -->
<!-- TAB 3: KELOLA MASTER ROLE (1:1 REKAP-MUKHOLIF MANAGE_ROLES.PHP)           -->
<!-- ========================================================================= -->
<div class="space-y-4">
    
    <!-- Table Master Role with Integrated Header Toolbar -->
    <div class="card shadow-sm border-0 rounded-4 mb-4 overflow-hidden" style="background: #ffffff; border: 1.5px solid #edf2f7 !important; border-radius: 16px; margin-bottom: 20px;">
        
        <!-- Header Toolbar -->
        <div class="card-header bg-white border-bottom p-3.5 p-md-4" style="padding: 18px 24px; border-bottom: 1.5px solid #f1f5f9; background: #ffffff;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
                <div>
                    <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2" style="font-size: 1.1rem; font-weight: 700; margin: 0 0 4px 0; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="shield" style="width: 18px; height: 18px; color: var(--iz-primary);"></i>
                        <span>Daftar Jabatan (Role)</span>
                    </h5>
                    <div class="d-flex align-items-center gap-2 flex-wrap text-muted small" style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: #64748b;">
                        <span><strong class="text-dark" style="color: #0f172a;"><?= $totalRoles ?></strong> total role</span>
                        <span>•</span>
                        <span style="color: var(--iz-primary);"><strong style="color: var(--iz-primary);"><?= $defaultRolesCount ?></strong> sistem default</span>
                        <span>•</span>
                        <span style="color: #16a34a;"><strong style="color: #16a34a;"><?= $customRolesCount ?></strong> kustom</span>
                    </div>
                </div>

                <div>
                    <button type="button" @click="addRoleModalOpen = true; setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 50);"
                            class="btn btn-primary rounded-3 px-3.5 py-2 fw-semibold shadow-sm d-inline-flex align-items-center justify-content-center gap-2" 
                            style="background: var(--iz-primary); border: none; font-size: 0.85rem; font-weight: 600; color: #ffffff; padding: 9px 18px; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                        <i data-lucide="plus" style="width: 15px; height: 15px;"></i>
                        <span>Tambah Role Baru</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0" style="padding: 0;">
            <!-- Desktop Table View -->
            <div class="table-responsive d-none d-md-block" style="overflow-x: auto;">
                <table class="table table-hover align-middle mb-0" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0;">
                        <tr>
                            <th style="padding: 12px 18px; width: 60px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">No</th>
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Nama Role (Jabatan)</th>
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Slug / ID Sistem</th>
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Tipe Role</th>
                            <th style="padding: 12px 18px; text-align: center; width: 100px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Pengguna</th>
                            <th style="padding: 12px 18px; text-align: center; width: 120px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach (($rolesWithStats ?? $roles ?? []) as $row): 
                            $isProtected = in_array($row['nama_peran'], $protectedList, true);
                            $userCount   = count($usersPerRole[(string)$row['id']] ?? []);
                        ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 14px 18px; font-size: 0.85rem; font-weight: 600; color: #94a3b8;"><?= $no++ ?></td>
                                <td style="padding: 14px 18px;">
                                    <div>
                                        <div style="font-size: 0.92rem; font-weight: 700; color: #0f172a; margin-bottom: 2px;">
                                            <?= htmlspecialchars(ucfirst($row['nama_peran'])) ?>
                                        </div>
                                        <?php if ($row['nama_peran'] !== 'developer'): ?>
                                            <a href="javascript:void(0)" @click="setTab('role'); const url = new URL(window.location.href); url.searchParams.set('role_id', '<?= urlencode($row['id']) ?>'); window.history.replaceState({}, '', url.toString()); window.location.reload();" 
                                               style="color: var(--iz-primary); font-size: 0.78rem; font-weight: 600; text-decoration: none;">
                                                <i data-lucide="sliders" style="width: 12px; height: 12px; display: inline-block; vertical-align: -1px;"></i> Atur Izin Default &rarr;
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #64748b; font-size: 0.76rem;">
                                                <i data-lucide="infinity" style="width: 12px; height: 12px; display: inline-block; vertical-align: -1px; color: var(--iz-primary);"></i> Akses Penuh (Bypass)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="padding: 14px 18px;">
                                    <span class="perm-code-tag">
                                        <i data-lucide="code" style="width: 11px; height: 11px; opacity: 0.6;"></i> <?= htmlspecialchars($row['nama_peran']) ?>
                                    </span>
                                </td>
                                <td style="padding: 14px 18px;">
                                    <?php if ($isProtected): ?>
                                        <span class="badge" style="background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; font-size:0.73rem; font-weight:600; padding: 4px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i data-lucide="shield" style="width: 12px; height: 12px;"></i> Sistem Default
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; font-size:0.73rem; font-weight:600; padding: 4px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i data-lucide="user-check" style="width: 12px; height: 12px; color: #16a34a;"></i> Role Kustom
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <!-- Kolom Jumlah Pengguna -->
                                <td style="padding: 14px 18px; text-align: center;">
                                    <?php if ($userCount > 0): ?>
                                        <button type="button"
                                                @click="showRoleUsers('<?= htmlspecialchars($row['nama_peran']) ?>', window._roleUsersMap['<?= htmlspecialchars($row['id']) ?>'] || [])"
                                                title="Lihat daftar user"
                                                style="display:inline-flex;align-items:center;gap:5px;background:none;border:none;cursor:pointer;color:#2563eb;font-size:0.82rem;font-weight:700;padding:4px 6px;border-radius:8px;transition:background 0.12s;"
                                                onmouseover="this.style.background='#eff6ff';"
                                                onmouseout="this.style.background='none';">
                                            <i data-lucide="users" style="width:13px;height:13px;opacity:0.75;"></i>
                                            <?= $userCount ?>
                                        </button>
                                    <?php else: ?>
                                        <span style="color:#cbd5e1;font-size:0.85rem;">—</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Kolom Aksi -->
                                <td style="padding: 14px 18px; text-align: center;">
                                    <div style="display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                        <button type="button" 
                                                @click="editRole = { id: '<?= htmlspecialchars($row['id']) ?>', nama_peran: '<?= htmlspecialchars($row['nama_peran']) ?>', deskripsi: '<?= htmlspecialchars(addslashes($row['deskripsi'] ?? '')) ?>' }; editRoleModalOpen = true; setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 50);"
                                                class="btn-action-round" title="Edit Keterangan Role" style="color: var(--iz-primary);">
                                            <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i>
                                        </button>

                                        <?php if (!$isProtected && $userCount === 0): ?>
                                            <button type="button"
                                                    @click="confirmDeleteRole('<?= htmlspecialchars($row['id']) ?>', '<?= htmlspecialchars($row['nama_peran']) ?>')"
                                                    class="btn-action-round" title="Hapus Role" style="color: var(--iz-red);">
                                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View (< 768px) -->
            <div class="d-block d-md-none" style="display: none;">
                <?php foreach (($rolesWithStats ?? $roles ?? []) as $row): 
                    $isProtected = in_array($row['nama_peran'], $protectedList, true);
                    $userCount   = count($usersPerRole[(string)$row['id']] ?? []);
                ?>
                    <div style="padding: 16px; border-bottom: 1px solid #f1f5f9;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 8px;">
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin-bottom: 4px;">
                                    <?= htmlspecialchars(ucfirst($row['nama_peran'])) ?>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <span class="perm-code-tag">
                                        <i data-lucide="code" style="width: 11px; height: 11px; opacity: 0.6;"></i> <?= htmlspecialchars($row['nama_peran']) ?>
                                    </span>
                                    <?php if ($isProtected): ?>
                                        <span class="badge" style="background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; font-size:0.7rem; font-weight:600; padding: 3px 6px; border-radius: 6px;">
                                            Sistem Default
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; font-size:0.7rem; font-weight:600; padding: 3px 6px; border-radius: 6px;">
                                            Role Kustom
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($userCount > 0): ?>
                                        <button type="button"
                                                @click="showRoleUsers('<?= htmlspecialchars($row['nama_peran']) ?>', window._roleUsersMap['<?= htmlspecialchars($row['id']) ?>'] || [])"
                                                style="display:inline-flex;align-items:center;gap:4px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;font-size:0.7rem;font-weight:700;padding:2px 7px;border-radius:6px;cursor:pointer;">
                                            <i data-lucide="users" style="width:11px;height:11px;"></i>
                                            <?= $userCount ?> user
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 6px;">
                                <button type="button" 
                                        @click="editRole = { id: '<?= htmlspecialchars($row['id']) ?>', nama_peran: '<?= htmlspecialchars($row['nama_peran']) ?>', deskripsi: '<?= htmlspecialchars(addslashes($row['deskripsi'] ?? '')) ?>' }; editRoleModalOpen = true; setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 50);"
                                        class="btn-action-round" title="Edit Nama Role" style="color: var(--iz-primary);">
                                    <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i>
                                </button>
                                <?php if (!$isProtected && $userCount === 0): ?>
                                    <button type="button"
                                            @click="confirmDeleteRole('<?= htmlspecialchars($row['id']) ?>', '<?= htmlspecialchars($row['nama_peran']) ?>')"
                                            class="btn-action-round" title="Hapus Role" style="color: var(--iz-red);">
                                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($row['nama_peran'] !== 'developer'): ?>
                            <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #f8fafc;">
                                <a href="javascript:void(0)" @click="setTab('role'); const url = new URL(window.location.href); url.searchParams.set('role_id', '<?= urlencode($row['id']) ?>'); window.history.replaceState({}, '', url.toString()); window.location.reload();" 
                                   style="color: var(--iz-primary); font-size: 0.78rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                    <i data-lucide="sliders" style="width: 12px; height: 12px;"></i> Atur Izin Default Role Ini &rarr;
                                </a>
                            </div>
                        <?php else: ?>
                            <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #f8fafc;">
                                <span style="color: #64748b; font-size: 0.76rem;">
                                    <i data-lucide="infinity" style="width: 12px; height: 12px; color: var(--iz-primary);"></i> Akses Penuh Seluruh Sistem (Bypass)
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL TAMBAH ROLE                                                     -->
    <!-- ===================================================================== -->
    <template x-teleport="body">
        <div x-show="addRoleModalOpen" x-cloak class="modal-backdrop" style="background:rgba(15,23,42,0.55);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;padding:16px;z-index:9999;" @click.self="addRoleModalOpen = false">
            <div class="modal-box" style="max-width:540px;width:100%;border-radius:20px;padding:28px 32px;background:#ffffff;border:1.5px solid #e2e8f0;box-shadow:0 25px 50px -12px rgba(15,23,42,0.25);" @click.stop>
                
                <div class="modal-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;padding-bottom:18px;border-bottom:1.5px solid #f1f5f9;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:44px;height:44px;border-radius:12px;background:#eff6ff;color:var(--iz-primary, #2563eb);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="shield-plus" style="width:22px;height:22px;"></i>
                        </div>
                        <div>
                            <h4 style="font-size:1.18rem;font-weight:700;color:#0f172a;margin:0 0 3px 0;">Tambah Role Baru</h4>
                            <p style="font-size:0.82rem;color:#64748b;margin:0;">Definisikan nama jabatan dan hak wewenang baru dalam sistem</p>
                        </div>
                    </div>
                    <button type="button" @click="addRoleModalOpen = false" 
                            style="width:34px;height:34px;border-radius:10px;background:#f8fafc;border:1.5px solid #e2e8f0;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.15s;"
                            onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';this.style.borderColor='#fecaca';"
                            onmouseout="this.style.background='#f8fafc';this.style.color='#64748b';this.style.borderColor='#e2e8f0';">
                        <i data-lucide="x" style="width:16px;height:16px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/permissions/store-role') ?>" method="POST">
                    <div style="display:flex;flex-direction:column;gap:18px;">
                        <div>
                            <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                Nama Jabatan / Role <span style="color:#e11d48;">*</span>
                            </label>
                            <input type="text" name="nama_peran" placeholder="Contoh: auditor, kasir_cabang" required 
                                   style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;font-family:monospace;outline:none;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                   onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                   onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                            <div style="font-size:0.78rem;color:#64748b;margin-top:6px;display:flex;align-items:center;gap:5px;">
                                <i data-lucide="info" style="width:13px;height:13px;color:var(--iz-primary, #2563eb);flex-shrink:0;"></i>
                                <span>Sistem akan membuat slug teknis otomatis (huruf kecil & underscore).</span>
                            </div>
                        </div>

                        <div>
                            <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                Deskripsi Wewenang <span style="font-size:0.78rem;color:#64748b;font-weight:400;">(Opsional)</span>
                            </label>
                            <textarea name="deskripsi" rows="3" placeholder="Keterangan singkat cakupan tanggung jawab peran ini..." 
                                      style="width:100%;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;outline:none;resize:vertical;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                      onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                      onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';"></textarea>
                        </div>
                    </div>

                    <div style="display:flex;justify-content:flex-end;align-items:center;gap:10px;padding-top:20px;margin-top:26px;border-top:1.5px solid #f1f5f9;">
                        <button type="button" @click="addRoleModalOpen = false" 
                                style="height:42px;padding:0 20px;border-radius:10px;background:#f1f5f9;color:#475569;font-weight:600;font-size:0.88rem;border:none;cursor:pointer;transition:all 0.15s;"
                                onmouseover="this.style.background='#e2e8f0';" onmouseout="this.style.background='#f1f5f9';">
                            Batal
                        </button>
                        <button type="submit" 
                                style="height:42px;padding:0 24px;border-radius:10px;background:var(--iz-primary, #2563eb);color:#ffffff;font-weight:700;font-size:0.88rem;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(37,99,235,0.25);display:inline-flex;align-items:center;gap:8px;transition:all 0.15s;"
                                onmouseover="this.style.transform='translateY(-1px)';" onmouseout="this.style.transform='none';">
                            <i data-lucide="check" style="width:16px;height:16px;"></i>
                            <span>Simpan Role</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </template>

    <!-- ===================================================================== -->
    <!-- MODAL EDIT ROLE                                                       -->
    <!-- ===================================================================== -->
    <template x-teleport="body">
        <div x-show="editRoleModalOpen" x-cloak class="modal-backdrop" style="background:rgba(15,23,42,0.55);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;padding:16px;z-index:9999;" @click.self="editRoleModalOpen = false">
            <div class="modal-box" style="max-width:540px;width:100%;border-radius:20px;padding:28px 32px;background:#ffffff;border:1.5px solid #e2e8f0;box-shadow:0 25px 50px -12px rgba(15,23,42,0.25);" @click.stop>
                
                <div class="modal-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;padding-bottom:18px;border-bottom:1.5px solid #f1f5f9;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:44px;height:44px;border-radius:12px;background:#eff6ff;color:var(--iz-primary, #2563eb);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="edit-3" style="width:22px;height:22px;"></i>
                        </div>
                        <div>
                            <h4 style="font-size:1.18rem;font-weight:700;color:#0f172a;margin:0 0 3px 0;">Edit Deskripsi Role</h4>
                            <p style="font-size:0.82rem;color:#64748b;margin:0;">Perbarui keterangan wewenang dan cakupan fungsi jabatan</p>
                        </div>
                    </div>
                    <button type="button" @click="editRoleModalOpen = false" 
                            style="width:34px;height:34px;border-radius:10px;background:#f8fafc;border:1.5px solid #e2e8f0;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.15s;"
                            onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';this.style.borderColor='#fecaca';"
                            onmouseout="this.style.background='#f8fafc';this.style.color='#64748b';this.style.borderColor='#e2e8f0';">
                        <i data-lucide="x" style="width:16px;height:16px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/permissions/update-role') ?>" method="POST">
                    <input type="hidden" name="id" :value="editRole.id">

                    <div style="display:flex;flex-direction:column;gap:18px;">
                        <div>
                            <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                ID Sistem Role <span style="font-size:0.76rem;color:#64748b;font-weight:400;">(Terkunci)</span>
                            </label>
                            <input type="text" :value="editRole.nama_peran" readonly 
                                   style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:0.88rem;color:#64748b;font-family:monospace;outline:none;cursor:not-allowed;">
                            <div style="font-size:0.78rem;color:#e11d48;margin-top:6px;display:flex;align-items:center;gap:5px;">
                                <i data-lucide="lock" style="width:13px;height:13px;flex-shrink:0;"></i>
                                <span>ID teknis tidak dapat diubah untuk menjaga integritas relasi basis data.</span>
                            </div>
                        </div>

                        <div>
                            <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                Deskripsi Wewenang <span style="font-size:0.78rem;color:#64748b;font-weight:400;">(Opsional)</span>
                            </label>
                            <textarea name="deskripsi" rows="3" x-model="editRole.deskripsi" placeholder="Keterangan cakupan tanggung jawab peran ini..." 
                                      style="width:100%;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;outline:none;resize:vertical;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                      onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                      onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';"></textarea>
                        </div>
                    </div>

                    <div style="display:flex;justify-content:flex-end;align-items:center;gap:10px;padding-top:20px;margin-top:26px;border-top:1.5px solid #f1f5f9;">
                        <button type="button" @click="editRoleModalOpen = false" 
                                style="height:42px;padding:0 20px;border-radius:10px;background:#f1f5f9;color:#475569;font-weight:600;font-size:0.88rem;border:none;cursor:pointer;transition:all 0.15s;"
                                onmouseover="this.style.background='#e2e8f0';" onmouseout="this.style.background='#f1f5f9';">
                            Batal
                        </button>
                        <button type="submit" 
                                style="height:42px;padding:0 24px;border-radius:10px;background:var(--iz-primary, #2563eb);color:#ffffff;font-weight:700;font-size:0.88rem;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(37,99,235,0.25);display:inline-flex;align-items:center;gap:8px;transition:all 0.15s;"
                                onmouseover="this.style.transform='translateY(-1px)';" onmouseout="this.style.transform='none';">
                            <i data-lucide="check" style="width:16px;height:16px;"></i>
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </template>

    <!-- ===================================================================== -->
    <!-- MODAL KONFIRMASI HAPUS ROLE                                           -->
    <!-- ===================================================================== -->
    <template x-teleport="body">
        <div x-show="deleteRoleModalOpen" x-cloak class="modal-backdrop"
             style="background:rgba(15,23,42,0.55);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;padding:16px;z-index:9999;"
             @click.self="deleteRoleModalOpen = false">
            <div class="modal-box" style="max-width:460px;width:100%;border-radius:20px;padding:28px 32px;background:#ffffff;border:1.5px solid #fecaca;box-shadow:0 25px 50px -12px rgba(239,68,68,0.15);" @click.stop>

                <!-- Header -->
                <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px;">
                    <div style="width:48px;height:48px;border-radius:14px;background:#fef2f2;color:#ef4444;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="trash-2" style="width:24px;height:24px;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <h4 style="font-size:1.12rem;font-weight:700;color:#0f172a;margin:0 0 6px 0;">Hapus Role?</h4>
                        <p style="font-size:0.86rem;color:#64748b;margin:0;line-height:1.55;">
                            Role <strong style="color:#0f172a;" x-text="'«' + deleteRoleTarget.nama_peran + '»'"></strong> akan dihapus permanen dari sistem.
                            User yang sudah terlanjur memakai role ini bisa kehilangan akses standarnya.
                        </p>
                    </div>
                </div>

                <!-- Warning box -->
                <div style="background:#fef9ec;border:1.5px solid #fde68a;border-radius:12px;padding:12px 16px;display:flex;align-items:flex-start;gap:10px;margin-bottom:24px;">
                    <i data-lucide="alert-triangle" style="width:17px;height:17px;color:#d97706;flex-shrink:0;margin-top:1px;"></i>
                    <p style="font-size:0.82rem;color:#92400e;margin:0;line-height:1.5;">
                        Tindakan ini <strong>tidak dapat dibatalkan</strong>. Pastikan tidak ada user aktif yang bergantung pada role ini sebelum menghapus.
                    </p>
                </div>

                <!-- Action buttons -->
                <form :action="'<?= Router::url('/permissions/delete-role') ?>'" method="POST">
                    <input type="hidden" name="id" :value="deleteRoleTarget.id">
                    <div style="display:flex;justify-content:flex-end;align-items:center;gap:10px;">
                        <button type="button" @click="deleteRoleModalOpen = false"
                                style="height:42px;padding:0 22px;border-radius:10px;background:#f1f5f9;color:#475569;font-weight:600;font-size:0.88rem;border:none;cursor:pointer;transition:all 0.15s;"
                                onmouseover="this.style.background='#e2e8f0';" onmouseout="this.style.background='#f1f5f9';">
                            Batal
                        </button>
                        <button type="submit"
                                style="height:42px;padding:0 22px;border-radius:10px;background:#ef4444;color:#ffffff;font-weight:700;font-size:0.88rem;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(239,68,68,0.3);display:inline-flex;align-items:center;gap:8px;transition:all 0.15s;"
                                onmouseover="this.style.transform='translateY(-1px)';" onmouseout="this.style.transform='none';">
                            <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                            <span>Ya, Hapus Role</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </template>

    <!-- ===================================================================== -->
    <!-- MODAL DAFTAR PENGGUNA ROLE (minimalis)                               -->
    <!-- ===================================================================== -->
    <template x-teleport="body">
        <div x-show="viewUsersRoleModalOpen" x-cloak class="modal-backdrop"
             style="background:rgba(15,23,42,0.45);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:16px;z-index:9999;"
             @click.self="viewUsersRoleModalOpen = false"
             @keydown.escape.window="viewUsersRoleModalOpen = false">
            <div style="max-width:380px;width:100%;border-radius:16px;background:#ffffff;border:1px solid #e2e8f0;box-shadow:0 8px 30px rgba(15,23,42,0.12);display:flex;flex-direction:column;max-height:80vh;overflow:hidden;" @click.stop>

                <!-- Header -->
                <div style="padding:16px 18px 12px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                    <div>
                        <p style="font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:0.6px;color:#94a3b8;margin:0 0 2px 0;">Pengguna Role</p>
                        <h5 style="font-size:0.98rem;font-weight:700;color:#0f172a;margin:0;" x-text="viewUsersRoleTarget.nama_peran"></h5>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:0.75rem;font-weight:600;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:20px;padding:3px 10px;" x-text="viewUsersRoleTarget.users.length + ' akun'"></span>
                        <button type="button" @click="viewUsersRoleModalOpen = false"
                                style="width:28px;height:28px;border-radius:8px;background:none;border:none;color:#94a3b8;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.12s;"
                                onmouseover="this.style.background='#f1f5f9';this.style.color='#475569';"
                                onmouseout="this.style.background='none';this.style.color='#94a3b8';">
                            <i data-lucide="x" style="width:14px;height:14px;"></i>
                        </button>
                    </div>
                </div>

                <!-- List (scrollable) -->
                <div style="overflow-y:auto;flex:1;">

                    <!-- Empty -->
                    <template x-if="viewUsersRoleTarget.users.length === 0">
                        <div style="padding:32px 20px;text-align:center;">
                            <p style="font-size:0.85rem;color:#94a3b8;margin:0;">Tidak ada pengguna.</p>
                        </div>
                    </template>

                    <!-- Rows -->
                    <template x-if="viewUsersRoleTarget.users.length > 0">
                        <div>
                            <template x-for="(user, i) in viewUsersRoleTarget.users" :key="user.id">
                                <div style="display:flex;align-items:center;gap:10px;padding:10px 18px;border-bottom:1px solid #f8fafc;">
                                    <!-- Avatar -->
                                    <div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:0.82rem;font-weight:700;flex-shrink:0;"
                                         x-text="user.nama_lengkap ? user.nama_lengkap.charAt(0).toUpperCase() : '?'"></div>
                                    <!-- Info -->
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:0.85rem;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="user.nama_lengkap"></div>
                                        <div style="font-size:0.74rem;color:#94a3b8;font-family:monospace;" x-text="'@' + user.nama_pengguna"></div>
                                    </div>
                                    <!-- Status dot -->
                                    <template x-if="user.status_aktif">
                                        <span style="width:7px;height:7px;border-radius:50%;background:#22c55e;flex-shrink:0;" title="Aktif"></span>
                                    </template>
                                    <template x-if="!user.status_aktif">
                                        <span style="width:7px;height:7px;border-radius:50%;background:#f87171;flex-shrink:0;" title="Nonaktif"></span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                </div>

                <!-- Footer legend -->
                <div style="padding:10px 18px;border-top:1px solid #f1f5f9;display:flex;align-items:center;gap:12px;flex-shrink:0;">
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.73rem;color:#64748b;">
                        <span style="width:7px;height:7px;border-radius:50%;background:#22c55e;"></span> Aktif
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.73rem;color:#64748b;">
                        <span style="width:7px;height:7px;border-radius:50%;background:#f87171;"></span> Nonaktif
                    </span>
                </div>

            </div>
        </div>
    </template>

</div>

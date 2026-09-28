<?php 
use App\Core\Router; 

$totalRoles = count($rolesWithStats ?? $roles ?? []);
$defaultRolesCount = 0;
$customRolesCount = 0;
$protectedList = $protectedRoles ?? ['developer', 'owner', 'admin', 'sales', 'driver', 'mandor', 'gudang'];

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
                                            <a href="javascript:void(0)" @click="if (window.AppAction) window.AppAction.show('Memuat izin role...', 'Membuka konfigurasi hak akses role'); try { sessionStorage.setItem('app_action_triggered', 'true'); } catch(e){}; window.location.href = '<?= Router::url('/permissions') ?>?tab=role&role_id=' + encodeURIComponent('<?= $row['id'] ?>');" 
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
        <div x-show="addRoleModalOpen" x-cloak class="modal-backdrop" @click="addRoleModalOpen = false" style="display:none;">
            <div class="modal-box" style="max-width:540px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>
                
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:40px;height:40px;border-radius:12px;background:rgba(37,99,235,0.12);color:var(--iz-primary, #2563eb);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="shield-plus" style="width:20px;height:20px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title">Tambah Role Baru</div>
                            <div style="font-size:12px;color:var(--color-ink-mute);margin-top:1px;">Definisikan nama jabatan dan hak wewenang baru dalam sistem</div>
                        </div>
                    </div>
                    <button type="button" @click="addRoleModalOpen = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/permissions/store-role') ?>" method="POST" data-action-text="Menyimpan role baru...">
                    <div class="modal-body custom-scrollbar space-y-4">
                        <div>
                            <label style="display:block;font-size:0.85rem;font-weight:700;color:var(--color-ink);margin-bottom:7px;">
                                Nama Jabatan / Role <span style="color:#e11d48;">*</span>
                            </label>
                            <input type="text" name="nama_peran" placeholder="Contoh: auditor, kasir_cabang" required 
                                   style="width:100%;height:44px;padding:0 14px;border:1px solid var(--color-hairline);border-radius:12px;background:var(--color-canvas);font-size:0.88rem;color:var(--color-ink);font-family:monospace;outline:none;">
                            <div style="font-size:0.78rem;color:var(--color-ink-mute);margin-top:6px;display:flex;align-items:center;gap:5px;">
                                <i data-lucide="info" style="width:13px;height:13px;color:var(--iz-primary, #2563eb);flex-shrink:0;"></i>
                                <span>Sistem akan membuat slug teknis otomatis (huruf kecil & underscore).</span>
                            </div>
                        </div>

                        <div>
                            <label style="display:block;font-size:0.85rem;font-weight:700;color:var(--color-ink);margin-bottom:7px;">
                                Deskripsi Wewenang <span style="font-size:0.78rem;color:var(--color-ink-mute);font-weight:400;">(Opsional)</span>
                            </label>
                            <textarea name="deskripsi" rows="3" placeholder="Keterangan singkat cakupan tanggung jawab peran ini..." 
                                      style="width:100%;padding:12px 14px;border:1px solid var(--color-hairline);border-radius:12px;background:var(--color-canvas);font-size:0.88rem;color:var(--color-ink);outline:none;resize:vertical;"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="addRoleModalOpen = false" class="btn btn-secondary modal-btn-cancel-desktop">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary w-full sm:w-auto" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;">
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
        <div x-show="editRoleModalOpen" x-cloak class="modal-backdrop" @click="editRoleModalOpen = false" style="display:none;">
            <div class="modal-box" style="max-width:540px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>
                
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:40px;height:40px;border-radius:12px;background:rgba(37,99,235,0.12);color:var(--iz-primary, #2563eb);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="edit-3" style="width:20px;height:20px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title">Edit Deskripsi Role</div>
                            <div style="font-size:12px;color:var(--color-ink-mute);margin-top:1px;">Perbarui keterangan wewenang dan cakupan fungsi jabatan</div>
                        </div>
                    </div>
                    <button type="button" @click="editRoleModalOpen = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/permissions/update-role') ?>" method="POST" data-action-text="Memperbarui deskripsi role...">
                    <input type="hidden" name="id" :value="editRole.id">

                    <div class="modal-body custom-scrollbar space-y-4">
                        <div>
                            <label style="display:block;font-size:0.85rem;font-weight:700;color:var(--color-ink);margin-bottom:7px;">
                                ID Sistem Role <span style="font-size:0.76rem;color:var(--color-ink-mute);font-weight:400;">(Terkunci)</span>
                            </label>
                            <input type="text" :value="editRole.nama_peran" readonly 
                                   style="width:100%;height:44px;padding:0 14px;border:1px solid var(--color-hairline);border-radius:12px;background:var(--color-canvas-soft);font-size:0.88rem;color:var(--color-ink-mute);font-family:monospace;outline:none;cursor:not-allowed;">
                            <div style="font-size:0.78rem;color:#e11d48;margin-top:6px;display:flex;align-items:center;gap:5px;">
                                <i data-lucide="lock" style="width:13px;height:13px;flex-shrink:0;"></i>
                                <span>ID teknis tidak dapat diubah untuk menjaga integritas relasi basis data.</span>
                            </div>
                        </div>

                        <div>
                            <label style="display:block;font-size:0.85rem;font-weight:700;color:var(--color-ink);margin-bottom:7px;">
                                Deskripsi Wewenang <span style="font-size:0.78rem;color:var(--color-ink-mute);font-weight:400;">(Opsional)</span>
                            </label>
                            <textarea name="deskripsi" rows="3" x-model="editRole.deskripsi" placeholder="Keterangan cakupan tanggung jawab peran ini..." 
                                      style="width:100%;padding:12px 14px;border:1px solid var(--color-hairline);border-radius:12px;background:var(--color-canvas);font-size:0.88rem;color:var(--color-ink);outline:none;resize:vertical;"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="editRoleModalOpen = false" class="btn btn-secondary modal-btn-cancel-desktop">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary w-full sm:w-auto" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;">
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
        <div x-show="deleteRoleModalOpen" x-cloak class="modal-backdrop" @click="deleteRoleModalOpen = false" style="display:none;">
            <div class="modal-box" style="max-width:460px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>

                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:40px;height:40px;border-radius:12px;background:rgba(239,68,68,0.12);color:#ef4444;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="trash-2" style="width:20px;height:20px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title" style="color:var(--color-danger);">Hapus Role?</div>
                            <div style="font-size:12px;color:var(--color-ink-mute);margin-top:1px;">Penghapusan jabatan sistem secara permanen</div>
                        </div>
                    </div>
                    <button type="button" @click="deleteRoleModalOpen = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <div class="modal-body custom-scrollbar space-y-4">
                    <p style="font-size:0.86rem;color:var(--color-ink-mute);margin:0;line-height:1.55;">
                        Role <strong style="color:var(--color-ink);" x-text="'«' + deleteRoleTarget.nama_peran + '»'"></strong> akan dihapus permanen dari sistem.
                        User yang sudah terlanjur memakai role ini bisa kehilangan akses standarnya.
                    </p>

                    <!-- Warning box -->
                    <div style="background:#fef9ec;border:1.5px solid #fde68a;border-radius:12px;padding:12px 16px;display:flex;align-items:flex-start;gap:10px;">
                        <i data-lucide="alert-triangle" style="width:17px;height:17px;color:#d97706;flex-shrink:0;margin-top:1px;"></i>
                        <p style="font-size:0.82rem;color:#92400e;margin:0;line-height:1.5;">
                            Tindakan ini <strong>tidak dapat dibatalkan</strong>. Pastikan tidak ada user aktif yang bergantung pada role ini sebelum menghapus.
                        </p>
                    </div>
                </div>

                <!-- Action buttons -->
                <form action="<?= Router::url('/permissions/delete-role') ?>" method="POST" data-action-text="Menghapus role...">
                    <input type="hidden" name="id" :value="deleteRoleTarget.id">
                    <div class="modal-footer">
                        <button type="button" @click="deleteRoleModalOpen = false" class="btn btn-secondary modal-btn-cancel-desktop">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-danger w-full sm:w-auto" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;">
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
        <div x-show="viewUsersRoleModalOpen" x-cloak class="modal-backdrop" @click="viewUsersRoleModalOpen = false" style="display:none;" @keydown.escape.window="viewUsersRoleModalOpen = false">
            <div class="modal-box" style="max-width:440px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>

                <!-- Header -->
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:38px;height:38px;border-radius:12px;background:rgba(37,99,235,0.1);color:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="users" style="width:18px;height:18px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title" x-text="'Pengguna: ' + viewUsersRoleTarget.nama_peran"></div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);" x-text="viewUsersRoleTarget.users.length + ' akun terdaftar'"></div>
                        </div>
                    </div>
                    <button type="button" @click="viewUsersRoleModalOpen = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <!-- List (scrollable) -->
                <div class="modal-body custom-scrollbar" style="max-height:360px;padding:8px 16px;">

                    <!-- Empty -->
                    <template x-if="viewUsersRoleTarget.users.length === 0">
                        <div style="padding:32px 20px;text-align:center;">
                            <i data-lucide="user-x" style="width:28px;height:28px;margin:0 auto 8px auto;color:var(--color-ink-mute);opacity:0.5;"></i>
                            <p style="font-size:0.85rem;color:var(--color-ink-mute);margin:0;">Tidak ada pengguna aktif pada role ini.</p>
                        </div>
                    </template>

                    <!-- Rows -->
                    <template x-if="viewUsersRoleTarget.users.length > 0">
                        <div class="divide-y divide-[var(--color-hairline)]">
                            <template x-for="(user, i) in viewUsersRoleTarget.users" :key="user.id">
                                <div style="display:flex;align-items:center;gap:10px;padding:10px 0;">
                                    <!-- Avatar -->
                                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(37,99,235,0.1);color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:0.82rem;font-weight:700;flex-shrink:0;"
                                         x-text="user.nama_lengkap ? user.nama_lengkap.charAt(0).toUpperCase() : '?'"></div>
                                    <!-- Info -->
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:0.85rem;font-weight:600;color:var(--color-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="user.nama_lengkap"></div>
                                        <div style="font-size:0.74rem;color:var(--color-ink-mute);font-family:var(--font-mono);" x-text="'@' + user.nama_pengguna"></div>
                                    </div>
                                    <!-- Status dot -->
                                    <template x-if="user.status_aktif">
                                        <span class="badge" style="background:rgba(16,185,129,0.12);color:#059669;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700;">Aktif</span>
                                    </template>
                                    <template x-if="!user.status_aktif">
                                        <span class="badge" style="background:rgba(239,68,68,0.12);color:#dc2626;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700;">Nonaktif</span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                </div>

                <!-- Footer legend -->
                <div class="modal-footer" style="justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.73rem;color:var(--color-ink-mute);">
                            <span style="width:7px;height:7px;border-radius:50%;background:#22c55e;"></span> Aktif
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.73rem;color:var(--color-ink-mute);">
                            <span style="width:7px;height:7px;border-radius:50%;background:#f87171;"></span> Nonaktif
                        </span>
                    </div>
                    <button type="button" @click="viewUsersRoleModalOpen = false" class="btn btn-secondary btn-sm">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    </template>

</div>

<?php 
use App\Core\Router; 

$allPermissionsFlat = [];
$grupList = [];
foreach (($matrixGroupedPerms ?? $permissions ?? []) as $grupName => $pList) {
    if (!in_array($grupName, $grupList, true)) {
        $grupList[] = $grupName;
    }
    foreach ($pList as $p) {
        $allPermissionsFlat[] = $p;
    }
}
$totalPermsCount = count($allPermissionsFlat);
$totalGroupsCount = count($grupList);
$totalRolesNum = $totalRolesCount ?? count($roles ?? []);
$totalUsersNum = $totalUsersCount ?? count($groupedUsers ?? []);
?>

<!-- ========================================================================= -->
<!-- TAB 4: DAFTAR & MATRIKS IZIN (SUPERIOR UI/UX STANDARDIZED)                -->
<!-- ========================================================================= -->
<div class="space-y-4">
    
    <!-- 1. Statistik Ringkas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 20px;">
        <div class="stat-card-pro">
            <div class="stat-icon-pro" style="width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; color: var(--iz-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i data-lucide="shield-check" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <div class="stat-lbl-pro" style="font-size: 0.74rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Total Tiket Izin</div>
                <div class="stat-val-pro" style="font-size: 1.3rem; font-weight: 700; color: #0f172a; line-height: 1.2; margin-top: 2px;">
                    <?= $totalPermsCount ?> <span style="font-size: 0.75rem; color: #64748b; font-weight: 500;">izin</span>
                </div>
            </div>
        </div>

        <div class="stat-card-pro">
            <div class="stat-icon-pro" style="width: 44px; height: 44px; border-radius: 12px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i data-lucide="folder" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <div class="stat-lbl-pro" style="font-size: 0.74rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Kategori Modul</div>
                <div class="stat-val-pro" style="font-size: 1.3rem; font-weight: 700; color: #0f172a; line-height: 1.2; margin-top: 2px;">
                    <?= $totalGroupsCount ?> <span style="font-size: 0.75rem; color: #64748b; font-weight: 500;">grup</span>
                </div>
            </div>
        </div>

        <div class="stat-card-pro">
            <div class="stat-icon-pro" style="width: 44px; height: 44px; border-radius: 12px; background: #fffbeb; color: #d97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i data-lucide="tags" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <div class="stat-lbl-pro" style="font-size: 0.74rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Role Terdaftar</div>
                <div class="stat-val-pro" style="font-size: 1.3rem; font-weight: 700; color: #0f172a; line-height: 1.2; margin-top: 2px;">
                    <?= $totalRolesNum ?> <span style="font-size: 0.75rem; color: #64748b; font-weight: 500;">role</span>
                </div>
            </div>
        </div>

        <div class="stat-card-pro">
            <div class="stat-icon-pro" style="width: 44px; height: 44px; border-radius: 12px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i data-lucide="users" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <div class="stat-lbl-pro" style="font-size: 0.74rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Pengguna Aktif</div>
                <div class="stat-val-pro" style="font-size: 1.3rem; font-weight: 700; color: #0f172a; line-height: 1.2; margin-top: 2px;">
                    <?= $totalUsersNum ?> <span style="font-size: 0.75rem; color: #64748b; font-weight: 500;">user</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Toolbar Pencarian & Filter Kategori Pills -->
    <div class="filter-panel-pro" style="background: #ffffff; border: 1.5px solid #edf2f7; border-radius: 18px; padding: 18px 20px; box-shadow: 0 2px 8px rgba(15,23,42,0.03); margin-bottom: 24px;">
        <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3 mb-3" style="display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 14px;">
            <div class="search-wrapper-pro" style="position: relative; flex: 1; min-width: 260px; width: 100%;">
                <i data-lucide="search" class="search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #94a3b8; pointer-events: none;"></i>
                <input type="text" id="liveSearchMatrix" placeholder="Cari nama izin, kode teknis (misal: inventory.opname), role, atau user..." autocomplete="off" oninput="applyMatrixFilters()"
                       style="width: 100%; border-radius: 12px; padding: 0.6rem 2.4rem 0.6rem 2.6rem; font-size: 0.88rem; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; outline: none; transition: all 0.2s;">
                <i data-lucide="x" class="clear-icon" id="clearMatrixSearchBtn" onclick="clearMatrixSearch()" title="Hapus pencarian" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #94a3b8; cursor: pointer; display: none;"></i>
            </div>
            
            <div class="d-flex align-items-center gap-2" style="display: flex; align-items: center; gap: 8px;">
                <span class="badge" style="background: #f8fafc; color: #475569; border: 1.5px solid #e2e8f0; font-size: 0.78rem; font-weight: 600; padding: 7px 14px; border-radius: 20px;">
                    Menampilkan <strong id="matrixVisibleCount" class="text-dark" style="color: #0f172a;"><?= $totalPermsCount ?></strong> izin
                </span>
            </div>
        </div>

        <!-- Filter Category Pills Horizontal Scroll Track -->
        <div class="cat-scroll-track" style="display: flex; align-items: center; gap: 8px; overflow-x: auto; padding-bottom: 4px; padding-top: 2px;">
            <button type="button" class="cat-chip active" data-cat="all" onclick="filterMatrixCat('all', this)">
                <i data-lucide="layout-grid" style="width: 14px; height: 14px;"></i>
                <span>Semua Grup</span>
                <span class="count-badge"><?= $totalPermsCount ?></span>
            </button>
            <?php foreach ($grupList as $g): 
                $cInGrup = count($matrixGroupedPerms[$g] ?? $permissions[$g] ?? []);
            ?>
                <button type="button" class="cat-chip" data-cat="<?= htmlspecialchars($g) ?>" onclick="filterMatrixCat('<?= htmlspecialchars(addslashes($g)) ?>', this)">
                    <span><?= htmlspecialchars($g) ?></span>
                    <span class="count-badge"><?= $cInGrup ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 3. Cards Grid View (Plek Ketiplek & Responsif) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="matrixCardsContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
        <?php foreach ($allPermissionsFlat as $perm): 
            $permId = (string)$perm['id'];
            $rolesGranted = $perm['roles'] ?? [];
            $usersAllow = $perm['users_allow'] ?? [];
            $usersDeny = $perm['users_deny'] ?? [];
            $totalOverrides = count($usersAllow) + count($usersDeny);
            
            $searchContent = strtolower(($perm['nama_izin'] ?? '') . ' ' . ($perm['deskripsi'] ?? '') . ' ' . ($perm['kode_izin'] ?? '') . ' ' . ($perm['grup_izin'] ?? ''));
            foreach ($rolesGranted as $rKey) $searchContent .= ' ' . strtolower($rKey);
            foreach ($usersAllow as $uObj) $searchContent .= ' ' . strtolower($uObj['nama_pengguna'] ?? '');
            foreach ($usersDeny as $uObj) $searchContent .= ' ' . strtolower($uObj['nama_pengguna'] ?? '');
        ?>
            <div class="perm-card-item" data-group="<?= htmlspecialchars($perm['grup_izin'] ?? '') ?>" data-search="<?= htmlspecialchars($searchContent) ?>" style="min-width: 0;">
                <div class="perm-card-pro" style="background: #ffffff; border: 1.5px solid #edf2f7; border-radius: 16px; height: 100%; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: all 0.2s ease;">
                    
                    <!-- Header Kartu -->
                    <div class="perm-card-pro-header" style="padding: 16px 20px; border-bottom: 1.5px solid #f8fafc; background: #ffffff;">
                        <h5 class="fw-bold text-dark mb-2" style="font-size: 0.96rem; font-weight: 700; color: #0f172a; line-height: 1.35; margin: 0 0 10px 0;">
                            <?= htmlspecialchars($perm['nama_izin'] ?: ($perm['deskripsi'] ?? $perm['kode_izin'])) ?>
                        </h5>
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                            <span class="perm-code-tag" style="white-space: nowrap; word-break: keep-all; font-family: monospace; font-size: 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px;">
                                <i data-lucide="code" style="width: 12px; height: 12px; opacity: 0.6; flex-shrink: 0;"></i>
                                <span style="font-family: monospace;"><?= htmlspecialchars($perm['kode_izin']) ?></span>
                            </span>
                            <span class="badge" style="background: #eff6ff; color: var(--iz-primary); border: 1px solid #dbeafe; font-size: 0.72rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px;">
                                <i data-lucide="folder" style="width: 11px; height: 11px; flex-shrink: 0;"></i>
                                <span><?= htmlspecialchars($perm['grup_izin']) ?></span>
                            </span>
                        </div>
                    </div>

                    <!-- Body Kartu -->
                    <div class="perm-card-pro-body" style="padding: 16px 20px; flex: 1; display: flex; flex-direction: column; gap: 14px; background: #ffffff;">
                        
                        <!-- Bagian 1: Role Default -->
                        <div>
                            <div class="section-label-pro" style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center;">
                                <span><i data-lucide="shield" style="width: 12px; height: 12px; display: inline-block; vertical-align: -1px; color: var(--iz-primary);"></i> Role Bawaan</span>
                                <span class="badge" style="background:#f1f5f9; color:#475569; font-size: 0.68rem; padding: 2px 6px; border-radius: 6px;"><?= count($rolesGranted) + 1 ?> Role</span>
                            </div>
                            <div class="d-flex flex-wrap gap-1" style="display: flex; flex-wrap: wrap; gap: 6px;">
                                <span class="chip-role-default chip-role-admin" style="background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; padding: 3px 8px; border-radius: 6px; font-size: 0.74rem; font-weight: 600;" title="Developer selalu bypass hak akses">
                                    👑 Developer
                                </span>
                                <?php if (!empty($rolesGranted)): ?>
                                    <?php foreach ($rolesGranted as $rName): ?>
                                        <span class="chip-role-default" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 3px 8px; border-radius: 6px; font-size: 0.74rem; font-weight: 600;">
                                            💼 <?= htmlspecialchars(ucfirst($rName)) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr style="border: none; border-top: 1.5px solid #f1f5f9; margin: 0;">

                        <!-- Bagian 2: Pengguna Khusus (Overrides: Allow / Deny) -->
                        <div>
                            <div class="section-label-pro" style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center;">
                                <span><i data-lucide="sliders" style="width: 12px; height: 12px; display: inline-block; vertical-align: -1px; color: #f59e0b;"></i> Izin Khusus User</span>
                                <span class="badge" style="background:#f1f5f9; color:#475569; font-size: 0.68rem; padding: 2px 6px; border-radius: 6px;"><?= $totalOverrides ?> Kustom</span>
                            </div>
                            
                            <?php if ($totalOverrides > 0): ?>
                                <div class="d-flex flex-wrap gap-1" style="display: flex; flex-wrap: wrap; gap: 6px;">
                                    <?php foreach ($usersAllow as $u): ?>
                                        <span class="chip-user-pro" style="background:#f0fdf4; border: 1px solid #bbf7d0; color:#15803d; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;" title="Izin Tambahan Khusus (+ Allow)">
                                            <span style="color:#16a34a; font-weight:bold;">+</span> @<?= htmlspecialchars($u['nama_pengguna']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php foreach ($usersDeny as $u): ?>
                                        <span class="chip-user-pro" style="background:#fef2f2; border: 1px solid #fecaca; color:#b91c1c; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;" title="Izin Dicabut Khusus (- Deny)">
                                            <span style="color:#dc2626; font-weight:bold;">-</span> <span style="text-decoration: line-through;">@<?= htmlspecialchars($u['nama_pengguna']) ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small py-1" style="font-size: 0.75rem; color: #94a3b8; display: flex; align-items: center; gap: 5px;">
                                    <i data-lucide="check-circle" style="width: 13px; height: 13px; color: #16a34a; flex-shrink: 0;"></i>
                                    <span>100% mengikuti aturan role (tidak ada pengecualian).</span>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
    var currentMatrixCat = 'all';

    function filterMatrixCat(cat, btn) {
        currentMatrixCat = cat;
        document.querySelectorAll('.cat-chip').forEach(el => el.classList.remove('active'));
        if (btn) btn.classList.add('active');
        applyMatrixFilters();
    }

    function applyMatrixFilters() {
        var query = (document.getElementById('liveSearchMatrix').value || '').toLowerCase().trim();
        var clearBtn = document.getElementById('clearMatrixSearchBtn');
        if (clearBtn) clearBtn.style.display = query ? 'block' : 'none';

        var items = document.querySelectorAll('.perm-card-item');
        var visibleCount = 0;

        items.forEach(function(item) {
            var searchData = (item.getAttribute('data-search') || '').toLowerCase();
            var grp = item.getAttribute('data-group') || '';
            var matchesQuery = !query || searchData.includes(query);
            var matchesCat = (currentMatrixCat === 'all') || (grp === currentMatrixCat);

            if (matchesQuery && matchesCat) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        document.getElementById('matrixVisibleCount').textContent = visibleCount;
    }

    function clearMatrixSearch() {
        var input = document.getElementById('liveSearchMatrix');
        if (input) {
            input.value = '';
            applyMatrixFilters();
            input.focus();
        }
    }
</script>

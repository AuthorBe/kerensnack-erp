<?php use App\Core\Router; ?>

<?php
$scopePairsMap = [
    'orders.view_all' => 'orders.view',
    'orders.view_assigned' => 'orders.view',
    'orders.edit_all' => 'orders.edit',
    'orders.edit_assigned' => 'orders.edit',
    'consignment.view_all' => 'consignment.view',
    'consignment.view_assigned' => 'consignment.view',
    'consignment.opname_all' => 'consignment.opname',
    'consignment.opname_assigned' => 'consignment.opname',
    'consignment.reports_all' => 'consignment.reports',
    'consignment.reports_assigned' => 'consignment.reports',
    'consignment.komisi_all' => 'consignment.komisi',
    'consignment.komisi_self' => 'consignment.komisi',
    'deliveries.view_all' => 'deliveries.view',
    'deliveries.view_assigned' => 'deliveries.view',
    'deliveries.update_all' => 'deliveries.update',
    'deliveries.update_assigned' => 'deliveries.update',
    'master.customers_view_all' => 'master.customers_view',
    'master.customers_view_assigned' => 'master.customers_view',
    'orders.po_view_all' => 'orders.po_view',
    'orders.po_view_assigned' => 'orders.po_view',
];

$scopeDefs = [
    'orders.po_view' => [
        'title' => 'Lihat Daftar PO Masuk',
        'desc' => 'Akses melihat daftar pesanan baru / PO yang menunggu packing gudang',
        'all_code' => 'orders.po_view_all',
        'assigned_code' => 'orders.po_view_assigned',
    ],
    'orders.view' => [
        'title' => 'Lihat Pesanan Penjualan Reguler',
        'desc' => 'Akses melihat daftar transaksi pesanan / faktur toko',
        'all_code' => 'orders.view_all',
        'assigned_code' => 'orders.view_assigned',
    ],
    'orders.edit' => [
        'title' => 'Edit Faktur Pesanan Reguler',
        'desc' => 'Mengubah item, kuantiti & diskon pesanan sebelum dikirim',
        'all_code' => 'orders.edit_all',
        'assigned_code' => 'orders.edit_assigned',
    ],
    'consignment.view' => [
        'title' => 'Monitoring Rak Konsinyasi',
        'desc' => 'Melihat portal rak toko mitra konsinyasi',
        'all_code' => 'consignment.view_all',
        'assigned_code' => 'consignment.view_assigned',
    ],
    'consignment.opname' => [
        'title' => 'Opname Fisik Rak Konsinyasi',
        'desc' => 'Menghitung stok fisik snack di rak toko mitra',
        'all_code' => 'consignment.opname_all',
        'assigned_code' => 'consignment.opname_assigned',
    ],
    'consignment.reports' => [
        'title' => 'Laporan Laku Titip Konsinyasi',
        'desc' => 'Melihat rekap omset dan kuantiti laku titip toko',
        'all_code' => 'consignment.reports_all',
        'assigned_code' => 'consignment.reports_assigned',
    ],
    'consignment.komisi' => [
        'title' => 'Rekap Komisi Penjualan Konsinyasi',
        'desc' => 'Melihat perhitungan komisi penjualan titip rak',
        'all_code' => 'consignment.komisi_all',
        'assigned_code' => 'consignment.komisi_self',
    ],
    'deliveries.view' => [
        'title' => 'Lihat Surat Jalan Pengiriman',
        'desc' => 'Memantau jadwal kirim armada dan manifest pengiriman',
        'all_code' => 'deliveries.view_all',
        'assigned_code' => 'deliveries.view_assigned',
    ],
    'deliveries.update' => [
        'title' => 'Update Status & Upload Bukti POD',
        'desc' => 'Mengubah status pengiriman dan upload bukti terima toko',
        'all_code' => 'deliveries.update_all',
        'assigned_code' => 'deliveries.update_assigned',
    ],
    'master.customers_view' => [
        'title' => 'Lihat Direktori Toko Pelanggan',
        'desc' => 'Melihat direktori database profil mitra toko',
        'all_code' => 'master.customers_view_all',
        'assigned_code' => 'master.customers_view_assigned',
    ],
];
?>

<style>
.scope-segmented-group {
    display: flex;
    gap: 4px;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}
.scope-pill-btn {
    flex: 1;
    text-align: center;
    font-size: 0.74rem;
    font-weight: 700;
    padding: 6px 4px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 3px;
    transition: all 0.15s ease;
    color: #64748b;
    border: 1px solid transparent;
}
.scope-pill-btn:hover {
    background: rgba(255, 255, 255, 0.6);
    color: #1e293b;
}
.scope-pill-btn.active-all {
    background: #1d4ed8 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(29, 78, 216, 0.25);
}
.scope-pill-btn.active-assigned {
    background: #0284c7 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(2, 132, 199, 0.25);
}
.scope-pill-btn.active-none {
    background: #ffffff !important;
    color: #64748b !important;
    border: 1px solid #cbd5e1 !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
</style>

<!-- ========================================================================= -->
<!-- TAB 2: DEFAULT ROLE (SMART 3-STATE SCOPE & GRANULAR MATRIX)               -->
<!-- ========================================================================= -->
<div class="space-y-4">
    
    <!-- 1. Form Pilih Role -->
    <div class="card shadow-sm border-0 rounded-4 mb-4 role-picker-card" style="padding: 20px; margin-bottom: 20px;">
        <form method="GET" action="<?= Router::url('/permissions') ?>" id="formSelectRole">
            <input type="hidden" name="tab" value="role">
            <label class="fw-bold text-dark mb-2.5 d-flex align-items-center gap-2" style="font-size: 0.95rem; font-weight: 700; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: #eff6ff; color: var(--iz-primary); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                    <i data-lucide="shield" style="width: 16px; height: 16px;"></i>
                </span>
                <span>Pilih Role (Jabatan)</span>
            </label>
            <div>
                <select class="form-select" name="role_id" id="role_select" onchange="this.form.submit()" 
                        style="width: 100%; height: 50px; border-radius: 14px; border: 1.5px solid #e2e8f0; background-color: #ffffff; padding: 0.55rem 1.1rem; font-size: 0.88rem; font-weight: 600; color: #1e293b; outline: none; cursor: pointer;">
                    <option value="">-- Pilih role / jabatan --</option>
                    <?php foreach ($roles as $r): ?>
                        <?php if ($r['nama_peran'] === 'developer') continue; ?>
                        <option value="<?= htmlspecialchars($r['id']) ?>" <?= ($selectedRoleId === (string)$r['id']) ? 'selected' : '' ?>>
                            💼 Role: <?= htmlspecialchars(ucfirst($r['nama_peran'])) ?> (<?= htmlspecialchars($r['nama_peran']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if ($selectedRoleId && $selectedRole): ?>
        <?php if ($selectedRole['nama_peran'] === 'developer'): ?>
            <!-- Developer Super Admin Notice -->
            <div class="card shadow-sm border-0 rounded-4 p-5 text-center my-4" style="background: #ffffff; border-radius: 16px; border: 1.5px solid #edf2f7; padding: 40px 20px; text-align: center;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 mx-auto" style="width: 64px; height: 64px; border-radius: 50%; background: #eff6ff; color: var(--iz-primary); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <i data-lucide="terminal" style="width: 32px; height: 32px;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1" style="font-weight: 700; color: #0f172a; font-size: 1.15rem;">Peran Developer (Super Administrator)</h5>
                <p class="text-muted small mb-0" style="color: #64748b; font-size: 0.88rem; max-width: 500px; margin: 0 auto;">
                    Peran developer memiliki hak akses universal bypass (<code>*</code>) ke seluruh modul, controller, dan fitur sistem tanpa batasan matriks perizinan.
                </p>
            </div>
        <?php else: ?>
            <!-- 2. Info Banner Role Terpilih -->
            <div class="card shadow-sm border-0 rounded-4 mb-4 role-info-banner" style="background: #ffffff; padding: 18px 24px; margin-bottom: 20px;">
                <div class="d-flex align-items-center gap-3" style="display: flex; align-items: center; gap: 14px;">
                    <div class="role-avatar-circle flex-shrink-0">
                        <i data-lucide="shield-check" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div class="min-w-0 flex-grow-1">
                        <h5 class="fw-bold mb-1 text-dark text-truncate" style="font-size: 1.1rem; font-weight: 700; margin: 0 0 4px 0; color: #0f172a;">
                            Role: <?= htmlspecialchars(ucfirst($selectedRole['nama_peran'])) ?> 
                            <span class="text-muted fw-normal font-monospace" style="font-size: 0.82rem; color: #64748b; font-weight: 400;">(<?= htmlspecialchars($selectedRole['nama_peran']) ?>)</span>
                        </h5>
                        <div class="d-flex flex-wrap align-items-center gap-1.5" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span class="badge" id="bannerActiveCountBadge" style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-size:0.75rem; font-weight:600; padding: 3px 8px; border-radius: 8px; display: inline-flex; align-items: center; gap: 4px;">
                                <i data-lucide="check-circle-2" style="width: 13px; height: 13px;"></i> 
                                <span id="bannerActiveCountText"><?= $tabRoleActiveCount ?> Izin Aktif</span>
                            </span>
                            <span class="badge" style="background:#f8fafc; color:#64748b; border:1px solid #e2e8f0; font-size:0.72rem; font-weight:500; padding: 3px 8px; border-radius: 8px; display: inline-flex; align-items: center; gap: 4px;">
                                <i data-lucide="info" style="width: 13px; height: 13px;"></i> Berlaku otomatis ke semua user dengan role ini
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Filter & Search Toolbar -->
            <div class="card shadow-sm border-0 rounded-4 mb-4" style="background: #ffffff; border: 1.5px solid #edf2f7; border-radius: 16px; padding: 14px 18px; margin-bottom: 20px;">
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3" style="display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap;">
                    
                    <div class="perm-search-box" style="position: relative; flex: 1; min-width: 260px; width: 100%;">
                        <i data-lucide="search" class="search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #94a3b8; pointer-events: none;"></i>
                        <input type="text" id="filterInputRole" placeholder="Cari nama izin atau kode teknis (misal: inventori, pos)..." autocomplete="off" oninput="applyFiltersRole()"
                               style="width: 100%; border-radius: 12px; padding: 0.55rem 2.4rem 0.55rem 2.6rem; font-size: 0.86rem; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; outline: none;">
                        <i data-lucide="x" class="clear-btn" id="clearFilterBtnRole" onclick="clearFilterRole()" title="Hapus filter" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #94a3b8; cursor: pointer; display: none;"></i>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap" style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" class="filter-btn-pill active" id="btnFilterAllRole" onclick="setFilterModeRole('all')">
                            <i data-lucide="list" style="width: 14px; height: 14px;"></i>
                            <span>Semua Izin</span>
                            <span class="badge-count" id="totalVisibleCountRole"><?= $totalPermCount ?></span>
                        </button>
                        <button type="button" class="filter-btn-pill" id="btnFilterActiveRole" onclick="setFilterModeRole('active')" title="Filter hanya izin yang sedang aktif pada role ini">
                            <i data-lucide="check-circle-2" style="width: 14px; height: 14px;"></i>
                            <span>Izin Aktif</span>
                            <span class="badge-count" id="activeFilterCountRole"><?= $tabRoleActiveCount ?></span>
                        </button>
                    </div>

                </div>
            </div>

            <!-- 4. Form Simpan Izin Role -->
            <form action="<?= Router::url('/permissions/save-role-permissions') ?>" method="POST" id="form-role-permissions">
                <input type="hidden" name="role_id" value="<?= htmlspecialchars($selectedRoleId) ?>">

                <!-- Empty State jika Pencarian Tidak Ada -->
                <div id="noSearchPermsStateRole" class="card shadow-sm border-0 rounded-4 p-5 text-center my-4" style="display: none; background: #ffffff; border-radius: 16px; border: 1.5px solid #edf2f7; padding: 50px 20px; text-align: center; margin: 20px 0;">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 mx-auto" style="width: 64px; height: 64px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                        <i data-lucide="search" style="width: 32px; height: 32px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1" style="font-weight: 700; color: #0f172a; margin-bottom: 4px;">Tidak Ada Izin yang Cocok</h5>
                    <p class="text-muted small mb-0" style="color: #64748b; font-size: 0.85rem;">Coba gunakan kata kunci pencarian yang lain.</p>
                </div>

                <!-- Group Cards Grid (2-Column Responsive) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="permGroupsContainerRole" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px;">
                    <?php foreach ($permissions as $grup => $items): ?>
                        <div class="perm-group-wrapper" data-group="<?= htmlspecialchars($grup) ?>" style="min-width: 0;">
                            <div class="card shadow-sm border-0 rounded-4 h-100" style="position: relative; overflow: hidden; background: #ffffff; border-radius: 16px; border: 1.5px solid #edf2f7; height: 100%;">
                                
                                <!-- Top Accent Bar -->
                                <div class="group-accent-line"></div>

                                <div class="card-header bg-white border-bottom-0 pt-4 pb-2" style="padding: 18px 20px 10px 20px; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                                    <div class="d-flex align-items-center gap-2" style="display: flex; align-items: center; gap: 8px;">
                                        <i data-lucide="folder" style="width: 20px; height: 20px; color: #f59e0b;"></i>
                                        <h6 class="fw-bold mb-0 text-dark" style="font-size: 1.05rem; font-weight: 700; margin: 0; color: #0f172a;">
                                            <?= htmlspecialchars($grup) ?>
                                        </h6>
                                    </div>
                                    <span class="badge" style="background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; font-size: 0.72rem; font-weight: 600; padding: 3px 8px; border-radius: 8px;">
                                        <?= count($items) ?> Izin
                                    </span>
                                </div>
                                
                                <div class="card-body pt-2 pb-4" style="padding: 10px 20px 20px 20px;">
                                    <div class="d-flex flex-column gap-2" style="display: flex; flex-direction: column; gap: 10px;">
                                        <?php 
                                        $renderedScopes = [];
                                        foreach ($items as $perm): 
                                            $permId = (string)$perm['id'];
                                            $code = $perm['kode_izin'];

                                            // A. JIKA INI MERUPAKAN IZIN BERPASANGAN DENGAN SCOPE
                                            if (isset($scopePairsMap[$code])):
                                                $scopeKey = $scopePairsMap[$code];
                                                if (isset($renderedScopes[$scopeKey])) {
                                                    continue; // Sudah dirender pada kartu scope gabungan
                                                }
                                                $renderedScopes[$scopeKey] = true;

                                                $def = $scopeDefs[$scopeKey];
                                                $allPerm = null;
                                                $assignedPerm = null;
                                                foreach ($items as $it) {
                                                    if ($it['kode_izin'] === $def['all_code']) $allPerm = $it;
                                                    if ($it['kode_izin'] === $def['assigned_code']) $assignedPerm = $it;
                                                }

                                                $allId = (string)($allPerm['id'] ?? '');
                                                $assignedId = (string)($assignedPerm['id'] ?? '');

                                                $isAllChecked = $allId && in_array($allId, $tabRoleActivePerms, true);
                                                $isAssignedChecked = $assignedId && in_array($assignedId, $tabRoleActivePerms, true);

                                                $currentScope = 'none';
                                                if ($isAllChecked) $currentScope = 'all';
                                                elseif ($isAssignedChecked) $currentScope = 'assigned';

                                                $isScopeActive = ($currentScope !== 'none');
                                                $searchKeywords = strtolower($def['title'] . ' ' . $def['desc'] . ' ' . $def['all_code'] . ' ' . $def['assigned_code'] . ' ' . $grup);
                                        ?>
                                            <div class="perm-item-box perm-scope-box <?= $isScopeActive ? 'is-active' : '' ?>" id="scope_box_<?= $scopeKey ?>" data-search="<?= htmlspecialchars($searchKeywords) ?>" data-active="<?= $isScopeActive ? '1' : '0' ?>">
                                                <div style="margin-bottom: 8px;">
                                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                                        <span class="fw-bold text-dark" style="font-size: 0.91rem; line-height: 1.35; font-weight: 700; color: #0f172a;">
                                                            <?= htmlspecialchars($def['title']) ?>
                                                        </span>
                                                        <span class="perm-code-tag" style="font-size: 0.68rem;">
                                                            <?= htmlspecialchars($scopeKey) ?>.*
                                                        </span>
                                                    </div>
                                                    <div class="text-muted small" style="font-size: 0.78rem; color: #64748b; margin-top: 2px;">
                                                        <?= htmlspecialchars($def['desc']) ?>
                                                    </div>
                                                </div>

                                                <!-- 3-State Segmented Radio Pill Controls -->
                                                <div class="scope-segmented-group" id="segmented_<?= $scopeKey ?>">
                                                    <label class="scope-pill-btn <?= ($currentScope === 'all') ? 'active-all' : '' ?>" id="pill_<?= $scopeKey ?>_all">
                                                        <input type="radio" name="scope_radio_<?= $scopeKey ?>" value="all" <?= ($currentScope === 'all') ? 'checked' : '' ?> onchange="onRoleScopeChange('<?= $scopeKey ?>', '<?= $allId ?>', '<?= $assignedId ?>', 'all')" style="display: none;">
                                                        <span>🌐 Semua Data</span>
                                                    </label>
                                                    <label class="scope-pill-btn <?= ($currentScope === 'assigned') ? 'active-assigned' : '' ?>" id="pill_<?= $scopeKey ?>_assigned">
                                                        <input type="radio" name="scope_radio_<?= $scopeKey ?>" value="assigned" <?= ($currentScope === 'assigned') ? 'checked' : '' ?> onchange="onRoleScopeChange('<?= $scopeKey ?>', '<?= $allId ?>', '<?= $assignedId ?>', 'assigned')" style="display: none;">
                                                        <span>👤 Toko Binaan</span>
                                                    </label>
                                                    <label class="scope-pill-btn <?= ($currentScope === 'none') ? 'active-none' : '' ?>" id="pill_<?= $scopeKey ?>_none">
                                                        <input type="radio" name="scope_radio_<?= $scopeKey ?>" value="none" <?= ($currentScope === 'none') ? 'checked' : '' ?> onchange="onRoleScopeChange('<?= $scopeKey ?>', '<?= $allId ?>', '<?= $assignedId ?>', 'none')" style="display: none;">
                                                        <span>❌ Nonaktif</span>
                                                    </label>
                                                </div>

                                                <!-- Hidden Checkboxes for Backend Form Post -->
                                                <input type="checkbox" name="permissions[]" value="<?= $allId ?>" id="role_perm_<?= $allId ?>" <?= $isAllChecked ? 'checked' : '' ?> style="display: none;">
                                                <input type="checkbox" name="permissions[]" value="<?= $assignedId ?>" id="role_perm_<?= $assignedId ?>" <?= $isAssignedChecked ? 'checked' : '' ?> style="display: none;">
                                            </div>
                                        <?php 
                                            continue;
                                            endif;

                                            // B. IZIN STANDALONE / REGULER (Single Toggle Switch)
                                            $isChecked = in_array($permId, $tabRoleActivePerms, true);
                                            $searchKeywords = strtolower(($perm['deskripsi'] ?? '') . ' ' . ($perm['nama_izin'] ?? '') . ' ' . ($perm['kode_izin'] ?? '') . ' ' . $grup);
                                        ?>
                                            <div class="perm-item-box <?= $isChecked ? 'is-active' : '' ?>" id="box_role_perm_<?= $permId ?>" data-search="<?= htmlspecialchars($searchKeywords) ?>" data-active="<?= $isChecked ? '1' : '0' ?>">
                                                <div class="d-flex align-items-start justify-content-between gap-3" style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                                                    
                                                    <label class="form-check-label w-100 m-0" for="role_perm_<?= $permId ?>" style="cursor: pointer; flex: 1; margin: 0;">
                                                        <div class="fw-bold text-dark" style="font-size: 0.91rem; line-height: 1.35; font-weight: 700; color: #0f172a;">
                                                            <?= htmlspecialchars($perm['nama_izin'] ?: ($perm['deskripsi'] ?? $perm['kode_izin'])) ?>
                                                        </div>
                                                        <div class="mt-1" style="margin-top: 4px;">
                                                            <span class="perm-code-tag">
                                                                <i data-lucide="code" style="width: 11px; height: 11px; opacity: 0.6;"></i> <?= htmlspecialchars($perm['kode_izin']) ?>
                                                            </span>
                                                        </div>
                                                    </label>

                                                    <div class="form-check form-switch pt-1 pe-1" style="margin: 0; display: flex; align-items: center;">
                                                        <input class="form-check-input" type="checkbox" role="switch" name="permissions[]" value="<?= $permId ?>" id="role_perm_<?= $permId ?>" <?= $isChecked ? 'checked' : '' ?> onchange="onRolePermToggle('<?= $permId ?>', this.checked)">
                                                    </div>

                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Bottom Action Bar (Centered Pill) -->
                <div class="mt-4 pt-3 pb-5 text-center px-1" style="margin-top: 24px; padding: 20px 0; text-align: center;">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 py-2.5 shadow-sm fw-bold" 
                            style="background: var(--iz-primary); border: none; font-size: 0.96rem; font-weight: 700; color: #ffffff; padding: 12px 36px; border-radius: 30px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3); cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;" 
                            onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                        <i data-lucide="save" style="width: 18px; height: 18px;"></i>
                        <span>Simpan Perubahan Izin Role</span>
                    </button>
                </div>

            </form>
        <?php endif; ?>
    <?php else: ?>
        <!-- Empty State -->
        <div class="card shadow-sm border-0 rounded-4 p-5 text-center my-5" style="border-radius: 16px; border: 1.5px solid #edf2f7; background: linear-gradient(180deg, #ffffff, #f8fafc); padding: 60px 20px; text-align: center; margin: 24px 0;">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4 bg-white shadow-sm mx-auto" style="width: 90px; height: 90px; border-radius: 50%; background: #ffffff; box-shadow: 0 4px 12px rgba(15,23,42,0.06); display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                <i data-lucide="shield" style="width: 44px; height: 44px; color: var(--iz-primary); opacity: 0.85;"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2" style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Pilih Role Dahulu</h4>
            <p class="text-muted" style="max-width: 500px; margin: 0 auto; color: #64748b; font-size: 0.9rem;">
                Pilih salah satu role dari dropdown di atas untuk mulai mengatur paket izin standar bawaannya.
            </p>
        </div>
    <?php endif; ?>

</div>

<!-- JS Logic for Tab Role -->
<script>
    var currentFilterModeRole = 'all'; // 'all' or 'active'

    function onRoleScopeChange(scopeKey, allId, assignedId, choice) {
        var chkAll = document.getElementById('role_perm_' + allId);
        var chkAssigned = document.getElementById('role_perm_' + assignedId);
        var box = document.getElementById('scope_box_' + scopeKey);

        var pillAll = document.getElementById('pill_' + scopeKey + '_all');
        var pillAssigned = document.getElementById('pill_' + scopeKey + '_assigned');
        var pillNone = document.getElementById('pill_' + scopeKey + '_none');

        // Reset pill classes
        if (pillAll) pillAll.className = 'scope-pill-btn';
        if (pillAssigned) pillAssigned.className = 'scope-pill-btn';
        if (pillNone) pillNone.className = 'scope-pill-btn';

        if (choice === 'all') {
            if (chkAll) chkAll.checked = true;
            if (chkAssigned) chkAssigned.checked = false;
            if (pillAll) pillAll.className = 'scope-pill-btn active-all';
            if (box) {
                box.classList.add('is-active');
                box.setAttribute('data-active', '1');
            }
        } else if (choice === 'assigned') {
            if (chkAll) chkAll.checked = false;
            if (chkAssigned) chkAssigned.checked = true;
            if (pillAssigned) pillAssigned.className = 'scope-pill-btn active-assigned';
            if (box) {
                box.classList.add('is-active');
                box.setAttribute('data-active', '1');
            }
        } else {
            if (chkAll) chkAll.checked = false;
            if (chkAssigned) chkAssigned.checked = false;
            if (pillNone) pillNone.className = 'scope-pill-btn active-none';
            if (box) {
                box.classList.remove('is-active');
                box.setAttribute('data-active', '0');
            }
        }

        updateRoleActiveCount();
        applyFiltersRole();
    }

    function onRolePermToggle(permId, isChecked) {
        var box = document.getElementById('box_role_perm_' + permId);
        if (!box) return;

        if (isChecked) {
            box.classList.add('is-active');
            box.setAttribute('data-active', '1');
        } else {
            box.classList.remove('is-active');
            box.setAttribute('data-active', '0');
        }

        updateRoleActiveCount();
        applyFiltersRole();
    }

    function updateRoleActiveCount() {
        var activeBoxes = document.querySelectorAll('#permGroupsContainerRole .perm-item-box[data-active="1"]');
        var count = activeBoxes.length;

        var countBadge = document.getElementById('activeFilterCountRole');
        if (countBadge) countBadge.textContent = count;

        var bannerText = document.getElementById('bannerActiveCountText');
        if (bannerText) bannerText.textContent = count + ' Izin Aktif';
    }

    function setFilterModeRole(mode) {
        currentFilterModeRole = mode;
        var btnAll = document.getElementById('btnFilterAllRole');
        var btnActive = document.getElementById('btnFilterActiveRole');

        if (btnAll) {
            if (mode === 'all') btnAll.classList.add('active');
            else btnAll.classList.remove('active');
        }

        if (btnActive) {
            if (mode === 'active') btnActive.classList.add('active');
            else btnActive.classList.remove('active');
        }

        applyFiltersRole();
    }

    function applyFiltersRole() {
        var filterInput = document.getElementById('filterInputRole');
        var query = filterInput ? filterInput.value.toLowerCase().trim() : '';
        var clearBtn = document.getElementById('clearFilterBtnRole');
        if (clearBtn) {
            clearBtn.style.display = query ? 'block' : 'none';
        }

        var items = document.querySelectorAll('#permGroupsContainerRole .perm-item-box');
        var visibleCount = 0;

        items.forEach(function(box) {
            var searchData = (box.getAttribute('data-search') || '').toLowerCase();
            var isActive = (box.getAttribute('data-active') === '1');

            var matchesQuery = !query || searchData.includes(query);
            var matchesMode = (currentFilterModeRole === 'all') || (currentFilterModeRole === 'active' && isActive);

            if (matchesQuery && matchesMode) {
                box.style.display = '';
                visibleCount++;
            } else {
                box.style.display = 'none';
            }
        });

        document.querySelectorAll('#permGroupsContainerRole .perm-group-wrapper').forEach(function(col) {
            var hasVisibleChild = false;
            col.querySelectorAll('.perm-item-box').forEach(function(box) {
                if (box.style.display !== 'none') {
                    hasVisibleChild = true;
                }
            });
            col.style.display = hasVisibleChild ? '' : 'none';
        });

        var countElem = document.getElementById('totalVisibleCountRole');
        if (countElem) {
            countElem.textContent = (currentFilterModeRole === 'all') ? visibleCount : items.length;
        }

        var noSearchState = document.getElementById('noSearchPermsStateRole');
        if (noSearchState) {
            noSearchState.style.display = (query && visibleCount === 0) ? 'block' : 'none';
        }
    }

    function clearFilterRole() {
        var input = document.getElementById('filterInputRole');
        if (input) {
            input.value = '';
            applyFiltersRole();
            input.focus();
        }
    }
</script>

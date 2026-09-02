<?php 
use App\Core\Router; 
?>

<?php if ($selectedUserData && !empty($permissions)): ?>
    <!-- ===================================================================== -->
    <!-- 1. INFO BANNER USER TERPILIH (1:1 REKAP-MUKHOLIF)                     -->
    <!-- ===================================================================== -->
    <div class="card shadow-sm border-0 rounded-4 mb-4 user-info-banner" style="background: #ffffff; padding: 18px 24px; margin-bottom: 20px;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
            
            <div class="d-flex align-items-center gap-3" style="display: flex; align-items: center; gap: 14px;">
                <div class="user-avatar-circle flex-shrink-0">
                    <i data-lucide="user-cog" style="width: 24px; height: 24px;"></i>
                </div>
                <div class="min-w-0">
                    <h5 class="fw-bold mb-1 text-dark text-truncate" style="font-size: 1.1rem; font-weight: 700; margin: 0 0 4px 0; color: #0f172a;">
                        <?= htmlspecialchars($selectedUserData['nama_lengkap']) ?> 
                        <span class="text-muted fw-normal" style="font-size: 0.82rem; color: #64748b; font-weight: 400;">(@<?= htmlspecialchars($selectedUserData['nama_pengguna']) ?>)</span>
                    </h5>
                    <div class="d-flex flex-wrap align-items-center gap-2" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span class="badge badge-user-role">
                            <i data-lucide="id-card" style="width: 14px; height: 14px;"></i>
                            Role: <?= htmlspecialchars(ucfirst($selectedUserData['nama_peran'])) ?>
                        </span>
                        <span class="badge" id="bannerOverrideBadge" style="<?= ($customOverrideCount > 0) ? 'background:#fffbeb; color:#b45309; border:1px solid #fde68a;' : 'background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;' ?> font-size:0.75rem; font-weight:600; padding: 3px 8px; border-radius: 8px; display: inline-flex; align-items: center; gap: 4px;">
                            <i data-lucide="sliders" style="width: 13px; height: 13px;"></i>
                            <span id="bannerOverrideText"><?= ($customOverrideCount > 0) ? "{$customOverrideCount} Izin Khusus Aktif" : "100% Mengikuti Default Role" ?></span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tombol 1-Klik Reset ke Default Role -->
            <form action="<?= Router::url('/permissions/reset-user-overrides') ?>" method="POST" id="formResetAll" class="m-0" style="margin: 0;">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($selectedUserId) ?>">
                <button type="button" class="btn-reset-overrides" onclick="confirmResetAll(event)" title="Hapus semua izin khusus dan kembalikan ke default role">
                    <span class="btn-icon-circle"><i data-lucide="rotate-ccw" style="width: 13px; height: 13px;"></i></span>
                    <span>Reset Semua ke Default Role</span>
                </button>
            </form>

        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- 2. FILTER & SEARCH TOOLBAR (1:1 REKAP-MUKHOLIF)                       -->
    <!-- ===================================================================== -->
    <div class="card shadow-sm border-0 rounded-4 mb-4" style="background: #ffffff; border: 1.5px solid #edf2f7; border-radius: 16px; padding: 14px 18px; margin-bottom: 20px;">
        <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3" style="display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap;">
            
            <div class="perm-search-box" style="position: relative; flex: 1; min-width: 260px; width: 100%;">
                <i data-lucide="search" class="search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #94a3b8; pointer-events: none;"></i>
                <input type="text" id="filterInput" placeholder="Cari nama izin atau kode teknis (misal: inventori, pos, kasir)..." autocomplete="off" oninput="applyFilters()"
                       style="width: 100%; border-radius: 12px; padding: 0.55rem 2.4rem 0.55rem 2.6rem; font-size: 0.86rem; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #1e293b; outline: none;">
                <i data-lucide="x" class="clear-btn" id="clearFilterBtn" onclick="clearFilter()" title="Hapus filter" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #94a3b8; cursor: pointer; display: none;"></i>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap" style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="filter-btn-pill active" id="btnFilterAll" onclick="setFilterMode('all')">
                    <i data-lucide="list" style="width: 14px; height: 14px;"></i>
                    <span>Semua Izin</span>
                    <span class="badge-count" id="totalVisibleCount"><?= $totalPermCount ?></span>
                </button>
                <button type="button" class="filter-btn-pill" id="btnFilterCustom" onclick="setFilterMode('custom')" title="Filter hanya izin khusus yang diubah">
                    <i data-lucide="sliders" style="width: 14px; height: 14px;"></i>
                    <span>Izin Khusus</span>
                    <span class="badge-count" id="customFilterCount"><?= $customOverrideCount ?></span>
                </button>
            </div>

        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- 3. FORM SIMPAN IZIN DENGAN SMART CONTEXTUAL 2-STATE CONTROLS          -->
    <!-- ===================================================================== -->
    <form action="<?= Router::url('/permissions/save-user-overrides') ?>" method="POST" id="form-user-permissions">
        <input type="hidden" name="user_id" value="<?= htmlspecialchars($selectedUserId) ?>">

        <!-- Empty State jika Filter "Izin Khusus" Kosong -->
        <div id="noCustomPermsState" class="card shadow-sm border-0 rounded-4 p-5 text-center my-4" style="display: none; background: #ffffff; border-radius: 16px; border: 1.5px solid #edf2f7; padding: 50px 20px; text-align: center; margin: 20px 0;">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 mx-auto" style="width: 64px; height: 64px; border-radius: 50%; background: #ecfdf5; color: #047857; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                <i data-lucide="check-circle-2" style="width: 32px; height: 32px;"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1" style="font-weight: 700; color: #0f172a; margin-bottom: 4px;">Tidak Ada Izin Khusus</h5>
            <p class="text-muted small mb-3" style="color: #64748b; font-size: 0.85rem; max-width: 450px; margin: 0 auto 16px auto;">
                Pengguna ini 100% mengikuti hak akses bawaan rolenya. Belum ada izin yang ditambah (Allow) atau dicabut (Deny) secara khusus.
            </p>
            <div>
                <button type="button" class="filter-btn-pill active" onclick="setFilterMode('all')">
                    <i data-lucide="eye" style="width: 14px; height: 14px;"></i> Tampilkan Semua Izin
                </button>
            </div>
        </div>

        <!-- Empty State jika Pencarian Tidak Ada -->
        <div id="noSearchPermsState" class="card shadow-sm border-0 rounded-4 p-5 text-center my-4" style="display: none; background: #ffffff; border-radius: 16px; border: 1.5px solid #edf2f7; padding: 50px 20px; text-align: center; margin: 20px 0;">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 mx-auto" style="width: 64px; height: 64px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                <i data-lucide="search" style="width: 32px; height: 32px;"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1" style="font-weight: 700; color: #0f172a; margin-bottom: 4px;">Izin Tidak Ditemukan</h5>
            <p class="text-muted small mb-3" style="color: #64748b; font-size: 0.85rem; margin-bottom: 16px;">Tidak ada nama izin atau kode yang cocok dengan kata kunci pencarian Anda.</p>
            <div>
                <button type="button" class="filter-btn-pill" onclick="clearFilter()">
                    <i data-lucide="x" style="width: 14px; height: 14px;"></i> Hapus Kata Kunci
                </button>
            </div>
        </div>

        <!-- Group Cards Grid (2-Column Responsive) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="permGroupsRow" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px;">
            <?php foreach ($permissions as $grup => $items): ?>
                <div class="group-column" data-group-name="<?= htmlspecialchars($grup) ?>" style="min-width: 0;">
                    <div class="card shadow-sm border-0 rounded-4 h-100" style="position: relative; overflow: hidden; background: #ffffff; border-radius: 16px; border: 1.5px solid #edf2f7; height: 100%;">
                        
                        <!-- Vibrant Top Accent Bar -->
                        <div class="group-accent-line"></div>
                        
                        <!-- Card Header -->
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-2" style="padding: 18px 20px 10px 20px; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                            <div class="d-flex align-items-center gap-2" style="display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="folder" style="width: 20px; height: 20px; color: #f59e0b;"></i>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.05rem; font-weight: 700; margin: 0; color: #0f172a;">
                                    <?= htmlspecialchars($grup) ?>
                                </h5>
                            </div>
                            <span class="badge" style="background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; font-size: 0.72rem; font-weight: 600; padding: 3px 8px; border-radius: 8px;">
                                <?= count($items) ?> Izin
                            </span>
                        </div>
                        
                        <!-- Card Body: Permission Items -->
                        <div class="card-body pt-2 pb-4" style="padding: 10px 20px 20px 20px;">
                            <div class="d-flex flex-column gap-2.5" style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach ($items as $perm): 
                                    $permId = (string)$perm['id'];
                                    $isInherited = in_array($permId, $rolePermissions, true);
                                    
                                    // Current state: 'allow', 'deny', or 'default'
                                    $currentState = 'default';
                                    if (isset($userOverrides[$permId])) {
                                        $currentState = ($userOverrides[$permId] === 1) ? 'allow' : 'deny';
                                    }

                                    $boxClass = '';
                                    if ($currentState === 'allow') $boxClass = 'is-allow';
                                    elseif ($currentState === 'deny') $boxClass = 'is-deny';

                                    $searchKeywords = strtolower(($perm['deskripsi'] ?? '') . ' ' . ($perm['nama_izin'] ?? '') . ' ' . ($perm['kode_izin'] ?? '') . ' ' . $grup);
                                ?>
                                    <div class="perm-item-box <?= $boxClass ?>" id="box_perm_<?= $permId ?>" data-search="<?= htmlspecialchars($searchKeywords) ?>" data-state="<?= $currentState ?>">
                                        
                                        <!-- Row 1: Title (Full width, no cutoff) -->
                                        <div class="fw-bold text-dark" style="font-size: 0.91rem; line-height: 1.35; font-weight: 700; color: #0f172a;">
                                            <?= htmlspecialchars($perm['nama_izin'] ?: ($perm['deskripsi'] ?? $perm['kode_izin'])) ?>
                                        </div>

                                        <!-- Row 2: Metadata (Monospace Code Tag on Left + Role Default Badge on Right) -->
                                        <div class="perm-meta-bar">
                                            <span class="perm-code-tag">
                                                <i data-lucide="code" style="width: 11px; height: 11px; opacity: 0.6;"></i> <?= htmlspecialchars($perm['kode_izin']) ?>
                                            </span>

                                            <div>
                                                <?php if ($isInherited): ?>
                                                    <span class="badge-role-status badge-role-on" title="Role <?= htmlspecialchars($selectedUserData['nama_peran']) ?> memiliki izin ini secara default">
                                                        <i data-lucide="check-circle-2" style="width: 12px; height: 12px;"></i> Role: Aktif
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge-role-status badge-role-off" title="Role <?= htmlspecialchars($selectedUserData['nama_peran']) ?> tidak memiliki izin ini secara default">
                                                        <i data-lucide="minus-circle" style="width: 12px; height: 12px;"></i> Role: Nonaktif
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Row 3: Smart Contextual Segmented Control (Plek Ketiplek 2-State) -->
                                        <div class="smart-perm-control">
                                            <?php if ($isInherited): ?>
                                                <!-- KASUS A: Bawaan Role adalah AKTIF -->
                                                <input type="radio" name="overrides[<?= $permId ?>]" id="perm_<?= $permId ?>_def" value="default" <?= ($currentState !== 'deny') ? 'checked' : '' ?> onchange="onPermStateChange('<?= $permId ?>', 'default')">
                                                <label for="perm_<?= $permId ?>_def" class="lbl-smart-default" title="Gunakan setelan bawaan role (Aktif)">
                                                    <i data-lucide="check-circle-2" style="width: 13px; height: 13px; color: var(--iz-green);"></i> Default (Aktif)
                                                </label>

                                                <input type="radio" name="overrides[<?= $permId ?>]" id="perm_<?= $permId ?>_deny" value="deny" <?= ($currentState === 'deny') ? 'checked' : '' ?> onchange="onPermStateChange('<?= $permId ?>', 'deny')">
                                                <label for="perm_<?= $permId ?>_deny" class="lbl-smart-deny" title="Cabut/blokir izin ini khusus untuk user ini">
                                                    <i data-lucide="ban" style="width: 13px; height: 13px;"></i> Cabut Khusus
                                                </label>
                                            <?php else: ?>
                                                <!-- KASUS B: Bawaan Role adalah NONAKTIF -->
                                                <input type="radio" name="overrides[<?= $permId ?>]" id="perm_<?= $permId ?>_def" value="default" <?= ($currentState !== 'allow') ? 'checked' : '' ?> onchange="onPermStateChange('<?= $permId ?>', 'default')">
                                                <label for="perm_<?= $permId ?>_def" class="lbl-smart-default" title="Gunakan setelan bawaan role (Nonaktif)">
                                                    <i data-lucide="minus-circle" style="width: 13px; height: 13px; color: #94a3b8;"></i> Default (Nonaktif)
                                                </label>

                                                <input type="radio" name="overrides[<?= $permId ?>]" id="perm_<?= $permId ?>_allow" value="allow" <?= ($currentState === 'allow') ? 'checked' : '' ?> onchange="onPermStateChange('<?= $permId ?>', 'allow')">
                                                <label for="perm_<?= $permId ?>_allow" class="lbl-smart-allow" title="Berikan izin tambahan ini khusus untuk user ini">
                                                    <i data-lucide="plus-circle" style="width: 13px; height: 13px;"></i> Berikan Khusus
                                                </label>
                                            <?php endif; ?>
                                        </div>

                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Non-Sticky Bottom Action Bar (Mobile Responsive Centered Pill) -->
        <div class="mt-4 pt-3 pb-5 text-center px-1" style="margin-top: 24px; padding: 20px 0; text-align: center;">
            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2.5 shadow-sm fw-bold" 
                    style="background: var(--iz-primary); border: none; font-size: 0.96rem; font-weight: 700; color: #ffffff; padding: 12px 36px; border-radius: 30px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3); cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;" 
                    onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                <i data-lucide="save" style="width: 18px; height: 18px;"></i>
                <span>Simpan Perubahan Izin User</span>
            </button>
        </div>

    </form>
<?php endif; ?>

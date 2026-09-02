<?php
use App\Core\Router;
ob_start();
?>

<!-- ========================================================================= -->
<!-- SCOPED STYLES: 100% IDENTIK DENGAN REKAP-MUKHOLIF PERMISSIONS ENGINE      -->
<!-- ========================================================================= -->
<style>
    :root {
        --iz-primary: #2563eb;
        --iz-primary-hover: #1d4ed8;
        --iz-green: #16a34a;
        --iz-red: #dc2626;
        --iz-border: #e2e8f0;
        --iz-bg-soft: #f8fafc;
    }

    /* 1. SEGMENTED NAVIGATION CONTROL (PILLS) */
    .nav-segmented-control {
        display: inline-flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        gap: 3px;
        max-width: 100%;
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .nav-segmented-control::-webkit-scrollbar {
        display: none;
    }
    .nav-segment-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.45rem 0.95rem;
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #64748b;
        text-decoration: none;
        white-space: nowrap;
        flex-shrink: 0;
        transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        background: transparent;
        cursor: pointer;
    }
    .nav-segment-link:hover {
        color: #0f172a;
        background: rgba(255, 255, 255, 0.7);
    }
    .nav-segment-link.active {
        background: #ffffff !important;
        color: var(--iz-primary) !important;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(226, 232, 240, 0.8) !important;
    }

    /* 2. USER / ROLE PICKER CARD */
    .user-picker-card, .role-picker-card {
        border: 1.5px solid #edf2f7 !important;
        background: linear-gradient(180deg, #ffffff, #f8fafc) !important;
        border-radius: 16px !important;
    }

    /* 3. USER INFO BANNER */
    .user-info-banner, .role-info-banner {
        border: 1.5px solid #edf2f7 !important;
        background: #ffffff !important;
        border-radius: 16px !important;
        position: relative;
        overflow: hidden;
    }
    .user-avatar-circle, .role-avatar-circle {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        background: #eff6ff;
        color: var(--iz-primary);
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .badge-user-role {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* 4. MODERN RESET OVERRIDES BUTTON */
    .btn-reset-overrides {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: #fff1f2;
        color: #e11d48;
        border: 1.5px solid #fecdd3;
        border-radius: 12px;
        padding: 0.52rem 1.1rem;
        font-size: 0.82rem;
        font-weight: 600;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        white-space: nowrap;
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(225, 29, 72, 0.08);
    }
    .btn-reset-overrides:hover {
        background: #ffe4e6;
        border-color: #fda4af;
        color: #be123c;
        box-shadow: 0 3px 10px rgba(225, 29, 72, 0.16);
        transform: translateY(-1px);
    }
    .btn-reset-overrides:active {
        transform: translateY(0);
    }
    .btn-reset-overrides .btn-icon-circle {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: rgba(225, 29, 72, 0.12);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        transition: transform 0.25s ease;
    }
    .btn-reset-overrides:hover .btn-icon-circle {
        transform: rotate(-60deg);
    }

    /* 5. FILTER TOOLBAR & PILLS */
    .perm-search-box {
        position: relative;
        flex: 1;
        min-width: 240px;
    }
    .perm-search-box input {
        width: 100%;
        border-radius: 12px;
        padding: 0.55rem 2.4rem 0.55rem 2.4rem;
        font-size: 0.86rem;
        border: 1.5px solid #e2e8f0;
        background: #f8fafc;
        color: #1e293b;
        transition: all 0.2s ease;
        outline: none;
    }
    .perm-search-box input:focus {
        background: #ffffff;
        border-color: var(--iz-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }
    .perm-search-box .search-icon {
        position: absolute;
        left: 0.9rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }
    .perm-search-box .clear-btn {
        position: absolute;
        right: 0.85rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        cursor: pointer;
        display: none;
    }

    .filter-btn-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.45rem 0.95rem;
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 600;
        border: 1.5px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
    }
    .filter-btn-pill:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a;
    }
    .filter-btn-pill.active {
        background: var(--iz-primary) !important;
        border-color: var(--iz-primary) !important;
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
    }
    .filter-btn-pill.active i, .filter-btn-pill.active svg {
        color: #ffffff !important;
    }
    .filter-btn-pill.active .badge-count {
        background: rgba(255, 255, 255, 0.25) !important;
        color: #ffffff !important;
    }
    .filter-btn-pill .badge-count {
        background: #f1f5f9;
        color: #475569;
        font-size: 0.72rem;
        padding: 2px 6px;
        border-radius: 6px;
        font-weight: 700;
    }

    /* 6. PERM ITEM BOX */
    .perm-item-box {
        background: #ffffff;
        border: 1.5px solid #edf2f7;
        border-radius: 14px;
        padding: 0.95rem 1rem;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }
    .perm-item-box:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.04);
    }
    .perm-item-box.is-allow {
        border-color: #bbf7d0 !important;
        background: #f0fdf4 !important;
        box-shadow: 0 2px 8px -2px rgba(22, 163, 74, 0.1);
    }
    .perm-item-box.is-deny {
        border-color: #fecaca !important;
        background: #fef2f2 !important;
        box-shadow: 0 2px 8px -2px rgba(220, 38, 38, 0.1);
    }
    .perm-item-box.is-active {
        background: #eff6ff !important;
        border-color: #bfdbfe !important;
    }

    /* 7. META BAR (CODE + STATUS BADGE) */
    .perm-meta-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
    }
    .perm-code-tag {
        font-family: 'JetBrains Mono', 'Fira Code', monospace;
        font-size: 0.73rem;
        background: #f1f5f9;
        color: #475569;
        padding: 2px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border: 1px solid #e2e8f0;
        word-break: break-all;
    }
    .perm-item-box.is-allow .perm-code-tag {
        background: #dcfce7;
        color: #166534;
        border-color: #bbf7d0;
    }
    .perm-item-box.is-deny .perm-code-tag {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fecaca;
    }

    /* 8. ROLE STATUS BADGES */
    .badge-role-status {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
        line-height: 1.2;
    }
    .badge-role-on {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
    .badge-role-off {
        background: #f8fafc;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    /* 9. CONTEXTUAL SMART SEGMENTED CONTROL */
    .smart-perm-control {
        display: grid;
        grid-template-columns: 1fr 1fr;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 3px;
        gap: 4px;
        width: 100%;
    }
    .perm-item-box.is-allow .smart-perm-control {
        background: #dcfce7;
        border-color: #bbf7d0;
    }
    .perm-item-box.is-deny .smart-perm-control {
        background: #fee2e2;
        border-color: #fecaca;
    }
    .smart-perm-control input[type="radio"] {
        display: none !important;
    }
    .smart-perm-control label {
        text-align: center;
        padding: 0.44rem 0.4rem;
        font-size: 0.79rem;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.16s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        user-select: none;
        line-height: 1.2;
        border: 1px solid transparent;
        white-space: nowrap;
    }
    .smart-perm-control label:hover {
        background: rgba(255, 255, 255, 0.7);
        color: #0f172a;
    }
    .smart-perm-control input:checked + .lbl-smart-default {
        background: #ffffff !important;
        color: #1e293b !important;
        border-color: #cbd5e1 !important;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08) !important;
    }
    .smart-perm-control input:checked + .lbl-smart-allow {
        background: var(--iz-green) !important;
        color: #ffffff !important;
        border-color: #15803d !important;
        box-shadow: 0 2px 6px rgba(22, 163, 74, 0.28) !important;
    }
    .smart-perm-control input:checked + .lbl-smart-allow i,
    .smart-perm-control input:checked + .lbl-smart-allow svg {
        color: #ffffff !important;
    }
    .smart-perm-control input:checked + .lbl-smart-deny {
        background: var(--iz-red) !important;
        color: #ffffff !important;
        border-color: #b91c1c !important;
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.28) !important;
    }
    .smart-perm-control input:checked + .lbl-smart-deny i,
    .smart-perm-control input:checked + .lbl-smart-deny svg {
        color: #ffffff !important;
    }

    /* 10. TOGGLE SWITCH FOR TAB ROLES */
    .form-switch .form-check-input {
        width: 2.6rem;
        height: 1.35rem;
        cursor: pointer;
        background-color: #cbd5e1;
        border-color: #cbd5e1;
        transition: background-position .15s ease-in-out;
    }
    .form-switch .form-check-input:checked {
        background-color: var(--iz-primary) !important;
        border-color: var(--iz-primary) !important;
    }

    /* 11. CATEGORY PILL FILTER & STAT CARDS */
    .cat-scroll-track {
        display: flex;
        align-items: center;
        gap: 6px;
        overflow-x: auto;
        padding-bottom: 4px;
        padding-top: 2px;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }
    .cat-scroll-track::-webkit-scrollbar {
        display: none;
    }
    .cat-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.38rem 0.85rem;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #475569;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
        white-space: nowrap;
        font-family: inherit;
        outline: none;
    }
    .cat-chip:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }
    .cat-chip.active {
        background: var(--iz-primary) !important;
        color: #ffffff !important;
        border-color: var(--iz-primary) !important;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.28) !important;
    }
    .cat-chip.active svg,
    .cat-chip.active i {
        color: #ffffff !important;
    }
    .cat-chip .count-badge {
        background: rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: 1px 6px;
        font-size: 0.7rem;
        font-weight: 700;
    }
    .cat-chip.active .count-badge {
        background: rgba(255,255,255,0.25);
        color: #ffffff;
    }

    .stat-card-pro {
        background: #ffffff;
        border: 1.5px solid #edf2f7;
        border-radius: 16px;
        padding: 1rem 1.15rem;
        display: flex;
        align-items: center;
        gap: 0.9rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
    }
    .stat-card-pro:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px -2px rgba(15, 23, 42, 0.06);
        border-color: #cbd5e1;
    }

    /* 12. GENERAL HELPERS */
    .group-card-header {
        position: relative;
        overflow: hidden;
    }
    .group-accent-line {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #f97316, #fb923c);
    }
    .btn-action-round {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid #e2e8f0;
        background: #ffffff;
        cursor: pointer;
    }
    .btn-action-round:hover {
        transform: translateY(-2px);
        background: #f8fafc;
    }

    @media (max-width: 767.98px) {
        .btn-reset-overrides {
            width: 100% !important;
            padding: 0.62rem 1rem;
            font-size: 0.84rem;
            margin-top: 0.35rem;
        }
    }
</style>

<div class="dashboard-wrapper container-fluid px-0 px-md-2 mt-2 mb-5"
     x-data="{ 
         activeTab: '<?= htmlspecialchars($activeTab ?? 'user') ?>',
         addRoleModalOpen: false,
         editRoleModalOpen: false,
         editRole: { id: '', nama_peran: '', deskripsi: '' },
         deleteRoleModalOpen: false,
         deleteRoleTarget: { id: '', nama_peran: '' },
         viewUsersRoleModalOpen: false,
         viewUsersRoleTarget: { nama_peran: '', users: [] },
         setTab(tab) {
             this.activeTab = tab;
             const url = new URL(window.location.href);
             url.searchParams.set('tab', tab);
             window.history.replaceState({}, '', url.toString());
             setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 50);
         },
         confirmDeleteRole(id, nama) {
             this.deleteRoleTarget = { id: id, nama_peran: nama };
             this.deleteRoleModalOpen = true;
             setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 50);
         },
         showRoleUsers(nama, users) {
             this.viewUsersRoleTarget = { nama_peran: nama, users: users };
             this.viewUsersRoleModalOpen = true;
             setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 50);
         }
     }"
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    <!-- ===================================================================== -->
    <!-- HEADER PAGE + SEGMENTED PILLS NAVIGATION                              -->
    <!-- ===================================================================== -->
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-start align-items-xl-center mb-4 px-1 gap-3" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
        <div class="d-flex align-items-center" style="display: flex; align-items: center; gap: 14px;">
            <div class="d-flex align-items-center justify-content-center rounded-3 shadow-sm flex-shrink-0" style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #2563eb, #3b82f6); color: white; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="shield-check" style="width: 26px; height: 26px;"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-0 text-dark" style="font-size: 1.35rem; font-weight: 700; margin: 0; color: #0f172a; letter-spacing: -0.3px;">
                    <?= htmlspecialchars($pageTitle ?? 'Manajemen Hak Akses & Izin') ?>
                </h3>
                <p class="text-muted mb-0" style="font-size: 0.85rem; color: #64748b; margin: 0;">
                    <?= htmlspecialchars($pageSubtitle ?? 'Pusat konfigurasi hak akses, matriks peran bawaan, dan audit perizinan sistem') ?>
                </p>
            </div>
        </div>
        
        <!-- Segmented Navigation Control -->
        <?php require __DIR__ . '/_nav.php'; ?>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 1: IZIN USER                                                      -->
    <!-- ===================================================================== -->
    <div x-show="activeTab === 'user'" x-cloak class="space-y-4">
        
        <!-- User Picker Card -->
        <div class="card shadow-sm border-0 rounded-4 mb-4 user-picker-card" style="padding: 20px; margin-bottom: 20px;">
            <label class="fw-bold text-dark mb-2.5 d-flex align-items-center gap-2" style="font-size: 0.95rem; font-weight: 700; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: #eff6ff; color: var(--iz-primary); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                    <i data-lucide="user-check" style="width: 16px; height: 16px;"></i>
                </span>
                <span>Pilih Akun Pengguna</span>
            </label>
            <div>
                <select class="form-select" id="userSelector" onchange="onUserSelect(this.value)" 
                        style="width: 100%; height: 50px; border-radius: 14px; border: 1.5px solid #e2e8f0; background-color: #ffffff; padding: 0.55rem 1.1rem; font-size: 0.88rem; font-weight: 600; color: #1e293b; outline: none; cursor: pointer;">
                    <option value="">-- Ketik atau Pilih Akun Pengguna --</option>
                    <?php foreach ($groupedUsers as $roleGroup => $userList): ?>
                        <optgroup label="💼 Peran: <?= htmlspecialchars($roleGroup) ?>">
                            <?php foreach ($userList as $u): ?>
                                <option value="<?= htmlspecialchars($u['id']) ?>" <?= ($selectedUserId === $u['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['nama_lengkap']) ?> (@<?= htmlspecialchars($u['nama_pengguna']) ?>) - <?= htmlspecialchars($roleGroup) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Dynamic Permissions Container (AJAX Loaded or Preloaded) -->
        <div id="userPermissionsContainer">
            <?php if (!empty($selectedUserId) && $selectedUserData): ?>
                <?php require __DIR__ . '/user_content.php'; ?>
            <?php else: ?>
                <!-- Empty State (Plekan Rekap-Mukholif) -->
                <div class="card shadow-sm border-0 rounded-4 p-5 text-center my-5" style="border-radius: 16px; border: 1.5px solid #edf2f7; background: linear-gradient(180deg, #ffffff, #f8fafc); padding: 60px 20px; text-align: center; margin: 24px 0;">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4 bg-white shadow-sm mx-auto" style="width: 90px; height: 90px; border-radius: 50%; background: #ffffff; box-shadow: 0 4px 12px rgba(15,23,42,0.06); display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                        <i data-lucide="mouse-pointer-click" style="width: 44px; height: 44px; color: var(--iz-primary); opacity: 0.85;"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2" style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Pilih Pengguna Dahulu</h4>
                    <p class="text-muted" style="max-width: 500px; margin: 0 auto; color: #64748b; font-size: 0.9rem;">
                        Pilih seorang pengguna dari menu dropdown di atas untuk mulai melihat dan mengatur pengecualian izin (penambahan atau pencabutan khusus).
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: DEFAULT ROLE                                                   -->
    <!-- ===================================================================== -->
    <div x-show="activeTab === 'role'" x-cloak>
        <?php require __DIR__ . '/tab_roles.php'; ?>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 3: KELOLA MASTER ROLE                                             -->
    <!-- ===================================================================== -->
    <div x-show="activeTab === 'manage_roles'" x-cloak>
        <?php require __DIR__ . '/tab_manage_roles.php'; ?>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 4: MATRIKS AUDIT IZIN                                             -->
    <!-- ===================================================================== -->
    <div x-show="activeTab === 'matrix'" x-cloak>
        <?php require __DIR__ . '/tab_matrix.php'; ?>
    </div>

</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT LOGIC DENGAN INSTANT AJAX & REAL-TIME SEARCH FILTER            -->
<!-- ========================================================================= -->
<script>
    var currentFilterMode = 'all'; // 'all' or 'custom'
    var lastLoadedUserId = <?= !empty($selectedUserId) ? json_encode($selectedUserId) : 'null' ?>;

    function onUserSelect(userId) {
        var uid = String(userId || '').trim();

        if (!uid || uid === "" || uid === "0") {
            lastLoadedUserId = null;
            if (window.history.pushState) {
                const url = new URL(window.location.href);
                url.searchParams.delete('user_id');
                window.history.pushState(null, '', url.toString());
            }
            var container = document.getElementById('userPermissionsContainer');
            if (container) {
                container.innerHTML = `
                    <div class="card shadow-sm border-0 rounded-4 p-5 text-center my-5" style="border-radius: 16px; border: 1.5px solid #edf2f7; background: linear-gradient(180deg, #ffffff, #f8fafc); padding: 60px 20px; text-align: center; margin: 24px 0;">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4 bg-white shadow-sm mx-auto" style="width: 90px; height: 90px; border-radius: 50%; background: #ffffff; box-shadow: 0 4px 12px rgba(15,23,42,0.06); display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                            <i data-lucide="mouse-pointer-click" style="width: 44px; height: 44px; color: var(--iz-primary); opacity: 0.85;"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2" style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Pilih Pengguna Dahulu</h4>
                        <p class="text-muted" style="max-width: 500px; margin: 0 auto; color: #64748b; font-size: 0.9rem;">
                            Pilih seorang pengguna dari menu dropdown di atas untuk mulai melihat dan mengatur pengecualian izin (penambahan atau pencabutan khusus).
                        </p>
                    </div>`;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
            return;
        }

        if (uid === String(lastLoadedUserId)) return;
        lastLoadedUserId = uid;

        var container = document.getElementById('userPermissionsContainer');
        if (!container) return;

        if (window.history.pushState) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'user');
            url.searchParams.set('user_id', uid);
            window.history.pushState(null, '', url.toString());
        }

        container.innerHTML = `
            <div class="card shadow-sm border-0 rounded-4 p-5 text-center my-4" style="border-radius: 16px; border: 1.5px solid #edf2f7; background: #ffffff; padding: 40px 20px; text-align: center;">
                <div class="spinner-border mb-3" style="width: 2.5rem; height: 2.5rem; color: var(--iz-primary); display: inline-block; border: 3px solid currentColor; border-right-color: transparent; border-radius: 50%; animation: spinner-border .75s linear infinite;" role="status"></div>
                <h5 class="fw-bold text-dark mb-1" style="font-weight: 700; color: #0f172a;">Memuat Izin Pengguna...</h5>
                <p class="text-muted small mb-0" style="color: #64748b; font-size: 0.85rem;">Sedang mengambil data hak akses role & pengecualian khusus</p>
            </div>`;

        fetch('<?= Router::url('/permissions') ?>?tab=user&user_id=' + encodeURIComponent(uid) + '&ajax=1', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(response) {
            if (!response.ok) throw new Error('Status respon server: ' + response.status);
            return response.text();
        })
        .then(function(html) {
            container.innerHTML = html;
            currentFilterMode = 'all';
            updateCustomCount();
            applyFilters();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        })
        .catch(function(error) {
            lastLoadedUserId = null;
            container.innerHTML = `
                <div class="card shadow-sm border-0 rounded-4 p-4 text-center my-4" style="border-radius: 16px; border: 1.5px solid #fecdd3; background: #fff1f2; padding: 24px; text-align: center;">
                    <div class="fw-bold text-danger mb-1" style="font-weight: 700; color: #e11d48;">Gagal Memuat Izin</div>
                    <div class="small text-muted" style="color: #64748b; font-size: 0.85rem;">${error.message}. Silakan coba pilih ulang pengguna dari dropdown di atas.</div>
                </div>`;
        });
    }

    function onPermStateChange(permId, state) {
        var box = document.getElementById('box_perm_' + permId);
        if (!box) return;

        box.classList.remove('is-allow', 'is-deny');
        box.setAttribute('data-state', state);

        if (state === 'allow') {
            box.classList.add('is-allow');
        } else if (state === 'deny') {
            box.classList.add('is-deny');
        }

        updateCustomCount();
        applyFilters();
    }

    function updateCustomCount() {
        var customBoxes = document.querySelectorAll('.perm-item-box[data-state="allow"], .perm-item-box[data-state="deny"]');
        var count = customBoxes.length;

        var countBadge = document.getElementById('customFilterCount');
        if (countBadge) countBadge.textContent = count;

        var bannerText = document.getElementById('bannerOverrideText');
        var bannerBadge = document.getElementById('bannerOverrideBadge');

        if (bannerText && bannerBadge) {
            if (count > 0) {
                bannerText.textContent = count + ' Izin Khusus Aktif';
                bannerBadge.style.background = '#fffbeb';
                bannerBadge.style.color = '#b45309';
                bannerBadge.style.borderColor = '#fde68a';
            } else {
                bannerText.textContent = '100% Mengikuti Default Role';
                bannerBadge.style.background = '#ecfdf5';
                bannerBadge.style.color = '#047857';
                bannerBadge.style.borderColor = '#a7f3d0';
            }
        }
    }

    function setFilterMode(mode) {
        currentFilterMode = mode;
        var btnAll = document.getElementById('btnFilterAll');
        var btnCustom = document.getElementById('btnFilterCustom');

        if (btnAll) {
            if (mode === 'all') btnAll.classList.add('active');
            else btnAll.classList.remove('active');
        }

        if (btnCustom) {
            if (mode === 'custom') btnCustom.classList.add('active');
            else btnCustom.classList.remove('active');
        }

        applyFilters();
    }

    function applyFilters() {
        var filterInput = document.getElementById('filterInput');
        var query = filterInput ? filterInput.value.toLowerCase().trim() : '';
        var clearBtn = document.getElementById('clearFilterBtn');
        if (clearBtn) {
            clearBtn.style.display = query ? 'block' : 'none';
        }

        var items = document.querySelectorAll('.perm-item-box');
        var visibleCount = 0;
        var totalCustomCount = 0;

        items.forEach(function(box) {
            var searchData = (box.getAttribute('data-search') || '').toLowerCase();
            var state = box.getAttribute('data-state') || 'default';
            var isCustom = (state === 'allow' || state === 'deny');

            if (isCustom) totalCustomCount++;

            var matchesQuery = !query || searchData.includes(query);
            var matchesMode = (currentFilterMode === 'all') || (currentFilterMode === 'custom' && isCustom);

            if (matchesQuery && matchesMode) {
                box.style.display = '';
                visibleCount++;
            } else {
                box.style.display = 'none';
            }
        });

        document.querySelectorAll('.group-column').forEach(function(col) {
            var hasVisibleChild = false;
            col.querySelectorAll('.perm-item-box').forEach(function(box) {
                if (box.style.display !== 'none') {
                    hasVisibleChild = true;
                }
            });
            col.style.display = hasVisibleChild ? '' : 'none';
        });

        var countElem = document.getElementById('totalVisibleCount');
        if (countElem) {
            countElem.textContent = (currentFilterMode === 'all') ? visibleCount : items.length;
        }

        var noCustomState = document.getElementById('noCustomPermsState');
        var noSearchState = document.getElementById('noSearchPermsState');

        if (noCustomState) {
            noCustomState.style.display = (currentFilterMode === 'custom' && totalCustomCount === 0) ? 'block' : 'none';
        }

        if (noSearchState) {
            noSearchState.style.display = (query && visibleCount === 0) ? 'block' : 'none';
        }
    }

    function clearFilter() {
        var input = document.getElementById('filterInput');
        if (input) {
            input.value = '';
            applyFilters();
            input.focus();
        }
    }

    async function confirmResetAll(e) {
        if (e) e.preventDefault();
        var form = document.getElementById('formResetAll');
        if (!form) return;

        const confirmed = window.AppConfirm ? await window.AppConfirm({
            title: 'Reset Izin Khusus Pengguna',
            message: 'Apakah Anda yakin ingin menghapus semua izin khusus user ini dan mengembalikannya 100% murni mengikuti default rolenya?',
            confirmText: 'Ya, Reset Izin',
            cancelText: 'Batal',
            type: 'warning',
            icon: 'rotate-ccw'
        }) : confirm('Apakah Anda yakin ingin menghapus semua izin khusus user ini dan mengembalikannya 100% murni mengikuti default rolenya?');

        if (confirmed) {
            form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

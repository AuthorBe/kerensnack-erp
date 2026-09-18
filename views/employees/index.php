<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<style>
/* ========================================================= */
/* MODAL SKEMA KOMISI BERTINGKAT (STANDALONE STYLES)         */
/* ========================================================= */
.skema-modal-box {
    width: 95%;
    max-width: 880px;
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    max-height: 88vh;
    max-height: 88dvh;
    overflow: hidden;
    position: relative;
    z-index: 10000;
    margin: auto;
    animation: modalPopIn 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@media (max-width: 768px) {
    .skema-modal-box {
        width: 100% !important;
        max-width: 100% !important;
        max-height: 94vh !important;
        max-height: 94dvh !important;
        border-radius: 20px 20px 0 0 !important;
        margin: 0 !important;
        animation: drawerSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
}

.skema-mobile-pull {
    display: none;
    justify-content: center;
    padding: 10px 0 4px 0;
    background: var(--color-canvas-soft);
    flex-shrink: 0;
}
.skema-mobile-pull-bar {
    width: 44px;
    height: 5px;
    border-radius: 99px;
    background: var(--color-hairline-strong);
}
@media (max-width: 768px) {
    .skema-mobile-pull {
        display: flex;
    }
}

.skema-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: var(--color-canvas-soft);
    border-bottom: 1px solid var(--color-hairline);
    flex-shrink: 0;
    gap: 12px;
}
.skema-modal-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.skema-modal-header-icon {
    width: 38px;
    height: 38px;
    min-width: 38px;
    min-height: 38px;
    border-radius: 10px;
    background: rgba(245, 158, 11, 0.15);
    border: 1px solid rgba(245, 158, 11, 0.35);
    color: #d97706;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.skema-modal-header-icon svg {
    width: 20px !important;
    height: 20px !important;
    display: block;
}
.skema-modal-header-text {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.skema-modal-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--color-ink);
    display: flex;
    align-items: center;
    gap: 8px;
    line-height: 1.25;
}
.skema-modal-subtitle {
    font-size: 11.5px;
    color: var(--color-ink-mute);
    line-height: 1.3;
}

.skema-modal-body {
    padding: 16px 20px;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    flex: 1 1 auto;
    min-height: 0;
    display: block;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.5) transparent;
}
.skema-modal-body::-webkit-scrollbar {
    display: block !important;
    width: 7px !important;
}
.skema-modal-body::-webkit-scrollbar-track {
    background: transparent !important;
}
.skema-modal-body::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.45) !important;
    border-radius: 99px !important;
}
.skema-modal-body::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.75) !important;
}
@media (max-width: 640px) {
    .skema-modal-body {
        padding: 14px 14px;
    }
}

/* 1. Alert Penjelasan */
.skema-info-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgba(245, 158, 11, 0.08);
    border: 1px solid rgba(245, 158, 11, 0.25);
    margin-bottom: 16px;
    flex-shrink: 0;
}
.skema-info-icon {
    width: 28px;
    height: 28px;
    min-width: 28px;
    border-radius: 8px;
    background: rgba(245, 158, 11, 0.18);
    border: 1px solid rgba(245, 158, 11, 0.4);
    color: #d97706;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}
.skema-info-icon svg {
    width: 15px !important;
    height: 15px !important;
    display: block;
}
.skema-info-content {
    flex: 1;
    min-width: 0;
}
.skema-info-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 4px;
}
.skema-info-title {
    font-size: 12.5px;
    font-weight: 800;
    color: #b45309;
}
.skema-info-badge {
    font-size: 9.5px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 99px;
    background: rgba(245, 158, 11, 0.18);
    color: #b45309;
    border: 1px solid rgba(245, 158, 11, 0.35);
    font-family: var(--font-mono);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.skema-info-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: 6px;
}
.skema-info-item {
    display: flex !important;
    flex-direction: row !important;
    align-items: flex-start !important;
    gap: 8px !important;
    font-size: 11px;
    color: var(--color-ink-secondary);
    line-height: 1.45;
}
.skema-info-bullet {
    width: 18px !important;
    height: 18px !important;
    min-width: 18px !important;
    max-width: 18px !important;
    border-radius: 99px;
    background: rgba(245, 158, 11, 0.22);
    border: 1px solid rgba(245, 158, 11, 0.4);
    color: #b45309;
    font-weight: 800;
    font-size: 10px;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    margin-top: 1px;
    flex-shrink: 0 !important;
    font-family: var(--font-mono);
}
.skema-info-text {
    flex: 1;
    min-width: 0;
}

/* 2. Tier Section Header */
.skema-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 10px;
    flex-shrink: 0;
}
.skema-section-title {
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-ink-secondary);
    display: flex;
    align-items: center;
    gap: 8px;
}

/* 3. Tier Desktop Table */
.skema-table-wrap {
    display: block !important;
    border-radius: 12px;
    border: 1px solid var(--color-hairline);
    background: var(--color-canvas);
    overflow-x: auto;
    overflow-y: visible;
    width: 100%;
    margin-bottom: 14px;
    flex-shrink: 0;
}
.skema-table {
    width: 100%;
    min-width: 680px;
    border-collapse: collapse;
    font-size: 12px;
}
.skema-table th {
    padding: 10px 14px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--color-ink-mute);
    background: var(--color-canvas-soft);
    border-bottom: 1px solid var(--color-hairline);
    text-align: left;
    white-space: nowrap;
}
.skema-table td {
    padding: 8px 14px;
    border-bottom: 1px solid var(--color-hairline);
    vertical-align: middle;
}
.skema-table tr:last-child td {
    border-bottom: none;
}

/* 4. Tier Mobile Cards */
.skema-cards-wrap {
    display: none !important;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 14px;
    flex-shrink: 0;
}
@media (max-width: 640px) {
    .skema-table-wrap {
        display: none !important;
    }
    .skema-cards-wrap {
        display: flex !important;
    }
}
.skema-tier-card {
    padding: 12px;
    border-radius: 12px;
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    display: flex;
    flex-direction: column;
    gap: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.skema-tier-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.skema-tier-card-badge {
    width: 26px;
    height: 26px;
    min-width: 26px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font-mono);
    font-weight: 800;
    font-size: 11.5px;
    color: #d97706;
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.35);
    flex-shrink: 0;
}
.skema-tier-card-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.skema-tier-card-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 8px;
    border-top: 1px solid var(--color-hairline);
    gap: 8px;
}

/* 5. Live Simulator */
.skema-calc-box {
    padding: 14px 16px;
    border-radius: 14px;
    background: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 4px;
    flex-shrink: 0;
}
.skema-calc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 6px;
}
.skema-calc-header-left {
    display: flex;
    align-items: center;
    gap: 8px;
}
.skema-calc-header-icon {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    background: rgba(16, 185, 129, 0.14);
    color: #10b981;
    display: flex;
    align-items: center;
    justify-content: center;
}
.skema-calc-header-icon svg {
    width: 14px !important;
    height: 14px !important;
    display: block;
}
.skema-calc-header-title {
    font-size: 11.5px;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: var(--color-ink);
    text-transform: uppercase;
}
.skema-calc-header-sub {
    font-size: 11px;
    color: var(--color-ink-mute);
}
.skema-calc-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr 1fr;
    gap: 10px;
}
@media (max-width: 640px) {
    .skema-calc-grid {
        grid-template-columns: 1fr;
        gap: 8px;
    }
}
.skema-calc-card {
    padding: 8px 12px;
    border-radius: 10px;
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 54px;
}
.skema-calc-card-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--color-ink-mute);
    margin-bottom: 2px;
}
.skema-calc-gap-banner {
    padding: 8px 12px;
    border-radius: 9px;
    background: rgba(59, 130, 246, 0.08);
    border: 1px solid rgba(59, 130, 246, 0.25);
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11.5px;
    color: var(--color-ink);
}
.skema-calc-gap-icon {
    width: 20px;
    height: 20px;
    border-radius: 99px;
    background: rgba(59, 130, 246, 0.2);
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.skema-calc-gap-icon svg {
    width: 12px !important;
    height: 12px !important;
    display: block;
}

/* 6. Modal Footer */
.skema-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: var(--color-canvas-soft);
    border-top: 1px solid var(--color-hairline);
    flex-shrink: 0;
    gap: 12px;
}
.skema-modal-footer-note {
    font-size: 11px;
    color: var(--color-ink-mute);
    line-height: 1.4;
}
.skema-modal-footer-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
@media (max-width: 640px) {
    .skema-modal-footer {
        flex-direction: column-reverse;
        padding: 12px 14px;
        gap: 10px;
    }
    .skema-modal-footer-note {
        text-align: center;
    }
    .skema-modal-footer-actions {
        width: 100%;
    }
    .skema-modal-footer-actions .btn {
        flex: 1;
        justify-content: center;
        padding: 9px 12px;
        font-size: 12px;
    }
}
</style>

<div x-data="employeeApp()" x-init="init()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-indigo">
                <i data-lucide="users"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#6366f1;"></span>
                    <span>SDM &amp; Tenaga Kerja</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Master Data Karyawan' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Kelola Data Pegawai Admin, Gudang, Pengemasan, Sales &amp; Driver' ?></p>
            </div>
        </div>
        <div class="page-header-actions" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <?php if (\App\Core\Auth::can('master.employees_manage')): ?>
            <button @click="openTierModal()" class="btn btn-secondary" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;">
                <i data-lucide="award" class="w-4 h-4 text-amber-500"></i>
                <span>Atur Skema Komisi Bertingkat</span>
                <span class="badge badge-warning text-[10px] py-0.5 px-1.5" x-text="editableTiers.length + ' Tier'"></span>
            </button>
            <button @click="openAddModal()" class="btn btn-primary" style="font-weight:700;">
                <i data-lucide="user-plus"></i>
                <span>Tambah Karyawan</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TOP STATS: 5 KEY EMPLOYEE METRIC CARDS                                    -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-4">
        
        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Total Karyawan</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="users" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:20px;font-weight:900;font-family:var(--font-mono);color:#10b981;line-height:1.2;">
                <?= $metrics['total'] ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Pegawai Operasional</div>
        </div>

        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Buruh Borongan</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(59,130,246,0.12);color:#3b82f6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="package" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:20px;font-weight:900;font-family:var(--font-mono);color:#3b82f6;line-height:1.2;">
                <?= $metrics['borongan'] ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Pengemasan &amp; Packing</div>
        </div>

        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Sales Toko</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(245,158,11,0.12);color:#f59e0b;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="store" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:20px;font-weight:900;font-family:var(--font-mono);color:#f59e0b;line-height:1.2;">
                <?= $metrics['sales'] ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Canvaser &amp; Komisi</div>
        </div>

        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Driver Logistik</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(2,132,199,0.12);color:#0284c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="truck" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:20px;font-weight:900;font-family:var(--font-mono);color:#0284c7;line-height:1.2;">
                <?= $metrics['driver'] ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Supir Pengantar</div>
        </div>

        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Admin &amp; Gudang</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(139,92,246,0.12);color:#8b5cf6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="building-2" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:20px;font-weight:900;font-family:var(--font-mono);color:#8b5cf6;line-height:1.2;">
                <?= $metrics['admin_gudang'] ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Kantor, Gudang &amp; Mandor</div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- MAIN DATA TABLE CARD                                                      -->
    <!-- ========================================================================= -->
    <div class="card p-0 overflow-hidden" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);">

        <!-- ACTION & FILTER BAR -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            
            <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto flex-1">
                <div class="form-input-icon flex-1 sm:max-w-xs">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="text" x-model="searchQuery" placeholder="Cari nama / NIK / HP..." class="form-input" style="height:38px;font-size:13px;">
                </div>

                <select x-model="filterPosition" class="form-input" style="height:38px;font-size:13px;max-width:220px;">
                    <option value="all">Semua Divisi / Posisi</option>
                    <option value="sales">💼 Sales Toko</option>
                    <option value="driver">🚚 Driver Logistik</option>
                    <option value="pengemasan">🍿 Pengemasan (Borongan)</option>
                    <option value="gudang">📦 Staff Gudang &amp; Logistik</option>
                    <option value="admin">👩‍💼 Admin &amp; Keuangan</option>
                    <option value="mandor">👷 Mandor / Supervisor</option>
                </select>
            </div>

            <div class="text-xs" style="color:var(--color-ink-mute);font-weight:600;white-space:nowrap;">
                Menampilkan <span class="font-mono" style="font-weight:800;color:var(--color-primary);" x-text="filteredEmployees.length"></span> dari <span class="font-mono" style="font-weight:700;color:var(--color-ink);" x-text="employees.length"></span> karyawan
            </div>
        </div>

        <!-- TABLE LIST -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 960px;">
                <thead>
                    <tr>
                        <th style="min-width:180px;">Nama Karyawan</th>
                        <th style="min-width:140px;">Divisi / Posisi</th>
                        <th class="cell-center cell-nowrap" style="width:110px; min-width:100px;">Tipe Gaji</th>
                        <th class="cell-right cell-nowrap" style="width:160px; min-width:140px;">Gaji Pokok / Komisi</th>
                        <th style="min-width:150px;">Uang Hadir &amp; Tunjangan</th>
                        <th style="min-width:160px;">Rekening Bank / Pembayaran</th>
                        <th class="cell-center cell-nowrap" style="width:90px; min-width:80px;">Status</th>
                        <?php if (\App\Core\Auth::can('master.employees_manage')): ?>
                        <th class="cell-center cell-nowrap" style="width:90px; min-width:80px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="e in filteredEmployees" :key="e.id">
                        <tr :style="!e.status_aktif ? 'opacity:0.5;' : ''">
                            
                            <!-- Nama & Kontak -->
                            <td>
                                <div style="font-weight:800;font-size:13.5px;color:var(--color-ink);" x-text="e.nama_karyawan"></div>
                                <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                    <span x-text="(e.nomor_whatsapp || e.nomor_telepon) ? ('WA: ' + (e.nomor_whatsapp || e.nomor_telepon)) : (e.nik ? ('NIK: ' + e.nik) : 'Belum ada kontak')"></span>
                                    <template x-if="e.nomor_polisi_kendaraan">
                                        <span class="badge badge-mono" style="font-size:10.5px;padding:1px 6px;color:#0284c7;border-color:rgba(2,132,199,0.3);background:rgba(2,132,199,0.08);">
                                            &#x1F69A; <span x-text="e.nomor_polisi_kendaraan"></span>
                                        </span>
                                    </template>
                                </div>
                            </td>

                            <!-- Posisi Badge -->
                            <td class="cell-nowrap">
                                <template x-if="e.posisi === 'sales'">
                                    <span class="badge badge-warning" style="font-weight:700;">💼 Sales Toko</span>
                                </template>
                                <template x-if="e.posisi === 'driver'">
                                    <span class="badge badge-info" style="font-weight:700;">🚚 Driver Logistik</span>
                                </template>
                                <template x-if="e.posisi === 'pengemasan'">
                                    <span class="badge badge-success" style="font-weight:700;">📦 Buruh Kemas</span>
                                </template>
                                <template x-if="e.posisi === 'gudang'">
                                    <span class="badge badge-secondary" style="font-weight:700;">🏭 Staf Gudang</span>
                                </template>
                                <template x-if="e.posisi === 'admin'">
                                    <span class="badge badge-primary" style="font-weight:700;">💻 Admin Kantor</span>
                                </template>
                                <template x-if="e.posisi === 'mandor'">
                                    <span class="badge" style="font-weight:700;background:rgba(245,158,11,0.15);color:#d97706;border:1px solid rgba(245,158,11,0.3);">👷 Mandor / SPV</span>
                                </template>
                            </td>

                            <!-- Tipe Gaji -->
                            <td class="cell-center cell-nowrap">
                                <span class="badge" :class="e.tipe_penggajian === 'borongan' ? 'badge-primary' : 'badge-info'" style="text-transform:capitalize;font-weight:700;" x-text="e.tipe_penggajian || 'borongan'"></span>
                            </td>

                            <!-- Gaji Pokok & Komisi -->
                            <td class="cell-right cell-nowrap">
                                <template x-if="e.tipe_penggajian === 'borongan'">
                                    <div>
                                        <div style="font-weight:700;font-size:13px;font-family:var(--font-mono);color:var(--color-ink);" x-text="formatRupiah(e.gaji_pokok_bulanan)"></div>
                                        <span class="badge badge-secondary font-mono" style="font-size:10px;padding:1px 5px;margin-top:2px;display:inline-block;">+ Upah Borongan</span>
                                        <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:1px;">Hasil Kemas (Pcs)</div>
                                    </div>
                                </template>
                                <template x-if="e.tipe_penggajian !== 'borongan'">
                                    <div>
                                        <div style="font-weight:700;font-size:13px;font-family:var(--font-mono);color:var(--color-ink);" x-text="formatRupiah(e.gaji_pokok_bulanan)"></div>
                                        <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:1px;">Gaji Pokok Tetap</div>
                                        <template x-if="e.posisi === 'sales'">
                                            <span class="badge badge-warning" style="font-size:10px;padding:1px 5px;margin-top:3px;display:inline-block;" x-text="'+ Komisi Omzet'"></span>
                                        </template>
                                        <template x-if="e.posisi === 'driver'">
                                            <span class="badge badge-info" style="font-size:10px;padding:1px 5px;margin-top:3px;display:inline-block;" x-text="'+ Uang Jalan / Hadir'"></span>
                                        </template>
                                    </div>
                                </template>
                            </td>

                            <!-- Uang Hadir & Tunjangan -->
                            <td>
                                <div style="display:flex;flex-direction:column;gap:2px;">
                                    <div style="font-size:12px;display:flex;align-items:center;gap:4px;">
                                        <span style="color:var(--color-ink-mute);font-size:11px;">Hadir:</span>
                                        <span style="font-family:var(--font-mono);font-weight:600;" x-text="formatRupiah(e.uang_kehadiran_harian) + '/hr'"></span>
                                    </div>
                                    <div style="font-size:12px;display:flex;align-items:center;gap:4px;">
                                        <span style="color:var(--color-ink-mute);font-size:11px;">Tunjangan:</span>
                                        <span style="font-family:var(--font-mono);font-weight:600;" x-text="formatRupiah(e.tunjangan_bulanan) + '/bln'"></span>
                                    </div>
                                </div>
                            </td>

                            <!-- Rekening Bank -->
                            <td class="cell-nowrap">
                                <div style="font-size:12px;font-weight:600;" x-text="e.bank_nama || 'Tunai'"></div>
                                <template x-if="e.bank_nomor_rekening">
                                    <div style="font-size:11px;font-family:var(--font-mono);color:var(--color-ink-mute);" x-text="e.bank_nomor_rekening + (e.bank_atas_nama ? ' (a.n. ' + e.bank_atas_nama + ')' : '')"></div>
                                </template>
                            </td>

                            <!-- Status Aktif -->
                            <td class="cell-center cell-nowrap">
                                <span class="badge" :class="e.status_aktif ? 'badge-success' : 'badge-danger'" x-text="e.status_aktif ? 'Aktif' : 'Nonaktif'"></span>
                            </td>

                            <?php if (\App\Core\Auth::can('master.employees_manage')): ?>
                            <!-- Aksi -->
                            <td class="cell-center cell-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="openEditModal(e)" class="btn btn-ghost btn-sm" style="padding:6px 8px;" title="Edit Data Karyawan">
                                        <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                    </button>
                                    <template x-if="e.status_aktif">
                                        <button @click="deactivateEmployee(e.id, e.nama_karyawan)" class="btn btn-ghost btn-sm" style="padding:6px 8px;color:#ef4444;" title="Nonaktifkan Karyawan">
                                            <i data-lucide="user-x" style="width:14px;height:14px;"></i>
                                        </button>
                                    </template>
                                </div>
                            </td>
                            <?php endif; ?>

                        </tr>
                    </template>

                    <template x-if="filteredEmployees.length === 0">
                        <tr>
                            <td colspan="<?= \App\Core\Auth::can('master.employees_manage') ? 8 : 7 ?>" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="search-x" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Tidak ada data karyawan yang cocok</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: TAMBAH / EDIT MASTER KARYAWAN                                     -->
    <!-- ========================================================================= -->
    <?php if (\App\Core\Auth::can('master.employees_manage')): ?>
    <template x-teleport="body">
    <div x-show="showModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:580px;padding:24px;">
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(16,185,129,0.1);color:#10b981;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="user-plus" style="width:16px;height:16px;"></i>
                    </div>
                    <div class="modal-title" x-text="isEdit ? 'Edit Data Karyawan' : 'Tambah Karyawan Baru'"></div>
                </div>
                <button @click="showModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form :action="isEdit ? '<?= Router::url('/employees/update') ?>' : '<?= Router::url('/employees/store') ?>'" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="id" :value="form.id">

                <!-- SECTION 1: BIODATA -->
                <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-primary);border-bottom:1px solid var(--color-hairline);padding-bottom:4px;">
                    1. Identitas &amp; Kontak Pegawai
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nama Lengkap Karyawan *</label>
                        <input type="text" name="nama_karyawan" x-model="form.nama_karyawan" required class="form-input" placeholder="Contoh: Teh Ika">
                    </div>
                    <div>
                        <label class="form-label">Nomor WhatsApp *</label>
                        <input type="text" name="nomor_whatsapp" x-model="form.nomor_whatsapp" class="form-input font-mono" placeholder="08123456789">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">NIK / No. KTP (Opsional)</label>
                        <input type="text" name="nik" x-model="form.nik" 
                               @input="form.nik = $event.target.value.replace(/[^0-9]/g, '').slice(0, 16)"
                               maxlength="16" class="form-input font-mono" placeholder="3201xxxxxxxxxxxx (16 Digit)">
                    </div>
                    <div>
                        <label class="form-label">Alamat Domisili</label>
                        <input type="text" name="alamat" x-model="form.alamat" class="form-input" placeholder="Alamat tinggal karyawan">
                    </div>
                </div>

                <!-- SECTION 2: DIVISI & SKEMA GAJI -->
                <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-primary);border-bottom:1px solid var(--color-hairline);padding-bottom:4px;margin-top:6px;">
                    2. Penempatan Kerja &amp; Skema Remunerasi
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Divisi / Posisi Kerja *</label>
                        <select name="posisi" x-model="form.posisi" @change="onPosisiChange()" required class="form-input" style="font-weight:600;">
                            <option value="sales">💼 Sales (Canvaser &amp; Komisi Toko)</option>
                            <option value="driver">🚚 Driver (Supir Logistik &amp; Pengantar)</option>
                            <option value="pengemasan">🍿 Pengemasan (Packing Borongan)</option>
                            <option value="gudang">📦 Staff Gudang &amp; Sortir</option>
                            <option value="admin">👩‍💼 Admin &amp; Kasir Kantor</option>
                            <option value="mandor">👷 Mandor / Supervisor</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Tipe Penggajian *</label>
                        <select name="tipe_penggajian" x-model="form.tipe_penggajian" required class="form-input" style="font-weight:600;">
                            <option value="borongan">🍿 Borongan (Kolom Terisi + Upah Borongan)</option>
                            <option value="bulanan">💼 Bulanan (Sesuai Kolom Terisi)</option>
                        </select>
                    </div>
                </div>

                <!-- FORMULA KETENTUAN TIPE PENGGAJIAN -->
                <div style="padding:10px 14px;background:rgba(241,245,249,0.9);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);font-size:11.5px;color:var(--color-ink-secondary);line-height:1.45;">
                    <div style="font-weight:700;color:var(--color-ink);margin-bottom:4px;display:flex;align-items:center;gap:5px;">
                        <i data-lucide="calculator" style="width:14px;height:14px;color:var(--color-primary);"></i>
                        Formula Perhitungan Gaji:
                    </div>
                    <div style="display:flex;flex-direction:column;gap:3px;">
                        <div>• <strong style="color:var(--color-primary);">Borongan:</strong> Total Gaji = <em>Kolom Terisi (Gaji Pokok + Uang Hadir × Hari Masuk + Tunjangan)</em> + <strong>Upah Borongan</strong> (Pcs hasil produksi packing).</div>
                        <div>• <strong style="color:#0284c7);">Bulanan:</strong> Total Gaji = <em>Hanya Kolom Terisi (Gaji Pokok + Uang Hadir × Hari Masuk + Tunjangan)</em>. Khusus Sales ditambah <strong>Komisi Omzet</strong>. Driver tanpa gapok cukup isi Uang Hadir/Jalan.</div>
                    </div>
                </div>

                <!-- DYNAMIC CASE 1: PENGEMASAN (BORONGAN) -->
                <template x-if="form.posisi === 'pengemasan'">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div style="padding:10px 14px;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:var(--rounded-md);font-size:12px;color:var(--color-ink-secondary);display:flex;align-items:flex-start;gap:8px;">
                            <i data-lucide="info" style="width:16px;height:16px;color:#3b82f6;flex-shrink:0;margin-top:2px;"></i>
                            <div>
                                <strong style="color:var(--color-ink);">Skema Upah Borongan:</strong>
                                <div>Upah borongan dihitung otomatis per bungkus snack saat input data produksi packing, ditambah nominal kolom terisi di bawah.</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="form-label">Gaji Pokok (Rp/bln)</label>
                                <input type="text" name="gaji_pokok_bulanan" x-model="form.gaji_pokok_bulanan" class="form-input font-mono input-rupiah" placeholder="0">
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">Opsional / default 0</div>
                            </div>
                            <div>
                                <label class="form-label">Uang Hadir Harian (Rp/hari) *</label>
                                <input type="text" name="uang_kehadiran_harian" x-model="form.uang_kehadiran_harian" required class="form-input font-mono input-rupiah" placeholder="10.000">
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">Diberikan setiap hari masuk kerja</div>
                            </div>
                            <div>
                                <label class="form-label">Tunjangan Bulanan (Rp/bln)</label>
                                <input type="text" name="tunjangan_bulanan" x-model="form.tunjangan_bulanan" class="form-input font-mono input-rupiah" placeholder="50.000">
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">Bonus / tunjangan tetap bulanan</div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- DYNAMIC CASE 2: SALES (KOMISI BERTINGKAT & PARAMETER PENGGAJIAN FLEKSIBEL) -->
                <template x-if="form.posisi === 'sales'">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div style="padding:10px 14px;background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.25);border-radius:var(--rounded-md);font-size:12px;color:var(--color-ink-secondary);display:flex;align-items:center;justify-content:space-between;gap:10px;">
                            <div style="display:flex;align-items:flex-start;gap:8px;">
                                <i data-lucide="trending-up" style="width:16px;height:16px;color:#10b981;flex-shrink:0;margin-top:2px;"></i>
                                <div>
                                    <strong style="color:var(--color-ink);">Skema Komisi Bertingkat:</strong>
                                    <div>Komisi dihitung otomatis terpusat dari tier pencapaian omzet toko binaan sales.</div>
                                </div>
                            </div>
                            <button type="button" @click="openTierModal()" class="btn btn-secondary" style="font-size:11px;padding:4px 8px;white-space:nowrap;display:inline-flex;align-items:center;gap:4px;flex-shrink:0;">
                                <i data-lucide="sliders" style="width:12px;height:12px;"></i>
                                Skema Komisi
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Gaji Pokok (Rp/bln)</label>
                                <input type="text" name="gaji_pokok_bulanan" x-model="form.gaji_pokok_bulanan" class="form-input font-mono input-rupiah" placeholder="0">
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">Gaji pokok bulanan (isi 0 jika tidak ada)</div>
                            </div>
                            <div>
                                <label class="form-label">Uang Kehadiran / Hadir (Rp/hari)</label>
                                <input type="text" name="uang_kehadiran_harian" x-model="form.uang_kehadiran_harian" class="form-input font-mono input-rupiah" placeholder="0">
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">Uang kehadiran per hari masuk (isi 0 jika tidak ada)</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Tunjangan Bulanan (Rp/bln)</label>
                                <input type="text" name="tunjangan_bulanan" x-model="form.tunjangan_bulanan" class="form-input font-mono input-rupiah" placeholder="0">
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">Tunjangan tetap bulanan (opsional)</div>
                            </div>
                            <div>
                                <label class="form-label">Plat No. Kendaraan (Opsional)</label>
                                <input type="text" name="nomor_polisi_kendaraan" x-model="form.nomor_polisi_kendaraan" 
                                       @input="form.nomor_polisi_kendaraan = $event.target.value.toUpperCase().slice(0, 12)"
                                       maxlength="12" class="form-input font-mono uppercase" placeholder="Contoh: B 9876 KRS">
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">Kendaraan operasional kanvaser</div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- DYNAMIC CASE 3: DRIVER (MURNI PENGANTAR, TANPA KOMISI) -->
                <template x-if="form.posisi === 'driver'">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div>
                            <label class="form-label">Plat No. Armada Truk / Mobil *</label>
                            <input type="text" name="nomor_polisi_kendaraan" x-model="form.nomor_polisi_kendaraan" 
                                   @input="form.nomor_polisi_kendaraan = $event.target.value.toUpperCase().slice(0, 12)"
                                   maxlength="12" class="form-input font-mono uppercase" placeholder="Contoh: B 1234 ABC" required>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Gaji Pokok (Rp/bln)</label>
                                <input type="text" name="gaji_pokok_bulanan" x-model="form.gaji_pokok_bulanan" class="form-input font-mono input-rupiah" placeholder="0">
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">Isi 0 jika tanpa gaji pokok tetap</div>
                            </div>
                            <div>
                                <label class="form-label">Uang Jalan / Hadir Harian (Rp/hari) *</label>
                                <input type="text" name="uang_kehadiran_harian" x-model="form.uang_kehadiran_harian" class="form-input font-mono input-rupiah" placeholder="120.000">
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">Dikalikan jumlah hari jalan/kerja</div>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Tunjangan Bulanan (Rp/bln)</label>
                            <input type="text" name="tunjangan_bulanan" x-model="form.tunjangan_bulanan" class="form-input font-mono input-rupiah" placeholder="0">
                        </div>
                    </div>
                </template>

                <!-- DYNAMIC CASE 3: GUDANG & ADMIN -->
                <template x-if="form.posisi === 'gudang' || form.posisi === 'admin'">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Gaji Pokok (Rp/bln) *</label>
                                <input type="text" name="gaji_pokok_bulanan" x-model="form.gaji_pokok_bulanan" required class="form-input font-mono input-rupiah" placeholder="2.500.000">
                            </div>
                            <div>
                                <label class="form-label">Uang Kehadiran (Rp/hari)</label>
                                <input type="text" name="uang_kehadiran_harian" x-model="form.uang_kehadiran_harian" class="form-input font-mono input-rupiah" placeholder="15.000">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Tunjangan Bulanan (Rp/bln)</label>
                            <input type="text" name="tunjangan_bulanan" x-model="form.tunjangan_bulanan" class="form-input font-mono input-rupiah" placeholder="100.000">
                        </div>
                    </div>
                </template>

                <!-- DYNAMIC CASE 4: MANDOR / SUPERVISOR -->
                <template x-if="form.posisi === 'mandor'">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Gaji Pokok Bulanan (Rp/bln) *</label>
                                <input type="text" name="gaji_pokok_bulanan" x-model="form.gaji_pokok_bulanan" required class="form-input font-mono input-rupiah" placeholder="3.000.000">
                            </div>
                            <div>
                                <label class="form-label">Tunjangan Jabatan Mandor (Rp/bln)</label>
                                <input type="text" name="tunjangan_bulanan" x-model="form.tunjangan_bulanan" class="form-input font-mono input-rupiah" placeholder="500.000">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Uang Kehadiran (Rp/hari)</label>
                            <input type="text" name="uang_kehadiran_harian" x-model="form.uang_kehadiran_harian" class="form-input font-mono input-rupiah" placeholder="20.000">
                        </div>
                    </div>
                </template>

                <!-- SECTION 3: REKENING BANK -->
                <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-primary);border-bottom:1px solid var(--color-hairline);padding-bottom:4px;margin-top:6px;">
                    3. Rekening Pembayaran Gaji
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="form-label">Metode / Bank</label>
                        <select name="bank_nama" x-model="form.bank_nama" class="form-input">
                            <option value="Tunai">Tunai (Cash)</option>
                            <option value="BCA">Bank BCA</option>
                            <option value="BRI">Bank BRI</option>
                            <option value="Mandiri">Bank Mandiri</option>
                            <option value="BNI">Bank BNI</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Nomor Rekening</label>
                        <input type="text" name="bank_nomor_rekening" x-model="form.bank_nomor_rekening"
                               @input="form.bank_nomor_rekening = $event.target.value.replace(/[^0-9-]/g, '').slice(0, 25)"
                               maxlength="25" class="form-input font-mono" placeholder="Nomor rekening">
                    </div>

                    <div>
                        <label class="form-label">Atas Nama Rekening <template x-if="form.bank_nomor_rekening"><span style="color:var(--color-danger);">*</span></template></label>
                        <input type="text" name="bank_atas_nama" x-model="form.bank_atas_nama"
                               :required="!!form.bank_nomor_rekening"
                               class="form-input" placeholder="Nama pemilik rek">
                    </div>
                </div>
                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:4px;">Opsional. Jika nomor rekening diisi, pemilik rekening wajib diisi.</div>

                <template x-if="isEdit">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12.5px;font-weight:600;margin-top:4px;">
                        <input type="checkbox" name="status_aktif" x-model="form.status_aktif" style="width:16px;height:16px;accent-color:var(--color-primary);">
                        <span>Karyawan Masih Aktif Bekerja</span>
                    </label>
                </template>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px;">
                    <button type="button" @click="showModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Karyawan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL PENGATURAN SKEMA KOMISI BERTINGKAT                                  -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showTierModal" 
         x-cloak 
         class="modal-backdrop" 
         @click.self="showTierModal = false" 
         @keydown.escape.window="showTierModal = false">
        
        <div class="skema-modal-box" @click.stop>
            
            <!-- Mobile Pull Bar Indicator -->
            <div class="skema-mobile-pull">
                <div class="skema-mobile-pull-bar"></div>
            </div>

            <!-- Modal Header -->
            <div class="skema-modal-header">
                <div class="skema-modal-header-left">
                    <div class="skema-modal-header-icon">
                        <i data-lucide="award"></i>
                    </div>
                    <div class="skema-modal-header-text">
                        <div class="skema-modal-title">
                            <span>Pengaturan Skema Komisi Bertingkat</span>
                            <span class="badge badge-warning" style="font-size:10px;padding:1px 6px;font-weight:800;" x-text="editableTiers.length + ' Tier'"></span>
                        </div>
                        <div class="skema-modal-subtitle">Konfigurasi ambang batas omzet bulanan &amp; simulasi komisi otomatis</div>
                    </div>
                </div>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="skema-modal-body">

                <!-- Alert Penjelasan Sistem (Edukasi Ketentuan Komisi) -->
                <div class="skema-info-alert">
                    <div class="skema-info-icon">
                        <i data-lucide="info"></i>
                    </div>
                    <div class="skema-info-content">
                        <div class="skema-info-title-row">
                            <span class="skema-info-title">Ketentuan Perhitungan Komisi Sales:</span>
                            <span class="skema-info-badge">Otomatis Terintegrasi</span>
                        </div>
                        <div class="skema-info-list">
                            <div class="skema-info-item">
                                <span class="skema-info-bullet">1</span>
                                <div class="skema-info-text">
                                    <strong>Dasar Kas Masuk (Cash Basis):</strong> Akumulasi omzet hanya dihitung dari faktur konsinyasi &amp; pesanan grosir yang telah dibayar lunas (kas masuk riil) dalam 1 bulan berjalan.
                                </div>
                            </div>
                            <div class="skema-info-item">
                                <span class="skema-info-bullet">2</span>
                                <div class="skema-info-text">
                                    <strong>Sistem Flat Retroaktif:</strong> Saat target tier berikutnya tercapai, 100% total omzet terbayar di bulan berjalan langsung dikalikan tarif persen tier baru secara penuh (bukan berjenjang selisih).
                                </div>
                            </div>
                            <div class="skema-info-item">
                                <span class="skema-info-bullet">3</span>
                                <div class="skema-info-text">
                                    <strong>Penangguhan Piutang:</strong> Sisa piutang toko atau kunjungan opname belum berbayar ditangguhkan, dan baru dihitung omzetnya setelah pembayaran toko resmi dilunasi.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Header -->
                <div class="skema-section-header">
                    <div class="skema-section-title">
                        <span>Daftar Tingkatan Tier (Rendah ke Tinggi)</span>
                        <span class="badge badge-secondary" style="font-family:var(--font-mono);font-size:10px;padding:2px 7px;font-weight:800;" x-text="editableTiers.length + ' TINGKAT'"></span>
                    </div>
                    <button type="button" @click="addTierRow()" class="btn btn-secondary btn-sm" style="font-weight:700;font-size:11.5px;display:inline-flex;align-items:center;gap:6px;">
                        <i data-lucide="plus" style="width:14px;height:14px;color:#f59e0b;"></i>
                        <span>Tambah Baris Tier</span>
                    </button>
                </div>

                <!-- DESKTOP / TABLET VIEW: CLEAN DATA TABLE -->
                <div class="skema-table-wrap">
                    <table class="skema-table">
                        <thead>
                            <tr>
                                <th style="width:40px;text-align:center;">#</th>
                                <th style="min-width:140px;">Nama Tingkatan (Tier)</th>
                                <th style="min-width:140px;">Omzet Min (Rp)</th>
                                <th style="min-width:160px;">Omzet Maks (Rp)</th>
                                <th style="width:110px;text-align:center;">Komisi (%)</th>
                                <th style="width:50px;text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(tier, idx) in editableTiers" :key="'desk-' + idx">
                                <tr>
                                    <!-- Urutan -->
                                    <td style="text-align:center;font-weight:800;color:var(--color-ink-mute);font-family:var(--font-mono);" x-text="idx + 1"></td>

                                    <!-- Nama Tier -->
                                    <td>
                                        <input type="text" x-model="tier.nama_tier" class="form-input" style="height:34px;font-size:12px;font-weight:700;" placeholder="Misal: Tier 1 (Dasar)" required>
                                    </td>

                                    <!-- Omzet Min -->
                                    <td>
                                        <input type="text" 
                                               :value="formatInputRupiah(tier.omzet_min)"
                                               @input="tier.omzet_min = parseInputRupiah($event.target.value); $event.target.value = formatInputRupiah(tier.omzet_min); runSimulation();"
                                               class="form-input font-mono" style="height:34px;font-size:12px;" placeholder="0">
                                    </td>

                                    <!-- Omzet Maks & Checkbox Tanpa Batas -->
                                    <td>
                                        <div>
                                            <input type="text" 
                                                   :disabled="tier.tanpa_batas"
                                                   :value="tier.tanpa_batas ? 'Tanpa Batas Atas (∞)' : formatInputRupiah(tier.omzet_maks)"
                                                   @input="tier.omzet_maks = parseInputRupiah($event.target.value); $event.target.value = formatInputRupiah(tier.omzet_maks); runSimulation();"
                                                   class="form-input font-mono" 
                                                   :style="tier.tanpa_batas ? 'height:34px;font-size:12px;background:rgba(148,163,184,0.1);color:var(--color-ink-mute);cursor:not-allowed;font-style:italic;' : 'height:34px;font-size:12px;'"
                                                   placeholder="Batas atas">
                                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:11px;color:var(--color-ink-mute);margin-top:4px;user-select:none;">
                                                <input type="checkbox" x-model="tier.tanpa_batas" @change="if(tier.tanpa_batas) tier.omzet_maks = null; runSimulation();" style="width:14px;height:14px;cursor:pointer;accent-color:#f59e0b;">
                                                <span>Tanpa Batas Atas</span>
                                            </label>
                                        </div>
                                    </td>

                                    <!-- Persentase Komisi -->
                                    <td>
                                        <div style="display:flex;align-items:center;gap:4px;justify-content:center;">
                                            <input type="number" step="0.1" min="0" max="100" x-model.number="tier.persentase" @input="runSimulation()" class="form-input" style="height:34px;font-size:12px;font-weight:800;text-align:center;width:58px;" placeholder="0">
                                            <span style="font-weight:700;color:var(--color-ink-mute);font-size:12px;">%</span>
                                        </div>
                                    </td>

                                    <!-- Tombol Hapus -->
                                    <td style="text-align:center;">
                                        <button type="button" @click="removeTierRow(idx)" class="btn btn-icon btn-ghost btn-sm" style="color:#ef4444;padding:4px;" :disabled="editableTiers.length <= 1" title="Hapus Baris">
                                            <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- MOBILE VIEW (< 640px): SLEEK TOUCH CARDS -->
                <div class="skema-cards-wrap">
                    <template x-for="(tier, idx) in editableTiers" :key="'mob-' + idx">
                        <div class="skema-tier-card">
                            <!-- Card Header: Badge + Nama + Hapus -->
                            <div class="skema-tier-card-head">
                                <div style="display:flex;align-items:center;gap:8px;flex:1;">
                                    <div class="skema-tier-card-badge" x-text="idx + 1"></div>
                                    <input type="text" x-model="tier.nama_tier" class="form-input" style="height:34px;font-size:12px;font-weight:800;flex:1;" placeholder="Nama Tier (misal: Tier 1)">
                                </div>
                                <button type="button" @click="removeTierRow(idx)" class="btn btn-icon btn-ghost btn-sm" style="color:#ef4444;padding:4px;flex-shrink:0;" :disabled="editableTiers.length <= 1" title="Hapus Tier">
                                    <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                                </button>
                            </div>

                            <!-- Card Body: Omzet Min & Omzet Maks -->
                            <div class="skema-tier-card-inputs">
                                <div>
                                    <div style="font-size:10.5px;font-weight:700;color:var(--color-ink-secondary);margin-bottom:3px;">Omzet Min (Rp):</div>
                                    <input type="text" 
                                           :value="formatInputRupiah(tier.omzet_min)"
                                           @input="tier.omzet_min = parseInputRupiah($event.target.value); $event.target.value = formatInputRupiah(tier.omzet_min); runSimulation();"
                                           class="form-input font-mono" style="height:34px;font-size:12px;" placeholder="0">
                                </div>
                                <div>
                                    <div style="font-size:10.5px;font-weight:700;color:var(--color-ink-secondary);margin-bottom:3px;">Omzet Maks (Rp):</div>
                                    <input type="text" 
                                           :disabled="tier.tanpa_batas"
                                           :value="tier.tanpa_batas ? 'Tanpa Batas (∞)' : formatInputRupiah(tier.omzet_maks)"
                                           @input="tier.omzet_maks = parseInputRupiah($event.target.value); $event.target.value = formatInputRupiah(tier.omzet_maks); runSimulation();"
                                           class="form-input font-mono" 
                                           :style="tier.tanpa_batas ? 'height:34px;font-size:12px;background:rgba(148,163,184,0.1);color:var(--color-ink-mute);cursor:not-allowed;font-style:italic;' : 'height:34px;font-size:12px;'"
                                           placeholder="Batas atas">
                                </div>
                            </div>

                            <!-- Card Footer: Checkbox Tanpa Batas & Komisi % -->
                            <div class="skema-tier-card-foot">
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:11px;color:var(--color-ink-mute);user-select:none;">
                                    <input type="checkbox" x-model="tier.tanpa_batas" @change="if(tier.tanpa_batas) tier.omzet_maks = null; runSimulation();" style="width:14px;height:14px;cursor:pointer;accent-color:#f59e0b;">
                                    <span>Tanpa Batas (∞)</span>
                                </label>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <span style="font-size:11px;font-weight:700;color:var(--color-ink-secondary);">Komisi:</span>
                                    <input type="number" step="0.1" min="0" max="100" x-model.number="tier.persentase" @input="runSimulation()" class="form-input" style="height:32px;font-size:12px;font-weight:800;text-align:center;width:56px;" placeholder="0">
                                    <span style="font-weight:800;font-size:12px;color:var(--color-ink-mute);">%</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- LIVE SIMULATOR / KALKULATOR CEPAT -->
                <div class="skema-calc-box">
                    <div class="skema-calc-header">
                        <div class="skema-calc-header-left">
                            <div class="skema-calc-header-icon">
                                <i data-lucide="calculator"></i>
                            </div>
                            <span class="skema-calc-header-title">Kalkulator Simulator Omzet Cepat</span>
                        </div>
                        <span class="skema-calc-header-sub">Uji coba simulasi komisi dengan skema di atas</span>
                    </div>

                    <div class="skema-calc-grid">
                        <!-- Input Omzet -->
                        <div class="skema-calc-card">
                            <div class="skema-calc-card-label">Input Omzet Terbayar:</div>
                            <div style="position:relative;display:flex;align-items:center;">
                                <input type="text" 
                                       :value="formatInputRupiah(simulasiOmzet)" 
                                       @input="simulasiOmzet = parseInputRupiah($event.target.value); $event.target.value = formatInputRupiah(simulasiOmzet); runSimulation();"
                                       class="form-input font-mono" 
                                       style="height:36px;font-size:13px;font-weight:800;width:100%;padding-right:30px;">
                                <span style="position:absolute;right:10px;font-size:11px;font-weight:700;color:var(--color-ink-mute);pointer-events:none;">Rp</span>
                            </div>
                        </div>

                        <!-- Hasil Simulasi: Tier & Rate -->
                        <div class="skema-calc-card">
                            <div class="skema-calc-card-label">Tier &amp; Persentase Diraih:</div>
                            <div style="display:flex;align-items:center;gap:6px;margin-top:2px;">
                                <span style="font-weight:800;font-size:13px;color:#d97706;" x-text="simResult.nama_tier"></span>
                                <span class="badge badge-success" style="font-size:11px;padding:2px 7px;font-weight:800;" x-text="simResult.persentase + '%'"></span>
                            </div>
                        </div>

                        <!-- Hasil Simulasi: Nominal Komisi -->
                        <div class="skema-calc-card">
                            <div class="skema-calc-card-label">Estimasi Nominal Komisi:</div>
                            <div style="font-weight:900;font-size:15px;color:#059669;font-family:var(--font-mono);margin-top:2px;" x-text="formatRupiah(simResult.nominal_komisi)"></div>
                        </div>
                    </div>

                    <!-- Progress / Gap ke Tier Berikutnya -->
                    <template x-if="simResult.has_next">
                        <div class="skema-calc-gap-banner">
                            <div class="skema-calc-gap-icon">
                                <i data-lucide="target"></i>
                            </div>
                            <div style="line-height:1.4;">
                                Kurang <strong style="color:#2563eb;font-family:var(--font-mono);font-weight:800;" x-text="formatRupiah(simResult.gap_omzet)"></strong> untuk naik ke <strong x-text="simResult.next_tier_nama"></strong> (<span style="font-weight:700;" x-text="simResult.next_tier_persen + '%'"></span>)
                            </div>
                        </div>
                    </template>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="skema-modal-footer">
                <div class="skema-modal-footer-note">
                    Perubahan langsung berlaku pada perhitungan di menu Rekap Komisi Sales.
                </div>
                <div class="skema-modal-footer-actions">
                    <button type="button" @click="showTierModal = false" class="btn btn-secondary btn-sm" style="font-weight:700;padding:8px 16px;" :disabled="isSavingTiers">
                        Batal
                    </button>
                    <button type="button" @click="saveTierConfig()" class="btn btn-primary btn-sm" :disabled="isSavingTiers" style="background:#f59e0b;border-color:#f59e0b;color:#090d16;font-weight:800;padding:8px 18px;display:inline-flex;align-items:center;gap:6px;">
                        <i data-lucide="check" style="width:16px;height:16px;"></i>
                        <span x-text="isSavingTiers ? 'Menyimpan...' : 'Simpan Skema Komisi'"></span>
                    </button>
                </div>
            </div>

        </div>
    </div>
    </template>

    <!-- HIDDEN FORM FOR DEACTIVATING EMPLOYEE -->
    <form id="delete-employee-form" action="<?= Router::url('/employees/delete') ?>" method="POST" data-action-text="Menonaktifkan karyawan..." style="display:none;">
        <input type="hidden" name="id" id="delete-employee-id">
    </form>
    <?php endif; ?>

</div>

<script>
function employeeApp() {
    return {
        employees: <?= json_encode($employees) ?>,
        searchQuery: '',
        filterPosition: 'all',
        showModal: false,
        isEdit: false,

        // Skema Komisi Bertingkat State
        showTierModal: false,
        isSavingTiers: false,
        simulasiOmzet: 35000000,
        simResult: {
            nama_tier: 'Tier 2 (Reguler)',
            persentase: 2.5,
            nominal_komisi: 875000,
            has_next: true,
            gap_omzet: 1,
            next_tier_nama: 'Tier 3 (Gold)',
            next_tier_persen: 4.0
        },
        editableTiers: (<?= json_encode($commissionTiers ?? []) ?>).map(t => ({
            id: t.id,
            urutan: Number(t.urutan),
            nama_tier: t.nama_tier,
            omzet_min: Number(t.omzet_min),
            omzet_maks: t.omzet_maks !== null ? Number(t.omzet_maks) : null,
            tanpa_batas: t.omzet_maks === null,
            persentase: Number(t.persentase),
            status_aktif: Boolean(t.status_aktif)
        })),

        form: {
            id: '',
            nik: '',
            nama_karyawan: '',
            posisi: 'pengemasan',
            tipe_penggajian: 'borongan',
            gaji_pokok_bulanan: '0',
            uang_kehadiran_harian: '10.000',
            tunjangan_bulanan: '50.000',
            nomor_telepon: '',
            alamat: '',
            bank_nama: 'Tunai',
            bank_nomor_rekening: '',
            bank_atas_nama: '',
            status_aktif: true
        },

        init() {
            this.runSimulation();
            this.$watch('showTierModal', value => {
                if (value) {
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                    setTimeout(() => {
                        if (window.lucide) lucide.createIcons();
                    }, 50);
                }
            });
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        get filteredEmployees() {
            return this.employees.filter(e => {
                const q = this.searchQuery.toLowerCase();
                const matchQuery = !q ||
                    e.nama_karyawan.toLowerCase().includes(q) ||
                    (e.nik && e.nik.toLowerCase().includes(q)) ||
                    (e.nomor_telepon && e.nomor_telepon.includes(q));

                const matchPos = this.filterPosition === 'all' || e.posisi === this.filterPosition;
                return matchQuery && matchPos;
            });
        },

        get salesTipePenggajian() {
            return 'bulanan';
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        formatInputRupiah(val) {
            if (val === null || val === undefined || val === '') return '';
            return Number(val).toLocaleString('id-ID');
        },

        parseInputRupiah(str) {
            if (!str) return 0;
            const cleaned = String(str).replace(/[^0-9]/g, '');
            return cleaned ? parseFloat(cleaned) : 0;
        },

        openTierModal() {
            this.showTierModal = true;
            this.runSimulation();
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
            setTimeout(() => {
                if (window.lucide) lucide.createIcons();
            }, 60);
        },

        addTierRow() {
            const lastTier = this.editableTiers[this.editableTiers.length - 1];
            let nextMin = 0;
            if (lastTier) {
                if (lastTier.tanpa_batas) {
                    lastTier.tanpa_batas = false;
                    lastTier.omzet_maks = lastTier.omzet_min + 20000000;
                }
                nextMin = (lastTier.omzet_maks || lastTier.omzet_min) + 1;
            }
            const nextUrutan = this.editableTiers.length + 1;
            this.editableTiers.push({
                id: null,
                urutan: nextUrutan,
                nama_tier: 'Tier ' + nextUrutan,
                omzet_min: nextMin,
                omzet_maks: null,
                tanpa_batas: true,
                persentase: lastTier ? Math.min(100, lastTier.persentase + 1) : 1,
                status_aktif: true
            });
            this.runSimulation();
            this.$nextTick(() => lucide.createIcons());
        },

        removeTierRow(idx) {
            if (this.editableTiers.length <= 1) {
                if (window.AppAction) {
                    window.AppAction.error('Aksi Dibatasi!', 'Minimal harus ada 1 tingkatan (tier) skema komisi.', 2200);
                } else if (window.toast) {
                    window.toast.error('Minimal harus ada 1 tingkatan (tier) skema komisi.');
                }
                return;
            }
            this.editableTiers.splice(idx, 1);
            this.runSimulation();
            this.$nextTick(() => lucide.createIcons());
        },

        runSimulation() {
            const omzet = Number(this.simulasiOmzet || 0);
            const tiers = this.editableTiers.slice().sort((a, b) => a.urutan - b.urutan);
            let matched = null;
            for (let i = tiers.length - 1; i >= 0; i--) {
                const t = tiers[i];
                if (omzet >= t.omzet_min && (t.tanpa_batas || t.omzet_maks === null || omzet <= t.omzet_maks)) {
                    matched = t;
                    break;
                }
            }

            if (!matched && tiers.length > 0) {
                matched = {
                    nama_tier: 'Di Bawah Minimum',
                    persentase: 0,
                    urutan: 0
                };
            }

            const persen = matched ? matched.persentase : 0;
            const nominal = Math.round(omzet * (persen / 100));

            // Next tier
            let nextTier = null;
            if (matched) {
                nextTier = tiers.find(t => t.urutan > matched.urutan);
            } else if (tiers.length > 0) {
                nextTier = tiers[0];
            }

            let gap = 0;
            if (nextTier) {
                gap = Math.max(0, nextTier.omzet_min - omzet);
            }

            this.simResult = {
                nama_tier: matched ? matched.nama_tier : 'Tanpa Tier',
                persentase: persen,
                nominal_komisi: nominal,
                has_next: Boolean(nextTier),
                gap_omzet: gap,
                next_tier_nama: nextTier ? nextTier.nama_tier : '',
                next_tier_persen: nextTier ? nextTier.persentase : 0
            };
        },

        async saveTierConfig() {
            // 1. Validasi input tier sebelum kirim ke server
            for (let i = 0; i < this.editableTiers.length; i++) {
                const t = this.editableTiers[i];
                const tierNum = i + 1;
                if (!t.nama_tier || !t.nama_tier.trim()) {
                    if (window.AppAction) {
                        await window.AppAction.error('Nama Tier Wajib Diisi!', `Harap isi nama tingkatan pada baris ke-${tierNum}.`, 2400);
                    } else if (window.toast) {
                        window.toast.error(`Harap isi nama tingkatan pada baris ke-${tierNum}.`);
                    }
                    return;
                }
                if (t.omzet_min < 0) {
                    if (window.AppAction) {
                        await window.AppAction.error('Omzet Tidak Valid!', `Omzet minimum pada ${t.nama_tier} tidak boleh bernilai negatif.`, 2400);
                    } else if (window.toast) {
                        window.toast.error(`Omzet minimum pada ${t.nama_tier} tidak boleh bernilai negatif.`);
                    }
                    return;
                }
                if (!t.tanpa_batas && t.omzet_maks !== null && Number(t.omzet_maks) <= Number(t.omzet_min)) {
                    if (window.AppAction) {
                        await window.AppAction.error('Batas Omzet Tidak Valid!', `Omzet maksimum pada ${t.nama_tier} harus lebih besar dari omzet minimum.`, 2600);
                    } else if (window.toast) {
                        window.toast.error(`Omzet maksimum pada ${t.nama_tier} harus lebih besar dari omzet minimum.`);
                    }
                    return;
                }
                if (t.persentase < 0 || t.persentase > 100) {
                    if (window.AppAction) {
                        await window.AppAction.error('Persentase Tidak Valid!', `Persentase komisi pada ${t.nama_tier} harus di antara 0% dan 100%.`, 2500);
                    } else if (window.toast) {
                        window.toast.error(`Persentase komisi pada ${t.nama_tier} harus di antara 0% dan 100%.`);
                    }
                    return;
                }
            }

            this.isSavingTiers = true;
            if (window.AppAction) {
                window.AppAction.show('Menyimpan skema komisi...', 'Memperbarui ambang batas & persentase tier');
            }

            try {
                const payload = {
                    tiers: this.editableTiers.map((t, i) => ({
                        id: t.id,
                        urutan: i + 1,
                        nama_tier: t.nama_tier,
                        omzet_min: t.omzet_min,
                        omzet_maks: t.tanpa_batas ? null : t.omzet_maks,
                        tanpa_batas: t.tanpa_batas,
                        persentase: t.persentase,
                        status_aktif: true
                    }))
                };

                const res = await fetch('<?= Router::url('/employees/commission-tiers/batch-save') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.message || 'Gagal memperbarui skema komisi.');
                }

                // Tutup modal konfigurasi agar notifikasi tengah layar bersih
                this.showTierModal = false;

                if (window.AppAction) {
                    await window.AppAction.success('Skema Komisi Berhasil Disimpan! ✨', data.message || 'Skema komisi sales bertingkat berhasil diperbarui.', 1500);
                } else if (window.toast) {
                    window.toast.success(data.message || 'Skema komisi sales bertingkat berhasil diperbarui.');
                    await new Promise(r => setTimeout(r, 800));
                }

                window.location.reload();

            } catch (err) {
                if (window.AppAction) {
                    await window.AppAction.error('Gagal Menyimpan Skema!', err.message || 'Terjadi kesalahan sistem saat menyimpan skema komisi.', 2800);
                } else if (window.toast) {
                    window.toast.error(err.message || 'Terjadi kesalahan sistem saat menyimpan skema komisi.');
                }
            } finally {
                this.isSavingTiers = false;
            }
        },

        onPosisiChange() {
            if (this.form.posisi === 'pengemasan') {
                if (!this.isEdit) {
                    this.form.tipe_penggajian = 'borongan';
                    this.form.gaji_pokok_bulanan = '0';
                    this.form.uang_kehadiran_harian = '10.000';
                    this.form.tunjangan_bulanan = '50.000';
                }
            } else if (this.form.posisi === 'sales') {
                if (!this.isEdit) {
                    this.form.tipe_penggajian = 'bulanan';
                    this.form.gaji_pokok_bulanan = '0';
                    this.form.uang_kehadiran_harian = '0';
                    this.form.tunjangan_bulanan = '0';
                }
            } else if (this.form.posisi === 'driver') {
                if (!this.isEdit) {
                    this.form.tipe_penggajian = 'bulanan';
                    this.form.gaji_pokok_bulanan = '2.500.000';
                    this.form.uang_kehadiran_harian = '15.000';
                    this.form.tunjangan_bulanan = '100.000';
                }
            } else if (this.form.posisi === 'gudang') {
                if (!this.isEdit) {
                    this.form.tipe_penggajian = 'bulanan';
                    this.form.gaji_pokok_bulanan = '2.500.000';
                    this.form.uang_kehadiran_harian = '15.000';
                    this.form.tunjangan_bulanan = '100.000';
                }
            } else if (this.form.posisi === 'admin') {
                if (!this.isEdit) {
                    this.form.tipe_penggajian = 'bulanan';
                    this.form.gaji_pokok_bulanan = '2.500.000';
                    this.form.uang_kehadiran_harian = '15.000';
                    this.form.tunjangan_bulanan = '100.000';
                }
            } else if (this.form.posisi === 'mandor') {
                if (!this.isEdit) {
                    this.form.tipe_penggajian = 'bulanan';
                    this.form.gaji_pokok_bulanan = '3.000.000';
                    this.form.uang_kehadiran_harian = '20.000';
                    this.form.tunjangan_bulanan = '500.000';
                }
            }
            this.$nextTick(() => lucide.createIcons());
        },

        openAddModal() {
            this.isEdit = false;
            this.form = {
                id: '',
                nik: '',
                nama_karyawan: '',
                posisi: 'pengemasan',
                tipe_penggajian: 'borongan',
                gaji_pokok_bulanan: '0',
                uang_kehadiran_harian: '10.000',
                tunjangan_bulanan: '50.000',
                nomor_whatsapp: '',
                alamat: '',
                nomor_polisi_kendaraan: '',
                bank_nama: 'Tunai',
                bank_nomor_rekening: '',
                bank_atas_nama: '',
                status_aktif: true
            };
            this.showModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditModal(e) {
            this.isEdit = true;
            this.form = {
                id: e.id,
                nik: e.nik || '',
                nama_karyawan: e.nama_karyawan,
                posisi: e.posisi || 'pengemasan',
                tipe_penggajian: e.tipe_penggajian || 'borongan',
                gaji_pokok_bulanan: window.formatRupiahNumber ? window.formatRupiahNumber(e.gaji_pokok_bulanan) : String(e.gaji_pokok_bulanan || 0),
                uang_kehadiran_harian: window.formatRupiahNumber ? window.formatRupiahNumber(e.uang_kehadiran_harian) : String(e.uang_kehadiran_harian || 0),
                tunjangan_bulanan: window.formatRupiahNumber ? window.formatRupiahNumber(e.tunjangan_bulanan) : String(e.tunjangan_bulanan || 0),
                nomor_whatsapp: e.nomor_whatsapp || e.nomor_telepon || '',
                alamat: e.alamat === '-' ? '' : (e.alamat || ''),
                nomor_polisi_kendaraan: e.nomor_polisi_kendaraan || '',
                bank_nama: e.bank_nama || 'Tunai',
                bank_nomor_rekening: e.bank_nomor_rekening || '',
                bank_atas_nama: e.bank_atas_nama || '',
                status_aktif: Boolean(e.status_aktif)
            };
            this.showModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async deactivateEmployee(id, name) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Nonaktifkan Karyawan',
                message: `Apakah Anda yakin ingin menonaktifkan karyawan "${name}"? Seluruh data historis pesanan, pengiriman, dan penggajian tetap aman tersimpan.`,
                type: 'danger',
                confirmText: 'Ya, Nonaktifkan'
            }) : confirm(`Nonaktifkan karyawan "${name}"?`);

            if (confirmed) {
                document.getElementById('delete-employee-id').value = id;
                document.getElementById('delete-employee-form').submit();
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

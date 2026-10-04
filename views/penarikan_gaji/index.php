<?php
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CSRF;

ob_start();

// Date shortcuts for filters
$todayStr = date('Y-m-d');
$thisMonthStart = date('Y-m-01');
$thisMonthEnd = date('Y-m-t');
$lastMonthStart = date('Y-m-01', strtotime('-1 month'));
$lastMonthEnd = date('Y-m-t', strtotime('-1 month'));
$last7Days = date('Y-m-d', strtotime('-7 days'));

// Helper Avatar Initials
function getInitials(string $name): string {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $initials .= mb_substr($p, 0, 1);
    }
    return strtoupper($initials ?: 'KR');
}

// Prepare Karyawan Dictionary for Alpine.js
$karyawanMapData = [];
foreach ($karyawanBulanan as $kb) {
    $kid = (string)$kb['id'];
    $karyawanMapData[$kid] = [
        'id' => $kid,
        'nama' => $kb['nama_karyawan'],
        'posisi' => $kb['posisi'] ?? 'Staff',
        'uang_kehadiran' => (float)($kb['uang_kehadiran_harian'] ?? 0),
        'gaji_pokok' => (float)($kb['gaji_pokok_bulanan'] ?? 0),
        'initials' => getInitials($kb['nama_karyawan'])
    ];
}
?>

<style>
/* =========================================================================
   MODUL PENARIKAN GAJI HARIAN — CANONICAL KEREN ONE ERP DESIGN SYSTEM
   ========================================================================= */

/* Mobile & Touch Ergonomics */
* {
    -webkit-tap-highlight-color: transparent;
}
button, select {
    touch-action: manipulation;
}
input[type="text"], input[type="number"], input[type="search"], input[type="date"] {
    touch-action: manipulation;
}

/* Date Dock Toolbar Card */
.pg-dock-card {
    background-color: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: var(--rounded-lg, 14px);
    padding: 12px 16px;
    box-shadow: var(--shadow-1, 0 1px 3px rgba(0, 0, 0, 0.03));
}
.dark .pg-dock-card {
    background-color: #1e293b;
    border-color: #334155;
}

/* Stat Cards */
.pg-stat-card {
    background-color: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: var(--rounded-lg, 14px);
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: var(--shadow-1, 0 1px 3px rgba(0, 0, 0, 0.03));
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.dark .pg-stat-card {
    background-color: #1e293b;
    border-color: #334155;
}
.pg-stat-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border-color: var(--color-hairline-strong, #cbd5e1);
}
.dark .pg-stat-card:hover {
    border-color: #475569;
}
.pg-stat-icon {
    width: 44px;
    height: 44px;
    min-width: 44px;
    min-height: 44px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-sizing: border-box;
}
.pg-stat-icon svg {
    width: 22px;
    height: 22px;
    display: block;
}

/* Table Avatar Badge */
.pg-table-avatar {
    width: 32px;
    height: 32px;
    min-width: 32px;
    min-height: 32px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
    font-family: var(--font-mono, monospace);
    background-color: rgba(136, 19, 55, 0.1);
    color: #881337;
    border: 1px solid rgba(136, 19, 55, 0.22);
    flex-shrink: 0;
    box-sizing: border-box;
}
.dark .pg-table-avatar {
    background-color: rgba(251, 113, 133, 0.14);
    color: #fb7185;
    border-color: rgba(251, 113, 133, 0.3);
}

/* Segmented Control Filter Tabs */
.pg-filter-tab-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 700;
    border-radius: 8px;
    color: #64748b;
    background: transparent;
    transition: all 0.15s ease;
    border: none;
    cursor: pointer;
    user-select: none;
    -webkit-user-select: none;
    text-decoration: none;
}
.pg-filter-tab-btn:hover {
    color: #0f172a;
    background: rgba(0, 0, 0, 0.04);
}
.dark .pg-filter-tab-btn:hover {
    color: #f8fafc;
    background: rgba(255, 255, 255, 0.06);
}
.pg-filter-tab-btn.is-active {
    background: #881337 !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(136, 19, 55, 0.25);
}

/* Filter Dock Top Row & Period Group */
.pg-filter-top-row {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding-bottom: 14px;
    margin-bottom: 14px;
    border-bottom: 1px solid var(--color-hairline, #e2e8f0);
}
@media (min-width: 640px) {
    .pg-filter-top-row {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}
.pg-period-group {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* Quick Period Pill Button */
.period-pill-btn {
    font-size: 11px;
    font-weight: 700;
    height: 30px;
    padding: 0 12px;
    border-radius: 8px;
    border: 1px solid var(--color-hairline, #e2e8f0);
    background: var(--color-canvas, #ffffff);
    color: var(--color-ink-secondary, #64748b);
    transition: all 0.15s ease;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    line-height: 1;
    box-sizing: border-box;
}
.period-pill-btn:hover {
    background: var(--color-canvas-soft, #f8fafc);
    color: var(--color-ink, #0f172a);
    border-color: var(--color-hairline-strong, #cbd5e1);
}
.period-pill-btn.is-active {
    background: rgba(136, 19, 55, 0.08) !important;
    border-color: #881337 !important;
    color: #881337 !important;
    font-weight: 800 !important;
    box-shadow: 0 1px 3px rgba(136, 19, 55, 0.12);
}
.dark .period-pill-btn {
    background: #1e293b;
    border-color: #334155;
    color: #94a3b8;
}
.dark .period-pill-btn:hover {
    background: #334155;
    color: #f8fafc;
}
.dark .period-pill-btn.is-active {
    background: rgba(251, 113, 133, 0.15) !important;
    border-color: #fb7185 !important;
    color: #fb7185 !important;
}

/* Search Bar Wrapper */
.pg-search-wrap {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}
.pg-search-icon {
    position: absolute;
    left: 10px;
    width: 14px;
    height: 14px;
    color: #94a3b8;
    pointer-events: none;
}
.pg-search-clear {
    position: absolute;
    right: 8px;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 99px;
    color: #94a3b8;
    background: #f1f5f9;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
}
.pg-search-clear:hover {
    color: #0f172a;
    background: #e2e8f0;
}
.dark .pg-search-clear {
    background: #334155;
    color: #94a3b8;
}
.dark .pg-search-clear:hover {
    color: #f8fafc;
    background: #475569;
}
.pg-search-input {
    padding-left: 32px !important;
    padding-right: 32px !important;
    padding-top: 7px !important;
    padding-bottom: 7px !important;
    font-size: 12.5px !important;
    border-radius: 8px !important;
    border: 1px solid var(--color-hairline-strong, #cbd5e1) !important;
    background: var(--color-canvas, #ffffff) !important;
    color: var(--color-ink, #0f172a) !important;
    width: 100% !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.dark .pg-search-input {
    background: #1e293b !important;
    border-color: #475569 !important;
    color: #f8fafc !important;
}
.pg-search-input:focus {
    border-color: #881337 !important;
    box-shadow: 0 0 0 1px #881337 !important;
    outline: none !important;
}

/* Attached Unit Input Group */
.pg-currency-group {
    display: flex;
    align-items: stretch;
    border: 1px solid var(--color-hairline-strong, #cbd5e1);
    border-radius: var(--rounded-md, 8px);
    background: var(--color-canvas, #ffffff);
    overflow: hidden;
    height: 38px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.dark .pg-currency-group {
    background: #1e293b;
    border-color: #475569;
}
.pg-currency-group:focus-within {
    border-color: #881337 !important;
    box-shadow: 0 0 0 1px #881337 !important;
}
.dark .pg-currency-group:focus-within {
    border-color: #fb7185 !important;
    box-shadow: 0 0 0 1px #fb7185 !important;
}
.pg-currency-addon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 10px;
    background: var(--color-canvas-soft, #f8fafc);
    border-right: 1px solid var(--color-hairline, #e2e8f0);
    color: var(--color-ink-mute, #64748b);
    font-size: 11.5px;
    font-weight: 800;
    font-family: var(--font-mono, monospace);
    user-select: none;
}
.dark .pg-currency-addon {
    background: #334155;
    border-color: #475569;
    color: #94a3b8;
}
.pg-currency-input {
    flex: 1;
    min-width: 0;
    height: 100%;
    padding: 0 10px;
    font-size: 13px;
    font-weight: 700;
    font-family: var(--font-mono, monospace);
    color: var(--color-ink, #0f172a);
    background: transparent;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    text-align: right;
}
.dark .pg-currency-input {
    color: #f8fafc;
}

/* Quick Nominal / Note Preset Chips */
.quick-chip-btn {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 700;
    border-radius: 6px;
    border: 1px solid var(--color-hairline, #e2e8f0);
    background: var(--color-canvas-soft, #f8fafc);
    color: var(--color-ink-secondary, #475569);
    cursor: pointer;
    transition: all 0.15s ease;
    user-select: none;
}
.quick-chip-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
    border-color: #cbd5e1;
}
.dark .quick-chip-btn {
    background: #334155;
    border-color: #475569;
    color: #cbd5e1;
}
.dark .quick-chip-btn:hover {
    background: #475569;
    color: #ffffff;
}

/* Searchable Dropdown Styles */
.dropdown-menu-searchable {
    animation: pgDropdownFadeIn 0.15s ease-out;
}
@keyframes pgDropdownFadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
.searchable-option {
    transition: background-color 0.12s ease;
    user-select: none;
}
.searchable-option:hover,
.searchable-option.is-active {
    background-color: var(--color-canvas-soft, #f8fafc);
}
.dark .searchable-option:hover,
.dark .searchable-option.is-active {
    background-color: #334155;
}
.searchable-option.is-selected {
    background-color: rgba(136, 19, 55, 0.08) !important;
}
.dark .searchable-option.is-selected {
    background-color: rgba(251, 113, 133, 0.14) !important;
}

/* Nominal Badge in Dropdown */
.pg-nominal-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2.5px 8px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    background: rgba(136, 19, 55, 0.08);
    color: #881337;
    border: 1px solid rgba(136, 19, 55, 0.18);
    white-space: nowrap;
    line-height: 1.3;
}
.dark .pg-nominal-badge {
    background: rgba(251, 113, 133, 0.14);
    color: #fb7185;
    border-color: rgba(251, 113, 133, 0.28);
}
.pg-nominal-badge-empty {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2.5px 8px;
    border-radius: 9999px;
    font-size: 10.5px;
    font-weight: 600;
    color: #94a3b8;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    white-space: nowrap;
    line-height: 1.3;
}
.dark .pg-nominal-badge-empty {
    background: #1e293b;
    color: #64748b;
    border-color: #334155;
}

/* Primary Button in Maroon */
.btn-primary-maroon {
    background-color: #881337 !important;
    color: #ffffff !important;
    border: 1px solid #700f2d !important;
    box-shadow: 0 1px 2px rgba(136, 19, 55, 0.2);
    transition: all 0.15s ease;
}
.btn-primary-maroon:hover {
    background-color: #9f1239 !important;
    box-shadow: 0 4px 10px rgba(136, 19, 55, 0.3);
}

/* =========================================================================
   MOBILE RESPONSIVE CARD VIEW (SCREEN WIDTH < 768px)
   ========================================================================= */
@media (max-width: 767.98px) {
    .responsive-pg-table thead {
        display: none !important;
    }
    .responsive-pg-table,
    .responsive-pg-table tbody {
        display: block !important;
        width: 100% !important;
    }
    .responsive-pg-table tr.pg-data-row {
        display: flex !important;
        flex-direction: column !important;
        background: var(--color-canvas, #ffffff) !important;
        border: 1px solid var(--color-hairline, #e2e8f0) !important;
        border-radius: 14px !important;
        margin: 10px !important;
        padding: 14px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
        gap: 10px !important;
    }
    .dark .responsive-pg-table tr.pg-data-row {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    .responsive-pg-table td {
        display: block !important;
        padding: 0 !important;
        border: none !important;
    }
    .responsive-pg-table td.col-no {
        display: none !important;
    }
}
</style>

<div x-data="penarikanGajiApp()" class="space-y-4 pb-16 md:pb-6">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER (Pola Kanonikal KEREN ONE ERP)                              -->
    <!-- ========================================================================= -->
    <div class="page-header flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-rose" style="flex-shrink:0;">
                <i data-lucide="hand-coins"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#881337;"></span>
                    <span>Modul HR &bull; Ambil Uang Harian Bulanan</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100">
                    <?= htmlspecialchars($pageTitle) ?>
                </h1>
                <p class="page-subtitle text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Pencatatan penarikan uang kehadiran harian &amp; kasbon harian karyawan bulanan untuk integrasi payroll
                </p>
            </div>
        </div>
        <div class="page-header-actions flex items-center gap-2 w-full sm:w-auto justify-end">
            <button type="button" 
                    @click="openModalTambah()" 
                    class="btn btn-primary-maroon text-xs sm:text-sm font-bold flex items-center gap-1.5 w-full sm:w-auto justify-center"
                    style="height:38px; border-radius:10px;">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Catat Penarikan Baru</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. KPI SUMMARY METRICS (Standardized ERP Stat Strip)                       -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Card 1: Total Penarikan -->
        <div class="pg-stat-card">
            <div class="pg-stat-icon" style="background:rgba(136, 19, 55, 0.1); color:#881337; border:1px solid rgba(136, 19, 55, 0.2);">
                <i data-lucide="badge-dollar-sign"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10.5px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Total Penarikan Periode</div>
                <div class="text-base sm:text-lg font-bold font-mono text-slate-900 dark:text-slate-100 mt-0.5">
                    <?= Format::rupiah($totalNominal) ?>
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5">
                    <?= $countTotal ?> catatan (<?= $countEmployees ?> karyawan)
                </div>
            </div>
        </div>

        <!-- Card 2: Total Pending -->
        <div class="pg-stat-card">
            <div class="pg-stat-icon" style="background:rgba(245, 158, 11, 0.1); color:#f59e0b; border:1px solid rgba(245, 158, 11, 0.25);">
                <i data-lucide="clock-alert"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10.5px] sm:text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider truncate">Pending (Belum Di-Payroll)</div>
                <div class="text-base sm:text-lg font-bold font-mono text-amber-700 dark:text-amber-300 mt-0.5">
                    <?= Format::rupiah($totalPending) ?>
                </div>
                <div class="text-[11px] text-amber-600/80 dark:text-amber-400/80 mt-0.5">
                    <?= $countPending ?> transaksi belum dipotong
                </div>
            </div>
        </div>

        <!-- Card 3: Total Terkunci Payroll -->
        <div class="pg-stat-card">
            <div class="pg-stat-icon" style="background:rgba(16, 185, 129, 0.1); color:#10b981; border:1px solid rgba(16, 185, 129, 0.25);">
                <i data-lucide="shield-check"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10.5px] sm:text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider truncate">Terkunci dalam Payroll</div>
                <div class="text-base sm:text-lg font-bold font-mono text-emerald-700 dark:text-emerald-300 mt-0.5">
                    <?= Format::rupiah($totalLocked) ?>
                </div>
                <div class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5">
                    <?= $countLocked ?> transaksi resmi terpotong
                </div>
            </div>
        </div>

        <!-- Card 4: Karyawan Terdaftar -->
        <div class="pg-stat-card">
            <div class="pg-stat-icon" style="background:rgba(2, 132, 199, 0.1); color:#0284c7; border:1px solid rgba(2, 132, 199, 0.25);">
                <i data-lucide="users"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10.5px] sm:text-[11px] font-bold text-sky-600 dark:text-sky-400 uppercase tracking-wider truncate">Master Karyawan Bulanan</div>
                <div class="text-base sm:text-lg font-bold font-mono text-slate-900 dark:text-slate-100 mt-0.5">
                    <?= count($karyawanBulanan) ?> <span class="text-xs font-normal text-slate-400">orang aktif</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5">
                    Memiliki tunjangan kehadiran harian
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. EXECUTIVE FILTER DOCK & SEARCH TOOLBAR                                  -->
    <!-- ========================================================================= -->
    <div class="pg-dock-card">
        <!-- Top row: Quick Period Shortcuts -->
        <div class="pg-filter-top-row">
            <div class="pg-period-group">
                <span style="font-size:11px;font-weight:700;color:var(--color-ink-mute,#94a3b8);text-transform:uppercase;letter-spacing:0.04em;margin-right:2px;">Periode Cepat:</span>
                <a href="<?= Router::url('/penarikan-gaji?tanggal_awal=' . $thisMonthStart . '&tanggal_akhir=' . $thisMonthEnd . '&status=' . urlencode($status) . '&karyawan_id=' . urlencode($karyawanId)) ?>" 
                   class="period-pill-btn <?= ($tglAwal === $thisMonthStart && $tglAkhir === $thisMonthEnd) ? 'is-active' : '' ?>">
                    <i data-lucide="calendar" style="width:14px;height:14px;"></i>
                    <span>Bulan Ini</span>
                </a>
                <a href="<?= Router::url('/penarikan-gaji?tanggal_awal=' . $lastMonthStart . '&tanggal_akhir=' . $lastMonthEnd . '&status=' . urlencode($status) . '&karyawan_id=' . urlencode($karyawanId)) ?>" 
                   class="period-pill-btn <?= ($tglAwal === $lastMonthStart && $tglAkhir === $lastMonthEnd) ? 'is-active' : '' ?>">
                    <span>Bulan Lalu</span>
                </a>
                <a href="<?= Router::url('/penarikan-gaji?tanggal_awal=' . $last7Days . '&tanggal_akhir=' . $todayStr . '&status=' . urlencode($status) . '&karyawan_id=' . urlencode($karyawanId)) ?>" 
                   class="period-pill-btn <?= ($tglAwal === $last7Days && $tglAkhir === $todayStr) ? 'is-active' : '' ?>">
                    <span>7 Hari Terakhir</span>
                </a>
                <a href="<?= Router::url('/penarikan-gaji?tanggal_awal=' . $todayStr . '&tanggal_akhir=' . $todayStr . '&status=' . urlencode($status) . '&karyawan_id=' . urlencode($karyawanId)) ?>" 
                   class="period-pill-btn <?= ($tglAwal === $todayStr && $tglAkhir === $todayStr) ? 'is-active' : '' ?>">
                    <span>Hari Ini</span>
                </a>
            </div>

            <div style="font-size:11.5px;color:var(--color-ink-mute,#64748b);font-weight:500;">
                Rentang Aktif: <span style="font-weight:700;color:var(--color-primary,#881337);"><?= Format::tanggalIndo($tglAwal) ?></span> s/d <span style="font-weight:700;color:var(--color-primary,#881337);"><?= Format::tanggalIndo($tglAkhir) ?></span>
            </div>
        </div>

        <!-- Bottom row: Custom Filter Form -->
        <form method="GET" action="<?= Router::url('/penarikan-gaji') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 items-end">
            <div>
                <label class="form-label text-[11px] font-bold text-slate-600 dark:text-slate-300">Dari Tanggal</label>
                <input type="date" name="tanggal_awal" value="<?= htmlspecialchars($tglAwal) ?>" class="form-input form-input-sm text-xs rounded-lg w-full">
            </div>
            <div>
                <label class="form-label text-[11px] font-bold text-slate-600 dark:text-slate-300">Sampai Tanggal</label>
                <input type="date" name="tanggal_akhir" value="<?= htmlspecialchars($tglAkhir) ?>" class="form-input form-input-sm text-xs rounded-lg w-full">
            </div>
            <div>
                <label class="form-label text-[11px] font-bold text-slate-600 dark:text-slate-300">Karyawan Bulanan</label>
                <select name="karyawan_id" class="form-select form-select-sm text-xs rounded-lg w-full">
                    <option value="">-- Semua Karyawan --</option>
                    <?php foreach ($karyawanBulanan as $kb): ?>
                        <option value="<?= $kb['id'] ?>" <?= $karyawanId === $kb['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kb['nama_karyawan']) ?> (<?= htmlspecialchars($kb['posisi'] ?? 'Staff') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label text-[11px] font-bold text-slate-600 dark:text-slate-300">Status Payroll</label>
                <select name="status" class="form-select form-select-sm text-xs rounded-lg w-full">
                    <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>⏳ Pending (Belum Terpotong)</option>
                    <option value="locked" <?= $status === 'locked' ? 'selected' : '' ?>>🔒 Terkunci Payroll</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-secondary w-full text-xs font-bold flex items-center justify-center gap-1.5 h-[34px] rounded-lg">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Terapkan</span>
                </button>
                <a href="<?= Router::url('/penarikan-gaji') ?>" class="btn btn-ghost text-xs px-2.5 h-[34px] rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. MAIN DATA CARD WITH LIVE SEARCH & RESPONSIVE DATA TABLE                -->
    <!-- ========================================================================= -->
    <div class="card overflow-hidden border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-2xl shadow-xs">
        
        <!-- Table Toolbar Header -->
        <div class="p-3.5 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/70 dark:bg-slate-900/70">
            <!-- Left: Filter Pills & Counts -->
            <div class="flex items-center gap-2 flex-wrap">
                <div class="inline-flex bg-slate-100 dark:bg-slate-800 p-0.5 rounded-lg border border-slate-200/80 dark:border-slate-700/80 gap-0.5">
                    <button type="button" 
                            @click="tableFilterStatus = 'all'" 
                            :class="tableFilterStatus === 'all' ? 'is-active' : ''"
                            class="pg-filter-tab-btn">
                        <span>Semua</span>
                        <span class="text-[10px] py-0.2 px-1.5 rounded-full font-mono font-bold"
                              :class="tableFilterStatus === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'">
                            <?= count($penarikanList) ?>
                        </span>
                    </button>
                    <button type="button" 
                            @click="tableFilterStatus = 'pending'" 
                            :class="tableFilterStatus === 'pending' ? 'is-active' : ''"
                            class="pg-filter-tab-btn">
                        <span>⏳ Pending</span>
                        <span class="text-[10px] py-0.2 px-1.5 rounded-full font-mono font-bold"
                              :class="tableFilterStatus === 'pending' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300'">
                            <?= $countPending ?>
                        </span>
                    </button>
                    <button type="button" 
                            @click="tableFilterStatus = 'locked'" 
                            :class="tableFilterStatus === 'locked' ? 'is-active' : ''"
                            class="pg-filter-tab-btn">
                        <span>🔒 Terkunci</span>
                        <span class="text-[10px] py-0.2 px-1.5 rounded-full font-mono font-bold"
                              :class="tableFilterStatus === 'locked' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'">
                            <?= $countLocked ?>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Right: Real-Time Live Search -->
            <div class="w-full sm:w-72">
                <div class="pg-search-wrap">
                    <i data-lucide="search" class="pg-search-icon"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           @keydown.enter.prevent
                           placeholder="Cari nama karyawan / ket / nomor..." 
                           class="pg-search-input">
                    <button type="button" 
                            @click="searchQuery = ''" 
                            x-show="searchQuery.trim().length > 0" 
                            class="pg-search-clear" 
                            title="Reset Pencarian"
                            style="display: none;">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Body -->
        <div class="table-wrapper overflow-x-auto">
            <table class="responsive-pg-table data-table w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/60 dark:bg-slate-800/40 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4 w-32">Tanggal</th>
                        <th class="py-3 px-4 min-w-[200px]">Karyawan</th>
                        <th class="py-3 px-4 text-right min-w-[140px]">Nominal Ambil</th>
                        <th class="py-3 px-4 min-w-[220px]">Keterangan</th>
                        <th class="py-3 px-4 text-center w-36">Status Payroll</th>
                        <th class="py-3 px-4 text-center w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($penarikanList)): ?>
                        <tr class="border-0">
                            <td colspan="7" class="py-12 px-4 text-center border-0">
                                <div style="width:48px;height:48px;border-radius:14px;background:rgba(136,19,55,0.08);color:#881337;display:flex;align-items:center;justify-content:center;margin:0 auto 12px auto;border:1px solid rgba(136,19,55,0.18);">
                                    <i data-lucide="hand-coins" style="width:24px;height:24px;"></i>
                                </div>
                                <div class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Catatan Penarikan Gaji</div>
                                <div class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Tidak ada riwayat penarikan uang harian untuk rentang tanggal atau kriteria filter yang dipilih.</div>
                                <button type="button" @click="openModalTambah()" class="btn btn-primary-maroon btn-sm text-xs font-bold mt-4 inline-flex items-center gap-1.5" style="border-radius:8px;">
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    <span>Catat Penarikan Baru</span>
                                </button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($penarikanList as $idx => $pg): 
                            $isLocked = !empty($pg['penggajian_id']);
                            $initials = getInitials($pg['nama_karyawan']);
                            $searchKeywords = strtolower($pg['nama_karyawan'] . ' ' . ($pg['posisi'] ?? '') . ' ' . ($pg['keterangan'] ?? '') . ' ' . ($pg['nomor_payroll'] ?? '') . ' ' . $pg['tanggal']);
                        ?>
                        <tr class="pg-data-row hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors"
                            x-show="isRowVisible('<?= htmlspecialchars($searchKeywords, ENT_QUOTES, 'UTF-8') ?>', <?= $isLocked ? 'true' : 'false' ?>)"
                            data-id="<?= htmlspecialchars($pg['id']) ?>">
                            
                            <!-- Col: No -->
                            <td class="col-no py-3 px-4 text-center font-mono text-slate-400"><?= $idx + 1 ?></td>
                            
                            <!-- Col: Tanggal -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0 hidden sm:inline"></i>
                                    <span class="font-mono font-semibold text-slate-800 dark:text-slate-200"><?= Format::tanggalIndo($pg['tanggal']) ?></span>
                                </div>
                                <div class="text-[10.5px] text-slate-400 sm:hidden">
                                    Dicatat: <?= date('d/m/Y H:i', strtotime($pg['dibuat_pada'] ?? $pg['tanggal'])) ?>
                                </div>
                            </td>

                            <!-- Col: Karyawan -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="pg-table-avatar">
                                        <?= htmlspecialchars($initials) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 truncate"><?= htmlspecialchars($pg['nama_karyawan']) ?></div>
                                        <div class="text-[11px] text-slate-400 truncate"><?= htmlspecialchars($pg['posisi'] ?? 'Staff') ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Col: Nominal -->
                            <td class="py-3 px-4 text-left sm:text-right">
                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider sm:hidden">Nominal Penarikan</div>
                                <div class="text-sm font-bold font-mono text-rose-900 dark:text-rose-400">
                                    <?= Format::rupiah((float)$pg['nominal']) ?>
                                </div>
                            </td>

                            <!-- Col: Keterangan -->
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-300">
                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider sm:hidden">Alasan / Catatan</div>
                                <div class="leading-relaxed">
                                    <?= htmlspecialchars($pg['keterangan'] ?: 'Ambil Uang Harian') ?>
                                </div>
                            </td>

                            <!-- Col: Status Payroll -->
                            <td class="py-3 px-4 text-left sm:text-center">
                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider sm:hidden mb-1">Status Payroll</div>
                                <?php if ($isLocked): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        <i data-lucide="lock" class="w-3 h-3 text-slate-500"></i>
                                        <span><?= htmlspecialchars($pg['nomor_payroll'] ?: 'Terkunci Payroll') ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10.5px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/70 dark:border-amber-800/40">
                                        <i data-lucide="clock" class="w-3 h-3 text-amber-600"></i>
                                        <span>Pending (Belum Di-Payroll)</span>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Col: Aksi -->
                            <td class="py-3 px-4 text-left sm:text-center">
                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider sm:hidden mb-1">Tindakan</div>
                                <?php if ($isLocked): ?>
                                    <span class="text-[11px] font-semibold text-slate-400 italic">Terkunci</span>
                                <?php else: ?>
                                    <div class="flex items-center sm:justify-center gap-1">
                                        <!-- Edit Button -->
                                        <button type="button" 
                                                @click="openModalEdit({
                                                    id: '<?= htmlspecialchars($pg['id']) ?>',
                                                    karyawan_id: '<?= htmlspecialchars($pg['karyawan_id']) ?>',
                                                    akun_kas_id: '<?= htmlspecialchars($pg['akun_kas_id'] ?? '') ?>',
                                                    tanggal: '<?= htmlspecialchars($pg['tanggal']) ?>',
                                                    nominal: <?= (float)$pg['nominal'] ?>,
                                                    keterangan: '<?= htmlspecialchars($pg['keterangan'] ?? '', ENT_QUOTES, 'UTF-8') ?>'
                                                })"
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-rose-800 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" 
                                                title="Edit Catatan Penarikan">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>

                                        <!-- Delete Form -->
                                        <form method="POST" action="<?= Router::url('/penarikan-gaji/delete') ?>" 
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan penarikan gaji sebesar <?= Format::rupiah((float)$pg['nominal']) ?> untuk <?= htmlspecialchars($pg['nama_karyawan']) ?>?');"
                                              class="inline-block m-0">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($pg['id']) ?>">
                                            <button type="submit" 
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" 
                                                    title="Hapus Catatan">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. MODAL FORM: CATAT / EDIT PENARIKAN GAJI (CANONICAL ERP POPUP DIALOG)   -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="modalOpen" 
             x-cloak 
             class="modal-backdrop" 
             @click="closeModal()" 
             @keydown.escape.window="closeModal()">
            
            <div class="modal-box modal-box-md" style="max-width:520px;" @click.stop>
                
                <!-- Mobile Bottom-Sheet Pull Handle Indicator -->
                <div class="modal-handle">
                    <div class="modal-handle-bar"></div>
                </div>

                <!-- Modal Header (Canonical KEREN ONE Layout) -->
                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(136,19,55,0.1);color:#881337;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(136,19,55,0.22);">
                            <i data-lucide="hand-coins" style="width:18px;height:18px;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div class="modal-title" x-text="isEditMode ? 'Edit Catatan Penarikan Gaji' : 'Catat Penarikan Uang Harian'">
                                Catat Penarikan Uang Harian
                            </div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;" x-text="isEditMode ? 'Sesuaikan data penarikan uang harian yang belum di-payroll' : 'Khusus karyawan bulanan yang mengambil uang harian'">
                                Khusus karyawan bulanan yang mengambil uang harian
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="closeModal()" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form :action="isEditMode ? '<?= Router::url('/penarikan-gaji/update') ?>' : '<?= Router::url('/penarikan-gaji/store') ?>'" 
                      method="POST" 
                      style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="id" :value="formId" x-show="isEditMode">

                    <!-- Modal Body with Scrollable Area -->
                    <div class="modal-body custom-scrollbar" style="display:flex;flex-direction:column;gap:14px;">
                        
                        <!-- 1. Karyawan Selection (Searchable Dropdown) -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Pilih Karyawan Bulanan <span style="color:#e11d48;">*</span>
                            </label>

                            <div class="relative" @click.outside="karyawanDropdownOpen = false">
                                <button type="button"
                                        @click="karyawanDropdownOpen = !karyawanDropdownOpen"
                                        class="form-input flex items-center justify-between w-full text-left font-medium transition cursor-pointer"
                                        style="height:40px; border-radius:var(--rounded-md, 8px); background-color:var(--color-canvas, #ffffff); border:1px solid var(--color-hairline-strong, #cbd5e1); padding:0 12px;">
                                    <div class="flex items-center gap-2 min-w-0 pr-2">
                                        <div class="pg-table-avatar" style="width:22px; height:22px; font-size:9.5px;" x-show="selectedKid && currentKaryawan">
                                            <span x-text="currentKaryawan ? currentKaryawan.initials : 'KR'"></span>
                                        </div>
                                        <span class="truncate text-xs font-bold" 
                                              :style="!selectedKid ? 'color:var(--color-ink-mute); font-weight:500;' : 'color:var(--color-ink);'"
                                              x-text="selectedKid && currentKaryawan ? (currentKaryawan.nama + ' (' + currentKaryawan.posisi + ')') : '-- Pilih Karyawan Bulanan --'">
                                        </span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="karyawanDropdownOpen ? 'rotate-180' : ''"></i>
                                </button>

                                <input type="hidden" name="karyawan_id" :value="selectedKid" required>

                                <!-- Dropdown Menu -->
                                <div x-show="karyawanDropdownOpen" x-cloak
                                     class="dropdown-menu-searchable bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl"
                                     style="position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:1050; overflow:hidden;">
                                    
                                    <!-- Search inside dropdown -->
                                    <div style="padding:8px 10px; border-bottom:1px solid var(--color-hairline, #e2e8f0); background:var(--color-canvas-soft, #f8fafc);">
                                        <div style="position:relative; display:flex; align-items:center; width:100%;">
                                            <svg style="position:absolute; left:10px; width:14px; height:14px; color:#94a3b8; pointer-events:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                            </svg>
                                            <input type="text" 
                                                   x-model="karyawanSearch" 
                                                   placeholder="Ketik nama atau posisi..." 
                                                   class="form-input"
                                                   style="height:34px; padding-left:32px; padding-right:10px; font-size:12px; border-radius:8px; width:100%; border:1px solid var(--color-hairline-strong, #cbd5e1); background:var(--color-canvas, #ffffff); color:var(--color-ink, #0f172a);">
                                        </div>
                                    </div>

                                    <!-- List of options -->
                                    <div class="max-h-52 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60 custom-scrollbar">
                                        <template x-for="k in filteredKaryawans" :key="k.id">
                                            <div @click="selectKaryawan(k.id)"
                                                 class="searchable-option p-2.5 flex items-center justify-between gap-3 cursor-pointer transition-colors"
                                                 :class="String(k.id) === String(selectedKid) ? 'is-selected' : ''">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <div class="pg-table-avatar" style="width:28px; height:28px; font-size:10px;">
                                                        <span x-text="k.initials"></span>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate" x-text="k.nama"></div>
                                                        <div class="text-[11px] text-slate-400 capitalize truncate" x-text="k.posisi"></div>
                                                    </div>
                                                </div>
                                                <div class="text-right shrink-0">
                                                    <template x-if="k.uang_kehadiran > 0">
                                                        <span class="pg-nominal-badge">
                                                            <span class="font-bold" x-text="formatRupiah(k.uang_kehadiran)"></span>
                                                            <span style="font-size:9.5px;font-weight:600;opacity:0.75;">/hari</span>
                                                        </span>
                                                    </template>
                                                    <template x-if="k.uang_kehadiran <= 0">
                                                        <span class="pg-nominal-badge-empty">
                                                            Tanpa Uang Harian
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <div x-show="filteredKaryawans.length === 0" style="padding:14px; text-align:center; font-size:11.5px; color:var(--color-ink-mute, #94a3b8);">
                                            Tidak ada karyawan yang cocok dengan pencarian
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Karyawan Allowance Hint Banner (Only shown if selected and allowance > 0) -->
                            <div x-show="selectedKid && currentKaryawan && currentKaryawan.uang_kehadiran > 0" x-cloak 
                                 class="mt-2 p-2.5 rounded-lg flex items-center justify-between gap-2"
                                 style="background:rgba(136,19,55,0.06);border:1px solid rgba(136,19,55,0.18);">
                                <div class="flex items-center gap-1.5 text-xs text-rose-900 dark:text-rose-300">
                                    <i data-lucide="badge-percent" class="w-4 h-4 shrink-0 text-rose-800"></i>
                                    <span>Uang Harian Standar: <strong class="font-mono font-bold" x-text="formatRupiah(currentKaryawan ? currentKaryawan.uang_kehadiran : 0)"></strong></span>
                                </div>
                                <button type="button" 
                                        @click="setNominalDefault()" 
                                        class="btn btn-ghost btn-xs text-[10.5px] font-bold py-1 px-2 rounded-md border"
                                        style="background:var(--color-canvas, #ffffff);color:#881337;border-color:rgba(136,19,55,0.25);">
                                    Gunakan Standar
                                </button>
                            </div>
                        </div>

                        <!-- 2. Tanggal & Nominal Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label" style="display:block;margin-bottom:6px;">
                                    Tanggal Penarikan <span style="color:#e11d48;">*</span>
                                </label>
                                <input type="date" 
                                       name="tanggal" 
                                       x-model="formTanggal" 
                                       required 
                                       class="form-input text-xs font-medium w-full"
                                       style="height:38px; border-radius:var(--rounded-md, 8px);">
                            </div>

                            <div>
                                <label class="form-label" style="display:block;margin-bottom:6px;">
                                    Nominal Penarikan <span style="color:#e11d48;">*</span>
                                </label>
                                <div class="pg-currency-group">
                                    <span class="pg-currency-addon">Rp</span>
                                    <input type="number" 
                                           name="nominal" 
                                           x-model="nominalInput" 
                                           min="1000" 
                                           step="1000" 
                                           required 
                                           placeholder="0" 
                                           class="pg-currency-input">
                                </div>
                            </div>
                        </div>

                        <!-- Quick Nominal Preset Chips -->
                        <div>
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Preset Nominal Cepat:</div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <button type="button" @click="addNominal(10000)" class="quick-chip-btn">+10.000</button>
                                <button type="button" @click="addNominal(20000)" class="quick-chip-btn">+20.000</button>
                                <button type="button" @click="addNominal(50000)" class="quick-chip-btn">+50.000</button>
                                <button type="button" @click="addNominal(100000)" class="quick-chip-btn">+100.000</button>
                                <button type="button" @click="nominalInput = ''" class="quick-chip-btn text-rose-600 dark:text-rose-400">Reset</button>
                            </div>
                        </div>

                        <!-- 3. Pilihan Sumber Kas Pencairan Uang Harian -->
                        <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 space-y-2">
                            <label class="text-xs font-bold text-slate-800 dark:text-slate-100 flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="wallet" class="w-3.5 h-3.5 text-rose-600"></i>
                                    <span>Sumber Kas Pencairan</span>
                                    <span class="text-rose-500">*</span>
                                </span>
                                <span class="text-[11px] text-slate-400 font-normal">Pilih laci kasir / kas</span>
                            </label>

                            <input type="hidden" name="akun_kas_id" :value="selectedKasId" required>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-40 overflow-y-auto custom-scrollbar p-0.5">
                                <template x-for="acc in (cashAccounts || [])" :key="acc.id">
                                    <div @click="selectedKasId = acc.id"
                                         :class="{
                                             'border-rose-600 dark:border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/40 dark:bg-rose-950/20': selectedKasId === acc.id,
                                             'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 hover:border-slate-300 dark:hover:border-slate-600': selectedKasId !== acc.id,
                                             'opacity-60 cursor-not-allowed border-dashed': acc.saldo < (nominalInput || 0)
                                         }"
                                         class="relative flex items-center justify-between p-2 rounded-lg border transition-all cursor-pointer select-none">
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            <div class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0"
                                                 :class="acc.tipe_akun === 'kas_tunai' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50' : 'bg-blue-50 text-blue-600 dark:bg-blue-950/50'">
                                                <template x-if="acc.tipe_akun === 'kas_tunai'">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                                </template>
                                                <template x-if="acc.tipe_akun !== 'kas_tunai'">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                                </template>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-1">
                                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate" x-text="acc.nama_akun"></span>
                                                    <span x-show="acc.is_default_pos" class="px-1 py-0.2 text-[9px] font-bold rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300">POS</span>
                                                </div>
                                                <div class="text-[10.5px] font-mono text-slate-500 dark:text-slate-400 mt-0.5">
                                                    Saldo: <span :class="acc.saldo < (nominalInput || 0) ? 'text-rose-600 font-bold' : 'text-slate-700 dark:text-slate-200'" x-text="formatRupiah(acc.saldo)"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ml-1.5 flex-shrink-0">
                                            <div class="w-3.5 h-3.5 rounded-full border flex items-center justify-center"
                                                 :class="selectedKasId === acc.id ? 'border-rose-600 bg-rose-600 text-white' : 'border-slate-300 dark:border-slate-600'">
                                                <svg x-show="selectedKasId === acc.id" class="w-2 h-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div x-show="selectedAccount && selectedAccount.saldo < (nominalInput || 0)" class="text-[11px] text-rose-600 dark:text-rose-400 font-medium flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                <span>Saldo kas terpilih tidak mencukupi nominal penarikan ini.</span>
                            </div>
                        </div>

                        <!-- 4. Keterangan / Alasan -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Keterangan / Alasan
                            </label>
                            <input type="text" 
                                   name="keterangan" 
                                   x-model="formKeterangan"
                                   placeholder="Contoh: Ambil Uang Harian..." 
                                   class="form-input text-xs w-full"
                                   style="height:38px; border-radius:var(--rounded-md, 8px);">

                            <!-- Quick Reason Chips -->
                            <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                <span class="text-[10.5px] font-semibold text-slate-400">Pilihan Cepat:</span>
                                <button type="button" @click="formKeterangan = 'Ambil Uang Harian'" class="quick-chip-btn text-[10.5px]">Ambil Uang Harian</button>
                                <button type="button" @click="formKeterangan = 'Kasbon Harian'" class="quick-chip-btn text-[10.5px]">Kasbon Harian</button>
                                <button type="button" @click="formKeterangan = 'Transportasi Operasional'" class="quick-chip-btn text-[10.5px]">Transportasi</button>
                                <button type="button" @click="formKeterangan = 'Keperluan Pribadi Mendesak'" class="quick-chip-btn text-[10.5px]">Keperluan Mendesak</button>
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer (Desktop Cancel Button + Submit Button) -->
                    <div class="modal-footer">
                        <button type="button" @click="closeModal()" class="btn btn-secondary modal-btn-cancel-desktop">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary" style="background:#881337; border-color:#700f2d;">
                            <i data-lucide="save"></i>
                            <span x-text="isEditMode ? 'Simpan Perubahan' : 'Simpan Penarikan'">Simpan Penarikan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>

<!-- Alpine Application Script -->
<script>
function penarikanGajiApp() {
    return {
        // Master Data
        karyawans: <?= json_encode($karyawanMapData, JSON_UNESCAPED_UNICODE) ?>,
        cashAccounts: <?= json_encode(array_map(function($a) {
            return [
                'id' => (string)$a['id'],
                'nama_akun' => (string)$a['nama_akun'],
                'tipe_akun' => (string)$a['tipe_akun'],
                'saldo' => (float)$a['saldo_saat_ini'],
                'is_default_pos' => (bool)($a['is_default_pos'] ?? false),
            ];
        }, $activeCashAccounts ?? []), JSON_UNESCAPED_UNICODE) ?>,
        
        // Modal State
        modalOpen: false,
        isEditMode: false,
        formId: '',
        selectedKid: '',
        selectedKasId: '<?= !empty($activeCashAccounts[0]['id']) ? (string)$activeCashAccounts[0]['id'] : '' ?>',
        formTanggal: '<?= date('Y-m-d') ?>',
        nominalInput: '',
        formKeterangan: 'Ambil Uang Harian',
        karyawanDropdownOpen: false,
        karyawanSearch: '',

        // Table Filter & Search State
        searchQuery: '',
        tableFilterStatus: 'all', // 'all', 'pending', 'locked'

        get selectedAccount() {
            return this.cashAccounts.find(a => a.id === this.selectedKasId) || null;
        },

        get currentKaryawan() {
            return (this.selectedKid && this.karyawans[this.selectedKid]) ? this.karyawans[this.selectedKid] : null;
        },

        get filteredKaryawans() {
            const list = Object.values(this.karyawans);
            if (!this.karyawanSearch.trim()) return list;
            const q = this.karyawanSearch.toLowerCase();
            return list.filter(k => k.nama.toLowerCase().includes(q) || k.posisi.toLowerCase().includes(q));
        },

        openModalTambah() {
            this.isEditMode = false;
            this.formId = '';
            this.selectedKid = '';
            this.selectedKasId = this.cashAccounts[0] ? this.cashAccounts[0].id : '';
            this.formTanggal = '<?= date('Y-m-d') ?>';
            this.nominalInput = '';
            this.formKeterangan = 'Ambil Uang Harian';
            this.karyawanSearch = '';
            this.karyawanDropdownOpen = false;
            this.modalOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        openModalEdit(data) {
            this.isEditMode = true;
            this.formId = data.id || '';
            this.selectedKid = data.karyawan_id || '';
            this.selectedKasId = data.akun_kas_id || (this.cashAccounts[0] ? this.cashAccounts[0].id : '');
            this.formTanggal = data.tanggal || '<?= date('Y-m-d') ?>';
            this.nominalInput = data.nominal || '';
            this.formKeterangan = data.keterangan || 'Ambil Uang Harian';
            this.karyawanSearch = '';
            this.karyawanDropdownOpen = false;
            this.modalOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        closeModal() {
            this.modalOpen = false;
            this.karyawanDropdownOpen = false;
        },

        selectKaryawan(id) {
            this.selectedKid = id;
            this.karyawanDropdownOpen = false;
            this.karyawanSearch = '';
            this.setNominalDefault();
        },

        setNominalDefault() {
            if (this.currentKaryawan && this.currentKaryawan.uang_kehadiran > 0) {
                this.nominalInput = this.currentKaryawan.uang_kehadiran;
            }
        },

        addNominal(amount) {
            let current = parseFloat(this.nominalInput) || 0;
            this.nominalInput = current + amount;
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        isRowVisible(searchKeywords, isLocked) {
            // Status Tab Check
            if (this.tableFilterStatus === 'pending' && isLocked) return false;
            if (this.tableFilterStatus === 'locked' && !isLocked) return false;

            // Search Query Check
            if (!this.searchQuery.trim()) return true;
            const q = this.searchQuery.toLowerCase().trim();
            return searchKeywords.includes(q);
        }
    };
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

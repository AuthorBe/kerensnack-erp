<?php
/**
 * views/kasbon/index.php
 * Halaman Utama Modul Pinjaman / Kasbon Karyawan Keren One ERP
 * 100% Selaras dengan DNA Desain & Sistem Modal KEREN ONE
 */

use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CSRF;

ob_start();

function getKasbonInitials(string $name): string {
    $words = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach ($words as $w) {
        if (!empty($w)) {
            $initials .= mb_strtoupper(mb_substr($w, 0, 1));
        }
        if (mb_strlen($initials) >= 2) break;
    }
    return $initials ?: 'KR';
}
?>

<style>
/* ==========================================================================
   Kasbon Module DNA Styling & Responsive Utilities
   ========================================================================== */

/* 0. KPI Stat Cards */
.kasbon-stat-card {
    background-color: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: var(--rounded-lg, 14px);
    padding: 13px 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--shadow-1, 0 1px 3px rgba(0, 0, 0, 0.03));
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.kasbon-stat-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border-color: var(--color-hairline-strong, #cbd5e1);
}
.dark .kasbon-stat-card {
    background-color: #1e293b;
    border-color: #334155;
}
.dark .kasbon-stat-card:hover {
    border-color: #475569;
}
.kasbon-stat-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    min-height: 42px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-sizing: border-box;
}
.kasbon-stat-icon svg {
    width: 20px;
    height: 20px;
    display: block;
}

/* 1. Filter Dock Container */
.kasbon-filter-dock {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: var(--rounded-lg, 12px);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    margin-bottom: 16px;
    padding: 12px 14px;
}
.dark .kasbon-filter-dock {
    background: #1e293b;
    border-color: #334155;
}

.kasbon-filter-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

/* 2. Filter Status Tab Pills */
.tab-pill-group {
    display: inline-flex;
    align-items: center;
    background: var(--color-canvas-soft, #f8fafc);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 9px;
    padding: 3px;
    gap: 2px;
    overflow-x: auto;
    max-width: 100%;
}
.dark .tab-pill-group {
    background: #0f172a;
    border-color: #334155;
}

.tab-pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 11px;
    border-radius: 7px;
    font-size: 11.5px;
    font-weight: 700;
    color: var(--color-ink-secondary, #64748b);
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    user-select: none;
}
.tab-pill-btn:hover {
    color: var(--color-ink, #0f172a);
    background: rgba(0, 0, 0, 0.04);
}
.dark .tab-pill-btn:hover {
    color: #f8fafc;
    background: rgba(255, 255, 255, 0.06);
}
.tab-pill-btn.is-active {
    background: #4f46e5 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(79, 70, 229, 0.28);
}
.dark .tab-pill-btn.is-active {
    background: #6366f1 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(99, 102, 241, 0.35);
}

.tab-pill-counter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 17px;
    height: 17px;
    padding: 0 4.5px;
    border-radius: 9999px;
    font-size: 10px;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    background: rgba(0, 0, 0, 0.08);
}
.dark .tab-pill-counter {
    background: rgba(255, 255, 255, 0.14);
}
.tab-pill-btn.is-active .tab-pill-counter {
    background: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
}

/* 3. Search Box in Filter */
.kasbon-search-box {
    position: relative;
    display: flex;
    align-items: center;
    min-width: 220px;
    flex: 1;
    max-width: 320px;
}
.kasbon-search-input {
    width: 100%;
    height: 36px;
    padding-left: 34px;
    padding-right: 28px;
    font-size: 12px;
    font-weight: 500;
    border-radius: var(--rounded-md, 8px);
    border: 1px solid var(--color-hairline-strong, #cbd5e1);
    background: var(--color-canvas, #ffffff);
    color: var(--color-ink, #0f172a);
    transition: all 0.15s ease;
}
.kasbon-search-input:focus {
    border-color: #4f46e5 !important;
    box-shadow: 0 0 0 1px #4f46e5 !important;
    outline: none !important;
}
.dark .kasbon-search-input {
    background: #1e293b;
    border-color: #475569;
    color: #f8fafc;
}
.dark .kasbon-search-input:focus {
    border-color: #818cf8 !important;
    box-shadow: 0 0 0 1px #818cf8 !important;
}

/* 4. Table Avatar Badge */
.kasbon-avatar {
    width: 32px;
    height: 32px;
    border-radius: 9999px;
    background: rgba(99, 102, 241, 0.1);
    color: #4f46e5;
    border: 1px solid rgba(99, 102, 241, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 11px;
    flex-shrink: 0;
    user-select: none;
}
.dark .kasbon-avatar {
    background: rgba(129, 140, 248, 0.16);
    color: #818cf8;
    border-color: rgba(129, 140, 248, 0.35);
}

/* Table Card Container Rounded */
.kasbon-table-card {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
.dark .kasbon-table-card {
    background: #1e293b;
    border-color: #334155;
}
.kasbon-table-card .table-wrapper {
    overflow-x: auto;
    border-radius: 16px;
}
.kasbon-table-card table thead tr th:first-child {
    border-top-left-radius: 15px;
}
.kasbon-table-card table thead tr th:last-child {
    border-top-right-radius: 15px;
}

/* 5. Currency Group Input for Modals */
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

/* Quick Chips */
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

/* Searchable Dropdown */
.dropdown-menu-searchable {
    animation: kasbonDropdownFadeIn 0.15s ease-out;
}
@keyframes kasbonDropdownFadeIn {
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

/* Canonical Page Header Icon Indigo */
.page-header-icon.is-indigo {
    background: rgba(99, 102, 241, 0.1) !important;
    color: #4f46e5 !important;
    border: 1px solid rgba(99, 102, 241, 0.25) !important;
}
.dark .page-header-icon.is-indigo {
    background: rgba(99, 102, 241, 0.16) !important;
    color: #818cf8 !important;
    border-color: rgba(99, 102, 241, 0.35) !important;
}

/* Sleek Pill Badge for Dropdown Karyawan */
.kasbon-dropdown-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    background: rgba(245, 158, 11, 0.1);
    color: #b45309;
    border: 1px solid rgba(245, 158, 11, 0.28);
    white-space: nowrap;
    line-height: 1.3;
}
.dark .kasbon-dropdown-badge {
    background: rgba(245, 158, 11, 0.18);
    color: #fbbf24;
    border-color: rgba(245, 158, 11, 0.38);
}
.kasbon-dropdown-badge-empty {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    color: #059669;
    background: rgba(16, 185, 129, 0.08);
    border: 1px solid rgba(16, 185, 129, 0.22);
    white-space: nowrap;
    line-height: 1.3;
}
.dark .kasbon-dropdown-badge-empty {
    background: rgba(16, 185, 129, 0.16);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.32);
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
    background-color: #700f2d !important;
    color: #ffffff !important;
}

/* Desktop Rules (>= 768px) */
@media (min-width: 768px) {
    .kasbon-m-only,
    .kasbon-m-flex,
    .kasbon-m-date-subtitle {
        display: none !important;
    }
    .kasbon-d-only {
        display: block !important;
    }
}

/* Responsive Table for Mobile App Feel (< 768px) */
@media (max-width: 767px) {
    .responsive-kasbon-table,
    .responsive-kasbon-table tbody {
        display: block !important;
        width: 100% !important;
    }
    .responsive-kasbon-table thead {
        display: none !important;
    }
    .responsive-kasbon-table tbody tr.kasbon-data-row {
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
        background: var(--color-canvas, #ffffff) !important;
        border: 1px solid var(--color-hairline, #e2e8f0) !important;
        border-radius: 18px !important;
        padding: 16px !important;
        margin-bottom: 14px !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03) !important;
    }
    .dark .responsive-kasbon-table tbody tr.kasbon-data-row {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    .responsive-kasbon-table td {
        display: block !important;
        padding: 0 !important;
        border: none !important;
    }
    .responsive-kasbon-table td.col-no,
    .responsive-kasbon-table td.col-tanggal,
    .responsive-kasbon-table td.col-cicilan,
    .responsive-kasbon-table td.col-status {
        display: none !important;
    }
    .kasbon-d-only {
        display: none !important;
    }
    .kasbon-m-only {
        display: block !important;
    }
    .kasbon-m-flex {
        display: flex !important;
    }
    .kasbon-m-date-subtitle {
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        font-size: 11.5px !important;
        color: #64748b !important;
        font-family: var(--font-mono, ui-monospace, monospace) !important;
        margin-top: 6px !important;
    }
    .dark .kasbon-m-date-subtitle {
        color: #94a3b8 !important;
    }
    .kasbon-m-num-pill {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        font-family: var(--font-mono, ui-monospace, monospace) !important;
        color: #64748b !important;
        background: rgba(0, 0, 0, 0.04) !important;
        border: 1px solid rgba(0, 0, 0, 0.07) !important;
        padding: 3px 7px !important;
        border-radius: 7px !important;
        line-height: 1.2 !important;
    }
    .dark .kasbon-m-num-pill {
        color: #94a3b8 !important;
        background: rgba(255, 255, 255, 0.06) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    .kasbon-search-box {
        max-width: 100% !important;
        min-width: 100% !important;
    }
    .kasbon-m-card-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 8px !important;
        padding-bottom: 10px !important;
        border-bottom: 1px solid var(--color-hairline, #e2e8f0) !important;
    }
    .dark .kasbon-m-card-header {
        border-color: #334155 !important;
    }
    .kasbon-m-stat-box {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important;
        padding: 12px 14px !important;
        border-radius: 14px !important;
        background: var(--color-canvas-soft, #f8fafc) !important;
        border: 1px solid var(--color-hairline, #e2e8f0) !important;
    }
    .dark .kasbon-m-stat-box {
        background: rgba(15, 23, 42, 0.5) !important;
        border-color: #334155 !important;
    }
    .kasbon-m-progress-box {
        padding: 12px 14px !important;
        border-radius: 14px !important;
        background: var(--color-canvas-soft, #f8fafc) !important;
        border: 1px solid var(--color-hairline, #e2e8f0) !important;
    }
    .dark .kasbon-m-progress-box {
        background: rgba(15, 23, 42, 0.5) !important;
        border-color: #334155 !important;
    }
    .kasbon-m-keterangan {
        padding: 10px 13px !important;
        border-radius: 14px !important;
        background: var(--color-canvas-soft, #f8fafc) !important;
        border: 1px solid var(--color-hairline, #e2e8f0) !important;
        font-size: 11.5px !important;
    }
    .dark .kasbon-m-keterangan {
        background: rgba(15, 23, 42, 0.4) !important;
        border-color: #334155 !important;
    }
    .kasbon-m-action .btn {
        width: 100% !important;
        justify-content: center !important;
        height: 38px !important;
        border-radius: 12px !important;
        font-weight: 700 !important;
    }
}

/* Kasbon Progress Bar */
.kasbon-progress-track {
    width: 100%;
    height: 7px;
    border-radius: 9999px;
    background: #e2e8f0;
    overflow: hidden;
    position: relative;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
}
.dark .kasbon-progress-track {
    background: #334155;
}
.kasbon-progress-fill {
    height: 100%;
    border-radius: 9999px;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    min-width: 0;
}
.kasbon-progress-fill.is-lunas {
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

/* Status Badges */
.kasbon-badge-aktif {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 10.5px;
    font-weight: 700;
    color: #b45309;
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.35);
    white-space: nowrap;
    line-height: 1.3;
}
.dark .kasbon-badge-aktif {
    color: #fbbf24;
    background: rgba(245, 158, 11, 0.18);
    border-color: rgba(245, 158, 11, 0.45);
}

.kasbon-badge-lunas {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 10.5px;
    font-weight: 700;
    color: #059669;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.35);
    white-space: nowrap;
    line-height: 1.3;
}
.dark .kasbon-badge-lunas {
    color: #34d399;
    background: rgba(16, 185, 129, 0.18);
    border-color: rgba(16, 185, 129, 0.45);
}
</style>

<div x-data="kasbonApp()" class="space-y-4">

    <!-- 1. PAGE HEADER (Pola Kanonikal KEREN ONE ERP) -->
    <div class="page-header flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-indigo" style="flex-shrink:0;">
                <i data-lucide="hand-coins"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#6366f1;"></span>
                    <span>Modul HR &bull; Pinjaman Kasbon Karyawan</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100">
                    <?= htmlspecialchars($pageTitle) ?>
                </h1>
                <p class="page-subtitle text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    <?= htmlspecialchars($pageSubtitle) ?>
                </p>
            </div>
        </div>
        <div class="page-header-actions flex items-center gap-2 w-full sm:w-auto justify-end">
            <button type="button" 
                    @click="openModalTambah()" 
                    class="btn btn-primary-maroon text-xs sm:text-sm font-bold flex items-center gap-1.5 w-full sm:w-auto justify-center"
                    style="height:38px; border-radius:10px;">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Tambah Kasbon Baru</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI METRICS STRIP -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Card 1: Kasbon Aktif -->
        <div class="kasbon-stat-card">
            <div class="kasbon-stat-icon" style="background:rgba(99, 102, 241, 0.1); color:#4f46e5; border:1px solid rgba(99, 102, 241, 0.25);">
                <i data-lucide="hand-coins"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Kasbon Aktif</div>
                <div class="text-base sm:text-lg font-bold font-mono text-indigo-600 dark:text-indigo-400 mt-0.5 truncate">
                    <?= Format::rupiah($totalSisaAktif) ?>
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5 truncate">
                    <?= $countAktif ?> pinjaman berjalan
                </div>
            </div>
        </div>

        <!-- Card 2: Kasbon Lunas -->
        <div class="kasbon-stat-card">
            <div class="kasbon-stat-icon" style="background:rgba(16, 185, 129, 0.1); color:#10b981; border:1px solid rgba(16, 185, 129, 0.25);">
                <i data-lucide="badge-check"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Kasbon Lunas</div>
                <div class="text-base sm:text-lg font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-0.5 truncate">
                    <?= Format::rupiah($totalNominalLunas) ?>
                </div>
                <div class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 truncate">
                    <?= $countLunas ?> pinjaman selesai
                </div>
            </div>
        </div>

        <!-- Card 3: Total Terbayar -->
        <div class="kasbon-stat-card">
            <div class="kasbon-stat-icon" style="background:rgba(6, 182, 212, 0.1); color:#0891b2; border:1px solid rgba(6, 182, 212, 0.25);">
                <i data-lucide="receipt"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Total Terbayar</div>
                <div class="text-base sm:text-lg font-bold font-mono text-cyan-700 dark:text-cyan-300 mt-0.5 truncate">
                    <?= Format::rupiah($totalCicilanTerbayar ?? 0) ?>
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5 truncate">
                    Cicilan kembali ke kas
                </div>
            </div>
        </div>

        <!-- Card 4: Pinjaman Bulan Ini -->
        <div class="kasbon-stat-card">
            <div class="kasbon-stat-icon" style="background:rgba(245, 158, 11, 0.1); color:#f59e0b; border:1px solid rgba(245, 158, 11, 0.25);">
                <i data-lucide="calendar-plus"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Pinjaman Bulan Ini</div>
                <div class="text-base sm:text-lg font-bold font-mono text-slate-900 dark:text-slate-100 mt-0.5 truncate">
                    <?= Format::rupiah($pinjamanBulanIni) ?>
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5 truncate">
                    Bulan <?= date('m/Y') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. FILTER & SEARCH DOCK -->
    <div class="kasbon-filter-dock">
        <div class="kasbon-filter-row">
            <!-- Filter Tabs -->
            <div class="tab-pill-group">
                <button type="button" 
                        @click="statusFilter = 'all'" 
                        class="tab-pill-btn" 
                        :class="statusFilter === 'all' ? 'is-active' : ''">
                    <span>Semua Pinjaman</span>
                    <span class="tab-pill-counter"><?= count($kasbonList) ?></span>
                </button>
                <button type="button" 
                        @click="statusFilter = 'aktif'" 
                        class="tab-pill-btn" 
                        :class="statusFilter === 'aktif' ? 'is-active' : ''">
                    <span>Kasbon Aktif</span>
                    <span class="tab-pill-counter"><?= $countAktif ?></span>
                </button>
                <button type="button" 
                        @click="statusFilter = 'lunas'" 
                        class="tab-pill-btn" 
                        :class="statusFilter === 'lunas' ? 'is-active' : ''">
                    <span>Lunas</span>
                    <span class="tab-pill-counter"><?= $countLunas ?></span>
                </button>
                <button type="button" 
                        @click="statusFilter = 'borongan'" 
                        class="tab-pill-btn" 
                        :class="statusFilter === 'borongan' ? 'is-active' : ''">
                    <span>Borongan</span>
                </button>
                <button type="button" 
                        @click="statusFilter = 'bulanan'" 
                        class="tab-pill-btn" 
                        :class="statusFilter === 'bulanan' ? 'is-active' : ''">
                    <span>Bulanan</span>
                </button>
            </div>

            <!-- Instant Search Box -->
            <div class="kasbon-search-box">
                <svg style="position:absolute; left:11px; width:14px; height:14px; color:#94a3b8; pointer-events:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Cari peminjam / alasan..." 
                       class="kasbon-search-input">
                <button type="button" 
                        x-show="searchQuery.length > 0" 
                        @click="searchQuery = ''" 
                        style="position:absolute; right:8px; color:#94a3b8; padding:2px;" 
                        title="Hapus pencarian">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 4. DATA TABLE KASBON -->
    <div class="kasbon-table-card">
        <div class="table-wrapper overflow-x-auto">
            <table class="responsive-kasbon-table data-table w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/60 dark:bg-slate-800/40 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4 w-28">Tgl Pinjam</th>
                        <th class="py-3 px-4 min-w-[190px]">Nama Karyawan</th>
                        <th class="py-3 px-4 text-right min-w-[130px]">Total Pinjaman</th>
                        <th class="py-3 px-4 text-right min-w-[130px]">Cicilan/Periode</th>
                        <th class="py-3 px-4 min-w-[180px]">Sisa Pinjaman & Progress</th>
                        <th class="py-3 px-4 min-w-[180px]">Keterangan</th>
                        <th class="py-3 px-4 text-center w-28">Status</th>
                        <th class="py-3 px-4 text-center w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($kasbonList)): ?>
                        <tr class="border-0">
                            <td colspan="9" class="py-12 px-4 text-center border-0">
                                <div style="width:48px;height:48px;border-radius:14px;background:rgba(99,102,241,0.08);color:#4f46e5;display:flex;align-items:center;justify-content:center;margin:0 auto 12px auto;border:1px solid rgba(99,102,241,0.2);">
                                    <i data-lucide="hand-coins" style="width:24px;height:24px;"></i>
                                </div>
                                <div class="text-sm font-bold text-slate-800 dark:text-slate-200">Tidak Ada Catatan Kasbon</div>
                                <div class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Belum ada catatan pinjaman kasbon karyawan yang terdaftar.</div>
                                <button type="button" @click="openModalTambah()" class="btn btn-primary-maroon btn-sm text-xs font-bold mt-4 inline-flex items-center gap-1.5" style="border-radius:8px;">
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    <span>Tambah Kasbon Baru</span>
                                </button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($kasbonList as $idx => $kb): 
                            $totalPinjaman = (float)$kb['total_pinjaman'];
                            $sisaPinjaman = (float)$kb['sisa_pinjaman'];
                            $terbayar = (float)$kb['total_terbayar'];
                            $persenLunas = ($totalPinjaman > 0) ? min(100, round(($terbayar / $totalPinjaman) * 100, 1)) : 100;
                            $isAktif = ($kb['status_kasbon'] === 'aktif');
                            $initials = getKasbonInitials($kb['nama_karyawan']);
                            $searchKeywords = strtolower($kb['nama_karyawan'] . ' ' . ($kb['posisi'] ?? '') . ' ' . ($kb['keterangan'] ?? '') . ' ' . ($kb['tipe_penggajian'] ?? ''));
                        ?>
                        <tr class="kasbon-data-row hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors"
                            x-show="isRowVisible('<?= htmlspecialchars($searchKeywords, ENT_QUOTES, 'UTF-8') ?>', '<?= $kb['status_kasbon'] ?>', '<?= $kb['tipe_penggajian'] ?? '' ?>')"
                            data-id="<?= htmlspecialchars($kb['id']) ?>">
                            
                            <!-- Col 1: No -->
                            <td class="col-no py-3 px-4 text-center font-mono text-slate-400"><?= $idx + 1 ?></td>

                            <!-- Col 2: Tanggal Pengajuan -->
                            <td class="col-tanggal py-3 px-4">
                                <div class="flex items-center gap-2 font-mono font-semibold text-slate-800 dark:text-slate-200">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                    <span><?= Format::tanggalIndo($kb['tanggal_pengajuan']) ?></span>
                                </div>
                            </td>

                            <!-- Col 3: Nama Karyawan -->
                            <td class="col-karyawan py-3 px-4">
                                <!-- Mobile Header: Avatar + Name + Status Pill -->
                                <div class="kasbon-m-card-header kasbon-m-flex">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="kasbon-avatar">
                                            <?= htmlspecialchars($initials) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 dark:text-slate-100 truncate"><?= htmlspecialchars($kb['nama_karyawan']) ?></div>
                                            <div class="text-[11px] text-slate-400 capitalize truncate"><?= htmlspecialchars($kb['posisi'] ?? '-') ?> &bull; <?= ucfirst($kb['tipe_penggajian'] ?? '') ?></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <span class="kasbon-m-num-pill">#<?= $idx + 1 ?></span>
                                        <?php if ($kb['status_kasbon'] === 'aktif'): ?>
                                            <span class="kasbon-badge-aktif">
                                                <i data-lucide="clock" class="w-3 h-3 text-amber-600"></i>
                                                <span>Aktif</span>
                                            </span>
                                        <?php else: ?>
                                            <span class="kasbon-badge-lunas">
                                                <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i>
                                                <span>Lunas</span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <!-- Mobile Date Subtitle -->
                                <div class="kasbon-m-date-subtitle">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                    <span>Diajukan: <?= Format::tanggalIndo($kb['tanggal_pengajuan']) ?></span>
                                </div>
                                <!-- Desktop Karyawan Presentation -->
                                <div class="kasbon-d-only">
                                    <div class="flex items-center gap-2.5">
                                        <div class="kasbon-avatar">
                                            <?= htmlspecialchars($initials) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 dark:text-slate-100 truncate"><?= htmlspecialchars($kb['nama_karyawan']) ?></div>
                                            <div class="text-[11px] text-slate-400 capitalize truncate"><?= htmlspecialchars($kb['posisi'] ?? '-') ?> &bull; <?= ucfirst($kb['tipe_penggajian'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Col 4: Total Pinjaman -->
                            <td class="col-pinjaman py-3 px-4 text-left sm:text-right">
                                <!-- Mobile 2-Col Stat Box -->
                                <div class="kasbon-m-stat-box kasbon-m-only">
                                    <div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total Pinjaman</div>
                                        <div class="text-sm font-bold font-mono text-slate-900 dark:text-slate-100 mt-0.5">
                                            <?= Format::rupiah($totalPinjaman) ?>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Cicilan Payroll</div>
                                        <div class="text-xs font-bold font-mono text-slate-700 dark:text-slate-300 mt-0.5">
                                            <?= ($kb['potongan_per_periode'] > 0) ? Format::rupiah((float)$kb['potongan_per_periode']) : '<span class="text-slate-400 font-sans">Manual</span>' ?>
                                        </div>
                                    </div>
                                </div>
                                <!-- Desktop Only Presentation -->
                                <div class="kasbon-d-only text-sm font-bold font-mono text-slate-900 dark:text-slate-100">
                                    <?= Format::rupiah($totalPinjaman) ?>
                                </div>
                            </td>

                            <!-- Col 5: Cicilan / Periode -->
                            <td class="col-cicilan py-3 px-4 text-right font-mono">
                                <?php if ($kb['potongan_per_periode'] > 0): ?>
                                    <span class="font-bold text-slate-700 dark:text-slate-300"><?= Format::rupiah((float)$kb['potongan_per_periode']) ?></span>
                                    <span class="text-[10px] text-slate-400 block font-sans">/periode</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10.5px] font-medium text-slate-400 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">Manual</span>
                                <?php endif; ?>
                            </td>

                            <!-- Col 6: Sisa Pinjaman & Progress -->
                            <td class="col-progress py-3 px-4">
                                <!-- Mobile Rounded Progress Container -->
                                <div class="kasbon-m-progress-box kasbon-m-only">
                                    <div class="flex items-center justify-between text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 font-sans">
                                        <span>Sisa Pinjaman</span>
                                        <span>Progress</span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs font-mono mb-2">
                                        <span class="font-bold <?= $isAktif ? 'text-amber-700 dark:text-amber-400' : 'text-slate-400' ?>"><?= Format::rupiah($sisaPinjaman) ?></span>
                                        <span class="text-[11px] font-bold <?= $persenLunas >= 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-300' ?>"><?= $persenLunas ?>% Terlunasi</span>
                                    </div>
                                    <div class="kasbon-progress-track">
                                        <div class="kasbon-progress-fill <?= $persenLunas >= 100 ? 'is-lunas' : 'is-aktif' ?>" 
                                             style="width: <?= max(0, min(100, $persenLunas)) ?>%;"></div>
                                    </div>
                                </div>

                                <!-- Desktop Only Presentation -->
                                <div class="kasbon-d-only">
                                    <div class="flex items-center justify-between text-xs font-mono mb-1.5">
                                        <span class="font-bold <?= $isAktif ? 'text-amber-700 dark:text-amber-400' : 'text-slate-400' ?>"><?= Format::rupiah($sisaPinjaman) ?></span>
                                        <span class="text-[11px] font-bold <?= $persenLunas >= 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-300' ?>"><?= $persenLunas ?>% Terlunasi</span>
                                    </div>
                                    <div class="kasbon-progress-track">
                                        <div class="kasbon-progress-fill <?= $persenLunas >= 100 ? 'is-lunas' : 'is-aktif' ?>" 
                                             style="width: <?= max(0, min(100, $persenLunas)) ?>%;"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Col 7: Keterangan -->
                            <td class="col-keterangan py-3 px-4 text-slate-600 dark:text-slate-300">
                                <?php if (!empty($kb['keterangan'])): ?>
                                    <div class="kasbon-m-keterangan kasbon-m-only">
                                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Alasan:</div>
                                        <div class="leading-relaxed text-xs">
                                            <?= htmlspecialchars($kb['keterangan']) ?>
                                        </div>
                                    </div>
                                    <div class="kasbon-d-only leading-relaxed text-xs">
                                        <?= htmlspecialchars($kb['keterangan']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-400 hidden sm:inline">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Col 8: Status -->
                            <td class="col-status py-3 px-4 text-center">
                                <?php if ($kb['status_kasbon'] === 'aktif'): ?>
                                    <span class="kasbon-badge-aktif">
                                        <i data-lucide="clock" class="w-3 h-3 text-amber-600"></i>
                                        <span>Aktif</span>
                                    </span>
                                <?php else: ?>
                                    <span class="kasbon-badge-lunas">
                                        <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i>
                                        <span>Lunas</span>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Col 9: Aksi -->
                            <td class="col-aksi py-3 px-4 text-center">
                                <div class="kasbon-m-action">
                                    <a href="<?= Router::url('/kasbon/detail?id=' . $kb['id']) ?>" 
                                       class="btn btn-secondary btn-sm text-xs px-3 py-1.5 flex items-center justify-center gap-1.5 w-full sm:w-auto" 
                                       title="Lihat Rincian & Riwayat Cicilan">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-500"></i>
                                        <span>Detail Pinjaman</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. MODAL PENGAJUAN KASBON BARU (Teleported to Body)                       -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="modalTambahOpen" 
             x-cloak 
             class="modal-backdrop" 
             @click="closeModalTambah()" 
             @keydown.escape.window="closeModalTambah()">
            
            <div class="modal-box modal-box-md" style="max-width:520px;" @click.stop>
                <!-- Mobile Bottom-Sheet Pull Handle -->
                <div class="modal-handle">
                    <div class="modal-handle-bar"></div>
                </div>

                <!-- Modal Header -->
                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(99,102,241,0.1);color:#4f46e5;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(99,102,241,0.25);">
                            <i data-lucide="hand-coins" style="width:18px;height:18px;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div class="modal-title">Formulir Tambah Kasbon Baru</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Daftarkan pinjaman dana kasbon karyawan beserta rencana cicilan</div>
                        </div>
                    </div>
                    <button type="button" @click="closeModalTambah()" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form action="<?= Router::url('/kasbon/store') ?>" method="POST" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                    <?= CSRF::field() ?>

                    <div class="modal-body custom-scrollbar" style="display:flex;flex-direction:column;gap:14px;">
                        
                        <!-- 1. Karyawan Selection (Searchable Dropdown) -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Pilih Karyawan <span style="color:#e11d48;">*</span>
                            </label>

                            <div class="relative" @click.outside="karyawanDropdownOpen = false">
                                <button type="button"
                                        @click="karyawanDropdownOpen = !karyawanDropdownOpen"
                                        class="form-input flex items-center justify-between w-full text-left font-medium transition cursor-pointer"
                                        style="height:40px; border-radius:var(--rounded-md, 8px); background-color:var(--color-canvas, #ffffff); border:1px solid var(--color-hairline-strong, #cbd5e1); padding:0 12px;">
                                    <div class="flex items-center gap-2 min-w-0 pr-2">
                                        <div class="kasbon-avatar" style="width:22px; height:22px; font-size:9.5px;" x-show="selectedKid && currentKaryawan">
                                            <span x-text="currentKaryawan ? currentKaryawan.initials : 'KR'"></span>
                                        </div>
                                        <span class="truncate text-xs font-bold" 
                                              :style="!selectedKid ? 'color:var(--color-ink-mute); font-weight:500;' : 'color:var(--color-ink);'"
                                              x-text="selectedKid && currentKaryawan ? (currentKaryawan.nama + ' (' + currentKaryawan.posisi + ' - ' + currentKaryawan.tipe_penggajian + ')') : '-- Pilih Karyawan --'">
                                        </span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="karyawanDropdownOpen ? 'rotate-180' : ''"></i>
                                </button>

                                <input type="hidden" name="karyawan_id" :value="selectedKid" required>

                                <!-- Dropdown Menu -->
                                <div x-show="karyawanDropdownOpen" x-cloak
                                     class="dropdown-menu-searchable bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl"
                                     style="position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:1050; overflow:hidden;">
                                    
                                    <div style="padding:8px 10px; border-bottom:1px solid var(--color-hairline, #e2e8f0); background:var(--color-canvas-soft, #f8fafc);">
                                        <div style="position:relative; display:flex; align-items:center; width:100%;">
                                            <svg style="position:absolute; left:10px; width:14px; height:14px; color:#94a3b8; pointer-events:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                            </svg>
                                            <input type="text" 
                                                   x-model="karyawanSearch" 
                                                   placeholder="Ketik nama atau divisi..." 
                                                   class="form-input"
                                                   style="height:34px; padding-left:32px; padding-right:10px; font-size:12px; border-radius:8px; width:100%; border:1px solid var(--color-hairline-strong, #cbd5e1); background:var(--color-canvas, #ffffff); color:var(--color-ink, #0f172a);">
                                        </div>
                                    </div>

                                    <div class="max-h-52 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60 custom-scrollbar">
                                        <template x-for="k in filteredKaryawans" :key="k.id">
                                            <div @click="selectKaryawan(k.id)"
                                                 class="searchable-option p-2.5 flex items-center justify-between gap-3 cursor-pointer transition-colors"
                                                 :class="String(k.id) === String(selectedKid) ? 'is-selected' : ''">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <div class="kasbon-avatar" style="width:28px; height:28px; font-size:10px;">
                                                        <span x-text="k.initials"></span>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate" x-text="k.nama"></div>
                                                        <div class="text-[11px] text-slate-400 capitalize truncate" x-text="k.posisi + ' • ' + k.tipe_penggajian"></div>
                                                    </div>
                                                </div>
                                                <div class="text-right shrink-0">
                                                    <template x-if="Number(k.total_kasbon_berjalan || 0) > 0">
                                                        <span class="kasbon-dropdown-badge">
                                                            <span>Kasbon:</span>
                                                            <span class="font-bold" x-text="formatRupiah(k.total_kasbon_berjalan)"></span>
                                                        </span>
                                                    </template>
                                                    <template x-if="Number(k.total_kasbon_berjalan || 0) <= 0">
                                                        <span class="kasbon-dropdown-badge-empty">
                                                            <svg style="width:11px;height:11px;color:#059669;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                                <polyline points="20 6 9 17 4 12"></polyline>
                                                            </svg>
                                                            <span>Bebas Kasbon</span>
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <div x-show="filteredKaryawans.length === 0" style="padding:14px; text-align:center; font-size:11.5px; color:var(--color-ink-mute, #94a3b8);">
                                            Tidak ada karyawan yang cocok
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Warning Kasbon Berjalan Banner -->
                            <div x-show="selectedKid && currentKaryawan && Number(currentKaryawan.total_kasbon_berjalan || 0) > 0" x-cloak
                                 class="mt-2 p-2.5 rounded-lg flex items-start gap-2"
                                 style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);">
                                <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5 text-amber-600"></i>
                                <div class="text-xs text-amber-900 dark:text-amber-300">
                                    <div class="font-bold">Perhatian: Ada Kasbon Berjalan</div>
                                    <div class="text-[11px] mt-0.5">Karyawan ini masih memiliki sisa kasbon aktif sebesar <strong class="font-mono" x-text="formatRupiah(currentKaryawan ? currentKaryawan.total_kasbon_berjalan : 0)"></strong>. Pastikan nominal cicilan tidak memberatkan batas penggajian.</div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Tanggal Pinjaman & Total Pinjaman Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label" style="display:block;margin-bottom:6px;">
                                    Tanggal Pinjaman <span style="color:#e11d48;">*</span>
                                </label>
                                <input type="date" 
                                       name="tanggal_pengajuan" 
                                       x-model="formTanggal" 
                                       required 
                                       class="form-input text-xs font-medium w-full"
                                       style="height:38px; border-radius:var(--rounded-md, 8px);">
                            </div>

                            <div>
                                <label class="form-label" style="display:block;margin-bottom:6px;">
                                    Total Pinjaman (Rp) <span style="color:#e11d48;">*</span>
                                </label>
                                <div class="pg-currency-group">
                                    <span class="pg-currency-addon">Rp</span>
                                    <input type="text" 
                                           x-model="totalPinjamanDisplay" 
                                           @input="onTotalPinjamanInput($event)"
                                           @focus="$event.target.select()"
                                           @click="$event.target.select()"
                                           required 
                                           placeholder="0" 
                                           class="pg-currency-input font-mono font-bold text-right"
                                           autocomplete="off">
                                    <input type="hidden" name="total_pinjaman" :value="totalPinjamanRaw">
                                </div>
                            </div>
                        </div>

                        <!-- Quick Nominal Preset Chips -->
                        <div>
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Preset Pinjaman Cepat:</div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <button type="button" @click="setTotalPinjaman(500000)" class="quick-chip-btn">500.000</button>
                                <button type="button" @click="setTotalPinjaman(1000000)" class="quick-chip-btn">1.000.000</button>
                                <button type="button" @click="setTotalPinjaman(2000000)" class="quick-chip-btn">2.000.000</button>
                                <button type="button" @click="setTotalPinjaman(3000000)" class="quick-chip-btn">3.000.000</button>
                                <button type="button" @click="resetTotalPinjaman()" class="quick-chip-btn text-rose-600 dark:text-rose-400">Reset</button>
                            </div>
                        </div>

                        <!-- 3. Cicilan / Potongan per Periode Payroll -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Potongan / Cicilan per Periode Payroll (Rp)
                            </label>
                            <div class="pg-currency-group">
                                <span class="pg-currency-addon">Rp</span>
                                <input type="text" 
                                       x-model="cicilanDisplay" 
                                       @input="onCicilanInput($event)"
                                       @focus="$event.target.select()"
                                       @click="$event.target.select()"
                                       placeholder="0 (Isi 0 jika bayar manual)" 
                                       class="pg-currency-input font-mono font-bold text-right"
                                       autocomplete="off">
                                <input type="hidden" name="potongan_per_periode" :value="cicilanRaw">
                            </div>
                            <span class="text-[11px] text-slate-400 block mt-1">Nominal yang akan otomatis dipotong setiap kali periode penggajian diproses.</span>

                            <!-- Quick Plan Divider Chips -->
                            <div class="flex items-center gap-1.5 mt-2 flex-wrap" x-show="totalPinjamanRaw > 0">
                                <span class="text-[10.5px] font-semibold text-slate-400">Rencana Cicilan:</span>
                                <button type="button" @click="divideCicilan(1)" class="quick-chip-btn text-[10.5px]" :class="selectedDivideCount === 1 ? 'is-active' : ''">1x (Lunas Sekali)</button>
                                <button type="button" @click="divideCicilan(2)" class="quick-chip-btn text-[10.5px]" :class="selectedDivideCount === 2 ? 'is-active' : ''">Bagi 2x</button>
                                <button type="button" @click="divideCicilan(4)" class="quick-chip-btn text-[10.5px]" :class="selectedDivideCount === 4 ? 'is-active' : ''">Bagi 4x</button>
                                <button type="button" @click="divideCicilan(5)" class="quick-chip-btn text-[10.5px]" :class="selectedDivideCount === 5 ? 'is-active' : ''">Bagi 5x</button>
                            </div>
                        </div>

                        <!-- 4. Pilihan Sumber Akun Kas & Opsi Bypass -->
                        <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-800 dark:text-slate-100 flex items-center gap-1.5">
                                    <i data-lucide="wallet" class="w-3.5 h-3.5 text-rose-600"></i>
                                    <span>Sumber Kas Pencairan Pinjaman</span>
                                    <span class="text-rose-500" x-show="!bypassKas">*</span>
                                </label>
                                <label class="flex items-center gap-1.5 cursor-pointer text-[11px] font-medium text-slate-600 dark:text-slate-400 select-none">
                                    <input type="checkbox" name="bypass_kas" value="1" x-model="bypassKas" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 w-3.5 h-3.5">
                                    <span>Bypass Kas (Non-Kas)</span>
                                </label>
                            </div>

                            <div x-show="!bypassKas" class="space-y-2">
                                <input type="hidden" name="akun_kas_id" :value="selectedKasId" :required="!bypassKas">
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-44 overflow-y-auto custom-scrollbar p-0.5">
                                    <template x-for="acc in (cashAccounts || [])" :key="acc.id">
                                        <div @click="selectedKasId = acc.id"
                                             :class="{
                                                 'border-rose-600 dark:border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/40 dark:bg-rose-950/20': selectedKasId === acc.id,
                                                 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 hover:border-slate-300 dark:hover:border-slate-600': selectedKasId !== acc.id,
                                                 'opacity-60 cursor-not-allowed border-dashed': acc.saldo < (totalPinjamanRaw || 0)
                                             }"
                                             class="relative flex items-center justify-between p-2.5 rounded-lg border transition-all cursor-pointer select-none">
                                            
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
                                                        Saldo: <span :class="acc.saldo < (totalPinjamanRaw || 0) ? 'text-rose-600 font-bold' : 'text-slate-700 dark:text-slate-200'" x-text="formatRupiah(acc.saldo)"></span>
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
                                <div x-show="selectedAccount && selectedAccount.saldo < totalPinjamanRaw" class="text-[11px] text-rose-600 dark:text-rose-400 font-medium flex items-center gap-1">
                                    <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                    <span>Saldo akun kas terpilih kurang dari nominal pinjaman.</span>
                                </div>
                            </div>
                            <div x-show="bypassKas" class="text-[11px] text-slate-500 dark:text-slate-400 italic">
                                Transaksi kasbon ini akan dicatat tanpa memotong saldo kas perusahaan (pembukuan historis/eksternal).
                            </div>
                        </div>

                        <!-- 5. Alasan / Keterangan Kasbon -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Alasan / Keterangan Pinjaman
                            </label>
                            <input type="text" 
                                   name="keterangan" 
                                   x-model="formKeterangan"
                                   placeholder="Contoh: Keperluan mendesak keluarga..." 
                                   class="form-input text-xs w-full"
                                   style="height:38px; border-radius:var(--rounded-md, 8px);">

                            <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                <span class="text-[10.5px] font-semibold text-slate-400">Pilihan Cepat:</span>
                                <button type="button" @click="formKeterangan = 'Kebutuhan Mendesak Keluarga'" class="quick-chip-btn text-[10.5px]">Kebutuhan Keluarga</button>
                                <button type="button" @click="formKeterangan = 'Biaya Pengobatan / Medis'" class="quick-chip-btn text-[10.5px]">Biaya Medis</button>
                                <button type="button" @click="formKeterangan = 'Pendidikan & Sekolah Anak'" class="quick-chip-btn text-[10.5px]">Pendidikan Anak</button>
                                <button type="button" @click="formKeterangan = 'Perbaikan Kendaraan Operasional'" class="quick-chip-btn text-[10.5px]">Perbaikan Kendaraan</button>
                            </div>
                        </div>

                        <!-- 6. Catatan Tambahan (Opsional) -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Catatan Tambahan (Opsional)
                            </label>
                            <textarea name="catatan" 
                                      rows="2" 
                                      placeholder="Catatan persetujuan atau perjanjian internal..." 
                                      class="form-input text-xs w-full"
                                      style="border-radius:var(--rounded-md, 8px);"></textarea>
                        </div>

                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" @click="closeModalTambah()" class="btn btn-secondary modal-btn-cancel-desktop">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary-maroon font-bold">
                            <i data-lucide="save"></i>
                            <span>Simpan Kasbon</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>

<script>
function kasbonApp() {
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
        }, $akunKasList ?? []), JSON_UNESCAPED_UNICODE) ?>,

        // Table Filter & Search
        searchQuery: '',
        statusFilter: 'aktif', // 'all', 'aktif', 'lunas', 'borongan', 'bulanan'

        // Modal Form State
        modalTambahOpen: false,
        selectedKid: '',
        selectedKasId: '<?= !empty($akunKasList[0]['id']) ? (string)$akunKasList[0]['id'] : '' ?>',
        bypassKas: false,
        formTanggal: '<?= date('Y-m-d') ?>',
        totalPinjamanRaw: 0,
        totalPinjamanDisplay: '',
        cicilanRaw: 0,
        cicilanDisplay: '',
        selectedDivideCount: 2,
        formKeterangan: 'Kebutuhan Mendesak Keluarga',
        karyawanDropdownOpen: false,
        karyawanSearch: '',

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
            this.selectedKid = '';
            this.formTanggal = '<?= date('Y-m-d') ?>';
            this.totalPinjamanRaw = 0;
            this.totalPinjamanDisplay = '';
            this.cicilanRaw = 0;
            this.cicilanDisplay = '';
            this.selectedDivideCount = 2;
            this.formKeterangan = 'Kebutuhan Mendesak Keluarga';
            this.karyawanSearch = '';
            this.karyawanDropdownOpen = false;
            this.modalTambahOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        closeModalTambah() {
            this.modalTambahOpen = false;
            this.karyawanDropdownOpen = false;
        },

        selectKaryawan(id) {
            this.selectedKid = id;
            this.karyawanDropdownOpen = false;
            this.karyawanSearch = '';
        },

        onTotalPinjamanInput(e) {
            let raw = String(e.target.value || '').replace(/\D/g, '');
            let val = parseInt(raw, 10) || 0;
            this.totalPinjamanRaw = val;
            this.totalPinjamanDisplay = val > 0 ? val.toLocaleString('id-ID') : '';
            if (this.selectedDivideCount > 0) {
                this.divideCicilan(this.selectedDivideCount);
            }
        },

        onCicilanInput(e) {
            let raw = String(e.target.value || '').replace(/\D/g, '');
            let val = parseInt(raw, 10) || 0;
            this.cicilanRaw = val;
            this.cicilanDisplay = val > 0 ? val.toLocaleString('id-ID') : '';
            this.selectedDivideCount = null;
        },

        setTotalPinjaman(amount) {
            let val = Number(amount) || 0;
            this.totalPinjamanRaw = val;
            this.totalPinjamanDisplay = val > 0 ? val.toLocaleString('id-ID') : '';
            // Default cicilan: bagi 2x
            this.divideCicilan(2);
        },

        resetTotalPinjaman() {
            this.totalPinjamanRaw = 0;
            this.totalPinjamanDisplay = '';
            this.cicilanRaw = 0;
            this.cicilanDisplay = '';
            this.selectedDivideCount = null;
        },

        divideCicilan(count) {
            this.selectedDivideCount = count;
            if (this.totalPinjamanRaw > 0 && count > 0) {
                let part = Math.round(this.totalPinjamanRaw / count);
                this.cicilanRaw = part;
                this.cicilanDisplay = part > 0 ? part.toLocaleString('id-ID') : '';
            } else {
                this.cicilanRaw = 0;
                this.cicilanDisplay = '';
            }
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        isRowVisible(keywords, status, tipeGaji) {
            // Status Check
            if (this.statusFilter === 'aktif' && status !== 'aktif') return false;
            if (this.statusFilter === 'lunas' && status !== 'lunas') return false;
            if (this.statusFilter === 'borongan' && tipeGaji !== 'borongan') return false;
            if (this.statusFilter === 'bulanan' && tipeGaji !== 'bulanan') return false;

            // Search Check
            if (!this.searchQuery.trim()) return true;
            const q = this.searchQuery.toLowerCase().trim();
            return keywords.includes(q);
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

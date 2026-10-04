<?php
/**
 * views/tabungan/index.php
 * Halaman Utama Modul Tabungan / Simpanan Karyawan Keren One ERP
 * 100% Selaras dengan DNA Desain, Responsive Desktop & Mobile, & Sistem Modal KEREN ONE
 */

use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CSRF;

ob_start();

function getTabunganInitials(string $name): string {
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

function getTabunganAvatarColor(string $name): array {
    $colors = [
        ['bg' => 'rgba(16, 185, 129, 0.12)', 'text' => '#047857', 'border' => 'rgba(16, 185, 129, 0.28)'], // Emerald
        ['bg' => 'rgba(79, 70, 229, 0.1)',   'text' => '#4338ca', 'border' => 'rgba(79, 70, 229, 0.25)'],  // Indigo
        ['bg' => 'rgba(2, 132, 199, 0.1)',   'text' => '#0284c7', 'border' => 'rgba(2, 132, 199, 0.25)'],  // Sky Blue
        ['bg' => 'rgba(217, 119, 6, 0.12)',  'text' => '#b45309', 'border' => 'rgba(217, 119, 6, 0.28)'],  // Warm Amber
        ['bg' => 'rgba(147, 51, 234, 0.1)',  'text' => '#7e22ce', 'border' => 'rgba(147, 51, 234, 0.25)'], // Violet
        ['bg' => 'rgba(13, 148, 136, 0.12)', 'text' => '#0f766e', 'border' => 'rgba(13, 148, 136, 0.28)'], // Teal
        ['bg' => 'rgba(225, 29, 72, 0.1)',   'text' => '#be123c', 'border' => 'rgba(225, 29, 72, 0.25)'],  // Rose
    ];
    $idx = abs(crc32($name)) % count($colors);
    return $colors[$idx];
}

$escrowAccount = null;
foreach ($akunKasList ?? [] as $acc) {
    if (!empty($acc['is_escrow'])) {
        $escrowAccount = $acc;
        break;
    }
}
?>

<style>
/* ==========================================================================
   Tabungan Module DNA Styling & Responsive Utilities (Forest & Harmonies)
   ========================================================================== */

/* 0. KPI Stat Cards */
.tabungan-stat-card {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 14px;
    padding: 13px 15px;
    display: flex;
    align-items: center;
    gap: 13px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.tabungan-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border-color: var(--color-hairline-strong, #cbd5e1);
}
.dark .tabungan-stat-card {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}
.dark .tabungan-stat-card:hover {
    border-color: #475569;
}

/* Stat Icon Box */
.tabungan-stat-icon {
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
.tabungan-stat-icon svg {
    width: 20px;
    height: 20px;
    display: block;
}

.tabungan-stat-icon.is-emerald {
    background: rgba(16, 185, 129, 0.1);
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.25);
}
.dark .tabungan-stat-icon.is-emerald {
    background: rgba(16, 185, 129, 0.18);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.35);
}

.tabungan-stat-icon.is-indigo {
    background: rgba(99, 102, 241, 0.1);
    color: #4f46e5;
    border: 1px solid rgba(99, 102, 241, 0.25);
}
.dark .tabungan-stat-icon.is-indigo {
    background: rgba(99, 102, 241, 0.18);
    color: #a5b4fc;
    border-color: rgba(99, 102, 241, 0.35);
}

.tabungan-stat-icon.is-sky {
    background: rgba(2, 132, 199, 0.1);
    color: #0284c7;
    border: 1px solid rgba(2, 132, 199, 0.25);
}
.dark .tabungan-stat-icon.is-sky {
    background: rgba(2, 132, 199, 0.18);
    color: #38bdf8;
    border-color: rgba(2, 132, 199, 0.35);
}

.tabungan-stat-icon.is-amber {
    background: rgba(217, 119, 6, 0.1);
    color: #d97706;
    border: 1px solid rgba(217, 119, 6, 0.25);
}
.dark .tabungan-stat-icon.is-amber {
    background: rgba(217, 119, 6, 0.18);
    color: #fbbf24;
    border-color: rgba(217, 119, 6, 0.35);
}

/* 1. Filter Dock Container */
.tabungan-filter-dock {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: var(--rounded-lg, 12px);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    margin-bottom: 16px;
    padding: 12px 14px;
}
.dark .tabungan-filter-dock {
    background: #1e293b;
    border-color: #334155;
}

.tabungan-filter-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

/* 2. Filter Status Tab Pills (Forest Emerald Theme) */
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
    background: #047857 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(4, 120, 87, 0.3);
}
.dark .tab-pill-btn.is-active {
    background: #059669 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(5, 150, 105, 0.35);
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
.tabungan-search-box {
    position: relative;
    display: flex;
    align-items: center;
    min-width: 220px;
    flex: 1;
    max-width: 320px;
}
.tabungan-search-input {
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
.tabungan-search-input:focus {
    border-color: #047857 !important;
    box-shadow: 0 0 0 1px #047857 !important;
    outline: none !important;
}
.dark .tabungan-search-input {
    background: #1e293b;
    border-color: #475569;
    color: #f8fafc;
}
.dark .tabungan-search-input:focus {
    border-color: #34d399 !important;
    box-shadow: 0 0 0 1px #34d399 !important;
}

/* 4. Table Avatar Badge */
.tabungan-avatar {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 11.5px;
    flex-shrink: 0;
    user-select: none;
    border-width: 1px;
    border-style: solid;
}

/* Table Card Container Rounded */
.tabungan-table-card {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
.dark .tabungan-table-card {
    background: #1e293b;
    border-color: #334155;
}
.tabungan-table-card .table-wrapper {
    overflow-x: auto;
    border-radius: 16px;
}
.tabungan-table-card table thead tr th {
    background: #f8fafc;
    color: #475569;
    border-bottom: 1px solid #e2e8f0;
}
.dark .tabungan-table-card table thead tr th {
    background: #0f172a;
    color: #94a3b8;
    border-bottom-color: #334155;
}
.tabungan-table-card table thead tr th:first-child {
    border-top-left-radius: 15px;
}
.tabungan-table-card table thead tr th:last-child {
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
    border-color: #047857 !important;
    box-shadow: 0 0 0 1px #047857 !important;
}
.dark .pg-currency-group:focus-within {
    border-color: #34d399 !important;
    box-shadow: 0 0 0 1px #34d399 !important;
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
    animation: tabunganDropdownFadeIn 0.15s ease-out;
}
@keyframes tabunganDropdownFadeIn {
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
    background-color: rgba(4, 120, 87, 0.08) !important;
}
.dark .searchable-option.is-selected {
    background-color: rgba(16, 185, 129, 0.14) !important;
}

/* Canonical Page Header Icon Emerald / Forest */
.page-header-icon.is-emerald {
    background: rgba(16, 185, 129, 0.12) !important;
    color: #047857 !important;
    border: 1px solid rgba(16, 185, 129, 0.28) !important;
}
.dark .page-header-icon.is-emerald {
    background: rgba(16, 185, 129, 0.18) !important;
    color: #34d399 !important;
    border-color: rgba(16, 185, 129, 0.35) !important;
}

/* Primary Button in Mature Calm Forest Emerald */
.btn-primary-forest {
    background: linear-gradient(135deg, #047857 0%, #065f46 100%) !important;
    color: #ffffff !important;
    border: 1px solid #065f46 !important;
    box-shadow: 0 1px 3px rgba(4, 120, 87, 0.25);
    transition: all 0.15s ease;
}
.btn-primary-forest:hover {
    background: linear-gradient(135deg, #065f46 0%, #064e3b 100%) !important;
    color: #ffffff !important;
}

/* Secondary Button in Warm Amber */
.btn-secondary-amber {
    background: rgba(245, 158, 11, 0.08) !important;
    color: #b45309 !important;
    border: 1px solid rgba(245, 158, 11, 0.28) !important;
    transition: all 0.15s ease;
}
.btn-secondary-amber:hover {
    background: #d97706 !important;
    color: #ffffff !important;
    border-color: #b45309 !important;
}
.dark .btn-secondary-amber {
    background: rgba(245, 158, 11, 0.16) !important;
    color: #fbbf24 !important;
    border-color: rgba(245, 158, 11, 0.38) !important;
}
.dark .btn-secondary-amber:hover {
    background: #d97706 !important;
    color: #ffffff !important;
}

/* Sleek Pill Badge for Saldo */
.tabungan-saldo-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.28);
    white-space: nowrap;
    line-height: 1.3;
}
.dark .tabungan-saldo-badge {
    background: rgba(16, 185, 129, 0.18);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.35);
}
.tabungan-saldo-badge-empty {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 10.5px;
    font-weight: 600;
    color: #64748b;
    background: rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.08);
    white-space: nowrap;
    line-height: 1.3;
}
.dark .tabungan-saldo-badge-empty {
    background: rgba(255, 255, 255, 0.06);
    color: #94a3b8;
    border-color: rgba(255, 255, 255, 0.12);
}

/* Payroll Type Badges */
.badge-tipe-bulanan {
    display: inline-flex;
    align-items: center;
    padding: 2.5px 8px;
    border-radius: 9999px;
    font-size: 10px;
    font-weight: 700;
    color: #0284c7;
    background: rgba(2, 132, 199, 0.1);
    border: 1px solid rgba(2, 132, 199, 0.25);
    white-space: nowrap;
}
.dark .badge-tipe-bulanan {
    color: #38bdf8;
    background: rgba(2, 132, 199, 0.18);
    border-color: rgba(2, 132, 199, 0.35);
}

.badge-tipe-borongan {
    display: inline-flex;
    align-items: center;
    padding: 2.5px 8px;
    border-radius: 9999px;
    font-size: 10px;
    font-weight: 700;
    color: #7e22ce;
    background: rgba(147, 51, 234, 0.1);
    border: 1px solid rgba(147, 51, 234, 0.25);
    white-space: nowrap;
}
.dark .badge-tipe-borongan {
    color: #c084fc;
    background: rgba(147, 51, 234, 0.18);
    border-color: rgba(147, 51, 234, 0.35);
}

/* Desktop Rules (>= 768px) */
@media (min-width: 768px) {
    .tabungan-m-only,
    .tabungan-m-flex {
        display: none !important;
    }
    .tabungan-d-only {
        display: block;
    }
    .tabungan-d-flex {
        display: flex;
    }
}

/* Responsive Table for Mobile App Feel (< 768px) */
@media (max-width: 767px) {
    .responsive-tabungan-table,
    .responsive-tabungan-table tbody {
        display: block !important;
        width: 100% !important;
    }
    .responsive-tabungan-table thead {
        display: none !important;
    }
    .responsive-tabungan-table tbody tr.tabungan-data-row {
        display: block !important;
        background: var(--color-canvas, #ffffff) !important;
        border: 1px solid var(--color-hairline, #e2e8f0) !important;
        border-radius: 16px !important;
        padding: 14px !important;
        margin-bottom: 12px !important;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03) !important;
    }
    .dark .responsive-tabungan-table tbody tr.tabungan-data-row {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    /* Hide desktop table cells on mobile */
    .responsive-tabungan-table td.col-no,
    .responsive-tabungan-table td.col-tipe,
    .responsive-tabungan-table td.col-saldo,
    .responsive-tabungan-table td.col-mutasi,
    .responsive-tabungan-table td.col-terakhir,
    .responsive-tabungan-table td.col-aksi {
        display: none !important;
    }
    .responsive-tabungan-table td.col-karyawan {
        display: block !important;
        padding: 0 !important;
        border: none !important;
    }
    .tabungan-d-only,
    .tabungan-d-flex {
        display: none !important;
    }
    .tabungan-m-only {
        display: block !important;
    }
    .tabungan-m-flex {
        display: flex !important;
    }
    .tabungan-m-num-pill {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        font-family: var(--font-mono, ui-monospace, monospace) !important;
        color: #0f766e !important;
        background: rgba(15, 118, 110, 0.08) !important;
        border: 1px solid rgba(15, 118, 110, 0.18) !important;
        padding: 3px 7px !important;
        border-radius: 7px !important;
        line-height: 1.2 !important;
    }
    .dark .tabungan-m-num-pill {
        color: #2dd4bf !important;
        background: rgba(45, 212, 191, 0.12) !important;
        border-color: rgba(45, 212, 191, 0.25) !important;
    }
    .tabungan-search-box {
        max-width: 100% !important;
        min-width: 100% !important;
    }
}
</style>

<div x-data="tabunganApp()" class="space-y-4">

    <!-- 1. PAGE HEADER (Pola Kanonikal KEREN ONE ERP) -->
    <div class="page-header flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-emerald" style="flex-shrink:0;">
                <i data-lucide="piggy-bank"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#047857;"></span>
                    <span>Modul HR &bull; Simpanan &amp; Tabungan Pegawai</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100">
                    <?= htmlspecialchars($pageTitle) ?>
                </h1>
                <p class="page-subtitle text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    <?= htmlspecialchars($pageSubtitle) ?>
                </p>
            </div>
        </div>
        <div class="page-header-actions grid grid-cols-2 sm:flex items-center gap-2 w-full sm:w-auto justify-end">
            <button type="button" 
                    @click="openModalSetor()" 
                    class="btn btn-primary-forest text-xs sm:text-sm font-bold flex items-center justify-center gap-1.5 w-full sm:w-auto"
                    style="height:38px; border-radius:10px;">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Setor Simpanan</span>
            </button>
            <button type="button" 
                    @click="openModalTarik()" 
                    class="btn btn-secondary-amber text-xs sm:text-sm font-bold flex items-center justify-center gap-1.5 w-full sm:w-auto"
                    style="height:38px; border-radius:10px;">
                <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                <span>Tarik Simpanan</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI METRICS CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- Card 1: Total Saldo -->
        <div class="tabungan-stat-card">
            <div class="tabungan-stat-icon is-emerald">
                <i data-lucide="piggy-bank"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Dana Simpanan</div>
                <div class="text-lg sm:text-xl font-bold font-mono text-emerald-700 dark:text-emerald-300 mt-0.5"><?= Format::rupiah($totalSaldo) ?></div>
            </div>
        </div>

        <!-- Card 2: Karyawan Penabung -->
        <div class="tabungan-stat-card">
            <div class="tabungan-stat-icon is-indigo">
                <i data-lucide="users"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Karyawan Penabung</div>
                <div class="text-lg sm:text-xl font-bold font-mono text-slate-900 dark:text-slate-100 mt-0.5">
                    <?= $karyawanMenabung ?> <span class="text-xs font-normal text-slate-400">/ <?= $totalKaryawan ?> aktif</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Setoran Bulan Ini -->
        <div class="tabungan-stat-card">
            <div class="tabungan-stat-icon is-sky">
                <i data-lucide="arrow-down-left"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Setoran Bulan Ini</div>
                <div class="text-lg sm:text-xl font-bold font-mono text-sky-600 dark:text-sky-400 mt-0.5"><?= Format::rupiah($depositBulanIni) ?></div>
            </div>
        </div>

        <!-- Card 4: Penarikan Bulan Ini -->
        <div class="tabungan-stat-card">
            <div class="tabungan-stat-icon is-amber">
                <i data-lucide="arrow-up-right"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Penarikan Bulan Ini</div>
                <div class="text-lg sm:text-xl font-bold font-mono text-amber-600 dark:text-amber-400 mt-0.5"><?= Format::rupiah($withdrawalBulanIni) ?></div>
            </div>
        </div>
    </div>

    <!-- 3. FILTER & SEARCH DOCK -->
    <div class="tabungan-filter-dock">
        <div class="tabungan-filter-row">
            <!-- Filter Tabs -->
            <div class="tab-pill-group">
                <button type="button" 
                        @click="statusFilter = 'all'" 
                        class="tab-pill-btn" 
                        :class="statusFilter === 'all' ? 'is-active' : ''">
                    <span>Semua Karyawan</span>
                    <span class="tab-pill-counter"><?= count($tabunganList) ?></span>
                </button>
                <button type="button" 
                        @click="statusFilter = 'saldo_positif'" 
                        class="tab-pill-btn" 
                        :class="statusFilter === 'saldo_positif' ? 'is-active' : ''">
                    <span>Ada Saldo</span>
                    <span class="tab-pill-counter"><?= $karyawanMenabung ?></span>
                </button>
                <button type="button" 
                        @click="statusFilter = 'saldo_nol'" 
                        class="tab-pill-btn" 
                        :class="statusFilter === 'saldo_nol' ? 'is-active' : ''">
                    <span>Saldo Nol</span>
                    <span class="tab-pill-counter"><?= count($tabunganList) - $karyawanMenabung ?></span>
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
            <div class="tabungan-search-box">
                <svg style="position:absolute; left:11px; width:14px; height:14px; color:#94a3b8; pointer-events:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Cari karyawan / divisi..." 
                       class="tabungan-search-input">
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

    <!-- 4. DATA TABLE TABUNGAN -->
    <div class="tabungan-table-card">
        <div class="table-wrapper">
            <table class="responsive-tabungan-table data-table w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4 min-w-[200px]">Nama Karyawan</th>
                        <th class="py-3 px-4 w-28 text-center">Tipe Gaji</th>
                        <th class="py-3 px-4 text-right min-w-[140px]">Saldo Simpanan</th>
                        <th class="py-3 px-4 text-center w-28">Total Mutasi</th>
                        <th class="py-3 px-4 w-36">Terakhir Transaksi</th>
                        <th class="py-3 px-4 text-center min-w-[170px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($tabunganList)): ?>
                        <tr class="border-0">
                            <td colspan="7" class="py-12 px-4 text-center border-0">
                                <div style="width:48px;height:48px;border-radius:14px;background:rgba(16,185,129,0.08);color:#047857;display:flex;align-items:center;justify-content:center;margin:0 auto 12px auto;border:1px solid rgba(16,185,129,0.2);">
                                    <i data-lucide="piggy-bank" style="width:24px;height:24px;"></i>
                                </div>
                                <div class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Akun Tabungan</div>
                                <div class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Tidak ada data tabungan karyawan aktif yang terdaftar di sistem.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tabunganList as $idx => $t): 
                            $saldo = (float)$t['saldo'];
                            $initials = getTabunganInitials($t['nama_karyawan']);
                            $avatarTheme = getTabunganAvatarColor($t['nama_karyawan']);
                            $searchKeywords = strtolower($t['nama_karyawan'] . ' ' . ($t['posisi'] ?? '') . ' ' . ($t['tipe_penggajian'] ?? ''));
                        ?>
                        <tr class="tabungan-data-row hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors"
                            x-show="isRowVisible('<?= htmlspecialchars($searchKeywords, ENT_QUOTES, 'UTF-8') ?>', <?= $saldo ?>, '<?= $t['tipe_penggajian'] ?? '' ?>')"
                            data-kid="<?= htmlspecialchars($t['karyawan_id']) ?>">
                            
                            <!-- Col 1: No (Desktop Only) -->
                            <td class="col-no py-3 px-4 text-center font-mono text-slate-400"><?= $idx + 1 ?></td>

                            <!-- Col 2: Karyawan (Holds Desktop Presentation + Mobile Card) -->
                            <td class="col-karyawan py-3 px-4">
                                <!-- MOBILE CARD VIEW (< 768px) -->
                                <div class="tabungan-m-only">
                                    <div class="flex items-start justify-between gap-2.5">
                                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                            <div class="tabungan-avatar" style="background:<?= $avatarTheme['bg'] ?>; color:<?= $avatarTheme['text'] ?>; border-color:<?= $avatarTheme['border'] ?>;">
                                                <?= htmlspecialchars($initials) ?>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="tabungan-m-num-pill">#<?= $idx + 1 ?></span>
                                                    <span class="font-bold text-slate-900 dark:text-slate-100 truncate text-[13px]"><?= htmlspecialchars($t['nama_karyawan']) ?></span>
                                                </div>
                                                <div class="text-[11px] text-slate-400 capitalize truncate mt-0.5">
                                                    <?= htmlspecialchars($t['posisi'] ?? '-') ?> &bull; 
                                                    <span class="<?= $t['tipe_penggajian'] === 'borongan' ? 'text-purple-600 dark:text-purple-400 font-semibold' : 'text-sky-600 dark:text-sky-400 font-semibold' ?>">
                                                        <?= ucfirst($t['tipe_penggajian'] ?? '') ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Mobile Saldo Pill -->
                                        <div class="shrink-0 text-right">
                                            <?php if ($saldo > 0): ?>
                                                <span class="tabungan-saldo-badge">
                                                    <?= Format::rupiah($saldo) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="tabungan-saldo-badge-empty">
                                                    Rp 0
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Mobile 2-Col Stat Box -->
                                    <div class="grid grid-cols-2 gap-2 mt-3 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-xs">
                                        <div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Mutasi</div>
                                            <div class="font-mono font-bold text-slate-700 dark:text-slate-200 mt-0.5 flex items-center gap-1.5">
                                                <i data-lucide="receipt" class="w-3.5 h-3.5 text-sky-500"></i>
                                                <span><?= $t['jumlah_transaksi'] ?> mutasi</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Terakhir Transaksi</div>
                                            <div class="font-mono font-medium text-slate-600 dark:text-slate-300 mt-0.5 truncate flex items-center gap-1.5">
                                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-500"></i>
                                                <span><?= !empty($t['terakhir_transaksi']) ? Format::tanggalIndo($t['terakhir_transaksi']) : '<span class="text-slate-400 italic font-sans">Belum ada</span>' ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mobile Action Button Row -->
                                    <div class="grid <?= $saldo > 0 ? 'grid-cols-3' : 'grid-cols-2' ?> gap-2 mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800">
                                        <a href="<?= Router::url('/tabungan/detail?karyawan_id=' . $t['karyawan_id']) ?>" 
                                           class="btn btn-secondary btn-sm text-xs font-bold py-2 justify-center flex items-center gap-1.5 rounded-lg">
                                            <i data-lucide="book-open" class="w-3.5 h-3.5 text-slate-500"></i>
                                            <span>Buku</span>
                                        </a>

                                        <button type="button" 
                                                @click="openModalSetor('<?= $t['karyawan_id'] ?>')"
                                                class="btn btn-sm text-xs font-bold py-2 justify-center flex items-center gap-1 rounded-lg"
                                                style="background:rgba(4, 120, 87, 0.08); color:#047857; border:1px solid rgba(4, 120, 87, 0.22);">
                                            <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                                            <span>Setor</span>
                                        </button>

                                        <?php if ($saldo > 0): ?>
                                        <button type="button" 
                                                @click="openModalTarik('<?= $t['karyawan_id'] ?>')"
                                                class="btn btn-sm text-xs font-bold py-2 justify-center flex items-center gap-1 rounded-lg"
                                                style="background:rgba(217, 119, 6, 0.08); color:#b45309; border:1px solid rgba(217, 119, 6, 0.22);">
                                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                                            <span>Tarik</span>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- DESKTOP VIEW (>= 768px) -->
                                <div class="tabungan-d-flex items-center gap-2.5">
                                    <div class="tabungan-avatar" style="background:<?= $avatarTheme['bg'] ?>; color:<?= $avatarTheme['text'] ?>; border-color:<?= $avatarTheme['border'] ?>;">
                                        <?= htmlspecialchars($initials) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 truncate"><?= htmlspecialchars($t['nama_karyawan']) ?></div>
                                        <div class="text-[11px] text-slate-400 capitalize truncate"><?= htmlspecialchars($t['posisi'] ?? '-') ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Col 3: Tipe Gaji (Desktop Only) -->
                            <td class="col-tipe py-3 px-4 text-center">
                                <span class="<?= $t['tipe_penggajian'] === 'borongan' ? 'badge-tipe-borongan' : 'badge-tipe-bulanan' ?>">
                                    <?= ucfirst($t['tipe_penggajian'] ?? '') ?>
                                </span>
                            </td>

                            <!-- Col 4: Saldo Simpanan (Desktop Only) -->
                            <td class="col-saldo py-3 px-4 text-right">
                                <div class="text-sm font-bold font-mono <?= $saldo > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-400' ?>">
                                    <?= Format::rupiah($saldo) ?>
                                </div>
                            </td>

                            <!-- Col 5: Total Mutasi (Desktop Only) -->
                            <td class="col-mutasi py-3 px-4 text-center">
                                <span class="inline-flex items-center gap-1.5 font-mono text-slate-700 dark:text-slate-300 text-xs font-semibold">
                                    <i data-lucide="receipt" class="w-3.5 h-3.5 text-sky-500"></i>
                                    <span><?= $t['jumlah_transaksi'] ?></span>
                                </span>
                            </td>

                            <!-- Col 6: Terakhir Transaksi (Desktop Only) -->
                            <td class="col-terakhir py-3 px-4 text-slate-500 font-mono text-xs">
                                <?php if (!empty($t['terakhir_transaksi'])): ?>
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-500 shrink-0"></i>
                                        <span><?= Format::tanggalIndo($t['terakhir_transaksi']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-400 italic font-sans">Belum ada</span>
                                <?php endif; ?>
                            </td>

                            <!-- Col 7: Aksi (Desktop Only) -->
                            <td class="col-aksi py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Buku Detail Button -->
                                    <a href="<?= Router::url('/tabungan/detail?karyawan_id=' . $t['karyawan_id']) ?>" 
                                       class="btn btn-secondary btn-sm text-[11px] px-2.5 py-1" 
                                       title="Lihat Buku Tabungan">
                                        <i data-lucide="book-open" class="w-3.5 h-3.5 text-slate-500"></i>
                                        <span>Buku</span>
                                    </a>

                                    <!-- Quick Setor Button -->
                                    <button type="button" 
                                            @click="openModalSetor('<?= $t['karyawan_id'] ?>')"
                                            class="btn btn-sm text-[11px] px-2.5 py-1 rounded-md transition"
                                            style="background:rgba(4, 120, 87, 0.08); color:#047857; border:1px solid rgba(4, 120, 87, 0.22);" 
                                            title="Setor Simpanan Manual">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        <span>Setor</span>
                                    </button>

                                    <!-- Quick Tarik Button (Only if saldo > 0) -->
                                    <?php if ($saldo > 0): ?>
                                    <button type="button" 
                                            @click="openModalTarik('<?= $t['karyawan_id'] ?>')"
                                            class="btn btn-sm text-[11px] px-2.5 py-1 rounded-md transition"
                                            style="background:rgba(217, 119, 6, 0.08); color:#b45309; border:1px solid rgba(217, 119, 6, 0.22);" 
                                            title="Tarik Simpanan">
                                        <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                                        <span>Tarik</span>
                                    </button>
                                    <?php endif; ?>
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
    <!-- 5. MODAL SETOR SIMPANAN (Teleported to Body)                               -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="modalSetorOpen" 
             x-cloak 
             class="modal-backdrop" 
             @click="closeModalSetor()" 
             @keydown.escape.window="closeModalSetor()">
            
            <div class="modal-box modal-box-md" style="max-width:500px;" @click.stop>
                <!-- Pull Handle for Mobile Bottom-Sheet -->
                <div class="modal-handle">
                    <div class="modal-handle-bar"></div>
                </div>

                <!-- Modal Header -->
                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(16,185,129,0.12);color:#047857;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(16,185,129,0.25);">
                            <i data-lucide="plus-circle" style="width:18px;height:18px;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div class="modal-title">Setoran Simpanan Karyawan</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Catat penambahan saldo simpanan / tabungan karyawan</div>
                        </div>
                    </div>
                    <button type="button" @click="closeModalSetor()" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form action="<?= Router::url('/tabungan/setor') ?>" method="POST" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                    <?= CSRF::field() ?>

                    <div class="modal-body custom-scrollbar" style="display:flex;flex-direction:column;gap:14px;">
                        
                        <!-- 1. Karyawan Selection (Searchable Dropdown) -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Pilih Karyawan <span style="color:#e11d48;">*</span>
                            </label>

                            <div class="relative" @click.outside="setorDropdownOpen = false">
                                <button type="button"
                                        @click="setorDropdownOpen = !setorDropdownOpen"
                                        class="form-input flex items-center justify-between w-full text-left font-medium transition cursor-pointer"
                                        style="height:40px; border-radius:var(--rounded-md, 8px); background-color:var(--color-canvas, #ffffff); border:1px solid var(--color-hairline-strong, #cbd5e1); padding:0 12px;">
                                    <div class="flex items-center gap-2 min-w-0 pr-2">
                                        <div class="tabungan-avatar" style="width:22px; height:22px; font-size:9.5px; background:rgba(16,185,129,0.12); color:#047857; border-color:rgba(16,185,129,0.25);" x-show="selectedSetorKid && currentSetorKaryawan">
                                            <span x-text="currentSetorKaryawan ? currentSetorKaryawan.initials : 'KR'"></span>
                                        </div>
                                        <span class="truncate text-xs font-bold" 
                                              :style="!selectedSetorKid ? 'color:var(--color-ink-mute); font-weight:500;' : 'color:var(--color-ink);'"
                                              x-text="selectedSetorKid && currentSetorKaryawan ? (currentSetorKaryawan.nama + ' (' + currentSetorKaryawan.posisi + ')') : '-- Pilih Karyawan --'">
                                        </span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="setorDropdownOpen ? 'rotate-180' : ''"></i>
                                </button>

                                <input type="hidden" name="karyawan_id" :value="selectedSetorKid" required>

                                <!-- Dropdown Menu -->
                                <div x-show="setorDropdownOpen" x-cloak
                                     class="dropdown-menu-searchable bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl"
                                     style="position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:1050; overflow:hidden;">
                                    
                                    <div style="padding:8px 10px; border-bottom:1px solid var(--color-hairline, #e2e8f0); background:var(--color-canvas-soft, #f8fafc);">
                                        <div style="position:relative; display:flex; align-items:center; width:100%;">
                                            <svg style="position:absolute; left:10px; width:14px; height:14px; color:#94a3b8; pointer-events:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                            </svg>
                                            <input type="text" 
                                                   x-model="setorSearch" 
                                                   placeholder="Ketik nama atau divisi..." 
                                                   class="form-input"
                                                   style="height:34px; padding-left:32px; padding-right:10px; font-size:12px; border-radius:8px; width:100%; border:1px solid var(--color-hairline-strong, #cbd5e1); background:var(--color-canvas, #ffffff); color:var(--color-ink, #0f172a);">
                                        </div>
                                    </div>

                                    <div class="max-h-52 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60 custom-scrollbar">
                                        <template x-for="k in filteredSetorKaryawans" :key="k.id">
                                            <div @click="selectSetorKaryawan(k.id)"
                                                 class="searchable-option p-2.5 flex items-center justify-between gap-3 cursor-pointer transition-colors"
                                                 :class="String(k.id) === String(selectedSetorKid) ? 'is-selected' : ''">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <div class="tabungan-avatar" style="width:28px; height:28px; font-size:10px; background:rgba(16,185,129,0.12); color:#047857; border-color:rgba(16,185,129,0.25);">
                                                        <span x-text="k.initials"></span>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate" x-text="k.nama"></div>
                                                        <div class="text-[11px] text-slate-400 capitalize truncate" x-text="k.posisi"></div>
                                                    </div>
                                                </div>
                                                <div class="text-right shrink-0">
                                                    <template x-if="Number(k.saldo || 0) > 0">
                                                        <span class="tabungan-saldo-badge">
                                                            <span x-text="formatRupiah(k.saldo)"></span>
                                                        </span>
                                                    </template>
                                                    <template x-if="Number(k.saldo || 0) <= 0">
                                                        <span class="tabungan-saldo-badge-empty">
                                                            Saldo Rp 0
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <div x-show="filteredSetorKaryawans.length === 0" style="padding:14px; text-align:center; font-size:11.5px; color:var(--color-ink-mute, #94a3b8);">
                                            Tidak ada karyawan yang cocok
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Saldo Saat Ini Banner -->
                            <div x-show="selectedSetorKid && currentSetorKaryawan" x-cloak
                                 class="mt-2 p-2.5 rounded-lg flex items-center justify-between gap-2"
                                 style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.25);">
                                <div class="flex items-center gap-1.5 text-xs text-emerald-900 dark:text-emerald-300">
                                    <i data-lucide="wallet" class="w-4 h-4 shrink-0 text-emerald-600"></i>
                                    <span>Saldo Simpanan Saat Ini: <strong class="font-mono font-bold" x-text="formatRupiah(currentSetorKaryawan ? currentSetorKaryawan.saldo : 0)"></strong></span>
                                </div>
                            </div>

                            <!-- Escrow Account Target Banner -->
                            <div class="mt-2 p-2.5 rounded-lg flex items-center justify-between gap-2 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs">
                                <div class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                                    <i data-lucide="lock" class="w-3.5 h-3.5 text-purple-600"></i>
                                    <span>Tersimpan di: <strong class="text-slate-900 dark:text-slate-100"><?= htmlspecialchars($escrowAccount['nama_akun'] ?? 'Kas Tabungan Karyawan') ?></strong></span>
                                </div>
                                <span class="font-mono font-bold text-slate-600 dark:text-slate-400 text-[11px]"><?= Format::rupiah((float)($escrowAccount['saldo_saat_ini'] ?? 0)) ?></span>
                            </div>
                        </div>

                        <!-- 2. Tanggal & Nominal Setor Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label" style="display:block;margin-bottom:6px;">
                                    Tanggal Setoran <span style="color:#e11d48;">*</span>
                                </label>
                                <input type="date" 
                                       name="tanggal" 
                                       x-model="setorTanggal" 
                                       required 
                                       class="form-input text-xs font-medium w-full"
                                       style="height:38px; border-radius:var(--rounded-md, 8px);">
                            </div>

                            <div>
                                <label class="form-label" style="display:block;margin-bottom:6px;">
                                    Nominal Setor (Rp) <span style="color:#e11d48;">*</span>
                                </label>
                                <div class="pg-currency-group">
                                    <span class="pg-currency-addon">Rp</span>
                                    <input type="text" 
                                           inputmode="numeric"
                                           x-model="setorNominalDisplay" 
                                           @input="onSetorInput($event)"
                                           required 
                                           placeholder="0" 
                                           class="pg-currency-input">
                                    <input type="hidden" name="jumlah" :value="setorNominal">
                                </div>
                            </div>
                        </div>

                        <!-- Quick Nominal Preset Chips -->
                        <div>
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Preset Nominal Cepat:</div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <button type="button" @click="addSetorNominal(20000)" class="quick-chip-btn">+20.000</button>
                                <button type="button" @click="addSetorNominal(50000)" class="quick-chip-btn">+50.000</button>
                                <button type="button" @click="addSetorNominal(100000)" class="quick-chip-btn">+100.000</button>
                                <button type="button" @click="addSetorNominal(200000)" class="quick-chip-btn">+200.000</button>
                                <button type="button" @click="resetSetorNominal()" class="quick-chip-btn text-rose-600 dark:text-rose-400">Reset</button>
                            </div>
                        </div>

                        <!-- 3. Keterangan Setoran -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Keterangan Setoran
                            </label>
                            <input type="text" 
                                   name="keterangan" 
                                   x-model="setorKeterangan"
                                   placeholder="Contoh: Setoran sukarela / tabungan rutin..." 
                                   class="form-input text-xs w-full"
                                   style="height:38px; border-radius:var(--rounded-md, 8px);">

                            <!-- Quick Reason Chips -->
                            <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                <span class="text-[10.5px] font-semibold text-slate-400">Pilihan Cepat:</span>
                                <button type="button" @click="setorKeterangan = 'Setoran Rutin'" class="quick-chip-btn text-[10.5px]">Setoran Rutin</button>
                                <button type="button" @click="setorKeterangan = 'Tabungan Hari Raya'" class="quick-chip-btn text-[10.5px]">Tabungan Hari Raya</button>
                                <button type="button" @click="setorKeterangan = 'Bonus / THR'" class="quick-chip-btn text-[10.5px]">Bonus / THR</button>
                                <button type="button" @click="setorKeterangan = 'Setoran Sukarela'" class="quick-chip-btn text-[10.5px]">Setoran Sukarela</button>
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" @click="closeModalSetor()" class="btn btn-secondary modal-btn-cancel-desktop">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary-forest" style="height:38px;">
                            <i data-lucide="check-circle-2"></i>
                            <span>Simpan Setoran</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- ========================================================================= -->
    <!-- 6. MODAL TARIK SIMPANAN (Teleported to Body)                               -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="modalTarikOpen" 
             x-cloak 
             class="modal-backdrop" 
             @click="closeModalTarik()" 
             @keydown.escape.window="closeModalTarik()">
            
            <div class="modal-box modal-box-md" style="max-width:500px;" @click.stop>
                <!-- Pull Handle for Mobile Bottom-Sheet -->
                <div class="modal-handle">
                    <div class="modal-handle-bar"></div>
                </div>

                <!-- Modal Header -->
                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(245,158,11,0.12);color:#d97706;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(245,158,11,0.25);">
                            <i data-lucide="arrow-up-right" style="width:18px;height:18px;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div class="modal-title">Penarikan Tabungan Karyawan</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Proses penarikan simpanan karyawan sesuai saldo yang tersedia</div>
                        </div>
                    </div>
                    <button type="button" @click="closeModalTarik()" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form action="<?= Router::url('/tabungan/tarik') ?>" method="POST" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                    <?= CSRF::field() ?>

                    <div class="modal-body custom-scrollbar" style="display:flex;flex-direction:column;gap:14px;">
                        
                        <!-- 1. Karyawan Selection (Searchable Dropdown) -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Pilih Karyawan <span style="color:#e11d48;">*</span>
                            </label>

                            <div class="relative" @click.outside="tarikDropdownOpen = false">
                                <button type="button"
                                        @click="tarikDropdownOpen = !tarikDropdownOpen"
                                        class="form-input flex items-center justify-between w-full text-left font-medium transition cursor-pointer"
                                        style="height:40px; border-radius:var(--rounded-md, 8px); background-color:var(--color-canvas, #ffffff); border:1px solid var(--color-hairline-strong, #cbd5e1); padding:0 12px;">
                                    <div class="flex items-center gap-2 min-w-0 pr-2">
                                        <div class="tabungan-avatar" style="width:22px; height:22px; font-size:9.5px; background:rgba(217,119,6,0.12); color:#b45309; border-color:rgba(217,119,6,0.25);" x-show="selectedTarikKid && currentTarikKaryawan">
                                            <span x-text="currentTarikKaryawan ? currentTarikKaryawan.initials : 'KR'"></span>
                                        </div>
                                        <span class="truncate text-xs font-bold" 
                                              :style="!selectedTarikKid ? 'color:var(--color-ink-mute); font-weight:500;' : 'color:var(--color-ink);'"
                                              x-text="selectedTarikKid && currentTarikKaryawan ? (currentTarikKaryawan.nama + ' (Saldo: ' + formatRupiah(currentTarikKaryawan.saldo) + ')') : '-- Pilih Karyawan --'">
                                        </span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="tarikDropdownOpen ? 'rotate-180' : ''"></i>
                                </button>

                                <input type="hidden" name="karyawan_id" :value="selectedTarikKid" required>

                                <!-- Dropdown Menu -->
                                <div x-show="tarikDropdownOpen" x-cloak
                                     class="dropdown-menu-searchable bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl"
                                     style="position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:1050; overflow:hidden;">
                                    
                                    <div style="padding:8px 10px; border-bottom:1px solid var(--color-hairline, #e2e8f0); background:var(--color-canvas-soft, #f8fafc);">
                                        <div style="position:relative; display:flex; align-items:center; width:100%;">
                                            <svg style="position:absolute; left:10px; width:14px; height:14px; color:#94a3b8; pointer-events:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                            </svg>
                                            <input type="text" 
                                                   x-model="tarikSearch" 
                                                   placeholder="Ketik nama atau divisi..." 
                                                   class="form-input"
                                                   style="height:34px; padding-left:32px; padding-right:10px; font-size:12px; border-radius:8px; width:100%; border:1px solid var(--color-hairline-strong, #cbd5e1); background:var(--color-canvas, #ffffff); color:var(--color-ink, #0f172a);">
                                        </div>
                                    </div>

                                    <div class="max-h-52 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60 custom-scrollbar">
                                        <template x-for="k in filteredTarikKaryawans" :key="k.id">
                                            <div @click="selectTarikKaryawan(k.id)"
                                                 class="searchable-option p-2.5 flex items-center justify-between gap-3 cursor-pointer transition-colors"
                                                 :class="String(k.id) === String(selectedTarikKid) ? 'is-selected' : ''">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <div class="tabungan-avatar" style="width:28px; height:28px; font-size:10px; background:rgba(217,119,6,0.12); color:#b45309; border-color:rgba(217,119,6,0.25);">
                                                        <span x-text="k.initials"></span>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate" x-text="k.nama"></div>
                                                        <div class="text-[11px] text-slate-400 capitalize truncate" x-text="k.posisi"></div>
                                                    </div>
                                                </div>
                                                <div class="text-right shrink-0">
                                                    <template x-if="Number(k.saldo || 0) > 0">
                                                        <span class="tabungan-saldo-badge">
                                                            <span x-text="formatRupiah(k.saldo)"></span>
                                                        </span>
                                                    </template>
                                                    <template x-if="Number(k.saldo || 0) <= 0">
                                                        <span class="tabungan-saldo-badge-empty">
                                                            Saldo Rp 0
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <div x-show="filteredTarikKaryawans.length === 0" style="padding:14px; text-align:center; font-size:11.5px; color:var(--color-ink-mute, #94a3b8);">
                                            Tidak ada karyawan yang cocok
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Saldo Maksimal Banner -->
                            <div x-show="selectedTarikKid && currentTarikKaryawan" x-cloak
                                 class="mt-2 p-2.5 rounded-lg flex items-center justify-between gap-2"
                                 :style="currentTarikKaryawan && currentTarikKaryawan.saldo > 0 ? 'background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);' : 'background:rgba(244,63,94,0.08);border:1px solid rgba(244,63,94,0.25);'">
                                <div class="flex items-center gap-1.5 text-xs" :class="currentTarikKaryawan && currentTarikKaryawan.saldo > 0 ? 'text-amber-900 dark:text-amber-300' : 'text-rose-900 dark:text-rose-300'">
                                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                                    <span x-show="currentTarikKaryawan && currentTarikKaryawan.saldo > 0">
                                        Maksimal Penarikan: <strong class="font-mono font-bold" x-text="formatRupiah(currentTarikKaryawan ? currentTarikKaryawan.saldo : 0)"></strong>
                                    </span>
                                    <span x-show="currentTarikKaryawan && currentTarikKaryawan.saldo <= 0">
                                        Saldo karyawan Rp 0 (tidak dapat ditarik)
                                    </span>
                                </div>
                                <button type="button" 
                                        x-show="currentTarikKaryawan && currentTarikKaryawan.saldo > 0"
                                        @click="setAllTarikSaldo()" 
                                        class="btn btn-ghost btn-xs text-[10.5px] font-bold py-1 px-2.5 rounded-md border"
                                        style="background:var(--color-canvas, #ffffff);color:#d97706;border-color:rgba(245,158,11,0.3);">
                                    Tarik Semua
                                </button>
                            </div>

                            <!-- Escrow Source Account Banner -->
                            <div class="mt-2 p-2.5 rounded-lg flex items-center justify-between gap-2 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs">
                                <div class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-amber-600"></i>
                                    <span>Pencairan dari Kas: <strong class="text-slate-900 dark:text-slate-100"><?= htmlspecialchars($escrowAccount['nama_akun'] ?? 'Kas Tabungan Karyawan') ?></strong></span>
                                </div>
                                <span class="font-mono font-bold text-slate-600 dark:text-slate-400 text-[11px]">Tersedia: <?= Format::rupiah((float)($escrowAccount['saldo_saat_ini'] ?? 0)) ?></span>
                            </div>

                            <!-- Overdraft Guard Warning -->
                            <div x-show="tarikNominal > <?= (float)($escrowAccount['saldo_saat_ini'] ?? 0) ?>" class="mt-2 p-2 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 text-[11px] text-rose-700 dark:text-rose-300 font-medium flex items-center gap-1.5">
                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-rose-600 shrink-0"></i>
                                <span>Nominal penarikan melebihi saldo kas fisik tabungan yang tersedia (<?= Format::rupiah((float)($escrowAccount['saldo_saat_ini'] ?? 0)) ?>).</span>
                            </div>
                        </div>

                        <!-- 2. Tanggal & Nominal Tarik Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label" style="display:block;margin-bottom:6px;">
                                    Tanggal Penarikan <span style="color:#e11d48;">*</span>
                                </label>
                                <input type="date" 
                                       name="tanggal" 
                                       x-model="tarikTanggal" 
                                       required 
                                       class="form-input text-xs font-medium w-full"
                                       style="height:38px; border-radius:var(--rounded-md, 8px);">
                            </div>

                            <div>
                                <label class="form-label" style="display:block;margin-bottom:6px;">
                                    Nominal Tarik (Rp) <span style="color:#e11d48;">*</span>
                                </label>
                                <div class="pg-currency-group is-amber">
                                    <span class="pg-currency-addon">Rp</span>
                                    <input type="text" 
                                           inputmode="numeric"
                                           x-model="tarikNominalDisplay" 
                                           @input="onTarikInput($event)"
                                           required 
                                           placeholder="0" 
                                           class="pg-currency-input">
                                    <input type="hidden" name="jumlah" :value="tarikNominal">
                                </div>
                            </div>
                        </div>

                        <!-- Quick Nominal Preset Chips -->
                        <div>
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Preset Cepat:</div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <button type="button" @click="addTarikNominal(50000)" class="quick-chip-btn">+50.000</button>
                                <button type="button" @click="addTarikNominal(100000)" class="quick-chip-btn">+100.000</button>
                                <button type="button" @click="addTarikNominal(200000)" class="quick-chip-btn">+200.000</button>
                                <button type="button" 
                                        x-show="currentTarikKaryawan && currentTarikKaryawan.saldo > 0"
                                        @click="setAllTarikSaldo()" 
                                        class="quick-chip-btn text-amber-700 dark:text-amber-400">
                                    Semua Saldo
                                </button>
                                <button type="button" @click="resetTarikNominal()" class="quick-chip-btn text-rose-600 dark:text-rose-400">Reset</button>
                            </div>
                        </div>

                        <!-- 3. Alasan / Keterangan Penarikan -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Alasan / Keterangan Penarikan
                            </label>
                            <input type="text" 
                                   name="keterangan" 
                                   x-model="tarikKeterangan"
                                   placeholder="Contoh: Kebutuhan keluarga / keperluan mendesak..." 
                                   class="form-input text-xs w-full"
                                   style="height:38px; border-radius:var(--rounded-md, 8px);">

                            <!-- Quick Reason Chips -->
                            <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                <span class="text-[10.5px] font-semibold text-slate-400">Pilihan Cepat:</span>
                                <button type="button" @click="tarikKeterangan = 'Kebutuhan Keluarga'" class="quick-chip-btn text-[10.5px]">Kebutuhan Keluarga</button>
                                <button type="button" @click="tarikKeterangan = 'Keperluan Mendesak'" class="quick-chip-btn text-[10.5px]">Keperluan Mendesak</button>
                                <button type="button" @click="tarikKeterangan = 'Hari Raya / Lebaran'" class="quick-chip-btn text-[10.5px]">Hari Raya</button>
                                <button type="button" @click="tarikKeterangan = 'Penarikan Rutin'" class="quick-chip-btn text-[10.5px]">Penarikan Rutin</button>
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" @click="closeModalTarik()" class="btn btn-secondary modal-btn-cancel-desktop">
                            Batal
                        </button>
                        <button type="submit" 
                                :disabled="!currentTarikKaryawan || currentTarikKaryawan.saldo <= 0"
                                class="btn" 
                                style="background:#d97706; color:#ffffff; height:38px; border-radius:8px;">
                            <i data-lucide="arrow-up-right"></i>
                            <span>Proses Penarikan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>

<!-- Alpine Script Application -->
<script>
function tabunganApp() {
    return {
        // Master Data
        karyawans: <?= json_encode($karyawanMapData, JSON_UNESCAPED_UNICODE) ?>,

        // Filter & Search State
        searchQuery: '',
        statusFilter: 'all', // 'all', 'saldo_positif', 'saldo_nol', 'borongan', 'bulanan'

        // Modal Setor State
        modalSetorOpen: false,
        selectedSetorKid: '',
        setorTanggal: '<?= date('Y-m-d') ?>',
        setorNominal: '',
        setorNominalDisplay: '',
        setorKeterangan: 'Setoran Rutin',
        setorDropdownOpen: false,
        setorSearch: '',

        // Modal Tarik State
        modalTarikOpen: false,
        selectedTarikKid: '',
        tarikTanggal: '<?= date('Y-m-d') ?>',
        tarikNominal: '',
        tarikNominalDisplay: '',
        tarikKeterangan: 'Kebutuhan Keluarga',
        tarikDropdownOpen: false,
        tarikSearch: '',

        // Getters
        get currentSetorKaryawan() {
            return (this.selectedSetorKid && this.karyawans[this.selectedSetorKid]) ? this.karyawans[this.selectedSetorKid] : null;
        },

        get currentTarikKaryawan() {
            return (this.selectedTarikKid && this.karyawans[this.selectedTarikKid]) ? this.karyawans[this.selectedTarikKid] : null;
        },

        get filteredSetorKaryawans() {
            const list = Object.values(this.karyawans);
            if (!this.setorSearch.trim()) return list;
            const q = this.setorSearch.toLowerCase();
            return list.filter(k => k.nama.toLowerCase().includes(q) || k.posisi.toLowerCase().includes(q));
        },

        get filteredTarikKaryawans() {
            const list = Object.values(this.karyawans);
            if (!this.tarikSearch.trim()) return list;
            const q = this.tarikSearch.toLowerCase();
            return list.filter(k => k.nama.toLowerCase().includes(q) || k.posisi.toLowerCase().includes(q));
        },

        // Modal Setor Actions
        openModalSetor(presetKid = '') {
            this.selectedSetorKid = presetKid || '';
            this.setorTanggal = '<?= date('Y-m-d') ?>';
            this.setorNominal = '';
            this.setorNominalDisplay = '';
            this.setorKeterangan = 'Setoran Rutin';
            this.setorSearch = '';
            this.setorDropdownOpen = false;
            this.modalSetorOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        closeModalSetor() {
            this.modalSetorOpen = false;
            this.setorDropdownOpen = false;
        },

        selectSetorKaryawan(id) {
            this.selectedSetorKid = id;
            this.setorDropdownOpen = false;
            this.setorSearch = '';
        },

        onSetorInput(event) {
            const raw = event.target.value.replace(/\D/g, '');
            if (!raw) {
                this.setorNominal = '';
                this.setorNominalDisplay = '';
                return;
            }
            const num = parseInt(raw, 10);
            this.setorNominal = num;
            this.setorNominalDisplay = num.toLocaleString('id-ID');
        },

        addSetorNominal(amount) {
            let current = parseInt(this.setorNominal, 10) || 0;
            this.setorNominal = current + amount;
            this.setorNominalDisplay = this.setorNominal.toLocaleString('id-ID');
        },

        resetSetorNominal() {
            this.setorNominal = '';
            this.setorNominalDisplay = '';
        },

        // Modal Tarik Actions
        openModalTarik(presetKid = '') {
            this.selectedTarikKid = presetKid || '';
            this.tarikTanggal = '<?= date('Y-m-d') ?>';
            this.tarikNominal = '';
            this.tarikNominalDisplay = '';
            this.tarikKeterangan = 'Kebutuhan Keluarga';
            this.tarikSearch = '';
            this.tarikDropdownOpen = false;
            this.modalTarikOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        closeModalTarik() {
            this.modalTarikOpen = false;
            this.tarikDropdownOpen = false;
        },

        selectTarikKaryawan(id) {
            this.selectedTarikKid = id;
            this.tarikDropdownOpen = false;
            this.tarikSearch = '';
        },

        onTarikInput(event) {
            const raw = event.target.value.replace(/\D/g, '');
            if (!raw) {
                this.tarikNominal = '';
                this.tarikNominalDisplay = '';
                return;
            }
            let num = parseInt(raw, 10);
            const maxSaldo = this.currentTarikKaryawan ? this.currentTarikKaryawan.saldo : 0;
            if (maxSaldo > 0 && num > maxSaldo) {
                num = maxSaldo;
            }
            this.tarikNominal = num;
            this.tarikNominalDisplay = num.toLocaleString('id-ID');
        },

        addTarikNominal(amount) {
            let current = parseInt(this.tarikNominal, 10) || 0;
            let maxSaldo = this.currentTarikKaryawan ? this.currentTarikKaryawan.saldo : 0;
            let target = current + amount;
            if (maxSaldo > 0 && target > maxSaldo) {
                target = maxSaldo;
            }
            this.tarikNominal = target;
            this.tarikNominalDisplay = target.toLocaleString('id-ID');
        },

        setAllTarikSaldo() {
            const maxSaldo = this.currentTarikKaryawan ? this.currentTarikKaryawan.saldo : 0;
            this.tarikNominal = maxSaldo;
            this.tarikNominalDisplay = maxSaldo > 0 ? maxSaldo.toLocaleString('id-ID') : '';
        },

        resetTarikNominal() {
            this.tarikNominal = '';
            this.tarikNominalDisplay = '';
        },

        // Helpers
        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        isRowVisible(searchKeywords, saldo, tipeGaji) {
            // 1. Status Filter Check
            if (this.statusFilter === 'saldo_positif' && saldo <= 0) return false;
            if (this.statusFilter === 'saldo_nol' && saldo > 0) return false;
            if (this.statusFilter === 'borongan' && tipeGaji !== 'borongan') return false;
            if (this.statusFilter === 'bulanan' && tipeGaji !== 'bulanan') return false;

            // 2. Search Query Check
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

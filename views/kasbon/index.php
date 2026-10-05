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

$countBorongan = 0;
$countBulanan = 0;
foreach ($kasbonList as $kb) {
    $tg = strtolower($kb['tipe_penggajian'] ?? '');
    if ($tg === 'borongan') $countBorongan++;
    if ($tg === 'bulanan') $countBulanan++;
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
@media (max-width: 639.98px) {
    .kasbon-stat-card {
        padding: 11px 12px;
        gap: 10px;
        border-radius: 12px;
    }
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
@media (max-width: 639.98px) {
    .kasbon-stat-icon {
        width: 36px;
        height: 36px;
        min-width: 36px;
        min-height: 36px;
        border-radius: 10px;
    }
    .kasbon-stat-icon svg {
        width: 18px;
        height: 18px;
    }
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
    flex-direction: column;
    gap: 10px;
}
@media (min-width: 640px) {
    .kasbon-filter-row {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
}

/* 2. Filter Status Tab Pills (Clean Enterprise ERP Style - Horizontally Scrollable) */
.pg-tab-scroll-wrap {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding-bottom: 2px;
}
.pg-tab-scroll-wrap::-webkit-scrollbar {
    display: none;
}
@media (min-width: 640px) {
    .pg-tab-scroll-wrap {
        width: auto;
        overflow-x: visible;
        padding-bottom: 0;
    }
}

.pg-tab-container {
    display: inline-flex;
    align-items: center;
    background: #f1f5f9;
    padding: 3.5px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    gap: 4px;
    min-width: max-content;
    box-sizing: border-box;
}
.dark .pg-tab-container {
    background: #0f172a;
    border-color: #334155;
}

.pg-filter-tab-btn {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    height: 33px;
    padding: 0 13px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 7px;
    color: #64748b;
    background: transparent;
    transition: all 0.15s ease;
    border: 1px solid transparent;
    cursor: pointer;
    user-select: none;
    -webkit-user-select: none;
    text-decoration: none;
    white-space: nowrap;
}
.pg-filter-tab-btn:hover {
    color: #0f172a;
    background: rgba(255, 255, 255, 0.6);
}
.dark .pg-filter-tab-btn:hover {
    color: #f8fafc;
    background: rgba(255, 255, 255, 0.05);
}
.pg-filter-tab-btn.is-active {
    background: #ffffff !important;
    color: #0f172a !important;
    font-weight: 700 !important;
    border-color: rgba(0, 0, 0, 0.06) !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04) !important;
}
.dark .pg-filter-tab-btn.is-active {
    background: #1e293b !important;
    color: #f8fafc !important;
    border-color: #334155 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
}

.pg-tab-icon {
    width: 13.5px;
    height: 13.5px;
    flex-shrink: 0;
    display: inline-block;
    color: inherit;
}

.pg-tab-counter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-family: var(--font-mono, monospace);
    font-weight: 700;
    padding: 0 6px;
    height: 18px;
    border-radius: 9999px;
    line-height: 1;
    background: #e2e8f0;
    color: #64748b;
    transition: all 0.15s ease;
    flex-shrink: 0;
}
.dark .pg-tab-counter {
    background: #334155;
    color: #94a3b8;
}
.pg-filter-tab-btn.is-active .pg-tab-counter {
    background: rgba(136, 19, 55, 0.1);
    color: #881337;
    font-weight: 800;
}
.dark .pg-filter-tab-btn.is-active .pg-tab-counter {
    background: rgba(251, 113, 133, 0.18);
    color: #fb7185;
}

/* 3. Search Box in Filter */
.kasbon-search-box {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}
@media (min-width: 640px) {
    .kasbon-search-box {
        width: 260px;
        max-width: 320px;
        flex: 1;
    }
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
    border-color: #881337 !important;
    box-shadow: 0 0 0 1px #881337 !important;
    outline: none !important;
}
.dark .kasbon-search-input {
    background: #1e293b;
    border-color: #475569;
    color: #f8fafc;
}
.dark .kasbon-search-input:focus {
    border-color: #fb7185 !important;
    box-shadow: 0 0 0 1px #fb7185 !important;
}

/* 4. Table Avatar Badge */
.kasbon-avatar {
    width: 32px;
    height: 32px;
    border-radius: 9999px;
    background: rgba(136, 19, 55, 0.1);
    color: #881337;
    border: 1px solid rgba(136, 19, 55, 0.22);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 11px;
    font-family: var(--font-mono, monospace);
    flex-shrink: 0;
    user-select: none;
    box-sizing: border-box;
}
.dark .kasbon-avatar {
    background: rgba(251, 113, 133, 0.14);
    color: #fb7185;
    border-color: rgba(251, 113, 133, 0.3);
}

/* Table Card Container */
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
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
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
    padding: 0 12px;
    font-size: 14px;
    font-weight: 700;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    font-variant-numeric: tabular-nums;
    font-feature-settings: "zero" 0 !important;
    -webkit-font-feature-settings: "zero" 0 !important;
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

/* Disable dotted zero so 0 renders as clean rounded 0 */
.font-mono,
[class*="font-mono"],
.pg-currency-input,
.pg-currency-addon {
    font-feature-settings: "zero" 0 !important;
    -webkit-font-feature-settings: "zero" 0 !important;
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
.quick-chip-btn.is-active {
    background: rgba(136, 19, 55, 0.08) !important;
    border-color: #881337 !important;
    color: #881337 !important;
    font-weight: 800 !important;
}
.dark .quick-chip-btn.is-active {
    background: rgba(251, 113, 133, 0.16) !important;
    border-color: #fb7185 !important;
    color: #fb7185 !important;
}
.quick-chip-btn.is-reset {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
}
.quick-chip-btn.is-reset:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #b91c1c;
}
.dark .quick-chip-btn.is-reset {
    background: rgba(239, 68, 68, 0.12);
    border-color: rgba(239, 68, 68, 0.3);
    color: #f87171;
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
    background-color: #9f1239 !important;
    box-shadow: 0 4px 10px rgba(136, 19, 55, 0.3);
}

/* =========================================================================
   RESPONSIVE SWITCHER: DESKTOP TABLE vs MOBILE CARDS
   ========================================================================= */
.pg-desktop-view {
    display: block !important;
}
.pg-mobile-view {
    display: none !important;
}
@media (max-width: 767.98px) {
    .pg-desktop-view {
        display: none !important;
    }
    .pg-mobile-view {
        display: block !important;
    }
}

/* Ensure Alpine x-show="false" and x-cloak are always honored */
[style*="display: none"],
[style*="display:none"],
[x-cloak] {
    display: none !important;
}

/* =========================================================================
   MOBILE CARD VIEW SPECIFICS
   ========================================================================= */
.pg-mobile-card {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 12px;
    padding: 13px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    display: flex;
    flex-direction: column;
    gap: 10px;
    transition: all 0.15s ease;
}
.dark .pg-mobile-card {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.pg-mobile-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 26px;
    height: 22px;
    padding: 0 5px;
    border-radius: 6px;
    font-family: var(--font-mono, monospace);
    font-size: 11px;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    flex-shrink: 0;
}
.dark .pg-mobile-num {
    background: #0f172a;
    color: #94a3b8;
    border-color: #334155;
}

.pg-mobile-mid-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding-top: 8px;
    border-top: 1px solid var(--color-hairline, #e2e8f0);
}
.dark .pg-mobile-mid-row {
    border-color: #334155;
}

.pg-mobile-date-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #475569;
}
.dark .pg-mobile-date-wrap {
    color: #94a3b8;
}
.pg-mobile-date-wrap svg {
    width: 15px;
    height: 15px;
    min-width: 15px;
    min-height: 15px;
    color: #64748b;
    flex-shrink: 0;
    display: block;
}
.dark .pg-mobile-date-wrap svg {
    color: #94a3b8;
}
.pg-mobile-date-text {
    font-weight: 700;
    color: #0f172a;
    font-size: 13px;
}
.dark .pg-mobile-date-text {
    color: #f1f5f9;
}

.pg-mobile-nominal {
    font-size: 15px;
    font-weight: 800;
    font-family: var(--font-mono, monospace);
    color: #881337;
    text-align: right;
    white-space: nowrap;
}
.dark .pg-mobile-nominal {
    color: #fb7185;
}

.pg-mobile-note {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 7px 11px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-size: 13px;
    color: #334155;
    line-height: 1.4;
}
.dark .pg-mobile-note {
    background: rgba(30, 41, 59, 0.5);
    border-color: #334155;
    color: #cbd5e1;
}
.pg-mobile-note svg {
    width: 15px;
    height: 15px;
    min-width: 15px;
    min-height: 15px;
    color: #64748b;
    flex-shrink: 0;
    display: block;
}
.dark .pg-mobile-note svg {
    color: #94a3b8;
}
.pg-mobile-note-text {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    word-break: break-word;
}
.dark .pg-mobile-note-text {
    color: #e2e8f0;
}

.pg-mobile-action-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-top: 12px;
    margin-top: 2px;
    border-top: 1px solid var(--color-hairline, #e2e8f0);
}
.dark .pg-mobile-action-row {
    border-color: #334155;
}

/* =========================================================================
   CASH ACCOUNT SELECTION BOX IN MODAL (NO CIRCLE RADIO, HARMONIOUS EMERALD)
   ========================================================================= */
.pg-kas-section {
    padding: 12px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.dark .pg-kas-section {
    background: rgba(15, 23, 42, 0.4);
    border-color: #334155;
}
.pg-kas-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.pg-kas-section-title {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
}
.dark .pg-kas-section-title {
    color: #f1f5f9;
}
.pg-kas-section-title svg {
    width: 15px;
    height: 15px;
    color: #881337;
    flex-shrink: 0;
}
.dark .pg-kas-section-title svg {
    color: #fb7185;
}

.pg-kas-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
    max-height: 200px;
    overflow-y: auto;
    padding: 4px;
}
@media (min-width: 640px) {
    .pg-kas-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.pg-kas-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
    text-align: left;
    width: 100%;
    outline: none;
    position: relative;
    box-sizing: border-box;
}
.pg-kas-card:hover:not(.is-disabled) {
    border-color: #cbd5e1;
    background: #f8fafc;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
}
.dark .pg-kas-card {
    background: #1e293b;
    border-color: #334155;
}
.dark .pg-kas-card:hover:not(.is-disabled) {
    border-color: #475569;
    background: #273549;
}

/* Selected State: Harmonious Emerald Green (Lembut & Elegan, Zero Red/Rose) */
.pg-kas-card.is-selected {
    border-color: #059669 !important;
    background: #f0fdf4 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.22) !important;
}
.dark .pg-kas-card.is-selected {
    border-color: #10b981 !important;
    background: rgba(16, 185, 129, 0.12) !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25) !important;
}

/* Disabled State: Saldo Kurang */
.pg-kas-card.is-disabled {
    opacity: 0.55;
    cursor: not-allowed !important;
    border-style: dashed !important;
    background: #f1f5f9 !important;
}
.dark .pg-kas-card.is-disabled {
    background: #0f172a !important;
    border-color: #334155 !important;
}

/* Cash Icon Box */
.pg-kas-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.pg-kas-icon svg {
    width: 18px;
    height: 18px;
    stroke-width: 2;
    flex-shrink: 0;
}
.pg-kas-icon.is-tunai {
    background: #ecfdf5 !important;
    color: #059669 !important;
    border: 1px solid #a7f3d0 !important;
}
.dark .pg-kas-icon.is-tunai {
    background: rgba(16, 185, 129, 0.16) !important;
    color: #34d399 !important;
    border-color: rgba(16, 185, 129, 0.32) !important;
}
.pg-kas-icon.is-bank {
    background: #eff6ff !important;
    color: #2563eb !important;
    border: 1px solid #bfdbfe !important;
}
.dark .pg-kas-icon.is-bank {
    background: rgba(59, 130, 246, 0.16) !important;
    color: #60a5fa !important;
    border-color: rgba(59, 130, 246, 0.32) !important;
}

.pg-kas-pos-pill {
    padding: 1px 5px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 700;
    background: #d1fae5;
    color: #065f46;
    letter-spacing: 0.02em;
}
.dark .pg-kas-pos-pill {
    background: rgba(16, 185, 129, 0.25);
    color: #6ee7b7;
}

/* Alert & Validation Banners */
.pg-alert-warning {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 500;
    line-height: 1.35;
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #b45309;
}
.dark .pg-alert-warning {
    background: rgba(245, 158, 11, 0.12);
    border-color: rgba(245, 158, 11, 0.28);
    color: #fbbf24;
}
.pg-alert-danger {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 500;
    line-height: 1.35;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
}
.dark .pg-alert-danger {
    background: rgba(239, 68, 68, 0.12);
    border-color: rgba(239, 68, 68, 0.28);
    color: #f87171;
}

/* =========================================================================
   CLEAN ENTERPRISE EMPTY STATE CARD
   ========================================================================= */
.pg-empty-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 36px 20px;
    border-radius: 14px;
    background: var(--color-canvas, #ffffff);
    border: 1.5px dashed var(--color-hairline, #cbd5e1);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}
.dark .pg-empty-card {
    background: #1e293b;
    border-color: #334155;
}
.pg-empty-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px auto;
    background: rgba(136, 19, 55, 0.08);
    color: #881337;
    border: 1px solid rgba(136, 19, 55, 0.18);
}
.pg-empty-icon-box svg {
    width: 24px;
    height: 24px;
    display: block;
    stroke-width: 2;
}
.dark .pg-empty-icon-box {
    background: rgba(251, 113, 133, 0.12);
    color: #fb7185;
    border-color: rgba(251, 113, 133, 0.25);
}
.pg-empty-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--color-ink, #0f172a);
    margin-bottom: 4px;
}
.dark .pg-empty-title {
    color: #f1f5f9;
}
.pg-empty-desc {
    font-size: 12px;
    color: var(--color-ink-mute, #64748b);
    max-width: 280px;
    margin: 0 auto;
    line-height: 1.5;
}
.dark .pg-empty-desc {
    color: #94a3b8;
}
.pg-btn-reset-filter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    height: 34px;
    padding: 0 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    background: var(--color-canvas, #ffffff);
    color: #881337;
    border: 1px solid rgba(136, 19, 55, 0.25);
    cursor: pointer;
    margin-top: 14px;
    transition: all 0.15s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}
.pg-btn-reset-filter svg {
    width: 14px;
    height: 14px;
    display: block;
    stroke-width: 2;
    flex-shrink: 0;
}
.pg-btn-reset-filter:hover {
    background: rgba(136, 19, 55, 0.06);
    border-color: #881337;
}
.dark .pg-btn-reset-filter {
    background: #0f172a;
    color: #fb7185;
    border-color: rgba(251, 113, 133, 0.35);
}
.dark .pg-btn-reset-filter:hover {
    background: rgba(251, 113, 133, 0.14);
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
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
        <!-- Card 1: Kasbon Aktif -->
        <div class="kasbon-stat-card">
            <div class="kasbon-stat-icon" style="background:rgba(99, 102, 241, 0.1); color:#4f46e5; border:1px solid rgba(99, 102, 241, 0.25);">
                <i data-lucide="hand-coins"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider leading-snug line-clamp-2">Kasbon Aktif</div>
                <div class="text-sm sm:text-base lg:text-lg font-bold font-mono text-indigo-600 dark:text-indigo-400 mt-0.5 truncate">
                    <?= Format::rupiah($totalSisaAktif) ?>
                </div>
                <div class="text-[10.5px] sm:text-[11px] text-slate-400 mt-0.5 leading-snug truncate sm:whitespace-normal">
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
                <div class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider leading-snug line-clamp-2">Kasbon Lunas</div>
                <div class="text-sm sm:text-base lg:text-lg font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-0.5 truncate">
                    <?= Format::rupiah($totalNominalLunas) ?>
                </div>
                <div class="text-[10.5px] sm:text-[11px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 leading-snug truncate sm:whitespace-normal">
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
                <div class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider leading-snug line-clamp-2">Total Terbayar</div>
                <div class="text-sm sm:text-base lg:text-lg font-bold font-mono text-cyan-700 dark:text-cyan-300 mt-0.5 truncate">
                    <?= Format::rupiah($totalCicilanTerbayar ?? 0) ?>
                </div>
                <div class="text-[10.5px] sm:text-[11px] text-slate-400 mt-0.5 leading-snug truncate sm:whitespace-normal">
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
                <div class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider leading-snug line-clamp-2">Pinjaman Bulan Ini</div>
                <div class="text-sm sm:text-base lg:text-lg font-bold font-mono text-slate-900 dark:text-slate-100 mt-0.5 truncate">
                    <?= Format::rupiah($pinjamanBulanIni) ?>
                </div>
                <div class="text-[10.5px] sm:text-[11px] text-slate-400 mt-0.5 leading-snug truncate sm:whitespace-normal">
                    Bulan <?= date('m/Y') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. FILTER & SEARCH DOCK -->
    <div class="kasbon-filter-dock">
        <div class="kasbon-filter-row">
            <!-- Filter Tabs (Clean Enterprise ERP Style - Horizontally Scrollable) -->
            <div class="pg-tab-scroll-wrap">
                <div class="pg-tab-container">
                    <button type="button" 
                            @click="statusFilter = 'all'" 
                            class="pg-filter-tab-btn" 
                            :class="statusFilter === 'all' ? 'is-active' : ''">
                        <span>Semua</span>
                        <span class="pg-tab-counter"><?= count($kasbonList) ?></span>
                    </button>
                    <button type="button" 
                            @click="statusFilter = 'aktif'" 
                            class="pg-filter-tab-btn" 
                            :class="statusFilter === 'aktif' ? 'is-active' : ''">
                        <i data-lucide="clock-3" class="pg-tab-icon"></i>
                        <span>Aktif</span>
                        <span class="pg-tab-counter"><?= $countAktif ?></span>
                    </button>
                    <button type="button" 
                            @click="statusFilter = 'lunas'" 
                            class="pg-filter-tab-btn" 
                            :class="statusFilter === 'lunas' ? 'is-active' : ''">
                        <i data-lucide="check" class="pg-tab-icon"></i>
                        <span>Lunas</span>
                        <span class="pg-tab-counter"><?= $countLunas ?></span>
                    </button>
                    <button type="button" 
                            @click="statusFilter = 'borongan'" 
                            class="pg-filter-tab-btn" 
                            :class="statusFilter === 'borongan' ? 'is-active' : ''">
                        <span>Borongan</span>
                        <span class="pg-tab-counter"><?= $countBorongan ?></span>
                    </button>
                    <button type="button" 
                            @click="statusFilter = 'bulanan'" 
                            class="pg-filter-tab-btn" 
                            :class="statusFilter === 'bulanan' ? 'is-active' : ''">
                        <span>Bulanan</span>
                        <span class="pg-tab-counter"><?= $countBulanan ?></span>
                    </button>
                </div>
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
                        style="position:absolute; right:8px; color:#94a3b8; padding:2px; background:transparent; border:none; cursor:pointer;" 
                        title="Hapus pencarian">
                    <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- 4. DATA TABLE KASBON -->
    <div class="kasbon-table-card">
        <!-- Canonical Desktop Table View -->
        <div class="pg-desktop-view table-wrapper overflow-x-auto custom-scrollbar">
            <table class="data-table w-full text-left border-collapse text-xs" style="min-width: 980px;">
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
                        <tr class="empty-row border-0">
                            <td colspan="9" class="empty-state-cell border-0 p-6">
                                <div class="pg-empty-card max-w-md mx-auto">
                                    <div class="pg-empty-icon-box">
                                        <i data-lucide="hand-coins"></i>
                                    </div>
                                    <div class="pg-empty-title">Belum Ada Catatan Kasbon</div>
                                    <div class="pg-empty-desc">Belum ada catatan pinjaman kasbon karyawan yang terdaftar dalam sistem.</div>
                                    <button type="button" @click="openModalTambah()" class="pg-btn-reset-filter" style="background:#881337; color:#ffffff; border-color:#700f2d;">
                                        <i data-lucide="plus"></i>
                                        <span>Tambah Kasbon Baru</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <!-- Client-side No Results Row -->
                        <tr x-show="visibleCount === 0" x-cloak class="empty-row border-0">
                            <td colspan="9" class="empty-state-cell border-0 p-6">
                                <div class="pg-empty-card max-w-md mx-auto">
                                    <div class="pg-empty-icon-box">
                                        <i data-lucide="search-x"></i>
                                    </div>
                                    <div class="pg-empty-title">Tidak Ada Kasbon Yang Cocok</div>
                                    <div class="pg-empty-desc">Tidak ada data kasbon yang sesuai dengan filter status atau kata kunci saat ini.</div>
                                    <button type="button" @click="searchQuery = ''; statusFilter = 'all'" class="pg-btn-reset-filter">
                                        <i data-lucide="rotate-ccw"></i>
                                        <span>Reset Filter & Pencarian</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php foreach ($kasbonList as $idx => $kb): 
                            $totalPinjaman = (float)$kb['total_pinjaman'];
                            $sisaPinjaman = (float)$kb['sisa_pinjaman'];
                            $terbayar = (float)$kb['total_terbayar'];
                            $persenLunas = ($totalPinjaman > 0) ? min(100, round(($terbayar / $totalPinjaman) * 100, 1)) : 100;
                            $isAktif = ($kb['status_kasbon'] === 'aktif');
                            $initials = getKasbonInitials($kb['nama_karyawan']);
                            $searchKeywords = strtolower($kb['nama_karyawan'] . ' ' . ($kb['posisi'] ?? '') . ' ' . ($kb['keterangan'] ?? '') . ' ' . ($kb['tipe_penggajian'] ?? ''));
                        ?>
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors"
                            x-show="isRowVisible('<?= htmlspecialchars($searchKeywords, ENT_QUOTES, 'UTF-8') ?>', '<?= $kb['status_kasbon'] ?>', '<?= $kb['tipe_penggajian'] ?? '' ?>')"
                            data-id="<?= htmlspecialchars($kb['id']) ?>">
                            
                            <!-- Col 1: No -->
                            <td class="py-3 px-4 text-center font-mono text-slate-400"><?= $idx + 1 ?></td>

                            <!-- Col 2: Tanggal Pengajuan -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 font-mono font-semibold text-slate-800 dark:text-slate-200">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                    <span><?= Format::tanggalIndo($kb['tanggal_pengajuan']) ?></span>
                                </div>
                            </td>

                            <!-- Col 3: Nama Karyawan -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="kasbon-avatar">
                                        <?= htmlspecialchars($initials) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 truncate"><?= htmlspecialchars($kb['nama_karyawan']) ?></div>
                                        <div class="text-[11px] text-slate-400 capitalize truncate"><?= htmlspecialchars($kb['posisi'] ?? '-') ?> &bull; <?= ucfirst($kb['tipe_penggajian'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Col 4: Total Pinjaman -->
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                                <?= Format::rupiah($totalPinjaman) ?>
                            </td>

                            <!-- Col 5: Cicilan / Periode -->
                            <td class="py-3 px-4 text-right font-mono">
                                <?php if ($kb['potongan_per_periode'] > 0): ?>
                                    <span class="font-bold text-slate-700 dark:text-slate-300"><?= Format::rupiah((float)$kb['potongan_per_periode']) ?></span>
                                    <span class="text-[10px] text-slate-400 block font-sans">/periode</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10.5px] font-medium text-slate-400 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">Manual</span>
                                <?php endif; ?>
                            </td>

                            <!-- Col 6: Sisa Pinjaman & Progress -->
                            <td class="py-3 px-4">
                                <div class="flex items-center justify-between text-xs font-mono mb-1.5">
                                    <span class="font-bold <?= $isAktif ? 'text-amber-700 dark:text-amber-400' : 'text-slate-400' ?>"><?= Format::rupiah($sisaPinjaman) ?></span>
                                    <span class="text-[11px] font-bold <?= $persenLunas >= 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-300' ?>"><?= $persenLunas ?>% Terlunasi</span>
                                </div>
                                <div class="kasbon-progress-track">
                                    <div class="kasbon-progress-fill <?= $persenLunas >= 100 ? 'is-lunas' : 'is-aktif' ?>" 
                                         style="width: <?= max(0, min(100, $persenLunas)) ?>%;"></div>
                                </div>
                            </td>

                            <!-- Col 7: Keterangan -->
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-300 leading-relaxed text-xs">
                                <?php if (!empty($kb['keterangan'])): ?>
                                    <?= htmlspecialchars($kb['keterangan']) ?>
                                <?php else: ?>
                                    <span class="text-slate-400">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Col 8: Status -->
                            <td class="py-3 px-4 text-center">
                                <?php if ($kb['status_kasbon'] === 'aktif'): ?>
                                    <span class="kasbon-badge-aktif">
                                        <i data-lucide="clock-3" style="width:12px;height:12px;flex-shrink:0;"></i>
                                        <span>Aktif</span>
                                    </span>
                                <?php else: ?>
                                    <span class="kasbon-badge-lunas">
                                        <i data-lucide="check" style="width:12px;height:12px;flex-shrink:0;"></i>
                                        <span>Lunas</span>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Col 9: Aksi -->
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center">
                                    <a href="<?= Router::url('/kasbon/detail?id=' . $kb['id']) ?>" 
                                       class="btn btn-secondary btn-sm text-xs px-2.5 py-1.5 flex items-center justify-center gap-1.5" 
                                       title="Lihat Rincian & Riwayat Cicilan">
                                        <i data-lucide="eye" style="width:13.5px;height:13.5px;" class="text-slate-500"></i>
                                        <span>Detail</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Clean Modern Mobile Card View -->
        <div class="pg-mobile-view p-3 sm:p-4 space-y-3 bg-slate-50/60 dark:bg-slate-900/40">
            <?php if (empty($kasbonList)): ?>
                <!-- Initial Empty State on Mobile -->
                <div class="pg-empty-card">
                    <div class="pg-empty-icon-box">
                        <i data-lucide="hand-coins"></i>
                    </div>
                    <div class="pg-empty-title">Belum Ada Catatan Kasbon</div>
                    <div class="pg-empty-desc">Belum ada catatan pinjaman kasbon karyawan yang terdaftar dalam sistem.</div>
                    <button type="button" 
                            @click="openModalTambah()" 
                            class="pg-btn-reset-filter"
                            style="background:#881337; color:#ffffff; border-color:#700f2d;">
                        <i data-lucide="plus"></i>
                        <span>Tambah Kasbon Baru</span>
                    </button>
                </div>
            <?php else: ?>
                <!-- Client-side No Search Results State on Mobile -->
                <div x-show="visibleCount === 0" x-cloak class="pg-empty-card">
                    <div class="pg-empty-icon-box">
                        <i data-lucide="search-x"></i>
                    </div>
                    <div class="pg-empty-title">Tidak Ada Kasbon Yang Cocok</div>
                    <div class="pg-empty-desc">Tidak ada data kasbon yang sesuai dengan filter atau kata kunci saat ini.</div>
                    <button type="button" 
                            @click="searchQuery = ''; statusFilter = 'all'" 
                            class="pg-btn-reset-filter">
                        <i data-lucide="rotate-ccw"></i>
                        <span>Reset Filter & Pencarian</span>
                    </button>
                </div>

                <?php foreach ($kasbonList as $idx => $kb): 
                    $totalPinjaman = (float)$kb['total_pinjaman'];
                    $sisaPinjaman = (float)$kb['sisa_pinjaman'];
                    $terbayar = (float)$kb['total_terbayar'];
                    $persenLunas = ($totalPinjaman > 0) ? min(100, round(($terbayar / $totalPinjaman) * 100, 1)) : 100;
                    $isAktif = ($kb['status_kasbon'] === 'aktif');
                    $initials = getKasbonInitials($kb['nama_karyawan']);
                    $searchKeywords = strtolower($kb['nama_karyawan'] . ' ' . ($kb['posisi'] ?? '') . ' ' . ($kb['keterangan'] ?? '') . ' ' . ($kb['tipe_penggajian'] ?? ''));
                ?>
                <div class="pg-mobile-card"
                     x-show="isRowVisible('<?= htmlspecialchars($searchKeywords, ENT_QUOTES, 'UTF-8') ?>', '<?= $kb['status_kasbon'] ?>', '<?= $kb['tipe_penggajian'] ?? '' ?>')"
                     data-id="<?= htmlspecialchars($kb['id']) ?>">

                    <!-- Top: Nomor Urut + Avatar + Name + Status Badge -->
                    <div class="flex items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2 min-w-0">
                            <!-- Nomor Urut Badge -->
                            <span class="pg-mobile-num">#<?= $idx + 1 ?></span>

                            <div class="kasbon-avatar shrink-0">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-sm truncate"><?= htmlspecialchars($kb['nama_karyawan']) ?></div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate"><?= htmlspecialchars($kb['posisi'] ?? '-') ?> &bull; <?= ucfirst($kb['tipe_penggajian'] ?? '') ?></div>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <?php if ($isAktif): ?>
                                <span class="kasbon-badge-aktif">
                                    <i data-lucide="clock-3" style="width:12px;height:12px;flex-shrink:0;"></i>
                                    <span>Aktif</span>
                                </span>
                            <?php else: ?>
                                <span class="kasbon-badge-lunas">
                                    <i data-lucide="check" style="width:12px;height:12px;flex-shrink:0;"></i>
                                    <span>Lunas</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Middle: Date & Nominal Row -->
                    <div class="pg-mobile-mid-row">
                        <div class="pg-mobile-date-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <span class="pg-mobile-date-text"><?= Format::tanggalIndo($kb['tanggal_pengajuan']) ?></span>
                        </div>
                        <div class="pg-mobile-nominal">
                            <?= Format::rupiah($totalPinjaman) ?>
                        </div>
                    </div>

                    <!-- Sub Row: Cicilan & Sisa Progress Box -->
                    <div style="background:var(--color-canvas-soft, #f8fafc); border:1px solid var(--color-hairline, #e2e8f0); border-radius:10px; padding:10px 12px;" class="dark:bg-slate-800/50 dark:border-slate-700">
                        <div class="flex items-center justify-between text-xs mb-1.5 font-mono">
                            <span class="text-slate-500 font-sans text-[11px] font-semibold">Cicilan:</span>
                            <span class="font-bold text-slate-700 dark:text-slate-200">
                                <?= ($kb['potongan_per_periode'] > 0) ? Format::rupiah((float)$kb['potongan_per_periode']) . ' <span class="text-slate-400 font-sans text-[10px]">/periode</span>' : '<span class="text-slate-400 font-sans">Manual</span>' ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs font-mono mb-1.5 pt-1.5 border-t border-slate-200/60 dark:border-slate-700/60">
                            <span class="text-slate-500 font-sans text-[11px] font-semibold">Sisa:</span>
                            <div class="flex items-center gap-2">
                                <span class="font-bold <?= $isAktif ? 'text-amber-700 dark:text-amber-400' : 'text-slate-400' ?>"><?= Format::rupiah($sisaPinjaman) ?></span>
                                <span class="text-[10.5px] font-bold <?= $persenLunas >= 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-300' ?>">(<?= $persenLunas ?>%)</span>
                            </div>
                        </div>
                        <div class="kasbon-progress-track" style="margin-top:6px;">
                            <div class="kasbon-progress-fill <?= $persenLunas >= 100 ? 'is-lunas' : 'is-aktif' ?>" 
                                 style="width: <?= max(0, min(100, $persenLunas)) ?>%;"></div>
                        </div>
                    </div>

                    <!-- Catatan / Keterangan (if any) -->
                    <?php if (!empty($kb['keterangan'])): ?>
                        <div class="pg-mobile-note">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
                            <span class="pg-mobile-note-text"><?= htmlspecialchars($kb['keterangan']) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Action Row (Separated with clean 12px top spacing) -->
                    <div class="pg-mobile-action-row">
                        <a href="<?= Router::url('/kasbon/detail?id=' . $kb['id']) ?>" 
                           class="btn btn-secondary flex-1"
                           style="height:36px; display:inline-flex; align-items:center; justify-content:center; gap:6px; font-size:12px; font-weight:600; border-radius:8px;">
                            <i data-lucide="eye" style="width:14px;height:14px;"></i>
                            <span>Detail Pinjaman</span>
                        </a>
                    </div>

                </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
                <form action="<?= Router::url('/kasbon/store') ?>" method="POST" @submit="return handleFormSubmit($event)" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
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

                        <!-- 4. Pilihan Sumber Akun Kas -->
                        <div class="pg-kas-section">
                            <div class="pg-kas-section-header">
                                <label class="pg-kas-section-title">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                                    <span>Sumber Kas Pencairan Pinjaman</span>
                                    <span style="color:#e11d48;">*</span>
                                </label>
                            </div>

                            <div class="space-y-2">
                                <input type="hidden" name="akun_kas_id" :value="selectedKasId" required>
                                
                                <div class="pg-kas-grid custom-scrollbar">
                                    <template x-for="acc in (cashAccounts || [])" :key="acc.id">
                                        <button type="button" 
                                                @click.prevent.stop="selectKas(acc.id)"
                                                :disabled="totalPinjamanRaw > 0 && acc.saldo < totalPinjamanRaw"
                                                :class="{
                                                    'is-selected': String(selectedKasId) === String(acc.id),
                                                    'is-disabled': (totalPinjamanRaw > 0 && acc.saldo < totalPinjamanRaw)
                                                }"
                                                class="pg-kas-card">
                                            <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">
                                                <div class="pg-kas-icon" :class="acc.tipe_akun === 'kas_tunai' ? 'is-tunai' : 'is-bank'">
                                                    <template x-if="acc.tipe_akun === 'kas_tunai'">
                                                        <svg width="18" height="18" style="width:18px;height:18px;min-width:18px;min-height:18px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                                    </template>
                                                    <template x-if="acc.tipe_akun !== 'kas_tunai'">
                                                        <svg width="18" height="18" style="width:18px;height:18px;min-width:18px;min-height:18px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                                    </template>
                                                </div>
                                                <div style="min-width:0;flex:1;">
                                                    <div style="display:flex;align-items:center;gap:5px;">
                                                        <span style="font-size:12px;font-weight:700;color:var(--color-ink,#0f172a);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="acc.nama_akun"></span>
                                                        <template x-if="acc.is_default_pos">
                                                            <span class="pg-kas-pos-pill">POS</span>
                                                        </template>
                                                    </div>
                                                    <div style="font-size:11px;color:var(--color-ink-mute,#64748b);margin-top:2px;">
                                                        <span>Saldo:</span>
                                                        <strong :style="(totalPinjamanRaw > 0 && acc.saldo < totalPinjamanRaw) ? 'color:#dc2626;font-weight:700;' : 'color:var(--color-ink,#0f172a);font-weight:600;'" 
                                                                class="font-mono ml-0.5" 
                                                                x-text="formatRupiah(acc.saldo)">
                                                        </strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </button>
                                    </template>
                                </div>

                                <template x-if="!selectedKasId">
                                    <div class="pg-alert-warning" style="margin-top:6px;">
                                        <svg width="15" height="15" style="width:15px;height:15px;min-width:15px;min-height:15px;color:#d97706;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        <span>Wajib memilih salah satu akun kas untuk pencairan dana pinjaman.</span>
                                    </div>
                                </template>
                                <template x-if="selectedAccount && totalPinjamanRaw > 0 && selectedAccount.saldo < totalPinjamanRaw">
                                    <div class="pg-alert-danger" style="margin-top:6px;">
                                        <svg width="15" height="15" style="width:15px;height:15px;min-width:15px;min-height:15px;color:#dc2626;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        <span>Saldo akun kas terpilih (<strong x-text="formatRupiah(selectedAccount.saldo)"></strong>) tidak mencukupi nominal pinjaman.</span>
                                    </div>
                                </template>
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
        items: <?= json_encode(array_map(function($kb) {
            return [
                'id' => (string)$kb['id'],
                'keywords' => strtolower($kb['nama_karyawan'] . ' ' . ($kb['posisi'] ?? '') . ' ' . ($kb['keterangan'] ?? '') . ' ' . ($kb['tipe_penggajian'] ?? '')),
                'status' => $kb['status_kasbon'],
                'tipeGaji' => $kb['tipe_penggajian'] ?? ''
            ];
        }, $kasbonList ?? []), JSON_UNESCAPED_UNICODE) ?>,

        get visibleCount() {
            if (!this.items || this.items.length === 0) return 0;
            return this.items.filter(item => this.isRowVisible(item.keywords, item.status, item.tipeGaji)).length;
        },

        // Modal Form State
        modalTambahOpen: false,
        selectedKid: '',
        selectedKasId: '<?= !empty($akunKasList[0]['id']) ? (string)$akunKasList[0]['id'] : '' ?>',
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
            if (!this.selectedKasId) return null;
            return this.cashAccounts.find(a => String(a.id) === String(this.selectedKasId)) || null;
        },

        selectKas(id) {
            const strId = String(id || '');
            const acc = this.cashAccounts.find(a => String(a.id) === strId);
            if (acc && this.totalPinjamanRaw > 0 && acc.saldo < this.totalPinjamanRaw) {
                if (window.toast) window.toast('Saldo akun ' + acc.nama_akun + ' tidak mencukupi nominal pinjaman.', 'warning');
                return;
            }
            this.selectedKasId = strId;
        },

        handleFormSubmit(e) {
            if (!this.selectedKid) {
                if (window.toast) window.toast('Pilih karyawan terlebih dahulu.', 'warning');
                e.preventDefault();
                return false;
            }
            if (!this.totalPinjamanRaw || this.totalPinjamanRaw <= 0) {
                if (window.toast) window.toast('Total pinjaman harus lebih dari Rp 0.', 'warning');
                e.preventDefault();
                return false;
            }
            if (!this.selectedKasId) {
                if (window.toast) window.toast('Silakan pilih salah satu sumber akun kas pencairan.', 'warning');
                e.preventDefault();
                return false;
            }
            if (this.selectedAccount && this.selectedAccount.saldo < this.totalPinjamanRaw) {
                if (window.toast) window.toast('Saldo akun kas terpilih tidak mencukupi total pinjaman.', 'error');
                e.preventDefault();
                return false;
            }
            return true;
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

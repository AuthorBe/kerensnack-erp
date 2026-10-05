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
@media (max-width: 639.98px) {
    .pg-stat-card {
        padding: 11px 12px;
        gap: 10px;
        border-radius: 12px;
    }
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
@media (max-width: 639.98px) {
    .pg-stat-icon {
        width: 36px;
        height: 36px;
        min-width: 36px;
        min-height: 36px;
        border-radius: 10px;
    }
    .pg-stat-icon svg {
        width: 18px;
        height: 18px;
    }
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

/* Segmented Control Filter Tabs (Clean Enterprise ERP Style - Horizontally Scrollable) */
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
   STATUS BADGES & ACTION BUTTONS (KEREN ONE DESIGN SYSTEM)
   ========================================================================= */
.pg-badge-unprocessed {
    display: inline-flex;
    align-items: center;
    gap: 4.5px;
    padding: 2.5px 8.5px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.25;
    background-color: #fffbeb !important;
    color: #b45309 !important;
    border: 1px solid #fde68a !important;
    white-space: nowrap;
    user-select: none;
    box-shadow: 0 1px 2px rgba(217, 119, 6, 0.05);
}
.dark .pg-badge-unprocessed {
    background-color: rgba(245, 158, 11, 0.12) !important;
    color: #fbbf24 !important;
    border-color: rgba(245, 158, 11, 0.28) !important;
}
.pg-badge-unprocessed svg {
    width: 12px;
    height: 12px;
    color: #d97706 !important;
    stroke-width: 2.2;
    flex-shrink: 0;
}
.dark .pg-badge-unprocessed svg {
    color: #fbbf24 !important;
}

.pg-badge-locked {
    display: inline-flex;
    align-items: center;
    gap: 4.5px;
    padding: 2.5px 8.5px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    font-family: var(--font-mono, monospace);
    line-height: 1.25;
    background-color: #f1f5f9 !important;
    color: #475569 !important;
    border: 1px solid #cbd5e1 !important;
    white-space: nowrap;
    user-select: none;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}
.dark .pg-badge-locked {
    background-color: rgba(255, 255, 255, 0.06) !important;
    color: #94a3b8 !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
}
.pg-badge-locked svg {
    width: 12px;
    height: 12px;
    color: #64748b !important;
    stroke-width: 2.2;
    flex-shrink: 0;
}
.dark .pg-badge-locked svg {
    color: #94a3b8 !important;
}

/* Action Button Tool Group */
.pg-action-group {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}
.pg-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1px solid var(--color-hairline, #e2e8f0);
    background-color: var(--color-canvas, #ffffff);
    color: var(--color-ink-secondary, #64748b);
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
    outline: none;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}
.pg-action-btn svg {
    width: 14px;
    height: 14px;
    display: block;
    stroke-width: 2;
    flex-shrink: 0;
}
.pg-action-btn.is-edit:hover {
    color: #881337 !important;
    background-color: rgba(136, 19, 55, 0.08) !important;
    border-color: rgba(136, 19, 55, 0.25) !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(136, 19, 55, 0.12);
}
.pg-action-btn.is-delete:hover {
    color: #dc2626 !important;
    background-color: #fef2f2 !important;
    border-color: #fecaca !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(220, 38, 38, 0.12);
}
.dark .pg-action-btn {
    background-color: #1e293b;
    border-color: #334155;
    color: #94a3b8;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}
.dark .pg-action-btn.is-edit:hover {
    color: #fb7185 !important;
    background-color: rgba(251, 113, 133, 0.14) !important;
    border-color: rgba(251, 113, 133, 0.3) !important;
}
.dark .pg-action-btn.is-delete:hover {
    color: #f87171 !important;
    background-color: rgba(239, 68, 68, 0.14) !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
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

/* Mobile Card View Specifics */
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

/* Mobile Card Date & Info Row */
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
.pg-mobile-time-text {
    font-size: 12px;
    color: #64748b;
    font-family: var(--font-mono, monospace);
    font-weight: 600;
}
.dark .pg-mobile-time-text {
    color: #94a3b8;
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

/* Mobile Card Keterangan / Note Badge */
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

/* Mobile Card Actions & Locked Info */
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
.pg-mobile-action-row .btn {
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 8px;
}
.pg-btn-delete-mobile {
    background-color: #fff1f2 !important;
    color: #e11d48 !important;
    border: 1px solid #fecdd3 !important;
    padding: 0 14px;
    transition: all 0.15s ease;
}
.pg-btn-delete-mobile svg {
    color: #e11d48 !important;
}
.pg-btn-delete-mobile:hover {
    background-color: #ffe4e6 !important;
    border-color: #fda4af !important;
    color: #be123c !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(225, 29, 72, 0.12);
}
.dark .pg-btn-delete-mobile {
    background-color: rgba(244, 63, 94, 0.12) !important;
    color: #fb7185 !important;
    border-color: rgba(244, 63, 94, 0.28) !important;
}
.dark .pg-btn-delete-mobile svg {
    color: #fb7185 !important;
}
.dark .pg-btn-delete-mobile:hover {
    background-color: rgba(244, 63, 94, 0.2) !important;
    border-color: rgba(244, 63, 94, 0.45) !important;
    color: #fda4af !important;
}
.pg-mobile-card .btn svg {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
    display: block;
}
.pg-mobile-locked-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11.5px;
    color: #64748b;
    padding-top: 11px;
    margin-top: 2px;
    border-top: 1px solid var(--color-hairline, #e2e8f0);
    font-family: var(--font-mono, monospace);
}
.dark .pg-mobile-locked-row {
    border-color: #334155;
    color: #94a3b8;
}
.pg-mobile-locked-row svg {
    width: 13px;
    height: 13px;
    color: #64748b;
    flex-shrink: 0;
    display: block;
}

/* Clean Enterprise Empty State Card (Mobile & Desktop) */
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

/* =========================================================================
   POPUP MODAL: PENARIKAN GAJI DIALOG STYLES (IMMUNE TO CSS PURGE)
   ========================================================================= */

/* Ensure display:none always beats !important from app.css */
[style*="display: none"],
[style*="display:none"] {
    display: none !important;
}

/* Modal Body Scroll Container */
.pg-modal-scroll {
    max-height: calc(85vh - 125px);
    overflow-y: auto;
    overscroll-behavior: contain;
    padding-right: 4px;
}

/* 1. Karyawan Allowance Hint Banner */
.pg-allowance-hint {
    margin-top: 8px;
    padding: 10px 14px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: #fff1f2;
    border: 1px solid #fecdd3;
    box-shadow: 0 1px 2px rgba(136, 19, 55, 0.04);
}
.dark .pg-allowance-hint {
    background: rgba(251, 113, 133, 0.08);
    border-color: rgba(251, 113, 133, 0.22);
}
.pg-allowance-hint-content {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    font-size: 12px;
}
.pg-allowance-hint-icon {
    width: 16px;
    height: 16px;
    color: #881337;
    flex-shrink: 0;
}
.dark .pg-allowance-hint-icon {
    color: #fb7185;
}
.pg-allowance-hint-label {
    color: #64748b;
    font-size: 11.5px;
}
.dark .pg-allowance-hint-label {
    color: #94a3b8;
}
.pg-allowance-hint-val {
    font-weight: 700;
    color: #881337;
    font-family: var(--font-mono, monospace);
    font-size: 13px;
}
.dark .pg-allowance-hint-val {
    color: #fda4af;
}
.pg-allowance-hint-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 7px;
    background: #ffffff;
    color: #881337;
    border: 1px solid rgba(136, 19, 55, 0.25);
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
    transition: all 0.15s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.pg-allowance-hint-btn:hover {
    background: #881337;
    color: #ffffff;
    border-color: #881337;
    box-shadow: 0 2px 4px rgba(136, 19, 55, 0.2);
}
.dark .pg-allowance-hint-btn {
    background: #1e293b;
    color: #fb7185;
    border-color: rgba(251, 113, 133, 0.35);
}
.dark .pg-allowance-hint-btn:hover {
    background: #fb7185;
    color: #0f172a;
    border-color: #fb7185;
}

/* 2. Quick Preset Chips */
.pg-preset-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
}
.dark .pg-preset-label {
    color: #94a3b8;
}
.pg-preset-group {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}
.pg-preset-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 5px 10px;
    font-size: 11.5px;
    font-weight: 700;
    font-family: var(--font-mono, monospace);
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #1e293b;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}
.pg-preset-chip:hover {
    background: #e2e8f0;
    border-color: #94a3b8;
    color: #0f172a;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
}
.pg-preset-chip:active {
    transform: translateY(0);
}
.dark .pg-preset-chip {
    background: #1e293b;
    border-color: #334155;
    color: #cbd5e1;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}
.dark .pg-preset-chip:hover {
    background: #334155;
    border-color: #475569;
    color: #ffffff;
}
.pg-preset-chip.is-reset {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
    font-family: inherit;
    font-weight: 700;
}
.pg-preset-chip.is-reset:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #b91c1c;
}
.dark .pg-preset-chip.is-reset {
    background: rgba(239, 68, 68, 0.12);
    border-color: rgba(239, 68, 68, 0.3);
    color: #f87171;
}
.dark .pg-preset-chip.is-reset:hover {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.5);
    color: #fca5a5;
}

/* 3. Cash Account Selection Box */
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
}
.dark .pg-kas-section-title svg {
    color: #fb7185;
}
.pg-kas-section-hint {
    font-size: 11px;
    color: #64748b;
}
.dark .pg-kas-section-hint {
    color: #94a3b8;
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

/* Selected State (Harmonious Emerald Green, lembut & elegan) */
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

/* Disabled State (Saldo kurang) */
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

/* Cash Tag / POS Pill */
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

/* 4. Alert & Validation Banners */
.pg-alert-warning {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    line-height: 1.35;
    background: #fffbeb !important;
    border: 1px solid #fde68a !important;
    color: #b45309 !important;
}
.dark .pg-alert-warning {
    background: rgba(245, 158, 11, 0.12) !important;
    border-color: rgba(245, 158, 11, 0.28) !important;
    color: #fbbf24 !important;
}
.pg-alert-warning svg {
    width: 16px;
    height: 16px;
    color: #d97706 !important;
    flex-shrink: 0;
}
.dark .pg-alert-warning svg {
    color: #fbbf24 !important;
}

.pg-alert-danger {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    line-height: 1.35;
    background: #fef2f2 !important;
    border: 1px solid #fecaca !important;
    color: #b91c1c !important;
}
.dark .pg-alert-danger {
    background: rgba(239, 68, 68, 0.12) !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
    color: #f87171 !important;
}
.pg-alert-danger svg {
    width: 16px;
    height: 16px;
    color: #dc2626 !important;
    flex-shrink: 0;
}
.dark .pg-alert-danger svg {
    color: #f87171 !important;
}

/* 5. Submit CTA Button */
.pg-btn-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    height: 38px;
    padding: 0 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    background: #881337 !important;
    color: #ffffff !important;
    border: 1px solid #700f2d !important;
    box-shadow: 0 1px 2px rgba(136, 19, 55, 0.2);
    cursor: pointer;
    transition: all 0.15s ease;
}
.pg-btn-submit:hover:not(:disabled) {
    background: #9f1239 !important;
    box-shadow: 0 3px 8px rgba(136, 19, 55, 0.3);
}
.pg-btn-submit:disabled {
    opacity: 0.55 !important;
    cursor: not-allowed !important;
    background: #64748b !important;
    border-color: #475569 !important;
    color: #e2e8f0 !important;
    box-shadow: none !important;
    pointer-events: none;
}
</style>

<div x-data="penarikanGajiApp()" x-init="init()" class="space-y-4 pb-16 md:pb-6">

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
                    Pencatatan penarikan uang kehadiran harian &amp; kasbon harian karyawan bulanan langsung potong kas, termonitoring otomatis ke payroll
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
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
        <!-- Card 1: Total Penarikan -->
        <div class="pg-stat-card">
            <div class="pg-stat-icon" style="background:rgba(136, 19, 55, 0.1); color:#881337; border:1px solid rgba(136, 19, 55, 0.2);">
                <i data-lucide="badge-dollar-sign"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider leading-snug line-clamp-2">Total Penarikan Periode</div>
                <div class="text-sm sm:text-base lg:text-lg font-bold font-mono text-slate-900 dark:text-slate-100 mt-0.5 truncate">
                    <?= Format::rupiah($totalNominal) ?>
                </div>
                <div class="text-[10.5px] sm:text-[11px] text-slate-400 mt-0.5 leading-snug truncate sm:whitespace-normal">
                    <?= $countTotal ?> catatan (<?= $countEmployees ?> karyawan)
                </div>
            </div>
        </div>

        <!-- Card 2: Total Belum Masuk Payroll -->
        <div class="pg-stat-card">
            <div class="pg-stat-icon" style="background:rgba(2, 132, 199, 0.1); color:#0284c7; border:1px solid rgba(2, 132, 199, 0.25);">
                <i data-lucide="wallet"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] sm:text-[11px] font-bold text-sky-600 dark:text-sky-400 uppercase tracking-wider leading-snug line-clamp-2">Belum Di-Payroll</div>
                <div class="text-sm sm:text-base lg:text-lg font-bold font-mono text-sky-700 dark:text-sky-300 mt-0.5 truncate">
                    <?= Format::rupiah($totalBelumPayroll) ?>
                </div>
                <div class="text-[10.5px] sm:text-[11px] text-sky-600/80 dark:text-sky-400/80 mt-0.5 leading-snug truncate sm:whitespace-normal">
                    <?= $countBelumPayroll ?> transaksi siap rekonsiliasi
                </div>
            </div>
        </div>

        <!-- Card 3: Total Terkunci Payroll -->
        <div class="pg-stat-card">
            <div class="pg-stat-icon" style="background:rgba(16, 185, 129, 0.1); color:#10b981; border:1px solid rgba(16, 185, 129, 0.25);">
                <i data-lucide="shield-check"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] sm:text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider leading-snug line-clamp-2">Terkunci dalam Payroll</div>
                <div class="text-sm sm:text-base lg:text-lg font-bold font-mono text-emerald-700 dark:text-emerald-300 mt-0.5 truncate">
                    <?= Format::rupiah($totalLocked) ?>
                </div>
                <div class="text-[10.5px] sm:text-[11px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 leading-snug truncate sm:whitespace-normal">
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
                <div class="text-[10px] sm:text-[11px] font-bold text-sky-600 dark:text-sky-400 uppercase tracking-wider leading-snug line-clamp-2">Master Karyawan Bulanan</div>
                <div class="text-sm sm:text-base lg:text-lg font-bold font-mono text-slate-900 dark:text-slate-100 mt-0.5 truncate">
                    <?= count($karyawanBulanan) ?> <span class="text-xs font-normal text-slate-400">orang aktif</span>
                </div>
                <div class="text-[10.5px] sm:text-[11px] text-slate-400 mt-0.5 leading-snug truncate sm:whitespace-normal">
                    Tunjangan kehadiran harian
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
                    <option value="belum_payroll" <?= in_array($status, ['belum_payroll', 'pending', 'terbuka', 'aktif'], true) ? 'selected' : '' ?>>💵 Belum Masuk Payroll</option>
                    <option value="terkunci" <?= in_array($status, ['terkunci', 'locked', 'payroll'], true) ? 'selected' : '' ?>>🔒 Terkunci Payroll</option>
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
    <div class="card pg-table-card overflow-hidden border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-2xl shadow-xs">
        
        <!-- Table Toolbar Header -->
        <div class="p-3 sm:p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/70 dark:bg-slate-900/70">
            <!-- Left: Filter Pills & Counts (Responsive Horizontally Scrollable Bar) -->
            <div class="pg-tab-scroll-wrap">
                <div class="pg-tab-container">
                    <button type="button" 
                            @click="tableFilterStatus = 'all'" 
                            :class="tableFilterStatus === 'all' ? 'is-active' : ''"
                            class="pg-filter-tab-btn">
                        <span>Semua</span>
                        <span class="pg-tab-counter font-mono">
                            <?= count($penarikanList) ?>
                        </span>
                    </button>
                    <button type="button" 
                            @click="tableFilterStatus = 'unprocessed'" 
                            :class="tableFilterStatus === 'unprocessed' ? 'is-active' : ''"
                            class="pg-filter-tab-btn">
                        <i data-lucide="clock-3" class="pg-tab-icon"></i>
                        <span>Belum Payroll</span>
                        <span class="pg-tab-counter font-mono">
                            <?= $countBelumPayroll ?>
                        </span>
                    </button>
                    <button type="button" 
                            @click="tableFilterStatus = 'locked'" 
                            :class="tableFilterStatus === 'locked' ? 'is-active' : ''"
                            class="pg-filter-tab-btn">
                        <i data-lucide="lock" class="pg-tab-icon"></i>
                        <span>Terkunci</span>
                        <span class="pg-tab-counter font-mono">
                            <?= $countTerkunciPayroll ?? $countLocked ?>
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
                           placeholder="Cari karyawan atau catatan..." 
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

        <!-- ========================================================================= -->
        <!-- 4A. DESKTOP VIEW: FULL ENTERPRISE DATA TABLE (Pristine 7-Column Layout)   -->
        <!-- ========================================================================= -->
        <div class="pg-desktop-view table-wrapper overflow-x-auto custom-scrollbar">
            <table class="data-table w-full text-left border-collapse text-xs" style="min-width: 860px;">
                <thead>
                    <tr class="bg-slate-50/60 dark:bg-slate-800/40 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4 w-36">Tanggal</th>
                        <th class="py-3 px-4 min-w-[200px]">Karyawan</th>
                        <th class="py-3 px-4 text-right min-w-[140px]">Nominal Ambil</th>
                        <th class="py-3 px-4 min-w-[220px]">Keterangan</th>
                        <th class="py-3 px-4 text-center w-36">Status Payroll</th>
                        <th class="py-3 px-4 text-center w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($penarikanList)): ?>
                        <tr class="empty-row border-0">
                            <td colspan="7" class="empty-state-cell border-0 p-6">
                                <div class="pg-empty-card max-w-md mx-auto">
                                    <div class="pg-empty-icon-box">
                                        <i data-lucide="hand-coins"></i>
                                    </div>
                                    <div class="pg-empty-title">Belum Ada Catatan Penarikan Gaji</div>
                                    <div class="pg-empty-desc">Tidak ada riwayat penarikan uang harian untuk rentang tanggal yang dipilih.</div>
                                    <button type="button" 
                                            @click="openModalTambah()" 
                                            class="pg-btn-reset-filter"
                                            style="background:#881337; color:#ffffff; border-color:#700f2d;">
                                        <i data-lucide="plus"></i>
                                        <span>Catat Penarikan Baru</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <!-- Client-side No Search Results Row -->
                        <tr x-show="visibleCount === 0" x-cloak class="empty-row border-0">
                            <td colspan="7" class="empty-state-cell border-0 p-6">
                                <div class="pg-empty-card max-w-md mx-auto">
                                    <div class="pg-empty-icon-box">
                                        <i data-lucide="search-x"></i>
                                    </div>
                                    <div class="pg-empty-title">Tidak Ada Data Yang Cocok</div>
                                    <div class="pg-empty-desc">Tidak ada catatan yang sesuai dengan kata kunci pencarian atau filter status saat ini.</div>
                                    <button type="button" 
                                            @click="searchQuery = ''; tableFilterStatus = 'all'" 
                                            class="pg-btn-reset-filter">
                                        <i data-lucide="rotate-ccw"></i>
                                        <span>Reset Filter & Pencarian</span>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <?php foreach ($penarikanList as $idx => $pg): 
                            $isLocked = !empty($pg['penggajian_id']);
                            $initials = getInitials($pg['nama_karyawan']);
                            $searchKeywords = strtolower($pg['nama_karyawan'] . ' ' . ($pg['posisi'] ?? '') . ' ' . ($pg['keterangan'] ?? '') . ' ' . ($pg['nomor_payroll'] ?? '') . ' ' . $pg['tanggal']);
                        ?>
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors"
                            x-show="isRowVisible('<?= htmlspecialchars($searchKeywords, ENT_QUOTES, 'UTF-8') ?>', <?= $isLocked ? 'true' : 'false' ?>)"
                            data-id="<?= htmlspecialchars($pg['id']) ?>">
                            
                            <!-- Col 1: No -->
                            <td class="py-3 px-4 text-center font-mono text-slate-400"><?= $idx + 1 ?></td>
                            
                            <!-- Col 2: Tanggal -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                    <span class="font-mono font-semibold text-slate-800 dark:text-slate-200"><?= Format::tanggalIndo($pg['tanggal']) ?></span>
                                </div>
                            </td>

                            <!-- Col 3: Karyawan -->
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

                            <!-- Col 4: Nominal -->
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="text-sm font-bold font-mono text-rose-900 dark:text-rose-400">
                                    <?= Format::rupiah((float)$pg['nominal']) ?>
                                </div>
                            </td>

                            <!-- Col 5: Keterangan -->
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-300">
                                <div class="leading-relaxed">
                                    <?= htmlspecialchars($pg['keterangan'] ?: 'Ambil Uang Harian') ?>
                                </div>
                            </td>

                            <!-- Col 6: Status Payroll -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <?php if ($isLocked): ?>
                                    <span class="pg-badge-locked">
                                        <i data-lucide="lock"></i>
                                        <span><?= htmlspecialchars($pg['nomor_payroll'] ?: 'Terkunci') ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="pg-badge-unprocessed">
                                        <i data-lucide="clock-3"></i>
                                        <span>Belum Payroll</span>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Col 7: Aksi -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <?php if ($isLocked): ?>
                                    <span class="text-[11px] font-semibold text-slate-400 italic">Terkunci</span>
                                <?php else: ?>
                                    <div class="pg-action-group">
                                        <button type="button" 
                                                @click="openModalEdit({
                                                    id: '<?= htmlspecialchars($pg['id']) ?>',
                                                    karyawan_id: '<?= htmlspecialchars($pg['karyawan_id']) ?>',
                                                    akun_kas_id: '<?= htmlspecialchars($pg['akun_kas_id'] ?? '') ?>',
                                                    tanggal: '<?= htmlspecialchars($pg['tanggal']) ?>',
                                                    nominal: <?= (float)$pg['nominal'] ?>,
                                                    keterangan: '<?= htmlspecialchars($pg['keterangan'] ?? '', ENT_QUOTES, 'UTF-8') ?>'
                                                })"
                                                class="pg-action-btn is-edit" 
                                                title="Edit Catatan Penarikan">
                                            <i data-lucide="edit-3"></i>
                                        </button>
                                        <button type="button" 
                                                @click="confirmDelete('<?= htmlspecialchars($pg['id']) ?>', '<?= htmlspecialchars($pg['nama_karyawan'], ENT_QUOTES, 'UTF-8') ?>', <?= (float)$pg['nominal'] ?>)"
                                                class="pg-action-btn is-delete" 
                                                title="Hapus Catatan">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ========================================================================= -->
        <!-- 4B. MOBILE VIEW: CLEAN APP-STYLE LIST (High Density & Zero Clutter)       -->
        <!-- ========================================================================= -->
        <div class="pg-mobile-view p-3 sm:p-4 space-y-3 bg-slate-50/60 dark:bg-slate-900/40">
            <?php if (empty($penarikanList)): ?>
                <!-- Initial Empty State on Mobile -->
                <div class="pg-empty-card">
                    <div class="pg-empty-icon-box">
                        <i data-lucide="hand-coins"></i>
                    </div>
                    <div class="pg-empty-title">Belum Ada Catatan Penarikan Gaji</div>
                    <div class="pg-empty-desc">Tidak ada riwayat penarikan uang harian untuk rentang tanggal yang dipilih.</div>
                    <button type="button" 
                            @click="openModalTambah()" 
                            class="pg-btn-reset-filter"
                            style="background:#881337; color:#ffffff; border-color:#700f2d;">
                        <i data-lucide="plus"></i>
                        <span>Catat Penarikan Baru</span>
                    </button>
                </div>
            <?php else: ?>
                <!-- Client-side No Search Results State on Mobile -->
                <div x-show="visibleCount === 0" x-cloak class="pg-empty-card">
                    <div class="pg-empty-icon-box">
                        <i data-lucide="search-x"></i>
                    </div>
                    <div class="pg-empty-title">Tidak Ada Data Yang Cocok</div>
                    <div class="pg-empty-desc">Tidak ada catatan yang sesuai dengan kata kunci pencarian atau filter status saat ini.</div>
                    <button type="button" 
                            @click="searchQuery = ''; tableFilterStatus = 'all'" 
                            class="pg-btn-reset-filter">
                        <i data-lucide="rotate-ccw"></i>
                        <span>Reset Filter & Pencarian</span>
                    </button>
                </div>

                <?php foreach ($penarikanList as $idx => $pg): 
                    $isLocked = !empty($pg['penggajian_id']);
                    $initials = getInitials($pg['nama_karyawan']);
                    $searchKeywords = strtolower($pg['nama_karyawan'] . ' ' . ($pg['posisi'] ?? '') . ' ' . ($pg['keterangan'] ?? '') . ' ' . ($pg['nomor_payroll'] ?? '') . ' ' . $pg['tanggal']);
                ?>
                <div class="pg-mobile-card"
                     x-show="isRowVisible('<?= htmlspecialchars($searchKeywords, ENT_QUOTES, 'UTF-8') ?>', <?= $isLocked ? 'true' : 'false' ?>)"
                     data-id="<?= htmlspecialchars($pg['id']) ?>">

                    <!-- Top: Nomor Urut + Avatar + Name + Status Badge -->
                    <div class="flex items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2 min-w-0">
                            <!-- Nomor Urut Badge -->
                            <span class="pg-mobile-num">#<?= $idx + 1 ?></span>

                            <div class="pg-table-avatar shrink-0">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-sm truncate"><?= htmlspecialchars($pg['nama_karyawan']) ?></div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate"><?= htmlspecialchars($pg['posisi'] ?? 'Staff') ?></div>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <?php if ($isLocked): ?>
                                <span class="pg-badge-locked">
                                    <i data-lucide="lock"></i>
                                    <span><?= htmlspecialchars($pg['nomor_payroll'] ?: 'Terkunci') ?></span>
                                </span>
                            <?php else: ?>
                                <span class="pg-badge-unprocessed">
                                    <i data-lucide="clock-3"></i>
                                    <span>Belum Payroll</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Middle: Date & Nominal Row -->
                    <div class="pg-mobile-mid-row">
                        <div class="pg-mobile-date-wrap">
                            <i data-lucide="calendar"></i>
                            <span class="pg-mobile-date-text"><?= Format::tanggalIndo($pg['tanggal']) ?></span>
                            <span style="color:#94a3b8; font-size:11px;">&bull;</span>
                            <span class="pg-mobile-time-text"><?= date('H:i', strtotime($pg['dibuat_pada'] ?? $pg['tanggal'])) ?></span>
                        </div>
                        <div class="pg-mobile-nominal">
                            <?= Format::rupiah((float)$pg['nominal']) ?>
                        </div>
                    </div>

                    <!-- Catatan (if any) -->
                    <?php if (!empty($pg['keterangan'])): ?>
                        <div class="pg-mobile-note">
                            <i data-lucide="file-text"></i>
                            <span class="pg-mobile-note-text"><?= htmlspecialchars($pg['keterangan']) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Actions / Locked state -->
                    <?php if ($isLocked): ?>
                        <div class="pg-mobile-locked-row">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="lock"></i>
                                <span>Sudah dipotong payroll</span>
                            </span>
                            <span class="font-semibold text-slate-700 dark:text-slate-200"><?= htmlspecialchars($pg['nomor_payroll'] ?: 'Diproses') ?></span>
                        </div>
                    <?php else: ?>
                        <div class="pg-mobile-action-row">
                            <button type="button" 
                                    @click="openModalEdit({
                                        id: '<?= htmlspecialchars($pg['id']) ?>',
                                        karyawan_id: '<?= htmlspecialchars($pg['karyawan_id']) ?>',
                                        akun_kas_id: '<?= htmlspecialchars($pg['akun_kas_id'] ?? '') ?>',
                                        tanggal: '<?= htmlspecialchars($pg['tanggal']) ?>',
                                        nominal: <?= (float)$pg['nominal'] ?>,
                                        keterangan: '<?= htmlspecialchars($pg['keterangan'] ?? '', ENT_QUOTES, 'UTF-8') ?>'
                                    })"
                                    class="btn btn-secondary flex-1">
                                <i data-lucide="edit-3"></i>
                                <span>Edit Data</span>
                            </button>
                            <button type="button" 
                                    @click="confirmDelete('<?= htmlspecialchars($pg['id']) ?>', '<?= htmlspecialchars($pg['nama_karyawan'], ENT_QUOTES, 'UTF-8') ?>', <?= (float)$pg['nominal'] ?>)"
                                    class="btn pg-btn-delete-mobile">
                                <i data-lucide="trash-2"></i>
                                <span>Hapus</span>
                            </button>
                        </div>
                    <?php endif; ?>

                </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
            
            <div class="modal-box modal-box-md" style="max-width:540px; display:flex; flex-direction:column; max-height:calc(92vh - 20px);" @click.stop>
                
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
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;" x-text="isEditMode ? 'Sesuaikan data penarikan uang harian yang belum masuk payroll' : 'Khusus karyawan bulanan yang mengambil uang harian (langsung dicairkan)'">
                                Khusus karyawan bulanan yang mengambil uang harian (langsung dicairkan)
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
                      @submit="return handleFormSubmit($event)"
                      style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="id" :value="formId" x-show="isEditMode">

                    <!-- Modal Body with Scrollable Area -->
                    <div class="modal-body custom-scrollbar pg-modal-scroll" style="display:flex;flex-direction:column;gap:14px;">
                        
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

                            <!-- Selected Karyawan Allowance Hint Banner (Immune to CSS leak with template x-if) -->
                            <template x-if="selectedKid && currentKaryawan && currentKaryawan.uang_kehadiran > 0">
                                <div class="pg-allowance-hint">
                                    <div class="pg-allowance-hint-content">
                                        <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;min-height:16px;flex-shrink:0;" class="pg-allowance-hint-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m3.85 8.62 4.77-4.76a2 2 0 0 1 2.83 0l4.77 4.76a2 2 0 0 1 0 2.83l-4.77 4.77a2 2 0 0 1-2.83 0L3.85 11.45a2 2 0 0 1 0-2.83Z"/>
                                            <path d="m14.5 9.5 2.5-2.5"/>
                                            <path d="m7 15 2.5-2.5"/>
                                            <path d="M12 12h.01"/>
                                        </svg>
                                        <div class="min-w-0">
                                            <span class="pg-allowance-hint-label">Standar Harian:</span>
                                            <span class="pg-allowance-hint-val ml-1" x-text="formatRupiah(currentKaryawan ? currentKaryawan.uang_kehadiran : 0)"></span>
                                        </div>
                                    </div>
                                    <button type="button" @click="setNominalDefault()" class="pg-allowance-hint-btn">
                                        <svg style="width:12px;height:12px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                        <span>Gunakan Standar</span>
                                    </button>
                                </div>
                            </template>
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
                                    Nominal Penarikan (Rp) <span style="color:#e11d48;">*</span>
                                </label>
                                <div class="pg-currency-group">
                                    <span class="pg-currency-addon">Rp</span>
                                    <input type="text" 
                                           x-model="nominalDisplay" 
                                           @input="onNominalInput($event)"
                                           @focus="$event.target.select()"
                                           @click="$event.target.select()"
                                           required 
                                           placeholder="0" 
                                           class="pg-currency-input"
                                           autocomplete="off">
                                    <input type="hidden" name="nominal" :value="nominalRaw">
                                </div>
                            </div>
                        </div>

                        <!-- Quick Nominal Preset Chips -->
                        <div>
                            <div class="pg-preset-label">Preset Nominal Cepat:</div>
                            <div class="pg-preset-group">
                                <button type="button" @click="addNominal(10000)" class="pg-preset-chip">+10.000</button>
                                <button type="button" @click="addNominal(20000)" class="pg-preset-chip">+20.000</button>
                                <button type="button" @click="addNominal(50000)" class="pg-preset-chip">+50.000</button>
                                <button type="button" @click="addNominal(100000)" class="pg-preset-chip">+100.000</button>
                                <button type="button" @click="resetNominal()" class="pg-preset-chip is-reset">
                                    <svg width="12" height="12" style="width:12px;height:12px;min-width:12px;min-height:12px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                    <span>Reset</span>
                                </button>
                            </div>
                        </div>

                        <!-- 3. Pilihan Sumber Kas Pencairan Uang Harian -->
                        <div class="pg-kas-section">
                            <div class="pg-kas-section-header">
                                <div class="pg-kas-section-title">
                                    <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;min-height:16px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/>
                                        <path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>
                                    </svg>
                                    <span>Sumber Kas Pencairan</span>
                                    <span style="color:#e11d48;">*</span>
                                </div>
                                <span class="pg-kas-section-hint">Pilih laci kasir / kas</span>
                            </div>

                            <input type="hidden" name="akun_kas_id" :value="selectedKasId" required>

                            <div class="pg-kas-grid custom-scrollbar">
                                <template x-for="acc in (cashAccounts || [])" :key="acc.id">
                                    <button type="button" 
                                            @click.prevent.stop="selectKas(acc.id)"
                                            :disabled="nominalRaw > 0 && acc.saldo < nominalRaw"
                                            :class="{
                                                'is-selected': String(selectedKasId) === String(acc.id),
                                                'is-disabled': (nominalRaw > 0 && acc.saldo < nominalRaw)
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
                                                    <strong :style="(nominalRaw > 0 && acc.saldo < nominalRaw) ? 'color:#dc2626;font-weight:700;' : 'color:var(--color-ink,#0f172a);font-weight:600;'" 
                                                            class="font-mono ml-0.5" 
                                                            x-text="formatRupiah(acc.saldo)">
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                </template>
                            </div>

                            <!-- Feedback Notification Banners wrapped in template x-if so DOM is clean -->
                            <template x-if="!selectedKasId">
                                <div class="pg-alert-warning">
                                    <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;min-height:16px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                        <line x1="12" y1="9" x2="12" y2="13"/>
                                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                                    </svg>
                                    <span>Wajib memilih salah satu sumber kas di atas sebelum menyimpan.</span>
                                </div>
                            </template>

                            <template x-if="selectedKasId && selectedAccount && (nominalRaw > 0) && (selectedAccount.saldo < nominalRaw)">
                                <div class="pg-alert-danger">
                                    <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;min-height:16px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="12" y1="8" x2="12" y2="12"/>
                                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                                    </svg>
                                    <span>Saldo kas terpilih (<strong x-text="formatRupiah(selectedAccount.saldo)"></strong>) tidak mencukupi nominal penarikan (<strong x-text="formatRupiah(nominalRaw)"></strong>).</span>
                                </div>
                            </template>
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
                            <div style="display:flex;align-items:center;gap:6px;margin-top:8px;flex-wrap:wrap;">
                                <span style="font-size:10.5px;font-weight:600;color:var(--color-ink-mute,#94a3b8);">Pilihan Cepat:</span>
                                <button type="button" @click="formKeterangan = 'Ambil Uang Harian'" class="quick-chip-btn">Ambil Uang Harian</button>
                                <button type="button" @click="formKeterangan = 'Kasbon Harian'" class="quick-chip-btn">Kasbon Harian</button>
                                <button type="button" @click="formKeterangan = 'Transportasi Operasional'" class="quick-chip-btn">Transportasi</button>
                                <button type="button" @click="formKeterangan = 'Keperluan Mendesak'" class="quick-chip-btn">Keperluan Mendesak</button>
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer (Desktop Cancel Button + Submit Button) -->
                    <div class="modal-footer">
                        <button type="button" @click="closeModal()" class="btn btn-secondary modal-btn-cancel-desktop">
                            Batal
                        </button>
                        <button type="submit" 
                                :disabled="!isFormValid"
                                class="pg-btn-submit">
                            <i data-lucide="save" style="width:16px;height:16px;"></i>
                            <span x-text="isEditMode ? 'Simpan Perubahan' : 'Simpan &amp; Cairkan Langsung'">Simpan &amp; Cairkan Langsung</span>
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
        }, $activeCashAccounts ?? $akunKasList ?? []), JSON_UNESCAPED_UNICODE) ?>,
        
        // Modal State
        modalOpen: false,
        isEditMode: false,
        formId: '',
        selectedKid: '',
        selectedKasId: '',
        formTanggal: '<?= date('Y-m-d') ?>',
        nominalRaw: 0,
        nominalDisplay: '',
        formKeterangan: 'Ambil Uang Harian',
        karyawanDropdownOpen: false,
        karyawanSearch: '',

        // Table Filter & Search State
        searchQuery: '',
        tableFilterStatus: 'all', // 'all', 'unprocessed', 'locked'
        items: <?= json_encode(array_map(function($pg) {
            return [
                'id' => (string)$pg['id'],
                'keywords' => strtolower($pg['nama_karyawan'] . ' ' . ($pg['posisi'] ?? '') . ' ' . ($pg['keterangan'] ?? '') . ' ' . ($pg['nomor_payroll'] ?? '') . ' ' . $pg['tanggal']),
                'isLocked' => !empty($pg['penggajian_id'])
            ];
        }, $penarikanList ?? []), JSON_UNESCAPED_UNICODE) ?>,

        init() {
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            this.$watch('tableFilterStatus', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$watch('searchQuery', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
        },

        get visibleCount() {
            if (!this.items || this.items.length === 0) return 0;
            return this.items.filter(item => this.isRowVisible(item.keywords, item.isLocked)).length;
        },

        get selectedAccount() {
            if (!this.selectedKasId) return null;
            return this.cashAccounts.find(a => String(a.id) === String(this.selectedKasId)) || null;
        },

        get isFormValid() {
            if (!this.selectedKid) return false;
            if (!this.nominalRaw || this.nominalRaw <= 0) return false;
            if (!this.selectedKasId) return false;
            if (this.selectedAccount && this.selectedAccount.saldo < this.nominalRaw) return false;
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

        selectKas(id) {
            const strId = String(id || '');
            const acc = this.cashAccounts.find(a => String(a.id) === strId);
            if (acc && this.nominalRaw > 0 && acc.saldo < this.nominalRaw) {
                if (window.toast) window.toast('Saldo kas ' + acc.nama_akun + ' tidak mencukupi (' + this.formatRupiah(acc.saldo) + ').', 'warning');
                return; // Saldo tidak mencukupi, abaikan pemilihan
            }
            this.selectedKasId = strId;
        },

        openModalTambah() {
            this.isEditMode = false;
            this.formId = '';
            this.selectedKid = '';
            this.selectedKasId = '';
            this.formTanggal = '<?= date('Y-m-d') ?>';
            this.nominalRaw = 0;
            this.nominalDisplay = '';
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
            let targetKas = String(data.akun_kas_id || '');
            const exists = this.cashAccounts.some(a => String(a.id) === targetKas);
            this.selectedKasId = exists ? targetKas : (this.cashAccounts[0] ? String(this.cashAccounts[0].id) : '');
            this.formTanggal = data.tanggal || '<?= date('Y-m-d') ?>';
            let val = Math.round(Number(data.nominal) || 0);
            this.nominalRaw = val;
            this.nominalDisplay = val > 0 ? val.toLocaleString('id-ID') : '';
            this.formKeterangan = data.keterangan || 'Ambil Uang Harian';
            this.karyawanSearch = '';
            this.karyawanDropdownOpen = false;
            this.modalOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        handleFormSubmit(e) {
            if (!this.selectedKid) {
                if (window.toast) window.toast('Pilih karyawan bulanan terlebih dahulu.', 'warning');
                e.preventDefault();
                return false;
            }
            if (!this.nominalRaw || this.nominalRaw <= 0) {
                if (window.toast) window.toast('Nominal penarikan harus lebih dari Rp 0.', 'warning');
                e.preventDefault();
                return false;
            }
            if (!this.selectedKasId) {
                if (window.toast) window.toast('Silakan pilih salah satu sumber kas pencairan.', 'warning');
                e.preventDefault();
                return false;
            }
            if (this.selectedAccount && this.selectedAccount.saldo < this.nominalRaw) {
                if (window.toast) window.toast('Saldo kas terpilih tidak mencukupi nominal penarikan.', 'error');
                e.preventDefault();
                return false;
            }
            return true;
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
                let val = Math.round(Number(this.currentKaryawan.uang_kehadiran) || 0);
                this.nominalRaw = val;
                this.nominalDisplay = val > 0 ? val.toLocaleString('id-ID') : '';
            }
        },

        addNominal(amount) {
            let current = parseInt(this.nominalRaw, 10) || 0;
            let val = current + amount;
            this.nominalRaw = val;
            this.nominalDisplay = val > 0 ? val.toLocaleString('id-ID') : '';
        },

        resetNominal() {
            this.nominalRaw = 0;
            this.nominalDisplay = '';
        },

        onNominalInput(e) {
            let raw = String(e.target.value || '').replace(/\D/g, '');
            let val = parseInt(raw, 10) || 0;
            this.nominalRaw = val;
            this.nominalDisplay = val > 0 ? val.toLocaleString('id-ID') : '';
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        isRowVisible(searchKeywords, isLocked) {
            // Status Tab Check
            if ((this.tableFilterStatus === 'unprocessed' || this.tableFilterStatus === 'pending') && isLocked) return false;
            if (this.tableFilterStatus === 'locked' && !isLocked) return false;

            // Search Query Check
            if (!this.searchQuery.trim()) return true;
            const q = this.searchQuery.toLowerCase().trim();
            return searchKeywords.includes(q);
        },

        async confirmDelete(id, nama, nominal) {
            const ok = await window.AppConfirm({
                title: 'Hapus Catatan Penarikan',
                message: 'Apakah Anda yakin ingin menghapus catatan penarikan gaji sebesar ' + this.formatRupiah(nominal) + ' untuk ' + nama + '?',
                submessage: 'Data yang dihapus tidak dapat dipulihkan kembali.',
                type: 'danger',
                confirmText: 'Ya, Hapus',
                cancelText: 'Batal'
            });
            if (!ok) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= Router::url('/penarikan-gaji/delete') ?>';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= \App\Helpers\CSRF::token() ?>';
            form.appendChild(csrfInput);

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            form.appendChild(idInput);

            document.body.appendChild(form);
            form.submit();
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

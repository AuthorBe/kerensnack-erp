<?php
/**
 * views/tabungan/detail.php
 * Buku Tabungan & Buku Besar Mutasi Simpanan Karyawan Keren One ERP
 * 100% Selaras dengan DNA Desain, Responsive Desktop & Mobile, & Sistem Modal KEREN ONE
 */

use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CSRF;

ob_start();

if (!function_exists('getTabunganAvatarColor')) {
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
}

$currentSaldo = (float)($karyawan['saldo'] ?? 0);
$initials = '';
$words = preg_split('/\s+/', trim($karyawan['nama_karyawan'] ?? ''));
foreach ($words as $w) {
    if (!empty($w)) {
        $initials .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    if (mb_strlen($initials) >= 2) break;
}
$initials = $initials ?: 'KR';
$avatarTheme = getTabunganAvatarColor($karyawan['nama_karyawan'] ?? '');

$escrowAccount = null;
foreach ($akunKasList ?? [] as $acc) {
    if (!empty($acc['is_escrow'])) {
        $escrowAccount = $acc;
        break;
    }
}
$defaultKasId = !empty($escrowAccount['id']) ? (string)$escrowAccount['id'] : (!empty($akunKasList[0]['id']) ? (string)$akunKasList[0]['id'] : '');
?>

<style>
/* ==========================================================================
   Tabungan Detail DNA Styling & Responsive Utilities (Forest & Harmonies)
   ========================================================================== */

/* 0. KPI Stat Cards & Themes */
.tabungan-stat-card {
    background: #ffffff;
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 16px;
    padding: 15px 17px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
    transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.18s ease, border-color 0.18s ease;
    position: relative;
    overflow: hidden;
}
.tabungan-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.08);
}
.dark .tabungan-stat-card {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}
.dark .tabungan-stat-card:hover {
    border-color: #475569;
    box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.35);
}

/* Stat Icon Box */
.tabungan-stat-icon {
    width: 44px;
    height: 44px;
    min-width: 44px;
    min-height: 44px;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-sizing: border-box;
    transition: transform 0.18s ease;
}
.tabungan-stat-card:hover .tabungan-stat-icon {
    transform: scale(1.06);
}
.tabungan-stat-icon svg {
    width: 21px;
    height: 21px;
    stroke-width: 2.2px;
    display: block;
}

.tabungan-stat-icon.is-emerald {
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
    border: 1.5px solid rgba(16, 185, 129, 0.28);
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.12);
}
.dark .tabungan-stat-icon.is-emerald {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.4);
}

.tabungan-stat-icon.is-sky {
    background: rgba(2, 132, 199, 0.12);
    color: #0284c7;
    border: 1.5px solid rgba(2, 132, 199, 0.28);
    box-shadow: 0 2px 6px rgba(2, 132, 199, 0.12);
}
.dark .tabungan-stat-icon.is-sky {
    background: rgba(2, 132, 199, 0.2);
    color: #38bdf8;
    border-color: rgba(2, 132, 199, 0.4);
}

.tabungan-stat-icon.is-amber {
    background: rgba(217, 119, 6, 0.12);
    color: #d97706;
    border: 1.5px solid rgba(217, 119, 6, 0.28);
    box-shadow: 0 2px 6px rgba(217, 119, 6, 0.12);
}
.dark .tabungan-stat-icon.is-amber {
    background: rgba(217, 119, 6, 0.2);
    color: #fbbf24;
    border-color: rgba(217, 119, 6, 0.4);
}

/* Avatar Large for Profile Banner */
.tabungan-avatar-lg {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 15px;
    letter-spacing: -0.02em;
    flex-shrink: 0;
    user-select: none;
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

/* 2. Filter Status Tab Pills (Forest Emerald Theme) */
.tab-pill-group {
    display: inline-flex;
    align-items: center;
    background: var(--color-canvas-soft, #f8fafc);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 9px;
    padding: 3px;
    gap: 2px;
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

/* Mutation Badges */
.badge-mutasi-setor {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    color: #047857;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.3);
    white-space: nowrap;
}
.dark .badge-mutasi-setor {
    color: #34d399;
    background: rgba(16, 185, 129, 0.2);
    border-color: rgba(16, 185, 129, 0.45);
}

.badge-mutasi-tarik {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    color: #b45309;
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.3);
    white-space: nowrap;
}
.dark .badge-mutasi-tarik {
    color: #fbbf24;
    background: rgba(245, 158, 11, 0.2);
    border-color: rgba(245, 158, 11, 0.45);
}

.tabungan-nominal-setor {
    font-family: var(--font-mono, monospace);
    font-weight: 700;
    font-size: 13px;
    color: #047857;
    white-space: nowrap;
}
.dark .tabungan-nominal-setor {
    color: #34d399;
}

.tabungan-nominal-tarik {
    font-family: var(--font-mono, monospace);
    font-weight: 700;
    font-size: 13px;
    color: #b45309;
    white-space: nowrap;
}
.dark .tabungan-nominal-tarik {
    color: #fbbf24;
}

.badge-ref-payroll {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2.5px 7.5px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 700;
    font-family: var(--font-mono, monospace);
    color: #4338ca;
    background: rgba(79, 70, 229, 0.08);
    border: 1px solid rgba(79, 70, 229, 0.22);
    white-space: nowrap;
}
.dark .badge-ref-payroll {
    color: #a5b4fc;
    background: rgba(79, 70, 229, 0.16);
    border-color: rgba(79, 70, 229, 0.35);
}

/* Primary Button Forest Emerald (Solid Flat) */
.btn-primary-forest {
    background: #047857 !important;
    color: #ffffff !important;
    border: 1px solid #047857 !important;
    box-shadow: 0 1px 2px rgba(4, 120, 87, 0.2);
    transition: all 0.15s ease;
}
.btn-primary-forest:hover {
    background: #065f46 !important;
    border-color: #065f46 !important;
    color: #ffffff !important;
    box-shadow: 0 2px 5px rgba(4, 120, 87, 0.25);
}
.btn-primary-forest:active {
    background: #064e3b !important;
    border-color: #064e3b !important;
}

/* Secondary Button Warm Amber */
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
    box-shadow: 0 3px 8px rgba(217, 119, 6, 0.35);
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

/* Currency Group Input */
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
.pg-currency-group.is-amber:focus-within {
    border-color: #d97706 !important;
    box-shadow: 0 0 0 1px #d97706 !important;
}
.dark .pg-currency-group.is-amber:focus-within {
    border-color: #fbbf24 !important;
    box-shadow: 0 0 0 1px #fbbf24 !important;
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

/* =========================================================================
   CASH ACCOUNT SELECTION GRID IN MODAL
   ========================================================================= */
.pg-kas-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
    max-height: 180px;
    overflow-y: auto;
    padding: 2px;
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
    padding: 9px 12px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
    text-align: left;
    width: 100%;
    outline: none;
    box-sizing: border-box;
}
.pg-kas-card:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
    transform: translateY(-1px);
}
.dark .pg-kas-card {
    background: #1e293b;
    border-color: #334155;
}
.dark .pg-kas-card:hover {
    border-color: #475569;
    background: #273549;
}
.pg-kas-card.is-selected {
    border-color: #047857 !important;
    background: #f0fdf4 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.22) !important;
}
.dark .pg-kas-card.is-selected {
    border-color: #10b981 !important;
    background: rgba(16, 185, 129, 0.12) !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25) !important;
}
.pg-kas-card.is-selected-amber {
    border-color: #d97706 !important;
    background: #fffbeb !important;
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.22) !important;
}
.dark .pg-kas-card.is-selected-amber {
    border-color: #f59e0b !important;
    background: rgba(245, 158, 11, 0.12) !important;
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25) !important;
}
.pg-kas-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.pg-kas-icon.is-escrow {
    background: #f3e8ff !important;
    color: #7e22ce !important;
    border: 1px solid #d8b4fe !important;
}
.dark .pg-kas-icon.is-escrow {
    background: rgba(147, 51, 234, 0.16) !important;
    color: #c084fc !important;
    border-color: rgba(147, 51, 234, 0.32) !important;
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
}
.dark .pg-kas-pos-pill {
    background: rgba(16, 185, 129, 0.25);
    color: #6ee7b7;
}
.pg-kas-escrow-pill {
    padding: 1.5px 6.5px;
    border-radius: 9999px;
    font-size: 9.5px;
    font-weight: 700;
    letter-spacing: 0.02em;
    background: #ede9fe;
    color: #6d28d9;
    border: 1px solid rgba(109, 40, 217, 0.2);
}
.dark .pg-kas-escrow-pill {
    background: rgba(147, 51, 234, 0.25);
    color: #d8b4fe;
    border-color: rgba(147, 51, 234, 0.35);
}

/* Refined Auto-Lock Escrow Pill Badge (Red / Rose for Locked Context) */
.badge-locked-pill,
.badge-locked-pill-amber {
    display: inline-flex;
    align-items: center;
    gap: 4.5px;
    padding: 2.5px 8.5px 2.5px 7.5px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.2;
    letter-spacing: 0.01em;
    color: #e11d48;
    background: rgba(225, 29, 72, 0.08);
    border: 1px solid rgba(225, 29, 72, 0.28);
    white-space: nowrap;
    flex-shrink: 0;
    transition: all 0.15s ease;
}
.dark .badge-locked-pill,
.dark .badge-locked-pill-amber {
    color: #fb7185;
    background: rgba(225, 29, 72, 0.18);
    border-color: rgba(225, 29, 72, 0.38);
}

/* Alert Warning */
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
        display: flex !important;
        flex-direction: column;
        gap: 12px;
    }
}

/* Ensure Alpine x-show="false" and x-cloak are always honored */
[style*="display: none"],
[style*="display:none"],
[x-cloak] {
    display: none !important;
}

/* =========================================================================
   MOBILE CARD VIEW SPECIFICS (GOLDEN PATTERN)
   ========================================================================= */
.pg-mobile-card {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 14px;
    padding: 14px 15px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    display: flex;
    flex-direction: column;
    gap: 12px;
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
    min-width: 24px;
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
    gap: 12px;
    padding: 2px 0;
}

.pg-mobile-time-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 9px;
    border-radius: 6px;
    font-family: var(--font-mono, monospace);
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    white-space: nowrap;
}
.dark .pg-mobile-time-pill {
    background: #0f172a;
    color: #94a3b8;
    border-color: #334155;
}
.pg-mobile-time-pill svg {
    width: 12.5px;
    height: 12.5px;
    color: #94a3b8;
    flex-shrink: 0;
}

.pg-mobile-note {
    display: flex;
    align-items: flex-start;
    gap: 8.5px;
    padding: 8px 11px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-size: 12px;
    line-height: 1.45;
}
.dark .pg-mobile-note {
    background: rgba(30, 41, 59, 0.45);
    border-color: #334155;
}
.pg-mobile-note svg {
    width: 13.5px;
    height: 13.5px;
    color: #64748b;
    flex-shrink: 0;
    margin-top: 2px;
}
.dark .pg-mobile-note svg {
    color: #94a3b8;
}
.pg-mobile-note-text {
    font-size: 12px;
    font-weight: 500;
    color: #334155;
    word-break: break-word;
    flex: 1;
}
.dark .pg-mobile-note-text {
    color: #cbd5e1;
}

.pg-mobile-ref-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding-top: 8px;
    border-top: 1px solid var(--color-hairline, #f1f5f9);
}
.dark .pg-mobile-ref-row {
    border-color: rgba(51, 65, 85, 0.6);
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
    background: rgba(16, 185, 129, 0.08);
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
}
.dark .pg-empty-icon-box {
    background: rgba(16, 185, 129, 0.16);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.3);
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
    max-width: 320px;
    line-height: 1.45;
}
.pg-btn-reset-filter {
    margin-top: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #047857;
    background: rgba(16, 185, 129, 0.08);
    border: 1px solid rgba(16, 185, 129, 0.25);
    cursor: pointer;
    transition: all 0.15s ease;
}
.pg-btn-reset-filter:hover {
    background: rgba(16, 185, 129, 0.15);
}
.dark .pg-btn-reset-filter {
    color: #34d399;
    background: rgba(16, 185, 129, 0.16);
    border-color: rgba(16, 185, 129, 0.32);
}
</style>

<div x-data="bukuTabunganApp()" class="space-y-4">

    <!-- 1. PAGE HEADER (Pola Kanonikal KEREN ONE ERP) -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div class="flex items-center gap-3 min-w-0">
            <div class="tabungan-avatar-lg" style="background: <?= $avatarTheme['bg'] ?>; color: <?= $avatarTheme['text'] ?>; border: 1.5px solid <?= $avatarTheme['border'] ?>;">
                <?= htmlspecialchars($initials) ?>
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 truncate">
                        <?= htmlspecialchars($karyawan['nama_karyawan']) ?>
                    </h1>
                    <span class="<?= ($karyawan['tipe_penggajian'] ?? '') === 'borongan' ? 'badge-tipe-borongan' : 'badge-tipe-bulanan' ?>">
                        <?= ucfirst($karyawan['tipe_penggajian'] ?? 'Bulanan') ?>
                    </span>
                </div>
                <div class="text-xs text-slate-400 capitalize mt-0.5 truncate">
                    <?= htmlspecialchars($karyawan['posisi'] ?? '-') ?> &bull; Rekening Simpanan Pegawai
                </div>
            </div>
        </div>

        <div class="grid <?= $currentSaldo > 0 ? 'grid-cols-3' : 'grid-cols-2' ?> sm:flex items-center gap-2 w-full sm:w-auto justify-end">
            <a href="<?= Router::url('/tabungan') ?>" class="btn btn-secondary text-xs sm:text-sm font-bold flex items-center justify-center gap-1.5 w-full sm:w-auto" style="height:38px; border-radius:10px;">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali</span>
            </a>
            <button type="button" @click="openModalSetor()" class="btn btn-primary-forest text-xs sm:text-sm font-bold flex items-center justify-center gap-1.5 w-full sm:w-auto" style="height:38px; border-radius:10px;">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Setor</span>
            </button>
            <?php if ($currentSaldo > 0): ?>
            <button type="button" @click="openModalTarik()" class="btn btn-secondary-amber text-xs sm:text-sm font-bold flex items-center justify-center gap-1.5 w-full sm:w-auto" style="height:38px; border-radius:10px;">
                <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                <span>Tarik</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. INFORMASI SALDO & METRIK TABUNGAN -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        <!-- Card 1: Saldo Simpanan -->
        <div class="tabungan-stat-card">
            <div class="tabungan-stat-icon is-emerald">
                <i data-lucide="coins"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Saldo Simpanan Saat Ini</div>
                <div class="text-lg sm:text-xl font-bold font-mono text-emerald-700 dark:text-emerald-300 mt-0.5"><?= Format::rupiah($currentSaldo) ?></div>
            </div>
        </div>

        <!-- Card 2: Akumulasi Setoran Masuk -->
        <div class="tabungan-stat-card">
            <div class="tabungan-stat-icon is-sky">
                <i data-lucide="arrow-down-left"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Akumulasi Setoran Masuk</div>
                <div class="text-lg sm:text-xl font-bold font-mono text-sky-600 dark:text-sky-400 mt-0.5"><?= Format::rupiah($totalDeposit) ?></div>
            </div>
        </div>

        <!-- Card 3: Akumulasi Penarikan Keluar -->
        <div class="tabungan-stat-card">
            <div class="tabungan-stat-icon is-amber">
                <i data-lucide="arrow-up-right"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Akumulasi Penarikan Keluar</div>
                <div class="text-lg sm:text-xl font-bold font-mono text-amber-600 dark:text-amber-400 mt-0.5"><?= Format::rupiah($totalWithdrawal) ?></div>
            </div>
        </div>
    </div>

    <!-- 3. FILTER DOCK FOR MUTASI -->
    <div class="tabungan-filter-dock">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="tab-pill-group">
                <button type="button" 
                        @click="tipeFilter = 'all'" 
                        class="tab-pill-btn" 
                        :class="tipeFilter === 'all' ? 'is-active' : ''">
                    <span>Semua Mutasi</span>
                    <span class="tab-pill-counter"><?= count($transaksiList) ?></span>
                </button>
                <button type="button" 
                        @click="tipeFilter = 'deposit'" 
                        class="tab-pill-btn" 
                        :class="tipeFilter === 'deposit' ? 'is-active' : ''">
                    <span>Setoran</span>
                </button>
                <button type="button" 
                        @click="tipeFilter = 'withdrawal'" 
                        class="tab-pill-btn" 
                        :class="tipeFilter === 'withdrawal' ? 'is-active' : ''">
                    <span>Penarikan</span>
                </button>
            </div>

            <!-- Instant Search Box -->
            <div style="position:relative; display:flex; align-items:center; min-width:220px; max-width:320px; flex:1;">
                <svg style="position:absolute; left:11px; width:14px; height:14px; color:#94a3b8; pointer-events:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" 
                       x-model="searchMutasi" 
                       placeholder="Cari keterangan / no payroll..." 
                       class="form-input"
                       style="height:36px; padding-left:34px; padding-right:28px; font-size:12px; border-radius:8px; width:100%; border:1px solid var(--color-hairline-strong, #cbd5e1); background:var(--color-canvas, #ffffff); color:var(--color-ink, #0f172a);">
                <button type="button" 
                        x-show="searchMutasi.length > 0" 
                        @click="searchMutasi = ''" 
                        style="position:absolute; right:8px; color:#94a3b8; padding:2px;" 
                        title="Hapus pencarian">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 4. DATA PRESENTATION (Desktop Table & Mobile Cards) -->
    
    <!-- DESKTOP VIEW (>= 768px) -->
    <div class="pg-desktop-view">
        <div class="tabungan-table-card">
            <div class="table-wrapper">
                <table class="data-table w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4 w-32">Tanggal</th>
                            <th class="py-3.5 px-4 w-28 text-center">Tipe Mutasi</th>
                            <th class="py-3.5 px-4 w-28">Sumber</th>
                            <th class="py-3.5 px-4 min-w-[200px]">Keterangan</th>
                            <th class="py-3.5 px-4 min-w-[150px]">Referensi Payroll</th>
                            <th class="py-3.5 px-4 text-right min-w-[140px]">Nominal Mutasi</th>
                            <th class="py-3.5 px-4 w-36">Waktu Catat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <?php if (empty($transaksiList)): ?>
                            <tr class="border-0">
                                <td colspan="8" class="py-12 px-4 text-center border-0">
                                    <div class="pg-empty-card max-w-sm mx-auto">
                                        <div class="pg-empty-icon-box">
                                            <i data-lucide="book-open"></i>
                                        </div>
                                        <div class="pg-empty-title">Buku Tabungan Masih Kosong</div>
                                        <div class="pg-empty-desc">Belum ada riwayat mutasi setoran atau penarikan simpanan untuk karyawan ini.</div>
                                        <button type="button" @click="openModalSetor()" class="btn btn-primary-forest btn-sm text-xs font-bold mt-3 inline-flex items-center gap-1.5" style="border-radius:8px;">
                                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                            <span>Catat Setoran Pertama</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <!-- Client-side No Search Results State -->
                            <tr x-show="visibleMutasiCount === 0" x-cloak class="border-0">
                                <td colspan="8" class="py-12 px-4 text-center border-0">
                                    <div class="pg-empty-card max-w-sm mx-auto">
                                        <div class="pg-empty-icon-box">
                                            <i data-lucide="search-x"></i>
                                        </div>
                                        <div class="pg-empty-title">Tidak Ada Mutasi Yang Cocok</div>
                                        <div class="pg-empty-desc">Tidak ada riwayat mutasi yang cocok dengan filter atau kata kunci saat ini.</div>
                                        <button type="button" @click="searchMutasi = ''; tipeFilter = 'all'" class="pg-btn-reset-filter">
                                            <i data-lucide="rotate-ccw"></i>
                                            <span>Reset Filter & Pencarian</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <?php foreach ($transaksiList as $idx => $t): 
                                $isDeposit = ($t['tipe'] === 'deposit');
                                $searchKeywords = strtolower($t['keterangan'] . ' ' . ($t['nomor_payroll'] ?? '') . ' ' . $t['sumber'] . ' ' . $t['tanggal']);
                            ?>
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors"
                                x-show="isMutasiVisible('<?= htmlspecialchars($searchKeywords, ENT_QUOTES, 'UTF-8') ?>', '<?= $t['tipe'] ?>')">
                                
                                <!-- Col: No -->
                                <td class="py-3 px-4 text-center font-mono text-slate-400"><?= $idx + 1 ?></td>

                                <!-- Col: Tanggal -->
                                <td class="py-3 px-4 font-mono font-semibold text-slate-800 dark:text-slate-200">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                        <span><?= Format::tanggalIndo($t['tanggal']) ?></span>
                                    </div>
                                </td>

                                <!-- Col: Tipe Mutasi -->
                                <td class="py-3 px-4 text-center">
                                    <?php if ($isDeposit): ?>
                                        <span class="badge-mutasi-setor">
                                            <i data-lucide="arrow-down-left" class="w-3 h-3"></i> Setor
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-mutasi-tarik">
                                            <i data-lucide="arrow-up-right" class="w-3 h-3"></i> Tarik
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Col: Sumber -->
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-300 capitalize text-xs">
                                    <span class="badge badge-neutral text-[10px] py-0.5 px-2 font-mono">
                                        <?= htmlspecialchars($t['sumber'] ?? '-') ?>
                                    </span>
                                </td>

                                <!-- Col: Keterangan -->
                                <td class="py-3 px-4 text-slate-700 dark:text-slate-200">
                                    <div class="leading-relaxed font-medium">
                                        <?= htmlspecialchars($t['keterangan'] ?: '-') ?>
                                    </div>
                                </td>

                                <!-- Col: Referensi Payroll -->
                                <td class="py-3 px-4 font-mono text-slate-500">
                                    <?php if (!empty($t['nomor_payroll'])): ?>
                                        <span class="badge-ref-payroll">
                                            <i data-lucide="file-spreadsheet" class="w-3 h-3 text-indigo-500 shrink-0"></i>
                                            <span><?= htmlspecialchars($t['nomor_payroll']) ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Col: Nominal Mutasi -->
                                <td class="py-3 px-4 text-right">
                                    <?php if ($isDeposit): ?>
                                        <span class="tabungan-nominal-setor">
                                            + <?= Format::rupiah((float)$t['jumlah']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="tabungan-nominal-tarik">
                                            - <?= Format::rupiah((float)$t['jumlah']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Col: Waktu Catat -->
                                <td class="py-3 px-4 text-slate-400 font-mono text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="clock" class="w-3 h-3 text-slate-400 shrink-0"></i>
                                        <span><?= date('d/m/Y H:i', strtotime($t['dibuat_pada'])) ?></span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MOBILE CARD VIEW (< 768px) -->
    <div class="pg-mobile-view">
        <?php if (empty($transaksiList)): ?>
            <div class="pg-empty-card">
                <div class="pg-empty-icon-box">
                    <i data-lucide="book-open"></i>
                </div>
                <div class="pg-empty-title">Buku Tabungan Masih Kosong</div>
                <div class="pg-empty-desc">Belum ada riwayat mutasi setoran atau penarikan simpanan untuk karyawan ini.</div>
                <button type="button" @click="openModalSetor()" class="btn btn-primary-forest btn-sm text-xs font-bold mt-3 inline-flex items-center gap-1.5" style="border-radius:8px;">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Catat Setoran Pertama</span>
                </button>
            </div>
        <?php else: ?>
            <!-- Client-side No Search Results State Mobile -->
            <div x-show="visibleMutasiCount === 0" x-cloak class="pg-empty-card">
                <div class="pg-empty-icon-box">
                    <i data-lucide="search-x"></i>
                </div>
                <div class="pg-empty-title">Tidak Ada Mutasi Yang Cocok</div>
                <div class="pg-empty-desc">Tidak ada riwayat mutasi yang sesuai dengan pencarian saat ini.</div>
                <button type="button" @click="searchMutasi = ''; tipeFilter = 'all'" class="pg-btn-reset-filter">
                    <i data-lucide="rotate-ccw"></i>
                    <span>Reset Filter & Pencarian</span>
                </button>
            </div>

            <?php foreach ($transaksiList as $idx => $t): 
                $isDeposit = ($t['tipe'] === 'deposit');
                $searchKeywords = strtolower($t['keterangan'] . ' ' . ($t['nomor_payroll'] ?? '') . ' ' . $t['sumber'] . ' ' . $t['tanggal']);
                $timeFormatted = date('H:i', strtotime($t['dibuat_pada'])) . ' WIB';
                $fullTimeIso = date('d/m/Y H:i', strtotime($t['dibuat_pada']));
            ?>
            <div class="pg-mobile-card"
                 x-show="isMutasiVisible('<?= htmlspecialchars($searchKeywords, ENT_QUOTES, 'UTF-8') ?>', '<?= $t['tipe'] ?>')">
                
                <!-- Top Row: #No + Tanggal + Tipe Badge -->
                <div class="flex items-center justify-between gap-2.5 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="pg-mobile-num">#<?= $idx + 1 ?></span>
                        <div class="flex items-center gap-1.5 text-xs sm:text-[13px] font-bold text-slate-800 dark:text-slate-200 truncate font-mono">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                            <span><?= Format::tanggalIndo($t['tanggal']) ?></span>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <?php if ($isDeposit): ?>
                            <span class="badge-mutasi-setor">
                                <i data-lucide="arrow-down-left" class="w-3 h-3"></i> Setor
                            </span>
                        <?php else: ?>
                            <span class="badge-mutasi-tarik">
                                <i data-lucide="arrow-up-right" class="w-3 h-3"></i> Tarik
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mid Row: Nominal & Waktu Transaksi -->
                <div class="pg-mobile-mid-row">
                    <div class="min-w-0">
                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider mb-1">Nominal Mutasi</div>
                        <?php if ($isDeposit): ?>
                            <span class="tabungan-nominal-setor text-sm">
                                + <?= Format::rupiah((float)$t['jumlah']) ?>
                            </span>
                        <?php else: ?>
                            <span class="tabungan-nominal-tarik text-sm">
                                - <?= Format::rupiah((float)$t['jumlah']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider mb-1">Waktu Catat</div>
                        <span class="pg-mobile-time-pill" title="<?= $fullTimeIso ?>">
                            <i data-lucide="clock"></i>
                            <span><?= $timeFormatted ?></span>
                        </span>
                    </div>
                </div>

                <!-- Note Row -->
                <div class="pg-mobile-note">
                    <i data-lucide="file-text"></i>
                    <span class="pg-mobile-note-text">
                        <?= htmlspecialchars($t['keterangan'] ?: ($isDeposit ? 'Setoran simpanan' : 'Penarikan simpanan')) ?>
                    </span>
                </div>

                <!-- Sub Row: Sumber & Ref Payroll (jika ada) -->
                <div class="pg-mobile-ref-row">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Sumber:</span>
                        <span class="badge badge-neutral text-[10px] py-0.5 px-2 font-mono capitalize">
                            <?= htmlspecialchars($t['sumber'] ?? '-') ?>
                        </span>
                    </div>
                    <?php if (!empty($t['nomor_payroll'])): ?>
                    <span class="badge-ref-payroll">
                        <i data-lucide="file-spreadsheet" class="w-3 h-3 text-indigo-500 shrink-0"></i>
                        <span><?= htmlspecialchars($t['nomor_payroll']) ?></span>
                    </span>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. MODAL SETOR (Teleported to Body)                                       -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="modalSetorOpen" 
             x-cloak 
             class="modal-backdrop" 
             @click="closeModalSetor()" 
             @keydown.escape.window="closeModalSetor()">
            
            <div class="modal-box modal-box-md" style="max-width:480px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>

                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(16,185,129,0.12);color:#059669;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(16,185,129,0.25);">
                            <i data-lucide="plus-circle" style="width:18px;height:18px;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div class="modal-title">Setor Simpanan</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Karyawan: <?= htmlspecialchars($karyawan['nama_karyawan']) ?></div>
                        </div>
                    </div>
                    <button type="button" @click="closeModalSetor()" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <form method="POST" action="<?= Router::url('/tabungan/setor') ?>" @submit="handleSetorSubmit($event)" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="karyawan_id" value="<?= htmlspecialchars($karyawan['id']) ?>">

                    <div class="modal-body custom-scrollbar space-y-3.5">
                        
                        <div class="p-2.5 rounded-lg flex items-center justify-between gap-2"
                             style="background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.2);">
                            <div class="flex items-center gap-1.5 text-xs text-emerald-800 dark:text-emerald-300">
                                <i data-lucide="wallet" class="w-4 h-4 shrink-0 text-emerald-600"></i>
                                <span>Saldo Simpanan Saat Ini: <strong class="font-mono font-bold"><?= Format::rupiah($currentSaldo) ?></strong></span>
                            </div>
                        </div>

                        <!-- Rekening Kas Titipan Tabungan (Terkunci Otomatis ke Escrow) -->
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1.5" style="flex-wrap:nowrap;">
                                <label class="form-label text-xs font-bold text-slate-700 dark:text-slate-300" style="margin-bottom:0;white-space:nowrap;">
                                    Akun Kas Penampung <span style="color:#e11d48;">*</span>
                                </label>
                                <span class="badge-locked-pill">
                                    <i data-lucide="lock" style="width:11.5px;height:11.5px;stroke-width:2.2;"></i>
                                    <span>Terkunci Otomatis</span>
                                </span>
                            </div>

                            <input type="hidden" name="akun_kas_id" value="<?= htmlspecialchars($defaultKasId) ?>">

                            <div style="padding:10px 12px;border-radius:10px;border:1.5px solid rgba(16,185,129,0.3);background:rgba(16,185,129,0.06);">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                                    <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">
                                        <div style="width:36px;height:36px;border-radius:8px;background:rgba(16,185,129,0.15);color:#047857;border:1px solid rgba(16,185,129,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i data-lucide="shield-check" style="width:18px;height:18px;"></i>
                                        </div>
                                        <div style="min-width:0;flex:1;">
                                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                                <span style="font-size:12.5px;font-weight:700;color:var(--color-ink,#0f172a);white-space:nowrap;">
                                                    <?= htmlspecialchars(preg_replace('/\s*\(Terkunci\)/i', '', $escrowAccount['nama_akun'] ?? 'Kas Tabungan Karyawan')) ?>
                                                </span>
                                                <span class="pg-kas-escrow-pill">ESCROW</span>
                                            </div>
                                            <div style="font-size:11px;color:var(--color-ink-mute,#64748b);margin-top:1px;">
                                                Rekening titipan simpanan karyawan
                                            </div>
                                        </div>
                                    </div>
                                    <div style="text-align:right;flex-shrink:0;">
                                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute,#64748b);font-weight:600;">Saldo Kas Fisik</div>
                                        <div class="font-mono" style="font-size:12.5px;font-weight:700;color:#047857;">
                                            <?= Format::rupiah((float)($escrowAccount['saldo_saat_ini'] ?? 0)) ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="margin-top:8px;padding-top:7px;border-top:1px solid rgba(16,185,129,0.15);font-size:10.5px;color:var(--color-ink-secondary,#475569);display:flex;align-items:center;gap:5px;">
                                    <i data-lucide="info" style="width:13px;height:13px;flex-shrink:0;color:#059669;"></i>
                                    <span>Setoran wajib masuk ke rekening kas titipan khusus untuk memisahkan tabungan dari operasional usaha.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tanggal & Nominal Setor Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label" style="display:block;margin-bottom:6px;">
                                    Tanggal Setoran <span style="color:#e11d48;">*</span>
                                </label>
                                <input type="date" 
                                       name="tanggal" 
                                       value="<?= date('Y-m-d') ?>" 
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

                        <!-- Keterangan Setoran -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Keterangan Setoran
                            </label>
                            <input type="text" 
                                   name="keterangan" 
                                   x-model="setorKeterangan" 
                                   placeholder="Contoh: Setoran sukarela..." 
                                   class="form-input text-xs w-full"
                                   style="height:38px; border-radius:var(--rounded-md, 8px);">

                            <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                <span class="text-[10.5px] font-semibold text-slate-400">Pilihan Cepat:</span>
                                <button type="button" @click="setorKeterangan = 'Setoran Rutin'" class="quick-chip-btn text-[10.5px]">Setoran Rutin</button>
                                <button type="button" @click="setorKeterangan = 'Tabungan Hari Raya'" class="quick-chip-btn text-[10.5px]">Tabungan Hari Raya</button>
                                <button type="button" @click="setorKeterangan = 'Bonus / THR'" class="quick-chip-btn text-[10.5px]">Bonus / THR</button>
                            </div>
                        </div>

                    </div>

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
    <!-- 6. MODAL TARIK (Teleported to Body)                                       -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="modalTarikOpen" 
             x-cloak 
             class="modal-backdrop" 
             @click="closeModalTarik()" 
             @keydown.escape.window="closeModalTarik()">
            
            <div class="modal-box modal-box-md" style="max-width:480px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>

                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(245,158,11,0.12);color:#d97706;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(245,158,11,0.25);">
                            <i data-lucide="arrow-up-right" style="width:18px;height:18px;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div class="modal-title">Penarikan Simpanan</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Karyawan: <?= htmlspecialchars($karyawan['nama_karyawan']) ?></div>
                        </div>
                    </div>
                    <button type="button" @click="closeModalTarik()" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <form method="POST" action="<?= Router::url('/tabungan/tarik') ?>" @submit="handleTarikSubmit($event)" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="karyawan_id" value="<?= htmlspecialchars($karyawan['id']) ?>">

                    <div class="modal-body custom-scrollbar space-y-3.5">
                        
                        <div class="p-2.5 rounded-lg flex items-center justify-between gap-2"
                             style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);">
                            <div class="flex items-center gap-1.5 text-xs text-amber-900 dark:text-amber-300">
                                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-amber-600"></i>
                                <span>Maksimal Penarikan: <strong class="font-mono font-bold"><?= Format::rupiah($currentSaldo) ?></strong></span>
                            </div>
                            <button type="button" 
                                    @click="setAllTarikSaldo()" 
                                    class="btn btn-ghost btn-xs text-[10.5px] font-bold py-1 px-2.5 rounded-md border"
                                    style="background:var(--color-canvas, #ffffff);color:#d97706;border-color:rgba(245,158,11,0.3);">
                                Tarik Semua
                            </button>
                        </div>

                        <!-- Rekening Kas Sumber Pencairan (Terkunci Otomatis ke Escrow) -->
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1.5" style="flex-wrap:nowrap;">
                                <label class="form-label text-xs font-bold text-slate-700 dark:text-slate-300" style="margin-bottom:0;white-space:nowrap;">
                                    Akun Kas Sumber Dana <span style="color:#e11d48;">*</span>
                                </label>
                                <span class="badge-locked-pill-amber">
                                    <i data-lucide="lock" style="width:11.5px;height:11.5px;stroke-width:2.2;"></i>
                                    <span>Terkunci Otomatis</span>
                                </span>
                            </div>

                            <input type="hidden" name="akun_kas_id" value="<?= htmlspecialchars($defaultKasId) ?>">

                            <div style="padding:10px 12px;border-radius:10px;border:1.5px solid rgba(245,158,11,0.3);background:rgba(245,158,11,0.06);">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                                    <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">
                                        <div style="width:36px;height:36px;border-radius:8px;background:rgba(245,158,11,0.15);color:#b45309;border:1px solid rgba(245,158,11,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i data-lucide="shield-check" style="width:18px;height:18px;"></i>
                                        </div>
                                        <div style="min-width:0;flex:1;">
                                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                                <span style="font-size:12.5px;font-weight:700;color:var(--color-ink,#0f172a);white-space:nowrap;">
                                                    <?= htmlspecialchars(preg_replace('/\s*\(Terkunci\)/i', '', $escrowAccount['nama_akun'] ?? 'Kas Tabungan Karyawan')) ?>
                                                </span>
                                                <span class="pg-kas-escrow-pill">ESCROW</span>
                                            </div>
                                            <div style="font-size:11px;color:var(--color-ink-mute,#64748b);margin-top:1px;">
                                                Rekening titipan simpanan karyawan
                                            </div>
                                        </div>
                                    </div>
                                    <div style="text-align:right;flex-shrink:0;">
                                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute,#64748b);font-weight:600;">Saldo Kas Fisik</div>
                                        <div class="font-mono" style="font-size:12.5px;font-weight:700;color:#d97706;">
                                            <?= Format::rupiah((float)($escrowAccount['saldo_saat_ini'] ?? 0)) ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="margin-top:8px;padding-top:7px;border-top:1px solid rgba(245,158,11,0.15);font-size:10.5px;color:var(--color-ink-secondary,#475569);display:flex;align-items:center;gap:5px;">
                                    <i data-lucide="info" style="width:13px;height:13px;flex-shrink:0;color:#d97706;"></i>
                                    <span>Pencairan hanya dapat ditarik langsung dari rekening titipan simpanan karyawan.</span>
                                </div>
                            </div>

                            <!-- Overdraft Guard Warning for Escrow Cash Account -->
                            <div x-show="isKasTarikOverdraft" x-cloak class="pg-alert-warning mt-2">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 shrink-0"></i>
                                <span>Nominal penarikan melebihi saldo kas fisik yang tersedia pada rekening tabungan ini.</span>
                            </div>
                        </div>

                        <!-- Tanggal & Nominal Tarik Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label" style="display:block;margin-bottom:6px;">
                                    Tanggal Penarikan <span style="color:#e11d48;">*</span>
                                </label>
                                <input type="date" 
                                       name="tanggal" 
                                       value="<?= date('Y-m-d') ?>" 
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
                                <button type="button" @click="setAllTarikSaldo()" class="quick-chip-btn text-amber-700 dark:text-amber-400">Semua Saldo</button>
                                <button type="button" @click="resetTarikNominal()" class="quick-chip-btn text-rose-600 dark:text-rose-400">Reset</button>
                            </div>
                        </div>

                        <!-- Alasan / Keterangan Penarikan -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Alasan / Keterangan Penarikan
                            </label>
                            <input type="text" 
                                   name="keterangan" 
                                   x-model="tarikKeterangan" 
                                   placeholder="Contoh: Kebutuhan keluarga mendesak..." 
                                   class="form-input text-xs w-full"
                                   style="height:38px; border-radius:var(--rounded-md, 8px);">

                            <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                <span class="text-[10.5px] font-semibold text-slate-400">Pilihan Cepat:</span>
                                <button type="button" @click="tarikKeterangan = 'Kebutuhan Keluarga'" class="quick-chip-btn text-[10.5px]">Kebutuhan Keluarga</button>
                                <button type="button" @click="tarikKeterangan = 'Keperluan Mendesak'" class="quick-chip-btn text-[10.5px]">Keperluan Mendesak</button>
                                <button type="button" @click="tarikKeterangan = 'Hari Raya / Lebaran'" class="quick-chip-btn text-[10.5px]">Hari Raya</button>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="closeModalTarik()" class="btn btn-secondary modal-btn-cancel-desktop">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-secondary-amber font-bold" style="height:38px; border-radius:8px;">
                            <i data-lucide="arrow-up-right"></i>
                            <span>Proses Penarikan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>

<script>
function bukuTabunganApp() {
    return {
        tipeFilter: 'all', // 'all', 'deposit', 'withdrawal'
        searchMutasi: '',
        modalSetorOpen: false,
        modalTarikOpen: false,
        setorNominal: '',
        setorNominalDisplay: '',
        setorKeterangan: 'Setoran Rutin',
        tarikNominal: '',
        tarikNominalDisplay: '',
        tarikKeterangan: 'Kebutuhan Keluarga',
        maxSaldo: <?= $currentSaldo ?>,

        cashAccounts: <?= json_encode($akunKasList, JSON_UNESCAPED_UNICODE) ?>,
        escrowBalance: <?= (float)($escrowAccount['saldo_saat_ini'] ?? 0) ?>,
        selectedKasIdSetor: '<?= $defaultKasId ?>',
        selectedKasIdTarik: '<?= $defaultKasId ?>',

        mutasiItems: <?= json_encode(array_map(function($idx, $t) {
            return [
                'index' => $idx + 1,
                'id' => (string)($t['id'] ?? $idx),
                'tipe' => (string)$t['tipe'],
                'keywords' => strtolower($t['keterangan'] . ' ' . ($t['nomor_payroll'] ?? '') . ' ' . $t['sumber'] . ' ' . $t['tanggal']),
            ];
        }, array_keys($transaksiList), $transaksiList), JSON_UNESCAPED_UNICODE) ?>,

        init() {
            this.$watch('tipeFilter', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$watch('searchMutasi', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        get visibleMutasiCount() {
            if (!this.mutasiItems || this.mutasiItems.length === 0) return 0;
            return this.mutasiItems.filter(item => this.isMutasiVisible(item.keywords, item.tipe)).length;
        },

        get isKasTarikOverdraft() {
            if (!this.tarikNominal) return false;
            return Number(this.tarikNominal) > this.escrowBalance;
        },

        openModalSetor() {
            this.setorNominal = '';
            this.setorNominalDisplay = '';
            this.setorKeterangan = 'Setoran Rutin';
            this.modalSetorOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        closeModalSetor() {
            this.modalSetorOpen = false;
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

        handleSetorSubmit(e) {
            if (!this.setorNominal || this.setorNominal <= 0) {
                if (window.toast) window.toast('Nominal setoran harus lebih dari Rp 0.', 'warning');
                e.preventDefault();
                return false;
            }
            return true;
        },

        openModalTarik() {
            this.tarikNominal = '';
            this.tarikNominalDisplay = '';
            this.tarikKeterangan = 'Kebutuhan Keluarga';
            this.modalTarikOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        closeModalTarik() {
            this.modalTarikOpen = false;
        },

        onTarikInput(event) {
            const raw = event.target.value.replace(/\D/g, '');
            if (!raw) {
                this.tarikNominal = '';
                this.tarikNominalDisplay = '';
                return;
            }
            let num = parseInt(raw, 10);
            if (this.maxSaldo > 0 && num > this.maxSaldo) {
                num = this.maxSaldo;
            }
            this.tarikNominal = num;
            this.tarikNominalDisplay = num.toLocaleString('id-ID');
        },

        addTarikNominal(amount) {
            let current = parseInt(this.tarikNominal, 10) || 0;
            let target = current + amount;
            if (this.maxSaldo > 0 && target > this.maxSaldo) {
                target = this.maxSaldo;
            }
            this.tarikNominal = target;
            this.tarikNominalDisplay = target.toLocaleString('id-ID');
        },

        setAllTarikSaldo() {
            this.tarikNominal = this.maxSaldo;
            this.tarikNominalDisplay = this.maxSaldo > 0 ? this.maxSaldo.toLocaleString('id-ID') : '';
        },

        resetTarikNominal() {
            this.tarikNominal = '';
            this.tarikNominalDisplay = '';
        },

        handleTarikSubmit(e) {
            if (!this.tarikNominal || this.tarikNominal <= 0) {
                if (window.toast) window.toast('Nominal penarikan harus lebih dari Rp 0.', 'warning');
                e.preventDefault();
                return false;
            }
            if (this.tarikNominal > this.maxSaldo) {
                if (window.toast) window.toast('Nominal penarikan melebihi saldo tabungan saat ini.', 'warning');
                e.preventDefault();
                return false;
            }
            if (this.isKasTarikOverdraft) {
                if (window.toast) window.toast('Saldo kas fisik tabungan tidak mencukupi untuk penarikan.', 'warning');
                e.preventDefault();
                return false;
            }
            return true;
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        isMutasiVisible(searchKeywords, tipe) {
            if (this.tipeFilter !== 'all' && tipe !== this.tipeFilter) {
                return false;
            }
            if (!this.searchMutasi.trim()) {
                return true;
            }
            return searchKeywords.includes(this.searchMutasi.toLowerCase().trim());
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

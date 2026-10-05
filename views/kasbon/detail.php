<?php
/**
 * views/kasbon/detail.php
 * Halaman Rincian Kasbon & Riwayat Cicilan Pelunasan Keren One ERP
 * 100% Selaras dengan DNA Desain & Sistem Modal KEREN ONE
 */

use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CSRF;

ob_start();

function getKasbonDetailInitials(string $name): string {
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

$isAktif = ($kasbon['status_kasbon'] === 'aktif');
$canDelete = ($isAktif && (float)$kasbon['sisa_pinjaman'] === (float)$kasbon['total_pinjaman']);
$sisaPinjaman = (float)$kasbon['sisa_pinjaman'];
$totalPinjaman = (float)$kasbon['total_pinjaman'];
$totalTerbayarNominal = (float)$totalTerbayar;
$cicilanNominal = (float)($kasbon['potongan_per_periode'] ?? 0);
$initials = getKasbonDetailInitials($kasbon['nama_karyawan']);
?>

<style>
/* Currency Group */
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

/* Kasbon Avatar */
.kasbon-avatar {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 13px;
    letter-spacing: -0.02em;
    box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
    flex-shrink: 0;
}

/* Kasbon Modern Cards */
.kasbon-card {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03), 0 1px 2px rgba(0, 0, 0, 0.02);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    position: relative;
}
.dark .kasbon-card {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

/* Kasbon Metric Tiles */
.kasbon-tile {
    background: var(--color-canvas-soft, #f8fafc);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 12px;
    padding: 12px 14px;
    transition: all 0.15s ease;
}
.dark .kasbon-tile {
    background: rgba(15, 23, 42, 0.5);
    border-color: #334155;
}
.kasbon-tile:hover {
    border-color: #cbd5e1;
}
.dark .kasbon-tile:hover {
    border-color: #475569;
}

/* Detail Progress Track Large */
.kasbon-progress-track-lg {
    width: 100%;
    height: 12px;
    border-radius: 9999px;
    background: #e2e8f0;
    overflow: hidden;
    position: relative;
    padding: 1.5px;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.06);
}
.dark .kasbon-progress-track-lg {
    background: #334155;
}
.kasbon-progress-fill-lg {
    height: 100%;
    border-radius: 9999px;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    transition: width 0.7s cubic-bezier(0.4, 0, 0.2, 1);
    min-width: 0;
}
.kasbon-progress-fill-lg.is-lunas {
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

/* Status Badges */
.kasbon-badge-aktif {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 11px;
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
    gap: 6px;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 11px;
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

.kasbon-pulse-dot {
    width: 6.5px;
    height: 6.5px;
    border-radius: 9999px;
    background-color: #d97706;
    animation: kasbonPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    flex-shrink: 0;
}
.dark .kasbon-pulse-dot {
    background-color: #fbbf24;
}

@keyframes kasbonPulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.35;
        transform: scale(0.85);
    }
}

/* Payment Type & Table Badges */
.badge-pay-manual {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 11.5px;
    font-weight: 700;
    color: #047857;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.35);
    white-space: nowrap;
    line-height: 1.3;
}
.dark .badge-pay-manual {
    color: #34d399;
    background: rgba(16, 185, 129, 0.2);
    border-color: rgba(16, 185, 129, 0.45);
}

.badge-pay-payroll {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 11.5px;
    font-weight: 700;
    color: #1d4ed8;
    background: rgba(37, 99, 235, 0.1);
    border: 1px solid rgba(37, 99, 235, 0.3);
    white-space: nowrap;
    line-height: 1.3;
}
.dark .badge-pay-payroll {
    color: #60a5fa;
    background: rgba(37, 99, 235, 0.18);
    border-color: rgba(37, 99, 235, 0.45);
}

.badge-ref-payroll {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2.5px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    white-space: nowrap;
}
.dark .badge-ref-payroll {
    color: #cbd5e1;
    background: #1e293b;
    border-color: #475569;
}

.kasbon-nominal-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3.5px 10px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 700;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    color: #047857;
    background: rgba(16, 185, 129, 0.08);
    border: 1px solid rgba(16, 185, 129, 0.25);
    white-space: nowrap;
}
.dark .kasbon-nominal-pill {
    color: #34d399;
    background: rgba(16, 185, 129, 0.16);
    border-color: rgba(16, 185, 129, 0.35);
}

.kasbon-no-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    color: #64748b;
    background: rgba(0, 0, 0, 0.04);
}
.dark .kasbon-no-badge {
    color: #94a3b8;
    background: rgba(255, 255, 255, 0.06);
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
.kasbon-table-card table thead tr th {
    background: #f8fafc;
    color: #475569;
    border-bottom: 1px solid #e2e8f0;
}
.dark .kasbon-table-card table thead tr th {
    background: #0f172a;
    color: #94a3b8;
    border-bottom-color: #334155;
}
.kasbon-table-card table thead tr th:first-child {
    border-top-left-radius: 15px;
}
.kasbon-table-card table thead tr th:last-child {
    border-top-right-radius: 15px;
}

/* Filter Dock */
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

.btn-success-green {
    background-color: #059669 !important;
    color: #ffffff !important;
    border: 1px solid #047857 !important;
    box-shadow: 0 1px 2px rgba(5, 150, 105, 0.2);
    transition: all 0.15s ease;
}
.btn-success-green:hover {
    background-color: #047857 !important;
    color: #ffffff !important;
    box-shadow: 0 4px 10px rgba(5, 150, 105, 0.28);
}
.dark .btn-success-green {
    background-color: #059669 !important;
    border-color: #10b981 !important;
}
.dark .btn-success-green:hover {
    background-color: #10b981 !important;
}

/* =========================================================================
   CASH ACCOUNT SELECTION BOX IN MODAL (HARMONIOUS EMERALD)
   ========================================================================= */
.pg-kas-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
    max-height: 200px;
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

/* Selected State: Harmonious Emerald Green */
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
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px auto;
    background: rgba(99, 102, 241, 0.08);
    color: #4f46e5;
    border: 1px solid rgba(99, 102, 241, 0.2);
}
.pg-empty-icon-box svg {
    width: 24px;
    height: 24px;
    display: block;
    stroke-width: 2;
}
.dark .pg-empty-icon-box {
    background: rgba(99, 102, 241, 0.16);
    color: #818cf8;
    border-color: rgba(99, 102, 241, 0.35);
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
    max-width: 300px;
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
}
.pg-btn-reset-filter:hover {
    background: #fff1f2;
    border-color: #881337;
}
.dark .pg-btn-reset-filter {
    background: #1e293b;
    color: #fb7185;
    border-color: rgba(251, 113, 133, 0.3);
}
.dark .pg-btn-reset-filter:hover {
    background: rgba(251, 113, 133, 0.12);
}
</style>

<div x-data="kasbonDetailApp()" class="space-y-4">

    <!-- 1. PAGE HEADER (Pola Kanonikal KEREN ONE ERP) -->
    <div class="page-header flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-indigo" style="flex-shrink:0;">
                <i data-lucide="receipt-text"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#6366f1;"></span>
                    <span>Modul HR &bull; Rincian Kasbon &amp; Cicilan</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100">
                    <?= htmlspecialchars($pageTitle) ?>
                </h1>
                <div class="flex items-center gap-2 mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    <span class="font-bold text-slate-900 dark:text-slate-100"><?= htmlspecialchars($kasbon['nama_karyawan']) ?></span>
                    <span>&bull;</span>
                    <span>Diajukan: <?= Format::tanggalIndo($kasbon['tanggal_pengajuan']) ?></span>
                    <span>&bull;</span>
                    <span class="capitalize"><?= htmlspecialchars($kasbon['posisi'] ?? 'Staff') ?></span>
                </div>
            </div>
        </div>
        <div class="page-header-actions flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
            <a href="<?= Router::url('/kasbon') ?>" class="btn btn-secondary text-xs sm:text-sm font-semibold flex items-center justify-center gap-2 w-full sm:w-auto px-4" style="border-radius:10px; height:38px;">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Kasbon</span>
            </a>

            <?php if ($canDelete): ?>
                <button type="button" @click="modalDeleteOpen = true" class="btn btn-danger text-xs sm:text-sm font-semibold flex items-center justify-center gap-1.5 w-full sm:w-auto px-4" style="border-radius:10px; height:38px;">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span>Hapus Kasbon</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. INFORMASI KASBON & PROGRESS GRID (MODERN ENTERPRISE CARDS) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        
        <!-- ================================================================= -->
        <!-- KARTU 1 (KIRI, 7 cols): RINCIAN PINJAMAN & PROFIL PEMINJAM        -->
        <!-- ================================================================= -->
        <div class="lg:col-span-7 kasbon-card p-5 sm:p-6 flex flex-col justify-between overflow-hidden">
            <!-- Top Gradient Accent -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 via-rose-500 to-amber-500"></div>

            <div class="space-y-4">
                <!-- Header Kartu Kiri: Title & Status Badge -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-200/60 dark:border-indigo-800/60">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 truncate">
                                Rincian Pinjaman Kasbon
                            </h2>
                            <p class="text-[11px] text-slate-400 font-mono truncate">
                                ID: #<?= substr($kasbon['id'], 0, 8) ?> &bull; Pinjaman: <?= Format::tanggalIndo($kasbon['tanggal_pengajuan']) ?>
                            </p>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <?php if ($isAktif): ?>
                            <span class="kasbon-badge-aktif">
                                <span class="kasbon-pulse-dot"></span>
                                <span>Aktif (Berjalan)</span>
                            </span>
                        <?php else: ?>
                            <span class="kasbon-badge-lunas">
                                <i data-lucide="badge-check" style="width:14px;height:14px;color:#059669;"></i>
                                <span>Lunas Sepenuhnya</span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Profil Karyawan Banner -->
                <div class="p-3 bg-slate-50/80 dark:bg-slate-800/40 rounded-xl border border-slate-200/70 dark:border-slate-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="kasbon-avatar">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Peminjam</div>
                            <div class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100 truncate">
                                <?= htmlspecialchars($kasbon['nama_karyawan']) ?>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5 mt-0.5 truncate">
                                <span class="capitalize"><?= htmlspecialchars($kasbon['posisi'] ?? 'Staff') ?></span>
                                <span>&bull;</span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                    <?= htmlspecialchars($kasbon['tipe_penggajian'] ?? 'Bulanan') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right shrink-0 hidden sm:block">
                        <div class="text-[10.5px] text-slate-400 font-bold uppercase tracking-wider">Total Pinjaman</div>
                        <div class="text-base font-bold font-mono text-slate-900 dark:text-slate-100 mt-0.5">
                            <?= Format::rupiah($totalPinjaman) ?>
                        </div>
                    </div>
                </div>

                <!-- 4 Metric Tiles Grid (2x2) -->
                <div class="grid grid-cols-2 sm:grid-cols-2 gap-2.5">
                    <!-- Tile 1: Total Pinjaman -->
                    <div class="kasbon-tile">
                        <div class="flex items-center gap-1.5 text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">
                            <i data-lucide="wallet" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Total Pinjaman</span>
                        </div>
                        <div class="text-sm sm:text-base font-bold font-mono text-slate-900 dark:text-slate-100 mt-1">
                            <?= Format::rupiah($totalPinjaman) ?>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5 truncate">
                            Pencairan dana kasbon
                        </div>
                    </div>

                    <!-- Tile 2: Potongan / Cicilan Payroll -->
                    <div class="kasbon-tile">
                        <div class="flex items-center gap-1.5 text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">
                            <i data-lucide="receipt" class="w-3.5 h-3.5 text-cyan-600"></i>
                            <span>Cicilan / Periode</span>
                        </div>
                        <div class="text-sm sm:text-base font-bold font-mono text-slate-900 dark:text-slate-100 mt-1">
                            <?= ($cicilanNominal > 0) ? Format::rupiah($cicilanNominal) : '<span class="text-slate-400 font-sans text-xs">Manual (Kasir)</span>' ?>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5 truncate">
                            <?= ($cicilanNominal > 0) ? 'Otomatis via payroll' : 'Pembayaran di kasir' ?>
                        </div>
                    </div>

                    <!-- Tile 3: Sisa Pinjaman -->
                    <div class="kasbon-tile" style="<?= $isAktif ? 'background:rgba(245,158,11,0.06);border-color:rgba(245,158,11,0.25);' : '' ?>">
                        <div class="flex items-center gap-1.5 text-[10.5px] font-bold <?= $isAktif ? 'text-amber-700 dark:text-amber-400' : 'text-slate-400' ?> uppercase tracking-wider">
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-600"></i>
                            <span>Sisa Belum Lunas</span>
                        </div>
                        <div class="text-sm sm:text-base font-bold font-mono <?= $isAktif ? 'text-amber-700 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400' ?> mt-1">
                            <?= Format::rupiah($sisaPinjaman) ?>
                        </div>
                        <div class="text-[11px] <?= $isAktif ? 'text-amber-600/80 dark:text-amber-400/80' : 'text-slate-400' ?> mt-0.5 truncate">
                            <?= $isAktif ? 'Wajib dilunasi' : 'Telah lunas' ?>
                        </div>
                    </div>

                    <!-- Tile 4: Total Sudah Terbayar -->
                    <div class="kasbon-tile" style="background:rgba(16,185,129,0.06);border-color:rgba(16,185,129,0.25);">
                        <div class="flex items-center gap-1.5 text-[10.5px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
                            <i data-lucide="badge-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Sudah Terbayar</span>
                        </div>
                        <div class="text-sm sm:text-base font-bold font-mono text-emerald-700 dark:text-emerald-400 mt-1">
                            <?= Format::rupiah($totalTerbayarNominal) ?>
                        </div>
                        <div class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 truncate">
                            <?= count($potonganList) ?> transaksi cicilan
                        </div>
                    </div>
                </div>

                <!-- Alasan / Keterangan Banner -->
                <?php if (!empty($kasbon['keterangan']) || !empty($kasbon['catatan'])): ?>
                    <div class="p-3 bg-indigo-50/40 dark:bg-slate-800/60 rounded-xl text-xs text-slate-700 dark:text-slate-300 border-l-4 border-indigo-500 border border-slate-100 dark:border-slate-800 space-y-1">
                        <?php if (!empty($kasbon['keterangan'])): ?>
                            <div class="flex items-start gap-1.5">
                                <span class="font-bold text-slate-500 shrink-0">Alasan:</span>
                                <span class="leading-relaxed"><?= htmlspecialchars($kasbon['keterangan']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($kasbon['catatan'])): ?>
                            <div class="flex items-start gap-1.5 text-[11.5px] text-slate-500 dark:text-slate-400 pt-0.5 border-t border-slate-200/50 dark:border-slate-700/50">
                                <span class="font-bold shrink-0">Catatan:</span>
                                <span class="leading-relaxed"><?= htmlspecialchars($kasbon['catatan']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- KARTU 2 (KANAN, 5 cols): PROGRESS PELUNASAN & QUICK ACTION        -->
        <!-- ================================================================= -->
        <div class="lg:col-span-5 kasbon-card p-5 sm:p-6 flex flex-col justify-between overflow-hidden">
            <!-- Top Gradient Accent -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500"></div>

            <div class="space-y-4">
                <!-- Header Kartu Kanan: Title & Percentage Pill -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-200/60 dark:border-emerald-800/60">
                            <i data-lucide="pie-chart" class="w-4 h-4"></i>
                        </div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                            Progress Pelunasan
                        </h2>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-mono font-extrabold <?= $persenLunas >= 100 ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' ?>">
                        <?= $persenLunas ?>%
                    </span>
                </div>

                <!-- Big Number Highlight -->
                <div class="bg-slate-50/70 dark:bg-slate-800/30 rounded-xl p-4 border border-slate-200/60 dark:border-slate-800 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Sisa Pinjaman</div>
                        <div class="text-xl sm:text-2xl font-bold font-mono <?= $isAktif ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' ?> mt-0.5">
                            <?= Format::rupiah($sisaPinjaman) ?>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Total Terbayar</div>
                        <div class="text-sm sm:text-base font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">
                            <?= Format::rupiah($totalTerbayarNominal) ?>
                        </div>
                    </div>
                </div>

                <!-- Modern Progress Bar with Marker -->
                <div>
                    <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1.5 font-mono">
                        <span>Rp 0</span>
                        <span class="<?= $persenLunas >= 100 ? 'text-emerald-600' : 'text-indigo-600 dark:text-indigo-400' ?>"><?= $persenLunas ?>% Terlunasi</span>
                        <span><?= Format::rupiah($totalPinjaman) ?></span>
                    </div>
                    <div class="kasbon-progress-track-lg">
                        <div class="kasbon-progress-fill-lg <?= $persenLunas >= 100 ? 'is-lunas' : '' ?>" 
                             style="width: <?= max(0, min(100, $persenLunas)) ?>%;"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-400 mt-1.5">
                        <span><?= count($potonganList) ?> kali cicilan tercatat</span>
                        <span><?= $isAktif ? 'Masih berjalan' : 'Selesai' ?></span>
                    </div>
                </div>
            </div>

            <!-- Action Button / Status Footer inside Card Kanan -->
            <div class="mt-5 pt-3 border-t border-slate-100 dark:border-slate-800">
                <?php if ($isAktif): ?>
                    <button type="button" 
                            @click="openModalBayar()" 
                            class="btn btn-success-green w-full font-bold text-xs flex items-center justify-center gap-2 py-2.5 rounded-xl shadow-xs transition active:scale-[0.99]">
                        <i data-lucide="hand-coins" class="w-4 h-4"></i>
                        <span>Bayar / Lunasi Manual</span>
                    </button>
                    <p class="text-[10.5px] text-center text-slate-400 mt-2">
                        Cicilan dapat dipotong via penggajian atau dilunasi langsung di sini.
                    </p>
                <?php else: ?>
                    <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/70 dark:border-emerald-800/50 text-center text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center justify-center gap-1.5">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span>Pinjaman Ini Telah Lunas Sepenuhnya</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- 3. FILTER DOCK FOR CICILAN -->
    <div class="kasbon-filter-dock">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="overflow-x-auto no-scrollbar pb-1 sm:pb-0">
                <div class="tab-pill-group">
                    <button type="button" 
                            @click="tipeFilter = 'all'" 
                            class="tab-pill-btn" 
                            :class="tipeFilter === 'all' ? 'is-active' : ''">
                        <span>Semua Cicilan</span>
                        <span class="tab-pill-counter"><?= count($potonganList) ?></span>
                    </button>
                    <button type="button" 
                            @click="tipeFilter = 'payroll'" 
                            class="tab-pill-btn" 
                            :class="tipeFilter === 'payroll' ? 'is-active' : ''">
                        <i data-lucide="layers" style="width:13px;height:13px;"></i>
                        <span>Potongan Payroll</span>
                        <span class="tab-pill-counter"><?= count(array_filter($potonganList, fn($p) => $p['tipe_potongan'] === 'payroll')) ?></span>
                    </button>
                    <button type="button" 
                            @click="tipeFilter = 'manual'" 
                            class="tab-pill-btn" 
                            :class="tipeFilter === 'manual' ? 'is-active' : ''">
                        <i data-lucide="hand-coins" style="width:13px;height:13px;"></i>
                        <span>Bayar Manual</span>
                        <span class="tab-pill-counter"><?= count(array_filter($potonganList, fn($p) => $p['tipe_potongan'] === 'manual')) ?></span>
                    </button>
                </div>
            </div>

            <!-- Instant Search Box -->
            <div style="position:relative; display:flex; align-items:center; min-width:200px; max-width:320px; width:100%;">
                <svg style="position:absolute; left:11px; width:14px; height:14px; color:#94a3b8; pointer-events:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" 
                       x-model="searchCicilan" 
                       placeholder="Cari keterangan / no payroll..." 
                       class="form-input"
                       style="height:36px; padding-left:34px; padding-right:28px; font-size:12px; border-radius:8px; width:100%; border:1px solid var(--color-hairline-strong, #cbd5e1); background:var(--color-canvas, #ffffff); color:var(--color-ink, #0f172a);">
                <button type="button" 
                        x-show="searchCicilan.length > 0" 
                        @click="searchCicilan = ''" 
                        style="position:absolute; right:8px; color:#94a3b8; padding:2px; background:transparent; border:none; cursor:pointer;" 
                        title="Hapus pencarian">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 4. TABEL RIWAYAT CICILAN & POTONGAN -->
    <div class="kasbon-table-card">
        <!-- Canonical Desktop Table View -->
        <div class="pg-desktop-view table-wrapper overflow-x-auto custom-scrollbar">
            <table class="data-table w-full text-left border-collapse text-xs" style="min-width: 900px;">
                <thead>
                    <tr class="text-[11px] font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4 w-36">Tanggal</th>
                        <th class="py-3.5 px-4 w-36 text-center">Tipe Pembayaran</th>
                        <th class="py-3.5 px-4 text-right w-40">Nominal Potongan</th>
                        <th class="py-3.5 px-4 min-w-[200px]">Keterangan</th>
                        <th class="py-3.5 px-4 w-40 text-center">Referensi Payroll</th>
                        <th class="py-3.5 px-4 w-36 text-slate-500">Waktu Transaksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($potonganList)): ?>
                        <tr class="empty-row border-0">
                            <td colspan="7" class="empty-state-cell border-0 py-12 px-4 text-center">
                                <div class="pg-empty-card max-w-md mx-auto">
                                    <div class="pg-empty-icon-box">
                                        <i data-lucide="receipt"></i>
                                    </div>
                                    <div class="pg-empty-title">Belum Ada Riwayat Cicilan</div>
                                    <div class="pg-empty-desc">Kasbon ini belum pernah dipotong pada penggajian maupun dibayar manual.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <!-- Client-side No Results Row -->
                        <tr x-show="visibleCicilanCount === 0" x-cloak class="empty-row border-0">
                            <td colspan="7" class="empty-state-cell border-0 py-12 px-4 text-center">
                                <div class="pg-empty-card max-w-md mx-auto">
                                    <div class="pg-empty-icon-box">
                                        <i data-lucide="search-x"></i>
                                    </div>
                                    <div class="pg-empty-title">Tidak Ada Cicilan Yang Cocok</div>
                                    <div class="pg-empty-desc">Tidak ada riwayat cicilan yang sesuai dengan filter atau kata kunci saat ini.</div>
                                    <button type="button" @click="searchCicilan = ''; tipeFilter = 'all'" class="pg-btn-reset-filter">
                                        <i data-lucide="rotate-ccw"></i>
                                        <span>Reset Filter & Pencarian</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php foreach ($potonganList as $idx => $pk): 
                            $isPayroll = ($pk['tipe_potongan'] === 'payroll');
                            $searchKeywords = strtolower($pk['keterangan'] . ' ' . ($pk['nomor_payroll'] ?? '') . ' ' . $pk['tipe_potongan'] . ' ' . $pk['tanggal']);
                        ?>
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors"
                            x-show="isCicilanVisible('<?= htmlspecialchars($searchKeywords, ENT_QUOTES, 'UTF-8') ?>', '<?= $pk['tipe_potongan'] ?>')">
                            
                            <!-- Col 1: No -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="kasbon-no-badge"><?= $idx + 1 ?></span>
                            </td>

                            <!-- Col 2: Tanggal -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2 font-medium text-slate-800 dark:text-slate-200 text-xs">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-200/60 dark:border-indigo-800/50">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <span class="font-semibold"><?= Format::tanggalIndo($pk['tanggal']) ?></span>
                                </div>
                            </td>

                            <!-- Col 3: Tipe Pembayaran -->
                            <td class="py-3.5 px-4 text-center">
                                <?php if ($isPayroll): ?>
                                    <span class="badge-pay-payroll">
                                        <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                                        <span>Potongan Payroll</span>
                                    </span>
                                <?php else: ?>
                                    <span class="badge-pay-manual">
                                        <i data-lucide="hand-coins" class="w-3.5 h-3.5"></i>
                                        <span>Bayar Manual</span>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Col 4: Nominal Potongan -->
                            <td class="py-3.5 px-4 text-right">
                                <span class="kasbon-nominal-pill">
                                    <?= Format::rupiah((float)$pk['nominal']) ?>
                                </span>
                            </td>

                            <!-- Col 5: Keterangan -->
                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                                <div class="leading-relaxed font-medium text-xs">
                                    <?= htmlspecialchars($pk['keterangan'] ?: '-') ?>
                                </div>
                            </td>

                            <!-- Col 6: Referensi Payroll -->
                            <td class="py-3.5 px-4 text-center">
                                <?php if (!empty($pk['nomor_payroll'])): ?>
                                    <span class="badge-ref-payroll">
                                        <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-indigo-500"></i>
                                        <span><?= htmlspecialchars($pk['nomor_payroll']) ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs italic">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Col 7: Waktu Transaksi -->
                            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 font-mono text-xs">
                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span><?= date('d/m/Y H:i', strtotime($pk['dibuat_pada'])) ?></span>
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
            <?php if (empty($potonganList)): ?>
                <!-- Initial Empty State on Mobile -->
                <div class="pg-empty-card">
                    <div class="pg-empty-icon-box">
                        <i data-lucide="receipt"></i>
                    </div>
                    <div class="pg-empty-title">Belum Ada Riwayat Cicilan</div>
                    <div class="pg-empty-desc">Kasbon ini belum pernah dipotong pada penggajian maupun dibayar manual.</div>
                </div>
            <?php else: ?>
                <!-- Client-side No Search Results State on Mobile -->
                <div x-show="visibleCicilanCount === 0" x-cloak class="pg-empty-card">
                    <div class="pg-empty-icon-box">
                        <i data-lucide="search-x"></i>
                    </div>
                    <div class="pg-empty-title">Tidak Ada Cicilan Yang Cocok</div>
                    <div class="pg-empty-desc">Tidak ada riwayat cicilan yang sesuai dengan filter atau kata kunci saat ini.</div>
                    <button type="button" @click="searchCicilan = ''; tipeFilter = 'all'" class="pg-btn-reset-filter">
                        <i data-lucide="rotate-ccw"></i>
                        <span>Reset Filter & Pencarian</span>
                    </button>
                </div>

                <?php foreach ($potonganList as $idx => $pk): 
                    $isPayroll = ($pk['tipe_potongan'] === 'payroll');
                    $searchKeywords = strtolower($pk['keterangan'] . ' ' . ($pk['nomor_payroll'] ?? '') . ' ' . $pk['tipe_potongan'] . ' ' . $pk['tanggal']);
                    $timeFormatted = date('H:i', strtotime($pk['dibuat_pada'])) . ' WIB';
                    $fullTimeIso = date('d/m/Y H:i', strtotime($pk['dibuat_pada']));
                ?>
                <div class="pg-mobile-card"
                     x-show="isCicilanVisible('<?= htmlspecialchars($searchKeywords, ENT_QUOTES, 'UTF-8') ?>', '<?= $pk['tipe_potongan'] ?>')">
                    
                    <!-- Top Row: No & Tanggal + Tipe Badge -->
                    <div class="flex items-center justify-between gap-2.5 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="pg-mobile-num">#<?= $idx + 1 ?></span>
                            <div class="flex items-center gap-1.5 text-xs sm:text-[13px] font-bold text-slate-800 dark:text-slate-200 truncate">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                <span><?= Format::tanggalIndo($pk['tanggal']) ?></span>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <?php if ($isPayroll): ?>
                                <span class="badge-pay-payroll">
                                    <i data-lucide="layers" class="w-3 h-3"></i>
                                    <span>Payroll</span>
                                </span>
                            <?php else: ?>
                                <span class="badge-pay-manual">
                                    <i data-lucide="hand-coins" class="w-3 h-3"></i>
                                    <span>Manual</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Mid Row: Nominal & Waktu Transaksi -->
                    <div class="pg-mobile-mid-row">
                        <div class="min-w-0">
                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider mb-1">Nominal Potongan</div>
                            <span class="kasbon-nominal-pill">
                                <?= Format::rupiah((float)$pk['nominal']) ?>
                            </span>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider mb-1">Waktu Transaksi</div>
                            <span class="pg-mobile-time-pill" title="<?= $fullTimeIso ?>">
                                <i data-lucide="clock"></i>
                                <span><?= $timeFormatted ?></span>
                            </span>
                        </div>
                    </div>

                    <!-- Keterangan Note Box -->
                    <div class="pg-mobile-note">
                        <i data-lucide="file-text"></i>
                        <span class="pg-mobile-note-text">
                            <?= htmlspecialchars($pk['keterangan'] ?: 'Pembayaran cicilan kasbon') ?>
                        </span>
                    </div>

                    <!-- Referensi Payroll (jika ada) -->
                    <?php if (!empty($pk['nomor_payroll'])): ?>
                    <div class="pg-mobile-ref-row">
                        <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Ref Payroll:</span>
                        <span class="badge-ref-payroll">
                            <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-indigo-500 shrink-0"></i>
                            <span><?= htmlspecialchars($pk['nomor_payroll']) ?></span>
                        </span>
                    </div>
                    <?php endif; ?>

                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. MODAL BAYAR / LUNASI MANUAL (Teleported to Body)                       -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="modalBayarOpen" 
             x-cloak 
             class="modal-backdrop" 
             @click="closeModalBayar()" 
             @keydown.escape.window="closeModalBayar()">
            
            <div class="modal-box modal-box-md" style="max-width:480px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>

                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(99,102,241,0.1);color:#4f46e5;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(99,102,241,0.25);">
                            <i data-lucide="hand-coins" style="width:18px;height:18px;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div class="modal-title">Pembayaran Cicilan Kasbon</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Catat penerimaan cicilan atau pelunasan pinjaman secara manual</div>
                        </div>
                    </div>
                    <button type="button" @click="closeModalBayar()" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <form method="POST" action="<?= Router::url('/kasbon/bayar') ?>" @submit="handleBayarSubmit($event)" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="kasbon_id" value="<?= htmlspecialchars($kasbon['id']) ?>">

                    <div class="modal-body custom-scrollbar" style="display:flex;flex-direction:column;gap:14px;">
                        
                        <!-- Sisa Pinjaman Banner -->
                        <div class="p-3 rounded-lg flex items-center justify-between gap-2"
                             style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);">
                            <div class="flex items-center gap-1.5 text-xs text-amber-900 dark:text-amber-300">
                                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-amber-600"></i>
                                <span>Sisa Pinjaman Saat Ini: <strong class="font-mono font-bold"><?= Format::rupiah($sisaPinjaman) ?></strong></span>
                            </div>
                            <button type="button" 
                                    @click="setBayarNominal(<?= $sisaPinjaman ?>)" 
                                    class="btn btn-ghost btn-xs text-[10.5px] font-bold py-1 px-2.5 rounded-md border"
                                    style="background:var(--color-canvas, #ffffff);color:#d97706;border-color:rgba(245,158,11,0.3);">
                                Lunasi Penuh
                            </button>
                        </div>

                        <!-- Tanggal & Nominal Bayar Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label" style="display:block;margin-bottom:6px;">
                                    Tanggal Pembayaran <span style="color:#e11d48;">*</span>
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
                                    Nominal Bayar (Rp) <span style="color:#e11d48;">*</span>
                                </label>
                                <div class="pg-currency-group">
                                    <span class="pg-currency-addon">Rp</span>
                                    <input type="text" 
                                           x-model="bayarNominalDisplay" 
                                           @input="onBayarNominalInput($event)"
                                           @focus="$event.target.select()"
                                           @click="$event.target.select()"
                                           required 
                                           placeholder="0" 
                                           class="pg-currency-input font-mono font-bold text-right"
                                           autocomplete="off">
                                    <input type="hidden" name="nominal" :value="bayarNominalRaw">
                                </div>
                            </div>
                        </div>

                        <!-- Quick Nominal Preset Chips -->
                        <div>
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Preset Pembayaran:</div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <?php if ($cicilanNominal > 0 && $cicilanNominal <= $sisaPinjaman): ?>
                                    <button type="button" @click="setBayarNominal(<?= $cicilanNominal ?>)" class="quick-chip-btn">
                                        Sesuai Cicilan (<?= Format::rupiah($cicilanNominal) ?>)
                                    </button>
                                <?php endif; ?>
                                <button type="button" @click="setBayarNominal(<?= $sisaPinjaman ?>)" class="quick-chip-btn text-emerald-700 dark:text-emerald-400">
                                    Lunasi Semua (<?= Format::rupiah($sisaPinjaman) ?>)
                                </button>
                                <?php if ($sisaPinjaman >= 100000): ?>
                                    <button type="button" @click="setBayarNominal(<?= round($sisaPinjaman / 2) ?>)" class="quick-chip-btn">
                                        50% Sisa
                                    </button>
                                <?php endif; ?>
                                <button type="button" @click="resetBayarNominal()" class="quick-chip-btn text-rose-600 dark:text-rose-400">Reset</button>
                            </div>
                        </div>

                        <!-- Pilihan Akun Kas Penerima Pembayaran -->
                        <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 space-y-2">
                            <label class="text-xs font-bold text-slate-800 dark:text-slate-100 flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="wallet" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Masuk ke Akun Kas / Bank</span>
                                    <span class="text-rose-500">*</span>
                                </span>
                                <span class="text-[11px] text-slate-400 font-normal">Pilih penampung uang</span>
                            </label>

                            <input type="hidden" name="akun_kas_id" :value="selectedKasId" required>

                            <div class="pg-kas-grid custom-scrollbar">
                                <template x-for="acc in (cashAccounts || [])" :key="acc.id">
                                    <button type="button" 
                                            @click.prevent.stop="selectKas(acc.id)"
                                            :class="{
                                                'is-selected': String(selectedKasId) === String(acc.id)
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
                                                    <strong class="font-mono ml-0.5" style="color:var(--color-ink,#0f172a);font-weight:600;" x-text="formatRupiah(acc.saldo)"></strong>
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                </template>
                            </div>

                            <template x-if="!selectedKasId">
                                <div class="pg-alert-warning" style="margin-top:6px;">
                                    <svg width="15" height="15" style="width:15px;height:15px;min-width:15px;min-height:15px;color:#d97706;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    <span>Wajib memilih salah satu akun kas penampung uang pembayaran.</span>
                                </div>
                            </template>
                        </div>

                        <!-- Keterangan Pembayaran -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Keterangan Pembayaran
                            </label>
                            <input type="text" 
                                   name="keterangan" 
                                   x-model="bayarKeterangan"
                                   placeholder="Contoh: Pembayaran tunai kasir..." 
                                   class="form-input text-xs w-full"
                                   style="height:38px; border-radius:var(--rounded-md, 8px);">

                            <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                <span class="text-[10.5px] font-semibold text-slate-400">Pilihan Cepat:</span>
                                <button type="button" @click="bayarKeterangan = 'Pembayaran Tunai Kasir'" class="quick-chip-btn text-[10.5px]">Tunai Kasir</button>
                                <button type="button" @click="bayarKeterangan = 'Cicilan Manual Karyawan'" class="quick-chip-btn text-[10.5px]">Cicilan Manual</button>
                                <button type="button" @click="bayarKeterangan = 'Pelunasan Penuh Pinjaman'" class="quick-chip-btn text-[10.5px]">Pelunasan Penuh</button>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="closeModalBayar()" class="btn btn-secondary modal-btn-cancel-desktop">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-success-green font-bold flex items-center justify-center gap-1.5">
                            <i data-lucide="check-circle-2"></i>
                            <span>Simpan Pembayaran</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- ========================================================================= -->
    <!-- 6. MODAL KONFIRMASI HAPUS KASBON (Teleported to Body)                     -->
    <!-- ========================================================================= -->
    <?php if ($canDelete): ?>
    <template x-teleport="body">
        <div x-show="modalDeleteOpen" 
             x-cloak 
             class="modal-backdrop" 
             @click="modalDeleteOpen = false" 
             @keydown.escape.window="modalDeleteOpen = false">
            
            <div class="modal-box modal-box-md" style="max-width:440px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>

                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(225,29,72,0.12);color:#e11d48;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(225,29,72,0.25);">
                            <i data-lucide="trash-2" style="width:18px;height:18px;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div class="modal-title">Konfirmasi Hapus Kasbon</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Tindakan ini akan menghapus catatan pinjaman secara permanen</div>
                        </div>
                    </div>
                    <button type="button" @click="modalDeleteOpen = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <form method="POST" action="<?= Router::url('/kasbon/delete') ?>" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($kasbon['id']) ?>">

                    <div class="modal-body custom-scrollbar" style="display:flex;flex-direction:column;gap:12px;">
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                            Apakah Anda yakin ingin menghapus catatan pinjaman kasbon untuk <strong class="text-slate-900 dark:text-slate-100"><?= htmlspecialchars($kasbon['nama_karyawan']) ?></strong> sebesar <strong class="font-mono"><?= Format::rupiah((float)$kasbon['total_pinjaman']) ?></strong>?
                        </p>
                        <div class="p-2.5 rounded-lg text-[11px] bg-rose-50 dark:bg-rose-950/40 text-rose-900 dark:text-rose-300 border border-rose-200/70 dark:border-rose-800/40">
                            Catatan pinjaman ini belum memiliki riwayat pembayaran cicilan dan akan dihapus seutuhnya dari sistem.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="modalDeleteOpen = false" class="btn btn-secondary modal-btn-cancel-desktop">
                            Kembali
                        </button>
                        <button type="submit" class="btn btn-danger font-bold">
                            <i data-lucide="trash-2"></i>
                            <span>Ya, Hapus Kasbon</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
    <?php endif; ?>

</div>

<script>
function kasbonDetailApp() {
    const maxSisa = <?= $sisaPinjaman ?>;
    const defaultNominal = <?= min($sisaPinjaman, ($cicilanNominal > 0 ? $cicilanNominal : $sisaPinjaman)) ?>;

    return {
        tipeFilter: 'all', // 'all', 'payroll', 'manual'
        searchCicilan: '',
        modalBayarOpen: false,
        modalDeleteOpen: false,
        cashAccounts: <?= json_encode(array_map(function($a) {
            return [
                'id' => (string)$a['id'],
                'nama_akun' => (string)$a['nama_akun'],
                'tipe_akun' => (string)$a['tipe_akun'],
                'saldo' => (float)$a['saldo_saat_ini'],
                'is_default_pos' => (bool)($a['is_default_pos'] ?? false),
            ];
        }, $akunKasList ?? []), JSON_UNESCAPED_UNICODE) ?>,
        selectedKasId: '<?= !empty($akunKasList[0]['id']) ? (string)$akunKasList[0]['id'] : '' ?>',
        bayarNominalRaw: defaultNominal,
        bayarNominalDisplay: defaultNominal > 0 ? defaultNominal.toLocaleString('id-ID') : '',
        bayarKeterangan: 'Pembayaran Tunai Kasir',
        maxNominal: maxSisa,

        cicilanItems: <?= json_encode(array_map(function($idx, $pk) {
            return [
                'index' => $idx + 1,
                'id' => (string)($pk['id'] ?? $idx),
                'tipe' => (string)$pk['tipe_potongan'],
                'keywords' => strtolower($pk['keterangan'] . ' ' . ($pk['nomor_payroll'] ?? '') . ' ' . $pk['tipe_potongan'] . ' ' . $pk['tanggal']),
            ];
        }, array_keys($potonganList), $potonganList), JSON_UNESCAPED_UNICODE) ?>,

        get visibleCicilanCount() {
            if (!this.cicilanItems || this.cicilanItems.length === 0) return 0;
            return this.cicilanItems.filter(item => this.isCicilanVisible(item.keywords, item.tipe)).length;
        },

        init() {
            this.$watch('tipeFilter', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$watch('searchCicilan', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        get selectedAccount() {
            if (!this.selectedKasId) return null;
            return this.cashAccounts.find(a => String(a.id) === String(this.selectedKasId)) || null;
        },

        selectKas(id) {
            this.selectedKasId = String(id || '');
        },

        handleBayarSubmit(e) {
            if (!this.selectedKasId) {
                if (window.toast) window.toast('Pilih salah satu akun kas penampung pembayaran terlebih dahulu.', 'warning');
                e.preventDefault();
                return false;
            }
            if (!this.bayarNominalRaw || this.bayarNominalRaw <= 0) {
                if (window.toast) window.toast('Nominal pembayaran harus lebih dari Rp 0.', 'warning');
                e.preventDefault();
                return false;
            }
            return true;
        },

        openModalBayar() {
            this.setBayarNominal(defaultNominal);
            if (!this.selectedKasId && this.cashAccounts && this.cashAccounts.length > 0) {
                const defaultPos = this.cashAccounts.find(a => a.is_default_pos);
                this.selectedKasId = defaultPos ? String(defaultPos.id) : String(this.cashAccounts[0].id);
            }
            this.modalBayarOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        closeModalBayar() {
            this.modalBayarOpen = false;
        },

        onBayarNominalInput(e) {
            let raw = String(e.target.value || '').replace(/\D/g, '');
            let val = parseInt(raw, 10) || 0;
            if (val > this.maxNominal) {
                val = this.maxNominal;
            }
            this.bayarNominalRaw = val;
            this.bayarNominalDisplay = val > 0 ? val.toLocaleString('id-ID') : '';
        },

        setBayarNominal(amt) {
            let val = Math.min(Number(amt) || 0, this.maxNominal);
            this.bayarNominalRaw = val;
            this.bayarNominalDisplay = val > 0 ? val.toLocaleString('id-ID') : '';
        },

        resetBayarNominal() {
            this.bayarNominalRaw = 0;
            this.bayarNominalDisplay = '';
        },

        isCicilanVisible(keywords, tipe) {
            if (this.tipeFilter !== 'all' && this.tipeFilter !== tipe) return false;
            if (!this.searchCicilan.trim()) return true;
            const q = this.searchCicilan.toLowerCase().trim();
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

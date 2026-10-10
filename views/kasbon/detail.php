<?php
/**
 * views/kasbon/detail.php
 * Halaman Rincian Buku Kasbon Karyawan & Rekening Koran Log Aktivitas Multifungsi
 * 100% Selaras dengan DNA Desain, SSOT & Standar UI KEREN ONE
 */

use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CSRF;

ob_start();

if (!function_exists('getKasbonDetailInitials')) {
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
}

$isAktif = ($sisaPinjaman > 0.001);
$initials = getKasbonDetailInitials($karyawan['nama_karyawan']);
?>

<style>
/* ==========================================================================
   Kasbon Detail & Multifunction Ledger Styling
   ========================================================================== */

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
    font-size: 13.5px;
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

/* Tabular Nums & Zero Dot elimination */
.font-mono,
[class*="font-mono"],
.pg-currency-input,
.pg-currency-addon {
    font-variant-numeric: tabular-nums;
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

/* Kasbon Avatar */
.kasbon-avatar {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(136, 19, 55, 0.1);
    color: #881337;
    border: 1px solid rgba(136, 19, 55, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 14px;
    letter-spacing: -0.02em;
    font-family: var(--font-mono, monospace);
    font-variant-numeric: tabular-nums;
    flex-shrink: 0;
}
.dark .kasbon-avatar {
    background: rgba(251, 113, 133, 0.14);
    color: #fb7185;
    border-color: rgba(251, 113, 133, 0.3);
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
    height: 10px;
    border-radius: 9999px;
    background: #e2e8f0;
    overflow: hidden;
    position: relative;
    padding: 1px;
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
.kasbon-progress-fill-lg.is-aktif {
    background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
}
.kasbon-progress-fill-lg.is-lunas {
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

/* Status Badges */
.kasbon-badge-aktif {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    color: #b45309;
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.35);
    white-space: nowrap;
    line-height: 1;
}
.dark .kasbon-badge-aktif {
    color: #fbbf24;
    background: rgba(245, 158, 11, 0.18);
    border-color: rgba(245, 158, 11, 0.45);
}

.kasbon-badge-lunas {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    color: #059669;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.35);
    white-space: nowrap;
    line-height: 1;
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

/* Ledger Badges (Pills) */
.badge-act-pinjaman {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 3.5px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    color: #9f1239;
    background: rgba(225, 29, 72, 0.1);
    border: 1px solid rgba(225, 29, 72, 0.28);
    white-space: nowrap;
    line-height: 1;
}
.dark .badge-act-pinjaman {
    color: #fb7185;
    background: rgba(251, 113, 133, 0.16);
    border-color: rgba(251, 113, 133, 0.35);
}

.badge-act-payroll {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 3.5px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    color: #3730a3;
    background: rgba(79, 70, 229, 0.1);
    border: 1px solid rgba(79, 70, 229, 0.28);
    white-space: nowrap;
    line-height: 1;
}
.dark .badge-act-payroll {
    color: #818cf8;
    background: rgba(99, 102, 241, 0.18);
    border-color: rgba(99, 102, 241, 0.38);
}

.badge-act-manual {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 3.5px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    color: #065f46;
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.28);
    white-space: nowrap;
    line-height: 1;
}
.dark .badge-act-manual {
    color: #34d399;
    background: rgba(16, 185, 129, 0.18);
    border-color: rgba(16, 185, 129, 0.38);
}

.badge-ref-payroll {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 2.5px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    font-family: var(--font-mono, monospace);
    font-variant-numeric: tabular-nums;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    white-space: nowrap;
    line-height: 1;
}
.dark .badge-ref-payroll {
    color: #cbd5e1;
    background: #1e293b;
    border-color: #475569;
}

.badge-ref-kas {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 3.5px;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 600;
    color: #64748b;
    background: rgba(0, 0, 0, 0.04);
    border: 1px solid var(--color-hairline, #e2e8f0);
    white-space: nowrap;
    line-height: 1;
}
.dark .badge-ref-kas {
    color: #94a3b8;
    background: rgba(255, 255, 255, 0.05);
    border-color: #334155;
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
    font-family: var(--font-mono, monospace);
    font-variant-numeric: tabular-nums;
    color: #64748b;
    background: rgba(0, 0, 0, 0.04);
    line-height: 1;
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
    gap: 3px;
    overflow-x: auto;
}
.dark .tab-pill-group {
    background: #0f172a;
    border-color: #334155;
}
.tab-pill-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 12px;
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
    line-height: 1;
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
    background: #881337 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(136, 19, 55, 0.28);
}
.dark .tab-pill-btn.is-active {
    background: #9f1239 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(159, 18, 57, 0.35);
}
.tab-pill-counter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 9999px;
    font-size: 10.5px;
    font-weight: 800;
    font-family: var(--font-mono, monospace);
    font-variant-numeric: tabular-nums;
    background: rgba(0, 0, 0, 0.08);
    line-height: 1;
}
.dark .tab-pill-counter {
    background: rgba(255, 255, 255, 0.14);
}
.tab-pill-btn.is-active .tab-pill-counter {
    background: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
}

.btn-primary-maroon {
    background-color: #881337 !important;
    color: #ffffff !important;
    border: 1px solid #700f2d !important;
    box-shadow: 0 1px 2px rgba(136, 19, 55, 0.2);
    transition: all 0.15s ease;
}
.btn-primary-maroon:hover {
    background-color: #9f1239 !important;
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

/* Responsive Table vs Mobile Cards */
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

/* Mobile Card View */
.pg-mobile-card {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 14px;
    padding: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
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
    min-width: 24px;
    height: 22px;
    padding: 0 5px;
    border-radius: 6px;
    font-family: var(--font-mono, monospace);
    font-variant-numeric: tabular-nums;
    font-size: 11px;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    line-height: 1;
    flex-shrink: 0;
}
.dark .pg-mobile-num {
    background: #0f172a;
    color: #94a3b8;
    border-color: #334155;
}

/* Cash selection in modal */
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
}
.dark .pg-kas-card {
    background: #1e293b;
    border-color: #334155;
}
.dark .pg-kas-card:hover:not(.is-disabled) {
    border-color: #475569;
    background: #273549;
}
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
.pg-kas-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
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
}
.dark .pg-kas-pos-pill {
    background: rgba(16, 185, 129, 0.25);
    color: #6ee7b7;
}

.pg-alert-warning {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 500;
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #b45309;
}
.dark .pg-alert-warning {
    background: rgba(245, 158, 11, 0.12);
    border-color: rgba(245, 158, 11, 0.28);
    color: #fbbf24;
}

/* Empty State Card */
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
.dark .pg-btn-reset-filter {
    background: #1e293b;
    color: #fb7185;
    border-color: rgba(251, 113, 133, 0.35);
}
</style>

<div x-data="kasbonDetailApp()" class="space-y-4">

    <!-- 1. PAGE HEADER (Pola Kanonikal KEREN ONE ERP) -->
    <div class="page-header flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-indigo" style="flex-shrink:0;">
                <i data-lucide="notebook-tabs"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#6366f1;"></span>
                    <span>Modul HR &bull; Buku Kasbon Karyawan</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100">
                    Buku Kasbon: <?= htmlspecialchars($karyawan['nama_karyawan']) ?>
                </h1>
                <div class="flex items-center gap-2 mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    <span class="font-bold text-slate-900 dark:text-slate-100 capitalize"><?= htmlspecialchars($karyawan['posisi'] ?? 'Staff') ?></span>
                    <span>&bull;</span>
                    <span class="capitalize">Penggajian <?= htmlspecialchars($karyawan['tipe_penggajian'] ?? 'Bulanan') ?></span>
                    <span>&bull;</span>
                    <span><?= (int)$frekuensiPinjaman ?>x Riwayat Pinjaman</span>
                </div>
            </div>
        </div>
        <div class="page-header-actions flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
            <a href="<?= Router::url('/kasbon') ?>" class="btn btn-secondary text-xs sm:text-sm font-semibold flex items-center justify-center gap-2 w-full sm:w-auto px-4" style="border-radius:10px; height:38px;">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Kasbon</span>
            </a>

            <button type="button" 
                    @click="openModalTambahPinjaman()" 
                    class="btn btn-primary-maroon text-xs sm:text-sm font-bold flex items-center justify-center gap-1.5 w-full sm:w-auto px-4" 
                    style="border-radius:10px; height:38px;">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>+ Pinjaman Baru</span>
            </button>

            <?php if ($isAktif): ?>
                <button type="button" 
                        @click="openModalBayar()" 
                        class="btn btn-success-green text-xs sm:text-sm font-bold flex items-center justify-center gap-1.5 w-full sm:w-auto px-4" 
                        style="border-radius:10px; height:38px;">
                    <i data-lucide="hand-coins" class="w-4 h-4"></i>
                    <span>Bayar Cicilan (FIFO)</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. INFORMASI KASBON & PROGRESS GRID (MODERN ENTERPRISE CARDS) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        
        <!-- KARTU 1 (KIRI, 7 cols): PROFIL & KPI KONSOLIDASI KARYAWAN -->
        <div class="lg:col-span-7 kasbon-card p-5 sm:p-6 flex flex-col justify-between overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 via-rose-500 to-amber-500"></div>

            <div class="space-y-4">
                <!-- Header Kartu Kiri -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-200/60 dark:border-indigo-800/60">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 truncate">
                                Profil Peminjam &amp; Rekening Koran
                            </h2>
                            <p class="text-[11px] text-slate-400 font-mono truncate">
                                NIK: <?= htmlspecialchars($karyawan['nik'] ?? '-') ?> &bull; Status: <?= (!empty($karyawan['status_aktif']) ? 'Karyawan Aktif' : 'Nonaktif') ?>
                            </p>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <?php if ($isAktif): ?>
                            <span class="kasbon-badge-aktif">
                                <span class="kasbon-pulse-dot"></span>
                                <span>Kasbon Aktif</span>
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
                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Karyawan</div>
                            <div class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100 truncate">
                                <?= htmlspecialchars($karyawan['nama_karyawan']) ?>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5 mt-0.5 truncate">
                                <span class="capitalize"><?= htmlspecialchars($karyawan['posisi'] ?? 'Staff') ?></span>
                                <span>&bull;</span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                    <?= htmlspecialchars($karyawan['tipe_penggajian'] ?? 'Bulanan') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right shrink-0 hidden sm:block">
                        <div class="text-[10.5px] text-slate-400 font-bold uppercase tracking-wider">Sisa Berjalan</div>
                        <div class="text-base font-bold font-mono <?= $isAktif ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500' ?> mt-0.5">
                            <?= Format::rupiah($sisaPinjaman) ?>
                        </div>
                    </div>
                </div>

                <!-- 4 Metric Tiles Grid (2x2) -->
                <div class="grid grid-cols-2 sm:grid-cols-2 gap-2.5">
                    <!-- Tile 1: Sisa Pinjaman Berjalan -->
                    <div class="kasbon-tile" style="<?= $isAktif ? 'background:rgba(225,29,72,0.06);border-color:rgba(225,29,72,0.25);' : '' ?>">
                        <div class="flex items-center gap-1.5 text-[10.5px] font-bold <?= $isAktif ? 'text-rose-700 dark:text-rose-400' : 'text-slate-400' ?> uppercase tracking-wider">
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-rose-600"></i>
                            <span>Sisa Kasbon Berjalan</span>
                        </div>
                        <div class="text-sm sm:text-base font-bold font-mono <?= $isAktif ? 'text-rose-700 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400' ?> mt-1">
                            <?= Format::rupiah($sisaPinjaman) ?>
                        </div>
                        <div class="text-[11px] <?= $isAktif ? 'text-rose-600/80 dark:text-rose-400/80' : 'text-slate-400' ?> mt-0.5 truncate">
                            <?= $isAktif ? ((int)$jumlahPinjamanAktif . ' pinjaman aktif') : 'Telah lunas' ?>
                        </div>
                    </div>

                    <!-- Tile 2: Total Pinjaman Akumulatif -->
                    <div class="kasbon-tile">
                        <div class="flex items-center gap-1.5 text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">
                            <i data-lucide="wallet" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Total Akumulasi Pinjam</span>
                        </div>
                        <div class="text-sm sm:text-base font-bold font-mono text-slate-900 dark:text-slate-100 mt-1">
                            <?= Format::rupiah($totalPinjaman) ?>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5 truncate">
                            Sepanjang masa kerja
                        </div>
                    </div>

                    <!-- Tile 3: Total Terbayar -->
                    <div class="kasbon-tile" style="background:rgba(16,185,129,0.06);border-color:rgba(16,185,129,0.25);">
                        <div class="flex items-center gap-1.5 text-[10.5px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
                            <i data-lucide="badge-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Total Sudah Terbayar</span>
                        </div>
                        <div class="text-sm sm:text-base font-bold font-mono text-emerald-700 dark:text-emerald-400 mt-1">
                            <?= Format::rupiah($totalTerbayar) ?>
                        </div>
                        <div class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 truncate">
                            Payroll &amp; manual
                        </div>
                    </div>

                    <!-- Tile 4: Frekuensi Pinjaman -->
                    <div class="kasbon-tile">
                        <div class="flex items-center gap-1.5 text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">
                            <i data-lucide="layers" class="w-3.5 h-3.5 text-cyan-600"></i>
                            <span>Frekuensi Pinjaman</span>
                        </div>
                        <div class="text-sm sm:text-base font-bold font-mono text-slate-900 dark:text-slate-100 mt-1">
                            <?= (int)$frekuensiPinjaman ?>x Pinjaman
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5 truncate">
                            <?= (int)$jumlahPinjamanAktif ?> pinjaman aktif saat ini
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KARTU 2 (KANAN, 5 cols): PROGRESS PELUNASAN KONSOLIDASI & AKSI FIFO -->
        <div class="lg:col-span-5 kasbon-card p-5 sm:p-6 flex flex-col justify-between overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500"></div>

            <div class="space-y-4">
                <!-- Header Kartu Kanan -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-200/60 dark:border-emerald-800/60">
                            <i data-lucide="pie-chart" class="w-4 h-4"></i>
                        </div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                            Tingkat Pelunasan Pinjaman
                        </h2>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-mono font-extrabold <?= $persenLunas >= 100 ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' ?>">
                        <?= $persenLunas ?>%
                    </span>
                </div>

                <!-- Big Number Highlight -->
                <div class="bg-slate-50/70 dark:bg-slate-800/30 rounded-xl p-4 border border-slate-200/60 dark:border-slate-800 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Sisa Hutang Berjalan</div>
                        <div class="text-xl sm:text-2xl font-bold font-mono <?= $isAktif ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' ?> mt-0.5">
                            <?= Format::rupiah($sisaPinjaman) ?>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Telah Terbayar</div>
                        <div class="text-sm sm:text-base font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">
                            <?= Format::rupiah($totalTerbayar) ?>
                        </div>
                    </div>
                </div>

                <!-- Modern Progress Bar -->
                <div>
                    <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1.5 font-mono">
                        <span>Rp 0</span>
                        <span class="<?= $persenLunas >= 100 ? 'text-emerald-600 font-bold' : 'text-indigo-600 dark:text-indigo-400' ?>"><?= $persenLunas ?>% Terlunasi</span>
                        <span><?= Format::rupiah($totalPinjaman) ?></span>
                    </div>
                    <div class="kasbon-progress-track-lg">
                        <div class="kasbon-progress-fill-lg <?= $persenLunas >= 100 ? 'is-lunas' : 'is-aktif' ?>" 
                             style="width: <?= max(0, min(100, $persenLunas)) ?>%;"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-400 mt-1.5">
                        <span><?= count($activities) ?> rekaman mutasi buku besar</span>
                        <span><?= $isAktif ? 'Masih ada kewajiban' : 'Lunas tuntas' ?></span>
                    </div>
                </div>
            </div>

            <!-- Action Button inside Card Kanan -->
            <div class="mt-5 pt-3 border-t border-slate-100 dark:border-slate-800">
                <?php if ($isAktif): ?>
                    <button type="button" 
                            @click="openModalBayar()" 
                            class="btn btn-success-green w-full font-bold text-xs flex items-center justify-center gap-2 py-2.5 rounded-xl shadow-xs transition active:scale-[0.99]">
                        <i data-lucide="hand-coins" class="w-4 h-4"></i>
                        <span>Bayar Cicilan Manual (Alokasi FIFO)</span>
                    </button>
                    <p class="text-[10.5px] text-center text-slate-400 mt-2">
                        Pembayaran akan otomatis melunasi pinjaman aktif tertua lebih dahulu.
                    </p>
                <?php else: ?>
                    <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/70 dark:border-emerald-800/50 text-center text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center justify-center gap-1.5">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span>Seluruh Pinjaman Karyawan Telah Lunas Sepenuhnya</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- 3. FILTER DOCK FOR MULTIFUNCTION ACTIVITIES -->
    <div class="kasbon-filter-dock">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="overflow-x-auto no-scrollbar pb-1 sm:pb-0">
                <div class="tab-pill-group">
                    <!-- Tab 1: Semua Aktivitas -->
                    <button type="button" 
                            @click="tipeFilter = 'all'" 
                            class="tab-pill-btn" 
                            :class="tipeFilter === 'all' ? 'is-active' : ''">
                        <span>Semua Aktivitas</span>
                        <span class="tab-pill-counter"><?= (int)$countAll ?></span>
                    </button>

                    <!-- Tab 2: Pencairan Pinjaman -->
                    <button type="button" 
                            @click="tipeFilter = 'pinjaman'" 
                            class="tab-pill-btn" 
                            :class="tipeFilter === 'pinjaman' ? 'is-active' : ''">
                        <i data-lucide="hand-coins" style="width:13px;height:13px;"></i>
                        <span>Pencairan Pinjaman</span>
                        <span class="tab-pill-counter"><?= (int)$countPinjaman ?></span>
                    </button>

                    <!-- Tab 3: Potongan Payroll -->
                    <button type="button" 
                            @click="tipeFilter = 'potongan_payroll'" 
                            class="tab-pill-btn" 
                            :class="tipeFilter === 'potongan_payroll' ? 'is-active' : ''">
                        <i data-lucide="layers" style="width:13px;height:13px;"></i>
                        <span>Potongan Payroll</span>
                        <span class="tab-pill-counter"><?= (int)$countPayroll ?></span>
                    </button>

                    <!-- Tab 4: Bayar Manual -->
                    <button type="button" 
                            @click="tipeFilter = 'bayar_manual'" 
                            class="tab-pill-btn" 
                            :class="tipeFilter === 'bayar_manual' ? 'is-active' : ''">
                        <i data-lucide="check-circle-2" style="width:13px;height:13px;"></i>
                        <span>Bayar Manual</span>
                        <span class="tab-pill-counter"><?= (int)$countManual ?></span>
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
                       x-model="searchKeyword" 
                       placeholder="Cari keterangan, akun, no payroll..." 
                       class="form-input"
                       style="height:36px; padding-left:34px; padding-right:28px; font-size:12px; border-radius:8px; width:100%; border:1px solid var(--color-hairline-strong, #cbd5e1); background:var(--color-canvas, #ffffff); color:var(--color-ink, #0f172a);">
                <button type="button" 
                        x-show="searchKeyword.length > 0" 
                        @click="searchKeyword = ''" 
                        style="position:absolute; right:8px; color:#94a3b8; padding:2px; background:transparent; border:none; cursor:pointer;" 
                        title="Hapus pencarian">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 4. TABEL BUKU BESAR LOG AKTIVITAS MULTIFUNGSI (REKENING KORAN) -->
    <div class="kasbon-table-card">
        <!-- Canonical Desktop Table View -->
        <div class="pg-desktop-view table-wrapper overflow-x-auto custom-scrollbar">
            <table class="data-table w-full text-left border-collapse text-xs" style="min-width: 980px;">
                <thead>
                    <tr class="text-[11px] font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4 w-32">Tanggal</th>
                        <th class="py-3.5 px-4 w-40 text-center">Jenis Aktivitas</th>
                        <th class="py-3.5 px-4 text-right w-36">Pinjaman (+)</th>
                        <th class="py-3.5 px-4 text-right w-36">Pembayaran (-)</th>
                        <th class="py-3.5 px-4 text-right w-40">Saldo Berjalan</th>
                        <th class="py-3.5 px-4 min-w-[200px]">Keterangan &amp; Referensi</th>
                        <th class="py-3.5 px-4 w-32 text-center text-slate-500">Waktu</th>
                        <th class="py-3.5 px-4 w-20 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($activities)): ?>
                        <tr class="empty-row border-0">
                            <td colspan="9" class="empty-state-cell border-0 py-12 px-4 text-center">
                                <div class="pg-empty-card max-w-md mx-auto">
                                    <div class="pg-empty-icon-box">
                                        <i data-lucide="receipt"></i>
                                    </div>
                                    <div class="pg-empty-title">Belum Ada Riwayat Aktivitas</div>
                                    <div class="pg-empty-desc">Karyawan ini belum memiliki catatan aktivitas pinjaman kasbon atau pembayaran cicilan.</div>
                                    <button type="button" @click="openModalTambahPinjaman()" class="pg-btn-reset-filter" style="background:#881337; color:#ffffff; border-color:#700f2d;">
                                        <i data-lucide="plus"></i>
                                        <span>+ Pinjaman Baru</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <!-- Client-side No Results Row -->
                        <tr x-show="visibleActivityCount === 0" x-cloak class="empty-row border-0">
                            <td colspan="9" class="empty-state-cell border-0 py-12 px-4 text-center">
                                <div class="pg-empty-card max-w-md mx-auto">
                                    <div class="pg-empty-icon-box">
                                        <i data-lucide="search-x"></i>
                                    </div>
                                    <div class="pg-empty-title">Tidak Ada Aktivitas Yang Cocok</div>
                                    <div class="pg-empty-desc">Tidak ada riwayat mutasi buku besar yang sesuai dengan filter atau kata kunci pencarian.</div>
                                    <button type="button" @click="searchKeyword = ''; tipeFilter = 'all'" class="pg-btn-reset-filter">
                                        <i data-lucide="rotate-ccw"></i>
                                        <span>Reset Filter & Pencarian</span>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <?php foreach ($activities as $idx => $act): 
                            $isPinjaman = ($act['jenis_aktivitas'] === 'pinjaman');
                            $isPayroll = ($act['jenis_aktivitas'] === 'potongan_payroll');
                            $isManual = ($act['jenis_aktivitas'] === 'bayar_manual');
                            $searchKeywords = strtolower($act['keterangan'] . ' ' . ($act['nama_akun_kas'] ?? '') . ' ' . ($act['nomor_payroll'] ?? '') . ' ' . $act['jenis_aktivitas'] . ' ' . $act['tanggal']);
                            $canDeleteThisPinjaman = ($isPinjaman && (float)$act['sisa_pinjaman'] === (float)$act['nominal_pinjaman']);
                        ?>
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors"
                            x-show="isActivityVisible('<?= htmlspecialchars($searchKeywords, ENT_QUOTES, 'UTF-8') ?>', '<?= $act['jenis_aktivitas'] ?>')">
                            
                            <!-- Col 1: No -->
                            <td class="py-3 px-4 text-center">
                                <span class="kasbon-no-badge"><?= $idx + 1 ?></span>
                            </td>

                            <!-- Col 2: Tanggal -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-1.5 font-semibold text-slate-800 dark:text-slate-200 text-xs">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                    <span><?= Format::tanggalIndo($act['tanggal']) ?></span>
                                </div>
                            </td>

                            <!-- Col 3: Jenis Aktivitas Badge -->
                            <td class="py-3 px-4 text-center">
                                <?php if ($isPinjaman): ?>
                                    <span class="badge-act-pinjaman">
                                        <i data-lucide="hand-coins" class="w-3 h-3"></i>
                                        <span>Pencairan Kasbon</span>
                                    </span>
                                <?php elseif ($isPayroll): ?>
                                    <span class="badge-act-payroll">
                                        <i data-lucide="layers" class="w-3 h-3"></i>
                                        <span>Potongan Payroll</span>
                                    </span>
                                <?php else: ?>
                                    <span class="badge-act-manual">
                                        <i data-lucide="check-circle-2" class="w-3 h-3"></i>
                                        <span>Bayar Manual</span>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Col 4: Mutasi Pinjaman (+) -->
                            <td class="py-3 px-4 text-right font-mono">
                                <?php if ((float)$act['nominal_pinjaman'] > 0): ?>
                                    <span class="font-bold text-slate-900 dark:text-slate-100">
                                        <?= Format::rupiah((float)$act['nominal_pinjaman']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-300 dark:text-slate-600 font-sans">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Col 5: Mutasi Pembayaran (-) -->
                            <td class="py-3 px-4 text-right font-mono">
                                <?php if ((float)$act['nominal_bayar'] > 0): ?>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                        -<?= Format::rupiah((float)$act['nominal_bayar']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-300 dark:text-slate-600 font-sans">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Col 6: Saldo Berjalan (Running Balance) -->
                            <td class="py-3 px-4 text-right font-mono">
                                <span class="font-bold <?= (float)$act['saldo_berjalan'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' ?>">
                                    <?= Format::rupiah((float)$act['saldo_berjalan']) ?>
                                </span>
                            </td>

                            <!-- Col 7: Keterangan & Referensi -->
                            <td class="py-3 px-4 text-slate-700 dark:text-slate-300">
                                <div class="leading-relaxed text-xs">
                                    <span><?= htmlspecialchars($act['keterangan'] ?: '-') ?></span>
                                    
                                    <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                        <?php if (!empty($act['nomor_payroll'])): ?>
                                            <span class="badge-ref-payroll" title="Nomor Referensi Penggajian">
                                                <i data-lucide="file-spreadsheet" class="w-3 h-3 text-indigo-500"></i>
                                                <span><?= htmlspecialchars($act['nomor_payroll']) ?></span>
                                            </span>
                                        <?php endif; ?>

                                        <?php if (!empty($act['nama_akun_kas'])): ?>
                                            <span class="badge-ref-kas" title="Akun Kas Transaksi">
                                                <i data-lucide="wallet" class="w-3 h-3"></i>
                                                <span><?= htmlspecialchars($act['nama_akun_kas']) ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- Col 8: Waktu Transaksi -->
                            <td class="py-3 px-4 text-center text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                                <?= date('d/m/y H:i', strtotime($act['dibuat_pada'])) ?>
                            </td>

                            <!-- Col 9: Aksi -->
                            <td class="py-3 px-4 text-center">
                                <?php if ($canDeleteThisPinjaman): ?>
                                    <button type="button" 
                                            @click="confirmDeletePinjaman('<?= $act['id'] ?>', '<?= Format::rupiah((float)$act['nominal_pinjaman']) ?>')"
                                            class="btn btn-ghost btn-sm text-rose-600 hover:text-rose-700 p-1"
                                            title="Batalkan & Hapus Kasbon (Belum ada cicilan)">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-slate-300 dark:text-slate-600">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Clean Modern Mobile Card View -->
        <div class="pg-mobile-view p-3 sm:p-4 space-y-3 bg-slate-50/60 dark:bg-slate-900/40">
            <?php if (empty($activities)): ?>
                <div class="pg-empty-card">
                    <div class="pg-empty-icon-box">
                        <i data-lucide="receipt"></i>
                    </div>
                    <div class="pg-empty-title">Belum Ada Riwayat Aktivitas</div>
                    <div class="pg-empty-desc">Karyawan ini belum memiliki catatan aktivitas pinjaman kasbon atau pembayaran cicilan.</div>
                </div>
            <?php else: ?>
                <div x-show="visibleActivityCount === 0" x-cloak class="pg-empty-card">
                    <div class="pg-empty-icon-box">
                        <i data-lucide="search-x"></i>
                    </div>
                    <div class="pg-empty-title">Tidak Ada Aktivitas Yang Cocok</div>
                    <div class="pg-empty-desc">Tidak ada riwayat mutasi yang sesuai dengan filter atau kata kunci saat ini.</div>
                    <button type="button" @click="searchKeyword = ''; tipeFilter = 'all'" class="pg-btn-reset-filter">
                        <i data-lucide="rotate-ccw"></i>
                        <span>Reset Filter & Pencarian</span>
                    </button>
                </div>

                <?php foreach ($activities as $idx => $act): 
                    $isPinjaman = ($act['jenis_aktivitas'] === 'pinjaman');
                    $isPayroll = ($act['jenis_aktivitas'] === 'potongan_payroll');
                    $isManual = ($act['jenis_aktivitas'] === 'bayar_manual');
                    $searchKeywords = strtolower($act['keterangan'] . ' ' . ($act['nama_akun_kas'] ?? '') . ' ' . ($act['nomor_payroll'] ?? '') . ' ' . $act['jenis_aktivitas'] . ' ' . $act['tanggal']);
                    $canDeleteThisPinjaman = ($isPinjaman && (float)$act['sisa_pinjaman'] === (float)$act['nominal_pinjaman']);
                ?>
                <div class="pg-mobile-card"
                     x-show="isActivityVisible('<?= htmlspecialchars($searchKeywords, ENT_QUOTES, 'UTF-8') ?>', '<?= $act['jenis_aktivitas'] ?>')">
                    
                    <!-- Top Row: No, Tanggal & Badge -->
                    <div class="flex items-center justify-between gap-2.5 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="pg-mobile-num">#<?= $idx + 1 ?></span>
                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800 dark:text-slate-200 truncate">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                <span><?= Format::tanggalIndo($act['tanggal']) ?></span>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <?php if ($isPinjaman): ?>
                                <span class="badge-act-pinjaman">
                                    <i data-lucide="hand-coins" class="w-3 h-3"></i>
                                    <span>Pencairan</span>
                                </span>
                            <?php elseif ($isPayroll): ?>
                                <span class="badge-act-payroll">
                                    <i data-lucide="layers" class="w-3 h-3"></i>
                                    <span>Payroll</span>
                                </span>
                            <?php else: ?>
                                <span class="badge-act-manual">
                                    <i data-lucide="check-circle-2" class="w-3 h-3"></i>
                                    <span>Manual</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Mid Row: Mutasi & Saldo Berjalan Box -->
                    <div style="background:var(--color-canvas-soft, #f8fafc); border:1px solid var(--color-hairline, #e2e8f0); border-radius:10px; padding:10px 12px;" class="dark:bg-slate-800/50 dark:border-slate-700 space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-mono">
                            <span class="text-slate-500 font-sans text-[11px] font-semibold">Mutasi Transaksi:</span>
                            <?php if ($isPinjaman): ?>
                                <span class="font-bold text-slate-900 dark:text-slate-100">
                                    +<?= Format::rupiah((float)$act['nominal_pinjaman']) ?>
                                </span>
                            <?php else: ?>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                    -<?= Format::rupiah((float)$act['nominal_bayar']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center justify-between text-xs font-mono pt-1.5 border-t border-slate-200/60 dark:border-slate-700/60">
                            <span class="text-slate-500 font-sans text-[11px] font-semibold">Saldo Berjalan:</span>
                            <span class="font-bold <?= (float)$act['saldo_berjalan'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' ?>">
                                <?= Format::rupiah((float)$act['saldo_berjalan']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Keterangan & Referensi -->
                    <div class="text-xs text-slate-700 dark:text-slate-300">
                        <div class="font-medium"><?= htmlspecialchars($act['keterangan'] ?: '-') ?></div>
                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                            <?php if (!empty($act['nomor_payroll'])): ?>
                                <span class="badge-ref-payroll">
                                    <i data-lucide="file-spreadsheet" class="w-3 h-3 text-indigo-500"></i>
                                    <span><?= htmlspecialchars($act['nomor_payroll']) ?></span>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($act['nama_akun_kas'])): ?>
                                <span class="badge-ref-kas">
                                    <i data-lucide="wallet" class="w-3 h-3"></i>
                                    <span><?= htmlspecialchars($act['nama_akun_kas']) ?></span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Bottom row: Waktu Transaksi & Delete jika bisa -->
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-400 font-mono">
                        <span>Waktu: <?= date('d/m/Y H:i', strtotime($act['dibuat_pada'])) ?></span>
                        <?php if ($canDeleteThisPinjaman): ?>
                            <button type="button" 
                                    @click="confirmDeletePinjaman('<?= $act['id'] ?>', '<?= Format::rupiah((float)$act['nominal_pinjaman']) ?>')"
                                    class="text-rose-600 font-sans font-bold hover:underline">
                                Hapus Pinjaman
                            </button>
                        <?php endif; ?>
                    </div>

                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. MODAL BAYAR CICILAN MANUAL (FIFO) (Teleported to Body)                 -->
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
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(16,185,129,0.1);color:#059669;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(16,185,129,0.25);">
                            <i data-lucide="hand-coins" style="width:18px;height:18px;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div class="modal-title">Pembayaran Kasbon Manual (FIFO)</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Otomatis memotong dan melunasi pinjaman aktif tertua lebih dahulu</div>
                        </div>
                    </div>
                    <button type="button" @click="closeModalBayar()" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <form method="POST" action="<?= Router::url('/kasbon/bayar') ?>" @submit="handleBayarSubmit($event)" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="karyawan_id" value="<?= htmlspecialchars($karyawan['id']) ?>">

                    <div class="modal-body custom-scrollbar" style="display:flex;flex-direction:column;gap:14px;">
                        
                        <!-- Sisa Kasbon Banner -->
                        <div class="p-3 rounded-lg flex items-center justify-between gap-2"
                             style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);">
                            <div class="flex items-center gap-1.5 text-xs text-amber-900 dark:text-amber-300">
                                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-amber-600"></i>
                                <span>Total Sisa Kasbon: <strong class="font-mono font-bold"><?= Format::rupiah($sisaPinjaman) ?></strong></span>
                            </div>
                            <button type="button" 
                                    @click="setBayarNominal(<?= $sisaPinjaman ?>)" 
                                    class="btn btn-ghost btn-xs text-[10.5px] font-bold py-1 px-2.5 rounded-md border"
                                    style="background:var(--color-canvas, #ffffff);color:#d97706;border-color:rgba(245,158,11,0.3);">
                                Lunasi Semua
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
                                <button type="button" @click="setBayarNominal(<?= $sisaPinjaman ?>)" class="quick-chip-btn text-emerald-700 dark:text-emerald-400">
                                    Lunasi Seluruh Sisa (<?= Format::rupiah($sisaPinjaman) ?>)
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
    <!-- 6. MODAL TAMBAH PINJAMAN BARU (KARYAWAN TERKUNCI)                         -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="modalTambahOpen" 
             x-cloak 
             class="modal-backdrop" 
             @click="closeModalTambahPinjaman()" 
             @keydown.escape.window="closeModalTambahPinjaman()">
            
            <div class="modal-box modal-box-md" style="max-width:500px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>

                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(136,19,55,0.1);color:#881337;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(136,19,55,0.25);">
                            <i data-lucide="hand-coins" style="width:18px;height:18px;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div class="modal-title">Tambah Pinjaman Baru</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Pencairan pinjaman kasbon untuk <?= htmlspecialchars($karyawan['nama_karyawan']) ?></div>
                        </div>
                    </div>
                    <button type="button" @click="closeModalTambahPinjaman()" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/kasbon/store') ?>" method="POST" @submit="return handleFormTambahSubmit($event)" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="karyawan_id" value="<?= htmlspecialchars($karyawan['id']) ?>">

                    <div class="modal-body custom-scrollbar" style="display:flex;flex-direction:column;gap:14px;">
                        
                        <!-- Info Karyawan Box -->
                        <div class="p-2.5 bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center gap-2.5">
                            <div class="kasbon-avatar" style="width:32px; height:32px; font-size:11px;">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate"><?= htmlspecialchars($karyawan['nama_karyawan']) ?></div>
                                <div class="text-[11px] text-slate-500 capitalize"><?= htmlspecialchars($karyawan['posisi'] ?? 'Staff') ?> &bull; <?= htmlspecialchars($karyawan['tipe_penggajian'] ?? 'Bulanan') ?></div>
                            </div>
                        </div>

                        <!-- Tanggal Pinjaman & Total Pinjaman Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label" style="display:block;margin-bottom:6px;">
                                    Tanggal Pinjaman <span style="color:#e11d48;">*</span>
                                </label>
                                <input type="date" 
                                       name="tanggal_pengajuan" 
                                       x-model="formTanggalPinjam" 
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
                                           x-model="totalPinjamDisplay" 
                                           @input="onTotalPinjamInput($event)"
                                           @focus="$event.target.select()"
                                           @click="$event.target.select()"
                                           required 
                                           placeholder="0" 
                                           class="pg-currency-input font-mono font-bold text-right"
                                           autocomplete="off">
                                    <input type="hidden" name="total_pinjaman" :value="totalPinjamRaw">
                                </div>
                            </div>
                        </div>

                        <!-- Quick Nominal Preset Chips -->
                        <div>
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Preset Pinjaman Cepat:</div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <button type="button" @click="setTotalPinjam(500000)" class="quick-chip-btn">500.000</button>
                                <button type="button" @click="setTotalPinjam(1000000)" class="quick-chip-btn">1.000.000</button>
                                <button type="button" @click="setTotalPinjam(2000000)" class="quick-chip-btn">2.000.000</button>
                                <button type="button" @click="resetTotalPinjam()" class="quick-chip-btn text-rose-600 dark:text-rose-400">Reset</button>
                            </div>
                        </div>

                        <!-- Cicilan / Potongan per Periode Payroll -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Potongan / Cicilan per Periode Payroll (Rp)
                            </label>
                            <div class="pg-currency-group">
                                <span class="pg-currency-addon">Rp</span>
                                <input type="text" 
                                       x-model="cicilanBaruDisplay" 
                                       @input="onCicilanBaruInput($event)"
                                       @focus="$event.target.select()"
                                       @click="$event.target.select()"
                                       placeholder="0 (Isi 0 jika bayar manual)" 
                                       class="pg-currency-input font-mono font-bold text-right"
                                       autocomplete="off">
                                <input type="hidden" name="potongan_per_periode" :value="cicilanBaruRaw">
                            </div>
                            <span class="text-[11px] text-slate-400 block mt-1">Otomatis dipotong setiap periode gaji.</span>

                            <!-- Quick Plan Divider Chips -->
                            <div class="flex items-center gap-1.5 mt-2 flex-wrap" x-show="totalPinjamRaw > 0">
                                <span class="text-[10.5px] font-semibold text-slate-400">Rencana Cicilan:</span>
                                <button type="button" @click="divideCicilanBaru(1)" class="quick-chip-btn text-[10.5px]" :class="selectedDivideBaru === 1 ? 'is-active' : ''">1x (Lunas Sekali)</button>
                                <button type="button" @click="divideCicilanBaru(2)" class="quick-chip-btn text-[10.5px]" :class="selectedDivideBaru === 2 ? 'is-active' : ''">Bagi 2x</button>
                                <button type="button" @click="divideCicilanBaru(4)" class="quick-chip-btn text-[10.5px]" :class="selectedDivideBaru === 4 ? 'is-active' : ''">Bagi 4x</button>
                            </div>
                        </div>

                        <!-- Sumber Akun Kas Pencairan -->
                        <div class="pg-kas-section">
                            <label class="pg-kas-section-title">
                                <i data-lucide="wallet" class="w-3.5 h-3.5 text-rose-700"></i>
                                <span>Sumber Kas Pencairan Pinjaman</span>
                                <span style="color:#e11d48;">*</span>
                            </label>

                            <input type="hidden" name="akun_kas_id" :value="selectedKasPencairanId" required>

                            <div class="pg-kas-grid custom-scrollbar">
                                <template x-for="acc in (cashAccounts || [])" :key="acc.id">
                                    <button type="button" 
                                            @click.prevent.stop="selectKasPencairan(acc.id)"
                                            :disabled="totalPinjamRaw > 0 && acc.saldo < totalPinjamRaw"
                                            :class="{
                                                'is-selected': String(selectedKasPencairanId) === String(acc.id),
                                                'is-disabled': (totalPinjamRaw > 0 && acc.saldo < totalPinjamRaw)
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
                                                    <strong :style="(totalPinjamRaw > 0 && acc.saldo < totalPinjamRaw) ? 'color:#dc2626;font-weight:700;' : 'color:var(--color-ink,#0f172a);font-weight:600;'" 
                                                            class="font-mono ml-0.5" 
                                                            x-text="formatRupiah(acc.saldo)">
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Alasan / Keterangan -->
                        <div>
                            <label class="form-label" style="display:block;margin-bottom:6px;">
                                Alasan / Keterangan Pinjaman
                            </label>
                            <input type="text" 
                                   name="keterangan" 
                                   x-model="formKeteranganPinjam"
                                   placeholder="Contoh: Keperluan mendesak keluarga..." 
                                   class="form-input text-xs w-full"
                                   style="height:38px; border-radius:var(--rounded-md, 8px);">

                            <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                <button type="button" @click="formKeteranganPinjam = 'Kebutuhan Mendesak Keluarga'" class="quick-chip-btn text-[10.5px]">Kebutuhan Keluarga</button>
                                <button type="button" @click="formKeteranganPinjam = 'Biaya Pengobatan / Medis'" class="quick-chip-btn text-[10.5px]">Biaya Medis</button>
                                <button type="button" @click="formKeteranganPinjam = 'Pendidikan & Sekolah Anak'" class="quick-chip-btn text-[10.5px]">Pendidikan Anak</button>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="closeModalTambahPinjaman()" class="btn btn-secondary modal-btn-cancel-desktop">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary-maroon font-bold">
                            <i data-lucide="save"></i>
                            <span>Simpan Pinjaman</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- ========================================================================= -->
    <!-- 7. MODAL KONFIRMASI HAPUS PINJAMAN KASBON                                 -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="modalDeletePinjamanOpen" 
             x-cloak 
             class="modal-backdrop" 
             @click="modalDeletePinjamanOpen = false" 
             @keydown.escape.window="modalDeletePinjamanOpen = false">
            
            <div class="modal-box modal-box-md" style="max-width:440px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>

                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(225,29,72,0.12);color:#e11d48;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(225,29,72,0.25);">
                            <i data-lucide="trash-2" style="width:18px;height:18px;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div class="modal-title">Batalkan Pinjaman Kasbon</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Pinjaman belum pernah dicicil dan saldo kas akan dipulihkan</div>
                        </div>
                    </div>
                    <button type="button" @click="modalDeletePinjamanOpen = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <form method="POST" action="<?= Router::url('/kasbon/delete') ?>" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="id" :value="deletePinjamanId">

                    <div class="modal-body custom-scrollbar" style="display:flex;flex-direction:column;gap:12px;">
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                            Apakah Anda yakin ingin membatalkan dan menghapus pinjaman sebesar <strong class="font-mono text-rose-600" x-text="deletePinjamanNominal"></strong> untuk <strong class="text-slate-900 dark:text-slate-100"><?= htmlspecialchars($karyawan['nama_karyawan']) ?></strong>?
                        </p>
                        <div class="p-2.5 rounded-lg text-[11px] bg-rose-50 dark:bg-rose-950/40 text-rose-900 dark:text-rose-300 border border-rose-200/70 dark:border-rose-800/40">
                            Pinjaman ini belum memiliki riwayat pembayaran cicilan. Saldo kas pengeluaran akan otomatis dikembalikan.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="modalDeletePinjamanOpen = false" class="btn btn-secondary modal-btn-cancel-desktop">
                            Kembali
                        </button>
                        <button type="submit" class="btn btn-danger font-bold">
                            <i data-lucide="trash-2"></i>
                            <span>Ya, Hapus Pinjaman</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>

<script>
function kasbonDetailApp() {
    const maxSisa = <?= $sisaPinjaman ?>;

    return {
        tipeFilter: 'all', // 'all', 'pinjaman', 'potongan_payroll', 'bayar_manual'
        searchKeyword: '',
        modalBayarOpen: false,
        modalTambahOpen: false,
        modalDeletePinjamanOpen: false,
        deletePinjamanId: '',
        deletePinjamanNominal: '',

        cashAccounts: <?= json_encode(array_map(function($a) {
            return [
                'id' => (string)$a['id'],
                'nama_akun' => (string)$a['nama_akun'],
                'tipe_akun' => (string)$a['tipe_akun'],
                'saldo' => (float)$a['saldo_saat_ini'],
                'is_default_pos' => (bool)($a['is_default_pos'] ?? false),
            ];
        }, $akunKasList ?? []), JSON_UNESCAPED_UNICODE) ?>,

        // State Bayar FIFO
        selectedKasId: '<?= !empty($akunKasList[0]['id']) ? (string)$akunKasList[0]['id'] : '' ?>',
        bayarNominalRaw: maxSisa,
        bayarNominalDisplay: maxSisa > 0 ? maxSisa.toLocaleString('id-ID') : '',
        bayarKeterangan: 'Pembayaran Tunai Kasir',
        maxNominal: maxSisa,

        // State Tambah Pinjaman Baru
        formTanggalPinjam: '<?= date('Y-m-d') ?>',
        totalPinjamRaw: 0,
        totalPinjamDisplay: '',
        cicilanBaruRaw: 0,
        cicilanBaruDisplay: '',
        selectedDivideBaru: 2,
        formKeteranganPinjam: 'Kebutuhan Mendesak Keluarga',
        selectedKasPencairanId: '<?= !empty($akunKasList[0]['id']) ? (string)$akunKasList[0]['id'] : '' ?>',

        // Activity Items for live client filtering
        activities: <?= json_encode(array_map(function($idx, $act) {
            return [
                'index' => $idx + 1,
                'id' => (string)$act['id'],
                'tipe' => (string)$act['jenis_aktivitas'],
                'keywords' => strtolower($act['keterangan'] . ' ' . ($act['nama_akun_kas'] ?? '') . ' ' . ($act['nomor_payroll'] ?? '') . ' ' . $act['jenis_aktivitas'] . ' ' . $act['tanggal']),
            ];
        }, array_keys($activities), $activities), JSON_UNESCAPED_UNICODE) ?>,

        get visibleActivityCount() {
            if (!this.activities || this.activities.length === 0) return 0;
            return this.activities.filter(item => this.isActivityVisible(item.keywords, item.tipe)).length;
        },

        init() {
            this.$watch('tipeFilter', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$watch('searchKeyword', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        // Helper Kas Penerima
        selectKas(id) {
            this.selectedKasId = String(id || '');
        },

        // Helper Kas Pencairan
        selectKasPencairan(id) {
            const strId = String(id || '');
            const acc = this.cashAccounts.find(a => String(a.id) === strId);
            if (acc && this.totalPinjamRaw > 0 && acc.saldo < this.totalPinjamRaw) {
                if (window.toast) window.toast('Saldo akun ' + acc.nama_akun + ' tidak mencukupi nominal pinjaman.', 'warning');
                return;
            }
            this.selectedKasPencairanId = strId;
        },

        // Modal Bayar FIFO Handlers
        openModalBayar() {
            this.setBayarNominal(this.maxNominal);
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

        handleBayarSubmit(e) {
            if (!this.selectedKasId) {
                if (window.toast) window.toast('Pilih salah satu akun kas penampung pembayaran.', 'warning');
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

        // Modal Tambah Pinjaman Handlers
        openModalTambahPinjaman() {
            this.formTanggalPinjam = '<?= date('Y-m-d') ?>';
            this.totalPinjamRaw = 0;
            this.totalPinjamDisplay = '';
            this.cicilanBaruRaw = 0;
            this.cicilanBaruDisplay = '';
            this.selectedDivideBaru = 2;
            this.formKeteranganPinjam = 'Kebutuhan Mendesak Keluarga';
            if (!this.selectedKasPencairanId && this.cashAccounts && this.cashAccounts.length > 0) {
                const defaultPos = this.cashAccounts.find(a => a.is_default_pos);
                this.selectedKasPencairanId = defaultPos ? String(defaultPos.id) : String(this.cashAccounts[0].id);
            }
            this.modalTambahOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        closeModalTambahPinjaman() {
            this.modalTambahOpen = false;
        },

        onTotalPinjamInput(e) {
            let raw = String(e.target.value || '').replace(/\D/g, '');
            let val = parseInt(raw, 10) || 0;
            this.totalPinjamRaw = val;
            this.totalPinjamDisplay = val > 0 ? val.toLocaleString('id-ID') : '';
            if (this.selectedDivideBaru > 0) {
                this.divideCicilanBaru(this.selectedDivideBaru);
            }
        },

        setTotalPinjam(amt) {
            let val = Number(amt) || 0;
            this.totalPinjamRaw = val;
            this.totalPinjamDisplay = val > 0 ? val.toLocaleString('id-ID') : '';
            this.divideCicilanBaru(2);
        },

        resetTotalPinjam() {
            this.totalPinjamRaw = 0;
            this.totalPinjamDisplay = '';
            this.cicilanBaruRaw = 0;
            this.cicilanBaruDisplay = '';
            this.selectedDivideBaru = null;
        },

        onCicilanBaruInput(e) {
            let raw = String(e.target.value || '').replace(/\D/g, '');
            let val = parseInt(raw, 10) || 0;
            this.cicilanBaruRaw = val;
            this.cicilanBaruDisplay = val > 0 ? val.toLocaleString('id-ID') : '';
            this.selectedDivideBaru = null;
        },

        divideCicilanBaru(count) {
            this.selectedDivideBaru = count;
            if (this.totalPinjamRaw > 0 && count > 0) {
                let part = Math.round(this.totalPinjamRaw / count);
                this.cicilanBaruRaw = part;
                this.cicilanBaruDisplay = part > 0 ? part.toLocaleString('id-ID') : '';
            } else {
                this.cicilanBaruRaw = 0;
                this.cicilanBaruDisplay = '';
            }
        },

        handleFormTambahSubmit(e) {
            if (!this.totalPinjamRaw || this.totalPinjamRaw <= 0) {
                if (window.toast) window.toast('Total pinjaman harus lebih dari Rp 0.', 'warning');
                e.preventDefault();
                return false;
            }
            if (!this.selectedKasPencairanId) {
                if (window.toast) window.toast('Pilih salah satu sumber kas pencairan.', 'warning');
                e.preventDefault();
                return false;
            }
            const acc = this.cashAccounts.find(a => String(a.id) === String(this.selectedKasPencairanId));
            if (acc && acc.saldo < this.totalPinjamRaw) {
                if (window.toast) window.toast('Saldo kas terpilih tidak mencukupi nominal pinjaman.', 'error');
                e.preventDefault();
                return false;
            }
            return true;
        },

        // Delete Pinjaman Confirm
        confirmDeletePinjaman(id, nominal) {
            this.deletePinjamanId = id;
            this.deletePinjamanNominal = nominal;
            this.modalDeletePinjamanOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        formatRupiah(num) {
            if (typeof window.formatRupiah === 'function') {
                return window.formatRupiah(num);
            }
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        isActivityVisible(keywords, tipe) {
            if (this.tipeFilter !== 'all' && this.tipeFilter !== tipe) return false;
            if (!this.searchKeyword.trim()) return true;
            const q = this.searchKeyword.toLowerCase().trim();
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

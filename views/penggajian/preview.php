<?php
/**
 * views/penggajian/preview.php
 * Tinjau, Koreksi, & Otorisasi Ledger Payroll Run Keren One ERP
 * 100% Selaras DNA Desain, Modern Enterprise ERP / Supabase Clean UI
 * Mengadopsi struktur 4 Pilar Finansial & Interaktivitas Kaya dari Projek Salary
 */
use App\Helpers\CSRF;
use App\Helpers\Format;
use App\Core\Auth;
use App\Core\Router;

ob_start();

$totalPotonganTabunganAll = (float)($totalPotonganTabunganAll ?? $totalPotonganTabunganPayroll ?? 0);
$totalPenarikanTabunganAll = (float)($totalPenarikanTabunganAll ?? $totalPenarikanTabunganPayroll ?? 0);
$totalGajiBersih = (float)($totalGajiBersih ?? 0);
$totalKebutuhanKasOperasional = $totalGajiBersih + $totalPotonganTabunganAll;

$totalGajiTunai = (float)($totalGajiTunai ?? 0);
$countTunai = (int)($countTunai ?? 0);
$totalGajiTransfer = (float)($totalGajiTransfer ?? 0);
$countTransfer = (int)($countTransfer ?? 0);
$potonganTabunganTunai = (float)($potonganTabunganTunai ?? 0);
$potonganTabunganTransfer = (float)($potonganTabunganTransfer ?? 0);

// Periode rincian per tipe (Borongan / Bulanan) dari options_json dengan bulan singkat (e.g. 5 Okt – 10 Okt 2026)
$opts = json_decode((string)($run['options_json'] ?? ''), true) ?: [];

$formatRangeShort = function(?string $start, ?string $end): string {
    if (empty($start) || empty($end)) return '-';
    $t1 = strtotime($start);
    $t2 = strtotime($end);
    if (!$t1 || !$t2) return "{$start} – {$end}";

    $bulanShort = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    $d1 = date('j', $t1);
    $m1 = (int)date('n', $t1);
    $y1 = date('Y', $t1);

    $d2 = date('j', $t2);
    $m2 = (int)date('n', $t2);
    $y2 = date('Y', $t2);

    $b1 = $bulanShort[$m1] ?? '';
    $b2 = $bulanShort[$m2] ?? '';

    if ($start === $end) {
        return "{$d1} {$b1} {$y1}";
    }
    if ($y1 === $y2) {
        return "{$d1} {$b1} – {$d2} {$b2} {$y1}";
    }
    return "{$d1} {$b1} {$y1} – {$d2} {$b2} {$y2}";
};

$periodLabels = [
    'borongan' => (isset($opts['borongan']['start']) && isset($opts['borongan']['end']))
        ? $formatRangeShort($opts['borongan']['start'], $opts['borongan']['end'])
        : $formatRangeShort($run['periode_awal'], $run['periode_akhir']),
    'bulanan' => (isset($opts['bulanan']['start']) && isset($opts['bulanan']['end']))
        ? $formatRangeShort($opts['bulanan']['start'], $opts['bulanan']['end'])
        : $formatRangeShort($run['periode_awal'], $run['periode_akhir']),
];
?>

<style>
/* ==========================================================================
   Payroll Preview Specific Styles (Design System Harmonized)
   ========================================================================== */

/* Alpine Cloak & Display-None Override Guard against CSS .flex !important */
[x-cloak],
[style*="display: none"],
[style*="display:none"] {
    display: none !important;
}

/* Pulsing Status Dot */
.dot-pulse {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: currentColor;
    animation: pulseDot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulseDot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: .4; transform: scale(0.85); }
}

/* Tab Pills Filter Group */
.tab-pill-group {
    display: inline-flex;
    align-items: center;
    background: var(--color-canvas-soft, #f8fafc);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: var(--rounded-md, 8px);
    padding: 3px;
    gap: 3px;
    overflow-x: auto;
    max-width: 100%;
}
.tab-pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: var(--rounded-sm, 6px);
    font-size: 12px;
    font-weight: 600;
    color: var(--color-ink-mute);
    background: transparent;
    border: 1px solid transparent !important;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    user-select: none;
    outline: none !important;
    appearance: none;
    -webkit-appearance: none;
    -webkit-tap-highlight-color: transparent;
}
.tab-pill-btn:hover {
    color: var(--color-ink);
    background: rgba(0, 0, 0, 0.04);
}
.tab-pill-btn:focus,
.tab-pill-btn:focus-visible,
.tab-pill-btn:active {
    outline: none !important;
    box-shadow: none;
}
.dark .tab-pill-btn:hover {
    color: var(--color-ink);
    background: rgba(255, 255, 255, 0.06);
}
.tab-pill-btn.is-active-primary {
    background: var(--color-canvas, #ffffff) !important;
    color: var(--color-ink, #0f172a) !important;
    border: 1px solid var(--color-hairline, #e2e8f0) !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
    font-weight: 700;
}
.dark .tab-pill-btn.is-active-primary {
    background: #334155 !important;
    color: #f8fafc !important;
    border-color: #475569 !important;
}
.tab-pill-btn.is-active-amber {
    background: #fef3c7 !important;
    color: #b45309 !important;
    border: 1px solid #fde68a !important;
    font-weight: 700;
}
.dark .tab-pill-btn.is-active-amber {
    background: rgba(245, 158, 11, 0.2) !important;
    color: #fbbf24 !important;
    border-color: rgba(245, 158, 11, 0.35) !important;
}
.tab-pill-btn.is-active-sky {
    background: #eff6ff !important;
    color: #1d4ed8 !important;
    border: 1px solid #bfdbfe !important;
    font-weight: 700;
}
.dark .tab-pill-btn.is-active-sky {
    background: rgba(59, 130, 246, 0.2) !important;
    color: #60a5fa !important;
    border-color: rgba(59, 130, 246, 0.35) !important;
}
.tab-pill-btn.is-active-emerald {
    background: #ecfdf5 !important;
    color: #047857 !important;
    border: 1px solid #a7f3d0 !important;
    font-weight: 700;
}
.dark .tab-pill-btn.is-active-emerald {
    background: rgba(16, 185, 129, 0.2) !important;
    color: #34d399 !important;
    border-color: rgba(16, 185, 129, 0.35) !important;
}
.tab-pill-btn.is-active-purple {
    background: #f5f3ff !important;
    color: #6d28d9 !important;
    border: 1px solid #ddd6fe !important;
    font-weight: 700;
}
.dark .tab-pill-btn.is-active-purple {
    background: rgba(139, 92, 246, 0.2) !important;
    color: #a78bfa !important;
    border-color: rgba(139, 92, 246, 0.35) !important;
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
    font-weight: 700;
    font-family: var(--font-mono);
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    color: inherit;
    line-height: 1;
}
.dark .tab-pill-counter {
    background: #0f172a;
    border-color: #334155;
}

/* Pillar Cells (4 Pilar Finansial ala Salary) */
.pillar-cell {
    min-width: 175px;
    vertical-align: top;
    padding-top: 10px !important;
    padding-bottom: 10px !important;
}
.pillar-total {
    font-weight: 700;
    font-family: var(--font-mono);
    font-size: 13px;
    color: var(--color-ink);
    padding-bottom: 4px;
    margin-bottom: 6px;
    border-bottom: 1px solid var(--color-hairline);
    text-align: right;
}
.pillar-list {
    display: flex;
    flex-direction: column;
    gap: 3px;
    font-size: 11.5px;
}
.pillar-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    color: var(--color-ink-mute);
}
.pillar-row .val {
    font-family: var(--font-mono);
    font-weight: 600;
    white-space: nowrap;
}

/* Group Row Header Separator (Distinct Thematic Colors) */
.group-row td {
    padding: 10px 16px !important;
}

/* Group Row Header Separator (Desktop) */
.group-row td {
    padding: 10px 16px !important;
}

.group-row-bulanan td {
    background-color: #eff6ff !important;
    border-top: 1.5px solid #bfdbfe !important;
    border-bottom: 1.5px solid #bfdbfe !important;
    border-left: 4px solid #2563eb !important;
    color: #1e40af !important;
}
.dark .group-row-bulanan td {
    background-color: rgba(37, 99, 235, 0.16) !important;
    border-top-color: rgba(59, 130, 246, 0.35) !important;
    border-bottom-color: rgba(59, 130, 246, 0.35) !important;
    border-left-color: #3b82f6 !important;
    color: #93c5fd !important;
}

.group-row-borongan td {
    background-color: #fffbeb !important;
    border-top: 1.5px solid #fde68a !important;
    border-bottom: 1.5px solid #fde68a !important;
    border-left: 4px solid #d97706 !important;
    color: #92400e !important;
}
.dark .group-row-borongan td {
    background-color: rgba(217, 119, 6, 0.16) !important;
    border-top-color: rgba(245, 158, 11, 0.35) !important;
    border-bottom-color: rgba(245, 158, 11, 0.35) !important;
    border-left-color: #f59e0b !important;
    color: #fde68a !important;
}

/* Group Header Separator (Mobile Specific - Clean Single Line & Sleek) */
.group-header-bulanan-mob {
    background-color: #eff6ff !important;
    border-top: 1px solid #bfdbfe !important;
    border-bottom: 1px solid #bfdbfe !important;
    border-left: 3.5px solid #2563eb !important;
    color: #1e40af !important;
    padding: 7px 12px !important;
}
.dark .group-header-bulanan-mob {
    background-color: rgba(37, 99, 235, 0.16) !important;
    border-top-color: rgba(59, 130, 246, 0.35) !important;
    border-bottom-color: rgba(59, 130, 246, 0.35) !important;
    border-left-color: #3b82f6 !important;
    color: #93c5fd !important;
}

.group-header-borongan-mob {
    background-color: #fffbeb !important;
    border-top: 1px solid #fde68a !important;
    border-bottom: 1px solid #fde68a !important;
    border-left: 3.5px solid #d97706 !important;
    color: #92400e !important;
    padding: 7px 12px !important;
    margin-top: 8px;
}
.dark .group-header-borongan-mob {
    background-color: rgba(217, 119, 6, 0.16) !important;
    border-top-color: rgba(245, 158, 11, 0.35) !important;
    border-bottom-color: rgba(245, 158, 11, 0.35) !important;
    border-left-color: #f59e0b !important;
    color: #fde68a !important;
}

.group-pill-bulanan {
    background: #dbeafe !important;
    color: #1e40af !important;
    border: 1px solid #bfdbfe !important;
    font-weight: 700;
}
.dark .group-pill-bulanan {
    background: rgba(37, 99, 235, 0.3) !important;
    color: #bfdbfe !important;
    border-color: rgba(59, 130, 246, 0.4) !important;
}

.group-period-bulanan {
    background: rgba(255, 255, 255, 0.88) !important;
    color: #1e40af !important;
    border: 1px solid #bfdbfe !important;
    font-size: 10px;
    padding: 2.5px 8px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    line-height: 1;
    white-space: nowrap;
}
.dark .group-period-bulanan {
    background: rgba(15, 23, 42, 0.75) !important;
    color: #93c5fd !important;
    border-color: rgba(59, 130, 246, 0.35) !important;
}

.group-pill-borongan {
    background: #fef3c7 !important;
    color: #92400e !important;
    border: 1px solid #fde68a !important;
    font-weight: 700;
}
.dark .group-pill-borongan {
    background: rgba(217, 119, 6, 0.3) !important;
    color: #fde68a !important;
    border-color: rgba(245, 158, 11, 0.4) !important;
}

.group-period-borongan {
    background: rgba(255, 255, 255, 0.88) !important;
    color: #92400e !important;
    border: 1px solid #fde68a !important;
    font-size: 10px;
    padding: 2.5px 8px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    line-height: 1;
    white-space: nowrap;
}
.dark .group-period-borongan {
    background: rgba(15, 23, 42, 0.75) !important;
    color: #fde68a !important;
    border-color: rgba(245, 158, 11, 0.35) !important;
}

/* Mobile Card 4-Pillar Accordion Toggle */
.mobile-accordion-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6.5px 10px;
    border-radius: var(--rounded-md, 8px);
    background: var(--color-canvas-soft, #f8fafc);
    border: 1px solid var(--color-hairline, #e2e8f0);
    color: var(--color-ink-mute, #64748b);
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    outline: none;
    transition: all 0.15s ease;
    user-select: none;
}
.mobile-accordion-toggle:hover {
    background: #f1f5f9;
    color: var(--color-ink, #0f172a);
    border-color: #cbd5e1;
}
.mobile-accordion-toggle.is-active {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #2563eb;
}
.dark .mobile-accordion-toggle {
    background: rgba(30, 41, 59, 0.6);
    border-color: #334155;
    color: #94a3b8;
}
.dark .mobile-accordion-toggle:hover {
    background: #334155;
    color: #f1f5f9;
}
.dark .mobile-accordion-toggle.is-active {
    background: rgba(37, 99, 235, 0.2);
    border-color: rgba(59, 130, 246, 0.4);
    color: #93c5fd;
}
.mobile-accordion-toggle .chevron-arrow {
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    color: currentColor;
    opacity: 0.8;
}
.rotate-180 {
    transform: rotate(180deg) !important;
}

/* Modal Split Card & Net Banner */
.money-panel {
    padding: 14px;
    border-radius: var(--rounded-md, 8px);
    border: 1px solid var(--color-hairline);
    background: var(--color-canvas-soft);
}
.money-panel-danger {
    padding: 14px;
    border-radius: var(--rounded-md, 8px);
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #991b1b;
}
.dark .money-panel-danger {
    border-color: rgba(239, 68, 68, 0.35);
    background: rgba(239, 68, 68, 0.12);
    color: #fca5a5;
}
.net-banner {
    padding: 14px 18px;
    border-radius: var(--rounded-md, 8px);
    border: 1px solid #a7f3d0;
    background: #ecfdf5;
    text-align: center;
    transition: all 0.2s ease;
}
.net-banner.is-negative {
    border-color: #fecaca !important;
    background: #fef2f2 !important;
}
.dark .net-banner {
    border-color: rgba(16, 185, 129, 0.35);
    background: rgba(16, 185, 129, 0.12);
}
.dark .net-banner.is-negative {
    border-color: rgba(239, 68, 68, 0.35) !important;
    background: rgba(239, 68, 68, 0.15) !important;
}

/* Warning / Info Banner Minimalis, Clean & Modern */
.payroll-warning-banner {
    padding: 12px 14px;
    border-radius: var(--rounded-md, 8px);
    background: #fffbeb !important;
    border: 1px solid #fde68a !important;
    display: flex;
    flex-direction: column;
    gap: 9px;
    box-shadow: 0 1px 2px rgba(217, 119, 6, 0.06);
}
.dark .payroll-warning-banner {
    background: rgba(245, 158, 11, 0.1) !important;
    border-color: rgba(245, 158, 11, 0.28) !important;
    box-shadow: none;
}
.payroll-warning-header {
    display: flex;
    align-items: flex-start;
    gap: 9px;
}
.payroll-warning-icon {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    background: #fef3c7;
    color: #d97706;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}
.dark .payroll-warning-icon {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
}
.payroll-warning-title {
    font-size: 12px;
    font-weight: 700;
    color: #92400e;
    line-height: 1.3;
}
.dark .payroll-warning-title {
    color: #fde68a;
}
.payroll-warning-desc {
    font-size: 11px;
    color: #b45309;
    line-height: 1.35;
    margin-top: 1.5px;
}
.dark .payroll-warning-desc {
    color: #fcd34d;
}

/* Row item defisit saldo kas - Clean Card */
.payroll-deficit-row {
    padding: 8px 10px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(245, 158, 11, 0.3);
    display: flex;
    flex-direction: column;
    gap: 5px;
    transition: all 0.15s ease;
}
.dark .payroll-deficit-row {
    background: rgba(15, 23, 42, 0.5);
    border-color: rgba(245, 158, 11, 0.2);
}
.payroll-deficit-row-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.payroll-deficit-acc-name {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--color-ink, #0f172a);
    line-height: 1.3;
}
.dark .payroll-deficit-acc-name {
    color: #f1f5f9;
}
.payroll-deficit-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 3px;
    line-height: 1;
    font-size: 10px;
    font-weight: 700;
    font-family: var(--font-mono);
    font-variant-numeric: tabular-nums;
    padding: 3px 7px;
    border-radius: 9999px;
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    white-space: nowrap;
    flex-shrink: 0;
}
.dark .payroll-deficit-pill {
    background: rgba(220, 38, 38, 0.2);
    color: #fca5a5;
    border-color: rgba(239, 68, 68, 0.35);
}
.payroll-deficit-row-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 10.5px;
    color: var(--color-ink-mute, #64748b);
    padding-top: 4px;
    border-top: 1px solid rgba(245, 158, 11, 0.15);
    font-family: var(--font-sans);
}
.dark .payroll-deficit-row-bottom {
    border-top-color: rgba(255, 255, 255, 0.08);
    color: #94a3b8;
}

/* Modal Responsiveness Safeguards for Mobile (HP) */
@media (max-width: 640px) {
    .modal-box .modal-body {
        padding: 14px 14px !important;
    }
    .modal-box .modal-header {
        padding: 14px 14px 12px 14px !important;
    }
    .modal-box .modal-footer {
        padding: 12px 14px !important;
    }
}

/* ==========================================================================
   Header Badges Kebutuhan Akun Kas (Pill Standar & Clean)
   ========================================================================== */
.payroll-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 8px;
}
.payroll-section-title {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 700;
    color: var(--color-ink, #0f172a);
    line-height: 1.4;
}
.dark .payroll-section-title {
    color: #f1f5f9;
}
.payroll-kebutuhan-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 500;
    line-height: 1.35;
    white-space: nowrap;
    width: auto;
    max-width: 100%;
    align-self: flex-start;
    box-sizing: border-box;
    vertical-align: middle;
}
@media (min-width: 640px) {
    .payroll-kebutuhan-badge {
        align-self: center;
    }
}
.payroll-kebutuhan-badge.is-tunai {
    background-color: #ecfdf5 !important;
    color: #047857 !important;
    border: 1px solid #a7f3d0 !important;
}
.dark .payroll-kebutuhan-badge.is-tunai {
    background-color: rgba(16, 185, 129, 0.15) !important;
    color: #34d399 !important;
    border-color: rgba(16, 185, 129, 0.35) !important;
}
.payroll-kebutuhan-badge.is-transfer {
    background-color: #eff6ff !important;
    color: #1d4ed8 !important;
    border: 1px solid #bfdbfe !important;
}
.dark .payroll-kebutuhan-badge.is-transfer {
    background-color: rgba(59, 130, 246, 0.15) !important;
    color: #60a5fa !important;
    border-color: rgba(59, 130, 246, 0.35) !important;
}
.payroll-kebutuhan-badge.is-tabungan {
    background-color: #f5f3ff !important;
    color: #6d28d9 !important;
    border: 1px solid #ddd6fe !important;
}
.dark .payroll-kebutuhan-badge.is-tabungan {
    background-color: rgba(139, 92, 246, 0.15) !important;
    color: #a78bfa !important;
    border-color: rgba(139, 92, 246, 0.35) !important;
}
.payroll-kebutuhan-badge .badge-label {
    font-weight: 500;
    color: inherit;
}
.payroll-kebutuhan-badge .badge-amount {
    font-family: var(--font-mono);
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: inherit;
}
.payroll-kebutuhan-badge .badge-sep {
    opacity: 0.5;
    font-size: 10px;
}
.payroll-kebutuhan-badge .badge-count {
    font-weight: 500;
    color: inherit;
    opacity: 0.9;
}

/* Banner Dampak Otorisasi Biru Soft */
.payroll-info-banner-blue {
    padding: 10px 14px;
    border-radius: var(--rounded-md, 8px);
    background-color: #f0f7ff !important;
    border: 1px solid #bfdbfe !important;
    color: #1e40af !important;
    font-size: 11.5px;
    line-height: 1.5;
}
.payroll-info-banner-blue .info-title {
    font-weight: 700;
    color: #1d4ed8 !important;
    font-size: 11.5px;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 5px;
}
.payroll-info-banner-blue ul {
    margin: 0;
    padding-left: 18px;
    list-style-type: disc;
    color: #1e3a8a;
}
.payroll-info-banner-blue li {
    margin-bottom: 2px;
}
.dark .payroll-info-banner-blue {
    background-color: rgba(59, 130, 246, 0.12) !important;
    border-color: rgba(59, 130, 246, 0.35) !important;
    color: #93c5fd !important;
}
.dark .payroll-info-banner-blue .info-title {
    color: #60a5fa !important;
}
.dark .payroll-info-banner-blue ul {
    color: #bfdbfe;
}

/* ==========================================================================
   Modern Searchable Kas Account Dropdown in Approval Modal
   ========================================================================== */
.payroll-dropdown-trigger {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    min-height: 52px;
    border-radius: var(--rounded-md, 8px);
    border: 1.5px solid var(--color-hairline, #cbd5e1);
    background: var(--color-canvas, #ffffff);
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
    text-align: left;
    width: 100%;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
}
.payroll-dropdown-trigger:hover {
    border-color: #94a3b8;
    background: var(--color-canvas-soft, #f8fafc);
}
.dark .payroll-dropdown-trigger {
    background: #1e293b;
    border-color: #334155;
}
.dark .payroll-dropdown-trigger:hover {
    border-color: #475569;
    background: #273549;
}
.payroll-dropdown-trigger.is-open {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
}
.dark .payroll-dropdown-trigger.is-open {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3) !important;
}

.payroll-dropdown-menu {
    position: absolute;
    top: calc(100% + 5px);
    left: 0;
    right: 0;
    z-index: 1050;
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #cbd5e1);
    border-radius: 12px;
    box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.14), 0 6px 12px -3px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    animation: payrollDropdownFade 0.15s cubic-bezier(0.16, 1, 0.3, 1);
}
.dark .payroll-dropdown-menu {
    background: #0f172a;
    border-color: #334155;
    box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.45);
}
@keyframes payrollDropdownFade {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}

.payroll-dropdown-search-wrap {
    padding: 8px 10px;
    border-bottom: 1px solid var(--color-hairline, #e2e8f0);
    background: var(--color-canvas-soft, #f8fafc);
}
.dark .payroll-dropdown-search-wrap {
    background: #1e293b;
    border-color: #334155;
}
.payroll-dropdown-search-input {
    height: 36px;
    width: 100%;
    padding: 0 32px 0 34px;
    font-size: 12px;
    border-radius: 8px;
    border: 1px solid var(--color-hairline-strong, #cbd5e1);
    background: var(--color-canvas, #ffffff);
    color: var(--color-ink, #0f172a);
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.payroll-dropdown-search-input:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.18);
}
.dark .payroll-dropdown-search-input {
    background: #0f172a;
    border-color: #475569;
    color: #f1f5f9;
}
.dark .payroll-dropdown-search-input:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.28);
}

.payroll-search-icon {
    position: absolute;
    left: 10px;
    width: 14px;
    height: 14px;
    color: #94a3b8;
    pointer-events: none;
}
.payroll-search-clear {
    position: absolute;
    right: 8px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
}
.payroll-search-clear:hover {
    color: #475569;
    background: rgba(148, 163, 184, 0.2);
}
.payroll-search-clear svg {
    width: 12px;
    height: 12px;
}

.payroll-dropdown-options-list {
    max-height: 210px;
    overflow-y: auto;
    overscroll-behavior: contain;
}

.payroll-dropdown-option {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 9px 12px;
    cursor: pointer;
    user-select: none;
    border-bottom: 1px solid rgba(226, 232, 240, 0.6);
    transition: background-color 0.12s ease;
}
.payroll-dropdown-option:last-child {
    border-bottom: none;
}
.payroll-dropdown-option:hover {
    background-color: var(--color-canvas-soft, #f8fafc);
}
.dark .payroll-dropdown-option {
    border-bottom-color: rgba(51, 65, 85, 0.5);
}
.dark .payroll-dropdown-option:hover {
    background-color: #1e293b;
}
.payroll-dropdown-option.is-selected {
    background-color: #f0fdf4 !important;
}
.dark .payroll-dropdown-option.is-selected {
    background-color: rgba(16, 185, 129, 0.15) !important;
}

.payroll-kas-type-pill {
    font-size: 9.5px;
    font-weight: 600;
    padding: 1px 6px;
    border-radius: 9999px;
    background: rgba(100, 116, 139, 0.12);
    color: #64748b;
}
.dark .payroll-kas-type-pill {
    background: rgba(148, 163, 184, 0.18);
    color: #94a3b8;
}
.payroll-sufficient-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    font-size: 10px;
    font-weight: 700;
    padding: 2.5px 7px;
    border-radius: 9999px;
    background: #d1fae5;
    color: #065f46;
}
.dark .payroll-sufficient-badge {
    background: rgba(16, 185, 129, 0.22);
    color: #6ee7b7;
}
.payroll-deficit-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    font-size: 10px;
    font-weight: 700;
    padding: 2.5px 7px;
    border-radius: 9999px;
    background: #fee2e2;
    color: #b91c1c;
}
.dark .payroll-deficit-badge {
    background: rgba(239, 68, 68, 0.22);
    color: #fca5a5;
}
.payroll-selected-check {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #10b981;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.payroll-selected-check svg {
    width: 11px;
    height: 11px;
    stroke-width: 3;
}
.payroll-dropdown-empty {
    padding: 24px 16px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: var(--color-ink-mute, #94a3b8);
    font-size: 11.5px;
}
.payroll-dropdown-empty svg {
    width: 22px;
    height: 22px;
    color: #cbd5e1;
}
.dark .payroll-dropdown-empty svg {
    color: #475569;
}

/* Kas Icon Box */
.payroll-kas-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.payroll-kas-icon.is-tunai {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}
.dark .payroll-kas-icon.is-tunai {
    background: rgba(16, 185, 129, 0.18);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.32);
}
.payroll-kas-icon.is-bank {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}
.dark .payroll-kas-icon.is-bank {
    background: rgba(59, 130, 246, 0.18);
    color: #60a5fa;
    border-color: rgba(59, 130, 246, 0.32);
}

/* POS Tag */
.payroll-kas-pos-pill {
    padding: 1px 5px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 700;
    background: #d1fae5;
    color: #065f46;
    letter-spacing: 0.02em;
}
.dark .payroll-kas-pos-pill {
    background: rgba(16, 185, 129, 0.25);
    color: #6ee7b7;
}

/* Saldo highlight */
.payroll-kas-saldo-insufficient {
    color: #dc2626 !important;
    font-weight: 700 !important;
}
.dark .payroll-kas-saldo-insufficient {
    color: #f87171 !important;
}
.payroll-kas-saldo-sufficient {
    color: var(--color-ink, #0f172a) !important;
    font-weight: 600 !important;
}
.dark .payroll-kas-saldo-sufficient {
    color: #e2e8f0 !important;
}

/* Radio Cards: Metode Pembayaran Gaji (Prominent & High-Contrast) */
.payroll-method-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
@media (max-width: 540px) {
    .payroll-method-grid {
        grid-template-columns: 1fr;
    }
}
.payroll-method-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: var(--rounded-lg, 10px);
    border: 1.5px solid var(--color-hairline, #cbd5e1);
    background: var(--color-canvas, #ffffff);
    cursor: pointer;
    user-select: none;
    transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    text-align: left;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
    width: 100%;
}
.payroll-method-card:hover {
    border-color: #94a3b8;
    background: var(--color-canvas-soft, #f8fafc);
}
.dark .payroll-method-card {
    background: #1e293b;
    border-color: #334155;
}
.dark .payroll-method-card:hover {
    border-color: #475569;
    background: #273549;
}
/* Active Tunai (Emerald) */
.payroll-method-card.is-active-tunai {
    border-color: #059669 !important;
    background: #f0fdf4 !important;
    box-shadow: 0 0 0 2.5px rgba(16, 185, 129, 0.22) !important;
}
.dark .payroll-method-card.is-active-tunai {
    border-color: #10b981 !important;
    background: rgba(16, 185, 129, 0.12) !important;
    box-shadow: 0 0 0 2.5px rgba(16, 185, 129, 0.28) !important;
}
/* Active Transfer (Blue) */
.payroll-method-card.is-active-transfer {
    border-color: #2563eb !important;
    background: #eff6ff !important;
    box-shadow: 0 0 0 2.5px rgba(37, 99, 235, 0.22) !important;
}
.dark .payroll-method-card.is-active-transfer {
    border-color: #3b82f6 !important;
    background: rgba(59, 130, 246, 0.12) !important;
    box-shadow: 0 0 0 2.5px rgba(59, 130, 246, 0.28) !important;
}
/* Method Icon Badge */
.payroll-method-icon {
    width: 38px;
    height: 38px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.15s ease;
}
.payroll-method-card:hover .payroll-method-icon {
    transform: scale(1.05);
}
.payroll-method-icon.is-tunai {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}
.dark .payroll-method-icon.is-tunai {
    background: rgba(16, 185, 129, 0.18);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.32);
}
.payroll-method-icon.is-transfer {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}
.dark .payroll-method-icon.is-transfer {
    background: rgba(59, 130, 246, 0.18);
    color: #60a5fa;
    border-color: rgba(59, 130, 246, 0.32);
}

/* Card Bank Master Terverifikasi */
.payroll-bank-card {
    padding: 10px 14px;
    border-radius: var(--rounded-md, 8px);
    border: 1px solid #bfdbfe;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.dark .payroll-bank-card {
    background: #0f172a;
    border-color: rgba(59, 130, 246, 0.35);
}

/* Emblem / Logo Bank Modern */
.payroll-bank-emblem {
    width: 44px;
    height: 44px;
    min-width: 44px;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.25);
    user-select: none;
    transition: transform 0.15s ease;
}
.payroll-bank-emblem:hover {
    transform: scale(1.04);
}

/* Badge Rekening Master Pill */
.payroll-bank-master-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    font-size: 10px;
    font-weight: 700;
    padding: 2.5px 7px;
    border-radius: 9999px;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}
.dark .payroll-bank-master-pill {
    background: rgba(37, 99, 235, 0.2);
    color: #93c5fd;
    border-color: rgba(59, 130, 246, 0.4);
}

/* Tombol Aksi Ubah Rekening (Sleek Smooth Pill) */
.payroll-bank-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 6px 13px;
    border-radius: 9999px;
    font-size: 11.5px;
    font-weight: 600;
    color: #2563eb;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    cursor: pointer;
    transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    appearance: none;
    -webkit-appearance: none;
    outline: none;
    line-height: 1;
    flex-shrink: 0;
}
.payroll-bank-action-btn:hover {
    background: #dbeafe;
    border-color: #93c5fd;
    color: #1d4ed8;
    transform: translateY(-0.5px);
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.12);
}
.dark .payroll-bank-action-btn {
    background: rgba(37, 99, 235, 0.18);
    border-color: rgba(59, 130, 246, 0.35);
    color: #93c5fd;
}
.dark .payroll-bank-action-btn:hover {
    background: rgba(37, 99, 235, 0.3);
    color: #bfdbfe;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.25);
}

/* Panel Koreksi Custom Rekening */
.payroll-bank-custom-panel {
    padding: 10px 12px;
    border-radius: var(--rounded-md, 8px);
    border: 1px dashed #93c5fd;
    background: rgba(239, 246, 255, 0.5);
}
.dark .payroll-bank-custom-panel {
    border-color: rgba(59, 130, 246, 0.4);
    background: rgba(30, 58, 138, 0.15);
}

/* Tombol Reset ke Master */
.payroll-bank-reset-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4.5px;
    padding: 3.5px 9px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    color: #2563eb;
    background: transparent;
    border: 1px dashed rgba(59, 130, 246, 0.45);
    cursor: pointer;
    transition: all 0.15s ease;
    appearance: none;
    -webkit-appearance: none;
    outline: none;
    line-height: 1;
}
.payroll-bank-reset-btn:hover {
    background: #eff6ff;
    border-color: #2563eb;
    color: #1d4ed8;
}
.dark .payroll-bank-reset-btn {
    color: #93c5fd;
    border-color: rgba(96, 165, 250, 0.45);
}
.dark .payroll-bank-reset-btn:hover {
    background: rgba(37, 99, 235, 0.2);
    color: #ffffff;
}

/* Badge Hint Drag-to-Scroll */
.payroll-scroll-hint-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5.5px;
    padding: 3.5px 11px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 500;
    line-height: 1;
    white-space: nowrap;
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    color: var(--color-ink-mute);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    user-select: none;
    transition: all 0.15s ease;
}
.payroll-scroll-hint-badge i,
.payroll-scroll-hint-badge svg {
    width: 12px;
    height: 12px;
    flex-shrink: 0;
    color: #2563eb;
    stroke-width: 2.2;
    display: block;
}
.dark .payroll-scroll-hint-badge {
    background: var(--color-surface);
    border-color: var(--color-hairline);
    color: var(--color-ink-mute);
}
.dark .payroll-scroll-hint-badge i,
.dark .payroll-scroll-hint-badge svg {
    color: #60a5fa;
}
</style>

<div x-data="payrollPreviewApp()" x-init="init()" class="space-y-5 pb-20">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER                                                            -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-rose">
                <i data-lucide="receipt"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag" style="display:flex; flex-wrap:wrap; gap:6px;">
                    <span class="tag-dot" style="background-color:#881337;"></span>
                    <span>Modul Penggajian</span>
                    <span class="badge badge-mono"><?= htmlspecialchars($run['nomor_referensi']) ?></span>
                    <?php if ($run['status'] === 'draf'): ?>
                        <span class="badge badge-warning" style="display:inline-flex; align-items:center; gap:5px;">
                            <span class="dot-pulse"></span>
                            <span>Draf Payroll</span>
                        </span>
                    <?php elseif ($run['status'] === 'disetujui'): ?>
                        <span class="badge badge-success" style="display:inline-flex; align-items:center; gap:4px;">
                            <i data-lucide="check" style="width:13px; height:13px;"></i>
                            <span>Disetujui</span>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-info" style="display:inline-flex; align-items:center; gap:4px;">
                            <i data-lucide="check-check" style="width:13px; height:13px;"></i>
                            <span>Dibayarkan</span>
                        </span>
                    <?php endif; ?>
                </div>
                <h1 class="page-title text-base sm:text-2xl font-extrabold tracking-tight" style="line-height:1.25;"><?= htmlspecialchars($run['nama_payroll'] ?: $run['nomor_referensi']) ?></h1>
                <p class="page-subtitle text-xs sm:text-[13px]">
                    <span>Periode: <strong><?= Format::tanggalIndo($run['periode_awal']) ?></strong> s/d <strong><?= Format::tanggalIndo($run['periode_akhir']) ?></strong></span>
                    <span class="mx-1">&bull;</span>
                    <span>Tipe: <strong><?= ucfirst($run['tipe_penggajian']) ?></strong></span>
                    <?php if ($run['status'] !== 'draf' && !empty($run['disetujui_oleh'])): ?>
                        <span class="mx-1">&bull;</span>
                        <span>Disetujui oleh <?= htmlspecialchars($run['disetujui_oleh']) ?> (<?= Format::tanggalIndo($run['disetujui_pada'] ?? '') ?>)</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Page Header Actions (Responsive 2-Col Grid on Mobile, Flex on Desktop) -->
        <div class="page-header-actions grid grid-cols-2 sm:flex sm:items-center gap-2 sm:gap-2.5 w-full sm:w-auto">
            <a href="<?= Router::url('/penggajian') ?>" class="btn btn-secondary justify-center col-span-1" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="arrow-left" style="width:16px; height:16px;"></i>
                <span>Kembali</span>
            </a>

            <?php if ($run['status'] === 'draf'): ?>
                <!-- Actions saat DRAF -->
                <a href="<?= Router::url('/penggajian/create?edit_id=' . $run['id']) ?>" class="btn btn-secondary justify-center col-span-1" style="height:38px; display:inline-flex; align-items:center; gap:6px;" title="Sesuaikan tanggal periode & karyawan sebelum menghitung ulang">
                    <i data-lucide="refresh-cw" style="width:15px; height:15px; color:#2563eb;"></i>
                    <span class="truncate">Regenerasi Draf</span>
                </a>

                <button type="button" @click="showDeleteModal = true" class="btn btn-secondary justify-center col-span-1 text-rose-600 hover:text-rose-700" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                    <i data-lucide="trash-2" style="width:15px; height:15px;"></i>
                    <span>Hapus Draf</span>
                </button>

                <?php if (Auth::hasPermission('hr.payroll_approve')): ?>
                    <button type="button" @click="showApproveModal = true" class="btn btn-primary justify-center col-span-1" style="height:38px; background:#059669; border-color:#047857; display:inline-flex; align-items:center; gap:6px;">
                        <i data-lucide="check-circle" style="width:16px; height:16px;"></i>
                        <span class="truncate">Setujui &amp; Bayar</span>
                    </button>
                <?php endif; ?>

            <?php else: ?>
                <!-- Actions saat DISETUJUI / DIBAYARKAN -->
                <button type="button" 
                        @click="downloadPdf('<?= Router::url('/penggajian/slip-batch?run_id=' . $run['id']) ?>', 'Menyiapkan Slip Batch...', 'Mengompilasi seluruh slip gaji karyawan...')" 
                        class="btn btn-secondary justify-center col-span-1" 
                        style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                    <i data-lucide="file-text" style="width:16px; height:16px; color:#be123c;"></i>
                    <span>Slip Batch</span>
                </button>

                <button type="button" 
                        @click="downloadPdf('<?= Router::url('/penggajian/rekap-pdf?run_id=' . $run['id']) ?>', 'Menyiapkan Rekap PDF...', 'Mengompilasi dokumen rekapitulasi penggajian...')" 
                        class="btn btn-secondary justify-center col-span-1" 
                        style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                    <i data-lucide="printer" style="width:16px; height:16px; color:#0284c7;"></i>
                    <span>Rekap PDF</span>
                </button>

                <?php if ($canCancelApprove && Auth::hasPermission('hr.payroll_approve')): ?>
                    <button type="button" @click="showCancelApproveModal = true" class="btn btn-secondary justify-center col-span-2 sm:col-span-1 text-amber-600 hover:text-amber-700" style="height:38px; display:inline-flex; align-items:center; gap:6px;" title="Batalkan approval dalam batas 24 jam">
                        <i data-lucide="rotate-ccw" style="width:16px; height:16px;"></i>
                        <span>Batal Approval (24h)</span>
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. QUICK STATS KPI (2 Kolom di HP / 4 Kolom di Desktop)                   -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
        <!-- Card 1: Total Kotor -->
        <div class="stat-card" style="display:flex; align-items:center; gap:8px; border-left:3.5px solid #6366f1; padding:10px 12px;">
            <div class="stat-card-icon hidden xs:flex sm:flex" style="background:rgba(99,102,241,0.12); color:#4f46e5; width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="layers" style="width:16px; height:16px;"></i>
            </div>
            <div style="min-width:0; flex:1;">
                <div class="stat-card-label truncate" style="font-size:10.5px; text-transform:uppercase; letter-spacing:0.04em;">Total Kotor</div>
                <div class="stat-card-value font-mono font-bold text-sm sm:text-lg lg:text-xl truncate" style="color:var(--color-ink);"><?= Format::rupiah($totalKotor) ?></div>
                <div style="font-size:10px; color:var(--color-ink-mute); margin-top:1px;" class="truncate hidden sm:block">Bruto komponen</div>
            </div>
        </div>

        <!-- Card 2: Total Potongan -->
        <div class="stat-card" style="display:flex; align-items:center; gap:8px; border-left:3.5px solid #e11d48; padding:10px 12px;">
            <div class="stat-card-icon hidden xs:flex sm:flex" style="background:rgba(225,29,72,0.12); color:#be123c; width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="wallet" style="width:16px; height:16px;"></i>
            </div>
            <div style="min-width:0; flex:1;">
                <div class="stat-card-label truncate" style="font-size:10.5px; text-transform:uppercase; letter-spacing:0.04em;">Total Potongan</div>
                <div class="stat-card-value font-mono font-bold text-sm sm:text-lg lg:text-xl truncate" style="color:#e11d48;"><?= Format::rupiah($totalPotongan) ?></div>
                <div style="font-size:10px; color:var(--color-ink-mute); margin-top:1px;" class="truncate hidden sm:block">Kasbon, tabungan</div>
            </div>
        </div>

        <!-- Card 3: Karyawan Terproses -->
        <div class="stat-card" style="display:flex; align-items:center; gap:8px; border-left:3.5px solid #0284c7; padding:10px 12px;">
            <div class="stat-card-icon hidden xs:flex sm:flex" style="background:rgba(2,132,199,0.12); color:#0284c7; width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="users" style="width:16px; height:16px;"></i>
            </div>
            <div style="min-width:0; flex:1;">
                <div class="stat-card-label truncate" style="font-size:10.5px; text-transform:uppercase; letter-spacing:0.04em;">Terproses</div>
                <div class="stat-card-value font-mono font-bold text-sm sm:text-lg lg:text-xl truncate">
                    <?= $includedCount ?> <span style="font-size:11px; font-weight:600; color:var(--color-ink-mute);">/ <?= count($items) ?></span>
                </div>
                <div style="font-size:10px; color:var(--color-ink-mute); margin-top:1px;" class="truncate hidden sm:block">
                    <?= $excludedCount > 0 ? $excludedCount . ' dikecualikan' : 'Semua disertakan' ?>
                </div>
            </div>
        </div>

        <!-- Card 4: Total Gaji Bersih -->
        <div class="stat-card" style="display:flex; align-items:center; gap:8px; border-left:3.5px solid #10b981; padding:10px 12px;">
            <div class="stat-card-icon hidden xs:flex sm:flex" style="background:rgba(16,185,129,0.12); color:#047857; width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="check-check" style="width:16px; height:16px;"></i>
            </div>
            <div style="min-width:0; flex:1;">
                <div class="stat-card-label truncate" style="font-size:10.5px; text-transform:uppercase; letter-spacing:0.04em;">Gaji Bersih (Net)</div>
                <div class="stat-card-value font-mono font-bold text-sm sm:text-lg lg:text-xl truncate" style="color:#10b981;"><?= Format::rupiah($totalGajiBersih) ?></div>
                <div style="font-size:10px; color:var(--color-ink-mute); margin-top:1px; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                    <span title="Tunai: <?= Format::rupiah($totalGajiTunai) ?> (<?= $countTunai ?> orang)"><i data-lucide="banknote" style="width:11px; height:11px; display:inline-block; vertical-align:middle; color:#059669;"></i> Tunai: <strong class="font-mono text-emerald-700 dark:text-emerald-400"><?= Format::rupiah($totalGajiTunai) ?></strong></span>
                    <span>&bull;</span>
                    <span title="Transfer: <?= Format::rupiah($totalGajiTransfer) ?> (<?= $countTransfer ?> orang)"><i data-lucide="arrow-up-right" style="width:11px; height:11px; display:inline-block; vertical-align:middle; color:#2563eb;"></i> TF: <strong class="font-mono text-blue-700 dark:text-blue-400"><?= Format::rupiah($totalGajiTransfer) ?></strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. KARTU UTAMA & TABEL RINCIAN LEDGER (4 PILAR FINANSIAL)                -->
    <!-- ========================================================================= -->
    <div class="card" style="padding:0; overflow:hidden;">
        <!-- Filter & Search Toolbar -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 sm:p-4 border-b"
             style="border-color:var(--color-hairline); background-color:var(--color-canvas);">
            
            <!-- Filter Tabs (Semua / Borongan / Bulanan / Tunai / Transfer) -->
            <div class="tab-pill-group">
                <button type="button" @click="statusFilter = 'all'" class="tab-pill-btn" :class="statusFilter === 'all' ? 'is-active-primary' : ''">
                    <span>Semua Karyawan</span>
                    <span class="tab-pill-counter" x-text="includedItems.length"></span>
                </button>
                <button type="button" @click="statusFilter = 'borongan'" class="tab-pill-btn" :class="statusFilter === 'borongan' ? 'is-active-amber' : ''">
                    <span>Borongan</span>
                    <span class="tab-pill-counter" x-text="boronganCount"></span>
                </button>
                <button type="button" @click="statusFilter = 'bulanan'" class="tab-pill-btn" :class="statusFilter === 'bulanan' ? 'is-active-sky' : ''">
                    <span>Bulanan</span>
                    <span class="tab-pill-counter" x-text="bulananCount"></span>
                </button>
                <button type="button" @click="statusFilter = 'tunai'" class="tab-pill-btn" :class="statusFilter === 'tunai' ? 'is-active-emerald' : ''">
                    <i data-lucide="banknote" style="width:12px; height:12px;"></i>
                    <span>Tunai</span>
                    <span class="tab-pill-counter" x-text="tunaiCount"></span>
                </button>
                <button type="button" @click="statusFilter = 'transfer'" class="tab-pill-btn" :class="statusFilter === 'transfer' ? 'is-active-purple' : ''">
                    <i data-lucide="credit-card" style="width:12px; height:12px;"></i>
                    <span>Transfer</span>
                    <span class="tab-pill-counter" x-text="transferCount"></span>
                </button>
            </div>

            <!-- Instant Search Input Debounced -->
            <div class="flex items-center gap-2 flex-1 sm:max-w-xs w-full">
                <div class="form-input-icon flex-1 relative">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="search" x-model.debounce.250ms="searchQuery"
                           placeholder="Cari karyawan / jabatan..."
                           autocomplete="off" class="form-input" style="height:36px; font-size:12.5px; padding-right:30px;">
                    <button type="button" x-cloak x-show="searchQuery" @click="searchQuery = ''"
                            class="btn btn-ghost btn-xs text-slate-400 hover:text-slate-600"
                            style="position:absolute; right:6px; top:50%; transform:translateY(-50%);">
                        <i data-lucide="x" style="width:13px; height:13px;"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Subheader Table Counter + Hint Drag-to-Scroll -->
        <div style="padding:10px 18px; background:var(--color-canvas-soft); border-bottom:1px solid var(--color-hairline); display:flex; align-items:center; justify-content:space-between; font-size:12px; color:var(--color-ink-mute); gap:8px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="display:flex; align-items:center; gap:6px; font-weight:600; color:var(--color-ink);">
                    <i data-lucide="users" style="width:14px; height:14px;"></i>
                    <span>Rincian per Karyawan</span>
                </div>
                <!-- Hint Drag-to-Scroll Desktop (Fitur bawaan app.js TableGrabScroll) -->
                <div class="hidden md:inline-flex payroll-scroll-hint-badge">
                    <i data-lucide="move-horizontal"></i>
                    <span>Tahan &amp; geser untuk scroll</span>
                </div>
            </div>
            <div class="font-mono">
                Menampilkan <strong style="color:var(--color-ink);" x-text="tableItems.length"></strong> dari <span x-text="includedItems.length"></span> karyawan aktif
            </div>
        </div>

        <!-- Desktop Table View (4 Pilar Finansial ala Salary) -->
        <?php
        // Closure perender baris karyawan desktop (100% Alpine.js Single Root Element Compliant)
        $renderRow = function(string $groupType) use ($run) {
            $isBorongan = ($groupType === 'borongan');
        ?>
        <tr>
            <td class="cell-center cell-nowrap" style="color:var(--color-ink-mute); font-size:12px;" 
                x-text="<?= $isBorongan ? "(statusFilter === 'all' ? bulananTableItems.length : 0) + idx + 1" : "idx + 1" ?>"></td>
            <td>
                <div style="font-weight:700; font-size:13px; color:var(--color-ink);" x-text="item.nama_karyawan"></div>
                <div style="display:flex; align-items:center; gap:5px; margin-top:3px; flex-wrap:wrap;">
                    <?php if ($isBorongan): ?>
                        <span class="badge badge-amber" style="font-size:10.5px; padding:1px 6px;">Borongan</span>
                    <?php else: ?>
                        <span class="badge badge-info" style="font-size:10.5px; padding:1px 6px;">Bulanan</span>
                    <?php endif; ?>
                    <span class="badge badge-mono" style="font-size:10.5px;" x-text="item.hari_hadir + ' hr'"></span>
                    <template x-if="item.posisi && item.posisi !== '-'">
                        <span style="font-size:11px; color:var(--color-ink-mute);" x-text="item.posisi"></span>
                    </template>
                    <template x-if="item.metode_pembayaran === 'transfer'">
                        <span class="badge" style="background:#f5f3ff; color:#6d28d9; border:1px solid #ddd6fe; font-size:10.5px; padding:1px 6px; display:inline-flex; align-items:center; gap:3px;">
                            <i data-lucide="arrow-up-right" style="width:11px; height:11px;"></i>
                            <span x-text="item.bank_nama ? ('TF ' + item.bank_nama) : 'Transfer'"></span>
                        </span>
                    </template>
                    <template x-if="item.metode_pembayaran !== 'transfer'">
                        <span class="badge" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; font-size:10.5px; padding:1px 6px; display:inline-flex; align-items:center; gap:3px;">
                            <i data-lucide="banknote" style="width:11px; height:11px;"></i>
                            <span>Tunai</span>
                        </span>
                    </template>
                </div>
            </td>

            <!-- Pilar 1: Pendapatan Awal -->
            <td class="pillar-cell">
                <div class="pillar-total" x-text="formatRupiah(item.pendapatan_awal)"></div>
                <div class="pillar-list">
                    <template x-if="item.gaji_pokok > 0">
                        <div class="pillar-row">
                            <span>Gaji Pokok</span>
                            <span class="val" x-text="formatRupiah(item.gaji_pokok)"></span>
                        </div>
                    </template>
                    <template x-if="item.uang_hadir > 0">
                        <div class="pillar-row">
                            <span>Kehadiran</span>
                            <span class="val" x-text="formatRupiah(item.uang_hadir)"></span>
                        </div>
                    </template>
                    <template x-if="item.borongan > 0">
                        <div class="pillar-row">
                            <span>Borongan</span>
                            <span class="val" x-text="formatRupiah(item.borongan)"></span>
                        </div>
                    </template>
                    <template x-if="item.tunjangan_bulanan > 0">
                        <div class="pillar-row">
                            <span>T. Bulanan</span>
                            <span class="val" x-text="formatRupiah(item.tunjangan_bulanan)"></span>
                        </div>
                    </template>
                    <template x-if="item.lembur > 0">
                        <div class="pillar-row" style="color:#b45309; font-weight:600;">
                            <span>Lembur</span>
                            <span class="val" x-text="formatRupiah(item.lembur)"></span>
                        </div>
                    </template>
                    <template x-if="item.komisi > 0">
                        <div class="pillar-row" style="color:#6d28d9; font-weight:600;">
                            <span>Komisi Sales</span>
                            <span class="val" x-text="formatRupiah(item.komisi)"></span>
                        </div>
                    </template>
                </div>
            </td>

            <!-- Pilar 2: Potongan -->
            <td class="pillar-cell">
                <div class="pillar-total" :class="item.potongan_total > 0 ? 'text-rose-600' : ''" x-text="formatRupiah(item.potongan_total)"></div>
                <div class="pillar-list">
                    <template x-if="item.kasbon > 0">
                        <div class="pillar-row" style="color:#e11d48;">
                            <span style="display:inline-flex; align-items:center; gap:4px;">
                                <span>Kasbon</span>
                                <template x-if="item.kasbon_adjusted">
                                    <i data-lucide="alert-circle" style="width:12px; height:12px; color:#d97706;" title="Angka diturunkan otomatis agar sisa gaji tidak minus"></i>
                                </template>
                            </span>
                            <span class="val" x-text="formatRupiah(item.kasbon)"></span>
                        </div>
                    </template>
                    <template x-if="item.penarikan_gaji > 0">
                        <div class="pillar-row" style="color:#e11d48;">
                            <span>Penarikan Gaji</span>
                            <span class="val" x-text="formatRupiah(item.penarikan_gaji)"></span>
                        </div>
                    </template>
                    <template x-if="item.setor_tabungan > 0">
                        <div class="pillar-row" style="color:#e11d48;">
                            <span>Setor Tabungan</span>
                            <span class="val" x-text="formatRupiah(item.setor_tabungan)"></span>
                        </div>
                    </template>
                </div>
            </td>

            <!-- Pilar 3: Penyesuaian (+/-) -->
            <td class="pillar-cell">
                <div class="pillar-total" 
                     :class="item.penyesuaian > 0 ? 'text-emerald-600' : (item.penyesuaian < 0 ? 'text-rose-600' : '')"
                     x-text="(item.penyesuaian > 0 ? '+' : '') + formatRupiah(item.penyesuaian)"></div>
                <div class="pillar-list">
                    <template x-if="item.tunjangan_lain > 0">
                        <div class="pillar-row" style="color:#059669;">
                            <span>Bonus Lain</span>
                            <span class="val" x-text="'+' + formatRupiah(item.tunjangan_lain)"></span>
                        </div>
                    </template>
                    <template x-if="item.tarik_tabungan > 0">
                        <div class="pillar-row" style="color:#059669;">
                            <span>Tarik Tabungan</span>
                            <span class="val" x-text="'+' + formatRupiah(item.tarik_tabungan)"></span>
                        </div>
                    </template>
                    <template x-if="item.pembulatan !== 0">
                        <div class="pillar-row" style="color:#0284c7;">
                            <span>Pembulatan</span>
                            <span class="val" x-text="(item.pembulatan > 0 ? '+' : '') + formatRupiah(item.pembulatan)"></span>
                        </div>
                    </template>
                    <template x-if="item.potongan_lain > 0">
                        <div class="pillar-row" style="color:#e11d48;">
                            <span>Potongan Manual</span>
                            <span class="val" x-text="'-' + formatRupiah(item.potongan_lain)"></span>
                        </div>
                    </template>
                    <template x-if="item.catatan_tunjangan_lain || item.catatan_potongan_lain">
                        <div class="mt-1" style="font-size:10.5px; color:var(--color-ink-mute); display:flex; align-items:center; gap:4px;"
                             :title="[item.catatan_tunjangan_lain, item.catatan_potongan_lain].filter(Boolean).join(' | ')">
                            <i data-lucide="info" style="width:11px; height:11px; color:#2563eb;"></i>
                            <span class="truncate" style="max-width:130px;">Catatan manual</span>
                        </div>
                    </template>
                </div>
            </td>

            <!-- Pilar 4: Net Gaji -->
            <td class="cell-right" style="vertical-align:top; padding-top:12px !important;">
                <div class="font-mono font-bold text-emerald-600 dark:text-emerald-400" style="font-size:15.5px;" x-text="formatRupiah(item.gaji_bersih)"></div>
            </td>

            <!-- Aksi -->
            <td class="cell-center cell-nowrap" style="vertical-align:top; padding-top:10px !important;">
                <?php if ($run['status'] === 'draf'): ?>
                    <div style="display:flex; flex-direction:column; gap:5px; align-items:center;">
                        <button type="button" @click="openEdit(item)" class="btn btn-secondary btn-sm" style="font-size:11.5px; padding:3px 10px; width:100%; display:inline-flex; align-items:center; justify-content:center; gap:4px;" title="Sesuaikan Komponen Gaji">
                            <i data-lucide="sliders" style="width:12px; height:12px;"></i>
                            <span>Sesuaikan</span>
                        </button>
                        <button type="button" @click="openExclude(item)" class="btn btn-ghost btn-sm text-rose-600 hover:text-rose-700" style="font-size:11px; padding:2px 8px; width:100%; display:inline-flex; align-items:center; justify-content:center; gap:4px;" title="Kecualikan dari Payroll ini">
                            <i data-lucide="user-x" style="width:12px; height:12px;"></i>
                            <span>Kecualikan</span>
                        </button>
                    </div>
                <?php else: ?>
                    <button type="button" 
                            @click="downloadPdf('<?= Router::url('/penggajian/slip?run_id=' . $run['id'] . '&rincian_id=') ?>' + item.id, 'Menyiapkan Slip Gaji...', 'Memproses slip gaji ' + item.nama_karyawan + '...')"
                            class="btn btn-secondary btn-sm" 
                            style="font-size:11px; padding:4px 9px; gap:4px; display:inline-flex; align-items:center;" 
                            title="Cetak Slip Gaji">
                        <i data-lucide="file-text" style="width:13px; height:13px; color:#be123c;"></i>
                        <span>Slip</span>
                    </button>
                <?php endif; ?>
            </td>
        </tr>
        <?php
        };
        ?>

        <div class="hidden sm:block relative overflow-x-auto custom-scrollbar table-scroll">
            <table class="data-table" style="min-width: 1100px; width: 100%;">
                <thead>
                    <tr>
                        <th style="width:46px;" class="cell-center cell-nowrap">#</th>
                        <th style="min-width:210px;">Karyawan</th>
                        <th class="cell-right" style="min-width:185px;">Pendapatan Awal</th>
                        <th class="cell-right" style="min-width:165px;">Potongan</th>
                        <th class="cell-right" style="min-width:165px;">Penyesuaian (+/-)</th>
                        <th class="cell-right" style="min-width:145px; color:#059669;">Net Gaji</th>
                        <th style="width:115px;" class="cell-center cell-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- ======================================================= -->
                    <!-- GRUP 1: BULANAN                                         -->
                    <!-- ======================================================= -->
                    <tr x-show="bulananTableItems.length > 0" class="group-row group-row-bulanan">
                        <td colspan="7">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                                <div style="display:flex; align-items:center; gap:8px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; font-size:11.5px; color:#1e40af;" class="dark:text-sky-300">
                                    <div style="width:22px; height:22px; border-radius:6px; background:#dbeafe; color:#2563eb; display:flex; align-items:center; justify-content:center;">
                                        <i data-lucide="users" style="width:13px; height:13px;"></i>
                                    </div>
                                    <span>Grup Karyawan Bulanan</span>
                                    <span class="badge-counter group-pill-bulanan" x-text="bulananTableItems.length"></span>
                                </div>
                                <span class="group-period-bulanan font-mono">
                                    <i data-lucide="calendar" style="width:12px; height:12px; color:#2563eb;"></i>
                                    <span>Periode: <strong x-text="periodLabels.bulanan"></strong></span>
                                </span>
                            </div>
                        </td>
                    </tr>
                    <template x-for="(item, idx) in bulananTableItems" :key="'bul-' + item.id">
                        <?php $renderRow('bulanan'); ?>
                    </template>

                    <!-- ======================================================= -->
                    <!-- GRUP 2: BORONGAN                                        -->
                    <!-- ======================================================= -->
                    <tr x-show="boronganTableItems.length > 0" class="group-row group-row-borongan">
                        <td colspan="7">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                                <div style="display:flex; align-items:center; gap:8px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; font-size:11.5px; color:#92400e;" class="dark:text-amber-300">
                                    <div style="width:22px; height:22px; border-radius:6px; background:#fef3c7; color:#d97706; display:flex; align-items:center; justify-content:center;">
                                        <i data-lucide="users" style="width:13px; height:13px;"></i>
                                    </div>
                                    <span>Grup Karyawan Borongan</span>
                                    <span class="badge-counter group-pill-borongan" x-text="boronganTableItems.length"></span>
                                </div>
                                <span class="group-period-borongan font-mono">
                                    <i data-lucide="calendar" style="width:12px; height:12px; color:#d97706;"></i>
                                    <span>Periode: <strong x-text="periodLabels.borongan"></strong></span>
                                </span>
                            </div>
                        </td>
                    </tr>
                    <template x-for="(item, idx) in boronganTableItems" :key="'bor-' + item.id">
                        <?php $renderRow('borongan'); ?>
                    </template>

                    <!-- Empty State Desktop -->
                    <template x-if="tableItems.length === 0">
                        <tr>
                            <td colspan="7" style="text-align:center; padding:45px 20px; color:var(--color-ink-mute);">
                                <i data-lucide="inbox" style="width:36px; height:36px; margin:0 auto 8px auto; opacity:0.35;"></i>
                                <div style="font-weight:700; font-size:13px; color:var(--color-ink);">Tidak ada data karyawan yang cocok</div>
                                <div style="font-size:12px; margin-top:2px;">Sesuaikan filter tipe atau kata kunci pencarian</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View (block sm:hidden) -->
        <?php
        // Closure perender kartu karyawan mobile (Touch-Friendly & Expandable 4-Pilar)
        $renderMobileCard = function(string $groupType) use ($run) {
            $isBorongan = ($groupType === 'borongan');
        ?>
        <div x-data="{ expanded: false }" class="p-3.5 space-y-2.5 bg-white dark:bg-slate-900">
            <!-- Header Kartu: No, Nama, Badges, Net Gaji -->
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold text-slate-400 font-mono" 
                              x-text="'#' + <?= $isBorongan ? "((statusFilter === 'all' ? bulananTableItems.length : 0) + idx + 1)" : "(idx + 1)" ?>"></span>
                        <div class="font-bold text-sm truncate" style="color:var(--color-ink);" x-text="item.nama_karyawan"></div>
                    </div>
                    <div class="flex items-center gap-1 mt-1 flex-wrap">
                        <?php if ($isBorongan): ?>
                            <span class="badge badge-amber" style="font-size:10px; padding:1px 5px;">Borongan</span>
                        <?php else: ?>
                            <span class="badge badge-info" style="font-size:10px; padding:1px 5px;">Bulanan</span>
                        <?php endif; ?>
                        <span class="badge badge-mono" style="font-size:10px; padding:1px 5px;" x-text="item.hari_hadir + ' hr'"></span>
                        <template x-if="item.posisi && item.posisi !== '-'">
                            <span class="text-[11px] text-slate-500 truncate" x-text="item.posisi"></span>
                        </template>
                        <template x-if="item.kasbon_adjusted">
                            <span class="badge badge-warning" style="font-size:10px; padding:1px 5px;">⚠️ Kasbon Disesuaikan</span>
                        </template>
                        <template x-if="item.metode_pembayaran === 'transfer'">
                            <span class="badge" style="background:#f5f3ff; color:#6d28d9; border:1px solid #ddd6fe; font-size:10px; padding:1px 5px; display:inline-flex; align-items:center; gap:2px;">
                                <i data-lucide="arrow-up-right" style="width:10px; height:10px;"></i>
                                <span x-text="item.bank_nama ? ('TF ' + item.bank_nama) : 'Transfer'"></span>
                            </span>
                        </template>
                        <template x-if="item.metode_pembayaran !== 'transfer'">
                            <span class="badge" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; font-size:10px; padding:1px 5px; display:inline-flex; align-items:center; gap:2px;">
                                <i data-lucide="banknote" style="width:10px; height:10px;"></i>
                                <span>Tunai</span>
                            </span>
                        </template>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Net Gaji</div>
                    <div class="font-mono font-bold text-base text-emerald-600 dark:text-emerald-400" x-text="formatRupiah(item.gaji_bersih)"></div>
                </div>
            </div>

            <!-- Ringkasan 3 Pilar Finansial -->
            <div class="grid grid-cols-3 gap-1.5 p-2 rounded-lg border text-xs" style="background:var(--color-canvas-soft); border-color:var(--color-hairline);">
                <div>
                    <span class="text-[10px] text-slate-400 block">Pendapatan:</span>
                    <span class="font-mono font-semibold block text-[11px] truncate" style="color:var(--color-ink);" x-text="formatRupiah(item.pendapatan_awal)"></span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 block">Potongan:</span>
                    <span class="font-mono font-semibold block text-[11px] text-rose-600 truncate" x-text="formatRupiah(item.potongan_total)"></span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 block">Penyesuaian:</span>
                    <span class="font-mono font-semibold block text-[11px] truncate" :class="item.penyesuaian >= 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="(item.penyesuaian > 0 ? '+' : '') + formatRupiah(item.penyesuaian)"></span>
                </div>
            </div>

            <!-- Accordion Toggle Rincian Lengkap 4 Pilar -->
            <div class="pt-1">
                <button type="button" 
                        @click="expanded = !expanded" 
                        class="mobile-accordion-toggle"
                        :class="expanded ? 'is-active' : ''">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="layers" style="width:12px; height:12px;" :class="expanded ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400'"></i>
                        <span x-text="expanded ? 'Tutup Rincian Komponen' : 'Lihat Rincian Komponen'"></span>
                    </span>
                    <i data-lucide="chevron-down" class="chevron-arrow" :class="expanded ? 'rotate-180' : ''" style="width:12px; height:12px;"></i>
                </button>
            </div>

                <!-- Panel Detail yang Terbuka -->
                <div x-show="expanded" x-cloak class="mt-2 p-2.5 rounded-lg border bg-slate-50/70 dark:bg-slate-800/50 space-y-2 text-[11.5px]" style="border-color:var(--color-hairline);">
                    <!-- Breakdown Pendapatan Awal -->
                    <div>
                        <div class="font-bold text-[10px] uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-1">Rincian Pendapatan Awal</div>
                        <div class="space-y-0.5 font-mono text-[11px]">
                            <template x-if="item.gaji_pokok > 0">
                                <div class="flex justify-between"><span>Gaji Pokok:</span><span x-text="formatRupiah(item.gaji_pokok)"></span></div>
                            </template>
                            <template x-if="item.uang_hadir > 0">
                                <div class="flex justify-between"><span>Uang Hadir:</span><span x-text="formatRupiah(item.uang_hadir)"></span></div>
                            </template>
                            <template x-if="item.borongan > 0">
                                <div class="flex justify-between"><span>Upah Borongan:</span><span x-text="formatRupiah(item.borongan)"></span></div>
                            </template>
                            <template x-if="item.tunjangan_bulanan > 0">
                                <div class="flex justify-between"><span>Tunjangan Bulanan:</span><span x-text="formatRupiah(item.tunjangan_bulanan)"></span></div>
                            </template>
                            <template x-if="item.lembur > 0">
                                <div class="flex justify-between text-amber-600 font-semibold"><span>Upah Lembur:</span><span x-text="formatRupiah(item.lembur)"></span></div>
                            </template>
                            <template x-if="item.komisi > 0">
                                <div class="flex justify-between text-purple-600 font-semibold"><span>Komisi Sales:</span><span x-text="formatRupiah(item.komisi)"></span></div>
                            </template>
                        </div>
                    </div>

                    <!-- Breakdown Potongan -->
                    <template x-if="item.potongan_total > 0">
                        <div class="pt-1.5 border-t border-slate-200/60 dark:border-slate-700/60">
                            <div class="font-bold text-[10px] uppercase tracking-wider text-rose-500 mb-1">Rincian Potongan</div>
                            <div class="space-y-0.5 font-mono text-[11px] text-rose-600">
                                <template x-if="item.kasbon > 0">
                                    <div class="flex justify-between"><span>Potongan Kasbon:</span><span x-text="formatRupiah(item.kasbon)"></span></div>
                                </template>
                                <template x-if="item.penarikan_gaji > 0">
                                    <div class="flex justify-between"><span>Penarikan Gaji:</span><span x-text="formatRupiah(item.penarikan_gaji)"></span></div>
                                </template>
                                <template x-if="item.setor_tabungan > 0">
                                    <div class="flex justify-between"><span>Setor Tabungan:</span><span x-text="formatRupiah(item.setor_tabungan)"></span></div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Breakdown Penyesuaian -->
                    <template x-if="item.tunjangan_lain > 0 || item.tarik_tabungan > 0 || item.pembulatan !== 0 || item.potongan_lain > 0">
                        <div class="pt-1.5 border-t border-slate-200/60 dark:border-slate-700/60">
                            <div class="font-bold text-[10px] uppercase tracking-wider text-slate-400 mb-1">Penyesuaian Manual (+/-)</div>
                            <div class="space-y-0.5 font-mono text-[11px]">
                                <template x-if="item.tunjangan_lain > 0">
                                    <div class="flex justify-between text-emerald-600">
                                        <span>Bonus Lain:</span>
                                        <span x-text="'+' + formatRupiah(item.tunjangan_lain)"></span>
                                    </div>
                                </template>
                                <template x-if="item.tarik_tabungan > 0">
                                    <div class="flex justify-between text-emerald-600">
                                        <span>Tarik Tabungan:</span>
                                        <span x-text="'+' + formatRupiah(item.tarik_tabungan)"></span>
                                    </div>
                                </template>
                                <template x-if="item.pembulatan !== 0">
                                    <div class="flex justify-between text-blue-600">
                                        <span>Pembulatan:</span>
                                        <span x-text="(item.pembulatan > 0 ? '+' : '') + formatRupiah(item.pembulatan)"></span>
                                    </div>
                                </template>
                                <template x-if="item.potongan_lain > 0">
                                    <div class="flex justify-between text-rose-600">
                                        <span>Potongan Manual:</span>
                                        <span x-text="'-' + formatRupiah(item.potongan_lain)"></span>
                                    </div>
                                </template>
                            </div>
                            <template x-if="item.catatan_tunjangan_lain || item.catatan_potongan_lain">
                                <div class="mt-1 text-[10.5px] text-slate-500 italic bg-white dark:bg-slate-900 p-1.5 rounded border border-slate-200 dark:border-slate-800"
                                     x-text="'Catatan: ' + [item.catatan_tunjangan_lain, item.catatan_potongan_lain].filter(Boolean).join(' | ')"></div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Mobile Action Row Touch-Friendly -->
            <div class="flex items-center gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                <?php if ($run['status'] === 'draf'): ?>
                    <button type="button" @click="openEdit(item)" class="btn btn-secondary flex-1 justify-center py-2 text-xs" style="height:35px;" title="Sesuaikan Komponen Gaji">
                        <i data-lucide="sliders" style="width:13px; height:13px;"></i>
                        <span>Sesuaikan</span>
                    </button>
                    <button type="button" @click="openExclude(item)" class="btn btn-ghost text-rose-600 hover:text-rose-700 justify-center py-2 px-3 text-xs" style="height:35px;" title="Kecualikan dari Payroll">
                        <i data-lucide="user-x" style="width:13px; height:13px;"></i>
                        <span>Kecualikan</span>
                    </button>
                <?php else: ?>
                    <button type="button" 
                            @click="downloadPdf('<?= Router::url('/penggajian/slip?run_id=' . $run['id'] . '&rincian_id=') ?>' + item.id, 'Menyiapkan Slip Gaji...', 'Memproses slip gaji ' + item.nama_karyawan + '...')"
                            class="btn btn-secondary flex-1 justify-center py-2 text-xs" 
                            style="height:35px;" 
                            title="Lihat Slip Gaji">
                        <i data-lucide="file-text" style="width:13px; height:13px; color:#be123c;"></i>
                        <span>Lihat Slip</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        };
        ?>

        <div class="block sm:hidden divide-y divide-slate-100 dark:divide-slate-800">
            <!-- Header Pembatas Grup Bulanan Mobile -->
            <div x-show="bulananTableItems.length > 0" class="group-header-bulanan-mob flex items-center justify-between text-xs font-bold gap-2">
                <div class="flex items-center gap-1.5 shrink-0">
                    <div style="width:20px; height:20px; border-radius:6px; background:#dbeafe; color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i data-lucide="users" style="width:11px; height:11px;"></i>
                    </div>
                    <span style="letter-spacing:0.04em; font-weight:800; font-size:11px;">GRUP BULANAN</span>
                    <span class="badge-counter group-pill-bulanan" style="font-size:10px; height:16px; min-width:16px; padding:0 4px;" x-text="bulananTableItems.length"></span>
                </div>
                <div class="group-period-bulanan font-mono shrink-0">
                    <i data-lucide="calendar" style="width:10.5px; height:10.5px; color:#2563eb; flex-shrink:0;"></i>
                    <span x-text="periodLabels.bulanan"></span>
                </div>
            </div>
            <template x-for="(item, idx) in bulananTableItems" :key="'mob-bul-' + item.id">
                <?php $renderMobileCard('bulanan'); ?>
            </template>

            <!-- Header Pembatas Grup Borongan Mobile -->
            <div x-show="boronganTableItems.length > 0" class="group-header-borongan-mob flex items-center justify-between text-xs font-bold gap-2">
                <div class="flex items-center gap-1.5 shrink-0">
                    <div style="width:20px; height:20px; border-radius:6px; background:#fef3c7; color:#d97706; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i data-lucide="users" style="width:11px; height:11px;"></i>
                    </div>
                    <span style="letter-spacing:0.04em; font-weight:800; font-size:11px;">GRUP BORONGAN</span>
                    <span class="badge-counter group-pill-borongan" style="font-size:10px; height:16px; min-width:16px; padding:0 4px;" x-text="boronganTableItems.length"></span>
                </div>
                <div class="group-period-borongan font-mono shrink-0">
                    <i data-lucide="calendar" style="width:10.5px; height:10.5px; color:#d97706; flex-shrink:0;"></i>
                    <span x-text="periodLabels.borongan"></span>
                </div>
            </div>
            <template x-for="(item, idx) in boronganTableItems" :key="'mob-bor-' + item.id">
                <?php $renderMobileCard('borongan'); ?>
            </template>

            <!-- Empty State Mobile -->
            <template x-if="tableItems.length === 0">
                <div style="text-align:center; padding:35px 20px; color:var(--color-ink-mute);">
                    <i data-lucide="inbox" style="width:32px; height:32px; margin:0 auto 8px auto; opacity:0.35;"></i>
                    <div style="font-weight:700; font-size:12.5px; color:var(--color-ink);">Tidak ada data karyawan yang cocok</div>
                </div>
            </template>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. KARTU KHUSUS: KARYAWAN DIKECUALIKAN (EXCLUDED SECTION ALA SALARY)     -->
    <!-- ========================================================================= -->
    <div x-show="excludedItems.length > 0" x-cloak class="card" style="padding:0; overflow:hidden; border-color:#fecaca; background:rgba(239, 68, 68, 0.02);">
        <div style="padding:14px 18px; border-bottom:1px solid #fecaca; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; background:rgba(239, 68, 68, 0.05);">
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="width:28px; height:28px; border-radius:8px; background:rgba(239, 68, 68, 0.15); color:#dc2626; display:flex; align-items:center; justify-content:center;">
                    <i data-lucide="user-x" style="width:16px; height:16px;"></i>
                </div>
                <div>
                    <h3 style="font-weight:700; font-size:13px; color:#991b1b; margin:0;">Karyawan Dikecualikan</h3>
                    <div style="font-size:11.5px; color:var(--color-ink-mute);">Karyawan berikut tidak diproses gajinya pada periode payroll ini.</div>
                </div>
            </div>
            <span class="badge badge-danger" style="font-size:11px;" x-text="excludedItems.length + ' Orang'"></span>
        </div>

        <!-- Mobile Excluded Cards View (block sm:hidden) -->
        <div class="block sm:hidden divide-y divide-red-100 dark:divide-red-950/40">
            <template x-for="(eit, eidx) in excludedItems" :key="'mob-exc-' + eit.id">
                <div class="p-3.5 space-y-2.5 bg-red-50/20 dark:bg-red-950/15">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold text-red-400 font-mono" x-text="'#' + (eidx + 1)"></span>
                                <div class="font-bold text-sm text-slate-800 dark:text-slate-100 truncate" x-text="eit.nama_karyawan"></div>
                            </div>
                            <div class="flex items-center gap-1 mt-1 flex-wrap">
                                <span class="badge badge-danger text-[10px] py-0 px-1.5" x-text="eit.group === 'borongan' ? 'Borongan' : 'Bulanan'"></span>
                                <template x-if="eit.posisi && eit.posisi !== '-'">
                                    <span class="text-[11px] text-slate-500 truncate" x-text="eit.posisi"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="p-2 rounded bg-white dark:bg-slate-900 border border-red-200 dark:border-red-900/60 text-xs text-slate-600 dark:text-slate-300">
                        <span class="font-semibold text-red-600 block text-[10.5px]">Alasan Pengecualian:</span>
                        <span x-text="eit.catatan_pengecualian || 'Dikecualikan manual dari payroll ini.'"></span>
                    </div>
                    <?php if ($run['status'] === 'draf'): ?>
                        <form action="<?= Router::url('/penggajian/toggle-exclude') ?>" method="POST" style="margin:0;" @submit="confirmUnexclude($event, eit)">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="run_id" value="<?= $run['id'] ?>">
                            <input type="hidden" name="item_id" :value="eit.id">
                            <button type="submit" class="btn btn-secondary w-full justify-center py-2 text-xs text-emerald-700 border-emerald-300 bg-emerald-50/60 hover:bg-emerald-100 dark:bg-emerald-950/30 dark:border-emerald-800" style="height:36px;">
                                <i data-lucide="rotate-ccw" style="width:13px; height:13px;"></i>
                                <span>Sertakan Kembali ke Payroll</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </template>
        </div>

        <!-- Desktop Excluded Table (hidden sm:block) -->
        <div class="hidden sm:block relative overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 700px; width: 100%;">
                <thead>
                    <tr>
                        <th style="width:46px;" class="cell-center cell-nowrap">#</th>
                        <th style="min-width:200px;">Karyawan</th>
                        <th>Alasan Pengecualian</th>
                        <?php if ($run['status'] === 'draf'): ?>
                            <th style="width:130px;" class="cell-center cell-nowrap">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(eit, eidx) in excludedItems" :key="'exc-' + eit.id">
                        <tr>
                            <td class="cell-center cell-nowrap" style="color:var(--color-ink-mute); font-size:12px;" x-text="eidx + 1"></td>
                            <td>
                                <div style="font-weight:700; font-size:13px; color:var(--color-ink);" x-text="eit.nama_karyawan"></div>
                                <div style="font-size:11px; color:var(--color-ink-mute); margin-top:2px;" x-text="(eit.group === 'borongan' ? 'Borongan' : 'Bulanan') + (eit.posisi && eit.posisi !== '-' ? (' • ' + eit.posisi) : '')"></div>
                            </td>
                            <td>
                                <span style="font-size:12px; color:var(--color-ink);" x-text="eit.catatan_pengecualian || '-'"></span>
                            </td>
                            <?php if ($run['status'] === 'draf'): ?>
                                <td class="cell-center cell-nowrap">
                                    <form action="<?= Router::url('/penggajian/toggle-exclude') ?>" method="POST" style="margin:0;" @submit="confirmUnexclude($event, eit)">
                                        <?= CSRF::field() ?>
                                        <input type="hidden" name="run_id" value="<?= $run['id'] ?>">
                                        <input type="hidden" name="item_id" :value="eit.id">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="font-size:11px; padding:4px 10px; color:#059669; border-color:#a7f3d0; display:inline-flex; align-items:center; gap:4px;">
                                            <i data-lucide="rotate-ccw" style="width:12px; height:12px;"></i>
                                            <span>Batal Kecuali</span>
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. MODALS (DNA POPUP STANDARD)                                            -->
    <!-- ========================================================================= -->

    <!-- MODAL 1: SESUAIKAN KOMPONEN GAJI (FULL INTERACTIVE CALCULATOR ALA SALARY) -->
    <template x-teleport="body">
        <div x-show="showEditModal" x-cloak class="modal-backdrop" @click="showEditModal = false">
            <div class="modal-box modal-box-lg" style="max-width: 740px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(37,99,235,0.12); color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i data-lucide="sliders" style="width:20px; height:20px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title">Sesuaikan Gaji: <span class="text-primary font-bold" x-text="editForm.karyawan_nama"></span></div>
                            <div style="font-size:12px; color:var(--color-ink-mute); margin-top:1px;">Koreksi kasbon, tunjangan, potongan manual, tabungan, &amp; pembulatan.</div>
                        </div>
                    </div>
                    <button type="button" @click="showEditModal = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px; height:18px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/penggajian/update-item') ?>" method="POST" @submit="guardEditSubmit($event)">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="run_id" value="<?= $run['id'] ?>">
                    <input type="hidden" name="item_id" :value="editForm.item_id">

                    <!-- Hidden inputs menyimpan angka mentah untuk parsing aman server -->
                    <input type="hidden" name="total_potongan_kasbon" :value="editForm.total_potongan_kasbon">
                    <input type="hidden" name="tunjangan_lain" :value="editForm.tunjangan_lain">
                    <input type="hidden" name="potongan_lain" :value="editForm.potongan_lain">
                    <input type="hidden" name="total_potongan_tabungan" :value="editForm.total_potongan_tabungan">
                    <input type="hidden" name="penarikan_tabungan" :value="editForm.penarikan_tabungan">
                    <input type="hidden" name="nominal_pembulatan" :value="editForm.nominal_pembulatan">

                    <div class="modal-body custom-scrollbar space-y-4">
                        <!-- Top Split Panels: Pendapatan Sementara vs Potongan Kasbon -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            <!-- Panel Kiri: Pendapatan Sementara & Breakdown -->
                            <div class="money-panel">
                                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--color-ink-mute); letter-spacing:0.04em; margin-bottom:4px;">Total Pendapatan Sementara</div>
                                <div class="font-mono font-bold" style="font-size:18px; color:var(--color-ink); padding-bottom:6px; margin-bottom:8px; border-bottom:1px solid var(--color-hairline);" x-text="formatRupiah(editForm.pendapatan_awal)"></div>
                                <div class="space-y-1" style="font-size:11.5px;">
                                    <template x-for="b in editForm.breakdown" :key="b.label">
                                        <div style="display:flex; justify-content:space-between; align-items:center; color:var(--color-ink-mute);">
                                            <span x-text="b.label"></span>
                                            <span class="font-mono font-semibold" style="color:var(--color-ink);" x-text="formatRupiah(b.val)"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Panel Kanan: Potongan Kasbon Aktif -->
                            <div class="money-panel-danger">
                                <div style="display:flex; justify-content:space-between; align-items:center; gap:6px; margin-bottom:6px;">
                                    <span style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">Potongan Kasbon</span>
                                    <span class="badge badge-mono" style="background:#ffffff; color:#dc2626; border:1px solid #fecaca; font-size:10.5px; padding:1px 6px;">
                                        Sisa: <strong x-text="formatRupiah(editForm.max_kasbon)"></strong>
                                    </span>
                                </div>
                                <input type="text" inputmode="numeric" class="form-input font-mono font-bold text-rose-600 dark:text-rose-400" style="font-size:16px; background:#ffffff;"
                                       :value="formatRupiahNumber(editForm.total_potongan_kasbon)"
                                       @input="setMoney('total_potongan_kasbon', $event)">
                                <template x-if="errKasbon">
                                    <div class="text-rose-600 text-[11.5px] font-semibold mt-1.5 flex items-center gap-1">
                                        <i data-lucide="alert-triangle" style="width:12px; height:12px;"></i>
                                        <span>Potongan melebihi sisa kasbon aktif karyawan!</span>
                                    </div>
                                </template>

                                <template x-if="editForm.penarikan_gaji > 0">
                                    <div style="margin-top:10px; padding-top:8px; border-top:1px dashed #fecaca; display:flex; justify-content:space-between; align-items:center; font-size:11.5px; font-weight:700;">
                                        <span>Penarikan Gaji (Tetap)</span>
                                        <span class="font-mono" x-text="formatRupiah(editForm.penarikan_gaji)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Section: Penambahan & Pemotongan Manual -->
                        <div class="space-y-3 pt-1">
                            <div style="font-size:12px; font-weight:700; color:var(--color-ink); border-bottom:1px solid var(--color-hairline); padding-bottom:4px;">
                                Penambahan &amp; Pemotongan Manual
                            </div>

                            <!-- Baris Bonus -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="form-label text-emerald-600 font-bold">Bonus / Penambahan Lain (Rp)</label>
                                    <input type="text" inputmode="numeric" class="form-input font-mono font-semibold text-emerald-600"
                                           :value="formatRupiahNumber(editForm.tunjangan_lain)"
                                           @input="setMoney('tunjangan_lain', $event)">
                                </div>
                                <div>
                                    <label class="form-label">Keterangan Bonus</label>
                                    <input type="text" name="catatan_tunjangan_lain" x-model="editForm.catatan_tunjangan_lain" placeholder="Misal: Bonus lembur, transport" class="form-input">
                                </div>
                            </div>

                            <!-- Baris Potongan Manual -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="form-label text-rose-600 font-bold">Pemotongan Manual Lain (Rp)</label>
                                    <input type="text" inputmode="numeric" class="form-input font-mono font-semibold text-rose-600"
                                           :value="formatRupiahNumber(editForm.potongan_lain)"
                                           @input="setMoney('potongan_lain', $event)">
                                </div>
                                <div>
                                    <label class="form-label">Keterangan Pemotongan</label>
                                    <input type="text" name="catatan_potongan_lain" x-model="editForm.catatan_potongan_lain" placeholder="Misal: Denda keterlambatan, ganti rugi" class="form-input">
                                </div>
                            </div>
                        </div>

                        <!-- Section: Tabungan Karyawan -->
                        <div class="space-y-2.5 pt-1">
                            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-hairline); padding-bottom:4px;">
                                <span style="font-size:12px; font-weight:700; color:var(--color-ink);">Tabungan Karyawan</span>
                                <span class="badge badge-mono" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; font-size:11px;">
                                    Total Tabungan: <strong x-text="formatRupiah(editForm.saldo_tabungan)"></strong>
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 rounded-lg border" style="background:var(--color-canvas-soft); border-color:var(--color-hairline);">
                                <div>
                                    <label class="form-label text-rose-600 font-bold">
                                        Setor Tabungan (Rp) <span style="font-weight:400; color:var(--color-ink-mute); font-size:11px;">- memotong gaji</span>
                                    </label>
                                    <input type="text" inputmode="numeric" class="form-input font-mono font-semibold text-rose-600"
                                           :value="formatRupiahNumber(editForm.total_potongan_tabungan)"
                                           @input="setMoney('total_potongan_tabungan', $event)">
                                </div>
                                <div>
                                    <label class="form-label text-emerald-600 font-bold">
                                        Tarik Tabungan (Rp) <span style="font-weight:400; color:var(--color-ink-mute); font-size:11px;">- menambah gaji</span>
                                    </label>
                                    <input type="text" inputmode="numeric" class="form-input font-mono font-semibold text-emerald-600"
                                           :value="formatRupiahNumber(editForm.penarikan_tabungan)"
                                           @input="setMoney('penarikan_tabungan', $event)">
                                    <template x-if="errTarikTab">
                                        <div class="text-rose-600 text-[11px] font-semibold mt-1 flex items-center gap-1">
                                            <i data-lucide="alert-triangle" style="width:12px; height:12px;"></i>
                                            <span>Penarikan melebihi saldo tabungan (<strong x-text="formatRupiah(editForm.saldo_tabungan)"></strong>)!</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Pembulatan Gaji -->
                        <div class="space-y-2 pt-1">
                            <div style="font-size:12px; font-weight:700; color:var(--color-ink); border-bottom:1px solid var(--color-hairline); padding-bottom:4px;">
                                Pembulatan Gaji
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center p-3 rounded-lg border" style="background:var(--color-canvas-soft); border-color:var(--color-hairline);">
                                <div style="font-size:11.5px; color:var(--color-ink-mute); line-height:1.4;">
                                    Jika angka net gaji tidak bulat (misal nominal perak), Anda dapat menginput angka penambah/pengurang pembulatan (contoh: 500 atau -500).
                                </div>
                                <div>
                                    <label class="form-label font-bold">Nominal Pembulatan (Rp)</label>
                                    <input type="text" inputmode="numeric" class="form-input font-mono font-semibold"
                                           :value="editForm.nominal_pembulatan !== 0 ? ((editForm.nominal_pembulatan < 0 ? '-' : '') + formatRupiahNumber(Math.abs(editForm.nominal_pembulatan))) : '0'"
                                           @input="setMoney('nominal_pembulatan', $event, true)">
                                </div>
                            </div>
                        </div>

                        <!-- Section: Metode Pembayaran Gaji (Prominent High-Contrast Radio Cards) -->
                        <div class="space-y-3 pt-1">
                            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-hairline); padding-bottom:5px;">
                                <div>
                                    <span style="font-size:12px; font-weight:700; color:var(--color-ink);">Metode Pembayaran Gaji</span>
                                    <span style="font-size:11px; color:var(--color-ink-mute); margin-left:6px;">Pilih jalur pencairan gaji karyawan ini</span>
                                </div>
                                <span class="badge text-[11px]" :class="editForm.metode_pembayaran === 'transfer' ? 'badge-info' : 'badge-success'">
                                    <span x-text="editForm.metode_pembayaran === 'transfer' ? 'Transfer Bank' : 'Tunai / Cash'"></span>
                                </span>
                            </div>

                            <!-- Hidden inputs POST update-item -->
                            <input type="hidden" name="metode_pembayaran" :value="editForm.metode_pembayaran">
                            <input type="hidden" name="bank_nama" :value="editForm.bank_nama">
                            <input type="hidden" name="bank_nomor_rekening" :value="editForm.bank_nomor_rekening">
                            <input type="hidden" name="bank_atas_nama" :value="editForm.bank_atas_nama">

                            <!-- Prominent 2-Column Method Cards (Clean, No Circle) -->
                            <div class="payroll-method-grid">
                                <!-- Card 1: Tunai (Cash) -->
                                <button type="button" 
                                        @click="selectMetode('tunai')"
                                        :class="editForm.metode_pembayaran === 'tunai' ? 'is-active-tunai' : ''"
                                        class="payroll-method-card">
                                    <div class="payroll-method-icon is-tunai">
                                        <svg style="width:20px;height:20px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-xs" :class="editForm.metode_pembayaran === 'tunai' ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-800 dark:text-slate-200'">Tunai (Cash)</span>
                                            <span x-show="editForm.metode_pembayaran === 'tunai'" class="payroll-kas-pos-pill" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;">Terpilih</span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-tight">
                                            Dibayarkan fisik / amplop via kas tunai
                                        </div>
                                    </div>
                                </button>

                                <!-- Card 2: Transfer Bank -->
                                <button type="button" 
                                        @click="selectMetode('transfer')"
                                        :class="editForm.metode_pembayaran === 'transfer' ? 'is-active-transfer' : ''"
                                        class="payroll-method-card">
                                    <div class="payroll-method-icon is-transfer">
                                        <svg style="width:20px;height:20px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-xs" :class="editForm.metode_pembayaran === 'transfer' ? 'text-blue-700 dark:text-blue-400' : 'text-slate-800 dark:text-slate-200'">Transfer Bank</span>
                                            <span x-show="editForm.metode_pembayaran === 'transfer'" class="payroll-kas-pos-pill" style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;">Terpilih</span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-tight">
                                            Ditransfer langsung ke rekening bank
                                        </div>
                                    </div>
                                </button>
                            </div>

                            <!-- Bank Details Input if Transfer Selected -->
                            <div x-show="editForm.metode_pembayaran === 'transfer'" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-cloak 
                                 class="p-3 rounded-lg border space-y-2.5 bg-blue-50/50 dark:bg-blue-950/25" 
                                 style="border-color:rgba(59,130,246,0.3);">

                                <!-- CASE 1: Karyawan sudah memiliki rekening master (Otomatis & Tanpa Perlu Ketik Ulang) -->
                                <template x-if="editForm.has_master_bank">
                                    <div class="space-y-2">
                                        <div class="payroll-bank-card">
                                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                                <!-- Emblem / Logo Bank Modern -->
                                                <div class="payroll-bank-emblem" 
                                                     :style="{ backgroundColor: getBankBadge(editForm.bank_nama).bg, color: getBankBadge(editForm.bank_nama).color }">
                                                    <svg style="width:15px;height:15px;margin-bottom:2px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/>
                                                    </svg>
                                                    <span x-text="getBankBadge(editForm.bank_nama).label" style="font-size:9.5px;font-weight:900;letter-spacing:0.04em;line-height:1;text-transform:uppercase;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;"></span>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <strong class="text-xs text-slate-800 dark:text-slate-100" style="font-size:12.5px; font-weight:700;" x-text="editForm.bank_nama"></strong>
                                                        <span class="text-slate-400" style="font-size:10px;">•</span>
                                                        <span class="font-mono text-xs font-bold text-blue-700 dark:text-blue-300" style="font-size:12.5px; letter-spacing:0.02em;" x-text="editForm.bank_nomor_rekening"></span>
                                                        <span class="payroll-bank-master-pill">Rekening Master</span>
                                                    </div>
                                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-1">
                                                        a.n <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="editForm.bank_atas_nama || editForm.karyawan_nama"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Tombol Ubah Rekening (Sleek Smooth Pill) -->
                                            <button type="button" 
                                                    @click="editForm.show_custom_bank = !editForm.show_custom_bank" 
                                                    class="payroll-bank-action-btn">
                                                <template x-if="!editForm.show_custom_bank">
                                                    <svg style="width:12px;height:12px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                                </template>
                                                <template x-if="editForm.show_custom_bank">
                                                    <svg style="width:12px;height:12px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                                </template>
                                                <span x-text="editForm.show_custom_bank ? 'Tutup Koreksi' : 'Ubah Rekening'"></span>
                                            </button>
                                        </div>

                                        <!-- Form opsional jika ingin mengubah rekening khusus untuk slip payroll ini saja -->
                                        <div x-show="editForm.show_custom_bank" x-cloak class="payroll-bank-custom-panel space-y-2">
                                            <div class="text-[11px] font-semibold text-slate-700 dark:text-slate-300 flex items-center justify-between">
                                                <span>Koreksi rekening tujuan untuk slip penggajian ini saja:</span>
                                                <button type="button" 
                                                        @click="editForm.bank_nama = editForm.master_bank_nama; editForm.bank_nomor_rekening = editForm.master_bank_nomor_rekening; editForm.bank_atas_nama = editForm.master_bank_atas_nama; editForm.show_custom_bank = false;" 
                                                        class="payroll-bank-reset-btn">
                                                    <svg style="width:11px;height:11px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                                    <span>Reset ke Master</span>
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                                <div>
                                                    <label class="form-label text-[11px]">Nama Bank</label>
                                                    <input type="text" x-model="editForm.bank_nama" placeholder="BCA / BRI / Mandiri" class="form-input text-xs" style="height:32px;">
                                                </div>
                                                <div>
                                                    <label class="form-label text-[11px]">Nomor Rekening</label>
                                                    <input type="text" x-model="editForm.bank_nomor_rekening" placeholder="Nomor rekening" class="form-input text-xs font-mono" style="height:32px;">
                                                </div>
                                                <div>
                                                    <label class="form-label text-[11px]">Atas Nama</label>
                                                    <input type="text" x-model="editForm.bank_atas_nama" placeholder="Nama pemilik rekening" class="form-input text-xs" style="height:32px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- CASE 2: Karyawan belum memiliki rekening di master data -->
                                <template x-if="!editForm.has_master_bank">
                                    <div class="space-y-2">
                                        <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 text-[11px] text-amber-800 dark:text-amber-200 flex items-center gap-2">
                                            <svg style="width:14px;height:14px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                            <span>Karyawan ini belum memiliki rekening di master data. Masukkan rekening tujuan transfer untuk payroll ini:</span>
                                        </div>
                                        <div class="p-3 rounded-lg border bg-white dark:bg-slate-900 border-blue-200 dark:border-blue-900/60 shadow-sm space-y-2.5">
                                            <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100 dark:border-slate-800">
                                                <div class="payroll-bank-emblem" 
                                                     :style="{ backgroundColor: getBankBadge(editForm.bank_nama).bg, color: getBankBadge(editForm.bank_nama).color }">
                                                    <svg style="width:15px;height:15px;margin-bottom:2px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/>
                                                    </svg>
                                                    <span x-text="getBankBadge(editForm.bank_nama).label" style="font-size:9.5px;font-weight:900;letter-spacing:0.04em;line-height:1;text-transform:uppercase;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;"></span>
                                                </div>
                                                <div>
                                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-100" x-text="editForm.bank_nama ? ('Rekening Bank ' + editForm.bank_nama) : 'Rekening Bank Baru'"></div>
                                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Rekening ini otomatis dicatat pada slip &amp; ledger payroll periode ini</div>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                                <div>
                                                    <label class="form-label text-xs font-semibold text-slate-700 dark:text-slate-300">Nama Bank *</label>
                                                    <input type="text" x-model="editForm.bank_nama" placeholder="Contoh: BCA / BRI / Mandiri" class="form-input text-xs" style="height:35px;">
                                                </div>
                                                <div>
                                                    <label class="form-label text-xs font-semibold text-slate-700 dark:text-slate-300">Nomor Rekening *</label>
                                                    <input type="text" x-model="editForm.bank_nomor_rekening" placeholder="Nomor rekening tujuan" class="form-input text-xs font-mono" style="height:35px;">
                                                </div>
                                                <div>
                                                    <label class="form-label text-xs font-semibold text-slate-700 dark:text-slate-300">Atas Nama *</label>
                                                    <input type="text" x-model="editForm.bank_atas_nama" placeholder="Nama pemilik rekening" class="form-input text-xs" style="height:35px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div class="text-[11px] text-blue-700 dark:text-blue-300 flex items-center gap-1.5 pt-0.5">
                                    <svg style="width:13px;height:13px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                    <span>Pilihan metode &amp; rekening ini langsung tercatat pada slip &amp; ledger payroll periode ini.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Banner Realtime: Net Gaji Setelah Penyesuaian (Layer 1 Guard) -->
                        <div class="net-banner" :class="editNet < 0 ? 'is-negative' : ''">
                            <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;"
                                 :class="editNet < 0 ? 'text-rose-700 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300'">
                                Net Gaji Setelah Penyesuaian
                            </div>
                            <div class="font-mono font-bold" style="font-size:26px; margin-top:2px;"
                                 :class="editNet < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'"
                                 x-text="formatRupiah(editNet)"></div>
                            <template x-if="editNet < 0">
                                <div class="text-rose-600 text-xs font-semibold mt-1 flex items-center gap-1 justify-center">
                                    <i data-lucide="alert-triangle" style="width:13px; height:13px;"></i>
                                    <span>Total potongan melebihi pendapatan. Gaji bersih tidak boleh bernilai negatif!</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Modal Footer (Sticky Realtime Net Indicator & Guarded Actions) -->
                    <div class="modal-footer" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                        <!-- Live Net Salary Indicator (Selalu terlihat tanpa harus scroll ke bawah) -->
                        <div class="flex items-center gap-2">
                            <span style="font-size:11.5px; color:var(--color-ink-mute); font-weight:600;">Net Diterima:</span>
                            <span class="font-mono font-bold" style="font-size:16px;"
                                  :class="editNet < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'"
                                  x-text="formatRupiah(editNet)"></span>
                            <template x-if="editNet < 0">
                                <span class="badge badge-danger" style="font-size:10px; padding:1px 6px; font-weight:700;">MINUS</span>
                            </template>
                            <template x-if="errKasbon">
                                <span class="badge badge-warning" style="font-size:10px; padding:1px 6px; font-weight:700;">KASBON OVER</span>
                            </template>
                            <template x-if="errTarikTab">
                                <span class="badge badge-warning" style="font-size:10px; padding:1px 6px; font-weight:700;">TABUNGAN OVER</span>
                            </template>
                        </div>

                        <!-- Tombol Aksi Modal -->
                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <button type="button" @click="showEditModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                            <button type="submit" class="btn btn-primary w-full sm:w-auto"
                                    style="display:inline-flex; align-items:center; justify-content:center; gap:6px;"
                                    :disabled="editInvalid"
                                    :class="{'opacity-50 cursor-not-allowed': editInvalid}">
                                <i data-lucide="save" style="width:16px; height:16px;"></i>
                                <span x-text="editNet < 0 ? 'Gaji Bersih Minus' : (editInvalid ? 'Data Belum Valid' : 'Simpan Penyesuaian')"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL 2: KONFIRMASI KECUALIKAN KARYAWAN -->
    <template x-teleport="body">
        <div x-show="showExcludeModal" x-cloak class="modal-backdrop" @click="showExcludeModal = false">
            <div class="modal-box modal-box-md" style="max-width: 480px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(239,68,68,0.12); color:#dc2626; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i data-lucide="user-x" style="width:20px; height:20px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title">Kecualikan: <span class="text-rose-600 font-bold" x-text="excludeForm.nama"></span></div>
                            <div style="font-size:12px; color:var(--color-ink-mute); margin-top:1px;">Karyawan tidak akan diproses pada payroll ini.</div>
                        </div>
                    </div>
                    <button type="button" @click="showExcludeModal = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px; height:18px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/penggajian/toggle-exclude') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="run_id" value="<?= $run['id'] ?>">
                    <input type="hidden" name="item_id" :value="excludeForm.item_id">

                    <div class="modal-body custom-scrollbar space-y-3">
                        <p style="font-size:12.5px; color:var(--color-ink-mute); line-height:1.5;">
                            Karyawan ini tidak akan diikutsertakan dalam payroll periode ini. Data absensi, produksi, dan kasbonnya tidak akan dikunci sehingga dapat ditarik pada payroll berikutnya.
                        </p>
                        <div>
                            <label class="form-label font-bold">Alasan Pengecualian (Opsional)</label>
                            <textarea name="catatan_pengecualian" x-model="excludeForm.alasan" rows="3" placeholder="Tulis alasan pengecualian jika ada..." class="form-input"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="showExcludeModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                        <button type="submit" class="btn btn-danger btn-solid w-full sm:w-auto" style="display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                            <i data-lucide="user-x" style="width:16px; height:16px;"></i>
                            <span>Kecualikan Karyawan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL 3: OTORISASI & PEMBAYARAN PAYROLL -->
    <template x-teleport="body">
        <div x-show="showApproveModal" x-cloak class="modal-backdrop" @click="showApproveModal = false">
            <div class="modal-box modal-box-md" style="max-width: 580px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(16,185,129,0.12); color:#10b981; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg style="width:20px;height:20px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title">Otorisasi &amp; Pembayaran Payroll</div>
                            <div style="font-size:12px; color:var(--color-ink-mute); margin-top:1px;">Konfirmasi pengeluaran kas dan pemotongan saldo kas otomatis.</div>
                        </div>
                    </div>
                    <button type="button" @click="showApproveModal = false" class="modal-close-x" title="Tutup Modal">
                        <svg style="width:18px;height:18px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>

                <form action="<?= Router::url('/penggajian/approve') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="penggajian_id" value="<?= $run['id'] ?>">
                    <input type="hidden" name="akun_kas_id" :value="selectedKasTunaiId || selectedKasTransferId || ''">
                    <input type="hidden" name="akun_kas_tunai_id" :value="selectedKasTunaiId">
                    <input type="hidden" name="akun_kas_transfer_id" :value="selectedKasTransferId">
                    <input type="hidden" name="akun_kas_tabungan_sumber_id" :value="selectedKasTabunganId">

                    <div class="modal-body custom-scrollbar space-y-4">
                        <!-- Highlight & Breakdown Card -->
                        <div class="p-3 rounded-lg border border-slate-200/90 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/40 space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-medium text-slate-600 dark:text-slate-400">Total Gaji Bersih (Net Dibayarkan):</span>
                                <span class="font-mono font-bold text-sm text-slate-900 dark:text-slate-100"><?= Format::rupiah($totalGajiBersih) ?></span>
                            </div>
                            <?php if ($totalGajiTunai > 0): ?>
                            <div class="flex justify-between items-center text-xs border-t border-slate-200/80 dark:border-slate-800 pt-1.5">
                                <span class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-semibold">
                                    <svg style="width:14px;height:14px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                    <span>Gaji Tunai / Cash (<?= $countTunai ?> Karyawan):</span>
                                </span>
                                <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400"><?= Format::rupiah($totalGajiTunai) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($totalGajiTransfer > 0): ?>
                            <div class="flex justify-between items-center text-xs border-t border-slate-200/80 dark:border-slate-800 pt-1.5">
                                <span class="flex items-center gap-1.5 text-blue-700 dark:text-blue-400 font-semibold">
                                    <svg style="width:14px;height:14px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                    <span>Gaji Transfer Bank (<?= $countTransfer ?> Karyawan):</span>
                                </span>
                                <span class="font-mono font-bold text-blue-700 dark:text-blue-400"><?= Format::rupiah($totalGajiTransfer) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($totalPotonganTabunganAll > 0): ?>
                            <div class="flex justify-between items-center text-xs border-t border-slate-200/80 dark:border-slate-800 pt-1.5">
                                <span class="flex items-center gap-1 text-purple-700 dark:text-purple-300">
                                    <svg style="width:13px;height:13px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    <span>Transfer ke Kas Tabungan (Escrow):</span>
                                </span>
                                <span class="font-mono font-bold text-purple-700 dark:text-purple-300">+<?= Format::rupiah($totalPotonganTabunganAll) ?></span>
                            </div>
                            <div class="flex justify-between items-center text-xs border-t border-slate-200/80 dark:border-slate-800 pt-1.5 font-medium">
                                <span class="text-slate-700 dark:text-slate-300">Total Kebutuhan Kas Operasional:</span>
                                <span class="font-mono font-bold text-slate-900 dark:text-slate-100"><?= Format::rupiah($totalKebutuhanKasOperasional) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($totalPenarikanTabunganAll > 0): ?>
                            <div class="flex justify-between items-center text-xs border-t border-slate-200/80 dark:border-slate-800 pt-1.5">
                                <span class="flex items-center gap-1 text-amber-700 dark:text-amber-300">
                                    <svg style="width:13px;height:13px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                                    <span>Reimbursement dari Kas Tabungan:</span>
                                </span>
                                <span class="font-mono font-bold text-amber-700 dark:text-amber-300">+<?= Format::rupiah($totalPenarikanTabunganAll) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex justify-between items-center text-[11px] text-slate-500 dark:text-slate-400 border-t border-slate-200/80 dark:border-slate-800 pt-1.5">
                                <span>Karyawan Disertakan:</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300"><?= $includedCount ?> orang <span class="font-normal text-slate-400">(dari <?= count($items) ?> total)</span></span>
                            </div>
                        </div>

                        <!-- 1. Akun Kas Tunai (Hanya muncul jika ada gaji tunai) -->
                        <template x-if="totalGajiTunai > 0">
                            <div class="space-y-2 p-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/30"
                                 :style="openKasDropdown === 'tunai' ? 'position: relative; z-index: 35;' : 'position: relative; z-index: 25;'">
                                <div class="payroll-section-header">
                                    <div class="payroll-section-title">
                                        <svg style="width:14px;height:14px;display:block;color:#059669;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                        <span>Akun Kas untuk Gaji Tunai</span>
                                        <span class="text-rose-500 font-bold">*</span>
                                    </div>
                                    <div class="payroll-kebutuhan-badge is-tunai">
                                        <span class="badge-label">Kebutuhan:</span>
                                        <span class="badge-amount" x-text="formatRupiah(totalGajiTunai)"></span>
                                        <span class="badge-sep">·</span>
                                        <span class="badge-count" x-text="countTunai + ' org'"></span>
                                    </div>
                                </div>

                                <!-- Modern Searchable Dropdown: Kas Tunai -->
                                <div class="relative" @click.outside="if (openKasDropdown === 'tunai') openKasDropdown = null">
                                    <button type="button"
                                            @click="openKasDropdown = openKasDropdown === 'tunai' ? null : 'tunai'; if (openKasDropdown === 'tunai') $nextTick(() => $refs.searchTunaiInput?.focus())"
                                            class="payroll-dropdown-trigger"
                                            :class="{ 'is-open': openKasDropdown === 'tunai' }">
                                        <div class="flex items-center gap-2.5 min-w-0 flex-1 pr-2">
                                            <div class="payroll-kas-icon shrink-0" :class="getAccount(selectedKasTunaiId)?.tipe_akun === 'bank' ? 'is-bank' : 'is-tunai'">
                                                <template x-if="getAccount(selectedKasTunaiId)?.tipe_akun === 'bank'">
                                                    <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                                </template>
                                                <template x-if="getAccount(selectedKasTunaiId)?.tipe_akun !== 'bank'">
                                                    <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                                </template>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate" x-text="getAccount(selectedKasTunaiId)?.nama_akun || '-- Pilih Akun Kas Tunai --'"></span>
                                                    <span x-show="getAccount(selectedKasTunaiId)?.is_default_pos" class="payroll-kas-pos-pill">POS</span>
                                                </div>
                                                <div class="text-[11px] font-mono mt-0.5 text-slate-500 dark:text-slate-400 flex items-center gap-1 flex-wrap" x-show="getAccount(selectedKasTunaiId)">
                                                    <span>Saldo Terkini:</span>
                                                    <strong class="tabular-nums font-mono"
                                                            :class="(getAccount(selectedKasTunaiId)?.saldo < (accountNeeds[selectedKasTunaiId] || totalGajiTunai)) ? 'payroll-kas-saldo-insufficient' : 'payroll-kas-saldo-sufficient'"
                                                            x-text="formatRupiah(getAccount(selectedKasTunaiId)?.saldo || 0)"></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <template x-if="getAccount(selectedKasTunaiId)">
                                                <span x-show="getAccount(selectedKasTunaiId).saldo >= (accountNeeds[selectedKasTunaiId] || totalGajiTunai)" class="payroll-sufficient-badge hidden sm:inline-flex">Mencukupi</span>
                                            </template>
                                            <template x-if="getAccount(selectedKasTunaiId)">
                                                <span x-show="getAccount(selectedKasTunaiId).saldo < (accountNeeds[selectedKasTunaiId] || totalGajiTunai)" class="payroll-deficit-badge hidden sm:inline-flex">Kurang</span>
                                            </template>
                                            <svg style="width:16px;height:16px;display:block;" class="text-slate-400 transition-transform duration-150" :class="openKasDropdown === 'tunai' ? 'rotate-180 text-emerald-600' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                        </div>
                                    </button>

                                    <!-- Dropdown Menu: Tunai -->
                                    <div x-show="openKasDropdown === 'tunai'"
                                         x-cloak
                                         class="payroll-dropdown-menu">
                                        <div class="payroll-dropdown-search-wrap">
                                            <div class="relative flex items-center w-full">
                                                <svg class="payroll-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="11" cy="11" r="8"></circle>
                                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                                </svg>
                                                <input type="text"
                                                       x-ref="searchTunaiInput"
                                                       x-model="searchKasTunai"
                                                       placeholder="Cari nama akun atau tipe kas..."
                                                       class="payroll-dropdown-search-input"
                                                       @keydown.escape="openKasDropdown = null">
                                                <button type="button"
                                                        x-show="searchKasTunai"
                                                        @click="searchKasTunai = ''; $refs.searchTunaiInput?.focus()"
                                                        class="payroll-search-clear">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="payroll-dropdown-options-list custom-scrollbar">
                                            <template x-for="acc in getFilteredAccounts(searchKasTunai)" :key="'opt-tunai-' + acc.id">
                                                <div @click="selectedKasTunaiId = acc.id; openKasDropdown = null; searchKasTunai = '';"
                                                     class="payroll-dropdown-option"
                                                     :class="{
                                                         'is-selected': String(selectedKasTunaiId) === String(acc.id)
                                                     }">
                                                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                                        <div class="payroll-kas-icon shrink-0" :class="acc.tipe_akun === 'kas_tunai' ? 'is-tunai' : 'is-bank'">
                                                            <template x-if="acc.tipe_akun === 'kas_tunai'">
                                                                <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                                            </template>
                                                            <template x-if="acc.tipe_akun !== 'kas_tunai'">
                                                                <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                                            </template>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate" x-text="acc.nama_akun"></span>
                                                                <span x-show="acc.is_default_pos" class="payroll-kas-pos-pill">POS</span>
                                                                <span class="payroll-kas-type-pill" x-text="acc.tipe_akun === 'kas_tunai' ? 'Kas Tunai' : 'Bank'"></span>
                                                            </div>
                                                            <div class="text-[11px] font-mono mt-0.5 text-slate-500 dark:text-slate-400">
                                                                Saldo: <strong :class="acc.saldo < (accountNeeds[acc.id] || totalGajiTunai) ? 'payroll-kas-saldo-insufficient' : 'payroll-kas-saldo-sufficient'" class="tabular-nums font-mono" x-text="formatRupiah(acc.saldo)"></strong>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-2 shrink-0">
                                                        <template x-if="acc.saldo < (accountNeeds[acc.id] || totalGajiTunai)">
                                                            <span class="payroll-deficit-badge">Saldo Kurang</span>
                                                        </template>
                                                        <template x-if="acc.saldo >= (accountNeeds[acc.id] || totalGajiTunai)">
                                                            <span class="payroll-sufficient-badge">Cukup</span>
                                                        </template>
                                                        <div x-show="String(selectedKasTunaiId) === String(acc.id)" class="payroll-selected-check">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- Empty State -->
                                            <div x-show="getFilteredAccounts(searchKasTunai).length === 0" class="payroll-dropdown-empty">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="m14 8-6 6"/><path d="m8 8 6 6"/></svg>
                                                <span>Tidak ada akun kas yang sesuai pencarian</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- 2. Akun Bank Transfer (Hanya muncul jika ada gaji transfer) -->
                        <template x-if="totalGajiTransfer > 0">
                            <div class="space-y-2 p-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/30"
                                 :style="openKasDropdown === 'transfer' ? 'position: relative; z-index: 35;' : 'position: relative; z-index: 20;'">
                                <div class="payroll-section-header">
                                    <div class="payroll-section-title">
                                        <svg style="width:14px;height:14px;display:block;color:#2563eb;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                        <span>Akun Bank untuk Gaji Transfer</span>
                                        <span class="text-rose-500 font-bold">*</span>
                                    </div>
                                    <div class="payroll-kebutuhan-badge is-transfer">
                                        <span class="badge-label">Kebutuhan:</span>
                                        <span class="badge-amount" x-text="formatRupiah(totalGajiTransfer)"></span>
                                        <span class="badge-sep">·</span>
                                        <span class="badge-count" x-text="countTransfer + ' org'"></span>
                                    </div>
                                </div>

                                <!-- Modern Searchable Dropdown: Bank Transfer -->
                                <div class="relative" @click.outside="if (openKasDropdown === 'transfer') openKasDropdown = null">
                                    <button type="button"
                                            @click="openKasDropdown = openKasDropdown === 'transfer' ? null : 'transfer'; if (openKasDropdown === 'transfer') $nextTick(() => $refs.searchTransferInput?.focus())"
                                            class="payroll-dropdown-trigger"
                                            :class="{ 'is-open': openKasDropdown === 'transfer' }">
                                        <div class="flex items-center gap-2.5 min-w-0 flex-1 pr-2">
                                            <div class="payroll-kas-icon shrink-0" :class="getAccount(selectedKasTransferId)?.tipe_akun === 'bank' ? 'is-bank' : 'is-tunai'">
                                                <template x-if="getAccount(selectedKasTransferId)?.tipe_akun === 'bank'">
                                                    <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                                </template>
                                                <template x-if="getAccount(selectedKasTransferId)?.tipe_akun !== 'bank'">
                                                    <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                                </template>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate" x-text="getAccount(selectedKasTransferId)?.nama_akun || '-- Pilih Akun Bank Transfer --'"></span>
                                                    <span x-show="getAccount(selectedKasTransferId)?.is_default_pos" class="payroll-kas-pos-pill">POS</span>
                                                </div>
                                                <div class="text-[11px] font-mono mt-0.5 text-slate-500 dark:text-slate-400 flex items-center gap-1 flex-wrap" x-show="getAccount(selectedKasTransferId)">
                                                    <span>Saldo Terkini:</span>
                                                    <strong class="tabular-nums font-mono"
                                                            :class="(getAccount(selectedKasTransferId)?.saldo < (accountNeeds[selectedKasTransferId] || totalGajiTransfer)) ? 'payroll-kas-saldo-insufficient' : 'payroll-kas-saldo-sufficient'"
                                                            x-text="formatRupiah(getAccount(selectedKasTransferId)?.saldo || 0)"></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <template x-if="getAccount(selectedKasTransferId)">
                                                <span x-show="getAccount(selectedKasTransferId).saldo >= (accountNeeds[selectedKasTransferId] || totalGajiTransfer)" class="payroll-sufficient-badge hidden sm:inline-flex">Mencukupi</span>
                                            </template>
                                            <template x-if="getAccount(selectedKasTransferId)">
                                                <span x-show="getAccount(selectedKasTransferId).saldo < (accountNeeds[selectedKasTransferId] || totalGajiTransfer)" class="payroll-deficit-badge hidden sm:inline-flex">Kurang</span>
                                            </template>
                                            <svg style="width:16px;height:16px;display:block;" class="text-slate-400 transition-transform duration-150" :class="openKasDropdown === 'transfer' ? 'rotate-180 text-blue-600' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                        </div>
                                    </button>

                                    <!-- Dropdown Menu: Transfer -->
                                    <div x-show="openKasDropdown === 'transfer'"
                                         x-cloak
                                         class="payroll-dropdown-menu">
                                        <div class="payroll-dropdown-search-wrap">
                                            <div class="relative flex items-center w-full">
                                                <svg class="payroll-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="11" cy="11" r="8"></circle>
                                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                                </svg>
                                                <input type="text"
                                                       x-ref="searchTransferInput"
                                                       x-model="searchKasTransfer"
                                                       placeholder="Cari nama akun atau bank..."
                                                       class="payroll-dropdown-search-input"
                                                       @keydown.escape="openKasDropdown = null">
                                                <button type="button"
                                                        x-show="searchKasTransfer"
                                                        @click="searchKasTransfer = ''; $refs.searchTransferInput?.focus()"
                                                        class="payroll-search-clear">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="payroll-dropdown-options-list custom-scrollbar">
                                            <template x-for="acc in getFilteredAccounts(searchKasTransfer)" :key="'opt-transfer-' + acc.id">
                                                <div @click="selectedKasTransferId = acc.id; openKasDropdown = null; searchKasTransfer = '';"
                                                     class="payroll-dropdown-option"
                                                     :class="{
                                                         'is-selected': String(selectedKasTransferId) === String(acc.id)
                                                     }">
                                                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                                        <div class="payroll-kas-icon shrink-0" :class="acc.tipe_akun === 'kas_tunai' ? 'is-tunai' : 'is-bank'">
                                                            <template x-if="acc.tipe_akun === 'kas_tunai'">
                                                                <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                                            </template>
                                                            <template x-if="acc.tipe_akun !== 'kas_tunai'">
                                                                <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                                            </template>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate" x-text="acc.nama_akun"></span>
                                                                <span x-show="acc.is_default_pos" class="payroll-kas-pos-pill">POS</span>
                                                                <span class="payroll-kas-type-pill" x-text="acc.tipe_akun === 'kas_tunai' ? 'Kas Tunai' : 'Bank'"></span>
                                                            </div>
                                                            <div class="text-[11px] font-mono mt-0.5 text-slate-500 dark:text-slate-400">
                                                                Saldo: <strong :class="acc.saldo < (accountNeeds[acc.id] || totalGajiTransfer) ? 'payroll-kas-saldo-insufficient' : 'payroll-kas-saldo-sufficient'" class="tabular-nums font-mono" x-text="formatRupiah(acc.saldo)"></strong>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-2 shrink-0">
                                                        <template x-if="acc.saldo < (accountNeeds[acc.id] || totalGajiTransfer)">
                                                            <span class="payroll-deficit-badge">Saldo Kurang</span>
                                                        </template>
                                                        <template x-if="acc.saldo >= (accountNeeds[acc.id] || totalGajiTransfer)">
                                                            <span class="payroll-sufficient-badge">Cukup</span>
                                                        </template>
                                                        <div x-show="String(selectedKasTransferId) === String(acc.id)" class="payroll-selected-check">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- Empty State -->
                                            <div x-show="getFilteredAccounts(searchKasTransfer).length === 0" class="payroll-dropdown-empty">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="m14 8-6 6"/><path d="m8 8 6 6"/></svg>
                                                <span>Tidak ada akun kas yang sesuai pencarian</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- 3. Sumber Kas untuk Setoran Tabungan Escrow (Hanya muncul jika ada potongan tabungan) -->
                        <template x-if="totalPotonganTabunganAll > 0">
                            <div class="space-y-2 p-3 rounded-lg border border-purple-200 dark:border-purple-900/40 bg-purple-50/40 dark:bg-purple-950/20"
                                 :style="openKasDropdown === 'tabungan' ? 'position: relative; z-index: 35;' : 'position: relative; z-index: 15;'">
                                <div class="payroll-section-header">
                                    <div class="payroll-section-title">
                                        <svg style="width:14px;height:14px;display:block;color:#7c3aed;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 5c-1.5 0-2.8 1.4-3 2-3.5-1.5-11-.3-11 5 0 1.8 0 3 2 4.5V20h4v-2h3v2h4v-4c1-.5 1.7-1 2-2h2v-4h-2c0-1-.5-1.5-1-2V5z"/><path d="M2 9v1c0 1.1.9 2 2 2h1"/><circle cx="16" cy="11" r="1"/></svg>
                                        <span>Sumber Kas untuk Setoran Tabungan</span>
                                    </div>
                                    <div class="payroll-kebutuhan-badge is-tabungan">
                                        <span class="badge-label">Kebutuhan:</span>
                                        <span class="badge-amount" x-text="formatRupiah(totalPotonganTabunganAll)"></span>
                                    </div>
                                </div>

                                <!-- Modern Searchable Dropdown: Setoran Tabungan -->
                                <div class="relative" @click.outside="if (openKasDropdown === 'tabungan') openKasDropdown = null">
                                    <button type="button"
                                            @click="openKasDropdown = openKasDropdown === 'tabungan' ? null : 'tabungan'; if (openKasDropdown === 'tabungan') $nextTick(() => $refs.searchTabunganInput?.focus())"
                                            class="payroll-dropdown-trigger"
                                            :class="{ 'is-open': openKasDropdown === 'tabungan' }">
                                        <div class="flex items-center gap-2.5 min-w-0 flex-1 pr-2">
                                            <div class="payroll-kas-icon shrink-0" :class="getAccount(selectedKasTabunganId)?.tipe_akun === 'bank' ? 'is-bank' : 'is-tunai'">
                                                <template x-if="getAccount(selectedKasTabunganId)?.tipe_akun === 'bank'">
                                                    <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                                </template>
                                                <template x-if="getAccount(selectedKasTabunganId)?.tipe_akun !== 'bank'">
                                                    <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                                </template>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate" x-text="getAccount(selectedKasTabunganId)?.nama_akun || '-- Pilih Sumber Kas Tabungan --'"></span>
                                                    <span x-show="getAccount(selectedKasTabunganId)?.is_default_pos" class="payroll-kas-pos-pill">POS</span>
                                                </div>
                                                <div class="text-[11px] font-mono mt-0.5 text-slate-500 dark:text-slate-400 flex items-center gap-1 flex-wrap" x-show="getAccount(selectedKasTabunganId)">
                                                    <span>Saldo Terkini:</span>
                                                    <strong class="tabular-nums font-mono"
                                                            :class="(getAccount(selectedKasTabunganId)?.saldo < (accountNeeds[selectedKasTabunganId] || totalPotonganTabunganAll)) ? 'payroll-kas-saldo-insufficient' : 'payroll-kas-saldo-sufficient'"
                                                            x-text="formatRupiah(getAccount(selectedKasTabunganId)?.saldo || 0)"></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <template x-if="getAccount(selectedKasTabunganId)">
                                                <span x-show="getAccount(selectedKasTabunganId).saldo >= (accountNeeds[selectedKasTabunganId] || totalPotonganTabunganAll)" class="payroll-sufficient-badge hidden sm:inline-flex">Mencukupi</span>
                                            </template>
                                            <template x-if="getAccount(selectedKasTabunganId)">
                                                <span x-show="getAccount(selectedKasTabunganId).saldo < (accountNeeds[selectedKasTabunganId] || totalPotonganTabunganAll)" class="payroll-deficit-badge hidden sm:inline-flex">Kurang</span>
                                            </template>
                                            <svg style="width:16px;height:16px;display:block;" class="text-slate-400 transition-transform duration-150" :class="openKasDropdown === 'tabungan' ? 'rotate-180 text-purple-600' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                        </div>
                                    </button>

                                    <!-- Dropdown Menu: Tabungan -->
                                    <div x-show="openKasDropdown === 'tabungan'"
                                         x-cloak
                                         class="payroll-dropdown-menu">
                                        <div class="payroll-dropdown-search-wrap">
                                            <div class="relative flex items-center w-full">
                                                <svg class="payroll-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="11" cy="11" r="8"></circle>
                                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                                </svg>
                                                <input type="text"
                                                       x-ref="searchTabunganInput"
                                                       x-model="searchKasTabungan"
                                                       placeholder="Cari nama akun atau bank..."
                                                       class="payroll-dropdown-search-input"
                                                       @keydown.escape="openKasDropdown = null">
                                                <button type="button"
                                                        x-show="searchKasTabungan"
                                                        @click="searchKasTabungan = ''; $refs.searchTabunganInput?.focus()"
                                                        class="payroll-search-clear">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="payroll-dropdown-options-list custom-scrollbar">
                                            <template x-for="acc in getFilteredAccounts(searchKasTabungan)" :key="'opt-tabungan-' + acc.id">
                                                <div @click="selectedKasTabunganId = acc.id; openKasDropdown = null; searchKasTabungan = '';"
                                                     class="payroll-dropdown-option"
                                                     :class="{
                                                         'is-selected': String(selectedKasTabunganId) === String(acc.id)
                                                     }">
                                                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                                        <div class="payroll-kas-icon shrink-0" :class="acc.tipe_akun === 'kas_tunai' ? 'is-tunai' : 'is-bank'">
                                                            <template x-if="acc.tipe_akun === 'kas_tunai'">
                                                                <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                                            </template>
                                                            <template x-if="acc.tipe_akun !== 'kas_tunai'">
                                                                <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                                            </template>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate" x-text="acc.nama_akun"></span>
                                                                <span x-show="acc.is_default_pos" class="payroll-kas-pos-pill">POS</span>
                                                                <span class="payroll-kas-type-pill" x-text="acc.tipe_akun === 'kas_tunai' ? 'Kas Tunai' : 'Bank'"></span>
                                                            </div>
                                                            <div class="text-[11px] font-mono mt-0.5 text-slate-500 dark:text-slate-400">
                                                                Saldo: <strong :class="acc.saldo < (accountNeeds[acc.id] || totalPotonganTabunganAll) ? 'payroll-kas-saldo-insufficient' : 'payroll-kas-saldo-sufficient'" class="tabular-nums font-mono" x-text="formatRupiah(acc.saldo)"></strong>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-2 shrink-0">
                                                        <template x-if="acc.saldo < (accountNeeds[acc.id] || totalPotonganTabunganAll)">
                                                            <span class="payroll-deficit-badge">Saldo Kurang</span>
                                                        </template>
                                                        <template x-if="acc.saldo >= (accountNeeds[acc.id] || totalPotonganTabunganAll)">
                                                            <span class="payroll-sufficient-badge">Cukup</span>
                                                        </template>
                                                        <div x-show="String(selectedKasTabunganId) === String(acc.id)" class="payroll-selected-check">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- Empty State -->
                                            <div x-show="getFilteredAccounts(searchKasTabungan).length === 0" class="payroll-dropdown-empty">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="m14 8-6 6"/><path d="m8 8 6 6"/></svg>
                                                <span>Tidak ada akun kas yang sesuai pencarian</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Info Banner Saldo Kurang Dinamis (Minimalis & Modern Clean) -->
                        <template x-if="balanceErrors.length > 0">
                            <div class="payroll-warning-banner">
                                <div class="payroll-warning-header">
                                    <div class="payroll-warning-icon">
                                        <svg style="width:13px;height:13px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                            <line x1="12" y1="9" x2="12" y2="13"/>
                                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="payroll-warning-title">Saldo Akun Kas Belum Mencukupi</div>
                                        <div class="payroll-warning-desc">Pilih akun kas dengan saldo mencukupi atau lakukan top-up kas sebelum otorisasi:</div>
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <template x-for="err in balanceErrors" :key="err.id">
                                        <div class="payroll-deficit-row">
                                            <div class="payroll-deficit-row-top">
                                                <span class="payroll-deficit-acc-name truncate" x-text="err.name"></span>
                                                <span class="payroll-deficit-pill">
                                                    Kurang <strong class="font-mono tabular-nums ml-0.5" x-text="formatRupiah(err.deficit)"></strong>
                                                </span>
                                            </div>
                                            <div class="payroll-deficit-row-bottom">
                                                <span class="flex items-center gap-1">
                                                    <span>Saldo:</span>
                                                    <strong class="font-mono tabular-nums text-slate-700 dark:text-slate-300" x-text="formatRupiah(err.balance)"></strong>
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <span>Dibutuhkan:</span>
                                                    <strong class="font-mono tabular-nums text-slate-700 dark:text-slate-300" x-text="formatRupiah(err.needed)"></strong>
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Banner Dampak Otorisasi Biru Soft -->
                        <div class="payroll-info-banner-blue">
                            <div class="info-title">
                                <svg style="width:14px;height:14px;display:block;color:#2563eb;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                                <span>Dampak Otorisasi:</span>
                            </div>
                            <ul class="space-y-0.5">
                                <li>Pengeluaran kas tercatat pada masing-masing akun kas (Tunai &amp; Bank Transfer).</li>
                                <li>Cicilan kasbon terpotong otomatis dari saldo pinjaman karyawan.</li>
                                <li>Tabungan karyawan bertambah atau dicairkan sesuai rincian payroll.</li>
                                <li>Slip gaji resmi diterbitkan dan siap dibagikan ke karyawan.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="showApproveModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                        <button type="submit" 
                                class="btn w-full sm:w-auto transition-all" 
                                :style="canApprove ? 'background:#059669; border-color:#047857; color:#ffffff;' : 'background:#64748b; border-color:#475569; color:#ffffff; opacity:0.75; cursor:not-allowed;'"
                                :disabled="!canApprove"
                                style="display:inline-flex; align-items:center; justify-content:center; gap:6px; font-weight:600;">
                            <template x-if="canApprove">
                                <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            </template>
                            <template x-if="!canApprove && balanceErrors.length > 0">
                                <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            </template>
                            <template x-if="!canApprove && balanceErrors.length === 0">
                                <svg style="width:16px;height:16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            </template>
                            <span x-text="!canApprove ? (balanceErrors.length > 0 ? 'Saldo Kas Belum Cukup' : 'Pilih Akun Kas Terlebih Dahulu') : 'Setujui &amp; Bayar Sekarang'">Setujui &amp; Bayar Sekarang</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL 4: HAPUS DRAF -->
    <template x-teleport="body">
        <div x-show="showDeleteModal" x-cloak class="modal-backdrop" @click="showDeleteModal = false">
            <div class="modal-box modal-box-md" style="max-width: 460px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(239,68,68,0.12); color:#dc2626; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i data-lucide="trash-2" style="width:20px; height:20px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title">Hapus Draf Penggajian</div>
                            <div style="font-size:12px; color:var(--color-ink-mute); margin-top:1px;">Membatalkan seluruh lembar draf payroll ini.</div>
                        </div>
                    </div>
                    <button type="button" @click="showDeleteModal = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px; height:18px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/penggajian/delete') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="penggajian_id" value="<?= $run['id'] ?>">

                    <div class="modal-body custom-scrollbar space-y-3">
                        <div style="padding:12px 14px; background:#fef2f2; border:1px solid #fecaca; border-radius:var(--rounded-md); font-size:12.5px; color:#991b1b; line-height:1.5;">
                            Apakah Anda yakin ingin menghapus draf penggajian <strong><?= htmlspecialchars($run['nomor_referensi']) ?></strong>? Seluruh data kehadiran, produksi harian, dan penarikan gaji pada periode ini akan dibuka kembali (unlocked).
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="showDeleteModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                        <button type="submit" class="btn btn-danger btn-solid w-full sm:w-auto" style="display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                            <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                            <span>Ya, Hapus Draf</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL 5: BATALKAN APPROVAL (24 JAM) -->
    <template x-teleport="body">
        <div x-show="showCancelApproveModal" 
             x-cloak 
             class="modal-backdrop" 
             @click="if(!isCancellingApprove) showCancelApproveModal = false"
             @keydown.escape.window="if(!isCancellingApprove) showCancelApproveModal = false">
            <div class="modal-box modal-box-md" style="max-width: 490px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(245,158,11,0.12); color:#d97706; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i data-lucide="rotate-ccw" style="width:20px; height:20px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title">Batalkan Persetujuan Payroll</div>
                            <div style="font-size:12px; color:var(--color-ink-mute); margin-top:1px;">Rollback darurat dalam batas waktu 24 jam</div>
                        </div>
                    </div>
                    <button type="button" 
                            @click="showCancelApproveModal = false" 
                            :disabled="isCancellingApprove"
                            class="modal-close-x" 
                            title="Tutup Modal">
                        <i data-lucide="x" style="width:18px; height:18px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/penggajian/cancel-approve') ?>" 
                      method="POST" 
                      @submit="if(isCancellingApprove) { $event.preventDefault(); return; } isCancellingApprove = true;">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="penggajian_id" value="<?= $run['id'] ?>">

                    <div class="modal-body custom-scrollbar space-y-3.5">
                        <div style="padding:12px 14px; background:#fffbeb; border:1px solid #fde68a; border-radius:var(--rounded-md); font-size:12.5px; color:#92400e; line-height:1.55;">
                            <div class="font-semibold mb-1" style="display:flex; align-items:center; gap:6px;">
                                <i data-lucide="alert-triangle" style="width:15px; height:15px; color:#d97706;"></i>
                                <span>Konfirmasi Rollback Penggajian</span>
                            </div>
                            <span>Anda akan membatalkan persetujuan payroll <strong><?= htmlspecialchars($run['nomor_referensi']) ?></strong>. Sistem akan mengembalikan seluruh transaksi keuangan secara otomatis:</span>
                        </div>

                        <div class="space-y-2 text-xs" style="color:var(--color-ink); line-height:1.5;">
                            <div class="flex items-start gap-2.5 p-2 rounded-lg" style="background:var(--color-surface-soft, rgba(0,0,0,0.02)); border:1px solid var(--color-hairline, #e2e8f0);">
                                <i data-lucide="wallet" style="width:15px; height:15px; color:#059669; margin-top:2px; flex-shrink:0;"></i>
                                <div>
                                    <div class="font-semibold text-emerald-800 dark:text-emerald-300">Pemulihan Saldo Kas</div>
                                    <div style="color:var(--color-ink-mute);">Pengembalian saldo kas operasional (Tunai &amp; Transfer) serta pembatalan mutasi escrow tabungan.</div>
                                </div>
                            </div>

                            <div class="flex items-start gap-2.5 p-2 rounded-lg" style="background:var(--color-surface-soft, rgba(0,0,0,0.02)); border:1px solid var(--color-hairline, #e2e8f0);">
                                <i data-lucide="credit-card" style="width:15px; height:15px; color:#2563eb; margin-top:2px; flex-shrink:0;"></i>
                                <div>
                                    <div class="font-semibold text-blue-800 dark:text-blue-300">Pemulihan Pinjaman Kasbon</div>
                                    <div style="color:var(--color-ink-mute);">Cicilan yang terpotong akan dikembalikan ke sisa pinjaman karyawan dan status pinjaman diaktifkan kembali.</div>
                                </div>
                            </div>

                            <div class="flex items-start gap-2.5 p-2 rounded-lg" style="background:var(--color-surface-soft, rgba(0,0,0,0.02)); border:1px solid var(--color-hairline, #e2e8f0);">
                                <i data-lucide="piggy-bank" style="width:15px; height:15px; color:#7c3aed; margin-top:2px; flex-shrink:0;"></i>
                                <div>
                                    <div class="font-semibold text-purple-800 dark:text-purple-300">Pembalikan Mutasi Tabungan</div>
                                    <div style="color:var(--color-ink-mute);">Setoran simpanan dibatalkan dan penarikan simpanan dikembalikan ke saldo tabungan karyawan.</div>
                                </div>
                            </div>

                            <div class="flex items-start gap-2.5 p-2 rounded-lg" style="background:var(--color-surface-soft, rgba(0,0,0,0.02)); border:1px solid var(--color-hairline, #e2e8f0);">
                                <i data-lucide="file-edit" style="width:15px; height:15px; color:#d97706; margin-top:2px; flex-shrink:0;"></i>
                                <div>
                                    <div class="font-semibold text-amber-800 dark:text-amber-300">Pengembalian ke Status Draf</div>
                                    <div style="color:var(--color-ink-mute);">Lembar penggajian dapat ditinjau atau diedit kembali sebelum dilakukan persetujuan ulang.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" 
                                @click="showCancelApproveModal = false" 
                                :disabled="isCancellingApprove"
                                class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                        <button type="submit" 
                                :disabled="isCancellingApprove"
                                class="btn w-full sm:w-auto" 
                                style="background:#d97706; color:#ffffff; border:1px solid #b45309; display:inline-flex; align-items:center; justify-content:center; gap:6px; font-weight:700;">
                            <template x-if="!isCancellingApprove">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="rotate-ccw" style="width:16px; height:16px;"></i>
                                    <span>Ya, Batalkan Approval</span>
                                </span>
                            </template>
                            <template x-if="isCancellingApprove">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="animate-spin" style="width:14px; height:14px; border:2px solid #fff; border-top-color:transparent; border-radius:50%; display:inline-block;"></span>
                                    <span>Memproses Rollback...</span>
                                </span>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>

<script>
function payrollPreviewApp() {
    return {
        searchQuery: '',
        statusFilter: 'all', // 'all' | 'borongan' | 'bulanan'
        showApproveModal: false,
        showEditModal: false,
        showExcludeModal: false,
        showDeleteModal: false,
        showCancelApproveModal: false,
        isCancellingApprove: false,
        <?php
        $defaultKasTunaiId = '';
        $defaultKasTransferId = '';
        $defaultKasTabunganId = '';
        if (!empty($akunKasList)) {
            // Default Kas Tunai
            foreach ($akunKasList as $ak) {
                if ($ak['tipe_akun'] === 'kas_tunai' && (float)$ak['saldo_saat_ini'] >= (float)$totalGajiTunai) {
                    $defaultKasTunaiId = (string)$ak['id'];
                    break;
                }
            }
            if ($defaultKasTunaiId === '') {
                foreach ($akunKasList as $ak) {
                    if ($ak['tipe_akun'] === 'kas_tunai') {
                        $defaultKasTunaiId = (string)$ak['id'];
                        break;
                    }
                }
            }
            if ($defaultKasTunaiId === '' && isset($akunKasList[0]['id'])) {
                $defaultKasTunaiId = (string)$akunKasList[0]['id'];
            }

            // Default Kas Transfer Bank
            foreach ($akunKasList as $ak) {
                if ($ak['tipe_akun'] === 'bank' && (float)$ak['saldo_saat_ini'] >= (float)$totalGajiTransfer) {
                    $defaultKasTransferId = (string)$ak['id'];
                    break;
                }
            }
            if ($defaultKasTransferId === '') {
                foreach ($akunKasList as $ak) {
                    if ($ak['tipe_akun'] === 'bank') {
                        $defaultKasTransferId = (string)$ak['id'];
                        break;
                    }
                }
            }
            if ($defaultKasTransferId === '' && isset($akunKasList[0]['id'])) {
                $defaultKasTransferId = (string)$akunKasList[0]['id'];
            }

            // Default Tabungan Sumber
            $defaultKasTabunganId = $defaultKasTransferId ?: $defaultKasTunaiId;
        }
        ?>
        selectedKasTunaiId: '<?= $defaultKasTunaiId ?>',
        selectedKasTransferId: '<?= $defaultKasTransferId ?>',
        selectedKasTabunganId: '<?= $defaultKasTabunganId ?>',
        selectedKasId: '<?= $defaultKasTunaiId ?: $defaultKasTransferId ?>',
        totalPayrollNet: <?= (float)$totalGajiBersih ?>,
        totalGajiTunai: <?= (float)$totalGajiTunai ?>,
        countTunai: <?= (int)$countTunai ?>,
        totalGajiTransfer: <?= (float)$totalGajiTransfer ?>,
        countTransfer: <?= (int)$countTransfer ?>,
        potonganTabunganTunai: <?= (float)$potonganTabunganTunai ?>,
        potonganTabunganTransfer: <?= (float)$potonganTabunganTransfer ?>,
        totalPotonganTabunganAll: <?= (float)$totalPotonganTabunganAll ?>,
        totalPenarikanTabunganAll: <?= (float)$totalPenarikanTabunganAll ?>,
        periodLabels: <?= json_encode($periodLabels, JSON_UNESCAPED_UNICODE) ?>,
        get totalKebutuhanKas() {
            return this.totalPayrollNet + this.totalPotonganTabunganAll;
        },
        get accountNeeds() {
            const needs = {};
            const tunaiId = String(this.selectedKasTunaiId || '');
            const transferId = String(this.selectedKasTransferId || '');
            const tabunganId = String(this.selectedKasTabunganId || transferId || tunaiId || '');

            if (this.totalGajiTunai > 0 && tunaiId) {
                needs[tunaiId] = (needs[tunaiId] || 0) + this.totalGajiTunai;
            }
            if (this.totalGajiTransfer > 0 && transferId) {
                needs[transferId] = (needs[transferId] || 0) + this.totalGajiTransfer;
            }
            if (this.totalPotonganTabunganAll > 0 && tabunganId) {
                needs[tabunganId] = (needs[tabunganId] || 0) + this.totalPotonganTabunganAll;
            }
            return needs;
        },
        get balanceErrors() {
            const errs = [];
            const needs = this.accountNeeds;
            for (const [accId, needed] of Object.entries(needs)) {
                const bal = Number(this.kasBalances[accId] !== undefined ? this.kasBalances[accId] : 0);
                if (bal < needed) {
                    const accObj = this.cashAccounts.find(a => String(a.id) === String(accId));
                    const name = accObj ? accObj.nama_akun : ('Akun #' + accId);
                    errs.push({
                        id: accId,
                        name: name,
                        needed: needed,
                        balance: bal,
                        deficit: needed - bal
                    });
                }
            }
            return errs;
        },
        get canApprove() {
            if (this.totalGajiTunai > 0 && !this.selectedKasTunaiId) return false;
            if (this.totalGajiTransfer > 0 && !this.selectedKasTransferId) return false;
            if (this.totalPotonganTabunganAll > 0 && !this.selectedKasTabunganId) return false;
            return this.balanceErrors.length === 0;
        },
        cashAccounts: <?= json_encode(array_map(function($a) {
            return [
                'id' => (string)$a['id'],
                'nama_akun' => (string)$a['nama_akun'],
                'tipe_akun' => (string)$a['tipe_akun'],
                'saldo' => (float)$a['saldo_saat_ini'],
                'is_default_pos' => (bool)($a['is_default_pos'] ?? false),
            ];
        }, $akunKasList ?? []), JSON_UNESCAPED_UNICODE) ?>,
        kasBalances: {
            <?php foreach ($akunKasList as $ak): ?>
            "<?= $ak['id'] ?>": <?= (float)$ak['saldo_saat_ini'] ?>,
            <?php endforeach; ?>
        },
        openKasDropdown: null,
        searchKasTunai: '',
        searchKasTransfer: '',
        searchKasTabungan: '',
        getAccount(id) {
            if (!id) return null;
            return this.cashAccounts.find(a => String(a.id) === String(id)) || null;
        },
        getFilteredAccounts(query) {
            if (!query || !query.trim()) return this.cashAccounts;
            const q = query.toLowerCase().trim();
            return this.cashAccounts.filter(a => {
                const name = (a.nama_akun || '').toLowerCase();
                const type = (a.tipe_akun || '').toLowerCase();
                return name.includes(q) || type.includes(q);
            });
        },
        selectedItem: null,
        runRef: '<?= htmlspecialchars($run['nomor_referensi'], ENT_QUOTES) ?>',
        isDownloadingPdf: false,

        async downloadPdf(url, title, subtitle, defaultFilename) {
            if (this.isDownloadingPdf) return;
            this.isDownloadingPdf = true;

            const t = title || 'Menyiapkan Dokumen PDF...';
            const s = subtitle || 'Mengompilasi data dan memproses berkas...';
            const df = defaultFilename || ('Dokumen_' + (this.runRef || 'Payroll') + '.pdf');

            try {
                if (typeof window.downloadFileWithLoading === 'function') {
                    await window.downloadFileWithLoading(url, {
                        title: t,
                        subtitle: s,
                        defaultFilename: df
                    });
                } else {
                    if (window.AppAction && typeof window.AppAction.show === 'function') {
                        window.AppAction.show(t, s);
                    }

                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    const contentType = (response.headers.get('content-type') || '').toLowerCase();

                    if (!response.ok || (contentType.indexOf('application/json') !== -1 && contentType.indexOf('pdf') === -1)) {
                        let errorMsg = 'Terjadi kesalahan saat memproses berkas PDF.';
                        try {
                            const errData = await response.json();
                            errorMsg = errData.message || errorMsg;
                        } catch (e) {
                            const txt = await response.text();
                            if (txt && txt.length < 300) errorMsg = txt;
                        }
                        if (window.AppAction && typeof window.AppAction.error === 'function') {
                            await window.AppAction.error('Gagal Mengunduh!', errorMsg, 3200);
                        } else if (window.toast) {
                            window.toast.error(errorMsg);
                        }
                        return;
                    }

                    let filename = df;
                    const disposition = response.headers.get('content-disposition');
                    if (disposition) {
                        const mUtf8 = /filename\*=UTF-8''([^;]+)/i.exec(disposition);
                        if (mUtf8 && mUtf8[1]) {
                            filename = decodeURIComponent(mUtf8[1]).trim();
                        } else {
                            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/i.exec(disposition);
                            if (matches != null && matches[1]) {
                                filename = matches[1].replace(/['"]/g, '').trim();
                            }
                        }
                    }

                    const blob = await response.blob();
                    if (blob.size === 0) {
                        throw new Error('Berkas yang diterima kosong.');
                    }

                    const blobUrl = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = blobUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();

                    setTimeout(() => {
                        if (a.parentNode) a.parentNode.removeChild(a);
                        window.URL.revokeObjectURL(blobUrl);
                    }, 1000);

                    if (window.AppAction && typeof window.AppAction.success === 'function') {
                        await window.AppAction.success('Berhasil Diunduh! ✨', filename, 1800);
                    } else if (window.toast) {
                        window.toast.success('Berkas ' + filename + ' berhasil diunduh.');
                    }
                }
            } catch (err) {
                console.error('[PayrollPreview] Download PDF error:', err);
                const msg = err.message || 'Koneksi terputus saat mengunduh berkas.';
                if (window.AppAction && typeof window.AppAction.error === 'function') {
                    await window.AppAction.error('Gagal Mengunduh Dokumen!', msg, 3200);
                } else if (window.toast) {
                    window.toast.error(msg);
                }
            } finally {
                this.isDownloadingPdf = false;
            }
        },

        // Data JSON Lengkap untuk Reaktivitas Alpine 100%
        itemsList: <?= json_encode(array_map(function($item) {
            $details = json_decode((string)$item['rincian_json'], true) ?: [];
            $isExc = (bool)$item['is_excluded'];
            $grp = ($item['tipe_penggajian'] === 'bulanan') ? 'bulanan' : 'borongan';

            $gapok = (float)$item['gaji_pokok'];
            $hadir = (float)$item['total_uang_kehadiran'];
            $borongan = (float)$item['total_upah_borongan'];
            $tBulanan = (float)$item['tunjangan_bulanan'];
            $lembur = (float)$item['total_upah_lembur'];
            $komisi = (float)$item['total_komisi_sales'];

            $kasbon = (float)$item['total_potongan_kasbon'];
            $penarikanGaji = (float)$item['total_penarikan_gaji'];
            $setorTab = (float)$item['total_potongan_tabungan'];

            $tLain = (float)$item['tunjangan_lain'];
            $tarikTab = (float)$item['penarikan_tabungan'];
            $pembulatan = (float)$item['nominal_pembulatan'];
            $potLain = (float)$item['potongan_lain'];

            $pendapatanAwal = $gapok + $hadir + $borongan + $tBulanan + $lembur + $komisi;
            $potonganTotal  = $kasbon + $penarikanGaji + $setorTab;
            $penyesuaian    = $tLain + $tarikTab + $pembulatan - $potLain;
            $gajiBersih     = (float)$item['gaji_bersih_diterima'];

            return [
                'id' => (string)$item['id'],
                'karyawan_id' => (string)$item['karyawan_id'],
                'nama_karyawan' => (string)$item['nama_karyawan'],
                'posisi' => (string)($item['posisi'] ?? '-'),
                'tipe_penggajian' => (string)$item['tipe_penggajian'],
                'group' => $grp,
                'is_excluded' => $isExc,
                'catatan_pengecualian' => (string)($item['catatan_pengecualian'] ?? ''),
                'kasbon_adjusted' => (bool)($details['kasbon_adjusted_down'] ?? false),
                'hari_hadir' => (int)$item['hari_hadir'],
                // Rincian Komponen Mentah
                'gaji_pokok' => (int)round($gapok),
                'uang_hadir' => (int)round($hadir),
                'borongan' => (int)round($borongan),
                'tunjangan_bulanan' => (int)round($tBulanan),
                'lembur' => (int)round($lembur),
                'komisi' => (int)round($komisi),
                'kasbon' => (int)round($kasbon),
                'penarikan_gaji' => (int)round($penarikanGaji),
                'setor_tabungan' => (int)round($setorTab),
                'tunjangan_lain' => (int)round($tLain),
                'tarik_tabungan' => (int)round($tarikTab),
                'pembulatan' => (int)round($pembulatan),
                'potongan_lain' => (int)round($potLain),
                'catatan_tunjangan_lain' => (string)($item['catatan_tunjangan_lain'] ?? ''),
                'catatan_potongan_lain' => (string)($item['catatan_potongan_lain'] ?? ''),
                // Total 4 Pilar
                'pendapatan_awal' => (int)round($pendapatanAwal),
                'potongan_total' => (int)round($potonganTotal),
                'penyesuaian' => (int)round($penyesuaian),
                'gaji_bersih' => (int)round($gajiBersih),
                // Batas Validasi Modal
                'max_kasbon_aktif' => (int)round((float)($item['max_kasbon_aktif'] ?? 0)),
                'saldo_tabungan_saat_ini' => (int)round((float)($item['saldo_tabungan_saat_ini'] ?? 0)),
                // Metode Pembayaran & Rekening Bank
                'metode_pembayaran' => (string)($item['metode_pembayaran'] ?? 'tunai'),
                'bank_nama' => (string)($item['bank_nama'] ?? ''),
                'bank_nomor_rekening' => (string)($item['bank_nomor_rekening'] ?? ''),
                'bank_atas_nama' => (string)($item['bank_atas_nama'] ?? ''),
                'has_master_bank' => !empty($item['has_master_bank']),
                'master_bank_nama' => (string)($item['master_bank_nama'] ?? ''),
                'master_bank_nomor_rekening' => (string)($item['master_bank_nomor_rekening'] ?? ''),
                'master_bank_atas_nama' => (string)($item['master_bank_atas_nama'] ?? ''),
            ];
        }, $items), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,

        editForm: {
            item_id: '',
            karyawan_nama: '',
            pendapatan_awal: 0,
            breakdown: [],
            penarikan_gaji: 0,
            max_kasbon: 0,
            saldo_tabungan: 0,
            total_potongan_kasbon: 0,
            tunjangan_lain: 0,
            catatan_tunjangan_lain: '',
            potongan_lain: 0,
            catatan_potongan_lain: '',
            total_potongan_tabungan: 0,
            penarikan_tabungan: 0,
            nominal_pembulatan: 0,
            metode_pembayaran: 'tunai',
            bank_nama: '',
            bank_nomor_rekening: '',
            bank_atas_nama: '',
            has_master_bank: false,
            master_bank_nama: '',
            master_bank_nomor_rekening: '',
            master_bank_atas_nama: '',
            show_custom_bank: false,
        },

        excludeForm: {
            item_id: '',
            nama: '',
            alasan: ''
        },

        // Helper Format Resmi Universal (Sesuai AGENTS.md & erp-ui-design)
        formatRupiah(val) {
            if (typeof window.formatRupiah === 'function') {
                return window.formatRupiah(val);
            }
            var n = Math.round(Number(val) || 0);
            var isNeg = n < 0;
            var s = String(Math.abs(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return (isNeg ? '-Rp ' : 'Rp ') + s;
        },

        formatRupiahNumber(val) {
            if (typeof window.formatRupiahNumber === 'function') {
                return window.formatRupiahNumber(val);
            }
            var n = Math.round(Number(val) || 0);
            var isNeg = n < 0;
            var s = String(Math.abs(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return isNeg ? ('-' + s) : s;
        },

        // Helper Visual Emblem Logo Bank (Brand Colors & Vector Badges)
        getBankBadge(name) {
            const b = String(name || '').toUpperCase().trim();
            if (b.includes('BCA')) {
                return { bg: '#003893', color: '#ffffff', label: 'BCA' };
            }
            if (b.includes('BSI') || b.includes('SYARIAH')) {
                return { bg: '#00a39d', color: '#ffffff', label: 'BSI' };
            }
            if (b.includes('BRI')) {
                return { bg: '#00529c', color: '#ffffff', label: 'BRI' };
            }
            if (b.includes('MANDIRI')) {
                return { bg: '#003d79', color: '#fbbf24', label: 'MANDIRI' };
            }
            if (b.includes('BNI')) {
                return { bg: '#005e6a', color: '#ffffff', label: 'BNI' };
            }
            if (b.includes('JAGO')) {
                return { bg: '#f59e0b', color: '#ffffff', label: 'JAGO' };
            }
            if (b.includes('SEABANK') || b.includes('SEA')) {
                return { bg: '#f25c05', color: '#ffffff', label: 'SEABANK' };
            }
            if (b.includes('CIMB')) {
                return { bg: '#8b0000', color: '#ffffff', label: 'CIMB' };
            }
            if (b.includes('DANAMON')) {
                return { bg: '#0b2c6b', color: '#fbbf24', label: 'DANAMON' };
            }
            if (b.includes('PERMATA')) {
                return { bg: '#047857', color: '#ffffff', label: 'PERMATA' };
            }
            const clean = b.replace(/BANK/g, '').trim();
            const shortName = clean ? clean.substring(0, 4) : 'BANK';
            return { bg: '#1e40af', color: '#ffffff', label: shortName || 'BANK' };
        },

        // Input Currency Formatter Realtime
        setMoney(field, event, allowNegative = false) {
            let raw = (event.target.value || '').toString().trim();
            let isNeg = allowNegative && raw.startsWith('-');
            let digits = raw.replace(/[^0-9]/g, '');
            let num = parseInt(digits, 10) || 0;
            if (isNeg) num = -num;

            this.editForm[field] = Number(num);

            // Format kembali tampilan input
            let formatted = this.formatRupiahNumber(Math.abs(num));
            event.target.value = isNeg ? ('-' + formatted) : formatted;
        },

        // Kalkulasi Reaktif Net Gaji Setelah Penyesuaian (Layer 1 Guard)
        get editNet() {
            const f = this.editForm;
            const pendapatan = Number(f.pendapatan_awal) || 0;
            const kasbon = Number(f.total_potongan_kasbon) || 0;
            const penarikanGaji = Number(f.penarikan_gaji) || 0;
            const tunjanganLain = Number(f.tunjangan_lain) || 0;
            const potonganLain = Number(f.potongan_lain) || 0;
            const setorTab = Number(f.total_potongan_tabungan) || 0;
            const tarikTab = Number(f.penarikan_tabungan) || 0;
            const pembulatan = Number(f.nominal_pembulatan) || 0;

            return pendapatan - kasbon - penarikanGaji + tunjanganLain - potonganLain - setorTab + tarikTab + pembulatan;
        },

        get errKasbon() {
            const pot = Number(this.editForm.total_potongan_kasbon) || 0;
            const max = Number(this.editForm.max_kasbon) || 0;
            return pot > max;
        },

        get errTarikTab() {
            const tarik = Number(this.editForm.penarikan_tabungan) || 0;
            const saldo = Number(this.editForm.saldo_tabungan) || 0;
            return tarik > saldo;
        },

        get editInvalid() {
            return this.errKasbon || this.errTarikTab || this.editNet < 0;
        },

        // Filtered Lists
        get includedItems() {
            return this.itemsList.filter(i => !i.is_excluded);
        },

        get excludedItems() {
            return this.itemsList.filter(i => i.is_excluded);
        },

        get boronganCount() {
            return this.includedItems.filter(i => i.group === 'borongan').length;
        },

        get bulananCount() {
            return this.includedItems.filter(i => i.group === 'bulanan').length;
        },

        get tunaiCount() {
            return this.includedItems.filter(i => (i.metode_pembayaran || 'tunai') === 'tunai').length;
        },

        get transferCount() {
            return this.includedItems.filter(i => i.metode_pembayaran === 'transfer').length;
        },

        // Table items berdasarkan tab & search
        get bulananTableItems() {
            if (this.statusFilter === 'borongan') return [];
            const q = (this.searchQuery || '').toLowerCase().trim();
            return this.includedItems.filter(item => {
                if (item.group !== 'bulanan') return false;
                if (this.statusFilter === 'tunai' && item.metode_pembayaran === 'transfer') return false;
                if (this.statusFilter === 'transfer' && item.metode_pembayaran !== 'transfer') return false;
                if (q) {
                    const name = (item.nama_karyawan || '').toLowerCase();
                    const pos = (item.posisi || '').toLowerCase();
                    if (!name.includes(q) && !pos.includes(q)) return false;
                }
                return true;
            });
        },

        get boronganTableItems() {
            if (this.statusFilter === 'bulanan') return [];
            const q = (this.searchQuery || '').toLowerCase().trim();
            return this.includedItems.filter(item => {
                if (item.group !== 'borongan') return false;
                if (this.statusFilter === 'tunai' && item.metode_pembayaran === 'transfer') return false;
                if (this.statusFilter === 'transfer' && item.metode_pembayaran !== 'transfer') return false;
                if (q) {
                    const name = (item.nama_karyawan || '').toLowerCase();
                    const pos = (item.posisi || '').toLowerCase();
                    if (!name.includes(q) && !pos.includes(q)) return false;
                }
                return true;
            });
        },

        get tableItems() {
            return [...this.bulananTableItems, ...this.boronganTableItems];
        },

        init() {
            this.safeRefreshIcons();

            this.$watch('statusFilter', () => this.safeRefreshIcons());
            this.$watch('searchQuery', () => this.safeRefreshIcons());
            this.$watch('showApproveModal', val => { if (val) this.safeRefreshIcons(); });
            this.$watch('showEditModal', val => { if (val) this.safeRefreshIcons(); });
            this.$watch('showExcludeModal', val => { if (val) this.safeRefreshIcons(); });
            this.$watch('showDeleteModal', val => { if (val) this.safeRefreshIcons(); });
            this.$watch('showCancelApproveModal', val => { if (val) this.safeRefreshIcons(); });
        },

        safeRefreshIcons() {
            this.$nextTick(() => {
                if (typeof window.refreshIcons === 'function') {
                    window.refreshIcons();
                } else if (window.lucide && typeof lucide.createIcons === 'function') {
                    lucide.createIcons();
                }
            });
        },

        selectMetode(metode) {
            this.editForm.metode_pembayaran = metode;
            if (metode === 'transfer' && this.editForm.has_master_bank) {
                if (!this.editForm.bank_nomor_rekening) {
                    this.editForm.bank_nama = this.editForm.master_bank_nama;
                    this.editForm.bank_nomor_rekening = this.editForm.master_bank_nomor_rekening;
                    this.editForm.bank_atas_nama = this.editForm.master_bank_atas_nama;
                }
            }
            this.safeRefreshIcons();
        },

        openEdit(item) {
            this.selectedItem = item;

            // Rangkum Breakdown Pendapatan untuk Panel Kiri Modal
            const bd = [];
            if (Number(item.gaji_pokok) > 0) bd.push({ label: 'Gaji Pokok', val: Number(item.gaji_pokok) });
            if (Number(item.uang_hadir) > 0) bd.push({ label: 'Uang Kehadiran', val: Number(item.uang_hadir) });
            if (Number(item.borongan) > 0) bd.push({ label: 'Upah Borongan', val: Number(item.borongan) });
            if (Number(item.tunjangan_bulanan) > 0) bd.push({ label: 'Tunjangan Bulanan', val: Number(item.tunjangan_bulanan) });
            if (Number(item.lembur) > 0) bd.push({ label: 'Upah Lembur', val: Number(item.lembur) });
            if (Number(item.komisi) > 0) bd.push({ label: 'Komisi Sales', val: Number(item.komisi) });

            const hasMaster = !!item.has_master_bank;
            const currentMetode = String(item.metode_pembayaran || (hasMaster ? 'transfer' : 'tunai'));
            const bNama = String(item.bank_nama || item.master_bank_nama || '');
            const bRek = String(item.bank_nomor_rekening || item.master_bank_nomor_rekening || '');
            const bAn = String(item.bank_atas_nama || item.master_bank_atas_nama || item.nama_karyawan || '');
            const isCustom = hasMaster && (
                (bRek && bRek !== String(item.master_bank_nomor_rekening || '')) ||
                (bNama && bNama !== String(item.master_bank_nama || ''))
            );

            this.editForm = {
                item_id: String(item.id || ''),
                karyawan_nama: String(item.nama_karyawan || ''),
                pendapatan_awal: Number(item.pendapatan_awal) || 0,
                breakdown: bd,
                penarikan_gaji: Number(item.penarikan_gaji) || 0,
                max_kasbon: Number(item.max_kasbon_aktif) || 0,
                saldo_tabungan: Number(item.saldo_tabungan_saat_ini) || 0,
                total_potongan_kasbon: Number(item.kasbon) || 0,
                tunjangan_lain: Number(item.tunjangan_lain) || 0,
                catatan_tunjangan_lain: String(item.catatan_tunjangan_lain || ''),
                potongan_lain: Number(item.potongan_lain) || 0,
                catatan_potongan_lain: String(item.catatan_potongan_lain || ''),
                total_potongan_tabungan: Number(item.setor_tabungan) || 0,
                penarikan_tabungan: Number(item.tarik_tabungan) || 0,
                nominal_pembulatan: Number(item.pembulatan) || 0,
                metode_pembayaran: currentMetode,
                bank_nama: bNama,
                bank_nomor_rekening: bRek,
                bank_atas_nama: bAn,
                has_master_bank: hasMaster,
                master_bank_nama: String(item.master_bank_nama || ''),
                master_bank_nomor_rekening: String(item.master_bank_nomor_rekening || ''),
                master_bank_atas_nama: String(item.master_bank_atas_nama || item.nama_karyawan || ''),
                show_custom_bank: isCustom,
            };

            this.showEditModal = true;
            this.safeRefreshIcons();
        },

        openExclude(item) {
            this.excludeForm = {
                item_id: item.id,
                nama: item.nama_karyawan,
                alasan: ''
            };
            this.showExcludeModal = true;
            this.safeRefreshIcons();
        },

        // Guard Submit Form Modal Edit (Layer 2 Guard)
        async guardEditSubmit(event) {
            if (this.editInvalid) {
                event.preventDefault();
                let errMsg = 'Periksa kembali input Anda.';
                if (this.errKasbon) {
                    errMsg = 'Potongan kasbon melebihi sisa pinjaman kasbon aktif (' + this.formatRupiah(this.editForm.max_kasbon) + ').';
                } else if (this.errTarikTab) {
                    errMsg = 'Penarikan tabungan melebihi saldo tabungan karyawan (' + this.formatRupiah(this.editForm.saldo_tabungan) + ').';
                } else if (this.editNet < 0) {
                    errMsg = 'Total potongan melebihi pendapatan. Gaji bersih tidak boleh bernilai negatif!';
                }

                if (typeof window.AppAlert === 'function') {
                    await window.AppAlert({
                        title: 'Data Belum Valid',
                        message: errMsg,
                        type: 'warning'
                    });
                } else if (window.toast) {
                    window.toast.error(errMsg);
                }
                return false;
            }
        },

        // Konfirmasi Batal Kecualikan via AppConfirm Universal
        async confirmUnexclude(event, item) {
            event.preventDefault();
            const form = event.target;
            let ok = false;
            if (typeof window.AppConfirm === 'function') {
                ok = await window.AppConfirm({
                    title: 'Sertakan Kembali ke Payroll',
                    message: 'Batalkan pengecualian dan masukkan kembali "' + item.nama_karyawan + '" ke dalam daftar payroll?',
                    submessage: 'Data absensi dan produksinya akan kembali dikunci oleh payroll ini.',
                    type: 'primary',
                    confirmText: 'Ya, Sertakan',
                    cancelText: 'Batal'
                });
            } else {
                ok = true;
            }
            if (ok) {
                form.submit();
            }
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

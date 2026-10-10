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
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    user-select: none;
}
.tab-pill-btn:hover {
    color: var(--color-ink);
    background: rgba(0, 0, 0, 0.04);
}
.dark .tab-pill-btn:hover {
    color: var(--color-ink);
    background: rgba(255, 255, 255, 0.06);
}
.tab-pill-btn.is-active-primary {
    background: var(--color-canvas, #ffffff) !important;
    color: var(--color-ink, #0f172a) !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    font-weight: 700;
}
.dark .tab-pill-btn.is-active-primary {
    background: #334155 !important;
    color: #f8fafc !important;
}
.tab-pill-btn.is-active-amber {
    background: #fef3c7 !important;
    color: #b45309 !important;
    border: 1px solid #fde68a;
    font-weight: 700;
}
.dark .tab-pill-btn.is-active-amber {
    background: rgba(245, 158, 11, 0.2) !important;
    color: #fbbf24 !important;
    border-color: rgba(245, 158, 11, 0.35);
}
.tab-pill-btn.is-active-sky {
    background: #eff6ff !important;
    color: #1d4ed8 !important;
    border: 1px solid #bfdbfe;
    font-weight: 700;
}
.dark .tab-pill-btn.is-active-sky {
    background: rgba(59, 130, 246, 0.2) !important;
    color: #60a5fa !important;
    border-color: rgba(59, 130, 246, 0.35);
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

/* Warning / Info Banner Minimalis */
.payroll-warning-banner {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    border-radius: var(--rounded-md, 8px);
    background-color: #fffbeb !important;
    border: 1px solid #fde68a !important;
    color: #92400e !important;
    font-size: 11.5px;
    line-height: 1.5;
}
.payroll-warning-banner .banner-icon-box {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    background-color: #fef3c7 !important;
    color: #b45309 !important;
    border: 1px solid #fde68a !important;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}
.payroll-warning-banner strong {
    color: #78350f !important;
    font-weight: 700;
}
.dark .payroll-warning-banner {
    background-color: rgba(245, 158, 11, 0.15) !important;
    border-color: rgba(245, 158, 11, 0.4) !important;
    color: #fef3c7 !important;
}
.dark .payroll-warning-banner .banner-icon-box {
    background-color: rgba(245, 158, 11, 0.25) !important;
    border-color: rgba(245, 158, 11, 0.45) !important;
    color: #fcd34d !important;
}
.dark .payroll-warning-banner strong {
    color: #fbbf24 !important;
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
                <a href="<?= Router::url('/penggajian/slip-batch?run_id=' . $run['id']) ?>" target="_blank" class="btn btn-secondary justify-center col-span-1" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                    <i data-lucide="file-text" style="width:16px; height:16px; color:#be123c;"></i>
                    <span>Slip Batch</span>
                </a>

                <a href="<?= Router::url('/penggajian/rekap-pdf?run_id=' . $run['id']) ?>" target="_blank" class="btn btn-secondary justify-center col-span-1" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                    <i data-lucide="printer" style="width:16px; height:16px; color:#0284c7;"></i>
                    <span>Rekap PDF</span>
                </a>

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
                <div style="font-size:10px; color:var(--color-ink-mute); margin-top:1px;" class="truncate hidden sm:block">Beban kas keluar</div>
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
            
            <!-- Filter Tabs (Semua / Borongan / Bulanan) -->
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
                <div class="hidden md:inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full border text-[11px] text-slate-500 dark:text-slate-400" style="background:var(--color-canvas); border-color:var(--color-hairline);">
                    <i data-lucide="move-horizontal" style="width:12px; height:12px; color:#2563eb;"></i>
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
                    <a :href="'<?= Router::url('/penggajian/slip?run_id=' . $run['id'] . '&rincian_id=') ?>' + item.id" target="_blank"
                       class="btn btn-secondary btn-sm" style="font-size:11px; padding:4px 9px; gap:4px; display:inline-flex; align-items:center;" title="Cetak Slip Gaji">
                        <i data-lucide="file-text" style="width:13px; height:13px; color:#be123c;"></i>
                        <span>Slip</span>
                    </a>
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
                    <a :href="'<?= Router::url('/penggajian/slip?run_id=' . $run['id'] . '&rincian_id=') ?>' + item.id" target="_blank"
                       class="btn btn-secondary flex-1 justify-center py-2 text-xs" style="height:35px;" title="Lihat Slip Gaji">
                        <i data-lucide="file-text" style="width:13px; height:13px; color:#be123c;"></i>
                        <span>Lihat Slip</span>
                    </a>
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
            <div class="modal-box modal-box-md" style="max-width: 540px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(16,185,129,0.12); color:#10b981; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i data-lucide="check-circle-2" style="width:20px; height:20px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title">Otorisasi &amp; Pembayaran Payroll</div>
                            <div style="font-size:12px; color:var(--color-ink-mute); margin-top:1px;">Konfirmasi pengeluaran kas dan pemotongan saldo kas otomatis.</div>
                        </div>
                    </div>
                    <button type="button" @click="showApproveModal = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px; height:18px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/penggajian/approve') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="penggajian_id" value="<?= $run['id'] ?>">

                    <div class="modal-body custom-scrollbar space-y-4">
                        <!-- Highlight & Breakdown Card -->
                        <div class="p-3 rounded-lg border border-slate-200/90 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/40 space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-medium text-slate-600 dark:text-slate-400">Total Gaji Bersih (Net Dibayarkan):</span>
                                <span class="font-mono font-bold text-sm text-slate-900 dark:text-slate-100"><?= Format::rupiah($totalGajiBersih) ?></span>
                            </div>
                            <?php if ($totalPotonganTabunganAll > 0): ?>
                            <div class="flex justify-between items-center text-xs border-t border-slate-200/80 dark:border-slate-800 pt-1.5">
                                <span class="flex items-center gap-1 text-purple-700 dark:text-purple-300">
                                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
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
                                    <i data-lucide="arrow-left" class="w-3 h-3"></i>
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

                        <!-- Cash Account Selector Cards -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-800 dark:text-slate-100 flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="wallet" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Pilih Sumber Akun Kas Operasional / Payroll</span>
                                    <span class="text-rose-500">*</span>
                                </span>
                                <span class="text-[11px] text-slate-400 font-normal">Wajib bukan akun escrow</span>
                            </label>

                            <input type="hidden" name="akun_kas_id" :value="selectedKasId" required>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-44 overflow-y-auto custom-scrollbar p-0.5">
                                <template x-for="acc in cashAccounts" :key="acc.id">
                                    <div @click="selectedKasId = acc.id"
                                         :class="{
                                             'border-emerald-600 dark:border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/40 dark:bg-emerald-950/20': selectedKasId === acc.id,
                                             'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 hover:border-slate-300 dark:hover:border-slate-600': selectedKasId !== acc.id,
                                             'opacity-60 cursor-not-allowed border-dashed': acc.saldo < totalKebutuhanKas
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
                                                    Saldo: <span :class="acc.saldo < totalKebutuhanKas ? 'text-rose-600 font-bold' : 'text-slate-700 dark:text-slate-200'" x-text="formatRupiah(acc.saldo)"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ml-1.5 flex-shrink-0">
                                            <div class="w-3.5 h-3.5 rounded-full border flex items-center justify-center"
                                                 :class="selectedKasId === acc.id ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-300 dark:border-slate-600'">
                                                <svg x-show="selectedKasId === acc.id" class="w-2 h-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Info Banner Kuning / Minimalis & Clean -->
                            <div x-show="selectedKasId && kasBalances[selectedKasId] !== undefined && kasBalances[selectedKasId] < totalKebutuhanKas"
                                 x-cloak
                                 class="payroll-warning-banner">
                                <div class="banner-icon-box">
                                    <i data-lucide="alert-triangle" style="width:13px; height:13px;"></i>
                                </div>
                                <div style="min-width:0; flex:1;">
                                    <span style="font-weight:700;">Saldo akun kas belum mencukupi</span> untuk pembayaran payroll &amp; setoran tabungan.
                                    <div style="margin-top:2px; font-family:var(--font-mono); font-size:11px; color:#b45309;">
                                        Kebutuhan kas: <strong><span x-text="formatRupiah(totalKebutuhanKas)"></span></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Banner Dampak Otorisasi Biru Soft -->
                        <div class="payroll-info-banner-blue">
                            <div class="info-title">
                                <i data-lucide="shield-check" style="width:14px; height:14px; color:#2563eb;"></i>
                                <span>Dampak Otorisasi:</span>
                            </div>
                            <ul class="space-y-0.5">
                                <li>Cicilan kasbon terpotong otomatis dari saldo pinjaman karyawan.</li>
                                <li>Tabungan karyawan bertambah atau dicairkan sesuai rincian payroll.</li>
                                <li>Slip gaji resmi diterbitkan dan siap dibagikan ke karyawan.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="showApproveModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                        <button type="submit" 
                                class="btn btn-primary w-full sm:w-auto transition-all" 
                                style="background:#059669; border-color:#047857; display:inline-flex; align-items:center; justify-content:center; gap:6px;"
                                :disabled="!selectedKasId || (kasBalances[selectedKasId] !== undefined && kasBalances[selectedKasId] < totalKebutuhanKas)"
                                :class="{'opacity-50 cursor-not-allowed': !selectedKasId || (kasBalances[selectedKasId] !== undefined && kasBalances[selectedKasId] < totalKebutuhanKas)}">
                            <i data-lucide="check-circle" style="width:16px; height:16px;"></i>
                            <span x-text="(!selectedKasId || (kasBalances[selectedKasId] !== undefined && kasBalances[selectedKasId] < totalKebutuhanKas)) ? 'Saldo Tidak Cukup' : 'Setujui &amp; Bayar Sekarang'">Setujui &amp; Bayar Sekarang</span>
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
        <div x-show="showCancelApproveModal" x-cloak class="modal-backdrop" @click="showCancelApproveModal = false">
            <div class="modal-box modal-box-md" style="max-width: 480px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(245,158,11,0.12); color:#d97706; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i data-lucide="rotate-ccw" style="width:20px; height:20px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title">Batalkan Persetujuan Payroll</div>
                            <div style="font-size:12px; color:var(--color-ink-mute); margin-top:1px;">Fitur darurat rollback dalam batas waktu 24 jam.</div>
                        </div>
                    </div>
                    <button type="button" @click="showCancelApproveModal = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px; height:18px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/penggajian/cancel-approve') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="penggajian_id" value="<?= $run['id'] ?>">

                    <div class="modal-body custom-scrollbar space-y-3">
                        <div style="padding:12px 14px; background:#fffbeb; border:1px solid #fde68a; border-radius:var(--rounded-md); font-size:12.5px; color:#92400e; line-height:1.5;">
                            Pembatalan akan me-rollback transaksi arus kas, memulihkan saldo akun kas terpilih, mengembalikan cicilan pinjaman kasbon dan tabungan, serta mengembalikan status payroll menjadi <strong>DRAF</strong>.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="showCancelApproveModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                        <button type="submit" class="btn w-full sm:w-auto" style="background:#d97706; color:#ffffff; border:1px solid #b45309; display:inline-flex; align-items:center; justify-content:center; gap:6px; font-weight:700;">
                            <i data-lucide="rotate-ccw" style="width:16px; height:16px;"></i>
                            <span>Ya, Batalkan Approval</span>
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
        selectedKasId: '<?= !empty($akunKasList[0]['id']) ? (string)$akunKasList[0]['id'] : '' ?>',
        totalPayrollNet: <?= (float)$totalGajiBersih ?>,
        totalPotonganTabunganAll: <?= (float)$totalPotonganTabunganAll ?>,
        totalPenarikanTabunganAll: <?= (float)$totalPenarikanTabunganAll ?>,
        periodLabels: <?= json_encode($periodLabels, JSON_UNESCAPED_UNICODE) ?>,
        get totalKebutuhanKas() {
            return this.totalPayrollNet + this.totalPotonganTabunganAll;
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
        selectedItem: null,

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

        // Table items berdasarkan tab & search
        get bulananTableItems() {
            if (this.statusFilter === 'borongan') return [];
            const q = (this.searchQuery || '').toLowerCase().trim();
            return this.includedItems.filter(item => {
                if (item.group !== 'bulanan') return false;
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

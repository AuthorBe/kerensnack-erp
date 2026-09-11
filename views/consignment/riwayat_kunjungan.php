<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();

// Perhitungan Finansial & Operasional untuk KPI Summary
$totalKunjungan = (int)($kpiSummary['total_kunjungan'] ?? count($visits));
$totalNominalLaku = (float)($kpiSummary['total_nominal_laku'] ?? array_sum(array_column($visits, 'total_laku_nominal')));
$totalPcsLaku = (int)($kpiSummary['total_pcs_laku'] ?? array_sum(array_column($visits, 'total_laku')));
$totalReturRusak = (int)($kpiSummary['total_retur_rusak'] ?? array_sum(array_column($visits, 'total_retur_rusak')));
$totalReturBagus = (int)($kpiSummary['total_retur_bagus'] ?? array_sum(array_column($visits, 'total_retur_bagus')));

$countUniqueStores = (int)($kpiSummary['count_unique_stores'] ?? count(array_unique(array_filter(array_column($visits, 'nama_toko')))));

// Kunjungan yang laku > 0 tapi belum diterbitkan faktur
$countMenungguTagihan = (int)($kpiSummary['count_menunggu_tagihan'] ?? count(array_filter($visits, fn($v) => (float)$v['total_laku_nominal'] > 0 && empty($v['pesanan_id']))));
$nominalMenungguTagihan = (float)($kpiSummary['nominal_menunggu_tagihan'] ?? array_sum(array_column(array_filter($visits, fn($v) => (float)$v['total_laku_nominal'] > 0 && empty($v['pesanan_id'])), 'total_laku_nominal')));

// Hitung rentang tanggal cepat untuk quick preset
$todayStr     = date('Y-m-d');
$last7DaysStr = date('Y-m-d', strtotime('-6 days'));
$startMonth   = date('Y-m-01');
$endMonth     = date('Y-m-d');
$startPrevMo  = date('Y-m-01', strtotime('first day of last month'));
$endPrevMo    = date('Y-m-t', strtotime('last month'));
?>

<style>
/* ========================================================================= */
/* RIWAYAT KUNJUNGAN ERP STANDARD STYLING                                    */
/* ========================================================================= */
.rk-page-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding-bottom: 90px;
}

.rk-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 18px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: all 0.2s ease;
}

/* KPI Stat Cards */
.rk-kpi-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 18px;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 122px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.rk-kpi-card:hover {
    border-color: var(--color-hairline-strong);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.04);
}

.rk-kpi-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-ink-mute);
    line-height: 1.2;
}

.rk-kpi-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.rk-kpi-value {
    font-family: var(--font-mono);
    font-weight: 900;
    font-size: clamp(20px, 2.2vw, 26px);
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
}

/* Filter Card Header & Quick Presets */
.rk-filter-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 14px;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--color-hairline);
    flex-wrap: wrap;
}

.rk-filter-title {
    display: flex;
    align-items: center;
    gap: 8px;
}

.rk-filter-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: rgba(99, 102, 241, 0.08);
    color: #6366f1;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.dark .rk-filter-icon {
    background: rgba(99, 102, 241, 0.16);
    color: #818cf8;
}

.rk-filter-presets {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.rk-preset-chip {
    font-size: 11.5px;
    font-weight: 700;
    padding: 5.5px 12px;
    border-radius: 9px;
    border: 1px solid var(--color-hairline);
    background: var(--color-canvas-soft);
    color: var(--color-ink-secondary);
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    user-select: none;
    line-height: 1.3;
}

.rk-preset-chip:hover {
    background: var(--color-canvas);
    color: var(--color-ink);
    border-color: var(--color-hairline-strong);
}

.rk-preset-chip.is-active {
    background: #6366f1;
    color: #ffffff;
    border-color: #6366f1;
    box-shadow: 0 2px 6px rgba(99, 102, 241, 0.25);
}

/* Toolbar & Search Bar Styling */
.rk-toolbar-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 16px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.rk-search-wrap {
    position: relative;
    width: 100%;
    max-width: 420px;
    flex: 1 1 280px;
}

.rk-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 15px !important;
    height: 15px !important;
    color: var(--color-ink-mute);
    pointer-events: none;
}

.rk-search-input {
    width: 100%;
    height: 38px;
    font-size: 12px;
    font-weight: 500;
    font-family: var(--font-sans);
    border-radius: 11px;
    border: 1px solid var(--color-hairline);
    background-color: var(--color-canvas-soft);
    color: var(--color-ink);
    padding: 0 32px 0 36px;
    transition: all 0.15s ease;
    outline: none;
    box-sizing: border-box;
}

.rk-search-input:focus {
    border-color: #6366f1;
    background-color: var(--color-canvas);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.rk-search-input::placeholder {
    color: var(--color-ink-mute);
    font-weight: 400;
}

.rk-search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-ink-mute);
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    transition: all 0.15s ease;
}

.rk-search-clear:hover {
    color: var(--color-ink);
    background: rgba(0, 0, 0, 0.06);
}
.dark .rk-search-clear:hover {
    background: rgba(255, 255, 255, 0.1);
}

.rk-toolbar-summary {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 600;
    color: var(--color-ink-secondary);
    white-space: nowrap;
    user-select: none;
}

.rk-toolbar-summary strong {
    color: var(--color-ink);
    font-weight: 800;
}

.rk-toolbar-summary i, .rk-toolbar-summary svg {
    width: 15px !important;
    height: 15px !important;
    color: #6366f1;
    flex-shrink: 0;
}

/* Empty State */
.rk-empty-box {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 20px;
    padding: 48px 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.rk-empty-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: rgba(99, 102, 241, 0.1);
    color: #6366f1;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
    box-shadow: 0 0 0 5px rgba(99, 102, 241, 0.05);
}

/* Minimalist Clean Table Styling */
.rk-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    text-align: left;
}

.rk-table thead th {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-ink-mute);
    padding: 12px 16px;
    border-bottom: 1px solid var(--color-hairline);
    background-color: var(--color-canvas-soft);
    white-space: nowrap;
    vertical-align: middle;
}

.rk-table tbody td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--color-hairline);
    color: var(--color-ink);
    vertical-align: middle;
}

.rk-table tbody tr {
    transition: background-color 0.15s ease;
}

.rk-table tbody tr:hover td {
    background-color: rgba(99, 102, 241, 0.035);
}

.rk-table tbody tr:last-child td {
    border-bottom: none;
}

/* Unified Soft Badges */
.rk-badge-visit {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3.5px 9.5px;
    border-radius: 8px;
    font-family: var(--font-mono);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.02em;
    background: var(--color-canvas-soft);
    color: var(--color-ink);
    border: 1px solid var(--color-hairline);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
    line-height: 1.25;
    white-space: nowrap;
}

.dark .rk-badge-visit {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
    color: #e2e8f0;
}

.rk-badge-cust {
    display: inline-flex;
    align-items: center;
    padding: 2.5px 8px;
    border-radius: 6px;
    font-family: var(--font-mono);
    font-size: 10px;
    font-weight: 800;
    color: #0284c7;
    background: rgba(2, 132, 199, 0.08);
    border: 1px solid rgba(2, 132, 199, 0.2);
    line-height: 1.25;
    white-space: nowrap;
}

.rk-badge-bs {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 800;
    background: rgba(239, 68, 68, 0.08);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.22);
    line-height: 1.25;
    white-space: nowrap;
}

/* Unified Status Badges */
.rk-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3.5px 9.5px;
    border-radius: 7px;
    font-size: 10.5px;
    font-weight: 700;
    line-height: 1.25;
    white-space: nowrap;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.rk-status-lunas {
    background: rgba(16, 185, 129, 0.09);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.25);
}
.dark .rk-status-lunas {
    color: #34d399;
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.3);
}

.rk-status-belum-lunas {
    background: rgba(244, 63, 94, 0.09);
    color: #e11d48;
    border: 1px solid rgba(244, 63, 94, 0.25);
}
.dark .rk-status-belum-lunas {
    color: #fb7185;
    background: rgba(244, 63, 94, 0.15);
    border-color: rgba(244, 63, 94, 0.3);
}

.rk-status-cicil {
    background: rgba(245, 158, 11, 0.09);
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.25);
}
.dark .rk-status-cicil {
    color: #fbbf24;
    background: rgba(245, 158, 11, 0.15);
    border-color: rgba(245, 158, 11, 0.3);
}

.rk-status-menunggu {
    background: rgba(245, 158, 11, 0.08);
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.22);
}
.dark .rk-status-menunggu {
    color: #fbbf24;
    background: rgba(245, 158, 11, 0.14);
    border-color: rgba(245, 158, 11, 0.28);
}

.rk-status-nihil {
    background: var(--color-canvas-soft);
    color: var(--color-ink-mute);
    border: 1px solid var(--color-hairline);
}
.dark .rk-status-nihil {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.08);
    color: #94a3b8;
}

.rk-nota-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    border-radius: 5px;
    font-family: var(--font-mono);
    font-size: 9.5px;
    font-weight: 600;
    letter-spacing: 0.01em;
    color: var(--color-ink-mute);
    background: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    text-decoration: none;
    transition: all 0.15s ease;
}
.dark .rk-nota-badge {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.08);
    color: #94a3b8;
}
.rk-nota-badge:hover {
    color: #6366f1;
    border-color: rgba(99, 102, 241, 0.35);
    background: rgba(99, 102, 241, 0.06);
}

/* Pagination Styling */
.rk-pagination-bar {
    padding: 14px 18px;
    background: var(--color-canvas-soft);
    border-top: 1px solid var(--color-hairline);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.rk-pagination-info {
    font-size: 12px;
    color: var(--color-ink-secondary);
    font-weight: 500;
}
.rk-pagination-info strong {
    color: var(--color-ink);
    font-weight: 700;
}
.rk-pagination-nav {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
}
.rk-page-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 700;
    color: var(--color-ink);
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    text-decoration: none;
    transition: all 0.15s ease;
}
.rk-page-btn:hover:not(.is-disabled) {
    border-color: #6366f1;
    color: #6366f1;
    background: rgba(99, 102, 241, 0.05);
}
.rk-page-btn.is-disabled {
    opacity: 0.45;
    cursor: not-allowed;
    background: transparent;
    border-color: var(--color-hairline);
    color: var(--color-ink-mute);
}
.rk-page-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 6px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    font-family: var(--font-mono);
    color: var(--color-ink-secondary);
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    text-decoration: none;
    transition: all 0.15s ease;
}
.rk-page-num:hover:not(.is-active) {
    border-color: #6366f1;
    color: #6366f1;
    background: rgba(99, 102, 241, 0.05);
}
.rk-page-num.is-active {
    background: #6366f1;
    border-color: #6366f1;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(99, 102, 241, 0.3);
}
.rk-page-dots {
    padding: 0 4px;
    color: var(--color-ink-mute);
    font-weight: bold;
}
</style>

<div x-data="riwayatKunjunganApp()" class="rk-page-container">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-header-body">
            <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary btn-sm p-2.5 rounded-xl" title="Kembali ke Portal Konsinyasi">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#6366f1;"></span>
                    <span>Audit Trail • Settlement Lapangan</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl">Riwayat Kunjungan Toko</h1>
                <p class="page-subtitle text-xs sm:text-sm">Log riwayat audit kunjungan opname berkala, hasil penjualan rak, dan status settlement faktur toko mitra.</p>
            </div>
        </div>

        <div class="flex items-center gap-2 mt-3 sm:mt-0 flex-wrap">
            <a href="<?= Router::url('/consignment/opname') ?>" class="btn btn-primary btn-sm flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold shadow-sm" style="background:#6366f1;border-color:#6366f1;">
                <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                <span>Opname Baru</span>
            </a>
            <a href="<?= Router::url('/consignment/tagihan') ?>" class="btn btn-secondary btn-sm flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold">
                <i data-lucide="receipt" class="w-4 h-4 text-emerald-600"></i>
                <span>Menu Tagihan</span>
            </a>
        </div>
    </div>

    <!-- 4 KPI SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        <!-- Card 1: Total Kunjungan -->
        <div class="rk-kpi-card" style="border-left: 3.5px solid #6366f1;">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <span class="rk-kpi-label block" style="color:#6366f1;">Total Kunjungan</span>
                    <span class="text-[10px] font-bold font-mono text-indigo-500/80 block mt-0.5">PERIODE TERPILIH</span>
                </div>
                <div class="rk-kpi-icon" style="background:rgba(99,102,241,0.1);color:#6366f1;">
                    <i data-lucide="calendar-check-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="rk-kpi-value text-indigo-600 dark:text-indigo-400">
                    <?= number_format($totalKunjungan, 0, ',', '.') ?> <span class="text-xs font-normal text-slate-400">Sesi</span>
                </div>
                <span class="text-xs mt-1 block" style="color:var(--color-ink-secondary);">
                    <?= $countUniqueStores ?> Toko mitra unik dikunjungi
                </span>
            </div>
        </div>

        <!-- Card 2: Omset Terjual -->
        <div class="rk-kpi-card" style="border-left: 3.5px solid #10b981;">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <span class="rk-kpi-label block" style="color:#059669;">Omset Laku Terjual</span>
                    <span class="text-[10px] font-bold font-mono text-emerald-500/80 block mt-0.5">PENJUALAN RAK</span>
                </div>
                <div class="rk-kpi-icon" style="background:rgba(16,185,129,0.1);color:#10b981;">
                    <i data-lucide="banknote" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="rk-kpi-value text-emerald-600 dark:text-emerald-400">
                    <?= Format::rupiah($totalNominalLaku) ?>
                </div>
                <span class="text-xs mt-1 block" style="color:var(--color-ink-secondary);">
                    Akumulasi penjualan laku bersih
                </span>
            </div>
        </div>

        <!-- Card 3: Fisik Terjual -->
        <div class="rk-kpi-card" style="border-left: 3.5px solid #0284c7;">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <span class="rk-kpi-label block" style="color:#0284c7;">Fisik Terjual</span>
                    <span class="text-[10px] font-bold font-mono text-sky-500/80 block mt-0.5">TOTAL KUANTITAS</span>
                </div>
                <div class="rk-kpi-icon" style="background:rgba(2,132,199,0.1);color:#0284c7;">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="rk-kpi-value text-sky-600 dark:text-sky-400">
                    <?= number_format($totalPcsLaku, 0, ',', '.') ?> <span class="text-xs font-normal text-slate-400">Pcs</span>
                </div>
                <span class="text-xs mt-1 block" style="color:var(--color-ink-secondary);">
                    <?= $totalReturRusak > 0 ? '+ ' . number_format($totalReturRusak) . ' pcs retur BS ditarik' : 'Stok rak terpantau seimbang' ?>
                </span>
            </div>
        </div>

        <!-- Card 4: Menunggu Tagihan -->
        <div class="rk-kpi-card" style="border-left: 3.5px solid <?= $countMenungguTagihan > 0 ? '#f59e0b' : '#10b981' ?>;">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <span class="rk-kpi-label block" style="color:<?= $countMenungguTagihan > 0 ? '#d97706' : '#059669' ?>;">Menunggu Tagihan</span>
                    <span class="text-[10px] font-bold font-mono <?= $countMenungguTagihan > 0 ? 'text-amber-500/80' : 'text-emerald-500/80' ?> block mt-0.5">
                        <?= $countMenungguTagihan > 0 ? 'PERLU FAKTUR' : 'SEMUA TERFAKTUR' ?>
                    </span>
                </div>
                <div class="rk-kpi-icon" style="background:<?= $countMenungguTagihan > 0 ? 'rgba(245,158,11,0.1)' : 'rgba(16,185,129,0.1)' ?>;color:<?= $countMenungguTagihan > 0 ? '#d97706' : '#10b981' ?>;">
                    <i data-lucide="<?= $countMenungguTagihan > 0 ? 'clock' : 'check-circle-2' ?>" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="rk-kpi-value <?= $countMenungguTagihan > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-500' ?>">
                    <?= number_format($countMenungguTagihan, 0, ',', '.') ?> <span class="text-xs font-normal text-slate-400">Kunjungan</span>
                </div>
                <span class="text-xs mt-1 block" style="color:var(--color-ink-secondary);">
                    <?= $countMenungguTagihan > 0 ? Format::rupiah($nominalMenungguTagihan) . ' belum difakturkan' : 'Seluruh sesi opname selesai' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- FILTER CARD & QUICK PRESETS -->
    <div class="rk-card p-4 sm:p-5">
        <div class="rk-filter-header">
            <div class="rk-filter-title">
                <div class="rk-filter-icon">
                    <i data-lucide="sliders-horizontal" style="width:14px;height:14px;"></i>
                </div>
                <span class="font-bold text-xs sm:text-sm" style="color:var(--color-ink);">Filter Parameter Kunjungan</span>
            </div>

            <!-- Quick Date Presets -->
            <div class="rk-filter-presets">
                <span class="text-xs font-bold text-slate-400 mr-1 hidden sm:inline">Preset:</span>
                <button type="button" @click="setDateRange('<?= $todayStr ?>', '<?= $todayStr ?>')" class="rk-preset-chip <?= ($startDate === $todayStr && $endDate === $todayStr) ? 'is-active' : '' ?>">Hari Ini</button>
                <button type="button" @click="setDateRange('<?= $last7DaysStr ?>', '<?= $todayStr ?>')" class="rk-preset-chip <?= ($startDate === $last7DaysStr && $endDate === $todayStr) ? 'is-active' : '' ?>">7 Hari</button>
                <button type="button" @click="setDateRange('<?= $startMonth ?>', '<?= $todayStr ?>')" class="rk-preset-chip <?= ($startDate === $startMonth && $endDate === $todayStr) ? 'is-active' : '' ?>">Bulan Ini</button>
                <button type="button" @click="setDateRange('<?= $startPrevMo ?>', '<?= $endPrevMo ?>')" class="rk-preset-chip <?= ($startDate === $startPrevMo && $endDate === $endPrevMo) ? 'is-active' : '' ?>">Bulan Lalu</button>
            </div>
        </div>

        <form id="filterForm" method="GET" action="<?= Router::url('/consignment/riwayat-kunjungan') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 items-end">
            <!-- Dari Tanggal -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider mb-1.5" style="color:var(--color-ink-mute);">Dari Tanggal</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-input w-full text-xs font-medium" style="height:38px;border-radius:10px;">
            </div>

            <!-- Sampai Tanggal -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider mb-1.5" style="color:var(--color-ink-mute);">Sampai Tanggal</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-input w-full text-xs font-medium" style="height:38px;border-radius:10px;">
            </div>

            <!-- Filter Toko -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider mb-1.5" style="color:var(--color-ink-mute);">Toko Mitra</label>
                <select name="pelanggan_id" class="form-input w-full text-xs searchable-select" style="height:38px;border-radius:10px;">
                    <option value="">Semua Toko Mitra</option>
                    <?php foreach ($stores as $st): ?>
                    <option value="<?= $st['id'] ?>" <?= $selectedStoreId === $st['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($st['nama_toko']) ?><?= !empty($st['kode_pelanggan']) ? ' (' . htmlspecialchars($st['kode_pelanggan']) . ')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Tagihan -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider mb-1.5" style="color:var(--color-ink-mute);">Status Settlement</label>
                <select name="status_tagihan" class="form-input w-full text-xs font-medium" style="height:38px;border-radius:10px;">
                    <option value="semua" <?= ($selectedStatusTagihan ?? 'semua') === 'semua' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="menunggu_tagihan" <?= ($selectedStatusTagihan ?? '') === 'menunggu_tagihan' ? 'selected' : '' ?>>Menunggu Tagihan (Siap Ditagih)</option>
                    <option value="belum_lunas" <?= ($selectedStatusTagihan ?? '') === 'belum_lunas' ? 'selected' : '' ?>>Faktur - Belum Lunas / Cicil</option>
                    <option value="lunas" <?= ($selectedStatusTagihan ?? '') === 'lunas' ? 'selected' : '' ?>>Faktur - Lunas</option>
                    <option value="nihil" <?= ($selectedStatusTagihan ?? '') === 'nihil' ? 'selected' : '' ?>>Nihil (Stok Utuh Rp 0)</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary flex-1 py-2 text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm" style="background:#6366f1;border-color:#6366f1;height:38px;border-radius:10px;">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Terapkan</span>
                </button>
                <a href="<?= Router::url('/consignment/riwayat-kunjungan') ?>" class="btn btn-secondary py-2 px-3 text-xs flex items-center justify-center rounded-xl" style="height:38px;" title="Reset Filter ke Default">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- TOOLBAR REAL-TIME SEARCH & SUMMARY -->
    <div class="rk-toolbar-card">
        <div class="rk-search-wrap">
            <i data-lucide="search" class="rk-search-icon"></i>
            <input type="text" 
                   x-model="search" 
                   placeholder="Cari toko, no kunjungan, sales, atau nota..." 
                   class="rk-search-input">
            <button type="button" 
                    x-show="search" 
                    @click="search = ''" 
                    class="rk-search-clear"
                    title="Hapus kata kunci pencarian">
                <i data-lucide="x" style="width:13px;height:13px;"></i>
            </button>
        </div>

        <div class="rk-toolbar-summary">
            <i data-lucide="list-ordered"></i>
            <span>Menampilkan <strong><?= count($visits) ?></strong> dari <strong><?= number_format($totalVisits ?? $totalKunjungan) ?></strong> sesi kunjungan</span>
        </div>
    </div>

    <!-- TABLE RIWAYAT KUNJUNGAN -->
    <?php if (empty($visits)): ?>
        <div class="rk-empty-box">
            <div class="rk-empty-icon">
                <i data-lucide="calendar-x" class="w-7 h-7"></i>
            </div>
            <h3 class="text-base sm:text-lg font-bold" style="color:var(--color-ink);">Belum Ada Riwayat Kunjungan</h3>
            <p class="text-xs sm:text-sm mt-1.5 max-w-md mx-auto" style="color:var(--color-ink-secondary);line-height:1.5;">
                Tidak ada data riwayat kunjungan konsinyasi yang sesuai dengan rentang tanggal, filter toko, atau status settlement yang dipilih.
            </p>
            <div class="mt-4">
                <button type="button" @click="setDateRange('<?= $startMonth ?>', '<?= $todayStr ?>')" class="btn btn-secondary btn-sm px-4 py-2 text-xs font-bold rounded-xl">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 mr-1.5 text-indigo-500"></i>
                    <span>Tampilkan Kunjungan Bulan Ini</span>
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="rk-card overflow-hidden">
            <div class="table-scroll">
                <table class="rk-table">
                    <thead>
                        <tr>
                            <th style="min-width:180px;">Kunjungan &amp; Tanggal</th>
                            <th style="min-width:200px;">Toko Mitra</th>
                            <th style="min-width:150px;">Petugas Sales</th>
                            <th class="cell-center" style="min-width:110px;">Opname SKU</th>
                            <th class="cell-center" style="min-width:105px;">Fisik Laku</th>
                            <th class="cell-right" style="min-width:135px;">Nilai Laku (Rp)</th>
                            <th class="cell-center" style="min-width:160px;">Status Tagihan</th>
                            <th class="cell-center" style="width:85px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visits as $v): ?>
                        <?php 
                            $searchKeywords = strtolower(trim(preg_replace('/\s+/', ' ', $v['nama_toko'] . ' ' . ($v['kode_pelanggan'] ?? '') . ' ' . $v['nomor_kunjungan'] . ' ' . $v['nama_sales'] . ' ' . ($v['auditor_name'] ?? '') . ' ' . ($v['nomor_nota'] ?? ''))));
                        ?>
                        <tr data-search="<?= htmlspecialchars($searchKeywords, ENT_QUOTES, 'UTF-8') ?>" x-show="!search || ($el.dataset.search &amp;&amp; $el.dataset.search.includes(search.toLowerCase()))">
                            <!-- Kunjungan & Tanggal (2-Line) -->
                            <td class="cell-nowrap">
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex items-center">
                                        <a href="/consignment/opname/hasil?kunjungan_id=<?= urlencode((string)$v['id']) ?>" 
                                           class="inline-flex items-center gap-1 font-mono font-bold text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 transition-colors"
                                           title="Buka rincian hasil opname kunjungan">
                                            <i data-lucide="external-link" style="width:12px;height:12px;"></i>
                                            <span><?= htmlspecialchars($v['nomor_kunjungan']) ?></span>
                                        </a>
                                    </div>
                                    <div class="flex items-center gap-1 text-[11px] text-mute">
                                        <i data-lucide="calendar" style="width:11px;height:11px;"></i>
                                        <span><?= date('d/m/Y', strtotime($v['tanggal_kunjungan'])) ?> &bull; <?= date('H:i', strtotime($v['tanggal_kunjungan'])) ?> WIB</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Toko Mitra -->
                            <td>
                                <div>
                                    <span class="font-bold text-xs block" style="color:var(--color-ink);" title="<?= htmlspecialchars($v['nama_toko']) ?>">
                                        <?= htmlspecialchars($v['nama_toko']) ?>
                                    </span>
                                    <?php if (!empty($v['kode_pelanggan'])): ?>
                                        <span class="rk-badge-cust mt-1" title="Kode Pelanggan: <?= htmlspecialchars($v['kode_pelanggan']) ?>">
                                            <?= htmlspecialchars($v['kode_pelanggan']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Petugas Sales & Penginput -->
                            <td class="cell-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 flex-shrink-0">
                                        <i data-lucide="user" style="width:12px;height:12px;"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <span class="block truncate max-w-[150px] font-semibold text-xs" style="color:var(--color-ink);" title="Sales Lapangan: <?= htmlspecialchars($v['nama_sales']) ?>">
                                            <?= htmlspecialchars($v['nama_sales']) ?>
                                        </span>
                                        <?php if (!empty($v['auditor_name']) && strcasecmp(trim($v['auditor_name']), trim($v['nama_sales'])) !== 0): ?>
                                            <span class="block text-[10.5px] truncate max-w-[150px]" style="color:var(--color-ink-mute);margin-top:1px;" title="Diopname oleh: <?= htmlspecialchars($v['auditor_name']) ?>">
                                                opname: <?= htmlspecialchars($v['auditor_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- Ringkasan Opname SKU & Retur -->
                            <td class="cell-center cell-nowrap">
                                <div class="flex flex-col items-center gap-1.5">
                                    <span class="font-bold text-xs" style="color:var(--color-ink);">
                                        <?= (int)$v['total_sku'] ?> SKU
                                    </span>
                                    <?php if ((int)$v['total_retur_rusak'] > 0): ?>
                                        <span class="rk-badge-bs" title="Barang Rusak/BS Ditarik">
                                            <i data-lucide="trash-2" style="width:11px;height:11px;" class="text-rose-500"></i>
                                            <span>BS: <?= (int)$v['total_retur_rusak'] ?></span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Total Fisik Laku -->
                            <td class="cell-center cell-nowrap">
                                <span class="font-black font-mono text-xs <?= (int)$v['total_laku'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' ?>">
                                    <?= number_format((int)$v['total_laku'], 0, ',', '.') ?> <span class="text-[10px] font-normal text-slate-400">pcs</span>
                                </span>
                            </td>

                            <!-- Nilai Laku (Rp) -->
                            <td class="cell-right cell-currency cell-nowrap">
                                <span class="font-black font-mono text-xs <?= (float)$v['total_laku_nominal'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' ?>">
                                    <?= Format::rupiah((float)$v['total_laku_nominal']) ?>
                                </span>
                            </td>

                            <!-- Status Tagihan -->
                            <td class="cell-center cell-nowrap">
                                <div class="flex flex-col items-center gap-1.5">
                                    <?php if ((float)$v['total_laku_nominal'] == 0.0): ?>
                                        <span class="rk-status-badge rk-status-nihil" title="Tidak ada penjualan rak">
                                            <i data-lucide="minus-circle" style="width:12px;height:12px;"></i>
                                            <span>Nihil (Rp 0)</span>
                                        </span>
                                    <?php elseif (empty($v['pesanan_id'])): ?>
                                        <span class="rk-status-badge rk-status-menunggu" title="Opname selesai, siap dibuatkan faktur">
                                            <i data-lucide="clock" style="width:12px;height:12px;"></i>
                                            <span>Menunggu Tagihan</span>
                                        </span>
                                    <?php elseif ($v['status_pembayaran'] === 'lunas'): ?>
                                        <span class="rk-status-badge rk-status-lunas" title="Tagihan lunas terbayar">
                                            <i data-lucide="check-circle-2" style="width:12px;height:12px;"></i>
                                            <span>Lunas</span>
                                        </span>
                                        <a href="<?= Router::url('/consignment/nota-pdf?pesanan_id=' . $v['pesanan_id']) ?>" target="_blank" class="rk-nota-badge" title="Buka Faktur <?= htmlspecialchars($v['nomor_nota'] ?? '') ?>">
                                            <i data-lucide="receipt" style="width:10px;height:10px;"></i>
                                            <span><?= htmlspecialchars($v['nomor_nota'] ?? '-') ?></span>
                                        </a>
                                    <?php elseif ($v['status_pembayaran'] === 'sebagian'): ?>
                                        <span class="rk-status-badge rk-status-cicil" title="Sisa tagihan: <?= Format::rupiah((float)($v['sisa_tagihan'] ?? 0)) ?>">
                                            <i data-lucide="pie-chart" style="width:12px;height:12px;"></i>
                                            <span>Cicil</span>
                                        </span>
                                        <a href="<?= Router::url('/consignment/nota-pdf?pesanan_id=' . $v['pesanan_id']) ?>" target="_blank" class="rk-nota-badge" title="Buka Faktur <?= htmlspecialchars($v['nomor_nota'] ?? '') ?>">
                                            <i data-lucide="receipt" style="width:10px;height:10px;"></i>
                                            <span><?= htmlspecialchars($v['nomor_nota'] ?? '-') ?></span>
                                        </a>
                                    <?php else: ?>
                                        <span class="rk-status-badge rk-status-belum-lunas" title="Belum ada pembayaran tagihan">
                                            <i data-lucide="alert-circle" style="width:12px;height:12px;"></i>
                                            <span>Belum Lunas</span>
                                        </span>
                                        <a href="<?= Router::url('/consignment/nota-pdf?pesanan_id=' . $v['pesanan_id']) ?>" target="_blank" class="rk-nota-badge" title="Buka Faktur <?= htmlspecialchars($v['nomor_nota'] ?? '') ?>">
                                            <i data-lucide="receipt" style="width:10px;height:10px;"></i>
                                            <span><?= htmlspecialchars($v['nomor_nota'] ?? '-') ?></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Aksi -->
                            <td class="cell-center cell-nowrap">
                                <a href="<?= Router::url('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)$v['id']) . '&ref=riwayat') ?>" 
                                   class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-xl hover:border-slate-400 transition-all" 
                                   title="Buka Lembar Rincian Kunjungan &amp; Opname">
                                    <i data-lucide="eye" class="w-3.5 h-3.5 text-sky-500"></i>
                                    <span>Detail</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--color-canvas-soft);border-top:2px solid var(--color-hairline);">
                            <td colspan="4" class="font-extrabold uppercase text-[11px] tracking-wider" style="padding:14px 18px;color:var(--color-ink);">
                                TOTAL REKAPITULASI (PERIODE INI):
                            </td>
                            <td class="cell-center cell-nowrap font-mono font-black" style="padding:14px 18px;font-size:13px;color:var(--color-ink);">
                                <?= number_format($totalPcsLaku, 0, ',', '.') ?> pcs
                            </td>
                            <td class="cell-right cell-currency cell-nowrap font-mono font-black text-emerald-600 dark:text-emerald-400" style="padding:14px 18px;font-size:13.5px;">
                                <?= Format::rupiah($totalNominalLaku) ?>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Pagination Controls Bar -->
            <?php 
                $curPage = $currentPage ?? 1;
                $totPages = $totalPages ?? 1;
                $totRecords = $totalVisits ?? count($visits);
                $pSize = $perPage ?? 25;
                $startRecord = $totRecords > 0 ? ($curPage - 1) * $pSize + 1 : 0;
                $endRecord = min($curPage * $pSize, $totRecords);
            ?>
            <?php if ($totPages > 1 || $totRecords > 0): ?>
            <div class="rk-pagination-bar">
                <div class="rk-pagination-info">
                    Menampilkan <strong><?= number_format($startRecord) ?></strong> &ndash; <strong><?= number_format($endRecord) ?></strong> dari <strong><?= number_format($totRecords) ?></strong> sesi kunjungan
                    <?php if ($totPages > 1): ?>
                        (Halaman <strong><?= $curPage ?></strong> dari <strong><?= $totPages ?></strong>)
                    <?php endif; ?>
                </div>

                <?php if ($totPages > 1): ?>
                <div class="rk-pagination-nav">
                    <?php 
                        $baseQuery = $_GET;
                        unset($baseQuery['page']);
                        $buildPageUrl = function($p) use ($baseQuery) {
                            return Router::url('/consignment/riwayat-kunjungan?' . http_build_query(array_merge($baseQuery, ['page' => $p])));
                        };
                    ?>

                    <!-- Tombol Halaman Sebelumnya -->
                    <?php if ($curPage > 1): ?>
                        <a href="<?= $buildPageUrl($curPage - 1) ?>" class="rk-page-btn" title="Halaman Sebelumnya">
                            <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                            <span>Sebelumnya</span>
                        </a>
                    <?php else: ?>
                        <span class="rk-page-btn is-disabled">
                            <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                            <span>Sebelumnya</span>
                        </span>
                    <?php endif; ?>

                    <!-- Nomor Halaman -->
                    <?php 
                        $rangeStart = max(1, $curPage - 2);
                        $rangeEnd = min($totPages, $curPage + 2);
                        if ($rangeEnd - $rangeStart < 4) {
                            if ($rangeStart === 1) {
                                $rangeEnd = min($totPages, $rangeStart + 4);
                            } elseif ($rangeEnd === $totPages) {
                                $rangeStart = max(1, $rangeEnd - 4);
                            }
                        }
                    ?>

                    <?php if ($rangeStart > 1): ?>
                        <a href="<?= $buildPageUrl(1) ?>" class="rk-page-num">1</a>
                        <?php if ($rangeStart > 2): ?>
                            <span class="rk-page-dots">&hellip;</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $rangeStart; $p <= $rangeEnd; $p++): ?>
                        <?php if ($p === $curPage): ?>
                            <span class="rk-page-num is-active"><?= $p ?></span>
                        <?php else: ?>
                            <a href="<?= $buildPageUrl($p) ?>" class="rk-page-num"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($rangeEnd < $totPages): ?>
                        <?php if ($rangeEnd < $totPages - 1): ?>
                            <span class="rk-page-dots">&hellip;</span>
                        <?php endif; ?>
                        <a href="<?= $buildPageUrl($totPages) ?>" class="rk-page-num"><?= $totPages ?></a>
                    <?php endif; ?>

                    <!-- Tombol Halaman Selanjutnya -->
                    <?php if ($curPage < $totPages): ?>
                        <a href="<?= $buildPageUrl($curPage + 1) ?>" class="rk-page-btn" title="Halaman Selanjutnya">
                            <span>Selanjutnya</span>
                            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                        </a>
                    <?php else: ?>
                        <span class="rk-page-btn is-disabled">
                            <span>Selanjutnya</span>
                            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                        </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<script>
function riwayatKunjunganApp() {
    return {
        search: '',
        setDateRange(start, end) {
            const startInput = document.querySelector('input[name="start_date"]');
            const endInput = document.querySelector('input[name="end_date"]');
            if (startInput && endInput) {
                startInput.value = start;
                endInput.value = end;
                document.getElementById('filterForm').submit();
            }
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

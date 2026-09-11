<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();

$initialTab = in_array($_GET['tab'] ?? '', ['rusak', 'hilang']) ? $_GET['tab'] : 'rusak';

$exportExcelUrlRusak = Router::url('/consignment/kerugian-rusak/export-excel') . '?' . http_build_query([
    'type' => 'rusak',
    'start_date' => $startDate,
    'end_date' => $endDate,
    'pelanggan_id' => $selectedStoreId
]);

$exportExcelUrlHilang = Router::url('/consignment/kerugian-rusak/export-excel') . '?' . http_build_query([
    'type' => 'hilang',
    'pelanggan_id' => $selectedStoreId
]);

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
/* KERUGIAN & BARANG HILANG ERP STANDARD STYLING                             */
/* ========================================================================= */
.kr-page-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding-bottom: 90px;
}

.kr-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 18px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: all 0.2s ease;
}

.kr-tabs-nav {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px;
    border-radius: 16px;
    background-color: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    width: 100%;
    max-width: 520px;
}

.kr-tab-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 9px 16px;
    font-size: 13px;
    font-weight: 700;
    border-radius: 12px;
    transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    color: var(--color-ink-mute);
    border: 1px solid transparent;
    cursor: pointer;
    user-select: none;
    text-decoration: none;
    white-space: nowrap;
}

.kr-tab-btn:hover {
    color: var(--color-ink);
}

.kr-tab-btn.is-active {
    background-color: var(--color-canvas);
    color: var(--color-ink);
    border-color: var(--color-hairline);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.dark .kr-tab-btn.is-active {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* KPI Stat Cards */
.kr-kpi-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 18px;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 125px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.kr-kpi-card:hover {
    border-color: var(--color-hairline-strong);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.04);
}

.kr-kpi-label {
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--color-ink-mute);
    line-height: 1.2;
}

.kr-kpi-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.kr-kpi-value {
    font-family: var(--font-mono);
    font-weight: 900;
    font-size: clamp(20px, 2.2vw, 26px);
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
}

/* Quick Filter Presets */
.kr-preset-chip {
    font-size: 11.5px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 10px;
    border: 1px solid var(--color-hairline);
    background: var(--color-canvas-soft);
    color: var(--color-ink-secondary);
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.kr-preset-chip:hover {
    background: var(--color-canvas);
    color: var(--color-ink);
    border-color: var(--color-hairline-strong);
}

.kr-preset-chip.is-active {
    background: #ef4444;
    color: #ffffff;
    border-color: #ef4444;
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.25);
}

/* Accordion Store Card */
.kr-store-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 18px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    overflow: hidden;
    transition: border-color 0.2s ease;
}

.kr-store-card:hover {
    border-color: var(--color-hairline-strong);
}

/* SOP Policy Box */
.kr-sop-box {
    background-color: var(--color-canvas);
    border: 1px solid rgba(245, 158, 11, 0.25);
    border-radius: 18px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.kr-sop-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.kr-sop-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 800;
    color: #b45309;
}

.kr-sop-badge {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 3px 9px;
    border-radius: 6px;
    background: rgba(245, 158, 11, 0.12);
    color: #d97706;
}

.kr-sop-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 12px;
}

.kr-sop-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 14px;
    background: rgba(245, 158, 11, 0.04);
    border: 1px solid rgba(245, 158, 11, 0.12);
    font-size: 12px;
    line-height: 1.5;
    color: var(--color-ink-secondary);
}

.kr-sop-item-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Empty State Card */
.kr-empty-box {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 20px;
    padding: 44px 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.kr-empty-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
    box-shadow: 0 0 0 5px rgba(16, 185, 129, 0.05);
}

.kr-empty-badges {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 14px;
}

.kr-empty-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
    background: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    color: var(--color-ink-secondary);
}

/* Store View & Toolbar */
.kr-toolbar-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 16px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.kr-view-nav {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px;
    border-radius: 12px;
    background-color: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
}

.kr-view-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 700;
    border-radius: 8px;
    border: 1px solid transparent;
    color: var(--color-ink-mute);
    background: transparent;
    cursor: pointer;
    transition: all 0.15s ease;
    user-select: none;
}

.kr-view-btn:hover {
    color: var(--color-ink);
}

.kr-view-btn.is-active {
    background-color: var(--color-canvas);
    color: var(--color-ink);
    border-color: var(--color-hairline);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.kr-store-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-weight: 800;
    background: rgba(245, 158, 11, 0.12);
    color: #d97706;
}
</style>

<div x-data="kerugianRusakApp()" class="kr-page-container">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-header-body">
            <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary btn-sm p-2.5 rounded-xl" title="Kembali ke Portal Konsinyasi">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#ef4444;"></span>
                    <span>Audit Kerugian Stok • Admin &amp; Owner</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl">Laporan Kerugian &amp; Barang Hilang</h1>
                <p class="page-subtitle text-xs sm:text-sm">Audit HPP resmi barang retur rusak/BS ditarik &amp; monitoring saldo selisih hilang gantung di toko mitra.</p>
            </div>
        </div>
        <div class="page-header-actions flex items-center gap-2 flex-wrap">
            <a :href="getExportUrl()" 
               class="btn btn-secondary btn-sm flex items-center gap-2" 
               style="border-radius:12px;font-weight:700;border-color:rgba(16,185,129,0.4);color:#10b981;background:rgba(16,185,129,0.06);height:38px;padding:0 14px;" 
               title="Download Laporan Format Excel (.xlsx)">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                <span x-text="activeTab === 'hilang' ? 'Export Excel (Barang Hilang)' : 'Export Excel (Retur Rusak)'">Export Excel</span>
            </a>
        </div>
    </div>

    <!-- ZERO HPP NOTICE BANNER (IF ANY) -->
    <?php if (!empty($hasZeroHpp)): ?>
    <div class="card p-4 rounded-2xl flex items-start gap-3.5" style="background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.3);color:#b45309;">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(245,158,11,0.15);color:#d97706;">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        </div>
        <div class="text-xs leading-relaxed">
            <strong class="font-bold block text-sm" style="color:#b45309;">Perhatian: Terdeteksi Produk dengan HPP Rp 0</strong>
            <span style="color:var(--color-ink-secondary);">
                Terdapat produk yang belum memiliki Harga Pokok Pembelian (HPP) valid pada database. Nilai kerugian finansial untuk item tersebut belum dapat dihitung secara penuh. Silakan periksa dan perbarui HPP di master data produk agar laporan keuangan 100% presisi.
            </span>
        </div>
    </div>
    <?php endif; ?>

    <!-- SUMMARY METRIC CARDS (4 BALANCED CARDS) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Kerugian Rusak Riil -->
        <div class="kr-kpi-card" style="border-left: 3.5px solid #ef4444;">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <span class="kr-kpi-label block" style="color:#ef4444;">Kerugian Rusak</span>
                    <span class="text-[10px] font-bold font-mono text-rose-500/80 block mt-0.5">RETUR BS</span>
                </div>
                <div class="kr-kpi-icon flex-shrink-0" style="background:rgba(239,68,68,0.1);color:#ef4444;">
                    <i data-lucide="package-x" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="kr-kpi-value text-rose-600 dark:text-rose-400">
                    <?= Format::rupiah((float)$totalLossNominal) ?>
                </div>
                <span class="text-xs mt-1 block" style="color:var(--color-ink-secondary);">Valuasi resmi HPP barang BS</span>
            </div>
        </div>

        <!-- Card 2: Fisik Rusak Pcs -->
        <div class="kr-kpi-card" style="border-left: 3.5px solid #64748b;">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <span class="kr-kpi-label block">Fisik Rusak</span>
                    <span class="text-[10px] font-bold font-mono text-slate-400 block mt-0.5">DITARIK GUDANG</span>
                </div>
                <div class="kr-kpi-icon flex-shrink-0" style="background:rgba(100,116,139,0.1);color:#64748b;">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="kr-kpi-value" style="color:var(--color-ink);">
                    <?= number_format((float)$totalPcsRusak) ?> <span class="text-xs font-semibold" style="color:var(--color-ink-mute);">pcs</span>
                </div>
                <span class="text-xs mt-1 block" style="color:var(--color-ink-secondary);">Mutasi riwayat_stok waste</span>
            </div>
        </div>

        <!-- Card 3: Potensi Kerugian Barang Hilang Gantung -->
        <div class="kr-kpi-card" style="border-left: 3.5px solid #f59e0b;">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <span class="kr-kpi-label block" style="color:#d97706;">Potensi Hilang</span>
                    <span class="text-[10px] font-bold font-mono text-amber-500/80 block mt-0.5">DITANGGUHKAN</span>
                </div>
                <div class="kr-kpi-icon flex-shrink-0" style="background:rgba(245,158,11,0.1);color:#d97706;">
                    <i data-lucide="help-circle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="kr-kpi-value text-amber-600 dark:text-amber-400">
                    <?= Format::rupiah((float)$totalNominalPendingLoss) ?>
                </div>
                <span class="text-xs mt-1 block" style="color:var(--color-ink-secondary);">HPP barang hilang di rak (Ditangguhkan)</span>
            </div>
        </div>

        <!-- Card 4: Fisik Hilang Pcs -->
        <div class="kr-kpi-card" style="border-left: 3.5px solid <?= $totalPcsPendingLoss > 0 ? '#f59e0b' : '#10b981' ?>;">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <span class="kr-kpi-label block">Fisik Hilang</span>
                    <span class="text-[10px] font-bold font-mono text-slate-400 block mt-0.5">SALDO GANTUNG</span>
                </div>
                <div class="kr-kpi-icon flex-shrink-0" style="background:<?= $totalPcsPendingLoss > 0 ? 'rgba(245,158,11,0.1)' : 'rgba(16,185,129,0.1)' ?>;color:<?= $totalPcsPendingLoss > 0 ? '#d97706' : '#10b981' ?>;">
                    <i data-lucide="<?= $totalPcsPendingLoss > 0 ? 'scale' : 'check-circle' ?>" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="kr-kpi-value <?= $totalPcsPendingLoss > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-500' ?>">
                    <?= number_format((float)$totalPcsPendingLoss) ?> <span class="text-xs font-semibold" style="color:var(--color-ink-mute);">pcs</span>
                </div>
                <span class="text-xs mt-1 block" style="color:var(--color-ink-secondary);">
                    <?= $totalStoresPendingLoss > 0 ? "Dari {$totalStoresPendingLoss} toko mitra terdampak" : "Seluruh toko mitra seimbang" ?>
                </span>
            </div>
        </div>
    </div>

    <!-- MODERN SEGMENTED TAB SWITCHER -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="kr-tabs-nav">
            <button type="button" 
                    @click="setTab('rusak')" 
                    class="kr-tab-btn" 
                    :class="{ 'is-active': activeTab === 'rusak' }">
                <i data-lucide="package-x" class="w-4 h-4 text-rose-500"></i>
                <span>Retur Rusak / BS (Riil)</span>
                <span class="badge text-[11px] px-2 py-0.5" :class="activeTab === 'rusak' ? 'badge-danger' : 'badge-mono'"><?= count($losses) ?></span>
            </button>

            <button type="button" 
                    @click="setTab('hilang')" 
                    class="kr-tab-btn" 
                    :class="{ 'is-active': activeTab === 'hilang' }">
                <i data-lucide="help-circle" class="w-4 h-4 text-amber-500"></i>
                <span>Barang Hilang Toko (Gantung)</span>
                <span class="badge text-[11px] px-2 py-0.5 <?= $totalPcsPendingLoss > 0 ? 'badge-warning' : 'badge-mono' ?>">
                    <?= count($pendingLosses) ?>
                </span>
            </button>
        </div>

        <div class="text-xs font-semibold" style="color:var(--color-ink-secondary);">
            <span x-show="activeTab === 'rusak'">Periode Kunjungan: <strong class="text-slate-900 dark:text-white"><?= date('d/m/Y', strtotime($startDate)) ?> – <?= date('d/m/Y', strtotime($endDate)) ?></strong></span>
            <span x-show="activeTab === 'hilang'">Saldo Live Rak: <strong class="text-slate-900 dark:text-white">Per <?= date('d/m/Y H:i') ?></strong></span>
        </div>
    </div>

    <!-- TAB 1 CONTENT: RETUR RUSAK / BS -->
    <div x-show="activeTab === 'rusak'" class="space-y-5">

        <!-- FILTER BAR WITH QUICK DATE PRESETS -->
        <div class="card p-5 rounded-2xl" style="border:1px solid var(--color-hairline);">
            <form id="filterForm" method="GET" action="<?= Router::url('/consignment/kerugian-rusak') ?>" class="space-y-4">
                <input type="hidden" name="tab" value="rusak">

                <!-- Quick Presets Row -->
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-bold mr-1" style="color:var(--color-ink-mute);">Preset Tanggal:</span>
                    <button type="button" 
                            @click="setDateRange('<?= $todayStr ?>', '<?= $todayStr ?>')" 
                            class="kr-preset-chip <?= ($startDate === $todayStr && $endDate === $todayStr) ? 'is-active' : '' ?>">
                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                        <span>Hari Ini</span>
                    </button>
                    <button type="button" 
                            @click="setDateRange('<?= $last7DaysStr ?>', '<?= $todayStr ?>')" 
                            class="kr-preset-chip <?= ($startDate === $last7DaysStr && $endDate === $todayStr) ? 'is-active' : '' ?>">
                        <i data-lucide="calendar-range" class="w-3.5 h-3.5"></i>
                        <span>7 Hari Terakhir</span>
                    </button>
                    <button type="button" 
                            @click="setDateRange('<?= $startMonth ?>', '<?= $endMonth ?>')" 
                            class="kr-preset-chip <?= ($startDate === $startMonth && $endDate === $endMonth) ? 'is-active' : '' ?>">
                        <i data-lucide="calendar-days" class="w-3.5 h-3.5"></i>
                        <span>Bulan Ini</span>
                    </button>
                    <button type="button" 
                            @click="setDateRange('<?= $startPrevMo ?>', '<?= $endPrevMo ?>')" 
                            class="kr-preset-chip <?= ($startDate === $startPrevMo && $endDate === $endPrevMo) ? 'is-active' : '' ?>">
                        <i data-lucide="history" class="w-3.5 h-3.5"></i>
                        <span>Bulan Lalu</span>
                    </button>
                </div>

                <!-- Input Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end pt-1">
                    <div>
                        <label class="block text-xs font-bold mb-1.5" style="color:var(--color-ink);">Dari Tanggal:</label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-input w-full text-xs" style="height:40px;border-radius:12px;">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1.5" style="color:var(--color-ink);">Sampai Tanggal:</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-input w-full text-xs" style="height:40px;border-radius:12px;">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1.5" style="color:var(--color-ink);">Filter Toko Mitra:</label>
                        <select name="pelanggan_id" class="form-input w-full text-xs searchable-select" style="height:40px;border-radius:12px;">
                            <option value="">Semua Toko Mitra</option>
                            <?php foreach ($stores as $st): ?>
                            <option value="<?= $st['id'] ?>" <?= $selectedStoreId === $st['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($st['nama_toko']) ?><?= !empty($st['kode_pelanggan']) ? ' (' . htmlspecialchars($st['kode_pelanggan']) . ')' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary flex-1 py-2 text-xs font-bold flex items-center justify-center gap-2" style="background:#ef4444;border-color:#ef4444;height:40px;border-radius:12px;">
                            <i data-lucide="filter" class="w-4 h-4"></i>
                            <span>Terapkan Filter</span>
                        </button>
                        <a href="<?= Router::url('/consignment/kerugian-rusak') ?>" class="btn btn-secondary py-2 px-3 text-xs flex items-center justify-center" style="height:40px;border-radius:12px;" title="Reset Filter">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- MINI TABLE: TOP PRODUK BS (MINIMALIS, TERSTRUKTUR & RAPI) -->
        <?php if (!empty($topBsProducts)): ?>
        <div class="card p-0 rounded-2xl overflow-hidden" style="border:1px solid var(--color-hairline);">
            <!-- Card Header -->
            <div class="px-5 py-3 flex items-center justify-between border-b" style="background:var(--color-canvas-soft);border-color:var(--color-hairline);">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(239,68,68,0.12);color:#ef4444;">
                        <i data-lucide="flame" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="font-bold text-xs sm:text-sm" style="color:var(--color-ink);">Top Produk Retur Rusak</span>
                        <span class="text-[11px] text-slate-400 hidden sm:inline ml-1">• Evaluasi Mutu Kemasan</span>
                    </div>
                </div>
                <span class="badge badge-mono text-[10.5px]">
                    <?= count($topBsProducts) ?> SKU Terbanyak
                </span>
            </div>

            <!-- Data Table with Balanced Proportions -->
            <div class="table-scroll">
                <table class="data-table" style="font-size:12.5px;">
                    <thead>
                        <tr>
                            <th class="cell-center" style="width:75px;">Rank</th>
                            <th style="min-width:240px;">Item Produk</th>
                            <th style="width:130px;">Kode SKU</th>
                            <th class="cell-center" style="width:130px;">Qty Rusak</th>
                            <th class="cell-right" style="width:160px;">Valuasi Kerugian</th>
                            <th style="width:160px;">Kontribusi %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rankBadges = [
                            0 => ['label' => '🥇 #1', 'color' => '#d97706', 'bg' => 'rgba(245,158,11,0.12)', 'border' => 'rgba(245,158,11,0.25)'],
                            1 => ['label' => '🥈 #2', 'color' => '#64748b', 'bg' => 'rgba(100,116,139,0.12)', 'border' => 'rgba(100,116,139,0.25)'],
                            2 => ['label' => '🥉 #3', 'color' => '#b45309', 'bg' => 'rgba(217,119,6,0.12)',  'border' => 'rgba(217,119,6,0.25)']
                        ];
                        foreach ($topBsProducts as $idx => $tbs): 
                            $rb = $rankBadges[$idx] ?? ['label' => '#' . ($idx + 1), 'color' => '#64748b', 'bg' => 'rgba(100,116,139,0.1)', 'border' => 'transparent'];
                            $pct = $totalLossNominal > 0 ? round(((float)$tbs['total_rp'] / (float)$totalLossNominal) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td class="cell-center">
                                <span class="badge font-bold text-xs" style="background:<?= $rb['bg'] ?>;color:<?= $rb['color'] ?>;border:1px solid <?= $rb['border'] ?>;padding:2px 8px;">
                                    <?= $rb['label'] ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color:var(--color-ink);font-size:13px;"><?= htmlspecialchars($tbs['nama_item']) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-mono font-mono text-[10.5px]">
                                    <?= htmlspecialchars($tbs['kode_sku'] ?? 'NO-SKU') ?>
                                </span>
                            </td>
                            <td class="cell-center">
                                <span class="badge" style="background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.25);font-weight:800;font-size:12px;font-family:var(--font-mono);">
                                    <?= (int)$tbs['total_pcs'] ?> <?= htmlspecialchars($tbs['satuan_dasar']) ?>
                                </span>
                            </td>
                            <td class="cell-right cell-currency" style="font-weight:900;color:#ef4444;font-size:13.5px;">
                                <?= Format::rupiah((float)$tbs['total_rp']) ?>
                            </td>
                            <td>
                                <div style="display:flex;flex-direction:column;gap:4px;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;font-size:11px;font-family:var(--font-mono);">
                                        <span style="font-weight:700;color:var(--color-ink-secondary);"><?= $pct ?>%</span>
                                        <span style="color:var(--color-ink-mute);font-size:10px;">dari total</span>
                                    </div>
                                    <div style="width:100%;height:5px;background:var(--color-hairline);border-radius:99px;overflow:hidden;">
                                        <div style="width:<?= min(100, $pct) ?>%;height:100%;background:#ef4444;border-radius:99px;"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- TABLE LAPORAN KERUGIAN RUSAK -->
        <?php if (empty($losses)): ?>
            <div class="kr-empty-box">
                <div class="kr-empty-icon">
                    <i data-lucide="check-circle-2" style="width:28px;height:28px;"></i>
                </div>
                <h3 class="text-base sm:text-lg font-bold" style="color:var(--color-ink);">Nihil Kerugian Barang Rusak</h3>
                <p class="text-xs sm:text-sm mt-1.5 max-w-md mx-auto" style="color:var(--color-ink-secondary);line-height:1.5;">
                    Tidak ditemukan data barang retur rusak atau bocor pada rentang filter tanggal yang dipilih.
                </p>
                <div class="kr-empty-badges">
                    <span class="kr-empty-pill">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-emerald-500"></i>
                        <span>Kondisi Stok Prima</span>
                    </span>
                    <span class="kr-empty-pill">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-sky-500"></i>
                        <span>Rp 0 Kerugian Rusak</span>
                    </span>
                </div>
            </div>
        <?php else: ?>
            <div class="card p-0 rounded-2xl overflow-hidden" style="border:1px solid var(--color-hairline);">
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="min-width:180px;">Tanggal &amp; No. Kunjungan</th>
                                <th style="min-width:200px;">Toko Mitra</th>
                                <th style="min-width:130px;">Sales PIC</th>
                                <th style="min-width:200px;">Item Produk</th>
                                <th class="cell-center" style="width:110px;">Qty Rusak</th>
                                <th class="cell-right" style="width:130px;">HPP Satuan</th>
                                <th class="cell-right" style="width:150px;">Nilai Kerugian</th>
                                <th class="cell-center" style="width:90px;">Struk</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($losses as $loss): ?>
                            <tr>
                                <!-- Tanggal & Kunjungan (2-Line) -->
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:3px;">
                                        <div style="font-weight:800;color:var(--color-ink);font-size:13px;display:flex;align-items:center;gap:5px;">
                                            <i data-lucide="calendar" class="w-3.5 h-3.5" style="color:var(--color-ink-mute);"></i>
                                            <span><?= date('d/m/Y', strtotime($loss['tanggal_kunjungan'])) ?></span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="badge badge-mono font-mono" style="font-size:10.5px;letter-spacing:0.02em;">
                                                <?= htmlspecialchars($loss['nomor_kunjungan']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Toko Mitra & Kode (2-Line) -->
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:3px;">
                                        <strong style="color:var(--color-ink);font-size:13px;">
                                            <?= htmlspecialchars($loss['nama_toko']) ?>
                                        </strong>
                                        <?php if (!empty($loss['kode_pelanggan'])): ?>
                                        <div class="flex items-center gap-1.5">
                                            <span style="font-size:10px;font-weight:800;color:#0284c7;background:rgba(2,132,199,0.08);padding:1px 6px;border-radius:4px;border:1px solid rgba(2,132,199,0.18);font-family:var(--font-mono);">
                                                <?= htmlspecialchars($loss['kode_pelanggan']) ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Sales PIC -->
                                <td style="color:var(--color-ink-secondary);font-size:12px;font-weight:600;">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="user" class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--color-ink-mute);"></i>
                                        <span class="truncate"><?= htmlspecialchars($loss['nama_sales'] ?? 'Sales Driver') ?></span>
                                    </div>
                                </td>

                                <!-- Item Produk & SKU (2-Line) -->
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:3px;">
                                        <strong style="color:var(--color-ink);font-size:13px;">
                                            <?= htmlspecialchars($loss['nama_item']) ?>
                                        </strong>
                                        <div class="flex items-center gap-1.5">
                                            <span class="badge badge-mono font-mono" style="font-size:10px;">
                                                <?= htmlspecialchars($loss['kode_sku'] ?? 'NO-SKU') ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Qty Rusak -->
                                <td class="cell-center">
                                    <span class="badge" style="background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.25);font-weight:800;font-size:12px;font-family:var(--font-mono);">
                                        <?= (int)$loss['retur_rusak'] ?> <?= $loss['satuan_dasar'] ?>
                                    </span>
                                </td>

                                <!-- HPP Satuan -->
                                <td class="cell-right cell-currency" style="color:var(--color-ink-secondary);font-size:12.5px;">
                                    <?php if ((float)$loss['harga_pokok_satuan'] <= 0): ?>
                                        <span class="badge badge-warning" style="font-size:10.5px;">
                                            <i data-lucide="alert-circle" class="w-3 h-3 inline"></i> Rp 0
                                        </span>
                                    <?php else: ?>
                                        <?= Format::rupiah((float)$loss['harga_pokok_satuan']) ?>
                                    <?php endif; ?>
                                </td>

                                <!-- Nilai Kerugian -->
                                <td class="cell-right cell-currency" style="font-weight:900;color:#ef4444;font-size:13.5px;">
                                    <?= Format::rupiah((float)$loss['nilai_kerugian_rusak']) ?>
                                </td>

                                <!-- Aksi Struk -->
                                <td class="cell-center">
                                    <?php if (!empty($loss['kunjungan_id'])): ?>
                                    <a href="<?= Router::url('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)$loss['kunjungan_id']) . '&ref=kerugian') ?>" 
                                       class="btn btn-secondary btn-sm"
                                       style="padding:4px 9px;font-size:11.5px;font-weight:700;border-radius:8px;display:inline-flex;align-items:center;gap:4px;"
                                       title="Buka struk bukti opname kunjungan ini">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>Struk</span>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-slate-400">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:var(--color-canvas-soft);border-top:2px solid var(--color-hairline);">
                                <td colspan="4" style="padding:14px 18px;font-weight:800;color:var(--color-ink);font-size:12.5px;">
                                    TOTAL KERUGIAN RUSAK:
                                </td>
                                <td class="cell-center" style="padding:14px 18px;font-weight:900;color:#ef4444;font-size:13px;font-family:var(--font-mono);">
                                    <?= number_format((float)$totalPcsRusak) ?> pcs
                                </td>
                                <td class="cell-right" style="padding:14px 18px;color:var(--color-ink-mute);">—</td>
                                <td class="cell-right cell-currency" style="padding:14px 18px;font-weight:900;color:#ef4444;font-size:15px;">
                                    <?= Format::rupiah((float)$totalLossNominal) ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- TAB 2 CONTENT: BARANG HILANG DI TOKO (DITANGGUHKAN / GANTUNG) -->
    <div x-show="activeTab === 'hilang'" style="display:none;" class="space-y-5">
        <!-- POLICY EXPLAINER CARD (SOP REKONSILIASI SELISIH RAK) -->
        <div class="kr-sop-box">
            <div class="kr-sop-header">
                <div class="kr-sop-title">
                    <i data-lucide="shield-alert" class="w-4 h-4 text-amber-600"></i>
                    <span>SOP Rekonsiliasi Selisih Rak (Ditangguhkan / Saldo Gantung)</span>
                </div>
                <span class="kr-sop-badge">Audit Internal &bull; Bebas Tagihan Mitra</span>
            </div>
            
            <div class="kr-sop-grid">
                <div class="kr-sop-item">
                    <div class="kr-sop-item-icon" style="background:rgba(245,158,11,0.15);color:#d97706;">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <strong class="font-bold block text-[12.5px]" style="color:var(--color-ink);">Tidak Ditagihkan ke Toko Mitra</strong>
                        <span class="text-xs block mt-0.5" style="color:var(--color-ink-secondary);">
                            Barang hilang saat opname fisik tidak dibebankan ke invoice toko mitra. Nilai kerugian dihitung murni berdasarkan HPP resmi produk sebagai estimasi potensi risiko internal.
                        </span>
                    </div>
                </div>

                <div class="kr-sop-item">
                    <div class="kr-sop-item-icon" style="background:rgba(2,132,199,0.15);color:#0284c7;">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <strong class="font-bold block text-[12.5px]" style="color:var(--color-ink);">Pemulihan Otomatis (Auto-Reconcile)</strong>
                        <span class="text-xs block mt-0.5" style="color:var(--color-ink-secondary);">
                            Saldo hilang gantung akan <strong>otomatis pulih/berkurang</strong> jika pada kunjungan opname rak berikutnya barang tersebut ditemukan kembali oleh sales/driver.
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($pendingLosses)): ?>
            <div class="kr-empty-box">
                <div class="kr-empty-icon">
                    <i data-lucide="shield-check" style="width:30px;height:30px;"></i>
                </div>
                <h3 class="text-base sm:text-lg font-bold" style="color:var(--color-ink);">Seluruh Rak Konsinyasi Terkontrol Seimbang</h3>
                <p class="text-xs sm:text-sm mt-1.5 max-w-md mx-auto" style="color:var(--color-ink-secondary);line-height:1.5;">
                    Tidak ada saldo barang hilang atau selisih fisik yang sedang menggantung di seluruh jaringan toko mitra konsinyasi.
                </p>

                <div class="kr-empty-badges">
                    <span class="kr-empty-pill">
                        <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                        <span>100% Rak Fisik Sinkron</span>
                    </span>
                    <span class="kr-empty-pill">
                        <i data-lucide="store" class="w-3.5 h-3.5 text-sky-500"></i>
                        <span>0 Toko Berselisih</span>
                    </span>
                    <span class="kr-empty-pill">
                        <i data-lucide="package-check" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span>0 Pcs Saldo Gantung</span>
                    </span>
                </div>

                <div class="mt-5 flex items-center gap-2.5 flex-wrap justify-center">
                    <a href="<?= Router::url('/consignment/stok-rak') ?>" 
                       class="btn btn-secondary btn-sm flex items-center gap-1.5" 
                       style="border-radius:10px;font-weight:700;padding:7px 14px;">
                        <i data-lucide="boxes" class="w-4 h-4 text-sky-600"></i>
                        <span>Cek Daftar Stok Rak</span>
                    </a>
                    <a href="<?= Router::url('/consignment/riwayat-kunjungan') ?>" 
                       class="btn btn-secondary btn-sm flex items-center gap-1.5" 
                       style="border-radius:10px;font-weight:700;padding:7px 14px;">
                        <i data-lucide="history" class="w-4 h-4 text-emerald-600"></i>
                        <span>Lihat Riwayat Kunjungan</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- TOOLBAR: SEARCH & VIEW SWITCHER -->
            <div class="kr-toolbar-card">
                <div class="flex items-center gap-2 flex-1 min-w-[240px]">
                    <div class="relative w-full max-w-sm">
                        <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2" style="color:var(--color-ink-mute);"></i>
                        <input type="text" 
                               x-model="searchHilang" 
                               placeholder="Cari toko, kode pelanggan, atau produk..." 
                               class="form-input w-full text-xs pl-9 pr-3" 
                               style="height:38px;border-radius:10px;">
                    </div>
                    <button type="button" 
                            x-show="searchHilang" 
                            @click="searchHilang = ''" 
                            class="text-xs font-semibold text-slate-400 hover:text-slate-600 px-2 py-1 cursor-pointer">
                        Reset
                    </button>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="text-xs font-bold" style="color:var(--color-ink-mute);">Tampilan:</span>
                    <div class="kr-view-nav">
                        <button type="button" 
                                @click="viewModeHilang = 'store'; $nextTick(() => window.lucide && window.lucide.createIcons());"
                                class="kr-view-btn"
                                :class="{ 'is-active': viewModeHilang === 'store' }">
                            <i data-lucide="store" class="w-3.5 h-3.5"></i>
                            <span>Rekap Toko (<?= count($pendingLossByStore) ?>)</span>
                        </button>
                        <button type="button" 
                                @click="viewModeHilang = 'sku'; $nextTick(() => window.lucide && window.lucide.createIcons());"
                                class="kr-view-btn"
                                :class="{ 'is-active': viewModeHilang === 'sku' }">
                            <i data-lucide="list" class="w-3.5 h-3.5"></i>
                            <span>Rincian SKU (<?= count($pendingLosses) ?>)</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- VIEW 1: REKAP PER TOKO (GROUPED ACCORDION VIEW) -->
            <div x-show="viewModeHilang === 'store'" class="space-y-3.5">
                <?php foreach ($pendingLossByStore as $st): ?>
                <?php 
                    $storeSearchKeywords = strtolower($st['nama_toko'] . ' ' . $st['kode_pelanggan'] . ' ' . $st['nama_sales'] . ' ' . implode(' ', array_column($st['items'], 'nama_item')));
                ?>
                <div class="kr-store-card" 
                     x-show="!searchHilang || '<?= addslashes($storeSearchKeywords) ?>'.includes(searchHilang.toLowerCase())">
                    
                    <!-- Store Summary Header -->
                    <div class="p-4 sm:p-5 flex items-center justify-between gap-4 flex-wrap cursor-pointer hover:bg-slate-500/5 transition-colors"
                         @click="toggleStore('<?= $st['pelanggan_id'] ?>')">
                        
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div class="kr-store-icon">
                                <i data-lucide="store" class="w-5 h-5"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="font-bold text-sm sm:text-base" style="color:var(--color-ink);">
                                        <?= htmlspecialchars($st['nama_toko']) ?>
                                    </h4>
                                    <?php if (!empty($st['kode_pelanggan'])): ?>
                                    <span style="font-size:10px;font-weight:800;color:#0284c7;background:rgba(2,132,199,0.08);padding:1px 6px;border-radius:4px;border:1px solid rgba(2,132,199,0.18);font-family:var(--font-mono);">
                                        <?= htmlspecialchars($st['kode_pelanggan']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs block mt-1" style="color:var(--color-ink-mute);">
                                    PIC: <strong class="text-slate-700 dark:text-slate-300"><?= htmlspecialchars($st['nama_sales'] ?? 'Sales Driver') ?></strong> • 
                                    <span class="text-amber-600 dark:text-amber-400 font-bold"><?= (int)$st['total_sku_hilang'] ?> SKU selisih</span>
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 sm:gap-6 flex-wrap ml-auto">
                            <div class="text-right">
                                <span class="text-[10.5px] uppercase font-bold tracking-wider block" style="color:var(--color-ink-mute);">Fisik Hilang:</span>
                                <span class="font-black text-sm sm:text-base text-amber-600 dark:text-amber-400 font-mono">
                                    -<?= number_format((float)$st['total_pcs_hilang']) ?> pcs
                                </span>
                            </div>

                            <div class="text-right">
                                <span class="text-[10.5px] uppercase font-bold tracking-wider block text-amber-600 dark:text-amber-400">Potensi HPP Gantung:</span>
                                <span class="font-black text-sm sm:text-base text-amber-600 dark:text-amber-400 font-mono">
                                    <?= Format::rupiah((float)$st['total_nilai_hpp']) ?>
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="<?= Router::url('/consignment/opname?pelanggan_id=' . urlencode((string)$st['pelanggan_id'])) ?>" 
                                   @click.stop
                                   class="btn btn-secondary btn-sm py-1.5 px-3 rounded-xl text-xs font-bold flex items-center gap-1.5"
                                   style="height:36px;border-radius:10px;"
                                   title="Buka form opname rak toko ini">
                                    <i data-lucide="clipboard-check" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Opname Rak</span>
                                </a>

                                <button type="button" 
                                        class="p-2 rounded-xl text-slate-400 hover:text-slate-600 transition-transform duration-200"
                                        :class="{ 'rotate-180': expandedStores['<?= $st['pelanggan_id'] ?>'] }">
                                    <i data-lucide="chevron-down" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Expandable Item Breakdown -->
                    <div x-show="expandedStores['<?= $st['pelanggan_id'] ?>']" 
                         style="display:none;background:var(--color-canvas-soft);border-color:var(--color-hairline);" 
                         class="border-t p-0">
                        
                        <div class="table-scroll">
                            <table class="data-table" style="font-size:12px;">
                                <thead>
                                    <tr>
                                        <th style="min-width:220px;">Item Produk &amp; SKU</th>
                                        <th class="cell-center" style="width:130px;">Stok Titip Saat Ini</th>
                                        <th class="cell-center" style="width:140px;">Hilang (Gantung)</th>
                                        <th class="cell-right" style="width:140px;">HPP Satuan</th>
                                        <th class="cell-right" style="width:160px;">Potensi Kerugian HPP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($st['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <strong style="color:var(--color-ink);font-size:12.5px;"><?= htmlspecialchars($item['nama_item']) ?></strong>
                                            <span class="badge badge-mono font-mono text-[10px] block mt-0.5" style="width:fit-content;"><?= htmlspecialchars($item['kode_sku'] ?? 'NO-SKU') ?></span>
                                        </td>
                                        <td class="cell-center font-mono font-bold" style="color:var(--color-ink-secondary);">
                                            <?= (int)$item['stok_titip_saat_ini'] ?> <?= $item['satuan_dasar'] ?>
                                        </td>
                                        <td class="cell-center">
                                            <span class="badge" style="background:rgba(245,158,11,0.12);color:#d97706;font-weight:900;font-size:12px;font-family:var(--font-mono);">
                                                -<?= (int)$item['stok_hilang_pending'] ?> <?= $item['satuan_dasar'] ?>
                                            </span>
                                        </td>
                                        <td class="cell-right cell-currency" style="color:var(--color-ink-secondary);">
                                            <?= Format::rupiah((float)$item['hpp']) ?>
                                        </td>
                                        <td class="cell-right cell-currency font-black text-amber-600 dark:text-amber-400" style="font-size:13px;">
                                            <?= Format::rupiah((float)$item['nilai_hpp_hilang']) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

            <!-- VIEW 2: FLAT SKU TABLE -->
            <div x-show="viewModeHilang === 'sku'" style="display:none;border:1px solid var(--color-hairline);" class="card p-0 rounded-xl overflow-hidden">
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="min-width:200px;">Toko Mitra</th>
                                <th style="min-width:130px;">Sales PIC</th>
                                <th style="min-width:200px;">Item Produk &amp; SKU</th>
                                <th class="cell-center" style="width:120px;">Stok Titip</th>
                                <th class="cell-center" style="width:130px;">Hilang (Gantung)</th>
                                <th class="cell-right" style="width:130px;">HPP Satuan</th>
                                <th class="cell-right" style="width:150px;">Nilai HPP Gantung</th>
                                <th class="cell-center" style="width:100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingLosses as $pl): ?>
                            <?php 
                                $plKeywords = strtolower($pl['nama_toko'] . ' ' . $pl['kode_pelanggan'] . ' ' . $pl['nama_item'] . ' ' . $pl['kode_sku'] . ' ' . $pl['nama_sales']);
                            ?>
                            <tr x-show="!searchHilang || '<?= addslashes($plKeywords) ?>'.includes(searchHilang.toLowerCase())">
                                <!-- Toko & Kode (2-Line) -->
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:3px;">
                                        <strong style="color:var(--color-ink);font-size:13px;">
                                            <?= htmlspecialchars($pl['nama_toko']) ?>
                                        </strong>
                                        <?php if (!empty($pl['kode_pelanggan'])): ?>
                                        <div class="flex items-center gap-1.5">
                                            <span style="font-size:10px;font-weight:800;color:#0284c7;background:rgba(2,132,199,0.08);padding:1px 6px;border-radius:4px;border:1px solid rgba(2,132,199,0.18);font-family:var(--font-mono);">
                                                <?= htmlspecialchars($pl['kode_pelanggan']) ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Sales PIC -->
                                <td style="color:var(--color-ink-secondary);font-size:12px;font-weight:600;">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="user" class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--color-ink-mute);"></i>
                                        <span class="truncate"><?= htmlspecialchars($pl['nama_sales'] ?? 'Sales Driver') ?></span>
                                    </div>
                                </td>

                                <!-- Item Produk & SKU (2-Line) -->
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:3px;">
                                        <strong style="color:var(--color-ink);font-size:13px;">
                                            <?= htmlspecialchars($pl['nama_item']) ?>
                                        </strong>
                                        <div class="flex items-center gap-1.5">
                                            <span class="badge badge-mono font-mono" style="font-size:10px;">
                                                <?= htmlspecialchars($pl['kode_sku'] ?? 'NO-SKU') ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Stok Titip Saat Ini -->
                                <td class="cell-center font-mono font-bold" style="color:var(--color-ink-secondary);">
                                    <?= (int)$pl['stok_titip_saat_ini'] ?> <?= $pl['satuan_dasar'] ?>
                                </td>

                                <!-- Hilang (Gantung) -->
                                <td class="cell-center">
                                    <span class="badge" style="background:rgba(245,158,11,0.12);color:#d97706;font-weight:900;font-size:12px;font-family:var(--font-mono);">
                                        -<?= (int)$pl['stok_hilang_pending'] ?> <?= $pl['satuan_dasar'] ?>
                                    </span>
                                </td>

                                <!-- HPP Satuan -->
                                <td class="cell-right cell-currency" style="color:var(--color-ink-secondary);font-size:12.5px;">
                                    <?= Format::rupiah((float)$pl['hpp']) ?>
                                </td>

                                <!-- Nilai HPP Gantung -->
                                <td class="cell-right cell-currency font-black text-amber-600 dark:text-amber-400" style="font-size:13.5px;">
                                    <?= Format::rupiah((float)$pl['nilai_hpp_hilang']) ?>
                                </td>

                                <!-- Aksi Opname -->
                                <td class="cell-center">
                                    <a href="<?= Router::url('/consignment/opname?pelanggan_id=' . urlencode((string)$pl['pelanggan_id'])) ?>" 
                                       class="btn btn-secondary btn-sm"
                                       style="padding:4px 9px;font-size:11.5px;font-weight:700;border-radius:8px;display:inline-flex;align-items:center;gap:4px;"
                                       title="Buka form opname rak toko ini">
                                        <i data-lucide="clipboard-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        <span>Opname</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:var(--color-canvas-soft);border-top:2px solid var(--color-hairline);">
                                <td colspan="4" style="padding:14px 18px;font-weight:800;color:var(--color-ink);font-size:12.5px;">
                                    TOTAL POTENSI HILANG GANTUNG:
                                </td>
                                <td class="cell-center" style="padding:14px 18px;font-weight:900;color:#d97706;font-size:13px;font-family:var(--font-mono);">
                                    -<?= number_format((float)$totalPcsPendingLoss) ?> pcs
                                </td>
                                <td class="cell-right" style="padding:14px 18px;color:var(--color-ink-mute);">—</td>
                                <td class="cell-right cell-currency" style="padding:14px 18px;font-weight:900;color:#d97706;font-size:15px;">
                                    <?= Format::rupiah((float)$totalNominalPendingLoss) ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function kerugianRusakApp() {
    return {
        activeTab: '<?= $initialTab ?>',
        searchHilang: '',
        viewModeHilang: 'store', // 'store' | 'sku'
        expandedStores: {},
        toggleStore(storeId) {
            this.expandedStores[storeId] = !this.expandedStores[storeId];
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        },
        setTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        },
        getExportUrl() {
            return this.activeTab === 'hilang' ? '<?= $exportExcelUrlHilang ?>' : '<?= $exportExcelUrlRusak ?>';
        },
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

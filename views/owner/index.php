<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<style>
@media print {
    .page-header-actions,
    .no-scrollbar,
    .app-sidebar,
    .app-header,
    .mobile-bottom-bar,
    form,
    button,
    .btn {
        display: none !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #e2e8f0 !important;
        break-inside: avoid;
    }
    body {
        background: #fff !important;
        color: #000 !important;
    }
}
</style>

<div x-data="{ 
    activeTab: '<?= htmlspecialchars($activeTab ?? 'finance') ?>', 
    filterPreset: '<?= htmlspecialchars($preset ?? 'this_month') ?>',
    filterStart: '<?= htmlspecialchars($startDate ?? '') ?>',
    filterEnd: '<?= htmlspecialchars($endDate ?? '') ?>',
    setPreset(p) {
        this.filterPreset = p;
        const form = this.$refs.filterForm;
        form.querySelector('input[name=preset]').value = p;
        form.querySelector('input[name=tab]').value = this.activeTab;
        form.submit();
    }
}" class="space-y-5 pb-20">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER (Clean Standard Responsive Layout)                         -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-amber">
                <i data-lucide="crown"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#f59e0b;"></span>
                    <span>Executive Hub</span>
                    <span style="display:inline-flex;align-items:center;gap:4px;background:rgba(16,185,129,0.12);color:var(--color-success);padding:2px 7px;border-radius:20px;font-size:9px;font-weight:700;letter-spacing:0.04em;border:1px solid rgba(16,185,129,0.2);">
                        <span style="width:5px;height:5px;border-radius:50%;background:var(--color-success);animation:pulse 1.5s infinite;flex-shrink:0;"></span>
                        KESEHATAN BISNIS
                    </span>
                </div>
                <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Owner Executive Dashboard') ?></h1>
                <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle ?? 'Pusat Analisis Performa Finansial & Bisnis Perusahaan') ?></p>
            </div>
        </div>

        <!-- Quick Top Links -->
        <div class="page-header-actions" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;" title="Cetak atau Simpan PDF Laporan Eksekutif">
                <i data-lucide="printer" style="width:14px;height:14px;color:var(--color-ink);"></i>
                <span>Cetak Ringkasan</span>
            </button>
            <a href="<?= Router::url('/cash/reports') ?>" class="btn btn-secondary btn-sm" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;">
                <i data-lucide="wallet" style="width:14px;height:14px;color:#3b82f6;"></i>
                <span>Laporan Arus Kas</span>
            </a>
            <a href="<?= Router::url('/inventory') ?>" class="btn btn-secondary btn-sm" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;">
                <i data-lucide="boxes" style="width:14px;height:14px;color:#10b981;"></i>
                <span>Gudang Stok</span>
            </a>
            <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary btn-sm" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;">
                <i data-lucide="store" style="width:14px;height:14px;color:#f59e0b;"></i>
                <span>Konsinyasi Hub</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. FILTER PERIODE DINAMIS & PRESET CEPAT                                  -->
    <!-- ========================================================================= -->
    <div class="card p-3.5 sm:p-4 rounded-xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <form x-ref="filterForm" method="GET" action="<?= Router::url('/owner') ?>" style="display:flex;flex-direction:column;gap:12px;">
            <input type="hidden" name="preset" :value="filterPreset">
            <input type="hidden" name="tab" :value="activeTab">

            <!-- Baris Preset Periode Cepat -->
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding-bottom:10px;border-bottom:1px solid var(--color-hairline);">
                <div style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:800;color:var(--color-ink);">
                    <i data-lucide="calendar-range" style="width:15px;height:15px;color:var(--color-primary);"></i>
                    <span>Filter Periode Evaluasi:</span>
                    <span class="badge badge-primary font-mono text-[10.5px]" style="padding:2px 8px;">
                        <?= htmlspecialchars($periodLabel ?? 'Bulan Ini') ?>
                    </span>
                </div>

                <!-- Preset Buttons -->
                <div class="grid grid-cols-3 sm:flex gap-1.5 w-full sm:w-auto">
                    <button type="button" @click="setPreset('today')" 
                            :class="filterPreset === 'today' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                            style="padding:4px 10px;font-size:11px;font-weight:700;border-radius:6px;border:1px solid var(--color-hairline);justify-content:center;">
                        Hari Ini
                    </button>
                    <button type="button" @click="setPreset('7days')" 
                            :class="filterPreset === '7days' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                            style="padding:4px 10px;font-size:11px;font-weight:700;border-radius:6px;border:1px solid var(--color-hairline);justify-content:center;">
                        7 Hari Terakhir
                    </button>
                    <button type="button" @click="setPreset('this_month')" 
                            :class="filterPreset === 'this_month' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                            style="padding:4px 10px;font-size:11px;font-weight:700;border-radius:6px;border:1px solid var(--color-hairline);justify-content:center;">
                        Bulan Ini
                    </button>
                    <button type="button" @click="setPreset('last_month')" 
                            :class="filterPreset === 'last_month' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                            style="padding:4px 10px;font-size:11px;font-weight:700;border-radius:6px;border:1px solid var(--color-hairline);justify-content:center;">
                        Bulan Lalu
                    </button>
                    <button type="button" @click="setPreset('this_year')" 
                            :class="filterPreset === 'this_year' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                            style="padding:4px 10px;font-size:11px;font-weight:700;border-radius:6px;border:1px solid var(--color-hairline);justify-content:center;">
                        Tahun Ini
                    </button>
                </div>
            </div>

            <!-- Custom Date Range Form -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-3 items-end">
                <div>
                    <label class="form-label" style="font-size:10.5px;font-weight:700;margin-bottom:3px;display:block;">Mulai Tanggal</label>
                    <input type="date" name="start_date" x-model="filterStart" @change="filterPreset = 'custom'" class="form-input" style="height:36px;font-size:12px;width:100%;">
                </div>

                <div>
                    <label class="form-label" style="font-size:10.5px;font-weight:700;margin-bottom:3px;display:block;">Sampai Tanggal</label>
                    <input type="date" name="end_date" x-model="filterEnd" @change="filterPreset = 'custom'" class="form-input" style="height:36px;font-size:12px;width:100%;">
                </div>

                <div class="col-span-2 sm:col-span-1">
                    <button type="submit" class="btn btn-primary" style="height:36px;width:100%;font-weight:700;display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                        <i data-lucide="filter" style="width:14px;height:14px;"></i>
                        <span>Terapkan Rentang</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. TAB SWITCHER EKSEKUTIF (4 PILAR PERFORMA PERUSAHAAN)                    -->
    <!-- ========================================================================= -->
    <div class="no-scrollbar" style="display:flex;gap:8px;padding-bottom:4px;overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <button type="button" 
                @click="activeTab = 'finance'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                :class="activeTab === 'finance' ? 'btn btn-primary' : 'btn btn-secondary'"
                style="display:flex;align-items:center;gap:8px;font-weight:800;padding:8px 16px;border-radius:10px;font-size:12.5px;white-space:nowrap;flex-shrink:0;">
            <i data-lucide="trending-up" style="width:15px;height:15px;"></i>
            <span>Laba Rugi &amp; Modal Kerja</span>
        </button>

        <button type="button" 
                @click="activeTab = 'sales'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                :class="activeTab === 'sales' ? 'btn btn-primary' : 'btn btn-secondary'"
                style="display:flex;align-items:center;gap:8px;font-weight:800;padding:8px 16px;border-radius:10px;font-size:12.5px;white-space:nowrap;flex-shrink:0;">
            <i data-lucide="shopping-bag" style="width:15px;height:15px;"></i>
            <span>Channel Penjualan &amp; Top SKU</span>
        </button>

        <button type="button" 
                @click="activeTab = 'factory'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                :class="activeTab === 'factory' ? 'btn btn-primary' : 'btn btn-secondary'"
                style="display:flex;align-items:center;gap:8px;font-weight:800;padding:8px 16px;border-radius:10px;font-size:12.5px;white-space:nowrap;flex-shrink:0;">
            <i data-lucide="factory" style="width:15px;height:15px;"></i>
            <span>Pabrik &amp; Produksi Snack</span>
            <span class="badge font-mono text-[9.5px] px-1.5 py-0.2" style="background:rgba(59,130,246,0.15);color:#2563eb;">
                <?= number_format((int)($productionSummary['total_pcs'] ?? 0)) ?> pcs
            </span>
        </button>

        <button type="button" 
                @click="activeTab = 'consignment'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                :class="activeTab === 'consignment' ? 'btn btn-primary' : 'btn btn-secondary'"
                style="display:flex;align-items:center;gap:8px;font-weight:800;padding:8px 16px;border-radius:10px;font-size:12.5px;white-space:nowrap;flex-shrink:0;">
            <i data-lucide="store" style="width:15px;height:15px;"></i>
            <span>Mitra Konsinyasi &amp; Sales</span>
            <?php if (!empty($overdueStores)): ?>
            <span class="badge badge-warning text-[9.5px] px-1.5 py-0.2"><?= count($overdueStores) ?> Overdue</span>
            <?php endif; ?>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: FINANSIAL, PROFITABILITAS & MODAL KERJA                            -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'finance'" x-cloak class="space-y-5">

        <!-- 5 KPI STAT CARDS (STANDAR LABA RUGI & ARUS KAS) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            
            <!-- 1. Omzet Penjualan Bersih -->
            <div class="card p-3.5 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-primary);border-radius:14px;box-shadow:var(--shadow-1);">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">1. Omzet Riil</span>
                    <i data-lucide="receipt" style="width:15px;height:15px;color:var(--color-primary);"></i>
                </div>
                <div class="font-mono" style="font-size:clamp(14px, 2.8vw, 18px);font-weight:900;color:var(--color-ink);line-height:1.2;">
                    <?= Format::rupiah($totalOmzet) ?>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                    <span><?= number_format($totalTransaksi) ?> Transaksi</span>
                    <span style="color:var(--color-success);font-weight:700;">Hari ini: <?= Format::rupiah($omzetToday) ?></span>
                </div>
            </div>

            <!-- 2. HPP Barang Terjual (COGS) -->
            <div class="card p-3.5 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid #64748b;border-radius:14px;box-shadow:var(--shadow-1);">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">2. HPP Terjual</span>
                    <i data-lucide="package" style="width:15px;height:15px;color:#64748b;"></i>
                </div>
                <div class="font-mono" style="font-size:clamp(14px, 2.8vw, 18px);font-weight:900;color:#64748b;line-height:1.2;">
                    <?= Format::rupiah($totalHpp) ?>
                </div>
                <div style="font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                    <span>Bahan baku + Kemas</span>
                </div>
            </div>

            <!-- 3. Laba Kotor & Margin -->
            <div class="card p-3.5 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:14px;box-shadow:var(--shadow-1);">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">3. Laba Kotor</span>
                    <span class="badge badge-success font-mono font-bold" style="font-size:10px;padding:1px 5px;">
                        <?= $marginLabaKotor ?>% Margin
                    </span>
                </div>
                <div class="font-mono" style="font-size:clamp(14px, 2.8vw, 18px);font-weight:900;color:var(--color-success);line-height:1.2;">
                    <?= Format::rupiah($labaKotor) ?>
                </div>
                <div style="font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                    <span>Omzet - HPP</span>
                </div>
            </div>

            <!-- 4. Beban Pengeluaran Operasional -->
            <div class="card p-3.5 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-danger);border-radius:14px;box-shadow:var(--shadow-1);">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">4. Beban Kas</span>
                    <i data-lucide="arrow-up-right" style="width:15px;height:15px;color:var(--color-danger);"></i>
                </div>
                <div class="font-mono" style="font-size:clamp(14px, 2.8vw, 18px);font-weight:900;color:var(--color-danger);line-height:1.2;">
                    <?= Format::rupiah($totalBebanOperasional) ?>
                </div>
                <div style="font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                    <span>Arus Kas Keluar Periode</span>
                </div>
            </div>

            <!-- 5. Estimasi Laba Bersih Operasional -->
            <div class="col-span-2 sm:col-span-1 card p-3.5 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid <?= $labaBersih >= 0 ? '#10b981' : '#ef4444' ?>;border-radius:14px;box-shadow:var(--shadow-1);">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">5. Laba Bersih</span>
                    <span class="badge <?= $labaBersih >= 0 ? 'badge-success' : 'badge-danger' ?> font-mono font-bold" style="font-size:10px;padding:1px 5px;">
                        <?= $marginLabaBersih ?>% Net
                    </span>
                </div>
                <div class="font-mono" style="font-size:clamp(14px, 2.8vw, 18px);font-weight:900;color:<?= $labaBersih >= 0 ? 'var(--color-success)' : 'var(--color-danger)' ?>;line-height:1.2;">
                    <?= Format::rupiah($labaBersih) ?>
                </div>
                <div style="font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                    <span>Laba Kotor - Beban Kas</span>
                </div>
            </div>

        </div>

        <!-- 2-PANE: NERACA MODAL KERJA & BREAKDOWN BEBAN -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            
            <!-- NERACA KESEHATAN MODAL KERJA BERSIH (2 KOLOM DESKTOP) -->
            <div class="lg:col-span-2 card p-4 sm:p-5 space-y-4" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center justify-between pb-3 border-b" style="border-color:var(--color-hairline);">
                    <div class="flex items-center gap-2.5">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(59,130,246,0.12);color:#3b82f6;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="scale" style="width:18px;height:18px;"></i>
                        </div>
                        <div>
                            <h2 style="font-size:14.5px;font-weight:800;color:var(--color-ink);">Neraca Modal Kerja Bersih (Net Working Capital)</h2>
                            <p style="font-size:11px;color:var(--color-ink-mute);">Live Snapshot: Likuiditas Aset Lancar Usaha vs Kewajiban Supplier</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="flex items-center gap-1.5 justify-end">
                            <span class="font-mono font-black text-sm sm:text-base" style="color:var(--color-primary);"><?= Format::rupiah($netWorkingCapital) ?></span>
                            <?php if ($netWorkingCapital > 0): ?>
                            <span class="badge badge-success font-mono text-[9px] px-1.5 py-0.5" title="Likuiditas Lancar: Aset lancar usaha mencukupi kewajiban jangka pendek">
                                SEHAT
                            </span>
                            <?php else: ?>
                            <span class="badge badge-danger font-mono text-[9px] px-1.5 py-0.5" title="Defisit Modal Kerja: Kewajiban hutang melebihi aset lancar likuid">
                                WASPADA
                            </span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:10px;color:var(--color-ink-mute);">Modal Kerja Bersih</div>
                    </div>
                </div>

                <!-- 4 KARTU ASET & KEWAJIBAN -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <!-- Kas & Bank -->
                    <div class="p-3 rounded-xl space-y-1" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex items-center justify-between">
                            <span style="font-size:10px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Kas &amp; Bank</span>
                            <i data-lucide="wallet" style="width:13px;height:13px;color:#3b82f6;"></i>
                        </div>
                        <div class="font-mono text-sm font-black" style="color:#3b82f6;"><?= Format::rupiah($totalKasLikuid) ?></div>
                        <div style="font-size:9.5px;color:var(--color-ink-mute);"><?= count($kasDetail) ?> Rekening Aktif</div>
                    </div>

                    <!-- Total Piutang -->
                    <div class="p-3 rounded-xl space-y-1" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex items-center justify-between">
                            <span style="font-size:10px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Piutang Toko</span>
                            <i data-lucide="clock" style="width:13px;height:13px;color:#f59e0b;"></i>
                        </div>
                        <div class="font-mono text-sm font-black" style="color:#f59e0b;"><?= Format::rupiah($totalPiutang) ?></div>
                        <div style="font-size:9.5px;color:var(--color-ink-mute);">Grosir &amp; Konsinyasi</div>
                    </div>

                    <!-- Valuasi Persediaan Total -->
                    <div class="p-3 rounded-xl space-y-1" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex items-center justify-between">
                            <span style="font-size:10px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Aset Persediaan</span>
                            <i data-lucide="boxes" style="width:13px;height:13px;color:var(--color-success);"></i>
                        </div>
                        <div class="font-mono text-sm font-black" style="color:var(--color-success);"><?= Format::rupiah($totalValuasiPersediaan) ?></div>
                        <div style="font-size:9.5px;color:var(--color-ink-mute);">Gudang + Rak Toko</div>
                    </div>

                    <!-- Hutang Supplier -->
                    <div class="p-3 rounded-xl space-y-1" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex items-center justify-between">
                            <span style="font-size:10px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Hutang Vendor</span>
                            <i data-lucide="alert-circle" style="width:13px;height:13px;color:var(--color-danger);"></i>
                        </div>
                        <div class="font-mono text-sm font-black" style="color:var(--color-danger);"><?= Format::rupiah($totalHutangPemasok) ?></div>
                        <div style="font-size:9.5px;color:var(--color-ink-mute);">Kewajiban Bahan Baku</div>
                    </div>
                </div>

                <!-- RINCIAN VALUASI PERSEDIAAN GUDANG VS RAK -->
                <div class="p-3 rounded-xl space-y-2" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <div style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">
                        Komposisi Valuasi Stok Persediaan (Berdasarkan HPP):
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                        <div class="p-2 rounded-lg" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                            <span style="color:var(--color-ink-mute);font-size:10px;">Bahan Mentah:</span>
                            <div class="font-bold font-mono" style="color:var(--color-ink);"><?= Format::rupiah($stokGudangBahan) ?></div>
                        </div>
                        <div class="p-2 rounded-lg" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                            <span style="color:var(--color-ink-mute);font-size:10px;">Bahan Kemas:</span>
                            <div class="font-bold font-mono" style="color:var(--color-ink);"><?= Format::rupiah($stokGudangKemas) ?></div>
                        </div>
                        <div class="p-2 rounded-lg" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                            <span style="color:var(--color-ink-mute);font-size:10px;">Barang Jadi Gudang:</span>
                            <div class="font-bold font-mono" style="color:var(--color-ink);"><?= Format::rupiah($stokGudangJadi) ?></div>
                        </div>
                        <div class="p-2 rounded-lg" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:2.5px solid #f59e0b;">
                            <span style="color:var(--color-warning);font-size:10px;font-weight:700;">Titipan Rak Toko:</span>
                            <div class="font-black font-mono" style="color:var(--color-warning);"><?= Format::rupiah($stokRakKonsinyasi) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BREAKDOWN PENGELUARAN OPERASIONAL TERBESAR -->
            <div class="card p-4 sm:p-5 space-y-3" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center justify-between pb-3 border-b" style="border-color:var(--color-hairline);">
                    <div class="flex items-center gap-2">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(239,68,68,0.12);color:var(--color-danger);display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="pie-chart" style="width:16px;height:16px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:13.5px;font-weight:800;color:var(--color-ink);">Beban Kas Terbesar</h3>
                            <div style="font-size:11px;color:var(--color-ink-mute);">Top 5 Pengeluaran Periode</div>
                        </div>
                    </div>
                    <span class="font-bold text-xs" style="color:var(--color-danger);"><?= Format::rupiah($totalBebanOperasional) ?></span>
                </div>

                <?php if (empty($expenseBreakdown)): ?>
                <div class="p-6 text-center text-xs" style="color:var(--color-ink-mute);">
                    Tidak ada transaksi beban keluar pada periode yang dipilih.
                </div>
                <?php else: ?>
                <div class="space-y-2 text-xs">
                    <?php foreach ($expenseBreakdown as $exp): 
                        $pct = ($totalBebanOperasional > 0) ? round(((float)$exp['total_nominal'] / $totalBebanOperasional) * 100, 1) : 0;
                    ?>
                    <div class="p-2.5 rounded-xl space-y-1.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-center">
                            <span style="font-weight:700;color:var(--color-ink);text-transform:capitalize;">
                                <?= htmlspecialchars(str_replace('_', ' ', (string)$exp['kategori'])) ?>
                            </span>
                            <span class="font-mono font-bold" style="color:var(--color-danger);">
                                <?= Format::rupiah((float)$exp['total_nominal']) ?>
                            </span>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="flex:1;height:5px;background:rgba(0,0,0,0.06);border-radius:10px;overflow:hidden;">
                                <div style="width:<?= $pct ?>%;height:100%;background:var(--color-danger);border-radius:10px;"></div>
                            </div>
                            <span style="font-size:10px;color:var(--color-ink-mute);font-weight:600;min-width:32px;text-align:right;"><?= $pct ?>%</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- AGING PIUTANG KONSOLIDASI & TOP TOKO MENUNGGAK -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            
            <!-- AGING BREAKDOWN KONSOLIDASI (2 KOLOM) -->
            <div class="lg:col-span-2 card p-4 sm:p-5 space-y-4" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center justify-between pb-3 border-b" style="border-color:var(--color-hairline);">
                    <div class="flex items-center gap-2">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="hourglass" style="width:16px;height:16px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:14px;font-weight:800;color:var(--color-ink);">Aging Piutang Usaha Konsolidasi (Grosir &amp; Konsinyasi)</h3>
                            <div style="font-size:11px;color:var(--color-ink-mute);">Klasifikasi Berdasarkan Hari Jatuh Tempo Nota</div>
                        </div>
                    </div>
                    <span class="font-mono font-bold text-xs" style="color:var(--color-warning);">
                        Total: <?= Format::rupiah((float)($agingSummary['total_outstanding'] ?? 0)) ?>
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <!-- Lancar -->
                    <div class="p-3 rounded-xl space-y-1" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3px solid var(--color-success);">
                        <div style="font-size:10px;font-weight:700;color:var(--color-success);">Lancar (&le; Jatuh Tempo)</div>
                        <div class="font-mono text-xs sm:text-sm font-black" style="color:var(--color-ink);">
                            <?= Format::rupiah((float)($agingSummary['piutang_lancar'] ?? 0)) ?>
                        </div>
                        <div style="font-size:9.5px;color:var(--color-ink-mute);">Aman &amp; Tertib</div>
                    </div>

                    <!-- Overdue 1-14 -->
                    <div class="p-3 rounded-xl space-y-1" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3px solid #eab308;">
                        <div style="font-size:10px;font-weight:700;color:#eab308;">Overdue 1–14 Hari</div>
                        <div class="font-mono text-xs sm:text-sm font-black" style="color:var(--color-ink);">
                            <?= Format::rupiah((float)($agingSummary['overdue_1_14'] ?? 0)) ?>
                        </div>
                        <div style="font-size:9.5px;color:var(--color-ink-mute);">Perlu Follow Up</div>
                    </div>

                    <!-- Overdue 15-30 -->
                    <div class="p-3 rounded-xl space-y-1" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3px solid var(--color-warning);">
                        <div style="font-size:10px;font-weight:700;color:var(--color-warning);">Overdue 15–30 Hari</div>
                        <div class="font-mono text-xs sm:text-sm font-black" style="color:var(--color-ink);">
                            <?= Format::rupiah((float)($agingSummary['overdue_15_30'] ?? 0)) ?>
                        </div>
                        <div style="font-size:9.5px;color:var(--color-ink-mute);">Perhatian Khusus</div>
                    </div>

                    <!-- Overdue > 30 -->
                    <div class="p-3 rounded-xl space-y-1" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3px solid var(--color-danger);">
                        <div style="font-size:10px;font-weight:700;color:var(--color-danger);">&gt; 30 Hari (Kritis)</div>
                        <div class="font-mono text-xs sm:text-sm font-black" style="color:var(--color-danger);">
                            <?= Format::rupiah((float)($agingSummary['overdue_over_30'] ?? 0)) ?>
                        </div>
                        <div style="font-size:9.5px;color:var(--color-ink-mute);">Potensi Macet</div>
                    </div>
                </div>
            </div>

            <!-- TOP 5 TOKO DENGAN PIUTANG TERBESAR -->
            <div class="card p-4 sm:p-5 space-y-3" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center justify-between pb-3 border-b" style="border-color:var(--color-hairline);">
                    <div class="flex items-center gap-2">
                        <div style="width:30px;height:30px;border-radius:8px;background:rgba(239,68,68,0.1);color:var(--color-danger);display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="alert-triangle" style="width:15px;height:15px;"></i>
                        </div>
                        <h3 style="font-size:13.5px;font-weight:800;color:var(--color-ink);">Top Penunggak Piutang</h3>
                    </div>
                </div>

                <div class="space-y-2 text-xs">
                    <?php if (empty($unpaidStoreList)): ?>
                    <div class="p-6 text-center text-xs" style="color:var(--color-success);font-weight:600;">
                        🎉 Seluruh tagihan toko berjalan lunas!
                    </div>
                    <?php else: ?>
                    <?php foreach ($unpaidStoreList as $us): 
                        $storePhone = preg_replace('/[^0-9]/', '', (string)($us['nomor_whatsapp'] ?? $us['nomor_telepon'] ?? ''));
                        if (str_starts_with($storePhone, '0')) {
                            $storePhone = '62' . substr($storePhone, 1);
                        }
                    ?>
                    <div class="p-2.5 rounded-xl space-y-1" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-start">
                            <div>
                                <div style="font-weight:700;color:var(--color-ink);"><?= htmlspecialchars($us['nama_toko']) ?></div>
                                <div class="flex items-center gap-2 text-[10px]" style="color:var(--color-ink-mute);">
                                    <span>Sales: <?= htmlspecialchars($us['nama_sales'] ?? '—') ?></span>
                                    <?php if (!empty($storePhone)): ?>
                                    <a href="https://wa.me/<?= $storePhone ?>?text=<?= urlencode('Halo ' . $us['nama_toko'] . ', konfirmasi sisa tagihan Keren Snack sebesar ' . Format::rupiah((float)$us['total_sisa_tagihan']) . '. Terima kasih.') ?>" 
                                       target="_blank"
                                       class="badge badge-success text-[9px] hover:opacity-80 inline-flex items-center gap-0.5" title="Kirim Pengingat WhatsApp">
                                        <i data-lucide="message-circle" style="width:10px;height:10px;"></i>
                                        <span>WA</span>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="font-mono font-black" style="color:var(--color-danger);"><?= Format::rupiah((float)$us['total_sisa_tagihan']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: CHANNEL PENJUALAN & TOP PRODUK SKU                                 -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'sales'" x-cloak class="space-y-5">
        
        <!-- 3 CHANNEL PENJUALAN BREAKDOWN -->
        <div class="card p-4 sm:p-5 space-y-4" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
            <div class="flex items-center justify-between pb-3 border-b" style="border-color:var(--color-hairline);">
                <div class="flex items-center gap-2.5">
                    <div style="width:34px;height:34px;border-radius:9px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="layers" style="width:18px;height:18px;"></i>
                    </div>
                    <div>
                        <h2 style="font-size:14.5px;font-weight:800;color:var(--color-ink);">Distribusi Omzet Penjualan Multi-Channel</h2>
                        <p style="font-size:11px;color:var(--color-ink-mute);">Perbandingan Kontribusi: Kasir Ritel POS vs Grosir vs Rak Konsinyasi</p>
                    </div>
                </div>
                <span class="font-mono font-black text-sm" style="color:var(--color-success);"><?= Format::rupiah($totalOmzet) ?></span>
            </div>

            <?php
            $omzetPos = (float)($channelBreakdown['omzet_pos'] ?? 0);
            $omzetGrosir = (float)($channelBreakdown['omzet_grosir'] ?? 0);
            $omzetKonsinyasi = (float)($channelBreakdown['omzet_konsinyasi'] ?? 0);
            $pctPos = ($totalOmzet > 0) ? round(($omzetPos / $totalOmzet) * 100, 1) : 0;
            $pctGrosir = ($totalOmzet > 0) ? round(($omzetGrosir / $totalOmzet) * 100, 1) : 0;
            $pctKonsinyasi = ($totalOmzet > 0) ? round(($omzetKonsinyasi / $totalOmzet) * 100, 1) : 0;
            ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <!-- POS Retail -->
                <div class="p-3.5 rounded-xl space-y-2" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3.5px solid #3b82f6;">
                    <div class="flex items-center justify-between">
                        <span style="font-size:11px;font-weight:700;color:var(--color-ink-mute);">1. Kasir Toko Retail (POS)</span>
                        <span class="badge font-mono text-[10px]" style="background:rgba(59,130,246,0.12);color:#3b82f6;"><?= $pctPos ?>%</span>
                    </div>
                    <div class="font-mono text-base font-black" style="color:var(--color-ink);"><?= Format::rupiah($omzetPos) ?></div>
                    <p style="font-size:10.5px;color:var(--color-ink-mute);">Penjualan langsung kasir (Tunai &amp; QRIS)</p>
                </div>

                <!-- Grosir Direct Order -->
                <div class="p-3.5 rounded-xl space-y-2" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3.5px solid var(--color-primary);">
                    <div class="flex items-center justify-between">
                        <span style="font-size:11px;font-weight:700;color:var(--color-ink-mute);">2. Grosir / Toko Direct</span>
                        <span class="badge badge-primary font-mono text-[10px]"><?= $pctGrosir ?>%</span>
                    </div>
                    <div class="font-mono text-base font-black" style="color:var(--color-ink);"><?= Format::rupiah($omzetGrosir) ?></div>
                    <p style="font-size:10.5px;color:var(--color-ink-mute);">Faktur order bal-balan / tempo langsung toko</p>
                </div>

                <!-- Konsinyasi Rak Toko -->
                <div class="p-3.5 rounded-xl space-y-2" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3.5px solid #f59e0b;">
                    <div class="flex items-center justify-between">
                        <span style="font-size:11px;font-weight:700;color:var(--color-ink-mute);">3. Rak Toko Konsinyasi</span>
                        <span class="badge badge-warning font-mono text-[10px]"><?= $pctKonsinyasi ?>%</span>
                    </div>
                    <div class="font-mono text-base font-black" style="color:var(--color-ink);"><?= Format::rupiah($omzetKonsinyasi) ?></div>
                    <p style="font-size:10.5px;color:var(--color-ink-mute);">Omzet laku dari kunjungan opname sales</p>
                </div>
            </div>
        </div>

        <!-- TOP 5 PRODUK SKU TERLARIS & MARGIN -->
        <div class="card p-4 sm:p-5 space-y-3" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
            <div class="flex items-center justify-between pb-3 border-b" style="border-color:var(--color-hairline);">
                <div class="flex items-center gap-2">
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="award" style="width:16px;height:16px;"></i>
                    </div>
                    <div>
                        <h3 style="font-size:14px;font-weight:800;color:var(--color-ink);">Top 5 SKU Produk Terlaris &amp; Kontribusi Laba Kotor</h3>
                        <div style="font-size:11px;color:var(--color-ink-mute);">Berdasarkan Total Omzet Penjualan Periode Terpilih</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th>SKU &amp; Nama Produk</th>
                            <th class="cell-center">Kuantitas Terjual</th>
                            <th class="cell-right">Total Omzet</th>
                            <th class="cell-right">Laba Kotor SKU</th>
                            <th class="cell-center">Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topSkuList)): ?>
                        <tr>
                            <td colspan="5" class="cell-center p-6 text-xs" style="color:var(--color-ink-mute);">
                                Belum ada data penjualan pada periode ini.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($topSkuList as $sku): 
                            $skuOmzet = (float)$sku['total_omzet'];
                            $skuMarginRp = (float)$sku['laba_kotor_sku'];
                            $skuMarginPct = ($skuOmzet > 0) ? round(($skuMarginRp / $skuOmzet) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:700;color:var(--color-ink);"><?= htmlspecialchars($sku['nama_item']) ?></div>
                                <div class="font-mono text-[10px]" style="color:var(--color-ink-mute);"><?= htmlspecialchars($sku['kode_sku']) ?></div>
                            </td>
                            <td class="cell-center font-bold font-mono">
                                <?= number_format((float)$sku['total_qty']) ?> <?= htmlspecialchars($sku['satuan_dasar'] ?? 'pcs') ?>
                            </td>
                            <td class="cell-right font-mono font-bold" style="color:var(--color-ink);">
                                <?= Format::rupiah($skuOmzet) ?>
                            </td>
                            <td class="cell-right font-mono font-black" style="color:var(--color-success);">
                                <?= Format::rupiah($skuMarginRp) ?>
                            </td>
                            <td class="cell-center">
                                <span class="badge badge-success font-mono font-bold text-[10.5px]">
                                    <?= $skuMarginPct ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 3: PABRIK & PRODUKSI SNACK (MANUFACTURING KPI)                         -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'factory'" x-cloak class="space-y-5">
        
        <!-- 4 KPI PABRIK -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <!-- Total Output Produksi -->
            <div class="card p-3.5 space-y-1.5" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-primary);border-radius:14px;">
                <div class="flex items-center justify-between">
                    <span style="font-size:10px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Total Output</span>
                    <i data-lucide="package-check" style="width:14px;height:14px;color:var(--color-primary);"></i>
                </div>
                <div class="font-mono text-base sm:text-lg font-black" style="color:var(--color-ink);">
                    <?= number_format((int)($productionSummary['total_pcs'] ?? 0)) ?> <span style="font-size:11px;font-weight:600;">pcs</span>
                </div>
                <div style="font-size:10px;color:var(--color-ink-mute);">
                    Setara <?= number_format((int)($productionSummary['total_bal'] ?? 0)) ?> Bal snack
                </div>
            </div>

            <!-- Pekerja Borongan Aktif -->
            <div class="card p-3.5 space-y-1.5" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:14px;">
                <div class="flex items-center justify-between">
                    <span style="font-size:10px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Pekerja Borongan</span>
                    <i data-lucide="users" style="width:14px;height:14px;color:var(--color-success);"></i>
                </div>
                <div class="font-mono text-base sm:text-lg font-black" style="color:var(--color-success);">
                    <?= number_format($totalPekerjaAktif ?? (int)($productionSummary['total_pekerja_aktif'] ?? 0)) ?> <span style="font-size:11px;font-weight:600;">Orang</span>
                </div>
                <div style="font-size:10px;color:var(--color-ink-mute);">
                    Rata-rata: <?= number_format($rataRataOutputPerPekerja ?? (int)($productionSummary['rata_rata_output_per_pekerja'] ?? 0)) ?> pcs / org
                </div>
            </div>

            <!-- Upah Borongan Pabrik -->
            <div class="card p-3.5 space-y-1.5" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-warning);border-radius:14px;">
                <div class="flex items-center justify-between">
                    <span style="font-size:10px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Upah Borongan</span>
                    <i data-lucide="coins" style="width:14px;height:14px;color:var(--color-warning);"></i>
                </div>
                <div class="font-mono text-base sm:text-lg font-black" style="color:var(--color-warning);">
                    <?= Format::rupiah((float)($productionSummary['total_upah_borongan'] ?? 0)) ?>
                </div>
                <div style="font-size:10px;color:var(--color-ink-mute);">
                    Rata-rata: Rp <?= number_format((float)($rataRataUpahPerPcs ?? $productionSummary['rata_rata_upah_per_pcs'] ?? 0), 0, ',', '.') ?> / pcs
                </div>
            </div>

            <!-- Hari Aktif Produksi -->
            <div class="card p-3.5 space-y-1.5" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid #8b5cf6;border-radius:14px;">
                <div class="flex items-center justify-between">
                    <span style="font-size:10px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Hari Kerja</span>
                    <i data-lucide="calendar" style="width:14px;height:14px;color:#8b5cf6;"></i>
                </div>
                <div class="font-mono text-base sm:text-lg font-black" style="color:#8b5cf6;">
                    <?= (int)($productionSummary['hari_produksi_aktif'] ?? 0) ?> Hari
                </div>
                <div style="font-size:10px;color:var(--color-ink-mute);">
                    Lembur: <?= number_format((int)($productionSummary['total_lembur_pcs'] ?? 0)) ?> pcs
                </div>
            </div>
        </div>

        <!-- TOP PRODUK HASIL PRODUKSI -->
        <div class="card p-4 sm:p-5 space-y-3" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
            <div class="flex items-center justify-between pb-3 border-b" style="border-color:var(--color-hairline);">
                <div class="flex items-center gap-2">
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(59,130,246,0.12);color:#3b82f6;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="boxes" style="width:16px;height:16px;"></i>
                    </div>
                    <div>
                        <h3 style="font-size:14px;font-weight:800;color:var(--color-ink);">Produk yang Paling Banyak Diproduksi Pabrik</h3>
                        <div style="font-size:11px;color:var(--color-ink-mute);">Akumulasi Volume Produksi &amp; Alokasi Upah Borongan</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th>SKU &amp; Nama Produk Jadi</th>
                            <th class="cell-center">Output Pcs</th>
                            <th class="cell-center">Output Bal</th>
                            <th class="cell-right">Total Upah Borongan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topProducedItems)): ?>
                        <tr>
                            <td colspan="4" class="cell-center p-6 text-xs" style="color:var(--color-ink-mute);">
                                Belum ada catatan batch produksi harian pada periode ini.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($topProducedItems as $tpi): ?>
                        <tr>
                            <td>
                                <div style="font-weight:700;color:var(--color-ink);"><?= htmlspecialchars($tpi['nama_item']) ?></div>
                                <div class="font-mono text-[10px]" style="color:var(--color-ink-mute);"><?= htmlspecialchars($tpi['kode_sku']) ?></div>
                            </td>
                            <td class="cell-center font-mono font-bold" style="color:var(--color-primary);">
                                <?= number_format((int)$tpi['total_pcs']) ?> pcs
                            </td>
                            <td class="cell-center font-mono font-bold">
                                <?= number_format((int)$tpi['total_bal']) ?> bal
                            </td>
                            <td class="cell-right font-mono font-bold" style="color:var(--color-warning);">
                                <?= Format::rupiah((float)$tpi['total_upah']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 4: MITRA KONSINYASI & TIM SALES LEADERBOARD                           -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'consignment'" x-cloak class="space-y-5">
        
        <!-- 2-COLUMN GRID: LEADERBOARD SALES & TOP TOKO -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            
            <!-- LEADERBOARD SALES & KOMISI -->
            <div class="card p-4 sm:p-5 space-y-4" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center justify-between pb-3 border-b" style="border-color:var(--color-hairline);">
                    <div class="flex items-center gap-2.5">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(37,99,235,0.12);color:var(--color-primary);display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="medal" style="width:16px;height:16px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:13.5px;font-weight:800;color:var(--color-ink);">Leaderboard Tim Sales</h3>
                            <div style="font-size:11px;color:var(--color-ink-mute);">Berdasarkan Omzet Tertagih Toko Binaan</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-2.5 text-xs">
                    <?php if (empty($salesLeaderboard)): ?>
                    <div class="p-6 text-center" style="color:var(--color-ink-mute);">Belum ada data sales yang tercatat.</div>
                    <?php else: ?>
                    <?php foreach ($salesLeaderboard as $sc): ?>
                    <div class="p-3 rounded-xl space-y-1.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex items-center justify-between">
                            <div>
                                <div style="font-weight:800;font-size:13px;color:var(--color-ink);"><?= htmlspecialchars($sc['nama_karyawan']) ?></div>
                                <div style="font-size:10.5px;color:var(--color-ink-mute);"><?= (int)$sc['total_toko_binaan'] ?> Toko Binaan Tetap</div>
                            </div>
                            <div class="text-right">
                                <span class="font-mono font-black text-sm" style="color:var(--color-ink);"><?= Format::rupiah((float)$sc['total_omzet_laku']) ?></span>
                                <div style="font-size:10px;color:var(--color-ink-mute);">Omzet Tertagih</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-1 border-t text-[11px]" style="border-color:var(--color-hairline);">
                            <span class="badge badge-primary font-mono text-[9.5px]"><?= htmlspecialchars($sc['nama_tier']) ?></span>
                            <span class="font-bold" style="color:var(--color-success);">
                                Komisi (<?= (float)$sc['persentase_komisi'] ?>%): <?= Format::rupiah((float)$sc['estimasi_komisi_rp']) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TOP 5 TOKO KONSINYASI PENYUMBANG OMZET -->
            <div class="card p-4 sm:p-5 space-y-4" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center justify-between pb-3 border-b" style="border-color:var(--color-hairline);">
                    <div class="flex items-center gap-2.5">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="store" style="width:16px;height:16px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:13.5px;font-weight:800;color:var(--color-ink);">Top 5 Toko Konsinyasi Terproduktif</h3>
                            <div style="font-size:11px;color:var(--color-ink-mute);">Mitra dengan Penjualan Rak Tertinggi</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-2 text-xs">
                    <?php if (empty($topStores)): ?>
                    <div class="p-6 text-center text-xs" style="color:var(--color-ink-mute);">Belum ada riwayat kunjungan toko beromzet pada periode ini.</div>
                    <?php else: ?>
                    <?php foreach ($topStores as $ts): ?>
                    <div class="flex items-center justify-between p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div>
                            <div style="font-weight:700;color:var(--color-ink);"><?= htmlspecialchars($ts['nama_toko']) ?></div>
                            <div style="font-size:10px;color:var(--color-ink-mute);"><?= $ts['total_kunjungan'] ?> kali opname/kunjungan periode ini</div>
                        </div>
                        <span class="font-mono font-black" style="color:var(--color-success);"><?= Format::rupiah((float)$ts['total_omzet']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- 2-COLUMN: EARLY WARNING TOKO OVERDUE (>14 HARI) & KERUGIAN RETUR RUSAK -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            
            <!-- WARNING: TOKO KONSINYASI OVERDUE > 14 HARI TIDAK DIKUNJUNGI -->
            <div class="card p-4 sm:p-5 space-y-3 text-xs" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center justify-between pb-2 border-b" style="border-color:var(--color-hairline);">
                    <div class="flex items-center gap-2">
                        <div style="width:28px;height:28px;border-radius:8px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="alert-triangle" style="width:14px;height:14px;"></i>
                        </div>
                        <div style="font-weight:800;color:var(--color-ink);font-size:13px;">Toko Perlu Kunjungan (&gt;14 Hari)</div>
                    </div>
                    <span class="badge badge-warning font-mono text-[10px]"><?= count($overdueStores) ?> Toko</span>
                </div>

                <div class="space-y-2 max-h-[280px] overflow-y-auto pr-1">
                    <?php if (empty($overdueStores)): ?>
                    <div class="p-6 text-center text-xs font-semibold" style="color:var(--color-success);">
                        🎉 Luar biasa! Semua toko mitra aktif rutin dikunjungi &le; 14 hari!
                    </div>
                    <?php else: ?>
                    <?php foreach ($overdueStores as $os): ?>
                    <div class="p-2.5 rounded-xl space-y-1" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-start">
                            <span style="font-weight:700;color:var(--color-ink);"><?= htmlspecialchars($os['nama_toko']) ?></span>
                            <span class="badge badge-danger text-[9.5px]">
                                <?= ($os['hari_tidak_dikunjungi'] >= 900) ? 'Belum Pernah' : $os['hari_tidak_dikunjungi'] . ' hari lalu' ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-[10.5px]" style="color:var(--color-ink-mute);">
                            <span>Sales: <strong style="color:var(--color-ink);"><?= htmlspecialchars($os['nama_sales'] ?? '—') ?></strong></span>
                            <?php 
                            $osPhone = preg_replace('/[^0-9]/', '', (string)($os['nomor_whatsapp'] ?? $os['nomor_telepon'] ?? ''));
                            if (str_starts_with($osPhone, '0')) {
                                $osPhone = '62' . substr($osPhone, 1);
                            }
                            ?>
                            <?php if (!empty($osPhone)): ?>
                            <a href="https://wa.me/<?= $osPhone ?>?text=<?= urlencode('Halo ' . $os['nama_toko'] . ', konfirmasi jadwal kunjungan sales dan opname snack dari Keren Snack.') ?>" 
                               target="_blank"
                               class="badge badge-success font-mono text-[9.5px] hover:opacity-80 inline-flex items-center gap-1" title="Chat WhatsApp Toko">
                                <i data-lucide="message-circle" style="width:10px;height:10px;"></i>
                                <span><?= htmlspecialchars($os['nomor_whatsapp'] ?? $os['nomor_telepon'] ?? '') ?></span>
                            </a>
                            <?php else: ?>
                            <span>WA: —</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- LAPORAN KERUGIAN RETUR RUSAK (BS) -->
            <div class="card p-4 sm:p-5 space-y-3 text-xs" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center gap-2 pb-2 border-b" style="border-color:var(--color-hairline);">
                    <div style="width:28px;height:28px;border-radius:8px;background:rgba(239,68,68,0.12);color:var(--color-danger);display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="package-x" style="width:14px;height:14px;"></i>
                    </div>
                    <div>
                        <div style="font-weight:800;color:var(--color-ink);font-size:13px;">Evaluasi Kerugian Retur Rusak (BS) Toko</div>
                        <div style="font-size:10.5px;color:var(--color-ink-mute);">Beban Kerugian HPP Akibat Barang Bocor / Remuk</div>
                    </div>
                </div>

                <div class="p-3 rounded-xl space-y-1" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <div style="font-size:11px;color:var(--color-ink-mute);">Total Nilai Kerugian HPP:</div>
                    <div class="text-lg font-black font-mono" style="color:var(--color-danger);">
                        <?= Format::rupiah((float)($lossReport['total_kerugian_rusak'] ?? 0)) ?>
                    </div>
                    <div style="font-size:10.5px;color:var(--color-ink-mute);">
                        <?= (int)($lossReport['total_pcs_rusak'] ?? 0) ?> pcs snack rusak ditarik dari rak toko
                    </div>
                </div>

                <div class="space-y-1.5 pt-1">
                    <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:var(--color-ink-mute);">SKU Paling Sering Rusak di Toko:</div>
                    <?php if (empty($topDamagedItems)): ?>
                    <div class="text-[11px] italic" style="color:var(--color-ink-mute);">Nol laporan retur rusak pada periode ini.</div>
                    <?php else: ?>
                    <?php foreach ($topDamagedItems as $tdi): ?>
                    <div class="flex justify-between items-center py-1 border-b" style="border-color:var(--color-hairline);">
                        <span style="color:var(--color-ink);font-weight:600;"><?= htmlspecialchars($tdi['nama_item']) ?></span>
                        <span class="font-bold font-mono" style="color:var(--color-danger);">
                            <?= (int)$tdi['total_pcs_rusak'] ?> pcs (<?= Format::rupiah((float)$tdi['total_nominal_kerugian']) ?>)
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

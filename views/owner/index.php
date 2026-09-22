<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<style>
/* Executive Dashboard Standardized Card Spacing System */
.executive-card-header {
    padding: 18px 24px !important;
    border-bottom: 1px solid var(--color-hairline) !important;
    background: var(--color-canvas) !important;
}
.executive-card-body {
    padding: 22px 24px !important;
}
.executive-card-body.is-centered {
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
    align-items: center !important;
    text-align: center !important;
    flex: 1 1 0% !important;
    min-height: 180px;
}
.executive-card-body.is-flex-col-centered {
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
    flex: 1 1 0% !important;
}
.executive-card-body.is-flex-col-centered.overflow-y-auto {
    justify-content: flex-start !important;
}
.executive-card-footer {
    padding: 14px 24px !important;
    border-top: 1px solid var(--color-hairline) !important;
    background: var(--color-canvas-soft) !important;
}

@media (max-width: 640px) {
    .executive-card-header {
        padding: 16px 18px !important;
    }
    .executive-card-body {
        padding: 18px 16px !important;
    }
    .executive-card-footer {
        padding: 12px 16px !important;
    }
}

.health-status-badge {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 3.5px 12px !important;
    border-radius: 9999px !important;
    font-size: 11.5px !important;
    font-weight: 700 !important;
    line-height: 1.25 !important;
    white-space: nowrap !important;
    vertical-align: middle !important;
    letter-spacing: 0 !important;
    box-sizing: border-box !important;
    text-transform: none !important;
    flex-shrink: 0 !important;
}
.health-status-badge .health-dot {
    width: 6px !important;
    height: 6px !important;
    border-radius: 50% !important;
    flex-shrink: 0 !important;
    display: inline-block !important;
}

.period-filter-header {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--color-hairline);
}
@media (min-width: 1200px) {
    .period-filter-header {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }
}

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
}" class="space-y-4 sm:space-y-5 pb-20">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER (Pola Kanonikal dari /dashboard)                           -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-amber">
                <i data-lucide="crown"></i>
            </div>
            <div class="page-header-text min-w-0">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#f59e0b;"></span>
                    <span>Executive Command Center</span>
                </div>
                <?php
                // Evaluasi Realitas Kesehatan Finansial & Operasional Bisnis
                $isDeficit = ($labaBersih < 0 && $netWorkingCapital < 0);
                $isWarning = ($labaBersih < 0 || $netWorkingCapital < 0 || (!empty($overdueStores) && count($overdueStores) >= 5));
                $isPrima   = ($labaBersih > 0 && ($marginLabaBersih ?? 0) >= 10 && $netWorkingCapital > 0);

                if ($isDeficit) {
                    $healthStatus = 'danger';
                    $healthLabel  = 'Defisit';
                    $healthTitle  = 'Laba operasional negatif dan modal kerja defisit';
                    $healthBg     = 'rgba(239, 68, 68, 0.12)';
                    $healthColor  = 'var(--color-danger)';
                    $healthBorder = 'rgba(239, 68, 68, 0.25)';
                } elseif ($isWarning) {
                    $healthStatus = 'warning';
                    $healthLabel  = 'Perlu Perhatian';
                    $healthTitle  = ($labaBersih < 0) 
                        ? 'Beban operasional melebihi laba kotor periode ini' 
                        : (($netWorkingCapital < 0) ? 'Kewajiban hutang melebihi aset lancar' : 'Ada toko konsinyasi menunggak');
                    $healthBg     = 'rgba(245, 158, 11, 0.12)';
                    $healthColor  = 'var(--color-warning)';
                    $healthBorder = 'rgba(245, 158, 11, 0.25)';
                } elseif ($isPrima) {
                    $healthStatus = 'prima';
                    $healthLabel  = 'Prima';
                    $healthTitle  = 'Laba operasional positif dengan margin > 10% dan modal kerja aman';
                    $healthBg     = 'rgba(16, 185, 129, 0.12)';
                    $healthColor  = 'var(--color-success)';
                    $healthBorder = 'rgba(16, 185, 129, 0.25)';
                } else {
                    // Normal / Sehat / Tanpa Kerugian Finansial (termasuk kondisi awal periode bersih)
                    $healthStatus = 'healthy';
                    $healthLabel  = 'Sehat';
                    $healthTitle  = 'Operasional bisnis berjalan stabil tanpa beban kerugian atau hutang tertunggak';
                    $healthBg     = 'rgba(16, 185, 129, 0.12)';
                    $healthColor  = 'var(--color-success)';
                    $healthBorder = 'rgba(16, 185, 129, 0.25)';
                }
                ?>
                <h1 class="page-title">
                    <span><?= htmlspecialchars($pageTitle ?? 'Owner Executive Dashboard') ?></span>
                    <span class="health-status-badge" 
                          style="background:<?= $healthBg ?>;color:<?= $healthColor ?>;border:1px solid <?= $healthBorder ?>;"
                          title="<?= htmlspecialchars($healthTitle) ?>">
                        <span class="health-dot" style="background:<?= $healthColor ?>;box-shadow:0 0 6px <?= $healthColor ?>;animation:pulse 1.8s infinite;"></span>
                        <span>Kesehatan Bisnis: <?= $healthLabel ?></span>
                    </span>
                </h1>
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
    <div class="card p-4 sm:p-5 rounded-2xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <form x-ref="filterForm" method="GET" action="<?= Router::url('/owner') ?>" style="display:flex;flex-direction:column;gap:14px;">
            <input type="hidden" name="preset" :value="filterPreset">
            <input type="hidden" name="tab" :value="activeTab">

            <!-- Baris Preset Periode Cepat -->
            <div class="period-filter-header">
                <div class="flex items-center gap-2 flex-wrap min-w-0" style="row-gap: 8px;">
                    <div class="flex items-center gap-1.5 flex-shrink-0 text-ink-mute">
                        <i data-lucide="calendar-range" style="width:15px;height:15px;color:var(--color-primary);flex-shrink:0;"></i>
                        <span style="font-size:12px;font-weight:800;color:var(--color-ink);white-space:nowrap;">Periode Evaluasi:</span>
                    </div>
                    <span class="badge badge-primary font-bold text-[11px] sm:text-xs inline-flex items-center flex-shrink-0" 
                          style="padding:4px 12px;border-radius:8px;font-family:inherit;line-height:1.3;white-space:nowrap;" 
                          title="<?= htmlspecialchars($periodLabel ?? 'Bulan Ini') ?>">
                        <?= htmlspecialchars($periodLabel ?? 'Bulan Ini') ?>
                    </span>
                </div>

                <!-- Preset Buttons (Responsif horizontal scrollable dock) -->
                <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5 -mx-1 px-1 sm:mx-0 sm:px-0" style="-webkit-overflow-scrolling:touch;">
                    <button type="button" @click="setPreset('today')" 
                            :class="filterPreset === 'today' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                            style="padding:4px 10px;font-size:11px;font-weight:700;border-radius:7px;border:1px solid var(--color-hairline);white-space:nowrap;flex-shrink:0;height:30px;">
                        Hari Ini
                    </button>
                    <button type="button" @click="setPreset('7days')" 
                            :class="filterPreset === '7days' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                            style="padding:4px 10px;font-size:11px;font-weight:700;border-radius:7px;border:1px solid var(--color-hairline);white-space:nowrap;flex-shrink:0;height:30px;">
                        7 Hari Terakhir
                    </button>
                    <button type="button" @click="setPreset('this_month')" 
                            :class="filterPreset === 'this_month' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                            style="padding:4px 10px;font-size:11px;font-weight:700;border-radius:7px;border:1px solid var(--color-hairline);white-space:nowrap;flex-shrink:0;height:30px;">
                        Bulan Ini
                    </button>
                    <button type="button" @click="setPreset('last_month')" 
                            :class="filterPreset === 'last_month' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                            style="padding:4px 10px;font-size:11px;font-weight:700;border-radius:7px;border:1px solid var(--color-hairline);white-space:nowrap;flex-shrink:0;height:30px;">
                        Bulan Lalu
                    </button>
                    <button type="button" @click="setPreset('this_year')" 
                            :class="filterPreset === 'this_year' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                            style="padding:4px 10px;font-size:11px;font-weight:700;border-radius:7px;border:1px solid var(--color-hairline);white-space:nowrap;flex-shrink:0;height:30px;">
                        Tahun Ini
                    </button>
                </div>
            </div>

            <!-- Custom Date Range Form (3 Kolom pas tanpa ruang kosong) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3 items-end">
                <div>
                    <label class="form-label" style="font-size:10.5px;font-weight:700;margin-bottom:3px;display:block;color:var(--color-ink-mute);">Mulai Tanggal</label>
                    <input type="date" name="start_date" x-model="filterStart" @change="filterPreset = 'custom'" class="form-input" style="height:36px;font-size:12px;width:100%;">
                </div>

                <div>
                    <label class="form-label" style="font-size:10.5px;font-weight:700;margin-bottom:3px;display:block;color:var(--color-ink-mute);">Sampai Tanggal</label>
                    <input type="date" name="end_date" x-model="filterEnd" @change="filterPreset = 'custom'" class="form-input" style="height:36px;font-size:12px;width:100%;">
                </div>

                <div>
                    <button type="submit" class="btn btn-primary" style="height:36px;width:100%;font-weight:700;display:inline-flex;align-items:center;justify-content:center;gap:6px;font-size:12px;">
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
    <div class="card p-1.5 sm:p-2 rounded-2xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2">
            <!-- Tab 1: Finansial -->
            <button type="button" 
                    @click="activeTab = 'finance'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                    :class="activeTab === 'finance' ? 'btn btn-primary shadow-sm' : 'btn btn-ghost hover:bg-canvas-soft'"
                    style="display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:700;padding:8px 10px;border-radius:10px;font-size:12px;white-space:nowrap;height:38px;border:1px solid transparent;width:100%;"
                    :style="activeTab !== 'finance' ? 'border-color:var(--color-hairline);color:var(--color-ink);' : ''">
                <i data-lucide="trending-up" style="width:15px;height:15px;flex-shrink:0;"></i>
                <span>Laba Rugi</span>
            </button>

            <!-- Tab 2: Penjualan -->
            <button type="button" 
                    @click="activeTab = 'sales'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                    :class="activeTab === 'sales' ? 'btn btn-primary shadow-sm' : 'btn btn-ghost hover:bg-canvas-soft'"
                    style="display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:700;padding:8px 10px;border-radius:10px;font-size:12px;white-space:nowrap;height:38px;border:1px solid transparent;width:100%;"
                    :style="activeTab !== 'sales' ? 'border-color:var(--color-hairline);color:var(--color-ink);' : ''">
                <i data-lucide="shopping-bag" style="width:15px;height:15px;flex-shrink:0;"></i>
                <span>Penjualan</span>
            </button>

            <!-- Tab 3: Produksi -->
            <button type="button" 
                    @click="activeTab = 'factory'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                    :class="activeTab === 'factory' ? 'btn btn-primary shadow-sm' : 'btn btn-ghost hover:bg-canvas-soft'"
                    style="display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:700;padding:8px 10px;border-radius:10px;font-size:12px;white-space:nowrap;height:38px;border:1px solid transparent;width:100%;"
                    :style="activeTab !== 'factory' ? 'border-color:var(--color-hairline);color:var(--color-ink);' : ''">
                <i data-lucide="factory" style="width:15px;height:15px;flex-shrink:0;"></i>
                <span>Produksi</span>
                <span class="badge badge-primary font-bold text-[10px] px-1.5 py-0.5 rounded-full flex-shrink-0"
                      style="font-family:inherit;line-height:1;margin-left:2px;letter-spacing:0.01em;"
                      :style="activeTab === 'factory' ? 'background:rgba(255,255,255,0.22) !important;color:#fff !important;border-color:rgba(255,255,255,0.35) !important;' : ''">
                    <?= number_format((int)($productionSummary['total_pcs'] ?? 0)) ?> pcs
                </span>
            </button>

            <!-- Tab 4: Konsinyasi -->
            <button type="button" 
                    @click="activeTab = 'consignment'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                    :class="activeTab === 'consignment' ? 'btn btn-primary shadow-sm' : 'btn btn-ghost hover:bg-canvas-soft'"
                    style="display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:700;padding:8px 10px;border-radius:10px;font-size:12px;white-space:nowrap;height:38px;border:1px solid transparent;width:100%;"
                    :style="activeTab !== 'consignment' ? 'border-color:var(--color-hairline);color:var(--color-ink);' : ''">
                <i data-lucide="store" style="width:15px;height:15px;flex-shrink:0;"></i>
                <span>Konsinyasi</span>
                <?php if (!empty($overdueStores)): ?>
                <span class="badge badge-warning font-bold text-[10px] px-1.5 py-0.5 rounded-full flex-shrink-0"
                      style="font-family:inherit;line-height:1;margin-left:2px;letter-spacing:0.01em;"
                      :style="activeTab === 'consignment' ? 'background:rgba(255,255,255,0.22) !important;color:#fff !important;border-color:rgba(255,255,255,0.35) !important;' : ''"
                      title="<?= count($overdueStores) ?> Toko Overdue">
                    <?= count($overdueStores) ?> Overdue
                </span>
                <?php endif; ?>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: FINANSIAL, PROFITABILITAS & MODAL KERJA                            -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'finance'" x-cloak class="space-y-5 sm:space-y-6">

        <!-- 5 KPI STAT CARDS (STANDAR LABA RUGI & ARUS KAS) -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4.5">
            
            <!-- 1. Omzet Penjualan Bersih -->
            <div class="card p-4 sm:p-4.5 space-y-2.5 flex flex-col justify-between h-full" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-primary);border-radius:16px;box-shadow:var(--shadow-1);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span style="font-size:10.5px;sm:font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">1. Omzet Riil</span>
                    <i data-lucide="receipt" style="width:16px;height:16px;color:var(--color-primary);flex-shrink:0;"></i>
                </div>
                <div class="font-mono" style="font-size:clamp(15px, 2.7vw, 20px);font-weight:900;color:var(--color-ink);line-height:1.2;">
                    <?= Format::rupiah($totalOmzet) ?>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10.5px;color:var(--color-ink-mute);padding-top:6px;border-top:1px dashed var(--color-hairline);">
                    <span><?= number_format($totalTransaksi) ?> Nota</span>
                    <span style="color:var(--color-success);font-weight:700;">Hari ini: <?= Format::rupiah($omzetToday) ?></span>
                </div>
            </div>

            <!-- 2. HPP Barang Terjual (COGS) -->
            <div class="card p-4 sm:p-4.5 space-y-2.5 flex flex-col justify-between h-full" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid #64748b;border-radius:16px;box-shadow:var(--shadow-1);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span style="font-size:10.5px;sm:font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">2. HPP Terjual</span>
                    <i data-lucide="package" style="width:16px;height:16px;color:#64748b;flex-shrink:0;"></i>
                </div>
                <div class="font-mono" style="font-size:clamp(15px, 2.7vw, 20px);font-weight:900;color:#64748b;line-height:1.2;">
                    <?= Format::rupiah($totalHpp) ?>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10.5px;color:var(--color-ink-mute);padding-top:6px;border-top:1px dashed var(--color-hairline);">
                    <span>Bahan baku + Kemas</span>
                    <span class="badge font-bold text-[9.5px] px-2 py-0.5 rounded flex-shrink-0" style="background:rgba(100,116,139,0.12);color:#64748b;font-family:inherit;">COGS</span>
                </div>
            </div>

            <!-- 3. Laba Kotor & Margin -->
            <div class="card p-4 sm:p-4.5 space-y-2.5 flex flex-col justify-between h-full" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:16px;box-shadow:var(--shadow-1);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span style="font-size:10.5px;sm:font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">3. Laba Kotor</span>
                    <span class="badge badge-success font-bold flex-shrink-0 text-[10px] px-2 py-0.5 rounded" style="font-family:inherit;">
                        <?= $marginLabaKotor ?>% Margin
                    </span>
                </div>
                <div class="font-mono" style="font-size:clamp(15px, 2.7vw, 20px);font-weight:900;color:var(--color-success);line-height:1.2;">
                    <?= Format::rupiah($labaKotor) ?>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10.5px;color:var(--color-ink-mute);padding-top:6px;border-top:1px dashed var(--color-hairline);">
                    <span>Omzet - HPP</span>
                    <span class="badge badge-success font-bold text-[9.5px] px-2 py-0.5 rounded flex-shrink-0" style="font-family:inherit;">GROSS</span>
                </div>
            </div>

            <!-- 4. Beban Pengeluaran Operasional -->
            <div class="card p-4 sm:p-4.5 space-y-2.5 flex flex-col justify-between h-full" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-danger);border-radius:16px;box-shadow:var(--shadow-1);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span style="font-size:10.5px;sm:font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">4. Beban Kas</span>
                    <i data-lucide="arrow-up-right" style="width:16px;height:16px;color:var(--color-danger);flex-shrink:0;"></i>
                </div>
                <div class="font-mono" style="font-size:clamp(15px, 2.7vw, 20px);font-weight:900;color:var(--color-danger);line-height:1.2;">
                    <?= Format::rupiah($totalBebanOperasional) ?>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10.5px;color:var(--color-ink-mute);padding-top:6px;border-top:1px dashed var(--color-hairline);">
                    <span>Arus Kas Keluar</span>
                    <span class="badge badge-danger font-bold text-[9.5px] px-2 py-0.5 rounded flex-shrink-0" style="font-family:inherit;">OPEX</span>
                </div>
            </div>

            <!-- 5. Estimasi Laba Bersih Operasional -->
            <div class="col-span-2 sm:col-span-1 card p-4 sm:p-4.5 space-y-2.5 flex flex-col justify-between h-full" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid <?= $labaBersih >= 0 ? '#10b981' : '#ef4444' ?>;border-radius:16px;box-shadow:var(--shadow-1);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span style="font-size:10.5px;sm:font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">5. Laba Bersih</span>
                    <span class="badge <?= $labaBersih >= 0 ? 'badge-success' : 'badge-danger' ?> font-bold flex-shrink-0 text-[10px] px-2 py-0.5 rounded" style="font-family:inherit;">
                        <?= $marginLabaBersih ?>% Net
                    </span>
                </div>
                <div class="font-mono" style="font-size:clamp(15px, 2.7vw, 20px);font-weight:900;color:<?= $labaBersih >= 0 ? 'var(--color-success)' : 'var(--color-danger)' ?>;line-height:1.2;">
                    <?= Format::rupiah($labaBersih) ?>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10.5px;color:var(--color-ink-mute);padding-top:6px;border-top:1px dashed var(--color-hairline);">
                    <span>Laba Kotor - Beban</span>
                    <span class="badge <?= $labaBersih >= 0 ? 'badge-success' : 'badge-danger' ?> font-bold text-[9.5px] px-2 py-0.5 rounded flex-shrink-0" style="font-family:inherit;">NET</span>
                </div>
            </div>

        </div>

        <!-- 2-PANE: NERACA MODAL KERJA & BREAKDOWN BEBAN -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6 items-stretch">
            
            <!-- NERACA KESEHATAN MODAL KERJA BERSIH (2 KOLOM DESKTOP) -->
            <div class="lg:col-span-2 card overflow-hidden flex flex-col justify-between h-full" style="border-radius:18px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
                <!-- Header -->
                <div class="executive-card-header px-5 py-4 sm:px-6 sm:py-4.5 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="padding: 18px 24px; border-bottom: 1px solid var(--color-hairline); background: var(--color-canvas);">
                    <div class="flex items-center gap-3 min-w-0">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(59,130,246,0.12);color:#3b82f6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="scale" style="width:18px;height:18px;"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 style="font-size:14.5px;sm:font-size:15.5px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;">Neraca Modal Kerja Bersih (Net Working Capital)</h2>
                            <p style="font-size:11.5px;sm:font-size:12px;color:var(--color-ink-mute);margin:3px 0 0 0;line-height:1.35;">Likuiditas aset lancar usaha vs kewajiban supplier</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5 self-start sm:self-auto flex-shrink-0 pl-11 sm:pl-0">
                        <span class="font-mono font-black text-sm sm:text-base" style="color:var(--color-primary);"><?= Format::rupiah($netWorkingCapital) ?></span>
                        <?php if ($netWorkingCapital > 0): ?>
                        <span class="badge badge-success font-bold text-[10px] px-2.5 py-0.5 rounded-full" style="font-family:inherit;" title="Likuiditas Lancar: Aset lancar usaha mencukupi kewajiban jangka pendek">SEHAT</span>
                        <?php else: ?>
                        <span class="badge badge-danger font-bold text-[10px] px-2.5 py-0.5 rounded-full" style="font-family:inherit;" title="Defisit Modal Kerja: Kewajiban hutang melebihi aset lancar likuid">WASPADA</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Body: 4 Kartu Aset & Rincian -->
                <div class="executive-card-body is-flex-col-centered space-y-4 sm:space-y-5" style="padding: 24px;">
                    <!-- 4 KARTU ASET & KEWAJIBAN -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-3.5">
                        <!-- Kas & Bank -->
                        <div class="p-3.5 sm:p-4 rounded-xl space-y-1.5 flex flex-col justify-between" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3px solid #3b82f6;">
                            <div class="flex items-center justify-between">
                                <span style="font-size:10.5px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;">Kas &amp; Bank</span>
                                <i data-lucide="wallet" style="width:14px;height:14px;color:#3b82f6;"></i>
                            </div>
                            <div class="font-mono text-sm sm:text-base font-black" style="color:#3b82f6;"><?= Format::rupiah($totalKasLikuid) ?></div>
                            <div style="font-size:10px;color:var(--color-ink-mute);"><?= count($kasDetail) ?> Rekening Aktif</div>
                        </div>

                        <!-- Total Piutang -->
                        <div class="p-3.5 sm:p-4 rounded-xl space-y-1.5 flex flex-col justify-between" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3px solid #f59e0b;">
                            <div class="flex items-center justify-between">
                                <span style="font-size:10.5px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;">Piutang Toko</span>
                                <i data-lucide="clock" style="width:14px;height:14px;color:#f59e0b;"></i>
                            </div>
                            <div class="font-mono text-sm sm:text-base font-black" style="color:#f59e0b;"><?= Format::rupiah($totalPiutang) ?></div>
                            <div style="font-size:10px;color:var(--color-ink-mute);">Grosir &amp; Konsinyasi</div>
                        </div>

                        <!-- Valuasi Persediaan Total -->
                        <div class="p-3.5 sm:p-4 rounded-xl space-y-1.5 flex flex-col justify-between" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3px solid var(--color-success);">
                            <div class="flex items-center justify-between">
                                <span style="font-size:10.5px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;">Aset Persediaan</span>
                                <i data-lucide="boxes" style="width:14px;height:14px;color:var(--color-success);"></i>
                            </div>
                            <div class="font-mono text-sm sm:text-base font-black" style="color:var(--color-success);"><?= Format::rupiah($totalValuasiPersediaan) ?></div>
                            <div style="font-size:10px;color:var(--color-ink-mute);">Gudang + Rak Toko</div>
                        </div>

                        <!-- Hutang Supplier -->
                        <div class="p-3.5 sm:p-4 rounded-xl space-y-1.5 flex flex-col justify-between" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3px solid var(--color-danger);">
                            <div class="flex items-center justify-between">
                                <span style="font-size:10.5px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;">Hutang Vendor</span>
                                <i data-lucide="alert-circle" style="width:14px;height:14px;color:var(--color-danger);"></i>
                            </div>
                            <div class="font-mono text-sm sm:text-base font-black" style="color:var(--color-danger);"><?= Format::rupiah($totalHutangPemasok) ?></div>
                            <div style="font-size:10px;color:var(--color-ink-mute);">Kewajiban Bahan Baku</div>
                        </div>
                    </div>

                    <!-- RINCIAN VALUASI PERSEDIAAN GUDANG VS RAK -->
                    <div class="p-4 sm:p-4.5 rounded-xl space-y-2.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">
                            Komposisi Valuasi Stok Persediaan (Berdasarkan HPP):
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                            <div class="p-2.5 sm:p-3 rounded-lg" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                                <span style="color:var(--color-ink-mute);font-size:10.5px;">Bahan Mentah:</span>
                                <div class="font-bold font-mono text-xs sm:text-[13px]" style="color:var(--color-ink);margin-top:2px;"><?= Format::rupiah($stokGudangBahan) ?></div>
                            </div>
                            <div class="p-2.5 sm:p-3 rounded-lg" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                                <span style="color:var(--color-ink-mute);font-size:10.5px;">Bahan Kemas:</span>
                                <div class="font-bold font-mono text-xs sm:text-[13px]" style="color:var(--color-ink);margin-top:2px;"><?= Format::rupiah($stokGudangKemas) ?></div>
                            </div>
                            <div class="p-2.5 sm:p-3 rounded-lg" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                                <span style="color:var(--color-ink-mute);font-size:10.5px;">Barang Jadi Gudang:</span>
                                <div class="font-bold font-mono text-xs sm:text-[13px]" style="color:var(--color-ink);margin-top:2px;"><?= Format::rupiah($stokGudangJadi) ?></div>
                            </div>
                            <div class="p-2.5 sm:p-3 rounded-lg" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:2.5px solid #f59e0b;">
                                <span style="color:var(--color-warning);font-size:10.5px;font-weight:700;">Titipan Rak Toko:</span>
                                <div class="font-black font-mono text-xs sm:text-[13px]" style="color:var(--color-warning);margin-top:2px;"><?= Format::rupiah($stokRakKonsinyasi) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="executive-card-footer px-5 py-3 sm:px-6 sm:py-3.5 border-t text-center text-xs sm:text-[12px] text-ink-mute" style="padding: 14px 24px; border-top: 1px solid var(--color-hairline); background: var(--color-canvas-soft);">
                    Net Working Capital = (Kas + Piutang + Total Persediaan) &minus; Hutang Vendor
                </div>
            </div>

            <!-- BREAKDOWN PENGELUARAN OPERASIONAL TERBESAR -->
            <div class="card overflow-hidden flex flex-col justify-between h-full" style="border-radius:18px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
                <!-- Header -->
                <div class="executive-card-header px-5 py-4 sm:px-6 sm:py-4.5 border-b flex items-center justify-between gap-3" style="padding: 18px 24px; border-bottom: 1px solid var(--color-hairline); background: var(--color-canvas);">
                    <div class="flex items-center gap-3 min-w-0">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(239,68,68,0.12);color:var(--color-danger);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="pie-chart" style="width:18px;height:18px;"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 style="font-size:14.5px;sm:font-size:15.5px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;">Beban Kas Terbesar</h3>
                            <p style="font-size:11.5px;sm:font-size:12px;color:var(--color-ink-mute);margin:3px 0 0 0;line-height:1.25;">Top 5 Pengeluaran Periode</p>
                        </div>
                    </div>
                    <span class="font-bold font-mono text-xs sm:text-sm flex-shrink-0" style="color:var(--color-danger);"><?= Format::rupiah($totalBebanOperasional) ?></span>
                </div>

                <!-- Body -->
                <?php if (empty($expenseBreakdown)): ?>
                <div class="executive-card-body is-centered" style="padding: 24px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:rgba(16,185,129,0.1);color:var(--color-success);display:flex;align-items:center;justify-content:center;margin:0 auto 10px auto;flex-shrink:0;">
                        <i data-lucide="check-circle-2" style="width:22px;height:22px;"></i>
                    </div>
                    <div style="font-size:13.5px;font-weight:700;color:var(--color-ink);">Nol Beban Operasional</div>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin:3px 0 0 0;">Tidak ada transaksi kas keluar pada periode terpilih.</p>
                </div>
                <?php else: ?>
                <div class="executive-card-body is-flex-col-centered space-y-3 sm:space-y-3.5 text-xs" style="padding: 24px;">
                    <?php foreach ($expenseBreakdown as $exp): 
                        $pct = ($totalBebanOperasional > 0) ? round(((float)$exp['total_nominal'] / $totalBebanOperasional) * 100, 1) : 0;
                    ?>
                    <div class="p-3 sm:p-3.5 rounded-xl space-y-2" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-center">
                            <span style="font-weight:700;font-size:12.5px;color:var(--color-ink);text-transform:capitalize;">
                                <?= htmlspecialchars(str_replace('_', ' ', (string)$exp['kategori'])) ?>
                            </span>
                            <span class="font-mono font-bold text-xs sm:text-[13px] flex-shrink-0 pl-2" style="color:var(--color-danger);">
                                <?= Format::rupiah((float)$exp['total_nominal']) ?>
                            </span>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;height:6px;background:var(--color-hairline);border-radius:10px;overflow:hidden;">
                                <div style="width:<?= $pct ?>%;height:100%;background:var(--color-danger);border-radius:10px;"></div>
                            </div>
                            <span style="font-size:10.5px;color:var(--color-ink-mute);font-weight:700;min-width:34px;text-align:right;"><?= $pct ?>%</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Footer Note -->
                <div class="executive-card-footer px-5 py-3 sm:px-6 sm:py-3.5 border-t text-center text-xs sm:text-[12px] text-ink-mute" style="padding: 14px 24px; border-top: 1px solid var(--color-hairline); background: var(--color-canvas-soft);">
                    Beban riil dari transaksi buku kas operasional
                </div>
            </div>

        </div>

        <!-- AGING PIUTANG KONSOLIDASI & TOP TOKO MENUNGGAK -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6 items-stretch">
            
            <!-- AGING BREAKDOWN KONSOLIDASI (2 KOLOM) -->
            <div class="lg:col-span-2 card overflow-hidden flex flex-col justify-between h-full" style="border-radius:18px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
                <!-- Header -->
                <div class="executive-card-header px-5 py-4 sm:px-6 sm:py-4.5 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="padding: 18px 24px; border-bottom: 1px solid var(--color-hairline); background: var(--color-canvas);">
                    <div class="flex items-center gap-3 min-w-0">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="hourglass" style="width:18px;height:18px;"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 style="font-size:14.5px;sm:font-size:15.5px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;">Aging Piutang Usaha Konsolidasi</h3>
                            <p style="font-size:11.5px;sm:font-size:12px;color:var(--color-ink-mute);margin:3px 0 0 0;line-height:1.35;">Grosir &amp; Konsinyasi berdasarkan hari jatuh tempo nota</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 self-start sm:self-auto flex-shrink-0 pl-11 sm:pl-0">
                        <span style="font-size:11.5px;color:var(--color-ink-mute);font-weight:700;">Total:</span>
                        <span class="font-mono font-black text-sm sm:text-base" style="color:var(--color-warning);">
                            <?= Format::rupiah((float)($agingSummary['total_outstanding'] ?? 0)) ?>
                        </span>
                    </div>
                </div>

                <!-- Body: 4 Bucket Cards -->
                <div class="executive-card-body is-flex-col-centered" style="padding: 24px;">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-3.5">
                        <!-- Lancar -->
                        <div class="p-3.5 sm:p-4 rounded-xl space-y-1.5 flex flex-col justify-between" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3px solid var(--color-success);">
                            <div style="font-size:11px;font-weight:700;color:var(--color-success);">Lancar (&le; Tempo)</div>
                            <div class="font-mono text-sm sm:text-base font-black" style="color:var(--color-ink);">
                                <?= Format::rupiah((float)($agingSummary['piutang_lancar'] ?? 0)) ?>
                            </div>
                            <div style="font-size:10px;color:var(--color-ink-mute);">Aman &amp; Tertib</div>
                        </div>

                        <!-- Overdue 1-14 -->
                        <div class="p-3.5 sm:p-4 rounded-xl space-y-1.5 flex flex-col justify-between" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3px solid #eab308;">
                            <div style="font-size:11px;font-weight:700;color:#eab308;">Overdue 1–14 Hari</div>
                            <div class="font-mono text-sm sm:text-base font-black" style="color:var(--color-ink);">
                                <?= Format::rupiah((float)($agingSummary['overdue_1_14'] ?? 0)) ?>
                            </div>
                            <div style="font-size:10px;color:var(--color-ink-mute);">Perlu Follow Up</div>
                        </div>

                        <!-- Overdue 15-30 -->
                        <div class="p-3.5 sm:p-4 rounded-xl space-y-1.5 flex flex-col justify-between" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3px solid var(--color-warning);">
                            <div style="font-size:11px;font-weight:700;color:var(--color-warning);">Overdue 15–30 Hari</div>
                            <div class="font-mono text-sm sm:text-base font-black" style="color:var(--color-ink);">
                                <?= Format::rupiah((float)($agingSummary['overdue_15_30'] ?? 0)) ?>
                            </div>
                            <div style="font-size:10px;color:var(--color-ink-mute);">Perhatian Khusus</div>
                        </div>

                        <!-- Overdue > 30 -->
                        <div class="p-3.5 sm:p-4 rounded-xl space-y-1.5 flex flex-col justify-between" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3px solid var(--color-danger);">
                            <div style="font-size:11px;font-weight:700;color:var(--color-danger);">&gt; 30 Hari (Kritis)</div>
                            <div class="font-mono text-sm sm:text-base font-black" style="color:var(--color-danger);">
                                <?= Format::rupiah((float)($agingSummary['overdue_over_30'] ?? 0)) ?>
                            </div>
                            <div style="font-size:10px;color:var(--color-ink-mute);">Potensi Macet</div>
                        </div>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="executive-card-footer px-5 py-3 sm:px-6 sm:py-3.5 border-t text-center text-xs sm:text-[12px] text-ink-mute" style="padding: 14px 24px; border-top: 1px solid var(--color-hairline); background: var(--color-canvas-soft);">
                    Diperbarui real-time dari sisa saldo nota penjualan grosir &amp; tagihan konsinyasi
                </div>
            </div>

            <!-- TOP 5 TOKO DENGAN PIUTANG TERBESAR -->
            <div class="card overflow-hidden flex flex-col justify-between h-full" style="border-radius:18px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
                <!-- Header -->
                <div class="executive-card-header px-5 py-4 sm:px-6 sm:py-4.5 border-b flex items-center gap-3" style="padding: 18px 24px; border-bottom: 1px solid var(--color-hairline); background: var(--color-canvas);">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(239,68,68,0.1);color:var(--color-danger);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="alert-triangle" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 style="font-size:14.5px;sm:font-size:15.5px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;">Top Penunggak Piutang</h3>
                        <p style="font-size:11.5px;sm:font-size:12px;color:var(--color-ink-mute);margin:3px 0 0 0;line-height:1.25;">5 Toko Tagihan Tertinggi</p>
                    </div>
                </div>

                <!-- Body -->
                <?php if (empty($unpaidStoreList)): ?>
                <div class="executive-card-body is-centered" style="padding: 24px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:rgba(16,185,129,0.1);color:var(--color-success);display:flex;align-items:center;justify-content:center;margin:0 auto 10px auto;flex-shrink:0;">
                        <i data-lucide="check-circle-2" style="width:22px;height:22px;"></i>
                    </div>
                    <div style="font-size:13.5px;font-weight:700;color:var(--color-ink);">Seluruh Tagihan Lunas</div>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin:3px 0 0 0;">Tidak ada piutang toko yang menunggak saat ini.</p>
                </div>
                <?php else: ?>
                <div class="executive-card-body is-flex-col-centered space-y-2.5 text-xs max-h-[340px] overflow-y-auto no-scrollbar" style="padding: 24px;">
                    <?php foreach ($unpaidStoreList as $us): 
                        $storePhone = preg_replace('/[^0-9]/', '', (string)($us['nomor_whatsapp'] ?? $us['nomor_telepon'] ?? ''));
                        if (str_starts_with($storePhone, '0')) {
                            $storePhone = '62' . substr($storePhone, 1);
                        }
                    ?>
                    <div class="p-3 sm:p-3.5 rounded-xl space-y-1.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div style="font-weight:700;font-size:13px;color:var(--color-ink);"><?= htmlspecialchars($us['nama_toko']) ?></div>
                                <div class="flex items-center gap-2.5 text-[11px] mt-1" style="color:var(--color-ink-mute);">
                                    <span>Sales: <?= htmlspecialchars($us['nama_sales'] ?? '—') ?></span>
                                    <?php if (!empty($storePhone)): ?>
                                    <a href="https://wa.me/<?= $storePhone ?>?text=<?= urlencode('Halo ' . $us['nama_toko'] . ', konfirmasi sisa tagihan Keren Snack sebesar ' . Format::rupiah((float)$us['total_sisa_tagihan']) . '. Terima kasih.') ?>" 
                                       target="_blank"
                                       class="badge badge-success text-[10px] hover:opacity-80 inline-flex items-center gap-1 flex-shrink-0 px-2 py-0.5 rounded-md" style="font-family:inherit;" title="Kirim Pengingat WhatsApp">
                                        <i data-lucide="message-circle" style="width:11px;height:11px;"></i>
                                        <span>WA</span>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="font-mono font-black text-xs sm:text-sm flex-shrink-0" style="color:var(--color-danger);"><?= Format::rupiah((float)$us['total_sisa_tagihan']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Footer Note -->
                <div class="executive-card-footer px-5 py-3 sm:px-6 sm:py-3.5 border-t text-center text-xs sm:text-[12px] text-ink-mute" style="padding: 14px 24px; border-top: 1px solid var(--color-hairline); background: var(--color-canvas-soft);">
                    Hubungi via WhatsApp untuk konfirmasi penagihan
                </div>
            </div>

        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: CHANNEL PENJUALAN & TOP PRODUK SKU                                 -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'sales'" x-cloak class="space-y-5 sm:space-y-6">
        
        <!-- 3 CHANNEL PENJUALAN BREAKDOWN -->
        <div class="card overflow-hidden" style="border-radius:18px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
            <div class="executive-card-header px-5 py-4 sm:px-6 sm:py-4.5 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="padding: 18px 24px; border-bottom: 1px solid var(--color-hairline); background: var(--color-canvas);">
                <div class="flex items-center gap-3 min-w-0">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="layers" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 style="font-size:14.5px;sm:font-size:15.5px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;">Distribusi Omzet Penjualan Multi-Channel</h2>
                        <p style="font-size:11.5px;sm:font-size:12px;color:var(--color-ink-mute);margin:3px 0 0 0;line-height:1.35;">Kasir Retail POS &bull; Grosir Direct &bull; Rak Konsinyasi</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-auto flex-shrink-0 pl-11 sm:pl-0">
                    <span style="font-size:11.5px;color:var(--color-ink-mute);font-weight:700;">Total:</span>
                    <span class="font-mono font-black text-sm sm:text-base" style="color:var(--color-success);"><?= Format::rupiah($totalOmzet) ?></span>
                </div>
            </div>

            <?php
            $omzetPos = (float)($channelBreakdown['omzet_pos'] ?? 0);
            $omzetGrosir = (float)($channelBreakdown['omzet_grosir'] ?? 0);
            $omzetKonsinyasi = (float)($channelBreakdown['omzet_konsinyasi'] ?? 0);
            $pctPos = ($totalOmzet > 0) ? round(($omzetPos / $totalOmzet) * 100, 1) : 0;
            $pctGrosir = ($totalOmzet > 0) ? round(($omzetGrosir / $totalOmzet) * 100, 1) : 0;
            $pctKonsinyasi = ($totalOmzet > 0) ? round(($omzetKonsinyasi / $totalOmzet) * 100, 1) : 0;
            ?>

            <div class="executive-card-body is-flex-col-centered" style="padding: 24px;">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
                    <!-- POS Retail -->
                    <div class="p-4 sm:p-5 rounded-2xl space-y-2.5 flex flex-col justify-between" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3.5px solid #3b82f6;">
                        <div class="flex items-center justify-between">
                            <span style="font-size:12px;font-weight:800;color:var(--color-ink-mute);">1. Kasir Toko Retail (POS)</span>
                            <span class="badge font-bold text-[10.5px] px-2 py-0.5 rounded" style="background:rgba(59,130,246,0.12);color:#3b82f6;font-family:inherit;"><?= $pctPos ?>%</span>
                        </div>
                        <div class="font-mono text-lg sm:text-xl font-black" style="color:var(--color-ink);"><?= Format::rupiah($omzetPos) ?></div>
                        <p style="font-size:11.5px;color:var(--color-ink-mute);margin:0;">Penjualan langsung kasir (Tunai &amp; QRIS)</p>
                    </div>

                    <!-- Grosir Direct Order -->
                    <div class="p-4 sm:p-5 rounded-2xl space-y-2.5 flex flex-col justify-between" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3.5px solid var(--color-primary);">
                        <div class="flex items-center justify-between">
                            <span style="font-size:12px;font-weight:800;color:var(--color-ink-mute);">2. Grosir / Toko Direct</span>
                            <span class="badge badge-primary font-bold text-[10.5px] px-2 py-0.5 rounded" style="font-family:inherit;"><?= $pctGrosir ?>%</span>
                        </div>
                        <div class="font-mono text-lg sm:text-xl font-black" style="color:var(--color-ink);"><?= Format::rupiah($omzetGrosir) ?></div>
                        <p style="font-size:11.5px;color:var(--color-ink-mute);margin:0;">Faktur order bal-balan / tempo langsung toko</p>
                    </div>

                    <!-- Konsinyasi Rak Toko -->
                    <div class="p-4 sm:p-5 rounded-2xl space-y-2.5 flex flex-col justify-between" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-top:3.5px solid #f59e0b;">
                        <div class="flex items-center justify-between">
                            <span style="font-size:12px;font-weight:800;color:var(--color-ink-mute);">3. Rak Toko Konsinyasi</span>
                            <span class="badge badge-warning font-bold text-[10.5px] px-2 py-0.5 rounded" style="font-family:inherit;"><?= $pctKonsinyasi ?>%</span>
                        </div>
                        <div class="font-mono text-lg sm:text-xl font-black" style="color:var(--color-ink);"><?= Format::rupiah($omzetKonsinyasi) ?></div>
                        <p style="font-size:11.5px;color:var(--color-ink-mute);margin:0;">Omzet laku dari kunjungan opname sales</p>
                    </div>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="executive-card-footer px-5 py-3 sm:px-6 sm:py-3.5 border-t text-center text-xs sm:text-[12px] text-ink-mute" style="padding: 14px 24px; border-top: 1px solid var(--color-hairline); background: var(--color-canvas-soft);">
                Total omzet menggabungkan penjualan ritel harian, pesanan grosir, dan penagihan konsinyasi
            </div>
        </div>

        <!-- TOP 5 PRODUK SKU TERLARIS & MARGIN -->
        <div class="card overflow-hidden" style="border-radius:18px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
            <div class="executive-card-header px-5 py-4 sm:px-6 sm:py-4.5 border-b flex items-center justify-between gap-3" style="padding: 18px 24px; border-bottom: 1px solid var(--color-hairline); background: var(--color-canvas);">
                <div class="flex items-center gap-3 min-w-0">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="award" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 style="font-size:14.5px;sm:font-size:15.5px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;">Top 5 SKU Produk Terlaris</h3>
                        <p style="font-size:11.5px;sm:font-size:12px;color:var(--color-ink-mute);margin:3px 0 0 0;line-height:1.25;">Berdasarkan total omzet penjualan periode terpilih</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto no-scrollbar w-full">
                <table class="table w-full text-sm" style="margin:0;">
                    <thead>
                        <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                            <th style="padding:14px 20px;text-align:left;font-weight:800;color:var(--color-ink-mute);font-size:11.5px;letter-spacing:0.04em;text-transform:uppercase;">SKU &amp; Nama Produk</th>
                            <th class="cell-center" style="padding:14px 20px;font-weight:800;color:var(--color-ink-mute);font-size:11.5px;letter-spacing:0.04em;text-transform:uppercase;">Kuantitas</th>
                            <th class="cell-right" style="padding:14px 20px;font-weight:800;color:var(--color-ink-mute);font-size:11.5px;letter-spacing:0.04em;text-transform:uppercase;">Total Omzet</th>
                            <th class="cell-right" style="padding:14px 20px;font-weight:800;color:var(--color-ink-mute);font-size:11.5px;letter-spacing:0.04em;text-transform:uppercase;">Laba Kotor SKU</th>
                            <th class="cell-center" style="padding:14px 20px;font-weight:800;color:var(--color-ink-mute);font-size:11.5px;letter-spacing:0.04em;text-transform:uppercase;">Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topSkuList)): ?>
                        <tr>
                            <td colspan="5" class="cell-center py-16 sm:py-20 text-center" style="color:var(--color-ink-mute);">
                                <div style="width:44px;height:44px;border-radius:50%;background:rgba(245,158,11,0.1);color:var(--color-warning);display:flex;align-items:center;justify-content:center;margin:0 auto 10px auto;">
                                    <i data-lucide="package" style="width:22px;height:22px;"></i>
                                </div>
                                <div style="font-size:14px;font-weight:700;color:var(--color-ink);">Belum Ada Data Penjualan</div>
                                <p style="font-size:11.5px;color:var(--color-ink-mute);margin:3px 0 0 0;">Tidak ada transaksi produk SKU tercatat pada periode ini.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($topSkuList as $sku): 
                            $skuOmzet = (float)$sku['total_omzet'];
                            $skuMarginRp = (float)$sku['laba_kotor_sku'];
                            $skuMarginPct = ($skuOmzet > 0) ? round(($skuMarginRp / $skuOmzet) * 100, 1) : 0;
                        ?>
                        <tr class="hover:bg-canvas-soft/50 transition-colors" style="border-bottom:1px solid var(--color-hairline);">
                            <td style="padding:14px 20px;">
                                <div style="font-weight:700;font-size:13.5px;color:var(--color-ink);"><?= htmlspecialchars($sku['nama_item']) ?></div>
                                <div class="font-mono text-[11px] mt-0.5" style="color:var(--color-ink-mute);"><?= htmlspecialchars($sku['kode_sku']) ?></div>
                            </td>
                            <td class="cell-center font-bold font-mono text-sm" style="padding:14px 20px;white-space:nowrap;">
                                <?= number_format((float)$sku['total_qty']) ?> <?= htmlspecialchars($sku['satuan_dasar'] ?? 'pcs') ?>
                            </td>
                            <td class="cell-right font-mono font-bold text-sm" style="color:var(--color-ink);padding:14px 20px;white-space:nowrap;">
                                <?= Format::rupiah($skuOmzet) ?>
                            </td>
                            <td class="cell-right font-mono font-black text-sm" style="color:var(--color-success);padding:14px 20px;white-space:nowrap;">
                                <?= Format::rupiah($skuMarginRp) ?>
                            </td>
                            <td class="cell-center" style="padding:14px 20px;">
                                <span class="badge badge-success font-bold text-[10.5px] px-2.5 py-1 rounded-md" style="font-family:inherit;">
                                    <?= $skuMarginPct ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer Note -->
            <div class="executive-card-footer px-5 py-3 sm:px-6 sm:py-3.5 border-t text-center text-xs sm:text-[12px] text-ink-mute" style="padding: 14px 24px; border-top: 1px solid var(--color-hairline); background: var(--color-canvas-soft);">
                Laba kotor SKU = Omzet penjualan dikurangi HPP bahan baku dan bahan kemasan standar
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 3: PABRIK & PRODUKSI SNACK (MANUFACTURING KPI)                         -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'factory'" x-cloak class="space-y-5 sm:space-y-6">
        
        <!-- 4 KPI PABRIK -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4.5">
            <!-- Total Output Produksi -->
            <div class="card p-4 sm:p-4.5 space-y-2.5 flex flex-col justify-between h-full rounded-2xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-primary);box-shadow:var(--shadow-1);">
                <div class="flex items-center justify-between">
                    <span style="font-size:10.5px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;">Total Output</span>
                    <i data-lucide="package-check" style="width:16px;height:16px;color:var(--color-primary);"></i>
                </div>
                <div class="font-mono text-lg sm:text-xl font-black" style="color:var(--color-ink);">
                    <?= number_format((int)($productionSummary['total_pcs'] ?? 0)) ?> <span style="font-size:11.5px;font-weight:600;">pcs</span>
                </div>
                <div style="font-size:10.5px;color:var(--color-ink-mute);padding-top:6px;border-top:1px dashed var(--color-hairline);">
                    Setara <?= number_format((int)($productionSummary['total_bal'] ?? 0)) ?> Bal snack
                </div>
            </div>

            <!-- Pekerja Borongan Aktif -->
            <div class="card p-4 sm:p-4.5 space-y-2.5 flex flex-col justify-between h-full rounded-2xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);box-shadow:var(--shadow-1);">
                <div class="flex items-center justify-between">
                    <span style="font-size:10.5px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;">Pekerja Borongan</span>
                    <i data-lucide="users" style="width:16px;height:16px;color:var(--color-success);"></i>
                </div>
                <div class="font-mono text-lg sm:text-xl font-black" style="color:var(--color-success);">
                    <?= number_format($totalPekerjaAktif ?? (int)($productionSummary['total_pekerja_aktif'] ?? 0)) ?> <span style="font-size:11.5px;font-weight:600;">Orang</span>
                </div>
                <div style="font-size:10.5px;color:var(--color-ink-mute);padding-top:6px;border-top:1px dashed var(--color-hairline);">
                    Rata-rata: <?= number_format($rataRataOutputPerPekerja ?? (int)($productionSummary['rata_rata_output_per_pekerja'] ?? 0)) ?> pcs / org
                </div>
            </div>

            <!-- Upah Borongan Pabrik -->
            <div class="card p-4 sm:p-4.5 space-y-2.5 flex flex-col justify-between h-full rounded-2xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-warning);box-shadow:var(--shadow-1);">
                <div class="flex items-center justify-between">
                    <span style="font-size:10.5px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;">Upah Borongan</span>
                    <i data-lucide="coins" style="width:16px;height:16px;color:var(--color-warning);"></i>
                </div>
                <div class="font-mono text-lg sm:text-xl font-black" style="color:var(--color-warning);">
                    <?= Format::rupiah((float)($productionSummary['total_upah_borongan'] ?? 0)) ?>
                </div>
                <div style="font-size:10.5px;color:var(--color-ink-mute);padding-top:6px;border-top:1px dashed var(--color-hairline);">
                    Rata-rata: Rp <?= number_format((float)($rataRataUpahPerPcs ?? $productionSummary['rata_rata_upah_per_pcs'] ?? 0), 0, ',', '.') ?> / pcs
                </div>
            </div>

            <!-- Hari Aktif Produksi -->
            <div class="card p-4 sm:p-4.5 space-y-2.5 flex flex-col justify-between h-full rounded-2xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid #8b5cf6;box-shadow:var(--shadow-1);">
                <div class="flex items-center justify-between">
                    <span style="font-size:10.5px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;">Hari Kerja</span>
                    <i data-lucide="calendar" style="width:16px;height:16px;color:#8b5cf6;"></i>
                </div>
                <div class="font-mono text-lg sm:text-xl font-black" style="color:#8b5cf6;">
                    <?= (int)($productionSummary['hari_produksi_aktif'] ?? 0) ?> Hari
                </div>
                <div style="font-size:10.5px;color:var(--color-ink-mute);padding-top:6px;border-top:1px dashed var(--color-hairline);">
                    Lembur: <?= number_format((int)($productionSummary['total_lembur_pcs'] ?? 0)) ?> pcs
                </div>
            </div>
        </div>

        <!-- TOP PRODUK HASIL PRODUKSI -->
        <div class="card overflow-hidden" style="border-radius:18px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
            <div class="executive-card-header px-5 py-4 sm:px-6 sm:py-4.5 border-b flex items-center justify-between gap-3" style="padding: 18px 24px; border-bottom: 1px solid var(--color-hairline); background: var(--color-canvas);">
                <div class="flex items-center gap-3 min-w-0">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(59,130,246,0.12);color:#3b82f6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="boxes" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 style="font-size:14.5px;sm:font-size:15.5px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;">Top Output Produksi Pabrik</h3>
                        <p style="font-size:11.5px;sm:font-size:12px;color:var(--color-ink-mute);margin:3px 0 0 0;line-height:1.25;">Akumulasi volume produksi &amp; alokasi upah borongan</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto no-scrollbar w-full">
                <table class="table w-full text-sm" style="margin:0;">
                    <thead>
                        <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                            <th style="padding:14px 20px;text-align:left;font-weight:800;color:var(--color-ink-mute);font-size:11.5px;letter-spacing:0.04em;text-transform:uppercase;">SKU &amp; Nama Produk Jadi</th>
                            <th class="cell-center" style="padding:14px 20px;font-weight:800;color:var(--color-ink-mute);font-size:11.5px;letter-spacing:0.04em;text-transform:uppercase;">Output Pcs</th>
                            <th class="cell-center" style="padding:14px 20px;font-weight:800;color:var(--color-ink-mute);font-size:11.5px;letter-spacing:0.04em;text-transform:uppercase;">Output Bal</th>
                            <th class="cell-right" style="padding:14px 20px;font-weight:800;color:var(--color-ink-mute);font-size:11.5px;letter-spacing:0.04em;text-transform:uppercase;">Total Upah Borongan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topProducedItems)): ?>
                        <tr>
                            <td colspan="4" class="cell-center py-16 sm:py-20 text-center" style="color:var(--color-ink-mute);">
                                <div style="width:44px;height:44px;border-radius:50%;background:rgba(59,130,246,0.1);color:#3b82f6;display:flex;align-items:center;justify-content:center;margin:0 auto 10px auto;">
                                    <i data-lucide="boxes" style="width:22px;height:22px;"></i>
                                </div>
                                <div style="font-size:14px;font-weight:700;color:var(--color-ink);">Belum Ada Catatan Produksi</div>
                                <p style="font-size:11.5px;color:var(--color-ink-mute);margin:3px 0 0 0;">Belum ada catatan batch produksi harian pada periode ini.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($topProducedItems as $tpi): ?>
                        <tr class="hover:bg-canvas-soft/50 transition-colors" style="border-bottom:1px solid var(--color-hairline);">
                            <td style="padding:14px 20px;">
                                <div style="font-weight:700;font-size:13.5px;color:var(--color-ink);"><?= htmlspecialchars($tpi['nama_item']) ?></div>
                                <div class="font-mono text-[11px] mt-0.5" style="color:var(--color-ink-mute);"><?= htmlspecialchars($tpi['kode_sku']) ?></div>
                            </td>
                            <td class="cell-center font-mono font-bold text-sm" style="color:var(--color-primary);padding:14px 20px;white-space:nowrap;">
                                <?= number_format((int)$tpi['total_pcs']) ?> pcs
                            </td>
                            <td class="cell-center font-mono font-bold text-sm" style="padding:14px 20px;white-space:nowrap;">
                                <?= number_format((int)$tpi['total_bal']) ?> bal
                            </td>
                            <td class="cell-right font-mono font-black text-sm" style="color:var(--color-warning);padding:14px 20px;white-space:nowrap;">
                                <?= Format::rupiah((float)$tpi['total_upah']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer Note -->
            <div class="executive-card-footer px-5 py-3 sm:px-6 sm:py-3.5 border-t text-center text-xs sm:text-[12px] text-ink-mute" style="padding: 14px 24px; border-top: 1px solid var(--color-hairline); background: var(--color-canvas-soft);">
                Data diambil langsung dari rekapitulasi batch lembar kerja produksi harian mandor
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 4: MITRA KONSINYASI & TIM SALES LEADERBOARD                           -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'consignment'" x-cloak class="space-y-5 sm:space-y-6">
        
        <!-- 2-COLUMN GRID: LEADERBOARD SALES & TOP TOKO -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6 items-stretch">
            
            <!-- LEADERBOARD SALES & KOMISI -->
            <div class="card overflow-hidden flex flex-col justify-between h-full" style="border-radius:18px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
                <!-- Header -->
                <div class="executive-card-header px-5 py-4 sm:px-6 sm:py-4.5 border-b flex items-center gap-3" style="padding: 18px 24px; border-bottom: 1px solid var(--color-hairline); background: var(--color-canvas);">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(37,99,235,0.12);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="medal" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 style="font-size:14.5px;sm:font-size:15.5px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;">Leaderboard Tim Sales</h3>
                        <p style="font-size:11.5px;sm:font-size:12px;color:var(--color-ink-mute);margin:3px 0 0 0;line-height:1.25;">Berdasarkan omzet tertagih toko binaan</p>
                    </div>
                </div>

                <!-- Body -->
                <?php if (empty($salesLeaderboard)): ?>
                <div class="executive-card-body is-centered" style="padding: 24px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:rgba(100,116,139,0.1);color:var(--color-ink-mute);display:flex;align-items:center;justify-content:center;margin:0 auto 10px auto;flex-shrink:0;">
                        <i data-lucide="info" style="width:22px;height:22px;"></i>
                    </div>
                    <div style="font-size:13.5px;font-weight:600;color:var(--color-ink-mute);">Belum ada data sales yang tercatat.</div>
                </div>
                <?php else: ?>
                <div class="executive-card-body is-flex-col-centered space-y-3 sm:space-y-3.5 text-xs" style="padding: 24px;">
                    <?php foreach ($salesLeaderboard as $sc): ?>
                    <div class="p-3.5 sm:p-4 rounded-xl space-y-2" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div style="font-weight:800;font-size:13.5px;color:var(--color-ink);"><?= htmlspecialchars($sc['nama_karyawan']) ?></div>
                                <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;"><?= (int)$sc['total_toko_binaan'] ?> Toko Binaan Tetap</div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="font-mono font-black text-sm sm:text-base" style="color:var(--color-ink);"><?= Format::rupiah((float)$sc['total_omzet_laku']) ?></span>
                                <div style="font-size:10.5px;color:var(--color-ink-mute);">Omzet Tertagih</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t text-xs" style="border-color:var(--color-hairline);">
                            <span class="badge badge-primary font-bold text-[10px] px-2.5 py-0.5 rounded-md" style="font-family:inherit;"><?= htmlspecialchars($sc['nama_tier']) ?></span>
                            <span class="font-bold" style="color:var(--color-success);">
                                Komisi (<?= (float)$sc['persentase_komisi'] ?>%): <?= Format::rupiah((float)$sc['estimasi_komisi_rp']) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Footer Note -->
                <div class="executive-card-footer px-5 py-3 sm:px-6 sm:py-3.5 border-t text-center text-xs sm:text-[12px] text-ink-mute" style="padding: 14px 24px; border-top: 1px solid var(--color-hairline); background: var(--color-canvas-soft);">
                    Komisi dihitung berdasarkan omzet laku kunjungan sales
                </div>
            </div>

            <!-- TOP 5 TOKO KONSINYASI PENYUMBANG OMZET -->
            <div class="card overflow-hidden flex flex-col justify-between h-full" style="border-radius:18px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
                <!-- Header -->
                <div class="executive-card-header px-5 py-4 sm:px-6 sm:py-4.5 border-b flex items-center gap-3" style="padding: 18px 24px; border-bottom: 1px solid var(--color-hairline); background: var(--color-canvas);">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="store" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 style="font-size:14.5px;sm:font-size:15.5px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;">Top 5 Toko Konsinyasi Terproduktif</h3>
                        <p style="font-size:11.5px;sm:font-size:12px;color:var(--color-ink-mute);margin:3px 0 0 0;line-height:1.25;">Mitra dengan penjualan rak tertinggi</p>
                    </div>
                </div>

                <!-- Body -->
                <?php if (empty($topStores)): ?>
                <div class="executive-card-body is-centered" style="padding: 24px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:rgba(100,116,139,0.1);color:var(--color-ink-mute);display:flex;align-items:center;justify-content:center;margin:0 auto 10px auto;flex-shrink:0;">
                        <i data-lucide="info" style="width:22px;height:22px;"></i>
                    </div>
                    <div style="font-size:13.5px;font-weight:600;color:var(--color-ink-mute);">Belum ada riwayat kunjungan toko pada periode ini.</div>
                </div>
                <?php else: ?>
                <div class="executive-card-body is-flex-col-centered space-y-2.5 text-xs" style="padding: 24px;">
                    <?php foreach ($topStores as $ts): ?>
                    <div class="flex items-center justify-between p-3.5 sm:p-4 rounded-xl gap-3" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="min-w-0">
                            <div style="font-weight:700;font-size:13.5px;color:var(--color-ink);"><?= htmlspecialchars($ts['nama_toko']) ?></div>
                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;"><?= $ts['total_kunjungan'] ?> kali opname/kunjungan periode ini</div>
                        </div>
                        <span class="font-mono font-black text-sm sm:text-base flex-shrink-0" style="color:var(--color-success);"><?= Format::rupiah((float)$ts['total_omzet']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Footer Note -->
                <div class="executive-card-footer px-5 py-3 sm:px-6 sm:py-3.5 border-t text-center text-xs sm:text-[12px] text-ink-mute" style="padding: 14px 24px; border-top: 1px solid var(--color-hairline); background: var(--color-canvas-soft);">
                    Diakumulasi dari total penjualan riil di rak mitra toko
                </div>
            </div>

        </div>

        <!-- 2-COLUMN: EARLY WARNING TOKO OVERDUE (>14 HARI) & KERUGIAN RETUR RUSAK -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6 items-stretch">
            
            <!-- WARNING: TOKO KONSINYASI OVERDUE > 14 HARI TIDAK DIKUNJUNGI -->
            <div class="card overflow-hidden flex flex-col justify-between h-full" style="border-radius:18px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
                <!-- Header -->
                <div class="executive-card-header px-5 py-4 sm:px-6 sm:py-4.5 border-b flex items-center gap-3" style="padding: 18px 24px; border-bottom: 1px solid var(--color-hairline); background: var(--color-canvas);">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="alert-triangle" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 style="font-size:14.5px;sm:font-size:15.5px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;">Toko Perlu Kunjungan (&gt;14 Hari) <span class="badge badge-warning font-bold text-[10px] px-2 py-0.5 rounded-md align-middle inline-flex items-center ml-1.5" style="font-family:inherit;vertical-align:middle;"><?= count($overdueStores) ?> Toko</span></h3>
                        <p style="font-size:11.5px;sm:font-size:12px;color:var(--color-ink-mute);margin:3px 0 0 0;line-height:1.25;">Monitoring disiplin jadwal opname sales</p>
                    </div>
                </div>

                <!-- Body -->
                <?php if (empty($overdueStores)): ?>
                <div class="executive-card-body is-centered" style="padding: 24px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:rgba(16,185,129,0.1);color:var(--color-success);display:flex;align-items:center;justify-content:center;margin:0 auto 10px auto;flex-shrink:0;">
                        <i data-lucide="check-circle-2" style="width:22px;height:22px;"></i>
                    </div>
                    <div style="font-size:13.5px;font-weight:700;color:var(--color-success);">Jadwal Kunjungan Tertib</div>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin:3px 0 0 0;">Semua toko mitra aktif rutin dikunjungi &le; 14 hari.</p>
                </div>
                <?php else: ?>
                <div class="executive-card-body is-flex-col-centered space-y-2.5 max-h-[320px] overflow-y-auto no-scrollbar" style="padding: 24px;">
                    <?php foreach ($overdueStores as $os): ?>
                    <div class="p-3 sm:p-3.5 rounded-xl space-y-1.5 text-xs" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-start gap-3">
                            <span style="font-weight:700;font-size:13px;color:var(--color-ink);"><?= htmlspecialchars($os['nama_toko']) ?></span>
                            <span class="badge badge-danger font-bold text-[10px] px-2 py-0.5 rounded-md flex-shrink-0" style="font-family:inherit;">
                                <?= ($os['hari_tidak_dikunjungi'] >= 900) ? 'Belum Pernah' : $os['hari_tidak_dikunjungi'] . ' hari lalu' ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-[11px]" style="color:var(--color-ink-mute);">
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
                               class="badge badge-success text-[10px] hover:opacity-80 inline-flex items-center gap-1 flex-shrink-0 px-2 py-0.5 rounded-md" style="font-family:inherit;" title="Chat WhatsApp Toko">
                                <i data-lucide="message-circle" style="width:11px;height:11px;"></i>
                                <span>WA</span>
                            </a>
                            <?php else: ?>
                            <span class="flex-shrink-0">WA: —</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Footer Note -->
                <div class="executive-card-footer px-5 py-3 sm:px-6 sm:py-3.5 border-t text-center text-xs sm:text-[12px] text-ink-mute" style="padding: 14px 24px; border-top: 1px solid var(--color-hairline); background: var(--color-canvas-soft);">
                    Toko yang tidak dikunjungi &gt;14 hari berisiko kehilangan omzet dan stok basi
                </div>
            </div>

            <!-- LAPORAN KERUGIAN RETUR RUSAK (BS) -->
            <div class="card overflow-hidden flex flex-col justify-between h-full" style="border-radius:18px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
                <!-- Header -->
                <div class="executive-card-header px-5 py-4 sm:px-6 sm:py-4.5 border-b flex items-center gap-3" style="padding: 18px 24px; border-bottom: 1px solid var(--color-hairline); background: var(--color-canvas);">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(239,68,68,0.12);color:var(--color-danger);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="package-x" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 style="font-size:14.5px;sm:font-size:15.5px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;">Evaluasi Kerugian Retur Rusak (BS) Toko</h3>
                        <p style="font-size:11.5px;sm:font-size:12px;color:var(--color-ink-mute);margin:3px 0 0 0;line-height:1.25;">Beban kerugian HPP akibat barang bocor / remuk</p>
                    </div>
                </div>

                <!-- Body -->
                <div class="executive-card-body is-flex-col-centered space-y-3.5 sm:space-y-4" style="padding: 24px;">
                    <div class="p-3.5 sm:p-4 rounded-xl space-y-1.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="font-size:11px;color:var(--color-ink-mute);font-weight:800;text-transform:uppercase;">Total Nilai Kerugian HPP:</div>
                        <div class="text-base sm:text-xl font-black font-mono" style="color:var(--color-danger);">
                            <?= Format::rupiah((float)($lossReport['total_kerugian_rusak'] ?? 0)) ?>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);">
                            <?= (int)($lossReport['total_pcs_rusak'] ?? 0) ?> pcs snack rusak ditarik dari rak toko
                        </div>
                    </div>

                    <div class="space-y-2 pt-1 text-xs sm:text-sm">
                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:var(--color-ink-mute);">SKU Paling Sering Rusak di Toko:</div>
                        <?php if (empty($topDamagedItems)): ?>
                        <div class="text-xs italic py-3" style="color:var(--color-ink-mute);">Nol laporan retur rusak pada periode ini.</div>
                        <?php else: ?>
                        <?php foreach ($topDamagedItems as $tdi): ?>
                        <div class="flex justify-between items-center py-2 border-b gap-3" style="border-color:var(--color-hairline);">
                            <span style="color:var(--color-ink);font-weight:600;font-size:13px;"><?= htmlspecialchars($tdi['nama_item']) ?></span>
                            <span class="font-bold font-mono text-xs sm:text-sm flex-shrink-0" style="color:var(--color-danger);">
                                <?= (int)$tdi['total_pcs_rusak'] ?> pcs (<?= Format::rupiah((float)$tdi['total_nominal_kerugian']) ?>)
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="executive-card-footer px-5 py-3 sm:px-6 sm:py-3.5 border-t text-center text-xs sm:text-[12px] text-ink-mute" style="padding: 14px 24px; border-top: 1px solid var(--color-hairline); background: var(--color-canvas-soft);">
                    Diambil langsung dari pencatatan riil retur opname konsinyasi
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

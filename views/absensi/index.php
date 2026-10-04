<?php
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CSRF;

ob_start();

$kemarin = date('Y-m-d', strtotime($tanggal . ' -1 day'));
$besok = date('Y-m-d', strtotime($tanggal . ' +1 day'));
$isToday = ($tanggal === date('Y-m-d'));
$isFuture = ($tanggal > date('Y-m-d'));

$todayTs = strtotime(date('Y-m-d'));
$targetTs = strtotime($tanggal);
$diffDays = (int)round(($targetTs - $todayTs) / 86400);

$hariList = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$namaHari = $hariList[(int)date('w', strtotime($tanggal))];
$tanggalLengkap = $namaHari . ', ' . Format::tanggal($tanggal, false, false);
?>

<style>
/* =========================================================================
   MODUL KEHADIRAN / ABSENSI — 100% RESPONSIVE & iOS / MOBILE NATIVE UI
   ========================================================================= */

/* iOS / Touchscreen Core Ergonomics */
* {
    -webkit-tap-highlight-color: transparent;
}
button, select {
    touch-action: manipulation;
}
input[type="text"], input[type="number"], input[type="search"], textarea {
    touch-action: manipulation;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

/* 1. Segmented Control for Radio Status Pills */
.status-pill-group {
    display: inline-flex;
    align-items: center;
    background: #f1f5f9;
    padding: 3px;
    border-radius: 9px;
    border: 1px solid #e2e8f0;
    gap: 2px;
    user-select: none;
    -webkit-user-select: none;
}
.dark .status-pill-group {
    background: #1e293b;
    border-color: #334155;
}
.status-pill-item {
    position: relative;
    cursor: pointer;
    margin: 0;
    user-select: none;
    -webkit-user-select: none;
}
.status-pill-item input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    pointer-events: none;
}
.status-pill-content {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 6px 10px;
    font-size: 11.5px;
    font-weight: 600;
    border-radius: 6px;
    color: #64748b;
    transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
}
.status-pill-item:hover .status-pill-content {
    color: #0f172a;
    background: rgba(255, 255, 255, 0.7);
}
.dark .status-pill-item:hover .status-pill-content {
    color: #f8fafc;
    background: rgba(255, 255, 255, 0.08);
}

/* Checked States */
.status-pill-item input[value="hadir"]:checked + .status-pill-content {
    background: #10b981 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(16, 185, 129, 0.35);
}
.status-pill-item input[value="izin"]:checked + .status-pill-content {
    background: #f59e0b !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(245, 158, 11, 0.35);
}
.status-pill-item input[value="sakit"]:checked + .status-pill-content {
    background: #ea580c !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(234, 88, 12, 0.35);
}
.status-pill-item input[value="libur"]:checked + .status-pill-content {
    background: #0284c7 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(2, 132, 199, 0.35);
}
.status-pill-item input[value="alpa"]:checked + .status-pill-content {
    background: #e11d48 !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(225, 29, 72, 0.35);
}

/* 2. Custom Checkbox Pill (Telat & Ambil Uang) */
.toggle-pill-item {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    margin: 0;
    user-select: none;
    -webkit-user-select: none;
}
.toggle-pill-item input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    pointer-events: none;
}
.toggle-pill-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 6px 10px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 7px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #64748b;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.dark .toggle-pill-badge {
    background: #1e293b;
    border-color: #334155;
    color: #94a3b8;
}
.toggle-pill-item input[type="checkbox"]:checked + .toggle-pill-badge.is-telat {
    background: rgba(245, 158, 11, 0.14);
    border-color: #f59e0b;
    color: #b45309;
}
.dark .toggle-pill-item input[type="checkbox"]:checked + .toggle-pill-badge.is-telat {
    background: rgba(245, 158, 11, 0.22);
    border-color: #f59e0b;
    color: #fcd34d;
}
.toggle-pill-item input[type="checkbox"]:checked + .toggle-pill-badge.is-ambil {
    background: rgba(16, 185, 129, 0.14);
    border-color: #10b981;
    color: #047857;
}
.dark .toggle-pill-item input[type="checkbox"]:checked + .toggle-pill-badge.is-ambil {
    background: rgba(16, 185, 129, 0.22);
    border-color: #10b981;
    color: #6ee7b7;
}

/* 3. Date Control Bar */
.date-control-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 14px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}
.dark .date-control-card {
    background: #1e293b;
    border-color: #334155;
}

/* 4. KPI Cards */
.kpi-card-clean {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    transition: transform 0.12s ease, box-shadow 0.12s ease;
}
.dark .kpi-card-clean {
    background: #1e293b;
    border-color: #334155;
}
.kpi-icon-clean {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* 5. Main Card Header & Tabs */
.tab-pill-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 700;
    border-radius: 8px;
    color: #64748b;
    background: transparent;
    transition: all 0.15s ease;
    border: none;
    cursor: pointer;
    user-select: none;
    -webkit-user-select: none;
}
.tab-pill-btn:hover {
    color: #0f172a;
    background: rgba(0, 0, 0, 0.04);
}
.dark .tab-pill-btn:hover {
    color: #f8fafc;
    background: rgba(255, 255, 255, 0.06);
}
.tab-pill-btn.is-active {
    background: #881337 !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(136, 19, 55, 0.3);
}

.btn-primary-maroon {
    background: #881337 !important;
    color: #ffffff !important;
    border: 1px solid #700f2d !important;
    box-shadow: 0 1px 2px rgba(136, 19, 55, 0.2);
    transition: all 0.15s ease;
}
.btn-primary-maroon:hover {
    background: #9f1239 !important;
    box-shadow: 0 3px 8px rgba(136, 19, 55, 0.3);
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}
.search-icon-inside {
    position: absolute;
    left: 10px;
    width: 14px;
    height: 14px;
    color: #94a3b8;
    pointer-events: none;
}
.search-clear-btn {
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
.search-clear-btn:hover {
    color: #0f172a;
    background: #e2e8f0;
}
.dark .search-clear-btn {
    background: #334155;
    color: #94a3b8;
}
.dark .search-clear-btn:hover {
    color: #f8fafc;
    background: #475569;
}
.search-field-clean {
    padding-left: 32px !important;
    padding-right: 32px !important;
    padding-top: 7px !important;
    padding-bottom: 7px !important;
    font-size: 13px !important;
    border-radius: 8px !important;
    border: 1px solid #cbd5e1 !important;
    background: #ffffff !important;
    width: 100% !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.dark .search-field-clean {
    background: #1e293b !important;
    border-color: #475569 !important;
    color: #f8fafc !important;
}
.search-field-clean:focus {
    border-color: #881337 !important;
    box-shadow: 0 0 0 1px #881337 !important;
    outline: none !important;
}

.employee-avatar-clean {
    width: 34px;
    height: 34px;
    border-radius: 99px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11.5px;
    font-weight: 800;
    flex-shrink: 0;
}

/* 6. Professional Currency Input Group (Zero Overlap) */
.input-group-currency {
    display: flex;
    align-items: center;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    overflow: hidden;
    height: 32px;
    width: 100%;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.dark .input-group-currency {
    background: #1e293b;
    border-color: #475569;
}
.input-group-currency:focus-within {
    border-color: #881337 !important;
    box-shadow: 0 0 0 1px #881337 !important;
}
.currency-addon {
    background: #f1f5f9;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    padding: 0 8px;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border-right: 1px solid #e2e8f0;
    user-select: none;
    -webkit-user-select: none;
    flex-shrink: 0;
}
.dark .currency-addon {
    background: #334155;
    color: #94a3b8;
    border-color: #475569;
}
.currency-input {
    border: none !important;
    background: transparent !important;
    outline: none !important;
    box-shadow: none !important;
    font-size: 12px !important;
    font-family: monospace, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    font-weight: 600 !important;
    text-align: right !important;
    padding: 4px 8px !important;
    width: 100% !important;
    min-width: 0 !important;
    color: #0f172a !important;
}
.dark .currency-input {
    color: #f8fafc !important;
}
.currency-input:disabled {
    background: #f8fafc !important;
    color: #94a3b8 !important;
    cursor: not-allowed !important;
}
.dark .currency-input:disabled {
    background: #0f172a !important;
    color: #64748b !important;
}

/* =========================================================================
   MOBILE RESPONSIVE CARD VIEW (SCREEN WIDTH < 768px)
   ========================================================================= */
@media (max-width: 767.98px) {
    /* Hide Table Header on Mobile */
    .responsive-absensi-table thead {
        display: none !important;
    }
    
    .responsive-absensi-table,
    .responsive-absensi-table tbody {
        display: block !important;
        width: 100% !important;
    }
    
    /* Transform each row into a distinct mobile card using Flexbox layout */
    .responsive-absensi-table tr.attendance-row {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 8px !important;
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 14px !important;
        margin: 10px !important;
        padding: 12px 14px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
    }
    .dark .responsive-absensi-table tr.attendance-row {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    
    .responsive-absensi-table td {
        display: block !important;
        padding: 0 !important;
        border: none !important;
    }
    
    /* Hide No Column on Mobile */
    .responsive-absensi-table td.col-no {
        display: none !important;
    }
    
    /* Employee Name & Badge Header (Full Width) */
    .responsive-absensi-table td.col-emp {
        width: 100% !important;
        order: 1 !important;
        margin-bottom: 2px !important;
    }
    
    /* Status Radio Group Stretches Full Width on Mobile (Order 2) */
    .responsive-absensi-table td.col-status {
        width: 100% !important;
        order: 2 !important;
        margin-bottom: 2px !important;
    }
    .responsive-absensi-table td.col-status .status-pill-group {
        display: flex !important;
        width: 100% !important;
        justify-content: space-between !important;
        padding: 3px !important;
    }
    .responsive-absensi-table td.col-status .status-pill-item {
        flex: 1 !important;
        text-align: center !important;
    }
    .responsive-absensi-table td.col-status .status-pill-content {
        width: 100% !important;
        justify-content: center !important;
        padding: 7px 2px !important;
        font-size: 11.5px !important;
    }
    
    /* Chips row: Telat & Ambil Uang Makan (Order 3 & 4) */
    .responsive-absensi-table td.col-telat {
        order: 3 !important;
        width: auto !important;
        display: inline-flex !important;
    }
    .responsive-absensi-table td.col-ambil {
        order: 4 !important;
        width: auto !important;
        display: inline-flex !important;
    }
    
    /* Inputs: Lembur (Order 5) & Catatan (Order 6) */
    .responsive-absensi-table td.col-lembur {
        order: 5 !important;
        flex: 1 1 calc(50% - 4px) !important;
        min-width: 120px !important;
    }
    .responsive-absensi-table td.col-catatan {
        order: 6 !important;
        flex: 1 1 calc(50% - 4px) !important;
        min-width: 120px !important;
    }
    /* In Borongan where lembur doesn't exist, Catatan takes remaining/full width */
    .responsive-absensi-table.table-borongan td.col-catatan {
        flex: 1 1 100% !important;
    }
}

@media (max-width: 380px) {
    .responsive-absensi-table td.col-status .status-pill-content {
        padding: 6px 1px !important;
        font-size: 10px !important;
        gap: 2px !important;
    }
    .status-pill-group {
        padding: 2px !important;
        gap: 1px !important;
    }
}

/* Sticky Mobile Save Bottom Bar */
.sticky-mobile-save-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 45;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-top: 1px solid #e2e8f0;
    padding: 10px 16px;
    padding-bottom: max(10px, env(safe-area-inset-bottom));
    box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.06);
}
.dark .sticky-mobile-save-bar {
    background: rgba(15, 23, 42, 0.95);
    border-color: #334155;
    box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.25);
}
</style>

<div x-data="absensiApp()" class="space-y-4 pb-16 md:pb-4">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER                                                            -->
    <!-- ========================================================================= -->
    <div class="page-header flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-rose" style="flex-shrink:0;">
                <i data-lucide="calendar-check-2"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#881337;"></span>
                    <span>Modul HR &bull; Presensi Harian</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl font-bold">
                    <?= htmlspecialchars($pageTitle) ?>
                </h1>
                <p class="page-subtitle text-xs sm:text-sm">
                    Pencatatan presensi harian borongan &amp; bulanan, lembur, dan sinkronisasi uang harian
                </p>
            </div>
        </div>
        <div class="page-header-actions flex items-center gap-2 w-full sm:w-auto justify-end">
            <a href="<?= Router::url('/absensi/rekap') ?>" class="btn btn-secondary text-xs sm:text-sm flex items-center gap-1.5 w-full sm:w-auto justify-center">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                <span>Rekap Bulanan</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. DATE NAVIGATOR & ACTION CONTROLS (RESPONSIVE BAR)                      -->
    <!-- ========================================================================= -->
    <div class="date-control-card flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <!-- Left: Date Stepper & Label -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-2">
                <div class="inline-flex items-center bg-slate-100 dark:bg-slate-800 p-0.5 rounded-lg border border-slate-200 dark:border-slate-700">
                    <a href="<?= Router::url('/absensi?tanggal=' . $kemarin) ?>" 
                       class="px-2.5 py-1.5 text-slate-600 dark:text-slate-300 hover:text-slate-900 hover:bg-white dark:hover:bg-slate-700 rounded-md transition" 
                       title="Hari Sebelumnya (<?= date('d/m/Y', strtotime($kemarin)) ?>)">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </a>

                    <form method="GET" action="<?= Router::url('/absensi') ?>" class="inline-flex items-center m-0">
                        <input type="date" 
                               name="tanggal" 
                               value="<?= $tanggal ?>" 
                               onchange="this.form.submit()"
                               class="bg-transparent border-0 text-xs font-bold text-slate-800 dark:text-slate-100 py-1 px-2 focus:ring-0 cursor-pointer">
                    </form>

                    <a href="<?= Router::url('/absensi?tanggal=' . $besok) ?>" 
                       class="px-2.5 py-1.5 text-slate-600 dark:text-slate-300 hover:text-slate-900 hover:bg-white dark:hover:bg-slate-700 rounded-md transition" 
                       title="Hari Berikutnya (<?= date('d/m/Y', strtotime($besok)) ?>)">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                </div>

                <?php if (!$isToday): ?>
                    <a href="<?= Router::url('/absensi?tanggal=' . date('Y-m-d')) ?>" 
                       class="btn btn-ghost btn-sm text-xs font-semibold text-rose-800 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 border border-rose-200/60 dark:border-rose-800/40 py-1.5 px-2.5">
                        Hari Ini
                    </a>
                <?php endif; ?>
            </div>

            <div class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 flex-wrap">
                <span class="font-bold"><?= $tanggalLengkap ?></span>
                <?php if ($isToday): ?>
                    <span class="badge badge-success text-[10px] py-0.5 px-2">Hari Ini</span>
                <?php elseif ($diffDays === 1): ?>
                    <span class="badge badge-warning text-[10px] py-0.5 px-2">Besok</span>
                <?php elseif ($diffDays === 2): ?>
                    <span class="badge badge-warning text-[10px] py-0.5 px-2">Lusa</span>
                <?php elseif ($diffDays > 2): ?>
                    <span class="badge badge-warning text-[10px] py-0.5 px-2"><?= $diffDays ?> Hari Berikutnya</span>
                <?php elseif ($diffDays === -1): ?>
                    <span class="badge badge-ghost text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] py-0.5 px-2">Kemarin</span>
                <?php endif; ?>
                <?php if ($isTanggalLocked): ?>
                    <span class="badge badge-neutral text-[10px] py-0.5 px-2">🔒 Terkunci Payroll</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Quick Bulk Actions -->
        <?php if (!$isTanggalLocked): ?>
        <div class="flex items-center gap-2 w-full md:w-auto">
            <span class="text-[11px] font-semibold text-slate-400 hidden xl:inline">Aksi Cepat:</span>
            <button type="button" @click="setAllStatus('hadir')" class="flex-1 md:flex-initial btn btn-ghost btn-sm text-[11.5px] font-semibold text-emerald-700 dark:text-emerald-400 bg-emerald-50/90 dark:bg-emerald-950/40 border border-emerald-200/70 dark:border-emerald-800/40 py-1.5 px-3">
                <i data-lucide="check-check" class="w-3.5 h-3.5 mr-1 text-emerald-600"></i>
                Semua Hadir
            </button>
            <button type="button" @click="setAllStatus('libur')" class="flex-1 md:flex-initial btn btn-ghost btn-sm text-[11.5px] font-semibold text-sky-700 dark:text-sky-400 bg-sky-50/90 dark:bg-sky-950/40 border border-sky-200/70 dark:border-sky-800/40 py-1.5 px-3">
                <i data-lucide="sun" class="w-3.5 h-3.5 mr-1 text-sky-600"></i>
                Semua Libur
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. KPI STAT METRIC GRID (COMPACT & RESPONSIVE)                            -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
        <!-- Metric 1: Borongan -->
        <div class="kpi-card-clean">
            <div class="kpi-icon-clean bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                <i data-lucide="boxes" class="w-4 h-4"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] sm:text-[10.5px] font-bold text-slate-400 uppercase tracking-wider truncate">Hadir Borongan</div>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-base sm:text-lg font-bold font-mono text-slate-900 dark:text-slate-100"><?= $hadirBorongan ?></span>
                    <span class="text-[11px] sm:text-xs font-mono text-slate-400">/ <?= $totalBorongan ?></span>
                </div>
            </div>
        </div>

        <!-- Metric 2: Bulanan -->
        <div class="kpi-card-clean">
            <div class="kpi-icon-clean bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 border border-sky-200/60 dark:border-sky-800/40">
                <i data-lucide="briefcase" class="w-4 h-4"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] sm:text-[10.5px] font-bold text-slate-400 uppercase tracking-wider truncate">Hadir Bulanan</div>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-base sm:text-lg font-bold font-mono text-slate-900 dark:text-slate-100"><?= $hadirBulanan ?></span>
                    <span class="text-[11px] sm:text-xs font-mono text-slate-400">/ <?= $totalBulanan ?></span>
                </div>
            </div>
        </div>

        <!-- Metric 3: Total Alpa -->
        <div class="kpi-card-clean">
            <div class="kpi-icon-clean bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 border border-rose-200/60 dark:border-rose-800/40">
                <i data-lucide="user-x" class="w-4 h-4"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] sm:text-[10.5px] font-bold text-slate-400 uppercase tracking-wider truncate">Total Alpa</div>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-base sm:text-lg font-bold font-mono <?= $alpaTotal > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' ?>"><?= $alpaTotal ?></span>
                    <span class="text-[10.5px] sm:text-xs text-slate-400">orang</span>
                </div>
            </div>
        </div>

        <!-- Metric 4: Payroll Status -->
        <div class="kpi-card-clean">
            <div class="kpi-icon-clean <?= $isTanggalLocked ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 border border-amber-200/60' : 'bg-slate-100 dark:bg-slate-800 text-slate-600' ?>">
                <i data-lucide="<?= $isTanggalLocked ? 'lock' : 'unlock' ?>" class="w-4 h-4"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] sm:text-[10.5px] font-bold text-slate-400 uppercase tracking-wider truncate">Status Payroll</div>
                <div class="text-xs font-bold mt-0.5 truncate <?= $isTanggalLocked ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' ?>">
                    <?= $isTanggalLocked ? 'Terkunci Payroll' : 'Terbuka (Draf)' ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isTanggalLocked): ?>
    <div class="p-3.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-800/60 rounded-xl flex items-center gap-3 text-amber-900 dark:text-amber-200 text-xs shadow-xs">
        <div class="p-2 bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 rounded-lg shrink-0">
            <i data-lucide="lock" class="w-4 h-4"></i>
        </div>
        <div class="leading-relaxed min-w-0">
            <span class="font-bold">Presensi Terkunci Payroll:</span> Seluruh data presensi pada tanggal ini telah disetujui dalam transaksi penggajian resmi. Modifikasi data dinonaktifkan untuk menjamin integritas catatan keuangan.
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- 4. MAIN CARD WITH INTEGRATED TOOLBAR & RESPONSIVE DATA TABLE              -->
    <!-- ========================================================================= -->
    <form method="POST" action="<?= Router::url('/absensi/bulk-store') ?>" id="formPresensi" class="m-0">
        <?= CSRF::field() ?>
        <input type="hidden" name="tanggal" value="<?= $tanggal ?>">

        <div class="card overflow-hidden border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-xl shadow-sm">
            
            <!-- Unified Responsive Toolbar Header -->
            <div class="p-3 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-slate-50/50 dark:bg-slate-900/50">
                <!-- Left: Tabs (Full-width grid on mobile, inline on desktop) -->
                <div class="w-full sm:w-auto grid grid-cols-2 sm:flex bg-slate-100 dark:bg-slate-800 p-1 rounded-lg border border-slate-200/80 dark:border-slate-700/80 gap-1">
                    <button type="button" 
                            @click="tab = 'borongan'" 
                            :class="tab === 'borongan' ? 'is-active' : ''"
                            class="tab-pill-btn">
                        <i data-lucide="boxes" class="w-3.5 h-3.5"></i>
                        <span>Borongan</span>
                        <span class="text-[10.5px] py-0.2 px-1.5 rounded-full font-mono font-bold" 
                              :class="tab === 'borongan' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'">
                            <?= $totalBorongan ?>
                        </span>
                    </button>

                    <button type="button" 
                            @click="tab = 'bulanan'" 
                            :class="tab === 'bulanan' ? 'is-active' : ''"
                            class="tab-pill-btn">
                        <i data-lucide="briefcase" class="w-3.5 h-3.5"></i>
                        <span>Bulanan</span>
                        <span class="text-[10.5px] py-0.2 px-1.5 rounded-full font-mono font-bold" 
                              :class="tab === 'bulanan' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'">
                            <?= $totalBulanan ?>
                        </span>
                    </button>
                </div>

                <!-- Right: Live Search Input -->
                <div class="w-full sm:w-72">
                    <div class="search-input-wrapper w-full">
                        <i data-lucide="search" class="search-icon-inside"></i>
                        <input type="text" 
                               x-model="searchQuery" 
                               @keydown.enter.prevent
                               placeholder="Cari nama / posisi..." 
                               class="search-field-clean">
                        <button type="button" 
                                @click="searchQuery = ''" 
                                x-show="searchQuery.trim().length > 0" 
                                class="search-clear-btn" 
                                title="Reset Pencarian"
                                style="display: none;">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ================================================================= -->
            <!-- TAB 1: KARYAWAN BORONGAN                                          -->
            <!-- ================================================================= -->
            <div x-show="tab === 'borongan'" class="table-wrapper overflow-x-auto">
                <table class="responsive-absensi-table table-borongan data-table w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                            <th class="py-2.5 px-3.5 w-12 text-center">No</th>
                            <th class="py-2.5 px-3.5 min-w-[210px]">Karyawan</th>
                            <th class="py-2.5 px-3.5 min-w-[330px]">Status Kehadiran</th>
                            <th class="py-2.5 px-3.5 w-24 text-center">Disiplin</th>
                            <th class="py-2.5 px-3.5 min-w-[200px]">Catatan Khusus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                        <?php if (empty($karyawanBorongan)): ?>
                            <tr>
                                <td colspan="5" class="py-10 text-center text-slate-400 dark:text-slate-500">
                                    <i data-lucide="users" class="w-7 h-7 mx-auto mb-1.5 opacity-40"></i>
                                    Tidak ada data karyawan borongan aktif.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($karyawanBorongan as $emp): 
                                $kid = $emp['karyawan_id'];
                                $isLocked = !empty($emp['penggajian_id']);
                                $statusKehadiran = $emp['status_kehadiran'] ?? 'hadir';
                                $initials = strtoupper(substr($emp['nama_karyawan'], 0, 2));
                                $uangHadir = (float)($emp['uang_kehadiran_harian'] ?? 0);
                            ?>
                            <tr x-show="matchSearch('<?= strtolower(addslashes($emp['nama_karyawan'] . ' ' . $emp['posisi'])) ?>')"
                                class="attendance-row hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors <?= $isLocked ? 'bg-slate-50/40 dark:bg-slate-800/20' : '' ?>">
                                
                                <td class="col-no py-2.5 px-3.5 text-center text-slate-400 font-mono text-[11px]"><?= $no++ ?></td>
                                
                                <!-- Nama & Posisi -->
                                <td class="col-emp py-2.5 px-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="employee-avatar-clean bg-rose-50 dark:bg-rose-950/50 text-rose-900 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800/40">
                                             <?= $initials ?>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="font-bold text-slate-900 dark:text-slate-100 truncate text-xs sm:text-sm"><?= htmlspecialchars($emp['nama_karyawan']) ?></span>
                                                <?php if ($isLocked): ?>
                                                    <span class="badge badge-neutral text-[9px] py-0 px-1">🔒 Locked</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="badge badge-success text-[9.5px] py-0 px-1.5"><?= htmlspecialchars(ucfirst($emp['posisi'] ?? 'Borongan')) ?></span>
                                                <?php if ($uangHadir > 0): ?>
                                                    <span class="text-[10.5px] font-mono text-slate-500 dark:text-slate-400 font-medium">+<?= Format::rupiah($uangHadir) ?>/hr</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status Kehadiran Segmented Control (Clean Text Only) -->
                                <td class="col-status py-2.5 px-3.5">
                                    <div class="status-pill-group">
                                        <?php foreach (['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'libur' => 'Libur', 'alpa' => 'Alpa'] as $val => $label): ?>
                                            <label class="status-pill-item">
                                                <input type="radio" 
                                                       name="absensi[<?= $kid ?>][status_kehadiran]" 
                                                       value="<?= $val ?>" 
                                                       data-employee-type="borongan"
                                                       @change="onStatusChanged($el)"
                                                       <?= $statusKehadiran === $val ? 'checked' : '' ?>
                                                       <?= $isLocked ? 'disabled' : '' ?>>
                                                <span class="status-pill-content">
                                                    <?= $label ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </td>

                                <!-- Telat Toggle -->
                                <td class="col-telat py-2.5 px-3.5 text-center">
                                    <label class="toggle-pill-item">
                                        <input type="checkbox" 
                                               name="absensi[<?= $kid ?>][telat]" 
                                               value="1" 
                                               <?= $emp['telat'] ? 'checked' : '' ?>
                                               <?= $isLocked ? 'disabled' : '' ?>>
                                        <span class="toggle-pill-badge is-telat">
                                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                            <span>Telat</span>
                                        </span>
                                    </label>
                                </td>

                                <!-- Catatan Khusus -->
                                <td class="col-catatan py-2.5 px-3.5">
                                    <input type="text" 
                                           name="absensi[<?= $kid ?>][catatan]" 
                                           value="<?= htmlspecialchars($emp['catatan'] ?? '') ?>" 
                                           placeholder="Keterangan opsional..." 
                                           <?= $isLocked ? 'disabled' : '' ?>
                                           class="form-input form-input-sm text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 w-full py-1.5 px-2.5 focus:ring-1 focus:ring-rose-800">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Empty Search Result State -->
                            <tr x-show="isSearchEmpty('borongan')" style="display: none;">
                                <td colspan="5" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-1">
                                        <i data-lucide="search-x" class="w-6 h-6 text-slate-300 dark:text-slate-600 mb-0.5"></i>
                                        <span class="text-xs">Tidak ada karyawan borongan yang cocok dengan "<strong class="text-slate-700 dark:text-slate-300" x-text="searchQuery"></strong>"</span>
                                        <button type="button" @click="searchQuery = ''" class="text-[11.5px] font-semibold text-rose-800 dark:text-rose-400 hover:underline mt-0.5">Reset Pencarian</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ================================================================= -->
            <!-- TAB 2: KARYAWAN BULANAN                                           -->
            <!-- ================================================================= -->
            <div x-show="tab === 'bulanan'" class="table-wrapper overflow-x-auto" style="display: none;">
                <!-- Sumber Kas Penarikan Harian Bulanan -->
                <div class="p-3 bg-rose-50/40 dark:bg-rose-950/20 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-rose-100 dark:bg-rose-900/60 text-rose-800 dark:text-rose-200 flex items-center justify-center shrink-0">
                            <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-slate-800 dark:text-slate-100 text-xs">Kas Pengambilan Uang Harian</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">Pilih akun kas yang dipotong jika opsi 'Ambil Uang' dicentang</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-1 sm:pb-0">
                        <input type="hidden" name="akun_kas_id" :value="selectedKasId">
                        <div class="inline-flex items-center gap-1.5 bg-white dark:bg-slate-800 p-1 rounded-lg border border-slate-200 dark:border-slate-700">
                            <template x-for="acc in cashAccounts" :key="acc.id">
                                <button type="button" 
                                        @click="selectedKasId = acc.id"
                                        :class="selectedKasId === acc.id ? 'bg-rose-800 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 font-medium'"
                                        class="px-2.5 py-1 rounded-md text-[11px] transition-all flex items-center gap-1.5 whitespace-nowrap">
                                    <span x-text="acc.nama_akun"></span>
                                    <span x-show="acc.is_default_pos" class="text-[9px] px-1 py-0.2 rounded font-mono font-bold"
                                          :class="selectedKasId === acc.id ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300'">POS</span>
                                    <span class="font-mono text-[10.5px] opacity-80" x-text="'(' + formatRupiah(acc.saldo) + ')'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <table class="responsive-absensi-table table-bulanan data-table w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                            <th class="py-2.5 px-3.5 w-12 text-center">No</th>
                            <th class="py-2.5 px-3.5 min-w-[210px]">Karyawan</th>
                            <th class="py-2.5 px-3.5 min-w-[330px]">Status Kehadiran</th>
                            <th class="py-2.5 px-3.5 w-24 text-center">Disiplin</th>
                            <th class="py-2.5 px-3.5 min-w-[150px]">Uang Harian</th>
                            <th class="py-2.5 px-3.5 min-w-[130px]">Lembur (Rp)</th>
                            <th class="py-2.5 px-3.5 min-w-[180px]">Catatan Khusus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                        <?php if (empty($karyawanBulanan)): ?>
                            <tr>
                                <td colspan="7" class="py-10 text-center text-slate-400 dark:text-slate-500">
                                    <i data-lucide="briefcase" class="w-7 h-7 mx-auto mb-1.5 opacity-40"></i>
                                    Tidak ada data karyawan bulanan aktif.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($karyawanBulanan as $emp): 
                                $kid = $emp['karyawan_id'];
                                $isLocked = !empty($emp['penggajian_id']);
                                $statusKehadiran = $emp['status_kehadiran'] ?? 'hadir';
                                $initials = strtoupper(substr($emp['nama_karyawan'], 0, 2));
                                $gajiPokok = (float)($emp['gaji_pokok_bulanan'] ?? 0);
                                $uangHadir = (float)($emp['uang_kehadiran_harian'] ?? 0);
                            ?>
                            <tr x-show="matchSearch('<?= strtolower(addslashes($emp['nama_karyawan'] . ' ' . $emp['posisi'])) ?>')"
                                class="attendance-row hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors <?= $isLocked ? 'bg-slate-50/40 dark:bg-slate-800/20' : '' ?>">
                                
                                <td class="col-no py-2.5 px-3.5 text-center text-slate-400 font-mono text-[11px]"><?= $no++ ?></td>
                                
                                <!-- Nama & Posisi -->
                                <td class="col-emp py-2.5 px-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="employee-avatar-clean bg-sky-50 dark:bg-sky-950/50 text-sky-900 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/40">
                                            <?= $initials ?>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="font-bold text-slate-900 dark:text-slate-100 truncate text-xs sm:text-sm"><?= htmlspecialchars($emp['nama_karyawan']) ?></span>
                                                <?php if ($isLocked): ?>
                                                    <span class="badge badge-neutral text-[9px] py-0 px-1">🔒 Locked</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                                <span class="badge badge-info text-[9.5px] py-0 px-1.5"><?= htmlspecialchars(ucfirst($emp['posisi'] ?? 'Bulanan')) ?></span>
                                                <?php if ($gajiPokok > 0): ?>
                                                    <span class="text-[10.5px] font-mono text-slate-500 dark:text-slate-400 font-medium">Gaji: <?= Format::rupiah($gajiPokok) ?>/bln</span>
                                                <?php elseif ($uangHadir > 0): ?>
                                                    <span class="text-[10.5px] font-mono text-slate-500 dark:text-slate-400 font-medium">Uang Kehadiran: <?= Format::rupiah($uangHadir) ?>/hr</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status Kehadiran Segmented Control (Clean Text Only) -->
                                <td class="col-status py-2.5 px-3.5">
                                    <div class="status-pill-group">
                                        <?php foreach (['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'libur' => 'Libur', 'alpa' => 'Alpa'] as $val => $label): ?>
                                            <label class="status-pill-item">
                                                <input type="radio" 
                                                       name="absensi[<?= $kid ?>][status_kehadiran]" 
                                                       value="<?= $val ?>" 
                                                       data-employee-type="bulanan"
                                                       @change="onStatusChanged($el)"
                                                       <?= $statusKehadiran === $val ? 'checked' : '' ?>
                                                       <?= $isLocked ? 'disabled' : '' ?>>
                                                <span class="status-pill-content">
                                                    <?= $label ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </td>

                                <!-- Disiplin & Uang Harian (Desktop Columns / Reflowed on Mobile) -->
                                <td class="col-telat py-2.5 px-3.5 text-center">
                                    <label class="toggle-pill-item">
                                        <input type="checkbox" 
                                               name="absensi[<?= $kid ?>][telat]" 
                                               value="1" 
                                               <?= $emp['telat'] ? 'checked' : '' ?>
                                               <?= $isLocked ? 'disabled' : '' ?>>
                                        <span class="toggle-pill-badge is-telat">
                                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                            <span>Telat</span>
                                        </span>
                                    </label>
                                </td>

                                <!-- Ambil Uang Harian (Hanya jika uang_kehadiran_harian > 0) -->
                                <td class="col-ambil py-2.5 px-3.5">
                                    <?php if ($uangHadir > 0): ?>
                                        <label class="toggle-pill-item">
                                            <input type="checkbox" 
                                                   name="absensi[<?= $kid ?>][ambil_uang]" 
                                                   value="1" 
                                                   <?= $emp['ambil_uang'] ? 'checked' : '' ?>
                                                   <?= $isLocked ? 'disabled' : '' ?>>
                                            <span class="toggle-pill-badge is-ambil">
                                                <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
                                                <span>Ambil <?= Format::rupiah($uangHadir) ?></span>
                                            </span>
                                        </label>
                                    <?php else: ?>
                                        <span class="hidden md:inline-block text-xs text-slate-300 dark:text-slate-600 font-mono text-center">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Lembur Nominal (Rp) Group -->
                                <td class="col-lembur py-2.5 px-3.5">
                                    <div class="input-group-currency">
                                        <span class="currency-addon">Rp</span>
                                        <input type="number" 
                                               name="absensi[<?= $kid ?>][lembur_nominal]" 
                                               value="<?= ((int)($emp['lembur_nominal'] ?? 0) > 0) ? (int)$emp['lembur_nominal'] : '' ?>" 
                                               placeholder="0" 
                                               min="0" 
                                               step="5000" 
                                               <?= $isLocked ? 'disabled' : '' ?> 
                                               class="currency-input">
                                    </div>
                                </td>

                                <!-- Catatan Khusus -->
                                <td class="col-catatan py-2.5 px-3.5">
                                    <input type="text" 
                                           name="absensi[<?= $kid ?>][catatan]" 
                                           value="<?= htmlspecialchars($emp['catatan'] ?? '') ?>" 
                                           placeholder="Catatan..." 
                                           <?= $isLocked ? 'disabled' : '' ?> 
                                           class="form-input form-input-sm text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 w-full py-1.5 px-2.5 focus:ring-1 focus:ring-rose-800">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Empty Search Result State -->
                            <tr x-show="isSearchEmpty('bulanan')" style="display: none;">
                                <td colspan="7" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-1">
                                        <i data-lucide="search-x" class="w-6 h-6 text-slate-300 dark:text-slate-600 mb-0.5"></i>
                                        <span class="text-xs">Tidak ada karyawan bulanan yang cocok dengan "<strong class="text-slate-700 dark:text-slate-300" x-text="searchQuery"></strong>"</span>
                                        <button type="button" @click="searchQuery = ''" class="text-[11.5px] font-semibold text-rose-800 dark:text-rose-400 hover:underline mt-0.5">Reset Pencarian</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer Bar (Desktop) -->
            <div class="p-3 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-2.5 text-xs text-slate-500">
                <div class="flex items-center gap-1.5 text-[11.5px]">
                    <i data-lucide="info" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                    <span>Data yang sudah disetujui dalam periode penggajian akan terkunci (🔒) dan tidak dapat dimodifikasi.</span>
                </div>
                <div class="hidden sm:block">
                    <button type="submit" class="btn btn-primary-maroon btn-sm flex items-center gap-1.5 px-4 py-1.5 font-semibold text-xs rounded-lg" <?= $isTanggalLocked ? 'disabled' : '' ?>>
                        <i data-lucide="save" class="w-3.5 h-3.5"></i>
                        <span>Simpan Presensi</span>
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Floating Sticky Save Bar on Mobile & iOS -->
    <div class="sticky-mobile-save-bar md:hidden flex items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Presensi <?= $namaHari ?></div>
            <div class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate"><?= date('d M Y', strtotime($tanggal)) ?></div>
        </div>
        <button type="submit" form="formPresensi" class="btn btn-primary-maroon btn-sm flex items-center gap-1.5 px-4 py-2 font-semibold text-xs rounded-xl shadow-md" <?= $isTanggalLocked ? 'disabled' : '' ?>>
            <i data-lucide="save" class="w-4 h-4"></i>
            <span>Simpan Presensi</span>
        </button>
    </div>
</div>

<script>
function absensiApp() {
    <?php
    $defaultKasId = '';
    foreach ($activeCashAccounts ?? [] as $ak) {
        if (!empty($ak['is_default_pos'])) { $defaultKasId = (string)$ak['id']; break; }
    }
    if (!$defaultKasId && !empty($activeCashAccounts[0]['id'])) {
        $defaultKasId = (string)$activeCashAccounts[0]['id'];
    }
    ?>
    return {
        tab: 'borongan',
        searchQuery: '',
        isDirty: false,
        cashAccounts: <?= json_encode(array_map(function($a) {
            return [
                'id' => (string)$a['id'],
                'nama_akun' => (string)$a['nama_akun'],
                'tipe_akun' => (string)$a['tipe_akun'],
                'saldo' => (float)$a['saldo_saat_ini'],
                'is_default_pos' => (bool)($a['is_default_pos'] ?? false),
            ];
        }, $activeCashAccounts ?? []), JSON_UNESCAPED_UNICODE) ?>,
        selectedKasId: '<?= $defaultKasId ?>',
        formatRupiah(val) {
            return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
        },
        boronganList: <?= json_encode(array_values(array_map(function($e) { return strtolower($e['nama_karyawan'] . ' ' . ($e['posisi'] ?? '')); }, $karyawanBorongan ?? []))) ?>,
        bulananList: <?= json_encode(array_values(array_map(function($e) { return strtolower($e['nama_karyawan'] . ' ' . ($e['posisi'] ?? '')); }, $karyawanBulanan ?? []))) ?>,
        matchSearch(text) {
            if (!this.searchQuery || !this.searchQuery.trim()) return true;
            return text.includes(this.searchQuery.toLowerCase().trim());
        },
        isSearchEmpty(tabType) {
            const q = this.searchQuery ? this.searchQuery.toLowerCase().trim() : '';
            if (!q) return false;
            const list = tabType === 'borongan' ? this.boronganList : this.bulananList;
            if (!list || list.length === 0) return false;
            return !list.some(item => item.includes(q));
        },
        setAllStatus(status) {
            const currentTab = this.tab;
            const radios = document.querySelectorAll(`input[type="radio"][data-employee-type="${currentTab}"][value="${status}"]`);
            radios.forEach(radio => {
                if (!radio.disabled) {
                    radio.checked = true;
                    this.onStatusChanged(radio);
                }
            });
            this.isDirty = true;
        },
        onStatusChanged(radioEl) {
            this.isDirty = true;
            const row = radioEl.closest('.attendance-row');
            if (!row) return;
            const status = radioEl.value;
            const telatBox = row.querySelector('input[name*="[telat]"]');
            const ambilBox = row.querySelector('input[name*="[ambil_uang]"]');
            const lemburInput = row.querySelector('input[name*="[lembur_nominal]"]');
            
            if (status !== 'hadir') {
                if (telatBox) telatBox.checked = false;
                if (ambilBox) ambilBox.checked = false;
                if (lemburInput && ['izin', 'sakit', 'alpa'].includes(status)) {
                    lemburInput.value = '';
                }
            }
        }
    };
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();

    // Prevent accidental navigation when form has unsaved changes
    const form = document.getElementById('formPresensi');
    if (form) {
        form.addEventListener('submit', () => {
            window._isSubmittingPresensi = true;
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

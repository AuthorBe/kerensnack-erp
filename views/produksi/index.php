<?php
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CSRF;

ob_start();

$kemarin = date('Y-m-d', strtotime($tanggal . ' -1 day'));
$besok = date('Y-m-d', strtotime($tanggal . ' +1 day'));
$isToday = ($tanggal === date('Y-m-d'));

$todayTs = strtotime(date('Y-m-d'));
$targetTs = strtotime($tanggal);
$diffDays = (int)round(($targetTs - $todayTs) / 86400);

$hariList = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$namaHari = $hariList[(int)date('w', strtotime($tanggal))];
$tanggalLengkap = $namaHari . ', ' . Format::tanggal($tanggal, false, false);

$totalBalHariIni = $totalBalHariIni ?? 0;
$totalLemburPcsHariIni = $totalLemburPcsHariIni ?? 0;
$totalLemburBalHariIni = $totalLemburBalHariIni ?? 0;
$isTanggalLocked = $isTanggalLocked ?? false;
$selectedKaryawanId = $selectedKaryawanId ?? '';
$produksiHariIni = $produksiHariIni ?? [];
$absensiMap = $absensiMap ?? [];

// Helper Avatar Initials
function getInitials(string $name): string {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $initials .= mb_substr($p, 0, 1);
    }
    return strtoupper($initials ?: 'KR');
}

// Build Karyawan Dictionary for fast Alpine reactivity
$karyawanMapData = [];
foreach ($karyawanBorongan as $kb) {
    $kid = (string)$kb['karyawan_id'];
    $empProd = $produksiPerKaryawan[$kid] ?? null;
    $empCount = $empProd ? count($empProd['items']) : 0;
    $empUpahTotal = $empProd ? (float)$empProd['total_upah'] : 0.0;
    $empAbsen = $absensiMap[$kid]['status_kehadiran'] ?? 'belum_absen';
    $karyawanMapData[$kid] = [
        'id' => $kid,
        'name' => $kb['nama_karyawan'],
        'posisi' => $kb['posisi'] ?? 'Pengemasan',
        'itemsCount' => $empCount,
        'upahTotal' => $empUpahTotal,
        'absen' => $empAbsen
    ];
}
?>

<style>
/* =========================================================================
   MODUL PRODUKSI BORONGAN — CANONICAL KEREN ONE ERP DESIGN SYSTEM
   ========================================================================= */

/* Mobile & Touch Ergonomics */
* {
    -webkit-tap-highlight-color: transparent;
}
button, select {
    touch-action: manipulation;
}
input[type="text"], input[type="number"], input[type="search"], input[type="date"] {
    touch-action: manipulation;
}

/* Date Dock Toolbar Card */
.prod-dock-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: var(--rounded-lg, 14px);
    padding: 10px 14px;
    box-shadow: var(--shadow-1);
}

/* Stat Cards */
.prod-stat-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: var(--rounded-lg, 14px);
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--shadow-1);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.prod-stat-card:hover {
    box-shadow: var(--shadow-2);
    border-color: var(--color-hairline-strong);
}
.prod-stat-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    min-height: 42px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-sizing: border-box;
}
.prod-stat-icon svg {
    width: 20px;
    height: 20px;
    display: block;
}

/* Section & Card Header Icons */
.prod-section-icon {
    width: 38px;
    height: 38px;
    min-width: 38px;
    min-height: 38px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-sizing: border-box;
}
.prod-section-icon.is-maroon {
    background-color: rgba(136, 19, 55, 0.1);
    color: #881337;
    border: 1px solid rgba(136, 19, 55, 0.22);
}
.dark .prod-section-icon.is-maroon {
    background-color: rgba(251, 113, 133, 0.14);
    color: #fb7185;
    border-color: rgba(251, 113, 133, 0.3);
}
.prod-section-icon.is-emerald {
    background-color: rgba(16, 185, 129, 0.1);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.25);
}
.dark .prod-section-icon.is-emerald {
    background-color: rgba(16, 185, 129, 0.16);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.35);
}
.prod-section-icon svg {
    width: 18px;
    height: 18px;
    display: block;
}

/* Table Initials Avatar */
.prod-table-avatar {
    width: 28px;
    height: 28px;
    min-width: 28px;
    min-height: 28px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 700;
    font-family: var(--font-mono, monospace);
    background-color: rgba(136, 19, 55, 0.1);
    color: #881337;
    border: 1px solid rgba(136, 19, 55, 0.22);
    flex-shrink: 0;
    box-sizing: border-box;
}
.dark .prod-table-avatar {
    background-color: rgba(251, 113, 133, 0.14);
    color: #fb7185;
    border-color: rgba(251, 113, 133, 0.3);
}

/* Empty State Icon Box */
.prod-empty-icon {
    width: 44px;
    height: 44px;
    min-width: 44px;
    min-height: 44px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: var(--color-canvas-soft);
    color: var(--color-ink-mute);
    border: 1px solid var(--color-hairline);
    margin: 0 auto 12px auto;
    box-sizing: border-box;
}
.prod-empty-icon.is-maroon {
    background-color: rgba(136, 19, 55, 0.08);
    color: #881337;
    border: 1px solid rgba(136, 19, 55, 0.2);
}
.dark .prod-empty-icon.is-maroon {
    background-color: rgba(251, 113, 133, 0.12);
    color: #fb7185;
    border-color: rgba(251, 113, 133, 0.25);
}
.prod-empty-icon svg {
    width: 22px;
    height: 22px;
    display: block;
}

/* Multi-Item Row Card */
.prod-item-row-box {
    background-color: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    border-radius: var(--rounded-lg, 12px);
    padding: 12px 14px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.prod-item-row-box:hover {
    border-color: var(--color-hairline-strong);
}

/* Toggle Switch Styling (Calm Amber / Warm Subtle Theme for Lembur) */
.toggle-switch-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    user-select: none;
    font-size: 11.5px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 9999px;
    border: 1px solid rgba(245, 158, 11, 0.28);
    background-color: rgba(245, 158, 11, 0.06);
    color: #92400e;
    transition: all 0.2s ease;
}
.toggle-switch-pill:hover {
    color: #78350f;
    border-color: rgba(245, 158, 11, 0.5);
    background-color: rgba(245, 158, 11, 0.12);
    box-shadow: 0 1px 3px rgba(245, 158, 11, 0.1);
}
.toggle-switch-pill.is-active {
    background-color: #fef3c7 !important;
    border-color: #d97706 !important;
    color: #78350f !important;
    box-shadow: 0 2px 6px rgba(217, 119, 6, 0.15) !important;
}
.dark .toggle-switch-pill {
    border-color: rgba(245, 158, 11, 0.35);
    background-color: rgba(245, 158, 11, 0.1);
    color: #fbbf24;
}
.dark .toggle-switch-pill:hover {
    background-color: rgba(245, 158, 11, 0.18);
    color: #fde68a;
}
.dark .toggle-switch-pill.is-active {
    background-color: rgba(245, 158, 11, 0.25) !important;
    border-color: #fbbf24 !important;
    color: #fde68a !important;
    box-shadow: 0 2px 8px rgba(251, 191, 36, 0.2) !important;
}

/* Minimalist Delete Row Button */
.btn-delete-row {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    color: var(--color-ink-mute);
    background: transparent;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
}
.btn-delete-row:hover {
    color: #e11d48;
    background-color: rgba(225, 29, 72, 0.08);
    border-color: rgba(225, 29, 72, 0.15);
}
.dark .btn-delete-row:hover {
    color: #fb7185;
    background-color: rgba(251, 113, 133, 0.12);
    border-color: rgba(251, 113, 133, 0.25);
}

/* Elegant Add Row Button */
.btn-add-item-row {
    width: 100%;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 700;
    border-radius: 10px;
    border: 1.5px dashed rgba(136, 19, 55, 0.25);
    background-color: rgba(136, 19, 55, 0.03);
    color: #881337;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-add-item-row:hover {
    background-color: rgba(136, 19, 55, 0.08);
    border-color: rgba(136, 19, 55, 0.5);
    border-style: solid;
}
.dark .btn-add-item-row {
    background-color: rgba(251, 113, 133, 0.05);
    border-color: rgba(251, 113, 133, 0.3);
    color: #fb7185;
}
.dark .btn-add-item-row:hover {
    background-color: rgba(251, 113, 133, 0.12);
    border-color: rgba(251, 113, 133, 0.6);
    border-style: solid;
}

/* Custom Searchable Select Dropdowns */
.dropdown-menu-searchable {
    animation: prodDropdownFadeIn 0.15s ease-out;
}
@keyframes prodDropdownFadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
.searchable-option {
    transition: background-color 0.12s ease;
    user-select: none;
}
.searchable-option:hover,
.searchable-option.is-active {
    background-color: var(--color-canvas-soft);
}
.searchable-option.is-selected {
    background-color: rgba(136, 19, 55, 0.08) !important;
}
.dark .searchable-option.is-selected {
    background-color: rgba(251, 113, 133, 0.14) !important;
}
.searchable-option.is-highlighted {
    background-color: rgba(136, 19, 55, 0.05);
}
.dark .searchable-option.is-highlighted {
    background-color: rgba(251, 113, 133, 0.08);
}

/* Active / Focused Input & Button (Clear & Clean Emerald Focus Ring) */
.enter-nav:focus,
.form-input:focus,
button.enter-nav:focus {
    outline: none !important;
    border-color: #059669 !important; /* Emerald 600 */
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.38), 0 1px 2px rgba(0, 0, 0, 0.06) !important;
    background-color: rgba(16, 185, 129, 0.035) !important;
    position: relative;
    z-index: 5;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
}

.dark .enter-nav:focus,
.dark .form-input:focus,
.dark button.enter-nav:focus {
    border-color: #34d399 !important; /* Emerald 400 */
    box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.35), 0 1px 2px rgba(0, 0, 0, 0.3) !important;
    background-color: rgba(52, 211, 153, 0.06) !important;
}

/* Mode Output Card (Clean Minimalist Segmented Selector - No Radio Bullets) */
.prod-mode-card {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 11px 16px;
    border: 1.5px solid var(--color-hairline);
    border-radius: 12px;
    background: var(--color-canvas-soft);
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
    text-align: left;
}
.prod-mode-card:hover {
    border-color: rgba(136, 19, 55, 0.4);
    background: rgba(136, 19, 55, 0.03);
}
.prod-mode-card.is-selected {
    border-color: #881337 !important;
    background: rgba(136, 19, 55, 0.08) !important;
    box-shadow: 0 0 0 2px rgba(136, 19, 55, 0.15) !important;
}
.prod-mode-card.is-selected .prod-mode-title {
    color: #881337 !important;
}
.dark .prod-mode-card:hover {
    border-color: rgba(251, 113, 133, 0.4);
    background: rgba(251, 113, 133, 0.05);
}
.dark .prod-mode-card.is-selected {
    border-color: #fb7185 !important;
    background: rgba(251, 113, 133, 0.15) !important;
    box-shadow: 0 0 0 2px rgba(251, 113, 133, 0.25) !important;
}
.dark .prod-mode-card.is-selected .prod-mode-title {
    color: #fb7185 !important;
}

/* Attached Unit Input Group */
.prod-input-group {
    display: flex;
    align-items: stretch;
    border: 1.5px solid var(--color-hairline-strong, #cbd5e1);
    border-radius: 10px;
    background: var(--color-canvas);
    overflow: hidden;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.prod-input-group:focus-within {
    border-color: #881337 !important;
    box-shadow: 0 0 0 3px rgba(136, 19, 55, 0.15) !important;
}
.dark .prod-input-group:focus-within {
    border-color: #fb7185 !important;
    box-shadow: 0 0 0 3px rgba(251, 113, 133, 0.22) !important;
}
.prod-input-group input {
    flex: 1;
    min-width: 0;
    height: 42px;
    padding: 0 12px;
    font-size: 14px;
    font-weight: 700;
    font-family: var(--font-mono, monospace);
    color: var(--color-ink);
    background: transparent;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
}
.prod-input-group .prod-addon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 14px;
    background: var(--color-canvas-soft, #f8fafc);
    border-left: 1px solid var(--color-hairline);
    color: var(--color-ink-mute);
    font-size: 11.5px;
    font-weight: 800;
    font-family: var(--font-mono, monospace);
    text-transform: uppercase;
    user-select: none;
}

/* Search Field */
.search-box-wrap {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}
.search-box-icon {
    position: absolute;
    left: 12px;
    width: 15px;
    height: 15px;
    color: var(--color-ink-mute);
    pointer-events: none;
}
.search-box-clear {
    position: absolute;
    right: 10px;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    color: var(--color-ink-mute);
    background-color: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    cursor: pointer;
    transition: all 0.15s ease;
}
.search-box-clear:hover {
    color: var(--color-ink);
    border-color: var(--color-hairline-strong);
}
.search-box-input {
    padding-left: 36px !important;
    padding-right: 36px !important;
    padding-top: 8px !important;
    padding-bottom: 8px !important;
    font-size: 13px !important;
    border-radius: var(--rounded-md, 8px) !important;
    border: 1px solid var(--color-hairline-strong) !important;
    background-color: var(--color-canvas) !important;
    color: var(--color-ink) !important;
    width: 100% !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.search-box-input:focus {
    border-color: var(--color-primary, #881337) !important;
    box-shadow: 0 0 0 1px var(--color-primary, #881337) !important;
}

/* Numeric Input Mono */
.form-input-mono {
    font-family: var(--font-mono, ui-monospace, monospace);
    text-align: right;
    font-weight: 700;
}
</style>

<div class="space-y-4 pb-20" x-data="productionApp()">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER (Pola Kanonikal KEREN ONE ERP)                              -->
    <!-- ========================================================================= -->
    <div class="page-header flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-rose" style="flex-shrink:0;">
                <i data-lucide="boxes"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#881337;"></span>
                    <span>Modul Produksi Borongan &bull; Pencatatan &amp; Riwayat Harian</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl font-bold" style="color:var(--color-ink);">
                    <?= htmlspecialchars($pageTitle ?? 'Produksi Borongan') ?>
                </h1>
                <p class="page-subtitle text-xs sm:text-sm" style="color:var(--color-ink-mute);">
                    Pencatatan hasil kerja harian karyawan pengemasan, lembur, dan upah borongan real-time
                </p>
            </div>
        </div>
        <div class="page-header-actions flex items-center gap-2 w-full sm:w-auto justify-end">
            <a href="<?= Router::url('/produksi/history') ?>" class="btn btn-secondary text-xs sm:text-sm flex items-center gap-1.5 w-full sm:w-auto justify-center" style="font-weight:700; height:38px; border-radius:10px;">
                <i data-lucide="history" class="w-4 h-4 text-rose-700 dark:text-rose-400"></i>
                <span>Riwayat &amp; Rekap Produksi</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. DATE NAVIGATOR & ACTION CONTROLS (Executive Dock)                       -->
    <!-- ========================================================================= -->
    <div class="prod-dock-card flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <!-- Left: Date Stepper & Dynamic Badges -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-2">
                <div class="inline-flex items-center p-0.5 rounded-lg border" style="background:var(--color-canvas-soft); border-color:var(--color-hairline);">
                    <a href="<?= Router::url('/produksi?tanggal=' . $kemarin) ?>" 
                       class="px-2.5 py-1.5 rounded-md transition hover:bg-white dark:hover:bg-slate-700" 
                       style="color:var(--color-ink-secondary);"
                       title="Hari Sebelumnya (<?= date('d/m/Y', strtotime($kemarin)) ?>)">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </a>

                    <form method="GET" action="<?= Router::url('/produksi') ?>" class="inline-flex items-center m-0">
                        <input type="date" 
                               name="tanggal" 
                               value="<?= htmlspecialchars($tanggal) ?>" 
                               onchange="this.form.submit()"
                               class="bg-transparent border-0 text-xs font-bold py-1 px-2 focus:ring-0 cursor-pointer"
                               style="color:var(--color-ink); font-family:var(--font-mono);">
                    </form>

                    <a href="<?= Router::url('/produksi?tanggal=' . $besok) ?>" 
                       class="px-2.5 py-1.5 rounded-md transition hover:bg-white dark:hover:bg-slate-700" 
                       style="color:var(--color-ink-secondary);"
                       title="Hari Berikutnya (<?= date('d/m/Y', strtotime($besok)) ?>)">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                </div>

                <?php if (!$isToday): ?>
                    <a href="<?= Router::url('/produksi?tanggal=' . date('Y-m-d')) ?>" 
                       class="btn btn-ghost btn-sm text-xs font-bold py-1.5 px-3 rounded-lg border"
                       style="background:rgba(136, 19, 55, 0.08); color:#881337; border-color:rgba(136, 19, 55, 0.2);">
                        Hari Ini
                    </a>
                <?php endif; ?>
            </div>

            <div class="inline-flex items-center gap-1.5 text-xs font-medium flex-wrap" style="color:var(--color-ink);">
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
                    <span class="badge badge-ghost text-[10px] py-0.5 px-2">Kemarin</span>
                <?php elseif ($diffDays < -1): ?>
                    <span class="badge badge-ghost text-[10px] py-0.5 px-2"><?= abs($diffDays) ?> Hari Lalu</span>
                <?php endif; ?>

                <?php if ($isTanggalLocked): ?>
                    <span class="badge badge-neutral text-[10px] py-0.5 px-2" style="font-family:var(--font-mono);">🔒 Terkunci Payroll</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-xs font-medium" style="color:var(--color-ink-mute);">
            Tercatat Hari Ini: <span class="font-bold" style="color:var(--color-primary, #881337); font-family:var(--font-mono);"><?= count($produksiHariIni) ?> Baris Output</span>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. KPI METRIC CARDS (Standardized ERP Stat Strip)                          -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Card 1: Total Pcs -->
        <div class="prod-stat-card">
            <div class="prod-stat-icon" style="background:rgba(136, 19, 55, 0.1); color:#881337; border:1px solid rgba(136, 19, 55, 0.2);">
                <i data-lucide="package"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--color-ink-mute);">Total Output (Pcs)</div>
                <div class="text-base sm:text-lg font-bold" style="color:var(--color-ink); font-family:var(--font-mono);">
                    <?= number_format($totalPcsHariIni, 0, ',', '.') ?>
                    <span class="text-xs font-normal" style="color:var(--color-ink-mute);">pcs</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Total Bal & Lembur -->
        <div class="prod-stat-card">
            <div class="prod-stat-icon" style="background:rgba(2, 132, 199, 0.1); color:#0284c7; border:1px solid rgba(2, 132, 199, 0.2);">
                <i data-lucide="layers"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--color-ink-mute);">Kemasan Bal &amp; Lembur</div>
                <div class="text-base sm:text-lg font-bold" style="color:#0284c7; font-family:var(--font-mono);">
                    <?= number_format($totalBalHariIni, 0, ',', '.') ?> <span class="text-xs font-normal" style="color:var(--color-ink-mute);">bal</span>
                    <?php if ($totalLemburPcsHariIni > 0): ?>
                        <span class="text-xs font-bold text-rose-600 dark:text-rose-400 ml-1">(+<?= number_format($totalLemburPcsHariIni, 0, ',', '.') ?> pcs)</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Upah -->
        <div class="prod-stat-card">
            <div class="prod-stat-icon" style="background:rgba(16, 185, 129, 0.1); color:#10b981; border:1px solid rgba(16, 185, 129, 0.2);">
                <i data-lucide="wallet"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--color-ink-mute);">Total Upah Borongan</div>
                <div class="text-base sm:text-lg font-bold" style="color:var(--color-success); font-family:var(--font-mono);">
                    <?= Format::rupiah($totalUpahHariIni) ?>
                </div>
            </div>
        </div>

        <!-- Card 4: Karyawan Berproduksi -->
        <div class="prod-stat-card">
            <div class="prod-stat-icon" style="background:rgba(245, 158, 11, 0.1); color:#f59e0b; border:1px solid rgba(245, 158, 11, 0.2);">
                <i data-lucide="users"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--color-ink-mute);">Karyawan Berproduksi</div>
                <div class="text-base sm:text-lg font-bold" style="color:var(--color-ink); font-family:var(--font-mono);">
                    <?= $totalKaryawanBerproduksi ?> <span class="text-xs font-normal" style="color:var(--color-ink-mute);">/ <?= $totalKaryawanBorongan ?> aktif</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. DYNAMIC MULTI-ITEM INPUT FORM PER KARYAWAN (INLINE CREATE & EDIT)       -->
    <!-- ========================================================================= -->
    <div id="formProduksiCard" class="card p-4 sm:p-5 transition-all">
        <!-- MODE EDIT BANNER NOTIFICATION -->
        <template x-if="isEditMode">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 mb-4 rounded-xl border transition-all"
                 style="background: #fffbeb; border-color: #fcd34d;">
                <div class="flex items-center gap-3 min-w-0">
                    <div style="width:36px; height:36px; border-radius:8px; background:#fef3c7; color:#d97706; border:1px solid #fde68a; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <svg style="width:17px; height:17px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span style="display:inline-block; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:800; background:#d97706; color:#ffffff; letter-spacing:0.02em;">
                                MODE EDIT AKTIF
                            </span>
                            <span class="text-xs font-extrabold text-slate-800 dark:text-slate-100 truncate" x-text="editKaryawanName"></span>
                        </div>
                        <div style="font-size:11.5px; color:#92400e; margin-top:2px; line-height:1.35;">
                            Data produksi sedang diedit di formulir. Lakukan perubahan lalu klik simpan, atau batalkan kapan saja.
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button type="button" 
                            @click="cancelEdit()" 
                            class="btn btn-secondary text-xs font-bold px-3 py-1.5 rounded-lg border flex items-center gap-1.5 transition hover:bg-slate-100 dark:hover:bg-slate-800"
                            style="height:32px; background:#ffffff; color:#334155; border-color:#cbd5e1;">
                        <svg style="width:14px; height:14px; color:#64748b;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>Batalkan Mode Edit</span>
                    </button>
                </div>
            </div>
        </template>

        <!-- Header Form -->
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding-bottom:12px;border-bottom:1px solid var(--color-hairline);margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                <div class="prod-section-icon is-maroon" style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="package-plus" style="width:18px;height:18px;"></i>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:14px;font-weight:800;color:var(--color-ink);line-height:1.2;margin:0;" x-text="isEditMode ? 'Form Edit Hasil Produksi Karyawan' : 'Form Input Hasil Produksi Karyawan'">Form Input Hasil Produksi Karyawan</div>
                    <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.3;" x-text="isEditMode ? 'Sesuaikan jumlah produk atau tambah/hapus item yang dikerjakan karyawan ini' : 'Pilih karyawan pengemasan dan tentukan seluruh item produk yang dikerjakan'">Pilih karyawan pengemasan dan tentukan seluruh item produk yang dikerjakan</div>
                </div>
            </div>
        </div>

        <form id="formProduksi" 
              method="POST" 
              action="<?= Router::url('/produksi/store') ?>" 
              data-add-row-btn="#btn-add-row"
              @submit="if(!submitProductionForm($event)) $event.preventDefault();"
              class="space-y-4">
            <?= CSRF::field() ?>
            <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>">
            <input type="hidden" name="is_edit" :value="isEditMode ? '1' : '0'">

            <!-- Field Karyawan & Status Kehadiran Terpadu -->
            <div>
                <!-- Custom Searchable Select Karyawan -->
                <div class="relative" @click.outside="karyawanDropdownOpen = false">
                    <button type="button"
                            id="btnKaryawan"
                            :disabled="isEditMode"
                            @click="if(!isEditMode) toggleKaryawanDropdown()"
                            class="form-input flex items-center justify-between w-full text-left font-medium transition enter-nav"
                            :style="isEditMode ? 'height:42px; border-radius:10px; background-color:var(--color-canvas-soft); color:var(--color-ink); border:1px solid var(--color-hairline-strong); cursor:not-allowed; opacity:0.9; padding:0 12px;' : 'height:42px; border-radius:10px; background-color:var(--color-canvas); color:var(--color-ink); border:1px solid var(--color-hairline-strong); cursor:pointer; padding:0 12px;'">
                        <div class="flex items-center gap-2 min-w-0 pr-2">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                            <span class="truncate text-xs font-bold" 
                                  :style="!selectedKaryawan ? 'color:var(--color-ink-mute); font-weight:500;' : 'color:var(--color-ink);'"
                                  x-text="selectedKaryawan && currentKaryawan ? (currentKaryawan.name + ' (' + currentKaryawan.posisi + ')' + (currentKaryawan.itemsCount > 0 ? (' • ' + currentKaryawan.itemsCount + ' item tercatat') : '')) : '-- Pilih Nama Karyawan Borongan / Pengemasan --'">
                            </span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 flex-shrink-0 transition-transform duration-200" :style="karyawanDropdownOpen ? 'transform:rotate(180deg)' : ''" x-show="!isEditMode"></i>
                        <span x-show="isEditMode" class="badge badge-mono text-[9px] font-bold" style="background:rgba(136,19,55,0.1); color:#881337;">Terkunci</span>
                    </button>

                    <input type="hidden" name="karyawan_id" :value="selectedKaryawan" required>

                    <!-- Floating Searchable Menu -->
                    <div x-show="karyawanDropdownOpen && !isEditMode" x-cloak
                         class="dropdown-menu-searchable"
                         style="position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:1050; border-radius:12px; overflow:hidden; background:var(--color-canvas); border:1px solid var(--color-hairline); box-shadow:0 14px 34px -4px rgba(0,0,0,0.18);">
                        
                        <!-- Search Box inside Dropdown -->
                        <div style="padding:8px 10px; border-bottom:1px solid var(--color-hairline); background:var(--color-canvas-soft);">
                            <div style="position:relative; display:flex; align-items:center;">
                                <i data-lucide="search" style="position:absolute; left:10px; width:14px; height:14px; color:var(--color-ink-mute); pointer-events:none;"></i>
                                <input type="text" 
                                       x-ref="karyawanSearchInput"
                                       x-model="karyawanSearch"
                                       @keydown.escape.prevent="karyawanDropdownOpen = false"
                                       @keydown.down.prevent="navigateKaryawan(1)"
                                       @keydown.up.prevent="navigateKaryawan(-1)"
                                       @keydown.enter.prevent="selectHighlightedKaryawan()"
                                       placeholder="Ketik untuk cari nama karyawan / posisi..."
                                       class="form-input sd-search"
                                       style="height:34px; padding-left:32px; font-size:12px; border-radius:8px; width:100%; background:var(--color-canvas);">
                            </div>
                        </div>

                        <!-- Option List -->
                        <div style="max-height:220px; overflow-y:auto;" class="custom-scrollbar" x-ref="karyawanListWrap">
                            <template x-for="(k, idx) in filteredKaryawan" :key="k.id">
                                <div @click="selectKaryawan(k.id)"
                                     class="searchable-option"
                                     :class="[(String(k.id) === String(selectedKaryawan) ? 'is-selected' : ''), (karyawanHighlightedIndex === idx ? 'is-highlighted' : '')]"
                                     style="padding:8px 12px; font-size:12px; cursor:pointer; display:flex; align-items:center; justify-content:space-between; gap:8px; border-bottom:1px solid var(--color-hairline-soft);">
                                    <div style="min-width:0; flex:1;">
                                        <div style="font-weight:700; color:var(--color-ink);" x-text="k.name"></div>
                                        <div style="font-size:11px; color:var(--color-ink-mute);" x-text="k.posisi"></div>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                                        <template x-if="k.itemsCount > 0">
                                            <span class="badge badge-mono text-[10px]" style="color:#881337; font-weight:700;" x-text="k.itemsCount + ' item'"></span>
                                        </template>
                                        <template x-if="k.absen === 'hadir'">
                                            <span class="badge badge-success text-[10px] py-0.5 px-1.5 font-bold">Sudah Absen</span>
                                        </template>
                                        <template x-if="k.absen === 'belum_absen'">
                                            <span class="badge badge-warning text-[10px] py-0.5 px-1.5 font-medium">Belum Absen</span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <div x-show="filteredKaryawan.length === 0" style="padding:14px; text-align:center; font-size:11.5px; color:var(--color-ink-mute);">
                                Tidak ada karyawan yang cocok dengan pencarian
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PLACEHOLDER JIKA KARYAWAN BELUM DIPILIH -->
            <div x-show="!selectedKaryawan" style="padding:22px 16px;text-align:center;border:1px dashed var(--color-hairline-strong);border-radius:12px;background:var(--color-canvas-soft);margin-top:14px;">
                <div class="prod-empty-icon is-maroon" style="margin:0 auto 6px auto;width:36px;height:36px;border-radius:10px;">
                    <i data-lucide="user-check" style="width:18px;height:18px;"></i>
                </div>
                <div style="font-weight:700;font-size:13px;color:var(--color-ink);margin:0;line-height:1.3;">Pilih Karyawan Borongan Terlebih Dahulu</div>
                <div style="font-size:11.5px;margin-top:3px;color:var(--color-ink-mute);line-height:1.4;">Pilih nama karyawan di atas untuk mulai memasukkan rincian item produk yang dikerjakan.</div>
            </div>

            <!-- MULTI-ITEM ROWS CONTAINER (MUNCUL JIKA KARYAWAN DIPILIH) -->
            <div x-show="selectedKaryawan" x-cloak class="space-y-3 pt-2">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding-bottom:10px;border-bottom:1px solid var(--color-hairline);">
                    <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                        <span style="font-size:12.5px;font-weight:800;color:var(--color-ink);">
                            Rincian Output: <span style="color:#881337;" x-text="currentKaryawan ? currentKaryawan.name : ''"></span>
                        </span>
                        <span class="badge badge-mono text-[10px]" x-text="rows.length + ' Item'"></span>
                    </div>

                    <!-- Single Global Toggle Lembur (Calm Warm Amber Pill) -->
                    <button type="button" 
                            @click="toggleGlobalLembur()" 
                            class="toggle-switch-pill" 
                            :class="is_lembur ? 'is-active' : ''"
                            title="Klik untuk beralih mode lembur">
                        <i data-lucide="zap" style="width:13px;height:13px;" :class="is_lembur ? 'text-amber-700 dark:text-amber-300' : 'text-amber-600/80'"></i>
                        <span x-text="is_lembur ? 'Lembur Aktif' : 'Lembur'"></span>
                    </button>
                </div>

                <!-- Template Baris-Baris Item Produk -->
                <template x-for="(row, index) in rows" :key="index">
                    <div class="prod-item-row-box relative p-3.5 rounded-xl border" style="background:var(--color-canvas-soft); border-color:var(--color-hairline);">
                        <!-- Baris Header Item -->
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding-bottom:8px;margin-bottom:12px;border-bottom:1px solid var(--color-hairline);">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span class="inline-flex items-center justify-center font-bold text-xs rounded-full" 
                                      style="width:22px; height:22px; background:rgba(136,19,55,0.08); color:#881337; font-family:var(--font-mono); border:1px solid rgba(136,19,55,0.2);" 
                                      x-text="index + 1">
                                </span>
                                <span style="font-size:12.5px;font-weight:700;color:var(--color-ink);">Item Produk</span>
                                <span class="text-[11px] font-semibold" 
                                      style="color:#881337; font-family:var(--font-mono);" 
                                      x-show="getRowRate(row.item_id) > 0" 
                                      x-text="'(Rp ' + getRowRate(row.item_id).toLocaleString('id-ID') + '/pcs)'">
                                </span>
                            </div>

                            <!-- Tombol Hapus Baris Minimalis -->
                            <button type="button" 
                                    x-show="rows.length > 1" 
                                    @click="removeRow(index)" 
                                    class="btn-delete-row btn-remove-row" 
                                    title="Hapus Baris Item Ini">
                                <i data-lucide="trash-2" style="width:13px;height:13px;"></i>
                                <span>Hapus</span>
                            </button>
                        </div>

                        <!-- Grid Input Baris Item (Clean & Balanced Form Layout) -->
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                            <!-- Dropdown Produk Borongan Searchable (6 cols) -->
                            <div class="md:col-span-6 relative" @click.outside="row.dropdownOpen = false">
                                <label class="block text-[11.5px] font-semibold mb-1.5" style="color:var(--color-ink-secondary);">
                                    Produk Borongan <span class="text-rose-600">*</span>
                                </label>
                                
                                <button type="button"
                                        @click="toggleRowDropdown(row, index)"
                                        class="form-input flex items-center justify-between w-full text-left font-medium transition enter-nav"
                                        style="height:38px; border-radius:8px; background-color:var(--color-canvas); color:var(--color-ink); border:1px solid var(--color-hairline-strong); cursor:pointer; padding:0 10px;">
                                    <span class="truncate text-xs font-semibold" 
                                          :style="!row.item_id ? 'color:var(--color-ink-mute); font-weight:500;' : 'color:var(--color-ink);'"
                                          x-text="row.item_id && itemsMap[row.item_id] ? (itemsMap[row.item_id].nama_item + ' - [' + itemsMap[row.item_id].nama_kelompok + ']') : '-- Pilih Item Produk --'">
                                    </span>
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 transition-transform duration-200" :style="row.dropdownOpen ? 'transform:rotate(180deg)' : ''"></i>
                                </button>

                                <input type="hidden" :name="'items[' + index + '][item_id]'" :value="row.item_id" required>

                                <!-- Floating Searchable Items Menu -->
                                <div x-show="row.dropdownOpen" x-cloak
                                     class="dropdown-menu-searchable"
                                     style="position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:1050; border-radius:12px; overflow:hidden; background:var(--color-canvas); border:1px solid var(--color-hairline); box-shadow:0 14px 34px -4px rgba(0,0,0,0.18);">
                                    
                                    <!-- Search Box inside Dropdown -->
                                    <div style="padding:6px 8px; border-bottom:1px solid var(--color-hairline); background:var(--color-canvas-soft);">
                                        <div style="position:relative; display:flex; align-items:center;">
                                            <i data-lucide="search" style="position:absolute; left:8px; width:13px; height:13px; color:var(--color-ink-mute); pointer-events:none;"></i>
                                            <input type="text" 
                                                   :id="'item-search-input-' + index"
                                                   x-model="row.search"
                                                   @input="row.highlightedIndex = 0"
                                                   @keydown.escape.prevent="row.dropdownOpen = false"
                                                   @keydown.down.prevent="navigateItem(row, 1)"
                                                   @keydown.up.prevent="navigateItem(row, -1)"
                                                   @keydown.enter.prevent="selectHighlightedItem(row)"
                                                   placeholder="Cari produk / kelompok (misal: 300, Basreng)..."
                                                   class="form-input sd-search"
                                                   style="height:32px; padding-left:28px; font-size:11.5px; border-radius:6px; width:100%; background:var(--color-canvas);">
                                        </div>
                                    </div>

                                    <!-- Items Option List -->
                                    <div style="max-height:220px; overflow-y:auto;" class="custom-scrollbar" :id="'item-list-wrap-' + index">
                                        <template x-for="(item, idx) in getFilteredItems(row)" :key="item.id">
                                            <div @click="selectRowItem(row, item.id)"
                                                 class="searchable-option"
                                                 :class="[(String(item.id) === String(row.item_id) ? 'is-selected' : ''), (row.highlightedIndex === idx ? 'is-highlighted' : '')]"
                                                 style="padding:7px 10px; font-size:12px; cursor:pointer; display:flex; align-items:center; justify-content:space-between; gap:8px; border-bottom:1px solid var(--color-hairline-soft);">
                                                <div style="min-width:0; flex:1;">
                                                    <div style="font-weight:700; color:var(--color-ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" x-text="item.nama_item"></div>
                                                    <div style="font-size:11px; color:var(--color-ink-mute); display:flex; align-items:center; gap:6px; margin-top:2px;">
                                                        <span class="badge badge-mono text-[10px] py-0 px-1 font-bold" style="background:rgba(136,19,55,0.08); color:#881337;" x-text="item.nama_kelompok"></span>
                                                        <span x-text="'Rp ' + parseFloat(item.upah_per_bungkus).toLocaleString('id-ID') + '/pcs'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                        <div x-show="getFilteredItems(row).length === 0" style="padding:12px; text-align:center; font-size:11.5px; color:var(--color-ink-mute);">
                                            Tidak ada produk / kelompok yang cocok
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dual Qty Inputs: 6 cols total, 2 cols side-by-side -->
                            <div class="md:col-span-6 grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-[11.5px] font-semibold mb-1.5 truncate" 
                                           :style="is_lembur ? 'color:#881337;' : 'color:var(--color-ink-secondary);'">
                                        <span x-text="is_lembur ? 'Lembur (Pcs)' : 'Hasil (Pcs)'"></span>
                                    </label>
                                    <input type="number" 
                                           :name="is_lembur ? ('items[' + index + '][lembur_pcs]') : ('items[' + index + '][kuantitas_pcs]')" 
                                           x-model.number="row.kuantitas_pcs" 
                                           min="1" 
                                           placeholder="Min. 1" 
                                           required
                                           class="form-input form-input-sm text-xs form-input-mono rounded-lg w-full enter-nav"
                                           :style="is_lembur ? 'height:38px; background-color:var(--color-canvas); color:#881337; border-color:rgba(136,19,55,0.35); font-weight:700;' : 'height:38px; background-color:var(--color-canvas); color:var(--color-ink); border-color:var(--color-hairline-strong);'">
                                </div>

                                <div>
                                    <label class="block text-[11.5px] font-semibold mb-1.5 truncate" 
                                           :style="is_lembur ? 'color:#881337;' : 'color:var(--color-ink-secondary);'">
                                        <span x-text="is_lembur ? 'Lembur (Bal)' : 'Kemasan (Bal)'"></span>
                                    </label>
                                    <input type="number" 
                                           :name="is_lembur ? ('items[' + index + '][lembur_bal]') : ('items[' + index + '][kuantitas_bal]')" 
                                           x-model.number="row.kuantitas_bal" 
                                           min="0" 
                                           placeholder="0" 
                                           class="form-input form-input-sm text-xs form-input-mono rounded-lg w-full enter-nav"
                                           :style="is_lembur ? 'height:38px; background-color:var(--color-canvas); color:#881337; border-color:rgba(136,19,55,0.35); font-weight:700;' : 'height:38px; background-color:var(--color-canvas); color:var(--color-ink); border-color:var(--color-hairline-strong);'">
                                </div>
                            </div>
                        </div>

                        <!-- Baris Subtotal -->
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:12px;padding-top:8px;border-top:1px solid var(--color-hairline);font-size:12px;">
                            <div style="font-size:11.5px;color:var(--color-ink-mute);display:flex;align-items:center;gap:6px;">
                                <span>Output:</span>
                                <b style="color:var(--color-ink); font-family:var(--font-mono);" x-text="(parseInt(row.kuantitas_pcs) || 0) + ' pcs'"></b>
                                <span x-show="is_lembur" class="badge text-[9px] py-0 px-1.5 font-bold" style="background:rgba(136,19,55,0.12); color:#881337; border:1px solid rgba(136,19,55,0.25);">Lembur</span>
                            </div>
                            
                            <div style="display:flex;align-items:center;gap:6px;font-family:var(--font-mono);">
                                <span style="font-size:11px;color:var(--color-ink-mute);">Subtotal Upah:</span>
                                <span class="badge badge-success text-xs font-bold py-0.5 px-2" x-text="'Rp ' + calculateRowSubtotal(row).toLocaleString('id-ID')">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Tombol Tambah Baris Produk Baru -->
                <div class="pt-1.5">
                    <button type="button" id="btn-add-row" @click="addRow()" class="btn-add-item-row">
                        <i data-lucide="plus" style="width:14px;height:14px;stroke-width:2.4;"></i>
                        <span>Tambah Item Produk Lain</span>
                    </button>
                </div>

                <!-- Action Footer & Grand Total Estimation -->
                <div class="rounded-xl border p-3 sm:p-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-3" 
                     style="background-color: var(--color-canvas-soft); border-color: var(--color-hairline);">
                    
                    <!-- Total Upah Section: Left/Right spread on mobile, inline on desktop -->
                    <div class="flex items-center justify-between sm:justify-start gap-2.5 min-w-0">
                        <div class="flex items-center gap-2.5">
                            <div class="prod-section-icon is-emerald" style="width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <i data-lucide="wallet" style="width:16px; height:16px;"></i>
                            </div>
                            <div>
                                <div style="font-size:11px; color:var(--color-ink-mute); line-height:1.2;">Total Estimasi Upah:</div>
                                <div class="hidden sm:block text-base font-bold" style="color:var(--color-success); font-family:var(--font-mono); line-height:1.2; margin-top:2px;" x-text="'Rp ' + totalGrandPreview.toLocaleString('id-ID')">Rp 0</div>
                            </div>
                        </div>
                        
                        <!-- Big Green Rupiah on Mobile (Right aligned) -->
                        <div class="sm:hidden text-base font-extrabold" style="color:var(--color-success); font-family:var(--font-mono);" x-text="'Rp ' + totalGrandPreview.toLocaleString('id-ID')">
                            Rp 0
                        </div>
                    </div>

                    <!-- Action Buttons: Responsive full width on mobile, compact on desktop -->
                    <div class="flex items-center gap-2 w-full sm:w-auto pt-2.5 sm:pt-0 border-t sm:border-t-0" style="border-color: var(--color-hairline);">
                        <button type="button" 
                                @click="isEditMode ? cancelEdit() : resetForm()" 
                                class="btn btn-secondary btn-sm text-xs px-3.5 font-bold" 
                                style="border-radius:8px; height:38px;">
                            <span x-text="isEditMode ? 'Batal Edit' : 'Reset'">Reset</span>
                        </button>
                        <button type="submit" 
                                class="btn btn-primary btn-sm text-xs flex-1 sm:flex-initial flex items-center justify-center gap-2 px-4 shadow-sm font-bold" 
                                style="background:#881337; border-color:#700f2d; color:#ffffff; border-radius:8px; height:38px;">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span x-text="isEditMode ? ('Simpan Perubahan (' + rows.length + ' Item)') : ('Simpan ' + rows.length + ' Item Produksi')">Simpan Hasil Produksi</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. REKAPITULASI HASIL PRODUKSI HARI INI (1 BARIS = 1 KARYAWAN)            -->
    <!-- ========================================================================= -->
    <div class="table-wrapper">
        <!-- Card Header & Live Search -->
        <div style="padding:14px 16px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;border-bottom:1px solid var(--color-hairline);background:var(--color-canvas);">
            <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                <div class="prod-section-icon is-emerald" style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="list-checks" style="width:18px;height:18px;"></i>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:14px;font-weight:800;color:var(--color-ink);line-height:1.2;margin:0;">Daftar Hasil Produksi &bull; <?= Format::tanggalIndo($tanggal) ?></div>
                    <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.3;">Ringkasan akumulasi hasil kerja per karyawan pada tanggal ini</div>
                </div>
            </div>

            <!-- Search Field -->
            <div class="search-box-wrap sm:max-w-xs" style="margin-left:auto;">
                <i data-lucide="search" class="search-box-icon"></i>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Cari karyawan atau produk..." 
                       class="search-box-input">
                <button type="button" x-show="searchQuery" @click="searchQuery = ''" class="search-box-clear">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>

        <!-- Clean ERP Standard Data Table with Horizontal Scroll -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 820px;">
                <thead>
                    <tr>
                        <th style="width: 48px; text-align: center;">No</th>
                        <th style="min-width: 170px;">Karyawan</th>
                        <th style="min-width: 170px;">Rincian Item Produk</th>
                        <th class="cell-right" style="width: 110px;">Total Reguler</th>
                        <th class="cell-right" style="width: 95px;">Lembur</th>
                        <th class="cell-right" style="width: 130px;">Total Upah</th>
                        <th style="width: 110px; text-align: center;">Status Payroll</th>
                        <th style="width: 90px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produksiPerKaryawan)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:44px 16px; color:var(--color-ink-mute);">
                                <div class="prod-empty-icon is-maroon">
                                    <i data-lucide="package-open"></i>
                                </div>
                                <div style="font-weight:700; font-size:14px; color:var(--color-ink);">Belum Ada Catatan Produksi</div>
                                <div style="font-size:12px; margin-top:3px; color:var(--color-ink-mute);">
                                    Gunakan form di atas untuk mencatat hasil kerja borongan pada tanggal <?= Format::tanggalIndo($tanggal) ?>.
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $rowIdx = 0;
                        foreach ($produksiPerKaryawan as $kid => $emp): 
                            $rowIdx++;
                            $isLocked = $emp['is_locked'];
                            $initials = getInitials($emp['nama_karyawan']);
                            $itemsCount = count($emp['items']);
                            
                            // Build search key for client-side instant live filter
                            $itemNames = array_map(fn($it) => ($it['nama_item'] ?? '') . ' ' . ($it['kode_sku'] ?? '') . ' ' . ($it['nama_kelompok'] ?? ''), $emp['items']);
                            $searchKey = strtolower($emp['nama_karyawan'] . ' ' . ($emp['nama_panggilan'] ?? '') . ' ' . ($emp['posisi'] ?? 'pengemasan') . ' ' . implode(' ', $itemNames));
                            
                            $empPayload = [
                                'karyawan_id' => $emp['karyawan_id'],
                                'nama_karyawan' => $emp['nama_karyawan'],
                                'posisi' => $emp['posisi'] ?? 'Pengemasan',
                                'total_pcs' => (int)$emp['total_pcs'],
                                'total_bal' => (int)$emp['total_bal'],
                                'total_lembur_pcs' => (int)$emp['total_lembur_pcs'],
                                'total_lembur_bal' => (int)$emp['total_lembur_bal'],
                                'total_upah' => (float)$emp['total_upah'],
                                'is_locked' => (bool)$emp['is_locked'],
                                'nomor_payroll' => $emp['nomor_payroll'] ?? null,
                                'has_lembur' => (bool)$emp['has_lembur'],
                                'items' => array_map(function($it) {
                                    return [
                                        'id' => $it['id'],
                                        'item_id' => $it['item_id'],
                                        'nama_item' => $it['nama_item'],
                                        'kode_sku' => $it['kode_sku'] ?? '',
                                        'nama_kelompok' => $it['nama_kelompok'] ?? '',
                                        'upah_per_pcs_snapshot' => (float)$it['upah_per_pcs_snapshot'],
                                        'kuantitas_pcs' => (int)$it['kuantitas_pcs'],
                                        'kuantitas_bal' => (int)$it['kuantitas_bal'],
                                        'lembur_pcs' => (int)$it['lembur_pcs'],
                                        'lembur_bal' => (int)$it['lembur_bal'],
                                        'total_upah_didapat' => (float)$it['total_upah_didapat']
                                    ];
                                }, $emp['items'])
                            ];
                        ?>
                        <tr x-show="matchSearch(<?= htmlspecialchars(json_encode($searchKey), ENT_QUOTES, 'UTF-8') ?>)">
                            <!-- No -->
                            <td style="text-align: center; font-family:var(--font-mono); color:var(--color-ink-mute); font-size: 12px;"><?= $rowIdx ?></td>

                            <!-- Karyawan Info -->
                            <td style="font-weight:600; color:var(--color-ink); vertical-align: middle;">
                                <div class="flex items-center gap-2.5">
                                    <div class="prod-table-avatar">
                                        <?= $initials ?>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <span class="truncate block text-xs font-bold" style="color:var(--color-ink);"><?= htmlspecialchars($emp['nama_karyawan']) ?></span>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-[10.5px] font-normal" style="color:var(--color-ink-mute);"><?= htmlspecialchars($emp['posisi'] ?? 'Pengemasan') ?></span>
                                            <span class="badge badge-mono text-[9.5px] font-bold py-0 px-1.5" style="background:rgba(136,19,55,0.08); color:#881337;">
                                                <?= $itemsCount ?> Produk
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Rincian Item Produk (Tombol Detail Modal Ringkas & Rapi) -->
                            <td style="vertical-align: middle; padding: 10px 14px;">
                                <button type="button" 
                                        @click="openDetailModal(<?= htmlspecialchars(json_encode($empPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)"
                                        class="btn btn-secondary btn-sm inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold rounded-lg border transition hover:border-rose-400 hover:bg-rose-50/60 dark:hover:bg-rose-950/40"
                                        style="height: 32px; background: var(--color-canvas-soft); border-color: var(--color-hairline); color: var(--color-ink);"
                                        title="Lihat rincian lengkap item produk">
                                    <i data-lucide="package" class="w-3.5 h-3.5 text-rose-700 dark:text-rose-400 flex-shrink-0"></i>
                                    <span><?= $itemsCount ?> Item Produk</span>
                                    <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 ml-0.5"></i>
                                </button>
                            </td>

                            <!-- Total Reguler -->
                            <td class="cell-right" style="font-family:var(--font-mono); font-weight:700; color:var(--color-ink); vertical-align: middle;">
                                <?= number_format($emp['total_pcs'], 0, ',', '.') ?> pcs
                                <?php if ($emp['total_bal'] > 0): ?>
                                    <span class="text-[10.5px] font-normal block text-slate-400"><?= $emp['total_bal'] ?> bal</span>
                                <?php endif; ?>
                            </td>

                            <!-- Total Lembur -->
                            <td class="cell-right" style="font-family:var(--font-mono); font-weight:700; vertical-align: middle;">
                                <?php if ($emp['total_lembur_pcs'] > 0 || $emp['total_lembur_bal'] > 0): ?>
                                    <span style="color:#881337;"><?= number_format($emp['total_lembur_pcs'], 0, ',', '.') ?> pcs</span>
                                    <?php if ($emp['total_lembur_bal'] > 0): ?>
                                        <span class="text-[10px] font-normal block text-rose-400"><?= $emp['total_lembur_bal'] ?> bal</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:var(--color-ink-mute); font-weight:400;">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Total Upah -->
                            <td class="cell-right" style="font-family:var(--font-mono); font-weight:800; font-size:13px; color:var(--color-success); vertical-align: middle;">
                                <?= Format::rupiah((float)$emp['total_upah']) ?>
                            </td>

                            <!-- Status Payroll -->
                            <td style="text-align: center; vertical-align: middle;">
                                <?php if ($isLocked): ?>
                                    <span class="badge badge-neutral text-[9px] py-0.5 px-2" style="font-family:var(--font-mono);">
                                        🔒 <?= htmlspecialchars($emp['nomor_payroll'] ?? 'Payroll') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning text-[9.5px] py-0.5 px-2 font-semibold">⏳ Belum Digaji</span>
                                <?php endif; ?>
                            </td>

                            <!-- Aksi -->
                            <td style="text-align: center; vertical-align: middle;">
                                <?php if ($isLocked): ?>
                                    <span class="text-[11px] italic" style="color:var(--color-ink-mute);">Locked</span>
                                <?php else: ?>
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Tombol Edit Inline -->
                                        <button type="button" 
                                                @click="startEdit(<?= htmlspecialchars(json_encode($empPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)" 
                                                class="btn btn-ghost btn-sm p-1.5 transition text-slate-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40" 
                                                title="Edit Data Produksi di Form Atas">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>

                                        <!-- Tombol Hapus Branded Confirmation -->
                                        <button type="button" 
                                                @click="deleteKaryawan('<?= htmlspecialchars($emp['karyawan_id']) ?>', '<?= htmlspecialchars(addslashes($emp['nama_karyawan'])) ?>', <?= $itemsCount ?>, '<?= Format::rupiah((float)$emp['total_upah']) ?>')" 
                                                class="btn btn-ghost btn-sm p-1.5 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" 
                                                title="Hapus Seluruh Catatan Produksi Karyawan Ini">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <!-- State Jika Pencarian Tidak Menemukan Hasil -->
                        <tr x-show="searchQuery && !hasSearchResults" x-cloak>
                            <td colspan="8" style="text-align: center; padding: 36px 16px; color: var(--color-ink-mute);">
                                <div class="prod-empty-icon is-maroon" style="width: 38px; height: 38px; margin: 0 auto 8px auto;">
                                    <i data-lucide="search-x" style="width: 18px; height: 18px;"></i>
                                </div>
                                <div style="font-weight: 700; font-size: 13.5px; color: var(--color-ink);">Pencarian Tidak Ditemukan</div>
                                <div style="font-size: 11.5px; margin-top: 3px;">
                                    Tidak ada catatan produksi yang cocok dengan kata kunci "<span class="font-bold" style="color: var(--color-ink);" x-text="searchQuery"></span>".
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 6. MODAL POPUP RINCIAN ITEM PRODUK KARYAWAN                               -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="showDetailModal" 
             x-cloak 
             class="modal-backdrop" 
             @click="closeDetailModal()"
             @keydown.escape.window="closeDetailModal()">
            <div class="modal-box modal-box-lg" style="max-width: 620px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>
                
                <!-- Modal Header -->
                <div class="modal-header" style="padding: 16px 20px; border-bottom: 1px solid var(--color-hairline); display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div class="prod-section-icon is-maroon" style="width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="package" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <div class="modal-title text-base font-extrabold" style="color: var(--color-ink); line-height: 1.2;">
                                    Rincian Hasil Produksi
                                </div>
                                <template x-if="detailEmp && detailEmp.is_locked">
                                    <span class="badge badge-neutral text-[10px] py-0.5 px-2 font-mono flex-shrink-0">
                                        🔒 Terkunci Payroll (<span x-text="detailEmp.nomor_payroll || 'Terkunci'"></span>)
                                    </span>
                                </template>
                                <template x-if="detailEmp && !detailEmp.is_locked">
                                    <span class="badge badge-warning text-[10px] py-0.5 px-2 font-semibold flex-shrink-0">
                                        ⏳ Belum Digaji
                                    </span>
                                </template>
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5" x-text="detailEmp ? (detailEmp.nama_karyawan + ' (' + (detailEmp.posisi || 'Pengemasan') + ') • ' + '<?= Format::tanggalIndo($tanggal) ?>') : ''"></div>
                        </div>
                    </div>
                    <button type="button" @click="closeDetailModal()" class="modal-close-x modal-close-always" title="Tutup Modal">
                        <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body custom-scrollbar space-y-4" style="padding: 16px 20px; max-height: 70vh; overflow-y: auto;">
                    <!-- Ringkasan Singkat KPI Mini Bar -->
                    <div class="grid grid-cols-3 gap-2.5 p-3 rounded-xl border" style="background: var(--color-canvas-soft); border-color: var(--color-hairline);">
                        <div class="text-center">
                            <div class="text-[10.5px] uppercase font-bold text-slate-400">Total Output</div>
                            <div class="text-sm font-black text-slate-800 dark:text-slate-100 font-mono mt-0.5" x-text="detailEmp ? ((detailEmp.total_pcs || 0).toLocaleString('id-ID') + ' pcs') : '0 pcs'"></div>
                            <div class="text-[10px] text-slate-400" x-show="detailEmp && detailEmp.total_bal > 0" x-text="detailEmp ? (detailEmp.total_bal + ' bal') : ''"></div>
                        </div>
                        <div class="text-center border-x" style="border-color: var(--color-hairline);">
                            <div class="text-[10.5px] uppercase font-bold text-slate-400">Total Lembur</div>
                            <div class="text-sm font-black font-mono mt-0.5" :style="detailEmp && (detailEmp.total_lembur_pcs > 0 || detailEmp.total_lembur_bal > 0) ? 'color:#881337;' : 'color:var(--color-ink-mute);'" x-text="detailEmp && detailEmp.total_lembur_pcs > 0 ? (detailEmp.total_lembur_pcs.toLocaleString('id-ID') + ' pcs') : '-'"></div>
                            <div class="text-[10px] text-rose-400" x-show="detailEmp && detailEmp.total_lembur_bal > 0" x-text="detailEmp ? (detailEmp.total_lembur_bal + ' bal') : ''"></div>
                        </div>
                        <div class="text-center">
                            <div class="text-[10.5px] uppercase font-bold text-slate-400">Total Upah</div>
                            <div class="text-sm font-black font-mono mt-0.5 text-emerald-600 dark:text-emerald-400" x-text="detailEmp ? ('Rp ' + (detailEmp.total_upah || 0).toLocaleString('id-ID')) : 'Rp 0'"></div>
                        </div>
                    </div>

                    <!-- Daftar Item Produk -->
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-600 dark:text-slate-300">
                            <span class="uppercase tracking-wider text-[11px] text-slate-400">Daftar Item Dikerjakan (<span x-text="detailEmp && detailEmp.items ? detailEmp.items.length : 0"></span> Produk)</span>
                        </div>

                        <template x-for="(it, idx) in (detailEmp ? detailEmp.items : [])" :key="idx">
                            <div class="p-3.5 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition hover:border-slate-300 dark:hover:border-slate-700"
                                 style="background: var(--color-canvas); border-color: var(--color-hairline);">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center justify-center font-bold text-xs rounded-full flex-shrink-0" 
                                              style="width: 22px; height: 22px; background: rgba(136,19,55,0.08); color: #881337; font-family: var(--font-mono); border: 1px solid rgba(136,19,55,0.2);" 
                                              x-text="idx + 1">
                                        </span>
                                        <span class="font-bold text-xs sm:text-sm text-slate-800 dark:text-slate-100" style="color: var(--color-ink);" x-text="it.nama_item"></span>
                                        <template x-if="it.nama_kelompok">
                                            <span class="badge badge-mono text-[10px] px-1.5 py-0.5 font-bold" 
                                                  style="background: rgba(136,19,55,0.08); color: #881337; border: 1px solid rgba(136,19,55,0.2);" 
                                                  x-text="it.nama_kelompok">
                                            </span>
                                        </template>
                                        <template x-if="it.lembur_pcs > 0 || it.lembur_bal > 0">
                                            <span class="badge text-[9.5px] font-bold py-0.5 px-1.5 inline-flex items-center gap-1" 
                                                  style="background: rgba(245,158,11,0.14); color: #b45309; border: 1px solid rgba(245,158,11,0.35);">
                                                ⚡ Lembur
                                            </span>
                                        </template>
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5 flex items-center gap-3">
                                        <span>Rate Upah: <strong class="font-mono text-slate-700 dark:text-slate-200" style="color: var(--color-ink);" x-text="'Rp ' + (it.upah_per_pcs_snapshot || 0).toLocaleString('id-ID') + '/pcs'"></strong></span>
                                        <template x-if="it.kode_sku">
                                            <span class="font-mono text-slate-400" x-text="'SKU: ' + it.kode_sku"></span>
                                        </template>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-3.5 border-t sm:border-t-0 pt-2.5 sm:pt-0 flex-shrink-0" style="border-color: var(--color-hairline);">
                                    <!-- Output Pcs & Bal -->
                                    <div class="text-left sm:text-right flex items-center gap-1.5 font-mono">
                                        <span class="font-bold text-xs text-slate-800 dark:text-slate-100" style="color: var(--color-ink);" x-text="(it.lembur_pcs > 0 ? it.lembur_pcs : it.kuantitas_pcs).toLocaleString('id-ID') + ' pcs'"></span>
                                        <span class="text-[11px] text-slate-400 font-medium" x-show="(it.lembur_bal > 0 ? it.lembur_bal : it.kuantitas_bal) > 0" x-text="'(' + (it.lembur_bal > 0 ? it.lembur_bal : it.kuantitas_bal) + ' bal)'"></span>
                                    </div>

                                    <!-- Subtotal Upah Badge dengan padding dan kontras yang aman -->
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-success font-mono font-extrabold text-xs py-1 px-2.5 shadow-xs inline-flex items-center justify-center" 
                                              x-text="'Rp ' + (it.total_upah_didapat || 0).toLocaleString('id-ID')">
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Modal Footer (Hanya ditampilkan bila ada aksi Edit) -->
                <template x-if="detailEmp && !detailEmp.is_locked">
                    <div class="modal-footer" style="padding: 12px 20px; border-top: 1px solid var(--color-hairline); display: flex; justify-content: flex-end; align-items: center; gap: 8px; background: var(--color-canvas-soft);">
                        <button type="button" @click="closeDetailModal()" class="btn btn-secondary btn-sm text-xs font-bold px-3.5 modal-btn-cancel-desktop" style="border-radius: 8px; height: 34px;">
                            Batal
                        </button>
                        <button type="button" 
                                @click="editFromModal()" 
                                class="btn btn-primary btn-sm text-xs font-bold flex items-center justify-center gap-1.5 px-4" 
                                style="background: #881337; border-color: #700f2d; color: #ffffff; border-radius: 8px; height: 34px;">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            <span>Edit Data</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </template>

</div>

<script>
function productionApp() {
    return {
        selectedKaryawan: '<?= addslashes($selectedKaryawanId) ?>',
        karyawanSearch: '',
        karyawanDropdownOpen: false,
        karyawanHighlightedIndex: 0,
        karyawanList: (<?= json_encode(array_values($karyawanMapData), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>) || [],
        karyawanMap: (<?= json_encode($karyawanMapData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>) || {},
        isEditMode: false,
        editKaryawanName: '',
        showDetailModal: false,
        detailEmp: null,
        openDetailModal(empData) {
            this.detailEmp = empData;
            this.showDetailModal = true;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },
        closeDetailModal() {
            this.showDetailModal = false;
            this.detailEmp = null;
        },
        editFromModal() {
            const emp = this.detailEmp;
            this.closeDetailModal();
            if (emp) {
                this.startEdit(emp);
            }
        },
        get currentKaryawan() {
            return (this.selectedKaryawan && this.karyawanMap[this.selectedKaryawan]) ? this.karyawanMap[this.selectedKaryawan] : null;
        },
        get filteredKaryawan() {
            if (!this.karyawanSearch) return this.karyawanList;
            const q = this.karyawanSearch.toLowerCase().trim();
            return this.karyawanList.filter(k => 
                (k.name && k.name.toLowerCase().includes(q)) ||
                (k.posisi && k.posisi.toLowerCase().includes(q))
            );
        },
        toggleKaryawanDropdown() {
            this.karyawanDropdownOpen = !this.karyawanDropdownOpen;
            if (this.karyawanDropdownOpen) {
                this.karyawanSearch = '';
                this.rows.forEach(r => r.dropdownOpen = false);
                this.$nextTick(() => {
                    if (this.$refs.karyawanSearchInput) {
                        this.$refs.karyawanSearchInput.focus();
                    }
                    if (window.lucide) lucide.createIcons();
                });
            }
        },
        selectKaryawan(id) {
            this.selectedKaryawan = id;
            this.karyawanDropdownOpen = false;
            // Pastikan saat karyawan dipilih, baris awal selalu tepat 1 baris jika data masih kosong
            const hasData = this.rows.some(r => r.item_id || r.kuantitas_pcs || r.kuantitas_bal);
            if (!hasData && this.rows.length > 1) {
                this.rows = [
                    { item_id: '', kuantitas_pcs: '', kuantitas_bal: '', search: '', dropdownOpen: false, highlightedIndex: 0 }
                ];
            }
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
                // Pindah fokus ke item produk pertama setelah karyawan dipilih
                setTimeout(() => {
                    const firstItemBtn = document.querySelector('.prod-item-row-box button.enter-nav');
                    if (firstItemBtn) {
                        if (window.focusElement) window.focusElement(firstItemBtn);
                        else firstItemBtn.focus();
                    }
                }, 50);
            });
        },
        navigateKaryawan(dir) {
            const list = this.filteredKaryawan;
            if (list.length === 0) return;
            this.karyawanHighlightedIndex += dir;
            if (this.karyawanHighlightedIndex < 0) this.karyawanHighlightedIndex = 0;
            if (this.karyawanHighlightedIndex >= list.length) this.karyawanHighlightedIndex = list.length - 1;
            
            // Auto scroll ke elemen yang di-highlight
            this.$nextTick(() => {
                const el = this.$refs.karyawanListWrap;
                if (el) {
                    const active = el.querySelector('.searchable-option.is-highlighted');
                    if (active) active.scrollIntoView({ block: 'nearest' });
                }
            });
        },
        selectHighlightedKaryawan() {
            const list = this.filteredKaryawan;
            if (list.length > 0 && this.karyawanHighlightedIndex >= 0 && this.karyawanHighlightedIndex < list.length) {
                this.selectKaryawan(list[this.karyawanHighlightedIndex].id);
            }
        },

        itemsList: (<?= json_encode(array_values($itemBorongan), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>) || [],
        itemsMap: (<?= json_encode(array_column($itemBorongan, null, 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>) || {},
        is_lembur: false,
        rows: [
            { item_id: '', kuantitas_pcs: '', kuantitas_bal: '', search: '', dropdownOpen: false, highlightedIndex: 0 }
        ],
        searchQuery: '',
        init() {
            this.$watch('selectedKaryawan', () => {
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            });
            this.$watch('karyawanSearch', () => {
                this.karyawanHighlightedIndex = 0;
            });
            if (window.lucide) {
                this.$nextTick(() => {
                    lucide.createIcons();
                });
            }
            // Auto focus saat halaman dimuat
            setTimeout(() => {
                const btnKaryawan = document.getElementById('btnKaryawan');
                if (btnKaryawan && !this.selectedKaryawan) {
                    if (window.focusElement) window.focusElement(btnKaryawan);
                    else btnKaryawan.focus();
                } else if (this.selectedKaryawan) {
                    const firstItemBtn = document.querySelector('.prod-item-row-box button.enter-nav');
                    if (firstItemBtn) {
                        if (window.focusElement) window.focusElement(firstItemBtn);
                        else firstItemBtn.focus();
                    }
                }
            }, 100);
        },
        getFilteredItems(row) {
            if (!row.search) return this.itemsList;
            const q = row.search.toLowerCase().trim();
            return this.itemsList.filter(it => 
                (it.nama_item && it.nama_item.toLowerCase().includes(q)) ||
                (it.nama_kelompok && it.nama_kelompok.toLowerCase().includes(q)) ||
                (it.upah_per_bungkus && String(it.upah_per_bungkus).includes(q))
            );
        },
        toggleRowDropdown(row, index) {
            const willOpen = !row.dropdownOpen;
            this.rows.forEach((r, idx) => {
                r.dropdownOpen = false;
            });
            this.karyawanDropdownOpen = false;
            row.dropdownOpen = willOpen;
            if (row.dropdownOpen) {
                row.search = '';
                this.$nextTick(() => {
                    const el = document.getElementById('item-search-input-' + index);
                    if (el) el.focus();
                    if (window.lucide) lucide.createIcons();
                });
            }
        },
        selectRowItem(row, itemId) {
            row.item_id = itemId;
            row.dropdownOpen = false;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
                // Pindah fokus ke input pcs baris ini
                setTimeout(() => {
                    const rowIndex = this.rows.indexOf(row);
                    const rowBoxes = document.querySelectorAll('.prod-item-row-box');
                    if (rowBoxes && rowBoxes[rowIndex]) {
                        const pcsInput = rowBoxes[rowIndex].querySelector('input[name*="[kuantitas_pcs]"], input[name*="[lembur_pcs]"]');
                        if (pcsInput) {
                            if (window.focusElement) window.focusElement(pcsInput);
                            else {
                                pcsInput.focus();
                                if (pcsInput.select) pcsInput.select();
                            }
                        }
                    }
                }, 50);
            });
        },
        navigateItem(row, dir) {
            const list = this.getFilteredItems(row);
            if (list.length === 0) return;
            if (typeof row.highlightedIndex === 'undefined') row.highlightedIndex = 0;
            row.highlightedIndex += dir;
            if (row.highlightedIndex < 0) row.highlightedIndex = 0;
            if (row.highlightedIndex >= list.length) row.highlightedIndex = list.length - 1;
            
            // Auto scroll
            this.$nextTick(() => {
                const el = document.getElementById('item-search-input-' + this.rows.indexOf(row));
                if (el) {
                    const wrap = el.closest('.dropdown-menu-searchable').querySelector('.custom-scrollbar');
                    const active = wrap.querySelector('.searchable-option.is-highlighted');
                    if (active) active.scrollIntoView({ block: 'nearest' });
                }
            });
        },
        selectHighlightedItem(row) {
            const list = this.getFilteredItems(row);
            if (typeof row.highlightedIndex === 'undefined') row.highlightedIndex = 0;
            if (list.length > 0 && row.highlightedIndex >= 0 && row.highlightedIndex < list.length) {
                this.selectRowItem(row, list[row.highlightedIndex].id);
            }
        },
        toggleGlobalLembur() {
            this.is_lembur = !this.is_lembur;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },
        addRow() {
            this.rows.push({
                item_id: '',
                kuantitas_pcs: '',
                kuantitas_bal: '',
                search: '',
                dropdownOpen: false,
                highlightedIndex: 0
            });
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },
        removeRow(index) {
            if (this.rows.length > 1) {
                this.rows.splice(index, 1);
            } else if (this.rows.length === 1) {
                this.rows[0].item_id = '';
                this.rows[0].kuantitas_pcs = '';
                this.rows[0].kuantitas_bal = '';
                this.rows[0].search = '';
                this.rows[0].dropdownOpen = false;
                this.rows[0].highlightedIndex = 0;
            }
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },
        getRowRate(itemId) {
            return (itemId && this.itemsMap && this.itemsMap[itemId]) ? parseFloat(this.itemsMap[itemId].upah_per_bungkus || 0) : 0;
        },
        calculateRowSubtotal(row) {
            const rate = this.getRowRate(row.item_id);
            const p = parseInt(row.kuantitas_pcs) || 0;
            return p * rate;
        },
        get totalGrandPreview() {
            let total = 0;
            for (let i = 0; i < this.rows.length; i++) {
                total += this.calculateRowSubtotal(this.rows[i]);
            }
            return total;
        },
        resetForm() {
            this.isEditMode = false;
            this.editKaryawanName = '';
            this.selectedKaryawan = '';
            this.is_lembur = false;
            this.rows = [
                { item_id: '', kuantitas_pcs: '', kuantitas_bal: '', search: '', dropdownOpen: false, highlightedIndex: 0 }
            ];

            // Bersihkan parameter karyawan_id dari URL agar saat di-refresh formulir kembali bersih
            try {
                const url = new URL(window.location.href);
                if (url.searchParams.has('karyawan_id')) {
                    url.searchParams.delete('karyawan_id');
                    window.history.replaceState({}, '', url.toString());
                }
            } catch (e) {}

            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },
        startEdit(empData) {
            if (!empData) return;
            this.isEditMode = true;
            this.selectedKaryawan = empData.karyawan_id;
            this.editKaryawanName = empData.nama_karyawan;
            this.is_lembur = Boolean(empData.has_lembur);
            
            if (empData.items && empData.items.length > 0) {
                this.rows = empData.items.map(it => {
                    const isLemburRow = (parseInt(it.lembur_pcs) > 0 || parseInt(it.lembur_bal) > 0);
                    return {
                        item_id: it.item_id,
                        kuantitas_pcs: isLemburRow ? (it.lembur_pcs || 1) : (it.kuantitas_pcs || 1),
                        kuantitas_bal: isLemburRow ? (it.lembur_bal || '') : (it.kuantitas_bal || ''),
                        search: '',
                        dropdownOpen: false,
                        highlightedIndex: 0
                    };
                });
            } else {
                this.rows = [
                    { item_id: '', kuantitas_pcs: '', kuantitas_bal: '', search: '', dropdownOpen: false, highlightedIndex: 0 }
                ];
            }

            // Sync URL query param
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('karyawan_id', empData.karyawan_id);
                window.history.replaceState({}, '', url.toString());
            } catch (e) {}

            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
                const formCard = document.getElementById('formProduksiCard');
                if (formCard) {
                    formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });

            if (window.toast) {
                toast.info('Mode Edit: Memuat data produksi ' + empData.nama_karyawan);
            }
        },
        cancelEdit() {
            this.resetForm();
            if (window.toast) {
                toast.info('Mode edit dibatalkan');
            }
        },
        submitProductionForm(event) {
            if (!this.selectedKaryawan) {
                if (window.toast) toast.warning('Mohon pilih nama karyawan borongan terlebih dahulu!');
                else alert('Mohon pilih nama karyawan borongan terlebih dahulu!');
                return false;
            }

            if (!this.rows || this.rows.length === 0) {
                if (window.toast) toast.warning('Setidaknya tambahkan minimal 1 baris item produk!');
                else alert('Setidaknya tambahkan minimal 1 baris item produk!');
                return false;
            }

            for (let i = 0; i < this.rows.length; i++) {
                const r = this.rows[i];
                const rowNum = i + 1;
                if (!r.item_id) {
                    if (window.toast) toast.warning(`Baris #${rowNum}: Silakan pilih item produk terlebih dahulu!`);
                    else alert(`Baris #${rowNum}: Silakan pilih item produk terlebih dahulu!`);
                    return false;
                }
                const pcs = parseInt(r.kuantitas_pcs) || 0;
                if (pcs <= 0) {
                    if (window.toast) toast.warning(`Baris #${rowNum}: Jumlah output produksi (Pcs) tidak boleh 0 atau kosong!`);
                    else alert(`Baris #${rowNum}: Jumlah output produksi (Pcs) tidak boleh 0 atau kosong!`);
                    return false;
                }
            }

            if (window.AppAction) {
                window.AppAction.show(
                    this.isEditMode ? 'Menyimpan Perubahan' : 'Menyimpan Data Produksi',
                    this.isEditMode ? 'Memproses pembaruan data borongan...' : 'Memproses penyimpanan data borongan...'
                );
            }
            return true;
        },
        async deleteKaryawan(karyawanId, namaKaryawan, totalItems, totalUpah) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Catatan Produksi',
                message: `Apakah Anda yakin ingin menghapus seluruh catatan produksi (${totalItems} item produk) untuk karyawan "${namaKaryawan}"? Total upah: ${totalUpah}.`,
                submessage: 'Tindakan ini akan menghapus data produksi tanggal <?= htmlspecialchars($tanggal) ?> untuk karyawan ini.',
                type: 'danger',
                confirmText: 'Ya, Hapus Semua',
                cancelText: 'Batal'
            }) : confirm(`Hapus seluruh data produksi ${namaKaryawan}?`);

            if (!confirmed) return;

            if (window.AppAction) {
                window.AppAction.show('Menghapus Catatan', 'Memproses penghapusan data produksi...');
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= Router::url('/produksi/delete') ?>';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= CSRF::token() ?>';
            form.appendChild(csrfInput);

            const kIdInput = document.createElement('input');
            kIdInput.type = 'hidden';
            kIdInput.name = 'karyawan_id';
            kIdInput.value = karyawanId;
            form.appendChild(kIdInput);

            const tglInput = document.createElement('input');
            tglInput.type = 'hidden';
            tglInput.name = 'tanggal';
            tglInput.value = '<?= addslashes($tanggal) ?>';
            form.appendChild(tglInput);

            document.body.appendChild(form);
            form.submit();
        },
        productionSearchKeys: (<?= json_encode(array_values(array_map(function($emp) {
            $itemNames = array_map(fn($it) => ($it['nama_item'] ?? '') . ' ' . ($it['kode_sku'] ?? '') . ' ' . ($it['nama_kelompok'] ?? ''), $emp['items']);
            return strtolower($emp['nama_karyawan'] . ' ' . ($emp['nama_panggilan'] ?? '') . ' ' . ($emp['posisi'] ?? 'pengemasan') . ' ' . implode(' ', $itemNames));
        }, $produksiPerKaryawan)), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>) || [],
        get hasSearchResults() {
            if (!this.searchQuery || !this.searchQuery.trim()) return true;
            const q = this.searchQuery.toLowerCase().trim();
            return this.productionSearchKeys.some(k => k.includes(q));
        },
        matchSearch(key) {
            if (!this.searchQuery || !this.searchQuery.trim()) return true;
            const q = this.searchQuery.toLowerCase().trim();
            return (key || '').toLowerCase().includes(q);
        }
    };
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
<script src="<?= Router::url('/assets/js/keyboard-nav.js') ?>?v=<?= time() ?>"></script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

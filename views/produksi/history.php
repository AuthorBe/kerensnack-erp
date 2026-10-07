<?php
use App\Core\Router;
use App\Helpers\Format;

ob_start();

$totalPcs = $totalPcs ?? 0;
$totalBal = $totalBal ?? 0;
$totalLemburPcs = $totalLemburPcs ?? 0;
$totalLemburBal = $totalLemburBal ?? 0;
$totalUpah = $totalUpah ?? 0.00;
$history = $history ?? [];
$historyGrouped = $historyGrouped ?? [];
$karyawanList = $karyawanList ?? [];
$itemList = $itemList ?? [];

// Helper Avatar Initials
if (!function_exists('getInitials')) {
    function getInitials(string $name): string {
        $parts = explode(' ', trim($name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            $initials .= mb_substr($p, 0, 1);
        }
        return strtoupper($initials ?: 'KR');
    }
}

$initialRows = [];
foreach ($historyGrouped as $emp) {
    $empPayload = $emp;
    $empPayload['initials'] = getInitials($emp['nama_karyawan']);
    $empPayload['tanggal_indo'] = Format::tanggalIndo($emp['tanggal']);
    $empPayload['total_upah'] = (float)$emp['total_upah'];
    $empPayload['total_upah_formatted'] = Format::rupiah((float)$emp['total_upah']);
    if (!empty($empPayload['items'])) {
        foreach ($empPayload['items'] as &$it) {
            $it['kuantitas_pcs'] = (int)$it['kuantitas_pcs'];
            $it['kuantitas_bal'] = (int)$it['kuantitas_bal'];
            $it['lembur_pcs'] = (int)$it['lembur_pcs'];
            $it['lembur_bal'] = (int)$it['lembur_bal'];
            $it['upah_per_pcs_snapshot'] = (float)$it['upah_per_pcs_snapshot'];
            $it['total_upah_didapat'] = (float)$it['total_upah_didapat'];
            $it['upah_per_pcs_formatted'] = Format::rupiah((float)$it['upah_per_pcs_snapshot']);
            $it['total_upah_didapat_formatted'] = Format::rupiah((float)$it['total_upah_didapat']);
        }
        unset($it);
    }
    $initialRows[] = $empPayload;
}
?>

<style>
/* =========================================================================
   MODUL RIWAYAT PRODUKSI BORONGAN — CANONICAL KEREN ONE ERP DESIGN SYSTEM
   ========================================================================= */

/* Mobile & Touch Ergonomics */
* {
    -webkit-tap-highlight-color: transparent;
}
button, select {
    touch-action: manipulation;
}
input[type="text"], input[type="date"], select {
    touch-action: manipulation;
}

/* Filter Card */
.prod-filter-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: var(--rounded-xl, 16px);
    padding: 16px;
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
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease, opacity 0.2s ease;
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
.prod-section-icon.is-cyan {
    background-color: rgba(2, 132, 199, 0.1);
    color: #0284c7;
    border: 1px solid rgba(2, 132, 199, 0.25);
}
.dark .prod-section-icon.is-cyan {
    background-color: rgba(2, 132, 199, 0.16);
    color: #38bdf8;
    border-color: rgba(2, 132, 199, 0.35);
}
.prod-section-icon svg {
    width: 18px;
    height: 18px;
    display: block;
}

/* Canonical Balanced Header Counter Badges */
.badge-pill-cyan {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    font-size: 11.5px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 9999px;
    background: rgba(2, 132, 199, 0.08);
    color: #0284c7;
    border: 1px solid rgba(2, 132, 199, 0.22);
    flex-shrink: 0;
}
.dark .badge-pill-cyan {
    background: rgba(2, 132, 199, 0.16);
    color: #38bdf8;
    border-color: rgba(2, 132, 199, 0.35);
}
.badge-pill-emerald {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    font-size: 11.5px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 9999px;
    background: rgba(16, 185, 129, 0.08);
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.22);
    flex-shrink: 0;
}
.dark .badge-pill-emerald {
    background: rgba(16, 185, 129, 0.16);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.35);
}

/* Table Cell Canonical Spacing (Thead, Tbody, Tfoot) */
.data-table thead th {
    padding: 13px 18px !important;
    vertical-align: middle;
}
.data-table tbody td {
    padding: 13px 18px !important;
    vertical-align: middle;
}
.data-table tfoot td {
    padding: 13px 18px !important;
    vertical-align: middle;
    background-color: var(--color-canvas-soft);
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
.prod-empty-icon svg {
    width: 22px;
    height: 22px;
    display: block;
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

/* Localized Table Loading Glassmorphism Overlay */
.table-loading-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.72);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
    z-index: 25;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    border-radius: var(--rounded-lg, 12px);
}
.dark .table-loading-overlay {
    background: rgba(15, 23, 42, 0.75);
}
</style>

<div class="space-y-4 pb-20" x-data="historyApp()">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER (Pola Kanonikal KEREN ONE ERP)                              -->
    <!-- ========================================================================= -->
    <div class="page-header flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-rose" style="flex-shrink:0;">
                <i data-lucide="history"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#881337;"></span>
                    <span>Modul Produksi Borongan &bull; Riwayat &amp; Rekapitulasi</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl font-bold" style="color:var(--color-ink);">
                    <?= htmlspecialchars($pageTitle ?? 'Riwayat Produksi Borongan') ?>
                </h1>
                <p class="page-subtitle text-xs sm:text-sm" style="color:var(--color-ink-mute);">
                    Periode: <span class="font-bold" style="color:#881337; font-family:var(--font-mono);" x-text="periodeText"><?= Format::tanggalIndo($tglAwal) ?> s/d <?= Format::tanggalIndo($tglAkhir) ?></span>
                </p>
            </div>
        </div>
        <div class="page-header-actions flex items-center gap-2 w-full sm:w-auto justify-end">
            <a href="<?= Router::url('/produksi') ?>" class="btn btn-secondary text-xs sm:text-sm flex items-center gap-1.5 w-full sm:w-auto justify-center" style="font-weight:700; height:38px; border-radius:10px;">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Input Produksi</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. FILTER CARD (Real-time Instant Searchable Filter)                       -->
    <!-- ========================================================================= -->
    <div class="prod-filter-card">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding-bottom:12px;margin-bottom:14px;border-bottom:1px solid var(--color-hairline);">
            <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                <div class="prod-section-icon is-maroon" style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="filter" style="width:18px;height:18px;"></i>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:14px;font-weight:800;color:var(--color-ink);line-height:1.2;margin:0;">Filter Data Riwayat Produksi</div>
                    <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.3;">Pencarian otomatis langsung diterapkan secara real-time</div>
                </div>
            </div>
            <div>
                <template x-if="!isLoading">
                    <span class="badge badge-success text-[10px] py-0.5 px-2 font-bold inline-flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Auto-Filter Aktif</span>
                    </span>
                </template>
                <template x-if="isLoading">
                    <span class="badge text-[10px] py-0.5 px-2 font-bold inline-flex items-center gap-1.5" style="background:rgba(245,158,11,0.12); color:#b45309; border:1px solid rgba(245,158,11,0.3);">
                        <svg class="animate-spin w-3 h-3 text-amber-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span>Memfilter...</span>
                    </span>
                </template>
            </div>
        </div>

        <form id="filterForm" @submit.prevent="applyFilter()" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Tanggal Awal -->
                <div>
                    <label class="form-label text-xs font-semibold mb-1 block" style="color:var(--color-ink-secondary);">Tanggal Awal</label>
                    <input type="date" 
                           name="tanggal_awal" 
                           id="inputTglAwal" 
                           x-model="tglAwal"
                           @change="applyFilter()"
                           class="form-input form-input-sm text-xs rounded-lg w-full" 
                           style="height:38px; background-color:var(--color-canvas); color:var(--color-ink); border-color:var(--color-hairline-strong); font-family:var(--font-mono);">
                </div>

                <!-- Tanggal Akhir -->
                <div>
                    <label class="form-label text-xs font-semibold mb-1 block" style="color:var(--color-ink-secondary);">Tanggal Akhir</label>
                    <input type="date" 
                           name="tanggal_akhir" 
                           id="inputTglAkhir" 
                           x-model="tglAkhir"
                           @change="applyFilter()"
                           class="form-input form-input-sm text-xs rounded-lg w-full" 
                           style="height:38px; background-color:var(--color-canvas); color:var(--color-ink); border-color:var(--color-hairline-strong); font-family:var(--font-mono);">
                </div>

                <!-- Karyawan Borongan Searchable Select -->
                <div class="relative" @click.outside="karyawanDropdownOpen = false">
                    <label class="form-label text-xs font-semibold mb-1 block" style="color:var(--color-ink-secondary);">Karyawan Borongan</label>
                    
                    <button type="button"
                            @click="toggleKaryawanDropdown()"
                            class="form-input flex items-center justify-between w-full text-left font-medium transition"
                            style="height:38px; border-radius:8px; background-color:var(--color-canvas); color:var(--color-ink); border:1px solid var(--color-hairline-strong); cursor:pointer; padding:0 10px;">
                        <span class="truncate text-xs font-semibold" 
                              :style="!selectedKaryawan ? 'color:var(--color-ink-mute);' : 'color:var(--color-ink);'"
                              x-text="currentKaryawanLabel">
                        </span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 transition-transform duration-200" :style="karyawanDropdownOpen ? 'transform:rotate(180deg)' : ''"></i>
                    </button>

                    <input type="hidden" name="karyawan_id" :value="selectedKaryawan">

                    <!-- Floating Searchable Menu -->
                    <div x-show="karyawanDropdownOpen" x-cloak
                         class="dropdown-menu-searchable"
                         style="position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:1050; border-radius:12px; overflow:hidden; background:var(--color-canvas); border:1px solid var(--color-hairline); box-shadow:0 14px 34px -4px rgba(0,0,0,0.18);">
                        
                        <!-- Search Box inside Dropdown -->
                        <div style="padding:6px 8px; border-bottom:1px solid var(--color-hairline); background:var(--color-canvas-soft);">
                            <div style="position:relative; display:flex; align-items:center;">
                                <i data-lucide="search" style="position:absolute; left:8px; width:13px; height:13px; color:var(--color-ink-mute); pointer-events:none;"></i>
                                <input type="text" 
                                       x-ref="karyawanSearchInput"
                                       x-model="karyawanSearch"
                                       @input="karyawanHighlightedIndex = 0"
                                       @keydown.escape.prevent="karyawanDropdownOpen = false"
                                       @keydown.down.prevent="navigateKaryawan(1)"
                                       @keydown.up.prevent="navigateKaryawan(-1)"
                                       @keydown.enter.prevent="selectHighlightedKaryawan()"
                                       placeholder="Cari karyawan..."
                                       class="form-input sd-search"
                                       style="height:32px; padding-left:28px; font-size:11.5px; border-radius:6px; width:100%; background:var(--color-canvas);">
                            </div>
                        </div>

                        <!-- Option List -->
                        <div style="max-height:220px; overflow-y:auto;" class="custom-scrollbar" x-ref="karyawanListWrap">
                            <!-- Option: Semua Karyawan -->
                            <div @click="selectKaryawan('')"
                                 class="searchable-option"
                                 :class="[(!selectedKaryawan ? 'is-selected font-bold' : ''), (karyawanHighlightedIndex === 0 && !karyawanSearch ? 'is-highlighted' : '')]"
                                 style="padding:8px 10px; font-size:12px; cursor:pointer; display:flex; align-items:center; justify-content:space-between; gap:8px; border-bottom:1px solid var(--color-hairline-soft);">
                                <span style="color:var(--color-ink);">-- Semua Karyawan --</span>
                                <template x-if="!selectedKaryawan">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-rose-700 dark:text-rose-400"></i>
                                </template>
                            </div>

                            <template x-for="(k, idx) in filteredKaryawan" :key="k.id">
                                <div @click="selectKaryawan(k.id)"
                                     class="searchable-option"
                                     :class="[(String(k.id) === String(selectedKaryawan) ? 'is-selected font-bold' : ''), (karyawanHighlightedIndex === (karyawanSearch ? idx : idx + 1) ? 'is-highlighted' : '')]"
                                     style="padding:7px 10px; font-size:12px; cursor:pointer; display:flex; align-items:center; justify-content:space-between; gap:8px; border-bottom:1px solid var(--color-hairline-soft);">
                                    <div style="min-width:0; flex:1;">
                                        <div style="font-weight:700; color:var(--color-ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" x-text="k.nama_karyawan"></div>
                                        <div style="font-size:11px; color:var(--color-ink-mute);" x-text="k.posisi || 'Pengemasan'"></div>
                                    </div>
                                    <template x-if="String(k.id) === String(selectedKaryawan)">
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-rose-700 dark:text-rose-400 flex-shrink-0"></i>
                                    </template>
                                </div>
                            </template>
                            <div x-show="filteredKaryawan.length === 0" style="padding:12px; text-align:center; font-size:11.5px; color:var(--color-ink-mute);">
                                Tidak ada karyawan yang cocok
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Item Produk Searchable Select -->
                <div class="relative" @click.outside="itemDropdownOpen = false">
                    <label class="form-label text-xs font-semibold mb-1 block" style="color:var(--color-ink-secondary);">Item Produk</label>
                    
                    <button type="button"
                            @click="toggleItemDropdown()"
                            class="form-input flex items-center justify-between w-full text-left font-medium transition"
                            style="height:38px; border-radius:8px; background-color:var(--color-canvas); color:var(--color-ink); border:1px solid var(--color-hairline-strong); cursor:pointer; padding:0 10px;">
                        <span class="truncate text-xs font-semibold" 
                              :style="!selectedItem ? 'color:var(--color-ink-mute);' : 'color:var(--color-ink);'"
                              x-text="currentItemLabel">
                        </span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 transition-transform duration-200" :style="itemDropdownOpen ? 'transform:rotate(180deg)' : ''"></i>
                    </button>

                    <input type="hidden" name="item_id" :value="selectedItem">

                    <!-- Floating Searchable Menu -->
                    <div x-show="itemDropdownOpen" x-cloak
                         class="dropdown-menu-searchable"
                         style="position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:1050; border-radius:12px; overflow:hidden; background:var(--color-canvas); border:1px solid var(--color-hairline); box-shadow:0 14px 34px -4px rgba(0,0,0,0.18);">
                        
                        <!-- Search Box inside Dropdown -->
                        <div style="padding:6px 8px; border-bottom:1px solid var(--color-hairline); background:var(--color-canvas-soft);">
                            <div style="position:relative; display:flex; align-items:center;">
                                <i data-lucide="search" style="position:absolute; left:8px; width:13px; height:13px; color:var(--color-ink-mute); pointer-events:none;"></i>
                                <input type="text" 
                                       x-ref="itemSearchInput"
                                       x-model="itemSearch"
                                       @input="itemHighlightedIndex = 0"
                                       @keydown.escape.prevent="itemDropdownOpen = false"
                                       @keydown.down.prevent="navigateItem(1)"
                                       @keydown.up.prevent="navigateItem(-1)"
                                       @keydown.enter.prevent="selectHighlightedItem()"
                                       placeholder="Cari item / kelompok..."
                                       class="form-input sd-search"
                                       style="height:32px; padding-left:28px; font-size:11.5px; border-radius:6px; width:100%; background:var(--color-canvas);">
                            </div>
                        </div>

                        <!-- Option List -->
                        <div style="max-height:220px; overflow-y:auto;" class="custom-scrollbar" x-ref="itemListWrap">
                            <!-- Option: Semua Produk -->
                            <div @click="selectItem('')"
                                 class="searchable-option"
                                 :class="[(!selectedItem ? 'is-selected font-bold' : ''), (itemHighlightedIndex === 0 && !itemSearch ? 'is-highlighted' : '')]"
                                 style="padding:8px 10px; font-size:12px; cursor:pointer; display:flex; align-items:center; justify-content:space-between; gap:8px; border-bottom:1px solid var(--color-hairline-soft);">
                                <span style="color:var(--color-ink);">-- Semua Produk --</span>
                                <template x-if="!selectedItem">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-rose-700 dark:text-rose-400"></i>
                                </template>
                            </div>

                            <template x-for="(item, idx) in filteredItems" :key="item.id">
                                <div @click="selectItem(item.id)"
                                     class="searchable-option"
                                     :class="[(String(item.id) === String(selectedItem) ? 'is-selected font-bold' : ''), (itemHighlightedIndex === (itemSearch ? idx : idx + 1) ? 'is-highlighted' : '')]"
                                     style="padding:7px 10px; font-size:12px; cursor:pointer; display:flex; align-items:center; justify-content:space-between; gap:8px; border-bottom:1px solid var(--color-hairline-soft);">
                                    <div style="min-width:0; flex:1;">
                                        <div style="font-weight:700; color:var(--color-ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" x-text="item.nama_item"></div>
                                        <div style="font-size:11px; color:var(--color-ink-mute); display:flex; align-items:center; gap:6px; margin-top:2px;">
                                            <template x-if="item.nama_kelompok">
                                                <span class="badge badge-mono text-[9.5px] py-0 px-1 font-bold" style="background:rgba(136,19,55,0.08); color:#881337;" x-text="item.nama_kelompok"></span>
                                            </template>
                                            <template x-if="item.kode_sku">
                                                <span class="font-mono text-slate-400 text-[10.5px]" x-text="item.kode_sku"></span>
                                            </template>
                                        </div>
                                    </div>
                                    <template x-if="String(item.id) === String(selectedItem)">
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-rose-700 dark:text-rose-400 flex-shrink-0"></i>
                                    </template>
                                </div>
                            </template>
                            <div x-show="filteredItems.length === 0" style="padding:12px; text-align:center; font-size:11.5px; color:var(--color-ink-mute);">
                                Tidak ada produk yang cocok
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-3.5 mt-2 border-t flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="border-color:var(--color-hairline);">
                <!-- Shortcut Periode Buttons -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-[11.5px] font-bold mr-1 flex items-center gap-1" style="color:var(--color-ink-mute);">
                        <i data-lucide="zap" style="width:13px;height:13px;" class="text-amber-500 flex-shrink-0"></i>
                        <span>Shortcut:</span>
                    </span>
                    <button type="button" 
                            @click="setShortcutPeriode('<?= date('Y-m-01') ?>', '<?= date('Y-m-d') ?>')" 
                            class="btn btn-ghost btn-sm text-[11px] font-semibold py-1.5 px-3 rounded-lg border transition hover:border-rose-400 hover:bg-rose-50/50 dark:hover:bg-rose-950/30" 
                            style="background:var(--color-canvas-soft); color:var(--color-ink); border-color:var(--color-hairline);">
                        Bulan Ini
                    </button>
                    <button type="button" 
                            @click="setShortcutPeriode('<?= date('Y-m-d', strtotime('-7 days')) ?>', '<?= date('Y-m-d') ?>')" 
                            class="btn btn-ghost btn-sm text-[11px] font-semibold py-1.5 px-3 rounded-lg border transition hover:border-rose-400 hover:bg-rose-50/50 dark:hover:bg-rose-950/30" 
                            style="background:var(--color-canvas-soft); color:var(--color-ink); border-color:var(--color-hairline);">
                        7 Hari Terakhir
                    </button>
                    <button type="button" 
                            @click="setShortcutPeriode('<?= date('Y-m-01', strtotime('first day of last month')) ?>', '<?= date('Y-m-t', strtotime('last month')) ?>')" 
                            class="btn btn-ghost btn-sm text-[11px] font-semibold py-1.5 px-3 rounded-lg border transition hover:border-rose-400 hover:bg-rose-50/50 dark:hover:bg-rose-950/30" 
                            style="background:var(--color-canvas-soft); color:var(--color-ink); border-color:var(--color-hairline);">
                        Bulan Lalu
                    </button>
                    <button type="button" 
                            @click="setShortcutPeriode('<?= date('Y-m-d', strtotime('-30 days')) ?>', '<?= date('Y-m-d') ?>')" 
                            class="btn btn-ghost btn-sm text-[11px] font-semibold py-1.5 px-3 rounded-lg border transition hover:border-rose-400 hover:bg-rose-50/50 dark:hover:bg-rose-950/30" 
                            style="background:var(--color-canvas-soft); color:var(--color-ink); border-color:var(--color-hairline);">
                        30 Hari Terakhir
                    </button>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <button type="button" @click="resetFilter()" class="btn btn-secondary btn-sm text-xs font-bold px-3.5" style="border-radius:8px; height:34px;">
                        Reset Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. KPI METRIC CARDS (Standardized ERP Stat Strip)                          -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Card 1: Total Pcs -->
        <div class="prod-stat-card" :class="isLoading ? 'opacity-50 pointer-events-none' : 'opacity-100'">
            <div class="prod-stat-icon" style="background:rgba(136, 19, 55, 0.1); color:#881337; border:1px solid rgba(136, 19, 55, 0.2);">
                <i data-lucide="package"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--color-ink-mute);">Total Output (Pcs)</div>
                <div class="text-base sm:text-lg font-bold" style="color:var(--color-ink); font-family:var(--font-mono);">
                    <span x-text="(totalPcs || 0).toLocaleString('id-ID')"><?= number_format($totalPcs, 0, ',', '.') ?></span>
                    <span class="text-xs font-normal" style="color:var(--color-ink-mute);">pcs</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Total Bal -->
        <div class="prod-stat-card" :class="isLoading ? 'opacity-50 pointer-events-none' : 'opacity-100'">
            <div class="prod-stat-icon" style="background:rgba(2, 132, 199, 0.1); color:#0284c7; border:1px solid rgba(2, 132, 199, 0.2);">
                <i data-lucide="layers"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--color-ink-mute);">Total Kemasan Bal</div>
                <div class="text-base sm:text-lg font-bold" style="color:#0284c7; font-family:var(--font-mono);">
                    <span x-text="(totalBal || 0).toLocaleString('id-ID')"><?= number_format($totalBal, 0, ',', '.') ?></span>
                    <span class="text-xs font-normal" style="color:var(--color-ink-mute);">bal</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Lembur -->
        <div class="prod-stat-card" :class="isLoading ? 'opacity-50 pointer-events-none' : 'opacity-100'">
            <div class="prod-stat-icon" style="background:rgba(245, 158, 11, 0.1); color:#f59e0b; border:1px solid rgba(245, 158, 11, 0.2);">
                <i data-lucide="flame"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--color-ink-mute);">Total Output Lembur</div>
                <div class="text-base sm:text-lg font-bold" style="color:var(--color-warning); font-family:var(--font-mono);">
                    <span x-text="(totalLemburPcs || 0).toLocaleString('id-ID')"><?= number_format($totalLemburPcs, 0, ',', '.') ?></span>
                    <span class="text-xs font-normal" style="color:var(--color-ink-mute);">unit</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Total Upah -->
        <div class="prod-stat-card" :class="isLoading ? 'opacity-50 pointer-events-none' : 'opacity-100'">
            <div class="prod-stat-icon" style="background:rgba(16, 185, 129, 0.1); color:#10b981; border:1px solid rgba(16, 185, 129, 0.2);">
                <i data-lucide="wallet"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--color-ink-mute);">Total Akumulasi Upah</div>
                <div class="text-base sm:text-lg font-bold" style="color:var(--color-success); font-family:var(--font-mono);">
                    <span x-text="totalUpahFormatted"><?= Format::rupiah($totalUpah) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3B. REKAPITULASI PEMAKAIAN BAHAN CURAH (BAL)                              -->
    <!-- ========================================================================= -->
    <div class="table-wrapper" style="position: relative; min-height: 120px;" x-show="rekapBalBahan && rekapBalBahan.length > 0" x-cloak>
        <!-- Header -->
        <div style="padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:1px solid var(--color-hairline);background:var(--color-canvas);" class="flex-wrap sm:flex-nowrap">
            <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                <div class="prod-section-icon is-cyan" style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="boxes" style="width:18px;height:18px;"></i>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:14px;font-weight:800;color:var(--color-ink);line-height:1.2;margin:0;">
                        Rekap Pemakaian Bahan Baku Curah (Bal) &bull; <span style="color:#0284c7; font-family:var(--font-mono); font-weight:700;" x-text="periodeText"></span>
                    </div>
                    <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.3;">Total bahan mentah yang terpotong dari gudang berdasarkan catatan input bal karyawan</div>
                </div>
            </div>
            <div class="badge-pill-cyan self-start sm:self-auto" style="display:inline-flex; align-items:center; gap:6px;">
                <span class="font-mono font-bold" x-text="rekapBalBahan.length"></span>
                <span>Jenis Bahan Curah</span>
            </div>
        </div>

        <!-- Table Responsive -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table w-full text-left border-collapse text-xs" style="min-width: 860px;">
                <thead style="background:var(--color-canvas-soft); border-bottom:1px solid var(--color-hairline-strong);">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center" style="vertical-align: middle;">No</th>
                        <th class="py-3 px-4 min-w-[220px]" style="vertical-align: middle;">Bahan Baku Curah</th>
                        <th class="py-3 px-4 min-w-[170px]" style="vertical-align: middle;">Karyawan Terlibat</th>
                        <th class="py-3 px-4 w-36" style="text-align: right; vertical-align: middle;">Total Terpakai</th>
                        <th class="py-3 px-4 w-36" style="text-align: right; vertical-align: middle;">Output Dihasilkan</th>
                        <th class="py-3 px-4 w-36" style="text-align: right; vertical-align: middle;">Rata-rata Yield</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(rkb, idx) in rekapBalBahan" :key="rkb.item_bahan_id || idx">
                        <tr class="hover:bg-black/[0.02] dark:hover:bg-white/[0.02] transition-colors">
                            <!-- No -->
                            <td class="py-2.5 px-4 text-center font-mono text-xs" style="color:var(--color-ink-mute); vertical-align: middle;" x-text="idx + 1"></td>

                            <!-- Nama Bahan -->
                            <td class="py-2.5 px-4" style="vertical-align: middle;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span class="shrink-0 font-bold" 
                                          style="display:inline-flex; align-items:center; justify-content:center; line-height:1; font-size:10px; padding:3.5px 8px; border-radius:9999px; background:rgba(2, 132, 199, 0.08); color:#0284c7; border:1px solid rgba(2, 132, 199, 0.22);">
                                        Curah
                                    </span>
                                    <span class="font-bold text-xs" style="color:var(--color-ink);" x-text="rkb.nama_bahan"></span>
                                </div>
                            </td>

                            <!-- Karyawan -->
                            <td class="py-2.5 px-4" style="vertical-align: middle;">
                                <div class="inline-flex items-center gap-1.5 text-xs" style="color:var(--color-ink-mute);">
                                    <i data-lucide="users" style="width:13px; height:13px;" class="text-slate-400 shrink-0"></i>
                                    <span>Dikerjakan oleh <strong class="font-bold" style="color:var(--color-ink);" x-text="rkb.jumlah_karyawan"></strong> karyawan</span>
                                </div>
                            </td>

                            <!-- Total Terpakai (Bal) -->
                            <td class="py-2.5 px-4" style="text-align: right; vertical-align: middle;">
                                <span class="badge-pill-cyan font-mono" style="padding:4px 10px; font-size:11.5px;">
                                    <span x-text="rkb.total_bal_formatted"></span>&nbsp;<span x-text="rkb.satuan_dasar"></span>
                                </span>
                            </td>

                            <!-- Output Dihasilkan (Pcs) -->
                            <td class="py-2.5 px-4 font-mono font-bold text-xs" style="text-align: right; color:var(--color-ink); vertical-align: middle;">
                                <span x-text="rkb.total_pcs_formatted + ' pcs'"></span>
                            </td>

                            <!-- Yield Real -->
                            <td class="py-2.5 px-4 font-mono font-extrabold text-xs" style="text-align: right; color:#059669; vertical-align: middle;">
                                <span x-text="'~' + rkb.yield_real + ' pcs/bal'"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot x-show="rekapBalBahan.length > 0">
                    <tr class="font-bold border-t-2" style="background:var(--color-canvas-soft); border-color:var(--color-hairline-strong); color:var(--color-ink);">
                        <td style="vertical-align: middle;"></td>
                        <td colspan="2" style="vertical-align: middle;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <span class="text-xs font-black uppercase tracking-wider" style="color:var(--color-ink);">Total Pemakaian Bahan Curah</span>
                                <span class="badge-pill-cyan font-mono" style="font-size:11px; padding:3px 10px;" x-text="rekapBalBahan.length + ' Bahan'"></span>
                            </div>
                        </td>
                        <td style="text-align: right; vertical-align: middle;">
                            <span class="badge-pill-cyan font-mono font-bold text-xs" 
                                  style="padding:4px 10px; font-size:11.5px; font-weight:800;"
                                  x-text="(rekapBalBahan.reduce((sum, r) => sum + Number(r.total_bal_terpakai || 0), 0)).toLocaleString('id-ID') + ' bal'">
                            </span>
                        </td>
                        <td class="font-mono font-bold text-xs" style="text-align: right; vertical-align: middle; color:var(--color-ink);">
                            <span x-text="(rekapBalBahan.reduce((sum, r) => sum + Number(r.total_pcs_dihasilkan || 0), 0)).toLocaleString('id-ID') + ' pcs'"></span>
                        </td>
                        <td class="font-mono font-extrabold text-xs" style="text-align: right; vertical-align: middle; color:#059669;">
                            <span x-text="'~' + ((rekapBalBahan.reduce((sum, r) => sum + Number(r.total_bal_terpakai || 0), 0) > 0) ? Math.round(rekapBalBahan.reduce((sum, r) => sum + Number(r.total_pcs_dihasilkan || 0), 0) / rekapBalBahan.reduce((sum, r) => sum + Number(r.total_bal_terpakai || 0), 0)) : 0) + ' pcs/bal'"></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. DATA TABLE / MOBILE CARDS                                              -->
    <!-- ========================================================================= -->
    <div class="table-wrapper" style="position: relative; min-height: 200px;">
        <!-- Localized Glassmorphism Loading Overlay for Table -->
        <div x-show="isLoading" 
             x-cloak 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="table-loading-overlay">
            <div class="inline-flex items-center justify-center shadow-md" style="width: 44px; height: 44px; border-radius: 12px; background: rgba(136, 19, 55, 0.12); color: #881337; border: 1.5px solid rgba(136, 19, 55, 0.25);">
                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </div>
            <span style="font-size: 12px; font-weight: 700; color: var(--color-ink);" class="tracking-wide">Memuat Data Riwayat...</span>
        </div>

        <div style="padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:1px solid var(--color-hairline);background:var(--color-canvas);" class="flex-wrap sm:flex-nowrap">
            <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                <div class="prod-section-icon is-emerald" style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="file-spreadsheet" style="width:18px;height:18px;"></i>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:14px;font-weight:800;color:var(--color-ink);line-height:1.2;margin:0;">Daftar Riwayat Output Produksi</div>
                    <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.3;">Hasil rekapitulasi data produksi borongan sesuai parameter filter</div>
                </div>
            </div>
            <div class="badge-pill-emerald self-start sm:self-auto" style="display:inline-flex; align-items:center; gap:6px;">
                <span class="font-mono font-bold" x-text="historyGrouped.length"></span>
                <span>Rekap Karyawan</span>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table w-full text-left border-collapse text-xs" style="min-width: 860px;">
                <thead style="background:var(--color-canvas-soft); border-bottom:1px solid var(--color-hairline-strong);">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center" style="vertical-align: middle;">No</th>
                        <th class="py-3 px-4 w-32" style="vertical-align: middle;">Tanggal</th>
                        <th class="py-3 px-4 min-w-[170px]" style="vertical-align: middle;">Karyawan</th>
                        <th class="py-3 px-4 min-w-[170px]" style="vertical-align: middle;">Rincian Item Produk</th>
                        <th class="py-3 px-4 w-28" style="text-align: right; vertical-align: middle;">Total Reguler</th>
                        <th class="py-3 px-4 w-24" style="text-align: right; vertical-align: middle;">Lembur</th>
                        <th class="py-3 px-4 min-w-[130px]" style="text-align: right; vertical-align: middle;">Total Upah</th>
                        <th class="py-3 px-4 text-center w-28" style="vertical-align: middle;">Status Payroll</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(emp, idx) in historyGrouped" :key="emp.tanggal + '_' + emp.karyawan_id">
                        <tr class="hover:bg-black/[0.02] dark:hover:bg-white/[0.02] transition-colors">
                            <!-- No -->
                            <td class="py-2.5 px-4 text-center font-mono text-xs" style="color:var(--color-ink-mute); vertical-align: middle;" x-text="idx + 1"></td>
                            
                            <!-- Tanggal -->
                            <td class="py-2.5 px-4 font-mono font-semibold" style="color:var(--color-ink); vertical-align: middle;" x-text="emp.tanggal_indo"></td>
                            
                            <!-- Karyawan -->
                            <td class="py-2.5 px-4" style="vertical-align: middle;">
                                <div class="flex items-center gap-2.5">
                                    <div class="prod-table-avatar" x-text="emp.initials"></div>
                                    <div class="min-w-0 flex-1">
                                        <span class="truncate block text-xs font-bold" style="color:var(--color-ink);" x-text="emp.nama_karyawan"></span>
                                        <div style="display:flex; align-items:center; gap:8px; margin-top:3px;">
                                            <span class="text-[10.5px] font-normal" style="color:var(--color-ink-mute);" x-text="emp.posisi || 'Pengemasan'"></span>
                                            <span class="badge text-[9.5px] font-bold" style="display:inline-flex; align-items:center; justify-content:center; line-height:1; padding:2.5px 8px; border-radius:9999px; background:rgba(136,19,55,0.08); color:#881337; border:1px solid rgba(136,19,55,0.22);" x-text="(emp.items ? emp.items.length : 0) + ' Produk'"></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Rincian Item Produk (Tombol Detail Modal Ringkas & Rapi) -->
                            <td class="py-2.5 px-4" style="vertical-align: middle;">
                                <button type="button" 
                                        @click="openDetailModal(emp)"
                                        class="btn btn-secondary btn-sm inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold rounded-lg border transition hover:border-rose-400 hover:bg-rose-50/60 dark:hover:bg-rose-950/40"
                                        style="height: 32px; background: var(--color-canvas-soft); border-color: var(--color-hairline); color: var(--color-ink);"
                                        title="Lihat rincian lengkap item produk">
                                    <i data-lucide="package" class="w-3.5 h-3.5 text-rose-700 dark:text-rose-400 flex-shrink-0"></i>
                                    <span x-text="(emp.items ? emp.items.length : 0) + ' Item Produk'"></span>
                                    <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 ml-0.5"></i>
                                </button>
                            </td>
                            
                            <!-- Total Reguler -->
                            <td class="py-2.5 px-4 font-semibold font-mono" style="text-align: right; color:var(--color-ink); vertical-align: middle;">
                                <span x-text="(emp.total_pcs || 0).toLocaleString('id-ID') + ' pcs'"></span>
                                <template x-if="emp.total_bal > 0">
                                    <span class="text-[10.5px] font-normal block text-slate-400" x-text="emp.total_bal + ' bal'"></span>
                                </template>
                            </td>
                            
                            <!-- Total Lembur -->
                            <td class="py-2.5 px-4 font-semibold font-mono" style="text-align: right; vertical-align: middle;">
                                <template x-if="emp.total_lembur_pcs > 0 || emp.total_lembur_bal > 0">
                                    <div>
                                        <span style="color:#881337;" x-text="formatQty(emp.total_lembur_pcs) + ' pcs'"></span>
                                        <template x-if="emp.total_lembur_bal > 0">
                                            <span class="text-[10px] font-normal block text-rose-400" x-text="formatQty(emp.total_lembur_bal) + ' bal'"></span>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!(emp.total_lembur_pcs > 0 || emp.total_lembur_bal > 0)">
                                    <span style="color:var(--color-ink-mute); font-weight:400;">-</span>
                                </template>
                            </td>
                            
                            <!-- Total Upah -->
                            <td class="py-2.5 px-4 font-bold font-mono text-xs" style="text-align: right; color:var(--color-success); vertical-align: middle;" x-text="emp.total_upah_formatted || formatRupiah(emp.total_upah)"></td>
                            
                            <!-- Status Payroll -->
                            <td class="py-2.5 px-4 text-center" style="vertical-align: middle;">
                                <template x-if="emp.is_locked">
                                    <span class="badge badge-neutral text-[9px] py-0.5 px-2 font-mono">
                                        🔒 <span x-text="emp.nomor_payroll || 'Payroll'"></span>
                                    </span>
                                </template>
                                <template x-if="!emp.is_locked">
                                    <span class="badge badge-warning text-[9.5px] py-0.5 px-2 font-semibold">⏳ Belum Digaji</span>
                                </template>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State Row -->
                    <tr x-show="!isLoading && historyGrouped.length === 0" x-cloak>
                        <td colspan="8" class="py-12 px-4 text-center">
                            <div class="prod-empty-icon">
                                <i data-lucide="inbox"></i>
                            </div>
                            <div class="font-bold text-sm" style="color:var(--color-ink);">Tidak ada riwayat produksi</div>
                            <div class="text-xs mt-1 max-w-sm mx-auto" style="color:var(--color-ink-mute);">Coba sesuaikan rentang tanggal atau filter pencarian di atas.</div>
                        </td>
                    </tr>
                </tbody>
                <tfoot x-show="historyGrouped.length > 0">
                    <tr class="font-bold border-t-2" style="background:var(--color-canvas-soft); border-color:var(--color-hairline-strong); color:var(--color-ink);">
                        <td style="vertical-align: middle;"></td>
                        <td colspan="3" style="vertical-align: middle;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <span class="text-xs font-black uppercase tracking-wider" style="color:var(--color-ink);">Total Rekapitulasi Output</span>
                                <span class="badge-pill-emerald font-mono" style="font-size:11px; padding:3px 10px;" x-text="historyGrouped.length + ' Karyawan'"></span>
                            </div>
                        </td>
                        <td class="font-mono font-bold text-xs" style="text-align: right; color:var(--color-ink); vertical-align: middle;">
                            <span x-text="(totalPcs || 0).toLocaleString('id-ID') + ' pcs'"></span>
                            <template x-if="totalBal > 0">
                                <span class="text-[10.5px] font-normal block text-slate-400" style="margin-top:2px;" x-text="(totalBal || 0).toLocaleString('id-ID') + ' bal'"></span>
                            </template>
                        </td>
                        <td class="font-mono font-bold text-xs" style="text-align: right; vertical-align: middle;">
                            <template x-if="totalLemburPcs > 0 || totalLemburBal > 0">
                                <div>
                                    <span style="color:#881337;" x-text="(totalLemburPcs || 0).toLocaleString('id-ID') + ' pcs'"></span>
                                    <template x-if="totalLemburBal > 0">
                                        <span class="text-[10px] font-normal block text-rose-400" style="margin-top:2px;" x-text="(totalLemburBal || 0).toLocaleString('id-ID') + ' bal'"></span>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!(totalLemburPcs > 0 || totalLemburBal > 0)">
                                <span style="color:var(--color-ink-mute); font-weight:400;">-</span>
                            </template>
                        </td>
                        <td class="font-mono font-bold text-xs sm:text-sm" style="text-align: right; color:var(--color-success); vertical-align: middle;" x-text="totalUpahFormatted"></td>
                        <td style="vertical-align: middle;"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. MODAL POPUP DETAIL RIWAYAT PRODUKSI                                    -->
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
                                    Rincian Output Produksi
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
                            <div class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5" x-text="detailEmp ? (detailEmp.nama_karyawan + ' (' + (detailEmp.posisi || 'Pengemasan') + ') • ' + (detailEmp.tanggal_indo || '')) : ''"></div>
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
                            <div class="text-sm font-black text-slate-800 dark:text-slate-100 font-mono mt-0.5" x-text="detailEmp ? (formatQty(detailEmp.total_pcs) + ' pcs') : '0 pcs'"></div>
                            <div class="text-[10px] text-slate-400" x-show="detailEmp && detailEmp.total_bal > 0" x-text="detailEmp ? (formatQty(detailEmp.total_bal) + ' bal') : ''"></div>
                        </div>
                        <div class="text-center border-x" style="border-color: var(--color-hairline);">
                            <div class="text-[10.5px] uppercase font-bold text-slate-400">Total Lembur</div>
                            <div class="text-sm font-black font-mono mt-0.5" :style="detailEmp && (detailEmp.total_lembur_pcs > 0 || detailEmp.total_lembur_bal > 0) ? 'color:#881337;' : 'color:var(--color-ink-mute);'" x-text="detailEmp && detailEmp.total_lembur_pcs > 0 ? (formatQty(detailEmp.total_lembur_pcs) + ' pcs') : '-'"></div>
                            <div class="text-[10px] text-rose-400" x-show="detailEmp && detailEmp.total_lembur_bal > 0" x-text="detailEmp ? (formatQty(detailEmp.total_lembur_bal) + ' bal') : ''"></div>
                        </div>
                        <div class="text-center">
                            <div class="text-[10.5px] uppercase font-bold text-slate-400">Total Upah</div>
                            <div class="text-sm font-black font-mono mt-0.5 text-emerald-600 dark:text-emerald-400" x-text="detailEmp ? (detailEmp.total_upah_formatted || formatRupiah(detailEmp.total_upah)) : 'Rp 0'"></div>
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
                                        <span>Rate Upah: <strong class="font-mono text-slate-700 dark:text-slate-200" style="color: var(--color-ink);" x-text="(it.upah_per_pcs_formatted || formatRupiah(it.upah_per_pcs_snapshot)) + '/pcs'"></strong></span>
                                        <template x-if="it.kode_sku">
                                            <span class="font-mono text-slate-400" x-text="'SKU: ' + it.kode_sku"></span>
                                        </template>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-3.5 border-t sm:border-t-0 pt-2.5 sm:pt-0 flex-shrink-0" style="border-color: var(--color-hairline);">
                                    <!-- Output Pcs & Bal -->
                                    <div class="text-left sm:text-right flex items-center gap-1.5 font-mono">
                                        <span class="font-bold text-xs text-slate-800 dark:text-slate-100" style="color: var(--color-ink);" x-text="formatQty(it.lembur_pcs > 0 ? it.lembur_pcs : it.kuantitas_pcs) + ' pcs'"></span>
                                        <span class="text-[11px] text-slate-400 font-medium" x-show="(it.lembur_bal > 0 ? it.lembur_bal : it.kuantitas_bal) > 0" x-text="'(' + formatQty(it.lembur_bal > 0 ? it.lembur_bal : it.kuantitas_bal) + ' bal)'"></span>
                                    </div>

                                    <!-- Subtotal Upah Badge -->
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-success font-mono font-extrabold text-xs py-1 px-2.5 shadow-xs inline-flex items-center justify-center" 
                                              x-text="it.total_upah_didapat_formatted || formatRupiah(it.total_upah_didapat)">
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function historyApp() {
    return {
        isLoading: false,
        abortController: null,
        
        tglAwal: '<?= addslashes($tglAwal) ?>',
        tglAkhir: '<?= addslashes($tglAkhir) ?>',
        periodeText: '<?= addslashes(Format::tanggalIndo($tglAwal) . ' s/d ' . Format::tanggalIndo($tglAkhir)) ?>',
        
        totalPcs: <?= (int)$totalPcs ?>,
        totalBal: <?= (int)$totalBal ?>,
        totalLemburPcs: <?= (int)$totalLemburPcs ?>,
        totalLemburBal: <?= (int)$totalLemburBal ?>,
        totalUpah: <?= (float)$totalUpah ?>,
        totalUpahFormatted: '<?= addslashes(Format::rupiah($totalUpah)) ?>',
        
        rekapBalBahan: (<?= json_encode($rekapBalBahan ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>) || [],
        historyGrouped: (<?= json_encode($initialRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>) || [],
        
        selectedKaryawan: '<?= addslashes($karyawanId) ?>',
        karyawanDropdownOpen: false,
        karyawanSearch: '',
        karyawanHighlightedIndex: 0,
        karyawanList: (<?= json_encode(array_values($karyawanList), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>) || [],
        
        selectedItem: '<?= addslashes($itemId) ?>',
        itemDropdownOpen: false,
        itemSearch: '',
        itemHighlightedIndex: 0,
        itemList: (<?= json_encode(array_values($itemList), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>) || [],

        showDetailModal: false,
        detailEmp: null,

        get currentKaryawanLabel() {
            if (!this.selectedKaryawan) return '-- Semua Karyawan --';
            const k = this.karyawanList.find(x => String(x.id) === String(this.selectedKaryawan));
            return k ? k.nama_karyawan : '-- Semua Karyawan --';
        },

        get currentItemLabel() {
            if (!this.selectedItem) return '-- Semua Produk --';
            const i = this.itemList.find(x => String(x.id) === String(this.selectedItem));
            return i ? (i.nama_item + (i.nama_kelompok ? ' [' + i.nama_kelompok + ']' : '')) : '-- Semua Produk --';
        },

        get filteredKaryawan() {
            if (!this.karyawanSearch.trim()) return this.karyawanList;
            const q = this.karyawanSearch.toLowerCase().trim();
            return this.karyawanList.filter(k => 
                (k.nama_karyawan || '').toLowerCase().includes(q) ||
                (k.nama_panggilan || '').toLowerCase().includes(q) ||
                (k.posisi || '').toLowerCase().includes(q)
            );
        },

        get filteredItems() {
            if (!this.itemSearch.trim()) return this.itemList;
            const q = this.itemSearch.toLowerCase().trim();
            return this.itemList.filter(i => 
                (i.nama_item || '').toLowerCase().includes(q) ||
                (i.kode_sku || '').toLowerCase().includes(q) ||
                (i.nama_kelompok || '').toLowerCase().includes(q)
            );
        },

        toggleKaryawanDropdown() {
            this.itemDropdownOpen = false;
            this.karyawanDropdownOpen = !this.karyawanDropdownOpen;
            if (this.karyawanDropdownOpen) {
                this.karyawanSearch = '';
                this.karyawanHighlightedIndex = 0;
                this.$nextTick(() => {
                    this.$refs.karyawanSearchInput?.focus();
                    if (window.lucide) lucide.createIcons();
                });
            }
        },

        selectKaryawan(id) {
            this.selectedKaryawan = id;
            this.karyawanDropdownOpen = false;
            this.applyFilter();
        },

        navigateKaryawan(step) {
            const list = this.karyawanSearch ? this.filteredKaryawan : [{ id: '' }, ...this.filteredKaryawan];
            if (!list.length) return;
            this.karyawanHighlightedIndex = Math.max(0, Math.min(list.length - 1, this.karyawanHighlightedIndex + step));
            this.scrollToHighlighted(this.$refs.karyawanListWrap, this.karyawanHighlightedIndex);
        },

        selectHighlightedKaryawan() {
            const list = this.karyawanSearch ? this.filteredKaryawan : [{ id: '' }, ...this.filteredKaryawan];
            if (list[this.karyawanHighlightedIndex]) {
                this.selectKaryawan(list[this.karyawanHighlightedIndex].id || '');
            }
        },

        toggleItemDropdown() {
            this.karyawanDropdownOpen = false;
            this.itemDropdownOpen = !this.itemDropdownOpen;
            if (this.itemDropdownOpen) {
                this.itemSearch = '';
                this.itemHighlightedIndex = 0;
                this.$nextTick(() => {
                    this.$refs.itemSearchInput?.focus();
                    if (window.lucide) lucide.createIcons();
                });
            }
        },

        selectItem(id) {
            this.selectedItem = id;
            this.itemDropdownOpen = false;
            this.applyFilter();
        },

        navigateItem(step) {
            const list = this.itemSearch ? this.filteredItems : [{ id: '' }, ...this.filteredItems];
            if (!list.length) return;
            this.itemHighlightedIndex = Math.max(0, Math.min(list.length - 1, this.itemHighlightedIndex + step));
            this.scrollToHighlighted(this.$refs.itemListWrap, this.itemHighlightedIndex);
        },

        selectHighlightedItem() {
            const list = this.itemSearch ? this.filteredItems : [{ id: '' }, ...this.filteredItems];
            if (list[this.itemHighlightedIndex]) {
                this.selectItem(list[this.itemHighlightedIndex].id || '');
            }
        },

        scrollToHighlighted(wrapEl, index) {
            if (!wrapEl) return;
            const items = wrapEl.querySelectorAll('.searchable-option');
            if (items[index]) {
                items[index].scrollIntoView({ block: 'nearest' });
            }
        },

        setShortcutPeriode(start, end) {
            this.tglAwal = start;
            this.tglAkhir = end;
            this.applyFilter();
        },

        resetFilter() {
            this.tglAwal = '<?= date('Y-m-01') ?>';
            this.tglAkhir = '<?= date('Y-m-d') ?>';
            this.selectedKaryawan = '';
            this.selectedItem = '';
            this.applyFilter();
        },

        async applyFilter() {
            if (this.abortController) {
                this.abortController.abort();
            }
            this.abortController = new AbortController();

            this.isLoading = true;

            const params = new URLSearchParams({
                tanggal_awal: this.tglAwal,
                tanggal_akhir: this.tglAkhir,
                karyawan_id: this.selectedKaryawan,
                item_id: this.selectedItem,
                ajax: '1'
            });

            // Update URL browser address bar tanpa reload
            const displayUrl = '<?= Router::url('/produksi/history') ?>?' + new URLSearchParams({
                tanggal_awal: this.tglAwal,
                tanggal_akhir: this.tglAkhir,
                karyawan_id: this.selectedKaryawan,
                item_id: this.selectedItem
            }).toString();
            window.history.replaceState(null, '', displayUrl);

            try {
                const response = await fetch('<?= Router::url('/produksi/history') ?>?' + params.toString(), {
                    signal: this.abortController.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('HTTP error ' + response.status);

                const data = await response.json();
                if (data && data.success) {
                    this.totalPcs = data.totalPcs || 0;
                    this.totalBal = data.totalBal || 0;
                    this.totalLemburPcs = data.totalLemburPcs || 0;
                    this.totalLemburBal = data.totalLemburBal || 0;
                    this.totalUpah = data.totalUpah || 0;
                    this.totalUpahFormatted = data.totalUpahFormatted || 'Rp 0';
                    this.periodeText = data.periodeText || '';
                    this.rekapBalBahan = data.rekapBalBahan || [];
                    this.historyGrouped = data.historyGrouped || [];
                }
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('Filter request error:', err);
                }
            } finally {
                this.isLoading = false;
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            }
        },

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

        formatRupiah(val) {
            return window.formatRupiah ? window.formatRupiah(val) : ('Rp ' + Number(val || 0).toLocaleString('id-ID'));
        },

        formatQty(val) {
            return window.formatQty ? window.formatQty(val) : Number(val || 0).toLocaleString('id-ID');
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

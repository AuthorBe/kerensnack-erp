<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();

$totalSku = count($items);
$totalPcsGudang = array_sum(array_column($items, 'stok_fisik_saat_ini'));
$totalMenipis = count(array_filter($items, fn($i) => (float)$i['stok_fisik_saat_ini'] > 0 && (float)$i['stok_fisik_saat_ini'] <= (float)($i['stok_minimum_peringatan'] ?? 10)));
$totalKosong = count(array_filter($items, fn($i) => (float)$i['stok_fisik_saat_ini'] <= 0));
$totalValuasiGudang = array_sum(array_map(fn($i) => (float)$i['stok_fisik_saat_ini'] * (float)($i['harga_pokok_pembelian'] ?? 0), $items));

$countBarangJadi = count(array_filter($items, fn($i) => ($i['tipe_item'] ?? 'barang_jadi') === 'barang_jadi'));
$countBahanMentah = count(array_filter($items, fn($i) => ($i['tipe_item'] ?? '') === 'bahan_mentah'));
$countBahanKemas = count(array_filter($items, fn($i) => ($i['tipe_item'] ?? '') === 'bahan_kemas'));
?>

<div x-data="inventoryApp()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER (Clean Flex Layout: No Title Wrapping)                     -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-emerald" style="flex-shrink:0;">
                <i data-lucide="warehouse"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#10b981;"></span>
                    <span>Inventaris Gudang</span>
                </div>
                <h1 class="page-title" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= $pageTitle ?? 'Katalog &amp; Mutasi Stok Fisik' ?>
                </h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Monitoring stok realtime, status ketersediaan &amp; kartu stok gudang' ?></p>
            </div>
        </div>

        <!-- Action Buttons (Right-aligned, Compact) -->
        <div class="page-header-actions" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; flex-shrink:0;">
            <?php if (Auth::can('inventory.opname')): ?>
            <a href="<?= Router::url('/inventory/bulk-opname') ?>" class="btn btn-primary" style="height:38px; font-weight:700; display:inline-flex; align-items:center; gap:7px;">
                <i data-lucide="layers" style="width:16px; height:16px;"></i>
                <span>Bulk Opname Gudang</span>
            </a>
            <?php endif; ?>
            <?php if (Auth::can(['inventory.view_all', 'inventory.opname'])): ?>
            <a href="<?= Router::url('/inventory/opname/history') ?>" class="btn btn-secondary" style="height:38px; font-weight:600; display:inline-flex; align-items:center; gap:7px;">
                <i data-lucide="history" style="width:15px; height:15px;"></i>
                <span>Riwayat Opname</span>
            </a>
            <?php endif; ?>
            <a href="<?= Router::url('/guide#bab-inventori-opname') ?>" target="_blank" class="btn btn-ghost" style="height:38px; font-weight:700; color:var(--color-primary); background:rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.25); display:inline-flex; align-items:center; gap:6px; text-decoration:none;" title="Buka Panduan Modul Inventaris &amp; Bulk Opname di Tab Baru">
                <i data-lucide="book-open" style="width:15px; height:15px;"></i>
                <span>Panduan</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. QUICK KPI SUMMARY CARDS                                                -->
    <!-- ========================================================================= -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
        <!-- Total SKU -->
        <div class="card" style="padding:14px 16px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(99,102,241,0.1); color:var(--color-primary); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="package" style="width:20px; height:20px;"></i>
            </div>
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--color-ink-mute); letter-spacing:0.04em;">Total Katalog</div>
                <div style="font-size:18px; font-weight:800; font-family:var(--font-mono); color:var(--color-ink); margin-top:2px;">
                    <span x-text="kpiTotalSku"></span> <span style="font-size:12px; font-weight:600; color:var(--color-ink-mute);">SKU</span>
                </div>
            </div>
        </div>

        <!-- Total Fisik Gudang -->
        <div class="card" style="padding:14px 16px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(16,185,129,0.1); color:#059669; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="boxes" style="width:20px; height:20px;"></i>
            </div>
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--color-ink-mute); letter-spacing:0.04em;">Total Fisik Gudang</div>
                <div style="font-size:18px; font-weight:800; font-family:var(--font-mono); color:#059669; margin-top:2px;">
                    <span x-text="formatQty(kpiTotalFisik)"></span> <span style="font-size:12px; font-weight:600; color:var(--color-ink-mute);">pcs</span>
                </div>
            </div>
        </div>

        <!-- Valuasi Aset HPP -->
        <div class="card" style="padding:14px 16px; border-radius:14px; border:1px solid #bfdbfe; background:rgba(59,130,246,0.06); display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(59,130,246,0.15); color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="coins" style="width:20px; height:20px;"></i>
            </div>
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:#1d4ed8; letter-spacing:0.04em;">Valuasi Aset (HPP)</div>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); color:#1d4ed8; margin-top:2px;"
                     x-text="formatRupiah(kpiTotalValuasi)">
                </div>
            </div>
        </div>

        <!-- Stok Menipis -->
        <div class="card" style="padding:14px 16px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(245,158,11,0.1); color:#d97706; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="alert-triangle" style="width:20px; height:20px;"></i>
            </div>
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--color-ink-mute); letter-spacing:0.04em;">Stok Menipis</div>
                <div style="font-size:18px; font-weight:800; font-family:var(--font-mono); color:#d97706; margin-top:2px;">
                    <span x-text="kpiTotalMenipis"></span> <span style="font-size:12px; font-weight:600; color:var(--color-ink-mute);">SKU</span>
                </div>
            </div>
        </div>

        <!-- Stok Kosong -->
        <div class="card" style="padding:14px 16px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(239,68,68,0.1); color:#dc2626; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="x-circle" style="width:20px; height:20px;"></i>
            </div>
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--color-ink-mute); letter-spacing:0.04em;">Stok Habis (0)</div>
                <div style="font-size:18px; font-weight:800; font-family:var(--font-mono); color:#dc2626; margin-top:2px;">
                    <span x-text="kpiTotalKosong"></span> <span style="font-size:12px; font-weight:600; color:var(--color-ink-mute);">SKU</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2.5 TAB FILTER KATEGORI ITEM (Barang Jadi, Bahan Mentah, Bahan Kemas)       -->
    <!-- ========================================================================= -->
    <div class="card" style="padding:10px 14px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; flex-direction:column; gap:8px;">
        <!-- Row 1: Tab Navigation Buttons -->
        <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5" style="min-width:0; width:100%;">
            <!-- Tab 1: Semua -->
            <button type="button" 
                    @click="setTab('all')"
                    :class="activeTab === 'all' ? 'shadow-sm' : 'hover:bg-slate-100 dark:hover:bg-slate-800/80'"
                    class="btn btn-sm"
                    :style="activeTab === 'all' 
                        ? 'background:#881337; color:#ffffff; border-color:#881337; font-weight:700;' 
                        : 'border-color:var(--color-hairline); color:var(--color-ink); font-weight:600; background:transparent;'"
                    style="height:34px; padding:0 12px; display:inline-flex; align-items:center; gap:7px; border-radius:8px; font-size:12px; white-space:nowrap;">
                <i data-lucide="layers" style="width:14px; height:14px;"></i>
                <span>Semua Stok</span>
                <span class="badge-counter"
                      :style="activeTab === 'all' 
                          ? 'background:rgba(255,255,255,0.25); color:#fff;' 
                          : 'background:var(--color-canvas-soft); color:var(--color-ink-mute); border-color:var(--color-hairline);'"
                      x-text="items.length"></span>
            </button>

            <!-- Tab 2: Barang Jadi -->
            <button type="button" 
                    @click="setTab('barang_jadi')"
                    :class="activeTab === 'barang_jadi' ? 'shadow-sm' : 'hover:bg-slate-100 dark:hover:bg-slate-800/80'"
                    class="btn btn-sm"
                    :style="activeTab === 'barang_jadi' 
                        ? 'background:#2563eb; color:#ffffff; border-color:#2563eb; font-weight:700;' 
                        : 'border-color:var(--color-hairline); color:var(--color-ink); font-weight:600; background:transparent;'"
                    style="height:34px; padding:0 12px; display:inline-flex; align-items:center; gap:7px; border-radius:8px; font-size:12px; white-space:nowrap;">
                <i data-lucide="package" style="width:14px; height:14px;"></i>
                <span>Barang Jadi</span>
                <span class="badge-counter"
                      :style="activeTab === 'barang_jadi' 
                          ? 'background:rgba(255,255,255,0.25); color:#fff;' 
                          : 'background:rgba(37,99,235,0.1); color:#2563eb; border-color:rgba(37,99,235,0.25);'"
                      x-text="countBarangJadi"></span>
            </button>

            <!-- Tab 3: Bahan Mentah -->
            <button type="button" 
                    @click="setTab('bahan_mentah')"
                    :class="activeTab === 'bahan_mentah' ? 'shadow-sm' : 'hover:bg-slate-100 dark:hover:bg-slate-800/80'"
                    class="btn btn-sm"
                    :style="activeTab === 'bahan_mentah' 
                        ? 'background:#d97706; color:#ffffff; border-color:#d97706; font-weight:700;' 
                        : 'border-color:var(--color-hairline); color:var(--color-ink); font-weight:600; background:transparent;'"
                    style="height:34px; padding:0 12px; display:inline-flex; align-items:center; gap:7px; border-radius:8px; font-size:12px; white-space:nowrap;">
                <i data-lucide="archive" style="width:14px; height:14px;"></i>
                <span>Bahan Mentah</span>
                <span class="badge-counter"
                      :style="activeTab === 'bahan_mentah' 
                          ? 'background:rgba(255,255,255,0.25); color:#fff;' 
                          : 'background:rgba(217,119,6,0.1); color:#d97706; border-color:rgba(217,119,6,0.25);'"
                      x-text="countBahanMentah"></span>
            </button>

            <!-- Tab 4: Bahan Kemas -->
            <button type="button" 
                    @click="setTab('bahan_kemas')"
                    :class="activeTab === 'bahan_kemas' ? 'shadow-sm' : 'hover:bg-slate-100 dark:hover:bg-slate-800/80'"
                    class="btn btn-sm"
                    :style="activeTab === 'bahan_kemas' 
                        ? 'background:#0f766e; color:#ffffff; border-color:#0f766e; font-weight:700;' 
                        : 'border-color:var(--color-hairline); color:var(--color-ink); font-weight:600; background:transparent;'"
                    style="height:34px; padding:0 12px; display:inline-flex; align-items:center; gap:7px; border-radius:8px; font-size:12px; white-space:nowrap;">
                <i data-lucide="box" style="width:14px; height:14px;"></i>
                <span>Bahan Kemas</span>
                <span class="badge-counter"
                      :style="activeTab === 'bahan_kemas' 
                          ? 'background:rgba(255,255,255,0.25); color:#fff;' 
                          : 'background:rgba(15,118,110,0.1); color:#0f766e; border-color:rgba(15,118,110,0.25);'"
                      x-text="countBahanKemas"></span>
            </button>
        </div>

        <!-- Row 2: Active Filter Status & Reset Action (Harmonis, Seimbang, & Tidak Ngegantung) -->
        <div x-cloak x-show="hasActiveFilter" x-transition
             class="flex items-center justify-between gap-3 pt-2.5 mt-0.5 flex-wrap"
             style="border-top:1px solid var(--color-hairline);">
            <!-- Sisi Kiri: Status & Jumlah Cocok -->
            <div class="inline-flex items-center gap-2">
                <div class="inline-flex items-center justify-center gap-1.5 rounded-full"
                     style="height:24px; padding:0 10px; font-size:11.5px; font-weight:600; line-height:1; background:rgba(37,99,235,0.08); color:var(--color-primary); border:1px solid rgba(37,99,235,0.22); box-sizing:border-box;">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse" style="flex-shrink:0;"></span>
                    <span x-text="filteredItems.length + ' item cocok'" style="line-height:1; display:inline-block; transform:translateY(-0.5px);"></span>
                </div>
                <span class="text-xs text-slate-400 dark:text-slate-500 font-normal hidden sm:inline" style="line-height:1;">
                    menyesuaikan kriteria pencarian &amp; filter aktif
                </span>
            </div>

            <!-- Sisi Kanan: Tombol Reset yang Terdefinisi Rapi di Ujung Kanan (Bukan Teks Melayang) -->
            <button type="button" @click="resetAllFilters()"
                    class="btn btn-secondary btn-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                    style="height:26px; padding:0 10px; font-size:11.5px; font-weight:600; line-height:1; display:inline-flex; align-items:center; gap:5px; border-radius:6px; border:1px solid var(--color-hairline);"
                    title="Kembalikan semua filter ke kondisi awal">
                <i data-lucide="rotate-ccw" style="width:12px; height:12px;"></i>
                <span>Reset Filter</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. TOOLBAR & FILTER CARD                                                  -->
    <!-- ========================================================================= -->
    <div class="card" style="padding:14px 18px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <!-- Live Search with Enhanced Multi-keyword & Clear Button -->
        <div class="form-input-icon flex-1 relative" style="min-width:240px;">
            <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
            <input type="text" 
                   x-ref="searchInput"
                   x-model="searchQuery" 
                   @input.debounce.180ms="onSearchInput()"
                   placeholder="Cari SKU, Nama Produk/Bahan, Barcode, Kemasan..."
                   class="form-input" 
                   style="height:38px; padding-right:58px; font-size:13px;">

            <!-- Shortcut Hint '/' when query is empty -->
            <span x-show="!searchQuery" 
                  class="hidden sm:inline-flex items-center justify-center font-mono text-[10px] text-slate-400 border border-slate-200 dark:border-slate-700 rounded px-1.5 py-0.5"
                  style="position:absolute; right:10px; top:50%; transform:translateY(-50%); pointer-events:none;">
                /
            </span>

            <!-- Clear Search Button when query is present -->
            <button type="button" 
                    x-cloak 
                    x-show="searchQuery" 
                    @click="clearSearch()"
                    class="btn btn-ghost btn-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    style="position:absolute; right:8px; top:50%; transform:translateY(-50%); height:24px; width:24px; padding:0; display:flex; align-items:center; justify-content:center;"
                    title="Hapus pencarian">
                <i data-lucide="x" style="width:14px; height:14px;"></i>
            </button>
        </div>

        <!-- Filter Grup Kemasan (Tampil untuk 'all' dan 'barang_jadi') -->
        <div style="min-width:180px;" x-show="activeTab === 'all' || activeTab === 'barang_jadi'">
            <select x-model="selectedGroup" @change="applyFilterWithSkeleton()" class="form-select" style="height:38px; font-size:13px;">
                <option value="">Semua Grup Kemasan</option>
                <?php foreach ($groups ?? [] as $g): ?>
                <option value="<?= htmlspecialchars($g['kode_grup']) ?>"><?= htmlspecialchars($g['kode_grup'] . ' - ' . $g['nama_grup']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filter Status Ketersediaan -->
        <div style="min-width:150px;">
            <select x-model="filterStatus" @change="applyFilterWithSkeleton()" class="form-select" style="height:38px; font-size:13px;">
                <option value="all">Semua Status</option>
                <option value="available">Tersedia (> Min)</option>
                <option value="low">Menipis (1 - Min)</option>
                <option value="empty">Kosong (0)</option>
            </select>
        </div>

        <!-- Tampilkan per Halaman -->
        <div style="display:flex; align-items:center; gap:6px;">
            <span style="font-size:12px; color:var(--color-ink-mute); white-space:nowrap;">Baris:</span>
            <select x-model="perPage" @change="applyFilterWithSkeleton()" class="form-select" style="height:38px; width:80px; font-size:12.5px; font-family:var(--font-mono);">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="all">Semua</option>
            </select>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. DATA TABLE (LIGHTWEIGHT & PAGINATED)                                   -->
    <!-- ========================================================================= -->
    <div class="table-wrapper" x-ref="tableWrapper"
         style="overflow-anchor:none; scroll-behavior:auto !important;">
        <div class="table-scroll"
             style="overflow-anchor:none;">
            <table class="data-table" style="overflow-anchor:none;">
                <thead>
                    <tr>
                        <th style="width:45px; text-align:center;">No</th>
                        <th style="min-width:260px;">Produk &amp; SKU</th>
                        <th class="hide-sm" style="min-width:130px;">Barcode</th>
                        <th style="text-align:right; width:150px;">Stok Fisik Gudang</th>
                        <th class="hide-mobile" style="text-align:center; width:110px;">Status</th>
                        <th style="text-align:center; width:160px;">Aksi</th>
                    </tr>
                </thead>
                <!-- ============================================================= -->
                <!-- SKELETON LOADING TBODY (HANYA TABEL YANG MENAMPILKAN SKELETON) -->
                <!-- ============================================================= -->
                <tbody x-show="isTableLoading" x-cloak>
                    <template x-for="n in skeletonRows" :key="n">
                        <tr class="skeleton-row" style="background:transparent;">
                            <!-- No -->
                            <td style="text-align:center;">
                                <div class="skeleton-shimmer skeleton-box" style="width:20px; height:15px; margin:0 auto; border-radius:4px;"></div>
                            </td>

                            <!-- Produk & SKU -->
                            <td>
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                                    <div class="skeleton-shimmer skeleton-pill" style="width:72px; height:18px;"></div>
                                    <div class="skeleton-shimmer skeleton-line" style="width:50%; height:14px;"></div>
                                </div>
                                <div class="skeleton-shimmer skeleton-line" style="width:28%; height:11px;"></div>
                            </td>

                            <!-- Barcode -->
                            <td class="hide-sm">
                                <div class="skeleton-shimmer skeleton-box" style="width:88px; height:18px; border-radius:4px;"></div>
                            </td>

                            <!-- Stok Fisik -->
                            <td style="text-align:right;">
                                <div class="skeleton-shimmer skeleton-line" style="width:75px; height:16px; margin-left:auto; margin-bottom:4px;"></div>
                                <div class="skeleton-shimmer skeleton-pill show-mobile" style="width:48px; height:13px; margin-left:auto;"></div>
                            </td>

                            <!-- Status -->
                            <td class="hide-mobile" style="text-align:center;">
                                <div class="skeleton-shimmer skeleton-pill" style="width:65px; height:20px; margin:0 auto;"></div>
                            </td>

                            <!-- Aksi -->
                            <td style="text-align:center;">
                                <div style="display:flex; align-items:center; justify-content:center; gap:5px;">
                                    <div class="skeleton-shimmer skeleton-box" style="width:58px; height:26px; border-radius:6px;"></div>
                                    <div class="skeleton-shimmer skeleton-box" style="width:52px; height:26px; border-radius:6px;"></div>
                                    <div class="skeleton-shimmer skeleton-box" style="width:28px; height:26px; border-radius:6px;"></div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>

                <!-- ============================================================= -->
                <!-- REAL DATA TBODY                                               -->
                <!-- ============================================================= -->
                <tbody x-show="!isTableLoading">
                    <template x-if="paginatedItems.length === 0">
                        <tr>
                            <td colspan="6" style="text-align:center; padding:48px 16px; color:var(--color-ink-mute);">
                                <div style="width:44px; height:44px; border-radius:12px; background:var(--color-canvas-soft); border:1px solid var(--color-hairline); display:flex; align-items:center; justify-content:center; margin:0 auto 10px auto; color:var(--color-ink-mute); opacity:0.75;">
                                    <i data-lucide="package-search" style="width:22px; height:22px;"></i>
                                </div>
                                <div style="font-weight:700; color:var(--color-ink); font-size:13.5px; margin-bottom:4px;">Tidak ada item yang cocok</div>
                                <div style="font-size:12px; color:var(--color-ink-mute); max-width:320px; margin:0 auto 12px auto;">
                                    Tidak ditemukan rekaman stok dengan kata kunci atau filter status yang dipilih.
                                </div>
                                <button type="button" @click="resetAllFilters()" class="btn btn-secondary btn-sm" style="font-size:11.5px; display:inline-flex; align-items:center; gap:5px;">
                                    <i data-lucide="rotate-ccw" style="width:12px; height:12px;"></i>
                                    <span>Reset Filter &amp; Pencarian</span>
                                </button>
                            </td>
                        </tr>
                    </template>

                    <template x-for="(item, index) in paginatedItems" :key="item.id">
                        <tr>
                            <!-- No -->
                            <td style="text-align:center; font-family:var(--font-mono); font-size:12px; color:var(--color-ink-mute);"
                                x-text="getStartRowIndex() + index + 1"></td>

                            <!-- Produk & SKU -->
                            <td>
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    <span class="badge badge-mono" x-text="item.kode_sku"></span>
                                    <span style="font-size:13px; font-weight:700; color:var(--color-ink);" x-text="item.nama_item"></span>

                                    <!-- Category Pill Badges for easy distinction -->
                                    <span x-show="item.tipe_item === 'bahan_mentah'" 
                                          class="badge badge-warning" 
                                          style="font-size:10px; font-weight:700; padding:1px 6px;">
                                        Bahan Mentah
                                    </span>
                                    <span x-show="item.tipe_item === 'bahan_kemas'" 
                                          class="badge" 
                                          style="background:rgba(15,118,110,0.12); color:#0f766e; border:1px solid rgba(15,118,110,0.25); font-size:10px; font-weight:700; padding:1px 6px;">
                                        Bahan Kemas
                                    </span>
                                </div>
                                <div x-show="item.kode_grup || item.nama_grup"
                                     style="font-size:11px; font-family:var(--font-mono); color:var(--color-ink-mute); margin-top:2px;"
                                     x-text="(item.kode_grup ? item.kode_grup + (item.nama_grup ? ' • ' + item.nama_grup : '') : (item.nama_grup || ''))"></div>
                            </td>

                            <!-- Barcode -->
                            <td class="hide-sm">
                                <span style="font-family:var(--font-mono); font-size:11px; padding:2px 6px; border:1px solid var(--color-hairline); border-radius:4px; background:var(--color-canvas-soft); color:var(--color-ink-mute);"
                                      x-text="formatBarcode(item.barcode || item.barcode_universal)"></span>
                            </td>

                            <!-- Stok Fisik -->
                            <td style="text-align:right;">
                                <div style="font-family:var(--font-mono); font-weight:800; font-size:14px; color:var(--color-ink);"
                                     x-text="formatQty(item.stok_fisik_saat_ini) + ' ' + (item.satuan_dasar || 'pcs')"></div>
                                <div class="show-mobile" style="margin-top:3px;">
                                    <span x-show="parseFloat(item.stok_fisik_saat_ini) > (parseFloat(item.stok_minimum_peringatan) || 10)" class="badge badge-success" style="font-size:10px;">Tersedia</span>
                                    <span x-show="parseFloat(item.stok_fisik_saat_ini) > 0 && parseFloat(item.stok_fisik_saat_ini) <= (parseFloat(item.stok_minimum_peringatan) || 10)" class="badge badge-warning" style="font-size:10px;">Menipis</span>
                                    <span x-show="parseFloat(item.stok_fisik_saat_ini) <= 0" class="badge badge-muted" style="font-size:10px;">Kosong</span>
                                </div>
                            </td>

                            <!-- Status (Desktop) -->
                            <td class="hide-mobile" style="text-align:center;">
                                <span x-show="parseFloat(item.stok_fisik_saat_ini) > (parseFloat(item.stok_minimum_peringatan) || 10)" class="badge badge-success">Tersedia</span>
                                <span x-show="parseFloat(item.stok_fisik_saat_ini) > 0 && parseFloat(item.stok_fisik_saat_ini) <= (parseFloat(item.stok_minimum_peringatan) || 10)" class="badge badge-warning">Menipis</span>
                                <span x-show="parseFloat(item.stok_fisik_saat_ini) <= 0" class="badge badge-muted">Kosong</span>
                            </td>

                            <!-- Aksi -->
                            <td style="text-align:center;">
                                <div style="display:flex; align-items:center; justify-content:center; gap:5px;">
                                    <?php if (Auth::can('inventory.opname')): ?>
                                    <button type="button" @click="openAdjust(item)" class="btn btn-secondary btn-sm" style="padding:4px 8px; font-size:11.5px;" title="Koreksi Opname Fisik">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;"><line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/></svg>
                                        <span>Opname</span>
                                    </button>
                                    <?php endif; ?>

                                    <?php if (Auth::can('inventory.waste')): ?>
                                    <button type="button" @click="openWaste(item)" class="btn btn-sm" style="background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; padding:4px 8px; font-size:11.5px; font-weight:700;" title="Catat Barang Rusak / Waste">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                        <span>Waste</span>
                                    </button>
                                    <?php endif; ?>

                                    <button type="button" @click="openHistory(item)" class="btn btn-ghost btn-sm" style="padding:4px 7px; border:1px solid var(--color-hairline);" title="Lihat Kartu Stok &amp; Riwayat Mutasi">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-primary); display:inline-block;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- ===================================================================== -->
        <!-- PAGINATION BAR (Instant 0ms in Alpine)                                -->
        <!-- ===================================================================== -->
        <div x-ref="paginationBar" class="pagination-bar"
             style="padding:12px 18px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; border-top:1px solid var(--color-hairline); background:var(--color-surface);"
             x-show="perPage !== 'all' && totalPages > 1">
            <div style="font-size:12px; color:var(--color-ink-mute); font-family:var(--font-mono);">
                Menampilkan <strong class="text-ink" x-text="getStartRowIndex() + 1"></strong> -
                <strong class="text-ink" x-text="Math.min(getStartRowIndex() + parseInt(perPage), filteredItems.length)"></strong>
                dari <strong class="text-ink" x-text="filteredItems.length"></strong> produk
            </div>

            <div style="display:flex; align-items:center; gap:6px;">
                <!-- Tombol Prev -->
                <button type="button" @click.prevent="prevPage($event)"
                        class="btn btn-secondary btn-sm"
                        :disabled="currentPage <= 1"
                        :style="currentPage <= 1 ? 'opacity:0.35; cursor:not-allowed;' : 'cursor:pointer;'"
                        style="padding:4px 12px; height:32px; transition:none !important; transform:none !important;">
                    &larr; Prev
                </button>

                <!-- Indikator Halaman -->
                <span style="font-size:12px; font-family:var(--font-mono); padding:0 8px; color:var(--color-ink); user-select:none;">
                    Hal <strong x-text="currentPage"></strong> / <span x-text="totalPages"></span>
                </span>

                <!-- Tombol Next -->
                <button type="button" @click.prevent="nextPage($event)"
                        class="btn btn-secondary btn-sm"
                        :disabled="currentPage >= totalPages"
                        :style="currentPage >= totalPages ? 'opacity:0.35; cursor:not-allowed;' : 'cursor:pointer;'"
                        style="padding:4px 12px; height:32px; transition:none !important; transform:none !important;">
                    Next &rarr;
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: PENYESUAIAN STOK (OPNAME FISIK, ITEM MASUK, ITEM KELUAR)          -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showAdjustModal" x-cloak class="modal-backdrop" @click="showAdjustModal = false">
        <div class="modal-box" style="max-width:540px;" @click.stop>
            <div class="modal-handle"><div class="modal-handle-bar"></div></div>
            <div class="modal-header">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div style="width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all 0.2s;"
                         :style="adjustMode === 'opname' ? 'background:rgba(37,99,235,0.12); color:#2563eb;' : (adjustMode === 'masuk' ? 'background:rgba(16,185,129,0.12); color:#10b981;' : 'background:rgba(239,68,68,0.12); color:#ef4444;')">
                        <i :data-lucide="adjustMode === 'opname' ? 'clipboard-check' : (adjustMode === 'masuk' ? 'arrow-down-left' : 'arrow-up-right')" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="modal-title">Penyesuaian Stok Barang</div>
                        <div style="font-size:12px; font-family:var(--font-mono); color:var(--color-primary); margin-top:1px;"
                             x-text="(selectedItem.kode_sku || '') + ' — ' + (selectedItem.nama_item || '')"></div>
                    </div>
                </div>
                <button type="button" @click="showAdjustModal = false" class="modal-close-x" title="Tutup Modal">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/inventory/adjust') ?>" method="POST" data-action-text="Menyimpan penyesuaian stok...">
                <div class="modal-body custom-scrollbar space-y-3.5">
                    <?= \App\Helpers\CSRF::field() ?>
                    <input type="hidden" name="item_id" :value="selectedItem.id">
                    <input type="hidden" name="mode" :value="adjustMode">

                    <!-- 3-Way Mode Switcher (Universal Segmented Control) -->
                    <div>
                        <label class="form-label font-semibold mb-1.5 block">Pilih Aksi Penyesuaian</label>
                        <div class="segmented-track">
                            <!-- Mode 1: Opname Fisik -->
                            <button type="button" @click="setAdjustMode('opname')"
                                    class="segmented-btn"
                                    :class="adjustMode === 'opname' ? 'is-active is-opname' : ''">
                                <i data-lucide="clipboard-check" style="width:14px; height:14px;"></i>
                                <span>Opname Fisik</span>
                            </button>

                            <!-- Mode 2: Item Masuk -->
                            <button type="button" @click="setAdjustMode('masuk')"
                                    class="segmented-btn"
                                    :class="adjustMode === 'masuk' ? 'is-active is-masuk' : ''">
                                <i data-lucide="arrow-down-left" style="width:14px; height:14px;"></i>
                                <span>Item Masuk</span>
                            </button>

                            <!-- Mode 3: Item Keluar -->
                            <button type="button" @click="setAdjustMode('keluar')"
                                    class="segmented-btn"
                                    :class="adjustMode === 'keluar' ? 'is-active is-keluar' : ''">
                                <i data-lucide="arrow-up-right" style="width:14px; height:14px;"></i>
                                <span>Item Keluar</span>
                            </button>
                        </div>
                    </div>

                    <!-- Banner Stok Sistem Saat Ini -->
                    <div style="padding:10px 14px; background:var(--color-canvas-soft); border:1px solid var(--color-hairline); border-radius:10px; display:flex; align-items:center; justify-content:space-between;">
                        <span style="font-size:12px; color:var(--color-ink-mute); font-weight:500;">Stok Fisik di Sistem:</span>
                        <strong class="font-mono" style="font-size:14px; color:var(--color-ink);" x-text="(selectedItem.stok_fisik_saat_ini || 0) + ' ' + (selectedItem.satuan_dasar || 'pcs')"></strong>
                    </div>

                    <!-- Info Panduan Peruntukan Mode & Dampak Laporan Keuangan (Modern Multi-Tone Card) -->
                    <div style="padding:12px 14px; background:var(--color-canvas-soft); border:1px solid var(--color-hairline); border-radius:12px; font-size:12px; line-height:1.55; color:var(--color-ink);"
                         :style="adjustMode === 'opname' 
                             ? 'border-left:3.5px solid #2563eb;' 
                             : (adjustMode === 'masuk' 
                                 ? 'border-left:3.5px solid #059669;' 
                                 : 'border-left:3.5px solid #e11d48;')">
                        
                        <!-- Mode Opname Fisik -->
                        <div x-show="adjustMode === 'opname'">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; padding-bottom:6px; border-bottom:1px solid var(--color-hairline); flex-wrap:wrap;">
                                <div style="display:inline-flex; align-items:center; gap:7px; font-weight:700; font-size:12px; color:var(--color-ink);">
                                    <span style="width:22px; height:22px; border-radius:6px; background:rgba(37,99,235,0.12); color:#2563eb; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <i data-lucide="clipboard-check" style="width:13px; height:13px;"></i>
                                    </span>
                                    <span>Audit Stok Opname Fisik</span>
                                </div>
                                <span class="badge-counter" style="background:rgba(37,99,235,0.08); color:#1d4ed8; border-color:rgba(37,99,235,0.22); font-size:10.5px; font-weight:700; padding:2px 8px;">
                                    Selisih Rak
                                </span>
                            </div>
                            <div style="display:flex; align-items:flex-start; gap:8px;">
                                <span style="width:6px; height:6px; border-radius:50%; background:#2563eb; flex-shrink:0; margin-top:6px;"></span>
                                <div style="flex:1; color:var(--color-ink-mute);">
                                    Menyelaraskan stok sistem dengan hitungan riil di rak gudang saat audit berkala. Selisih lebih (+) atau kurang (-) otomatis dibukukan ke pos <strong style="color:var(--color-primary);">Selisih Persediaan (Inventory Variance)</strong>.
                                </div>
                            </div>
                        </div>

                        <!-- Mode Item Masuk -->
                        <div x-show="adjustMode === 'masuk'">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; padding-bottom:6px; border-bottom:1px solid var(--color-hairline); flex-wrap:wrap;">
                                <div style="display:inline-flex; align-items:center; gap:7px; font-weight:700; font-size:12px; color:var(--color-ink);">
                                    <span style="width:22px; height:22px; border-radius:6px; background:rgba(16,185,129,0.12); color:#059669; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <i data-lucide="arrow-down-left" style="width:13px; height:13px;"></i>
                                    </span>
                                    <span>Penerimaan Masuk Manual</span>
                                </div>
                                <span class="badge-counter" style="background:rgba(16,185,129,0.08); color:#047857; border-color:rgba(16,185,129,0.22); font-size:10.5px; font-weight:700; padding:2px 8px;">
                                    Non-PO
                                </span>
                            </div>
                            <div style="display:flex; align-items:flex-start; gap:8px;">
                                <span style="width:6px; height:6px; border-radius:50%; background:#059669; flex-shrink:0; margin-top:6px;"></span>
                                <div style="flex:1; color:var(--color-ink-mute);">
                                    Menambah saldo stok untuk penerimaan insidental di luar PO Pembelian (misal: bonus supplier, sampel masuk, atau temuan fisik barang di rak).
                                </div>
                            </div>
                        </div>

                        <!-- Mode Item Keluar -->
                        <div x-show="adjustMode === 'keluar'">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; padding-bottom:6px; border-bottom:1px solid var(--color-hairline); flex-wrap:wrap;">
                                <div style="display:inline-flex; align-items:center; gap:7px; font-weight:700; font-size:12px; color:var(--color-ink);">
                                    <span style="width:22px; height:22px; border-radius:6px; background:rgba(245,158,11,0.12); color:#d97706; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <i data-lucide="arrow-up-right" style="width:13px; height:13px;"></i>
                                    </span>
                                    <span>Pengurangan Stok Administratif</span>
                                </div>
                                <span class="badge-counter" style="background:rgba(245,158,11,0.08); color:#b45309; border-color:rgba(245,158,11,0.22); font-size:10.5px; font-weight:700; padding:2px 8px;">
                                    Perhatian
                                </span>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <div style="display:flex; align-items:flex-start; gap:8px;">
                                    <span style="width:6px; height:6px; border-radius:50%; background:#d97706; flex-shrink:0; margin-top:6px;"></span>
                                    <div style="flex:1; color:var(--color-ink-mute);">
                                        Digunakan untuk koreksi pengeluaran administratif non-penjualan atau selisih nota.
                                    </div>
                                </div>
                                <div style="display:flex; align-items:flex-start; gap:8px; padding-top:6px; margin-top:2px; border-top:1px dashed var(--color-hairline); font-size:11.5px;">
                                    <span style="width:18px; height:18px; border-radius:5px; background:rgba(225,29,72,0.1); color:#e11d48; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px;">
                                        <i data-lucide="alert-triangle" style="width:12px; height:12px;"></i>
                                    </span>
                                    <div style="flex:1; color:var(--color-ink);">
                                        Jika barang keluar karena <strong>rusak, remuk, bocor kemasan, expired, atau sampel promosi</strong>: tutup modal ini dan gunakan tombol <span style="background:rgba(225,29,72,0.1); color:#be123c; padding:1px 6px; border-radius:4px; font-weight:700; border:1px solid rgba(225,29,72,0.2);">[Waste]</span> agar tercatat di pos <strong>Beban Kerusakan / Promosi</strong>.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Input: MODE OPNAME FISIK -->
                    <div x-show="adjustMode === 'opname'">
                        <label class="form-label font-semibold" style="margin-bottom:4px;">
                            <span>Hasil Hitung Fisik Nyata di Gudang *</span>
                        </label>
                        <div class="relative">
                            <input type="number" step="any" min="0" name="stok_fisik_baru" x-model="adjustPhysicalCount"
                                   @keydown="if (['-', 'e', 'E', '+'].includes($event.key)) $event.preventDefault()"
                                   @input="adjustPhysicalCount = $event.target.value.replace(/[^0-9.]/g, '')"
                                   @paste="setTimeout(() => { adjustPhysicalCount = adjustPhysicalCount.replace(/[^0-9.]/g, '') }, 10)"
                                   @wheel="$event.target.blur()"
                                   :required="adjustMode === 'opname'"
                                   placeholder="Contoh: 15"
                                   class="form-input font-mono" style="font-weight:700; height:38px; font-size:14px; padding-right:55px;">
                            <span class="font-mono text-xs font-semibold" 
                                   style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--color-ink-mute); pointer-events:none;"
                                   x-text="selectedItem.satuan_dasar || 'pcs'"></span>
                        </div>
                        <span class="text-[11.5px] text-slate-400 dark:text-slate-500 mt-1 block">
                            Masukkan jumlah riil yang dihitung di rak gudang (minimal 0). Selisih lebih (+) atau kurang (-) otomatis dihitung sistem.
                        </span>
                    </div>

                    <!-- Dynamic Input: MODE MASUK / KELUAR -->
                    <div x-show="adjustMode !== 'opname'">
                        <label class="form-label font-semibold" style="margin-bottom:4px;">
                            <span x-text="adjustMode === 'masuk' ? 'Jumlah Kuantitas Item Masuk (+) *' : 'Jumlah Kuantitas Item Keluar (-) *'"></span>
                        </label>
                        <div class="relative">
                            <input type="number" step="any" min="0.0001" name="kuantitas" x-model="adjustDeltaQty"
                                   @keydown="if (['-', 'e', 'E', '+'].includes($event.key)) $event.preventDefault()"
                                   @input="adjustDeltaQty = $event.target.value.replace(/[^0-9.]/g, '')"
                                   @paste="setTimeout(() => { adjustDeltaQty = adjustDeltaQty.replace(/[^0-9.]/g, '') }, 10)"
                                   @wheel="$event.target.blur()"
                                   :required="adjustMode !== 'opname'"
                                   :placeholder="adjustMode === 'masuk' ? 'Contoh: 5' : 'Contoh: 3'"
                                   class="form-input font-mono" style="font-weight:700; height:38px; font-size:14px; padding-right:55px;">
                            <span class="font-mono text-xs font-semibold" 
                                   style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--color-ink-mute); pointer-events:none;"
                                   x-text="selectedItem.satuan_dasar || 'pcs'"></span>
                        </div>
                        <span class="text-[11.5px] text-slate-400 dark:text-slate-500 mt-1 block"
                              x-text="adjustMode === 'masuk' ? 'Kuantitas ini akan ditambahkan ke saldo stok saat ini.' : 'Kuantitas ini akan dipotong dari saldo stok saat ini.'">
                        </span>
                    </div>

                    <!-- Live Calculation Preview Card -->
                    <div style="padding:12px 14px; background:var(--color-canvas-soft); border:1px solid var(--color-hairline); border-radius:10px; display:flex; flex-direction:column; gap:7px;">
                        <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--color-ink-mute);">
                            <span>Stok Sistem Saat Ini:</span>
                            <span class="font-mono font-semibold" style="color:var(--color-ink);" x-text="(selectedItem.stok_fisik_saat_ini || 0) + ' ' + (selectedItem.satuan_dasar || 'pcs')"></span>
                        </div>

                        <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--color-ink-mute);">
                            <span x-text="adjustMode === 'opname' ? 'Selisih Hasil Opname:' : (adjustMode === 'masuk' ? 'Mutasi Penambahan:' : 'Mutasi Pengurangan:')"></span>
                            <span class="font-mono font-bold"
                                  :style="getAdjustDiffStyle()"
                                  x-text="getAdjustDiffText()"></span>
                        </div>

                        <div style="height:1px; background:var(--color-hairline); margin:2px 0;"></div>

                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:12.5px; font-weight:700; color:var(--color-ink);">Stok Akhir Baru:</span>
                            <strong class="font-mono" style="font-size:16px; color:var(--color-primary);"
                                    x-text="getAdjustFinalStock() + ' ' + (selectedItem.satuan_dasar || 'pcs')"></strong>
                        </div>
                    </div>

                    <!-- Warning: Item Keluar Melebihi Stok -->
                    <div x-cloak x-show="adjustMode === 'keluar' && parseFloat(adjustDeltaQty || 0) > parseFloat(selectedItem.stok_fisik_saat_ini || 0)"
                         style="padding:8px 12px; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.25); border-radius:8px; font-size:12px; color:#dc2626; font-weight:600; display:flex; align-items:center; gap:6px;">
                         <i data-lucide="alert-triangle" style="width:14px; height:14px; flex-shrink:0;"></i>
                         <span>Jumlah item keluar melebihi sisa stok fisik saat ini! Stok tidak boleh minus.</span>
                    </div>

                    <!-- Catatan / Alasan -->
                    <div>
                        <label class="form-label font-semibold" style="margin-bottom:4px;">Catatan / Alasan *</label>
                        <input type="text" name="alasan" required x-model="adjustReason"
                               :placeholder="adjustMode === 'opname' ? 'Contoh: Hitung fisik opname rak gudang A' : (adjustMode === 'masuk' ? 'Contoh: Bonus supplier / Koreksi stok masuk' : 'Contoh: Koreksi selisih hitung nota / Penyesuaian administratif')"
                               class="form-input" style="height:38px; font-size:13px;">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showAdjustModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                    <button type="submit" class="btn w-full sm:w-auto"
                            :class="adjustMode === 'opname' ? 'btn-primary' : (adjustMode === 'masuk' ? 'btn-success' : 'btn-danger-solid')"
                            :disabled="isAdjustSubmitDisabled()"
                            :style="isAdjustSubmitDisabled() ? 'opacity:0.5; cursor:not-allowed;' : ''"
                            style="display:inline-flex; align-items:center; justify-content:center; gap:6px; font-weight:600; color:#ffffff !important;">
                        <i :data-lucide="adjustMode === 'opname' ? 'save' : (adjustMode === 'masuk' ? 'plus-circle' : 'minus-circle')" style="width:16px; height:16px; color:#ffffff !important;"></i>
                        <span x-text="adjustMode === 'opname' ? 'Simpan Opname Fisik' : (adjustMode === 'masuk' ? 'Simpan Item Masuk' : 'Simpan Item Keluar')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL 2: WASTE / BARANG RUSAK                                             -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showWasteModal" x-cloak class="modal-backdrop" @click="showWasteModal = false">
        <div class="modal-box" style="max-width:520px;" @click.stop>
            <div class="modal-handle"><div class="modal-handle-bar"></div></div>
            <div class="modal-header">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(239,68,68,0.12);color:#dc2626;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="trash-2" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="modal-title" style="color:#b91c1c;">Catat Barang Rusak / Waste</div>
                        <div style="font-size:12px; font-family:var(--font-mono); color:#dc2626; margin-top:1px;"
                             x-text="(selectedItem.kode_sku || '') + ' — ' + (selectedItem.nama_item || '')"></div>
                    </div>
                </div>
                <button type="button" @click="showWasteModal = false" class="modal-close-x" title="Tutup Modal">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/inventory/waste') ?>" method="POST" data-action-text="Mencatat barang rusak / waste...">
                <div class="modal-body custom-scrollbar space-y-3.5">
                    <?= \App\Helpers\CSRF::field() ?>
                    <input type="hidden" name="item_id" :value="selectedItem.id">

                    <div style="padding:10px 12px; background:#fef2f2; border:1px solid #fee2e2; border-radius:10px; font-size:12px; color:#991b1b; display:flex; align-items:center; justify-content:space-between;">
                        <span>Sisa Stok Fisik Saat Ini:</span>
                        <strong class="font-mono" style="font-size:14px;" x-text="(selectedItem.stok_fisik_saat_ini || 0) + ' ' + (selectedItem.satuan_dasar || 'pcs')"></strong>
                    </div>

                    <!-- Info Panduan Peruntukan Waste & Laporan Keuangan (Multi-Tone Responsive Card) -->
                    <div style="padding:12px 14px; background:var(--color-canvas-soft); border:1px solid var(--color-hairline); border-left:3.5px solid #e11d48; border-radius:12px; font-size:12px; line-height:1.55; color:var(--color-ink);">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; padding-bottom:6px; border-bottom:1px solid var(--color-hairline); flex-wrap:wrap;">
                            <div style="display:inline-flex; align-items:center; gap:7px; font-weight:700; font-size:12px; color:var(--color-ink);">
                                <span style="width:22px; height:22px; border-radius:6px; background:rgba(225,29,72,0.12); color:#e11d48; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <i data-lucide="scale" style="width:13px; height:13px;"></i>
                                </span>
                                <span>Panduan Peruntukan &amp; Pembukuan</span>
                            </div>
                            <span class="badge-counter" style="background:rgba(225,29,72,0.08); color:#be123c; border-color:rgba(225,29,72,0.22); font-size:10.5px; font-weight:700; padding:2px 8px;">
                                Akuntansi
                            </span>
                        </div>

                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <div style="display:flex; align-items:flex-start; gap:8px;">
                                <span style="width:6px; height:6px; border-radius:50%; background:#e11d48; flex-shrink:0; margin-top:6px;"></span>
                                <div style="flex:1; color:var(--color-ink);">
                                    Gunakan tombol ini <strong>hanya jika wujud fisik barang nyata ada &amp; diketahui penyebab rusaknya/dibuangnya</strong> (kemasan bocor, remuk, kadaluarsa, atau sampel promosi uji rasa). Mutasi dibukukan resmi ke pos <strong style="color:#be123c;">Beban Kerusakan / Beban Promosi</strong>.
                                </div>
                            </div>

                            <div style="display:flex; align-items:flex-start; gap:8px; padding-top:6px; margin-top:2px; border-top:1px dashed var(--color-hairline); font-size:11.5px; color:var(--color-ink-mute);">
                                <span style="width:18px; height:18px; border-radius:5px; background:rgba(37,99,235,0.08); color:#2563eb; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px;">
                                    <i data-lucide="info" style="width:12px; height:12px;"></i>
                                </span>
                                <div style="flex:1;">
                                    Jika fisik barang <strong>tidak ada / selisih rak</strong> saat audit berkala tanpa bukti kerusakan fisik riil, gunakan fitur <strong style="color:var(--color-primary);">Opname Fisik</strong> agar dibukukan sebagai <strong>Selisih Persediaan (Shrinkage)</strong>.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="form-label font-semibold">Kategori Kerusakan / Waste *</label>
                        <select name="kategori_waste" class="form-select font-semibold" required>
                            <option value="kemasan_rusak">Kemasan Rusak / Gagal Segel</option>
                            <option value="remuk_hancur">Produk Remuk / Hancur</option>
                            <option value="expired_kadaluarsa">Kadaluarsa / Expired</option>
                            <option value="sampel_promosi">Sampel Uji Rasa / Promosi</option>
                            <option value="lainnya">Lain-lain</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label font-semibold">Jumlah Kuantitas Rusak / Dibuang *</label>
                        <input type="number" step="any" name="kuantitas" x-model="wasteQty" @wheel="$event.target.blur()" required min="0.0001" :max="selectedItem.stok_fisik_saat_ini" placeholder="1"
                               class="form-input font-mono" style="font-weight:700;">
                    </div>

                    <!-- Live Calculation Preview for Waste -->
                    <div style="padding:10px 14px; background:#fef2f2; border:1px dashed #f87171; border-radius:10px; display:flex; flex-direction:column; gap:4px;">
                        <div style="display:flex; justify-content:space-between; font-size:12px; color:#991b1b;">
                            <span>Sisa Stok Sebelum Waste:</span>
                            <span class="font-mono" x-text="(selectedItem.stok_fisik_saat_ini || 0) + ' ' + (selectedItem.satuan_dasar || 'pcs')"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:12px; color:#991b1b;">
                            <span>Pemotongan Waste (-):</span>
                            <span class="font-mono" style="font-weight:700; color:#dc2626;"
                                  x-text="'-' + (wasteQty || 0) + ' ' + (selectedItem.satuan_dasar || 'pcs')"></span>
                        </div>
                        <div style="height:1px; background:#fecaca; margin:2px 0;"></div>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:12.5px; font-weight:700; color:#991b1b;">Estimasi Sisa Stok Akhir:</span>
                            <strong class="font-mono" style="font-size:14.5px; color:#b91c1c;"
                                    x-text="calculateWastePreview() + ' ' + (selectedItem.satuan_dasar || 'pcs')"></strong>
                        </div>
                    </div>

                    <div x-show="parseFloat(wasteQty) > parseFloat(selectedItem.stok_fisik_saat_ini || 0)"
                         style="padding:8px 12px; background:#fee2e2; border:1px solid #fecaca; border-radius:8px; font-size:12px; color:#b91c1c; font-weight:600; display:flex; align-items:center; gap:6px;">
                        <i data-lucide="alert-triangle" style="width:14px; height:14px; flex-shrink:0;"></i>
                        <span>Jumlah waste melebihi sisa stok fisik saat ini!</span>
                    </div>

                    <div>
                        <label class="form-label font-semibold">Keterangan / Kronologi *</label>
                        <input type="text" name="keterangan" required placeholder="Contoh: Plastik bocor saat packing"
                               class="form-input">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showWasteModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                    <button type="submit" class="btn btn-danger w-full sm:w-auto"
                            :disabled="parseFloat(wasteQty) <= 0 || parseFloat(wasteQty) > parseFloat(selectedItem.stok_fisik_saat_ini || 0)"
                            :style="(parseFloat(wasteQty) <= 0 || parseFloat(wasteQty) > parseFloat(selectedItem.stok_fisik_saat_ini || 0)) ? 'opacity:0.5; cursor:not-allowed;' : ''"
                            style="display:inline-flex;align-items:center;justify-content:center;gap:6px;background:#dc2626;color:#fff;">
                        <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                        <span>Potong Stok Waste</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL 3: KARTU STOK & RIWAYAT MUTASI TERAKHIR                             -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showHistoryModal" x-cloak class="modal-backdrop" @click="showHistoryModal = false">
        <div class="modal-box modal-box-lg" style="max-width:760px;" @click.stop>
            <div class="modal-handle"><div class="modal-handle-bar"></div></div>
            <div class="modal-header">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(37,99,235,0.12);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="activity" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="modal-title">Kartu Stok &amp; Riwayat Mutasi</div>
                        <div style="font-size:12px; font-family:var(--font-mono); color:var(--color-primary); margin-top:1px;"
                             x-text="(selectedItem.kode_sku || '') + ' — ' + (selectedItem.nama_item || '')"></div>
                    </div>
                </div>
                <button type="button" @click="showHistoryModal = false" class="modal-close-x" title="Tutup Modal">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <!-- Loading State -->
            <div x-show="historyLoading" style="padding:36px; text-align:center; color:var(--color-ink-mute);">
                <i data-lucide="loader" class="spin" style="width:24px; height:24px; margin:0 auto 8px auto;"></i>
                <div>Memuat riwayat pergerakan stok...</div>
            </div>

            <!-- Content State -->
            <div x-show="!historyLoading">
                <div class="modal-body custom-scrollbar space-y-3.5">
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:var(--color-canvas-soft); border-radius:12px; border:1px solid var(--color-hairline);">
                        <div>
                            <div style="font-size:11px; color:var(--color-ink-mute); text-transform:uppercase; letter-spacing:0.04em;">Stok Fisik Gudang Saat Ini</div>
                            <div style="font-size:18px; font-weight:800; font-family:var(--font-mono); color:var(--color-ink);"
                                 x-text="formatQty(selectedItem.stok_fisik_saat_ini) + ' ' + (selectedItem.satuan_dasar || 'pcs')"></div>
                        </div>
                        <div style="text-align:right;">
                            <span class="badge badge-mono" x-text="selectedItem.barcode || selectedItem.barcode_universal || 'No Barcode'"></span>
                        </div>
                    </div>

                    <div class="table-wrapper" style="max-height:360px; overflow-y:auto; border-radius:10px;">
                        <table class="data-table" style="font-size:12px;">
                            <thead>
                                <tr>
                                    <th>Waktu &amp; Tanggal</th>
                                    <th>Tipe Mutasi</th>
                                    <th style="text-align:right;">Perubahan</th>
                                    <th style="text-align:right;">Stok Akhir</th>
                                    <th>Keterangan / Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="itemHistoryList.length === 0">
                                    <tr>
                                        <td colspan="5" style="text-align:center; padding:24px; color:var(--color-ink-mute);">
                                            Belum ada riwayat mutasi tercatat untuk produk ini.
                                        </td>
                                    </tr>
                                </template>
                                <template x-for="log in itemHistoryList" :key="log.id">
                                    <tr>
                                        <td style="font-family:var(--font-mono); font-size:11px; color:var(--color-ink-mute);" x-text="formatDate(log.dibuat_pada)"></td>
                                        <td>
                                            <span class="badge" :class="getMutationBadgeClass(log.tipe_mutasi)" x-text="formatMutationType(log.tipe_mutasi)"></span>
                                        </td>
                                        <td style="text-align:right; font-family:var(--font-mono); font-weight:700;"
                                            :style="isStockIn(log.tipe_mutasi) ? 'color:#059669;' : 'color:#dc2626;'"
                                            x-text="(isStockIn(log.tipe_mutasi) ? '+' : '-') + formatQty(log.jumlah_perubahan) + ' pcs'"></td>
                                        <td style="text-align:right; font-family:var(--font-mono); color:var(--color-ink);" x-text="formatQty(log.stok_sesudah) + ' pcs'"></td>
                                        <td>
                                            <div style="font-size:11.5px; color:var(--color-ink);" x-text="log.keterangan || '—'"></div>
                                            <div x-show="log.nama_user" style="font-size:10px; color:var(--color-ink-mute); font-style:italic;" x-text="'Oleh: ' + log.nama_user"></div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showHistoryModal = false" class="btn btn-secondary modal-btn-cancel-desktop w-full sm:w-auto">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    </template>

</div>

<script>
function inventoryApp() {
    return {
        items: <?= json_encode($items) ?>,
        activeTab: 'all',
        searchQuery: '',
        selectedGroup: '',
        filterStatus: 'all',
        perPage: '25',
        currentPage: 1,

        isTableLoading: false,
        _tableTimer: null,

        showAdjustModal: false,
        showWasteModal: false,
        showHistoryModal: false,
        historyLoading: false,
        itemHistoryList: [],
        selectedItem: {},
        adjustMode: 'opname',
        adjustPhysicalCount: '',
        adjustDeltaQty: '',
        adjustReason: '',
        adjustQty: '',
        adjustType: 'opname_lebih',
        wasteQty: '',

        get skeletonRows() {
            if (this.perPage === 'all') return 6;
            const p = parseInt(this.perPage) || 6;
            return Math.min(p, 8);
        },

        get countBarangJadi() {
            return this.items.filter(i => (i.tipe_item || 'barang_jadi') === 'barang_jadi').length;
        },

        get countBahanMentah() {
            return this.items.filter(i => i.tipe_item === 'bahan_mentah').length;
        },

        get countBahanKemas() {
            return this.items.filter(i => i.tipe_item === 'bahan_kemas').length;
        },

        get hasActiveFilter() {
            return this.activeTab !== 'all' || this.searchQuery.trim() !== '' || this.selectedGroup !== '' || this.filterStatus !== 'all';
        },

        get kpiTotalSku() {
            return this.filteredItems.length;
        },

        get kpiTotalFisik() {
            return this.filteredItems.reduce((acc, item) => acc + (parseFloat(item.stok_fisik_saat_ini) || 0), 0);
        },

        get kpiTotalValuasi() {
            return this.filteredItems.reduce((acc, item) => {
                const stok = parseFloat(item.stok_fisik_saat_ini) || 0;
                const hpp = parseFloat(item.harga_pokok_pembelian) || 0;
                return acc + (stok * hpp);
            }, 0);
        },

        get kpiTotalMenipis() {
            return this.filteredItems.filter(item => {
                const s = parseFloat(item.stok_fisik_saat_ini) || 0;
                const min = parseFloat(item.stok_minimum_peringatan) || 10;
                return s > 0 && s <= min;
            }).length;
        },

        get kpiTotalKosong() {
            return this.filteredItems.filter(item => (parseFloat(item.stok_fisik_saat_ini) || 0) <= 0).length;
        },

        init() {
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });

            // Global shortcut: tekan '/' untuk fokus ke live search, Escape untuk reset/unfocus
            window.addEventListener('keydown', (e) => {
                if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
                    e.preventDefault();
                    this.$refs.searchInput?.focus();
                    this.$refs.searchInput?.select();
                }
                if (e.key === 'Escape' && document.activeElement === this.$refs.searchInput) {
                    if (this.searchQuery) {
                        this.clearSearch();
                    } else {
                        this.$refs.searchInput?.blur();
                    }
                }
            });
        },

        setTab(tab) {
            if (this.activeTab === tab) return;
            this.activeTab = tab;
            // Jika pindah ke bahan, reset grup kemasan karena bahan tidak terikat grup kemasan
            if (tab === 'bahan_mentah' || tab === 'bahan_kemas') {
                this.selectedGroup = '';
            }
            this.applyFilterWithSkeleton();
        },

        onSearchInput() {
            this.applyFilterWithSkeleton(160);
        },

        clearSearch() {
            this.searchQuery = '';
            this.applyFilterWithSkeleton(120);
            this.$nextTick(() => {
                this.$refs.searchInput?.focus();
            });
        },

        resetAllFilters() {
            this.activeTab = 'all';
            this.searchQuery = '';
            this.selectedGroup = '';
            this.filterStatus = 'all';
            this.applyFilterWithSkeleton(140);
        },

        applyFilterWithSkeleton(duration = 180) {
            this.isTableLoading = true;
            this.currentPage = 1;

            if (this._tableTimer) {
                clearTimeout(this._tableTimer);
            }

            this._tableTimer = setTimeout(() => {
                this.isTableLoading = false;
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            }, duration);
        },

        get filteredItems() {
            let res = this.items;

            // 1. Filter Tab Kategori Tipe Item
            if (this.activeTab !== 'all') {
                res = res.filter(item => {
                    const tipe = item.tipe_item || 'barang_jadi';
                    return tipe === this.activeTab;
                });
            }

            // 2. Enhanced Search: Multi-keyword / Tokenized Matching
            if (this.searchQuery.trim()) {
                const rawTokens = this.searchQuery.toLowerCase().trim().split(/\s+/).filter(Boolean);
                res = res.filter(item => {
                    const nama = (item.nama_item || '').toLowerCase();
                    const sku = (item.kode_sku || '').toLowerCase();
                    const bc = (item.barcode || '').toLowerCase();
                    const bcu = (item.barcode_universal || '').toLowerCase();
                    const grup = (item.nama_grup || '').toLowerCase();
                    const grupKode = (item.kode_grup || '').toLowerCase();
                    const satuan = (item.satuan_dasar || '').toLowerCase();
                    const tipe = (item.tipe_item || 'barang_jadi').toLowerCase();

                    let alias = '';
                    if (tipe === 'barang_jadi') alias = 'barang jadi finish goods produk kemasan siap jual';
                    else if (tipe === 'bahan_mentah') alias = 'bahan mentah curah baku kilo bal bumbu';
                    else if (tipe === 'bahan_kemas') alias = 'bahan kemas plastik standing pouch kardus karton stiker label kemasan';

                    const searchBlob = `${nama} ${sku} ${bc} ${bcu} ${grup} ${grupKode} ${satuan} ${tipe} ${alias}`;

                    return rawTokens.every(token => searchBlob.includes(token));
                });
            }

            // 3. Filter Group Kemasan
            if (this.selectedGroup) {
                res = res.filter(item => item.kode_grup === this.selectedGroup);
            }

            // 4. Filter Stock Status sesuai stok_minimum_peringatan tiap produk
            if (this.filterStatus === 'available') {
                res = res.filter(item => {
                    const s = parseFloat(item.stok_fisik_saat_ini) || 0;
                    const min = parseFloat(item.stok_minimum_peringatan) || 10;
                    return s > min;
                });
            } else if (this.filterStatus === 'low') {
                res = res.filter(item => {
                    const s = parseFloat(item.stok_fisik_saat_ini) || 0;
                    const min = parseFloat(item.stok_minimum_peringatan) || 10;
                    return s > 0 && s <= min;
                });
            } else if (this.filterStatus === 'empty') {
                res = res.filter(item => (parseFloat(item.stok_fisik_saat_ini) || 0) <= 0);
            }

            return res;
        },

        get totalPages() {
            if (this.perPage === 'all') return 1;
            const size = parseInt(this.perPage) || 25;
            return Math.max(1, Math.ceil(this.filteredItems.length / size));
        },

        get paginatedItems() {
            if (this.perPage === 'all') return this.filteredItems;
            const size = parseInt(this.perPage) || 25;
            const start = (this.currentPage - 1) * size;
            return this.filteredItems.slice(start, start + size);
        },

        getStartRowIndex() {
            if (this.perPage === 'all') return 0;
            return (this.currentPage - 1) * parseInt(this.perPage);
        },

        goToPage(targetPage) {
            if (targetPage < 1 || targetPage > this.totalPages || targetPage === this.currentPage) return;

            const paginationEl = this.$refs.paginationBar;
            const tableEl = this.$refs.tableWrapper;

            // Pre-emptively lock current height so DOM cannot collapse during Alpine render
            if (tableEl && tableEl.offsetHeight > 0) {
                tableEl.style.minHeight = tableEl.offsetHeight + 'px';
            }

            const paginationTopBefore = paginationEl ? paginationEl.getBoundingClientRect().top : null;

            this.isTableLoading = true;
            this.currentPage = targetPage;

            if (this._tableTimer) clearTimeout(this._tableTimer);
            this._tableTimer = setTimeout(() => {
                this.isTableLoading = false;
                this.$nextTick(() => {
                    if (tableEl) {
                        tableEl.style.minHeight = '';
                    }
                    if (window.lucide) lucide.createIcons();

                    if (paginationTopBefore !== null && paginationEl) {
                        const paginationTopAfter = paginationEl.getBoundingClientRect().top;
                        const delta = paginationTopAfter - paginationTopBefore;

                        if (Math.abs(delta) >= 1) {
                            const scrollContainer = document.querySelector('.app-content');
                            if (scrollContainer && (scrollContainer.scrollHeight > scrollContainer.clientHeight)) {
                                scrollContainer.scrollBy({ top: delta, behavior: 'instant' });
                            } else {
                                window.scrollBy({ top: delta, behavior: 'instant' });
                            }
                        }
                    }
                });
            }, 130);
        },

        prevPage(evt) {
            if (this.currentPage <= 1) return;
            if (evt && evt.currentTarget) evt.currentTarget.blur();
            this.goToPage(this.currentPage - 1);
        },

        nextPage(evt) {
            if (this.currentPage >= this.totalPages) return;
            if (evt && evt.currentTarget) evt.currentTarget.blur();
            this.goToPage(this.currentPage + 1);
        },

        setAdjustMode(mode) {
            this.adjustMode = mode;
            this.adjustDeltaQty = '';
            this.adjustReason = '';
            if (mode === 'opname') {
                const cur = parseFloat(this.selectedItem.stok_fisik_saat_ini) || 0;
                this.adjustPhysicalCount = cur % 1 === 0 ? cur.toString() : cur.toFixed(2);
            } else {
                this.adjustPhysicalCount = '';
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        openAdjust(item) {
            this.selectedItem = item;
            this.setAdjustMode('opname');
            this.showAdjustModal = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        getAdjustDiffText() {
            const cur = parseFloat(this.selectedItem.stok_fisik_saat_ini) || 0;
            const unit = this.selectedItem.satuan_dasar || 'pcs';

            if (this.adjustMode === 'opname') {
                if (this.adjustPhysicalCount === '' || isNaN(parseFloat(this.adjustPhysicalCount))) {
                    return '0 ' + unit + ' (Belum diisi)';
                }
                const target = parseFloat(this.adjustPhysicalCount) || 0;
                const diff = target - cur;
                if (Math.abs(diff) < 0.0001) {
                    return '0 ' + unit + ' (Sesuai / Pas)';
                }
                const formatted = diff % 1 === 0 ? Math.abs(diff).toString() : Math.abs(diff).toFixed(2);
                return (diff > 0 ? '+' : '-') + formatted + ' ' + unit + (diff > 0 ? ' (Surplus / Lebih)' : ' (Kurang / Selisih)');
            } else if (this.adjustMode === 'masuk') {
                const raw = parseFloat(this.adjustDeltaQty);
                if (isNaN(raw) || raw <= 0) return '+0 ' + unit;
                const formatted = raw % 1 === 0 ? raw.toString() : raw.toFixed(2);
                return '+' + formatted + ' ' + unit;
            } else {
                const raw = parseFloat(this.adjustDeltaQty);
                if (isNaN(raw) || raw <= 0) return '-0 ' + unit;
                const formatted = raw % 1 === 0 ? raw.toString() : raw.toFixed(2);
                return '-' + formatted + ' ' + unit;
            }
        },

        getAdjustDiffStyle() {
            if (this.adjustMode === 'opname') {
                const cur = parseFloat(this.selectedItem.stok_fisik_saat_ini) || 0;
                const target = parseFloat(this.adjustPhysicalCount);
                if (isNaN(target) || Math.abs(target - cur) < 0.0001) return 'color:var(--color-ink-mute); font-weight:600;';
                return target > cur ? 'color:#059669; font-weight:700;' : 'color:#dc2626; font-weight:700;';
            } else if (this.adjustMode === 'masuk') {
                const raw = parseFloat(this.adjustDeltaQty);
                if (isNaN(raw) || raw <= 0) return 'color:var(--color-ink-mute); font-weight:600;';
                return 'color:#059669; font-weight:700;';
            } else {
                const raw = parseFloat(this.adjustDeltaQty);
                if (isNaN(raw) || raw <= 0) return 'color:var(--color-ink-mute); font-weight:600;';
                return 'color:#dc2626; font-weight:700;';
            }
        },

        getAdjustFinalStock() {
            const cur = parseFloat(this.selectedItem.stok_fisik_saat_ini) || 0;

            if (this.adjustMode === 'opname') {
                if (this.adjustPhysicalCount === '' || isNaN(parseFloat(this.adjustPhysicalCount))) {
                    return cur % 1 === 0 ? cur.toString() : cur.toFixed(2);
                }
                const target = parseFloat(this.adjustPhysicalCount) || 0;
                return target % 1 === 0 ? target.toString() : target.toFixed(2);
            } else if (this.adjustMode === 'masuk') {
                const raw = parseFloat(this.adjustDeltaQty);
                if (isNaN(raw) || raw <= 0) return cur % 1 === 0 ? cur.toString() : cur.toFixed(2);
                const res = cur + raw;
                return res % 1 === 0 ? res.toString() : res.toFixed(2);
            } else {
                const raw = parseFloat(this.adjustDeltaQty);
                if (isNaN(raw) || raw <= 0) return cur % 1 === 0 ? cur.toString() : cur.toFixed(2);
                const res = Math.max(0, cur - raw);
                return res % 1 === 0 ? res.toString() : res.toFixed(2);
            }
        },

        isAdjustSubmitDisabled() {
            const cur = parseFloat(this.selectedItem.stok_fisik_saat_ini) || 0;
            if (this.adjustMode === 'opname') {
                if (this.adjustPhysicalCount === '' || isNaN(parseFloat(this.adjustPhysicalCount))) return true;
                return parseFloat(this.adjustPhysicalCount) < 0;
            } else if (this.adjustMode === 'masuk') {
                const q = parseFloat(this.adjustDeltaQty);
                return isNaN(q) || q <= 0;
            } else {
                const q = parseFloat(this.adjustDeltaQty);
                return isNaN(q) || q <= 0 || q > cur;
            }
        },

        openWaste(item) {
            this.selectedItem = item;
            this.wasteQty = '';
            this.showWasteModal = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        calculateWastePreview() {
            const cur = parseFloat(this.selectedItem.stok_fisik_saat_ini) || 0;
            const q = parseFloat(this.wasteQty) || 0;
            const res = Math.max(0, cur - q);
            return res % 1 === 0 ? res.toString() : res.toFixed(2);
        },

        async openHistory(item) {
            this.selectedItem = item;
            this.showHistoryModal = true;
            this.historyLoading = true;
            this.itemHistoryList = [];
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });

            try {
                const res = await fetch('<?= Router::url('/inventory/api/item-history') ?>?item_id=' + encodeURIComponent(item.id));
                const data = await res.json();
                if (data.success) {
                    this.itemHistoryList = data.history || [];
                }
            } catch (err) {
                console.error("Gagal memuat kartu stok:", err);
            } finally {
                this.historyLoading = false;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            }
        },

        formatQty(val) {
            const num = parseFloat(val);
            if (isNaN(num)) return '0';
            return num % 1 === 0 ? num.toString() : num.toFixed(2);
        },

        formatBarcode(val) {
            if (!val) return '—';
            val = String(val).trim();
            if (!val || val === 'null' || val === '—') return '—';
            if (/^[0-9]+\.[0-9]+[eE]\+?[0-9]+$/i.test(val)) {
                try {
                    const num = Number(val);
                    if (!isNaN(num)) return num.toLocaleString('fullwide', { useGrouping: false });
                } catch (e) {}
            }
            if (/^[0-9]+\.0+$/.test(val)) {
                return val.split('.')[0];
            }
            return val;
        },

        isStockIn(type) {
            return ['produksi_masuk', 'pembelian_masuk', 'penyesuaian_opname_tambah', 'retur_pelanggan_masuk', 'konsinyasi_retur_masuk'].includes(type);
        },

        getMutationBadgeClass(type) {
            if (this.isStockIn(type)) return 'badge-success';
            if (['item_keluar_waste', 'konsinyasi_retur_rusak'].includes(type)) return 'badge-danger';
            return 'badge-warning';
        },

        formatMutationType(type) {
            const map = {
                'produksi_masuk': 'Produksi Masuk',
                'pembelian_masuk': 'Pembelian Vendor',
                'penyesuaian_opname_tambah': 'Opname (+ Masuk)',
                'penyesuaian_opname_kurang': 'Opname (- Keluar)',
                'penjualan_keluar': 'Penjualan Keluar',
                'konsinyasi_keluar': 'Titip Konsinyasi',
                'konsinyasi_retur_masuk': 'Retur Konsinyasi',
                'konsinyasi_retur_rusak': 'Retur Rusak Konsinyasi',
                'item_keluar_waste': 'Waste / Rusak',
                'bahan_terpakai_produksi': 'Pemakaian Bahan'
            };
            return map[type] || type;
        },

        formatDate(dtStr) {
            if (!dtStr) return '—';
            const d = new Date(dtStr);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },

        formatRupiah(val) {
            const num = parseFloat(val) || 0;
            return 'Rp ' + Math.round(num).toLocaleString('id-ID');
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

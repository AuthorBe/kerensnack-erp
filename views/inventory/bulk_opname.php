<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="bulkOpnameApp()" class="space-y-5">
    <style>
        /* Hilangkan spinner number bawaan browser & cegah gangguan scroll mouse */
        input[data-col="physical"]::-webkit-outer-spin-button,
        input[data-col="physical"]::-webkit-inner-spin-button,
        input[data-col="diff"]::-webkit-outer-spin-button,
        input[data-col="diff"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none !important;
            margin: 0 !important;
        }
        input[data-col="physical"],
        input[data-col="diff"],
        input[type="number"] {
            -moz-appearance: textfield !important;
        }
    </style>

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER                                                            -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-emerald" style="flex-shrink:0;">
                <i data-lucide="layers"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#10b981;"></span>
                    <span>Modul Inventaris &amp; Gudang</span>
                </div>
                <h1 class="page-title" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= $pageTitle ?? 'Bulk Opname Stok Gudang' ?>
                </h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Penyesuaian Massal Stok Fisik &amp; Audit Mutasi Buku Besar Gudang' ?></p>
            </div>
        </div>
        <div class="page-header-actions" style="display:flex; gap:8px; align-items:center; flex-shrink:0;">
            <a href="<?= Router::url('/inventory') ?>" @click.prevent="navigateAway('<?= Router::url('/inventory') ?>')" class="btn btn-secondary" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="arrow-left" style="width:15px; height:15px;"></i>
                <span>Kembali ke Stok</span>
            </a>
            <a href="<?= Router::url('/inventory/opname/history') ?>" @click.prevent="navigateAway('<?= Router::url('/inventory/opname/history') ?>')" class="btn btn-secondary" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="history" style="width:15px; height:15px;"></i>
                <span>Riwayat Dokumen</span>
            </a>
            <a href="<?= Router::url('/guide#bab-inventori-opname') ?>" target="_blank" class="btn btn-ghost" style="height:38px; font-weight:700; color:var(--color-primary); background:rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.25); display:inline-flex; align-items:center; gap:6px; text-decoration:none;" title="Buka Panduan Bulk Opname di Tab Baru">
                <i data-lucide="book-open" style="width:15px; height:15px;"></i>
                <span>Panduan</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 1.5 DRAFT RECOVERY BANNER (Auto-Saved Session)                            -->
    <!-- ========================================================================= -->
    <template x-if="hasDraft && !draftDismissed">
        <div class="card" style="padding:14px 18px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:14px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:36px; height:36px; border-radius:10px; background:#dbeafe; display:flex; align-items:center; justify-content:center; color:#2563eb; flex-shrink:0;">
                    <i data-lucide="history" style="width:20px; height:20px;"></i>
                </div>
                <div>
                    <div style="font-size:13.5px; font-weight:700; color:#1e40af;">
                        Ditemukan Draf Opname Tersimpan Otomatis
                    </div>
                    <div style="font-size:12px; color:#3b82f6; margin-top:2px;">
                        Tersimpan pada <strong x-text="draftSavedAt"></strong> berisi <strong x-text="draftItemCount + ' item'"></strong> yang telah disesuaikan. Apakah ingin memulihkan draf ini?
                    </div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <button type="button" @click="restoreDraft()" class="btn btn-primary btn-sm" style="background:#2563eb; border-color:#2563eb; height:34px; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                    <i data-lucide="rotate-ccw" style="width:14px; height:14px;"></i>
                    <span>Pulihkan Draf</span>
                </button>
                <button type="button" @click="confirmClearDraft()" class="btn btn-ghost btn-sm" style="color:#64748b; height:34px;">
                    Buang Draf
                </button>
            </div>
        </div>
    </template>

    <!-- ========================================================================= -->
    <!-- 2. METADATA DOKUMEN & PENGATURAN SESI                                     -->
    <!-- ========================================================================= -->
    <div class="card" style="padding:16px 20px; background:var(--color-surface); border:1px solid var(--color-hairline); border-radius:16px;">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:14px; align-items:flex-end;">
            <!-- Tanggal Opname -->
            <div style="max-width:220px;">
                <label class="form-label font-semibold" style="font-size:12px; margin-bottom:4px;">Tanggal Opname *</label>
                <input type="date" x-model="formTanggal" @input="scheduleAutoSave()" class="form-input" style="height:38px; font-weight:700;">
            </div>

            <!-- Catatan Umum Dokumen -->
            <div style="flex:1;">
                <label class="form-label font-semibold" style="font-size:12px; margin-bottom:4px;">Keterangan / Catatan Sesi Opname *</label>
                <input type="text" x-model="formCatatan" @input="scheduleAutoSave()" placeholder="Contoh: Opname Fisik Rutin Akhir Bulan Gudang Pusat" class="form-input" style="height:38px;">
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. SUMMARY METRIC CARDS & ACTIONS (Clean Flow - No Sticky Gap)            -->
    <!-- ========================================================================= -->
    <div class="card bulk-summary-card">
        <!-- Metric Items: Horizontal Bar on Desktop, 2x2 Grid on Mobile -->
        <div class="bulk-summary-metrics">
            <!-- 1. Total Produk -->
            <div class="bulk-metric-item">
                <span class="bulk-metric-label">Total Produk</span>
                <div class="bulk-metric-value" x-text="items.length + ' SKU'"></div>
            </div>

            <div class="bulk-metric-divider"></div>

            <!-- 2. Item Disesuaikan -->
            <div class="bulk-metric-item">
                <span class="bulk-metric-label">Item Disesuaikan</span>
                <div class="bulk-metric-value"
                     :style="totalModified > 0 ? 'color:var(--color-primary);' : 'color:var(--color-ink-mute);'"
                     x-text="totalModified + ' Item'"></div>
            </div>

            <div class="bulk-metric-divider"></div>

            <!-- 3. Stok Masuk (+) -->
            <div class="bulk-metric-item">
                <span class="bulk-metric-label" style="color:#047857;">Stok Masuk (+)</span>
                <div class="bulk-metric-value" style="color:#059669;" x-text="'+' + formatQty(totalQtyIn) + ' pcs'"></div>
            </div>

            <div class="bulk-metric-divider"></div>

            <!-- 4. Stok Keluar (-) -->
            <div class="bulk-metric-item">
                <span class="bulk-metric-label" style="color:#b91c1c;">Stok Keluar (-)</span>
                <div class="bulk-metric-value" style="color:#dc2626;" x-text="'-' + formatQty(totalQtyOut) + ' pcs'"></div>
            </div>
        </div>

        <!-- Actions: Sits gracefully on the right on Desktop, full-width thumb reachable on Mobile -->
        <div class="bulk-summary-actions">
            <button type="button" @click="resetAllRows()" x-show="totalModified > 0"
                    class="btn btn-ghost btn-sm bulk-btn-reset"
                    style="color:#dc2626; border:1px solid #fecaca; height:38px;">
                <i data-lucide="rotate-ccw" style="width:14px; height:14px;"></i>
                <span>Reset (<span x-text="totalModified"></span>)</span>
            </button>

            <button type="button" @click="openConfirmModal()"
                    :style="totalModified === 0 ? 'opacity:0.5; cursor:not-allowed;' : ''"
                    class="btn btn-primary bulk-btn-submit"
                    style="height:38px; padding:0 20px; font-weight:700;">
                <i data-lucide="check-circle-2" style="width:16px; height:16px;"></i>
                <span>Review &amp; Simpan (<span x-text="totalModified"></span>)</span>
            </button>
        </div>
    </div>

    <style>
    .bulk-summary-card {
        padding: 14px 20px;
        background: var(--color-surface);
        border: 1px solid var(--color-hairline);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        box-sizing: border-box;
    }
    .bulk-summary-metrics {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: nowrap;
    }
    .bulk-metric-item {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .bulk-metric-label {
        font-size: 10.5px;
        text-transform: uppercase;
        color: var(--color-ink-mute);
        font-weight: 700;
        letter-spacing: 0.04em;
        white-space: nowrap;
    }
    .bulk-metric-value {
        font-size: 16px;
        font-weight: 800;
        font-family: var(--font-mono);
        color: var(--color-ink);
        margin-top: 2px;
        white-space: nowrap;
    }
    .bulk-metric-divider {
        height: 26px;
        width: 1px;
        background: var(--color-hairline);
        flex-shrink: 0;
    }
    .bulk-summary-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }
    .bulk-summary-actions .bulk-btn-reset {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        white-space: nowrap;
    }
    .bulk-summary-actions .bulk-btn-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        white-space: nowrap;
    }
    @media (max-width: 768px) {
        .bulk-summary-card {
            flex-direction: column;
            align-items: stretch;
            padding: 12px;
            gap: 12px;
        }
        .bulk-summary-metrics {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            width: 100%;
        }
        .bulk-metric-divider {
            display: none !important;
        }
        .bulk-metric-item {
            background: rgba(148, 163, 184, 0.08);
            border: 1px solid var(--color-hairline);
            border-radius: 12px;
            padding: 10px 12px;
        }
        .bulk-metric-label {
            font-size: 10px;
        }
        .bulk-metric-value {
            font-size: 15px;
        }
        .bulk-summary-actions {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-top: 4px;
            border-top: 1px solid var(--color-hairline);
        }
        .bulk-summary-actions .bulk-btn-reset {
            flex: 1;
        }
        .bulk-summary-actions .bulk-btn-submit {
            flex: 2;
        }
    }
    </style>

    <!-- ========================================================================= -->
    <!-- 3.5 TAB FILTER KATEGORI ITEM (Semua, Barang Jadi, Bahan Mentah, Bahan Kemas) -->
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
                <span>Semua Item</span>
                <span class="badge-counter"
                      :style="activeTab === 'all' 
                          ? 'background:rgba(255,255,255,0.25); color:#fff;' 
                          : 'background:var(--color-canvas-soft); color:var(--color-ink-mute); border-color:var(--color-hairline);'"
                      x-text="countAll"></span>
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

        <!-- Row 2: Active Filter Status & Reset Action -->
        <div x-cloak x-show="hasActiveFilter" x-transition
             class="flex items-center justify-between gap-3 pt-2.5 mt-0.5 flex-wrap"
             style="border-top:1px solid var(--color-hairline);">
            <div class="inline-flex items-center gap-2">
                <div class="inline-flex items-center justify-center gap-1.5 rounded-full"
                     style="height:24px; padding:0 10px; font-size:11.5px; font-weight:600; line-height:1; background:rgba(37,99,235,0.08); color:var(--color-primary); border:1px solid rgba(37,99,235,0.22); box-sizing:border-box;">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse" style="flex-shrink:0;"></span>
                    <span x-text="filteredItems.length + ' item cocok'" style="line-height:1; display:inline-block; transform:translateY(-0.5px);"></span>
                </div>
                <span class="text-xs text-slate-400 dark:text-slate-500 font-normal hidden sm:inline" style="line-height:1;">
                    menyesuaikan kriteria filter &amp; pencarian aktif
                </span>
            </div>

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
    <!-- 4. TOOLBAR FILTER & SEARCH                                                -->
    <!-- ========================================================================= -->
    <div class="card" style="padding:12px 18px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <!-- Live Search -->
        <div class="form-input-icon flex-1 relative" style="min-width:240px;">
            <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
            <input type="text" x-ref="searchInput" x-model="searchQuery" @input="onSearchInput()"
                   placeholder="Cari SKU, Nama Produk/Bahan, Barcode..." class="form-input" style="height:38px; padding-right:58px; font-size:13px;">
            
            <!-- Shortcut Hint '/' when query is empty -->
            <span x-show="!searchQuery" 
                  class="hidden sm:inline-flex items-center justify-center font-mono text-[10px] text-slate-400 border border-slate-200 dark:border-slate-700 rounded px-1.5 py-0.5"
                  style="position:absolute; right:10px; top:50%; transform:translateY(-50%); pointer-events:none;">
                /
            </span>

            <!-- Clear Search Button when query is present -->
            <button type="button" x-cloak x-show="searchQuery" @click="clearSearch()"
                    class="btn btn-ghost btn-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    style="position:absolute; right:8px; top:50%; transform:translateY(-50%); height:24px; width:24px; padding:0; display:flex; align-items:center; justify-content:center;"
                    title="Hapus pencarian">
                <i data-lucide="x" style="width:14px; height:14px;"></i>
            </button>
        </div>

        <!-- Filter Grup Kemasan (Tampil untuk 'all' dan 'barang_jadi') -->
        <div style="min-width:180px;" x-show="activeTab === 'all' || activeTab === 'barang_jadi'">
            <select x-model="selectedGroup" @change="onFilterChange()" class="form-select" style="height:38px; font-size:13px;">
                <option value="">Semua Grup Kemasan</option>
                <template x-for="g in groups" :key="g.id">
                    <option :value="g.kode_grup" x-text="g.kode_grup + ' - ' + g.nama_grup"></option>
                </template>
            </select>
        </div>

        <!-- Filter Status Perubahan -->
        <div style="min-width:180px;">
            <select x-model="filterStatus" @change="onFilterChange()" class="form-select" style="height:38px; font-size:13px;">
                <option value="all">Semua Status</option>
                <option value="changed">Hanya yang Diubah (Selisih)</option>
                <option value="unmodified">Belum Dihitung / Belum Diperiksa</option>
                <option value="in">Hanya Stok Masuk (+)</option>
                <option value="out">Hanya Stok Keluar (-)</option>
            </select>
        </div>

        <!-- Per Page Selector -->
        <div style="display:flex; align-items:center; gap:6px;">
            <span style="font-size:12px; color:var(--color-ink-mute); white-space:nowrap;">Baris:</span>
            <select x-model="perPage" @change="onPerPageChange()" class="form-select" style="height:38px; width:80px; font-size:12.5px; font-family:var(--font-mono);">
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="all">Semua</option>
            </select>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. TABEL FORM BULK OPNAME                                                 -->
    <!-- ========================================================================= -->
    <div class="table-wrapper" x-ref="tableWrapper"
         style="overflow-anchor:none; scroll-behavior:auto !important;">
        <div class="table-scroll"
             style="overflow-anchor:none;">
            <table class="data-table" style="overflow-anchor:none;">
                <thead>
                    <tr>
                        <th style="width:45px; text-align:center;">No</th>
                        <th style="min-width:240px;">Produk &amp; SKU</th>
                        <th class="hide-sm" style="min-width:110px;">Barcode</th>
                        <th style="width:120px; text-align:right;">Stok Sistem</th>
                        <th style="width:150px; text-align:center; background:rgba(99,102,241,0.04);">
                            Stok Fisik Realita
                            <span style="display:block; font-size:10px; font-weight:normal; color:var(--color-ink-mute);">(Hasil Hitung)</span>
                        </th>
                        <th style="width:140px; text-align:center; background:rgba(99,102,241,0.04);">
                            Penyesuaian (+/-)
                            <span style="display:block; font-size:10px; font-weight:normal; color:var(--color-ink-mute);">(Selisih)</span>
                        </th>
                        <th style="width:110px; text-align:center;">Mutasi</th>
                        <th style="min-width:180px;">Catatan Baris (Opsional)</th>
                        <th style="width:80px; text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="paginatedItems.length === 0">
                        <tr>
                            <td colspan="9" style="text-align:center; padding:48px 16px; color:var(--color-ink-mute); font-size:13px;">
                                Tidak ada produk yang sesuai dengan filter atau pencarian.
                            </td>
                        </tr>
                    </template>

                    <template x-for="(it, idx) in paginatedItems" :key="it.id">
                        <tr :style="it.is_modified ? 'background:rgba(99, 102, 241, 0.05); border-left:3px solid var(--color-primary);' : ''">
                            <!-- No -->
                            <td style="text-align:center; font-family:var(--font-mono); font-size:12px; color:var(--color-ink-mute);"
                                x-text="getStartRowIndex() + idx + 1"></td>

                            <!-- Produk & SKU -->
                            <td>
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    <span class="badge badge-mono" x-text="it.kode_sku"></span>
                                    <span style="font-size:13px; font-weight:700; color:var(--color-ink);" x-text="it.nama_item"></span>

                                    <!-- Category Pill Badges for easy distinction -->
                                    <span x-show="it.tipe_item === 'bahan_mentah'" 
                                          class="badge badge-warning" 
                                          style="font-size:10px; font-weight:700; padding:1px 6px;">
                                        Bahan Mentah
                                    </span>
                                    <span x-show="it.tipe_item === 'bahan_kemas'" 
                                          class="badge" 
                                          style="background:rgba(15,118,110,0.12); color:#0f766e; border:1px solid rgba(15,118,110,0.25); font-size:10px; font-weight:700; padding:1px 6px;">
                                        Bahan Kemas
                                    </span>
                                </div>
                                <div x-show="it.kode_grup || it.nama_grup"
                                     style="font-size:11px; font-family:var(--font-mono); color:var(--color-ink-mute); margin-top:2px;"
                                     x-text="(it.kode_grup ? it.kode_grup + (it.nama_grup ? ' • ' + it.nama_grup : '') : (it.nama_grup || ''))"></div>
                            </td>

                            <!-- Barcode -->
                            <td class="hide-sm">
                                <span style="font-family:var(--font-mono); font-size:11px; padding:2px 6px; border:1px solid var(--color-hairline); border-radius:4px; background:var(--color-canvas-soft); color:var(--color-ink-mute);"
                                      x-text="formatBarcode(it.barcode || it.barcode_universal)"></span>
                            </td>

                            <!-- Stok Sistem -->
                            <td style="text-align:right;">
                                <div style="font-family:var(--font-mono); font-weight:700; font-size:13.5px; color:var(--color-ink);"
                                     x-text="formatQty(it.stok_sistem) + ' ' + it.satuan_dasar"></div>
                            </td>

                            <!-- Input 1: Stok Fisik Realita -->
                            <td style="text-align:center; background:rgba(99,102,241,0.02);">
                                <input type="text" inputmode="decimal"
                                       data-col="physical"
                                       :value="it.stok_fisik_val !== null ? it.stok_fisik_val : ''"
                                       :placeholder="formatQty(it.stok_sistem)"
                                       @focus="$event.target.select()"
                                       @click="$event.target.select()"
                                       @wheel="$event.target.blur()"
                                       @keydown="onPhysicalKeydown($event)"
                                       @keydown.enter.prevent="focusNextRow($event, 'physical')"
                                       @input="onPhysicalInput(it, $event.target)"
                                       @blur="onPhysicalBlur(it, $event.target)"
                                       class="form-input font-mono"
                                       style="height:36px; text-align:right; font-weight:700; font-size:13.5px;"
                                       :style="it.is_modified ? 'border-color:var(--color-primary); background:var(--color-surface);' : ''">
                            </td>

                            <!-- Input 2: Penyesuaian (+/-) -->
                            <td style="text-align:center; background:rgba(99,102,241,0.02);">
                                <input type="text" inputmode="decimal"
                                       data-col="diff"
                                       :value="it.selisih_input_val || ''"
                                       placeholder="0"
                                       @focus="$event.target.select()"
                                       @click="$event.target.select()"
                                       @wheel="$event.target.blur()"
                                       @keydown="onDiffKeydown($event)"
                                       @keydown.enter.prevent="focusNextRow($event, 'diff')"
                                       @input="onDiffInput(it, $event.target)"
                                       @blur="onDiffBlur(it, $event.target)"
                                       class="form-input font-mono"
                                       style="height:36px; text-align:right; font-weight:700; font-size:13.5px;"
                                       :style="it.selisih_val > 0 ? 'color:#059669; border-color:#10b981; background:var(--color-surface);' : (it.selisih_val < 0 ? 'color:#dc2626; border-color:#ef4444; background:var(--color-surface);' : '')">
                            </td>

                            <!-- Status Mutasi -->
                            <td style="text-align:center;">
                                <span x-show="!it.is_modified" class="badge badge-muted" style="font-size:11px;">Tetap</span>
                                <span x-show="it.is_modified && it.selisih_val > 0" class="badge badge-success" style="font-size:11px; font-weight:700;">
                                    + Masuk
                                </span>
                                <span x-show="it.is_modified && it.selisih_val < 0" class="badge badge-danger" style="font-size:11px; font-weight:700;">
                                    - Keluar
                                </span>
                            </td>

                            <!-- Catatan Khusus Item -->
                            <td>
                                <input type="text" x-model="it.catatan_item"
                                       @input="scheduleAutoSave()"
                                       placeholder="Catatan khusus baris ini..."
                                       class="form-input" style="height:34px; font-size:12px;">
                            </td>

                            <!-- Aksi -->
                            <td style="text-align:center;">
                                <div style="display:flex; align-items:center; justify-content:center; gap:4px;">
                                    <!-- Intip Mutasi -->
                                    <button type="button" @click="openHistory(it)"
                                            class="btn btn-ghost btn-sm" style="padding:4px 6px; border:1px solid var(--color-hairline);"
                                            title="Intip Riwayat Kartu Stok">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-primary); display:inline-block;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                                    </button>

                                    <!-- Reset Row -->
                                    <button type="button" @click="resetRow(it)"
                                            x-show="it.is_modified"
                                            class="btn btn-ghost btn-sm" style="padding:4px 6px; color:#dc2626;"
                                            title="Reset Baris Ini">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div x-ref="paginationBar" class="pagination-bar"
             style="padding:12px 18px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; border-top:1px solid var(--color-hairline); background:var(--color-surface);"
             x-show="perPage !== 'all' && totalPages > 1">
            <div style="font-size:12px; color:var(--color-ink-mute); font-family:var(--font-mono);">
                Menampilkan <strong class="text-ink" x-text="getStartRowIndex() + 1"></strong> -
                <strong class="text-ink" x-text="Math.min(getStartRowIndex() + parseInt(perPage), filteredItems.length)"></strong>
                dari <strong class="text-ink" x-text="filteredItems.length"></strong> produk
            </div>

            <div style="display:flex; align-items:center; gap:6px;">
                <button type="button" @click.prevent="prevPage($event)"
                        class="btn btn-secondary btn-sm"
                        :disabled="currentPage <= 1"
                        :style="currentPage <= 1 ? 'opacity:0.35; cursor:not-allowed;' : 'cursor:pointer;'"
                        style="padding:4px 12px; height:32px; transition:none !important; transform:none !important;">
                    &larr; Prev
                </button>
                <span style="font-size:12px; font-family:var(--font-mono); padding:0 8px; color:var(--color-ink); user-select:none;">
                    Hal <strong x-text="currentPage"></strong> / <span x-text="totalPages"></span>
                </span>
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
    <!-- MODAL 1: INTIP RIWAYAT MUTASI (KARTU STOK MINI)                           -->
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
                        <div class="modal-title">Kartu Stok &amp; Riwayat Mutasi Terakhir</div>
                        <div style="font-size:12px; font-family:var(--font-mono); color:var(--color-primary); margin-top:1px;"
                             x-text="(selectedItem.kode_sku || '') + ' — ' + (selectedItem.nama_item || '')"></div>
                    </div>
                </div>
                <button type="button" @click="showHistoryModal = false" class="modal-close-x" title="Tutup Modal">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <div x-show="historyLoading" style="padding:36px; text-align:center; color:var(--color-ink-mute);">
                <i data-lucide="loader" class="spin" style="width:24px; height:24px; margin:0 auto 8px auto;"></i>
                <div>Memuat riwayat pergerakan stok...</div>
            </div>

            <div x-show="!historyLoading">
                <div class="modal-body custom-scrollbar space-y-3.5">
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:var(--color-canvas-soft); border-radius:12px; border:1px solid var(--color-hairline);">
                        <div>
                            <div style="font-size:11px; color:var(--color-ink-mute); text-transform:uppercase; letter-spacing:0.04em;">Stok Fisik Saat Ini (Sistem)</div>
                            <div style="font-size:18px; font-weight:800; font-family:var(--font-mono); color:var(--color-ink);"
                                 x-text="formatQty(selectedItem.stok_sistem) + ' ' + (selectedItem.satuan_dasar || 'pcs')"></div>
                        </div>
                        <div style="text-align:right;">
                            <span class="badge badge-mono" x-text="formatBarcode(selectedItem.barcode || selectedItem.barcode_universal) || 'No Barcode'"></span>
                        </div>
                    </div>

                    <!-- Petunjuk Gesture Scroll untuk Layar Mobile / HP -->
                    <div class="sm:hidden flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500 px-1 pt-0.5">
                        <span class="inline-flex items-center gap-1.5 font-medium">
                            <i data-lucide="move-horizontal" style="width:12px; height:12px; flex-shrink:0;"></i>
                            <span>Geser tabel ke samping untuk melihat detail</span>
                        </span>
                    </div>

                    <div class="table-wrapper custom-scrollbar" style="max-height:380px; overflow-x:auto !important; overflow-y:auto !important; -webkit-overflow-scrolling:touch !important; touch-action:pan-x pan-y; overscroll-behavior-x:contain; border-radius:10px;">
                        <table class="data-table" style="min-width:620px; font-size:12px;">
                            <thead>
                                <tr>
                                    <th style="min-width:130px; position:sticky; top:0; z-index:2;" class="cell-nowrap">Waktu &amp; Tanggal</th>
                                    <th style="min-width:130px; position:sticky; top:0; z-index:2;" class="cell-nowrap">Tipe Mutasi</th>
                                    <th class="cell-right cell-nowrap" style="min-width:105px; text-align:right; position:sticky; top:0; z-index:2;">Perubahan</th>
                                    <th class="cell-right cell-nowrap" style="min-width:95px; text-align:right; position:sticky; top:0; z-index:2;">Stok Akhir</th>
                                    <th style="min-width:160px; position:sticky; top:0; z-index:2;">Keterangan / Oleh</th>
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
                                        <td class="cell-nowrap" style="font-family:var(--font-mono); font-size:11px; color:var(--color-ink-mute); white-space:nowrap;" x-text="formatDate(log.dibuat_pada)"></td>
                                        <td class="cell-nowrap">
                                            <span class="badge" :class="getMutationBadgeClass(log.tipe_mutasi)" x-text="formatMutationType(log.tipe_mutasi)"></span>
                                        </td>
                                        <td class="cell-right cell-nowrap" style="text-align:right; font-family:var(--font-mono); font-weight:700; white-space:nowrap;"
                                            :style="isStockIn(log.tipe_mutasi) ? 'color:#059669;' : 'color:#dc2626;'"
                                            x-text="(isStockIn(log.tipe_mutasi) ? '+' : '-') + formatQty(log.jumlah_perubahan) + ' pcs'"></td>
                                        <td class="cell-right cell-nowrap" style="text-align:right; font-family:var(--font-mono); color:var(--color-ink); white-space:nowrap;" x-text="formatQty(log.stok_sesudah) + ' pcs'"></td>
                                        <td style="min-width:160px;">
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

    <!-- ========================================================================= -->
    <!-- MODAL 2: KONFIRMASI RINGKASAN MUTASI SEBELUM SIMPAN                       -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showConfirmModal" x-cloak class="modal-backdrop" @click="showConfirmModal = false">
        <div class="modal-box modal-box-lg" style="max-width:820px;" @click.stop>
            <div class="modal-handle"><div class="modal-handle-bar"></div></div>
            <div class="modal-header">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(37,99,235,0.12);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="clipboard-check" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="modal-title">Konfirmasi Penyesuaian Mutasi Stok</div>
                        <div style="font-size:12px; color:var(--color-ink-mute); margin-top:1px;">
                            Periksa kembali ringkasan mutasi sebelum dicatat permanen ke buku besar gudang.
                        </div>
                    </div>
                </div>
                <button type="button" @click="showConfirmModal = false" class="modal-close-x" title="Tutup Modal">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/inventory/bulk-opname/store') ?>" method="POST"
                  @submit="onFormSubmit($event)"
                  data-action-text="Memproses dan menyimpan transaksi bulk opname...">
                
                <div class="modal-body custom-scrollbar space-y-3.5">
                    <?= \App\Helpers\CSRF::field() ?>
                    <input type="hidden" name="tanggal" :value="formTanggal">
                    <input type="hidden" name="catatan" :value="formCatatan">
                    <input type="hidden" name="total_katalog" :value="items.length">
                    <input type="hidden" name="items_json" :value="JSON.stringify(getChangedItemsPayload())">

                    <!-- Ringkasan Metrik Dokumen -->
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px;">
                        <div style="background:var(--color-canvas-soft); padding:12px 14px; border-radius:12px; border:1px solid var(--color-hairline);">
                            <span style="font-size:11px; text-transform:uppercase; color:var(--color-ink-mute); font-weight:700;">Tanggal Opname</span>
                            <div style="font-size:14px; font-weight:700; font-family:var(--font-mono); color:var(--color-ink); margin-top:2px;" x-text="formTanggal"></div>
                        </div>
                        <div style="background:var(--color-canvas-soft); padding:12px 14px; border-radius:12px; border:1px solid var(--color-hairline);">
                            <span style="font-size:11px; text-transform:uppercase; color:var(--color-ink-mute); font-weight:700;">Item Disesuaikan</span>
                            <div style="font-size:14px; font-weight:800; font-family:var(--font-mono); color:var(--color-primary); margin-top:2px;" x-text="totalModified + ' Produk'"></div>
                        </div>
                        <div style="background:rgba(16,185,129,0.08); padding:12px 14px; border-radius:12px; border:1px solid rgba(16,185,129,0.22);">
                            <span style="font-size:11px; text-transform:uppercase; color:#047857; font-weight:700;">Total Stok Masuk</span>
                            <div style="font-size:14px; font-weight:800; font-family:var(--font-mono); color:#059669; margin-top:2px;" x-text="'+' + formatQty(totalQtyIn) + ' pcs'"></div>
                        </div>
                        <div style="background:rgba(239,68,68,0.08); padding:12px 14px; border-radius:12px; border:1px solid rgba(239,68,68,0.22);">
                            <span style="font-size:11px; text-transform:uppercase; color:#b91c1c; font-weight:700;">Total Stok Keluar</span>
                            <div style="font-size:14px; font-weight:800; font-family:var(--font-mono); color:#dc2626; margin-top:2px;" x-text="'-' + formatQty(totalQtyOut) + ' pcs'"></div>
                        </div>
                    </div>

                    <!-- Catatan Umum -->
                    <div style="background:var(--color-canvas-soft); padding:10px 14px; border-radius:10px; font-size:12.5px; color:var(--color-ink);">
                        <strong style="color:var(--color-ink-mute);">Keterangan Dokumen:</strong>
                        <span x-text="formCatatan || '—'"></span>
                    </div>

                    <!-- Petunjuk Gesture Scroll untuk Layar Mobile / HP -->
                    <div class="sm:hidden flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500 px-1 pt-0.5">
                        <span class="inline-flex items-center gap-1.5 font-medium">
                            <i data-lucide="move-horizontal" style="width:12px; height:12px; flex-shrink:0;"></i>
                            <span>Geser tabel ke samping untuk melihat detail rincian</span>
                        </span>
                    </div>

                    <!-- Tabel Rincian Perubahan -->
                    <div class="table-wrapper custom-scrollbar" style="max-height:320px; overflow-x:auto !important; overflow-y:auto !important; -webkit-overflow-scrolling:touch !important; touch-action:pan-x pan-y; overscroll-behavior-x:contain; border-radius:10px;">
                        <table class="data-table" style="min-width:580px; font-size:12px;">
                            <thead>
                                <tr>
                                    <th style="min-width:180px; position:sticky; top:0; z-index:2;">Produk &amp; SKU</th>
                                    <th class="cell-right cell-nowrap" style="min-width:90px; text-align:right; position:sticky; top:0; z-index:2;">Stok Lama</th>
                                    <th class="cell-right cell-nowrap" style="min-width:90px; text-align:right; position:sticky; top:0; z-index:2;">Stok Baru</th>
                                    <th class="cell-right cell-nowrap" style="min-width:105px; text-align:right; position:sticky; top:0; z-index:2;">Mutasi (+/-)</th>
                                    <th style="min-width:140px; position:sticky; top:0; z-index:2;">Catatan Item</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in getChangedItemsPayload()" :key="row.item_id">
                                    <tr>
                                        <td>
                                            <div style="font-weight:700; color:var(--color-ink);" x-text="row.nama_item"></div>
                                            <div style="font-size:11px; font-family:var(--font-mono); color:var(--color-ink-mute);" x-text="row.kode_sku"></div>
                                        </td>
                                        <td class="cell-right cell-nowrap" style="text-align:right; font-family:var(--font-mono);" x-text="formatQty(row.stok_sistem) + ' pcs'"></td>
                                        <td class="cell-right cell-nowrap" style="text-align:right; font-family:var(--font-mono); font-weight:700;" x-text="formatQty(row.stok_fisik) + ' pcs'"></td>
                                        <td class="cell-right cell-nowrap" style="text-align:right; font-family:var(--font-mono); font-weight:800;"
                                            :style="row.selisih > 0 ? 'color:#059669;' : 'color:#dc2626;'"
                                            x-text="(row.selisih > 0 ? '+' : '') + formatQty(row.selisih) + ' pcs'"></td>
                                        <td style="font-size:11.5px; color:var(--color-ink-mute);" x-text="row.catatan_item || '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="modal-footer">
                    <button type="button" @click="showConfirmModal = false" :disabled="isSubmitting" class="btn btn-secondary modal-btn-cancel-desktop">
                        Periksa Kembali
                    </button>
                    <button type="submit" class="btn btn-primary w-full sm:w-auto" :disabled="isSubmitting"
                            :style="isSubmitting ? 'opacity:0.6; cursor:not-allowed;' : ''"
                            style="display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                        <i data-lucide="check" x-show="!isSubmitting" style="width:16px;height:16px;"></i>
                        <span x-text="isSubmitting ? 'Memproses Transaksi...' : 'Konfirmasi & Simpan Permanen'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

</div>

<script>
function bulkOpnameApp() {
    const rawItems = <?= json_encode($items) ?>;
    const groups = <?= json_encode($groups) ?>;

    const initializedItems = rawItems.map(it => {
        const sysStock = parseFloat(it.stok_fisik_saat_ini) || 0;
        return {
            id: it.id,
            kode_sku: it.kode_sku,
            barcode: it.barcode,
            barcode_universal: it.barcode_universal,
            nama_item: it.nama_item,
            tipe_item: it.tipe_item || 'barang_jadi',
            varian_rasa: it.varian_rasa,
            nama_grup: it.nama_grup,
            kode_grup: it.kode_grup,
            satuan_dasar: it.satuan_dasar || 'pcs',
            stok_sistem: sysStock,
            stok_fisik_val: null,
            selisih_val: 0,
            selisih_input_val: '',
            is_modified: false,
            catatan_item: ''
        };
    });

    return {
        items: initializedItems,
        groups: groups,
        formTanggal: '<?= date('Y-m-d') ?>',
        formCatatan: 'Bulk Opname Stok Fisik Gudang Pusat',
        activeTab: 'all',
        searchQuery: '',
        selectedGroup: '',
        filterStatus: 'all',
        perPage: '50',
        currentPage: 1,
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',

        // High-Performance Filtering & Totals State
        cachedFilteredItems: [],
        totalModified: 0,
        totalQtyIn: 0,
        totalQtyOut: 0,
        _recalcTimer: null,
        _bypassBeforeUnload: false,

        // Draft & Resilience State
        STORAGE_KEY: 'kerensnack_bulk_opname_draft_v1',
        hasDraft: false,
        draftDismissed: false,
        draftSavedAt: '',
        draftItemCount: 0,
        isSubmitting: false,
        autoSaveTimeout: null,

        // Modals
        showHistoryModal: false,
        showConfirmModal: false,
        historyLoading: false,
        itemHistoryList: [],
        selectedItem: {},

        // ── Scroll Helper ─────────────────────────────────────────────────────
        _getScrollContainer() {
            const el = document.querySelector('main.app-content') || document.querySelector('.app-content');
            if (el && (el.scrollHeight > el.clientHeight)) {
                return el;
            }
            return window;
        },

        _getScrollTop(el) {
            return el === window ? window.scrollY : el.scrollTop;
        },

        _setScrollTop(el, top) {
            if (el === window) {
                window.scrollTo({ top, behavior: 'instant' });
            } else {
                el.scrollTop = top;
            }
        },

        // ── Navigation & In-App Link Interception (AppConfirm instead of Native Dialog) ──
        async navigateAway(url) {
            if (this.totalModified > 0 && !this.isSubmitting) {
                const confirmed = window.AppConfirm ? await window.AppConfirm({
                    title: 'Tinggalkan Halaman Opname?',
                    message: 'Terdapat ' + this.totalModified + ' item yang telah Anda sesuaikan namun belum disimpan permanen ke database.',
                    submessage: 'Draf perubahan Anda tersimpan otomatis di perangkat ini dan akan langsung dipulihkan saat Anda kembali.',
                    type: 'warning',
                    confirmText: 'Ya, Tinggalkan',
                    cancelText: 'Tetap di Halaman Ini'
                }) : confirm('Terdapat data opname yang belum disimpan. Tinggalkan halaman?');

                if (!confirmed) return;
            }
            this._bypassBeforeUnload = true;
            window.location.href = url;
        },

        init() {
            // 1. Initial filter and totals computation
            this.applyFilter();
            this.recalculateTotals();

            // 2. Auto-restore saved draft from previous session
            try {
                const draftRaw = localStorage.getItem(this.STORAGE_KEY);
                if (draftRaw) {
                    const draft = JSON.parse(draftRaw);
                    const changes = draft.changes || {};
                    const changeCount = Object.keys(changes).length;
                    if (changeCount > 0) {
                        if (draft.formTanggal) this.formTanggal = draft.formTanggal;
                        if (draft.formCatatan) this.formCatatan = draft.formCatatan;
                        this.items.forEach(it => {
                            const ch = changes[it.id];
                            if (ch) {
                                it.stok_fisik_val = ch.stok_fisik_val;
                                it.selisih_val = ch.selisih_val;
                                it.is_modified = ch.is_modified;
                                it.catatan_item = ch.catatan_item || '';
                                if (it.selisih_val > 0) {
                                    it.selisih_input_val = '+' + this.formatQty(it.selisih_val);
                                } else if (it.selisih_val < 0) {
                                    it.selisih_input_val = '-' + this.formatQty(Math.abs(it.selisih_val));
                                } else if (it.stok_fisik_val !== null) {
                                    it.selisih_input_val = '0';
                                } else {
                                    it.selisih_input_val = '';
                                }
                            }
                        });
                        this.recalculateTotals();
                        this.applyFilter();
                        this.$nextTick(() => {
                            if (window.toast && window.toast.info) {
                                window.toast.info(`Draf opname otomatis dipulihkan (${changeCount} item).`);
                            }
                        });
                    }
                }
            } catch (e) {
                console.warn("Gagal membaca draft opname:", e);
            }

            // 3. Intercept in-app link clicks (Sidebar, Header, etc.) with AppConfirm
            document.addEventListener('click', async (e) => {
                const link = e.target.closest('a[href]');
                if (!link) return;
                const href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank') return;
                if (this.totalModified > 0 && !this.isSubmitting && !this._bypassBeforeUnload) {
                    e.preventDefault();
                    e.stopPropagation();
                    const confirmed = window.AppConfirm ? await window.AppConfirm({
                        title: 'Tinggalkan Halaman Opname?',
                        message: 'Terdapat ' + this.totalModified + ' item opname yang telah Anda ubah namun belum disimpan ke database.',
                        submessage: 'Draf perubahan tetap aman tersimpan di browser ini dan tidak akan hilang.',
                        type: 'warning',
                        confirmText: 'Ya, Tinggalkan',
                        cancelText: 'Tetap di Halaman Ini'
                    }) : confirm('Ada data opname belum disimpan. Tinggalkan halaman?');

                    if (confirmed) {
                        this._bypassBeforeUnload = true;
                        window.location.href = link.href;
                    }
                }
            }, true);

            // 4. Fallback guard if user attempts to close browser tab directly
            window.addEventListener('beforeunload', (e) => {
                if (this._bypassBeforeUnload || this.isSubmitting || this.totalModified === 0) {
                    return;
                }
                e.preventDefault();
                e.returnValue = '';
            });

            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        // ── Category Tab Counters ─────────────────────────────────────────────
        get countAll() {
            return this.items.length;
        },

        get countBarangJadi() {
            return this.items.filter(it => it.tipe_item === 'barang_jadi' || !it.tipe_item).length;
        },

        get countBahanMentah() {
            return this.items.filter(it => it.tipe_item === 'bahan_mentah').length;
        },

        get countBahanKemas() {
            return this.items.filter(it => it.tipe_item === 'bahan_kemas').length;
        },

        setTab(tab) {
            if (this.activeTab === tab) return;
            this.activeTab = tab;
            this.currentPage = 1;
            this.applyFilter();
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        // ── High-Performance Caching Filter Engine ────────────────────────────
        onSearchInput() {
            this.currentPage = 1;
            this.applyFilter();
        },

        clearSearch() {
            this.searchQuery = '';
            this.currentPage = 1;
            this.applyFilter();
            if (this.$refs.searchInput) this.$refs.searchInput.focus();
        },

        onFilterChange() {
            this.currentPage = 1;
            this.applyFilter();
        },

        onPerPageChange() {
            this.currentPage = 1;
            this.applyFilter();
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        get hasActiveFilter() {
            return this.activeTab !== 'all' || 
                   this.searchQuery.trim() !== '' || 
                   this.selectedGroup !== '' || 
                   this.filterStatus !== 'all';
        },

        resetAllFilters() {
            this.activeTab = 'all';
            this.searchQuery = '';
            this.selectedGroup = '';
            this.filterStatus = 'all';
            this.currentPage = 1;
            this.applyFilter();
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        applyFilter() {
            let res = this.items;

            // 1. Tab Kategori
            if (this.activeTab !== 'all') {
                res = res.filter(it => it.tipe_item === this.activeTab);
            }

            // 2. Live Search
            const q = this.searchQuery.trim().toLowerCase();
            if (q) {
                res = res.filter(it =>
                    (it.nama_item && it.nama_item.toLowerCase().includes(q)) ||
                    (it.kode_sku && it.kode_sku.toLowerCase().includes(q)) ||
                    (it.barcode && String(it.barcode).toLowerCase().includes(q)) ||
                    (it.barcode_universal && String(it.barcode_universal).toLowerCase().includes(q)) ||
                    (it.nama_grup && it.nama_grup.toLowerCase().includes(q))
                );
            }

            // 3. Grup Kemasan (khusus all & barang_jadi)
            if (this.selectedGroup && (this.activeTab === 'all' || this.activeTab === 'barang_jadi')) {
                res = res.filter(it => it.kode_grup === this.selectedGroup);
            }

            // 4. Status Perubahan
            if (this.filterStatus === 'changed') {
                res = res.filter(it => it.is_modified);
            } else if (this.filterStatus === 'unmodified') {
                res = res.filter(it => !it.is_modified);
            } else if (this.filterStatus === 'in') {
                res = res.filter(it => it.is_modified && it.selisih_val > 0);
            } else if (this.filterStatus === 'out') {
                res = res.filter(it => it.is_modified && it.selisih_val < 0);
            }

            this.cachedFilteredItems = res;
        },

        get filteredItems() {
            return this.cachedFilteredItems;
        },

        get totalPages() {
            if (this.perPage === 'all') return 1;
            const size = parseInt(this.perPage) || 50;
            return Math.max(1, Math.ceil(this.filteredItems.length / size));
        },

        get paginatedItems() {
            if (this.perPage === 'all') return this.filteredItems;
            const size = parseInt(this.perPage) || 50;
            const start = (this.currentPage - 1) * size;
            return this.filteredItems.slice(start, start + size);
        },

        getStartRowIndex() {
            if (this.perPage === 'all') return 0;
            return (this.currentPage - 1) * parseInt(this.perPage);
        },

        // ── Single-Pass High Speed Totals Calculation ─────────────────────────
        recalculateTotals() {
            let mod = 0, qIn = 0, qOut = 0;
            const len = this.items.length;
            for (let i = 0; i < len; i++) {
                const it = this.items[i];
                if (it.is_modified) {
                    mod++;
                    if (it.selisih_val > 0) qIn += it.selisih_val;
                    else if (it.selisih_val < 0) qOut += Math.abs(it.selisih_val);
                }
            }
            this.totalModified = mod;
            this.totalQtyIn = qIn;
            this.totalQtyOut = qOut;
        },

        recalculateTotalsDebounced() {
            if (this._recalcTimer) cancelAnimationFrame(this._recalcTimer);
            this._recalcTimer = requestAnimationFrame(() => {
                this.recalculateTotals();
            });
        },

        // ── Page Navigation ───────────────────────────────────────────────────
        goToPage(targetPage) {
            if (targetPage < 1 || targetPage > this.totalPages || targetPage === this.currentPage) return;

            const paginationEl = this.$refs.paginationBar;
            const tableEl = this.$refs.tableWrapper;

            if (tableEl && tableEl.offsetHeight > 0) {
                tableEl.style.minHeight = tableEl.offsetHeight + 'px';
            }

            const paginationTopBefore = paginationEl ? paginationEl.getBoundingClientRect().top : null;
            const scrollEl = this._getScrollContainer();

            this.currentPage = targetPage;

            this.$nextTick(() => {
                if (tableEl) {
                    tableEl.style.minHeight = '';
                }

                if (paginationTopBefore !== null && paginationEl) {
                    const paginationTopAfter = paginationEl.getBoundingClientRect().top;
                    const delta = paginationTopAfter - paginationTopBefore;

                    if (Math.abs(delta) >= 1) {
                        if (scrollEl === window) {
                            window.scrollBy({ top: delta, behavior: 'instant' });
                        } else {
                            scrollEl.scrollBy({ top: delta, behavior: 'instant' });
                        }
                    }
                }

                if (window.lucide) lucide.createIcons();
            });
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

        // ── Draft & Auto-Save Mechanics ───────────────────────────────────────
        scheduleAutoSave() {
            clearTimeout(this.autoSaveTimeout);
            this.autoSaveTimeout = setTimeout(() => {
                this.saveDraftNow();
            }, 600);
        },

        saveDraftNow() {
            if (this.isSubmitting) return;
            const changes = {};
            this.items.forEach(it => {
                if (it.is_modified || (it.catatan_item && it.catatan_item.trim() !== '') || it.stok_fisik_val !== null) {
                    changes[it.id] = {
                        stok_fisik_val: it.stok_fisik_val,
                        selisih_val: it.selisih_val,
                        is_modified: it.is_modified,
                        catatan_item: it.catatan_item
                    };
                }
            });

            if (Object.keys(changes).length > 0) {
                const now = new Date();
                const timeStr = now.toLocaleDateString('id-ID', { day:'2-digit', month:'short' }) + ' ' +
                                now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });
                const payload = {
                    saved_at: timeStr,
                    formTanggal: this.formTanggal,
                    formCatatan: this.formCatatan,
                    changes: changes
                };
                localStorage.setItem(this.STORAGE_KEY, JSON.stringify(payload));
            } else {
                localStorage.removeItem(this.STORAGE_KEY);
            }
        },

        clearDraft() {
            localStorage.removeItem(this.STORAGE_KEY);
            this.hasDraft = false;
            this.draftDismissed = true;
        },

        async confirmClearDraft() {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Buang Draf Opname?',
                message: 'Apakah Anda yakin ingin membuang draf opname otomatis ini? Data yang tersimpan di browser Anda akan dihapus permanen.',
                type: 'warning',
                confirmText: 'Ya, Buang Draf',
                cancelText: 'Batal'
            }) : confirm('Apakah Anda yakin ingin membuang draf opname otomatis ini?');

            if (confirmed) {
                this.clearDraft();
                if (window.toast && window.toast.info) {
                    window.toast.info('Draf opname berhasil dibuang.');
                }
            }
        },

        onFormSubmit(e) {
            this.isSubmitting = true;
            this._bypassBeforeUnload = true;
            localStorage.removeItem(this.STORAGE_KEY);
        },

        focusNextRow(evt, colType) {
            const td = evt.target.closest('td');
            const tr = td?.closest('tr');
            const nextTr = tr?.nextElementSibling;
            if (nextTr) {
                const targetInput = nextTr.querySelector(`input[data-col="${colType}"]`);
                if (targetInput) {
                    targetInput.focus();
                    targetInput.select();
                }
            }
        },

        // ── High Speed Two-way Binding 1: Stok Fisik Realita ─────────────────
        onPhysicalKeydown(evt) {
            if (evt.ctrlKey || evt.metaKey) return;
            const allowed = [
                'Backspace', 'Delete', 'Tab', 'Enter', 'Escape',
                'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                'Home', 'End'
            ];
            if (allowed.includes(evt.key)) return;

            // Only allow 0-9
            if (/^[0-9]$/.test(evt.key)) return;

            // Decimal separator (. or ,) max 1
            if (evt.key === '.' || evt.key === ',') {
                const target = evt.target;
                const val = target.value || '';
                const hasDecimal = val.includes('.') || val.includes(',');
                const selectionHasDecimal = target.selectionStart !== target.selectionEnd &&
                    val.substring(target.selectionStart, target.selectionEnd).search(/[.,]/) !== -1;
                if (!hasDecimal || selectionHasDecimal) return;
            }

            // Strictly prevent negative (-), plus (+), and other letters
            evt.preventDefault();
        },

        onPhysicalInput(it, target) {
            let val = typeof target === 'object' && target ? target.value : target;
            if (val === '' || val === null || val === undefined) {
                it.stok_fisik_val = null;
                it.selisih_val = 0;
                it.selisih_input_val = '';
                it.is_modified = false;

                const tr = target?.closest ? target.closest('tr') : null;
                const diffInput = tr?.querySelector('input[data-col="diff"]');
                if (diffInput) {
                    diffInput.value = '';
                    diffInput.style.color = '';
                    diffInput.style.borderColor = '';
                }

                this.recalculateTotalsDebounced();
                this.scheduleAutoSave();
                return;
            }

            let raw = String(val).replace(/[^0-9.,]/g, '');
            let parts = raw.split(/[.,]/);
            if (parts.length > 2) {
                raw = parts[0] + '.' + parts.slice(1).join('');
            }
            if (typeof target === 'object' && target && target.value !== raw) {
                target.value = raw;
            }

            const clean = raw.replace(',', '.');
            let num = parseFloat(clean);
            if (isNaN(num)) return;
            if (num < 0) num = 0;

            const diff = num - it.stok_sistem;
            it.stok_fisik_val = num;
            it.selisih_val = diff;
            it.is_modified = Math.abs(diff) > 0.0001;

            const formattedDiff = diff > 0 ? ('+' + this.formatQty(diff)) : (diff < 0 ? ('-' + this.formatQty(Math.abs(diff))) : '0');
            it.selisih_input_val = formattedDiff;

            // Direct sister DOM update for instant 60fps response
            const tr = target?.closest ? target.closest('tr') : null;
            const diffInput = tr?.querySelector('input[data-col="diff"]');
            if (diffInput) {
                diffInput.value = formattedDiff;
                if (diff > 0) {
                    diffInput.style.color = '#059669';
                    diffInput.style.borderColor = '#10b981';
                } else if (diff < 0) {
                    diffInput.style.color = '#dc2626';
                    diffInput.style.borderColor = '#ef4444';
                } else {
                    diffInput.style.color = 'var(--color-ink)';
                    diffInput.style.borderColor = 'var(--color-hairline)';
                }
            }

            this.recalculateTotalsDebounced();
            this.scheduleAutoSave();
        },

        onPhysicalBlur(it, target) {
            let val = typeof target === 'object' && target ? target.value : target;
            if (val === '' || val === null || val === undefined) {
                this.resetRow(it);
                return;
            }
            const clean = String(val).replace(',', '.').replace(/\s+/g, '');
            let num = parseFloat(clean);
            if (isNaN(num)) {
                this.resetRow(it);
                return;
            }
            if (num < 0) num = 0;
            const diff = num - it.stok_sistem;
            it.stok_fisik_val = num;
            it.selisih_val = diff;
            it.is_modified = Math.abs(diff) > 0.0001;
            it.selisih_input_val = diff > 0 ? ('+' + this.formatQty(diff)) : (diff < 0 ? ('-' + this.formatQty(Math.abs(diff))) : '');

            // Format physical display on blur
            if (typeof target === 'object' && target) {
                target.value = this.formatQty(num);
            }
            this.recalculateTotals();
            this.scheduleAutoSave();
        },

        // ── High Speed Two-way Binding 2: Penyesuaian (+/-) ───────────────────
        onDiffKeydown(evt) {
            if (evt.ctrlKey || evt.metaKey) return;
            const allowed = [
                'Backspace', 'Delete', 'Tab', 'Enter', 'Escape',
                'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                'Home', 'End'
            ];
            if (allowed.includes(evt.key)) return;

            const target = evt.target;
            const val = target.value || '';
            const selStart = target.selectionStart;
            const selEnd = target.selectionEnd;

            // Allow + and -
            if (evt.key === '+' || evt.key === '-') {
                const hasExistingSign = /^[+-]/.test(val);
                const selectionReplacesSign = (selStart === 0 && (selEnd > 0 || !hasExistingSign));
                if (selStart === 0 || selectionReplacesSign) {
                    return;
                }
                evt.preventDefault();
                let withoutSign = val.replace(/^[+-]/, '');
                let newVal = evt.key + withoutSign;
                target.value = newVal;
                let newPos = Math.min(selStart + 1, newVal.length);
                target.setSelectionRange(newPos, newPos);
                target.dispatchEvent(new Event('input'));
                return;
            }

            if (/^[0-9]$/.test(evt.key)) return;

            if (evt.key === '.' || evt.key === ',') {
                const hasDecimal = val.includes('.') || val.includes(',');
                const selectionSpansDecimal = selStart !== selEnd &&
                    val.substring(selStart, selEnd).search(/[.,]/) !== -1;
                if (!hasDecimal || selectionSpansDecimal) return;
            }

            evt.preventDefault();
        },

        sanitizeDiffInput(val) {
            if (val === null || val === undefined) return '';
            let str = String(val).trim();
            if (!str) return '';

            let sign = '';
            if (str.startsWith('+')) {
                sign = '+';
                str = str.substring(1);
            } else if (str.startsWith('-')) {
                sign = '-';
                str = str.substring(1);
            }

            str = str.replace(/[^0-9.,]/g, '');
            let parts = str.split(/[.,]/);
            if (parts.length > 2) {
                str = parts[0] + '.' + parts.slice(1).join('');
            }

            return sign + str;
        },

        notifyMaxReductionCapped(it, maxReduction) {
            const now = Date.now();
            if (this._lastCappedToastTime && (now - this._lastCappedToastTime < 2400)) {
                return;
            }
            this._lastCappedToastTime = now;

            const unit = it.satuan_dasar || 'pcs';
            if (maxReduction <= 0) {
                if (window.toast && window.toast.warning) {
                    window.toast.warning(`Stok sistem "${it.nama_item}" adalah 0 ${unit}. Pengurangan stok (-) tidak diizinkan.`);
                }
            } else {
                if (window.toast && window.toast.warning) {
                    window.toast.warning(`Pengurangan stok "${it.nama_item}" dibatasi maksimal -${this.formatQty(maxReduction)} ${unit} agar stok fisik tidak minus.`);
                }
            }
        },

        onDiffInput(it, target) {
            let rawVal = typeof target === 'object' && target ? target.value : target;
            let sanitized = this.sanitizeDiffInput(rawVal);
            if (typeof target === 'object' && target && target.value !== sanitized) {
                target.value = sanitized;
            }
            it.selisih_input_val = sanitized;

            if (sanitized === '' || sanitized === '-' || sanitized === '+' || sanitized === null) {
                return;
            }

            const clean = sanitized.replace(',', '.').replace(/\s+/g, '');
            let diff = parseFloat(clean);
            if (isNaN(diff)) return;

            const systemStock = Number(it.stok_sistem) || 0;
            if (diff < 0) {
                const maxReduction = Math.max(0, systemStock);
                if (Math.abs(diff) > maxReduction) {
                    diff = -maxReduction;
                    let cappedStr = '-' + this.formatQty(maxReduction);
                    it.selisih_input_val = cappedStr;
                    if (typeof target === 'object' && target) {
                        target.value = cappedStr;
                    }
                    this.notifyMaxReductionCapped(it, maxReduction);
                }
            }

            let targetPhysical = Math.max(0, systemStock + diff);
            it.selisih_val = diff;
            it.stok_fisik_val = targetPhysical;
            it.is_modified = Math.abs(diff) > 0.0001;

            // Direct sister DOM update
            const tr = target?.closest ? target.closest('tr') : null;
            const physInput = tr?.querySelector('input[data-col="physical"]');
            if (physInput) {
                physInput.value = this.formatQty(targetPhysical);
            }

            this.recalculateTotalsDebounced();
            this.scheduleAutoSave();
        },

        onDiffBlur(it, target) {
            let rawVal = typeof target === 'object' && target ? target.value : target;
            if (rawVal === '' || rawVal === '-' || rawVal === '+' || rawVal === null || rawVal === undefined) {
                this.resetRow(it);
                return;
            }

            let sanitized = this.sanitizeDiffInput(rawVal);
            const clean = sanitized.replace(',', '.').replace(/\s+/g, '');
            let diff = parseFloat(clean);
            if (isNaN(diff)) {
                this.resetRow(it);
                return;
            }

            const systemStock = Number(it.stok_sistem) || 0;
            if (diff < 0) {
                const maxReduction = Math.max(0, systemStock);
                if (Math.abs(diff) > maxReduction) {
                    diff = -maxReduction;
                    this.notifyMaxReductionCapped(it, maxReduction);
                }
            }

            let targetPhysical = Math.max(0, systemStock + diff);
            it.selisih_val = diff;
            it.stok_fisik_val = targetPhysical;
            it.is_modified = Math.abs(diff) > 0.0001;
            it.selisih_input_val = diff > 0 ? ('+' + this.formatQty(diff)) : (diff < 0 ? ('-' + this.formatQty(Math.abs(diff))) : '');

            if (typeof target === 'object' && target) {
                target.value = it.selisih_input_val;
            }
            this.recalculateTotals();
            this.scheduleAutoSave();
        },

        resetRow(it) {
            it.stok_fisik_val = null;
            it.selisih_val = 0;
            it.selisih_input_val = '';
            it.is_modified = false;
            this.recalculateTotals();
            this.scheduleAutoSave();
        },

        async resetAllRows() {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Batalkan Semua Perubahan?',
                message: 'Apakah Anda yakin ingin membatalkan seluruh perubahan opname di tabel ini? Data yang belum disimpan akan dikembalikan.',
                type: 'danger',
                confirmText: 'Ya, Batalkan',
                cancelText: 'Kembali'
            }) : confirm('Apakah Anda yakin ingin membatalkan seluruh perubahan opname di tabel ini?');

            if (!confirmed) {
                return;
            }

            this.items.forEach(it => {
                it.stok_fisik_val = null;
                it.selisih_val = 0;
                it.selisih_input_val = '';
                it.is_modified = false;
                it.catatan_item = '';
            });
            this.recalculateTotals();
            this.applyFilter();
            this.clearDraft();
            if (window.toast && window.toast.info) {
                window.toast.info('Semua perubahan opname berhasil dibatalkan.');
            }
        },

        getChangedItemsPayload() {
            return this.items
                .filter(it => it.is_modified)
                .map(it => ({
                    item_id: it.id,
                    kode_sku: it.kode_sku,
                    nama_item: it.nama_item,
                    stok_sistem: it.stok_sistem,
                    stok_fisik: it.stok_fisik_val !== null ? it.stok_fisik_val : it.stok_sistem,
                    selisih: it.selisih_val,
                    catatan_item: it.catatan_item
                }));
        },

        async openConfirmModal() {
            if (this.totalModified === 0) {
                if (window.AppAlert) {
                    await window.AppAlert({
                        title: 'Belum Ada Perubahan',
                        message: 'Tidak ada item yang diubah. Silakan masukkan stok fisik atau selisih minimal 1 produk sebelum menyimpan.',
                        type: 'info',
                        buttonText: 'Mengerti'
                    });
                } else {
                    alert('Tidak ada item yang diubah. Silakan masukkan stok fisik atau selisih minimal 1 produk.');
                }
                return;
            }
            this.showConfirmModal = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
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
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

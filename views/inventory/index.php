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
            <a href="<?= Router::url('/inventory/export-excel') ?>" class="btn btn-secondary" style="height:38px; background:#10b981; color:#fff; border-color:#059669; font-weight:700; display:inline-flex; align-items:center; gap:7px;">
                <i data-lucide="file-spreadsheet" style="width:15px; height:15px;"></i>
                <span>Export Excel</span>
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
    <!-- 3. TOOLBAR & FILTER CARD                                                  -->
    <!-- ========================================================================= -->
    <div class="card" style="padding:14px 18px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <!-- Live Search -->
        <div class="form-input-icon" style="flex:1; min-width:240px;">
            <i data-lucide="search" class="icon-left"></i>
            <input type="text" x-model="searchQuery" @input="currentPage = 1"
                   placeholder="Cari SKU, Nama Produk, atau Barcode..."
                   class="form-input" style="height:38px;">
        </div>

        <!-- Filter Grup Kemasan -->
        <div style="min-width:180px;">
            <select x-model="selectedGroup" @change="currentPage = 1" class="form-select" style="height:38px; font-size:13px;">
                <option value="">Semua Grup Kemasan</option>
                <?php foreach ($groups ?? [] as $g): ?>
                <option value="<?= htmlspecialchars($g['kode_grup']) ?>"><?= htmlspecialchars($g['kode_grup'] . ' - ' . $g['nama_grup']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filter Status Ketersediaan -->
        <div style="min-width:150px;">
            <select x-model="filterStatus" @change="currentPage = 1" class="form-select" style="height:38px; font-size:13px;">
                <option value="all">Semua Status</option>
                <option value="available">Tersedia (&gt; 10)</option>
                <option value="low">Menipis (1 - 10)</option>
                <option value="empty">Kosong (0)</option>
            </select>
        </div>

        <!-- Tampilkan per Halaman -->
        <div style="display:flex; align-items:center; gap:6px;">
            <span style="font-size:12px; color:var(--color-ink-mute); white-space:nowrap;">Baris:</span>
            <select x-model="perPage" @change="currentPage = 1" class="form-select" style="height:38px; width:80px; font-size:12.5px; font-family:var(--font-mono);">
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
                <tbody>
                    <template x-if="paginatedItems.length === 0">
                        <tr>
                            <td colspan="6" style="text-align:center; padding:48px 16px; color:var(--color-ink-mute); font-size:13px;">
                                Tidak ada produk yang cocok dengan pencarian / filter.
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

                    <!-- Filler Row: Ensures Table Height Never Changes Across Pages -->
                    <tr class="filler-row"
                        x-show="perPage !== 'all' && totalPages > 1 && paginatedItems.length < parseInt(perPage)"
                        style="border:none; background:transparent; pointer-events:none;">
                        <td colspan="6" style="border:none; padding:0; background:transparent;">
                            <div :style="'height:' + ((parseInt(perPage) - paginatedItems.length) * (avgRowHeight || 48)) + 'px; pointer-events:none;'"></div>
                        </td>
                    </tr>
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
    <!-- MODAL 1: OPNAME TUNGGAL                                                   -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showAdjustModal" x-cloak class="modal-backdrop">
        <div @click.away="showAdjustModal = false" class="modal-box">
            <div class="modal-header">
                <div>
                    <div class="modal-title">Penyesuaian Stok (Opname Tunggal)</div>
                    <div style="font-size:11px; font-family:var(--font-mono); color:var(--color-primary); margin-top:2px;"
                         x-text="(selectedItem.kode_sku || '') + ' — ' + (selectedItem.nama_item || '')"></div>
                </div>
                <button type="button" @click="showAdjustModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:15px;height:15px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/inventory/adjust') ?>" method="POST" data-action-text="Menyimpan penyesuaian stok..." style="display:flex;flex-direction:column;gap:14px; padding:18px 22px;">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="item_id" :value="selectedItem.id">

                <div style="padding:10px 12px; background:var(--color-canvas-soft); border:1px solid var(--color-hairline); border-radius:10px; display:flex; align-items:center; justify-content:space-between;">
                    <span style="font-size:12px; color:var(--color-ink-mute);">Stok Fisik Saat Ini:</span>
                    <strong class="font-mono" style="font-size:14px;" x-text="(selectedItem.stok_fisik_saat_ini || 0) + ' ' + (selectedItem.satuan_dasar || 'pcs')"></strong>
                </div>

                <div>
                    <label class="form-label font-semibold">Jenis Penyesuaian</label>
                    <select name="tipe_penyesuaian" x-model="adjustType" class="form-select">
                        <option value="opname_lebih">Opname Lebih (Tambah Stok Masuk)</option>
                        <option value="opname_hilang">Opname Hilang / Selisih Fisik (Potong Stok Keluar)</option>
                    </select>
                </div>

                <div>
                    <label class="form-label font-semibold">Jumlah Kuantitas Penyesuaian *</label>
                    <input type="number" step="any" name="kuantitas" x-model="adjustQty" @wheel="$event.target.blur()" required min="0.0001" placeholder="Masukkan jumlah selisih pcs..."
                           class="form-input font-mono" style="font-weight:700;">
                </div>

                <!-- Live Calculation Preview -->
                <div style="padding:10px 14px; background:rgba(99,102,241,0.06); border:1px dashed var(--color-primary); border-radius:10px; display:flex; flex-direction:column; gap:4px;">
                    <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--color-ink-mute);">
                        <span>Stok Sistem Saat Ini:</span>
                        <span class="font-mono" x-text="(selectedItem.stok_fisik_saat_ini || 0) + ' ' + (selectedItem.satuan_dasar || 'pcs')"></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--color-ink-mute);">
                        <span>Perubahan Mutasi:</span>
                        <span class="font-mono" :style="adjustType === 'opname_lebih' ? 'color:#059669; font-weight:700;' : 'color:#dc2626; font-weight:700;'"
                              x-text="(adjustType === 'opname_lebih' ? '+' : '-') + (adjustQty || 0) + ' ' + (selectedItem.satuan_dasar || 'pcs')"></span>
                    </div>
                    <div style="height:1px; background:var(--color-hairline); margin:2px 0;"></div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-size:12.5px; font-weight:700; color:var(--color-ink);">Estimasi Stok Baru:</span>
                        <strong class="font-mono" style="font-size:14.5px; color:var(--color-primary);"
                                x-text="calculateAdjustPreview() + ' ' + (selectedItem.satuan_dasar || 'pcs')"></strong>
                    </div>
                </div>

                <div x-show="adjustType === 'opname_hilang' && parseFloat(adjustQty) > parseFloat(selectedItem.stok_fisik_saat_ini || 0)"
                     style="padding:8px 12px; background:#fee2e2; border:1px solid #fecaca; border-radius:8px; font-size:12px; color:#b91c1c; font-weight:600; display:flex; align-items:center; gap:6px;">
                    <i data-lucide="alert-triangle" style="width:14px; height:14px; flex-shrink:0;"></i>
                    <span>Pengurangan opname melebihi sisa stok fisik saat ini!</span>
                </div>

                <div>
                    <label class="form-label font-semibold">Catatan / Alasan *</label>
                    <input type="text" name="alasan" required placeholder="Contoh: Hasil hitung fisik rak A2"
                           class="form-input">
                </div>

                <div style="display:flex; gap:8px; padding-top:4px;">
                    <button type="button" @click="showAdjustModal = false" class="btn btn-secondary" style="flex:1; justify-content:center;">Batal</button>
                    <button type="submit" class="btn btn-primary"
                            :disabled="adjustType === 'opname_hilang' && parseFloat(adjustQty) > parseFloat(selectedItem.stok_fisik_saat_ini || 0)"
                            :style="(adjustType === 'opname_hilang' && parseFloat(adjustQty) > parseFloat(selectedItem.stok_fisik_saat_ini || 0)) ? 'opacity:0.5; cursor:not-allowed;' : ''"
                            style="flex:1; justify-content:center; font-weight:700;">
                        <i data-lucide="save"></i>
                        Simpan Opname
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
    <div x-show="showWasteModal" x-cloak class="modal-backdrop">
        <div @click.away="showWasteModal = false" class="modal-box">
            <div class="modal-header">
                <div>
                    <div class="modal-title" style="color:#b91c1c;">Catat Barang Rusak / Waste</div>
                    <div style="font-size:11px; font-family:var(--font-mono); color:#dc2626; margin-top:2px;"
                         x-text="(selectedItem.kode_sku || '') + ' — ' + (selectedItem.nama_item || '')"></div>
                </div>
                <button type="button" @click="showWasteModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:15px;height:15px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/inventory/waste') ?>" method="POST" data-action-text="Mencatat barang rusak / waste..." style="display:flex;flex-direction:column;gap:14px; padding:18px 22px;">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="item_id" :value="selectedItem.id">

                <div style="padding:10px 12px; background:#fef2f2; border:1px solid #fee2e2; border-radius:10px; font-size:12px; color:#991b1b; display:flex; align-items:center; justify-content:space-between;">
                    <span>Sisa Stok Fisik Saat Ini:</span>
                    <strong class="font-mono" style="font-size:14px;" x-text="(selectedItem.stok_fisik_saat_ini || 0) + ' ' + (selectedItem.satuan_dasar || 'pcs')"></strong>
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

                <div style="display:flex; gap:8px; padding-top:4px;">
                    <button type="button" @click="showWasteModal = false" class="btn btn-secondary" style="flex:1; justify-content:center;">Batal</button>
                    <button type="submit" class="btn btn-danger-solid"
                            :disabled="parseFloat(wasteQty) <= 0 || parseFloat(wasteQty) > parseFloat(selectedItem.stok_fisik_saat_ini || 0)"
                            :style="(parseFloat(wasteQty) <= 0 || parseFloat(wasteQty) > parseFloat(selectedItem.stok_fisik_saat_ini || 0)) ? 'opacity:0.5; cursor:not-allowed;' : ''"
                            style="flex:1; justify-content:center; background:#dc2626; color:#fff; font-weight:700;">
                        <i data-lucide="trash-2"></i>
                        Potong Stok Waste
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
    <div x-show="showHistoryModal" x-cloak class="modal-backdrop">
        <div @click.away="showHistoryModal = false" class="modal-box" style="max-width:760px; width:95%;">
            <div class="modal-header">
                <div>
                    <div class="modal-title" style="display:flex; align-items:center; gap:8px;">
                        <i data-lucide="activity" style="color:var(--color-primary); width:18px; height:18px;"></i>
                        <span>Kartu Stok &amp; Riwayat Mutasi</span>
                    </div>
                    <div style="font-size:12px; font-family:var(--font-mono); color:var(--color-primary); margin-top:2px;"
                         x-text="(selectedItem.kode_sku || '') + ' — ' + (selectedItem.nama_item || '')"></div>
                </div>
                <button type="button" @click="showHistoryModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px; height:16px;"></i>
                </button>
            </div>

            <!-- Loading State -->
            <div x-show="historyLoading" style="padding:36px; text-align:center; color:var(--color-ink-mute);">
                <i data-lucide="loader" class="spin" style="width:24px; height:24px; margin:0 auto 8px auto;"></i>
                <div>Memuat riwayat pergerakan stok...</div>
            </div>

            <!-- Content State -->
            <div x-show="!historyLoading" class="space-y-4" style="padding:16px 20px 20px 20px;">
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

                <div style="text-align:right; padding-top:4px;">
                    <button type="button" @click="showHistoryModal = false" class="btn btn-secondary">Tutup</button>
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
        searchQuery: '',
        selectedGroup: '',
        filterStatus: 'all',
        perPage: '25',
        currentPage: 1,
        avgRowHeight: 48,

        showAdjustModal: false,
        showWasteModal: false,
        showHistoryModal: false,
        historyLoading: false,
        itemHistoryList: [],
        selectedItem: {},
        adjustQty: '',
        adjustType: 'opname_lebih',
        wasteQty: '',

        get kpiTotalSku() {
            return this.filteredItems.length;
        },

        get kpiTotalFisik() {
            return this.filteredItems.reduce((acc, item) => acc + (parseFloat(item.stok_fisik_saat_ini) || 0), 0);
        },

        get kpiTotalValuasi() {
            return this.filteredItems.reduce((acc, item) => {
                const stok = parseFloat(item.stok_fisik_saat_ini) || 0;
                const hpp = parseFloat(item.harga_pokok) || 0;
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

        updateRowHeight() {
            const row = this.$refs.tableWrapper?.querySelector('tbody tr:not(.filler-row)');
            if (row && row.offsetHeight > 0) {
                this.avgRowHeight = row.offsetHeight;
            }
        },

        init() {
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
                this.updateRowHeight();
            });
            setTimeout(() => this.updateRowHeight(), 100);
            this.$watch('searchQuery', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$watch('selectedGroup', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$watch('filterStatus', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$watch('perPage', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
        },

        get filteredItems() {
            let res = this.items;

            // Filter Search Text
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase();
                res = res.filter(item => {
                    return (item.nama_item && item.nama_item.toLowerCase().includes(q)) ||
                           (item.kode_sku && item.kode_sku.toLowerCase().includes(q)) ||
                           (item.barcode && item.barcode.toLowerCase().includes(q)) ||
                           (item.barcode_universal && item.barcode_universal.toLowerCase().includes(q)) ||
                           (item.nama_grup && item.nama_grup.toLowerCase().includes(q));
                });
            }

            // Filter Group
            if (this.selectedGroup) {
                res = res.filter(item => item.kode_grup === this.selectedGroup);
            }

            // Filter Stock Status sesuai stok_minimum_peringatan tiap produk
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

            this.currentPage = targetPage;

            this.$nextTick(() => {
                // Instantly clear the temporary minHeight lock after Alpine finishes rendering the new page
                if (tableEl) {
                    tableEl.style.minHeight = '';
                }
                this.updateRowHeight();

                if (paginationTopBefore !== null && paginationEl) {
                    const paginationTopAfter = paginationEl.getBoundingClientRect().top;
                    const delta = paginationTopAfter - paginationTopBefore;

                    // Pixel-perfect compensation: neutralize any shift >= 1px
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

        openAdjust(item) {
            this.selectedItem = item;
            this.adjustQty = '';
            this.adjustType = 'opname_lebih';
            this.showAdjustModal = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        calculateAdjustPreview() {
            const cur = parseFloat(this.selectedItem.stok_fisik_saat_ini) || 0;
            const q = parseFloat(this.adjustQty) || 0;
            if (this.adjustType === 'opname_lebih') {
                const res = cur + q;
                return res % 1 === 0 ? res.toString() : res.toFixed(2);
            } else {
                const res = Math.max(0, cur - q);
                return res % 1 === 0 ? res.toString() : res.toFixed(2);
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

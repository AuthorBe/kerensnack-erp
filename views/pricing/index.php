<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<div class="space-y-5" x-data="pricingApp()">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div class="page-header-body">
            <div class="page-header-icon is-amber">
                <i data-lucide="layers"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#f59e0b;"></span>
                    <span>Manajemen Harga Jual</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Matriks Level Harga Produk' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Pengaturan 30 Tingkat Level Harga Jual Per Bungkus / Pcs' ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="openMasterLevelsModal()" class="btn btn-secondary flex items-center gap-2" style="border-radius:10px;height:38px;padding:0 14px;font-weight:700;font-size:12.5px;" title="Lihat & Ubah Nama Acuan 30 Level Harga">
                <i data-lucide="list-tree" style="width:16px;height:16px;color:var(--color-primary);"></i>
                <span>Daftar 30 Level Acuan</span>
            </button>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="layers"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Grup Produk</div>
                <div class="stat-card-value"><?= count($groups) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:4px;">Grup kemasan aktif</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                <i data-lucide="tags"></i>
            </div>
            <div>
                <div class="stat-card-label">Tingkat Level Harga</div>
                <div class="stat-card-value" style="font-size:18px;">Level 1 – 30</div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:4px;">Master level harga terpusat</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-card-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
                <i data-lucide="users"></i>
            </div>
            <div>
                <div class="stat-card-label">Tier Grup Pelanggan</div>
                <div class="stat-card-value"><?= count($customerGroups) ?> Tier</div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:4px;">Kategori toko terhubung FK</div>
            </div>
        </div>
    </div>

    <!-- MATRIKS HARGA PER GRUP PRODUK -->
    <div class="card p-5 space-y-4">
        <!-- Toolbar Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b" style="border-color:var(--color-hairline); padding-bottom:20px;">
            <!-- Search Box -->
            <div class="form-input-icon w-full md:w-auto flex-1 md:max-w-sm">
                <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                <input type="text" x-model="searchMatrix" class="form-input" style="height:36px;font-size:13px;width:100%;" placeholder="Cari kode / nama grup...">
            </div>

            <div class="grid grid-cols-2 md:flex items-center gap-2 w-full md:w-auto">
                <!-- Buka / Tutup Semua -->
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-lg border border-hairline">
                    <button type="button" @click="expandAll()" class="btn btn-ghost btn-sm flex-1 flex items-center justify-center gap-1" style="font-size:12px;font-weight:600;white-space:nowrap;padding:5px 10px;height:28px;" title="Buka Semua Grup">
                        <i data-lucide="chevrons-down" style="width:14px;height:14px;"></i>
                        <span class="hidden sm:inline">Buka Semua</span>
                        <span class="sm:hidden">Buka</span>
                    </button>
                    <button type="button" @click="collapseAll()" class="btn btn-ghost btn-sm flex-1 flex items-center justify-center gap-1" style="font-size:12px;font-weight:600;white-space:nowrap;padding:5px 10px;height:28px;" title="Tutup Semua Grup">
                        <i data-lucide="chevrons-up" style="width:14px;height:14px;"></i>
                        <span class="hidden sm:inline">Tutup Semua</span>
                        <span class="sm:hidden">Tutup</span>
                    </button>
                </div>

                <!-- Switcher View: Tabel vs Grid -->
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-lg border border-hairline">
                    <button type="button" @click="setViewMode('table')" :class="viewMode === 'table' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'" class="flex-1 flex items-center justify-center gap-1" style="font-size:12px;font-weight:600;white-space:nowrap;padding:5px 10px;height:28px;">
                        <i data-lucide="list" style="width:14px;height:14px;"></i>
                        <span>Tabel</span>
                    </button>
                    <button type="button" @click="setViewMode('grid')" :class="viewMode === 'grid' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'" class="flex-1 flex items-center justify-center gap-1" style="font-size:12px;font-weight:600;white-space:nowrap;padding:5px 10px;height:28px;">
                        <i data-lucide="layout-grid" style="width:14px;height:14px;"></i>
                        <span>Grid</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Group Accordion List -->
        <div class="space-y-3">
            <template x-for="g in filteredGroups" :key="g.id">
                <div class="card p-0 overflow-hidden transition-all duration-200"
                     :style="isOpen(g.id) ? 'border-color:var(--color-primary-soft);box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);' : ''">
                    
                    <!-- Accordion Header Bar -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 cursor-pointer select-none transition-colors"
                         :style="isOpen(g.id) ? 'background-color:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);' : 'background-color:var(--color-canvas);'"
                         @click="toggleGroup(g.id)">
                        
                        <!-- Left Info: Chevron + Kode + Nama -->
                        <div class="flex items-start sm:items-center gap-3 flex-1 min-w-0">
                            <div class="flex items-center justify-center flex-shrink-0 transition-transform duration-200 mt-1 sm:mt-0"
                                 :style="isOpen(g.id) ? 'color:var(--color-primary);transform:rotate(180deg);' : 'color:var(--color-ink-mute);'">
                                <i data-lucide="chevron-down" style="width:20px;height:20px;"></i>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span style="font-size:10px;color:var(--color-ink-mute);font-weight:700;letter-spacing:0.5px;text-transform:uppercase;" x-text="g.kode_grup"></span>
                                    <template x-if="g.barcode_universal">
                                        <div class="flex items-center gap-2">
                                            <span style="font-size:10px;color:var(--color-ink-mute);opacity:0.5;">•</span>
                                            <div class="flex items-center gap-1" style="font-size:10px;color:var(--color-ink-mute);font-family:var(--font-mono);" :title="'Barcode: ' + g.barcode_universal">
                                                <i data-lucide="barcode" style="width:12px;height:12px;"></i>
                                                <span x-text="g.barcode_universal"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <span style="font-weight:700;font-size:14.5px;color:var(--color-ink);line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;" :title="g.nama_grup" x-text="g.nama_grup"></span>
                            </div>
                        </div>

                        <!-- Right Info: Badges & Actions -->
                        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 flex-shrink-0 w-full sm:w-auto" @click.stop>
                            <!-- Badges -->
                            <div class="flex flex-wrap items-center gap-2 flex-1 sm:flex-none">
                                <span class="badge"
                                      :class="getGroupLevelCount(g) > 0 ? 'badge-secondary' : 'badge-danger'"
                                      x-text="getGroupLevelCount(g) + ' Level'"></span>
                                
                                <template x-if="getGroupLevelCount(g) > 0">
                                    <div class="badge badge-success flex items-center gap-1.5 font-mono">
                                        <i data-lucide="tag" style="width:12px;height:12px;"></i>
                                        <span style="font-weight:700;" x-text="getPriceRange(g)"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Add Level Button -->
                            <?php if (\App\Core\Auth::can('master.pricing_manage')): ?>
                            <template x-if="getAvailableLevels(g).length > 0">
                                <button type="button" @click="openAddLevelModal(g)" class="btn btn-primary btn-sm flex-shrink-0 w-full sm:w-auto flex justify-center mt-2 sm:mt-0" style="padding:6px 12px;height:32px;">
                                    <i data-lucide="plus" style="width:14px;height:14px;"></i>
                                    <span>Tambah Level</span>
                                </button>
                            </template>
                            <template x-if="getAvailableLevels(g).length === 0">
                                <span class="badge badge-success w-full sm:w-auto text-center justify-center mt-2 sm:mt-0">30 Level Lengkap</span>
                            </template>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Accordion Content -->
                    <div x-show="isOpen(g.id)" 
                         x-transition:enter="transition ease-out duration-200" 
                         x-transition:enter-start="opacity-0 -translate-y-2" 
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2">
                        
                        <!-- Empty State jika 0 level -->
                        <template x-if="!groupedPrices[g.id] || groupedPrices[g.id].length === 0">
                            <div class="p-4 m-3 bg-red-50/50 dark:bg-red-950/20 border border-dashed border-red-300 dark:border-red-800 rounded-xl flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-red-600 dark:text-red-400">
                                <div class="flex items-center gap-2.5">
                                    <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 text-red-500"></i>
                                    <div>
                                        <strong class="font-bold">Belum ada level harga yang dikonfigurasi.</strong>
                                        <div class="text-slate-500 dark:text-slate-400 mt-0.5">Transaksi POS dan Pesanan B2B untuk grup ini akan ditolak (Pilihan B) sampai minimal Level 1 (Ritel) ditambahkan.</div>
                                    </div>
                                </div>
                                <button type="button" @click="openAddLevelModal(g)" class="btn btn-primary btn-sm h-8 px-3 text-xs whitespace-nowrap">
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    <span>Set Level Sekarang</span>
                                </button>
                            </div>
                        </template>

                        <!-- MODE 1: TABEL RAMPING MODERN -->
                        <template x-if="viewMode === 'table' && groupedPrices[g.id] && groupedPrices[g.id].length > 0">
                            <div class="overflow-x-auto custom-scrollbar">
                                <table class="data-table" style="min-width: 800px; border-top: 1px solid var(--color-hairline);">
                                    <thead>
                                        <tr>
                                            <th style="width:120px;" class="cell-nowrap">Level</th>
                                            <th style="min-width:250px;">Nama / Sasaran Mitra Toko</th>
                                            <th class="cell-right cell-nowrap" style="width:200px;">Harga Jual Satuan</th>
                                            <?php if (\App\Core\Auth::can('master.pricing_manage')): ?>
                                            <th class="cell-center cell-nowrap" style="width:120px;">Aksi</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="p in getGroupPricesSorted(g)" :key="p.id">
                                            <tr>
                                                <!-- Col 1: Level Badge -->
                                                <td class="cell-nowrap">
                                                    <span class="badge badge-mono"
                                                          :class="Number(p.level_harga) === 1 ? 'badge-primary' : 'badge-secondary'"
                                                          x-text="'Level ' + p.level_harga">
                                                    </span>
                                                </td>

                                                <!-- Col 2: Nama & Deskripsi Master -->
                                                <td>
                                                    <div style="font-weight:700;color:var(--color-ink);" x-text="p.nama_level || ('Level ' + p.level_harga)"></div>
                                                    <template x-if="getMasterDesc(p.level_harga)">
                                                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;" x-text="getMasterDesc(p.level_harga)"></div>
                                                    </template>
                                                </td>

                                                <!-- Col 3: Harga Satuan Pcs -->
                                                <td class="cell-right cell-nowrap">
                                                    <div style="font-weight:700;font-family:var(--font-mono);font-size:14px;color:var(--color-ink);" x-text="formatRupiah(p.harga_jual_pcs)"></div>
                                                    <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">per bungkus (pcs)</div>
                                                </td>

                                                <?php if (\App\Core\Auth::can('master.pricing_manage')): ?>
                                                <!-- Col 4: Aksi -->
                                                <td class="cell-center cell-nowrap">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button type="button" @click="openEditLevelModal(g, p)" class="btn btn-ghost btn-sm" style="padding:6px 10px;" title="Ubah Harga / Nama">
                                                            <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                                        </button>
                                                        
                                                        <button type="button" @click="deleteLevel(p.id, p.level_harga)" class="btn btn-ghost btn-sm" style="padding:6px 10px;color:var(--color-danger);" title="Hapus Level">
                                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <!-- MODE 2: KARTU GRID MODERN -->
                        <template x-if="viewMode === 'grid' && groupedPrices[g.id] && groupedPrices[g.id].length > 0">
                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 bg-slate-50/50 dark:bg-slate-900/50 border-t border-hairline">
                                <template x-for="p in getGroupPricesSorted(g)" :key="p.id">
                                    <div class="card p-4 flex flex-col justify-between transition-shadow hover:shadow-sm" style="border-radius:10px;">
                                        <div>
                                            <div class="flex items-center justify-between gap-2 mb-3">
                                                <span class="badge badge-mono"
                                                      :class="Number(p.level_harga) === 1 ? 'badge-primary' : 'badge-secondary'"
                                                      x-text="'Level ' + p.level_harga"></span>
                                                <?php if (\App\Core\Auth::can('master.pricing_manage')): ?>
                                                <div class="flex items-center gap-1">
                                                    <button type="button" @click="openEditLevelModal(g, p)" class="btn btn-ghost btn-sm" style="padding:4px 6px;" title="Ubah">
                                                        <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                                    </button>
                                                    <button type="button" @click="deleteLevel(p.id, p.level_harga)" class="btn btn-ghost btn-sm" style="padding:4px 6px;color:var(--color-danger);" title="Hapus">
                                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                                    </button>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-weight:700;font-size:14px;color:var(--color-ink);margin-bottom:4px;" class="truncate" :title="p.nama_level || ('Level ' + p.level_harga)" x-text="p.nama_level || ('Level ' + p.level_harga)"></div>
                                            <div style="font-size:11px;color:var(--color-ink-mute);min-height:32px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;" x-text="getMasterDesc(p.level_harga)"></div>
                                        </div>
                                        
                                        <div class="mt-4 pt-3 border-t border-hairline flex items-center justify-between">
                                            <span style="font-size:11px;color:var(--color-ink-mute);">Harga Satuan</span>
                                            <div class="text-right">
                                                <span style="font-weight:700;font-family:var(--font-mono);font-size:14px;color:var(--color-ink);" x-text="formatRupiah(p.harga_jual_pcs)"></span>
                                                <span style="font-size:10px;color:var(--color-ink-mute);">/pcs</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- MODAL: ATUR / TAMBAH LEVEL HARGA PRODUK -->
    <?php if (\App\Core\Auth::can('master.pricing_manage')): ?>
    <template x-teleport="body">
    <div x-show="showLevelModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:480px;padding:24px;">
            <div class="modal-header">
                <div>
                    <div class="modal-title" x-text="isEditLevel ? ('Ubah Harga Level ' + levelForm.level_harga) : 'Tambah Level Harga Baru'"></div>
                    <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;" x-text="selectedGroup?.nama_grup"></div>
                </div>
            </div>

            <form action="<?= Router::url('/pricing/update-level') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="id" :value="levelForm.id">
                <input type="hidden" name="grup_produk_id" :value="selectedGroup?.id">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Tingkat Level -->
                    <div>
                        <label class="form-label">Tingkat Level Harga (1–30) *</label>

                        <!-- Tampilan Mode Tambah: Hanya level yang belum terdaftar di grup ini -->
                        <template x-if="!isEditLevel">
                            <select name="level_harga" x-model.number="levelForm.level_harga" @change="onLevelNumberChange()" class="form-input">
                                <template x-for="lvl in getAvailableLevels(selectedGroup)" :key="lvl.level_nomor">
                                    <option :value="lvl.level_nomor" x-text="'Level ' + lvl.level_nomor"></option>
                                </template>
                            </select>
                        </template>

                        <!-- Tampilan Mode Ubah: Nomor level terkunci paten (tidak bisa dibajak) -->
                        <template x-if="isEditLevel">
                            <div>
                                <input type="hidden" name="level_harga" :value="levelForm.level_harga">
                                <div class="form-input font-mono font-bold" style="background:var(--color-canvas-soft);color:var(--color-ink);display:flex;align-items:center;justify-content:space-between;cursor:not-allowed;">
                                    <span x-text="'Level ' + levelForm.level_harga"></span>
                                    <span class="badge badge-mono text-xs" style="font-size:10px;">Terkunci</span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Nama / Label Level -->
                    <div>
                        <label class="form-label">Nama / Label Level *</label>
                        <input type="text" name="nama_level" x-model="levelForm.nama_level" required class="form-input" placeholder="Contoh: Level 8 - Grosir Mitra">
                    </div>
                </div>

                <div>
                    <label class="form-label">Harga Jual Satuan per Bungkus / Pcs (Rp) *</label>
                    <input type="text" name="harga_jual_pcs" x-model="levelForm.harga_jual_pcs" required class="form-input font-mono input-rupiah" placeholder="15.000">
                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;">
                        Semua transaksi POS kasir dan penjualan B2B dihitung murni berdasarkan harga satuan pcs.
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
                    <button type="button" @click="showLevelModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span x-text="isEditLevel ? 'Simpan Perubahan' : 'Simpan Level Harga'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- MODAL: DAFTAR 30 MASTER LEVEL HARGA ACUAN -->
    <template x-teleport="body">
    <div x-show="showMasterLevelsModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:720px;padding:24px;max-height:90vh;display:flex;flex-direction:column;">
            <div class="modal-header" style="flex-shrink:0;">
                <div>
                    <div class="modal-title flex items-center gap-2">
                        <i data-lucide="list-tree" style="width:20px;height:20px;color:var(--color-primary);"></i>
                        <span>Daftar 30 Level Harga Acuan Sistem</span>
                    </div>
                    <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">
                        Kustomisasi label nama dan deskripsi peruntukan untuk masing-masing Level 1 sampai 30
                    </div>
                </div>
            </div>

            <!-- Search box for master levels -->
            <div style="margin-bottom:12px;flex-shrink:0;">
                <input type="text" x-model="masterLevelSearch" class="form-input" style="height:36px;font-size:12.5px;width:100%;" placeholder="Cari nomor level / nama acuan...">
            </div>

            <div class="overflow-y-auto custom-scrollbar flex-1" style="border:1px solid var(--color-hairline);border-radius:10px;">
                <table class="data-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th style="width:80px;" class="cell-nowrap">Level</th>
                            <th>Nama Acuan Level</th>
                            <th>Deskripsi / Sasaran Mitra</th>
                            <?php if (\App\Core\Auth::can('master.pricing_manage')): ?>
                            <th class="cell-center cell-nowrap" style="width:90px;">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="ml in filteredMasterLevels" :key="ml.level_nomor">
                            <tr>
                                <td class="cell-nowrap">
                                    <span class="badge badge-mono" :class="Number(ml.level_nomor) === 1 ? 'badge-primary' : 'badge-secondary'" x-text="'Level ' + ml.level_nomor"></span>
                                </td>
                                <td>
                                    <strong style="color:var(--color-ink);" x-text="ml.nama_level || ('Level ' + ml.level_nomor)"></strong>
                                </td>
                                <td style="font-size:12px;color:var(--color-ink-mute);" x-text="ml.deskripsi || '-'"></td>
                                <?php if (\App\Core\Auth::can('master.pricing_manage')): ?>
                                <td class="cell-center cell-nowrap">
                                    <button type="button" @click="editMasterLevel(ml)" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:11px;font-weight:700;">
                                        <i data-lucide="edit-3" style="width:12px;height:12px;"></i>
                                        <span>Ubah</span>
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:14px;flex-shrink:0;">
                <button type="button" @click="showMasterLevelsModal = false" class="btn btn-secondary">Tutup</button>
            </div>
        </div>
    </div>
    </template>

    <!-- MODAL: EDIT SINGLE MASTER LEVEL -->
    <?php if (\App\Core\Auth::can('master.pricing_manage')): ?>
    <template x-teleport="body">
    <div x-show="showEditMasterModal" x-cloak class="modal-backdrop" style="z-index:99999;">
        <div class="modal-box" style="max-width:480px;padding:24px;">
            <div class="modal-header">
                <div>
                    <div class="modal-title" x-text="'Ubah Label Level ' + editingMasterLevel.level_nomor"></div>
                    <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">Kustomisasi nama acuan level yang tampil di sistem & Excel</div>
                </div>
            </div>

            <form action="<?= Router::url('/pricing/update-master-level') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="level_nomor" :value="editingMasterLevel.level_nomor">

                <div>
                    <label class="form-label">Nama / Label Level Acuan *</label>
                    <input type="text" name="nama_level" x-model="editingMasterLevel.nama_level" required class="form-input" placeholder="Contoh: Level 8 - Grosir Mitra">
                </div>

                <div>
                    <label class="form-label">Deskripsi / Peruntukan Sasaran Mitra</label>
                    <textarea name="deskripsi" x-model="editingMasterLevel.deskripsi" rows="3" class="form-input" placeholder="Contoh: Khusus grosir mitra warung pembelian minimal 5 karton..."></textarea>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
                    <button type="button" @click="showEditMasterModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span>Simpan Nama Acuan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>
    <?php endif; ?>

    <!-- FORM SUBMIT HIDDEN FOR DELETE -->
    <form id="delete-level-form" action="<?= Router::url('/pricing/delete-level') ?>" method="POST" data-action-text="Menghapus level harga..." style="display:none;">
        <input type="hidden" name="id" id="delete-level-id">
    </form>
    <?php endif; ?>

</div>

<script>
function pricingApp() {
    return {
        searchMatrix: '',
        groups: <?= json_encode($groups) ?>,
        groupedPrices: <?= json_encode($groupedPrices) ?>,
        masterLevels: <?= json_encode($masterLevels) ?>,

        // Master Levels Modal
        showMasterLevelsModal: false,
        showEditMasterModal: false,
        masterLevelSearch: '',
        editingMasterLevel: {
            level_nomor: 1,
            nama_level: '',
            deskripsi: ''
        },

        get filteredMasterLevels() {
            const q = (this.masterLevelSearch || '').toLowerCase().trim();
            if (!q) return this.masterLevels;
            return this.masterLevels.filter(ml => {
                const name = (ml.nama_level || '').toLowerCase();
                const desc = (ml.deskripsi || '').toLowerCase();
                const num = String(ml.level_nomor);
                return name.includes(q) || desc.includes(q) || num.includes(q);
            });
        },

        openMasterLevelsModal() {
            this.masterLevelSearch = '';
            this.showMasterLevelsModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        editMasterLevel(ml) {
            this.editingMasterLevel = {
                level_nomor: ml.level_nomor,
                nama_level: ml.nama_level || ('Level ' + ml.level_nomor),
                deskripsi: ml.deskripsi || ''
            };
            this.showEditMasterModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        // Tampilan & Accordion (Default SEMUA Grup Tertutup)
        viewMode: (function() {
            try {
                return localStorage.getItem('pricing_view_mode') || 'table';
            } catch (e) {
                return 'table';
            }
        })(),
        openGroups: {}, // Kosong secara default -> Semua grup tertutup saat pertama kali buka

        // Modal Level
        showLevelModal: false,
        isEditLevel: false,
        selectedGroup: null,
        levelForm: {
            id: '',
            level_harga: 1,
            nama_level: '',
            harga_jual_pcs: '15.000'
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        setViewMode(mode) {
            this.viewMode = mode;
            try {
                localStorage.setItem('pricing_view_mode', mode);
            } catch (e) {}
            this.$nextTick(() => lucide.createIcons());
        },

        isOpen(id) {
            return !!this.openGroups[id];
        },

        toggleGroup(id) {
            this.openGroups[id] = !this.openGroups[id];
            this.$nextTick(() => lucide.createIcons());
        },

        expandAll() {
            const map = {};
            this.groups.forEach(g => {
                map[g.id] = true;
            });
            this.openGroups = map;
            this.$nextTick(() => lucide.createIcons());
        },

        collapseAll() {
            this.openGroups = {};
            this.$nextTick(() => lucide.createIcons());
        },

        get filteredGroups() {
            if (!this.searchMatrix.trim()) return this.groups;
            const q = this.searchMatrix.toLowerCase();
            return this.groups.filter(g =>
                g.nama_grup.toLowerCase().includes(q) ||
                g.kode_grup.toLowerCase().includes(q) ||
                (g.barcode_universal && g.barcode_universal.includes(q))
            );
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        getGroupLevelCount(group) {
            if (!group) return 0;
            return (this.groupedPrices[group.id] || []).length;
        },

        getGroupPricesSorted(group) {
            if (!group) return [];
            const list = this.groupedPrices[group.id] || [];
            return list.slice().sort((a, b) => Number(a.level_harga) - Number(b.level_harga));
        },

        getPriceRange(group) {
            if (!group) return 'Belum Diset';
            const list = this.groupedPrices[group.id] || [];
            if (list.length === 0) return 'Belum Diset';
            const prices = list.map(p => Number(p.harga_jual_pcs) || 0);
            const min = Math.min(...prices);
            const max = Math.max(...prices);
            if (min === max) {
                return this.formatRupiah(min) + ' / pcs';
            }
            return this.formatRupiah(min) + ' – ' + this.formatRupiah(max) + ' / pcs';
        },

        getMasterDesc(level) {
            const m = this.masterLevels.find(ml => Number(ml.level_nomor) === Number(level));
            return m ? (m.deskripsi || '') : '';
        },

        getAvailableLevels(group) {
            if (!group) return this.masterLevels;
            const existing = (this.groupedPrices[group.id] || []).map(p => Number(p.level_harga));
            return this.masterLevels.filter(ml => !existing.includes(Number(ml.level_nomor)));
        },

        openAddLevelModal(g) {
            this.selectedGroup = g;
            this.isEditLevel = false;
            const available = this.getAvailableLevels(g);
            if (available.length === 0) {
                alert('Seluruh 30 level harga sudah lengkap dikonfigurasi untuk grup ini.');
                return;
            }

            const firstAvailable = available[0];
            this.levelForm = {
                id: '',
                level_harga: Number(firstAvailable.level_nomor),
                nama_level: firstAvailable.nama_level,
                harga_jual_pcs: '15.000'
            };
            this.openGroups[g.id] = true;
            this.showLevelModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditLevelModal(g, p) {
            this.selectedGroup = g;
            this.isEditLevel = true;
            this.openGroups[g.id] = true;
            this.levelForm = {
                id: p.id,
                level_harga: Number(p.level_harga),
                nama_level: p.nama_level || ('Level ' + p.level_harga),
                harga_jual_pcs: window.formatRupiahNumber ? window.formatRupiahNumber(p.harga_jual_pcs) : String(p.harga_jual_pcs)
            };
            this.showLevelModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        onLevelNumberChange() {
            const level = this.levelForm.level_harga;
            if (!this.isEditLevel) {
                const found = this.masterLevels.find(m => Number(m.level_nomor) === Number(level));
                if (found) {
                    this.levelForm.nama_level = found.nama_level;
                }
            }
        },

        async deleteLevel(id, levelNumber) {
            if (Number(levelNumber) === 1) {
                alert('Level 1 (Ritel Standar) adalah harga dasar acuan utama sistem dan tidak boleh dihapus.');
                return;
            }

            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Level Harga',
                message: 'Apakah Anda yakin ingin menghapus level harga ini dari grup produk? Pastikan level ini tidak sedang dipakai oleh grup pelanggan aktif.',
                type: 'danger',
                confirmText: 'Ya, Hapus'
            }) : confirm('Hapus level harga ini?');

            if (confirmed) {
                document.getElementById('delete-level-id').value = id;
                document.getElementById('delete-level-form').submit();
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

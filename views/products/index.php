<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
$activeTab = $_GET['tab'] ?? 'finished_goods';
?>

<div x-data="productApp('<?= htmlspecialchars($activeTab) ?>', '<?= htmlspecialchars($selectedRecipeItemId ?? '') ?>')" x-init="init()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-indigo">
                <i data-lucide="boxes"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot"></span>
                    <span>Master Katalog &amp; Produksi</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Master Produk, Bahan &amp; Resep BOM' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Katalog Barang Jadi, Bahan Baku Curah, Kemasan &amp; Resep BOM' ?></p>
            </div>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;padding:14px 18px;">
            <div class="stat-card-icon shrink-0" style="background:rgba(59,130,246,0.1);color:#3b82f6;width:42px;height:42px;">
                <i data-lucide="package" style="width:20px;height:20px;"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="stat-card-label" style="font-size:11px;margin-bottom:2px;">Barang Jadi (Siap Jual)</div>
                <div class="flex items-baseline gap-1.5">
                    <span style="font-size:22px;font-weight:900;color:#3b82f6;font-family:var(--font-mono);line-height:1.1;"><?= number_format($totalAllFinishedGoods ?? ($paginationFg['total'] ?? count($finishedGoods)), 0, ',', '.') ?></span>
                    <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">SKU</span>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;"><?= count($groups) ?> Grup Kemasan Universal</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;padding:14px 18px;">
            <div class="stat-card-icon shrink-0" style="background:rgba(62,207,142,0.1);color:var(--color-primary);width:42px;height:42px;">
                <i data-lucide="boxes" style="width:20px;height:20px;"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="stat-card-label" style="font-size:11px;margin-bottom:2px;">Bahan Baku &amp; Kemasan</div>
                <div class="flex items-baseline gap-1.5">
                    <span style="font-size:22px;font-weight:900;color:var(--color-primary);font-family:var(--font-mono);line-height:1.1;"><?= count($materials) ?></span>
                    <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">Item</span>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Bal Curah, Plastik &amp; Stiker</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;padding:14px 18px;">
            <div class="stat-card-icon shrink-0" style="background:rgba(245,158,11,0.1);color:#f59e0b;width:42px;height:42px;">
                <i data-lucide="git-merge" style="width:20px;height:20px;"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="stat-card-label" style="font-size:11px;margin-bottom:2px;">Resep BOM Terhubung</div>
                <div class="flex items-baseline gap-1.5">
                    <span style="font-size:22px;font-weight:900;color:#f59e0b;font-family:var(--font-mono);line-height:1.1;"><?= count($recipes) ?></span>
                    <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">Relasi</span>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Auto-Potong Bahan Repacking</div>
            </div>
        </div>
    </div>

    <!-- SUB-TABS NAVIGATION (Single Line Segmented Control) -->
    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-lg border border-hairline overflow-x-auto no-scrollbar w-full sm:w-auto">
        <button type="button"
                @click="activeTab = 'finished_goods'"
                :class="activeTab === 'finished_goods' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                style="font-size:12px;font-weight:700;white-space:nowrap;padding:6px 12px;">
            <i data-lucide="package" style="width:14px;height:14px;"></i>
            <span>1. Barang Jadi (<?= number_format($totalAllFinishedGoods ?? ($paginationFg['total'] ?? count($finishedGoods)), 0, ',', '.') ?>)</span>
        </button>

        <button type="button"
                @click="activeTab = 'materials'"
                :class="activeTab === 'materials' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                style="font-size:12px;font-weight:700;white-space:nowrap;padding:6px 12px;">
            <i data-lucide="boxes" style="width:14px;height:14px;"></i>
            <span>2. Bahan &amp; Kemasan (<?= count($materials) ?>)</span>
        </button>

        <button type="button"
                @click="activeTab = 'recipes'"
                :class="activeTab === 'recipes' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                style="font-size:12px;font-weight:700;white-space:nowrap;padding:6px 12px;">
            <i data-lucide="git-merge" style="width:14px;height:14px;"></i>
            <span>3. Resep / BOM Repacking</span>
        </button>

        <button type="button"
                @click="activeTab = 'borongan'"
                :class="activeTab === 'borongan' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                style="font-size:12px;font-weight:700;white-space:nowrap;padding:6px 12px;">
            <i data-lucide="badge-percent" style="width:14px;height:14px;"></i>
            <span>4. Upah Borongan (<?= count($wageGroups) ?>)</span>
        </button>

        <button type="button"
                @click="activeTab = 'brands'"
                :class="activeTab === 'brands' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                style="font-size:12px;font-weight:700;white-space:nowrap;padding:6px 12px;">
            <i data-lucide="tag" style="width:14px;height:14px;"></i>
            <span>5. Merek Produk (<?= count($brands ?? []) ?>)</span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: KATALOG BARANG JADI (FINISHED GOODS)                               -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'finished_goods'" class="card" style="padding:0;overflow:hidden;">
        <!-- ACTION & FILTER BAR (Server-Synchronized) -->
        <form method="GET" action="<?= Router::url('/products') ?>" class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <input type="hidden" name="tab" value="finished_goods">
            <div class="flex items-center flex-wrap gap-2 w-full sm:w-auto flex-1">
                <div class="form-input-icon flex-1 sm:max-w-xs">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="text" name="q_fg" value="<?= htmlspecialchars($paginationFg['q'] ?? '') ?>" placeholder="Cari snack / varian / barcode..." class="form-input" style="height:38px;font-size:13px;">
                </div>

                <select name="group_id" onchange="this.form.submit()" class="form-input" style="height:38px;font-size:13px;max-width:240px;">
                    <option value="all">Semua Grup Kemasan</option>
                    <?php foreach ($groups as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= ($selectedGroupId ?? '') === $g['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['nama_grup']) ?> (<?= $g['total_sku'] ?> SKU)
                    </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-secondary btn-sm" style="height:38px;padding:0 12px;">
                    <i data-lucide="filter" style="width:14px;height:14px;"></i>
                    <span>Cari</span>
                </button>

                <?php if (!empty($paginationFg['q']) || (!empty($selectedGroupId) && $selectedGroupId !== 'all')): ?>
                <a href="<?= Router::url('/products?tab=finished_goods') ?>" class="btn btn-ghost btn-sm" style="height:38px;padding:0 10px;color:var(--color-ink-mute);" title="Reset Filter">
                    <i data-lucide="x-circle" style="width:14px;height:14px;"></i>
                    <span>Reset</span>
                </a>
                <?php endif; ?>
            </div>

            <?php if (\App\Core\Auth::can('master.products_manage')): ?>
            <div class="flex items-center gap-2">
                <button type="button" @click="openManageGroupsModal()" class="btn btn-secondary" style="height:38px;" title="Kelola / Edit Grup Kemasan">
                    <i data-lucide="folder-cog" style="width:14px;height:14px;"></i>
                    <span>Kelola Grup</span>
                </button>
                <button type="button" @click="openAddItemModal()" class="btn btn-primary" style="height:38px;">
                    <i data-lucide="plus" style="width:14px;height:14px;"></i>
                    <span>Tambah Barang Jadi</span>
                </button>
            </div>
            <?php endif; ?>
        </form>

        <!-- TABLE LIST BARANG JADI (Hierarki Jelas & Anti-Duplikasi) -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 960px;">
                <thead>
                    <tr>
                        <th style="width:90px; min-width:85px;" class="cell-nowrap">SKU</th>
                        <th style="min-width:260px;">Barang Jadi &amp; Grup Kemasan Universal</th>
                        <th style="min-width:140px;">Upah Bungkus</th>
                        <th class="cell-center cell-nowrap" style="width:115px;">Resep BOM</th>
                        <th class="cell-right cell-nowrap" style="width:130px;">Estimasi HPP</th>
                        <th class="cell-right cell-nowrap" style="width:125px;">Harga Ritel (L1)</th>
                        <th class="cell-center cell-nowrap" style="width:110px;">Stok Fisik</th>
                        <?php if (\App\Core\Auth::can('master.products_manage')): ?>
                        <th class="cell-center cell-nowrap" style="width:90px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in finishedGoods" :key="item.id">
                        <tr :style="!item.status_aktif ? 'opacity:0.5;' : ''">
                            <td class="cell-nowrap">
                                <span class="badge badge-mono" style="font-weight:700;" x-text="item.kode_sku"></span>
                            </td>
                            <td>
                                <div style="font-weight:700;font-size:13.5px;color:var(--color-ink);" x-text="item.nama_item"></div>
                                <div class="flex items-center flex-wrap gap-1.5 mt-1">
                                    <template x-if="item.nama_merek">
                                        <span class="badge" style="font-size:10px;padding:1px 5px;background:rgba(245,158,11,0.08);color:#d97706;border:1px solid rgba(245,158,11,0.25);font-weight:700;">
                                            <i data-lucide="tag" style="width:10px;height:10px;margin-right:2px;display:inline-block;vertical-align:middle;"></i>
                                            <span x-text="item.nama_merek"></span>
                                        </span>
                                    </template>
                                    <span class="badge" style="font-size:10.5px;padding:1px 6px;background:rgba(99,102,241,0.08);color:#6366f1;border:1px solid rgba(99,102,241,0.2);">
                                        <i data-lucide="package" style="width:11px;height:11px;margin-right:3px;display:inline-block;vertical-align:middle;"></i>
                                        <span x-text="item.nama_grup || 'Tanpa Grup'"></span>
                                    </span>
                                    <span style="font-size:11px;font-family:var(--font-mono);color:var(--color-ink-mute);" x-text="'Barcode: ' + (item.barcode_universal || '-')"></span>
                                </div>
                            </td>
                            <td class="cell-nowrap">
                                <template x-if="Number(item.upah_bungkus_efektif) > 0">
                                    <div>
                                        <span class="badge" style="font-family:var(--font-mono);font-weight:700;background:rgba(16,185,129,0.08);color:#10b981;border:1px solid rgba(16,185,129,0.2);" x-text="formatRupiah(item.upah_bungkus_efektif) + '/pack'"></span>
                                        <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;" x-text="item.nama_kelompok || 'Tarif Kelompok'"></div>
                                    </div>
                                </template>
                                <template x-if="!item.upah_bungkus_efektif || Number(item.upah_bungkus_efektif) == 0">
                                    <span style="color:var(--color-ink-mute);font-size:12px;">-</span>
                                </template>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <template x-if="item.total_resep_bahan > 0">
                                    <button @click="selectProductForRecipe(item.id)" class="badge badge-success" style="cursor:pointer;" title="Klik untuk kelola komposisi resep">
                                        <i data-lucide="git-merge" style="width:11px;height:11px;margin-right:3px;display:inline-block;vertical-align:middle;"></i>
                                        <span x-text="item.total_resep_bahan + ' Bahan'"></span>
                                    </button>
                                </template>
                                <template x-if="!item.total_resep_bahan || item.total_resep_bahan == 0">
                                    <button @click="selectProductForRecipe(item.id)" class="badge badge-warning" style="cursor:pointer;" title="Klik untuk atur resep bahan baku">
                                        <i data-lucide="alert-circle" style="width:11px;height:11px;margin-right:3px;display:inline-block;vertical-align:middle;"></i>
                                        <span>Belum Ada</span>
                                    </button>
                                </template>
                            </td>
                            <td class="cell-currency cell-right cell-nowrap">
                                <template x-if="(Number(item.estimasi_biaya_bahan || 0) + Number(item.upah_bungkus_efektif || 0)) > 0">
                                    <div>
                                        <div style="font-weight:700;font-family:var(--font-mono);color:var(--color-ink);" x-text="formatRupiah(Number(item.estimasi_biaya_bahan || 0) + Number(item.upah_bungkus_efektif || 0))"></div>
                                        <div style="font-size:9.5px;color:var(--color-ink-mute);" x-text="'Bahan: ' + formatRupiah(item.estimasi_biaya_bahan) + ' + Upah: ' + formatRupiah(item.upah_bungkus_efektif)"></div>
                                    </div>
                                </template>
                                <template x-if="(Number(item.estimasi_biaya_bahan || 0) + Number(item.upah_bungkus_efektif || 0)) == 0">
                                    <div>
                                        <div style="font-weight:700;font-family:var(--font-mono);color:var(--color-ink-mute);" x-text="formatRupiah(item.harga_pokok_pembelian)"></div>
                                        <div style="font-size:9.5px;color:var(--color-ink-mute);">HPP Manual Acuan</div>
                                    </div>
                                </template>
                            </td>
                            <td class="cell-currency cell-right cell-nowrap">
                                <template x-if="item.harga_jual_ritel && Number(item.harga_jual_ritel) > 0">
                                    <span style="font-family:var(--font-mono);font-weight:700;color:var(--color-primary);" x-text="formatRupiah(item.harga_jual_ritel)"></span>
                                </template>
                                <template x-if="!item.harga_jual_ritel || Number(item.harga_jual_ritel) == 0">
                                    <span style="color:var(--color-ink-mute);font-size:12px;">-</span>
                                </template>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <div>
                                    <span class="badge"
                                          :class="{
                                              'badge-danger': Number(item.stok_fisik_saat_ini) <= 0,
                                              'badge-warning': Number(item.stok_fisik_saat_ini) > 0 && Number(item.stok_fisik_saat_ini) <= Number(item.stok_minimum_peringatan),
                                              'badge-success': Number(item.stok_fisik_saat_ini) > Number(item.stok_minimum_peringatan)
                                          }"
                                          style="font-family:var(--font-mono);font-size:11.5px;font-weight:700;"
                                          x-text="formatQty(item.stok_fisik_saat_ini) + ' ' + (item.satuan_dasar || 'pcs')"></span>
                                </div>
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;" x-text="'Min: ' + formatQty(item.stok_minimum_peringatan)"></div>
                            </td>
                            <?php if (\App\Core\Auth::can('master.products_manage')): ?>
                            <td class="cell-center cell-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="openEditItemModal(item)" class="btn btn-ghost btn-sm" style="padding:6px 8px;" title="Edit Barang Jadi">
                                        <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                    </button>
                                    <button @click="deleteItem(item.id, item.nama_item)" class="btn btn-ghost btn-sm" style="padding:6px 8px;color:#ef4444;" title="Hapus Barang Jadi">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    </template>

                    <template x-if="finishedGoods.length === 0">
                        <tr>
                            <td colspan="<?= \App\Core\Auth::can('master.products_manage') ? 8 : 7 ?>" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="search-x" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Tidak ada Barang Jadi yang cocok dengan pencarian / filter</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION BAR FINISHED GOODS -->
        <?php if (!empty($paginationFg) && $paginationFg['totalPages'] > 1): ?>
        <div style="padding:12px 16px;background:var(--color-canvas-soft, #f8fafc);border-top:1px solid var(--color-hairline);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <div style="font-size:12px;color:var(--color-ink-mute);">
                Menampilkan Halaman <strong><?= $paginationFg['page'] ?></strong> dari <strong><?= $paginationFg['totalPages'] ?></strong> (Total <?= number_format($paginationFg['total'], 0, ',', '.') ?> barang jadi)
            </div>
            <div style="display:flex;gap:6px;">
                <?php if ($paginationFg['page'] > 1): ?>
                <a href="<?= Router::url('/products?' . http_build_query(array_merge($_GET, ['page_fg' => $paginationFg['page'] - 1, 'tab' => 'finished_goods']))) ?>" class="btn btn-secondary btn-sm" style="font-size:12px;">
                    &laquo; Sebelumnya
                </a>
                <?php endif; ?>
                <?php if ($paginationFg['page'] < $paginationFg['totalPages']): ?>
                <a href="<?= Router::url('/products?' . http_build_query(array_merge($_GET, ['page_fg' => $paginationFg['page'] + 1, 'tab' => 'finished_goods']))) ?>" class="btn btn-secondary btn-sm" style="font-size:12px;">
                    Selanjutnya &raquo;
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: MASTER BAHAN BAKU & KEMASAN (RAW MATERIALS & PACKAGING)            -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'materials'" class="card" style="padding:0;overflow:hidden;">
        <!-- ACTION & FILTER BAR -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="flex items-center gap-3 w-full sm:w-auto flex-1">
                <div class="form-input-icon flex-1 sm:max-w-xs">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="text" x-model="searchMat" placeholder="Cari nama bahan mentah / kemasan..." class="form-input" style="height:38px;font-size:13px;">
                </div>

                <select x-model="materialTypeFilter" class="form-input" style="height:38px;font-size:13px;max-width:200px;">
                    <option value="all">Semua Kategori Bahan</option>
                    <option value="bahan_mentah">Bahan Mentah Curah (Bal/Kg)</option>
                    <option value="bahan_kemas">Bahan Kemasan (Plastik/Label/Cup)</option>
                </select>
            </div>

            <?php if (\App\Core\Auth::can(['master.materials_manage', 'master.products_manage'])): ?>
            <div>
                <button @click="openAddMaterialModal()" class="btn btn-primary" style="height:38px;">
                    <i data-lucide="plus"></i>
                    <span>Tambah Bahan / Kemasan</span>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- TABLE LIST BAHAN BAKU -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 960px;">
                <thead>
                    <tr>
                        <th style="width:110px; min-width:90px;" class="cell-nowrap">Kode Bahan</th>
                        <th style="min-width:180px;">Nama Bahan / Kemasan</th>
                        <th style="min-width:120px;">Kategori</th>
                        <th style="min-width:90px;">Satuan</th>
                        <th style="min-width:160px;">Vendor Pemasok Utama</th>
                        <th class="cell-right cell-nowrap" style="width:130px; min-width:120px;">HPP Beli Vendor</th>
                        <th class="cell-center cell-nowrap" style="width:110px; min-width:100px;">Stok Fisik</th>
                        <?php if (\App\Core\Auth::can(['master.materials_manage', 'master.products_manage'])): ?>
                        <th class="cell-center cell-nowrap" style="width:100px; min-width:90px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="mat in filteredMaterials" :key="mat.id">
                        <tr :style="!mat.status_aktif ? 'opacity:0.5;' : ''">
                            <td class="cell-nowrap">
                                <span class="badge badge-mono" x-text="mat.kode_sku"></span>
                            </td>
                            <td>
                                <div style="font-weight:700;color:var(--color-ink);" x-text="mat.nama_item"></div>
                                <div style="font-size:11px;color:var(--color-ink-mute);" x-text="'Dipakai di ' + (mat.dipakai_di_resep || 0) + ' resep produk'"></div>
                            </td>
                            <td>
                                <span class="badge"
                                      :class="mat.tipe_item === 'bahan_mentah' ? 'badge-warning' : 'badge-secondary'"
                                      x-text="mat.tipe_item === 'bahan_mentah' ? 'Curah Mentah (Bal/Kg)' : 'Bahan Kemas (Plastik/Label)'"></span>
                            </td>
                            <td class="cell-nowrap">
                                <span style="font-weight:600;font-family:var(--font-mono);" x-text="mat.satuan_dasar"></span>
                            </td>
                            <td>
                                <div style="font-weight:600;" x-text="mat.nama_pemasok || '-'"></div>
                                <div style="font-size:10.5px;color:var(--color-ink-mute);" x-text="mat.kode_pemasok || ''"></div>
                            </td>
                            <td class="cell-currency cell-right cell-nowrap" x-text="formatRupiah(mat.harga_pokok_pembelian)"></td>
                            <td class="cell-center cell-nowrap">
                                <div>
                                    <span class="badge"
                                          :class="{
                                              'badge-danger': Number(mat.stok_fisik_saat_ini) <= 0,
                                              'badge-warning': Number(mat.stok_fisik_saat_ini) > 0 && Number(mat.stok_fisik_saat_ini) <= Number(mat.stok_minimum_peringatan),
                                              'badge-success': Number(mat.stok_fisik_saat_ini) > Number(mat.stok_minimum_peringatan)
                                          }"
                                          style="font-family:var(--font-mono);font-size:11.5px;font-weight:700;"
                                          x-text="formatQty(mat.stok_fisik_saat_ini) + ' ' + mat.satuan_dasar"></span>
                                </div>
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;" x-text="'Min: ' + formatQty(mat.stok_minimum_peringatan)"></div>
                            </td>
                            <?php if (\App\Core\Auth::can(['master.materials_manage', 'master.products_manage'])): ?>
                            <td class="cell-center cell-nowrap">
                                <div style="display:flex;align-items:center;justify-content:center;gap:4px;">
                                    <button @click="openEditMaterialModal(mat)" class="btn btn-ghost btn-sm" style="padding:6px;" title="Edit Bahan">
                                        <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                    </button>
                                    <button @click="deleteMaterial(mat.id, mat.nama_item)" class="btn btn-ghost btn-sm" style="padding:6px;color:#ef4444;" title="Hapus Bahan">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    </template>

                    <template x-if="filteredMaterials.length === 0">
                        <tr>
                            <td colspan="<?= \App\Core\Auth::can(['master.materials_manage', 'master.products_manage']) ? 8 : 7 ?>" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="boxes" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Belum ada master bahan baku / kemasan</div>
                                <div style="font-size:12px;color:var(--color-ink-mute);margin-top:4px;">Klik tombol "+ Tambah Bahan / Kemasan" untuk mendaftarkan bahan balan, plastik, atau label.</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 3: MASTER RESEP / BOM (BILL OF MATERIALS)                             -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'recipes'" class="space-y-4">
        <!-- SELECTOR BARANG JADI & AKSI -->
        <div class="card p-3.5 sm:p-4">
            <label class="form-label" style="font-size:11.5px;font-weight:700;margin-bottom:8px;display:flex;align-items:center;gap:6px;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;">
                <i data-lucide="package-search" style="width:14px;height:14px;color:var(--color-primary);"></i>
                <span>Pilih Produk Barang Jadi untuk Atur Resep BOM:</span>
            </label>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <div class="flex-1 min-w-0">
                    <select x-model="selectedRecipeProductId" class="form-input searchable-select" style="height:40px;font-size:13.5px;font-weight:600;width:100%;margin:0;">
                        <option value="">-- Pilih Barang Jadi --</option>
                        <?php foreach (($allFinishedGoodsList ?? $finishedGoods) as $fg): ?>
                        <option value="<?= $fg['id'] ?>"><?= htmlspecialchars($fg['kode_sku']) ?> - <?= htmlspecialchars($fg['nama_item']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <template x-if="selectedRecipeProduct">
                    <?php if (\App\Core\Auth::can('master.products_manage')): ?>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" @click="openCopyRecipeModal()" class="btn btn-secondary" style="height:40px;padding:0 14px;white-space:nowrap;font-size:12.5px;margin:0;" title="Salin resep ke produk lain">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                            <span>Salin Resep</span>
                        </button>
                        <button type="button" @click="openAddRecipeItemModal()" class="btn btn-primary" style="height:40px;padding:0 16px;white-space:nowrap;font-size:12.5px;margin:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>
                            <span>Tambah Komponen Bahan</span>
                        </button>
                    </div>
                    <?php endif; ?>
                </template>
            </div>
        </div>

        <!-- SELECTED PRODUCT RECIPE DETAIL -->
        <template x-if="selectedRecipeProduct">
            <div class="space-y-4">
                <!-- RINGKASAN ESTIMASI HPP PRODUK (BALANCED 12-COL GRID) -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <!-- BLOK KIRI: 3 KOMPONEN BIAYA BAHAN & UPAH (7 KOLOM) -->
                    <div class="md:col-span-7 grid grid-cols-3 gap-2.5">
                        <!-- 1. Bahan Mentah Curah -->
                        <div class="card p-3 flex flex-col justify-between" style="background:var(--color-canvas-soft, #f8fafc);border:1px solid var(--color-hairline);">
                            <div class="flex items-center justify-between gap-1">
                                <span style="font-size:10px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;">1. Bahan Mentah</span>
                                <i data-lucide="package" style="width:13px;height:13px;color:#f59e0b;flex-shrink:0;"></i>
                            </div>
                            <div class="my-1">
                                <div style="font-size:15px;font-weight:800;color:#d97706;font-family:var(--font-mono);line-height:1.2;" x-text="formatRupiah(recipeSummary.rawMaterialCost)"></div>
                            </div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);" x-text="recipeSummary.rawMaterialCount + ' jenis curah'"></div>
                        </div>

                        <!-- 2. Bahan Kemasan & Label -->
                        <div class="card p-3 flex flex-col justify-between" style="background:var(--color-canvas-soft, #f8fafc);border:1px solid var(--color-hairline);">
                            <div class="flex items-center justify-between gap-1">
                                <span style="font-size:10px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;">2. Kemasan</span>
                                <i data-lucide="boxes" style="width:13px;height:13px;color:#3b82f6;flex-shrink:0;"></i>
                            </div>
                            <div class="my-1">
                                <div style="font-size:15px;font-weight:800;color:#2563eb;font-family:var(--font-mono);line-height:1.2;" x-text="formatRupiah(recipeSummary.packagingCost)"></div>
                            </div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);" x-text="recipeSummary.packagingCount + ' jenis kemas'"></div>
                        </div>

                        <!-- 3. Upah Borongan Packing -->
                        <div class="card p-3 flex flex-col justify-between" style="background:var(--color-canvas-soft, #f8fafc);border:1px solid var(--color-hairline);">
                            <div class="flex items-center justify-between gap-1">
                                <span style="font-size:10px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;">3. Upah Bungkus</span>
                                <i data-lucide="badge-percent" style="width:13px;height:13px;color:#10b981;flex-shrink:0;"></i>
                            </div>
                            <div class="my-1">
                                <div style="font-size:15px;font-weight:800;color:#059669;font-family:var(--font-mono);line-height:1.2;" x-text="formatRupiah(recipeSummary.wageCost)"></div>
                            </div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="selectedRecipeProduct?.nama_kelompok || 'Tanpa Borongan'"></div>
                        </div>
                    </div>

                    <!-- BLOK KANAN: ESTIMASI HPP & HARGA RITEL L1 (5 KOLOM) -->
                    <div class="md:col-span-5 grid grid-cols-2 gap-2.5">
                        <!-- Total Estimasi HPP -->
                        <div class="card p-3 flex flex-col justify-between" style="background:rgba(99,102,241,0.06);border:1px solid rgba(99,102,241,0.25);">
                            <div class="flex items-center justify-between gap-1">
                                <span style="font-size:10px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.04em;">Estimasi HPP</span>
                                <i data-lucide="calculator" style="width:13px;height:13px;color:#6366f1;flex-shrink:0;"></i>
                            </div>
                            <div class="my-1">
                                <div style="font-size:16px;font-weight:900;color:#4f46e5;font-family:var(--font-mono);line-height:1.2;" x-text="formatRupiah(recipeSummary.totalHpp)"></div>
                            </div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);">Biaya per bungkus</div>
                        </div>

                        <!-- Harga Ritel Level 1 -->
                        <div class="card p-3 flex flex-col justify-between" style="background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.25);">
                            <div class="flex items-center justify-between gap-1">
                                <span style="font-size:10px;font-weight:700;color:#059669;text-transform:uppercase;letter-spacing:0.04em;">Harga Ritel (L1)</span>
                                <i data-lucide="tag" style="width:13px;height:13px;color:#059669;flex-shrink:0;"></i>
                            </div>
                            <div class="my-1">
                                <div style="font-size:16px;font-weight:900;color:#059669;font-family:var(--font-mono);line-height:1.2;" x-text="selectedRecipeProduct?.harga_jual_ritel ? formatRupiah(selectedRecipeProduct.harga_jual_ritel) : '-'"></div>
                            </div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);">Katalog Jual POS</div>
                        </div>
                    </div>
                </div>

                <!-- TABLE KOMPONEN RESEP -->
                <div class="card p-0 overflow-hidden" style="border: 1px solid var(--color-hairline); box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.04));">
                    <!-- HEADER INFORMASI PRODUK (LEGA & TERATUR) -->
                    <div class="p-4 sm:px-5 sm:py-4 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
                        <div class="min-w-0 flex-1">
                            <!-- Baris 1: SKU & Nama Produk Sejajar Rapi -->
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <span class="badge badge-mono font-bold shrink-0" style="font-size:12px;padding:3px 8px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline-strong);" x-text="selectedRecipeProduct.kode_sku"></span>
                                <h3 style="font-size:15.5px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;" x-text="selectedRecipeProduct.nama_item"></h3>
                            </div>
                            <!-- Baris 2: Grup Kemasan & Barcode dengan Desain Pill Capsule Modern & Elegan -->
                            <div class="flex items-center gap-2 mt-2.5 flex-wrap" style="margin-top: 10px;">
                                <!-- Pill Badge Grup Kemasan -->
                                <span class="inline-flex items-center gap-1.5" style="padding: 3px 11px; border-radius: 9999px; background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.22); color: #4338ca; font-size: 11.5px; font-weight: 600; line-height: 1.4;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#6366f1;flex-shrink:0;"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>
                                    <span x-text="selectedRecipeProduct.nama_grup || 'Grup Kemasan Universal'"></span>
                                </span>

                                <!-- Pill Badge Barcode -->
                                <template x-if="selectedRecipeProduct.barcode_universal">
                                    <span class="inline-flex items-center gap-1.5 font-mono" style="padding: 3px 11px; border-radius: 9999px; background: rgba(100, 116, 139, 0.08); border: 1px solid rgba(100, 116, 139, 0.22); color: #334155; font-size: 11.5px; font-weight: 600; line-height: 1.4; letter-spacing: 0.02em;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#64748b;flex-shrink:0;"><path d="M3 5v14"/><path d="M8 5v14"/><path d="M12 5v14"/><path d="M17 5v14"/><path d="M21 5v14"/></svg>
                                        <span x-text="selectedRecipeProduct.barcode_universal"></span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Badge Jumlah Komponen di Kanan -->
                        <div class="shrink-0 flex items-center self-start sm:self-center">
                            <span class="badge" style="font-size:12px;font-weight:700;padding:5px 12px;background:rgba(37,99,235,0.08);color:#2563eb;border:1px solid rgba(37,99,235,0.22);display:inline-flex;align-items:center;gap:6px;border-radius:9999px;">
                                <i data-lucide="boxes" style="width:14px;height:14px;"></i>
                                <span x-text="currentProductRecipeList.length + ' Komponen Terdaftar'"></span>
                            </span>
                        </div>
                    </div>

                    <!-- TABEL DAFTAR KOMPONEN (RESPONSIVE & PROPORSIONAL) -->
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="data-table" style="width: 100%; min-width: 720px;">
                            <thead>
                                <tr>
                                    <th style="width:95px; padding:11px 14px;" class="cell-nowrap">Kode</th>
                                    <th style="padding:11px 14px;">Bahan Baku &amp; Kemasan</th>
                                    <th style="width:115px; padding:11px 14px;" class="cell-nowrap">Kategori</th>
                                    <th class="cell-center cell-nowrap" style="width:140px; padding:11px 14px;">Kebutuhan / Pcs</th>
                                    <th class="cell-right cell-nowrap" style="width:130px; padding:11px 14px;">Biaya / Pcs</th>
                                    <?php if (\App\Core\Auth::can('master.products_manage')): ?>
                                    <th class="cell-center cell-nowrap" style="width:65px; padding:11px 10px;">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="r in currentProductRecipeList" :key="r.id">
                                    <tr style="transition:background-color 0.15s ease;">
                                        <td class="cell-nowrap" style="padding:12px 14px;">
                                            <span class="badge badge-mono font-bold" style="font-size:11.5px;padding:2px 7px;" x-text="r.item_bahan_sku"></span>
                                        </td>
                                        <td style="padding:12px 14px;">
                                            <div style="font-weight:700;font-size:13.5px;color:var(--color-ink);line-height:1.35;" x-text="r.item_bahan_nama"></div>
                                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;display:flex;align-items:center;gap:4px;">
                                                <span>HPP Vendor:</span>
                                                <span style="font-weight:600;font-family:var(--font-mono);color:var(--color-ink-secondary);" x-text="formatRupiah(r.item_bahan_hpp) + ' / ' + r.item_bahan_satuan"></span>
                                            </div>
                                        </td>
                                        <td class="cell-nowrap" style="padding:12px 14px;">
                                            <span class="badge"
                                                  :class="r.item_bahan_tipe === 'bahan_mentah' ? 'badge-warning' : 'badge-secondary'"
                                                  style="font-size:10.5px;padding:2px 7px;font-weight:600;"
                                                  x-text="r.item_bahan_tipe === 'bahan_mentah' ? 'Curah Mentah' : 'Bahan Kemas'"></span>
                                        </td>
                                        <td class="cell-center cell-nowrap" style="padding:12px 14px;">
                                            <span class="badge" style="font-family:var(--font-mono);font-size:12.5px;font-weight:700;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);color:var(--color-primary-deep);padding:3px 8px;">
                                                <span x-text="Number(r.jumlah_kebutuhan).toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 4})"></span>
                                                <span style="font-size:10.5px;font-weight:600;color:var(--color-ink-mute);margin-left:3px;" x-text="r.item_bahan_satuan"></span>
                                            </span>
                                        </td>
                                        <td class="cell-right cell-nowrap" style="padding:12px 14px;">
                                            <div style="font-family:var(--font-mono);font-size:13.5px;font-weight:800;color:var(--color-ink);" x-text="formatRupiah(r.subtotal_biaya_bahan)"></div>
                                        </td>
                                        <?php if (\App\Core\Auth::can('master.products_manage')): ?>
                                        <td class="cell-center cell-nowrap" style="padding:12px 10px;">
                                            <button @click="deleteRecipeItem(r.id)" class="btn btn-ghost btn-sm" style="padding:4px 8px;color:#ef4444;border-radius:6px;" title="Hapus Komponen Resep">
                                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                            </button>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                </template>

                                <template x-if="currentProductRecipeList.length === 0">
                                    <tr>
                                        <td colspan="<?= \App\Core\Auth::can('master.products_manage') ? 6 : 5 ?>" style="text-align:center;padding:48px 24px;color:var(--color-ink-mute);">
                                            <i data-lucide="flask-conical" style="width:40px;height:40px;margin:0 auto 10px auto;opacity:0.4;"></i>
                                            <div style="font-weight:700;font-size:13.5px;color:var(--color-ink);">Belum ada komposisi bahan untuk produk ini</div>
                                            <div style="font-size:12px;color:var(--color-ink-mute);margin-top:4px;">Klik "+ Tambah Komponen Bahan" atau "Salin Resep" untuk menetapkan bahan balan curah, plastik kemasan, atau label.</div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot x-show="currentProductRecipeList.length > 0" style="background:var(--color-canvas-soft);border-top:2px solid var(--color-hairline);">
                                <tr>
                                    <td colspan="3" style="padding:12px 14px;font-weight:700;font-size:11.5px;color:var(--color-ink-secondary);text-transform:uppercase;letter-spacing:0.04em;">
                                        Total Estimasi Biaya Bahan (Curah + Kemasan)
                                    </td>
                                    <td class="cell-center cell-nowrap" style="padding:12px 14px;">
                                        <span class="badge badge-secondary" style="font-size:11px;font-weight:600;padding:2px 7px;" x-text="currentProductRecipeList.length + ' Komponen'"></span>
                                    </td>
                                    <td class="cell-right cell-nowrap" style="padding:12px 14px;">
                                        <div style="font-family:var(--font-mono);font-size:14px;font-weight:900;color:#2563eb;" x-text="formatRupiah(Number(recipeSummary.rawMaterialCost || 0) + Number(recipeSummary.packagingCost || 0))"></div>
                                    </td>
                                    <?php if (\App\Core\Auth::can('master.products_manage')): ?>
                                    <td style="padding:12px 10px;"></td>
                                    <?php endif; ?>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="!selectedRecipeProduct">
            <div class="card" style="text-align:center;padding:48px;color:var(--color-ink-mute);">
                <i data-lucide="package-search" style="width:44px;height:44px;margin:0 auto 12px auto;opacity:0.4;"></i>
                <div style="font-size:14px;font-weight:700;color:var(--color-ink);">Pilih Barang Jadi Terlebih Dahulu</div>
                <div style="font-size:12.5px;color:var(--color-ink-mute);margin-top:4px;">Pilih salah satu produk pada dropdown di atas untuk mengelola daftar resep bahan baku dan kemasannya.</div>
            </div>
        </template>
    </div>
    <!-- ========================================================================= -->
    <!-- TAB 4: KELOMPOK UPAH BORONGAN PACKING                                     -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'borongan'" class="card" style="padding:0;overflow:hidden;">
        <!-- ACTION & HEADER BAR -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="flex items-center gap-3 w-full sm:w-auto flex-1">
                <div class="form-input-icon flex-1 sm:max-w-xs">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="text" x-model="searchBorongan" placeholder="Cari nama kelompok / tarif..." class="form-input" style="height:38px;font-size:13px;">
                </div>
                <div class="hidden sm:block" style="font-size:11.5px;color:var(--color-ink-mute);">Tarif upah borongan buruh packing per bungkus kemasan</div>
            </div>

            <?php if (\App\Core\Auth::can('master.products_manage')): ?>
            <button @click="openAddBoronganModal()" class="btn btn-primary" style="height:38px;white-space:nowrap;">
                <i data-lucide="plus"></i>
                <span>Tambah Kelompok Borongan</span>
            </button>
            <?php endif; ?>
        </div>

        <!-- TABLE LIST KELOMPOK BORONGAN -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 860px;">
                <thead>
                    <tr>
                        <th style="min-width:180px;">Nama Kelompok</th>
                        <th class="cell-right cell-nowrap" style="width:180px; min-width:140px;">Tarif Upah / Bungkus</th>
                        <th style="min-width:180px;">Deskripsi &amp; Keterangan</th>
                        <th class="cell-center cell-nowrap" style="width:150px; min-width:120px;">SKU Terhubung</th>
                        <th class="cell-center cell-nowrap" style="width:100px; min-width:85px;">Status</th>
                        <?php if (\App\Core\Auth::can('master.products_manage')): ?>
                        <th class="cell-center cell-nowrap" style="width:100px; min-width:85px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="w in filteredWageGroups" :key="w.id">
                        <tr :style="!w.status_aktif ? 'opacity:0.5;' : ''">
                            <td>
                                <div style="font-weight:800;font-size:13.5px;color:var(--color-ink);" x-text="w.nama_kelompok"></div>
                            </td>
                            <td class="cell-right cell-currency" style="font-weight:900;font-size:14px;color:#10b981;">
                                <span x-text="formatRupiah(w.upah_per_bungkus)"></span>
                                <span style="font-size:11px;font-weight:600;color:var(--color-ink-mute);">/pack</span>
                            </td>
                            <td style="font-size:12.5px;color:var(--color-ink-secondary);" x-text="w.keterangan || '-'"></td>
                            <td class="cell-center cell-nowrap">
                                <span class="badge badge-secondary" style="font-weight:700;" x-text="(w.total_sku_terhubung || 0) + ' SKU Produk'"></span>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <template x-if="w.status_aktif">
                                    <span class="badge badge-success">Aktif</span>
                                </template>
                                <template x-if="!w.status_aktif">
                                    <span class="badge badge-danger">Nonaktif</span>
                                </template>
                            </td>
                            <?php if (\App\Core\Auth::can('master.products_manage')): ?>
                            <td class="cell-center cell-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="openEditBoronganModal(w)" class="btn btn-ghost btn-sm" style="padding:6px;" title="Edit Kelompok">
                                        <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                    </button>
                                    <template x-if="!w.total_sku_terhubung || w.total_sku_terhubung == 0">
                                        <button @click="deleteBoronganGroup(w.id, w.nama_kelompok)" class="btn btn-ghost btn-sm" style="padding:6px;color:#ef4444;" title="Hapus Kelompok">
                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                        </button>
                                    </template>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    </template>

                    <template x-if="filteredWageGroups.length === 0">
                        <tr>
                            <td colspan="<?= \App\Core\Auth::can('master.products_manage') ? 6 : 5 ?>" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="badge-percent" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Belum ada kelompok upah borongan yang cocok</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 5: MASTER MEREK PRODUK                                                -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'brands'" class="card" style="padding:0;overflow:hidden;">
        <!-- ACTION & HEADER BAR -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="flex items-center gap-3 w-full sm:w-auto flex-1">
                <div class="form-input-icon flex-1 sm:max-w-xs">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="text" x-model="searchBrand" placeholder="Cari kode / nama merek..." class="form-input" style="height:38px;font-size:13px;">
                </div>
                <div class="hidden sm:block" style="font-size:11.5px;color:var(--color-ink-mute);">Master Merek dagang yang menaungi grup produk dan varian kemasan</div>
            </div>

            <?php if (\App\Core\Auth::can('master.products_manage')): ?>
            <div class="flex items-center gap-2">
                <button @click="openAddBrandModal()" class="btn btn-primary" style="height:38px;white-space:nowrap;">
                    <i data-lucide="plus"></i>
                    <span>Tambah Merek Baru</span>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- TABLE LIST MASTER MEREK -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 860px;">
                <thead>
                    <tr>
                        <th style="width:140px; min-width:120px;">Kode Merek</th>
                        <th style="min-width:220px;">Nama Merek</th>
                        <th class="cell-center cell-nowrap" style="width:180px; min-width:140px;">Grup Produk Menaungi</th>
                        <th class="cell-center cell-nowrap" style="width:120px; min-width:90px;">Status</th>
                        <?php if (\App\Core\Auth::can('master.products_manage')): ?>
                        <th class="cell-center cell-nowrap" style="width:110px; min-width:90px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="b in filteredBrands" :key="b.id">
                        <tr :style="!b.status_aktif ? 'opacity:0.5;' : ''">
                            <td class="cell-nowrap">
                                <span class="badge badge-mono font-bold" style="font-size:12px;" x-text="b.kode_merek"></span>
                            </td>
                            <td>
                                <div style="font-weight:800;font-size:13.5px;color:var(--color-ink);" x-text="b.nama_merek"></div>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <span class="badge badge-secondary" style="font-weight:700;" x-text="(b.total_grup || 0) + ' Grup Produk'"></span>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <template x-if="b.status_aktif">
                                    <span class="badge badge-success">Aktif</span>
                                </template>
                                <template x-if="!b.status_aktif">
                                    <span class="badge badge-danger">Nonaktif</span>
                                </template>
                            </td>
                            <?php if (\App\Core\Auth::can('master.products_manage')): ?>
                            <td class="cell-center cell-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="openEditBrandModal(b)" class="btn btn-ghost btn-sm" style="padding:6px;" title="Edit Merek">
                                        <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                    </button>
                                    <template x-if="!b.total_grup || b.total_grup == 0">
                                        <button @click="deleteBrand(b.id, b.nama_merek)" class="btn btn-ghost btn-sm" style="padding:6px;color:#ef4444;" title="Hapus Merek">
                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                        </button>
                                    </template>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    </template>

                    <template x-if="filteredBrands.length === 0">
                        <tr>
                            <td colspan="<?= \App\Core\Auth::can('master.products_manage') ? 5 : 4 ?>" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="tag" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Belum ada merek produk yang cocok</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODALS SECTION                                                            -->
    <!-- ========================================================================= -->

    <?php if (\App\Core\Auth::can('master.products_manage')): ?>
    <!-- MODAL 1: TAMBAH / EDIT BARANG JADI -->
    <template x-teleport="body">
    <div x-show="showItemModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:540px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title" x-text="isEditItem ? 'Edit Barang Jadi' : 'Tambah Barang Jadi Baru'"></div>
            </div>

            <form :action="isEditItem ? '<?= Router::url('/products/update-item') ?>' : '<?= Router::url('/products/store-item') ?>'" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="id" :value="itemForm.id">

                <div>
                    <label class="form-label">Grup Kemasan Luar (Barcode Universal) *</label>
                    <select name="grup_id" x-model="itemForm.grup_id" required class="form-input">
                        <option value="">-- Pilih Grup Kemasan --</option>
                        <?php foreach ($groups as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama_grup']) ?> (<?= $g['kode_grup'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Nama Barang Jadi Lengkap *</label>
                    <input type="text" name="nama_item" x-model="itemForm.nama_item" required class="form-input" placeholder="Contoh: Berondong Beras Manis Gurih 135gr">
                </div>

                <div>
                    <label class="form-label">Kelompok Upah Borongan</label>
                    <select name="kelompok_borongan_id" x-model="itemForm.kelompok_borongan_id" class="form-input">
                        <option value="">-- Tanpa Kelompok Upah (Rp 0) --</option>
                        <?php foreach ($wageGroups as $w): ?>
                        <option value="<?= $w['id'] ?>" data-wage="<?= (float)$w['upah_per_bungkus'] ?>"><?= htmlspecialchars($w['nama_kelompok']) ?> (Rp <?= number_format((float)$w['upah_per_bungkus'], 0, ',', '.') ?>/pack)</option>
                        <?php endforeach; ?>
                    </select>
                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;">
                        Tarif upah borongan kemas otomatis mengikuti master kelompok upah borongan yang dipilih.
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">HPP Acuan (Rp)</label>
                        <input type="text" name="harga_pokok_pembelian" x-model="itemForm.harga_pokok_pembelian" class="form-input font-mono input-rupiah" placeholder="10.000">
                    </div>
                    <div>
                        <label class="form-label">Stok Minimum Alert (Pcs)</label>
                        <input type="number" name="stok_minimum_peringatan" x-model.number="itemForm.stok_minimum_peringatan" class="form-input font-mono" placeholder="10">
                    </div>
                </div>

                <template x-if="!isEditItem">
                    <div>
                        <label class="form-label">Stok Awal Fisik (Pcs)</label>
                        <input type="number" name="stok_awal" x-model.number="itemForm.stok_awal" class="form-input font-mono" placeholder="0">
                    </div>
                </template>

                <template x-if="isEditItem">
                    <div style="display:flex;gap:16px;margin-top:6px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;font-weight:600;">
                            <input type="checkbox" name="status_jual" x-model="itemForm.status_jual" style="width:15px;height:15px;accent-color:var(--color-primary);">
                            <span>Dapat Dijual di POS</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;font-weight:600;">
                            <input type="checkbox" name="status_aktif" x-model="itemForm.status_aktif" style="width:15px;height:15px;accent-color:var(--color-primary);">
                            <span>Status Aktif</span>
                        </label>
                    </div>
                </template>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showItemModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span x-text="isEditItem ? 'Simpan Perubahan' : 'Tambah Barang Jadi'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- MODAL 2: TAMBAH GRUP KEMASAN -->
    <template x-teleport="body">
    <div x-show="showGroupModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:460px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title">Tambah Grup Kemasan Baru</div>
            </div>

            <form action="<?= Router::url('/products/store-group') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <?= \App\Helpers\CSRF::field() ?>
                <div>
                    <label class="form-label">Merek Dagang *</label>
                    <select name="merek_id" required class="form-input">
                        <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama_merek']) ?> (<?= $b['kode_merek'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Nama Grup Kemasan *</label>
                    <input type="text" name="nama_grup" required class="form-input" placeholder="Contoh: KEREN SNACK SINGKONG 250GR">
                </div>

                <div>
                    <label class="form-label">Barcode Universal Kemasan (Pabrik)</label>
                    <input type="text" name="barcode_universal" 
                           oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 13)"
                           maxlength="13" class="form-input font-mono" placeholder="88030173 (8–13 Digit Angka)">
                </div>

                <div>
                    <label class="form-label">Harga Ritel Standar (L1) (Rp/pcs) *</label>
                    <input type="text" name="harga_ritel_l1" required class="form-input font-mono input-rupiah" placeholder="15.000" value="15.000">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showGroupModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span>Tambah Grup</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- MODAL 2B: KELOLA DAFTAR GRUP KEMASAN -->
    <template x-teleport="body">
    <div x-show="showManageGroupsModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:780px;padding:24px;">
            <div class="modal-header">
                <div>
                    <div class="modal-title">Kelola Grup Kemasan Luar</div>
                    <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">Daftar seluruh grup kemasan dan barcode universal kemasan pabrik</div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-bottom:12px;">
                <button type="button" @click="openAddGroupModal()" class="btn btn-primary btn-sm" style="height:34px;">
                    <i data-lucide="plus" style="width:14px;height:14px;"></i>
                    <span>Tambah Grup Baru</span>
                </button>
            </div>

            <div class="overflow-x-auto custom-scrollbar" style="max-height:380px;border:1px solid var(--color-hairline);border-radius:6px;">
                <table class="data-table" style="width:100%;font-size:12.5px;">
                    <thead>
                        <tr>
                            <th style="width:85px;">Kode</th>
                            <th style="width:120px;">Merek</th>
                            <th>Nama Grup Kemasan</th>
                            <th style="width:120px;">Barcode Pabrik</th>
                            <th class="cell-center" style="width:85px;">Total SKU</th>
                            <th class="cell-center" style="width:75px;">Status</th>
                            <th class="cell-center" style="width:85px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="g in groups" :key="g.id">
                            <tr :style="!g.status_aktif ? 'opacity:0.5;' : ''">
                                <td class="cell-nowrap">
                                    <span class="badge badge-mono" x-text="g.kode_grup"></span>
                                </td>
                                <td class="cell-nowrap">
                                    <span class="badge badge-mono" style="font-weight:700;" x-text="g.nama_merek || 'KEREN SNACK'"></span>
                                </td>
                                <td>
                                    <div style="font-weight:700;" x-text="g.nama_grup"></div>
                                </td>
                                <td class="cell-nowrap">
                                    <span class="badge badge-mono" x-text="g.barcode_universal || '-'"></span>
                                </td>
                                <td class="cell-center cell-nowrap">
                                    <span class="badge badge-secondary" x-text="(g.total_sku || 0) + ' SKU'"></span>
                                </td>
                                <td class="cell-center cell-nowrap">
                                    <template x-if="g.status_aktif">
                                        <span class="badge badge-success">Aktif</span>
                                    </template>
                                    <template x-if="!g.status_aktif">
                                        <span class="badge badge-danger">Nonaktif</span>
                                    </template>
                                </td>
                                <td class="cell-center cell-nowrap">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="openEditGroupModal(g)" class="btn btn-ghost btn-sm" style="padding:4px 6px;" title="Edit Grup">
                                            <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                        </button>
                                        <template x-if="!g.total_sku || g.total_sku == 0">
                                            <button @click="deleteGroup(g.id, g.nama_grup)" class="btn btn-ghost btn-sm" style="padding:4px 6px;color:#ef4444;" title="Hapus Grup">
                                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                <button type="button" @click="showManageGroupsModal = false" class="btn btn-secondary">Tutup</button>
            </div>
        </div>
    </div>
    </template>

    <!-- MODAL 2C: EDIT GRUP KEMASAN -->
    <template x-teleport="body">
    <div x-show="showEditGroupModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:460px;padding:24px;">
            <div class="modal-header">
                <div>
                    <div class="modal-title">Edit Grup Kemasan</div>
                    <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;" x-text="editGroupForm.kode_grup"></div>
                </div>
            </div>

            <form action="<?= Router::url('/products/update-group') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="id" :value="editGroupForm.id">

                <div>
                    <label class="form-label">Merek Dagang *</label>
                    <select name="merek_id" x-model="editGroupForm.merek_id" required class="form-input">
                        <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama_merek']) ?> (<?= $b['kode_merek'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Nama Grup Kemasan *</label>
                    <input type="text" name="nama_grup" x-model="editGroupForm.nama_grup" required class="form-input" placeholder="Contoh: KEREN SNACK SINGKONG 250GR">
                </div>

                <div>
                    <label class="form-label">Barcode Universal Kemasan (Pabrik)</label>
                    <input type="text" name="barcode_universal" x-model="editGroupForm.barcode_universal"
                           @input="editGroupForm.barcode_universal = $event.target.value.replace(/[^0-9]/g, '').slice(0, 13)"
                           maxlength="13" class="form-input font-mono" placeholder="88030173 (8–13 Digit Angka)">
                </div>

                <div>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;font-weight:600;">
                        <input type="checkbox" name="status_aktif" x-model="editGroupForm.status_aktif" style="width:15px;height:15px;accent-color:var(--color-primary);">
                        <span>Status Grup Aktif</span>
                    </label>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showEditGroupModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>
    <?php endif; ?>

    <!-- MODAL 3: TAMBAH / EDIT BAHAN BAKU & KEMASAN -->
    <?php if (\App\Core\Auth::can(['master.materials_manage', 'master.products_manage'])): ?>
    <template x-teleport="body">
    <div x-show="showMaterialModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:500px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title" x-text="isEditMaterial ? 'Edit Bahan / Kemasan' : 'Tambah Bahan / Kemasan Baru'"></div>
            </div>

            <form :action="isEditMaterial ? '<?= Router::url('/products/update-material') ?>' : '<?= Router::url('/products/store-material') ?>'" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="id" :value="materialForm.id">

                <div>
                    <label class="form-label">Nama Bahan Mentah / Kemasan *</label>
                    <input type="text" name="nama_item" x-model="materialForm.nama_item" required class="form-input" placeholder="Contoh: Makaroni Curah Balan 10Kg, Plastik 150gr, Label Berondong">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Kategori Bahan *</label>
                        <select name="tipe_item" x-model="materialForm.tipe_item" required class="form-input">
                            <option value="bahan_mentah">Bahan Mentah Curah (Bal/Kg)</option>
                            <option value="bahan_kemas">Bahan Kemasan (Plastik/Label/Cup)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Satuan Beli / Dasar *</label>
                        <select name="satuan_dasar" x-model="materialForm.satuan_dasar" required class="form-input font-mono">
                            <option value="bal">bal</option>
                            <option value="kg">kg</option>
                            <option value="lembar">lembar</option>
                            <option value="pack">pack</option>
                            <option value="roll">roll</option>
                            <option value="pcs">pcs</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label">Vendor Pemasok Utama</label>
                    <select name="pemasok_utama_id" x-model="materialForm.pemasok_utama_id" class="form-input">
                        <option value="">-- Pilih Supplier Vendor --</option>
                        <?php foreach ($suppliers as $sup): ?>
                        <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['nama_pemasok']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">HPP Beli Vendor (Rp)</label>
                        <input type="text" name="harga_pokok_pembelian" x-model="materialForm.harga_pokok_pembelian" class="form-input font-mono input-rupiah" placeholder="250.000">
                    </div>
                    <div>
                        <label class="form-label">Stok Minimum Peringatan</label>
                        <input type="number" step="any" name="stok_minimum_peringatan" x-model.number="materialForm.stok_minimum_peringatan" class="form-input font-mono" placeholder="5">
                    </div>
                </div>

                <template x-if="!isEditMaterial">
                    <div>
                        <label class="form-label">Stok Awal Fisik</label>
                        <input type="number" step="any" name="stok_awal" x-model.number="materialForm.stok_awal" class="form-input font-mono" placeholder="0">
                    </div>
                </template>

                <template x-if="isEditMaterial">
                    <div>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;font-weight:600;">
                            <input type="checkbox" name="status_aktif" x-model="materialForm.status_aktif" style="width:15px;height:15px;accent-color:var(--color-primary);">
                            <span>Status Bahan Aktif</span>
                        </label>
                    </div>
                </template>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showMaterialModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span x-text="isEditMaterial ? 'Simpan Perubahan' : 'Tambah Bahan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>
    <?php endif; ?>

    <!-- MODAL 4: TAMBAH KOMPONEN RESEP BOM (DENGAN KALKULATOR HASIL BUNGKUS / YIELD) -->
    <?php if (\App\Core\Auth::can('master.products_manage')): ?>
    <template x-teleport="body">
    <div x-show="showRecipeModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:520px;padding:24px;">
            <div class="modal-header">
                <div>
                    <div class="modal-title">Tambah Komponen Resep BOM</div>
                    <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;" x-text="'Produk: ' + (selectedRecipeProduct?.nama_item || '')"></div>
                </div>
            </div>

            <form action="<?= Router::url('/products/store-recipe-item') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="item_jadi_id" :value="selectedRecipeProduct?.id">

                <div>
                    <label class="form-label">Pilih Bahan Baku / Kemasan *</label>
                    <select name="item_bahan_id" x-model="recipeForm.item_bahan_id" @change="onRecipeMaterialChange()" required class="form-input">
                        <option value="">-- Pilih Bahan Baku Curah / Kemasan --</option>
                        <?php
                        $bahanKemas = [];
                        $bahanMentah = [];
                        foreach ($materials as $m) {
                            if (($m['tipe_item'] ?? '') === 'bahan_kemas') {
                                $bahanKemas[] = $m;
                            } else {
                                $bahanMentah[] = $m;
                            }
                        }
                        ?>
                        <?php if (!empty($bahanKemas)): ?>
                        <optgroup label="Bahan Kemasan">
                            <?php foreach ($bahanKemas as $m): ?>
                            <option value="<?= $m['id'] ?>">
                                <?= htmlspecialchars($m['nama_item']) ?> (Kemas - <?= $m['satuan_dasar'] ?> | HPP Rp <?= number_format((float)$m['harga_pokok_pembelian'], 0, ',', '.') ?>)
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($bahanMentah)): ?>
                        <optgroup label="Bahan Mentah / Curah">
                            <?php foreach ($bahanMentah as $m): ?>
                            <option value="<?= $m['id'] ?>">
                                <?= htmlspecialchars($m['nama_item']) ?> (Mentah Curah - <?= $m['satuan_dasar'] ?> | HPP Rp <?= number_format((float)$m['harga_pokok_pembelian'], 0, ',', '.') ?>)
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- MODE INPUT SELECTION -->
                <div>
                    <label class="form-label">Metode Input Kebutuhan Bahan</label>
                    <div class="flex gap-2">
                        <button type="button"
                                @click="recipeInputMode = 'yield'"
                                :class="recipeInputMode === 'yield' ? 'btn btn-primary btn-sm flex-1' : 'btn btn-secondary btn-sm flex-1'"
                                style="font-size:12px;">
                            🎯 Hitung Hasil Bungkus (Yield)
                        </button>
                        <button type="button"
                                @click="recipeInputMode = 'direct'"
                                :class="recipeInputMode === 'direct' ? 'btn btn-primary btn-sm flex-1' : 'btn btn-secondary btn-sm flex-1'"
                                style="font-size:12px;">
                            🔢 Input Desimal Langsung
                        </button>
                    </div>
                </div>

                <!-- MODE A: YIELD CALCULATOR (OPSI B) -->
                <template x-if="recipeInputMode === 'yield'">
                    <div class="p-3 rounded-lg border border-hairline space-y-3" style="background:var(--color-canvas-soft, #f8fafc);">
                        <div>
                            <label class="form-label" style="font-size:12.5px;">
                                <span>1 </span>
                                <strong x-text="selectedMaterialInModal?.satuan_dasar || 'satuan'"></strong>
                                <span> bahan ini menghasilkan berapa bungkus barang jadi? *</span>
                            </label>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input type="number" step="any" min="0.0001" x-model="recipeYieldPcs" @input="calculateFromYield()" class="form-input font-mono flex-1" placeholder="Contoh: 80">
                                <span class="badge badge-secondary" style="font-family:var(--font-mono);font-size:12px;height:38px;display:flex;align-items:center;padding:0 10px;font-weight:700;">bungkus / pack</span>
                            </div>
                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;">
                                💡 <em>Misal: 1 bal (10 kg) makaroni menghasilkan 80 bungkus snack, isi <strong>80</strong>.</em>
                            </div>
                        </div>

                        <!-- LIVE PREVIEW HASIL KALKULASI -->
                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-hairline">
                            <div style="font-size:12px;">
                                <div style="color:var(--color-ink-mute);font-size:10.5px;">Kebutuhan per bungkus:</div>
                                <div style="font-weight:700;font-family:var(--font-mono);color:var(--color-ink);" x-text="(recipeForm.jumlah_kebutuhan || '0') + ' ' + (selectedMaterialInModal?.satuan_dasar || '')"></div>
                            </div>
                            <div style="font-size:12px;text-align:right;">
                                <div style="color:var(--color-ink-mute);font-size:10.5px;">Estimasi biaya bahan:</div>
                                <div style="font-weight:800;font-family:var(--font-mono);color:var(--color-primary);" x-text="formatRupiah(estimatedCostPerPiece) + '/pack'"></div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- MODE B: DIRECT DECIMAL INPUT -->
                <template x-if="recipeInputMode === 'direct'">
                    <div class="p-3 rounded-lg border border-hairline space-y-3" style="background:var(--color-canvas-soft, #f8fafc);">
                        <div>
                            <label class="form-label" style="font-size:12.5px;">Jumlah Kebutuhan per 1 Bungkus Barang Jadi *</label>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input type="number" step="0.000001" min="0.000001" name="jumlah_kebutuhan" x-model="recipeForm.jumlah_kebutuhan" @input="calculateFromDirect()" required class="form-input font-mono flex-1" placeholder="Contoh: 0.15 atau 1">
                                <span class="badge badge-secondary" style="font-family:var(--font-mono);font-size:12px;height:38px;display:flex;align-items:center;padding:0 10px;font-weight:700;" x-text="selectedMaterialInModal?.satuan_dasar || 'satuan'"></span>
                            </div>
                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;">
                                💡 <em>Misal: 1 lembar plastik = <strong>1</strong>. Atau 150 gram dari satuan kg = <strong>0.15</strong>.</em>
                            </div>
                        </div>

                        <!-- LIVE PREVIEW HASIL BIAYA -->
                        <div class="flex justify-between items-center pt-2 border-t border-hairline" style="font-size:12px;">
                            <span style="color:var(--color-ink-mute);">Estimasi biaya bahan per bungkus:</span>
                            <span style="font-weight:800;font-family:var(--font-mono);color:var(--color-primary);" x-text="formatRupiah(estimatedCostPerPiece) + '/pack'"></span>
                        </div>
                    </div>
                </template>

                <!-- HIDDEN INPUT IF YIELD MODE -->
                <template x-if="recipeInputMode === 'yield'">
                    <input type="hidden" name="jumlah_kebutuhan" :value="recipeForm.jumlah_kebutuhan">
                </template>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
                    <button type="button" @click="showRecipeModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary" :disabled="!recipeForm.item_bahan_id || Number(recipeForm.jumlah_kebutuhan) <= 0">
                        <i data-lucide="save"></i>
                        <span>Simpan Komponen Resep</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- MODAL 4B: SALIN RESEP KE PRODUK LAIN (MASSAL / CHECKBOX) -->
    <template x-teleport="body">
    <div x-show="showCopyRecipeModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:580px;padding:24px;">
            <div class="modal-header" style="align-items:flex-start;padding-bottom:12px;border-bottom:1px solid var(--color-hairline);">
                <div>
                    <div class="modal-title" style="font-size:16px;font-weight:800;color:var(--color-ink);">Salin &amp; Terapkan Resep ke Produk Lain</div>
                    <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">Duplikasi komposisi bahan baku &amp; kemasan ke beberapa produk jadi sekaligus.</div>
                </div>
            </div>

            <form action="<?= Router::url('/products/copy-recipe') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;margin-top:14px;">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="source_item_id" :value="selectedRecipeProduct?.id">

                <!-- CARD SUMBER RESEP & KOMPOSISI BAHAN -->
                <div class="rounded-xl border border-hairline overflow-hidden" style="background:var(--color-canvas-soft, #f8fafc);">
                    <!-- Header Produk Sumber -->
                    <div class="p-3 border-b border-hairline flex items-center justify-between gap-2.5 flex-wrap" style="background:var(--color-canvas);">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <span style="font-size:10.5px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;" class="shrink-0">Sumber:</span>
                            <span class="badge badge-mono font-bold shrink-0" style="font-size:11px;padding:2px 7px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline-strong);" x-text="selectedRecipeProduct?.kode_sku"></span>
                            <span class="font-bold text-xs sm:text-sm truncate" style="color:var(--color-ink);" x-text="selectedRecipeProduct?.nama_item"></span>
                        </div>
                        <span class="badge badge-primary shrink-0" style="font-size:11px;font-weight:600;padding:2px 8px;display:inline-flex;align-items:center;gap:4px;">
                            <i data-lucide="layers" style="width:12px;height:12px;"></i>
                            <span x-text="currentProductRecipeList.length + ' Bahan'"></span>
                        </span>
                    </div>

                    <!-- Komposisi yang Akan Disalin -->
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span style="font-size:10.5px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;">Komposisi yang akan disalin:</span>
                            <span style="font-size:11px;color:var(--color-primary);font-weight:600;" x-text="currentProductRecipeList.length + ' komponen resep'"></span>
                        </div>
                        <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto custom-scrollbar">
                            <template x-for="b in currentProductRecipeList" :key="b.id">
                                <span class="badge" style="font-size:11px;padding:4px 9px;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:6px;box-shadow:0 1px 2px rgba(0,0,0,0.02);">
                                    <span style="font-weight:600;color:var(--color-ink);" x-text="b.item_bahan_nama"></span>
                                    <span class="font-mono ml-1 font-bold" style="color:var(--color-primary);" x-text="'(' + Number(b.jumlah_kebutuhan).toLocaleString('id-ID', {maximumFractionDigits:4}) + ' ' + b.item_bahan_satuan + ')'"></span>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- TOOLBAR PENCARIAN & FILTER -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <label class="form-label" style="font-size:12px;font-weight:700;margin:0;color:var(--color-ink);">
                            Pilih Produk Tujuan yang Akan Mengikuti Resep Ini:
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs" style="color:var(--color-ink-secondary);">
                            <input type="checkbox" x-model="copyOnlyWithoutRecipe" class="form-checkbox" style="width:14px;height:14px;border-radius:3px;">
                            <span style="font-size:11px;font-weight:600;">Hanya yang belum ada resep</span>
                        </label>
                    </div>

                    <div class="form-input-icon">
                        <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);width:14px;height:14px;"></i>
                        <input type="text" x-model="copySearchQuery" placeholder="Cari nama produk / varian / SKU tujuan..." class="form-input" style="height:36px;font-size:12.5px;padding-left:32px;">
                    </div>
                </div>

                <!-- SELECT ALL & COUNTER -->
                <div class="flex items-center justify-between px-1 py-1.5 text-xs border-b border-hairline" style="color:var(--color-ink-mute);">
                    <label class="flex items-center gap-2 cursor-pointer font-medium hover:text-slate-900 dark:hover:text-white select-none">
                        <input type="checkbox" :checked="isAllTargetsSelected" @change="toggleSelectAllTargets()" class="form-checkbox" style="width:15px;height:15px;border-radius:3px;">
                        <span x-text="isAllTargetsSelected ? 'Batal Pilih Semua' : 'Pilih Semua (' + availableTargetProducts.length + ' produk)'"></span>
                    </label>
                    <span class="badge" :class="selectedTargetItemIds.length > 0 ? 'badge-primary' : 'badge-secondary'" style="font-size:11px;font-weight:700;padding:2.5px 10px;white-space:nowrap;" x-text="selectedTargetItemIds.length + ' produk terpilih'"></span>
                </div>

                <!-- DAFTAR PRODUK TARGET (CHECKBOX LIST) -->
                <div class="max-h-60 overflow-y-auto custom-scrollbar border rounded-lg p-1.5 space-y-1" style="background:var(--color-canvas, #ffffff);border-color:var(--color-hairline);">
                    <template x-for="p in availableTargetProducts" :key="p.id">
                        <label class="flex items-center justify-between p-2 rounded hover:bg-slate-50 dark:hover:bg-slate-800 transition cursor-pointer border border-transparent hover:border-hairline"
                               :style="selectedTargetItemIds.includes(p.id) ? 'background:rgba(37,99,235,0.05);border-color:rgba(37,99,235,0.25);' : ''">
                            <div class="flex items-center gap-2.5 min-w-0 flex-1 mr-2">
                                <input type="checkbox" name="target_item_ids[]" :value="p.id" x-model="selectedTargetItemIds" class="form-checkbox shrink-0" style="width:16px;height:16px;border-radius:4px;">
                                <span class="badge badge-mono text-xs font-bold shrink-0" x-text="p.kode_sku"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-xs truncate" style="color:var(--color-ink);" x-text="p.nama_item"></div>
                                    <div class="text-xs truncate flex items-center gap-1.5" style="font-size:10.5px;color:var(--color-ink-mute);">
                                        <span x-text="p.nama_grup || 'Tanpa Grup'"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="shrink-0">
                                <template x-if="!p.total_resep_bahan || Number(p.total_resep_bahan) === 0">
                                    <span class="badge badge-warning" style="font-size:10px;padding:2px 6px;">Belum Ada Resep</span>
                                </template>
                                <template x-if="Number(p.total_resep_bahan) > 0">
                                    <span class="badge badge-secondary" style="font-size:10px;padding:2px 6px;" x-text="p.total_resep_bahan + ' Bahan'"></span>
                                </template>
                            </div>
                        </label>
                    </template>

                    <template x-if="availableTargetProducts.length === 0">
                        <div class="text-center py-8 px-4" style="color:var(--color-ink-mute);">
                            <i data-lucide="inbox" style="width:32px;height:32px;margin:0 auto 6px auto;opacity:0.4;"></i>
                            <div class="text-xs font-semibold">Tidak ada produk yang cocok</div>
                            <div class="text-xs mt-1" x-show="copyOnlyWithoutRecipe">Semua produk lainnya sudah memiliki resep BOM. Matikan centang filter jika ingin menimpa resep produk lain.</div>
                        </div>
                    </template>
                </div>

                <div class="p-3 rounded-lg border border-blue-200 bg-blue-50 dark:bg-blue-950/30 dark:border-blue-800 text-blue-800 dark:text-blue-300 text-xs leading-relaxed flex items-start gap-2">
                    <i data-lucide="info" style="width:15px;height:15px;flex-shrink:0;margin-top:2px;"></i>
                    <span>Resep dari <strong><span x-text="selectedRecipeProduct?.nama_item"></span></strong> akan disalin dan langsung diterapkan ke <strong><span x-text="selectedTargetItemIds.length"></span> produk tujuan</strong> yang dicentang.</span>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px;">
                    <button type="button" @click="showCopyRecipeModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary" :disabled="selectedTargetItemIds.length === 0">
                        <i data-lucide="copy"></i>
                        <span x-text="selectedTargetItemIds.length > 0 ? 'Salin &amp; Terapkan ke (' + selectedTargetItemIds.length + ') Produk' : 'Pilih Minimal 1 Produk'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- MODAL 5: TAMBAH / EDIT KELOMPOK UPAH BORONGAN -->
    <template x-teleport="body">
    <div x-show="showBoronganModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:480px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title" x-text="isEditBorongan ? 'Edit Kelompok Upah Borongan' : 'Tambah Kelompok Upah Borongan'"></div>
            </div>

            <form :action="isEditBorongan ? '<?= Router::url('/products/update-borongan-group') ?>' : '<?= Router::url('/products/store-borongan-group') ?>'" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="id" :value="boronganForm.id">

                <div>
                    <label class="form-label">Nama Kelompok Borongan *</label>
                    <input type="text" name="nama_kelompok" x-model="boronganForm.nama_kelompok" required class="form-input" placeholder="Contoh: Kelompok 600">
                </div>

                <div>
                    <label class="form-label">Tarif Upah per Bungkus (Rp/pcs) *</label>
                    <input type="text" name="upah_per_bungkus" x-model="boronganForm.upah_per_bungkus" required class="form-input font-mono input-rupiah" placeholder="600">
                </div>

                <div>
                    <label class="form-label">Keterangan / Deskripsi</label>
                    <textarea name="keterangan" x-model="boronganForm.keterangan" class="form-input" rows="2" placeholder="Contoh: Tarif borongan pack bungkus Rp 600/pcs"></textarea>
                </div>

                <template x-if="isEditBorongan">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12.5px;font-weight:600;">
                        <input type="checkbox" name="status_aktif" x-model="boronganForm.status_aktif" style="width:16px;height:16px;accent-color:var(--color-primary);">
                        <span>Kelompok Upah Borongan Aktif</span>
                    </label>
                </template>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showBoronganModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span x-text="isEditBorongan ? 'Simpan Perubahan' : 'Tambah Kelompok'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>
    <!-- MODAL 6: TAMBAH / EDIT MASTER MEREK -->
    <template x-teleport="body">
    <div x-show="showBrandModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:460px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title" x-text="isEditBrand ? 'Edit Merek Produk' : 'Tambah Merek Produk Baru'"></div>
            </div>

            <form :action="isEditBrand ? '<?= Router::url('/products/update-brand') ?>' : '<?= Router::url('/products/store-brand') ?>'" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="id" :value="brandForm.id">

                <div>
                    <label class="form-label">Kode Merek</label>
                    <input type="text" name="kode_merek" x-model="brandForm.kode_merek" :readonly="isEditBrand" class="form-input font-mono" placeholder="Otomatis (Contoh: KRN / KEREN)">
                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;">
                        <span x-show="!isEditBrand">💡 Kosongkan untuk kode otomatis <code>MRK-XXX</code> atau ketik kode khusus.</span>
                        <span x-show="isEditBrand">🔒 Kode merek bersifat permanen untuk integritas database.</span>
                    </div>
                </div>

                <div>
                    <label class="form-label">Nama Merek Dagang *</label>
                    <input type="text" name="nama_merek" x-model="brandForm.nama_merek" required class="form-input" placeholder="Contoh: KEREN SNACK, SNACK NUSANTARA">
                </div>

                <template x-if="isEditBrand">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12.5px;font-weight:600;">
                        <input type="checkbox" name="status_aktif" x-model="brandForm.status_aktif" style="width:16px;height:16px;accent-color:var(--color-primary);">
                        <span>Status Merek Aktif</span>
                    </label>
                </template>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showBrandModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span x-text="isEditBrand ? 'Simpan Perubahan' : 'Tambah Merek'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>
    <?php endif; ?>

    <!-- HIDDEN FORM FOR DELETING ITEM, GROUP, BRAND, MATERIAL, RECIPE & BORONGAN -->
    <?php if (\App\Core\Auth::can('master.products_manage')): ?>
    <form id="delete-item-form" action="<?= Router::url('/products/delete-item') ?>" method="POST" data-action-text="Menghapus barang jadi..." style="display:none;">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="id" id="delete-item-id">
    </form>
    <form id="delete-group-form" action="<?= Router::url('/products/delete-group') ?>" method="POST" data-action-text="Menghapus grup kemasan..." style="display:none;">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="id" id="delete-group-id">
    </form>
    <form id="delete-brand-form" action="<?= Router::url('/products/delete-brand') ?>" method="POST" data-action-text="Menghapus merek..." style="display:none;">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="id" id="delete-brand-id">
    </form>
    <?php endif; ?>
    <?php if (\App\Core\Auth::can(['master.materials_manage', 'master.products_manage'])): ?>
    <form id="delete-material-form" action="<?= Router::url('/products/delete-material') ?>" method="POST" data-action-text="Menghapus bahan mentah/kemasan..." style="display:none;">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="id" id="delete-material-id">
    </form>
    <?php endif; ?>
    <?php if (\App\Core\Auth::can('master.products_manage')): ?>
    <form id="delete-recipe-form" action="<?= Router::url('/products/delete-recipe-item') ?>" method="POST" data-action-text="Menghapus komponen resep..." style="display:none;">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="id" id="delete-recipe-id">
    </form>
    <form id="delete-borongan-form" action="<?= Router::url('/products/delete-borongan-group') ?>" method="POST" data-action-text="Menghapus kelompok borongan..." style="display:none;">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="id" id="delete-borongan-id">
    </form>
    <?php endif; ?>

</div>

<script>
function productApp(initialTab, initialRecipeItemId) {
    return {
        activeTab: initialTab || 'finished_goods',
        brands: <?= json_encode($brands ?? []) ?>,
        groups: <?= json_encode($groups) ?>,
        finishedGoods: <?= json_encode($finishedGoods) ?>,
        allFinishedGoods: <?= json_encode($allFinishedGoodsList ?? $finishedGoods) ?>,
        materials: <?= json_encode($materials) ?>,
        recipes: <?= json_encode($recipes) ?>,
        recipesByFinishedGood: <?= json_encode($recipesByFinishedGood) ?>,
        wageGroups: <?= json_encode($wageGroups) ?>,

        searchFg: '',
        selectedGroupFilter: 'all',
        searchMat: '',
        materialTypeFilter: 'all',
        selectedRecipeProductId: initialRecipeItemId || '',
        searchBorongan: '',
        searchBrand: '',

        showItemModal: false,
        showGroupModal: false,
        showManageGroupsModal: false,
        showEditGroupModal: false,
        showMaterialModal: false,
        showRecipeModal: false,
        showCopyRecipeModal: false,
        showBoronganModal: false,
        showBrandModal: false,

        isEditItem: false,
        isEditMaterial: false,
        isEditBorongan: false,
        isEditBrand: false,

        selectedTargetItemIds: [],
        copySearchQuery: '',
        copyOnlyWithoutRecipe: true,
        recipeInputMode: 'yield',
        recipeYieldPcs: '',

        brandForm: {
            id: '',
            kode_merek: '',
            nama_merek: '',
            status_aktif: true
        },

        editGroupForm: {
            id: '',
            kode_grup: '',
            nama_grup: '',
            barcode_universal: '',
            merek_id: '',
            status_aktif: true
        },

        boronganForm: {
            id: '',
            nama_kelompok: '',
            upah_per_bungkus: '',
            keterangan: '',
            status_aktif: true
        },

        itemForm: {
            id: '',
            grup_id: '',
            nama_item: '',
            kelompok_borongan_id: '',
            harga_pokok_pembelian: '10.000',
            stok_minimum_peringatan: 10,
            stok_awal: 0,
            status_jual: true,
            status_aktif: true
        },

        materialForm: {
            id: '',
            nama_item: '',
            tipe_item: 'bahan_mentah',
            satuan_dasar: 'kg',
            pemasok_utama_id: '',
            harga_pokok_pembelian: '250.000',
            stok_minimum_peringatan: 5,
            stok_awal: 0,
            status_aktif: true
        },

        recipeForm: {
            item_bahan_id: '',
            jumlah_kebutuhan: ''
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
            this.$watch('selectedRecipeProductId', () => {
                this.$nextTick(() => lucide.createIcons());
            });
            this.$watch('activeTab', () => {
                this.$nextTick(() => lucide.createIcons());
            });
        },

        get filteredFinishedGoods() {
            return this.finishedGoods.filter(i => {
                const q = this.searchFg.toLowerCase();
                const matchQuery = !q ||
                    i.nama_item.toLowerCase().includes(q) ||
                    i.kode_sku.toLowerCase().includes(q) ||
                    (i.barcode_universal && i.barcode_universal.includes(q)) ||
                    (i.nama_grup && i.nama_grup.toLowerCase().includes(q));

                const matchGroup = this.selectedGroupFilter === 'all' || i.grup_id === this.selectedGroupFilter;
                return matchQuery && matchGroup;
            });
        },

        get filteredMaterials() {
            return this.materials.filter(m => {
                const q = this.searchMat.toLowerCase();
                const matchQuery = !q ||
                    m.nama_item.toLowerCase().includes(q) ||
                    m.kode_sku.toLowerCase().includes(q) ||
                    (m.nama_pemasok && m.nama_pemasok.toLowerCase().includes(q));

                const matchType = this.materialTypeFilter === 'all' || m.tipe_item === this.materialTypeFilter;
                return matchQuery && matchType;
            });
        },

        get selectedRecipeProduct() {
            return (this.allFinishedGoods || this.finishedGoods).find(fg => fg.id === this.selectedRecipeProductId) || null;
        },

        get selectedMaterialInModal() {
            return this.materials.find(m => m.id === this.recipeForm.item_bahan_id) || null;
        },

        get currentProductRecipeList() {
            if (!this.selectedRecipeProductId) return [];
            return this.recipesByFinishedGood[this.selectedRecipeProductId] || [];
        },

        get recipeSummary() {
            const list = this.currentProductRecipeList;
            let rawCost = 0;
            let rawCount = 0;
            let pkgCost = 0;
            let pkgCount = 0;

            list.forEach(r => {
                const cost = Number(r.subtotal_biaya_bahan || 0);
                if (r.item_bahan_tipe === 'bahan_mentah') {
                    rawCost += cost;
                    rawCount++;
                } else {
                    pkgCost += cost;
                    pkgCount++;
                }
            });

            const wageCost = Number(this.selectedRecipeProduct?.upah_bungkus_efektif || 0);
            const totalHpp = rawCost + pkgCost + wageCost;

            return {
                rawMaterialCost: rawCost,
                rawMaterialCount: rawCount,
                packagingCost: pkgCost,
                packagingCount: pkgCount,
                wageCost: wageCost,
                totalHpp: totalHpp
            };
        },

        get availableTargetProducts() {
            if (!this.selectedRecipeProductId) return [];
            return (this.allFinishedGoods || this.finishedGoods).filter(p => {
                if (p.id === this.selectedRecipeProductId) return false;
                const hasRecipe = Number(p.total_resep_bahan || 0) > 0;
                if (this.copyOnlyWithoutRecipe && hasRecipe) return false;
                if (this.copySearchQuery) {
                    const q = this.copySearchQuery.toLowerCase();
                    const matchName = p.nama_item && p.nama_item.toLowerCase().includes(q);
                    const matchSku = p.kode_sku && p.kode_sku.toLowerCase().includes(q);
                    const matchGroup = p.nama_grup && p.nama_grup.toLowerCase().includes(q);
                    return matchName || matchSku || matchGroup;
                }
                return true;
            });
        },

        get isAllTargetsSelected() {
            const list = this.availableTargetProducts;
            return list.length > 0 && this.selectedTargetItemIds.length >= list.length;
        },

        toggleSelectAllTargets() {
            const list = this.availableTargetProducts;
            if (this.isAllTargetsSelected) {
                this.selectedTargetItemIds = [];
            } else {
                this.selectedTargetItemIds = list.map(p => p.id);
            }
        },

        get estimatedCostPerPiece() {
            const mat = this.selectedMaterialInModal;
            if (!mat) return 0;
            const hpp = Number(mat.harga_pokok_pembelian || 0);
            const qty = Number(this.recipeForm.jumlah_kebutuhan || 0);
            return Math.round(hpp * qty);
        },

        get filteredWageGroups() {
            return this.wageGroups.filter(w => {
                const q = (this.searchBorongan || '').toLowerCase();
                return !q ||
                    (w.nama_kelompok && w.nama_kelompok.toLowerCase().includes(q)) ||
                    (w.keterangan && w.keterangan.toLowerCase().includes(q)) ||
                    (w.upah_per_bungkus && String(w.upah_per_bungkus).includes(q));
            });
        },

        get filteredBrands() {
            return this.brands.filter(b => {
                const q = (this.searchBrand || '').toLowerCase();
                return !q ||
                    (b.nama_merek && b.nama_merek.toLowerCase().includes(q)) ||
                    (b.kode_merek && b.kode_merek.toLowerCase().includes(q));
            });
        },

        selectProductForRecipe(fgId) {
            this.selectedRecipeProductId = fgId;
            this.activeTab = 'recipes';
            this.$nextTick(() => lucide.createIcons());
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        formatQty(val) {
            if (val === null || val === undefined || val === '') return '0';
            const num = Number(val);
            if (isNaN(num)) return '0';
            return num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 4 });
        },

        onRecipeMaterialChange() {
            const mat = this.selectedMaterialInModal;
            if (mat && (mat.satuan_dasar === 'lembar' || mat.satuan_dasar === 'pcs')) {
                this.recipeInputMode = 'direct';
                this.recipeForm.jumlah_kebutuhan = '1';
                this.recipeYieldPcs = '1';
            } else {
                if (this.recipeInputMode === 'yield') {
                    this.calculateFromYield();
                } else {
                    this.calculateFromDirect();
                }
            }
        },

        calculateFromYield() {
            const yieldNum = Number(this.recipeYieldPcs);
            if (yieldNum > 0) {
                const perPiece = 1 / yieldNum;
                this.recipeForm.jumlah_kebutuhan = Number(perPiece.toFixed(6)).toString();
            } else {
                this.recipeForm.jumlah_kebutuhan = '';
            }
        },

        calculateFromDirect() {
            const qty = Number(this.recipeForm.jumlah_kebutuhan);
            if (qty > 0) {
                this.recipeYieldPcs = Number((1 / qty).toFixed(2)).toString();
            } else {
                this.recipeYieldPcs = '';
            }
        },

        // --- BARANG JADI MODALS ---
        openAddItemModal() {
            this.showGroupModal = false;
            this.showMaterialModal = false;
            this.showRecipeModal = false;
            this.showCopyRecipeModal = false;
            this.isEditItem = false;
            this.itemForm = {
                id: '',
                grup_id: this.groups[0]?.id || '',
                nama_item: '',
                kelompok_borongan_id: '',
                harga_pokok_pembelian: '10.000',
                stok_minimum_peringatan: 10,
                stok_awal: 0,
                status_jual: true,
                status_aktif: true
            };
            this.showItemModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditItemModal(it) {
            this.showGroupModal = false;
            this.showMaterialModal = false;
            this.showRecipeModal = false;
            this.showCopyRecipeModal = false;
            this.isEditItem = true;
            this.itemForm = {
                id: it.id,
                grup_id: it.grup_id || '',
                nama_item: it.nama_item,
                kelompok_borongan_id: it.kelompok_borongan_id || '',
                harga_pokok_pembelian: window.formatRupiahNumber ? window.formatRupiahNumber(it.harga_pokok_pembelian) : String(it.harga_pokok_pembelian || 0),
                stok_minimum_peringatan: Number(it.stok_minimum_peringatan || 10),
                status_jual: Boolean(it.status_jual),
                status_aktif: Boolean(it.status_aktif)
            };
            this.showItemModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async deleteItem(id, name) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Barang Jadi',
                message: `Apakah Anda yakin ingin menghapus barang jadi "${name}"? Produk yang sudah memiliki riwayat transaksi/stok hanya akan dinonaktifkan secara aman.`,
                type: 'danger',
                confirmText: 'Ya, Hapus'
            }) : confirm(`Hapus barang jadi "${name}"?`);

            if (confirmed) {
                document.getElementById('delete-item-id').value = id;
                document.getElementById('delete-item-form').submit();
            }
        },

        openManageGroupsModal() {
            this.showItemModal = false;
            this.showGroupModal = false;
            this.showMaterialModal = false;
            this.showRecipeModal = false;
            this.showCopyRecipeModal = false;
            this.showManageGroupsModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openAddGroupModal() {
            this.showItemModal = false;
            this.showMaterialModal = false;
            this.showRecipeModal = false;
            this.showCopyRecipeModal = false;
            this.showManageGroupsModal = false;
            this.showEditGroupModal = false;
            this.showGroupModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditGroupModal(g) {
            this.editGroupForm = {
                id: g.id,
                kode_grup: g.kode_grup,
                nama_grup: g.nama_grup,
                barcode_universal: g.barcode_universal || '',
                merek_id: g.merek_id || (this.brands[0]?.id || ''),
                status_aktif: Boolean(g.status_aktif)
            };
            this.showManageGroupsModal = false;
            this.showEditGroupModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async deleteGroup(id, name) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Grup Kemasan',
                message: `Apakah Anda yakin ingin menghapus grup "${name}"? Grup hanya dapat dihapus jika tidak ada SKU yang terhubung.`,
                type: 'danger',
                confirmText: 'Ya, Hapus'
            }) : confirm(`Hapus grup kemasan "${name}"?`);

            if (confirmed) {
                document.getElementById('delete-group-id').value = id;
                document.getElementById('delete-group-form').submit();
            }
        },

        // --- MATERIAL MODALS ---
        openAddMaterialModal() {
            this.showItemModal = false;
            this.showGroupModal = false;
            this.showRecipeModal = false;
            this.showCopyRecipeModal = false;
            this.isEditMaterial = false;
            this.materialForm = {
                id: '',
                nama_item: '',
                tipe_item: 'bahan_mentah',
                satuan_dasar: 'kg',
                pemasok_utama_id: '',
                harga_pokok_pembelian: '250.000',
                stok_minimum_peringatan: 5,
                stok_awal: 0,
                status_aktif: true
            };
            this.showMaterialModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditMaterialModal(mat) {
            this.showItemModal = false;
            this.showGroupModal = false;
            this.showRecipeModal = false;
            this.showCopyRecipeModal = false;
            this.isEditMaterial = true;
            this.materialForm = {
                id: mat.id,
                nama_item: mat.nama_item,
                tipe_item: mat.tipe_item,
                satuan_dasar: mat.satuan_dasar || 'kg',
                pemasok_utama_id: mat.pemasok_utama_id || '',
                harga_pokok_pembelian: window.formatRupiahNumber ? window.formatRupiahNumber(mat.harga_pokok_pembelian) : String(mat.harga_pokok_pembelian || 0),
                stok_minimum_peringatan: Number(mat.stok_minimum_peringatan || 5),
                status_aktif: Boolean(mat.status_aktif)
            };
            this.showMaterialModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async deleteMaterial(id, name) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Bahan / Kemasan',
                message: `Apakah Anda yakin ingin menghapus bahan "${name}"?`,
                type: 'danger',
                confirmText: 'Ya, Hapus'
            }) : confirm(`Hapus bahan "${name}"?`);

            if (confirmed) {
                document.getElementById('delete-material-id').value = id;
                document.getElementById('delete-material-form').submit();
            }
        },

        // --- RECIPE BOM MODALS ---
        openAddRecipeItemModal() {
            this.showItemModal = false;
            this.showGroupModal = false;
            this.showMaterialModal = false;
            this.showCopyRecipeModal = false;
            this.recipeInputMode = 'yield';
            this.recipeYieldPcs = '';
            this.recipeForm = {
                item_bahan_id: this.materials[0]?.id || '',
                jumlah_kebutuhan: ''
            };
            const firstMat = this.materials[0];
            if (firstMat && (firstMat.satuan_dasar === 'lembar' || firstMat.satuan_dasar === 'pcs')) {
                this.recipeInputMode = 'direct';
                this.recipeForm.jumlah_kebutuhan = '1';
                this.recipeYieldPcs = '1';
            }
            this.showRecipeModal = true;
            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        openCopyRecipeModal() {
            if (!this.selectedRecipeProduct) return;
            if (this.currentProductRecipeList.length === 0) {
                if (window.AppAlert) {
                    window.AppAlert({
                        title: 'Belum Ada Resep',
                        message: 'Produk ini belum memiliki komponen bahan resep untuk disalin. Silakan tambahkan komponen bahan terlebih dahulu.',
                        type: 'warning'
                    });
                } else {
                    alert('Produk ini belum memiliki komponen bahan resep untuk disalin. Silakan tambahkan komponen bahan terlebih dahulu.');
                }
                return;
            }
            this.showItemModal = false;
            this.showGroupModal = false;
            this.showMaterialModal = false;
            this.showRecipeModal = false;
            this.copySearchQuery = '';
            this.copyOnlyWithoutRecipe = true;
            this.selectedTargetItemIds = [];
            this.showCopyRecipeModal = true;
            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        async deleteRecipeItem(id) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Komponen Resep',
                message: 'Apakah Anda yakin ingin menghapus komponen bahan ini dari resep?',
                type: 'danger',
                confirmText: 'Ya, Hapus'
            }) : confirm('Hapus komponen resep ini?');

            if (confirmed) {
                document.getElementById('delete-recipe-id').value = id;
                document.getElementById('delete-recipe-form').submit();
            }
        },

        // --- BORONGAN GROUP MODALS ---
        openAddBoronganModal() {
            this.showItemModal = false;
            this.showGroupModal = false;
            this.showMaterialModal = false;
            this.showRecipeModal = false;
            this.showCopyRecipeModal = false;
            this.isEditBorongan = false;
            this.boronganForm = {
                id: '',
                nama_kelompok: '',
                upah_per_bungkus: '500',
                keterangan: '',
                status_aktif: true
            };
            this.showBoronganModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditBoronganModal(w) {
            this.showItemModal = false;
            this.showGroupModal = false;
            this.showMaterialModal = false;
            this.showRecipeModal = false;
            this.showCopyRecipeModal = false;
            this.isEditBorongan = true;
            this.boronganForm = {
                id: w.id,
                nama_kelompok: w.nama_kelompok,
                upah_per_bungkus: window.formatRupiahNumber ? window.formatRupiahNumber(w.upah_per_bungkus) : String(w.upah_per_bungkus || 0),
                keterangan: w.keterangan || '',
                status_aktif: Boolean(w.status_aktif)
            };
            this.showBoronganModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async deleteBoronganGroup(id, name) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Kelompok Upah Borongan',
                message: `Apakah Anda yakin ingin menghapus kelompok "${name}"?`,
                type: 'danger',
                confirmText: 'Ya, Hapus'
            }) : confirm(`Hapus kelompok borongan "${name}"?`);

            if (confirmed) {
                document.getElementById('delete-borongan-id').value = id;
                document.getElementById('delete-borongan-form').submit();
            }
        },

        // --- BRAND MODALS ---
        openAddBrandModal() {
            this.showItemModal = false;
            this.showGroupModal = false;
            this.showMaterialModal = false;
            this.showRecipeModal = false;
            this.showCopyRecipeModal = false;
            this.showBoronganModal = false;
            this.isEditBrand = false;
            this.brandForm = {
                id: '',
                kode_merek: '',
                nama_merek: '',
                status_aktif: true
            };
            this.showBrandModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditBrandModal(b) {
            this.showItemModal = false;
            this.showGroupModal = false;
            this.showMaterialModal = false;
            this.showRecipeModal = false;
            this.showCopyRecipeModal = false;
            this.showBoronganModal = false;
            this.isEditBrand = true;
            this.brandForm = {
                id: b.id,
                kode_merek: b.kode_merek,
                nama_merek: b.nama_merek,
                status_aktif: Boolean(b.status_aktif)
            };
            this.showBrandModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async deleteBrand(id, name) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Merek Produk',
                message: `Apakah Anda yakin ingin menghapus merek "${name}"? Merek hanya dapat dihapus jika tidak ada grup produk yang terhubung.`,
                type: 'danger',
                confirmText: 'Ya, Hapus'
            }) : confirm(`Hapus merek "${name}"?`);

            if (confirmed) {
                document.getElementById('delete-brand-id').value = id;
                document.getElementById('delete-brand-form').submit();
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>


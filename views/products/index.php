<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
$activeTab = $_GET['tab'] ?? 'finished_goods';
?>

<div x-data="productApp('<?= htmlspecialchars($activeTab) ?>')" x-init="init()" class="space-y-5">

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
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                <i data-lucide="package"></i>
            </div>
            <div>
                <div class="stat-card-label">Barang Jadi (Siap Jual)</div>
                <div class="stat-card-value" style="color:#3b82f6;"><?= count($finishedGoods) ?> SKU</div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;"><?= count($groups) ?> Grup Kemasan Universal</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="boxes"></i>
            </div>
            <div>
                <div class="stat-card-label">Bahan Baku &amp; Kemasan</div>
                <div class="stat-card-value" style="color:var(--color-primary);"><?= count($materials) ?> Item</div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Bal Curah, Plastik &amp; Stiker</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
                <i data-lucide="git-merge"></i>
            </div>
            <div>
                <div class="stat-card-label">Resep BOM Terhubung</div>
                <div class="stat-card-value" style="color:#f59e0b;"><?= count($recipes) ?> Relasi</div>
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
            <span>1. Barang Jadi (<?= count($finishedGoods) ?>)</span>
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
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: KATALOG BARANG JADI (FINISHED GOODS)                               -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'finished_goods'" class="card" style="padding:0;overflow:hidden;">
        <!-- ACTION & FILTER BAR -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="flex items-center gap-3 w-full sm:w-auto flex-1">
                <div class="form-input-icon flex-1 sm:max-w-xs">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="text" x-model="searchFg" placeholder="Cari nama snack / varian / barcode..." class="form-input" style="height:38px;font-size:13px;">
                </div>

                <select x-model="selectedGroupFilter" class="form-input" style="height:38px;font-size:13px;max-width:220px;">
                    <option value="all">Semua Grup Kemasan</option>
                    <?php foreach ($groups as $g): ?>
                    <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama_grup']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button @click="openAddGroupModal()" class="btn btn-secondary" style="height:38px;">
                    <i data-lucide="folder-plus"></i>
                    <span>+ Grup Kemasan</span>
                </button>
                <button @click="openAddItemModal()" class="btn btn-primary" style="height:38px;">
                    <i data-lucide="plus"></i>
                    <span>+ Barang Jadi</span>
                </button>
            </div>
        </div>

        <!-- TABLE LIST BARANG JADI -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 980px;">
                <thead>
                    <tr>
                        <th style="width:100px; min-width:90px;" class="cell-nowrap">SKU</th>
                        <th style="min-width:180px;">Nama Item &amp; Varian Rasa</th>
                        <th style="min-width:180px;">Grup Kemasan &amp; Barcode Universal</th>
                        <th style="min-width:160px;">Kelompok Upah Borongan</th>
                        <th class="cell-center cell-nowrap" style="width:120px; min-width:110px;">Resep BOM</th>
                        <th class="cell-right cell-nowrap" style="width:120px; min-width:110px;">HPP Acuan</th>
                        <th class="cell-center cell-nowrap" style="width:110px; min-width:100px;">Stok Fisik</th>
                        <th class="cell-center cell-nowrap" style="width:90px; min-width:80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in filteredFinishedGoods" :key="item.id">
                        <tr :style="!item.status_aktif ? 'opacity:0.5;' : ''">
                            <td class="cell-nowrap">
                                <span class="badge badge-mono" x-text="item.kode_sku"></span>
                            </td>
                            <td>
                                <div style="font-weight:700;color:var(--color-ink);" x-text="item.nama_item"></div>
                                <div style="font-size:11px;color:var(--color-ink-mute);" x-text="'Varian: ' + (item.varian_rasa || '-')"></div>
                            </td>
                            <td>
                                <div style="font-weight:600;" x-text="item.nama_grup || '-'"></div>
                                <div style="font-size:11px;font-family:var(--font-mono);color:var(--color-primary-deep);" x-text="'Barcode: ' + (item.barcode || item.barcode_universal || '-')"></div>
                            </td>
                            <td class="cell-nowrap">
                                <template x-if="item.nama_kelompok">
                                    <div>
                                        <span class="badge badge-secondary" x-text="item.nama_kelompok"></span>
                                        <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;" x-text="formatRupiah(item.upah_per_bungkus) + '/pack'"></div>
                                    </div>
                                </template>
                                <template x-if="!item.nama_kelompok">
                                    <span style="color:var(--color-ink-mute);">-</span>
                                </template>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <template x-if="item.total_resep_bahan > 0">
                                    <button @click="selectProductForRecipe(item.id)" class="badge badge-success" style="cursor:pointer;" title="Klik untuk lihat resep">
                                        <span x-text="item.total_resep_bahan + ' Bahan'"></span>
                                    </button>
                                </template>
                                <template x-if="!item.total_resep_bahan || item.total_resep_bahan == 0">
                                    <button @click="selectProductForRecipe(item.id)" class="badge badge-secondary" style="cursor:pointer;opacity:0.7;" title="Klik untuk atur resep">
                                        <span>+ Atur Resep</span>
                                    </button>
                                </template>
                            </td>
                            <td class="cell-currency cell-right cell-nowrap" x-text="formatRupiah(item.harga_pokok_pembelian)"></td>
                            <td class="cell-center cell-nowrap">
                                <div>
                                    <span class="badge"
                                          :class="{
                                              'badge-danger': item.stok_fisik_saat_ini <= 0,
                                              'badge-warning': item.stok_fisik_saat_ini > 0 && item.stok_fisik_saat_ini <= item.stok_minimum_peringatan,
                                              'badge-success': item.stok_fisik_saat_ini > item.stok_minimum_peringatan
                                          }"
                                          style="font-family:var(--font-mono);font-size:11.5px;font-weight:700;"
                                          x-text="item.stok_fisik_saat_ini + ' ' + (item.satuan_dasar || 'pcs')"></span>
                                </div>
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;" x-text="'Min: ' + item.stok_minimum_peringatan"></div>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <button @click="openEditItemModal(item)" class="btn btn-ghost btn-sm" style="padding:6px 10px;" title="Edit Barang Jadi">
                                    <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                </button>
                            </td>
                        </tr>
                    </template>

                    <template x-if="filteredFinishedGoods.length === 0">
                        <tr>
                            <td colspan="8" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="search-x" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Tidak ada Barang Jadi yang cocok</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
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

            <div>
                <button @click="openAddMaterialModal()" class="btn btn-primary" style="height:38px;">
                    <i data-lucide="plus"></i>
                    <span>Tambah Bahan / Kemasan</span>
                </button>
            </div>
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
                        <th class="cell-center cell-nowrap" style="width:100px; min-width:90px;">Aksi</th>
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
                                          x-text="mat.stok_fisik_saat_ini + ' ' + mat.satuan_dasar"></span>
                                </div>
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;" x-text="'Min: ' + mat.stok_minimum_peringatan"></div>
                            </td>
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
                        </tr>
                    </template>

                    <template x-if="filteredMaterials.length === 0">
                        <tr>
                            <td colspan="8" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
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
        <!-- SELECTOR BARANG JADI -->
        <div class="card p-4">
            <div class="flex flex-col sm:flex-row items-end justify-between gap-3">
                <div class="flex-1 w-full">
                    <label class="form-label" style="font-size:13px;font-weight:700;margin-bottom:6px;">Pilih Produk Barang Jadi untuk Atur Resep BOM:</label>
                    <select x-model="selectedRecipeProductId" class="form-input searchable-select" style="height:42px;font-size:13.5px;font-weight:600;">
                        <option value="">-- Pilih Barang Jadi --</option>
                        <?php foreach ($finishedGoods as $fg): ?>
                        <option value="<?= $fg['id'] ?>"><?= htmlspecialchars($fg['kode_sku']) ?> - <?= htmlspecialchars($fg['nama_item']) ?> (Varian: <?= htmlspecialchars($fg['varian_rasa'] ?? '-') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div x-show="selectedRecipeProduct" style="flex-shrink:0;">
                    <button @click="openAddRecipeItemModal()" class="btn btn-primary" style="height:42px;white-space:nowrap;padding:0 16px;display:flex;align-items:center;gap:6px;">
                        <i data-lucide="plus-circle" style="width:16px;height:16px;"></i>
                        <span>Tambah Komponen Bahan</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- SELECTED PRODUCT RECIPE DETAIL -->
        <template x-if="selectedRecipeProduct">
            <div class="card p-0 overflow-hidden">
                <div class="p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);display:flex;justify-content:between;align-items:center;">
                    <div>
                        <div style="font-size:15px;font-weight:800;color:var(--color-ink);" x-text="selectedRecipeProduct.nama_item"></div>
                        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;" x-text="'SKU: ' + selectedRecipeProduct.kode_sku + ' | Grup Kemasan: ' + (selectedRecipeProduct.nama_grup || '-') + ' | Barcode: ' + (selectedRecipeProduct.barcode || selectedRecipeProduct.barcode_universal || '-')"></div>
                    </div>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="data-table" style="min-width: 780px;">
                        <thead>
                            <tr>
                                <th style="width:110px; min-width:90px;" class="cell-nowrap">Kode Bahan</th>
                                <th style="min-width:180px;">Nama Bahan Baku / Kemasan</th>
                                <th style="min-width:120px;">Kategori</th>
                                <th class="cell-right cell-nowrap" style="width:180px; min-width:150px;">Kebutuhan per 1 Pcs Jadi</th>
                                <th class="cell-center cell-nowrap" style="width:90px; min-width:80px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="r in currentProductRecipeList" :key="r.id">
                                <tr>
                                    <td class="cell-nowrap">
                                        <span class="badge badge-mono" x-text="r.item_bahan_sku"></span>
                                    </td>
                                    <td>
                                        <div style="font-weight:700;color:var(--color-ink);" x-text="r.item_bahan_nama"></div>
                                    </td>
                                    <td>
                                        <span class="badge"
                                              :class="r.item_bahan_tipe === 'bahan_mentah' ? 'badge-warning' : 'badge-secondary'"
                                              x-text="r.item_bahan_tipe === 'bahan_mentah' ? 'Curah Mentah' : 'Bahan Kemas'"></span>
                                    </td>
                                    <td class="cell-right cell-nowrap">
                                        <span style="font-family:var(--font-mono);font-size:13px;font-weight:700;color:var(--color-primary-deep);" x-text="Number(r.jumlah_kebutuhan).toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 4})"></span>
                                        <span style="font-size:11.5px;color:var(--color-ink-mute);margin-left:4px;" x-text="r.item_bahan_satuan"></span>
                                    </td>
                                    <td class="cell-center cell-nowrap">
                                        <button @click="deleteRecipeItem(r.id)" class="btn btn-ghost btn-sm" style="padding:6px;color:#ef4444;" title="Hapus dari Resep">
                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="currentProductRecipeList.length === 0">
                                <tr>
                                    <td colspan="5" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                        <i data-lucide="flask-conical" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                        <div style="font-weight:600;font-size:13px;">Belum ada komposisi bahan untuk produk ini</div>
                                        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:4px;">Klik "+ Tambah Komponen Bahan" untuk menautkan bahan balan curah, plastik kemasan, atau label.</div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
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

            <button @click="openAddBoronganModal()" class="btn btn-primary" style="height:38px;white-space:nowrap;">
                <i data-lucide="plus"></i>
                <span>+ Kelompok Borongan Baru</span>
            </button>
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
                        <th class="cell-center cell-nowrap" style="width:100px; min-width:85px;">Aksi</th>
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
                        </tr>
                    </template>

                    <template x-if="filteredWageGroups.length === 0">
                        <tr>
                            <td colspan="6" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
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
    <!-- MODALS SECTION                                                            -->
    <!-- ========================================================================= -->

    <!-- MODAL 1: TAMBAH / EDIT BARANG JADI -->
    <template x-teleport="body">
    <div x-show="showItemModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:540px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title" x-text="isEditItem ? 'Edit Barang Jadi' : 'Tambah Barang Jadi Baru'"></div>
                <button @click="showItemModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form :action="isEditItem ? '<?= Router::url('/products/update-item') ?>' : '<?= Router::url('/products/store-item') ?>'" method="POST" style="display:flex;flex-direction:column;gap:14px;">
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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nama Barang Jadi Lengkap *</label>
                        <input type="text" name="nama_item" x-model="itemForm.nama_item" required class="form-input" placeholder="Contoh: Berondong Beras Label Jagung">
                    </div>
                    <div>
                        <label class="form-label">Varian Rasa</label>
                        <input type="text" name="varian_rasa" x-model="itemForm.varian_rasa" class="form-input" placeholder="Contoh: Manis Gurih">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Barcode Khusus (Opsional)</label>
                        <input type="text" name="barcode" x-model="itemForm.barcode" 
                               @input="itemForm.barcode = $event.target.value.replace(/[^0-9]/g, '').slice(0, 13)"
                               maxlength="13" class="form-input font-mono" placeholder="88030173 (8–13 Digit Angka)">
                    </div>
                    <div>
                        <label class="form-label">Kelompok Upah Borongan Packing</label>
                        <select name="kelompok_borongan_id" x-model="itemForm.kelompok_borongan_id" class="form-input">
                            <option value="">-- Tanpa Upah Borongan --</option>
                            <?php foreach ($wageGroups as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['nama_kelompok']) ?> (Rp <?= number_format((float)$w['upah_per_bungkus'], 0, ',', '.') ?>/pack)</option>
                            <?php endforeach; ?>
                        </select>
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
        <div class="modal-box" style="max-width:440px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title">Tambah Grup Kemasan Baru</div>
                <button @click="showGroupModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/products/store-group') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
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

    <!-- MODAL 3: TAMBAH / EDIT BAHAN BAKU & KEMASAN -->
    <template x-teleport="body">
    <div x-show="showMaterialModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:500px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title" x-text="isEditMaterial ? 'Edit Bahan / Kemasan' : 'Tambah Bahan / Kemasan Baru'"></div>
                <button @click="showMaterialModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form :action="isEditMaterial ? '<?= Router::url('/products/update-material') ?>' : '<?= Router::url('/products/store-material') ?>'" method="POST" style="display:flex;flex-direction:column;gap:14px;">
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

    <!-- MODAL 4: TAMBAH KOMPONEN RESEP BOM -->
    <template x-teleport="body">
    <div x-show="showRecipeModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:460px;padding:24px;">
            <div class="modal-header">
                <div>
                    <div class="modal-title">Tambah Komponen Resep</div>
                    <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;" x-text="selectedRecipeProduct?.nama_item"></div>
                </div>
                <button @click="showRecipeModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/products/store-recipe-item') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="item_jadi_id" :value="selectedRecipeProduct?.id">

                <div>
                    <label class="form-label">Pilih Bahan Baku / Kemasan *</label>
                    <select name="item_bahan_id" x-model="recipeForm.item_bahan_id" required class="form-input searchable-select">
                        <option value="">-- Pilih Bahan Mentah / Kemasan --</option>
                        <?php foreach ($materials as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_item']) ?> (<?= $m['tipe_item'] === 'bahan_mentah' ? 'Mentah Curah' : 'Kemas' ?> - <?= $m['satuan_dasar'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Jumlah Kebutuhan per 1 Bungkus/Pcs Barang Jadi *</label>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="number" step="0.0001" name="jumlah_kebutuhan" x-model="recipeForm.jumlah_kebutuhan" required class="form-input font-mono flex-1" placeholder="Contoh: 0.15">
                        <span class="badge badge-secondary" style="font-family:var(--font-mono);font-size:12.5px;height:38px;display:flex;align-items:center;padding:0 12px;font-weight:700;" x-text="selectedMaterialInModal?.satuan_dasar || 'satuan'"></span>
                    </div>
                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:5px;line-height:1.4;">
                        💡 <em>Misal: jika 1 bungkus snack butuh 150 gram makaroni balan, isi <code>0.15</code> kg. Jika butuh 1 lembar plastik/stiker label, isi <code>1</code> lembar.</em>
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showRecipeModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span>Simpan Komponen</span>
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
                <button @click="showBoronganModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
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

    <!-- HIDDEN FORM FOR DELETING MATERIAL, RECIPE & BORONGAN -->
    <form id="delete-material-form" action="<?= Router::url('/products/delete-material') ?>" method="POST" data-action-text="Menghapus bahan mentah/kemasan..." style="display:none;">
        <input type="hidden" name="id" id="delete-material-id">
    </form>
    <form id="delete-recipe-form" action="<?= Router::url('/products/delete-recipe-item') ?>" method="POST" data-action-text="Menghapus komponen resep..." style="display:none;">
        <input type="hidden" name="id" id="delete-recipe-id">
    </form>
    <form id="delete-borongan-form" action="<?= Router::url('/products/delete-borongan-group') ?>" method="POST" data-action-text="Menghapus kelompok borongan..." style="display:none;">
        <input type="hidden" name="id" id="delete-borongan-id">
    </form>

</div>

<script>
function productApp(initialTab) {
    return {
        activeTab: initialTab || 'finished_goods',
        groups: <?= json_encode($groups) ?>,
        finishedGoods: <?= json_encode($finishedGoods) ?>,
        materials: <?= json_encode($materials) ?>,
        recipes: <?= json_encode($recipes) ?>,
        recipesByFinishedGood: <?= json_encode($recipesByFinishedGood) ?>,
        wageGroups: <?= json_encode($wageGroups) ?>,

        searchFg: '',
        selectedGroupFilter: 'all',
        searchMat: '',
        materialTypeFilter: 'all',
        selectedRecipeProductId: '',
        searchBorongan: '',

        showItemModal: false,
        showGroupModal: false,
        showMaterialModal: false,
        showRecipeModal: false,
        showBoronganModal: false,

        isEditItem: false,
        isEditMaterial: false,
        isEditBorongan: false,

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
            varian_rasa: '',
            barcode: '',
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
            jumlah_kebutuhan: '1'
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        get filteredFinishedGoods() {
            return this.finishedGoods.filter(i => {
                const q = this.searchFg.toLowerCase();
                const matchQuery = !q ||
                    i.nama_item.toLowerCase().includes(q) ||
                    i.kode_sku.toLowerCase().includes(q) ||
                    (i.barcode && i.barcode.includes(q)) ||
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
            return this.finishedGoods.find(fg => fg.id === this.selectedRecipeProductId) || null;
        },

        get selectedMaterialInModal() {
            return this.materials.find(m => m.id === this.recipeForm.item_bahan_id) || null;
        },

        get currentProductRecipeList() {
            if (!this.selectedRecipeProductId) return [];
            return this.recipesByFinishedGood[this.selectedRecipeProductId] || [];
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

        selectProductForRecipe(fgId) {
            this.selectedRecipeProductId = fgId;
            this.activeTab = 'recipes';
            this.$nextTick(() => lucide.createIcons());
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        // --- BARANG JADI MODALS ---
        openAddItemModal() {
            this.showGroupModal = false;
            this.showMaterialModal = false;
            this.showRecipeModal = false;
            this.isEditItem = false;
            this.itemForm = {
                id: '',
                grup_id: this.groups[0]?.id || '',
                nama_item: '',
                varian_rasa: '',
                barcode: '',
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
            this.isEditItem = true;
            this.itemForm = {
                id: it.id,
                grup_id: it.grup_id || '',
                nama_item: it.nama_item,
                varian_rasa: it.varian_rasa || '',
                barcode: it.barcode || '',
                kelompok_borongan_id: it.kelompok_borongan_id || '',
                harga_pokok_pembelian: window.formatRupiahNumber ? window.formatRupiahNumber(it.harga_pokok_pembelian) : String(it.harga_pokok_pembelian || 0),
                stok_minimum_peringatan: Number(it.stok_minimum_peringatan || 10),
                status_jual: Boolean(it.status_jual),
                status_aktif: Boolean(it.status_aktif)
            };
            this.showItemModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openAddGroupModal() {
            this.showItemModal = false;
            this.showMaterialModal = false;
            this.showRecipeModal = false;
            this.showGroupModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        // --- MATERIAL MODALS ---
        openAddMaterialModal() {
            this.showItemModal = false;
            this.showGroupModal = false;
            this.showRecipeModal = false;
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
            this.recipeForm = {
                item_bahan_id: this.materials[0]?.id || '',
                jumlah_kebutuhan: '1'
            };
            this.showRecipeModal = true;
            this.$nextTick(() => {
                lucide.createIcons();
                if (window.initSearchableSelects) window.initSearchableSelects();
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
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>


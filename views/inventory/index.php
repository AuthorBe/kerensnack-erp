<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="inventoryApp()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-emerald">
                <i data-lucide="warehouse"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot"></span>
                    <span>Inventaris Gudang</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Katalog & Mutasi Stok Fisik' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Monitoring stok realtime, status ketersediaan & kartu stok gudang' ?></p>
            </div>
        </div>
        <div class="page-header-actions">
            <div class="form-input-icon w-full sm:w-80">
                <i data-lucide="search" class="icon-left"></i>
                <input type="text" x-model="searchQuery"
                       placeholder="Cari SKU, Nama atau Barcode..."
                       class="form-input" style="height:40px;">
            </div>
        </div>
    </div>

    <!-- DATA TABLE -->
    <div class="table-wrapper">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="min-width:200px;">Produk &amp; SKU</th>
                        <th class="hide-sm">Barcode</th>
                        <th style="text-align:right;">Stok Fisik</th>
                        <th class="hide-mobile" style="text-align:center;">Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="filteredItems.length === 0">
                        <tr>
                            <td colspan="5" style="text-align:center;padding:48px 16px;color:var(--color-ink-mute-2);font-family:var(--font-mono);font-size:12px;">
                                Tidak ada produk yang cocok dengan "<span x-text="searchQuery"></span>".
                            </td>
                        </tr>
                    </template>

                    <template x-for="item in filteredItems" :key="item.id">
                        <tr>
                            <!-- Produk & SKU -->
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <span class="badge badge-mono" x-text="item.kode_sku"></span>
                                    <span style="font-size:13px;font-weight:600;color:var(--color-ink);" x-text="item.nama_item"></span>
                                </div>
                                <div style="font-size:11px;font-family:var(--font-mono);color:var(--color-ink-mute-2);margin-top:3px;" x-text="item.kode_grup + ' • ' + item.nama_grup"></div>
                            </td>

                            <!-- Barcode -->
                            <td class="hide-sm">
                                <span style="font-family:var(--font-mono);font-size:12px;padding:2px 8px;border:1px solid var(--color-hairline);border-radius:var(--rounded-xs);background:var(--color-canvas-soft);color:var(--color-ink-mute);"
                                      x-text="item.barcode || item.barcode_universal || '—'"></span>
                            </td>

                            <!-- Stok Fisik -->
                            <td style="text-align:right;">
                                <span style="font-family:var(--font-mono);font-weight:700;font-size:14px;color:var(--color-ink);"
                                      x-text="item.stok_fisik_saat_ini + ' ' + item.satuan_dasar"></span>
                                <!-- Status inline on mobile -->
                                <div class="show-mobile" style="margin-top:4px;">
                                    <span x-show="item.stok_fisik_saat_ini > 10" class="badge badge-success">Tersedia</span>
                                    <span x-show="item.stok_fisik_saat_ini > 0 && item.stok_fisik_saat_ini <= 10" class="badge badge-warning">Menipis</span>
                                    <span x-show="item.stok_fisik_saat_ini <= 0" class="badge badge-muted">Kosong</span>
                                </div>
                            </td>

                            <!-- Status (Desktop) -->
                            <td class="hide-mobile" style="text-align:center;">
                                <span x-show="item.stok_fisik_saat_ini > 10" class="badge badge-success">Tersedia</span>
                                <span x-show="item.stok_fisik_saat_ini > 0 && item.stok_fisik_saat_ini <= 10" class="badge badge-warning">Menipis</span>
                                <span x-show="item.stok_fisik_saat_ini <= 0" class="badge badge-muted">Kosong</span>
                            </td>

                            <!-- Aksi -->
                            <td style="text-align:center;">
                                <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
                                    <?php if (Auth::can('inventory.opname')): ?>
                                    <button @click="openAdjust(item)" class="btn btn-secondary btn-sm" title="Opname Koreksi Stok Fisik">
                                        <i data-lucide="sliders-horizontal" style="width:13px;height:13px;"></i>
                                        <span>Opname</span>
                                    </button>
                                    <?php endif; ?>

                                    <?php if (Auth::can('inventory.waste')): ?>
                                    <button @click="openWaste(item)" class="btn btn-sm" style="background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;padding:5px 9px;font-size:11.5px;font-weight:700;" title="Catat Barang Rusak / Waste">
                                        <i data-lucide="trash-2" style="width:13px;height:13px;"></i>
                                        <span>Waste</span>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL OPNAME -->
    <template x-teleport="body">
    <div x-show="showAdjustModal" x-cloak class="modal-backdrop">
        <div @click.away="showAdjustModal = false" class="modal-box">
            <div class="modal-header">
                <div>
                    <div class="modal-title">Penyesuaian Stok (Opname)</div>
                    <div style="font-size:11px;font-family:var(--font-mono);color:var(--color-primary);margin-top:2px;"
                         x-text="selectedItem.kode_sku + ' — ' + selectedItem.nama_item"></div>
                </div>
                <button @click="showAdjustModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:15px;height:15px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/inventory/adjust') ?>" method="POST" data-action-text="Menyimpan penyesuaian stok..." style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="item_id" :value="selectedItem.id">

                <div>
                    <label class="form-label">Jenis Penyesuaian</label>
                    <select name="tipe_penyesuaian" class="form-select">
                        <option value="opname_lebih">Opname Lebih (Tambah Stok)</option>
                        <option value="opname_hilang">Opname Hilang / Selisih Fisik (Kurang Stok)</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Jumlah Pcs</label>
                    <input type="number" name="kuantitas" required min="1" placeholder="10"
                           class="form-input font-mono" style="font-weight:700;">
                </div>

                <div>
                    <label class="form-label">Catatan / Alasan</label>
                    <input type="text" name="alasan" required placeholder="Hasil hitung fisik gudang"
                           class="form-input">
                </div>

                <div style="display:flex;gap:8px;padding-top:4px;">
                    <button type="button" @click="showAdjustModal = false" class="btn btn-secondary" style="flex:1;justify-content:center;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">
                        <i data-lucide="save"></i>
                        Simpan Opname
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- MODAL WASTE / BARANG RUSAK -->
    <template x-teleport="body">
    <div x-show="showWasteModal" x-cloak class="modal-backdrop">
        <div @click.away="showWasteModal = false" class="modal-box">
            <div class="modal-header">
                <div>
                    <div class="modal-title" style="color:#b91c1c;">Catat Barang Rusak / Waste</div>
                    <div style="font-size:11px;font-family:var(--font-mono);color:#dc2626;margin-top:2px;"
                         x-text="selectedItem.kode_sku + ' — ' + selectedItem.nama_item"></div>
                </div>
                <button @click="showWasteModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:15px;height:15px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/inventory/waste') ?>" method="POST" data-action-text="Mencatat barang rusak / waste..." style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="item_id" :value="selectedItem.id">

                <div style="padding:10px 12px;background:#fef2f2;border:1px solid #fee2e2;border-radius:10px;font-size:12px;color:#991b1b;display:flex;align-items:center;justify-content:space-between;">
                    <span>Sisa Stok Fisik Saat Ini:</span>
                    <strong class="font-mono" style="font-size:13px;" x-text="(selectedItem.stok_fisik_saat_ini || 0) + ' Pcs'"></strong>
                </div>

                <div>
                    <label class="form-label">Kategori Kerusakan / Waste *</label>
                    <select name="kategori_waste" class="form-select font-semibold" required>
                        <option value="kemasan_rusak">Kemasan Rusak / Gagal Segel</option>
                        <option value="remuk_hancur">Produk Remuk / Hancur</option>
                        <option value="expired_kadaluarsa">Kadaluarsa / Expired</option>
                        <option value="sampel_promosi">Sampel Uji Rasa / Promosi</option>
                        <option value="lainnya">Lain-lain</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Jumlah Pcs Rusak / Dibuang *</label>
                    <input type="number" name="kuantitas" required min="1" :max="selectedItem.stok_fisik_saat_ini" placeholder="1"
                           class="form-input font-mono" style="font-weight:700;">
                </div>

                <div>
                    <label class="form-label">Keterangan / Kronologi *</label>
                    <input type="text" name="keterangan" required placeholder="Contoh: Plastik bocor saat proses packing di line 2"
                           class="form-input">
                </div>

                <div style="display:flex;gap:8px;padding-top:4px;">
                    <button type="button" @click="showWasteModal = false" class="btn btn-secondary" style="flex:1;justify-content:center;">Batal</button>
                    <button type="submit" class="btn btn-danger" style="flex:1;justify-content:center;background:#dc2626;">
                        <i data-lucide="trash-2"></i>
                        Potong Stok Waste
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

</div>

<script>
function inventoryApp() {
    return {
        items: <?= json_encode($items) ?>,
        searchQuery: '',
        showAdjustModal: false,
        showWasteModal: false,
        selectedItem: {},

        get filteredItems() {
            if (!this.searchQuery.trim()) return this.items;
            const q = this.searchQuery.toLowerCase();
            return this.items.filter(item => {
                return (item.nama_item && item.nama_item.toLowerCase().includes(q)) ||
                       (item.kode_sku && item.kode_sku.toLowerCase().includes(q)) ||
                       (item.barcode && item.barcode.toLowerCase().includes(q)) ||
                       (item.barcode_universal && item.barcode_universal.toLowerCase().includes(q)) ||
                       (item.nama_grup && item.nama_grup.toLowerCase().includes(q));
            });
        },

        openAdjust(item) {
            this.selectedItem = item;
            this.showAdjustModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openWaste(item) {
            this.selectedItem = item;
            this.showWasteModal = true;
            this.$nextTick(() => lucide.createIcons());
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<div x-data="inventoryApp()" class="space-y-4">

    <!-- HEADER & SEARCH -->
    <div class="card" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="boxes"></i>
            </div>
            <div>
                <div class="section-title">Katalog Master Produk (<span x-text="filteredItems.length"></span> SKU)</div>
                <div class="section-subtitle">Monitoring stok fisik gudang secara realtime.</div>
            </div>
        </div>
        <div class="form-input-icon" style="width:100%;max-width:280px;">
            <i data-lucide="search" class="icon-left"></i>
            <input type="text" x-model="searchQuery"
                   placeholder="Cari SKU, Nama atau Barcode..."
                   class="form-input">
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
                                <button @click="openAdjust(item)" class="btn btn-secondary btn-sm">
                                    Opname
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL OPNAME -->
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

            <form action="<?= Router::url('/inventory/adjust') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="item_id" :value="selectedItem.id">

                <div>
                    <label class="form-label">Jenis Penyesuaian</label>
                    <select name="tipe_penyesuaian" class="form-select">
                        <option value="opname_lebih">Opname Lebih (Tambah Stok)</option>
                        <option value="opname_hilang">Opname Hilang / Rusak (Kurang Stok)</option>
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

</div>

<script>
function inventoryApp() {
    return {
        items: <?= json_encode($items) ?>,
        searchQuery: '',
        showAdjustModal: false,
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
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

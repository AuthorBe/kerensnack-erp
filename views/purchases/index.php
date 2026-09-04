<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<div x-data="purchaseApp()" x-init="init()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-blue">
                <i data-lucide="receipt"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#3b82f6;"></span>
                    <span>Pengadaan Bahan Gudang</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Pembelian &amp; Faktur Vendor' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Penerimaan Bahan Mentah, Bumbu &amp; Kemasan dari Supplier' ?></p>
            </div>
        </div>
        <div class="page-header-actions">
            <button @click="openAddModal()" class="btn btn-primary" style="font-weight:700;">
                <i data-lucide="plus"></i>
                <span>Catat Pembelian Baru</span>
            </button>
        </div>
    </div>

    <!-- STATS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="file-check"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Faktur Pembelian</div>
                <div class="stat-card-value"><?= count($purchases) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Faktur masuk vendor</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                <i data-lucide="coins"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Belanja Bahan</div>
                <div class="stat-card-value" style="color:#3b82f6;">
                    <?= Format::rupiah(array_sum(array_column($purchases, 'total_biaya'))) ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Akumulasi pengadaan</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
                <i data-lucide="truck"></i>
            </div>
            <div>
                <div class="stat-card-label">Vendor Terlibat</div>
                <div class="stat-card-value" style="color:#f59e0b;"><?= count($suppliers) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Supplier aktif</div>
            </div>
        </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card" style="padding:0;overflow:hidden;">

        <!-- FILTER & ACTION BAR -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="form-input-icon flex-1 sm:max-w-xs">
                <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                <input type="text" x-model="searchQuery" placeholder="Cari nomor faktur / vendor..." class="form-input" style="height:38px;font-size:13px;">
            </div>

            <button @click="openAddModal()" class="btn btn-primary" style="height:38px;white-space:nowrap;">
                <i data-lucide="plus"></i>
                <span>Faktur Pembelian Masuk</span>
            </button>
        </div>

        <!-- TABLE LIST -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 850px;">
                <thead>
                    <tr>
                        <th style="width:160px; min-width:140px;" class="cell-nowrap">No. Faktur</th>
                        <th style="width:110px; min-width:95px;" class="cell-nowrap">Tanggal</th>
                        <th style="min-width:180px;">Vendor Pemasok</th>
                        <th class="cell-right cell-nowrap" style="width:140px; min-width:120px;">Total Biaya</th>
                        <th class="cell-center cell-nowrap" style="width:110px; min-width:100px;">Status Bayar</th>
                        <th class="cell-center cell-nowrap" style="width:120px; min-width:110px;">Penerimaan</th>
                        <th style="min-width:140px;">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="pb in filteredPurchases" :key="pb.id">
                        <tr>
                            <td class="cell-nowrap">
                                <span class="badge badge-mono" x-text="pb.nomor_faktur_pembelian"></span>
                            </td>
                            <td class="cell-nowrap" style="font-family:var(--font-mono);font-size:12px;" x-text="pb.tanggal_pembelian"></td>
                            <td>
                                <div style="font-weight:700;color:var(--color-ink);" x-text="pb.nama_pemasok || '-'"></div>
                                <div style="font-size:11px;color:var(--color-ink-mute);" x-text="pb.kode_pemasok || ''"></div>
                            </td>
                            <td class="cell-currency cell-right cell-nowrap" style="color:var(--color-primary-deep);font-weight:700;" x-text="formatRupiah(pb.total_biaya)"></td>
                            <td class="cell-center cell-nowrap">
                                <span class="badge" :class="pb.status_pembayaran === 'lunas' ? 'badge-primary' : 'badge-warning'" style="text-transform:capitalize;" x-text="pb.status_pembayaran ? pb.status_pembayaran.replace(/_/g, ' ') : ''"></span>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <span class="badge badge-info" style="text-transform:capitalize;" x-text="pb.status_penerimaan"></span>
                            </td>
                            <td style="color:var(--color-ink-mute);font-size:12px;" x-text="pb.catatan || '-'"></td>
                        </tr>
                    </template>

                    <template x-if="filteredPurchases.length === 0">
                        <tr>
                            <td colspan="7" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="search-x" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Belum ada riwayat faktur pembelian</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL INPUT FAKTUR PEMBELIAN -->
    <template x-teleport="body">
    <div x-show="showModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:680px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title">Input Faktur Pembelian Vendor</div>
                <button @click="showModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <div style="display:flex;flex-direction:column;gap:14px;">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Vendor Pemasok *</label>
                        <select x-model="form.pemasok_id" class="form-input">
                            <option value="">-- Pilih Pemasok --</option>
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_pemasok']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Nomor Faktur / Nota Pembelian *</label>
                        <div class="input-group-addon">
                            <span class="addon-prefix">PO-</span>
                            <input type="text" x-model="form.nomor_faktur_suffix" 
                                   @input="form.nomor_faktur_suffix = $event.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 16)"
                                   maxlength="16" class="form-input font-mono uppercase addon-input" placeholder="<?= date('Ymd') ?>-001">
                        </div>
                        <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:3px;">Prefix <code>PO-</code> otomatis. Maks. 16 huruf/angka.</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="form-label">Tanggal Pembelian</label>
                        <input type="date" x-model="form.tanggal_pembelian" class="form-input font-mono">
                    </div>
                    <div>
                        <label class="form-label">Status Pembayaran</label>
                        <select x-model="form.status_pembayaran" class="form-input">
                            <option value="lunas">Lunas (Tunai/Bank)</option>
                            <option value="belum_lunas">Hutang (Tempo)</option>
                        </select>
                    </div>
                    <div x-show="form.status_pembayaran === 'lunas'">
                        <label class="form-label">Akun Kas Sumber Dana</label>
                        <select x-model="form.akun_kas_id" class="form-input">
                            <?php foreach ($cashAccounts as $ca): ?>
                            <option value="<?= $ca['id'] ?>"><?= htmlspecialchars($ca['nama_akun']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- ITEMS LIST CONTAINER -->
                <div style="padding:14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                        <span style="font-size:12.5px;font-weight:700;color:var(--color-ink);">Daftar Barang Diterima</span>
                        <button @click="addItemRow()" type="button" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:11.5px;">
                            <i data-lucide="plus" style="width:13px;height:13px;"></i>
                            <span>Tambah Baris</span>
                        </button>
                    </div>

                    <!-- Column Header Labels -->
                    <div style="display:grid;grid-template-columns:2.5fr 1fr 1.5fr 36px;gap:8px;padding-bottom:6px;font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">
                        <div>Nama Barang / SKU</div>
                        <div>Kuantitas</div>
                        <div>Harga Satuan (Rp)</div>
                        <div></div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:8px;min-height:200px;overflow:visible;">
                        <template x-for="(row, idx) in form.items" :key="idx">
                            <div style="display:grid;grid-template-columns:2.5fr 1fr 1.5fr 36px;gap:8px;align-items:center;">
                                <div class="relative" @click.outside="row.dropdownOpen = false">
                                    <button type="button" @click="toggleItemDropdown(row)"
                                            class="form-input flex items-center justify-between w-full text-left"
                                            style="height:36px;font-size:12px;font-weight:600;border-radius:8px;cursor:pointer;background:var(--color-canvas);padding:0 8px;">
                                        <span class="truncate" :style="!row.item_id ? 'color:var(--color-ink-mute);font-weight:500;' : 'color:var(--color-ink);'"
                                              x-text="getSelectedItemName(row.item_id)"></span>
                                        <i data-lucide="chevron-down" style="width:13px;height:13px;flex-shrink:0;transition:transform 0.2s;" :style="row.dropdownOpen ? 'transform:rotate(180deg)' : ''"></i>
                                    </button>

                                    <div x-show="row.dropdownOpen" x-cloak
                                         class="dropdown-menu-searchable"
                                         style="position:absolute;top:calc(100% + 4px);left:0;min-width:280px;max-width:360px;z-index:1050;border-radius:10px;overflow:hidden;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:0 12px 28px -4px rgba(0,0,0,0.15);">
                                        <div style="padding:6px 8px;border-bottom:1px solid var(--color-hairline);background:var(--color-canvas-soft);">
                                            <div style="position:relative;display:flex;align-items:center;">
                                                <i data-lucide="search" style="position:absolute;left:8px;width:13px;height:13px;color:var(--color-ink-mute);pointer-events:none;"></i>
                                                <input type="text" x-model="row.search"
                                                       @keydown.escape="row.dropdownOpen = false"
                                                       placeholder="Cari nama barang / SKU / jenis..."
                                                       class="form-input"
                                                       style="height:30px;padding-left:26px;font-size:11.5px;border-radius:6px;width:100%;background:var(--color-canvas);">
                                            </div>
                                        </div>
                                        <div style="max-height:180px;overflow-y:auto;" class="custom-scrollbar">
                                            <template x-for="it in getFilteredItems(row)" :key="it.id">
                                                <div @click="selectItemRow(row, idx, it)"
                                                     class="searchable-option"
                                                     :class="{ 'is-selected': String(it.id) === String(row.item_id) }"
                                                     style="padding:8px 10px;font-size:11.5px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:6px;border-bottom:1px solid var(--color-hairline-soft);">
                                                    <div style="min-width:0;flex:1;">
                                                        <div style="font-weight:700;color:var(--color-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="it.nama_item"></div>
                                                        <div style="font-size:10.5px;color:var(--color-ink-mute);" x-text="(it.tipe_item === 'bahan_mentah' ? 'Mentah Bal/Kg' : 'Kemasan') + ' • ' + it.satuan_dasar"></div>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="getFilteredItems(row).length === 0">
                                                <div style="padding:12px;text-align:center;font-size:11.5px;color:var(--color-ink-mute);">
                                                    Barang tidak ditemukan
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <input type="number" step="any" x-model.number="row.qty" placeholder="Qty" class="form-input font-mono" style="font-size:12px;height:36px;" @input="recalcRow(idx)">
                                <input type="text" x-model="row.harga_satuan" placeholder="Harga" class="form-input font-mono input-rupiah" style="font-size:12px;height:36px;" @input="recalcRow(idx)">
                                <button @click="removeItemRow(idx)" type="button" class="btn btn-ghost btn-sm" style="color:var(--color-danger);padding:4px;height:36px;width:36px;" title="Hapus Baris">
                                    <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:10px;border-top:1px solid var(--color-hairline);">
                        <span style="font-size:12px;font-weight:600;color:var(--color-ink-secondary);">Total Faktur Masuk:</span>
                        <strong class="cell-currency" style="font-size:15px;color:var(--color-primary-deep);" x-text="formatRupiah(formTotal)"></strong>
                    </div>
                </div>

                <div>
                    <label class="form-label">Catatan</label>
                    <input type="text" x-model="form.catatan" class="form-input" placeholder="Keterangan pengiriman...">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showModal = false" class="btn btn-secondary">Batal</button>
                    <button type="button" @click="submitPurchase()" :disabled="isSubmitting" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span x-show="!isSubmitting">Simpan Faktur & Tambah Stok</span>
                        <span x-show="isSubmitting">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>

</div>

<script>
function purchaseApp() {
    return {
        purchases: <?= json_encode($purchases) ?>,
        availableItems: <?= json_encode($items) ?>,
        searchQuery: '',
        showModal: false,
        isSubmitting: false,
        form: {
            pemasok_id: '<?= $suppliers[0]['id'] ?? '' ?>',
            nomor_faktur_suffix: '<?= date('Ymd') ?>-001',
            tanggal_pembelian: '<?= date('Y-m-d') ?>',
            status_pembayaran: 'lunas',
            akun_kas_id: '<?= $cashAccounts[0]['id'] ?? '' ?>',
            catatan: '',
            items: []
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        get filteredPurchases() {
            return this.purchases.filter(p => {
                const q = this.searchQuery.toLowerCase();
                return !q ||
                    p.nomor_faktur_pembelian.toLowerCase().includes(q) ||
                    (p.nama_pemasok && p.nama_pemasok.toLowerCase().includes(q));
            });
        },

        get formTotal() {
            return this.form.items.reduce((sum, it) => {
                const rawHarga = typeof it.harga_satuan === 'string' ? (window.unformatRupiah ? window.unformatRupiah(it.harga_satuan) : Number(it.harga_satuan.replace(/\./g, ''))) : Number(it.harga_satuan || 0);
                return sum + (Number(it.qty || 0) * rawHarga);
            }, 0);
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        getFilteredItems(row) {
            const q = (row.search || '').toLowerCase().trim();
            if (!q) return this.availableItems;
            return this.availableItems.filter(it => {
                const name = (it.nama_item || '').toLowerCase();
                const sku = (it.kode_sku || '').toLowerCase();
                const tipe = (it.tipe_item || '').toLowerCase();
                return name.includes(q) || sku.includes(q) || tipe.includes(q);
            });
        },

        getSelectedItemName(itemId) {
            if (!itemId) return '-- Pilih Bahan Baku / Kemasan --';
            const it = this.availableItems.find(x => x.id === itemId);
            if (!it) return '-- Pilih Bahan Baku / Kemasan --';
            return it.nama_item + ' (' + (it.tipe_item === 'bahan_mentah' ? 'Mentah Bal/Kg' : 'Kemasan') + ' - ' + it.satuan_dasar + ')';
        },

        selectItemRow(row, idx, item) {
            row.item_id = item.id;
            row.dropdownOpen = false;
            row.search = '';
            this.onItemChange(idx);
        },

        toggleItemDropdown(row) {
            const wasOpen = row.dropdownOpen;
            this.form.items.forEach(r => r.dropdownOpen = false);
            row.dropdownOpen = !wasOpen;
            if (row.dropdownOpen) {
                row.search = '';
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        openAddModal() {
            this.form = {
                pemasok_id: '<?= $suppliers[0]['id'] ?? '' ?>',
                nomor_faktur: '',
                tanggal_pembelian: '<?= date('Y-m-d') ?>',
                status_pembayaran: 'lunas',
                akun_kas_id: '<?= $cashAccounts[0]['id'] ?? '' ?>',
                catatan: '',
                items: [
                    { item_id: '', qty: 10, harga_satuan: '10.000', subtotal: 100000, dropdownOpen: false, search: '' }
                ]
            };
            this.showModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        addItemRow() {
            this.form.items.push({ item_id: '', qty: 1, harga_satuan: '0', subtotal: 0, dropdownOpen: false, search: '' });
            this.$nextTick(() => lucide.createIcons());
        },

        removeItemRow(idx) {
            this.form.items.splice(idx, 1);
        },

        onItemChange(idx) {
            const row = this.form.items[idx];
            const found = this.availableItems.find(i => i.id === row.item_id);
            if (found) {
                row.harga_satuan = window.formatRupiahNumber ? window.formatRupiahNumber(found.harga_pokok_pembelian || 0) : String(found.harga_pokok_pembelian || 0);
                this.recalcRow(idx);
            }
        },

        recalcRow(idx) {
            const row = this.form.items[idx];
            const rawHarga = typeof row.harga_satuan === 'string' ? (window.unformatRupiah ? window.unformatRupiah(row.harga_satuan) : Number(row.harga_satuan.replace(/\./g, ''))) : Number(row.harga_satuan || 0);
            row.subtotal = Number(row.qty || 0) * rawHarga;
        },

        async submitPurchase() {
            if (!this.form.pemasok_id) {
                toast.warning('Mohon pilih vendor pemasok!');
                return;
            }

            const validItems = this.form.items.filter(it => it.item_id && Number(it.qty) > 0);
            if (validItems.length === 0) {
                toast.warning('Mohon tambahkan minimal 1 item barang yang diterima dengan kuantitas > 0!');
                return;
            }

            this.isSubmitting = true;
            if (window.AppAction) {
                window.AppAction.show('Menyimpan faktur pembelian...');
            }
            try {
                // Unmask currency fields for API
                const payload = JSON.parse(JSON.stringify(this.form));
                payload.nomor_faktur = 'PO-' + (this.form.nomor_faktur_suffix || '').trim();
                payload.items = validItems.map(it => ({
                    item_id: it.item_id,
                    qty: Number(it.qty || 0),
                    harga_satuan: typeof it.harga_satuan === 'string' ? (window.unformatRupiah ? window.unformatRupiah(it.harga_satuan) : Number(it.harga_satuan.replace(/\./g, ''))) : Number(it.harga_satuan || 0),
                    subtotal: Number(it.subtotal || 0)
                }));

                const res = await fetch('<?= Router::url('/purchases/store') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.success) {
                    if (window.AppAction) {
                        await window.AppAction.success('Faktur Berhasil Disimpan! ✨', 650);
                    }
                    toast.success('Faktur pembelian berhasil disimpan dan stok otomatis bertambah!');
                    try { sessionStorage.setItem('app_action_triggered', 'true'); } catch (e) {}
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    if (window.AppAction) {
                        await window.AppAction.error(`Gagal: ${json.message || 'Gagal menyimpan faktur'}`, 1200);
                    }
                    toast.error('Gagal: ' + json.message);
                }
            } catch (err) {
                if (window.AppAction) {
                    await window.AppAction.error('Kesalahan Jaringan / Koneksi!', 1200);
                }
                toast.error('Terjadi kesalahan koneksi saat menyimpan faktur pembelian.');
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

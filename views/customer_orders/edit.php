<?php
use App\Core\Router;
use App\Core\Auth;
use App\Helpers\Format;
ob_start();
?>

<div class="space-y-5" x-data="editSalesOrderApp()">

    <!-- ========================================================================= -->
    <!-- 1. TOP ACTION BAR                                                         -->
    <!-- ========================================================================= -->
    <div class="page-header bg-card p-4 rounded-xl border border-hairline shadow-sm">
        <div class="page-header-body">
            <a href="<?= Router::url('/customer-orders') ?>" class="page-back-btn" title="Kembali ke Daftar Penjualan">
                <i data-lucide="arrow-left"></i>
            </a>
            <div class="page-header-icon is-indigo">
                <i data-lucide="edit-3"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot"></span>
                    <span>Edit Faktur Pesanan</span>
                </div>
                <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Edit Pesanan Pelanggan') ?></h1>
                <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle ?? 'Perbarui item produk, kuantiti, dan skema harga') ?></p>
            </div>
        </div>
        <div class="page-header-actions">
            <button type="button" @click="submitOrder()" :disabled="isSubmitting || items.length === 0" class="btn btn-primary flex-1 sm:flex-initial" style="font-weight:700;">
                <i data-lucide="save"></i>
                <span x-text="isSubmitting ? 'Menyimpan Perubahan...' : 'Simpan Perubahan'"></span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MAIN FORM                                                                 -->
    <!-- ========================================================================= -->
    <form id="salesOrderForm" data-add-row-btn="#btnAddRow" action="<?= Router::url('/customer-orders/update') ?>" method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($order['id']) ?>">
        <input type="hidden" name="items_json" :value="JSON.stringify(items)">

        <div class="space-y-5">

            <!-- ===================================================================== -->
            <!-- 2. INFORMASI TOKO & TRANSAKSI (2-PANEL BALANCED MULTI-COLOR GRID)     -->
            <!-- ===================================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                <!-- PANEL KIRI: DATA TOKO & LOGISTIK (Read-Only Customer) -->
                <div class="card p-4 space-y-3.5">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:1px solid var(--color-hairline);padding-bottom:10px;">
                        <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                            <div style="width:34px;height:34px;border-radius:10px;background:rgba(99,102,241,0.12);color:#6366f1;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="store" style="width:18px;height:18px;"></i>
                            </div>
                            <div>
                                <div style="font-size:13.5px;font-weight:800;color:var(--color-ink);line-height:1.2;">Mitra Toko (Terkunci)</div>
                                <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">Data toko mitra terdaftar pada faktur</div>
                            </div>
                        </div>
                        <span class="badge flex items-center gap-1.5" style="background:rgba(99,102,241,0.1);color:#4f46e5;border:1px solid rgba(99,102,241,0.25);font-weight:800;font-size:11.5px;padding:4px 10px;border-radius:999px;white-space:nowrap;">
                            <i data-lucide="tag" style="width:13px;height:13px;"></i> Level <?= htmlspecialchars($order['level_harga']) ?>
                        </span>
                    </div>

                    <div>
                        <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Toko Pelanggan</label>
                        <input type="text" readonly value="<?= htmlspecialchars($order['nama_toko']) ?> (<?= htmlspecialchars($order['kode_pelanggan']) ?> - Lvl <?= htmlspecialchars($order['level_harga']) ?>)"
                               class="form-input font-bold" style="height:42px;border-radius:10px;font-size:13.5px;background:var(--color-canvas-soft);cursor:not-allowed;color:var(--color-ink);">
                    </div>

                    <!-- Customer Detail Strip -->
                    <div style="padding:10px 12px;border-radius:12px;font-size:12px;display:flex;align-items:center;gap:8px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);color:var(--color-ink);">
                        <div style="width:26px;height:26px;border-radius:6px;background:rgba(99,102,241,0.1);color:#6366f1;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="lock" style="width:13px;height:13px;"></i>
                        </div>
                        <span>
                            Toko mitra dikunci pada mode edit. Skema: <strong>Level <?= htmlspecialchars($order['level_harga']) ?> (<?= htmlspecialchars($order['nama_grup_harga'] ?? 'Standar') ?>)</strong>
                        </span>
                    </div>

                    <div>
                        <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Catatan / Keterangan Nota</label>
                        <textarea name="catatan" rows="2" class="form-input" style="border-radius:10px;font-size:12.5px;" placeholder="Catatan khusus pesanan ini..."><?= htmlspecialchars($order['catatan'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- PANEL KANAN: PARAMETER FAKTUR & PEMBAYARAN -->
                <div class="card p-4 space-y-3.5">
                    <div style="display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--color-hairline);padding-bottom:10px;">
                        <div style="width:34px;height:34px;border-radius:10px;background:rgba(16,185,129,0.12);color:#059669;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="receipt" style="width:18px;height:18px;"></i>
                        </div>
                        <div>
                            <div style="font-size:13.5px;font-weight:800;color:var(--color-ink);line-height:1.2;">Parameter Faktur</div>
                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">Nomor nota &amp; parameter tanggal</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Nomor Faktur / Nota</label>
                            <input type="text" readonly value="<?= htmlspecialchars($order['nomor_nota']) ?>" 
                                   class="form-input font-bold font-mono" style="height:42px;border-radius:10px;background:var(--color-canvas-soft);cursor:not-allowed;color:var(--color-ink);">
                        </div>

                        <div>
                            <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Tanggal Pesanan *</label>
                            <input type="date" name="tanggal_pesanan" x-model="header.tanggal_pesanan" required class="form-input font-mono" style="height:42px;border-radius:10px;">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Skema Pembayaran *</label>
                            <select name="tipe_pembayaran" x-model="header.tipe_pembayaran" @change="onTipePembayaranChange()" class="form-input" style="height:42px;border-radius:10px;font-weight:600;">
                                <option value="cash">Tunai (Lunas 100%)</option>
                                <option value="sebagian">Kredit / Bayar Sebagian (DP)</option>
                                <option value="tempo_7_hari">Tempo 7 Hari</option>
                                <option value="tempo_14_hari">Tempo 14 Hari</option>
                                <option value="tempo_30_hari">Tempo 30 Hari</option>
                                <option value="konsinyasi">Titip Jual (Konsinyasi)</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Sales / Driver Bertugas</label>
                            <select name="sales_driver_id" x-model="header.sales_driver_id" class="form-input" style="height:42px;border-radius:10px;">
                                <option value="">-- Otomatis / Belum Ditugaskan --</option>
                                <?php foreach ($drivers as $d): ?>
                                <option value="<?= $d['id'] ?>">
                                    <?= htmlspecialchars($d['nama_karyawan']) ?> (<?= htmlspecialchars($d['nik'] ?? 'Staf') ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Jatuh Tempo</label>
                            <input type="date" name="tanggal_jatuh_tempo" x-model="header.tanggal_jatuh_tempo" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Diskon Faktur (Rp)</label>
                            <input type="number" name="diskon_faktur" x-model.number="header.diskon_faktur" min="0" step="500" class="form-input font-bold" placeholder="0">
                        </div>
                    </div>

                </div>

            </div>

            <!-- ===================================================================== -->
            <!-- 3. TABEL ITEM PESANAN                                                 -->
            <!-- ===================================================================== -->
            <div class="card p-4 space-y-3">
                <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--color-hairline);padding-bottom:10px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;border-radius:6px;background:rgba(245,158,11,0.12);color:#fbbf24;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="package" style="width:15px;height:15px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:13px;font-weight:800;text-transform:uppercase;color:var(--color-ink);margin:0;">Daftar Produk Pesanan</h3>
                            <p style="font-size:11px;color:var(--color-ink-muted);margin:0;">Sesuaikan kuantiti atau ganti varian snack</p>
                        </div>
                    </div>
                    <button type="button" id="btnAddRow" @click="addItemRow()" class="btn btn-secondary btn-sm" style="font-weight:700;">
                        <i data-lucide="plus"></i>
                        <span>Tambah Item</span>
                    </button>
                </div>

                <!-- Tabel Item Desktop -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left" style="font-size:12px;border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:1.5px solid var(--color-hairline);color:var(--color-ink-muted);font-weight:700;font-size:11px;text-transform:uppercase;">
                                <th style="padding:8px 6px;width:35px;">No</th>
                                <th style="padding:8px 6px;min-width:240px;">Produk Snack</th>
                                <th style="padding:8px 6px;width:95px;text-align:center;">Qty (Pcs)</th>
                                <th style="padding:8px 6px;width:120px;text-align:right;">Harga Deal</th>
                                <th style="padding:8px 6px;width:105px;text-align:right;">Diskon (Rp)</th>
                                <th style="padding:8px 6px;width:125px;text-align:right;">Subtotal</th>
                                <th style="padding:8px 6px;width:40px;text-align:center;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in items" :key="row.uid">
                                <tr style="border-bottom:1px solid var(--color-hairline);">
                                    <td style="padding:8px 6px;color:var(--color-ink-muted);font-weight:600;" x-text="index + 1"></td>
                                    <td style="padding:8px 6px;">
                                        <select x-model="row.item_id" @change="onProductSelect(row)" class="form-input" style="height:34px;font-size:12px;font-weight:600;">
                                            <option value="">-- Pilih Produk Snack --</option>
                                            <template x-for="p in availableProducts" :key="p.id">
                                                <option :value="p.id" x-text="p.nama_item + (p.varian_rasa ? ' - ' + p.varian_rasa : '')"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td style="padding:8px 6px;">
                                        <input type="number" x-model.number="row.qty" @input="calcRow(row)" min="1" class="form-input text-center font-bold" style="height:34px;padding:4px;">
                                    </td>
                                    <td style="padding:8px 6px;">
                                        <input type="number" x-model.number="row.harga" @input="calcRow(row)" min="0" step="500" class="form-input text-right font-mono" style="height:34px;padding:4px 8px;">
                                    </td>
                                    <td style="padding:8px 6px;">
                                        <input type="number" x-model.number="row.diskon" @input="calcRow(row)" min="0" step="500" class="form-input text-right font-mono" style="height:34px;padding:4px 8px;">
                                    </td>
                                    <td style="padding:8px 6px;text-align:right;font-weight:700;color:var(--color-ink);" x-text="formatRupiah(row.subtotal)"></td>
                                    <td style="padding:8px 6px;text-align:center;">
                                        <button type="button" @click="removeItemRow(index)" class="btn-action-round" title="Hapus Baris" style="color:#ef4444;width:28px;height:28px;padding:0;">
                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Ringkasan Total Faktur -->
                <div style="border-top:1.5px solid var(--color-hairline);padding-top:14px;margin-top:10px;display:flex;justify-content:flex-end;">
                    <div style="width:100%;max-width:320px;" class="space-y-1.5">
                        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--color-ink-muted);">
                            <span>Total Bruto:</span>
                            <span style="font-weight:700;color:var(--color-ink);" x-text="formatRupiah(calcBruto())"></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--color-ink-muted);">
                            <span>Total Diskon Item:</span>
                            <span style="font-weight:700;color:#ef4444;" x-text="'- ' + formatRupiah(calcDiskonItem())"></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--color-ink-muted);">
                            <span>Diskon Faktur Tambahan:</span>
                            <span style="font-weight:700;color:#ef4444;" x-text="'- ' + formatRupiah(header.diskon_faktur || 0)"></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:800;color:#2563eb;border-top:1px dashed var(--color-hairline);padding-top:6px;">
                            <span>Total Netto:</span>
                            <span x-text="formatRupiah(calcNetto())"></span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </form>

</div>

<script>
function editSalesOrderApp() {
    return {
        isSubmitting: false,
        products: <?= json_encode($products ?? [], JSON_UNESCAPED_UNICODE) ?>,
        priceMatrix: <?= json_encode($priceMatrix ?? [], JSON_UNESCAPED_UNICODE) ?>,
        order: <?= json_encode($order ?? [], JSON_UNESCAPED_UNICODE) ?>,
        header: {
            tanggal_pesanan: '<?= htmlspecialchars($order['tanggal_pesanan'] ?? date('Y-m-d')) ?>',
            tipe_pembayaran: '<?= htmlspecialchars($order['tipe_pembayaran'] ?? 'cash') ?>',
            tanggal_jatuh_tempo: '<?= htmlspecialchars($order['tanggal_jatuh_tempo'] ?? '') ?>',
            sales_driver_id: '<?= htmlspecialchars($order['sales_driver_id'] ?? '') ?>',
            diskon_faktur: <?= (float)($order['total_diskon'] ?? 0) ?>
        },
        items: <?= json_encode(array_map(function($it) {
            return [
                'uid' => uniqid('row_'),
                'item_id' => $it['item_id'],
                'qty' => (int)$it['qty'],
                'harga' => (float)$it['harga'],
                'diskon' => (float)$it['diskon'],
                'subtotal' => (float)$it['subtotal'],
                'is_bonus' => (bool)$it['is_bonus']
            ];
        }, $existingItems ?? []), JSON_UNESCAPED_UNICODE) ?>,

        get availableProducts() {
            return this.products;
        },

        init() {
            if (this.items.length === 0) {
                this.addItemRow();
            }
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        onProductSelect(row) {
            if (!row.item_id) {
                row.harga = 0;
                row.subtotal = 0;
                return;
            }
            const product = this.products.find(p => p.id === row.item_id);
            if (product) {
                const level = Number(this.order.level_harga) || 1;
                const groupPrices = this.priceMatrix[product.grup_id];
                if (groupPrices && groupPrices[level]) {
                    row.harga = Number(groupPrices[level].pcs || 0);
                } else {
                    row.harga = 0;
                }
                this.calcRow(row);
            }
        },

        onTipePembayaranChange() {
            const tgl = new Date(this.header.tanggal_pesanan || new Date());
            if (this.header.tipe_pembayaran === 'tempo_7_hari') {
                tgl.setDate(tgl.getDate() + 7);
                this.header.tanggal_jatuh_tempo = tgl.toISOString().split('T')[0];
            } else if (this.header.tipe_pembayaran === 'tempo_14_hari' || this.header.tipe_pembayaran === 'sebagian') {
                tgl.setDate(tgl.getDate() + 14);
                this.header.tanggal_jatuh_tempo = tgl.toISOString().split('T')[0];
            } else if (this.header.tipe_pembayaran === 'tempo_30_hari' || this.header.tipe_pembayaran === 'konsinyasi') {
                tgl.setDate(tgl.getDate() + 30);
                this.header.tanggal_jatuh_tempo = tgl.toISOString().split('T')[0];
            }
        },

        addItemRow() {
            this.items.push({
                uid: 'row_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
                item_id: '',
                qty: 1,
                harga: 0,
                diskon: 0,
                subtotal: 0,
                is_bonus: false
            });
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        removeItemRow(idx) {
            this.items.splice(idx, 1);
        },

        calcRow(row) {
            const qty = Number(row.qty || 1);
            const harga = Number(row.harga || 0);
            const diskon = Number(row.diskon || 0);
            row.subtotal = Math.max(0, (qty * harga) - diskon);
        },

        calcBruto() {
            return this.items.reduce((sum, r) => sum + (r.item_id ? (Number(r.qty || 0) * Number(r.harga || 0)) : 0), 0);
        },

        calcDiskonItem() {
            return this.items.reduce((sum, r) => sum + (r.item_id ? Number(r.diskon || 0) : 0), 0);
        },

        calcNetto() {
            const subtotalItems = this.items.reduce((sum, r) => sum + (r.item_id ? Number(r.subtotal || 0) : 0), 0);
            const diskonFaktur = Number(this.header.diskon_faktur || 0);
            return Math.max(0, subtotalItems - diskonFaktur);
        },

        submitOrder() {
            const validItems = this.items.filter(r => r.item_id && Number(r.qty) > 0);
            if (validItems.length === 0) {
                alert('Mohon masukkan minimal 1 produk snack dengan Qty valid.');
                return;
            }

            this.isSubmitting = true;
            this.$nextTick(() => {
                document.getElementById('salesOrderForm').submit();
            });
        },

        formatRupiah(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

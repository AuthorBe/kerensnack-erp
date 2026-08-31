<?php
use App\Core\Router;
use App\Helpers\Format;
ob_start();
?>

<div class="space-y-5" x-data="createSalesOrderApp()">

    <!-- ========================================================================= -->
    <!-- 1. TOP ACTION BAR                                                         -->
    <!-- ========================================================================= -->
    <div class="page-header bg-card p-4 rounded-xl border border-hairline shadow-sm">
        <div class="page-header-body">
            <a href="<?= Router::url('/sales-orders') ?>" class="page-back-btn" title="Kembali ke Daftar Penjualan">
                <i data-lucide="arrow-left"></i>
            </a>
            <div class="page-header-icon is-emerald">
                <i data-lucide="file-plus-2"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot"></span>
                    <span>Transaksi Baru B2B</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Input Faktur Penjualan Toko' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Penerbitan faktur pemesanan mitra toko, grosir & supermarket' ?></p>
            </div>
        </div>
        <div class="page-header-actions">
            <button type="button" @click="submitOrder(false)" :disabled="isSubmitting || items.length === 0" class="btn btn-primary flex-1 sm:flex-initial" style="font-weight:700;">
                <i data-lucide="save"></i>
                <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Faktur'"></span>
            </button>
            <button type="button" @click="submitOrder(true)" :disabled="isSubmitting || items.length === 0" class="btn btn-secondary flex-1 sm:flex-initial" style="font-weight:700;">
                <i data-lucide="printer"></i>
                <span>Simpan & Cetak</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MAIN FORM                                                                 -->
    <!-- ========================================================================= -->
    <form id="salesOrderForm" action="<?= Router::url('/sales-orders/store') ?>" method="POST">
        <input type="hidden" name="items_json" :value="JSON.stringify(items)">
        <input type="hidden" name="print_direct" :value="printDirect ? '1' : '0'">

        <div class="space-y-5">

            <!-- ===================================================================== -->
            <!-- 2. INFORMASI TOKO & TRANSAKSI (2-PANEL BALANCED MULTI-COLOR GRID)     -->
            <!-- ===================================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                <!-- PANEL KIRI: DATA TOKO & LOGISTIK (Aksen Indigo / Slate) -->
                <div class="card p-4 space-y-3">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:1px solid var(--color-hairline);padding-bottom:8px;">
                        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                            <div style="width:28px;height:28px;border-radius:6px;background:rgba(99,102,241,0.12);color:#818cf8;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="store" style="width:15px;height:15px;"></i>
                            </div>
                            <span style="font-size:12.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink);white-space:nowrap;">Mitra Toko &amp; Logistik</span>
                        </div>
                        <template x-if="selectedCustomer">
                            <span class="badge" style="background:rgba(99,102,241,0.1);color:#818cf8;border:1px solid rgba(99,102,241,0.25);font-weight:700;font-size:11px;padding:3px 8px;white-space:nowrap;">
                                🏷️ Level <span x-text="selectedCustomer.level_harga"></span>
                            </span>
                        </template>
                    </div>

                    <div>
                        <label class="form-label">Toko Pelanggan *</label>
                        <select name="pelanggan_id" x-model="header.pelanggan_id" @change="onCustomerChange()" required class="form-input font-bold" style="height:38px;">
                            <option value="">-- Pilih Toko Pelanggan --</option>
                            <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= htmlspecialchars($c['nama_toko']) ?> (<?= htmlspecialchars($c['kode_pelanggan']) ?> - Lvl <?= $c['level_harga'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Customer Detail Strip (Jika Toko Terpilih) -->
                    <template x-if="selectedCustomer">
                        <div style="padding:6px 10px;border-radius:var(--rounded-xs);font-size:11.5px;display:flex;align-items:center;gap:6px;background:rgba(99,102,241,0.06);border:1px solid rgba(99,102,241,0.15);color:var(--color-ink-secondary);">
                            <i data-lucide="tag" style="width:13px;height:13px;color:#818cf8;flex-shrink:0;"></i>
                            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                Skema Harga: <strong>Level <span x-text="selectedCustomer.level_harga"></span> (<span x-text="selectedCustomer.nama_grup_harga || 'Standar'"></span>)</strong>
                            </span>
                        </div>
                    </template>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Sales / Driver Rute <span style="color:#ef4444;">*</span></label>
                            <select name="sales_driver_id" x-model="header.sales_driver_id" required class="form-input font-semibold" style="height:38px;">
                                <option value="">-- Pilih Driver Rute --</option>
                                <?php foreach ($drivers as $d): ?>
                                <option value="<?= $d['id'] ?>">🚚 <?= htmlspecialchars($d['nama_karyawan']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Catatan / Keterangan Nota</label>
                            <input type="text" name="catatan" x-model="header.catatan" class="form-input" style="height:38px;" placeholder="Contoh: Titip faktur ke kasir">
                        </div>
                    </div>

                    <!-- Whitelist Status Banner (Aksen Cyan / Sky) -->
                    <template x-if="selectedCustomer">
                        <div style="padding:7px 10px;border-radius:var(--rounded-md);font-size:11.5px;display:flex;align-items:center;justify-content:space-between;background:rgba(56,189,248,0.06);border:1px solid rgba(56,189,248,0.18);color:var(--color-ink-secondary);">
                            <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                                <i data-lucide="shield-check" style="width:14px;height:14px;color:#38bdf8;flex-shrink:0;" x-show="hasWhitelist"></i>
                                <i data-lucide="globe" style="width:14px;height:14px;color:#38bdf8;flex-shrink:0;" x-show="!hasWhitelist"></i>
                                <span x-text="hasWhitelist ? '🔒 Khusus ' + whitelistCount + ' Produk Terdaftar Toko' : '🌐 Semua 137 Produk Tersedia'" style="font-weight:700;color:var(--color-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></span>
                            </div>
                            <template x-if="hasWhitelist">
                                <button type="button" @click="showAllProducts = !showAllProducts" class="btn btn-ghost btn-sm" style="font-size:11px;padding:2px 6px;font-weight:700;color:#38bdf8;" x-text="showAllProducts ? '← Saring Whitelist' : '👁️ Buka 137 SKU'"></button>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- PANEL KANAN: DATA FAKTUR & PEMBAYARAN (Aksen Amber / Gold) -->
                <div class="card p-4 space-y-3">
                    <div style="display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--color-hairline);padding-bottom:8px;">
                        <div style="width:28px;height:28px;border-radius:6px;background:rgba(245,158,11,0.12);color:#fbbf24;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="receipt" style="width:15px;height:15px;"></i>
                        </div>
                        <span style="font-size:12.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink);">Faktur &amp; Skema Pembayaran</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label flex items-center justify-between">
                                <span>No. Faktur (Auto)</span>
                                <span style="font-size:10px;color:var(--color-ink-mute);">🔒 Terkunci</span>
                            </label>
                            <input type="text" name="nomor_nota" :value="header.nomor_nota" readonly class="form-input font-mono font-bold" style="height:38px;background:var(--color-canvas-soft);cursor:not-allowed;color:var(--color-ink);letter-spacing:0.02em;">
                        </div>
                        <div>
                            <label class="form-label">Tanggal Transaksi *</label>
                            <input type="date" name="tanggal_pesanan" x-model="header.tanggal_pesanan" required class="form-input font-mono" style="height:38px;">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Skema Pembayaran *</label>
                            <select name="tipe_pembayaran" x-model="header.tipe_pembayaran" @change="onTipePembayaranChange()" class="form-input font-semibold" style="height:38px;">
                                <option value="cash">💵 Tunai (Lunas 100%)</option>
                                <option value="sebagian">💳 Kredit / Bayar Sebagian (DP)</option>
                                <option value="tempo_7_hari">⏱️ Tempo 7 Hari</option>
                                <option value="tempo_14_hari">⏱️ Tempo 14 Hari</option>
                                <option value="tempo_30_hari">⏱️ Tempo 30 Hari</option>
                                <option value="konsinyasi">🏪 Titip Jual (Konsinyasi)</option>
                            </select>
                        </div>

                        <!-- Conditional: Akun Kas Penerima jika Tunai atau Bayar Sebagian (DP) -->
                        <template x-if="header.tipe_pembayaran === 'cash' || header.tipe_pembayaran === 'sebagian'">
                            <div>
                                <label class="form-label" style="color:#10b981;">Masuk ke Akun Kas *</label>
                                <select name="akun_kas_id" x-model="header.akun_kas_id" required class="form-input" style="height:38px;">
                                    <?php foreach ($cashAccounts as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nama_akun']) ?> (Rp <?= number_format((float)$a['saldo_saat_ini'], 0, ',', '.') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </template>

                        <!-- Conditional: Tanggal Jatuh Tempo jika Tempo murni -->
                        <template x-if="header.tipe_pembayaran !== 'cash' && header.tipe_pembayaran !== 'sebagian'">
                            <div>
                                <label class="form-label" style="color:#ef4444;">Tanggal Jatuh Tempo *</label>
                                <input type="date" name="tanggal_jatuh_tempo" x-model="header.tanggal_jatuh_tempo" required class="form-input font-mono" style="height:38px;border-color:#ef4444;color:#ef4444;font-weight:700;">
                            </div>
                        </template>
                    </div>

                    <!-- Row Tambahan Jika Skema Pembayaran Sebagian / DP -->
                    <template x-if="header.tipe_pembayaran === 'sebagian'">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2" style="border-top:1px dashed var(--color-hairline);">
                            <div>
                                <label class="form-label" style="color:#10b981;font-weight:700;">Nominal Dibayar Saat Ini (DP) *</label>
                                <input type="number" min="0" step="any" name="nominal_dibayar" x-model.number="header.nominal_dibayar" class="form-input font-mono font-bold" style="height:38px;border-color:#10b981;" placeholder="0">
                            </div>
                            <div>
                                <label class="form-label" style="color:#ef4444;font-weight:700;">Jatuh Tempo Sisa Tagihan *</label>
                                <input type="date" name="tanggal_jatuh_tempo" x-model="header.tanggal_jatuh_tempo" required class="form-input font-mono" style="height:38px;border-color:#ef4444;color:#ef4444;font-weight:700;">
                            </div>
                        </div>
                    </template>
                </div>

            </div>

            <!-- ===================================================================== -->
            <!-- 3. TABEL BARIS PRODUK BARANG JADI (CRISP & BALANCED)                  -->
            <!-- ===================================================================== -->
            <div class="card p-0">
                <div style="padding:14px 20px;border-bottom:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:space-between;background:var(--color-canvas-soft);border-top-left-radius:var(--rounded-xl);border-top-right-radius:var(--rounded-xl);">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;border-radius:6px;background:rgba(59,130,246,0.12);color:#60a5fa;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="package" style="width:15px;height:15px;"></i>
                        </div>
                        <span style="font-weight:800;font-size:14px;color:var(--color-ink);">Rincian Produk Snack (Bungkus)</span>
                        <span class="badge badge-mono" style="font-size:11px;font-weight:700;" x-text="items.length + ' Baris'"></span>
                    </div>
                    <button type="button" @click="addItemRow()" class="btn btn-primary btn-sm">
                        <i data-lucide="plus"></i>
                        <span>Tambah Baris Snack</span>
                    </button>
                </div>

                <div class="table-scroll no-scrollbar" style="overflow-x:auto;scrollbar-width:none;-ms-overflow-style:none;">
                    <table class="table" style="min-width:860px;width:100%;">
                        <thead>
                            <tr>
                                <th class="cell-center" style="width:36px;">No</th>
                                <th style="min-width:320px;">Produk Snack Siap Jual (137 SKU)</th>
                                <th class="cell-center cell-nowrap" style="width:95px;">Stok Gudang</th>
                                <th class="cell-center cell-nowrap" style="width:85px;">Qty (Bks)</th>
                                <th class="cell-right cell-nowrap" style="width:135px;">Harga Satuan</th>
                                <th class="cell-right cell-nowrap" style="width:110px;">Diskon (Rp)</th>
                                <th class="cell-right cell-nowrap" style="width:145px;">Subtotal (Rp)</th>
                                <th class="cell-center" style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, idx) in items" :key="row.uid">
                                <tr>
                                    <!-- No -->
                                    <td class="cell-center cell-nowrap" style="color:var(--color-ink-mute);font-size:12px;font-weight:700;" x-text="idx + 1"></td>

                                    <!-- Produk Dropdown -->
                                    <td>
                                        <select x-model="row.item_id" @change="onProductSelect(row)" class="form-input" style="height:36px;font-size:12.5px;font-weight:600;width:100%;">
                                            <option value="">-- Pilih Snack &amp; Varian Rasa --</option>
                                            <template x-for="p in availableProducts" :key="p.id">
                                                <option :value="p.id" x-text="p.nama_item + ' (' + p.kode_sku + ')'"></option>
                                            </template>
                                        </select>
                                    </td>

                                    <!-- Stok Gudang -->
                                    <td class="cell-center cell-nowrap">
                                        <span class="badge" :class="Number(row.stok_tersedia) > 0 ? 'badge-mono' : 'badge-warning'" style="font-family:var(--font-mono);font-size:11px;font-weight:700;" x-text="formatNumber(row.stok_tersedia) + ' bks'"></span>
                                    </td>

                                    <!-- Qty (Bungkus) -->
                                    <td class="cell-center cell-nowrap">
                                        <input type="number" min="1" :disabled="!row.item_id" x-model.number="row.qty" @input="calcRow(row)" class="form-input font-mono text-center" style="height:36px;width:75px;font-size:14px;font-weight:800;padding:4px;">
                                    </td>

                                    <!-- Harga Satuan Level (Locked / Sesuai Level Harga Toko) -->
                                    <td class="cell-right cell-nowrap">
                                        <div class="font-mono font-bold text-sm" style="color:var(--color-ink);padding-right:8px;" x-text="row.item_id ? formatRupiah(row.harga) : '-'"></div>
                                    </td>

                                    <!-- Diskon Item -->
                                    <td class="cell-right cell-nowrap">
                                        <input type="number" step="any" min="0" :disabled="!row.item_id" x-model.number="row.diskon" @input="calcRow(row)" class="form-input font-mono text-right" style="height:36px;width:95px;font-size:12.5px;" placeholder="0">
                                    </td>

                                    <!-- Subtotal -->
                                    <td class="cell-right cell-nowrap">
                                        <div class="font-mono font-bold" style="font-size:13.5px;color:var(--color-ink);" x-text="row.item_id ? formatRupiah(row.subtotal) : 'Rp 0'"></div>
                                    </td>

                                    <!-- Hapus Baris -->
                                    <td class="cell-center cell-nowrap">
                                        <button type="button" @click="removeItemRow(idx)" class="btn btn-ghost btn-sm text-danger" style="padding:4px;" title="Hapus baris ini">
                                            <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="8" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                        <div style="font-size:13px;font-weight:600;">Belum ada produk yang ditambahkan ke faktur.</div>
                                        <button type="button" @click="addItemRow()" class="btn btn-secondary btn-sm mt-3">
                                            <i data-lucide="plus"></i>
                                            <span>Tambah Baris Produk Pertama</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ===================================================================== -->
            <!-- 4. RINGKASAN TOTAL & ACTION CARDS                                     -->
            <!-- ===================================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Info Petunjuk Kiri -->
                <div class="card p-5 lg:col-span-2 space-y-3">
                    <div style="font-weight:800;font-size:13.5px;color:var(--color-ink);display:flex;align-items:center;gap:6px;">
                        <i data-lucide="info" style="width:16px;height:16px;color:#60a5fa;"></i>
                        <span>Petunjuk Sistem Penjualan Toko (B2B):</span>
                    </div>
                    <ul style="font-size:12.5px;color:var(--color-ink-mute);line-height:1.6;list-style:disc;padding-left:18px;" class="space-y-1.5">
                        <li>Semua produk barang jadi otomatis dihitung dalam satuan <strong>Bungkus (Pcs)</strong> dengan level harga toko.</li>
                        <li>Stok fisik di gudang akan <strong>langsung terpotong real-time</strong> saat faktur disimpan.</li>
                        <li>Untuk pembayaran <strong>Kredit / Sebagian</strong>, nominal yang dibayar langsung masuk ke kas, dan sisa tagihan tercatat otomatis di piutang toko.</li>
                    </ul>
                </div>

                <!-- Rincian Total Kanan -->
                <div class="card p-5 space-y-3">
                    <div class="flex justify-between items-center" style="font-size:13px;color:var(--color-ink-secondary);">
                        <span>Subtotal Bruto:</span>
                        <span class="font-mono font-bold" x-text="formatRupiah(calcBruto())"></span>
                    </div>

                    <div class="flex justify-between items-center" style="font-size:13px;color:var(--color-ink-secondary);">
                        <span>Total Diskon Item:</span>
                        <span class="font-mono font-semibold" style="color:#f87171;" x-text="'-' + formatRupiah(calcDiskonItem())"></span>
                    </div>

                    <div class="flex justify-between items-center gap-2">
                        <span style="font-size:13px;color:var(--color-ink-secondary);">Diskon Faktur (Rp):</span>
                        <input type="text" name="diskon_faktur" x-model="header.diskon_faktur" class="form-input font-mono input-rupiah text-right" style="height:32px;width:120px;font-size:12px;" placeholder="0">
                    </div>

                    <div style="border-top:2px solid var(--color-hairline);padding-top:10px;" class="flex justify-between items-center">
                        <span style="font-size:14px;font-weight:900;color:var(--color-ink);">TOTAL NETTO:</span>
                        <span class="font-mono font-black" style="font-size:22px;color:var(--color-ink);" x-text="formatRupiah(calcNetto())"></span>
                    </div>

                    <!-- Breakdown Khusus Bayar Sebagian (DP) -->
                    <template x-if="header.tipe_pembayaran === 'sebagian'">
                        <div class="p-3 rounded-lg space-y-1.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);font-size:12px;">
                            <div class="flex justify-between items-center">
                                <span style="color:#10b981;font-weight:700;">Dibayar Masuk Kas (DP):</span>
                                <span class="font-mono font-bold" style="color:#10b981;" x-text="formatRupiah(header.nominal_dibayar || 0)"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span style="color:#f59e0b;font-weight:700;">Sisa Piutang Toko (Tempo):</span>
                                <span class="font-mono font-bold" style="color:#f59e0b;" x-text="formatRupiah(Math.max(0, calcNetto() - (Number(header.nominal_dibayar) || 0)))"></span>
                            </div>
                        </div>
                    </template>

                    <div class="pt-2 flex flex-col gap-2">
                        <button type="button" @click="submitOrder(false)" :disabled="isSubmitting || items.length === 0" class="btn btn-primary w-full" style="height:44px;font-size:14px;font-weight:800;">
                            <i data-lucide="check"></i>
                            <span x-text="isSubmitting ? 'Menyimpan Faktur...' : 'Simpan &amp; Terbitkan Faktur'"></span>
                        </button>
                        <button type="button" @click="submitOrder(true)" :disabled="isSubmitting || items.length === 0" class="btn btn-secondary w-full" style="font-size:13px;font-weight:700;">
                            <i data-lucide="printer"></i>
                            <span>Simpan &amp; Langsung Cetak</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

<script>
function createSalesOrderApp() {
    const rawCustomers = <?= json_encode($customers) ?>;
    const rawProducts = <?= json_encode($products) ?>;
    const priceMatrix = <?= json_encode($priceMatrix) ?>;
    const whitelistMap = <?= json_encode($whitelistMap) ?>;

    return {
        customers: rawCustomers,
        products: rawProducts,
        priceMatrix: priceMatrix,
        whitelistMap: whitelistMap,

        header: {
            nomor_nota: '<?= $autoNota ?>',
            tanggal_pesanan: '<?= date('Y-m-d') ?>',
            pelanggan_id: '',
            sales_driver_id: '',
            tipe_pembayaran: 'cash',
            tanggal_jatuh_tempo: '<?= date('Y-m-d', strtotime('+14 days')) ?>',
            akun_kas_id: '<?= !empty($cashAccounts) ? $cashAccounts[0]['id'] : '' ?>',
            nominal_dibayar: 0,
            diskon_faktur: '0',
            catatan: ''
        },

        items: [],
        selectedCustomer: null,
        showAllProducts: false,
        isSubmitting: false,
        printDirect: false,

        init() {
            this.addItemRow();
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        get hasWhitelist() {
            if (!this.header.pelanggan_id) return false;
            const wl = this.whitelistMap[this.header.pelanggan_id];
            return Boolean(wl && wl.length > 0);
        },

        get whitelistCount() {
            if (!this.header.pelanggan_id) return 0;
            const wl = this.whitelistMap[this.header.pelanggan_id];
            return wl ? wl.length : 0;
        },

        get availableProducts() {
            if (!this.header.pelanggan_id || this.showAllProducts) return this.products;
            const wl = this.whitelistMap[this.header.pelanggan_id];
            if (wl && wl.length > 0) {
                return this.products.filter(p => wl.includes(p.id));
            }
            return this.products;
        },

        onCustomerChange() {
            this.selectedCustomer = this.customers.find(c => c.id === this.header.pelanggan_id) || null;
            this.showAllProducts = false;

            if (this.selectedCustomer) {
                if (this.selectedCustomer.tipe_pembayaran_default) {
                    this.header.tipe_pembayaran = this.selectedCustomer.tipe_pembayaran_default;
                    this.onTipePembayaranChange();
                }

                // Cek apakah item yang sudah ada di baris sesuai dengan whitelist toko baru
                const wl = this.whitelistMap[this.selectedCustomer.id];
                this.items.forEach(row => {
                    if (row.item_id) {
                        if (wl && wl.length > 0 && !wl.includes(row.item_id)) {
                            row.item_id = '';
                            row.stok_tersedia = 0;
                        } else {
                            row.harga = this.getPriceForProduct(row.item_id);
                            this.calcRow(row);
                        }
                    }
                });
            }

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
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

        getPriceForProduct(itemId) {
            if (!itemId) return 0;
            const product = this.products.find(p => p.id === itemId);
            if (!product) return 0;
            const level = this.selectedCustomer ? (Number(this.selectedCustomer.level_harga) || 1) : 1;
            const groupPrices = this.priceMatrix[product.grup_id];
            if (groupPrices && groupPrices[level]) {
                return Number(groupPrices[level].pcs || 0);
            }
            return 0;
        },

        addItemRow() {
            this.items.push({
                uid: Date.now() + Math.random().toString(36).substr(2, 5),
                item_id: '',
                stok_tersedia: 0,
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

        onProductSelect(row) {
            if (!row.item_id) {
                row.stok_tersedia = 0;
                row.harga = 0;
                row.diskon = 0;
                row.subtotal = 0;
                return;
            }
            const product = this.products.find(p => p.id === row.item_id);
            if (product) {
                row.stok_tersedia = Number(product.stok_fisik_saat_ini || 0);
                row.harga = this.getPriceForProduct(product.id);
                this.calcRow(row);
            }
        },

        calcRow(row) {
            if (!row.item_id) {
                row.harga = 0;
                row.subtotal = 0;
                return;
            }
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
            const diskonFaktur = Number(String(this.header.diskon_faktur || 0).replace(/[^0-9]/g, ''));
            return Math.max(0, subtotalItems - diskonFaktur);
        },

        submitOrder(print = false) {
            if (!this.header.pelanggan_id) {
                toast.warning('Mohon pilih Toko Pelanggan terlebih dahulu.');
                return;
            }
            if (!this.header.sales_driver_id) {
                toast.warning('Mohon pilih Sales / Driver penanggung jawab pengiriman.');
                return;
            }

            const validItems = this.items.filter(r => r.item_id && Number(r.qty) > 0);
            if (validItems.length === 0) {
                toast.warning('Mohon masukkan minimal 1 produk snack dengan Qty valid.');
                return;
            }

            this.printDirect = print;
            this.isSubmitting = true;
            this.$nextTick(() => {
                document.getElementById('salesOrderForm').submit();
            });
        },

        formatRupiah(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
        },

        formatNumber(val) {
            return new Intl.NumberFormat('id-ID').format(val || 0);
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

<?php
use App\Core\Router;
ob_start();
?>

<div x-data="posApp()" x-init="init()" @keydown.window="handleHotkeys($event)"
     class="flex flex-col lg:flex-row gap-5 lg:h-[calc(100vh-7.5rem)] min-w-0">

    <!-- ====================================================================== -->
    <!-- KOLOM KIRI: KATALOG PRODUK & SCANNER                                   -->
    <!-- ====================================================================== -->
    <div class="flex-1 flex flex-col min-w-0 card overflow-hidden" style="padding:0;">

        <!-- SEARCH & BARCODE HEADER -->
        <div class="flex flex-col sm:flex-row gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">

            <!-- Scan Barcode Input -->
            <div class="form-input-icon flex-1">
                <i data-lucide="scan-barcode" class="icon-left" style="color:var(--color-primary-deep);"></i>
                <input type="text" x-model="barcodeQuery" @keydown.enter="scanBarcode()"
                    x-ref="barcodeInput"
                    placeholder="Scan / ketik barcode kemasan..."
                    class="form-input font-mono"
                    style="height:40px;font-size:13px;">
            </div>

            <!-- Search Filter -->
            <div class="form-input-icon sm:w-72">
                <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                <input type="text" x-model="searchQuery"
                    x-ref="searchInput"
                    placeholder="Cari SKU / Nama Rasa (F1)..."
                    class="form-input"
                    style="height:40px;font-size:13px;">
            </div>
        </div>

        <!-- FILTER CHIPS (Kategori) -->
        <div class="flex items-center gap-2 px-4 py-3 overflow-x-auto no-scrollbar" style="border-bottom:1px solid var(--color-hairline); flex-shrink:0; background-color:var(--color-canvas);">
            <button @click="selectedGroup = 'all'"
                class="category-pill"
                :class="{ 'is-active': selectedGroup === 'all' }">
                Semua
            </button>
            <?php foreach ($groups as $g): ?>
            <button @click="selectedGroup = '<?= $g['id'] ?>'"
                class="category-pill"
                :class="{ 'is-active': selectedGroup === '<?= $g['id'] ?>' }">
                <?= htmlspecialchars($g['nama_grup']) ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- GRID PRODUK -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-4 pb-24 lg:pb-4" style="background-color:var(--color-canvas-soft);">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 2xl:grid-cols-3 gap-3.5">
                <template x-for="item in filteredItems" :key="item.id">
                    <div @click="addItemToCart(item)"
                         class="product-card group">

                        <div class="product-card-head">
                            <div style="flex:1;min-width:0;">
                                <span class="badge badge-mono" x-text="item.kode_sku"></span>
                                <div class="product-card-name" x-text="item.nama_item"></div>
                                <div class="product-card-grup" x-text="item.nama_grup"></div>
                            </div>
                        </div>

                        <div class="product-card-foot">
                            <div>
                                <span class="product-card-price" x-text="formatRupiah(item.calculatedPrice || item.harga_pokok_pembelian * 1.25)"></span>
                                <div class="product-card-stock" x-text="'Stok: ' + item.stok_fisik_saat_ini + ' ' + item.satuan_dasar"></div>
                            </div>
                            <button class="btn btn-primary btn-sm" style="flex-shrink:0;" @click.stop="addItemToCart(item)">
                                <i data-lucide="plus"></i>
                                Tambah
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <template x-if="filteredItems.length === 0">
                    <div class="col-span-full flex flex-col items-center justify-center py-16 text-center" style="color:var(--color-ink-mute);">
                        <i data-lucide="package-x" style="width:40px;height:40px;margin-bottom:12px;opacity:0.6;"></i>
                        <div style="font-size:14px;font-weight:600;color:var(--color-ink);">Tidak ada produk ditemukan</div>
                        <div style="font-size:12px;margin-top:4px;">Coba ubah filter kategori atau kata kunci pencarian</div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- KOLOM KANAN: KERANJANG BELANJA (Desktop)                               -->
    <!-- ====================================================================== -->
    <div class="hidden lg:flex w-96 card flex-col justify-between" style="width:360px;flex-shrink:0;padding:0;">

        <div class="flex flex-col flex-1 min-h-0 p-5 gap-4">

            <!-- Toko Pelanggan -->
            <div>
                <label class="form-label">Toko Pelanggan</label>
                <select x-model="selectedCustomerId" @change="recalculateCartPrices()" class="form-select font-semibold">
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>">
                        <?= htmlspecialchars($c['nama_toko']) ?> (<?= htmlspecialchars($c['grup_nama']) ?>) <?= $c['is_konsinyasi'] ? '• [KONSINYASI]' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Cart Header -->
            <div class="section-header" style="margin-bottom:0;padding-bottom:10px;">
                <span class="section-title" style="display:flex;align-items:center;gap:8px;">
                    <i data-lucide="shopping-bag" style="width:16px;height:16px;color:var(--color-primary-deep);"></i>
                    Keranjang (<span x-text="cart.length"></span> item)
                </span>
                <button @click="clearCart()" x-show="cart.length > 0"
                        class="btn btn-ghost btn-sm"
                        style="color:var(--color-danger);padding:4px 8px;font-size:11.5px;">
                    Kosongkan
                </button>
            </div>

            <!-- Cart List -->
            <div class="flex-1 overflow-y-auto custom-scrollbar" style="display:flex;flex-direction:column;gap:8px;padding-right:2px;">
                <template x-if="cart.length === 0">
                    <div style="height:180px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;border:1px dashed var(--color-hairline-strong);border-radius:var(--rounded-lg);color:var(--color-ink-mute);gap:8px;padding:16px;">
                        <i data-lucide="shopping-cart" style="width:32px;height:32px;opacity:0.5;"></i>
                        <div style="font-size:12.5px;font-weight:500;line-height:1.4;">Keranjang belanja masih kosong.<br>Pilih produk di sebelah kiri.</div>
                    </div>
                </template>

                <template x-for="(item, index) in cart" :key="item.item_id">
                    <div style="padding:12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px;">
                            <div style="min-width:0;">
                                <div style="font-size:12.5px;font-weight:700;color:var(--color-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="item.nama_item"></div>
                                <div style="font-size:11.5px;font-family:var(--font-mono);color:var(--color-primary-deep);font-weight:600;margin-top:1px;" x-text="formatRupiah(item.price) + ' / ' + item.satuan"></div>
                            </div>
                            <button @click="removeItem(index)" class="btn btn-ghost btn-sm" style="padding:4px 6px;color:var(--color-danger);border-radius:var(--rounded-xs);" title="Hapus item">
                                <i data-lucide="trash-2" style="width:15px;height:15px;color:var(--color-danger);"></i>
                            </button>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;padding-top:8px;border-top:1px solid var(--color-hairline);">
                            <div class="qty-counter">
                                <button @click="updateQty(index, -1)" class="qty-btn">−</button>
                                <input type="number" x-model.number="item.qty_pcs" @change="recalculateItemSubtotal(index)" class="qty-input">
                                <button @click="updateQty(index, 1)" class="qty-btn">+</button>
                            </div>
                            <span style="font-size:13.5px;font-weight:800;font-family:var(--font-mono);color:var(--color-ink);" x-text="formatRupiah(item.subtotal)"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Total & Checkout -->
        <div style="padding:18px 20px;border-top:1px solid var(--color-hairline);background-color:var(--color-canvas);flex-shrink:0;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <span style="font-size:12.5px;font-weight:500;color:var(--color-ink-mute);">Total Kuantitas</span>
                <span style="font-size:13px;font-family:var(--font-mono);font-weight:800;color:var(--color-ink);" x-text="totalPcsCount + ' pcs'"></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:baseline;padding-top:10px;margin-top:6px;border-top:1px solid var(--color-hairline);margin-bottom:14px;">
                <span style="font-size:13.5px;font-weight:700;color:var(--color-ink);">Grand Total Netto</span>
                <span style="font-size:22px;font-weight:900;font-family:var(--font-mono);color:var(--color-primary-deep);letter-spacing:-0.03em;" x-text="formatRupiah(grandTotal)"></span>
            </div>
            <button @click="openPaymentModal()" :disabled="cart.length === 0"
                    class="btn btn-primary btn-full btn-lg" style="justify-content:center;height:44px;">
                <i data-lucide="credit-card"></i>
                Bayar Sekarang (F4)
            </button>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MOBILE BOTTOM BAR                                                      -->
    <!-- ====================================================================== -->
    <div class="mobile-bottom-bar">
        <div @click="showMobileCartDrawer = true" style="flex:1;cursor:pointer;">
            <div style="font-size:12px;font-weight:600;color:var(--color-ink-mute);">
                <span style="font-weight:800;color:var(--color-primary-deep);" x-text="totalPcsCount"></span> Item di Keranjang
            </div>
            <div style="font-size:17px;font-weight:900;font-family:var(--font-mono);color:var(--color-ink);" x-text="formatRupiah(grandTotal)"></div>
        </div>
        <button @click="showMobileCartDrawer = true" class="btn btn-primary" style="flex-shrink:0;">
            <i data-lucide="shopping-cart"></i>
            <span x-text="cart.length > 0 ? 'Keranjang (' + cart.length + ')' : 'Keranjang'"></span>
        </button>
    </div>

    <!-- MOBILE CART DRAWER -->
    <div x-show="showMobileCartDrawer" x-cloak
         class="lg:hidden fixed inset-0 z-50 flex flex-col justify-end"
         style="background:rgba(0,0,0,0.65);backdrop-filter:blur(4px);">
        <div @click.away="showMobileCartDrawer = false" class="cart-drawer">

            <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid var(--color-hairline);">
                <span class="section-title" style="display:flex;align-items:center;gap:8px;">
                    <i data-lucide="shopping-bag" style="width:16px;height:16px;color:var(--color-primary-deep);"></i>
                    Keranjang Kasir
                </span>
                <button @click="showMobileCartDrawer = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <!-- Pelanggan Mobile -->
            <div>
                <label class="form-label">Toko Pelanggan</label>
                <select x-model="selectedCustomerId" @change="recalculateCartPrices()" class="form-select font-semibold">
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nama_toko']) ?> (<?= htmlspecialchars($c['grup_nama']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Cart Items Mobile -->
            <div style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:8px;max-height:260px;" class="custom-scrollbar">
                <template x-if="cart.length === 0">
                    <div style="text-align:center;padding:24px;font-size:12.5px;color:var(--color-ink-mute);">Keranjang belanja masih kosong.</div>
                </template>
                <template x-for="(item, index) in cart" :key="item.item_id">
                    <div style="padding:12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                            <div style="font-size:12.5px;font-weight:700;color:var(--color-ink);" x-text="item.nama_item"></div>
                            <button @click="removeItem(index)" class="btn btn-ghost btn-sm" style="padding:4px 6px;color:var(--color-danger);border-radius:var(--rounded-xs);" title="Hapus item">
                                <i data-lucide="trash-2" style="width:15px;height:15px;color:var(--color-danger);"></i>
                            </button>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding-top:8px;border-top:1px solid var(--color-hairline);">
                            <div class="qty-counter">
                                <button @click="updateQty(index, -1)" class="qty-btn">−</button>
                                <input type="number" x-model.number="item.qty_pcs" @change="recalculateItemSubtotal(index)" class="qty-input">
                                <button @click="updateQty(index, 1)" class="qty-btn">+</button>
                            </div>
                            <span style="font-size:13px;font-weight:800;font-family:var(--font-mono);" x-text="formatRupiah(item.subtotal)"></span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Total & Bayar Mobile -->
            <div style="padding-top:12px;border-top:1px solid var(--color-hairline);">
                <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:12px;">
                    <span style="font-size:13.5px;font-weight:700;color:var(--color-ink);">Total Netto:</span>
                    <strong style="font-size:22px;font-weight:900;font-family:var(--font-mono);color:var(--color-primary-deep);" x-text="formatRupiah(grandTotal)"></strong>
                </div>
                <button @click="showMobileCartDrawer = false; openPaymentModal()" :disabled="cart.length === 0"
                        class="btn btn-primary btn-full btn-lg" style="justify-content:center;">
                    Lanjut ke Pembayaran
                </button>
            </div>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MODAL 1: DISAMBIGUASI BARCODE                                          -->
    <!-- ====================================================================== -->
    <div x-show="showBarcodeModal" x-cloak class="modal-backdrop">
        <div @click.away="showBarcodeModal = false" class="modal-box">
            <div class="modal-header">
                <div>
                    <div class="modal-title">Pilih Varian Rasa</div>
                    <div style="font-size:11.5px;font-family:var(--font-mono);font-weight:700;color:var(--color-primary-deep);margin-top:2px;" x-text="'Barcode: ' + currentScannedBarcode"></div>
                </div>
                <button @click="showBarcodeModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <p style="font-size:12.5px;font-weight:500;color:var(--color-ink-secondary);margin-bottom:12px;">Barcode ini digunakan bersama. Pilih varian yang di-scan:</p>

            <div style="display:flex;flex-direction:column;gap:8px;max-height:240px;overflow-y:auto;" class="custom-scrollbar">
                <template x-for="(v, i) in multiVariants" :key="v.item_id">
                    <button @click="selectVariant(v)"
                            style="padding:12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;align-items:center;justify-content:space-between;text-align:left;cursor:pointer;transition:all 0.15s;"
                            onmouseover="this.style.borderColor='var(--color-primary)';this.style.transform='translateY(-1px)'"
                            onmouseout="this.style.borderColor='var(--color-hairline)';this.style.transform='none'">
                        <div>
                            <div style="font-size:13px;font-weight:700;color:var(--color-ink);" x-text="v.nama_item"></div>
                            <div style="font-size:11.5px;font-family:var(--font-mono);font-weight:500;color:var(--color-ink-mute);margin-top:1px;" x-text="v.grup_nama + ' • Stok: ' + v.stok_fisik + ' ' + v.satuan_dasar"></div>
                        </div>
                        <span style="font-size:12px;font-family:var(--font-mono);font-weight:800;color:var(--color-primary-deep);" x-text="'[' + (i+1) + ']'"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MODAL 2: PEMBAYARAN                                                    -->
    <!-- ====================================================================== -->
    <div x-show="showPaymentModal" x-cloak class="modal-backdrop">
        <div @click.away="showPaymentModal = false" class="modal-box">
            <div class="modal-header">
                <div class="modal-title">Pembayaran Kasir</div>
                <button @click="showPaymentModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <!-- Tipe Pembayaran -->
            <div style="margin-bottom:16px;">
                <label class="form-label">Tipe Pembayaran</label>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                    <button @click="paymentType = 'cash'"
                            :class="paymentType === 'cash' ? 'btn btn-primary btn-sm' : 'btn btn-secondary btn-sm'"
                            style="justify-content:center;">Tunai</button>
                    <button @click="paymentType = 'tempo_7_hari'"
                            :class="paymentType === 'tempo_7_hari' ? 'btn btn-primary btn-sm' : 'btn btn-secondary btn-sm'"
                            style="justify-content:center;">Tempo 7H</button>
                    <button @click="paymentType = 'tempo_14_hari'"
                            :class="paymentType === 'tempo_14_hari' ? 'btn btn-primary btn-sm' : 'btn btn-secondary btn-sm'"
                            style="justify-content:center;">Tempo 14H</button>
                </div>
            </div>

            <!-- Tagihan -->
            <div style="padding:14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:12.5px;font-weight:600;color:var(--color-ink-secondary);">Total Netto</span>
                    <strong style="font-size:16px;font-family:var(--font-mono);font-weight:800;color:var(--color-ink);" x-text="formatRupiah(grandTotal)"></strong>
                </div>

                <div x-show="paymentType === 'cash'" style="margin-top:10px;padding-top:10px;border-top:1px solid var(--color-hairline);">
                    <label class="form-label">Nominal Uang Tunai</label>
                    <input type="number" x-model.number="paidAmount" class="form-input font-mono" style="font-size:15px;font-weight:800;">

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
                        <span style="font-size:12.5px;font-weight:600;color:var(--color-ink-secondary);">Kembalian:</span>
                        <strong :style="paidAmount >= grandTotal ? 'color:var(--color-primary-deep)' : 'color:var(--color-danger)'"
                                style="font-family:var(--font-mono);font-size:15px;font-weight:800;"
                                x-text="formatRupiah(Math.max(0, paidAmount - grandTotal))"></strong>
                    </div>
                </div>
            </div>

            <button @click="submitCheckout()" :disabled="isSubmitting"
                    class="btn btn-primary btn-full btn-lg" style="justify-content:center;height:44px;">
                <i data-lucide="check-circle"></i>
                <span x-show="!isSubmitting">Konfirmasi & Simpan Transaksi</span>
                <span x-show="isSubmitting">Memproses Transaksi...</span>
            </button>
        </div>
    </div>

</div>

<!-- Alpine.js POS Store -->
<script>
function posApp() {
    return {
        items: <?= json_encode($items) ?>,
        customers: <?= json_encode($customers) ?>,
        selectedCustomerId: '<?= $customers[0]['id'] ?? '' ?>',
        selectedGroup: 'all',
        searchQuery: '',
        barcodeQuery: '',
        cart: [],

        showMobileCartDrawer: false,
        showBarcodeModal: false,
        currentScannedBarcode: '',
        multiVariants: [],

        showPaymentModal: false,
        paymentType: 'cash',
        paidAmount: 0,
        isSubmitting: false,

        init() {
            this.$nextTick(() => {
                lucide.createIcons();
                if (window.innerWidth >= 1024 && !('ontouchstart' in window)) {
                    this.$refs.barcodeInput?.focus();
                }
            });
        },

        get filteredItems() {
            return this.items.filter(item => {
                const matchGroup = (this.selectedGroup === 'all' || item.grup_id === this.selectedGroup);
                const query = this.searchQuery.toLowerCase();
                const matchQuery = !query ||
                    item.nama_item.toLowerCase().includes(query) ||
                    item.kode_sku.toLowerCase().includes(query) ||
                    (item.barcode && item.barcode.includes(query));
                return matchGroup && matchQuery;
            });
        },

        get totalPcsCount() {
            return this.cart.reduce((sum, item) => sum + item.qty_pcs, 0);
        },

        get grandTotal() {
            return this.cart.reduce((sum, item) => sum + item.subtotal, 0);
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        handleHotkeys(e) {
            if (e.key === 'F1') {
                e.preventDefault(); this.$refs.searchInput?.focus();
            } else if (e.key === 'F2') {
                e.preventDefault(); this.$refs.barcodeInput?.focus();
            } else if (e.key === 'F4') {
                e.preventDefault(); if (this.cart.length > 0) this.openPaymentModal();
            } else if (e.key === 'Escape') {
                this.showBarcodeModal = false;
                this.showPaymentModal = false;
                this.showMobileCartDrawer = false;
            }
        },

        async scanBarcode() {
            const barcode = this.barcodeQuery.trim();
            if (!barcode) return;
            try {
                const url = '<?= Router::url('/api/pos/search-barcode') ?>?barcode=' + encodeURIComponent(barcode);
                const res = await fetch(url);
                const json = await res.json();
                if (json.success && json.data.ditemukan) {
                    if (json.data.total_varian === 1) {
                        const rawItem = this.items.find(i => i.id === json.data.items[0].item_id);
                        if (rawItem) this.addItemToCart(rawItem);
                    } else {
                        this.currentScannedBarcode = barcode;
                        this.multiVariants = json.data.items;
                        this.showBarcodeModal = true;
                    }
                } else {
                    alert(`Barcode '${barcode}' tidak ditemukan.`);
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.barcodeQuery = '';
                this.$nextTick(() => lucide.createIcons());
            }
        },

        selectVariant(variant) {
            const rawItem = this.items.find(i => i.id === variant.item_id);
            if (rawItem) this.addItemToCart(rawItem);
            this.showBarcodeModal = false;
        },

        async addItemToCart(rawItem) {
            const existingIndex = this.cart.findIndex(c => c.item_id === rawItem.id);
            if (existingIndex > -1) {
                this.cart[existingIndex].qty_pcs += 1;
                this.recalculateItemSubtotal(existingIndex);
                return;
            }

            let calculatedPrice = rawItem.harga_pokok_pembelian * 1.25;
            let discountPercent = 0;
            let discountNominal = 0;

            try {
                const url = '<?= Router::url('/api/pos/calculate-price') ?>?item_id=' + rawItem.id + '&customer_id=' + this.selectedCustomerId;
                const res = await fetch(url);
                const json = await res.json();
                if (json.success && json.data) {
                    calculatedPrice = Number(json.data.harga_pcs_netto);
                    discountPercent = Number(json.data.diskon_persen);
                    discountNominal = Number(json.data.diskon_nominal);
                }
            } catch (e) { console.error(e); }

            this.cart.push({
                item_id: rawItem.id,
                kode_sku: rawItem.kode_sku,
                nama_item: rawItem.nama_item,
                grup_nama: rawItem.nama_grup,
                satuan: rawItem.satuan_dasar,
                price: calculatedPrice,
                qty_pcs: 1,
                qty_bal: 0,
                discount_percent: discountPercent,
                discount_nominal: discountNominal,
                subtotal: calculatedPrice
            });

            this.$nextTick(() => lucide.createIcons());
        },

        updateQty(index, change) {
            const newQty = this.cart[index].qty_pcs + change;
            if (newQty <= 0) {
                this.removeItem(index);
            } else {
                this.cart[index].qty_pcs = newQty;
                this.recalculateItemSubtotal(index);
            }
        },

        removeItem(index) {
            this.cart.splice(index, 1);
            this.$nextTick(() => lucide.createIcons());
        },

        async clearCart() {
            if (this.cart.length === 0) return;
            const confirmed = await AppConfirm({
                title: 'Kosongkan Keranjang?',
                message: 'Semua item yang telah dipilih akan dihapus dari daftar belanja kasir.',
                confirmText: 'Ya, Kosongkan',
                cancelText: 'Batal',
                type: 'danger',
                icon: 'trash-2'
            });
            if (confirmed) {
                this.cart = [];
                this.showMobileCartDrawer = false;
                if (typeof AppToast !== 'undefined') {
                    AppToast.show('Keranjang berhasil dikosongkan', 'info', 2500);
                }
            }
        },

        recalculateItemSubtotal(index) {
            const item = this.cart[index];
            item.subtotal = item.qty_pcs * item.price;
        },

        async recalculateCartPrices() {
            for (let i = 0; i < this.cart.length; i++) {
                try {
                    const url = '<?= Router::url('/api/pos/calculate-price') ?>?item_id=' + this.cart[i].item_id + '&customer_id=' + this.selectedCustomerId;
                    const res = await fetch(url);
                    const json = await res.json();
                    if (json.success && json.data) {
                        this.cart[i].price = Number(json.data.harga_pcs_netto);
                        this.recalculateItemSubtotal(i);
                    }
                } catch (e) { console.error(e); }
            }
        },

        openPaymentModal() {
            this.paidAmount = this.grandTotal;
            this.showPaymentModal = true;
        },

        async submitCheckout() {
            this.isSubmitting = true;
            try {
                const res = await fetch('<?= Router::url('/api/pos/checkout') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer_id: this.selectedCustomerId,
                        payment_type: this.paymentType,
                        cart: this.cart,
                        paid_amount: this.paidAmount
                    })
                });
                const json = await res.json();
                if (json.success) {
                    alert(`✅ Transaksi Selesai!\nNomor Nota: ${json.data.nomor_nota}`);
                    this.cart = [];
                    this.showPaymentModal = false;
                    this.showMobileCartDrawer = false;
                } else {
                    alert(`❌ Gagal: ${json.message}`);
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
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

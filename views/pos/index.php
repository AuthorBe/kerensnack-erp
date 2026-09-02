<?php
use App\Core\Router;
ob_start();
?>

<!-- Alpine.js POS Store (Defined First to Eliminate Race Conditions) -->
<script>
function posApp() {
    return {
        items: <?= json_encode($items) ?>,
        customers: <?= json_encode($customers) ?>,
        customerItemsMap: <?= json_encode($customerItemsMap ?? []) ?>,
        selectedCustomerId: '<?= $customers[0]['id'] ?? '' ?>',
        filterStoreOnly: true,
        selectedGroup: 'all',
        searchQuery: '',
        barcodeQuery: '',
        cart: [],

        showMobileCartDrawer: false,
        showBarcodeModal: false,
        currentScannedBarcode: '',
        multiVariants: [],

        showPaymentModal: false,
        showReceiptModal: false,
        receiptData: null,
        paymentType: 'cash',
        paidAmount: 0,
        paidAmountDisplay: '0',
        isSubmitting: false,

        init() {
            window.posInstance = this;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
                    lucide.createIcons();
                }
                if (window.innerWidth >= 1024 && !('ontouchstart' in window)) {
                    this.$refs.barcodeInput?.focus();
                }
            });
        },

        get hasCustomerAssignedItems() {
            const assigned = this.customerItemsMap[this.selectedCustomerId];
            return Array.isArray(assigned) && assigned.length > 0;
        },

        get filteredItems() {
            const assigned = this.customerItemsMap[this.selectedCustomerId];
            const isRestricted = this.filterStoreOnly && Array.isArray(assigned) && assigned.length > 0;

            return this.items.filter(item => {
                if (isRestricted && !assigned.includes(item.id)) {
                    return false;
                }
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
            if (!this.cart || !Array.isArray(this.cart)) return 0;
            return this.cart.reduce((sum, item) => sum + (parseInt(item.qty_pcs, 10) || 0), 0);
        },

        get grandTotal() {
            if (!this.cart || !Array.isArray(this.cart)) return 0;
            return this.cart.reduce((sum, item) => sum + (Number(item.subtotal) || 0), 0);
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        handleHotkeys(e) {
            if (this.showReceiptModal) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.printReceipt();
                } else if (e.key === 'F2') {
                    e.preventDefault();
                    this.resetForNewTransaction();
                } else if (e.key === 'Escape') {
                    this.showReceiptModal = false;
                }
                return;
            }

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

        isScanning: false,

        async scanBarcode() {
            const barcode = this.barcodeQuery.trim();
            if (!barcode || this.isScanning) return;
            this.isScanning = true;
            try {
                const url = '<?= Router::url('/api/pos/search-barcode') ?>?barcode=' + encodeURIComponent(barcode);
                const res = await fetch(url);
                const json = await res.json();
                if (json.success && json.data.ditemukan) {
                    if (json.data.total_varian === 1) {
                        const rawItem = this.items.find(i => String(i.id) === String(json.data.items[0].item_id));
                        if (rawItem) this.addItemToCart(rawItem);
                    } else {
                        this.currentScannedBarcode = barcode;
                        this.multiVariants = json.data.items;
                        this.showBarcodeModal = true;
                    }
                } else {
                    if (window.AppAlert) {
                        window.AppAlert({
                            title: 'Barcode Tidak Ditemukan',
                            message: `Barcode '${barcode}' tidak terdaftar dalam katalog produk aktif.`,
                            type: 'warning',
                            icon: 'scan-line'
                        });
                    } else if (typeof toast !== 'undefined') {
                        toast.warning(`Barcode '${barcode}' tidak ditemukan dalam katalog.`);
                    }
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.isScanning = false;
                this.barcodeQuery = '';
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        selectVariant(variant) {
            const rawItem = this.items.find(i => String(i.id) === String(variant.item_id));
            if (rawItem) this.addItemToCart(rawItem);
            this.showBarcodeModal = false;
        },

        getItemQty(itemId) {
            if (!this.cart || !Array.isArray(this.cart) || !itemId) return 0;
            const item = this.cart.find(c => String(c.item_id) === String(itemId));
            return item ? (parseInt(item.qty_pcs, 10) || 0) : 0;
        },

        decreaseItemInCart(rawItem) {
            if (!rawItem || !rawItem.id) return;
            const existingIndex = this.cart.findIndex(c => String(c.item_id) === String(rawItem.id));
            if (existingIndex > -1) {
                this.updateQty(existingIndex, -1);
            }
        },

        async addItemToCart(rawItem) {
            if (!rawItem || !rawItem.id) return;

            if (Number(rawItem.stok_fisik_saat_ini || 0) <= 0) {
                if (window.AppAlert) {
                    window.AppAlert({
                        title: 'Stok Habis',
                        message: `Stok produk '${rawItem.nama_item}' sedang habis (0 pcs).`,
                        type: 'warning',
                        icon: 'package-x'
                    });
                } else if (typeof toast !== 'undefined') {
                    toast.warning(`Stok produk '${rawItem.nama_item}' sedang habis (0 pcs).`);
                }
                return;
            }

            const existingIndex = this.cart.findIndex(c => String(c.item_id) === String(rawItem.id));
            if (existingIndex > -1) {
                this.updateQty(existingIndex, 1);
                return;
            }

            let calculatedPrice = Number(rawItem.harga_jual_satuan || 15000);
            let discountPercent = 0;
            let discountNominal = 0;

            if (this.selectedCustomerId) {
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
            }

            this.cart.push({
                item_id: rawItem.id,
                kode_sku: rawItem.kode_sku || '',
                nama_item: rawItem.nama_item || 'Produk',
                grup_nama: rawItem.nama_grup || '',
                satuan: rawItem.satuan_dasar || 'pcs',
                price: calculatedPrice,
                qty_pcs: 1,
                qty_bal: 0,
                discount_percent: discountPercent,
                discount_nominal: discountNominal,
                subtotal: calculatedPrice
            });

            this.cart = [...this.cart];

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
                    lucide.createIcons();
                }
            });
        },

        updateQty(index, change) {
            if (!this.cart[index]) return;
            const current = parseInt(this.cart[index].qty_pcs, 10) || 1;
            const newQty = current + change;
            if (newQty <= 0) {
                this.removeItem(index);
            } else {
                this.cart[index].qty_pcs = newQty;
                this.cart[index].subtotal = newQty * Number(this.cart[index].price || 0);
                this.cart = [...this.cart];
            }
        },

        recalculateItemSubtotal(index) {
            if (!this.cart[index]) return;
            const qty = Math.max(1, parseInt(this.cart[index].qty_pcs, 10) || 1);
            this.cart[index].qty_pcs = qty;
            this.cart[index].subtotal = qty * Number(this.cart[index].price || 0);
            this.cart = [...this.cart];
        },

        removeItem(index) {
            this.cart.splice(index, 1);
            this.cart = [...this.cart];
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
                    lucide.createIcons();
                }
            });
        },

        async clearCart() {
            if (this.cart.length === 0) return;
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Kosongkan Keranjang POS',
                message: 'Apakah Anda yakin ingin mengosongkan semua item dari keranjang belanja kasir?',
                confirmText: 'Ya, Kosongkan',
                cancelText: 'Batal',
                type: 'danger',
                icon: 'trash-2'
            }) : confirm('Kosongkan semua item dari keranjang belanja?');

            if (confirmed) {
                this.cart = [];
                this.showMobileCartDrawer = false;
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
            this.paymentType = 'cash';
            this.paidAmount = this.grandTotal;
            this.paidAmountDisplay = window.formatRupiahNumber ? window.formatRupiahNumber(this.grandTotal) : String(this.grandTotal);
            this.showPaymentModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
                this.$refs.paidInput?.focus();
                this.$refs.paidInput?.select();
            });
        },

        setQuickCash(amount) {
            this.paidAmount = amount;
            this.paidAmountDisplay = window.formatRupiahNumber ? window.formatRupiahNumber(amount) : String(amount);
        },

        setExactCash() {
            this.paidAmount = this.grandTotal;
            this.paidAmountDisplay = window.formatRupiahNumber ? window.formatRupiahNumber(this.grandTotal) : String(this.grandTotal);
        },

        onPaidInput(e) {
            const rawVal = e.target.value.replace(/[^0-9]/g, '');
            this.paidAmount = rawVal ? parseInt(rawVal, 10) : 0;
            this.paidAmountDisplay = window.formatRupiahNumber ? window.formatRupiahNumber(this.paidAmount) : String(this.paidAmount);
        },

        async submitCheckout() {
            if (this.paymentType === 'cash' && this.paidAmount < this.grandTotal) {
                if (window.AppAlert) {
                    window.AppAlert({
                        title: 'Nominal Kurang',
                        message: `Uang tunai yang dibayarkan (Rp ${Number(this.paidAmount).toLocaleString('id-ID')}) kurang dari total tagihan (Rp ${Number(this.grandTotal).toLocaleString('id-ID')}).`,
                        type: 'warning',
                        icon: 'alert-triangle'
                    });
                } else if (typeof toast !== 'undefined') {
                    toast.warning('Nominal uang tunai kurang dari total tagihan!');
                }
                return;
            }

            this.isSubmitting = true;
            if (window.AppAction) {
                window.AppAction.show('Memproses transaksi kasir...');
            }

            try {
                const res = await fetch('<?= Router::url('/api/pos/checkout') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer_id: this.selectedCustomerId,
                        payment_type: this.paymentType,
                        cart: this.cart,
                        paid_amount: this.paymentType === 'qris' ? this.grandTotal : this.paidAmount
                    })
                });
                const json = await res.json();
                if (json.success) {
                    if (window.AppAction) {
                        await window.AppAction.success('Transaksi Berhasil! ✨', 650);
                    }

                    if (json.data && json.data.items) {
                        json.data.items.forEach(it => {
                            const found = this.items.find(i => String(i.id) === String(it.item_id));
                            if (found) {
                                found.stok_fisik_saat_ini = Math.max(0, found.stok_fisik_saat_ini - it.qty_pcs);
                            }
                        });
                    }

                    this.receiptData = {
                        ...json.data,
                        paid_amount: this.paymentType === 'qris' ? this.grandTotal : this.paidAmount
                    };
                    this.cart = [];
                    this.showPaymentModal = false;
                    this.showMobileCartDrawer = false;
                    this.showReceiptModal = true;
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    });
                } else {
                    if (window.AppAction) window.AppAction.hide();
                    toast.error(`Gagal memproses transaksi: ${json.message}`);
                }
            } catch (err) {
                if (window.AppAction) window.AppAction.hide();
                toast.error('Terjadi kesalahan jaringan/koneksi saat memproses checkout.');
            } finally {
                this.isSubmitting = false;
            }
        },

        printReceipt() {
            window.print();
        },

        resetForNewTransaction() {
            this.showReceiptModal = false;
            this.receiptData = null;
            this.cart = [];
            this.$nextTick(() => {
                this.$refs.barcodeInput?.focus();
            });
        }
    }
}
window.posApp = posApp;
document.addEventListener('alpine:init', () => {
    if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
        Alpine.data('posApp', posApp);
    }
});
</script>

<div x-data="posApp()" x-init="init()" @keydown.window="handleHotkeys($event)"
     class="flex flex-col lg:flex-row gap-5 lg:h-[calc(100vh-7.5rem)] min-w-0">

    <!-- ====================================================================== -->
    <!-- KOLOM KIRI: KATALOG PRODUK & SCANNER                                   -->
    <!-- ====================================================================== -->
    <div class="flex-1 flex flex-col min-w-0 lg:card overflow-visible lg:overflow-hidden" style="padding:0;background:transparent;border:none;">

        <!-- SEARCH & BARCODE HEADER -->
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 p-3 sm:p-4 rounded-xl lg:rounded-b-none" style="border:1px solid var(--color-hairline);background-color:var(--color-canvas);margin-bottom:6px;">

            <!-- Scan Barcode Input -->
            <div class="form-input-icon flex-1">
                <i data-lucide="scan-barcode" class="icon-left" style="color:#6366f1;"></i>
                <input type="text" x-model="barcodeQuery" @keydown.enter="scanBarcode()"
                    x-ref="barcodeInput"
                    :disabled="isScanning"
                    placeholder="Scan / ketik barcode..."
                    class="form-input font-mono"
                    style="height:38px;font-size:12.5px;">
            </div>

            <!-- Search Filter -->
            <div class="form-input-icon sm:w-72">
                <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                <input type="text" x-model="searchQuery"
                    x-ref="searchInput"
                    placeholder="Cari SKU / Nama Rasa (F1)..."
                    class="form-input"
                    style="height:38px;font-size:12.5px;">
            </div>
        </div>

        <!-- FILTER CHIPS (Kategori Horizontal Scroll) & STORE ITEM TOGGLE -->
        <div class="category-filter-bar rounded-xl" style="border:1px solid var(--color-hairline);margin-bottom:8px;">
            <div x-ref="catTrack"
                 onwheel="if(Math.abs(event.deltaY) > Math.abs(event.deltaX)) { this.scrollLeft += event.deltaY; event.preventDefault(); }"
                 class="category-scroll-track">
                <button type="button"
                    @click="selectedGroup = 'all'"
                    class="category-pill"
                    :class="{ 'is-active': selectedGroup === 'all' }">
                    Semua
                </button>
                <?php foreach ($groups as $g): ?>
                <button type="button"
                    @click="selectedGroup = '<?= $g['id'] ?>'"
                    class="category-pill"
                    :class="{ 'is-active': selectedGroup === '<?= $g['id'] ?>' }">
                    <?= htmlspecialchars($g['nama_grup']) ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Toggle Item Khusus Toko -->
            <template x-if="hasCustomerAssignedItems">
                <div style="flex-shrink:0;">
                    <button type="button" @click="filterStoreOnly = !filterStoreOnly"
                            :class="filterStoreOnly ? 'pos-store-filter-active' : 'pos-store-filter-inactive'"
                            style="font-size:11.5px;padding:4px 12px;height:30px;white-space:nowrap;border-radius:20px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;transition:all 0.15s ease;">
                        <i data-lucide="check-circle-2" style="width:13px;height:13px;"></i>
                        <span x-text="filterStoreOnly ? 'Item Khusus Toko' : 'Semua Katalog'"></span>
                    </button>
                </div>
            </template>
        </div>

        <!-- GRID PRODUK (Mobile 2-Kolom, Desktop 3-Kolom) -->
        <div class="flex-1 overflow-visible lg:overflow-y-auto custom-scrollbar p-0 sm:p-2 lg:p-4 pb-28 lg:pb-4" style="background:transparent;">
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3 gap-2.5 sm:gap-3.5">
                <template x-for="item in filteredItems" :key="item.id">
                    <div @click="addItemToCart(item)"
                         class="product-card group relative"
                         :class="{ 'opacity-60': Number(item.stok_fisik_saat_ini || 0) <= 0 }">

                        <div class="product-card-head">
                            <div class="product-card-top-row">
                                <span class="product-card-sku" x-text="item.kode_sku"></span>
                                <span class="product-card-stock-pill"
                                      :class="{ 'is-empty': Number(item.stok_fisik_saat_ini || 0) <= 0 }"
                                      x-text="Number(item.stok_fisik_saat_ini || 0) <= 0 ? 'Habis' : (item.stok_fisik_saat_ini + ' ' + item.satuan_dasar)"></span>
                            </div>
                            <div class="product-card-name" x-text="item.nama_item"></div>
                            <div class="product-card-grup" x-text="item.nama_grup"></div>
                        </div>

                        <div class="product-card-foot">
                            <div class="product-card-price-row">
                                <span class="product-card-price" x-text="formatRupiah(item.harga_jual_satuan || 15000)"></span>
                                <span class="product-card-unit" x-text="'/ ' + item.satuan_dasar"></span>
                            </div>

                            <!-- Case 1: Stok Tersedia & Qty Belum Masuk Keranjang -->
                            <button type="button"
                                    x-show="Number(item.stok_fisik_saat_ini || 0) > 0 && getItemQty(item.id) === 0"
                                    class="btn-pos-add-full"
                                    @click.stop="addItemToCart(item)">
                                <svg width="14" height="14" style="width:14px;height:14px;min-width:14px;min-height:14px;flex-shrink:0;display:inline-block;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                <span>Tambah</span>
                            </button>

                            <!-- Case 2: Stok Tersedia & Qty Sudah di Keranjang (Interactive Full Stepper) -->
                            <div x-show="Number(item.stok_fisik_saat_ini || 0) > 0 && getItemQty(item.id) > 0"
                                 class="pos-stepper-full"
                                 @click.stop>
                                <button type="button" class="pos-stepper-btn" @click.stop="decreaseItemInCart(item)" aria-label="Kurang">−</button>
                                <span class="pos-stepper-val" x-text="getItemQty(item.id) + ' ' + item.satuan_dasar"></span>
                                <button type="button" class="pos-stepper-btn" @click.stop="addItemToCart(item)" aria-label="Tambah">+</button>
                            </div>

                            <!-- Case 3: Stok Habis -->
                            <div x-show="Number(item.stok_fisik_saat_ini || 0) <= 0"
                                 class="badge-out-of-stock-full">
                                Stok Habis
                            </div>
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
    <div class="pos-desktop-cart card">

        <div class="flex flex-col flex-1 min-h-0 p-5 gap-4">

            <!-- Pelanggan Kasir -->
            <div>
                <label class="form-label">Pelanggan Kasir (Ritel)</label>
                <select x-model="selectedCustomerId" disabled class="form-select font-bold" style="background-color: var(--color-canvas-soft); cursor: not-allowed; appearance: none; padding-right: 12px; opacity: 0.9;">
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>">
                        <?= htmlspecialchars($c['nama_toko']) ?> (<?= htmlspecialchars($c['grup_nama']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Cart Header -->
            <div class="section-header" style="margin-bottom:0;padding-bottom:10px;">
                <span class="section-title" style="display:flex;align-items:center;gap:8px;">
                    <i data-lucide="shopping-bag" style="width:16px;height:16px;color:#2563eb;"></i>
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
                                <div style="font-size:12.5px;font-weight:700;color:var(--color-ink);" x-text="item.nama_item"></div>
                                <div style="font-size:11.5px;font-family:var(--font-mono);color:var(--color-ink-mute);font-weight:600;margin-top:1px;" x-text="formatRupiah(item.price) + ' / ' + item.satuan"></div>
                            </div>
                            <button @click.stop="removeItem(index)" class="btn btn-ghost btn-sm" style="padding:4px 6px;color:var(--color-danger);border-radius:var(--rounded-xs);" title="Hapus item">
                                <i data-lucide="trash-2" style="width:15px;height:15px;color:var(--color-danger);"></i>
                            </button>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;padding-top:8px;border-top:1px solid var(--color-hairline);">
                            <div class="qty-counter">
                                <button type="button" @click.stop="updateQty(index, -1)" class="qty-btn" aria-label="Kurang">−</button>
                                <input type="number" min="1" x-model.number="item.qty_pcs" @input="recalculateItemSubtotal(index)" @change="recalculateItemSubtotal(index)" class="qty-input">
                                <button type="button" @click.stop="updateQty(index, 1)" class="qty-btn" aria-label="Tambah">+</button>
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
                <span style="font-size:22px;font-weight:900;font-family:var(--font-mono);color:var(--color-ink);letter-spacing:-0.03em;" x-text="formatRupiah(grandTotal)"></span>
            </div>
            <button @click="openPaymentModal()" :disabled="cart.length === 0"
                    :class="cart.length > 0 ? 'btn btn-primary' : 'btn btn-secondary opacity-50 cursor-not-allowed'"
                    class="btn-full btn-lg" style="justify-content:center;height:44px;font-weight:700;border-radius:8px;">
                <i data-lucide="credit-card"></i>
                Bayar Sekarang (F4)
            </button>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MOBILE BOTTOM BAR                                                      -->
    <!-- ====================================================================== -->
    <div class="mobile-bottom-bar" @click="showMobileCartDrawer = true">
        <div style="flex:1;cursor:pointer;">
            <div style="font-size:11.5px;font-weight:600;color:var(--color-ink-mute);">
                <span style="font-weight:800;color:var(--color-ink);" x-text="totalPcsCount"></span> Item di Keranjang
            </div>
            <div style="font-size:17px;font-weight:900;font-family:var(--font-mono);color:var(--color-ink);" x-text="formatRupiah(grandTotal)"></div>
        </div>
        <button type="button"
                @click.stop="showMobileCartDrawer = true"
                class="btn btn-primary"
                style="flex-shrink:0;padding:8px 14px;border-radius:10px;display:flex;align-items:center;gap:6px;">
            <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;min-height:16px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span x-text="cart.length > 0 ? 'Keranjang (' + cart.length + ')' : 'Keranjang'"></span>
        </button>
    </div>

    <!-- MOBILE CART DRAWER -->
    <div id="mobile-cart-drawer"
         x-show="showMobileCartDrawer"
         x-cloak
         class="mobile-cart-drawer-overlay"
         @click.self="showMobileCartDrawer = false">
        <div class="cart-drawer" @click.stop>

            <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid var(--color-hairline);">
                <span class="section-title" style="display:flex;align-items:center;gap:8px;">
                    <i data-lucide="shopping-bag" style="width:16px;height:16px;color:var(--color-primary-deep);"></i>
                    Keranjang Kasir
                </span>
                <button type="button"
                        @click="showMobileCartDrawer = false"
                        class="btn btn-ghost btn-sm"
                        style="padding:6px;min-width:38px;min-height:38px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:var(--color-canvas-soft);cursor:pointer;"
                        aria-label="Tutup Keranjang">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <!-- Pelanggan Mobile -->
            <div>
                <label class="form-label">Toko Pelanggan</label>
                <select x-model="selectedCustomerId" disabled class="form-select font-semibold" style="background-color: var(--color-canvas-soft); cursor: not-allowed; appearance: none; padding-right: 12px; opacity: 0.9;">
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
                            <button @click.stop="removeItem(index)" class="btn btn-ghost btn-sm" style="padding:4px 6px;color:var(--color-danger);border-radius:var(--rounded-xs);" title="Hapus item">
                                <i data-lucide="trash-2" style="width:15px;height:15px;color:var(--color-danger);"></i>
                            </button>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding-top:8px;border-top:1px solid var(--color-hairline);">
                            <div class="qty-counter">
                                <button type="button" @click.stop="updateQty(index, -1)" class="qty-btn" aria-label="Kurang">−</button>
                                <input type="number" min="1" x-model.number="item.qty_pcs" @input="recalculateItemSubtotal(index)" @change="recalculateItemSubtotal(index)" class="qty-input">
                                <button type="button" @click.stop="updateQty(index, 1)" class="qty-btn" aria-label="Tambah">+</button>
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
                    <strong style="font-size:22px;font-weight:900;font-family:var(--font-mono);color:var(--color-ink);" x-text="formatRupiah(grandTotal)"></strong>
                </div>
                <button type="button"
                        @click="showMobileCartDrawer = false; openPaymentModal();"
                        :disabled="cart.length === 0"
                        class="btn btn-primary btn-full btn-lg" style="justify-content:center;">
                    Lanjut ke Pembayaran
                </button>
            </div>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MODAL 1: DISAMBIGUASI BARCODE                                          -->
    <!-- ====================================================================== -->
    <div x-show="showBarcodeModal" x-cloak class="modal-backdrop" style="display:none;">
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
<div style="font-size:12px;font-family:var(--font-mono);font-weight:800;color:var(--color-primary-deep);" x-text="'[' + (i+1) + ']'"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MODAL 2: PEMBAYARAN KASIR POS (OFFICIAL MATERIAL DESIGN 3 DIALOG)       -->
    <!-- ====================================================================== -->
    <div x-show="showPaymentModal" x-cloak class="modal-backdrop" @click.self="showPaymentModal = false" style="display:none;">
        <div class="m3-dialog" @click.stop>
            
            <!-- M3 Dialog Header -->
            <div class="m3-dialog-header">
                <div class="m3-dialog-icon">
                    <i data-lucide="wallet" style="width: 22px; height: 22px;"></i>
                </div>
                <div class="m3-dialog-title-group">
                    <h2 class="m3-dialog-title">Pembayaran Transaksi</h2>
                    <p class="m3-dialog-subtitle">Kasir Ritel Keren Snack</p>
                </div>
                <button type="button" @click="showPaymentModal = false" class="m3-icon-btn" aria-label="Tutup">
                    <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                </button>
            </div>

            <!-- M3 Total Headline Display (Tonal Hero Card) -->
            <div class="m3-hero-total">
                <div>
                    <div class="m3-hero-total-label">Total Tagihan Netto</div>
                    <div class="m3-hero-total-amount" x-text="formatRupiah(grandTotal)"></div>
                </div>
                <div class="m3-hero-total-badge" x-text="cartTotalQty + ' Pcs Item'"></div>
            </div>

            <!-- M3 Segmented Button Group (Payment Method Selection) -->
            <div class="m3-section">
                <div class="m3-section-label">Metode Pembayaran</div>
                <div class="m3-segmented-group">
                    <!-- Cash Button -->
                    <button type="button" 
                            @click="paymentType = 'cash'; $nextTick(() => { $refs.paidInput?.focus(); $refs.paidInput?.select(); if (typeof lucide !== 'undefined') lucide.createIcons(); })"
                            class="m3-segment-btn"
                            :class="{ 'is-selected': paymentType === 'cash' }">
                        <i data-lucide="banknote" style="width: 17px; height: 17px;"></i>
                        <span>Tunai (Cash)</span>
                    </button>
                    <!-- QRIS Button -->
                    <button type="button" 
                            @click="paymentType = 'qris'; $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
                            class="m3-segment-btn"
                            :class="{ 'is-selected': paymentType === 'qris' }">
                        <i data-lucide="qr-code" style="width: 17px; height: 17px;"></i>
                        <span>QRIS Toko</span>
                    </button>
                </div>
            </div>

            <!-- M3 Content Area (Conditional by Payment Type) -->
            <!-- 1. Cash Payment Area -->
            <div x-show="paymentType === 'cash'" class="m3-cash-area">
                
                <!-- M3 Outlined Text Field -->
                <div class="m3-text-field">
                    <div class="m3-text-field-header">
                        <label class="m3-field-label">Nominal Uang Diterima</label>
                        <button type="button" @click="setExactCash()" class="m3-text-action-btn">
                            <i data-lucide="sparkles" style="width: 13px; height: 13px;"></i>
                            <span>Uang Pas</span>
                        </button>
                    </div>
                    <div class="m3-input-container">
                        <span class="m3-input-prefix">Rp</span>
                        <input type="text" x-ref="paidInput" x-model="paidAmountDisplay" @input="onPaidInput($event)"
                               class="m3-input-text font-mono" placeholder="0">
                    </div>
                </div>

                <!-- M3 Suggestion Chips Row -->
                <div class="m3-chips-row">
                    <button type="button" @click="setQuickCash(10000)" class="m3-chip">10.000</button>
                    <button type="button" @click="setQuickCash(20000)" class="m3-chip">20.000</button>
                    <button type="button" @click="setQuickCash(50000)" class="m3-chip">50.000</button>
                    <button type="button" @click="setQuickCash(100000)" class="m3-chip">100.000</button>
                    <button type="button" @click="setQuickCash(200000)" class="m3-chip">200.000</button>
                </div>

                <!-- M3 Kembalian Status Card -->
                <div class="m3-status-banner"
                     :class="paidAmount >= grandTotal ? 'is-success' : 'is-error'">
                    <div class="m3-status-banner-left">
                        <i :data-lucide="paidAmount >= grandTotal ? 'check-circle' : 'alert-circle'" style="width: 18px; height: 18px;"></i>
                        <span x-text="paidAmount >= grandTotal ? 'Kembalian:' : 'Uang Kurang:'"></span>
                    </div>
                    <div class="m3-status-banner-amount font-mono"
                         x-text="paidAmount >= grandTotal ? formatRupiah(paidAmount - grandTotal) : 'Kurang ' + formatRupiah(grandTotal - paidAmount)"></div>
                </div>
            </div>

            <!-- 2. QRIS Payment Area -->
            <div x-show="paymentType === 'qris'" class="m3-qris-area">
                <div class="m3-qris-banner">
                    <div class="m3-qris-icon-box">
                        <i data-lucide="qr-code" style="width: 22px; height: 22px;"></i>
                    </div>
                    <div class="m3-qris-text">
                        <div class="m3-qris-title">Pembayaran QRIS Otomatis</div>
                        <div class="m3-qris-desc">
                            Dana sebesar <strong style="color: var(--color-ink);" x-text="formatRupiah(grandTotal)"></strong> langsung dialokasikan ke rekening <strong>Kantong Kas QRIS</strong> tanpa uang kembalian.
                        </div>
                    </div>
                </div>
            </div>

            <!-- M3 Dialog Actions Footer -->
            <div class="m3-dialog-actions">
                <button type="button" @click="showPaymentModal = false" class="m3-btn-outlined">
                    Batal
                </button>
                <button type="button" @click="submitCheckout()"
                        :disabled="isSubmitting || (paymentType === 'cash' && paidAmount < grandTotal)"
                        class="m3-btn-filled">
                    <i data-lucide="check" style="width: 18px; height: 18px;"></i>
                    <span x-show="!isSubmitting">Selesaikan Transaksi</span>
                    <span x-show="isSubmitting">Memproses...</span>
                </button>
            </div>

        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MODAL 3: STRUK NOTA PEMBAYARAN & CETAK THERMAL                         -->
    <!-- ====================================================================== -->
    <div x-show="showReceiptModal" x-cloak class="modal-backdrop" style="display:none;">
        <div @click.away="showReceiptModal = false" class="modal-box" style="max-width:420px;padding:20px;">
            <div class="modal-header" style="padding-bottom:12px;margin-bottom:12px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:28px;height:28px;border-radius:50%;background:rgba(62,207,142,0.15);color:var(--color-primary);display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="check" style="width:16px;height:16px;"></i>
                    </div>
                    <div>
                        <div class="modal-title" style="font-size:15px;">Transaksi Sukses!</div>
                        <div style="font-size:11.5px;color:var(--color-ink-mute);" x-text="receiptData?.nomor_nota"></div>
                    </div>
                </div>
                <button @click="showReceiptModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <!-- TAMPILAN FISIK STRUK NOTA (THERMAL PREVIEW) -->
            <div id="printable-receipt" class="receipt-paper" style="background:#ffffff;color:#111827;padding:16px;border-radius:8px;font-family:'JetBrains Mono', monospace;font-size:11px;line-height:1.4;box-shadow:0 2px 8px rgba(0,0,0,0.15);margin-bottom:16px;max-height:360px;overflow-y:auto;">
                <div style="text-align:center;margin-bottom:10px;">
                    <div style="font-size:14px;font-weight:800;letter-spacing:0.5px;" x-text="receiptData?.store?.nama || 'KEREN SNACK'"></div>
                    <div style="font-size:9.5px;color:#4b5563;" x-text="receiptData?.store?.alamat || 'Distribusi Makanan Ringan'"></div>
                    <div style="font-size:9.5px;color:#4b5563;" x-text="receiptData?.store?.kontak || ''"></div>
                </div>

                <div style="border-top:1px dashed #9ca3af;margin:8px 0;"></div>

                <div style="display:flex;justify-content:space-between;font-size:10px;">
                    <span>No: <strong x-text="receiptData?.nomor_nota"></strong></span>
                    <span x-text="receiptData?.tanggal"></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:10px;">
                    <span>Kasir: <span x-text="receiptData?.cashier_name"></span></span>
                    <span>Tipe: <strong style="text-transform:uppercase;" x-text="receiptData?.payment_type === 'qris' ? 'QRIS' : 'TUNAI'"></strong></span>
                </div>
                <div style="font-size:9.5px;color:#4b5563;margin-top:2px;">
                    <span>Kas Masuk: <span x-text="receiptData?.akun_kas_nama || (receiptData?.payment_type === 'qris' ? 'Kantong Kas QRIS' : 'Kasir Utama Toko')"></span></span>
                </div>
                <div style="font-size:10px;margin-top:2px;">
                    <span>Toko: <strong x-text="receiptData?.customer_name"></strong> (<span x-text="receiptData?.customer_group"></span>)</span>
                </div>

                <div style="border-top:1px dashed #9ca3af;margin:8px 0;"></div>

                <!-- ITEMS -->
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <template x-for="it in (receiptData?.items || [])" :key="it.item_id">
                        <div>
                            <div style="font-weight:700;font-size:10.5px;" x-text="it.nama_item"></div>
                            <div style="display:flex;justify-content:space-between;font-size:10px;color:#374151;">
                                <span x-text="(it.qty_bal > 0 ? (it.qty_bal + ' bal ') : '') + (it.qty_pcs > 0 ? (it.qty_pcs + ' pcs') : '') + ' @ ' + formatRupiah(it.harga)"></span>
                                <span style="font-weight:700;" x-text="formatRupiah(it.subtotal)"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <div style="border-top:1px dashed #9ca3af;margin:8px 0;"></div>

                <!-- TOTALS -->
                <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:800;margin-top:4px;">
                    <span>TOTAL NETTO:</span>
                    <span style="font-size:12px;" x-text="formatRupiah(receiptData?.total_netto)"></span>
                </div>

                <template x-if="receiptData?.payment_type === 'cash'">
                    <div style="font-size:10px;color:#374151;margin-top:4px;">
                        <div style="display:flex;justify-content:space-between;">
                            <span>TUNAI DITERIMA:</span>
                            <span x-text="formatRupiah(receiptData?.paid_amount || receiptData?.total_netto)"></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span>KEMBALIAN:</span>
                            <span style="font-weight:700;" x-text="formatRupiah(Math.max(0, (receiptData?.paid_amount || receiptData?.total_netto) - receiptData?.total_netto))"></span>
                        </div>
                    </div>
                </template>

                <div style="border-top:1px dashed #9ca3af;margin:10px 0 6px 0;"></div>
                <div style="text-align:center;font-size:9.5px;color:#6b7280;">
                    <div>Terima Kasih Atas Kunjungan Anda!</div>
                    <div>Barang yang sudah dibeli tidak dapat ditukar</div>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div style="display:flex;gap:8px;">
                <button @click="printReceipt()" class="btn btn-primary btn-full" style="justify-content:center;height:40px;">
                    <i data-lucide="printer"></i>
                    <span>Cetak Struk [Enter]</span>
                </button>
                <button @click="resetForNewTransaction()" class="btn btn-secondary" style="justify-content:center;height:40px;white-space:nowrap;">
                    <i data-lucide="plus"></i>
                    <span>Transaksi Baru [F2]</span>
                </button>
            </div>
        </div>
    </div>

</div>

<!-- CSS PRINT STYLES FOR THERMAL PRINTER (58mm / 80mm) -->
<style>
@media print {
    /* Hide everything in page except the receipt */
    body * {
        visibility: hidden !important;
    }
    #printable-receipt, #printable-receipt * {
        visibility: visible !important;
    }
    #printable-receipt {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 72mm !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 4mm !important;
        box-shadow: none !important;
        font-family: 'Courier New', Courier, monospace !important;
        font-size: 10pt !important;
        color: #000000 !important;
        background: #ffffff !important;
        z-index: 9999999 !important;
    }
}
</style>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

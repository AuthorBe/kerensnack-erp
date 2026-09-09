<?php
use App\Core\Router;
use App\Core\Auth;
use App\Helpers\Format;
ob_start();
$isRetryEdit = !empty($isRetryEdit) || ($order['status_pemrosesan'] ?? '') === 'gagal_dikirim';
$initialItemDiskon = array_reduce($existingItems ?? [], function($sum, $it) {
    return $sum + (float)($it['diskon'] ?? 0);
}, 0);
$initialDiskonFaktur = max(0, (float)($order['total_diskon'] ?? 0) - $initialItemDiskon);
?>

<div class="space-y-5" x-data="editSalesOrderApp()">

    <!-- ========================================================================= -->
    <!-- 1. TOP ACTION BAR                                                         -->
    <!-- ========================================================================= -->
    <div class="card p-4 sm:p-5 rounded-2xl border border-hairline shadow-sm" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:14px;min-width:0;">
            <a href="<?= Router::url('/customer-orders') ?>" class="page-back-btn" style="flex-shrink:0;" title="Kembali ke Daftar Penjualan">
                <i data-lucide="arrow-left"></i>
            </a>
            <div class="page-header-icon <?= $isRetryEdit ? 'is-blue' : 'is-indigo' ?>" style="flex-shrink:0;">
                <i data-lucide="<?= $isRetryEdit ? 'rotate-cw' : 'edit-3' ?>"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag" <?= $isRetryEdit ? 'style="background:rgba(37,99,235,0.1);color:#1d4ed8;border-color:rgba(37,99,235,0.25);"' : '' ?>>
                    <span class="tag-dot" <?= $isRetryEdit ? 'style="background:#2563eb;"' : '' ?>></span>
                    <span><?= $isRetryEdit ? 'Kirim Ulang Pesanan' : 'Edit Faktur Pesanan' ?></span>
                </div>
                <h1 class="page-title" style="margin:0;font-size:1.35rem;font-weight:800;"><?= htmlspecialchars($pageTitle ?? 'Edit Pesanan Pelanggan') ?></h1>
                <p class="page-subtitle" style="margin:3px 0 0 0;font-size:12.5px;"><?= htmlspecialchars($pageSubtitle ?? 'Perbarui rincian produk, kuantiti, dan skema harga pesanan') ?></p>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
            <button type="button" @click="showGuideModal = true" class="btn btn-secondary btn-sm flex items-center gap-2" style="border-radius:10px;height:38px;padding:0 14px;font-weight:700;font-size:12.5px;" title="Petunjuk Alur Operasional Pesanan">
                <i data-lucide="book-open" style="width:16px;height:16px;color:var(--color-primary);"></i>
                <span>Petunjuk Alur</span>
            </button>
            <button type="button" @click="submitOrder()" :disabled="isSubmitting || items.length === 0" class="btn btn-primary btn-sm flex items-center gap-2" style="border-radius:10px;height:38px;padding:0 16px;font-weight:700;font-size:12.5px;<?= $isRetryEdit ? 'background:#1e3a8a;border-color:#1e3a8a;' : '' ?>">
                <i data-lucide="<?= $isRetryEdit ? 'rotate-cw' : 'save' ?>" style="width:15px;height:15px;"></i>
                <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan'"></span>
            </button>
        </div>
    </div>

    <!-- Banner Mode Kirim Ulang Khusus Pesanan Gagal -->
    <?php if ($isRetryEdit): ?>
    <div class="card" style="background:rgba(37,99,235,0.06);border:1.5px solid rgba(37,99,235,0.25);border-radius:16px;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:40px;height:40px;border-radius:12px;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-lucide="rotate-cw" style="width:20px;height:20px;"></i>
            </div>
            <div>
                <div style="font-size:14px;font-weight:800;color:#1e3a8a;">Mode Kirim Ulang Pesanan Gagal</div>
                <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;line-height:1.45;">
                    Anda sedang mengedit rincian item pesanan yang sebelumnya gagal dikirim. Setelah disimpan, pesanan ini akan masuk kembali ke antrean <strong>Daftar PO Gudang</strong> untuk disiapkan dan dikirim ulang.
                </div>
            </div>
        </div>
        <span class="badge" style="background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;font-weight:800;padding:6px 12px;border-radius:8px;font-size:12px;white-space:nowrap;">
            🔁 Kirim Ulang
        </span>
    </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- MAIN FORM                                                                 -->
    <!-- ========================================================================= -->
    <form id="salesOrderForm" data-add-row-btn="#btnAddRow" data-action-text="<?= $isRetryEdit ? 'Menjadwalkan Kirim Ulang...' : 'Menyimpan Perubahan...' ?>" action="<?= Router::url('/customer-orders/update') ?>" method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($order['id']) ?>">
        <input type="hidden" name="items_json" :value="JSON.stringify(items)">
        <input type="hidden" name="refund_akun_kas_id" :value="refundForm.akun_kas_id">
        <input type="hidden" name="diskon_faktur" :value="header.diskon_faktur">
        <input type="hidden" name="nominal_dibayar" :value="header.nominal_dibayar">

        <div class="space-y-5">

            <!-- ===================================================================== -->
            <!-- 2. INFORMASI TOKO MITRA & TRANSAKSI (2-ROW MINIMALIST CARD)           -->
            <!-- ===================================================================== -->
            <div class="card" style="padding:22px 24px;display:flex;flex-direction:column;gap:18px;">
                <!-- Header Strip -->
                <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px;padding-bottom:14px;border-bottom:1px solid var(--color-hairline);margin-bottom:2px;">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                        <div style="width:36px;height:36px;border-radius:10px;background:rgba(99,102,241,0.12);color:#818cf8;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="store" style="width:18px;height:18px;"></i>
                        </div>
                        <div>
                            <div style="font-size:14px;font-weight:800;color:var(--color-ink);line-height:1.3;">Informasi Toko Mitra &amp; Transaksi</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Data toko mitra terdaftar dan tanggal penerbitan transaksi faktur</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span class="badge" style="display:inline-flex;align-items:center;gap:6px;background:rgba(99,102,241,0.1);color:#818cf8;border:1px solid rgba(99,102,241,0.25);font-weight:800;font-size:11.5px;padding:4px 10px;border-radius:999px;">
                            <i data-lucide="tag" style="width:13px;height:13px;"></i> Level <?= htmlspecialchars($order['level_harga']) ?> (<?= htmlspecialchars($order['nama_grup_harga'] ?? 'Standar') ?>)
                        </span>
                        <template x-if="isKonsinyasi">
                            <span style="background:rgba(225,29,72,0.1);color:#f43f5e;border:1px solid rgba(225,29,72,0.25);padding:4px 10px;border-radius:999px;font-weight:800;font-size:11px;text-transform:uppercase;">
                                Titip Jual (Konsinyasi)
                            </span>
                        </template>
                        <template x-if="!isKonsinyasi">
                            <span style="background:rgba(16,185,129,0.1);color:#34d399;border:1px solid rgba(16,185,129,0.25);padding:4px 10px;border-radius:999px;font-weight:800;font-size:11px;text-transform:uppercase;">
                                Penjualan Reguler
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Form Grid (Tepat 2 Baris Simetris) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- BARIS 1 KIRI: No. Faktur (Auto) -->
                    <div>
                        <label class="form-label flex items-center justify-between" style="font-size:12px;font-weight:700;margin-bottom:6px;">
                            <span>No. Transaksi / Faktur</span>
                            <span style="font-size:10.5px;color:var(--color-ink-mute);display:flex;align-items:center;gap:3px;"><i data-lucide="lock" style="width:11px;height:11px;"></i> Terkunci</span>
                        </label>
                        <input type="text" readonly value="<?= htmlspecialchars($order['nomor_nota']) ?>" class="form-input font-mono font-bold" style="height:42px;border-radius:10px;background:var(--color-canvas-soft);cursor:not-allowed;color:var(--color-ink);letter-spacing:0.02em;">
                    </div>

                    <!-- BARIS 1 KANAN: Tanggal Transaksi -->
                    <div>
                        <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Tanggal Transaksi *</label>
                        <input type="date" name="tanggal_pesanan" x-model="header.tanggal_pesanan" @change="onTanggalPesananChange()" required class="form-input font-mono font-semibold" style="height:42px;border-radius:10px;">
                    </div>

                    <!-- BARIS 2 KIRI: Toko Pelanggan -->
                    <div>
                        <label class="form-label flex items-center justify-between" style="font-size:12px;font-weight:700;margin-bottom:6px;">
                            <span>Toko Pelanggan</span>
                            <template x-if="hasWhitelist">
                                <button type="button" @click="showAllProducts = !showAllProducts" class="text-xs font-bold text-primary flex items-center gap-1 hover:underline" style="font-size:11px;color:#0284c7;">
                                    <i data-lucide="filter" style="width:11px;height:11px;"></i>
                                    <span x-text="showAllProducts ? 'Saring Whitelist Toko' : 'Buka Semua <?= count($products) ?> SKU'"></span>
                                </button>
                            </template>
                        </label>
                        <div class="form-input font-bold flex items-center justify-between w-full"
                             style="height:42px;border-radius:10px;font-size:13.5px;background:var(--color-canvas-soft);cursor:not-allowed;color:var(--color-ink);">
                            <span class="truncate"><?= htmlspecialchars($order['nama_toko']) ?> (<?= htmlspecialchars($order['kode_pelanggan']) ?> - Lvl <?= htmlspecialchars($order['level_harga']) ?>)</span>
                            <span style="font-size:10.5px;color:var(--color-ink-mute);display:flex;align-items:center;gap:3px;flex-shrink:0;">
                                <i data-lucide="lock" style="width:12px;height:12px;"></i> Terkunci
                            </span>
                        </div>
                    </div>

                    <!-- BARIS 2 KANAN: Catatan / Keterangan Nota Opsional -->
                    <div>
                        <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Catatan / Keterangan Nota <span class="font-normal text-xs" style="color:var(--color-ink-mute);">(Opsional)</span></label>
                        <input type="text" name="catatan" x-model="header.catatan"
                               @keydown.enter.prevent="focusFirstProduct()"
                               class="form-input enter-nav"
                               style="height:42px;border-radius:10px;font-size:13px;"
                               placeholder="Contoh: Titip faktur ke kasir...">
                    </div>
                </div>
            </div>

            <!-- ===================================================================== -->
            <!-- 3. TABEL BARIS PRODUK BARANG JADI (CRISP & BALANCED)                  -->
            <!-- ===================================================================== -->
            <div class="card p-0">
                <div class="product-card-header">
                    <div class="product-card-top-bar">
                        <div class="product-card-title-group">
                            <div class="product-card-icon">
                                <i data-lucide="package" style="width:16px;height:16px;"></i>
                            </div>
                            <div class="product-card-text-col">
                                <span class="product-card-heading">Rincian Produk Snack</span>
                                <span class="product-card-subheading hidden sm:inline">(Bungkus)</span>
                            </div>
                        </div>
                        <span class="badge badge-mono product-card-badge" x-text="items.length + ' Baris'"></span>
                    </div>
                    <button type="button" id="btnAddRow" @click="addItemRow()"
                            class="btn btn-primary btn-sm btn-add-row-snack">
                        <i data-lucide="plus" style="width:15px;height:15px;"></i>
                        <span>Tambah Baris Snack</span>
                    </button>
                </div>

<style>
/* Responsive Table and Mobile Card Layout */
.product-card-header {
    padding: 14px 18px;
    border-bottom: 1px solid var(--color-hairline);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: var(--color-canvas-soft);
    border-top-left-radius: var(--rounded-xl);
    border-top-right-radius: var(--rounded-xl);
}
.product-card-top-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.product-card-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.product-card-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(59,130,246,0.12);
    color: #60a5fa;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.product-card-text-col {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
}
.product-card-heading {
    font-weight: 800;
    font-size: 14px;
    color: var(--color-ink);
    white-space: nowrap;
}
.product-card-subheading {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--color-ink-mute);
    white-space: nowrap;
}
.product-card-badge {
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
}
.btn-add-row-snack {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    flex-shrink: 0;
}

@media (max-width: 640px) {
    .product-card-header {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        padding: 12px 14px;
    }
    .product-card-top-bar {
        width: 100%;
        justify-content: space-between;
    }
    .btn-add-row-snack {
        width: 100%;
        justify-content: center;
        height: 38px;
        font-size: 13px;
    }
}

.table-scroll-order {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 768px) {
    .responsive-order-table {
        display: block;
        width: 100% !important;
        min-width: 0 !important;
    }
    .responsive-order-table thead {
        display: none !important;
    }
    .responsive-order-table tbody {
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding: 12px;
        width: 100%;
    }
    .responsive-order-table tr.order-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        background: var(--color-card, #ffffff);
        border: 1px solid var(--color-hairline);
        border-radius: 14px;
        padding: 14px;
        box-shadow: 0 2px 8px -2px rgba(0,0,0,0.06);
        width: 100%;
    }
    .responsive-order-table tr.order-row td {
        display: block;
        padding: 0 !important;
        border: none !important;
        text-align: left;
        width: 100%;
    }
    .responsive-order-table tr.order-row td.col-no {
        grid-column: 1 / -1;
    }
    .responsive-order-table tr.order-row td.col-product {
        grid-column: 1 / -1;
    }
    .responsive-order-table tr.order-row td.col-stock {
        grid-column: span 1;
    }
    .responsive-order-table tr.order-row td.col-qty {
        grid-column: span 1;
    }
    .responsive-order-table tr.order-row td.col-price {
        grid-column: span 1;
    }
    .responsive-order-table tr.order-row td.col-discount {
        grid-column: span 1;
    }
    .responsive-order-table tr.order-row td.col-subtotal {
        grid-column: 1 / -1;
    }
    .responsive-order-table tr.order-row td.col-action {
        display: none !important;
    }
    .mobile-field-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: var(--color-ink-mute);
        margin-bottom: 4px;
    }
    .mobile-row-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 10px;
        margin-bottom: 4px;
        border-bottom: 1px dashed var(--color-hairline);
        width: 100%;
    }
    .desktop-only-cell {
        display: none !important;
    }
}

@media (min-width: 769px) {
    .mobile-only-block {
        display: none !important;
    }
    .mobile-field-label {
        display: none !important;
    }
    .responsive-order-table {
        min-width: 860px;
        width: 100%;
    }
}
</style>

                <div class="table-scroll-order no-scrollbar">
                    <table class="table responsive-order-table">
                        <thead>
                            <tr>
                                <th class="cell-center" style="width:40px;">No</th>
                                <th style="min-width:260px;">Produk Snack Siap Jual (<?= count($products) ?> SKU)</th>
                                <th class="cell-center cell-nowrap" style="width:105px;">Stok Gudang</th>
                                <th class="cell-center cell-nowrap" style="width:90px;">Qty (Bks)</th>
                                <th class="cell-right cell-nowrap" style="width:130px;" x-show="!isKonsinyasi">Harga Satuan</th>
                                <th class="cell-right cell-nowrap" style="width:115px;" x-show="!isKonsinyasi">Diskon (Rp)</th>
                                <th class="cell-right cell-nowrap" style="width:135px;" x-show="!isKonsinyasi">Subtotal (Rp)</th>
                                <th class="cell-center" style="width:46px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Baris Produk -->
                            <template x-for="(row, idx) in items" :key="row.uid">
                                <tr :class="'row-uid-' + row.uid" class="order-row">
                                    <!-- No & Mobile Header -->
                                    <td class="cell-center cell-nowrap col-no">
                                        <span class="desktop-only-cell" style="color:var(--color-ink-mute);font-size:12px;font-weight:700;" x-text="idx + 1"></span>
                                        <div class="mobile-only-block mobile-row-header">
                                            <div class="flex items-center gap-2">
                                                <span class="badge badge-mono font-bold" style="font-size:11px;" x-text="'Item #' + (idx + 1)"></span>
                                            </div>
                                            <button type="button" @click="removeItemRow(idx)" class="btn btn-ghost btn-xs text-danger" style="padding:4px 8px;border-radius:6px;gap:4px;" title="Hapus baris ini">
                                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                                <span style="font-size:11.5px;font-weight:700;">Hapus</span>
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Produk Dropdown Button -->
                                    <td class="col-product" data-label="Produk Snack">
                                        <label class="mobile-field-label">Produk Snack *</label>
                                        <button type="button" @click="toggleProductDropdown(row, $event)"
                                                data-nav="product"
                                                :class="'prod-trigger-' + row.uid"
                                                @keydown.enter.stop.prevent="openProductDropdown(row, $event)"
                                                @keydown.space.stop.prevent="openProductDropdown(row, $event)"
                                                @keydown.down.stop.prevent="openProductDropdown(row, $event)"
                                                class="form-input enter-nav flex items-center justify-between w-full text-left"
                                                style="height:38px;font-size:13px;font-weight:600;border-radius:8px;cursor:pointer;background:var(--color-canvas);padding:0 12px;">
                                            <span class="truncate" :style="!row.item_id ? 'color:var(--color-ink-mute);font-weight:500;' : 'color:var(--color-ink);'"
                                                  x-text="getSelectedProductName(row.item_id)"></span>
                                            <i data-lucide="chevron-down" style="width:15px;height:15px;flex-shrink:0;transition:transform 0.2s;" :style="activeDropdownRow === row ? 'transform:rotate(180deg)' : ''"></i>
                                        </button>
                                    </td>

                                    <!-- Stok Gudang -->
                                    <td class="cell-center cell-nowrap col-stock" data-label="Stok Gudang">
                                        <label class="mobile-field-label">Stok Gudang</label>
                                        <span class="badge" :class="Number(row.stok_tersedia) > 0 ? 'badge-mono' : 'badge-warning'" style="font-family:var(--font-mono);font-size:11.5px;font-weight:700;" x-text="formatNumber(row.stok_tersedia) + ' bks'"></span>
                                    </td>

                                    <!-- Qty (Bungkus) -->
                                    <td class="cell-center cell-nowrap col-qty" data-label="Qty (Bks)">
                                        <label class="mobile-field-label">Qty (Bks) *</label>
                                        <input type="number" min="1" :disabled="!row.item_id"
                                               x-model.number="row.qty"
                                               @input="calcRow(row)"
                                               @focus="$event.target.select()"
                                               @click="$event.target.select()"
                                               data-nav="qty"
                                               class="form-input font-bold text-center enter-nav"
                                               style="height:38px;width:80px;font-size:13.5px;"
                                               placeholder="1"
                                               @keydown.enter.prevent="onQtyEnter(row, idx)"
                                               @keydown.down.prevent="moveRowVertical(idx, 1, 'qty')"
                                               @keydown.up.prevent="moveRowVertical(idx, -1, 'qty')">
                                    </td>

                                    <!-- Harga Satuan Level (Locked Box) -->
                                    <td class="cell-right cell-nowrap col-price" x-show="!isKonsinyasi" data-label="Harga Satuan">
                                        <label class="mobile-field-label">Harga Satuan</label>
                                        <div class="inline-flex items-center justify-end font-mono font-bold"
                                             style="height:38px;padding:0 10px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:8px;font-size:12.5px;color:var(--color-ink);min-width:105px;width:100%;max-width:130px;"
                                             x-text="row.item_id ? formatRupiah(row.harga) : '-'"
                                             title="Harga satuan deal otomatis berdasarkan level harga toko">
                                        </div>
                                    </td>

                                    <!-- Diskon Item -->
                                    <td class="cell-right cell-nowrap col-discount" x-show="!isKonsinyasi" data-label="Diskon (Rp)">
                                        <label class="mobile-field-label">Diskon Item (Rp)</label>
                                        <input type="number" step="any" min="0" :disabled="!row.item_id"
                                               x-model.number="row.diskon"
                                               @input="calcRow(row)"
                                               @focus="$event.target.select()"
                                               @click="$event.target.select()"
                                               data-nav="diskon"
                                               class="form-input font-mono text-right enter-nav no-spinner"
                                               style="height:38px;width:105px;font-size:12.5px;"
                                               placeholder="0"
                                               @keydown.enter.prevent="onDiskonEnter(row, idx)"
                                               @keydown.down.prevent="moveRowVertical(idx, 1, 'diskon')"
                                               @keydown.up.prevent="moveRowVertical(idx, -1, 'diskon')">
                                    </td>

                                    <!-- Subtotal -->
                                    <td class="cell-right cell-nowrap col-subtotal" x-show="!isKonsinyasi" data-label="Subtotal (Rp)">
                                        <label class="mobile-field-label">Subtotal</label>
                                        <div class="inline-flex items-center justify-end font-mono font-black"
                                             style="height:38px;padding:0 8px;font-size:14px;color:#1e3a8a;min-width:110px;"
                                             x-text="row.item_id ? formatRupiah(row.subtotal) : 'Rp 0'">
                                        </div>
                                    </td>

                                    <!-- Hapus Baris -->
                                    <td class="cell-center cell-nowrap col-action desktop-only-cell" data-label="Hapus">
                                        <button type="button" @click="removeItemRow(idx)" class="btn btn-ghost btn-sm text-danger" style="padding:6px;" title="Hapus baris ini (Ctrl+Del)">
                                            <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
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

            <!-- Single Floating Product Dropdown Portal -->
            <div x-show="activeDropdownRow !== null" x-cloak
                 @click.outside="closeProductDropdown()"
                 class="dropdown-menu-searchable"
                 :style="`position:fixed;top:${dropdownCoords.top}px;left:${dropdownCoords.left}px;width:${dropdownCoords.width}px;z-index:99999;border-radius:12px;overflow:hidden;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:0 16px 36px -4px rgba(0,0,0,0.22);`">
                <!-- Search Input Box -->
                <div style="padding:8px 10px;border-bottom:1px solid var(--color-hairline);background:var(--color-canvas-soft);">
                    <div style="position:relative;display:flex;align-items:center;">
                        <i data-lucide="search" style="position:absolute;left:10px;width:13px;height:13px;color:var(--color-ink-mute);pointer-events:none;"></i>
                        <input type="text" x-model="productSearch"
                               x-ref="singleProductSearchInput"
                               @keydown.down.prevent="navigateProduct(1)"
                               @keydown.up.prevent="navigateProduct(-1)"
                               @keydown.enter.prevent="selectActiveProduct()"
                               @keydown.escape.prevent="closeProductDropdown()"
                               placeholder="Cari nama snack / varian / SKU..."
                               class="form-input"
                               style="height:32px;padding-left:30px;font-size:12px;border-radius:6px;width:100%;background:var(--color-canvas);">
                    </div>
                </div>
                <!-- Options List -->
                <div style="max-height:220px;overflow-y:auto;" class="custom-scrollbar" id="single-product-opt-list-edit">
                    <template x-for="(p, pIdx) in filteredProductList" :key="p.id">
                        <div @click="selectProduct(p)"
                             class="searchable-option"
                             :class="[
                                 'prod-opt-idx-' + pIdx,
                                 activeDropdownRow && String(p.id) === String(activeDropdownRow.item_id) ? 'is-selected' : '',
                                 pIdx === activeProductIndex ? 'is-active' : ''
                             ]"
                             style="padding:8px 12px;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:1px solid var(--color-hairline-soft);">
                            <div style="min-width:0;flex:1;">
                                <div style="font-weight:700;color:var(--color-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="p.nama_item"></div>
                                <div style="font-size:11px;color:var(--color-ink-mute);" x-text="(p.kode_sku || '') + (p.varian_rasa ? ' • ' + p.varian_rasa : '')"></div>
                            </div>
                            <span class="badge badge-mono" :class="Number(p.stok_fisik_saat_ini || 0) > 0 ? 'badge-mono' : 'badge-warning'" style="font-size:10px;font-weight:700;white-space:nowrap;flex-shrink:0;" x-text="'Stok: ' + (p.stok_fisik_saat_ini || 0)"></span>
                        </div>
                    </template>
                    <template x-if="filteredProductList.length === 0">
                        <div style="padding:14px;text-align:center;font-size:12px;color:var(--color-ink-mute);">
                            Produk snack tidak ditemukan
                        </div>
                    </template>
                </div>
            </div>

            <!-- ===================================================================== -->
            <!-- 4. SKEMA PEMBAYARAN (KIRI) & RINGKASAN TOTAL (KANAN)                  -->
            <!-- ===================================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                <!-- PANEL KIRI: SKEMA & KETENTUAN PEMBAYARAN (7 Kolom) -->
                <div class="card p-5 lg:col-span-7 flex flex-col justify-between gap-4">
                    <div class="space-y-4">
                        <div style="display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--color-hairline);padding-bottom:12px;">
                            <div style="width:34px;height:34px;border-radius:10px;background:rgba(245,158,11,0.12);color:#d97706;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="wallet" style="width:18px;height:18px;"></i>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:800;color:var(--color-ink);line-height:1.2;">Ketentuan &amp; Skema Pembayaran</div>
                                <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:1px;">Atur metode pelunasan pesanan, akun kas, dan termin jatuh tempo</div>
                            </div>
                        </div>

                        <!-- Kondisi Titip Jual (Konsinyasi) -->
                        <template x-if="isKonsinyasi">
                            <div style="padding:16px;background:rgba(225,29,72,0.05);border:1px solid rgba(225,29,72,0.22);border-radius:12px;" class="space-y-2">
                                <input type="hidden" name="tipe_pembayaran" value="konsinyasi">
                                <div class="flex items-center gap-2" style="color:#b91c1c;font-weight:800;font-size:13.5px;">
                                    <i data-lucide="shield-alert" style="width:18px;height:18px;"></i>
                                    <span>Mode Titip Jual (Konsinyasi) Aktif</span>
                                </div>
                                <p style="font-size:12px;color:#991b1b;line-height:1.5;">
                                    Pesanan ini diterbitkan sebagai <strong>Surat Jalan Titipan Barang</strong> ke rak toko mitra tanpa tagihan langsung di depan. Penagihan omzet penjualan dilakukan saat jadwal opname berkala.
                                </p>
                            </div>
                        </template>

                        <!-- Kondisi Reguler B2B -->
                        <template x-if="!isKonsinyasi">
                            <div style="display:flex;flex-direction:column;gap:14px;">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <!-- Skema Pembayaran -->
                                    <div>
                                        <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Skema Pembayaran *</label>
                                        <select name="tipe_pembayaran" x-model="header.tipe_pembayaran" @change="onTipePembayaranChange()" class="form-input font-semibold" style="height:42px;border-radius:10px;">
                                            <template x-for="opt in allowedPaymentOptions" :key="opt.value">
                                                <option :value="opt.value" x-text="opt.label" :selected="header.tipe_pembayaran === opt.value"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- Conditional: Akun Kas Penerima jika Tunai / QRIS / Transfer / DP -->
                                    <template x-if="header.tipe_pembayaran === 'cash' || header.tipe_pembayaran === 'qris' || header.tipe_pembayaran === 'transfer' || header.tipe_pembayaran === 'sebagian'">
                                        <div>
                                            <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;color:#059669;">
                                                <span x-text="header.tipe_pembayaran === 'sebagian' ? 'Masuk ke Akun Kas (DP) *' : 'Masuk ke Akun Kas Penerima *'"></span>
                                            </label>
                                            <select name="akun_kas_id" x-model="header.akun_kas_id" required class="form-input font-semibold" style="height:42px;border-radius:10px;border-color:#10b981;">
                                                <?php foreach ($cashAccounts as $a): ?>
                                                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nama_akun']) ?> (Rp <?= number_format((float)$a['saldo_saat_ini'], 0, ',', '.') ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </template>

                                    <!-- Conditional: Tanggal Jatuh Tempo jika Tempo Murni -->
                                    <template x-if="header.tipe_pembayaran === 'tempo_7_hari' || header.tipe_pembayaran === 'tempo_14_hari' || header.tipe_pembayaran === 'tempo_30_hari'">
                                        <div>
                                            <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;color:#dc2626;">Tanggal Jatuh Tempo *</label>
                                            <input type="date" name="tanggal_jatuh_tempo" x-model="header.tanggal_jatuh_tempo" required class="form-input font-mono" style="height:42px;border-radius:10px;border-color:#ef4444;color:#dc2626;font-weight:700;">
                                        </div>
                                    </template>
                                </div>

                                <!-- Grid Khusus Skema Pembayaran Sebagian / DP -->
                                <template x-if="header.tipe_pembayaran === 'sebagian'">
                                    <div style="background:rgba(16,185,129,0.05);border:1px solid rgba(16,185,129,0.25);border-radius:14px;padding:16px 18px;margin-top:4px;box-sizing:border-box;">
                                        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;align-items:flex-start;">
                                            <!-- Nominal Dibayar (DP) dengan Masking Rupiah Otomatis & Pengaman Total PO -->
                                            <div>
                                                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;">
                                                    <label style="color:#059669;font-weight:800;font-size:12px;margin:0;">Nominal Dibayar Saat Ini (DP) *</label>
                                                    <span style="font-size:11px;font-weight:700;color:var(--color-ink-mute);background:var(--color-canvas);padding:2px 8px;border-radius:6px;border:1px solid var(--color-hairline);">
                                                        Maks: <strong style="color:#2563eb;" x-text="formatRupiah(calcNetto())"></strong>
                                                    </span>
                                                </div>
                                                <div style="position:relative;">
                                                    <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-weight:800;font-size:13px;color:var(--color-ink-mute);pointer-events:none;">Rp</span>
                                                    <input type="text" 
                                                           x-model="nominal_dibayar_display"
                                                           @input="onNominalDibayarInput($event)"
                                                           @blur="validateDpLimit()"
                                                           @focus="$event.target.select()"
                                                           @click="$event.target.select()"
                                                           placeholder="0"
                                                           class="form-input font-mono font-bold text-right"
                                                           :style="(header.nominal_dibayar > calcNetto() && calcNetto() > 0) ? 'height:42px;border-radius:10px;padding-left:40px;border:1.5px solid #ef4444;background:#fef2f2;color:#dc2626;font-size:13.5px;width:100%;box-sizing:border-box;' : 'height:42px;border-radius:10px;padding-left:40px;border:1.5px solid #10b981;font-size:13.5px;width:100%;box-sizing:border-box;'">
                                                </div>
                                                <!-- Feedback Peringatan Cepat -->
                                                <template x-if="calcNetto() === 0">
                                                    <div style="display:flex;align-items:center;gap:6px;margin-top:6px;color:#d97706;font-size:11.5px;font-weight:600;line-height:1.4;">
                                                        <i data-lucide="info" style="width:14px;height:14px;flex-shrink:0;"></i>
                                                        <span>Pilih produk pesanan terlebih dahulu agar total PO terhitung.</span>
                                                    </div>
                                                </template>
                                                <template x-if="header.nominal_dibayar > calcNetto() && calcNetto() > 0">
                                                    <div style="display:flex;align-items:center;gap:6px;margin-top:6px;color:#dc2626;font-size:11.5px;font-weight:700;line-height:1.4;">
                                                        <i data-lucide="alert-circle" style="width:14px;height:14px;flex-shrink:0;"></i>
                                                        <span>Nominal DP melebihi Total PO. Ditolak oleh sistem!</span>
                                                    </div>
                                                </template>
                                            </div>

                                            <!-- Jatuh Tempo Sisa Tagihan -->
                                            <div>
                                                <div style="display:flex;align-items:center;margin-bottom:8px;height:21px;">
                                                    <label style="color:#dc2626;font-weight:800;font-size:12px;margin:0;">Jatuh Tempo Sisa Tagihan *</label>
                                                </div>
                                                <input type="date" name="tanggal_jatuh_tempo" x-model="header.tanggal_jatuh_tempo" required class="form-input font-mono font-bold" style="height:42px;border-radius:10px;border:1.5px solid #ef4444;color:#dc2626;font-size:13.5px;width:100%;box-sizing:border-box;">
                                                <div style="display:flex;align-items:center;gap:6px;margin-top:6px;color:var(--color-ink-mute);font-size:11.5px;line-height:1.4;">
                                                    <i data-lucide="calendar" style="width:14px;height:14px;flex-shrink:0;"></i>
                                                    <span>Batas pelunasan sisa tagihan pesanan.</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Informasi Pembayaran Faktur Sebelumnya jika Ada -->
                                <?php if (!empty($order['total_dibayar']) && (float)$order['total_dibayar'] > 0): ?>
                                <div style="padding:12px 16px;border-radius:12px;background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.2);display:flex;align-items:center;justify-content:space-between;gap:12px;">
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <i data-lucide="banknote" style="width:18px;height:18px;color:#2563eb;flex-shrink:0;"></i>
                                        <div>
                                            <div style="font-size:12px;font-weight:800;color:#1e40af;">Total Telah Dibayar Toko:</div>
                                            <div style="font-size:11px;color:var(--color-ink-mute);">Tercatat pada transaksi sebelumnya</div>
                                        </div>
                                    </div>
                                    <span class="font-mono font-black" style="font-size:14px;color:#1e3a8a;">Rp <?= number_format((float)$order['total_dibayar'], 0, ',', '.') ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- PANEL KANAN: RINGKASAN FINANSIAL & TOMBOL AKSI (5 Kolom) -->
                <div class="card p-5 lg:col-span-5 flex flex-col justify-between gap-4">
                    <!-- Finansial Section -->
                    <div class="space-y-3.5">
                        <div style="display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--color-hairline);padding-bottom:12px;">
                            <div style="width:34px;height:34px;border-radius:10px;background:rgba(59,130,246,0.12);color:#3b82f6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="receipt" style="width:18px;height:18px;"></i>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:800;color:var(--color-ink);line-height:1.2;">Ringkasan Finansial</div>
                                <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:1px;">Kalkulasi total nilai transaksi pesanan</div>
                            </div>
                        </div>

                        <!-- Konsinyasi info -->
                        <div x-show="isKonsinyasi" style="padding:14px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;font-size:12px;color:#991b1b;line-height:1.5;">
                            Harga satuan &amp; nilai penjualan disembunyikan dalam mode titip konsinyasi. Transaksi dinilai berdasarkan kuantitas fisik kirim.
                        </div>

                        <!-- Reguler B2B Finansial -->
                        <div x-show="!isKonsinyasi" class="space-y-2.5">
                            <div class="flex justify-between items-center text-xs" style="color:var(--color-ink-secondary);">
                                <span>Subtotal Bruto:</span>
                                <span class="font-mono font-bold text-sm" x-text="formatRupiah(calcBruto())"></span>
                            </div>

                            <div class="flex justify-between items-center text-xs" style="color:var(--color-ink-secondary);">
                                <span>Total Diskon Item:</span>
                                <span class="font-mono font-bold text-sm text-rose-500" x-text="'-' + formatRupiah(calcDiskonItem())"></span>
                            </div>

                            <div class="flex justify-between items-center gap-2 pt-1">
                                <span class="text-xs font-semibold" style="color:var(--color-ink-secondary);">Diskon Faktur (Rp):</span>
                                <div class="relative" style="width:140px;">
                                    <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-weight:700;font-size:11px;color:var(--color-ink-mute);pointer-events:none;">Rp</span>
                                    <input type="text" 
                                           x-model="diskon_faktur_display" 
                                           @input="onDiskonFakturInput($event)" 
                                           @focus="$event.target.select()"
                                           class="form-input font-mono text-right text-xs font-bold" 
                                           style="height:34px;padding-left:30px;border-radius:8px;" 
                                           placeholder="0">
                                </div>
                            </div>

                            <div style="border-top:2px solid var(--color-hairline);padding-top:10px;margin-top:8px;" class="flex justify-between items-center">
                                <span style="font-size:13.5px;font-weight:900;color:var(--color-ink);">TOTAL NETTO:</span>
                                <span class="font-mono font-black text-xl" style="color:#1e3a8a;" x-text="formatRupiah(calcNetto())"></span>
                            </div>

                            <!-- Breakdown DP jika Bayar Sebagian -->
                            <template x-if="header.tipe_pembayaran === 'sebagian'">
                                <div class="p-3 rounded-lg space-y-1.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);font-size:11.5px;margin-top:8px;">
                                    <div class="flex justify-between items-center">
                                        <span style="color:#059669;font-weight:700;">Masuk Kas (DP):</span>
                                        <span class="font-mono font-bold" style="color:#059669;" x-text="formatRupiah(header.nominal_dibayar || 0)"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span style="color:#d97706;font-weight:700;">Sisa Piutang (Tempo):</span>
                                        <span class="font-mono font-bold" style="color:#d97706;" x-text="formatRupiah(Math.max(0, calcNetto() - (Number(header.nominal_dibayar) || 0)))"></span>
                                    </div>
                                </div>
                            </template>

                            <!-- Breakdown Status Jika Ada Riwayat Pembayaran -->
                            <template x-if="Number(order.total_dibayar || 0) > 0">
                                <div class="p-3 rounded-xl space-y-2" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);font-size:11.5px;margin-top:8px;">
                                    <div class="flex justify-between items-center">
                                        <span style="color:var(--color-ink-mute);font-weight:600;">Telah Dibayar Sebelumnya:</span>
                                        <span class="font-mono font-bold" style="color:#059669;" x-text="formatRupiah(order.total_dibayar)"></span>
                                    </div>
                                    <template x-if="calcNetto() > Number(order.total_dibayar || 0)">
                                        <div class="flex justify-between items-center pt-1" style="border-top:1px dashed var(--color-hairline);">
                                            <span style="color:#dc2626;font-weight:800;">Sisa Tagihan Toko:</span>
                                            <span class="font-mono font-black" style="color:#dc2626;" x-text="formatRupiah(calcNetto() - Number(order.total_dibayar || 0))"></span>
                                        </div>
                                    </template>
                                    <template x-if="calcNetto() < Number(order.total_dibayar || 0)">
                                        <div class="flex justify-between items-center pt-1" style="border-top:1px dashed var(--color-hairline);">
                                            <span style="color:#dc2626;font-weight:800;">Kelebihan Bayar (Refund):</span>
                                            <span class="font-mono font-black" style="color:#dc2626;" x-text="formatRupiah(Number(order.total_dibayar || 0) - calcNetto())"></span>
                                        </div>
                                    </template>
                                    <template x-if="calcNetto() === Number(order.total_dibayar || 0)">
                                        <div class="flex justify-between items-center pt-1" style="border-top:1px dashed var(--color-hairline);">
                                            <span style="color:#059669;font-weight:800;">Status Pembayaran:</span>
                                            <span class="badge" style="background:#d1fae5;color:#065f46;font-weight:800;padding:2px 8px;border-radius:6px;">Lunas Pas</span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="pt-2">
                        <button type="button" @click="submitOrder()" :disabled="isSubmitting || items.length === 0" class="btn btn-primary w-full" style="font-weight:700;height:42px;border-radius:10px;<?= $isRetryEdit ? 'background:#1e3a8a;border-color:#1e3a8a;' : '' ?>">
                            <i data-lucide="<?= $isRetryEdit ? 'rotate-cw' : 'save' ?>"></i>
                            <span x-text="isSubmitting ? (isRetryEdit ? 'Menjadwalkan Kirim Ulang...' : 'Menyimpan Perubahan...') : (isRetryEdit ? 'Simpan &amp; Kirim Ulang (Ctrl+Enter)' : 'Simpan Perubahan (Ctrl+Enter)')"></span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>

    <!-- ========================================================================= -->
    <!-- MODAL POP-UP PETUNJUK ALUR OPERASIONAL (ENTERPRISE WORKFLOW STEPPER)       -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="showGuideModal" x-cloak class="modal-backdrop"
             style="position:fixed;inset:0;background:rgba(15,23,42,0.65);backdrop-filter:blur(8px);z-index:99999;display:flex;align-items:center;justify-content:center;padding:24px;">
            <div @click.outside="showGuideModal = false" 
                 x-data="{ activeGuideTab: (isKonsinyasi ? 'konsinyasi' : 'reguler') }"
                 class="card shadow-2xl bg-card border border-hairline animate-scale-in"
                 style="width:100%;max-width:620px;border-radius:24px;padding:28px 30px;box-sizing:border-box;">
                
                <!-- Modal Header -->
                <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:18px;border-bottom:1px solid var(--color-hairline);margin-bottom:20px;">
                    <div style="display:flex;align-items:center;gap:14px;">
                        <div style="width:44px;height:44px;min-width:44px;border-radius:12px;background:rgba(99,102,241,0.12);color:#6366f1;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="book-open" style="width:22px;height:22px;"></i>
                        </div>
                        <div>
                            <div class="font-extrabold text-ink" style="font-size:16.5px;line-height:1.3;">Petunjuk Alur Operasional Pesanan</div>
                            <div class="text-xs text-ink-mute" style="margin-top:3px;">Alur transaksi penerbitan PO hingga barang diterima di toko</div>
                        </div>
                    </div>
                    <button type="button" @click="showGuideModal = false" class="btn btn-ghost btn-sm text-ink-mute hover:text-ink" style="width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;padding:0;">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                <!-- Modern Segmented Pill Switcher -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;padding:5px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;margin-bottom:22px;">
                    <button type="button" @click="activeGuideTab = 'reguler'"
                            class="py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2"
                            :style="activeGuideTab === 'reguler' ? 'background:var(--color-canvas);color:#2563eb;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid rgba(37,99,235,0.25);' : 'color:var(--color-ink-mute);background:transparent;border:1px solid transparent;'">
                        <i data-lucide="shopping-bag" style="width:15px;height:15px;flex-shrink:0;"></i>
                        <span>Penjualan Reguler</span>
                    </button>
                    <button type="button" @click="activeGuideTab = 'konsinyasi'"
                            class="py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2"
                            :style="activeGuideTab === 'konsinyasi' ? 'background:var(--color-canvas);color:#e11d48;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid rgba(225,29,72,0.25);' : 'color:var(--color-ink-mute);background:transparent;border:1px solid transparent;'">
                        <i data-lucide="store" style="width:15px;height:15px;flex-shrink:0;"></i>
                        <span>Titip Jual (Konsinyasi)</span>
                    </button>
                </div>

                <!-- Tab 1: Penjualan Reguler -->
                <div x-show="activeGuideTab === 'reguler'" style="display:flex;flex-direction:column;gap:12px;">
                    <!-- Step 1 -->
                    <div style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;padding:14px 18px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                            <span style="font-weight:800;font-size:13.5px;color:var(--color-ink);">Tahap 1: Penerbitan Purchase Order (PO)</span>
                            <span style="font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:6px;background:rgba(245,158,11,0.12);color:#d97706;border:1px solid rgba(245,158,11,0.25);">Draf Antrean</span>
                        </div>
                        <p style="font-size:12.5px;line-height:1.55;color:var(--color-ink-secondary);margin:0;">
                            Pesanan baru masuk antrean sistem dengan status <strong>PO</strong>. Pada tahap ini, <strong>stok fisik di gudang belum terpotong</strong> sehingga pesanan masih dapat diedit atau dibatalkan.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;padding:14px 18px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                            <span style="font-weight:800;font-size:13.5px;color:var(--color-ink);">Tahap 2: Proses Gudang &amp; Surat Jalan</span>
                            <span style="font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:6px;background:rgba(37,99,235,0.1);color:#2563eb;border:1px solid rgba(37,99,235,0.25);">Stok Gudang Terpotong</span>
                        </div>
                        <p style="font-size:12.5px;line-height:1.55;color:var(--color-ink-secondary);margin:0;">
                            Tim gudang menyiapkan stok fisik lalu menekan tombol <strong>"Siap Kirim"</strong>. Sistem otomatis <strong>memotong stok fisik gudang</strong> dan menerbitkan lembar Surat Jalan resmi untuk pengiriman.
                        </p>
                    </div>

                    <!-- Step 3 (Final) -->
                    <div style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;padding:14px 18px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                            <span style="font-weight:800;font-size:13.5px;color:var(--color-ink);">Tahap 3: Pengiriman &amp; Pencatatan Finansial</span>
                            <span style="font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:6px;background:rgba(16,185,129,0.12);color:#059669;border:1px solid rgba(16,185,129,0.25);">Selesai Diterima</span>
                        </div>
                        <p style="font-size:12.5px;line-height:1.55;color:var(--color-ink-secondary);margin:0;">
                            Driver menyerahkan barang ke toko mitra. Saat status pesanan diubah ke <strong>"Selesai Diterima"</strong>, pembayaran tunai/DP otomatis masuk ke <strong>Buku Kas</strong> &amp; sisa tagihan dicatat ke <strong>Piutang Toko</strong>.
                        </p>
                    </div>
                </div>

                <!-- Tab 2: Titip Jual Konsinyasi -->
                <div x-show="activeGuideTab === 'konsinyasi'" style="display:flex;flex-direction:column;gap:12px;">
                    <!-- Step 1 -->
                    <div style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;padding:14px 18px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                            <span style="font-weight:800;font-size:13.5px;color:var(--color-ink);">Tahap 1: Surat Jalan Titipan Barang (Rp 0)</span>
                            <span style="font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:6px;background:rgba(225,29,72,0.12);color:#e11d48;border:1px solid rgba(225,29,72,0.25);">Non-Tagihan Awal</span>
                        </div>
                        <p style="font-size:12.5px;line-height:1.55;color:var(--color-ink-secondary);margin:0;">
                            Pesanan dicatat sebagai distribusi barang titipan rak toko mitra tanpa ada kewajiban pembayaran tunai di muka.
                        </p>
                    </div>

                    <!-- Step 2 (Final) -->
                    <div style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;padding:14px 18px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                            <span style="font-weight:800;font-size:13.5px;color:var(--color-ink);">Tahap 2: Stok Rak Toko &amp; Penagihan Opname</span>
                            <span style="font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:6px;background:rgba(16,185,129,0.12);color:#059669;border:1px solid rgba(16,185,129,0.25);">Opname Fisik Rutin</span>
                        </div>
                        <p style="font-size:12.5px;line-height:1.55;color:var(--color-ink-secondary);margin:0;">
                            Kuantiti fisik otomatis masuk ke <strong>Stok Rak Toko Mitra</strong>. Tagihan penjualan hanya dihitung dari selisih barang yang laku terjual saat kunjungan opname berkala berikutnya.
                        </p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div style="display:flex;justify-content:flex-end;padding-top:20px;margin-top:22px;border-top:1px solid var(--color-hairline);">
                    <button type="button" @click="showGuideModal = false" class="btn btn-primary font-bold text-xs flex items-center justify-center gap-2 shadow-sm" style="border-radius:10px;height:42px;padding:0 28px;">
                        <i data-lucide="check-circle-2" style="width:16px;height:16px;"></i>
                        <span>Saya Mengerti</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL KONFIRMASI REFUND DANA KELEBIHAN BAYAR                              -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="showRefundModal" x-cloak class="modal-backdrop"
             style="position:fixed;inset:0;background:rgba(15,23,42,0.65);backdrop-filter:blur(8px);z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;">
            <div @click.outside="showRefundModal = false"
                 class="card shadow-2xl bg-card border border-hairline animate-scale-in"
                 style="max-width:480px;width:100%;border-radius:20px;padding:24px;box-sizing:border-box;display:flex;flex-direction:column;gap:18px;">
                
                <div style="display:flex;align-items:center;gap:14px;">
                    <div style="width:48px;height:48px;border-radius:14px;background:rgba(239,68,68,0.1);color:#ef4444;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="wallet-cards" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <h3 style="font-size:16px;font-weight:900;color:var(--color-ink);margin:0;">Konfirmasi Pengembalian Dana (Refund)</h3>
                        <p style="font-size:12px;color:var(--color-ink-mute);margin:2px 0 0 0;">Kelebihan pembayaran akibat pengurangan item pesanan</p>
                    </div>
                </div>

                <div style="padding:14px;background:rgba(239,68,68,0.05);border:1.5px solid rgba(239,68,68,0.2);border-radius:12px;font-size:13px;color:var(--color-ink);line-height:1.5;">
                    <div style="margin-bottom:8px;">
                        Total tagihan baru (<strong x-text="formatRupiah(calcNetto())"></strong>) lebih kecil daripada total yang telah dibayar toko (<strong x-text="formatRupiah(order.total_dibayar || 0)"></strong>).
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:var(--color-canvas);border-radius:8px;border:1px solid rgba(239,68,68,0.15);font-size:13px;">
                        <span style="font-weight:700;color:#991b1b;">Nominal Refund Kasir:</span>
                        <span class="font-mono font-black" style="font-size:15px;color:#ef4444;" x-text="formatRupiah(refundForm.nominal)"></span>
                    </div>
                </div>

                <div>
                    <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;display:block;">Pilih Akun Kas Pengeluaran Refund:</label>
                    <select x-model="refundForm.akun_kas_id" class="form-input font-semibold w-full" style="height:42px;border-radius:10px;font-size:13px;">
                        <template x-for="acc in cashAccounts" :key="acc.id">
                            <option :value="acc.id" x-text="acc.nama_akun + ' (Saldo: ' + formatRupiah(acc.saldo_saat_ini) + ')'"></option>
                        </template>
                    </select>
                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:5px;">
                        Sistem akan otomatis mencatat arus kas keluar kategori <strong>Koreksi / Refund</strong> dan menyinkronkan saldo kas.
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:6px;">
                    <button type="button" @click="showRefundModal = false" class="btn btn-secondary" style="border-radius:10px;font-weight:700;padding:9px 16px;">
                        Batal &amp; Periksa
                    </button>
                    <button type="button" @click="confirmAndSubmitRefund()" class="btn btn-danger" style="border-radius:10px;font-weight:700;padding:9px 18px;display:flex;align-items:center;gap:6px;">
                        <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                        <span>Konfirmasi Refund &amp; Simpan</span>
                    </button>
                </div>

            </div>
        </div>
    </template>

</div>

<script>
function editSalesOrderApp() {
    return {
        isSubmitting: false,
        products: <?= json_encode($products ?? [], JSON_UNESCAPED_UNICODE) ?>,
        priceMatrix: <?= json_encode($priceMatrix ?? [], JSON_UNESCAPED_UNICODE) ?>,
        order: <?= json_encode($order ?? [], JSON_UNESCAPED_UNICODE) ?>,
        cashAccounts: <?= json_encode($cashAccounts ?? [], JSON_UNESCAPED_UNICODE) ?>,
        whitelistMap: <?= json_encode($whitelistMap ?? [], JSON_UNESCAPED_UNICODE) ?>,
        isRetryEdit: <?= json_encode((bool)$isRetryEdit) ?>,
        showGuideModal: false,
        showRefundModal: false,
        refundForm: {
            akun_kas_id: '<?= !empty($cashAccounts) ? $cashAccounts[0]['id'] : '' ?>',
            nominal: 0
        },
        showAllProducts: false,
        header: {
            tanggal_pesanan: '<?= htmlspecialchars($order['tanggal_pesanan'] ?? date('Y-m-d')) ?>',
            tipe_pembayaran: '<?= htmlspecialchars($order['tipe_pembayaran'] ?? 'cash') ?>',
            tanggal_jatuh_tempo: '<?= htmlspecialchars($order['tanggal_jatuh_tempo'] ?? '') ?>',
            sales_driver_id: '<?= htmlspecialchars($order['sales_driver_id'] ?? '') ?>',
            akun_kas_id: '<?= !empty($order['akun_kas_id']) ? htmlspecialchars($order['akun_kas_id']) : (!empty($cashAccounts) ? $cashAccounts[0]['id'] : '') ?>',
            nominal_dibayar: <?= (float)($order['total_dibayar'] ?? 0) ?>,
            diskon_faktur: <?= (float)$initialDiskonFaktur ?>,
            catatan: <?= json_encode($order['catatan'] ?? '') ?>
        },
        nominal_dibayar_display: '<?= !empty($order['total_dibayar']) && (float)$order['total_dibayar'] > 0 ? number_format((float)$order['total_dibayar'], 0, ',', '.') : '' ?>',
        diskon_faktur_display: '<?= $initialDiskonFaktur > 0 ? number_format((float)$initialDiskonFaktur, 0, ',', '.') : '' ?>',

        items: <?= json_encode(array_map(function($it) use ($products) {
            $p = null;
            foreach ($products ?? [] as $prod) {
                if ($prod['id'] == $it['item_id']) {
                    $p = $prod;
                    break;
                }
            }
            return [
                'uid' => uniqid('row_'),
                'item_id' => $it['item_id'],
                'stok_tersedia' => (int)($p['stok_fisik_saat_ini'] ?? $it['stok_fisik_saat_ini'] ?? 0),
                'qty' => (int)$it['qty'],
                'harga' => (float)$it['harga'],
                'diskon' => (float)$it['diskon'],
                'subtotal' => (float)$it['subtotal'],
                'is_bonus' => (bool)$it['is_bonus']
            ];
        }, $existingItems ?? []), JSON_UNESCAPED_UNICODE) ?>,

        // Single Floating Dropdown State
        activeDropdownRow: null,
        productSearch: '',
        activeProductIndex: 0,
        dropdownCoords: { top: 0, left: 0, width: 380 },

        get isKonsinyasi() {
            return this.header.tipe_pembayaran === 'konsinyasi' || Number(this.order.level_harga) === 5 || Boolean(this.order.is_konsinyasi);
        },

        get hasWhitelist() {
            const pelId = this.order.pelanggan_id;
            if (!pelId) return false;
            const wl = this.whitelistMap[pelId];
            return Boolean(wl && wl.length > 0);
        },

        get whitelistCount() {
            const pelId = this.order.pelanggan_id;
            if (!pelId) return 0;
            const wl = this.whitelistMap[pelId];
            return wl ? wl.length : 0;
        },

        get availableProducts() {
            const pelId = this.order.pelanggan_id;
            if (!pelId || this.showAllProducts) return this.products;
            const wl = this.whitelistMap[pelId];
            if (wl && wl.length > 0) {
                return this.products.filter(p => wl.includes(p.id));
            }
            return this.products;
        },

        get filteredProductList() {
            const q = (this.productSearch || '').toLowerCase().trim();
            const prods = this.availableProducts;
            if (!q) return prods;
            return prods.filter(p => {
                const name = (p.nama_item || '').toLowerCase();
                const sku = (p.kode_sku || '').toLowerCase();
                const rasa = (p.varian_rasa || '').toLowerCase();
                return name.includes(q) || sku.includes(q) || rasa.includes(q);
            });
        },

        closeProductDropdown() {
            this.activeDropdownRow = null;
            this.productSearch = '';
            this.activeProductIndex = 0;
        },

        getSelectedProductName(itemId) {
            if (!itemId) return 'Pilih Snack & Varian Rasa';
            const p = this.products.find(x => x.id === itemId);
            if (!p) return 'Pilih Snack & Varian Rasa';
            return p.nama_item + (p.kode_sku ? ' (' + p.kode_sku + ')' : '');
        },

        openProductDropdown(row, event) {
            this.activeDropdownRow = row;
            this.productSearch = '';
            this.activeProductIndex = 0;

            const triggerEl = event ? (event.currentTarget || event.target) : document.querySelector('.prod-trigger-' + row.uid);
            this.updateDropdownCoords(triggerEl);

            this.$nextTick(() => {
                this.$refs.singleProductSearchInput?.focus();
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        toggleProductDropdown(row, event) {
            if (this.activeDropdownRow === row) {
                this.closeProductDropdown();
                return;
            }
            this.openProductDropdown(row, event);
        },

        navigateProduct(dir) {
            const list = this.filteredProductList;
            if (!list.length) return;
            this.activeProductIndex = Math.max(0, Math.min(list.length - 1, this.activeProductIndex + dir));
            this.scrollToActive('prod-opt-idx-' + this.activeProductIndex, 'single-product-opt-list-edit');
        },

        selectActiveProduct() {
            const list = this.filteredProductList;
            if (list.length > 0) {
                const idx = (this.activeProductIndex >= 0 && this.activeProductIndex < list.length) ? this.activeProductIndex : 0;
                this.selectProduct(list[idx]);
            }
        },

        selectProduct(p) {
            if (!this.activeDropdownRow) return;
            const row = this.activeDropdownRow;
            const uid = row.uid;
            row.item_id = p.id;
            row.stok_tersedia = p.stok_fisik_saat_ini || 0;
            this.onProductSelect(row);
            this.closeProductDropdown();

            setTimeout(() => {
                const tr = document.querySelector('.row-uid-' + uid);
                if (tr) {
                    const qtyIn = tr.querySelector('input[data-nav="qty"]');
                    if (qtyIn) {
                        qtyIn.focus();
                        qtyIn.select();
                    }
                }
            }, 40);
        },

        updateDropdownCoords(triggerEl) {
            if (!triggerEl) return;
            const rect = triggerEl.getBoundingClientRect();
            const maxW = Math.min(460, window.innerWidth - 24);
            const menuWidth = Math.max(280, Math.min(maxW, rect.width > 200 ? rect.width : 380));
            let left = rect.left;
            if (left + menuWidth > window.innerWidth - 12) {
                left = window.innerWidth - menuWidth - 12;
            }
            if (left < 12) left = 12;
            
            const spaceBelow = window.innerHeight - rect.bottom;
            let top = rect.bottom + 4;
            if (spaceBelow < 250 && rect.top > 250) {
                top = Math.max(10, rect.top - 264);
            }
            
            this.dropdownCoords = {
                top: Math.round(top),
                left: Math.round(left),
                width: Math.round(menuWidth)
            };
        },

        focusFirstProduct() {
            this.closeProductDropdown();
            setTimeout(() => {
                const firstProductBtn = document.querySelector('tbody tr:first-child [data-nav="product"]');
                if (firstProductBtn) {
                    firstProductBtn.focus();
                }
            }, 40);
        },

        onQtyEnter(row, idx) {
            this.closeProductDropdown();
            const isKons = this.isKonsinyasi;
            const tr = document.querySelector('.row-uid-' + row.uid) || document.querySelectorAll('tbody tr')[idx];
            if (!isKons) {
                const diskonIn = tr ? tr.querySelector('input[data-nav="diskon"]') : null;
                if (diskonIn && !diskonIn.disabled) {
                    diskonIn.focus();
                    diskonIn.select();
                    return;
                }
            }
            this.onDiskonEnter(row, idx);
        },

        onDiskonEnter(row, idx) {
            this.closeProductDropdown();
            if (idx === this.items.length - 1) {
                this.addItemRow();
                setTimeout(() => {
                    const rows = document.querySelectorAll('tbody tr');
                    const nextTr = rows[rows.length - 1];
                    if (nextTr) {
                        const prodBtn = nextTr.querySelector('[data-nav="product"]');
                        if (prodBtn) {
                            prodBtn.focus();
                        }
                    }
                }, 50);
            } else {
                setTimeout(() => {
                    const rows = document.querySelectorAll('tbody tr');
                    const nextTr = rows[idx + 1];
                    if (nextTr) {
                        const prodBtn = nextTr.querySelector('[data-nav="product"]');
                        if (prodBtn) {
                            prodBtn.focus();
                        }
                    }
                }, 30);
            }
        },

        moveRowVertical(idx, dir, field) {
            const targetIdx = idx + dir;
            const rows = document.querySelectorAll('tbody tr');
            if (targetIdx >= 0 && targetIdx < rows.length) {
                const targetInp = rows[targetIdx].querySelector(`input[data-nav="${field}"]`);
                if (targetInp && !targetInp.disabled) {
                    targetInp.focus();
                    if (typeof targetInp.select === 'function') targetInp.select();
                }
            }
        },

        scrollToActive(className, containerId) {
            this.$nextTick(() => {
                const container = document.getElementById(containerId);
                const el = container ? container.querySelector('.' + className) : null;
                if (el && container) {
                    const cTop = container.scrollTop;
                    const cBottom = cTop + container.clientHeight;
                    const eTop = el.offsetTop;
                    const eBottom = eTop + el.clientHeight;
                    if (eTop < cTop) {
                        container.scrollTop = eTop;
                    } else if (eBottom > cBottom) {
                        container.scrollTop = eBottom - container.clientHeight;
                    }
                }
            });
        },

        init() {
            if (this.items.length === 0) {
                this.addItemRow();
            }
            window.addEventListener('scroll', () => {
                if (this.activeDropdownRow !== null) this.closeProductDropdown();
            }, { passive: true });

            // Keyboard Shortcut Ctrl+Enter untuk submit
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    this.submitOrder();
                }
            });

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        onNominalDibayarInput(e) {
            let raw = (e.target.value || '').replace(/[^0-9]/g, '');
            let val = raw ? parseInt(raw, 10) : 0;
            const maxAllowed = this.calcNetto();

            if (maxAllowed === 0 && val > 0) {
                this.header.nominal_dibayar = 0;
                this.nominal_dibayar_display = '';
                if (typeof toast !== 'undefined' && toast.warning) {
                    toast.warning('Silakan masukkan produk pesanan terlebih dahulu sebelum mengisi nominal DP.');
                }
                return;
            }

            if (val > maxAllowed && maxAllowed > 0) {
                this.header.nominal_dibayar = maxAllowed;
                this.nominal_dibayar_display = Number(maxAllowed).toLocaleString('id-ID');
                if (typeof toast !== 'undefined' && toast.error) {
                    toast.error(`Nominal DP Ditolak: Tidak boleh melebihi Total Nilai PO (${this.formatRupiah(maxAllowed)}). DP langsung dibatasi ke batas maksimal.`);
                }
                return;
            }

            this.header.nominal_dibayar = val;
            this.nominal_dibayar_display = val > 0 ? Number(val).toLocaleString('id-ID') : '';
        },

        validateDpLimit() {
            if (this.header.tipe_pembayaran === 'sebagian') {
                const maxAllowed = this.calcNetto();
                if (this.header.nominal_dibayar > maxAllowed) {
                    this.header.nominal_dibayar = maxAllowed;
                    this.nominal_dibayar_display = maxAllowed > 0 ? Number(maxAllowed).toLocaleString('id-ID') : '';
                    if (typeof toast !== 'undefined' && toast.warning) {
                        toast.warning(`Nominal DP disesuaikan menjadi ${this.formatRupiah(maxAllowed)} agar tidak melebihi Total PO.`);
                    }
                }
            }
        },

        onDiskonFakturInput(e) {
            let raw = (e.target.value || '').replace(/[^0-9]/g, '');
            this.header.diskon_faktur = raw ? parseInt(raw, 10) : 0;
            this.diskon_faktur_display = raw ? Number(raw).toLocaleString('id-ID') : '';
            this.validateDpLimit();
        },

        onTanggalPesananChange() {
            this.onTipePembayaranChange();
        },

        onProductSelect(row) {
            if (!row.item_id) {
                row.harga = 0;
                row.subtotal = 0;
                this.validateDpLimit();
                return;
            }
            row.harga = this.getPriceForProduct(row.item_id);
            this.calcRow(row);
        },

        getPriceForProduct(itemId) {
            if (!itemId) return 0;
            const product = this.products.find(p => p.id === itemId);
            if (!product) return 0;
            const level = Number(this.order.level_harga) || 1;
            const groupPrices = this.priceMatrix[product.grup_id];
            if (!groupPrices) return 0;

            // 1. Level harga spesifik toko
            if (groupPrices[level] && Number(groupPrices[level].pcs) > 0) {
                return Number(groupPrices[level].pcs);
            }

            // 2. Smart Fallback ke level terdekat yang tersedia
            const availableLevels = Object.keys(groupPrices).map(Number).sort((a, b) => a - b);
            if (availableLevels.length > 0) {
                const lowerLevels = availableLevels.filter(lvl => lvl <= level);
                if (lowerLevels.length > 0) {
                    const fallbackLvl = lowerLevels[lowerLevels.length - 1];
                    if (groupPrices[fallbackLvl] && Number(groupPrices[fallbackLvl].pcs) > 0) {
                        return Number(groupPrices[fallbackLvl].pcs);
                    }
                }
                if (groupPrices[1] && Number(groupPrices[1].pcs) > 0) {
                    return Number(groupPrices[1].pcs);
                }
                return Number(groupPrices[availableLevels[0]].pcs || 0);
            }

            return 0;
        },

        get allowedPaymentOptions() {
            if (this.isKonsinyasi) {
                return [{ value: 'konsinyasi', label: 'Titip Jual (Konsinyasi)' }];
            }
            const def = this.order?.tipe_pembayaran_default || this.header?.tipe_pembayaran || 'cash';
            if (def === 'cash' || def === 'qris' || def === 'transfer') {
                return [
                    { value: 'cash', label: 'Tunai (Lunas 100%)' },
                    { value: 'qris', label: 'QRIS (Non-Tunai Lunas)' },
                    { value: 'transfer', label: 'Transfer Bank (Lunas)' },
                    { value: 'sebagian', label: 'Kredit / Bayar Sebagian (DP)' }
                ];
            }
            if (def === 'tempo_7_hari') {
                return [
                    { value: 'tempo_7_hari', label: 'Tempo 7 Hari (Sesuai Toko)' },
                    { value: 'sebagian', label: 'Kredit / Bayar Sebagian (DP)' }
                ];
            }
            if (def === 'tempo_14_hari') {
                return [
                    { value: 'tempo_14_hari', label: 'Tempo 14 Hari (Sesuai Toko)' },
                    { value: 'sebagian', label: 'Kredit / Bayar Sebagian (DP)' }
                ];
            }
            if (def === 'tempo_30_hari') {
                return [
                    { value: 'tempo_30_hari', label: 'Tempo 30 Hari (Sesuai Toko)' },
                    { value: 'sebagian', label: 'Kredit / Bayar Sebagian (DP)' }
                ];
            }
            if (def === 'sebagian') {
                return [
                    { value: 'sebagian', label: 'Kredit / Bayar Sebagian (DP)' },
                    { value: 'cash', label: 'Tunai (Lunas 100%)' },
                    { value: 'transfer', label: 'Transfer Bank (Lunas)' }
                ];
            }
            return [
                { value: 'cash', label: 'Tunai (Lunas 100%)' },
                { value: 'qris', label: 'QRIS (Non-Tunai Lunas)' },
                { value: 'transfer', label: 'Transfer Bank (Lunas)' },
                { value: 'sebagian', label: 'Kredit / Bayar Sebagian (DP)' },
                { value: 'tempo_7_hari', label: 'Tempo 7 Hari' },
                { value: 'tempo_14_hari', label: 'Tempo 14 Hari' },
                { value: 'tempo_30_hari', label: 'Tempo 30 Hari' }
            ];
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
            this.validateDpLimit();
        },

        calcRow(row) {
            const qty = Number(row.qty || 1);
            const harga = Number(row.harga || 0);
            const diskon = Number(row.diskon || 0);
            row.subtotal = Math.max(0, (qty * harga) - diskon);
            this.validateDpLimit();
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
                if (typeof toast !== 'undefined' && toast.warning) {
                    toast.warning('Mohon masukkan minimal 1 produk snack dengan Qty valid.');
                } else {
                    alert('Mohon masukkan minimal 1 produk snack dengan Qty valid.');
                }
                return;
            }

            // Validasi Finansial Pengaman Uang Muka (DP)
            if (this.header.tipe_pembayaran === 'sebagian') {
                const totalPo = this.calcNetto();
                const dp = Number(this.header.nominal_dibayar || 0);

                if (dp <= 0) {
                    if (typeof toast !== 'undefined' && toast.error) {
                        toast.error('Gagal Simpan: Skema Pembayaran Sebagian (DP) mewajibkan nominal uang muka lebih dari Rp 0.');
                    } else {
                        alert('Skema Pembayaran Sebagian (DP) mewajibkan nominal uang muka lebih dari Rp 0.');
                    }
                    return;
                }

                if (dp > totalPo) {
                    if (typeof toast !== 'undefined' && toast.error) {
                        toast.error(`Gagal Simpan: Nominal DP (${this.formatRupiah(dp)}) melebihi Total PO (${this.formatRupiah(totalPo)}). Transaksi ditolak oleh sistem!`);
                    } else {
                        alert(`Nominal DP melebihi Total PO.`);
                    }
                    return;
                }
            }

            const netto = this.calcNetto();
            const totalDibayar = Number(this.order.total_dibayar || 0);

            // Jika mode kirim ulang / gagal dikirim dan ada kelebihan bayar
            if (this.isRetryEdit && totalDibayar > 0 && netto < totalDibayar) {
                const selisih = totalDibayar - netto;
                this.refundForm.nominal = selisih;
                this.showRefundModal = true;
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
                return;
            }

            this.executeSubmit();
        },

        confirmAndSubmitRefund() {
            if (!this.refundForm.akun_kas_id) {
                if (window.AppAction) {
                    window.AppAction.error('Pilih Akun Kas!', 'Mohon pilih akun kas sumber pengembalian dana (refund).');
                } else {
                    alert('Mohon pilih akun kas sumber pengembalian dana.');
                }
                return;
            }
            this.showRefundModal = false;
            this.executeSubmit();
        },

        executeSubmit() {
            this.isSubmitting = true;
            const actionText = this.isRetryEdit 
                ? 'Menjadwalkan Kirim Ulang Pesanan...' 
                : (this.isKonsinyasi ? 'Memperbarui Draf Titip Jual...' : 'Memperbarui Pesanan Pelanggan...');

            if (window.AppAction) {
                window.AppAction.show(actionText);
            }
            if (window.AppSkeleton) {
                window.AppSkeleton.hide();
            }

            this.$nextTick(() => {
                const form = document.getElementById('salesOrderForm');
                if (form) {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            });
        },

        formatRupiah(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
        },

        formatNumber(val) {
            return new Intl.NumberFormat('id-ID').format(val || 0);
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

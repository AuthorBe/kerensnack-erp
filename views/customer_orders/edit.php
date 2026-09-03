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
                <div class="card p-4 flex flex-col gap-4">
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
                    <div style="padding:10px 12px;border-radius:12px;font-size:12px;display:flex;align-items:center;justify-content:space-between;gap:8px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);color:var(--color-ink);">
                        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                            <div style="width:26px;height:26px;border-radius:6px;background:rgba(99,102,241,0.1);color:#6366f1;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="lock" style="width:13px;height:13px;"></i>
                            </div>
                            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                Skema: <strong>Level <?= htmlspecialchars($order['level_harga']) ?> (<?= htmlspecialchars($order['nama_grup_harga'] ?? 'Standar') ?>)</strong>
                            </span>
                        </div>
                        <template x-if="isKonsinyasi">
                            <span style="background:rgba(225,29,72,0.1);color:#e11d48;border:1px solid rgba(225,29,72,0.22);padding:3px 8px;border-radius:6px;font-weight:800;font-size:10.5px;text-transform:uppercase;white-space:nowrap;">
                                Titip Jual (Konsinyasi)
                            </span>
                        </template>
                        <template x-if="!isKonsinyasi">
                            <span style="background:rgba(16,185,129,0.1);color:#059669;border:1px solid rgba(16,185,129,0.22);padding:3px 8px;border-radius:6px;font-weight:800;font-size:10.5px;text-transform:uppercase;white-space:nowrap;">
                                Penjualan B2B
                            </span>
                        </template>
                    </div>

                    <div>
                        <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Catatan / Keterangan Nota</label>
                        <input type="text" name="catatan" x-model="header.catatan"
                               @keydown.enter.prevent="focusFirstProduct()"
                               class="form-input enter-nav"
                               style="height:42px;border-radius:10px;font-size:13px;"
                               placeholder="Contoh: Titip faktur ke kasir...">
                    </div>

                    <!-- Whitelist Status Banner -->
                    <div style="padding:9px 12px;border-radius:12px;font-size:12px;display:flex;align-items:center;justify-content:space-between;background:rgba(56,189,248,0.06);border:1px solid rgba(56,189,248,0.2);color:var(--color-ink-secondary);">
                        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                            <i data-lucide="shield-check" style="width:16px;height:16px;color:#0284c7;flex-shrink:0;" x-show="hasWhitelist"></i>
                            <i data-lucide="globe" style="width:16px;height:16px;color:#0284c7;flex-shrink:0;" x-show="!hasWhitelist"></i>
                            <span x-text="hasWhitelist ? ('Khusus ' + whitelistCount + ' Produk Terdaftar Toko') : ('Semua <?= count($products) ?> Produk Tersedia')" style="font-weight:700;color:var(--color-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></span>
                        </div>
                        <template x-if="hasWhitelist">
                            <button type="button" @click="showAllProducts = !showAllProducts" class="btn btn-ghost btn-xs" style="font-size:11.5px;padding:3px 8px;font-weight:800;color:#0284c7;border-radius:6px;" x-text="showAllProducts ? '← Saring Whitelist' : 'Buka <?= count($products) ?> SKU'"></button>
                        </template>
                    </div>
                </div>

                <!-- PANEL KANAN: PARAMETER FAKTUR & PEMBAYARAN -->
                <div class="card p-4 flex flex-col gap-4">
                    <div style="display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--color-hairline);padding-bottom:10px;">
                        <div style="width:34px;height:34px;border-radius:10px;background:rgba(245,158,11,0.12);color:#d97706;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="receipt" style="width:18px;height:18px;"></i>
                        </div>
                        <div>
                            <div style="font-size:13.5px;font-weight:800;color:var(--color-ink);line-height:1.2;">Faktur &amp; Skema Pembayaran</div>
                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">Nomor transaksi &amp; ketentuan tempo</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label flex items-center justify-between" style="font-size:12px;font-weight:700;margin-bottom:6px;">
                                <span>No. Faktur</span>
                                <span style="font-size:10.5px;color:var(--color-ink-mute);display:flex;align-items:center;gap:3px;"><i data-lucide="lock" style="width:11px;height:11px;"></i> Terkunci</span>
                            </label>
                            <input type="text" readonly value="<?= htmlspecialchars($order['nomor_nota']) ?>" 
                                   class="form-input font-bold font-mono" style="height:42px;border-radius:10px;background:var(--color-canvas-soft);cursor:not-allowed;color:var(--color-ink);letter-spacing:0.02em;">
                        </div>

                        <div>
                            <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Tanggal Transaksi *</label>
                            <input type="date" name="tanggal_pesanan" x-model="header.tanggal_pesanan" required class="form-input font-mono" style="height:42px;border-radius:10px;">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Skema Pembayaran *</label>
                            <template x-if="isKonsinyasi">
                                <div>
                                    <input type="hidden" name="tipe_pembayaran" value="konsinyasi">
                                    <div class="form-input font-semibold flex items-center justify-between" style="height:42px;border-radius:10px;background:rgba(225,29,72,0.06);color:#b91c1c;border-color:rgba(225,29,72,0.2);">
                                        <span>Titip Jual (Konsinyasi)</span>
                                        <i data-lucide="lock" style="width:13px;height:13px;"></i>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!isKonsinyasi">
                                <select name="tipe_pembayaran" x-model="header.tipe_pembayaran" @change="onTipePembayaranChange()" class="form-input font-semibold" style="height:42px;border-radius:10px;">
                                    <template x-for="opt in allowedPaymentOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label" :selected="header.tipe_pembayaran === opt.value"></option>
                                    </template>
                                </select>
                            </template>
                        </div>

                        <!-- Conditional: Tanggal Jatuh Tempo jika Tempo Murni -->
                        <template x-if="!isKonsinyasi && (header.tipe_pembayaran === 'tempo_7_hari' || header.tipe_pembayaran === 'tempo_14_hari' || header.tipe_pembayaran === 'tempo_30_hari')">
                            <div>
                                <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;color:#ef4444;">Tanggal Jatuh Tempo *</label>
                                <input type="date" name="tanggal_jatuh_tempo" x-model="header.tanggal_jatuh_tempo" required class="form-input font-mono" style="height:42px;border-radius:10px;border-color:#ef4444;color:#ef4444;font-weight:700;">
                            </div>
                        </template>

                        <!-- Conditional: Diskon Faktur jika Pembayaran Tunai / QRIS / Transfer -->
                        <template x-if="!isKonsinyasi && (header.tipe_pembayaran === 'cash' || header.tipe_pembayaran === 'qris' || header.tipe_pembayaran === 'transfer')">
                            <div>
                                <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Diskon Faktur (Rp)</label>
                                <input type="number" name="diskon_faktur" x-model.number="header.diskon_faktur" min="0" step="500" class="form-input font-bold font-mono" placeholder="0" style="height:42px;border-radius:10px;">
                            </div>
                        </template>
                    </div>

                    <!-- Row Tambahan Jika Skema Pembayaran Sebagian / DP -->
                    <template x-if="!isKonsinyasi && header.tipe_pembayaran === 'sebagian'">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3" style="border-top:1px dashed var(--color-hairline);">
                            <div>
                                <label class="form-label" style="color:#ef4444;font-weight:700;font-size:12px;margin-bottom:6px;">Jatuh Tempo Sisa Tagihan *</label>
                                <input type="date" name="tanggal_jatuh_tempo" x-model="header.tanggal_jatuh_tempo" required class="form-input font-mono" style="height:42px;border-radius:10px;border-color:#ef4444;color:#ef4444;font-weight:700;">
                            </div>
                            <div>
                                <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Diskon Faktur (Rp)</label>
                                <input type="number" name="diskon_faktur" x-model.number="header.diskon_faktur" min="0" step="500" class="form-input font-bold font-mono" placeholder="0" style="height:42px;border-radius:10px;">
                            </div>
                        </div>
                    </template>

                    <!-- Row Diskon Faktur jika Tempo Murni -->
                    <template x-if="!isKonsinyasi && (header.tipe_pembayaran === 'tempo_7_hari' || header.tipe_pembayaran === 'tempo_14_hari' || header.tipe_pembayaran === 'tempo_30_hari')">
                        <div class="pt-2">
                            <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Diskon Faktur (Rp)</label>
                            <input type="number" name="diskon_faktur" x-model.number="header.diskon_faktur" min="0" step="500" class="form-input font-bold font-mono" placeholder="0" style="height:42px;border-radius:10px;">
                        </div>
                    </template>

                </div>

            </div>

            <!-- ===================================================================== -->
            <!-- 3. TABEL ITEM PESANAN                                                 -->
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
                    <button type="button" id="btnAddRow" @click="addItemRow()" class="btn btn-primary btn-sm">
                        <i data-lucide="plus"></i>
                        <span>Tambah Baris Snack</span>
                    </button>
                </div>

                <div class="table-scroll no-scrollbar" style="overflow-x:auto;scrollbar-width:none;-ms-overflow-style:none;">
                    <table class="table responsive-table" style="min-width:860px;width:100%;">
                        <thead>
                            <tr>
                                <th class="cell-center" style="width:36px;">No</th>
                                <th style="min-width:260px;">Produk Snack Siap Jual (<?= count($products) ?> SKU)</th>
                                <th class="cell-center cell-nowrap" style="width:95px;">Stok Gudang</th>
                                <th class="cell-center cell-nowrap" style="width:85px;">Qty (Bks)</th>
                                <th class="cell-right cell-nowrap" style="width:130px;" x-show="!isKonsinyasi">Harga Satuan</th>
                                <th class="cell-right cell-nowrap" style="width:110px;" x-show="!isKonsinyasi">Diskon (Rp)</th>
                                <th class="cell-right cell-nowrap" style="width:135px;" x-show="!isKonsinyasi">Subtotal (Rp)</th>
                                <th class="cell-center" style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, idx) in items" :key="row.uid">
                                <tr :class="'row-uid-' + row.uid">
                                    <!-- No -->
                                    <td class="cell-center cell-nowrap col-no" data-label="No" style="color:var(--color-ink-mute);font-size:12px;font-weight:700;" x-text="idx + 1"></td>

                                    <!-- Produk Dropdown Button -->
                                    <td data-label="Produk Snack">
                                        <button type="button" @click="toggleProductDropdown(row, $event)"
                                                data-nav="product"
                                                :class="'prod-trigger-' + row.uid"
                                                @keydown.enter.stop.prevent="openProductDropdown(row, $event)"
                                                @keydown.space.stop.prevent="openProductDropdown(row, $event)"
                                                @keydown.down.stop.prevent="openProductDropdown(row, $event)"
                                                class="form-input enter-nav flex items-center justify-between w-full text-left"
                                                style="height:36px;font-size:12.5px;font-weight:600;border-radius:8px;cursor:pointer;background:var(--color-canvas);padding:0 10px;">
                                            <span class="truncate" :style="!row.item_id ? 'color:var(--color-ink-mute);font-weight:500;' : 'color:var(--color-ink);'"
                                                  x-text="getSelectedProductName(row.item_id)"></span>
                                            <i data-lucide="chevron-down" style="width:14px;height:14px;flex-shrink:0;transition:transform 0.2s;" :style="activeDropdownRow === row ? 'transform:rotate(180deg)' : ''"></i>
                                        </button>
                                    </td>

                                    <!-- Stok Gudang -->
                                    <td class="cell-center cell-nowrap" data-label="Stok Gudang">
                                        <span class="badge" :class="Number(row.stok_tersedia) > 0 ? 'badge-mono' : 'badge-warning'" style="font-family:var(--font-mono);font-size:11px;font-weight:700;" x-text="formatNumber(row.stok_tersedia) + ' bks'"></span>
                                    </td>

                                    <!-- Qty (Bungkus) -->
                                    <td class="cell-center cell-nowrap" data-label="Qty (Bks)">
                                        <input type="number" min="1" :disabled="!row.item_id"
                                               x-model.number="row.qty"
                                               @input="calcRow(row)"
                                               @focus="$event.target.select()"
                                               @click="$event.target.select()"
                                               data-nav="qty"
                                               class="form-input font-bold text-center enter-nav"
                                               style="height:36px;width:75px;font-size:13px;"
                                               placeholder="1"
                                               @keydown.enter.prevent="onQtyEnter(row, idx)"
                                               @keydown.down.prevent="moveRowVertical(idx, 1, 'qty')"
                                               @keydown.up.prevent="moveRowVertical(idx, -1, 'qty')">
                                    </td>

                                    <!-- Harga Satuan Level (Styled Locked Price Box) -->
                                    <td class="cell-right cell-nowrap" x-show="!isKonsinyasi" data-label="Harga Satuan">
                                        <div class="inline-flex items-center justify-end font-mono font-bold"
                                             style="height:36px;padding:0 10px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:8px;font-size:12.5px;color:var(--color-ink);min-width:105px;width:100%;max-width:125px;"
                                             x-text="row.item_id ? formatRupiah(row.harga) : '-'"
                                             title="Harga satuan deal otomatis berdasarkan level harga toko">
                                        </div>
                                    </td>

                                    <!-- Diskon Item -->
                                    <td class="cell-right cell-nowrap" x-show="!isKonsinyasi" data-label="Diskon (Rp)">
                                        <input type="number" step="any" min="0" :disabled="!row.item_id"
                                               x-model.number="row.diskon"
                                               @input="calcRow(row)"
                                               @focus="$event.target.select()"
                                               @click="$event.target.select()"
                                               data-nav="diskon"
                                               class="form-input font-mono text-right enter-nav no-spinner"
                                               style="height:36px;width:95px;font-size:12.5px;"
                                               placeholder="0"
                                               @keydown.enter.prevent="onDiskonEnter(row, idx)"
                                               @keydown.down.prevent="moveRowVertical(idx, 1, 'diskon')"
                                               @keydown.up.prevent="moveRowVertical(idx, -1, 'diskon')">
                                    </td>

                                    <!-- Subtotal -->
                                    <td class="cell-right cell-nowrap" x-show="!isKonsinyasi" data-label="Subtotal (Rp)">
                                        <div class="inline-flex items-center justify-end font-mono font-black"
                                             style="height:36px;padding:0 8px;font-size:13.5px;color:#1e3a8a;min-width:110px;"
                                             x-text="row.item_id ? formatRupiah(row.subtotal) : 'Rp 0'">
                                        </div>
                                    </td>

                                    <!-- Hapus Baris -->
                                    <td class="cell-center cell-nowrap" data-label="Hapus">
                                        <button type="button" @click="removeItemRow(idx)" class="btn btn-ghost btn-sm text-danger btn-remove-row" style="padding:4px;" title="Hapus baris ini (Ctrl+Del)">
                                             <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                                             <span>Hapus Baris</span>
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
                               style="height:32px;padding-left:30px;font-size:12px;border-radius:8px;width:100%;background:var(--color-canvas);">
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
                            <span class="badge badge-mono" style="font-size:10.5px;font-weight:700;white-space:nowrap;" x-text="'Stok: ' + (p.stok_fisik_saat_ini || 0)"></span>
                        </div>
                    </template>
                    <template x-if="filteredProductList.length === 0">
                        <div style="padding:14px;text-align:center;font-size:12px;color:var(--color-ink-mute);">
                            Produk snack tidak ditemukan
                        </div>
                    </template>
                </div>
            </div>

            <!-- Ringkasan Total Faktur (Executive Billing Summary Card) -->
            <div style="border-top:1.5px solid var(--color-hairline);padding-top:20px;margin-top:16px;display:flex;justify-content:flex-end;">
                <div style="width:100%;max-width:390px;">
                    
                    <!-- KONSINYASI VIEW -->
                    <template x-if="isKonsinyasi">
                        <div style="background:linear-gradient(135deg, rgba(245,158,11,0.08) 0%, rgba(245,158,11,0.02) 100%);border:1.5px solid rgba(245,158,11,0.25);border-radius:16px;padding:18px 20px;box-shadow:0 2px 10px rgba(0,0,0,0.02);">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                                <div style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:800;color:#b45309;">
                                    <i data-lucide="package-check" style="width:18px;height:18px;"></i>
                                    <span>Titip Jual (Konsinyasi)</span>
                                </div>
                                <span class="badge" style="background:#fef3c7;color:#92400e;font-weight:800;font-size:11px;border-radius:6px;padding:2px 8px;">Non-Tagihan</span>
                            </div>
                            <div style="font-size:12.5px;color:var(--color-ink-mute);line-height:1.5;">
                                Total muatan <strong class="text-ink" x-text="items.reduce((s, r) => s + (Number(r.qty) || 0), 0) + ' bungkus snack'"></strong> disalurkan ke rak toko mitra. Nilai piutang akan dihitung otomatis saat opname fisik kunjungan berikutnya.
                            </div>
                        </div>
                    </template>

                    <!-- REGULER B2B VIEW (PREMIUM BILLING SUMMARY CARD) -->
                    <template x-if="!isKonsinyasi">
                        <div style="background:var(--color-surface);border:1px solid var(--color-hairline);border-radius:18px;padding:18px 20px;box-shadow:0 4px 16px rgba(0,0,0,0.03);" class="space-y-3.5">
                            
                            <!-- Header Ringkasan -->
                            <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid var(--color-hairline-soft);">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="width:28px;height:28px;border-radius:8px;background:rgba(37,99,235,0.1);color:#2563eb;display:flex;align-items:center;justify-content:center;">
                                        <i data-lucide="calculator" style="width:15px;height:15px;"></i>
                                    </div>
                                    <span style="font-size:13px;font-weight:800;color:var(--color-ink);">Kalkulasi Pembayaran</span>
                                </div>
                                <span class="badge badge-mono text-[11px]" style="font-weight:700;padding:2px 8px;border-radius:6px;" x-text="items.filter(r => r.item_id).length + ' SKU • ' + items.reduce((s, r) => s + (Number(r.qty) || 0), 0) + ' Bks'"></span>
                            </div>

                            <!-- Baris Rincian Biaya -->
                            <div class="space-y-2.5" style="font-size:12.5px;">
                                <!-- Subtotal Bruto -->
                                <div style="display:flex;justify-content:space-between;align-items:center;color:var(--color-ink-mute);">
                                    <span style="font-weight:600;">Subtotal Bruto:</span>
                                    <span class="font-mono font-bold text-ink" style="font-size:13px;" x-text="'Rp ' + formatRupiah(calcBruto())"></span>
                                </div>

                                <!-- Total Diskon Item -->
                                <div style="display:flex;justify-content:space-between;align-items:center;color:var(--color-ink-mute);">
                                    <span style="font-weight:600;">Total Diskon Item:</span>
                                    <span class="font-mono font-bold" style="font-size:13px;color:#ef4444;" x-text="'- Rp ' + formatRupiah(calcDiskonItem())"></span>
                                </div>

                                <!-- Diskon Faktur Tambahan -->
                                <div style="display:flex;justify-content:space-between;align-items:center;color:var(--color-ink-mute);">
                                    <span style="font-weight:600;">Diskon Faktur Global:</span>
                                    <span class="font-mono font-bold" style="font-size:13px;color:#ef4444;" x-text="'- Rp ' + formatRupiah(header.diskon_faktur || 0)"></span>
                                </div>
                            </div>

                            <!-- Total Netto Highlight Box -->
                            <div style="background:linear-gradient(135deg, rgba(37,99,235,0.08) 0%, rgba(59,130,246,0.03) 100%);border:1.5px solid rgba(37,99,235,0.22);border-radius:14px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:6px;">
                                <div>
                                    <div style="font-size:11px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:#2563eb;">Total Netto Bayar</div>
                                    <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">Kewajiban Tagihan Nota</div>
                                </div>
                                <div class="font-mono font-black text-right" style="font-size:20px;color:#1e3a8a;letter-spacing:-0.02em;" x-text="'Rp ' + formatRupiah(calcNetto())"></div>
                            </div>

                        </div>
                    </template>

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
        whitelistMap: <?= json_encode($whitelistMap ?? [], JSON_UNESCAPED_UNICODE) ?>,
        showAllProducts: false,
        header: {
            tanggal_pesanan: '<?= htmlspecialchars($order['tanggal_pesanan'] ?? date('Y-m-d')) ?>',
            tipe_pembayaran: '<?= htmlspecialchars($order['tipe_pembayaran'] ?? 'cash') ?>',
            tanggal_jatuh_tempo: '<?= htmlspecialchars($order['tanggal_jatuh_tempo'] ?? '') ?>',
            sales_driver_id: '<?= htmlspecialchars($order['sales_driver_id'] ?? '') ?>',
            diskon_faktur: <?= (float)($order['total_diskon'] ?? 0) ?>
        },
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
            if (!itemId) return '-- Pilih Snack & Varian Rasa --';
            const p = this.products.find(x => x.id === itemId);
            if (!p) return '-- Pilih Snack & Varian Rasa --';
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
            const menuWidth = Math.max(340, Math.min(460, rect.width > 200 ? rect.width + 80 : 380));
            let left = rect.left;
            if (left + menuWidth > window.innerWidth - 16) {
                left = window.innerWidth - menuWidth - 16;
            }
            if (left < 16) left = 16;
            
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
                if (typeof toast !== 'undefined' && toast.warning) {
                    toast.warning('Mohon masukkan minimal 1 produk snack dengan Qty valid.');
                } else {
                    alert('Mohon masukkan minimal 1 produk snack dengan Qty valid.');
                }
                return;
            }

            this.isSubmitting = true;
            const actionText = this.isKonsinyasi 
                ? 'Memperbarui Draf Titip Jual...' 
                : 'Memperbarui Pesanan Pelanggan...';

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

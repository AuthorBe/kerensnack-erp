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
            <a href="<?= Router::url('/customer-orders') ?>" class="page-back-btn" title="Kembali ke Daftar Penjualan">
                <i data-lucide="arrow-left"></i>
            </a>
            <div class="page-header-icon is-emerald">
                <i data-lucide="file-plus-2"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot"></span>
                    <span>Penerbitan PO Baru B2B</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Input Purchase Order (PO) Baru' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Penerbitan pesanan mitra toko, grosir & supermarket (Tahap 1: PO)' ?></p>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MAIN FORM                                                                 -->
    <!-- ========================================================================= -->
    <form id="salesOrderForm" data-add-row-btn="#btnAddRow" data-action-text="Menerbitkan Purchase Order..." action="<?= Router::url('/customer-orders/store') ?>" method="POST">
        <input type="hidden" name="items_json" :value="JSON.stringify(items)">
        <input type="hidden" name="print_direct" :value="printDirect ? '1' : '0'">

        <div class="space-y-5">

            <!-- ===================================================================== -->
            <!-- 2. INFORMASI TOKO & TRANSAKSI (2-PANEL BALANCED MULTI-COLOR GRID)     -->
            <!-- ===================================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                <!-- PANEL KIRI: DATA TOKO & LOGISTIK (Modern Material Design 3 Styling) -->
                <div class="card p-4 flex flex-col gap-4">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:1px solid var(--color-hairline);padding-bottom:10px;">
                        <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                            <div style="width:34px;height:34px;border-radius:10px;background:rgba(99,102,241,0.12);color:#6366f1;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="store" style="width:18px;height:18px;"></i>
                            </div>
                            <div>
                                <div style="font-size:13.5px;font-weight:800;color:var(--color-ink);line-height:1.2;">Mitra Toko &amp; Logistik</div>
                                <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">Pilih toko pelanggan tujuan distribusi</div>
                            </div>
                        </div>
                        <template x-if="selectedCustomer">
                            <span class="badge flex items-center gap-1.5" style="background:rgba(99,102,241,0.1);color:#4f46e5;border:1px solid rgba(99,102,241,0.25);font-weight:800;font-size:11.5px;padding:4px 10px;border-radius:999px;white-space:nowrap;">
                                <i data-lucide="tag" style="width:13px;height:13px;"></i> Level <span x-text="selectedCustomer.level_harga"></span>
                            </span>
                        </template>
                    </div>

                    <div>
                        <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Toko Pelanggan *</label>
                        <div class="relative" @click.outside="customerDropdownOpen = false">
                            <input type="hidden" name="pelanggan_id" :value="header.pelanggan_id" required>
                            <!-- Trigger Button -->
                            <button type="button" @click="toggleCustomerDropdown()"
                                    data-nav="customer"
                                    x-ref="customerTrigger"
                                    @keydown.enter.stop.prevent="openCustomerDropdown($event)"
                                    @keydown.space.stop.prevent="openCustomerDropdown($event)"
                                    @keydown.down.stop.prevent="openCustomerDropdown($event)"
                                    class="form-input font-bold flex items-center justify-between w-full text-left enter-nav"
                                    style="height:42px;border-radius:10px;font-size:13.5px;cursor:pointer;background:var(--color-canvas);">
                                <span class="truncate" :style="!selectedCustomer ? 'color:var(--color-ink-mute);font-weight:500;' : 'color:var(--color-ink);'"
                                      x-text="selectedCustomer ? (selectedCustomer.nama_toko + ' (' + selectedCustomer.kode_pelanggan + ' - Lvl ' + selectedCustomer.level_harga + ')') : '-- Pilih Toko Pelanggan --'"></span>
                                <i data-lucide="chevron-down" style="width:16px;height:16px;flex-shrink:0;transition:transform 0.2s;" :style="customerDropdownOpen ? 'transform:rotate(180deg)' : ''"></i>
                            </button>

                            <!-- Dropdown Menu with Search -->
                            <div x-show="customerDropdownOpen" x-cloak
                                 class="dropdown-menu-searchable"
                                 style="position:absolute;top:calc(100% + 4px);left:0;right:0;z-index:1050;border-radius:12px;overflow:hidden;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:0 14px 34px -4px rgba(0,0,0,0.16);">
                                <!-- Search Input Box -->
                                <div style="padding:8px 10px;border-bottom:1px solid var(--color-hairline);background:var(--color-canvas-soft);">
                                    <div style="position:relative;display:flex;align-items:center;">
                                        <i data-lucide="search" style="position:absolute;left:10px;width:14px;height:14px;color:var(--color-ink-mute);pointer-events:none;"></i>
                                        <input type="text" x-ref="customerSearchInput" x-model="customerSearch"
                                               @keydown.down.prevent="navigateCustomer(1)"
                                               @keydown.up.prevent="navigateCustomer(-1)"
                                               @keydown.enter.prevent="selectActiveCustomer()"
                                               @keydown.escape="customerDropdownOpen = false"
                                               placeholder="Cari toko / kode pelanggan / pemilik..."
                                               class="form-input"
                                               style="height:34px;padding-left:32px;font-size:12.5px;border-radius:8px;width:100%;background:var(--color-canvas);">
                                    </div>
                                </div>
                                <!-- Options List -->
                                <div style="max-height:240px;overflow-y:auto;" class="custom-scrollbar" id="cust-list-container">
                                    <template x-for="(c, cIdx) in filteredCustomers" :key="c.id">
                                        <div @click="selectCustomer(c)"
                                             class="searchable-option"
                                             :class="[
                                                 'cust-opt-idx-' + cIdx,
                                                 String(c.id) === String(header.pelanggan_id) ? 'is-selected' : '',
                                                 cIdx === customerActiveIndex ? 'is-active' : ''
                                             ]"
                                             style="padding:9px 12px;font-size:12.5px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:1px solid var(--color-hairline-soft);">
                                            <div style="min-width:0;flex:1;">
                                                <div style="font-weight:700;color:var(--color-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="c.nama_toko"></div>
                                                <div style="font-size:11px;color:var(--color-ink-mute);" x-text="c.kode_pelanggan + (c.nama_pemilik ? ' • ' + c.nama_pemilik : '')"></div>
                                            </div>
                                            <span class="badge badge-mono" style="font-size:10.5px;font-weight:700;flex-shrink:0;" x-text="'Lvl ' + c.level_harga"></span>
                                        </div>
                                    </template>
                                    <template x-if="filteredCustomers.length === 0">
                                        <div style="padding:16px;text-align:center;font-size:12px;color:var(--color-ink-mute);">
                                            Toko pelanggan tidak ditemukan
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Detail Strip (Jika Toko Terpilih) -->
                    <template x-if="selectedCustomer">
                        <div style="padding:10px 12px;border-radius:12px;font-size:12px;display:flex;align-items:center;justify-content:space-between;gap:8px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);color:var(--color-ink);">
                            <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                                <div style="width:26px;height:26px;border-radius:6px;background:rgba(99,102,241,0.1);color:#6366f1;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i data-lucide="tag" style="width:13px;height:13px;"></i>
                                </div>
                                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    Skema: <strong>Level <span x-text="selectedCustomer.level_harga"></span> (<span x-text="selectedCustomer.nama_grup_harga || 'Standar'"></span>)</strong>
                                </span>
                            </div>
                            <template x-if="selectedCustomer.is_konsinyasi">
                                <span style="background:rgba(225,29,72,0.1);color:#e11d48;border:1px solid rgba(225,29,72,0.22);padding:3px 8px;border-radius:6px;font-weight:800;font-size:10.5px;text-transform:uppercase;white-space:nowrap;">
                                    Titip Jual (Konsinyasi)
                                </span>
                            </template>
                            <template x-if="!selectedCustomer.is_konsinyasi">
                                <span style="background:rgba(16,185,129,0.1);color:#059669;border:1px solid rgba(16,185,129,0.22);padding:3px 8px;border-radius:6px;font-weight:800;font-size:10.5px;text-transform:uppercase;white-space:nowrap;">
                                    Penjualan B2B
                                </span>
                            </template>
                        </div>
                    </template>

                    <div>
                        <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Catatan / Keterangan Nota</label>
                        <input type="text" name="catatan" x-model="header.catatan"
                               @keydown.enter.prevent="focusFirstProduct()"
                               class="form-input enter-nav"
                               style="height:42px;border-radius:10px;font-size:13px;"
                               placeholder="Contoh: Titip faktur ke kasir...">
                    </div>

                    <!-- Whitelist Status Banner (Aksen Cyan / Sky) -->
                    <template x-if="selectedCustomer">
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
                    </template>
                </div>

                <!-- PANEL KANAN: DATA FAKTUR & PEMBAYARAN (Aksen Amber / Gold) -->
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
                                <span>No. Faktur (Auto)</span>
                                <span style="font-size:10.5px;color:var(--color-ink-mute);display:flex;align-items:center;gap:3px;"><i data-lucide="lock" style="width:11px;height:11px;"></i> Terkunci</span>
                            </label>
                            <input type="text" name="nomor_nota" :value="header.nomor_nota" readonly class="form-input font-mono font-bold" style="height:42px;border-radius:10px;background:var(--color-canvas-soft);cursor:not-allowed;color:var(--color-ink);letter-spacing:0.02em;">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Tanggal Transaksi *</label>
                            <input type="date" name="tanggal_pesanan" x-model="header.tanggal_pesanan" required class="form-input font-mono" style="height:42px;border-radius:10px;">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;">Skema Pembayaran *</label>
                            
                            <!-- If Konsinyasi -->
                            <template x-if="selectedCustomer?.is_konsinyasi">
                                <div>
                                    <input type="hidden" name="tipe_pembayaran" value="konsinyasi">
                                    <div class="form-input font-semibold flex items-center justify-between" style="height:42px;border-radius:10px;background:rgba(225,29,72,0.06);color:#b91c1c;border-color:rgba(225,29,72,0.2);">
                                        <span>Titip Jual (Konsinyasi)</span>
                                        <i data-lucide="lock" style="width:13px;height:13px;"></i>
                                    </div>
                                </div>
                            </template>

                            <!-- If Reguler B2B -->
                            <template x-if="!selectedCustomer?.is_konsinyasi">
                                <select name="tipe_pembayaran" x-model="header.tipe_pembayaran" @change="onTipePembayaranChange()" class="form-input font-semibold" style="height:42px;border-radius:10px;">
                                    <template x-for="opt in allowedPaymentOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label" :selected="header.tipe_pembayaran === opt.value"></option>
                                    </template>
                                </select>
                            </template>
                        </div>

                        <!-- Conditional: Akun Kas Penerima jika Tunai / QRIS / Transfer / DP -->
                        <template x-if="!selectedCustomer?.is_konsinyasi && (header.tipe_pembayaran === 'cash' || header.tipe_pembayaran === 'qris' || header.tipe_pembayaran === 'transfer' || header.tipe_pembayaran === 'sebagian')">
                            <div>
                                <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;color:#10b981;">
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
                        <template x-if="!selectedCustomer?.is_konsinyasi && (header.tipe_pembayaran === 'tempo_7_hari' || header.tipe_pembayaran === 'tempo_14_hari' || header.tipe_pembayaran === 'tempo_30_hari')">
                            <div>
                                <label class="form-label" style="font-size:12px;font-weight:700;margin-bottom:6px;color:#ef4444;">Tanggal Jatuh Tempo *</label>
                                <input type="date" name="tanggal_jatuh_tempo" x-model="header.tanggal_jatuh_tempo" required class="form-input font-mono" style="height:42px;border-radius:10px;border-color:#ef4444;color:#ef4444;font-weight:700;">
                            </div>
                        </template>
                    </div>

                    <!-- Row Tambahan Jika Skema Pembayaran Sebagian / DP -->
                    <template x-if="!selectedCustomer?.is_konsinyasi && header.tipe_pembayaran === 'sebagian'">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3" style="border-top:1px dashed var(--color-hairline);">
                            <div>
                                <label class="form-label" style="color:#10b981;font-weight:700;font-size:12px;margin-bottom:6px;">Nominal Dibayar Saat Ini (DP) *</label>
                                <input type="number" min="0" step="any" name="nominal_dibayar" x-model.number="header.nominal_dibayar" class="form-input font-mono font-bold" style="height:42px;border-radius:10px;border-color:#10b981;" placeholder="0">
                            </div>
                            <div>
                                <label class="form-label" style="color:#ef4444;font-weight:700;font-size:12px;margin-bottom:6px;">Jatuh Tempo Sisa Tagihan *</label>
                                <input type="date" name="tanggal_jatuh_tempo" x-model="header.tanggal_jatuh_tempo" required class="form-input font-mono" style="height:42px;border-radius:10px;border-color:#ef4444;color:#ef4444;font-weight:700;">
                            </div>
                        </div>
                    </template>

                    <!-- Row Tambahan Jika Skema Pembayaran Sebagian / DP -->
                    <template x-if="!selectedCustomer?.is_konsinyasi && header.tipe_pembayaran === 'sebagian'">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3" style="border-top:1px dashed var(--color-hairline);">
                            <div>
                                <label class="form-label" style="color:#10b981;font-weight:700;font-size:12px;margin-bottom:6px;">Nominal Dibayar Saat Ini (DP) *</label>
                                <input type="number" min="0" step="any" name="nominal_dibayar" x-model.number="header.nominal_dibayar" class="form-input font-mono font-bold" style="height:42px;border-radius:10px;border-color:#10b981;" placeholder="0">
                            </div>
                            <div>
                                <label class="form-label" style="color:#ef4444;font-weight:700;font-size:12px;margin-bottom:6px;">Jatuh Tempo Sisa Tagihan *</label>
                                <input type="date" name="tanggal_jatuh_tempo" x-model="header.tanggal_jatuh_tempo" required class="form-input font-mono" style="height:42px;border-radius:10px;border-color:#ef4444;color:#ef4444;font-weight:700;">
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
                    <button type="button" id="btnAddRow" @click="addItemRow()"
                            :disabled="!selectedCustomer"
                            class="btn btn-primary btn-sm"
                            :style="!selectedCustomer ? 'opacity:0.5;cursor:not-allowed;' : ''">
                        <i data-lucide="plus"></i>
                        <span>Tambah Baris Snack</span>
                    </button>
                </div>

<style>
/* CSS Grid Fallback for Mobile Table */
@media (max-width: 768px) {
    .responsive-table { display: block; width: 100%; min-width: 0 !important; }
    .responsive-table thead { display: none; }
    .responsive-table tbody { display: block; }
    .responsive-table tr { 
        display: flex; flex-direction: column; 
        border: 1px solid var(--color-hairline); 
        border-radius: 8px; margin-bottom: 12px; 
        padding: 12px; background: #fff;
    }
    .responsive-table td { 
        display: flex; justify-content: space-between; align-items: center; 
        padding: 4px 0 !important; border: none !important; text-align: right; 
    }
    .responsive-table td::before { 
        content: attr(data-label); 
        font-weight: 600; font-size: 12px; color: var(--color-ink-mute); 
        text-align: left; margin-right: 12px;
    }
    /* Sembunyikan No. baris atau format ulang */
    .responsive-table td.col-no { display: none; }
    /* Pastikan input selebar mungkin */
    .responsive-table select, .responsive-table input { width: 100%; max-width: 200px; }
}
</style>

                <div class="table-scroll no-scrollbar" style="overflow-x:auto;scrollbar-width:none;-ms-overflow-style:none;">
                    <table class="table responsive-table" style="min-width:860px;width:100%;">
                        <thead>
                            <tr>
                                <th class="cell-center" style="width:36px;">No</th>
                                <th style="min-width:260px;">Produk Snack Siap Jual (<?= count($products) ?> SKU)</th>
                                <th class="cell-center cell-nowrap" style="width:95px;">Stok Gudang</th>
                                <th class="cell-center cell-nowrap" style="width:85px;">Qty (Bks)</th>
                                <th class="cell-right cell-nowrap" style="width:130px;" x-show="!selectedCustomer?.is_konsinyasi">Harga Satuan</th>
                                <th class="cell-right cell-nowrap" style="width:110px;" x-show="!selectedCustomer?.is_konsinyasi">Diskon (Rp)</th>
                                <th class="cell-right cell-nowrap" style="width:135px;" x-show="!selectedCustomer?.is_konsinyasi">Subtotal (Rp)</th>
                                <th class="cell-center" style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Locked State: Toko Belum Dipilih -->
                            <template x-if="!selectedCustomer">
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:48px 20px;background:var(--color-canvas);">
                                        <div style="max-width:440px;margin:0 auto;display:flex;flex-direction:column;align-items:center;gap:10px;">
                                            <div style="width:48px;height:48px;border-radius:12px;background:rgba(99,102,241,0.1);color:#6366f1;display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="store" style="width:24px;height:24px;"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight:800;font-size:14px;color:var(--color-ink);">Pilih Toko Pelanggan Terlebih Dahulu</div>
                                                <div style="font-size:12px;color:var(--color-ink-mute);margin-top:4px;line-height:1.5;">
                                                    Daftar produk dan kalkulasi level harga (B2B / Titip Jual) akan otomatis aktif dan tersinkronisasi setelah toko mitra dipilih.
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Baris Produk Ketika Toko Sudah Dipilih -->
                            <template x-for="(row, idx) in (selectedCustomer ? items : [])" :key="row.uid">
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
                                    <td class="cell-right cell-nowrap" x-show="!selectedCustomer?.is_konsinyasi" data-label="Harga Satuan">
                                        <div class="inline-flex items-center justify-end font-mono font-bold"
                                             style="height:36px;padding:0 10px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:8px;font-size:12.5px;color:var(--color-ink);min-width:105px;width:100%;max-width:125px;"
                                             x-text="row.item_id ? formatRupiah(row.harga) : '-'"
                                             title="Harga satuan deal otomatis berdasarkan level harga toko">
                                        </div>
                                    </td>

                                    <!-- Diskon Item -->
                                    <td class="cell-right cell-nowrap" x-show="!selectedCustomer?.is_konsinyasi" data-label="Diskon (Rp)">
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
                                    <td class="cell-right cell-nowrap" x-show="!selectedCustomer?.is_konsinyasi" data-label="Subtotal (Rp)">
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

                            <template x-if="selectedCustomer && items.length === 0">
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
                <div style="max-height:220px;overflow-y:auto;" class="custom-scrollbar" id="single-product-opt-list">
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
                                <div style="font-size:11px;color:var(--color-ink-mute);" x-text="p.kode_sku + (p.varian_rasa ? ' • ' + p.varian_rasa : '')"></div>
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
            <!-- 4. RINGKASAN TOTAL & ACTION CARDS                                     -->
            <!-- ===================================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Info Petunjuk Kiri -->
                <div class="card p-5 lg:col-span-2 space-y-3">
                    <div style="font-weight:800;font-size:13.5px;color:var(--color-ink);display:flex;align-items:center;gap:6px;">
                        <i data-lucide="info" style="width:16px;height:16px;color:#60a5fa;"></i>
                        <span>Petunjuk Alur Operasional Pesanan:</span>
                    </div>
                    
                    <!-- Kondisi Reguler B2B -->
                    <div x-show="!selectedCustomer?.is_konsinyasi" class="space-y-2">
                        <ul style="font-size:12.5px;color:var(--color-ink-mute);line-height:1.6;list-style:disc;padding-left:18px;" class="space-y-1.5">
                            <li><strong>Tahap 1 (Penerbitan PO)</strong>: Pesanan baru berstatus <strong style="color:var(--color-ink);">PO</strong> dan masuk ke antrean <em>Daftar PO Pelanggan</em>. Stok fisik di gudang <strong>belum berkurang</strong>.</li>
                            <li><strong>Tahap 2 (Proses Gudang &amp; Surat Jalan)</strong>: Staf gudang mengecek kesiapan stok fisik dan menekan <em>"Proses PO ke Siap Kirim"</em> untuk memotong stok gudang &amp; menerbitkan Surat Jalan.</li>
                            <li><strong>Tahap 3 (Pengiriman &amp; Finansial)</strong>: Saat driver menyelesaikan kiriman (status <strong style="color:var(--color-ink);white-space:nowrap;">Selesai Diterima</strong>), penerimaan kas (Tunai/QRIS/Transfer/DP) otomatis masuk ke Buku Kas &amp; sisa tagihan dicatat ke Piutang Toko.</li>
                        </ul>
                    </div>

                    <!-- Kondisi Titip Jual Konsinyasi -->
                    <div x-show="selectedCustomer?.is_konsinyasi" class="space-y-2">
                        <ul style="font-size:12.5px;color:var(--color-ink-mute);line-height:1.6;list-style:disc;padding-left:18px;" class="space-y-1.5">
                            <li><strong>Tahap 1 (Draf Titip Jual)</strong>: Pesanan dicatat sebagai surat jalan distribusi titipan ke rak toko mitra tanpa tagihan langsung.</li>
                            <li><strong>Tahap 2 (Distribusi Fisik)</strong>: Barang diproses gudang dan dikirim oleh sales/driver ke lokasi toko mitra.</li>
                            <li><strong>Tahap 3 (Stok Rak Konsinyasi)</strong>: Saat status <strong style="color:var(--color-ink);white-space:nowrap;">Selesai Diterima</strong>, kuantiti otomatis menambah <em>Stok Titip Konsinyasi Toko</em>. Penagihan omzet dilakukan saat kunjungan opname berkala.</li>
                        </ul>
                    </div>
                </div>

                <!-- Rincian Total Kanan -->
                <div class="card p-5 space-y-3">
                    
                    <div x-show="selectedCustomer?.is_konsinyasi" style="padding:16px;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;display:flex;flex-direction:column;gap:8px;">
                        <div style="font-weight:800;color:#b91c1c;font-size:14px;display:flex;align-items:center;gap:6px;">
                            <i data-lucide="shield-alert" style="width:18px;height:18px;"></i>
                            Mode Titip Konsinyasi Aktif
                        </div>
                        <div style="font-size:12px;color:#991b1b;line-height:1.5;">
                            Harga jual deal disembunyikan. Pengiriman ini dicatat sebagai <strong>surat jalan titipan barang</strong>. Nilai persediaan internal menggunakan HPP.
                        </div>
                    </div>

                    <div x-show="!selectedCustomer?.is_konsinyasi">
                        <div class="flex justify-between items-center" style="font-size:13px;color:var(--color-ink-secondary);">
                            <span>Subtotal Bruto:</span>
                            <span class="font-mono font-bold" x-text="formatRupiah(calcBruto())"></span>
                        </div>

                        <div class="flex justify-between items-center" style="font-size:13px;color:var(--color-ink-secondary);margin-top:12px;">
                            <span>Total Diskon Item:</span>
                            <span class="font-mono font-semibold" style="color:#f87171;" x-text="'-' + formatRupiah(calcDiskonItem())"></span>
                        </div>

                        <div class="flex justify-between items-center gap-2" style="margin-top:12px;">
                            <span style="font-size:13px;color:var(--color-ink-secondary);">Diskon Faktur (Rp):</span>
                            <input type="text" name="diskon_faktur" x-model="diskon_faktur_display" @input="onDiskonFakturInput($event)" class="form-input font-mono text-right" style="height:32px;width:120px;font-size:12px;" placeholder="0">
                        </div>

                        <div style="border-top:2px solid var(--color-hairline);padding-top:10px;margin-top:12px;" class="flex justify-between items-center">
                            <span style="font-size:14px;font-weight:900;color:var(--color-ink);">TOTAL NETTO:</span>
                            <span class="font-mono font-black" style="font-size:22px;color:var(--color-ink);" x-text="formatRupiah(calcNetto())"></span>
                        </div>

                        <!-- Breakdown Khusus Bayar Sebagian (DP) -->
                        <template x-if="header.tipe_pembayaran === 'sebagian'">
                            <div class="p-3 rounded-lg space-y-1.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);font-size:12px;margin-top:12px;">
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
                    </div>

                    <div class="pt-2 flex flex-col gap-2">
                        <button type="button" @click="submitOrder(false)" :disabled="isSubmitting || !selectedCustomer || items.length === 0" class="btn btn-primary w-full" style="font-weight:700;height:42px;border-radius:10px;">
                            <i data-lucide="inbox"></i>
                            <span x-text="isSubmitting ? 'Menerbitkan...' : 'Terbitkan PO (Ctrl+Enter)'"></span>
                        </button>
                        <button type="button" @click="submitOrder(true)" :disabled="isSubmitting || !selectedCustomer || items.length === 0" class="btn btn-secondary w-full" style="font-weight:700;height:40px;border-radius:10px;">
                            <i data-lucide="printer"></i>
                            <span>Terbitkan &amp; Cetak Picking List</span>
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
            tipe_pembayaran: 'cash',
            tanggal_jatuh_tempo: '<?= date('Y-m-d', strtotime('+14 days')) ?>',
            akun_kas_id: '<?= !empty($cashAccounts) ? $cashAccounts[0]['id'] : '' ?>',
            nominal_dibayar: 0,
            diskon_faktur: 0,
            catatan: ''
        },
        diskon_faktur_display: '',

        items: [],
        selectedCustomer: null,
        showAllProducts: false,
        isSubmitting: false,
        printDirect: false,

        // Dropdown Searchable Toko
        customerDropdownOpen: false,
        customerSearch: '',
        customerActiveIndex: 0,

        get filteredCustomers() {
            const q = (this.customerSearch || '').toLowerCase().trim();
            if (!q) return this.customers;
            return this.customers.filter(c => {
                const name = (c.nama_toko || '').toLowerCase();
                const code = (c.kode_pelanggan || '').toLowerCase();
                const owner = (c.nama_pemilik || '').toLowerCase();
                return name.includes(q) || code.includes(q) || owner.includes(q);
            });
        },

        navigateCustomer(dir) {
            const max = this.filteredCustomers.length - 1;
            if (max < 0) return;
            this.customerActiveIndex = Math.max(0, Math.min(max, (this.customerActiveIndex || 0) + dir));
            this.scrollToActive('cust-opt-idx-' + this.customerActiveIndex, 'cust-list-container');
        },

        selectActiveCustomer() {
            if (this.filteredCustomers.length > 0) {
                const idx = (this.customerActiveIndex >= 0 && this.customerActiveIndex < this.filteredCustomers.length) ? this.customerActiveIndex : 0;
                this.selectCustomer(this.filteredCustomers[idx]);
            }
        },

        selectCustomer(c) {
            this.header.pelanggan_id = c.id;
            this.customerDropdownOpen = false;
            this.customerSearch = '';
            this.customerActiveIndex = 0;
            this.onCustomerChange();
            this.$nextTick(() => {
                const catatanInp = document.querySelector('input[name="catatan"]');
                if (catatanInp) {
                    catatanInp.focus();
                } else {
                    this.focusFirstProduct();
                }
            });
        },

        openCustomerDropdown(event) {
            this.closeProductDropdown();
            this.customerDropdownOpen = true;
            this.customerSearch = '';
            this.customerActiveIndex = 0;
            this.$nextTick(() => {
                this.$refs.customerSearchInput?.focus();
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        toggleCustomerDropdown() {
            this.items.forEach(r => r.dropdownOpen = false);
            this.customerDropdownOpen = !this.customerDropdownOpen;
            if (this.customerDropdownOpen) {
                this.customerSearch = '';
                this.customerActiveIndex = 0;
                this.$nextTick(() => {
                    this.$refs.customerSearchInput?.focus();
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        focusFirstProduct() {
            if (!this.selectedCustomer) {
                this.openCustomerDropdown();
                return;
            }
            this.closeProductDropdown();
            setTimeout(() => {
                const firstProductBtn = document.querySelector('tbody tr:first-child [data-nav="product"]');
                if (firstProductBtn) {
                    firstProductBtn.focus();
                }
            }, 40);
        },

        // Single Floating Dropdown State
        activeDropdownRow: null,
        productSearch: '',
        activeProductIndex: 0,
        dropdownCoords: { top: 0, left: 0, width: 380 },

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
            if (!this.selectedCustomer) {
                this.openCustomerDropdown();
                return;
            }
            this.customerDropdownOpen = false;
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
            if (!this.selectedCustomer) {
                this.openCustomerDropdown();
                return;
            }
            this.customerDropdownOpen = false;
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
            this.scrollToActive('prod-opt-idx-' + this.activeProductIndex, 'single-product-opt-list');
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

        onQtyEnter(row, idx) {
            this.closeProductDropdown();
            const isKonsinyasi = this.selectedCustomer?.is_konsinyasi;
            const tr = document.querySelector('.row-uid-' + row.uid) || document.querySelectorAll('tbody tr')[idx];
            if (!isKonsinyasi) {
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
            if (this.header.pelanggan_id) {
                this.onCustomerChange();
            }
            if (this.selectedCustomer && this.items.length === 0) {
                this.addItemRow();
            }
            window.addEventListener('scroll', () => {
                if (this.customerDropdownOpen) this.customerDropdownOpen = false;
                if (this.activeDropdownRow !== null) this.closeProductDropdown();
            }, { passive: true });
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        onDiskonFakturInput(e) {
            let val = e.target.value.replace(/[^0-9]/g, '');
            if (!val) val = '0';
            this.header.diskon_faktur = parseInt(val, 10);
            this.diskon_faktur_display = this.formatRupiah(this.header.diskon_faktur).replace('Rp ', '');
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
                if (this.selectedCustomer.is_konsinyasi) {
                    this.header.tipe_pembayaran = 'konsinyasi';
                } else if (this.selectedCustomer.tipe_pembayaran_default) {
                    this.header.tipe_pembayaran = this.selectedCustomer.tipe_pembayaran_default;
                } else {
                    this.header.tipe_pembayaran = 'cash'; // Fallback to cash if no default
                }
                this.header.sales_driver_id = this.selectedCustomer.sales_driver_id || '';
                this.onTipePembayaranChange();

                // If items array is empty, auto-add first row
                if (this.items.length === 0) {
                    this.addItemRow();
                } else {
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
            } else {
                this.header.sales_driver_id = '';
                this.items = [];
            }

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        get allowedPaymentOptions() {
            if (!this.selectedCustomer) {
                return [
                    { value: 'cash', label: 'Tunai (Lunas 100%)' },
                    { value: 'qris', label: 'QRIS (Non-Tunai Lunas)' },
                    { value: 'transfer', label: 'Transfer Bank (Lunas)' },
                    { value: 'sebagian', label: 'Kredit / Bayar Sebagian (DP)' }
                ];
            }
            if (this.selectedCustomer.is_konsinyasi) {
                return [{ value: 'konsinyasi', label: 'Titip Jual (Konsinyasi)' }];
            }
            const def = this.selectedCustomer.tipe_pembayaran_default || 'cash';
            if (def === 'cash') {
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
                { value: 'sebagian', label: 'Kredit / Bayar Sebagian (DP)' }
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

        getPaymentSchemeLabel(type) {
            const labels = {
                'cash': 'Tunai (Lunas 100%)',
                'qris': 'QRIS (Non-Tunai Lunas)',
                'transfer': 'Transfer Bank (Lunas)',
                'sebagian': 'Kredit / Bayar Sebagian (DP)',
                'tempo_7_hari': 'Tempo 7 Hari',
                'tempo_14_hari': 'Tempo 14 Hari',
                'tempo_30_hari': 'Tempo 30 Hari',
                'konsinyasi': 'Titip Jual (Konsinyasi)'
            };
            return labels[type] || 'Tunai (Lunas 100%)';
        },

        getPriceForProduct(itemId) {
            if (!itemId) return 0;
            const product = this.products.find(p => p.id === itemId);
            if (!product) return 0;
            const level = this.selectedCustomer ? (Number(this.selectedCustomer.level_harga) || 1) : 1;
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

        addItemRow() {
            if (!this.selectedCustomer) {
                this.toggleCustomerDropdown();
                return;
            }
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

            const validItems = this.items.filter(r => r.item_id && Number(r.qty) > 0);
            if (validItems.length === 0) {
                toast.warning('Mohon masukkan minimal 1 produk snack dengan Qty valid.');
                return;
            }

            this.printDirect = print;
            this.isSubmitting = true;

            const actionText = this.selectedCustomer?.is_konsinyasi 
                ? (print ? 'Menerbitkan & Menyiapkan Cetak Draf...' : 'Menerbitkan Draf Titip Jual...') 
                : (print ? 'Menerbitkan & Menyiapkan Cetak PO...' : 'Menerbitkan Purchase Order...');

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
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

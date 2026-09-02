<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="opnameApp()" class="space-y-4 sm:space-y-6 pb-28">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-header-body">
            <a href="<?= Router::url('/consignment/stok-rak') ?>" class="btn btn-secondary btn-sm p-2 rounded-xl" title="Kembali ke Stok Rak">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#10b981;"></span>
                    <span>Opname Rak Toko • <?= htmlspecialchars($customer['kode_pelanggan'] ?? 'TOKO') ?></span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl"><?= htmlspecialchars($customer['nama_toko']) ?></h1>
                <p class="page-subtitle text-xs sm:text-sm"><?= htmlspecialchars($customer['alamat_lengkap'] ?? 'Alamat belum diatur') ?></p>
            </div>
        </div>
        <div class="page-header-actions flex items-center gap-2">
            <?php if (!empty($customer['nomor_whatsapp'])): ?>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $customer['nomor_whatsapp']) ?>" target="_blank" class="btn btn-secondary btn-sm flex items-center gap-1.5" style="color:#10b981;">
                <i data-lucide="phone" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Hubungi Toko</span>
                <span class="sm:hidden">WA</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- BANNER KIRIMAN MASUK INTERAKTIF (JIKA ADA SJ SEDANG_DIKIRIM) -->
    <?php if (!empty($incomingDeliveries)): ?>
    <div class="space-y-3">
        <?php foreach ($incomingDeliveries as $deliv): ?>
        <div class="card p-3.5 sm:p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="border:1px solid var(--color-hairline);">
            <div class="flex items-center gap-3">
                <i data-lucide="truck" class="w-6 h-6 flex-shrink-0" style="color:#f59e0b;"></i>
                <div>
                    <div class="text-xs sm:text-sm font-black" style="color:var(--color-ink);">
                        🚚 Kiriman Menuju Toko: <?= htmlspecialchars($deliv['nomor_surat_jalan']) ?>
                    </div>
                    <div class="text-[11px] sm:text-xs mt-0.5" style="color:var(--color-ink-mute);">
                        Membawa <strong><?= $deliv['total_sku'] ?> SKU (<?= number_format((float)$deliv['total_pcs']) ?> pcs)</strong> barang titipan baru.
                    </div>
                </div>
            </div>

            <form action="<?= Router::url('/consignment/konfirmasi-terima') ?>" method="POST"
                  data-confirm="Konfirmasi bahwa barang kiriman surat jalan <?= htmlspecialchars($deliv['nomor_surat_jalan']) ?> sudah sampai dan diterima di rak toko?"
                  data-confirm-title="Konfirmasi Terima Barang di Rak"
                  data-confirm-type="warning"
                  data-confirm-icon="check-circle"
                  data-confirm-btn="Ya, Sudah Diterima"
                  class="w-full sm:w-auto">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="surat_jalan_id" value="<?= htmlspecialchars((string)$deliv['surat_jalan_id']) ?>">
                <input type="hidden" name="redirect_url" value="<?= Router::url('/consignment/opname?pelanggan_id=' . urlencode((string)$customer['id'])) ?>">
                <button type="submit" class="btn btn-primary btn-sm w-full sm:w-auto flex items-center justify-center gap-1.5 shadow-md" style="background:#f59e0b;border-color:#f59e0b;color:#090d16;font-weight:800;padding:8px 14px;border-radius:10px;">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <span>Konfirmasi Barang Diterima Toko</span>
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- JIKA TOKO BELUM PERNAH ADA TITIPAN -->
    <?php if (empty($items)): ?>
    <div class="card p-8 sm:p-12 text-center rounded-3xl" style="border:1px solid var(--color-hairline);">
        <i data-lucide="package-open" class="w-12 h-12 mx-auto mb-3 text-slate-400" style="color:var(--color-ink-mute);"></i>
        <h3 class="text-base sm:text-lg font-bold" style="color:var(--color-ink);">Toko Ini Belum Memiliki Barang Titipan</h3>
        <p class="text-xs sm:text-sm mt-1 max-w-md mx-auto" style="color:var(--color-ink-mute);">
            Belum ada data saldo rak yang tercatat untuk toko ini. Buat pesanan pengiriman titip jual pertama kali melalui menu Customer Orders.
        </p>
        <a href="<?= Router::url('/customer-orders/create') ?>" class="btn btn-primary mt-4 inline-flex items-center gap-2">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>Buat Pengiriman Pertama</span>
        </a>
    </div>
    <?php else: ?>

    <!-- PANDUAN PENGISIAN OPNAME (CLEAN GUIDANCE) -->
    <div class="p-3.5 sm:p-4 rounded-2xl flex items-start gap-2.5 sm:gap-3" style="border:1px solid var(--color-hairline);background:var(--color-surface);">
        <i data-lucide="info" class="w-5 h-5 flex-shrink-0 mt-0.5" style="color:#0284c7;"></i>
        <div style="font-size:11.5px;color:var(--color-ink-secondary);line-height:1.45;">
            <strong style="color:var(--color-ink);">Panduan Pengisian:</strong>
            <span> Cukup masukkan <strong>Sisa Fisik di Rak</strong> yang Anda hitung. Sistem otomatis menghitung <strong>Laku Terjual = Stok Titip - (Sisa + Retur)</strong>.</span>
            <span class="block text-[10.5px] mt-0.5" style="color:var(--color-ink-mute);">
                * Isi <em>Retur Bagus</em> jika ditarik ke gudang. Isi <em>Retur Rusak</em> jika produk BS/pecah/expired (kerugian HPP).
            </span>
        </div>
    </div>

    <!-- FORM OPNAME TABLE / CARDS -->
    <form id="opnameForm" action="<?= Router::url('/consignment/opname/proses') ?>" method="POST" class="space-y-4">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="pelanggan_id" value="<?= htmlspecialchars((string)$customer['id']) ?>">
        <input type="hidden" name="items_json" :value="JSON.stringify(items)">

        <!-- SKU CARDS LIST -->
        <div class="space-y-3.5 sm:space-y-4">
            <template x-for="(item, index) in items" :key="item.item_id">
                <div class="card p-4 sm:p-5 rounded-2xl transition-all"
                     style="border:1px solid var(--color-hairline);"
                     :style="itemWarning(item) ? 'border-color:#f43f5e !important;background:rgba(244,63,94,0.03);' : ''">
                    
                    <!-- SKU HEADER & CLEAN TYPOGRAPHY -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-3 pb-3 mb-3.5" style="border-bottom:1px solid var(--color-hairline);">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-mono text-[11px] font-bold text-slate-400" x-text="'[' + item.kode_sku + ']'"></span>
                                <template x-if="item.stok_titip_saat_ini === 0">
                                    <span style="font-size:9.5px;font-weight:800;color:#f43f5e;">(Stok Kosong)</span>
                                </template>
                            </div>
                            <h4 class="text-sm sm:text-base font-bold mt-0.5" style="color:var(--color-ink);" x-text="item.nama_item"></h4>
                        </div>

                        <!-- METRICS CLEAN TYPOGRAPHY -->
                        <div class="flex items-center gap-3 sm:gap-4 text-xs">
                            <div>
                                <span style="color:var(--color-ink-mute);font-size:11px;">Stok Titip:</span>
                                <strong class="ml-1 font-bold text-sky-600 dark:text-sky-400" style="font-size:12.5px;" x-text="item.stok_titip_saat_ini + ' ' + item.satuan_dasar"></strong>
                            </div>
                            <div>
                                <span style="color:var(--color-ink-mute);font-size:11px;">Harga Deal:</span>
                                <strong class="ml-1 font-bold" style="color:var(--color-ink);font-size:12.5px;" x-text="formatRupiah(item.harga_deal)"></strong>
                            </div>
                        </div>
                    </div>

                    <!-- INPUT FIELDS GRID (CLEAN SEPARATED UNIT BOX - NO OVERLAP) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                        <!-- SISA FISIK DI RAK -->
                        <div>
                            <label class="block text-xs font-bold mb-1" style="color:var(--color-ink);">
                                Sisa Fisik di Rak <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-center rounded-xl overflow-hidden" 
                                 style="border:1px solid var(--color-hairline);background:var(--color-surface);"
                                 :style="itemWarning(item) ? 'border-color:#f43f5e;' : ''">
                                <input type="number" 
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       min="0"
                                       x-model.number="item.sisa_fisik_di_rak"
                                       @input="item.is_touched = true; calculateLaku(item)"
                                       class="w-full bg-transparent border-none px-3.5 py-2.5 font-bold text-base focus:outline-none"
                                       style="height:44px;color:var(--color-ink);"
                                       :style="itemWarning(item) ? 'color:#f43f5e;' : ''">
                                <span class="px-3 text-xs font-bold select-none border-l flex items-center justify-center" 
                                      style="color:var(--color-ink-mute);border-color:var(--color-hairline);background:var(--color-canvas);height:44px;min-width:44px;" 
                                      x-text="item.satuan_dasar"></span>
                            </div>
                            <span class="text-[10px] sm:text-[10.5px] mt-1 block" style="color:var(--color-ink-mute);">Jumlah produk yang masih ada di rak toko.</span>
                        </div>

                        <!-- RETUR BAGUS (BAWA PULANG GUDANG) -->
                        <div>
                            <label class="block text-xs font-bold mb-1" style="color:var(--color-ink);">
                                Retur Bagus (Tarik Gudang)
                            </label>
                            <div class="flex items-center rounded-xl overflow-hidden" 
                                 style="border:1px solid var(--color-hairline);background:var(--color-surface);">
                                <input type="number" 
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       min="0"
                                       x-model.number="item.retur_bagus"
                                       @input="item.is_touched = true; calculateLaku(item)"
                                       class="w-full bg-transparent border-none px-3.5 py-2.5 font-bold text-base focus:outline-none"
                                       style="height:44px;color:var(--color-ink);">
                                <span class="px-3 text-xs font-bold select-none border-l flex items-center justify-center" 
                                      style="color:var(--color-ink-mute);border-color:var(--color-hairline);background:var(--color-canvas);height:44px;min-width:44px;" 
                                      x-text="item.satuan_dasar"></span>
                            </div>
                            <span class="text-[10px] sm:text-[10.5px] mt-1 block" style="color:var(--color-ink-mute);">Barang bagus dibawa pulang ke gudang.</span>
                        </div>

                        <!-- RETUR RUSAK / BS (KERUGIAN) -->
                        <div>
                            <label class="block text-xs font-bold mb-1" style="color:var(--color-ink);">
                                Retur Rusak / Bocor / Expired
                            </label>
                            <div class="flex items-center rounded-xl overflow-hidden" 
                                 style="border:1px solid var(--color-hairline);background:var(--color-surface);"
                                 :style="item.retur_rusak > 0 ? 'border-color:#f43f5e;background:rgba(244,63,94,0.03);' : ''">
                                <input type="number" 
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       min="0"
                                       x-model.number="item.retur_rusak"
                                       @input="item.is_touched = true; calculateLaku(item)"
                                       class="w-full bg-transparent border-none px-3.5 py-2.5 font-bold text-base focus:outline-none"
                                       style="height:44px;color:#f43f5e;">
                                <span class="px-3 text-xs font-bold select-none border-l flex items-center justify-center" 
                                      style="color:var(--color-ink-mute);border-color:var(--color-hairline);background:var(--color-canvas);height:44px;min-width:44px;" 
                                      x-text="item.satuan_dasar"></span>
                            </div>
                            <span class="text-[10px] sm:text-[10.5px] mt-1 block" style="color:var(--color-ink-mute);">Barang rusak diakui sebagai kerugian HPP.</span>
                        </div>
                    </div>

                    <!-- REALTIME CALCULATION PREVIEW FOOTER -->
                    <div class="mt-3.5 pt-2.5 flex items-center justify-between flex-wrap gap-2 text-xs" style="border-top:1px solid var(--color-hairline);">
                        <div>
                            <template x-if="itemWarning(item)">
                                <div style="display:inline-flex;align-items:center;gap:4px;color:#f43f5e;font-weight:800;font-size:11px;">
                                    <i data-lucide="alert-circle" style="width:14px;height:14px;"></i>
                                    <span>Melebihi stok titip (<span x-text="item.stok_titip_saat_ini"></span> pcs)!</span>
                                </div>
                            </template>
                            <template x-if="!itemWarning(item)">
                                <div style="display:inline-flex;align-items:center;gap:4px;">
                                    <span style="color:var(--color-ink-mute);font-size:11px;">Laku Terjual:</span>
                                    <strong style="font-size:12.5px;"
                                            :style="item.jumlah_laku > 0 ? 'color:#10b981;' : 'color:var(--color-ink);'"
                                            x-text="item.jumlah_laku + ' ' + item.satuan_dasar"></strong>
                                </div>
                            </template>
                        </div>

                        <div class="flex items-center gap-1">
                            <span style="color:var(--color-ink-mute);font-size:11px;">Subtotal:</span>
                            <strong style="font-size:13.5px;font-weight:900;" 
                                    :style="item.jumlah_laku > 0 ? 'color:#10b981;' : 'color:var(--color-ink);'" 
                                    x-text="formatRupiah(item.jumlah_laku * item.harga_deal)"></strong>
                        </div>
                    </div>

                </div>
            </template>
        </div>

        <!-- CATATAN OPSIONAL -->
        <div class="card p-4 sm:p-5 rounded-2xl" style="border:1px solid var(--color-hairline);">
            <label class="block text-xs font-bold mb-1.5" style="color:var(--color-ink);">
                Catatan Kunjungan Lapangan (Opsional):
            </label>
            <textarea name="catatan" rows="2" placeholder="Tuliskan catatan kondisi rak, pemilik toko, atau kendala lapangan..." class="form-input w-full p-2.5 sm:p-3 text-xs" style="border-radius:12px;"></textarea>
        </div>

        <!-- ========================================================================= -->
        <!-- SUMMARY REAL-TIME PREVIEW BAR (DI BAWAH FORM - CLEAN & NON-OVERLAPPING)  -->
        <!-- ========================================================================= -->
        <div class="card p-4 sm:p-6 rounded-3xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm" 
             style="border:1px solid var(--color-hairline);background:var(--color-card);">
            
            <div class="flex items-center justify-between sm:justify-start gap-4 sm:gap-6 flex-wrap">
                <div>
                    <span class="text-[10.5px] sm:text-xs font-bold uppercase tracking-wider block" style="color:var(--color-ink-mute);">TOTAL ESTIMASI LAKU:</span>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-500 mt-0.5" x-text="formatRupiah(grandTotalLakuRp)">Rp 0</div>
                </div>

                <div class="h-10 w-px bg-slate-200 dark:bg-slate-800 hidden sm:block"></div>

                <div class="flex items-center gap-4 text-xs">
                    <div>
                        <span style="color:var(--color-ink-mute);font-size:11px;" class="block">Total Qty Laku:</span>
                        <strong style="color:var(--color-ink);font-size:14px;" class="font-black" x-text="grandTotalLakuPcs + ' pcs'">0 pcs</strong>
                    </div>
                    <div>
                        <span style="color:var(--color-ink-mute);font-size:11px;" class="block">Total Retur Rusak:</span>
                        <strong style="color:#f43f5e;font-size:14px;" class="font-black" x-text="grandTotalRusakPcs + ' pcs'">0 pcs</strong>
                    </div>
                </div>
            </div>

            <button type="button" 
                    @click="openConfirmModal()"
                    class="btn btn-primary w-full sm:w-auto px-8 py-3 rounded-2xl font-black flex items-center justify-center gap-2 shadow-lg text-sm sm:text-base"
                    style="background:#10b981;border-color:#10b981;color:#fff;">
                <i data-lucide="send" class="w-4 h-4"></i>
                <span>Submit Opname</span>
            </button>
        </div>

    </form>

    <!-- MODAL KONFIRMASI SUBMIT (CLEAN ERP DESIGN & TELEPORT) -->
    <template x-teleport="body">
        <div x-show="showConfirmModal" 
             x-cloak 
             class="modal-backdrop"
             @click.self="showConfirmModal = false"
             @keydown.escape.window="showConfirmModal = false">
            <div class="modal-box p-6 rounded-3xl space-y-4" style="max-width: 440px; border: 1px solid var(--color-hairline);">
                <div class="flex items-center justify-between pb-3 border-b" style="border-color: var(--color-hairline);">
                    <div class="flex items-center gap-2">
                        <i data-lucide="clipboard-check" class="w-5 h-5 text-emerald-500"></i>
                        <h3 class="text-base font-black" style="color: var(--color-ink);">Konfirmasi Hasil Opname</h3>
                    </div>
                    <button type="button" @click="showConfirmModal = false" class="btn btn-ghost btn-xs p-1" style="color: var(--color-ink-mute);">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <p class="text-xs" style="color: var(--color-ink-secondary);">
                    Pastikan hasil hitungan fisik rak toko <strong><?= htmlspecialchars($customer['nama_toko']) ?></strong> sudah benar:
                </p>

                <!-- WARNING JIKA NIHIL PENJUALAN -->
                <template x-if="grandTotalLakuPcs === 0 && grandTotalRusakPcs === 0">
                    <div class="p-3 rounded-2xl flex items-start gap-2.5" style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); color: #f59e0b;">
                        <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                        <div class="text-xs">
                            <strong>Perhatian (Nihil Penjualan):</strong>
                            <div class="text-[11px] mt-0.5" style="color: var(--color-ink-secondary);">Semua stok fisik rak tercatat utuh. Tidak ada faktur tagihan yang akan diterbitkan.</div>
                        </div>
                    </div>
                </template>

                <!-- SUMMARY CARD -->
                <div class="p-4 rounded-2xl space-y-2.5 text-xs" style="background: var(--color-surface); border: 1px solid var(--color-hairline);">
                    <div class="flex justify-between items-center">
                        <span style="color: var(--color-ink-mute);">Total Barang Laku:</span>
                        <strong class="text-emerald-500 font-bold" style="font-size: 13px;" x-text="grandTotalLakuPcs + ' pcs (' + formatRupiah(grandTotalLakuRp) + ')'"></strong>
                    </div>
                    <div class="flex justify-between items-center">
                        <span style="color: var(--color-ink-mute);">Total Retur Rusak (BS):</span>
                        <strong class="text-rose-500 font-bold" style="font-size: 13px;" x-text="grandTotalRusakPcs + ' pcs'"></strong>
                    </div>
                    <div class="flex justify-between items-center">
                        <span style="color: var(--color-ink-mute);">Total Retur Bagus:</span>
                        <strong class="font-bold" style="color: var(--color-ink);" x-text="grandTotalBagusPcs + ' pcs'"></strong>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t" style="border-color: var(--color-hairline);">
                        <span style="color: var(--color-ink-mute);">Status Faktur:</span>
                        <strong class="font-bold" style="color: var(--color-ink);" x-text="grandTotalLakuRp > 0 ? 'Faktur Otomatis Terbit' : 'Tidak Diterbitkan'"></strong>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                    <button type="button" @click="showConfirmModal = false" class="btn btn-secondary rounded-xl py-2.5 font-bold text-xs">
                        Periksa Lagi
                    </button>
                    <button type="button" @click="submitForm()" class="btn btn-primary rounded-xl py-2.5 font-bold text-xs shadow-md" style="background: #10b981; border-color: #10b981; color: #fff;">
                        Ya, Simpan Opname
                    </button>
                </div>
            </div>
        </div>
    </template>

    <?php endif; ?>

</div>

<script>
function opnameApp() {
    return {
        searchQuery: '',
        showConfirmModal: false,
        items: <?= json_encode($items ?? []) ?>,

        init() {
            this.items.forEach(item => this.calculateLaku(item));
        },

        calculateLaku(item) {
            const titip = parseInt(item.stok_titip_saat_ini) || 0;
            const sisa = parseInt(item.sisa_fisik_di_rak) || 0;
            const returBagus = parseInt(item.retur_bagus) || 0;
            const returRusak = parseInt(item.retur_rusak) || 0;

            const laku = titip - (sisa + returBagus + returRusak);
            item.jumlah_laku = laku;
            item.subtotal_laku = laku > 0 ? (laku * item.harga_deal) : 0;
        },

        itemWarning(item) {
            const sisa = parseInt(item.sisa_fisik_di_rak) || 0;
            const returBagus = parseInt(item.retur_bagus) || 0;
            const returRusak = parseInt(item.retur_rusak) || 0;
            const total = sisa + returBagus + returRusak;
            return total > parseInt(item.stok_titip_saat_ini);
        },

        get hasAnyWarning() {
            return this.items.some(item => this.itemWarning(item));
        },

        get hasAnyTouched() {
            return this.items.some(item => item.is_touched);
        },

        get grandTotalLakuPcs() {
            return this.items.reduce((sum, i) => sum + Math.max(0, parseInt(i.jumlah_laku) || 0), 0);
        },

        get grandTotalRusakPcs() {
            return this.items.reduce((sum, i) => sum + Math.max(0, parseInt(i.retur_rusak) || 0), 0);
        },

        get grandTotalBagusPcs() {
            return this.items.reduce((sum, i) => sum + Math.max(0, parseInt(i.retur_bagus) || 0), 0);
        },

        get grandTotalLakuRp() {
            return this.items.reduce((sum, i) => sum + (i.jumlah_laku > 0 ? (i.jumlah_laku * i.harga_deal) : 0), 0);
        },

        openConfirmModal() {
            if (this.items.length === 0) {
                if (window.toast) {
                    window.toast.error('Toko ini belum memiliki item barang titipan di rak untuk diopname.');
                }
                return;
            }

            if (this.hasAnyWarning) {
                if (window.toast) {
                    window.toast.error('Jumlah sisa fisik + retur tidak boleh melebihi stok titip rak awal.');
                }
                return;
            }

            // Validasi: Cegah submit jika data masih kosong / belum dihitung fisik sama sekali
            if (!this.hasAnyTouched && this.grandTotalLakuPcs === 0 && this.grandTotalRusakPcs === 0 && this.grandTotalBagusPcs === 0) {
                if (window.toast) {
                    window.toast.warning('Data Opname Belum Diisi! Silakan masukkan hasil hitung Sisa Fisik di Rak terlebih dahulu.');
                }
                return;
            }

            this.showConfirmModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        submitForm() {
            const form = document.getElementById('opnameForm');
            if (form) form.submit();
        },

        formatRupiah(amount) {
            const val = parseFloat(amount) || 0;
            return 'Rp ' + val.toLocaleString('id-ID');
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

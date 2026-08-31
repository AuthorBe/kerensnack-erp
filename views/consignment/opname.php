<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();

$cleanWa = preg_replace('/[^0-9]/', '', (string)($customer['nomor_whatsapp'] ?? ''));
if (str_starts_with($cleanWa, '0')) {
    $cleanWa = '62' . substr($cleanWa, 1);
}
?>

<div x-data="salesOpnameApp()" x-init="init()" class="space-y-5 pb-24">

    <!-- ========================================================================= -->
    <!-- HEADER TOKO & QUICK CALL / WHATSAPP                                        -->
    <!-- ========================================================================= -->
    <div class="card p-4 md:p-5" style="border-radius:18px;background:var(--color-surface);border:1.5px solid var(--color-hairline);">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <a href="<?= Router::url('/consignment') ?>" class="inline-flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-amber-400 mb-2 transition-colors">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Kembali ke Daftar Toko</span>
                </a>
                <div class="flex items-center gap-2">
                    <span class="text-xl md:text-2xl font-black text-slate-100"><?= htmlspecialchars($customer['nama_toko']) ?></span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-extrabold border border-amber-500/30">
                        <?= htmlspecialchars($customer['kode_pelanggan']) ?>
                    </span>
                </div>
                <div class="text-xs md:text-sm text-slate-400 mt-1 flex items-center gap-1.5">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-500 shrink-0"></i>
                    <span><?= htmlspecialchars($customer['alamat_lengkap']) ?></span>
                </div>
            </div>

            <!-- Quick Action: WA / Telp -->
            <div class="flex items-center gap-2 shrink-0">
                <?php if (!empty($customer['nomor_telepon'])): ?>
                <a href="tel:<?= htmlspecialchars($customer['nomor_telepon']) ?>" 
                   class="btn btn-secondary text-xs font-bold flex items-center gap-1.5 py-2 px-3">
                    <i data-lucide="phone" class="w-3.5 h-3.5 text-sky-400"></i>
                    <span>Telepon</span>
                </a>
                <?php endif; ?>

                <?php if (!empty($cleanWa)): ?>
                <a href="https://wa.me/<?= htmlspecialchars($cleanWa) ?>" target="_blank" 
                   class="btn btn-secondary text-xs font-bold flex items-center gap-1.5 py-2 px-3" style="border-color:rgba(16,185,129,0.4);color:#34d399;">
                    <i data-lucide="message-circle" class="w-3.5 h-3.5 text-emerald-400"></i>
                    <span>WhatsApp</span>
                </a>
                <?php endif; ?>

                <a href="<?= Router::url('/customer-orders/create') ?>"
                   class="btn btn-primary text-xs font-bold flex items-center gap-1.5 py-2 px-3" style="background:#0284c7;border-color:#0284c7;">
                    <i data-lucide="truck" class="w-3.5 h-3.5"></i>
                    <span>Tambah Kiriman</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- CTA JIKA BELUM ADA BARANG DITITIP SAMA SEKALI                              -->
    <!-- ========================================================================= -->
    <?php if (empty($items)): ?>
    <div class="card p-10 text-center flex flex-col items-center justify-center space-y-4" 
         style="border-radius:20px;border:2px dashed rgba(245,158,11,0.4);background:rgba(245,158,11,0.03);">
        <div class="w-16 h-16 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center">
            <i data-lucide="package-plus" class="w-8 h-8"></i>
        </div>
        <div class="space-y-1">
            <h2 class="text-lg font-black text-slate-100">Toko ini belum ada barang titipan</h2>
            <p class="text-xs md:text-sm text-slate-400 max-w-md">
                Toko <strong><?= htmlspecialchars($customer['nama_toko']) ?></strong> belum memiliki saldo produk di rak. Silakan ajukan pengiriman pertama sekarang.
            </p>
        </div>
        <a href="<?= Router::url('/customer-orders/create') ?>" 
           class="btn btn-primary font-bold py-2.5 px-6" style="background:#f59e0b;border-color:#f59e0b;color:#0f172a;">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>Buat Pengiriman Pertama</span>
        </a>
    </div>
    <?php else: ?>

    <!-- ========================================================================= -->
    <!-- FORM OPNAME RAK TOKO                                                      -->
    <!-- ========================================================================= -->
    <form @submit.prevent="openConfirmModal()">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="pelanggan_id" value="<?= htmlspecialchars($customer['id']) ?>">

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-extrabold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="clipboard-check" class="w-4 h-4 text-amber-400"></i>
                    <span>Daftar Produk di Rak (<?= count($items) ?> SKU)</span>
                </h2>
                <div class="text-xs text-slate-400">
                    <span class="text-rose-400 font-bold">*</span> Wajib isi seluruh sisa fisik rak
                </div>
            </div>

            <!-- List SKU Rak -->
            <div class="space-y-3">
                <template x-for="(item, idx) in formItems" :key="item.item_id">
                    <div class="card p-4 transition-all"
                         :class="item.is_invalid ? 'border-rose-500/60 bg-rose-950/10' : (item.is_touched ? 'border-emerald-500/40 bg-slate-900/60' : 'border-slate-800 bg-slate-900/40')"
                         style="border-radius:16px;">
                        
                        <!-- Header Baris Item -->
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-base text-slate-100" x-text="item.nama_item"></span>
                                    <template x-if="item.stok_titip_saat_ini === 0">
                                        <span class="text-[10px] font-black px-2 py-0.5 rounded bg-slate-800 text-slate-400 border border-slate-700">
                                            Habis: 0 pcs
                                        </span>
                                    </template>
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-2">
                                    <span x-text="'SKU: ' + item.kode_sku"></span>
                                    <span>&bull;</span>
                                    <span class="text-amber-400 font-bold" x-text="'Harga Deal: ' + formatRupiah(item.harga_deal)"></span>
                                </div>
                            </div>

                            <!-- Saldo Titip Sistem -->
                            <div class="text-right shrink-0">
                                <div class="text-[11px] font-bold text-slate-400">Stok Titip Sistem</div>
                                <div class="text-base font-black text-amber-300" x-text="item.stok_titip_saat_ini + ' ' + item.satuan"></div>
                            </div>
                        </div>

                        <!-- 3 Kolom Input Opname -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            
                            <!-- 1. Sisa Fisik di Rak -->
                            <div>
                                <label class="block text-[11px] font-extrabold text-slate-300 mb-1">
                                    Sisa Fisik di Rak <span class="text-rose-400">*</span>
                                </label>
                                <input type="number" 
                                       min="0"
                                       x-model.number="item.sisa_fisik_di_rak"
                                       @input="item.is_touched = true; calculateItem(item)"
                                       class="form-input text-center font-bold text-base text-emerald-400"
                                       placeholder="0"
                                       style="height:42px;border-radius:10px;background:var(--color-surface-soft);"
                                       required>
                                <div class="text-[10px] text-slate-500 mt-1">Hitung fisik yg masih ada di rak</div>
                            </div>

                            <!-- 2. Retur Bagus (Kembali ke Gudang) -->
                            <div>
                                <label class="block text-[11px] font-extrabold text-slate-300 mb-1">
                                    Retur Bagus (Tarik Balik)
                                </label>
                                <input type="number" 
                                       min="0"
                                       x-model.number="item.retur_bagus"
                                       @input="calculateItem(item)"
                                       class="form-input text-center font-bold text-base text-sky-400"
                                       placeholder="0"
                                       style="height:42px;border-radius:10px;background:var(--color-surface-soft);">
                                <!-- Helper text WAJIB sesuai PRD -->
                                <div class="text-[10.5px] text-amber-400/90 font-semibold mt-1 leading-snug">
                                    ⚠️ Isi CUMA kalau dibawa pulang ke gudang. Kalau masih ditinggal di toko, JANGAN diisi di sini.
                                </div>
                            </div>

                            <!-- 3. Retur Rusak (BS/Bocor) -->
                            <div>
                                <label class="block text-[11px] font-extrabold text-slate-300 mb-1">
                                    Retur Rusak (Bocor / BS)
                                </label>
                                <input type="number" 
                                       min="0"
                                       x-model.number="item.retur_rusak"
                                       @input="calculateItem(item)"
                                       class="form-input text-center font-bold text-base text-rose-400"
                                       placeholder="0"
                                       style="height:42px;border-radius:10px;background:var(--color-surface-soft);">
                                <div class="text-[10px] text-slate-500 mt-1">Bungkus bocor / rusak ditarik</div>
                            </div>

                        </div>

                        <!-- Realtime Estimation Footer per Item -->
                        <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between flex-wrap gap-2 text-xs">
                            <div class="flex items-center gap-2">
                                <span class="text-slate-400 font-semibold">Estimasi Laku:</span>
                                <span class="font-extrabold text-sm" 
                                      :class="item.laku > 0 ? 'text-emerald-400' : 'text-slate-400'"
                                      x-text="item.laku + ' ' + item.satuan"></span>
                                <template x-if="item.laku > 0">
                                    <span class="text-emerald-400 font-bold text-[11px]" x-text="'(' + formatRupiah(item.laku * item.harga_deal) + ')'"></span>
                                </template>
                            </div>

                            <!-- Warning jika sisa + retur > stok titip -->
                            <template x-if="item.is_invalid">
                                <div class="inline-flex items-center gap-1 text-rose-400 font-bold text-[11px]">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                    <span>Total (sisa+retur) melebihi stok titip awal!</span>
                                </div>
                            </template>
                        </div>

                    </div>
                </template>
            </div>

            <!-- Catatan Kunjungan -->
            <div class="card p-4" style="border-radius:16px;background:var(--color-surface);">
                <label class="block text-xs font-bold text-slate-300 mb-1.5">
                    Catatan Kunjungan (Opsional)
                </label>
                <textarea x-model="catatan" 
                          rows="2" 
                          placeholder="Misal: Pemilik toko pesan varian pedas lebih banyak minggu depan..."
                          class="form-input w-full text-xs"
                          style="border-radius:10px;background:var(--color-surface-soft);"></textarea>
            </div>

            <!-- STICKY BOTTOM SUBMIT BAR -->
            <div class="fixed bottom-0 left-0 right-0 p-4 bg-slate-950/95 backdrop-blur border-t border-slate-800 z-40 flex items-center justify-between gap-4 max-w-7xl mx-auto">
                <div>
                    <div class="text-[11px] font-bold text-slate-400">Total Estimasi Terjual:</div>
                    <div class="text-lg md:text-xl font-black text-emerald-400" x-text="formatRupiah(totalNominalLaku)"></div>
                    <div class="text-[11px] text-slate-400" x-text="totalQtyLaku + ' pcs dari ' + totalItemLakuCount + ' item laku'"></div>
                </div>

                <button type="submit" 
                        :disabled="!isFormValid || isSubmitting" 
                        class="btn btn-primary py-3 px-6 md:px-8 text-sm md:text-base font-black rounded-xl shadow-lg transition-all"
                        :style="(!isFormValid || isSubmitting) ? 'opacity:0.5;cursor:not-allowed;' : 'background:#10b981;border-color:#10b981;box-shadow:0 8px 24px rgba(16,185,129,0.35);'">
                    <span x-show="!isSubmitting" class="flex items-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Selesaikan Opname</span>
                    </span>
                    <span x-show="isSubmitting" class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        <span>Menyimpan...</span>
                    </span>
                </button>
            </div>

        </div>
    </form>

    <!-- ========================================================================= -->
    <!-- MODAL KONFIRMASI SEBELUM SUBMIT                                           -->
    <!-- ========================================================================= -->
    <div x-show="showConfirmModal" 
         x-cloak 
         class="modal-backdrop"
         @keydown.escape.window="showConfirmModal = false">
        
        <div class="modal-box space-y-4" @click.outside="showConfirmModal = false">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;margin:0 auto;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <path d="M12 17h.01"></path>
                </svg>
            </div>

            <div class="text-center space-y-1">
                <h3 class="modal-title text-center" style="font-size:16px;font-weight:800;color:var(--color-ink);">Konfirmasi Kunjungan Opname</h3>
                <p style="font-size:12px;color:var(--color-ink-secondary);line-height:1.4;">
                    Kamu akan mencatat kunjungan ke <strong style="color:var(--color-primary);"><?= htmlspecialchars($customer['nama_toko']) ?></strong>:
                </p>
            </div>

            <div class="p-3.5 rounded-xl space-y-2 text-xs" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                <div class="flex justify-between">
                    <span style="color:var(--color-ink-mute);">Total Produk Laku:</span>
                    <span class="font-black" style="color:var(--color-ink);" x-text="totalQtyLaku + ' pcs (' + totalItemLakuCount + ' varian)'"></span>
                </div>
                <div class="flex justify-between">
                    <span style="color:var(--color-ink-mute);">Total Nilai Penjualan:</span>
                    <span class="font-black text-sm" style="color:var(--color-success);" x-text="formatRupiah(totalNominalLaku)"></span>
                </div>
                <div class="flex justify-between">
                    <span style="color:var(--color-ink-mute);">Total Retur Bagus:</span>
                    <span class="font-black" style="color:var(--color-primary);" x-text="totalReturBagus + ' pcs'"></span>
                </div>
                <div class="flex justify-between">
                    <span style="color:var(--color-ink-mute);">Total Retur Rusak:</span>
                    <span class="font-black" style="color:var(--color-danger);" x-text="totalReturRusak + ' pcs'"></span>
                </div>
            </div>

            <div style="font-size:11px;color:var(--color-ink-mute);text-align:center;font-style:italic;">
                *Nota faktur tagihan akan otomatis diterbitkan oleh sistem jika ada barang laku.
            </div>

            <div style="display:flex;gap:8px;padding-top:6px;">
                <button type="button" @click="showConfirmModal = false" class="btn btn-secondary" style="flex:1;font-weight:700;padding:10px;" :disabled="isSubmitting">
                    Batal / Periksa Lagi
                </button>
                <button type="button" @click="submitOpname()" class="btn btn-primary" style="flex:1;font-weight:800;padding:10px;background:var(--color-success);border-color:var(--color-success);" :disabled="isSubmitting">
                    <span x-show="!isSubmitting">Ya, Simpan &amp; Selesai</span>
                    <span x-show="isSubmitting">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>

    <?php endif; ?>

</div>

<script>
function salesOpnameApp() {
    return {
        isSubmitting: false,
        showConfirmModal: false,
        catatan: '',
        formItems: <?= json_encode(array_map(function($i) {
            return [
                'item_id' => $i['item_id'],
                'nama_item' => $i['nama_item'],
                'kode_sku' => $i['kode_sku'],
                'satuan' => $i['satuan_dasar'] ?? 'pcs',
                'stok_titip_saat_ini' => (int)$i['stok_titip_saat_ini'],
                'harga_deal' => (float)$i['harga_deal'],
                'sisa_fisik_di_rak' => (int)($i['sisa_fisik_di_rak'] ?? $i['stok_titip_saat_ini']),
                'retur_bagus' => (int)($i['retur_bagus'] ?? 0),
                'retur_rusak' => (int)($i['retur_rusak'] ?? 0),
                'laku' => 0,
                'is_touched' => (bool)($i['is_touched'] ?? false),
                'is_invalid' => false
            ];
        }, $items ?? [])) ?>,

        init() {
            this.formItems.forEach(i => this.calculateItem(i));
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        calculateItem(item) {
            const sisa = Math.max(0, parseInt(item.sisa_fisik_di_rak) || 0);
            const rBagus = Math.max(0, parseInt(item.retur_bagus) || 0);
            const rRusak = Math.max(0, parseInt(item.retur_rusak) || 0);
            const titip = item.stok_titip_saat_ini;

            // Validasi over-count
            item.is_invalid = (sisa + rBagus + rRusak) > titip;

            // Rumus laku
            item.laku = Math.max(0, titip - (sisa + rBagus + rRusak));
        },

        get isFormValid() {
            if (this.formItems.length === 0) return false;
            // Semua item harus disentuh / diisi sisa fisik
            const allTouched = this.formItems.every(i => i.is_touched || i.sisa_fisik_di_rak !== undefined);
            // Tidak boleh ada item yang invalid
            const noInvalid = this.formItems.every(i => !i.is_invalid);
            return allTouched && noInvalid;
        },

        get totalNominalLaku() {
            return this.formItems.reduce((acc, i) => acc + (i.laku * i.harga_deal), 0);
        },

        get totalQtyLaku() {
            return this.formItems.reduce((acc, i) => acc + i.laku, 0);
        },

        get totalItemLakuCount() {
            return this.formItems.filter(i => i.laku > 0).length;
        },

        get totalReturBagus() {
            return this.formItems.reduce((acc, i) => acc + (parseInt(i.retur_bagus) || 0), 0);
        },

        get totalReturRusak() {
            return this.formItems.reduce((acc, i) => acc + (parseInt(i.retur_rusak) || 0), 0);
        },

        openConfirmModal() {
            if (!this.isFormValid) return;
            this.showConfirmModal = true;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        submitOpname() {
            this.isSubmitting = true;

            const payload = {
                pelanggan_id: '<?= htmlspecialchars($customer['id'] ?? '') ?>',
                catatan: this.catatan,
                items: this.formItems.map(i => ({
                    item_id: i.item_id,
                    sisa_fisik_di_rak: Math.max(0, parseInt(i.sisa_fisik_di_rak) || 0),
                    retur_bagus: Math.max(0, parseInt(i.retur_bagus) || 0),
                    retur_rusak: Math.max(0, parseInt(i.retur_rusak) || 0),
                    jumlah_laku: i.laku
                }))
            };

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= Router::url('/consignment/opname') ?>';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '<?= \App\Helpers\CSRF::name() ?>';
            csrfInput.value = '<?= \App\Helpers\CSRF::token() ?>';
            form.appendChild(csrfInput);

            const custInput = document.createElement('input');
            custInput.type = 'hidden';
            custInput.name = 'pelanggan_id';
            custInput.value = payload.pelanggan_id;
            form.appendChild(custInput);

            const itemsInput = document.createElement('input');
            itemsInput.type = 'hidden';
            itemsInput.name = 'items_json';
            itemsInput.value = JSON.stringify(payload.items);
            form.appendChild(itemsInput);

            const catInput = document.createElement('input');
            catInput.type = 'hidden';
            catInput.name = 'catatan';
            catInput.value = payload.catatan;
            form.appendChild(catInput);

            document.body.appendChild(form);
            form.submit();
        },

        formatRupiah(num) {
            return 'Rp ' + (new Intl.NumberFormat('id-ID')).format(Math.round(num || 0));
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

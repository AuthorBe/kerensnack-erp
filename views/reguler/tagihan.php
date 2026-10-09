<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="bukuPiutangApp()" x-init="init()" class="space-y-4 sm:space-y-6 pb-20">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER                                                            -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <a href="<?= Router::url('/reguler') ?>" 
               class="btn btn-secondary btn-sm p-2 rounded-xl" 
               title="Kembali ke Portal Reguler">
                <i data-lucide="arrow-left" class="w-5 h-5" style="pointer-events:none;"></i>
            </a>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#2563eb;"></span>
                    <span>Buku Piutang Mitra &bull; Grosir B2B</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl"><?= $pageTitle ?? 'Buku Piutang &amp; Penagihan Toko' ?></h1>
                <p class="page-subtitle text-xs sm:text-sm"><?= $pageSubtitle ?? 'Rekapitulasi Kewajiban Tagihan Tempo per Toko Mitra &amp; Penerbitan Surat Tagihan Resmi' ?></p>
            </div>
        </div>
        <div class="page-header-actions flex items-center gap-2">
            <a href="<?= Router::url('/reguler/early-warning') ?>" 
               class="btn btn-secondary flex items-center gap-2 text-xs font-bold"
               style="border-radius:12px;">
                <i data-lucide="shield-alert" class="w-4 h-4 text-warning"></i>
                <span>Radar Risiko</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. FILTER & SEARCH BAR                                                    -->
    <!-- ========================================================================= -->
    <div class="card p-3 sm:p-4 rounded-2xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
        <form method="GET" action="<?= Router::url('/reguler/tagihan') ?>" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-1 flex-wrap">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[200px] sm:max-w-xs">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-ink-mute pointer-events-none"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($filter['q'] ?? '') ?>" 
                           placeholder="Cari toko / kode / pemilik..." 
                           class="form-input text-xs pl-9 w-full" style="height:38px;border-radius:10px;">
                </div>

                <!-- Filter Wilayah -->
                <select name="wilayah_id" class="form-select text-xs font-medium" style="height:38px;border-radius:10px;">
                    <option value="">-- Semua Wilayah --</option>
                    <?php foreach ($wilayahList as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= ($filter['wilayah_id'] ?? '') === $w['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($w['nama_wilayah']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Filter Tipe Tempo -->
                <select name="tipe_bayar" class="form-select text-xs font-medium" style="height:38px;border-radius:10px;">
                    <option value="semua" <?= ($filter['tipe_bayar'] ?? '') === 'semua' ? 'selected' : '' ?>>-- Semua Tipe Tempo --</option>
                    <option value="tempo_faktur" <?= ($filter['tipe_bayar'] ?? '') === 'tempo_faktur' ? 'selected' : '' ?>>Tempo Faktur</option>
                    <option value="tempo_tanggal" <?= ($filter['tipe_bayar'] ?? '') === 'tempo_tanggal' ? 'selected' : '' ?>>Tempo Tanggal</option>
                </select>

                <!-- Filter Status Piutang -->
                <select name="status_piutang" class="form-select text-xs font-medium" style="height:38px;border-radius:10px;">
                    <option value="berhutang" <?= ($filter['status_piutang'] ?? '') === 'berhutang' ? 'selected' : '' ?>>Hanya yang Berhutang</option>
                    <option value="over_plafon" <?= ($filter['status_piutang'] ?? '') === 'over_plafon' ? 'selected' : '' ?>>Over-Plafon Saja</option>
                    <option value="semua" <?= ($filter['status_piutang'] ?? '') === 'semua' ? 'selected' : '' ?>>Semua Toko Reguler</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex items-center gap-1.5 font-bold" style="height:38px;border-radius:10px;">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Terapkan Filter</span>
                </button>
                <?php if (!empty($filter['q']) || !empty($filter['wilayah_id']) || $filter['tipe_bayar'] !== 'semua' || $filter['status_piutang'] !== 'berhutang'): ?>
                <a href="<?= Router::url('/reguler/tagihan') ?>" class="btn btn-ghost btn-sm text-ink-mute" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. TABEL BUKU PIUTANG PER TOKO                                            -->
    <!-- ========================================================================= -->
    <div class="card p-0 rounded-2xl overflow-hidden" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
        <?php if (empty($stores)): ?>
            <div class="p-10 text-center text-ink-mute">
                <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-2 opacity-40"></i>
                <div class="font-bold text-sm">Tidak ditemukan data toko sesuai filter</div>
                <div class="text-xs mt-0.5">Coba ubah kata kunci pencarian atau reset filter di atas.</div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="data-table min-w-[850px]">
                    <thead>
                        <tr>
                            <th style="width:40px;" class="cell-center">No</th>
                            <th>Toko Mitra &amp; Kontak</th>
                            <th>Wilayah &amp; Sales</th>
                            <th class="cell-center">Skema Bayar</th>
                            <th class="cell-right">Plafon Kredit</th>
                            <th class="cell-right">Piutang Berjalan</th>
                            <th class="cell-center">Faktur Gantung</th>
                            <th class="cell-center" style="width:130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stores as $idx => $st): 
                            $isOver = (float)$st['plafon_piutang'] > 0 && (float)$st['total_piutang_berjalan'] > (float)$st['plafon_piutang'];
                            $menumpuk = (int)$st['total_faktur_gantung'] >= 2 && $st['tipe_pembayaran_default'] === 'tempo_faktur';
                        ?>
                        <tr class="<?= $isOver ? 'bg-rose-50/20 dark:bg-rose-950/10' : '' ?>">
                            <td class="cell-center font-bold text-ink-mute"><?= $idx + 1 ?></td>
                            <td>
                                <div class="font-bold text-sm text-ink"><?= htmlspecialchars($st['nama_toko']) ?></div>
                                <div class="text-xs text-ink-mute font-mono">
                                    <?= htmlspecialchars($st['kode_pelanggan']) ?>
                                    <?= !empty($st['nama_pemilik']) ? ' &bull; ' . htmlspecialchars($st['nama_pemilik']) : '' ?>
                                </div>
                                <?php if (!empty($st['nomor_whatsapp'])): ?>
                                <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-mono flex items-center gap-1 mt-0.5">
                                    <i data-lucide="phone" class="w-3 h-3"></i>
                                    <span><?= htmlspecialchars($st['nomor_whatsapp']) ?></span>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="font-semibold text-xs text-ink"><?= htmlspecialchars($st['nama_wilayah']) ?></div>
                                <div class="text-[11px] text-ink-mute">Sales: <?= htmlspecialchars($st['nama_sales']) ?></div>
                            </td>
                            <td class="cell-center">
                                <?php if ($st['tipe_pembayaran_default'] === 'tempo_faktur'): ?>
                                    <span class="badge badge-warning">Tempo Faktur</span>
                                <?php elseif ($st['tipe_pembayaran_default'] === 'tempo_tanggal'): ?>
                                    <span class="badge badge-info">Tempo Tanggal</span>
                                <?php else: ?>
                                    <span class="badge badge-neutral"><?= strtoupper(str_replace('_', ' ', $st['tipe_pembayaran_default'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="cell-right font-mono font-bold text-ink-mute" style="font-variant-numeric:tabular-nums;">
                                <?= (float)$st['plafon_piutang'] > 0 ? Format::rupiah((float)$st['plafon_piutang']) : 'Tanpa Limit' ?>
                            </td>
                            <td class="cell-right font-mono font-black <?= $isOver ? 'text-danger' : 'text-ink' ?>" style="font-variant-numeric:tabular-nums;">
                                <?= Format::rupiah((float)$st['total_piutang_berjalan']) ?>
                                <?php if ($isOver): ?>
                                    <div class="text-[10px] text-danger font-sans font-bold flex items-center justify-end gap-1">
                                        <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                        <span>Over Plafon</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="cell-center">
                                <?php if ((int)$st['total_faktur_gantung'] > 0): ?>
                                    <span class="badge <?= $menumpuk ? 'badge-danger font-bold' : 'badge-mono' ?>" style="font-size:11px;">
                                        <?= (int)$st['total_faktur_gantung'] ?> Nota
                                    </span>
                                    <?php if (!empty($st['tgl_faktur_tertua'])): ?>
                                        <div class="text-[10px] text-ink-mute mt-0.5">
                                            Tertua: <?= date('d/m/y', strtotime($st['tgl_faktur_tertua'])) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-success" style="font-size:10.5px;">Lunas</span>
                                <?php endif; ?>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <button type="button" 
                                        @click="openDrawer(<?= htmlspecialchars(json_encode($st)) ?>)" 
                                        class="btn btn-secondary btn-sm flex items-center gap-1 font-bold"
                                        style="font-size:11.5px;padding:5px 10px;">
                                    <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                                    <span>Lihat Faktur</span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. MODAL DRAWER RINCIAN FAKTUR TOKO & PEMBUATAN INVOICE TAGIHAN           -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="showDrawer" x-cloak class="modal-backdrop" @click="showDrawer = false">
            <div class="modal-box modal-box-lg" @click.stop style="max-width:750px;">
                <!-- Handle Bar Mobile -->
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>

                <!-- Modal Header -->
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(37,99,235,0.12);color:#2563eb;">
                            <i data-lucide="store" class="w-5 h-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="modal-title text-base sm:text-lg font-black truncate" x-text="activeStore?.nama_toko"></h3>
                            <div class="text-xs text-ink-mute flex items-center gap-2 mt-0.5">
                                <span class="font-mono font-bold" x-text="activeStore?.kode_pelanggan"></span>
                                <span>&bull;</span>
                                <span x-text="activeStore?.nama_wilayah"></span>
                                <span>&bull;</span>
                                <span x-text="'PIC: ' + (activeStore?.nama_pemilik || '-')"></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="showDrawer = false" class="modal-close-x" title="Tutup">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Body: Ringkasan Finansial Toko & Tabel Faktur Gantung -->
                <div class="modal-body custom-scrollbar space-y-4">
                    <!-- Stat Bar Toko -->
                    <div class="grid grid-cols-3 gap-2.5 p-3 rounded-xl" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div>
                            <span class="text-[10.5px] font-bold text-ink-mute uppercase block">Plafon Kredit</span>
                            <span class="font-mono font-extrabold text-xs sm:text-sm text-ink" x-text="Number(activeStore?.plafon_piutang) > 0 ? formatRupiah(activeStore?.plafon_piutang) : 'Tanpa Limit'"></span>
                        </div>
                        <div>
                            <span class="text-[10.5px] font-bold text-ink-mute uppercase block">Piutang Berjalan</span>
                            <span class="font-mono font-black text-xs sm:text-sm text-danger" x-text="formatRupiah(activeStore?.total_piutang_berjalan)"></span>
                        </div>
                        <div>
                            <span class="text-[10.5px] font-bold text-ink-mute uppercase block">Faktur Gantung</span>
                            <span class="font-mono font-extrabold text-xs sm:text-sm text-amber-600" x-text="(activeStore?.faktur_list?.length || 0) + ' Nota'"></span>
                        </div>
                    </div>

                    <!-- Checklist Tabel Faktur -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="font-extrabold text-xs uppercase tracking-wider text-ink flex items-center gap-1.5">
                                <span>Pilih Faktur yang Ditagihkan:</span>
                                <span class="badge badge-mono text-[10px]" x-text="selectedInvoices.length + ' Dipilih'"></span>
                            </label>
                            <button type="button" @click="toggleSelectAll()" class="btn btn-ghost btn-xs text-blue-600 font-bold">
                                <span x-text="selectedInvoices.length === (activeStore?.faktur_list?.length || 0) ? 'Batal Pilih Semua' : 'Pilih Semua Faktur'"></span>
                            </button>
                        </div>

                        <div class="rounded-xl overflow-hidden border" style="border-color:var(--color-hairline);">
                            <template x-if="!activeStore?.faktur_list || activeStore.faktur_list.length === 0">
                                <div class="p-6 text-center text-ink-mute text-xs">
                                    Tidak ada faktur gantung aktif untuk toko ini.
                                </div>
                            </template>

                            <template x-if="activeStore?.faktur_list && activeStore.faktur_list.length > 0">
                                <div class="overflow-x-auto max-h-[280px] custom-scrollbar">
                                    <table class="w-full text-left text-xs min-w-[500px]">
                                        <thead>
                                            <tr style="background:var(--color-canvas);border-bottom:1px solid var(--color-hairline);" class="text-[10.5px] font-extrabold text-ink-mute uppercase">
                                                <th style="width:36px;" class="py-2.5 px-3 cell-center">Pilih</th>
                                                <th class="py-2.5 px-3">No. Nota</th>
                                                <th class="py-2.5 px-3">Tanggal</th>
                                                <th class="py-2.5 px-3 cell-center">Skema</th>
                                                <th class="py-2.5 px-3 cell-right">Total Netto</th>
                                                <th class="py-2.5 px-3 cell-right">Sisa Tagihan</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y" style="border-color:var(--color-hairline);">
                                            <template x-for="inv in activeStore.faktur_list" :key="inv.id">
                                                <tr class="hover:bg-slate-500/5 transition-colors cursor-pointer" @click="toggleInvoice(inv.id)">
                                                    <td class="py-2.5 px-3 cell-center" @click.stop>
                                                        <input type="checkbox" :value="inv.id" x-model="selectedInvoices" class="rounded form-checkbox">
                                                    </td>
                                                    <td class="py-2.5 px-3 font-mono font-bold text-ink" x-text="inv.nomor_nota"></td>
                                                    <td class="py-2.5 px-3 text-ink-mute font-mono" x-text="formatDateShort(inv.tanggal_pesanan)"></td>
                                                    <td class="py-2.5 px-3 cell-center">
                                                        <span class="badge text-[10px]" :class="inv.tipe_pembayaran === 'tempo_faktur' ? 'badge-warning' : 'badge-info'" x-text="inv.tipe_pembayaran === 'tempo_faktur' ? 'Tempo Faktur' : 'Tempo Tanggal'"></span>
                                                    </td>
                                                    <td class="py-2.5 px-3 cell-right font-mono text-ink-mute" x-text="formatRupiah(inv.total_netto)"></td>
                                                    <td class="py-2.5 px-3 cell-right font-mono font-black text-danger" x-text="formatRupiah(inv.sisa_tagihan)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Total Yang Terpilih -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800">
                        <span class="text-xs font-bold text-blue-900 dark:text-blue-300">Total Nominal Terpilih untuk Ditagih:</span>
                        <span class="font-mono font-black text-sm sm:text-base text-blue-700 dark:text-blue-400" x-text="formatRupiah(selectedTotalAmount)"></span>
                    </div>
                </div>

                <!-- Modal Footer Actions -->
                <div class="modal-footer flex items-center justify-between flex-wrap gap-2">
                    <button type="button" @click="showDrawer = false" class="btn btn-secondary modal-btn-cancel-desktop">Tutup</button>
                    
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <!-- Tombol Broadcast WhatsApp -->
                        <button type="button" 
                                @click="broadcastWhatsapp()" 
                                :disabled="selectedInvoices.length === 0"
                                class="btn btn-secondary flex-1 sm:flex-none flex items-center justify-center gap-1.5 font-bold"
                                style="border-color:#10b981;color:#059669;">
                            <i data-lucide="message-circle" class="w-4 h-4"></i>
                            <span>Kirim Rekap WA</span>
                        </button>

                        <!-- Tombol Cetak Dokumen Resmi -->
                        <button type="button" 
                                @click="printInvoiceDoc()" 
                                :disabled="selectedInvoices.length === 0"
                                class="btn btn-primary flex-1 sm:flex-none flex items-center justify-center gap-1.5 font-bold">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                            <span>Cetak Surat Tagihan</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </template>

</div>

<script>
function bukuPiutangApp() {
    return {
        showDrawer: false,
        activeStore: null,
        selectedInvoices: [],

        init() {
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        openDrawer(store) {
            this.activeStore = store;
            // Default: pilih semua faktur gantung milik toko
            this.selectedInvoices = (store.faktur_list || []).map(inv => inv.id);
            this.showDrawer = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        toggleSelectAll() {
            const list = this.activeStore?.faktur_list || [];
            if (this.selectedInvoices.length === list.length) {
                this.selectedInvoices = [];
            } else {
                this.selectedInvoices = list.map(inv => inv.id);
            }
        },

        toggleInvoice(id) {
            if (this.selectedInvoices.includes(id)) {
                this.selectedInvoices = this.selectedInvoices.filter(i => i !== id);
            } else {
                this.selectedInvoices.push(id);
            }
        },

        get selectedTotalAmount() {
            if (!this.activeStore?.faktur_list) return 0;
            return this.activeStore.faktur_list
                .filter(inv => this.selectedInvoices.includes(inv.id))
                .reduce((sum, inv) => sum + Number(inv.sisa_tagihan || 0), 0);
        },

        printInvoiceDoc() {
            if (this.selectedInvoices.length === 0) {
                window.toast?.warning('Pilih minimal 1 faktur untuk dicetak.');
                return;
            }
            const orderIds = this.selectedInvoices.join(',');
            const url = `<?= Router::url('/reguler/tagihan/cetak') ?>?pelanggan_id=${this.activeStore.id}&order_ids=${orderIds}`;
            window.open(url, '_blank');
        },

        broadcastWhatsapp() {
            if (!this.activeStore?.nomor_whatsapp) {
                window.toast?.error('Nomor WhatsApp pemilik toko belum terdaftar di master data.');
                return;
            }
            if (this.selectedInvoices.length === 0) {
                window.toast?.warning('Pilih minimal 1 faktur yang ditagihkan.');
                return;
            }

            const cleanPhone = this.activeStore.nomor_whatsapp.replace(/[^0-9]/g, '');
            const phone = cleanPhone.startsWith('0') ? '62' + cleanPhone.substring(1) : cleanPhone;

            const selectedList = this.activeStore.faktur_list.filter(inv => this.selectedInvoices.includes(inv.id));
            const listText = selectedList.map(inv => `- Nota ${inv.nomor_nota} (Tgl: ${this.formatDateShort(inv.tanggal_pesanan)}): ${this.formatRupiah(inv.sisa_tagihan)}`).join('\n');

            const message = `Halo ${this.activeStore.nama_pemilik ? 'Bpk/Ibu ' + this.activeStore.nama_pemilik : ''} (${this.activeStore.nama_toko}),\n\nBerikut kami sampaikan rincian tagihan faktur tempo berjalan yang perlu diselesaikan:\n\n${listText}\n\n*TOTAL TAGIHAN: ${this.formatRupiah(this.selectedTotalAmount)}*\n\nMohon dapat disiapkan sebelum armada kami melakukan pengiriman jadwal berikutnya. Pembayaran dapat diserahkan tunai kepada driver atau ditransfer ke rekening resmi kantor.\n\nTerima kasih atas kerja samanya. 🙏\n*KEREN ONE OPERATIONAL*`;

            const waUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
            window.open(waUrl, '_blank');
        },

        formatRupiah(val) {
            return window.formatRupiah ? window.formatRupiah(val) : 'Rp ' + Number(val || 0).toLocaleString('id-ID');
        },

        formatDateShort(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear();
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

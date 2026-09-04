<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="piutangApp()" class="space-y-4 sm:space-y-6 pb-20">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-header-body">
            <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary btn-sm p-2 rounded-xl" title="Kembali ke Portal">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#f43f5e;"></span>
                    <span>Keuangan &amp; Kas • <?= $isAdmin ? 'Admin Operasional' : 'Owner (Read-Only)' ?></span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl">Piutang Konsinyasi</h1>
                <p class="page-subtitle text-xs sm:text-sm">Daftar faktur hasil penjualan konsinyasi yang belum dilunasi oleh toko mitra.</p>
            </div>
        </div>
        <div class="page-header-actions">
            <a href="<?= Router::url('/consignment/piutang/export-excel') ?>" class="btn btn-secondary" style="height:38px; background:#10b981; color:#fff; border-color:#059669; font-weight:700; border-radius:12px; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                <span>Export Excel</span>
            </a>
        </div>
    </div>

    <!-- SUMMARY METRIC -->
    <div class="card p-4 sm:p-5 rounded-3xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4" style="background:rgba(244,63,94,0.04);border:1px solid var(--color-hairline);">
        <div class="flex items-center gap-3.5">
            <i data-lucide="receipt" class="w-8 h-8 flex-shrink-0" style="color:#f43f5e;"></i>
            <div>
                <span class="text-[10.5px] sm:text-xs font-bold uppercase tracking-wider block" style="color:var(--color-ink-mute);">Total Outstanding Piutang:</span>
                <div class="text-xl sm:text-3xl font-black text-rose-500 mt-0.5"><?= Format::rupiah((float)$totalPiutang) ?></div>
            </div>
        </div>

        <div class="text-xs" style="color:var(--color-ink-mute);">
            <span>Faktur Belum Lunas:</span>
            <strong class="ml-1" style="color:var(--color-ink);"><?= count($invoices) ?> Nota</strong>
        </div>
    </div>

    <!-- INVOICE TABLE -->
    <?php if (empty($invoices)): ?>
        <div class="card p-8 sm:p-12 text-center rounded-3xl" style="border:1px solid var(--color-hairline);">
            <i data-lucide="check-circle-2" class="w-12 h-12 mx-auto mb-3" style="color:#10b981;"></i>
            <h3 class="text-base sm:text-lg font-bold" style="color:var(--color-ink);">Tidak Ada Piutang Konsinyasi Aktif</h3>
            <p class="text-xs sm:text-sm mt-1 max-w-md mx-auto" style="color:var(--color-ink-mute);">
                Semua faktur penjualan konsinyasi telah lunas tercatat di kas. Kerja bagus! 👍
            </p>
        </div>
    <?php else: ?>
        <div class="card rounded-3xl overflow-hidden" style="border:1px solid var(--color-hairline);">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[650px]">
                    <thead>
                        <tr class="font-bold uppercase tracking-wider text-[10.5px]" style="background:var(--color-canvas);border-bottom:1px solid var(--color-hairline);color:var(--color-ink-mute);">
                            <th class="py-3.5 px-4">Toko Mitra</th>
                            <th class="py-3.5 px-4">No. Nota</th>
                            <th class="py-3.5 px-4">Tanggal Tagihan</th>
                            <th class="py-3.5 px-4 text-right">Total Tagihan</th>
                            <th class="py-3.5 px-4 text-right">Sudah Dibayar</th>
                            <th class="py-3.5 px-4 text-right">Sisa Tagihan</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <?php if ($isAdmin): ?>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color:var(--color-hairline);">
                        <?php foreach ($invoices as $inv): 
                            $status = $inv['status_pembayaran'];
                            $sisa = (float)$inv['sisa_tagihan'];
                        ?>
                        <tr class="hover:bg-slate-500/5 transition-colors">
                            <td class="py-3.5 px-4 font-bold" style="color:var(--color-ink);">
                                <span class="text-xs sm:text-sm"><?= htmlspecialchars($inv['nama_toko']) ?></span>
                                <span class="text-[10px] block font-normal" style="color:var(--color-ink-mute);"><?= htmlspecialchars($inv['nama_sales'] ?? 'Sales') ?></span>
                            </td>

                            <td class="py-3.5 px-4 font-mono font-bold text-sky-600 dark:text-sky-400">
                                <?= htmlspecialchars($inv['nomor_nota']) ?>
                            </td>

                            <td class="py-3.5 px-4" style="color:var(--color-ink-secondary);">
                                <?= date('d/m/Y', strtotime($inv['tanggal_pesanan'])) ?>
                            </td>

                            <td class="py-3.5 px-4 text-right" style="color:var(--color-ink);">
                                <?= Format::rupiah((float)$inv['total_netto']) ?>
                            </td>

                            <td class="py-3.5 px-4 text-right text-emerald-500 font-bold">
                                <?= Format::rupiah((float)$inv['total_dibayar']) ?>
                            </td>

                            <td class="py-3.5 px-4 text-right font-black text-rose-500 text-xs sm:text-sm">
                                <?= Format::rupiah($sisa) ?>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <?php if ($status === 'sebagian'): ?>
                                    <span style="background:rgba(245,158,11,0.12);color:#f59e0b;border:1px solid rgba(245,158,11,0.25);padding:2px 7px;border-radius:12px;font-weight:700;font-size:9.5px;">SEBAGIAN</span>
                                <?php else: ?>
                                    <span style="background:rgba(244,63,94,0.12);color:#f43f5e;border:1px solid rgba(244,63,94,0.25);padding:2px 7px;border-radius:12px;font-weight:700;font-size:9.5px;">BELUM LUNAS</span>
                                <?php endif; ?>
                            </td>

                            <?php if ($isAdmin): ?>
                            <td class="py-3.5 px-4 text-right">
                                <button type="button" 
                                        @click="openPayModal(<?= htmlspecialchars(json_encode($inv)) ?>)"
                                        class="btn btn-primary btn-sm flex items-center gap-1.5 py-1.5 px-3 rounded-xl text-xs font-bold"
                                        style="background:#10b981;border-color:#10b981;color:#fff;">
                                    <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
                                    <span>Bayar</span>
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    <!-- MODAL CATAT PEMBAYARAN (ADMIN ONLY) -->
    <div x-show="showPayModal" 
         x-cloak 
         class="modal-backdrop"
         @click.self="showPayModal = false"
         @keydown.escape.window="showPayModal = false">
        <div class="modal-box p-5 sm:p-6 rounded-3xl shadow-2xl max-h-[90vh] overflow-y-auto" style="max-width: 440px;width:100%;border:1px solid var(--color-hairline);background:var(--color-card);">
            <div class="flex items-center justify-between pb-3.5" style="border-bottom:1px solid var(--color-hairline);">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="wallet" class="w-6 h-6" style="color:#10b981;"></i>
                    <div>
                        <h3 class="text-sm sm:text-base font-black" style="color:var(--color-ink);">Catat Pembayaran Piutang</h3>
                        <p class="text-[11px]" style="color:var(--color-ink-mute);" x-text="selectedInvoice.nomor_nota + ' • ' + selectedInvoice.nama_toko"></p>
                    </div>
                </div>
                <button type="button" @click="showPayModal = false" class="btn btn-ghost btn-xs">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="payForm" action="<?= Router::url('/consignment/piutang/bayar') ?>" method="POST" data-action-text="Mencatat pembayaran piutang..." class="space-y-3.5 mt-3.5">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="pesanan_id" :value="selectedInvoice.pesanan_id">

                <!-- SISA TAGIHAN INFO -->
                <div class="p-3 rounded-2xl flex justify-between items-center text-xs" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                    <span style="color:var(--color-ink-mute);">Sisa Tagihan:</span>
                    <strong class="text-rose-500 text-sm font-black" x-text="formatRupiah(selectedInvoice.sisa_tagihan)"></strong>
                </div>

                <!-- PILIH AKUN KAS -->
                <div>
                    <label class="block text-xs font-bold mb-1" style="color:var(--color-ink);">Setor ke Kas / Rekening <span class="text-rose-500">*</span></label>
                    <select name="akun_kas_id" required class="form-input w-full text-xs" style="height:42px;border-radius:12px;">
                        <option value="">-- Pilih Akun Kas --</option>
                        <?php foreach ($cashAccounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>">
                            <?= htmlspecialchars($acc['nama_akun']) ?> (Saldo: <?= Format::rupiah((float)$acc['saldo_saat_ini']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- NOMINAL BAYAR -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="text-xs font-bold" style="color:var(--color-ink);">Nominal Bayar (Rp) <span class="text-rose-500">*</span></label>
                        <button type="button" @click="payNominal = selectedInvoice.sisa_tagihan" class="text-[11px] font-bold text-emerald-500 hover:underline">
                            Lunas (100%)
                        </button>
                    </div>
                    <input type="number" 
                           inputmode="numeric"
                           pattern="[0-9]*"
                           name="nominal" 
                           min="1" 
                           :max="selectedInvoice.sisa_tagihan"
                           x-model.number="payNominal" 
                           required 
                           class="form-input w-full text-base font-black text-emerald-500" 
                           style="height:44px;border-radius:12px;">
                    <span class="text-[10px] mt-1 block" style="color:var(--color-ink-mute);">Maksimal: <span x-text="formatRupiah(selectedInvoice.sisa_tagihan)"></span></span>
                </div>

                <!-- KETERANGAN OPSIONAL -->
                <div>
                    <label class="block text-xs font-bold mb-1" style="color:var(--color-ink);">Keterangan (Opsional):</label>
                    <input type="text" 
                           name="keterangan" 
                           placeholder="Contoh: Titipan pelunasan via transfer..." 
                           class="form-input w-full text-xs" 
                           style="height:40px;border-radius:12px;">
                </div>

                <!-- BUTTON ACTIONS -->
                <div class="grid grid-cols-2 gap-2.5 sm:gap-3 pt-3.5" style="border-top:1px solid var(--color-hairline);">
                    <button type="button" @click="showPayModal = false" class="btn btn-secondary rounded-xl py-2.5 font-bold text-xs sm:text-sm">
                        Batal
                    </button>
                    <button type="button" 
                            @click="confirmPaymentSubmit()" 
                            :disabled="payNominal <= 0 || payNominal > selectedInvoice.sisa_tagihan"
                            class="btn btn-primary rounded-xl py-2.5 font-bold flex items-center justify-center gap-1.5 disabled:opacity-40 text-xs sm:text-sm"
                            style="background:#10b981;border-color:#10b981;color:#fff;">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Simpan Kas</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function piutangApp() {
    return {
        showPayModal: false,
        selectedInvoice: {},
        payNominal: 0,

        openPayModal(inv) {
            this.selectedInvoice = inv;
            this.payNominal = parseFloat(inv.sisa_tagihan) || 0;
            this.showPayModal = true;
        },

        async confirmPaymentSubmit() {
            if (this.payNominal <= 0) return;
            const rpFormatted = this.formatRupiah(this.payNominal);
            const nota = this.selectedInvoice.nomor_nota;
            const toko = this.selectedInvoice.nama_toko;

            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Konfirmasi Penerimaan Kas',
                message: `Catat penerimaan kas sebesar ${rpFormatted} untuk nota ${nota} dari ${toko}?`,
                confirmText: 'Ya, Terima Pembayaran',
                cancelText: 'Batal',
                type: 'primary',
                icon: 'banknote'
            }) : confirm(`Catat penerimaan kas sebesar ${rpFormatted} untuk nota ${nota} dari ${toko}?`);

            if (confirmed) {
                const form = document.getElementById('payForm');
                if (form) form.submit();
            }
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

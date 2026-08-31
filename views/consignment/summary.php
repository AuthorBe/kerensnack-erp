<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div class="space-y-6 pb-20 max-w-3xl mx-auto">

    <!-- ========================================================================= -->
    <!-- SUCCESS BADGE & HEADER                                                     -->
    <!-- ========================================================================= -->
    <div class="card p-6 text-center space-y-3" style="border-radius:20px;background:linear-gradient(180deg, rgba(16,185,129,0.1) 0%, rgba(15,23,42,0.6) 100%);border:1.5px solid rgba(16,185,129,0.35);">
        <div class="w-14 h-14 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto shadow-lg">
            <i data-lucide="check-check" class="w-7 h-7"></i>
        </div>

        <div class="space-y-1">
            <span class="text-xs font-black uppercase tracking-wider text-emerald-400">Kunjungan Berhasil Disimpan</span>
            <h1 class="text-xl md:text-2xl font-black text-slate-100"><?= htmlspecialchars($visit['nama_toko']) ?></h1>
            <div class="text-xs text-slate-400">
                No. Kunjungan: <strong class="text-slate-200"><?= htmlspecialchars($visit['nomor_kunjungan']) ?></strong> &bull; <?= date('d M Y', strtotime($visit['tanggal_kunjungan'])) ?>
            </div>
        </div>

        <!-- Total Omzet Penjualan / Laku -->
        <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 max-w-md mx-auto mt-4">
            <div class="text-xs text-slate-400 font-semibold">Total Nilai Laku Terjual:</div>
            <div class="text-2xl md:text-3xl font-black text-emerald-400 mt-0.5">
                <?= Format::rupiah((float)$visit['total_laku_nominal']) ?>
            </div>

            <?php if (!empty($visit['nomor_nota'])): ?>
            <div class="mt-2.5 pt-2.5 border-t border-slate-800 flex items-center justify-center gap-2 text-xs">
                <i data-lucide="receipt" class="w-4 h-4 text-sky-400"></i>
                <span class="text-slate-300">Nota Faktur Otomatis: <strong class="text-sky-400"><?= htmlspecialchars($visit['nomor_nota']) ?></strong></span>
            </div>
            <?php else: ?>
            <div class="mt-2.5 pt-2.5 border-t border-slate-800 text-xs text-slate-400 font-semibold italic">
                Tidak ada barang laku pada kunjungan ini (Faktur tagihan tidak diterbitkan).
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- RINCIAN PER ITEM HASIL OPNAME                                             -->
    <!-- ========================================================================= -->
    <div class="card p-4 md:p-5 space-y-4" style="border-radius:18px;background:var(--color-surface);">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-extrabold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4 text-amber-400"></i>
                <span>Rincian Hasil Opname Rak</span>
            </h2>
            <span class="text-xs text-slate-400 font-bold"><?= count($details) ?> Varian</span>
        </div>

        <div class="space-y-2.5">
            <?php foreach ($details as $d): ?>
            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                <div class="min-w-0 flex-1">
                    <div class="font-bold text-sm text-slate-200"><?= htmlspecialchars($d['nama_item']) ?></div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                        Harga Deal: <?= Format::rupiah((float)$d['harga_satuan_deal']) ?> / <?= htmlspecialchars($d['satuan_dasar'] ?? 'pcs') ?>
                    </div>
                </div>

                <!-- Snapshot Mutasi -->
                <div class="grid grid-cols-4 gap-2 text-center shrink-0">
                    <div class="p-1.5 rounded-lg bg-slate-950/70 border border-slate-800">
                        <div class="text-[10px] text-slate-400">Titip Awal</div>
                        <div class="font-black text-slate-200"><?= (int)$d['stok_titip_awal'] ?></div>
                    </div>
                    <div class="p-1.5 rounded-lg bg-emerald-950/40 border border-emerald-500/30">
                        <div class="text-[10px] text-emerald-400 font-bold">Laku</div>
                        <div class="font-black text-emerald-400"><?= (int)$d['jumlah_laku_terjual'] ?></div>
                    </div>
                    <div class="p-1.5 rounded-lg bg-slate-950/70 border border-slate-800">
                        <div class="text-[10px] text-slate-400">Sisa Rak</div>
                        <div class="font-black text-amber-300"><?= (int)$d['sisa_fisik_di_rak'] ?></div>
                    </div>
                    <div class="p-1.5 rounded-lg bg-slate-950/70 border border-slate-800">
                        <div class="text-[10px] text-slate-400">Retur</div>
                        <div class="font-black text-rose-400"><?= (int)($d['retur_bagus'] + $d['retur_rusak']) ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($visit['catatan'])): ?>
        <div class="p-3 rounded-xl bg-slate-900/40 border border-slate-800 text-xs text-slate-400">
            <strong class="text-slate-300">Catatan Sales:</strong> <?= nl2br(htmlspecialchars($visit['catatan'])) ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- ACTION BUTTONS: RESTOCK ATAU KEMBALI                                       -->
    <!-- ========================================================================= -->
    <div class="space-y-3 pt-2">
        <!-- Tombol Utama: Ajukan Pengiriman Baru -->
        <a href="<?= Router::url('/consignment/sales/request-delivery?pelanggan_id=' . urlencode($visit['pelanggan_id'])) ?>" 
           class="btn btn-primary w-full py-3.5 px-6 font-black text-base rounded-xl shadow-xl flex items-center justify-center gap-2"
           style="background:#0284c7;border-color:#0284c7;box-shadow:0 8px 24px rgba(2,132,199,0.35);">
            <i data-lucide="package-plus" class="w-5 h-5"></i>
            <span>Toko Ini Minta Kiriman / Restock Baru?</span>
        </a>

        <!-- Tombol Sekunder: Kembali ke Daftar Toko -->
        <a href="<?= Router::url('/consignment/sales') ?>" 
           class="btn btn-secondary w-full py-3 px-6 font-bold text-sm rounded-xl flex items-center justify-center gap-2">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Selesai, Kembali ke Daftar Toko</span>
        </a>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

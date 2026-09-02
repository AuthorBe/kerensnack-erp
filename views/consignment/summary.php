<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div class="space-y-4 sm:space-y-6 pb-20 max-w-3xl mx-auto">

    <!-- ========================================================================= -->
    <!-- SUCCESS BADGE & HEADER                                                     -->
    <!-- ========================================================================= -->
    <div class="card p-5 sm:p-6 text-center space-y-3" style="border-radius:20px;border:1px solid var(--color-hairline);background:var(--color-surface);">
        <i data-lucide="check-circle-2" class="w-12 sm:w-14 h-12 sm:h-14 mx-auto text-emerald-500"></i>

        <div class="space-y-1">
            <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-emerald-500">Kunjungan Berhasil Disimpan</span>
            <h1 class="text-lg sm:text-2xl font-black" style="color:var(--color-ink);"><?= htmlspecialchars($visit['nama_toko']) ?></h1>
            <div class="text-xs" style="color:var(--color-ink-mute);">
                No. Kunjungan: <strong style="color:var(--color-ink);"><?= htmlspecialchars($visit['nomor_kunjungan']) ?></strong> &bull; <?= date('d M Y', strtotime($visit['tanggal_kunjungan'])) ?>
            </div>
        </div>

        <!-- Total Omzet Penjualan / Laku -->
        <div class="p-3.5 sm:p-4 rounded-2xl max-w-md mx-auto mt-3 sm:mt-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
            <div class="text-xs font-semibold" style="color:var(--color-ink-mute);">Total Nilai Laku Terjual:</div>
            <div class="text-xl sm:text-3xl font-black text-emerald-500 mt-0.5">
                <?= Format::rupiah((float)$visit['total_laku_nominal']) ?>
            </div>

            <?php if (!empty($visit['nomor_nota'])): ?>
            <div class="mt-2.5 pt-2.5 flex items-center justify-center gap-1.5 text-xs" style="border-top:1px solid var(--color-hairline);">
                <i data-lucide="receipt" class="w-4 h-4 text-sky-500"></i>
                <span style="color:var(--color-ink-secondary);">Nota Faktur: <strong class="text-sky-500 font-mono"><?= htmlspecialchars($visit['nomor_nota']) ?></strong></span>
            </div>
            <?php else: ?>
            <div class="mt-2.5 pt-2.5 text-xs font-semibold italic" style="border-top:1px solid var(--color-hairline);color:var(--color-ink-mute);">
                Tidak ada barang laku pada kunjungan ini (Faktur tagihan tidak diterbitkan).
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- RINCIAN PER ITEM HASIL OPNAME                                             -->
    <!-- ========================================================================= -->
    <div class="card p-4 sm:p-5 space-y-3.5 sm:space-y-4" style="border-radius:18px;border:1px solid var(--color-hairline);">
        <div class="flex items-center justify-between">
            <h2 class="text-xs sm:text-sm font-extrabold uppercase tracking-wider flex items-center gap-2" style="color:var(--color-ink);">
                <i data-lucide="list" class="w-4 h-4 text-amber-500"></i>
                <span>Rincian Hasil Opname Rak</span>
            </h2>
            <span class="text-xs font-bold" style="color:var(--color-ink-mute);"><?= count($details) ?> Varian</span>
        </div>

        <div class="space-y-2.5">
            <?php foreach ($details as $d): ?>
            <div class="p-3 sm:p-3.5 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3 text-xs" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div>
                    <div class="font-bold text-xs sm:text-sm" style="color:var(--color-ink);"><?= htmlspecialchars($d['nama_item']) ?></div>
                    <div class="text-[11px] mt-0.5" style="color:var(--color-ink-mute);">
                        Sisa Rak: <strong style="color:var(--color-ink);"><?= $d['sisa_fisik_di_rak'] ?> <?= $d['satuan_dasar'] ?></strong> | 
                        Retur Bagus: <strong style="color:var(--color-ink);"><?= $d['retur_bagus'] ?> <?= $d['satuan_dasar'] ?></strong> | 
                        Retur Rusak: <strong class="text-rose-500"><?= $d['retur_rusak'] ?> <?= $d['satuan_dasar'] ?></strong>
                    </div>
                </div>

                <div class="sm:text-right flex sm:flex-col justify-between items-end border-t sm:border-t-0 pt-2 sm:pt-0" style="border-color:var(--color-hairline);">
                    <div>
                        <span style="color:var(--color-ink-mute);">Laku: </span>
                        <strong class="text-emerald-500 text-xs sm:text-sm"><?= $d['jumlah_laku_terjual'] ?> <?= $d['satuan_dasar'] ?></strong>
                    </div>
                    <div class="font-bold text-xs sm:text-sm" style="color:var(--color-ink);">
                        <?= Format::rupiah((float)$d['subtotal_laku']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($visit['catatan'])): ?>
        <div class="p-3 rounded-xl text-xs" style="background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-ink-secondary);">
            <strong style="color:var(--color-ink);">Catatan:</strong> <?= htmlspecialchars($visit['catatan']) ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="flex items-center justify-between gap-3 pt-2">
        <a href="<?= Router::url('/consignment/opname') ?>" class="btn btn-secondary flex-1 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali ke Daftar Toko</span>
        </a>
        <a href="<?= Router::url('/consignment') ?>" class="btn btn-primary flex-1 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2">
            <i data-lucide="store" class="w-4 h-4"></i>
            <span>Portal Konsinyasi</span>
        </a>
    </div>

</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

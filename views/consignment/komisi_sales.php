<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div class="space-y-4 sm:space-y-6 pb-20">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-header-body">
            <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary btn-sm p-2 rounded-xl" title="Kembali ke Portal">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#f59e0b;"></span>
                    <span>Insentif &amp; Penggajian • Admin &amp; Owner</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl">Rekap Komisi Sales</h1>
                <p class="page-subtitle text-xs sm:text-sm">Perhitungan insentif komisi bulanan berdasarkan omzet laku toko binaan tetap.</p>
            </div>
        </div>
    </div>

    <!-- FILTER PERIODE BULAN -->
    <div class="card p-3.5 sm:p-4 rounded-3xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4" style="border:1px solid var(--color-hairline);">
        <form method="GET" action="<?= Router::url('/consignment/komisi-sales') ?>" class="flex items-center gap-2.5 sm:gap-3 flex-wrap w-full sm:w-auto">
            <label class="text-xs font-bold" style="color:var(--color-ink);">Periode Bulan:</label>
            <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" class="form-input text-xs" style="height:38px;border-radius:10px;">
            <button type="submit" class="btn btn-primary py-2 px-3.5 text-xs font-bold flex items-center gap-1.5" style="background:#f59e0b;border-color:#f59e0b;color:#090d16;height:38px;border-radius:10px;">
                <i data-lucide="calendar" class="w-4 h-4"></i>
                <span>Tampilkan</span>
            </button>
        </form>

        <div class="text-[11px] sm:text-xs" style="color:var(--color-ink-mute);">
            Rentang: <strong><?= date('01/m/Y', strtotime($startDate)) ?></strong> s/d <strong><?= date('d/m/Y', strtotime($endDate)) ?></strong>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
        <div class="card p-4 sm:p-5 rounded-2xl" style="border:1px solid var(--color-hairline);">
            <span class="text-[10.5px] sm:text-xs font-bold uppercase tracking-wider block" style="color:var(--color-ink-mute);">Total Omzet Laku:</span>
            <div class="text-xl sm:text-2xl font-black text-emerald-500 mt-1"><?= Format::rupiah((float)$grandOmzet) ?></div>
            <span class="text-[10px] sm:text-[11px] mt-0.5 block" style="color:var(--color-ink-mute);">Dari seluruh toko konsinyasi aktif bulan ini</span>
        </div>

        <div class="card p-4 sm:p-5 rounded-2xl" style="background:rgba(245,158,11,0.04);border:1px solid var(--color-hairline);">
            <span class="text-[10.5px] sm:text-xs font-bold uppercase tracking-wider block" style="color:#f59e0b;">Total Alokasi Komisi:</span>
            <div class="text-xl sm:text-2xl font-black mt-1" style="color:#f59e0b;"><?= Format::rupiah((float)$grandKomisi) ?></div>
            <span class="text-[10px] sm:text-[11px] mt-0.5 block" style="color:var(--color-ink-mute);">Total beban komisi operasional bulanan</span>
        </div>
    </div>

    <!-- TABLE KOMISI -->
    <?php if (empty($commissions)): ?>
        <div class="card p-8 sm:p-12 text-center rounded-3xl" style="border:1px solid var(--color-hairline);">
            <i data-lucide="percent" class="w-12 h-12 mx-auto mb-3" style="color:var(--color-ink-mute);"></i>
            <h3 class="text-base sm:text-lg font-bold" style="color:var(--color-ink);">Belum Ada Data Komisi</h3>
            <p class="text-xs sm:text-sm mt-1 max-w-md mx-auto" style="color:var(--color-ink-mute);">
                Belum ada transaksi penjualan konsinyasi yang tercatat untuk periode bulan <?= date('F Y', strtotime($startDate)) ?>.
            </p>
        </div>
    <?php else: ?>
        <div class="card rounded-3xl overflow-hidden" style="border:1px solid var(--color-hairline);">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[600px]">
                    <thead>
                        <tr class="font-bold uppercase tracking-wider text-[10.5px]" style="background:var(--color-canvas);border-bottom:1px solid var(--color-hairline);color:var(--color-ink-mute);">
                            <th class="py-3.5 px-4">Nama Sales Lapangan</th>
                            <th class="py-3.5 px-4 text-center">Toko Binaan Tetap</th>
                            <th class="py-3.5 px-4 text-right">Total Omzet Laku (Rp)</th>
                            <th class="py-3.5 px-4 text-center">Rate Komisi</th>
                            <th class="py-3.5 px-4 text-right">Nominal Komisi (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color:var(--color-hairline);">
                        <?php foreach ($commissions as $c): ?>
                        <tr class="hover:bg-slate-500/5 transition-colors">
                            <td class="py-3.5 px-4 font-bold" style="color:var(--color-ink);">
                                <span class="text-xs sm:text-sm"><?= htmlspecialchars($c['nama_karyawan']) ?></span>
                                <span class="text-[10px] block font-normal" style="color:var(--color-ink-mute);"><?= htmlspecialchars($c['nomor_telepon'] ?? '-') ?></span>
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold" style="color:var(--color-ink-secondary);">
                                <?= $c['total_toko_assigned'] ?> Toko
                            </td>

                            <td class="py-3.5 px-4 text-right font-black" style="color:var(--color-ink);">
                                <?= Format::rupiah((float)$c['total_omzet']) ?>
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold text-sky-600 dark:text-sky-400">
                                <?= number_format((float)$c['persentase_komisi'], 1) ?> %
                            </td>

                            <td class="py-3.5 px-4 text-right font-black text-amber-500 text-xs sm:text-sm">
                                <?= Format::rupiah((float)$c['nominal_komisi']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="font-bold text-xs" style="background:var(--color-canvas);border-top:2px solid var(--color-hairline);">
                            <td colspan="2" class="py-3.5 px-4" style="color:var(--color-ink-mute);">TOTAL:</td>
                            <td class="py-3.5 px-4 text-right font-black" style="color:var(--color-ink);"><?= Format::rupiah((float)$grandOmzet) ?></td>
                            <td class="py-3.5 px-4 text-center" style="color:var(--color-ink-mute);">—</td>
                            <td class="py-3.5 px-4 text-right text-amber-500 text-sm font-black"><?= Format::rupiah((float)$grandKomisi) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

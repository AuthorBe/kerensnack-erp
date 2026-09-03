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
                    <span class="tag-dot" style="background-color:#ef4444;"></span>
                    <span>Audit Kerugian Stok • Admin &amp; Owner</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl">Laporan Kerugian Rusak</h1>
                <p class="page-subtitle text-xs sm:text-sm">Valuasi HPP resmi barang retur rusak/bocor/BS yang ditarik saat kunjungan opname rak.</p>
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="card p-3.5 sm:p-4 rounded-3xl" style="border:1px solid var(--color-hairline);">
        <form method="GET" action="<?= Router::url('/consignment/kerugian-rusak') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs font-bold mb-1" style="color:var(--color-ink);">Dari Tanggal:</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-input w-full text-xs" style="height:38px;border-radius:10px;">
            </div>

            <div>
                <label class="block text-xs font-bold mb-1" style="color:var(--color-ink);">Sampai Tanggal:</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-input w-full text-xs" style="height:38px;border-radius:10px;">
            </div>

            <div>
                <label class="block text-xs font-bold mb-1" style="color:var(--color-ink);">Filter Toko:</label>
                <select name="pelanggan_id" class="form-input w-full text-xs searchable-select" style="height:38px;border-radius:10px;">
                    <option value="">Semua Toko</option>
                    <?php foreach ($stores as $st): ?>
                    <option value="<?= $st['id'] ?>" <?= $selectedStoreId === $st['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($st['nama_toko']) ?><?= !empty($st['kode_pelanggan']) ? ' (' . htmlspecialchars($st['kode_pelanggan']) . ')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary flex-1 py-2 text-xs font-bold flex items-center justify-center gap-1.5" style="background:#ef4444;border-color:#ef4444;height:38px;border-radius:10px;">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Filter Laporan</span>
                </button>
                <a href="<?= Router::url('/consignment/kerugian-rusak') ?>" class="btn btn-secondary py-2 px-3 text-xs flex items-center justify-center" style="height:38px;border-radius:10px;" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- SUMMARY METRIC CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
        <div class="card p-4 sm:p-5 rounded-2xl" style="background:rgba(239,68,68,0.04);border:1px solid var(--color-hairline);">
            <span class="text-[10.5px] sm:text-xs font-bold uppercase tracking-wider block" style="color:#ef4444;">Total Kerugian Rusak:</span>
            <div class="text-xl sm:text-3xl font-black text-red-500 mt-1"><?= Format::rupiah((float)$totalLossNominal) ?></div>
            <span class="text-[10px] sm:text-[11px] mt-0.5 block" style="color:var(--color-ink-mute);">Valuasi berdasarkan snapshot HPP</span>
        </div>

        <div class="card p-4 sm:p-5 rounded-2xl" style="border:1px solid var(--color-hairline);">
            <span class="text-[10.5px] sm:text-xs font-bold uppercase tracking-wider block" style="color:var(--color-ink-mute);">Total Fisik Rusak:</span>
            <div class="text-xl sm:text-3xl font-black mt-1" style="color:var(--color-ink);"><?= number_format((float)$totalPcsRusak) ?> pcs</div>
            <span class="text-[10px] sm:text-[11px] mt-0.5 block" style="color:var(--color-ink-mute);">Tercatat di mutasi riwayat_stok</span>
        </div>
    </div>

    <!-- TABLE LAPORAN KERUGIAN -->
    <?php if (empty($losses)): ?>
        <div class="card p-8 sm:p-12 text-center rounded-3xl" style="border:1px solid var(--color-hairline);">
            <i data-lucide="check-circle-2" class="w-12 h-12 mx-auto mb-3" style="color:#10b981;"></i>
            <h3 class="text-base sm:text-lg font-bold" style="color:var(--color-ink);">Tidak Ada Barang Rusak</h3>
            <p class="text-xs sm:text-sm mt-1 max-w-md mx-auto" style="color:var(--color-ink-mute);">
                Tidak ada laporan barang retur rusak atau bocor pada rentang tanggal yang dipilih.
            </p>
        </div>
    <?php else: ?>
        <div class="card rounded-3xl overflow-hidden" style="border:1px solid var(--color-hairline);">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[650px]">
                    <thead>
                        <tr class="font-bold uppercase tracking-wider text-[10.5px]" style="background:var(--color-canvas);border-bottom:1px solid var(--color-hairline);color:var(--color-ink-mute);">
                            <th class="py-3.5 px-4">Tanggal</th>
                            <th class="py-3.5 px-4">Toko Mitra</th>
                            <th class="py-3.5 px-4">Item Produk</th>
                            <th class="py-3.5 px-4 text-center">Qty Rusak</th>
                            <th class="py-3.5 px-4 text-right">HPP Satuan</th>
                            <th class="py-3.5 px-4 text-right">Nilai Kerugian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color:var(--color-hairline);">
                        <?php foreach ($losses as $loss): ?>
                        <tr class="hover:bg-slate-500/5 transition-colors">
                            <td class="py-3.5 px-4" style="color:var(--color-ink-secondary);">
                                <?= date('d/m/Y', strtotime($loss['tanggal_kunjungan'])) ?>
                                <span class="text-[9.5px] block font-mono" style="color:var(--color-ink-mute);"><?= htmlspecialchars($loss['nomor_kunjungan']) ?></span>
                            </td>

                            <td class="py-3.5 px-4 font-bold" style="color:var(--color-ink);">
                                <span class="text-xs sm:text-sm"><?= htmlspecialchars($loss['nama_toko']) ?></span>
                                <span class="text-[10px] block font-normal" style="color:var(--color-ink-mute);"><?= htmlspecialchars($loss['nama_sales'] ?? 'Sales') ?></span>
                            </td>

                            <td class="py-3.5 px-4 font-bold" style="color:var(--color-ink);">
                                <span class="text-xs sm:text-sm"><?= htmlspecialchars($loss['nama_item']) ?></span>
                                <span class="text-[10px] block font-mono font-normal" style="color:var(--color-ink-mute);"><?= htmlspecialchars($loss['kode_sku'] ?? '-') ?></span>
                            </td>

                            <td class="py-3.5 px-4 text-center font-black text-rose-500 text-xs sm:text-sm">
                                <?= (int)$loss['retur_rusak'] ?> <?= $loss['satuan_dasar'] ?>
                            </td>

                            <td class="py-3.5 px-4 text-right" style="color:var(--color-ink-mute);">
                                <?= Format::rupiah((float)$loss['harga_pokok_satuan']) ?>
                            </td>

                            <td class="py-3.5 px-4 text-right font-black text-red-500 text-xs sm:text-sm">
                                <?= Format::rupiah((float)$loss['nilai_kerugian_rusak']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="font-bold text-xs" style="background:var(--color-canvas);border-top:2px solid var(--color-hairline);">
                            <td colspan="3" class="py-3.5 px-4" style="color:var(--color-ink-mute);">TOTAL KERUGIAN:</td>
                            <td class="py-3.5 px-4 text-center text-rose-500 font-black"><?= number_format((float)$totalPcsRusak) ?> pcs</td>
                            <td class="py-3.5 px-4 text-right" style="color:var(--color-ink-mute);">—</td>
                            <td class="py-3.5 px-4 text-right text-red-500 text-sm sm:text-base font-black"><?= Format::rupiah((float)$totalLossNominal) ?></td>
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

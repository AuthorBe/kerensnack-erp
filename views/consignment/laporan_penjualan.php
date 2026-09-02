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
                    <span class="tag-dot" style="background-color:#10b981;"></span>
                    <span>Laporan &amp; Settlement • <?= $isSales ? 'Toko Binaan' : 'Semua Mitra' ?></span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl">Laporan Penjualan Konsinyasi</h1>
                <p class="page-subtitle text-xs sm:text-sm">Rekapitulasi omzet penjualan laku dan status faktur hasil kunjungan opname.</p>
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="card p-3.5 sm:p-4 rounded-3xl" style="border:1px solid var(--color-hairline);">
        <form method="GET" action="<?= Router::url('/consignment/laporan-penjualan') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
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
                <select name="pelanggan_id" class="form-input w-full text-xs" style="height:38px;border-radius:10px;">
                    <option value="">Semua Toko</option>
                    <?php foreach ($stores as $st): ?>
                    <option value="<?= $st['id'] ?>" <?= $selectedStoreId === $st['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($st['nama_toko']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary flex-1 py-2 text-xs font-bold flex items-center justify-center gap-1.5" style="background:#10b981;border-color:#10b981;height:38px;border-radius:10px;">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Terapkan Filter</span>
                </button>
                <a href="<?= Router::url('/consignment/laporan-penjualan') ?>" class="btn btn-secondary py-2 px-3 text-xs flex items-center justify-center" style="height:38px;border-radius:10px;" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- KPI AGGREGATION CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="card p-3.5 sm:p-4 rounded-2xl" style="border:1px solid var(--color-hairline);">
            <span class="text-[11px] sm:text-xs font-bold block" style="color:var(--color-ink-mute);">Total Penjualan Laku:</span>
            <div class="text-base sm:text-2xl font-black text-emerald-500 mt-1"><?= Format::rupiah($totalLaku) ?></div>
            <span class="text-[10px] sm:text-[11px] mt-0.5 block" style="color:var(--color-ink-mute);">Periode terpilih</span>
        </div>

        <div class="card p-3.5 sm:p-4 rounded-2xl" style="border:1px solid var(--color-hairline);">
            <span class="text-[11px] sm:text-xs font-bold block" style="color:var(--color-ink-mute);">Total Faktur Terbit:</span>
            <div class="text-base sm:text-2xl font-black mt-1" style="color:var(--color-ink);"><?= $totalNotaCount ?> Nota</div>
            <span class="text-[10px] sm:text-[11px] mt-0.5 block" style="color:var(--color-ink-mute);">Dari <?= count($reports) ?> kunjungan</span>
        </div>

        <div class="card p-3.5 sm:p-4 rounded-2xl" style="border:1px solid var(--color-hairline);">
            <span class="text-[11px] sm:text-xs font-bold block" style="color:var(--color-ink-mute);">Sudah Dilunasi:</span>
            <div class="text-base sm:text-2xl font-black text-sky-500 mt-1"><?= Format::rupiah($totalDibayar) ?></div>
            <span class="text-[10px] sm:text-[11px] mt-0.5 block" style="color:var(--color-ink-mute);">Masuk kas</span>
        </div>

        <div class="card p-3.5 sm:p-4 rounded-2xl" style="border:1px solid var(--color-hairline);">
            <span class="text-[11px] sm:text-xs font-bold block" style="color:var(--color-ink-mute);">Sisa Piutang:</span>
            <div class="text-base sm:text-2xl font-black text-rose-500 mt-1"><?= Format::rupiah($totalPiutang) ?></div>
            <span class="text-[10px] sm:text-[11px] mt-0.5 block" style="color:var(--color-ink-mute);">Belum lunas</span>
        </div>
    </div>

    <!-- TABLE LAPORAN -->
    <?php if (empty($reports)): ?>
        <div class="card p-8 sm:p-12 text-center rounded-3xl" style="border:1px solid var(--color-hairline);">
            <i data-lucide="bar-chart-3" class="w-12 h-12 mx-auto mb-3" style="color:var(--color-ink-mute);"></i>
            <h3 class="text-base sm:text-lg font-bold" style="color:var(--color-ink);">Belum Ada Data Penjualan</h3>
            <p class="text-xs sm:text-sm mt-1 max-w-md mx-auto" style="color:var(--color-ink-mute);">
                Tidak ada data transaksi kunjungan konsinyasi pada rentang tanggal atau filter yang dipilih.
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
                            <th class="py-3.5 px-4">No. Kunjungan</th>
                            <th class="py-3.5 px-4">No. Nota / Faktur</th>
                            <th class="py-3.5 px-4 text-center">Qty Laku</th>
                            <th class="py-3.5 px-4 text-right">Total Laku (Rp)</th>
                            <th class="py-3.5 px-4 text-center">Status Bayar</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color:var(--color-hairline);">
                        <?php foreach ($reports as $r): 
                            $status = $r['status_pembayaran'] ?? 'belum_lunas';
                        ?>
                        <tr class="hover:bg-slate-500/5 transition-colors">
                            <td class="py-3.5 px-4" style="color:var(--color-ink-secondary);">
                                <?= date('d/m/Y', strtotime($r['tanggal_kunjungan'])) ?>
                            </td>

                            <td class="py-3.5 px-4 font-bold" style="color:var(--color-ink);">
                                <span class="text-xs sm:text-sm"><?= htmlspecialchars($r['nama_toko']) ?></span>
                                <span class="text-[10px] block font-normal" style="color:var(--color-ink-mute);"><?= htmlspecialchars($r['nama_sales'] ?? 'Sales') ?></span>
                            </td>

                            <td class="py-3.5 px-4 font-mono text-[11px]" style="color:var(--color-ink-mute);">
                                <?= htmlspecialchars($r['nomor_kunjungan']) ?>
                            </td>

                            <td class="py-3.5 px-4 font-mono text-[11px]">
                                <?= !empty($r['nomor_nota']) ? '<span class="text-sky-600 dark:text-sky-400 font-bold">' . htmlspecialchars($r['nomor_nota']) . '</span>' : '<span style="color:var(--color-ink-mute);">-</span>' ?>
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold" style="color:var(--color-ink);">
                                <?= (int)$r['total_qty_laku'] ?> pcs
                            </td>

                            <td class="py-3.5 px-4 text-right font-black <?= (float)$r['total_laku_nominal'] > 0 ? 'text-emerald-500' : '' ?>" style="<?= (float)$r['total_laku_nominal'] === 0.0 ? 'color:var(--color-ink-mute);' : '' ?>">
                                <?= Format::rupiah((float)$r['total_laku_nominal']) ?>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <?php if (empty($r['nomor_nota'])): ?>
                                    <span style="color:var(--color-ink-mute);font-size:9.5px;">Nihil Laku</span>
                                <?php elseif ($status === 'lunas'): ?>
                                    <span style="background:rgba(16,185,129,0.12);color:#10b981;border:1px solid rgba(16,185,129,0.25);padding:2px 7px;border-radius:12px;font-weight:700;font-size:9.5px;">LUNAS</span>
                                <?php elseif ($status === 'sebagian'): ?>
                                    <span style="background:rgba(245,158,11,0.12);color:#f59e0b;border:1px solid rgba(245,158,11,0.25);padding:2px 7px;border-radius:12px;font-weight:700;font-size:9.5px;">SEBAGIAN</span>
                                <?php else: ?>
                                    <span style="background:rgba(244,63,94,0.12);color:#f43f5e;border:1px solid rgba(244,63,94,0.25);padding:2px 7px;border-radius:12px;font-weight:700;font-size:9.5px;">BELUM LUNAS</span>
                                <?php endif; ?>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <a href="<?= Router::url('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)$r['kunjungan_id'])) ?>" class="btn btn-secondary btn-sm p-1.5 rounded-lg" title="Lihat Rincian">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

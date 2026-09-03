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
                    <span class="tag-dot" style="background-color:#6366f1;"></span>
                    <span>Audit Trail • Admin &amp; Owner</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl">Riwayat Kunjungan</h1>
                <p class="page-subtitle text-xs sm:text-sm">Log riwayat audit seluruh kunjungan opname dan settlement sales ke toko mitra.</p>
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="card p-3.5 sm:p-4 rounded-3xl" style="border:1px solid var(--color-hairline);">
        <form method="GET" action="<?= Router::url('/consignment/riwayat-kunjungan') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
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
                <button type="submit" class="btn btn-primary flex-1 py-2 text-xs font-bold flex items-center justify-center gap-1.5" style="background:#6366f1;border-color:#6366f1;height:38px;border-radius:10px;">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Terapkan Filter</span>
                </button>
                <a href="<?= Router::url('/consignment/riwayat-kunjungan') ?>" class="btn btn-secondary py-2 px-3 text-xs flex items-center justify-center" style="height:38px;border-radius:10px;" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- TABLE RIWAYAT -->
    <?php if (empty($visits)): ?>
        <div class="card p-8 sm:p-12 text-center rounded-3xl" style="border:1px solid var(--color-hairline);">
            <i data-lucide="history" class="w-12 h-12 mx-auto mb-3" style="color:var(--color-ink-mute);"></i>
            <h3 class="text-base sm:text-lg font-bold" style="color:var(--color-ink);">Belum Ada Riwayat Kunjungan</h3>
            <p class="text-xs sm:text-sm mt-1 max-w-md mx-auto" style="color:var(--color-ink-mute);">
                Tidak ada data riwayat kunjungan konsinyasi yang sesuai dengan filter tanggal atau toko yang dipilih.
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
                            <th class="py-3.5 px-4">Petugas / Sales</th>
                            <th class="py-3.5 px-4 text-center">SKU</th>
                            <th class="py-3.5 px-4 text-center">Total Laku</th>
                            <th class="py-3.5 px-4 text-right">Nilai Laku (Rp)</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color:var(--color-hairline);">
                        <?php foreach ($visits as $v): ?>
                        <tr class="hover:bg-slate-500/5 transition-colors">
                            <td class="py-3.5 px-4 font-medium" style="color:var(--color-ink-secondary);">
                                <?= date('d/m/Y', strtotime($v['tanggal_kunjungan'])) ?>
                            </td>

                            <td class="py-3.5 px-4 font-bold" style="color:var(--color-ink);">
                                <span class="text-xs sm:text-sm"><?= htmlspecialchars($v['nama_toko']) ?></span>
                                <span class="text-[10px] block font-mono" style="color:var(--color-ink-mute);"><?= htmlspecialchars($v['kode_pelanggan'] ?? '') ?></span>
                            </td>

                            <td class="py-3.5 px-4 font-mono font-bold text-sky-600 dark:text-sky-400">
                                <?= htmlspecialchars($v['nomor_kunjungan']) ?>
                            </td>

                            <td class="py-3.5 px-4" style="color:var(--color-ink-secondary);">
                                <?= htmlspecialchars($v['nama_sales']) ?>
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold" style="color:var(--color-ink);">
                                <?= $v['total_sku'] ?> SKU
                            </td>

                            <td class="py-3.5 px-4 text-center font-black <?= (int)$v['total_laku'] > 0 ? 'text-emerald-500' : '' ?>" style="<?= (int)$v['total_laku'] === 0 ? 'color:var(--color-ink-mute);' : '' ?>">
                                <?= (int)$v['total_laku'] ?> pcs
                            </td>

                            <td class="py-3.5 px-4 text-right font-black <?= (float)$v['total_laku_nominal'] > 0 ? 'text-emerald-500' : '' ?>" style="<?= (float)$v['total_laku_nominal'] === 0.0 ? 'color:var(--color-ink-mute);' : '' ?>">
                                <?= Format::rupiah((float)$v['total_laku_nominal']) ?>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <a href="<?= Router::url('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)$v['id'])) ?>" class="btn btn-secondary btn-sm p-1.5 rounded-lg flex items-center justify-center gap-1 text-[11px] font-bold" title="Buka Detail">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>Detail</span>
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

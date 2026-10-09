<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div class="space-y-4 sm:space-y-6 pb-20">

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
                    <span class="tag-dot" style="background-color:#10b981;"></span>
                    <span>Analitik Grosir B2B &bull; Manajemen &amp; Owner</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl"><?= $pageTitle ?? 'Laporan Penjualan per Toko B2B' ?></h1>
                <p class="page-subtitle text-xs sm:text-sm"><?= $pageSubtitle ?? 'Analitik Omzet, Frekuensi Pemesanan Grosir &amp; Kontribusi Penjualan per Toko Mitra' ?></p>
            </div>
        </div>
        <div class="page-header-actions flex items-center gap-2">
            <button onclick="window.print()" class="btn btn-secondary flex items-center gap-2 text-xs font-bold" style="border-radius:12px;">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak Laporan</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. FILTER PERIODE & WILAYAH                                               -->
    <!-- ========================================================================= -->
    <div class="card p-3 sm:p-4 rounded-2xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
        <form method="GET" action="<?= Router::url('/reguler/laporan-toko') ?>" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 flex-1 flex-wrap">
                <div class="flex items-center gap-1.5 text-xs font-bold text-ink-mute">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    <span>Periode:</span>
                </div>
                <input type="date" name="start_date" value="<?= htmlspecialchars($filter['start_date']) ?>" class="form-input text-xs font-mono font-semibold" style="height:38px;border-radius:10px;width:140px;">
                <span class="text-xs text-ink-mute">s/d</span>
                <input type="date" name="end_date" value="<?= htmlspecialchars($filter['end_date']) ?>" class="form-input text-xs font-mono font-semibold" style="height:38px;border-radius:10px;width:140px;">

                <!-- Filter Wilayah -->
                <select name="wilayah_id" class="form-select text-xs font-medium" style="height:38px;border-radius:10px;">
                    <option value="">-- Semua Wilayah --</option>
                    <?php foreach ($wilayahList as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= ($filter['wilayah_id'] ?? '') === $w['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($w['nama_wilayah']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex items-center gap-1.5 font-bold" style="height:38px;border-radius:10px;">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Tampilkan Data</span>
                </button>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. KPI RINGKASAN PERFORMA PENJUALAN GROSIR                                -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <!-- Total Omzet B2B -->
        <div class="stat-card p-3.5 sm:p-4 rounded-2xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-ink-mute block mb-1">Total Omzet Bersih</span>
            <div class="font-mono font-black text-base sm:text-xl text-emerald-600 dark:text-emerald-400" style="font-variant-numeric:tabular-nums;">
                <?= Format::rupiah((float)($summary['total_omzet'] ?? 0)) ?>
            </div>
            <div class="text-[10.5px] mt-1 text-ink-mute">
                Dari <strong><?= (int)($summary['toko_aktif_belanja'] ?? 0) ?></strong> toko bertransaksi
            </div>
        </div>

        <!-- Total Volume Produk -->
        <div class="stat-card p-3.5 sm:p-4 rounded-2xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-ink-mute block mb-1">Volume Penjualan</span>
            <div class="font-mono font-black text-base sm:text-xl text-ink" style="font-variant-numeric:tabular-nums;">
                <?= number_format((float)($summary['total_pcs'] ?? 0)) ?> <span class="text-xs font-semibold text-ink-mute">Pcs</span>
            </div>
            <div class="text-[10.5px] mt-1 text-ink-mute">
                Total snack terdistribusi
            </div>
        </div>

        <!-- Total Lembar PO -->
        <div class="stat-card p-3.5 sm:p-4 rounded-2xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-ink-mute block mb-1">Total Frekuensi Order</span>
            <div class="font-mono font-black text-base sm:text-xl text-blue-600 dark:text-blue-400" style="font-variant-numeric:tabular-nums;">
                <?= (int)($summary['total_transaksi'] ?? 0) ?> <span class="text-xs font-semibold text-ink-mute">Nota</span>
            </div>
            <div class="text-[10.5px] mt-1 text-ink-mute">
                Faktur terbit periode ini
            </div>
        </div>

        <!-- Rata-rata Nilai Nota -->
        <div class="stat-card p-3.5 sm:p-4 rounded-2xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-ink-mute block mb-1">Rata-Rata per Nota</span>
            <div class="font-mono font-black text-base sm:text-xl text-indigo-600 dark:text-indigo-400" style="font-variant-numeric:tabular-nums;">
                <?= Format::rupiah((float)($summary['avg_order_value'] ?? 0)) ?>
            </div>
            <div class="text-[10.5px] mt-1 text-ink-mute">
                Rata-rata belanja per transaksi
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. TABEL ANALITIK PERFORMA PER TOKO B2B                                   -->
    <!-- ========================================================================= -->
    <div class="card p-0 rounded-2xl overflow-hidden" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
        <div class="p-4 sm:p-5 border-b flex items-center justify-between" style="border-color:var(--color-hairline);">
            <div>
                <h2 class="text-sm sm:text-base font-black text-ink">Peringkat &amp; Kontribusi Penjualan per Toko</h2>
                <p class="text-xs text-ink-mute mt-0.5">Analisis Pareto kontribusi omzet dan rasio pelunasan toko periode <?= date('d/m/Y', strtotime($filter['start_date'])) ?> s/d <?= date('d/m/Y', strtotime($filter['end_date'])) ?></p>
            </div>
            <span class="badge badge-mono text-xs"><?= count($storeAnalytics) ?> Toko Terdata</span>
        </div>

        <?php if (empty($storeAnalytics)): ?>
            <div class="p-10 text-center text-ink-mute">
                <i data-lucide="bar-chart-2" class="w-12 h-12 mx-auto mb-2 opacity-40"></i>
                <div class="font-bold text-sm">Tidak ada transaksi pesanan grosir pada periode ini</div>
                <div class="text-xs mt-0.5">Silakan pilih rentang tanggal lain atau periksa data pesanan.</div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="data-table min-w-[850px]">
                    <thead>
                        <tr>
                            <th style="width:40px;" class="cell-center">Rank</th>
                            <th>Toko Mitra &amp; Kontak</th>
                            <th>Wilayah &amp; Sales</th>
                            <th class="cell-center">Frekuensi</th>
                            <th class="cell-right">Volume (Pcs)</th>
                            <th class="cell-right">Omzet Netto</th>
                            <th class="cell-right">Terbayar</th>
                            <th class="cell-right">Sisa Tagihan</th>
                            <th class="cell-center" style="width:80px;">Rasio Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($storeAnalytics as $idx => $st): 
                            $omzet = (float)$st['total_omzet_netto'];
                            $bayar = (float)$st['total_terbayar'];
                            $rasio = $omzet > 0 ? round(($bayar / $omzet) * 100) : 0;
                        ?>
                        <tr>
                            <td class="cell-center">
                                <?php if ($idx === 0): ?>
                                    <span class="badge" style="background:#fef3c7;color:#b45309;font-weight:900;">🥇 1</span>
                                <?php elseif ($idx === 1): ?>
                                    <span class="badge" style="background:#f1f5f9;color:#475569;font-weight:900;">🥈 2</span>
                                <?php elseif ($idx === 2): ?>
                                    <span class="badge" style="background:#ffedd5;color:#c2410c;font-weight:900;">🥉 3</span>
                                <?php else: ?>
                                    <span class="font-mono font-bold text-ink-mute"><?= $idx + 1 ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="font-bold text-sm text-ink"><?= htmlspecialchars($st['nama_toko']) ?></div>
                                <div class="text-xs text-ink-mute font-mono">
                                    <?= htmlspecialchars($st['kode_pelanggan']) ?>
                                    <?= !empty($st['nama_pemilik']) ? ' &bull; ' . htmlspecialchars($st['nama_pemilik']) : '' ?>
                                </div>
                            </td>
                            <td>
                                <div class="font-semibold text-xs text-ink"><?= htmlspecialchars($st['nama_wilayah'] ?? '—') ?></div>
                                <div class="text-[11px] text-ink-mute">Sales: <?= htmlspecialchars($st['nama_sales'] ?? '—') ?></div>
                            </td>
                            <td class="cell-center font-mono font-bold">
                                <?= (int)$st['total_orders'] ?>x Order
                            </td>
                            <td class="cell-right font-mono font-bold text-ink">
                                <?= number_format((float)$st['total_pcs_terjual']) ?>
                            </td>
                            <td class="cell-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                <?= Format::rupiah($omzet) ?>
                            </td>
                            <td class="cell-right font-mono font-bold text-ink">
                                <?= Format::rupiah($bayar) ?>
                            </td>
                            <td class="cell-right font-mono font-bold <?= (float)$st['sisa_tagihan_periode'] > 0 ? 'text-danger' : 'text-ink-mute' ?>">
                                <?= Format::rupiah((float)$st['sisa_tagihan_periode']) ?>
                            </td>
                            <td class="cell-center">
                                <span class="badge <?= $rasio >= 90 ? 'badge-success' : ($rasio >= 50 ? 'badge-warning' : 'badge-danger') ?> font-mono font-bold text-[10.5px]">
                                    <?= $rasio ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();

$totalStoresCount = count($stores);
$totalPcsAll = array_sum(array_column($stores, 'total_pcs_titip'));
$totalSkuAll = array_sum(array_column($stores, 'total_sku_titip'));
$overdueStoresCount = 0;
foreach ($stores as $st) {
    if (empty($st['terakhir_opname'])) {
        $overdueStoresCount++;
    } else {
        $diff = (new DateTime())->diff(new DateTime($st['terakhir_opname']));
        if (abs((int)$diff->format('%r%a')) > 14) {
            $overdueStoresCount++;
        }
    }
}
?>

<div x-data="stokRakApp()" class="space-y-4 sm:space-y-6 pb-24">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-header-body">
            <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary btn-sm p-2 rounded-xl" title="Kembali ke Portal">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#0284c7;"></span>
                    <span>Monitoring Rak • <?= $isAdminOrOwner ? 'Semua Mitra' : 'Toko Binaan' ?></span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl">Stok Rak per Toko</h1>
                <p class="page-subtitle text-xs sm:text-sm">Rincian saldo barang titipan konsinyasi yang aktif di seluruh rak toko mitra.</p>
            </div>
        </div>
    </div>

    <!-- STATS SUMMARY CARDS (MINIMALIST) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="card p-3.5 sm:p-4 rounded-2xl" style="border:1px solid var(--color-hairline);">
            <span class="text-[11px] sm:text-xs font-bold block" style="color:var(--color-ink-mute);">Total Toko Mitra</span>
            <div class="text-lg sm:text-2xl font-black mt-1" style="color:var(--color-ink);"><?= $totalStoresCount ?> Toko</div>
            <span class="text-[10px] sm:text-[11px] mt-0.5 block" style="color:var(--color-ink-mute);">Aktif konsinyasi</span>
        </div>

        <div class="card p-3.5 sm:p-4 rounded-2xl" style="border:1px solid var(--color-hairline);">
            <span class="text-[11px] sm:text-xs font-bold block" style="color:var(--color-ink-mute);">Total Saldo Rak</span>
            <div class="text-lg sm:text-2xl font-black text-sky-600 dark:text-sky-400 mt-1"><?= number_format((float)$totalPcsAll) ?> pcs</div>
            <span class="text-[10px] sm:text-[11px] mt-0.5 block" style="color:var(--color-ink-mute);">Barang di seluruh toko</span>
        </div>

        <div class="card p-3.5 sm:p-4 rounded-2xl" style="border:1px solid var(--color-hairline);">
            <span class="text-[11px] sm:text-xs font-bold block" style="color:var(--color-ink-mute);">Total Varian</span>
            <div class="text-lg sm:text-2xl font-black text-emerald-500 mt-1"><?= $totalSkuAll ?> SKU</div>
            <span class="text-[10px] sm:text-[11px] mt-0.5 block" style="color:var(--color-ink-mute);">Varian barang aktif</span>
        </div>

        <div class="card p-3.5 sm:p-4 rounded-2xl" style="border:1px solid var(--color-hairline);">
            <span class="text-[11px] sm:text-xs font-bold block" style="color:var(--color-ink-mute);">Perlu Opname</span>
            <div class="text-lg sm:text-2xl font-black mt-1 <?= $overdueStoresCount > 0 ? 'text-rose-500' : 'text-emerald-500' ?>">
                <?= $overdueStoresCount ?> Toko
            </div>
            <span class="text-[10px] sm:text-[11px] mt-0.5 block" style="color:var(--color-ink-mute);">
                <?= $overdueStoresCount > 0 ? '&gt;14 hari belum opname' : 'Semua terkontrol baik' ?>
            </span>
        </div>
    </div>

    <!-- SEARCH & FILTER BAR -->
    <div class="card p-3 rounded-2xl flex items-center gap-3" style="border:1px solid var(--color-hairline);">
        <div class="relative flex-1 w-full">
            <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color:var(--color-ink-mute);"></i>
            <input type="text" 
                   x-model="searchQuery" 
                   placeholder="Cari nama toko, kode pelanggan, atau nama sales..." 
                   class="form-input w-full text-xs sm:text-sm"
                   style="height:40px;padding-left:38px;padding-right:38px;border-radius:12px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-ink);">
            <button type="button" 
                    x-show="searchQuery" 
                    @click="searchQuery = ''" 
                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded-lg hover:text-slate-600 dark:hover:text-slate-200 transition-colors" 
                    style="color:var(--color-ink-mute);"
                    title="Reset Pencarian">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <?php if (empty($stores)): ?>
        <div class="card p-8 sm:p-12 text-center rounded-3xl" style="border:1px solid var(--color-hairline);">
            <i data-lucide="boxes" class="w-10 h-10 mx-auto mb-2 text-slate-400" style="color:var(--color-ink-mute);"></i>
            <h3 class="text-base sm:text-lg font-bold" style="color:var(--color-ink);">Belum Ada Data Stok Rak</h3>
            <p class="text-xs sm:text-sm mt-1 max-w-md mx-auto" style="color:var(--color-ink-mute);">
                Belum ada data barang titipan di rak toko. Buat pengiriman konsinyasi pertama lewat menu Customer Orders.
            </p>
            <a href="<?= Router::url('/customer-orders/create') ?>" class="btn btn-primary mt-4 inline-flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Buat Pengiriman Baru</span>
            </a>
        </div>
    <?php else: ?>

        <!-- ===================================================================== -->
        <!-- MOBILE VIEW (< 768px): CLEAN & MINIMALIST STORE CARDS                 -->
        <!-- ===================================================================== -->
        <div class="block md:hidden space-y-3">
            <?php foreach ($stores as $store): 
                $storeId = $store['id'];
                $tipeKonsinyasi = $store['tipe_konsinyasi'] ?? 'rolling_nota';
                $daysSince = 999;
                if (!empty($store['terakhir_opname'])) {
                    $opnameDate = new DateTime($store['terakhir_opname']);
                    $diff = (new DateTime())->diff($opnameDate);
                    $daysSince = abs((int)$diff->format('%r%a'));
                }
                $storeItems = $itemsByStore[$storeId] ?? [];
                $storeUnbilled = $unbilledOrdersByStore[$storeId] ?? [];
                $unbilledCount = count($storeUnbilled);
                $storeSearchKey = addslashes(strtolower($store['nama_toko'] . ' ' . ($store['nama_sales'] ?? '') . ' ' . $store['kode_pelanggan']));
            ?>
            <div class="card p-4 rounded-2xl space-y-3 transition-all"
                 style="border:1px solid var(--color-hairline);"
                 x-show="matchStore('<?= $storeSearchKey ?>')">
                
                <!-- STORE HEADER (CLEAN & MINIMALIST) -->
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm font-bold truncate" style="color:var(--color-ink);">
                                <?= htmlspecialchars($store['nama_toko']) ?>
                            </h3>
                            <span class="text-[10px] font-mono text-slate-400 flex-shrink-0" style="color:var(--color-ink-mute);">
                                <?= htmlspecialchars($store['kode_pelanggan'] ?? '') ?>
                            </span>
                            <?php if ($tipeKonsinyasi === 'kolektif_toko' || $tipeKonsinyasi === 'kolektif_tagihan'): ?>
                                <span style="background:rgba(2,132,199,0.1);color:#0284c7;border:1px solid rgba(2,132,199,0.25);padding:1.5px 6px;border-radius:6px;font-weight:800;font-size:9px;">Kolektif Toko</span>
                            <?php else: ?>
                                <span style="background:rgba(124,58,237,0.1);color:#7c3aed;border:1px solid rgba(124,58,237,0.25);padding:1.5px 6px;border-radius:6px;font-weight:800;font-size:9px;">Rolling Nota</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-[11px] mt-0.5 truncate" style="color:var(--color-ink-mute);">
                            <?= htmlspecialchars($store['alamat_lengkap'] ?? 'Alamat belum diatur') ?>
                        </p>
                        <?php if ($unbilledCount > 0): ?>
                        <div class="mt-1.5">
                            <span style="display:inline-flex;align-items:center;gap:4px;background:rgba(245,158,11,0.12);color:#b45309;border:1px solid rgba(245,158,11,0.35);padding:2px 8px;border-radius:9999px;font-weight:700;font-size:10px;line-height:1.2;">
                                <i data-lucide="clock" style="width:11px;height:11px;stroke:#b45309;stroke-width:2.5;flex-shrink:0;"></i>
                                <span><?= $unbilledCount ?> PO Belum Ditagih</span>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- STATUS BADGE -->
                    <div class="flex-shrink-0">
                        <?php if ($daysSince === 999): ?>
                            <span style="background:rgba(244,63,94,0.1);color:#f43f5e;padding:2px 7px;border-radius:6px;font-weight:700;font-size:9.5px;">Belum Opname</span>
                        <?php elseif ($daysSince > 14): ?>
                            <span style="background:rgba(244,63,94,0.1);color:#f43f5e;padding:2px 7px;border-radius:6px;font-weight:700;font-size:9.5px;"><?= $daysSince ?> hr lalu</span>
                        <?php elseif ($daysSince > 7): ?>
                            <span style="background:rgba(245,158,11,0.1);color:#f59e0b;padding:2px 7px;border-radius:6px;font-weight:700;font-size:9.5px;"><?= $daysSince ?> hr lalu</span>
                        <?php else: ?>
                            <span style="background:rgba(16,185,129,0.1);color:#10b981;padding:2px 7px;border-radius:6px;font-weight:700;font-size:9.5px;"><?= $daysSince ?> hr lalu</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- KEY METRICS STRIP -->
                <div class="grid grid-cols-3 gap-2 p-2.5 rounded-xl text-center" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                    <div>
                        <span class="text-[10px] block" style="color:var(--color-ink-mute);">Saldo Rak:</span>
                        <strong class="text-xs font-black text-sky-600 dark:text-sky-400"><?= number_format((float)$store['total_pcs_titip']) ?> pcs</strong>
                    </div>
                    <div>
                        <span class="text-[10px] block" style="color:var(--color-ink-mute);">Varian:</span>
                        <strong class="text-xs font-bold" style="color:var(--color-ink);"><?= $store['total_sku_titip'] ?> SKU</strong>
                    </div>
                    <div>
                        <span class="text-[10px] block" style="color:var(--color-ink-mute);">Sales:</span>
                        <strong class="text-xs truncate block" style="color:var(--color-ink-secondary);"><?= htmlspecialchars($store['nama_sales'] ?? '-') ?></strong>
                    </div>
                </div>

                <!-- ACTION BUTTONS (MOBILE) -->
                <div class="space-y-2 pt-0.5">
                    <?php if ($tipeKonsinyasi === 'kolektif_toko' || $tipeKonsinyasi === 'kolektif_tagihan'): ?>
                    <a href="<?= Router::url('/consignment/opname?pelanggan_id=' . urlencode((string)$store['id'])) ?>" 
                       class="btn btn-primary btn-sm w-full flex items-center justify-center gap-1.5 text-xs py-2 px-3 rounded-xl font-bold shadow-sm"
                       style="background:#0284c7;border-color:#0284c7;color:#fff !important;"
                       title="Lakukan Opname Toko Langsung">
                        <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                        <span>Lakukan Opname Toko</span>
                    </a>
                    <?php endif; ?>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" 
                                @click="toggleStore('<?= $storeId ?>')"
                                class="btn btn-secondary btn-sm flex items-center justify-center gap-1.5 text-xs py-2 px-2 rounded-xl font-semibold min-w-0"
                                style="border:1px solid var(--color-hairline);"
                                :style="expandedStoreId === '<?= $storeId ?>' ? 'background:var(--color-canvas-soft);' : ''">
                            <span class="truncate" x-text="expandedStoreId === '<?= $storeId ?>' ? 'Tutup Rincian' : 'Rincian &amp; PO (<?= count($storeItems) ?>)'"></span>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200" :class="expandedStoreId === '<?= $storeId ?>' ? 'rotate-180' : ''"></i>
                        </button>
                        <a href="<?= Router::url('/customer-orders?pelanggan_id=' . urlencode((string)$store['id'])) ?>" 
                           class="btn btn-secondary btn-sm flex items-center justify-center gap-1.5 text-xs py-2 px-2 rounded-xl font-semibold min-w-0"
                           style="border:1px solid var(--color-hairline);color:var(--color-ink);"
                           title="Lihat Semua PO/Nota Toko Ini">
                            <i data-lucide="receipt" class="w-3.5 h-3.5 text-sky-600 flex-shrink-0"></i>
                            <span class="truncate">Semua PO/Nota</span>
                        </a>
                    </div>
                </div>

                <!-- MOBILE DRILLDOWN (SMOOTH & LIGHTWEIGHT TRANSITION) -->
                <div x-show="expandedStoreId === '<?= $storeId ?>'" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     x-cloak 
                     class="pt-3 border-t space-y-3" 
                     style="border-color:var(--color-hairline);">

                    <!-- DAFTAR PO/NOTA BELUM ADA NILAI TAGIHAN -->
                    <div class="p-3.5 rounded-2xl space-y-3" style="background:var(--color-surface);border:1px solid var(--color-hairline);">
                        <div class="flex items-center justify-between" style="padding-bottom:10px;margin-bottom:10px;border-bottom:1px solid var(--color-hairline);">
                            <div class="flex items-center gap-2.5">
                                <div style="width:30px;height:30px;border-radius:8px;background:rgba(2,132,199,0.12);color:#0284c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i data-lucide="receipt" style="width:16px;height:16px;stroke:#0284c7;stroke-width:2.2;"></i>
                                </div>
                                <span class="text-xs font-bold" style="color:var(--color-ink);">
                                    PO Belum Ada Tagihan
                                </span>
                            </div>
                            <span style="display:inline-flex;align-items:center;background:rgba(245,158,11,0.15);color:#b45309;border:1px solid rgba(245,158,11,0.35);padding:2px 8px;border-radius:9999px;font-weight:700;font-size:10px;line-height:1;">
                                <?= $unbilledCount ?> PO
                            </span>
                        </div>

                        <?php if (empty($storeUnbilled)): ?>
                            <p class="text-[11px] italic py-2 text-center" style="color:var(--color-ink-mute);">Tidak ada PO kiriman yang menunggu tagihan.</p>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($storeUnbilled as $upo): ?>
                                <div class="p-2.5 rounded-xl space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                                    <div class="flex items-center justify-between">
                                        <span class="font-mono font-bold text-xs text-sky-600 dark:text-sky-400"><?= htmlspecialchars($upo['nomor_nota']) ?></span>
                                        <span style="display:inline-flex;align-items:center;gap:3px;background:rgba(245,158,11,0.12);color:#b45309;border:1px solid rgba(245,158,11,0.3);padding:2px 7px;border-radius:9999px;font-weight:700;font-size:9.5px;line-height:1.2;">
                                            <i data-lucide="clock" style="width:10px;height:10px;stroke:#b45309;stroke-width:2.5;"></i>
                                            <span>Belum Ditagih</span>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs" style="color:var(--color-ink-mute);">
                                        <span>Tgl Kirim: <?= date('d/m/Y', strtotime($upo['tanggal_pesanan'])) ?></span>
                                        <strong class="font-bold" style="color:var(--color-ink);"><?= number_format((float)$upo['total_qty_kirim']) ?> pcs</strong>
                                    </div>
                                    <?php if (!empty($upo['rincian_barang'])): ?>
                                    <div class="text-[10.5px] text-slate-500 truncate">
                                        <?= htmlspecialchars($upo['rincian_barang']) ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="pt-1.5 border-t" style="border-color:var(--color-hairline);">
                                        <a href="<?= Router::url('/consignment/opname?pelanggan_id=' . urlencode((string)$storeId) . '&pesanan_id=' . urlencode((string)$upo['pesanan_id'])) ?>"
                                           class="btn btn-primary btn-sm w-full py-1.5 px-3 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm"
                                           style="background:#0284c7;border-color:#0284c7;color:#fff !important;">
                                            <i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i>
                                            <span>Opname Nota Ini</span>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- RINCIAN PRODUK DI RAK -->
                    <div class="p-3.5 rounded-2xl space-y-3" style="background:var(--color-surface);border:1px solid var(--color-hairline);">
                        <div class="flex items-center justify-between" style="padding-bottom:10px;margin-bottom:10px;border-bottom:1px solid var(--color-hairline);">
                            <div class="flex items-center gap-2.5">
                                <div style="width:30px;height:30px;border-radius:8px;background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i data-lucide="layers" style="width:16px;height:16px;stroke:#10b981;stroke-width:2.2;"></i>
                                </div>
                                <span class="text-xs font-bold" style="color:var(--color-ink);">
                                    Produk di Rak Toko
                                </span>
                            </div>
                            <span style="display:inline-flex;align-items:center;background:rgba(16,185,129,0.12);color:#047857;border:1px solid rgba(16,185,129,0.25);padding:2px 8px;border-radius:9999px;font-weight:700;font-size:10px;line-height:1;">
                                <?= count($storeItems) ?> SKU
                            </span>
                        </div>

                        <?php if (empty($storeItems)): ?>
                            <p class="text-xs italic py-2 text-center" style="color:var(--color-ink-mute);">Belum ada barang di rak toko ini.</p>
                        <?php else: ?>
                            <div class="space-y-1">
                                <?php foreach ($storeItems as $sItem): 
                                    $stok = (int)$sItem['stok_titip_saat_ini'];
                                    $hpp = (float)($sItem['hpp'] ?? 0);
                                ?>
                                <div class="p-2 rounded-lg flex items-center justify-between text-xs" style="background:var(--color-canvas);">
                                    <div class="min-w-0 flex-1 pr-2">
                                        <div class="font-medium truncate" style="color:var(--color-ink);"><?= htmlspecialchars($sItem['nama_item']) ?></div>
                                        <div class="text-[10px] font-mono text-slate-400" style="color:var(--color-ink-mute);"><?= htmlspecialchars($sItem['kode_sku'] ?? '-') ?></div>
                                    </div>
                                    <div class="text-right flex-shrink-0 font-bold" style="color:var(--color-ink);">
                                        <?= $stok ?> <?= $sItem['satuan_dasar'] ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

        <!-- ===================================================================== -->
        <!-- DESKTOP VIEW (>= 768px): CLEAN & MODERN DATA TABLE                    -->
        <!-- ===================================================================== -->
        <div class="hidden md:block card rounded-3xl overflow-hidden shadow-sm" style="border:1px solid var(--color-hairline);padding:0;">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="data-table" style="min-width: 960px;">
                    <thead>
                        <tr>
                            <th style="min-width: 280px;">Toko Mitra</th>
                            <th style="min-width: 140px;" class="cell-nowrap">Sales Pemegang</th>
                            <th style="width: 100px; min-width: 95px;" class="cell-center cell-nowrap">Total SKU</th>
                            <th style="width: 120px; min-width: 110px;" class="cell-center cell-nowrap">Saldo Rak</th>
                            <th style="width: 140px; min-width: 130px;" class="cell-center cell-nowrap">Terakhir Opname</th>
                            <th style="width: 180px; min-width: 170px;" class="cell-right cell-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stores as $store): 
                            $storeId = $store['id'];
                            $tipeKonsinyasi = $store['tipe_konsinyasi'] ?? 'rolling_nota';
                            $daysSince = 999;
                            if (!empty($store['terakhir_opname'])) {
                                $opnameDate = new DateTime($store['terakhir_opname']);
                                $diff = (new DateTime())->diff($opnameDate);
                                $daysSince = abs((int)$diff->format('%r%a'));
                            }
                            $storeItems = $itemsByStore[$storeId] ?? [];
                            $storeUnbilled = $unbilledOrdersByStore[$storeId] ?? [];
                            $unbilledCount = count($storeUnbilled);
                            $storeSearchKey = addslashes(strtolower($store['nama_toko'] . ' ' . ($store['nama_sales'] ?? '') . ' ' . $store['kode_pelanggan']));
                        ?>
                        <!-- MAIN STORE ROW (CLEAN & MODERN) -->
                        <tr class="cursor-pointer"
                            x-show="matchStore('<?= $storeSearchKey ?>')"
                            @click="toggleStore('<?= $storeId ?>')">
                            
                            <!-- TOKO MITRA CELL -->
                            <td>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span style="font-size: 13.5px; font-weight: 700; color: var(--color-ink);"><?= htmlspecialchars($store['nama_toko']) ?></span>
                                    <?php if (!empty($store['kode_pelanggan'])): ?>
                                        <span class="badge badge-mono" style="font-size: 10px;"><?= htmlspecialchars($store['kode_pelanggan']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($tipeKonsinyasi === 'kolektif_toko' || $tipeKonsinyasi === 'kolektif_tagihan'): ?>
                                        <span style="background:rgba(2,132,199,0.1);color:#0284c7;border:1px solid rgba(2,132,199,0.25);padding:1.5px 7px;border-radius:6px;font-weight:700;font-size:10px;">Kolektif Toko</span>
                                    <?php else: ?>
                                        <span style="background:rgba(124,58,237,0.1);color:#7c3aed;border:1px solid rgba(124,58,237,0.25);padding:1.5px 7px;border-radius:6px;font-weight:700;font-size:10px;">Rolling Nota</span>
                                    <?php endif; ?>
                                </div>
                                <div class="truncate max-w-md" style="font-size: 11.5px; color: var(--color-ink-mute); margin-top: 3px;">
                                    <?= htmlspecialchars($store['alamat_lengkap'] ?? '-') ?>
                                </div>
                                <?php if ($unbilledCount > 0): ?>
                                <div style="margin-top: 5px;">
                                    <span style="display:inline-flex;align-items:center;gap:4.5px;background:rgba(245,158,11,0.12);color:#b45309;border:1px solid rgba(245,158,11,0.35);padding:2px 8.5px;border-radius:9999px;font-weight:700;font-size:10.5px;line-height:1.2;" title="Ada <?= $unbilledCount ?> kiriman PO yang belum diopname/ditagih">
                                        <i data-lucide="clock" style="width:11px;height:11px;stroke:#b45309;stroke-width:2.5;flex-shrink:0;"></i>
                                        <span><?= $unbilledCount ?> PO Belum Ditagih</span>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </td>

                            <!-- SALES PEMEGANG CELL -->
                            <td class="cell-nowrap" style="color: var(--color-ink-secondary); font-size: 12.5px;">
                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="user" style="width: 13px; height: 13px; color: var(--color-ink-mute); flex-shrink: 0;"></i>
                                    <span><?= htmlspecialchars($store['nama_sales'] ?? 'Belum Di-assign') ?></span>
                                </div>
                            </td>

                            <!-- TOTAL SKU CELL -->
                            <td class="cell-center cell-nowrap">
                                <span class="badge" style="background:var(--color-canvas-soft);color:var(--color-ink);font-weight:700;font-size:11.5px;padding:3.5px 8.5px;border:1px solid var(--color-hairline);">
                                    <?= $store['total_sku_titip'] ?> SKU
                                </span>
                            </td>

                            <!-- SALDO RAK CELL -->
                            <td class="cell-center cell-nowrap">
                                <span style="font-size: 13.5px; font-weight: 800; color: #0284c7; white-space: nowrap;">
                                    <?= number_format((float)$store['total_pcs_titip']) ?> pcs
                                </span>
                            </td>

                            <!-- TERAKHIR OPNAME CELL -->
                            <td class="cell-center cell-nowrap">
                                <?php if (!empty($store['terakhir_opname'])): ?>
                                    <div style="font-weight: 600; font-size: 12px; color: var(--color-ink);">
                                        <?= date('d/m/Y', strtotime($store['terakhir_opname'])) ?>
                                    </div>
                                    <div style="margin-top: 3px;">
                                        <?php if ($daysSince > 14): ?>
                                            <span style="background:rgba(244,63,94,0.1);color:#f43f5e;border:1px solid rgba(244,63,94,0.25);padding:1.5px 7px;border-radius:6px;font-weight:700;font-size:9.5px;display:inline-block;"><?= $daysSince ?> hr lalu</span>
                                        <?php elseif ($daysSince > 7): ?>
                                            <span style="background:rgba(245,158,11,0.1);color:#f59e0b;border:1px solid rgba(245,158,11,0.25);padding:1.5px 7px;border-radius:6px;font-weight:700;font-size:9.5px;display:inline-block;"><?= $daysSince ?> hr lalu</span>
                                        <?php else: ?>
                                            <span style="background:rgba(16,185,129,0.1);color:#10b981;border:1px solid rgba(16,185,129,0.25);padding:1.5px 7px;border-radius:6px;font-weight:700;font-size:9.5px;display:inline-block;"><?= $daysSince ?> hr lalu</span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="background:rgba(244,63,94,0.1);color:#f43f5e;border:1px solid rgba(244,63,94,0.25);padding:2px 8px;border-radius:6px;font-weight:700;font-size:10px;display:inline-block;">Belum Opname</span>
                                <?php endif; ?>
                            </td>

                            <!-- AKSI CELL -->
                            <td class="cell-right cell-nowrap" @click.stop>
                                <div class="flex items-center justify-end gap-1.5">
                                    <?php if ($tipeKonsinyasi === 'kolektif_toko' || $tipeKonsinyasi === 'kolektif_tagihan'): ?>
                                    <a href="<?= Router::url('/consignment/opname?pelanggan_id=' . urlencode((string)$store['id'])) ?>" 
                                       class="btn btn-primary btn-sm py-1.5 px-2.5 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 shadow-sm"
                                       style="background:#0284c7;border-color:#0284c7;color:#fff !important;"
                                       title="Lakukan Opname Toko Langsung">
                                        <i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i>
                                        <span>Opname Toko</span>
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?= Router::url('/customer-orders?pelanggan_id=' . urlencode((string)$store['id'])) ?>" 
                                       class="btn btn-secondary btn-sm py-1.5 px-2.5 rounded-xl text-xs font-semibold inline-flex items-center gap-1.5"
                                       style="border:1px solid var(--color-hairline);color:var(--color-ink);"
                                       title="Lihat Daftar Lengkap Seluruh PO / Nota Toko Ini">
                                        <i data-lucide="receipt" class="w-3.5 h-3.5 text-sky-600"></i>
                                        <span>Semua PO/Nota</span>
                                    </a>
                                    <button type="button" 
                                            @click="toggleStore('<?= $storeId ?>')" 
                                            class="btn btn-secondary btn-sm p-1.5 rounded-xl transition-all" 
                                            :style="expandedStoreId === '<?= $storeId ?>' ? 'background:var(--color-canvas-soft);border-color:var(--color-hairline);' : 'border:1px solid var(--color-hairline);'"
                                            title="Buka/Tutup Rincian">
                                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="expandedStoreId === '<?= $storeId ?>' ? 'rotate-180' : ''"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- DESKTOP DRILLDOWN ITEMS (HARMONIZED & MINIMALIST) -->
                        <tr x-show="expandedStoreId === '<?= $storeId ?>'" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            x-cloak 
                            style="background:var(--color-canvas);">
                            <td colspan="6" class="p-4 sm:p-5">
                                <div class="space-y-4">

                                    <!-- SEKSI 1: DAFTAR PO/NOTA KIRIMAN BELUM ADA NILAI TAGIHAN -->
                                    <div class="p-5 rounded-2xl space-y-4" style="background:var(--color-surface);border:1px solid var(--color-hairline);">
                                        <div class="flex items-center justify-between" style="padding-bottom:14px;margin-bottom:16px;border-bottom:1px solid var(--color-hairline);">
                                            <div class="flex items-center gap-3">
                                                <div style="width:36px;height:36px;border-radius:10px;background:rgba(2,132,199,0.12);color:#0284c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                    <i data-lucide="receipt" style="width:18px;height:18px;stroke:#0284c7;stroke-width:2.2;"></i>
                                                </div>
                                                <div>
                                                    <div class="flex items-center gap-2.5">
                                                        <span class="text-sm font-bold" style="color:var(--color-ink);">PO / Nota Kiriman Belum Ada Nilai Tagihan</span>
                                                        <span style="display:inline-flex;align-items:center;background:rgba(245,158,11,0.15);color:#b45309;border:1px solid rgba(245,158,11,0.35);padding:2.5px 8.5px;border-radius:9999px;font-weight:700;font-size:10.5px;line-height:1;">
                                                            <?= $unbilledCount ?> PO
                                                        </span>
                                                    </div>
                                                    <span class="text-xs block mt-1" style="color:var(--color-ink-mute);">
                                                        Kiriman konsinyasi baru yang menunggu pelaksanaan opname untuk perhitungan nilai penjualan &amp; tagihan.
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if (empty($storeUnbilled)): ?>
                                            <div class="py-4 text-center text-xs italic flex items-center justify-center gap-2" style="color:var(--color-ink-mute);">
                                                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500"></i>
                                                <span>Tidak ada PO kiriman konsinyasi yang menunggu tagihan di toko ini.</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-xs">
                                                    <thead>
                                                        <tr class="text-[10px] uppercase font-bold" style="border-bottom:1px solid var(--color-hairline);color:var(--color-ink-mute);">
                                                            <th class="py-2.5 px-3">Nomor Nota / PO</th>
                                                            <th class="py-2.5 px-3">Tanggal Kirim</th>
                                                            <th class="py-2.5 px-3 text-center">Total Kirim</th>
                                                            <th class="py-2.5 px-3">Rincian Barang</th>
                                                            <th class="py-2.5 px-3 text-center">Status</th>
                                                            <th class="py-2.5 px-3 text-right">Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y" style="border-color:var(--color-hairline);">
                                                        <?php foreach ($storeUnbilled as $upo): ?>
                                                        <tr class="hover:bg-slate-500/5 transition-colors">
                                                            <td class="py-3 px-3 font-mono font-bold text-sky-600 dark:text-sky-400">
                                                                <?= htmlspecialchars($upo['nomor_nota']) ?>
                                                            </td>
                                                            <td class="py-3 px-3" style="color:var(--color-ink-secondary);">
                                                                <?= date('d/m/Y', strtotime($upo['tanggal_pesanan'])) ?>
                                                            </td>
                                                            <td class="py-3 px-3 text-center font-bold" style="color:var(--color-ink);">
                                                                <?= number_format((float)$upo['total_qty_kirim']) ?> pcs <span class="text-slate-400 font-normal">(<?= $upo['total_sku'] ?> SKU)</span>
                                                            </td>
                                                            <td class="py-3 px-3 text-[11px] max-w-xs truncate" style="color:var(--color-ink-mute);" title="<?= htmlspecialchars($upo['rincian_barang'] ?? '') ?>">
                                                                <?= htmlspecialchars($upo['rincian_barang'] ?? '-') ?>
                                                            </td>
                                                            <td class="py-3 px-3 text-center">
                                                                <span style="display:inline-flex;align-items:center;gap:4px;background:rgba(245,158,11,0.12);color:#b45309;border:1px solid rgba(245,158,11,0.35);padding:2.5px 8.5px;border-radius:9999px;font-weight:700;font-size:10.5px;line-height:1.2;">
                                                                    <i data-lucide="clock" style="width:11px;height:11px;stroke:#b45309;stroke-width:2.5;"></i>
                                                                    <span>Belum Ditagih</span>
                                                                </span>
                                                            </td>
                                                            <td class="py-3 px-3 text-right">
                                                                <a href="<?= Router::url('/consignment/opname?pelanggan_id=' . urlencode((string)$storeId) . '&pesanan_id=' . urlencode((string)$upo['pesanan_id'])) ?>"
                                                                   class="btn btn-primary btn-sm py-1.5 px-3 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 shadow-sm"
                                                                   style="background:#0284c7;border-color:#0284c7;color:#fff !important;"
                                                                   title="Lakukan Opname & Hitung Tagihan untuk Nota Ini">
                                                                    <i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i>
                                                                    <span>Opname Nota Ini</span>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- SEKSI 2: RINCIAN PRODUK DI RAK -->
                                    <div class="p-5 rounded-2xl space-y-4" style="background:var(--color-surface);border:1px solid var(--color-hairline);">
                                        <div class="flex items-center justify-between" style="padding-bottom:14px;margin-bottom:16px;border-bottom:1px solid var(--color-hairline);">
                                            <div class="flex items-center gap-3">
                                                <div style="width:36px;height:36px;border-radius:10px;background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                    <i data-lucide="layers" style="width:18px;height:18px;stroke:#10b981;stroke-width:2.2;"></i>
                                                </div>
                                                <div>
                                                    <span class="text-sm font-bold block" style="color:var(--color-ink);">
                                                        Rincian Saldo Produk di Rak: <strong style="color:var(--color-ink);"><?= htmlspecialchars($store['nama_toko']) ?></strong>
                                                    </span>
                                                    <span class="text-xs block mt-1" style="color:var(--color-ink-mute);">
                                                        Daftar saldo stok fisik riil yang tercatat di rak toko mitra saat ini.
                                                    </span>
                                                </div>
                                            </div>
                                            <span style="display:inline-flex;align-items:center;background:rgba(16,185,129,0.12);color:#047857;border:1px solid rgba(16,185,129,0.25);padding:3px 10px;border-radius:9999px;font-weight:700;font-size:11px;line-height:1;">
                                                <?= count($storeItems) ?> SKU
                                            </span>
                                        </div>

                                        <?php if (empty($storeItems)): ?>
                                            <p class="text-xs italic py-3 text-center" style="color:var(--color-ink-mute);">Belum ada barang di rak toko ini.</p>
                                        <?php else: ?>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-xs">
                                                    <thead>
                                                        <tr class="text-[10px] uppercase font-bold" style="border-bottom:1px solid var(--color-hairline);color:var(--color-ink-mute);">
                                                            <th class="py-2.5 px-3">Kode SKU</th>
                                                            <th class="py-2.5 px-3">Nama Produk</th>
                                                            <th class="py-2.5 px-3 text-center">Stok Titip Rak</th>
                                                            <?php if ($isAdminOrOwner): ?>
                                                            <th class="py-2.5 px-3 text-right">HPP Satuan</th>
                                                            <th class="py-2.5 px-3 text-right">Valuasi Aset Rak</th>
                                                            <?php endif; ?>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y" style="border-color:var(--color-hairline);">
                                                        <?php foreach ($storeItems as $sItem): 
                                                            $stok = (int)$sItem['stok_titip_saat_ini'];
                                                            $hpp = (float)($sItem['hpp'] ?? 0);
                                                        ?>
                                                        <tr class="hover:bg-slate-500/5 transition-colors">
                                                            <td class="py-2.5 px-3 font-mono text-[11px]" style="color:var(--color-ink-mute);"><?= htmlspecialchars($sItem['kode_sku'] ?? '-') ?></td>
                                                            <td class="py-2.5 px-3 font-medium" style="color:var(--color-ink);"><?= htmlspecialchars($sItem['nama_item']) ?></td>
                                                            <td class="py-2.5 px-3 text-center font-bold" style="color:var(--color-ink);">
                                                                <?= $stok ?> <?= $sItem['satuan_dasar'] ?>
                                                            </td>
                                                            <?php if ($isAdminOrOwner): ?>
                                                            <td class="py-2.5 px-3 text-right" style="color:var(--color-ink-mute);"><?= Format::rupiah($hpp) ?></td>
                                                            <td class="py-2.5 px-3 text-right font-medium" style="color:var(--color-ink);"><?= Format::rupiah($stok * $hpp) ?></td>
                                                            <?php endif; ?>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
function stokRakApp() {
    return {
        expandedStoreId: null,
        searchQuery: '',

        matchStore(storeSearchKey) {
            if (!this.searchQuery) return true;
            return storeSearchKey.includes(this.searchQuery.toLowerCase().trim());
        },

        toggleStore(storeId) {
            this.expandedStoreId = (this.expandedStoreId === storeId ? null : storeId);
            this.$nextTick(() => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

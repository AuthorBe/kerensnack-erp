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
            <button type="button" 
                    onclick="try{window.close();}catch(e){} if(window.history.length > 1 && document.referrer.includes(window.location.host)) { window.history.back(); } else { window.location.href = '<?= Router::url('/consignment') ?>'; }" 
                    class="btn btn-secondary btn-sm p-2 rounded-xl" 
                    title="Kembali ke Portal">
                <i data-lucide="arrow-left" class="w-5 h-5" style="pointer-events:none;"></i>
            </button>
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
            <i data-lucide="search" class="absolute pointer-events-none" style="left:14px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:var(--color-ink-mute);"></i>
            <input type="text" 
                   x-model.debounce.300ms="searchQuery" 
                   placeholder="Cari nama toko, kode pelanggan, atau nama sales..." 
                   class="form-input w-full text-xs sm:text-sm"
                   style="height:44px;padding-left:42px;padding-right:42px;border-radius:12px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-ink);outline:none;box-shadow:none;">
            <button type="button" 
                    x-show="searchQuery.length > 0" 
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-90"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-90"
                    @click="searchQuery = ''" 
                    class="absolute p-2 rounded-lg hover:bg-slate-500/10 transition-colors" 
                    style="right:8px; top:50%; transform:translateY(-50%); color:var(--color-ink-mute); display:flex; align-items:center; justify-content:center; background:transparent; border:none; outline:none; box-shadow:none; cursor:pointer;"
                    title="Reset Pencarian">
                <i data-lucide="x" style="width:16px;height:16px;stroke-width:2.5;"></i>
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
            <template x-if="isStoreVisible('<?= $storeId ?>')">
                <div class="card p-4 rounded-2xl space-y-3 transition-all"
                     style="border:1px solid var(--color-hairline);">
                    
                    <!-- STORE HEADER (NEAT & COMPACT) -->
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center flex-shrink-0" style="width:22px;height:22px;border-radius:6px;border:1px solid var(--color-hairline);background:var(--color-canvas-soft);color:var(--color-ink-secondary);font-size:10.5px;font-weight:800;line-height:1;box-shadow:0 1px 2px rgba(0,0,0,0.02);" x-text="visibleIndexMap['<?= $storeId ?>']"></span>
                            <h3 class="text-sm font-bold m-0 p-0" style="color:var(--color-ink); line-height:1.4;">
                                <?= htmlspecialchars($store['nama_toko']) ?>
                            </h3>
                        </div>
                        
                        <?php 
                        $alamat = trim($store['alamat_lengkap'] ?? ''); 
                        if ($alamat !== '' && $alamat !== '-'): 
                        ?>
                        <p class="text-[11px] mt-1 line-clamp-1" style="color:var(--color-ink-mute);">
                            <?= htmlspecialchars($alamat) ?>
                        </p>
                        <?php endif; ?>

                        <!-- TAGS & BADGES -->
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <?php if (!empty($store['kode_pelanggan'])): ?>
                            <span class="font-mono" style="background:var(--color-canvas-soft);color:var(--color-ink-mute);padding:1.5px 6px;border-radius:6px;font-weight:700;font-size:9.5px;border:1px solid var(--color-hairline);">
                                <?= htmlspecialchars($store['kode_pelanggan']) ?>
                            </span>
                            <?php endif; ?>

                            <?php if ($tipeKonsinyasi === 'kolektif_toko' || $tipeKonsinyasi === 'kolektif_tagihan'): ?>
                                <span style="background:rgba(2,132,199,0.1);color:#0284c7;border:1px solid rgba(2,132,199,0.25);padding:1.5px 6px;border-radius:6px;font-weight:700;font-size:9.5px;">Kolektif</span>
                            <?php else: ?>
                                <span style="background:rgba(124,58,237,0.1);color:#7c3aed;border:1px solid rgba(124,58,237,0.25);padding:1.5px 6px;border-radius:6px;font-weight:700;font-size:9.5px;">Rolling</span>
                            <?php endif; ?>

                            <?php if ($daysSince === 999): ?>
                                <span style="background:rgba(244,63,94,0.1);color:#f43f5e;padding:1.5px 6px;border-radius:6px;font-weight:700;font-size:9.5px;">Belum Opname</span>
                            <?php elseif ($daysSince > 14): ?>
                                <span style="background:rgba(244,63,94,0.1);color:#f43f5e;padding:1.5px 6px;border-radius:6px;font-weight:700;font-size:9.5px;">Opname <?= $daysSince ?>hr lalu</span>
                            <?php elseif ($daysSince > 7): ?>
                                <span style="background:rgba(245,158,11,0.1);color:#f59e0b;padding:1.5px 6px;border-radius:6px;font-weight:700;font-size:9.5px;">Opname <?= $daysSince ?>hr lalu</span>
                            <?php else: ?>
                                <span style="background:rgba(16,185,129,0.1);color:#10b981;padding:1.5px 6px;border-radius:6px;font-weight:700;font-size:9.5px;">Opname <?= $daysSince ?>hr lalu</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($unbilledCount > 0): ?>
                        <div class="mt-2.5">
                            <span style="display:inline-flex;align-items:center;gap:4px;background:rgba(245,158,11,0.12);color:#b45309;border:1px solid rgba(245,158,11,0.35);padding:2px 8px;border-radius:9999px;font-weight:700;font-size:10px;line-height:1.2;">
                                <i data-lucide="clock" style="width:11px;height:11px;stroke:#b45309;stroke-width:2.5;flex-shrink:0;"></i>
                                <span><?= $unbilledCount ?> PO Belum Ditagih</span>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- KEY METRICS STRIP -->
                    <div class="grid grid-cols-3 gap-2 p-2.5 rounded-xl text-center" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                        <div>
                            <span class="text-[10px] block" style="color:var(--color-ink-mute);">Saldo Rak:</span>
                            <strong class="text-[11.5px] font-black text-sky-600 dark:text-sky-400"><?= number_format((float)$store['total_pcs_titip']) ?> pcs</strong>
                        </div>
                        <div>
                            <span class="text-[10px] block" style="color:var(--color-ink-mute);">Varian:</span>
                            <strong class="text-[11.5px] font-bold" style="color:var(--color-ink);"><?= $store['total_sku_titip'] ?> SKU</strong>
                        </div>
                        <div>
                            <span class="text-[10px] block" style="color:var(--color-ink-mute);">Sales:</span>
                            <strong class="text-[11.5px] truncate block" style="color:var(--color-ink-secondary);"><?= htmlspecialchars($store['nama_sales'] ?? '-') ?></strong>
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
                        
                        <div class="flex items-center gap-2">
                            <button type="button" 
                                    @click="toggleStore('<?= $storeId ?>')"
                                    class="btn btn-secondary flex-1 flex items-center justify-center gap-1.5 text-[11px] py-2 px-2 rounded-xl font-bold transition-all"
                                    style="border:1px solid var(--color-hairline);"
                                    :style="expandedStoreId === '<?= $storeId ?>' ? 'background:var(--color-canvas-soft);' : ''">
                                <span class="truncate" x-text="expandedStoreId === '<?= $storeId ?>' ? 'Tutup Rincian' : 'Rincian (<?= count($storeItems) ?>)'"></span>
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200" :class="expandedStoreId === '<?= $storeId ?>' ? 'rotate-180' : ''"></i>
                            </button>
                            <a href="<?= Router::url('/customer-orders?pelanggan_id=' . urlencode((string)$store['id'])) ?>" 
                               class="btn btn-secondary flex-1 flex items-center justify-center gap-1.5 text-[11px] py-2 px-2 rounded-xl font-bold"
                               style="border:1px solid var(--color-hairline);color:var(--color-ink);"
                               title="Lihat Semua PO/Nota Toko Ini">
                                <i data-lucide="receipt" class="w-3 h-3 text-sky-600 flex-shrink-0"></i>
                                <span class="truncate">Daftar PO/Nota</span>
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
            </template>
            <?php endforeach; ?>

            <!-- Skeleton loader mobile -->
            <div class="space-y-3 mt-3" x-show="isLoadingMore" x-cloak>
                <div class="card p-4 rounded-2xl animate-pulse" style="border:1px solid var(--color-hairline);">
                    <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/2 mb-2"></div>
                    <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-1/3 mb-4"></div>
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div class="h-10 bg-slate-100 dark:bg-slate-800 rounded-xl"></div>
                        <div class="h-10 bg-slate-100 dark:bg-slate-800 rounded-xl"></div>
                        <div class="h-10 bg-slate-100 dark:bg-slate-800 rounded-xl"></div>
                    </div>
                    <div class="h-8 bg-slate-200 dark:bg-slate-700 rounded-xl w-full"></div>
                </div>
            </div>

            <!-- Next button mobile -->
            <div x-show="hasMore" class="pt-2 pb-4" x-cloak>
                <button type="button" @click="loadMore()" :disabled="isLoadingMore" :class="isLoadingMore ? 'opacity-75 cursor-wait' : ''" class="btn btn-secondary w-full py-3 rounded-xl font-bold flex items-center justify-center gap-2 transition-all" style="border:1px solid var(--color-hairline);">
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-sky-600" x-show="isLoadingMore"></i>
                    <i data-lucide="chevron-down" class="w-4 h-4" x-show="!isLoadingMore"></i>
                    <span x-text="isLoadingMore ? 'Sedang Memuat...' : 'Tampilkan Lebih Banyak'"></span>
                </button>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- DESKTOP VIEW (>= 768px): CLEAN & MODERN DATA TABLE                    -->
        <!-- ===================================================================== -->
        <div class="hidden md:block card rounded-3xl overflow-hidden shadow-sm" style="border:1px solid var(--color-hairline);padding:0;">
            <div class="overflow-x-auto custom-scrollbar cursor-grab"
                 x-data="{ isDown: false, startX: 0, scrollLeft: 0 }"
                 @mousedown="isDown = true; $el.classList.add('cursor-grabbing'); $el.classList.remove('cursor-grab'); startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft;"
                 @mouseleave="isDown = false; $el.classList.remove('cursor-grabbing'); $el.classList.add('cursor-grab');"
                 @mouseup="isDown = false; $el.classList.remove('cursor-grabbing'); $el.classList.add('cursor-grab');"
                 @mousemove="if(!isDown) return; $event.preventDefault(); const x = $event.pageX - $el.offsetLeft; const walk = (x - startX) * 1.5; $el.scrollLeft = scrollLeft - walk;">
                <table class="data-table" style="min-width: 960px;">
                    <thead>
                        <tr>
                            <th style="width: 50px; min-width: 50px;" class="cell-center">No.</th>
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
                        <template x-if="isStoreVisible('<?= $storeId ?>')">
                            <tr class="cursor-pointer"
                                @click="toggleStore('<?= $storeId ?>')">
                            
                            <!-- NOMOR CELL -->
                            <td class="cell-center">
                                <span class="inline-flex items-center justify-center" style="width:24px;height:24px;border-radius:6px;border:1px solid var(--color-hairline);background:var(--color-canvas-soft);color:var(--color-ink-secondary);font-size:11px;font-weight:800;line-height:1;box-shadow:0 1px 2px rgba(0,0,0,0.02);" x-text="visibleIndexMap['<?= $storeId ?>']"></span>
                            </td>

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
                        </template>

                        <!-- DESKTOP DRILLDOWN ITEMS (HARMONIZED & MINIMALIST) -->
                        <template x-if="isStoreVisible('<?= $storeId ?>')">
                        <tr x-show="expandedStoreId === '<?= $storeId ?>'" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            x-cloak 
                            style="background:var(--color-canvas);">
                            <td colspan="7" class="p-4 sm:p-5">
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
                        </template>
                        <?php endforeach; ?>

                        <!-- Skeleton Loader Desktop -->
                        <tr x-show="isLoadingMore" class="animate-pulse" style="background:var(--color-canvas-soft);" x-cloak>
                            <td class="py-4 px-4"><div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-6 mx-auto"></div></td>
                            <td class="py-4 px-4"><div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-3/4"></div></td>
                            <td class="py-4 px-4"><div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/2"></div></td>
                            <td class="py-4 px-4"><div class="h-6 bg-slate-200 dark:bg-slate-700 rounded-full w-16 mx-auto"></div></td>
                            <td class="py-4 px-4"><div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-20 mx-auto"></div></td>
                            <td class="py-4 px-4"><div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-24 mx-auto"></div></td>
                            <td class="py-4 px-4"><div class="h-8 bg-slate-200 dark:bg-slate-700 rounded-xl w-24 ml-auto"></div></td>
                        </tr>
                        <!-- Second Skeleton Row to make it more visible -->
                        <tr x-show="isLoadingMore" class="animate-pulse" style="background:var(--color-canvas-soft);" x-cloak>
                            <td class="py-4 px-4"><div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-6 mx-auto"></div></td>
                            <td class="py-4 px-4"><div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-2/3"></div></td>
                            <td class="py-4 px-4"><div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/3"></div></td>
                            <td class="py-4 px-4"><div class="h-6 bg-slate-200 dark:bg-slate-700 rounded-full w-16 mx-auto"></div></td>
                            <td class="py-4 px-4"><div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-16 mx-auto"></div></td>
                            <td class="py-4 px-4"><div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-20 mx-auto"></div></td>
                            <td class="py-4 px-4"><div class="h-8 bg-slate-200 dark:bg-slate-700 rounded-xl w-24 ml-auto"></div></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Next Button Desktop -->
            <div x-show="hasMore" class="p-4 border-t flex justify-center" style="border-color:var(--color-hairline); background:var(--color-canvas-soft);" x-cloak>
                <button type="button" @click="loadMore()" :disabled="isLoadingMore" :class="isLoadingMore ? 'opacity-75 cursor-wait' : ''" class="btn btn-secondary py-2.5 px-6 rounded-xl font-bold flex items-center gap-2 transition-all" style="border:1px solid var(--color-hairline);">
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-sky-600" x-show="isLoadingMore"></i>
                    <i data-lucide="chevron-down" class="w-4 h-4" x-show="!isLoadingMore"></i>
                    <span x-text="isLoadingMore ? 'Sedang Memuat...' : 'Tampilkan Lebih Banyak'"></span>
                </button>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
function stokRakApp() {
    return {
        expandedStoreId: null,
        searchQuery: '',
        limit: 25,
        isLoadingMore: false,
        
        allStoreIds: <?= json_encode(array_column($stores, 'id')) ?>,
        
        storeKeys: {
            <?php foreach($stores as $s): ?>
            '<?= $s['id'] ?>': <?= json_encode(strtolower($s['nama_toko'] . ' ' . ($s['nama_sales'] ?? '') . ' ' . $s['kode_pelanggan'])) ?>,
            <?php endforeach; ?>
        },
        
        visibleMap: {},
        visibleIndexMap: {},
        hasMore: false,
        
        updateVisibleMap() {
            const map = {};
            const indexMap = {};
            const q = this.searchQuery.toLowerCase().trim();
            const filtered = q ? this.allStoreIds.filter(id => this.storeKeys[id].includes(q)) : this.allStoreIds;
            const arr = filtered.slice(0, this.limit);
            for (let i = 0; i < arr.length; i++) {
                map[arr[i]] = true;
                indexMap[arr[i]] = i + 1;
            }
            this.visibleMap = map;
            this.visibleIndexMap = indexMap;
            this.hasMore = this.limit < filtered.length;
        },
        
        isStoreVisible(storeId) {
            return !!this.visibleMap[storeId];
        },
        
        loadMore() {
            if (this.isLoadingMore || !this.hasMore) return;
            this.isLoadingMore = true;
            setTimeout(() => {
                this.limit += 25;
                this.updateVisibleMap();
                this.isLoadingMore = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }, 600);
        },

        toggleStore(storeId) {
            this.expandedStoreId = (this.expandedStoreId === storeId ? null : storeId);
            this.$nextTick(() => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        },

        init() {
            this.updateVisibleMap();
            this.$watch('searchQuery', () => {
                this.limit = 50;
                this.expandedStoreId = null;
                this.updateVisibleMap();
            });
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

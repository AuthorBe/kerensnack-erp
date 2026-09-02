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
    <div class="relative flex items-center w-full">
        <i data-lucide="search" class="absolute left-3.5 w-4 h-4 text-slate-400 pointer-events-none" style="color:var(--color-ink-mute);"></i>
        <input type="text" 
               x-model="searchQuery" 
               placeholder="Cari nama toko, kode pelanggan, atau nama sales..." 
               class="form-input w-full text-xs sm:text-sm"
               style="height:42px;padding-left:38px;padding-right:38px;border-radius:14px;background:var(--color-surface);border:1px solid var(--color-hairline);color:var(--color-ink);">
        <button type="button" 
                x-show="searchQuery" 
                @click="searchQuery = ''" 
                class="absolute right-3 p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors" 
                style="color:var(--color-ink-mute);"
                title="Reset Pencarian">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
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
                $daysSince = 999;
                if (!empty($store['terakhir_opname'])) {
                    $opnameDate = new DateTime($store['terakhir_opname']);
                    $diff = (new DateTime())->diff($opnameDate);
                    $daysSince = abs((int)$diff->format('%r%a'));
                }
                $storeItems = $itemsByStore[$storeId] ?? [];
                $storeSearchKey = addslashes(strtolower($store['nama_toko'] . ' ' . ($store['nama_sales'] ?? '') . ' ' . $store['kode_pelanggan']));
            ?>
            <div class="card p-4 rounded-2xl space-y-3 transition-all"
                 style="border:1px solid var(--color-hairline);"
                 x-show="matchStore('<?= $storeSearchKey ?>')">
                
                <!-- STORE HEADER (CLEAN & MINIMALIST) -->
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold truncate" style="color:var(--color-ink);">
                                <?= htmlspecialchars($store['nama_toko']) ?>
                            </h3>
                            <span class="text-[10px] font-mono text-slate-400 flex-shrink-0" style="color:var(--color-ink-mute);">
                                <?= htmlspecialchars($store['kode_pelanggan'] ?? '') ?>
                            </span>
                        </div>
                        <p class="text-[11px] mt-0.5 truncate" style="color:var(--color-ink-mute);">
                            <?= htmlspecialchars($store['alamat_lengkap'] ?? 'Alamat belum diatur') ?>
                        </p>
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

                <!-- ACTION BUTTONS -->
                <div class="flex items-center gap-2 pt-0.5">
                    <button type="button" 
                            @click="toggleStore('<?= $storeId ?>')"
                            class="btn btn-secondary btn-sm flex-1 flex items-center justify-center gap-1.5 text-xs py-2 rounded-xl font-semibold">
                        <span x-text="expandedStoreId === '<?= $storeId ?>' ? 'Tutup Rincian' : 'Rincian Item (<?= count($storeItems) ?>)'"></span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200" :class="expandedStoreId === '<?= $storeId ?>' ? 'rotate-180' : ''"></i>
                    </button>
                    <a href="<?= Router::url('/consignment/opname?pelanggan_id=' . urlencode((string)$store['id'])) ?>" 
                       class="btn btn-primary btn-sm flex items-center justify-center gap-1 text-xs py-2 px-3.5 rounded-xl font-bold"
                       style="background:#0284c7;border-color:#0284c7;color:#fff;">
                        <i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i>
                        <span>Opname</span>
                    </a>
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
                     class="pt-2.5 border-t space-y-1.5" 
                     style="border-color:var(--color-hairline);">
                    <div class="text-[10px] font-bold uppercase tracking-wider" style="color:var(--color-ink-mute);">
                        Rincian Produk di Rak (<?= count($storeItems) ?>):
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
            <?php endforeach; ?>
        </div>

        <!-- ===================================================================== -->
        <!-- DESKTOP VIEW (>= 768px): CLEAN & MINIMALIST DATA TABLE                -->
        <!-- ===================================================================== -->
        <div class="hidden md:block card rounded-3xl overflow-hidden shadow-sm" style="border:1px solid var(--color-hairline);">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="font-bold uppercase tracking-wider text-[11px]" style="background:var(--color-canvas);border-bottom:1px solid var(--color-hairline);color:var(--color-ink-mute);">
                            <th class="py-3.5 px-4">Toko Mitra</th>
                            <th class="py-3.5 px-4">Sales Pemegang</th>
                            <th class="py-3.5 px-4 text-center">Total SKU</th>
                            <th class="py-3.5 px-4 text-center">Saldo Rak</th>
                            <th class="py-3.5 px-4 text-center">Terakhir Opname</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color:var(--color-hairline);">
                        <?php foreach ($stores as $store): 
                            $storeId = $store['id'];
                            $daysSince = 999;
                            if (!empty($store['terakhir_opname'])) {
                                $opnameDate = new DateTime($store['terakhir_opname']);
                                $diff = (new DateTime())->diff($opnameDate);
                                $daysSince = abs((int)$diff->format('%r%a'));
                            }
                            $storeItems = $itemsByStore[$storeId] ?? [];
                            $storeSearchKey = addslashes(strtolower($store['nama_toko'] . ' ' . ($store['nama_sales'] ?? '') . ' ' . $store['kode_pelanggan']));
                        ?>
                        <!-- MAIN STORE ROW (CLEAN & MINIMALIST) -->
                        <tr class="hover:bg-slate-500/5 transition-colors cursor-pointer"
                            x-show="matchStore('<?= $storeSearchKey ?>')"
                            @click="toggleStore('<?= $storeId ?>')">
                            
                            <!-- CLEAN TOKO MITRA CELL (NO CLUTTER ICONS) -->
                            <td class="py-3.5 px-4 font-bold" style="color:var(--color-ink);">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold" style="color:var(--color-ink);"><?= htmlspecialchars($store['nama_toko']) ?></span>
                                    <span class="text-[11px] font-mono font-normal text-slate-400" style="color:var(--color-ink-mute);"><?= htmlspecialchars($store['kode_pelanggan'] ?? '') ?></span>
                                </div>
                                <div class="text-[11px] font-normal mt-0.5 text-slate-500 truncate max-w-xs" style="color:var(--color-ink-mute);">
                                    <?= htmlspecialchars($store['alamat_lengkap'] ?? '-') ?>
                                </div>
                            </td>

                            <td class="py-3.5 px-4" style="color:var(--color-ink-secondary);">
                                <?= htmlspecialchars($store['nama_sales'] ?? 'Belum Di-assign') ?>
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold" style="color:var(--color-ink);">
                                <?= $store['total_sku_titip'] ?> SKU
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold text-sm text-sky-600 dark:text-sky-400">
                                <?= number_format((float)$store['total_pcs_titip']) ?> pcs
                            </td>

                            <td class="py-3.5 px-4 text-center" style="color:var(--color-ink-mute);">
                                <?= !empty($store['terakhir_opname']) ? date('d/m/Y', strtotime($store['terakhir_opname'])) : '<span style="color:var(--color-ink-mute);">Belum pernah</span>' ?>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <?php if ($daysSince === 999): ?>
                                    <span style="background:rgba(244,63,94,0.1);color:#f43f5e;padding:2px 8px;border-radius:6px;font-weight:700;font-size:10px;">Belum Opname</span>
                                <?php elseif ($daysSince > 14): ?>
                                    <span style="background:rgba(244,63,94,0.1);color:#f43f5e;padding:2px 8px;border-radius:6px;font-weight:700;font-size:10px;"><?= $daysSince ?> hr lalu</span>
                                <?php elseif ($daysSince > 7): ?>
                                    <span style="background:rgba(245,158,11,0.1);color:#f59e0b;padding:2px 8px;border-radius:6px;font-weight:700;font-size:10px;"><?= $daysSince ?> hr lalu</span>
                                <?php else: ?>
                                    <span style="background:rgba(16,185,129,0.1);color:#10b981;padding:2px 8px;border-radius:6px;font-weight:700;font-size:10px;"><?= $daysSince ?> hr lalu</span>
                                <?php endif; ?>
                            </td>

                            <td class="py-3.5 px-4 text-right" @click.stop>
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?= Router::url('/consignment/opname?pelanggan_id=' . urlencode((string)$store['id'])) ?>" class="btn btn-primary btn-sm py-1.5 px-2.5 rounded-lg text-xs font-bold" style="background:#0284c7;border-color:#0284c7;color:#fff;" title="Mulai Opname Toko Ini">
                                        <i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i>
                                        <span>Opname</span>
                                    </a>
                                    <button type="button" @click="toggleStore('<?= $storeId ?>')" class="btn btn-secondary btn-sm p-1.5 rounded-lg transition-transform duration-200" title="Buka/Tutup Rincian">
                                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="expandedStoreId === '<?= $storeId ?>' ? 'rotate-180' : ''"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- DESKTOP DRILLDOWN ITEMS (SMOOTH & MINIMALIST) -->
                        <tr x-show="expandedStoreId === '<?= $storeId ?>'" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            x-cloak 
                            style="background:var(--color-canvas);">
                            <td colspan="7" class="p-3 sm:p-4">
                                <div class="p-3 sm:p-4 rounded-2xl space-y-2.5" style="background:var(--color-surface);border:1px solid var(--color-hairline);">
                                    <div class="flex items-center justify-between text-xs font-bold" style="color:var(--color-ink-mute);">
                                        <span>Rincian Produk di Rak: <strong style="color:var(--color-ink);"><?= htmlspecialchars($store['nama_toko']) ?></strong></span>
                                        <span><?= count($storeItems) ?> SKU</span>
                                    </div>

                                    <?php if (empty($storeItems)): ?>
                                        <p class="text-xs italic py-2" style="color:var(--color-ink-mute);">Belum ada barang di rak toko ini.</p>
                                    <?php else: ?>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left text-xs">
                                                <thead>
                                                    <tr class="text-[10px] uppercase font-bold" style="border-bottom:1px solid var(--color-hairline);color:var(--color-ink-mute);">
                                                        <th class="py-2 px-3">Kode SKU</th>
                                                        <th class="py-2 px-3">Nama Produk</th>
                                                        <th class="py-2 px-3 text-center">Stok Titip Rak</th>
                                                        <?php if ($isAdminOrOwner): ?>
                                                        <th class="py-2 px-3 text-right">HPP Satuan</th>
                                                        <th class="py-2 px-3 text-right">Valuasi Aset Rak</th>
                                                        <?php endif; ?>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y" style="border-color:var(--color-hairline);">
                                                    <?php foreach ($storeItems as $sItem): 
                                                        $stok = (int)$sItem['stok_titip_saat_ini'];
                                                        $hpp = (float)($sItem['hpp'] ?? 0);
                                                    ?>
                                                    <tr class="hover:bg-slate-500/5 transition-colors">
                                                        <td class="py-2 px-3 font-mono text-[11px]" style="color:var(--color-ink-mute);"><?= htmlspecialchars($sItem['kode_sku'] ?? '-') ?></td>
                                                        <td class="py-2 px-3 font-medium" style="color:var(--color-ink);"><?= htmlspecialchars($sItem['nama_item']) ?></td>
                                                        <td class="py-2 px-3 text-center font-bold" style="color:var(--color-ink);">
                                                            <?= $stok ?> <?= $sItem['satuan_dasar'] ?>
                                                        </td>
                                                        <?php if ($isAdminOrOwner): ?>
                                                        <td class="py-2 px-3 text-right" style="color:var(--color-ink-mute);"><?= Format::rupiah($hpp) ?></td>
                                                        <td class="py-2 px-3 text-right font-medium" style="color:var(--color-ink);"><?= Format::rupiah($stok * $hpp) ?></td>
                                                        <?php endif; ?>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
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

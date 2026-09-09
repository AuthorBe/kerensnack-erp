<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div class="space-y-4 sm:space-y-6 pb-20">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-emerald">
                <i data-lucide="store"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#10b981;"></span>
                    <span>Modul Konsinyasi &bull; <?= $isSales ? 'Sales Lapangan' : ($isAdmin ? 'Admin Operasional' : 'Owner / Owner Read-Only') ?></span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl"><?= $pageTitle ?? 'Portal Konsinyasi' ?></h1>
                <p class="page-subtitle text-xs sm:text-sm"><?= $pageSubtitle ?? 'Pusat Manajemen Titip Jual Rak, Opname Lapangan &amp; Settlement Faktur Mitra' ?></p>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- QUICK BANNER INFORMASI LOGISTIK OWNER (JIKA MEMILIKI IZIN APPROVAL)        -->
    <!-- ========================================================================= -->
    <?php if (Auth::can(['owner.dashboard', 'owner.approval_delivery'])): ?>
    <div class="p-3.5 sm:p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm" style="background:rgba(2,132,199,0.04);border:1px solid var(--color-hairline);">
        <div class="flex items-center gap-3">
            <i data-lucide="shield-check" class="w-6 h-6 flex-shrink-0" style="color:#0284c7;"></i>
            <div>
                <div class="text-xs sm:text-sm font-bold" style="color:var(--color-ink);">Approval Pengiriman Konsinyasi</div>
                <div class="text-[11px] sm:text-xs" style="color:var(--color-ink-mute);">Persetujuan draft surat jalan konsinyasi baru dipusatkan di Owner Command Center.</div>
            </div>
        </div>
        <a href="<?= Router::url('/owner') ?>" class="btn btn-primary btn-sm w-full sm:w-auto flex items-center justify-center gap-1.5" style="background:#0284c7;border-color:#0284c7;">
            <span>Buka Command Center</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
    </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- GRID CARD NAVIGASI PORTAL (RESPONSIVE & CLEAN - ZERO DUPLICATION)         -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">

        <!-- CARD 1: Stok Rak & Opname Toko -->
        <?php if (Auth::can(['consignment.view_all', 'consignment.view_assigned', 'consignment.opname_all', 'consignment.opname_assigned'])): ?>
        <a href="<?= Router::url('/consignment/stok-rak') ?>" 
           class="card p-3.5 sm:p-5 group flex flex-col justify-between hover:shadow-lg transition-all"
           style="border-radius:18px;text-decoration:none;min-height:125px;border:1px solid var(--color-hairline);">
            <div class="flex items-start justify-between">
                <i data-lucide="boxes" class="w-6 sm:w-7 h-6 sm:h-7 group-hover:scale-110 transition-transform" style="color:#0284c7;"></i>
                <i data-lucide="arrow-up-right" class="w-3.5 sm:w-4 h-3.5 sm:h-4 transition-colors" style="color:var(--color-ink-mute);"></i>
            </div>
            <div class="mt-3 sm:mt-4">
                <h3 class="text-xs sm:text-sm font-black transition-colors" style="color:var(--color-ink);">Stok Rak &amp; Opname</h3>
                <p class="text-[11px] mt-0.5 line-clamp-1" style="color:var(--color-ink-mute);">Monitoring rak &amp; input fisik toko</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- CARD 2: Laporan Penjualan -->
        <?php if (Auth::can(['consignment.reports_all', 'consignment.reports_assigned'])): ?>
        <a href="<?= Router::url('/consignment/laporan-penjualan') ?>" 
           class="card p-3.5 sm:p-5 group flex flex-col justify-between hover:shadow-lg transition-all"
           style="border-radius:18px;text-decoration:none;min-height:125px;border:1px solid var(--color-hairline);">
            <div class="flex items-start justify-between">
                <i data-lucide="bar-chart-3" class="w-6 sm:w-7 h-6 sm:h-7 group-hover:scale-110 transition-transform" style="color:#10b981;"></i>
                <i data-lucide="arrow-up-right" class="w-3.5 sm:w-4 h-3.5 sm:h-4 transition-colors" style="color:var(--color-ink-mute);"></i>
            </div>
            <div class="mt-3 sm:mt-4">
                <h3 class="text-xs sm:text-sm font-black transition-colors" style="color:var(--color-ink);">Laporan Penjualan</h3>
                <p class="text-[11px] mt-0.5 line-clamp-1" style="color:var(--color-ink-mute);">Grafik performa & KPI toko</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- CARD 3: Tagihan Konsinyasi -->
        <?php if (Auth::can('consignment.piutang')): ?>
        <a href="<?= Router::url('/consignment/tagihan') ?>" 
           class="card p-3.5 sm:p-5 group flex flex-col justify-between hover:shadow-lg transition-all"
           style="border-radius:18px;text-decoration:none;min-height:125px;border:1px solid var(--color-hairline);">
            <div class="flex items-start justify-between">
                <i data-lucide="file-text" class="w-6 sm:w-7 h-6 sm:h-7 group-hover:scale-110 transition-transform" style="color:#f43f5e;"></i>
                <i data-lucide="arrow-up-right" class="w-3.5 sm:w-4 h-3.5 sm:h-4 transition-colors" style="color:var(--color-ink-mute);"></i>
            </div>
            <div class="mt-3 sm:mt-4">
                <h3 class="text-xs sm:text-sm font-black transition-colors" style="color:var(--color-ink);">Tagihan Konsinyasi</h3>
                <p class="text-[11px] mt-0.5 line-clamp-1" style="color:var(--color-ink-mute);">Buat tagihan & catat pembayaran</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- CARD 4: Assignment Sales ↔ Toko -->
        <?php if (Auth::can('consignment.assignment')): ?>
        <a href="<?= Router::url('/consignment/assignment-sales') ?>" 
           class="card p-3.5 sm:p-5 group flex flex-col justify-between hover:shadow-lg transition-all"
           style="border-radius:18px;text-decoration:none;min-height:125px;border:1px solid var(--color-hairline);">
            <div class="flex items-start justify-between">
                <i data-lucide="user-check" class="w-6 sm:w-7 h-6 sm:h-7 group-hover:scale-110 transition-transform" style="color:#8b5cf6;"></i>
                <i data-lucide="arrow-up-right" class="w-3.5 sm:w-4 h-3.5 sm:h-4 transition-colors" style="color:var(--color-ink-mute);"></i>
            </div>
            <div class="mt-3 sm:mt-4">
                <h3 class="text-xs sm:text-sm font-black transition-colors" style="color:var(--color-ink);">Assignment Sales</h3>
                <p class="text-[11px] mt-0.5 line-clamp-1" style="color:var(--color-ink-mute);">Penugasan sales tetap</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- CARD 5: Rekap Komisi Sales -->
        <?php if (Auth::can(['consignment.komisi_all', 'consignment.komisi_self'])): ?>
        <a href="<?= Router::url('/consignment/komisi-sales') ?>" 
           class="card p-3.5 sm:p-5 group flex flex-col justify-between hover:shadow-lg transition-all"
           style="border-radius:18px;text-decoration:none;min-height:125px;border:1px solid var(--color-hairline);">
            <div class="flex items-start justify-between">
                <i data-lucide="percent" class="w-6 sm:w-7 h-6 sm:h-7 group-hover:scale-110 transition-transform" style="color:#f59e0b;"></i>
                <i data-lucide="arrow-up-right" class="w-3.5 sm:w-4 h-3.5 sm:h-4 transition-colors" style="color:var(--color-ink-mute);"></i>
            </div>
            <div class="mt-3 sm:mt-4">
                <h3 class="text-xs sm:text-sm font-black transition-colors" style="color:var(--color-ink);">Rekap Komisi Sales</h3>
                <p class="text-[11px] mt-0.5 line-clamp-1" style="color:var(--color-ink-mute);">Insentif omzet bulanan</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- CARD 6: Kerugian Barang Rusak -->
        <?php if (Auth::can('consignment.kerugian')): ?>
        <a href="<?= Router::url('/consignment/kerugian-rusak') ?>" 
           class="card p-3.5 sm:p-5 group flex flex-col justify-between hover:shadow-lg transition-all"
           style="border-radius:18px;text-decoration:none;min-height:125px;border:1px solid var(--color-hairline);">
            <div class="flex items-start justify-between">
                <i data-lucide="alert-triangle" class="w-6 sm:w-7 h-6 sm:h-7 group-hover:scale-110 transition-transform" style="color:#ef4444;"></i>
                <i data-lucide="arrow-up-right" class="w-3.5 sm:w-4 h-3.5 sm:h-4 transition-colors" style="color:var(--color-ink-mute);"></i>
            </div>
            <div class="mt-3 sm:mt-4">
                <h3 class="text-xs sm:text-sm font-black transition-colors" style="color:var(--color-ink);">Kerugian Rusak / BS</h3>
                <p class="text-[11px] mt-0.5 line-clamp-1" style="color:var(--color-ink-mute);">Valuasi retur rusak HPP</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- CARD 7: Early Warning Toko -->
        <?php if (Auth::can('consignment.early_warning')): ?>
        <a href="<?= Router::url('/consignment/early-warning') ?>" 
           class="card p-3.5 sm:p-5 group flex flex-col justify-between hover:shadow-lg transition-all"
           style="border-radius:18px;text-decoration:none;min-height:125px;border:1px solid var(--color-hairline);">
            <div class="flex items-start justify-between">
                <i data-lucide="clock-alert" class="w-6 sm:w-7 h-6 sm:h-7 group-hover:scale-110 transition-transform" style="color:#ea580c;"></i>
                <i data-lucide="arrow-up-right" class="w-3.5 sm:w-4 h-3.5 sm:h-4 transition-colors" style="color:var(--color-ink-mute);"></i>
            </div>
            <div class="mt-3 sm:mt-4">
                <h3 class="text-xs sm:text-sm font-black transition-colors" style="color:var(--color-ink);">Early Warning</h3>
                <p class="text-[11px] mt-0.5 line-clamp-1" style="color:var(--color-ink-mute);">&gt;14 hari belum opname</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- CARD 8: Riwayat Kunjungan -->
        <?php if (Auth::can(['consignment.view_all', 'consignment.view_assigned'])): ?>
        <a href="<?= Router::url('/consignment/riwayat-kunjungan') ?>" 
           class="card p-3.5 sm:p-5 group flex flex-col justify-between hover:shadow-lg transition-all"
           style="border-radius:18px;text-decoration:none;min-height:125px;border:1px solid var(--color-hairline);">
            <div class="flex items-start justify-between">
                <i data-lucide="history" class="w-6 sm:w-7 h-6 sm:h-7 group-hover:scale-110 transition-transform" style="color:#6366f1;"></i>
                <i data-lucide="arrow-up-right" class="w-3.5 sm:w-4 h-3.5 sm:h-4 transition-colors" style="color:var(--color-ink-mute);"></i>
            </div>
            <div class="mt-3 sm:mt-4">
                <h3 class="text-xs sm:text-sm font-black transition-colors" style="color:var(--color-ink);">Riwayat Kunjungan</h3>
                <p class="text-[11px] mt-0.5 line-clamp-1" style="color:var(--color-ink-mute);">Log audit kunjungan sales</p>
            </div>
        </a>
        <?php endif; ?>

    </div>

</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

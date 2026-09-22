<?php
/**
 * views/dashboard/partials/owner.php
 * Dasbor Terpersonalisasi Pemilik Perusahaan & Eksekutif
 * Pola Desain Kanonikal: Sesuai /owner (KEREN SNACK ERP)
 */

use App\Core\Router;
use App\Core\Auth;
use App\Helpers\Format;

$salesMetric = $roleData['salesMetric'] ?? [];
$piutangMetric = $roleData['piutangMetric'] ?? [];
$totalKas = (float)($roleData['totalKas'] ?? 0);
?>

<div class="space-y-4 sm:space-y-5">

    <!-- 1. 4 KPI STAT CARDS (Gaya Kartu Finansial /owner) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- 1. Omzet Hari Ini -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">1. Omzet Hari Ini</span>
                <i data-lucide="trending-up" style="width:14px;height:14px;color:var(--color-success);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-success);line-height:1.2;">
                <?= Format::rupiah((float)($salesMetric['omzet_hari_ini'] ?? 0)) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate"><?= (int)($salesMetric['transaksi_hari_ini'] ?? 0) ?> Nota</span>
                <span class="badge badge-success font-mono text-[9px] px-1.5 py-0 flex-shrink-0">REAL-TIME</span>
            </div>
        </div>

        <!-- 2. Omzet Bulan Ini -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-primary);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">2. Omzet Bulan Ini</span>
                <i data-lucide="bar-chart-3" style="width:14px;height:14px;color:var(--color-primary);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-primary);line-height:1.2;">
                <?= Format::rupiah((float)($salesMetric['omzet_bulan_ini'] ?? 0)) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate"><?= date('M Y') ?></span>
                <span class="font-mono text-[9.5px] flex-shrink-0">Akumulasi</span>
            </div>
        </div>

        <!-- 3. Total Piutang Toko -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-warning);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">3. Piutang Toko</span>
                <i data-lucide="receipt" style="width:14px;height:14px;color:var(--color-warning);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-warning);line-height:1.2;">
                <?= Format::rupiah((float)($piutangMetric['total_piutang'] ?? 0)) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate"><?= (int)($piutangMetric['total_toko_berpiutang'] ?? 0) ?> Outlet</span>
                <span class="badge badge-warning font-mono text-[9px] px-1.5 py-0 flex-shrink-0">TEMPO</span>
            </div>
        </div>

        <!-- 4. Saldo Kas & Bank -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid #a855f7;border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">4. Likuiditas Kas</span>
                <i data-lucide="wallet" style="width:14px;height:14px;color:#a855f7;flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:#a855f7;line-height:1.2;">
                <?= Format::rupiah($totalKas) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Kas &amp; Bank</span>
                <span class="badge badge-mono text-[9px] px-1.5 py-0 flex-shrink-0">LIKUID</span>
            </div>
        </div>

    </div>

    <!-- 2. EXECUTIVE LAUNCHPAD -->
    <div class="card p-3.5 sm:p-5" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 sm:gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="crown" style="width:18px;height:18px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 style="font-size:14px;sm:font-size:14.5px;font-weight:800;color:var(--color-ink);line-height:1.3;margin:0;">Pusat Kendali Eksekutif &amp; Analisis Finansial Bisnis</h2>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.35;">Akses laporan mendalam Laba Rugi, Analisis HPP, Arus Kas, Valuasi Inventori, dan Performa Penjualan.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full xl:w-auto">
                <?php if (Auth::can('owner.dashboard')): ?>
                <a href="<?= Router::url('/owner') ?>" class="btn btn-primary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="crown" style="width:14px;height:14px;"></i>
                    <span>Executive Hub</span>
                </a>
                <?php endif; ?>
                <?php if (Auth::can('cash.view_all')): ?>
                <a href="<?= Router::url('/cash/reports') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="wallet" style="width:14px;height:14px;"></i>
                    <span>Arus Kas</span>
                </a>
                <?php endif; ?>
                <?php if (Auth::can('inventory.view_all')): ?>
                <a href="<?= Router::url('/inventory') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="boxes" style="width:14px;height:14px;"></i>
                    <span>Gudang Stok</span>
                </a>
                <?php endif; ?>
                <?php if (Auth::can(['consignment.view_all', 'consignment.view_assigned'])): ?>
                <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="store" style="width:14px;height:14px;"></i>
                    <span>Konsinyasi Hub</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

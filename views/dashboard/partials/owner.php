<?php
/**
 * views/dashboard/partials/owner.php
 * Dasbor Terpersonalisasi Pemilik Perusahaan & Eksekutif
 * Pola Desain Kanonikal: Sesuai /owner (KEREN SNACK ERP)
 */

use App\Core\Router;
use App\Helpers\Format;

$salesMetric = $roleData['salesMetric'] ?? [];
$piutangMetric = $roleData['piutangMetric'] ?? [];
$totalKas = (float)($roleData['totalKas'] ?? 0);
?>

<div class="space-y-5">

    <!-- 1. 4 KPI STAT CARDS (Gaya Kartu Finansial /owner) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- 1. Omzet Hari Ini -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">1. Omzet Hari Ini</span>
                <i data-lucide="trending-up" style="width:15px;height:15px;color:var(--color-success);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-success);line-height:1.2;">
                <?= Format::rupiah((float)($salesMetric['omzet_hari_ini'] ?? 0)) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span><?= (int)($salesMetric['transaksi_hari_ini'] ?? 0) ?> Transaksi Nota</span>
                <span class="badge badge-success font-mono text-[9.5px]">REAL-TIME</span>
            </div>
        </div>

        <!-- 2. Omzet Bulan Ini -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-primary);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">2. Omzet Bulan Ini</span>
                <i data-lucide="bar-chart-3" style="width:15px;height:15px;color:var(--color-primary);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-primary);line-height:1.2;">
                <?= Format::rupiah((float)($salesMetric['omzet_bulan_ini'] ?? 0)) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Periode <?= date('F Y') ?></span>
                <span class="font-mono text-[10px]">Akumulasi</span>
            </div>
        </div>

        <!-- 3. Total Piutang Toko -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-warning);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">3. Piutang Toko</span>
                <i data-lucide="receipt" style="width:15px;height:15px;color:var(--color-warning);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-warning);line-height:1.2;">
                <?= Format::rupiah((float)($piutangMetric['total_piutang'] ?? 0)) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span><?= (int)($piutangMetric['total_toko_berpiutang'] ?? 0) ?> Toko Berjalan</span>
                <span class="badge badge-warning font-mono text-[9.5px]">TEMPO</span>
            </div>
        </div>

        <!-- 4. Saldo Kas & Bank -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid #a855f7;border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">4. Likuiditas Kas</span>
                <i data-lucide="wallet" style="width:15px;height:15px;color:#a855f7;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:#a855f7;line-height:1.2;">
                <?= Format::rupiah($totalKas) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Kas &amp; Bank Aktif</span>
                <span class="badge badge-mono text-[9.5px]">LIKUID</span>
            </div>
        </div>

    </div>

    <!-- 2. EXECUTIVE LAUNCHPAD -->
    <div class="card p-4 sm:p-5" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3">
                <div style="width:38px;height:38px;border-radius:10px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="crown" style="width:18px;height:18px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 style="font-size:14.5px;font-weight:800;color:var(--color-ink);">Pusat Kendali Eksekutif &amp; Analisis Finansial Bisnis</h2>
                        <span class="badge badge-amber font-mono text-[9.5px]">EXECUTIVE SUITE</span>
                    </div>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Akses laporan mendalam Laba Rugi, Analisis HPP, Arus Kas, Valuasi Inventori, dan Performa Penjualan.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full xl:w-auto">
                <a href="<?= Router::url('/owner') ?>" class="btn btn-primary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="crown" style="width:14px;height:14px;"></i>
                    <span>Executive Hub</span>
                </a>
                <a href="<?= Router::url('/cash/reports') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="wallet" style="width:14px;height:14px;"></i>
                    <span>Arus Kas</span>
                </a>
                <a href="<?= Router::url('/inventory') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="boxes" style="width:14px;height:14px;"></i>
                    <span>Gudang Stok</span>
                </a>
                <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="store" style="width:14px;height:14px;"></i>
                    <span>Konsinyasi Hub</span>
                </a>
            </div>
        </div>
    </div>

</div>

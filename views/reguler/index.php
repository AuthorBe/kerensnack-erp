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
            <div class="page-header-icon is-blue">
                <i data-lucide="store"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#2563eb;"></span>
                    <span>Modul Grosir B2B &bull; <?= $isRestricted ? 'Sales Lapangan' : 'Admin Operasional &amp; Owner' ?></span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl"><?= $pageTitle ?? 'Portal Pesanan Reguler' ?></h1>
                <p class="page-subtitle text-xs sm:text-sm"><?= $pageSubtitle ?? 'Pusat Manajemen Piutang Grosir B2B, Penagihan Tempo &amp; Monitoring Risiko Kredit Toko' ?></p>
            </div>
        </div>
        <div class="page-header-actions flex items-center gap-2 flex-wrap">
            <a href="<?= Router::url('/customer-orders/create') ?>" 
               class="btn btn-primary flex items-center gap-2"
               style="border-radius:12px; font-weight:700; text-decoration:none;">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Buat PO Baru</span>
            </a>
            <a href="<?= Router::url('/customer-orders') ?>" 
               class="btn btn-secondary flex items-center gap-2"
               style="border-radius:12px; font-weight:700; text-decoration:none;">
                <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                <span>Daftar Pesanan</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. QUICK STATS KPI (MINIMALIST HORIZONTAL ALA INVENTORY)                  -->
    <!-- ========================================================================= -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:12px;">

        <!-- Total Piutang Toko -->
        <div class="card" style="padding:12px 14px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(239,68,68,0.1); color:#ef4444; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="wallet" style="width:20px; height:20px;"></i>
            </div>
            <div style="min-width:0; flex:1;">
                <div style="font-size:10.5px; font-weight:700; text-transform:uppercase; color:var(--color-ink-mute); letter-spacing:0.04em;">Total Piutang Toko</div>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); color:var(--color-danger); margin-top:2px; font-variant-numeric:tabular-nums; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= Format::rupiah((float)($kpi['total_piutang_nasional'] ?? 0)) ?>
                </div>
                <div style="font-size:10.5px; color:var(--color-ink-mute); margin-top:1px;">
                    <?= (int)($kpi['total_toko_berhutang'] ?? 0) ?> toko berhutang
                </div>
            </div>
        </div>

        <!-- Toko Over-Plafon -->
        <div class="card" style="padding:12px 14px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(234,88,12,0.1); color:#ea580c; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="shield-alert" style="width:20px; height:20px;"></i>
            </div>
            <div style="min-width:0; flex:1;">
                <div style="font-size:10.5px; font-weight:700; text-transform:uppercase; color:var(--color-ink-mute); letter-spacing:0.04em;">Toko Over-Plafon</div>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); color:#ea580c; margin-top:2px; font-variant-numeric:tabular-nums;">
                    <?= (int)($kpi['total_toko_over_plafon'] ?? 0) ?> <span style="font-size:12px; font-weight:600; color:var(--color-ink-mute);">Toko</span>
                </div>
                <div style="font-size:10.5px; color:var(--color-ink-mute); margin-top:1px;">
                    Limit kredit terlampaui
                </div>
            </div>
        </div>

        <!-- Faktur Tempo Gantung -->
        <div class="card" style="padding:12px 14px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(59,130,246,0.1); color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="file-clock" style="width:20px; height:20px;"></i>
            </div>
            <div style="min-width:0; flex:1;">
                <div style="font-size:10.5px; font-weight:700; text-transform:uppercase; color:var(--color-ink-mute); letter-spacing:0.04em;">Faktur Belum Lunas</div>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); color:#2563eb; margin-top:2px; font-variant-numeric:tabular-nums;">
                    <?= (int)($fakturKpi['total_faktur_gantung'] ?? 0) ?> <span style="font-size:12px; font-weight:600; color:var(--color-ink-mute);">Nota</span>
                </div>
                <div style="font-size:10.5px; color:var(--color-ink-mute); margin-top:1px; font-family:var(--font-mono);">
                    <?= Format::rupiah((float)($fakturKpi['total_nominal_gantung'] ?? 0)) ?>
                </div>
            </div>
        </div>

        <!-- Radar Risiko Alerts -->
        <div class="card" style="padding:12px 14px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(220,38,38,0.1); color:#dc2626; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="alert-triangle" style="width:20px; height:20px;"></i>
            </div>
            <div style="min-width:0; flex:1;">
                <div style="font-size:10.5px; font-weight:700; text-transform:uppercase; color:var(--color-ink-mute); letter-spacing:0.04em;">Radar Risiko</div>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); color:#dc2626; margin-top:2px; font-variant-numeric:tabular-nums;">
                    <?= $menumpukCount + $overdueCount ?> <span style="font-size:12px; font-weight:600; color:var(--color-ink-mute);">Warning</span>
                </div>
                <div style="font-size:10.5px; color:var(--color-ink-mute); margin-top:1px;">
                    <?= $menumpukCount ?> numpuk &bull; <?= $overdueCount ?> overdue
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 3. MENU NAVIGASI PORTAL REGULER (MINIMALIS KONSINYASI)                    -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">

        <!-- CARD 1: Buku Piutang per Toko -->
        <a href="<?= Router::url('/reguler/tagihan') ?>" 
           class="card p-3.5 sm:p-5 group flex flex-col justify-between hover:shadow-lg transition-all"
           style="border-radius:18px;text-decoration:none;min-height:125px;border:1px solid var(--color-hairline);">
            <div class="flex items-start justify-between">
                <i data-lucide="book-open-check" class="w-6 sm:w-7 h-6 sm:h-7 group-hover:scale-110 transition-transform" style="color:#2563eb;"></i>
                <i data-lucide="arrow-up-right" class="w-3.5 sm:w-4 h-3.5 sm:h-4 transition-colors" style="color:var(--color-ink-mute);"></i>
            </div>
            <div class="mt-3 sm:mt-4">
                <h3 class="text-xs sm:text-sm font-black transition-colors" style="color:var(--color-ink);">Buku Piutang &amp; Tagihan</h3>
                <p class="text-[11px] mt-0.5 line-clamp-1" style="color:var(--color-ink-mute);">Cetak surat tagihan &amp; WA toko</p>
            </div>
        </a>

        <!-- CARD 2: Early Warning Radar -->
        <a href="<?= Router::url('/reguler/early-warning') ?>" 
           class="card p-3.5 sm:p-5 group flex flex-col justify-between hover:shadow-lg transition-all"
           style="border-radius:18px;text-decoration:none;min-height:125px;border:1px solid var(--color-hairline);">
            <div class="flex items-start justify-between">
                <i data-lucide="shield-alert" class="w-6 sm:w-7 h-6 sm:h-7 group-hover:scale-110 transition-transform" style="color:#ea580c;"></i>
                <i data-lucide="arrow-up-right" class="w-3.5 sm:w-4 h-3.5 sm:h-4 transition-colors" style="color:var(--color-ink-mute);"></i>
            </div>
            <div class="mt-3 sm:mt-4">
                <h3 class="text-xs sm:text-sm font-black transition-colors" style="color:var(--color-ink);">Early Warning Toko</h3>
                <p class="text-[11px] mt-0.5 line-clamp-1" style="color:var(--color-ink-mute);">Over-plafon &amp; faktur gantung</p>
            </div>
        </a>

        <!-- CARD 3: Laporan Penjualan per Toko B2B -->
        <a href="<?= Router::url('/reguler/laporan-toko') ?>" 
           class="card p-3.5 sm:p-5 group flex flex-col justify-between hover:shadow-lg transition-all"
           style="border-radius:18px;text-decoration:none;min-height:125px;border:1px solid var(--color-hairline);">
            <div class="flex items-start justify-between">
                <i data-lucide="bar-chart-3" class="w-6 sm:w-7 h-6 sm:h-7 group-hover:scale-110 transition-transform" style="color:#10b981;"></i>
                <i data-lucide="arrow-up-right" class="w-3.5 sm:w-4 h-3.5 sm:h-4 transition-colors" style="color:var(--color-ink-mute);"></i>
            </div>
            <div class="mt-3 sm:mt-4">
                <h3 class="text-xs sm:text-sm font-black transition-colors" style="color:var(--color-ink);">Laporan per Toko</h3>
                <p class="text-[11px] mt-0.5 line-clamp-1" style="color:var(--color-ink-mute);">Pareto omzet &amp; tren belanja</p>
            </div>
        </a>

        <!-- CARD 4: Antrean PO & Picking List Gudang -->
        <a href="<?= Router::url('/customer-orders/po-list') ?>" 
           class="card p-3.5 sm:p-5 group flex flex-col justify-between hover:shadow-lg transition-all"
           style="border-radius:18px;text-decoration:none;min-height:125px;border:1px solid var(--color-hairline);">
            <div class="flex items-start justify-between">
                <i data-lucide="file-input" class="w-6 sm:w-7 h-6 sm:h-7 group-hover:scale-110 transition-transform" style="color:#8b5cf6;"></i>
                <i data-lucide="arrow-up-right" class="w-3.5 sm:w-4 h-3.5 sm:h-4 transition-colors" style="color:var(--color-ink-mute);"></i>
            </div>
            <div class="mt-3 sm:mt-4">
                <h3 class="text-xs sm:text-sm font-black transition-colors" style="color:var(--color-ink);">Antrean PO Gudang</h3>
                <p class="text-[11px] mt-0.5 line-clamp-1" style="color:var(--color-ink-mute);">Picking list &amp; siap kirim</p>
            </div>
        </a>

    </div>

    <!-- ========================================================================= -->
    <!-- 4. TOP 5 TOKO DENGAN PIUTANG TERBESAR (TABEL PRESISI ALA INVENTORY)        -->
    <!-- ========================================================================= -->
    <div class="card p-0 rounded-2xl overflow-hidden" style="border:1px solid var(--color-hairline); background:var(--color-surface);">
        <!-- Table Header Toolbar -->
        <div style="padding:14px 18px; border-bottom:1px solid var(--color-hairline); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:36px; height:36px; border-radius:10px; background:rgba(239,68,68,0.1); color:#ef4444; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i data-lucide="alert-circle" style="width:18px; height:18px;"></i>
                </div>
                <div>
                    <h2 style="font-size:14px; font-weight:800; color:var(--color-ink); margin:0; line-height:1.2;">Top Toko dengan Piutang Terbesar</h2>
                    <p style="font-size:11.5px; color:var(--color-ink-mute); margin:2px 0 0 0;">Toko mitra grosir dengan eksposur kewajiban piutang berjalan tertinggi</p>
                </div>
            </div>
            <a href="<?= Router::url('/reguler/tagihan') ?>" class="btn btn-secondary btn-sm" style="font-size:11.5px; font-weight:700; height:32px; display:inline-flex; align-items:center; gap:6px;">
                <span>Buku Piutang Lengkap</span>
                <i data-lucide="arrow-right" style="width:13px; height:13px;"></i>
            </a>
        </div>

        <?php if (empty($topDebtors)): ?>
            <!-- Clean Empty State (ala Inventory) -->
            <div style="padding:48px 16px; text-align:center; color:var(--color-ink-mute);">
                <div style="width:44px; height:44px; border-radius:12px; background:var(--color-canvas-soft); border:1px solid var(--color-hairline); display:flex; align-items:center; justify-content:center; margin:0 auto 10px auto; color:var(--color-ink-mute); opacity:0.85;">
                    <i data-lucide="shield-check" style="width:22px; height:22px; color:#10b981;"></i>
                </div>
                <div style="font-weight:700; color:var(--color-ink); font-size:13.5px; margin-bottom:4px;">Tidak Ada Piutang Berjalan Tertunggak</div>
                <div style="font-size:12px; color:var(--color-ink-mute); max-width:340px; margin:0 auto 12px auto;">
                    Seluruh pesanan reguler mitra toko berada dalam status lunas dan tidak ada saldo tagihan tertunggak.
                </div>
                <a href="<?= Router::url('/reguler/tagihan') ?>" class="btn btn-secondary btn-sm" style="font-size:11.5px; display:inline-flex; align-items:center; gap:5px;">
                    <i data-lucide="book-open" style="width:12px; height:12px;"></i>
                    <span>Buka Buku Piutang Toko</span>
                </a>
            </div>
        <?php else: ?>
            <div class="table-wrapper" style="overflow-anchor:none;">
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width:45px; text-align:center;">No</th>
                                <th style="min-width:240px;">Toko Pelanggan</th>
                                <th class="hide-sm" style="min-width:140px;">Wilayah / Rute</th>
                                <th style="text-align:center; width:120px;">Skema Bayar</th>
                                <th style="text-align:right; width:140px;">Plafon Kredit</th>
                                <th style="text-align:right; width:150px;">Piutang Berjalan</th>
                                <th class="hide-mobile" style="text-align:center; width:110px;">Status</th>
                                <th style="text-align:center; width:100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topDebtors as $idx => $td): 
                                $plafon = (float)($td['plafon_piutang'] ?? 0);
                                $piutang = (float)($td['total_piutang_berjalan'] ?? 0);
                                $isOver = ($plafon > 0 && $piutang > $plafon);
                            ?>
                            <tr>
                                <!-- No -->
                                <td style="text-align:center; font-family:var(--font-mono); font-size:12px; color:var(--color-ink-mute);">
                                    <?= $idx + 1 ?>
                                </td>

                                <!-- Toko & Pelanggan -->
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                        <span class="badge badge-mono"><?= htmlspecialchars($td['kode_pelanggan']) ?></span>
                                        <span style="font-size:13px; font-weight:700; color:var(--color-ink);"><?= htmlspecialchars($td['nama_toko']) ?></span>
                                    </div>
                                    <div style="font-size:11px; font-family:var(--font-mono); color:var(--color-ink-mute); margin-top:2px;">
                                        <?= htmlspecialchars($td['nama_pemilik'] ?? 'Mitra Toko') ?>
                                        <?= !empty($td['nomor_whatsapp']) ? ' &bull; ' . htmlspecialchars($td['nomor_whatsapp']) : '' ?>
                                    </div>
                                </td>

                                <!-- Wilayah / Rute -->
                                <td class="hide-sm">
                                    <div style="display:flex; align-items:center; gap:5px; font-size:12px; color:var(--color-ink); font-weight:600;">
                                        <i data-lucide="map-pin" style="width:13px; height:13px; color:var(--color-ink-mute); flex-shrink:0;"></i>
                                        <span><?= htmlspecialchars($td['nama_wilayah'] ?? 'Semua Wilayah') ?></span>
                                    </div>
                                </td>

                                <!-- Skema Bayar -->
                                <td style="text-align:center;">
                                    <?php if ($td['tipe_pembayaran_default'] === 'tempo_faktur'): ?>
                                        <span class="badge badge-warning" style="font-size:10px; font-weight:700; padding:2px 8px;">Tempo Faktur</span>
                                    <?php elseif ($td['tipe_pembayaran_default'] === 'tempo_tanggal'): ?>
                                        <span class="badge badge-info" style="font-size:10px; font-weight:700; padding:2px 8px;">Tempo Tanggal</span>
                                    <?php else: ?>
                                        <span class="badge badge-neutral" style="font-size:10px; font-weight:700; padding:2px 8px;"><?= strtoupper(str_replace('_', ' ', $td['tipe_pembayaran_default'])) ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Plafon Kredit -->
                                <td style="text-align:right;">
                                    <div style="font-family:var(--font-mono); font-weight:700; font-size:13px; color:var(--color-ink-mute); font-variant-numeric:tabular-nums;">
                                        <?= $plafon > 0 ? Format::rupiah($plafon) : 'Tanpa Limit' ?>
                                    </div>
                                </td>

                                <!-- Piutang Berjalan -->
                                <td style="text-align:right;">
                                    <div style="font-family:var(--font-mono); font-weight:800; font-size:13.5px; font-variant-numeric:tabular-nums; color:<?= $isOver ? 'var(--color-danger)' : 'var(--color-ink)' ?>;">
                                        <?= Format::rupiah($piutang) ?>
                                    </div>
                                    <div style="font-size:10.5px; color:var(--color-ink-mute); margin-top:1px;">
                                        <?= (int)($td['total_faktur_gantung'] ?? 0) ?> Faktur Gantung
                                    </div>
                                </td>

                                <!-- Status (Desktop) -->
                                <td class="hide-mobile" style="text-align:center;">
                                    <?php if ($isOver): ?>
                                        <span class="badge badge-danger" style="font-size:10px; font-weight:700;">Over-Plafon</span>
                                    <?php elseif ($plafon > 0): ?>
                                        <span class="badge badge-success" style="font-size:10px; font-weight:700;">Dalam Limit</span>
                                    <?php else: ?>
                                        <span class="badge badge-neutral" style="font-size:10px; font-weight:700;">Tanpa Limit</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Aksi -->
                                <td style="text-align:center;">
                                    <a href="<?= Router::url('/reguler/tagihan?q=' . urlencode($td['kode_pelanggan'])) ?>" 
                                       class="btn btn-secondary btn-sm"
                                       style="height:28px; padding:0 10px; font-size:11px; font-weight:700; display:inline-flex; align-items:center; gap:4px;"
                                       title="Buka Buku Piutang &amp; Rincian Faktur">
                                        <i data-lucide="book-open" style="width:12px; height:12px;"></i>
                                        <span>Buku</span>
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

</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

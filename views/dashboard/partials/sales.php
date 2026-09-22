<?php
/**
 * views/dashboard/partials/sales.php
 * Dasbor Terpersonalisasi Salesman Titip Jual Konsinyasi & Toko Binaan
 * Pola Desain Kanonikal: Sesuai /owner (KEREN SNACK ERP)
 */

use App\Core\Router;
use App\Core\Auth;
use App\Helpers\Format;

$assignedStores = $roleData['assignedStores'] ?? [];
$recentVisits = $roleData['recentVisits'] ?? [];
$stats = $roleData['stats'] ?? [];
?>

<div class="space-y-4 sm:space-y-5">

    <!-- 1. 4 KPI STAT CARDS (Gaya Kartu Finansial /owner) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- 1. Toko Binaan Saya -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid #a855f7;border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">1. Toko Binaan</span>
                <i data-lucide="store" style="width:14px;height:14px;color:#a855f7;flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-ink);line-height:1.2;">
                <?= (int)($stats['total_toko_binaan'] ?? 0) ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">Outlet</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Outlet Aktif</span>
                <span class="badge badge-mono text-[9px] px-1.5 py-0 flex-shrink-0">AKTIF</span>
            </div>
        </div>

        <!-- 2. Perlu Opname / Dikunjungi -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-warning);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">2. Perlu Opname</span>
                <i data-lucide="calendar-clock" style="width:14px;height:14px;color:var(--color-warning);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-warning);line-height:1.2;">
                <?= (int)($stats['perlu_dikunjungi'] ?? 0) ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">Toko</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">&gt; 7 hari lalu</span>
                <span class="badge badge-warning font-mono text-[9px] px-1.5 py-0 flex-shrink-0">OPNAME</span>
            </div>
        </div>

        <!-- 3. Piutang Toko Binaan -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-danger, #ef4444);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">3. Piutang Toko</span>
                <i data-lucide="receipt" style="width:14px;height:14px;color:var(--color-danger, #ef4444);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(14px, 2.4vw, 20px);font-weight:900;color:var(--color-danger, #ef4444);line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= Format::rupiah((float)($stats['total_piutang_binaan'] ?? 0)) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Tagihan Tempo</span>
                <span class="badge badge-rose font-mono text-[9px] px-1.5 py-0 flex-shrink-0">TEMPO</span>
            </div>
        </div>

        <!-- 4. Laku Konsinyasi Bulan Ini -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">4. Laku Bulan Ini</span>
                <i data-lucide="trending-up" style="width:14px;height:14px;color:var(--color-success);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(14px, 2.4vw, 20px);font-weight:900;color:var(--color-success);line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= Format::rupiah((float)($stats['total_laku_bulan_ini'] ?? 0)) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Total Omzet</span>
                <span class="badge badge-success font-mono text-[9px] px-1.5 py-0 flex-shrink-0">KONSINYASI</span>
            </div>
        </div>

    </div>

    <!-- 2. QUICK ACTION LAUNCHPAD -->
    <div class="card p-3.5 sm:p-5" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 sm:gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(168,85,247,0.12);color:#a855f7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="store" style="width:18px;height:18px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 style="font-size:14px;sm:font-size:14.5px;font-weight:800;color:var(--color-ink);line-height:1.3;margin:0;">Portal Konsinyasi &amp; Kunjungan Toko</h2>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.35;">Catat opname fisik stok di rak toko, pantau laporan omzet laku, dan kelola penagihan piutang toko binaan.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full xl:w-auto">
                <?php if (Auth::can(['consignment.opname_all', 'consignment.opname_assigned'])): ?>
                <a href="<?= Router::url('/consignment/opname') ?>" class="btn btn-primary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="clipboard-check" style="width:14px;height:14px;"></i>
                    <span>Opname Rak</span>
                </a>
                <?php endif; ?>
                <?php if (Auth::can(['consignment.reports_all', 'consignment.reports_assigned'])): ?>
                <a href="<?= Router::url('/consignment/laporan-penjualan') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="bar-chart-3" style="width:14px;height:14px;"></i>
                    <span>Laporan Laku</span>
                </a>
                <?php endif; ?>
                <?php if (Auth::can(['consignment.piutang', 'consignment.view_all', 'consignment.view_assigned'])): ?>
                <a href="<?= Router::url('/consignment/tagihan') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="receipt" style="width:14px;height:14px;"></i>
                    <span>Tagihan</span>
                </a>
                <?php endif; ?>
                <?php if (Auth::can(['master.customers_view_all', 'master.customers_view_assigned'])): ?>
                <a href="<?= Router::url('/customers') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="users" style="width:14px;height:14px;"></i>
                    <span>Daftar Toko</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 3. DUA KOLOM: TOKO BINAAN & LAPORAN LAKU KONSINYASI (Gaya 2-Pane /owner) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 items-stretch">
        
        <!-- KOLOM KIRI: TOKO BINAAN & JADWAL KUNJUNGAN OPNAME -->
        <div class="card h-full flex flex-col justify-between overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
            <div class="flex-1 flex flex-col">
                <!-- Card Header (Standard 'Lihat semua >' Button) -->
                <div class="px-3 py-2.5 sm:px-4 sm:py-3.5 border-b border-hairline" style="background:var(--color-canvas);">
                    <div class="flex items-center justify-between gap-2 sm:gap-3">
                        <div class="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1">
                            <div style="width:30px;height:30px;border-radius:8px;background:rgba(168,85,247,0.12);color:#a855f7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="map-pinned" style="width:15px;height:15px;"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <h3 class="truncate" style="font-size:12px;sm:font-size:13px;font-weight:800;color:var(--color-ink);line-height:1.2;margin:0;flex-shrink:1;">Toko Binaan &amp; Jadwal Opname</h3>
                                    <span class="inline-flex items-center justify-center flex-shrink-0 font-mono" style="font-size:9.5px;font-weight:700;padding:1px 5px;border-radius:9999px;background:rgba(168,85,247,0.12);color:#a855f7;border:1px solid rgba(168,85,247,0.25);line-height:1.2;min-width:18px;"><?= count($assignedStores) ?></span>
                                </div>
                                <p class="truncate" style="font-size:10.5px;sm:font-size:11px;color:var(--color-ink-mute);margin-top:2px;line-height:1.25;">Jadwal opname rak dan saldo piutang berjalan</p>
                            </div>
                        </div>
                        <?php if (Auth::can(['consignment.view_all', 'consignment.view_assigned'])): ?>
                        <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary btn-sm" style="font-size:10.5px;sm:font-size:11px;font-weight:700;padding:3px 8px;border-radius:7px;display:inline-flex;align-items:center;gap:3px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-ink);box-shadow:0 1px 2px rgba(0,0,0,0.04);white-space:nowrap;flex-shrink:0;height:28px;">
                            <span>Lihat semua</span>
                            <i data-lucide="chevron-right" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card Body -->
                <?php if (empty($assignedStores)): ?>
                <div class="p-6 sm:p-8 text-center flex-1 flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);padding-top:32px;padding-bottom:32px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:rgba(168,85,247,0.1);color:#a855f7;display:flex;align-items:center;justify-content:center;margin:0 auto 12px auto;flex-shrink:0;">
                        <i data-lucide="store" style="width:22px;height:22px;"></i>
                    </div>
                    <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);margin:0 0 4px 0;">Belum Ada Toko Ditugaskan</h4>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:290px;margin:0 auto;line-height:1.4;">Admin belum menetapkan toko binaan khusus ke akun Anda. Anda dapat melihat semua toko di portal konsinyasi.</p>
                </div>
                <?php else: ?>
                <div class="table-container overflow-x-auto" style="width:100%;-webkit-overflow-scrolling:touch;">
                    <table class="table w-full text-left" style="margin-bottom:0;border-collapse:collapse;min-width:480px;">
                        <thead>
                            <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Toko / Outlet</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Wilayah</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Kunjungan</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Piutang</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignedStores as $store): 
                                $lastVisit = $store['terakhir_kunjungan'];
                                $isOverdue = empty($lastVisit) || (strtotime($lastVisit) < strtotime('-7 days'));
                            ?>
                            <tr class="hover:bg-slate-500/5 transition-colors" style="border-bottom:1px solid var(--color-hairline);">
                                <td style="padding:10px 12px;vertical-align:middle;">
                                    <strong style="color:var(--color-ink);font-size:12.5px;" class="block truncate max-w-[130px]"><?= htmlspecialchars($store['nama_toko']) ?></strong>
                                    <div class="font-mono" style="font-size:10.5px;color:var(--color-ink-mute);"><?= htmlspecialchars($store['kode_pelanggan']) ?></div>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                                    <span class="badge badge-slate font-semibold text-[9.5px] px-2 py-0.5"><?= htmlspecialchars($store['nama_wilayah'] ?? $store['kode_rute'] ?? 'Rute Utama') ?></span>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                                    <?php if ($lastVisit): ?>
                                        <span class="<?= $isOverdue ? 'badge badge-amber font-semibold text-[9px] px-1.5 py-0.5' : 'text-xs text-muted font-medium' ?>">
                                             <?= Format::tanggal($lastVisit) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-danger font-semibold text-[9px] px-1.5 py-0.5">Belum Opname</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                                    <strong class="font-mono font-bold text-[12.5px]" style="<?= (float)($store['total_piutang_berjalan'] ?? 0) > 0 ? 'color:var(--color-danger, #ef4444);' : 'color:var(--color-ink);' ?>">
                                        <?= Format::rupiah((float)($store['total_piutang_berjalan'] ?? 0)) ?>
                                    </strong>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;text-align:right;">
                                    <?php if (Auth::can(['consignment.opname_all', 'consignment.opname_assigned'])): ?>
                                    <a href="<?= Router::url('/consignment/opname?pelanggan_id=' . $store['id']) ?>" class="btn btn-secondary btn-sm" style="font-size:11px;font-weight:700;padding:3px 8px;gap:3px;">
                                        <i data-lucide="clipboard-check" style="width:11px;height:11px;"></i>
                                        <span>Opname</span>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted text-[11px]">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- KOLOM KANAN: LAPORAN LAKU & RIWAYAT KUNJUNGAN KONSINYASI -->
        <div class="card h-full flex flex-col justify-between overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
            <div class="flex-1 flex flex-col">
                <!-- Card Header (Standard 'Lihat semua >' Button) -->
                <div class="px-3 py-2.5 sm:px-4 sm:py-3.5 border-b border-hairline" style="background:var(--color-canvas);">
                    <div class="flex items-center justify-between gap-2 sm:gap-3">
                        <div class="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1">
                            <div style="width:30px;height:30px;border-radius:8px;background:rgba(34,197,94,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="file-check" style="width:15px;height:15px;"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <h3 class="truncate" style="font-size:12px;sm:font-size:13px;font-weight:800;color:var(--color-ink);line-height:1.2;margin:0;flex-shrink:1;">Laporan Laku &amp; Kunjungan</h3>
                                    <span class="inline-flex items-center justify-center flex-shrink-0 font-mono" style="font-size:9.5px;font-weight:700;padding:1px 5px;border-radius:9999px;background:rgba(34,197,94,0.12);color:var(--color-success);border:1px solid rgba(34,197,94,0.25);line-height:1.2;min-width:18px;"><?= count($recentVisits) ?></span>
                                </div>
                                <p class="truncate" style="font-size:10.5px;sm:font-size:11px;color:var(--color-ink-mute);margin-top:2px;line-height:1.25;">Riwayat kunjungan toko dan laporan barang laku</p>
                            </div>
                        </div>
                        <?php if (Auth::can(['consignment.reports_all', 'consignment.reports_assigned'])): ?>
                        <a href="<?= Router::url('/consignment/laporan-penjualan') ?>" class="btn btn-secondary btn-sm" style="font-size:10.5px;sm:font-size:11px;font-weight:700;padding:3px 8px;border-radius:7px;display:inline-flex;align-items:center;gap:3px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-ink);box-shadow:0 1px 2px rgba(0,0,0,0.04);white-space:nowrap;flex-shrink:0;height:28px;">
                            <span>Lihat semua</span>
                            <i data-lucide="chevron-right" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card Body -->
                <?php if (empty($recentVisits)): ?>
                <div class="p-6 sm:p-8 text-center flex-1 flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);padding-top:32px;padding-bottom:32px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:rgba(34,197,94,0.1);color:var(--color-success);display:flex;align-items:center;justify-content:center;margin:0 auto 12px auto;flex-shrink:0;">
                        <i data-lucide="clipboard-list" style="width:22px;height:22px;"></i>
                    </div>
                    <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);margin:0 0 4px 0;">Belum Ada Riwayat Kunjungan</h4>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:290px;margin:0 auto;line-height:1.4;">Klik tombol "Opname Rak" di atas untuk mencatat opname stok dan omzet laku konsinyasi.</p>
                </div>
                <?php else: ?>
                <div class="table-container overflow-x-auto" style="width:100%;-webkit-overflow-scrolling:touch;">
                    <table class="table w-full text-left" style="margin-bottom:0;border-collapse:collapse;min-width:440px;">
                        <thead>
                            <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">No. Visit</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Toko / Outlet</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Total Laku</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Status</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentVisits as $visit): 
                                $status = $visit['status'] ?? 'selesai';
                            ?>
                            <tr class="hover:bg-slate-500/5 transition-colors" style="border-bottom:1px solid var(--color-hairline);">
                                <td style="padding:10px 12px;vertical-align:middle;">
                                    <strong class="font-mono" style="font-size:12px;color:var(--color-primary);"><?= htmlspecialchars($visit['nomor_kunjungan'] ?? '—') ?></strong>
                                    <div style="font-size:10.5px;color:var(--color-ink-mute);"><?= Format::tanggal($visit['tanggal_kunjungan'] ?? date('Y-m-d')) ?></div>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;">
                                    <strong style="color:var(--color-ink);font-size:12.5px;" class="block truncate max-w-[140px]"><?= htmlspecialchars($visit['nama_toko'] ?? 'Outlet') ?></strong>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                                    <strong class="font-mono font-bold text-[12.5px]" style="color:var(--color-ink);"><?= Format::rupiah((float)($visit['total_laku_bersih'] ?? 0)) ?></strong>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                                    <span class="badge badge-success font-semibold text-[9.5px] px-2 py-0.5"><?= ucfirst($status) ?></span>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;text-align:right;">
                                    <?php if (Auth::can(['consignment.reports_all', 'consignment.reports_assigned'])): ?>
                                    <a href="<?= Router::url('/consignment/laporan-penjualan') ?>" class="btn btn-secondary btn-sm" style="font-size:11px;font-weight:700;padding:3px 8px;">
                                        <i data-lucide="file-text" style="width:11px;height:11px;"></i>
                                        <span>Rincian</span>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted text-[11px]">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

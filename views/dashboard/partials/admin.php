<?php
/**
 * views/dashboard/partials/admin.php
 * Dasbor Terpersonalisasi Administrator Operasional & Kasir Toko
 * Pola Desain Kanonikal: Sesuai /owner (KEREN SNACK ERP)
 */

use App\Core\Router;
use App\Core\Auth;
use App\Helpers\Format;

$posStats = $roleData['posStats'] ?? [];
$pendingOrders = $roleData['pendingOrders'] ?? [];
$cashAccounts = $roleData['cashAccounts'] ?? [];
$todayDeliveriesCount = (int)($roleData['todayDeliveriesCount'] ?? 0);
?>

<div class="space-y-4 sm:space-y-5">

    <!-- 1. 4 KPI STAT CARDS (Gaya Kartu Finansial /owner) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- 1. Kasir POS Hari Ini -->
        <div class="card p-3.5 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid #e11d48;border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">1. POS Hari Ini</span>
                <i data-lucide="scan-line" style="width:14px;height:14px;color:#e11d48;flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-ink);line-height:1.2;">
                <?= (int)($posStats['total_transaksi'] ?? 0) ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">Nota</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate" style="color:#e11d48;font-weight:700;"><?= Format::rupiah((float)($posStats['omzet_hari_ini'] ?? 0)) ?></span>
                <span class="badge badge-mono text-[9px] px-1.5 py-0 flex-shrink-0">RETAIL</span>
            </div>
        </div>

        <!-- 2. Pesanan Pending -->
        <div class="card p-3.5 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-warning);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">2. Antrean Order</span>
                <i data-lucide="inbox" style="width:14px;height:14px;color:var(--color-warning);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-warning);line-height:1.2;">
                <?= count($pendingOrders) ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">Nota</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Antrean B2B</span>
                <span class="badge badge-warning font-mono text-[9px] px-1.5 py-0 flex-shrink-0">PROSES</span>
            </div>
        </div>

        <!-- 3. Surat Jalan Hari Ini -->
        <div class="card p-3.5 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-primary);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">3. Surat Jalan</span>
                <i data-lucide="truck" style="width:14px;height:14px;color:var(--color-primary);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-primary);line-height:1.2;">
                <?= $todayDeliveriesCount ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">SJ</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Armada Toko</span>
                <span class="font-mono text-[9.5px] flex-shrink-0">Hari Ini</span>
            </div>
        </div>

        <!-- 4. Akun Kas Aktif -->
        <div class="card p-3.5 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">4. Akun Kas</span>
                <i data-lucide="wallet" style="width:14px;height:14px;color:var(--color-success);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-success);line-height:1.2;">
                <?= count($cashAccounts) ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">Akun</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Buku Kas</span>
                <span class="badge badge-success font-mono text-[9px] px-1.5 py-0 flex-shrink-0">READY</span>
            </div>
        </div>

    </div>

    <!-- 2. QUICK ACTION LAUNCHPAD -->
    <div class="card p-3.5 sm:p-5" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 sm:gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(37,99,235,0.1);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="shield-check" style="width:18px;height:18px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 style="font-size:14px;sm:font-size:14.5px;font-weight:800;color:var(--color-ink);line-height:1.3;margin:0;">Pusat Operasional Penjualan &amp; Toko</h2>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.35;">Akses kasir POS retail, antrean pesanan grosir, surat jalan pengiriman, dan buku kas.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full xl:w-auto">
                <?php if (Auth::can('pos.pos')): ?>
                <a href="<?= Router::url('/pos') ?>" class="btn btn-primary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="scan-line" style="width:14px;height:14px;"></i>
                    <span>Kasir POS</span>
                </a>
                <?php endif; ?>
                <?php if (Auth::can(['orders.view_all', 'orders.view_assigned'])): ?>
                <a href="<?= Router::url('/customer-orders') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="shopping-bag" style="width:14px;height:14px;"></i>
                    <span>Pesanan Toko</span>
                </a>
                <?php endif; ?>
                <?php if (Auth::can(['deliveries.view_all', 'deliveries.view_assigned'])): ?>
                <a href="<?= Router::url('/deliveries') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="truck" style="width:14px;height:14px;"></i>
                    <span>Surat Jalan</span>
                </a>
                <?php endif; ?>
                <?php if (Auth::can('cash.view_all')): ?>
                <a href="<?= Router::url('/cash') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="wallet" style="width:14px;height:14px;"></i>
                    <span>Buku Kas</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 3. DUA KOLOM: ANTREAN PESANAN & STATUS KAS (Gaya 2-Pane /owner) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 items-stretch">
        
        <!-- KOLOM KIRI: ANTREAN PESANAN PELANGGAN -->
        <div class="card h-full flex flex-col justify-between overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
            <div class="flex-1 flex flex-col">
                <!-- Card Header (Gaya Tombol 'Lihat semua >' Modern) -->
                <div class="px-3 py-2.5 sm:px-4 sm:py-3.5 border-b border-hairline" style="background:var(--color-canvas);">
                    <div class="flex items-center justify-between gap-2 sm:gap-3">
                        <div class="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1">
                            <div style="width:30px;height:30px;border-radius:8px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="clipboard-list" style="width:15px;height:15px;"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <h3 class="truncate" style="font-size:12px;sm:font-size:13px;font-weight:800;color:var(--color-ink);line-height:1.2;margin:0;flex-shrink:1;">Pesanan Baru Perlu Diproses</h3>
                                    <span class="inline-flex items-center justify-center flex-shrink-0 font-mono" style="font-size:9.5px;font-weight:700;padding:1px 5px;border-radius:9999px;background:rgba(245,158,11,0.12);color:#d97706;border:1px solid rgba(245,158,11,0.25);line-height:1.2;min-width:18px;"><?= count($pendingOrders) ?></span>
                                </div>
                                <p style="font-size:10.5px;sm:font-size:11px;color:var(--color-ink-mute);margin-top:2px;line-height:1.25;" class="truncate">Pesanan siap proses</p>
                            </div>
                        </div>
                        <?php if (Auth::can(['orders.view_all', 'orders.view_assigned'])): ?>
                        <a href="<?= Router::url('/customer-orders') ?>" class="btn btn-secondary btn-sm" style="font-size:10.5px;sm:font-size:11px;font-weight:700;padding:3px 8px;border-radius:7px;display:inline-flex;align-items:center;gap:3px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-ink);box-shadow:0 1px 2px rgba(0,0,0,0.04);white-space:nowrap;flex-shrink:0;height:28px;">
                            <span>Lihat semua</span>
                            <i data-lucide="chevron-right" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card Body -->
                <?php if (empty($pendingOrders)): ?>
                <div class="p-6 sm:p-8 text-center flex-1 flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);padding-top:32px;padding-bottom:32px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:rgba(16,185,129,0.1);color:var(--color-success);display:flex;align-items:center;justify-content:center;margin:0 auto 12px auto;flex-shrink:0;">
                        <i data-lucide="check-circle-2" style="width:22px;height:22px;"></i>
                    </div>
                    <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);margin:0 0 4px 0;">Tidak Ada Antrean Pesanan</h4>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:290px;margin:0 auto;line-height:1.4;">
                        Seluruh pesanan pelanggan sudah selesai diproses dan siap dikirim.
                    </p>
                    <?php if (Auth::can('orders.create')): ?>
                    <div class="mt-3.5">
                        <a href="<?= Router::url('/customer-orders/create') ?>" class="btn btn-secondary btn-sm" style="font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border-radius:8px;">
                            <i data-lucide="plus" style="width:12px;height:12px;"></i>
                            <span>Buat Pesanan Baru</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-container overflow-x-auto" style="width:100%;-webkit-overflow-scrolling:touch;">
                    <table class="table w-full text-left" style="margin-bottom:0;border-collapse:collapse;min-width:460px;">
                        <thead>
                            <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">No. Nota</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Pelanggan</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Total Netto</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Status</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingOrders as $ord): 
                                $st = $ord['status_pemrosesan'] ?? 'po';
                            ?>
                            <tr class="hover:bg-slate-500/5 transition-colors" style="border-bottom:1px solid var(--color-hairline);">
                                <td style="padding:10px 12px;vertical-align:middle;">
                                    <strong class="font-mono" style="font-size:12px;color:var(--color-primary);"><?= htmlspecialchars($ord['nomor_nota']) ?></strong>
                                    <div style="font-size:10.5px;color:var(--color-ink-mute);"><?= Format::tanggal($ord['tanggal_pesanan']) ?></div>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;">
                                    <strong style="color:var(--color-ink);font-size:12.5px;" class="truncate block max-w-[140px]"><?= htmlspecialchars($ord['nama_toko']) ?></strong>
                                    <div style="font-size:10.5px;color:var(--color-ink-mute);"><?= htmlspecialchars($ord['nama_wilayah'] ?? 'Rute Utama') ?></div>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                                    <strong class="font-mono font-bold" style="font-size:12.5px;color:var(--color-ink);"><?= Format::rupiah((float)$ord['total_netto']) ?></strong>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                                    <span class="badge badge-amber font-semibold text-[9.5px] px-2 py-0.5"><?= ucfirst(str_replace('_', ' ', $st)) ?></span>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;text-align:right;">
                                    <?php if (Auth::can(['orders.view_all', 'orders.view_assigned', 'orders.edit_all', 'orders.edit_assigned'])): ?>
                                    <a href="<?= Router::url('/customer-orders') ?>" class="btn btn-secondary btn-sm" style="font-size:11px;font-weight:700;padding:3px 8px;">
                                        <span>Proses</span>
                                        <i data-lucide="arrow-right" style="width:11px;height:11px;"></i>
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

        <!-- KOLOM KANAN: STATUS AKUN KAS AKTIF -->
        <div class="card h-full flex flex-col justify-between overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
            <div class="flex-1 flex flex-col">
                <!-- Card Header (Gaya Tombol 'Lihat semua >' Modern) -->
                <div class="px-3 py-2.5 sm:px-4 sm:py-3.5 border-b border-hairline" style="background:var(--color-canvas);">
                    <div class="flex items-center justify-between gap-2 sm:gap-3">
                        <div class="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1">
                            <div style="width:30px;height:30px;border-radius:8px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="landmark" style="width:15px;height:15px;"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <h3 class="truncate" style="font-size:12px;sm:font-size:13px;font-weight:800;color:var(--color-ink);line-height:1.2;margin:0;flex-shrink:1;">Status Kas &amp; Bank</h3>
                                    <span class="inline-flex items-center justify-center flex-shrink-0 font-mono" style="font-size:9.5px;font-weight:700;padding:1px 5px;border-radius:9999px;background:rgba(16,185,129,0.12);color:#059669;border:1px solid rgba(16,185,129,0.25);line-height:1.2;min-width:18px;"><?= count($cashAccounts) ?></span>
                                </div>
                                <p style="font-size:10.5px;sm:font-size:11px;color:var(--color-ink-mute);margin-top:2px;line-height:1.25;" class="truncate">Posisi likuiditas kas aktif</p>
                            </div>
                        </div>
                        <?php if (Auth::can('cash.view_all')): ?>
                        <a href="<?= Router::url('/cash') ?>" class="btn btn-secondary btn-sm" style="font-size:10.5px;sm:font-size:11px;font-weight:700;padding:3px 8px;border-radius:7px;display:inline-flex;align-items:center;gap:3px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-ink);box-shadow:0 1px 2px rgba(0,0,0,0.04);white-space:nowrap;flex-shrink:0;height:28px;">
                            <span>Lihat semua</span>
                            <i data-lucide="chevron-right" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card Body -->
                <?php if (empty($cashAccounts)): ?>
                <div class="p-6 sm:p-8 text-center flex-1 flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);padding-top:32px;padding-bottom:32px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:rgba(16,185,129,0.1);color:var(--color-success);display:flex;align-items:center;justify-content:center;margin:0 auto 12px auto;flex-shrink:0;">
                        <i data-lucide="wallet" style="width:22px;height:22px;"></i>
                    </div>
                    <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);margin:0 0 4px 0;">Belum Ada Akun Kas Terdaftar</h4>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:290px;margin:0 auto;line-height:1.4;">Silakan buat akun kas baru di menu Buku Kas untuk mencatat transaksi keuangan.</p>
                </div>
                <?php else: ?>
                <div class="table-container overflow-x-auto" style="width:100%;-webkit-overflow-scrolling:touch;">
                    <table class="table w-full text-left" style="margin-bottom:0;border-collapse:collapse;min-width:320px;">
                        <thead>
                            <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Nama Akun Kas</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Tipe</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;text-align:right;">Saldo Saat Ini</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cashAccounts as $acc): ?>
                            <tr class="hover:bg-slate-500/5 transition-colors" style="border-bottom:1px solid var(--color-hairline);">
                                <td style="padding:10px 12px;vertical-align:middle;">
                                    <strong style="color:var(--color-ink);font-size:12.5px;" class="block"><?= htmlspecialchars($acc['nama_akun']) ?></strong>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                                    <?php if (!empty($acc['is_default_pos'])): ?>
                                        <span class="badge badge-success font-semibold text-[9.5px] px-2 py-0.5">Default POS</span>
                                    <?php else: ?>
                                        <span class="badge badge-slate font-semibold text-[9.5px] px-2 py-0.5">Operasional</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;text-align:right;white-space:nowrap;">
                                    <strong class="font-mono font-bold" style="font-size:13px;color:var(--color-ink);"><?= Format::rupiah((float)$acc['saldo_saat_ini']) ?></strong>
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

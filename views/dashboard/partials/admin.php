<?php
/**
 * views/dashboard/partials/admin.php
 * Dasbor Terpersonalisasi Administrator Operasional & Kasir Toko
 * Pola Desain Kanonikal: Sesuai /owner (KEREN SNACK ERP)
 */

use App\Core\Router;
use App\Helpers\Format;

$posStats = $roleData['posStats'] ?? [];
$pendingOrders = $roleData['pendingOrders'] ?? [];
$cashAccounts = $roleData['cashAccounts'] ?? [];
$todayDeliveriesCount = (int)($roleData['todayDeliveriesCount'] ?? 0);
?>

<div class="space-y-5">

    <!-- 1. 4 KPI STAT CARDS (Gaya Kartu Finansial /owner) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- 1. Kasir POS Hari Ini -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid #e11d48;border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">1. Kasir POS Hari Ini</span>
                <i data-lucide="scan-line" style="width:15px;height:15px;color:#e11d48;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-ink);line-height:1.2;">
                <?= (int)($posStats['total_transaksi'] ?? 0) ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">Nota</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span style="color:#e11d48;font-weight:700;"><?= Format::rupiah((float)($posStats['omzet_hari_ini'] ?? 0)) ?></span>
                <span class="badge badge-mono text-[9.5px]">RETAIL</span>
            </div>
        </div>

        <!-- 2. Pesanan Pending -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-warning);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">2. Pesanan Pending</span>
                <i data-lucide="inbox" style="width:15px;height:15px;color:var(--color-warning);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-warning);line-height:1.2;">
                <?= count($pendingOrders) ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">Nota</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Antrean B2B Grosir</span>
                <span class="badge badge-warning font-mono text-[9.5px]">PROSES</span>
            </div>
        </div>

        <!-- 3. Surat Jalan Hari Ini -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-primary);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">3. Surat Jalan</span>
                <i data-lucide="truck" style="width:15px;height:15px;color:var(--color-primary);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-primary);line-height:1.2;">
                <?= $todayDeliveriesCount ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">SJ</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Pengiriman Armada</span>
                <span class="font-mono text-[10px]">Hari Ini</span>
            </div>
        </div>

        <!-- 4. Akun Kas Aktif -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">4. Akun Kas Aktif</span>
                <i data-lucide="wallet" style="width:15px;height:15px;color:var(--color-success);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-success);line-height:1.2;">
                <?= count($cashAccounts) ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">Akun</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Buku Kas &amp; Bank</span>
                <span class="badge badge-success font-mono text-[9.5px]">READY</span>
            </div>
        </div>

    </div>

    <!-- 2. QUICK ACTION LAUNCHPAD -->
    <div class="card p-4 sm:p-5" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3">
                <div style="width:38px;height:38px;border-radius:10px;background:rgba(37,99,235,0.1);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="shield-check" style="width:18px;height:18px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 style="font-size:14.5px;font-weight:800;color:var(--color-ink);">Pusat Operasional Penjualan &amp; Logistik Toko</h2>
                        <span class="badge badge-primary font-mono text-[9.5px]">OPERASIONAL</span>
                    </div>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Akses cepat kasir POS retail, antrean pesanan pelanggan, logistik surat jalan, dan buku kas.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full xl:w-auto">
                <a href="<?= Router::url('/pos') ?>" class="btn btn-primary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="scan-line" style="width:14px;height:14px;"></i>
                    <span>Kasir POS</span>
                </a>
                <a href="<?= Router::url('/customer-orders') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="shopping-bag" style="width:14px;height:14px;"></i>
                    <span>Pesanan Toko</span>
                </a>
                <a href="<?= Router::url('/deliveries') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="truck" style="width:14px;height:14px;"></i>
                    <span>Surat Jalan</span>
                </a>
                <a href="<?= Router::url('/cash') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="wallet" style="width:14px;height:14px;"></i>
                    <span>Buku Kas</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 3. DUA KOLOM: ANTREAN PESANAN & STATUS KAS (Gaya 2-Pane /owner) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 items-stretch">
        
        <!-- KOLOM KIRI: ANTREAN PESANAN PELANGGAN -->
        <div class="card flex flex-col justify-between overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
            <div>
                <div class="p-4 sm:p-5 flex items-center justify-between border-b border-hairline" style="background:var(--color-canvas);">
                    <div class="flex items-center gap-2.5">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="clipboard-list" style="width:17px;height:17px;"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 style="font-size:14px;font-weight:800;color:var(--color-ink);">Antrean Pesanan Pelanggan</h3>
                                <span class="badge badge-mono" style="font-size:10px;"><?= count($pendingOrders) ?></span>
                            </div>
                            <p style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">Pesanan menunggu approval atau siap kirim</p>
                        </div>
                    </div>
                    <a href="<?= Router::url('/customer-orders') ?>" class="btn btn-ghost btn-sm" style="font-size:12px;font-weight:700;color:var(--color-primary);display:inline-flex;align-items:center;gap:4px;">
                        <span>Semua Pesanan</span>
                        <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                    </a>
                </div>

                <?php if (empty($pendingOrders)): ?>
                <div class="p-8 text-center flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);">
                    <div style="width:40px;height:40px;border-radius:50%;background:rgba(16,185,129,0.1);color:var(--color-success);display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;">
                        <i data-lucide="check-circle-2" style="width:20px;height:20px;"></i>
                    </div>
                    <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);">Tidak Ada Antrean Pesanan</h4>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:320px;margin-top:2px;">
                        Seluruh pesanan pelanggan sudah selesai diproses dan siap dikirim.
                    </p>
                    <div class="mt-3">
                        <a href="<?= Router::url('/customer-orders/create') ?>" class="btn btn-secondary btn-sm" style="font-size:11.5px;font-weight:700;display:inline-flex;align-items:center;gap:5px;">
                            <i data-lucide="plus" style="width:13px;height:13px;"></i>
                            <span>Buat Pesanan Baru</span>
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table" style="margin-bottom:0;">
                        <thead>
                            <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">No. Nota</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Pelanggan</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Total Netto</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Status</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingOrders as $ord): 
                                $st = $ord['status_pemrosesan'] ?? 'menunggu_approval';
                            ?>
                            <tr style="border-bottom:1px solid var(--color-hairline);">
                                <td style="padding:12px 16px;">
                                    <strong class="font-mono text-primary" style="font-size:12.5px;"><?= htmlspecialchars($ord['nomor_nota']) ?></strong>
                                    <div style="font-size:11px;color:var(--color-ink-mute);"><?= Format::tanggal($ord['tanggal_pesanan']) ?></div>
                                </td>
                                <td style="padding:12px 16px;">
                                    <strong style="color:var(--color-ink);font-size:13px;"><?= htmlspecialchars($ord['nama_toko']) ?></strong>
                                    <div style="font-size:11px;color:var(--color-ink-mute);"><?= htmlspecialchars($ord['nama_wilayah'] ?? 'Rute Utama') ?></div>
                                </td>
                                <td style="padding:12px 16px;">
                                    <strong class="font-mono font-bold" style="font-size:13px;"><?= Format::rupiah((float)$ord['total_netto']) ?></strong>
                                </td>
                                <td style="padding:12px 16px;">
                                    <span class="badge badge-amber font-semibold" style="font-size:10px;padding:2px 8px;"><?= ucfirst(str_replace('_', ' ', $st)) ?></span>
                                </td>
                                <td style="padding:12px 16px;text-align:right;">
                                    <a href="<?= Router::url('/customer-orders') ?>" class="btn btn-secondary btn-sm" style="font-size:11.5px;font-weight:700;padding:4px 10px;">
                                        <span>Proses</span>
                                        <i data-lucide="arrow-right" style="width:12px;height:12px;"></i>
                                    </a>
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
        <div class="card flex flex-col justify-between overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
            <div>
                <div class="p-4 sm:p-5 flex items-center justify-between border-b border-hairline" style="background:var(--color-canvas);">
                    <div class="flex items-center gap-2.5">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="landmark" style="width:17px;height:17px;"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 style="font-size:14px;font-weight:800;color:var(--color-ink);">Saldo Akun Kas &amp; Bank</h3>
                                <span class="badge badge-mono" style="font-size:10px;"><?= count($cashAccounts) ?></span>
                            </div>
                            <p style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">Posisi likuiditas kas operasional aktif</p>
                        </div>
                    </div>
                    <a href="<?= Router::url('/cash') ?>" class="btn btn-ghost btn-sm" style="font-size:12px;font-weight:700;color:var(--color-primary);display:inline-flex;align-items:center;gap:4px;">
                        <span>Buku Kas</span>
                        <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                    </a>
                </div>

                <?php if (empty($cashAccounts)): ?>
                <div class="p-8 text-center flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);">
                    <div style="width:40px;height:40px;border-radius:50%;background:var(--color-canvas-soft);color:var(--color-ink-mute);display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;">
                        <i data-lucide="wallet" style="width:20px;height:20px;"></i>
                    </div>
                    <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);">Belum Ada Akun Kas Terdaftar</h4>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:320px;margin-top:2px;">Silakan buat akun kas baru di menu Buku Kas.</p>
                </div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table" style="margin-bottom:0;">
                        <thead>
                            <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Nama Akun Kas</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Tipe Akun</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;text-align:right;">Saldo Saat Ini</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cashAccounts as $acc): ?>
                            <tr style="border-bottom:1px solid var(--color-hairline);">
                                <td style="padding:12px 16px;">
                                    <strong style="color:var(--color-ink);font-size:13px;"><?= htmlspecialchars($acc['nama_akun']) ?></strong>
                                </td>
                                <td style="padding:12px 16px;">
                                    <?php if (!empty($acc['is_default_pos'])): ?>
                                        <span class="badge badge-success font-semibold" style="font-size:10px;padding:2px 8px;">Default POS</span>
                                    <?php else: ?>
                                        <span class="badge badge-slate font-semibold" style="font-size:10px;padding:2px 8px;">Operasional</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:12px 16px;text-align:right;">
                                    <strong class="font-mono font-bold" style="font-size:13.5px;color:var(--color-ink);"><?= Format::rupiah((float)$acc['saldo_saat_ini']) ?></strong>
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

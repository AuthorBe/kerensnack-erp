<?php
/**
 * views/dashboard/partials/mandor.php
 * Dasbor Terpersonalisasi Mandor Produksi & Pengawas Gudang
 * Pola Desain Kanonikal: Sesuai /owner (KEREN SNACK ERP)
 */

use App\Core\Router;
use App\Core\Auth;
use App\Helpers\Format;

$criticalStock = $roleData['criticalStock'] ?? [];
$pendingPurchases = $roleData['pendingPurchases'] ?? [];
$boronganGroups = $roleData['boronganGroups'] ?? [];
$stats = $roleData['stats'] ?? [];
?>

<div class="space-y-4 sm:space-y-5">

    <!-- 1. 4 KPI STAT CARDS (Gaya Kartu Finansial /owner) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- 1. Stok Kritis / Menipis -->
        <div class="card p-3.5 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-danger);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">1. Stok Kritis</span>
                <i data-lucide="alert-octagon" style="width:14px;height:14px;color:var(--color-danger);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-danger);line-height:1.2;">
                <?= (int)($stats['stok_kritis_count'] ?? 0) ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">Item</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Batas &le; 50 unit</span>
                <span class="badge badge-danger font-mono text-[9px] px-1.5 py-0 flex-shrink-0">ALERT</span>
            </div>
        </div>

        <!-- 2. PO Masuk Vendor -->
        <div class="card p-3.5 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-warning);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">2. PO Vendor</span>
                <i data-lucide="truck" style="width:14px;height:14px;color:var(--color-warning);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-warning);line-height:1.2;">
                <?= (int)($stats['po_pending_count'] ?? 0) ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">PO</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Menunggu Fisik</span>
                <span class="badge badge-warning font-mono text-[9px] px-1.5 py-0 flex-shrink-0">DELIVERY</span>
            </div>
        </div>

        <!-- 3. Kelompok Borongan -->
        <div class="card p-3.5 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-primary);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">3. Upah Borongan</span>
                <i data-lucide="users" style="width:14px;height:14px;color:var(--color-primary);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-primary);line-height:1.2;">
                <?= (int)($stats['borongan_group_count'] ?? 0) ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">Grup</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Tenaga Kerja</span>
                <span class="badge badge-mono text-[9px] px-1.5 py-0 flex-shrink-0">BORONGAN</span>
            </div>
        </div>

        <!-- 4. Status Gudang -->
        <div class="card p-3.5 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">4. Gudang Fisik</span>
                <i data-lucide="boxes" style="width:14px;height:14px;color:var(--color-success);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-success);line-height:1.2;">
                Online
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Opname &amp; Mutasi</span>
                <span class="badge badge-success font-mono text-[9px] px-1.5 py-0 flex-shrink-0">READY</span>
            </div>
        </div>

    </div>

    <!-- 2. QUICK ACTION LAUNCHPAD -->
    <div class="card p-3.5 sm:p-5" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 sm:gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="factory" style="width:18px;height:18px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 style="font-size:14px;sm:font-size:14.5px;font-weight:800;color:var(--color-ink);line-height:1.3;margin:0;">Pusat Pengawasan Pabrik &amp; Gudang</h2>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.35;">Kelola penerimaan barang dari vendor, opname stok gudang, dan konfigurasi resep BOM serta borongan.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full xl:w-auto">
                <?php if (Auth::can('inventory.view_all')): ?>
                <a href="<?= Router::url('/inventory') ?>" class="btn btn-primary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="boxes" style="width:14px;height:14px;"></i>
                    <span>Stok Gudang</span>
                </a>
                <?php endif; ?>
                <?php if (Auth::can(['purchases.view', 'purchases.create', 'purchases.receive'])): ?>
                <a href="<?= Router::url('/purchases') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="truck" style="width:14px;height:14px;"></i>
                    <span>Penerimaan PO</span>
                </a>
                <?php endif; ?>
                <?php if (Auth::can(['master.products_view', 'master.products_manage', 'production.bom_manage'])): ?>
                <a href="<?= Router::url('/products') ?>" class="btn btn-secondary btn-sm justify-center col-span-2 sm:col-span-1" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="chef-hat" style="width:14px;height:14px;"></i>
                    <span>Resep &amp; Borongan</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 3. DUA KOLOM: STOK KRITIS & PO PEMBELIAN (Gaya 2-Pane /owner) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 items-stretch">
        
        <!-- KOLOM KIRI: STOK BAHAN BAKU & BARANG KRITIS -->
        <div class="card h-full flex flex-col justify-between overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
            <div class="flex-1 flex flex-col">
                <!-- Card Header (Gaya Tombol 'Lihat semua >' Modern) -->
                <div class="px-3 py-2.5 sm:px-4 sm:py-3.5 border-b border-hairline" style="background:var(--color-canvas);">
                    <div class="flex items-center justify-between gap-2 sm:gap-3">
                        <div class="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1">
                            <div style="width:30px;height:30px;border-radius:8px;background:rgba(239,68,68,0.12);color:var(--color-danger);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="alert-triangle" style="width:15px;height:15px;"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <h3 class="truncate" style="font-size:12px;sm:font-size:13px;font-weight:800;color:var(--color-ink);line-height:1.2;margin:0;flex-shrink:1;">Stok Kritis</h3>
                                    <span class="inline-flex items-center justify-center flex-shrink-0 font-mono" style="font-size:9.5px;font-weight:700;padding:1px 5px;border-radius:9999px;background:rgba(239,68,68,0.12);color:var(--color-danger);border:1px solid rgba(239,68,68,0.25);line-height:1.2;min-width:18px;"><?= count($criticalStock) ?></span>
                                </div>
                                <p style="font-size:10.5px;sm:font-size:11px;color:var(--color-ink-mute);margin-top:2px;line-height:1.25;" class="truncate">Item di bawah batas &le; 50 unit</p>
                            </div>
                        </div>
                        <?php if (Auth::can('inventory.view_all')): ?>
                        <a href="<?= Router::url('/inventory') ?>" class="btn btn-secondary btn-sm" style="font-size:10.5px;sm:font-size:11px;font-weight:700;padding:3px 8px;border-radius:7px;display:inline-flex;align-items:center;gap:3px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-ink);box-shadow:0 1px 2px rgba(0,0,0,0.04);white-space:nowrap;flex-shrink:0;height:28px;">
                            <span>Lihat semua</span>
                            <i data-lucide="chevron-right" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card Body -->
                <?php if (empty($criticalStock)): ?>
                <div class="p-6 sm:p-8 text-center flex-1 flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);padding-top:32px;padding-bottom:32px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:rgba(16,185,129,0.1);color:var(--color-success);display:flex;align-items:center;justify-content:center;margin:0 auto 12px auto;flex-shrink:0;">
                        <i data-lucide="check-circle" style="width:22px;height:22px;"></i>
                    </div>
                    <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);margin:0 0 4px 0;">Seluruh Stok Dalam Kondisi Aman</h4>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:290px;margin:0 auto;line-height:1.4;">Tidak ada bahan baku atau barang jadi yang berada di bawah batas minimum.</p>
                </div>
                <?php else: ?>
                <div class="table-container overflow-x-auto" style="width:100%;-webkit-overflow-scrolling:touch;">
                    <table class="table w-full text-left" style="margin-bottom:0;border-collapse:collapse;min-width:460px;">
                        <thead>
                            <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">SKU / Barang</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Kategori</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Sisa Stok</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($criticalStock as $item): ?>
                            <tr class="hover:bg-slate-500/5 transition-colors" style="border-bottom:1px solid var(--color-hairline);">
                                <td style="padding:10px 12px;vertical-align:middle;">
                                    <strong style="color:var(--color-ink);font-size:12.5px;" class="block truncate max-w-[140px]"><?= htmlspecialchars($item['nama_item']) ?></strong>
                                    <div class="font-mono" style="font-size:10.5px;color:var(--color-ink-mute);"><?= htmlspecialchars($item['kode_sku']) ?></div>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                                    <span class="badge badge-slate font-semibold text-[9.5px] px-2 py-0.5"><?= htmlspecialchars($item['nama_grup'] ?? ucfirst($item['tipe_item'])) ?></span>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                                    <strong class="font-mono font-bold text-[13px]" style="color:var(--color-danger);"><?= (int)$item['stok_fisik_saat_ini'] ?></strong>
                                    <span style="font-size:10.5px;color:var(--color-ink-mute);"><?= htmlspecialchars($item['satuan_dasar'] ?? 'pcs') ?></span>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;text-align:right;">
                                    <?php if (Auth::can('inventory.view_all')): ?>
                                    <a href="<?= Router::url('/inventory') ?>" class="btn btn-secondary btn-sm" style="font-size:11px;font-weight:700;padding:3px 8px;">
                                        <i data-lucide="sliders-horizontal" style="width:11px;height:11px;"></i>
                                        <span>Atur</span>
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

        <!-- KOLOM KANAN: PO PEMBELIAN VENDOR MENUNGGU DITERIMA -->
        <div class="card h-full flex flex-col justify-between overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
            <div class="flex-1 flex flex-col">
                <!-- Card Header (Gaya Tombol 'Lihat semua >' Modern) -->
                <div class="px-3 py-2.5 sm:px-4 sm:py-3.5 border-b border-hairline" style="background:var(--color-canvas);">
                    <div class="flex items-center justify-between gap-2 sm:gap-3">
                        <div class="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1">
                            <div style="width:30px;height:30px;border-radius:8px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="package-search" style="width:15px;height:15px;"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <h3 class="truncate" style="font-size:12px;sm:font-size:13px;font-weight:800;color:var(--color-ink);line-height:1.2;margin:0;flex-shrink:1;">PO Vendor</h3>
                                    <span class="inline-flex items-center justify-center flex-shrink-0 font-mono" style="font-size:9.5px;font-weight:700;padding:1px 5px;border-radius:9999px;background:rgba(245,158,11,0.12);color:#d97706;border:1px solid rgba(245,158,11,0.25);line-height:1.2;min-width:18px;"><?= count($pendingPurchases) ?></span>
                                </div>
                                <p style="font-size:10.5px;sm:font-size:11px;color:var(--color-ink-mute);margin-top:2px;line-height:1.25;" class="truncate">Pesanan barang masuk menunggu terima</p>
                            </div>
                        </div>
                        <?php if (Auth::can(['purchases.view', 'purchases.create', 'purchases.receive'])): ?>
                        <a href="<?= Router::url('/purchases') ?>" class="btn btn-secondary btn-sm" style="font-size:10.5px;sm:font-size:11px;font-weight:700;padding:3px 8px;border-radius:7px;display:inline-flex;align-items:center;gap:3px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-ink);box-shadow:0 1px 2px rgba(0,0,0,0.04);white-space:nowrap;flex-shrink:0;height:28px;">
                            <span>Lihat semua</span>
                            <i data-lucide="chevron-right" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card Body -->
                <?php if (empty($pendingPurchases)): ?>
                <div class="p-6 sm:p-8 text-center flex-1 flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);padding-top:32px;padding-bottom:32px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:rgba(245,158,11,0.1);color:var(--color-warning);display:flex;align-items:center;justify-content:center;margin:0 auto 12px auto;flex-shrink:0;">
                        <i data-lucide="package" style="width:22px;height:22px;"></i>
                    </div>
                    <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);margin:0 0 4px 0;">Tidak Ada PO Menunggu</h4>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:290px;margin:0 auto;line-height:1.4;">Seluruh pesanan barang masuk dari vendor sudah diterima ke stok gudang.</p>
                </div>
                <?php else: ?>
                <div class="table-container overflow-x-auto" style="width:100%;-webkit-overflow-scrolling:touch;">
                    <table class="table w-full text-left" style="margin-bottom:0;border-collapse:collapse;min-width:440px;">
                        <thead>
                            <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">No. PO</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Vendor</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Total</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingPurchases as $po): ?>
                            <tr class="hover:bg-slate-500/5 transition-colors" style="border-bottom:1px solid var(--color-hairline);">
                                <td style="padding:10px 12px;vertical-align:middle;">
                                    <strong class="font-mono" style="font-size:12px;color:var(--color-primary);"><?= htmlspecialchars($po['nomor_pembelian']) ?></strong>
                                    <div style="font-size:10.5px;color:var(--color-ink-mute);"><?= Format::tanggal($po['tanggal_pembelian']) ?></div>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;">
                                    <strong style="color:var(--color-ink);font-size:12.5px;" class="block truncate max-w-[140px]"><?= htmlspecialchars($po['nama_pemasok']) ?></strong>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                                    <strong class="font-mono font-bold text-[12.5px]" style="color:var(--color-ink);"><?= Format::rupiah((float)$po['total_akhir']) ?></strong>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;text-align:right;">
                                    <?php if (Auth::can(['purchases.receive', 'purchases.edit', 'purchases.view'])): ?>
                                    <a href="<?= Router::url('/purchases') ?>" class="btn btn-secondary btn-sm" style="font-size:11px;font-weight:700;padding:3px 8px;">
                                        <i data-lucide="check-square" style="width:11px;height:11px;"></i>
                                        <span>Terima</span>
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

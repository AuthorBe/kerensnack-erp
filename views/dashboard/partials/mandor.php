<?php
/**
 * views/dashboard/partials/mandor.php
 * Dasbor Terpersonalisasi Mandor Produksi & Pengawas Gudang
 * Pola Desain Kanonikal: Sesuai /owner (KEREN SNACK ERP)
 */

use App\Core\Router;
use App\Helpers\Format;

$criticalStock = $roleData['criticalStock'] ?? [];
$pendingPurchases = $roleData['pendingPurchases'] ?? [];
$boronganGroups = $roleData['boronganGroups'] ?? [];
$stats = $roleData['stats'] ?? [];
?>

<div class="space-y-5">

    <!-- 1. 4 KPI STAT CARDS (Gaya Kartu Finansial /owner) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- 1. Stok Kritis / Menipis -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-danger);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">1. Stok Kritis</span>
                <i data-lucide="alert-octagon" style="width:15px;height:15px;color:var(--color-danger);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-danger);line-height:1.2;">
                <?= (int)($stats['stok_kritis_count'] ?? 0) ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">Item</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Batas &le; 50 pcs / unit</span>
                <span class="badge badge-danger font-mono text-[9.5px]">ALERT</span>
            </div>
        </div>

        <!-- 2. PO Masuk Vendor -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-warning);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">2. PO Vendor</span>
                <i data-lucide="truck" style="width:15px;height:15px;color:var(--color-warning);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-warning);line-height:1.2;">
                <?= (int)($stats['po_pending_count'] ?? 0) ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">PO</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Menunggu Fisik</span>
                <span class="badge badge-warning font-mono text-[9.5px]">DELIVERY</span>
            </div>
        </div>

        <!-- 3. Kelompok Borongan -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-primary);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">3. Upah Borongan</span>
                <i data-lucide="users" style="width:15px;height:15px;color:var(--color-primary);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-primary);line-height:1.2;">
                <?= (int)($stats['borongan_group_count'] ?? 0) ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">Grup</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Tenaga Kerja Pabrik</span>
                <span class="badge badge-mono text-[9.5px]">BORONGAN</span>
            </div>
        </div>

        <!-- 4. Status Gudang -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">4. Katalog Gudang</span>
                <i data-lucide="boxes" style="width:15px;height:15px;color:var(--color-success);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-success);line-height:1.2;">
                Online
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Opname &amp; Mutasi Stok</span>
                <span class="badge badge-success font-mono text-[9.5px]">READY</span>
            </div>
        </div>

    </div>

    <!-- 2. QUICK ACTION LAUNCHPAD -->
    <div class="card p-4 sm:p-5" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3">
                <div style="width:38px;height:38px;border-radius:10px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="factory" style="width:18px;height:18px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 style="font-size:14.5px;font-weight:800;color:var(--color-ink);">Pusat Pengawasan Pabrik &amp; Inventori Gudang</h2>
                        <span class="badge badge-success font-mono text-[9.5px]">PRODUKSI</span>
                    </div>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Kelola penerimaan barang dari vendor, opname stok fisik gudang, dan konfigurasi resep BOM serta borongan.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:flex sm:items-center gap-2 w-full xl:w-auto">
                <a href="<?= Router::url('/inventory') ?>" class="btn btn-primary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="boxes" style="width:14px;height:14px;"></i>
                    <span>Stok Gudang</span>
                </a>
                <a href="<?= Router::url('/purchases') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="truck" style="width:14px;height:14px;"></i>
                    <span>Penerimaan PO</span>
                </a>
                <a href="<?= Router::url('/products') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="chef-hat" style="width:14px;height:14px;"></i>
                    <span>Resep &amp; Borongan</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 3. DUA KOLOM: STOK KRITIS & PO PEMBELIAN (Gaya 2-Pane /owner) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 items-stretch">
        
        <!-- KOLOM KIRI: STOK BAHAN BAKU & BARANG KRITIS -->
        <div class="card flex flex-col justify-between overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
            <div>
                <div class="p-4 sm:p-5 flex items-center justify-between border-b border-hairline" style="background:var(--color-canvas);">
                    <div class="flex items-center gap-2.5">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(239,68,68,0.12);color:var(--color-danger);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="alert-triangle" style="width:17px;height:17px;"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 style="font-size:14px;font-weight:800;color:var(--color-ink);">Stok Kritis / Perlu Restock</h3>
                                <span class="badge badge-mono" style="font-size:10px;"><?= count($criticalStock) ?></span>
                            </div>
                            <p style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">Barang jadi &amp; bahan baku di bawah batas minimum (&le; 50 unit)</p>
                        </div>
                    </div>
                    <a href="<?= Router::url('/inventory') ?>" class="btn btn-ghost btn-sm" style="font-size:12px;font-weight:700;color:var(--color-primary);display:inline-flex;align-items:center;gap:4px;">
                        <span>Opname Gudang</span>
                        <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                    </a>
                </div>

                <?php if (empty($criticalStock)): ?>
                <div class="p-8 text-center flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);">
                    <div style="width:40px;height:40px;border-radius:50%;background:rgba(16,185,129,0.1);color:var(--color-success);display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;">
                        <i data-lucide="check-circle" style="width:20px;height:20px;"></i>
                    </div>
                    <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);">Seluruh Stok Dalam Kondisi Aman</h4>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:320px;margin-top:2px;">Tidak ada bahan baku atau barang jadi yang berada di bawah batas minimum.</p>
                </div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table" style="margin-bottom:0;">
                        <thead>
                            <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">SKU / Nama Barang</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Kategori</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Sisa Stok Fisik</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($criticalStock as $item): ?>
                            <tr style="border-bottom:1px solid var(--color-hairline);">
                                <td style="padding:12px 16px;">
                                    <strong style="color:var(--color-ink);font-size:13px;"><?= htmlspecialchars($item['nama_item']) ?></strong>
                                    <div class="font-mono" style="font-size:11px;color:var(--color-ink-mute);"><?= htmlspecialchars($item['kode_sku']) ?></div>
                                </td>
                                <td style="padding:12px 16px;">
                                    <span class="badge badge-slate font-semibold" style="font-size:10px;padding:2px 8px;"><?= htmlspecialchars($item['nama_grup'] ?? ucfirst($item['tipe_item'])) ?></span>
                                </td>
                                <td style="padding:12px 16px;">
                                    <strong class="font-mono text-danger font-bold" style="font-size:13.5px;"><?= (int)$item['stok_fisik_saat_ini'] ?></strong>
                                    <span style="font-size:11px;color:var(--color-ink-mute);"><?= htmlspecialchars($item['satuan_dasar'] ?? 'pcs') ?></span>
                                </td>
                                <td style="padding:12px 16px;text-align:right;">
                                    <a href="<?= Router::url('/inventory') ?>" class="btn btn-secondary btn-sm" style="font-size:11.5px;font-weight:700;padding:4px 10px;">
                                        <i data-lucide="sliders-horizontal" style="width:12px;height:12px;"></i>
                                        <span>Sesuaikan</span>
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

        <!-- KOLOM KANAN: PO PEMBELIAN VENDOR MENUNGGU DITERIMA -->
        <div class="card flex flex-col justify-between overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
            <div>
                <div class="p-4 sm:p-5 flex items-center justify-between border-b border-hairline" style="background:var(--color-canvas);">
                    <div class="flex items-center gap-2.5">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="package-search" style="width:17px;height:17px;"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 style="font-size:14px;font-weight:800;color:var(--color-ink);">PO Masuk dari Vendor</h3>
                                <span class="badge badge-mono" style="font-size:10px;"><?= count($pendingPurchases) ?></span>
                            </div>
                            <p style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">Pesanan pembelian barang/bahan menunggu penerimaan fisik</p>
                        </div>
                    </div>
                    <a href="<?= Router::url('/purchases') ?>" class="btn btn-ghost btn-sm" style="font-size:12px;font-weight:700;color:var(--color-primary);display:inline-flex;align-items:center;gap:4px;">
                        <span>Daftar PO</span>
                        <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                    </a>
                </div>

                <?php if (empty($pendingPurchases)): ?>
                <div class="p-8 text-center flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);">
                    <div style="width:40px;height:40px;border-radius:50%;background:var(--color-canvas-soft);color:var(--color-ink-mute);display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;">
                        <i data-lucide="package" style="width:20px;height:20px;"></i>
                    </div>
                    <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);">Tidak Ada PO Menunggu</h4>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:320px;margin-top:2px;">Seluruh pesanan barang masuk dari vendor sudah diterima dan diproses ke stok gudang.</p>
                </div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table" style="margin-bottom:0;">
                        <thead>
                            <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">No. PO</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Vendor Pemasok</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Total Nilai</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingPurchases as $po): ?>
                            <tr style="border-bottom:1px solid var(--color-hairline);">
                                <td style="padding:12px 16px;">
                                    <strong class="font-mono text-primary" style="font-size:12.5px;"><?= htmlspecialchars($po['nomor_pembelian']) ?></strong>
                                    <div style="font-size:11px;color:var(--color-ink-mute);"><?= Format::tanggal($po['tanggal_pembelian']) ?></div>
                                </td>
                                <td style="padding:12px 16px;">
                                    <strong style="color:var(--color-ink);font-size:13px;"><?= htmlspecialchars($po['nama_pemasok']) ?></strong>
                                </td>
                                <td style="padding:12px 16px;">
                                    <strong class="font-mono font-bold" style="font-size:13px;"><?= Format::rupiah((float)$po['total_akhir']) ?></strong>
                                </td>
                                <td style="padding:12px 16px;text-align:right;">
                                    <a href="<?= Router::url('/purchases') ?>" class="btn btn-secondary btn-sm" style="font-size:11.5px;font-weight:700;padding:4px 10px;">
                                        <i data-lucide="check-square" style="width:12px;height:12px;"></i>
                                        <span>Terima</span>
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

    </div>

</div>

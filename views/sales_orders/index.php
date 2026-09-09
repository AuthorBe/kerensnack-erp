<?php
use App\Core\Router;
use App\Helpers\Format;
ob_start();
?>

<div class="space-y-6" x-data="salesOrderListApp()">

    <!-- ========================================================================= -->
    <!-- HEADER ACTION BAR                                                         -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-emerald">
                <i data-lucide="shopping-bag"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot"></span>
                    <span>Modul Penjualan</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Penjualan Toko' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Daftar Transaksi & Faktur Mitra Toko' ?></p>
            </div>
        </div>
        <div class="page-header-actions">
            <a href="<?= Router::url('/sales-orders/create') ?>" class="btn btn-primary" style="font-weight:700;">
                <i data-lucide="plus"></i>
                <span>Tambah Penjualan Baru</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4 RINGKASAN METRIK TRANSAKSI                                              -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Omset -->
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="stat-label">Total Omset Penjualan</div>
                    <div class="stat-value text-primary" style="font-size:20px;"><?= Format::rupiah($metrics['total_omset']) ?></div>
                </div>
                <div style="width:40px;height:40px;border-radius:var(--rounded-md);background:rgba(16,185,129,0.1);color:#10b981;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="trending-up" style="width:20px;height:20px;"></i>
                </div>
            </div>
            <div class="stat-helper">Akumulasi periode terpilih</div>
        </div>

        <!-- 2. Piutang Toko Berjalan -->
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="stat-label">Total Piutang Toko</div>
                    <div class="stat-value" style="color:#ef4444;font-size:20px;"><?= Format::rupiah($metrics['total_piutang']) ?></div>
                </div>
                <div style="width:40px;height:40px;border-radius:var(--rounded-md);background:rgba(239,68,68,0.1);color:#ef4444;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="clock" style="width:20px;height:20px;"></i>
                </div>
            </div>
            <div class="stat-helper">Tagihan tempo belum lunas</div>
        </div>

        <!-- 3. Total Faktur -->
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="stat-label">Faktur Terbit</div>
                    <div class="stat-value" style="font-size:20px;"><?= $metrics['count_total'] ?> <span style="font-size:13px;font-weight:600;color:var(--color-ink-mute);">Nota</span></div>
                </div>
                <div style="width:40px;height:40px;border-radius:var(--rounded-md);background:rgba(59,130,246,0.1);color:#3b82f6;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="file-text" style="width:20px;height:20px;"></i>
                </div>
            </div>
            <div class="stat-helper">Total transaksi toko</div>
        </div>

        <!-- 4. Lunas -->
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="stat-label">Faktur Lunas</div>
                    <div class="stat-value" style="color:#10b981;font-size:20px;"><?= $metrics['count_lunas'] ?> <span style="font-size:13px;font-weight:600;color:var(--color-ink-mute);">/ <?= $metrics['count_total'] ?></span></div>
                </div>
                <div style="width:40px;height:40px;border-radius:var(--rounded-md);background:rgba(16,185,129,0.1);color:#10b981;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="check-circle" style="width:20px;height:20px;"></i>
                </div>
            </div>
            <div class="stat-helper">
                <?= $metrics['count_total'] > 0 ? round(($metrics['count_lunas'] / $metrics['count_total']) * 100) : 0 ?>% pembayaran lunas
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- FILTER BAR (IPOS STYLE)                                                   -->
    <!-- ========================================================================= -->
    <div class="card p-4">
        <form action="<?= Router::url('/sales-orders') ?>" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
            <div>
                <label class="form-label" style="font-size:11px;">Dari Tanggal</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($filter['start_date']) ?>" class="form-input font-mono" style="height:36px;font-size:12.5px;">
            </div>

            <div>
                <label class="form-label" style="font-size:11px;">Sampai Tanggal</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($filter['end_date']) ?>" class="form-input font-mono" style="height:36px;font-size:12.5px;">
            </div>

            <div>
                <label class="form-label" style="font-size:11px;">Toko Pelanggan</label>
                <select name="pelanggan_id" class="form-input searchable-select" style="height:36px;font-size:12px;">
                    <option value="">-- Semua Toko Pelanggan --</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filter['pelanggan_id'] === $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nama_toko']) ?><?= !empty($c['kode_pelanggan']) ? ' (' . htmlspecialchars($c['kode_pelanggan']) . ')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label" style="font-size:11px;">Sales / Driver</label>
                <select name="sales_driver_id" class="form-input" style="height:36px;font-size:12px;">
                    <option value="">-- Semua Sales --</option>
                    <?php foreach ($drivers as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $filter['sales_driver_id'] === $d['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['nama_karyawan']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label" style="font-size:11px;">Status Bayar</label>
                <select name="status_pembayaran" class="form-input" style="height:36px;font-size:12px;">
                    <option value="semua" <?= $filter['status_pembayaran'] === 'semua' ? 'selected' : '' ?>>-- Semua Status --</option>
                    <option value="lunas" <?= $filter['status_pembayaran'] === 'lunas' ? 'selected' : '' ?>>Lunas</option>
                    <option value="belum_lunas" <?= $filter['status_pembayaran'] === 'belum_lunas' ? 'selected' : '' ?>>Tempo / Belum Lunas</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary flex-1" style="height:36px;font-size:12.5px;">
                    <i data-lucide="filter" style="width:14px;height:14px;"></i>
                    <span>Filter</span>
                </button>
                <a href="<?= Router::url('/sales-orders') ?>" class="btn btn-secondary" style="height:36px;padding:0 12px;" title="Reset Filter">
                    <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- TABEL DAFTAR PENJUALAN TOKO                                               -->
    <!-- ========================================================================= -->
    <div class="card p-0">
        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th class="cell-center" style="width:50px;">No</th>
                        <th class="cell-nowrap">No. Transaksi</th>
                        <th class="cell-nowrap">Tanggal</th>
                        <th>Toko Pelanggan</th>
                        <th>Sales / Driver</th>
                        <th class="cell-nowrap">Pembayaran &amp; Tempo</th>
                        <th class="cell-right cell-nowrap">Total Netto</th>
                        <th class="cell-right cell-nowrap">Dibayar / Sisa</th>
                        <th class="cell-center cell-nowrap">Status</th>
                        <th class="cell-center cell-nowrap" style="width:140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="10" style="padding: 48px 20px; text-align: center;">
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(148, 163, 184, 0.1); color: var(--color-ink-mute); display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                                    <i data-lucide="file-x" style="width: 28px; height: 28px;"></i>
                                </div>
                                <div style="font-size: 15px; font-weight: 700; color: var(--color-ink);">Belum Ada Transaksi Penjualan Toko</div>
                                <div style="font-size: 13px; color: var(--color-ink-mute); margin-top: 4px; margin-bottom: 16px;">Belum ada faktur penjualan yang sesuai dengan filter pencarian.</div>
                                <a href="<?= Router::url('/sales-orders/create') ?>" class="btn btn-primary btn-sm">
                                    <i data-lucide="plus"></i>
                                    <span>Input Penjualan Toko Baru</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($orders as $idx => $o): 
                        $isLunas = ($o['status_pembayaran'] === 'lunas');
                        $sisa = max(0, (float)$o['total_netto'] - (float)$o['total_dibayar']);
                    ?>
                    <tr>
                        <!-- No -->
                        <td class="cell-center cell-nowrap" style="color:var(--color-ink-mute);font-size:12px;">
                            <?= $idx + 1 ?>
                        </td>

                        <!-- No Transaksi -->
                        <td class="cell-nowrap">
                            <a href="<?= Router::url('/sales-orders/invoice?id=' . $o['id']) ?>" target="_blank" class="font-mono font-bold" style="color:var(--color-primary-deep);display:flex;align-items:center;gap:6px;">
                                <span><?= htmlspecialchars($o['nomor_nota']) ?></span>
                                <i data-lucide="external-link" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                            </a>
                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">
                                <?= $o['total_sku_items'] ?> SKU (<?= number_format($o['total_pcs_items'], 0, ',', '.') ?> bungkus)
                            </div>
                        </td>

                        <!-- Tanggal -->
                        <td class="cell-nowrap">
                            <div class="font-mono" style="font-size:12.5px;font-weight:600;">
                                <?= date('d/m/Y', strtotime($o['tanggal_pesanan'])) ?>
                            </div>
                            <div style="font-size:11px;color:var(--color-ink-mute);">
                                <?= date('H:i', strtotime($o['dibuat_pada'])) ?> WIB
                            </div>
                        </td>

                        <!-- Toko Pelanggan -->
                        <td>
                            <div style="font-weight:800;font-size:13px;color:var(--color-ink);">
                                <?= htmlspecialchars($o['nama_toko']) ?>
                            </div>
                            <div style="font-size:11px;color:var(--color-ink-mute);">
                                Kode: <span class="font-mono"><?= htmlspecialchars($o['kode_pelanggan']) ?></span>
                                <?php if (!empty($o['nomor_whatsapp'])): ?>
                                • WA: <?= htmlspecialchars($o['nomor_whatsapp']) ?>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Sales / Driver & Pengiriman -->
                        <td>
                            <?php if (!empty($o['nama_sales'])): ?>
                            <div style="font-weight:700;font-size:12.5px;color:var(--color-ink);display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                                <span>🚚 <?= htmlspecialchars($o['nama_sales']) ?></span>
                                <?php if (!empty($o['nopol_driver'])): ?>
                                <span class="badge badge-mono" style="font-size:10px;padding:1px 5px;"><?= htmlspecialchars($o['nopol_driver']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);">Belum ada driver</div>
                            <?php endif; ?>

                            <!-- Status Pengiriman / Surat Jalan Interaktif -->
                            <div style="margin-top:4px;">
                                <?php 
                                $statusSj = $o['status_surat_jalan'] ?? 'disetujui_owner';
                                if ($statusSj === 'selesai_diterima'): ?>
                                <button type="button" @click="openDeliveryModal(<?= htmlspecialchars(json_encode($o)) ?>)" class="badge badge-success" style="font-size:10px;cursor:pointer;border:none;" title="Klik untuk ubah status pengiriman">
                                    ✅ Selesai Diterima
                                </button>
                                <?php elseif ($statusSj === 'sedang_dikirim'): ?>
                                <button type="button" @click="openDeliveryModal(<?= htmlspecialchars(json_encode($o)) ?>)" class="badge badge-info" style="font-size:10px;cursor:pointer;border:none;" title="Klik untuk ubah status pengiriman">
                                    🚚 Sedang Dikirim
                                </button>
                                <?php elseif ($statusSj === 'gagal_kembali'): ?>
                                <button type="button" @click="openDeliveryModal(<?= htmlspecialchars(json_encode($o)) ?>)" class="badge badge-danger" style="font-size:10px;cursor:pointer;border:none;" title="Klik untuk ubah status pengiriman">
                                    ⚠️ Retur / Batal
                                </button>
                                <?php else: ?>
                                <button type="button" @click="openDeliveryModal(<?= htmlspecialchars(json_encode($o)) ?>)" class="badge badge-warning" style="font-size:10px;cursor:pointer;border:none;" title="Klik untuk ubah status pengiriman">
                                    📦 Siap Kirim
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Pembayaran & Tempo -->
                        <td class="cell-nowrap">
                            <div style="font-size:12px;font-weight:700;text-transform:capitalize;">
                                <?php if ($o['tipe_pembayaran'] === 'cash'): ?>
                                <span style="color:#10b981;">💵 Tunai</span>
                                <?php elseif ($o['tipe_pembayaran'] === 'konsinyasi'): ?>
                                <span style="color:#f59e0b;">🏪 Titip Jual (Konsinyasi)</span>
                                <?php else: ?>
                                <span style="color:#ef4444;">⏱️ <?= str_replace('_', ' ', $o['tipe_pembayaran']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($o['tanggal_jatuh_tempo']) && !$isLunas): ?>
                            <div style="font-size:11px;color:#ef4444;margin-top:2px;">
                                Jatuh Tempo: <strong><?= date('d/m/Y', strtotime($o['tanggal_jatuh_tempo'])) ?></strong>
                            </div>
                            <?php endif; ?>
                        </td>

                        <!-- Total Netto -->
                        <td class="cell-right cell-nowrap">
                            <div class="font-mono font-bold" style="font-size:13.5px;color:var(--color-ink);">
                                <?= Format::rupiah($o['total_netto']) ?>
                            </div>
                            <?php if ((float)$o['total_diskon'] > 0): ?>
                            <div style="font-size:10.5px;color:#10b981;">
                                Disc: -<?= Format::rupiah($o['total_diskon']) ?>
                            </div>
                            <?php endif; ?>
                        </td>

                        <!-- Dibayar / Sisa -->
                        <td class="cell-right cell-nowrap">
                            <?php if ($isLunas): ?>
                            <div class="font-mono text-success" style="font-size:12.5px;font-weight:700;">
                                Lunas (<?= Format::rupiah($o['total_netto']) ?>)
                            </div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);">
                                <?= htmlspecialchars($o['nama_akun_kas'] ?: 'Kas') ?>
                            </div>
                            <?php else: ?>
                            <div class="font-mono" style="font-size:12.5px;font-weight:700;color:#ef4444;">
                                Sisa: <?= Format::rupiah($sisa) ?>
                            </div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);">
                                Terbayar: <?= Format::rupiah($o['total_dibayar']) ?>
                            </div>
                            <?php endif; ?>
                        </td>

                        <!-- Status -->
                        <td class="cell-center cell-nowrap">
                            <?php if ($isLunas): ?>
                            <span class="badge badge-success" style="font-weight:800;">LUNAS</span>
                            <?php else: ?>
                            <span class="badge badge-warning" style="font-weight:800;color:#ef4444;background:rgba(239,68,68,0.1);border-color:rgba(239,68,68,0.2);">
                                BELUM LUNAS
                            </span>
                            <?php endif; ?>
                        </td>

                        <!-- Aksi -->
                        <td class="cell-center cell-nowrap">
                            <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
                                <!-- Cetak Dokumen (Mini Hover / Dropdown Pilihan) -->
                                <div class="relative inline-block text-left" x-data="{ openPrint: false }" @mouseenter="openPrint = true" @mouseleave="openPrint = false">
                                    <button type="button" @click="openPrint = !openPrint" class="btn btn-secondary btn-sm" style="padding:4px 8px;gap:3px;display:flex;align-items:center;" title="Pilihan Cetak Dokumen">
                                        <i data-lucide="printer" style="width:14px;height:14px;"></i>
                                        <i data-lucide="chevron-down" style="width:11px;height:11px;opacity:0.7;"></i>
                                    </button>

                                    <!-- Floating Mini Hover Card -->
                                    <div x-show="openPrint" 
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                         x-transition:leave="transition ease-in duration-100"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-95"
                                         x-cloak
                                         class="absolute right-0 top-full mt-1 w-52 rounded-xl shadow-2xl z-50 py-1.5 border text-left"
                                         style="background:var(--color-surface, #ffffff);border-color:var(--color-hairline, #e2e8f0);box-shadow:0 15px 30px -5px rgba(0,0,0,0.25);">
                                        
                                        <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:var(--color-ink-mute,#64748b);padding:6px 12px;border-bottom:1px solid var(--color-hairline,#e2e8f0);">
                                            🖨️ Cetak Dokumen
                                        </div>

                                        <!-- 1. Cetak Faktur Penjualan -->
                                        <a href="<?= Router::url('/customer-orders/invoice?id=' . $o['id']) ?>" target="_blank"
                                           class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold hover:bg-emerald-500/10 hover:text-emerald-600 transition"
                                           style="color:var(--color-ink,#0f172a);text-decoration:none;">
                                            <div style="width:28px;height:28px;border-radius:6px;background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                <i data-lucide="file-text" style="width:14px;height:14px;"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight:700;line-height:1.2;">Faktur Penjualan</div>
                                                <div style="font-size:10px;color:var(--color-ink-mute,#64748b);font-weight:500;">Invoice Tagihan Toko</div>
                                            </div>
                                        </a>

                                        <!-- 2. Cetak Surat Jalan -->
                                        <a href="<?= Router::url('/deliveries/print?order_id=' . $o['id']) ?>" target="_blank"
                                           class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold hover:bg-sky-500/10 hover:text-sky-600 transition"
                                           style="color:var(--color-ink,#0f172a);text-decoration:none;">
                                            <div style="width:28px;height:28px;border-radius:6px;background:rgba(2,132,199,0.12);color:#0284c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                <i data-lucide="truck" style="width:14px;height:14px;"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight:700;line-height:1.2;">Surat Jalan</div>
                                                <div style="font-size:10px;color:var(--color-ink-mute,#64748b);font-weight:500;">Delivery Order Driver</div>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <!-- Catat Pelunasan Piutang (Jika belum lunas) -->
                                <?php 
                                $isGagalKirim = in_array($o['status_pemrosesan'] ?? '', ['gagal_dikirim', 'gagal_kembali', 'gagal_kirim'], true) || ($o['status_surat_jalan'] ?? '') === 'gagal_kembali';
                                ?>
                                <?php if (!$isLunas && !$isGagalKirim): ?>
                                <button type="button" @click="openPaymentModal(<?= htmlspecialchars(json_encode($o)) ?>)" class="btn btn-primary btn-sm" style="padding:4px 8px;font-size:11px;" title="Catat Pembayaran Toko">
                                    <i data-lucide="wallet" style="width:14px;height:14px;"></i>
                                    <span>Bayar</span>
                                </button>
                                <?php elseif (!$isLunas && $isGagalKirim): ?>
                                <button type="button" @click="openPaymentModal(<?= htmlspecialchars(json_encode($o)) ?>)" class="btn btn-sm" style="padding:4px 8px;font-size:11px;background:#fee2e2;color:#991b1b;border:1px solid #fecdd3;" title="Pembayaran ditangguhkan: Pengiriman Gagal Kirim">
                                    <i data-lucide="alert-triangle" style="width:13px;height:13px;"></i>
                                    <span>Tertunda</span>
                                </button>
                                <?php endif; ?>

                                <!-- Batalkan Transaksi -->
                                <button type="button" @click="openCancelModal(<?= htmlspecialchars(json_encode($o)) ?>)" class="btn btn-ghost btn-sm text-danger" style="padding:4px 6px;" title="Batalkan Faktur & Kembalikan Stok">
                                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: CATAT PELUNASAN PIUTANG TOKO                                       -->
    <!-- ========================================================================= -->
    <div x-show="showPaymentModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:480px;padding:24px;">
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(16,185,129,0.1);color:#10b981;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="wallet" style="width:16px;height:16px;"></i>
                    </div>
                    <div>
                        <div class="modal-title">Catat Pelunasan Tagihan Toko</div>
                        <div style="font-size:11.5px;color:var(--color-ink-mute);" x-text="selectedOrder?.nama_toko + ' (' + selectedOrder?.nomor_nota + ')'"></div>
                    </div>
                </div>
                <button @click="showPaymentModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <!-- Tampilan Peringatan Jika Pesanan Gagal Kirim -->
            <template x-if="['gagal_dikirim', 'gagal_kembali', 'gagal_kirim'].includes(selectedOrder?.status_pemrosesan) || selectedOrder?.status_surat_jalan === 'gagal_kembali'">
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div style="padding:16px;background:#fff1f2;border:1.5px solid #fecdd3;border-radius:var(--rounded-md);display:flex;align-items:flex-start;gap:12px;">
                        <div style="width:36px;height:36px;border-radius:10px;background:#ffe4e6;color:#e11d48;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="alert-triangle" style="width:18px;height:18px;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:13.5px;font-weight:800;color:#9f1239;">Pembayaran Ditangguhkan: Gagal Kirim</div>
                            <div style="font-size:12px;color:#be123c;margin-top:2px;line-height:1.45;">
                                Pesanan ini berstatus <strong>Gagal Kirim</strong>. Seluruh stok fisik produk telah aman dikembalikan ke rak gudang. Pelunasan tagihan tidak dapat dicatat sebelum dilakukan penjadwalan kirim ulang.
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:8px;">
                        <button type="button" @click="showPaymentModal = false" class="btn btn-secondary">Tutup</button>
                    </div>
                </div>
            </template>

            <!-- Form Catat Pembayaran Jika Bukan Gagal Kirim -->
            <template x-if="!['gagal_dikirim', 'gagal_kembali', 'gagal_kirim'].includes(selectedOrder?.status_pemrosesan) && selectedOrder?.status_surat_jalan !== 'gagal_kembali'">
                <form action="<?= Router::url('/sales-orders/pay') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                    <input type="hidden" name="id" :value="selectedOrder?.id">

                    <!-- Ringkasan Tagihan -->
                    <div style="padding:12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="font-size:11px;color:var(--color-ink-mute);">Sisa Tagihan Belum Lunas:</div>
                            <div style="font-size:16px;font-weight:900;font-family:var(--font-mono);color:#ef4444;" x-text="formatRupiah(calcSisaTagihan())"></div>
                        </div>
                        <button type="button" @click="paymentForm.nominal_bayar = formatRupiahNumber(calcSisaTagihan())" class="btn btn-secondary btn-sm" style="font-size:11px;padding:4px 8px;">
                            Bayar Lunas
                        </button>
                    </div>

                    <div>
                        <label class="form-label">Masuk ke Akun Kas / Bank *</label>
                        <select name="akun_kas_id" x-model="paymentForm.akun_kas_id" required class="form-input">
                            <option value="">-- Pilih Akun Kas Penerima --</option>
                            <?php foreach ($cashAccounts as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nama_akun']) ?> (Rp <?= number_format((float)$a['saldo_saat_ini'], 0, ',', '.') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Nominal Pembayaran (Rp) *</label>
                            <input type="text" name="nominal_bayar" x-model="paymentForm.nominal_bayar" required class="form-input font-mono input-rupiah" placeholder="0">
                        </div>
                        <div>
                            <label class="form-label">Tanggal Bayar *</label>
                            <input type="date" name="tanggal_bayar" x-model="paymentForm.tanggal_bayar" required class="form-input font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Catatan / Keterangan</label>
                        <input type="text" name="keterangan" x-model="paymentForm.keterangan" class="form-input" placeholder="Contoh: Titipan pembayaran tagihan lewat Driver">
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                        <button type="button" @click="showPaymentModal = false" class="btn btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="save"></i>
                            <span>Simpan Pembayaran</span>
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: KONFIRMASI PEMBATALAN TRANSAKSI                                    -->
    <!-- ========================================================================= -->
    <div x-show="showCancelModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:440px;padding:24px;">
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(239,68,68,0.1);color:#ef4444;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="alert-triangle" style="width:16px;height:16px;"></i>
                    </div>
                    <div class="modal-title" style="color:#ef4444;">Batalkan Faktur Penjualan?</div>
                </div>
                <button @click="showCancelModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/sales-orders/cancel') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="id" :value="selectedOrder?.id">

                <div style="font-size:13px;color:var(--color-ink-secondary);line-height:1.5;">
                    Apakah Anda yakin ingin membatalkan transaksi faktur <strong class="font-mono" style="color:var(--color-ink);" x-text="selectedOrder?.nomor_nota"></strong>?
                    <div style="margin-top:8px;padding:10px;background:rgba(239,68,68,0.08);border-radius:var(--rounded-md);font-size:12px;color:#ef4444;">
                        ⚠️ <strong>Perhatian:</strong> Seluruh stok barang jadi pada faktur ini akan <strong>otomatis dikembalikan ke gudang</strong>, dan transaksi kas terkait akan dikoreksi.
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showCancelModal = false" class="btn btn-secondary">Tidak, Kembali</button>
                    <button type="submit" class="btn btn-danger">
                        <i data-lucide="trash-2"></i>
                        <span>Ya, Batalkan Transaksi</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: UPDATE STATUS PENGIRIMAN & DRIVER                                 -->
    <!-- ========================================================================= -->
    <div x-show="showDeliveryModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:480px;padding:24px;">
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(2,132,199,0.1);color:#0284c7;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="truck" style="width:16px;height:16px;"></i>
                    </div>
                    <div>
                        <div class="modal-title">Status Pengiriman &amp; Armada Driver</div>
                        <div style="font-size:11.5px;color:var(--color-ink-mute);" x-text="selectedOrder?.nama_toko + ' (' + selectedOrder?.nomor_nota + ')'"></div>
                    </div>
                </div>
                <button @click="showDeliveryModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/customer-orders/update-delivery-status') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="order_id" :value="selectedOrder?.id">

                <div>
                    <label class="form-label">Tugaskan Sales-Driver &amp; Armada</label>
                    <select name="sales_driver_id" x-model="deliveryForm.sales_driver_id" class="form-input">
                        <option value="">-- Pilih Sales / Driver --</option>
                        <?php foreach ($drivers as $d): ?>
                        <option value="<?= $d['id'] ?>">🚚 <?= htmlspecialchars($d['nama_karyawan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Status Pengiriman Barang *</label>
                    <select name="status_surat_jalan" x-model="deliveryForm.status_surat_jalan" required class="form-input">
                        <option value="disetujui_owner">📦 Siap Kirim (Disiapkan di Gudang)</option>
                        <option value="sedang_dikirim">🚚 Sedang Dikirim (Driver Dalam Perjalanan)</option>
                        <option value="selesai_diterima">✅ Selesai Diterima (Toko Telah Menerima Barang)</option>
                        <option value="gagal_kembali">⚠️ Gagal Kirim / Retur Kembali ke Gudang</option>
                    </select>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showDeliveryModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span>Simpan Status Pengiriman</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function salesOrderListApp() {
    return {
        showPaymentModal: false,
        showCancelModal: false,
        showDeliveryModal: false,
        selectedOrder: null,

        paymentForm: {
            akun_kas_id: '<?= !empty($cashAccounts) ? $cashAccounts[0]['id'] : '' ?>',
            nominal_bayar: '0',
            tanggal_bayar: '<?= date('Y-m-d') ?>',
            keterangan: 'Pelunasan Faktur Toko'
        },

        deliveryForm: {
            sales_driver_id: '',
            status_surat_jalan: 'sedang_dikirim'
        },

        init() {
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        calcSisaTagihan() {
            if (!this.selectedOrder) return 0;
            const netto = Number(this.selectedOrder.total_netto || 0);
            const dibayar = Number(this.selectedOrder.total_dibayar || 0);
            return Math.max(0, netto - dibayar);
        },

        openPaymentModal(order) {
            this.selectedOrder = order;
            const sisa = Math.max(0, Number(order.total_netto || 0) - Number(order.total_dibayar || 0));
            this.paymentForm = {
                akun_kas_id: '<?= !empty($cashAccounts) ? $cashAccounts[0]['id'] : '' ?>',
                nominal_bayar: window.formatRupiahNumber ? window.formatRupiahNumber(sisa) : String(sisa),
                tanggal_bayar: '<?= date('Y-m-d') ?>',
                keterangan: 'Pelunasan Faktur Toko'
            };
            this.showPaymentModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        openDeliveryModal(order) {
            this.selectedOrder = order;
            this.deliveryForm = {
                sales_driver_id: order.sales_driver_id || '',
                status_surat_jalan: order.status_surat_jalan || 'disetujui_owner'
            };
            this.showDeliveryModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        openCancelModal(order) {
            this.selectedOrder = order;
            this.showCancelModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        formatRupiah(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

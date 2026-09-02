<?php
use App\Core\Router;
use App\Core\Auth;
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
                    <span>Modul Penjualan B2B</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Pesanan Pelanggan' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Daftar Transaksi & Faktur B2B Toko Mitra' ?></p>
            </div>
        </div>
        <div class="page-header-actions">
            <a href="<?= Router::url('/customer-orders/create') ?>" class="btn btn-primary" style="font-weight:700;">
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
                    <div class="stat-value" style="color:#059669;font-size:20px;font-weight:900;"><?= Format::rupiah($metrics['total_omset']) ?></div>
                </div>
                <div style="width:40px;height:40px;border-radius:var(--rounded-md);background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center;">
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
                    <div class="stat-value" style="color:#d97706;font-size:20px;font-weight:800;"><?= Format::rupiah($metrics['total_piutang']) ?></div>
                </div>
                <div style="width:40px;height:40px;border-radius:var(--rounded-md);background:rgba(245,158,11,0.12);color:#f59e0b;display:flex;align-items:center;justify-content:center;">
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
            <div class="stat-helper">Total transaksi B2B</div>
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
        <form action="<?= Router::url('/customer-orders') ?>" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
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
                <select name="pelanggan_id" class="form-input" style="height:36px;font-size:12px;">
                    <option value="">-- Semua Toko --</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filter['pelanggan_id'] === $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nama_toko']) ?>
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
                <a href="<?= Router::url('/customer-orders?reset=1') ?>" class="btn btn-secondary" style="height:36px;padding:0 12px;" title="Reset Filter ke Default">
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
                        <th class="cell-center cell-nowrap" style="width:110px;">Aksi</th>
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
                                <a href="<?= Router::url('/customer-orders/create') ?>" class="btn btn-primary btn-sm">
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
                            <button type="button" @click="openOrderDetail(<?= htmlspecialchars(json_encode($o)) ?>)" class="font-mono font-bold" style="color:var(--color-ink);background:none;border:none;padding:0;cursor:pointer;display:flex;align-items:center;gap:6px;text-align:left;">
                                <span class="hover:underline hover:text-blue-600"><?= htmlspecialchars($o['nomor_nota']) ?></span>
                                <i data-lucide="external-link" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                            </button>
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

                            <!-- Status Tahap Pemrosesan Badge -->
                            <div style="margin-top:4px;">
                                <?php 
                                $statusProc = $o['status_pemrosesan'] ?? 'po';
                                if ($statusProc === 'po'): ?>
                                <span class="badge" style="background:#dbeafe;color:#1e40af;font-size:10.5px;font-weight:700;border-radius:6px;padding:3px 7px;">
                                    📝 PO
                                </span>
                                <?php elseif (in_array($statusProc, ['siap_dikirim', 'siap_kirim'], true)): ?>
                                <span class="badge" style="background:#d1fae5;color:#065f46;font-size:10.5px;font-weight:700;border-radius:6px;padding:3px 7px;">
                                    📦 Siap Dikirim
                                </span>
                                <?php elseif ($statusProc === 'sedang_dikirim'): ?>
                                <span class="badge" style="background:#fef3c7;color:#92400e;font-size:10.5px;font-weight:700;border-radius:6px;padding:3px 7px;">
                                    🚚 Sedang Dikirim
                                </span>
                                <?php elseif (in_array($statusProc, ['selesai_dikirim', 'selesai', 'selesai_diterima'], true)): ?>
                                <span class="badge" style="background:#ecfdf5;color:#047857;font-size:10.5px;font-weight:700;border-radius:6px;padding:3px 7px;">
                                    ✅ Selesai Diterima
                                </span>
                                <?php elseif (in_array($statusProc, ['gagal_dikirim', 'gagal_kembali', 'gagal_kirim'], true)): ?>
                                <span class="badge" style="background:#ffe4e6;color:#9f1239;font-size:10.5px;font-weight:700;border-radius:6px;padding:3px 7px;">
                                    ❌ Gagal Kirim
                                </span>
                                <?php elseif ($statusProc === 'dibatalkan'): ?>
                                <span class="badge" style="background:#f1f5f9;color:#64748b;font-size:10.5px;font-weight:700;border-radius:6px;padding:3px 7px;">
                                    🚫 Dibatalkan
                                </span>
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
                                <span style="color:#d97706;">⏱️ <?= str_replace('_', ' ', $o['tipe_pembayaran']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($o['tanggal_jatuh_tempo']) && !$isLunas): 
                                $isOverdue = strtotime($o['tanggal_jatuh_tempo']) < strtotime(date('Y-m-d'));
                            ?>
                            <div style="font-size:11px;margin-top:2px;color:<?= $isOverdue ? '#ef4444' : 'var(--color-ink-mute)' ?>;">
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

                        <!-- Aksi (Detail & Edit) -->
                        <td class="cell-center cell-nowrap">
                            <div class="d-inline-flex align-items-center gap-1.5" style="display:inline-flex;align-items:center;gap:6px;">
                                <button type="button" 
                                        @click="openOrderDetail(<?= htmlspecialchars(json_encode($o)) ?>)" 
                                        class="btn btn-secondary btn-sm" 
                                        style="padding:6px 12px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:5px;border-radius:var(--rounded-md);box-shadow:0 1px 2px rgba(0,0,0,0.05);"
                                        title="Buka Rincian & Aksi Transaksi">
                                    <i data-lucide="eye" style="width:14px;height:14px;color:var(--color-primary-deep);"></i>
                                    <span>Detail</span>
                                </button>
                                <?php if (Auth::can(['orders.edit_all', 'orders.edit_assigned']) && !in_array($o['status_pemrosesan'] ?? '', ['selesai', 'selesai_diterima', 'dibatalkan', 'dikirim'], true)): ?>
                                    <?php if (!empty($o['surat_jalan_id'])): ?>
                                    <button type="button" 
                                            class="btn btn-secondary btn-sm"
                                            style="padding:6px 10px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:4px;border-radius:var(--rounded-md);color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;cursor:pointer;"
                                            title="Terkunci: Surat Jalan #<?= htmlspecialchars($o['nomor_surat_jalan'] ?? '') ?> aktif"
                                            onclick="window.AppAlert ? window.AppAlert({ title: 'Edit Pesanan Terkunci', message: 'Pesanan ini sudah memiliki Surat Jalan aktif (#<?= htmlspecialchars($o['nomor_surat_jalan'] ?? '') ?>).\n\nUntuk mengedit rincian pesanan, silakan batalkan atau hapus Surat Jalan terlebih dahulu.', type: 'warning', icon: 'lock' }) : alert('Pesanan ini sudah memiliki Surat Jalan aktif (#<?= htmlspecialchars($o['nomor_surat_jalan'] ?? '') ?>). Batalkan Surat Jalan terlebih dahulu jika ingin mengedit.')">
                                        <i data-lucide="lock" style="width:13px;height:13px;color:#94a3b8;"></i>
                                        <span>Edit</span>
                                    </button>
                                    <?php else: ?>
                                    <a href="<?= Router::url('/customer-orders/edit?id=' . urlencode($o['id'])) ?>" 
                                       class="btn btn-secondary btn-sm"
                                       style="padding:6px 10px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:4px;border-radius:var(--rounded-md);color:#2563eb;background:#eff6ff;border:1px solid #bfdbfe;"
                                       title="Edit Pesanan">
                                        <i data-lucide="edit-3" style="width:13px;height:13px;"></i>
                                        <span>Edit</span>
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (in_array($o['status_pemrosesan'] ?? '', ['gagal_dikirim', 'gagal_kembali', 'gagal_kirim'], true) && Auth::can('orders.retry_delivery')): ?>
                                <form action="<?= Router::url('/customer-orders/retry-delivery') ?>" method="POST"
                                      data-confirm="Jadwalkan ulang pesanan #<?= htmlspecialchars($o['nomor_nota']) ?> untuk pengiriman? Status pesanan akan kembali menjadi 'Siap Dikirim'."
                                      data-confirm-title="Kirim Ulang Pesanan"
                                      data-confirm-type="info"
                                      data-confirm-btn="Ya, Jadwalkan Ulang"
                                      style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($o['id']) ?>">
                                    <button type="submit" class="btn btn-sm" style="padding:6px 10px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:4px;border-radius:var(--rounded-md);color:#ffffff;background:#e11d48;" title="Jadwalkan Kirim Ulang (Batas 7 Hari)">
                                        <i data-lucide="rotate-cw" style="width:13px;height:13px;"></i>
                                        <span>Kirim Ulang</span>
                                    </button>
                                </form>
                                <?php endif; ?>
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
    <!-- MODAL: POP-UP DETAIL PESANAN LENGKAP (MODERN & RESPONSIF)                 -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showDetailModal" x-cloak class="modal-backdrop" @click.self="showDetailModal = false">
        <div class="modal-box modal-box-lg" @click.stop>
            
            <!-- MOBILE PULL HANDLE -->
            <div class="sm:hidden w-full flex justify-center pt-3 pb-1 flex-shrink-0" style="background:var(--color-canvas);">
                <div style="width:40px;height:4px;border-radius:2px;background:var(--color-hairline-strong);"></div>
            </div>

            <!-- 1. MODAL HEADER -->
            <div style="padding:16px 20px;border-bottom:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:space-between;background:var(--color-canvas);flex-shrink:0;gap:12px;">
                <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                    <div style="width:42px;height:42px;border-radius:12px;background:#eff6ff;color:#1e3a8a;border:1px solid rgba(30,58,138,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="file-text" style="width:20px;height:20px;"></i>
                    </div>
                    <div style="min-width:0;flex:1;">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span class="font-mono font-black" style="font-size:16px;color:var(--color-ink);letter-spacing:-0.02em;" x-text="orderDetail?.nomor_nota"></span>
                            <span class="badge" 
                                  :class="orderDetail?.status_pembayaran === 'lunas' ? 'badge-success' : 'badge-warning'" 
                                  style="font-size:10px;font-weight:800;text-transform:uppercase;padding:2px 8px;border-radius:6px;" 
                                  x-text="orderDetail?.status_pembayaran === 'lunas' ? 'LUNAS' : 'BELUM LUNAS'"></span>
                            <template x-if="orderDetail?.status_surat_jalan">
                                <span class="badge badge-mono" style="font-size:10px;text-transform:capitalize;padding:2px 7px;" x-text="orderDetail?.status_surat_jalan.replace('_', ' ')"></span>
                            </template>
                        </div>
                        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <span x-text="formatDateFull(orderDetail?.tanggal_pesanan)"></span> &bull; 
                            <span style="font-weight:700;color:var(--color-ink);" x-text="orderDetail?.nama_toko"></span>
                        </div>
                    </div>
                </div>
                
                <div style="display:flex;align-items:center;gap:12px;flex-shrink:0;">
                    <div class="hidden md:flex flex-col items-end">
                        <span style="font-size:10.5px;color:var(--color-ink-mute);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;">Grand Total</span>
                        <span class="font-mono font-black" style="font-size:16px;color:var(--color-ink);" x-text="formatRupiah(orderDetail?.total_netto)"></span>
                    </div>
                    <button type="button" @click="showDetailModal = false" class="btn btn-ghost btn-sm" style="width:34px;height:34px;padding:0;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--color-ink-mute);" aria-label="Tutup">
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>
            </div>

            <!-- 2. TAB NAVIGATION BAR -->
            <div class="modal-tab-nav custom-scrollbar">
                <button type="button" @click="activeTab = 'items'" class="modal-tab-btn" :class="{ 'is-active': activeTab === 'items' }">
                    <i data-lucide="package" style="width:14px;height:14px;"></i>
                    <span>Produk &amp; Nota</span>
                    <span class="badge" style="font-size:10px;padding:1px 6px;border-radius:10px;" x-text="orderItems.length || '0'"></span>
                </button>
                <button type="button" @click="activeTab = 'shipping'" class="modal-tab-btn" :class="{ 'is-active': activeTab === 'shipping' }">
                    <i data-lucide="truck" style="width:14px;height:14px;"></i>
                    <span>Pengiriman</span>
                </button>
                <button type="button" @click="activeTab = 'payment'" class="modal-tab-btn" :class="{ 'is-active': activeTab === 'payment' }">
                    <i data-lucide="credit-card" style="width:14px;height:14px;"></i>
                    <span>Pembayaran</span>
                    <template x-if="calcSisaTagihan() > 0">
                        <span class="badge badge-danger" style="font-size:9.5px;padding:1px 5px;border-radius:8px;">Sisa</span>
                    </template>
                </button>
                <button type="button" @click="activeTab = 'actions'" class="modal-tab-btn" :class="{ 'is-active': activeTab === 'actions' }">
                    <i data-lucide="printer" style="width:14px;height:14px;"></i>
                    <span>Dokumen &amp; Aksi</span>
                </button>
            </div>

            <!-- 3. TAB BODIES -->
            <div class="modal-tab-body custom-scrollbar">

                <!-- LOADING SKELETON -->
                <template x-if="loadingDetail">
                    <div style="display:flex;flex-direction:column;gap:14px;padding:60px 20px;align-items:center;justify-content:center;color:var(--color-ink-mute);">
                        <div class="spinner" style="width:32px;height:32px;border:3px solid var(--color-hairline);border-top-color:#1e3a8a;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
                        <div style="font-size:13px;font-weight:600;">Memuat rincian pesanan pelanggan...</div>
                    </div>
                </template>

                <!-- TAB 1: PRODUK & NOTA (UNIFIED CONTINUOUS CANVAS) -->
                <div x-show="!loadingDetail && activeTab === 'items'" style="display:flex;flex-direction:column;">
                    
                    <!-- SINGLE UNIFIED INVOICE CARD -->
                    <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                        
                        <!-- 1. Toko & Parameter Info Section -->
                        <div style="padding:18px 20px;background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                                <div>
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <span style="font-size:10.5px;color:var(--color-ink-mute);font-weight:800;text-transform:uppercase;letter-spacing:0.05em;">Toko Pelanggan</span>
                                        <span class="font-mono font-bold" style="font-size:11px;color:var(--color-ink-mute);" x-text="'(' + (orderDetail?.kode_pelanggan || '-') + ')'"></span>
                                    </div>
                                    <div style="font-size:16px;font-weight:800;color:var(--color-ink);margin-top:2px;" x-text="orderDetail?.nama_toko"></div>
                                    <div style="font-size:12px;color:var(--color-ink-secondary);margin-top:2px;">
                                        Pemilik: <strong style="color:var(--color-ink);" x-text="orderDetail?.nama_pemilik || '-'"></strong>
                                    </div>
                                </div>

                                <div class="flex flex-col md:items-end gap-2">
                                    <template x-if="orderDetail?.nomor_whatsapp">
                                        <a :href="'https://wa.me/' + cleanWa(orderDetail?.nomor_whatsapp)" target="_blank" 
                                           class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 self-start md:self-end" 
                                           style="color:#059669;font-weight:700;font-size:11.5px;padding:4px 10px;border-color:rgba(16,185,129,0.25);background:rgba(16,185,129,0.06);text-decoration:none;border-radius:8px;">
                                            <i data-lucide="message-circle" style="width:13px;height:13px;"></i>
                                            <span class="font-mono" x-text="orderDetail?.nomor_whatsapp"></span>
                                        </a>
                                    </template>
                                    <div style="font-size:12px;color:var(--color-ink-mute);">
                                        Metode: <strong style="color:var(--color-ink);" x-text="formatTipeBayar(orderDetail?.tipe_pembayaran)"></strong>
                                        <template x-if="orderDetail?.tanggal_jatuh_tempo">
                                            <span> &bull; Tempo: <span class="font-mono font-bold text-danger" x-text="formatDateShort(orderDetail?.tanggal_jatuh_tempo)"></span></span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <template x-if="orderDetail?.alamat_lengkap">
                                <div style="font-size:12px;color:var(--color-ink-secondary);padding-top:12px;margin-top:12px;border-top:1px dashed var(--color-hairline);line-height:1.45;display:flex;align-items:flex-start;gap:6px;">
                                    <i data-lucide="map-pin" style="width:14px;height:14px;color:var(--color-ink-mute);flex-shrink:0;margin-top:1px;"></i>
                                    <span x-text="orderDetail?.alamat_lengkap"></span>
                                </div>
                            </template>
                        </div>

                        <!-- 2. DESKTOP VIEW: TABEL ITEM (Integrated seamlessly) -->
                        <div class="detail-modal-table-wrap" style="border:none;border-radius:0;background:transparent;">
                            <table class="table" style="margin:0;width:100%;">
                                <thead style="background:var(--color-canvas);">
                                    <tr style="border-bottom:1px solid var(--color-hairline);">
                                        <th style="width:50px;text-align:center;font-size:11px;font-weight:700;text-transform:uppercase;padding:12px 16px;">No</th>
                                        <th style="font-size:11px;font-weight:700;text-transform:uppercase;padding:12px 16px;">Produk &amp; SKU</th>
                                        <th class="cell-center" style="font-size:11px;font-weight:700;text-transform:uppercase;padding:12px 16px;">Kuantitas</th>
                                        <th class="cell-right" style="font-size:11px;font-weight:700;text-transform:uppercase;padding:12px 16px;">Harga Satuan</th>
                                        <th class="cell-right" style="font-size:11px;font-weight:700;text-transform:uppercase;padding:12px 16px;">Diskon</th>
                                        <th class="cell-right" style="font-size:11px;font-weight:700;text-transform:uppercase;padding:12px 20px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(it, idx) in orderItems" :key="it.id">
                                        <tr style="border-bottom:1px solid var(--color-hairline);">
                                            <td class="cell-center" style="font-size:12px;color:var(--color-ink-mute);font-weight:600;padding:14px 16px;" x-text="idx + 1"></td>
                                            <td style="padding:14px 16px;">
                                                <div style="font-weight:700;font-size:13.5px;color:var(--color-ink);" x-text="it.nama_item"></div>
                                                <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:3px;display:flex;align-items:center;gap:6px;">
                                                    <span class="badge badge-mono" style="font-size:10px;padding:0 5px;" x-text="it.kode_sku"></span>
                                                    <template x-if="it.varian_rasa">
                                                        <span style="color:var(--color-ink-secondary);font-weight:500;" x-text="'Varian: ' + it.varian_rasa"></span>
                                                    </template>
                                                </div>
                                            </td>
                                            <td class="cell-center cell-nowrap font-mono" style="font-size:12.5px;font-weight:700;padding:14px 16px;">
                                                <template x-if="Number(it.jumlah_bal) > 0">
                                                    <span class="badge" style="background:#eff6ff;color:#1e3a8a;font-weight:700;font-size:11px;padding:3px 8px;border-radius:6px;" x-text="it.jumlah_bal + ' Bal' + (Number(it.jumlah_pcs_lepas) > 0 ? ' + ' + it.jumlah_pcs_lepas + ' Pcs' : '') + ' (' + it.kuantitas_satuan_dasar + ' ' + it.satuan_dasar + ')'"></span>
                                                </template>
                                                <template x-if="Number(it.jumlah_bal || 0) <= 0">
                                                    <span class="badge badge-mono" style="font-size:11.5px;padding:3px 8px;" x-text="it.kuantitas_satuan_dasar + ' ' + it.satuan_dasar"></span>
                                                </template>
                                            </td>
                                            <td class="cell-right cell-nowrap font-mono" style="font-size:12.5px;color:var(--color-ink-secondary);padding:14px 16px;" x-text="formatRupiah(it.harga_satuan_dasar)"></td>
                                            <td class="cell-right cell-nowrap font-mono" style="font-size:12px;color:#059669;padding:14px 16px;">
                                                <span x-text="Number(it.diskon_nominal || 0) > 0 ? '-' + formatRupiah(it.diskon_nominal) : '-'"></span>
                                            </td>
                                            <td class="cell-right cell-nowrap font-mono font-black" style="font-size:14px;color:var(--color-ink);padding:14px 20px;" x-text="formatRupiah(it.subtotal)"></td>
                                        </tr>
                                    </template>
                                    <template x-if="orderItems.length === 0">
                                        <tr>
                                            <td colspan="6" style="padding:36px 20px;text-align:center;color:var(--color-ink-mute);font-size:13px;">
                                                Tidak ada rincian item produk pada faktur pesanan ini.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- 3. MOBILE VIEW: ITEM CARDS (Integrated seamlessly on screen < 640px) -->
                        <div class="detail-modal-cards-wrap" style="padding:14px 16px;background:var(--color-canvas);border-top:1px solid var(--color-hairline);">
                            <div style="font-size:11px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;display:flex;justify-content:space-between;align-items:center;padding:0 2px;margin-bottom:6px;">
                                <span>Daftar Produk (<span x-text="orderItems.length"></span> SKU)</span>
                                <span>Subtotal</span>
                            </div>
                            <template x-for="(it, idx) in orderItems" :key="it.id">
                                <div style="padding:12px 0;border-bottom:1px solid var(--color-hairline);display:flex;flex-direction:column;gap:8px;">
                                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                                        <div style="min-width:0;flex:1;">
                                            <div style="font-weight:700;font-size:13px;color:var(--color-ink);line-height:1.35;" x-text="it.nama_item"></div>
                                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">
                                                <span class="font-mono font-semibold" x-text="it.kode_sku"></span>
                                                <template x-if="it.varian_rasa">
                                                    <span> &bull; Rasa: <strong style="color:var(--color-ink);" x-text="it.varian_rasa"></strong></span>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <div class="font-mono font-black" style="font-size:14px;color:var(--color-ink);" x-text="formatRupiah(it.subtotal)"></div>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;font-size:11.5px;">
                                        <div style="display:inline-flex;align-items:center;gap:4px;background:var(--color-canvas-soft);padding:3px 8px;border-radius:6px;border:1px solid var(--color-hairline);">
                                            <span class="font-mono font-bold" style="color:var(--color-ink);">
                                                <template x-if="Number(it.jumlah_bal) > 0">
                                                    <span x-text="it.jumlah_bal + ' Bal' + (Number(it.jumlah_pcs_lepas) > 0 ? ' + ' + it.jumlah_pcs_lepas : '') + ' (' + it.kuantitas_satuan_dasar + ' pcs)'"></span>
                                                </template>
                                                <template x-if="Number(it.jumlah_bal || 0) <= 0">
                                                    <span x-text="it.kuantitas_satuan_dasar + ' ' + it.satuan_dasar"></span>
                                                </template>
                                            </span>
                                        </div>

                                        <div class="font-mono text-right" style="color:var(--color-ink-secondary);font-size:11px;">
                                            <span x-text="'@ ' + formatRupiah(it.harga_satuan_dasar)"></span>
                                            <template x-if="Number(it.diskon_nominal || 0) > 0">
                                                <span style="color:#059669;margin-left:4px;font-weight:700;" x-text="'(-' + formatRupiah(it.diskon_nominal) + ')'"></span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- 4. Financial Summary & Notes (Unified Footer Section) -->
                        <div style="padding:16px 20px;background:var(--color-canvas-soft);border-top:1px solid var(--color-hairline);">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                                <!-- Left: Catatan -->
                                <div>
                                    <template x-if="orderDetail?.catatan">
                                        <div style="font-size:12px;color:var(--color-ink);line-height:1.45;">
                                            <span style="font-weight:800;color:#1e3a8a;text-transform:uppercase;font-size:10.5px;letter-spacing:0.04em;">Catatan:</span>
                                            <div style="margin-top:2px;color:var(--color-ink-secondary);" x-text="orderDetail?.catatan"></div>
                                        </div>
                                    </template>
                                    <template x-if="!orderDetail?.catatan">
                                        <div style="font-size:11.5px;color:var(--color-ink-mute);font-style:italic;">
                                            Tidak ada catatan khusus pada faktur pesanan ini.
                                        </div>
                                    </template>
                                </div>

                                <!-- Right: Financial Summary Box -->
                                <div style="display:flex;flex-direction:column;gap:6px;" class="md:items-end">
                                    <div style="display:flex;justify-content:space-between;width:100%;max-width:280px;font-size:12.5px;color:var(--color-ink-mute);">
                                        <span>Total Bruto:</span>
                                        <span class="font-mono font-bold" style="color:var(--color-ink);" x-text="formatRupiah(orderDetail?.total_bruto)"></span>
                                    </div>
                                    <template x-if="Number(orderDetail?.total_diskon || 0) > 0">
                                        <div style="display:flex;justify-content:space-between;width:100%;max-width:280px;font-size:12.5px;color:#059669;">
                                            <span>Total Diskon:</span>
                                            <span class="font-mono font-bold" x-text="'-' + formatRupiah(orderDetail?.total_diskon)"></span>
                                        </div>
                                    </template>
                                    <div style="display:flex;justify-content:space-between;align-items:center;width:100%;max-width:280px;font-size:14px;font-weight:900;color:var(--color-ink);padding-top:8px;border-top:1px solid var(--color-hairline);margin-top:2px;">
                                        <span>Grand Total:</span>
                                        <span class="font-mono font-black" style="font-size:18px;color:#1e3a8a;" x-text="formatRupiah(orderDetail?.total_netto)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- TAB 2: DRIVER & PENGIRIMAN -->
                <div x-show="!loadingDetail && activeTab === 'shipping'" style="display:flex;flex-direction:column;gap:16px;">
                    
                    <div style="padding:14px 16px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;display:flex;flex-direction:column;gap:4px;">
                        <div style="font-size:10px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;">Tujuan Pengiriman:</div>
                        <div style="font-size:14px;font-weight:800;color:var(--color-ink);" x-text="orderDetail?.nama_toko"></div>
                        <div style="font-size:12px;color:var(--color-ink-secondary);line-height:1.45;" x-text="orderDetail?.alamat_lengkap || 'Alamat toko belum diatur lengkap di master pelanggan.'"></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="form-label font-bold" style="font-size:12px;">Driver / Armada Pengantar</label>
                            <div style="padding:10px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:10px;font-size:13px;font-weight:600;color:var(--color-ink);" 
                                 x-text="orderDetail?.nama_sales || 'Belum ditugaskan dari Gudang'"></div>
                        </div>

                        <div>
                            <label class="form-label font-bold" style="font-size:12px;">Nomor Polisi Kendaraan</label>
                            <div style="padding:10px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:10px;font-size:13px;font-weight:700;font-family:monospace;color:var(--color-ink-secondary);" 
                                 x-text="orderDetail?.nopol_driver || 'Tidak ada info'"></div>
                        </div>
                    </div>

                    <div>
                        <label class="form-label font-bold" style="font-size:12px;">Status Pengiriman Saat Ini</label>
                        <div style="padding:12px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-weight:700;display:flex;align-items:center;gap:8px;">
                            <i data-lucide="info" style="width:16px;height:16px;color:#3b82f6;"></i>
                            <span style="color:#1e293b;" x-text="(orderDetail?.status_surat_jalan || 'siap_kirim').replace(/_/g, ' ').toUpperCase()"></span>
                        </div>
                        <div style="margin-top:8px;font-size:11.5px;color:var(--color-ink-mute);display:flex;gap:6px;align-items:flex-start;">
                            <i data-lucide="help-circle" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;"></i>
                            <span>Wewenang penugasan driver dan pencetakan surat jalan telah dipindahkan ke menu <strong>Status Pengiriman (Logistik)</strong>. Halaman ini hanya menampilkan informasi secara <em>read-only</em>.</span>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: PEMBAYARAN & PELUNASAN -->
                <div x-show="!loadingDetail && activeTab === 'payment'" style="display:flex;flex-direction:column;gap:16px;">
                    <!-- 3 Financial Metrics (Clean 3-col Grid Symmetrical) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <!-- Metric 1: Total Tagihan -->
                        <div class="metric-card metric-card-neutral">
                            <div class="metric-card-title">Total Tagihan</div>
                            <div class="metric-card-value" x-text="formatRupiah(orderDetail?.total_netto)"></div>
                        </div>

                        <!-- Metric 2: Sudah Dibayar -->
                        <div class="metric-card metric-card-success">
                            <div class="metric-card-title">Sudah Dibayar</div>
                            <div class="metric-card-value" x-text="formatRupiah(orderDetail?.total_dibayar)"></div>
                        </div>

                        <!-- Metric 3: Sisa Tagihan -->
                        <div class="metric-card" :class="calcSisaTagihan() > 0 ? 'metric-card-danger' : 'metric-card-success'">
                            <div class="metric-card-title">Sisa Tagihan</div>
                            <div class="metric-card-value" x-text="formatRupiah(calcSisaTagihan())"></div>
                        </div>
                    </div>

                    <!-- Info Pembayaran Ringkas -->
                    <div style="padding:12px 16px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:12px;font-size:12.5px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                        <div>
                            <span style="color:var(--color-ink-mute);">Metode:</span> 
                            <strong style="color:var(--color-ink);" x-text="formatTipeBayar(orderDetail?.tipe_pembayaran)"></strong>
                            <template x-if="orderDetail?.tanggal_jatuh_tempo">
                                <span> &bull; Jatuh Tempo: <strong class="font-mono text-danger" x-text="formatDateShort(orderDetail?.tanggal_jatuh_tempo)"></strong></span>
                            </template>
                        </div>
                        <div style="font-size:12px;color:var(--color-ink-mute);">
                            Akun Kas: <strong style="color:var(--color-ink);" x-text="orderDetail?.nama_akun_kas || 'Kasir Utama Toko (Tunai)'"></strong>
                        </div>
                    </div>

                    <!-- FORM CATAT BAYAR JIKA BELUM LUNAS -->
                    <template x-if="calcSisaTagihan() > 0">
                        <div style="padding:16px;background:var(--color-canvas);border:1.5px solid rgba(30,58,138,0.2);border-radius:14px;">
                            <div style="font-size:13px;font-weight:800;color:var(--color-ink);margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                                <i data-lucide="wallet" style="width:16px;height:16px;color:#1e3a8a;"></i>
                                <span>Input Pembayaran / Pelunasan</span>
                            </div>
                            <form action="<?= Router::url('/customer-orders/pay') ?>" method="POST" style="display:flex;flex-direction:column;gap:12px;">
                                <input type="hidden" name="id" :value="orderDetail?.id">

                                <div>
                                    <label class="form-label font-bold" style="font-size:11.5px;">Masuk ke Akun Kas / Bank *</label>
                                    <select name="akun_kas_id" x-model="paymentForm.akun_kas_id" required class="form-input font-semibold" style="height:40px;font-size:12.5px;border-radius:10px;">
                                        <template x-for="a in masterCashAccounts" :key="a.id">
                                            <option :value="a.id" x-text="a.nama_akun + ' (Rp ' + Number(a.saldo_saat_ini || 0).toLocaleString('id-ID') + ')'"></option>
                                        </template>
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                            <label class="form-label font-bold" style="margin:0;font-size:11.5px;">Nominal Bayar (Rp) *</label>
                                            <button type="button" @click="paymentForm.nominal_bayar = String(calcSisaTagihan())" class="btn btn-secondary btn-sm" style="font-size:10.5px;padding:2px 8px;border-radius:6px;">
                                                Bayar Lunas
                                            </button>
                                        </div>
                                        <input type="text" name="nominal_bayar" x-model="paymentForm.nominal_bayar" required class="form-input font-mono font-bold input-rupiah" style="height:40px;font-size:13px;border-radius:10px;">
                                    </div>
                                    <div>
                                        <label class="form-label font-bold" style="font-size:11.5px;">Tanggal Bayar *</label>
                                        <input type="date" name="tanggal_bayar" x-model="paymentForm.tanggal_bayar" required class="form-input font-mono" style="height:40px;font-size:12.5px;border-radius:10px;">
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label" style="font-size:11.5px;">Catatan / Keterangan Pembayaran</label>
                                    <input type="text" name="keterangan" x-model="paymentForm.keterangan" class="form-input" placeholder="Contoh: Titipan pelunasan melalui driver" style="height:40px;font-size:12.5px;border-radius:10px;">
                                </div>

                                <div style="display:flex;justify-content:flex-end;margin-top:4px;">
                                    <button type="submit" class="btn btn-primary w-full sm:w-auto" style="padding:10px 20px;font-weight:700;font-size:13px;border-radius:10px;">
                                        <i data-lucide="save"></i>
                                        <span>Simpan Pembayaran</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </template>

                    <template x-if="calcSisaTagihan() <= 0">
                        <div style="padding:16px 20px;text-align:center;background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.25);border-radius:14px;color:#059669;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;gap:8px;">
                            <i data-lucide="check-circle-2" style="width:18px;height:18px;"></i>
                            <span>Faktur penjualan ini telah LUNAS sepenuhnya.</span>
                        </div>
                    </template>
                </div>

                <!-- TAB 4: DOKUMEN & TINDAKAN -->
                <div x-show="!loadingDetail && activeTab === 'actions'" style="display:flex;flex-direction:column;gap:16px;">
                    <!-- Cetak Dokumen -->
                    <div class="grid grid-cols-1 gap-3">
                        <!-- 1. Cetak Faktur -->
                        <a :href="'<?= Router::url('/customer-orders/invoice?id=') ?>' + orderDetail?.id" target="_blank"
                           class="card p-4 hover:shadow-md transition" style="text-decoration:none;display:flex;align-items:center;gap:12px;border:1px solid var(--color-hairline);border-radius:14px;">
                            <div style="width:42px;height:42px;border-radius:12px;background:#eff6ff;color:#1e3a8a;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="printer" style="width:20px;height:20px;"></i>
                            </div>
                            <div>
                                <div style="font-weight:800;font-size:13.5px;color:var(--color-ink);">Cetak Faktur Penjualan</div>
                                <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Invoice tagihan resmi B2B Toko</div>
                            </div>
                        </a>

                        <?php if (Auth::can(['orders.edit_all', 'orders.edit_assigned'])): ?>
                        <template x-if="orderDetail && !['dikirim', 'selesai', 'selesai_diterima', 'dibatalkan'].includes(orderDetail.status_pemrosesan)">
                            <div>
                                <template x-if="orderDetail.surat_jalan_id">
                                    <div class="card p-4" style="border:1.5px solid #e2e8f0;background:#f8fafc;border-radius:14px;display:flex;align-items:center;gap:12px;">
                                        <div style="width:42px;height:42px;border-radius:12px;background:#94a3b8;color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i data-lucide="lock" style="width:20px;height:20px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:800;font-size:13px;color:#475569;">Edit Pesanan Terkunci</div>
                                            <div style="font-size:11px;color:#64748b;margin-top:2px;">
                                                Surat Jalan <span class="font-mono font-bold" x-text="'#' + orderDetail.nomor_surat_jalan"></span> aktif. Batalkan Surat Jalan terlebih dahulu jika ingin mengedit.
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!orderDetail.surat_jalan_id">
                                    <a :href="'<?= Router::url('/customer-orders/edit?id=') ?>' + orderDetail.id"
                                       class="card p-4 hover:shadow-md transition" style="text-decoration:none;display:flex;align-items:center;gap:12px;border:1.5px solid #bfdbfe;background:#eff6ff;border-radius:14px;">
                                        <div style="width:42px;height:42px;border-radius:12px;background:#2563eb;color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i data-lucide="edit-3" style="width:20px;height:20px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:800;font-size:13.5px;color:#1e3a8a;">Edit Rincian Pesanan</div>
                                            <div style="font-size:11px;color:#3b82f6;margin-top:2px;">Ubah item produk, jumlah kuantiti, dan parameter faktur</div>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </template>
                        <?php endif; ?>

                        <?php if (Auth::can('orders.retry_delivery')): ?>
                        <template x-if="orderDetail && ['gagal_dikirim', 'gagal_kembali', 'gagal_kirim'].includes(orderDetail.status_pemrosesan)">
                            <div class="card p-4" style="border:1.5px solid #fecaca;background:#fff1f2;border-radius:14px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div style="width:42px;height:42px;border-radius:12px;background:#e11d48;color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i data-lucide="rotate-cw" style="width:20px;height:20px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:800;font-size:13.5px;color:#9f1239;">Jadwalkan Kirim Ulang Pesanan Gagal</div>
                                        <div style="font-size:11.5px;color:#be123c;margin-top:2px;">
                                            Kembalikan status pesanan ke "Siap Dikirim" agar dapat diterbitkan Surat Jalan baru di menu Logistik.
                                        </div>
                                    </div>
                                </div>
                                <form action="<?= Router::url('/customer-orders/retry-delivery') ?>" method="POST"
                                      data-confirm="Jadwalkan ulang pesanan ini untuk pengiriman? Status pesanan akan menjadi 'Siap Dikirim'."
                                      data-confirm-title="Kirim Ulang Pesanan"
                                      data-confirm-type="info"
                                      data-confirm-btn="Ya, Jadwalkan Kirim Ulang">
                                    <input type="hidden" name="order_id" :value="orderDetail?.id">
                                    <button type="submit" class="btn btn-sm" style="background:#e11d48;color:#ffffff;font-weight:800;border-radius:10px;padding:9px 18px;display:inline-flex;align-items:center;gap:6px;">
                                        <i data-lucide="truck" style="width:15px;height:15px;"></i>
                                        <span>Jadwalkan Kirim Ulang</span>
                                    </button>
                                </form>
                            </div>
                        </template>
                        <?php endif; ?>
                    </div>

                    <!-- Batalkan & Hapus Transaksi (Danger Area) -->
                    <div style="padding:16px;background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.22);border-radius:14px;margin-top:4px;">
                        <div style="font-size:13px;font-weight:800;color:#ef4444;margin-bottom:4px;display:flex;align-items:center;gap:6px;">
                            <i data-lucide="alert-triangle" style="width:16px;height:16px;"></i>
                            <span>Zona Bahaya: Batalkan &amp; Hapus Faktur</span>
                        </div>
                        <p style="font-size:12px;color:var(--color-ink-secondary);line-height:1.45;margin-bottom:12px;">
                            Membatalkan faktur ini akan secara otomatis <strong>mengembalikan seluruh stok produk ke gudang</strong>.
                        </p>
                        <form action="<?= Router::url('/customer-orders/cancel') ?>" method="POST"
                              data-confirm="Apakah Anda YAKIN ingin membatalkan transaksi faktur ini? Seluruh stok produk akan otomatis dikembalikan ke gudang!"
                              data-confirm-title="Batalkan & Hapus Transaksi Faktur"
                              data-confirm-type="danger"
                              data-confirm-btn="Ya, Batalkan Transaksi">
                            <input type="hidden" name="id" :value="orderDetail?.id">
                            <button type="submit" class="btn btn-danger w-full sm:w-auto" style="padding:9px 18px;font-size:12.5px;font-weight:700;border-radius:10px;">
                                <i data-lucide="trash-2"></i>
                                <span>Batalkan &amp; Hapus Transaksi</span>
                            </button>
                        </form>
                    </div>
                </div>

            </div>

        </div>
    </div>
    </template>

</div>

<script>
function salesOrderListApp() {
    return {
        showDetailModal: false,
        activeTab: 'items',
        loadingDetail: false,
        orderDetail: null,
        orderItems: [],
        masterDrivers: <?= json_encode($drivers ?? []) ?>,
        masterCashAccounts: <?= json_encode($cashAccounts ?? []) ?>,

        shippingForm: {
            driver_id: '',
            status: 'sedang_dikirim',
            nopol: ''
        },

        paymentForm: {
            akun_kas_id: '<?= !empty($cashAccounts) ? $cashAccounts[0]['id'] : '' ?>',
            nominal_bayar: '0',
            tanggal_bayar: '<?= date('Y-m-d') ?>',
            keterangan: 'Pelunasan Faktur Toko'
        },

        init() {
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        async openOrderDetail(order) {
            this.orderDetail = order;
            this.orderItems = [];
            this.activeTab = 'items';
            this.loadingDetail = true;
            this.showDetailModal = true;

            const sisa = Math.max(0, Number(order.total_netto || 0) - Number(order.total_dibayar || 0));
            this.shippingForm = {
                driver_id: order.sales_driver_id || '',
                status: order.status_surat_jalan || 'disetujui_owner',
                nopol: order.nopol_driver || ''
            };
            this.paymentForm = {
                akun_kas_id: order.akun_kas_id || (this.masterCashAccounts.length > 0 ? this.masterCashAccounts[0].id : ''),
                nominal_bayar: String(sisa),
                tanggal_bayar: new Date().toISOString().split('T')[0],
                keterangan: 'Pelunasan Faktur Toko ' + (order.nomor_nota || '')
            };

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });

            try {
                const res = await fetch('<?= Router::url('/customer-orders/detail-ajax?id=') ?>' + order.id);
                const data = await res.json();
                if (data.success) {
                    this.orderDetail = data.order;
                    this.orderItems = data.items || [];
                    if (data.drivers) this.masterDrivers = data.drivers;
                    if (data.cashAccounts) this.masterCashAccounts = data.cashAccounts;
                    
                    this.shippingForm.driver_id = data.order.sales_driver_id || '';
                    this.shippingForm.status = data.order.status_surat_jalan || 'disetujui_owner';
                    this.syncDriverNopol();
                }
            } catch (err) {
                console.error('Failed to load order detail:', err);
            } finally {
                this.loadingDetail = false;
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        syncDriverNopol() {
            const found = this.masterDrivers.find(d => String(d.id) === String(this.shippingForm.driver_id));
            this.shippingForm.nopol = found ? (found.nomor_polisi_kendaraan || 'Tidak ada nopol') : '';
        },

        calcSisaTagihan() {
            if (!this.orderDetail) return 0;
            const netto = Number(this.orderDetail.total_netto || 0);
            const dibayar = Number(this.orderDetail.total_dibayar || 0);
            return Math.max(0, netto - dibayar);
        },

        cleanWa(wa) {
            if (!wa) return '';
            let cleaned = String(wa).replace(/[^0-9]/g, '');
            if (cleaned.startsWith('0')) {
                cleaned = '62' + cleaned.substring(1);
            }
            return cleaned;
        },

        formatRupiah(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
        },

        formatDateShort(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            return isNaN(d.getTime()) ? dateStr : d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },

        formatTipeBayar(t) {
            if (!t) return 'Tunai';
            return String(t).replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        formatDateFull(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            return isNaN(d.getTime()) ? dateStr : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

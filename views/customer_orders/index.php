<?php
use App\Core\Router;
use App\Core\Auth;
use App\Helpers\Format;
ob_start();
?>

<style>
/* ========================================================================= */
/* TAB SECTION CARDS (MODAL LOG AKTIVITAS & TRACKING)                        */
/* ========================================================================= */
.tab-section-card,
.tracking-timeline-box {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 18px;
    box-shadow: 0 2px 10px -2px rgba(15, 23, 42, 0.05), 0 1px 3px rgba(15, 23, 42, 0.02);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: relative;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.tab-section-header {
    padding: 14px 20px;
    background: var(--color-canvas-soft, #f8fafc);
    border-bottom: 1px solid var(--color-hairline, #e2e8f0);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
.tab-section-body {
    padding: 20px;
    flex: 1;
}

/* Minimalist Contextual Alerts */
.tracking-alert {
    border-radius: 12px;
    padding: 10px 14px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid transparent;
}
.tracking-alert-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.tracking-alert-icon i, .tracking-alert-icon svg {
    width: 17px;
    height: 17px;
}
.tracking-alert-body {
    flex: 1;
    min-width: 0;
}
.tracking-alert-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 6px;
}
.tracking-alert-title {
    font-size: 12.5px;
    font-weight: 800;
}
.tracking-alert-desc {
    font-size: 11.5px;
    margin: 2px 0 0 0;
    line-height: 1.4;
}

/* Alert Variants */
.alert-danger {
    background: #fef2f2;
    border-color: #fecaca;
}
.alert-danger .tracking-alert-icon { background: #fee2e2; color: #dc2626; }
.alert-danger .tracking-alert-title { color: #991b1b; }
.alert-danger .tracking-alert-desc { color: #b91c1c; }

.alert-info {
    background: #eff6ff;
    border-color: #bfdbfe;
}
.alert-info .tracking-alert-icon { background: #dbeafe; color: #2563eb; }
.alert-info .tracking-alert-title { color: #1e40af; }
.alert-info .tracking-alert-desc { color: #1d4ed8; }

.alert-success {
    background: #ecfdf5;
    border-color: #a7f3d0;
}
.alert-success .tracking-alert-icon { background: #d1fae5; color: #059669; }
.alert-success .tracking-alert-title { color: #065f46; }
.alert-success .tracking-alert-desc { color: #047857; }

.alert-warning {
    background: #eef2ff;
    border-color: #c7d2fe;
}
.alert-warning .tracking-alert-icon { background: rgba(99, 102, 241, 0.15); color: #4f46e5; }
.alert-warning .tracking-alert-title { color: #3730a3; }
.alert-warning .tracking-alert-desc { color: #4338ca; }

.alert-muted {
    background: #f8fafc;
    border-color: #e2e8f0;
}
.alert-muted .tracking-alert-icon { background: #e2e8f0; color: #64748b; }
.alert-muted .tracking-alert-title { color: #334155; }
.alert-muted .tracking-alert-desc { color: #475569; }

/* Stepper Node Circle */
.timeline-node-circle {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 2;
    background: var(--color-canvas, #ffffff);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    transition: all 0.25s ease;
    flex-shrink: 0;
}

.timeline-node-circle.is-done {
    background: #10b981;
    color: #ffffff;
    border: 3px solid #ffffff;
    box-shadow: 0 0 0 2px #10b981;
}
.timeline-node-circle.is-active {
    background: #2563eb;
    color: #ffffff;
    border: 3px solid #ffffff;
    box-shadow: 0 0 0 2px #2563eb;
    animation: pulseRingActive 2s infinite;
}
.timeline-node-circle.is-failed {
    background: #ef4444;
    color: #ffffff;
    border: 3px solid #ffffff;
    box-shadow: 0 0 0 2px #ef4444;
    animation: pulseRingDanger 2s infinite;
}
.timeline-node-circle.is-cancelled {
    background: #64748b;
    color: #ffffff;
    border: 3px solid #ffffff;
    box-shadow: 0 0 0 2px #64748b;
}
.timeline-node-circle.is-pending {
    background: var(--color-canvas-soft, #f8fafc);
    color: #94a3b8;
    border: 2px dashed #cbd5e1;
}

@keyframes pulseRingActive {
    0% { box-shadow: 0 0 0 2px #2563eb, 0 0 0 0 rgba(37, 99, 235, 0.4); }
    70% { box-shadow: 0 0 0 2px #2563eb, 0 0 0 6px rgba(37, 99, 235, 0); }
    100% { box-shadow: 0 0 0 2px #2563eb, 0 0 0 0 rgba(37, 99, 235, 0); }
}

@keyframes pulseRingDanger {
    0% { box-shadow: 0 0 0 2px #ef4444, 0 0 0 0 rgba(239, 68, 68, 0.4); }
    70% { box-shadow: 0 0 0 2px #ef4444, 0 0 0 6px rgba(239, 68, 68, 0); }
    100% { box-shadow: 0 0 0 2px #ef4444, 0 0 0 0 rgba(239, 68, 68, 0); }
}

/* ========================================================================= */
/* DESKTOP HORIZONTAL STEPPER                                                */
/* ========================================================================= */
.timeline-track-desktop {
    display: none;
}
@media (min-width: 641px) {
    .timeline-track-desktop {
        display: block;
        margin: 16px 0 8px 0;
    }
}

.timeline-h-container {
    display: flex;
    align-items: stretch;
    width: 100%;
    gap: 12px;
}

.timeline-h-step {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.timeline-h-rail {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    height: 42px;
    margin-bottom: 8px;
}

.timeline-h-span-line {
    position: absolute;
    top: 50%;
    left: 50%;
    width: calc(100% + 12px);
    height: 3px;
    transform: translateY(-50%);
    background: #e2e8f0;
    z-index: 1;
}

.timeline-h-card {
    width: 100%;
    flex: 1;
    display: flex;
    flex-direction: column;
    background: var(--color-canvas-soft, #f8fafc);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 12px;
    padding: 10px 10px;
    text-align: left;
    transition: all 0.2s ease;
}

.timeline-h-card.card-active {
    background: #eff6ff;
    border-color: #bfdbfe;
}
.timeline-h-card.card-failed {
    background: #fef2f2;
    border-color: #fecaca;
}

.timeline-step-title {
    font-size: 12px;
    font-weight: 800;
    color: var(--color-ink);
    line-height: 1.25;
}
.timeline-step-badge {
    font-size: 9.5px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 5px;
    white-space: nowrap;
}
.timeline-step-time {
    font-size: 10.5px;
    font-weight: 600;
    color: var(--color-ink-mute);
    font-family: monospace;
    margin: 3px 0;
}
.timeline-step-note {
    font-size: 11px;
    color: var(--color-ink-secondary);
    line-height: 1.35;
    margin-top: auto;
}

/* ========================================================================= */
/* MOBILE VERTICAL STEPPER                                                   */
/* ========================================================================= */
.timeline-track-mobile {
    display: block;
    margin: 14px 0 4px 0;
}
@media (min-width: 641px) {
    .timeline-track-mobile {
        display: none;
    }
}

.timeline-v-container {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.timeline-v-item {
    display: flex;
    align-items: stretch;
    gap: 12px;
}

.timeline-v-rail {
    width: 34px;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
}

.timeline-v-rail .timeline-node-circle {
    width: 34px;
    height: 34px;
}

.timeline-v-line {
    width: 3px;
    flex: 1;
    min-height: 18px;
    background: #e2e8f0;
    border-radius: 999px;
    margin: 0;
}

.timeline-v-card {
    flex: 1;
    min-width: 0;
    margin-bottom: 12px;
    background: var(--color-canvas-soft, #f8fafc);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: 12px;
    padding: 10px 12px;
    display: flex;
    flex-direction: column;
}

.timeline-v-item:last-child .timeline-v-card {
    margin-bottom: 0;
}

.timeline-v-card.card-active {
    background: #eff6ff;
    border-color: #bfdbfe;
}
.timeline-v-card.card-failed {
    background: #fef2f2;
    border-color: #fecaca;
}

/* Dark Mode Adjustments */
.dark .timeline-node-circle.is-done,
.dark .timeline-node-circle.is-active,
.dark .timeline-node-circle.is-failed,
.dark .timeline-node-circle.is-cancelled {
    border-color: #0f172a;
}
.dark .timeline-node-circle.is-pending {
    background: var(--color-canvas, #0f172a);
    border-color: rgba(255, 255, 255, 0.2);
    color: #64748b;
}
.dark .timeline-h-span-line,
.dark .timeline-v-line {
    background: rgba(255, 255, 255, 0.1);
}
.dark .timeline-h-card,
.dark .timeline-v-card {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.08);
}
.dark .timeline-h-card.card-active,
.dark .timeline-v-card.card-active {
    background: rgba(37, 99, 235, 0.12);
    border-color: rgba(37, 99, 235, 0.3);
}
.dark .timeline-h-card.card-failed,
.dark .timeline-v-card.card-failed {
    background: rgba(239, 68, 68, 0.12);
    border-color: rgba(239, 68, 68, 0.3);
}
.dark .tab-section-card,
.dark .tracking-timeline-box {
    background: var(--color-canvas, #0f172a);
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
}
.dark .tab-section-header {
    background: rgba(255, 255, 255, 0.02);
    border-bottom-color: rgba(255, 255, 255, 0.08);
}
.dark .alert-danger { background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.25); }
.dark .alert-danger .tracking-alert-desc { color: #fca5a5; }
.dark .alert-info { background: rgba(37, 99, 235, 0.1); border-color: rgba(37, 99, 235, 0.25); }
.dark .alert-info .tracking-alert-desc { color: #93c5fd; }
.dark .alert-success { background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.25); }
.dark .alert-success .tracking-alert-desc { color: #6ee7b7; }
.dark .alert-warning { background: rgba(99, 102, 241, 0.1); border-color: rgba(99, 102, 241, 0.25); }
.dark .alert-warning .tracking-alert-desc { color: #a5b4fc; }
.dark .alert-muted { background: rgba(100, 116, 139, 0.1); border-color: rgba(100, 116, 139, 0.25); }
.dark .alert-muted .tracking-alert-desc { color: #cbd5e1; }
</style>

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
                <h1 class="page-title"><?= $pageTitle ?? 'Pesanan Pelanggan' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Daftar Transaksi & Faktur Penjualan Toko Mitra' ?></p>
            </div>
        </div>
        <div class="page-header-actions" style="display:flex; gap:8px;">
            <a href="<?= Router::url('/customer-orders/export/excel?' . http_build_query($filter)) ?>" class="btn btn-secondary" style="font-weight:700; background:#10b981; color:#fff; border-color:#059669;">
                <i data-lucide="file-spreadsheet"></i>
                <span>Export Excel</span>
            </a>
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
        <form action="<?= Router::url('/customer-orders') ?>" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-3 items-end">
            <div>
                <label class="form-label" style="font-size:11px;">Cari No. Nota / Toko</label>
                <input type="text" name="q" value="<?= htmlspecialchars($filter['q'] ?? '') ?>" placeholder="Ketik nota / toko..." class="form-input" style="height:36px;font-size:12.5px;">
            </div>

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
                        $isKonsinyasiOrder = ($o['tipe_pembayaran'] === 'konsinyasi') || !empty($o['is_konsinyasi']) || (isset($o['adalah_tagihan']) && ($o['adalah_tagihan'] === false || $o['adalah_tagihan'] === 'f' || $o['adalah_tagihan'] === 0 || $o['adalah_tagihan'] === 'false'));
                        $isLunas = ($o['status_pembayaran'] === 'lunas');
                        $sisa = $isKonsinyasiOrder ? 0 : max(0, (float)$o['total_netto'] - (float)$o['total_dibayar']);
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
                        <td class="cell-nowrap">
                            <?php if (!empty($o['nama_sales'])): ?>
                            <div style="font-weight:700;font-size:12.5px;color:var(--color-ink);">
                                <?= htmlspecialchars($o['nama_sales']) ?>
                            </div>
                            <div class="font-mono" style="font-size:11px;color:var(--color-ink-mute);">
                                <?= htmlspecialchars($o['nopol_driver'] ?: 'Armada Toko') ?>
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
                                <?php if ($isKonsinyasiOrder): ?>
                                <span style="color:#f59e0b;">🏪 Titip Jual (Konsinyasi)</span>
                                <?php elseif ($o['tipe_pembayaran'] === 'cash'): ?>
                                <span style="color:#10b981;">💵 Tunai</span>
                                <?php elseif ($o['tipe_pembayaran'] === 'qris'): ?>
                                <span style="color:#3b82f6;">📱 QRIS</span>
                                <?php elseif ($o['tipe_pembayaran'] === 'transfer'): ?>
                                <span style="color:#6366f1;">🏦 Transfer Bank</span>
                                <?php elseif ($o['tipe_pembayaran'] === 'sebagian'): ?>
                                <span style="color:#8b5cf6;">💳 DP / Sebagian</span>
                                <?php else: ?>
                                <span style="color:#d97706;">⏱️ <?= str_replace('_', ' ', $o['tipe_pembayaran']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($isKonsinyasiOrder): ?>
                            <div style="font-size:11px;margin-top:2px;color:var(--color-ink-mute);">
                                Non-Tagihan Langsung
                            </div>
                            <?php elseif (!empty($o['tanggal_jatuh_tempo']) && !$isLunas): 
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
                            <?php if ($isKonsinyasiOrder): ?>
                            <div class="font-mono text-ink-secondary" style="font-size:12.5px;font-weight:700;">
                                Titip Jual (Rp 0)
                            </div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);">
                                Tagih saat Opname
                            </div>
                            <?php elseif ($isLunas): ?>
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
                            <?php if ($isKonsinyasiOrder): ?>
                            <span class="badge" style="background:#fef3c7;color:#92400e;font-size:10.5px;font-weight:800;border:1px solid #fde68a;">
                                TITIP RAK (NON-TAGIHAN)
                            </span>
                            <?php elseif ($isLunas): ?>
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
                                <?php if (Auth::can(['orders.edit_all', 'orders.edit_assigned'])): ?>
                                    <?php if (($o['status_pemrosesan'] ?? '') === 'po'): ?>
                                    <a href="<?= Router::url('/customer-orders/edit?id=' . urlencode($o['id'])) ?>" 
                                       class="btn btn-secondary btn-sm"
                                       style="padding:6px 10px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:4px;border-radius:var(--rounded-md);color:#2563eb;background:#eff6ff;border:1px solid #bfdbfe;"
                                       title="Edit Pesanan (Tahap PO)">
                                        <i data-lucide="edit-3" style="width:13px;height:13px;"></i>
                                        <span>Edit</span>
                                    </a>
                                    <?php elseif (!in_array($o['status_pemrosesan'] ?? '', ['gagal_dikirim', 'gagal_kembali', 'gagal_kirim'], true)): ?>
                                    <button type="button" 
                                            class="btn btn-secondary btn-sm"
                                            style="padding:6px 10px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:4px;border-radius:var(--rounded-md);color:#94a3b8;background:#f8fafc;border:1px solid #e2e8f0;cursor:not-allowed;opacity:0.75;"
                                            title="Terkunci: Status <?= strtoupper(str_replace('_', ' ', $o['status_pemrosesan'] ?? '')) ?>"
                                            onclick="window.AppAlert ? window.AppAlert({ title: 'Edit Pesanan Terkunci', message: 'Pesanan ini sudah diproses ke tahap <?= strtoupper(str_replace('_', ' ', $o['status_pemrosesan'] ?? '')) ?> (bukan draf PO).\n\nPesanan yang sudah diproses gudang / siap kirim tidak dapat diedit kembali.', type: 'warning', icon: 'lock' }) : alert('Pesanan sudah diproses gudang / siap kirim.')">
                                        <i data-lucide="lock" style="width:13px;height:13px;color:#94a3b8;"></i>
                                        <span>Edit</span>
                                    </button>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (in_array($o['status_pemrosesan'] ?? '', ['gagal_dikirim', 'gagal_kembali', 'gagal_kirim'], true) && Auth::can('orders.retry_delivery')): ?>
                                <button type="button" 
                                        @click="openRetryModal(<?= htmlspecialchars(json_encode($o)) ?>)"
                                        class="btn btn-sm" 
                                        style="padding:6px 10px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:4px;border-radius:var(--rounded-md);color:#ffffff;background:#e11d48;" 
                                        title="Jadwalkan Kirim Ulang (Batas 7 Hari)">
                                    <i data-lucide="rotate-cw" style="width:13px;height:13px;"></i>
                                    <span>Kirim Ulang</span>
                                </button>
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
                            <template x-if="orderDetail?.tipe_pembayaran === 'konsinyasi' || orderDetail?.is_konsinyasi || orderDetail?.adalah_tagihan === false || orderDetail?.adalah_tagihan === 'false'">
                                <span class="badge" style="background:rgba(225,29,72,0.1);color:#e11d48;border:1px solid rgba(225,29,72,0.22);font-weight:800;font-size:10px;padding:2px 8px;border-radius:6px;text-transform:uppercase;">
                                    Titip Jual (Konsinyasi)
                                </span>
                            </template>
                            <template x-if="orderDetail?.tipe_pembayaran !== 'konsinyasi' && !orderDetail?.is_konsinyasi && orderDetail?.adalah_tagihan !== false && orderDetail?.adalah_tagihan !== 'false'">
                                <span class="badge" 
                                      :class="orderDetail?.status_pembayaran === 'lunas' ? 'badge-success' : 'badge-warning'" 
                                      style="font-size:10px;font-weight:800;text-transform:uppercase;padding:2px 8px;border-radius:6px;" 
                                      x-text="orderDetail?.status_pembayaran === 'lunas' ? 'LUNAS' : 'BELUM LUNAS'"></span>
                            </template>
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
                <button type="button" @click="activeTab = 'payment'; $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });" class="modal-tab-btn" :class="{ 'is-active': activeTab === 'payment' }">
                    <i data-lucide="credit-card" style="width:14px;height:14px;"></i>
                    <span>Pembayaran</span>
                    <template x-if="calcSisaTagihan() > 0 && !['gagal_dikirim', 'gagal_kembali', 'gagal_kirim'].includes(orderDetail?.status_pemrosesan)">
                        <span class="badge badge-danger" style="font-size:9.5px;padding:1px 5px;border-radius:8px;">Sisa</span>
                    </template>
                    <template x-if="calcSisaTagihan() > 0 && ['gagal_dikirim', 'gagal_kembali', 'gagal_kirim'].includes(orderDetail?.status_pemrosesan)">
                        <span class="badge" style="font-size:9.5px;padding:1px 5px;border-radius:8px;background:#fee2e2;color:#991b1b;border:1px solid #fecdd3;">Tertunda</span>
                    </template>
                </button>
                <button type="button" @click="activeTab = 'actions'" class="modal-tab-btn" :class="{ 'is-active': activeTab === 'actions' }">
                    <i data-lucide="file-text" style="width:14px;height:14px;"></i>
                    <span>Dokumen &amp; Aksi</span>
                </button>
                <button type="button" @click="activeTab = 'activity'; $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });" class="modal-tab-btn" :class="{ 'is-active': activeTab === 'activity' }">
                    <i data-lucide="history" style="width:14px;height:14px;"></i>
                    <span>Log Aktivitas</span>
                    <template x-if="activityLogs && activityLogs.length > 0">
                        <span class="badge" style="font-size:10px;padding:1px 6px;border-radius:10px;background:rgba(99,102,241,0.1);color:#6366f1;" x-text="activityLogs.length"></span>
                    </template>
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
                <div x-show="!loadingDetail && activeTab === 'shipping'" style="display:flex;flex-direction:column;gap:20px;">
                    
                    <div style="padding:16px 18px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;display:flex;flex-direction:column;gap:5px;margin-bottom:4px;">
                        <div style="font-size:10px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;">Tujuan Pengiriman:</div>
                        <div style="font-size:14.5px;font-weight:800;color:var(--color-ink);" x-text="orderDetail?.nama_toko"></div>
                        <div style="font-size:12px;color:var(--color-ink-secondary);line-height:1.45;" x-text="orderDetail?.alamat_lengkap || 'Alamat toko belum diatur lengkap di master pelanggan.'"></div>
                    </div>

                    <!-- Dua Kolom Informasi Driver & Armada -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" style="gap:16px;row-gap:16px;column-gap:16px;margin-bottom:4px;">
                        <!-- Kolom Driver -->
                        <div style="padding:16px 18px;background:var(--color-canvas);border:1px solid var(--color-hairline-strong);border-radius:12px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                            <div style="width:38px;height:38px;border-radius:10px;background:rgba(37,99,235,0.1);color:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="user-check" style="width:18px;height:18px;"></i>
                            </div>
                            <div style="min-width:0;flex:1;">
                                <div style="font-size:10.5px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;">
                                    Driver / Armada Pengantar
                                </div>
                                <div style="font-size:13.5px;font-weight:700;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" 
                                     :style="orderDetail?.nama_sales ? 'color:var(--color-ink);' : 'color:var(--color-ink-mute);font-style:italic;font-weight:600;'"
                                     x-text="orderDetail?.nama_sales || 'Belum ditugaskan (Tahap PO)'"></div>
                            </div>
                        </div>

                        <!-- Kolom Plat Kendaraan -->
                        <div style="padding:16px 18px;background:var(--color-canvas);border:1px solid var(--color-hairline-strong);border-radius:12px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                            <div style="width:38px;height:38px;border-radius:10px;background:rgba(99,102,241,0.1);color:#6366f1;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="truck" style="width:18px;height:18px;"></i>
                            </div>
                            <div style="min-width:0;flex:1;">
                                <div style="font-size:10.5px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;">
                                    Nomor Polisi Kendaraan
                                </div>
                                <div class="font-mono" style="font-size:13.5px;font-weight:700;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" 
                                     :style="orderDetail?.nopol_driver ? 'color:var(--color-ink);' : 'color:var(--color-ink-mute);'"
                                     x-text="orderDetail?.nopol_driver || '-'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Pengiriman Box -->
                    <div style="padding:18px 20px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;display:flex;flex-direction:column;gap:14px;">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                            <div>
                                <div style="font-size:10.5px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:4px;">
                                    Status Pengiriman Saat Ini
                                </div>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="display:inline-flex;align-items:center;">
                                        <i data-lucide="clock" style="width:18px;height:18px;color:#d97706;" x-show="!orderDetail?.status_surat_jalan && (orderDetail?.status_pemrosesan === 'po' || !orderDetail?.status_pemrosesan)"></i>
                                        <i data-lucide="truck" style="width:18px;height:18px;color:#4f46e5;" x-show="orderDetail?.status_surat_jalan === 'dalam_perjalanan' || orderDetail?.status_pemrosesan === 'sedang_dikirim'"></i>
                                        <i data-lucide="check-circle-2" style="width:18px;height:18px;color:#059669;" x-show="orderDetail?.status_surat_jalan === 'selesai_diterima' || orderDetail?.status_pemrosesan === 'selesai'"></i>
                                        <i data-lucide="package" style="width:18px;height:18px;color:#2563eb;" x-show="orderDetail?.status_surat_jalan && orderDetail?.status_surat_jalan !== 'selesai_diterima' && orderDetail?.status_surat_jalan !== 'dalam_perjalanan'"></i>
                                    </span>
                                    <span style="font-size:14.5px;font-weight:800;color:var(--color-ink);"
                                          x-text="orderDetail?.status_pemrosesan === 'po' && !orderDetail?.status_surat_jalan
                                            ? 'Draf PO (Menunggu Proses Gudang)'
                                            : (orderDetail?.status_surat_jalan ? orderDetail.status_surat_jalan.replace(/_/g, ' ').toUpperCase() : 'Menunggu Proses Gudang')"></span>
                                </div>
                            </div>

                            <div>
                                <span class="badge" style="font-size:11px;font-weight:800;padding:5px 12px;border-radius:6px;display:inline-flex;align-items:center;gap:6px;"
                                      :style="orderDetail?.status_pemrosesan === 'po' && !orderDetail?.status_surat_jalan
                                        ? 'background:rgba(245,158,11,0.12);color:#b45309;border:1px solid rgba(245,158,11,0.25);'
                                        : (orderDetail?.status_surat_jalan === 'selesai_diterima'
                                            ? 'background:rgba(16,185,129,0.12);color:#047857;border:1px solid rgba(16,185,129,0.25);'
                                            : (orderDetail?.status_surat_jalan === 'dalam_perjalanan' || orderDetail?.status_pemrosesan === 'sedang_dikirim'
                                                ? 'background:rgba(99,102,241,0.12);color:#4338ca;border:1px solid rgba(99,102,241,0.25);'
                                                : 'background:rgba(37,99,235,0.12);color:#1d4ed8;border:1px solid rgba(37,99,235,0.25);'))">
                                    <span x-text="orderDetail?.status_pemrosesan === 'po' && !orderDetail?.status_surat_jalan ? 'Tahap 1: PO' : (orderDetail?.nomor_surat_jalan ? ('SJ: ' + orderDetail.nomor_surat_jalan) : 'Siap Kirim')"></span>
                                </span>
                            </div>
                        </div>

                        <div style="font-size:11.5px;color:var(--color-ink-secondary);line-height:1.45;padding-top:10px;border-top:1px dashed var(--color-hairline);display:flex;align-items:center;gap:7px;">
                            <i data-lucide="info" style="width:14px;height:14px;color:var(--color-ink-mute);flex-shrink:0;"></i>
                            <span>Wewenang penugasan driver &amp; surat jalan diproses pada menu <strong style="color:var(--color-ink);">Surat Jalan</strong> saat pesanan siap dikirim.</span>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: PEMBAYARAN & PELUNASAN -->
                <div x-show="!loadingDetail && activeTab === 'payment'" style="display:flex;flex-direction:column;gap:16px;">
                    <!-- Template Khusus Toko Konsinyasi (Non-Tagihan) -->
                    <template x-if="orderDetail?.tipe_pembayaran === 'konsinyasi' || orderDetail?.is_konsinyasi || orderDetail?.adalah_tagihan === false || orderDetail?.adalah_tagihan === 'false'">
                        <div style="display:flex;flex-direction:column;gap:16px;">
                            <!-- 3 Kartu Metrik Konsinyasi yang Selaras & Elegan -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                                <div style="padding:14px 16px;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:14px;display:flex;flex-direction:column;gap:6px;box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                                    <div style="display:flex;align-items:center;justify-content:space-between;">
                                        <span style="font-size:10.5px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;">Valuasi Titipan</span>
                                        <div style="width:28px;height:28px;border-radius:8px;background:rgba(99,102,241,0.1);color:#6366f1;display:flex;align-items:center;justify-content:center;">
                                            <i data-lucide="package" style="width:14px;height:14px;"></i>
                                        </div>
                                    </div>
                                    <div class="font-mono font-black" style="font-size:17px;color:var(--color-ink);" x-text="formatRupiah(orderDetail?.total_netto)"></div>
                                    <div style="font-size:11px;color:var(--color-ink-mute);">Estimasi nilai stok produk</div>
                                </div>

                                <div style="padding:14px 16px;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:14px;display:flex;flex-direction:column;gap:6px;box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                                    <div style="display:flex;align-items:center;justify-content:space-between;">
                                        <span style="font-size:10.5px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;">Skema Distribusi</span>
                                        <div style="width:28px;height:28px;border-radius:8px;background:rgba(225,29,72,0.1);color:#e11d48;display:flex;align-items:center;justify-content:center;">
                                            <i data-lucide="store" style="width:14px;height:14px;"></i>
                                        </div>
                                    </div>
                                    <div style="font-size:15px;font-weight:800;color:#e11d48;line-height:1.2;">Titip Jual Rak</div>
                                    <div style="font-size:11px;color:var(--color-ink-mute);">Stok rak toko konsinyasi</div>
                                </div>

                                <div style="padding:14px 16px;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:14px;display:flex;flex-direction:column;gap:6px;box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                                    <div style="display:flex;align-items:center;justify-content:space-between;">
                                        <span style="font-size:10.5px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;">Kewajiban Tagihan</span>
                                        <div style="width:28px;height:28px;border-radius:8px;background:rgba(16,185,129,0.1);color:#059669;display:flex;align-items:center;justify-content:center;">
                                            <i data-lucide="shield-check" style="width:14px;height:14px;"></i>
                                        </div>
                                    </div>
                                    <div style="font-size:15px;font-weight:800;color:#059669;line-height:1.2;">Non-Tagihan (Rp 0)</div>
                                    <div style="font-size:11px;color:var(--color-ink-mute);">Bukan piutang tempo langsung</div>
                                </div>
                            </div>

                            <!-- Card Informasi Penjelasan Konsinyasi (Modern Enterprise Notice) -->
                            <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:16px;padding:18px 20px;display:flex;flex-direction:column;gap:14px;box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding-bottom:12px;border-bottom:1px solid var(--color-hairline);">
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="width:34px;height:34px;border-radius:10px;background:rgba(225,29,72,0.1);color:#e11d48;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i data-lucide="info" style="width:18px;height:18px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-size:13.5px;font-weight:800;color:var(--color-ink);">Tata Kelola Skema Titip Jual (Konsinyasi)</div>
                                            <div style="font-size:11.5px;color:var(--color-ink-mute);">Pencatatan alokasi stok rak dan mekanisme pelunasan omzet</div>
                                        </div>
                                    </div>
                                    <span class="badge" style="background:rgba(225,29,72,0.08);color:#e11d48;border:1px solid rgba(225,29,72,0.2);font-weight:800;font-size:11px;padding:3px 9px;border-radius:999px;">
                                        Bebas Piutang Langsung
                                    </span>
                                </div>

                                <div style="display:grid;grid-template-columns:1fr;gap:10px;">
                                    <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;background:var(--color-canvas-soft);border-radius:12px;border:1px solid var(--color-hairline);">
                                        <div style="width:26px;height:26px;border-radius:7px;background:rgba(99,102,241,0.1);color:#6366f1;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                                            <i data-lucide="layers" style="width:14px;height:14px;"></i>
                                        </div>
                                        <div style="font-size:12px;color:var(--color-ink-secondary);line-height:1.5;">
                                            <strong style="color:var(--color-ink);">Pengiriman Stok Titipan:</strong> Pesanan ini bukan merupakan faktur tagihan langsung. Barang yang dikirim akan dialokasikan langsung ke <strong>Stok Rak Konsinyasi</strong> mitra toko sehingga tidak membebani kewajiban piutang toko.
                                        </div>
                                    </div>

                                    <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;background:var(--color-canvas-soft);border-radius:12px;border:1px solid var(--color-hairline);">
                                        <div style="width:26px;height:26px;border-radius:7px;background:rgba(16,185,129,0.1);color:#059669;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                                            <i data-lucide="receipt" style="width:14px;height:14px;"></i>
                                        </div>
                                        <div style="font-size:12px;color:var(--color-ink-secondary);line-height:1.5;">
                                            <strong style="color:var(--color-ink);">Penagihan Omzet Penjualan:</strong> Tagihan resmi omzet penjualan baru akan dihitung dan diterbitkan saat Sales melakukan kunjungan melalui menu <strong style="color:#2563eb;">Portal Konsinyasi &rarr; Form Opname Kunjungan Sales</strong> berdasarkan snack yang telah laku terjual.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Template Toko Reguler B2B (Faktur Tagihan) -->
                    <template x-if="orderDetail?.tipe_pembayaran !== 'konsinyasi' && !orderDetail?.is_konsinyasi && orderDetail?.adalah_tagihan !== false && orderDetail?.adalah_tagihan !== 'false'">
                        <div style="display:flex;flex-direction:column;gap:16px;">
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
                                <div style="font-size:12px;display:flex;align-items:center;gap:6px;">
                                    <span style="color:var(--color-ink-mute);">Status:</span>
                                    <span class="badge" :class="calcSisaTagihan() <= 0 ? 'badge-success' : 'badge-warning'" x-text="calcSisaTagihan() <= 0 ? 'LUNAS' : 'BELUM LUNAS'"></span>
                                </div>
                            </div>

                            <!-- BANNER STATUS JIKA SUDAH LUNAS SEPENUHNYA -->
                            <template x-if="calcSisaTagihan() <= 0">
                                <div style="padding:16px;background:rgba(16,185,129,0.08);border:1.5px solid rgba(16,185,129,0.3);border-radius:14px;display:flex;align-items:center;gap:14px;">
                                    <div style="width:42px;height:42px;border-radius:12px;background:rgba(16,185,129,0.18);color:#059669;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i data-lucide="check-check" style="width:24px;height:24px;"></i>
                                    </div>
                                    <div style="flex:1;">
                                        <div style="font-size:13.5px;font-weight:800;color:#065f46;">Faktur Ini Telah LUNAS Sepenuhnya</div>
                                        <div style="font-size:12px;color:#047857;margin-top:2px;line-height:1.4;">
                                            Seluruh tagihan sebesar <strong x-text="formatRupiah(orderDetail?.total_netto)"></strong> telah selesai dibayar dan dibukukan ke rekening kas/bank.
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- BANNER PERINGATAN JIKA STATUS GAGAL KIRIM -->
                            <template x-if="calcSisaTagihan() > 0 && ['gagal_dikirim', 'gagal_kembali', 'gagal_kirim'].includes(orderDetail?.status_pemrosesan)">
                                <div style="padding:18px 20px;background:#fff1f2;border:1.5px solid #fecdd3;border-radius:14px;display:flex;align-items:flex-start;gap:14px;">
                                    <div style="width:42px;height:42px;border-radius:12px;background:#ffe4e6;color:#e11d48;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                                        <i data-lucide="alert-triangle" style="width:22px;height:22px;"></i>
                                    </div>
                                    <div style="flex:1;">
                                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                            <span style="font-size:14px;font-weight:800;color:#9f1239;">Pembayaran Ditangguhkan: Pesanan Gagal Kirim</span>
                                            <span class="badge" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;font-size:10.5px;font-weight:800;padding:2px 8px;border-radius:6px;">STOK DI GUDANG</span>
                                        </div>
                                        <div style="font-size:12.5px;color:#be123c;margin-top:4px;line-height:1.5;">
                                            Pengiriman pesanan ini mengalami kendala dan berstatus <strong>Gagal Kirim</strong>. Seluruh stok fisik produk telah aman dikembalikan ke rak gudang. Untuk menjaga akurasi pembukuan kas &amp; kartu piutang toko, penginputan pelunasan tagihan ditangguhkan hingga pengiriman pesanan dijadwalkan ulang.
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- BANNER STATUS JIKA PESANAN DIBATALKAN -->
                            <template x-if="orderDetail?.status_pemrosesan === 'dibatalkan'">
                                <div style="padding:16px;background:rgba(239,68,68,0.08);border:1.5px solid rgba(239,68,68,0.25);border-radius:14px;display:flex;align-items:center;gap:14px;">
                                    <div style="width:42px;height:42px;border-radius:12px;background:rgba(239,68,68,0.15);color:#dc2626;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i data-lucide="x-circle" style="width:24px;height:24px;"></i>
                                    </div>
                                    <div style="flex:1;">
                                        <div style="font-size:13.5px;font-weight:800;color:#991b1b;">Pesanan Dibatalkan</div>
                                        <div style="font-size:12px;color:#b91c1c;margin-top:2px;line-height:1.4;">
                                            Pesanan ini telah dibatalkan. Pembayaran tagihan tidak dapat diproses untuk pesanan yang telah dibatalkan.
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- FORM CATAT BAYAR JIKA BELUM LUNAS DAN BUKAN STATUS GAGAL/BATAL -->
                            <template x-if="calcSisaTagihan() > 0 && !['gagal_dikirim', 'gagal_kembali', 'gagal_kirim', 'dibatalkan'].includes(orderDetail?.status_pemrosesan)">
                                <div style="padding:16px;background:var(--color-canvas);border:1.5px solid rgba(30,58,138,0.2);border-radius:14px;">
                                    <div style="font-size:13px;font-weight:800;color:var(--color-ink);margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                                        <i data-lucide="wallet" style="width:16px;height:16px;color:#1e3a8a;"></i>
                                        <span x-text="Number(paymentForm.nominal_bayar || 0) >= calcSisaTagihan() ? 'Input Pelunasan Tagihan' : 'Input Pembayaran Sebagian Tagihan'">Input Pembayaran / Pelunasan</span>
                                    </div>
                                    <form @submit.prevent="submitPayment()" action="<?= Router::url('/customer-orders/pay') ?>" method="POST" style="display:flex;flex-direction:column;gap:12px;">
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
                                                    <button type="button" @click="setPaymentLunas()" class="btn btn-secondary btn-sm" style="font-size:10.5px;padding:2px 8px;border-radius:6px;">
                                                        Bayar Lunas
                                                    </button>
                                                </div>
                                                <div style="position:relative;">
                                                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-weight:700;color:var(--color-ink-mute);font-size:12.5px;pointer-events:none;">Rp</span>
                                                    <input type="text"
                                                           inputmode="numeric"
                                                           x-model="paymentDisplay"
                                                           @input="onPaymentInput($event)"
                                                           required
                                                           placeholder="0"
                                                           class="form-input font-mono font-bold text-success input-rupiah"
                                                           style="height:40px;border-radius:10px;padding-left:36px;">
                                                    <input type="hidden" name="nominal_bayar" :value="paymentForm.nominal_bayar">
                                                </div>
                                            </div>

                                            <div>
                                                <label class="form-label font-bold" style="font-size:11.5px;">Tanggal Pembayaran *</label>
                                                <input type="date" name="tanggal_bayar" x-model="paymentForm.tanggal_bayar" required class="form-input font-mono" style="height:40px;border-radius:10px;">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="form-label font-bold" style="font-size:11.5px;">Keterangan / Referensi Bukti</label>
                                            <input type="text" name="keterangan" x-model="paymentForm.keterangan" placeholder="Contoh: Bukti Transfer BCA an Toko" class="form-input" style="height:40px;border-radius:10px;">
                                        </div>

                                        <div style="display:flex;justify-content:flex-end;margin-top:4px;">
                                            <button type="submit" :disabled="isSubmittingPayment" class="btn btn-primary" style="padding:9px 20px;font-size:12.5px;font-weight:800;border-radius:10px;background:#1e3a8a;border-color:#1e3a8a;">
                                                <i data-lucide="check-circle" style="width:14px;height:14px;"></i>
                                                <span x-text="Number(paymentForm.nominal_bayar || 0) >= calcSisaTagihan() ? 'Simpan Pelunasan Tagihan' : 'Simpan Pembayaran Sebagian Tagihan'">Simpan Pelunasan Tagihan</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </template>

                            <!-- SEKSI RIWAYAT PEMBAYARAN FAKTUR -->
                            <div style="margin-top:4px;">
                                <div style="font-size:12px;font-weight:800;color:var(--color-ink-secondary);text-transform:uppercase;letter-spacing:0.04em;display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <i data-lucide="history" style="width:14px;height:14px;color:var(--color-ink-mute);"></i>
                                        <span>Riwayat Pembayaran &amp; Arus Kas</span>
                                    </div>
                                    <span class="badge badge-neutral" style="font-size:10.5px;padding:2px 8px;" x-text="(paymentsList?.length || 0) + ' Transaksi'"></span>
                                </div>

                                <!-- Template Jika Belum Ada Pembayaran -->
                                <template x-if="!paymentsList || paymentsList.length === 0">
                                    <div style="padding:16px;text-align:center;background:var(--color-canvas-soft);border:1px dashed var(--color-hairline);border-radius:12px;font-size:12px;color:var(--color-ink-mute);">
                                        <i data-lucide="receipt" style="width:22px;height:22px;margin:0 auto 6px auto;opacity:0.5;display:block;"></i>
                                        Belum ada catatan transaksi pembayaran yang masuk untuk faktur ini.
                                    </div>
                                </template>

                                <!-- Template Jika Ada Pembayaran -->
                                <template x-if="paymentsList && paymentsList.length > 0">
                                    <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:12px;overflow:hidden;">
                                        <div class="table-responsive" style="max-height:220px;overflow-y:auto;">
                                            <table class="table" style="font-size:12px;margin:0;width:100%;">
                                                <thead>
                                                    <tr style="background:var(--color-canvas-soft);">
                                                        <th style="padding:8px 12px;width:95px;">Tanggal</th>
                                                        <th style="padding:8px 12px;">Akun Kas / Bank</th>
                                                        <th style="padding:8px 12px;text-align:right;">Nominal</th>
                                                        <th style="padding:8px 12px;">Keterangan / Bukti</th>
                                                        <th style="padding:8px 12px;">Pencatat</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="(p, idx) in paymentsList" :key="p.id || idx">
                                                        <tr style="border-top:1px solid var(--color-hairline);">
                                                            <td style="padding:8px 12px;white-space:nowrap;font-family:var(--font-mono);font-size:11.5px;" x-text="formatDateShort(p.tanggal_transaksi)"></td>
                                                            <td style="padding:8px 12px;">
                                                                <div style="display:flex;align-items:center;gap:6px;">
                                                                    <i data-lucide="credit-card" style="width:13px;height:13px;color:var(--color-primary);flex-shrink:0;"></i>
                                                                    <span style="font-weight:700;color:var(--color-ink);" x-text="p.akun_kas_nama || 'Kasir Toko'"></span>
                                                                </div>
                                                            </td>
                                                            <td style="padding:8px 12px;text-align:right;white-space:nowrap;">
                                                                <strong class="font-mono text-success" style="font-size:12.5px;" x-text="formatRupiah(p.nominal)"></strong>
                                                            </td>
                                                            <td style="padding:8px 12px;color:var(--color-ink-secondary);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" :title="p.keterangan" x-text="p.keterangan || '-'"></td>
                                                            <td style="padding:8px 12px;color:var(--color-ink-mute);font-size:11.5px;white-space:nowrap;" x-text="p.dicatat_oleh_nama || '-'"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </template>
                            </div>

                        </div>
                    </template>
                </div>

                <!-- TAB 4: DOKUMEN & AKSI OPERASIONAL -->
                <div x-show="!loadingDetail && activeTab === 'actions'" style="padding-top:4px;padding-bottom:10px;">
                    
                    <!-- GRUP 1: CETAK & BERKAS DOKUMEN FAKTUR -->
                    <div>
                        <div style="font-size:11px;font-weight:800;color:var(--color-ink-secondary);text-transform:uppercase;letter-spacing:0.05em;display:flex;align-items:center;gap:7px;padding:0 2px;margin-bottom:8px;">
                            <i data-lucide="printer" style="width:14px;height:14px;color:var(--color-ink-mute);"></i>
                            <span>Dokumen &amp; Salinan Faktur</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2" style="gap:12px;">
                            <!-- Unduh PDF Faktur -->
                            <a :href="'<?= Router::url('/customer-orders/invoice/pdf?id=') ?>' + orderDetail?.id" target="_blank"
                               class="card hover:shadow-md transition" style="text-decoration:none;display:flex;align-items:center;gap:14px;padding:14px 16px;border:1.5px solid rgba(220,38,38,0.22);border-radius:14px;background:#fef2f2;">
                                <div style="width:40px;height:40px;border-radius:12px;background:rgba(220,38,38,0.1);color:#dc2626;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i data-lucide="file-text" style="width:20px;height:20px;"></i>
                                </div>
                                <div style="min-width:0;flex:1;">
                                    <div style="font-weight:800;font-size:13.5px;color:#991b1b;">Unduh PDF Faktur</div>
                                    <div style="font-size:11.5px;color:#dc2626;margin-top:2px;line-height:1.4;">Dokumen PDF resmi siap cetak, arsip, &amp; bagikan</div>
                                </div>
                            </a>

                            <!-- Unduh Excel Rincian Faktur -->
                            <a :href="'<?= Router::url('/customer-orders/invoice/excel?id=') ?>' + orderDetail?.id" target="_blank"
                               class="card hover:shadow-md transition" style="text-decoration:none;display:flex;align-items:center;gap:14px;padding:14px 16px;border:1.5px solid rgba(16,185,129,0.22);border-radius:14px;background:#ecfdf5;">
                                <div style="width:40px;height:40px;border-radius:12px;background:rgba(16,185,129,0.1);color:#059669;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i data-lucide="file-spreadsheet" style="width:20px;height:20px;"></i>
                                </div>
                                <div style="min-width:0;flex:1;">
                                    <div style="font-weight:800;font-size:13.5px;color:#065f46;">Unduh Excel Faktur</div>
                                    <div style="font-size:11.5px;color:#059669;margin-top:2px;line-height:1.4;">Spreadsheet rincian pesanan &amp; item produk</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- GRUP 2: PENGENDALIAN & KEAMANAN TRANSAKSI -->
                    <div style="margin-top:26px;">
                        <div style="font-size:11px;font-weight:800;color:var(--color-ink-secondary);text-transform:uppercase;letter-spacing:0.05em;display:flex;align-items:center;gap:7px;padding:0 2px;margin-bottom:8px;">
                            <i data-lucide="shield-check" style="width:14px;height:14px;color:var(--color-ink-mute);"></i>
                            <span>Pengendalian &amp; Keamanan Transaksi</span>
                        </div>

                        <div class="grid grid-cols-1" style="gap:12px;">

                            <?php if (Auth::can(['orders.edit_all', 'orders.edit_assigned'])): ?>
                            <div class="w-full">
                                <!-- Status Draf PO: Tombol Edit Aktif -->
                                <!-- Status Draf PO / Gagal Dikirim: Tombol Edit Aktif -->
                                <template x-if="orderDetail && ['po', 'gagal_dikirim'].includes(orderDetail.status_pemrosesan)">
                                    <a :href="'<?= Router::url('/customer-orders/edit?id=') ?>' + orderDetail.id + (orderDetail.status_pemrosesan === 'gagal_dikirim' ? '&retry=1' : '')"
                                       class="card hover:shadow-md transition w-full" style="text-decoration:none;display:flex;align-items:center;gap:14px;padding:14px 16px;border:1.5px solid #bfdbfe;background:#eff6ff;border-radius:14px;width:100%;">
                                        <div style="width:40px;height:40px;border-radius:12px;background:#2563eb;color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i data-lucide="edit-3" style="width:20px;height:20px;"></i>
                                        </div>
                                        <div style="min-width:0;flex:1;">
                                            <div style="font-weight:800;font-size:13.5px;color:#1e3a8a;" x-text="orderDetail.status_pemrosesan === 'gagal_dikirim' ? 'Edit Pesanan &amp; Kirim Ulang' : 'Edit Rincian Pesanan (PO)'">Edit Rincian Pesanan</div>
                                            <div style="font-size:11.5px;color:#3b82f6;margin-top:2px;line-height:1.4;" x-text="orderDetail.status_pemrosesan === 'gagal_dikirim' ? 'Sesuaikan item produk sebelum dijadwalkan ulang ke antrean gudang' : 'Ubah item produk, jumlah kuantiti, dan parameter faktur'">Ubah item produk, jumlah kuantiti, dan parameter faktur</div>
                                        </div>
                                    </a>
                                </template>

                                <!-- Status Selain PO & Gagal Dikirim: Notifikasi Terkunci -->
                                <template x-if="orderDetail && !['po', 'gagal_dikirim'].includes(orderDetail.status_pemrosesan)">
                                    <div class="card w-full" style="padding:15px 16px;border:1.5px solid #e2e8f0;background:#f8fafc;border-radius:14px;display:flex;align-items:center;gap:14px;width:100%;">
                                        <div style="width:40px;height:40px;border-radius:12px;background:#94a3b8;color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i data-lucide="lock" style="width:19px;height:19px;"></i>
                                        </div>
                                        <div style="min-width:0;flex:1;">
                                            <div style="font-weight:800;font-size:13.5px;color:#475569;">Edit Pesanan Terkunci</div>
                                            <div style="font-size:11.5px;color:#64748b;margin-top:2px;line-height:1.45;">
                                                Pesanan sudah diproses ke tahap <span class="font-bold uppercase" x-text="orderDetail.status_pemrosesan?.replace(/_/g, ' ')"></span>. Hanya pesanan berstatus draf PO atau Gagal Kirim yang dapat diedit.
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <?php endif; ?>

                            <!-- Status Selesai: Pembatalan Terkunci (Full Width) -->
                            <template x-if="orderDetail && ['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail.status_pemrosesan)">
                                <div class="card w-full" style="padding:15px 16px;border:1.5px solid #e2e8f0;background:#f8fafc;border-radius:14px;display:flex;align-items:flex-start;gap:14px;width:100%;">
                                    <div style="width:40px;height:40px;border-radius:12px;background:#94a3b8;color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                                        <i data-lucide="lock" style="width:19px;height:19px;"></i>
                                    </div>
                                    <div style="min-width:0;flex:1;">
                                        <div style="font-size:13.5px;font-weight:800;color:#475569;">Pembatalan Transaksi Terkunci</div>
                                        <div style="font-size:11.5px;color:#64748b;margin-top:3px;line-height:1.45;">
                                            Pesanan ini sudah selesai diterima oleh toko mitra. Pembatalan langsung dikunci untuk menjaga integritas kas &amp; kartu piutang. Silakan proses melalui menu <strong>Retur Penjualan</strong> atau <strong>Opname Konsinyasi</strong>.
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Status Belum Selesai: Zona Bahaya Batalkan Transaksi (Full Width) -->
                            <template x-if="orderDetail && !['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail.status_pemrosesan)">
                                <div class="w-full col-span-full" style="padding:16px 18px;background:rgba(239,68,68,0.05);border:1.5px solid rgba(239,68,68,0.22);border-radius:14px;">
                                    <div style="font-size:13px;font-weight:800;color:#ef4444;margin-bottom:4px;display:flex;align-items:center;gap:6px;">
                                        <i data-lucide="alert-triangle" style="width:16px;height:16px;"></i>
                                        <span>Zona Bahaya: Batalkan &amp; Hapus Transaksi</span>
                                    </div>
                                    <p style="font-size:12px;color:var(--color-ink-secondary);line-height:1.45;margin-bottom:12px;">
                                        <template x-if="orderDetail.status_pemrosesan === 'po'">
                                            <span>Membatalkan draf PO ini akan menghapus antrean pesanan (stok fisik gudang belum dipotong).</span>
                                        </template>
                                        <template x-if="orderDetail.status_pemrosesan !== 'po'">
                                            <span>Membatalkan transaksi ini akan secara otomatis <strong>mengembalikan seluruh stok produk ke rak gudang</strong> (jika belum dikembalikan).</span>
                                        </template>
                                    </p>
                                    <form action="<?= Router::url('/customer-orders/cancel') ?>" method="POST"
                                          :data-confirm="orderDetail.status_pemrosesan === 'po'
                                              ? 'Apakah Anda YAKIN ingin membatalkan draf PO #' + orderDetail.nomor_nota + '? Antrean pesanan akan dihapus.'
                                              : 'Apakah Anda YAKIN ingin membatalkan transaksi #' + orderDetail.nomor_nota + '?'"
                                          data-confirm-title="Batalkan &amp; Hapus Transaksi"
                                          data-confirm-type="danger"
                                          data-confirm-btn="Ya, Batalkan Transaksi">
                                        <input type="hidden" name="id" :value="orderDetail?.id">
                                        <button type="submit" class="btn btn-danger w-full sm:w-auto" style="padding:9px 18px;font-size:12.5px;font-weight:700;border-radius:10px;">
                                            <i data-lucide="trash-2"></i>
                                            <span>Batalkan &amp; Hapus Transaksi</span>
                                        </button>
                                    </form>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: LOG AKTIVITAS & RIWAYAT PENGIRIMAN -->
                <div x-show="!loadingDetail && activeTab === 'activity'" class="space-y-6" style="display:flex;flex-direction:column;gap:24px;">
                    
                    <!-- 1. KARTU STATUS PERJALANAN PESANAN (JNE / SHOPEE STYLE TIMELINE) -->
                    <div class="tab-section-card tracking-timeline-box">
                        
                        <!-- Header Status Perjalanan -->
                        <div class="tab-section-header">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:34px;height:34px;border-radius:10px;background:rgba(99,102,241,0.12);color:#6366f1;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i data-lucide="navigation-2" style="width:18px;height:18px;"></i>
                                </div>
                                <div>
                                    <div style="font-size:13.5px;font-weight:900;color:var(--color-ink);letter-spacing:-0.01em;">Status Perjalanan Pesanan</div>
                                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">Kronologi tahapan logistik dan status transaksi pesanan</div>
                                </div>
                            </div>
                            
                            <!-- Dynamic Badge Status -->
                            <div>
                                <template x-if="orderDetail?.status_pemrosesan === 'gagal_dikirim'">
                                    <span class="badge flex items-center gap-1.5" style="font-size:10.5px;font-weight:800;padding:4px 10px;border-radius:999px;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;">
                                        <span style="width:6px;height:6px;border-radius:50%;background:#ef4444;" class="animate-ping"></span>
                                        <span>STATUS: GAGAL DIKIRIM</span>
                                    </span>
                                </template>
                                <template x-if="orderDetail?.status_pemrosesan === 'sedang_dikirim'">
                                    <span class="badge flex items-center gap-1.5" style="font-size:10.5px;font-weight:800;padding:4px 10px;border-radius:999px;background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;">
                                        <span style="width:6px;height:6px;border-radius:50%;background:#3b82f6;" class="animate-pulse"></span>
                                        <span>STATUS: SEDANG DIKIRIM</span>
                                    </span>
                                </template>
                                <template x-if="orderDetail?.status_pemrosesan === 'siap_dikirim' || orderDetail?.status_pemrosesan === 'siap_kirim'">
                                    <span class="badge flex items-center gap-1.5" style="font-size:10.5px;font-weight:800;padding:4px 10px;border-radius:999px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;">
                                        <span style="width:6px;height:6px;border-radius:50%;background:#f59e0b;"></span>
                                        <span>STATUS: SIAP DIKIRIM (GUDANG)</span>
                                    </span>
                                </template>
                                <template x-if="orderDetail?.status_pemrosesan === 'po'">
                                    <span class="badge flex items-center gap-1.5" style="font-size:10.5px;font-weight:800;padding:4px 10px;border-radius:999px;background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;">
                                        <span style="width:6px;height:6px;border-radius:50%;background:#64748b;"></span>
                                        <span>STATUS: ANTREAN PO GUDANG</span>
                                    </span>
                                </template>
                                <template x-if="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan)">
                                    <span class="badge flex items-center gap-1.5" style="font-size:10.5px;font-weight:800;padding:4px 10px;border-radius:999px;background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;">
                                        <span style="width:6px;height:6px;border-radius:50%;background:#10b981;"></span>
                                        <span>STATUS: SELESAI DITERIMA</span>
                                    </span>
                                </template>
                                <template x-if="orderDetail?.status_pemrosesan === 'dibatalkan'">
                                    <span class="badge flex items-center gap-1.5" style="font-size:10.5px;font-weight:800;padding:4px 10px;border-radius:999px;background:#ffe4e6;color:#9f1239;border:1px solid #fecdd3;">
                                        <span style="width:6px;height:6px;border-radius:50%;background:#e11d48;"></span>
                                        <span>STATUS: DIBATALKAN</span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Card Body for Tracking Timeline -->
                        <div class="tab-section-body" style="padding:20px;">
                            <!-- LIVE CONTEXTUAL TRACKING ALERT (MINIMALIST) -->
                            <div>
                            <!-- GAGAL KIRIM ALERT -->
                            <template x-if="orderDetail?.status_pemrosesan === 'gagal_dikirim'">
                                <div class="tracking-alert alert-danger">
                                    <div class="tracking-alert-icon">
                                        <i data-lucide="alert-triangle"></i>
                                    </div>
                                    <div class="tracking-alert-body">
                                        <div class="tracking-alert-head">
                                            <span class="tracking-alert-title">Kendala Pengiriman: Paket Gagal Dikirim</span>
                                            <span class="badge badge-danger" style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;" x-text="orderDetail?.waktu_gagal_kirim ? formatDate(orderDetail.waktu_gagal_kirim) : 'Baru saja'"></span>
                                        </div>
                                        <p class="tracking-alert-desc">
                                            Stok fisik produk otomatis dikembalikan ke rak gudang. Anda dapat menjadwalkan Kirim Ulang langsung melalui tabel pesanan.
                                        </p>
                                    </div>
                                </div>
                            </template>

                            <!-- SEDANG DIKIRIM ALERT -->
                            <template x-if="orderDetail?.status_pemrosesan === 'sedang_dikirim'">
                                <div class="tracking-alert alert-info">
                                    <div class="tracking-alert-icon">
                                        <i data-lucide="truck"></i>
                                    </div>
                                    <div class="tracking-alert-body">
                                        <div class="tracking-alert-head">
                                            <span class="tracking-alert-title">Paket Sedang Dalam Pengantaran Kurir</span>
                                            <span class="badge badge-primary" style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;" x-text="'Armada: ' + (orderDetail?.nopol_driver || 'Toko')"></span>
                                        </div>
                                        <p class="tracking-alert-desc">
                                            Driver <strong x-text="orderDetail?.nama_sales || 'Kurir Logistik'"></strong> sedang menuju alamat toko pelanggan. Rincian Surat Jalan tercatat di bawah.
                                        </p>
                                    </div>
                                </div>
                            </template>

                            <!-- SIAP DIKIRIM ALERT -->
                            <template x-if="orderDetail?.status_pemrosesan === 'siap_dikirim' || orderDetail?.status_pemrosesan === 'siap_kirim'">
                                <div class="tracking-alert alert-success">
                                    <div class="tracking-alert-icon">
                                        <i data-lucide="package-check"></i>
                                    </div>
                                    <div class="tracking-alert-body">
                                        <div class="tracking-alert-head">
                                            <span class="tracking-alert-title">Barang Siap Dikirim di Gudang</span>
                                            <span class="badge badge-success" style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;">Stok Terpotong</span>
                                        </div>
                                        <p class="tracking-alert-desc">
                                            Pesanan telah diverifikasi dan dipacking rapi oleh staf gudang. Menunggu pembuatan Surat Jalan logistik.
                                        </p>
                                    </div>
                                </div>
                            </template>

                            <!-- PO GUDANG ALERT -->
                            <template x-if="orderDetail?.status_pemrosesan === 'po'">
                                <div class="tracking-alert alert-warning">
                                    <div class="tracking-alert-icon">
                                        <i data-lucide="clock"></i>
                                    </div>
                                    <div class="tracking-alert-body">
                                        <div class="tracking-alert-head">
                                            <span class="tracking-alert-title">Menunggu Penyiapan Gudang (Antrean PO)</span>
                                            <span class="badge" style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;background:#eef2ff;color:#4f46e5;border:1px solid #c7d2fe;">Daftar PO</span>
                                        </div>
                                        <p class="tracking-alert-desc">
                                            Pesanan berada dalam antrean PO gudang. Gudang akan memverifikasi stok fisik di rak sebelum pengemasan.
                                        </p>
                                    </div>
                                </div>
                            </template>

                            <!-- SELESAI ALERT -->
                            <template x-if="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan)">
                                <div class="tracking-alert alert-success">
                                    <div class="tracking-alert-icon">
                                        <i data-lucide="check-circle-2"></i>
                                    </div>
                                    <div class="tracking-alert-body">
                                        <div class="tracking-alert-head">
                                            <span class="tracking-alert-title">Pesanan Selesai &amp; Diterima Toko</span>
                                            <span class="badge badge-success" style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;">Tuntas</span>
                                        </div>
                                        <p class="tracking-alert-desc">
                                            Barang telah sukses diterima oleh toko <strong x-text="orderDetail?.nama_toko"></strong>. Seluruh proses logistik tuntas.
                                        </p>
                                    </div>
                                </div>
                            </template>

                            <!-- DIBATALKAN ALERT -->
                            <template x-if="orderDetail?.status_pemrosesan === 'dibatalkan'">
                                <div class="tracking-alert alert-muted">
                                    <div class="tracking-alert-icon">
                                        <i data-lucide="ban"></i>
                                    </div>
                                    <div class="tracking-alert-body">
                                        <div class="tracking-alert-head">
                                            <span class="tracking-alert-title">Pesanan Dibatalkan</span>
                                            <span class="badge badge-mono" style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;">Batal</span>
                                        </div>
                                        <p class="tracking-alert-desc">
                                            Pesanan resmi dibatalkan. Tidak ada stok fisik produk atau tagihan berjalan.
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- ========================================================================= -->
                        <!-- A. DESKTOP & TABLET HORIZONTAL TIMELINE STEPPER                           -->
                        <!-- ========================================================================= -->
                        <div class="timeline-track-desktop">
                            <div class="timeline-h-container">
                                
                                <!-- Step 1: Draf PO -->
                                <div class="timeline-h-step">
                                    <div class="timeline-h-rail">
                                        <div class="timeline-h-span-line"
                                             :style="orderDetail?.status_pemrosesan === 'dibatalkan' ? 'background:#94a3b8;' : (orderDetail?.status_pemrosesan !== 'po' ? 'background:#10b981;' : 'background:#2563eb;')"></div>
                                        <div class="timeline-node-circle is-done" title="Tahap 1: Draf PO Dibuat">
                                            <i data-lucide="file-text" style="width:18px;height:18px;"></i>
                                        </div>
                                    </div>
                                    <div class="timeline-h-card">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:3px;">
                                            <span class="timeline-step-title">1. Draf PO</span>
                                            <span class="timeline-step-badge badge" style="background:#d1fae5;color:#065f46;">Selesai</span>
                                        </div>
                                        <div class="timeline-step-time" x-text="getStepTime(1)"></div>
                                        <div class="timeline-step-note" x-text="getStepNote(1)"></div>
                                    </div>
                                </div>

                                <!-- Step 2: Gudang & Packing -->
                                <div class="timeline-h-step">
                                    <div class="timeline-h-rail">
                                        <div class="timeline-h-span-line"
                                             :style="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'background:#10b981;' : (orderDetail?.status_pemrosesan === 'gagal_dikirim' ? 'background:#ef4444;' : (orderDetail?.status_pemrosesan === 'sedang_dikirim' ? 'background:#2563eb;' : 'background:#e2e8f0;'))"></div>
                                        <div class="timeline-node-circle"
                                             :class="orderDetail?.status_pemrosesan === 'po' ? 'is-active' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'is-cancelled' : 'is-done')"
                                             title="Tahap 2: Gudang &amp; Packing">
                                            <span x-show="orderDetail?.status_pemrosesan === 'dibatalkan'" style="display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="package-x" style="width:18px;height:18px;"></i>
                                            </span>
                                            <span x-show="orderDetail?.status_pemrosesan !== 'dibatalkan'" style="display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="package-check" style="width:18px;height:18px;"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="timeline-h-card" :class="orderDetail?.status_pemrosesan === 'po' ? 'card-active' : ''">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:3px;">
                                            <span class="timeline-step-title">2. Gudang &amp; Packing</span>
                                            <span class="timeline-step-badge badge"
                                                  :style="orderDetail?.status_pemrosesan === 'po' ? 'background:#dbeafe;color:#1e40af;' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'background:#f1f5f9;color:#64748b;' : 'background:#d1fae5;color:#065f46;')"
                                                  x-text="orderDetail?.status_pemrosesan === 'po' ? 'Diproses' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'Batal' : 'Selesai')"></span>
                                        </div>
                                        <div class="timeline-step-time" x-text="getStepTime(2)"></div>
                                        <div class="timeline-step-note" x-text="getStepNote(2)"></div>
                                    </div>
                                </div>

                                <!-- Step 3: Pengiriman Kurir -->
                                <div class="timeline-h-step">
                                    <div class="timeline-h-rail">
                                        <div class="timeline-h-span-line"
                                             :style="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'background:#10b981;' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'background:#94a3b8;' : 'background:#e2e8f0;')"></div>
                                        <div class="timeline-node-circle"
                                             :class="orderDetail?.status_pemrosesan === 'gagal_dikirim' ? 'is-failed' : (orderDetail?.status_pemrosesan === 'sedang_dikirim' ? 'is-active' : (['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'is-done' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'is-cancelled' : 'is-pending')))"
                                             title="Tahap 3: Pengiriman Kurir">
                                            <span x-show="orderDetail?.status_pemrosesan === 'gagal_dikirim'" style="display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="alert-triangle" style="width:18px;height:18px;"></i>
                                            </span>
                                            <span x-show="orderDetail?.status_pemrosesan !== 'gagal_dikirim'" style="display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="truck" style="width:18px;height:18px;"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="timeline-h-card"
                                         :class="orderDetail?.status_pemrosesan === 'gagal_dikirim' ? 'card-failed' : (orderDetail?.status_pemrosesan === 'sedang_dikirim' ? 'card-active' : '')">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:3px;">
                                            <span class="timeline-step-title" :style="orderDetail?.status_pemrosesan === 'gagal_dikirim' ? 'color:#dc2626;' : ''">3. Pengiriman Kurir</span>
                                            <span class="timeline-step-badge badge"
                                                  :style="orderDetail?.status_pemrosesan === 'gagal_dikirim' ? 'background:#fee2e2;color:#991b1b;' : (orderDetail?.status_pemrosesan === 'sedang_dikirim' ? 'background:#dbeafe;color:#1e40af;' : (['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'background:#d1fae5;color:#065f46;' : 'background:#f1f5f9;color:#64748b;'))"
                                                  x-text="orderDetail?.status_pemrosesan === 'gagal_dikirim' ? 'Gagal Kirim' : (orderDetail?.status_pemrosesan === 'sedang_dikirim' ? 'Diantar' : (['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'Terkirim' : 'Menunggu'))"></span>
                                        </div>
                                        <div class="timeline-step-time" x-text="getStepTime(3)"></div>
                                        <div class="timeline-step-note" x-text="getStepNote(3)"></div>
                                    </div>
                                </div>

                                <!-- Step 4: Selesai Diterima -->
                                <div class="timeline-h-step">
                                    <div class="timeline-h-rail">
                                        <div class="timeline-node-circle"
                                             :class="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'is-done' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'is-cancelled' : 'is-pending')"
                                             title="Tahap 4: Selesai Diterima">
                                            <span x-show="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan)" style="display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="check-circle-2" style="width:18px;height:18px;"></i>
                                            </span>
                                            <span x-show="orderDetail?.status_pemrosesan === 'dibatalkan'" style="display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="ban" style="width:18px;height:18px;"></i>
                                            </span>
                                            <span x-show="!['selesai_dikirim', 'selesai_diterima', 'selesai', 'dibatalkan'].includes(orderDetail?.status_pemrosesan)" style="display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="store" style="width:18px;height:18px;"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="timeline-h-card"
                                         :class="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'card-active' : ''">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:3px;">
                                            <span class="timeline-step-title">4. Selesai Diterima</span>
                                            <span class="timeline-step-badge badge"
                                                  :style="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'background:#d1fae5;color:#065f46;' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'background:#fee2e2;color:#991b1b;' : 'background:#f1f5f9;color:#64748b;')"
                                                  x-text="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'Tuntas' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'Batal' : 'Menunggu')"></span>
                                        </div>
                                        <div class="timeline-step-time" x-text="getStepTime(4)"></div>
                                        <div class="timeline-step-note" x-text="getStepNote(4)"></div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- ========================================================================= -->
                        <!-- B. MOBILE VERTICAL TIMELINE STEPPER (SHOPEE / JNE MOBILE PATTERN)         -->
                        <!-- ========================================================================= -->
                        <div class="timeline-track-mobile">
                            <div class="timeline-v-container">
                                
                                <!-- Mobile Step 1: Draf PO -->
                                <div class="timeline-v-item">
                                    <div class="timeline-v-rail">
                                        <div class="timeline-node-circle is-done">
                                            <i data-lucide="file-text" style="width:16px;height:16px;"></i>
                                        </div>
                                        <div class="timeline-v-line"
                                             :style="orderDetail?.status_pemrosesan !== 'po' ? 'background:#10b981;' : 'background:#2563eb;'"></div>
                                    </div>
                                    <div class="timeline-v-card">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:3px;">
                                            <span class="timeline-step-title">1. Draf PO Dibuat</span>
                                            <span class="timeline-step-badge badge" style="background:#d1fae5;color:#065f46;">Selesai</span>
                                        </div>
                                        <div class="timeline-step-time" x-text="getStepTime(1)"></div>
                                        <div class="timeline-step-note" x-text="getStepNote(1)"></div>
                                    </div>
                                </div>

                                <!-- Mobile Step 2: Gudang & Packing -->
                                <div class="timeline-v-item">
                                    <div class="timeline-v-rail">
                                        <div class="timeline-node-circle"
                                             :class="orderDetail?.status_pemrosesan === 'po' ? 'is-active' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'is-cancelled' : 'is-done')">
                                            <span x-show="orderDetail?.status_pemrosesan === 'dibatalkan'" style="display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="package-x" style="width:16px;height:16px;"></i>
                                            </span>
                                            <span x-show="orderDetail?.status_pemrosesan !== 'dibatalkan'" style="display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="package-check" style="width:16px;height:16px;"></i>
                                            </span>
                                        </div>
                                        <div class="timeline-v-line"
                                             :style="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'background:#10b981;' : (orderDetail?.status_pemrosesan === 'gagal_dikirim' ? 'background:#ef4444;' : (orderDetail?.status_pemrosesan === 'sedang_dikirim' ? 'background:#2563eb;' : 'background:#e2e8f0;'))"></div>
                                    </div>
                                    <div class="timeline-v-card" :class="orderDetail?.status_pemrosesan === 'po' ? 'card-active' : ''">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:3px;">
                                            <span class="timeline-step-title">2. Gudang &amp; Packing</span>
                                            <span class="timeline-step-badge badge"
                                                  :style="orderDetail?.status_pemrosesan === 'po' ? 'background:#dbeafe;color:#1e40af;' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'background:#f1f5f9;color:#64748b;' : 'background:#d1fae5;color:#065f46;')"
                                                  x-text="orderDetail?.status_pemrosesan === 'po' ? 'Diproses' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'Batal' : 'Selesai')"></span>
                                        </div>
                                        <div class="timeline-step-time" x-text="getStepTime(2)"></div>
                                        <div class="timeline-step-note" x-text="getStepNote(2)"></div>
                                    </div>
                                </div>

                                <!-- Mobile Step 3: Pengiriman Kurir -->
                                <div class="timeline-v-item">
                                    <div class="timeline-v-rail">
                                        <div class="timeline-node-circle"
                                             :class="orderDetail?.status_pemrosesan === 'gagal_dikirim' ? 'is-failed' : (orderDetail?.status_pemrosesan === 'sedang_dikirim' ? 'is-active' : (['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'is-done' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'is-cancelled' : 'is-pending')))">
                                            <span x-show="orderDetail?.status_pemrosesan === 'gagal_dikirim'" style="display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="alert-triangle" style="width:16px;height:16px;"></i>
                                            </span>
                                            <span x-show="orderDetail?.status_pemrosesan !== 'gagal_dikirim'" style="display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="truck" style="width:16px;height:16px;"></i>
                                            </span>
                                        </div>
                                        <div class="timeline-v-line"
                                             :style="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'background:#10b981;' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'background:#94a3b8;' : 'background:#e2e8f0;')"></div>
                                    </div>
                                    <div class="timeline-v-card"
                                         :class="orderDetail?.status_pemrosesan === 'gagal_dikirim' ? 'card-failed' : (orderDetail?.status_pemrosesan === 'sedang_dikirim' ? 'card-active' : '')">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:3px;">
                                            <span class="timeline-step-title" :style="orderDetail?.status_pemrosesan === 'gagal_dikirim' ? 'color:#dc2626;' : ''">3. Pengiriman Kurir</span>
                                            <span class="timeline-step-badge badge"
                                                  :style="orderDetail?.status_pemrosesan === 'gagal_dikirim' ? 'background:#fee2e2;color:#991b1b;' : (orderDetail?.status_pemrosesan === 'sedang_dikirim' ? 'background:#dbeafe;color:#1e40af;' : (['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'background:#d1fae5;color:#065f46;' : 'background:#f1f5f9;color:#64748b;'))"
                                                  x-text="orderDetail?.status_pemrosesan === 'gagal_dikirim' ? 'Gagal Kirim' : (orderDetail?.status_pemrosesan === 'sedang_dikirim' ? 'Diantar' : (['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'Terkirim' : 'Menunggu'))"></span>
                                        </div>
                                        <div class="timeline-step-time" x-text="getStepTime(3)"></div>
                                        <div class="timeline-step-note" x-text="getStepNote(3)"></div>
                                    </div>
                                </div>

                                <!-- Mobile Step 4: Selesai Diterima -->
                                <div class="timeline-v-item">
                                    <div class="timeline-v-rail">
                                        <div class="timeline-node-circle"
                                             :class="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'is-done' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'is-cancelled' : 'is-pending')">
                                            <span x-show="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan)" style="display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="check-circle-2" style="width:16px;height:16px;"></i>
                                            </span>
                                            <span x-show="orderDetail?.status_pemrosesan === 'dibatalkan'" style="display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="ban" style="width:16px;height:16px;"></i>
                                            </span>
                                            <span x-show="!['selesai_dikirim', 'selesai_diterima', 'selesai', 'dibatalkan'].includes(orderDetail?.status_pemrosesan)" style="display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="store" style="width:16px;height:16px;"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="timeline-v-card"
                                         :class="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'card-active' : ''">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:3px;">
                                            <span class="timeline-step-title">4. Selesai Diterima</span>
                                            <span class="timeline-step-badge badge"
                                                  :style="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'background:#d1fae5;color:#065f46;' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'background:#fee2e2;color:#991b1b;' : 'background:#f1f5f9;color:#64748b;')"
                                                  x-text="['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(orderDetail?.status_pemrosesan) ? 'Tuntas' : (orderDetail?.status_pemrosesan === 'dibatalkan' ? 'Batal' : 'Menunggu')"></span>
                                        </div>
                                        <div class="timeline-step-time" x-text="getStepTime(4)"></div>
                                        <div class="timeline-step-note" x-text="getStepNote(4)"></div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>

                    <!-- ROW 2: 2-COLUMN GRID UNTUK MANIFEST & AUDIT TRAIL -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" style="gap:24px;">

                        <!-- 2. RIWAYAT SURAT JALAN & PENGIRIMAN -->
                        <div class="tab-section-card">
                            <div class="tab-section-header">
                                <div style="display:flex;align-items:center;gap:9px;">
                                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(245,158,11,0.12);color:#d97706;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i data-lucide="truck" style="width:16px;height:16px;"></i>
                                    </div>
                                    <div>
                                        <span style="font-size:13px;font-weight:800;color:var(--color-ink);display:block;">Riwayat Manifest &amp; Surat Jalan</span>
                                        <span style="font-size:11px;color:var(--color-ink-mute);display:block;margin-top:1px;">Arsip berkas ekspedisi pesanan</span>
                                    </div>
                                </div>
                                <span class="badge badge-neutral" style="font-size:10.5px;padding:2px 8px;" x-text="(shippingHistory?.length || 0) + ' Dokumen'"></span>
                            </div>

                            <div class="tab-section-body custom-scrollbar" style="max-height: 380px; overflow-y: auto; padding: 16px;">
                                <template x-if="!shippingHistory || shippingHistory.length === 0">
                                    <div style="text-align:center;padding:40px 20px;color:var(--color-ink-mute);font-size:12px;">
                                        <div style="width:40px;height:40px;border-radius:12px;background:var(--color-canvas-soft);display:flex;align-items:center;justify-content:center;margin:0 auto 10px auto;border:1px solid var(--color-hairline);">
                                            <i data-lucide="file-x-2" style="width:20px;height:20px;opacity:0.6;"></i>
                                        </div>
                                        <div>Belum ada Surat Jalan yang diterbitkan untuk pesanan ini.</div>
                                    </div>
                                </template>

                                <template x-if="shippingHistory && shippingHistory.length > 0">
                                    <div style="display:flex;flex-direction:column;gap:10px;">
                                        <template x-for="(sj, sIdx) in shippingHistory" :key="sj.id || sIdx">
                                            <div style="padding:12px 14px;border-radius:12px;border:1px solid var(--color-hairline);background:var(--color-canvas-soft);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                                                <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                                                    <div style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
                                                         :style="sj.status_surat_jalan === 'gagal_kirim' ? 'background:transparent;color:#ef4444;' : (sj.status_surat_jalan === 'selesai_diterima' ? 'background:#d1fae5;color:#059669;' : 'background:#eff6ff;color:#2563eb;')">
                                                        <i :data-lucide="sj.status_surat_jalan === 'gagal_kirim' ? 'alert-triangle' : (sj.status_surat_jalan === 'selesai_diterima' ? 'check-circle' : 'file-text')" style="width:16px;height:16px;"></i>
                                                    </div>
                                                    <div style="min-width:0;">
                                                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                                            <span class="font-mono font-bold" style="font-size:12.5px;color:var(--color-ink);" x-text="'#' + sj.nomor_surat_jalan"></span>
                                                            <span class="badge" style="font-size:10px;font-weight:800;padding:2px 7px;border-radius:6px;"
                                                                  :style="sj.status_surat_jalan === 'gagal_kirim' ? 'background:#fee2e2;color:#991b1b;' : (sj.status_surat_jalan === 'selesai_diterima' ? 'background:#d1fae5;color:#065f46;' : 'background:#dbeafe;color:#1e40af;')"
                                                                  x-text="sj.status_surat_jalan === 'gagal_kirim' ? 'GAGAL KIRIM (ARSIP)' : (sj.status_surat_jalan || '').toUpperCase().replace(/_/g, ' ')">
                                                            </span>
                                                        </div>
                                                        <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">
                                                            Driver: <strong class="text-ink" x-text="sj.nama_driver || 'Belum ditugaskan'"></strong>
                                                            <template x-if="sj.nopol_driver">
                                                                <span x-text="' (' + sj.nopol_driver + ')'"></span>
                                                            </template>
                                                            &bull; Diterbitkan: <span x-text="formatDate(sj.dibuat_pada)"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- 3. FEED AUDIT TRAIL LOG AKTIVITAS -->
                        <div class="tab-section-card">
                            <div class="tab-section-header">
                                <div style="display:flex;align-items:center;gap:9px;">
                                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(99,102,241,0.12);color:#6366f1;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i data-lucide="clipboard-list" style="width:16px;height:16px;"></i>
                                    </div>
                                    <div>
                                        <span style="font-size:13px;font-weight:800;color:var(--color-ink);display:block;">Audit Trail Aktivitas Pengguna</span>
                                        <span style="font-size:11px;color:var(--color-ink-mute);display:block;margin-top:1px;">Rekam jejak perubahan &amp; penanggung jawab</span>
                                    </div>
                                </div>
                                <span class="badge badge-neutral" style="font-size:10.5px;padding:2px 8px;" x-text="(activityLogs?.length || 0) + ' Log'"></span>
                            </div>

                            <div class="tab-section-body custom-scrollbar" style="max-height: 380px; overflow-y: auto; padding: 16px;">
                                <template x-if="!activityLogs || activityLogs.length === 0">
                                    <div style="text-align:center;padding:40px 20px;color:var(--color-ink-mute);font-size:12px;">
                                        <div style="width:40px;height:40px;border-radius:12px;background:var(--color-canvas-soft);display:flex;align-items:center;justify-content:center;margin:0 auto 10px auto;border:1px solid var(--color-hairline);">
                                            <i data-lucide="activity" style="width:20px;height:20px;opacity:0.6;"></i>
                                        </div>
                                        <div>Belum ada log aktivitas tercatat untuk pesanan ini.</div>
                                    </div>
                                </template>

                                <template x-if="activityLogs && activityLogs.length > 0">
                                    <div style="display:flex;flex-direction:column;gap:10px;">
                                        <template x-for="(log, lIdx) in activityLogs" :key="log.id || lIdx">
                                            <div style="padding:12px 14px;border-radius:12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);display:flex;flex-direction:column;gap:5px;">
                                                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                                                    <div style="display:flex;align-items:center;gap:8px;">
                                                        <span class="badge" style="font-size:10px;font-weight:800;padding:2px 6px;border-radius:6px;background:rgba(99,102,241,0.1);color:#4f46e5;text-transform:uppercase;" x-text="log.jenis_aksi || 'ACTION'"></span>
                                                        <strong style="font-size:12px;color:var(--color-ink);" x-text="log.nama_aktor || 'System'"></strong>
                                                        <span class="badge badge-mono" style="font-size:9.5px;padding:1px 5px;color:var(--color-ink-mute);" x-text="'(' + (log.peran_aktor || 'user') + ')'"></span>
                                                    </div>
                                                    <span style="font-size:10.5px;color:var(--color-ink-mute);font-family:monospace;" x-text="formatDate(log.waktu_kejadian)"></span>
                                                </div>
                                                <div style="font-size:12px;color:var(--color-ink-secondary);line-height:1.45;" x-text="log.deskripsi_aktivitas"></div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL: POP-UP KONFIRMASI MINIMALIS KIRIM ULANG PESANAN GAGAL              -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showRetryModal" x-cloak class="modal-backdrop" @click.self="showRetryModal = false" style="z-index: 10050;">
        <div class="modal-box" style="max-width: 480px; padding: 24px; border-radius: 20px; text-align: left;" @click.stop>
            
            <!-- JIKA KEDALUWARSA (> 7 HARI) -->
            <template x-if="retryExpired">
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div style="display:flex;align-items:center;gap:14px;">
                        <div style="width:48px;height:48px;border-radius:14px;background:rgba(239,68,68,0.12);color:#ef4444;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="alert-octagon" style="width:26px;height:26px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:16px;font-weight:900;color:var(--color-ink);margin:0;">Masa Tenggang Habis</h3>
                            <p style="font-size:12px;color:var(--color-ink-mute);margin:2px 0 0 0;">Pesanan Kedaluwarsa (&gt; 7 Hari)</p>
                        </div>
                    </div>

                    <div style="padding:14px;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;font-size:12.5px;color:#991b1b;line-height:1.5;">
                        Pesanan <strong>#<span x-text="retryOrder?.nomor_nota"></span></strong> telah melewati batas maksimal <strong>7 hari</strong> sejak dinyatakan gagal kirim.
                        <div style="margin-top:6px;font-size:12px;color:#b91c1c;">
                            Sesuai kebijakan sistem, pesanan ini sudah kedaluwarsa dan <strong>otomatis dibatalkan</strong>. Seluruh stok produk telah aman berada di rak gudang.
                        </div>
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:6px;">
                        <button type="button" @click="handleAutoCancelExpired()" :disabled="isSubmittingRetry" class="btn btn-danger" style="border-radius:10px;font-weight:700;padding:9px 18px;">
                            <span x-text="isSubmittingRetry ? 'Memproses...' : 'Tutup &amp; Batalkan Pesanan'"></span>
                        </button>
                    </div>
                </div>
            </template>

            <!-- JIKA MASIH DALAM TENGGANG WAKTU (<= 7 HARI) -->
            <template x-if="!retryExpired">
                <div style="display:flex;flex-direction:column;gap:18px;">
                    <div style="display:flex;align-items:center;gap:14px;">
                        <div style="width:48px;height:48px;border-radius:14px;background:rgba(37,99,235,0.1);color:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="rotate-cw" style="width:24px;height:24px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:16px;font-weight:900;color:var(--color-ink);margin:0;">Kirim Ulang Pesanan</h3>
                            <p style="font-size:12px;color:var(--color-ink-mute);margin:2px 0 0 0;" x-text="'Nota #' + (retryOrder?.nomor_nota || '') + ' - ' + (retryOrder?.nama_toko || '')"></p>
                        </div>
                    </div>

                    <div style="padding:14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:12px;font-size:13px;color:var(--color-ink);line-height:1.5;">
                        Apakah rincian item produk pada pesanan ini <strong>perlu diedit / disesuaikan</strong> terlebih dahulu sebelum dikirim ulang ke gudang?
                    </div>

                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <!-- Opsi 1: Ya, Edit Dulu -->
                        <a :href="'<?= Router::url('/customer-orders/edit?id=') ?>' + (retryOrder?.id || '') + '&retry=1'"
                           class="btn btn-secondary w-full"
                           style="padding:11px 16px;border-radius:12px;display:flex;align-items:center;justify-content:center;gap:8px;font-weight:800;font-size:13px;color:#2563eb;background:#eff6ff;border:1.5px solid #bfdbfe;text-decoration:none;">
                            <i data-lucide="edit-3" style="width:16px;height:16px;"></i>
                            <span>Ya, Edit Pesanan Dulu</span>
                        </a>

                        <!-- Opsi 2: Tidak, Langsung Kirim Ulang -->
                        <button type="button" @click="submitDirectRetry()" :disabled="isSubmittingRetry"
                                class="btn btn-primary w-full"
                                style="padding:11px 16px;border-radius:12px;display:flex;align-items:center;justify-content:center;gap:8px;font-weight:800;font-size:13px;background:#1e3a8a;border-color:#1e3a8a;">
                            <i data-lucide="package-check" style="width:16px;height:16px;"></i>
                            <span x-text="isSubmittingRetry ? 'Menjadwalkan...' : 'Tidak, Langsung Kirim Ulang'"></span>
                        </button>
                        
                        <!-- Batal -->
                        <button type="button" @click="showRetryModal = false" class="btn btn-ghost w-full" style="font-size:12px;font-weight:600;color:var(--color-ink-mute);padding:6px;">
                            Tutup / Batalkan Dialog
                        </button>
                    </div>
                </div>
            </template>

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
        paymentsList: [],
        shippingHistory: [],
        activityLogs: [],
        showRetryModal: false,
        retryOrder: null,
        retryExpired: false,
        isSubmittingRetry: false,

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
        paymentDisplay: '',
        isSubmittingPayment: false,

        init() {
            this.$watch('activeTab', () => {
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        async openOrderDetail(order) {
            this.orderDetail = order;
            this.orderItems = [];
            this.paymentsList = [];
            this.shippingHistory = [];
            this.activityLogs = [];
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
            this.paymentDisplay = sisa > 0 ? (window.formatRupiahNumber ? window.formatRupiahNumber(sisa) : Number(sisa).toLocaleString('id-ID')) : '';
            this.syncPaymentKeterangan(true);

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
                    if (data.payments) this.paymentsList = data.payments;
                    if (data.shippingHistory) this.shippingHistory = data.shippingHistory;
                    if (data.activityLogs) this.activityLogs = data.activityLogs;
                    
                    this.shippingForm.driver_id = data.order.sales_driver_id || '';
                    this.shippingForm.status = data.order.status_surat_jalan || 'disetujui_owner';
                    this.syncDriverNopol();

                    const sisaTerbaru = this.calcSisaTagihan();
                    this.paymentForm.nominal_bayar = String(sisaTerbaru);
                    this.paymentDisplay = sisaTerbaru > 0 ? (window.formatRupiahNumber ? window.formatRupiahNumber(sisaTerbaru) : Number(sisaTerbaru).toLocaleString('id-ID')) : '';
                    this.syncPaymentKeterangan(true);
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
            if (this.orderDetail.tipe_pembayaran === 'konsinyasi' || this.orderDetail.is_konsinyasi || this.orderDetail.adalah_tagihan === false || this.orderDetail.adalah_tagihan === 'false') {
                return 0;
            }
            const netto = Number(this.orderDetail.total_netto || 0);
            const dibayar = Number(this.orderDetail.total_dibayar || 0);
            return Math.max(0, netto - dibayar);
        },

        syncPaymentKeterangan(force = false) {
            const sisa = this.calcSisaTagihan();
            const bayar = Number(this.paymentForm.nominal_bayar || 0);
            const nota = (this.orderDetail?.nomor_nota || '').trim();
            const isFull = sisa > 0 && bayar >= sisa;
            const currentKet = (this.paymentForm.keterangan || '').trim();

            const textFull = 'Pelunasan Faktur Toko ' + nota;
            const textPartial = 'Pembayaran Sebagian Faktur Toko ' + nota;

            if (force || !currentKet || currentKet === textFull || currentKet === textPartial || currentKet.startsWith('Pelunasan') || currentKet.startsWith('Pembayaran Sebagian') || currentKet.startsWith('Pembayaran Faktur')) {
                this.paymentForm.keterangan = isFull ? textFull : textPartial;
            }
        },

        setPaymentLunas() {
            const sisa = this.calcSisaTagihan();
            this.paymentForm.nominal_bayar = String(sisa);
            this.paymentDisplay = sisa > 0 ? (window.formatRupiahNumber ? window.formatRupiahNumber(sisa) : Number(sisa).toLocaleString('id-ID')) : '';
            this.syncPaymentKeterangan();
        },

        onPaymentInput(e) {
            const raw = (e.target.value || '').replace(/[^0-9]/g, '');
            let num = parseInt(raw, 10) || 0;
            const max = this.calcSisaTagihan();
            if (max > 0 && num > max) {
                num = max;
            }
            this.paymentForm.nominal_bayar = String(num);
            this.paymentDisplay = num > 0 ? (window.formatRupiahNumber ? window.formatRupiahNumber(num) : Number(num).toLocaleString('id-ID')) : '';
            this.syncPaymentKeterangan();
        },

        async submitPayment() {
            if (this.isSubmittingPayment) return;

            if (['gagal_dikirim', 'gagal_kembali', 'gagal_kirim'].includes(this.orderDetail?.status_pemrosesan)) {
                if (window.AppAction) {
                    await window.AppAction.error('Pembayaran Ditangguhkan!', 'Pesanan berstatus Gagal Kirim. Harap selesaikan jadwal Kirim Ulang terlebih dahulu.', 3000);
                }
                return;
            }
            if (this.orderDetail?.status_pemrosesan === 'dibatalkan') {
                if (window.AppAction) {
                    await window.AppAction.error('Pesanan Dibatalkan!', 'Pembayaran tidak dapat dicatat untuk pesanan yang telah dibatalkan.', 3000);
                }
                return;
            }

            const nominal = Number(this.paymentForm.nominal_bayar || 0);
            const sisa = this.calcSisaTagihan();

            if (nominal <= 0) {
                if (window.AppAction) {
                    window.AppAction.error('Nominal Pembayaran Kosong!', 'Mohon masukkan nominal pembayaran yang valid (lebih dari Rp 0).');
                }
                return;
            }
            if (nominal > sisa) {
                if (window.AppAction) {
                    window.AppAction.error('Nominal Melebihi Tagihan!', 'Nominal pembayaran (' + this.formatRupiah(nominal) + ') tidak boleh melebihi sisa tagihan (' + this.formatRupiah(sisa) + ').');
                }
                return;
            }
            if (!this.paymentForm.akun_kas_id) {
                if (window.AppAction) {
                    window.AppAction.error('Akun Kas Kosong!', 'Mohon pilih akun kas / bank penerima pembayaran.');
                }
                return;
            }

            const selectedAccount = this.masterCashAccounts.find(a => String(a.id) === String(this.paymentForm.akun_kas_id));
            const namaAkun = selectedAccount ? selectedAccount.nama_akun : 'Akun Kas';
            const isLunas = nominal >= sisa;

            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: isLunas ? 'Konfirmasi Pelunasan Faktur' : 'Konfirmasi Pembayaran Tagihan',
                message: `Catat penerimaan kas sebesar ${this.formatRupiah(nominal)} ke ${namaAkun} untuk faktur ${this.orderDetail?.nomor_nota || ''}?`,
                confirmText: isLunas ? 'Ya, Catat Pelunasan' : 'Ya, Catat Pembayaran',
                cancelText: 'Batal',
                type: 'info',
                icon: 'wallet'
            }) : confirm(`Catat penerimaan kas sebesar ${this.formatRupiah(nominal)} ke ${namaAkun}?`);

            if (!confirmed) return;

            this.isSubmittingPayment = true;
            if (window.AppAction) {
                window.AppAction.show('Memproses pencatatan pembayaran...');
            }

            try {
                const formData = new FormData();
                formData.append('id', this.orderDetail?.id);
                formData.append('akun_kas_id', this.paymentForm.akun_kas_id);
                formData.append('nominal_bayar', this.paymentForm.nominal_bayar);
                formData.append('tanggal_bayar', this.paymentForm.tanggal_bayar);
                formData.append('keterangan', this.paymentForm.keterangan);

                const res = await fetch('<?= Router::url('/customer-orders/pay') ?>', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                const json = await res.json();
                if (json.success) {
                    const title = isLunas ? 'Pelunasan Berhasil Dicatat! ✨' : 'Pembayaran Berhasil Dicatat! ✨';
                    const sisaSisa = json.data?.sisa_tagihan ? ('Sisa tagihan: ' + this.formatRupiah(json.data.sisa_tagihan)) : 'Tagihan telah lunas sepenuhnya.';
                    
                    if (window.AppAction) {
                        await window.AppAction.success(title, sisaSisa, 1500);
                    }

                    // Kunci agar reload tidak pernah memicu skeleton page loader
                    try {
                        sessionStorage.setItem('app_action_triggered', 'true');
                    } catch (e) {}

                    this.showDetailModal = false;
                    setTimeout(() => {
                        window.location.reload();
                    }, 100);
                } else {
                    if (window.AppAction) {
                        await window.AppAction.error('Gagal Mencatat Pembayaran!', json.message || 'Terjadi kesalahan sistem.', 2800);
                    }
                }
            } catch (err) {
                if (window.AppAction) {
                    await window.AppAction.error('Gagal Menghubungi Server!', err.message, 2400);
                }
            } finally {
                this.isSubmittingPayment = false;
            }
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
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            const tgl = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            const jam = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
            return `${tgl} ${jam} WIB`;
        },

        getStepTime(stepNum) {
            const o = this.orderDetail;
            if (!o) return '-';
            const status = o.status_pemrosesan || 'po';

            if (stepNum === 1) {
                // Tahap 1: Draf PO Dibuat
                return this.formatDate(o.dibuat_pada);
            }

            if (stepNum === 2) {
                // Tahap 2: Gudang & Packing
                if (status === 'po') return 'Antrean PO Gudang';
                const time = o.waktu_packing || o.waktu_surat_jalan || o.diubah_pada;
                return time ? this.formatDate(time) : (status === 'dibatalkan' ? 'Batal' : 'Stok Terpotong');
            }

            if (stepNum === 3) {
                // Tahap 3: Pengiriman Kurir
                if (status === 'gagal_dikirim') {
                    const failTime = o.waktu_gagal_kirim || o.waktu_pengiriman || o.diubah_pada;
                    return failTime ? this.formatDate(failTime) : 'Kendala kurir';
                }
                if (status === 'sedang_dikirim') {
                    const sendTime = o.waktu_berangkat || o.waktu_pengiriman || o.waktu_surat_jalan || o.diubah_pada;
                    return sendTime ? this.formatDate(sendTime) : 'Dalam Perjalanan';
                }
                if (['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(status)) {
                    const sendTime = o.waktu_berangkat || o.waktu_pengiriman || o.waktu_surat_jalan || o.waktu_packing || o.dibuat_pada;
                    return sendTime ? this.formatDate(sendTime) : '-';
                }
                if (status === 'dibatalkan') {
                    return o.diubah_pada ? this.formatDate(o.diubah_pada) : 'Batal';
                }
                return o.nama_sales ? ('Kurir: ' + o.nama_sales) : 'Belum Dijadwalkan';
            }

            if (stepNum === 4) {
                // Tahap 4: Selesai Diterima
                if (['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(status)) {
                    const doneTime = o.waktu_sampai || o.waktu_selesai || o.diubah_pada;
                    return doneTime ? this.formatDate(doneTime) : 'Diterima Mitra Toko';
                }
                if (status === 'dibatalkan') {
                    return o.diubah_pada ? this.formatDate(o.diubah_pada) : 'Pesanan Batal';
                }
                return 'Menunggu Pengantaran';
            }

            return '-';
        },

        getStepNote(stepNum) {
            const o = this.orderDetail;
            if (!o) return '';
            const status = o.status_pemrosesan || 'po';

            if (stepNum === 1) {
                return 'Nota resmi tercatat di ERP';
            }
            if (stepNum === 2) {
                if (status === 'po') return 'Verifikasi & packing barang';
                if (status === 'dibatalkan') return 'Dibatalkan';
                return 'Barang dipacking & stok terpotong';
            }
            if (stepNum === 3) {
                if (status === 'gagal_dikirim') return 'Kendala kurir. Stok aman di gudang';
                if (status === 'sedang_dikirim') return 'Armada: ' + (o.nopol_driver || 'Toko') + (o.nama_sales ? ' (' + o.nama_sales + ')' : '');
                if (['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(status)) return 'Pengiriman berhasil tuntas' + (o.nama_sales ? ' (' + o.nama_sales + ')' : '');
                return 'Menunggu Surat Jalan';
            }
            if (stepNum === 4) {
                if (['selesai_dikirim', 'selesai_diterima', 'selesai'].includes(status)) return 'Transaksi sukses diselesaikan';
                if (status === 'dibatalkan') return 'Transaksi resmi dibatalkan';
                return 'Konfirmasi serah terima saat sampai';
            }
            return '';
        },

        openRetryModal(order) {
            this.retryOrder = order;
            this.isSubmittingRetry = false;
            
            // Periksa batas kedaluwarsa 7 hari
            const failureDateStr = order.waktu_gagal_kirim || order.diubah_pada || order.diperbarui_pada || order.dibuat_pada;
            let isExp = false;
            if (failureDateStr) {
                const fDate = new Date(failureDateStr);
                const now = new Date();
                const diffTime = Math.abs(now - fDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                if (diffDays > 7) {
                    isExp = true;
                }
            }
            this.retryExpired = isExp;
            this.showRetryModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        async submitDirectRetry() {
            if (this.isSubmittingRetry || !this.retryOrder) return;
            this.isSubmittingRetry = true;
            if (window.AppAction) {
                window.AppAction.show('Menjadwalkan Kirim Ulang...', 'Mengembalikan status pesanan ke antrean PO gudang...');
            }

            try {
                const formData = new FormData();
                formData.append('id', this.retryOrder.id);
                formData.append('mode', 'direct');

                const res = await fetch('<?= Router::url('/customer-orders/retry-delivery') ?>', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const json = await res.json();
                if (json.success) {
                    if (window.AppAction) {
                        await window.AppAction.success('Berhasil Dijadwalkan Kirim Ulang! 🔁', json.message || 'Pesanan telah masuk kembali ke antrean PO gudang.', 1600);
                    }
                    this.showRetryModal = false;
                    try { sessionStorage.setItem('app_action_triggered', 'true'); } catch (e) {}
                    setTimeout(() => { window.location.reload(); }, 150);
                } else {
                    if (json.expired) {
                        this.retryExpired = true;
                    }
                    if (window.AppAction) {
                        await window.AppAction.error('Gagal Kirim Ulang!', json.message || 'Terjadi kendala saat memproses kirim ulang.', 2800);
                    }
                }
            } catch (err) {
                if (window.AppAction) {
                    await window.AppAction.error('Kesalahan Jaringan!', err.message, 2400);
                }
            } finally {
                this.isSubmittingRetry = false;
            }
        },

        async handleAutoCancelExpired() {
            if (this.isSubmittingRetry || !this.retryOrder) return;
            this.isSubmittingRetry = true;
            if (window.AppAction) {
                window.AppAction.show('Membatalkan Pesanan Kedaluwarsa...', 'Memperbarui status pesanan menjadi dibatalkan...');
            }

            try {
                const formData = new FormData();
                formData.append('id', this.retryOrder.id);
                formData.append('alasan', 'Otomatis dibatalkan: Kirim ulang melebihi batas waktu 7 hari');

                const res = await fetch('<?= Router::url('/customer-orders/cancel') ?>', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const json = await res.json();
                if (json.success) {
                    if (window.AppAction) {
                        await window.AppAction.success('Pesanan Dibatalkan', 'Pesanan yang kedaluwarsa telah resmi dibatalkan.', 1600);
                    }
                    this.showRetryModal = false;
                    try { sessionStorage.setItem('app_action_triggered', 'true'); } catch (e) {}
                    setTimeout(() => { window.location.reload(); }, 150);
                } else {
                    if (window.AppAction) {
                        await window.AppAction.error('Gagal Membatalkan!', json.message || 'Terjadi kesalahan.', 2800);
                    }
                }
            } catch (err) {
                if (window.AppAction) {
                    await window.AppAction.error('Kesalahan Jaringan!', err.message, 2400);
                }
            } finally {
                this.isSubmittingRetry = false;
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

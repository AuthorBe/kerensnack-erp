<?php 
use App\Core\Router; 
use App\Core\Auth;
use App\Helpers\Format;

ob_start();
?>

<style>
/* ========================================================================= */
/* MATERIAL DESIGN 3 / COMPACT PO CARD & MODAL STYLES                       */
/* ========================================================================= */

.po-stat-card {
    background: var(--color-surface);
    border: 1px solid var(--color-hairline);
    border-radius: 16px;
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.po-stat-card:hover {
    border-color: var(--color-hairline-strong);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}

.po-stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.po-stat-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
.po-stat-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.po-stat-val {
    font-family: var(--font-sans);
    font-weight: 900;
    color: var(--color-ink);
    line-height: 1.1;
    display: flex;
    align-items: baseline;
    gap: 6px;
}

/* 2. SEGMENTED TABS (M3 PILLS) */
.po-segmented-tabs-wrapper {
    display: flex;
    align-items: center;
    background: var(--color-canvas-soft);
    padding: 5px;
    border-radius: 14px;
    border: 1px solid var(--color-hairline);
    gap: 4px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}
.po-segmented-tabs-wrapper::-webkit-scrollbar {
    display: none;
}

.po-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    border-radius: 10px;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--color-ink-mute);
    text-decoration: none;
    white-space: nowrap;
    flex-shrink: 0;
    transition: all 0.15s ease;
    border: none;
    background: transparent;
    cursor: pointer;
}
.po-tab-btn:hover {
    color: var(--color-ink);
    background: rgba(255, 255, 255, 0.6);
}
.dark .po-tab-btn:hover {
    background: rgba(255, 255, 255, 0.08);
}

.po-tab-btn.is-active {
    background: var(--color-canvas) !important;
    color: #2563eb !important;
    font-weight: 700 !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08), 0 0 0 1px var(--color-hairline) !important;
}
.dark .po-tab-btn.is-active {
    color: #60a5fa !important;
}

.po-tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 2px 7px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 800;
    line-height: 1;
    background: var(--color-hairline);
    color: var(--color-ink-secondary);
}
.po-tab-btn.is-active .po-tab-badge {
    background: rgba(37, 99, 235, 0.14);
    color: #2563eb;
}
.dark .po-tab-btn.is-active .po-tab-badge {
    background: rgba(96, 165, 250, 0.2);
    color: #93c5fd;
}

/* 3. COMPACT PO CARD */
.po-compact-card {
    background: var(--color-surface);
    border: 1px solid var(--color-hairline);
    border-radius: 16px;
    padding: 20px 24px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.po-compact-card:hover {
    border-color: var(--color-hairline-strong);
    box-shadow: 0 6px 16px rgba(0,0,0,0.06);
    transform: translateY(-1px);
}

/* 4. STOCK STATUS CHIPS */
.stock-chip-ok {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
    font-size: 11.5px;
    font-weight: 700;
    padding: 5px 11px;
    border-radius: 8px;
}
.dark .stock-chip-ok {
    background: rgba(22, 163, 74, 0.15);
    color: #4ade80;
    border-color: rgba(34, 197, 94, 0.3);
}

.stock-chip-danger {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    font-size: 11.5px;
    font-weight: 700;
    padding: 5px 11px;
    border-radius: 8px;
}
.dark .stock-chip-danger {
    background: rgba(220, 38, 38, 0.15);
    color: #f87171;
    border-color: rgba(239, 68, 68, 0.3);
}

/* 5. PO NOTE BADGE (FIT CONTENT, NO OVERFLOW) */
.po-note-badge {
    display: inline-block;
    width: fit-content;
    max-width: 100%;
    background: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    border-radius: 8px;
    padding: 3.5px 10px;
    font-size: 11.5px;
    line-height: 1.45;
    color: var(--color-ink-secondary);
    word-break: break-word;
    overflow-wrap: break-word;
    box-sizing: border-box;
}
.po-note-label {
    font-weight: 800;
    color: var(--color-ink);
    margin-right: 4px;
}
.po-note-text {
    color: var(--color-ink-secondary);
}
.dark .po-note-badge {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.08);
}
</style>

<div x-data="poCompactApp()" x-init="init()" class="space-y-6">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER (KHUSUS STAF GUDANG)                                       -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-blue">
                <i data-lucide="package"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color: #2563eb;"></span>
                    <span>Modul Logistik &amp; Gudang</span>
                </div>
                <h1 class="page-title">Daftar PO Pelanggan</h1>
                <p class="page-subtitle">Verifikasi Kesiapan Stok &amp; Penyiapan Barang Gudang</p>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. 4 KARTU RINGKASAN LOGISTIK GUDANG                                      -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- 1. PO Menunggu Disiapkan -->
        <div class="po-stat-card">
            <div>
                <div class="po-stat-header">
                    <span class="po-stat-label" style="color: #2563eb;">Menunggu Disiapkan</span>
                    <div class="po-stat-icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                        <i data-lucide="clock" style="width: 20px; height: 20px;"></i>
                    </div>
                </div>
                <div class="po-stat-val" style="font-size: 28px;">
                    <span><?= number_format($countPending ?? 0) ?></span>
                    <span style="font-size: 13px; font-weight: 700; color: #3b82f6;">Nota PO</span>
                </div>
            </div>
            <div style="font-size: 11.5px; color: var(--color-ink-mute); margin-top: 8px;">
                Antrean ambil barang fisik
            </div>
        </div>

        <!-- 2. PO Siap Dikirim (Belum Surat Jalan) -->
        <div class="po-stat-card">
            <div>
                <div class="po-stat-header">
                    <span class="po-stat-label" style="color: #059669;">Siap Kirim (Belum SJ)</span>
                    <div class="po-stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #059669;">
                        <i data-lucide="package-check" style="width: 20px; height: 20px;"></i>
                    </div>
                </div>
                <div class="po-stat-val" style="font-size: 28px;">
                    <span><?= number_format($countReadyNoSj ?? 0) ?></span>
                    <span style="font-size: 13px; font-weight: 700; color: #10b981;">Nota Siap</span>
                </div>
            </div>
            <div style="font-size: 11.5px; color: var(--color-ink-mute); margin-top: 8px;">
                Menunggu Surat Jalan Logistik
            </div>
        </div>

        <!-- 3. PO Kurang Stok / Defisit -->
        <div class="po-stat-card">
            <div>
                <div class="po-stat-header">
                    <span class="po-stat-label" style="color: #ea580c;">Kurang Stok (Defisit)</span>
                    <div class="po-stat-icon" style="background: rgba(234, 88, 12, 0.1); color: #ea580c;">
                        <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
                    </div>
                </div>
                <div class="po-stat-val" style="font-size: 28px;">
                    <span><?= number_format($countDeficit ?? 0) ?></span>
                    <span style="font-size: 13px; font-weight: 700; color: #f97316;">Nota Defisit</span>
                </div>
            </div>
            <div style="font-size: 11.5px; color: var(--color-ink-mute); margin-top: 8px;">
                Terkendala kekurangan stok
            </div>
        </div>

        <!-- 4. Kiriman yang Gagal -->
        <div class="po-stat-card">
            <div>
                <div class="po-stat-header">
                    <span class="po-stat-label" style="color: #e11d48;">Kiriman yang Gagal</span>
                    <div class="po-stat-icon" style="background: rgba(225, 29, 72, 0.1); color: #e11d48;">
                        <i data-lucide="truck" style="width: 20px; height: 20px;"></i>
                    </div>
                </div>
                <div class="po-stat-val" style="font-size: 28px;">
                    <span><?= number_format($countFailed ?? 0) ?></span>
                    <span style="font-size: 13px; font-weight: 700; color: #f43f5e;">Nota</span>
                </div>
            </div>
            <div style="font-size: 11.5px; color: var(--color-ink-mute); margin-top: 8px;">
                Riwayat gagal kirim kembali
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 3. FILTER TABS & PENCARIAN                                                -->
    <!-- ========================================================================= -->
    <div class="card p-4 sm:p-5 space-y-4" style="border-radius: 16px;">
        
        <!-- Baris 1: Segmented Filter Pills -->
        <div class="po-segmented-tabs-wrapper table-scroll" data-table-scroll>
            
            <!-- Tab 1: Menunggu Disiapkan -->
            <a href="<?= Router::url('/customer-orders/po-list?tab=pending' . (!empty($q) ? '&q=' . urlencode($q) : '') . (!empty($pelangganId) ? '&pelanggan_id=' . urlencode($pelangganId) : '')) ?>"
               class="po-tab-btn <?= ($tab === 'pending') ? 'is-active' : '' ?>">
                <i data-lucide="clock" style="width: 15px; height: 15px;"></i>
                <span>Menunggu Disiapkan</span>
                <span class="po-tab-badge"><?= $countPending ?></span>
            </a>

            <!-- Tab 2: Siap Dikirim -->
            <a href="<?= Router::url('/customer-orders/po-list?tab=ready' . (!empty($q) ? '&q=' . urlencode($q) : '') . (!empty($pelangganId) ? '&pelanggan_id=' . urlencode($pelangganId) : '')) ?>"
               class="po-tab-btn <?= ($tab === 'ready') ? 'is-active' : '' ?>">
                <i data-lucide="package-check" style="width: 15px; height: 15px;"></i>
                <span>Siap Dikirim</span>
                <span class="po-tab-badge"><?= $countReady ?></span>
            </a>

            <!-- Tab 3: Gagal Kirim (Riwayat List) -->
            <a href="<?= Router::url('/customer-orders/po-list?tab=failed' . (!empty($q) ? '&q=' . urlencode($q) : '') . (!empty($pelangganId) ? '&pelanggan_id=' . urlencode($pelangganId) : '')) ?>"
               class="po-tab-btn <?= ($tab === 'failed') ? 'is-active' : '' ?>">
                <i data-lucide="alert-triangle" style="width: 15px; height: 15px;"></i>
                <span>Gagal Kirim</span>
                <span class="po-tab-badge"><?= $countFailed ?></span>
            </a>

            <!-- Tab 4: Semua Riwayat -->
            <a href="<?= Router::url('/customer-orders/po-list?tab=all' . (!empty($q) ? '&q=' . urlencode($q) : '') . (!empty($pelangganId) ? '&pelanggan_id=' . urlencode($pelangganId) : '')) ?>"
               class="po-tab-btn <?= ($tab === 'all') ? 'is-active' : '' ?>">
                <i data-lucide="layers" style="width: 15px; height: 15px;"></i>
                <span>Semua PO</span>
            </a>

        </div>

        <!-- Divider Line -->
        <div style="border-top: 1px solid var(--color-hairline);"></div>

        <!-- Baris 2: Search & Toko Pelanggan Dropdown Filter Form -->
        <form method="GET" action="<?= Router::url('/customer-orders/po-list') ?>" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab ?? 'pending') ?>">

            <!-- Search Input Box -->
            <div class="relative md:col-span-6">
                <i data-lucide="search" style="width: 16px; height: 16px; position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--color-ink-mute);"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Cari No. PO, Nama Toko, Kode Pelanggan..." class="form-input font-medium" style="height: 40px; font-size: 13px; padding-left: 38px; border-radius: 12px;">
            </div>

            <!-- Dropdown Filter Toko -->
            <div class="md:col-span-4">
                <select name="pelanggan_id" class="form-input font-medium searchable-select" onchange="this.form.submit()" style="height: 40px; font-size: 13px; border-radius: 12px;">
                    <option value="">-- Semua Toko Pelanggan --</option>
                    <?php foreach (($customers ?? []) as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($pelangganId === $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nama_toko']) ?> (<?= htmlspecialchars($c['kode_pelanggan']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit" class="btn btn-primary flex-1" style="height: 40px; font-weight: 700; font-size: 12.5px; border-radius: 12px;">
                    <i data-lucide="filter" style="width: 14px; height: 14px;"></i>
                    <span>Filter</span>
                </button>
                <?php if (!empty($q) || !empty($pelangganId)): ?>
                <a href="<?= Router::url('/customer-orders/po-list?tab=' . urlencode($tab)) ?>" class="btn btn-secondary" style="height: 40px; padding: 0 12px; border-radius: 12px;" title="Reset Filter">
                    <i data-lucide="rotate-ccw" style="width: 14px; height: 14px;"></i>
                </a>
                <?php endif; ?>
            </div>

        </form>

        <?php if (!empty($poList)): ?>
        <!-- Baris 3: Batch Action & Pilih Semua Toolbar -->
        <div class="flex items-center justify-between border-t border-hairline flex-wrap gap-3" style="font-size: 12.5px; margin-top: 16px; padding-top: 16px; padding-bottom: 4px;">
            <label class="flex items-center gap-2 cursor-pointer font-bold text-ink-secondary hover:text-ink" style="user-select: none;">
                <input type="checkbox" @change="toggleSelectAll()" :checked="isAllSelected()" style="width: 17px; height: 17px; border-radius: 5px; accent-color: #2563eb; cursor: pointer;">
                <span>Pilih Semua (<?= count($poList) ?> PO)</span>
            </label>
            <div class="flex items-center gap-2">
                <a href="<?= Router::url('/customer-orders/po-list/batch-pdf?tab=' . urlencode($tab) . (!empty($q) ? '&q=' . urlencode($q) : '') . (!empty($pelangganId) ? '&pelanggan_id=' . urlencode($pelangganId) : '')) ?>"
                   target="_blank"
                   class="btn btn-secondary btn-sm"
                   style="font-weight: 700; color: #dc2626; border-color: #fca5a5; background: #fef2f2; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px;"
                   title="Unduh seluruh PO pada tab & filter ini ke dalam 1 file PDF gabungan">
                    <i data-lucide="file-text" style="width: 14px; height: 14px;"></i>
                    <span>Unduh Semua PO Aktif (PDF)</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ========================================================================= -->
    <!-- 4. DAFTAR KARTU PO RINGKAS (SPASIOUS & PROPORTIONAL GAP)                  -->
    <!-- ========================================================================= -->
    <div class="space-y-4 sm:space-y-5">
        <?php if (empty($poList)): ?>
        <div class="card" style="border-radius: 16px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 56px 20px;">
            <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--color-canvas-soft); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; color: var(--color-ink-mute);">
                <i data-lucide="inbox" style="width: 32px; height: 32px; opacity: 0.6;"></i>
            </div>
            <div style="font-weight: 800; font-size: 15px; color: var(--color-ink); text-align: center;">Tidak Ada Antrean Purchase Order</div>
            <p style="font-size: 12.5px; margin-top: 6px; color: var(--color-ink-mute); text-align: center; max-width: 420px; line-height: 1.5;">Tidak ditemukan data PO untuk tab atau kata kunci pencarian ini.</p>
        </div>
        <?php else: ?>
        <?php foreach ($poList as $idx => $po): 
            $isPending = ($po['status_pemrosesan'] === 'po');
            $isReady = in_array($po['status_pemrosesan'], ['siap_dikirim', 'siap_kirim'], true);
            $isDelivering = ($po['status_pemrosesan'] === 'sedang_dikirim');
            $isCompleted = in_array($po['status_pemrosesan'], ['selesai_dikirim', 'selesai', 'selesai_diterima'], true);
            $isFailed = ($po['status_pemrosesan'] === 'gagal_dikirim');
        ?>
        <div class="po-compact-card">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                
                <!-- SISI KIRI: NAMA TOKO (UTAMA/BESAR) & INFO MINIMALIS -->
                <div class="flex items-start gap-3.5 min-w-0 flex-1">
                    <!-- Checkbox Seleksi -->
                    <label class="flex items-center cursor-pointer" style="margin-top: 13px;">
                        <input type="checkbox" :value="'<?= $po['id'] ?>'" x-model="selectedPoIds" style="width: 18px; height: 18px; border-radius: 5px; accent-color: #2563eb; cursor: pointer;">
                    </label>

                    <div style="width: 46px; height: 46px; border-radius: 14px; background: rgba(37, 99, 235, 0.08); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                        <i data-lucide="store" style="width: 22px; height: 22px;"></i>
                    </div>
                    <div class="min-w-0 flex-1 space-y-1.5">
                        <!-- 1. NAMA TOKO JELAS & BESAR -->
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h2 style="font-size: 16.5px; font-weight: 900; color: var(--color-ink); line-height: 1.25; margin: 0;">
                                <?= htmlspecialchars($po['nama_toko']) ?>
                            </h2>
                            <span class="badge badge-mono" style="font-size: 11px; padding: 2px 6px; color: var(--color-ink-mute); border-color: var(--color-hairline);">
                                <?= htmlspecialchars($po['kode_pelanggan']) ?>
                            </span>
                        </div>

                        <!-- 2. KODE TRANSAKSI & DETAIL LAIN (MINIMALIS / BERSIH) -->
                        <div class="flex items-center gap-3 text-xs text-ink-mute flex-wrap" style="font-size: 12px; line-height: 1.4;">
                            <span class="font-mono text-ink-secondary" style="font-weight: 700;"><?= htmlspecialchars($po['nomor_nota']) ?></span>
                            <span>&bull;</span>
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="calendar" style="width: 13px; height: 13px; color: var(--color-ink-mute);"></i>
                                <?= date('d/m/Y H:i', strtotime($po['dibuat_pada'])) ?> WIB
                            </span>
                            <?php if (!empty($po['nama_wilayah'])): ?>
                            <span>&bull;</span>
                            <span>Wilayah: <strong class="text-ink"><?= htmlspecialchars($po['nama_wilayah']) ?></strong></span>
                            <?php endif; ?>
                        </div>

                        <!-- 3. CATATAN PO (JIKA ADA) -->
                        <?php if (!empty($po['catatan'])): ?>
                        <div style="margin-top: 8px;">
                            <div class="po-note-badge">
                                <strong class="po-note-label">Catatan:</strong>
                                <span class="po-note-text"><?= nl2br(htmlspecialchars($po['catatan'])) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- SISI TENGAH & KANAN: RINGKASAN BARANG, KESIAPAN STOK & TOMBOL AKSI -->
                <div class="flex items-center justify-between lg:justify-end gap-4 sm:gap-5 flex-wrap pt-3 lg:pt-0 border-t lg:border-t-0 border-hairline flex-shrink-0">
                    
                    <!-- Kuantitas Barang -->
                    <div class="text-left lg:text-right">
                        <div class="font-mono font-black text-ink" style="font-size: 15px;">
                            <?= number_format($po['total_pcs']) ?> <span style="font-size: 11.5px; font-weight: 600; color: var(--color-ink-mute);">Pcs</span>
                        </div>
                        <div style="font-size: 11.5px; color: var(--color-ink-mute); margin-top: 1px;">
                            <?= number_format($po['total_sku']) ?> SKU Produk
                        </div>
                    </div>

                    <!-- Badge Kesiapan Stok Gudang -->
                    <div>
                        <?php if ($isPending): ?>
                            <?php if ($po['is_stock_sufficient']): ?>
                                <span class="stock-chip-ok">
                                    <i data-lucide="check-circle" style="width: 14px; height: 14px;"></i>
                                    <span>Stok Cukup</span>
                                </span>
                            <?php else: ?>
                                <span class="stock-chip-danger">
                                    <i data-lucide="alert-circle" style="width: 14px; height: 14px;"></i>
                                    <span>Defisit (<?= $po['stock_deficit_count'] ?>)</span>
                                </span>
                            <?php endif; ?>
                        <?php elseif ($isReady): ?>
                            <span class="badge" style="background: #d1fae5; color: #065f46; font-weight: 700; font-size: 11.5px; border-radius: 6px; padding: 4px 9px;">
                                📦 Siap Kirim
                            </span>
                        <?php elseif ($isDelivering): ?>
                            <span class="badge" style="background: #fef3c7; color: #92400e; font-weight: 700; font-size: 11.5px; border-radius: 6px; padding: 4px 9px;">
                                🚚 Sedang Kirim
                            </span>
                        <?php elseif ($isCompleted): ?>
                            <span class="badge" style="background: #ecfdf5; color: #047857; font-weight: 700; font-size: 11.5px; border-radius: 6px; padding: 4px 9px;">
                                ✅ Selesai
                            </span>
                        <?php elseif ($isFailed): ?>
                            <span class="badge" style="background: #ffe4e6; color: #9f1239; font-weight: 700; font-size: 11.5px; border-radius: 6px; padding: 4px 9px;">
                                ❌ Gagal Kirim
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Tombol Aksi: Cukup Rincian Item (Aksi lainnya di dalam Modal Pop-up agar kartu rapi) -->
                    <div class="flex items-center">
                        <button type="button" 
                                class="btn btn-secondary btn-sm"
                                style="font-size: 12.5px; font-weight: 700; border-radius: 10px; padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;"
                                @click="openItemModal(<?= htmlspecialchars(json_encode($po)) ?>)">
                            <i data-lucide="list" style="width: 15px; height: 15px; color: var(--color-primary);"></i>
                            <span>Rincian Item</span>
                        </button>
                    </div>

                </div>

            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. MODAL DIALOG POP-UP: RINCIAN ITEM PRODUK (MATERIAL DESIGN 3)            -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showItemModal" x-cloak class="modal-backdrop" @keydown.escape.window="showItemModal = false" style="z-index: 9999;">
        <div class="modal-box" style="max-width: 680px; padding: 24px; border-radius: 20px;" @click.away="showItemModal = false">
            
            <!-- MODAL HEADER -->
            <div class="modal-header" style="margin-bottom: 16px;">
                <div class="flex items-center gap-3">
                    <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(37,99,235,0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i data-lucide="package" style="width: 22px; height: 22px;"></i>
                    </div>
                    <div>
                        <div class="modal-title" style="font-size: 16px; font-weight: 900; color: var(--color-ink);" x-text="activePo?.nama_toko"></div>
                        <div style="font-size: 12px; color: var(--color-ink-mute); margin-top: 1px;">
                            <span class="font-mono font-bold" x-text="activePo?.nomor_nota"></span> &bull; 
                            <span x-text="activePo?.total_pcs + ' Pcs (' + activePo?.total_sku + ' SKU)'"></span>
                        </div>
                    </div>
                </div>
                <button @click="showItemModal = false" class="btn btn-ghost btn-sm" style="padding: 6px; border-radius: 10px;">
                    <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                </button>
            </div>

            <!-- WARNING JIKA DEFISIT STOK -->
            <template x-if="activePo?.is_stock_sufficient === false">
                <div style="padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; font-size: 12.5px; color: #991b1b; display: flex; align-items: flex-start; gap: 10px; margin-bottom: 16px;">
                    <i data-lucide="alert-octagon" style="width: 18px; height: 18px; color: #dc2626; flex-shrink: 0; margin-top: 2px;"></i>
                    <div>
                        <strong>Stok Gudang Tidak Mencukupi:</strong> Terdapat produk yang kuantitasnya kurang dari pesanan PO. Harap lengkapi stok fisik gudang terlebih dahulu.
                    </div>
                </div>
            </template>

            <!-- TABEL RINCIAN ITEM PRODUK -->
            <div style="margin-bottom: 18px;">
                <div class="table-scroll" style="max-height: 320px; border: 1px solid var(--color-hairline); border-radius: 12px; background: var(--color-canvas);">
                    <table class="table" style="margin: 0; width: 100%; font-size: 12.5px;">
                        <thead style="background: var(--color-surface); position: sticky; top: 0; z-index: 2;">
                            <tr style="border-bottom: 1px solid var(--color-hairline);">
                                <th class="cell-center" style="width: 40px; padding: 10px 12px;">No</th>
                                <th style="width: 120px; padding: 10px 12px;">Kode SKU</th>
                                <th style="padding: 10px 12px;">Nama Produk</th>
                                <th class="cell-center" style="width: 120px; padding: 10px 12px;">Kebutuhan PO</th>
                                <th class="cell-center" style="width: 120px; padding: 10px 12px;">Stok Gudang</th>
                                <th class="cell-center" style="width: 90px; padding: 10px 12px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in activePo?.items || []" :key="item.item_id">
                                <tr style="border-bottom: 1px solid var(--color-hairline);" :class="Number(item.stok_fisik_saat_ini) < Number(item.kuantitas_satuan_dasar) ? 'bg-red-50/40 dark:bg-red-950/20' : ''">
                                    <td class="cell-center text-ink-mute font-semibold" style="padding: 10px 12px;" x-text="idx + 1"></td>
                                    <td style="padding: 10px 12px;">
                                        <span class="badge badge-mono font-bold" style="font-size: 10.5px; padding: 1px 5px;" x-text="item.kode_sku"></span>
                                    </td>
                                    <td style="padding: 10px 12px;">
                                        <div style="font-weight: 700; color: var(--color-ink);" x-text="item.nama_item"></div>
                                    </td>
                                    <td class="cell-center font-mono font-bold text-ink" style="padding: 10px 12px;" x-text="item.kuantitas_satuan_dasar + ' ' + (item.satuan_dasar || 'Pcs')"></td>
                                    <td class="cell-center font-mono font-bold" style="padding: 10px 12px;" :style="Number(item.stok_fisik_saat_ini) >= Number(item.kuantitas_satuan_dasar) ? 'color: #16a34a;' : 'color: #dc2626;'" x-text="item.stok_fisik_saat_ini + ' ' + (item.satuan_dasar || 'Pcs')"></td>
                                    <td class="cell-center" style="padding: 10px 12px;">
                                        <template x-if="Number(item.stok_fisik_saat_ini) >= Number(item.kuantitas_satuan_dasar)">
                                            <span class="stock-chip-ok" style="font-size: 10.5px; padding: 2px 6px;">Cukup</span>
                                        </template>
                                        <template x-if="Number(item.stok_fisik_saat_ini) < Number(item.kuantitas_satuan_dasar)">
                                            <span class="stock-chip-danger" style="font-size: 10.5px; padding: 2px 6px;">Kurang</span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CATATAN PO -->
            <template x-if="activePo?.catatan">
                <div style="margin-bottom: 18px; padding: 10px 14px; background: var(--color-canvas-soft); border: 1px solid var(--color-hairline); border-radius: 10px; font-size: 12px; color: var(--color-ink-secondary); line-height: 1.5; word-break: break-word;">
                    <span style="font-weight: 800; color: var(--color-ink); text-transform: uppercase; font-size: 10.5px;">Catatan:</span>
                    <span x-text="activePo?.catatan" style="word-break: break-word;"></span>
                </div>
            </template>

            <!-- FOOTER MODAL ACTIONS -->
            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px;">
                <button type="button" @click="showItemModal = false" class="btn btn-secondary" style="border-radius: 10px; font-weight: 600;">
                    Tutup
                </button>

                <!-- Tombol Unduh PDF List Item PO -->
                <a :href="'<?= Router::url('/customer-orders/picking-list/pdf?id=') ?>' + (activePo ? activePo.id : '')"
                   target="_blank"
                   class="btn btn-secondary"
                   style="border-radius: 10px; font-weight: 700; font-size: 13px; padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px; color:#dc2626; border-color:#fca5a5; background:#fef2f2;"
                   title="Unduh PDF Daftar Item Pesanan (PO)">
                    <i data-lucide="file-text" style="width: 15px; height: 15px;"></i>
                    <span>Unduh PDF</span>
                </a>

                <!-- Tombol Siap Dikirim Langsung Dari Modal -->
                <?php if (Auth::can('orders.po_process')): ?>
                <template x-if="activePo?.status_pemrosesan === 'po'">
                    <div>
                        <template x-if="activePo?.is_stock_sufficient">
                            <form action="<?= Router::url('/customer-orders/process-po') ?>" method="POST"
                                  data-confirm="Pastikan seluruh barang fisik telah selesai disiapkan di area logistik. Lanjutkan?"
                                  data-confirm-title="Konfirmasi Penyiapan Barang"
                                  data-confirm-type="info"
                                  data-confirm-btn="Ya, Siap Dikirim"
                                  style="display: inline;">
                                <input type="hidden" name="order_id" :value="activePo?.id">
                                <button type="submit" class="btn btn-primary" style="font-weight: 800; font-size: 13px; border-radius: 10px; padding: 8px 18px; display: inline-flex; align-items: center; gap: 6px;">
                                    <i data-lucide="package-check" style="width: 15px; height: 15px;"></i>
                                    <span>Siap Dikirim</span>
                                </button>
                            </form>
                        </template>
                        <template x-if="!activePo?.is_stock_sufficient">
                            <button type="button" disabled class="btn btn-secondary" style="font-weight: 700; font-size: 12px; border-radius: 10px; padding: 8px 16px; opacity: 0.55; cursor: not-allowed;">
                                <i data-lucide="alert-octagon" style="width: 14px; height: 14px; color: #dc2626;"></i>
                                <span>Stok Kurang (Belum Bisa Diproses)</span>
                            </button>
                        </template>
                    </div>
                </template>
                <?php endif; ?>
            </div>

        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- 6. FLOATING BATCH ACTION BAR (KETIKA >= 1 PO DICENTANG)                   -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="selectedPoIds.length > 0" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-8"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-8"
         style="position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); z-index: 9990; max-width: 92%; width: 520px;">
        <div style="background: #0f172a; color: #ffffff; border-radius: 16px; padding: 12px 18px; box-shadow: 0 12px 30px -5px rgba(0, 0, 0, 0.4), 0 8px 12px -6px rgba(0, 0, 0, 0.3); display: flex; align-items: center; justify-content: space-between; gap: 12px; border: 1px solid rgba(255, 255, 255, 0.12);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="badge" style="background: #2563eb; color: #ffffff; font-size: 13px; font-weight: 800; padding: 4px 10px; border-radius: 8px;" x-text="selectedPoIds.length + ' PO'"></span>
                <span style="font-size: 13px; font-weight: 600; color: #f8fafc;">Dipilih</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" @click="selectedPoIds = []" class="btn btn-sm" style="background: rgba(255,255,255,0.12); color: #cbd5e1; border: none; font-weight: 600; padding: 7px 14px; border-radius: 10px;">
                    Batal
                </button>
                <a :href="'<?= Router::url('/customer-orders/po-list/batch-pdf?ids=') ?>' + selectedPoIds.join(',')" target="_blank"
                   class="btn btn-sm" style="background: #dc2626; color: #ffffff; border: none; font-weight: 800; padding: 7px 18px; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px;">
                    <i data-lucide="file-text" style="width: 15px; height: 15px;"></i>
                    <span x-text="'Unduh PDF (' + selectedPoIds.length + ' PO)'">Unduh PDF</span>
                </a>
            </div>
        </div>
    </div>
    </template>

</div>

<script>
function poCompactApp() {
    return {
        showItemModal: false,
        activePo: null,
        selectedPoIds: [],
        allPoIds: <?= json_encode(array_column($poList, 'id')) ?>,

        init() {
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        toggleSelectAll() {
            if (this.isAllSelected()) {
                this.selectedPoIds = [];
            } else {
                this.selectedPoIds = [...this.allPoIds];
            }
        },

        isAllSelected() {
            return this.allPoIds.length > 0 && this.selectedPoIds.length === this.allPoIds.length;
        },

        openItemModal(po) {
            this.activePo = po;
            this.showItemModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
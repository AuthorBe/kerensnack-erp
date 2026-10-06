<?php 
use App\Core\Router; 
use App\Core\Auth;
use App\Helpers\Format;

ob_start();
?>

<style>
/* ========================================================================= */
/* KEREN ONE - DAFTAR PO PELANGGAN (CLEAN ENTERPRISE UI)                     */
/* ========================================================================= */

.po-stat-card {
    background: var(--color-surface);
    border: 1px solid var(--color-hairline);
    border-radius: var(--rounded-lg, 12px);
    padding: 16px 18px;
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
    margin-bottom: 8px;
}
.po-stat-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.po-stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
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

/* 2. SEGMENTED TABS (ENTERPRISE FILTER BAR) */
.po-segmented-tabs-wrapper {
    display: flex;
    align-items: center;
    background: var(--color-canvas-soft);
    padding: 4px;
    border-radius: var(--rounded-lg, 12px);
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
    border-radius: 8px;
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
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06), 0 0 0 1px var(--color-hairline) !important;
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
    border-radius: var(--rounded-lg, 12px);
    padding: 18px 20px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.po-compact-card:hover {
    border-color: var(--color-hairline-strong);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

/* 4. REUSABLE BADGE / CHIP COMPONENT */
.ui-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    height: 24px;
    padding: 0 8px;
    font-size: 11.5px;
    font-weight: 600;
    line-height: 1;
    white-space: nowrap;
    border-radius: var(--rounded-md, 8px);
    width: fit-content;
    box-sizing: border-box;
}
.ui-badge .lucide {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
}
.ui-badge.is-mono { font-family: var(--font-mono); font-variant-numeric: tabular-nums; }
.ui-badge.is-green { background: rgba(16, 185, 129, 0.12); color: #047857; border: 1px solid rgba(16, 185, 129, 0.25); }
.ui-badge.is-orange { background: rgba(245, 158, 11, 0.12); color: #b45309; border: 1px solid rgba(245, 158, 11, 0.25); }
.ui-badge.is-blue { background: rgba(37, 99, 235, 0.1); color: #2563eb; border: 1px solid rgba(37, 99, 235, 0.25); }
.ui-badge.is-indigo { background: rgba(99, 102, 241, 0.12); color: #4338ca; border: 1px solid rgba(99, 102, 241, 0.25); }
.ui-badge.is-red { background: rgba(239, 68, 68, 0.12); color: #b91c1c; border: 1px solid rgba(239, 68, 68, 0.25); }
.ui-badge.is-gray { background: var(--color-canvas-soft); color: var(--color-ink-mute); border: 1px solid var(--color-hairline); }

.dark .ui-badge.is-green { background: rgba(16, 185, 129, 0.18); color: #34d399; border-color: rgba(16, 185, 129, 0.35); }
.dark .ui-badge.is-red { background: rgba(239, 68, 68, 0.18); color: #f87171; border-color: rgba(239, 68, 68, 0.35); }
.dark .ui-badge.is-gray { background: rgba(255, 255, 255, 0.04); border-color: rgba(255, 255, 255, 0.08); }

/* 6. FLOATING BATCH ACTION BAR (Desktop Sidebar Aware) */
.po-floating-bar {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 35;
    max-width: 92%;
    width: 520px;
}
@media (min-width: 1024px) {
    .po-floating-bar {
        left: calc(50% + 130px);
    }
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
        
        <?php
        $sort = $sort ?? 'terbaru';
        $sortParam = ($sort === 'terlama') ? '&sort=terlama' : '';
        ?>

        <!-- Baris 1: Segmented Filter Pills -->
        <div class="po-segmented-tabs-wrapper table-scroll" data-table-scroll>
            
            <!-- Tab 1: Menunggu Disiapkan -->
            <a href="<?= Router::url('/customer-orders/po-list?tab=pending' . (!empty($q) ? '&q=' . urlencode($q) : '') . (!empty($pelangganId) ? '&pelanggan_id=' . urlencode($pelangganId) : '') . $sortParam) ?>"
               class="po-tab-btn <?= ($tab === 'pending') ? 'is-active' : '' ?>">
                <i data-lucide="clock" style="width: 15px; height: 15px;"></i>
                <span>Menunggu Disiapkan</span>
                <span class="po-tab-badge"><?= $countPending ?></span>
            </a>

            <!-- Tab 2: Siap Dikirim -->
            <a href="<?= Router::url('/customer-orders/po-list?tab=ready' . (!empty($q) ? '&q=' . urlencode($q) : '') . (!empty($pelangganId) ? '&pelanggan_id=' . urlencode($pelangganId) : '') . $sortParam) ?>"
               class="po-tab-btn <?= ($tab === 'ready') ? 'is-active' : '' ?>">
                <i data-lucide="package-check" style="width: 15px; height: 15px;"></i>
                <span>Siap Dikirim</span>
                <span class="po-tab-badge"><?= $countReady ?></span>
            </a>

            <!-- Tab 3: Gagal Kirim (Riwayat List) -->
            <a href="<?= Router::url('/customer-orders/po-list?tab=failed' . (!empty($q) ? '&q=' . urlencode($q) : '') . (!empty($pelangganId) ? '&pelanggan_id=' . urlencode($pelangganId) : '') . $sortParam) ?>"
               class="po-tab-btn <?= ($tab === 'failed') ? 'is-active' : '' ?>">
                <i data-lucide="alert-triangle" style="width: 15px; height: 15px;"></i>
                <span>Gagal Kirim</span>
                <span class="po-tab-badge"><?= $countFailed ?></span>
            </a>

            <!-- Tab 4: Semua Riwayat -->
            <a href="<?= Router::url('/customer-orders/po-list?tab=all' . (!empty($q) ? '&q=' . urlencode($q) : '') . (!empty($pelangganId) ? '&pelanggan_id=' . urlencode($pelangganId) : '') . $sortParam) ?>"
               class="po-tab-btn <?= ($tab === 'all') ? 'is-active' : '' ?>">
                <i data-lucide="layers" style="width: 15px; height: 15px;"></i>
                <span>Semua PO</span>
            </a>

        </div>

        <!-- Divider Line -->
        <div style="border-top: 1px solid var(--color-hairline);"></div>

        <!-- Baris 2: Search, Toko Pelanggan & Dropdown Urutan Filter Form -->
        <form method="GET" action="<?= Router::url('/customer-orders/po-list') ?>" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab ?? 'pending') ?>">

            <!-- Search Input Box -->
            <div class="relative md:col-span-4">
                <i data-lucide="search" style="width: 16px; height: 16px; position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--color-ink-mute);"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Cari No. PO, Nama Toko, Kode..." class="form-input font-medium" style="height: 40px; font-size: 13px; padding-left: 38px; border-radius: 12px;">
            </div>

            <!-- Dropdown Filter Toko -->
            <div class="md:col-span-3">
                <select name="pelanggan_id" class="form-input font-medium searchable-select" onchange="this.form.submit()" style="height: 40px; font-size: 13px; border-radius: 12px;">
                    <option value="">-- Semua Toko Pelanggan --</option>
                    <?php foreach (($customers ?? []) as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($pelangganId === $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nama_toko']) ?> (<?= htmlspecialchars($c['kode_pelanggan']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dropdown Urutan / Sort -->
            <div class="md:col-span-3">
                <select name="sort" class="form-input font-medium cursor-pointer" onchange="this.form.submit()" style="height: 40px; font-size: 13px; border-radius: 12px;">
                    <option value="terbaru" <?= ($sort !== 'terlama') ? 'selected' : '' ?>>Urutkan: Terbaru Dulu</option>
                    <option value="terlama" <?= ($sort === 'terlama') ? 'selected' : '' ?>>Urutkan: Terlama Dulu</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit" class="btn btn-primary flex-1" style="height: 40px; font-weight: 700; font-size: 12.5px; border-radius: 12px;">
                    <i data-lucide="filter" style="width: 14px; height: 14px;"></i>
                    <span>Filter</span>
                </button>
                <?php if (!empty($q) || !empty($pelangganId) || $sort === 'terlama'): ?>
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
                <a href="#"
                   @click.prevent="downloadPdfWithLoader('<?= Router::url('/customer-orders/po-list/batch-pdf?tab=' . urlencode($tab) . (!empty($q) ? '&q=' . urlencode($q) : '') . (!empty($pelangganId) ? '&pelanggan_id=' . urlencode($pelangganId) : '') . $sortParam) ?>')"
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
        <div class="po-compact-card" style="padding: 16px; display: flex; flex-direction: column; gap: 12px; background: var(--color-surface); border: 1px solid var(--color-hairline); border-radius: var(--rounded-md, 8px); transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <!-- ROW 1: Header (Checkbox, Icon, Title + Meta) -->
            <div class="flex items-center" style="gap: 12px;">
                <label class="flex items-center cursor-pointer m-0">
                    <input type="checkbox" :value="'<?= $po['id'] ?>'" x-model="selectedPoIds" style="width: 18px; height: 18px; border-radius: 5px; accent-color: #2563eb; cursor: pointer;">
                </label>
                <div style="width: 40px; height: 40px; border-radius: var(--rounded-md, 8px); background: rgba(37, 99, 235, 0.08); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="store" style="width: 20px; height: 20px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center flex-wrap" style="gap: 8px;">
                        <h2 style="font-size: 15px; font-weight: 700; color: var(--color-ink); line-height: 1.2; margin: 0;" class="truncate">
                            <?= htmlspecialchars($po['nama_toko']) ?>
                        </h2>
                        <span class="ui-badge is-mono is-gray">
                            <?= htmlspecialchars($po['kode_pelanggan']) ?>
                        </span>
                        <?php if (!empty($po['catatan']) && str_contains($po['catatan'], '[Kirim Ulang]')): ?>
                        <span class="ui-badge is-blue">
                            🔁 Kirim Ulang
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-wrap items-center mt-1" style="gap: 4px 12px; font-size: 12px; color: var(--color-ink-mute);">
                        <span class="ui-badge is-mono is-gray" style="background: transparent; border: none; padding: 0; height: auto;">
                            <?= htmlspecialchars($po['nomor_nota']) ?>
                        </span>
                        <span class="inline-flex items-center gap-1" style="gap: 4px;">
                            <i data-lucide="calendar" style="width: 14px; height: 14px;"></i>
                            <span><?= date('d/m/Y H:i', strtotime($po['dibuat_pada'])) ?> WIB</span>
                        </span>
                        <?php if (!empty($po['nama_wilayah'])): ?>
                        <span class="inline-flex items-center gap-1" style="gap: 4px;">
                            <i data-lucide="map-pin" style="width: 14px; height: 14px;"></i>
                            <span>Wilayah: <strong class="font-semibold text-slate-700 dark:text-zinc-300"><?= htmlspecialchars($po['nama_wilayah']) ?></strong></span>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ROW 2: Note Chip (if any) -->
            <?php if (!empty($po['catatan'])): ?>
            <div class="truncate w-full bg-slate-50 dark:bg-zinc-800/50 border border-hairline" style="border-radius: var(--rounded-md, 8px); padding: 6px 10px; font-size: 12px; color: var(--color-ink-secondary);">
                <span class="font-semibold text-ink inline-flex items-center gap-1">
                    <i data-lucide="message-square" style="width: 14px; height: 14px; color: var(--color-ink-mute);"></i>
                    Catatan:
                </span>
                <span><?= htmlspecialchars(str_replace('[Kirim Ulang]', '', $po['catatan'])) ?></span>
            </div>
            <?php endif; ?>

            <!-- DIVIDER -->
            <div style="border-top: 1px solid var(--color-hairline);"></div>

            <!-- ROW 3 & 4: Summary & Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between" style="gap: 16px;">
                
                <!-- Summary Area -->
                <div class="flex items-center justify-between sm:justify-start" style="gap: 16px;">
                    <div>
                        <div style="font-family: var(--font-sans); color: var(--color-ink); line-height: 1.2;">
                            <strong style="font-size: 15px; font-weight: 700;"><?= number_format($po['total_pcs']) ?></strong> <span style="font-size: 12px; font-weight: 500; color: var(--color-ink-mute);">Pcs</span>
                        </div>
                        <div style="font-family: var(--font-sans); color: var(--color-ink); line-height: 1.2; margin-top: 2px;">
                            <strong style="font-size: 13px; font-weight: 600;"><?= number_format($po['total_sku']) ?></strong> <span style="font-size: 12px; font-weight: 500; color: var(--color-ink-mute);">SKU Produk</span>
                        </div>
                    </div>

                    <div>
                        <?php if ($isPending): ?>
                            <?php if ($po['is_stock_sufficient']): ?>
                                <span class="ui-badge is-green">
                                    <i data-lucide="check-circle"></i> Stok Cukup
                                </span>
                            <?php else: ?>
                                <span class="ui-badge is-red">
                                    <i data-lucide="alert-circle"></i> Defisit (<?= $po['stock_deficit_count'] ?>)
                                </span>
                            <?php endif; ?>
                        <?php elseif ($isReady): ?>
                            <?php if (!empty($po['nomor_surat_jalan'])): ?>
                                <span class="ui-badge is-indigo">
                                    📦 Siap Kirim &bull; SJ: <?= htmlspecialchars($po['nomor_surat_jalan']) ?>
                                </span>
                            <?php else: ?>
                                <span class="ui-badge is-green">
                                    📦 Siap Kirim (Belum SJ)
                                </span>
                            <?php endif; ?>
                        <?php elseif ($isDelivering): ?>
                            <span class="ui-badge is-orange">
                                🚚 Sedang Kirim
                            </span>
                        <?php elseif ($isCompleted): ?>
                            <span class="ui-badge is-green">
                                ✅ Selesai
                            </span>
                        <?php elseif ($isFailed): ?>
                            <span class="ui-badge is-red">
                                ❌ Gagal Kirim
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-2 sm:flex sm:items-center shrink-0 w-full sm:w-auto" style="gap: 8px;">
                    <?php if ($isPending && $po['is_stock_sufficient'] && Auth::can('orders.po_process')): ?>
                    <button type="button" 
                            class="btn btn-primary w-full sm:w-auto" 
                            style="height: 40px; padding: 0 16px; font-size: 13px; font-weight: 600; border-radius: var(--rounded-md, 8px); display: inline-flex; align-items: center; justify-content: center; gap: 6px;"
                            @click="openConfirmReadyModal(<?= htmlspecialchars(json_encode($po)) ?>)">
                        <i data-lucide="package-check" style="width: 16px; height: 16px;"></i>
                        <span>Siap Dikirim</span>
                    </button>
                    <?php else: ?>
                    <div class="hidden sm:block"></div>
                    <?php endif; ?>

                    <button type="button" 
                            class="btn btn-secondary w-full sm:w-auto <?= (!($isPending && $po['is_stock_sufficient'] && Auth::can('orders.po_process'))) ? 'col-span-2' : '' ?>"
                            style="height: 40px; padding: 0 16px; font-size: 13px; font-weight: 600; border-radius: var(--rounded-md, 8px); display: inline-flex; align-items: center; justify-content: center; gap: 6px;"
                            @click="openItemModal(<?= htmlspecialchars(json_encode($po)) ?>)">
                        <i data-lucide="list" style="width: 16px; height: 16px; color: var(--color-primary);"></i>
                        <span>Rincian Item</span>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. MODAL DIALOG POP-UP: RINCIAN ITEM PRODUK                                -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showItemModal" x-cloak class="modal-backdrop" @click="showItemModal = false" style="z-index: 9999;">
        <div class="modal-box modal-box-lg" @click.stop>
            <!-- Mobile Pull Handle -->
            <div class="modal-handle">
                <div class="modal-handle-bar"></div>
            </div>

            <!-- MODAL HEADER -->
            <div class="modal-header">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(37,99,235,0.1);color:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="package" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="modal-title" x-text="activePo?.nama_toko"></div>
                            <template x-if="activePo?.catatan && activePo.catatan.includes('[Kirim Ulang]')">
                                <span class="badge" style="background:rgba(37,99,235,0.1);color:#2563eb;border:1px solid rgba(37,99,235,0.25);font-weight:700;font-size:11px;padding:2px 8px;border-radius:9999px;">
                                    🔁 Kirim Ulang
                                </span>
                            </template>
                        </div>
                        <div style="font-size: 12px; color: var(--color-ink-mute); margin-top: 1px;">
                            <span class="font-mono font-bold text-slate-700 dark:text-zinc-300" x-text="activePo?.nomor_nota"></span> &bull; 
                            <span x-text="activePo?.total_pcs + ' Pcs (' + activePo?.total_sku + ' SKU)'"></span>
                        </div>
                    </div>
                </div>
                <button type="button" @click="showItemModal = false" class="modal-close-x" title="Tutup Modal">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body custom-scrollbar">

            <!-- WARNING JIKA DEFISIT STOK -->
            <template x-if="activePo?.is_stock_sufficient === false">
                <div style="padding: 12px 16px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 12px; font-size: 12.5px; color: #b91c1c; display: flex; align-items: flex-start; gap: 10px; margin-bottom: 16px;">
                    <i data-lucide="alert-octagon" style="width: 18px; height: 18px; color: #dc2626; flex-shrink: 0; margin-top: 1px;"></i>
                    <div>
                        <strong style="color: #991b1b;">Stok Gudang Tidak Mencukupi:</strong> Terdapat produk yang kuantitasnya kurang dari pesanan PO. Harap lengkapi stok fisik gudang terlebih dahulu.
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
                            <template x-for="(item, idx) in activePo?.items || []" :key="idx">
                                <tr style="border-bottom: 1px solid var(--color-hairline);" :class="Number(item.stok_fisik_saat_ini) < Number(item.kuantitas_satuan_dasar) ? 'bg-red-50/40 dark:bg-red-950/20' : ''">
                                    <td class="cell-center text-ink-mute font-semibold" style="padding: 10px 12px;" x-text="idx + 1"></td>
                                    <td style="padding: 10px 12px;">
                                        <span class="badge badge-mono font-bold" style="font-size: 10.5px; padding: 1px 5px;" x-text="item.kode_sku"></span>
                                    </td>
                                    <td style="padding: 10px 12px;">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span style="font-weight: 700; color: var(--color-ink);" x-text="item.nama_item"></span>
                                            <template x-if="item.is_bonus">
                                                <span class="ui-badge is-orange">
                                                    <i data-lucide="gift"></i> Bonus
                                                </span>
                                            </template>
                                        </div>
                                        <template x-if="item.is_bonus && item.catatan_bonus">
                                            <div style="font-size: 11px; color: #059669; font-style: italic; margin-top: 2px;" x-text="'Alasan: ' + item.catatan_bonus"></div>
                                        </template>
                                    </td>
                                    <td class="cell-center font-mono font-bold text-ink" style="padding: 10px 12px;" x-text="item.kuantitas_satuan_dasar + ' ' + (item.satuan_dasar || 'Pcs')"></td>
                                    <td class="cell-center font-mono font-bold" style="padding: 10px 12px;" :style="Number(item.stok_fisik_saat_ini) >= Number(item.kuantitas_satuan_dasar) ? 'color: #16a34a;' : 'color: #dc2626;'" x-text="item.stok_fisik_saat_ini + ' ' + (item.satuan_dasar || 'Pcs')"></td>
                                    <td class="cell-center" style="padding: 10px 12px;">
                                        <template x-if="Number(item.stok_fisik_saat_ini) >= Number(item.kuantitas_satuan_dasar)">
                                            <span class="stock-chip-ok" style="font-size: 10.5px; padding: 2px 8px;">Cukup</span>
                                        </template>
                                        <template x-if="Number(item.stok_fisik_saat_ini) < Number(item.kuantitas_satuan_dasar)">
                                            <span class="stock-chip-danger" style="font-size: 10.5px; padding: 2px 8px;">Kurang</span>
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
                    <div class="flex items-center gap-1.5 mb-1 text-slate-700 dark:text-zinc-200">
                        <i data-lucide="message-square" style="width: 13px; height: 13px; color: var(--color-ink-mute);"></i>
                        <strong style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">Catatan Pemesanan:</strong>
                    </div>
                    <span x-text="activePo?.catatan" style="word-break: break-word;"></span>
                </div>
            </template>

            <!-- FOOTER MODAL ACTIONS -->
            <div class="modal-footer">
                <button type="button" @click="showItemModal = false" class="btn btn-secondary modal-btn-cancel-desktop">
                    Tutup
                </button>

                <!-- Tombol Unduh PDF List Item PO -->
                <a href="#"
                   @click.prevent="downloadPdfWithLoader('<?= Router::url('/customer-orders/picking-list/pdf?id=') ?>' + (activePo ? activePo.id : ''))"
                   class="btn btn-secondary"
                   style="color: #dc2626; border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.06);"
                   title="Unduh PDF Daftar Item Pesanan (PO)">
                    <i data-lucide="file-text" style="width: 15px; height: 15px;"></i>
                    <span>Unduh PDF</span>
                </a>

                <!-- Tombol Siap Dikirim Langsung Dari Modal -->
                <?php if (Auth::can('orders.po_process')): ?>
                <template x-if="activePo?.status_pemrosesan === 'po'">
                    <div>
                        <template x-if="activePo?.is_stock_sufficient">
                            <button type="button" 
                                    class="btn btn-primary" 
                                    @click="openConfirmReadyModal(activePo)">
                                <i data-lucide="package-check" style="width: 15px; height: 15px;"></i>
                                <span>Siap Dikirim</span>
                            </button>
                        </template>
                        <template x-if="!activePo?.is_stock_sufficient">
                            <button type="button" disabled class="btn btn-secondary" style="opacity: 0.55; cursor: not-allowed;">
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
    <!-- 6. MODAL DIALOG POP-UP: KONFIRMASI SIAP DIKIRIM & INPUT BONUS GUDANG        -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showConfirmReadyModal" x-cloak class="modal-backdrop" @click="showConfirmReadyModal = false" style="z-index: 9999;">
        <div class="modal-box modal-box-lg" style="max-width: 620px;" @click.stop>
            
            <!-- Mobile Pull Handle -->
            <div class="modal-handle">
                <div class="modal-handle-bar"></div>
            </div>

            <!-- MODAL HEADER -->
            <div class="modal-header">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="modal-header-icon is-emerald">
                        <i data-lucide="package-check"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="modal-title">Konfirmasi Penyiapan &amp; Bonus PO</div>
                        <div style="font-size: 12px; color: var(--color-ink-mute); margin-top: 2px;" class="truncate">
                            <span class="font-bold text-ink" x-text="targetPoForReady?.nama_toko"></span> &bull; 
                            <span class="font-mono font-semibold" style="color: var(--color-primary);" x-text="targetPoForReady?.nomor_nota"></span>
                        </div>
                    </div>
                </div>
                <button type="button" @click="showConfirmReadyModal = false" class="modal-close-x" title="Tutup Modal">
                    <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                </button>
            </div>

            <!-- FORM WRAPPER (Standard Golden Template: wraps modal-body and modal-footer) -->
            <form action="<?= Router::url('/customer-orders/process-po') ?>" method="POST" @submit="onSubmitReadyForm($event)">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="order_id" :value="targetPoForReady?.id">
                <input type="hidden" name="bonuses_json" :value="JSON.stringify(bonusItems)">

                <!-- MODAL BODY -->
                <div class="modal-body custom-scrollbar" style="max-height: calc(85vh - 140px); overflow-y: auto; min-height: 360px;">

                    <!-- INFO PENYIAPAN BARANG (VERIFIKASI FISIK LOGISTIK GUDANG) -->
                    <div class="rounded-xl border p-3.5 mb-4" style="background: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.2);">
                        <div class="flex items-center justify-between gap-2 mb-2 flex-wrap">
                            <div class="flex items-center gap-2 font-bold text-xs" style="color: #047857;">
                                <i data-lucide="check-circle-2" style="width: 15px; height: 15px; color: #10b981;"></i>
                                <span style="font-size: 12.5px;">Verifikasi Fisik Logistik Gudang</span>
                            </div>
                            <span class="ui-badge is-green">
                                <span x-text="targetPoForReady?.total_pcs ?? 0"></span> Pcs
                                <span class="opacity-40">&bull;</span>
                                <span x-text="targetPoForReady?.total_sku ?? 0"></span> SKU
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-zinc-300 leading-relaxed m-0" style="font-size: 13px;">
                            Pastikan seluruh barang pesanan PO ini telah selesai dipacking secara fisik. Stok barang jadi di gudang akan otomatis terpotong saat pesanan dikonfirmasi siap kirim.
                        </p>
                    </div>

                    <!-- FORM INPUT BONUS GUDANG (OPSIONAL) -->
                    <div style="margin-bottom: 8px;">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="flex items-start gap-2.5 min-w-0">
                                <div style="width: 32px; height: 32px; border-radius: 10px; background: rgba(245, 158, 11, 0.12); color: #d97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                                    <i data-lucide="gift" style="width: 16px; height: 16px;"></i>
                                </div>
                                <div class="min-w-0 flex flex-col gap-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-ink" style="font-size: 13.5px; line-height: 1.2;">Item Bonus Tambahan</span>
                                        <template x-if="bonusItems.length > 0">
                                            <span class="ui-badge is-orange" x-text="bonusItems.length + ' Item'"></span>
                                        </template>
                                    </div>
                                    <p class="text-ink-mute m-0 text-xs" style="font-size: 11.5px; line-height: 1.4;">
                                        Opsional &bull; Promo toko, tester produk, atau kompensasi pesanan.
                                    </p>
                                </div>
                            </div>

                            <button type="button" @click="addBonusRow()" 
                                    class="btn btn-secondary flex-shrink-0" 
                                    style="height: 36px; font-size: 13px; font-weight: 600; border-radius: var(--rounded-md, 8px); padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; color: #059669; border-color: rgba(16, 185, 129, 0.35); background: rgba(16, 185, 129, 0.08); white-space: nowrap;">
                                <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
                                <span>Tambah Bonus</span>
                            </button>
                        </div>

                        <!-- EMPTY STATE BONUS -->
                        <template x-if="bonusItems.length === 0">
                            <div class="p-4 rounded-xl text-center border border-dashed" style="background: var(--color-canvas-soft); border-color: var(--color-hairline);">
                                <div class="inline-flex items-center justify-center w-8 h-8 rounded-full mb-1.5" style="background: rgba(0,0,0,0.04); color: var(--color-ink-mute);">
                                    <i data-lucide="gift" style="width: 16px; height: 16px;"></i>
                                </div>
                                <div class="text-xs font-semibold text-ink" style="margin-bottom: 2px;">Tidak Ada Item Bonus</div>
                                <p class="text-ink-mute m-0" style="font-size: 11.5px; line-height: 1.4;">
                                    Klik tombol <strong>+ Tambah Bonus</strong> jika ingin menyertakan barang gratis ke toko ini.
                                </p>
                            </div>
                        </template>

                        <!-- DAFTAR BARIS BONUS -->
                        <template x-if="bonusItems.length > 0">
                            <div class="space-y-3" style="padding-bottom: 24px;">
                                <template x-for="(row, bIdx) in bonusItems" :key="bIdx">
                                    <div class="rounded-xl border p-3.5 transition-all"
                                         style="background: var(--color-canvas-soft); border-color: var(--color-hairline);"
                                         :style="row.dropdownOpen ? 'position: relative; z-index: 50;' : 'position: relative; z-index: 1;'">
                                        
                                        <!-- Card Header: Pill & Action -->
                                        <div class="flex items-center justify-between gap-2 border-b" style="border-color: var(--color-hairline); padding-bottom: 12px; margin-bottom: 12px;">
                                            <div class="flex items-center gap-2 min-w-0 flex-wrap">
                                                <span class="ui-badge is-orange">
                                                    <i data-lucide="gift"></i>
                                                    <span x-text="'Item Bonus #' + (bIdx + 1)"></span>
                                                </span>
                                                <template x-if="row.item_id">
                                                    <span class="ui-badge is-gray is-mono" style="font-weight: 500;">
                                                        <span style="color: var(--color-ink-mute); font-family: var(--font-sans);">Sisa stok:</span>
                                                        <strong class="font-bold text-ink" x-text="getAvailableStock(row.item_id) + ' Pcs'"></strong>
                                                    </span>
                                                </template>
                                            </div>
                                            <button type="button" @click="removeBonusRow(bIdx)" 
                                                    class="btn btn-ghost btn-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/30" 
                                                    style="padding: 4px 8px; font-size: 11.5px; font-weight: 600; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;" 
                                                    title="Hapus Baris Bonus">
                                                <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                                                <span>Hapus</span>
                                            </button>
                                        </div>

                                        <!-- Card Form Inputs -->
                                        <div class="space-y-4">
                                            <!-- Row 1: Pilih Produk (Searchable Dropdown) -->
                                            <div class="relative flex flex-col gap-1.5" @click.outside="row.dropdownOpen = false">
                                                <label class="form-label text-xs font-semibold" style="font-size: 12px; line-height: 1;">
                                                    Pilih Produk Bonus <span class="text-rose-500">*</span>
                                                </label>

                                                <!-- Dropdown Trigger Button -->
                                                <button type="button"
                                                        @click="toggleBonusDropdown(row, bIdx)"
                                                        class="form-input flex items-center justify-between w-full text-left transition cursor-pointer font-medium"
                                                        style="height: 44px; border-radius: var(--rounded-md, 8px); background-color: var(--color-canvas); padding: 0 12px; font-size: 14px;"
                                                        :style="row.dropdownOpen ? 'border-color: var(--color-primary); box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);' : ''">
                                                    
                                                    <div class="flex items-center gap-2 min-w-0 flex-1 pr-1">
                                                        <template x-if="row.item_id">
                                                            <div class="flex items-center gap-1.5 min-w-0 flex-1">
                                                                <span class="badge badge-mono text-[10px] font-bold shrink-0 whitespace-nowrap" style="padding: 1px 5px;" x-text="getItemById(row.item_id)?.kode_sku"></span>
                                                                <span class="truncate font-semibold text-ink" style="font-size: 14px;" x-text="getItemById(row.item_id)?.nama_item"></span>
                                                                <span class="text-[12px] font-mono shrink-0 whitespace-nowrap" style="color: var(--color-ink-mute); white-space: nowrap;" x-text="'(Stok: ' + getAvailableStock(row.item_id) + ')'"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="!row.item_id">
                                                            <span style="color: var(--color-ink-mute); font-weight: 500; font-size: 14px;">-- Cari &amp; Pilih Barang Jadi --</span>
                                                        </template>
                                                    </div>

                                                    <div class="flex items-center gap-1 shrink-0">
                                                        <template x-if="row.item_id">
                                                            <span role="button"
                                                                  @click.stop="clearBonusProduct(row)"
                                                                  class="text-ink-mute hover:text-rose-600 p-0.5 rounded cursor-pointer"
                                                                  title="Hapus Pilihan">
                                                                <i data-lucide="x" style="width: 13px; height: 13px;"></i>
                                                            </span>
                                                        </template>
                                                        <i data-lucide="chevron-down" style="width: 14px; height: 14px; color: var(--color-ink-mute); transition: transform 0.2s;" :style="row.dropdownOpen ? 'transform: rotate(180deg);' : ''"></i>
                                                    </div>
                                                </button>

                                                <!-- Floating Searchable Menu -->
                                                <div x-show="row.dropdownOpen" x-cloak
                                                     class="dropdown-menu-searchable"
                                                     style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 1050; border-radius: 10px; overflow: hidden; background: var(--color-canvas); border: 1px solid var(--color-hairline); box-shadow: 0 14px 34px -4px rgba(0,0,0,0.18);">
                                                    
                                                    <!-- Search Header -->
                                                    <div style="padding: 7px 9px; border-bottom: 1px solid var(--color-hairline); background: var(--color-canvas-soft);">
                                                        <div style="position: relative; display: flex; align-items: center;">
                                                            <i data-lucide="search" style="position: absolute; left: 9px; width: 13px; height: 13px; color: var(--color-ink-mute); pointer-events: none;"></i>
                                                            <input type="text"
                                                                   :id="'bonus-search-' + bIdx"
                                                                   x-model="row.search"
                                                                   @keydown.down.prevent="navigateBonusDropdown(row, bIdx, 1)"
                                                                   @keydown.up.prevent="navigateBonusDropdown(row, bIdx, -1)"
                                                                   @keydown.enter.prevent="selectHighlightedBonus(row)"
                                                                   @keydown.escape.prevent="row.dropdownOpen = false"
                                                                   placeholder="Ketik nama produk atau kode SKU..."
                                                                   class="form-input"
                                                                   style="height: 32px; padding-left: 28px; padding-right: 24px; font-size: 11.5px; border-radius: 6px; width: 100%; background: var(--color-canvas);">
                                                            <button type="button"
                                                                    x-show="row.search && row.search.length > 0"
                                                                    @click="row.search = ''; $nextTick(() => document.getElementById('bonus-search-' + bIdx)?.focus())"
                                                                    style="position: absolute; right: 7px; width: 16px; height: 16px; border: none; background: transparent; color: var(--color-ink-mute); cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                                                <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Options List -->
                                                    <div :id="'bonus-list-' + bIdx" style="max-height: 200px; overflow-y: auto;" class="custom-scrollbar divide-y divide-hairline">
                                                        <template x-for="(p, pIdx) in getFilteredBonusProducts(row)" :key="p.id">
                                                            <div :id="'bonus-opt-' + bIdx + '-' + pIdx"
                                                                 @click="selectBonusProduct(row, p)"
                                                                 class="searchable-option"
                                                                 :class="{
                                                                     'is-selected': String(p.id) === String(row.item_id),
                                                                     'is-active': pIdx === (row.highlightedIndex || 0),
                                                                     'opacity-40 cursor-not-allowed': Number(p.stok_fisik_saat_ini) <= 0,
                                                                     'cursor-pointer': Number(p.stok_fisik_saat_ini) > 0
                                                                 }"
                                                                 style="padding: 8px 10px; font-size: 12px; display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                                                
                                                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                                                    <span class="badge badge-mono text-[9.5px] font-bold shrink-0" style="padding: 1.5px 5px;" x-text="p.kode_sku"></span>
                                                                    <div class="truncate">
                                                                        <span class="font-semibold text-ink truncate block text-[12px]" x-text="p.nama_item"></span>
                                                                    </div>
                                                                </div>

                                                                <!-- Stock Pill -->
                                                                <div class="shrink-0 flex items-center gap-1.5">
                                                                    <template x-if="Number(p.stok_fisik_saat_ini) > 0">
                                                                        <span class="badge text-[10px] font-mono font-bold"
                                                                              style="background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.25); padding: 1.5px 6px; border-radius: 9999px;"
                                                                              x-text="'Stok: ' + p.stok_fisik_saat_ini + ' ' + (p.satuan_dasar || 'Pcs')"></span>
                                                                    </template>
                                                                    <template x-if="Number(p.stok_fisik_saat_ini) <= 0">
                                                                        <span class="badge text-[10px] font-mono font-bold"
                                                                              style="background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.25); padding: 1.5px 6px; border-radius: 9999px;">
                                                                            Stok Habis
                                                                        </span>
                                                                    </template>
                                                                    <template x-if="String(p.id) === String(row.item_id)">
                                                                        <i data-lucide="check" style="width: 14px; height: 14px; color: #059669;"></i>
                                                                    </template>
                                                                </div>

                                                            </div>
                                                        </template>

                                                        <!-- Empty Search Results -->
                                                        <template x-if="getFilteredBonusProducts(row).length === 0">
                                                            <div style="padding: 14px 10px; text-align: center; color: var(--color-ink-mute); font-size: 11.5px;">
                                                                <i data-lucide="search-x" style="width: 18px; height: 18px; margin: 0 auto 4px auto; opacity: 0.6; display: block;"></i>
                                                                <span>Produk tidak ditemukan</span>
                                                            </div>
                                                        </template>
                                                    </div>

                                                </div>
                                            </div>

                                            <!-- Row 2: Qty (Pcs) & Alasan Bonus Side-by-Side (2 Columns) -->
                                            <div class="grid grid-cols-12 gap-4 items-start mt-4">
                                                <!-- Qty -->
                                                <div class="col-span-4 sm:col-span-3 flex flex-col gap-1.5">
                                                    <label class="form-label text-xs font-semibold" style="font-size: 12px; line-height: 1;">
                                                        Qty <span class="text-ink-mute font-normal">(Pcs)</span> <span class="text-rose-500">*</span>
                                                    </label>
                                                    <input type="number" x-model.number="row.qty" :min="1" :max="getAvailableStock(row.item_id)" class="form-input font-medium w-full text-left" style="height: 44px; border-radius: var(--rounded-md, 8px); padding-left: 12px; font-size: 14px; font-family: var(--font-sans);" placeholder="1" required>
                                                </div>

                                                <!-- Alasan -->
                                                <div class="col-span-8 sm:col-span-9 flex flex-col gap-1.5">
                                                    <label class="form-label text-xs font-semibold" style="font-size: 12px; line-height: 1;">Alasan Bonus <span class="text-rose-500">*</span></label>
                                                    <div class="relative w-full">
                                                        <select x-model="row.reason" class="form-input font-medium w-full" style="height: 44px; border-radius: var(--rounded-md, 8px); padding-left: 12px; font-size: 14px; padding-right: 36px; background-color: var(--color-canvas); cursor: pointer; appearance: none; -webkit-appearance: none;">
                                                            <option value="Bonus Promo Gudang">Bonus Promo Gudang</option>
                                                            <option value="Tester Produk Baru">Tester Produk Baru</option>
                                                            <option value="Bonus Toko">Bonus Toko</option>
                                                            <option value="Pengganti / Kompensasi">Pengganti / Kompensasi</option>
                                                            <option value="Lainnya">Lainnya (Catatan Manual)</option>
                                                        </select>
                                                        <i data-lucide="chevron-down" style="width: 16px; height: 16px; color: var(--color-ink-mute); position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Row 3: Catatan Khusus if reason === 'Lainnya' -->
                                            <template x-if="row.reason === 'Lainnya'">
                                                <div class="mt-4 flex flex-col gap-1.5">
                                                    <input type="text" x-model="row.custom_reason" class="form-input font-medium w-full" style="height: 44px; border-radius: var(--rounded-md, 8px); padding-left: 12px; font-size: 14px;" placeholder="Tuliskan keterangan / alasan khusus pemberian bonus toko ini..." required>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                </div>

                <!-- MODAL FOOTER -->
                <div class="modal-footer" style="padding-bottom: calc(env(safe-area-inset-bottom, 16px) + 16px); position: sticky; bottom: 0; background: var(--color-surface); z-index: 10;">
                    <button type="button" @click="showConfirmReadyModal = false" class="btn btn-secondary modal-btn-cancel-desktop" :disabled="isSubmitting" style="height: 48px; font-size: 14px; border-radius: var(--rounded-md, 8px);">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary w-full sm:w-auto" :disabled="isSubmitting" style="height: 48px; font-size: 14px; border-radius: var(--rounded-md, 8px); display: inline-flex; align-items: center; justify-content: center; gap: 7px; background: #059669; border-color: #059669;">
                        <template x-if="!isSubmitting">
                            <i data-lucide="package-check" style="width: 18px; height: 18px;"></i>
                        </template>
                        <template x-if="isSubmitting">
                            <i data-lucide="loader-2" class="animate-spin" style="width: 18px; height: 18px;"></i>
                        </template>
                        <span x-text="isSubmitting ? 'Memproses...' : getSubmitButtonText()"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- 7. FLOATING BATCH ACTION BAR (KETIKA >= 1 PO DICENTANG)                   -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="selectedPoIds.length > 0" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-8"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-8"
         class="po-floating-bar">
        <div style="background: #0f172a; color: #ffffff; border-radius: 16px; padding: 12px 18px; box-shadow: 0 12px 30px -5px rgba(0, 0, 0, 0.4), 0 8px 12px -6px rgba(0, 0, 0, 0.3); display: flex; align-items: center; justify-content: space-between; gap: 12px; border: 1px solid rgba(255, 255, 255, 0.12);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="badge" style="background: #2563eb; color: #ffffff; font-size: 13px; font-weight: 800; padding: 4px 12px; border-radius: 9999px; line-height: 1; display: inline-flex; align-items: center; justify-content: center;" x-text="selectedPoIds.length + ' PO'"></span>
                <span style="font-size: 13px; font-weight: 600; color: #f8fafc;">Dipilih</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" @click="selectedPoIds = []" class="btn btn-sm" style="background: rgba(255,255,255,0.12); color: #cbd5e1; border: none; font-weight: 600; padding: 7px 14px; border-radius: 10px;">
                    Batal
                </button>
                <?php if (Auth::can('orders.po_print')): ?>
                <a :href="'<?= Router::url('/customer-orders/po-list/batch-pdf?ids=') ?>' + selectedPoIds.join(',') + '<?= $sortParam ?>'" target="_blank"
                   class="btn btn-sm" style="background: #dc2626; color: #ffffff; border: none; font-weight: 800; padding: 7px 18px; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px;">
                    <i data-lucide="file-text" style="width: 15px; height: 15px;"></i>
                    <span x-text="'Unduh PDF (' + selectedPoIds.length + ' PO)'">Unduh PDF</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </template>

</div>

<script>
function poCompactApp() {
    return {
        showItemModal: false,
        showConfirmReadyModal: false,
        isSubmitting: false,
        activePo: null,
        targetPoForReady: null,
        bonusItems: [],
        availableBonusItems: <?= json_encode($availableItems ?? []) ?>,
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
        },

        async downloadPdfWithLoader(url, defaultFilename = 'Dokumen.pdf') {
            if (window.AppAction) window.AppAction.show('Mengunduh PDF...');
            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error('Gagal mengunduh');
                
                let filename = defaultFilename;
                const disposition = response.headers.get('content-disposition');
                if (disposition && disposition.indexOf('filename=') !== -1) {
                    const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = matches[1].replace(/['"]/g, '');
                    }
                }
                
                const blob = await response.blob();
                const a = document.createElement('a');
                a.href = window.URL.createObjectURL(blob);
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(a.href);
            } catch (err) {
                console.error(err);
                if (window.toast) window.toast.error('Gagal mengunduh dokumen PDF.');
            } finally {
                if (window.AppAction) window.AppAction.hide();
            }
        },

        openConfirmReadyModal(po) {
            this.targetPoForReady = po;
            this.bonusItems = [];
            this.isSubmitting = false;
            this.showItemModal = false;
            this.showConfirmReadyModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        addBonusRow() {
            this.bonusItems.forEach(r => r.dropdownOpen = false);
            this.bonusItems.push({
                item_id: '',
                qty: 1,
                reason: 'Bonus Toko',
                custom_reason: '',
                search: '',
                dropdownOpen: true,
                highlightedIndex: 0
            });
            const newIdx = this.bonusItems.length - 1;
            this.$nextTick(() => {
                const searchInput = document.getElementById('bonus-search-' + newIdx);
                if (searchInput) searchInput.focus();
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        removeBonusRow(index) {
            this.bonusItems.splice(index, 1);
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        getItemById(itemId) {
            if (!itemId) return null;
            return this.availableBonusItems.find(i => String(i.id) === String(itemId)) || null;
        },

        getAvailableStock(itemId) {
            if (!itemId) return 9999;
            const found = this.getItemById(itemId);
            return found ? Number(found.stok_fisik_saat_ini) : 0;
        },

        onBonusItemChange(row) {
            const maxStock = this.getAvailableStock(row.item_id);
            if (row.qty > maxStock) {
                row.qty = Math.max(1, maxStock);
            }
        },

        getFilteredBonusProducts(row) {
            const q = (row.search || '').trim().toLowerCase();
            if (!q) return this.availableBonusItems;
            return this.availableBonusItems.filter(p => {
                const name = (p.nama_item || '').toLowerCase();
                const sku = (p.kode_sku || '').toLowerCase();
                return name.includes(q) || sku.includes(q);
            });
        },

        toggleBonusDropdown(row, bIdx) {
            const wasOpen = row.dropdownOpen;
            this.bonusItems.forEach((r, idx) => {
                if (idx !== bIdx) r.dropdownOpen = false;
            });
            row.dropdownOpen = !wasOpen;
            if (row.dropdownOpen) {
                row.search = '';
                row.highlightedIndex = 0;
                this.$nextTick(() => {
                    const searchInput = document.getElementById('bonus-search-' + bIdx);
                    if (searchInput) searchInput.focus();
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        selectBonusProduct(row, product) {
            if (!product || Number(product.stok_fisik_saat_ini) <= 0) return;
            row.item_id = product.id;
            row.dropdownOpen = false;
            row.search = '';
            this.onBonusItemChange(row);
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        clearBonusProduct(row) {
            row.item_id = '';
            row.dropdownOpen = false;
            row.search = '';
            row.qty = 1;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        navigateBonusDropdown(row, bIdx, step) {
            const filtered = this.getFilteredBonusProducts(row);
            if (!filtered || filtered.length === 0) return;
            const max = filtered.length - 1;
            let current = row.highlightedIndex ?? 0;
            current += step;
            if (current < 0) current = max;
            if (current > max) current = 0;
            row.highlightedIndex = current;
            this.$nextTick(() => {
                const optEl = document.getElementById('bonus-opt-' + bIdx + '-' + current);
                if (optEl) optEl.scrollIntoView({ block: 'nearest' });
            });
        },

        selectHighlightedBonus(row) {
            const filtered = this.getFilteredBonusProducts(row);
            if (filtered && filtered.length > 0) {
                const idx = row.highlightedIndex ?? 0;
                const target = filtered[idx];
                if (target && Number(target.stok_fisik_saat_ini) > 0) {
                    this.selectBonusProduct(row, target);
                }
            }
        },

        getValidBonusCount() {
            return this.bonusItems.filter(b => b.item_id && Number(b.qty) > 0).length;
        },

        getSubmitButtonText() {
            const count = this.getValidBonusCount();
            if (count > 0) {
                return 'Konfirmasi Siap Dikirim + ' + count + ' Bonus';
            }
            return 'Konfirmasi Siap Dikirim';
        },

        onSubmitReadyForm(event) {
            if (this.isSubmitting) {
                event.preventDefault();
                return false;
            }

            for (let i = 0; i < this.bonusItems.length; i++) {
                const b = this.bonusItems[i];
                if (!b.item_id) {
                    const msg = 'Harap pilih produk bonus pada baris ke-' + (i + 1) + ' atau hapus baris jika tidak jadi.';
                    if (window.toast && window.toast.warning) {
                        window.toast.warning(msg);
                    } else if (window.AppAlert) {
                        window.AppAlert({ title: 'Item Bonus Belum Dipilih', message: msg, type: 'warning' });
                    }
                    event.preventDefault();
                    return false;
                }
                const maxStock = this.getAvailableStock(b.item_id);
                if (Number(b.qty) <= 0) {
                    const msg = 'Kuantitas bonus baris ke-' + (i + 1) + ' harus minimal 1 pcs.';
                    if (window.toast && window.toast.warning) {
                        window.toast.warning(msg);
                    } else if (window.AppAlert) {
                        window.AppAlert({ title: 'Kuantitas Tidak Valid', message: msg, type: 'warning' });
                    }
                    event.preventDefault();
                    return false;
                }
                if (Number(b.qty) > maxStock) {
                    const msg = 'Kuantitas bonus baris ke-' + (i + 1) + ' melebihi sisa stok fisik di gudang (Maksimal: ' + maxStock + ' pcs).';
                    if (window.toast && window.toast.warning) {
                        window.toast.warning(msg);
                    } else if (window.AppAlert) {
                        window.AppAlert({ title: 'Stok Tidak Mencukupi', message: msg, type: 'warning' });
                    }
                    event.preventDefault();
                    return false;
                }
                if (b.reason === 'Lainnya' && (!b.custom_reason || b.custom_reason.trim() === '')) {
                    const msg = 'Harap tuliskan keterangan khusus bonus pada baris ke-' + (i + 1) + '.';
                    if (window.toast && window.toast.warning) {
                        window.toast.warning(msg);
                    } else if (window.AppAlert) {
                        window.AppAlert({ title: 'Keterangan Kosong', message: msg, type: 'warning' });
                    }
                    event.preventDefault();
                    return false;
                }
            }

            this.isSubmitting = true;
            return true;
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
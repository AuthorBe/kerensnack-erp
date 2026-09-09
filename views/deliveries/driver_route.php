<?php 
use App\Core\Router; 
use App\Core\Auth;
use App\Helpers\Format;

ob_start();
?>

<style>
/* ========================================================================= */
/* DRIVER DELIVERY PORTAL / COMPACT CARDS & SPACIOUS TABBED MODAL SYSTEM     */
/* ========================================================================= */

.driver-stat-card {
    background: var(--color-surface);
    border: 1px solid var(--color-hairline);
    border-radius: 18px;
    padding: 18px 22px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
.driver-stat-card:hover {
    border-color: var(--color-hairline-strong);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.04);
}

.driver-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Compact 2-Line Route Card */
.driver-compact-card {
    background: var(--color-surface);
    border: 1px solid var(--color-hairline);
    border-radius: 20px;
    padding: 18px 24px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
    cursor: pointer;
    position: relative;
}
@media (max-width: 640px) {
    .driver-compact-card {
        padding: 15px 16px;
        border-radius: 16px;
        gap: 10px;
    }
}
.driver-compact-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.08);
    transform: translateY(-1px);
}

.driver-compact-card.is-in-transit {
    border-color: #93c5fd;
    background: linear-gradient(180deg, rgba(239, 246, 255, 0.65) 0%, var(--color-surface) 100%);
}
.dark .driver-compact-card.is-in-transit {
    border-color: #1e3a8a;
    background: linear-gradient(180deg, rgba(30, 58, 138, 0.14) 0%, var(--color-surface) 100%);
}

.driver-compact-card.is-completed {
    border-color: #bbf7d0;
}
.dark .driver-compact-card.is-completed {
    border-color: #064e3b;
}

.driver-compact-card.is-failed {
    border-color: #fecaca;
    background: linear-gradient(180deg, rgba(254, 242, 242, 0.65) 0%, var(--color-surface) 100%);
}
.dark .driver-compact-card.is-failed {
    border-color: #7f1d1d;
    background: linear-gradient(180deg, rgba(127, 29, 29, 0.14) 0%, var(--color-surface) 100%);
}

/* Quick Action Links in Modal */
.quick-action-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.15s ease;
}

.quick-action-pill.is-maps {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}
.quick-action-pill.is-maps:hover {
    background: #d1fae5;
    color: #065f46;
}
.dark .quick-action-pill.is-maps {
    background: rgba(16, 185, 129, 0.14);
    color: #34d399;
    border-color: rgba(52, 211, 153, 0.3);
}

.quick-action-pill.is-wa {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.quick-action-pill.is-wa:hover {
    background: #dcfce7;
    color: #166534;
}
.dark .quick-action-pill.is-wa {
    background: rgba(34, 197, 94, 0.14);
    color: #4ade80;
    border-color: rgba(74, 222, 128, 0.3);
}

/* Pill Tabs */
.driver-tabs-wrapper {
    display: flex;
    align-items: center;
    background: var(--color-canvas-soft);
    padding: 6px;
    border-radius: 16px;
    border: 1px solid var(--color-hairline);
    gap: 6px;
    overflow-x: auto;
    scrollbar-width: none;
}
.driver-tabs-wrapper::-webkit-scrollbar {
    display: none;
}

.driver-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 11px;
    font-size: 13px;
    font-weight: 600;
    color: var(--color-ink-mute);
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.15s ease;
}
.driver-tab-btn.is-active {
    background: var(--color-canvas) !important;
    color: #2563eb !important;
    font-weight: 800 !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), 0 0 0 1px var(--color-hairline) !important;
}
.dark .driver-tab-btn.is-active {
    color: #60a5fa !important;
}

/* Konfirmasi Gagal Kirim Action Button */
.btn-driver-fail-confirm {
    background: #e11d48 !important;
    border: 1px solid #e11d48 !important;
    color: #ffffff !important;
    font-weight: 800 !important;
    font-size: 13.5px !important;
    border-radius: 12px !important;
    padding: 10px 24px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    box-shadow: 0 2px 8px rgba(225, 29, 72, 0.25) !important;
    transition: all 0.18s ease !important;
    cursor: pointer;
}
.btn-driver-fail-confirm:hover {
    background: #be123c !important;
    border-color: #be123c !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(225, 29, 72, 0.38) !important;
    transform: translateY(-1px);
}
.btn-driver-fail-confirm:active {
    background: #9f1239 !important;
    border-color: #9f1239 !important;
    transform: translateY(0);
}
.btn-driver-fail-confirm i,
.btn-driver-fail-confirm svg {
    color: #ffffff !important;
    stroke: #ffffff !important;
}

/* ========================================================================= */
/* STYLES INTERACTIVE PHOTO VIEWER (PINCH, PAN & MOBILE FULLSCREEN)          */
/* ========================================================================= */
.receipt-backdrop {
    z-index: 99999;
    background: rgba(15, 23, 42, 0.65) !important;
    backdrop-filter: blur(14px) saturate(160%) !important;
    -webkit-backdrop-filter: blur(14px) saturate(160%) !important;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    position: fixed;
    inset: 0;
    overscroll-behavior: contain;
    touch-action: none;
}
.receipt-container {
    width: 100%;
    max-width: 980px;
    height: 88vh;
    max-height: 88vh;
    display: flex;
    flex-direction: column;
    background: var(--color-surface, #ffffff);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.7);
    border: 1px solid var(--color-hairline);
    position: relative;
}
.receipt-viewport {
    flex: 1;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #050811;
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
}
.receipt-floating-toolbar {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 30;
    display: flex;
    align-items: center;
    gap: 4px;
    background: rgba(15, 23, 42, 0.88);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    padding: 5px 8px;
    border-radius: 9999px;
    border: 1px solid rgba(255, 255, 255, 0.18);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
    color: #f8fafc;
    user-select: none;
}
.receipt-tool-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: transparent;
    border: none;
    color: #f8fafc;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
}
.receipt-tool-btn:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.16);
    color: #ffffff;
}
.receipt-tool-btn:active:not(:disabled) {
    transform: scale(0.92);
}
.receipt-tool-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}
.receipt-tool-badge {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 9999px;
    padding: 4px 10px;
    font-family: var(--font-mono);
    font-size: 11.5px;
    font-weight: 700;
    color: #f8fafc;
    cursor: pointer;
    transition: background 0.15s ease;
}
.receipt-tool-badge:hover {
    background: rgba(255, 255, 255, 0.2);
}
.receipt-tool-divider {
    width: 1px;
    height: 18px;
    background: rgba(255, 255, 255, 0.2);
    margin: 0 3px;
}

@media (max-width: 640px) {
    .receipt-backdrop {
        padding: 0 !important;
    }
    .receipt-container {
        max-width: 100vw !important;
        width: 100vw !important;
        height: 100dvh !important;
        max-height: 100dvh !important;
        border-radius: 0 !important;
        border: none !important;
    }
    .receipt-floating-toolbar {
        bottom: calc(16px + env(safe-area-inset-bottom, 0px));
        gap: 6px;
        padding: 6px 12px;
    }
    .receipt-tool-btn {
        width: 40px;
        height: 40px;
    }
    .receipt-tool-badge {
        padding: 5px 12px;
        font-size: 12px;
    }
}
</style>

<div x-data="driverDeliveryApp()" x-init="init()" class="space-y-6 sm:space-y-7">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER & FILTER TANGGAL / DRIVER (LOCKED FOR SALES/DRIVER)        -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-blue" style="width: 48px; height: 48px; border-radius: 14px;">
                <i data-lucide="navigation"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color: #2563eb;"></span>
                    <span>Portal Lapangan Driver</span>
                </div>
                <h1 class="page-title">Pengiriman &amp; Rute Toko</h1>
                <p class="page-subtitle">Daftar Kunjungan Toko, Navigasi Rute &amp; Serah Terima Muatan</p>
            </div>
        </div>

        <!-- Filter Tanggal & Driver Selector -->
        <form method="GET" action="<?= Router::url('/driver-deliveries') ?>" class="flex items-center gap-3 flex-wrap">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter ?? 'semua') ?>">
            
            <input type="date" name="date" value="<?= htmlspecialchars($selectedDate ?? date('Y-m-d')) ?>" 
                   class="form-input font-medium" style="height: 42px; font-size: 13px; border-radius: 12px; width: 150px;"
                   onchange="this.form.submit()">

            <?php if (!empty($isRestricted)): ?>
                <!-- TERKUNCI UNTUK ROLE SALES & DRIVER -->
                <div class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-canvas-soft border border-hairline text-xs font-semibold text-ink-secondary shadow-xs" title="Armada terkunci pada akun Anda">
                    <i data-lucide="lock" style="width: 14px; height: 14px; color: var(--color-ink-mute);"></i>
                    <span>Armada: <strong class="text-ink"><?= htmlspecialchars($myDriverName ?? 'Driver Saya') ?></strong></span>
                </div>
                <input type="hidden" name="driver_id" value="<?= htmlspecialchars($currentEmployeeId ?? '') ?>">
            <?php else: ?>
                <!-- DROPDOWN FILTER UNTUK MANAGER / OWNER / DEVELOPER -->
                <select name="driver_id" class="form-input font-medium" style="height: 42px; font-size: 13px; border-radius: 12px; width: 195px;" onchange="this.form.submit()">
                    <option value="">-- Semua Driver --</option>
                    <?php foreach ($drivers as $dr): ?>
                        <option value="<?= $dr['id'] ?>" <?= ($filterDriver === $dr['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dr['nama_karyawan']) ?> <?= !empty($dr['nomor_polisi_kendaraan']) ? '(' . htmlspecialchars($dr['nomor_polisi_kendaraan']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. 4 KARTU RINGKASAN RUTE HARI INI                                        -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        
        <!-- 1. Total Toko Tujuan -->
        <div class="driver-stat-card">
            <div class="driver-stat-icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                <i data-lucide="store" style="width: 25px; height: 25px;"></i>
            </div>
            <div>
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--color-ink-mute); letter-spacing: 0.05em; margin-bottom: 2px;">Toko Tujuan</div>
                <div style="font-size: 25px; font-weight: 900; color: var(--color-ink); line-height: 1.1; font-family: var(--font-sans), sans-serif;">
                    <?= $metrics['count_total'] ?> <span style="font-size: 12.5px; font-weight: 700; color: #2563eb;">Toko</span>
                </div>
            </div>
        </div>

        <!-- 2. Sedang Dalam Perjalanan / Belum -->
        <div class="driver-stat-card">
            <div class="driver-stat-icon" style="background: rgba(234, 88, 12, 0.1); color: #ea580c;">
                <i data-lucide="truck" style="width: 25px; height: 25px;"></i>
            </div>
            <div>
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--color-ink-mute); letter-spacing: 0.05em; margin-bottom: 2px;">Belum Selesai</div>
                <div style="font-size: 25px; font-weight: 900; color: var(--color-ink); line-height: 1.1; font-family: var(--font-sans), sans-serif;">
                    <?= $metrics['count_pending'] + $metrics['count_in_transit'] ?> <span style="font-size: 12.5px; font-weight: 700; color: #ea580c;">Toko</span>
                </div>
            </div>
        </div>

        <!-- 3. Selesai Dikirim -->
        <div class="driver-stat-card">
            <div class="driver-stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #059669;">
                <i data-lucide="check-circle-2" style="width: 25px; height: 25px;"></i>
            </div>
            <div>
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--color-ink-mute); letter-spacing: 0.05em; margin-bottom: 2px;">Selesai Kirim</div>
                <div style="font-size: 25px; font-weight: 900; color: var(--color-ink); line-height: 1.1; font-family: var(--font-sans), sans-serif;">
                    <?= $metrics['count_completed'] ?> <span style="font-size: 12.5px; font-weight: 700; color: #10b981;">Toko</span>
                </div>
            </div>
        </div>

        <!-- 4. Gagal Kirim -->
        <div class="driver-stat-card">
            <div class="driver-stat-icon" style="background: rgba(225, 29, 72, 0.1); color: #e11d48;">
                <i data-lucide="alert-octagon" style="width: 25px; height: 25px;"></i>
            </div>
            <div>
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--color-ink-mute); letter-spacing: 0.05em; margin-bottom: 2px;">Gagal Kirim</div>
                <div style="font-size: 25px; font-weight: 900; color: var(--color-ink); line-height: 1.1; font-family: var(--font-sans), sans-serif;">
                    <?= $metrics['count_failed'] ?> <span style="font-size: 12.5px; font-weight: 700; color: #f43f5e;">Toko</span>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 3. SEGMENTED FILTER PILLS                                                 -->
    <!-- ========================================================================= -->
    <div class="driver-tabs-wrapper table-scroll" data-table-scroll>
        <a href="<?= Router::url('/driver-deliveries?status=semua&date=' . urlencode($selectedDate) . (!empty($filterDriver) ? '&driver_id=' . urlencode($filterDriver) : '')) ?>"
           class="driver-tab-btn <?= ($statusFilter === 'semua') ? 'is-active' : '' ?>">
            <i data-lucide="layers" style="width: 15px; height: 15px;"></i>
            <span>Semua Rute</span>
            <span class="badge" style="font-size: 11px; padding: 1px 7px; font-family: var(--font-sans), sans-serif; font-weight: 800;"><?= $metrics['count_total'] ?></span>
        </a>

        <a href="<?= Router::url('/driver-deliveries?status=in_transit&date=' . urlencode($selectedDate) . (!empty($filterDriver) ? '&driver_id=' . urlencode($filterDriver) : '')) ?>"
           class="driver-tab-btn <?= ($statusFilter === 'in_transit') ? 'is-active' : '' ?>">
            <i data-lucide="truck" style="width: 15px; height: 15px;"></i>
            <span>Sedang Dikirim</span>
            <span class="badge" style="font-size: 11px; padding: 1px 7px; background: rgba(59,130,246,0.15); color: #2563eb; font-family: var(--font-sans), sans-serif; font-weight: 800;"><?= $metrics['count_in_transit'] ?></span>
        </a>

        <a href="<?= Router::url('/driver-deliveries?status=pending&date=' . urlencode($selectedDate) . (!empty($filterDriver) ? '&driver_id=' . urlencode($filterDriver) : '')) ?>"
           class="driver-tab-btn <?= ($statusFilter === 'pending') ? 'is-active' : '' ?>">
            <i data-lucide="clock" style="width: 15px; height: 15px;"></i>
            <span>Siap / Menunggu</span>
            <span class="badge" style="font-size: 11px; padding: 1px 7px; font-family: var(--font-sans), sans-serif; font-weight: 800;"><?= $metrics['count_pending'] ?></span>
        </a>

        <a href="<?= Router::url('/driver-deliveries?status=completed&date=' . urlencode($selectedDate) . (!empty($filterDriver) ? '&driver_id=' . urlencode($filterDriver) : '')) ?>"
           class="driver-tab-btn <?= ($statusFilter === 'completed') ? 'is-active' : '' ?>">
            <i data-lucide="check-circle" style="width: 15px; height: 15px;"></i>
            <span>Selesai</span>
            <span class="badge" style="font-size: 11px; padding: 1px 7px; background: rgba(16,185,129,0.15); color: #059669; font-family: var(--font-sans), sans-serif; font-weight: 800;"><?= $metrics['count_completed'] ?></span>
        </a>

        <a href="<?= Router::url('/driver-deliveries?status=failed&date=' . urlencode($selectedDate) . (!empty($filterDriver) ? '&driver_id=' . urlencode($filterDriver) : '')) ?>"
           class="driver-tab-btn <?= ($statusFilter === 'failed') ? 'is-active' : '' ?>">
            <i data-lucide="alert-triangle" style="width: 15px; height: 15px;"></i>
            <span>Gagal</span>
            <span class="badge" style="font-size: 11px; padding: 1px 7px; background: rgba(225,29,72,0.15); color: #e11d48; font-family: var(--font-sans), sans-serif; font-weight: 800;"><?= $metrics['count_failed'] ?></span>
        </a>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. DAFTAR CARD RUTE SIMPEL 2-BARIS (COMPACT & SUPER HEMAT RUANG)          -->
    <!-- ========================================================================= -->
    <div class="space-y-3.5 sm:space-y-4">
        <?php if (empty($deliveries)): ?>
        <div class="card" style="border-radius: 20px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 64px 20px;">
            <div style="width: 68px; height: 68px; border-radius: 50%; background: var(--color-canvas-soft); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; color: var(--color-ink-mute);">
                <i data-lucide="truck" style="width: 34px; height: 34px; opacity: 0.6;"></i>
            </div>
            <div style="font-weight: 800; font-size: 17px; color: var(--color-ink); text-align: center;">Tidak Ada Tugas Pengiriman</div>
            <p style="font-size: 13.5px; margin-top: 6px; color: var(--color-ink-mute); text-align: center; max-width: 440px; line-height: 1.5;">
                Tidak ada rute pengiriman aktif untuk tanggal atau filter armada yang dipilih.
            </p>
        </div>
        <?php else: ?>
        <?php foreach ($deliveries as $idx => $deliv): 
            $statusSj = $deliv['status_surat_jalan'];
            $isInTransit = ($statusSj === 'sedang_dikirim');
            $isCompleted = ($statusSj === 'selesai_diterima');
            $isFailed = ($statusSj === 'gagal_kirim');
            $isPending = in_array($statusSj, ['menunggu_persetujuan', 'draf_n8n', 'siap_kirim', 'disetujui_owner'], true);

            $cardClass = $isInTransit ? 'is-in-transit' : ($isCompleted ? 'is-completed' : ($isFailed ? 'is-failed' : ''));
        ?>
        <!-- CARD SIMPEL 2-BARIS (BERSIH & LAPANG TANPA GARIS PEMISAH DEMPET) -->
        <div class="driver-compact-card <?= $cardClass ?>" @click="openDetailModal(<?= htmlspecialchars(json_encode($deliv)) ?>)">
            
            <!-- BARIS 1: NOMOR STOP, NAMA TOKO, KODE & STATUS -->
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/70 dark:text-blue-400 font-black text-xs shrink-0" style="font-family: var(--font-sans), sans-serif;">
                        #<?= $idx + 1 ?>
                    </span>
                    <span class="font-bold text-base sm:text-lg text-ink truncate">
                        <?= htmlspecialchars($deliv['nama_toko']) ?>
                    </span>
                    <span class="badge badge-mono text-[11px] px-2.5 py-0.5 shrink-0 hidden sm:inline-flex">
                        <?= htmlspecialchars($deliv['kode_pelanggan']) ?>
                    </span>
                    <?php if (!empty($deliv['nama_wilayah'])): ?>
                        <span class="badge badge-secondary text-[11px] hidden md:inline-flex shrink-0">
                            <?= htmlspecialchars($deliv['nama_wilayah']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- BADGE STATUS TAHAP -->
                <div class="flex items-center gap-2 shrink-0">
                    <?php if ($isInTransit): ?>
                        <span class="badge" style="background: #dbeafe; color: #1e40af; font-weight: 800; font-size: 12px; border-radius: 11px; padding: 5px 12px; display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="truck" style="width: 14px; height: 14px;"></i>
                            <span>Sedang Dikirim</span>
                        </span>
                    <?php elseif ($isCompleted): ?>
                        <span class="badge" style="background: #d1fae5; color: #065f46; font-weight: 800; font-size: 12px; border-radius: 11px; padding: 5px 12px; display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="check-circle" style="width: 14px; height: 14px;"></i>
                            <span>Selesai Diterima</span>
                        </span>
                    <?php elseif ($isFailed): ?>
                        <span class="badge" style="background: #ffe4e6; color: #9f1239; font-weight: 800; font-size: 12px; border-radius: 11px; padding: 5px 12px; display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="alert-octagon" style="width: 14px; height: 14px;"></i>
                            <span>Gagal Kirim</span>
                        </span>
                    <?php else: ?>
                        <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 800; font-size: 12px; border-radius: 11px; padding: 5px 12px; display: inline-flex; align-items: center; gap: 6px;">
                            <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
                            <span>Siap Berangkat</span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- BARIS 2: RINGKASAN MUATAN, METODE BAYAR, TAGIHAN & TOMBOL DETAIL -->
            <div class="flex items-center justify-between gap-3 text-xs text-ink-secondary flex-wrap">
                <div class="flex items-center gap-2.5 sm:gap-3.5 flex-wrap">
                    <span class="font-bold text-ink font-sans text-xs sm:text-sm">
                        📦 <?= number_format($deliv['total_pcs']) ?> Pcs <span class="text-ink-mute font-medium text-xs">(<?= $deliv['total_sku'] ?> SKU)</span>
                    </span>
                    <span class="text-ink-mute">&bull;</span>
                    <span class="badge font-bold uppercase text-[11px] px-2.5 py-0.5 bg-canvas-soft border border-hairline font-sans">
                        <?= strtoupper(str_replace('_', ' ', $deliv['tipe_pembayaran'] ?? 'CASH')) ?>
                    </span>
                    <span class="text-ink-mute">&bull;</span>
                    <span class="font-black text-blue-600 dark:text-blue-400 font-sans text-sm sm:text-base">
                        Rp <?= number_format((float)$deliv['total_netto'], 0, ',', '.') ?>
                    </span>
                    <span class="text-ink-mute hidden lg:inline">&bull;</span>
                    <span class="text-ink-mute hidden lg:inline truncate max-w-[320px]">
                        <?= htmlspecialchars($deliv['alamat_lengkap'] ?: 'Alamat belum diatur') ?>
                    </span>
                </div>

                <!-- Tombol Aksi Cepat & Detail Rute -->
                <div class="flex items-center gap-2">
                    <?php if ($isCompleted && !empty($deliv['bukti_terima_foto'])): ?>
                        <button type="button" 
                                class="btn btn-secondary btn-sm" 
                                style="font-size: 12px; font-weight: 700; border-radius: 11px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 5px; color: #059669; border-color: #a7f3d0; background: #ecfdf5;"
                                @click.stop="openPhotoViewer('<?= htmlspecialchars($deliv['bukti_terima_foto']) ?>', 'Bukti Serah Terima - <?= htmlspecialchars(addslashes($deliv['nama_toko'])) ?>')">
                            <i data-lucide="image" style="width: 14px; height: 14px;"></i>
                            <span>Foto Bukti</span>
                        </button>
                    <?php elseif ($isFailed && !empty($deliv['foto_bukti_gagal'])): ?>
                        <button type="button" 
                                class="btn btn-secondary btn-sm" 
                                style="font-size: 12px; font-weight: 700; border-radius: 11px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 5px; color: #e11d48; border-color: #fecaca; background: #fff1f2;"
                                @click.stop="openPhotoViewer('<?= htmlspecialchars($deliv['foto_bukti_gagal']) ?>', 'Bukti Gagal Kirim - <?= htmlspecialchars(addslashes($deliv['nama_toko'])) ?>')">
                            <i data-lucide="image" style="width: 14px; height: 14px;"></i>
                            <span>Foto Gagal</span>
                        </button>
                    <?php endif; ?>

                    <button type="button" 
                            class="btn btn-secondary btn-sm" 
                            style="font-size: 12.5px; font-weight: 700; border-radius: 11px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;"
                            @click.stop="openDetailModal(<?= htmlspecialchars(json_encode($deliv)) ?>)">
                        <i data-lucide="eye" style="width: 14px; height: 14px; color: #2563eb;"></i>
                        <span>Detail Rute</span>
                    </button>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. POP-UP MODAL DETAIL LENGKAP BERTAB (SPACIOUS & BEAUTIFULLY SPACED)      -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showDetailModal" x-cloak class="modal-backdrop" @click.self="closeDetailModal()" style="z-index: 9999;">
        <div class="modal-box modal-box-lg" style="max-width: 760px; padding: 0; border-radius: 24px; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;" @click.stop>
            
            <!-- MOBILE PULL HANDLE -->
            <div class="sm:hidden w-full flex justify-center pt-3 pb-1 flex-shrink-0" style="background:var(--color-canvas);">
                <div style="width:40px;height:4px;border-radius:2px;background:var(--color-hairline-strong);"></div>
            </div>

            <!-- 1. MODAL HEADER (SPACIOUS PADDING) -->
            <div style="padding: 22px 28px; border-bottom: 1px solid var(--color-hairline); display: flex; align-items: center; justify-content: space-between; background: var(--color-canvas); flex-shrink: 0; gap: 16px;">
                <div style="display: flex; align-items: center; gap: 14px; min-width: 0; flex: 1;">
                    <div style="width: 48px; height: 48px; border-radius: 15px; background: #eff6ff; color: #1e3a8a; border: 1px solid rgba(30,58,138,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i data-lucide="truck" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div style="min-width: 0; flex: 1;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <h2 style="font-size: 18px; font-weight: 900; color: var(--color-ink); margin: 0; line-height: 1.25;" x-text="activeDelivery?.nama_toko"></h2>
                            <span class="badge badge-mono text-xs font-bold" x-text="activeDelivery?.kode_pelanggan"></span>
                            
                            <!-- Status Badge -->
                            <template x-if="activeDelivery?.status_surat_jalan === 'sedang_dikirim'">
                                <span class="badge" style="background: #dbeafe; color: #1e40af; font-weight: 800; font-size: 11.5px; border-radius: 9px; padding: 3px 9px;">Sedang Dikirim</span>
                            </template>
                            <template x-if="activeDelivery?.status_surat_jalan === 'selesai_diterima'">
                                <span class="badge" style="background: #d1fae5; color: #065f46; font-weight: 800; font-size: 11.5px; border-radius: 9px; padding: 3px 9px;">Selesai Diterima</span>
                            </template>
                            <template x-if="activeDelivery?.status_surat_jalan === 'gagal_kirim'">
                                <span class="badge" style="background: #ffe4e6; color: #9f1239; font-weight: 800; font-size: 11.5px; border-radius: 9px; padding: 3px 9px;">Gagal Kirim</span>
                            </template>
                            <template x-if="['menunggu_persetujuan', 'draf_n8n', 'siap_kirim', 'disetujui_owner'].includes(activeDelivery?.status_surat_jalan)">
                                <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 800; font-size: 11.5px; border-radius: 9px; padding: 3px 9px;">Siap Berangkat</span>
                            </template>
                        </div>
                        <div style="font-size: 12.5px; color: var(--color-ink-mute); margin-top: 6px; font-family: var(--font-sans), sans-serif;">
                            <span>SJ: <strong class="text-ink-secondary" x-text="activeDelivery?.nomor_surat_jalan"></strong></span> &bull; 
                            <span>Nota: <strong class="text-ink-secondary" x-text="'#' + activeDelivery?.nomor_nota"></strong></span>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 16px; flex-shrink: 0;">
                    <div class="hidden md:flex flex-col items-end">
                        <span style="font-size: 10.5px; color: var(--color-ink-mute); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Total Tagihan</span>
                        <span class="font-black text-blue-600 dark:text-blue-400 font-sans" style="font-size: 17px;" x-text="'Rp ' + formatRupiah(activeDelivery?.total_netto)"></span>
                    </div>
                    <button type="button" @click="closeDetailModal()" class="btn btn-ghost btn-sm" style="width: 38px; height: 38px; padding: 0; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--color-ink-mute);" aria-label="Tutup">
                        <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                    </button>
                </div>
            </div>

            <!-- 2. TAB NAVIGATION BAR (HANYA MUNCUL DI MODE VIEW DETAIL) -->
            <template x-if="viewMode === 'detail'">
                <div class="modal-tab-nav custom-scrollbar" style="padding: 10px 24px;">
                    <button type="button" @click="activeTab = 'info'" class="modal-tab-btn" :class="{ 'is-active': activeTab === 'info' }">
                        <i data-lucide="map-pin" style="width: 14px; height: 14px;"></i>
                        <span>Info Toko &amp; Rute</span>
                    </button>
                    <button type="button" @click="activeTab = 'items'" class="modal-tab-btn" :class="{ 'is-active': activeTab === 'items' }">
                        <i data-lucide="package" style="width: 14px; height: 14px;"></i>
                        <span>Rincian Barang</span>
                        <span class="badge" style="font-size: 10px; padding: 1px 6px; border-radius: 10px;" x-text="activeDelivery?.items?.length || '0'"></span>
                    </button>
                    <button type="button" @click="activeTab = 'payment'" class="modal-tab-btn" :class="{ 'is-active': activeTab === 'payment' }">
                        <i data-lucide="credit-card" style="width: 14px; height: 14px;"></i>
                        <span>Pembayaran &amp; Tagihan</span>
                    </button>
                </div>
            </template>

            <!-- 3. MODAL BODY (SCROLLABLE & SPACIOUS) -->
            <div class="modal-tab-body custom-scrollbar" style="padding: 26px 28px; overflow-y: auto; flex: 1;">

                <!-- ================================================================= -->
                <!-- A. MODE 1: VIEW DETAIL BERTAB                                     -->
                <!-- ================================================================= -->
                <div x-show="viewMode === 'detail'" class="space-y-6">
                    
                    <!-- TAB 1: INFO TOKO & RUTE -->
                    <div x-show="activeTab === 'info'" class="space-y-5">
                        
                        <!-- Box Identitas Toko & Alamat -->
                        <div style="background: var(--color-canvas-soft); border: 1px solid var(--color-hairline); border-radius: 20px; padding: 22px 24px;" class="space-y-5">
                            
                            <div class="flex items-start justify-between gap-4 flex-wrap">
                                <div>
                                    <div style="font-size: 10.5px; color: var(--color-ink-mute); font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Toko Pelanggan</div>
                                    <div style="font-size: 17px; font-weight: 800; color: var(--color-ink);" x-text="activeDelivery?.nama_toko"></div>
                                    <div style="font-size: 13px; color: var(--color-ink-secondary); margin-top: 4px;">
                                        Pemilik: <strong style="color: var(--color-ink);" x-text="activeDelivery?.nama_pemilik || '-'"></strong>
                                        <template x-if="activeDelivery?.nama_wilayah">
                                            <span> &bull; Wilayah: <span class="badge badge-secondary ml-1" style="font-size: 11px;" x-text="activeDelivery?.nama_wilayah"></span></span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Quick WA Button -->
                                <template x-if="activeDelivery?.nomor_whatsapp">
                                    <a :href="'https://wa.me/' + cleanWa(activeDelivery?.nomor_whatsapp) + '?text=' + encodeURIComponent('Halo ' + (activeDelivery?.nama_toko || '') + ', armada KEREN Snack sedang menuju ke toko Anda untuk pengiriman nota #' + (activeDelivery?.nomor_nota || '') + '.')" target="_blank" class="quick-action-pill is-wa">
                                        <i data-lucide="message-circle" style="width: 15px; height: 15px;"></i>
                                        <span>WhatsApp ( <span x-text="activeDelivery?.nomor_whatsapp"></span> )</span>
                                    </a>
                                </template>
                            </div>

                            <div style="height: 1px; background: var(--color-hairline);"></div>

                            <!-- Alamat Lengkap & Maps -->
                            <div class="space-y-3">
                                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-ink-mute">
                                    <i data-lucide="map-pin" style="width: 16px; height: 16px; color: #ef4444;"></i>
                                    <span>Alamat Lengkap Pengiriman:</span>
                                </div>
                                <div class="text-sm font-medium text-ink leading-relaxed" style="padding-left: 24px;" x-text="activeDelivery?.alamat_lengkap || 'Alamat toko belum diatur'"></div>
                                
                                <div style="padding-left: 24px;" class="pt-1.5 flex items-center gap-2">
                                    <a :href="activeDelivery?.link_google_maps || ('https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent((activeDelivery?.nama_toko || '') + ' ' + (activeDelivery?.alamat_lengkap || '')))" target="_blank" rel="noopener noreferrer" class="quick-action-pill is-maps">
                                        <i data-lucide="map-pin" style="width: 15px; height: 15px; color: #ef4444;" x-show="activeDelivery?.link_google_maps"></i>
                                        <i data-lucide="map" style="width: 15px; height: 15px;" x-show="!activeDelivery?.link_google_maps"></i>
                                        <span x-text="activeDelivery?.link_google_maps ? 'Buka Titik Presisi Google Maps' : 'Buka Google Maps Navigasi'"></span>
                                        <template x-if="activeDelivery?.link_google_maps">
                                            <span class="badge" style="background:#d1fae5; color:#065f46; font-size:10px; font-weight:800; padding:1px 6px; border-radius:5px; margin-left:4px;">Presisi</span>
                                        </template>
                                    </a>
                                </div>
                            </div>

                        </div>

                        <!-- Box Info Operasional Surat Jalan -->
                        <div style="background: var(--color-canvas-soft); border: 1px solid var(--color-hairline); border-radius: 20px; padding: 20px 24px;">
                            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--color-ink-mute); letter-spacing: 0.05em; margin-bottom: 14px;">Data Penugasan &amp; Armada</div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                <div>
                                    <span class="text-ink-mute">Nomor Surat Jalan:</span>
                                    <div class="font-mono font-bold text-ink text-sm mt-1" x-text="activeDelivery?.nomor_surat_jalan"></div>
                                </div>
                                <div>
                                    <span class="text-ink-mute">Nomor Nota Transaksi:</span>
                                    <div class="font-mono font-bold text-ink text-sm mt-1" x-text="'#' + activeDelivery?.nomor_nota"></div>
                                </div>
                                <div>
                                    <span class="text-ink-mute">Tanggal Pesanan:</span>
                                    <div class="font-medium text-ink text-sm mt-1" x-text="activeDelivery?.tanggal_pesanan"></div>
                                </div>
                                <div>
                                    <span class="text-ink-mute">Armada Driver Ditugaskan:</span>
                                    <div class="font-bold text-ink text-sm mt-1" x-text="(activeDelivery?.nama_driver || '-') + (activeDelivery?.nopol_driver ? ' (' + activeDelivery?.nopol_driver + ')' : '')"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Box Info Penyelesaian (Jika Selesai Diterima) -->
                        <template x-if="activeDelivery?.status_surat_jalan === 'selesai_diterima' && activeDelivery?.nama_penerima_toko">
                            <div class="p-4.5 bg-emerald-50 dark:bg-emerald-950/25 rounded-2xl text-xs sm:text-sm text-emerald-900 dark:text-emerald-300 flex items-center gap-3" style="border: none;">
                                <i data-lucide="check-circle" style="width: 20px; height: 20px; color: #059669; flex-shrink: 0;"></i>
                                <div>
                                    <strong>Penerima di Toko:</strong> <span x-text="activeDelivery?.nama_penerima_toko"></span>
                                    <template x-if="activeDelivery?.waktu_sampai">
                                        <span class="text-emerald-700 dark:text-emerald-400 ml-1.5">&bull; Selesai: <span x-text="formatDateTime(activeDelivery?.waktu_sampai)"></span></span>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Box Info Kendala (Jika Gagal Kirim) -->
                        <template x-if="activeDelivery?.status_surat_jalan === 'gagal_kirim'">
                            <div class="p-4.5 bg-rose-50 dark:bg-rose-950/25 rounded-2xl text-xs sm:text-sm text-rose-900 dark:text-rose-300 flex items-start gap-3" style="border: none;">
                                <i data-lucide="alert-octagon" style="width: 20px; height: 20px; color: #e11d48; flex-shrink: 0; margin-top: 2px;"></i>
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-rose-950 dark:text-rose-200">
                                        <span>Kendala: </span>
                                        <span x-text="activeDelivery?.alasan_gagal || 'Pengiriman Gagal'"></span>
                                    </div>
                                    <template x-if="activeDelivery?.catatan_gagal">
                                        <div class="text-rose-700 dark:text-rose-400 mt-1 text-xs" x-text="activeDelivery.catatan_gagal"></div>
                                    </template>
                                    <template x-if="activeDelivery?.waktu_gagal_kirim">
                                        <div class="text-rose-600 dark:text-rose-400 mt-1 text-[11px]" x-text="'Waktu: ' + formatDateTime(activeDelivery.waktu_gagal_kirim)"></div>
                                    </template>
                                </div>
                            </div>
                        </template>

                    </div>

                    <!-- TAB 2: RINCIAN BARANG -->
                    <div x-show="activeTab === 'items'" class="space-y-4">
                        
                        <!-- Ringkasan Muatan Chip Strip -->
                        <div class="flex items-center justify-between gap-3 p-4 bg-canvas-soft border border-hairline rounded-2xl text-xs flex-wrap">
                            <div class="flex items-center gap-2">
                                <i data-lucide="package" style="width: 17px; height: 17px; color: #2563eb;"></i>
                                <span class="font-bold text-ink">Total Muatan:</span>
                                <span class="font-black text-blue-600 dark:text-blue-400 font-sans text-sm" x-text="activeDelivery?.total_pcs + ' Pcs'"></span>
                            </div>
                            <div>
                                <span class="badge badge-secondary font-bold text-xs px-3 py-1" x-text="activeDelivery?.total_sku + ' SKU Produk'"></span>
                            </div>
                        </div>

                        <!-- Tabel Barang -->
                        <div class="table-scroll" style="max-height: 340px; border: 1px solid var(--color-hairline); border-radius: 18px; overflow: hidden;">
                            <table class="table" style="margin: 0; width: 100%; font-size: 13px;">
                                <thead style="background: var(--color-canvas-soft); position: sticky; top: 0; z-index: 2;">
                                    <tr style="border-bottom: 1px solid var(--color-hairline);">
                                        <th class="cell-center" style="width: 45px; padding: 12px 16px;">No</th>
                                        <th style="width: 120px; padding: 12px 16px;">Kode SKU</th>
                                        <th style="padding: 12px 16px;">Nama Snack / Produk</th>
                                        <th class="cell-center" style="width: 130px; padding: 12px 16px;">Jumlah Turun</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, idx) in activeDelivery?.items || []" :key="item.item_id">
                                        <tr style="border-bottom: 1px solid var(--color-hairline);">
                                            <td class="cell-center text-ink-mute font-semibold" style="padding: 12px 16px;" x-text="idx + 1"></td>
                                            <td style="padding: 12px 16px;">
                                                <span class="badge badge-mono" style="font-size: 11px; padding: 2px 7px;" x-text="item.kode_sku"></span>
                                            </td>
                                            <td style="padding: 12px 16px;">
                                                <div style="font-weight: 700; color: var(--color-ink);" x-text="item.nama_item"></div>
                                                <template x-if="item.varian_rasa">
                                                    <div style="font-size: 11.5px; color: var(--color-ink-mute); margin-top: 2px;" x-text="'Varian: ' + item.varian_rasa"></div>
                                                </template>
                                            </td>
                                            <td class="cell-center font-black text-ink" style="padding: 12px 16px; color: #2563eb; font-family: var(--font-sans), sans-serif;" x-text="item.kuantitas_satuan_dasar + ' ' + (item.satuan_dasar || 'Pcs')"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                    </div>

                    <!-- TAB 3: PEMBAYARAN & TAGIHAN -->
                    <div x-show="activeTab === 'payment'" class="space-y-4">
                        
                        <div style="background: var(--color-canvas-soft); border: 1px solid var(--color-hairline); border-radius: 20px; padding: 22px 24px;" class="space-y-5">
                            
                            <div class="flex items-center justify-between gap-4 flex-wrap">
                                <div>
                                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--color-ink-mute); letter-spacing: 0.05em;">Metode Pembayaran</span>
                                    <div class="mt-1.5">
                                        <span class="badge font-bold uppercase text-xs px-3 py-1 bg-canvas border border-hairline" x-text="activeDelivery?.tipe_pembayaran ? activeDelivery.tipe_pembayaran.replace('_', ' ') : 'CASH'"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--color-ink-mute); letter-spacing: 0.05em;">Status Pembayaran</span>
                                    <div class="mt-1.5">
                                        <span class="badge" :class="activeDelivery?.status_pembayaran === 'lunas' ? 'badge-success' : 'badge-warning'" style="font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 3px 10px;" x-text="activeDelivery?.status_pembayaran === 'lunas' ? 'LUNAS' : 'TEMPO / BELUM LUNAS'"></span>
                                    </div>
                                </div>
                            </div>

                            <div style="height: 1px; background: var(--color-hairline);"></div>

                            <div class="space-y-2.5 text-sm">
                                <div class="flex items-center justify-between text-ink-secondary">
                                    <span>Total Bruto:</span>
                                    <span class="font-mono font-medium" x-text="'Rp ' + formatRupiah(activeDelivery?.total_bruto)"></span>
                                </div>
                                <div class="flex items-center justify-between text-ink-secondary">
                                    <span>Total Diskon / Potongan:</span>
                                    <span class="font-mono font-medium" x-text="'Rp ' + formatRupiah((activeDelivery?.total_bruto || 0) - (activeDelivery?.total_netto || 0))"></span>
                                </div>
                                <div class="flex items-center justify-between pt-3 border-t border-hairline font-bold text-base text-ink">
                                    <span>Grand Total Tagihan (Netto):</span>
                                    <span class="font-sans font-black text-blue-600 dark:text-blue-400 text-lg sm:text-xl" x-text="'Rp ' + formatRupiah(activeDelivery?.total_netto)"></span>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <!-- ================================================================= -->
                <!-- B. MODE 2: FORM SERAH TERIMA / SELESAI KIRIM (INLINE DI MODAL)     -->
                <!-- ================================================================= -->
                <div x-show="viewMode === 'complete_form'" class="space-y-6">
                    
                    <!-- Banner Info Hijau (Visual Serah Terima Selesai) -->
                    <div style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 1.5px solid #a7f3d0; border-radius: 18px; padding: 18px 22px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.06);" class="driver-complete-banner">
                        <div style="width: 44px; height: 44px; border-radius: 14px; background: #d1fae5; border: 1px solid #6ee7b7; color: #059669; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="check-circle" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div>
                            <div style="font-size: 16px; font-weight: 900; color: #065f46; line-height: 1.25;">Konfirmasi Serah Terima Pengiriman</div>
                            <div style="font-size: 13px; color: #047857; margin-top: 3px; font-weight: 500;">Silakan lengkapi nama penerima toko dan data serah terima muatan.</div>
                        </div>
                    </div>

                    <form id="completeDeliveryForm" action="<?= Router::url('/driver-deliveries/complete') ?>" method="POST" enctype="multipart/form-data" class="space-y-5 text-left"
                          data-action-text="Menyimpan serah terima...">
                        <input type="hidden" name="surat_jalan_id" :value="activeDelivery?.surat_jalan_id">

                        <!-- 1. Nama Penerima Toko (Wajib) -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-mute">
                                Nama Penerima di Toko <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama_penerima_toko" required placeholder="Contoh: Bu Hj. Siti (Pemilik Toko)" class="form-input font-medium" style="height: 46px; border-radius: 14px; font-size: 14px;">
                        </div>

                        <!-- 2. Pembayaran Tunai (Khusus Penjualan Tunai/Cash) -->
                        <template x-if="activeDelivery?.tipe_pembayaran === 'tunai' || activeDelivery?.tipe_pembayaran === 'cash'">
                            <div style="padding: 18px 20px; background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 18px;" class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="block font-bold text-xs uppercase tracking-wider text-emerald-800">
                                        💵 Uang Tunai Diterima (Rp)
                                    </label>
                                    <span class="text-xs font-bold text-emerald-700 font-sans" x-text="'Total Tagihan: Rp ' + formatRupiah(activeDelivery?.total_netto)"></span>
                                </div>
                                <input type="text" inputmode="numeric" name="nominal_tunai_diterima" :value="activeDelivery?.total_netto ? (window.formatRupiahNumber ? window.formatRupiahNumber(activeDelivery.total_netto) : activeDelivery.total_netto) : ''" class="form-input font-bold input-rupiah" style="height: 46px; border-radius: 14px; font-size: 16px; color: #047857; background: #ffffff; font-family: var(--font-sans), sans-serif;">
                                <div style="font-size: 12px; color: #15803d;">
                                    Masukkan nominal uang tunai yang diterima langsung dari pihak toko.
                                </div>
                            </div>
                        </template>

                        <!-- 3. Upload Foto Bukti Serah Terima (Kamera / Galeri HP) -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-mute">
                                Foto Bukti Serah Terima Toko
                            </label>
                            <input type="file" name="bukti_foto" accept="image/*" capture="environment" class="form-input" style="padding: 9px; border-radius: 14px; font-size: 13px;" @change="handleCompletePhotoChange($event)" x-ref="completePhotoInput">
                            
                            <!-- Thumbnail Preview & Clear Button Card -->
                            <template x-if="completePhotoPreview">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;margin-top:8px;">
                                    <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                                        <img :src="completePhotoPreview" alt="Preview Foto Serah Terima" style="width:48px;height:48px;object-fit:cover;border-radius:10px;border:1px solid var(--color-hairline);flex-shrink:0;">
                                        <div style="min-width:0;">
                                            <div style="font-size:13px;font-weight:700;color:var(--color-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Foto Siap Diunggah</div>
                                            <div style="font-size:11.5px;color:#059669;font-weight:600;display:flex;align-items:center;gap:4px;">
                                                <i data-lucide="check" style="width:13px;height:13px;"></i>
                                                <span>Terkompresi Otomatis</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" @click="clearCompletePhoto()" class="btn btn-ghost btn-sm" style="padding:6px 12px;border-radius:10px;color:#e11d48;background:rgba(225,29,72,0.08);border:1px solid rgba(225,29,72,0.2);display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;flex-shrink:0;" title="Hapus Foto">
                                        <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </template>

                            <div style="font-size: 12px; color: var(--color-ink-mute);">
                                Ambil foto serah terima barang di toko atau nota bertanda tangan.
                            </div>
                        </div>

                        <!-- 4. Catatan Driver Opsional -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-mute">
                                Catatan Tambahan Lapangan (Opsional)
                            </label>
                            <textarea name="catatan_driver" rows="3" placeholder="Catatan kondisi serah terima..." class="form-input font-medium" style="border-radius: 14px; font-size: 13.5px; padding: 12px;"></textarea>
                        </div>
                    </form>
                </div>

                <!-- ================================================================= -->
                <!-- C. MODE 3: FORM LAPOR GAGAL KIRIM (INLINE DI MODAL)               -->
                <!-- ================================================================= -->
                <div x-show="viewMode === 'fail_form'" class="space-y-6">
                    
                    <!-- Banner Info Merah (Visual Psikologi Peringatan / Kendala) -->
                    <div style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border: 1.5px solid #fda4af; border-radius: 18px; padding: 18px 22px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(225, 29, 72, 0.06);" class="driver-fail-banner">
                        <div style="width: 44px; height: 44px; border-radius: 14px; background: #fecdd3; border: 1px solid #f43f5e; color: #e11d48; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="alert-octagon" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div>
                            <div style="font-size: 16px; font-weight: 900; color: #9f1239; line-height: 1.25;">Lapor Kendala / Gagal Kirim</div>
                            <div style="font-size: 13px; color: #be123c; margin-top: 3px; font-weight: 500;">Silakan tentukan alasan kendala pengiriman di lokasi toko ini.</div>
                        </div>
                    </div>

                    <form id="failDeliveryForm" action="<?= Router::url('/driver-deliveries/fail') ?>" method="POST" enctype="multipart/form-data" class="space-y-5 text-left"
                          data-action-text="Melaporkan gagal kirim...">
                        <input type="hidden" name="surat_jalan_id" :value="activeDelivery?.surat_jalan_id">

                        <!-- Dropdown Alasan Gagal -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-mute">
                                Alasan Pengiriman Gagal <span class="text-danger">*</span>
                            </label>
                            <select name="alasan_gagal" required class="form-input font-medium" style="height: 46px; border-radius: 14px; font-size: 14px;">
                                <option value="Toko Tutup / Libur">Toko Tutup / Libur</option>
                                <option value="Pemilik / Penanggung Jawab Tidak Ada">Pemilik / Penanggung Jawab Tidak Ada</option>
                                <option value="Pesanan Ditolak / Dibatalkan Toko">Pesanan Ditolak / Dibatalkan Toko</option>
                                <option value="Kendala Akses Jalan / Armada Rusak">Kendala Akses Jalan / Armada Rusak</option>
                                <option value="Lainnya">Lainnya (Tulis di Catatan)</option>
                            </select>
                        </div>

                        <!-- Upload Foto Bukti Kendala Gagal Kirim -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-mute">
                                Foto Bukti Kendala (Kamera / Galeri HP)
                            </label>
                            <input type="file" name="foto_bukti_gagal" accept="image/*" capture="environment" class="form-input" style="padding: 9px; border-radius: 14px; font-size: 13px;" @change="handleFailPhotoChange($event)" x-ref="failPhotoInput">
                            
                            <!-- Thumbnail Preview & Clear Button Card -->
                            <template x-if="failPhotoPreview">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;margin-top:8px;">
                                    <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                                        <img :src="failPhotoPreview" alt="Preview Foto Kendala" style="width:48px;height:48px;object-fit:cover;border-radius:10px;border:1px solid var(--color-hairline);flex-shrink:0;">
                                        <div style="min-width:0;">
                                            <div style="font-size:13px;font-weight:700;color:var(--color-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Foto Kendala Dipilih</div>
                                            <div style="font-size:11.5px;color:#059669;font-weight:600;display:flex;align-items:center;gap:4px;">
                                                <i data-lucide="check" style="width:13px;height:13px;"></i>
                                                <span>Terkompresi Otomatis</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" @click="clearFailPhoto()" class="btn btn-ghost btn-sm" style="padding:6px 12px;border-radius:10px;color:#e11d48;background:rgba(225,29,72,0.08);border:1px solid rgba(225,29,72,0.2);display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;flex-shrink:0;" title="Hapus Foto">
                                        <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </template>

                            <div style="font-size: 12px; color: var(--color-ink-mute);">
                                Ambil foto kondisi toko (tutup / akses terhalang) sebagai bukti otentik lapangan.
                            </div>
                        </div>

                        <!-- Catatan Penjelasan -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-mute">
                                Penjelasan Kendala Lapangan
                            </label>
                            <textarea name="catatan_gagal" rows="3" placeholder="Jelaskan detail kondisi di lokasi toko..." class="form-input font-medium" style="border-radius: 14px; font-size: 13.5px; padding: 12px;"></textarea>
                        </div>
                    </form>
                </div>

            </div>

            <!-- 4. MODAL FOOTER AKSI OPERASIONAL TETAP (CLEAN, PROPORTIONAL & RESPONSIVE) -->
            <div class="modal-footer"
                 style="padding: 16px 24px; border-top: 1px solid var(--color-hairline); background: var(--color-canvas); flex-shrink: 0;">
                
                <!-- A. FOOTER MODE DETAIL (RINGKASAN & AKSI UTAMA RUTE) -->
                <template x-if="viewMode === 'detail'">
                    <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 w-full">
                        <!-- KIRI: Unduh Surat Jalan PDF -->
                        <div class="flex items-center">
                            <a :href="'<?= Router::url('/deliveries/pdf?id=') ?>' + encodeURIComponent(activeDelivery?.surat_jalan_id || '')"
                               target="_blank"
                               class="btn btn-secondary btn-sm w-full sm:w-auto justify-center"
                               style="border-radius: 12px; font-weight: 700; font-size: 13px; padding: 9px 16px; display: inline-flex; align-items: center; gap: 7px; color:#dc2626; border-color:#fca5a5; background:#fef2f2;">
                                <i data-lucide="file-text" style="width: 15px; height: 15px;"></i>
                                <span>Unduh Surat Jalan (PDF)</span>
                            </a>
                        </div>

                        <!-- KANAN: Tombol Aksi Operasional -->
                        <div class="flex items-center justify-end gap-2.5 flex-wrap sm:flex-nowrap">
                            <!-- Tombol Lihat Foto Bukti Selesai (Jika Selesai) -->
                            <template x-if="activeDelivery?.status_surat_jalan === 'selesai_diterima' && activeDelivery?.bukti_terima_foto">
                                <button type="button" 
                                        @click="openPhotoViewer(activeDelivery.bukti_terima_foto, 'Bukti Serah Terima - ' + (activeDelivery?.nama_toko || ''))"
                                        class="btn btn-secondary btn-sm flex-1 sm:flex-none justify-center"
                                        style="border-radius: 12px; font-weight: 700; font-size: 13px; padding: 9px 16px; color: #059669; border-color: #a7f3d0; background: #ecfdf5; display: inline-flex; align-items: center; gap: 6px;">
                                    <i data-lucide="image" style="width: 15px; height: 15px;"></i>
                                    <span>Lihat Foto Bukti</span>
                                </button>
                            </template>

                            <!-- Tombol Lihat Foto Bukti Gagal (Jika Gagal) -->
                            <template x-if="activeDelivery?.status_surat_jalan === 'gagal_kirim' && activeDelivery?.foto_bukti_gagal">
                                <button type="button" 
                                        @click="openPhotoViewer(activeDelivery.foto_bukti_gagal, 'Bukti Gagal Kirim - ' + (activeDelivery?.nama_toko || ''))"
                                        class="btn btn-secondary btn-sm flex-1 sm:flex-none justify-center"
                                        style="border-radius: 12px; font-weight: 700; font-size: 13px; padding: 9px 16px; color: #e11d48; border-color: #fecaca; background: #fff1f2; display: inline-flex; align-items: center; gap: 6px;">
                                    <i data-lucide="image" style="width: 15px; height: 15px;"></i>
                                    <span>Lihat Foto Gagal</span>
                                </button>
                            </template>

                            <!-- Form Mulai Kirim (Jika Masih Status Siap Berangkat) -->
                            <form action="<?= Router::url('/driver-deliveries/start') ?>" method="POST"
                                  style="display: contents;"
                                  :data-confirm="'Mulai perjalanan pengiriman ke ' + (activeDelivery?.nama_toko || '') + '?'"
                                  data-confirm-title="Mulai Pengiriman"
                                  data-confirm-type="info"
                                  data-confirm-btn="Ya, Mulai Berangkat"
                                  data-action-text="Memulai pengiriman...">
                                <input type="hidden" name="surat_jalan_id" :value="activeDelivery?.surat_jalan_id">
                                <template x-if="['menunggu_persetujuan', 'draf_n8n', 'siap_kirim', 'disetujui_owner'].includes(activeDelivery?.status_surat_jalan)">
                                    <button type="submit" class="btn btn-primary btn-sm flex-1 sm:flex-none justify-center"
                                            style="font-weight: 800; font-size: 13px; border-radius: 12px; padding: 9px 22px; display: inline-flex; align-items: center; gap: 7px; background: #2563eb; border-color: #2563eb; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);">
                                        <i data-lucide="send" style="width: 15px; height: 15px;"></i>
                                        <span>Mulai Kirim</span>
                                    </button>
                                </template>
                            </form>

                            <!-- Lapor Gagal (Jika Sedang Dikirim) -->
                            <template x-if="activeDelivery?.status_surat_jalan === 'sedang_dikirim'">
                                <button type="button" class="btn btn-secondary btn-sm flex-1 sm:flex-none justify-center"
                                        style="font-weight: 700; font-size: 13px; border-radius: 12px; padding: 9px 16px; color: #e11d48; border-color: #fecaca; background: #fff1f2; display: inline-flex; align-items: center; gap: 6px;"
                                        @click="viewMode = 'fail_form'">
                                    <i data-lucide="x-circle" style="width: 15px; height: 15px;"></i>
                                    <span>Lapor Gagal</span>
                                </button>
                            </template>

                            <!-- Selesai Kirim (Jika Sedang Dikirim) -->
                            <template x-if="activeDelivery?.status_surat_jalan === 'sedang_dikirim'">
                                <button type="button" class="btn btn-primary btn-sm flex-1 sm:flex-none justify-center"
                                        style="font-weight: 800; font-size: 13px; border-radius: 12px; padding: 9px 22px; background: #059669; border-color: #059669; display: inline-flex; align-items: center; gap: 7px; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);"
                                        @click="viewMode = 'complete_form'">
                                    <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
                                    <span>Selesai Kirim</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- B. FOOTER MODE COMPLETE FORM (KONFIRMASI SERAH TERIMA PENGIRIMAN) -->
                <template x-if="viewMode === 'complete_form'">
                    <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 w-full">
                        <!-- Kiri: Batal & Kembali ke Detail -->
                        <button type="button" @click="viewMode = 'detail'"
                                class="btn btn-secondary btn-sm flex-1 sm:flex-none justify-center"
                                style="border-radius: 12px; font-weight: 700; font-size: 13px; padding: 9px 20px; display: inline-flex; align-items: center; gap: 7px;">
                            <i data-lucide="arrow-left" style="width: 15px; height: 15px;"></i>
                            <span>Batal &amp; Kembali</span>
                        </button>

                        <!-- Kanan: Simpan & Selesai Kirim (Submit Form Serah Terima) -->
                        <button type="submit" form="completeDeliveryForm"
                                class="btn btn-primary btn-sm flex-1 sm:flex-none justify-center"
                                style="font-weight: 800; font-size: 13.5px; border-radius: 12px; padding: 10px 24px; background: #059669; border-color: #059669; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);">
                            <i data-lucide="check" style="width: 17px; height: 17px;"></i>
                            <span>Simpan &amp; Selesai Kirim</span>
                        </button>
                    </div>
                </template>

                <!-- C. FOOTER MODE FAIL FORM (LAPOR KENDALA / GAGAL KIRIM) -->
                <template x-if="viewMode === 'fail_form'">
                    <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 w-full">
                        <!-- Kiri: Batal & Kembali ke Detail -->
                        <button type="button" @click="viewMode = 'detail'"
                                class="btn btn-secondary btn-sm flex-1 sm:flex-none justify-center"
                                style="border-radius: 12px; font-weight: 700; font-size: 13px; padding: 9px 20px; display: inline-flex; align-items: center; gap: 7px;">
                            <i data-lucide="arrow-left" style="width: 15px; height: 15px;"></i>
                            <span>Batal &amp; Kembali</span>
                        </button>

                        <!-- Kanan: Konfirmasi Gagal Kirim (Submit Form Lapor Gagal) -->
                        <button type="submit" form="failDeliveryForm"
                                class="btn btn-driver-fail-confirm btn-sm flex-1 sm:flex-none justify-center">
                            <i data-lucide="alert-triangle" style="width: 17px; height: 17px;"></i>
                            <span>Konfirmasi Gagal Kirim</span>
                        </button>
                    </div>
                </template>

            </div>

        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL RESPONSIVE PREVIEW FOTO BUKTI PENGIRIMAN (TOUCH PINCH & PAN VIEWER) -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showPhotoModal" 
         x-cloak 
         class="receipt-backdrop" 
         @keydown.window="handleViewerKeydown($event)">
        
        <div class="receipt-container" @click.stop>
            <!-- Header Modal -->
            <div class="receipt-header" style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid var(--color-hairline);background:var(--color-surface, #ffffff);z-index:10;">
                <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                    <div style="width:34px;height:34px;border-radius:10px;background:rgba(59,130,246,0.12);display:flex;align-items:center;justify-content:center;color:#3b82f6;flex-shrink:0;">
                        <i data-lucide="image" style="width:18px;height:18px;"></i>
                    </div>
                    <div style="min-width:0;">
                        <h3 style="font-size:14px;font-weight:700;color:var(--color-ink-primary);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="photoModalTitle">Foto Bukti Pengiriman</h3>
                        <div style="font-size:11px;color:var(--color-ink-mute);font-family:monospace;" x-text="photoModalSubtitle"></div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                    <button type="button" @click="closePhotoViewer()" class="btn btn-ghost btn-sm" style="padding:6px;border-radius:8px;" title="Tutup">
                        <i data-lucide="x" style="width:20px;height:20px;"></i>
                    </button>
                </div>
            </div>

            <!-- Viewport Area Foto Gambar (Interactive Pinch & Pan Viewport) -->
            <div class="receipt-viewport" 
                 x-ref="photoViewport"
                 @wheel.prevent="handleWheel($event)"
                 @mousedown="handleMouseDown($event)"
                 @touchstart="handleTouchStart($event)"
                 @touchmove.prevent="handleTouchMove($event)"
                 @touchend="handleTouchEnd($event)"
                 @touchcancel="handleTouchEnd($event)"
                 @dblclick="toggleDoubleTap($event.clientX, $event.clientY)">

                <!-- State Error jika file fisik tidak ditemukan / dibersihkan -->
                <div x-show="photoLoadError" style="margin:auto;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;max-width:440px;width:100%;padding:32px 16px;z-index:5;">
                    <div style="width:56px;height:56px;border-radius:16px;background:rgba(239,68,68,0.15);display:flex;align-items:center;justify-content:center;color:#ef4444;margin:0 auto 16px auto;box-shadow:0 4px 12px rgba(239,68,68,0.12);">
                        <i data-lucide="image-off" style="width:28px;height:28px;display:block;"></i>
                    </div>
                    <div style="font-size:15px;font-weight:700;color:#f8fafc;margin-bottom:6px;text-align:center;width:100%;">Foto Bukti Tidak Ditemukan</div>
                    <div style="font-size:12.5px;color:#94a3b8;max-width:380px;line-height:1.6;margin:0 auto;text-align:center;width:100%;">
                        Berkas foto bukti ini tidak ditemukan di direktori server. Kemungkinan merupakan berkas lama yang telah dibersihkan atau belum berhasil terunggah.
                    </div>
                </div>

                <!-- Gambar Bukti Utama (Hardware-Accelerated CSS Transform) -->
                <template x-if="photoModalUrl">
                    <img :src="photoModalUrl" 
                         alt="Foto Bukti Pengiriman" 
                         x-show="!photoLoadError"
                         @load="onPhotoImageLoaded()"
                         @error="photoLoadError = true; $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });"
                         draggable="false"
                         :style="{
                             display: photoLoadError ? 'none' : 'block',
                             maxWidth: '100%',
                             maxHeight: '100%',
                             objectFit: 'contain',
                             transform: 'translate3d(' + zoomPanX + 'px, ' + zoomPanY + 'px, 0) scale(' + zoomScale + ') rotate(' + zoomRotate + 'deg)',
                             transformOrigin: 'center center',
                             transition: isDragging ? 'none' : 'transform 0.18s cubic-bezier(0.2, 0, 0, 1)',
                             cursor: zoomScale > 1.05 ? (isDragging ? 'grabbing' : 'grab') : 'zoom-in',
                             userSelect: 'none',
                             webkitUserDrag: 'none'
                         }">
                </template>

                <!-- Floating Glassmorphism Controls (Bar Alat Sentuh Mengambang) -->
                <div x-show="!photoLoadError" class="receipt-floating-toolbar">
                    <!-- Zoom Out -->
                    <button type="button" @click="zoomStep(-0.3)" class="receipt-tool-btn" title="Perkecil Zoom (-)" :disabled="zoomScale <= 0.6">
                        <i data-lucide="minus" style="width:16px;height:16px;"></i>
                    </button>

                    <!-- Persentase & Reset -->
                    <button type="button" @click="resetZoom()" class="receipt-tool-badge" title="Klik untuk Reset Tampilan Fit">
                        <span x-text="Math.round(zoomScale * 100) + '%'"></span>
                    </button>

                    <!-- Zoom In -->
                    <button type="button" @click="zoomStep(0.3)" class="receipt-tool-btn" title="Perbesar Zoom (+)" :disabled="zoomScale >= 5.0">
                        <i data-lucide="plus" style="width:16px;height:16px;"></i>
                    </button>

                    <div class="receipt-tool-divider"></div>

                    <!-- Rotate 90° Clockwise -->
                    <button type="button" @click="rotateClockwise()" class="receipt-tool-btn" title="Putar Posisi 90°">
                        <i data-lucide="rotate-cw" style="width:16px;height:16px;"></i>
                    </button>

                    <!-- Fit / Reset -->
                    <button type="button" @click="resetZoom()" class="receipt-tool-btn" title="Reset Ukuran Normal (Fit Layar)">
                        <i data-lucide="maximize-2" style="width:15px;height:15px;"></i>
                    </button>
                </div>
            </div>

            <!-- Footer Modal (Petunjuk Gestur - Cukup petunjuk, tanpa tombol tutup redundant) -->
            <div class="receipt-footer" style="display:flex;align-items:center;justify-content:center;padding:10px 18px;border-top:1px solid var(--color-hairline);background:var(--color-canvas-soft);font-size:11.5px;color:var(--color-ink-mute);z-index:10;text-align:center;">
                <div style="display:flex;align-items:center;gap:6px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <i data-lucide="info" style="width:14px;height:14px;flex-shrink:0;"></i>
                    <span class="hidden sm:inline">Geser untuk memindahkan foto • Scroll mouse / Cubit 2 jari untuk zoom • Ketuk 2x untuk zoom cepat</span>
                    <span class="inline sm:hidden">Cubit 2 jari untuk zoom • Geser foto • Ketuk 2x zoom</span>
                </div>
            </div>
        </div>
    </div>
    </template>

</div>

<script>
function driverDeliveryApp() {
    return {
        showDetailModal: false,
        activeDelivery: null,
        activeTab: 'info', // 'info', 'items', 'payment'
        viewMode: 'detail', // 'detail', 'complete_form', 'fail_form'

        // Photo viewer states
        showPhotoModal: false,
        photoModalUrl: '',
        photoModalTitle: '',
        photoModalSubtitle: '',
        photoLoadError: false,
        zoomScale: 1.0,
        zoomPanX: 0,
        zoomPanY: 0,
        zoomRotate: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,
        isPinching: false,
        pinchStartDist: 0,
        pinchStartScale: 1.0,
        lastTapTime: 0,

        // Upload previews
        completePhotoFile: null,
        completePhotoPreview: null,
        failPhotoFile: null,
        failPhotoPreview: null,

        init() {
            this.$watch('viewMode', () => {
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
            this.$watch('showPhotoModal', (val) => {
                if (val) {
                    document.body.style.overflow = 'hidden';
                    document.documentElement.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                    document.documentElement.style.overflow = '';
                }
            });
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        openDetailModal(deliv) {
            this.activeDelivery = deliv;
            this.activeTab = 'info';
            this.viewMode = 'detail';
            this.clearCompletePhoto();
            this.clearFailPhoto();
            this.showDetailModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        closeDetailModal() {
            this.showDetailModal = false;
            this.viewMode = 'detail';
        },

        // Photo viewer methods
        openPhotoViewer(url, title, subtitle) {
            if (!url) return;
            this.resetZoom();
            this.photoLoadError = false;
            this.photoModalUrl = '<?= Router::url('/') ?>' + url.replace(/^\//, '');
            this.photoModalTitle = title || 'Foto Bukti Pengiriman';
            this.photoModalSubtitle = subtitle || '';
            this.showPhotoModal = true;
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        closePhotoViewer() {
            this.showPhotoModal = false;
            this.photoModalUrl = '';
            this.resetZoom();
            this.photoLoadError = false;
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        },

        resetZoom() {
            this.zoomScale = 1.0;
            this.zoomPanX = 0;
            this.zoomPanY = 0;
            this.zoomRotate = 0;
            this.isDragging = false;
            this.isPinching = false;
        },

        zoomStep(step) {
            const next = Math.min(5.0, Math.max(0.6, Number((this.zoomScale + step).toFixed(2))));
            this.zoomScale = next;
            if (next <= 1.0) {
                this.zoomPanX = 0;
                this.zoomPanY = 0;
            } else {
                this.clampPan();
            }
        },

        rotateClockwise() {
            this.zoomRotate = (this.zoomRotate + 90) % 360;
        },

        clampPan() {
            if (this.zoomScale <= 1.0) {
                this.zoomPanX = 0;
                this.zoomPanY = 0;
                return;
            }
            const bound = Math.max(100, 480 * (this.zoomScale - 0.7));
            this.zoomPanX = Math.max(-bound, Math.min(bound, this.zoomPanX));
            this.zoomPanY = Math.max(-bound, Math.min(bound, this.zoomPanY));
        },

        onPhotoImageLoaded() {
            this.photoLoadError = false;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        toggleDoubleTap(clientX, clientY) {
            if (this.photoLoadError) return;
            if (this.zoomScale > 1.2) {
                this.resetZoom();
            } else {
                this.zoomScale = 2.4;
                this.zoomPanX = 0;
                this.zoomPanY = 0;
            }
        },

        handleMouseDown(e) {
            if (e.button !== 0 || this.photoLoadError) return;
            this.isDragging = true;
            this.dragStartX = e.clientX - this.zoomPanX;
            this.dragStartY = e.clientY - this.zoomPanY;

            const onMouseMove = (ev) => {
                if (!this.isDragging) return;
                this.zoomPanX = ev.clientX - this.dragStartX;
                this.zoomPanY = ev.clientY - this.dragStartY;
                this.clampPan();
            };

            const onMouseUp = () => {
                this.isDragging = false;
                this.clampPan();
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
            };

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
        },

        handleTouchStart(e) {
            if (this.photoLoadError) return;
            if (e.touches.length === 2) {
                this.isPinching = true;
                this.isDragging = false;
                this.pinchStartDist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                this.pinchStartScale = this.zoomScale;
            } else if (e.touches.length === 1) {
                const now = Date.now();
                if (now - this.lastTapTime < 300) {
                    this.toggleDoubleTap(e.touches[0].clientX, e.touches[0].clientY);
                    this.lastTapTime = 0;
                    return;
                }
                this.lastTapTime = now;

                this.isDragging = true;
                this.dragStartX = e.touches[0].clientX - this.zoomPanX;
                this.dragStartY = e.touches[0].clientY - this.zoomPanY;
            }
        },

        handleTouchMove(e) {
            if (this.photoLoadError) return;
            if (this.isPinching && e.touches.length === 2) {
                const dist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                if (this.pinchStartDist > 0) {
                    const factor = dist / this.pinchStartDist;
                    this.zoomScale = Math.min(5.0, Math.max(0.6, Number((this.pinchStartScale * factor).toFixed(2))));
                }
            } else if (this.isDragging && e.touches.length === 1) {
                this.zoomPanX = e.touches[0].clientX - this.dragStartX;
                this.zoomPanY = e.touches[0].clientY - this.dragStartY;
                this.clampPan();
            }
        },

        handleTouchEnd(e) {
            if (e.touches.length < 2) {
                this.isPinching = false;
            }
            if (e.touches.length === 0) {
                this.isDragging = false;
                this.clampPan();
            }
        },

        handleWheel(e) {
            if (this.photoLoadError) return;
            const delta = e.deltaY < 0 ? 0.25 : -0.25;
            this.zoomStep(delta);
        },

        handleViewerKeydown(e) {
            if (!this.showPhotoModal || this.photoLoadError) return;
            if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                this.zoomStep(0.3);
            } else if (e.key === '-' || e.key === '_') {
                e.preventDefault();
                this.zoomStep(-0.3);
            } else if (e.key === '0') {
                e.preventDefault();
                this.resetZoom();
            } else if (e.key === 'r' || e.key === 'R') {
                e.preventDefault();
                this.rotateClockwise();
            }
        },

        // Client-side canvas image compression helper
        compressImage(file, callback) {
            if (!file) return;
            if (file.size > 15 * 1024 * 1024) {
                if (typeof toast !== 'undefined') toast.warning('Ukuran file foto maksimal 15MB!');
                return;
            }
            if (!file.type.match(/^image\//i)) {
                if (typeof toast !== 'undefined') toast.warning('Format file harus berupa gambar (JPG, PNG, atau WebP)!');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const maxDim = 1600;
                    let w = img.width;
                    let h = img.height;
                    if (w > maxDim || h > maxDim) {
                        if (w >= h) {
                            h = Math.round((h / w) * maxDim);
                            w = maxDim;
                        } else {
                            w = Math.round((w / h) * maxDim);
                            h = maxDim;
                        }
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, w, h);
                    ctx.drawImage(img, 0, 0, w, h);

                    canvas.toBlob((blob) => {
                        if (blob) {
                            const newFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", { type: 'image/jpeg' });
                            callback(newFile, canvas.toDataURL('image/jpeg', 0.82));
                        } else {
                            callback(file, e.target.result);
                        }
                    }, 'image/jpeg', 0.82);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        handleCompletePhotoChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.compressImage(file, (compressedFile, previewDataUrl) => {
                this.completePhotoFile = compressedFile;
                this.completePhotoPreview = previewDataUrl;
                try {
                    const dt = new DataTransfer();
                    dt.items.add(compressedFile);
                    event.target.files = dt.files;
                } catch (e) {}
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
        },

        clearCompletePhoto() {
            this.completePhotoFile = null;
            this.completePhotoPreview = null;
            if (this.$refs.completePhotoInput) {
                this.$refs.completePhotoInput.value = '';
            }
        },

        handleFailPhotoChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.compressImage(file, (compressedFile, previewDataUrl) => {
                this.failPhotoFile = compressedFile;
                this.failPhotoPreview = previewDataUrl;
                try {
                    const dt = new DataTransfer();
                    dt.items.add(compressedFile);
                    event.target.files = dt.files;
                } catch (e) {}
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
        },

        clearFailPhoto() {
            this.failPhotoFile = null;
            this.failPhotoPreview = null;
            if (this.$refs.failPhotoInput) {
                this.$refs.failPhotoInput.value = '';
            }
        },

        cleanWa(raw) {
            if (!raw) return '';
            let cleaned = raw.replace(/[^0-9]/g, '');
            if (cleaned.startsWith('0')) {
                cleaned = '62' + cleaned.substring(1);
            }
            return cleaned;
        },

        formatRupiah(val) {
            return new Intl.NumberFormat('id-ID').format(val || 0);
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '-';
            try {
                const d = new Date(dateStr);
                return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + ' WIB';
            } catch (e) {
                return dateStr;
            }
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
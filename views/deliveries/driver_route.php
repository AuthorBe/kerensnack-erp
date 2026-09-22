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

/* Shopping Card Distinct Accents & Modern Border */
.driver-compact-card.is-shopping {
    border-left-width: 4px;
    border-color: var(--color-hairline);
    background: var(--color-surface);
}
.driver-compact-card.is-shopping.is-shopping-pending {
    border-color: var(--color-hairline);
    border-left-color: #f59e0b;
    background: linear-gradient(90deg, rgba(245, 158, 11, 0.05) 0%, var(--color-surface) 120px);
}
.dark .driver-compact-card.is-shopping.is-shopping-pending {
    border-color: var(--color-hairline);
    border-left-color: #f59e0b;
    background: linear-gradient(90deg, rgba(245, 158, 11, 0.08) 0%, var(--color-surface) 120px);
}
.driver-compact-card.is-shopping.is-shopping-picked {
    border-color: var(--color-hairline);
    border-left-color: #3b82f6;
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.05) 0%, var(--color-surface) 120px);
}
.dark .driver-compact-card.is-shopping.is-shopping-picked {
    border-color: var(--color-hairline);
    border-left-color: #3b82f6;
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.08) 0%, var(--color-surface) 120px);
}
.driver-compact-card.is-shopping.is-shopping-received {
    border-color: var(--color-hairline);
    border-left-color: #10b981;
    background: linear-gradient(90deg, rgba(16, 185, 129, 0.05) 0%, var(--color-surface) 120px);
}
.dark .driver-compact-card.is-shopping.is-shopping-received {
    border-color: var(--color-hairline);
    border-left-color: #10b981;
    background: linear-gradient(90deg, rgba(16, 185, 129, 0.08) 0%, var(--color-surface) 120px);
}
.driver-compact-card.is-shopping.is-failed {
    border-color: var(--color-hairline);
    border-left-color: #ef4444;
    background: linear-gradient(90deg, rgba(239, 68, 68, 0.05) 0%, var(--color-surface) 120px);
}
.dark .driver-compact-card.is-shopping.is-failed {
    border-color: var(--color-hairline);
    border-left-color: #ef4444;
    background: linear-gradient(90deg, rgba(239, 68, 68, 0.08) 0%, var(--color-surface) 120px);
}
.driver-compact-card.is-shopping.is-shopping-overdue {
    border-color: #fca5a5;
    border-left-color: #ef4444;
    background: linear-gradient(90deg, rgba(239, 68, 68, 0.07) 0%, var(--color-surface) 120px);
}
.dark .driver-compact-card.is-shopping.is-shopping-overdue {
    border-color: rgba(239, 68, 68, 0.35);
    border-left-color: #ef4444;
    background: linear-gradient(90deg, rgba(239, 68, 68, 0.12) 0%, var(--color-surface) 120px);
}
.driver-compact-card.is-shopping:hover {
    border-color: #cbd5e1;
    border-left-color: #d97706;
    box-shadow: 0 10px 25px -4px rgba(245, 158, 11, 0.10), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
    transform: translateY(-2px);
}
.driver-compact-card.is-shopping.is-shopping-overdue:hover {
    border-color: #f87171;
    border-left-color: #dc2626;
    box-shadow: 0 10px 25px -4px rgba(239, 68, 68, 0.15), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
}
.dark .driver-compact-card.is-shopping:hover {
    border-color: #4b5563;
    border-left-color: #f59e0b;
    box-shadow: 0 10px 25px -4px rgba(0, 0, 0, 0.35);
}

/* Shopping Stop Number Badge (B-1, B-2, dst) */
.shopping-badge-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 28px;
    padding: 0 8px;
    border-radius: 9px;
    background: #f59e0b;
    color: #ffffff !important;
    font-weight: 900;
    font-size: 12px;
    font-family: var(--font-sans), sans-serif;
    letter-spacing: -0.02em;
    flex-shrink: 0;
    user-select: none;
    box-shadow: 0 1px 3px rgba(245, 158, 11, 0.35);
}
.shopping-badge-number.is-overdue {
    background: #e11d48;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(225, 29, 72, 0.35);
}
.dark .shopping-badge-number {
    background: #d97706;
    color: #ffffff !important;
}
.dark .shopping-badge-number.is-overdue {
    background: #e11d48;
    color: #ffffff !important;
}

/* Status Pills for Shopping Tasks */
.shopping-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 800;
    border-radius: 9999px;
    padding: 4px 12px;
    letter-spacing: -0.01em;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    white-space: nowrap;
}
.shopping-status-pill.status-pending {
    background: #fffbeb;
    color: #92400e;
    border: 1px solid #fde68a;
}
.dark .shopping-status-pill.status-pending {
    background: rgba(180, 83, 9, 0.22);
    color: #fde68a;
    border-color: rgba(245, 158, 11, 0.35);
}
.shopping-status-pill.status-picked {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}
.dark .shopping-status-pill.status-picked {
    background: rgba(30, 58, 138, 0.22);
    color: #93c5fd;
    border-color: rgba(59, 130, 246, 0.35);
}
.shopping-status-pill.status-received {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
.dark .shopping-status-pill.status-received {
    background: rgba(6, 78, 59, 0.22);
    color: #6ee7b7;
    border-color: rgba(16, 185, 129, 0.35);
}
.shopping-status-pill.status-failed {
    background: #fef2f2;
    color: #9f1239;
    border: 1px solid #fecaca;
}
.dark .shopping-status-pill.status-failed {
    background: rgba(127, 29, 29, 0.22);
    color: #fca5a5;
    border-color: rgba(239, 68, 68, 0.35);
}

/* Detail Belanja Action Button */
.btn-detail-belanja {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 11px;
    font-size: 12.5px;
    font-weight: 700;
    color: #92400e;
    background: #fffbeb;
    border: 1px solid #fde68a;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(245, 158, 11, 0.06);
    white-space: nowrap;
}
.btn-detail-belanja:hover {
    background: #fef3c7;
    border-color: #f59e0b;
    color: #78350f;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.16);
}
.btn-detail-belanja .btn-chevron {
    opacity: 0.5;
    transition: transform 0.15s ease, opacity 0.15s ease;
}
.btn-detail-belanja:hover .btn-chevron {
    opacity: 1;
    transform: translateX(2px);
}
.dark .btn-detail-belanja {
    color: #fde68a;
    background: rgba(180, 83, 9, 0.2);
    border-color: rgba(245, 158, 11, 0.35);
}
.dark .btn-detail-belanja:hover {
    background: rgba(180, 83, 9, 0.35);
    border-color: #f59e0b;
    color: #fff;
}

/* Photo Action Button */
.btn-shopping-photo {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 11px;
    font-size: 12px;
    font-weight: 700;
    transition: all 0.15s ease;
    cursor: pointer;
    white-space: nowrap;
}
.btn-shopping-photo.is-nota {
    color: #047857;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
}
.btn-shopping-photo.is-nota:hover {
    background: #d1fae5;
    border-color: #6ee7b7;
    transform: translateY(-1px);
}
.dark .btn-shopping-photo.is-nota {
    color: #6ee7b7;
    background: rgba(6, 78, 59, 0.2);
    border-color: rgba(16, 185, 129, 0.3);
}
.btn-shopping-photo.is-kendala {
    color: #b91c1c;
    background: #fef2f2;
    border: 1px solid #fecaca;
}
.btn-shopping-photo.is-kendala:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    transform: translateY(-1px);
}
.dark .btn-shopping-photo.is-kendala {
    color: #fca5a5;
    background: rgba(127, 29, 29, 0.2);
    border-color: rgba(239, 68, 68, 0.3);
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

/* Header Date Filter Segmented Radio Controls */
.date-segmented-group {
    display: inline-flex;
    align-items: center;
    background: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    border-radius: 12px;
    padding: 3px;
    height: 42px;
    box-sizing: border-box;
    gap: 3px;
}
.date-segmented-btn {
    border: none !important;
    outline: none !important;
    background: transparent;
    color: var(--color-ink-mute);
    font-size: 13px;
    font-weight: 700;
    padding: 0 16px;
    height: 100%;
    border-radius: 9px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    user-select: none;
    -webkit-appearance: none;
    appearance: none;
    box-shadow: none;
}
.date-segmented-btn:hover:not(.is-active) {
    color: var(--color-ink);
    background: rgba(0, 0, 0, 0.04);
}
.dark .date-segmented-btn:hover:not(.is-active) {
    background: rgba(255, 255, 255, 0.06);
}
.date-segmented-btn.is-active {
    background: var(--color-surface) !important;
    color: #2563eb !important;
    font-weight: 800;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.04) !important;
}
.dark .date-segmented-btn.is-active {
    background: var(--color-surface) !important;
    color: #60a5fa !important;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.35) !important;
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
    <!-- 1. PAGE HEADER & FILTER TANGGAL / DRIVER (LOCKED FOR DRIVER / PENGANTAR)  -->
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
        <form id="headerFilterForm" method="GET" action="<?= Router::url('/driver-deliveries') ?>" class="flex items-center gap-2.5 sm:gap-3 flex-wrap">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter ?? 'semua') ?>">
            
            <?php 
            $todayDate = date('Y-m-d');
            $tomorrowDate = date('Y-m-d', strtotime('+1 day'));
            $isToday = ($selectedDate === $todayDate);
            $isTomorrow = ($selectedDate === $tomorrowDate);
            ?>

            <!-- Segmented Radio Button Control (Hari Ini / Besok) -->
            <div class="date-segmented-group" role="radiogroup" aria-label="Pilihan Jadwal Rute Driver">
                <button type="button" 
                        role="radio"
                        aria-checked="<?= $isToday ? 'true' : 'false' ?>"
                        onclick="document.getElementById('headerDateInput').value='<?= $todayDate ?>'; document.getElementById('headerFilterForm').submit();" 
                        class="date-segmented-btn <?= $isToday ? 'is-active' : '' ?>">
                    <span>Hari Ini</span>
                </button>
                <button type="button" 
                        role="radio"
                        aria-checked="<?= $isTomorrow ? 'true' : 'false' ?>"
                        onclick="document.getElementById('headerDateInput').value='<?= $tomorrowDate ?>'; document.getElementById('headerFilterForm').submit();" 
                        class="date-segmented-btn <?= $isTomorrow ? 'is-active' : '' ?>">
                    <span>Besok</span>
                </button>
            </div>

            <!-- Kalender Datepicker Standar (Tinggi 42px & Radius 12px Selaras Dropdown) -->
            <input type="date" id="headerDateInput" name="date" value="<?= htmlspecialchars($selectedDate ?? date('Y-m-d')) ?>" 
                   class="form-input font-bold" style="height: 42px; font-size: 13px; border-radius: 12px; width: 152px;"
                   onchange="this.form.submit()"
                   title="Pilih Tanggal Pengiriman Kalender">

            <?php if (!empty($isRestricted)): ?>
                <!-- TERKUNCI UNTUK ROLE SALES & DRIVER -->
                <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl bg-canvas-soft border border-hairline text-xs font-semibold text-ink-secondary shadow-xs" style="height: 42px;" title="Armada terkunci pada akun Anda">
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
        <div class="card" style="border-radius: 20px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: <?= !empty($shoppingTasks) ? '40px 20px' : '64px 20px' ?>;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--color-canvas-soft); display: flex; align-items: center; justify-content: center; margin-bottom: 12px; color: var(--color-ink-mute);">
                <i data-lucide="truck" style="width: 30px; height: 30px; opacity: 0.6;"></i>
            </div>
            <div style="font-weight: 800; font-size: 16px; color: var(--color-ink); text-align: center;">Tidak Ada Antaran Toko Pelanggan</div>
            <p style="font-size: 13px; margin-top: 4px; color: var(--color-ink-mute); text-align: center; max-width: 440px; line-height: 1.5;">
                Tidak ada rute antaran pesanan toko untuk tanggal atau filter armada yang dipilih.
            </p>
            <?php if (!empty($shoppingTasks)): ?>
            <div style="font-size:12px;color:#b45309;background:#fef3c7;border:1px solid #fde68a;padding:8px 14px;border-radius:10px;margin-top:14px;font-weight:700;display:inline-flex;align-items:center;gap:6px;">
                <i data-lucide="shopping-cart" style="width:15px;height:15px;"></i>
                <span>Terdapat <?= count($shoppingTasks) ?> Tugas Belanja Bahan Vendor yang harus dikerjakan di bawah ini:</span>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <?php foreach ($deliveries as $idx => $deliv): 
            $statusSj = $deliv['status_surat_jalan'];
            $isInTransit = ($statusSj === 'sedang_dikirim');
            $isCompleted = ($statusSj === 'selesai_diterima');
            $isFailed = ($statusSj === 'gagal_kirim');
            $isPending = ($statusSj === 'siap_kirim');

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

                    <?php 
                    $tglKirimDeliv = $deliv['tanggal_surat_jalan'] ?: $deliv['waktu_terbit_sj'];
                    if (!empty($tglKirimDeliv)):
                        $isTomorrowDeliv = (date('Y-m-d', strtotime($tglKirimDeliv)) === date('Y-m-d', strtotime('+1 day')));
                        $isTodayDeliv = (date('Y-m-d', strtotime($tglKirimDeliv)) === date('Y-m-d'));
                    ?>
                        <span class="badge text-[11px] inline-flex items-center gap-1 shrink-0 font-bold" 
                              style="<?= $isTomorrowDeliv ? 'background:rgba(245,158,11,0.1);color:#d97706;border:1px solid rgba(245,158,11,0.3);' : 'background:rgba(37,99,235,0.08);color:#2563eb;border:1px solid rgba(37,99,235,0.2);' ?>" 
                              title="Tanggal Rencana Pengiriman: <?= date('d/m/Y', strtotime($tglKirimDeliv)) ?>">
                            <i data-lucide="calendar" style="width: 11px; height: 11px;"></i>
                            <span><?= $isTodayDeliv ? 'Hari Ini (' . date('d/m', strtotime($tglKirimDeliv)) . ')' : ($isTomorrowDeliv ? 'Besok (' . date('d/m', strtotime($tglKirimDeliv)) . ')' : date('d/m/Y', strtotime($tglKirimDeliv))) ?></span>
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
    <!-- 4B. TUGAS BELANJA & PICKUP BAHAN GUDANG (PURCHASE ORDER DRIVER)           -->
    <!-- ========================================================================= -->
    <?php if (!empty($shoppingTasks)): 
        $overdueCount = 0;
        $todayStr = date('Y-m-d');
        foreach ($shoppingTasks as $stk) {
            $tDate = $stk['tanggal_jadwal_belanja'] ?: $stk['tanggal_pembelian'];
            if ($stk['status_penerimaan'] === 'ditugaskan_driver' && !empty($tDate) && $tDate < $todayStr) {
                $overdueCount++;
            }
        }
    ?>
    <div class="mt-8 space-y-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold shadow-xs shrink-0" style="background: rgba(245,158,11,0.12); color: #d97706; border: 1px solid rgba(245,158,11,0.25);">
                    <i data-lucide="shopping-cart" style="width: 18px; height: 18px;"></i>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-extrabold text-ink flex items-center gap-2 flex-wrap">
                        <span>Tugas Belanja &amp; Pickup Bahan Gudang</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-amber-100 text-amber-800 border border-amber-300/80 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800 shadow-xs">
                            <?= count($shoppingTasks) ?> PO
                        </span>
                        <?php if ($overdueCount > 0): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-700 border border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800 shadow-xs animate-pulse" title="<?= $overdueCount ?> tugas belanja tertunda dari hari sebelumnya">
                                <i data-lucide="alert-triangle" style="width: 12px; height: 12px;"></i>
                                <span><?= $overdueCount ?> Nunggak</span>
                            </span>
                        <?php endif; ?>
                    </h2>
                    <p class="text-xs text-ink-mute">
                        Barang yang harus dibelanjakan atau diambil dari vendor pemasok<?= $overdueCount > 0 ? " <span class=\"text-rose-600 dark:text-rose-400 font-semibold\">(termasuk {$overdueCount} tugas tertunda dari hari sebelumnya)</span>" : "" ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <?php foreach ($shoppingTasks as $sIdx => $task): 
                $statusPenerimaan = $task['status_penerimaan'];
                $isTaskPicked = ($statusPenerimaan === 'sudah_diambil');
                $isTaskReceived = ($statusPenerimaan === 'diterima');
                $isTaskFailed = ($statusPenerimaan === 'kendala_batal');
                $isTaskPending = ($statusPenerimaan === 'ditugaskan_driver');

                $taskDate = $task['tanggal_jadwal_belanja'] ?: $task['tanggal_pembelian'];
                $diffDays = 0;
                $isOverdue = false;
                $isToday = false;
                $isTomorrow = false;

                if (!empty($taskDate)) {
                    $taskTs = strtotime($taskDate);
                    $todayTs = strtotime($todayStr);
                    $diffDays = (int)(($todayTs - $taskTs) / 86400);
                    $isOverdue = ($isTaskPending && $diffDays > 0);
                    $isToday = ($diffDays === 0);
                    $isTomorrow = ($diffDays === -1);
                }

                $shopCardClass = $isTaskPending ? 'is-shopping-pending' : ($isTaskPicked ? 'is-shopping-picked' : ($isTaskReceived ? 'is-shopping-received' : ($isTaskFailed ? 'is-failed' : '')));
                if ($isOverdue) {
                    $shopCardClass .= ' is-shopping-overdue';
                }
            ?>
            <!-- CARD MODERN 2-BARIS TUGAS BELANJA -->
            <div class="driver-compact-card is-shopping <?= $shopCardClass ?>" @click="openShoppingDetailModal(<?= htmlspecialchars(json_encode($task)) ?>)">
                
                <!-- BARIS 1: NOMOR STOP, NAMA VENDOR, NOMOR PO & STATUS -->
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1 flex-wrap sm:flex-nowrap">
                        <span class="shopping-badge-number <?= $isOverdue ? 'is-overdue' : '' ?>" style="background: <?= $isOverdue ? '#e11d48' : '#f59e0b' ?>; color: #ffffff !important;">
                            B-<?= $sIdx + 1 ?>
                        </span>
                        <span class="font-extrabold text-base sm:text-lg text-ink truncate tracking-tight">
                            <?= htmlspecialchars($task['nama_pemasok']) ?>
                        </span>
                        <span class="badge badge-mono text-[11px] px-2.5 py-0.5 shrink-0 hidden sm:inline-flex bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-semibold">
                            <i data-lucide="receipt" style="width: 11px; height: 11px; margin-right: 4px; opacity: 0.65;"></i>
                            <?= htmlspecialchars($task['nomor_faktur_pembelian']) ?>
                        </span>
                        <?php if (!empty($task['kode_pemasok'])): ?>
                            <span class="badge text-[11px] px-2 py-0.5 hidden md:inline-flex shrink-0 bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 border border-slate-200/70 dark:border-slate-700/60 font-medium">
                                <?= htmlspecialchars($task['kode_pemasok']) ?>
                            </span>
                        <?php endif; ?>

                        <!-- BADGE INDIKATOR TANGGAL / TUNGGAKAN JADWAL BELANJA -->
                        <?php if ($isOverdue): ?>
                            <span class="badge text-[11px] font-extrabold px-2.5 py-0.5 shrink-0 inline-flex items-center gap-1.5 bg-rose-50 text-rose-700 border border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800 shadow-2xs"
                                  title="Tugas belanja tertunda dari tanggal <?= date('d/m/Y', strtotime($taskDate)) ?> (terlewat <?= $diffDays ?> hari)">
                                <i data-lucide="alert-triangle" style="width: 12px; height: 12px;" class="text-rose-600 dark:text-rose-400 animate-pulse"></i>
                                <span>Nunggak H-<?= $diffDays ?> (<?= date('d/m', strtotime($taskDate)) ?>)</span>
                            </span>
                        <?php elseif ($isToday): ?>
                            <span class="badge text-[11px] font-bold px-2 py-0.5 shrink-0 inline-flex items-center gap-1 bg-amber-50 text-amber-800 border border-amber-200/90 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800/70"
                                  title="Jadwal belanja hari ini">
                                <i data-lucide="calendar" style="width: 11px; height: 11px;"></i>
                                <span>Hari Ini (<?= date('d/m', strtotime($taskDate)) ?>)</span>
                            </span>
                        <?php elseif ($isTomorrow): ?>
                            <span class="badge text-[11px] font-bold px-2 py-0.5 shrink-0 inline-flex items-center gap-1 bg-blue-50 text-blue-800 border border-blue-200/90 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800/70"
                                  title="Jadwal belanja besok">
                                <i data-lucide="calendar" style="width: 11px; height: 11px;"></i>
                                <span>Besok (<?= date('d/m', strtotime($taskDate)) ?>)</span>
                            </span>
                        <?php elseif (!empty($taskDate)): ?>
                            <span class="badge text-[11px] font-medium px-2 py-0.5 shrink-0 inline-flex items-center gap-1 bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:text-slate-400">
                                <i data-lucide="calendar" style="width: 11px; height: 11px;"></i>
                                <span><?= date('d/m/Y', strtotime($taskDate)) ?></span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- BADGE STATUS TAHAP -->
                    <div class="flex items-center gap-2 shrink-0">
                        <?php if ($isTaskReceived): ?>
                            <span class="shopping-status-pill status-received">
                                <i data-lucide="check-check" style="width: 14px; height: 14px;"></i>
                                <span>Selesai Diterima</span>
                            </span>
                        <?php elseif ($isTaskPicked): ?>
                            <span class="shopping-status-pill status-picked">
                                <i data-lucide="truck" style="width: 13px; height: 13px;"></i>
                                <span>Sudah Diambil</span>
                            </span>
                        <?php elseif ($isTaskFailed): ?>
                            <span class="shopping-status-pill status-failed">
                                <i data-lucide="alert-octagon" style="width: 13px; height: 13px;"></i>
                                <span>Kendala Belanja</span>
                            </span>
                        <?php else: ?>
                            <span class="shopping-status-pill status-pending">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background: #fbbf24;"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2" style="background: #f59e0b;"></span>
                                </span>
                                <span>Menunggu Belanja</span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- BARIS 2: RINGKASAN MUATAN, METODE BAYAR, ESTIMASI BIAYA & TOMBOL DETAIL -->
                <div class="flex items-center justify-between gap-3 text-xs text-ink-secondary flex-wrap">
                    <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                        <div class="inline-flex items-center gap-1.5 font-bold text-xs sm:text-sm text-ink">
                            <i data-lucide="package" style="width: 14px; height: 14px;" class="text-slate-400 dark:text-slate-500 shrink-0"></i>
                            <span><?= number_format((float)$task['total_pcs']) ?> Pcs <span class="text-ink-mute font-normal text-xs">(<?= (int)$task['total_sku'] ?> Macam Bahan)</span></span>
                        </div>
                        <span class="text-slate-300 dark:text-slate-600 select-none">&bull;</span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider <?= $task['metode_bayar_belanja'] === 'tunai_driver' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/90 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/70' : 'bg-slate-100 text-slate-700 border border-slate-200/90 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700' ?>">
                            <i data-lucide="<?= $task['metode_bayar_belanja'] === 'tunai_driver' ? 'banknote' : 'credit-card' ?>" style="width: 12px; height: 12px;" class="opacity-80"></i>
                            <span><?= $task['metode_bayar_belanja'] === 'tunai_driver' ? 'Kas Tunai' : 'Transfer / Tempo' ?></span>
                        </span>
                        <span class="text-slate-300 dark:text-slate-600 select-none">&bull;</span>
                        <div class="inline-flex items-baseline gap-1.5">
                            <span class="text-[11px] font-bold text-ink-mute uppercase tracking-wide">Est:</span>
                            <span class="font-black text-slate-900 dark:text-white font-sans text-sm sm:text-base tracking-tight">
                                Rp <?= number_format((float)$task['total_biaya'], 0, ',', '.') ?>
                            </span>
                        </div>
                        <span class="text-slate-300 dark:text-slate-600 select-none hidden xl:inline">&bull;</span>
                        <span class="text-ink-mute hidden xl:inline-flex items-center gap-1 text-xs truncate max-w-[280px]" title="<?= htmlspecialchars($task['alamat_pemasok'] ?: '') ?>">
                            <i data-lucide="map-pin" style="width: 12px; height: 12px;" class="<?= !empty($task['link_google_maps']) ? 'text-rose-500' : 'text-slate-400' ?> shrink-0"></i>
                            <span class="truncate"><?= htmlspecialchars($task['alamat_pemasok'] ?: 'Alamat vendor belum diatur') ?></span>
                            <?php if (!empty($task['link_google_maps'])): ?>
                                <a href="<?= htmlspecialchars($task['link_google_maps']) ?>" target="_blank" @click.stop class="inline-flex items-center gap-0.5 text-[10px] font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 px-1.5 py-0.5 rounded shrink-0" title="Buka Titik Presisi Google Maps">
                                    <span>Maps</span>
                                    <i data-lucide="external-link" style="width: 9px; height: 9px;"></i>
                                </a>
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- Tombol Aksi Cepat & Detail Belanja -->
                    <div class="flex items-center gap-2">
                        <?php if (!empty($task['url_foto_nota'])): ?>
                            <button type="button" 
                                    class="btn-shopping-photo is-nota" 
                                    @click.stop="openPhotoViewer('<?= htmlspecialchars($task['url_foto_nota']) ?>', 'Nota Belanja - <?= htmlspecialchars(addslashes($task['nama_pemasok'])) ?>')">
                                <i data-lucide="image" style="width: 13px; height: 13px;"></i>
                                <span>Foto Nota</span>
                            </button>
                        <?php elseif (!empty($task['foto_bukti_kendala'])): ?>
                            <button type="button" 
                                    class="btn-shopping-photo is-kendala" 
                                    @click.stop="openPhotoViewer('<?= htmlspecialchars($task['foto_bukti_kendala']) ?>', 'Bukti Kendala Belanja - <?= htmlspecialchars(addslashes($task['nama_pemasok'])) ?>')">
                                <i data-lucide="image" style="width: 13px; height: 13px;"></i>
                                <span>Foto Kendala</span>
                            </button>
                        <?php endif; ?>

                        <button type="button" 
                                class="btn-detail-belanja" 
                                @click.stop="openShoppingDetailModal(<?= htmlspecialchars(json_encode($task)) ?>)">
                            <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                            <span>Detail Belanja</span>
                            <i data-lucide="chevron-right" style="width: 13px; height: 13px;" class="btn-chevron"></i>
                        </button>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- 5. POP-UP MODAL DETAIL LENGKAP BERTAB (SPACIOUS & BEAUTIFULLY SPACED)      -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showDetailModal" x-cloak class="modal-backdrop" style="z-index: 9999;">
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
                            <template x-if="activeDelivery?.status_surat_jalan === 'siap_kirim'">
                                <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 800; font-size: 11.5px; border-radius: 9px; padding: 3px 9px;">Siap Berangkat</span>
                            </template>
                        </div>
                        <div style="font-size: 12.5px; color: var(--color-ink-mute); margin-top: 6px; font-family: var(--font-sans), sans-serif; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span>SJ: <strong class="text-ink-secondary font-mono" x-text="activeDelivery?.nomor_surat_jalan"></strong></span> &bull; 
                            <span>Nota: <strong class="text-ink-secondary font-mono" x-text="'#' + activeDelivery?.nomor_nota"></strong></span> &bull; 
                            <span class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 font-bold">
                                <i data-lucide="calendar" style="width: 13px; height: 13px;"></i>
                                <span>Jadwal Kirim: <strong x-text="formatDateIndo(activeDelivery?.tanggal_surat_jalan || activeDelivery?.waktu_terbit_sj)"></strong></span>
                            </span>
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
                            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--color-ink-mute); letter-spacing: 0.05em; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between;">
                                <span>Data Penugasan &amp; Jadwal Pengiriman</span>
                                <template x-if="activeDelivery?.kode_rute">
                                    <span class="badge badge-mono text-[11px]" x-text="activeDelivery?.kode_rute"></span>
                                </template>
                            </div>
                            
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
                                    <span class="text-ink-mute">Tanggal Rencana Pengiriman:</span>
                                    <div class="font-bold text-blue-600 dark:text-blue-400 text-sm mt-1 flex items-center gap-1.5">
                                        <i data-lucide="calendar" style="width: 14px; height: 14px;"></i>
                                        <span x-text="formatDateIndo(activeDelivery?.tanggal_surat_jalan || activeDelivery?.waktu_terbit_sj)"></span>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-ink-mute">Tanggal Pesanan / Nota:</span>
                                    <div class="font-medium text-ink text-sm mt-1 flex items-center gap-1.5">
                                        <i data-lucide="file-text" style="width: 14px; height: 14px; color: var(--color-ink-mute);"></i>
                                        <span x-text="formatDateIndo(activeDelivery?.tanggal_pesanan)"></span>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-ink-mute">Armada Driver Ditugaskan:</span>
                                    <div class="font-bold text-ink text-sm mt-1 flex items-center gap-1.5">
                                        <i data-lucide="truck" style="width: 14px; height: 14px; color: #2563eb;"></i>
                                        <span x-text="(activeDelivery?.nama_driver || '-') + (activeDelivery?.nopol_driver ? ' (' + activeDelivery?.nopol_driver + ')' : '')"></span>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-ink-mute">Wilayah &amp; Rute Distribusi:</span>
                                    <div class="font-bold text-ink text-sm mt-1 flex items-center gap-1.5">
                                        <i data-lucide="map" style="width: 14px; height: 14px; color: #10b981;"></i>
                                        <span x-text="(activeDelivery?.nama_wilayah || '-') + (activeDelivery?.kode_rute ? ' (' + activeDelivery?.kode_rute + ')' : '')"></span>
                                    </div>
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
                        <input type="hidden" name="filter_date" value="<?= htmlspecialchars($selectedDate ?? '') ?>">
                        <input type="hidden" name="filter_driver_id" value="<?= htmlspecialchars($filterDriver ?? '') ?>">
                        <input type="hidden" name="filter_status" value="<?= htmlspecialchars($statusFilter ?? '') ?>">

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
                        <input type="hidden" name="filter_date" value="<?= htmlspecialchars($selectedDate ?? '') ?>">
                        <input type="hidden" name="filter_driver_id" value="<?= htmlspecialchars($filterDriver ?? '') ?>">
                        <input type="hidden" name="filter_status" value="<?= htmlspecialchars($statusFilter ?? '') ?>">

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
                        <!-- KIRI: Unduh Surat Jalan PDF & Cetak Dot Matrix -->
                        <div class="flex items-center gap-2">
                            <a :href="'<?= Router::url('/deliveries/print?id=') ?>' + encodeURIComponent(activeDelivery?.surat_jalan_id || '')"
                               class="btn btn-secondary btn-sm w-full sm:w-auto justify-center"
                               style="border-radius: 12px; font-weight: 700; font-size: 13px; padding: 9px 16px; display: inline-flex; align-items: center; gap: 7px; color:#0284c7; border-color:#bae6fd; background:#f0f9ff;">
                                <i data-lucide="printer" style="width: 15px; height: 15px;"></i>
                                <span>Cetak Surat Jalan</span>
                            </a>
                            <a :href="'<?= Router::url('/deliveries/pdf?id=') ?>' + encodeURIComponent(activeDelivery?.surat_jalan_id || '')"
                               target="_blank"
                               class="btn btn-secondary btn-sm w-full sm:w-auto justify-center"
                               style="border-radius: 12px; font-weight: 700; font-size: 13px; padding: 9px 16px; display: inline-flex; align-items: center; gap: 7px; color:#dc2626; border-color:#fca5a5; background:#fef2f2;">
                                <i data-lucide="file-text" style="width: 15px; height: 15px;"></i>
                                <span>PDF</span>
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
                                <input type="hidden" name="filter_date" value="<?= htmlspecialchars($selectedDate ?? '') ?>">
                                <input type="hidden" name="filter_driver_id" value="<?= htmlspecialchars($filterDriver ?? '') ?>">
                                <input type="hidden" name="filter_status" value="<?= htmlspecialchars($statusFilter ?? '') ?>">
                                <template x-if="activeDelivery?.status_surat_jalan === 'siap_kirim'">
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
                         loading="lazy"
                         decoding="async"
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
    <!-- ========================================================================= -->
    <!-- 6. POP-UP MODAL DETAIL LENGKAP TUGAS BELANJA PO (BERTAB & MULTI-MODE)     -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showShoppingDetailModal" x-cloak class="modal-backdrop" style="z-index: 9999;">
        <div class="modal-box modal-box-lg" style="max-width: 760px; padding: 0; border-radius: 24px; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;" @click.stop>
            
            <!-- MOBILE PULL HANDLE -->
            <div class="sm:hidden w-full flex justify-center pt-3 pb-1 flex-shrink-0" style="background:var(--color-canvas);">
                <div style="width:40px;height:4px;border-radius:2px;background:var(--color-hairline-strong);"></div>
            </div>

            <!-- 1. MODAL HEADER -->
            <div style="padding: 22px 28px; border-bottom: 1px solid var(--color-hairline); display: flex; align-items: center; justify-content: space-between; background: var(--color-canvas); flex-shrink: 0; gap: 16px;">
                <div style="display: flex; align-items: center; gap: 14px; min-width: 0; flex: 1;">
                    <div style="width: 48px; height: 48px; border-radius: 15px; background: #fef3c7; color: #b45309; border: 1px solid rgba(180,83,9,0.18); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i data-lucide="shopping-cart" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div style="min-width: 0; flex: 1;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <h2 style="font-size: 18px; font-weight: 900; color: var(--color-ink); margin: 0; line-height: 1.25;" x-text="activeShoppingTask?.nama_pemasok"></h2>
                            <span class="badge badge-mono text-xs font-bold text-amber-800 dark:text-amber-300 bg-amber-100/70 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800" x-text="activeShoppingTask?.nomor_faktur_pembelian"></span>
                            
                            <!-- Status Badge -->
                            <template x-if="activeShoppingTask?.status_penerimaan === 'diterima'">
                                <span class="badge" style="background: #d1fae5; color: #065f46; font-weight: 800; font-size: 11.5px; border-radius: 9px; padding: 3px 9px;">Selesai Diterima Gudang</span>
                            </template>
                            <template x-if="activeShoppingTask?.status_penerimaan === 'sudah_diambil'">
                                <span class="badge" style="background: #dbeafe; color: #1e40af; font-weight: 800; font-size: 11.5px; border-radius: 9px; padding: 3px 9px;">Sudah Diambil Driver</span>
                            </template>
                            <template x-if="activeShoppingTask?.status_penerimaan === 'kendala_batal'">
                                <span class="badge" style="background: #ffe4e6; color: #9f1239; font-weight: 800; font-size: 11.5px; border-radius: 9px; padding: 3px 9px;">Kendala Belanja</span>
                            </template>
                            <template x-if="activeShoppingTask?.status_penerimaan === 'ditugaskan_driver'">
                                <span class="badge" style="background: #fef3c7; color: #b45309; font-weight: 800; font-size: 11.5px; border-radius: 9px; padding: 3px 9px;">Menunggu Belanja</span>
                            </template>

                            <!-- Overdue / Tunggakan Badge di Modal Header -->
                            <template x-if="getShoppingDateInfo(activeShoppingTask).isOverdue">
                                <span class="badge" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; font-weight: 800; font-size: 11.5px; border-radius: 9px; padding: 3px 9px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i data-lucide="alert-triangle" style="width: 12px; height: 12px;"></i>
                                    <span x-text="'Tunggakan H-' + getShoppingDateInfo(activeShoppingTask).diffDays"></span>
                                </span>
                            </template>
                        </div>
                        <div style="font-size: 12.5px; color: var(--color-ink-mute); margin-top: 6px; font-family: var(--font-sans), sans-serif;">
                            <span>PO: <strong class="text-ink-secondary" x-text="activeShoppingTask?.nomor_faktur_pembelian"></strong></span> &bull; 
                            <span>Jadwal: <strong :class="getShoppingDateInfo(activeShoppingTask).isOverdue ? 'text-rose-600 dark:text-rose-400 font-extrabold' : 'text-ink-secondary'" x-text="activeShoppingTask?.tanggal_jadwal_belanja || activeShoppingTask?.tanggal_pembelian"></strong></span>
                            <template x-if="getShoppingDateInfo(activeShoppingTask).isOverdue">
                                <span class="text-rose-600 dark:text-rose-400 font-bold" x-text="' (Terlewat ' + getShoppingDateInfo(activeShoppingTask).diffDays + ' hari)'"></span>
                            </template>
                            <template x-if="activeShoppingTask?.kode_pemasok">
                                <span> &bull; Kode: <span class="badge badge-secondary" style="font-size: 10px; padding: 1px 5px;" x-text="activeShoppingTask.kode_pemasok"></span></span>
                            </template>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 16px; flex-shrink: 0;">
                    <div class="hidden md:flex flex-col items-end">
                        <span style="font-size: 10.5px; color: var(--color-ink-mute); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Estimasi Belanja</span>
                        <span class="font-black text-amber-600 dark:text-amber-400 font-sans" style="font-size: 17px;" x-text="'Rp ' + formatRupiah(activeShoppingTask?.total_biaya)"></span>
                    </div>
                    <button type="button" @click="closeShoppingDetailModal()" class="btn btn-ghost btn-sm" style="width: 38px; height: 38px; padding: 0; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--color-ink-mute);" aria-label="Tutup">
                        <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                    </button>
                </div>
            </div>

            <!-- 2. TAB NAVIGATION BAR (HANYA MUNCUL DI MODE VIEW DETAIL) -->
            <template x-if="shoppingViewMode === 'detail'">
                <div class="modal-tab-nav custom-scrollbar" style="padding: 10px 24px;">
                    <button type="button" @click="shoppingActiveTab = 'info'" class="modal-tab-btn" :class="{ 'is-active': shoppingActiveTab === 'info' }">
                        <i data-lucide="store" style="width: 14px; height: 14px;"></i>
                        <span>Info Vendor &amp; Lokasi</span>
                    </button>
                    <button type="button" @click="shoppingActiveTab = 'items'" class="modal-tab-btn" :class="{ 'is-active': shoppingActiveTab === 'items' }">
                        <i data-lucide="package" style="width: 14px; height: 14px;"></i>
                        <span>Daftar Bahan / Barang</span>
                        <span class="badge" style="font-size: 10px; padding: 1px 6px; border-radius: 10px;" x-text="activeShoppingTask?.items?.length || '0'"></span>
                    </button>
                    <button type="button" @click="shoppingActiveTab = 'payment'" class="modal-tab-btn" :class="{ 'is-active': shoppingActiveTab === 'payment' }">
                        <i data-lucide="credit-card" style="width: 14px; height: 14px;"></i>
                        <span>Pembayaran &amp; Nota</span>
                    </button>
                </div>
            </template>

            <!-- 3. MODAL BODY (SCROLLABLE) -->
            <div class="modal-tab-body custom-scrollbar" style="padding: 26px 28px; overflow-y: auto; flex: 1;">

                <!-- ================================================================= -->
                <!-- A. MODE 1: VIEW DETAIL BERTAB                                     -->
                <!-- ================================================================= -->
                <div x-show="shoppingViewMode === 'detail'" class="space-y-6">
                    
                    <!-- TAB 1: INFO VENDOR & LOKASI -->
                    <div x-show="shoppingActiveTab === 'info'" class="space-y-5">
                        
                        <!-- Box Identitas Vendor & Alamat -->
                        <div style="background: var(--color-canvas-soft); border: 1px solid var(--color-hairline); border-radius: 20px; padding: 22px 24px;" class="space-y-5">
                            
                            <div class="flex items-start justify-between gap-4 flex-wrap">
                                <div>
                                    <div style="font-size: 10.5px; color: var(--color-ink-mute); font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Vendor Pemasok</div>
                                    <div style="font-size: 17px; font-weight: 800; color: var(--color-ink);" x-text="activeShoppingTask?.nama_pemasok"></div>
                                    <div style="font-size: 13px; color: var(--color-ink-secondary); margin-top: 4px;">
                                        Kode: <strong style="color: var(--color-ink);" x-text="activeShoppingTask?.kode_pemasok || '-'"></strong>
                                        <template x-if="activeShoppingTask?.supplier_kontak">
                                            <span> &bull; PIC: <strong style="color: var(--color-ink);" x-text="activeShoppingTask.supplier_kontak"></strong></span>
                                        </template>
                                        <template x-if="activeShoppingTask?.supplier_wa">
                                            <span> &bull; Kontak: <span class="font-mono" x-text="activeShoppingTask.supplier_wa"></span></span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Quick WA Button -->
                                <template x-if="activeShoppingTask?.supplier_wa">
                                    <a :href="'https://wa.me/' + cleanWa(activeShoppingTask?.supplier_wa) + '?text=' + encodeURIComponent('Halo ' + (activeShoppingTask?.supplier_kontak ? (activeShoppingTask.supplier_kontak + ' (' + activeShoppingTask.nama_pemasok + ')') : (activeShoppingTask?.nama_pemasok || '')) + ', armada KEREN Snack sedang menuju ke lokasi Anda untuk pengambilan belanjaan PO #' + (activeShoppingTask?.nomor_faktur_pembelian || '') + '.')" target="_blank" class="quick-action-pill is-wa">
                                        <i data-lucide="message-circle" style="width: 15px; height: 15px;"></i>
                                        <span>WhatsApp ( <span x-text="activeShoppingTask?.supplier_wa"></span> )</span>
                                    </a>
                                </template>
                            </div>

                            <div style="height: 1px; background: var(--color-hairline);"></div>

                            <!-- Alamat Lengkap & Maps -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-ink-mute">
                                        <i data-lucide="map-pin" style="width: 16px; height: 16px; color: #ef4444;"></i>
                                        <span>Alamat Lengkap Vendor:</span>
                                    </div>
                                    <template x-if="activeShoppingTask?.link_google_maps">
                                        <span class="badge" style="background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.25);font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;display:inline-flex;align-items:center;gap:3px;">
                                            <i data-lucide="map-pin" style="width:10px;height:10px;"></i>
                                            <span>Titik Presisi Tersedia</span>
                                        </span>
                                    </template>
                                </div>
                                <div class="text-sm font-medium text-ink leading-relaxed" style="padding-left: 24px;" x-text="activeShoppingTask?.alamat_pemasok || 'Alamat vendor belum tercatat di master data'"></div>
                                
                                <div style="padding-left: 24px;" class="pt-1.5 flex items-center gap-2">
                                    <a :href="activeShoppingTask?.link_google_maps || ('https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent((activeShoppingTask?.nama_pemasok || '') + ' ' + (activeShoppingTask?.alamat_pemasok || '')))" target="_blank" rel="noopener noreferrer" class="quick-action-pill is-maps" :style="activeShoppingTask?.link_google_maps ? 'background:rgba(239,68,68,0.08);color:#ef4444;border-color:rgba(239,68,68,0.3);' : ''">
                                        <i data-lucide="map-pin" style="width: 15px; height: 15px; color:#ef4444;" x-show="activeShoppingTask?.link_google_maps"></i>
                                        <i data-lucide="navigation" style="width: 15px; height: 15px;" x-show="!activeShoppingTask?.link_google_maps"></i>
                                        <span x-text="activeShoppingTask?.link_google_maps ? 'Buka Titik Presisi Google Maps' : 'Buka Google Maps Navigasi'"></span>
                                    </a>
                                </div>
                            </div>

                            <!-- Catatan Khusus Vendor -->
                            <template x-if="activeShoppingTask?.supplier_catatan">
                                <div style="padding:12px 14px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);border-radius:12px;font-size:12px;color:#92400e;display:flex;align-items:flex-start;gap:8px;">
                                    <i data-lucide="info" style="width:16px;height:16px;color:#d97706;flex-shrink:0;margin-top:1px;"></i>
                                    <div>
                                        <strong>Catatan Khusus Vendor:</strong>
                                        <div style="margin-top:2px;color:var(--color-ink);" x-text="activeShoppingTask.supplier_catatan"></div>
                                    </div>
                                </div>
                            </template>

                        </div>

                        <!-- Alert Banner Carry-Over / Tunggakan Jika Terlambat -->
                        <template x-if="getShoppingDateInfo(activeShoppingTask).isOverdue">
                            <div class="p-4 bg-rose-50/90 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/60 rounded-2xl text-xs sm:text-sm text-rose-900 dark:text-rose-200 flex items-start gap-3">
                                <div class="w-8 h-8 rounded-xl bg-rose-100 dark:bg-rose-900/60 text-rose-600 dark:text-rose-300 flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="alert-triangle" style="width: 18px; height: 18px;"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-extrabold text-rose-950 dark:text-rose-100 flex items-center gap-2 flex-wrap">
                                        <span>Tugas Belanja Tertunda / Carry-Over Hari Sebelumnya</span>
                                        <span class="badge text-[10.5px] font-black bg-rose-200/80 text-rose-900 border border-rose-300 dark:bg-rose-900 dark:text-rose-200" x-text="'Terlewat ' + getShoppingDateInfo(activeShoppingTask).diffDays + ' Hari'"></span>
                                    </div>
                                    <p class="text-xs text-rose-800 dark:text-rose-300 mt-1 leading-relaxed">
                                        PO ini awalnya dijadwalkan pada tanggal <strong class="font-bold underline" x-text="activeShoppingTask?.tanggal_jadwal_belanja || activeShoppingTask?.tanggal_pembelian"></strong>. Tugas ini otomatis dibawa ke rute hari ini sebagai antrean belanja tertunda agar bahan belanjaan tidak terlewatkan dan segera dibelanjakan driver.
                                    </p>
                                </div>
                            </div>
                        </template>

                        <!-- Box Info Operasional Penugasan PO -->
                        <div style="background: var(--color-canvas-soft); border: 1px solid var(--color-hairline); border-radius: 20px; padding: 20px 24px;">
                            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--color-ink-mute); letter-spacing: 0.05em; margin-bottom: 14px;">Data Penugasan &amp; PO Belanja</div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                <div>
                                    <span class="text-ink-mute">Nomor PO Pembelian:</span>
                                    <div class="font-mono font-bold text-ink text-sm mt-1" x-text="activeShoppingTask?.nomor_faktur_pembelian"></div>
                                </div>
                                <div>
                                    <span class="text-ink-mute">Tanggal Jadwal Belanja:</span>
                                    <div class="font-medium text-ink text-sm mt-1 flex items-center gap-1.5 flex-wrap">
                                        <span :class="getShoppingDateInfo(activeShoppingTask).isOverdue ? 'text-rose-600 dark:text-rose-400 font-extrabold' : ''" x-text="activeShoppingTask?.tanggal_jadwal_belanja || activeShoppingTask?.tanggal_pembelian"></span>
                                        <template x-if="getShoppingDateInfo(activeShoppingTask).isOverdue">
                                            <span class="badge text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800" x-text="'Nunggak H-' + getShoppingDateInfo(activeShoppingTask).diffDays"></span>
                                        </template>
                                        <template x-if="getShoppingDateInfo(activeShoppingTask).isToday">
                                            <span class="badge text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">Hari Ini</span>
                                        </template>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-ink-mute">Driver Ditugaskan:</span>
                                    <div class="font-bold text-ink text-sm mt-1" x-text="(activeShoppingTask?.nama_driver || '-') + (activeShoppingTask?.nopol_driver ? ' (' + activeShoppingTask?.nopol_driver + ')' : '')"></div>
                                </div>
                                <div>
                                    <span class="text-ink-mute">Metode Logistik:</span>
                                    <div class="font-medium text-ink text-sm mt-1">Diambil Driver Operasional</div>
                                </div>
                            </div>
                        </div>

                        <!-- Box Instruksi Khusus Belanja Driver -->
                        <template x-if="activeShoppingTask?.instruksi_driver">
                            <div class="p-4 bg-amber-50/70 dark:bg-amber-950/25 border border-amber-200/80 dark:border-amber-800/40 rounded-2xl text-xs sm:text-sm text-amber-900 dark:text-amber-300 flex items-start gap-3">
                                <i data-lucide="info" style="width: 20px; height: 20px; color: #d97706; flex-shrink: 0; margin-top: 1px;"></i>
                                <div>
                                    <strong class="font-bold">Instruksi Khusus Belanja:</strong>
                                    <div class="mt-1 leading-relaxed" x-text="activeShoppingTask.instruksi_driver"></div>
                                </div>
                            </div>
                        </template>

                        <!-- Box Info Realisasi (Jika Sudah Diambil atau Selesai Diterima) -->
                        <template x-if="activeShoppingTask?.status_penerimaan === 'sudah_diambil' || activeShoppingTask?.status_penerimaan === 'diterima'">
                            <div class="p-4.5 bg-emerald-50 dark:bg-emerald-950/25 rounded-2xl text-xs sm:text-sm text-emerald-900 dark:text-emerald-300 space-y-2 border border-emerald-200/60 dark:border-emerald-800/40">
                                <div class="flex items-center gap-2 font-bold text-emerald-950 dark:text-emerald-200">
                                    <i data-lucide="check-circle" style="width: 18px; height: 18px; color: #059669;"></i>
                                    <span>Realisasi Pengambilan Barang:</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs pt-1">
                                    <div>
                                        <span class="text-emerald-700 dark:text-emerald-400">Nota Vendor:</span>
                                        <div class="font-mono font-bold text-emerald-950 dark:text-emerald-100 mt-0.5" x-text="activeShoppingTask?.nomor_nota_vendor || '-'"></div>
                                    </div>
                                    <div>
                                        <span class="text-emerald-700 dark:text-emerald-400">Waktu Diambil:</span>
                                        <div class="font-medium text-emerald-950 dark:text-emerald-100 mt-0.5" x-text="formatDateTime(activeShoppingTask?.waktu_diambil)"></div>
                                    </div>
                                    <div>
                                        <span class="text-emerald-700 dark:text-emerald-400">Kas Dibayar Driver:</span>
                                        <div class="font-mono font-bold text-emerald-950 dark:text-emerald-100 mt-0.5" x-text="'Rp ' + formatRupiah(activeShoppingTask?.nominal_dibayar_driver)"></div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Box Info Kendala (Jika Kendala Batal) -->
                        <template x-if="activeShoppingTask?.status_penerimaan === 'kendala_batal'">
                            <div class="p-4.5 bg-rose-50 dark:bg-rose-950/25 rounded-2xl text-xs sm:text-sm text-rose-900 dark:text-rose-300 flex items-start gap-3 border border-rose-200/60 dark:border-rose-800/40">
                                <i data-lucide="alert-octagon" style="width: 20px; height: 20px; color: #e11d48; flex-shrink: 0; margin-top: 2px;"></i>
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-rose-950 dark:text-rose-200">
                                        <span>Kendala Belanja Dilaporkan:</span>
                                    </div>
                                    <div class="text-rose-800 dark:text-rose-300 mt-1 leading-relaxed" x-text="activeShoppingTask?.alasan_kendala || 'Kendala tidak tercatat'"></div>
                                </div>
                            </div>
                        </template>

                    </div>

                    <!-- TAB 2: RINCIAN BAHAN / BARANG -->
                    <div x-show="shoppingActiveTab === 'items'" class="space-y-4">
                        
                        <!-- Ringkasan Macam Bahan Strip -->
                        <div class="flex items-center justify-between gap-3 p-4 bg-canvas-soft border border-hairline rounded-2xl text-xs flex-wrap">
                            <div class="flex items-center gap-2">
                                <i data-lucide="package" style="width: 17px; height: 17px; color: #d97706;"></i>
                                <span class="font-bold text-ink">Total Estimasi Belanja:</span>
                                <span class="font-black text-amber-600 dark:text-amber-400 font-sans text-sm" x-text="'Rp ' + formatRupiah(activeShoppingTask?.total_biaya)"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="badge badge-secondary font-bold text-xs px-3 py-1" x-text="(activeShoppingTask?.items?.length || 0) + ' Macam Bahan'"></span>
                                <span class="badge badge-mono text-xs px-2.5 py-1" x-text="(activeShoppingTask?.total_pcs || 0) + ' Total Item'"></span>
                            </div>
                        </div>

                        <!-- Tabel Bahan Belanjaan -->
                        <div class="table-scroll" style="max-height: 340px; border: 1px solid var(--color-hairline); border-radius: 18px; overflow: hidden;">
                            <table class="table" style="margin: 0; width: 100%; font-size: 13px;">
                                <thead style="background: var(--color-canvas-soft); position: sticky; top: 0; z-index: 2;">
                                    <tr style="border-bottom: 1px solid var(--color-hairline);">
                                        <th class="cell-center" style="width: 45px; padding: 12px 16px;">No</th>
                                        <th style="width: 120px; padding: 12px 16px;">Kode SKU</th>
                                        <th style="padding: 12px 16px;">Nama Bahan / Item</th>
                                        <th class="cell-center" style="width: 130px; padding: 12px 16px;">Jumlah Belanja</th>
                                        <th class="text-right" style="width: 130px; padding: 12px 16px;">Harga Satuan</th>
                                        <th class="text-right" style="width: 130px; padding: 12px 16px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, idx) in activeShoppingTask?.items || []" :key="item.item_id">
                                        <tr style="border-bottom: 1px solid var(--color-hairline);">
                                            <td class="cell-center text-ink-mute font-semibold" style="padding: 12px 16px;" x-text="idx + 1"></td>
                                            <td style="padding: 12px 16px;">
                                                <span class="badge badge-mono" style="font-size: 11px; padding: 2px 7px;" x-text="item.kode_sku"></span>
                                            </td>
                                            <td style="padding: 12px 16px;">
                                                <div style="font-weight: 700; color: var(--color-ink);" x-text="item.nama_item"></div>
                                            </td>
                                            <td class="cell-center font-black text-ink" style="padding: 12px 16px; color: #d97706; font-family: var(--font-sans), sans-serif;" x-text="Number(item.kuantitas) + ' ' + (item.satuan || 'Item')"></td>
                                            <td class="text-right font-mono" style="padding: 12px 16px;" x-text="'Rp ' + formatRupiah(item.harga_satuan)"></td>
                                            <td class="text-right font-bold text-ink font-mono" style="padding: 12px 16px;" x-text="'Rp ' + formatRupiah(item.subtotal)"></td>
                                        </tr>
                                    </template>
                                    <template x-if="!activeShoppingTask?.items || activeShoppingTask.items.length === 0">
                                        <tr>
                                            <td colspan="6" class="text-center py-6 text-ink-mute text-xs">
                                                Tidak ada rincian item dalam PO ini.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                    </div>

                    <!-- TAB 3: METODE BAYAR & NOTA -->
                    <div x-show="shoppingActiveTab === 'payment'" class="space-y-4">
                        
                        <div style="background: var(--color-canvas-soft); border: 1px solid var(--color-hairline); border-radius: 20px; padding: 22px 24px;" class="space-y-5">
                            
                            <div class="flex items-center justify-between gap-4 flex-wrap">
                                <div>
                                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--color-ink-mute); letter-spacing: 0.05em;">Metode Pembayaran Belanja</span>
                                    <div class="mt-1.5">
                                        <template x-if="activeShoppingTask?.metode_bayar_belanja === 'tunai_driver'">
                                            <span class="badge badge-success font-bold uppercase text-xs px-3 py-1">💵 Kas Tunai Driver / Toko</span>
                                        </template>
                                        <template x-if="activeShoppingTask?.metode_bayar_belanja !== 'tunai_driver'">
                                            <span class="badge badge-info font-bold uppercase text-xs px-3 py-1">💳 Ditransfer Kantor / Tempo</span>
                                        </template>
                                    </div>
                                    <template x-if="activeShoppingTask?.supplier_termin_bayar">
                                        <div class="mt-1" style="font-size:11px;color:var(--color-ink-mute);">
                                            Termin Master: <strong style="color:var(--color-ink);" x-text="activeShoppingTask.supplier_termin_bayar === 'cash' ? 'Tunai / COD' : (activeShoppingTask.supplier_termin_bayar === 'transfer' ? 'Transfer Bank (CBD)' : (activeShoppingTask.supplier_termin_bayar.replace(/_/g, ' ').toUpperCase()))"></strong>
                                        </div>
                                    </template>
                                </div>
                                <div class="text-right">
                                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--color-ink-mute); letter-spacing: 0.05em;">Status Pembayaran PO</span>
                                    <div class="mt-1.5">
                                        <span class="badge" :class="activeShoppingTask?.status_pembayaran === 'lunas' ? 'badge-success' : 'badge-warning'" style="font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 3px 10px;" x-text="activeShoppingTask?.status_pembayaran === 'lunas' ? 'LUNAS' : 'TEMPO / BELUM LUNAS'"></span>
                                    </div>
                                </div>
                            </div>

                            <div style="height: 1px; background: var(--color-hairline);"></div>

                            <!-- Rekening Vendor Pemasok -->
                            <div style="background: var(--color-canvas); border: 1px solid var(--color-hairline); border-radius: 14px; padding: 14px 16px;" class="space-y-2">
                                <div class="text-[11px] font-bold text-ink-mute uppercase tracking-wider">Rekening Bank Vendor Pemasok:</div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                    <div>
                                        <span class="text-ink-mute">Bank:</span>
                                        <div class="font-bold text-ink mt-0.5" x-text="activeShoppingTask?.nama_bank || '-'"></div>
                                    </div>
                                    <div>
                                        <span class="text-ink-mute">No. Rekening:</span>
                                        <div class="font-mono font-bold text-ink mt-0.5" x-text="activeShoppingTask?.nomor_rekening || '-'"></div>
                                    </div>
                                    <div>
                                        <span class="text-ink-mute">Atas Nama:</span>
                                        <div class="font-bold text-ink mt-0.5" x-text="activeShoppingTask?.atas_nama_rekening || '-'"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Ringkasan Anggaran Biaya -->
                            <div class="space-y-2.5 text-sm">
                                <div class="flex items-center justify-between text-ink-secondary">
                                    <span>Total Estimasi Biaya PO:</span>
                                    <span class="font-sans font-black text-amber-600 dark:text-amber-400 text-base" x-text="'Rp ' + formatRupiah(activeShoppingTask?.total_biaya)"></span>
                                </div>
                                <template x-if="activeShoppingTask?.nominal_dibayar_driver">
                                    <div class="flex items-center justify-between text-ink-secondary pt-2 border-t border-hairline">
                                        <span>Realisasi Kas Dibayar Driver:</span>
                                        <span class="font-mono font-bold text-ink" x-text="'Rp ' + formatRupiah(activeShoppingTask.nominal_dibayar_driver)"></span>
                                    </div>
                                </template>
                                <template x-if="activeShoppingTask?.nomor_nota_vendor">
                                    <div class="flex items-center justify-between text-ink-secondary">
                                        <span>Nomor Nota / Bon Vendor:</span>
                                        <span class="font-mono font-bold text-ink" x-text="activeShoppingTask.nomor_nota_vendor"></span>
                                    </div>
                                </template>
                            </div>

                        </div>

                        <!-- Box Bukti Foto Nota Vendor -->
                        <template x-if="activeShoppingTask?.url_foto_nota">
                            <div style="background: var(--color-canvas-soft); border: 1px solid var(--color-hairline); border-radius: 20px; padding: 20px 24px;" class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs font-bold uppercase tracking-wider text-ink-mute flex items-center gap-2">
                                        <i data-lucide="file-check" style="width: 15px; height: 15px; color: #059669;"></i>
                                        <span>Foto Bukti Nota / Bon Fisik Vendor:</span>
                                    </div>
                                    <button type="button" @click="openPhotoViewer(activeShoppingTask.url_foto_nota, 'Nota Belanja - ' + (activeShoppingTask?.nama_pemasok || ''))" class="btn btn-secondary btn-sm" style="font-size: 11.5px; padding: 4px 10px; border-radius: 8px;">
                                        <i data-lucide="zoom-in" style="width: 13px; height: 13px;"></i>
                                        <span>Perbesar</span>
                                    </button>
                                </div>
                                <div style="max-width: 320px; border-radius: 14px; overflow: hidden; border: 1px solid var(--color-hairline); cursor: pointer;" @click="openPhotoViewer(activeShoppingTask.url_foto_nota, 'Nota Belanja - ' + (activeShoppingTask?.nama_pemasok || ''))">
                                    <img :src="(activeShoppingTask.url_foto_nota || '').startsWith('http') ? activeShoppingTask.url_foto_nota : ('<?= Router::url('/') ?>' + (activeShoppingTask.url_foto_nota || '').replace(/^\//, ''))" 
                                         alt="Nota Vendor" 
                                         loading="lazy" 
                                         decoding="async" 
                                         style="width: 100%; height: auto; max-height: 200px; object-fit: cover;">
                                </div>
                            </div>
                        </template>

                        <!-- Display Existing Issue Photo If Any -->
                        <template x-if="activeShoppingTask?.foto_bukti_kendala">
                            <div class="mt-3 p-3 bg-red-50/80 rounded-xl border border-red-200">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-semibold text-red-800 flex items-center gap-1.5">
                                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-red-600"></i>
                                        Foto Bukti Kendala Belanja
                                    </span>
                                    <button type="button" @click="openPhotoViewer(activeShoppingTask.foto_bukti_kendala, 'Bukti Kendala - ' + (activeShoppingTask?.nama_pemasok || ''))" class="btn btn-secondary btn-sm" style="font-size: 11.5px; padding: 4px 10px; border-radius: 8px; color: #e11d48; border-color: #fecaca;">
                                        <i data-lucide="maximize-2" class="w-3 h-3"></i>
                                        Perbesar
                                    </button>
                                </div>
                                <div style="max-width: 320px; border-radius: 14px; overflow: hidden; border: 1px solid #fca5a5; cursor: pointer;" @click="openPhotoViewer(activeShoppingTask.foto_bukti_kendala, 'Bukti Kendala - ' + (activeShoppingTask?.nama_pemasok || ''))">
                                    <img :src="(activeShoppingTask.foto_bukti_kendala || '').startsWith('http') ? activeShoppingTask.foto_bukti_kendala : ('<?= Router::url('/') ?>' + (activeShoppingTask.foto_bukti_kendala || '').replace(/^\//, ''))" 
                                         alt="Foto Kendala" 
                                         loading="lazy" 
                                         decoding="async" 
                                         style="width: 100%; height: auto; max-height: 200px; object-fit: cover;">
                                </div>
                            </div>
                        </template>

                    </div>

                </div>

                <!-- ================================================================= -->
                <!-- B. MODE 2: FORM SELESAI BELANJA / AMBIL BAHAN (INLINE DI MODAL)   -->
                <!-- ================================================================= -->
                <div x-show="shoppingViewMode === 'complete_form'" class="space-y-6">
                    
                    <!-- Banner Info Kuning Emas / Amber -->
                    <div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1.5px solid #fde68a; border-radius: 18px; padding: 18px 22px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(217, 119, 6, 0.06);">
                        <div style="width: 44px; height: 44px; border-radius: 14px; background: #fde68a; border: 1px solid #f59e0b; color: #b45309; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="shopping-bag" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div>
                            <div style="font-size: 16px; font-weight: 900; color: #92400e; line-height: 1.25;">Konfirmasi Selesai Belanja / Ambil Bahan</div>
                            <div style="font-size: 13px; color: #b45309; margin-top: 3px; font-weight: 500;">Silakan isi nomor nota vendor dan nominal kas yang dibayarkan.</div>
                        </div>
                    </div>

                    <form id="completeShoppingForm" action="<?= Router::url('/driver-deliveries/shopping/complete') ?>" method="POST" enctype="multipart/form-data" class="space-y-5 text-left"
                          data-action-text="Menyimpan hasil belanja...">
                        <input type="hidden" name="purchase_id" :value="activeShoppingTask?.id">

                        <!-- 1. Nomor Nota Vendor -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-mute">
                                Nomor Nota / Bon Vendor <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nomor_nota_vendor" x-model="shoppingCompleteForm.nomor_nota_vendor" required placeholder="Contoh: INV-2026/09/012 atau nomor bon fisik kasir" class="form-input font-medium" style="height: 46px; border-radius: 14px; font-size: 14px;">
                        </div>

                        <!-- 2. Nominal Riil Dibayarkan -->
                        <div style="padding: 18px 20px; background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 18px;" class="space-y-2">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <label class="block font-bold text-xs uppercase tracking-wider text-amber-900">
                                    💵 Nominal Riil yang Dibayarkan (Rp)
                                </label>
                                <span class="badge" :class="activeShoppingTask?.metode_bayar_belanja === 'tunai_driver' ? 'badge-success' : 'badge-info'" style="font-size: 10.5px; font-weight: 700;" x-text="activeShoppingTask?.metode_bayar_belanja === 'tunai_driver' ? 'Kas Tunai Toko' : 'Transfer Kantor / Tempo'"></span>
                            </div>
                            <input type="text" inputmode="numeric" name="nominal_dibayar_driver" x-model="shoppingCompleteForm.nominal_dibayar_driver" class="form-input font-bold font-mono input-rupiah" style="height: 46px; border-radius: 14px; font-size: 16px; color: #b45309; background: #ffffff;">
                            <div style="font-size: 12px; color: #92400e;">
                                Estimasi Total PO: <strong x-text="'Rp ' + formatRupiah(activeShoppingTask?.total_biaya)"></strong> &bull; 
                                <span x-show="activeShoppingTask?.metode_bayar_belanja === 'tunai_driver'">Isikan jumlah uang tunai yang diserahkan ke pihak toko/vendor.</span>
                                <span x-show="activeShoppingTask?.metode_bayar_belanja !== 'tunai_driver'">Isi Rp 0 jika pembayaran ditransfer langsung oleh kantor/owner.</span>
                            </div>
                        </div>

                        <!-- 3. Foto Bukti Nota -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-mute">
                                Foto Bukti Nota / Bon Fisik Vendor (Kamera / Galeri)
                            </label>
                            <input type="file" name="foto_nota" accept="image/*" capture="environment" class="form-input" style="padding: 9px; border-radius: 14px; font-size: 13px;" @change="handleShoppingPhotoChange($event)" x-ref="shoppingPhotoInput">
                            
                            <!-- Thumbnail preview -->
                            <template x-if="shoppingCompletePreview">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;margin-top:8px;">
                                    <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                                        <img :src="shoppingCompletePreview" alt="Preview Foto Nota" style="width:48px;height:48px;object-fit:cover;border-radius:10px;border:1px solid var(--color-hairline);flex-shrink:0;">
                                        <div style="min-width:0;">
                                            <div style="font-size:13px;font-weight:700;color:var(--color-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Foto Nota Siap Diunggah</div>
                                            <div style="font-size:11.5px;color:#059669;font-weight:600;display:flex;align-items:center;gap:4px;">
                                                <i data-lucide="check" style="width:13px;height:13px;"></i>
                                                <span>Terkompresi Otomatis</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" @click="clearShoppingPhoto()" class="btn btn-ghost btn-sm" style="padding:6px 12px;border-radius:10px;color:#e11d48;background:rgba(225,29,72,0.08);border:1px solid rgba(225,29,72,0.2);display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;flex-shrink:0;" title="Hapus Foto">
                                        <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </template>

                            <div style="font-size: 12px; color: var(--color-ink-mute);">
                                Foto bon atau nota fisik dari kasir vendor sebagai bukti validasi pencatatan pembukuan.
                            </div>
                        </div>

                        <!-- 4. Catatan Driver -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-mute">
                                Catatan Lapangan Driver (Opsional)
                            </label>
                            <textarea name="catatan_driver" x-model="shoppingCompleteForm.catatan_driver" rows="3" placeholder="Contoh: Barang lengkap dimasukkan ke dalam armada, siap dibawa ke gudang..." class="form-input font-medium" style="border-radius: 14px; font-size: 13.5px; padding: 12px;"></textarea>
                        </div>
                    </form>
                </div>

                <!-- ================================================================= -->
                <!-- C. MODE 3: FORM LAPOR KENDALA BELANJA (INLINE DI MODAL)           -->
                <!-- ================================================================= -->
                <div x-show="shoppingViewMode === 'issue_form'" class="space-y-6">
                    
                    <!-- Banner Info Merah -->
                    <div style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border: 1.5px solid #fda4af; border-radius: 18px; padding: 18px 22px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(225, 29, 72, 0.06);">
                        <div style="width: 44px; height: 44px; border-radius: 14px; background: #fecdd3; border: 1px solid #f43f5e; color: #e11d48; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="alert-octagon" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div>
                            <div style="font-size: 16px; font-weight: 900; color: #9f1239; line-height: 1.25;">Lapor Kendala Belanja Vendor</div>
                            <div style="font-size: 13px; color: #be123c; margin-top: 3px; font-weight: 500;">Laporkan jika vendor tutup, stok habis, atau kendala lainnya di lapangan.</div>
                        </div>
                    </div>

                    <form id="issueShoppingForm" action="<?= Router::url('/driver-deliveries/shopping/report-issue') ?>" method="POST" enctype="multipart/form-data" class="space-y-5 text-left"
                          data-action-text="Melaporkan kendala belanja...">
                        <input type="hidden" name="purchase_id" :value="activeShoppingTask?.id">
                        <input type="hidden" name="alasan_kendala" :value="combinedShoppingIssueReason">

                        <!-- 1. Kategori Kendala -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-mute">
                                Kategori Kendala <span class="text-danger">*</span>
                            </label>
                            <select x-model="shoppingIssueForm.alasan_kategori" required class="form-input font-medium" style="height: 46px; border-radius: 14px; font-size: 14px;">
                                <option value="Toko / Vendor Tutup">Toko / Vendor Tutup</option>
                                <option value="Stok Barang Habis di Pemasok">Stok Barang Habis di Pemasok</option>
                                <option value="Harga Naik Melebihi Anggaran">Harga Naik Melebihi Anggaran</option>
                                <option value="Antrian Terlalu Panjang">Antrian Terlalu Panjang</option>
                                <option value="Kendala Akses / Armada">Kendala Akses / Armada</option>
                                <option value="Kendala Lainnya">Kendala Lainnya</option>
                            </select>
                        </div>

                        <!-- 2. Rincian Kendala -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-mute">
                                Penjelasan Rincian Kendala <span class="text-danger">*</span>
                            </label>
                            <textarea x-model="shoppingIssueForm.alasan_detail" required rows="3" placeholder="Jelaskan kondisi di lokasi vendor..." class="form-input font-medium" style="border-radius: 14px; font-size: 13.5px; padding: 12px;"></textarea>
                        </div>

                        <!-- 3. Foto Bukti Kendala -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-mute">
                                Foto Bukti Kendala (Kamera / Galeri HP)
                            </label>
                            <input type="file" name="foto_kendala" accept="image/*" capture="environment" class="form-input" style="padding: 9px; border-radius: 14px; font-size: 13px;" @change="handleShoppingIssuePhotoChange($event)" x-ref="shoppingIssuePhotoInput">
                            
                            <template x-if="shoppingIssuePreview">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;margin-top:8px;">
                                    <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                                        <img :src="shoppingIssuePreview" alt="Preview Foto Kendala" style="width:48px;height:48px;object-fit:cover;border-radius:10px;border:1px solid var(--color-hairline);flex-shrink:0;">
                                        <div style="min-width:0;">
                                            <div style="font-size:13px;font-weight:700;color:var(--color-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Foto Kendala Terpilih</div>
                                            <div style="font-size:11.5px;color:#059669;font-weight:600;display:flex;align-items:center;gap:4px;">
                                                <i data-lucide="check" style="width:13px;height:13px;"></i>
                                                <span>Terkompresi Otomatis</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" @click="clearShoppingIssuePhoto()" class="btn btn-ghost btn-sm" style="padding:6px 12px;border-radius:10px;color:#e11d48;background:rgba(225,29,72,0.08);border:1px solid rgba(225,29,72,0.2);display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;flex-shrink:0;" title="Hapus Foto">
                                        <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </template>

                            <div style="font-size: 12px; color: var(--color-ink-mute);">
                                Ambil foto kondisi toko (tutup / banner / akses) sebagai bukti kendala.
                            </div>
                        </div>
                    </form>
                </div>

            </div>

            <!-- 4. MODAL FOOTER -->
            <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid var(--color-hairline); background: var(--color-canvas); flex-shrink: 0;">
                
                <!-- A. FOOTER MODE DETAIL -->
                <template x-if="shoppingViewMode === 'detail'">
                    <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 w-full">
                        <!-- Kiri: Unduh PDF PO -->
                        <div class="flex items-center gap-2">
                            <a :href="'<?= Router::url('/purchases/pdf?id=') ?>' + encodeURIComponent(activeShoppingTask?.id || '')"
                               target="_blank"
                               class="btn btn-secondary btn-sm w-full sm:w-auto justify-center"
                               style="border-radius: 12px; font-weight: 700; font-size: 13px; padding: 9px 16px; display: inline-flex; align-items: center; gap: 7px; color:#dc2626; border-color:#fca5a5; background:#fef2f2;">
                                <i data-lucide="file-text" style="width: 15px; height: 15px;"></i>
                                <span>PDF Dokumen PO</span>
                            </a>
                        </div>

                        <!-- Kanan: Tombol Aksi Driver -->
                        <div class="flex items-center justify-end gap-2.5 flex-wrap sm:flex-nowrap">
                            <!-- Jika Masih Ditugaskan Driver -->
                            <template x-if="activeShoppingTask?.status_penerimaan === 'ditugaskan_driver'">
                                <div class="flex items-center gap-2 w-full sm:w-auto">
                                    <button type="button" 
                                            @click="openShoppingIssueInModal()"
                                            class="btn btn-secondary btn-sm flex-1 sm:flex-none justify-center"
                                            style="font-weight: 700; font-size: 13px; border-radius: 12px; padding: 9px 16px; color: #e11d48; border-color: #fecaca; background: #fff1f2; display: inline-flex; align-items: center; gap: 6px;">
                                        <i data-lucide="alert-triangle" style="width: 15px; height: 15px;"></i>
                                        <span>Lapor Kendala</span>
                                    </button>
                                    <button type="button" 
                                            @click="openShoppingCompleteInModal()"
                                            class="btn btn-primary btn-sm flex-1 sm:flex-none justify-center"
                                            style="font-weight: 800; font-size: 13px; border-radius: 12px; padding: 9px 22px; background: #d97706; border-color: #d97706; display: inline-flex; align-items: center; gap: 7px; box-shadow: 0 2px 8px rgba(217, 119, 6, 0.25);">
                                        <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
                                        <span>Selesai Belanja / Ambil</span>
                                    </button>
                                </div>
                            </template>

                            <!-- Jika Sudah Diambil Driver -->
                            <template x-if="activeShoppingTask?.status_penerimaan === 'sudah_diambil'">
                                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                                    <template x-if="activeShoppingTask?.url_foto_nota">
                                        <button type="button" 
                                                @click="openPhotoViewer(activeShoppingTask.url_foto_nota, 'Nota Belanja - ' + (activeShoppingTask?.nama_pemasok || ''))"
                                                class="btn btn-secondary btn-sm flex-1 sm:flex-none justify-center"
                                                style="border-radius: 12px; font-weight: 700; font-size: 13px; padding: 9px 16px; color: #059669; border-color: #a7f3d0; background: #ecfdf5; display: inline-flex; align-items: center; gap: 6px;">
                                            <i data-lucide="image" style="width: 15px; height: 15px;"></i>
                                            <span>Lihat Foto Nota</span>
                                        </button>
                                    </template>
                                    <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 flex items-center gap-1.5 px-3 py-2 bg-blue-50 dark:bg-blue-950/40 rounded-xl">
                                        <i data-lucide="truck" style="width: 15px; height: 15px;"></i>
                                        <span>Barang dibawa menuju gudang</span>
                                    </span>
                                </div>
                            </template>

                            <!-- Jika Selesai Diterima Gudang -->
                            <template x-if="activeShoppingTask?.status_penerimaan === 'diterima'">
                                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                                    <template x-if="activeShoppingTask?.url_foto_nota">
                                        <button type="button" 
                                                @click="openPhotoViewer(activeShoppingTask.url_foto_nota, 'Nota Belanja - ' + (activeShoppingTask?.nama_pemasok || ''))"
                                                class="btn btn-secondary btn-sm flex-1 sm:flex-none justify-center"
                                                style="border-radius: 12px; font-weight: 700; font-size: 13px; padding: 9px 16px; color: #059669; border-color: #a7f3d0; background: #ecfdf5; display: inline-flex; align-items: center; gap: 6px;">
                                            <i data-lucide="image" style="width: 15px; height: 15px;"></i>
                                            <span>Lihat Foto Nota</span>
                                        </button>
                                    </template>
                                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 px-3 py-2 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl">
                                        <i data-lucide="check-check" style="width: 15px; height: 15px;"></i>
                                        <span>Telah Diterima &amp; Dicek Gudang</span>
                                    </span>
                                </div>
                            </template>

                            <!-- Jika Kendala Batal -->
                            <template x-if="activeShoppingTask?.status_penerimaan === 'kendala_batal'">
                                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                                    <template x-if="activeShoppingTask?.foto_bukti_kendala">
                                        <button type="button" 
                                                @click="openPhotoViewer(activeShoppingTask.foto_bukti_kendala, 'Bukti Kendala Belanja - ' + (activeShoppingTask?.nama_pemasok || ''))"
                                                class="btn btn-secondary btn-sm flex-1 sm:flex-none justify-center"
                                                style="border-radius: 12px; font-weight: 700; font-size: 13px; padding: 9px 16px; color: #e11d48; border-color: #fecaca; background: #fff1f2; display: inline-flex; align-items: center; gap: 6px;">
                                            <i data-lucide="image" style="width: 15px; height: 15px;"></i>
                                            <span>Lihat Foto Kendala</span>
                                        </button>
                                    </template>
                                    <span class="text-xs font-semibold text-rose-600 dark:text-rose-400 flex items-center gap-1.5 px-3 py-2 bg-rose-50 dark:bg-rose-950/40 rounded-xl">
                                        <i data-lucide="alert-octagon" style="width: 15px; height: 15px;"></i>
                                        <span>Kendala Belanja Dilaporkan</span>
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- B. FOOTER MODE COMPLETE FORM -->
                <template x-if="shoppingViewMode === 'complete_form'">
                    <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 w-full">
                        <button type="button" @click="shoppingViewMode = 'detail'"
                                class="btn btn-secondary btn-sm flex-1 sm:flex-none justify-center"
                                style="border-radius: 12px; font-weight: 700; font-size: 13px; padding: 9px 20px; display: inline-flex; align-items: center; gap: 7px;">
                            <i data-lucide="arrow-left" style="width: 15px; height: 15px;"></i>
                            <span>Batal &amp; Kembali</span>
                        </button>

                        <button type="submit" form="completeShoppingForm"
                                class="btn btn-primary btn-sm flex-1 sm:flex-none justify-center"
                                style="font-weight: 800; font-size: 13.5px; border-radius: 12px; padding: 10px 24px; background: #d97706; border-color: #d97706; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(217, 119, 6, 0.25);">
                            <i data-lucide="check" style="width: 17px; height: 17px;"></i>
                            <span>Simpan &amp; Bawa ke Gudang</span>
                        </button>
                    </div>
                </template>

                <!-- C. FOOTER MODE ISSUE FORM -->
                <template x-if="shoppingViewMode === 'issue_form'">
                    <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 w-full">
                        <button type="button" @click="shoppingViewMode = 'detail'"
                                class="btn btn-secondary btn-sm flex-1 sm:flex-none justify-center"
                                style="border-radius: 12px; font-weight: 700; font-size: 13px; padding: 9px 20px; display: inline-flex; align-items: center; gap: 7px;">
                            <i data-lucide="arrow-left" style="width: 15px; height: 15px;"></i>
                            <span>Batal &amp; Kembali</span>
                        </button>

                        <button type="submit" form="issueShoppingForm"
                                class="btn btn-driver-fail-confirm btn-sm flex-1 sm:flex-none justify-center">
                            <i data-lucide="alert-triangle" style="width: 17px; height: 17px;"></i>
                            <span>Konfirmasi Lapor Kendala</span>
                        </button>
                    </div>
                </template>

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

        // Shopping tasks modal states
        showShoppingDetailModal: false,
        showShoppingCompleteModal: false,
        showShoppingIssueModal: false,
        activeShoppingTask: null,
        shoppingActiveTab: 'info', // 'info', 'items', 'payment'
        shoppingViewMode: 'detail', // 'detail', 'complete_form', 'issue_form'
        shoppingCompleteForm: {
            purchase_id: '',
            nomor_nota_vendor: '',
            nominal_dibayar_driver: '',
            catatan_driver: ''
        },
        shoppingCompletePhoto: null,
        shoppingCompletePreview: null,
        shoppingIssueForm: {
            purchase_id: '',
            alasan_kategori: 'Toko / Vendor Tutup',
            alasan_detail: ''
        },
        shoppingIssuePhoto: null,
        shoppingIssuePreview: null,

        get combinedShoppingIssueReason() {
            const cat = this.shoppingIssueForm.alasan_kategori || 'Kendala';
            const detail = (this.shoppingIssueForm.alasan_detail || '').trim();
            return cat + (detail ? ': ' + detail : '');
        },

        todayDate: '<?= date('Y-m-d') ?>',

        getShoppingDateInfo(task) {
            if (!task) return { isOverdue: false, isToday: false, isTomorrow: false, diffDays: 0, formattedDate: '' };
            const taskDateStr = task.tanggal_jadwal_belanja || task.tanggal_pembelian;
            if (!taskDateStr) return { isOverdue: false, isToday: false, isTomorrow: false, diffDays: 0, formattedDate: '' };

            const taskDate = new Date(taskDateStr + 'T00:00:00');
            const today = new Date(this.todayDate + 'T00:00:00');
            const diffTime = today - taskDate;
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

            const isPending = (task.status_penerimaan === 'ditugaskan_driver');
            const isOverdue = isPending && (diffDays > 0);
            const isToday = (diffDays === 0);
            const isTomorrow = (diffDays === -1);

            const parts = taskDateStr.split('-');
            const formattedDate = (parts.length === 3) ? `${parts[2]}/${parts[1]}/${parts[0]}` : taskDateStr;

            return {
                isOverdue,
                isToday,
                isTomorrow,
                diffDays,
                formattedDate,
                dateStr: taskDateStr
            };
        },

        init() {
            this.$watch('viewMode', () => {
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
            this.$watch('shoppingViewMode', () => {
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
            this.$watch('shoppingActiveTab', () => {
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
            const cleanUrl = String(url).trim();
            this.photoModalUrl = (cleanUrl.startsWith('http://') || cleanUrl.startsWith('https://')) 
                ? cleanUrl 
                : ('<?= Router::url('/') ?>' + cleanUrl.replace(/^\//, ''));
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
        },

        formatDateIndo(dateStr) {
            if (!dateStr) return '-';
            try {
                const cleanDate = dateStr.includes('T') ? dateStr.split('T')[0] : dateStr.split(' ')[0];
                const parts = cleanDate.split('-');
                if (parts.length === 3) {
                    const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });
                }
                const d = new Date(dateStr);
                return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });
            } catch (e) {
                return dateStr;
            }
        },

        openShoppingDetailModal(task) {
            this.activeShoppingTask = task;
            this.shoppingActiveTab = 'info';
            this.shoppingViewMode = 'detail';

            const isCash = (task.metode_bayar_belanja === 'tunai_driver');
            const defaultNominal = isCash
                ? (window.formatRupiahNumber ? window.formatRupiahNumber(task.total_biaya) : String(task.total_biaya || 0))
                : '0';

            this.shoppingCompleteForm = {
                purchase_id: task.id,
                nomor_nota_vendor: task.nomor_nota_vendor || '',
                nominal_dibayar_driver: defaultNominal,
                catatan_driver: ''
            };
            this.clearShoppingPhoto();

            this.shoppingIssueForm = {
                purchase_id: task.id,
                alasan_kategori: 'Toko / Vendor Tutup',
                alasan_detail: ''
            };
            this.clearShoppingIssuePhoto();

            this.showShoppingDetailModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        closeShoppingDetailModal() {
            this.showShoppingDetailModal = false;
            this.shoppingViewMode = 'detail';
        },

        openShoppingCompleteInModal() {
            this.shoppingViewMode = 'complete_form';
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        openShoppingIssueInModal() {
            this.shoppingViewMode = 'issue_form';
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        openShoppingCompleteModal(task) {
            this.openShoppingDetailModal(task);
            this.openShoppingCompleteInModal();
        },

        openShoppingIssueModal(task) {
            this.openShoppingDetailModal(task);
            this.openShoppingIssueInModal();
        },

        handleShoppingPhotoChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.compressImage(file, (compressedFile, previewDataUrl) => {
                this.shoppingCompletePhoto = compressedFile;
                this.shoppingCompletePreview = previewDataUrl;
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

        clearShoppingPhoto() {
            this.shoppingCompletePhoto = null;
            this.shoppingCompletePreview = null;
            if (this.$refs.shoppingPhotoInput) {
                this.$refs.shoppingPhotoInput.value = '';
            }
        },

        handleShoppingIssuePhotoChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.compressImage(file, (compressedFile, previewDataUrl) => {
                this.shoppingIssuePhoto = compressedFile;
                this.shoppingIssuePreview = previewDataUrl;
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

        clearShoppingIssuePhoto() {
            this.shoppingIssuePhoto = null;
            this.shoppingIssuePreview = null;
            if (this.$refs.shoppingIssuePhotoInput) {
                this.$refs.shoppingIssuePhotoInput.value = '';
            }
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
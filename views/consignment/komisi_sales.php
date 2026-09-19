<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<style>
/* ========================================================= */
/* TRACKING ALERT — sama dg customer-orders                  */
/* ========================================================= */
.tracking-alert {
    border-radius: 12px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid transparent;
}
.tracking-alert-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.tracking-alert-icon i,
.tracking-alert-icon svg {
    width: 15px;
    height: 15px;
}

/* ========================================================= */
/* TANGGA TIER KOMISI MODAL & RESPONSIVE STYLES               */
/* ========================================================= */
.modal-header-sales {
    padding: 16px 20px;
    border-bottom: 1px solid var(--color-hairline);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--color-canvas);
    flex-shrink: 0;
    gap: 12px;
}

.tier-ladder-wrapper {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

/* 1. Banner Edukasi Flat Retroaktif */
.tier-banner-box {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 14px;
    background: rgba(245, 158, 11, 0.08);
    border: 1px solid rgba(245, 158, 11, 0.25);
}
.tier-banner-icon {
    width: 32px;
    height: 32px;
    min-width: 32px;
    min-height: 32px;
    border-radius: 9px;
    background: rgba(245, 158, 11, 0.16);
    border: 1px solid rgba(245, 158, 11, 0.45);
    box-shadow: 0 1px 2px rgba(245, 158, 11, 0.15);
    color: #d97706;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}
.tier-banner-icon svg {
    width: 16px !important;
    height: 16px !important;
    max-width: 16px !important;
    max-height: 16px !important;
    display: block;
}
.tier-banner-content {
    min-width: 0;
    flex: 1;
}
.tier-banner-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 6px 10px;
    margin-bottom: 4px;
}
.tier-banner-title {
    font-size: 13px;
    font-weight: 800;
    color: var(--color-ink);
    line-height: 1.35;
}
.tier-banner-pill {
    font-size: 9.5px;
    font-weight: 800;
    padding: 2.5px 8px;
    border-radius: 99px;
    background: rgba(245, 158, 11, 0.25);
    color: #b45309;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
    flex-shrink: 0;
}
.tier-banner-desc {
    font-size: 11.5px;
    color: var(--color-ink-secondary);
    line-height: 1.55;
    margin: 0;
}

/* Tab 1, 2, 3 Info Banners with Bordered Icon Badges */
.modal-info-banner {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 12px;
}
.modal-info-banner-icon {
    width: 28px;
    height: 28px;
    min-width: 28px;
    min-height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}
.modal-info-banner-icon svg {
    width: 15px !important;
    height: 15px !important;
    max-width: 15px !important;
    max-height: 15px !important;
    display: block;
}

/* Variant Emerald (Tab 1 Konsinyasi) */
.modal-info-banner.is-emerald {
    background: rgba(16, 185, 129, 0.07);
    border: 1px solid rgba(16, 185, 129, 0.22);
}
.modal-info-banner.is-emerald .modal-info-banner-icon {
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.45);
    box-shadow: 0 1px 2px rgba(16, 185, 129, 0.12);
    color: #059669;
}

/* Variant Blue (Tab 2 B2B) */
.modal-info-banner.is-blue {
    background: rgba(59, 130, 246, 0.07);
    border: 1px solid rgba(59, 130, 246, 0.22);
}
.modal-info-banner.is-blue .modal-info-banner-icon {
    background: rgba(59, 130, 246, 0.12);
    border: 1px solid rgba(59, 130, 246, 0.45);
    box-shadow: 0 1px 2px rgba(59, 130, 246, 0.12);
    color: #2563eb;
}

/* Variant Rose (Tab 3 Belum Ditagih) */
.modal-info-banner.is-rose {
    background: rgba(239, 68, 68, 0.07);
    border: 1px solid rgba(239, 68, 68, 0.22);
}
.modal-info-banner.is-rose .modal-info-banner-icon {
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.45);
    box-shadow: 0 1px 2px rgba(239, 68, 68, 0.12);
    color: #dc2626;
}

/* 2. Hero Progress Motivation Card */
.tier-hero-card {
    padding: 16px 18px;
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(37, 99, 235, 0.02));
    border: 1px solid rgba(59, 130, 246, 0.25);
    box-shadow: 0 4px 14px -3px rgba(59, 130, 246, 0.12);
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.tier-hero-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
.tier-hero-target-group {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.tier-hero-target-icon {
    width: 38px;
    height: 38px;
    min-width: 38px;
    min-height: 38px;
    border-radius: 10px;
    background: #2563eb;
    border: 1.5px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.tier-hero-target-icon svg {
    width: 18px !important;
    height: 18px !important;
    max-width: 18px !important;
    max-height: 18px !important;
    display: block;
}
.tier-hero-label {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #2563eb;
    line-height: 1;
    margin-bottom: 3px;
}
.tier-hero-target-name {
    font-size: 14.5px;
    font-weight: 800;
    color: var(--color-ink);
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.tier-hero-rate-tag {
    font-size: 11px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 6px;
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
    white-space: nowrap;
}

.tier-hero-gap-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 99px;
    background: rgba(245, 158, 11, 0.14);
    color: #b45309;
    border: 1px solid rgba(245, 158, 11, 0.3);
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}
.tier-hero-gap-badge svg {
    width: 14px !important;
    height: 14px !important;
    max-width: 14px !important;
    max-height: 14px !important;
    color: #d97706;
    display: block;
    flex-shrink: 0;
}

/* Progress Track & Bar */
.tier-progress-wrap {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.tier-progress-track {
    width: 100%;
    height: 12px;
    border-radius: 99px;
    background: rgba(148, 163, 184, 0.22);
    overflow: hidden;
    position: relative;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.08);
}
.tier-progress-bar {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, #3b82f6, #6366f1, #10b981);
    transition: width 0.7s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 1px 4px rgba(37, 99, 235, 0.3);
}

.tier-milestone-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
}
.tier-milestone-col {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.tier-milestone-col.is-left {
    align-items: flex-start;
    text-align: left;
}
.tier-milestone-col.is-right {
    align-items: flex-end;
    text-align: right;
}
.tier-milestone-lbl {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--color-ink-mute);
    line-height: 1.2;
    margin-bottom: 2px;
    white-space: nowrap;
}
.tier-dot-current {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
    display: inline-block;
    flex-shrink: 0;
}
.tier-dot-target {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    display: inline-block;
    flex-shrink: 0;
}
.tier-milestone-num {
    font-size: 13px;
    font-weight: 800;
    font-family: var(--font-mono);
    color: var(--color-ink);
    line-height: 1.2;
    white-space: nowrap;
}
.tier-milestone-center {
    flex-shrink: 0;
}
.tier-pct-badge {
    padding: 3px 8px;
    border-radius: 6px;
    background: rgba(37, 99, 235, 0.12);
    color: #2563eb;
    font-weight: 800;
    font-size: 11px;
    white-space: nowrap;
    display: inline-block;
}

.tier-hero-note {
    padding-top: 10px;
    border-top: 1px solid rgba(59, 130, 246, 0.15);
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 11.5px;
    line-height: 1.55;
    color: var(--color-ink-secondary);
}
.tier-hero-note-icon {
    width: 22px;
    height: 22px;
    min-width: 22px;
    min-height: 22px;
    border-radius: 6px;
    background: rgba(245, 158, 11, 0.14);
    border: 1px solid rgba(245, 158, 11, 0.4);
    box-shadow: 0 1px 2px rgba(245, 158, 11, 0.1);
    color: #d97706;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}
.tier-hero-note-icon svg {
    width: 12px !important;
    height: 12px !important;
    max-width: 12px !important;
    max-height: 12px !important;
    flex-shrink: 0;
    display: block;
}

/* Tier Max Celebration Card */
.tier-max-card {
    padding: 14px 16px;
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.12), rgba(234, 179, 8, 0.04));
    border: 1px solid rgba(245, 158, 11, 0.35);
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.tier-max-trophy {
    width: 40px;
    height: 40px;
    min-width: 40px;
    min-height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #f59e0b, #eab308);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
    flex-shrink: 0;
    margin-top: 2px;
}

/* 3. Tier Grid (2 columns on tablet/desktop, 1 column on mobile) */
.tier-cards-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

/* Tier Card Block */
.tier-card-block {
    border-radius: 16px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.2s ease;
    border: 1px solid var(--color-hairline);
    background: var(--color-canvas);
    position: relative;
    overflow: hidden;
    gap: 10px;
}
.tier-card-block.is-active-tier {
    background: rgba(245, 158, 11, 0.08);
    border: 2px solid rgba(245, 158, 11, 0.65);
    box-shadow: 0 6px 20px -4px rgba(245, 158, 11, 0.22);
}
.tier-card-block.is-next-tier {
    background: rgba(59, 130, 246, 0.05);
    border: 2px solid rgba(59, 130, 246, 0.5);
    box-shadow: 0 4px 16px -4px rgba(59, 130, 246, 0.14);
}
.tier-card-block.is-passed-tier {
    background: rgba(16, 185, 129, 0.04);
    border: 1px solid rgba(16, 185, 129, 0.3);
}
.tier-card-block.is-locked-tier {
    background: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    opacity: 0.85;
}

.tier-card-top-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 6px;
}
.tier-card-title-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}
.tier-card-medal {
    font-size: 18px;
    line-height: 1;
    flex-shrink: 0;
}
.tier-card-heading {
    font-size: 14.5px;
    font-weight: 800;
    color: var(--color-ink);
    letter-spacing: -0.01em;
}
.tier-card-block.is-active-tier .tier-card-heading {
    color: #b45309;
}
.tier-card-block.is-next-tier .tier-card-heading {
    color: #1d4ed8;
}

.tier-card-rate-box {
    text-align: right;
    flex-shrink: 0;
}
.tier-card-rate-num {
    font-size: 20px;
    font-weight: 900;
    font-family: var(--font-mono);
    line-height: 1;
    color: var(--color-ink-mute);
}
.tier-card-block.is-active-tier .tier-card-rate-num {
    color: #d97706;
}
.tier-card-block.is-next-tier .tier-card-rate-num {
    color: #2563eb;
}
.tier-card-rate-lbl {
    font-size: 8.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--color-ink-mute);
    margin-top: 2px;
}

.tier-card-pill-row {
    margin-bottom: 8px;
}
.tier-card-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 9.5px;
    font-weight: 800;
    padding: 3px 9px;
    border-radius: 99px;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    white-space: nowrap;
}
.tier-card-status-pill.is-active {
    background: #f59e0b;
    color: #ffffff;
}
.tier-card-status-pill.is-next {
    background: #2563eb;
    color: #ffffff;
}
.tier-card-status-pill.is-passed {
    background: rgba(16, 185, 129, 0.15);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.3);
}
.tier-card-status-pill.is-locked {
    background: rgba(148, 163, 184, 0.18);
    color: var(--color-ink-mute);
    border: 1px solid var(--color-hairline);
}
.tier-card-status-pill svg {
    width: 11px !important;
    height: 11px !important;
    max-width: 11px !important;
    max-height: 11px !important;
    display: block;
    flex-shrink: 0;
}

.tier-card-range-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 9px;
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    font-size: 11px;
    font-family: var(--font-mono);
    font-weight: 600;
    color: var(--color-ink-secondary);
    align-self: flex-start;
    max-width: 100%;
    box-sizing: border-box;
    flex-wrap: wrap;
}
.tier-card-range-badge svg {
    width: 13px !important;
    height: 13px !important;
    max-width: 13px !important;
    max-height: 13px !important;
    flex-shrink: 0;
    display: block;
}
.tier-card-block.is-active-tier .tier-card-range-badge {
    border-color: rgba(245, 158, 11, 0.35);
    color: #92400e;
}
.tier-card-block.is-next-tier .tier-card-range-badge {
    border-color: rgba(59, 130, 246, 0.35);
    color: #1e40af;
}

.tier-card-bottom-row {
    margin-top: 10px;
    padding-top: 9px;
    border-top: 1px solid var(--color-hairline);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 4px;
    font-size: 11px;
    color: var(--color-ink-mute);
}
.tier-card-bottom-val {
    font-family: var(--font-mono);
    font-weight: 800;
    font-size: 12px;
}

/* ========================================================= */
/* MOBILE RESPONSIVE OVERRIDES (HP VIEW)                      */
/* ========================================================= */
@media (max-width: 640px) {
    .modal-header-sales {
        padding: 12px 14px;
        gap: 8px;
    }
    .modal-tab-nav {
        padding: 6px 10px;
        gap: 4px;
        -webkit-overflow-scrolling: touch;
    }
    .modal-tab-btn {
        padding: 6px 10px;
        font-size: 11px;
        gap: 5px;
        flex-shrink: 0;
    }
    .modal-tab-btn i,
    .modal-tab-btn svg {
        width: 13px !important;
        height: 13px !important;
    }
    .modal-tab-body {
        padding: 12px 12px 24px 12px;
    }
    .tier-cards-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .tier-banner-box {
        padding: 11px 13px;
        gap: 10px;
    }
    .tier-hero-card {
        padding: 13px 13px;
        gap: 11px;
    }
    .tier-hero-head {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    .tier-hero-gap-badge {
        width: 100%;
        justify-content: center;
        text-align: center;
        padding: 7px 12px;
        border-radius: 10px;
        box-sizing: border-box;
    }
    .tier-milestone-num {
        font-size: 12px;
    }
    .tier-pct-badge {
        font-size: 10.5px;
        padding: 2px 7px;
    }
    .tier-card-block {
        padding: 12px 14px;
    }
}
</style>

<div x-data="komisiApp()" x-init="init()" class="space-y-5 pb-20">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-amber">
                <i data-lucide="percent"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#f59e0b;"></span>
                    <span><?= $isSalesLocked ? 'Modul Konsinyasi • Komisi Penjualan Saya' : 'Modul Konsinyasi • Rekap Seluruh Sales Lapangan' ?></span>
                </div>
                <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Rekap Komisi Sales') ?></h1>
                <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle ?? 'Perhitungan insentif komisi bulanan berdasarkan omzet laku toko binaan tetap.') ?></p>
            </div>
        </div>
        <div class="page-header-actions">
            <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary">
                <i data-lucide="arrow-left"></i>
                <span>Kembali ke Portal</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- ALERT: AKUN SALES BELUM DITAUTKAN KE MASTER KARYAWAN                      -->
    <!-- ========================================================================= -->
    <?php if ($unlinkedAccount): ?>
    <div class="card p-4 rounded-2xl flex items-start gap-3.5" style="background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.25);">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(239,68,68,0.12);color:#ef4444;">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        </div>
        <div>
            <div class="text-xs sm:text-sm font-bold text-rose-600">Akun Belum Ditautkan ke Master Karyawan</div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Akun pengguna Anda belum terhubung dengan data karyawan sales di Master Pengguna. Silakan hubungi Administrator atau Owner untuk menautkan akun Anda agar data komisi dapat dihitung dan ditampilkan otomatis.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- TOP STATS CARDS (4-COLUMN REALIZED CASH & RECEIVABLE ISOLATION)           -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Omzet Komisi Terbayar -->
        <div class="stat-card">
            <div class="flex items-center justify-between mb-2.5">
                <span class="stat-card-label" style="margin-bottom:0;letter-spacing:0.04em;">
                    <?= $isSalesLocked ? 'Omzet Saya (Terbayar)' : 'Total Omzet Terbayar' ?>
                </span>
                <div class="stat-card-icon" style="background:rgba(16,185,129,0.12);color:#10b981;width:38px;height:38px;border-radius:10px;">
                    <i data-lucide="trending-up" style="width:19px;height:19px;"></i>
                </div>
            </div>
            <div class="stat-card-value" style="color:#10b981;font-size:1.55rem;line-height:1.2;">
                <?= Format::rupiah((float)$grandOmzet) ?>
            </div>
            <div class="stat-card-footer" style="margin-top:10px;padding-top:8px;border-top:1px solid var(--color-hairline);">
                <i data-lucide="check-circle" style="width:13px;height:13px;color:#10b981;flex-shrink:0;"></i>
                <span style="font-size:11px;color:var(--color-ink-mute);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    Konsin: <?= Format::rupiah((float)$grandKonsinTerbayar) ?> &bull; B2B: <?= Format::rupiah((float)$grandB2bTerbayar) ?>
                </span>
            </div>
        </div>

        <!-- Card 2: Estimasi Komisi Bertingkat -->
        <div class="stat-card" style="border-color:rgba(245,158,11,0.35);">
            <div class="flex items-center justify-between mb-2.5">
                <span class="stat-card-label" style="margin-bottom:0;color:#d97706;letter-spacing:0.04em;">
                    <?= $isSalesLocked ? 'Estimasi Komisi Saya' : 'Total Alokasi Komisi' ?>
                </span>
                <div class="stat-card-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b;width:38px;height:38px;border-radius:10px;">
                    <i data-lucide="award" style="width:19px;height:19px;"></i>
                </div>
            </div>
            <div class="stat-card-value" style="color:#f59e0b;font-size:1.55rem;line-height:1.2;">
                <?= Format::rupiah((float)$grandKomisi) ?>
            </div>
            <div class="stat-card-footer" style="margin-top:10px;padding-top:8px;border-top:1px solid var(--color-hairline);">
                <i data-lucide="percent" style="width:13px;height:13px;color:#d97706;flex-shrink:0;"></i>
                <span style="font-size:11px;color:var(--color-ink-mute);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    Dihitung otomatis via Skema Bertingkat
                </span>
            </div>
        </div>

        <!-- Card 3: Piutang Toko Tertunda (Belum Masuk Omzet) -->
        <div class="stat-card" style="border-color:rgba(239,68,68,0.25);">
            <div class="flex items-center justify-between mb-2.5">
                <span class="stat-card-label" style="margin-bottom:0;color:#ef4444;letter-spacing:0.04em;">
                    Piutang Pending (Non-Omzet)
                </span>
                <div class="stat-card-icon" style="background:rgba(239,68,68,0.12);color:#ef4444;width:38px;height:38px;border-radius:10px;">
                    <i data-lucide="clock" style="width:19px;height:19px;"></i>
                </div>
            </div>
            <div class="stat-card-value" style="color:#ef4444;font-size:1.55rem;line-height:1.2;">
                <?= Format::rupiah((float)$grandPiutangPending) ?>
            </div>
            <div class="stat-card-footer" style="margin-top:10px;padding-top:8px;border-top:1px solid var(--color-hairline);">
                <i data-lucide="alert-circle" style="width:13px;height:13px;color:#ef4444;flex-shrink:0;"></i>
                <span style="font-size:11px;color:var(--color-ink-mute);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    Hutang toko (masuk omzet setelah lunas)
                </span>
            </div>
        </div>

        <!-- Card 4: Toko Binaan Terlibat -->
        <div class="stat-card">
            <div class="flex items-center justify-between mb-2.5">
                <span class="stat-card-label" style="margin-bottom:0;letter-spacing:0.04em;">
                    <?= $isSalesLocked ? 'Toko Binaan Saya' : 'Total Toko Binaan' ?>
                </span>
                <div class="stat-card-icon" style="background:rgba(59,130,246,0.12);color:#3b82f6;width:38px;height:38px;border-radius:10px;">
                    <i data-lucide="store" style="width:19px;height:19px;"></i>
                </div>
            </div>
            <div class="stat-card-value" style="color:#3b82f6;font-size:1.55rem;line-height:1.2;">
                <?= (int)$totalStoresInvolved ?> <span style="font-size:13px;font-weight:600;color:var(--color-ink-mute);">Toko</span>
            </div>
            <div class="stat-card-footer" style="margin-top:10px;padding-top:8px;border-top:1px solid var(--color-hairline);">
                <i data-lucide="check-circle-2" style="width:13px;height:13px;color:#3b82f6;flex-shrink:0;"></i>
                <span style="font-size:11px;color:var(--color-ink-mute);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    Terdaftar sebagai penanggung jawab
                </span>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- DEDICATED FILTER CARD (FLEKSIBEL TANGGAL & PRESETS CEPAT)                 -->
    <!-- ========================================================================= -->
    <div class="card p-4 sm:p-5 rounded-2xl space-y-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
        
        <!-- Bar Atas: Pilihan Periode Cepat (Presets) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="padding-bottom: 16px; margin-bottom: 16px; border-bottom: 1px solid var(--color-hairline);">
            <div class="flex items-center gap-2 text-xs font-bold" style="color:var(--color-ink-secondary);">
                <i data-lucide="calendar" class="w-4 h-4 text-amber-500"></i>
                <span>PILIHAN PERIODE CEPAT:</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" 
                        @click="setQuickPeriod('this_month')" 
                        :class="isActivePreset('this_month') ? 'btn btn-primary' : 'btn btn-secondary'"
                        style="font-size:11.5px;padding:5px 12px;height:32px;border-radius:8px;">
                    Bulan Ini (Default)
                </button>
                <button type="button" 
                        @click="setQuickPeriod('last_month')" 
                        :class="isActivePreset('last_month') ? 'btn btn-primary' : 'btn btn-secondary'"
                        style="font-size:11.5px;padding:5px 12px;height:32px;border-radius:8px;">
                    Bulan Lalu
                </button>
                <button type="button" 
                        @click="setQuickPeriod('today')" 
                        :class="isActivePreset('today') ? 'btn btn-primary' : 'btn btn-secondary'"
                        style="font-size:11.5px;padding:5px 12px;height:32px;border-radius:8px;">
                    Hari Ini
                </button>
                <button type="button" 
                        @click="setQuickPeriod('this_week')" 
                        :class="isActivePreset('this_week') ? 'btn btn-primary' : 'btn btn-secondary'"
                        style="font-size:11.5px;padding:5px 12px;height:32px;border-radius:8px;">
                    Minggu Ini
                </button>
                <button type="button" 
                        @click="setQuickPeriod('last_30_days')" 
                        :class="isActivePreset('last_30_days') ? 'btn btn-primary' : 'btn btn-secondary'"
                        style="font-size:11.5px;padding:5px 12px;height:32px;border-radius:8px;">
                    30 Hari Terakhir
                </button>
            </div>
        </div>

        <!-- Form Kontrol: Grid Responsif 4 Kolom -->
        <form x-ref="filterForm" method="GET" action="<?= Router::url('/consignment/komisi-sales') ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 items-end">
                
                <!-- Field 1: Dari Tanggal -->
                <div>
                    <label class="form-label text-xs font-bold" style="color:var(--color-ink-secondary);margin-bottom:6px;">
                        Dari Tanggal
                    </label>
                    <input type="date" 
                           name="start_date" 
                           x-model="filterStartDate" 
                           class="form-input text-xs" 
                           style="height:38px;">
                </div>

                <!-- Field 2: Sampai Tanggal -->
                <div>
                    <label class="form-label text-xs font-bold" style="color:var(--color-ink-secondary);margin-bottom:6px;">
                        Sampai Tanggal
                    </label>
                    <input type="date" 
                           name="end_date" 
                           x-model="filterEndDate" 
                           class="form-input text-xs" 
                           style="height:38px;">
                </div>

                <!-- Field 3: Filter Sales Lapangan -->
                <div>
                    <?php if ($canViewAll): ?>
                        <label class="form-label text-xs font-bold" style="color:var(--color-ink-secondary);margin-bottom:6px;">
                            Filter Sales Lapangan
                        </label>
                        <select name="sales_id" class="form-select text-xs" style="height:38px;">
                            <option value="">-- Semua Sales Lapangan --</option>
                            <?php foreach ($salesOptions as $so): ?>
                            <option value="<?= $so['id'] ?>" <?= $selectedSalesId === $so['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($so['nama_karyawan']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <label class="form-label text-xs font-bold" style="color:var(--color-ink-secondary);margin-bottom:6px;">
                            Sales Lapangan (Terkunci)
                        </label>
                        <div class="flex items-center justify-between px-3 rounded-lg border bg-slate-500/5 text-xs font-bold" 
                             style="height:38px;border-color:var(--color-hairline);color:var(--color-ink);" 
                             title="Filter otomatis terkunci ke akun sales Anda">
                            <div class="flex items-center gap-1.5 truncate">
                                <i data-lucide="lock" style="width:13px;height:13px;color:#f59e0b;flex-shrink:0;"></i>
                                <span class="truncate"><?= htmlspecialchars($currentSalesName) ?></span>
                            </div>
                            <span class="badge badge-warning text-[9px] py-0.5 px-1.5 flex-shrink-0">Terkunci</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Field 4: Tombol Aksi Terapkan & Reset -->
                <div class="flex items-center gap-2">
                    <button type="submit" 
                            class="btn btn-primary flex-1 text-xs font-bold" 
                            style="height:38px;background:#3b82f6;border-color:#3b82f6;color:#ffffff;">
                        <i data-lucide="filter" style="width:14px;height:14px;"></i>
                        <span>Terapkan</span>
                    </button>

                    <?php if (!empty($selectedSalesId) && $canViewAll): ?>
                        <a href="<?= Router::url('/consignment/komisi-sales?start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate)) ?>" 
                           class="btn btn-secondary text-xs" 
                           style="height:38px;padding:0 12px;" 
                           title="Reset Filter Sales">
                            <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i>
                            <span>Reset</span>
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </form>

        <!-- Bar Keterangan Rentang Aktif -->
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs" style="padding-top:14px; margin-top:16px; border-top:1px solid var(--color-hairline); color:var(--color-ink-mute);">
            <div class="flex items-center gap-1.5">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-500 flex-shrink-0"></i>
                <span>Periode Transaksi: <strong style="color:var(--color-ink);"><?= date('d/m/Y', strtotime($startDate)) ?></strong> s/d <strong style="color:var(--color-ink);"><?= date('d/m/Y', strtotime($endDate)) ?></strong></span>
            </div>
            <div style="font-size:11.5px;">
                Ditemukan <strong style="color:var(--color-ink);"><?= count($commissions) ?></strong> Sales Lapangan
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TABEL REKAPITULASI KOMISI SALES BERTINGKAT                                -->
    <!-- ========================================================================= -->
    <div class="card p-0 rounded-2xl overflow-hidden" style="border:1px solid var(--color-hairline);">
        <div class="table-responsive">
            <table class="data-table" style="min-width: 960px;">
                <thead>
                    <tr>
                        <th style="min-width:210px;">Sales Lapangan</th>
                        <th class="cell-center cell-nowrap" style="width:110px;">Toko Binaan</th>
                        <th class="cell-right cell-nowrap" style="min-width:150px;">Konsinyasi (Terbayar)</th>
                        <th class="cell-right cell-nowrap" style="min-width:140px;">B2B (Terbayar)</th>
                        <th class="cell-right cell-nowrap" style="min-width:160px;">Total Omzet Komisi</th>
                        <th class="cell-center cell-nowrap" style="min-width:240px;width:250px;">Tier &amp; Rate Komisi</th>
                        <th class="cell-right cell-nowrap" style="min-width:160px;">Estimasi Komisi (Rp)</th>
                        <th class="cell-center cell-nowrap" style="width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($commissions)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:56px 20px;color:var(--color-ink-mute);">
                                <div class="w-12 h-12 rounded-2xl mx-auto mb-3 flex items-center justify-center" style="background:rgba(245,158,11,0.12);color:#f59e0b;">
                                    <i data-lucide="percent" style="width:24px;height:24px;"></i>
                                </div>
                                <div style="font-weight:700;color:var(--color-ink);font-size:14px;">Belum Ada Transaksi Penjualan yang Terbayar</div>
                                <div style="font-size:12px;margin-top:4px;max-width:440px;margin-left:auto;margin-right:auto;line-height:1.5;">
                                    Belum ada faktur tagihan konsinyasi atau pesanan B2B toko binaan yang memiliki pembayaran tercatat pada rentang tanggal terpilih.
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($commissions as $c): ?>
                        <tr>
                            <!-- Sales Lapangan Info -->
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div style="width:38px;height:38px;border-radius:50%;background:rgba(245,158,11,0.15);color:#d97706;font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(245,158,11,0.3);">
                                        <?= strtoupper(substr($c['nama_karyawan'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;color:var(--color-ink);font-size:13px;"><?= htmlspecialchars($c['nama_karyawan']) ?></div>
                                        <div style="display:flex;align-items:center;gap:6px;margin-top:2px;">
                                            <span class="badge" style="background:rgba(245,158,11,0.1);color:#d97706;font-size:9px;padding:1px 5px;">
                                                <?= htmlspecialchars(strtoupper($c['posisi'] ?? 'SALES')) ?>
                                            </span>
                                            <?php if (!empty($c['nomor_telepon'])): ?>
                                                <span class="font-mono text-[10.5px] flex items-center gap-1" style="color:var(--color-ink-mute);">
                                                    <i data-lucide="phone" style="width:10px;height:10px;"></i>
                                                    <span><?= htmlspecialchars($c['nomor_telepon']) ?></span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Jumlah Toko Binaan Tetap -->
                            <td class="cell-center cell-nowrap">
                                <?php if ((int)$c['total_toko_assigned'] > 0): ?>
                                    <span class="badge badge-success" style="font-weight:700;font-size:11px;padding:3px 8px;">
                                        <i data-lucide="store" style="width:12px;height:12px;"></i>
                                        <span><?= (int)$c['total_toko_assigned'] ?> Toko</span>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning" style="font-size:10px;padding:2px 7px;">
                                        0 Toko
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Omzet Konsinyasi Terbayar -->
                            <td class="cell-right cell-nowrap cell-currency font-mono text-xs" style="color:var(--color-ink);">
                                <div><?= Format::rupiah((float)$c['omzet_konsinyasi_terbayar']) ?></div>
                                <div class="text-[10px] text-slate-400 font-sans"><?= (int)$c['jumlah_faktur_konsin'] ?> Faktur Terbit</div>
                            </td>

                            <!-- Omzet B2B Terbayar -->
                            <td class="cell-right cell-nowrap cell-currency font-mono text-xs" style="color:var(--color-ink);">
                                <div><?= Format::rupiah((float)$c['omzet_b2b_terbayar']) ?></div>
                                <div class="text-[10px] text-slate-400 font-sans"><?= (int)$c['jumlah_faktur_b2b'] ?> Faktur B2B</div>
                            </td>

                            <!-- Total Omzet Komisi -->
                            <td class="cell-right cell-nowrap cell-currency font-black" style="font-size:13px;color:var(--color-ink);">
                                <div><?= Format::rupiah((float)$c['total_omzet']) ?></div>
                                <?php if ((float)$c['total_piutang_pending'] > 0): ?>
                                    <div class="text-[10px] text-rose-500 font-sans" title="Hutang toko yang belum dilunasi, tidak masuk omzet">
                                        +<?= Format::rupiah((float)$c['total_piutang_pending']) ?> pending
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Tier & Rate Komisi (Modern Compact Card & Progress Widget) -->
                            <?php
                            $curOmzet = (float)($c['total_omzet'] ?? 0);
                            $hasNextTier = !empty($c['tier_info']['has_next_tier']);
                            $gapOmzet = (float)($c['tier_info']['gap_omzet_ke_next_tier'] ?? 0);
                            $targetOmzet = $curOmzet + $gapOmzet;
                            $tierUrutan = (int)($c['tier_info']['urutan'] ?? 1);
                            $tierProgress = ($targetOmzet > 0) ? min(100, max(0, round(($curOmzet / $targetOmzet) * 100))) : 100;

                            // Dynamic Palette & Icons berdasarkan Level Tier
                            if ($tierUrutan <= 1) {
                                $tierBadgeBg = 'rgba(245, 158, 11, 0.10)';
                                $tierBadgeBorder = 'rgba(245, 158, 11, 0.28)';
                                $tierBadgeText = '#d97706';
                                $rateBg = '#f59e0b';
                                $rateText = '#0f172a';
                                $barGrad = 'linear-gradient(90deg, #f59e0b, #d97706)';
                                $tierIcon = 'award';
                            } elseif ($tierUrutan === 2) {
                                $tierBadgeBg = 'rgba(59, 130, 246, 0.10)';
                                $tierBadgeBorder = 'rgba(59, 130, 246, 0.28)';
                                $tierBadgeText = '#2563eb';
                                $rateBg = '#3b82f6';
                                $rateText = '#ffffff';
                                $barGrad = 'linear-gradient(90deg, #60a5fa, #2563eb)';
                                $tierIcon = 'shield';
                            } elseif ($tierUrutan === 3) {
                                $tierBadgeBg = 'rgba(139, 92, 246, 0.10)';
                                $tierBadgeBorder = 'rgba(139, 92, 246, 0.28)';
                                $tierBadgeText = '#7c3aed';
                                $rateBg = '#8b5cf6';
                                $rateText = '#ffffff';
                                $barGrad = 'linear-gradient(90deg, #a78bfa, #7c3aed)';
                                $tierIcon = 'zap';
                            } else {
                                $tierBadgeBg = 'rgba(16, 185, 129, 0.10)';
                                $tierBadgeBorder = 'rgba(16, 185, 129, 0.28)';
                                $tierBadgeText = '#059669';
                                $rateBg = '#10b981';
                                $rateText = '#ffffff';
                                $barGrad = 'linear-gradient(90deg, #34d399, #059669)';
                                $tierIcon = 'crown';
                            }
                            ?>
                            <td class="cell-center" style="padding: 10px 12px; vertical-align: middle;">
                                <div class="flex flex-col mx-auto text-left cursor-pointer transition hover:opacity-90 active:scale-[0.98]" 
                                     @click="openBreakdownModal(<?= htmlspecialchars(json_encode($c)) ?>, 'ladder')"
                                     title="Klik untuk melihat tangga tier komisi"
                                     style="max-width: 230px; border-radius: 10px; overflow: hidden; border: 1px solid <?= $tierBadgeBorder ?>; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                                    
                                    <!-- Bagian Atas: Nama Tier + Rate -->
                                    <div class="flex items-center justify-between gap-2"
                                         style="padding: 7px 10px; background: <?= $tierBadgeBg ?>;">
                                        <!-- Nama Tier -->
                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold truncate"
                                              style="color: <?= $tierBadgeText ?>;">
                                            <i data-lucide="<?= $tierIcon ?>" style="width: 12px; height: 12px; flex-shrink: 0;"></i>
                                            <span class="truncate" title="<?= htmlspecialchars($c['tier_info']['nama_tier'] ?? 'Tier 1') ?>">
                                                <?= htmlspecialchars($c['tier_info']['nama_tier'] ?? 'Tier 1') ?>
                                            </span>
                                        </span>
                                        <!-- Rate Badge — lebih elegan, pill shape -->
                                        <span class="inline-flex items-center rounded-full text-[11px] font-black tracking-tight flex-shrink-0"
                                              style="padding: 2px 9px; background: <?= $rateBg ?>; color: <?= $rateText ?>; letter-spacing: 0.01em;">
                                            <?= number_format((float)$c['persentase_komisi'], 2) ?>%
                                        </span>
                                    </div>

                                    <!-- Bagian Bawah: Progress & Next Tier -->
                                    <div class="flex flex-col gap-1.5"
                                         style="padding: 7px 10px; background: var(--color-canvas, #fff);">

                                        <!-- Progress Bar — lebih tebal & kontras -->
                                        <div class="w-full rounded-full overflow-hidden" style="height: 5px; background: rgba(148, 163, 184, 0.20);">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                 style="width: <?= $tierProgress ?>%; background: <?= $barGrad ?>;"></div>
                                        </div>

                                        <!-- Next Tier Info / Tier Tertinggi -->
                                        <?php if ($hasNextTier): ?>
                                            <div class="flex items-center justify-between gap-1" style="min-width: 0;">
                                                <span class="flex items-center gap-1 text-[10px] font-medium truncate"
                                                      style="color: var(--color-ink-mute);"
                                                      title="Ke <?= htmlspecialchars($c['tier_info']['next_tier_nama']) ?> (<?= (float)$c['tier_info']['next_tier_persentase'] ?>%)">
                                                    <i data-lucide="trending-up" style="width: 10px; height: 10px; color: #3b82f6; flex-shrink: 0;"></i>
                                                    <span class="truncate">Ke <strong style="color: var(--color-ink);"><?= htmlspecialchars($c['tier_info']['next_tier_nama']) ?></strong></span>
                                                </span>
                                                <span class="font-mono font-bold text-[10px] whitespace-nowrap flex-shrink-0"
                                                      style="color: #3b82f6;" title="Kurang omzet untuk naik tier">
                                                    -<?= Format::rupiah($gapOmzet) ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <div class="flex items-center justify-between gap-1">
                                                <span class="flex items-center gap-1 text-[10px] font-bold" style="color: #059669;">
                                                    <i data-lucide="sparkles" style="width: 10px; height: 10px; flex-shrink: 0;"></i>
                                                    <span>Tier Tertinggi</span>
                                                </span>
                                                <span class="text-[9px] font-bold uppercase tracking-wider rounded-full"
                                                      style="padding: 1px 7px; background: rgba(16,185,129,0.12); color: #059669;">
                                                    Maks
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                    </div>
                                </div>
                            </td>

                            <!-- Estimasi Nominal Komisi -->
                            <td class="cell-right cell-nowrap cell-currency font-black" style="color:#d97706;font-size:13.5px;">
                                <?= Format::rupiah((float)$c['nominal_komisi']) ?>
                            </td>

                            <!-- Tombol Aksi: Rincian Toko -->
                            <td class="cell-center cell-nowrap">
                                <button type="button" 
                                        @click="openBreakdownModal(<?= htmlspecialchars(json_encode($c)) ?>)"
                                        class="btn btn-secondary btn-sm"
                                        style="height:32px;font-size:11px;padding:0 10px;font-weight:600;"
                                        title="Lihat rincian faktur dan tier">
                                    <i data-lucide="layers" style="width:13px;height:13px;color:#d97706;"></i>
                                    <span>Rincian</span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($commissions)): ?>
                <tfoot>
                    <tr class="font-bold text-xs" style="background:var(--color-canvas);border-top:2px solid var(--color-hairline);">
                        <td colspan="2" style="padding:13px 18px;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.05em;">
                            TOTAL KESELURUHAN:
                        </td>
                        <td class="cell-right cell-currency font-mono" style="padding:13px 14px;color:var(--color-ink);font-size:12px;">
                            <?= Format::rupiah((float)$grandKonsinTerbayar) ?>
                        </td>
                        <td class="cell-right cell-currency font-mono" style="padding:13px 14px;color:var(--color-ink);font-size:12px;">
                            <?= Format::rupiah((float)$grandB2bTerbayar) ?>
                        </td>
                        <td class="cell-right cell-currency font-black" style="padding:13px 18px;color:var(--color-ink);font-size:13.5px;">
                            <?= Format::rupiah((float)$grandOmzet) ?>
                        </td>
                        <td class="cell-center" style="padding:13px 18px;color:var(--color-ink-mute);">—</td>
                        <td class="cell-right cell-currency font-black text-amber-500" style="padding:13px 18px;font-size:14.5px;">
                            <?= Format::rupiah((float)$grandKomisi) ?>
                        </td>
                        <td class="cell-center" style="padding:13px 18px;color:var(--color-ink-mute);">—</td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: RINCIAN FAKTUR, OMZET TERBAYAR, SISA HUTANG & TIER (TELEPORTED)    -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="showBreakdownModal"
             x-cloak
             class="modal-backdrop"
            
             @keydown.escape.window="showBreakdownModal = false">

            <div class="modal-box modal-box-lg" @click.stop>

                <!-- MOBILE PULL HANDLE -->
                <div class="sm:hidden w-full flex justify-center pt-3 pb-1 flex-shrink-0" style="background:var(--color-canvas);">
                    <div style="width:40px;height:4px;border-radius:2px;background:var(--color-hairline-strong);"></div>
                </div>

                <!-- ============================================================ -->
                <!-- 1. MODAL HEADER                                               -->
                <!-- ============================================================ -->
                <div class="modal-header-sales">
                    <!-- Kiri: Icon + Judul + Meta -->
                    <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">
                        <div style="width:38px;height:38px;border-radius:11px;background:rgba(245,158,11,0.12);color:#d97706;border:1px solid rgba(245,158,11,0.25);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="award" style="width:19px;height:19px;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                <span class="font-black" style="font-size:14.5px;color:var(--color-ink);letter-spacing:-0.01em;" x-text="activeSales.nama_karyawan"></span>
                                <!-- Tier badge -->
                                <span class="badge" style="background:#fffbeb;color:#b45309;border:1px solid #fde68a;font-weight:800;font-size:10px;padding:2px 7px;border-radius:6px;display:inline-flex;align-items:center;gap:4px;white-space:nowrap;">
                                    <i data-lucide="award" style="width:10px;height:10px;color:#d97706;"></i>
                                    <span x-text="activeSales.tier_info?.nama_tier || 'Tier 1'"></span>
                                </span>
                                <!-- Rate badge -->
                                <span class="badge" style="background:#f59e0b;color:#0f172a;font-weight:900;font-size:10px;padding:2px 7px;border-radius:6px;white-space:nowrap;">
                                    <span x-text="Number(activeSales.persentase_komisi || 0).toFixed(2) + '%'"></span>
                                </span>
                            </div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;display:flex;align-items:center;gap:4px;">
                                <i data-lucide="calendar" style="width:11px;height:11px;flex-shrink:0;"></i>
                                <span x-text="formatDateIndo(filterStartDate) + ' s/d ' + formatDateIndo(filterEndDate)"></span>
                            </div>
                        </div>
                    </div>
                    <!-- Kanan: Stat chips + Tombol X -->
                    <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                        <!-- Stat chips: hidden on mobile, show md+ -->
                        <div class="hidden md:flex items-center gap-2.5">
                            <!-- Omzet chip -->
                            <div style="display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:10px;background:rgba(16,185,129,0.09);border:1px solid rgba(16,185,129,0.25);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;max-width:15px;max-height:15px;flex-shrink:0;"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                                <div>
                                    <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#10b981;line-height:1;margin-bottom:2px;">Omzet</div>
                                    <div class="font-mono font-black" style="font-size:13.5px;color:#059669;line-height:1.2;" x-text="formatRupiahClean(activeSales.total_omzet)"></div>
                                </div>
                            </div>
                            <!-- Estimasi komisi chip -->
                            <div style="display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:10px;background:rgba(245,158,11,0.09);border:1px solid rgba(245,158,11,0.28);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;max-width:15px;max-height:15px;flex-shrink:0;"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                                <div>
                                    <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#d97706;line-height:1;margin-bottom:2px;">Komisi</div>
                                    <div class="font-mono font-black" style="font-size:13.5px;color:#d97706;line-height:1.2;" x-text="formatRupiahClean(activeSales.nominal_komisi)"></div>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="showBreakdownModal = false"
                                class="btn btn-ghost btn-sm"
                                style="width:34px;height:34px;padding:0;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--color-ink-mute);"
                                aria-label="Tutup">
                            <i data-lucide="x" style="width:18px;height:18px;"></i>
                        </button>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- 2. TAB NAVIGATION (pakai sistem yg sama dg customer-orders)  -->
                <!-- ============================================================ -->
                <div class="modal-tab-nav custom-scrollbar" x-ref="tabNav">
                    <button type="button" @click="selectTab('konsin', $event)" class="modal-tab-btn" :class="{ 'is-active': activeTab === 'konsin' }">
                        <i data-lucide="store" style="width:14px;height:14px;"></i>
                        <span><span class="hidden sm:inline">Tagihan </span>Konsinyasi</span>
                        <span class="badge" style="font-size:10px;padding:1px 6px;border-radius:10px;" x-text="(currentKonsinInvoices || []).length"></span>
                    </button>
                    <button type="button" @click="selectTab('b2b', $event)" class="modal-tab-btn" :class="{ 'is-active': activeTab === 'b2b' }">
                        <i data-lucide="shopping-bag" style="width:14px;height:14px;"></i>
                        <span>Pesanan B2B</span>
                        <span class="badge" style="font-size:10px;padding:1px 6px;border-radius:10px;" x-text="(currentB2bOrders || []).length"></span>
                    </button>
                    <button type="button" @click="selectTab('unbilled', $event)" class="modal-tab-btn" :class="{ 'is-active': activeTab === 'unbilled' }">
                        <i data-lucide="clock" style="width:14px;height:14px;"></i>
                        <span>Belum Ditagih</span>
                        <span class="badge" style="font-size:10px;padding:1px 6px;border-radius:10px;" x-text="(currentUnbilled || []).length"></span>
                    </button>
                    <button type="button" @click="selectTab('ladder', $event)" class="modal-tab-btn" :class="{ 'is-active': activeTab === 'ladder' }">
                        <i data-lucide="trending-up" style="width:14px;height:14px;"></i>
                        <span>Tangga Tier<span class="hidden sm:inline"> Komisi</span></span>
                    </button>
                </div>

                <!-- ============================================================ -->
                <!-- 3. TAB BODIES                                                 -->
                <!-- ============================================================ -->
                <div class="modal-tab-body custom-scrollbar">

                    <!-- TAB 1: TAGIHAN KONSINYASI -->
                    <template x-if="activeTab === 'konsin'">
                        <div style="display:flex;flex-direction:column;gap:16px;">

                            <!-- Info Banner -->
                            <div class="modal-info-banner is-emerald">
                                <div class="modal-info-banner-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;max-width:15px;max-height:15px;flex-shrink:0;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </div>
                                <p style="font-size:12px;color:var(--color-ink-secondary);line-height:1.55;margin:0;">
                                    Hanya faktur konsinyasi dengan <strong>nominal yang sudah dibayar</strong> yang dihitung ke dalam omzet komisi sales. Sisa piutang/hutang toko baru masuk setelah toko membayar.
                                </p>
                            </div>

                            <!-- Tabel -->
                            <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:14px;overflow-x:auto;-webkit-overflow-scrolling:touch;">
                                <table class="data-table" style="margin:0;min-width:540px;">
                                    <thead>
                                        <tr>
                                            <th>Nota &amp; Tanggal</th>
                                            <th>Toko Konsinyasi</th>
                                            <th class="cell-right">Total Tagihan</th>
                                            <th class="cell-right" style="color:#10b981;">Sudah Dibayar</th>
                                            <th class="cell-right" style="color:#ef4444;">Sisa Hutang</th>
                                            <th class="cell-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="inv in currentKonsinInvoices" :key="inv.id">
                                            <tr>
                                                <td>
                                                    <div class="font-mono font-bold text-xs" style="color:var(--color-ink);" x-text="inv.nomor_nota"></div>
                                                    <div class="text-[10.5px] text-slate-400" x-text="formatDateIndo(inv.tanggal_pesanan)"></div>
                                                </td>
                                                <td>
                                                    <div class="font-bold text-xs" style="color:var(--color-ink);" x-text="inv.nama_toko"></div>
                                                    <div class="font-mono text-[10.5px] text-slate-400" x-text="inv.kode_pelanggan"></div>
                                                </td>
                                                <td class="cell-right font-mono text-xs" x-text="formatRupiah(inv.total_netto)"></td>
                                                <td class="cell-right font-mono font-bold text-emerald-600 dark:text-emerald-400 text-xs" x-text="formatRupiah(inv.total_dibayar)"></td>
                                                <td class="cell-right font-mono font-bold text-rose-500 text-xs" x-text="formatRupiah(inv.sisa_tagihan)"></td>
                                                <td class="cell-center">
                                                    <span :class="{
                                                        'badge badge-success': inv.status_pembayaran === 'lunas',
                                                        'badge badge-warning': inv.status_pembayaran === 'sebagian',
                                                        'badge badge-danger': inv.status_pembayaran === 'belum_lunas'
                                                    }" style="font-size:10px;font-weight:800;text-transform:uppercase;" x-text="inv.status_pembayaran.replace('_', ' ')"></span>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="!currentKonsinInvoices || currentKonsinInvoices.length === 0">
                                            <tr>
                                                <td colspan="6" style="text-align:center;padding:48px 20px;color:var(--color-ink-mute);">
                                                    <i data-lucide="inbox" style="width:28px;height:28px;margin:0 auto 8px;display:block;opacity:0.3;"></i>
                                                    Tidak ada faktur tagihan konsinyasi pada periode ini.
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <!-- TAB 2: PESANAN GROSIR B2B -->
                    <template x-if="activeTab === 'b2b'">
                        <div style="display:flex;flex-direction:column;gap:16px;">

                            <!-- Info Banner -->
                            <div class="modal-info-banner is-blue">
                                <div class="modal-info-banner-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;max-width:15px;max-height:15px;flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                </div>
                                <p style="font-size:12px;color:var(--color-ink-secondary);line-height:1.55;margin:0;">
                                    Pesanan reguler / grosir beli putus yang ditangani sales. Hanya pembayaran riil yang diakui sebagai omzet komisi.
                                </p>
                            </div>

                            <!-- Tabel -->
                            <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:14px;overflow-x:auto;-webkit-overflow-scrolling:touch;">
                                <table class="data-table" style="margin:0;min-width:540px;">
                                    <thead>
                                        <tr>
                                            <th>Nota &amp; Tanggal</th>
                                            <th>Pelanggan / Toko</th>
                                            <th class="cell-right">Total Tagihan</th>
                                            <th class="cell-right" style="color:#10b981;">Sudah Dibayar</th>
                                            <th class="cell-right" style="color:#ef4444;">Sisa Hutang</th>
                                            <th class="cell-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="ord in currentB2bOrders" :key="ord.id">
                                            <tr>
                                                <td>
                                                    <div class="font-mono font-bold text-xs" style="color:var(--color-ink);" x-text="ord.nomor_nota"></div>
                                                    <div class="text-[10.5px] text-slate-400" x-text="formatDateIndo(ord.tanggal_pesanan)"></div>
                                                </td>
                                                <td>
                                                    <div class="font-bold text-xs" style="color:var(--color-ink);" x-text="ord.nama_toko"></div>
                                                    <div class="font-mono text-[10.5px] text-slate-400" x-text="ord.kode_pelanggan || '-'"></div>
                                                </td>
                                                <td class="cell-right font-mono text-xs" x-text="formatRupiah(ord.total_netto)"></td>
                                                <td class="cell-right font-mono font-bold text-emerald-600 dark:text-emerald-400 text-xs" x-text="formatRupiah(ord.total_dibayar)"></td>
                                                <td class="cell-right font-mono font-bold text-rose-500 text-xs" x-text="formatRupiah(ord.sisa_tagihan)"></td>
                                                <td class="cell-center">
                                                    <span :class="{
                                                        'badge badge-success': ord.status_pembayaran === 'lunas',
                                                        'badge badge-warning': ord.status_pembayaran === 'sebagian',
                                                        'badge badge-danger': ord.status_pembayaran === 'belum_lunas'
                                                    }" style="font-size:10px;font-weight:800;text-transform:uppercase;" x-text="ord.status_pembayaran.replace('_', ' ')"></span>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="!currentB2bOrders || currentB2bOrders.length === 0">
                                            <tr>
                                                <td colspan="6" style="text-align:center;padding:48px 20px;color:var(--color-ink-mute);">
                                                    <i data-lucide="inbox" style="width:28px;height:28px;margin:0 auto 8px;display:block;opacity:0.3;"></i>
                                                    Tidak ada pesanan B2B pada periode ini.
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <!-- TAB 3: KUNJUNGAN BELUM DITAGIHKAN -->
                    <template x-if="activeTab === 'unbilled'">
                        <div style="display:flex;flex-direction:column;gap:16px;">

                            <!-- Info Banner -->
                            <div class="modal-info-banner is-rose">
                                <div class="modal-info-banner-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;max-width:15px;max-height:15px;flex-shrink:0;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                </div>
                                <p style="font-size:12px;color:var(--color-ink-secondary);line-height:1.55;margin:0;">
                                    Kunjungan opname fisik rak berikut <strong>belum dibuatkan tagihan faktur</strong>. Omzetnya <strong>belum masuk komisi</strong> sampai tagihan resmi diterbitkan dan toko membayarnya.
                                </p>
                            </div>

                            <!-- Tabel -->
                            <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:14px;overflow-x:auto;-webkit-overflow-scrolling:touch;">
                                <table class="data-table" style="margin:0;min-width:500px;">
                                    <thead>
                                        <tr>
                                            <th>Nomor Kunjungan</th>
                                            <th>Tanggal</th>
                                            <th>Toko Konsinyasi</th>
                                            <th class="cell-right">Penjualan Fisik Rak</th>
                                            <th class="cell-center">Status Omzet</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="vis in currentUnbilled" :key="vis.id">
                                            <tr>
                                                <td class="font-mono font-bold text-xs" style="color:var(--color-ink);" x-text="vis.nomor_kunjungan"></td>
                                                <td class="text-xs text-slate-400" x-text="formatDateIndo(vis.tanggal_kunjungan)"></td>
                                                <td>
                                                    <div class="font-bold text-xs" style="color:var(--color-ink);" x-text="vis.nama_toko"></div>
                                                    <div class="font-mono text-[10.5px] text-slate-400" x-text="vis.kode_pelanggan"></div>
                                                </td>
                                                <td class="cell-right font-mono font-bold text-xs" x-text="formatRupiah(vis.total_laku_nominal)"></td>
                                                <td class="cell-center">
                                                    <span class="badge badge-warning" style="font-size:10px;font-weight:800;">Belum Ditagih</span>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="!currentUnbilled || currentUnbilled.length === 0">
                                            <tr>
                                                <td colspan="5" style="text-align:center;padding:48px 20px;color:var(--color-ink-mute);">
                                                    <i data-lucide="check-circle-2" style="width:28px;height:28px;margin:0 auto 8px;display:block;color:#10b981;opacity:0.5;"></i>
                                                    Semua kunjungan telah diterbitkan fakturnya.
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <!-- TAB 4: TANGGA TIER KOMISI -->
                    <template x-if="activeTab === 'ladder'">
                        <div class="tier-ladder-wrapper">

                            <!-- 1. Info Banner Edukasi Flat Retroaktif -->
                            <div class="tier-banner-box">
                                <div class="tier-banner-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                    </svg>
                                </div>
                                <div class="tier-banner-content">
                                    <div class="tier-banner-head">
                                        <div class="tier-banner-title">Skema Komisi: Model Flat Retroaktif</div>
                                        <span class="tier-banner-pill">Otomatis Naik Kelas</span>
                                    </div>
                                    <p class="tier-banner-desc">
                                        Tier tertinggi yang diraih sales otomatis berlaku untuk <strong>seluruh total omzet terbayar</strong> di bulan berjalan (bukan sistem berjenjang selisih). Begitu target tier berikutnya tembus, 100% total omzet dari awal langsung dikalikan rate komisi yang lebih besar!
                                    </p>
                                </div>
                            </div>

                            <!-- 2. Hero Milestone Progress Card -->
                            <template x-if="activeSales.tier_info?.has_next_tier">
                                <div class="tier-hero-card">
                                    <!-- Header: Target Name + Selisih Gap -->
                                    <div class="tier-hero-head">
                                        <div class="tier-hero-target-group">
                                            <div class="tier-hero-target-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"/><line x1="22" y1="12" x2="18" y2="12"/><line x1="6" y1="12" x2="2" y2="12"/><line x1="12" y1="6" x2="12" y2="2"/><line x1="12" y1="22" x2="12" y2="18"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="tier-hero-label">Target Level Berikutnya</div>
                                                <div class="tier-hero-target-name">
                                                    <span x-text="activeSales.tier_info.next_tier_nama"></span>
                                                    <span class="tier-hero-rate-tag" x-text="Number(activeSales.tier_info.next_tier_persentase) + '%'"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Gap Badge Motivasi -->
                                        <div class="tier-hero-gap-badge">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>
                                            </svg>
                                            <span>Kurang <strong class="font-mono font-black" x-text="formatRupiahClean(activeSales.tier_info.gap_omzet_ke_next_tier)"></strong> lagi</span>
                                        </div>
                                    </div>

                                    <!-- Progress Bar Track & Fill -->
                                    <div class="tier-progress-wrap">
                                        <div class="tier-progress-track">
                                            <div class="tier-progress-bar" :style="{ width: calcTierProgress() + '%' }"></div>
                                        </div>

                                        <!-- Milestone Labels -->
                                        <div class="tier-milestone-row">
                                            <!-- Kiri: Omzet Saat Ini -->
                                            <div class="tier-milestone-col is-left">
                                                <div class="tier-milestone-lbl">
                                                    <span class="tier-dot-current"></span>
                                                    <span>Omzet Saat Ini</span>
                                                </div>
                                                <div class="tier-milestone-num" x-text="formatRupiahClean(activeSales.total_omzet)"></div>
                                            </div>

                                            <!-- Tengah: Persen Tercapai -->
                                            <div class="tier-milestone-center">
                                                <span class="tier-pct-badge" x-text="calcTierProgress() + '% Tercapai'"></span>
                                            </div>

                                            <!-- Kanan: Target -->
                                            <div class="tier-milestone-col is-right">
                                                <div class="tier-milestone-lbl justify-end">
                                                    <span>Target</span>
                                                    <span class="tier-dot-target"></span>
                                                </div>
                                                <div class="tier-milestone-num" x-text="formatRupiahClean(Number(activeSales.total_omzet) + Number(activeSales.tier_info.gap_omzet_ke_next_tier))"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footnote -->
                                    <div class="tier-hero-note">
                                        <div class="tier-hero-note-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/>
                                            </svg>
                                        </div>
                                        <p style="margin:0;line-height:1.55;">
                                            <strong>Lonjakan Hasil:</strong> Capai omzet <span class="font-bold font-mono whitespace-nowrap" style="color:var(--color-ink);" x-text="formatRupiahClean(Number(activeSales.total_omzet) + Number(activeSales.tier_info.gap_omzet_ke_next_tier))"></span> untuk melipatgandakan komisi ke <span class="font-bold whitespace-nowrap" style="color:#2563eb;" x-text="Number(activeSales.tier_info.next_tier_persentase) + '%'"></span> dengan potensi komisi minimal <strong class="font-mono font-black whitespace-nowrap" style="color:#059669;" x-text="formatRupiahClean((Number(activeSales.total_omzet) + Number(activeSales.tier_info.gap_omzet_ke_next_tier)) * Number(activeSales.tier_info.next_tier_persentase) / 100)"></strong>!
                                        </p>
                                    </div>
                                </div>
                            </template>

                            <!-- State Jika Tier Maksimal -->
                            <template x-if="!activeSales.tier_info?.has_next_tier && activeSales.tier_info?.matched">
                                <div class="tier-max-card">
                                    <div class="tier-max-trophy">
                                        🏆
                                    </div>
                                    <div style="min-width:0;flex:1;">
                                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                            <h4 style="font-size:14px;font-weight:900;margin:0;color:#92400e;">Tier Puncak Tercapai — Komisi Maksimal!</h4>
                                            <span style="background:#f59e0b;color:#fff;font-size:10px;font-weight:900;padding:2px 8px;border-radius:99px;white-space:nowrap;">MAKSIMAL</span>
                                        </div>
                                        <p style="font-size:11.5px;color:#b45309;margin:4px 0 0;line-height:1.55;">
                                            Luar biasa! Sales ini telah menembus tingkatan tier tertinggi bulan ini. Seluruh total omzet terbayar berhak atas persentase komisi maksimal <strong class="whitespace-nowrap" x-text="Number(activeSales.tier_info.persentase) + '%'"></strong>.
                                        </p>
                                    </div>
                                </div>
                            </template>

                            <!-- 3. Grid Kartu Tangga Tier -->
                            <div class="tier-cards-grid">
                                <template x-for="t in allTiers" :key="t.id">
                                    <div class="tier-card-block"
                                         :class="{
                                             'is-active-tier': isCurrentTier(t),
                                             'is-next-tier': isNextTier(t) && !isCurrentTier(t),
                                             'is-passed-tier': isPassedTier(t),
                                             'is-locked-tier': isLockedTier(t)
                                         }">
                                        
                                        <!-- Header Kartu -->
                                        <div>
                                            <div class="tier-card-top-row">
                                                <div class="tier-card-title-wrap">
                                                    <span class="tier-card-medal" x-text="getTierBadgeIcon(t)"></span>
                                                    <div class="tier-card-heading" x-text="t.nama_tier"></div>
                                                </div>
                                                <div class="tier-card-rate-box">
                                                    <div class="tier-card-rate-num" x-text="Number(t.persentase) + '%'"></div>
                                                    <div class="tier-card-rate-lbl">Rate Komisi</div>
                                                </div>
                                            </div>

                                            <!-- Status Pill -->
                                            <div class="tier-card-pill-row">
                                                <!-- Aktif -->
                                                <template x-if="isCurrentTier(t)">
                                                    <span class="tier-card-status-pill is-active">
                                                        <span style="width:6px;height:6px;border-radius:50%;background:#fff;display:inline-block;animation:pulse 1.5s infinite;"></span>
                                                        Aktif Saat Ini
                                                    </span>
                                                </template>

                                                <!-- Target Berikutnya -->
                                                <template x-if="isNextTier(t) && !isCurrentTier(t)">
                                                    <span class="tier-card-status-pill is-next">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;max-width:11px;max-height:11px;flex-shrink:0;"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
                                                        Target Berikutnya
                                                    </span>
                                                </template>

                                                <!-- Terlewati -->
                                                <template x-if="isPassedTier(t)">
                                                    <span class="tier-card-status-pill is-passed">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;max-width:11px;max-height:11px;flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>
                                                        Sudah Terlewati
                                                    </span>
                                                </template>

                                                <!-- Terkunci -->
                                                <template x-if="isLockedTier(t)">
                                                    <span class="tier-card-status-pill is-locked">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;max-width:11px;max-height:11px;flex-shrink:0;"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                        Target Lanjutan
                                                    </span>
                                                </template>
                                            </div>

                                            <!-- Target Range -->
                                            <div class="tier-card-range-badge">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;max-width:13px;max-height:13px;flex-shrink:0;">
                                                    <circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>
                                                </svg>
                                                <span class="flex items-center gap-1 flex-wrap">
                                                    <span class="whitespace-nowrap" x-text="formatRupiahClean(t.omzet_min)"></span>
                                                    <span style="opacity:0.4;">—</span>
                                                    <span class="whitespace-nowrap" x-text="t.omzet_maks ? formatRupiahClean(t.omzet_maks) : '∞ (Tanpa Batas)'"></span>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Footer Kartu -->
                                        <div class="tier-card-bottom-row">
                                            <template x-if="isCurrentTier(t)">
                                                <span>Komisi diraih saat ini:</span>
                                            </template>
                                            <template x-if="!isCurrentTier(t)">
                                                <span>Potensi komisi minimal:</span>
                                            </template>

                                            <template x-if="isCurrentTier(t)">
                                                <span class="tier-card-bottom-val whitespace-nowrap" style="color:#d97706;" x-text="formatRupiahClean(activeSales.nominal_komisi)"></span>
                                            </template>
                                            <template x-if="!isCurrentTier(t)">
                                                <span class="tier-card-bottom-val whitespace-nowrap" :style="isNextTier(t) ? 'color:#2563eb;' : 'color:var(--color-ink-secondary);'" x-text="formatRupiahClean(calcMinPotentialCommission(t))"></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                        </div>
                    </template>

                </div><!-- /modal-tab-body -->



            </div><!-- /modal-box -->
        </div><!-- /modal-backdrop -->
    </template>

</div>

<script>
function komisiApp() {
    return {
        showBreakdownModal: false,
        activeTab: 'konsin',
        activeSales: {},
        filterStartDate: '<?= htmlspecialchars($startDate) ?>',
        filterEndDate: '<?= htmlspecialchars($endDate) ?>',

        // Master Data dari Backend
        allBreakdowns: <?= json_encode($storeBreakdown ?? []) ?>,
        allKonsinInvoices: <?= json_encode($breakdownKonsin ?? []) ?>,
        allB2bOrders: <?= json_encode($breakdownB2b ?? []) ?>,
        allUnbilled: <?= json_encode($breakdownUnbilled ?? []) ?>,
        allTiers: <?= json_encode($allTiers ?? []) ?>,

        init() {
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        get currentKonsinInvoices() {
            return this.allKonsinInvoices[this.activeSales.sales_id] || [];
        },

        get currentB2bOrders() {
            return this.allB2bOrders[this.activeSales.sales_id] || [];
        },

        get currentUnbilled() {
            return this.allUnbilled[this.activeSales.sales_id] || [];
        },

        formatYMD(d) {
            const pad = (n) => String(n).padStart(2, '0');
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        },

        setQuickPeriod(type) {
            const today = new Date();
            if (type === 'this_month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                this.filterStartDate = this.formatYMD(firstDay);
                this.filterEndDate = this.formatYMD(lastDay);
            } else if (type === 'last_month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
                this.filterStartDate = this.formatYMD(firstDay);
                this.filterEndDate = this.formatYMD(lastDay);
            } else if (type === 'today') {
                this.filterStartDate = this.formatYMD(today);
                this.filterEndDate = this.formatYMD(today);
            } else if (type === 'this_week') {
                const curr = new Date();
                const day = curr.getDay();
                const diff = curr.getDate() - day + (day === 0 ? -6 : 1);
                const monday = new Date(curr.setDate(diff));
                const sunday = new Date(curr.setDate(diff + 6));
                this.filterStartDate = this.formatYMD(monday);
                this.filterEndDate = this.formatYMD(sunday);
            } else if (type === 'last_30_days') {
                const past = new Date();
                past.setDate(today.getDate() - 30);
                this.filterStartDate = this.formatYMD(past);
                this.filterEndDate = this.formatYMD(today);
            }

            this.$nextTick(() => {
                this.$refs.filterForm.submit();
            });
        },

        isActivePreset(type) {
            const today = new Date();
            if (type === 'this_month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                return this.filterStartDate === this.formatYMD(firstDay) && this.filterEndDate === this.formatYMD(lastDay);
            } else if (type === 'last_month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
                return this.filterStartDate === this.formatYMD(firstDay) && this.filterEndDate === this.formatYMD(lastDay);
            } else if (type === 'today') {
                return this.filterStartDate === this.formatYMD(today) && this.filterEndDate === this.formatYMD(today);
            } else if (type === 'last_30_days') {
                const past = new Date();
                past.setDate(today.getDate() - 30);
                return this.filterStartDate === this.formatYMD(past) && this.filterEndDate === this.formatYMD(today);
            }
            return false;
        },

        openBreakdownModal(sales, tab = 'konsin') {
            this.activeSales = sales;
            this.activeTab = tab;
            this.showBreakdownModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
                const activeBtn = this.$refs.tabNav?.querySelector('.modal-tab-btn.is-active');
                if (activeBtn) {
                    activeBtn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                }
            });
        },

        selectTab(tab, e) {
            this.activeTab = tab;
            if (e && e.currentTarget) {
                e.currentTarget.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            }
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        isCurrentTier(t) {
            const currentTierId = this.activeSales.tier_info?.tier_id;
            return currentTierId && currentTierId === t.id;
        },

        isPassedTier(t) {
            const currentUrutan = Number(this.activeSales.tier_info?.urutan || 0);
            return currentUrutan > Number(t.urutan);
        },

        isNextTier(t) {
            const currentUrutan = Number(this.activeSales.tier_info?.urutan || 0);
            return Number(t.urutan) === currentUrutan + 1;
        },

        isLockedTier(t) {
            const currentUrutan = Number(this.activeSales.tier_info?.urutan || 0);
            return Number(t.urutan) > currentUrutan + 1;
        },

        getTierBadgeIcon(t) {
            const urutan = Number(t.urutan);
            if (urutan === 1) return '🥉';
            if (urutan === 2) return '🥈';
            if (urutan === 3) return '🥇';
            return '💎';
        },

        calcTierProgress() {
            const omzet = Number(this.activeSales.total_omzet || 0);
            const gap = Number(this.activeSales.tier_info?.gap_omzet_ke_next_tier || 0);
            const target = omzet + gap;
            if (target <= 0) return 100;
            return Math.min(100, Math.max(0, Math.round((omzet / target) * 100)));
        },

        calcMinPotentialCommission(t) {
            const minOmzet = Math.round(Number(t.omzet_min || 0));
            const pct = Number(t.persentase || 0);
            return Math.round(minOmzet * (pct / 100));
        },

        formatRupiah(val) {
            return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
        },

        formatRupiahClean(val) {
            if (val === null || val === undefined || val === '') return 'Rp 0';
            const rounded = Math.round(Number(val));
            return 'Rp ' + rounded.toLocaleString('id-ID');
        },

        formatDateIndo(dateStr) {
            if (!dateStr) return '-';
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                return parts[2] + '/' + parts[1] + '/' + parts[0];
            }
            return dateStr;
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

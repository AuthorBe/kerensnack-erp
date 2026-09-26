<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();

$tipeKonsinyasi = $customer['tipe_konsinyasi'] ?? 'rolling_nota';
$totalItemsCount = count($items);
$totalStokTitipAwal = array_sum(array_column($items, 'stok_titip_saat_ini'));
?>

<style>
/* ========================================================================= */
/* OPNAME KONSINYASI - MOBILE FIRST & MATERIAL DESIGN 3 ENGINE               */
/* ========================================================================= */

/* General resets & tap highlights */
* {
    -webkit-tap-highlight-color: transparent;
}

.opname-container {
    padding-bottom: 40px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
@media (min-width: 640px) {
    .opname-container {
        padding-bottom: 48px;
        gap: 20px;
    }
}

/* Helper Utilities */
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.opname-val-mono {
    font-family: var(--font-mono);
}

/* 1. Header Card */
.opname-header-card {
    background: var(--color-surface);
    border: 1px solid var(--color-hairline);
    border-radius: 20px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
@media (min-width: 640px) {
    .opname-header-card {
        padding: 22px;
        border-radius: 24px;
    }
}
.opname-header-main {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
@media (min-width: 640px) {
    .opname-header-main {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}
.opname-store-info {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    min-width: 0;
    flex: 1;
}
@media (min-width: 640px) {
    .opname-store-info {
        align-items: center;
    }
}
.opname-back-btn {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    border: 1px solid var(--color-hairline);
    background: var(--color-canvas-soft);
    color: var(--color-ink);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    text-decoration: none;
    transition: transform 0.1s ease, background 0.1s ease;
}
.opname-back-btn:active {
    transform: scale(0.92);
}
.opname-store-details {
    min-width: 0;
    flex: 1;
}
.opname-store-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 4px;
}
.opname-pill-live {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 2px 9px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
}
.opname-live-dot {
    width: 6px;
    height: 6px;
    border-radius: 9999px;
    background: #10b981;
}
.opname-pill-code {
    font-family: var(--font-mono);
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 6px;
    background: var(--color-canvas-soft);
    color: var(--color-ink-mute);
    border: 1px solid var(--color-hairline);
}
.opname-sales-badge {
    font-size: 11px;
    font-weight: 500;
    color: var(--color-ink-mute);
    display: none;
}
@media (min-width: 640px) {
    .opname-sales-badge {
        display: inline;
    }
}
.opname-store-name {
    font-size: 18px;
    font-weight: 900;
    color: var(--color-ink);
    line-height: 1.25;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
@media (min-width: 640px) {
    .opname-store-name {
        font-size: 22px;
    }
}
.opname-store-address {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--color-ink-mute);
    margin-top: 3px;
}
.opname-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    align-self: flex-end;
}
@media (min-width: 640px) {
    .opname-header-actions {
        align-self: center;
    }
}
.opname-btn-wa {
    height: 38px;
    padding: 0 14px;
    border-radius: 12px;
    border: 1px solid rgba(16, 185, 129, 0.3);
    background: rgba(16, 185, 129, 0.08);
    color: #10b981;
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: transform 0.1s ease;
}
.opname-btn-wa:active {
    transform: scale(0.95);
}
.opname-btn-guide {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    border: 1px solid var(--color-hairline);
    background: var(--color-canvas-soft);
    color: var(--color-ink-mute);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
}
.opname-btn-guide.is-active {
    background: rgba(2, 132, 199, 0.1);
    color: #0284c7;
    border-color: rgba(2, 132, 199, 0.3);
}

/* Collapsible Guide Banner */
.opname-guide-box {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid var(--color-hairline);
    display: flex;
    align-items: flex-start;
    gap: 10px;
    background: rgba(2, 132, 199, 0.06);
    border-radius: 14px;
    padding: 12px 14px;
}
.opname-guide-content {
    font-size: 12px;
    line-height: 1.5;
}
.opname-guide-title {
    font-weight: 800;
    color: #0284c7;
    margin-bottom: 3px;
}
.opname-guide-body {
    font-size: 11.5px;
    color: var(--color-ink-secondary);
}

/* 2. KPI Metrics Grid */
.opname-kpi-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}
@media (min-width: 640px) {
    .opname-kpi-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }
}
@media (min-width: 1024px) {
    .opname-kpi-grid {
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 12px;
    }
}
.opname-kpi-card {
    background: var(--color-surface);
    border: 1px solid var(--color-hairline);
    border-radius: 16px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.opname-kpi-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.opname-kpi-text {
    min-width: 0;
    flex: 1;
}
.opname-kpi-label {
    font-size: 10.5px;
    font-weight: 700;
    color: var(--color-ink-mute);
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.opname-kpi-value {
    font-size: 14.5px;
    font-weight: 900;
    color: var(--color-ink);
    display: block;
    font-family: var(--font-mono);
    line-height: 1.2;
    margin-top: 2px;
}
@media (min-width: 640px) {
    .opname-kpi-value {
        font-size: 16px;
    }
}

/* 3. Search & Toolbar Box */
.opname-toolbar-box {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.opname-toolbar-row {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
@media (min-width: 640px) {
    .opname-toolbar-row {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}
.opname-search-bar {
    position: relative;
    flex: 1;
    width: 100%;
}
.opname-search-input {
    width: 100%;
    height: 42px;
    padding-left: 42px;
    padding-right: 36px;
    border-radius: 12px;
    background: var(--color-surface);
    border: 1px solid var(--color-hairline);
    color: var(--color-ink);
    font-size: 13px;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.opname-search-input:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}
.opname-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    color: var(--color-ink-mute);
    pointer-events: none;
}
.opname-search-clear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: var(--color-ink-mute);
    padding: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
}
.opname-search-clear:hover {
    color: var(--color-ink);
}

/* Batch Helper Buttons */
.opname-batch-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    align-self: flex-end;
}
@media (min-width: 640px) {
    .opname-batch-actions {
        align-self: center;
    }
}
.opname-btn-batch {
    height: 40px;
    padding: 0 13px;
    border-radius: 11px;
    border: 1px solid var(--color-hairline);
    background: var(--color-surface);
    color: var(--color-ink-secondary);
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.12s ease;
    user-select: none;
}
.opname-btn-batch:hover {
    border-color: var(--color-hairline-strong);
    color: var(--color-ink);
    background: var(--color-canvas-soft);
}
.opname-btn-batch:active {
    transform: scale(0.96);
}

/* Filter Chips Track */
.opname-chips-track {
    display: flex;
    align-items: center;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 4px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}
.opname-chips-track::-webkit-scrollbar {
    display: none;
}
.opname-chip {
    padding: 7px 15px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid var(--color-hairline);
    background: var(--color-surface);
    color: var(--color-ink-secondary);
    cursor: pointer;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
    user-select: none;
}
.opname-chip:hover {
    border-color: var(--color-hairline-strong);
}
.opname-chip.active-all {
    background: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
    color: #ffffff !important;
}
.opname-chip.active-sold {
    background: #10b981 !important;
    border-color: #10b981 !important;
    color: #ffffff !important;
}
.opname-chip.active-intact {
    background: #0284c7 !important;
    border-color: #0284c7 !important;
    color: #ffffff !important;
}
.opname-chip.active-return {
    background: #f43f5e !important;
    border-color: #f43f5e !important;
    color: #ffffff !important;
}
.opname-chip-count {
    font-size: 10px;
    padding: 1px 6px;
    border-radius: 9999px;
    font-family: var(--font-mono);
    font-weight: 800;
}

/* 4. Product Cards */
.opname-cards-stack {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.opname-card {
    background: var(--color-surface);
    border: 1px solid var(--color-hairline);
    border-radius: 18px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
@media (min-width: 640px) {
    .opname-card {
        padding: 18px 20px;
        border-radius: 20px;
    }
}
.opname-card.is-sold {
    border-left: 5px solid #10b981;
}
.opname-card.is-warning {
    border-left: 5px solid #f43f5e;
    background: rgba(244, 63, 94, 0.02);
}
.opname-card.is-touched {
    border-left: 5px solid #0284c7;
}

/* Card Header Inside Card */
.opname-card-head {
    display: block;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--color-hairline);
}
.opname-card-info {
    min-width: 0;
    width: 100%;
}
.opname-card-tags {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 4px;
}
.opname-card-sku {
    font-family: var(--font-mono);
    font-size: 11px;
    font-weight: 700;
    color: var(--color-ink-mute);
}
.opname-card-price {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--color-ink-secondary);
}
.opname-card-empty-badge {
    font-size: 10px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 6px;
    background: rgba(244, 63, 94, 0.1);
    color: #f43f5e;
}
.opname-card-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--color-ink);
    line-height: 1.3;
    margin: 0;
}
@media (min-width: 640px) {
    .opname-card-title {
        font-size: 16px;
    }
}
.opname-card-pending-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10.5px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 6px;
    background: rgba(245, 158, 11, 0.12);
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.25);
}

.opname-card-baseline {
    flex-shrink: 0;
    text-align: right;
}
.opname-baseline-label {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--color-ink-mute);
    display: block;
    margin-bottom: 2px;
}
.opname-baseline-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 8px;
    background: rgba(2, 132, 199, 0.08);
    color: #0284c7;
    font-family: var(--font-mono);
    font-size: 12px;
    font-weight: 800;
    border: 1px solid rgba(2, 132, 199, 0.2);
}

/* 5. Fixed Bottom Dock (Khusus Mode Opname Kolektif Toko) */
.opname-dock {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 30;
    background: var(--color-surface);
    border-top: 1px solid var(--color-hairline);
    box-shadow: 0 -6px 20px rgba(0, 0, 0, 0.08);
    padding: 12px 16px;
    padding-bottom: max(12px, env(safe-area-inset-bottom));
}
@media (min-width: 1024px) {
    .opname-dock {
        left: var(--sidebar-width, 224px);
    }
}
.opname-dock-inner {
    max-width: 1280px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.opname-dock-total {
    min-width: 0;
    flex: 1;
}
.opname-dock-label {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--color-ink-mute);
    display: block;
}
@media (min-width: 640px) {
    .opname-dock-label {
        font-size: 11px;
    }
}
.opname-dock-val-row {
    display: flex;
    align-items: baseline;
    gap: 6px;
    margin-top: 2px;
}
.opname-dock-rp {
    font-size: 20px;
    font-weight: 900;
    font-family: var(--font-mono);
    color: #10b981;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
@media (min-width: 640px) {
    .opname-dock-rp {
        font-size: 24px;
    }
}
.opname-dock-pcs {
    font-size: 12px;
    font-weight: 700;
    color: var(--color-ink-secondary);
    display: none;
}
@media (min-width: 480px) {
    .opname-dock-pcs {
        display: inline;
    }
}
.opname-submit-btn {
    height: 48px;
    padding: 0 22px;
    border-radius: 14px;
    background: #10b981;
    color: #ffffff;
    border: none;
    font-weight: 900;
    font-size: 13.5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
    transition: all 0.15s ease;
    flex-shrink: 0;
    user-select: none;
}
.opname-submit-btn:hover {
    background: #059669;
}
.opname-submit-btn:active {
    transform: scale(0.96);
}
.opname-submit-btn.is-disabled,
.opname-submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    box-shadow: none;
}

/* Horizontal Formula Strip: Sisa Kiriman Lalu + Kirim Baru (PO) = Total Titipan */
.opname-formula-strip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 3px;
    flex-wrap: nowrap !important;
    width: 100%;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px dashed var(--color-hairline);
    box-sizing: border-box;
}
.opname-formula-badge {
    flex: 1 1 0px;
    min-width: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 3px;
    padding: 4px 4px;
    border-radius: 8px;
    font-size: 11px;
    line-height: 1.2;
    white-space: nowrap;
    text-align: center;
}
.opname-formula-badge.is-sisa {
    background: rgba(2, 132, 199, 0.08);
    border: 1px solid rgba(2, 132, 199, 0.22);
    color: #0369a1;
}
.opname-formula-badge.is-kirim {
    background: rgba(16, 185, 129, 0.08);
    border: 1px solid rgba(16, 185, 129, 0.22);
    color: #047857;
}
.opname-formula-badge.is-total {
    background: rgba(99, 102, 241, 0.08);
    border: 1px solid rgba(99, 102, 241, 0.25);
    color: #4338ca;
    font-weight: 800;
}
.opname-formula-tag {
    font-size: 10px;
    font-weight: 700;
    opacity: 0.9;
    white-space: nowrap;
}
.opname-formula-val {
    font-family: var(--font-mono);
    font-weight: 800;
    font-size: 11px;
    white-space: nowrap;
}
.opname-formula-unit {
    font-size: 9px;
    opacity: 0.8;
    white-space: nowrap;
}
.opname-formula-op {
    font-size: 11px;
    font-weight: 900;
    color: var(--color-ink-mute);
    user-select: none;
    flex-shrink: 0;
    padding: 0 1px;
}
@media (min-width: 640px) {
    .opname-formula-strip {
        gap: 8px;
    }
    .opname-formula-badge {
        padding: 5px 10px;
        gap: 5px;
        font-size: 12px;
    }
    .opname-formula-tag {
        font-size: 11px;
    }
    .opname-formula-val {
        font-size: 12.5px;
    }
    .opname-formula-unit {
        font-size: 10px;
    }
    .opname-formula-op {
        font-size: 13px;
        padding: 0 2px;
    }
}

/* 5-COLUMN RESPONSIVE PENTA GRID (SISA RAK, LAKU, RETUR RUSAK, RETUR BAGUS, SELISIH) */
.opname-penta-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-top: 14px;
}
@media (max-width: 639px) {
    .opname-col-selisih {
        grid-column: span 2;
    }
}
@media (min-width: 640px) and (max-width: 1023px) {
    .opname-penta-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }
}
@media (min-width: 1024px) {
    .opname-penta-grid {
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 10px;
    }
}
.opname-input-group {
    min-width: 0;
}
.opname-field-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11.5px;
    font-weight: 800;
    color: var(--color-ink);
    margin-bottom: 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.opname-stepper {
    display: flex;
    align-items: stretch;
    height: 40px;
    border: 1.5px solid var(--color-hairline-strong);
    border-radius: 12px;
    background: var(--color-canvas);
    overflow: hidden;
    width: 100%;
    box-sizing: border-box;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.opname-stepper:focus-within {
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}
.opname-stepper.is-laku-stepper {
    border-color: rgba(16, 185, 129, 0.45);
    background: rgba(16, 185, 129, 0.04);
}
.opname-stepper.is-laku-stepper:focus-within {
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}
.opname-stepper.is-rusak-stepper {
    border-color: rgba(244, 63, 94, 0.4);
    background: rgba(244, 63, 94, 0.03);
}
.opname-stepper.is-rusak-stepper:focus-within {
    box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.2);
}
.opname-stepper.is-bagus-stepper {
    border-color: rgba(2, 132, 199, 0.4);
    background: rgba(2, 132, 199, 0.03);
}
.opname-stepper.is-bagus-stepper:focus-within {
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2);
}
.opname-stepper.is-selisih-lost {
    border-color: rgba(245, 158, 11, 0.45);
    background: rgba(245, 158, 11, 0.04);
}
.opname-stepper.is-selisih-found {
    border-color: rgba(16, 185, 129, 0.45);
    background: rgba(16, 185, 129, 0.04);
}
.opname-stepper-btn {
    width: 32px;
    height: 37px;
    border: none;
    background: var(--color-canvas-soft);
    color: var(--color-ink);
    font-size: 17px;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.1s ease, transform 0.08s ease;
    user-select: none;
    padding: 0;
    flex-shrink: 0;
}
@media (min-width: 1200px) {
    .opname-stepper-btn {
        width: 36px;
    }
}
.opname-stepper-btn:hover {
    background: rgba(0, 0, 0, 0.08);
}
.dark .opname-stepper-btn:hover {
    background: rgba(255, 255, 255, 0.1);
}
.opname-stepper-btn:active {
    transform: scale(0.92);
}
.opname-stepper-btn.is-laku-btn {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
}
.opname-stepper-btn.is-laku-btn:hover {
    background: rgba(16, 185, 129, 0.25);
}
.opname-stepper-btn.is-rusak-btn {
    background: rgba(244, 63, 94, 0.12);
    color: #f43f5e;
}
.opname-stepper-btn.is-rusak-btn:hover {
    background: rgba(244, 63, 94, 0.22);
}
.opname-stepper-btn.is-bagus-btn {
    background: rgba(2, 132, 199, 0.12);
    color: #0284c7;
}
.opname-stepper-btn.is-bagus-btn:hover {
    background: rgba(2, 132, 199, 0.22);
}
.opname-stepper-btn.is-selisih-btn {
    background: rgba(245, 158, 11, 0.12);
    color: #d97706;
}
.opname-stepper-btn.is-selisih-btn:hover {
    background: rgba(245, 158, 11, 0.22);
}
.opname-stepper-input {
    flex: 1;
    width: 100%;
    min-width: 0;
    height: 37px;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    background: transparent !important;
    text-align: center;
    font-family: var(--font-mono);
    font-weight: 900;
    font-size: 15px;
    color: var(--color-ink);
    padding: 0;
    margin: 0;
    -moz-appearance: textfield;
}
@media (min-width: 1200px) {
    .opname-stepper-input {
        font-size: 16px;
    }
}
.opname-stepper-input.is-laku-input {
    color: #10b981;
}
.opname-stepper-input.is-bagus-input {
    color: #0284c7;
}
.opname-stepper-input::-webkit-outer-spin-button,
.opname-stepper-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.opname-field-hint {
    font-size: 10px;
    color: var(--color-ink-mute);
    margin-top: 4px;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Card Subrow (Presets & Live Outcome) */
.opname-card-subrow {
    margin-top: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
@media (min-width: 640px) {
    .opname-card-subrow {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}
.opname-presets-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}
.opname-quick-btn {
    height: 34px;
    padding: 0 12px;
    border-radius: 10px;
    border: 1px solid var(--color-hairline);
    background: var(--color-canvas-soft);
    color: var(--color-ink-secondary);
    font-size: 11.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.12s ease;
    user-select: none;
}
.opname-quick-btn i,
.opname-quick-btn svg {
    flex-shrink: 0;
}
.opname-quick-btn:hover {
    border-color: var(--color-hairline-strong);
    color: var(--color-ink);
}
.opname-quick-btn:active {
    transform: scale(0.95);
}
.opname-quick-btn.btn-habis:hover {
    color: #f43f5e;
    background: rgba(244, 63, 94, 0.08);
    border-color: rgba(244, 63, 94, 0.3);
}
.opname-quick-btn.btn-utuh:hover {
    color: #0284c7;
    background: rgba(2, 132, 199, 0.08);
    border-color: rgba(2, 132, 199, 0.3);
}

.opname-outcome-wrap {
    display: flex;
    align-items: center;
}
@media (min-width: 640px) {
    .opname-outcome-wrap {
        justify-content: flex-end;
    }
}
.opname-outcome-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 800;
}
.opname-outcome-pill.is-laku {
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.25);
}
.opname-outcome-pill.is-utuh {
    background: var(--color-canvas-soft);
    color: var(--color-ink-mute);
    border: 1px solid var(--color-hairline);
}
.opname-outcome-pill.is-lost {
    background: rgba(245, 158, 11, 0.12);
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.25);
}
.opname-outcome-pill.is-found {
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.25);
}
.opname-outcome-pill.is-warn {
    background: rgba(244, 63, 94, 0.1);
    color: #f43f5e;
    border: 1px solid rgba(244, 63, 94, 0.25);
}

.opname-chip.active-warn {
    background: #d97706 !important;
    border-color: #d97706 !important;
    color: #ffffff !important;
}

/* Retur Section */
.opname-retur-section {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--color-hairline);
}
.opname-retur-toggle {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 10px;
    border: 1px solid var(--color-hairline);
    background: var(--color-canvas-soft);
    color: var(--color-ink-secondary);
    cursor: pointer;
    transition: all 0.12s ease;
}
.opname-retur-toggle.has-retur {
    background: rgba(244, 63, 94, 0.08);
    border-color: rgba(244, 63, 94, 0.25);
    color: #f43f5e;
}
.opname-retur-box {
    margin-top: 10px;
    padding: 12px;
    border-radius: 14px;
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}
@media (min-width: 640px) {
    .opname-retur-box {
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }
}
.opname-retur-label {
    display: block;
    font-size: 11px;
    font-weight: 800;
    color: var(--color-ink);
    margin-bottom: 4px;
}
.opname-retur-stepper {
    display: flex;
    align-items: center;
    height: 38px;
    border-radius: 10px;
    border: 1px solid var(--color-hairline);
    background: var(--color-surface);
    overflow: hidden;
}
.opname-retur-stepper-btn {
    width: 38px;
    height: 38px;
    border: none;
    background: var(--color-canvas-soft);
    color: var(--color-ink);
    font-size: 16px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0;
    user-select: none;
}
.opname-retur-stepper-btn:hover {
    background: rgba(0, 0, 0, 0.06);
}
.opname-retur-stepper-input {
    flex: 1;
    width: 100%;
    min-width: 0;
    height: 38px;
    border: none !important;
    outline: none !important;
    background: transparent !important;
    text-align: center;
    font-family: var(--font-mono);
    font-weight: 800;
    font-size: 14px;
    color: var(--color-ink);
    padding: 0;
    -moz-appearance: textfield;
}
.opname-retur-stepper-input::-webkit-outer-spin-button,
.opname-retur-stepper-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.opname-retur-hint {
    font-size: 10px;
    color: var(--color-ink-mute);
    display: block;
    margin-top: 3px;
}

/* Responsive Confirm Modal */
@media (min-width: 640px) {
    .modal-backdrop.is-confirm-backdrop {
        align-items: center !important;
        padding: 20px;
    }
    .modal-box.is-confirm-box {
        border-radius: 24px !important;
        margin: auto;
    }
}

/* ========================================================================= */
/* 5. PROFESSIONAL POS CHECKOUT CARD & SUMMARY                                */
/* ========================================================================= */
.opname-pos-card {
    background: var(--color-surface);
    border: 1px solid var(--color-hairline);
    border-radius: 20px;
    padding: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    margin-top: 16px;
}
@media (min-width: 640px) {
    .opname-pos-card {
        padding: 22px;
    }
}
.opname-pos-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--color-hairline);
    margin-bottom: 16px;
}
.opname-pos-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}
@media (min-width: 900px) {
    .opname-pos-grid {
        grid-template-columns: 1.1fr 0.9fr;
        gap: 20px;
        align-items: stretch;
    }
}
.opname-pos-panel-input {
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 16px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.opname-pos-panel-summary {
    background: var(--color-surface);
    border: 1.5px solid var(--color-hairline);
    border-radius: 16px;
    padding: 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 14px;
}
.opname-pos-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 800;
    color: var(--color-ink-mute);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 6px;
}
.opname-pos-input-wrap {
    display: flex;
    align-items: center;
    height: 46px;
    background: var(--color-surface);
    border: 1.5px solid var(--color-hairline);
    border-radius: 12px;
    padding: 0 12px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.opname-pos-input-wrap:focus-within {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}
.opname-pos-prefix {
    font-size: 14px;
    font-weight: 800;
    color: var(--color-ink-mute);
    margin-right: 6px;
    user-select: none;
}
.opname-pos-input {
    flex: 1;
    border: none;
    background: transparent;
    font-family: var(--font-mono);
    font-size: 17px;
    font-weight: 900;
    color: #059669;
    outline: none;
    min-width: 0;
}
.opname-pos-quick-btns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.opname-pos-quick-btn {
    height: 36px;
    border-radius: 10px;
    font-size: 11.5px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    border: 1px solid var(--color-hairline);
    background: var(--color-surface);
    color: var(--color-ink);
}
.opname-pos-quick-btn.is-pas {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    border-color: rgba(16, 185, 129, 0.3);
}
.opname-pos-quick-btn.is-pas:hover {
    background: rgba(16, 185, 129, 0.2);
}
.opname-pos-quick-btn.is-sebagian {
    background: rgba(2, 132, 199, 0.08);
    color: #0284c7;
    border-color: rgba(2, 132, 199, 0.25);
}
.opname-pos-quick-btn.is-sebagian:hover {
    background: rgba(2, 132, 199, 0.18);
}

.opname-pos-sum-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
}
.opname-pos-sum-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--color-ink-secondary);
}
.opname-pos-sum-val {
    font-family: var(--font-mono);
    font-size: 14px;
    font-weight: 800;
    color: var(--color-ink);
}
.opname-pos-submit-btn {
    width: 100%;
    height: 50px;
    padding: 0 16px;
    border-radius: 14px;
    background: #10b981;
    color: #ffffff;
    border: none;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
    transition: all 0.15s ease;
    user-select: none;
}
.opname-pos-submit-btn:hover {
    background: #059669;
    box-shadow: 0 6px 18px rgba(16, 185, 129, 0.45);
}
.opname-pos-submit-btn:active {
    transform: scale(0.98);
}
.opname-pos-submit-btn.is-disabled,
.opname-pos-submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    box-shadow: none;
}
.opname-submit-count {
    font-size: 11px;
    font-family: var(--font-mono);
    background: rgba(255, 255, 255, 0.22);
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-block;
}
</style>

<div x-data="opnameApp()" class="opname-container">

    <!-- ========================================================================= -->
    <!-- 1. MODERN TOP BAR / STORE PROFILE HEADER                                  -->
    <!-- ========================================================================= -->
    <div class="opname-header-card">
        <div class="opname-header-main">
            
            <!-- Store Identity -->
            <div class="opname-store-info">
                <a href="<?= Router::url('/consignment/stok-rak') ?>" 
                   class="opname-back-btn" 
                   title="Kembali ke Monitoring Stok Rak">
                    <i data-lucide="arrow-left" style="width:18px;height:18px;"></i>
                </a>

                <div class="opname-store-details">
                    <div class="opname-store-meta">
                        <span class="opname-pill-live">
                            <span class="opname-live-dot"></span>
                            Opname Rak Fisik
                        </span>
                        <span class="opname-pill-code">
                            <?= htmlspecialchars($customer['kode_pelanggan'] ?? 'TOKO') ?>
                        </span>
                        <?php if (!empty($customer['nama_sales'])): ?>
                        <span class="opname-sales-badge">
                            • Sales: <strong><?= htmlspecialchars($customer['nama_sales']) ?></strong>
                        </span>
                        <?php endif; ?>
                    </div>

                    <h1 class="opname-store-name">
                        <?= htmlspecialchars($customer['nama_toko']) ?>
                    </h1>

                    <div class="opname-store-address">
                        <i data-lucide="map-pin" style="width:13px;height:13px;flex-shrink:0;"></i>
                        <span class="text-truncate"><?= htmlspecialchars($customer['alamat_lengkap'] ?? 'Alamat belum diatur') ?></span>
                    </div>
                </div>
            </div>

            <!-- Quick Contacts & Actions -->
            <div class="opname-header-actions">
                <?php if (!empty($customer['nomor_whatsapp'])): ?>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $customer['nomor_whatsapp']) ?>" 
                   target="_blank" 
                   class="opname-btn-wa">
                    <i data-lucide="phone" style="width:14px;height:14px;"></i>
                    <span>Hubungi WA</span>
                </a>
                <?php endif; ?>

                <button type="button" 
                        @click="showGuide = !showGuide"
                        class="opname-btn-guide"
                        :class="showGuide ? 'is-active' : ''"
                        title="Panduan Rumus & Bantuan">
                    <i data-lucide="help-circle" style="width:18px;height:18px;"></i>
                </button>
            </div>

        </div>

        <!-- COLLAPSIBLE PANDUAN RINGKAS -->
        <div x-show="showGuide" x-cloak class="opname-guide-box" style="margin-top:14px;background:rgba(2,132,199,0.06);border:1px solid rgba(2,132,199,0.2);border-radius:16px;padding:14px 16px;">
            <div style="display:flex;align-items:flex-start;gap:12px;width:100%;">
                <div style="width:32px;height:32px;border-radius:10px;background:rgba(2,132,199,0.15);color:#0284c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="calculator" style="width:18px;height:18px;stroke:#0284c7;stroke-width:2.2;"></i>
                </div>
                <div class="opname-guide-content" style="flex:1;min-width:0;">
                    <div class="opname-guide-title" style="font-size:13px;font-weight:900;color:#0284c7;margin-bottom:6px;">
                        Panduan Sistem Opname &amp; Alur Rolling Nota Konsinyasi (8 Kolom)
                    </div>
                    <div class="opname-guide-body" style="font-size:12px;line-height:1.6;color:var(--color-ink-secondary);">
                        <p style="margin-bottom:8px;">
                            Sistem konsinyasi KEREN ONE menerapkan <strong>Alur Rolling Antar-Nota</strong>: data SKU dan jumlah kiriman otomatis ditarik dari PO/Nota kiriman terkait, dan saldo rak fisik dihitung berkesinambungan.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 my-2.5">
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs">
                                <strong class="text-sky-700 dark:text-sky-300">1. Sisa Stok Lalu:</strong>
                                <span class="text-slate-600 dark:text-slate-300 ml-1">Sisa fisik di rak dari nota sebelumnya.</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs">
                                <strong class="text-emerald-700 dark:text-emerald-300">2. Kirim Hari Ini:</strong>
                                <span class="text-slate-600 dark:text-slate-300 ml-1">Kuantitas otomatis dari PO kiriman terpilih.</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs">
                                <strong class="text-indigo-700 dark:text-indigo-300">3. Jumlah Titip:</strong>
                                <span class="text-slate-600 dark:text-slate-300 ml-1">Total modal rak = <em>Sisa Lalu + Kirim Baru</em>.</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs">
                                <strong class="text-rose-700 dark:text-rose-300">4. Retur Rusak (BS):</strong>
                                <span class="text-slate-600 dark:text-slate-300 ml-1">Barang bocor/BS (tidak ditagih ke toko).</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs">
                                <strong class="text-slate-800 dark:text-slate-200">5. Sisa di Rak:</strong>
                                <span class="text-slate-600 dark:text-slate-300 ml-1">Fisik aktual saat ini di toko mitra.</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs">
                                <strong class="text-emerald-600 dark:text-emerald-400">6. Laku Terjual:</strong>
                                <span class="text-slate-600 dark:text-slate-300 ml-1">Rumus = <em>Titip &minus; (Retur Rusak + Sisa Rak)</em>.</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs">
                                <strong class="text-sky-600 dark:text-sky-400">7. Retur Bagus:</strong>
                                <span class="text-slate-600 dark:text-slate-300 ml-1">Produk ditarik kembali ke gudang pusat.</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs">
                                <strong class="text-amber-700 dark:text-amber-300">8. Selisih Rak:</strong>
                                <span class="text-slate-600 dark:text-slate-300 ml-1">Minus = hilang (gantung); Plus = surplus.</span>
                            </div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-sky-500/10 border border-sky-500/20 text-xs mt-2">
                            <div class="font-bold text-sky-800 dark:text-sky-200 mb-1">Rumus Tagihan Penjualan:</div>
                            <div class="font-mono font-bold text-[11.5px] text-slate-800 dark:text-slate-100 break-words leading-relaxed">
                                Tagihan = [ (Sisa Lalu + Kirim Hari Ini) &minus; (Retur Rusak + Sisa Rak) ] &times; Harga Satuan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PENGATURAN OPERATOR: SALES PEMBINA TOKO & DRIVER PENGIRIM FISIK -->
    <div style="background:var(--color-surface);border:1px solid var(--color-hairline);border-radius:18px;padding:14px 18px;box-shadow:0 1px 3px rgba(0,0,0,0.02);">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
            <div>
                <label style="display:flex;align-items:center;gap:6px;font-size:11px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;margin-bottom:4px;">
                    <i data-lucide="user-check" style="width:14px;height:14px;color:#10b981;"></i>
                    <span>Sales Pembina Toko (Terkunci Sistem):</span>
                </label>
                <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:10px;font-size:13px;font-weight:700;color:var(--color-ink);">
                    <span><?= htmlspecialchars($customer['nama_sales'] ?? 'Sales Lapangan') ?></span>
                    <span class="badge badge-success" style="font-size:9.5px;margin-left:auto;">PIC Komisi</span>
                </div>
            </div>
            <div>
                <label style="display:flex;align-items:center;gap:6px;font-size:11px;font-weight:800;color:var(--color-ink-mute);text-transform:uppercase;margin-bottom:4px;">
                    <i data-lucide="truck" style="width:14px;height:14px;color:#0284c7;"></i>
                    <span>Driver Pengirim Fisik (Muatan Toko):</span>
                </label>
                <?php if ($tipeKonsinyasi === 'rolling_nota' && !empty($selectedOrder)): ?>
                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:10px;font-size:13px;font-weight:700;color:var(--color-ink);">
                        <span><?= htmlspecialchars(!empty($selectedOrder['nama_driver']) ? $selectedOrder['nama_driver'] : 'Dikirim oleh Sales / Tanpa Driver Khusus') ?></span>
                        <span class="badge" style="font-size:9.5px;margin-left:auto;background:rgba(2,132,199,0.12);color:#0284c7;border:1px solid rgba(2,132,199,0.25);">Sesuai Nota PO</span>
                    </div>
                    <input type="hidden" name="driver_pengirim_id" form="opnameForm" value="<?= htmlspecialchars((string)($selectedOrder['sales_driver_id'] ?? '')) ?>">
                <?php else: ?>
                    <select name="driver_pengirim_id" form="opnameForm" style="width:100%;height:38px;padding:6px 12px;border-radius:10px;border:1px solid var(--color-hairline);background:var(--color-surface);font-size:13px;font-weight:600;color:var(--color-ink);outline:none;">
                        <option value="">-- Dikirim oleh Sales / Tanpa Driver Khusus --</option>
                        <?php if (!empty($drivers)): ?>
                            <?php foreach ($drivers as $drv): ?>
                            <option value="<?= $drv['id'] ?>" <?= (!empty($selectedOrder['sales_driver_id']) && $selectedOrder['sales_driver_id'] === $drv['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($drv['nama_karyawan']) ?> (<?= htmlspecialchars($drv['posisi'] ?? 'Driver') ?>)
                            </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JIKA TOKO BELUM PERNAH ADA TITIPAN -->
    <?php if (empty($items)): ?>
    <div class="card text-center" style="border-radius:20px;border:1px solid var(--color-hairline);padding:32px 16px;">
        <div style="width:56px;height:56px;border-radius:16px;margin:0 auto 12px;background:var(--color-canvas-soft);display:flex;align-items:center;justify-content:center;">
            <i data-lucide="package-open" style="width:28px;height:28px;color:var(--color-ink-mute);"></i>
        </div>
        <h3 style="font-size:16px;font-weight:800;color:var(--color-ink);margin:0 0 6px;">Toko Ini Belum Memiliki Barang Titipan</h3>
        <p style="font-size:12px;color:var(--color-ink-mute);max-width:400px;margin:0 auto 16px;">
            Belum ada saldo rak konsinyasi yang tercatat untuk toko ini. Buat pesanan pengiriman titip jual pertama kali lewat menu Pesanan Pelanggan.
        </p>
        <a href="<?= Router::url('/customer-orders/create') ?>" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:12px;font-weight:700;">
            <i data-lucide="plus-circle" style="width:16px;height:16px;"></i>
            <span>Buat Pengiriman Pertama</span>
        </a>
    </div>
    <?php else: ?>

    <!-- ========================================================================= -->
    <!-- 2. QUICK KPI SUMMARY CHIPS & METRICS STRIP (6 METRICS)                    -->
    <!-- ========================================================================= -->
    <div class="opname-kpi-grid">
        
        <!-- Metric 1: Total SKU -->
        <div class="opname-kpi-card">
            <div class="opname-kpi-icon" style="background:rgba(2,132,199,0.1);color:#0284c7;">
                <i data-lucide="layers" style="width:18px;height:18px;"></i>
            </div>
            <div class="opname-kpi-text">
                <span class="opname-kpi-label">Total SKU Rak</span>
                <span class="opname-kpi-value"><?= $totalItemsCount ?> Produk</span>
            </div>
        </div>

        <!-- Metric 2: Total Stok Titip -->
        <div class="opname-kpi-card">
            <div class="opname-kpi-icon" style="background:rgba(99,102,241,0.1);color:#6366f1;">
                <i data-lucide="box" style="width:18px;height:18px;"></i>
            </div>
            <div class="opname-kpi-text">
                <span class="opname-kpi-label">Stok Titip Sistem</span>
                <span class="opname-kpi-value" style="color:#6366f1;"><?= number_format((float)$totalStokTitipAwal) ?> pcs</span>
            </div>
        </div>

        <!-- Metric 3: Estimasi Laku -->
        <div class="opname-kpi-card">
            <div class="opname-kpi-icon" style="background:rgba(16,185,129,0.12);color:#10b981;">
                <i data-lucide="trending-up" style="width:18px;height:18px;"></i>
            </div>
            <div class="opname-kpi-text">
                <span class="opname-kpi-label">Total Laku Terjual</span>
                <span class="opname-kpi-value" style="color:#10b981;" x-text="grandTotalLakuPcs + ' pcs'">0 pcs</span>
            </div>
        </div>

        <!-- Metric 4: Retur Rusak (BS) -->
        <div class="opname-kpi-card">
            <div class="opname-kpi-icon" 
                 :style="grandTotalRusakPcs > 0 ? 'background:rgba(244,63,94,0.12);color:#f43f5e;' : 'background:var(--color-canvas-soft);color:var(--color-ink-mute);'">
                <i data-lucide="alert-triangle" style="width:18px;height:18px;"></i>
            </div>
            <div class="opname-kpi-text">
                <span class="opname-kpi-label">Retur Rusak (BS)</span>
                <span class="opname-kpi-value" 
                      :style="grandTotalRusakPcs > 0 ? 'color:#f43f5e;' : ''" 
                      x-text="grandTotalRusakPcs + ' pcs'">0 pcs</span>
            </div>
        </div>

        <!-- Metric 5: Retur Bagus (Tarik Gudang) -->
        <div class="opname-kpi-card">
            <div class="opname-kpi-icon" 
                 :style="grandTotalBagusPcs > 0 ? 'background:rgba(2,132,199,0.12);color:#0284c7;' : 'background:var(--color-canvas-soft);color:var(--color-ink-mute);'">
                <i data-lucide="package-minus" style="width:18px;height:18px;"></i>
            </div>
            <div class="opname-kpi-text">
                <span class="opname-kpi-label">Retur Bagus</span>
                <span class="opname-kpi-value" 
                      :style="grandTotalBagusPcs > 0 ? 'color:#0284c7;' : ''" 
                      x-text="grandTotalBagusPcs + ' pcs'">0 pcs</span>
            </div>
        </div>

        <!-- Metric 6: Selisih Rak (Signed Hilang/Ketemu) -->
        <div class="opname-kpi-card">
            <div class="opname-kpi-icon" 
                 :style="grandTotalSelisihPcs !== 0 ? (grandTotalSelisihPcs < 0 ? 'background:rgba(245,158,11,0.12);color:#d97706;' : 'background:rgba(16,185,129,0.12);color:#10b981;') : 'background:var(--color-canvas-soft);color:var(--color-ink-mute);'">
                <i data-lucide="scale" style="width:18px;height:18px;"></i>
            </div>
            <div class="opname-kpi-text">
                <span class="opname-kpi-label">Selisih Rak</span>
                <span class="opname-kpi-value" 
                      :style="grandTotalSelisihPcs < 0 ? 'color:#d97706;' : (grandTotalSelisihPcs > 0 ? 'color:#10b981;' : '')" 
                      x-text="grandTotalSelisihPcs === 0 ? '0 pcs' : (grandTotalSelisihPcs > 0 ? '+' + grandTotalSelisihPcs + ' pcs' : grandTotalSelisihPcs + ' pcs')">0 pcs</span>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 3. SEARCH, FILTER CHIPS & QUICK ACTIONS TOOLBAR                           -->
    <!-- ========================================================================= -->
    <div class="opname-toolbar-box">
        <div class="opname-toolbar-row">
            
            <!-- Search Bar -->
            <div class="opname-search-bar">
                <i data-lucide="search" class="opname-search-icon"></i>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Cari nama snack atau kode SKU..." 
                       class="opname-search-input">
                <button type="button" 
                       x-show="searchQuery" 
                       @click="searchQuery = ''" 
                       class="opname-search-clear"
                       title="Hapus pencarian">
                    <i data-lucide="x" style="width:14px;height:14px;"></i>
                </button>
            </div>

            <!-- Batch Quick Helpers -->
            <div class="opname-batch-actions">
                <button type="button" 
                        @click="setAllIntact()" 
                        class="opname-btn-batch"
                        title="Isi otomatis semua sisa fisik sama dengan stok titip rak (0 Laku)">
                    <i data-lucide="check-check" style="width:15px;height:15px;color:#0284c7;"></i>
                    <span>Set Semua Utuh</span>
                </button>

                <button type="button" 
                        @click="resetAll()" 
                        class="opname-btn-batch"
                        title="Kembalikan semua hitungan ke kondisi awal">
                    <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i>
                    <span>Reset</span>
                </button>
            </div>
        </div>

        <!-- Filter Chips (Segmented Pill Style) -->
        <div class="opname-chips-track">
            <button type="button" 
                    @click="activeTab = 'all'" 
                    class="opname-chip"
                    :class="activeTab === 'all' ? 'active-all' : ''">
                Semua (<?= $totalItemsCount ?>)
            </button>
            <button type="button" 
                    @click="activeTab = 'sold'" 
                    class="opname-chip"
                    :class="activeTab === 'sold' ? 'active-sold' : ''">
                <span>Ada Laku</span>
                <span class="opname-chip-count" 
                      :style="activeTab === 'sold' ? 'background:rgba(255,255,255,0.25);color:#fff;' : 'background:rgba(16,185,129,0.15);color:#10b981;'"
                      x-text="soldCount"></span>
            </button>
            <button type="button" 
                    @click="activeTab = 'intact'" 
                    class="opname-chip"
                    :class="activeTab === 'intact' ? 'active-intact' : ''">
                Utuh (0 Laku)
            </button>
            <button type="button" 
                    @click="activeTab = 'return'" 
                    class="opname-chip"
                    :class="activeTab === 'return' ? 'active-return' : ''">
                <span>Ada Retur</span>
                <span class="opname-chip-count" 
                      :style="activeTab === 'return' ? 'background:rgba(255,255,255,0.25);color:#fff;' : 'background:rgba(244,63,94,0.15);color:#f43f5e;'"
                      x-text="returnCount"></span>
            </button>
            <button type="button" 
                    @click="activeTab = 'discrepancy'" 
                    class="opname-chip"
                    :class="activeTab === 'discrepancy' ? 'active-warn' : ''">
                <span>Ada Selisih</span>
                <span class="opname-chip-count" 
                      :style="activeTab === 'discrepancy' ? 'background:rgba(255,255,255,0.25);color:#fff;' : 'background:rgba(245,158,11,0.15);color:#d97706;'"
                      x-text="discrepancyCount"></span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. SKU PRODUCT CARDS LIST (OPTION B: 4-BOX OPEN COLUMNS)                  -->
    <!-- ========================================================================= -->
    <form id="opnameForm" 
          action="<?= Router::url('/consignment/opname/proses') ?>" 
          method="POST" 
          enctype="multipart/form-data" 
          @keydown.enter.prevent 
          data-action-text="Menyimpan kunjungan & nota konsinyasi...">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="pelanggan_id" value="<?= htmlspecialchars((string)$customer['id']) ?>">
        <?php if (!empty($selectedOrder)): ?>
        <input type="hidden" name="pesanan_id" value="<?= htmlspecialchars((string)$selectedOrder['id']) ?>">
        <?php endif; ?>
        <input type="hidden" name="items_json" :value="JSON.stringify(items)">
        <input type="hidden" name="nominal_bayar" :value="nominal_bayar">
        <input type="hidden" name="akun_kas_id" :value="akun_kas_id">
        <input type="hidden" name="catatan_bayar" :value="catatan_bayar">

        <?php 
        $tipeKonsinyasi = $customer['tipe_konsinyasi'] ?? 'rolling_nota';
        if (!empty($selectedOrder)): 
        ?>
        <div style="background:linear-gradient(135deg, rgba(2, 132, 199, 0.08) 0%, rgba(14, 165, 233, 0.04) 100%);border:1.5px solid rgba(2, 132, 199, 0.35);border-radius:18px;padding:14px 18px;margin-bottom:16px;box-shadow:0 2px 8px rgba(2, 132, 199, 0.06);" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
            <div class="flex items-center gap-3.5">
                <div style="width:42px;height:42px;border-radius:12px;background:#0284c7;color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 3px 10px rgba(2,132,199,0.35);">
                    <i data-lucide="package-check" style="width:22px;height:22px;"></i>
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span style="font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:0.04em;color:#0369a1;">Opname Saldo Berjalan (Terkunci PO)</span>
                        <span style="font-family:var(--font-mono);font-size:12px;font-weight:900;padding:3px 10px;border-radius:8px;background:#0284c7;color:#ffffff;letter-spacing:0.02em;box-shadow:0 2px 6px rgba(2,132,199,0.25);">
                            <?= htmlspecialchars($selectedOrder['nomor_nota']) ?>
                        </span>
                        <span style="font-size:10px;font-weight:800;padding:2px 7px;border-radius:6px;background:rgba(2,132,199,0.15);color:#0369a1;">Tipe 2 (Rolling Nota)</span>
                    </div>
                    <div style="font-size:12px;color:var(--color-ink-secondary);margin-top:3px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                        <span>Kiriman Tanggal: <strong style="color:var(--color-ink);"><?= date('d/m/Y', strtotime($selectedOrder['tanggal_pesanan'])) ?></strong></span>
                        <span style="color:var(--color-ink-mute);">&bull;</span>
                        <span style="color:#0284c7;font-weight:600;">Nilai Kirim Baru terisi otomatis dari nota PO ini</span>
                    </div>
                </div>
            </div>
            <?php if ($tipeKonsinyasi === 'kolektif_tagihan'): ?>
            <div class="flex items-center gap-2 self-start sm:self-center">
                <a href="<?= Router::url('/consignment/opname?pelanggan_id=' . urlencode((string)$customer['id'])) ?>" 
                   style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:10px;background:var(--color-surface);border:1.5px solid var(--color-hairline);color:var(--color-ink);font-size:12px;font-weight:800;text-decoration:none;box-shadow:0 1px 3px rgba(0,0,0,0.04);transition:all 0.15s ease;"
                   title="Beralih ke mode opname reguler (tanpa mengunci PO tertentu)">
                    <i data-lucide="rotate-ccw" style="width:13px;height:13px;color:#0284c7;"></i>
                    <span>Mode Opname Bebas</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php elseif ($tipeKonsinyasi === 'kolektif_tagihan'): ?>
        <div style="background:linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(129, 140, 248, 0.04) 100%);border:1.5px solid rgba(99, 102, 241, 0.3);border-radius:18px;padding:14px 18px;margin-bottom:16px;" class="flex items-center gap-3.5">
            <div style="width:40px;height:40px;border-radius:12px;background:#6366f1;color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-lucide="calculator" style="width:20px;height:20px;"></i>
            </div>
            <div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:12px;font-weight:900;text-transform:uppercase;color:#4338ca;">Mode Opname Kolektif Toko</span>
                    <span style="font-size:10px;font-weight:800;padding:2px 8px;border-radius:6px;background:rgba(99,102,241,0.15);color:#4338ca;">Tipe 1 (Reguler)</span>
                </div>
                <div style="font-size:11.5px;color:var(--color-ink-secondary);margin-top:2px;">
                    Opname fisik menyeluruh rak toko tanpa penguncian nomor PO kiriman tertentu.
                </div>
            </div>
        </div>
        <?php elseif (!empty($storeUnbilledOrders)): ?>
        <div class="p-3 mb-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 border border-amber-500/30 bg-amber-500/5 shadow-sm">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="file-clock" class="w-4 h-4"></i>
                </div>
                <div class="text-xs text-amber-800 dark:text-amber-200">
                    Terdapat <strong><?= count($storeUnbilledOrders) ?> PO kiriman</strong> yang belum ada nilai tagihannya. Pilih PO jika kunjungan ini menagih kiriman tersebut:
                </div>
            </div>
            <div class="flex items-center gap-1.5 flex-wrap">
                <?php foreach ($storeUnbilledOrders as $unb): ?>
                <a href="<?= Router::url('/consignment/opname?pelanggan_id=' . urlencode((string)$customer['id']) . '&pesanan_id=' . urlencode((string)$unb['pesanan_id'])) ?>" 
                   class="btn btn-sm py-1 px-2.5 rounded-lg text-[11px] font-bold font-mono bg-amber-500/20 hover:bg-amber-500/30 text-amber-800 dark:text-amber-200 border border-amber-500/30 transition-colors inline-flex items-center gap-1">
                    <i data-lucide="tag" class="w-3 h-3"></i>
                    <span><?= htmlspecialchars($unb['nomor_nota']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="opname-cards-stack">
            <template x-for="(item, index) in items" :key="item.item_id">
                <div x-show="filterItem(item)" 
                     class="opname-card"
                     :class="{
                         'is-sold': item.jumlah_laku > 0,
                         'is-touched': item.is_touched && item.jumlah_laku === 0
                     }">

                    <!-- TOP SECTION: PRODUCT INFO & STOCK FORMULA -->
                    <template x-if="tipeKonsinyasi === 'kolektif_tagihan'">
                        <div class="opname-card-head" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                            <div class="opname-card-info">
                                <div class="opname-card-tags">
                                    <span class="opname-card-sku" x-text="'[' + (item.barcode_universal && item.barcode_universal !== '-' ? item.barcode_universal : item.kode_sku) + ']'"></span>
                                    <span class="opname-card-price" x-text="'@ ' + formatRupiah(item.harga_deal)"></span>
                                    <template x-if="item.stok_titip_saat_ini === 0">
                                        <span class="opname-card-empty-badge">Rak Kosong</span>
                                    </template>
                                    <template x-if="(item.stok_hilang_pending || 0) > 0">
                                        <span class="opname-card-pending-badge" :title="'Terdapat ' + item.stok_hilang_pending + ' pcs barang hilang gantung dari kunjungan sebelumnya'">
                                            <i data-lucide="help-circle" style="width:11px;height:11px;"></i>
                                            <span x-text="'Gantung: ' + item.stok_hilang_pending + ' pcs'"></span>
                                        </span>
                                    </template>
                                </div>
                                <h3 class="opname-card-title" x-text="item.nama_item"></h3>
                                <template x-if="item.varian_text">
                                    <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;display:flex;align-items:center;gap:4px;">
                                        <span style="font-weight:700;color:var(--color-ink-secondary);">Varian:</span>
                                        <span x-text="item.varian_text"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Baseline Stok Titip Rak Pill untuk Mode Kolektif Toko -->
                            <div class="opname-card-baseline">
                                <span class="opname-baseline-label">Stok Titip:</span>
                                <span class="opname-baseline-pill">
                                    <i data-lucide="package" style="width:14px;height:14px;"></i>
                                    <span x-text="item.stok_titip_saat_ini + ' ' + item.satuan_dasar"></span>
                                </span>
                            </div>
                        </div>
                    </template>

                    <template x-if="tipeKonsinyasi !== 'kolektif_tagihan'">
                        <div class="opname-card-head">
                            <div class="opname-card-info">
                                <div class="opname-card-tags">
                                    <span class="opname-card-sku" x-text="'[' + (item.barcode_universal && item.barcode_universal !== '-' ? item.barcode_universal : item.kode_sku) + ']'"></span>
                                    <span class="opname-card-price" x-text="'@ ' + formatRupiah(item.harga_deal)"></span>
                                    <template x-if="item.stok_titip_saat_ini === 0">
                                        <span class="opname-card-empty-badge">Rak Kosong</span>
                                    </template>
                                    <template x-if="(item.stok_hilang_pending || 0) > 0">
                                        <span class="opname-card-pending-badge" :title="'Terdapat ' + item.stok_hilang_pending + ' pcs barang hilang gantung dari kunjungan sebelumnya'">
                                            <i data-lucide="help-circle" style="width:11px;height:11px;"></i>
                                            <span x-text="'Gantung: ' + item.stok_hilang_pending + ' pcs'"></span>
                                        </span>
                                    </template>
                                </div>
                                <h3 class="opname-card-title" x-text="item.nama_item"></h3>
                                <template x-if="item.varian_text">
                                    <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;display:flex;align-items:center;gap:4px;">
                                        <span style="font-weight:700;color:var(--color-ink-secondary);">Varian:</span>
                                        <span x-text="item.varian_text"></span>
                                    </div>
                                </template>

                                <!-- Horizontal Calculation Strip: Sisa Kiriman Lalu + Kirim Baru (PO) = Total Titipan -->
                                <div class="opname-formula-strip">
                                    <div class="opname-formula-badge is-sisa" title="Saldo sisa di rak dari nota pengiriman sebelumnya">
                                        <i data-lucide="history" class="w-3 h-3 flex-shrink-0 hidden sm:inline-block"></i>
                                        <span class="opname-formula-tag"><span class="hidden sm:inline">Sisa </span>Lalu:</span>
                                        <span class="opname-formula-val" x-text="item.stok_titip_saat_ini"></span>
                                        <span class="opname-formula-unit" x-text="item.satuan_dasar"></span>
                                    </div>

                                    <span class="opname-formula-op">+</span>

                                    <div class="opname-formula-badge is-kirim" title="Barang kiriman baru dari PO yang diopname">
                                        <i data-lucide="package-plus" class="w-3 h-3 flex-shrink-0 hidden sm:inline-block"></i>
                                        <span class="opname-formula-tag">Kirim<span class="hidden sm:inline"> (PO)</span>:</span>
                                        <span class="opname-formula-val" x-text="'+' + item.tambah_titip_baru"></span>
                                        <span class="opname-formula-unit" x-text="item.satuan_dasar"></span>
                                    </div>

                                    <span class="opname-formula-op">=</span>

                                    <div class="opname-formula-badge is-total" title="Total barang titipan di toko = Sisa Kiriman Lalu + Kirim Baru">
                                        <i data-lucide="layers" class="w-3 h-3 flex-shrink-0 hidden sm:inline-block"></i>
                                        <span class="opname-formula-tag">Total<span class="hidden sm:inline"> Titip</span>:</span>
                                        <span class="opname-formula-val" x-text="item.stok_titip_saat_ini + item.tambah_titip_baru"></span>
                                        <span class="opname-formula-unit" x-text="item.satuan_dasar"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- 5 OPEN INPUT BOXES GRID (SISA RAK, LAKU, RETUR RUSAK, RETUR BAGUS, SELISIH) -->
                    <div class="opname-penta-grid">
                        
                        <!-- Box 1: Sisa Fisik di Rak -->
                        <div class="opname-input-group opname-col-sisa">
                            <label class="opname-field-label">
                                <span>Sisa di Rak</span>
                                <span style="font-size:10px;color:var(--color-ink-mute);" x-text="item.satuan_dasar"></span>
                            </label>
                            <div class="opname-stepper">
                                <button type="button" 
                                        @click="decrementSisa(item)"
                                        class="opname-stepper-btn"
                                        title="Kurang 1 Sisa">−</button>

                                <input type="number" 
                                       inputmode="numeric" 
                                       pattern="[0-9]*" 
                                       min="0"
                                       x-model.number="item.sisa_fisik_di_rak" 
                                       @focus="$event.target.select()"
                                       @click="$event.target.select()"
                                       @keydown="handleStepperKeydown($event, item, 'sisa_fisik_di_rak')"
                                       @input="handleStepperInput($event, item, 'sisa_fisik_di_rak')"
                                       @blur="handleStepperBlur(item, 'sisa_fisik_di_rak', $event)"
                                       class="opname-stepper-input">

                                <button type="button" 
                                        @click="incrementSisa(item)"
                                        class="opname-stepper-btn"
                                        title="Tambah 1 Sisa">+</button>
                            </div>
                            <span class="opname-field-hint">Fisik dihitung di toko</span>
                        </div>

                        <!-- Box 2: Laku Terjual -->
                        <div class="opname-input-group opname-col-laku">
                            <label class="opname-field-label">
                                <span style="color:#10b981;">Laku Terjual</span>
                                <span style="font-size:10px;color:#10b981;" x-text="item.satuan_dasar"></span>
                            </label>
                            <div class="opname-stepper is-laku-stepper">
                                <button type="button" 
                                        @click="decrementLaku(item)"
                                        class="opname-stepper-btn is-laku-btn"
                                        title="Kurang 1 Laku">−</button>

                                <input type="number" 
                                       inputmode="numeric" 
                                       pattern="[0-9]*" 
                                       min="0"
                                       x-model.number="item.jumlah_laku" 
                                       @focus="$event.target.select()"
                                       @click="$event.target.select()"
                                       @keydown="handleStepperKeydown($event, item, 'jumlah_laku')"
                                       @input="handleStepperInput($event, item, 'jumlah_laku')"
                                       @blur="handleStepperBlur(item, 'jumlah_laku', $event)"
                                       class="opname-stepper-input is-laku-input">

                                <button type="button" 
                                        @click="incrementLaku(item)"
                                        class="opname-stepper-btn is-laku-btn"
                                        title="Tambah 1 Laku">+</button>
                            </div>
                            <span class="opname-field-hint" style="color:#10b981;font-weight:700;" x-text="formatRupiah(item.jumlah_laku * item.harga_deal)"></span>
                        </div>

                        <!-- Box 3: Retur Rusak (BS) -->
                        <div class="opname-input-group opname-col-rusak">
                            <label class="opname-field-label">
                                <span style="color:#f43f5e;">Retur Rusak (BS)</span>
                                <span style="font-size:10px;color:#f43f5e;" x-text="item.satuan_dasar"></span>
                            </label>
                            <div class="opname-stepper is-rusak-stepper">
                                <button type="button" 
                                        @click="decrementRetur(item, 'retur_rusak')"
                                        class="opname-stepper-btn is-rusak-btn"
                                        title="Kurang 1 Rusak">−</button>

                                <input type="number" 
                                       inputmode="numeric" 
                                       pattern="[0-9]*" 
                                       min="0"
                                       x-model.number="item.retur_rusak" 
                                       @focus="$event.target.select()"
                                       @click="$event.target.select()"
                                       @keydown="handleStepperKeydown($event, item, 'retur_rusak')"
                                       @input="handleStepperInput($event, item, 'retur_rusak')"
                                       @blur="handleStepperBlur(item, 'retur_rusak', $event)"
                                       class="opname-stepper-input"
                                       :style="item.retur_rusak > 0 ? 'color:#f43f5e !important;' : ''">

                                <button type="button" 
                                        @click="incrementRetur(item, 'retur_rusak')"
                                        class="opname-stepper-btn is-rusak-btn"
                                        title="Tambah 1 Rusak">+</button>
                            </div>
                            <span class="opname-field-hint" :style="item.retur_rusak > 0 ? 'color:#f43f5e;' : ''">Bocor / BS (HPP)</span>
                        </div>

                        <!-- Box 4: Retur Bagus (Tarik Gudang) -->
                        <div class="opname-input-group opname-col-bagus">
                            <label class="opname-field-label">
                                <span style="color:#0284c7;">Retur Bagus</span>
                                <span style="font-size:10px;color:#0284c7;" x-text="item.satuan_dasar"></span>
                            </label>
                            <div class="opname-stepper is-bagus-stepper">
                                <button type="button" 
                                        @click="decrementRetur(item, 'retur_bagus')"
                                        class="opname-stepper-btn is-bagus-btn"
                                        title="Kurang 1 Retur Bagus">−</button>

                                <input type="number" 
                                       inputmode="numeric" 
                                       pattern="[0-9]*" 
                                       min="0"
                                       x-model.number="item.retur_bagus" 
                                       @focus="$event.target.select()"
                                       @click="$event.target.select()"
                                       @keydown="handleStepperKeydown($event, item, 'retur_bagus')"
                                       @input="handleStepperInput($event, item, 'retur_bagus')"
                                       @blur="handleStepperBlur(item, 'retur_bagus', $event)"
                                       class="opname-stepper-input is-bagus-input"
                                       :style="item.retur_bagus > 0 ? 'color:#0284c7 !important;' : ''">

                                <button type="button" 
                                        @click="incrementRetur(item, 'retur_bagus')"
                                        class="opname-stepper-btn is-bagus-btn"
                                        title="Tambah 1 Retur Bagus">+</button>
                            </div>
                            <span class="opname-field-hint" 
                                  :style="item.retur_bagus > 0 ? 'color:#0284c7;font-weight:700;' : ''"
                                  x-text="item.retur_bagus > 0 ? 'Tarik ' + item.retur_bagus + ' ' + item.satuan_dasar : 'Tarik ke gudang'"></span>
                        </div>

                        <!-- Box 5: Selisih Rak (Signed Hilang/Ketemu) -->
                        <div class="opname-input-group opname-col-selisih">
                            <label class="opname-field-label">
                                <span :style="(item.selisih_qty === '-' || item.selisih_qty < 0) ? 'color:#d97706;' : (item.selisih_qty > 0 ? 'color:#10b981;' : '')">Selisih Rak</span>
                                <span style="font-size:10px;" :style="(item.selisih_qty === '-' || item.selisih_qty < 0) ? 'color:#d97706;' : (item.selisih_qty > 0 ? 'color:#10b981;' : 'color:var(--color-ink-mute);')" x-text="item.satuan_dasar"></span>
                            </label>
                            <div class="opname-stepper" 
                                 :class="{
                                     'is-selisih-lost': item.selisih_qty === '-' || item.selisih_qty < 0,
                                     'is-selisih-found': item.selisih_qty > 0
                                 }">
                                <button type="button" 
                                        @click="decrementSelisih(item)"
                                        class="opname-stepper-btn is-selisih-btn"
                                        title="Kurang 1 Selisih">−</button>

                                <input type="text" 
                                       inputmode="text" 
                                       x-model="item.selisih_qty" 
                                       @focus="$event.target.select()"
                                       @click="$event.target.select()"
                                       @keydown="handleSelisihKeydown($event, item)"
                                       @input="handleSelisihInput($event, item)"
                                       @blur="handleSelisihBlur(item, $event)"
                                       class="opname-stepper-input"
                                       :style="(item.selisih_qty === '-' || item.selisih_qty < 0) ? 'color:#d97706 !important;' : (item.selisih_qty > 0 ? 'color:#10b981 !important;' : '')">

                                <button type="button" 
                                        @click="incrementSelisih(item)"
                                        class="opname-stepper-btn is-selisih-btn"
                                        title="Tambah 1 Selisih">+</button>
                            </div>
                            <span class="opname-field-hint font-bold" 
                                  :style="(item.selisih_qty === '-' || item.selisih_qty < 0) ? 'color:#d97706;' : (item.selisih_qty > 0 ? 'color:#10b981;' : '')" 
                                  x-text="item.selisih_qty === '-' ? 'Ketik angka minus...' : (item.selisih_qty < 0 ? 'Hilang ' + Math.abs(item.selisih_qty) + ' (Gantung)' : (item.selisih_qty > 0 ? 'Ketemu +' + item.selisih_qty + ' (Surplus)' : 'Seimbang (0)'))"></span>
                        </div>

                    </div>

                    <!-- CARD SUBROW: PRESETS & LIVE OUTCOME FEEDBACK -->
                    <div class="opname-card-subrow">
                        <!-- Quick Preset Action Buttons -->
                        <div class="opname-presets-wrap">
                            <button type="button" 
                                    @click="setHabis(item)" 
                                    class="opname-quick-btn btn-habis"
                                    title="Semua terjual habis (Sisa 0)">
                                <i data-lucide="check" style="width:13px;height:13px;"></i>
                                <span>Habis (0 Sisa)</span>
                            </button>
                            <button type="button" 
                                    @click="setUtuh(item)" 
                                    class="opname-quick-btn btn-utuh"
                                    title="Tidak ada yang terjual (Laku 0)">
                                <i data-lucide="rotate-ccw" style="width:12px;height:12px;"></i>
                                <span>Utuh (0 Laku)</span>
                            </button>
                        </div>

                        <!-- Live Outcome Status Pill (Hanya muncul saat ada penjualan atau barang selisih) -->
                        <div class="opname-outcome-wrap">
                            <template x-if="item.selisih_qty < 0">
                                <div class="opname-outcome-pill is-lost">
                                    <i data-lucide="alert-triangle" style="width:14px;height:14px;flex-shrink:0;"></i>
                                    <span>Hilang <strong x-text="Math.abs(item.selisih_qty) + ' ' + item.satuan_dasar"></strong> (Gantung / Tidak ditagihkan)</span>
                                </div>
                            </template>
                            <template x-if="item.selisih_qty > 0">
                                <div class="opname-outcome-pill is-found">
                                    <i data-lucide="sparkles" style="width:14px;height:14px;flex-shrink:0;"></i>
                                    <span>Ketemu <strong x-text="'+' + item.selisih_qty + ' ' + item.satuan_dasar"></strong> (Ditemukan Kembali)</span>
                                </div>
                            </template>
                            <template x-if="item.selisih_qty === 0 && item.jumlah_laku > 0">
                                <div class="opname-outcome-pill is-laku">
                                    <i data-lucide="check-circle" style="width:14px;height:14px;"></i>
                                    <span>Laku: <strong class="opname-val-mono" x-text="item.jumlah_laku + ' ' + item.satuan_dasar"></strong></span>
                                    <span style="opacity:0.4;">•</span>
                                    <span class="opname-val-mono font-bold" x-text="formatRupiah(item.jumlah_laku * item.harga_deal)"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </template>

            <!-- State Tidak Ada Hasil Pencarian -->
            <div x-show="filteredItemsCount === 0" 
                 x-cloak 
                 class="card text-center" 
                 style="border-radius:18px;border:1px solid var(--color-hairline);padding:36px 16px;text-align:center;">
                <i data-lucide="search-x" style="width:32px;height:32px;color:var(--color-ink-mute);margin:0 auto 8px;display:block;"></i>
                <p style="font-size:13px;font-weight:700;color:var(--color-ink);margin:0 0 10px;">Tidak ada produk yang cocok dengan pencarian / filter.</p>
                <button type="button" 
                        @click="searchQuery = ''; activeTab = 'all'" 
                        class="btn btn-secondary btn-sm"
                        style="padding:6px 14px;border-radius:10px;font-weight:700;font-size:12px;">
                    Reset Filter Pencarian
                </button>
            </div>
        </div>

        <!-- FOTO BUKTI KUNJUNGAN / RETUR BS (OPSIONAL) -->
        <div class="card" style="margin-top:16px;border-radius:18px;border:1px solid var(--color-hairline);background:var(--color-surface);padding:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:12px;">
                <div style="display:flex;align-items:center;gap:6px;min-width:0;flex:1;">
                    <i data-lucide="camera" style="width:16px;height:16px;color:#0284c7;flex-shrink:0;"></i>
                    <span style="font-size:12px;font-weight:800;color:var(--color-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Foto Bukti Kunjungan / BS</span>
                    <span style="font-size:10.5px;font-weight:600;color:var(--color-ink-mute);flex-shrink:0;">(Opsional)</span>
                </div>
            </div>

            <!-- Drop / Capture Zone -->
            <div>
                <input type="file" 
                       id="foto_kunjungan" 
                       name="foto_kunjungan" 
                       accept="image/*" 
                       capture="environment" 
                       class="hidden" 
                       @change="handlePhotoChange($event)">

                <div x-show="!photoPreview">
                    <label for="foto_kunjungan" 
                           style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:20px 16px;border:1.5px dashed var(--color-hairline-strong);border-radius:14px;background:var(--color-canvas-soft);cursor:pointer;transition:all 0.15s ease;"
                           class="hover:border-sky-500 active:scale-[0.99]">
                        <div style="width:44px;height:44px;border-radius:12px;background:rgba(2,132,199,0.1);color:#0284c7;display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                                <circle cx="12" cy="13" r="3"/>
                            </svg>
                        </div>
                        <div style="text-align:center;">
                            <strong style="font-size:12.5px;color:var(--color-ink);display:block;">Ambil Foto Kamera / Galeri</strong>
                            <span style="font-size:11px;color:var(--color-ink-mute);">Format JPG, PNG, WEBP (Maksimal 5MB)</span>
                        </div>
                    </label>
                </div>

                <div x-show="photoPreview" x-cloak>
                    <div style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);position:relative;overflow:hidden;">
                        <img :src="photoPreview" alt="Preview Foto" style="width:58px;height:58px;object-fit:cover;border-radius:10px;border:1px solid var(--color-hairline);flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                        <div style="min-width:0;flex:1;overflow:hidden;">
                            <div style="font-size:12px;font-weight:800;color:var(--color-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;" x-text="photoName || 'Foto Terpilih'"></div>
                            <div style="font-size:11px;font-weight:700;color:#10b981;display:flex;align-items:center;gap:4px;margin-top:2px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                <span>Foto siap diunggah</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:12px;margin-top:4px;">
                                <label for="foto_kunjungan" style="font-size:11px;font-weight:700;color:#0284c7;cursor:pointer;display:inline-flex;align-items:center;gap:3px;margin:0;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 2v6h-6"/>
                                        <path d="M3 12a9 9 0 0 1 15-6.7L21 8"/>
                                        <path d="M3 22v-6h6"/>
                                        <path d="M21 12a9 9 0 0 1-15 6.7L3 16"/>
                                    </svg>
                                    <span>Ganti</span>
                                </label>
                                <button type="button" @click="clearPhoto()" style="background:transparent;border:none;color:#f43f5e;font-size:11px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:3px;padding:0;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FIELD CATATAN KUNJUNGAN -->
        <div class="card" style="margin-top:16px;border-radius:18px;border:1px solid var(--color-hairline);background:var(--color-surface);padding:16px;">
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:800;color:var(--color-ink);margin-bottom:8px;">
                <i data-lucide="message-square" style="width:14px;height:14px;color:var(--color-ink-mute);"></i>
                <span>Catatan Kunjungan Lapangan (Opsional):</span>
            </label>
            <textarea name="catatan" 
                      rows="2" 
                      placeholder="Tuliskan catatan kondisi rak, pemilik toko, atau komitmen pembayaran berikutnya..." 
                      class="form-input" 
                      style="width:100%;box-sizing:border-box;padding:10px 12px;font-size:12px;border-radius:12px;border:1px solid var(--color-hairline);background:var(--color-canvas);"></textarea>
        </div>

        <!-- ========================================================================= -->
        <!-- 5. PENYELESAIAN OPNAME: POS CARD (ROLLING NOTA) vs FLOATING DOCK (KOLEKTIF) -->
        <!-- ========================================================================= -->
        <?php if ($tipeKonsinyasi === 'kolektif_tagihan'): ?>
        <!-- FLOATING DOCKED BOTTOM ACTION BAR (KHUSUS MODE OPNAME KOLEKTIF TOKO) -->
        <div class="opname-dock">
            <div class="opname-dock-inner">
                
                <!-- Left: Running Sales Total -->
                <div class="opname-dock-total">
                    <span class="opname-dock-label">
                        ESTIMASI LAKU TERJUAL:
                    </span>
                    <div class="opname-dock-val-row">
                        <span class="opname-dock-rp" x-text="formatRupiah(grandTotalLakuRp)">Rp 0</span>
                        <span class="opname-dock-pcs" x-text="'(' + grandTotalLakuPcs + ' pcs)'"></span>
                    </div>
                </div>

                <!-- Right: Submit Action Button -->
                <button type="button" 
                        @click="openConfirmModal()"
                        :disabled="hasAnyWarning"
                        class="opname-submit-btn"
                        :class="hasAnyWarning ? 'is-disabled' : ''">
                    <i data-lucide="clipboard-check" style="width:18px;height:18px;"></i>
                    <span>Submit Opname</span>
                    <span class="opname-submit-count" x-text="countedItemsCount + '/' + items.length"></span>
                </button>

            </div>
        </div>
        <?php else: ?>
        <!-- POS CHECKOUT CARD (KHUSUS ROLLING NOTA DENGAN PENAGIHAN KASIR TOKO) -->
        <div class="opname-pos-card">
            <!-- POS HEADER -->
            <div class="opname-pos-head">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:38px;height:38px;border-radius:12px;background:rgba(16,185,129,0.15);color:#059669;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="receipt" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <h3 style="font-size:14px;font-weight:900;color:var(--color-ink);margin:0;">Kasir &amp; Penyelesaian Opname</h3>
                        <p style="font-size:11.5px;color:var(--color-ink-mute);margin:2px 0 0 0;">Tentukan penerimaan kas toko mitra dan finalisasi cetak nota kunjungan</p>
                    </div>
                </div>

                <div class="hidden sm:flex items-center gap-2">
                    <span class="badge badge-secondary" style="font-size:11px;font-weight:800;padding:4px 10px;border-radius:8px;" x-text="countedItemsCount + ' dari ' + items.length + ' SKU terhitung'"></span>
                </div>
            </div>

            <!-- POS 2-PANEL GRID -->
            <div class="opname-pos-grid">
                
                <!-- PANEL KIRI: INPUT PEMBAYARAN DI TOKO -->
                <div class="opname-pos-panel-input">
                    <div style="font-size:11.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink);display:flex;align-items:center;gap:6px;padding-bottom:8px;border-bottom:1px solid var(--color-hairline);">
                        <i data-lucide="wallet" style="width:14px;height:14px;color:#059669;"></i>
                        <span>Penerimaan Pembayaran Toko</span>
                    </div>

                    <!-- 1. Akun Kas -->
                    <div>
                        <label class="opname-pos-label">
                            <i data-lucide="building-2" style="width:13px;height:13px;color:var(--color-ink-mute);"></i>
                            <span>Akun Kas Penerimaan:</span>
                        </label>
                        <select x-model="akun_kas_id" 
                                style="width:100%;height:42px;padding:6px 12px;font-size:12.5px;font-weight:700;border-radius:10px;background:var(--color-surface);border:1.5px solid var(--color-hairline);color:var(--color-ink);outline:none;">
                            <template x-for="acc in cashAccounts" :key="acc.id">
                                <option :value="acc.id" x-text="acc.nama_akun"></option>
                            </template>
                        </select>
                    </div>

                    <!-- 2. Nominal Bayar -->
                    <div>
                        <label class="opname-pos-label">
                            <i data-lucide="banknote" style="width:13px;height:13px;color:#059669;"></i>
                            <span>Jumlah Diterima (Rp):</span>
                        </label>
                        <div class="opname-pos-input-wrap">
                            <span class="opname-pos-prefix">Rp</span>
                            <input type="text" 
                                   inputmode="numeric"
                                   x-ref="inputBayar"
                                   :value="nominal_bayar_formatted" 
                                   @input="handleNominalBayarInput($event)"
                                   placeholder="0"
                                   @focus="$event.target.select()"
                                   class="opname-pos-input">
                        </div>

                        <!-- Quick Action Preset Buttons -->
                        <div class="opname-pos-quick-btns" style="margin-top:10px;">
                            <button type="button" 
                                    @click="setNominalBayar(grandTotalLakuRp)" 
                                    class="opname-pos-quick-btn is-pas"
                                    title="Isi pembayaran sama dengan total tagihan laku">
                                <i data-lucide="check" style="width:14px;height:14px;"></i>
                                <span>Bayar Pas (Lunas)</span>
                            </button>

                            <button type="button" 
                                    @click="nominal_bayar = 0; nominal_bayar_formatted = ''; $refs.inputBayar.focus();" 
                                    class="opname-pos-quick-btn is-sebagian"
                                    title="Ketik nominal pembayaran sebagian">
                                <i data-lucide="split" style="width:14px;height:14px;"></i>
                                <span>Bayar Sebagian</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PANEL KANAN: REKAPITULASI & SUBMIT NOTA -->
                <div class="opname-pos-panel-summary">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div style="font-size:11.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink);display:flex;align-items:center;justify-content:space-between;padding-bottom:8px;border-bottom:1px solid var(--color-hairline);">
                            <div style="display:flex;align-items:center;gap:6px;">
                                <i data-lucide="calculator" style="width:14px;height:14px;color:#0284c7;"></i>
                                <span>Rekapitulasi Transaksi</span>
                            </div>
                            <span style="font-size:11.5px;font-weight:800;color:#059669;" x-text="grandTotalLakuPcs + ' pcs laku'"></span>
                        </div>

                        <!-- Subtotal Laku -->
                        <div class="opname-pos-sum-row">
                            <span class="opname-pos-sum-label">Total Penjualan Laku:</span>
                            <span class="opname-pos-sum-val" style="color:#059669;font-size:15px;" x-text="formatRupiah(grandTotalLakuRp)">Rp 0</span>
                        </div>

                        <!-- Bayar Diterima -->
                        <div class="opname-pos-sum-row">
                            <span class="opname-pos-sum-label">Pembayaran Diterima:</span>
                            <span class="opname-pos-sum-val" x-text="formatRupiah(nominal_bayar || 0)">Rp 0</span>
                        </div>

                        <!-- Divider -->
                        <div style="border-top: 1px dashed var(--color-hairline); margin: 2px 0;"></div>

                        <!-- Sisa Piutang Nota (Sleek POS Total Row) -->
                        <div class="opname-pos-sum-row" style="align-items:center;">
                            <span style="font-size:12.5px;font-weight:900;text-transform:uppercase;letter-spacing:0.04em;" 
                                  :style="sisaPiutangRp > 0 ? 'color:#dc2626;' : 'color:#059669;'">
                                Sisa Piutang Nota:
                            </span>
                            <span style="font-family:var(--font-mono);font-size:18px;font-weight:900;" 
                                  :style="sisaPiutangRp > 0 ? 'color:#dc2626;' : 'color:#059669;'" 
                                  x-text="formatRupiah(sisaPiutangRp)">Rp 0</span>
                        </div>
                    </div>

                    <!-- Tombol Utama Submit -->
                    <div style="padding-top:6px;">
                        <button type="button" 
                                @click="openConfirmModal()"
                                :disabled="hasAnyWarning"
                                class="opname-pos-submit-btn"
                                :class="hasAnyWarning ? 'is-disabled' : ''">
                            <i data-lucide="printer" style="width:20px;height:20px;"></i>
                            <div style="text-align:left;flex:1;padding-left:4px;">
                                <div style="font-size:13.5px;font-weight:900;line-height:1.2;">Simpan &amp; Cetak Nota</div>
                                <div style="font-size:10.5px;font-weight:400;opacity:0.9;line-height:1.2;">Finalisasi stok rak &amp; rekam kunjungan</div>
                            </div>
                            <span class="opname-submit-count" x-text="countedItemsCount + '/' + items.length + ' SKU'"></span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
        <?php endif; ?>

    </form>

    <!-- ========================================================================= -->
    <!-- 6. MODAL KONFIRMASI SUBMIT (RESPONSIVE M3 BOTTOM SHEET / DIALOG)          -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="showConfirmModal" 
             x-cloak 
             class="modal-backdrop is-confirm-backdrop"
             style="display:flex;align-items:flex-end;justify-content:center;"
             @keydown.escape.window="showConfirmModal = false">
            
            <div class="modal-box is-confirm-box" 
                 style="width:100%;max-width:500px;border-top-left-radius:24px;border-top-right-radius:24px;padding:20px;box-shadow:0 -10px 40px rgba(0,0,0,0.2);background:var(--color-surface);border:1px solid var(--color-hairline);box-sizing:border-box;">
                
                <!-- Drag handle for mobile gesture recognition -->
                <div style="width:40px;height:5px;background:var(--color-hairline-strong);border-radius:9999px;margin:0 auto 14px;"></div>

                <!-- Modal Header -->
                <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid var(--color-hairline);margin-bottom:14px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:10px;background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="clipboard-check" style="width:18px;height:18px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:16px;font-weight:900;color:var(--color-ink);margin:0;">Konfirmasi Opname</h3>
                            <span style="font-size:11px;color:var(--color-ink-mute);display:block;"><?= htmlspecialchars($customer['nama_toko']) ?></span>
                        </div>
                    </div>
                    <button type="button" @click="showConfirmModal = false" style="background:transparent;border:none;color:var(--color-ink-mute);cursor:pointer;padding:4px;">
                        <i data-lucide="x" style="width:16px;height:16px;"></i>
                    </button>
                </div>

                <!-- Nihil Warning Notice -->
                <template x-if="grandTotalLakuPcs === 0 && grandTotalRusakPcs === 0">
                    <div style="padding:10px 12px;border-radius:12px;display:flex;align-items:flex-start;gap:8px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);color:#f59e0b;margin-bottom:12px;">
                        <i data-lucide="alert-triangle" style="width:16px;height:16px;flex-shrink:0;margin-top:2px;"></i>
                        <div style="font-size:12px;line-height:1.4;">
                            <strong style="display:block;">Nihil Penjualan:</strong>
                            <span style="font-size:11px;color:var(--color-ink-secondary);">Semua stok fisik rak tercatat utuh. Tidak ada barang laku untuk ditagihkan.</span>
                        </div>
                    </div>
                </template>

                <!-- Summary Table Details (Mode Kolektif Toko) -->
                <template x-if="tipeKonsinyasi === 'kolektif_tagihan'">
                    <div style="padding:14px;border-radius:14px;background:var(--color-canvas);border:1px solid var(--color-hairline);margin-bottom:16px;display:flex;flex-direction:column;gap:8px;font-size:12px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-ink-mute);">Total SKU Dihitung:</span>
                            <strong style="color:var(--color-ink);font-weight:800;font-family:var(--font-mono);" x-text="items.length + ' SKU'"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-ink-mute);">Total Barang Laku:</span>
                            <strong style="color:#10b981;font-weight:900;font-family:var(--font-mono);font-size:13.5px;" x-text="grandTotalLakuPcs + ' pcs (' + formatRupiah(grandTotalLakuRp) + ')'"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-ink-mute);">Total Retur Rusak (BS):</span>
                            <strong style="color:#f43f5e;font-weight:800;font-family:var(--font-mono);" x-text="grandTotalRusakPcs + ' pcs'"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-ink-mute);">Total Retur Bagus:</span>
                            <strong style="color:var(--color-ink);font-weight:800;font-family:var(--font-mono);" x-text="grandTotalBagusPcs + ' pcs'"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-ink-mute);">Status Selisih Rak:</span>
                            <template x-if="grandTotalSelisihPcs < 0">
                                <strong style="color:#d97706;font-weight:900;font-family:var(--font-mono);" x-text="'Hilang ' + Math.abs(grandTotalSelisihPcs) + ' pcs (Ditangguhkan)'"></strong>
                            </template>
                            <template x-if="grandTotalSelisihPcs > 0">
                                <strong style="color:#10b981;font-weight:900;font-family:var(--font-mono);" x-text="'Ditemukan +' + grandTotalSelisihPcs + ' pcs'"></strong>
                            </template>
                            <template x-if="grandTotalSelisihPcs === 0">
                                <strong style="color:var(--color-ink);font-weight:800;font-family:var(--font-mono);">0 (Seimbang)</strong>
                            </template>
                        </div>

                        <template x-if="grandTotalSelisihPcs < 0">
                            <div style="margin-top:4px;font-size:11px;color:#b45309;line-height:1.4;background:rgba(245,158,11,0.08);padding:8px 10px;border-radius:10px;border:1px solid rgba(245,158,11,0.2);">
                                <strong>Info Barang Selisih Hilang:</strong> Selisih kurang tidak ditagihkan ke mitra toko. Sistem mencatatnya sebagai stok pending hilang dan otomatis pulih saat barang ditemukan di kunjungan berikutnya.
                            </div>
                        </template>

                        <div style="margin-top:6px;font-size:11px;color:var(--color-ink-secondary);line-height:1.4;background:rgba(99,102,241,0.08);padding:8px 10px;border-radius:10px;border:1px solid rgba(99,102,241,0.2);">
                            <strong style="color:#4338ca;">Info Tagihan Kolektif:</strong> Nota tagihan tidak langsung terbit saat simpan opname. Faktur tagihan diterbitkan terpisah atau digabungkan melalui menu <strong>Tagihan Konsinyasi</strong>.
                        </div>
                    </div>
                </template>

                <!-- Summary Table Details (Mode Rolling Nota) -->
                <template x-if="tipeKonsinyasi !== 'kolektif_tagihan'">
                    <div style="padding:14px;border-radius:14px;background:var(--color-canvas);border:1px solid var(--color-hairline);margin-bottom:16px;display:flex;flex-direction:column;gap:8px;font-size:12px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-ink-mute);">Total SKU Dihitung:</span>
                            <strong style="color:var(--color-ink);font-weight:800;font-family:var(--font-mono);" x-text="items.length + ' SKU'"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-ink-mute);">Total Barang Laku:</span>
                            <strong style="color:#10b981;font-weight:900;font-family:var(--font-mono);font-size:13.5px;" x-text="grandTotalLakuPcs + ' pcs (' + formatRupiah(grandTotalLakuRp) + ')'"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-ink-mute);">Total Retur Rusak (BS):</span>
                            <strong style="color:#f43f5e;font-weight:800;font-family:var(--font-mono);" x-text="grandTotalRusakPcs + ' pcs'"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-ink-mute);">Total Retur Bagus:</span>
                            <strong style="color:var(--color-ink);font-weight:800;font-family:var(--font-mono);" x-text="grandTotalBagusPcs + ' pcs'"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-ink-mute);">Status Selisih Rak:</span>
                            <template x-if="grandTotalSelisihPcs < 0">
                                <strong style="color:#d97706;font-weight:900;font-family:var(--font-mono);" x-text="'Hilang ' + Math.abs(grandTotalSelisihPcs) + ' pcs (Ditangguhkan)'"></strong>
                            </template>
                            <template x-if="grandTotalSelisihPcs > 0">
                                <strong style="color:#10b981;font-weight:900;font-family:var(--font-mono);" x-text="'Ditemukan +' + grandTotalSelisihPcs + ' pcs'"></strong>
                            </template>
                            <template x-if="grandTotalSelisihPcs === 0">
                                <strong style="color:var(--color-ink);font-weight:800;font-family:var(--font-mono);">0 (Seimbang)</strong>
                            </template>
                        </div>

                        <template x-if="grandTotalSelisihPcs < 0">
                            <div style="margin-top:4px;font-size:11px;color:#b45309;line-height:1.4;background:rgba(245,158,11,0.08);padding:8px 10px;border-radius:10px;border:1px solid rgba(245,158,11,0.2);">
                                <strong>Info Barang Selisih Hilang:</strong> Selisih kurang tidak ditagihkan ke mitra toko. Sistem mencatatnya sebagai stok pending hilang dan otomatis pulih saat barang ditemukan di kunjungan berikutnya.
                            </div>
                        </template>

                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-ink-mute);">Kirim Hari Ini (Drop Baru):</span>
                            <strong style="color:#059669;font-weight:900;font-family:var(--font-mono);font-size:13px;" x-text="'+' + grandTotalDropPcs + ' pcs'"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-ink-mute);">Bayar di Toko:</span>
                            <strong style="color:#10b981;font-weight:900;font-family:var(--font-mono);" x-text="formatRupiah(nominal_bayar)"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-ink-mute);">Sisa Piutang:</span>
                            <strong :style="sisaPiutangRp > 0 ? 'color:#b91c1c;' : 'color:#10b981;'" style="font-weight:900;font-family:var(--font-mono);" x-text="formatRupiah(sisaPiutangRp)"></strong>
                        </div>

                        <div style="padding-top:8px;border-top:1px solid var(--color-hairline);margin-top:2px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <span style="color:var(--color-ink-mute);">Dokumen Keluar:</span>
                                <span style="font-size:12px;font-weight:800;color:#059669;">Nota Nusantara 8-Kolom</span>
                            </div>
                            <div style="margin-top:6px;font-size:11px;color:var(--color-ink-secondary);line-height:1.4;background:rgba(16,185,129,0.06);padding:6px 10px;border-radius:8px;border:1px solid rgba(16,185,129,0.15);">
                                <strong style="color:#059669;">Auto-Print Nota:</strong> Setelah disimpan, nota otomatis siap dicetak (mendukung printer Dot Matrix Full, Half 9.5"x5.5", atau PDF).
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Modal Actions -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <button type="button" 
                            @click="showConfirmModal = false" 
                            class="btn btn-secondary"
                            style="padding:12px;border-radius:12px;font-weight:700;font-size:12px;justify-content:center;">
                        Periksa Lagi
                    </button>
                    <button type="button" 
                            @click="submitForm()" 
                            class="btn btn-primary" 
                            style="padding:12px;border-radius:12px;font-weight:900;font-size:12px;background:#10b981;border-color:#10b981;color:#fff;justify-content:center;box-shadow:0 3px 10px rgba(16,185,129,0.3);"
                            x-text="tipeKonsinyasi === 'kolektif_tagihan' ? 'Konfirmasi & Simpan Opname' : 'Simpan & Cetak Nota'">
                    </button>
                </div>

            </div>
        </div>
    </template>

    <?php endif; ?>

</div>

<!-- ========================================================================= -->
<!-- 7. ALPINE.JS APP LOGIC & TWO-WAY DUAL INPUT SYNCHRONIZATION               -->
<!-- ========================================================================= -->
<script>
function opnameApp() {
    return {
        tipeKonsinyasi: '<?= htmlspecialchars($tipeKonsinyasi) ?>',
        searchQuery: '',
        activeTab: 'all',
        showGuide: false,
        showConfirmModal: false,
        photoPreview: null,
        photoName: '',
        nominal_bayar: 0,
        nominal_bayar_formatted: '0',
        akun_kas_id: '<?= !empty($cashAccounts) ? htmlspecialchars((string)$cashAccounts[0]['id']) : '' ?>',
        catatan_bayar: '',
        items: <?= json_encode($items ?? []) ?>,
        catalogItems: <?= json_encode($catalogItems ?? []) ?>,
        cashAccounts: <?= json_encode($cashAccounts ?? []) ?>,

        init() {
            this.items.forEach(item => {
                item.stok_titip_saat_ini = parseInt(item.stok_titip_saat_ini) || 0;
                item.stok_hilang_pending = parseInt(item.stok_hilang_pending) || 0;
                item.retur_bagus = parseInt(item.retur_bagus) || 0;
                item.retur_rusak = parseInt(item.retur_rusak) || 0;
                item.tambah_titip_baru = parseInt(item.tambah_titip_baru) || 0;
                item.showReturBagus = (item.retur_bagus > 0);

                if (item.sisa_fisik_di_rak !== undefined && item.sisa_fisik_di_rak !== null) {
                    item.sisa_fisik_di_rak = parseInt(item.sisa_fisik_di_rak);
                } else {
                    item.sisa_fisik_di_rak = 0;
                }

                const totalTitip = item.stok_titip_saat_ini + item.tambah_titip_baru;

                if (item.jumlah_laku !== undefined && item.jumlah_laku !== null) {
                    item.jumlah_laku = parseInt(item.jumlah_laku);
                } else {
                    item.jumlah_laku = Math.max(0, totalTitip - (item.sisa_fisik_di_rak + item.retur_bagus + item.retur_rusak));
                }

                if (item.selisih_qty !== undefined && item.selisih_qty !== null) {
                    item.selisih_qty = parseInt(item.selisih_qty);
                } else {
                    item.selisih_qty = (item.sisa_fisik_di_rak + item.jumlah_laku + item.retur_bagus + item.retur_rusak) - totalTitip;
                }
            });

            this.nominal_bayar_formatted = (this.nominal_bayar > 0) ? this.nominal_bayar.toLocaleString('id-ID') : '0';

            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
            this.$watch('searchQuery', () => this.$nextTick(() => window.lucide && window.lucide.createIcons()));
            this.$watch('activeTab', () => this.$nextTick(() => window.lucide && window.lucide.createIcons()));
            this.$watch('showConfirmModal', () => this.$nextTick(() => window.lucide && window.lucide.createIcons()));
        },

        // Helper: Input Cerdas (Otomatis mengganti 0 saat diketik, bukan 01 atau 10)
        handleStepperInput(e, item, field) {
            let val = e.target.value;
            if (val === '') {
                item[field] = '';
                return;
            }
            // Bersihkan leading zero: misal "01" -> "1", "-02" -> "-2"
            if (/^-?0+[0-9]+$/.test(val)) {
                val = String(parseInt(val, 10));
                e.target.value = val;
            }
            item[field] = parseInt(val, 10) || 0;
            this.triggerFieldChange(item, field);
        },

        handleStepperKeydown(e, item, field) {
            // Jika isi saat ini "0" dan user mengetik angka 0-9
            if (e.key >= '0' && e.key <= '9') {
                const isAllSelected = (e.target.selectionStart === 0 && e.target.selectionEnd === e.target.value.length);
                if (e.target.value === '0' && !isAllSelected) {
                    e.preventDefault();
                    e.target.value = e.key;
                    item[field] = parseInt(e.key, 10);
                    this.triggerFieldChange(item, field);
                }
            }
        },

        handleStepperBlur(item, field, e) {
            if (item[field] === '' || item[field] === null || isNaN(item[field])) {
                item[field] = 0;
                if (e && e.target) e.target.value = 0;
                this.triggerFieldChange(item, field);
            }
        },

        triggerFieldChange(item, field) {
            if (field === 'sisa_fisik_di_rak') {
                this.onSisaChange(item);
            } else if (field === 'jumlah_laku') {
                this.onLakuChange(item);
            } else if (field === 'retur_rusak' || field === 'retur_bagus') {
                this.onReturChange(item);
            } else if (field === 'selisih_qty') {
                this.onSelisihChange(item);
            } else if (field === 'tambah_titip_baru') {
                this.onDropChange(item);
            }
        },

        // Helper khusus Selisih Rak (Signed: Mengizinkan input minus [-] langsung secara fleksibel)
        handleSelisihKeydown(e, item) {
            if (e.key === '-' || e.key === 'Subtract') {
                e.preventDefault();
                const isAllSelected = (e.target.selectionStart === 0 && e.target.selectionEnd === e.target.value.length);
                const isZeroOrEmpty = e.target.value === '0' || e.target.value === '' || e.target.value === '-';
                
                if (isZeroOrEmpty || isAllSelected) {
                    item.selisih_qty = '-';
                    e.target.value = '-';
                } else {
                    let currentNum = parseInt(item.selisih_qty, 10);
                    if (!isNaN(currentNum) && currentNum > 0) {
                        item.selisih_qty = -currentNum;
                        e.target.value = item.selisih_qty;
                        this.onSelisihChange(item);
                    } else if (!isNaN(currentNum) && currentNum < 0) {
                        item.selisih_qty = Math.abs(currentNum);
                        e.target.value = item.selisih_qty;
                        this.onSelisihChange(item);
                    } else {
                        item.selisih_qty = '-';
                        e.target.value = '-';
                    }
                }
                return;
            }

            if (e.key >= '0' && e.key <= '9') {
                const isAllSelected = (e.target.selectionStart === 0 && e.target.selectionEnd === e.target.value.length);
                if (e.target.value === '0' && !isAllSelected) {
                    e.preventDefault();
                    e.target.value = e.key;
                    item.selisih_qty = parseInt(e.key, 10);
                    this.onSelisihChange(item);
                } else if (e.target.value === '-') {
                    e.preventDefault();
                    const newSignedStr = '-' + e.key;
                    e.target.value = newSignedStr;
                    item.selisih_qty = parseInt(newSignedStr, 10);
                    this.onSelisihChange(item);
                }
            }
        },

        handleSelisihInput(e, item) {
            let raw = String(e.target.value || '').trim();

            if (raw === '') {
                item.selisih_qty = '';
                return;
            }

            if (raw === '-' || raw === '-0' || raw === '0-' || raw === '--') {
                item.selisih_qty = '-';
                e.target.value = '-';
                return;
            }

            const hasMinus = raw.includes('-');
            let digits = raw.replace(/[^0-9]/g, '');

            if (digits === '') {
                item.selisih_qty = hasMinus ? '-' : '';
                e.target.value = item.selisih_qty;
                return;
            }

            if (digits.length > 1 && digits.startsWith('0')) {
                digits = String(parseInt(digits, 10));
            }

            let cleanStr = (hasMinus && digits !== '0') ? ('-' + digits) : digits;

            e.target.value = cleanStr;
            item.selisih_qty = parseInt(cleanStr, 10);
            this.onSelisihChange(item);
        },

        handleSelisihBlur(item, e) {
            if (item.selisih_qty === '-' || item.selisih_qty === '' || item.selisih_qty === null || isNaN(item.selisih_qty)) {
                item.selisih_qty = 0;
                if (e && e.target) e.target.value = 0;
            } else {
                item.selisih_qty = parseInt(item.selisih_qty, 10) || 0;
                if (e && e.target) e.target.value = item.selisih_qty;
            }
            this.onSelisihChange(item);
        },

        // Helper: hitung selisih dari (sisa + laku + retur) - (titip + drop)
        calcSelisih(item) {
            const titip = item.stok_titip_saat_ini;
            const drop = Math.max(0, parseInt(item.tambah_titip_baru) || 0);
            const sisa = Math.max(0, parseInt(item.sisa_fisik_di_rak) || 0);
            const laku = Math.max(0, parseInt(item.jumlah_laku) || 0);
            const rBagus = Math.max(0, parseInt(item.retur_bagus) || 0);
            const rRusak = Math.max(0, parseInt(item.retur_rusak) || 0);
            return (sisa + laku + rBagus + rRusak) - (titip + drop);
        },

        // 0. Aksi ketika Kiriman Hari Ini (Drop Baru) diubah
        onDropChange(item) {
            item.is_touched = true;
            item.tambah_titip_baru = Math.max(0, parseInt(item.tambah_titip_baru) || 0);
            if ((parseInt(item.selisih_qty, 10) || 0) === 0) {
                const titip = item.stok_titip_saat_ini;
                const drop = item.tambah_titip_baru;
                const rBagus = Math.max(0, parseInt(item.retur_bagus) || 0);
                const rRusak = Math.max(0, parseInt(item.retur_rusak) || 0);
                const sisa = Math.max(0, parseInt(item.sisa_fisik_di_rak) || 0);
                item.jumlah_laku = Math.max(0, (titip + drop) - (sisa + rBagus + rRusak));
            }
            item.selisih_qty = this.calcSelisih(item);
        },

        // 1. Aksi ketika Sisa Fisik diubah langsung
        onSisaChange(item) {
            item.is_touched = true;
            item.sisa_fisik_di_rak = Math.max(0, parseInt(item.sisa_fisik_di_rak) || 0);
            if ((parseInt(item.selisih_qty, 10) || 0) === 0) {
                const titip = item.stok_titip_saat_ini;
                const drop = Math.max(0, parseInt(item.tambah_titip_baru) || 0);
                const rBagus = Math.max(0, parseInt(item.retur_bagus) || 0);
                const rRusak = Math.max(0, parseInt(item.retur_rusak) || 0);
                item.jumlah_laku = Math.max(0, (titip + drop) - (item.sisa_fisik_di_rak + rBagus + rRusak));
            }
            item.selisih_qty = this.calcSelisih(item);
        },

        // 2. Aksi ketika Jumlah Laku diubah langsung
        onLakuChange(item) {
            item.is_touched = true;
            item.jumlah_laku = Math.max(0, parseInt(item.jumlah_laku) || 0);
            item.selisih_qty = this.calcSelisih(item);
        },

        // 3. Aksi ketika Selisih diubah langsung
        onSelisihChange(item) {
            item.is_touched = true;
            if (item.selisih_qty !== '-' && item.selisih_qty !== '') {
                item.selisih_qty = parseInt(item.selisih_qty, 10) || 0;
            }
            const selisih = parseInt(item.selisih_qty, 10) || 0;
            const titip = item.stok_titip_saat_ini;
            const drop = Math.max(0, parseInt(item.tambah_titip_baru) || 0);
            const sisa = Math.max(0, parseInt(item.sisa_fisik_di_rak) || 0);
            const rBagus = Math.max(0, parseInt(item.retur_bagus) || 0);
            const rRusak = Math.max(0, parseInt(item.retur_rusak) || 0);
            item.jumlah_laku = Math.max(0, (titip + drop + selisih) - (sisa + rBagus + rRusak));
        },

        // 4. Steppers Sisa Fisik (+ / -)
        incrementSisa(item) {
            item.is_touched = true;
            item.sisa_fisik_di_rak = (parseInt(item.sisa_fisik_di_rak) || 0) + 1;
            if (item.selisih_qty === 0) {
                const titip = item.stok_titip_saat_ini;
                const drop = Math.max(0, parseInt(item.tambah_titip_baru) || 0);
                const rBagus = Math.max(0, parseInt(item.retur_bagus) || 0);
                const rRusak = Math.max(0, parseInt(item.retur_rusak) || 0);
                item.jumlah_laku = Math.max(0, (titip + drop) - (item.sisa_fisik_di_rak + rBagus + rRusak));
            }
            item.selisih_qty = this.calcSelisih(item);
        },

        decrementSisa(item) {
            item.is_touched = true;
            const current = parseInt(item.sisa_fisik_di_rak) || 0;
            if (current > 0) {
                item.sisa_fisik_di_rak = current - 1;
                if (item.selisih_qty === 0) {
                    const titip = item.stok_titip_saat_ini;
                    const drop = Math.max(0, parseInt(item.tambah_titip_baru) || 0);
                    const rBagus = Math.max(0, parseInt(item.retur_bagus) || 0);
                    const rRusak = Math.max(0, parseInt(item.retur_rusak) || 0);
                    item.jumlah_laku = Math.max(0, (titip + drop) - (item.sisa_fisik_di_rak + rBagus + rRusak));
                }
                item.selisih_qty = this.calcSelisih(item);
            }
        },

        // 5. Steppers Kiriman Hari Ini / Drop Baru (+ / -)
        incrementDrop(item) {
            item.is_touched = true;
            item.tambah_titip_baru = (parseInt(item.tambah_titip_baru) || 0) + 1;
            this.onDropChange(item);
        },

        decrementDrop(item) {
            item.is_touched = true;
            const current = parseInt(item.tambah_titip_baru) || 0;
            if (current > 0) {
                item.tambah_titip_baru = current - 1;
                this.onDropChange(item);
            }
        },

        // 6. Steppers Laku Terjual (+ / -)
        incrementLaku(item) {
            item.is_touched = true;
            item.jumlah_laku = (parseInt(item.jumlah_laku) || 0) + 1;
            item.selisih_qty = this.calcSelisih(item);
        },

        decrementLaku(item) {
            item.is_touched = true;
            const current = parseInt(item.jumlah_laku) || 0;
            if (current > 0) {
                item.jumlah_laku = current - 1;
                item.selisih_qty = this.calcSelisih(item);
            }
        },

        // 7. Steppers Selisih (+ / -)
        incrementSelisih(item) {
            item.is_touched = true;
            item.selisih_qty = (parseInt(item.selisih_qty) || 0) + 1;
            this.onSelisihChange(item);
        },

        decrementSelisih(item) {
            item.is_touched = true;
            item.selisih_qty = (parseInt(item.selisih_qty) || 0) - 1;
            this.onSelisihChange(item);
        },

        // 8. Retur Steppers (+ / -)
        incrementRetur(item, field) {
            item.is_touched = true;
            item[field] = (parseInt(item[field]) || 0) + 1;
            this.onReturChange(item);
        },

        decrementRetur(item, field) {
            item.is_touched = true;
            const val = parseInt(item[field]) || 0;
            if (val > 0) {
                item[field] = val - 1;
                this.onReturChange(item);
            }
        },

        onReturChange(item) {
            item.is_touched = true;
            const titip = item.stok_titip_saat_ini;
            const drop = Math.max(0, parseInt(item.tambah_titip_baru) || 0);
            const rBagus = Math.max(0, parseInt(item.retur_bagus) || 0);
            const rRusak = Math.max(0, parseInt(item.retur_rusak) || 0);
            const sisa = Math.max(0, parseInt(item.sisa_fisik_di_rak) || 0);
            item.jumlah_laku = Math.max(0, (titip + drop + item.selisih_qty) - (sisa + rBagus + rRusak));
            item.selisih_qty = this.calcSelisih(item);
        },

        // 9. Presets
        setHabis(item) {
            item.is_touched = true;
            const titip = item.stok_titip_saat_ini;
            const drop = Math.max(0, parseInt(item.tambah_titip_baru) || 0);
            const rBagus = Math.max(0, parseInt(item.retur_bagus) || 0);
            const rRusak = Math.max(0, parseInt(item.retur_rusak) || 0);
            item.sisa_fisik_di_rak = 0;
            item.jumlah_laku = Math.max(0, (titip + drop) - (rBagus + rRusak));
            item.selisih_qty = 0;
        },

        setUtuh(item) {
            item.is_touched = true;
            const titip = item.stok_titip_saat_ini;
            const drop = Math.max(0, parseInt(item.tambah_titip_baru) || 0);
            const rBagus = Math.max(0, parseInt(item.retur_bagus) || 0);
            const rRusak = Math.max(0, parseInt(item.retur_rusak) || 0);
            item.jumlah_laku = 0;
            item.sisa_fisik_di_rak = Math.max(0, (titip + drop) - (rBagus + rRusak));
            item.selisih_qty = 0;
        },

        setAllIntact() {
            this.items.forEach(item => this.setUtuh(item));
            if (window.toast) window.toast.info('Semua produk diatur UTUH (0 Laku).');
        },

        handleNominalBayarInput(e) {
            let raw = String(e.target.value || '').replace(/[^0-9]/g, '');
            if (raw === '') {
                this.nominal_bayar = 0;
                this.nominal_bayar_formatted = '';
                e.target.value = '';
                return;
            }
            let num = parseInt(raw, 10) || 0;
            this.nominal_bayar = num;
            this.nominal_bayar_formatted = num.toLocaleString('id-ID');
            e.target.value = this.nominal_bayar_formatted;
        },

        setNominalBayar(amount) {
            let num = Math.max(0, parseInt(amount, 10) || 0);
            this.nominal_bayar = num;
            this.nominal_bayar_formatted = num === 0 ? '0' : num.toLocaleString('id-ID');
        },

        resetAll() {
            this.items.forEach(item => {
                item.retur_bagus = 0;
                item.retur_rusak = 0;
                item.tambah_titip_baru = 0;
                item.showReturBagus = false;
                this.setUtuh(item);
                item.is_touched = false;
            });
            this.nominal_bayar = 0;
            this.nominal_bayar_formatted = '0';
            this.catatan_bayar = '';
            if (window.toast) window.toast.info('Hitungan opname telah di-reset ke kondisi awal.');
        },

        // Photo Upload Handler
        handlePhotoChange(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    if (window.toast) window.toast.error('Ukuran file foto maksimal 5MB.');
                    event.target.value = '';
                    return;
                }
                this.photoName = file.name;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.photoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        clearPhoto() {
            this.photoPreview = null;
            this.photoName = '';
            const input = document.getElementById('foto_kunjungan');
            if (input) input.value = '';
        },

        // Validations
        itemWarning(item) {
            const sisa = parseInt(item.sisa_fisik_di_rak);
            const laku = parseInt(item.jumlah_laku);
            const rBagus = parseInt(item.retur_bagus);
            const rRusak = parseInt(item.retur_rusak);
            const drop = parseInt(item.tambah_titip_baru) || 0;
            if (isNaN(sisa) || sisa < 0 || isNaN(laku) || laku < 0 || isNaN(rBagus) || rBagus < 0 || isNaN(rRusak) || rRusak < 0 || drop < 0) {
                return true;
            }
            return false;
        },

        get hasAnyWarning() {
            return this.items.some(item => this.itemWarning(item));
        },

        get hasAnyTouched() {
            return this.items.some(item => item.is_touched);
        },

        get countedItemsCount() {
            return this.items.filter(item => item.is_touched).length;
        },

        get soldCount() {
            return this.items.filter(item => item.jumlah_laku > 0).length;
        },

        get returnCount() {
            return this.items.filter(item => (item.retur_bagus > 0 || item.retur_rusak > 0)).length;
        },

        get discrepancyCount() {
            return this.items.filter(item => (parseInt(item.selisih_qty) || 0) !== 0).length;
        },

        get grandTotalDropPcs() {
            return this.items.reduce((sum, i) => sum + Math.max(0, parseInt(i.tambah_titip_baru) || 0), 0);
        },

        get grandTotalLakuPcs() {
            return this.items.reduce((sum, i) => sum + Math.max(0, parseInt(i.jumlah_laku) || 0), 0);
        },

        get grandTotalRusakPcs() {
            return this.items.reduce((sum, i) => sum + Math.max(0, parseInt(i.retur_rusak) || 0), 0);
        },

        get grandTotalBagusPcs() {
            return this.items.reduce((sum, i) => sum + Math.max(0, parseInt(i.retur_bagus) || 0), 0);
        },

        get grandTotalSelisihPcs() {
            return this.items.reduce((sum, i) => sum + (parseInt(i.selisih_qty) || 0), 0);
        },

        get grandTotalLakuRp() {
            return this.items.reduce((sum, i) => sum + (i.jumlah_laku > 0 ? (i.jumlah_laku * i.harga_deal) : 0), 0);
        },

        get sisaPiutangRp() {
            const total = this.grandTotalLakuRp;
            const bayar = parseFloat(this.nominal_bayar) || 0;
            return Math.max(0, total - bayar);
        },

        filterItem(item) {
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                const matchName = (item.nama_item || item.nama_grup || '').toLowerCase().includes(q);
                const matchSku = (item.kode_sku || item.barcode_universal || '').toLowerCase().includes(q);
                const matchVar = (item.varian_text || '').toLowerCase().includes(q);
                if (!matchName && !matchSku && !matchVar) return false;
            }

            if (this.activeTab === 'sold') {
                return item.jumlah_laku > 0;
            }
            if (this.activeTab === 'intact') {
                return item.jumlah_laku === 0 && (item.retur_bagus === 0 && item.retur_rusak === 0 && (parseInt(item.selisih_qty) || 0) === 0);
            }
            if (this.activeTab === 'return') {
                return (item.retur_bagus > 0 || item.retur_rusak > 0);
            }
            if (this.activeTab === 'discrepancy') {
                return (parseInt(item.selisih_qty) || 0) !== 0;
            }
            return true;
        },

        get filteredItemsCount() {
            return this.items.filter(item => this.filterItem(item)).length;
        },

        openConfirmModal() {
            if (this.items.length === 0) {
                if (window.toast) {
                    window.toast.error('Toko ini belum memiliki item titipan di rak atau PO kiriman konsinyasi.');
                }
                return;
            }

            if (this.hasAnyWarning) {
                if (window.toast) {
                    window.toast.error('Jumlah angka fisik, laku, drop baru, atau retur tidak valid (tidak boleh negatif).');
                }
                return;
            }

            // Validasi: Ingatkan jika belum ada item yang dihitung dan tidak ada drop baru
            if (!this.hasAnyTouched && this.grandTotalLakuPcs === 0 && this.grandTotalRusakPcs === 0 && this.grandTotalBagusPcs === 0 && this.grandTotalSelisihPcs === 0 && this.grandTotalDropPcs === 0) {
                if (window.toast) {
                    window.toast.warning('Data Opname Belum Diisi! Silakan sesuaikan sisa fisik di rak atau isi Kiriman Hari Ini (Drop Baru).');
                }
                return;
            }

            // Validasi batas pembayaran di toko (hanya untuk mode rolling nota)
            if (this.tipeKonsinyasi !== 'kolektif_tagihan') {
                const bayar = parseFloat(this.nominal_bayar) || 0;
                if (bayar < 0) {
                    if (window.toast) {
                        window.toast.error('Nominal pembayaran di toko tidak boleh bernilai negatif.');
                    }
                    return;
                }

                if (bayar > this.grandTotalLakuRp) {
                    if (window.toast) {
                        window.toast.error('Nominal bayar (' + this.formatRupiah(bayar) + ') tidak boleh melebihi total penjualan (' + this.formatRupiah(this.grandTotalLakuRp) + ').');
                    }
                    return;
                }
            }

            this.showConfirmModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        submitForm() {
            this.items.forEach(item => {
                item.selisih_qty = parseInt(item.selisih_qty, 10) || 0;
                item.tambah_titip_baru = Math.max(0, parseInt(item.tambah_titip_baru, 10) || 0);
                item.sisa_fisik_di_rak = Math.max(0, parseInt(item.sisa_fisik_di_rak, 10) || 0);
                item.jumlah_laku = Math.max(0, parseInt(item.jumlah_laku, 10) || 0);
                item.retur_rusak = Math.max(0, parseInt(item.retur_rusak, 10) || 0);
                item.retur_bagus = Math.max(0, parseInt(item.retur_bagus, 10) || 0);
            });
            this.nominal_bayar = Math.max(0, parseFloat(this.nominal_bayar) || 0);
            const form = document.getElementById('opnameForm');
            if (form) form.submit();
        },

        formatRupiah(amount) {
            const val = parseFloat(amount) || 0;
            return 'Rp ' + val.toLocaleString('id-ID');
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

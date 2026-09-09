<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();

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
    padding-bottom: 150px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
@media (min-width: 640px) {
    .opname-container {
        padding-bottom: 160px;
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
@media (min-width: 1024px) {
    .opname-kpi-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
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
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--color-hairline);
}
.opname-card-info {
    min-width: 0;
    flex: 1;
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
    font-family: var(--font-mono);
    font-weight: 900;
    font-size: 12.5px;
    padding: 4px 10px;
    border-radius: 10px;
    background: rgba(2, 132, 199, 0.1);
    color: #0284c7;
}

/* DUAL STEPPER GRID (SISA RAK & LAKU DUA ARAH) */
.opname-dual-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 14px;
}
@media (min-width: 640px) {
    .opname-dual-grid {
        gap: 16px;
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
    margin-bottom: 6px;
}
.opname-stepper {
    display: flex;
    align-items: stretch;
    height: 42px;
    border: 1.5px solid var(--color-hairline-strong);
    border-radius: 12px;
    background: var(--color-canvas);
    overflow: hidden;
    width: 100%;
    box-sizing: border-box;
    transition: border-color 0.15s ease;
}
.opname-stepper.is-laku-stepper {
    border-color: rgba(16, 185, 129, 0.45);
    background: rgba(16, 185, 129, 0.04);
}
.opname-stepper-btn {
    width: 38px;
    height: 39px;
    border: none;
    background: var(--color-canvas-soft);
    color: var(--color-ink);
    font-size: 19px;
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
.opname-stepper-input {
    flex: 1;
    width: 100%;
    min-width: 0;
    height: 39px;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    background: transparent !important;
    text-align: center;
    font-family: var(--font-mono);
    font-weight: 900;
    font-size: 17px;
    color: var(--color-ink);
    padding: 0;
    margin: 0;
    -moz-appearance: textfield;
}
.opname-stepper-input.is-laku-input {
    color: #10b981;
}
.opname-stepper-input::-webkit-outer-spin-button,
.opname-stepper-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Card Subrow (Presets & Live Outcome) */
.opname-card-subrow {
    margin-top: 10px;
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
    height: 36px;
    padding: 0 11px;
    border-radius: 10px;
    border: 1px solid var(--color-hairline);
    background: var(--color-canvas-soft);
    color: var(--color-ink-secondary);
    font-size: 11.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.12s ease;
    user-select: none;
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
.opname-outcome-pill.is-warn {
    background: rgba(244, 63, 94, 0.1);
    color: #f43f5e;
    border: 1px solid rgba(244, 63, 94, 0.25);
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

/* 5. Fixed Bottom Dock */
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
        left: var(--sidebar-width, 260px);
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
.opname-submit-count {
    font-size: 11px;
    font-family: var(--font-mono);
    background: rgba(255, 255, 255, 0.22);
    padding: 2px 6px;
    border-radius: 6px;
    display: none;
}
@media (min-width: 640px) {
    .opname-submit-count {
        display: inline-block;
    }
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
        <div x-show="showGuide" x-cloak class="opname-guide-box">
            <i data-lucide="info" style="width:16px;height:16px;color:#0284c7;flex-shrink:0;margin-top:2px;"></i>
            <div class="opname-guide-content">
                <div class="opname-guide-title">
                    Rumus Dua Arah: Sisa Fisik di Rak &harr; Barang Laku Terjual
                </div>
                <div class="opname-guide-body">
                    • <strong>Input Bebas:</strong> Kamu bisa isi <em>Sisa Rak</em> (Laku otomatis dihitung) ATAU isi <em>Laku</em> (Sisa Rak otomatis dihitung).<br>
                    • <strong>Retur Bagus:</strong> Produk layak jual yang ditarik kembali ke gudang.<br>
                    • <strong>Retur Rusak (BS):</strong> Produk bocor, pecah, atau kadaluarsa (diakui kerugian HPP).
                </div>
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
    <!-- 2. QUICK KPI SUMMARY CHIPS & METRICS STRIP                                -->
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
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. SKU PRODUCT CARDS LIST (DUAL INPUT TWO-WAY SYNCHRONIZED)               -->
    <!-- ========================================================================= -->
    <form id="opnameForm" action="<?= Router::url('/consignment/opname/proses') ?>" method="POST" data-action-text="Menyimpan hasil opname konsinyasi...">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="pelanggan_id" value="<?= htmlspecialchars((string)$customer['id']) ?>">
        <input type="hidden" name="items_json" :value="JSON.stringify(items)">

        <div class="opname-cards-stack">
            <template x-for="(item, index) in items" :key="item.item_id">
                <div x-show="filterItem(item)" 
                     class="opname-card"
                     :class="{
                         'is-sold': item.jumlah_laku > 0 && !itemWarning(item),
                         'is-warning': itemWarning(item),
                         'is-touched': item.is_touched && item.jumlah_laku === 0 && !itemWarning(item)
                     }">

                    <!-- TOP SECTION: PRODUCT INFO & STOCK PILL -->
                    <div class="opname-card-head">
                        <div class="opname-card-info">
                            <div class="opname-card-tags">
                                <span class="opname-card-sku" x-text="'[' + item.kode_sku + ']'"></span>
                                <span class="opname-card-price" x-text="'@ ' + formatRupiah(item.harga_deal)"></span>
                                <template x-if="item.stok_titip_saat_ini === 0">
                                    <span class="opname-card-empty-badge">Stok Kosong</span>
                                </template>
                            </div>
                            <h3 class="opname-card-title" x-text="item.nama_item"></h3>
                        </div>

                        <!-- Baseline Stok Titip Rak Pill -->
                        <div class="opname-card-baseline">
                            <span class="opname-baseline-label">Stok Titip:</span>
                            <span class="opname-baseline-pill">
                                <i data-lucide="package" style="width:14px;height:14px;"></i>
                                <span x-text="item.stok_titip_saat_ini + ' ' + item.satuan_dasar"></span>
                            </span>
                        </div>
                    </div>

                    <!-- DUAL STEPPERS: SISA FISIK & LAKU TERJUAL (SINKRON DUA ARAH) -->
                    <div class="opname-dual-grid">
                        
                        <!-- Kolom 1: Sisa Fisik di Rak -->
                        <div class="opname-input-group">
                            <label class="opname-field-label">
                                <span>Sisa Fisik di Rak</span>
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
                                       @input="onSisaChange(item)"
                                       class="opname-stepper-input"
                                       :style="itemWarning(item) ? 'color:#f43f5e !important;' : ''">

                                <button type="button" 
                                        @click="incrementSisa(item)"
                                        class="opname-stepper-btn"
                                        title="Tambah 1 Sisa">+</button>
                            </div>
                        </div>

                        <!-- Kolom 2: Laku Terjual -->
                        <div class="opname-input-group">
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
                                       @input="onLakuChange(item)"
                                       class="opname-stepper-input is-laku-input"
                                       :style="itemWarning(item) ? 'color:#f43f5e !important;' : ''">

                                <button type="button" 
                                        @click="incrementLaku(item)"
                                        class="opname-stepper-btn is-laku-btn"
                                        title="Tambah 1 Laku">+</button>
                            </div>
                        </div>

                    </div>

                    <!-- CARD SUBROW: PRESETS & LIVE OUTCOME FEEDBACK -->
                    <div class="opname-card-subrow">
                        <!-- Quick Preset Buttons -->
                        <div class="opname-presets-wrap">
                            <button type="button" 
                                    @click="setHabis(item)" 
                                    class="opname-quick-btn btn-habis"
                                    title="Semua terjual habis (Sisa 0)">
                                Habis (0 Sisa)
                            </button>
                            <button type="button" 
                                    @click="setUtuh(item)" 
                                    class="opname-quick-btn btn-utuh"
                                    title="Tidak ada yang terjual (Laku 0)">
                                Utuh (0 Laku)
                            </button>
                        </div>

                        <!-- Live Outcome Pill / Subtotal -->
                        <div class="opname-outcome-wrap">
                            <!-- State 1: Warning Over Capacity -->
                            <template x-if="itemWarning(item)">
                                <div class="opname-outcome-pill is-warn">
                                    <i data-lucide="alert-circle" style="width:15px;height:15px;flex-shrink:0;"></i>
                                    <span>Melebihi Titip (<span x-text="item.stok_titip_saat_ini"></span> pcs)!</span>
                                </div>
                            </template>

                            <!-- State 2: Normal Valid Calculation -->
                            <template x-if="!itemWarning(item)">
                                <div>
                                    <template x-if="item.jumlah_laku > 0">
                                        <div class="opname-outcome-pill is-laku">
                                            <i data-lucide="check-circle" style="width:14px;height:14px;"></i>
                                            <span>Laku: <strong class="opname-val-mono" x-text="item.jumlah_laku + ' ' + item.satuan_dasar"></strong></span>
                                            <span style="opacity:0.4;">•</span>
                                            <span class="opname-val-mono font-bold" x-text="formatRupiah(item.jumlah_laku * item.harga_deal)"></span>
                                        </div>
                                    </template>
                                    <template x-if="item.jumlah_laku === 0">
                                        <div class="opname-outcome-pill is-utuh">
                                            <span>Utuh (0 laku)</span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- COLLAPSIBLE RETUR SECTION -->
                    <div class="opname-retur-section">
                        
                        <!-- Toggle Button -->
                        <button type="button" 
                                @click="item.showRetur = !item.showRetur" 
                                class="opname-retur-toggle"
                                :class="(item.retur_bagus > 0 || item.retur_rusak > 0) ? 'has-retur' : ''">
                            <i data-lucide="package-minus" style="width:14px;height:14px;"></i>
                            <span x-text="(item.retur_bagus > 0 || item.retur_rusak > 0) 
                                         ? 'Retur: ' + (item.retur_bagus || 0) + ' Bagus, ' + (item.retur_rusak || 0) + ' Rusak' 
                                         : '+ Catat Retur (Bagus / Rusak)'"></span>
                            <i data-lucide="chevron-down" style="width:14px;height:14px;transition:transform 0.2s;" :style="item.showRetur ? 'transform:rotate(180deg);' : ''"></i>
                        </button>

                        <!-- Drawer Fields -->
                        <div x-show="item.showRetur" 
                             x-cloak 
                             class="opname-retur-box">
                            
                            <!-- Field Retur Bagus -->
                            <div>
                                <label class="opname-retur-label">
                                    Retur Bagus (Tarik Gudang)
                                </label>
                                <div class="opname-retur-stepper">
                                    <button type="button" 
                                            @click="decrementRetur(item, 'retur_bagus')"
                                            class="opname-retur-stepper-btn">−</button>
                                    <input type="number" 
                                           inputmode="numeric" 
                                           pattern="[0-9]*" 
                                           min="0"
                                           x-model.number="item.retur_bagus" 
                                           @input="onReturChange(item)"
                                           class="opname-retur-stepper-input">
                                    <button type="button" 
                                            @click="incrementRetur(item, 'retur_bagus')"
                                            class="opname-retur-stepper-btn">+</button>
                                </div>
                                <span class="opname-retur-hint">Barang bagus ditarik kembali ke gudang.</span>
                            </div>

                            <!-- Field Retur Rusak -->
                            <div>
                                <label class="opname-retur-label">
                                    Retur Rusak / BS (Kerugian)
                                </label>
                                <div class="opname-retur-stepper" 
                                     :style="item.retur_rusak > 0 ? 'border-color:rgba(244,63,94,0.4);' : ''">
                                    <button type="button" 
                                            @click="decrementRetur(item, 'retur_rusak')"
                                            class="opname-retur-stepper-btn">−</button>
                                    <input type="number" 
                                           inputmode="numeric" 
                                           pattern="[0-9]*" 
                                           min="0"
                                           x-model.number="item.retur_rusak" 
                                           @input="onReturChange(item)"
                                           class="opname-retur-stepper-input"
                                           :style="item.retur_rusak > 0 ? 'color:#f43f5e !important;' : ''">
                                    <button type="button" 
                                            @click="incrementRetur(item, 'retur_rusak')"
                                            class="opname-retur-stepper-btn">+</button>
                                </div>
                                <span class="opname-retur-hint">Pecah/bocor/expired diakui sebagai beban kerugian HPP.</span>
                            </div>

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

    </form>

    <!-- ========================================================================= -->
    <!-- 5. FLOATING DOCKED BOTTOM ACTION BAR (MOBILE-FIRST HIGH ERGONOMICS)       -->
    <!-- ========================================================================= -->
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

    <!-- ========================================================================= -->
    <!-- 6. MODAL KONFIRMASI SUBMIT (RESPONSIVE M3 BOTTOM SHEET / DIALOG)          -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="showConfirmModal" 
             x-cloak 
             class="modal-backdrop"
             style="position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:flex-end;justify-content:center;z-index:9999;"
             @click.self="showConfirmModal = false"
             @keydown.escape.window="showConfirmModal = false">
            
            <div class="modal-box" 
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

                <!-- Summary Table Details -->
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
                    <div style="padding-top:8px;border-top:1px solid var(--color-hairline);margin-top:2px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-ink-mute);">Penerbitan Faktur:</span>
                            <span style="font-size:12px;font-weight:800;" 
                                  :style="grandTotalLakuRp > 0 ? 'color:#0284c7;' : 'color:var(--color-ink-mute);'"
                                  x-text="grandTotalLakuRp > 0 ? 'Diterbitkan di Menu Tagihan' : 'Nihil (Tidak Diterbitkan)'">
                            </span>
                        </div>
                        <template x-if="grandTotalLakuRp > 0">
                            <div style="margin-top:6px;font-size:11px;color:var(--color-ink-secondary);line-height:1.4;background:rgba(2,132,199,0.06);padding:6px 10px;border-radius:8px;border:1px solid rgba(2,132,199,0.15);">
                                <strong style="color:var(--color-ink);">Info Tagihan:</strong> Nota tagihan tidak langsung terbit saat simpan opname. Faktur tagihan diterbitkan terpisah atau digabungkan melalui menu <strong>Tagihan Konsinyasi</strong>.
                            </div>
                        </template>
                    </div>
                </div>

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
                            style="padding:12px;border-radius:12px;font-weight:900;font-size:12px;background:#10b981;border-color:#10b981;color:#fff;justify-content:center;box-shadow:0 3px 10px rgba(16,185,129,0.3);">
                        Ya, Simpan Opname
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
        searchQuery: '',
        activeTab: 'all',
        showGuide: false,
        showConfirmModal: false,
        items: <?= json_encode($items ?? []) ?>,

        init() {
            this.items.forEach(item => {
                item.stok_titip_saat_ini = parseInt(item.stok_titip_saat_ini) || 0;
                item.retur_bagus = parseInt(item.retur_bagus) || 0;
                item.retur_rusak = parseInt(item.retur_rusak) || 0;
                item.showRetur = (item.retur_bagus > 0 || item.retur_rusak > 0);

                if (item.sisa_fisik_di_rak !== undefined && item.sisa_fisik_di_rak !== null) {
                    item.sisa_fisik_di_rak = parseInt(item.sisa_fisik_di_rak);
                } else {
                    item.sisa_fisik_di_rak = item.stok_titip_saat_ini;
                }

                if (item.jumlah_laku !== undefined && item.jumlah_laku !== null) {
                    item.jumlah_laku = parseInt(item.jumlah_laku);
                } else {
                    item.jumlah_laku = Math.max(0, item.stok_titip_saat_ini - (item.sisa_fisik_di_rak + item.retur_bagus + item.retur_rusak));
                }
            });

            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
            this.$watch('searchQuery', () => this.$nextTick(() => window.lucide && window.lucide.createIcons()));
            this.$watch('activeTab', () => this.$nextTick(() => window.lucide && window.lucide.createIcons()));
        },

        // 1. Aksi ketika Sisa Fisik diubah langsung (manual type)
        onSisaChange(item) {
            item.is_touched = true;
            const titip = item.stok_titip_saat_ini;
            const rBagus = parseInt(item.retur_bagus) || 0;
            const rRusak = parseInt(item.retur_rusak) || 0;
            const sisa = Math.max(0, parseInt(item.sisa_fisik_di_rak) || 0);
            item.sisa_fisik_di_rak = sisa;

            // Hitung laku dari sisa fisik
            item.jumlah_laku = Math.max(0, titip - (sisa + rBagus + rRusak));
        },

        // 2. Aksi ketika Jumlah Laku diubah langsung (manual type)
        onLakuChange(item) {
            item.is_touched = true;
            const titip = item.stok_titip_saat_ini;
            const rBagus = parseInt(item.retur_bagus) || 0;
            const rRusak = parseInt(item.retur_rusak) || 0;
            const laku = Math.max(0, parseInt(item.jumlah_laku) || 0);
            item.jumlah_laku = laku;

            // Hitung sisa fisik dari laku
            item.sisa_fisik_di_rak = Math.max(0, titip - (laku + rBagus + rRusak));
        },

        // 3. Stepper Sisa Fisik (+ / -)
        incrementSisa(item) {
            item.is_touched = true;
            const titip = item.stok_titip_saat_ini;
            const rBagus = parseInt(item.retur_bagus) || 0;
            const rRusak = parseInt(item.retur_rusak) || 0;
            const maxSisa = Math.max(0, titip - (rBagus + rRusak));

            if ((item.sisa_fisik_di_rak || 0) < maxSisa) {
                item.sisa_fisik_di_rak = (item.sisa_fisik_di_rak || 0) + 1;
                item.jumlah_laku = Math.max(0, titip - (item.sisa_fisik_di_rak + rBagus + rRusak));
            }
        },

        decrementSisa(item) {
            item.is_touched = true;
            const titip = item.stok_titip_saat_ini;
            const rBagus = parseInt(item.retur_bagus) || 0;
            const rRusak = parseInt(item.retur_rusak) || 0;

            if ((item.sisa_fisik_di_rak || 0) > 0) {
                item.sisa_fisik_di_rak = (item.sisa_fisik_di_rak || 0) - 1;
                item.jumlah_laku = Math.max(0, titip - (item.sisa_fisik_di_rak + rBagus + rRusak));
            }
        },

        // 4. Stepper Laku Terjual (+ / -)
        incrementLaku(item) {
            item.is_touched = true;
            const titip = item.stok_titip_saat_ini;
            const rBagus = parseInt(item.retur_bagus) || 0;
            const rRusak = parseInt(item.retur_rusak) || 0;
            const maxLaku = Math.max(0, titip - (rBagus + rRusak));

            if ((item.jumlah_laku || 0) < maxLaku) {
                item.jumlah_laku = (item.jumlah_laku || 0) + 1;
                item.sisa_fisik_di_rak = Math.max(0, titip - (item.jumlah_laku + rBagus + rRusak));
            }
        },

        decrementLaku(item) {
            item.is_touched = true;
            const titip = item.stok_titip_saat_ini;
            const rBagus = parseInt(item.retur_bagus) || 0;
            const rRusak = parseInt(item.retur_rusak) || 0;

            if ((item.jumlah_laku || 0) > 0) {
                item.jumlah_laku = (item.jumlah_laku || 0) - 1;
                item.sisa_fisik_di_rak = Math.max(0, titip - (item.jumlah_laku + rBagus + rRusak));
            }
        },

        // 5. Presets
        setHabis(item) {
            item.is_touched = true;
            const titip = item.stok_titip_saat_ini;
            const rBagus = parseInt(item.retur_bagus) || 0;
            const rRusak = parseInt(item.retur_rusak) || 0;

            item.sisa_fisik_di_rak = 0;
            item.jumlah_laku = Math.max(0, titip - (rBagus + rRusak));
        },

        setUtuh(item) {
            item.is_touched = true;
            const titip = item.stok_titip_saat_ini;
            const rBagus = parseInt(item.retur_bagus) || 0;
            const rRusak = parseInt(item.retur_rusak) || 0;

            item.jumlah_laku = 0;
            item.sisa_fisik_di_rak = Math.max(0, titip - (rBagus + rRusak));
        },

        // 6. Retur Stepper (+ / -)
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
            const rBagus = parseInt(item.retur_bagus) || 0;
            const rRusak = parseInt(item.retur_rusak) || 0;
            const sisa = Math.max(0, parseInt(item.sisa_fisik_di_rak) || 0);

            // Selalu pertahankan sisa rak aktual dan sesuaikan sisa kapasitas laku
            item.jumlah_laku = Math.max(0, titip - (sisa + rBagus + rRusak));
        },

        // 7. Batch Actions
        setAllIntact() {
            this.items.forEach(item => this.setUtuh(item));
            if (window.toast) window.toast.info('Semua produk diatur UTUH (0 Laku).');
        },

        resetAll() {
            this.items.forEach(item => {
                item.is_touched = false;
                item.retur_bagus = 0;
                item.retur_rusak = 0;
                item.showRetur = false;
                this.setUtuh(item);
                item.is_touched = false;
            });
            if (window.toast) window.toast.info('Hitungan opname telah di-reset ke kondisi awal.');
        },

        // 8. Warning Over Capacity
        itemWarning(item) {
            const sisa = parseInt(item.sisa_fisik_di_rak) || 0;
            const laku = parseInt(item.jumlah_laku) || 0;
            const rBagus = parseInt(item.retur_bagus) || 0;
            const rRusak = parseInt(item.retur_rusak) || 0;
            const titip = parseInt(item.stok_titip_saat_ini) || 0;

            return (sisa + rBagus + rRusak) > titip || (laku + rBagus + rRusak) > titip;
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

        get grandTotalLakuPcs() {
            return this.items.reduce((sum, i) => sum + Math.max(0, parseInt(i.jumlah_laku) || 0), 0);
        },

        get grandTotalRusakPcs() {
            return this.items.reduce((sum, i) => sum + Math.max(0, parseInt(i.retur_rusak) || 0), 0);
        },

        get grandTotalBagusPcs() {
            return this.items.reduce((sum, i) => sum + Math.max(0, parseInt(i.retur_bagus) || 0), 0);
        },

        get grandTotalLakuRp() {
            return this.items.reduce((sum, i) => sum + (i.jumlah_laku > 0 ? (i.jumlah_laku * i.harga_deal) : 0), 0);
        },

        filterItem(item) {
            // 1. Filter Search Text
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                const matchName = (item.nama_item || '').toLowerCase().includes(q);
                const matchSku = (item.kode_sku || '').toLowerCase().includes(q);
                if (!matchName && !matchSku) return false;
            }

            // 2. Filter Active Tab
            if (this.activeTab === 'sold') {
                return item.jumlah_laku > 0;
            }
            if (this.activeTab === 'intact') {
                return item.jumlah_laku === 0 && (item.retur_bagus === 0 && item.retur_rusak === 0);
            }
            if (this.activeTab === 'return') {
                return (item.retur_bagus > 0 || item.retur_rusak > 0);
            }
            return true;
        },

        get filteredItemsCount() {
            return this.items.filter(item => this.filterItem(item)).length;
        },

        openConfirmModal() {
            if (this.items.length === 0) {
                if (window.toast) {
                    window.toast.error('Toko ini belum memiliki item barang titipan di rak untuk diopname.');
                }
                return;
            }

            if (this.hasAnyWarning) {
                if (window.toast) {
                    window.toast.error('Jumlah sisa fisik / laku + retur tidak boleh melebihi stok titip rak awal.');
                }
                return;
            }

            // Validasi: Ingatkan jika belum ada item yang dihitung sama sekali
            if (!this.hasAnyTouched && this.grandTotalLakuPcs === 0 && this.grandTotalRusakPcs === 0 && this.grandTotalBagusPcs === 0) {
                if (window.toast) {
                    window.toast.warning('Data Opname Belum Diisi! Silakan periksa atau sesuaikan sisa fisik di rak.');
                }
                return;
            }

            this.showConfirmModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        submitForm() {
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

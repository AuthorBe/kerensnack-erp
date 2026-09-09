<?php
use App\Helpers\Format;
use App\Helpers\CSRF;
use App\Core\Router;
use App\Core\Auth;
ob_start();

// Perhitungan Finansial KPI untuk Tab Daftar Tagihan
$totalTagihanDiterbitkan = array_sum(array_column($tagihan, 'total_netto'));
$totalSudahDibayar       = array_sum(array_column($tagihan, 'total_dibayar'));
$totalPiutangTertunggak  = $totalOutstanding;
$collectionRate          = $totalTagihanDiterbitkan > 0 ? round(($totalSudahDibayar / $totalTagihanDiterbitkan) * 100, 1) : 0;
$countBelumLunas         = count(array_filter($tagihan, fn($t) => in_array($t['status_pembayaran'], ['belum_lunas', 'sebagian'])));
$countLunas              = count(array_filter($tagihan, fn($t) => $t['status_pembayaran'] === 'lunas'));
?>

<style>
/* ========================================================================= */
/* 1. PAGE CONTAINER & AIRY CARDS (Unified ERP Design System)               */
/* ========================================================================= */
.tagihan-page-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding-bottom: 90px;
}

.tagihan-page-container .page-header {
    margin-bottom: 0 !important;
}

.tagihan-tab-pane {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.tagihan-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 18px;
    padding: 20px 22px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.tagihan-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 14px;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--color-hairline);
}

/* ========================================================================= */
/* 2. TAB NAVIGATION SWITCHER & HEADER ROW                                   */
/* ========================================================================= */
.tagihan-tabs-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.tagihan-tabs-nav {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px;
    border-radius: 16px;
    background-color: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    width: 100%;
    max-width: 480px;
}

.tagihan-tab-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 9px 16px;
    font-size: 13px;
    font-weight: 700;
    border-radius: 12px;
    transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    color: var(--color-ink-mute);
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    user-select: none;
}

.tagihan-tab-btn:hover {
    color: var(--color-ink);
}

.tagihan-tab-btn.is-active {
    background-color: var(--color-canvas);
    color: var(--color-ink);
    border-color: var(--color-hairline);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.dark .tagihan-tab-btn.is-active {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.tagihan-tab-hint {
    font-size: 12px;
    font-weight: 600;
    color: var(--color-ink-secondary);
    background: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    padding: 7px 14px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.tagihan-guide-btn-text {
    display: inline;
}

@media (max-width: 640px) {
    .tagihan-guide-btn-text {
        display: none;
    }
}

/* ========================================================================= */
/* 3. FILTER BAR RESPONSIVE GRID & PERFECT ALIGNMENT                        */
/* ========================================================================= */
.tagihan-filter-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
    align-items: flex-end;
}

@media (min-width: 640px) {
    .tagihan-filter-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
}

@media (min-width: 1024px) {
    .tagihan-filter-grid {
        grid-template-columns: minmax(260px, 2fr) minmax(140px, 1.2fr) minmax(140px, 1.2fr) auto;
        gap: 16px;
    }
}

.tagihan-filter-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
}

.tagihan-label-spacer {
    display: none;
    margin-bottom: 7px;
    font-size: 11.5px;
    font-weight: 700;
    user-select: none;
    visibility: hidden;
    height: 17px;
}

@media (min-width: 1024px) {
    .tagihan-label-spacer {
        display: block;
    }
}

.tagihan-filter-status-chip {
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 14px;
    font-size: 12px;
    font-weight: 700;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.15s ease;
    user-select: none;
}

/* ========================================================================= */
/* 4. KPI METRIC STAT CARDS (Responsive 1/2/4 Grid)                         */
/* ========================================================================= */
.tagihan-kpi-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
}

@media (min-width: 640px) {
    .tagihan-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
}

@media (min-width: 1024px) {
    .tagihan-kpi-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
}

.tagihan-kpi-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 18px;
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 135px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.tagihan-kpi-card:hover {
    border-color: var(--color-hairline-strong);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
}

.tagihan-kpi-label {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--color-ink-mute);
}

.tagihan-kpi-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.tagihan-kpi-value {
    font-family: var(--font-mono);
    font-weight: 900;
    font-size: clamp(18px, 2.2vw, 24px);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

.tagihan-kpi-footer {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: var(--color-ink-mute);
    padding-top: 10px;
    border-top: 1px solid var(--color-hairline-cool);
}

/* ========================================================================= */
/* 5. MODERN ALERT CALLOUT BANNER                                            */
/* ========================================================================= */
.tagihan-alert-banner {
    background-color: rgba(2, 132, 199, 0.07);
    border: 1px solid rgba(2, 132, 199, 0.2);
    border-radius: 16px;
    padding: 14px 18px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    color: var(--color-ink);
}

.dark .tagihan-alert-banner {
    background-color: rgba(2, 132, 199, 0.12);
    border-color: rgba(2, 132, 199, 0.3);
}

/* ========================================================================= */
/* 6. SYMMETRICAL EMPTY STATE COMPONENT                                      */
/* ========================================================================= */
.tagihan-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 52px 24px;
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.tagihan-empty-state-icon {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px auto;
    flex-shrink: 0;
}

.tagihan-empty-state-icon.is-success {
    background-color: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.tagihan-empty-state-icon.is-primary {
    background-color: rgba(2, 132, 199, 0.1);
    color: #0284c7;
}

.tagihan-empty-state-title {
    font-size: 17px;
    font-weight: 800;
    color: var(--color-ink);
    margin: 0 0 8px 0;
    text-align: center;
    line-height: 1.3;
}

.tagihan-empty-state-desc {
    font-size: 13px;
    color: var(--color-ink-mute);
    max-width: 460px;
    margin: 0 auto 22px auto;
    line-height: 1.55;
    text-align: center;
}

/* ========================================================================= */
/* 5. TABLE WRAPPER & ACTIVE ROW HIGHLIGHT                                   */
/* ========================================================================= */
.tagihan-row-selected {
    background-color: rgba(2, 132, 199, 0.08) !important;
}

.dark .tagihan-row-selected {
    background-color: rgba(2, 132, 199, 0.14) !important;
}

/* ========================================================================= */
/* 6. STICKY FLOATING ACTION BAR (Bottom Sheet Style)                        */
/* ========================================================================= */
.floating-action-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--color-canvas);
    border-top: 1px solid var(--color-hairline);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    padding: 10px 20px;
    padding-bottom: max(10px, env(safe-area-inset-bottom));
    z-index: 30;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@media (min-width: 1024px) {
    .floating-action-bar {
        left: var(--sidebar-width, 260px);
        padding: 11px 28px;
    }
}

.dark .floating-action-bar {
    background: rgba(30, 31, 34, 0.95);
    box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.35);
}

/* ========================================================================= */
/* ========================================================================= */
/* 7. MODAL POP-UPS (Catat Pembayaran & Panduan Ketentuan)                  */
/* ========================================================================= */
@keyframes tagihanModalPopIn {
    0% {
        opacity: 0;
        transform: scale(0.96) translateY(10px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.tagihan-modal-backdrop,
.tagihan-modal-overlay {
    position: fixed !important;
    inset: 0 !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    height: 100dvh !important;
    margin: 0 !important;
    z-index: 99999 !important;
    background-color: rgba(15, 23, 42, 0.72) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 20px !important;
    box-sizing: border-box !important;
    overflow-y: auto !important;
    overscroll-behavior: contain !important;
}

.tagihan-modal-backdrop[style*="display: none"],
.tagihan-modal-backdrop[style*="display:none"],
.tagihan-modal-overlay[style*="display: none"],
.tagihan-modal-overlay[style*="display:none"] {
    display: none !important;
}

.tagihan-modal-shell {
    width: 100%;
    max-width: 520px;
    max-height: 90vh;
    max-height: 90dvh;
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    margin: auto !important;
    animation: tagihanModalPopIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.tagihan-modal-guide-shell {
    width: 100%;
    max-width: 740px;
    height: 86vh;
    max-height: 760px;
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    margin: auto !important;
    animation: tagihanModalPopIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.tagihan-modal-header {
    padding: 16px 22px;
    border-bottom: 1px solid var(--color-hairline);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    background-color: var(--color-canvas);
}

.tagihan-modal-body {
    padding: 22px;
    overflow-y: auto;
    flex: 1 1 auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
}

.tagihan-modal-footer {
    padding: 14px 22px;
    border-top: 1px solid var(--color-hairline);
    background-color: var(--color-canvas-soft);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-shrink: 0;
}

/* Modal Pembayaran: Modern Fintech UI Refinements */
.tagihan-pay-field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.tagihan-pay-label {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--color-ink-secondary);
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0;
}

.tagihan-pay-input,
.tagihan-pay-select,
.tagihan-pay-textarea {
    width: 100%;
    background-color: var(--color-canvas);
    border: 1.5px solid var(--color-hairline);
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 12.5px;
    color: var(--color-ink);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    outline: none;
    box-sizing: border-box;
}

.tagihan-pay-input:focus,
.tagihan-pay-select:focus,
.tagihan-pay-textarea:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 3.5px rgba(2, 132, 199, 0.15);
}

.tagihan-pay-amount-box {
    position: relative;
    display: flex;
    align-items: center;
    background-color: var(--color-canvas);
    border: 1.5px solid var(--color-hairline);
    border-radius: 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    overflow: hidden;
}

.tagihan-pay-amount-box:focus-within {
    border-color: #0284c7;
    box-shadow: 0 0 0 3.5px rgba(2, 132, 199, 0.16);
}

.tagihan-pay-amount-box.is-invalid {
    border-color: #ef4444 !important;
    box-shadow: 0 0 0 3.5px rgba(239, 68, 68, 0.16) !important;
}

.tagihan-pay-amount-prefix {
    padding: 0 14px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 800;
    font-size: 15px;
    color: var(--color-ink-mute);
    background-color: var(--color-canvas-soft);
    height: 46px;
    display: flex;
    align-items: center;
    border-right: 1px solid var(--color-hairline);
    user-select: none;
    flex-shrink: 0;
}

.tagihan-pay-amount-input {
    flex: 1;
    height: 46px;
    border: none !important;
    background: transparent !important;
    padding: 0 14px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 18px;
    font-weight: 900;
    color: var(--color-ink);
    outline: none !important;
    box-shadow: none !important;
}

.tagihan-pay-preset-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 3px 9px;
    font-size: 11px;
    font-weight: 700;
    border-radius: 7px;
    border: 1px solid var(--color-hairline);
    background-color: var(--color-canvas-soft);
    color: var(--color-ink-secondary);
    cursor: pointer;
    transition: all 0.15s ease;
}

.tagihan-pay-preset-chip:hover {
    background-color: rgba(2, 132, 199, 0.08);
    border-color: rgba(2, 132, 199, 0.3);
    color: #0284c7;
    transform: translateY(-1px);
}

.tagihan-pay-preset-chip.is-full {
    background-color: rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.35);
    color: #059669;
    font-weight: 800;
}

.tagihan-pay-preset-chip.is-full:hover {
    background-color: rgba(16, 185, 129, 0.18);
}

.tagihan-pay-calc-card {
    margin-top: 14px;
    padding: 14px 16px;
    border-radius: 14px;
    transition: all 0.2s ease;
}

.tagihan-pay-calc-card.is-valid {
    background-color: rgba(16, 185, 129, 0.06);
    border: 1px solid rgba(16, 185, 129, 0.25);
}

.dark .tagihan-pay-calc-card.is-valid {
    background-color: rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.3);
}

.tagihan-pay-calc-card.is-overpaid {
    background-color: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.dark .tagihan-pay-calc-card.is-overpaid {
    background-color: rgba(239, 68, 68, 0.12);
    border-color: rgba(239, 68, 68, 0.35);
}

.tagihan-pay-calc-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 0;
}

.tagihan-pay-calc-row:not(:first-child) {
    border-top: 1px dashed rgba(16, 185, 129, 0.2);
    margin-top: 3px;
    padding-top: 7px;
}

.dark .tagihan-pay-calc-row:not(:first-child) {
    border-top-color: rgba(16, 185, 129, 0.25);
}

.tagihan-pay-textarea {
    resize: none;
    min-height: 70px;
    line-height: 1.5;
}

/* ========================================================================= */
/* 8. RESPONSIVE MEDIA QUERIES (Mobile Screen Optimizations)                 */
/* ========================================================================= */
@media (max-width: 640px) {
    .tagihan-card {
        padding: 15px 14px !important;
    }
    .floating-action-bar {
        padding: 10px 14px !important;
        padding-bottom: max(10px, env(safe-area-inset-bottom)) !important;
    }
    .tagihan-modal-backdrop,
    .tagihan-modal-overlay {
        padding: 12px !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .tagihan-modal-shell,
    .tagihan-modal-guide-shell {
        width: 100% !important;
        max-width: 100% !important;
        max-height: 92vh !important;
        max-height: 92dvh !important;
        border-radius: 18px !important;
        margin: auto !important;
    }
    .tagihan-modal-header {
        padding: 14px 16px !important;
    }
    .tagihan-modal-body {
        padding: 16px 14px !important;
    }
    .tagihan-modal-footer {
        padding: 12px 16px !important;
    }
}
</style>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('tagihanApp', () => ({
        activeTab: '<?= $activeTab ?>',
        searchQuery: '',
        
        // Tab 1: Buat Tagihan State
        selectedVisits: [],
        totalNominal: 0,
        selectedStoreName: '',
        visitsData: <?= json_encode(array_map(fn($v) => [
            'id' => $v['id'],
            'pelanggan_id' => $v['pelanggan_id'],
            'nama_toko' => $v['nama_toko'],
            'total_laku_nominal' => (float)$v['total_laku_nominal']
        ], $unbilledVisits)) ?>,

        init() {
            const params = new URLSearchParams(window.location.search);
            if (params.has('tab')) {
                this.activeTab = params.get('tab');
            }
        },

        setTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        toggleVisit(id) {
            const index = this.selectedVisits.indexOf(id);
            if (index === -1) {
                this.selectedVisits.push(id);
            } else {
                this.selectedVisits.splice(index, 1);
            }
            this.recalculate();
        },

        recalculate() {
            let total = 0;
            let storeName = '';
            this.selectedVisits.forEach(id => {
                const v = this.visitsData.find(x => x.id === id);
                if (v) {
                    total += v.total_laku_nominal;
                    if (!storeName) storeName = v.nama_toko;
                }
            });
            this.totalNominal = total;
            this.selectedStoreName = storeName;
        },

        isMixedStore() {
            if (this.selectedVisits.length <= 1) return false;
            let storeId = null;
            for (let i = 0; i < this.selectedVisits.length; i++) {
                const v = this.visitsData.find(x => x.id === this.selectedVisits[i]);
                if (v) {
                    if (storeId === null) storeId = v.pelanggan_id;
                    else if (storeId !== v.pelanggan_id) return true;
                }
            }
            return false;
        },

        clearSelection() {
            this.selectedVisits = [];
            this.recalculate();
        },

        // Tab 2: Modal Catat Pembayaran State
        bayarModalOpen: false,
        bayarData: {
            pesanan_id: '',
            nama_toko: '',
            nomor_nota: '',
            sisa_tagihan: 0,
            sisa_formatted: '',
            nominal: '',
            tanggal_bayar: '<?= date('Y-m-d') ?>',
            keterangan: ''
        },

        openBayarModal(id, toko, nota, sisa) {
            this.bayarData.pesanan_id = id;
            this.bayarData.nama_toko = toko;
            this.bayarData.nomor_nota = nota;
            this.bayarData.sisa_tagihan = parseFloat(sisa) || 0;
            this.bayarData.sisa_formatted = this.formatRupiah(this.bayarData.sisa_tagihan);
            this.bayarData.nominal = '';
            this.bayarData.tanggal_bayar = '<?= date('Y-m-d') ?>';
            this.bayarData.keterangan = '';
            this.bayarModalOpen = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        closeBayarModal() {
            this.bayarModalOpen = false;
            document.body.style.overflow = '';
        },

        setBayarPreset(percentage) {
            this.bayarData.nominal = Math.round(this.bayarData.sisa_tagihan * (percentage / 100));
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        setBayarFull() {
            this.setBayarPreset(100);
        },

        setBayarHalf() {
            this.setBayarPreset(50);
        },

        getCleanNominal() {
            if (!this.bayarData.nominal || this.bayarData.nominal === '') return 0;
            const val = parseFloat(this.bayarData.nominal);
            return isNaN(val) ? 0 : Math.max(0, val);
        },

        getSisaBaru() {
            const nom = this.getCleanNominal();
            return Math.max(0, this.bayarData.sisa_tagihan - nom);
        },

        isOverpaid() {
            const nom = this.getCleanNominal();
            return nom > 0 && nom > this.bayarData.sisa_tagihan;
        },

        isLunas() {
            const nom = this.getCleanNominal();
            return nom > 0 && nom >= this.bayarData.sisa_tagihan;
        },

        canSubmitBayar() {
            const nom = this.getCleanNominal();
            return nom > 0 && nom <= this.bayarData.sisa_tagihan;
        },

        formatRupiah(num) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(num || 0);
        },

        // Modal Panduan & Ketentuan State
        guideModalOpen: false,

        openGuideModal() {
            this.guideModalOpen = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        closeGuideModal() {
            this.guideModalOpen = false;
            document.body.style.overflow = '';
        },

        startFromGuide() {
            this.closeGuideModal();
            this.setTab('buat');
        }
    }));
});
</script>

<div class="tagihan-page-container" x-data="tagihanApp()">
     
    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER                                                            -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary btn-sm p-2 rounded-xl" title="Kembali ke Portal Konsinyasi">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#10b981;"></span>
                    <span>Modul Konsinyasi &bull; Keuangan &amp; Piutang</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="page-subtitle text-xs sm:text-sm">Penerbitan faktur tagihan batch dari kunjungan opname &amp; pencatatan pelunasan piutang toko mitra.</p>
            </div>
        </div>
        
        <div class="page-header-actions" style="display:flex; gap:8px; align-items:center;">
            <button type="button" 
                    @click="openGuideModal()" 
                    class="btn btn-secondary"
                    style="height:38px; display:inline-flex; align-items:center; gap:6px; font-weight:700; border-radius:12px;"
                    title="Buka Panduan & Ketentuan Tagihan">
                <i data-lucide="help-circle" class="w-4 h-4 text-sky-500"></i>
                <span class="tagihan-guide-btn-text">Panduan &amp; Ketentuan</span>
            </button>
            <a href="<?= Router::url('/consignment/tagihan/export-excel') ?>?status=<?= urlencode($filterStatus) ?>" 
               class="btn btn-secondary"
               style="height:38px; background:#10b981; color:#fff; border-color:#059669; font-weight:700; display:inline-flex; align-items:center; gap:6px; border-radius:12px;"
               x-show="activeTab === 'daftar'" 
               x-cloak>
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                <span>Export Excel</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. TAB NAVIGATION SWITCHER                                                -->
    <!-- ========================================================================= -->
    <div class="tagihan-tabs-row">
        <div class="tagihan-tabs-nav">
            <button type="button" 
                    @click="setTab('buat')" 
                    :class="{'is-active': activeTab === 'buat'}"
                    class="tagihan-tab-btn">
                <i data-lucide="plus-circle" class="w-4 h-4 text-sky-500"></i>
                <span>Buat Tagihan</span>
                <span class="badge" 
                      style="font-size:10px; font-weight:800; padding:1px 6px;"
                      :style="activeTab === 'buat' ? 'background:rgba(2,132,199,0.15);color:#0284c7;' : 'background:var(--color-hairline);color:var(--color-ink-mute);'">
                    <?= count($unbilledVisits) ?>
                </span>
            </button>
            <button type="button" 
                    @click="setTab('daftar')" 
                    :class="{'is-active': activeTab === 'daftar'}"
                    class="tagihan-tab-btn">
                <i data-lucide="receipt" class="w-4 h-4 text-emerald-500"></i>
                <span>Daftar Tagihan</span>
                <span class="badge" 
                      style="font-size:10px; font-weight:800; padding:1px 6px;"
                      :style="activeTab === 'daftar' ? 'background:rgba(16,185,129,0.15);color:#10b981;' : 'background:var(--color-hairline);color:var(--color-ink-mute);'">
                    <?= count($tagihan) ?>
                </span>
            </button>
        </div>

        <div class="hidden sm:flex items-center">
            <div x-show="activeTab === 'buat'" class="tagihan-tab-hint">
                <i data-lucide="layers" class="w-3.5 h-3.5 text-sky-500"></i>
                <span>Gabungkan kunjungan opname jadi 1 faktur piutang resmi</span>
            </div>
            <div x-show="activeTab === 'daftar'" class="tagihan-tab-hint" x-cloak>
                <i data-lucide="wallet" class="w-3.5 h-3.5 text-emerald-500"></i>
                <span>Pantau saldo piutang &amp; catat kas pelunasan</span>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. TAB 1: BUAT TAGIHAN (BATCH INVOICING)                                  -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'buat'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="tagihan-tab-pane">
        
        <!-- Filter Form Buat Tagihan -->
        <div class="tagihan-card">
            <form action="<?= Router::url('/consignment/tagihan') ?>" method="GET">
                <input type="hidden" name="tab" value="buat">
                <div class="tagihan-card-header flex-wrap gap-2">
                    <div class="flex items-center gap-2 text-xs font-bold" style="color:var(--color-ink-secondary);">
                        <i data-lucide="filter" class="w-4 h-4 text-sky-500"></i>
                        <span>FILTER KUNJUNGAN BELUM DITAGIH</span>
                    </div>
                    <?php if (!empty($filterStoreId) || $filterStart !== date('Y-m-01') || $filterEnd !== date('Y-m-d')): ?>
                    <a href="<?= Router::url('/consignment/tagihan?tab=buat') ?>" class="text-xs font-bold text-rose-500 hover:underline flex items-center gap-1">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        <span>Reset Filter</span>
                    </a>
                    <?php endif; ?>
                </div>

                <div class="tagihan-filter-grid">
                    <div>
                        <label class="form-label" style="font-size:11.5px;font-weight:700;color:var(--color-ink-secondary);margin-bottom:7px;display:block;">Filter Toko Mitra</label>
                        <select name="pelanggan_id" class="form-select" style="height:38px; font-size:12.5px; width:100%;">
                            <option value="">Semua Toko Mitra</option>
                            <?php foreach ($stores as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $filterStoreId === (string)$s['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nama_toko']) ?> <?= !empty($s['kode_pelanggan']) ? '(' . htmlspecialchars($s['kode_pelanggan']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" style="font-size:11.5px;font-weight:700;color:var(--color-ink-secondary);margin-bottom:7px;display:block;">Dari Tanggal Kunjungan</label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($filterStart) ?>" class="form-input" style="height:38px; font-size:12.5px; width:100%;">
                    </div>

                    <div>
                        <label class="form-label" style="font-size:11.5px;font-weight:700;color:var(--color-ink-secondary);margin-bottom:7px;display:block;">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($filterEnd) ?>" class="form-input" style="height:38px; font-size:12.5px; width:100%;">
                    </div>

                    <div>
                        <label class="tagihan-label-spacer">&nbsp;</label>
                        <div class="tagihan-filter-actions">
                            <button type="submit" class="btn btn-primary flex-1" style="height:38px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:6px; border-radius:10px;">
                                <i data-lucide="search" class="w-4 h-4"></i>
                                <span>Cari Kunjungan</span>
                            </button>
                            <?php if (!empty($filterStoreId) || $filterStart !== date('Y-m-01') || $filterEnd !== date('Y-m-d')): ?>
                            <a href="<?= Router::url('/consignment/tagihan?tab=buat') ?>" class="btn btn-secondary" style="height:38px; padding:0 12px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px;" title="Reset Filter">
                                <i data-lucide="rotate-ccw" class="w-4 h-4 text-rose-500"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Info Callout Banner -->
        <div class="tagihan-alert-banner">
            <i data-lucide="info" class="w-5 h-5 flex-shrink-0 text-sky-600 dark:text-sky-400 mt-0.5"></i>
            <div class="text-xs sm:text-sm leading-relaxed">
                Pilih satu atau lebih kunjungan opname dengan nominal terjual untuk digabungkan menjadi <strong>1 Faktur Tagihan Konsinyasi</strong> resmi. 
                <span class="font-bold text-sky-700 dark:text-sky-300">Seluruh kunjungan yang digabungkan harus berasal dari toko mitra yang sama.</span>
            </div>
        </div>

        <?php if (empty($unbilledVisits)): ?>
            <!-- Empty State: Semua Kunjungan Sudah Ditagih -->
            <div class="tagihan-empty-state">
                <div class="tagihan-empty-state-icon is-success">
                    <i data-lucide="check-circle-2" style="width:28px;height:28px;"></i>
                </div>
                <h3 class="tagihan-empty-state-title">Semua Kunjungan Sudah Ditagih</h3>
                <p class="tagihan-empty-state-desc">
                    Tidak ada kunjungan baru dengan nominal laku terjual yang belum diterbitkan fakturnya pada rentang tanggal ini.
                </p>
                <div>
                    <button type="button" @click="setTab('daftar')" class="btn btn-secondary btn-sm" style="font-weight:700; font-size:12px; height:36px; padding:0 18px; border-radius:10px; display:inline-flex; align-items:center; gap:6px;">
                        <i data-lucide="receipt" class="w-4 h-4"></i>
                        <span>Lihat Daftar Tagihan Aktif</span>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <form action="<?= Router::url('/consignment/tagihan/generate') ?>" method="POST" class="relative pb-6">
                <?= CSRF::field() ?>
                
                <div class="table-wrapper" style="border-radius:18px;">
                    <div style="padding:14px 20px;border-bottom:1px solid var(--color-hairline);background-color:var(--color-canvas);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="clipboard-list" class="w-4 h-4 text-sky-500"></i>
                            <span style="font-size:13px;font-weight:800;color:var(--color-ink);">Kunjungan Menunggu Faktur</span>
                            <span class="badge badge-mono" style="font-size:10.5px;"><?= count($unbilledVisits) ?> Data</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-xs" style="color:var(--color-ink-mute);" x-show="selectedVisits.length > 0">
                                <strong class="text-sky-600 dark:text-sky-400" x-text="selectedVisits.length"></strong> kunjungan terpilih
                            </span>
                            <button type="button" 
                                    @click="clearSelection()" 
                                    x-show="selectedVisits.length > 0" 
                                    class="btn btn-ghost btn-sm" 
                                    style="font-size:11px;padding:3px 8px;"
                                    title="Batalkan Pilihan">
                                Batalkan
                            </button>
                        </div>
                    </div>

                    <div class="table-scroll">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="cell-center" style="width:50px;">Pilih</th>
                                    <th style="min-width:180px;">Tanggal &amp; No. Kunjungan</th>
                                    <th style="min-width:220px;">Toko Mitra &amp; Kode</th>
                                    <th style="min-width:130px;">Sales PIC</th>
                                    <th class="cell-center" style="width:100px;">Jml SKU</th>
                                    <th class="cell-right" style="width:150px;">Total Laku Terjual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unbilledVisits as $v): ?>
                                <tr class="cursor-pointer transition-colors" 
                                    :class="{'tagihan-row-selected': selectedVisits.includes('<?= $v['id'] ?>')}"
                                    @click="toggleVisit('<?= $v['id'] ?>')">
                                    
                                    <td class="cell-center" @click.stop>
                                        <input type="checkbox" 
                                               name="kunjungan_ids[]" 
                                               value="<?= $v['id'] ?>" 
                                               class="form-checkbox"
                                               style="width:18px;height:18px;border-radius:5px;cursor:pointer;"
                                               :checked="selectedVisits.includes('<?= $v['id'] ?>')"
                                               @change="toggleVisit('<?= $v['id'] ?>')">
                                    </td>

                                    <!-- Tanggal & No Kunjungan (2-Line) -->
                                    <td>
                                        <div style="display:flex;flex-direction:column;gap:3px;">
                                            <div style="font-weight:800;color:var(--color-ink);font-size:13px;display:flex;align-items:center;gap:5px;">
                                                <i data-lucide="calendar" class="w-3.5 h-3.5" style="color:var(--color-ink-mute);"></i>
                                                <span><?= date('d/m/Y', strtotime($v['tanggal_kunjungan'])) ?></span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="badge badge-mono font-mono" style="font-size:10.5px;letter-spacing:0.02em;">
                                                    <?= htmlspecialchars($v['nomor_kunjungan']) ?>
                                                </span>
                                                <a href="<?= Router::url('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)$v['id']) . '&ref=tagihan') ?>" 
                                                   class="text-sky-600 hover:text-sky-700 p-0.5" 
                                                   title="Buka rincian opname kunjungan ini"
                                                   @click.stop>
                                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Toko Mitra & Kode (2-Line) -->
                                    <td>
                                        <div style="display:flex;flex-direction:column;gap:3px;">
                                            <strong style="color:var(--color-ink);font-size:13px;">
                                                <?= htmlspecialchars($v['nama_toko']) ?>
                                            </strong>
                                            <div class="flex items-center gap-1.5">
                                                <span style="font-size:10px;font-weight:800;color:#0284c7;background:rgba(2,132,199,0.08);padding:1px 6px;border-radius:4px;border:1px solid rgba(2,132,199,0.18);font-family:var(--font-mono);">
                                                    <?= htmlspecialchars($v['kode_pelanggan'] ?: 'TOKO') ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Sales PIC -->
                                    <td style="color:var(--color-ink-secondary);font-size:12px;font-weight:600;">
                                        <div class="flex items-center gap-1.5">
                                            <i data-lucide="user" class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--color-ink-mute);"></i>
                                            <span class="truncate"><?= htmlspecialchars($v['nama_sales']) ?></span>
                                        </div>
                                    </td>

                                    <!-- Jml SKU -->
                                    <td class="cell-center">
                                        <span class="badge badge-mono font-bold">
                                            <?= $v['total_sku'] ?> SKU
                                        </span>
                                    </td>

                                    <!-- Total Laku Nominal -->
                                    <td class="cell-right cell-currency" style="font-weight:800;color:#10b981;font-size:13.5px;">
                                        <?= Format::rupiah($v['total_laku_nominal']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- STICKY FLOATING ACTION BAR -->
                <div class="floating-action-bar" 
                     x-show="selectedVisits.length > 0" 
                     x-transition:enter="transition ease-out duration-200" 
                     x-transition:enter-start="transform translate-y-full" 
                     x-transition:enter-end="transform translate-y-0" 
                     x-transition:leave="transition ease-in duration-150" 
                     x-transition:leave-start="transform translate-y-0" 
                     x-transition:leave-end="transform translate-y-full" 
                     x-cloak>
                    <div style="max-width:1200px; margin:0 auto; width:100%;" class="flex flex-col sm:flex-row justify-between items-center gap-3">
                        <!-- Info Ringkasan Terpilih -->
                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <div style="width:38px;height:38px;border-radius:10px;background:rgba(2,132,199,0.12);color:#0284c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="check-square" style="width:19px;height:19px;"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div style="font-size:11.5px; color:var(--color-ink-mute); line-height:1.2;">
                                    <span>Total Terpilih (<strong style="color:var(--color-ink);" x-text="`${selectedVisits.length} Kunjungan`"></strong>)</span>
                                    <template x-if="selectedStoreName && !isMixedStore()">
                                        <span class="truncate">&bull; Toko: <strong style="color:var(--color-ink);" x-text="selectedStoreName"></strong></span>
                                    </template>
                                </div>
                                <div class="font-mono font-black text-sky-600 dark:text-sky-400" style="font-size:18px; line-height:1.2;" x-text="formatRupiah(totalNominal)"></div>
                            </div>
                        </div>

                        <!-- Action Buttons & Validation Alert -->
                        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                            <template x-if="isMixedStore()">
                                <div class="badge badge-danger" style="padding:5px 10px; font-size:11px; font-weight:700; display:inline-flex; align-items:center; gap:5px;">
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                                    <span>Kunjungan Beda Toko! Pilih 1 Toko yang Sama</span>
                                </div>
                            </template>

                            <button type="submit" 
                                    class="btn btn-primary w-full sm:w-auto"
                                    style="height:40px; padding:0 20px; font-size:12.5px; font-weight:800; display:inline-flex; align-items:center; justify-content:center; gap:7px; border-radius:10px;"
                                    :disabled="selectedVisits.length === 0 || isMixedStore()">
                                <i data-lucide="file-check-2" class="w-4 h-4"></i>
                                <span>Buat Faktur Tagihan &rarr;</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. TAB 2: DAFTAR TAGIHAN (RECEIVABLES & PAYMENTS)                          -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'daftar'" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="tagihan-tab-pane">
        
        <!-- 4-CARD FINANCIAL OVERVIEW -->
        <div class="tagihan-kpi-grid">
            
            <!-- CARD 1: Total Piutang Tertunggak -->
            <div class="tagihan-kpi-card" style="border-left:4px solid #f43f5e;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div class="tagihan-kpi-label">Piutang Belum Lunas</div>
                    <div class="tagihan-kpi-icon" style="background:rgba(244,63,94,0.12);color:#f43f5e;">
                        <i data-lucide="alert-circle" style="width:20px;height:20px;"></i>
                    </div>
                </div>
                <div class="tagihan-kpi-value text-rose-600 dark:text-rose-400" style="margin:6px 0 10px 0;">
                    <?= Format::rupiah($totalPiutangTertunggak) ?>
                </div>
                <div class="tagihan-kpi-footer">
                    <i data-lucide="file-text" style="width:13px;height:13px;"></i>
                    <span><?= $countBelumLunas ?> faktur konsinyasi tertunggak</span>
                </div>
            </div>

            <!-- CARD 2: Total Sudah Terbayar -->
            <div class="tagihan-kpi-card" style="border-left:4px solid #10b981;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div class="tagihan-kpi-label">Kas Masuk Terbayar</div>
                    <div class="tagihan-kpi-icon" style="background:rgba(16,185,129,0.12);color:#10b981;">
                        <i data-lucide="wallet" style="width:20px;height:20px;"></i>
                    </div>
                </div>
                <div class="tagihan-kpi-value text-emerald-600 dark:text-emerald-400" style="margin:6px 0 10px 0;">
                    <?= Format::rupiah($totalSudahDibayar) ?>
                </div>
                <div class="tagihan-kpi-footer">
                    <i data-lucide="check-circle-2" style="width:13px;height:13px;"></i>
                    <span>Pelunasan piutang konsinyasi</span>
                </div>
            </div>

            <!-- CARD 3: Total Nilai Faktur Diterbitkan -->
            <div class="tagihan-kpi-card" style="border-left:4px solid #0284c7;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div class="tagihan-kpi-label">Total Nilai Tagihan</div>
                    <div class="tagihan-kpi-icon" style="background:rgba(2,132,199,0.12);color:#0284c7;">
                        <i data-lucide="receipt" style="width:20px;height:20px;"></i>
                    </div>
                </div>
                <div class="tagihan-kpi-value text-sky-600 dark:text-sky-400" style="margin:6px 0 10px 0;">
                    <?= Format::rupiah($totalTagihanDiterbitkan) ?>
                </div>
                <div class="tagihan-kpi-footer">
                    <i data-lucide="layers" style="width:13px;height:13px;"></i>
                    <span><?= count($tagihan) ?> total seluruh nota diterbitkan</span>
                </div>
            </div>

            <!-- CARD 4: Tingkat Pelunasan -->
            <div class="tagihan-kpi-card" style="border-left:4px solid #8b5cf6;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div class="tagihan-kpi-label">Tingkat Pelunasan</div>
                    <div class="tagihan-kpi-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6;">
                        <i data-lucide="pie-chart" style="width:20px;height:20px;"></i>
                    </div>
                </div>
                <div class="tagihan-kpi-value text-violet-600 dark:text-violet-400" style="margin:6px 0 10px 0;">
                    <?= $collectionRate ?>%
                </div>
                <div class="tagihan-kpi-footer">
                    <i data-lucide="trending-up" style="width:13px;height:13px;"></i>
                    <span><?= $countLunas ?> faktur lunas sempurna</span>
                </div>
            </div>
        </div>

        <!-- Filter Status Chips & Search Input Bar -->
        <div class="tagihan-card">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <!-- Status Filter Chips -->
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold mr-1" style="color:var(--color-ink-mute);">Status:</span>
                    <a href="?tab=daftar" 
                       class="tagihan-filter-status-chip <?= $filterStatus === '' ? 'btn-primary' : 'btn-secondary' ?>">
                        Semua
                    </a>
                    <a href="?tab=daftar&status=belum_lunas" 
                       class="tagihan-filter-status-chip <?= $filterStatus === 'belum_lunas' ? 'btn-danger' : 'btn-secondary' ?>">
                        Belum Lunas
                    </a>
                    <a href="?tab=daftar&status=sebagian" 
                       class="tagihan-filter-status-chip <?= $filterStatus === 'sebagian' ? 'btn-warning' : 'btn-secondary' ?>">
                        Sebagian
                    </a>
                    <a href="?tab=daftar&status=lunas" 
                       class="tagihan-filter-status-chip <?= $filterStatus === 'lunas' ? 'btn-success' : 'btn-secondary' ?>">
                        Lunas
                    </a>
                </div>

                <!-- Instant Search Input -->
                <div class="form-input-icon w-full sm:w-auto" style="min-width:260px;">
                    <i data-lucide="search" class="icon-left"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="Cari toko, nota, sales..." 
                           class="form-input" 
                           style="height:36px;font-size:12.5px;border-radius:10px;width:100%;">
                </div>
            </div>
        </div>

        <?php if (empty($tagihan)): ?>
            <!-- Empty State: Tidak Ada Tagihan -->
            <div class="tagihan-empty-state">
                <div class="tagihan-empty-state-icon is-primary">
                    <i data-lucide="file-text" style="width:28px;height:28px;"></i>
                </div>
                <h3 class="tagihan-empty-state-title">Belum Ada Nota Tagihan</h3>
                <p class="tagihan-empty-state-desc">
                    <?= !empty($filterStatus) ? 'Tidak ditemukan faktur tagihan dengan status yang dipilih.' : 'Belum ada faktur tagihan konsinyasi yang diterbitkan.' ?>
                </p>
                <div>
                    <button type="button" @click="setTab('buat')" class="btn btn-primary btn-sm" style="font-weight:700; font-size:12px; height:36px; padding:0 18px; border-radius:10px; display:inline-flex; align-items:center; gap:6px;">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Buat Tagihan Pertama Sekarang</span>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <!-- Table of Tagihan -->
            <div class="table-wrapper" style="border-radius:18px;">
                <div style="padding:14px 20px;border-bottom:1px solid var(--color-hairline);background-color:var(--color-canvas);display:flex;align-items:center;justify-content:space-between;">
                    <div class="flex items-center gap-2.5">
                        <i data-lucide="receipt" class="w-4 h-4 text-emerald-500"></i>
                        <span style="font-size:13px;font-weight:800;color:var(--color-ink);">Daftar Faktur Tagihan Konsinyasi</span>
                        <span class="badge badge-mono" style="font-size:10.5px;"><?= count($tagihan) ?> Nota</span>
                    </div>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="min-width:210px;">Toko Mitra &amp; Kode</th>
                                <th style="min-width:180px;">No. Tagihan &amp; Tgl</th>
                                <th style="min-width:125px;">Sales PIC</th>
                                <th class="cell-center" style="width:105px;">Kunjungan</th>
                                <th class="cell-right" style="width:135px;">Total Nilai</th>
                                <th class="cell-right" style="width:135px;">Terbayar</th>
                                <th class="cell-right" style="width:135px;">Sisa Piutang</th>
                                <th class="cell-center" style="width:115px;">Status</th>
                                <th class="cell-center" style="width:115px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tagihan as $t): 
                                $isLunas = $t['status_pembayaran'] === 'lunas';
                                $isSebagian = $t['status_pembayaran'] === 'sebagian';
                                $searchKey = strtolower($t['nama_toko'] . ' ' . ($t['kode_pelanggan'] ?? '') . ' ' . $t['nomor_nota'] . ' ' . ($t['nama_sales'] ?? ''));
                            ?>
                            <tr x-show="!searchQuery || '<?= addslashes($searchKey) ?>'.includes(searchQuery.toLowerCase())">
                                
                                <!-- Toko Mitra & Kode (2-Line) -->
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:3px;">
                                        <strong style="color:var(--color-ink);font-size:13px;">
                                            <?= htmlspecialchars($t['nama_toko']) ?>
                                        </strong>
                                        <div class="flex items-center gap-1.5">
                                            <span style="font-size:10px;font-weight:800;color:#0284c7;background:rgba(2,132,199,0.08);padding:1px 6px;border-radius:4px;border:1px solid rgba(2,132,199,0.18);font-family:var(--font-mono);">
                                                <?= htmlspecialchars($t['kode_pelanggan'] ?: 'TOKO') ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- No Tagihan & Tanggal (2-Line) -->
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:3px;">
                                        <span class="badge badge-mono font-mono font-bold" style="font-size:11px;letter-spacing:0.01em;">
                                            <?= htmlspecialchars($t['nomor_nota']) ?>
                                        </span>
                                        <div style="font-size:11px;color:var(--color-ink-mute);display:inline-flex;align-items:center;gap:4px;">
                                            <i data-lucide="calendar" style="width:12px;height:12px;"></i>
                                            <span><?= date('d/m/Y', strtotime($t['tanggal_pesanan'])) ?></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Sales PIC -->
                                <td style="color:var(--color-ink-secondary);font-size:12px;font-weight:600;">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="user" class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--color-ink-mute);"></i>
                                        <span class="truncate"><?= htmlspecialchars($t['nama_sales']) ?></span>
                                    </div>
                                </td>

                                <!-- Kunjungan -->
                                <td class="cell-center">
                                    <span class="badge badge-mono">
                                        <?= $t['jumlah_kunjungan'] ?>x visit
                                    </span>
                                </td>

                                <!-- Total Nilai -->
                                <td class="cell-right cell-currency" style="font-weight:800;font-size:13px;color:var(--color-ink);">
                                    <?= Format::rupiah($t['total_netto']) ?>
                                </td>

                                <!-- Terbayar -->
                                <td class="cell-right cell-currency" style="font-weight:700;font-size:12.5px;color:#10b981;">
                                    <?= Format::rupiah($t['total_dibayar']) ?>
                                </td>

                                <!-- Sisa Piutang -->
                                <td class="cell-right cell-currency font-bold text-rose-600 dark:text-rose-400" style="font-size:13px;">
                                    <?= Format::rupiah($t['sisa_tagihan']) ?>
                                </td>

                                <!-- Status Badge -->
                                <td class="cell-center">
                                    <?php if ($isLunas): ?>
                                        <span class="badge badge-success" style="font-size:10.5px;padding:3px 9px;">Lunas</span>
                                    <?php elseif ($isSebagian): ?>
                                        <span class="badge badge-warning" style="font-size:10.5px;padding:3px 9px;">Sebagian</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger" style="font-size:10.5px;padding:3px 9px;">Belum Lunas</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Aksi -->
                                <td class="cell-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="<?= Router::url('/consignment/nota-pdf?pesanan_id=' . $t['pesanan_id']) ?>" 
                                           target="_blank" 
                                           class="btn btn-secondary btn-sm" 
                                           style="padding:5px 8px;border-radius:8px;" 
                                           title="Cetak Faktur PDF">
                                            <i data-lucide="printer" style="width:14px;height:14px;"></i>
                                        </a>

                                        <?php if (!$isLunas && ($isAdmin || $isOwner)): ?>
                                            <button type="button" 
                                                    @click="openBayarModal('<?= $t['pesanan_id'] ?>', '<?= htmlspecialchars(addslashes($t['nama_toko'])) ?>', '<?= htmlspecialchars(addslashes($t['nomor_nota'])) ?>', <?= $t['sisa_tagihan'] ?>)" 
                                                    class="btn btn-primary btn-sm"
                                                    style="padding:5px 10px;font-size:11px;font-weight:700;border-radius:8px;gap:4px;display:inline-flex;align-items:center;">
                                                <i data-lucide="wallet" style="width:13px;height:13px;"></i>
                                                <span>Bayar</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. MODAL POP-UP CATAT PEMBAYARAN (3-Layer Modern Shell)                   -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="bayarModalOpen" 
         x-cloak 
         class="tagihan-modal-backdrop" 
         @click.self="closeBayarModal()" 
         @keydown.escape.window="closeBayarModal()">
        
        <div class="tagihan-modal-shell" @click.stop>
            
            <!-- LAYER 1: MODAL HEADER (STICKY) -->
            <div class="tagihan-modal-header">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(2,132,199,0.12);color:#0284c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="wallet" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate" style="font-size:15px;font-weight:900;color:var(--color-ink);margin:0;">Catat Pembayaran Tagihan</h3>
                        <div class="text-xs truncate" style="color:var(--color-ink-mute);margin-top:2px;">
                            Pelunasan faktur piutang konsinyasi
                        </div>
                    </div>
                </div>

                <button type="button" @click="closeBayarModal()" class="btn btn-ghost btn-sm flex-shrink-0" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;" aria-label="Tutup">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <!-- LAYER 2: MODAL BODY (SCROLLABLE & TOUCH FRIENDLY) -->
            <form action="<?= Router::url('/consignment/tagihan/bayar') ?>" 
                  method="POST" 
                  class="flex flex-col flex-1 overflow-hidden"
                  @submit="if(!canSubmitBayar()) { $event.preventDefault(); return; } if(!confirm('Proses pembayaran ini? Saldo kas akan langsung bertambah dan sisa piutang berkurang.')) $event.preventDefault();">
                
                <?= CSRF::field() ?>
                <input type="hidden" name="pesanan_id" x-model="bayarData.pesanan_id">

                <div class="tagihan-modal-body custom-scrollbar space-y-4">
                    
                    <!-- 1. Ringkasan Faktur Toko & Sisa Piutang Sekarang -->
                    <div style="background:linear-gradient(135deg, var(--color-canvas-soft) 0%, rgba(2, 132, 199, 0.04) 100%);border:1px solid var(--color-hairline);border-radius:16px;padding:16px 18px;">
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <span class="text-xs font-bold flex items-center gap-1.5" style="color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;">
                                <i data-lucide="store" style="width:13px;height:13px;color:#0284c7;"></i>
                                Toko Mitra
                            </span>
                            <span class="badge badge-mono font-mono" style="font-size:10.5px;padding:3px 8px;border-radius:6px;" x-text="bayarData.nomor_nota"></span>
                        </div>
                        <div class="text-base font-black truncate" style="color:var(--color-ink);" x-text="bayarData.nama_toko"></div>
                        
                        <div class="flex items-center justify-between pt-3 mt-3 border-t" style="border-color:var(--color-hairline-cool);">
                            <span class="text-xs font-bold" style="color:var(--color-ink-mute);">Sisa Piutang Sekarang:</span>
                            <span class="font-mono font-black text-rose-600 dark:text-rose-400" style="font-size:19px;" x-text="bayarData.sisa_formatted"></span>
                        </div>
                    </div>

                    <!-- 2. Tanggal Pembayaran & Rekening Kas Penerima (2 Kolom) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="tagihan-pay-field">
                            <label class="tagihan-pay-label">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="calendar" style="width:13px;height:13px;color:var(--color-ink-mute);"></i>
                                    Tanggal Bayar
                                </span>
                                <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="date" 
                                   name="tanggal_bayar" 
                                   x-model="bayarData.tanggal_bayar" 
                                   required 
                                   class="tagihan-pay-input font-mono text-xs" 
                                   style="height:44px;">
                        </div>

                        <div class="tagihan-pay-field">
                            <label class="tagihan-pay-label">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="landmark" style="width:13px;height:13px;color:var(--color-ink-mute);"></i>
                                    Akun Kas / Bank
                                </span>
                                <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <select name="akun_kas_id" required class="tagihan-pay-select text-xs font-semibold" style="height:44px;">
                                <option value="">-- Pilih Rekening Kas --</option>
                                <?php foreach ($cashAccounts as $ca): ?>
                                    <option value="<?= $ca['id'] ?>" <?= (!empty($ca['is_default_pos']) ? 'selected' : '') ?>>
                                        <?= htmlspecialchars($ca['nama_akun']) ?> <?= !empty($ca['is_default_pos']) ? '⭐ (Default)' : '' ?> (<?= Format::rupiah($ca['saldo_saat_ini']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- 3. Input Nominal Pembayaran & Modern Preset Chips -->
                    <div class="tagihan-pay-field">
                        <div class="tagihan-pay-label">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="wallet" style="width:13px;height:13px;color:#0284c7;"></i>
                                Nominal Pembayaran
                                <span class="text-rose-500 font-bold">*</span>
                            </span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" 
                                        @click="setBayarPreset(25)" 
                                        class="tagihan-pay-preset-chip">
                                    25%
                                </button>
                                <button type="button" 
                                        @click="setBayarPreset(50)" 
                                        class="tagihan-pay-preset-chip">
                                    50%
                                </button>
                                <button type="button" 
                                        @click="setBayarFull()" 
                                        class="tagihan-pay-preset-chip is-full">
                                    Bayar Lunas (100%)
                                </button>
                            </div>
                        </div>

                        <div class="tagihan-pay-amount-box" :class="isOverpaid() ? 'is-invalid' : ''">
                            <span class="tagihan-pay-amount-prefix">Rp</span>
                            <input type="number" 
                                   name="nominal" 
                                   x-model="bayarData.nominal" 
                                   @input="$nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                                   required 
                                   min="1" 
                                   :max="bayarData.sisa_tagihan" 
                                   class="tagihan-pay-amount-input" 
                                   placeholder="0">
                        </div>
                    </div>

                    <!-- 4. LIVE CALCULATION & STATUS PREVIEW CARD (Spacious & Clean) -->
                    <div x-show="getCleanNominal() > 0" x-cloak>
                        
                        <!-- Overpayment Warning Card -->
                        <div x-show="isOverpaid()" class="tagihan-pay-calc-card is-overpaid">
                            <div class="flex items-start gap-2.5 text-rose-600 dark:text-rose-400">
                                <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;margin-top:2px;"></i>
                                <div class="text-xs leading-relaxed">
                                    <strong class="font-bold text-rose-700 dark:text-rose-300">Nominal melebihi sisa piutang!</strong><br>
                                    Sisa tagihan saat ini adalah <span class="font-mono font-bold" x-text="bayarData.sisa_formatted"></span>. Mohon kurangi nominal pembayaran.
                                </div>
                            </div>
                        </div>

                        <!-- Valid Dynamic Calculation Card -->
                        <div x-show="!isOverpaid()" class="tagihan-pay-calc-card is-valid">
                            <div class="tagihan-pay-calc-row">
                                <span class="text-xs font-semibold flex items-center gap-1.5" style="color:var(--color-ink-mute);">
                                    <i data-lucide="arrow-down-right" style="width:14px;height:14px;color:#10b981;"></i>
                                    Nominal Masuk Kas
                                </span>
                                <strong class="font-mono text-base font-black text-emerald-600 dark:text-emerald-400" x-text="formatRupiah(getCleanNominal())"></strong>
                            </div>

                            <div class="tagihan-pay-calc-row">
                                <span class="text-xs font-semibold flex items-center gap-1.5" style="color:var(--color-ink-mute);">
                                    <i data-lucide="scale" style="width:14px;height:14px;color:#f59e0b;"></i>
                                    Sisa Piutang Baru
                                </span>
                                <strong class="font-mono text-sm font-black" :class="getSisaBaru() === 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'" x-text="formatRupiah(getSisaBaru())"></strong>
                            </div>

                            <div class="tagihan-pay-calc-row">
                                <span class="text-xs font-semibold flex items-center gap-1.5" style="color:var(--color-ink-mute);">
                                    <i data-lucide="check-circle-2" style="width:14px;height:14px;color:#0284c7;"></i>
                                    Prediksi Status Faktur
                                </span>
                                <div>
                                    <span x-show="isLunas()" class="badge badge-success font-black" style="font-size:10.5px;padding:3px 10px;border-radius:20px;">
                                        🟢 LUNAS (Piutang Habis)
                                    </span>
                                    <span x-show="!isLunas()" class="badge badge-warning font-black" style="font-size:10.5px;padding:3px 10px;border-radius:20px;">
                                        🟡 SEBAGIAN / CICILAN
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Input Keterangan / Catatan Pembayaran -->
                    <div class="tagihan-pay-field">
                        <label class="tagihan-pay-label">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="file-text" style="width:13px;height:13px;color:var(--color-ink-mute);"></i>
                                Catatan Pembayaran
                            </span>
                            <span class="text-xs font-normal" style="color:var(--color-ink-mute);">(Opsional)</span>
                        </label>
                        <textarea name="keterangan" 
                                  x-model="bayarData.keterangan"
                                  rows="2" 
                                  class="tagihan-pay-textarea text-xs" 
                                  placeholder="Contoh: Titipan tunai via sales driver, transfer m-banking, dll..."></textarea>
                    </div>

                </div>

                <!-- LAYER 3: MODAL FOOTER (STICKY) -->
                <div class="tagihan-modal-footer">
                    <button type="button" 
                            @click="closeBayarModal()" 
                            class="btn btn-secondary btn-sm flex-1 sm:flex-initial justify-center" 
                            style="font-weight:700;font-size:12.5px;padding:8px 18px;border-radius:10px;">
                        <span>Batal</span>
                    </button>
                    <button type="submit" 
                            :disabled="!canSubmitBayar()" 
                            class="btn btn-primary btn-sm flex-1 sm:flex-initial justify-center disabled:opacity-40 disabled:cursor-not-allowed" 
                            style="font-weight:800;font-size:12.5px;padding:8px 20px;border-radius:10px;gap:7px;box-shadow:0 2px 8px rgba(2,132,199,0.25);">
                        <i data-lucide="check" style="width:15px;height:15px;"></i>
                        <span>Simpan Pembayaran</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- 6. MODAL PANDUAN & KETENTUAN OPERASIONAL TAGIHAN (3-Layer Teleported)      -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="guideModalOpen" 
         x-cloak 
         class="tagihan-modal-backdrop" 
         @click.self="closeGuideModal()" 
         @keydown.escape.window="closeGuideModal()">
        
        <div class="tagihan-modal-guide-shell" @click.stop>
            
            <!-- LAYER 1: MODAL HEADER (STICKY) -->
            <div class="tagihan-modal-header">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(2,132,199,0.12);color:#0284c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="book-open" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="truncate" style="font-size:15px;font-weight:900;color:var(--color-ink);margin:0;">Panduan &amp; Ketentuan Tagihan</h3>
                            <span class="badge" style="font-size:10px;font-weight:800;background:rgba(16,185,129,0.15);color:#10b981;border:1px solid rgba(16,185,129,0.3);padding:1px 7px;border-radius:6px;flex-shrink:0;">SOP ERP</span>
                        </div>
                        <p class="text-xs truncate" style="color:var(--color-ink-mute);margin:2px 0 0;">
                            Standard Operating Procedure &amp; aturan sistem konsinyasi
                        </p>
                    </div>
                </div>
                <button type="button" 
                        @click="closeGuideModal()" 
                        class="btn btn-ghost btn-sm flex-shrink-0" 
                        style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;" 
                        aria-label="Tutup">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <!-- LAYER 2: MODAL BODY (SCROLLABLE) -->
            <div class="tagihan-modal-body" style="display:flex;flex-direction:column;gap:20px;">
                
                <!-- 1. ALUR KERJA 4 TAHAP (STEP-BY-STEP WORKFLOW) -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <i data-lucide="git-commit" class="w-4 h-4 text-sky-500"></i>
                        <h4 style="font-size:13px;font-weight:800;color:var(--color-ink);text-transform:uppercase;letter-spacing:0.04em;margin:0;">
                            Alur Penagihan Konsinyasi (4 Langkah)
                        </h4>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:12px;">
                        <!-- Step 1 -->
                        <div style="background-color:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;padding:14px;display:flex;gap:12px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:#0284c7;color:#fff;font-weight:800;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">1</div>
                            <div>
                                <div style="font-size:12.5px;font-weight:700;color:var(--color-ink);margin-bottom:3px;">Kunjungan &amp; Opname</div>
                                <div style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.45;">
                                    Sales mengunjungi toko mitra, menghitung sisa stok fisik rak, retur, &amp; barang terjual tanpa menagih uang tunai di tempat.
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div style="background-color:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;padding:14px;display:flex;gap:12px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:#0284c7;color:#fff;font-weight:800;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">2</div>
                            <div>
                                <div style="font-size:12.5px;font-weight:700;color:var(--color-ink);margin-bottom:3px;">Batch Invoicing</div>
                                <div style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.45;">
                                    Admin memilih 1 atau lebih kunjungan dari toko yang sama di tab <strong>Buat Tagihan</strong> untuk diterbitkan 1 faktur resmi.
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div style="background-color:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;padding:14px;display:flex;gap:12px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:#0284c7;color:#fff;font-weight:800;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">3</div>
                            <div>
                                <div style="font-size:12.5px;font-weight:700;color:var(--color-ink);margin-bottom:3px;">Kirim Nota &amp; Piutang</div>
                                <div style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.45;">
                                    Faktur dicetak atau dikirim via WhatsApp. Nilai faktur otomatis tercatat menambah <em>Piutang Berjalan</em> toko mitra.
                                </div>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div style="background-color:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;padding:14px;display:flex;gap:12px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:#10b981;color:#fff;font-weight:800;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">4</div>
                            <div>
                                <div style="font-size:12.5px;font-weight:700;color:var(--color-ink);margin-bottom:3px;">Catat Pembayaran</div>
                                <div style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.45;">
                                    Saat toko membayar via transfer/tunai, catat di tab <strong>Daftar Tagihan</strong>. Kas bertambah dan piutang toko berkurang.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. KETENTUAN VALIDASI PEMBUATAN FAKTUR (DATABASE LEVEL) -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i>
                        <h4 style="font-size:13px;font-weight:800;color:var(--color-ink);text-transform:uppercase;letter-spacing:0.04em;margin:0;">
                            Ketentuan Mutlak Pembuatan Tagihan (Sistem Database)
                        </h4>
                    </div>

                    <div style="background-color:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:14px;overflow:hidden;">
                        
                        <div style="padding:12px 16px;border-bottom:1px solid var(--color-hairline);display:flex;align-items:flex-start;gap:10px;">
                            <i data-lucide="store" class="w-4 h-4 text-sky-500 flex-shrink-0" style="margin-top:2px;"></i>
                            <div>
                                <div style="font-size:12px;font-weight:700;color:var(--color-ink);">1. Satu Toko Mitra per Faktur (Single-Partner Rule)</div>
                                <div style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.4;margin-top:2px;">
                                    Seluruh kunjungan yang digabungkan dalam 1 faktur <strong>wajib berasal dari 1 toko yang sama</strong>. Sistem akan otomatis memblokir pembuatan tagihan jika Anda memilih kunjungan dari toko berbeda.
                                </div>
                            </div>
                        </div>

                        <div style="padding:12px 16px;border-bottom:1px solid var(--color-hairline);display:flex;align-items:flex-start;gap:10px;">
                            <i data-lucide="lock" class="w-4 h-4 text-amber-500 flex-shrink-0" style="margin-top:2px;"></i>
                            <div>
                                <div style="font-size:12px;font-weight:700;color:var(--color-ink);">2. Anti Double-Billing (Bebas Tagihan Ganda)</div>
                                <div style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.4;margin-top:2px;">
                                    Kunjungan yang sudah pernah dibuatkan tagihan dikunci secara permanen di database. Kunjungan tersebut otomatis hilang dari daftar antrean agar tidak terjadi penagihan berulang.
                                </div>
                            </div>
                        </div>

                        <div style="padding:12px 16px;border-bottom:1px solid var(--color-hairline);display:flex;align-items:flex-start;gap:10px;">
                            <i data-lucide="banknote" class="w-4 h-4 text-emerald-500 flex-shrink-0" style="margin-top:2px;"></i>
                            <div>
                                <div style="font-size:12px;font-weight:700;color:var(--color-ink);">3. Syarat Penjualan Riil (&gt; Rp 0)</div>
                                <div style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.4;margin-top:2px;">
                                    Kunjungan dengan nilai penjualan Rp 0 (misal toko tutup atau tidak ada barang laku) ditolak oleh database dan tidak dapat dijadikan faktur piutang.
                                </div>
                            </div>
                        </div>

                        <div style="padding:12px 16px;display:flex;align-items:flex-start;gap:10px;">
                            <i data-lucide="layers" class="w-4 h-4 text-indigo-500 flex-shrink-0" style="margin-top:2px;"></i>
                            <div>
                                <div style="font-size:12px;font-weight:700;color:var(--color-ink);">4. Format Nomor Nota &amp; Agregasi Produk Otomatis</div>
                                <div style="font-size:11.5px;color:var(--color-ink-mute);line-height:1.4;margin-top:2px;">
                                    Faktur otomatis diberi nomor resmi berformat <code>INV-KONSIN-YYYYMMDD-HHMMSS</code>. Item barang dari seluruh kunjungan terpilih otomatis dirangkum per SKU dengan kuantitas terakumulasi dan harga satuan kesepakatan toko.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- 3. KETENTUAN PEMBAYARAN & ARUS KAS -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <i data-lucide="wallet" class="w-4 h-4 text-sky-500"></i>
                        <h4 style="font-size:13px;font-weight:800;color:var(--color-ink);text-transform:uppercase;letter-spacing:0.04em;margin:0;">
                            Ketentuan Pembayaran &amp; Arus Kas
                        </h4>
                    </div>

                    <div style="background-color:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;padding:14px;display:flex;flex-direction:column;gap:10px;">
                        <div class="flex items-start gap-2.5">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500 flex-shrink-0" style="margin-top:2px;"></i>
                            <div style="font-size:11.5px;color:var(--color-ink);line-height:1.45;">
                                <strong>Pelunasan Penuh atau Bertahap (Cicil):</strong> Pembayaran dapat dilakukan langsung 100% lunas atau dicicil sebagian. Sistem menyediakan shortcut tombol <em>50%</em> dan <em>Bayar Full</em> untuk percepatan pengisian nominal.
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500 flex-shrink-0" style="margin-top:2px;"></i>
                            <div style="font-size:11.5px;color:var(--color-ink);line-height:1.45;">
                                <strong>Batas Maksimal Nominal:</strong> Database menolak pencatatan pembayaran yang melebihi sisa piutang faktur untuk mencegah kesalahan pembukuan (overpayment).
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500 flex-shrink-0" style="margin-top:2px;"></i>
                            <div style="font-size:11.5px;color:var(--color-ink);line-height:1.45;">
                                <strong>Integrasi Otomatis Arus Kas:</strong> Uang pembayaran langsung menambah saldo akun kas terpilih, tercatat di laporan arus kas kategori <em>penjualan</em>, dan memotong saldo piutang berjalan toko mitra secara real-time.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. HAK AKSES & OTORITAS -->
                <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);border-radius:14px;padding:12px 14px;display:flex;align-items:center;gap:12px;">
                    <div style="width:32px;height:32px;border-radius:10px;background:rgba(245,158,11,0.15);color:#d97706;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="user-check" class="w-4 h-4"></i>
                    </div>
                    <div style="font-size:11.5px;color:var(--color-ink);line-height:1.4;">
                        <strong>Hak Akses &amp; Otoritas:</strong> Pembuatan tagihan manual dan pencatatan kas pembayaran dibatasi untuk role <strong>Admin</strong> dan <strong>Owner</strong> yang memiliki izin <code>consignment.piutang</code> guna menjamin akurasi dan ketertiban pembukuan keuangan.
                    </div>
                </div>

            </div>

            <!-- LAYER 3: MODAL FOOTER (STICKY) -->
            <div class="tagihan-modal-footer">
                <button type="button" 
                        @click="closeGuideModal()" 
                        class="btn btn-secondary btn-sm flex-1 sm:flex-initial justify-center" 
                        style="font-weight:700;font-size:12px;">
                    <span>Tutup</span>
                </button>
                <button type="button" 
                        @click="startFromGuide()" 
                        class="btn btn-primary btn-sm flex-1 sm:flex-initial justify-center" 
                        style="font-weight:800;font-size:12px;gap:6px;">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Mulai Buat Tagihan</span>
                </button>
            </div>

        </div>
    </div>
    </template>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

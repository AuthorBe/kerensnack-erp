<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
use App\Helpers\CSRF;
use App\Helpers\CompanySetting;

ob_start();

$cNama          = $company['nama'] ?? CompanySetting::DEFAULTS['nama'];
$cTagline       = $company['tagline'] ?? CompanySetting::DEFAULTS['tagline'];
$cAlamat        = $company['alamat'] ?? CompanySetting::DEFAULTS['alamat'];
$cTelepon       = $company['telepon'] ?? CompanySetting::DEFAULTS['telepon'];
$cEmail         = $company['email'] ?? CompanySetting::DEFAULTS['email'];
$cWebsite       = $company['website'] ?? CompanySetting::DEFAULTS['website'];
$cCatatanFaktur = $company['catatan_faktur'] ?? CompanySetting::DEFAULTS['catatan_faktur'];
$cNamaBank      = $company['nama_bank'] ?? CompanySetting::DEFAULTS['nama_bank'];
$cNomorRekening = $company['nomor_rekening'] ?? CompanySetting::DEFAULTS['nomor_rekening'];
$cAtasNamaBank  = $company['atas_nama_bank'] ?? CompanySetting::DEFAULTS['atas_nama_bank'];
$cLogoUrl       = $company['logo_url'] ?? '';
?>

<style>
    /* =========================================================================
       COMPANY SETTINGS & LIVE PREVIEW SYSTEM
       Seamless integration with Supabase & M3 design language
       ========================================================================= */

    .company-page-container {
        max-width: 1280px;
        margin: 0 auto;
        padding-bottom: 2.5rem;
    }

    /* Main Responsive Grid: 2 Columns on Desktop, 1 Column on Mobile/Tablet */
    .company-layout-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(360px, 0.85fr);
        gap: 24px;
        align-items: start;
    }

    @media (max-width: 1024px) {
        .company-layout-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        .company-preview-sticky {
            position: static;
        }
        .preview-card-wrapper {
            max-height: none;
        }
    }

    /* Cards Stacking in Left Column */
    .company-form-stack {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .settings-panel-card {
        background-color: var(--color-canvas, #ffffff);
        border: 1px solid var(--color-hairline, #e2e8f0);
        border-radius: var(--rounded-lg, 12px);
        padding: 22px 24px;
        box-shadow: var(--shadow-1);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .settings-panel-card:hover {
        border-color: var(--color-hairline-strong, #cbd5e1);
    }

    .dark .settings-panel-card {
        background-color: var(--color-canvas, #303134);
        border-color: var(--color-hairline, #3c4043);
    }

    .dark .settings-panel-card:hover {
        border-color: var(--color-hairline-strong, #5f6368);
    }

    /* Section Header within Cards */
    .panel-section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid var(--color-hairline, #e2e8f0);
    }

    .dark .panel-section-header {
        border-bottom-color: var(--color-hairline, #3c4043);
    }

    .panel-icon-circle {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .panel-icon-circle svg {
        width: 18px;
        height: 18px;
    }

    .panel-icon-blue {
        background: rgba(37, 99, 235, 0.1);
        color: #2563eb;
    }
    .dark .panel-icon-blue {
        background: rgba(138, 180, 248, 0.16);
        color: #8ab4f8;
    }

    .panel-icon-emerald {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }
    .dark .panel-icon-emerald {
        background: rgba(129, 201, 149, 0.16);
        color: #81c995;
    }

    .panel-icon-purple {
        background: rgba(139, 92, 246, 0.1);
        color: #7c3aed;
    }
    .dark .panel-icon-purple {
        background: rgba(167, 139, 250, 0.16);
        color: #c084fc;
    }

    .panel-icon-amber {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
    }
    .dark .panel-icon-amber {
        background: rgba(251, 191, 36, 0.16);
        color: #fcd34d;
    }

    .panel-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--color-ink, #0f172a);
        margin: 0;
        line-height: 1.3;
    }

    .panel-subtitle {
        font-size: 12px;
        font-weight: 400;
        color: var(--color-ink-mute, #64748b);
        margin: 2px 0 0 0;
        line-height: 1.35;
    }

    /* Form Fields Styling */
    .form-group-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .field-hint {
        font-size: 11px;
        color: var(--color-ink-mute, #64748b);
        line-height: 1.4;
    }

    .field-required {
        color: var(--color-danger, #ef4444);
        margin-left: 2px;
        font-weight: 700;
    }

    /* Logo Upload Dropzone */
    .logo-uploader-box {
        border: 1.5px dashed var(--color-hairline-strong, #cbd5e1);
        border-radius: var(--rounded-md, 8px);
        padding: 16px;
        background-color: var(--color-canvas-soft, #f8fafc);
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .dark .logo-uploader-box {
        border-color: var(--color-hairline-strong, #5f6368);
        background-color: rgba(255, 255, 255, 0.02);
    }

    .logo-uploader-box:hover {
        border-color: var(--color-primary, #2563eb);
        background-color: var(--color-primary-soft, #eff6ff);
    }

    .dark .logo-uploader-box:hover {
        border-color: #8ab4f8;
        background-color: rgba(138, 180, 248, 0.08);
    }

    .logo-preview-badge {
        width: 76px;
        height: 76px;
        border-radius: 8px;
        border: 1px solid var(--color-hairline, #e2e8f0);
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 6px;
        flex-shrink: 0;
        overflow: hidden;
        position: relative;
    }

    .logo-preview-badge img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    /* Responsive Form Row */
    .form-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .form-row-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1.2fr;
        gap: 12px;
    }

    @media (max-width: 640px) {
        .form-row-2,
        .form-row-3 {
            grid-template-columns: 1fr;
            gap: 12px;
        }
    }

    /* Natural, spacious bottom save bar */
    .bottom-save-panel {
        background: var(--color-canvas, #ffffff);
        border: 1px solid var(--color-hairline, #e2e8f0);
        border-radius: var(--rounded-lg, 12px);
        padding: 14px 20px;
        box-shadow: var(--shadow-1);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .dark .bottom-save-panel {
        background-color: var(--color-canvas, #303134);
        border-color: var(--color-hairline, #3c4043);
    }

    .save-panel-info {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--color-ink-mute, #64748b);
        font-size: 12px;
        line-height: 1.4;
    }

    .save-info-icon {
        width: 16px;
        height: 16px;
        color: var(--color-success, #10b981);
        flex-shrink: 0;
    }

    .save-panel-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-cancel-action {
        padding: 8px 18px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .btn-save-action {
        padding: 8px 22px;
        font-size: 13px;
        font-weight: 700;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
    }

    @media (max-width: 640px) {
        .bottom-save-panel {
            padding: 14px 16px;
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }

        .save-panel-info {
            width: 100%;
            padding: 9px 12px;
            background: var(--color-canvas-soft, #f8fafc);
            border: 1px solid var(--color-hairline, #e2e8f0);
            border-radius: 8px;
            font-size: 11.5px;
            box-sizing: border-box;
            justify-content: flex-start;
        }

        .dark .save-panel-info {
            background: rgba(255, 255, 255, 0.04);
            border-color: var(--color-hairline, #3c4043);
        }

        .save-panel-actions {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .save-panel-actions .btn-cancel-action {
            flex: 1;
            height: 44px;
            font-size: 13.5px;
            border-radius: 10px;
            padding: 0 12px;
        }

        .save-panel-actions .btn-save-action {
            flex: 1.8;
            height: 44px;
            font-size: 13.5px;
            border-radius: 10px;
            padding: 0 16px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.28);
        }
    }

    /* =========================================================================
       LIVE PREVIEW COLUMN STYLES
       ========================================================================= */

    .company-preview-sticky {
        position: sticky;
        top: 14px;
        z-index: 20;
    }

    .preview-card-wrapper {
        background-color: var(--color-canvas, #ffffff);
        border: 1px solid var(--color-hairline, #e2e8f0);
        border-radius: var(--rounded-lg, 12px);
        padding: 10px 12px;
        box-shadow: var(--shadow-1);
        max-height: calc(100vh - 100px);
        max-height: calc(100dvh - 100px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .preview-doc-scroll-wrap {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 6px 6px 8px 6px;
    }

    .preview-doc-scroll-wrap::-webkit-scrollbar {
        width: 4px;
    }
    .preview-doc-scroll-wrap::-webkit-scrollbar-track {
        background: transparent;
    }
    .preview-doc-scroll-wrap::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.4);
        border-radius: 4px;
    }
    .preview-doc-scroll-wrap::-webkit-scrollbar-thumb:hover {
        background: rgba(100, 116, 139, 0.7);
    }

    .preview-callout-bar {
        flex-shrink: 0;
        margin-top: 6px;
        padding: 5px 10px;
        border-radius: 8px;
        background-color: var(--color-canvas-soft);
        border: 1px solid var(--color-hairline);
        font-size: 10px;
        color: var(--color-ink-mute);
        line-height: 1.35;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .dark .preview-card-wrapper {
        background-color: var(--color-canvas, #303134);
        border-color: var(--color-hairline, #3c4043);
    }

    .preview-header-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--color-hairline, #e2e8f0);
    }

    .dark .preview-header-bar {
        border-bottom-color: var(--color-hairline, #3c4043);
    }

    .live-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11.5px;
        font-weight: 700;
        color: var(--color-ink, #0f172a);
    }

    .dark .live-status-pill {
        color: #e8eaed;
    }

    .live-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background-color: #10b981;
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Segmented Control for Document Tabs */
    .preview-tabs-nav {
        display: flex;
        background: var(--color-canvas-soft, #f1f5f9);
        padding: 3px;
        border-radius: 8px;
        border: 1px solid var(--color-hairline, #e2e8f0);
        gap: 3px;
        margin-bottom: 10px;
    }

    .dark .preview-tabs-nav {
        background: #202124;
        border-color: #3c4043;
    }

    .preview-tab-button {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        color: var(--color-ink-mute, #64748b);
        background: transparent;
        border: none;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
    }

    .preview-tab-button:hover {
        color: var(--color-ink, #0f172a);
    }

    .preview-tab-button.is-active {
        background: var(--color-canvas, #ffffff);
        color: var(--color-primary, #2563eb);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        font-weight: 700;
    }

    .dark .preview-tab-button.is-active {
        background: #303134;
        color: #8ab4f8;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    }

    /* Realistic Document Paper Simulator */
    .paper-sheet-container {
        background-color: #ffffff;
        color: #0f172a;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 10px 12px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        font-size: 9px;
        line-height: 1.35;
        position: relative;
    }

    /* Dot Matrix Continuous Form Paper Simulator */
    .dotmatrix-sheet-container {
        background-color: #fffffb;
        color: #111111;
        border: 1px dashed #cbd5e1;
        border-radius: 6px;
        padding: 10px 14px;
        font-family: 'JetBrains Mono', 'Consolas', 'Courier New', monospace;
        font-size: 8.5px;
        line-height: 1.3;
        position: relative;
    }

    .dm-side-perforation {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 8px;
        background-image: radial-gradient(circle, #cbd5e1 1.2px, transparent 1.6px);
        background-size: 8px 8px;
        opacity: 0.65;
    }
    .dm-side-left { left: 2px; }
    .dm-side-right { right: 2px; }

    .doc-kop-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 4px;
    }

    .doc-kop-logo {
        max-height: 28px;
        width: auto;
        object-fit: contain;
        margin-bottom: 2px;
        display: block;
    }

    .doc-kop-brand {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        color: #0f172a;
        letter-spacing: 0.01em;
        line-height: 1.2;
    }

    .doc-kop-tagline {
        font-size: 8.5px;
        font-weight: 600;
        color: #475569;
        line-height: 1.25;
    }

    .doc-kop-details {
        font-size: 8px;
        color: #64748b;
        margin-top: 1px;
        line-height: 1.3;
    }

    .doc-title-badge {
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.03em;
        text-align: right;
        color: #0f172a;
    }

    .doc-table-mini {
        width: 100%;
        border-collapse: collapse;
        margin: 4px 0;
        font-size: 8px;
    }

    .doc-table-mini th {
        background-color: #f1f5f9;
        border-top: 1px solid #cbd5e1;
        border-bottom: 1px solid #cbd5e1;
        padding: 2px 4px;
        font-weight: 700;
        text-align: left;
    }

    .doc-table-mini td {
        padding: 2px 4px;
        border-bottom: 1px solid #f1f5f9;
    }

    .doc-bank-box {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        padding: 4px 6px;
        margin-top: 5px;
        font-size: 8px;
        color: #334155;
        line-height: 1.35;
    }

    .doc-footer-notes {
        margin-top: 4px;
        padding-top: 3px;
        border-top: 1px dashed #cbd5e1;
        font-size: 7.5px;
        color: #64748b;
        font-style: italic;
        line-height: 1.3;
    }

    /* Interactive Preview Sheet Click-to-Zoom Cue */
    .paper-sheet-interactive {
        cursor: pointer;
        position: relative;
        margin: 2px 2px 6px 2px;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }
    .paper-sheet-interactive:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(37, 99, 235, 0.12), 0 2px 6px rgba(0, 0, 0, 0.05);
        border-color: #2563eb;
    }
    .preview-zoom-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(15, 23, 42, 0.78);
        backdrop-filter: blur(4px);
        color: #ffffff;
        font-size: 10px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        opacity: 0.85;
        transition: all 0.15s ease;
        pointer-events: none;
        z-index: 2;
    }
    .paper-sheet-interactive:hover .preview-zoom-badge {
        opacity: 1;
        background: #2563eb;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);
    }

    /* Fullscreen / Centered Zoomed Document Modal */
    .zoomed-paper-sheet {
        background-color: #ffffff;
        color: #0f172a;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        width: 100%;
        max-width: 820px;
        margin: 0 auto;
        padding: 24px 28px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        font-size: 12px;
        line-height: 1.5;
        position: relative;
        box-sizing: border-box;
    }

    .zoomed-dm-sheet {
        background-color: #fffffb;
        color: #111111;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        width: 100%;
        max-width: 840px;
        margin: 0 auto;
        padding: 22px 32px;
        font-family: 'JetBrains Mono', 'Consolas', 'Courier New', monospace;
        font-size: 11.5px;
        line-height: 1.45;
        position: relative;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
        box-sizing: border-box;
    }

    .zoomed-dm-perforation {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 14px;
        background-image: radial-gradient(circle, #cbd5e1 2px, transparent 2.5px);
        background-size: 14px 14px;
        opacity: 0.8;
    }
    .zoomed-dm-left { left: 4px; }
    .zoomed-dm-right { right: 4px; }
</style>

<script>
    // Pure JavaScript component factory for Alpine.js
    // Avoids quote escaping issues inside HTML attributes
    function initCompanySettingsApp() {
        return {
            activeTab: 'standard',
            previewModalOpen: false,
            formData: <?= json_encode([
                'nama' => $cNama,
                'tagline' => $cTagline,
                'alamat' => $cAlamat,
                'telepon' => $cTelepon,
                'email' => $cEmail,
                'website' => $cWebsite,
                'catatan_faktur' => $cCatatanFaktur,
                'nama_bank' => $cNamaBank,
                'nomor_rekening' => $cNomorRekening,
                'atas_nama_bank' => $cAtasNamaBank,
                'logo_url' => $cLogoUrl,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            removeLogo: false,
            isSubmitting: false,

            openPreviewModal(tab) {
                if (tab) {
                    this.activeTab = tab;
                }
                this.previewModalOpen = true;
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            },

            closePreviewModal() {
                this.previewModalOpen = false;
            },

            handleLogoSelect(event) {
                const file = event.target.files[0];
                if (file) {
                    this.removeLogo = false;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.formData.logo_url = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            },

            triggerUpload() {
                this.$refs.logoInput.click();
            },

            doRemoveLogo() {
                this.removeLogo = true;
                this.formData.logo_url = '';
                if (this.$refs.logoInput) {
                    this.$refs.logoInput.value = '';
                }
            }
        };
    }
</script>

<div class="company-page-container" x-data="initCompanySettingsApp()">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER (STANDARD ENTERPRISE APP STYLE)                            -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-blue">
                <i data-lucide="building-2"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color: var(--color-primary);"></span>
                    <span>Pengaturan Sistem &bull; Kop Dokumen</span>
                </div>
                <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Informasi Perusahaan') ?></h1>
                <p class="page-subtitle">Identitas resmi dan kontak yang dicetak otomatis pada faktur, surat jalan, dan nota transaksi</p>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. MAIN 2-COLUMN LAYOUT: FORM INPUT (LEFT) + LIVE PREVIEW (RIGHT)         -->
    <!-- ========================================================================= -->
    <form id="form-company"
          action="<?= Router::url('/settings/company') ?>"
          method="POST"
          enctype="multipart/form-data"
          @submit="isSubmitting = true">
        <?= CSRF::field() ?>
        <input type="hidden" name="hapus_logo" :value="removeLogo ? '1' : '0'">

        <div class="company-layout-grid">

            <!-- ================================================================= -->
            <!-- LEFT COLUMN: STRUCTURED SETTINGS CARDS                            -->
            <!-- ================================================================= -->
            <div class="company-form-stack">

                <!-- CARD 1: IDENTITAS RESMI & LOGO BRAND -->
                <div class="settings-panel-card">
                    <div class="panel-section-header">
                        <div class="panel-icon-circle panel-icon-blue">
                            <i data-lucide="award"></i>
                        </div>
                        <div>
                            <h3 class="panel-title">Identitas Resmi &amp; Brand Usaha</h3>
                            <p class="panel-subtitle">Nama usaha dan logo yang menjadi judul utama kop dokumen resmi</p>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <!-- Nama Resmi Perusahaan -->
                        <div class="form-group-item">
                            <label class="form-label" for="input-nama">
                                Nama Resmi Usaha / Brand Perusahaan <span class="field-required">*</span>
                            </label>
                            <input type="text"
                                   id="input-nama"
                                   name="nama"
                                   class="form-input font-bold"
                                   value="<?= htmlspecialchars($cNama) ?>"
                                   x-model="formData.nama"
                                   placeholder="Contoh: KEREN SNACK INDONESIA"
                                   required
                                   maxlength="150">
                            <span class="field-hint">Ditampilkan dengan ukuran paling menonjol pada kop setiap cetakan dokumen.</span>
                        </div>

                        <!-- Tagline / Slogan -->
                        <div class="form-group-item">
                            <label class="form-label" for="input-tagline">
                                Slogan / Tagline Usaha
                            </label>
                            <input type="text"
                                   id="input-tagline"
                                   name="tagline"
                                   class="form-input"
                                   value="<?= htmlspecialchars($cTagline) ?>"
                                   x-model="formData.tagline"
                                   placeholder="Contoh: Produsen &amp; Distributor Makanan Ringan Berkualitas"
                                   maxlength="255">
                            <span class="field-hint">Teks penjelasan singkat yang muncul tepat di bawah nama perusahaan.</span>
                        </div>

                        <!-- Upload Logo Brand -->
                        <div class="form-group-item">
                            <label class="form-label">Logo Resmi Perusahaan</label>
                            
                            <div class="logo-uploader-box">
                                <!-- Thumbnail Preview -->
                                <div class="logo-preview-badge">
                                    <template x-if="formData.logo_url && !removeLogo">
                                        <img :src="formData.logo_url" alt="Logo Perusahaan">
                                    </template>
                                    <template x-if="!formData.logo_url || removeLogo">
                                        <div style="color: var(--color-ink-mute); text-align: center;">
                                            <i data-lucide="image" style="width: 24px; height: 24px; margin: 0 auto; display: block; opacity: 0.5;"></i>
                                            <span style="font-size: 9px; display: block; margin-top: 2px;">Tanpa Logo</span>
                                        </div>
                                    </template>
                                </div>

                                <!-- Action Buttons & Info -->
                                <div style="flex: 1; min-width: 0;">
                                    <input type="file"
                                           x-ref="logoInput"
                                           name="logo"
                                           accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                           style="display: none;"
                                           @change="handleLogoSelect($event)">

                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <button type="button"
                                                class="btn btn-secondary btn-sm"
                                                @click="triggerUpload()">
                                            <i data-lucide="upload-cloud"></i>
                                            <span x-text="formData.logo_url && !removeLogo ? 'Ganti Logo' : 'Unggah Logo'">Unggah Logo</span>
                                        </button>

                                        <template x-if="formData.logo_url && !removeLogo">
                                            <button type="button"
                                                    class="btn btn-danger btn-sm"
                                                    @click="doRemoveLogo()">
                                                <i data-lucide="trash-2"></i>
                                                <span>Hapus</span>
                                            </button>
                                        </template>
                                    </div>

                                    <div class="field-hint" style="margin-top: 6px;">
                                        Mendukung PNG transparan, JPG, WEBP, atau SVG (Maksimal 2 MB).
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: ALAMAT & KONTAK OPERASIONAL -->
                <div class="settings-panel-card">
                    <div class="panel-section-header">
                        <div class="panel-icon-circle panel-icon-emerald">
                            <i data-lucide="map-pin"></i>
                        </div>
                        <div>
                            <h3 class="panel-title">Kontak &amp; Lokasi Operasional</h3>
                            <p class="panel-subtitle">Alamat kantor/gudang dan nomor saluran komunikasi resmi</p>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <!-- Alamat Kantor / Gudang -->
                        <div class="form-group-item">
                            <label class="form-label" for="input-alamat">Alamat Lengkap Kantor / Gudang</label>
                            <textarea id="input-alamat"
                                      name="alamat"
                                      class="form-input"
                                      style="height: auto; padding-top: 8px; padding-bottom: 8px;"
                                      rows="2"
                                      x-model="formData.alamat"
                                      placeholder="Jl. Raya Industri Keripik No. 88, Bandung, Jawa Barat"
                                      maxlength="500"><?= htmlspecialchars($cAlamat) ?></textarea>
                            <span class="field-hint">Alamat pengirim yang tercantum pada kop faktur dan surat jalan logistik.</span>
                        </div>

                        <!-- 2 Kolom: Telepon & Email -->
                        <div class="form-row-2">
                            <div class="form-group-item">
                                <label class="form-label" for="input-telepon">No. Telepon / WhatsApp</label>
                                <div class="form-input-icon">
                                    <i data-lucide="phone" class="icon-left"></i>
                                    <input type="text"
                                           id="input-telepon"
                                           name="telepon"
                                           class="form-input"
                                           value="<?= htmlspecialchars($cTelepon) ?>"
                                           x-model="formData.telepon"
                                           placeholder="0812-3456-7890"
                                           maxlength="50">
                                </div>
                            </div>

                            <div class="form-group-item">
                                <label class="form-label" for="input-email">Email Resmi Bisnis</label>
                                <div class="form-input-icon">
                                    <i data-lucide="mail" class="icon-left"></i>
                                    <input type="email"
                                           id="input-email"
                                           name="email"
                                           class="form-input"
                                           value="<?= htmlspecialchars($cEmail) ?>"
                                           x-model="formData.email"
                                           placeholder="admin@kerensnack.com"
                                           maxlength="100">
                                </div>
                            </div>
                        </div>

                        <!-- Website -->
                        <div class="form-group-item">
                            <label class="form-label" for="input-website">Alamat Website</label>
                            <div class="form-input-icon">
                                <i data-lucide="globe" class="icon-left"></i>
                                <input type="text"
                                       id="input-website"
                                       name="website"
                                       class="form-input"
                                       value="<?= htmlspecialchars($cWebsite) ?>"
                                       x-model="formData.website"
                                       placeholder="www.kerensnack.com"
                                       maxlength="100">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 3: REKENING BANK PEMBAYARAN -->
                <div class="settings-panel-card">
                    <div class="panel-section-header">
                        <div class="panel-icon-circle panel-icon-purple">
                            <i data-lucide="credit-card"></i>
                        </div>
                        <div>
                            <h3 class="panel-title">Informasi Rekening Bank Penerima</h3>
                            <p class="panel-subtitle">Instruksi transfer pembayaran tempo untuk pelanggan toko dan nota konsinyasi</p>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <div class="form-row-3">
                            <div class="form-group-item">
                                <label class="form-label" for="input-bank">Nama Bank</label>
                                <input type="text"
                                       id="input-bank"
                                       name="nama_bank"
                                       class="form-input"
                                       value="<?= htmlspecialchars($cNamaBank) ?>"
                                       x-model="formData.nama_bank"
                                       placeholder="BCA / Mandiri / BRI"
                                       maxlength="50">
                            </div>

                            <div class="form-group-item">
                                <label class="form-label" for="input-rek">Nomor Rekening</label>
                                <input type="text"
                                       id="input-rek"
                                       name="nomor_rekening"
                                       class="form-input font-mono font-bold"
                                       value="<?= htmlspecialchars($cNomorRekening) ?>"
                                       x-model="formData.nomor_rekening"
                                       placeholder="8820-123-4567"
                                       maxlength="50">
                            </div>

                            <div class="form-group-item">
                                <label class="form-label" for="input-an">Atas Nama (A/N)</label>
                                <input type="text"
                                       id="input-an"
                                       name="atas_nama_bank"
                                       class="form-input font-bold"
                                       value="<?= htmlspecialchars($cAtasNamaBank) ?>"
                                       x-model="formData.atas_nama_bank"
                                       placeholder="KEREN SNACK INDONESIA"
                                       maxlength="100">
                            </div>
                        </div>

                        <div style="padding: 10px 12px; background: var(--color-canvas-soft); border: 1px solid var(--color-hairline); border-radius: var(--rounded-md); display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="info" style="width: 15px; height: 15px; color: var(--color-info); flex-shrink: 0;"></i>
                            <span style="font-size: 11.5px; color: var(--color-ink-secondary); line-height: 1.4;">
                                Nomor rekening ini tercetak otomatis di bagian bawah faktur tempo dan nota konsinyasi toko.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- CARD 4: CATATAN KAKI FAKTUR -->
                <div class="settings-panel-card">
                    <div class="panel-section-header">
                        <div class="panel-icon-circle panel-icon-amber">
                            <i data-lucide="file-text"></i>
                        </div>
                        <div>
                            <h3 class="panel-title">Catatan Kaki &amp; Ketentuan Faktur</h3>
                            <p class="panel-subtitle">Ketentuan retur barang, garansi, atau instruksi pembayaran di footer faktur</p>
                        </div>
                    </div>

                    <div class="form-group-item">
                        <label class="form-label" for="input-catatan">Catatan Syarat &amp; Ketentuan</label>
                        <textarea id="input-catatan"
                                  name="catatan_faktur"
                                  class="form-input"
                                  style="height: auto; padding-top: 8px; padding-bottom: 8px;"
                                  rows="2"
                                  x-model="formData.catatan_faktur"
                                  placeholder="Contoh: Barang yang sudah dibeli tidak dapat ditukar/dikembalikan tanpa persetujuan tertulis."
                                  maxlength="1000"><?= htmlspecialchars($cCatatanFaktur) ?></textarea>
                        <span class="field-hint">Maksimal 1.000 karakter. Ditampilkan pada bagian kaki faktur penjualan.</span>
                    </div>
                </div>

                <!-- NATURAL BOTTOM SAVE BAR -->
                <div class="bottom-save-panel">
                    <div class="save-panel-info">
                        <i data-lucide="shield-check" class="save-info-icon"></i>
                        <span>Otomatis aktif ke seluruh faktur, surat jalan &amp; nota</span>
                    </div>

                    <div class="save-panel-actions">
                        <a href="<?= Router::url('/settings') ?>" class="btn btn-secondary btn-cancel-action">
                            Batal
                        </a>
                        <button type="submit"
                                form="form-company"
                                class="btn btn-primary btn-save btn-save-action"
                                :disabled="isSubmitting">
                            <i data-lucide="save"></i>
                            <span x-show="!isSubmitting">Simpan Perubahan</span>
                            <span x-show="isSubmitting" x-cloak>Menyimpan...</span>
                        </button>
                    </div>
                </div>

            </div>

            <!-- ================================================================= -->
            <!-- RIGHT COLUMN: REALTIME DOCUMENT PREVIEW PANEL                     -->
            <!-- ================================================================= -->
            <div class="company-preview-sticky">
                <div class="preview-card-wrapper">
                    
                    <!-- Top Bar: Live Status -->
                    <div class="preview-header-bar">
                        <div class="live-status-pill">
                            <span class="live-dot"></span>
                            <span>Pratinjau Langsung</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button type="button"
                                    @click="openPreviewModal()"
                                    class="btn btn-secondary btn-xs"
                                    style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; padding: 4px 9px; cursor: pointer;"
                                    title="Perbesar Tampilan Pratinjau Dokumen">
                                <i data-lucide="maximize-2" style="width: 12px; height: 12px;"></i>
                                <span>Perbesar</span>
                            </button>
                            <span class="badge badge-mono">Real-Time</span>
                        </div>
                    </div>

                    <!-- Segmented Control Document Tabs (2 Format Media Cetak Utama) -->
                    <div class="preview-tabs-nav">
                        <button type="button"
                                @click="activeTab = 'standard'"
                                class="preview-tab-button"
                                :class="{ 'is-active': activeTab === 'standard' }">
                            <i data-lucide="file-text" style="width: 14px; height: 14px;"></i>
                            <span>Kertas A4 Biasa</span>
                        </button>
                        <button type="button"
                                @click="activeTab = 'dotmatrix'"
                                class="preview-tab-button"
                                :class="{ 'is-active': activeTab === 'dotmatrix' }">
                            <i data-lucide="printer" style="width: 14px; height: 14px;"></i>
                            <span>Printer Dot Matrix</span>
                        </button>
                    </div>

                    <!-- Document Sheets Scroll Container (Fit without clipping viewport) -->
                    <div class="preview-doc-scroll-wrap custom-scrollbar">

                        <!-- PREVIEW 1: FORMAT KERTAS A4 BIASA (FAKTUR PENJUALAN & DOKUMEN KANTOR) -->
                        <div x-show="activeTab === 'standard'"
                         class="paper-sheet-container paper-sheet-interactive"
                         @click="openPreviewModal('standard')"
                         title="Klik untuk memperbesar pratinjau Kertas A4 Biasa">
                        <div class="preview-zoom-badge">
                            <i data-lucide="zoom-in" style="width: 10px; height: 10px;"></i>
                            <span>Klik untuk Perbesar</span>
                        </div>
                        <div style="font-size: 8px; font-weight: 800; color: #e11d48; margin-bottom: 5px; display: flex; justify-content: space-between; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 3px; padding-right: 110px; letter-spacing: 0.02em;">
                            <span>SIMULASI CETAK A4 (LASER / INKJET)</span>
                            <span style="color: #94a3b8; font-weight: 600;">Faktur Penjualan</span>
                        </div>

                        <!-- Kop Dokumen Resmi -->
                        <table class="doc-kop-table">
                            <tr>
                                <td style="vertical-align: top; width: 62%;">
                                    <template x-if="formData.logo_url && !removeLogo">
                                        <img :src="formData.logo_url" alt="Logo" class="doc-kop-logo">
                                    </template>
                                    <div class="doc-kop-brand" style="color: #e11d48;" x-text="formData.nama || 'KEREN SNACK INDONESIA'"><?= htmlspecialchars($cNama) ?></div>
                                    <div class="doc-kop-tagline" x-text="formData.tagline || 'Produsen Aneka Snack Berkualitas'"><?= htmlspecialchars($cTagline) ?></div>
                                    <div class="doc-kop-details">
                                        <span x-text="formData.alamat || 'Alamat Kantor/Gudang Perusahaan'"><?= htmlspecialchars($cAlamat) ?></span><br>
                                        <span>Telp/WA: <strong x-text="formData.telepon || '-'"><?= htmlspecialchars($cTelepon) ?></strong></span>
                                        <span x-show="formData.email"> &bull; Email: <strong x-text="formData.email"><?= htmlspecialchars($cEmail) ?></strong></span>
                                        <span x-show="formData.website"> &bull; Web: <strong x-text="formData.website"><?= htmlspecialchars($cWebsite) ?></strong></span>
                                    </div>
                                </td>
                                <td style="vertical-align: top; text-align: right; width: 38%;">
                                    <div class="doc-title-badge" style="color: #0f172a; font-size: 10.5px;">FAKTUR PENJUALAN</div>
                                    <div style="font-family: var(--font-mono); font-size: 8.5px; font-weight: 700; color: #059669; margin-top: 1px;">INV-2026/09/0128</div>
                                    <div style="font-size: 7.5px; color: #64748b; margin-top: 1px;">Tgl: <?= date('d/m/Y') ?></div>
                                    <div style="font-size: 7.5px; color: #059669; font-weight: 800; margin-top: 1px;">[ LUNAS ]</div>
                                </td>
                            </tr>
                        </table>

                        <!-- Pelanggan & Info Transaksi -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 4px 6px; margin: 4px 0; display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 6px; font-size: 8px;">
                            <div>
                                <span style="color: #64748b; font-size: 7.5px; text-transform: uppercase; font-weight: 700;">Kepada Toko Pelanggan:</span>
                                <div style="font-weight: 700; color: #0f172a;">TOKO MAJU JAYA CEMILAN</div>
                                <div style="color: #64748b; font-size: 7.5px;">Jl. Kopo Permai No. 12 &bull; WA: 0812-9988-7766</div>
                            </div>
                            <div style="text-align: right;">
                                <span style="color: #64748b; font-size: 7.5px; text-transform: uppercase; font-weight: 700;">Sales &amp; Pengiriman:</span>
                                <div style="font-weight: 600; color: #0f172a;">Budi Santoso (Sales)</div>
                                <div style="color: #059669; font-size: 7.5px; font-weight: 700;">CASH / REGULER</div>
                            </div>
                        </div>

                        <!-- Tabel Item Mini -->
                        <table class="doc-table-mini">
                            <thead>
                                <tr>
                                    <th>Deskripsi Produk</th>
                                    <th style="text-align: center; width: 42px;">Qty</th>
                                    <th style="text-align: right; width: 70px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Keripik Singkong Pedas 250g</td>
                                    <td style="text-align: center; font-family: var(--font-mono);">20 Pcs</td>
                                    <td style="text-align: right; font-family: var(--font-mono); font-weight: 700;">Rp 300.000</td>
                                </tr>
                                <tr>
                                    <td>Makaroni Balado Gurih 150g</td>
                                    <td style="text-align: center; font-family: var(--font-mono);">15 Pcs</td>
                                    <td style="text-align: right; font-family: var(--font-mono); font-weight: 700;">Rp 180.000</td>
                                </tr>
                                <tr style="border-top: 1px solid #cbd5e1; font-weight: 800;">
                                    <td colspan="2" style="text-align: right; color: #0f172a;">TOTAL TAGIHAN:</td>
                                    <td style="text-align: right; font-family: var(--font-mono); color: #e11d48; font-size: 8.5px;">Rp 480.000</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Bank Pembayaran Box -->
                        <div class="doc-bank-box" x-show="formData.nomor_rekening">
                            <strong style="color: #0f172a;">Informasi Transfer Bank:</strong><br>
                            Bank <span x-text="formData.nama_bank || 'BCA'"><?= htmlspecialchars($cNamaBank) ?></span> &bull; 
                            No. Rek. <strong style="font-family: var(--font-mono); color: #0284c7;" x-text="formData.nomor_rekening || '-'"><?= htmlspecialchars($cNomorRekening) ?></strong><br>
                            a.n <span x-text="formData.atas_nama_bank || formData.nama"><?= htmlspecialchars($cAtasNamaBank ?: $cNama) ?></span>
                        </div>

                        <!-- Catatan Kaki -->
                        <div class="doc-footer-notes" x-text="formData.catatan_faktur || 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan tanpa persetujuan.'">
                            <?= htmlspecialchars($cCatatanFaktur) ?>
                        </div>

                        <!-- Tanda Tangan 3 Kolom Ringkas -->
                        <div style="display: flex; justify-content: space-between; text-align: center; margin-top: 6px; padding-top: 4px; border-top: 1px solid #f1f5f9; font-size: 7px; color: #475569;">
                            <div>Tanda Terima Pelanggan,<br><br>( ................ )</div>
                            <div>Petugas Pengirim / Sales,<br><br>( Budi S. )</div>
                            <div>Hormat Kami,<br><br>( Kasir / Admin )</div>
                        </div>
                    </div>

                    <!-- PREVIEW 2: FORMAT KERTAS PRINTER DOT MATRIX (SURAT JALAN & CONTINUOUS FORM) -->
                    <div x-show="activeTab === 'dotmatrix'"
                         x-cloak
                         class="dotmatrix-sheet-container paper-sheet-interactive"
                         @click="openPreviewModal('dotmatrix')"
                         title="Klik untuk memperbesar pratinjau Printer Dot Matrix">
                        <div class="dm-side-perforation dm-side-left"></div>
                        <div class="dm-side-perforation dm-side-right"></div>
                        <div class="preview-zoom-badge">
                            <i data-lucide="zoom-in" style="width: 10px; height: 10px;"></i>
                            <span>Klik untuk Perbesar</span>
                        </div>

                        <div style="font-size: 8px; font-weight: 700; color: #b45309; margin-bottom: 4px; border-bottom: 1px dashed #d97706; padding-bottom: 2px; display: flex; justify-content: space-between; padding-right: 110px;">
                            <span>CONTINUOUS FORM 9.5" × 11"</span>
                            <span>EPSON LX-310 / LQ-310</span>
                        </div>

                        <!-- Kop Dot Matrix Monokrom -->
                        <div style="border-bottom: 2px solid #000000; padding-bottom: 3px; margin-bottom: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="width: 58%;">
                                    <div style="font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.03em;" x-text="formData.nama || 'KEREN SNACK INDONESIA'"><?= htmlspecialchars($cNama) ?></div>
                                    <div style="font-size: 8px; color: #222;" x-text="formData.tagline || 'Produsen &amp; Distributor Snack'"><?= htmlspecialchars($cTagline) ?></div>
                                    <div style="font-size: 7px; color: #444; line-height: 1.25; margin-top: 1px;">
                                        <span x-text="formData.alamat || 'Alamat Kantor/Gudang'"><?= htmlspecialchars($cAlamat) ?></span><br>
                                        <span>Telp/WA: <span x-text="formData.telepon || '-'"><?= htmlspecialchars($cTelepon) ?></span></span>
                                        <span x-show="formData.email"> &bull; Email: <span x-text="formData.email"><?= htmlspecialchars($cEmail) ?></span></span>
                                    </div>
                                </div>
                                <div style="text-align: right; width: 42%;">
                                    <div style="font-size: 9.5px; font-weight: bold; letter-spacing: 0.02em;">SURAT JALAN PENGIRIMAN</div>
                                    <div style="font-size: 8px; font-weight: bold; color: #111;">No: SJ-2026/09/0088</div>
                                    <div style="font-size: 7px; color: #444;">Tgl Kirim: <?= date('d/m/Y') ?></div>
                                    <div style="font-size: 7px; font-weight: bold;">[ SIAP KIRIM ]</div>
                                </div>
                            </div>
                        </div>

                        <!-- Tujuan & Armada Logistik (2 Kolom) -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; border-bottom: 1px dashed #666; padding-bottom: 3px; margin-bottom: 3px; font-size: 7.5px; line-height: 1.25;">
                            <div>
                                <span style="font-weight: bold;">TUJUAN (TOKO):</span><br>
                                <strong>TOKO MAJU JAYA CEMILAN</strong><br>
                                <span>Jl. Kopo Permai No. 12 (WA: 0812-9988-7766)</span>
                            </div>
                            <div style="border-left: 1px dashed #999; padding-left: 4px;">
                                <span style="font-weight: bold;">ARMADA LOGISTIK:</span><br>
                                <span>Driver: <strong>Agus H.</strong> (D 8820 KS)</span><br>
                                <span>Rute: Bandung Timur &bull; Ref: INV-0128</span>
                            </div>
                        </div>

                        <!-- Tabel Muatan Barang 80-Kolom Mini -->
                        <table class="doc-table-mini" style="font-family: inherit; font-size: 7.5px; border-bottom: 1px dashed #666; margin: 3px 0;">
                            <thead>
                                <tr style="background: transparent; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                                    <th style="padding: 1.5px 2px; width: 15px; text-align: center;">NO</th>
                                    <th style="padding: 1.5px 2px; text-align: left;">NAMA BARANG / ITEM</th>
                                    <th style="padding: 1.5px 2px; width: 45px; text-align: center;">QTY</th>
                                    <th style="padding: 1.5px 2px; width: 35px; text-align: center;">FISIK</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="text-align: center;">1</td>
                                    <td><strong>Keripik Singkong Pedas 250g</strong></td>
                                    <td style="text-align: center; font-weight: bold;">20 Bks</td>
                                    <td style="text-align: center;">[ &nbsp; ]</td>
                                </tr>
                                <tr>
                                    <td style="text-align: center;">2</td>
                                    <td><strong>Makaroni Balado Gurih 150g</strong></td>
                                    <td style="text-align: center; font-weight: bold;">15 Bks</td>
                                    <td style="text-align: center;">[ &nbsp; ]</td>
                                </tr>
                                <tr style="border-top: 1px solid #000; font-weight: bold;">
                                    <td colspan="2" style="text-align: right;">TOTAL KUANTITAS:</td>
                                    <td style="text-align: center;">35 Bks</td>
                                    <td style="text-align: center;">(2 SKU)</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Info Rekening & Catatan -->
                        <div style="font-size: 7px; color: #333; line-height: 1.3;" x-show="formData.nomor_rekening">
                            Bank: <span x-text="formData.nama_bank || 'BCA'"><?= htmlspecialchars($cNamaBank) ?></span> &bull; 
                            A/C: <strong x-text="formData.nomor_rekening || '-'"><?= htmlspecialchars($cNomorRekening) ?></strong> 
                            a.n <span x-text="formData.atas_nama_bank || formData.nama"><?= htmlspecialchars($cAtasNamaBank ?: $cNama) ?></span>
                        </div>
                        <div style="font-size: 7px; color: #555; font-style: italic; margin-top: 2px;" x-text="formData.catatan_faktur || 'Barang telah diperiksa lengkap & kondisi baik saat muat.'">
                            <?= htmlspecialchars($cCatatanFaktur) ?>
                        </div>

                        <!-- Tanda Tangan 3 Pihak Dot Matrix -->
                        <div style="display: flex; justify-content: space-between; text-align: center; margin-top: 5px; font-size: 7px; border-top: 1px dashed #666; padding-top: 4px;">
                            <div>
                                Tanda Terima Toko,<br><br>
                                ( ................ )
                            </div>
                            <div>
                                Petugas Pengantar,<br><br>
                                ( Agus H. )
                            </div>
                            <div>
                                Hormat Kami,<br><br>
                                ( Petugas )
                            </div>
                        </div>

                        <!-- Footer Copy Rangkap NCR Continuous Form -->
                        <div style="margin-top: 5px; padding-top: 3px; border-top: 1px solid #000; font-size: 6.5px; color: #444; display: flex; justify-content: space-between; flex-wrap: wrap;">
                            <span>[ ] Lembar 1 (Putih): Gudang</span>
                            <span>[ ] Lembar 2 (Merah): Toko Mitra</span>
                            <span>[ ] Lembar 3 (Kuning): Driver</span>
                        </div>
                    </div>
                    </div>

                    <!-- Informational Callout (Compact Sleek Bar) -->
                    <div class="preview-callout-bar">
                        <i data-lucide="shield-check" style="width: 14px; height: 14px; color: var(--color-success); flex-shrink: 0;"></i>
                        <div>
                            <strong>Integrasi Terpusat:</strong> Otomatis aktif ke seluruh Faktur A4, Surat Jalan Dot Matrix, POS &amp; Nota.
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>

    <!-- ========================================================================= -->
    <!-- 3. INTERACTIVE FULL-SCREEN ZOOM MODAL POP-UP                              -->
    <!-- Center modal with backdrop blur & crisp 1:1 document rendering            -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="previewModalOpen"
             x-cloak
             class="modal-backdrop"
             @click.self="closePreviewModal()"
             @keydown.escape.window="closePreviewModal()">
            <div class="modal-box modal-box-lg"
                 style="max-width: 920px; width: 95%; max-height: 90vh; max-height: 90dvh; padding: 0; display: flex; flex-direction: column; overflow: hidden; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
                
                <!-- Modal Top Header -->
                <div style="padding: 16px 22px; background: var(--color-canvas); border-bottom: 1px solid var(--color-hairline); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-shrink: 0;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="panel-icon-circle panel-icon-blue" style="width: 38px; height: 38px;">
                            <i data-lucide="eye" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: var(--color-ink);">Pratinjau Dokumen Cetak Penuh</h3>
                            <p style="font-size: 12px; color: var(--color-ink-mute); margin: 2px 0 0 0;">Detail kop resmi, alamat web, kontak, dan tata letak dokumen cetak</p>
                        </div>
                    </div>

                    <!-- Modal Controls (Tab Switcher + Close Button) -->
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="preview-tabs-nav" style="margin-bottom: 0; padding: 3px;">
                            <button type="button"
                                    @click="activeTab = 'standard'"
                                    class="preview-tab-button"
                                    :class="{ 'is-active': activeTab === 'standard' }"
                                    style="padding: 6px 14px; font-size: 11.5px;">
                                <i data-lucide="file-text" style="width: 13px; height: 13px;"></i>
                                <span>Kertas A4 Biasa</span>
                            </button>
                            <button type="button"
                                    @click="activeTab = 'dotmatrix'"
                                    class="preview-tab-button"
                                    :class="{ 'is-active': activeTab === 'dotmatrix' }"
                                    style="padding: 6px 14px; font-size: 11.5px;">
                                <i data-lucide="printer" style="width: 13px; height: 13px;"></i>
                                <span>Printer Dot Matrix</span>
                            </button>
                        </div>

                        <button type="button"
                                @click="closePreviewModal()"
                                class="btn btn-ghost btn-sm"
                                style="padding: 6px; border-radius: 8px; color: var(--color-ink-mute);"
                                title="Tutup Pratinjau (Esc)">
                            <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body: High Resolution Document Preview -->
                <div style="padding: 24px 20px; overflow-y: auto; overflow-x: auto; background: var(--color-canvas-soft, #f8fafc); flex: 1; min-height: 0; display: block;">
                    
                    <!-- ZOOMED PREVIEW 1: FORMAT KERTAS A4 BIASA (FAKTUR PENJUALAN & DOKUMEN RESMI) -->
                    <div x-show="activeTab === 'standard'" class="zoomed-paper-sheet">
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 18px; border-bottom: 2px solid #0f172a; padding-bottom: 14px;">
                            <tr>
                                <td style="vertical-align: top; width: 62%;">
                                    <template x-if="formData.logo_url && !removeLogo">
                                        <img :src="formData.logo_url" alt="Logo" style="max-height: 52px; width: auto; object-fit: contain; margin-bottom: 8px; display: block;">
                                    </template>
                                    <div style="font-size: 20px; font-weight: 900; text-transform: uppercase; color: #e11d48; letter-spacing: -0.02em;" x-text="formData.nama || 'KEREN SNACK INDONESIA'"><?= htmlspecialchars($cNama) ?></div>
                                    <div style="font-size: 12px; font-weight: 600; color: #475569; margin-top: 2px;" x-text="formData.tagline || 'Produsen & Distributor Aneka Snack Berkualitas'"><?= htmlspecialchars($cTagline) ?></div>
                                    <div style="font-size: 11.5px; color: #64748b; margin-top: 6px; line-height: 1.45;">
                                        <span x-text="formData.alamat || 'Alamat Kantor/Gudang Perusahaan'"><?= htmlspecialchars($cAlamat) ?></span><br>
                                        <span>Telp/WhatsApp: <strong x-text="formData.telepon || '-'"><?= htmlspecialchars($cTelepon) ?></strong></span>
                                        <span x-show="formData.email"> &bull; Email: <strong x-text="formData.email"><?= htmlspecialchars($cEmail) ?></strong></span>
                                        <span x-show="formData.website"> &bull; Website: <strong x-text="formData.website"><?= htmlspecialchars($cWebsite) ?></strong></span>
                                    </div>
                                </td>
                                <td style="vertical-align: top; text-align: right; width: 38%;">
                                    <div style="font-size: 18px; font-weight: 900; letter-spacing: 0.04em; color: #0f172a;">FAKTUR PENJUALAN</div>
                                    <div style="font-family: var(--font-mono, monospace); font-size: 14px; font-weight: 800; color: #059669; margin-top: 4px;">INV-2026/09/0128</div>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Tanggal: <strong><?= date('d/m/Y') ?></strong></div>
                                    <div style="font-size: 12px; color: #059669; font-weight: 800; margin-top: 4px;">Status Pembayaran: [ LUNAS ]</div>
                                </td>
                            </tr>
                        </table>

                        <!-- Customer Info & Sales Box (2 Kolom) -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px; display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 14px;">
                            <div>
                                <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Ditujukan Kepada Toko Pelanggan:</div>
                                <div style="font-weight: 800; font-size: 14px; color: #0f172a; margin-top: 2px;">TOKO MAJU JAYA CEMILAN</div>
                                <div style="font-size: 11.5px; color: #475569; margin-top: 2px;">Kode: <strong>CUST-0082</strong> &bull; PIC: Ibu Heni</div>
                                <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">Jl. Kopo Permai No. 12, Bandung &bull; Telp/WA: 0812-9988-7766</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Rincian Penjualan &amp; Pembayaran:</div>
                                <div style="font-weight: 700; font-size: 13px; color: #0f172a; margin-top: 2px;">Budi Santoso (Sales Driver)</div>
                                <div style="font-size: 11.5px; color: #059669; font-weight: 700; margin-top: 2px;">Tipe Bayar: TUNAI / LUNAS</div>
                                <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">Kasir &amp; Verifikasi: Admin ERP</div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 12px;">
                            <thead>
                                <tr style="background: #f1f5f9; border-top: 1.5px solid #cbd5e1; border-bottom: 1.5px solid #cbd5e1;">
                                    <th style="padding: 8px 10px; text-align: left; width: 35px;">No</th>
                                    <th style="padding: 8px 10px; text-align: left; width: 95px;">Kode SKU</th>
                                    <th style="padding: 8px 10px; text-align: left;">Nama Produk / Kemasan</th>
                                    <th style="padding: 8px 10px; text-align: center; width: 70px;">Qty</th>
                                    <th style="padding: 8px 10px; text-align: right; width: 110px;">Harga Satuan</th>
                                    <th style="padding: 8px 10px; text-align: right; width: 120px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 8px 10px;">1</td>
                                    <td style="padding: 8px 10px; font-family: monospace; color: #475569;">SKU-KS-01</td>
                                    <td style="padding: 8px 10px; font-weight: 600;">Keripik Singkong Pedas Gurih 250g</td>
                                    <td style="padding: 8px 10px; text-align: center; font-family: monospace;">20 Pcs</td>
                                    <td style="padding: 8px 10px; text-align: right; font-family: monospace;">Rp 15.000</td>
                                    <td style="padding: 8px 10px; text-align: right; font-family: monospace; font-weight: 700;">Rp 300.000</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 8px 10px;">2</td>
                                    <td style="padding: 8px 10px; font-family: monospace; color: #475569;">SKU-MB-02</td>
                                    <td style="padding: 8px 10px; font-weight: 600;">Makaroni Renyah Balado Pedas Daun Jeruk 150g</td>
                                    <td style="padding: 8px 10px; text-align: center; font-family: monospace;">15 Pcs</td>
                                    <td style="padding: 8px 10px; text-align: right; font-family: monospace;">Rp 12.000</td>
                                    <td style="padding: 8px 10px; text-align: right; font-family: monospace; font-weight: 700;">Rp 180.000</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr style="border-top: 1.5px solid #cbd5e1; font-weight: 800; font-size: 13px;">
                                    <td colspan="5" style="padding: 10px; text-align: right; color: #0f172a;">TOTAL TAGIHAN PEMBAYARAN:</td>
                                    <td style="padding: 10px; text-align: right; font-family: monospace; color: #e11d48; font-size: 14px;">Rp 480.000</td>
                                </tr>
                            </tfoot>
                        </table>

                        <!-- Bank Details & Notes -->
                        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 16px; margin-top: 10px; font-size: 11.5px;">
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;" x-show="formData.nomor_rekening">
                                <div style="font-weight: 700; color: #1e3a8a; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="credit-card" style="width: 14px; height: 14px;"></i>
                                    <span>Informasi Pembayaran Transfer Bank:</span>
                                </div>
                                <div style="color: #334155; line-height: 1.5;">
                                    Bank: <strong x-text="formData.nama_bank || 'BCA'"><?= htmlspecialchars($cNamaBank) ?></strong><br>
                                    No. Rekening: <strong style="font-family: monospace; font-size: 13px; color: #0284c7;" x-text="formData.nomor_rekening || '-'"><?= htmlspecialchars($cNomorRekening) ?></strong><br>
                                    Atas Nama: <strong x-text="formData.atas_nama_bank || formData.nama"><?= htmlspecialchars($cAtasNamaBank ?: $cNama) ?></strong>
                                </div>
                            </div>

                            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px 14px;">
                                <div style="font-weight: 700; color: #475569; margin-bottom: 4px;">Catatan Syarat &amp; Ketentuan:</div>
                                <div style="color: #64748b; font-size: 11px; font-style: italic; line-height: 1.45;" x-text="formData.catatan_faktur || 'Barang yang sudah diterima dalam kondisi baik tidak dapat dikembalikan tanpa persetujuan tertulis.'">
                                    <?= htmlspecialchars($cCatatanFaktur) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Signatures -->
                        <div style="display: flex; justify-content: space-between; text-align: center; margin-top: 28px; padding-top: 12px; font-size: 11.5px; color: #334155;">
                            <div style="width: 180px;">
                                Tanda Terima Pelanggan,<br><br><br><br>
                                <div style="border-bottom: 1px solid #94a3b8; width: 140px; margin: 0 auto;"></div>
                                <span style="font-size: 10.5px; color: #64748b; margin-top: 4px; display: block;">Cap &amp; Tanda Tangan</span>
                            </div>
                            <div style="width: 180px;">
                                Petugas Pengirim / Sales,<br><br><br><br>
                                <div style="border-bottom: 1px solid #94a3b8; width: 140px; margin: 0 auto; font-weight: 700;">Budi Santoso</div>
                                <span style="font-size: 10.5px; color: #64748b; margin-top: 4px; display: block;">Sales Driver Distribusi</span>
                            </div>
                            <div style="width: 180px;">
                                Hormat Kami,<br><br><br><br>
                                <div style="border-bottom: 1px solid #94a3b8; width: 140px; margin: 0 auto; font-weight: 700;" x-text="formData.nama || 'KEREN SNACK'"></div>
                                <span style="font-size: 10.5px; color: #64748b; margin-top: 4px; display: block;">Bagian Keuangan / Admin</span>
                            </div>
                        </div>
                    </div>

                    <!-- ZOOMED PREVIEW 2: FORMAT KERTAS PRINTER DOT MATRIX (SURAT JALAN & CONTINUOUS FORM) -->
                    <div x-show="activeTab === 'dotmatrix'" x-cloak class="zoomed-dm-sheet">
                        <div class="zoomed-dm-perforation zoomed-dm-left"></div>
                        <div class="zoomed-dm-perforation zoomed-dm-right"></div>

                        <div style="font-size: 10px; font-weight: 700; color: #b45309; margin-bottom: 8px; border-bottom: 1px dashed #d97706; padding-bottom: 4px; display: flex; justify-content: space-between;">
                            <span>SIMULASI CETAK CONTINUOUS FORM 9.5" × 11" (EPSON LX-310 / LQ-310)</span>
                            <span>KERTAS RANGKAP NCR 3-PLY</span>
                        </div>

                        <!-- Kop Dot Matrix Monokrom -->
                        <div style="border-bottom: 2px solid #000000; padding-bottom: 6px; margin-bottom: 6px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="width: 56%;">
                                    <div style="font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.02em;" x-text="formData.nama || 'KEREN SNACK INDONESIA'"><?= htmlspecialchars($cNama) ?></div>
                                    <div style="font-size: 11.5px; font-weight: bold;" x-text="formData.tagline || 'Produsen &amp; Distributor Snack'"><?= htmlspecialchars($cTagline) ?></div>
                                    <div style="font-size: 11px; color: #333333; margin-top: 2px; line-height: 1.35;">
                                        <span x-text="formData.alamat || 'Alamat Kantor/Gudang'"><?= htmlspecialchars($cAlamat) ?></span><br>
                                        <span>Telp/WA: <span x-text="formData.telepon || '-'"><?= htmlspecialchars($cTelepon) ?></span></span>
                                        <span x-show="formData.email"> &bull; Email: <span x-text="formData.email"><?= htmlspecialchars($cEmail) ?></span></span>
                                        <span x-show="formData.website"> &bull; Web: <span x-text="formData.website"><?= htmlspecialchars($cWebsite) ?></span></span>
                                    </div>
                                </div>
                                <div style="text-align: right; width: 44%;">
                                    <div style="font-size: 15px; font-weight: bold; letter-spacing: 0.03em;">SURAT JALAN PENGIRIMAN</div>
                                    <div style="font-size: 10.5px; font-weight: bold; color: #333; letter-spacing: 0.05em; margin-bottom: 3px;">BUKTI SERAH TERIMA PENGIRIMAN</div>
                                    <table style="width: 100%; font-size: 11px; margin-top: 2px; line-height: 1.35;">
                                        <tr>
                                            <td style="text-align: right; width: 60%; font-weight: bold;">No. Surat Jalan :</td>
                                            <td style="text-align: right; width: 40%; font-weight: bold;">SJ-2026/09/0088</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: right;">Tanggal Kirim :</td>
                                            <td style="text-align: right;"><?= date('d/m/Y') ?></td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: right;">No. Faktur / PO :</td>
                                            <td style="text-align: right;">INV-2026/09/0128</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: right; font-weight: bold;">Status Kirim :</td>
                                            <td style="text-align: right; font-weight: bold;">[ SIAP KIRIM ]</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Double Divider -->
                        <div style="border-top: 2px solid #000; border-bottom: 0.5px solid #000; height: 2px; margin: 4px 0 8px 0;"></div>

                        <!-- 2 Kolom: TUJUAN PENGIRIMAN vs DATA ARMADA LOGISTIK -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 8px; font-size: 11.5px; line-height: 1.4;">
                            <div style="border-right: 1px dashed #000; padding-right: 10px;">
                                <div style="font-weight: bold; text-decoration: underline; margin-bottom: 3px;">TUJUAN PENGIRIMAN (TOKO PELANGGAN):</div>
                                <div>Nama Toko : <strong>TOKO MAJU JAYA CEMILAN</strong> (CUST-0082)</div>
                                <div>Pemilik / PIC : Ibu Heni</div>
                                <div>Alamat Lengkap : Jl. Kopo Permai No. 12, Bandung</div>
                                <div>No. Telp / WA : 0812-9988-7766</div>
                            </div>
                            <div style="padding-left: 4px;">
                                <div style="font-weight: bold; text-decoration: underline; margin-bottom: 3px;">DATA ARMADA LOGISTIK:</div>
                                <div>Driver / Kurir : <strong>Agus Hermawan</strong></div>
                                <div>No. Polisi : D 8820 KS</div>
                                <div>Rute / Wilayah : Bandung Timur (R-03)</div>
                                <div>Skema Kirim : Reguler</div>
                            </div>
                        </div>

                        <!-- Single Dashed Divider -->
                        <div style="border-top: 1px dashed #000; margin: 6px 0;"></div>

                        <!-- Tabel Daftar Item Muatan (80-Kolom Dot Matrix) -->
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 11.5px;">
                            <thead>
                                <tr style="border-top: 1px solid #000; border-bottom: 1px solid #000;">
                                    <th style="padding: 4px 6px; text-align: center; width: 30px;">NO</th>
                                    <th style="padding: 4px 6px; text-align: left; width: 95px;">KODE SKU</th>
                                    <th style="padding: 4px 6px; text-align: left;">NAMA BARANG / ITEM PRODUK</th>
                                    <th style="padding: 4px 6px; text-align: left; width: 110px;">VARIAN</th>
                                    <th style="padding: 4px 6px; text-align: right; width: 65px;">QTY</th>
                                    <th style="padding: 4px 6px; text-align: center; width: 75px;">SATUAN</th>
                                    <th style="padding: 4px 6px; text-align: center; width: 75px;">CEK FISIK</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 4px 6px; text-align: center;">1</td>
                                    <td style="padding: 4px 6px;">SKU-KS-01</td>
                                    <td style="padding: 4px 6px;"><strong>Keripik Singkong Pedas 250g</strong></td>
                                    <td style="padding: 4px 6px;">Pedas Gurih</td>
                                    <td style="padding: 4px 6px; text-align: right; font-weight: bold;">20</td>
                                    <td style="padding: 4px 6px; text-align: center;">Bungkus</td>
                                    <td style="padding: 4px 6px; text-align: center;">[ &nbsp; ]</td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 6px; text-align: center;">2</td>
                                    <td style="padding: 4px 6px;">SKU-MB-02</td>
                                    <td style="padding: 4px 6px;"><strong>Makaroni Balado Gurih 150g</strong></td>
                                    <td style="padding: 4px 6px;">Daun Jeruk</td>
                                    <td style="padding: 4px 6px; text-align: right; font-weight: bold;">15</td>
                                    <td style="padding: 4px 6px; text-align: center;">Bungkus</td>
                                    <td style="padding: 4px 6px; text-align: center;">[ &nbsp; ]</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr style="border-top: 1px solid #000; border-bottom: 1px solid #000; font-weight: bold;">
                                    <td colspan="4" style="padding: 5px 6px; text-align: right;">TOTAL KUANTITAS MUATAN:</td>
                                    <td style="padding: 5px 6px; text-align: right;">35</td>
                                    <td style="padding: 5px 6px; text-align: center;">Bungkus</td>
                                    <td style="padding: 5px 6px; text-align: center;">(2 SKU)</td>
                                </tr>
                            </tfoot>
                        </table>

                        <!-- Terbilang & Catatan Kaki Dokumen -->
                        <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 12px; line-height: 1.45;">
                            <div style="width: 65%;">
                                <strong>Terbilang:</strong> <em># Tiga Puluh Lima Bungkus #</em><br>
                                <strong>Catatan:</strong> <span x-text="formData.catatan_faktur || 'Barang telah diperiksa lengkap & kondisi baik saat muat.'"><?= htmlspecialchars($cCatatanFaktur) ?></span><br>
                                <span style="font-size: 10px; color: #555;">* Mohon periksa fisik kemasan &amp; segel bersama driver saat serah terima di toko mitra.</span>
                            </div>
                            <div style="width: 35%; text-align: right; font-size: 10.5px;">
                                <div x-show="formData.nomor_rekening">
                                    Rek: Bank <span x-text="formData.nama_bank || 'BCA'"><?= htmlspecialchars($cNamaBank) ?></span> <strong x-text="formData.nomor_rekening || '-'"><?= htmlspecialchars($cNomorRekening) ?></strong><br>
                                    a.n <span x-text="formData.atas_nama_bank || formData.nama"><?= htmlspecialchars($cAtasNamaBank ?: $cNama) ?></span>
                                </div>
                                <div style="color: #666; margin-top: 2px;">Dokumen Sistem ERP Cetak: <?= date('d/m/Y H:i:s') ?></div>
                            </div>
                        </div>

                        <!-- Tanda Tangan 3 Pihak (Penerima, Driver, Gudang) -->
                        <div style="display: flex; justify-content: space-between; text-align: center; margin-top: 14px; font-size: 11px;">
                            <div style="width: 170px;">
                                <div>Tanda Terima Toko / Pelanggan,</div>
                                <div style="height: 38px;"></div>
                                <div style="font-weight: bold;">( Ibu Heni / Toko Maju Jaya )</div>
                                <div style="font-size: 9.5px; color: #555;">Cap Toko &amp; Tanda Tangan</div>
                            </div>
                            <div style="width: 170px;">
                                <div>Petugas Pengantar,</div>
                                <div style="height: 38px;"></div>
                                <div style="font-weight: bold;">( Agus Hermawan )</div>
                                <div style="font-size: 9.5px; color: #555;">Armada Logistik Distribusi</div>
                            </div>
                            <div style="width: 170px;">
                                <div>Hormat Kami,</div>
                                <div style="height: 38px;"></div>
                                <div style="font-weight: bold;">( Petugas Gudang )</div>
                                <div style="font-size: 9.5px; color: #555;">Checker Logistik Pusat</div>
                            </div>
                        </div>

                        <!-- Footer Indikator Rangkap Lembar NCR Continuous Form -->
                        <div style="margin-top: 16px; padding-top: 6px; border-top: 2px solid #000; font-size: 10px; color: #444; display: flex; justify-content: space-between;">
                            <span>[ ] Lembar 1 (Putih): Arsip Gudang</span>
                            <span>&bull;</span>
                            <span>[ ] Lembar 2 (Merah): Toko Mitra</span>
                            <span>&bull;</span>
                            <span>[ ] Lembar 3 (Kuning): Petugas Driver</span>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer Bar -->
                <div style="padding: 12px 22px; background: var(--color-canvas); border-top: 1px solid var(--color-hairline); display: flex; align-items: center; justify-content: space-between; font-size: 12px; flex-shrink: 0;">
                    <div style="color: var(--color-ink-mute); display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="info" style="width: 14px; height: 14px; color: var(--color-primary);"></i>
                        <span>Live Sync: Data di pratinjau ini berubah otomatis mengikuti formulir. Tekan <strong>Esc</strong> untuk menutup.</span>
                    </div>
                    <button type="button" @click="closePreviewModal()" class="btn btn-secondary btn-sm" style="padding: 6px 16px; font-weight: 600;">
                        Tutup Pratinjau
                    </button>
                </div>

            </div>
        </div>
    </template>

</div>

<script>
    // Ensure Lucide icons are hydrated after Alpine renders
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

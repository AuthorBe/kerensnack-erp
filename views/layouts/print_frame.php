<?php
/**
 * views/layouts/print_frame.php
 * Unified Printing Engine Layout Frame untuk Keren Snack ERP.
 * Mendukung 3 profil cetak:
 * 1. Standar A4 / Laser (portrait)
 * 2. Dot Matrix Full Continuous Form (9.5" x 11" / Letter)
 * 3. Dot Matrix Half Continuous Form (9.5" x 5.5" / Wartel)
 */

use App\Core\Router;
use App\Helpers\PrintDocumentHelper;

$formatMode = $formatMode ?? PrintDocumentHelper::resolveFormat($_GET['format'] ?? 'standard');
$isPdf = !empty($isPdf);
$enableHalfMode = $enableHalfMode ?? true;
$backUrl = $backUrl ?? Router::url('/');
$pdfUrl = $pdfUrl ?? '';
$documentTitle = $documentTitle ?? 'Dokumen Cetak';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($documentTitle) ?></title>

    <!-- PWA & Mobile Web App Meta Tags -->
    <meta name="theme-color" content="#881337">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Keren Snack">
    <meta name="application-name" content="Keren Snack ERP">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= Router::asset('/favicon/favicon.ico') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= Router::asset('/favicon/favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= Router::asset('/favicon/favicon-96x96.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= Router::asset('/favicon/apple-touch-icon.png') ?>">
    <link rel="manifest" href="<?= Router::asset('/favicon/site.webmanifest') ?>">

    <style>
        /* BASE RESET & DEFAULT PRINT SETUP */
        @page {
            margin: 12mm 15mm 12mm 15mm;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', 'Arial', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 8.5pt;
            line-height: 1.35;
            color: #000000;
            background: #ffffff;
        }

        /* BROWSER SCREEN PREVIEW (DARK BACKDROP) */
        <?php if (!$isPdf): ?>
        body {
            background-color: #0f172a;
            color: #0f172a;
            padding: 76px 16px 48px 16px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            min-height: 100vh;
            overflow-x: auto;
        }

        .page-sheet {
            background: #ffffff;
            width: 210mm;
            max-width: 96vw;
            min-height: 297mm;
            padding: 15mm 18mm;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.35);
            border-radius: 4px;
            margin: 0 auto 40px auto;
            box-sizing: border-box;
        }
        <?php else: ?>
        .page-sheet {
            width: 100%;
            background: #ffffff;
            padding: 0;
            margin: 0;
        }
        <?php endif; ?>

        table {
            border-collapse: collapse;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        /* ========================================================================= */
        /* TOP STICKY TOOLBAR (WEB PREVIEW ONLY)                                     */
        /* ========================================================================= */
        .print-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 58px;
            background: #1e293b;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
            z-index: 9999;
            box-sizing: border-box;
        }

        .toolbar-left, .toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-tb {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 36px;
            padding: 0 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-tb-back { background: #334155; color: #f8fafc; }
        .btn-tb-back:hover { background: #475569; }
        .btn-tb-pdf { background: #b91c1c; color: #ffffff; }
        .btn-tb-pdf:hover { background: #dc2626; }
        .btn-tb-print { background: #0284c7; color: #ffffff; }
        .btn-tb-print:hover { background: #0369a1; }
        .btn-tb-excel { background: #059669; color: #ffffff; }
        .btn-tb-excel:hover { background: #047857; }

        /* FORMAT TABS SWITCHER */
        .format-tabs {
            display: flex;
            align-items: center;
            background: #0f172a;
            padding: 3px;
            border-radius: 8px;
            border: 1px solid #334155;
            gap: 2px;
        }

        .format-tab-btn {
            background: transparent;
            color: #94a3b8;
            border: none;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .format-tab-btn:hover {
            color: #f1f5f9;
            background: rgba(255,255,255,0.05);
        }

        .format-tab-btn.active {
            background: #3b82f6;
            color: #ffffff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.3);
        }

        /* GUIDELINE BANNER FOR DOT MATRIX */
        .guide-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 900px;
            width: 100%;
            margin: 0 auto 16px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            box-sizing: border-box;
        }

        /* ========================================================================= */
        /* CONTINUOUS FORM (DOT MATRIX 9.5" STYLING)                                 */
        /* ========================================================================= */
        .continuous-wrapper {
            width: 241mm; /* 9.5 inch continuous paper */
            max-width: 96vw;
            min-height: 279mm; /* 11 inch letter */
            background: #ffffff;
            box-shadow: 0 10px 35px rgba(0,0,0,0.35);
            position: relative;
            box-sizing: border-box;
            font-family: 'Consolas', 'Lucida Console', 'Courier New', Courier, monospace;
            color: #000000;
            margin: 0 auto 40px auto;
            border-radius: 4px;
        }

        /* Tractor feed strips with simulated sprocket holes for screen preview */
        .tractor-strip {
            width: 14mm;
            position: absolute;
            top: 0;
            bottom: 0;
            background-color: #f8fafc;
            background-image: radial-gradient(#94a3b8 2.5px, transparent 3px);
            background-size: 14mm 12.7mm;
            background-position: center;
        }
        .tractor-left { left: 0; border-right: 1px dashed #cbd5e1; }
        .tractor-right { right: 0; border-left: 1px dashed #cbd5e1; }

        .continuous-inner {
            padding: 8mm 6mm;
            margin: 0 14mm;
            box-sizing: border-box;
            font-size: 9.5pt;
            line-height: 1.35;
        }

        /* DOT MATRIX HALF-SHEET (9.5" x 5.5" WARTEL) ADJUSTMENTS */
        body.mode-dotmatrix_half .continuous-wrapper {
            min-height: 140mm; /* 5.5 inch */
        }
        body.mode-dotmatrix_half .continuous-inner {
            padding: 4mm 6mm;
            font-size: 8.5pt;
            line-height: 1.25;
        }
        body.mode-dotmatrix_half .dm-brand {
            font-size: 12pt;
        }
        body.mode-dotmatrix_half .dm-title {
            font-size: 11pt;
        }
        body.mode-dotmatrix_half .dm-sig-space {
            height: 28px !important;
        }
        body.mode-dotmatrix_half .dm-divider-double,
        body.mode-dotmatrix_half .dm-divider-single {
            margin: 3px 0 5px 0;
        }

        /* REUSABLE DOT MATRIX ATOM CLASSES */
        .dm-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Consolas', 'Lucida Console', 'Courier New', Courier, monospace;
            font-size: 9.5pt;
            color: #000000;
        }
        body.mode-dotmatrix_half .dm-table {
            font-size: 8.5pt;
        }

        .dm-brand {
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }
        .dm-sub {
            font-size: 9.5pt;
            font-weight: bold;
            margin-top: 2px;
            margin-bottom: 2px;
        }
        .dm-text-muted {
            font-size: 8.5pt;
            color: #111111;
            line-height: 1.35;
        }

        .dm-title {
            font-size: 13pt;
            font-weight: bold;
            text-align: right;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .dm-meta-table {
            width: auto;
            margin-left: auto;
            font-size: 9.5pt;
        }
        body.mode-dotmatrix_half .dm-meta-table {
            font-size: 8.5pt;
        }
        .dm-meta-lbl {
            white-space: nowrap;
            font-weight: bold;
            padding: 1px 0;
            text-align: left;
        }
        .dm-meta-sep {
            width: 14px;
            text-align: center;
            font-weight: bold;
            padding: 1px 4px;
        }
        .dm-meta-val {
            text-align: right;
            white-space: nowrap;
            padding: 1px 0;
        }

        .dm-divider-double {
            border-top: 2px solid #000000;
            border-bottom: 1px solid #000000;
            height: 2px;
            margin: 6px 0 8px 0;
        }

        .dm-divider-single {
            border-top: 1px solid #000000;
            margin: 6px 0 8px 0;
        }

        .dm-section-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 4px;
            font-size: 9pt;
            letter-spacing: 0.3px;
        }

        .dm-subtable {
            width: 100%;
            border-collapse: collapse;
        }
        .dm-lbl {
            width: 125px;
            white-space: nowrap;
            font-weight: bold;
            vertical-align: top;
            padding: 1.5px 0;
            font-size: 9pt;
        }
        .dm-sep {
            width: 14px;
            text-align: center;
            font-weight: bold;
            vertical-align: top;
            padding: 1.5px 4px;
            font-size: 9pt;
        }
        .dm-val {
            vertical-align: top;
            padding: 1.5px 0;
            font-size: 9pt;
            word-break: break-word;
        }

        .dm-items-table th {
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            padding: 4px 4px;
            font-weight: bold;
            font-size: 9pt;
            letter-spacing: 0.3px;
        }
        body.mode-dotmatrix_half .dm-items-table th {
            padding: 2.5px 3px;
            font-size: 8pt;
        }

        .dm-items-table td {
            padding: 3px 4px;
            font-size: 9pt;
            vertical-align: middle;
        }
        body.mode-dotmatrix_half .dm-items-table td {
            padding: 2px 3px;
            font-size: 8pt;
        }

        .dm-items-table tfoot td {
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            padding: 4px 4px;
            font-size: 9pt;
        }
        body.mode-dotmatrix_half .dm-items-table tfoot td {
            padding: 2.5px 3px;
            font-size: 8pt;
        }

        .dm-sig-table {
            margin-top: 10px;
        }
        .dm-sig-title {
            font-size: 8.5pt;
            font-weight: bold;
        }
        .dm-sig-space {
            height: 44px;
        }
        .dm-sig-line {
            font-weight: bold;
            font-size: 9pt;
        }
        .dm-sig-sub {
            font-size: 8pt;
        }

        .dm-ncr-footer {
            font-size: 8pt;
            text-align: center;
            padding-top: 6px;
            font-weight: bold;
            letter-spacing: 0.1px;
        }

        /* ========================================================================= */
        /* DISPLAY TOGGLES (PDF vs BROWSER SCREEN)                                   */
        /* ========================================================================= */
        <?php if ($isPdf): ?>
            <?php if ($formatMode !== 'standard'): ?>
            #sheet-standard { display: none !important; }
            #sheet-dotmatrix { display: block !important; width: 100% !important; margin: 0 !important; box-shadow: none !important; border: none !important; }
            .continuous-inner { padding: 0 !important; margin: 0 !important; }
            .tractor-strip { display: none !important; }
            <?php else: ?>
            #sheet-standard { display: block !important; box-shadow: none !important; }
            #sheet-dotmatrix { display: none !important; }
            <?php endif; ?>
        <?php else: ?>
            body.mode-standard #sheet-standard { display: block; }
            body.mode-standard #sheet-dotmatrix { display: none; }
            body.mode-standard .guide-box-dm { display: none; }

            body.mode-dotmatrix #sheet-standard,
            body.mode-dotmatrix_half #sheet-standard { display: none; }
            body.mode-dotmatrix #sheet-dotmatrix,
            body.mode-dotmatrix_half #sheet-dotmatrix { display: block; }
            body.mode-dotmatrix .guide-box-dm,
            body.mode-dotmatrix_half .guide-box-dm { display: flex; }
        <?php endif; ?>

        /* ========================================================================= */
        /* PRINT STYLES (@media print)                                               */
        /* ========================================================================= */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-toolbar, .actions-bar, .guide-box, .tractor-strip, .no-print {
                display: none !important;
            }

            body.mode-standard #sheet-standard {
                display: block !important;
                box-shadow: none !important;
                padding: 0 !important;
                width: 100% !important;
                border-radius: 0 !important;
            }
            body.mode-standard #sheet-dotmatrix {
                display: none !important;
            }

            body.mode-dotmatrix #sheet-standard,
            body.mode-dotmatrix_half #sheet-standard {
                display: none !important;
            }
            body.mode-dotmatrix #sheet-dotmatrix,
            body.mode-dotmatrix_half #sheet-dotmatrix {
                display: block !important;
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                margin: 0 !important;
            }
            body.mode-dotmatrix .continuous-inner,
            body.mode-dotmatrix_half .continuous-inner {
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>

    <style id="dynamic-page-style">
        <?= PrintDocumentHelper::getDynamicPageCss($formatMode) ?>
    </style>
</head>
<body class="<?= $isPdf ? 'is-pdf mode-' . $formatMode : 'mode-' . $formatMode ?>" style="<?= !$isPdf ? 'padding-top: 66px;' : '' ?>">

    <?php if (!$isPdf): ?>
    <!-- TOP STICKY TOOLBAR -->
    <div class="print-toolbar no-print">
        <div class="toolbar-left">
            <button type="button" onclick="goBackOrUrl('<?= htmlspecialchars($backUrl) ?>')" class="btn-tb btn-tb-back">
                &larr; Kembali
            </button>

            <!-- FORMAT SWITCHER TABS -->
            <div class="format-tabs">
                <button type="button" id="tab-standard" onclick="setFormat('standard')" class="format-tab-btn <?= $formatMode === 'standard' ? 'active' : '' ?>">
                    📄 Standar A4 / Laser
                </button>
                <button type="button" id="tab-dotmatrix" onclick="setFormat('dotmatrix')" class="format-tab-btn <?= $formatMode === 'dotmatrix' ? 'active' : '' ?>">
                    🖨️ Dot Matrix (9.5 x 11")
                </button>
                <?php if ($enableHalfMode): ?>
                <button type="button" id="tab-dotmatrix_half" onclick="setFormat('dotmatrix_half')" class="format-tab-btn <?= $formatMode === 'dotmatrix_half' ? 'active' : '' ?>" title="Ukuran Wartel / Continuous Form Bagi Dua (Hemat Kertas)">
                    ✂️ Dot Matrix Half (9.5 x 5.5")
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="toolbar-right">
            <?= $extraToolbarHtml ?? '' ?>
            <?php if (!empty($pdfUrl)): ?>
            <?php
            $separator = str_contains($pdfUrl, '?') ? '&' : '?';
            $pdfTargetUrl = $pdfUrl . ($formatMode !== 'standard' ? $separator . 'format=' . $formatMode : '');
            ?>
            <a id="btn-pdf-download" href="<?= htmlspecialchars($pdfTargetUrl) ?>" class="btn-tb btn-tb-pdf">
                📄 Unduh PDF
            </a>
            <?php endif; ?>
            <button type="button" onclick="triggerPrint()" class="btn-tb btn-tb-print">
                🖨️ Cetak Dokumen
            </button>
        </div>
    </div>

    <!-- PETUNJUK CETAK DOT MATRIX -->
    <div id="dm-guide-banner" class="guide-box guide-box-dm no-print">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:16px;">💡</span>
            <span id="dm-guide-text">
                <?php if ($formatMode === 'dotmatrix_half'): ?>
                <strong>Tips Cetak Dot Matrix Half (9.5" x 5.5" Wartel):</strong> Di dialog cetak browser (<code>Ctrl+P</code>), pilih Ukuran: <strong>Custom / Statement / 9.5 x 5.5 in</strong>, Margin: <strong>None / Minimum</strong>, dan matikan <strong>Headers &amp; Footers</strong>.
                <?php else: ?>
                <strong>Tips Cetak Dot Matrix (Continuous Form 9.5" x 11"):</strong> Di dialog cetak browser (<code>Ctrl+P</code>), pilih Ukuran: <strong>Letter / 9.5 x 11 in</strong>, Margin: <strong>None / Minimum</strong>, dan matikan <strong>Headers &amp; Footers</strong>.
                <?php endif; ?>
            </span>
        </div>
        <button type="button" onclick="document.getElementById('dm-guide-banner').style.display='none'" style="background:transparent;border:none;cursor:pointer;font-weight:bold;color:#92400e;font-size:14px;padding:4px 8px;" title="Tutup Petunjuk">✕</button>
    </div>
    <?php endif; ?>

    <!-- DOCUMENT CONTENT INJECTED HERE -->
    <?= $content ?? '' ?>

    <?php if (!$isPdf): ?>
    <!-- SCRIPT KONTROL FORMAT & CETAK -->
    <script>
    const BASE_PDF_URL = '<?= !empty($pdfUrl) ? addslashes($pdfUrl) : '' ?>';

    function setFormat(mode) {
        document.body.classList.remove('mode-standard', 'mode-dotmatrix', 'mode-dotmatrix_half');
        document.body.classList.add('mode-' + mode);

        document.getElementById('tab-standard')?.classList.toggle('active', mode === 'standard');
        document.getElementById('tab-dotmatrix')?.classList.toggle('active', mode === 'dotmatrix');
        document.getElementById('tab-dotmatrix_half')?.classList.toggle('active', mode === 'dotmatrix_half');

        // Update link PDF Download
        const pdfBtn = document.getElementById('btn-pdf-download');
        if (pdfBtn && BASE_PDF_URL) {
            const sep = BASE_PDF_URL.includes('?') ? '&' : '?';
            pdfBtn.href = mode !== 'standard' ? BASE_PDF_URL + sep + 'format=' + mode : BASE_PDF_URL;
        }

        // Update Page CSS for browser print
        const pageStyle = document.getElementById('dynamic-page-style');
        if (pageStyle) {
            if (mode === 'dotmatrix_half') {
                pageStyle.innerHTML = '@page { size: 9.5in 5.5in portrait; margin: 4mm 8mm 4mm 8mm; }';
            } else if (mode === 'dotmatrix') {
                pageStyle.innerHTML = '@page { size: letter portrait; margin: 6mm 10mm 6mm 10mm; }';
            } else {
                pageStyle.innerHTML = '@page { size: A4 portrait; margin: 12mm 15mm 12mm 15mm; }';
            }
        }

        // Update Guide Text
        const guideText = document.getElementById('dm-guide-text');
        if (guideText) {
            if (mode === 'dotmatrix_half') {
                guideText.innerHTML = '<strong>Tips Cetak Dot Matrix Half (9.5" x 5.5" Wartel):</strong> Di dialog cetak browser (<code>Ctrl+P</code>), pilih Ukuran: <strong>Custom / Statement / 9.5 x 5.5 in</strong>, Margin: <strong>None / Minimum</strong>, dan matikan <strong>Headers &amp; Footers</strong>.';
            } else {
                guideText.innerHTML = '<strong>Tips Cetak Dot Matrix (Continuous Form 9.5" x 11"):</strong> Di dialog cetak browser (<code>Ctrl+P</code>), pilih Ukuran: <strong>Letter / 9.5 x 11 in</strong>, Margin: <strong>None / Minimum</strong>, dan matikan <strong>Headers &amp; Footers</strong>.';
            }
        }

        // Update Browser URL without reload
        try {
            const url = new URL(window.location.href);
            if (mode !== 'standard') {
                url.searchParams.set('format', mode);
            } else {
                url.searchParams.delete('format');
            }
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    }

    function triggerPrint() {
        window.print();
    }

    window.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p') {
            e.preventDefault();
            triggerPrint();
        }
    });

    function goBackOrUrl(fallbackUrl) {
        if (window.history.length > 1 && document.referrer && document.referrer.includes(window.location.host)) {
            window.history.back();
        } else {
            window.location.href = fallbackUrl;
        }
    }
    </script>
    <?php endif; ?>

</body>
</html>

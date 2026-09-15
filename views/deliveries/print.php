<?php
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CompanySetting;

$comp = CompanySetting::getAll();

$totalQtyMuatan = (int)array_sum(array_column($items, 'kuantitas_satuan_dasar'));
$statusSuratJalan = strtoupper(str_replace('_', ' ', $delivery['status_surat_jalan'] ?? 'SIAP KIRIM'));
$skemaTransaksi = !empty($delivery['is_konsinyasi']) || (($delivery['tipe_pembayaran'] ?? '') === 'konsinyasi')
    ? 'Konsinyasi (Titip Jual)'
    : strtoupper(str_replace('_', ' ', $delivery['tipe_pembayaran'] ?? 'Reguler'));
$satuanTampil = !empty($items[0]['satuan_dasar']) ? htmlspecialchars($items[0]['satuan_dasar']) : 'Bungkus';
$formatMode = $formatMode ?? ((isset($_GET['format']) && $_GET['format'] === 'dotmatrix') ? 'dotmatrix' : 'standard');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan Pengiriman - <?= htmlspecialchars($delivery['nomor_surat_jalan']) ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= Router::asset('/favicon/favicon.ico') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= Router::asset('/favicon/favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= Router::asset('/favicon/favicon-96x96.png') ?>">
    <style>
        @page {
            margin: 15mm 18mm 15mm 18mm;
            size: A4 portrait;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8pt;
            line-height: 1.35;
            color: #000000;
            background: #ffffff;
        }

        <?php if (empty($isPdf)): ?>
        body {
            background-color: #0f172a;
            margin: 0;
            padding: 76px 16px 48px 16px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            min-height: 100vh;
            box-sizing: border-box;
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
            box-sizing: border-box;
            margin: 0 auto 40px auto;
        }
        <?php else: ?>
        .page-sheet {
            width: 100%;
            background: #ffffff;
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

        /* HEADER / KOP RESMI PERUSAHAAN */
        .kop-table {
            width: 100%;
            margin-bottom: 4px;
        }
        .company-name {
            font-size: 15pt;
            font-weight: bold;
            color: #000000;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .company-tagline {
            font-size: 8pt;
            font-weight: bold;
            color: #333333;
            margin-bottom: 3px;
        }
        .company-contact {
            font-size: 7.5pt;
            color: #333333;
            line-height: 1.35;
        }

        .doc-title-main {
            font-size: 13pt;
            font-weight: bold;
            color: #000000;
            text-align: right;
            letter-spacing: 0.5px;
        }
        .doc-title-sub {
            font-size: 8pt;
            font-weight: bold;
            color: #444444;
            text-align: right;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        /* METADATA SURAT JALAN (KOTAK KANAN RATA SEMPURNA) */
        .doc-meta-table {
            width: 250px;
            margin-left: auto;
            margin-top: 3px;
        }
        .doc-meta-table td {
            font-size: 7.5pt;
            padding: 1.5px 0;
            vertical-align: middle;
            color: #000000;
        }

        /* Status Box Formal */
        .status-box {
            display: inline-block;
            border: 1px solid #000000;
            padding: 1px 6px;
            font-weight: bold;
            font-size: 7.5pt;
            letter-spacing: 0.5px;
            color: #000000;
        }

        /* DOUBLE DIVIDER LINE */
        .divider-double {
            border-top: 2px solid #000000;
            border-bottom: 0.5px solid #000000;
            height: 2px;
            margin: 6px 0 9px 0;
        }

        /* CUSTOMER & LOGISTICS INFORMATION BOX */
        .info-card-table {
            width: 100%;
            border: 1px solid #000000;
            margin-bottom: 9px;
        }
        .info-card-header {
            background: #f0f0f0;
            border-bottom: 1px solid #000000;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 4px 8px;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-card-body {
            padding: 6px 8px;
            vertical-align: top;
            font-size: 7.5pt;
        }
        .info-table-inner {
            width: 100%;
        }
        .info-table-inner td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 7.5pt;
            color: #000000;
        }

        /* TABEL RINCIAN ITEM PRODUK RESMI */
        .items-table {
            width: 100%;
            border: 1px solid #000000;
            margin-bottom: 8px;
        }
        .items-table thead {
            display: table-header-group;
        }
        .items-table tr {
            page-break-inside: avoid;
        }
        .items-table th {
            background: #f0f0f0;
            border: 1px solid #000000;
            color: #000000;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 4px;
            letter-spacing: 0.2px;
        }
        .items-table td {
            border: 1px solid #000000;
            padding: 4px 5px;
            font-size: 7.5pt;
            vertical-align: middle;
            color: #000000;
        }
        .items-table tfoot {
            page-break-inside: avoid;
        }
        .items-table tfoot td {
            background: #f5f5f5;
            border: 1px solid #000000;
            font-weight: bold;
            font-size: 7.5pt;
            padding: 5px 4px;
            color: #000000;
        }

        /* BOTTOM SECTION */
        .bottom-table {
            width: 100%;
            margin-top: 5px;
            page-break-inside: avoid;
        }

        .terbilang-box {
            border: 1px solid #000000;
            background: #fafafa;
            padding: 5px 8px;
            margin-bottom: 5px;
            font-size: 7.5pt;
            color: #000000;
        }

        .payment-info-box {
            border: 1px solid #000000;
            padding: 6px 8px;
            font-size: 7pt;
            line-height: 1.45;
            color: #000000;
        }

        .calc-summary-table {
            width: 100%;
            border: 1px solid #000000;
        }
        .calc-summary-table td {
            padding: 3.5px 6px;
            font-size: 7.5pt;
            border-bottom: 0.5px solid #cccccc;
            color: #000000;
            vertical-align: middle;
        }
        .calc-summary-table tr.grand-row td {
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            font-size: 8.5pt;
            font-weight: bold;
            background: #f0f0f0;
            color: #000000;
        }

        /* TANDA TANGAN RESMI (3 KOLOM SEIMBANG) */
        .sig-table {
            width: 100%;
            margin-top: 14px;
            page-break-inside: avoid;
        }
        .sig-cell {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 8px;
        }
        .sig-title {
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #000000;
            line-height: 1.35;
        }
        .sig-space {
            height: 48px;
        }
        .sig-line {
            display: inline-block;
            min-width: 150px;
            border-top: 1px solid #000000;
            padding-top: 3px;
            font-weight: bold;
            font-size: 7.5pt;
            color: #000000;
        }
        .sig-caption {
            font-size: 6.5pt;
            color: #444444;
            margin-top: 1px;
        }

        .doc-footer {
            margin-top: 8px;
            text-align: center;
            font-size: 6.5pt;
            color: #555555;
            border-top: 0.5px solid #cccccc;
            padding-top: 3px;
        }

        /* FLOATING ACTION BUTTONS (WEB VIEW ONLY) */
        .actions-bar {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            gap: 10px;
            z-index: 100;
        }

        .btn-action {
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn-pdf { background: #dc2626; color: white; }
        .btn-print { background: #0284c7; color: white; }
        .btn-back { background: #334155; color: white; }

        /* MODERN PRINT TOOLBAR (SCREEN PREVIEW) */
        .print-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: #0f172a;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .toolbar-left, .toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .format-tabs {
            display: inline-flex;
            background: #1e293b;
            padding: 3px;
            border-radius: 8px;
            border: 1px solid #334155;
        }

        .format-tab-btn {
            background: transparent;
            color: #94a3b8;
            border: none;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }

        .format-tab-btn:hover {
            color: #ffffff;
        }

        .format-tab-btn.active {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .btn-tb {
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-tb-back { background: #334155; color: #f8fafc; }
        .btn-tb-back:hover { background: #475569; }
        .btn-tb-pdf { background: #b91c1c; color: #ffffff; }
        .btn-tb-pdf:hover { background: #dc2626; }
        .btn-tb-print { background: #0284c7; color: #ffffff; }
        .btn-tb-print:hover { background: #0369a1; }
        .btn-tb-tips { background: #3b82f6; color: #ffffff; font-size: 11px; padding: 4px 8px; border-radius: 4px; border: none; cursor: pointer; }

        /* GUIDELINE POPOVER FOR DOT MATRIX PRINT SETTINGS */
        .guide-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 12.5px;
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

        /* CONTINUOUS FORM (DOT MATRIX 9.5" x 11") STYLING */
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

        .dm-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Consolas', 'Lucida Console', 'Courier New', Courier, monospace;
            font-size: 9.5pt;
            color: #000000;
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
            color: #111;
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
        .dm-meta-lbl {
            white-space: nowrap;
            font-weight: bold;
            padding: 1.5px 0;
            text-align: left;
        }
        .dm-meta-sep {
            width: 14px;
            text-align: center;
            font-weight: bold;
            padding: 1.5px 4px;
        }
        .dm-meta-val {
            text-align: right;
            white-space: nowrap;
            padding: 1.5px 0;
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
            margin-bottom: 5px;
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
            padding: 2px 0;
            font-size: 9.5pt;
        }
        .dm-sep {
            width: 14px;
            text-align: center;
            font-weight: bold;
            vertical-align: top;
            padding: 2px 4px;
            font-size: 9.5pt;
        }
        .dm-val {
            vertical-align: top;
            padding: 2px 0;
            font-size: 9.5pt;
            word-break: break-word;
        }

        .dm-items-table th {
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            padding: 5px 4px;
            font-weight: bold;
            font-size: 9pt;
            letter-spacing: 0.3px;
        }

        .dm-items-table td {
            padding: 3.5px 4px;
            font-size: 9.5pt;
            vertical-align: middle;
        }

        .dm-items-table tfoot td {
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            padding: 5px 4px;
            font-size: 9.5pt;
        }

        .dm-sig-table {
            margin-top: 12px;
        }
        .dm-sig-title {
            font-size: 9pt;
            font-weight: bold;
        }
        .dm-sig-space {
            height: 48px;
        }
        .dm-sig-line {
            font-weight: bold;
            font-size: 9.5pt;
        }
        .dm-sig-sub {
            font-size: 8.5pt;
        }

        .dm-ncr-footer {
            font-size: 8pt;
            text-align: center;
            padding-top: 8px;
            font-weight: bold;
            letter-spacing: 0.1px;
        }

        /* DISPLAY TOGGLES */
        <?php if (!empty($isPdf)): ?>
            <?php if (($formatMode ?? '') === 'dotmatrix'): ?>
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

            body.mode-dotmatrix #sheet-standard { display: none; }
            body.mode-dotmatrix #sheet-dotmatrix { display: block; }
            body.mode-dotmatrix .guide-box-dm { display: flex; }
        <?php endif; ?>

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-toolbar, .actions-bar, .guide-box, .tractor-strip {
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
            body.mode-dotmatrix #sheet-standard {
                display: none !important;
            }
            body.mode-dotmatrix #sheet-dotmatrix {
                display: block !important;
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                margin: 0 !important;
            }
            body.mode-dotmatrix .continuous-inner {
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
    <style id="dynamic-page-style">
        <?php if ($formatMode === 'dotmatrix'): ?>
        @page { size: letter portrait; margin: 6mm 10mm 6mm 10mm; }
        <?php else: ?>
        @page { size: A4 portrait; margin: 12mm 15mm 12mm 15mm; }
        <?php endif; ?>
    </style>
</head>
<body class="<?= !empty($isPdf) ? 'is-pdf mode-' . ($formatMode ?? 'standard') : 'mode-' . $formatMode ?>" style="<?= empty($isPdf) ? 'padding-top: 66px;' : '' ?>">

    <?php if (empty($isPdf)): ?>
    <!-- TOP STICKY TOOLBAR -->
    <div class="print-toolbar no-print">
        <div class="toolbar-left">
            <button type="button" onclick="goBackOrUrl('<?= Router::url('/deliveries') ?>')" class="btn-tb btn-tb-back">
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
            </div>
        </div>

        <div class="toolbar-right">
            <a id="btn-pdf-download" href="<?= Router::url('/deliveries/pdf?id=' . $delivery['id'] . ($formatMode === 'dotmatrix' ? '&format=dotmatrix' : '')) ?>" class="btn-tb btn-tb-pdf">
                📄 Unduh PDF
            </a>
            <button type="button" onclick="triggerPrint()" class="btn-tb btn-tb-print">
                🖨️ Cetak Dokumen
            </button>
        </div>
    </div>

    <!-- PETUNJUK CETAK DOT MATRIX -->
    <div id="dm-guide-banner" class="guide-box guide-box-dm no-print">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:16px;">💡</span>
            <span><strong>Tips Cetak Dot Matrix (Continuous Form 9.5" x 11"):</strong> Di dialog cetak browser (<code>Ctrl+P</code>), pilih Ukuran: <strong>Letter / 9.5 x 11 in</strong>, Margin: <strong>None / Minimum</strong>, dan matikan <strong>Headers &amp; Footers</strong>.</span>
        </div>
        <button type="button" onclick="document.getElementById('dm-guide-banner').style.display='none'" style="background:transparent;border:none;cursor:pointer;font-weight:bold;color:#92400e;font-size:14px;padding:4px 8px;" title="Tutup Petunjuk">✕</button>
    </div>
    <?php endif; ?>

    <?php if (empty($isPdf) || ($formatMode ?? '') === 'standard'): ?>
    <div id="sheet-standard" class="page-sheet">
        <!-- KOP PERUSAHAAN & JUDUL SURAT JALAN RESMI -->
        <table class="kop-table">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div class="company-name"><?= htmlspecialchars($comp['nama']) ?></div>
                    <div class="company-tagline"><?= htmlspecialchars($comp['tagline']) ?></div>
                    <div class="company-contact">
                        <?= htmlspecialchars($comp['alamat']) ?> &bull; Telp/WA: <?= htmlspecialchars($comp['telepon']) ?><br>
                        <?= !empty($comp['email']) ? 'Email: ' . htmlspecialchars($comp['email']) : '' ?><?= (!empty($comp['email']) && !empty($comp['website'])) ? ' &bull; ' : '' ?><?= !empty($comp['website']) ? 'Website: ' . htmlspecialchars($comp['website']) : '' ?>
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <div class="doc-title-main">SURAT JALAN PENGIRIMAN</div>
                    <div class="doc-title-sub">BUKTI SERAH TERIMA PENGIRIMAN</div>

                    <!-- Perataan Barisan Sempurna: Kotak Kanan Lebar Tetap & Titik Dua Lurus -->
                    <table class="doc-meta-table">
                        <tr>
                            <td style="width: 110px; font-weight: bold;">No. Surat Jalan</td>
                            <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                            <td style="font-weight: bold;"><?= htmlspecialchars($delivery['nomor_surat_jalan']) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Tanggal Pengiriman</td>
                            <td style="text-align: center; font-weight: bold;">:</td>
                            <td><?= date('d F Y', strtotime($delivery['tanggal_surat_jalan'] ?: ($delivery['dibuat_pada'] ?: $delivery['tanggal_pesanan']))) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">No. Faktur / Nota</td>
                            <td style="text-align: center; font-weight: bold;">:</td>
                            <td><?= htmlspecialchars($delivery['nomor_nota']) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Status Pengiriman</td>
                            <td style="text-align: center; font-weight: bold;">:</td>
                            <td>
                                <span class="status-box"><?= $statusSuratJalan ?></span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="divider-double"></div>

        <!-- INFORMASI PELANGGAN & LOGISTIK (2 KOLOM SIMETRIS) -->
        <table class="info-card-table">
            <tr>
                <td style="width: 50%; border-right: 1px solid #000000;" class="info-card-header">
                    KEPADA YTH. (TOKO PENERIMA)
                </td>
                <td style="width: 50%;" class="info-card-header">
                    INFORMASI LOGISTIK &amp; PENGIRIMAN
                </td>
            </tr>
            <tr>
                <td class="info-card-body" style="border-right: 1px solid #000000;">
                    <table class="info-table-inner">
                        <tr>
                            <td style="width: 95px; font-weight: bold;">Nama Toko</td>
                            <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                            <td style="font-weight: bold; font-size: 8.5pt;"><?= htmlspecialchars($delivery['nama_toko']) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Kode Pelanggan</td>
                            <td style="text-align: center; font-weight: bold;">:</td>
                            <td><?= htmlspecialchars($delivery['kode_pelanggan'] ?? '-') ?></td>
                        </tr>
                        <?php if (!empty($delivery['nama_pemilik'])): ?>
                        <tr>
                            <td style="font-weight: bold;">Pemilik / PIC</td>
                            <td style="text-align: center; font-weight: bold;">:</td>
                            <td><?= htmlspecialchars($delivery['nama_pemilik']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td style="font-weight: bold;">Alamat Toko</td>
                            <td style="text-align: center; font-weight: bold;">:</td>
                            <td><?= htmlspecialchars($delivery['alamat_toko'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">No. Telepon / WA</td>
                            <td style="text-align: center; font-weight: bold;">:</td>
                            <td><?= htmlspecialchars($delivery['nomor_whatsapp'] ?: '-') ?></td>
                        </tr>
                    </table>
                </td>
                <td class="info-card-body">
                    <table class="info-table-inner">
                        <tr>
                            <td style="width: 95px; font-weight: bold;">Driver / Pengantar</td>
                            <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                            <td style="font-weight: bold;"><?= htmlspecialchars($delivery['nama_driver'] ?: 'Armada Pengiriman') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">No. Kendaraan</td>
                            <td style="text-align: center; font-weight: bold;">:</td>
                            <td><?= htmlspecialchars($delivery['nopol_driver'] ?: '-') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Telp. Driver</td>
                            <td style="text-align: center; font-weight: bold;">:</td>
                            <td><?= htmlspecialchars($delivery['telp_driver'] ?: '-') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Wilayah / Rute</td>
                            <td style="text-align: center; font-weight: bold;">:</td>
                            <td><?= htmlspecialchars($delivery['nama_wilayah'] ?? '-') ?> <?= !empty($delivery['kode_rute']) ? '('.htmlspecialchars($delivery['kode_rute']).')' : '' ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Skema Transaksi</td>
                            <td style="text-align: center; font-weight: bold;">:</td>
                            <td><?= $skemaTransaksi ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- TABEL RINCIAN ITEM BARANG PENGIRIMAN -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">NO</th>
                    <th style="width: 15%;" class="text-center">KODE SKU</th>
                    <th style="width: 44%;" class="text-left" style="padding-left: 6px;">NAMA PRODUK / VARIAN BARANG</th>
                    <th style="width: 10%;" class="text-center">SATUAN</th>
                    <th style="width: 12%;" class="text-center">QTY KIRIM</th>
                    <th style="width: 14%;" class="text-center">KONDISI FISIK</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $idx => $it): 
                    $qty = (int)$it['kuantitas_satuan_dasar'];
                ?>
                <tr>
                    <td class="text-center"><?= $idx + 1 ?></td>
                    <td class="text-center font-bold"><?= htmlspecialchars($it['kode_sku'] ?? '') ?></td>
                    <td style="padding-left: 6px;">
                        <strong><?= htmlspecialchars($it['nama_item']) ?></strong>
                        <?php if (!empty($it['varian_rasa'])): ?>
                        <span style="font-size: 7pt; color: #555555;">(<?= htmlspecialchars($it['varian_rasa']) ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= htmlspecialchars($it['satuan_dasar'] ?: 'pcs') ?></td>
                    <td class="text-center font-bold" style="font-size: 8.5pt;"><?= number_format($qty, 0, ',', '.') ?></td>
                    <td class="text-center font-bold" style="color: #000000;">Baik &amp; Segel</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right font-bold" style="padding-right: 8px;">TOTAL KUANTITAS MUATAN :</td>
                    <td class="text-center font-bold" style="font-size: 9pt;"><?= number_format($totalQtyMuatan, 0, ',', '.') ?></td>
                    <td class="text-center font-bold">Lengkap</td>
                </tr>
            </tfoot>
        </table>

        <!-- BOTTOM SECTION: KETENTUAN, INSTRUKSI, RINGKASAN -->
        <table class="bottom-table">
            <tr>
                <td style="width: 56%; vertical-align: top; padding-right: 10px;">
                    <!-- KOTAK TERBILANG KUANTITAS -->
                    <div class="terbilang-box">
                        <span style="font-weight: bold; text-transform: uppercase;">Terbilang Kuantitas Muatan:</span><br>
                        <span style="font-style: italic; font-weight: bold;">
                            # <?= Format::terbilang($totalQtyMuatan, false) ?> <?= $satuanTampil ?> #
                        </span>
                    </div>

                    <!-- KETENTUAN SERAH TERIMA -->
                    <div class="payment-info-box">
                        <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 3px; border-bottom: 0.5px solid #cccccc; padding-bottom: 2px;">
                            Ketentuan Serah Terima Barang:
                        </div>
                        1. Harap periksa fisik kemasan dan kesesuaian jumlah barang saat diserahterimakan.<br>
                        2. Surat jalan ini sah sebagai bukti resmi perpindahan fisik barang dagangan dari gudang ke toko mitra.<br>
                        3. Segala bentuk komplain fisik atau ketidaksesuaian wajib dicatat pada lembar ini dan dilaporkan maksimal 1x24 jam.<br>
                        <?php if (!empty($delivery['catatan_pesanan'])): ?>
                        4. Catatan Khusus: <em><?= htmlspecialchars($delivery['catatan_pesanan']) ?></em>
                        <?php endif; ?>
                    </div>
                </td>

                <td style="width: 44%; vertical-align: top;">
                    <!-- Perataan Barisan Sempurna: Lebar Label 120px, Titik Dua 10px, Nilai Rata Kanan -->
                    <table class="calc-summary-table">
                        <tr>
                            <td style="width: 120px;">Total Macam Produk</td>
                            <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                            <td class="text-right font-bold"><?= count($items) ?> SKU</td>
                        </tr>
                        <tr>
                            <td>Skema Transaksi</td>
                            <td style="text-align: center; font-weight: bold;">:</td>
                            <td class="text-right font-bold"><?= $skemaTransaksi ?></td>
                        </tr>
                        <tr class="grand-row">
                            <td style="font-weight: bold;">TOTAL MUATAN</td>
                            <td style="text-align: center; font-weight: bold;">:</td>
                            <td class="text-right font-bold"><?= number_format($totalQtyMuatan, 0, ',', '.') ?> <?= $satuanTampil ?></td>
                        </tr>
                        <tr>
                            <td>Status Pengiriman</td>
                            <td style="text-align: center; font-weight: bold;">:</td>
                            <td class="text-right font-bold"><?= $statusSuratJalan ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- TANDA TANGAN 3 PIHAK (PENERIMA, DRIVER, GUDANG) -->
        <table class="sig-table">
            <tr>
                <td class="sig-cell">
                    <div class="sig-title">Tanda Terima Toko / Pelanggan,</div>
                    <div class="sig-space"></div>
                    <div class="sig-line">( <?= htmlspecialchars($delivery['nama_penerima_toko'] ?: ($delivery['nama_pemilik'] ?: $delivery['nama_toko'])) ?> )</div>
                    <div class="sig-caption">Tanda Tangan &amp; Cap Toko</div>
                </td>

                <td class="sig-cell">
                    <div class="sig-title">Petugas Pengantar,</div>
                    <div class="sig-space"></div>
                    <div class="sig-line">( <?= htmlspecialchars($delivery['nama_driver'] ?: 'Sales Driver') ?> )</div>
                    <div class="sig-caption">Armada Logistik Distribusi</div>
                </td>

                <td class="sig-cell">
                    <div class="sig-title">Hormat Kami,<br><?= htmlspecialchars($comp['nama']) ?></div>
                    <div class="sig-space"></div>
                    <div class="sig-line">( Petugas Gudang )</div>
                    <div class="sig-caption">Checker Logistik Pusat</div>
                </td>
            </tr>
        </table>

        <div class="doc-footer">
            Surat Jalan ini dicetak secara otomatis melalui Sistem ERP <?= htmlspecialchars($comp['nama']) ?> pada <?= date('d/m/Y H:i:s') ?> dan merupakan dokumen sah serah terima barang.
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($isPdf) || ($formatMode ?? '') === 'dotmatrix'): ?>
    <!-- FORMAT KHUSUS PRINTER DOT MATRIX (CONTINUOUS FORM FULL FOLIO / LETTER 9.5" x 11") -->
    <div id="sheet-dotmatrix" class="continuous-wrapper">
        <div class="tractor-strip tractor-left"></div>
        <div class="continuous-inner">
            <!-- KOP RESMI PERUSAHAAN & HEADER DOKUMEN -->
            <table class="dm-table">
                <tr>
                    <td style="width: 55%; vertical-align: top;">
                        <div class="dm-brand"><?= htmlspecialchars($comp['nama']) ?></div>
                        <div class="dm-sub"><?= htmlspecialchars($comp['tagline']) ?></div>
                        <div class="dm-text-muted"><?= htmlspecialchars($comp['alamat']) ?></div>
                        <div class="dm-text-muted">Telp/WA: <?= htmlspecialchars($comp['telepon']) ?><?= !empty($comp['email']) ? ' &bull; Email: ' . htmlspecialchars($comp['email']) : '' ?></div>
                    </td>
                    <td style="width: 45%; vertical-align: top; text-align: right;">
                        <div class="dm-title">SURAT JALAN PENGIRIMAN</div>
                        <table class="dm-meta-table">
                            <tr>
                                <td class="dm-meta-lbl">No. Surat Jalan</td>
                                <td class="dm-meta-sep">:</td>
                                <td class="dm-meta-val"><strong><?= htmlspecialchars($delivery['nomor_surat_jalan']) ?></strong></td>
                            </tr>
                            <tr>
                                <td class="dm-meta-lbl">Tanggal Kirim</td>
                                <td class="dm-meta-sep">:</td>
                                <td class="dm-meta-val"><?= date('d/m/Y', strtotime($delivery['tanggal_surat_jalan'] ?: ($delivery['dibuat_pada'] ?: $delivery['tanggal_pesanan']))) ?></td>
                            </tr>
                            <tr>
                                <td class="dm-meta-lbl">No. Faktur / PO</td>
                                <td class="dm-meta-sep">:</td>
                                <td class="dm-meta-val"><?= htmlspecialchars($delivery['nomor_nota']) ?></td>
                            </tr>
                            <tr>
                                <td class="dm-meta-lbl">Status Kirim</td>
                                <td class="dm-meta-sep">:</td>
                                <td class="dm-meta-val">[ <?= $statusSuratJalan ?> ]</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="dm-divider-double"></div>

            <!-- TUJUAN PENERIMA & INFORMASI EKSPEDISI / ARMADA -->
            <table class="dm-table">
                <tr>
                    <td style="width: 52%; vertical-align: top; padding-right: 12px;">
                        <div class="dm-section-title">TUJUAN PENGIRIMAN (TOKO PELANGGAN):</div>
                        <table class="dm-subtable">
                            <tr>
                                <td class="dm-lbl">Nama Toko</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><strong><?= htmlspecialchars($delivery['nama_toko']) ?></strong> (<?= htmlspecialchars($delivery['kode_pelanggan']) ?>)</td>
                            </tr>
                            <tr>
                                <td class="dm-lbl">Pemilik / PIC</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><?= htmlspecialchars($delivery['nama_pemilik'] ?: '-') ?></td>
                            </tr>
                            <tr>
                                <td class="dm-lbl">Alamat Lengkap</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><?= htmlspecialchars($delivery['alamat_toko'] ?: '-') ?></td>
                            </tr>
                            <tr>
                                <td class="dm-lbl">No. Telp / WA</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><?= htmlspecialchars($delivery['nomor_whatsapp'] ?: '-') ?></td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 48%; vertical-align: top; border-left: 1px dashed #000000; padding-left: 14px;">
                        <div class="dm-section-title">DATA ARMADA LOGISTIK:</div>
                        <table class="dm-subtable">
                            <tr>
                                <td class="dm-lbl">Driver / Kurir</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><strong><?= htmlspecialchars($delivery['nama_driver'] ?: 'Sales Driver') ?></strong></td>
                            </tr>
                            <tr>
                                <td class="dm-lbl">No. Polisi</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><?= htmlspecialchars($delivery['nopol_driver'] ?: '-') ?></td>
                            </tr>
                            <tr>
                                <td class="dm-lbl">Rute / Wilayah</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><?= htmlspecialchars($delivery['nama_wilayah'] ?: 'Distribusi Lokal') ?></td>
                            </tr>
                            <tr>
                                <td class="dm-lbl">Skema Kirim</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><strong><?= $skemaTransaksi ?></strong></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="dm-divider-single"></div>

            <!-- TABEL DAFTAR ITEM PRODUK MUATAN (80 KOLOM COMPATIBLE) -->
            <table class="dm-table dm-items-table">
                <thead>
                    <tr>
                        <th style="width: 35px; text-align: center;">NO</th>
                        <th style="width: 110px; text-align: left;">KODE SKU</th>
                        <th style="text-align: left;">NAMA BARANG / ITEM PRODUK</th>
                        <th style="width: 120px; text-align: left;">VARIAN</th>
                        <th style="width: 75px; text-align: right;">QTY</th>
                        <th style="width: 75px; text-align: center;">SATUAN</th>
                        <th style="width: 75px; text-align: center;">CEK FISIK</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1; 
                    foreach ($items as $it): 
                        $namaItem = trim((string)($it['nama_item'] ?? ''));
                        $varianRasa = trim((string)($it['varian_rasa'] ?? ''));
                        $varianClean = (!empty($varianRasa) && strcasecmp($varianRasa, $namaItem) !== 0) ? $varianRasa : '-';
                    ?>
                    <tr>
                        <td style="text-align: center;"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($it['kode_sku'] ?? '-') ?></td>
                        <td><strong><?= htmlspecialchars($namaItem) ?></strong></td>
                        <td><?= htmlspecialchars($varianClean) ?></td>
                        <td style="text-align: right;"><strong><?= number_format((int)$it['kuantitas_satuan_dasar'], 0, ',', '.') ?></strong></td>
                        <td style="text-align: center;"><?= htmlspecialchars($it['satuan_dasar'] ?: 'Bungkus') ?></td>
                        <td style="text-align: center;">[ &nbsp; ]</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align: right; font-weight: bold;">TOTAL KUANTITAS MUATAN:</td>
                        <td style="text-align: right; font-weight: bold;"><?= number_format($totalQtyMuatan, 0, ',', '.') ?></td>
                        <td style="text-align: center; font-weight: bold;"><?= $satuanTampil ?></td>
                        <td style="text-align: center;">(<?= count($items) ?> SKU)</td>
                    </tr>
                </tfoot>
            </table>

            <div class="dm-divider-single"></div>

            <!-- CATATAN & TERBILANG -->
            <table class="dm-table" style="margin-bottom: 8px;">
                <tr>
                    <td style="vertical-align: top; width: 62%; font-size: 9pt;">
                        <strong>Terbilang:</strong> <em># <?= Format::terbilang($totalQtyMuatan, false) ?> <?= $satuanTampil ?> #</em><br>
                        <strong>Catatan:</strong> <?= !empty($delivery['catatan_pesanan']) ? htmlspecialchars($delivery['catatan_pesanan']) : 'Barang telah diperiksa lengkap & kondisi baik saat muat.' ?><br>
                        <em>* Mohon periksa fisik kemasan & segel bersama driver saat serah terima di toko.</em>
                    </td>
                    <td style="vertical-align: top; width: 38%; text-align: right; font-size: 8.5pt;">
                        Dokumen ERP Keren Snack<br>
                        Cetak: <?= date('d/m/Y H:i:s') ?>
                    </td>
                </tr>
            </table>

            <!-- TANDA TANGAN 3 PIHAK (PENERIMA, DRIVER, GUDANG) -->
            <table class="dm-table dm-sig-table">
                <tr>
                    <td style="width: 33.3%; text-align: center;">
                        <div class="dm-sig-title">Tanda Terima Toko / Pelanggan,</div>
                        <div class="dm-sig-space"></div>
                        <div class="dm-sig-line">( <?= htmlspecialchars($delivery['nama_penerima_toko'] ?: ($delivery['nama_pemilik'] ?: $delivery['nama_toko'])) ?> )</div>
                        <div class="dm-sig-sub">Cap Toko &amp; Tanda Tangan</div>
                    </td>
                    <td style="width: 33.3%; text-align: center;">
                        <div class="dm-sig-title">Petugas Pengantar,</div>
                        <div class="dm-sig-space"></div>
                        <div class="dm-sig-line">( <?= htmlspecialchars($delivery['nama_driver'] ?: 'Sales Driver') ?> )</div>
                        <div class="dm-sig-sub">Armada Logistik Distribusi</div>
                    </td>
                    <td style="width: 33.3%; text-align: center;">
                        <div class="dm-sig-title">Hormat Kami,</div>
                        <div class="dm-sig-space"></div>
                        <div class="dm-sig-line">( Petugas Gudang )</div>
                        <div class="dm-sig-sub">Checker Logistik Pusat</div>
                    </td>
                </tr>
            </table>

            <div class="dm-divider-double" style="margin-top: 10px;"></div>

            <!-- FOOTER COPY INDIKATOR RANGKAP NCR CONTINUOUS FORM -->
            <div class="dm-ncr-footer">
                <span>[ ] Lembar 1 (Putih): Arsip Gudang</span> &nbsp;&bull;&nbsp;
                <span>[ ] Lembar 2 (Merah): Toko Mitra</span> &nbsp;&bull;&nbsp;
                <span>[ ] Lembar 3 (Kuning): Petugas Driver</span>
            </div>
        </div>
        <div class="tractor-strip tractor-right"></div>
    </div>
    <?php endif; ?>

    <?php if (empty($isPdf)): ?>
    <!-- SCRIPT KONTROL FORMAT & CETAK -->
    <script>
    function setFormat(mode) {
        document.body.classList.remove('mode-standard', 'mode-dotmatrix');
        document.body.classList.add('mode-' + mode);

        document.getElementById('tab-standard')?.classList.toggle('active', mode === 'standard');
        document.getElementById('tab-dotmatrix')?.classList.toggle('active', mode === 'dotmatrix');

        const pdfBtn = document.getElementById('btn-pdf-download');
        if (pdfBtn) {
            const basePdfUrl = '<?= Router::url('/deliveries/pdf?id=' . $delivery['id']) ?>';
            pdfBtn.href = mode === 'dotmatrix' ? basePdfUrl + '&format=dotmatrix' : basePdfUrl;
        }

        const pageStyle = document.getElementById('dynamic-page-style');
        if (pageStyle) {
            if (mode === 'dotmatrix') {
                pageStyle.innerHTML = '@page { size: letter portrait; margin: 6mm 10mm 6mm 10mm; }';
            } else {
                pageStyle.innerHTML = '@page { size: A4 portrait; margin: 15mm 18mm 15mm 18mm; }';
            }
        }

        try {
            const url = new URL(window.location.href);
            if (mode === 'dotmatrix') {
                url.searchParams.set('format', 'dotmatrix');
            } else {
                url.searchParams.delete('format');
            }
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    }

    function triggerPrint() {
        window.print();
    }

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

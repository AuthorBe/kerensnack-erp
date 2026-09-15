<?php
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CompanySetting;

$comp = CompanySetting::getAll();
$formatMode = $formatMode ?? ((isset($_GET['format']) && $_GET['format'] === 'dotmatrix') ? 'dotmatrix' : 'standard');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= (!empty($order['is_konsinyasi']) || (($order['tipe_pembayaran'] ?? '') === 'konsinyasi')) ? 'Bukti Titip Barang' : 'Faktur Penjualan' ?> - <?= htmlspecialchars($order['nomor_nota']) ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= Router::asset('/favicon/favicon.ico') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= Router::asset('/favicon/favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= Router::asset('/favicon/favicon-96x96.png') ?>">
    <style>
        @page {
            margin: 12mm 15mm 12mm 15mm;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.35;
            color: #0f172a;
            background: #ffffff;
        }

        <?php if (empty($isPdf)): ?>
        body {
            background-color: #0f172a;
            color: #0f172a;
            margin: 0;
            padding: 76px 16px 48px 16px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            min-height: 100vh;
            box-sizing: border-box;
            overflow-x: auto;
        }

        .invoice-container {
            background: #ffffff;
            width: 100%;
            max-width: 860px;
            padding: 32px;
            border-radius: 4px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.35);
            margin: 0 auto 40px auto;
            box-sizing: border-box;
        }
        <?php else: ?>
        .invoice-container {
            width: 100%;
            background: #ffffff;
            padding: 0;
            margin: 0;
        }
        <?php endif; ?>

        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 24px;
            font-weight: 900;
            color: #e11d48;
            letter-spacing: -0.5px;
        }

        .company-sub {
            font-size: 11.5px;
            color: #64748b;
            line-height: 1.4;
            margin-top: 4px;
        }

        .invoice-title {
            text-align: right;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .invoice-no {
            text-align: right;
            font-family: monospace;
            font-size: 14px;
            font-weight: 700;
            color: #059669;
            margin-top: 4px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
            font-size: 12.5px;
        }

        .meta-box {
            background: #f8fafc;
            padding: 12px 14px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .meta-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .meta-value {
            font-weight: 700;
            color: #0f172a;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12.5px;
        }

        .items-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-align: left;
            padding: 8px 10px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
        }

        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .font-bold { font-weight: 700; }

        .summary-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 20px;
            margin-top: 10px;
            font-size: 12.5px;
        }

        .terbilang-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 12px;
            font-size: 12px;
            line-height: 1.5;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 4px 6px;
        }

        .total-row {
            border-top: 2px solid #0f172a;
            font-size: 15px;
            font-weight: 800;
            color: #059669;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
        }

        .sign-box {
            padding-top: 60px;
            border-top: 1px solid #0f172a;
            font-weight: 700;
        }

        .action-bar {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            gap: 10px;
            z-index: 100;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .btn-print {
            background: #0f172a;
            color: #ffffff;
        }

        .btn-pdf {
            background: #dc2626;
            color: #ffffff;
            text-decoration: none;
        }

        .btn-excel {
            background: #059669;
            color: #ffffff;
            text-decoration: none;
        }

        .btn-back {
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            text-decoration: none;
        }

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
        .btn-tb-excel { background: #059669; color: #ffffff; }
        .btn-tb-excel:hover { background: #047857; }
        .btn-tb-print { background: #0284c7; color: #ffffff; }
        .btn-tb-print:hover { background: #0369a1; }

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

        .dm-subtable td {
            padding: 2px 0;
            font-size: 9.5pt;
            vertical-align: top;
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
            #invoice-standard { display: none !important; }
            #invoice-dotmatrix { display: block !important; width: 100% !important; margin: 0 !important; box-shadow: none !important; border: none !important; }
            .continuous-inner { padding: 0 !important; margin: 0 !important; }
            .tractor-strip { display: none !important; }
            <?php else: ?>
            #invoice-standard { display: block !important; box-shadow: none !important; }
            #invoice-dotmatrix { display: none !important; }
            <?php endif; ?>
        <?php else: ?>
            body.mode-standard #invoice-standard { display: block; }
            body.mode-standard #invoice-dotmatrix { display: none; }
            body.mode-standard .guide-box-dm { display: none; }

            body.mode-dotmatrix #invoice-standard { display: none; }
            body.mode-dotmatrix #invoice-dotmatrix { display: block; }
            body.mode-dotmatrix .guide-box-dm { display: flex; }
        <?php endif; ?>

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-toolbar, .action-bar, .guide-box, .tractor-strip {
                display: none !important;
            }
            body.mode-standard #invoice-standard {
                display: block !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            body.mode-standard #invoice-dotmatrix {
                display: none !important;
            }
            body.mode-dotmatrix #invoice-standard {
                display: none !important;
            }
            body.mode-dotmatrix #invoice-dotmatrix {
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
            <button type="button" onclick="goBackOrUrl('<?= Router::url('/customer-orders') ?>')" class="btn-tb btn-tb-back">
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
            <a id="btn-pdf-download" href="<?= Router::url('/customer-orders/invoice/pdf?id=' . $order['id'] . ($formatMode === 'dotmatrix' ? '&format=dotmatrix' : '')) ?>" class="btn-tb btn-tb-pdf">
                📄 Unduh PDF
            </a>
            <a href="<?= Router::url('/customer-orders/invoice/excel?id=' . $order['id']) ?>" class="btn-tb btn-tb-excel">
                📊 Unduh Excel
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

<?php
$isKonsinyasi = !empty($order['is_konsinyasi']) || (($order['tipe_pembayaran'] ?? '') === 'konsinyasi') || (isset($order['adalah_tagihan']) && ($order['adalah_tagihan'] === false || $order['adalah_tagihan'] === 'f' || $order['adalah_tagihan'] === 0 || $order['adalah_tagihan'] === 'false'));
?>
    <?php if (empty($isPdf) || ($formatMode ?? '') === 'standard'): ?>
    <div id="invoice-standard" class="invoice-container">
        <!-- HEADER -->
        <table class="header-table">
            <tr>
                <td style="vertical-align:top; width:60%;">
                    <div class="company-name"><?= htmlspecialchars($comp['nama']) ?></div>
                    <div class="company-sub">
                        <?= htmlspecialchars($comp['tagline']) ?><br>
                        Telp / WhatsApp: <?= htmlspecialchars($comp['telepon']) ?><?= !empty($comp['email']) ? ' • Email: ' . htmlspecialchars($comp['email']) : '' ?><?= !empty($comp['website']) ? ' • Web: ' . htmlspecialchars($comp['website']) : '' ?><br>
                        <?= htmlspecialchars($comp['alamat']) ?>
                    </div>
                </td>
                <td style="vertical-align:top; width:40%;">
                    <div class="invoice-title"><?= $isKonsinyasi ? 'BUKTI TITIP BARANG' : 'FAKTUR PENJUALAN' ?></div>
                    <div class="invoice-no"><?= htmlspecialchars($order['nomor_nota']) ?></div>
                    <div style="text-align:right; font-size:11.5px; color:#64748b; margin-top:4px;">
                        Tanggal: <strong><?= date('d/m/Y', strtotime($order['tanggal_pesanan'])) ?></strong>
                    </div>
                </td>
            </tr>
        </table>

        <!-- METADATA -->
        <table class="meta-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <tr>
                <td style="width: 48%; vertical-align: top;">
                    <div class="meta-box">
                        <div class="meta-label">Kepada Toko Pelanggan:</div>
                        <div class="meta-value" style="font-size:14px;"><?= htmlspecialchars($order['nama_toko']) ?></div>
                        <div style="color:#64748b; margin-top:3px;">
                            Kode: <strong><?= htmlspecialchars($order['kode_pelanggan']) ?></strong> 
                            <?php if (!empty($order['nama_pemilik'])): ?>
                            &bull; PIC: <?= htmlspecialchars($order['nama_pemilik']) ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($order['alamat_lengkap'])): ?>
                        <div style="color:#64748b; margin-top:3px; font-size:11.5px;"><?= htmlspecialchars($order['alamat_lengkap']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($order['nomor_whatsapp'])): ?>
                        <div style="color:#64748b; margin-top:2px; font-size:11px;">WA: <?= htmlspecialchars($order['nomor_whatsapp']) ?></div>
                        <?php endif; ?>
                    </div>
                </td>
                <td style="width: 4%;"></td>
                <td style="width: 48%; vertical-align: top;">
                    <div class="meta-box">
                        <div class="meta-label"><?= $isKonsinyasi ? 'Rincian Pengiriman:' : 'Rincian Pengiriman &amp; Pembayaran:' ?></div>
                        <div>Petugas Pengantar: <strong><?= htmlspecialchars($order['nama_sales'] ?: 'Driver Toko') ?></strong></div>
                        <?php if ($isKonsinyasi): ?>
                        <div style="margin-top:3px;">
                            Skema Distribusi: <strong style="color:#d97706;">TITIP JUAL (KONSINYASI)</strong>
                        </div>
                        <div style="margin-top:3px; font-size:11px; color:#64748b;">
                            * Non-Tagihan Langsung (Penagihan via Opname Sales)
                        </div>
                        <?php else: ?>
                        <div style="margin-top:3px;">
                            Tipe Pembayaran: <strong><?= strtoupper(str_replace('_', ' ', $order['tipe_pembayaran'])) ?></strong>
                            <?php if ($order['status_pembayaran'] === 'lunas'): ?>
                            <span style="color:#059669; font-weight:800;"> (LUNAS)</span>
                            <?php else: ?>
                            <span style="color:#dc2626; font-weight:800;"> (TEMPO)</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($order['tanggal_jatuh_tempo'])): ?>
                        <div style="margin-top:3px; color:#dc2626;">
                            Jatuh Tempo: <strong><?= date('d/m/Y', strtotime($order['tanggal_jatuh_tempo'])) ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($order['nama_akun_kas'])): ?>
                        <div style="margin-top:2px; font-size:11px; color:#64748b;">
                            Rekening / Kas: <strong><?= htmlspecialchars($order['nama_akun_kas']) ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        </table>

        <!-- PRODUCT TABLE -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width:35px;">No</th>
                    <th>Nama Barang / Varian Snack</th>
                    <th class="text-center" style="width:70px;">Satuan</th>
                    <th class="text-center" style="width:60px;">Qty</th>
                    <th class="text-right" style="width:110px;">Harga Satuan</th>
                    <th class="text-right" style="width:90px;">Diskon</th>
                    <th class="text-right" style="width:120px;">Subtotal (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $idx => $it): ?>
                <tr>
                    <td class="text-center" style="color:#64748b;"><?= $idx + 1 ?></td>
                    <td>
                        <div class="font-bold"><?= htmlspecialchars($it['nama_item']) ?></div>
                        <div style="font-size:10.5px; color:#64748b;">SKU: <?= htmlspecialchars($it['kode_sku']) ?></div>
                    </td>
                    <td class="text-center"><?= htmlspecialchars($it['satuan_dasar'] ?: 'pcs') ?></td>
                    <td class="text-center font-bold font-mono"><?= number_format($it['kuantitas_satuan_dasar'], 0, ',', '.') ?></td>
                    <td class="text-right font-mono"><?= Format::rupiah($it['harga_satuan_deal']) ?></td>
                    <td class="text-right font-mono" style="color:#059669;">
                        <?= (float)$it['diskon_item_nominal'] > 0 ? '-' . Format::rupiah($it['diskon_item_nominal']) : '-' ?>
                    </td>
                    <td class="text-right font-bold font-mono"><?= Format::rupiah($it['subtotal']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- SUMMARY -->
        <table class="summary-grid-table" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <tr>
                <td style="width: 54%; vertical-align: top; padding-right: 18px;">
                    <div class="terbilang-box">
                        <div style="font-weight:700; color:#475569; margin-bottom:3px; font-size: 11px; text-transform: uppercase;">Terbilang:</div>
                        <div style="font-style:italic; font-weight:600; color:#0f172a; line-height: 1.4;">
                            "<?= Format::terbilang($order['total_netto']) ?>"
                        </div>
                        <?php if (!empty($order['catatan'])): ?>
                        <div style="margin-top:8px; font-size:11.5px; color:#64748b;">
                            <strong>Catatan:</strong> <?= htmlspecialchars($order['catatan']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($comp['nomor_rekening'])): ?>
                        <div style="margin-top:8px; font-size:11px; color:#334155; background:#f1f5f9; padding:6px 10px; border-radius:4px; border-left:3px solid #3b82f6;">
                            Pembayaran Transfer: <strong><?= htmlspecialchars($comp['nama_bank']) ?></strong> Rek: <strong style="font-family:monospace;"><?= htmlspecialchars($comp['nomor_rekening']) ?></strong> a.n <strong><?= htmlspecialchars($comp['atas_nama_bank']) ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($comp['catatan_faktur'])): ?>
                        <div style="margin-top:6px; font-size:10.5px; color:#64748b; font-style:italic;">
                            * <?= htmlspecialchars($comp['catatan_faktur']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </td>
                <td style="width: 46%; vertical-align: top;">
                    <table class="summary-table">
                        <tr>
                            <td style="color:#64748b;"><?= $isKonsinyasi ? 'Subtotal Valuasi:' : 'Subtotal Bruto:' ?></td>
                            <td class="text-right font-mono font-bold"><?= Format::rupiah($order['total_bruto']) ?></td>
                        </tr>
                        <?php if ((float)$order['total_diskon'] > 0): ?>
                        <tr>
                            <td style="color:#64748b;">Total Diskon:</td>
                            <td class="text-right font-mono" style="color:#059669;">-<?= Format::rupiah($order['total_diskon']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="total-row">
                            <td style="padding-top:8px;"><?= $isKonsinyasi ? 'TOTAL TITIP RAK:' : 'TOTAL NETTO:' ?></td>
                            <td class="text-right font-mono" style="padding-top:8px;"><?= Format::rupiah($order['total_netto']) ?></td>
                        </tr>
                        <?php if (!$isKonsinyasi && $order['status_pembayaran'] !== 'lunas'): ?>
                        <tr>
                            <td style="color:#dc2626; font-size:11.5px; padding-top:4px;">Sisa Tagihan Tempo:</td>
                            <td class="text-right font-mono font-bold" style="color:#dc2626; font-size:12px; padding-top:4px;">
                                <?= Format::rupiah(max(0, (float)$order['total_netto'] - (float)$order['total_dibayar'])) ?>
                            </td>
                        </tr>
                        <?php elseif ($isKonsinyasi): ?>
                        <tr>
                            <td style="color:#d97706; font-size:11px; padding-top:4px;" colspan="2" class="text-right">
                                <em>* Non-Tagihan Langsung (Omzet ditagih saat Opname Sales)</em>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </td>
            </tr>
        </table>

        <!-- SIGNATURES -->
        <table class="signature-grid-table" style="width: 100%; border-collapse: collapse; margin-top: 36px; text-align: center;">
            <tr>
                <td style="width: 32%; vertical-align: top;">
                    <div style="font-size: 11px; color: #475569; margin-bottom: 48px; font-weight: 700;">Tanda Terima Pelanggan,</div>
                    <div class="sign-box">( <?= htmlspecialchars($order['nama_pemilik'] ?: $order['nama_toko']) ?> )</div>
                    <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">Cap Toko &amp; Tanda Tangan</div>
                </td>
                <td style="width: 36%; vertical-align: top;">
                    <div style="font-size: 11px; color: #475569; margin-bottom: 48px; font-weight: 700;">Petugas Pengirim / Sales,</div>
                    <div class="sign-box">( <?= htmlspecialchars($order['nama_sales'] ?: 'Pengirim') ?> )</div>
                    <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">Sales Driver Distribusi</div>
                </td>
                <td style="width: 32%; vertical-align: top;">
                    <div style="font-size: 11px; color: #475569; margin-bottom: 48px; font-weight: 700;">Hormat Kami,</div>
                    <div class="sign-box">( Admin <?= htmlspecialchars($comp['nama']) ?> )</div>
                    <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">Bagian Keuangan &amp; Kasir</div>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <?php if (empty($isPdf) || ($formatMode ?? '') === 'dotmatrix'): ?>
    <!-- FORMAT KHUSUS PRINTER DOT MATRIX (CONTINUOUS FORM FULL FOLIO / LETTER 9.5" x 11") -->
    <div id="invoice-dotmatrix" class="continuous-wrapper">
        <div class="tractor-strip tractor-left"></div>
        <div class="continuous-inner">
            <!-- KOP RESMI PERUSAHAAN & HEADER FAKTUR -->
            <table class="dm-table">
                <tr>
                    <td style="width: 55%; vertical-align: top;">
                        <div class="dm-brand"><?= htmlspecialchars($comp['nama']) ?></div>
                        <div class="dm-sub"><?= htmlspecialchars($comp['tagline']) ?></div>
                        <div class="dm-text-muted"><?= htmlspecialchars($comp['alamat']) ?></div>
                        <div class="dm-text-muted">Telp/WA: <?= htmlspecialchars($comp['telepon']) ?><?= !empty($comp['email']) ? ' &bull; Email: ' . htmlspecialchars($comp['email']) : '' ?><?= !empty($comp['website']) ? ' &bull; Web: ' . htmlspecialchars($comp['website']) : '' ?></div>
                    </td>
                    <td style="width: 45%; vertical-align: top; text-align: right;">
                        <div class="dm-title"><?= $isKonsinyasi ? 'BUKTI TITIP BARANG' : 'FAKTUR PENJUALAN' ?></div>
                        <table class="dm-meta-table">
                            <tr>
                                <td class="dm-meta-lbl">No. Faktur</td>
                                <td class="dm-meta-sep">:</td>
                                <td class="dm-meta-val"><strong><?= htmlspecialchars($order['nomor_nota']) ?></strong></td>
                            </tr>
                            <tr>
                                <td class="dm-meta-lbl">Tanggal</td>
                                <td class="dm-meta-sep">:</td>
                                <td class="dm-meta-val"><?= date('d/m/Y', strtotime($order['tanggal_pesanan'])) ?></td>
                            </tr>
                            <tr>
                                <td class="dm-meta-lbl">Petugas Sales</td>
                                <td class="dm-meta-sep">:</td>
                                <td class="dm-meta-val"><?= htmlspecialchars($order['nama_sales'] ?: 'Petugas Toko') ?></td>
                            </tr>
                            <tr>
                                <td class="dm-meta-lbl">Status Bayar</td>
                                <td class="dm-meta-sep">:</td>
                                <td class="dm-meta-val"><?= $isKonsinyasi ? '[ KONSINYASI ]' : ($order['status_pembayaran'] === 'lunas' ? '[ LUNAS ]' : '[ TEMPO ]') ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="dm-divider-double"></div>

            <!-- TUJUAN PELANGGAN & DETAIL PEMBAYARAN -->
            <table class="dm-table">
                <tr>
                    <td style="width: 52%; vertical-align: top; padding-right: 12px;">
                        <div class="dm-section-title">KEPADA TOKO PELANGGAN:</div>
                        <table class="dm-subtable">
                            <tr>
                                <td class="dm-lbl">Nama Toko</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><strong><?= htmlspecialchars($order['nama_toko']) ?></strong> (<?= htmlspecialchars($order['kode_pelanggan']) ?>)</td>
                            </tr>
                            <tr>
                                <td class="dm-lbl">Pemilik / PIC</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><?= htmlspecialchars($order['nama_pemilik'] ?: '-') ?></td>
                            </tr>
                            <tr>
                                <td class="dm-lbl">Alamat Toko</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><?= htmlspecialchars($order['alamat_lengkap'] ?: '-') ?></td>
                            </tr>
                            <tr>
                                <td class="dm-lbl">No. Telp / WA</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><?= htmlspecialchars($order['nomor_whatsapp'] ?: '-') ?></td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 48%; vertical-align: top; border-left: 1px dashed #000000; padding-left: 14px;">
                        <div class="dm-section-title"><?= $isKonsinyasi ? 'KETENTUAN KONSINYASI:' : 'SKEMA PEMBAYARAN:' ?></div>
                        <table class="dm-subtable">
                            <?php if ($isKonsinyasi): ?>
                            <tr>
                                <td class="dm-lbl">Skema Titip</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><strong>TITIP JUAL (KONSINYASI)</strong></td>
                            </tr>
                            <tr>
                                <td class="dm-lbl">Sistem Tagih</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val">Ditagih saat Opname Sales</td>
                            </tr>
                            <tr>
                                <td class="dm-lbl">Sales Motoris</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><?= htmlspecialchars($order['nama_sales'] ?: 'Sales Motoris') ?></td>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <td class="dm-lbl">Tipe Bayar</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><strong><?= strtoupper(str_replace('_', ' ', $order['tipe_pembayaran'])) ?></strong></td>
                            </tr>
                            <tr>
                                <td class="dm-lbl">Status Bayar</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><strong><?= strtoupper($order['status_pembayaran']) ?></strong></td>
                            </tr>
                            <?php if (!empty($order['tanggal_jatuh_tempo'])): ?>
                            <tr>
                                <td class="dm-lbl">Jatuh Tempo</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><strong><?= date('d/m/Y', strtotime($order['tanggal_jatuh_tempo'])) ?></strong></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($order['nama_akun_kas'])): ?>
                            <tr>
                                <td class="dm-lbl">Rekening/Kas</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val"><?= htmlspecialchars($order['nama_akun_kas']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php endif; ?>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="dm-divider-single"></div>

            <!-- TABEL DAFTAR BARANG (80 KOLOM COMPATIBLE) -->
            <table class="dm-table dm-items-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 4%; text-align: center;">NO</th>
                        <th style="width: 14%; text-align: left;">KODE SKU</th>
                        <th style="text-align: left;">NAMA BARANG / PRODUK</th>
                        <th style="width: 7%; text-align: right;">QTY</th>
                        <th style="width: 7%; text-align: center;">SAT</th>
                        <th style="width: 15%; text-align: right;">HARGA (Rp)</th>
                        <th style="width: 10%; text-align: right;">DISKON</th>
                        <th style="width: 15%; text-align: right;">TOTAL (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $totalQty = 0;
                    foreach ($items as $it): 
                        $qtyItem = (int)($it['kuantitas_satuan_dasar'] ?? 0);
                        $totalQty += $qtyItem;
                        $hargaItem = (float)($it['harga_satuan_deal'] ?? $it['harga_satuan'] ?? $it['harga_satuan_dasar'] ?? 0);
                        $diskonItem = (float)($it['diskon_item_nominal'] ?? $it['diskon_nominal'] ?? 0);
                        $subtotalItem = (float)($it['subtotal'] ?? ($hargaItem * $qtyItem - $diskonItem));
                    ?>
                    <tr>
                        <td style="text-align: center;"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($it['kode_sku'] ?? '-') ?></td>
                        <td><strong><?= htmlspecialchars($it['nama_item'] ?? '-') ?></strong></td>
                        <td style="text-align: right;"><strong><?= number_format($qtyItem, 0, ',', '.') ?></strong></td>
                        <td style="text-align: center;"><?= htmlspecialchars($it['satuan_dasar'] ?: 'Pcs') ?></td>
                        <td style="text-align: right;"><?= number_format($hargaItem, 0, ',', '.') ?></td>
                        <td style="text-align: right;"><?= $diskonItem > 0 ? '-' . number_format($diskonItem, 0, ',', '.') : '-' ?></td>
                        <td style="text-align: right;"><strong><?= number_format($subtotalItem, 0, ',', '.') ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL ITEM / PRODUK:</td>
                        <td style="text-align: right; font-weight: bold;"><?= number_format($totalQty, 0, ',', '.') ?></td>
                        <td style="text-align: center; font-weight: bold;">Pcs</td>
                        <td colspan="2" style="text-align: right; font-weight: bold;">SUBTOTAL BRUTO:</td>
                        <td style="text-align: right; font-weight: bold;"><?= number_format((float)($order['total_bruto'] ?? 0), 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>

            <div class="dm-divider-single"></div>

            <!-- TERBILANG & RINGKASAN PEMBAYARAN -->
            <table class="dm-table" style="margin-bottom: 8px;">
                <tr>
                    <td style="vertical-align: top; width: 55%; font-size: 9pt; padding-right: 14px;">
                        <strong>Terbilang:</strong><br>
                        <em># <?= Format::terbilang((float)$order['total_netto'], true) ?> #</em><br><br>
                        <?php if (!empty($order['catatan'])): ?>
                        <strong>Catatan:</strong> <?= htmlspecialchars($order['catatan']) ?><br>
                        <?php endif; ?>
                        <div style="font-size: 8.5pt; color: #333; margin-top: 4px;">
                            * Pembayaran sah apabila disertai kuitansi resmi atau transfer ke rekening resmi.<br>
                            * Dokumen ERP Keren Snack &bull; Cetak: <?= date('d/m/Y H:i:s') ?>
                        </div>
                    </td>
                    <td style="vertical-align: top; width: 45%;">
                        <table class="dm-subtable" style="width: 100%;">
                            <tr>
                                <td class="dm-lbl" style="width: 140px;">Subtotal Bruto</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val" style="text-align: right;"><?= Format::rupiah((float)$order['total_bruto']) ?></td>
                            </tr>
                            <?php if ((float)$order['total_diskon'] > 0): ?>
                            <tr>
                                <td class="dm-lbl" style="width: 140px;">Total Diskon</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val" style="text-align: right; color: #000;">-<?= Format::rupiah((float)$order['total_diskon']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr style="font-weight: bold; font-size: 10pt;">
                                <td class="dm-lbl" style="width: 140px; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 3px 0;"><?= $isKonsinyasi ? 'TOTAL TITIP RAK' : 'TOTAL NETTO' ?></td>
                                <td class="dm-sep" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 3px 0;">:</td>
                                <td class="dm-val" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 3px 0; text-align: right;"><?= Format::rupiah((float)$order['total_netto']) ?></td>
                            </tr>
                            <?php if (!$isKonsinyasi): ?>
                            <tr>
                                <td class="dm-lbl" style="width: 140px;">Total Dibayar</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val" style="text-align: right;"><?= Format::rupiah((float)$order['total_dibayar']) ?></td>
                            </tr>
                            <?php if ((float)$order['sisa_tagihan'] > 0): ?>
                            <tr style="font-weight: bold;">
                                <td class="dm-lbl" style="width: 140px;">SISA TAGIHAN</td>
                                <td class="dm-sep">:</td>
                                <td class="dm-val" style="text-align: right;"><strong><?= Format::rupiah((float)$order['sisa_tagihan']) ?></strong></td>
                            </tr>
                            <?php endif; ?>
                            <?php endif; ?>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- TANDA TANGAN 3 PIHAK -->
            <table class="dm-table dm-sig-table">
                <tr>
                    <td style="width: 33.3%; text-align: center;">
                        <div class="dm-sig-title">Penerima / Toko Pelanggan,</div>
                        <div class="dm-sig-space"></div>
                        <div class="dm-sig-line">( <?= htmlspecialchars($order['nama_pemilik'] ?: $order['nama_toko']) ?> )</div>
                        <div class="dm-sig-sub">Cap Toko &amp; Tanda Tangan</div>
                    </td>
                    <td style="width: 33.3%; text-align: center;">
                        <div class="dm-sig-title">Petugas Pengirim / Sales,</div>
                        <div class="dm-sig-space"></div>
                        <div class="dm-sig-line">( <?= htmlspecialchars($order['nama_sales'] ?: 'Petugas ERP') ?> )</div>
                        <div class="dm-sig-sub">Sales Driver Distribusi</div>
                    </td>
                    <td style="width: 33.3%; text-align: center;">
                        <div class="dm-sig-title">Hormat Kami,</div>
                        <div class="dm-sig-space"></div>
                        <div class="dm-sig-line">( Bagian Keuangan )</div>
                        <div class="dm-sig-sub">Admin <?= htmlspecialchars($comp['nama']) ?></div>
                    </td>
                </tr>
            </table>

            <div class="dm-divider-double" style="margin-top: 10px;"></div>

            <!-- FOOTER COPY INDIKATOR RANGKAP NCR -->
            <div class="dm-ncr-footer">
                <span>[ ] Lembar 1 (Putih): Kasir / Accounting</span> &nbsp;&bull;&nbsp;
                <span>[ ] Lembar 2 (Merah): Toko Mitra</span> &nbsp;&bull;&nbsp;
                <span>[ ] Lembar 3 (Kuning): Sales Driver</span>
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

        const pageStyle = document.getElementById('dynamic-page-style');
        if (pageStyle) {
            if (mode === 'dotmatrix') {
                pageStyle.innerHTML = '@page { size: letter portrait; margin: 6mm 10mm 6mm 10mm; }';
            } else {
                pageStyle.innerHTML = '@page { size: A4 portrait; margin: 15mm 18mm 15mm 18mm; }';
            }
        }

        const pdfBtn = document.getElementById('btn-pdf-download');
        if (pdfBtn) {
            try {
                const pdfUrl = new URL(pdfBtn.href, window.location.origin);
                if (mode === 'dotmatrix') {
                    pdfUrl.searchParams.set('format', 'dotmatrix');
                } else {
                    pdfUrl.searchParams.delete('format');
                }
                pdfBtn.href = pdfUrl.pathname + pdfUrl.search;
            } catch (e) {}
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

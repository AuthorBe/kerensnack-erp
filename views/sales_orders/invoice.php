<?php
use App\Core\Router;
use App\Helpers\Format;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur Penjualan - <?= htmlspecialchars($order['nomor_nota']) ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= Router::asset('/favicon/favicon.ico') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= Router::asset('/favicon/favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= Router::asset('/favicon/favicon-96x96.png') ?>">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 24px;
            display: flex;
            justify-content: center;
        }

        .invoice-container {
            background: #ffffff;
            width: 100%;
            max-width: 800px;
            padding: 32px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

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

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                max-width: 100%;
                padding: 0;
            }
            .action-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <?php if (empty($isPdf)): ?>
    <!-- Floating Action Buttons -->
    <div class="action-bar">
        <a href="<?= Router::url('/sales-orders') ?>" class="btn btn-back">
            <span>&larr; Kembali</span>
        </a>
        <a href="<?= Router::url('/customer-orders/invoice/pdf?id=' . $order['id']) ?>" class="btn btn-pdf">
            <span>📄 Unduh PDF</span>
        </a>
        <a href="<?= Router::url('/customer-orders/invoice/excel?id=' . $order['id']) ?>" class="btn btn-excel">
            <span>📊 Unduh Excel</span>
        </a>
    </div>
    <?php endif; ?>

    <div class="invoice-container">
        <!-- HEADER -->
        <table class="header-table">
            <tr>
                <td style="vertical-align:top; width:60%;">
                    <div class="company-name">KEREN SNACK INDONESIA</div>
                    <div class="company-sub">
                        Produsen &amp; Distributor Aneka Makanan Ringan Berkualitas<br>
                        Telp / WhatsApp: 0812-3456-7890 • Email: admin@kerensnack.com<br>
                        Jawa Barat, Indonesia
                    </div>
                </td>
                <td style="vertical-align:top; width:40%;">
                    <div class="invoice-title">FAKTUR PENJUALAN</div>
                    <div class="invoice-no"><?= htmlspecialchars($order['nomor_nota']) ?></div>
                    <div style="text-align:right; font-size:11.5px; color:#64748b; margin-top:4px;">
                        Tanggal: <strong><?= date('d/m/Y', strtotime($order['tanggal_pesanan'])) ?></strong>
                    </div>
                </td>
            </tr>
        </table>

        <!-- METADATA -->
        <div class="meta-grid">
            <div class="meta-box">
                <div class="meta-label">Kepada Toko Pelanggan:</div>
                <div class="meta-value" style="font-size:14px;"><?= htmlspecialchars($order['nama_toko']) ?></div>
                <div style="color:#64748b; margin-top:3px;">
                    Kode: <strong><?= htmlspecialchars($order['kode_pelanggan']) ?></strong> 
                    <?php if (!empty($order['nama_pemilik'])): ?>
                    • PIC: <?= htmlspecialchars($order['nama_pemilik']) ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($order['alamat_lengkap'])): ?>
                <div style="color:#64748b; margin-top:3px; font-size:11.5px;"><?= htmlspecialchars($order['alamat_lengkap']) ?></div>
                <?php endif; ?>
            </div>

            <div class="meta-box">
                <div class="meta-label">Rincian Pengiriman &amp; Pembayaran:</div>
                <div>Sales / Driver: <strong><?= htmlspecialchars($order['nama_sales'] ?: 'Driver Toko') ?></strong></div>
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
            </div>
        </div>

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
        <div class="summary-grid">
            <div class="terbilang-box">
                <div style="font-weight:700; color:#475569; margin-bottom:3px;">Terbilang:</div>
                <div style="font-style:italic; font-weight:600; color:#0f172a;">
                    "<?= Format::terbilang($order['total_netto']) ?>"
                </div>
                <?php if (!empty($order['catatan'])): ?>
                <div style="margin-top:8px; font-size:11.5px; color:#64748b;">
                    Catatan: <?= htmlspecialchars($order['catatan']) ?>
                </div>
                <?php endif; ?>
            </div>

            <div>
                <table class="summary-table">
                    <tr>
                        <td style="color:#64748b;">Subtotal Bruto:</td>
                        <td class="text-right font-mono font-bold"><?= Format::rupiah($order['total_bruto']) ?></td>
                    </tr>
                    <?php if ((float)$order['total_diskon'] > 0): ?>
                    <tr>
                        <td style="color:#64748b;">Total Diskon:</td>
                        <td class="text-right font-mono" style="color:#059669;">-<?= Format::rupiah($order['total_diskon']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td style="padding-top:8px;">TOTAL NETTO:</td>
                        <td class="text-right font-mono" style="padding-top:8px;"><?= Format::rupiah($order['total_netto']) ?></td>
                    </tr>
                    <?php if ($order['status_pembayaran'] !== 'lunas'): ?>
                    <tr>
                        <td style="color:#dc2626; font-size:11.5px; padding-top:4px;">Sisa Tagihan Tempo:</td>
                        <td class="text-right font-mono font-bold" style="color:#dc2626; font-size:12px; padding-top:4px;">
                            <?= Format::rupiah(max(0, (float)$order['total_netto'] - (float)$order['total_dibayar'])) ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- SIGNATURES -->
        <div class="signature-grid">
            <div>
                <div class="meta-label">Penerima / Toko:</div>
                <div class="sign-box">( <?= htmlspecialchars($order['nama_pemilik'] ?: $order['nama_toko']) ?> )</div>
            </div>
            <div>
                <div class="meta-label">Driver / Pengirim:</div>
                <div class="sign-box">( <?= htmlspecialchars($order['nama_sales'] ?: 'Pengirim') ?> )</div>
            </div>
            <div>
                <div class="meta-label">Hormat Kami:</div>
                <div class="sign-box">( Admin KEREN SNACK )</div>
            </div>
        </div>
    </div>

</body>
</html>

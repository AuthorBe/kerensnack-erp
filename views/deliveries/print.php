<?php
use App\Core\Router;
use App\Helpers\Format;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - <?= htmlspecialchars($delivery['nomor_surat_jalan']) ?></title>
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

        .document-container {
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
            font-size: 22px;
            font-weight: 900;
            color: #0284c7;
            letter-spacing: -0.5px;
        }

        .company-sub {
            font-size: 11.5px;
            color: #64748b;
            line-height: 1.4;
            margin-top: 4px;
        }

        .document-title {
            text-align: right;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .document-no {
            text-align: right;
            font-family: monospace;
            font-size: 14px;
            font-weight: 700;
            color: #0284c7;
            margin-top: 4px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
            background-color: #f8fafc;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .info-block h4 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
        }

        .info-block p {
            font-size: 12.5px;
            line-height: 1.5;
            color: #1e293b;
        }

        .info-block strong {
            color: #0f172a;
            font-size: 13.5px;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        table.items-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }

        table.items-table td {
            padding: 10px 12px;
            font-size: 12.5px;
            border: 1px solid #cbd5e1;
            color: #1e293b;
        }

        .cell-center { text-align: center; }
        .cell-right { text-align: right; }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 36px;
            text-align: center;
        }

        .sig-box {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 110px;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 8px;
        }

        .sig-title {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
        }

        .sig-name {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            border-top: 1px solid #0f172a;
            padding-top: 4px;
        }

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

        .btn-print { background: #0284c7; color: white; }
        .btn-back { background: #334155; color: white; }

        @media print {
            body { background: transparent; padding: 0; }
            .document-container { box-shadow: none; padding: 0; max-width: 100%; border: none; }
            .actions-bar { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="document-container">
        <!-- HEADER -->
        <table class="header-table">
            <tr>
                <td style="vertical-align: top;">
                    <div class="company-name">KEREN SNACK LOGISTIK</div>
                    <div class="company-sub">
                        Produsen &amp; Distribusi Makanan Ringan Berkualitas<br>
                        Depot &amp; Gudang: Tangerang Selatan - Banten<br>
                        Hotline / WA: 0812-8888-9999
                    </div>
                </td>
                <td style="vertical-align: top; width: 45%;">
                    <div class="document-title">SURAT JALAN PENGIRIMAN</div>
                    <div class="document-no"><?= htmlspecialchars($delivery['nomor_surat_jalan']) ?></div>
                    <div style="text-align:right;font-size:11.5px;color:#64748b;margin-top:4px;">
                        No. Faktur: <strong><?= htmlspecialchars($delivery['nomor_nota']) ?></strong><br>
                        Tanggal: <strong><?= date('d/m/Y', strtotime($delivery['dibuat_pada'])) ?></strong>
                    </div>
                </td>
            </tr>
        </table>

        <!-- INFO GRID -->
        <div class="info-grid">
            <div class="info-block">
                <h4>Toko Tujuan / Penerima</h4>
                <p>
                    <strong><?= htmlspecialchars($delivery['nama_toko']) ?></strong><br>
                    <?= nl2br(htmlspecialchars($delivery['alamat_toko'] ?? '-')) ?><br>
                    <?php if (!empty($delivery['nomor_whatsapp'])): ?>
                    Telp / WA: <?= htmlspecialchars($delivery['nomor_whatsapp']) ?><br>
                    <?php endif; ?>
                    Penerima: <?= htmlspecialchars($delivery['nama_pemilik'] ?: 'Pemilik / Penanggung Jawab Toko') ?>
                </p>
            </div>
            <div class="info-block">
                <h4>Informasi Pengiriman &amp; Rute</h4>
                <p>
                    Sales / Driver: <strong><?= htmlspecialchars($delivery['nama_driver'] ?? 'Armada Pengiriman') ?></strong><br>
                    <?php if (!empty($delivery['nopol_driver'])): ?>
                    Plat Kendaraan: <strong style="font-family:monospace;color:#0284c7;">🚚 <?= htmlspecialchars($delivery['nopol_driver']) ?></strong><br>
                    <?php endif; ?>
                    Telp Driver: <?= htmlspecialchars($delivery['telp_driver'] ?? '-') ?><br>
                    Wilayah / Rute: <strong><?= htmlspecialchars($delivery['nama_wilayah'] ?? '-') ?> <?= !empty($delivery['kode_rute']) ? '('.htmlspecialchars($delivery['kode_rute']).')' : '' ?></strong><br>
                    Status: <strong style="color:#0284c7;text-transform:capitalize;"><?= htmlspecialchars(str_replace('_', ' ', $delivery['status_surat_jalan'])) ?></strong>
                </p>
            </div>
        </div>

        <!-- ITEMS TABLE -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="cell-center" style="width: 40px;">No</th>
                    <th style="width: 100px;">Kode SKU</th>
                    <th>Nama Produk Snack Siap Jual</th>
                    <th class="cell-center" style="width: 110px;">Qty Kirim</th>
                    <th class="cell-center" style="width: 80px;">Satuan</th>
                    <th class="cell-center" style="width: 130px;">Kondisi Barang</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $totalQty = 0;
                foreach ($items as $it): 
                    $qty = (int)$it['kuantitas_satuan_dasar'];
                    $totalQty += $qty;
                ?>
                <tr>
                    <td class="cell-center"><?= $no++ ?></td>
                    <td style="font-family: monospace; font-weight: 700;"><?= htmlspecialchars($it['kode_sku']) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($it['nama_item']) ?></strong>
                        <?php if (!empty($it['varian_rasa'])): ?>
                        <span style="font-size: 11px; color: #64748b;">(<?= htmlspecialchars($it['varian_rasa']) ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td class="cell-center font-mono" style="font-size: 14px; font-weight: 800;"><?= number_format($qty, 0, ',', '.') ?></td>
                    <td class="cell-center">Bungkus</td>
                    <td class="cell-center" style="color: #059669; font-weight: 600; font-size: 11.5px;">✓ Baik &amp; Segel</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f8fafc; font-weight: 800;">
                    <td colspan="3" style="text-align: right; text-transform: uppercase;">Total Kuantitas Muatan:</td>
                    <td class="cell-center font-mono" style="font-size: 15px; color: #0284c7;"><?= number_format($totalQty, 0, ',', '.') ?></td>
                    <td class="cell-center">Bungkus</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <!-- CATATAN -->
        <?php if (!empty($delivery['catatan_pesanan'])): ?>
        <div style="margin-bottom: 20px; font-size: 12px; background: #fffbeb; border: 1px solid #fde68a; padding: 8px 12px; border-radius: 4px; color: #92400e;">
            <strong>Catatan Pengiriman:</strong> <?= htmlspecialchars($delivery['catatan_pesanan']) ?>
        </div>
        <?php endif; ?>

        <!-- SIGNATURES -->
        <div class="signatures">
            <div class="sig-box">
                <div class="sig-title">Disiapkan Oleh (Gudang)</div>
                <div class="sig-name">( Petugas Gudang )</div>
            </div>
            <div class="sig-box">
                <div class="sig-title">Diserahkan Oleh (Driver)</div>
                <div class="sig-name"><?= htmlspecialchars($delivery['nama_driver'] ?? 'Sales Driver') ?></div>
            </div>
            <div class="sig-box">
                <div class="sig-title">Diterima Oleh (Toko)</div>
                <div class="sig-name"><?= htmlspecialchars($delivery['nama_penerima_toko'] ?: '( Cap &amp; Tanda Tangan Toko )') ?></div>
            </div>
        </div>

        <div style="margin-top: 24px; font-size: 10.5px; color: #94a3b8; text-align: center; border-top: 1px dashed #cbd5e1; padding-top: 8px;">
            * Harap periksa jumlah dan kemasan barang saat diterima. Komplain atau retur barang harus dilaporkan maksimal 1x24 jam setelah serah terima.
        </div>
    </div>

    <!-- ACTIONS BAR -->
    <div class="actions-bar">
        <button onclick="window.print()" class="btn-action btn-print">
            🖨️ Cetak Surat Jalan
        </button>
        <a href="<?= Router::url('/deliveries') ?>" class="btn-action btn-back">
            ← Kembali ke Logistik
        </a>
    </div>

</body>
</html>

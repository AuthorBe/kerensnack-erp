<?php
use App\Core\Router;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Daftar Item PO') ?> - <?= htmlspecialchars($order['nomor_nota'] ?? '') ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
        }
        body {
            background: #f8fafc;
            color: #0f172a;
            padding: 24px;
            font-size: 13px;
        }
        .sheet {
            background: #ffffff;
            max-width: 800px;
            margin: 0 auto;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .brand-title {
            font-size: 20px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .doc-title {
            font-size: 13px;
            font-weight: 800;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 3px;
        }
        .meta-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .meta-label {
            font-size: 10.5px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }
        .store-name {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        .store-sub {
            font-size: 12px;
            color: #475569;
            margin-top: 3px;
            line-height: 1.4;
        }
        .po-number {
            font-family: monospace;
            font-size: 15px;
            font-weight: 800;
            color: #2563eb;
        }
        .po-date {
            font-size: 11.5px;
            color: #64748b;
            margin-top: 3px;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items-table th {
            background: #f1f5f9;
            color: #1e293b;
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.items-table td {
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            font-size: 12.5px;
            color: #0f172a;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .font-bold { font-weight: 700; }

        .note-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 12px;
            color: #92400e;
            margin-top: 10px;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .sheet {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<?php if (empty($isPdf)): ?>
<div class="no-print" style="max-width: 800px; margin: 0 auto 16px; display: flex; justify-content: space-between; align-items: center;">
    <a href="javascript:window.close()" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 13px;">&larr; Tutup</a>
    <a href="<?= Router::url('/customer-orders/picking-list/pdf?id=' . $order['id']) ?>" style="background: #dc2626; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
        📄 Unduh PDF
    </a>
</div>
<?php endif; ?>

<div class="sheet">
    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td style="border: none; padding: 0; vertical-align: top;">
                <div class="brand-title">KEREN SNACK INDONESIA</div>
                <div class="doc-title">DAFTAR ITEM PESANAN (PO)</div>
            </td>
            <td style="border: none; padding: 0; vertical-align: top; text-align: right;">
                <div class="po-number"><?= htmlspecialchars($order['nomor_nota']) ?></div>
                <div class="po-date">Tgl Pesanan: <?= date('d/m/Y', strtotime($order['tanggal_pesanan'] ?? $order['dibuat_pada'])) ?></div>
            </td>
        </tr>
    </table>

    <!-- INFO TOKO -->
    <div class="meta-card">
        <div class="meta-label">Toko Pelanggan / Mitra:</div>
        <div class="store-name"><?= htmlspecialchars($order['nama_toko']) ?></div>
        <div class="store-sub">
            Kode Toko: <strong><?= htmlspecialchars($order['kode_pelanggan']) ?></strong>
            <?php if (!empty($order['nama_pemilik'])): ?>
            • PIC: <?= htmlspecialchars($order['nama_pemilik']) ?>
            <?php endif; ?>
            <?php if (!empty($order['alamat_lengkap'])): ?>
            <br><?= htmlspecialchars($order['alamat_lengkap']) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- TABEL DAFTAR BARANG -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40px;" class="text-center">No</th>
                <th style="width: 130px;" class="text-center">Kode SKU</th>
                <th>Nama Barang / Produk Snack</th>
                <th style="width: 140px;" class="text-center">Jumlah Pesanan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $totalPcs = 0;
            foreach ($items as $it): 
                $qty = (int)$it['kuantitas_satuan_dasar'];
                $totalPcs += $qty;
            ?>
            <tr>
                <td class="text-center" style="color: #64748b; font-weight: 600;"><?= $no++ ?></td>
                <td class="text-center font-mono font-bold" style="color: #475569;"><?= htmlspecialchars($it['kode_sku'] ?? '-') ?></td>
                <td>
                    <div style="font-weight: 700; color: #0f172a; font-size: 13px;"><?= htmlspecialchars($it['nama_item']) ?></div>
                    <?php if (!empty($it['barcode'])): ?>
                    <div style="font-size: 10.5px; color: #64748b; font-family: monospace;">Barcode: <?= htmlspecialchars($it['barcode']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-center font-bold" style="font-size: 13.5px; color: #1e3a8a;">
                    <?= number_format($qty, 0, ',', '.') ?> <?= htmlspecialchars($it['satuan_dasar'] ?: 'pcs') ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr style="background: #f8fafc; font-weight: 800;">
                <td colspan="3" class="text-right" style="text-transform: uppercase; font-size: 12px; color: #475569; padding: 12px 14px;">
                    Total Kuantitas Barang:
                </td>
                <td class="text-center" style="font-size: 14px; color: #0f172a; padding: 12px 14px;">
                    <?= number_format($totalPcs, 0, ',', '.') ?> Pcs
                </td>
            </tr>
        </tbody>
    </table>

    <!-- CATATAN PESANAN JIKA ADA -->
    <?php if (!empty($order['catatan'])): ?>
    <div class="note-box">
        <strong>Catatan:</strong> <?= htmlspecialchars($order['catatan']) ?>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
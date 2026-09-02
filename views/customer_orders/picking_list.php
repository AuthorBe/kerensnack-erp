<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Lembar Ambil Barang (Picking List)') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
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
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .brand-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .doc-title {
            font-size: 14px;
            font-weight: 700;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 24px;
        }
        .meta-item {
            font-size: 12px;
        }
        .meta-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .meta-val {
            font-weight: 700;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        th {
            background: #f1f5f9;
            color: #334155;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        td {
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            font-size: 12px;
        }
        .chk-box {
            width: 18px;
            height: 18px;
            border: 2px solid #475569;
            border-radius: 4px;
            margin: 0 auto;
        }
        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
        }
        .sig-box {
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
        }
        .sig-space {
            height: 60px;
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

<div class="no-print" style="max-width: 800px; margin: 0 auto 16px; display: flex; justify-content: space-between; align-items: center;">
    <a href="javascript:window.close()" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 13px;">&larr; Tutup</a>
    <button onclick="window.print()" style="background: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 700; cursor: pointer;">
        🖨️ Cetak Lembar Ambil (Picking List)
    </button>
</div>

<div class="sheet">
    <div class="header">
        <div>
            <div class="brand-title">KEREN SNACK INDONESIA</div>
            <div class="doc-title">LEMBAR AMBIL BARANG / PICKING LIST GUDANG</div>
        </div>
        <div style="text-align: right;">
            <div style="font-family: 'JetBrains Mono', monospace; font-size: 16px; font-weight: 800; color: #0f172a;">
                <?= htmlspecialchars($order['nomor_nota']) ?>
            </div>
            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                Tgl Pesanan: <?= date('d/m/Y H:i', strtotime($order['dibuat_pada'])) ?>
            </div>
        </div>
    </div>

    <div class="meta-grid">
        <div class="meta-item">
            <div class="meta-label">Toko Pelanggan / Mitra</div>
            <div class="meta-val"><?= htmlspecialchars($order['nama_toko']) ?> (<?= htmlspecialchars($order['kode_pelanggan']) ?>)</div>
            <div style="font-size: 11px; color: #64748b; margin-top: 2px;"><?= htmlspecialchars($order['alamat_lengkap'] ?: '-') ?></div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Wilayah &amp; Driver Armada</div>
            <div class="meta-val"><?= htmlspecialchars($order['nama_wilayah'] ?: 'Wilayah Pusat') ?></div>
            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                Driver: <?= htmlspecialchars($order['nama_driver'] ?: 'Belum ditentukan') ?>
                <?= !empty($order['nopol_driver']) ? ' (' . htmlspecialchars($order['nopol_driver']) . ')' : '' ?>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px; text-align: center;">No</th>
                <th style="width: 100px;">Kode SKU</th>
                <th>Nama Produk Snack</th>
                <th style="width: 110px; text-align: center;">Jumlah Diminta</th>
                <th style="width: 110px; text-align: center;">Stok Gudang</th>
                <th style="width: 40px; text-align: center;">Cek</th>
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
                <td style="text-align: center; font-weight: 600;"><?= $no++ ?></td>
                <td style="font-family: 'JetBrains Mono', monospace; font-size: 11px; font-weight: 600;"><?= htmlspecialchars($it['kode_sku'] ?? '') ?></td>
                <td>
                    <div style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($it['nama_item']) ?></div>
                    <?php if (!empty($it['barcode'])): ?>
                    <div style="font-size: 10px; color: #64748b; font-family: monospace;">Barcode: <?= htmlspecialchars($it['barcode']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="text-align: center; font-weight: 800; font-size: 13px; color: #2563eb;">
                    <?= $qty ?> <?= htmlspecialchars($it['satuan_dasar'] ?: 'Pcs') ?>
                </td>
                <td style="text-align: center; font-weight: 600; color: #475569;">
                    <?= (float)$it['stok_fisik_saat_ini'] ?> <?= htmlspecialchars($it['satuan_dasar'] ?: 'Pcs') ?>
                </td>
                <td style="text-align: center;">
                    <div class="chk-box"></div>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr style="background: #f8fafc; font-weight: 800;">
                <td colspan="3" style="text-align: right; text-transform: uppercase;">Total Kuantitas Diambil:</td>
                <td style="text-align: center; font-size: 14px; color: #0f172a;"><?= $totalPcs ?> Pcs</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <div class="signature-grid">
        <div>
            <div class="sig-space"></div>
            <div class="sig-box">( Petugas Gudang / Picker )</div>
        </div>
        <div>
            <div class="sig-space"></div>
            <div class="sig-box">( Pemeriksa / Checker )</div>
        </div>
        <div>
            <div class="sig-space"></div>
            <div class="sig-box">( Driver / Pembawa Barang )</div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    // Optional auto-print trigger
};
</script>
</body>
</html>
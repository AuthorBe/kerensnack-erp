<?php
use App\Core\Router;

$grandTotalPcs = 0;
$masterItems = [];
foreach ($orders as $o) {
    foreach (($o['items'] ?? []) as $it) {
        $sku = $it['kode_sku'] ?: $it['nama_item'];
        $qty = (int)$it['kuantitas_satuan_dasar'];
        $grandTotalPcs += $qty;

        if (!isset($masterItems[$sku])) {
            $masterItems[$sku] = [
                'kode_sku' => $it['kode_sku'] ?? '-',
                'nama_item' => $it['nama_item'],
                'satuan' => $it['satuan_dasar'] ?: 'pcs',
                'total_qty' => 0
            ];
        }
        $masterItems[$sku]['total_qty'] += $qty;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Rekapitulasi PO Gudang') ?></title>
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
            padding: 16px;
            font-size: 11.5px;
        }
        .container {
            background: #ffffff;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px 24px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .brand-title {
            font-size: 17px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.3px;
        }
        .doc-title {
            font-size: 11.5px;
            font-weight: 800;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }
        .section-header {
            background: #f1f5f9;
            border-left: 4px solid #2563eb;
            padding: 5px 8px;
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            color: #1e293b;
            letter-spacing: 0.04em;
            margin-top: 14px;
            margin-bottom: 6px;
        }
        .section-header.green {
            border-left-color: #059669;
        }
        .store-box {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .store-title-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .store-name {
            font-size: 12.5px;
            font-weight: 800;
            color: #0f172a;
        }
        .store-meta {
            font-size: 10.5px;
            color: #64748b;
        }
        .po-badge {
            font-family: monospace;
            font-weight: 800;
            color: #2563eb;
            font-size: 11.5px;
            text-align: right;
        }
        table.compact-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        table.compact-table th {
            background: #f8fafc;
            color: #334155;
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.compact-table td {
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
            font-size: 11px;
            color: #0f172a;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .font-bold { font-weight: 700; }

        .note-text {
            font-size: 10px;
            color: #92400e;
            background: #fffbeb;
            padding: 3px 6px;
            border-radius: 4px;
            margin-top: 4px;
            border: 1px solid #fef3c7;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .container {
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
<div class="no-print" style="max-width: 800px; margin: 0 auto 12px; display: flex; justify-content: space-between; align-items: center;">
    <a href="javascript:window.close()" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 12px;">&larr; Tutup</a>
    <span style="font-size: 11.5px; font-weight: 700; color: #059669;">Menampilkan <?= count($orders) ?> Nota PO Terpilih</span>
</div>
<?php endif; ?>

<div class="container">
    <!-- HEADER UTAMA -->
    <table class="header-table">
        <tr>
            <td style="border: none; padding: 0; vertical-align: top;">
                <div class="brand-title">KEREN SNACK INDONESIA</div>
                <div class="doc-title">DAFTAR REKAPITULASI PO GUDANG</div>
            </td>
            <td style="border: none; padding: 0; vertical-align: top; text-align: right;">
                <div style="font-size: 11.5px; font-weight: 800; color: #0f172a;">Tgl Cetak: <?= date('d/m/Y H:i') ?></div>
                <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">
                    Total: <strong><?= count($orders) ?> Nota PO</strong> &bull; <strong><?= number_format($grandTotalPcs, 0, ',', '.') ?> Pcs</strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- ========================================================================= -->
    <!-- 1. REKAP PER ITEM (TOTAL KEBUTUHAN GUDANG)                                 -->
    <!-- ========================================================================= -->
    <div class="section-header green">
        1. Rekapitulasi Total Barang (Semua Toko)
    </div>
    <table class="compact-table" style="margin-bottom: 12px;">
        <thead>
            <tr>
                <th style="width: 35px;" class="text-center">No</th>
                <th style="width: 120px;" class="text-center">Kode SKU</th>
                <th>Nama Produk Snack</th>
                <th style="width: 130px;" class="text-center">Total Kebutuhan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $mNo = 1;
            foreach ($masterItems as $mItem): 
            ?>
            <tr>
                <td class="text-center" style="color: #64748b; font-weight: 600;"><?= $mNo++ ?></td>
                <td class="text-center font-mono font-bold" style="color: #475569;"><?= htmlspecialchars($mItem['kode_sku']) ?></td>
                <td>
                    <div style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($mItem['nama_item']) ?></div>
                </td>
                <td class="text-center font-bold" style="color: #15803d; font-size: 12px;">
                    <?= number_format($mItem['total_qty'], 0, ',', '.') ?> <?= htmlspecialchars($mItem['satuan']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr style="background: #f8fafc; font-weight: 800;">
                <td colspan="3" class="text-right" style="text-transform: uppercase; font-size: 11px; color: #334155; padding: 6px 8px;">
                    Total Akumulasi Seluruh Barang:
                </td>
                <td class="text-center" style="font-size: 12.5px; color: #15803d; padding: 6px 8px;">
                    <?= number_format($grandTotalPcs, 0, ',', '.') ?> Pcs
                </td>
            </tr>
        </tbody>
    </table>

    <!-- ========================================================================= -->
    <!-- 2. NAMA TOKO DAN LIST PER ITEM (BERSAMBUNG / CONTINUOUS)                   -->
    <!-- ========================================================================= -->
    <div class="section-header">
        2. Rincian Item Per Toko Pelanggan
    </div>

    <?php foreach ($orders as $oIdx => $order): 
        $orderTotalPcs = 0;
    ?>
    <div class="store-box">
        <!-- Header Toko & PO -->
        <table class="store-title-table">
            <tr>
                <td style="border: none; padding: 0; vertical-align: top;">
                    <div class="store-name">
                        <?= ($oIdx + 1) ?>. <?= htmlspecialchars($order['nama_toko']) ?>
                        <span style="font-size: 10.5px; font-weight: 600; color: #64748b;">(<?= htmlspecialchars($order['kode_pelanggan']) ?>)</span>
                    </div>
                    <?php if (!empty($order['alamat_lengkap'])): ?>
                    <div class="store-meta"><?= htmlspecialchars($order['alamat_lengkap']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="border: none; padding: 0; vertical-align: top; text-align: right;">
                    <div class="po-badge"><?= htmlspecialchars($order['nomor_nota']) ?></div>
                    <div style="font-size: 10px; color: #64748b;"><?= date('d/m/Y', strtotime($order['tanggal_pesanan'] ?? $order['dibuat_pada'])) ?></div>
                </td>
            </tr>
        </table>

        <!-- Tabel Item Toko -->
        <table class="compact-table">
            <thead>
                <tr>
                    <th style="width: 30px;" class="text-center">No</th>
                    <th style="width: 110px;" class="text-center">Kode SKU</th>
                    <th>Nama Produk</th>
                    <th style="width: 100px;" class="text-center">Kuantitas</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $iNo = 1;
                foreach (($order['items'] ?? []) as $it): 
                    $qty = (int)$it['kuantitas_satuan_dasar'];
                    $orderTotalPcs += $qty;
                ?>
                <tr>
                    <td class="text-center" style="color: #64748b;"><?= $iNo++ ?></td>
                    <td class="text-center font-mono font-bold" style="color: #475569;"><?= htmlspecialchars($it['kode_sku'] ?? '-') ?></td>
                    <td>
                        <span style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($it['nama_item']) ?></span>
                    </td>
                    <td class="text-center font-bold" style="color: #1e3a8a;">
                        <?= number_format($qty, 0, ',', '.') ?> <?= htmlspecialchars($it['satuan_dasar'] ?: 'pcs') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr style="background: #f8fafc; font-weight: 700;">
                    <td colspan="3" class="text-right" style="font-size: 10.5px; color: #64748b; padding: 4px 8px;">
                        Subtotal Toko:
                    </td>
                    <td class="text-center font-bold" style="color: #0f172a; padding: 4px 8px;">
                        <?= number_format($orderTotalPcs, 0, ',', '.') ?> Pcs
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Catatan Jika Ada -->
        <?php if (!empty($order['catatan'])): ?>
        <div class="note-text">
            <strong>Catatan:</strong> <?= htmlspecialchars($order['catatan']) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

</div>

</body>
</html>

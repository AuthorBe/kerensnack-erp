<?php
use App\Helpers\Format;
use App\Core\Router;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Faktur Rekap Tagihan Konsinyasi') ?></title>
    <link rel="icon" type="image/x-icon" href="<?= Router::asset('/favicon/favicon.ico') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            padding: 24px;
            font-size: 13px;
            line-height: 1.5;
        }
        .invoice-card {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            padding: 32px 36px;
            border: 1px solid #e2e8f0;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 20px;
        }
        .brand-title {
            font-size: 22px;
            font-weight: 900;
            color: #e11d48;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }
        .brand-subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .invoice-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #fef3c7;
            color: #b45309;
            font-weight: 800;
            font-size: 11px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            background: #f8fafc;
            padding: 16px 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }
        .meta-group h4 {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .meta-group p {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
        }
        .meta-group span {
            font-size: 12px;
            color: #64748b;
        }
        table.invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.invoice-table th {
            background: #f1f5f9;
            color: #475569;
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 10px 12px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        table.invoice-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 12.5px;
        }
        table.invoice-table tr:hover td {
            background: #f8fafc;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .summary-box {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
            margin-bottom: 28px;
        }
        .summary-table {
            width: 320px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 12.5px;
        }
        .summary-row.grand-total {
            border-top: 2px solid #0f172a;
            margin-top: 8px;
            padding-top: 10px;
            font-size: 16px;
            font-weight: 900;
            color: #0f172a;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed #cbd5e1;
        }
        .sig-block {
            text-align: center;
        }
        .sig-title {
            font-size: 11.5px;
            color: #64748b;
            margin-bottom: 60px;
        }
        .sig-line {
            font-weight: 700;
            font-size: 13px;
            border-top: 1px solid #94a3b8;
            padding-top: 6px;
            display: inline-block;
            min-width: 180px;
        }
        .no-print-bar {
            max-width: 820px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            padding: 10px 20px;
            background: #e11d48;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-back {
            padding: 10px 18px;
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
        }

        @media (max-width: 640px) {
            body {
                padding: 12px;
            }
            .invoice-card {
                padding: 16px;
            }
            .invoice-header {
                flex-direction: column;
                gap: 12px;
            }
            .meta-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .no-print-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .btn-back, .btn-print {
                text-align: center;
                justify-content: center;
            }
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .invoice-card {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- ACTION BAR -->
    <div class="no-print-bar">
        <a href="<?= Router::url('/consignment?tab=billing&pelanggan_id=' . urlencode($store['id'] ?? '')) ?>" class="btn-back">
            &larr; Kembali ke Modul Konsinyasi
        </a>
        <button onclick="window.print()" class="btn-print">
            🖨️ Cetak / Simpan PDF
        </button>
    </div>

    <!-- INVOICE SHEET -->
    <div class="invoice-card">
        <!-- HEADER -->
        <div class="invoice-header">
            <div>
                <div class="brand-title">KEREN SNACK ERP</div>
                <div class="brand-subtitle">Distribusi &amp; Titip Jual Rak Mitra Berkualitas</div>
                <div style="font-size:11px;color:#64748b;margin-top:4px;">
                    Jl. Industri Snack No. 88, Jawa Barat • Telp/WA: 0812-3456-7890
                </div>
            </div>
            <div style="text-align:right;">
                <div class="invoice-badge">Tagihan Konsinyasi</div>
                <div style="font-size:12px;font-weight:800;color:#0f172a;margin-top:8px;">
                    REKAP-<?= strtoupper(date('ymd', strtotime($endDate))) ?>-<?= substr(md5($store['id'] ?? ''), 0, 4) ?>
                </div>
                <div style="font-size:11px;color:#64748b;margin-top:2px;">
                    Tanggal Cetak: <?= date('d/m/Y H:i') ?>
                </div>
            </div>
        </div>

        <!-- STORE & PERIOD META -->
        <div class="meta-grid">
            <div class="meta-group">
                <h4>Ditagihkan Kepada (Toko Konsinyasi):</h4>
                <p><?= htmlspecialchars($store['nama_toko'] ?? 'Toko Mitra') ?></p>
                <span>Pemilik: <?= htmlspecialchars($store['nama_pemilik'] ?? '-') ?></span><br>
                <span>Alamat: <?= htmlspecialchars($store['alamat_lengkap'] ?? '-') ?></span><br>
                <span>Rute Wilayah: <?= htmlspecialchars($store['rute'] ?? 'Umum') ?> • WA: <?= htmlspecialchars($store['nomor_whatsapp'] ?? '-') ?></span>
            </div>
            <div class="meta-group">
                <h4>Periode &amp; Ringkasan Kunjungan:</h4>
                <p class="font-mono"><?= date('d M Y', strtotime($startDate)) ?> &mdash; <?= date('d M Y', strtotime($endDate)) ?></p>
                <span>Total Kunjungan Opname: <strong><?= $totalKunjungan ?> kali</strong></span><br>
                <span>Total Barang Laku: <strong class="font-mono"><?= number_format($totalQtyLaku, 0, ',', '.') ?> pcs</strong></span><br>
                <span>Total Retur Ditarik: <strong class="font-mono"><?= number_format($totalRetur, 0, ',', '.') ?> pcs</strong></span>
            </div>
        </div>

        <!-- ITEMS TABLE -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width:30px;" class="text-center">No</th>
                    <th>Kode SKU &amp; Nama Produk</th>
                    <th class="text-center" style="width:85px;">Retur (Pcs)</th>
                    <th class="text-center" style="width:95px;">Laku (Pcs)</th>
                    <th class="text-right" style="width:120px;">Harga Deal</th>
                    <th class="text-right" style="width:140px;">Subtotal Tagihan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="padding:28px;color:#94a3b8;">
                        Tidak ada catatan produk laku terjual pada rentang tanggal ini.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($items as $idx => $it): ?>
                <tr>
                    <td class="text-center" style="color:#94a3b8;"><?= $idx + 1 ?></td>
                    <td>
                        <strong style="color:#0f172a;"><?= htmlspecialchars($it['nama_item']) ?></strong>
                        <div style="font-size:10.5px;color:#64748b;" class="font-mono"><?= htmlspecialchars($it['kode_sku']) ?></div>
                    </td>
                    <td class="text-center font-mono" style="color:#dc2626;">
                        <?php 
                            $rTot = (int)($it['total_retur_bagus'] + $it['total_retur_rusak']);
                            echo $rTot > 0 ? '-' . number_format($rTot, 0, ',', '.') : '-';
                        ?>
                    </td>
                    <td class="text-center font-mono" style="font-weight:800;color:#059669;font-size:13px;">
                        <?= number_format((int)$it['total_laku'], 0, ',', '.') ?> <?= htmlspecialchars($it['satuan_dasar'] ?? 'pcs') ?>
                    </td>
                    <td class="text-right font-mono" style="color:#475569;">
                        <?= Format::rupiah((float)$it['harga_satuan_deal']) ?>
                    </td>
                    <td class="text-right font-mono" style="font-weight:800;color:#0f172a;">
                        <?= Format::rupiah((float)$it['total_subtotal']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- SUMMARY -->
        <div class="summary-box">
            <div class="summary-table">
                <div class="summary-row">
                    <span style="color:#64748b;">Total Qty Laku Terjual:</span>
                    <strong class="font-mono"><?= number_format($totalQtyLaku, 0, ',', '.') ?> Pcs</strong>
                </div>
                <div class="summary-row">
                    <span style="color:#64748b;">Total Retur Ditarik:</span>
                    <strong class="font-mono"><?= number_format($totalRetur, 0, ',', '.') ?> Pcs</strong>
                </div>
                <div class="summary-row grand-total">
                    <span>GRAND TOTAL:</span>
                    <span class="font-mono" style="color:#e11d48;"><?= Format::rupiah($grandTotalLaku) ?></span>
                </div>
                <div class="summary-row" style="margin-top:6px;font-size:11.5px;color:#64748b;">
                    <span>Total Piutang Berjalan Toko:</span>
                    <strong class="font-mono" style="color:#dc2626;"><?= Format::rupiah((float)($store['total_piutang_berjalan'] ?? 0)) ?></strong>
                </div>
            </div>
        </div>

        <!-- PAYMENT INFO -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px;font-size:12px;color:#475569;margin-bottom:24px;">
            📌 <strong>Instruksi Pembayaran:</strong> Pembayaran tagihan konsinyasi dapat diserahkan tunai kepada Sales-Driver penanggung jawab atau ditransfer ke Rekening <strong>BCA: 123-456-7890 (a.n. KEREN SNACK ERP)</strong>. Mohon simpan lembar rekap ini sebagai bukti sah serah terima dan transaksi.
        </div>

        <!-- SIGNATURES -->
        <div class="signatures">
            <div class="sig-block">
                <div class="sig-title">Diterima &amp; Disetujui Oleh,<br><strong>Pemilik / Kasir Toko</strong></div>
                <div class="sig-line">( <?= htmlspecialchars($store['nama_pemilik'] ?? $store['nama_toko'] ?? '.......................') ?> )</div>
            </div>
            <div class="sig-block">
                <div class="sig-title">Diserahkan Oleh,<br><strong>Sales-Driver / Petugas ERP</strong></div>
                <div class="sig-line">( ............................................ )</div>
            </div>
        </div>
    </div>

    <?php if (!empty($autoPrint)): ?>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 600);
        });
    </script>
    <?php endif; ?>
</body>
</html>
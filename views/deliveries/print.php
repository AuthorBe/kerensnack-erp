<?php
use App\Core\Router;
use App\Helpers\Format;

$totalQtyMuatan = (int)array_sum(array_column($items, 'kuantitas_satuan_dasar'));
$statusSuratJalan = strtoupper(str_replace('_', ' ', $delivery['status_surat_jalan'] ?? 'SIAP KIRIM'));
$skemaTransaksi = !empty($delivery['is_konsinyasi']) || (($delivery['tipe_pembayaran'] ?? '') === 'konsinyasi')
    ? 'Konsinyasi (Titip Jual)'
    : strtoupper(str_replace('_', ' ', $delivery['tipe_pembayaran'] ?? 'Reguler'));
$satuanTampil = !empty($items[0]['satuan_dasar']) ? htmlspecialchars($items[0]['satuan_dasar']) : 'Bungkus';
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
            background-color: #f1f5f9;
            padding: 24px 0;
            display: flex;
            justify-content: center;
        }

        .page-sheet {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            padding: 15mm 18mm;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-radius: 4px;
            box-sizing: border-box;
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

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .page-sheet {
                box-shadow: none !important;
                padding: 0 !important;
                width: 100% !important;
                border-radius: 0 !important;
            }
            .actions-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <?php if (empty($isPdf)): ?>
    <div class="actions-bar">
        <a href="<?= Router::url('/deliveries') ?>" class="btn-action btn-back">
            &larr; Kembali ke Logistik
        </a>
        <a href="<?= Router::url('/deliveries/pdf?id=' . $delivery['id']) ?>" class="btn-action btn-pdf">
            📄 Unduh PDF
        </a>
        <button onclick="window.print()" class="btn-action btn-print">
            🖨️ Cetak Surat Jalan
        </button>
    </div>
    <?php endif; ?>

    <div class="page-sheet">
        <!-- KOP PERUSAHAAN & JUDUL SURAT JALAN RESMI -->
        <table class="kop-table">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div class="company-name">KEREN SNACK INDONESIA</div>
                    <div class="company-tagline">Produsen &amp; Distribusi Camilan Khas Nusantara Berkualitas</div>
                    <div class="company-contact">
                        Jl. Industri Snack No. 88, Jawa Barat &bull; Telp/WA: 0812-3456-7890<br>
                        Email: logistik@kerensnack.com &bull; Website: www.kerensnack.com
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
                            <td><?= date('d F Y', strtotime($delivery['dibuat_pada'] ?: $delivery['tanggal_pesanan'])) ?></td>
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
                            <td style="width: 95px; font-weight: bold;">Sales / Driver</td>
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
                    <div class="sig-title">Petugas Pengantar (Driver),</div>
                    <div class="sig-space"></div>
                    <div class="sig-line">( <?= htmlspecialchars($delivery['nama_driver'] ?: 'Sales Driver') ?> )</div>
                    <div class="sig-caption">Armada Logistik Distribusi</div>
                </td>

                <td class="sig-cell">
                    <div class="sig-title">Hormat Kami (Gudang),<br>KEREN SNACK INDONESIA</div>
                    <div class="sig-space"></div>
                    <div class="sig-line">( Petugas Gudang )</div>
                    <div class="sig-caption">Checker Logistik Pusat</div>
                </td>
            </tr>
        </table>

        <div class="doc-footer">
            Surat Jalan ini dicetak secara otomatis melalui Sistem ERP Keren Snack pada <?= date('d/m/Y H:i:s') ?> dan merupakan dokumen sah serah terima barang.
        </div>
    </div>

</body>
</html>

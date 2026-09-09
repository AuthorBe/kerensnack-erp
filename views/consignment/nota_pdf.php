<?php
use App\Helpers\Format;

$totalLakuRp = (float)($visit['total_laku_nominal'] ?? 0);
$totalQtyLaku = (int)array_sum(array_column($details, 'jumlah_laku_terjual'));
$totalQtyRusak = (int)array_sum(array_column($details, 'retur_rusak'));
$totalQtyBagus = (int)array_sum(array_column($details, 'retur_bagus'));
$totalSisaRak = (int)array_sum(array_column($details, 'sisa_fisik_di_rak'));
$totalStokAwal = (int)array_sum(array_column($details, 'stok_titip_awal'));

$rawStatus = strtoupper(trim((string)($visit['status_pembayaran'] ?? 'BELUM LUNAS')));
$statusBayar = str_replace('_', ' ', $rawStatus);

$sisaTagihan = (float)($visit['sisa_tagihan'] ?? $totalLakuRp);
$totalNetto = (float)($visit['total_netto'] ?? $totalLakuRp);
$dibayar = (float)($visit['total_dibayar'] ?? ($totalNetto - $sisaTagihan));
if ($dibayar < 0) {
    $dibayar = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Faktur Penjualan Konsinyasi - <?= htmlspecialchars($visit['nomor_nota'] ?? $visit['nomor_kunjungan']) ?></title>
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

        /* METADATA FAKTUR (Kotak Kanan Rata Sempurna) */
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

        /* CUSTOMER & TRANSACTION INFORMATION BOX */
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

        /* BOTTOM LAYOUT */
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

        /* TANDA TANGAN DUA BELAH PIHAK */
        .sig-table {
            width: 100%;
            margin-top: 14px;
            page-break-inside: avoid;
        }
        .sig-cell {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 25px;
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
            min-width: 180px;
            border-top: 1px solid #000000;
            padding-top: 3px;
            font-weight: bold;
            font-size: 8pt;
            color: #000000;
        }
        .sig-caption {
            font-size: 7pt;
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
    </style>
</head>
<body>

    <!-- KOP PERUSAHAAN & JUDUL FAKTUR RESMI -->
    <table class="kop-table">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="company-name">KEREN SNACK INDONESIA</div>
                <div class="company-tagline">Produsen &amp; Distribusi Camilan Konsinyasi Berkualitas</div>
                <div class="company-contact">
                    Jl. Industri Snack No. 88, Jawa Barat &bull; Telp/WA: 0812-3456-7890<br>
                    Email: finance@kerensnack.com &bull; Website: www.kerensnack.com
                </div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="doc-title-main">FAKTUR PENJUALAN KONSINYASI</div>
                <div class="doc-title-sub">NOTA PENAGIHAN RESMI</div>

                <!-- Perataan Barisan Sempurna: Kotak Kanan Lebar Tetap & Titik Dua Lurus -->
                <table class="doc-meta-table">
                    <tr>
                        <td style="width: 105px; font-weight: bold;">No. Faktur / Nota</td>
                        <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                        <td style="font-weight: bold;"><?= htmlspecialchars($visit['nomor_nota'] ?? $visit['nomor_kunjungan']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Tanggal Tagihan</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= date('d F Y', strtotime($visit['tanggal_kunjungan'])) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">No. Kunjungan</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($visit['nomor_kunjungan']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Status Tagihan</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td>
                            <span class="status-box"><?= $statusBayar ?></span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="divider-double"></div>

    <!-- INFORMASI PELANGGAN & TRANSAKSI (2 KOLOM SIMETRIS) -->
    <table class="info-card-table">
        <tr>
            <td style="width: 50%; border-right: 1px solid #000000;" class="info-card-header">
                KEPADA YTH. (TOKO MITRA)
            </td>
            <td style="width: 50%;" class="info-card-header">
                INFORMASI KUNJUNGAN &amp; PENAGIHAN
            </td>
        </tr>
        <tr>
            <td class="info-card-body" style="border-right: 1px solid #000000;">
                <!-- Perataan Barisan Simetris: Lebar Label 95px, Titik Dua 10px -->
                <table class="info-table-inner">
                    <tr>
                        <td style="width: 95px; font-weight: bold;">Nama Toko</td>
                        <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                        <td style="font-weight: bold; font-size: 8.5pt;"><?= htmlspecialchars($visit['nama_toko']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Kode Pelanggan</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($visit['kode_pelanggan'] ?? '-') ?></td>
                    </tr>
                    <?php if (!empty($visit['nama_pemilik'])): ?>
                    <tr>
                        <td style="font-weight: bold;">Pemilik / PIC</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($visit['nama_pemilik']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td style="font-weight: bold;">Alamat Toko</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($visit['alamat_lengkap'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">No. Telepon / WA</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($visit['nomor_whatsapp'] ?: ($visit['nomor_telepon'] ?: '-')) ?></td>
                    </tr>
                </table>
            </td>
            <td class="info-card-body">
                <!-- Perataan Barisan Simetris: Lebar Label 95px, Titik Dua 10px -->
                <table class="info-table-inner">
                    <tr>
                        <td style="width: 95px; font-weight: bold;">Sales / Driver</td>
                        <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                        <td style="font-weight: bold;"><?= htmlspecialchars($visit['sales_name']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Tgl. Kunjungan</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= date('d/m/Y', strtotime($visit['tanggal_kunjungan'])) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Skema Transaksi</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td>Konsinyasi Rak (Titip Jual Laku)</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Jatuh Tempo</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td>Saat Kunjungan / Tunai / Transfer</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TABEL RINCIAN ITEM PRODUK RESMI -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">NO</th>
                <th style="width: 13%;" class="text-center">KODE SKU</th>
                <th style="width: 31%;" class="text-left" style="padding-left: 6px;">NAMA PRODUK / BARANG</th>
                <th style="width: 7%;" class="text-center">TITIP</th>
                <th style="width: 7%;" class="text-center">SISA</th>
                <th style="width: 7%;" class="text-center">RETUR</th>
                <th style="width: 7%;" class="text-center">LAKU</th>
                <th style="width: 11%;" class="text-right" style="padding-right: 6px;">HARGA (RP)</th>
                <th style="width: 13%;" class="text-right" style="padding-right: 6px;">SUBTOTAL (RP)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($details as $idx => $d): 
                $laku = (int)$d['jumlah_laku_terjual'];
                $rusak = (int)$d['retur_rusak'];
                $bagus = (int)$d['retur_bagus'];
                $returTotal = $rusak + $bagus;
                $sisa = (int)$d['sisa_fisik_di_rak'];
                $titip = (int)$d['stok_titip_awal'];
                $subtotal = (float)$d['subtotal_laku'];
                $hargaDeal = (float)$d['harga_satuan_deal'];
            ?>
            <tr>
                <td class="text-center"><?= $idx + 1 ?></td>
                <td class="text-center font-bold"><?= htmlspecialchars($d['kode_sku'] ?? '') ?></td>
                <td style="padding-left: 6px;"><?= htmlspecialchars($d['nama_item']) ?></td>
                <td class="text-center"><?= number_format($titip, 0, ',', '.') ?></td>
                <td class="text-center"><?= number_format($sisa, 0, ',', '.') ?></td>
                <td class="text-center"><?= $returTotal > 0 ? number_format($returTotal, 0, ',', '.') : '-' ?></td>
                <td class="text-center font-bold"><?= number_format($laku, 0, ',', '.') ?></td>
                <td class="text-right" style="padding-right: 6px;"><?= number_format($hargaDeal, 0, ',', '.') ?></td>
                <td class="text-right font-bold" style="padding-right: 6px;"><?= number_format($subtotal, 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right font-bold" style="padding-right: 8px;">TOTAL KUANTITAS :</td>
                <td class="text-center font-bold"><?= number_format($totalStokAwal, 0, ',', '.') ?></td>
                <td class="text-center font-bold"><?= number_format($totalSisaRak, 0, ',', '.') ?></td>
                <td class="text-center font-bold"><?= number_format($totalQtyRusak + $totalQtyBagus, 0, ',', '.') ?></td>
                <td class="text-center font-bold"><?= number_format($totalQtyLaku, 0, ',', '.') ?></td>
                <td class="text-right font-bold" style="padding-right: 6px; white-space: nowrap;">TOTAL:</td>
                <td class="text-right font-bold" style="font-size: 8.5pt; padding-right: 6px; white-space: nowrap;"><?= number_format($totalLakuRp, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- BOTTOM SECTION: TERBILANG, PEMBAYARAN, RINGKASAN AKUNTANSI -->
    <table class="bottom-table">
        <tr>
            <td style="width: 56%; vertical-align: top; padding-right: 10px;">
                <!-- KOTAK TERBILANG -->
                <div class="terbilang-box">
                    <span style="font-weight: bold; text-transform: uppercase;">Terbilang:</span><br>
                    <span style="font-style: italic; font-weight: bold;">
                        # <?= Format::terbilang($totalLakuRp) ?> #
                    </span>
                </div>

                <!-- KETENTUAN & REKENING PEMBAYARAN -->
                <div class="payment-info-box">
                    <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 3px; border-bottom: 0.5px solid #cccccc; padding-bottom: 2px;">
                        Ketentuan &amp; Pembayaran:
                    </div>
                    1. Pembayaran via Transfer Bank Resmi:<br>
                    &nbsp;&nbsp;&nbsp;<strong><?= htmlspecialchars($bankAccount['nama_akun'] ?? 'Bank BCA') ?></strong> &bull; No. Rekening: <strong><?= htmlspecialchars($bankAccount['nomor_rekening'] ?? '8830192831') ?></strong><br>
                    &nbsp;&nbsp;&nbsp;Atas Nama: <strong><?= htmlspecialchars($bankAccount['atas_nama'] ?? 'Owner KEREN Snack') ?></strong><br>
                    2. Pembayaran tunai sah apabila diserahkan langsung kepada petugas resmi dengan tanda terima sah.<br>
                    3. Sisa fisik barang di rak (<strong><?= number_format($totalSisaRak, 0, ',', '.') ?> pcs</strong>) tetap menjadi titipan konsinyasi untuk periode berikutnya.<br>
                    <?php if (!empty($visit['catatan'])): ?>
                    4. Catatan Kunjungan: <em><?= htmlspecialchars($visit['catatan']) ?></em>
                    <?php endif; ?>
                </div>
            </td>

            <td style="width: 44%; vertical-align: top;">
                <!-- Perataan Barisan Sempurna: Lebar Label 110px, Titik Dua 10px, Nilai Rata Kanan -->
                <table class="calc-summary-table">
                    <tr>
                        <td style="width: 110px;">Total Penjualan (Laku)</td>
                        <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                        <td class="text-right font-bold">Rp <?= number_format($totalLakuRp, 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>Potongan / Retur</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td class="text-right font-bold">Rp 0</td>
                    </tr>
                    <tr class="grand-row">
                        <td style="font-weight: bold;">TOTAL TAGIHAN</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td class="text-right font-bold">Rp <?= number_format($totalLakuRp, 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>Sudah Dibayar</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td class="text-right font-bold">Rp <?= number_format($dibayar, 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">SISA PIUTANG</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td class="text-right font-bold" style="font-size: 8.5pt;">Rp <?= number_format($sisaTagihan, 0, ',', '.') ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TANDA TANGAN DUA BELAH PIHAK -->
    <table class="sig-table">
        <tr>
            <td class="sig-cell">
                <div class="sig-title">Tanda Terima Pelanggan / Toko Mitra,</div>
                <div class="sig-space"></div>
                <div class="sig-line">( <?= htmlspecialchars($visit['nama_pemilik'] ?: $visit['nama_toko']) ?> )</div>
                <div class="sig-caption">Tanda Tangan &amp; Cap Toko</div>
            </td>

            <td class="sig-cell">
                <div class="sig-title">Hormat Kami,<br>KEREN SNACK INDONESIA</div>
                <div class="sig-space"></div>
                <div class="sig-line">( <?= htmlspecialchars($visit['sales_name']) ?> )</div>
                <div class="sig-caption">Sales / Petugas Konsinyasi</div>
            </td>
        </tr>
    </table>

    <div class="doc-footer">
        Faktur ini dicetak secara otomatis melalui Sistem ERP Keren Snack pada <?= date('d/m/Y H:i:s') ?> dan merupakan bukti penagihan sah.
    </div>

</body>
</html>

<?php
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;

$comp = $company ?? CompanySetting::getAll();

$nomorDokumen = $opname['nomor_dokumen'] ?? '-';
$tanggalStr = date('d F Y', strtotime($opname['tanggal'] ?? date('Y-m-d')));
$petugas = $opname['nama_pembuat'] ?? 'Petugas Gudang';
$totalMasuk = (float)($opname['total_qty_masuk'] ?? 0);
$totalKeluar = (float)($opname['total_qty_keluar'] ?? 0);
$countSelisih = (int)($opname['total_item_selisih'] ?? count($items));
$totalKatalog = !empty($opname['total_item_katalog']) ? (int)$opname['total_item_katalog'] : null;
$totalNilaiRp = (float)($opname['total_nilai_selisih_rp'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Opname - <?= htmlspecialchars($nomorDokumen) ?></title>
    <style>
        @page {
            margin: 8mm 10mm 10mm 10mm;
            size: A4 portrait;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 7.5pt;
            line-height: 1.3;
            color: #0f172a;
            background: #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        /* KOP HEADER */
        .kop-table {
            width: 100%;
            margin-bottom: 3px;
        }
        .company-name {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }
        .company-tagline {
            font-size: 7.5pt;
            font-weight: bold;
            color: #475569;
            margin-bottom: 2px;
        }
        .company-contact {
            font-size: 7pt;
            color: #64748b;
            line-height: 1.3;
        }

        .doc-title-main {
            font-size: 12pt;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
            letter-spacing: 0.5px;
        }
        .doc-title-sub {
            font-size: 7.5pt;
            font-weight: bold;
            color: #475569;
            text-align: right;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
        }

        .doc-meta-table {
            float: right;
            width: auto;
            margin-top: 3px;
        }
        .doc-meta-table td {
            font-size: 7.5pt;
            padding: 1px 4px;
            vertical-align: middle;
        }

        /* DOUBLE DIVIDER LINE */
        .divider-double {
            border-top: 1.5px solid #0f172a;
            border-bottom: 0.5px solid #0f172a;
            height: 1.5px;
            margin: 4px 0 7px 0;
        }

        /* METADATA BOX */
        .meta-box {
            width: 100%;
            margin-bottom: 8px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 8px;
        }
        .meta-box td {
            font-size: 7.5pt;
            padding: 2px 4px;
            vertical-align: top;
        }
        .meta-label {
            color: #475569;
            font-weight: bold;
        }

        /* ITEM DATA TABLE */
        .item-table {
            width: 100%;
            border: 1px solid #94a3b8;
            margin-bottom: 8px;
        }
        .item-table th {
            background: #1e293b;
            color: #ffffff;
            font-weight: bold;
            font-size: 7pt;
            padding: 5px 4px;
            border: 1px solid #334155;
            letter-spacing: 0.2px;
            vertical-align: middle;
        }
        .item-table td {
            padding: 4px 5px;
            border: 1px solid #cbd5e1;
            font-size: 7pt;
            vertical-align: middle;
        }
        .item-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .item-table tfoot td {
            background: #f1f5f9;
            font-weight: bold;
            border-top: 1.5px solid #475569;
            border-bottom: 2px solid #475569;
            padding: 5px 4px;
            font-size: 7pt;
        }

        /* PILL BADGES */
        .pill-badge {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 3px;
            font-size: 6pt;
            font-weight: bold;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }
        .pill-in {
            background: #ecfdf5;
            color: #065f46;
            border: 0.5px solid #a7f3d0;
        }
        .pill-out {
            background: #fef2f2;
            color: #991b1b;
            border: 0.5px solid #fecaca;
        }
        .pill-zero {
            background: #f1f5f9;
            color: #475569;
            border: 0.5px solid #cbd5e1;
        }

        .text-in {
            color: #059669;
            font-weight: bold;
        }
        .text-out {
            color: #dc2626;
            font-weight: bold;
        }
        .text-zero {
            color: #64748b;
        }

        /* SIGNATURE SECTION */
        .signature-table {
            width: 100%;
            margin-top: 12px;
            page-break-inside: avoid;
        }
        .signature-table td {
            text-align: center;
            font-size: 7.5pt;
            vertical-align: top;
            width: 33.33%;
            padding: 0 8px;
        }
        .signature-title {
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 2px;
        }
        .signature-space {
            height: 48px;
        }
        .signature-name {
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1px solid #0f172a;
            display: inline-block;
            min-width: 130px;
            padding-bottom: 2px;
        }
        .signature-role {
            font-size: 6.8pt;
            color: #64748b;
            margin-top: 3px;
        }
        .signature-date {
            font-size: 6.5pt;
            color: #94a3b8;
            margin-top: 2px;
        }

        .footer-note {
            margin-top: 10px;
            font-size: 6.5pt;
            color: #94a3b8;
            display: table;
            width: 100%;
        }
        .footer-note-left {
            display: table-cell;
            text-align: left;
        }
        .footer-note-right {
            display: table-cell;
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- KOP HEADER -->
    <table class="kop-table">
        <tr>
            <td style="width: 58%; vertical-align: top;">
                <div class="company-name"><?= htmlspecialchars($comp['nama']) ?></div>
                <div class="company-tagline"><?= htmlspecialchars($comp['tagline']) ?></div>
                <div class="company-contact">
                    <?= htmlspecialchars($comp['alamat']) ?><br>
                    <?= PrintDocumentHelper::formatContactLine($comp, ' | ') ?>
                </div>
            </td>
            <td style="width: 42%; vertical-align: top; text-align: right;">
                <div class="doc-title-main">BUKTI PENYESUAIAN STOK</div>
                <div class="doc-title-sub">BERITA ACARA BULK OPNAME GUDANG</div>
                <table class="doc-meta-table">
                    <tr>
                        <td class="text-right" style="color: #64748b;">No. Dokumen :</td>
                        <td class="text-left font-bold" style="color: #0f172a;"><?= htmlspecialchars($nomorDokumen) ?></td>
                    </tr>
                    <tr>
                        <td class="text-right" style="color: #64748b;">Tanggal :</td>
                        <td class="text-left" style="color: #0f172a;"><?= $tanggalStr ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="divider-double"></div>

    <!-- METADATA INFORMATION BOX -->
    <table class="meta-box">
        <tr>
            <td style="width: 16%;" class="meta-label">Petugas Pelaksana</td>
            <td style="width: 34%;">: <strong><?= htmlspecialchars($petugas) ?></strong></td>
            <td style="width: 18%;" class="meta-label">Item Disesuaikan</td>
            <td style="width: 32%;">: <strong><?= $countSelisih ?> Produk</strong> <?= $totalKatalog ? "<span style='color:#64748b;'>(dari {$totalKatalog} di katalog)</span>" : '' ?></td>
        </tr>
        <tr>
            <td class="meta-label">Catatan Sesi</td>
            <td>: <?= htmlspecialchars($opname['catatan'] ?? '—') ?></td>
            <td class="meta-label">Mutasi Masuk / Keluar</td>
            <td>: <span class="text-in">+<?= Format::qty($totalMasuk) ?></span> / <span class="text-out">-<?= Format::qty($totalKeluar) ?> pcs</span></td>
        </tr>
        <tr>
            <td class="meta-label">Waktu Cetak</td>
            <td>: <?= date('d/m/Y H:i') ?> WIB</td>
            <td class="meta-label">Net Valuasi Selisih</td>
            <td>: <strong style="font-size:8pt; color: <?= $totalNilaiRp > 0 ? '#059669' : ($totalNilaiRp < 0 ? '#dc2626' : '#0f172a') ?>;">
                <?= ($totalNilaiRp > 0 ? '+' : '') . Format::rupiah($totalNilaiRp) ?>
            </strong></td>
        </tr>
    </table>

    <!-- ITEM DATA TABLE -->
    <table class="item-table">
        <thead>
            <tr>
                <th style="width: 20pt;" class="text-center">No</th>
                <th style="width: 56pt;" class="text-center">Kode SKU</th>
                <th class="text-left">Nama Produk &amp; Varian</th>
                <th style="width: 44pt;" class="text-center">Grup</th>
                <th style="width: 38pt;" class="text-right">Sistem</th>
                <th style="width: 38pt;" class="text-right">Fisik</th>
                <th style="width: 42pt;" class="text-right">Selisih</th>
                <th style="width: 48pt;" class="text-center">Status</th>
                <th style="width: 54pt;" class="text-right">HPP (Rp)</th>
                <th style="width: 64pt;" class="text-right">Subtotal (Rp)</th>
                <th style="width: 54pt;" class="text-left">Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
            <tr>
                <td colspan="11" class="text-center" style="padding: 18px; color: #64748b;">
                    Tidak ada item yang mengalami penyesuaian pada sesi opname ini.
                </td>
            </tr>
            <?php else: ?>
            <?php 
            $no = 1;
            $sumSistem = 0.0;
            $sumFisik = 0.0;
            $sumSubtotalRp = 0.0;
            $sumMasuk = 0.0;
            $sumKeluar = 0.0;

            foreach ($items as $it): 
                $selisih = (float)$it['selisih'];
                $isMasuk = $selisih > 0;
                $isKeluar = $selisih < 0;
                $isTetap = abs($selisih) <= 0.0001;
                $stokSistem = (float)$it['stok_sistem'];
                $stokFisik = (float)$it['stok_fisik'];
                $hpp = (float)($it['harga_pokok_saat_opname'] ?? $it['hpp_efektif'] ?? 0);
                $subtotalRp = (float)($it['subtotal_nilai_selisih'] ?? ($selisih * $hpp));

                $sumSistem += $stokSistem;
                $sumFisik += $stokFisik;
                $sumSubtotalRp += $subtotalRp;
                if ($isMasuk) $sumMasuk += $selisih;
                if ($isKeluar) $sumKeluar += abs($selisih);
            ?>
            <tr>
                <td class="text-center" style="color: #64748b;"><?= $no++ ?></td>
                <td class="text-center font-bold" style="letter-spacing: 0.3px; color: #1e293b;"><?= htmlspecialchars($it['kode_sku']) ?></td>
                <td>
                    <div style="font-weight: bold; color: #0f172a; line-height: 1.25;"><?= htmlspecialchars($it['nama_item']) ?></div>
                    <?php if (!empty($it['varian_rasa'])): ?>
                    <div style="font-size: 6.5pt; color: #64748b; margin-top: 1px;">Varian: <?= htmlspecialchars($it['varian_rasa']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-center" style="font-size: 6.5pt; color: #475569;"><?= htmlspecialchars($it['kode_grup'] ?? '-') ?></td>
                <td class="text-right" style="color: #334155;"><?= Format::qty($stokSistem) ?></td>
                <td class="text-right font-bold" style="color: #0f172a;"><?= Format::qty($stokFisik) ?></td>
                <td class="text-right <?= $isMasuk ? 'text-in' : ($isKeluar ? 'text-out' : 'text-zero') ?>">
                    <?= ($isMasuk ? '+' : '') . Format::qty($selisih) ?>
                </td>
                <td class="text-center">
                    <?php if ($isMasuk): ?>
                    <span class="pill-badge pill-in">+ MASUK</span>
                    <?php elseif ($isKeluar): ?>
                    <span class="pill-badge pill-out">- KELUAR</span>
                    <?php else: ?>
                    <span class="pill-badge pill-zero">TETAP</span>
                    <?php endif; ?>
                </td>
                <td class="text-right" style="color: #334155;">
                    <?= number_format($hpp, 0, ',', '.') ?>
                </td>
                <td class="text-right font-bold <?= $subtotalRp > 0 ? 'text-in' : ($subtotalRp < 0 ? 'text-out' : 'text-zero') ?>">
                    <?= ($subtotalRp > 0 ? '+' : '') . number_format($subtotalRp, 0, ',', '.') ?>
                </td>
                <td style="font-size: 6.5pt; color: #64748b;">
                    <?= !empty($it['catatan_item']) ? htmlspecialchars($it['catatan_item']) : '-' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (!empty($items)): ?>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right uppercase" style="letter-spacing: 0.3px;">TOTAL PENYESUAIAN FISIK:</td>
                <td class="text-right"><?= Format::qty($sumSistem) ?></td>
                <td class="text-right"><?= Format::qty($sumFisik) ?></td>
                <td class="text-right <?= ($sumMasuk - $sumKeluar) >= 0 ? 'text-in' : 'text-out' ?>">
                    <?= ($sumMasuk - $sumKeluar > 0 ? '+' : '') . Format::qty($sumMasuk - $sumKeluar) ?>
                </td>
                <td colspan="2" class="text-right uppercase" style="font-size: 6.5pt; color: #475569; letter-spacing: 0.3px;">NET VALUASI SELISIH:</td>
                <td class="text-right font-bold <?= $sumSubtotalRp > 0 ? 'text-in' : ($sumSubtotalRp < 0 ? 'text-out' : '') ?>" style="font-size: 7.5pt;">
                    <?= ($sumSubtotalRp > 0 ? '+' : '') . number_format($sumSubtotalRp, 0, ',', '.') ?>
                </td>
                <td></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <!-- SIGNATURES SECTION -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-title">Pelaksana Hitung Fisik,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( <?= htmlspecialchars($petugas) ?> )</div>
                <div class="signature-role">Staf Logistik / Gudang</div>
                <div class="signature-date">Tgl: .......................................</div>
            </td>
            <td>
                <div class="signature-title">Diverifikasi Oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( ........................................ )</div>
                <div class="signature-role">Kepala Gudang / Supervisor</div>
                <div class="signature-date">Tgl: .......................................</div>
            </td>
            <td>
                <div class="signature-title">Disetujui &amp; Diaudit Oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( ........................................ )</div>
                <div class="signature-role">Admin / Owner / Keuangan</div>
                <div class="signature-date">Tgl: .......................................</div>
            </td>
        </tr>
    </table>

    <!-- FOOTER NOTE -->
    <div class="footer-note">
        <div class="footer-note-left">
            Dokumen sah Berita Acara Opname Gudang • Dicetak oleh Keren Snack ERP pada <?= date('d/m/Y H:i:s') ?> WIB
        </div>
        <div class="footer-note-right">
            Nomor: <?= htmlspecialchars($nomorDokumen) ?>
        </div>
    </div>

    <!-- DOMPDF DYNAMIC PAGE NUMBERING SCRIPT -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Halaman " . $PAGE_NUM . " dari " . $PAGE_COUNT;
            $font = $fontMetrics->get_font("Helvetica", "normal");
            $size = 6.5;
            $color = array(0.5, 0.5, 0.5);
            $y = $pdf->get_height() - 18;
            $x = $pdf->get_width() - 85;
            $pdf->page_text($x, $y, $text, $font, $size, $color);
        }
    </script>

</body>
</html>

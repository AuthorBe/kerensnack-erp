<?php
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;
use App\Core\Auth;

$comp = $company ?? CompanySetting::getAll();
$logoSrc = PrintDocumentHelper::getLogoSrc($comp);
$items = $items ?? [];
$totalValuasi = (float)($totalValuasi ?? $grandTotalValuasi ?? 0);
$kategori = (string)($kategori ?? $kategoriLabel ?? 'Semua Jenis Item');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Katalog &amp; Valuasi Stok Gudang - <?= htmlspecialchars($comp['nama'] ?? 'KEREN SNACK') ?></title>
    <style>
        @page {
            margin: 10mm 12mm 12mm 12mm;
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
        table { width: 100%; border-collapse: collapse; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .kop-table { width: 100%; margin-bottom: 4px; }
        .company-name { font-size: 14pt; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px; margin-bottom: 2px; }
        .company-tagline { font-size: 8pt; font-weight: bold; color: #475569; margin-bottom: 2px; }
        .company-contact { font-size: 7pt; color: #64748b; line-height: 1.3; }

        .doc-title-main { font-size: 11pt; font-weight: bold; color: #0f172a; text-align: right; letter-spacing: 0.5px; }
        .doc-title-sub { font-size: 7.5pt; font-weight: bold; color: #7c3aed; text-align: right; letter-spacing: 0.8px; margin-bottom: 4px; }

        .divider-double {
            border-top: 1.5px solid #7c3aed;
            border-bottom: 0.5px solid #7c3aed;
            height: 1.5px;
            margin: 4px 0 8px 0;
        }

        .meta-box {
            width: 100%;
            margin-bottom: 10px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 8px;
        }
        .meta-box td { font-size: 7.5pt; padding: 2px 4px; vertical-align: middle; }
        .meta-label { color: #475569; font-weight: bold; }

        .report-table { width: 100%; border: 1px solid #cbd5e1; margin-bottom: 10px; }
        .report-table th {
            background: #1e293b;
            color: #ffffff;
            font-weight: bold;
            font-size: 7pt;
            padding: 5px 6px;
            border: 1px solid #334155;
            letter-spacing: 0.2px;
        }
        .report-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #f1f5f9;
            font-size: 7pt;
            vertical-align: middle;
        }
        .report-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .grand-total-row td {
            background: #f1f5f9;
            font-weight: bold;
            font-size: 7.5pt;
            border-top: 1.5px solid #334155;
            border-bottom: 2px solid #334155;
            padding: 6px 6px;
        }

        .pill-badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6pt;
            font-weight: bold;
        }
        .pill-aman { background: #ecfdf5; color: #065f46; border: 0.5px solid #a7f3d0; }
        .pill-menipis { background: #fffbeb; color: #b45309; border: 0.5px solid #fde68a; }
        .pill-habis { background: #fef2f2; color: #991b1b; border: 0.5px solid #fecaca; }

        .signature-table { width: 100%; margin-top: 14px; page-break-inside: avoid; }
        .signature-table td { text-align: center; font-size: 7.5pt; vertical-align: top; width: 33.33%; padding: 0 8px; }
        .signature-title { font-weight: bold; color: #1e293b; margin-bottom: 2px; }
        .signature-space { height: 40px; }
        .signature-name {
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1px solid #0f172a;
            display: inline-block;
            min-width: 120px;
            padding-bottom: 2px;
        }
        .signature-role { font-size: 6.8pt; color: #64748b; margin-top: 3px; }

        .footer-note { margin-top: 10px; font-size: 6.5pt; color: #94a3b8; display: table; width: 100%; }
        .footer-left { display: table-cell; text-align: left; }
        .footer-right { display: table-cell; text-align: right; }
    </style>
</head>
<body>

    <!-- KOP RESMI -->
    <table class="kop-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <table style="width: auto; border-collapse: collapse;">
                    <tr>
                        <?php if (!empty($logoSrc)): ?>
                        <td style="width: 50px; vertical-align: middle; padding-right: 12px;">
                            <img src="<?= $logoSrc ?>" alt="Logo" style="max-height: 44px; max-width: 50px; object-fit: contain;">
                        </td>
                        <?php endif; ?>
                        <td style="vertical-align: middle;">
                            <div class="company-name"><?= htmlspecialchars($comp['nama'] ?? 'KEREN SNACK INDONESIA') ?></div>
                            <div class="company-tagline"><?= htmlspecialchars($comp['tagline'] ?? 'Produsen & Distributor Aneka Makanan Ringan') ?></div>
                            <div class="company-contact">
                                <?= htmlspecialchars($comp['alamat'] ?? 'Jl. Industri Snack No. 88, Jawa Barat') ?><br>
                                <?= PrintDocumentHelper::formatContactLine($comp, ' | ') ?>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right;">
                <div class="doc-title-main">VALUASI STOK &amp; PERSEDIAAN</div>
                <div class="doc-title-sub">KATALOG FISIK GUDANG PUSAT</div>
                <div style="font-size:7.5pt; color:#64748b; margin-top:2px;">
                    Dokumen Aset Persediaan Resmi
                </div>
            </td>
        </tr>
    </table>

    <div class="divider-double"></div>

    <!-- METADATA BOX -->
    <table class="meta-box">
        <tr>
            <td style="width: 18%;" class="meta-label">Kategori Filter</td>
            <td style="width: 32%;">: <strong><?= htmlspecialchars(ucfirst($kategori ?? 'Semua Jenis Item')) ?></strong></td>
            <td style="width: 18%;" class="meta-label">Total SKU Aktif</td>
            <td style="width: 32%;">: <strong><?= count($items) ?> Item Produk</strong></td>
        </tr>
        <tr>
            <td class="meta-label">Waktu Cetak</td>
            <td>: <?= date('d/m/Y H:i') ?> WIB</td>
            <td class="meta-label">Total Valuasi Aset</td>
            <td>: <strong style="color:#7c3aed; font-size:8pt;"><?= Format::rupiah($totalValuasi) ?></strong></td>
        </tr>
    </table>

    <!-- DATA TABLE -->
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 20pt;" class="text-center">No</th>
                <th style="width: 55pt;" class="text-center">Kode SKU</th>
                <th class="text-left">Nama Produk / Bahan</th>
                <th style="width: 50pt;" class="text-center">Tipe Item</th>
                <th style="width: 32pt;" class="text-center">Sat</th>
                <th style="width: 40pt;" class="text-right">Stok Fisik</th>
                <th style="width: 35pt;" class="text-right">Min</th>
                <th style="width: 55pt;" class="text-right">HPP (Rp)</th>
                <th style="width: 65pt;" class="text-right">Total Valuasi (Rp)</th>
                <th style="width: 42pt;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $sumStok = 0.0;
            foreach ($items as $it): 
                $stok = (float)$it['stok_fisik_saat_ini'];
                $min = (float)$it['stok_minimum_peringatan'];
                $hpp = (float)($it['hpp_efektif'] ?? $it['harga_pokok_pembelian'] ?? 0);
                $valuasi = $stok * $hpp;
                $status = ($stok <= 0) ? 'HABIS' : (($stok <= $min) ? 'MENIPIS' : 'AMAN');
                $sumStok += $stok;
            ?>
            <tr>
                <td class="text-center" style="color:#64748b;"><?= $no++ ?></td>
                <td class="text-center font-bold"><?= htmlspecialchars($it['kode_sku']) ?></td>
                <td>
                    <div style="font-weight:bold; color:#0f172a;"><?= htmlspecialchars($it['nama_item']) ?></div>
                    <?php if (!empty($it['nama_grup_produk'])): ?>
                    <div style="font-size:6.5pt; color:#64748b;">Grup: <?= htmlspecialchars($it['nama_grup_produk']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-center" style="font-size:6.5pt;"><?= strtoupper((string)$it['tipe_item']) ?></td>
                <td class="text-center"><?= htmlspecialchars($it['satuan_dasar'] ?? 'pcs') ?></td>
                <td class="text-right font-bold"><?= Format::qty($stok) ?></td>
                <td class="text-right" style="color:#64748b;"><?= Format::qty($min) ?></td>
                <td class="text-right"><?= number_format($hpp, 0, ',', '.') ?></td>
                <td class="text-right font-bold" style="color:#0f172a;"><?= number_format($valuasi, 0, ',', '.') ?></td>
                <td class="text-center">
                    <?php if ($status === 'AMAN'): ?>
                    <span class="pill-badge pill-aman">AMAN</span>
                    <?php elseif ($status === 'MENIPIS'): ?>
                    <span class="pill-badge pill-menipis">MENIPIS</span>
                    <?php else: ?>
                    <span class="pill-badge pill-habis">HABIS</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <td colspan="5" class="text-right uppercase">TOTAL KESELURUHAN ASET GUDANG:</td>
                <td class="text-right"><?= Format::qty($sumStok) ?></td>
                <td colspan="2" class="text-right uppercase" style="font-size:6.5pt;">TOTAL VALUASI (HPP):</td>
                <td class="text-right font-bold" style="color:#7c3aed; font-size:8pt;"><?= Format::rupiah($totalValuasi) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- SIGNATURE SECTION -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-title">Petugas Gudang / Logistik,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( <?= htmlspecialchars(Auth::name()) ?> )</div>
                <div class="signature-role">Staf Inventori &amp; Gudang</div>
            </td>
            <td>
                <div class="signature-title">Kepala Gudang / Supervisor,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( ........................................ )</div>
                <div class="signature-role">Verifikasi Fisik &amp; Kuantitas</div>
            </td>
            <td>
                <div class="signature-title">Disetujui &amp; Diaudit Oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( ........................................ )</div>
                <div class="signature-role">Owner / Finance Management</div>
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer-note">
        <div class="footer-left">
            Dokumen Valuasi Persediaan Sah • Dicetak oleh Keren One ERP pada <?= date('d/m/Y H:i:s') ?> WIB
        </div>
        <div class="footer-right">
            Total Nilai: <?= Format::rupiah($totalValuasi) ?>
        </div>
    </div>

    <!-- DOMPDF_PAGE_NUMBERS -->
</body>
</html>

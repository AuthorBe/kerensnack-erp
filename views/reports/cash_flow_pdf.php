<?php
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;
use App\Core\Auth;

$comp = $company ?? CompanySetting::getAll();
$logoSrc = PrintDocumentHelper::getLogoSrc($comp);
$startDateStr = date('d F Y', strtotime($startDate ?? date('Y-m-01')));
$endDateStr = date('d F Y', strtotime($endDate ?? date('Y-m-d')));

$accountName = (string)($accountName ?? $selectedAccountName ?? 'Semua Rekening & Kas');
$begBalance = (float)($begBalance ?? $saldoAwal ?? 0);
$totalIn = (float)($totalIn ?? $totalMasuk ?? 0);
$totalOut = (float)($totalOut ?? $totalKeluar ?? 0);
$endingBalance = (float)($endingBalance ?? $saldoAkhir ?? 0);
$netCashFlow = (float)($netCashFlow ?? ($totalIn - $totalOut));
$netTransfer = (float)($netTransfer ?? 0);
$inflowBreakdown = $inflowBreakdown ?? [];
$outflowBreakdown = $outflowBreakdown ?? $kategoriKeluar ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Arus Kas - <?= htmlspecialchars($comp['nama'] ?? 'KEREN SNACK') ?></title>
    <style>
        @page {
            margin: 10mm 12mm 12mm 12mm;
            size: A4 portrait;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8pt;
            line-height: 1.35;
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
        .doc-title-sub { font-size: 7.5pt; font-weight: bold; color: #059669; text-align: right; letter-spacing: 0.8px; margin-bottom: 4px; }

        .divider-double {
            border-top: 1.5px solid #059669;
            border-bottom: 0.5px solid #059669;
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
            font-size: 7.5pt;
            padding: 6px 8px;
            border: 1px solid #334155;
            letter-spacing: 0.2px;
        }
        .report-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #f1f5f9;
            font-size: 7.5pt;
        }
        .section-header {
            background: #f1f5f9;
            font-weight: bold;
            color: #0f172a;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
        }
        .subtotal-row {
            background: #f0fdf4;
            font-weight: bold;
            color: #065f46;
            border-top: 1px solid #bbf7d0;
            border-bottom: 1px solid #bbf7d0;
        }
        .subtotal-row-out {
            background: #fef2f2;
            font-weight: bold;
            color: #991b1b;
            border-top: 1px solid #fecaca;
            border-bottom: 1px solid #fecaca;
        }
        .grand-total-row {
            background: #ecfdf5;
            font-weight: bold;
            font-size: 8.5pt;
            border-top: 1.5px solid #059669;
            border-bottom: 2px solid #059669;
        }

        .signature-table { width: 100%; margin-top: 14px; page-break-inside: avoid; }
        .signature-table td { text-align: center; font-size: 7.5pt; vertical-align: top; width: 33.33%; padding: 0 8px; }
        .signature-title { font-weight: bold; color: #1e293b; margin-bottom: 2px; }
        .signature-space { height: 42px; }
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
                <div class="doc-title-main">LAPORAN ARUS KAS (CASH FLOW)</div>
                <div class="doc-title-sub">REKAPITULASI PENERIMAAN &amp; PENGELUARAN KAS / BANK</div>
                <div style="font-size:7.5pt; color:#64748b; margin-top:2px;">
                    Dokumen Rekonsiliasi Perbendaharaan
                </div>
            </td>
        </tr>
    </table>

    <div class="divider-double"></div>

    <!-- METADATA BOX -->
    <table class="meta-box">
        <tr>
            <td style="width: 18%;" class="meta-label">Periode Analisis</td>
            <td style="width: 32%;">: <strong><?= $startDateStr ?> s/d <?= $endDateStr ?></strong></td>
            <td style="width: 18%;" class="meta-label">Akun Kas / Bank</td>
            <td style="width: 32%;">: <strong><?= htmlspecialchars($accountName ?? 'Semua Rekening & Kas') ?></strong></td>
        </tr>
        <tr>
            <td class="meta-label">Dicetak Oleh</td>
            <td>: <?= htmlspecialchars(Auth::name()) ?> (<?= ucfirst(Auth::role()) ?>)</td>
            <td class="meta-label">Waktu Cetak</td>
            <td>: <?= date('d/m/Y H:i') ?> WIB</td>
        </tr>
    </table>

    <!-- DATA TABLE CASH FLOW -->
    <table class="report-table">
        <thead>
            <tr>
                <th class="text-left" style="width: 55%;">Keterangan / Kategori Arus Kas</th>
                <th class="text-center" style="width: 15%;">Jumlah Trx</th>
                <th class="text-right" style="width: 30%;">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <!-- SALDO AWAL -->
            <tr style="background:#f8fafc; font-weight:bold;">
                <td>SALDO AWAL KAS &amp; BANK (PERIODE SEBELUMNYA)</td>
                <td class="text-center">-</td>
                <td class="text-right" style="font-size:8pt;"><?= Format::rupiah($begBalance) ?></td>
            </tr>

            <!-- INFLOW -->
            <tr class="section-header">
                <td colspan="3">1. ARUS KAS MASUK (INFLOW)</td>
            </tr>
            <?php if (!empty($inflowBreakdown)): ?>
                <?php foreach ($inflowBreakdown as $ib): ?>
                <tr>
                    <td>&bull; Penerimaan: <?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)$ib['kategori']))) ?></td>
                    <td class="text-center"><?= (int)$ib['jml'] ?></td>
                    <td class="text-right" style="color:#059669;"><?= Format::rupiah((float)$ib['total']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" class="text-center" style="color:#94a3b8;">Tidak ada kas masuk pada periode ini</td></tr>
            <?php endif; ?>
            <tr class="subtotal-row">
                <td colspan="2">TOTAL PENERIMAAN KAS MASUK</td>
                <td class="text-right" style="font-size:8pt;"><?= Format::rupiah($totalIn) ?></td>
            </tr>

            <!-- OUTFLOW -->
            <tr class="section-header">
                <td colspan="3">2. ARUS KAS KELUAR (OUTFLOW)</td>
            </tr>
            <?php if (!empty($outflowBreakdown)): ?>
                <?php foreach ($outflowBreakdown as $ob): ?>
                <tr>
                    <td>&bull; Pengeluaran: <?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)$ob['kategori']))) ?></td>
                    <td class="text-center"><?= (int)$ob['jml'] ?></td>
                    <td class="text-right" style="color:#dc2626;">-<?= Format::rupiah((float)$ob['total']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" class="text-center" style="color:#94a3b8;">Tidak ada kas keluar pada periode ini</td></tr>
            <?php endif; ?>
            <tr class="subtotal-row-out">
                <td colspan="2">TOTAL PENGELUARAN KAS KELUAR</td>
                <td class="text-right" style="font-size:8pt;">-<?= Format::rupiah($totalOut) ?></td>
            </tr>

            <!-- NET FLOW -->
            <tr style="background:#f1f5f9; font-weight:bold;">
                <td colspan="2">ARUS KAS BERSIH PERIODE INI (NET CASH FLOW)</td>
                <td class="text-right" style="color: <?= $netCashFlow >= 0 ? '#059669' : '#dc2626' ?>; font-size:8.5pt;">
                    <?= ($netCashFlow >= 0 ? '+' : '') . Format::rupiah($netCashFlow) ?>
                </td>
            </tr>

            <?php if (!empty($accountId) && $accountId !== 'all'): ?>
            <tr>
                <td colspan="2">&bull; Mutasi Transfer Dana Masuk / Keluar Bersih</td>
                <td class="text-right"><?= Format::rupiah($netTransfer) ?></td>
            </tr>
            <?php endif; ?>

            <!-- SALDO AKHIR -->
            <tr class="grand-total-row">
                <td colspan="2">SALDO AKHIR KAS &amp; BANK (CLOSING BALANCE)</td>
                <td class="text-right" style="color:#047857; font-size:9pt;"><?= Format::rupiah($endingBalance) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- SIGNATURE SECTION -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-title">Disusun Oleh Kasir,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( <?= htmlspecialchars(Auth::name()) ?> )</div>
                <div class="signature-role">Staff Kasir / Keuangan</div>
            </td>
            <td>
                <div class="signature-title">Diperiksa &amp; Direkonsiliasi,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( ........................................ )</div>
                <div class="signature-role">Supervisor / Finance Manager</div>
            </td>
            <td>
                <div class="signature-title">Disetujui Oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( ........................................ )</div>
                <div class="signature-role">Direktur / Owner</div>
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer-note">
        <div class="footer-left">
            Dokumen Laporan Arus Kas Resmi • Dicetak oleh Keren One ERP pada <?= date('d/m/Y H:i:s') ?> WIB
        </div>
        <div class="footer-right">
            Periode: <?= $startDateStr ?> - <?= $endDateStr ?>
        </div>
    </div>

    <!-- DOMPDF DYNAMIC PAGE NUMBERING -->
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

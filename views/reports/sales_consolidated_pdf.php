<?php
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;
use App\Core\Auth;

$comp = $company ?? CompanySetting::getAll();
$startDateStr = date('d F Y', strtotime($startDate ?? date('Y-m-01')));
$endDateStr = date('d F Y', strtotime($endDate ?? date('Y-m-d')));

$posOmzet = (float)($summaryData['pos_omzet'] ?? $pos['summary']['total_omzet'] ?? 0);
$posTerbayar = (float)($summaryData['pos_terbayar'] ?? $pos['summary']['total_dibayar'] ?? 0);
$posPiutang = (float)($summaryData['pos_piutang'] ?? $pos['summary']['total_piutang'] ?? 0);
$posHpp = (float)($summaryData['pos_hpp'] ?? $pos['summary']['total_hpp'] ?? 0);
$posLaba = (float)($summaryData['pos_laba'] ?? $pos['summary']['laba_kotor'] ?? 0);

$b2bOmzet = (float)($summaryData['b2b_omzet'] ?? $b2b['summary']['total_omzet'] ?? 0);
$b2bTerbayar = (float)($summaryData['b2b_terbayar'] ?? $b2b['summary']['total_dibayar'] ?? 0);
$b2bPiutang = (float)($summaryData['b2b_piutang'] ?? $b2b['summary']['total_piutang'] ?? 0);
$b2bHpp = (float)($summaryData['b2b_hpp'] ?? $b2b['summary']['total_hpp'] ?? 0);
$b2bLaba = (float)($summaryData['b2b_laba'] ?? $b2b['summary']['laba_kotor'] ?? 0);

$consOmzet = (float)($summaryData['cons_omzet'] ?? $konsinyasi['summary']['total_omzet'] ?? 0);
$consTerbayar = (float)($summaryData['cons_terbayar'] ?? $konsinyasi['summary']['total_dibayar'] ?? 0);
$consPiutang = (float)($summaryData['cons_piutang'] ?? $konsinyasi['summary']['total_piutang'] ?? 0);
$consHpp = (float)($summaryData['cons_hpp'] ?? $konsinyasi['summary']['total_hpp'] ?? 0);
$consLoss = (float)($summaryData['cons_loss'] ?? $konsinyasi['summary']['total_rugi'] ?? 0);
$consLaba = (float)($summaryData['cons_laba'] ?? $konsinyasi['summary']['laba_kotor'] ?? 0);

$totalOmzet = (float)($summaryData['total_omzet'] ?? $kpi['grand_total_omzet'] ?? ($posOmzet + $b2bOmzet + $consOmzet));
$totalTerbayar = (float)($summaryData['total_terbayar'] ?? $kpi['grand_total_terbayar'] ?? ($posTerbayar + $b2bTerbayar + $consTerbayar));
$totalPiutang = (float)($summaryData['total_piutang'] ?? $kpi['grand_total_piutang'] ?? ($posPiutang + $b2bPiutang + $consPiutang));
$totalHpp = (float)($summaryData['total_hpp'] ?? ($posHpp + $b2bHpp + $consHpp));
$totalLoss = (float)($summaryData['cons_loss'] ?? $consLoss);
$totalLaba = (float)($summaryData['total_laba'] ?? $kpi['grand_total_laba'] ?? ($posLaba + $b2bLaba + $consLaba));

$kolektibilitasPct = $totalOmzet > 0 ? round(($totalTerbayar / $totalOmzet) * 100, 1) : 0;
$marginLabaPct = $totalOmzet > 0 ? round(($totalLaba / $totalOmzet) * 100, 1) : 0;
$lossPct = $totalOmzet > 0 ? round(($totalLoss / $totalOmzet) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Penjualan Multi-Kanal - <?= htmlspecialchars($comp['nama'] ?? 'KEREN SNACK') ?></title>
    <style>
        @page {
            margin: 8mm 10mm 10mm 10mm;
            size: A4 landscape;
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

        .kop-table { width: 100%; margin-bottom: 3px; }
        .company-name { font-size: 14pt; font-weight: bold; color: #881337; letter-spacing: 0.3px; margin-bottom: 1px; }
        .company-tagline { font-size: 8pt; font-weight: bold; color: #475569; margin-bottom: 1px; }
        .company-contact { font-size: 7pt; color: #64748b; line-height: 1.25; }

        .doc-title-main { font-size: 12pt; font-weight: bold; color: #0f172a; text-align: right; letter-spacing: 0.5px; }
        .doc-title-sub { font-size: 8pt; font-weight: bold; color: #881337; text-align: right; letter-spacing: 0.8px; margin-bottom: 2px; }

        .divider-double {
            border-top: 1.5px solid #881337;
            border-bottom: 0.5px solid #881337;
            height: 1.5px;
            margin: 3px 0 6px 0;
        }

        .meta-box {
            width: 100%;
            margin-bottom: 8px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 4px 8px;
        }
        .meta-box td { font-size: 7.5pt; padding: 2px 4px; vertical-align: middle; }
        .meta-label { color: #475569; font-weight: bold; }

        /* KPI CARD METRICS */
        .kpi-table { width: 100%; margin-bottom: 8px; }
        .kpi-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 10px;
            background: #ffffff;
            text-align: center;
        }
        .kpi-title { font-size: 6.8pt; font-weight: bold; text-transform: uppercase; color: #64748b; letter-spacing: 0.3px; margin-bottom: 2px; }
        .kpi-val { font-size: 10.5pt; font-weight: bold; line-height: 1.2; }

        .report-table { width: 100%; border: 1px solid #cbd5e1; margin-bottom: 8px; }
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
            padding: 4.5px 6px;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #f1f5f9;
            font-size: 7.5pt;
            vertical-align: middle;
        }
        .grand-total-row td {
            background: #f1f5f9;
            font-weight: bold;
            font-size: 8pt;
            border-top: 1.5px solid #1e293b;
            border-bottom: 2px solid #1e293b;
            padding: 6px 6px;
        }

        .ratio-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 8px;
            font-size: 7pt;
        }

        .signature-table { width: 100%; margin-top: 10px; page-break-inside: avoid; }
        .signature-table td { text-align: center; font-size: 7.5pt; vertical-align: top; width: 33.33%; padding: 0 8px; }
        .signature-title { font-weight: bold; color: #1e293b; margin-bottom: 1px; }
        .signature-space { height: 36px; }
        .signature-name {
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1px solid #0f172a;
            display: inline-block;
            min-width: 130px;
            padding-bottom: 2px;
        }
        .signature-role { font-size: 6.8pt; color: #64748b; margin-top: 2px; }

        .footer-note { margin-top: 8px; font-size: 6.5pt; color: #94a3b8; display: table; width: 100%; }
        .footer-left { display: table-cell; text-align: left; }
        .footer-right { display: table-cell; text-align: right; }
    </style>
</head>
<body>

    <!-- KOP RESMI -->
    <table class="kop-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="company-name"><?= htmlspecialchars($comp['nama'] ?? 'KEREN SNACK INDONESIA') ?></div>
                <div class="company-tagline"><?= htmlspecialchars($comp['tagline'] ?? 'Produsen & Distributor Aneka Makanan Ringan') ?></div>
                <div class="company-contact">
                    <?= htmlspecialchars($comp['alamat'] ?? 'Jl. Industri Snack No. 88, Jawa Barat') ?><br>
                    <?= PrintDocumentHelper::formatContactLine($comp, ' | ') ?>
                </div>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right;">
                <div class="doc-title-main">REKAPITULASI PENJUALAN MULTI-KANAL</div>
                <div class="doc-title-sub">POS, TOKO REGULER B2B &amp; KONSINYASI RAK</div>
                <div style="font-size:7.5pt; color:#64748b; margin-top:2px;">
                    Laporan Konsolidasi Finansial Eksekutif
                </div>
            </td>
        </tr>
    </table>

    <div class="divider-double"></div>

    <!-- METADATA BOX -->
    <table class="meta-box">
        <tr>
            <td style="width: 15%;" class="meta-label">Periode Analisis</td>
            <td style="width: 35%;">: <strong><?= $startDateStr ?> s/d <?= $endDateStr ?></strong></td>
            <td style="width: 15%;" class="meta-label">Dicetak Oleh</td>
            <td style="width: 35%;">: <?= htmlspecialchars(Auth::name()) ?> (<?= ucfirst(Auth::role()) ?>)</td>
        </tr>
        <tr>
            <td class="meta-label">Waktu Unduh</td>
            <td>: <?= date('d/m/Y H:i') ?> WIB</td>
            <td class="meta-label">Status Laporan</td>
            <td>: <strong style="color:#059669;">RESMI / TERPOSTING &bull; AUDITED</strong></td>
        </tr>
    </table>

    <!-- 4 KPI SUMMARY CARDS -->
    <table class="kpi-table">
        <tr>
            <td style="width: 25%; padding-right: 4px;">
                <div class="kpi-card" style="border-left: 3px solid #1e40af;">
                    <div class="kpi-title">TOTAL OMZET KONSOLIDASI</div>
                    <div class="kpi-val" style="color: #1e40af;"><?= Format::rupiah($totalOmzet) ?></div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 2px;">
                <div class="kpi-card" style="border-left: 3px solid #059669;">
                    <div class="kpi-title">REALISASI KAS (SUDAH DIBAYAR)</div>
                    <div class="kpi-val" style="color: #059669;"><?= Format::rupiah($totalTerbayar) ?></div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 2px;">
                <div class="kpi-card" style="border-left: 3px solid #dc2626;">
                    <div class="kpi-title">SISA PIUTANG (OUTSTANDING)</div>
                    <div class="kpi-val" style="color: #dc2626;"><?= Format::rupiah($totalPiutang) ?></div>
                </div>
            </td>
            <td style="width: 25%; padding-left: 4px;">
                <div class="kpi-card" style="border-left: 3px solid #7c3aed;">
                    <div class="kpi-title">KEUNTUNGAN PENJUALAN MURNI</div>
                    <div class="kpi-val" style="color: #7c3aed;"><?= Format::rupiah($totalLaba) ?></div>
                </div>
            </td>
        </tr>
    </table>

    <!-- TABEL KOMPARASI UTAMA 3 KANAL -->
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 20pt;" class="text-center">No</th>
                <th class="text-left">Kanal Distribusi Penjualan</th>
                <th style="width: 75pt;" class="text-right">Total Omzet (Rp)</th>
                <th style="width: 40pt;" class="text-right">Sales Mix</th>
                <th style="width: 75pt;" class="text-right">Sudah Dibayar (Rp)</th>
                <th style="width: 75pt;" class="text-right">Sisa Piutang (Rp)</th>
                <th style="width: 75pt;" class="text-right">HPP Pokok (Rp)</th>
                <th style="width: 65pt;" class="text-right">Rugi Retur (Rp)</th>
                <th style="width: 80pt;" class="text-right">Keuntungan Murni (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td><strong>1. Kasir POS (Penjualan Ritel Walk-in)</strong></td>
                <td class="text-right font-bold"><?= Format::rupiah($posOmzet) ?></td>
                <td class="text-right"><?= $totalOmzet > 0 ? number_format(($posOmzet / $totalOmzet) * 100, 1) : '0' ?>%</td>
                <td class="text-right" style="color:#059669;"><?= Format::rupiah($posTerbayar) ?></td>
                <td class="text-right" style="color:#64748b;"><?= Format::rupiah($posPiutang) ?></td>
                <td class="text-right"><?= Format::rupiah($posHpp) ?></td>
                <td class="text-right" style="color:#64748b;">-</td>
                <td class="text-right font-bold" style="color:#059669;"><?= Format::rupiah($posLaba) ?></td>
            </tr>
            <tr>
                <td class="text-center">2</td>
                <td><strong>2. Toko Reguler B2B (Faktur Pesanan Grosir)</strong></td>
                <td class="text-right font-bold"><?= Format::rupiah($b2bOmzet) ?></td>
                <td class="text-right"><?= $totalOmzet > 0 ? number_format(($b2bOmzet / $totalOmzet) * 100, 1) : '0' ?>%</td>
                <td class="text-right" style="color:#059669;"><?= Format::rupiah($b2bTerbayar) ?></td>
                <td class="text-right font-bold" style="color:#dc2626;"><?= Format::rupiah($b2bPiutang) ?></td>
                <td class="text-right"><?= Format::rupiah($b2bHpp) ?></td>
                <td class="text-right" style="color:#64748b;">-</td>
                <td class="text-right font-bold" style="color:#059669;"><?= Format::rupiah($b2bLaba) ?></td>
            </tr>
            <tr>
                <td class="text-center">3</td>
                <td><strong>3. Toko Konsinyasi (Titip Jual Display Rak)</strong></td>
                <td class="text-right font-bold"><?= Format::rupiah($consOmzet) ?></td>
                <td class="text-right"><?= $totalOmzet > 0 ? number_format(($consOmzet / $totalOmzet) * 100, 1) : '0' ?>%</td>
                <td class="text-right" style="color:#059669;"><?= Format::rupiah($consTerbayar) ?></td>
                <td class="text-right font-bold" style="color:#dc2626;"><?= Format::rupiah($consPiutang) ?></td>
                <td class="text-right"><?= Format::rupiah($consHpp) ?></td>
                <td class="text-right font-bold" style="color:#dc2626;"><?= Format::rupiah($consLoss) ?></td>
                <td class="text-right font-bold" style="color:#059669;"><?= Format::rupiah($consLaba) ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <td colspan="2" class="text-right uppercase">GRAND TOTAL KONSOLIDASI:</td>
                <td class="text-right font-bold" style="color:#1e40af; font-size:8.5pt;"><?= Format::rupiah($totalOmzet) ?></td>
                <td class="text-right">100.0%</td>
                <td class="text-right font-bold" style="color:#059669;"><?= Format::rupiah($totalTerbayar) ?></td>
                <td class="text-right font-bold" style="color:#dc2626; font-size:8.5pt;"><?= Format::rupiah($totalPiutang) ?></td>
                <td class="text-right font-bold"><?= Format::rupiah($totalHpp) ?></td>
                <td class="text-right font-bold" style="color:#dc2626;"><?= Format::rupiah($totalLoss) ?></td>
                <td class="text-right font-bold" style="color:#047857; font-size:9pt;"><?= Format::rupiah($totalLaba) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- RATIO & FINANCIAL HEALTH MATRIX -->
    <div class="ratio-box">
        <table style="width: 100%;">
            <tr>
                <td style="width: 33%;">
                    <strong>Tingkat Kolektibilitas Kas:</strong> <span style="font-size:8pt; font-weight:bold; color: <?= $kolektibilitasPct >= 70 ? '#059669' : '#dc2626' ?>;"><?= $kolektibilitasPct ?>%</span> 
                    <em>(<?= $kolektibilitasPct >= 70 ? 'Arus Kas Sehat' : 'Perketat Penagihan AR' ?>)</em>
                </td>
                <td style="width: 33%;">
                    <strong>Margin Laba Kotor Murni:</strong> <span style="font-size:8pt; font-weight:bold; color:#059669;"><?= $marginLabaPct ?>%</span> 
                    <em>(Profitabilitas Penjualan)</em>
                </td>
                <td style="width: 33%;">
                    <strong>Rasio Retur Rusak Konsinyasi:</strong> <span style="font-size:8pt; font-weight:bold; color: <?= $lossPct <= 2 ? '#059669' : '#dc2626' ?>;"><?= $lossPct ?>%</span> 
                    <em>(<?= $lossPct <= 2 ? 'Dalam Batas Normal' : 'Tinggi / Perlu Audit' ?>)</em>
                </td>
            </tr>
        </table>
    </div>

    <!-- SIGNATURE SECTION -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-title">Disiapkan Oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( <?= htmlspecialchars(Auth::name()) ?> )</div>
                <div class="signature-role">Admin Penjualan &amp; Distribusi</div>
            </td>
            <td>
                <div class="signature-title">Diperiksa &amp; Direkonsiliasi,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( ........................................ )</div>
                <div class="signature-role">Finance &amp; AR Supervisor</div>
            </td>
            <td>
                <div class="signature-title">Disetujui &amp; Disahkan Oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( ........................................ )</div>
                <div class="signature-role">Direktur / Owner Perusahaan</div>
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer-note">
        <div class="footer-left">
            Dokumen Rekapitulasi Penjualan Multi-Kanal Resmi • Diterbitkan oleh Keren One ERP pada <?= date('d/m/Y H:i:s') ?> WIB
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
            $y = $pdf->get_height() - 16;
            $x = $pdf->get_width() - 85;
            $pdf->page_text($x, $y, $text, $font, $size, $color);
        }
    </script>
</body>
</html>

<?php
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;
use App\Core\Auth;

$comp = $company ?? CompanySetting::getAll();
$logoSrc = PrintDocumentHelper::getLogoSrc($comp);
$startDateStr = date('d F Y', strtotime($startDate ?? date('Y-m-01')));
$endDateStr = date('d F Y', strtotime($endDate ?? date('Y-m-d')));

$posRevenue = (float)($posRevenue ?? $pnl['omzet_pos'] ?? 0);
$b2bRevenue = (float)($b2bRevenue ?? $pnl['omzet_b2b'] ?? 0);
$consRevenue = (float)($consRevenue ?? $pnl['omzet_konsinyasi'] ?? 0);
$totalRevenue = (float)($totalRevenue ?? $pnl['total_omzet'] ?? ($posRevenue + $b2bRevenue + $consRevenue));

$posHpp = (float)($posHpp ?? $pnl['hpp_pos'] ?? 0);
$b2bHpp = (float)($b2bHpp ?? $pnl['hpp_b2b'] ?? 0);
$consHpp = (float)($consHpp ?? $pnl['hpp_konsinyasi'] ?? 0);
$consLoss = (float)($consLoss ?? $pnl['rugi_konsinyasi'] ?? 0);
$totalCogs = (float)($totalCogs ?? $pnl['total_beban_pokok'] ?? ($posHpp + $b2bHpp + $consHpp));
$grossProfit = (float)($grossProfit ?? $pnl['laba_kotor'] ?? ($totalRevenue - $totalCogs));

$expenseRows = $expenseRows ?? $pnl['beban_operasional_list'] ?? [];
$totalOperationalExpense = (float)($totalOperationalExpense ?? $pnl['total_beban_operasional'] ?? 0);
$netProfit = (float)($netProfit ?? $pnl['laba_bersih_final'] ?? ($grossProfit - $totalOperationalExpense));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi Eksekutif - <?= htmlspecialchars($comp['nama'] ?? 'KEREN SNACK') ?></title>
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
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        /* KOP HEADER */
        .kop-table { width: 100%; margin-bottom: 4px; }
        .company-name { font-size: 14pt; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px; margin-bottom: 2px; }
        .company-tagline { font-size: 8pt; font-weight: bold; color: #475569; margin-bottom: 2px; }
        .company-contact { font-size: 7pt; color: #64748b; line-height: 1.3; }

        .doc-title-main { font-size: 11pt; font-weight: bold; color: #0f172a; text-align: right; letter-spacing: 0.5px; }
        .doc-title-sub { font-size: 7.5pt; font-weight: bold; color: #881337; text-align: right; letter-spacing: 0.8px; margin-bottom: 4px; }

        .divider-double {
            border-top: 1.5px solid #881337;
            border-bottom: 0.5px solid #881337;
            height: 1.5px;
            margin: 4px 0 8px 0;
        }

        /* METADATA BOX */
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

        /* DATA TABLE */
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
            background: #fff1f2;
            font-weight: bold;
            color: #881337;
            border-top: 1px solid #fecdd3;
            border-bottom: 1px solid #fecdd3;
        }
        .grand-total-row {
            background: #ecfdf5;
            font-weight: bold;
            font-size: 8.5pt;
            border-top: 1.5px solid #059669;
            border-bottom: 2px solid #059669;
        }

        /* SIGNATURE SECTION */
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
            white-space: nowrap;
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
                <div class="doc-title-main">LAPORAN KINERJA KEUANGAN</div>
                <div class="doc-title-sub">RINGKASAN EKSEKUTIF LABA RUGI (P&amp;L)</div>
                <div style="font-size:7.5pt; color:#64748b; margin-top:2px;">
                    Dokumen Audit Finansial Manajemen
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
            <td style="width: 18%;" class="meta-label">Waktu Cetak</td>
            <td style="width: 32%;">: <?= date('d/m/Y H:i') ?> WIB</td>
        </tr>
        <tr>
            <td class="meta-label">Dicetak Oleh</td>
            <td>: <?= htmlspecialchars(Auth::name()) ?> (<?= ucfirst(Auth::role()) ?>)</td>
            <td class="meta-label">Status Dokumen</td>
            <td>: <strong style="color:#059669;">RESMI / TERPOSTING</strong></td>
        </tr>
    </table>

    <!-- DATA TABLE P&L -->
    <table class="report-table">
        <thead>
            <tr>
                <th class="text-left" style="width: 55%;">Keterangan Komponen Finansial</th>
                <th class="text-right" style="width: 25%;">Nominal (Rp)</th>
                <th class="text-right" style="width: 20%;">Rasio (%)</th>
            </tr>
        </thead>
        <tbody>
            <!-- 1. PENDAPATAN -->
            <tr class="section-header">
                <td colspan="3">1. PENDAPATAN USAHA (REVENUE)</td>
            </tr>
            <tr>
                <td>&bull; Penjualan Kasir POS (Ritel Walk-in)</td>
                <td class="text-right"><?= Format::rupiah($posRevenue) ?></td>
                <td class="text-right"><?= $totalRevenue > 0 ? number_format(($posRevenue / $totalRevenue) * 100, 1) : '0' ?>%</td>
            </tr>
            <tr>
                <td>&bull; Penjualan Pesanan Pelanggan B2B / Grosir</td>
                <td class="text-right"><?= Format::rupiah($b2bRevenue) ?></td>
                <td class="text-right"><?= $totalRevenue > 0 ? number_format(($b2bRevenue / $totalRevenue) * 100, 1) : '0' ?>%</td>
            </tr>
            <tr>
                <td>&bull; Penjualan Konsinyasi (Titip Jual Rak Mitra)</td>
                <td class="text-right"><?= Format::rupiah($consRevenue) ?></td>
                <td class="text-right"><?= $totalRevenue > 0 ? number_format(($consRevenue / $totalRevenue) * 100, 1) : '0' ?>%</td>
            </tr>
            <tr class="subtotal-row">
                <td>TOTAL PENDAPATAN (OMZET KOTOR)</td>
                <td class="text-right"><?= Format::rupiah($totalRevenue) ?></td>
                <td class="text-right">100.0%</td>
            </tr>

            <!-- 2. HPP & BEBAN POKOK -->
            <tr class="section-header">
                <td colspan="3">2. BEBAN POKOK PRODUK TERJUAL (HPP / COGS)</td>
            </tr>
            <tr>
                <td>&bull; HPP Penjualan Kasir POS</td>
                <td class="text-right"><?= Format::rupiah($posHpp) ?></td>
                <td class="text-right">-</td>
            </tr>
            <tr>
                <td>&bull; HPP Penjualan Pesanan B2B</td>
                <td class="text-right"><?= Format::rupiah($b2bHpp) ?></td>
                <td class="text-right">-</td>
            </tr>
            <tr>
                <td>&bull; HPP Penjualan Titip Jual Konsinyasi</td>
                <td class="text-right"><?= Format::rupiah($consHpp) ?></td>
                <td class="text-right">-</td>
            </tr>
            <tr class="subtotal-row">
                <td>TOTAL BEBAN POKOK (HPP)</td>
                <td class="text-right"><?= Format::rupiah($totalCogs) ?></td>
                <td class="text-right"><?= $totalRevenue > 0 ? number_format(($totalCogs / $totalRevenue) * 100, 1) : '0' ?>%</td>
            </tr>
            <?php if ($consLoss > 0): ?>
            <tr>
                <td style="color:#64748b; font-style:italic;">&bull; Catatan Analitik: Kerugian Retur Rusak/Basi Konsinyasi</td>
                <td class="text-right" style="color:#dc2626; font-style:italic;"><?= Format::rupiah($consLoss) ?></td>
                <td class="text-right" style="color:#dc2626; font-style:italic;"><?= $totalRevenue > 0 ? number_format(($consLoss / $totalRevenue) * 100, 2) : '0' ?>%</td>
            </tr>
            <?php endif; ?>

            <!-- MARGIN LABA KOTOR -->
            <tr style="background:#f8fafc; font-weight:bold;">
                <td>MARGIN LABA KOTOR (GROSS PROFIT)</td>
                <td class="text-right" style="color:#059669; font-size:8pt;"><?= Format::rupiah($grossProfit) ?></td>
                <td class="text-right" style="color:#059669;"><?= $totalRevenue > 0 ? number_format(($grossProfit / $totalRevenue) * 100, 1) : '0' ?>%</td>
            </tr>

            <!-- 3. BEBAN OPERASIONAL -->
            <tr class="section-header">
                <td colspan="3">3. BEBAN OPERASIONAL KAS (EXPENSES)</td>
            </tr>
            <?php if (!empty($expenseRows)): ?>
                <?php foreach ($expenseRows as $er): ?>
                <tr>
                    <td>&bull; Beban <?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)$er['kategori']))) ?></td>
                    <td class="text-right"><?= Format::rupiah((float)$er['total_beban']) ?></td>
                    <td class="text-right"><?= $totalRevenue > 0 ? number_format(((float)$er['total_beban'] / $totalRevenue) * 100, 1) : '0' ?>%</td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" class="text-center" style="color:#94a3b8;">Tidak ada pengeluaran kas operasional pada periode ini</td></tr>
            <?php endif; ?>
            <tr class="subtotal-row">
                <td>TOTAL BEBAN OPERASIONAL</td>
                <td class="text-right"><?= Format::rupiah($totalOperationalExpense) ?></td>
                <td class="text-right"><?= $totalRevenue > 0 ? number_format(($totalOperationalExpense / $totalRevenue) * 100, 1) : '0' ?>%</td>
            </tr>

            <!-- LABA BERSIH -->
            <tr class="grand-total-row" style="background: <?= $netProfit >= 0 ? '#ecfdf5' : '#fef2f2' ?>;">
                <td style="color: <?= $netProfit >= 0 ? '#065f46' : '#991b1b' ?>;">ESTIMASI LABA BERSIH (NET PROFIT)</td>
                <td class="text-right" style="color: <?= $netProfit >= 0 ? '#047857' : '#dc2626' ?>; font-size:9pt;"><?= Format::rupiah($netProfit) ?></td>
                <td class="text-right" style="color: <?= $netProfit >= 0 ? '#047857' : '#dc2626' ?>; font-weight:bold;"><?= $totalRevenue > 0 ? number_format(($netProfit / $totalRevenue) * 100, 1) : '0' ?>%</td>
            </tr>
        </tbody>
    </table>

    <!-- SIGNATURE SECTION -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-title">Disusun Oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( <?= htmlspecialchars(Auth::name()) ?> )</div>
                <div class="signature-role">Accounting &amp; Finance Staff</div>
            </td>
            <td>
                <div class="signature-title">Diperiksa Oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( .................... )</div>
                <div class="signature-role">Finance Manager / Supervisor</div>
            </td>
            <td>
                <div class="signature-title">Disetujui &amp; Disahkan Oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( .................... )</div>
                <div class="signature-role">Direktur / Owner Perusahaan</div>
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer-note">
        <div class="footer-left">
            Dokumen Laporan Keuangan Sah • Diterbitkan oleh Keren One ERP pada <?= date('d/m/Y H:i:s') ?> WIB
        </div>
        <div class="footer-right">
            Periode: <?= $startDateStr ?> - <?= $endDateStr ?>
        </div>
    </div>

    <!-- DOMPDF_PAGE_NUMBERS -->
</body>
</html>

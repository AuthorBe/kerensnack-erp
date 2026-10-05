<?php
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;
use App\Core\Auth;

$comp = $company ?? CompanySetting::getAll();
$logoSrc = PrintDocumentHelper::getLogoSrc($comp);
$invoices = $invoices ?? [];
$status = (string)($status ?? $statusLabel ?? 'Semua Status');
$totalPiutang = (float)($totalPiutang ?? $grandTotalSisa ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Tagihan Piutang Konsinyasi - <?= htmlspecialchars($comp['nama'] ?? 'KEREN SNACK') ?></title>
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
        .doc-title-sub { font-size: 7.5pt; font-weight: bold; color: #d97706; text-align: right; letter-spacing: 0.8px; margin-bottom: 4px; }

        .divider-double {
            border-top: 1.5px solid #d97706;
            border-bottom: 0.5px solid #d97706;
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
        .report-table tr:nth-child(even) td { background: #f8fafc; }
        .grand-total-row td {
            background: #f1f5f9;
            font-weight: bold;
            font-size: 7.5pt;
            border-top: 1.5px solid #334155;
            border-bottom: 2px solid #334155;
            padding: 6px 6px;
        }

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
                <div class="doc-title-main">REKAP TAGIHAN &amp; PIUTANG</div>
                <div class="doc-title-sub">OUTSTANDING KONSINYASI (TITIP JUAL RAK)</div>
                <div style="font-size:7.5pt; color:#64748b; margin-top:2px;">
                    Daftar Piutang Dagang Toko Mitra
                </div>
            </td>
        </tr>
    </table>

    <div class="divider-double"></div>

    <!-- METADATA BOX -->
    <table class="meta-box">
        <tr>
            <td style="width: 18%;" class="meta-label">Status Tagihan</td>
            <td style="width: 32%;">: <strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $status ?? 'Semua Tagihan'))) ?></strong></td>
            <td style="width: 18%;" class="meta-label">Total Faktur Tagihan</td>
            <td style="width: 32%;">: <strong><?= count($invoices) ?> Nota</strong></td>
        </tr>
        <tr>
            <td class="meta-label">Waktu Cetak</td>
            <td>: <?= date('d/m/Y H:i') ?> WIB</td>
            <td class="meta-label">Total Sisa Piutang</td>
            <td>: <strong style="color:#dc2626; font-size:8pt;"><?= Format::rupiah($totalPiutang) ?></strong></td>
        </tr>
    </table>

    <!-- DATA TABLE -->
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 18pt;" class="text-center">No</th>
                <th style="width: 58pt;" class="text-center">No. Tagihan</th>
                <th class="text-left">Toko Mitra Konsinyasi</th>
                <th style="width: 50pt;" class="text-left">Sales PIC</th>
                <th style="width: 44pt;" class="text-center">Tgl Nota</th>
                <th style="width: 44pt;" class="text-center">Jatuh Tempo</th>
                <th style="width: 55pt;" class="text-right">Total Tagih (Rp)</th>
                <th style="width: 52pt;" class="text-right">Terbayar (Rp)</th>
                <th style="width: 55pt;" class="text-right">Sisa Piutang (Rp)</th>
                <th style="width: 40pt;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $sumTagihan = 0.0;
            $sumBayar = 0.0;
            $sumSisa = 0.0;
            foreach ($invoices as $inv): 
                $tagih = (float)$inv['total_netto'];
                $bayar = (float)$inv['total_dibayar'];
                $sisa = (float)$inv['sisa_tagihan'];
                $sumTagihan += $tagih;
                $sumBayar += $bayar;
                $sumSisa += $sisa;
            ?>
            <tr>
                <td class="text-center" style="color:#64748b;"><?= $no++ ?></td>
                <td class="text-center font-bold"><?= htmlspecialchars($inv['nomor_nota']) ?></td>
                <td>
                    <div style="font-weight:bold; color:#0f172a;"><?= htmlspecialchars($inv['nama_toko']) ?></div>
                    <?php if (!empty($inv['nomor_whatsapp'])): ?>
                    <div style="font-size:6.5pt; color:#64748b;">WA: <?= htmlspecialchars($inv['nomor_whatsapp']) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($inv['nama_sales'] ?? '-') ?></td>
                <td class="text-center"><?= date('d/m/y', strtotime((string)$inv['tanggal_pesanan'])) ?></td>
                <td class="text-center" style="color: <?= !empty($inv['tanggal_jatuh_tempo']) && strtotime((string)$inv['tanggal_jatuh_tempo']) < time() ? '#dc2626' : '#475569' ?>;">
                    <?= !empty($inv['tanggal_jatuh_tempo']) ? date('d/m/y', strtotime((string)$inv['tanggal_jatuh_tempo'])) : '-' ?>
                </td>
                <td class="text-right"><?= number_format($tagih, 0, ',', '.') ?></td>
                <td class="text-right" style="color:#059669;"><?= number_format($bayar, 0, ',', '.') ?></td>
                <td class="text-right font-bold" style="color:#dc2626;"><?= number_format($sisa, 0, ',', '.') ?></td>
                <td class="text-center" style="font-size:6.5pt; font-weight:bold; color: <?= $inv['status_pembayaran'] === 'lunas' ? '#059669' : '#b45309' ?>;">
                    <?= strtoupper((string)$inv['status_pembayaran']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <td colspan="6" class="text-right uppercase">TOTAL REKAPITULASI PIUTANG KONSINYASI:</td>
                <td class="text-right"><?= number_format($sumTagihan, 0, ',', '.') ?></td>
                <td class="text-right" style="color:#059669;"><?= number_format($sumBayar, 0, ',', '.') ?></td>
                <td class="text-right font-bold" style="color:#dc2626; font-size:8pt;"><?= Format::rupiah($sumSisa) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- SIGNATURE SECTION -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-title">Petugas Penagihan / Kolektor,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( <?= htmlspecialchars(Auth::name()) ?> )</div>
                <div class="signature-role">Sales &amp; Billing Staff</div>
            </td>
            <td>
                <div class="signature-title">Bagian Verifikasi Piutang,</div>
                <div class="signature-space"></div>
                <div class="signature-name">( ........................................ )</div>
                <div class="signature-role">Accounting &amp; AR Controller</div>
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
            Dokumen Rekapitulasi Piutang Sah • Dicetak oleh Keren One ERP pada <?= date('d/m/Y H:i:s') ?> WIB
        </div>
        <div class="footer-right">
            Sisa Piutang: <?= Format::rupiah($totalPiutang) ?>
        </div>
    </div>

    <!-- DOMPDF_PAGE_NUMBERS -->
</body>
</html>

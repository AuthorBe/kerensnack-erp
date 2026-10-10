<?php
/**
 * views/penggajian/rekap_pdf.php
 * Template Rekapitulasi Penggajian (A4 Landscape PDF)
 */
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;

$company = $company ?? CompanySetting::getAll();
$logoSrc = PrintDocumentHelper::getAppLogoSrc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Penggajian - <?= htmlspecialchars($run['nomor_referensi']) ?></title>
    <style>
        @page {
            margin: 10mm 12mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #1c1917;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #881337;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .title {
            font-size: 13pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .subtitle {
            font-size: 9pt;
            color: #57534e;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .data-table th {
            background-color: #f5f5f4;
            color: #1c1917;
            padding: 5px 6px;
            text-transform: uppercase;
            font-size: 7.5pt;
            border: 1px solid #d6d3d1;
            text-align: left;
        }
        .data-table td {
            padding: 4px 6px;
            border: 1px solid #e7e5e4;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .font-mono {
            font-family: 'Helvetica', 'Arial', sans-serif; font-variant-numeric: tabular-nums;
        }
        .total-row td {
            background-color: #f5f5f4;
            font-weight: bold;
            border-top: 2px solid #881337;
        }
        .footer-table {
            width: 100%;
            margin-top: 15px;
            font-size: 8pt;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 65%; vertical-align: middle;">
                <table style="width: auto; border-collapse: collapse;">
                    <tr>
                        <?php if (!empty($logoSrc)): ?>
                        <td style="width: 42px; vertical-align: middle; padding-right: 10px;">
                            <img src="<?= $logoSrc ?>" alt="Logo App" style="width: 36px; height: 36px; display: block;">
                        </td>
                        <?php endif; ?>
                        <td style="vertical-align: middle;">
                            <div class="title">REKAPITULASI PENGGAJIAN KARYAWAN</div>
                            <div class="subtitle"><?= htmlspecialchars($company['nama'] ?? 'KEREN SNACK INDONESIA') ?> — <?= htmlspecialchars($run['nama_payroll'] ?: $run['nomor_referensi']) ?></div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 35%; text-align: right; vertical-align: middle;">
                <div><strong>No. Ref:</strong> <?= htmlspecialchars($run['nomor_referensi']) ?></div>
                <div class="subtitle">Periode: <?= Format::tanggalIndo($run['periode_awal']) ?> s/d <?= Format::tanggalIndo($run['periode_akhir']) ?></div>
            </td>
        </tr>
    </table>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th>Nama Karyawan</th>
                <th>Posisi</th>
                <th class="text-center">Tipe</th>
                <th class="text-center">Metode</th>
                <th class="text-center">Hadir</th>
                <th class="text-right">Gaji Pokok / Upah</th>
                <th class="text-right">Uang Hadir</th>
                <th class="text-right">Lembur/Komisi</th>
                <th class="text-right">Tunjangan</th>
                <th class="text-right">Pot. Kasbon</th>
                <th class="text-right">Pot. Lain/Adv</th>
                <th class="text-right font-bold">Gaji Bersih</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $totGapok = 0;
                $totUangHadir = 0;
                $totLemburKomisi = 0;
                $totTunjangan = 0;
                $totKasbon = 0;
                $totPotLain = 0;
                $totNet = 0;
                $totNetTunai = 0;
                $countNetTunai = 0;
                $totNetTransfer = 0;
                $countNetTransfer = 0;
                $transferItems = [];
                $no = 1;
            ?>
            <?php foreach ($items as $item): ?>
            <?php
                $gapok = ($item['tipe_penggajian'] === 'bulanan') ? (float)$item['gaji_pokok'] : (float)$item['total_upah_borongan'];
                $uangHadir = (float)$item['total_uang_kehadiran'];
                $lemburKomisi = (float)$item['total_upah_lembur'] + (float)$item['total_komisi_sales'];
                $tunj = (float)$item['tunjangan_bulanan'] + (float)$item['tunjangan_lain'] + (float)$item['penarikan_tabungan'];
                $kasbon = (float)$item['total_potongan_kasbon'];
                $potLain = (float)$item['potongan_lain'] + (float)$item['total_potongan_tabungan'] + (float)$item['total_penarikan_gaji'] - (float)$item['nominal_pembulatan'];
                $net = (float)$item['gaji_bersih_diterima'];
                $metode = (string)($item['metode_pembayaran'] ?? 'tunai');

                $totGapok += $gapok;
                $totUangHadir += $uangHadir;
                $totLemburKomisi += $lemburKomisi;
                $totTunjangan += $tunj;
                $totKasbon += $kasbon;
                $totPotLain += $potLain;
                $totNet += $net;

                if ($metode === 'transfer') {
                    $totNetTransfer += $net;
                    $countNetTransfer++;
                    $transferItems[] = $item;
                } else {
                    $totNetTunai += $net;
                    $countNetTunai++;
                }
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="font-bold"><?= htmlspecialchars($item['nama_karyawan']) ?></td>
                <td><?= htmlspecialchars($item['posisi'] ?? '-') ?></td>
                <td class="text-center"><?= ucfirst($item['tipe_penggajian']) ?></td>
                <td class="text-center" style="font-size: 7pt;">
                    <?= $metode === 'transfer' ? ('TF (' . htmlspecialchars($item['bank_nama'] ?: 'Bank') . ')') : 'Tunai' ?>
                </td>
                <td class="text-center"><?= $item['hari_hadir'] ?></td>
                <td class="text-right font-mono"><?= Format::rupiah($gapok) ?></td>
                <td class="text-right font-mono"><?= Format::rupiah($uangHadir) ?></td>
                <td class="text-right font-mono"><?= Format::rupiah($lemburKomisi) ?></td>
                <td class="text-right font-mono"><?= Format::rupiah($tunj) ?></td>
                <td class="text-right font-mono" style="color: #b91c1c;"><?= Format::rupiah($kasbon) ?></td>
                <td class="text-right font-mono" style="color: #b91c1c;"><?= Format::rupiah($potLain) ?></td>
                <td class="text-right font-mono font-bold"><?= Format::rupiah($net) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6" class="text-center">TOTAL KESELURUHAN (<?= count($items) ?> KARYAWAN)</td>
                <td class="text-right font-mono"><?= Format::rupiah($totGapok) ?></td>
                <td class="text-right font-mono"><?= Format::rupiah($totUangHadir) ?></td>
                <td class="text-right font-mono"><?= Format::rupiah($totLemburKomisi) ?></td>
                <td class="text-right font-mono"><?= Format::rupiah($totTunjangan) ?></td>
                <td class="text-right font-mono" style="color: #b91c1c;"><?= Format::rupiah($totKasbon) ?></td>
                <td class="text-right font-mono" style="color: #b91c1c;"><?= Format::rupiah($totPotLain) ?></td>
                <td class="text-right font-mono font-bold" style="color: #881337; font-size: 9pt;"><?= Format::rupiah($totNet) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Ringkasan Alokasi Pembayaran (Tunai vs Transfer Bank) -->
    <table style="width: 100%; margin-top: 10px; border-collapse: collapse; font-size: 8pt;">
        <tr>
            <td style="width: 48%; vertical-align: top; border: 1px solid #cbd5e1; padding: 6px 8px; background: #f8fafc;">
                <div style="font-weight: bold; color: #0f172a; margin-bottom: 4px; font-size: 8.5pt;">ALOKASI KAS TUNAI (AMPLOP GAJI)</div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="color: #64748b;">Jumlah Karyawan Tunai:</td>
                        <td class="text-right font-bold"><?= $countNetTunai ?> Orang</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;">Total Beban Kas Tunai:</td>
                        <td class="text-right font-mono font-bold" style="color: #059669; font-size: 9.5pt;"><?= Format::rupiah($totNetTunai) ?></td>
                    </tr>
                </table>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%; vertical-align: top; border: 1px solid #cbd5e1; padding: 6px 8px; background: #f8fafc;">
                <div style="font-weight: bold; color: #0f172a; margin-bottom: 4px; font-size: 8.5pt;">ALOKASI TRANSFER BANK (REKENING USAHA)</div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="color: #64748b;">Jumlah Karyawan Transfer:</td>
                        <td class="text-right font-bold"><?= $countNetTransfer ?> Orang</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;">Total Beban Transfer Bank:</td>
                        <td class="text-right font-mono font-bold" style="color: #2563eb; font-size: 9.5pt;"><?= Format::rupiah($totNetTransfer) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Tabel Khusus Rekening Transfer Bank Karyawan -->
    <?php if (!empty($transferItems)): ?>
    <div style="font-weight: bold; color: #1e40af; margin-top: 12px; margin-bottom: 4px; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.3px;">
        RINCIAN REKENING TRANSFER BANK (<?= count($transferItems) ?> KARYAWAN)
    </div>
    <table class="data-table" style="margin-bottom: 5px;">
        <thead>
            <tr style="background-color: #eff6ff;">
                <th class="text-center" style="width: 25px; border-color: #bfdbfe; color: #1e40af;">No</th>
                <th style="border-color: #bfdbfe; color: #1e40af; width: 22%;">Nama Karyawan</th>
                <th style="border-color: #bfdbfe; color: #1e40af; width: 14%;">Posisi</th>
                <th class="text-center" style="width: 12%; border-color: #bfdbfe; color: #1e40af;">Bank Tujuan</th>
                <th class="text-center font-mono" style="width: 18%; border-color: #bfdbfe; color: #1e40af;">Nomor Rekening</th>
                <th style="border-color: #bfdbfe; color: #1e40af; width: 20%;">Atas Nama (A/N)</th>
                <th class="text-right font-bold" style="width: 14%; border-color: #bfdbfe; color: #1e40af;">Gaji Bersih (Transfer)</th>
            </tr>
        </thead>
        <tbody>
            <?php $noTf = 1; foreach ($transferItems as $tfItem): ?>
            <tr>
                <td class="text-center"><?= $noTf++ ?></td>
                <td class="font-bold"><?= htmlspecialchars($tfItem['nama_karyawan']) ?></td>
                <td><?= htmlspecialchars($tfItem['posisi'] ?? '-') ?></td>
                <td class="text-center font-bold" style="color: #1d4ed8;"><?= htmlspecialchars(!empty($tfItem['bank_nama']) ? $tfItem['bank_nama'] : '-') ?></td>
                <td class="text-center font-mono font-bold" style="letter-spacing: 0.5px;"><?= htmlspecialchars(!empty($tfItem['bank_nomor_rekening']) ? $tfItem['bank_nomor_rekening'] : '-') ?></td>
                <td><?= htmlspecialchars(!empty($tfItem['bank_atas_nama']) ? $tfItem['bank_atas_nama'] : $tfItem['nama_karyawan']) ?></td>
                <td class="text-right font-mono font-bold" style="color: #2563eb;"><?= Format::rupiah((float)$tfItem['gaji_bersih_diterima']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row" style="background-color: #eff6ff;">
                <td colspan="6" class="text-center font-bold" style="color: #1e40af; border-top: 2px solid #2563eb;">TOTAL KEBUTUHAN TRANSFER BANK (<?= count($transferItems) ?> KARYAWAN)</td>
                <td class="text-right font-mono font-bold" style="color: #1e40af; font-size: 9pt; border-top: 2px solid #2563eb;"><?= Format::rupiah($totNetTransfer) ?></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>

    <!-- Signature Table -->
    <table class="footer-table">
        <tr>
            <td style="width: 33%; text-align: center;">
                <div>Dibuat oleh,</div>
                <div style="height: 45px;"></div>
                <div style="font-weight: bold; text-decoration: underline;">Admin HR / Payroll</div>
            </td>
            <td style="width: 33%; text-align: center;">
                <div>Diperiksa oleh,</div>
                <div style="height: 45px;"></div>
                <div style="font-weight: bold; text-decoration: underline;">Finance & Kasir</div>
            </td>
            <td style="width: 34%; text-align: center;">
                <div>Disetujui oleh,</div>
                <div style="height: 45px;"></div>
                <div style="font-weight: bold; text-decoration: underline;"><?= htmlspecialchars($run['nama_approver'] ?? 'Owner / Direksi') ?></div>
                <div style="font-size: 7.5pt; color: #78716c;"><?= !empty($run['disetujui_pada']) ? date('d F Y', strtotime($run['disetujui_pada'])) : date('d F Y') ?></div>
            </td>
        </tr>
    </table>
</body>
</html>

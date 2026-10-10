<?php
/**
 * views/penggajian/slip_pdf.php
 * Template Slip Gaji Karyawan:
 * - Single Mode: A4 Portrait Individual
 * - Batch Mode: A4 Portrait 2-Kolom Berdampingan Auto-Flow (Hemat Kertas, 4-6 Slip per Halaman)
 */
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;

$company = $company ?? CompanySetting::getAll();
$logoSrc = PrintDocumentHelper::getAppLogoSrc();
$isBatch = $isBatch ?? false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $isBatch ? 'Batch Slip Gaji' : 'Slip Gaji' ?></title>
    <style>
<?php if ($isBatch): ?>
        /* ==========================================================================
           BATCH MODE: A4 PORTRAIT 2-KOLOM BERDAMPINGAN AUTO-FLOW (HEMAT KERTAS)
           ========================================================================== */
        @page {
            size: a4 portrait;
            margin: 3.5mm 4mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 7pt;
            color: #1e293b;
            line-height: 1.2;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .batch-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .batch-row {
            page-break-inside: avoid;
        }
        .batch-cell {
            width: 50%;
            vertical-align: top;
            padding: 1.5mm 2mm;
            box-sizing: border-box;
        }
        .slip-card {
            border: 1px dashed #94a3b8;
            padding: 5px 7px;
            background: #ffffff;
            position: relative;
        }
        .cut-mark {
            position: absolute;
            top: -4.5px;
            right: 6px;
            background: #ffffff;
            padding: 0 4px;
            font-size: 5pt;
            color: #94a3b8;
            letter-spacing: 0.4px;
        }
        .header-table {
            width: 100%;
            border-bottom: 1.5px solid #881337;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }
        .company-name {
            font-size: 7.5pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.1;
        }
        .company-sub {
            font-size: 5.5pt;
            color: #64748b;
            line-height: 1.1;
        }
        .doc-title {
            font-size: 8pt;
            font-weight: bold;
            text-align: right;
            color: #881337;
            line-height: 1.1;
        }
        .doc-sub {
            font-size: 5.5pt;
            color: #64748b;
            text-align: right;
            line-height: 1.1;
        }
        .info-table {
            width: 100%;
            margin-bottom: 4px;
            font-size: 6.5pt;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 1px 0;
            vertical-align: top;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5pt;
            margin-bottom: 3px;
        }
        .content-table th {
            background-color: #f1f5f9;
            color: #334155;
            padding: 2px 3px;
            font-size: 5.8pt;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
        }
        .content-table td {
            padding: 1.5px 3px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .content-table tfoot td {
            border-top: 1px solid #cbd5e1;
            border-bottom: none;
            padding-top: 2px;
            font-weight: bold;
        }
        .total-box {
            background-color: #dcfce7;
            border: none;
            padding: 4px 6px;
            margin-top: 2px;
            margin-bottom: 3px;
            width: 100%;
            border-collapse: collapse;
        }
        .total-box td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .total-net-label {
            font-size: 6.8pt;
            font-weight: bold;
            color: #166534;
            letter-spacing: 0.2px;
        }
        .total-net-val {
            font-size: 9pt;
            font-weight: bold;
            color: #15803d;
            text-align: right;
        }
        .footer-table {
            width: 100%;
            font-size: 5.5pt;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 2px;
            line-height: 1.15;
        }
<?php else: ?>
        /* ==========================================================================
           SINGLE MODE: A4 PORTRAIT INDIVIDUAL (STANDALONE DOKUMEN ELEGAN)
           ========================================================================== */
        @page {
            size: a4 portrait;
            margin: 12mm 15mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9.5pt;
            color: #1c1917;
            line-height: 1.35;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .slip-card {
            border: 1px solid #78716c;
            padding: 12px 16px;
            border-radius: 4px;
        }
        .cut-mark {
            display: none;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #881337;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 13pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-sub {
            font-size: 8pt;
            color: #78716c;
        }
        .doc-title {
            font-size: 12pt;
            font-weight: bold;
            text-align: right;
            color: #1c1917;
        }
        .doc-sub {
            font-size: 8pt;
            color: #78716c;
            text-align: right;
        }
        .info-table {
            width: 100%;
            margin-bottom: 10px;
            font-size: 9pt;
        }
        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 10px;
        }
        .content-table th {
            background-color: #f5f5f4;
            color: #44403c;
            padding: 5px 8px;
            font-size: 8pt;
            text-transform: uppercase;
            border-bottom: 1px solid #d6d3d1;
        }
        .content-table td {
            padding: 4px 8px;
            border-bottom: 1px dashed #e7e5e4;
        }
        .content-table tfoot td {
            border-top: 1px solid #d6d3d1;
            border-bottom: none;
            padding-top: 4px;
            font-weight: bold;
        }
        .total-box {
            background-color: #dcfce7;
            border: none;
            padding: 8px 12px;
            margin-top: 6px;
            margin-bottom: 10px;
            width: 100%;
            border-collapse: collapse;
        }
        .total-box td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .total-net-label {
            font-size: 10pt;
            font-weight: bold;
            color: #166534;
            letter-spacing: 0.3px;
        }
        .total-net-val {
            font-size: 13pt;
            font-weight: bold;
            color: #15803d;
            text-align: right;
        }
        .footer-table {
            width: 100%;
            font-size: 8pt;
            color: #57534e;
            border-top: 1px solid #e7e5e4;
            padding-top: 6px;
        }
<?php endif; ?>

        /* Universal shared utility classes */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold   { font-weight: bold; }
        .font-mono   { font-family: 'Helvetica', 'Arial', sans-serif; font-variant-numeric: tabular-nums; }
    </style>
</head>
<body>

<?php
/**
 * Helper renderer untuk satu kartu slip gaji
 */
$renderSlipCard = function(array $item) use ($company, $logoSrc, $isBatch): void {
    $baseIncome = ($item['tipe_penggajian'] === 'bulanan') ? (float)$item['gaji_pokok'] : (float)$item['total_upah_borongan'];
    $uangHadir = (float)$item['total_uang_kehadiran'];
    $lembur = (float)$item['total_upah_lembur'];
    $komisi = (float)$item['total_komisi_sales'];
    $tunjanganBulanan = (float)$item['tunjangan_bulanan'];
    $tunjanganLain = (float)$item['tunjangan_lain'];
    $tarikTabungan = (float)$item['penarikan_tabungan'];

    $totalKotor = $baseIncome + $uangHadir + $lembur + $komisi + $tunjanganBulanan + $tunjanganLain + $tarikTabungan;

    $potKasbon = (float)$item['total_potongan_kasbon'];
    $potLain = (float)$item['potongan_lain'];
    $setorTabungan = (float)$item['total_potongan_tabungan'];
    $penarikanGaji = (float)$item['total_penarikan_gaji'];
    $pembulatan = (float)$item['nominal_pembulatan'];

    $totalPotongan = $potKasbon + $potLain + $setorTabungan + $penarikanGaji;
    $gajiBersih = (float)$item['gaji_bersih_diterima'];

    $logoSize = $isBatch ? '20px' : '38px';
    $logoPad  = $isBatch ? '5px' : '10px';
?>
    <div class="slip-card">
        <?php if ($isBatch): ?>
        <div class="cut-mark">-- POTONG --</div>
        <?php endif; ?>

        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="vertical-align: middle;">
                    <table style="width: auto; border-collapse: collapse;">
                        <tr>
                            <?php if (!empty($logoSrc)): ?>
                            <td style="width: <?= $logoSize ?>; vertical-align: middle; padding-right: <?= $logoPad ?>;">
                                <img src="<?= $logoSrc ?>" alt="Logo App" style="width: <?= $logoSize ?>; height: <?= $logoSize ?>; display: block;">
                            </td>
                            <?php endif; ?>
                            <td style="vertical-align: middle;">
                                <div class="company-name"><?= htmlspecialchars($company['nama'] ?? 'KEREN SNACK INDONESIA') ?></div>
                                <div class="company-sub"><?= htmlspecialchars($company['alamat'] ?? '') ?></div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="vertical-align: middle; text-align: right;">
                    <div class="doc-title">SLIP GAJI</div>
                    <div class="doc-sub">No: <?= htmlspecialchars($item['nomor_referensi']) ?></div>
                </td>
            </tr>
        </table>

        <!-- Info Karyawan -->
        <table class="info-table">
            <tr>
                <td style="width: 14%; color: <?= $isBatch ? '#64748b' : '#78716c' ?>;">Nama</td>
                <td style="width: 36%; font-weight: bold; color: #0f172a;">: <?= htmlspecialchars($item['nama_karyawan']) ?></td>
                <td style="width: 15%; color: <?= $isBatch ? '#64748b' : '#78716c' ?>;">Periode</td>
                <td style="width: 35%;">: <?= Format::tanggalIndo($item['periode_awal'], false, true) ?> - <?= Format::tanggalIndo($item['periode_akhir'], false, true) ?></td>
            </tr>
            <tr>
                <td style="color: <?= $isBatch ? '#64748b' : '#78716c' ?>;">Posisi</td>
                <td>: <?= htmlspecialchars($item['posisi'] ?? '-') ?> (<?= ucfirst($item['tipe_penggajian']) ?>, <?= $item['hari_hadir'] ?> hr)</td>
                <td style="color: <?= $isBatch ? '#64748b' : '#78716c' ?>;">Metode</td>
                <td style="font-weight: 600;">: <?= ($item['metode_pembayaran'] ?? 'tunai') === 'transfer' ? ('Transfer (' . htmlspecialchars($item['bank_nama'] ?: 'Bank') . ' ' . htmlspecialchars($item['bank_nomor_rekening'] ?: '-') . ')') : 'Tunai (Cash)' ?></td>
            </tr>
        </table>

        <!-- Rincian Gaji (2 Sub-Kolom: Pendapatan & Potongan) -->
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <!-- Sub-Kolom Kiri: Pendapatan -->
                <td style="width: 50%; vertical-align: top; padding-right: <?= $isBatch ? '3px' : '6px' ?>;">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th><?= $isBatch ? 'Pendapatan' : 'Komponen Pendapatan' ?></th>
                                <th class="text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($item['tipe_penggajian'] === 'bulanan'): ?>
                            <tr>
                                <td><?= $isBatch ? 'Gaji Pokok' : 'Gaji Pokok Bulanan' ?></td>
                                <td class="text-right font-mono"><?= Format::rupiah($baseIncome) ?></td>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <td><?= $isBatch ? 'Upah Borongan' : 'Upah Borongan (Produksi)' ?></td>
                                <td class="text-right font-mono"><?= Format::rupiah($baseIncome) ?></td>
                            </tr>
                            <?php endif; ?>

                            <tr>
                                <td>Uang Hadir (<?= $item['hari_hadir'] ?> hr)</td>
                                <td class="text-right font-mono"><?= Format::rupiah($uangHadir) ?></td>
                            </tr>

                            <?php if ($lembur > 0): ?>
                            <tr>
                                <td>Upah Lembur</td>
                                <td class="text-right font-mono"><?= Format::rupiah($lembur) ?></td>
                            </tr>
                            <?php endif; ?>

                            <?php if ($komisi > 0): ?>
                            <tr>
                                <td>Komisi Sales</td>
                                <td class="text-right font-mono"><?= Format::rupiah($komisi) ?></td>
                            </tr>
                            <?php endif; ?>

                            <?php if ($tunjanganBulanan > 0): ?>
                            <tr>
                                <td><?= $isBatch ? 'Tunj. Bulanan' : 'Tunjangan Bulanan' ?></td>
                                <td class="text-right font-mono"><?= Format::rupiah($tunjanganBulanan) ?></td>
                            </tr>
                            <?php endif; ?>

                            <?php if ($tunjanganLain > 0): ?>
                            <tr>
                                <td><?= $isBatch ? 'Tunj. Lain' : 'Tunjangan Lain' ?> <?= !empty($item['catatan_tunjangan_lain']) ? '(' . htmlspecialchars($item['catatan_tunjangan_lain']) . ')' : '' ?></td>
                                <td class="text-right font-mono"><?= Format::rupiah($tunjanganLain) ?></td>
                            </tr>
                            <?php endif; ?>

                            <?php if ($tarikTabungan > 0): ?>
                            <tr>
                                <td><?= $isBatch ? 'Pencairan Tab.' : 'Pencairan Tabungan' ?></td>
                                <td class="text-right font-mono"><?= Format::rupiah($tarikTabungan) ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td><?= $isBatch ? 'Total Kotor' : 'Total Pendapatan Kotor' ?></td>
                                <td class="text-right font-mono"><?= Format::rupiah($totalKotor) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </td>

                <!-- Sub-Kolom Kanan: Potongan -->
                <td style="width: 50%; vertical-align: top; padding-left: <?= $isBatch ? '3px' : '6px' ?>;">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th><?= $isBatch ? 'Potongan' : 'Komponen Potongan' ?></th>
                                <th class="text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($potKasbon > 0): ?>
                            <tr>
                                <td><?= $isBatch ? 'Cicilan Kasbon' : 'Cicilan Kasbon / Pinjaman' ?></td>
                                <td class="text-right font-mono"><?= Format::rupiah($potKasbon) ?></td>
                            </tr>
                            <?php endif; ?>

                            <?php if ($penarikanGaji > 0): ?>
                            <tr>
                                <td><?= $isBatch ? 'Ambil Harian' : 'Ambil Uang Harian (Advance)' ?></td>
                                <td class="text-right font-mono"><?= Format::rupiah($penarikanGaji) ?></td>
                            </tr>
                            <?php endif; ?>

                            <?php if ($setorTabungan > 0): ?>
                            <tr>
                                <td><?= $isBatch ? 'Simpan Tabungan' : 'Setoran Simpanan Tabungan' ?></td>
                                <td class="text-right font-mono"><?= Format::rupiah($setorTabungan) ?></td>
                            </tr>
                            <?php endif; ?>

                            <?php if ($potLain > 0): ?>
                            <tr>
                                <td>Potongan Lain <?= !empty($item['catatan_potongan_lain']) ? '(' . htmlspecialchars($item['catatan_potongan_lain']) . ')' : '' ?></td>
                                <td class="text-right font-mono"><?= Format::rupiah($potLain) ?></td>
                            </tr>
                            <?php endif; ?>

                            <?php if ($pembulatan != 0): ?>
                            <tr>
                                <td>Pembulatan</td>
                                <td class="text-right font-mono"><?= ($pembulatan > 0 ? '+' : '') . Format::rupiah($pembulatan) ?></td>
                            </tr>
                            <?php endif; ?>

                            <?php if ($totalPotongan == 0 && $pembulatan == 0): ?>
                            <tr>
                                <td colspan="2" class="text-center" style="color: #94a3b8; font-style: italic;">Tidak ada potongan</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td><?= $isBatch ? 'Total Pot.' : 'Total Potongan' ?></td>
                                <td class="text-right font-mono" style="color: #dc2626;"><?= Format::rupiah($totalPotongan) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Total Take Home Pay Box -->
        <table class="total-box">
            <tr>
                <td class="total-net-label"><?= $isBatch ? 'GAJI BERSIH (TAKE HOME PAY)' : 'GAJI BERSIH DITERIMA (TAKE HOME PAY)' ?></td>
                <td class="total-net-val font-mono"><?= Format::rupiah($gajiBersih) ?></td>
            </tr>
        </table>

        <!-- Footer -->
        <table class="footer-table">
            <tr>
                <td style="width: <?= $isBatch ? '55%' : '50%' ?>;">
                    <?php if (!empty($item['bank_nama']) && !empty($item['bank_nomor_rekening'])): ?>
                        <div><strong>Transfer Bank:</strong> <?= htmlspecialchars($item['bank_nama']) ?> - <?= htmlspecialchars($item['bank_nomor_rekening']) ?> (a/n <?= htmlspecialchars($item['bank_atas_nama'] ?? $item['nama_karyawan']) ?>)</div>
                    <?php else: ?>
                        <div><strong>Metode Pembayaran:</strong> Tunai / Kasir</div>
                    <?php endif; ?>
                    <div style="margin-top: 1px;">Dicetak: <?= date('d/m/Y H:i') ?></div>
                </td>
                <td style="width: <?= $isBatch ? '45%' : '50%' ?>; text-align: right; vertical-align: top;">
                    <div>Disetujui: <strong><?= htmlspecialchars($item['nama_approver'] ?? 'HR / Management') ?></strong></div>
                    <div>Tanggal: <?= !empty($item['disetujui_pada']) ? date('d/m/Y', strtotime($item['disetujui_pada'])) : date('d/m/Y') ?></div>
                </td>
            </tr>
        </table>
    </div>
<?php
};
?>

<?php if ($isBatch): ?>
    <!-- BATCH MODE: GRID 2-KOLOM BERDAMPINGAN AUTO-FLOW -->
    <?php $chunks = array_chunk($items, 2); ?>
    <table class="batch-grid">
        <?php foreach ($chunks as $chunk): ?>
        <tr class="batch-row">
            <?php foreach ($chunk as $item): ?>
            <td class="batch-cell">
                <?php $renderSlipCard($item); ?>
            </td>
            <?php endforeach; ?>
            <?php if (count($chunk) === 1): ?>
            <td class="batch-cell"></td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <!-- SINGLE MODE: A4 INDIVIDUAL -->
    <?php foreach ($items as $item): ?>
        <?php $renderSlipCard($item); ?>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>

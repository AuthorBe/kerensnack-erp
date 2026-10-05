<?php
/**
 * views/penggajian/slip_pdf.php
 * Template Slip Gaji Karyawan (A5 / A4 Batch)
 */
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;

$company = $company ?? CompanySetting::getAll();
$logoSrc = PrintDocumentHelper::getLogoSrc($company);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji</title>
    <style>
        @page {
            margin: 12mm 15mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #1c1917;
            line-height: 1.35;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .page-break {
            page-break-after: always;
        }
        .slip-container {
            border: 1px solid #78716c;
            padding: 12px 16px;
            border-radius: 4px;
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
        .total-box {
            background-color: #f5f5f4;
            border: 1.5px solid #881337;
            padding: 8px 12px;
            margin-top: 6px;
            margin-bottom: 10px;
        }
        .total-net {
            font-size: 12pt;
            font-weight: bold;
            color: #881337;
        }
        .footer-table {
            width: 100%;
            font-size: 8pt;
            color: #57534e;
            border-top: 1px solid #e7e5e4;
            padding-top: 6px;
        }
    </style>
</head>
<body>
<?php foreach ($items as $idx => $item): ?>
<?php
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
?>
<div class="slip-container">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="vertical-align: middle;">
                <table style="width: auto; border-collapse: collapse;">
                    <tr>
                        <?php if (!empty($logoSrc)): ?>
                        <td style="width: 50px; vertical-align: middle; padding-right: 10px;">
                            <img src="<?= $logoSrc ?>" alt="Logo" style="max-height: 42px; max-width: 50px; object-fit: contain;">
                        </td>
                        <?php endif; ?>
                        <td style="vertical-align: middle;">
                            <div class="company-name"><?= htmlspecialchars($company['nama'] ?? 'KEREN SNACK INDONESIA') ?></div>
                            <div style="font-size: 8pt; color: #78716c;"><?= htmlspecialchars($company['alamat'] ?? '') ?></div>
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
            <td style="width: 15%; color: #78716c;">Nama</td>
            <td style="width: 35%; font-weight: bold;">: <?= htmlspecialchars($item['nama_karyawan']) ?></td>
            <td style="width: 15%; color: #78716c;">Periode</td>
            <td style="width: 35%;">: <?= Format::tanggalIndo($item['periode_awal']) ?> - <?= Format::tanggalIndo($item['periode_akhir']) ?></td>
        </tr>
        <tr>
            <td style="color: #78716c;">Posisi</td>
            <td>: <?= htmlspecialchars($item['posisi'] ?? '-') ?> (<?= ucfirst($item['tipe_penggajian']) ?>)</td>
            <td style="color: #78716c;">Kehadiran</td>
            <td>: <?= $item['hari_hadir'] ?> Hari Hadir</td>
        </tr>
    </table>

    <!-- Rincian Gaji (2 Kolom Pendapatan & Potongan) -->
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <!-- Kolom Kiri: Pendapatan -->
            <td style="width: 50%; vertical-align: top; padding-right: 6px;">
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>Komponen Pendapatan</th>
                            <th class="text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($item['tipe_penggajian'] === 'bulanan'): ?>
                        <tr>
                            <td>Gaji Pokok Bulanan</td>
                            <td class="text-right font-mono"><?= Format::rupiah($baseIncome) ?></td>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <td>Upah Borongan (Produksi)</td>
                            <td class="text-right font-mono"><?= Format::rupiah($baseIncome) ?></td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <td>Uang Kehadiran (<?= $item['hari_hadir'] ?> hr)</td>
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
                            <td>Komisi Penjualan</td>
                            <td class="text-right font-mono"><?= Format::rupiah($komisi) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($tunjanganBulanan > 0): ?>
                        <tr>
                            <td>Tunjangan Bulanan</td>
                            <td class="text-right font-mono"><?= Format::rupiah($tunjanganBulanan) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($tunjanganLain > 0): ?>
                        <tr>
                            <td>Tunjangan Lain <?= !empty($item['catatan_tunjangan_lain']) ? '(' . htmlspecialchars($item['catatan_tunjangan_lain']) . ')' : '' ?></td>
                            <td class="text-right font-mono"><?= Format::rupiah($tunjanganLain) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($tarikTabungan > 0): ?>
                        <tr>
                            <td>Pencairan Tabungan</td>
                            <td class="text-right font-mono"><?= Format::rupiah($tarikTabungan) ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="font-bold">Total Pendapatan Kotor</td>
                            <td class="text-right font-mono font-bold"><?= Format::rupiah($totalKotor) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </td>

            <!-- Kolom Kanan: Potongan -->
            <td style="width: 50%; vertical-align: top; padding-left: 6px;">
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>Komponen Potongan</th>
                            <th class="text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($potKasbon > 0): ?>
                        <tr>
                            <td>Cicilan Kasbon / Pinjaman</td>
                            <td class="text-right font-mono"><?= Format::rupiah($potKasbon) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($penarikanGaji > 0): ?>
                        <tr>
                            <td>Ambil Uang Harian (Advance)</td>
                            <td class="text-right font-mono"><?= Format::rupiah($penarikanGaji) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($setorTabungan > 0): ?>
                        <tr>
                            <td>Setoran Simpanan Tabungan</td>
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
                            <td>Penyesuaian Pembulatan</td>
                            <td class="text-right font-mono"><?= ($pembulatan > 0 ? '+' : '') . Format::rupiah($pembulatan) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($totalPotongan == 0 && $pembulatan == 0): ?>
                        <tr>
                            <td colspan="2" class="text-center" style="color: #a8a29e; font-style: italic;">Tidak ada potongan</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="font-bold">Total Potongan</td>
                            <td class="text-right font-mono font-bold" style="color: #dc2626;"><?= Format::rupiah($totalPotongan) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
    </table>

    <!-- Total Gaji Bersih Box -->
    <table class="total-box" style="width: 100%;">
        <tr>
            <td style="font-weight: bold; font-size: 10pt; color: #44403c;">GAJI BERSIH DITERIMA (TAKE HOME PAY)</td>
            <td class="text-right total-net font-mono"><?= Format::rupiah($gajiBersih) ?></td>
        </tr>
    </table>

    <!-- Footer Transfer & Approver -->
    <table class="footer-table">
        <tr>
            <td style="width: 50%;">
                <?php if (!empty($item['bank_nama']) && !empty($item['bank_nomor_rekening'])): ?>
                    <div><strong>Transfer Bank:</strong> <?= htmlspecialchars($item['bank_nama']) ?> - <?= htmlspecialchars($item['bank_nomor_rekening']) ?> (a/n <?= htmlspecialchars($item['bank_atas_nama'] ?? $item['nama_karyawan']) ?>)</div>
                <?php else: ?>
                    <div><strong>Metode Pembayaran:</strong> Tunai / Kasir</div>
                <?php endif; ?>
                <div style="margin-top: 2px;">Dicetak otomatis pada <?= date('d/m/Y H:i:s') ?></div>
            </td>
            <td style="width: 50%; text-align: right;">
                <div>Disetujui: <strong><?= htmlspecialchars($item['nama_approver'] ?? 'HR / Management') ?></strong></div>
                <div>Tanggal: <?= !empty($item['disetujui_pada']) ? date('d/m/Y', strtotime($item['disetujui_pada'])) : date('d/m/Y') ?></div>
            </td>
        </tr>
    </table>
</div>

<?php if ($isBatch && $idx < count($items) - 1): ?>
<div class="page-break"></div>
<?php endif; ?>
<?php endforeach; ?>
</body>
</html>

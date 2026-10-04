<?php
/**
 * views/penggajian/rekap_karyawan_pdf.php
 * Template Laporan Komprehensif Karyawan (A4 Portrait PDF)
 */
use App\Helpers\Format;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Riwayat Karyawan - <?= htmlspecialchars($karyawan['nama_karyawan']) ?></title>
    <style>
        @page {
            margin: 12mm 15mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #1c1917;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #881337;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .company-title {
            font-size: 13pt;
            font-weight: bold;
            color: #881337;
        }
        .doc-title {
            font-size: 11pt;
            font-weight: bold;
            text-align: right;
        }
        .section-title {
            font-size: 9.5pt;
            font-weight: bold;
            color: #881337;
            border-bottom: 1px solid #d6d3d1;
            padding-bottom: 3px;
            margin-top: 12px;
            margin-bottom: 6px;
        }
        .profile-table {
            width: 100%;
            background-color: #f5f5f4;
            border: 1px solid #e7e5e4;
            padding: 8px 12px;
            margin-bottom: 10px;
            font-size: 8.5pt;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-bottom: 10px;
        }
        .data-table th {
            background-color: #f5f5f4;
            padding: 4px 6px;
            border: 1px solid #d6d3d1;
            text-align: left;
            font-size: 7.5pt;
            text-transform: uppercase;
        }
        .data-table td {
            padding: 3.5px 6px;
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
            font-family: Courier, monospace;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="company-title"><?= htmlspecialchars($company['nama'] ?? 'KEREN SNACK INDONESIA') ?></div>
                <div style="font-size: 8pt; color: #78716c;"><?= htmlspecialchars($company['alamat'] ?? '') ?></div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="doc-title">REKAP RIWAYAT KARYAWAN</div>
                <div style="font-size: 8pt; color: #78716c;">Tahun Laporan: <?= $tahun ?></div>
            </td>
        </tr>
    </table>

    <!-- Profil Karyawan -->
    <table class="profile-table">
        <tr>
            <td style="width: 15%; color: #78716c;">Nama Karyawan</td>
            <td style="width: 35%; font-weight: bold;">: <?= htmlspecialchars($karyawan['nama_karyawan']) ?></td>
            <td style="width: 15%; color: #78716c;">Sisa Kasbon</td>
            <td style="width: 35%; font-weight: bold; color: #b91c1c;">: <?= Format::rupiah($sisaKasbon) ?></td>
        </tr>
        <tr>
            <td style="color: #78716c;">Posisi / Tipe</td>
            <td>: <?= htmlspecialchars($karyawan['posisi'] ?? '-') ?> (<?= ucfirst($karyawan['tipe_penggajian']) ?>)</td>
            <td style="color: #78716c;">Saldo Tabungan</td>
            <td style="font-weight: bold; color: #15803d;">: <?= Format::rupiah($saldoTabungan) ?></td>
        </tr>
        <tr>
            <td style="color: #78716c;">Bank / Rekening</td>
            <td>: <?= htmlspecialchars($karyawan['bank_nama'] ?? '-') ?> - <?= htmlspecialchars($karyawan['bank_nomor_rekening'] ?? '-') ?></td>
            <td style="color: #78716c;">Uang Hadir / Gapok</td>
            <td>: <?= Format::rupiah((float)$karyawan['uang_kehadiran_harian']) ?> / <?= Format::rupiah((float)$karyawan['gaji_pokok_bulanan']) ?></td>
        </tr>
    </table>

    <!-- 1. Riwayat Penggajian -->
    <div class="section-title">1. Riwayat Pembayaran Gaji (Payroll) Tahun <?= $tahun ?></div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th>No. Referensi</th>
                <th>Nama Payroll</th>
                <th>Periode</th>
                <th class="text-center">Hadir</th>
                <th class="text-right">Pot. Kasbon</th>
                <th class="text-right font-bold">Gaji Bersih</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payrollHistory)): ?>
            <tr><td colspan="7" class="text-center" style="color: #78716c;">Tidak ada riwayat payroll pada tahun <?= $tahun ?>.</td></tr>
            <?php else: ?>
            <?php $no = 1; $totGaji = 0; foreach ($payrollHistory as $ph): $totGaji += (float)$ph['gaji_bersih_diterima']; ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="font-mono"><?= htmlspecialchars($ph['nomor_referensi']) ?></td>
                <td><?= htmlspecialchars($ph['nama_payroll'] ?: $ph['nomor_referensi']) ?></td>
                <td><?= Format::tanggalIndo($ph['periode_awal']) ?> - <?= Format::tanggalIndo($ph['periode_akhir']) ?></td>
                <td class="text-center"><?= $ph['hari_hadir'] ?> hr</td>
                <td class="text-right font-mono"><?= Format::rupiah((float)$ph['total_potongan_kasbon']) ?></td>
                <td class="text-right font-mono font-bold"><?= Format::rupiah((float)$ph['gaji_bersih_diterima']) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background-color: #f5f5f4; font-weight: bold;">
                <td colspan="6" class="text-center">TOTAL DITERIMA TAHUN <?= $tahun ?></td>
                <td class="text-right font-mono" style="color: #881337;"><?= Format::rupiah($totGaji) ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 2. Riwayat Presensi Bulanan -->
    <div class="section-title">2. Rekapitulasi Presensi Bulanan</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Bulan</th>
                <th class="text-center">Hadir</th>
                <th class="text-center">Izin</th>
                <th class="text-center">Sakit</th>
                <th class="text-center">Libur</th>
                <th class="text-center">Alpa</th>
                <th class="text-right">Total Lembur (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($absensiMonthly)): ?>
            <tr><td colspan="7" class="text-center" style="color: #78716c;">Belum ada data presensi.</td></tr>
            <?php else: ?>
            <?php foreach ($absensiMonthly as $am): ?>
            <tr>
                <td class="font-bold"><?= date('F Y', strtotime($am['bulan'] . '-01')) ?></td>
                <td class="text-center font-bold" style="color: #15803d;"><?= $am['hadir'] ?></td>
                <td class="text-center" style="color: #2563eb;"><?= $am['izin'] ?></td>
                <td class="text-center" style="color: #d97706;"><?= $am['sakit'] ?></td>
                <td class="text-center" style="color: #0284c7;"><?= $am['libur'] ?? 0 ?></td>
                <td class="text-center" style="color: #dc2626;"><?= $am['alpa'] ?></td>
                <td class="text-right font-mono"><?= Format::rupiah((float)$am['total_lembur']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 3. Mutasi Tabungan Karyawan -->
    <div class="section-title">3. Mutasi Tabungan / Simpanan Karyawan</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 75px;">Tanggal</th>
                <th class="text-center" style="width: 60px;">Tipe</th>
                <th class="text-right" style="width: 100px;">Jumlah</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tabunganList)): ?>
            <tr><td colspan="4" class="text-center" style="color: #78716c;">Belum ada mutasi tabungan.</td></tr>
            <?php else: ?>
            <?php foreach (array_slice($tabunganList, 0, 10) as $tl): ?>
            <tr>
                <td><?= Format::tanggalIndo($tl['tanggal']) ?></td>
                <td class="text-center font-bold" style="color: <?= $tl['tipe'] === 'deposit' ? '#15803d' : '#dc2626' ?>;">
                    <?= $tl['tipe'] === 'deposit' ? 'Setoran' : 'Penarikan' ?>
                </td>
                <td class="text-right font-mono font-bold">
                    <?= ($tl['tipe'] === 'deposit' ? '+' : '-') . Format::rupiah((float)$tl['jumlah']) ?>
                </td>
                <td><?= htmlspecialchars($tl['keterangan'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Cetak info footer -->
    <div style="margin-top: 15px; font-size: 7.5pt; color: #78716c; text-align: right;">
        Dokumen resmi digenerate otomatis oleh Sistem KEREN ONE ERP pada <?= date('d F Y, H:i:s') ?> WIB
    </div>
</body>
</html>

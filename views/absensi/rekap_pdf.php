<?php
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;

$comp = $comp ?? $company ?? CompanySetting::getAll();

$logoPath = dirname(__DIR__, 2) . '/public/assets/favicon/apple-touch-icon.png';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
    $data = file_get_contents($logoPath);
    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Kehadiran - <?= htmlspecialchars($comp['nama'] ?? 'KEREN SNACK') ?></title>
    <style>
        @page {
            margin: 10mm 12mm 12mm 12mm;
            size: A4 landscape;
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

        .kop-table { width: 100%; margin-bottom: 6px; }
        .company-name { font-size: 13pt; font-weight: bold; color: #881337; letter-spacing: 0.3px; margin-bottom: 2px; }
        .company-tagline { font-size: 7.5pt; font-weight: bold; color: #475569; margin-bottom: 2px; }
        .company-address { font-size: 7pt; color: #64748b; line-height: 1.25; margin-bottom: 2px; }
        .company-contact { font-size: 6.8pt; color: #64748b; line-height: 1.25; }

        .doc-title-main { font-size: 11pt; font-weight: bold; color: #0f172a; text-align: right; letter-spacing: 0.5px; }
        .doc-title-sub { font-size: 8pt; font-weight: bold; color: #881337; text-align: right; letter-spacing: 0.8px; margin-bottom: 3px; }

        .divider-double {
            border-top: 1.5px solid #881337;
            border-bottom: 0.5px solid #881337;
            height: 1.5px;
            margin: 4px 0 10px 0;
        }

        .data-table {
            width: 100%;
            border: 0.5px solid #cbd5e1;
            margin-top: 6px;
        }
        .data-table th {
            background-color: #f8fafc;
            color: #334155;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 6px;
            border: 0.5px solid #cbd5e1;
        }
        .data-table td {
            padding: 4px 6px;
            border: 0.5px solid #e2e8f0;
            font-size: 7.5pt;
        }
        .data-table tr:nth-child(even) td {
            background-color: #fafaf9;
        }

        .summary-box {
            margin-top: 15px;
            width: 100%;
        }
        .signature-table {
            width: 100%;
            margin-top: 25px;
        }
        .signature-box {
            text-align: center;
            width: 30%;
        }
    </style>
</head>
<body>

    <!-- KOP HEADER -->
    <table class="kop-table">
        <tr>
            <td style="width: 58%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <?php if (!empty($logoBase64)): ?>
                        <td style="width: 48px; vertical-align: top; padding-right: 8px;">
                            <img src="<?= $logoBase64 ?>" style="width: 42px; height: 42px; border-radius: 6px; object-fit: contain;">
                        </td>
                        <?php endif; ?>
                        <td style="vertical-align: top;">
                            <div class="company-name"><?= htmlspecialchars($comp['nama'] ?? 'KEREN SNACK INDONESIA') ?></div>
                            <?php if (!empty($comp['tagline'])): ?>
                                <div class="company-tagline"><?= htmlspecialchars($comp['tagline']) ?></div>
                            <?php endif; ?>
                            <div class="company-address"><?= htmlspecialchars($comp['alamat'] ?? '') ?></div>
                            <div class="company-contact"><?= PrintDocumentHelper::formatContactLine($comp, ' &bull; ') ?></div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 42%; vertical-align: top; text-align: right;">
                <div class="doc-title-main">REKAPITULASI KEHADIRAN KARYAWAN</div>
                <div class="doc-title-sub">PERIODE: <?= Format::tanggalIndo($tglAwal) ?> s/d <?= Format::tanggalIndo($tglAkhir) ?></div>
                <div style="font-size: 7pt; color: #64748b; margin-top: 2px;">Dicetak pada: <?= date('d/m/Y H:i') ?> WIB</div>
            </td>
        </tr>
    </table>

    <div class="divider-double"></div>

    <!-- TABLE -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 150px;">Nama Karyawan</th>
                <th style="width: 80px;" class="text-center">Tipe Gaji</th>
                <th style="width: 70px;">Posisi</th>
                <th style="width: 40px;" class="text-center">Hadir</th>
                <th style="width: 40px;" class="text-center">Izin</th>
                <th style="width: 40px;" class="text-center">Sakit</th>
                <th style="width: 40px;" class="text-center">Libur</th>
                <th style="width: 40px;" class="text-center">Alpa</th>
                <th style="width: 40px;" class="text-center">Telat</th>
                <th style="width: 90px;" class="text-right">Lembur (Rp)</th>
                <th style="width: 60px;" class="text-center">% Hadir</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rekapData)): ?>
                <tr>
                    <td colspan="12" class="text-center" style="padding: 15px; color: #94a3b8;">
                        Tidak ada data rekapitulasi kehadiran pada periode ini.
                    </td>
                </tr>
            <?php else: 
                $totHadir = 0;
                $totIzin = 0;
                $totSakit = 0;
                $totLibur = 0;
                $totAlpa = 0;
                $totTelat = 0;
                $totLembur = 0;

                foreach ($rekapData as $i => $row):
                    $totHadir += $row['hari_hadir'];
                    $totIzin += $row['hari_izin'];
                    $totSakit += $row['hari_sakit'];
                    $totLibur += $row['hari_libur'];
                    $totAlpa += $row['hari_alpa'];
                    $totTelat += $row['hari_telat'];
                    $totLembur += (float)$row['total_lembur_nominal'];

                    $totalHariAktif = $row['hari_hadir'] + $row['hari_izin'] + $row['hari_sakit'] + $row['hari_alpa'];
                    $persenHadir = ($totalHariAktif > 0) ? round(($row['hari_hadir'] / $totalHariAktif) * 100, 1) : 0;
            ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td class="font-bold"><?= htmlspecialchars($row['nama_karyawan']) ?></td>
                    <td class="text-center uppercase" style="font-size: 6.5pt;"><?= htmlspecialchars($row['tipe_penggajian']) ?></td>
                    <td><?= htmlspecialchars($row['posisi'] ?? '-') ?></td>
                    <td class="text-center font-bold" style="color: #059669;"><?= $row['hari_hadir'] ?></td>
                    <td class="text-center"><?= $row['hari_izin'] ?></td>
                    <td class="text-center"><?= $row['hari_sakit'] ?></td>
                    <td class="text-center"><?= $row['hari_libur'] ?></td>
                    <td class="text-center font-bold" style="color: #e11d48;"><?= $row['hari_alpa'] ?></td>
                    <td class="text-center"><?= $row['hari_telat'] ?></td>
                    <td class="text-right"><?= Format::rupiah((float)$row['total_lembur_nominal']) ?></td>
                    <td class="text-center font-bold"><?= $persenHadir ?>%</td>
                </tr>
            <?php endforeach; ?>
                <!-- TOTAL ROW -->
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td colspan="4" class="text-center uppercase">TOTAL KESELURUHAN</td>
                    <td class="text-center" style="color: #059669;"><?= $totHadir ?></td>
                    <td class="text-center"><?= $totIzin ?></td>
                    <td class="text-center"><?= $totSakit ?></td>
                    <td class="text-center"><?= $totLibur ?></td>
                    <td class="text-center" style="color: #e11d48;"><?= $totAlpa ?></td>
                    <td class="text-center"><?= $totTelat ?></td>
                    <td class="text-right"><?= Format::rupiah($totLembur) ?></td>
                    <td class="text-center">-</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- SIGNATURES -->
    <table class="signature-table">
        <tr>
            <td class="signature-box">
                <div>Dibuat Oleh,</div>
                <div style="height: 45px;"></div>
                <div style="font-weight: bold; border-top: 0.5px solid #94a3b8; display: inline-block; padding-top: 3px; min-width: 120px;">Admin</div>
            </td>
            <td style="width: 40%;"></td>
            <td class="signature-box">
                <div>Mengetahui / Disetujui,</div>
                <div style="height: 45px;"></div>
                <div style="font-weight: bold; border-top: 0.5px solid #94a3b8; display: inline-block; padding-top: 3px; min-width: 120px;">Pimpinan / Owner</div>
            </td>
        </tr>
    </table>

</body>
</html>

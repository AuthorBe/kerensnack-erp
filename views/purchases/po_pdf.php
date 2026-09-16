<?php
use App\Helpers\Format;
use App\Helpers\CompanySetting;

$comp = CompanySetting::getAll();
$isPo = (($purchase['jenis_dokumen'] ?? 'faktur') === 'po');
$docTitle = $isPo ? 'SURAT PESANAN PEMBELIAN (PO)' : 'FAKTUR PEMBELIAN VENDOR';
$nomorDokumen = $purchase['nomor_faktur_pembelian'] ?? '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $docTitle ?> - <?= htmlspecialchars($nomorDokumen) ?></title>
    <style>
        @page {
            margin: 14mm 16mm 14mm 16mm;
            size: A4 portrait;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8.5pt;
            line-height: 1.4;
            color: #111827;
            background: #ffffff;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        /* KOP PERUSAHAAN */
        .kop-table {
            margin-bottom: 12px;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 16pt;
            font-weight: 900;
            color: #111827;
            letter-spacing: -0.5px;
        }
        .company-sub {
            font-size: 8pt;
            color: #4b5563;
            margin-top: 2px;
            line-height: 1.35;
        }
        .doc-title-box {
            text-align: right;
        }
        .doc-title {
            font-size: 13pt;
            font-weight: 900;
            color: #1e40af;
            letter-spacing: 0.3px;
        }
        .doc-number {
            font-family: 'Courier', monospace;
            font-size: 11pt;
            font-weight: bold;
            color: #111827;
            margin-top: 2px;
        }

        /* INFO BOX */
        .info-table {
            margin-bottom: 16px;
        }
        .info-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 8px 12px;
            vertical-align: top;
            width: 48%;
        }
        .info-card-title {
            font-size: 7.5pt;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
        }

        /* DATA TABLE */
        .items-table {
            margin-bottom: 16px;
        }
        .items-table th {
            background: #1f2937;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            padding: 6px 8px;
            text-transform: uppercase;
            border: 1px solid #1f2937;
        }
        .items-table td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
            font-size: 8.5pt;
        }
        .items-table tr:nth-child(even) td {
            background: #f9fafb;
        }

        /* SUMMARY & CATATAN */
        .notes-card {
            background: #fdfdfd;
            border: 1px dashed #d1d5db;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 8pt;
            line-height: 1.4;
        }
        .badge-method {
            display: inline-block;
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 7.5pt;
        }

        /* SIGNATURE */
        .sign-table {
            margin-top: 24px;
            page-break-inside: avoid;
        }
        .sign-box {
            text-align: center;
            width: 33.3%;
            vertical-align: top;
        }
        .sign-line {
            border-bottom: 1px solid #111827;
            width: 75%;
            margin: 50px auto 4px auto;
        }
    </style>
</head>
<body>

    <!-- KOP RESMI -->
    <table class="kop-table">
        <tr>
            <td style="vertical-align:top; width:60%;">
                <div class="company-name"><?= htmlspecialchars($comp['company_name'] ?? 'KEREN SNACK ERP') ?></div>
                <div class="company-sub">
                    <?= htmlspecialchars($comp['company_address'] ?? 'Pusat Industri Camilan Nusantara') ?><br>
                    Telp / WA: <?= htmlspecialchars($comp['company_phone'] ?? '-') ?> &bull; Email: <?= htmlspecialchars($comp['company_email'] ?? '-') ?>
                </div>
            </td>
            <td style="vertical-align:top; width:40%;" class="doc-title-box">
                <div class="doc-title"><?= $docTitle ?></div>
                <div class="doc-number"><?= htmlspecialchars($nomorDokumen) ?></div>
                <div style="font-size:8pt; color:#6b7280; margin-top:2px;">
                    Tanggal: <strong><?= date('d/m/Y', strtotime($purchase['tanggal_pembelian'])) ?></strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- INFO VENDOR & LOGISTIK -->
    <table class="info-table">
        <tr>
            <td class="info-card">
                <div class="info-card-title">Ditujukan Kepada (Vendor Pemasok)</div>
                <div style="font-weight:bold; font-size:10pt; color:#111827;"><?= htmlspecialchars($purchase['nama_pemasok'] ?? 'Vendor') ?></div>
                <div style="font-size:8pt; color:#4b5563; margin-top:2px; line-height:1.4;">
                    Kode: <strong><?= htmlspecialchars($purchase['kode_pemasok'] ?? '-') ?></strong>
                    <?php if (!empty($purchase['supplier_kontak'])): ?>
                        &bull; PIC: <strong><?= htmlspecialchars($purchase['supplier_kontak']) ?></strong>
                    <?php endif; ?><br>
                    Kontak: <?= htmlspecialchars($purchase['supplier_wa'] ?: ($purchase['supplier_telepon'] ?: '-')) ?>
                    <?php if (!empty($purchase['supplier_email'])): ?>
                        &bull; Email: <?= htmlspecialchars($purchase['supplier_email']) ?>
                    <?php endif; ?><br>
                    Alamat: <?= htmlspecialchars($purchase['alamat_lengkap'] ?? 'Alamat tidak diatur') ?>
                    <?php if (!empty($purchase['supplier_termin_bayar'])): ?>
                        <br>Termin Standar: <strong><?= strtoupper(str_replace('_', ' ', $purchase['supplier_termin_bayar'])) ?></strong>
                    <?php endif; ?>
                </div>
            </td>
            <td style="width:4%;"></td>
            <td class="info-card">
                <div class="info-card-title">Detail Pengambilan &amp; Pengiriman</div>
                <div style="margin-bottom:3px;">
                    Metode Logistik: 
                    <span class="badge-method">
                        <?= ($purchase['metode_logistik'] ?? '') === 'diambil_driver' ? 'DIAMBIL DRIVER TOKO' : 'DIANTAR SUPPLIER KE GUDANG' ?>
                    </span>
                </div>
                <?php if (($purchase['metode_logistik'] ?? '') === 'diambil_driver'): ?>
                <div style="font-size:8pt; color:#374151; margin-top:3px;">
                    Driver Bertugas: <strong><?= htmlspecialchars($purchase['nama_driver'] ?? 'Belum Ditugaskan') ?></strong> <?= !empty($purchase['nopol_driver']) ? ' (' . htmlspecialchars($purchase['nopol_driver']) . ')' : '' ?><br>
                    Jadwal Belanja: <strong><?= !empty($purchase['tanggal_jadwal_belanja']) ? date('d/m/Y', strtotime($purchase['tanggal_jadwal_belanja'])) : '-' ?></strong><br>
                    Metode Bayar: <strong><?= strtoupper(str_replace('_', ' ', $purchase['metode_bayar_belanja'] ?? 'TEMPO')) ?></strong>
                </div>
                <?php else: ?>
                <div style="font-size:8pt; color:#374151; margin-top:3px;">
                    Lokasi Tujuan: <strong>Gudang Pusat <?= htmlspecialchars($comp['company_name'] ?? 'Keren Snack') ?></strong><br>
                    Target Tiba: <strong><?= !empty($purchase['tanggal_jadwal_belanja']) ? date('d/m/Y', strtotime($purchase['tanggal_jadwal_belanja'])) : date('d/m/Y', strtotime($purchase['tanggal_pembelian'])) ?></strong>
                </div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <!-- TABEL ITEM PESANAN -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:30px;" class="text-center">No</th>
                <th style="width:90px;">Kode SKU</th>
                <th>Nama Bahan / Kemasan</th>
                <th style="width:70px;" class="text-center">Kuantitas</th>
                <th style="width:50px;" class="text-center">Satuan</th>
                <th style="width:100px;" class="text-right">Estimasi Harga</th>
                <th style="width:110px;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $grandTotal = 0;
            foreach ($items as $it): 
                $sub = (float)$it['subtotal'];
                $grandTotal += $sub;
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td style="font-family:monospace;"><?= htmlspecialchars($it['kode_sku'] ?? '-') ?></td>
                <td><strong><?= htmlspecialchars($it['nama_item'] ?? '-') ?></strong></td>
                <td class="text-center" style="font-weight:bold;"><?= ((float)$it['kuantitas'] == (int)$it['kuantitas']) ? number_format((float)$it['kuantitas'], 0, ',', '.') : rtrim(rtrim(number_format((float)$it['kuantitas'], 2, ',', '.'), '0'), ',') ?></td>
                <td class="text-center"><?= htmlspecialchars($it['satuan'] ?? 'pcs') ?></td>
                <td class="text-right"><?= Format::rupiah((float)$it['harga_satuan']) ?></td>
                <td class="text-right" style="font-weight:bold; color:#1e40af;"><?= Format::rupiah($sub) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right" style="font-weight:bold; background:#f9fafb; font-size:9pt;">TOTAL ESTIMASI PEMBELIAN:</td>
                <td class="text-right" style="font-weight:900; background:#eff6ff; font-size:10pt; color:#1e40af;">
                    <?= Format::rupiah($grandTotal) ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- INSTRUKSI & CATATAN -->
    <table>
        <tr>
            <td style="vertical-align:top; width:65%;">
                <div class="notes-card">
                    <strong>Catatan &amp; Instruksi Khusus:</strong><br>
                    <?= nl2br(htmlspecialchars(!empty($purchase['instruksi_driver']) ? $purchase['instruksi_driver'] : ($purchase['catatan'] ?: 'Mohon dipersiapkan barang sesuai dengan rincian kuantitas di atas dalam kondisi baik & higienis.'))) ?>
                </div>
            </td>
            <td style="width:5%;"></td>
            <td style="vertical-align:top; width:30%;">
                <div style="font-size:7.5pt; color:#6b7280; line-height:1.4;">
                    Dokumen ini sah diterbitkan melalui sistem terkomputerisasi ERP <strong><?= htmlspecialchars($comp['company_name'] ?? 'Keren Snack') ?></strong>.<br>
                    Dibuat oleh: <strong><?= htmlspecialchars($purchase['pembuat'] ?? 'Admin') ?></strong><br>
                    Waktu Cetak: <?= date('d/m/Y H:i') ?> WIB
                </div>
            </td>
        </tr>
    </table>

    <!-- TANDA TANGAN -->
    <table class="sign-table">
        <tr>
            <td class="sign-box">
                <div>Pihak Pemasok / Vendor</div>
                <div class="sign-line"></div>
                <div style="font-weight:bold;"><?= htmlspecialchars($purchase['nama_pemasok'] ?? 'Pemasok') ?></div>
            </td>
            <td class="sign-box">
                <div>Petugas Driver / Pembawa</div>
                <div class="sign-line"></div>
                <div style="font-weight:bold;"><?= htmlspecialchars($purchase['nama_driver'] ?? 'Pengemudi Armada') ?></div>
            </td>
            <td class="sign-box">
                <div>Gudang &amp; Purchasing</div>
                <div class="sign-line"></div>
                <div style="font-weight:bold;"><?= htmlspecialchars($purchase['pembuat'] ?? 'Admin Gudang') ?></div>
            </td>
        </tr>
    </table>

</body>
</html>

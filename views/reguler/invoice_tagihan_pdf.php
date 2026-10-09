<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Helpers\PrintDocumentHelper;

/**
 * Konversi Angka ke Terbilang Bahasa Indonesia Formal
 */
function terbilangRupiah(float $angka): string {
    $bilangan = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
    $angka = floor(abs($angka));
    if ($angka < 12) {
        $hasil = $bilangan[$angka];
    } elseif ($angka < 20) {
        $hasil = terbilangRupiah($angka - 10) . ' Belas';
    } elseif ($angka < 100) {
        $hasil = terbilangRupiah(floor($angka / 10)) . ' Puluh ' . terbilangRupiah($angka % 10);
    } elseif ($angka < 200) {
        $hasil = 'Seratus ' . terbilangRupiah($angka - 100);
    } elseif ($angka < 1000) {
        $hasil = terbilangRupiah(floor($angka / 100)) . ' Ratus ' . terbilangRupiah($angka % 100);
    } elseif ($angka < 2000) {
        $hasil = 'Seribu ' . terbilangRupiah($angka - 1000);
    } elseif ($angka < 1000000) {
        $hasil = terbilangRupiah(floor($angka / 1000)) . ' Ribu ' . terbilangRupiah($angka % 1000);
    } elseif ($angka < 1000000000) {
        $hasil = terbilangRupiah(floor($angka / 1000000)) . ' Juta ' . terbilangRupiah($angka % 1000000);
    } elseif ($angka < 1000000000000) {
        $hasil = terbilangRupiah(floor($angka / 1000000000)) . ' Miliar ' . terbilangRupiah(fmod($angka, 1000000000));
    } else {
        $hasil = 'Angka terlalu besar';
    }
    return trim(preg_replace('/\s+/', ' ', $hasil)) . ' Rupiah';
}

$terbilangText = terbilangRupiah($totalSisaTagihan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Surat Rekap Tagihan Faktur Tempo') ?></title>
    <link rel="icon" type="image/x-icon" href="<?= Router::asset('/favicon/favicon.ico') ?>">
    <style>
        /* ========================================================================= */
        /* STANDAR DOKUMEN CETAK OPERASIONAL RESMI A4 (MONOKROM ELEGAN BERKELAS)      */
        /* ========================================================================= */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #e2e8f0;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.45;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .page-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #ffffff;
            padding: 18mm 20mm;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        /* TOOLBAR AKSI ATAS */
        .action-toolbar {
            max-width: 210mm;
            margin: 16px auto 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 16px;
            background: #0f172a;
            border-radius: 12px;
            color: #ffffff;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-print { background: #2563eb; color: #ffffff; }
        .btn-print:hover { background: #1d4ed8; }
        .btn-close { background: #334155; color: #cbd5e1; }
        .btn-close:hover { background: #475569; color: #ffffff; }

        /* KOP SURAT */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .header-table td { vertical-align: middle; }
        .company-name {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 0.02em;
            color: #0f172a;
            text-transform: uppercase;
        }
        .company-meta {
            font-size: 10px;
            color: #475569;
            margin-top: 2px;
            line-height: 1.4;
        }

        /* JUDUL DOKUMEN */
        .doc-title-block {
            text-align: right;
        }
        .doc-title {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #0f172a;
        }
        .doc-subtitle {
            font-size: 10.5px;
            font-weight: 600;
            color: #475569;
            margin-top: 2px;
        }

        /* DUA KOLOM IDENTITAS */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .meta-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 12px;
            background: #f8fafc;
        }
        .meta-box-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .meta-row {
            display: flex;
            font-size: 10.5px;
            margin-bottom: 3px;
        }
        .meta-lbl { width: 95px; color: #475569; font-weight: 600; }
        .meta-val { flex: 1; color: #0f172a; font-weight: 700; }

        /* TABEL DAFTAR FAKTUR */
        .invoices-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-bottom: 14px;
        }
        .invoices-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-size: 10px;
            padding: 7px 8px;
            border: 1px solid #0f172a;
        }
        .invoices-table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            color: #0f172a;
        }
        .invoices-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .cell-center { text-align: center; }
        .cell-right { text-align: right; }
        .font-mono {
            font-variant-numeric: tabular-nums;
            font-feature-settings: 'tnum' 1;
        }

        /* BARIS TOTAL AKHIR */
        .total-row td {
            background: #f1f5f9;
            font-weight: 900;
            border-top: 1.5px solid #0f172a;
            border-bottom: 3px double #0f172a;
            padding: 8px;
        }

        /* TERBILANG & CATATAN TRANSFER */
        .notes-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            margin-bottom: 24px;
        }
        .notes-grid td { vertical-align: top; }
        .terbilang-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 10.5px;
            margin-bottom: 10px;
        }
        .terbilang-label { font-size: 9.5px; font-weight: 800; color: #64748b; text-transform: uppercase; }
        .terbilang-val { font-style: italic; font-weight: 800; color: #0f172a; margin-top: 2px; }

        .bank-info-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 10.5px;
            background: #ffffff;
        }
        .bank-info-title { font-weight: 800; font-size: 10px; color: #0f172a; text-transform: uppercase; margin-bottom: 3px; }

        /* TANDA TANGAN */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .sign-col {
            width: 45%;
            text-align: center;
            font-size: 10.5px;
        }
        .sign-space {
            height: 60px;
        }
        .sign-line {
            border-bottom: 1px solid #0f172a;
            display: inline-block;
            min-width: 160px;
            padding-bottom: 2px;
            font-weight: 800;
        }

        /* PRINT STYLES */
        @media print {
            body { background: #ffffff; padding: 0; }
            .action-toolbar { display: none !important; }
            .page-sheet {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            @page {
                size: A4 portrait;
                margin: 12mm 15mm;
            }
        }
    </style>
</head>
<body>

    <!-- TOOLBAR AKSI (HILANG SAAT PRINT) -->
    <div class="action-toolbar">
        <div style="font-size: 12px; font-weight: 700; display:flex; align-items:center; gap:8px;">
            <span>📄 Format Cetak Resmi A4 &bull; Rekap Tagihan Grosir B2B</span>
        </div>
        <div style="display:flex; gap:8px;">
            <button onclick="window.print()" class="action-btn btn-print">
                <span>Cetak / Simpan PDF</span>
            </button>
            <button onclick="window.close()" class="action-btn btn-close">
                <span>Tutup Halaman</span>
            </button>
        </div>
    </div>

    <!-- SHEET HALAMAN A4 -->
    <div class="page-sheet">

        <!-- KOP SURAT PERUSAHAAN -->
        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <?php if (!empty($logoSrc)): ?>
                            <td style="width: 1%; white-space: nowrap; padding-right: 12px;">
                                <img src="<?= $logoSrc ?>" alt="Logo" style="width: 130px; height: auto; max-height: 48px; display: block;">
                            </td>
                            <?php endif; ?>
                            <td>
                                <div class="company-name"><?= htmlspecialchars($comp['nama'] ?? 'KEREN ONE DISTRIBUTION') ?></div>
                                <div class="company-meta">
                                    <?php if (!empty($comp['tagline'])): ?>
                                    <div><strong><?= htmlspecialchars($comp['tagline']) ?></strong></div>
                                    <?php endif; ?>
                                    <?php if (!empty($comp['alamat'])): ?>
                                    <div><?= htmlspecialchars($comp['alamat']) ?></div>
                                    <?php endif; ?>
                                    <div><?= PrintDocumentHelper::formatContactLine($comp, ' • ') ?></div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 40%; vertical-align: top;">
                    <div class="doc-title-block">
                        <div class="doc-title">Surat Rekap Tagihan</div>
                        <div class="doc-subtitle">Nomor: <strong class="font-mono"><?= htmlspecialchars($noSuratTagihan) ?></strong></div>
                        <div style="font-size: 10px; color: #475569; margin-top: 3px;">Tanggal Terbit: <strong><?= $tanggalCetak ?></strong></div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- IDENTITAS 2 KOLOM (KIRI: MITRA TOKO | KANAN: KETENTUAN BAYAR) -->
        <table class="meta-table">
            <tr>
                <!-- KIRI: TOKO PENERIMA TAGIHAN -->
                <td style="width: 49%; vertical-align: top;">
                    <div class="meta-box">
                        <div class="meta-box-title">Ditujukan Kepada (Toko Mitra):</div>
                        <div class="meta-row">
                            <span class="meta-lbl">Nama Toko</span>
                            <span class="meta-val" style="font-size: 12px;"><?= htmlspecialchars($store['nama_toko']) ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-lbl">Kode Pelanggan</span>
                            <span class="meta-val font-mono"><?= htmlspecialchars($store['kode_pelanggan']) ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-lbl">PIC / Pemilik</span>
                            <span class="meta-val"><?= htmlspecialchars($store['nama_pemilik'] ?: '-') ?></span>
                        </div>
                        <?php if (!empty($store['alamat_lengkap'])): ?>
                        <div class="meta-row">
                            <span class="meta-lbl">Alamat Toko</span>
                            <span class="meta-val" style="font-weight: 500;"><?= htmlspecialchars($store['alamat_lengkap']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($store['nomor_whatsapp'])): ?>
                        <div class="meta-row">
                            <span class="meta-lbl">Kontak WA/Telp</span>
                            <span class="meta-val font-mono"><?= htmlspecialchars($store['nomor_whatsapp']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </td>

                <td style="width: 2%;"></td>

                <!-- KANAN: INFORMASI OPERASIONAL & PENAGIHAN -->
                <td style="width: 49%; vertical-align: top;">
                    <div class="meta-box">
                        <div class="meta-box-title">Informasi &amp; Wilayah Distribusi:</div>
                        <div class="meta-row">
                            <span class="meta-lbl">Wilayah / Rute</span>
                            <span class="meta-val"><?= htmlspecialchars($store['nama_wilayah'] ?? '—') ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-lbl">Sales Pembina</span>
                            <span class="meta-val"><?= htmlspecialchars($store['nama_sales'] ?? 'Sales Area') ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-lbl">Skema Default</span>
                            <span class="meta-val"><?= strtoupper(str_replace('_', ' ', $store['tipe_pembayaran_default'])) ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-lbl">Plafon Kredit</span>
                            <span class="meta-val font-mono"><?= (float)$store['plafon_piutang'] > 0 ? Format::rupiah((float)$store['plafon_piutang']) : 'Tanpa Limit' ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-lbl">Status Piutang</span>
                            <span class="meta-val font-mono" style="color: #b91c1c;">AKTIF BERJALAN</span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- PENGANTAR RESMI -->
        <div style="font-size: 10.5px; color: #334155; margin-bottom: 8px;">
            Berikut ini rincian faktur penjualan grosir yang telah diserahkan dan menjadi kewajiban pembayaran tempo toko Anda per tanggal <strong><?= $tanggalCetak ?></strong>:
        </div>

        <!-- TABEL RINCIAN FAKTUR -->
        <table class="invoices-table">
            <thead>
                <tr>
                    <th style="width: 28px;" class="cell-center">NO</th>
                    <th style="width: 105px;">NO. NOTA / FAKTUR</th>
                    <th style="width: 75px;" class="cell-center">TANGGAL</th>
                    <th style="width: 80px;" class="cell-center">SKEMA TEMPO</th>
                    <th style="width: 75px;" class="cell-center">JATUH TEMPO</th>
                    <th style="width: 95px;" class="cell-right">TOTAL NETTO (RP)</th>
                    <th style="width: 90px;" class="cell-right">DIBAYAR (RP)</th>
                    <th style="width: 105px;" class="cell-right">SISA TAGIHAN (RP)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $idx => $inv): 
                    $usiaHari = (int)($inv['usia_hari_nota'] ?? 0);
                ?>
                <tr>
                    <td class="cell-center font-mono font-bold"><?= $idx + 1 ?></td>
                    <td class="font-mono font-bold"><?= htmlspecialchars($inv['nomor_nota']) ?></td>
                    <td class="cell-center font-mono"><?= date('d/m/Y', strtotime($inv['tanggal_pesanan'])) ?></td>
                    <td class="cell-center">
                        <?= $inv['tipe_pembayaran'] === 'tempo_faktur' ? 'Tempo Faktur' : ($inv['tipe_pembayaran'] === 'tempo_tanggal' ? 'Tempo Tanggal' : strtoupper(str_replace('_', ' ', $inv['tipe_pembayaran']))) ?>
                    </td>
                    <td class="cell-center font-mono">
                        <?php if (!empty($inv['tanggal_jatuh_tempo'])): ?>
                            <?= date('d/m/Y', strtotime($inv['tanggal_jatuh_tempo'])) ?>
                        <?php else: ?>
                            Kiriman Berikutnya
                        <?php endif; ?>
                    </td>
                    <td class="cell-right font-mono"><?= Format::rupiah((float)$inv['total_netto']) ?></td>
                    <td class="cell-right font-mono"><?= Format::rupiah((float)$inv['total_dibayar']) ?></td>
                    <td class="cell-right font-mono font-bold"><?= Format::rupiah((float)$inv['sisa_tagihan']) ?></td>
                </tr>
                <?php endforeach; ?>

                <!-- BARIS REKAP TOTAL -->
                <tr class="total-row">
                    <td colspan="5" style="text-align: right; letter-spacing: 0.05em; font-size: 10.5px;">TOTAL KEWAJIBAN PEMBAYARAN TOKO:</td>
                    <td class="cell-right font-mono"><?= Format::rupiah((float)$totalNetto) ?></td>
                    <td class="cell-right font-mono"><?= Format::rupiah((float)$totalDibayar) ?></td>
                    <td class="cell-right font-mono font-black" style="font-size: 12px; color: #000000;"><?= Format::rupiah((float)$totalSisaTagihan) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- TERBILANG DAN REKENING PEMBAYARAN -->
        <table class="notes-grid">
            <tr>
                <td style="width: 55%; padding-right: 14px;">
                    <div class="terbilang-box">
                        <div class="terbilang-label">Total Terbilang:</div>
                        <div class="terbilang-val"><?= htmlspecialchars($terbilangText) ?></div>
                    </div>
                    <div style="font-size: 9.5px; color: #475569; line-height: 1.4;">
                        * Harap menyelesaikan pelunasan sebelum pengiriman barang jadwal berikutnya.<br>
                        * Pembayaran tunai wajib menerima kuitansi / bukti paraf resmi dari driver pengantar.
                    </div>
                </td>

                <td style="width: 45%;">
                    <div class="bank-info-box">
                        <div class="bank-info-title">Instruksi Pembayaran Transfer Bank:</div>
                        <div style="font-size: 10px; line-height: 1.45; color: #1e293b;">
                            Bank Tujuan: <strong><?= htmlspecialchars($comp['nama_bank'] ?? 'BCA / Bank Transfer') ?></strong><br>
                            Nomor Rekening: <strong class="font-mono"><?= htmlspecialchars($comp['nomor_rekening'] ?? 'Silakan hubungi Finance') ?></strong><br>
                            Atas Nama: <strong><?= htmlspecialchars($comp['atas_nama_rekening'] ?? $comp['nama'] ?? 'Keren One') ?></strong>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- KOLOM TANDA TANGAN -->
        <table class="signature-table">
            <tr>
                <td class="sign-col">
                    <div>Penerima / Pemilik Toko,</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">( <?= htmlspecialchars($store['nama_pemilik'] ?: $store['nama_toko']) ?> )</div>
                    <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Tanda Tangan &amp; Cap Toko</div>
                </td>

                <td style="width: 10%;"></td>

                <td class="sign-col">
                    <div>Bagian Keuangan &amp; Penagihan,</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">( <?= htmlspecialchars($comp['nama'] ?? 'Finance Keren One') ?> )</div>
                    <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Staff Administrasi Penjualan</div>
                </td>
            </tr>
        </table>

    </div>

</body>
</html>

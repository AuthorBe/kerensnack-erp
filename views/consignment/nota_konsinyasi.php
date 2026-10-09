<?php
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;

$comp = CompanySetting::getAll();
$logoSrc = PrintDocumentHelper::getLogoSrc($comp);

$totalLakuRp = (float)($visit['total_laku_nominal'] ?? 0);
$totalQtyKirimLalu = (int)array_sum(array_column($details, 'stok_titip_awal'));
$totalQtyKirimBaru = (int)array_sum(array_column($details, 'tambah_titip_baru'));
$totalQtyLaku = (int)array_sum(array_column($details, 'jumlah_laku_terjual'));
$totalQtyReturRusak = (int)array_sum(array_column($details, 'retur_rusak'));
$totalQtySisaRak = (int)array_sum(array_column($details, 'sisa_fisik_di_rak'));
$tipeKonsinyasi = ($visit['tipe_konsinyasi'] ?? '') === 'kolektif_tagihan' ? 'KOLEKTIF TAGIHAN' : 'ROLLING NOTA';

$rawStatus = strtoupper(trim((string)($visit['status_pembayaran'] ?? 'BELUM LUNAS')));
$statusBayar = str_replace('_', ' ', $rawStatus);

$sisaTagihan = (float)($visit['sisa_tagihan'] ?? $totalLakuRp);
$totalNetto = (float)($visit['total_netto'] ?? $totalLakuRp);
$dibayar = (float)($visit['total_dibayar'] ?? ($totalNetto - $sisaTagihan));
if ($dibayar < 0) {
    $dibayar = 0;
}

$isInvoiced = !empty($isInvoiced);
$docMainTitle = 'NOTA KHUSUS SUPPLIER KONSINYASI';
$docIdentifier = $isInvoiced ? ($visit['nomor_nota'] ?? '-') : ($visit['nomor_kunjungan'] ?? '-');

$formatMode = $formatMode ?? PrintDocumentHelper::resolveFormat($_GET['format'] ?? 'standard');
$documentTitle = 'Nota Konsinyasi - ' . htmlspecialchars($visit['nama_toko'] ?? 'Toko') . ' - ' . htmlspecialchars($docIdentifier);

$ref = strtolower(trim((string)($_GET['ref'] ?? '')));
if ($ref === 'stok-rak' || $ref === 'stok') {
    $backUrl = Router::url('/consignment/stok-rak');
} elseif ($ref === 'riwayat') {
    $backUrl = Router::url('/consignment/riwayat-kunjungan');
} elseif ($ref === 'tagihan') {
    $backUrl = Router::url('/consignment/tagihan');
} elseif ($ref === 'hasil' && !empty($visit['id'])) {
    $backUrl = Router::url('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)$visit['id']));
} else {
    // Default: jika berasal dari kunjungan/opname, kembalikan ke detail hasil opname atau stok rak
    $backUrl = !empty($visit['id']) 
        ? Router::url('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)$visit['id']))
        : Router::url('/consignment/stok-rak');
}

$pdfUrlParams = !empty($visit['pesanan_id'])
    ? '?pesanan_id=' . urlencode((string)$visit['pesanan_id'])
    : '?kunjungan_id=' . urlencode((string)($visit['id'] ?? ''));
$pdfUrl = Router::url('/consignment/nota-pdf' . $pdfUrlParams);
$enableHalfMode = true;

ob_start();
?>
<style>
/* ========================================================================= */
/* NOTA KHUSUS SUPPLIER KONSINYASI (STANDAR MODEL TOKO NUSANTARA)           */
/* ========================================================================= */

/* Styling Standar A4 / Laser / PDF */
.nusantara-header-table { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 10px; }
.nusantara-brand-name { font-size: 16pt; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; color: #0f172a; }
.nusantara-brand-sub { font-size: 8.5pt; font-weight: bold; color: #475569; }
.nusantara-title-box { text-align: right; }
.nusantara-title-text { font-size: 11pt; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; color: #000000; }
.nusantara-no-box { font-size: 10.5pt; font-weight: 800; font-family: 'Helvetica', 'Arial', sans-serif; font-variant-numeric: tabular-nums; margin-top: 3px; }

.nusantara-meta-table { width: 100%; margin-bottom: 10px; font-size: 8.5pt; }
.nusantara-meta-table td { padding: 2px 0; vertical-align: top; }

.nusantara-table { width: 100%; border-collapse: collapse; border: 1.5px solid #000000; margin-bottom: 10px; font-size: 8pt; }
.nusantara-table th { background: #f1f5f9; border: 1px solid #000000; padding: 5px 4px; font-weight: 800; text-align: center; text-transform: uppercase; font-size: 7.5pt; }
.nusantara-table td { border: 1px solid #000000; padding: 4.5px 5px; vertical-align: middle; }
.nusantara-table tfoot td { background: #f8fafc; border: 1px solid #000000; font-weight: 800; padding: 5px; font-size: 8pt; }

.nusantara-footer-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
.nusantara-slogan { font-size: 8.5pt; font-weight: 900; font-style: italic; color: #1e293b; }
.nusantara-summary-table { width: 100%; border-collapse: collapse; border: 1px solid #000000; font-size: 8.5pt; }
.nusantara-summary-table td { padding: 4px 8px; border: 1px solid #000000; }

.nusantara-sig-table { width: 100%; border-collapse: collapse; margin-top: 20px; page-break-inside: avoid; text-align: center; font-size: 8.5pt; }
.nusantara-sig-table td { vertical-align: top; width: 25%; padding: 0 4px; }
.nusantara-sig-space { height: 42px; }
.nusantara-sig-line { display: inline-block; min-width: 110px; border-bottom: 1px dashed #000000; padding-bottom: 2px; font-weight: 800; font-size: 8pt; white-space: nowrap; }

/* Styling Khusus Printer Dot Matrix (Continuous 9.5"x11" Full / 9.5"x5.5" Half) */
.dm-nusantara-wrapper {
    font-family: 'Courier New', Courier, monospace;
    font-size: 8pt;
    line-height: 1.25;
    color: #000000;
}
.dm-nusantara-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 7.5pt;
    font-family: 'Courier New', Courier, monospace;
}
.dm-nusantara-table th, .dm-nusantara-table td {
    padding: 3px 4px;
    border: 1px solid #000000;
}
</style>

<?php if (empty($isPdf) || $formatMode === 'standard'): ?>
<!-- ========================================================================= -->
<!-- 1. FORMAT STANDAR LASER / INKJET / PDF (A4 / A5)                         -->
<!-- ========================================================================= -->
<div id="sheet-standard" class="page-sheet">
    <!-- HEADER RESMI SESUAI NOTA NUSANTARA -->
    <table class="nusantara-header-table">
        <tr>
            <td style="width: 58%; vertical-align: middle;">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr>
                        <?php if (!empty($logoSrc)): ?>
                        <td style="width: 56px; vertical-align: middle; padding-right: 12px; border: none;">
                            <img src="<?= $logoSrc ?>" alt="Logo" style="max-height: 48px; max-width: 56px; object-fit: contain; display: block;">
                        </td>
                        <?php endif; ?>
                        <td style="vertical-align: middle; border: none; padding: 0;">
                            <div class="nusantara-brand-name"><?= htmlspecialchars($visit['nama_toko']) ?></div>
                            <div class="nusantara-brand-sub">SNACK &amp; KUE BASAH TRADISIONAL</div>
                            <div style="font-size: 7.5pt; color: #475569; margin-top: 2px;">
                                <?= htmlspecialchars($visit['alamat_lengkap'] ?? '-') ?> &bull; Telp/WA: <?= htmlspecialchars($visit['nomor_whatsapp'] ?: ($visit['nomor_telepon'] ?: '-')) ?>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 42%; vertical-align: middle;" class="nusantara-title-box">
                <div class="nusantara-title-text"><?= $docMainTitle ?></div>
                <div class="nusantara-no-box">NO. <?= htmlspecialchars($docIdentifier) ?></div>
                <div style="font-size: 8pt; font-weight: 700; margin-top: 3px;">
                    Tanggal: <?= date('d.m.y', strtotime($visit['tanggal_kunjungan'])) ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- META SUPPLIER & TOKO -->
    <table class="nusantara-meta-table">
        <tr>
            <td style="width: 52%;">
                <strong>Nama Supplier:</strong> <?= htmlspecialchars($comp['nama']) ?><br>
                <strong>Sales Pembina:</strong> <?= htmlspecialchars($visit['sales_name']) ?>
                <?php if (!empty($visit['driver_name']) && $visit['driver_name'] !== '-'): ?>
                    &bull; <strong>Driver:</strong> <?= htmlspecialchars($visit['driver_name']) ?>
                <?php endif; ?>
            </td>
            <td style="width: 48%; text-align: right;">
                <strong>Toko Mitra:</strong> <?= htmlspecialchars($visit['nama_toko']) ?> (<?= htmlspecialchars($visit['kode_pelanggan'] ?? '-') ?>)<br>
                <strong>Model:</strong> [ <?= $tipeKonsinyasi ?> ] &bull; <strong>Status:</strong> [ <?= $statusBayar ?> ]
            </td>
        </tr>
    </table>

    <!-- TABEL UTAMA MODEL TOKO KONSINYASI -->
    <table class="nusantara-table">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 27%; text-align: left; padding-left: 6px;">Nama Produk</th>
                <th style="width: 9%;">Sisa Stok<br>Lalu</th>
                <th style="width: 9%;">Kiriman<br>Hari Ini</th>
                <th style="width: 8%;">Retur<br>Rusak</th>
                <th style="width: 8%;">Sisa<br>di Rak</th>
                <th style="width: 8%;">Terjual<br>(Laku)</th>
                <th style="width: 12%; text-align: right; padding-right: 6px;">Harga<br>Satuan</th>
                <th style="width: 16%; text-align: right; padding-right: 6px;">Jumlah<br>Rp.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($details as $idx => $d): 
                $laku = (int)$d['jumlah_laku_terjual'];
                $kirimLalu = (int)($d['stok_titip_awal'] ?? 0);
                $kirimBaru = (int)($d['tambah_titip_baru'] ?? 0);
                $returRusak = (int)($d['retur_rusak'] ?? 0);
                $sisaRak = (int)($d['sisa_fisik_di_rak'] ?? 0);
                $hargaDeal = (float)$d['harga_satuan_deal'];
                $subtotal = (float)$d['subtotal_laku'];
                $barcode = !empty($d['barcode_universal']) && $d['barcode_universal'] !== '-' ? $d['barcode_universal'] : ($d['kode_sku'] ?? '');
            ?>
            <tr>
                <td style="text-align: center;"><?= $idx + 1 ?></td>
                <td style="padding-left: 6px;">
                    <div style="font-weight: 800; font-size: 8.5pt; color: #0f172a;">
                        <?= htmlspecialchars($d['nama_item'] ?? $d['nama_grup']) ?>
                        <?php if (!empty($barcode)): ?>
                            <span style="font-size: 7pt; color: #475569; font-weight: 600; font-family: 'Helvetica', 'Arial', sans-serif; font-variant-numeric: tabular-nums;">[<?= htmlspecialchars($barcode) ?>]</span>
                        <?php endif; ?>
                    </div>
                </td>
                <td style="text-align: center; font-weight: 600;"><?= $kirimLalu > 0 ? number_format($kirimLalu, 0, ',', '.') : '0' ?></td>
                <td style="text-align: center; font-weight: 700; background: rgba(16,185,129,0.04);"><?= $kirimBaru > 0 ? number_format($kirimBaru, 0, ',', '.') : '-' ?></td>
                <td style="text-align: center; <?= $returRusak > 0 ? 'color:#dc2626;font-weight:700;' : '' ?>"><?= $returRusak > 0 ? number_format($returRusak, 0, ',', '.') : '0' ?></td>
                <td style="text-align: center; font-weight: 700; background: rgba(2,132,199,0.03);"><?= number_format($sisaRak, 0, ',', '.') ?></td>
                <td style="text-align: center; font-weight: 800; background: rgba(16,185,129,0.08); color: #047857;"><?= number_format($laku, 0, ',', '.') ?></td>
                <td style="text-align: right; padding-right: 6px;"><?= number_format($hargaDeal, 0, ',', '.') ?></td>
                <td style="text-align: right; padding-right: 6px; font-weight: 800;"><?= number_format($subtotal, 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right; padding-right: 8px;">TOTAL KUANTITAS:</td>
                <td style="text-align: center;"><?= number_format($totalQtyKirimLalu, 0, ',', '.') ?></td>
                <td style="text-align: center;"><?= number_format($totalQtyKirimBaru, 0, ',', '.') ?></td>
                <td style="text-align: center;"><?= number_format($totalQtyReturRusak, 0, ',', '.') ?></td>
                <td style="text-align: center;"><?= number_format($totalQtySisaRak, 0, ',', '.') ?></td>
                <td style="text-align: center; color: #047857; font-weight: 900;"><?= number_format($totalQtyLaku, 0, ',', '.') ?></td>
                <td style="text-align: right; padding-right: 6px;">TOTAL:</td>
                <td style="text-align: right; padding-right: 6px; font-size: 9pt; font-weight: 900;">Rp <?= number_format($totalLakuRp, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- FOOTER: SLOGAN, TERBILANG & SUMMARY BOX -->
    <table class="nusantara-footer-table">
        <tr>
            <td style="width: 58%; vertical-align: top; padding-right: 15px;">
                <div class="nusantara-slogan">Meraih Kebarokahan Bersama Snack &amp; Kue Basah Tradisional</div>
                <div style="margin-top: 6px; font-size: 7.5pt; font-style: italic; color: #334155; border: 1px dashed #cbd5e1; padding: 6px; border-radius: 4px;">
                    Terbilang: <?= Format::terbilang($totalLakuRp) ?><br>
                    <?php if (!empty($visit['catatan'])): ?>
                    Catatan Kunjungan: <?= htmlspecialchars($visit['catatan']) ?><br>
                    <?php endif; ?>
                    <em>* Sisa Stok Lalu berasal dari jumlah sisa di rak nota sebelumnya. Nilai tagihan = Sisa Stok Lalu + Kiriman Hari Ini - (Retur Rusak + Sisa di Rak). Sisa di rak hari ini menjadi Sisa Stok Lalu untuk nota berikutnya.</em>
                </div>
            </td>
            <td style="width: 42%; vertical-align: top;">
                <table class="nusantara-summary-table">
                    <tr>
                        <td style="font-weight: 800; width: 140px; background: #f8fafc;">Total Pembayaran</td>
                        <td style="text-align: right; font-weight: 800;">Rp <?= number_format($totalLakuRp, 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700; background: #f8fafc;">Bayar</td>
                        <td style="text-align: right; font-weight: 700; color: #047857;">Rp <?= number_format($dibayar, 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 800; background: #f1f5f9;">Sisa</td>
                        <td style="text-align: right; font-weight: 900; color: <?= $sisaTagihan > 0 ? '#b91c1c' : '#047857' ?>;">Rp <?= number_format($sisaTagihan, 0, ',', '.') ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- 4 KOLOM TANDA TANGAN RESMI -->
    <table class="nusantara-sig-table">
        <tr>
            <td>
                <div style="font-weight: 800; text-transform: uppercase;">Petugas Gudang,</div>
                <div class="nusantara-sig-space"></div>
                <div class="nusantara-sig-line">( <?= htmlspecialchars(!empty($visit['nama_petugas_gudang']) ? $visit['nama_petugas_gudang'] : 'Petugas Gudang') ?> )</div>
                <div style="font-size: 7pt; color: #64748b; margin-top: 2px;">Verifikasi Kiriman</div>
            </td>
            <td>
                <div style="font-weight: 800; text-transform: uppercase;">Driver Pengantar,</div>
                <div class="nusantara-sig-space"></div>
                <div class="nusantara-sig-line">( <?= htmlspecialchars(!empty($visit['driver_name']) && $visit['driver_name'] !== '-' ? $visit['driver_name'] : 'Driver Pengantar') ?> )</div>
                <div style="font-size: 7pt; color: #64748b; margin-top: 2px;">Pengantar Barang</div>
            </td>
            <td>
                <div style="font-weight: 800; text-transform: uppercase;">Penerima Toko,</div>
                <div class="nusantara-sig-space"></div>
                <div class="nusantara-sig-line">( .................... )</div>
                <div style="font-size: 7pt; color: #64748b; margin-top: 2px;">Penerima Titip Rak</div>
            </td>
            <td>
                <div style="font-weight: 800; text-transform: uppercase;">Pemeriksa Return,</div>
                <div class="nusantara-sig-space"></div>
                <div class="nusantara-sig-line">( .................... )</div>
                <div style="font-size: 7pt; color: #64748b; margin-top: 2px;">Penghitung Stok dan Laku</div>
            </td>
        </tr>
    </table>
</div>
<?php endif; ?>

<?php if (empty($isPdf) || $formatMode !== 'standard'): ?>
<!-- ========================================================================= -->
<!-- 2. FORMAT PRINTER DOT MATRIX (CONTINUOUS 9.5"x11" FULL / 9.5"x5.5" HALF) -->
<!-- ========================================================================= -->
<div id="sheet-dotmatrix" class="continuous-wrapper dm-nusantara-wrapper">
    <div class="tractor-strip tractor-left"></div>
    <div class="continuous-inner">
        <!-- HEADER DOT MATRIX -->
        <table style="width: 100%; border-bottom: 1px solid #000000; padding-bottom: 4px; margin-bottom: 6px;">
            <tr>
                <td style="width: 55%; vertical-align: middle;">
                    <table style="width: 100%; border-collapse: collapse; border: none;">
                        <tr>
                            <?php if (!empty($logoSrc)): ?>
                            <td style="width: 64px; vertical-align: middle; padding-right: 10px; border: none;">
                                <img src="<?= $logoSrc ?>" alt="Logo" style="max-height: 54px; max-width: 64px; object-fit: contain; filter: grayscale(100%); display: block;">
                            </td>
                            <?php endif; ?>
                            <td style="vertical-align: middle; border: none; padding: 0;">
                                <div style="font-size: 11pt; font-weight: bold;"><?= htmlspecialchars($visit['nama_toko']) ?></div>
                                <div style="font-size: 7pt; font-weight: bold;">SNACK &amp; KUE BASAH TRADISIONAL</div>
                                <div style="font-size: 6.5pt;"><?= htmlspecialchars($visit['alamat_lengkap'] ?? '-') ?></div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 45%; vertical-align: middle; text-align: right;">
                    <div style="font-size: 8.5pt; font-weight: bold; text-transform: uppercase;">NOTA KHUSUS SUPPLIER TOKO</div>
                    <div style="font-size: 8pt; font-weight: bold;">NO. <?= htmlspecialchars($docIdentifier) ?></div>
                    <div style="font-size: 7pt;">Tgl: <?= date('d.m.y', strtotime($visit['tanggal_kunjungan'])) ?></div>
                </td>
            </tr>
        </table>

        <div style="font-size: 7pt; margin-bottom: 5px;">
            Supplier: <strong><?= htmlspecialchars($comp['nama']) ?></strong> &bull; Sales: <?= htmlspecialchars($visit['sales_name']) ?>
            <?php if (!empty($visit['driver_name']) && $visit['driver_name'] !== '-'): ?>
                &bull; Driver: <?= htmlspecialchars($visit['driver_name']) ?>
            <?php endif; ?>
            &bull; Model: [ <?= $tipeKonsinyasi ?> ] &bull; Status: [ <?= $statusBayar ?> ]
        </div>

        <!-- TABEL 9 KOLOM DOT MATRIX -->
        <table class="dm-nusantara-table">
            <thead>
                <tr>
                    <th style="width: 3%; text-align: center;">NO</th>
                    <th style="width: 27%; text-align: left;">NAMA PRODUK</th>
                    <th style="width: 9%; text-align: center;">SISA STOK LALU</th>
                    <th style="width: 9%; text-align: center;">KIRIM KINI</th>
                    <th style="width: 8%; text-align: center;">RETUR</th>
                    <th style="width: 8%; text-align: center;">SISA RAK</th>
                    <th style="width: 8%; text-align: center;">TERJUAL</th>
                    <th style="width: 12%; text-align: right;">HARGA</th>
                    <th style="width: 16%; text-align: right;">JUMLAH RP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($details as $idx => $d): 
                    $laku = (int)$d['jumlah_laku_terjual'];
                    $kirimLalu = (int)($d['stok_titip_awal'] ?? 0);
                    $kirimBaru = (int)($d['tambah_titip_baru'] ?? 0);
                    $returRusak = (int)($d['retur_rusak'] ?? 0);
                    $sisaRak = (int)($d['sisa_fisik_di_rak'] ?? 0);
                    $hargaDeal = (float)$d['harga_satuan_deal'];
                    $subtotal = (float)$d['subtotal_laku'];
                    $barcode = !empty($d['barcode_universal']) && $d['barcode_universal'] !== '-' ? $d['barcode_universal'] : ($d['kode_sku'] ?? '');
                ?>
                <tr>
                    <td style="text-align: center;"><?= $idx + 1 ?></td>
                    <td>
                        <strong><?= htmlspecialchars($d['nama_item'] ?? $d['nama_grup']) ?></strong>
                        <?php if (!empty($barcode)): ?>
                            <span style="font-size: 6.5pt;">[<?= htmlspecialchars($barcode) ?>]</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;"><?= $kirimLalu ?></td>
                    <td style="text-align: center;"><?= $kirimBaru > 0 ? $kirimBaru : '-' ?></td>
                    <td style="text-align: center;"><?= $returRusak ?></td>
                    <td style="text-align: center;"><?= $sisaRak ?></td>
                    <td style="text-align: center; font-weight: bold;"><?= $laku ?></td>
                    <td style="text-align: right;"><?= number_format($hargaDeal, 0, ',', '.') ?></td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($subtotal, 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold;">
                    <td colspan="2" style="text-align: right;">TOTAL:</td>
                    <td style="text-align: center;"><?= $totalQtyKirimLalu ?></td>
                    <td style="text-align: center;"><?= $totalQtyKirimBaru ?></td>
                    <td style="text-align: center;"><?= $totalQtyReturRusak ?></td>
                    <td style="text-align: center;"><?= $totalQtySisaRak ?></td>
                    <td style="text-align: center;"><?= $totalQtyLaku ?></td>
                    <td style="text-align: right;">TOTAL RP:</td>
                    <td style="text-align: right;"><?= number_format($totalLakuRp, 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- FOOTER & RINGKASAN DOT MATRIX -->
        <table style="width: 100%; margin-top: 5px; font-size: 7.5pt;">
            <tr>
                <td style="width: 55%; vertical-align: top;">
                    <em>Meraih Kebarokahan Bersama Snack &amp; Kue Basah</em><br>
                    <span style="font-size: 6.5pt;">* Sisa Stok Lalu dari sisa rak nota lalu. Tagihan = Sisa Lalu + Kirim Kini - (Retur + Sisa Rak). Sisa di rak jadi Sisa Stok Lalu nota berikutnya.</span>
                </td>
                <td style="width: 45%; vertical-align: top;">
                    <table style="width: 100%; border-collapse: collapse; border: 1px solid #000000; font-size: 7.5pt;">
                        <tr>
                            <td style="padding: 2px 4px; border: 1px solid #000000; font-weight: bold;">Total Pembayaran</td>
                            <td style="padding: 2px 4px; border: 1px solid #000000; text-align: right; font-weight: bold;"><?= number_format($totalLakuRp, 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 4px; border: 1px solid #000000;">Bayar</td>
                            <td style="padding: 2px 4px; border: 1px solid #000000; text-align: right;"><?= number_format($dibayar, 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 4px; border: 1px solid #000000; font-weight: bold;">Sisa</td>
                            <td style="padding: 2px 4px; border: 1px solid #000000; text-align: right; font-weight: bold;"><?= number_format($sisaTagihan, 0, ',', '.') ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- 4 TTD DOT MATRIX -->
        <table style="width: 100%; margin-top: 14px; text-align: center; font-size: 7pt;">
            <tr>
                <td style="width: 25%;">
                    <strong>Petugas Gudang,</strong>
                    <div style="height: 32px;"></div>
                    <div style="white-space: nowrap;">( <?= htmlspecialchars(!empty($visit['nama_petugas_gudang']) ? $visit['nama_petugas_gudang'] : 'Petugas Gudang') ?> )</div>
                    <div style="font-size: 6pt; color: #475569;">Verifikasi Kiriman</div>
                </td>
                <td style="width: 25%;">
                    <strong>Driver Pengantar,</strong>
                    <div style="height: 32px;"></div>
                    <div style="white-space: nowrap;">( <?= htmlspecialchars(!empty($visit['driver_name']) && $visit['driver_name'] !== '-' ? $visit['driver_name'] : 'Driver Pengantar') ?> )</div>
                    <div style="font-size: 6pt; color: #475569;">Pengantar Barang</div>
                </td>
                <td style="width: 25%;">
                    <strong>Penerima Toko,</strong>
                    <div style="height: 32px;"></div>
                    <div style="white-space: nowrap;">( .................... )</div>
                    <div style="font-size: 6pt; color: #475569;">Penerima Titip Rak</div>
                </td>
                <td style="width: 25%;">
                    <strong>Pemeriksa Return,</strong>
                    <div style="height: 32px;"></div>
                    <div style="white-space: nowrap;">( .................... )</div>
                    <div style="font-size: 6pt; color: #475569;">Penghitung Stok dan Laku</div>
                </td>
            </tr>
        </table>

        <div style="margin-top: 8px; font-size: 6pt; text-align: center; border-top: 1px dashed #000000; padding-top: 2px;">
            [ ] Lembar 1: Kasir/Pusat &nbsp;&bull;&nbsp; [ ] Lembar 2: Toko Mitra &nbsp;&bull;&nbsp; [ ] Lembar 3: Sales Lapangan
        </div>
    </div>
    <div class="tractor-strip tractor-right"></div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/print_frame.php';

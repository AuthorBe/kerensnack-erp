<?php
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;

$comp = CompanySetting::getAll();

$totalLakuRp = (float)($visit['total_laku_nominal'] ?? 0);
$totalQtyLaku = (int)array_sum(array_column($details, 'jumlah_laku_terjual'));
$totalQtyRusak = (int)array_sum(array_column($details, 'retur_rusak'));
$totalQtyBagus = (int)array_sum(array_column($details, 'retur_bagus'));
$totalSisaRak = (int)array_sum(array_column($details, 'sisa_fisik_di_rak'));
$totalStokAwal = (int)array_sum(array_column($details, 'stok_titip_awal'));

$rawStatus = strtoupper(trim((string)($visit['status_pembayaran'] ?? 'BELUM LUNAS')));
$statusBayar = str_replace('_', ' ', $rawStatus);

$sisaTagihan = (float)($visit['sisa_tagihan'] ?? $totalLakuRp);
$totalNetto = (float)($visit['total_netto'] ?? $totalLakuRp);
$dibayar = (float)($visit['total_dibayar'] ?? ($totalNetto - $sisaTagihan));
if ($dibayar < 0) {
    $dibayar = 0;
}

$isInvoiced = !empty($isInvoiced);
$docMainTitle = $isInvoiced ? 'FAKTUR PENJUALAN KONSINYASI' : 'BERITA ACARA AUDIT RAK';
$docSubTitle  = $isInvoiced ? 'NOTA PENAGIHAN RESMI' : 'BUKTI KUNJUNGAN & AUDIT OPNAME FISIK';
$docIdentifier = $isInvoiced ? ($visit['nomor_nota'] ?? '-') : ($visit['nomor_kunjungan'] ?? '-');

$formatMode = $formatMode ?? PrintDocumentHelper::resolveFormat($_GET['format'] ?? 'standard');
$documentTitle = $docMainTitle . ' - ' . htmlspecialchars($docIdentifier);

$backUrl = !empty($visit['pesanan_id']) 
    ? Router::url('/consignment/tagihan') 
    : Router::url('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)($visit['id'] ?? '')));

$pdfUrlParams = !empty($visit['pesanan_id'])
    ? '?pesanan_id=' . urlencode((string)$visit['pesanan_id'])
    : '?kunjungan_id=' . urlencode((string)($visit['id'] ?? ''));
$pdfUrl = Router::url('/consignment/nota-pdf' . $pdfUrlParams);
$enableHalfMode = true;

ob_start();
?>
<style>
/* STYLING SPESIFIK NOTA KONSINYASI STANDAR A4 */
.kop-table { width: 100%; margin-bottom: 4px; }
.company-name { font-size: 15pt; font-weight: bold; color: #000000; letter-spacing: 0.5px; margin-bottom: 2px; }
.company-tagline { font-size: 8pt; font-weight: bold; color: #333333; margin-bottom: 3px; }
.company-contact { font-size: 7.5pt; color: #333333; line-height: 1.35; }
.doc-title-main { font-size: 13pt; font-weight: bold; color: #000000; text-align: right; letter-spacing: 0.5px; }
.doc-title-sub { font-size: 8pt; font-weight: bold; color: #444444; text-align: right; letter-spacing: 1px; margin-bottom: 4px; }
.doc-meta-table { width: 250px; margin-left: auto; margin-top: 3px; }
.doc-meta-table td { font-size: 7.5pt; padding: 1.5px 0; vertical-align: middle; color: #000000; }
.status-box { display: inline-block; border: 1px solid #000000; padding: 1px 6px; font-weight: bold; font-size: 7.5pt; letter-spacing: 0.5px; color: #000000; }
.divider-double { border-top: 2px solid #000000; border-bottom: 0.5px solid #000000; height: 2px; margin: 6px 0 9px 0; }
.info-card-table { width: 100%; border: 1px solid #000000; margin-bottom: 9px; }
.info-card-header { background: #f0f0f0; border-bottom: 1px solid #000000; font-size: 7.5pt; font-weight: bold; padding: 4px 8px; color: #000000; text-transform: uppercase; letter-spacing: 0.5px; }
.info-card-body { padding: 6px 8px; vertical-align: top; font-size: 7.5pt; }
.info-table-inner { width: 100%; }
.info-table-inner td { padding: 2px 0; vertical-align: top; font-size: 7.5pt; color: #000000; }
.items-table { width: 100%; border: 1px solid #000000; margin-bottom: 8px; border-collapse: collapse; }
.items-table thead { display: table-header-group; }
.items-table tr { page-break-inside: avoid; }
.items-table th { background: #f0f0f0; border: 1px solid #000000; color: #000000; font-size: 7.5pt; font-weight: bold; text-transform: uppercase; padding: 5px 4px; letter-spacing: 0.2px; }
.items-table td { border: 1px solid #000000; padding: 4px 5px; font-size: 7.5pt; vertical-align: middle; color: #000000; }
.items-table tfoot { page-break-inside: avoid; }
.items-table tfoot td { background: #f5f5f5; border: 1px solid #000000; font-weight: bold; font-size: 7.5pt; padding: 5px 4px; color: #000000; }
.bottom-table { width: 100%; margin-top: 5px; page-break-inside: avoid; }
.terbilang-box { border: 1px solid #000000; background: #fafafa; padding: 5px 8px; margin-bottom: 5px; font-size: 7.5pt; color: #000000; }
.payment-info-box { border: 1px solid #000000; padding: 6px 8px; font-size: 7pt; line-height: 1.45; color: #000000; }
.calc-summary-table { width: 100%; border: 1px solid #000000; border-collapse: collapse; }
.calc-summary-table td { padding: 3.5px 6px; font-size: 7.5pt; border-bottom: 0.5px solid #cccccc; color: #000000; vertical-align: middle; }
.calc-summary-table tr.grand-row td { border-top: 1.5px solid #000000; border-bottom: 1.5px solid #000000; font-size: 8.5pt; font-weight: bold; background: #f0f0f0; color: #000000; }
.sig-table { width: 100%; margin-top: 14px; page-break-inside: avoid; }
.sig-cell { width: 50%; text-align: center; vertical-align: top; padding: 0 25px; }
.sig-title { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; color: #000000; line-height: 1.35; }
.sig-space { height: 44px; }
.sig-line { display: inline-block; min-width: 180px; border-top: 1px solid #000000; padding-top: 3px; font-weight: bold; font-size: 8pt; color: #000000; }
.sig-caption { font-size: 7pt; color: #444444; margin-top: 1px; }
.doc-footer { margin-top: 8px; text-align: center; font-size: 6.5pt; color: #555555; border-top: 0.5px solid #cccccc; padding-top: 3px; }
</style>

<?php if (empty($isPdf) || $formatMode === 'standard'): ?>
<!-- FORMAT STANDAR LASER / INKJET (A4 PORTRAIT) -->
<div id="sheet-standard" class="page-sheet">
    <!-- KOP PERUSAHAAN & JUDUL FAKTUR RESMI -->
    <table class="kop-table">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="company-name"><?= htmlspecialchars($comp['nama']) ?></div>
                <div class="company-tagline"><?= htmlspecialchars($comp['tagline']) ?></div>
                <div class="company-contact">
                    <?= htmlspecialchars($comp['alamat']) ?><br>
                    <?= PrintDocumentHelper::formatContactLine($comp, ' &bull; ') ?>
                </div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="doc-title-main"><?= $docMainTitle ?></div>
                <div class="doc-title-sub"><?= $docSubTitle ?></div>

                <table class="doc-meta-table">
                    <?php if ($isInvoiced): ?>
                    <tr>
                        <td style="width: 105px; font-weight: bold;">No. Faktur</td>
                        <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                        <td style="font-weight: bold;"><?= htmlspecialchars($visit['nomor_nota'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Tanggal Faktur</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= date('d F Y', strtotime($visit['tanggal_kunjungan'])) ?></td>
                    </tr>
                    <?php if (!empty($visit['nomor_kunjungan'])): ?>
                    <tr>
                        <td style="font-weight: bold;">Ref. Kunjungan</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($visit['nomor_kunjungan']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td style="font-weight: bold;">Status Bayar</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td>
                            <span class="status-box"><?= $statusBayar ?></span>
                        </td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td style="width: 105px; font-weight: bold;">No. Kunjungan</td>
                        <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                        <td style="font-weight: bold;"><?= htmlspecialchars($visit['nomor_kunjungan'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Tanggal Audit</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= date('d F Y', strtotime($visit['tanggal_kunjungan'])) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Status Dokumen</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td>
                            <span class="status-box">MENUNGGU TAGIHAN</span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </td>
        </tr>
    </table>

    <div class="divider-double"></div>

    <!-- INFORMASI PELANGGAN & TRANSAKSI -->
    <table class="info-card-table">
        <tr>
            <td style="width: 50%; border-right: 1px solid #000000;" class="info-card-header">
                KEPADA YTH. (TOKO MITRA)
            </td>
            <td style="width: 50%;" class="info-card-header">
                INFORMASI KUNJUNGAN &amp; PENAGIHAN
            </td>
        </tr>
        <tr>
            <td class="info-card-body" style="border-right: 1px solid #000000;">
                <table class="info-table-inner">
                    <tr>
                        <td style="width: 95px; font-weight: bold;">Nama Toko</td>
                        <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                        <td style="font-weight: bold; font-size: 8.5pt;"><?= htmlspecialchars($visit['nama_toko']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Kode Pelanggan</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($visit['kode_pelanggan'] ?? '-') ?></td>
                    </tr>
                    <?php if (!empty($visit['nama_pemilik'])): ?>
                    <tr>
                        <td style="font-weight: bold;">Pemilik / PIC</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($visit['nama_pemilik']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td style="font-weight: bold;">Alamat Toko</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($visit['alamat_lengkap'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">No. Telepon / WA</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($visit['nomor_whatsapp'] ?: ($visit['nomor_telepon'] ?: '-')) ?></td>
                    </tr>
                </table>
            </td>
            <td class="info-card-body">
                <table class="info-table-inner">
                    <tr>
                        <td style="width: 95px; font-weight: bold;">Sales Lapangan</td>
                        <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                        <td style="font-weight: bold;"><?= htmlspecialchars($visit['sales_name']) ?></td>
                    </tr>
                    <?php if (!empty($visit['auditor_name']) && strcasecmp(trim($visit['sales_name']), trim($visit['auditor_name'])) !== 0): ?>
                    <tr>
                        <td style="width: 95px; font-weight: bold; color: #64748b;">Diopname Oleh</td>
                        <td style="width: 10px; text-align: center; font-weight: bold; color: #64748b;">:</td>
                        <td style="color: #64748b; font-size: 8.5pt;"><?= htmlspecialchars($visit['auditor_name']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td style="font-weight: bold;">Tgl. Kunjungan</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= date('d/m/Y', strtotime($visit['tanggal_kunjungan'])) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Skema Transaksi</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td>Konsinyasi Rak (Titip Jual Laku)</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Jatuh Tempo</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td>Saat Kunjungan / Tunai / Transfer</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TABEL RINCIAN ITEM PRODUK RESMI -->
    <table class="items-table">
        <?php if ($isInvoiced): ?>
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">NO</th>
                <th style="width: 15%;" class="text-center">KODE SKU</th>
                <th style="width: 42%;" class="text-left" style="padding-left: 6px;">NAMA PRODUK / BARANG</th>
                <th style="width: 11%;" class="text-center">QTY LAKU</th>
                <th style="width: 13%;" class="text-right" style="padding-right: 6px;">HARGA (RP)</th>
                <th style="width: 14%;" class="text-right" style="padding-right: 6px;">SUBTOTAL (RP)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($details as $idx => $d): 
                $laku = (int)$d['jumlah_laku_terjual'];
                $subtotal = (float)$d['subtotal_laku'];
                $hargaDeal = (float)$d['harga_satuan_deal'];
            ?>
            <tr>
                <td class="text-center"><?= $idx + 1 ?></td>
                <td class="text-center font-bold"><?= htmlspecialchars($d['kode_sku'] ?? '') ?></td>
                <td style="padding-left: 6px;"><?= htmlspecialchars($d['nama_item']) ?></td>
                <td class="text-center font-bold"><?= number_format($laku, 0, ',', '.') ?> <?= htmlspecialchars($d['satuan_dasar'] ?? 'pcs') ?></td>
                <td class="text-right" style="padding-right: 6px;"><?= number_format($hargaDeal, 0, ',', '.') ?></td>
                <td class="text-right font-bold" style="padding-right: 6px;"><?= number_format($subtotal, 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right font-bold" style="padding-right: 8px;">TOTAL KUANTITAS TERJUAL :</td>
                <td class="text-center font-bold"><?= number_format($totalQtyLaku, 0, ',', '.') ?></td>
                <td class="text-right font-bold" style="padding-right: 6px; white-space: nowrap;">TOTAL:</td>
                <td class="text-right font-bold" style="font-size: 8.5pt; padding-right: 6px; white-space: nowrap;"><?= number_format($totalLakuRp, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
        <?php else: ?>
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">NO</th>
                <th style="width: 13%;" class="text-center">KODE SKU</th>
                <th style="width: 31%;" class="text-left" style="padding-left: 6px;">NAMA PRODUK / BARANG</th>
                <th style="width: 7%;" class="text-center">TITIP</th>
                <th style="width: 7%;" class="text-center">SISA</th>
                <th style="width: 7%;" class="text-center">RETUR</th>
                <th style="width: 7%;" class="text-center">LAKU</th>
                <th style="width: 11%;" class="text-right" style="padding-right: 6px;">HARGA (RP)</th>
                <th style="width: 13%;" class="text-right" style="padding-right: 6px;">SUBTOTAL (RP)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($details as $idx => $d): 
                $laku = (int)$d['jumlah_laku_terjual'];
                $rusak = (int)$d['retur_rusak'];
                $bagus = (int)$d['retur_bagus'];
                $returTotal = $rusak + $bagus;
                $sisa = (int)$d['sisa_fisik_di_rak'];
                $titip = (int)$d['stok_titip_awal'];
                $subtotal = (float)$d['subtotal_laku'];
                $hargaDeal = (float)$d['harga_satuan_deal'];
            ?>
            <tr>
                <td class="text-center"><?= $idx + 1 ?></td>
                <td class="text-center font-bold"><?= htmlspecialchars($d['kode_sku'] ?? '') ?></td>
                <td style="padding-left: 6px;"><?= htmlspecialchars($d['nama_item']) ?></td>
                <td class="text-center"><?= number_format($titip, 0, ',', '.') ?></td>
                <td class="text-center"><?= number_format($sisa, 0, ',', '.') ?></td>
                <td class="text-center"><?= $returTotal > 0 ? number_format($returTotal, 0, ',', '.') : '-' ?></td>
                <td class="text-center font-bold"><?= number_format($laku, 0, ',', '.') ?></td>
                <td class="text-right" style="padding-right: 6px;"><?= number_format($hargaDeal, 0, ',', '.') ?></td>
                <td class="text-right font-bold" style="padding-right: 6px;"><?= number_format($subtotal, 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right font-bold" style="padding-right: 8px;">TOTAL KUANTITAS :</td>
                <td class="text-center font-bold"><?= number_format($totalStokAwal, 0, ',', '.') ?></td>
                <td class="text-center font-bold"><?= number_format($totalSisaRak, 0, ',', '.') ?></td>
                <td class="text-center font-bold"><?= number_format($totalQtyRusak + $totalQtyBagus, 0, ',', '.') ?></td>
                <td class="text-center font-bold"><?= number_format($totalQtyLaku, 0, ',', '.') ?></td>
                <td class="text-right font-bold" style="padding-right: 6px; white-space: nowrap;">TOTAL:</td>
                <td class="text-right font-bold" style="font-size: 8.5pt; padding-right: 6px; white-space: nowrap;"><?= number_format($totalLakuRp, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <!-- BOTTOM SECTION: TERBILANG & RINGKASAN AKUNTANSI -->
    <table class="bottom-table">
        <tr>
            <td style="width: 56%; vertical-align: top; padding-right: 10px;">
                <div class="terbilang-box">
                    <span style="font-weight: bold; text-transform: uppercase;">Terbilang:</span><br>
                    <span style="font-style: italic; font-weight: bold;">
                        # <?= Format::terbilang($totalLakuRp) ?> #
                    </span>
                </div>

                <div class="payment-info-box">
                    <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 3px; border-bottom: 0.5px solid #cccccc; padding-bottom: 2px;">
                        <?= $isInvoiced ? 'Ketentuan &amp; Pembayaran:' : 'Catatan Berita Acara Audit Rak:' ?>
                    </div>
                    <?php if ($isInvoiced): ?>
                    1. Pembayaran via Transfer Bank Resmi:<br>
                    &nbsp;&nbsp;&nbsp;<strong><?= htmlspecialchars($bankAccount['nama_akun'] ?? 'Bank BCA') ?></strong> &bull; No. Rekening: <strong><?= htmlspecialchars($bankAccount['nomor_rekening'] ?? '8830192831') ?></strong><br>
                    &nbsp;&nbsp;&nbsp;Atas Nama: <strong><?= htmlspecialchars($bankAccount['atas_nama'] ?? 'Owner KEREN Snack') ?></strong><br>
                    2. Pembayaran tunai sah apabila diserahkan langsung kepada petugas resmi dengan bukti tanda terima sah.<br>
                    3. Sisa fisik barang di rak (<strong><?= number_format($totalSisaRak, 0, ',', '.') ?> pcs</strong>) tetap menjadi titipan konsinyasi untuk periode berikutnya.<br>
                    <?php else: ?>
                    1. Dokumen ini merupakan Berita Acara Audit Fisik Rak Toko Konsinyasi yang sah.<br>
                    2. Faktur tagihan resmi dan penagihan akan diproses terpisah oleh kantor sesuai siklus penagihan toko mitra.<br>
                    3. Sisa fisik barang di rak (<strong><?= number_format($totalSisaRak, 0, ',', '.') ?> pcs</strong>) telah diverifikasi bersama dan menjadi saldo awal titipan berikutnya.<br>
                    <?php endif; ?>
                    <?php if (!empty($visit['catatan'])): ?>
                    4. Catatan Petugas: <em><?= htmlspecialchars($visit['catatan']) ?></em>
                    <?php endif; ?>
                </div>
            </td>

            <td style="width: 44%; vertical-align: top;">
                <table class="calc-summary-table">
                    <tr>
                        <td style="width: 110px;">Total Penjualan (Laku)</td>
                        <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                        <td class="text-right font-bold">Rp <?= number_format($totalLakuRp, 0, ',', '.') ?></td>
                    </tr>
                    <tr class="grand-row">
                        <td style="font-weight: bold;"><?= $isInvoiced ? 'TOTAL TAGIHAN' : 'NILAI LAKU' ?></td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td class="text-right font-bold">Rp <?= number_format($totalLakuRp, 0, ',', '.') ?></td>
                    </tr>
                    <?php if ($isInvoiced): ?>
                    <tr>
                        <td>Sudah Dibayar</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td class="text-right font-bold">Rp <?= number_format($dibayar, 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">SISA PIUTANG</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td class="text-right font-bold" style="font-size: 8.5pt;">Rp <?= number_format($sisaTagihan, 0, ',', '.') ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </td>
        </tr>
    </table>

    <!-- TANDA TANGAN DUA BELAH PIHAK -->
    <table class="sig-table">
        <tr>
            <td class="sig-cell">
                <div class="sig-title">Tanda Terima Pelanggan / Toko Mitra,</div>
                <div class="sig-space"></div>
                <div class="sig-line">( <?= htmlspecialchars($visit['nama_pemilik'] ?: $visit['nama_toko']) ?> )</div>
                <div class="sig-caption">Tanda Tangan &amp; Cap Toko</div>
            </td>

            <td class="sig-cell">
                <div class="sig-title">Hormat Kami,<br><?= htmlspecialchars($comp['nama']) ?></div>
                <div class="sig-space"></div>
                <div class="sig-line">( <?= htmlspecialchars($visit['sales_name']) ?> )</div>
                <div class="sig-caption">Sales / Petugas Konsinyasi</div>
            </td>
        </tr>
    </table>
</div>
<?php endif; ?>

<?php if (empty($isPdf) || $formatMode !== 'standard'): ?>
<!-- FORMAT KHUSUS PRINTER DOT MATRIX (CONTINUOUS FORM 9.5" x 11" FULL / 9.5" x 5.5" HALF) -->
<div id="sheet-dotmatrix" class="continuous-wrapper">
    <div class="tractor-strip tractor-left"></div>
    <div class="continuous-inner">
        <!-- KOP RESMI & HEADER DOKUMEN -->
        <table class="dm-table">
            <tr>
                <td style="width: 55%; vertical-align: top;">
                    <div class="dm-brand"><?= htmlspecialchars($comp['nama']) ?></div>
                    <div class="dm-sub"><?= htmlspecialchars($comp['tagline']) ?></div>
                    <div class="dm-text-muted"><?= htmlspecialchars($comp['alamat']) ?></div>
                    <div class="dm-text-muted"><?= PrintDocumentHelper::formatContactLine($comp, ' &bull; ') ?></div>
                </td>
                <td style="width: 45%; vertical-align: top; text-align: right;">
                    <div class="dm-title"><?= $docMainTitle ?></div>
                    <table class="dm-meta-table">
                        <tr>
                            <td class="dm-meta-lbl">No. Dokumen</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val"><strong><?= htmlspecialchars($docIdentifier) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="dm-meta-lbl">Tgl. Kunjungan</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val"><?= date('d/m/Y', strtotime($visit['tanggal_kunjungan'])) ?></td>
                        </tr>
                        <tr>
                            <td class="dm-meta-lbl">Status Bayar</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val">[ <?= $isInvoiced ? $statusBayar : 'AUDIT RAK' ?> ]</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="dm-divider-double"></div>

        <!-- TUJUAN TOKO & SALES -->
        <table class="dm-table">
            <tr>
                <td style="width: 52%; vertical-align: top; padding-right: 12px;">
                    <div class="dm-section-title">TOKO MITRA KONSINYASI:</div>
                    <table class="dm-subtable">
                        <tr>
                            <td class="dm-lbl">Nama Toko</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= htmlspecialchars($visit['nama_toko']) ?></strong> (<?= htmlspecialchars($visit['kode_pelanggan'] ?? '-') ?>)</td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Pemilik / PIC</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($visit['nama_pemilik'] ?: '-') ?></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Alamat Toko</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($visit['alamat_lengkap'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">No. Telp / WA</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($visit['nomor_whatsapp'] ?: ($visit['nomor_telepon'] ?: '-')) ?></td>
                        </tr>
                    </table>
                </td>
                <td style="width: 48%; vertical-align: top; border-left: 1px dashed #000000; padding-left: 14px;">
                    <div class="dm-section-title">DATA PETUGAS &amp; SKEMA:</div>
                    <table class="dm-subtable">
                        <tr>
                            <td class="dm-lbl">Sales Pembina</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= htmlspecialchars($visit['sales_name']) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Skema Titip</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val">Konsinyasi Rak (Titip Jual)</td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Sisa Fisik Rak</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= number_format($totalSisaRak, 0, ',', '.') ?> pcs</strong> (Stok Berjalan)</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="dm-divider-single"></div>

        <!-- TABEL ITEM KONSINYASI (80 KOLOM) -->
        <table class="dm-table dm-items-table" style="width: 100%;">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">NO</th>
                    <th style="width: 15%; text-align: left;">KODE SKU</th>
                    <th style="text-align: left;">NAMA BARANG / SNACK</th>
                    <th style="width: 10%; text-align: center;">TITIP</th>
                    <th style="width: 10%; text-align: center;">SISA</th>
                    <th style="width: 10%; text-align: right;">LAKU</th>
                    <th style="width: 15%; text-align: right;">HARGA</th>
                    <th style="width: 18%; text-align: right;">TOTAL (RP)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($details as $idx => $d): 
                    $laku = (int)$d['jumlah_laku_terjual'];
                    $subtotal = (float)$d['subtotal_laku'];
                    $hargaDeal = (float)$d['harga_satuan_deal'];
                    $titip = (int)($d['stok_titip_awal'] ?? 0);
                    $sisa = (int)($d['sisa_fisik_di_rak'] ?? 0);
                ?>
                <tr>
                    <td style="text-align: center;"><?= $idx + 1 ?></td>
                    <td><?= htmlspecialchars($d['kode_sku'] ?? '-') ?></td>
                    <td><strong><?= htmlspecialchars($d['nama_item']) ?></strong></td>
                    <td style="text-align: center;"><?= $titip > 0 ? number_format($titip, 0, ',', '.') : '-' ?></td>
                    <td style="text-align: center;"><?= $sisa > 0 ? number_format($sisa, 0, ',', '.') : '-' ?></td>
                    <td style="text-align: right;"><strong><?= number_format($laku, 0, ',', '.') ?></strong></td>
                    <td style="text-align: right;"><?= number_format($hargaDeal, 0, ',', '.') ?></td>
                    <td style="text-align: right;"><strong><?= number_format($subtotal, 0, ',', '.') ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: right; font-weight: bold;">TOTAL PRODUK LAKU:</td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($totalQtyLaku, 0, ',', '.') ?></td>
                    <td style="text-align: right; font-weight: bold;">GRAND TOTAL:</td>
                    <td style="text-align: right; font-weight: bold;"><?= Format::rupiah($totalLakuRp) ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="dm-divider-single"></div>

        <!-- TERBILANG & RINGKASAN TAGIHAN -->
        <table class="dm-table" style="margin-bottom: 6px;">
            <tr>
                <td style="vertical-align: top; width: 55%; font-size: 8.5pt;">
                    <strong>Terbilang:</strong> <em># <?= Format::terbilang($totalLakuRp, true) ?> #</em><br>
                    <?php if ($isInvoiced && !empty($bankAccount)): ?>
                    <strong>Rekening:</strong> <?= htmlspecialchars($bankAccount['nama_akun']) ?> - <?= htmlspecialchars($bankAccount['nomor_rekening']) ?> (a.n. <?= htmlspecialchars($bankAccount['atas_nama']) ?>)<br>
                    <?php endif; ?>
                    <em>* Sisa stok fisik di rak tetap menjadi saldo titip periode selanjutnya.</em>
                </td>
                <td style="vertical-align: top; width: 45%;">
                    <table class="dm-subtable" style="width: 100%;">
                        <tr style="font-weight: bold; font-size: 9.5pt;">
                            <td class="dm-lbl" style="width: 140px; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0;">TOTAL TAGIHAN</td>
                            <td class="dm-sep" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0;">:</td>
                            <td class="dm-val" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0; text-align: right;"><?= Format::rupiah($totalLakuRp) ?></td>
                        </tr>
                        <?php if ($isInvoiced): ?>
                        <tr>
                            <td class="dm-lbl" style="width: 140px;">Total Dibayar</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val" style="text-align: right;"><?= Format::rupiah($dibayar) ?></td>
                        </tr>
                        <?php if ($sisaTagihan > 0): ?>
                        <tr style="font-weight: bold;">
                            <td class="dm-lbl" style="width: 140px;">SISA PIUTANG</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val" style="text-align: right;"><?= Format::rupiah($sisaTagihan) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php endif; ?>
                    </table>
                </td>
            </tr>
        </table>

        <!-- TANDA TANGAN 2 PIHAK -->
        <table class="dm-table dm-sig-table">
            <tr>
                <td style="width: 50%; text-align: center;">
                    <div class="dm-sig-title">Toko Mitra Konsinyasi,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( <?= htmlspecialchars($visit['nama_pemilik'] ?: $visit['nama_toko']) ?> )</div>
                    <div class="dm-sig-sub">Cap Toko &amp; Tanda Tangan</div>
                </td>
                <td style="width: 50%; text-align: center;">
                    <div class="dm-sig-title">Sales Pembina Lapangan,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( <?= htmlspecialchars($visit['sales_name']) ?> )</div>
                    <div class="dm-sig-sub"><?= htmlspecialchars($comp['nama']) ?></div>
                </td>
            </tr>
        </table>

        <div class="dm-divider-double" style="margin-top: 8px;"></div>

        <!-- FOOTER COPY INDIKATOR RANGKAP NCR -->
        <div class="dm-ncr-footer">
            <span>[ ] Lembar 1 (Putih): Kasir / Accounting</span> &nbsp;&bull;&nbsp;
            <span>[ ] Lembar 2 (Merah): Toko Mitra</span> &nbsp;&bull;&nbsp;
            <span>[ ] Lembar 3 (Kuning): Sales Lapangan</span>
        </div>
    </div>
    <div class="tractor-strip tractor-right"></div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/print_frame.php';

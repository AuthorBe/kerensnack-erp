<?php
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;

$comp = CompanySetting::getAll();

// Saring item bonus: Lembar surat jalan cetak fisik untuk toko murni hanya mencetak item pesanan PO reguler
$items = array_values(array_filter($items ?? [], fn($it) => empty($it['is_bonus'])));

$totalQtyMuatan = (int)array_sum(array_column($items, 'kuantitas_satuan_dasar'));
$statusSuratJalan = strtoupper(str_replace('_', ' ', $delivery['status_surat_jalan'] ?? 'SIAP KIRIM'));
$skemaTransaksi = !empty($delivery['is_konsinyasi']) || (($delivery['tipe_pembayaran'] ?? '') === 'konsinyasi')
    ? 'Konsinyasi (Titip Jual)'
    : strtoupper(str_replace('_', ' ', $delivery['tipe_pembayaran'] ?? 'Reguler'));
$satuanTampil = !empty($items[0]['satuan_dasar']) ? htmlspecialchars($items[0]['satuan_dasar']) : 'Bungkus';

$formatMode = $formatMode ?? PrintDocumentHelper::resolveFormat($_GET['format'] ?? 'standard');
$documentTitle = 'Surat Jalan Pengiriman - ' . ($delivery['nomor_surat_jalan'] ?? '');
$backUrl = Router::url('/deliveries');
$pdfUrl = Router::url('/deliveries/pdf?id=' . $delivery['id']);
$enableHalfMode = true;

ob_start();
?>
<style>
/* STYLING KHUSUS LEMBAR A4 RESMI SURAT JALAN */
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
.items-table { width: 100%; border: 1px solid #000000; margin-bottom: 8px; }
.items-table thead { display: table-header-group; }
.items-table tr { page-break-inside: avoid; }
.items-table th { background: #f0f0f0; border: 1px solid #000000; color: #000000; font-size: 7.5pt; font-weight: bold; text-transform: uppercase; padding: 5px 4px; letter-spacing: 0.2px; }
.items-table td { border: 1px solid #000000; padding: 4px 5px; font-size: 7.5pt; vertical-align: middle; color: #000000; }
.items-table tfoot { page-break-inside: avoid; }
.items-table tfoot td { background: #f5f5f5; border: 1px solid #000000; font-weight: bold; font-size: 7.5pt; padding: 5px 4px; color: #000000; }
.bottom-table { width: 100%; margin-top: 5px; page-break-inside: avoid; }
.terbilang-box { border: 1px solid #000000; background: #fafafa; padding: 5px 8px; margin-bottom: 5px; font-size: 7.5pt; color: #000000; }
.payment-info-box { border: 1px solid #000000; padding: 6px 8px; font-size: 7pt; line-height: 1.45; color: #000000; }
.calc-summary-table { width: 100%; border: 1px solid #000000; }
.calc-summary-table td { padding: 3.5px 6px; font-size: 7.5pt; border-bottom: 0.5px solid #cccccc; color: #000000; vertical-align: middle; }
.calc-summary-table tr.grand-row td { border-top: 1.5px solid #000000; border-bottom: 1.5px solid #000000; font-size: 8.5pt; font-weight: bold; background: #f0f0f0; color: #000000; }
.sig-table { width: 100%; margin-top: 14px; page-break-inside: avoid; }
.sig-cell { width: 33.33%; text-align: center; vertical-align: top; padding: 0 8px; }
.sig-title { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; color: #000000; line-height: 1.35; }
.sig-space { height: 48px; }
.sig-line { display: inline-block; min-width: 150px; border-top: 1px solid #000000; padding-top: 3px; font-weight: bold; font-size: 7.5pt; color: #000000; }
.sig-caption { font-size: 6.5pt; color: #444444; margin-top: 1px; }
.doc-footer { margin-top: 8px; text-align: center; font-size: 6.5pt; color: #555555; border-top: 0.5px solid #cccccc; padding-top: 3px; }
</style>

<?php if (empty($isPdf) || $formatMode === 'standard'): ?>
<!-- FORMAT STANDAR LASER / INKJET (A4 PORTRAIT) -->
<div id="sheet-standard" class="page-sheet">
    <!-- KOP PERUSAHAAN & JUDUL SURAT JALAN RESMI -->
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
                <div class="doc-title-main">SURAT JALAN PENGIRIMAN</div>
                <div class="doc-title-sub">BUKTI SERAH TERIMA PENGIRIMAN</div>

                <table class="doc-meta-table">
                    <tr>
                        <td style="width: 110px; font-weight: bold;">No. Surat Jalan</td>
                        <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                        <td style="font-weight: bold;"><?= htmlspecialchars($delivery['nomor_surat_jalan']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Tanggal Pengiriman</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= date('d F Y', strtotime($delivery['tanggal_surat_jalan'] ?: ($delivery['dibuat_pada'] ?: $delivery['tanggal_pesanan']))) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">No. Faktur / Nota</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($delivery['nomor_nota']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Status Pengiriman</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td>
                            <span class="status-box"><?= $statusSuratJalan ?></span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="divider-double"></div>

    <!-- INFORMASI PELANGGAN & LOGISTIK (2 KOLOM SIMETRIS) -->
    <table class="info-card-table">
        <tr>
            <td style="width: 50%; border-right: 1px solid #000000;" class="info-card-header">
                KEPADA YTH. (TOKO PENERIMA)
            </td>
            <td style="width: 50%;" class="info-card-header">
                INFORMASI LOGISTIK &amp; PENGIRIMAN
            </td>
        </tr>
        <tr>
            <td class="info-card-body" style="border-right: 1px solid #000000;">
                <table class="info-table-inner">
                    <tr>
                        <td style="width: 95px; font-weight: bold;">Nama Toko</td>
                        <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                        <td><strong><?= htmlspecialchars($delivery['nama_toko']) ?></strong> (<?= htmlspecialchars($delivery['kode_pelanggan']) ?>)</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Nama Penerima</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($delivery['nama_pemilik'] ?: '-') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Alamat Lengkap</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($delivery['alamat_toko'] ?: '-') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Telepon / WA</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($delivery['nomor_whatsapp'] ?: '-') ?></td>
                    </tr>
                </table>
            </td>
            <td class="info-card-body">
                <table class="info-table-inner">
                    <tr>
                        <td style="width: 110px; font-weight: bold;">Driver / Kurir</td>
                        <td style="width: 10px; text-align: center; font-weight: bold;">:</td>
                        <td><strong><?= htmlspecialchars($delivery['nama_driver'] ?: 'Sales Driver') ?></strong></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">No. Polisi Kendaraan</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($delivery['nopol_driver'] ?: '-') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Wilayah / Rute</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><?= htmlspecialchars($delivery['nama_wilayah'] ?: 'Distribusi Lokal') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Skema Transaksi</td>
                        <td style="text-align: center; font-weight: bold;">:</td>
                        <td><strong><?= $skemaTransaksi ?></strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TABEL RINCIAN ITEM PRODUK RESMI -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">NO</th>
                <th style="width: 95px;" class="text-left">KODE SKU</th>
                <th class="text-left">NAMA BARANG / ITEM PRODUK</th>
                <th style="width: 110px;" class="text-left">VARIAN</th>
                <th style="width: 65px;" class="text-right">JUMLAH</th>
                <th style="width: 65px;" class="text-center">SATUAN</th>
                <th style="width: 70px;" class="text-center">CEK FISIK</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; 
            foreach ($items as $it): 
                $namaItem = trim((string)($it['nama_item'] ?? ''));
                $varianRasa = trim((string)($it['varian_rasa'] ?? ''));
                $varianClean = (!empty($varianRasa) && strcasecmp($varianRasa, $namaItem) !== 0) ? $varianRasa : '-';
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($it['kode_sku'] ?? '-') ?></td>
                <td><strong><?= htmlspecialchars($namaItem) ?></strong></td>
                <td><?= htmlspecialchars($varianClean) ?></td>
                <td class="text-right font-bold"><?= number_format((int)$it['kuantitas_satuan_dasar'], 0, ',', '.') ?></td>
                <td class="text-center"><?= htmlspecialchars($it['satuan_dasar'] ?: 'Bungkus') ?></td>
                <td class="text-center">[ &nbsp; ]</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right font-bold">TOTAL KUANTITAS MUATAN BARANG:</td>
                <td class="text-right font-bold"><?= number_format($totalQtyMuatan, 0, ',', '.') ?></td>
                <td class="text-center font-bold"><?= $satuanTampil ?></td>
                <td class="text-center">(<?= count($items) ?> SKU)</td>
            </tr>
        </tfoot>
    </table>

    <!-- SECTION BAWAH (CATATAN, TERBILANG, & INFORMASI PEMBAYARAN) -->
    <table class="bottom-table">
        <tr>
            <td style="width: 58%; vertical-align: top; padding-right: 10px;">
                <div class="terbilang-box">
                    <strong>Terbilang:</strong> <em># <?= Format::terbilang($totalQtyMuatan, false) ?> <?= $satuanTampil ?> #</em>
                </div>
                <div class="payment-info-box">
                    <strong>Catatan Khusus Pengiriman:</strong><br>
                    <?= !empty($delivery['catatan_pesanan']) ? nl2br(htmlspecialchars($delivery['catatan_pesanan'])) : 'Tidak ada catatan khusus pengiriman dari admin gudang.' ?><br>
                    <span style="color: #444444; font-style: italic; margin-top: 3px; display: inline-block;">
                        * Pastikan fisik kemasan luar dan segel produk telah diperiksa lengkap dan diterima dalam kondisi baik saat serah terima di toko.
                    </span>
                </div>
            </td>
            <td style="width: 42%; vertical-align: top;">
                <table class="calc-summary-table">
                    <tr>
                        <td style="width: 55%; font-weight: bold;">Total Jenis SKU Muatan</td>
                        <td style="width: 45%; text-align: right;"><?= count($items) ?> Jenis Produk</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Total Kuantitas Fisik</td>
                        <td style="text-align: right; font-weight: bold;"><?= number_format($totalQtyMuatan, 0, ',', '.') ?> <?= $satuanTampil ?></td>
                    </tr>
                    <tr class="grand-row">
                        <td>Status Pembayaran</td>
                        <td style="text-align: right;"><?= $skemaTransaksi ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TANDA TANGAN RESMI (3 PIHAK) -->
    <table class="sig-table">
        <tr>
            <td class="sig-cell">
                <div class="sig-title">Tanda Terima Toko Mitra,</div>
                <div class="sig-space"></div>
                <div class="sig-line">( <?= htmlspecialchars($delivery['nama_penerima_toko'] ?: ($delivery['nama_pemilik'] ?: $delivery['nama_toko'])) ?> )</div>
                <div class="sig-caption">Nama Jelas &amp; Cap Toko</div>
            </td>
            <td class="sig-cell">
                <div class="sig-title">Petugas Pengantar / Armada,</div>
                <div class="sig-space"></div>
                <div class="sig-line">( <?= htmlspecialchars($delivery['nama_driver'] ?: 'Sales Driver') ?> )</div>
                <div class="sig-caption">Driver Logistik Distribusi</div>
            </td>
            <td class="sig-cell">
                <div class="sig-title">Hormat Kami (Gudang Pusat),</div>
                <div class="sig-space"></div>
                <div class="sig-line">( Petugas Checker )</div>
                <div class="sig-caption">Bagian Muat &amp; Logistik</div>
            </td>
        </tr>
    </table>

    <div class="doc-footer">
        Surat Jalan ini dicetak secara otomatis melalui Sistem ERP <?= htmlspecialchars($comp['nama']) ?> pada <?= date('d/m/Y H:i:s') ?> dan merupakan dokumen sah serah terima barang.
    </div>
</div>
<?php endif; ?>

<?php if (empty($isPdf) || $formatMode !== 'standard'): ?>
<!-- FORMAT PRINTER DOT MATRIX (CONTINUOUS FORM 9.5" x 11" FULL / 9.5" x 5.5" HALF) -->
<div id="sheet-dotmatrix" class="continuous-wrapper">
    <div class="tractor-strip tractor-left"></div>
    <div class="continuous-inner">
        <!-- KOP RESMI PERUSAHAAN & HEADER DOKUMEN -->
        <table class="dm-table">
            <tr>
                <td style="width: 55%; vertical-align: top;">
                    <div class="dm-brand"><?= htmlspecialchars($comp['nama']) ?></div>
                    <div class="dm-sub"><?= htmlspecialchars($comp['tagline']) ?></div>
                    <div class="dm-text-muted"><?= htmlspecialchars($comp['alamat']) ?></div>
                    <div class="dm-text-muted"><?= PrintDocumentHelper::formatContactLine($comp, ' &bull; ') ?></div>
                </td>
                <td style="width: 45%; vertical-align: top; text-align: right;">
                    <div class="dm-title">SURAT JALAN PENGIRIMAN</div>
                    <table class="dm-meta-table">
                        <tr>
                            <td class="dm-meta-lbl">No. Surat Jalan</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val"><strong><?= htmlspecialchars($delivery['nomor_surat_jalan']) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="dm-meta-lbl">Tanggal Kirim</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val"><?= date('d/m/Y', strtotime($delivery['tanggal_surat_jalan'] ?: ($delivery['dibuat_pada'] ?: $delivery['tanggal_pesanan']))) ?></td>
                        </tr>
                        <tr>
                            <td class="dm-meta-lbl">No. Faktur / PO</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val"><?= htmlspecialchars($delivery['nomor_nota']) ?></td>
                        </tr>
                        <tr>
                            <td class="dm-meta-lbl">Status Kirim</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val">[ <?= $statusSuratJalan ?> ]</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="dm-divider-double"></div>

        <!-- TUJUAN PENERIMA & INFORMASI EKSPEDISI / ARMADA -->
        <table class="dm-table">
            <tr>
                <td style="width: 52%; vertical-align: top; padding-right: 12px;">
                    <div class="dm-section-title">TUJUAN PENGIRIMAN (TOKO PELANGGAN):</div>
                    <table class="dm-subtable">
                        <tr>
                            <td class="dm-lbl">Nama Toko</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= htmlspecialchars($delivery['nama_toko']) ?></strong> (<?= htmlspecialchars($delivery['kode_pelanggan']) ?>)</td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Pemilik / PIC</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($delivery['nama_pemilik'] ?: '-') ?></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Alamat Lengkap</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($delivery['alamat_toko'] ?: '-') ?></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">No. Telp / WA</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($delivery['nomor_whatsapp'] ?: '-') ?></td>
                        </tr>
                    </table>
                </td>
                <td style="width: 48%; vertical-align: top; border-left: 1px dashed #000000; padding-left: 14px;">
                    <div class="dm-section-title">DATA ARMADA LOGISTIK:</div>
                    <table class="dm-subtable">
                        <tr>
                            <td class="dm-lbl">Driver / Kurir</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= htmlspecialchars($delivery['nama_driver'] ?: 'Sales Driver') ?></strong></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">No. Polisi</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($delivery['nopol_driver'] ?: '-') ?></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Rute / Wilayah</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($delivery['nama_wilayah'] ?: 'Distribusi Lokal') ?></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Skema Kirim</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= $skemaTransaksi ?></strong></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="dm-divider-single"></div>

        <!-- TABEL DAFTAR ITEM PRODUK MUATAN (80 KOLOM COMPATIBLE) -->
        <table class="dm-table dm-items-table">
            <thead>
                <tr>
                    <th style="width: 35px; text-align: center;">NO</th>
                    <th style="width: 110px; text-align: left;">KODE SKU</th>
                    <th style="text-align: left;">NAMA BARANG / ITEM PRODUK</th>
                    <th style="width: 120px; text-align: left;">VARIAN</th>
                    <th style="width: 75px; text-align: right;">QTY</th>
                    <th style="width: 75px; text-align: center;">SATUAN</th>
                    <th style="width: 75px; text-align: center;">CEK FISIK</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1; 
                foreach ($items as $it): 
                    $namaItem = trim((string)($it['nama_item'] ?? ''));
                    $varianRasa = trim((string)($it['varian_rasa'] ?? ''));
                    $varianClean = (!empty($varianRasa) && strcasecmp($varianRasa, $namaItem) !== 0) ? $varianRasa : '-';
                ?>
                <tr>
                    <td style="text-align: center;"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($it['kode_sku'] ?? '-') ?></td>
                    <td><strong><?= htmlspecialchars($namaItem) ?></strong></td>
                    <td><?= htmlspecialchars($varianClean) ?></td>
                    <td style="text-align: right;"><strong><?= number_format((int)$it['kuantitas_satuan_dasar'], 0, ',', '.') ?></strong></td>
                    <td style="text-align: center;"><?= htmlspecialchars($it['satuan_dasar'] ?: 'Bungkus') ?></td>
                    <td style="text-align: center;">[ &nbsp; ]</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right; font-weight: bold;">TOTAL KUANTITAS MUATAN:</td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($totalQtyMuatan, 0, ',', '.') ?></td>
                    <td style="text-align: center; font-weight: bold;"><?= $satuanTampil ?></td>
                    <td style="text-align: center;">(<?= count($items) ?> SKU)</td>
                </tr>
            </tfoot>
        </table>

        <div class="dm-divider-single"></div>

        <!-- CATATAN & TERBILANG -->
        <table class="dm-table" style="margin-bottom: 6px;">
            <tr>
                <td style="vertical-align: top; width: 62%; font-size: 8.5pt;">
                    <strong>Terbilang:</strong> <em># <?= Format::terbilang($totalQtyMuatan, false) ?> <?= $satuanTampil ?> #</em><br>
                    <strong>Catatan:</strong> <?= !empty($delivery['catatan_pesanan']) ? htmlspecialchars($delivery['catatan_pesanan']) : 'Barang telah diperiksa lengkap & kondisi baik saat muat.' ?><br>
                    <em>* Mohon periksa fisik kemasan &amp; segel bersama driver saat serah terima di toko.</em>
                </td>
                <td style="vertical-align: top; width: 38%; text-align: right; font-size: 8pt;">
                    Dokumen App Keren One<br>
                    Cetak: <?= date('d/m/Y H:i:s') ?>
                </td>
            </tr>
        </table>

        <!-- TANDA TANGAN 3 PIHAK -->
        <table class="dm-table dm-sig-table">
            <tr>
                <td style="width: 33.3%; text-align: center;">
                    <div class="dm-sig-title">Tanda Terima Toko,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( <?= htmlspecialchars($delivery['nama_penerima_toko'] ?: ($delivery['nama_pemilik'] ?: $delivery['nama_toko'])) ?> )</div>
                    <div class="dm-sig-sub">Cap Toko &amp; Tanda Tangan</div>
                </td>
                <td style="width: 33.3%; text-align: center;">
                    <div class="dm-sig-title">Petugas Pengantar,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( <?= htmlspecialchars($delivery['nama_driver'] ?: 'Sales Driver') ?> )</div>
                    <div class="dm-sig-sub">Armada Logistik Distribusi</div>
                </td>
                <td style="width: 33.3%; text-align: center;">
                    <div class="dm-sig-title">Hormat Kami,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( Petugas Gudang )</div>
                    <div class="dm-sig-sub">Checker Logistik Pusat</div>
                </td>
            </tr>
        </table>

        <div class="dm-divider-double" style="margin-top: 8px;"></div>

        <!-- FOOTER COPY INDIKATOR RANGKAP NCR CONTINUOUS FORM -->
        <div class="dm-ncr-footer">
            <span>[ ] Lembar 1 (Putih): Arsip Gudang</span> &nbsp;&bull;&nbsp;
            <span>[ ] Lembar 2 (Merah): Toko Mitra</span> &nbsp;&bull;&nbsp;
            <span>[ ] Lembar 3 (Kuning): Petugas Driver</span>
        </div>
    </div>
    <div class="tractor-strip tractor-right"></div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/print_frame.php';

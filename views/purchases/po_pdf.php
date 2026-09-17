<?php
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;

$comp = CompanySetting::getAll();
$isPo = (($purchase['jenis_dokumen'] ?? 'faktur') === 'po');
$docTitle = $isPo ? 'SURAT PESANAN PEMBELIAN (PO)' : 'FAKTUR PEMBELIAN VENDOR';
$nomorDokumen = $purchase['nomor_faktur_pembelian'] ?? '-';

$formatMode = $formatMode ?? PrintDocumentHelper::resolveFormat($_GET['format'] ?? 'standard');
$documentTitle = $docTitle . ' - ' . htmlspecialchars($nomorDokumen);
$backUrl = Router::url('/purchases');
$pdfUrl = Router::url('/purchases/pdf?id=' . $purchase['id']);
$enableHalfMode = true;

ob_start();
?>
<style>
/* STYLING SPESIFIK PO PEMBELIAN STANDAR A4 */
.kop-table { margin-bottom: 12px; border-bottom: 2px solid #1f2937; padding-bottom: 10px; width: 100%; }
.company-name { font-size: 16pt; font-weight: 900; color: #111827; letter-spacing: -0.5px; }
.company-sub { font-size: 8pt; color: #4b5563; margin-top: 2px; line-height: 1.35; }
.doc-title-box { text-align: right; }
.doc-title { font-size: 13pt; font-weight: 900; color: #1e40af; letter-spacing: 0.3px; }
.doc-number { font-family: 'Courier', monospace; font-size: 11pt; font-weight: bold; color: #111827; margin-top: 2px; }
.info-table { margin-bottom: 16px; width: 100%; }
.info-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 12px; vertical-align: top; width: 48%; }
.info-card-title { font-size: 7.5pt; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
.items-table { margin-bottom: 16px; width: 100%; border-collapse: collapse; }
.items-table th { background: #1f2937; color: #ffffff; font-size: 8pt; font-weight: bold; padding: 6px 8px; text-transform: uppercase; border: 1px solid #1f2937; }
.items-table td { padding: 6px 8px; border: 1px solid #e5e7eb; font-size: 8.5pt; }
.items-table tr:nth-child(even) td { background: #f9fafb; }
.notes-card { background: #fdfdfd; border: 1px dashed #d1d5db; border-radius: 6px; padding: 8px 10px; font-size: 8pt; line-height: 1.4; }
.badge-method { display: inline-block; background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 7.5pt; }
.sign-table { margin-top: 24px; page-break-inside: avoid; width: 100%; }
.sign-box { text-align: center; width: 33.3%; vertical-align: top; }
.sign-line { border-bottom: 1px solid #111827; width: 75%; margin: 44px auto 4px auto; }
</style>

<?php if (empty($isPdf) || $formatMode === 'standard'): ?>
<!-- FORMAT STANDAR LASER / INKJET (A4 PORTRAIT) -->
<div id="sheet-standard" class="page-sheet">
    <!-- KOP RESMI -->
    <table class="kop-table">
        <tr>
            <td style="vertical-align:top; width:60%;">
                <div class="company-name"><?= htmlspecialchars($comp['nama']) ?></div>
                <div class="company-sub">
                    <?= htmlspecialchars($comp['tagline']) ?><br>
                    <?= htmlspecialchars($comp['alamat']) ?><br>
                    <?= PrintDocumentHelper::formatContactLine($comp, ' &bull; ') ?>
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
                    Lokasi Tujuan: <strong>Gudang Pusat <?= htmlspecialchars($comp['nama'] ?? 'Keren Snack') ?></strong><br>
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
    <table style="width: 100%;">
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
                    Dokumen ini sah diterbitkan melalui sistem terkomputerisasi ERP <strong><?= htmlspecialchars($comp['nama'] ?? 'Keren Snack') ?></strong>.<br>
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
</div>
<?php endif; ?>

<?php if (empty($isPdf) || $formatMode !== 'standard'): ?>
<!-- FORMAT KHUSUS PRINTER DOT MATRIX (CONTINUOUS FORM 9.5" x 11" FULL / 9.5" x 5.5" HALF) -->
<div id="sheet-dotmatrix" class="continuous-wrapper">
    <div class="tractor-strip tractor-left"></div>
    <div class="continuous-inner">
        <!-- KOP RESMI PERUSAHAAN & HEADER PO -->
        <table class="dm-table">
            <tr>
                <td style="width: 55%; vertical-align: top;">
                    <div class="dm-brand"><?= htmlspecialchars($comp['nama']) ?></div>
                    <div class="dm-sub"><?= htmlspecialchars($comp['tagline']) ?></div>
                    <div class="dm-text-muted"><?= htmlspecialchars($comp['alamat']) ?></div>
                    <div class="dm-text-muted"><?= PrintDocumentHelper::formatContactLine($comp, ' &bull; ') ?></div>
                </td>
                <td style="width: 45%; vertical-align: top; text-align: right;">
                    <div class="dm-title"><?= $docTitle ?></div>
                    <table class="dm-meta-table">
                        <tr>
                            <td class="dm-meta-lbl">No. Dokumen</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val"><strong><?= htmlspecialchars($nomorDokumen) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="dm-meta-lbl">Tanggal PO</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val"><?= date('d/m/Y', strtotime($purchase['tanggal_pembelian'])) ?></td>
                        </tr>
                        <tr>
                            <td class="dm-meta-lbl">Metode Logistik</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val">[ <?= ($purchase['metode_logistik'] ?? '') === 'diambil_driver' ? 'DIAMBIL DRIVER' : 'DIANTAR SUPPLIER' ?> ]</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="dm-divider-double"></div>

        <!-- TUJUAN VENDOR & DETAIL LOGISTIK -->
        <table class="dm-table">
            <tr>
                <td style="width: 52%; vertical-align: top; padding-right: 12px;">
                    <div class="dm-section-title">KEPADA VENDOR / PEMASOK:</div>
                    <table class="dm-subtable">
                        <tr>
                            <td class="dm-lbl">Nama Vendor</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= htmlspecialchars($purchase['nama_pemasok'] ?? 'Vendor') ?></strong> (<?= htmlspecialchars($purchase['kode_pemasok'] ?? '-') ?>)</td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">PIC / Kontak</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($purchase['supplier_kontak'] ?: '-') ?></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">No. Telp / WA</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($purchase['supplier_wa'] ?: ($purchase['supplier_telepon'] ?: '-')) ?></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Termin Bayar</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= strtoupper(str_replace('_', ' ', $purchase['supplier_termin_bayar'] ?? 'TEMPO')) ?></td>
                        </tr>
                    </table>
                </td>
                <td style="width: 48%; vertical-align: top; border-left: 1px dashed #000000; padding-left: 14px;">
                    <div class="dm-section-title">DATA PENGAMBILAN &amp; ARMADA:</div>
                    <table class="dm-subtable">
                        <tr>
                            <td class="dm-lbl">Driver Pengambil</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= htmlspecialchars($purchase['nama_driver'] ?: 'Armada Pabrik') ?></strong></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">No. Polisi</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($purchase['nopol_driver'] ?: '-') ?></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Jadwal Belanja</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= !empty($purchase['tanggal_jadwal_belanja']) ? date('d/m/Y', strtotime($purchase['tanggal_jadwal_belanja'])) : '-' ?></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Pembuat PO</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($purchase['pembuat'] ?: 'Admin Gudang') ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="dm-divider-single"></div>

        <!-- TABEL RINCIAN BAHAN BAKU / ITEM (80 KOLOM) -->
        <table class="dm-table dm-items-table" style="width: 100%;">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">NO</th>
                    <th style="width: 15%; text-align: left;">KODE SKU</th>
                    <th style="text-align: left;">NAMA BAHAN / KEMASAN</th>
                    <th style="width: 10%; text-align: right;">QTY</th>
                    <th style="width: 10%; text-align: center;">SATUAN</th>
                    <th style="width: 18%; text-align: right;">HARGA (Rp)</th>
                    <th style="width: 18%; text-align: right;">SUBTOTAL (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $totalPoQty = 0;
                $grandTotal = 0;
                foreach ($items as $it): 
                    $sub = (float)$it['subtotal'];
                    $grandTotal += $sub;
                    $qty = (float)$it['kuantitas'];
                    $totalPoQty += $qty;
                ?>
                <tr>
                    <td style="text-align: center;"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($it['kode_sku'] ?? '-') ?></td>
                    <td><strong><?= htmlspecialchars($it['nama_item'] ?? '-') ?></strong></td>
                    <td style="text-align: right;"><strong><?= ($qty == (int)$qty) ? number_format($qty, 0, ',', '.') : number_format($qty, 2, ',', '.') ?></strong></td>
                    <td style="text-align: center;"><?= htmlspecialchars($it['satuan'] ?? 'pcs') ?></td>
                    <td style="text-align: right;"><?= number_format((float)$it['harga_satuan'], 0, ',', '.') ?></td>
                    <td style="text-align: right;"><strong><?= number_format($sub, 0, ',', '.') ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL PESANAN:</td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($totalPoQty, 0, ',', '.') ?></td>
                    <td style="text-align: center; font-weight: bold;">Item</td>
                    <td style="text-align: right; font-weight: bold;">GRAND TOTAL:</td>
                    <td style="text-align: right; font-weight: bold;"><?= Format::rupiah($grandTotal) ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="dm-divider-single"></div>

        <!-- TERBILANG & INSTRUKSI KHUSUS -->
        <table class="dm-table" style="margin-bottom: 6px;">
            <tr>
                <td style="vertical-align: top; width: 60%; font-size: 8.5pt;">
                    <strong>Terbilang:</strong> <em># <?= Format::terbilang($grandTotal, true) ?> #</em><br>
                    <strong>Instruksi:</strong> <?= htmlspecialchars(!empty($purchase['instruksi_driver']) ? $purchase['instruksi_driver'] : ($purchase['catatan'] ?: 'Barang wajib dalam kondisi baik & tanggal kadaluarsa aman.')) ?><br>
                    <em>* Harap konfirmasi ketersediaan stok sebelum armada tiba di lokasi.</em>
                </td>
                <td style="vertical-align: top; width: 40%; text-align: right; font-size: 8pt;">
                    Dokumen ERP Keren Snack<br>
                    Dibuat: <?= htmlspecialchars($purchase['pembuat'] ?: 'Purchasing') ?><br>
                    Cetak: <?= date('d/m/Y H:i:s') ?>
                </td>
            </tr>
        </table>

        <!-- TANDA TANGAN 3 PIHAK -->
        <table class="dm-table dm-sig-table">
            <tr>
                <td style="width: 33.3%; text-align: center;">
                    <div class="dm-sig-title">Vendor Pemasok,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( <?= htmlspecialchars($purchase['nama_pemasok'] ?? 'Pemasok') ?> )</div>
                    <div class="dm-sig-sub">Cap &amp; Tanda Tangan</div>
                </td>
                <td style="width: 33.3%; text-align: center;">
                    <div class="dm-sig-title">Driver / Pengambil,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( <?= htmlspecialchars($purchase['nama_driver'] ?? 'Driver Toko') ?> )</div>
                    <div class="dm-sig-sub">Armada Logistik</div>
                </td>
                <td style="width: 33.3%; text-align: center;">
                    <div class="dm-sig-title">Purchasing / Gudang,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( <?= htmlspecialchars($purchase['pembuat'] ?? 'Admin Gudang') ?> )</div>
                    <div class="dm-sig-sub">Keren Snack ERP</div>
                </td>
            </tr>
        </table>

        <div class="dm-divider-double" style="margin-top: 8px;"></div>

        <!-- FOOTER COPY INDIKATOR RANGKAP NCR -->
        <div class="dm-ncr-footer">
            <span>[ ] Lembar 1 (Putih): Purchasing / Arsip</span> &nbsp;&bull;&nbsp;
            <span>[ ] Lembar 2 (Merah): Vendor Pemasok</span> &nbsp;&bull;&nbsp;
            <span>[ ] Lembar 3 (Kuning): Petugas Gudang / Driver</span>
        </div>
    </div>
    <div class="tractor-strip tractor-right"></div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/print_frame.php';

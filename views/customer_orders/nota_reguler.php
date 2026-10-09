<?php
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;

$comp = CompanySetting::getAll();
$logoSrc = PrintDocumentHelper::getLogoSrc($comp);
$formatMode = $formatMode ?? PrintDocumentHelper::resolveFormat($_GET['format'] ?? 'standard');
$isKonsinyasi = !empty($order['is_konsinyasi']) 
    || (($order['tipe_pembayaran'] ?? '') === 'konsinyasi') 
    || (isset($order['is_tagihan']) && ($order['is_tagihan'] === false || $order['is_tagihan'] === 'f' || $order['is_tagihan'] === 0 || $order['is_tagihan'] === 'false'));

// Saring item bonus: Dokumen resmi gabungan murni mencetak item pesanan PO reguler
$items = array_values(array_filter($items ?? [], fn($it) => empty($it['is_bonus'])));

$nomorNota = $order['nomor_nota'] ?? '-';
$nomorSj = $order['nomor_surat_jalan'] ?? null;
$docHeaderTitle = $isKonsinyasi ? 'SURAT JALAN & BUKTI TITIP RAK' : 'FAKTUR & SURAT JALAN PENGIRIMAN';
$documentTitle = $docHeaderTitle . ' - ' . htmlspecialchars($nomorNota) . ($nomorSj ? ' (' . htmlspecialchars($nomorSj) . ')' : '');

$backUrl = $backUrl ?? Router::url('/customer-orders');
$pdfUrl = $pdfUrl ?? Router::url('/customer-orders/invoice/pdf?id=' . ($order['id'] ?? $order['pesanan_id'] ?? ''));
$extraToolbarHtml = $extraToolbarHtml ?? '<a href="' . Router::url('/customer-orders/invoice/excel?id=' . ($order['id'] ?? $order['pesanan_id'] ?? '')) . '" class="btn-tb btn-tb-excel">📊 Unduh Excel</a>';
$enableHalfMode = true;

ob_start();
?>
<style>
/* ========================================================================= */
/* STYLING DOKUMEN FORMAL B2B (SURAT JALAN & FAKTUR RESMI) A4 / PDF          */
/* Desain Korporat Elegan, Hitam-Putih (Monokrom) Bersih, Logo Tetap Berwarna */
/* ========================================================================= */
.header-table { width: 100%; border-bottom: 2px solid #000000; padding-bottom: 12px; margin-bottom: 14px; }
.company-name { font-size: 18px; font-weight: 900; color: #000000; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.15; }
.company-sub { font-size: 9.5pt; color: #171717; line-height: 1.35; margin-top: 3px; }
.company-sub .contact-line { font-size: 9pt; color: #262626; margin-top: 2px; }

.invoice-title { text-align: right; font-size: 16.5px; font-weight: 900; color: #000000; letter-spacing: 0.5px; text-transform: uppercase; line-height: 1.2; }
.doc-meta-table { margin-left: auto; margin-top: 6px; border-collapse: collapse; font-size: 11px; text-align: left; }
.doc-meta-table td { padding: 1.5px 3px; }
.doc-meta-lbl { font-size: 10.5px; color: #262626; font-weight: 600; white-space: nowrap; }
.doc-meta-sep { color: #262626; padding: 0 4px; font-weight: bold; }
.doc-meta-val { font-family: 'Helvetica', 'Arial', sans-serif; font-variant-numeric: tabular-nums; font-weight: 800; color: #000000; font-size: 11.5px; white-space: nowrap; }

/* DUAL REF COMPATIBILITY */
.dual-ref-box { text-align: right; margin-top: 6px; font-size: 11.5px; }
.dual-ref-line { font-family: 'Helvetica', 'Arial', sans-serif; font-variant-numeric: tabular-nums; font-weight: 700; color: #000000; }
.dual-ref-nota { color: #000000; }
.dual-ref-sj { color: #000000; }

/* FORMAL BOXED METADATA SECTIONS (NO PASTEL, NO ROUNDED BUBBLE) */
.meta-box { background: #ffffff; border: 1.5px solid #000000; padding: 0; font-size: 11px; }
.meta-box-header { background: #f3f4f6; border-bottom: 1px solid #000000; padding: 5px 10px; font-size: 9.5px; font-weight: 800; color: #000000; text-transform: uppercase; letter-spacing: 0.5px; }
.meta-box-body { padding: 8px 10px; line-height: 1.4; color: #000000; }
.meta-label { font-size: 9.5px; font-weight: 800; color: #000000; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; border-bottom: 1px solid #000000; padding-bottom: 3px; }
.meta-value { font-weight: 800; color: #000000; }

/* ENTERPRISE ITEMS TABLE (CLEAN MONOCHROME GRID) */
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
.items-table th { background: #f3f4f6; color: #000000; font-size: 9.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.3px; padding: 5px 4px; border-top: 1.5px solid #000000; border-bottom: 1.5px solid #000000; text-align: left; }
.items-table td { padding: 5px 4px; border-bottom: 1px solid #e5e7eb; font-size: 10.5px; color: #000000; vertical-align: top; }
.items-table tbody tr:last-child td { border-bottom: 1.5px solid #000000; }

.font-mono { font-family: 'Helvetica', 'Arial', sans-serif; font-variant-numeric: tabular-nums; }

/* SUMMARY & TERBILANG */
.terbilang-box { background: #ffffff; border: 1.5px solid #000000; padding: 8px 10px; font-size: 11px; }
.terbilang-header { font-weight: 800; color: #000000; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
.terbilang-text { font-style: italic; font-weight: 700; color: #000000; line-height: 1.35; }
.bank-transfer-note { border-top: 1px solid #d1d5db; margin-top: 6px; padding-top: 5px; font-size: 10px; color: #000000; line-height: 1.35; }

.summary-table { width: 100%; font-size: 11px; border-collapse: collapse; }
.summary-table td { padding: 3px 0; color: #000000; }
.summary-table .total-row td { font-size: 13px; font-weight: 800; color: #000000; border-top: 1.5px solid #000000; border-bottom: 3px double #000000; padding: 6px 0; }

/* SIGNATURES */
.sign-box { border-bottom: 1px solid #000000; padding-bottom: 3px; font-weight: 700; color: #000000; font-size: 11px; display: inline-block; min-width: 140px; white-space: nowrap; }
</style>

<?php if (empty($isPdf) || $formatMode === 'standard'): ?>
<!-- FORMAT STANDAR LASER / INKJET (A4 PORTRAIT) -->
<div id="sheet-standard" class="page-sheet">
    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td style="vertical-align:middle; width:60%; padding-bottom: 10px;">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr>
                        <?php if (!empty($logoSrc)): ?>
                        <td style="width: 1%; white-space: nowrap; vertical-align: middle; padding-right: 8px; border: none;">
                            <img src="<?= $logoSrc ?>" alt="Logo" style="width: 135px; height: auto; max-height: 52px; display: block;">
                        </td>
                        <?php endif; ?>
                        <td style="vertical-align: middle; border: none; padding: 0;">
                            <div class="company-name"><?= htmlspecialchars($comp['nama']) ?></div>
                            <div class="company-sub">
                                <?php if (!empty($comp['tagline'])): ?>
                                <div style="font-weight: 600; margin-bottom: 2px;"><?= htmlspecialchars($comp['tagline']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($comp['alamat'])): ?>
                                <div><?= htmlspecialchars($comp['alamat']) ?></div>
                                <?php endif; ?>
                                <div class="contact-line"><?= PrintDocumentHelper::formatContactLine($comp, ' • ') ?></div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="vertical-align:middle; width:40%; text-align:right; padding-bottom: 10px;">
                <div class="invoice-title"><?= $docHeaderTitle ?></div>
                <table class="doc-meta-table">
                    <?php if ($nomorSj): ?>
                    <tr>
                        <td class="doc-meta-lbl">No. Surat Jalan</td>
                        <td class="doc-meta-sep">:</td>
                        <td class="doc-meta-val"><?= htmlspecialchars($nomorSj) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="doc-meta-lbl">No. Faktur / Nota</td>
                        <td class="doc-meta-sep">:</td>
                        <td class="doc-meta-val"><?= htmlspecialchars($nomorNota) ?></td>
                    </tr>
                    <?php if (!$nomorSj): ?>
                    <tr>
                        <td class="doc-meta-lbl">No. Surat Jalan</td>
                        <td class="doc-meta-sep">:</td>
                        <td class="doc-meta-val" style="font-size:10px; font-weight:normal; color:#404040;">[ Menunggu Pengiriman ]</td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="doc-meta-lbl">Tanggal</td>
                        <td class="doc-meta-sep">:</td>
                        <td class="doc-meta-val"><?= !empty($order['tanggal_pesanan']) ? date('d/m/Y', strtotime($order['tanggal_pesanan'])) : date('d/m/Y') ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- METADATA 2 KOLOM (KIRI: PELANGGAN & SALES | KANAN: LOGISTIK & PEMBAYARAN) -->
    <table class="meta-table" style="width: 100%; border-collapse: collapse; margin-bottom: 14px;">
        <tr>
            <!-- KOLOM KIRI: TOKO & SALES -->
            <td style="width: 48.5%; vertical-align: top; padding: 0;">
                <div class="meta-box">
                    <div class="meta-box-header">Tujuan Pengiriman &amp; Pelanggan:</div>
                    <div class="meta-box-body">
                        <div style="font-size:13px; font-weight:800; color:#000000;"><?= htmlspecialchars($order['nama_toko'] ?? '-') ?></div>
                        <div style="color:#262626; margin-top:2px; font-size:11px;">
                            Kode: <strong class="font-mono"><?= htmlspecialchars($order['kode_pelanggan'] ?? '-') ?></strong> 
                            <?php if (!empty($order['nama_pemilik'])): ?>
                            &bull; PIC: <?= htmlspecialchars($order['nama_pemilik']) ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($order['alamat_lengkap']) || !empty($order['alamat_toko'])): ?>
                        <div style="color:#262626; margin-top:2px; font-size:10.5px; line-height:1.35;"><?= htmlspecialchars($order['alamat_lengkap'] ?? $order['alamat_toko'] ?? '') ?></div>
                        <?php endif; ?>
                        <?php if (!empty($order['nomor_whatsapp'])): ?>
                        <div style="color:#262626; margin-top:1px; font-size:10.5px;">Telp/WA: <span class="font-mono"><?= htmlspecialchars($order['nomor_whatsapp']) ?></span></div>
                        <?php endif; ?>
                        <div style="margin-top:4px; font-size:10.5px; color:#000000; border-top:1px dashed #d1d5db; padding-top:3px;">
                            Sales Pembina: <strong><?= htmlspecialchars($order['nama_sales'] ?: 'Sales Area') ?></strong>
                        </div>
                    </div>
                </div>
            </td>
            <td style="width: 3%; padding: 0;"></td>
            <!-- KOLOM KANAN: ARMADA, DRIVER & SYARAT BAYAR -->
            <td style="width: 48.5%; vertical-align: top; padding: 0;">
                <div class="meta-box">
                    <div class="meta-box-header">Armada Pengiriman &amp; Pembayaran:</div>
                    <div class="meta-box-body">
                        <div>
                            Driver / Pengantar: <strong><?= htmlspecialchars($order['nama_driver'] ?: $order['nama_sales'] ?: 'Driver Toko') ?></strong>
                            <?php if (!empty($order['nopol_driver'])): ?>
                            <span class="font-mono" style="color:#262626;">(<?= htmlspecialchars($order['nopol_driver']) ?>)</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($order['nama_wilayah']) && $order['nama_wilayah'] !== '-'): ?>
                        <div style="margin-top:2px; font-size:10.5px; color:#262626;">
                            Rute / Wilayah: <strong><?= htmlspecialchars($order['nama_wilayah']) ?></strong>
                            <?php if (!empty($order['kode_rute']) && $order['kode_rute'] !== '-'): ?>
                            <span class="font-mono">(<?= htmlspecialchars($order['kode_rute']) ?>)</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($isKonsinyasi): ?>
                        <div style="margin-top:4px; border-top:1px dashed #d1d5db; padding-top:3px;">
                            Skema Distribusi: <strong>TITIP JUAL (KONSINYASI)</strong>
                        </div>
                        <div style="font-size:10px; color:#404040; font-style:italic;">
                            * Non-Tagihan Langsung (Penagihan via Form Opname Sales)
                        </div>
                        <?php else: ?>
                        <div style="margin-top:4px; border-top:1px dashed #d1d5db; padding-top:3px;">
                            Tipe Pembayaran: <strong><?= strtoupper(str_replace('_', ' ', $order['tipe_pembayaran'] ?? 'CASH')) ?></strong>
                            <?php if (($order['status_pembayaran'] ?? '') === 'lunas'): ?>
                            <strong> (LUNAS)</strong>
                            <?php else: ?>
                            <strong> (TEMPO)</strong>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($order['tanggal_jatuh_tempo'])): ?>
                        <div style="margin-top:1px; font-size:10.5px; color:#000000;">
                            Jatuh Tempo: <strong class="font-mono"><?= date('d/m/Y', strtotime($order['tanggal_jatuh_tempo'])) ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($order['nama_akun_kas'])): ?>
                        <div style="margin-top:1px; font-size:10px; color:#404040;">
                            Kas / Rekening: <strong><?= htmlspecialchars($order['nama_akun_kas']) ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- PRODUCT TABLE -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">NO</th>
                <th style="width: 80px;">KODE / BARCODE</th>
                <th>NAMA PRODUK</th>
                <th class="text-center" style="width: 45px;">SATUAN</th>
                <th class="text-center" style="width: 40px;">QTY</th>
                <th class="text-right" style="width: 90px;">HARGA (RP)</th>
                <th class="text-right" style="width: 60px;">DISKON</th>
                <th class="text-right" style="width: 100px;">SUBTOTAL (RP)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $idx => $it): 
                $qtyVal = (int)($it['kuantitas_satuan_dasar'] ?? $it['qty'] ?? 0);
                $hargaVal = (float)($it['harga_satuan_deal'] ?? $it['harga'] ?? $it['harga_satuan'] ?? 0);
                $discVal = (float)($it['diskon_item_nominal'] ?? $it['diskon'] ?? 0);
                $subtotalVal = (float)($it['subtotal'] ?? ($qtyVal * $hargaVal - $discVal));
            ?>
            <tr>
                <td class="text-center font-mono"><?= $idx + 1 ?></td>
                <td class="font-mono" style="font-size:10px;"><?= htmlspecialchars($it['kode_sku'] ?? '-') ?></td>
                <td>
                    <div class="font-bold"><?= htmlspecialchars($it['nama_grup'] ?? $it['nama_item'] ?? '-') ?></div>
                </td>
                <td class="text-center"><?= htmlspecialchars($it['satuan_dasar'] ?: 'pcs') ?></td>
                <td class="text-center font-bold font-mono"><?= number_format($qtyVal, 0, ',', '.') ?></td>
                <td class="text-right font-mono">
                    <?= Format::rupiah($hargaVal) ?>
                </td>
                <td class="text-right font-mono">
                    <?= $discVal > 0 ? '-' . Format::rupiah($discVal) : '-' ?>
                </td>
                <td class="text-right font-bold font-mono">
                    <?= Format::rupiah($subtotalVal) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- SUMMARY & TERBILANG -->
    <table class="summary-grid-table" style="width: 100%; border-collapse: collapse; margin-top: 4px;">
        <tr>
            <td style="width: 55%; vertical-align: top; padding-right: 16px;">
                <div class="terbilang-box">
                    <div class="terbilang-header">Terbilang:</div>
                    <div class="terbilang-text">
                        "<?= Format::terbilang((float)($order['total_netto'] ?? 0)) ?>"
                    </div>
                    <?php if (!empty($order['catatan']) || !empty($order['catatan_pesanan'])): ?>
                    <div style="margin-top:6px; font-size:10.5px; color:#171717;">
                        <strong>Catatan:</strong> <?= htmlspecialchars($order['catatan'] ?? $order['catatan_pesanan'] ?? '') ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($comp['nomor_rekening'])): ?>
                    <div class="bank-transfer-note">
                        Pembayaran Transfer: <strong><?= htmlspecialchars($comp['nama_bank']) ?></strong> Rek: <strong class="font-mono"><?= htmlspecialchars($comp['nomor_rekening']) ?></strong> a.n <strong><?= htmlspecialchars($comp['atas_nama_bank']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($comp['catatan_faktur'])): ?>
                    <div style="margin-top:4px; font-size:9.5px; color:#525252; font-style:italic;">
                        <?= htmlspecialchars($comp['catatan_faktur']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </td>
            <td style="width: 45%; vertical-align: top;">
                <table class="summary-table">
                    <tr>
                        <td style="color:#262626;"><?= $isKonsinyasi ? 'Subtotal Valuasi:' : 'Subtotal Bruto:' ?></td>
                        <td class="text-right font-mono font-bold"><?= Format::rupiah((float)($order['total_bruto'] ?? 0)) ?></td>
                    </tr>
                    <?php if ((float)($order['total_diskon'] ?? 0) > 0): ?>
                    <tr>
                        <td style="color:#262626;">Total Diskon:</td>
                        <td class="text-right font-mono">-<?= Format::rupiah((float)$order['total_diskon']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td><?= $isKonsinyasi ? 'TOTAL TITIP RAK:' : 'TOTAL NETTO:' ?></td>
                        <td class="text-right font-mono"><?= Format::rupiah((float)($order['total_netto'] ?? 0)) ?></td>
                    </tr>
                    <?php if (!$isKonsinyasi && ($order['status_pembayaran'] ?? '') !== 'lunas'): ?>
                    <tr>
                        <td style="font-size:11px; padding-top:4px; font-weight:600;">Sisa Tagihan:</td>
                        <td class="text-right font-mono font-bold" style="font-size:11.5px; padding-top:4px;">
                            <?= Format::rupiah(max(0, (float)($order['total_netto'] ?? 0) - (float)($order['total_dibayar'] ?? 0))) ?>
                        </td>
                    </tr>
                    <?php elseif ($isKonsinyasi): ?>
                    <tr>
                        <td style="font-size:10px; color:#404040; padding-top:4px;" colspan="2" class="text-right">
                            <em>* Non-Tagihan Langsung (Ditagih saat Opname Sales)</em>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </td>
        </tr>
    </table>

    <!-- SIGNATURES (3 PIHAK: PETUGAS GUDANG, DRIVER PENGANTAR, PENERIMA TOKO) -->
    <table class="signature-grid-table" style="width: 100%; border-collapse: collapse; margin-top: 26px; text-align: center;">
        <tr>
            <td style="width: 33.3%; vertical-align: top;">
                <div style="font-size: 10.5px; color: #000000; margin-bottom: 48px; font-weight: 700;">Petugas Gudang,</div>
                <div class="sign-box">( <?= htmlspecialchars(!empty($order['nama_petugas_gudang']) ? $order['nama_petugas_gudang'] : 'Petugas Gudang') ?> )</div>
                <div style="font-size: 9.5px; color: #404040; margin-top: 2px;">Verifikasi Fisik Sesuai PO</div>
            </td>
            <td style="width: 33.3%; vertical-align: top;">
                <div style="font-size: 10.5px; color: #000000; margin-bottom: 48px; font-weight: 700;">Driver Pengantar,</div>
                <div class="sign-box">( <?= htmlspecialchars($order['nama_driver'] ?: $order['nama_sales'] ?: 'Driver Pengantar') ?> )</div>
                <div style="font-size: 9.5px; color: #404040; margin-top: 2px;">Diantar ke Tujuan</div>
            </td>
            <td style="width: 33.3%; vertical-align: top;">
                <div style="font-size: 10.5px; color: #000000; margin-bottom: 48px; font-weight: 700;">Penerima Toko,</div>
                <div class="sign-box">( .................... )</div>
                <div style="font-size: 9.5px; color: #404040; margin-top: 2px;">Cap Toko dan Tanda Tangan</div>
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
        <!-- KOP RESMI PERUSAHAAN & HEADER FAKTUR GABUNGAN -->
        <table class="dm-table">
            <tr>
                <td style="width: 60%; vertical-align: middle;">
                    <table style="width: 100%; border-collapse: collapse; border: none;">
                        <tr>
                            <?php if (!empty($logoSrc)): ?>
                            <td style="width: 1%; white-space: nowrap; vertical-align: middle; padding-right: 8px; border: none;">
                                <img src="<?= $logoSrc ?>" alt="Logo" style="width: 130px; height: auto; max-height: 48px; display: block;">
                            </td>
                            <?php endif; ?>
                            <td style="vertical-align: middle; border: none; padding: 0;">
                                <div class="dm-brand"><?= htmlspecialchars($comp['nama']) ?></div>
                                <div class="dm-sub"><?= htmlspecialchars($comp['tagline']) ?></div>
                                <div class="dm-text-muted"><?= htmlspecialchars($comp['alamat']) ?></div>
                                <div class="dm-text-muted"><?= PrintDocumentHelper::formatContactLine($comp, ' &bull; ') ?></div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 40%; vertical-align: middle; text-align: right;">
                    <div class="dm-title"><?= $docHeaderTitle ?></div>
                    <table class="dm-meta-table">
                        <tr>
                            <td class="dm-meta-lbl">No. Faktur</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val"><strong><?= htmlspecialchars($nomorNota) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="dm-meta-lbl">No. Surat Jalan</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val"><strong><?= $nomorSj ? htmlspecialchars($nomorSj) : '-' ?></strong></td>
                        </tr>
                        <tr>
                            <td class="dm-meta-lbl">Tanggal</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val"><?= !empty($order['tanggal_pesanan']) ? date('d/m/Y', strtotime($order['tanggal_pesanan'])) : date('d/m/Y') ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="dm-divider-double"></div>

        <!-- TUJUAN PELANGGAN & DETAIL PENGIRIMAN/PEMBAYARAN -->
        <table class="dm-table">
            <tr>
                <td style="width: 52%; vertical-align: top; padding-right: 10px;">
                    <div class="dm-section-title">KEPADA TOKO PELANGGAN:</div>
                    <table class="dm-subtable">
                        <tr>
                            <td class="dm-lbl">Nama Toko</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= htmlspecialchars($order['nama_toko'] ?? '-') ?></strong> (<?= htmlspecialchars($order['kode_pelanggan'] ?? '-') ?>)</td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Pemilik/PIC</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($order['nama_pemilik'] ?: '-') ?></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Alamat</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($order['alamat_lengkap'] ?? $order['alamat_toko'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Sales PIC</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($order['nama_sales'] ?: '-') ?></td>
                        </tr>
                    </table>
                </td>
                <td style="width: 48%; vertical-align: top; border-left: 1px dashed #000000; padding-left: 12px;">
                    <div class="dm-section-title">ARMADA &amp; PEMBAYARAN:</div>
                    <table class="dm-subtable">
                        <tr>
                            <td class="dm-lbl">Driver</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= htmlspecialchars($order['nama_driver'] ?: $order['nama_sales'] ?: '-') ?></strong> <?= !empty($order['nopol_driver']) ? '(' . htmlspecialchars($order['nopol_driver']) . ')' : '' ?></td>
                        </tr>
                        <?php if (!empty($order['nama_wilayah']) && $order['nama_wilayah'] !== '-'): ?>
                        <tr>
                            <td class="dm-lbl">Wilayah/Rute</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($order['nama_wilayah']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($isKonsinyasi): ?>
                        <tr>
                            <td class="dm-lbl">Skema Titip</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong>TITIP JUAL (KONSINYASI)</strong></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Sistem Tagih</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val">Ditagih saat Opname Sales</td>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <td class="dm-lbl">Tipe Bayar</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= strtoupper(str_replace('_', ' ', $order['tipe_pembayaran'] ?? 'CASH')) ?></strong> (<?= strtoupper($order['status_pembayaran'] ?? 'BELUM LUNAS') ?>)</td>
                        </tr>
                        <?php if (!empty($order['tanggal_jatuh_tempo'])): ?>
                        <tr>
                            <td class="dm-lbl">Jatuh Tempo</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= date('d/m/Y', strtotime($order['tanggal_jatuh_tempo'])) ?></strong></td>
                        </tr>
                        <?php endif; ?>
                        <?php endif; ?>
                    </table>
                </td>
            </tr>
        </table>

        <div class="dm-divider-single"></div>

        <!-- TABEL DAFTAR BARANG (80 KOLOM COMPATIBLE) -->
        <table class="dm-table dm-items-table" style="width: 100%;">
            <thead>
                <tr>
                    <th style="width: 4%; text-align: center;">NO</th>
                    <th style="width: 15%; text-align: left;">KODE/BARCODE</th>
                    <th style="text-align: left;">NAMA PRODUK</th>
                    <th style="width: 7%; text-align: right;">QTY</th>
                    <th style="width: 7%; text-align: center;">SAT</th>
                    <th style="width: 15%; text-align: right;">HARGA (Rp)</th>
                    <th style="width: 10%; text-align: right;">DISKON</th>
                    <th style="width: 15%; text-align: right;">TOTAL (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $totalQty = 0;
                foreach ($items as $it): 
                    $qtyItem = (int)($it['kuantitas_satuan_dasar'] ?? $it['qty'] ?? 0);
                    $totalQty += $qtyItem;
                    $hargaItem = (float)($it['harga_satuan_deal'] ?? $it['harga'] ?? $it['harga_satuan'] ?? 0);
                    $diskonItem = (float)($it['diskon_item_nominal'] ?? $it['diskon'] ?? 0);
                    $subtotalItem = (float)($it['subtotal'] ?? ($hargaItem * $qtyItem - $diskonItem));
                ?>
                <tr>
                    <td style="text-align: center;"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($it['kode_sku'] ?? '-') ?></td>
                    <td>
                        <strong><?= htmlspecialchars($it['nama_grup'] ?? $it['nama_item'] ?? '-') ?></strong>
                    </td>
                    <td style="text-align: right;"><strong><?= number_format($qtyItem, 0, ',', '.') ?></strong></td>
                    <td style="text-align: center;"><?= htmlspecialchars($it['satuan_dasar'] ?: 'Pcs') ?></td>
                    <td style="text-align: right;"><?= number_format($hargaItem, 0, ',', '.') ?></td>
                    <td style="text-align: right;"><?= $diskonItem > 0 ? '-' . number_format($diskonItem, 0, ',', '.') : '-' ?></td>
                    <td style="text-align: right;"><strong><?= number_format($subtotalItem, 0, ',', '.') ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL ITEM / PRODUK:</td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($totalQty, 0, ',', '.') ?></td>
                    <td style="text-align: center; font-weight: bold;">Pcs</td>
                    <td colspan="2" style="text-align: right; font-weight: bold;">SUBTOTAL BRUTO:</td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format((float)($order['total_bruto'] ?? 0), 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="dm-divider-single"></div>

        <!-- TERBILANG & RINGKASAN PEMBAYARAN -->
        <table class="dm-table" style="margin-bottom: 6px;">
            <tr>
                <td style="vertical-align: top; width: 55%; font-size: 8.5pt; padding-right: 14px;">
                    <strong>Terbilang:</strong><br>
                    <em><?= Format::terbilang((float)($order['total_netto'] ?? 0), true) ?></em><br><br>
                    <?php if (!empty($order['catatan']) || !empty($order['catatan_pesanan'])): ?>
                    <strong>Catatan:</strong> <?= htmlspecialchars($order['catatan'] ?? $order['catatan_pesanan'] ?? '') ?><br>
                    <?php endif; ?>
                    <div style="font-size: 8pt; color: #333; margin-top: 4px;">
                        * Pembayaran sah apabila disertai kuitansi resmi atau transfer ke rekening resmi.<br>
                        * Dokumen App Keren One &bull; Cetak: <?= date('d/m/Y H:i:s') ?>
                    </div>
                </td>
                <td style="vertical-align: top; width: 45%;">
                    <table class="dm-subtable" style="width: 100%;">
                        <tr>
                            <td class="dm-lbl" style="width: 140px;">Subtotal Bruto</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val" style="text-align: right;"><?= Format::rupiah((float)($order['total_bruto'] ?? 0)) ?></td>
                        </tr>
                        <?php if ((float)($order['total_diskon'] ?? 0) > 0): ?>
                        <tr>
                            <td class="dm-lbl" style="width: 140px;">Total Diskon</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val" style="text-align: right; color: #000;">-<?= Format::rupiah((float)$order['total_diskon']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr style="font-weight: bold; font-size: 9.5pt;">
                            <td class="dm-lbl" style="width: 140px; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0;"><?= $isKonsinyasi ? 'TOTAL TITIP RAK' : 'TOTAL NETTO' ?></td>
                            <td class="dm-sep" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0;">:</td>
                            <td class="dm-val" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0; text-align: right;"><?= Format::rupiah((float)($order['total_netto'] ?? 0)) ?></td>
                        </tr>
                        <?php if (!$isKonsinyasi): ?>
                        <tr>
                            <td class="dm-lbl" style="width: 140px;">Total Dibayar</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val" style="text-align: right;"><?= Format::rupiah((float)($order['total_dibayar'] ?? 0)) ?></td>
                        </tr>
                        <?php if ((float)($order['sisa_tagihan'] ?? 0) > 0): ?>
                        <tr style="font-weight: bold;">
                            <td class="dm-lbl" style="width: 140px;">SISA TAGIHAN</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val" style="text-align: right;"><strong><?= Format::rupiah((float)$order['sisa_tagihan']) ?></strong></td>
                        </tr>
                        <?php endif; ?>
                        <?php endif; ?>
                    </table>
                </td>
            </tr>
        </table>

        <!-- TANDA TANGAN 3 PIHAK -->
        <table class="dm-table dm-sig-table">
            <tr>
                <td style="width: 33.3%; text-align: center;">
                    <div class="dm-sig-title">Petugas Gudang,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( <?= htmlspecialchars(!empty($order['nama_petugas_gudang']) ? $order['nama_petugas_gudang'] : 'Petugas Gudang') ?> )</div>
                    <div class="dm-sig-sub">Verifikasi Fisik Sesuai PO</div>
                </td>
                <td style="width: 33.3%; text-align: center;">
                    <div class="dm-sig-title">Driver Pengantar,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( <?= htmlspecialchars($order['nama_driver'] ?: $order['nama_sales'] ?: 'Driver') ?> )</div>
                    <div class="dm-sig-sub">Diantar ke Tujuan</div>
                </td>
                <td style="width: 33.3%; text-align: center;">
                    <div class="dm-sig-title">Penerima Toko,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( .................... )</div>
                    <div class="dm-sig-sub">Cap Toko dan Tanda Tangan</div>
                </td>
            </tr>
        </table>

        <div class="dm-divider-double" style="margin-top: 8px;"></div>

        <!-- FOOTER COPY INDIKATOR RANGKAP NCR -->
        <div class="dm-ncr-footer">
            <span>[ ] Lembar 1 (Putih): Kasir/Accounting</span> &nbsp;&bull;&nbsp;
            <span>[ ] Lembar 2 (Merah): Toko Mitra</span> &nbsp;&bull;&nbsp;
            <span>[ ] Lembar 3 (Kuning): Driver/Logistik</span>
        </div>
    </div>
    <div class="tractor-strip tractor-right"></div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/print_frame.php';

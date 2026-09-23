<?php
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;

$comp = CompanySetting::getAll();
$formatMode = $formatMode ?? PrintDocumentHelper::resolveFormat($_GET['format'] ?? 'standard');
$isKonsinyasi = !empty($order['is_konsinyasi']) || (($order['tipe_pembayaran'] ?? '') === 'konsinyasi') || (isset($order['adalah_tagihan']) && ($order['adalah_tagihan'] === false || $order['adalah_tagihan'] === 'f' || $order['adalah_tagihan'] === 0 || $order['adalah_tagihan'] === 'false'));

$documentTitle = ($isKonsinyasi ? 'Bukti Titip Barang' : 'Faktur Penjualan') . ' - ' . htmlspecialchars($order['nomor_nota']);
$backUrl = Router::url('/customer-orders');
$pdfUrl = Router::url('/customer-orders/invoice/pdf?id=' . $order['id']);
$extraToolbarHtml = '<a href="' . Router::url('/customer-orders/invoice/excel?id=' . $order['id']) . '" class="btn-tb btn-tb-excel">📊 Unduh Excel</a>';
$enableHalfMode = true;

ob_start();
?>
<style>
/* STYLING SPESIFIK FAKTUR PENJUALAN STANDAR A4 */
.header-table { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 20px; }
.company-name { font-size: 22px; font-weight: 900; color: #e11d48; letter-spacing: -0.5px; }
.company-sub { font-size: 11.5px; color: #64748b; line-height: 1.4; margin-top: 4px; }
.invoice-title { text-align: right; font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: 0.5px; }
.invoice-no { text-align: right; font-family: monospace; font-size: 14px; font-weight: 700; color: #059669; margin-top: 4px; }
.meta-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 14px; border-radius: 8px; font-size: 12px; }
.meta-label { font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 3px; }
.meta-value { font-weight: 800; color: #0f172a; }
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.items-table th { background: #f8fafc; color: #475569; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; padding: 10px 12px; border-bottom: 2px solid #cbd5e1; text-align: left; }
.items-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
.font-mono { font-family: monospace; }
.terbilang-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; font-size: 12px; }
.summary-table { width: 100%; font-size: 12px; }
.summary-table td { padding: 4px 0; }
.summary-table .total-row td { font-size: 15px; font-weight: 800; color: #0f172a; border-top: 2px solid #0f172a; border-bottom: 2px solid #0f172a; }
.sign-box { border-bottom: 1px solid #cbd5e1; padding-bottom: 6px; font-weight: 700; color: #0f172a; font-size: 12px; display: inline-block; min-width: 140px; }
</style>

<?php if (empty($isPdf) || $formatMode === 'standard'): ?>
<!-- FORMAT STANDAR LASER / INKJET (A4 PORTRAIT) -->
<div id="sheet-standard" class="page-sheet">
    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td style="vertical-align:top; width:60%;">
                <div class="company-name"><?= htmlspecialchars($comp['nama']) ?></div>
                <div class="company-sub">
                    <?= htmlspecialchars($comp['tagline']) ?><br>
                    <?= htmlspecialchars($comp['alamat']) ?><br>
                    <?= PrintDocumentHelper::formatContactLine($comp, ' • ') ?>
                </div>
            </td>
            <td style="vertical-align:top; width:40%;">
                <div class="invoice-title"><?= $isKonsinyasi ? 'BUKTI TITIP BARANG' : 'FAKTUR PENJUALAN' ?></div>
                <div class="invoice-no"><?= htmlspecialchars($order['nomor_nota']) ?></div>
                <div style="text-align:right; font-size:11.5px; color:#64748b; margin-top:4px;">
                    Tanggal: <strong><?= date('d/m/Y', strtotime($order['tanggal_pesanan'])) ?></strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- METADATA -->
    <table class="meta-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <div class="meta-box">
                    <div class="meta-label">Kepada Toko Pelanggan:</div>
                    <div class="meta-value" style="font-size:14px;"><?= htmlspecialchars($order['nama_toko']) ?></div>
                    <div style="color:#64748b; margin-top:3px;">
                        Kode: <strong><?= htmlspecialchars($order['kode_pelanggan']) ?></strong> 
                        <?php if (!empty($order['nama_pemilik'])): ?>
                        &bull; PIC: <?= htmlspecialchars($order['nama_pemilik']) ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($order['alamat_lengkap'])): ?>
                    <div style="color:#64748b; margin-top:3px; font-size:11.5px;"><?= htmlspecialchars($order['alamat_lengkap']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($order['nomor_whatsapp'])): ?>
                    <div style="color:#64748b; margin-top:2px; font-size:11px;">WA: <?= htmlspecialchars($order['nomor_whatsapp']) ?></div>
                    <?php endif; ?>
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%; vertical-align: top;">
                <div class="meta-box">
                    <div class="meta-label"><?= $isKonsinyasi ? 'Rincian Pengiriman:' : 'Rincian Pengiriman &amp; Pembayaran:' ?></div>
                    <div>Petugas Pengantar: <strong><?= htmlspecialchars($order['nama_sales'] ?: 'Driver Toko') ?></strong></div>
                    <?php if ($isKonsinyasi): ?>
                    <div style="margin-top:3px;">
                        Skema Distribusi: <strong style="color:#d97706;">TITIP JUAL (KONSINYASI)</strong>
                    </div>
                    <div style="margin-top:3px; font-size:11px; color:#64748b;">
                        * Non-Tagihan Langsung (Penagihan via Opname Sales)
                    </div>
                    <?php else: ?>
                    <div style="margin-top:3px;">
                        Tipe Pembayaran: <strong><?= strtoupper(str_replace('_', ' ', $order['tipe_pembayaran'])) ?></strong>
                        <?php if ($order['status_pembayaran'] === 'lunas'): ?>
                        <span style="color:#059669; font-weight:800;"> (LUNAS)</span>
                        <?php else: ?>
                        <span style="color:#dc2626; font-weight:800;"> (TEMPO)</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($order['tanggal_jatuh_tempo'])): ?>
                    <div style="margin-top:3px; color:#dc2626;">
                        Jatuh Tempo: <strong><?= date('d/m/Y', strtotime($order['tanggal_jatuh_tempo'])) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($order['nama_akun_kas'])): ?>
                    <div style="margin-top:2px; font-size:11px; color:#64748b;">
                        Rekening / Kas: <strong><?= htmlspecialchars($order['nama_akun_kas']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- PRODUCT TABLE -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width:35px;">No</th>
                <th>Nama Barang / Varian Snack</th>
                <th class="text-center" style="width:70px;">Satuan</th>
                <th class="text-center" style="width:60px;">Qty</th>
                <th class="text-right" style="width:110px;">Harga Satuan</th>
                <th class="text-right" style="width:90px;">Diskon</th>
                <th class="text-right" style="width:120px;">Subtotal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $idx => $it): ?>
            <tr>
                <td class="text-center" style="color:#64748b;"><?= $idx + 1 ?></td>
                <td>
                    <div class="font-bold"><?= htmlspecialchars($it['nama_item']) ?></div>
                    <div style="font-size:10.5px; color:#64748b;">SKU: <?= htmlspecialchars($it['kode_sku']) ?></div>
                </td>
                <td class="text-center"><?= htmlspecialchars($it['satuan_dasar'] ?: 'pcs') ?></td>
                <td class="text-center font-bold font-mono"><?= number_format($it['kuantitas_satuan_dasar'], 0, ',', '.') ?></td>
                <td class="text-right font-mono"><?= Format::rupiah($it['harga_satuan_deal']) ?></td>
                <td class="text-right font-mono" style="color:#059669;">
                    <?= (float)$it['diskon_item_nominal'] > 0 ? '-' . Format::rupiah($it['diskon_item_nominal']) : '-' ?>
                </td>
                <td class="text-right font-bold font-mono"><?= Format::rupiah($it['subtotal']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- SUMMARY -->
    <table class="summary-grid-table" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <tr>
            <td style="width: 54%; vertical-align: top; padding-right: 18px;">
                <div class="terbilang-box">
                    <div style="font-weight:700; color:#475569; margin-bottom:3px; font-size: 11px; text-transform: uppercase;">Terbilang:</div>
                    <div style="font-style:italic; font-weight:600; color:#0f172a; line-height: 1.4;">
                        "<?= Format::terbilang($order['total_netto']) ?>"
                    </div>
                    <?php if (!empty($order['catatan'])): ?>
                    <div style="margin-top:8px; font-size:11.5px; color:#64748b;">
                        <strong>Catatan:</strong> <?= htmlspecialchars($order['catatan']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($comp['nomor_rekening'])): ?>
                    <div style="margin-top:8px; font-size:11px; color:#334155; background:#f1f5f9; padding:6px 10px; border-radius:4px; border-left:3px solid #3b82f6;">
                        Pembayaran Transfer: <strong><?= htmlspecialchars($comp['nama_bank']) ?></strong> Rek: <strong style="font-family:monospace;"><?= htmlspecialchars($comp['nomor_rekening']) ?></strong> a.n <strong><?= htmlspecialchars($comp['atas_nama_bank']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($comp['catatan_faktur'])): ?>
                    <div style="margin-top:6px; font-size:10.5px; color:#64748b; font-style:italic;">
                        * <?= htmlspecialchars($comp['catatan_faktur']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </td>
            <td style="width: 46%; vertical-align: top;">
                <table class="summary-table">
                    <tr>
                        <td style="color:#64748b;"><?= $isKonsinyasi ? 'Subtotal Valuasi:' : 'Subtotal Bruto:' ?></td>
                        <td class="text-right font-mono font-bold"><?= Format::rupiah($order['total_bruto']) ?></td>
                    </tr>
                    <?php if ((float)$order['total_diskon'] > 0): ?>
                    <tr>
                        <td style="color:#64748b;">Total Diskon:</td>
                        <td class="text-right font-mono" style="color:#059669;">-<?= Format::rupiah($order['total_diskon']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td style="padding-top:8px;"><?= $isKonsinyasi ? 'TOTAL TITIP RAK:' : 'TOTAL NETTO:' ?></td>
                        <td class="text-right font-mono" style="padding-top:8px;"><?= Format::rupiah($order['total_netto']) ?></td>
                    </tr>
                    <?php if (!$isKonsinyasi && $order['status_pembayaran'] !== 'lunas'): ?>
                    <tr>
                        <td style="color:#dc2626; font-size:11.5px; padding-top:4px;">Sisa Tagihan Tempo:</td>
                        <td class="text-right font-mono font-bold" style="color:#dc2626; font-size:12px; padding-top:4px;">
                            <?= Format::rupiah(max(0, (float)$order['total_netto'] - (float)$order['total_dibayar'])) ?>
                        </td>
                    </tr>
                    <?php elseif ($isKonsinyasi): ?>
                    <tr>
                        <td style="color:#d97706; font-size:11px; padding-top:4px;" colspan="2" class="text-right">
                            <em>* Non-Tagihan Langsung (Omzet ditagih saat Opname Sales)</em>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </td>
        </tr>
    </table>

    <!-- SIGNATURES -->
    <table class="signature-grid-table" style="width: 100%; border-collapse: collapse; margin-top: 36px; text-align: center;">
        <tr>
            <td style="width: 32%; vertical-align: top;">
                <div style="font-size: 11px; color: #475569; margin-bottom: 48px; font-weight: 700;">Tanda Terima Pelanggan,</div>
                <div class="sign-box">( <?= htmlspecialchars($order['nama_pemilik'] ?: $order['nama_toko']) ?> )</div>
                <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">Cap Toko &amp; Tanda Tangan</div>
            </td>
            <td style="width: 36%; vertical-align: top;">
                <div style="font-size: 11px; color: #475569; margin-bottom: 48px; font-weight: 700;">Petugas Pengirim / Sales,</div>
                <div class="sign-box">( <?= htmlspecialchars($order['nama_sales'] ?: 'Pengirim') ?> )</div>
                <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">Sales Driver Distribusi</div>
            </td>
            <td style="width: 32%; vertical-align: top;">
                <div style="font-size: 11px; color: #475569; margin-bottom: 48px; font-weight: 700;">Hormat Kami,</div>
                <div class="sign-box">( Admin <?= htmlspecialchars($comp['nama']) ?> )</div>
                <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">Bagian Keuangan &amp; Kasir</div>
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
        <!-- KOP RESMI PERUSAHAAN & HEADER FAKTUR -->
        <table class="dm-table">
            <tr>
                <td style="width: 55%; vertical-align: top;">
                    <div class="dm-brand"><?= htmlspecialchars($comp['nama']) ?></div>
                    <div class="dm-sub"><?= htmlspecialchars($comp['tagline']) ?></div>
                    <div class="dm-text-muted"><?= htmlspecialchars($comp['alamat']) ?></div>
                    <div class="dm-text-muted"><?= PrintDocumentHelper::formatContactLine($comp, ' &bull; ') ?></div>
                </td>
                <td style="width: 45%; vertical-align: top; text-align: right;">
                    <div class="dm-title"><?= $isKonsinyasi ? 'BUKTI TITIP BARANG' : 'FAKTUR PENJUALAN' ?></div>
                    <table class="dm-meta-table">
                        <tr>
                            <td class="dm-meta-lbl">No. Faktur / Nota</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val"><strong><?= htmlspecialchars($order['nomor_nota']) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="dm-meta-lbl">Tanggal Pesanan</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val"><?= date('d/m/Y', strtotime($order['tanggal_pesanan'])) ?></td>
                        </tr>
                        <tr>
                            <td class="dm-meta-lbl">Skema Transaksi</td>
                            <td class="dm-meta-sep">:</td>
                            <td class="dm-meta-val"><?= $isKonsinyasi ? '[ KONSINYASI ]' : ($order['status_pembayaran'] === 'lunas' ? '[ LUNAS ]' : '[ TEMPO ]') ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="dm-divider-double"></div>

        <!-- TUJUAN PELANGGAN & DETAIL PEMBAYARAN -->
        <table class="dm-table">
            <tr>
                <td style="width: 52%; vertical-align: top; padding-right: 12px;">
                    <div class="dm-section-title">KEPADA TOKO PELANGGAN:</div>
                    <table class="dm-subtable">
                        <tr>
                            <td class="dm-lbl">Nama Toko</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= htmlspecialchars($order['nama_toko']) ?></strong> (<?= htmlspecialchars($order['kode_pelanggan']) ?>)</td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Pemilik / PIC</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($order['nama_pemilik'] ?: '-') ?></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Alamat Toko</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($order['alamat_lengkap'] ?: '-') ?></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">No. Telp / WA</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($order['nomor_whatsapp'] ?: '-') ?></td>
                        </tr>
                    </table>
                </td>
                <td style="width: 48%; vertical-align: top; border-left: 1px dashed #000000; padding-left: 14px;">
                    <div class="dm-section-title"><?= $isKonsinyasi ? 'KETENTUAN KONSINYASI:' : 'SKEMA PEMBAYARAN:' ?></div>
                    <table class="dm-subtable">
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
                        <tr>
                            <td class="dm-lbl">Sales Motoris</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($order['nama_sales'] ?: 'Sales Motoris') ?></td>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <td class="dm-lbl">Tipe Bayar</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= strtoupper(str_replace('_', ' ', $order['tipe_pembayaran'])) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="dm-lbl">Status Bayar</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= strtoupper($order['status_pembayaran']) ?></strong></td>
                        </tr>
                        <?php if (!empty($order['tanggal_jatuh_tempo'])): ?>
                        <tr>
                            <td class="dm-lbl">Jatuh Tempo</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><strong><?= date('d/m/Y', strtotime($order['tanggal_jatuh_tempo'])) ?></strong></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($order['nama_akun_kas'])): ?>
                        <tr>
                            <td class="dm-lbl">Rekening/Kas</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val"><?= htmlspecialchars($order['nama_akun_kas']) ?></td>
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
                    <th style="width: 14%; text-align: left;">KODE SKU</th>
                    <th style="text-align: left;">NAMA BARANG / PRODUK</th>
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
                    $qtyItem = (int)($it['kuantitas_satuan_dasar'] ?? 0);
                    $totalQty += $qtyItem;
                    $hargaItem = (float)($it['harga_satuan_deal'] ?? $it['harga_satuan'] ?? $it['harga_satuan_dasar'] ?? 0);
                    $diskonItem = (float)($it['diskon_item_nominal'] ?? $it['diskon_nominal'] ?? 0);
                    $subtotalItem = (float)($it['subtotal'] ?? ($hargaItem * $qtyItem - $diskonItem));
                ?>
                <tr>
                    <td style="text-align: center;"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($it['kode_sku'] ?? '-') ?></td>
                    <td><strong><?= htmlspecialchars($it['nama_item'] ?? '-') ?></strong></td>
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
                    <em># <?= Format::terbilang((float)$order['total_netto'], true) ?> #</em><br><br>
                    <?php if (!empty($order['catatan'])): ?>
                    <strong>Catatan:</strong> <?= htmlspecialchars($order['catatan']) ?><br>
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
                            <td class="dm-val" style="text-align: right;"><?= Format::rupiah((float)$order['total_bruto']) ?></td>
                        </tr>
                        <?php if ((float)$order['total_diskon'] > 0): ?>
                        <tr>
                            <td class="dm-lbl" style="width: 140px;">Total Diskon</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val" style="text-align: right; color: #000;">-<?= Format::rupiah((float)$order['total_diskon']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr style="font-weight: bold; font-size: 9.5pt;">
                            <td class="dm-lbl" style="width: 140px; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0;"><?= $isKonsinyasi ? 'TOTAL TITIP RAK' : 'TOTAL NETTO' ?></td>
                            <td class="dm-sep" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0;">:</td>
                            <td class="dm-val" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0; text-align: right;"><?= Format::rupiah((float)$order['total_netto']) ?></td>
                        </tr>
                        <?php if (!$isKonsinyasi): ?>
                        <tr>
                            <td class="dm-lbl" style="width: 140px;">Total Dibayar</td>
                            <td class="dm-sep">:</td>
                            <td class="dm-val" style="text-align: right;"><?= Format::rupiah((float)$order['total_dibayar']) ?></td>
                        </tr>
                        <?php if ((float)$order['sisa_tagihan'] > 0): ?>
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
                    <div class="dm-sig-title">Penerima / Toko Pelanggan,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( <?= htmlspecialchars($order['nama_pemilik'] ?: $order['nama_toko']) ?> )</div>
                    <div class="dm-sig-sub">Cap Toko &amp; Tanda Tangan</div>
                </td>
                <td style="width: 33.3%; text-align: center;">
                    <div class="dm-sig-title">Petugas Pengirim / Sales,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( <?= htmlspecialchars($order['nama_sales'] ?: 'Petugas ERP') ?> )</div>
                    <div class="dm-sig-sub">Sales Driver Distribusi</div>
                </td>
                <td style="width: 33.3%; text-align: center;">
                    <div class="dm-sig-title">Hormat Kami,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( Bagian Keuangan )</div>
                    <div class="dm-sig-sub">Admin <?= htmlspecialchars($comp['nama']) ?></div>
                </td>
            </tr>
        </table>

        <div class="dm-divider-double" style="margin-top: 8px;"></div>

        <!-- FOOTER COPY INDIKATOR RANGKAP NCR -->
        <div class="dm-ncr-footer">
            <span>[ ] Lembar 1 (Putih): Kasir / Accounting</span> &nbsp;&bull;&nbsp;
            <span>[ ] Lembar 2 (Merah): Toko Mitra</span> &nbsp;&bull;&nbsp;
            <span>[ ] Lembar 3 (Kuning): Sales Driver</span>
        </div>
    </div>
    <div class="tractor-strip tractor-right"></div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/print_frame.php';

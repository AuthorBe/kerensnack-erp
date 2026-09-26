<?php
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;

$comp = CompanySetting::getAll();
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
/* STYLING SPESIFIK DOKUMEN HYBRID (FAKTUR & SURAT JALAN GABUNGAN) A4 */
.header-table { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 14px; margin-bottom: 16px; }
.company-name { font-size: 20px; font-weight: 900; color: #e11d48; letter-spacing: -0.5px; }
.company-sub { font-size: 11px; color: #64748b; line-height: 1.4; margin-top: 3px; }
.invoice-title { text-align: right; font-size: 17px; font-weight: 900; color: #0f172a; letter-spacing: 0.5px; text-transform: uppercase; }
.dual-ref-box { text-align: right; margin-top: 6px; font-size: 12px; }
.dual-ref-line { font-family: monospace; font-weight: 700; color: #0f172a; }
.dual-ref-nota { color: #0284c7; }
.dual-ref-sj { color: #059669; }
.meta-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px; font-size: 11.5px; }
.meta-label { font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; }
.meta-value { font-weight: 800; color: #0f172a; }
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
.items-table th { background: #f8fafc; color: #334155; font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; padding: 8px 10px; border-bottom: 2px solid #cbd5e1; text-align: left; }
.items-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; font-size: 11.5px; }
.font-mono { font-family: monospace; }
.terbilang-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 11.5px; }
.summary-table { width: 100%; font-size: 11.5px; }
.summary-table td { padding: 3px 0; }
.summary-table .total-row td { font-size: 14px; font-weight: 800; color: #0f172a; border-top: 2px solid #0f172a; border-bottom: 2px solid #0f172a; }
.sign-box { border-bottom: 1px solid #94a3b8; padding-bottom: 4px; font-weight: 700; color: #0f172a; font-size: 11.5px; display: inline-block; min-width: 140px; }
</style>

<?php if (empty($isPdf) || $formatMode === 'standard'): ?>
<!-- FORMAT STANDAR LASER / INKJET (A4 PORTRAIT) -->
<div id="sheet-standard" class="page-sheet">
    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td style="vertical-align:top; width:54%;">
                <div class="company-name"><?= htmlspecialchars($comp['nama']) ?></div>
                <div class="company-sub">
                    <?= htmlspecialchars($comp['tagline']) ?><br>
                    <?= htmlspecialchars($comp['alamat']) ?><br>
                    <?= PrintDocumentHelper::formatContactLine($comp, ' • ') ?>
                </div>
            </td>
            <td style="vertical-align:top; width:46%;">
                <div class="invoice-title"><?= $docHeaderTitle ?></div>
                <div class="dual-ref-box">
                    <div class="dual-ref-line">
                        No. Faktur: <strong class="dual-ref-nota"><?= htmlspecialchars($nomorNota) ?></strong>
                    </div>
                    <div class="dual-ref-line" style="margin-top:2px;">
                        No. Surat Jalan: <strong class="dual-ref-sj"><?= $nomorSj ? htmlspecialchars($nomorSj) : '[ Menunggu Pengiriman ]' ?></strong>
                    </div>
                    <div style="font-size:11px; color:#64748b; margin-top:3px;">
                        Tanggal: <strong><?= !empty($order['tanggal_pesanan']) ? date('d/m/Y', strtotime($order['tanggal_pesanan'])) : date('d/m/Y') ?></strong>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- METADATA 2 KOLOM (KIRI: PELANGGAN & SALES | KANAN: LOGISTIK & PEMBAYARAN) -->
    <table class="meta-table" style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <tr>
            <!-- KOLOM KIRI: TOKO & SALES -->
            <td style="width: 48%; vertical-align: top;">
                <div class="meta-box">
                    <div class="meta-label">Tujuan Pengiriman &amp; Pelanggan:</div>
                    <div class="meta-value" style="font-size:13.5px;"><?= htmlspecialchars($order['nama_toko'] ?? '-') ?></div>
                    <div style="color:#64748b; margin-top:2px;">
                        Kode: <strong><?= htmlspecialchars($order['kode_pelanggan'] ?? '-') ?></strong> 
                        <?php if (!empty($order['nama_pemilik'])): ?>
                        &bull; PIC: <?= htmlspecialchars($order['nama_pemilik']) ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($order['alamat_lengkap']) || !empty($order['alamat_toko'])): ?>
                    <div style="color:#64748b; margin-top:2px; font-size:11px;"><?= htmlspecialchars($order['alamat_lengkap'] ?? $order['alamat_toko'] ?? '') ?></div>
                    <?php endif; ?>
                    <?php if (!empty($order['nomor_whatsapp'])): ?>
                    <div style="color:#64748b; margin-top:1px; font-size:11px;">WA: <?= htmlspecialchars($order['nomor_whatsapp']) ?></div>
                    <?php endif; ?>
                    <div style="margin-top:4px; font-size:11px; color:#334155; border-top:1px dashed #e2e8f0; padding-top:3px;">
                        Sales Pembina: <strong><?= htmlspecialchars($order['nama_sales'] ?: 'Sales Area') ?></strong>
                    </div>
                </div>
            </td>
            <td style="width: 4%;"></td>
            <!-- KOLOM KANAN: ARMADA, DRIVER & SYARAT BAYAR -->
            <td style="width: 48%; vertical-align: top;">
                <div class="meta-box">
                    <div class="meta-label">Armada Pengiriman &amp; Pembayaran:</div>
                    <div>
                        Driver / Pengantar: <strong><?= htmlspecialchars($order['nama_driver'] ?: $order['nama_sales'] ?: 'Driver Toko') ?></strong>
                        <?php if (!empty($order['nopol_driver'])): ?>
                        <span style="color:#64748b;">(<?= htmlspecialchars($order['nopol_driver']) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($order['nama_wilayah']) && $order['nama_wilayah'] !== '-'): ?>
                    <div style="margin-top:2px; font-size:11px; color:#64748b;">
                        Rute / Wilayah: <strong><?= htmlspecialchars($order['nama_wilayah']) ?></strong>
                        <?php if (!empty($order['kode_rute']) && $order['kode_rute'] !== '-'): ?>
                        (<?= htmlspecialchars($order['kode_rute']) ?>)
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($isKonsinyasi): ?>
                    <div style="margin-top:4px; border-top:1px dashed #e2e8f0; padding-top:3px;">
                        Skema Distribusi: <strong style="color:#d97706;">TITIP JUAL (KONSINYASI)</strong>
                    </div>
                    <div style="font-size:10.5px; color:#64748b;">
                        * Non-Tagihan Langsung (Penagihan via Form Opname Sales)
                    </div>
                    <?php else: ?>
                    <div style="margin-top:4px; border-top:1px dashed #e2e8f0; padding-top:3px;">
                        Tipe Pembayaran: <strong><?= strtoupper(str_replace('_', ' ', $order['tipe_pembayaran'] ?? 'CASH')) ?></strong>
                        <?php if (($order['status_pembayaran'] ?? '') === 'lunas'): ?>
                        <span style="color:#059669; font-weight:800;"> (LUNAS)</span>
                        <?php else: ?>
                        <span style="color:#dc2626; font-weight:800;"> (TEMPO)</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($order['tanggal_jatuh_tempo'])): ?>
                    <div style="margin-top:1px; color:#dc2626; font-size:11px;">
                        Jatuh Tempo: <strong><?= date('d/m/Y', strtotime($order['tanggal_jatuh_tempo'])) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($order['nama_akun_kas'])): ?>
                    <div style="margin-top:1px; font-size:10.5px; color:#64748b;">
                        Kas / Rekening: <strong><?= htmlspecialchars($order['nama_akun_kas']) ?></strong>
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
                <th class="text-center" style="width:30px;">No</th>
                <th style="width:110px;">Kode SKU</th>
                <th>Nama Produk / Varian Snack</th>
                <th class="text-center" style="width:60px;">Satuan</th>
                <th class="text-center" style="width:55px;">Qty</th>
                <th class="text-right" style="width:105px;">Harga Satuan</th>
                <th class="text-right" style="width:85px;">Diskon</th>
                <th class="text-right" style="width:115px;">Subtotal (Rp)</th>
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
                <td class="text-center" style="color:#64748b;"><?= $idx + 1 ?></td>
                <td class="font-mono" style="font-size:11px; color:#475569;"><?= htmlspecialchars($it['kode_sku'] ?? '-') ?></td>
                <td>
                    <div class="font-bold" style="color:#0f172a;"><?= htmlspecialchars($it['nama_item'] ?? '-') ?></div>
                </td>
                <td class="text-center"><?= htmlspecialchars($it['satuan_dasar'] ?: 'pcs') ?></td>
                <td class="text-center font-bold font-mono"><?= number_format($qtyVal, 0, ',', '.') ?></td>
                <td class="text-right font-mono">
                    <?= Format::rupiah($hargaVal) ?>
                </td>
                <td class="text-right font-mono" style="color:#059669;">
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
    <table class="summary-grid-table" style="width: 100%; border-collapse: collapse; margin-top: 6px;">
        <tr>
            <td style="width: 55%; vertical-align: top; padding-right: 16px;">
                <div class="terbilang-box">
                    <div style="font-weight:700; color:#475569; margin-bottom:2px; font-size: 10.5px; text-transform: uppercase;">Terbilang:</div>
                    <div style="font-style:italic; font-weight:600; color:#0f172a; line-height: 1.35;">
                        "<?= Format::terbilang((float)($order['total_netto'] ?? 0)) ?>"
                    </div>
                    <?php if (!empty($order['catatan']) || !empty($order['catatan_pesanan'])): ?>
                    <div style="margin-top:6px; font-size:11px; color:#64748b;">
                        <strong>Catatan:</strong> <?= htmlspecialchars($order['catatan'] ?? $order['catatan_pesanan'] ?? '') ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($comp['nomor_rekening'])): ?>
                    <div style="margin-top:6px; font-size:10.5px; color:#334155; background:#f1f5f9; padding:5px 8px; border-radius:4px; border-left:3px solid #3b82f6;">
                        Pembayaran Transfer: <strong><?= htmlspecialchars($comp['nama_bank']) ?></strong> Rek: <strong style="font-family:monospace;"><?= htmlspecialchars($comp['nomor_rekening']) ?></strong> a.n <strong><?= htmlspecialchars($comp['atas_nama_bank']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($comp['catatan_faktur'])): ?>
                    <div style="margin-top:4px; font-size:10px; color:#64748b; font-style:italic;">
                        * <?= htmlspecialchars($comp['catatan_faktur']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </td>
            <td style="width: 45%; vertical-align: top;">
                <table class="summary-table">
                    <tr>
                        <td style="color:#64748b;"><?= $isKonsinyasi ? 'Subtotal Valuasi:' : 'Subtotal Bruto:' ?></td>
                        <td class="text-right font-mono font-bold"><?= Format::rupiah((float)($order['total_bruto'] ?? 0)) ?></td>
                    </tr>
                    <?php if ((float)($order['total_diskon'] ?? 0) > 0): ?>
                    <tr>
                        <td style="color:#64748b;">Total Diskon:</td>
                        <td class="text-right font-mono" style="color:#059669;">-<?= Format::rupiah((float)$order['total_diskon']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td style="padding-top:6px;"><?= $isKonsinyasi ? 'TOTAL TITIP RAK:' : 'TOTAL NETTO:' ?></td>
                        <td class="text-right font-mono" style="padding-top:6px;"><?= Format::rupiah((float)($order['total_netto'] ?? 0)) ?></td>
                    </tr>
                    <?php if (!$isKonsinyasi && ($order['status_pembayaran'] ?? '') !== 'lunas'): ?>
                    <tr>
                        <td style="color:#dc2626; font-size:11px; padding-top:3px;">Sisa Tagihan:</td>
                        <td class="text-right font-mono font-bold" style="color:#dc2626; font-size:11.5px; padding-top:3px;">
                            <?= Format::rupiah(max(0, (float)($order['total_netto'] ?? 0) - (float)($order['total_dibayar'] ?? 0))) ?>
                        </td>
                    </tr>
                    <?php elseif ($isKonsinyasi): ?>
                    <tr>
                        <td style="color:#d97706; font-size:10.5px; padding-top:3px;" colspan="2" class="text-right">
                            <em>* Non-Tagihan Langsung (Ditagih saat Opname Sales)</em>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </td>
        </tr>
    </table>

    <!-- SIGNATURES (3 PIHAK: GUDANG, DRIVER, PENERIMA TOKO) -->
    <table class="signature-grid-table" style="width: 100%; border-collapse: collapse; margin-top: 30px; text-align: center;">
        <tr>
            <td style="width: 33.3%; vertical-align: top;">
                <div style="font-size: 10.5px; color: #475569; margin-bottom: 42px; font-weight: 700;">Petugas Gudang (Pengirim),</div>
                <div class="sign-box">( Staf Logistik Gudang )</div>
                <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Diserahkan dari Gudang</div>
            </td>
            <td style="width: 33.3%; vertical-align: top;">
                <div style="font-size: 10.5px; color: #475569; margin-bottom: 42px; font-weight: 700;">Driver / Sopir Pengantar,</div>
                <div class="sign-box">( <?= htmlspecialchars($order['nama_driver'] ?: $order['nama_sales'] ?: 'Driver Pengantar') ?> )</div>
                <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Diantar &amp; Diserahkan ke Toko</div>
            </td>
            <td style="width: 33.3%; vertical-align: top;">
                <div style="font-size: 10.5px; color: #475569; margin-bottom: 42px; font-weight: 700;">Penerima / Toko Pelanggan,</div>
                <div class="sign-box">( <?= htmlspecialchars($order['nama_pemilik'] ?: $order['nama_toko'] ?: 'Penerima Toko') ?> )</div>
                <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Cap Toko &amp; Tanda Tangan</div>
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
                <td style="width: 52%; vertical-align: top;">
                    <div class="dm-brand"><?= htmlspecialchars($comp['nama']) ?></div>
                    <div class="dm-sub"><?= htmlspecialchars($comp['tagline']) ?></div>
                    <div class="dm-text-muted"><?= htmlspecialchars($comp['alamat']) ?></div>
                    <div class="dm-text-muted"><?= PrintDocumentHelper::formatContactLine($comp, ' &bull; ') ?></div>
                </td>
                <td style="width: 48%; vertical-align: top; text-align: right;">
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
                        <strong><?= htmlspecialchars($it['nama_item'] ?? '-') ?></strong>
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
                    <em># <?= Format::terbilang((float)($order['total_netto'] ?? 0), true) ?> #</em><br><br>
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
                    <div class="dm-sig-title">Petugas Gudang (Pengirim),</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( Logistik Gudang )</div>
                    <div class="dm-sig-sub">Diserahkan dari Gudang</div>
                </td>
                <td style="width: 33.3%; text-align: center;">
                    <div class="dm-sig-title">Driver / Pengantar,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( <?= htmlspecialchars($order['nama_driver'] ?: $order['nama_sales'] ?: 'Driver') ?> )</div>
                    <div class="dm-sig-sub">Diantar ke Tujuan</div>
                </td>
                <td style="width: 33.3%; text-align: center;">
                    <div class="dm-sig-title">Penerima / Toko Pelanggan,</div>
                    <div class="dm-sig-space"></div>
                    <div class="dm-sig-line">( <?= htmlspecialchars($order['nama_pemilik'] ?: $order['nama_toko'] ?: 'Penerima') ?> )</div>
                    <div class="dm-sig-sub">Cap Toko &amp; Tanda Tangan</div>
                </td>
            </tr>
        </table>

        <div class="dm-divider-double" style="margin-top: 8px;"></div>

        <!-- FOOTER COPY INDIKATOR RANGKAP NCR -->
        <div class="dm-ncr-footer">
            <span>[ ] Lembar 1 (Putih): Kasir / Accounting</span> &nbsp;&bull;&nbsp;
            <span>[ ] Lembar 2 (Merah): Toko Mitra</span> &nbsp;&bull;&nbsp;
            <span>[ ] Lembar 3 (Kuning): Driver / Logistik</span>
        </div>
    </div>
    <div class="tractor-strip tractor-right"></div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/print_frame.php';

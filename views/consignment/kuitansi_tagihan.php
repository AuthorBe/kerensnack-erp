<?php
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CompanySetting;
use App\Helpers\PrintDocumentHelper;

$comp = CompanySetting::getAll();

$totalNetto   = (float)($order['total_netto'] ?? 0);
$totalDibayar = (float)($order['total_dibayar'] ?? 0);
$totalDiskon  = (float)($order['total_diskon'] ?? 0);
$sisaTagihan  = (float)($order['sisa_tagihan'] ?? 0);
$isLunas      = ($order['status_pembayaran'] ?? '') === 'lunas';
$statusText   = $isLunas ? 'LUNAS' : 'SEBAGIAN (CICIL)';

$nomorKuitansi = 'KUI-' . str_replace(['INV-', 'PO-', 'NOTA-'], '', (string)($order['nomor_nota'] ?? '')) . '-' . date('ymd');
$documentTitle = 'Kuitansi Pembayaran - ' . htmlspecialchars($order['nama_toko'] ?? 'Toko') . ' - ' . htmlspecialchars($order['nomor_nota'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($documentTitle) ?></title>

    <!-- PWA & Mobile Web App Meta Tags -->
    <meta name="theme-color" content="#881337">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Keren One">
    <meta name="application-name" content="Keren One">

    <link rel="icon" type="image/x-icon" href="<?= Router::asset('/favicon/favicon.ico') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            padding: 24px;
            font-size: 13px;
            line-height: 1.5;
        }
        
        /* Top Navigation Bar */
        .no-print-bar {
            max-width: 820px;
            margin: 0 auto 20px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background-color: #881337;
            color: #ffffff;
            border-color: #700f2b;
        }
        .btn-primary:hover {
            background-color: #9f1239;
        }
        .btn-secondary {
            background-color: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }
        .btn-secondary:hover {
            background-color: #f8fafc;
            color: #0f172a;
        }

        /* Receipt Card */
        .kuitansi-card {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            padding: 36px 40px;
            border: 1px solid #e2e8f0;
            position: relative;
        }

        /* Watermark */
        .kuitansi-watermark {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            font-size: 76px;
            font-weight: 900;
            color: rgba(16, 185, 129, 0.08);
            border: 6px solid rgba(16, 185, 129, 0.12);
            padding: 10px 40px;
            border-radius: 20px;
            pointer-events: none;
            user-select: none;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .kuitansi-watermark.is-partial {
            color: rgba(245, 158, 11, 0.08);
            border-color: rgba(245, 158, 11, 0.14);
        }

        /* Header */
        .kuitansi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 20px;
            border-bottom: 2px solid #881337;
            margin-bottom: 22px;
        }
        .brand-title {
            font-size: 22px;
            font-weight: 900;
            color: #881337;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }
        .brand-subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
            max-width: 440px;
            line-height: 1.4;
        }
        .kuitansi-title-box {
            text-align: right;
        }
        .kuitansi-title {
            font-size: 18px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .kuitansi-no {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            font-weight: 800;
            color: #881337;
            margin-top: 2px;
        }
        .kuitansi-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10.5px;
            font-weight: 900;
            letter-spacing: 0.04em;
        }
        .kuitansi-badge.is-lunas {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .kuitansi-badge.is-partial {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        /* Meta Grid */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            background: #f8fafc;
            padding: 16px 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 22px;
        }
        .meta-group h4 {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            font-weight: 800;
            margin-bottom: 3px;
        }
        .meta-group p {
            font-size: 13px;
            font-weight: 800;
            color: #1e293b;
        }
        .meta-group span {
            font-size: 11.5px;
            color: #64748b;
        }

        /* Financial Breakdown */
        .finance-summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }
        .finance-summary-table th {
            background: #f8fafc;
            padding: 10px 14px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        .finance-summary-table td {
            padding: 11px 14px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 12.5px;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        .text-right {
            text-align: right;
        }

        /* Payments History List */
        .history-box {
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .history-header {
            background: #f8fafc;
            padding: 8px 14px;
            font-size: 11px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .history-table th {
            background: #ffffff;
            padding: 7px 12px;
            font-size: 10.5px;
            font-weight: 700;
            color: #64748b;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .history-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        /* Signatures */
        .sig-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            margin-top: 30px;
            text-align: center;
            page-break-inside: avoid;
        }
        .sig-box {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .sig-title {
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 50px;
        }
        .sig-line {
            font-weight: 800;
            font-size: 12.5px;
            color: #0f172a;
            border-bottom: 1px dashed #64748b;
            min-width: 170px;
            padding-bottom: 3px;
        }

        /* Footer Note */
        .kuitansi-footer {
            margin-top: 26px;
            padding-top: 14px;
            border-top: 1px dashed #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            color: #94a3b8;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .no-print-bar {
                display: none !important;
            }
            .kuitansi-card {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            @page {
                size: A4 portrait;
                margin: 15mm;
            }
        }
    </style>
</head>
<body>

    <!-- TOP NAVIGATION & ACTION BAR -->
    <div class="no-print-bar">
        <a href="<?= Router::url('/consignment/tagihan?tab=daftar') ?>" 
           onclick="if (window.opener || window.history.length > 1) { window.history.back(); } else { window.location.href = '<?= Router::url('/consignment/tagihan?tab=daftar') ?>'; } return false;"
           class="btn btn-secondary">
            <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
            <span>Kembali ke Tagihan</span>
        </a>

        <div style="display:flex; align-items:center; gap:8px;">
            <?php 
                $waPhone = preg_replace('/[^0-9]/', '', (string)($order['nomor_whatsapp'] ?? ''));
                if (str_starts_with($waPhone, '0')) {
                    $waPhone = '62' . substr($waPhone, 1);
                }
                $waText = "Halo " . ($order['nama_toko'] ?? 'Toko') . ", terima kasih telah melakukan pembayaran konsinyasi kami.\n\n"
                    . "🧾 *TANDA TERIMA PEMBAYARAN KONSINYASI*\n"
                    . "• No. Tagihan: *" . ($order['nomor_nota'] ?? '-') . "*\n"
                    . "• No. Kuitansi: *" . $nomorKuitansi . "*\n"
                    . "• Tanggal: " . date('d/m/Y') . "\n"
                    . "• Total Tagihan: " . Format::rupiah($totalNetto) . "\n"
                    . "• Total Terbayar: *" . Format::rupiah($totalDibayar) . "*\n"
                    . ($totalDiskon > 0 ? "• Potongan/Diskon: " . Format::rupiah($totalDiskon) . "\n" : "")
                    . "• Sisa Piutang: *" . Format::rupiah($sisaTagihan) . "*\n"
                    . "• Status: *" . $statusText . "*\n\n"
                    . "Dokumen kuitansi ini sah diterbitkan oleh sistem " . ($comp['nama_perusahaan'] ?? 'Keren One') . ".";
                $waUrl = "https://wa.me/" . $waPhone . "?text=" . rawurlencode($waText);
            ?>
            <?php if (!empty($waPhone)): ?>
            <a href="<?= $waUrl ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="background:#25d366;color:#ffffff;border-color:#16a34a;">
                <i data-lucide="message-circle" style="width:16px;height:16px;"></i>
                <span>Kirim WA ke Toko</span>
            </a>
            <?php endif; ?>

            <button type="button" onclick="window.print()" class="btn btn-primary">
                <i data-lucide="printer" style="width:16px;height:16px;"></i>
                <span>Cetak Kuitansi</span>
            </button>
        </div>
    </div>

    <!-- MAIN RECEIPT CARD -->
    <div class="kuitansi-card">
        
        <!-- Watermark -->
        <div class="kuitansi-watermark <?= $isLunas ? '' : 'is-partial' ?>">
            <?= $isLunas ? 'LUNAS' : 'SEBAGIAN' ?>
        </div>

        <!-- HEADER -->
        <div class="kuitansi-header">
            <div>
                <div class="brand-title"><?= htmlspecialchars($comp['nama_perusahaan'] ?? 'KEREN ONE ERP') ?></div>
                <div class="brand-subtitle">
                    <?= htmlspecialchars($comp['alamat'] ?? 'Distribusi & Titip Jual Snack Nusantara') ?><br>
                    <?= PrintDocumentHelper::formatContactLine($comp) ?>
                </div>
            </div>

            <div class="kuitansi-title-box">
                <div class="kuitansi-title">Kuitansi Pembayaran</div>
                <div class="kuitansi-no"><?= htmlspecialchars($nomorKuitansi) ?></div>
                <div>
                    <span class="kuitansi-badge <?= $isLunas ? 'is-lunas' : 'is-partial' ?>">
                        STATUS: <?= $statusText ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- META GRID -->
        <div class="meta-grid">
            <div class="meta-group">
                <h4>Diterima Dari (Toko Mitra)</h4>
                <p><?= htmlspecialchars($order['nama_toko'] ?? '-') ?> <?= !empty($order['kode_pelanggan']) ? '(' . htmlspecialchars($order['kode_pelanggan']) . ')' : '' ?></p>
                <span>Pemilik: <?= htmlspecialchars($order['nama_pemilik'] ?? '-') ?> &bull; WA: <?= htmlspecialchars($order['nomor_whatsapp'] ?? '-') ?></span>
            </div>

            <div class="meta-group">
                <h4>Referensi Dokumen &amp; Petugas</h4>
                <p>Nota: <span class="font-mono text-sky-700"><?= htmlspecialchars($order['nomor_nota'] ?? '-') ?></span></p>
                <span>Tgl Nota: <?= date('d/m/Y', strtotime($order['tanggal_pesanan'] ?? 'now')) ?> &bull; Sales: <?= htmlspecialchars($order['sales_name'] ?? 'Sales Lapangan') ?></span>
            </div>
        </div>

        <!-- FINANCIAL BREAKDOWN TABLE -->
        <table class="finance-summary-table">
            <thead>
                <tr>
                    <th>Rincian Finansial Tagihan</th>
                    <th class="text-right" style="width:190px;">Nominal (IDR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Nilai Tagihan Faktur Konsinyasi</strong><br>
                        <span style="font-size:11px;color:#64748b;">Penjualan laku terjual dari produk konsinyasi</span>
                    </td>
                    <td class="text-right font-mono font-bold" style="font-size:13px;">
                        <?= Format::rupiah($totalNetto) ?>
                    </td>
                </tr>

                <?php if ($totalDiskon > 0): ?>
                <tr style="background:#fffbeb;">
                    <td>
                        <strong style="color:#b45309;">Potongan / Retur Susulan / Adjustment</strong><br>
                        <span style="font-size:11px;color:#92400e;">Penyesuaian resmi atas retur susulan / kesepakatan toko</span>
                    </td>
                    <td class="text-right font-mono font-bold text-amber-600">
                        - <?= Format::rupiah($totalDiskon) ?>
                    </td>
                </tr>
                <?php endif; ?>

                <tr style="background:#ecfdf5;">
                    <td>
                        <strong style="color:#065f46;">Total Kas Masuk Terbayar</strong><br>
                        <span style="font-size:11px;color:#047857;">Akumulasi penerimaan uang tunai / transfer bank</span>
                    </td>
                    <td class="text-right font-mono font-black text-emerald-600" style="font-size:14.5px;">
                        <?= Format::rupiah($totalDibayar) ?>
                    </td>
                </tr>

                <tr style="background:#f8fafc; font-weight:800;">
                    <td>
                        <strong>Sisa Piutang Berjalan</strong><br>
                        <span style="font-size:11px;color:#64748b;"><?= $sisaTagihan <= 0 ? 'Faktur telah lunas sepenuhnya' : 'Sisa kewajiban pembayaran yang belum dilunasi' ?></span>
                    </td>
                    <td class="text-right font-mono font-black <?= $sisaTagihan <= 0 ? 'text-emerald-600' : 'text-rose-600' ?>" style="font-size:14px;">
                        <?= Format::rupiah($sisaTagihan) ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- PAYMENT HISTORY (LEDGER LOGS) -->
        <?php if (!empty($payments)): ?>
        <div class="history-box">
            <div class="history-header">
                <i data-lucide="history" style="width:14px;height:14px;"></i>
                <span>Riwayat Transaksi Pembayaran / Setoran Kas</span>
            </div>
            <table class="history-table">
                <thead>
                    <tr>
                        <th style="width:110px;">Tanggal</th>
                        <th>Rekening Kas / Bank</th>
                        <th>Keterangan</th>
                        <th style="width:140px;">Pencatat</th>
                        <th class="text-right" style="width:140px;">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="font-mono"><?= date('d/m/Y', strtotime($p['tanggal_transaksi'])) ?></td>
                        <td><strong><?= htmlspecialchars($p['nama_akun'] ?? 'Kas') ?></strong></td>
                        <td style="color:#64748b;"><?= htmlspecialchars($p['keterangan'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['dicatat_oleh_nama'] ?? 'Kasir') ?></td>
                        <td class="text-right font-mono font-bold text-emerald-600"><?= Format::rupiah((float)$p['nominal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- SIGNATURES -->
        <div class="sig-grid">
            <div class="sig-box">
                <div class="sig-title">Penyetor / Toko Mitra</div>
                <div class="sig-line"><?= htmlspecialchars($order['nama_pemilik'] ?: $order['nama_toko']) ?></div>
                <span style="font-size:10.5px;color:#64748b;margin-top:2px;">Mitra Konsinyasi</span>
            </div>

            <div class="sig-box">
                <div class="sig-title">Kasir / Keuangan Perusahaan</div>
                <div class="sig-line"><?= htmlspecialchars($order['pembuat_nota_nama'] ?? 'Bagian Keuangan') ?></div>
                <span style="font-size:10.5px;color:#64748b;margin-top:2px;"><?= htmlspecialchars($comp['nama_perusahaan'] ?? 'Keren One') ?></span>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="kuitansi-footer">
            <span>Dicetak secara otomatis oleh Sistem Keren One pada <?= date('d/m/Y H:i') ?> WIB</span>
            <span>Bukti pembayaran ini sah tanpa tanda tangan basah jika berstempel QR sistem.</span>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>
</html>

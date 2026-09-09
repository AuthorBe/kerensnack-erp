<?php
use App\Helpers\Format;
use App\Helpers\CSRF;
use App\Core\Router;
use App\Core\Auth;
ob_start();

$totalLakuRp = (float)($visit['total_laku_nominal'] ?? 0);
$hasInvoice = !empty($visit['nomor_nota']) && !empty($visit['pesanan_id']);
$isNihil = $totalLakuRp <= 0;
$stBayar = strtolower((string)($visit['status_pembayaran'] ?? 'belum_lunas'));

$totalQtyLaku = (int)array_sum(array_column($details, 'jumlah_laku_terjual'));
$totalQtyRusak = (int)array_sum(array_column($details, 'retur_rusak'));
$totalQtyBagus = (int)array_sum(array_column($details, 'retur_bagus'));
$totalSisaRak = (int)array_sum(array_column($details, 'sisa_fisik_di_rak'));
$totalStokAwal = (int)array_sum(array_column($details, 'stok_titip_awal'));
$totalKerugianRusak = (float)array_sum(array_column($details, 'nilai_kerugian_rusak'));
$skuCount = count($details);

// Hitung kunjungan lain dari toko yang sama yang belum ditagih
$otherUnbilledList = $otherUnbilledVisits ?? [];
$otherUnbilledCount = count($otherUnbilledList);
$otherUnbilledExceptThis = 0;
foreach ($otherUnbilledList as $ouv) {
    if ((string)$ouv['id'] !== (string)$visit['id']) {
        $otherUnbilledExceptThis++;
    }
}

// Navigasi Kembali Cerdas
$ref = $_GET['ref'] ?? '';
$backUrl = Router::url('/consignment/stok-rak');
$backTitle = 'Daftar Stok Rak';
if ($ref === 'riwayat') {
    $backUrl = Router::url('/consignment/riwayat-kunjungan');
    $backTitle = 'Riwayat Kunjungan';
} elseif ($ref === 'laporan') {
    $backUrl = Router::url('/consignment/laporan-penjualan');
    $backTitle = 'Laporan Penjualan';
} elseif ($ref === 'tagihan') {
    $backUrl = Router::url('/consignment/tagihan');
    $backTitle = 'Menu Tagihan';
} elseif ($ref === 'opname') {
    $backUrl = Router::url('/consignment/opname?pelanggan_id=' . urlencode((string)$visit['pelanggan_id']));
    $backTitle = 'Form Opname';
}

// Format WhatsApp text
$waPhone = !empty($visit['nomor_whatsapp']) ? preg_replace('/[^0-9]/', '', (string)$visit['nomor_whatsapp']) : '';
if (!empty($waPhone) && str_starts_with($waPhone, '0')) {
    $waPhone = '62' . substr($waPhone, 1);
}

$storeTitle = ($visit['nama_toko'] ?? 'Toko Mitra');
if (!empty($visit['kode_pelanggan'])) {
    $storeTitle .= ' (' . $visit['kode_pelanggan'] . ')';
}

$waMessageLines = [
    "*KEREN SNACK — BUKTI KUNJUNGAN KONSINYASI*",
    "Toko: " . $storeTitle,
    "Tanggal: " . date('d/m/Y', strtotime($visit['tanggal_kunjungan'] ?? 'now')),
    "No. Kunjungan: " . ($visit['nomor_kunjungan'] ?? '-'),
    "Sales / Driver: " . ($visit['sales_name'] ?? 'Petugas'),
];

if ($hasInvoice) {
    $waMessageLines[] = "No. Nota Faktur: " . ($visit['nomor_nota'] ?? '-');
} elseif ($totalLakuRp > 0) {
    $waMessageLines[] = "Status Tagihan: Menunggu Tagihan";
} else {
    $waMessageLines[] = "Status: Nihil Penjualan (Stok Rak Utuh)";
}

$waMessageLines[] = "----------------------------------------";
$waMessageLines[] = "*RINCIAN STOK & PENJUALAN:*";

foreach ($details as $d) {
    $laku = (int)$d['jumlah_laku_terjual'];
    $rusak = (int)$d['retur_rusak'];
    $bagus = (int)$d['retur_bagus'];
    $sisa = (int)$d['sisa_fisik_di_rak'];
    $nama = $d['nama_item'] ?? 'Produk';
    
    $itemLine = "• {$nama}: Laku {$laku} pcs (Sisa rak: {$sisa} pcs)";
    if ($rusak > 0) $itemLine .= " [BS: {$rusak} pcs]";
    if ($bagus > 0) $itemLine .= " [Retur: {$bagus} pcs]";
    $waMessageLines[] = $itemLine;
}

$waMessageLines[] = "----------------------------------------";
$waMessageLines[] = "*TOTAL PENJUALAN: " . Format::rupiah($totalLakuRp) . "*";

if ($hasInvoice) {
    $waMessageLines[] = "Sudah Dibayar: " . Format::rupiah((float)($visit['total_dibayar'] ?? 0));
    $waMessageLines[] = "Sisa Tagihan: " . Format::rupiah((float)($visit['sisa_tagihan'] ?? $totalLakuRp));
    $waMessageLines[] = "Status Bayar: " . strtoupper(str_replace('_', ' ', $stBayar));
}

$waMessageLines[] = "";
$waMessageLines[] = "Terima kasih atas kerja samanya!";
$waMessageLines[] = "_Pesan otomatis sistem ERP Keren Snack_";

$fullWaMessage = implode("\n", $waMessageLines);
$encodedWaUrl = !empty($waPhone) ? "https://wa.me/{$waPhone}?text=" . urlencode($fullWaMessage) : "";
?>

<style>
/* ========================================================================= */
/* HASIL KUNJUNGAN KONSINYASI — ERP DESIGN SYSTEM ALIGNMENT                  */
/* ========================================================================= */

.oh-page-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding-bottom: 90px;
}

/* Standalone Airy Card */
.oh-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 18px;
    padding: 20px 22px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

@media (max-width: 640px) {
    .oh-card {
        padding: 16px;
        border-radius: 14px;
    }
}

.oh-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 14px;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--color-hairline);
}

/* ========================================================================= */
/* 1. EXECUTIVE STATUS BANNER (3-ZONE BALANCED LAYOUT)                       */
/* ========================================================================= */
.oh-status-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 20px;
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    position: relative;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

@media (min-width: 640px) {
    .oh-status-card {
        padding: 20px 22px;
    }
}

@media (min-width: 1024px) {
    .oh-status-card {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 20px 26px;
    }
}

.oh-status-card.is-pending {
    background: linear-gradient(135deg, rgba(2, 132, 199, 0.04) 0%, var(--color-canvas) 100%);
    border-color: rgba(2, 132, 199, 0.25);
}
.dark .oh-status-card.is-pending {
    background: linear-gradient(135deg, rgba(2, 132, 199, 0.08) 0%, var(--color-canvas) 100%);
    border-color: rgba(2, 132, 199, 0.35);
}

.oh-status-card.is-active {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.04) 0%, var(--color-canvas) 100%);
    border-color: rgba(16, 185, 129, 0.25);
}
.dark .oh-status-card.is-active {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, var(--color-canvas) 100%);
    border-color: rgba(16, 185, 129, 0.35);
}

.oh-status-card.is-nihil {
    background: linear-gradient(135deg, rgba(100, 116, 139, 0.03) 0%, var(--color-canvas) 100%);
    border-color: rgba(100, 116, 139, 0.22);
}
.dark .oh-status-card.is-nihil {
    background: linear-gradient(135deg, rgba(100, 116, 139, 0.06) 0%, var(--color-canvas) 100%);
    border-color: rgba(100, 116, 139, 0.3);
}

/* Zone 1: Context & Meta (Left) */
.oh-status-info-col {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    flex: 1 1 0%;
    min-width: 0;
}

.oh-status-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}
.is-pending .oh-status-icon-box {
    background: rgba(2, 132, 199, 0.12);
    color: #0284c7;
    border: 1px solid rgba(2, 132, 199, 0.2);
}
.is-active .oh-status-icon-box {
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.2);
}
.is-nihil .oh-status-icon-box {
    background: rgba(100, 116, 139, 0.1);
    color: #64748b;
    border: 1px solid rgba(100, 116, 139, 0.2);
}

.oh-status-meta {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}

.oh-status-badge-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.oh-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.oh-status-pill.is-pending {
    background: #0284c7;
    color: #ffffff;
}
.oh-status-pill.is-active {
    background: #10b981;
    color: #ffffff;
}
.oh-status-pill.is-nihil {
    background: #64748b;
    color: #ffffff;
}

.oh-status-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--color-ink);
    letter-spacing: -0.01em;
    margin: 2px 0 0 0;
}

.oh-status-sub {
    font-size: 12px;
    color: var(--color-ink-secondary);
    line-height: 1.5;
    margin: 0;
}

.oh-status-chip-note {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 600;
    color: #0284c7;
    background: rgba(2, 132, 199, 0.08);
    border: 1px solid rgba(2, 132, 199, 0.2);
    padding: 3px 8px;
    border-radius: 6px;
    margin-top: 4px;
    align-self: flex-start;
}
.dark .oh-status-chip-note {
    background: rgba(2, 132, 199, 0.14);
    border-color: rgba(2, 132, 199, 0.3);
    color: #38bdf8;
}

/* Zone 2: Financial Stat Highlight (Middle, Desktop) */
.oh-status-stat-col {
    display: none;
    padding: 0 20px;
    border-left: 1px solid var(--color-hairline);
    border-right: 1px solid var(--color-hairline);
    flex-shrink: 0;
}

@media (min-width: 1024px) {
    .oh-status-stat-col {
        display: block;
        min-width: 180px;
    }
}

.oh-stat-col-label {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--color-ink-mute);
    margin-bottom: 2px;
}

.oh-stat-col-val {
    font-size: 20px;
    font-weight: 900;
    font-family: var(--font-mono);
    line-height: 1.15;
}

.oh-stat-col-sub {
    font-size: 11px;
    color: var(--color-ink-secondary);
    margin-top: 2px;
}

/* Mobile Stat Pill (< 1024px) */
.oh-status-stat-mobile {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    border-radius: 12px;
    padding: 8px 12px;
    font-size: 12px;
}

@media (min-width: 1024px) {
    .oh-status-stat-mobile {
        display: none;
    }
}

/* Zone 3: Actions Column (Right) */
.oh-status-actions-col {
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 100%;
}

@media (min-width: 640px) {
    .oh-status-actions-col {
        flex-direction: row;
        align-items: center;
        gap: 10px;
        width: 100%;
    }
}

@media (min-width: 1024px) {
    .oh-status-actions-col {
        flex-direction: column;
        align-items: stretch;
        width: 250px;
        flex-shrink: 0;
        gap: 8px;
    }
}

.oh-status-actions-col .btn {
    width: 100% !important;
    justify-content: center !important;
    padding: 8px 14px !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    border-radius: 12px !important;
    white-space: nowrap !important;
    box-sizing: border-box !important;
    height: 38px !important;
}

@media (max-width: 639px) {
    .oh-status-actions-col .btn {
        height: 42px !important;
        font-size: 12.5px !important;
    }
}

/* 2. KPI Grid (4 Metrics) */
.oh-kpi-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
@media (min-width: 1024px) {
    .oh-kpi-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
}

.oh-kpi-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 16px;
    padding: 16px 18px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 115px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.oh-kpi-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.oh-kpi-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.oh-kpi-title {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--color-ink-mute);
}
.oh-kpi-value {
    font-size: 18px;
    font-weight: 900;
    font-family: var(--font-mono);
    line-height: 1.2;
    color: var(--color-ink);
}
@media (min-width: 640px) {
    .oh-kpi-value {
        font-size: 22px;
    }
}
.oh-kpi-subtitle {
    font-size: 11px;
    color: var(--color-ink-secondary);
    margin-top: 4px;
}

/* 3. Product Breakdown Table & Mobile Cards */
.oh-table-wrap {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 12px;
    border: 1px solid var(--color-hairline);
}
.oh-data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    text-align: left;
}
.oh-data-table th {
    background: var(--color-canvas-soft);
    padding: 12px 14px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--color-ink-mute);
    border-bottom: 1px solid var(--color-hairline);
    white-space: nowrap;
}
.oh-data-table td {
    padding: 12px 14px;
    border-bottom: 1px solid var(--color-hairline);
    vertical-align: middle;
    color: var(--color-ink);
}
.oh-data-table tbody tr:hover {
    background-color: var(--color-canvas-soft);
}
.oh-data-table tfoot td {
    background: var(--color-canvas-soft);
    padding: 13px 14px;
    font-weight: 800;
    border-top: 2px solid var(--color-hairline);
}

/* Mobile Product Cards (< 768px) */
.oh-mobile-card {
    background: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    border-radius: 14px;
    padding: 14px;
    margin-bottom: 10px;
}
.oh-flow-pills {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 10px;
    padding: 8px 6px;
    text-align: center;
    margin: 10px 0;
}
.oh-flow-cell span {
    display: block;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--color-ink-mute);
}
.oh-flow-cell strong {
    display: block;
    font-size: 13px;
    font-family: var(--font-mono);
    color: var(--color-ink);
    margin-top: 1px;
}

/* 4. WhatsApp Preview Box */
.oh-wa-box {
    background: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    border-radius: 12px;
    padding: 12px 14px;
    font-family: var(--font-mono);
    font-size: 11px;
    line-height: 1.5;
    color: var(--color-ink-secondary);
    white-space: pre-wrap;
    max-height: 140px;
    overflow-y: auto;
}

/* 5. Thermal Receipt Print Styling */
@media print {
    body * {
        visibility: hidden !important;
    }
    #printableReceipt, #printableReceipt * {
        visibility: visible !important;
    }
    #printableReceipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: 80mm;
        margin: 0 auto;
        padding: 4mm;
        background: #ffffff !important;
        color: #000000 !important;
        font-family: 'Courier New', Courier, monospace !important;
        font-size: 11px !important;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<div class="oh-page-container" x-data="opnameHasilApp()">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER (Unified ERP Component)                                    -->
    <!-- ========================================================================= -->
    <div class="page-header no-print">
        <div class="page-header-body">
            <a href="<?= $backUrl ?>" class="btn btn-secondary btn-sm p-2 rounded-xl" title="Kembali ke <?= htmlspecialchars($backTitle) ?>">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#10b981;"></span>
                    <span>Modul Konsinyasi &bull; Ringkasan Hasil Opname &amp; Kunjungan</span>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="page-title text-xl sm:text-2xl"><?= htmlspecialchars($visit['nama_toko']) ?></h1>
                    <?php if (!empty($visit['kode_pelanggan'])): ?>
                        <span class="badge badge-secondary font-mono font-bold"><?= htmlspecialchars($visit['kode_pelanggan']) ?></span>
                    <?php endif; ?>
                </div>
                <p class="page-subtitle text-xs sm:text-sm">
                    <span><?= htmlspecialchars($visit['alamat_lengkap'] ?? 'Alamat toko tidak tersedia') ?></span>
                    &bull; No. Kunjungan: <strong class="font-mono text-sky-600"><?= htmlspecialchars($visit['nomor_kunjungan']) ?></strong>
                    &bull; <?= date('d M Y', strtotime($visit['tanggal_kunjungan'])) ?>
                    &bull; Petugas: <strong><?= htmlspecialchars($visit['sales_name']) ?></strong>
                </p>
            </div>
        </div>

        <div class="page-header-actions flex items-center gap-2 flex-wrap">
            <a href="<?= Router::url('/consignment/stok-rak') ?>" class="btn btn-secondary btn-sm flex items-center gap-1.5" style="border-radius:12px;font-weight:700;">
                <i data-lucide="boxes" class="w-4 h-4"></i>
                <span>Opname Lain</span>
            </a>
            <a href="<?= Router::url('/consignment/tagihan') ?>" class="btn btn-secondary btn-sm flex items-center gap-1.5" style="border-radius:12px;font-weight:700;">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Menu Tagihan</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. INVOICE STATUS CARD (3-ZONE BALANCED EXECUTIVE LAYOUT)                 -->
    <!-- ========================================================================= -->
    <div class="oh-status-card no-print <?= $isNihil ? 'is-nihil' : ($hasInvoice ? 'is-active' : 'is-pending') ?>">
        <?php if ($isNihil): ?>
            <!-- State 3: Nihil Penjualan -->
            <!-- Zone 1: Context & Meta -->
            <div class="oh-status-info-col">
                <div class="oh-status-icon-box">
                    <i data-lucide="minus-circle" class="w-6 h-6"></i>
                </div>
                <div class="oh-status-meta">
                    <div class="oh-status-badge-row">
                        <span class="oh-status-pill is-nihil">
                            NIHIL PENJUALAN
                        </span>
                        <span class="text-xs font-bold" style="color:var(--color-ink-mute);">Stok Rak Utuh</span>
                    </div>
                    <h3 class="oh-status-title">Tidak Ada Penjualan yang Ditagihkan</h3>
                    <p class="oh-status-sub">
                        Seluruh saldo fisik rak toko masih utuh sesuai titipan sebelumnya. Tidak ada omzet atau tagihan baru pada kunjungan ini.
                    </p>
                </div>
            </div>

            <!-- Zone 2: Stat Highlight (Desktop) -->
            <div class="oh-status-stat-col">
                <div class="oh-stat-col-label">Total Penjualan</div>
                <div class="oh-stat-col-val text-slate-500 dark:text-slate-400">Rp 0</div>
                <div class="oh-stat-col-sub">0 pcs terjual &bull; <?= $skuCount ?> SKU dicek</div>
            </div>

            <!-- Zone 2 (Mobile < 1024px) -->
            <div class="oh-status-stat-mobile">
                <span class="text-xs font-bold" style="color:var(--color-ink-mute);">TOTAL PENJUALAN</span>
                <span class="font-mono font-bold text-slate-500">Rp 0 (0 pcs)</span>
            </div>

            <!-- Zone 3: Actions Column -->
            <div class="oh-status-actions-col">
                <div class="flex items-center justify-center p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300">
                    <i data-lucide="shield-check" class="w-4 h-4 mr-1.5 text-slate-500"></i>
                    <span>Rak Terverifikasi Fisik</span>
                </div>
            </div>

        <?php elseif ($hasInvoice): ?>
            <!-- State 2: Sudah Ditagih (Faktur Resmi Terbit) -->
            <!-- Zone 1: Context & Meta -->
            <div class="oh-status-info-col">
                <div class="oh-status-icon-box">
                    <i data-lucide="receipt-check" class="w-6 h-6"></i>
                </div>
                <div class="oh-status-meta">
                    <div class="oh-status-badge-row">
                        <span class="oh-status-pill is-active">
                            SUDAH DITAGIH
                        </span>
                        <?php if ($stBayar === 'lunas'): ?>
                            <span class="badge badge-success" style="font-weight:800;font-size:10px;padding:2px 8px;border-radius:999px;">
                                LUNAS
                            </span>
                        <?php elseif ($stBayar === 'sebagian'): ?>
                            <span class="badge badge-warning" style="font-weight:800;font-size:10px;padding:2px 8px;border-radius:999px;">
                                SEBAGIAN (CICIL)
                            </span>
                        <?php else: ?>
                            <span class="badge badge-danger" style="font-weight:800;font-size:10px;padding:2px 8px;border-radius:999px;">
                                BELUM LUNAS
                            </span>
                        <?php endif; ?>
                    </div>
                    <h3 class="oh-status-title flex items-center gap-1.5 font-mono text-emerald-600 dark:text-emerald-400">
                        <span>Faktur:</span>
                        <span><?= htmlspecialchars($visit['nomor_nota']) ?></span>
                    </h3>
                    <p class="oh-status-sub">
                        Faktur tagihan konsinyasi telah diterbitkan secara resmi dari hasil opname fisik ini.
                    </p>
                </div>
            </div>

            <!-- Zone 2: Stat Highlight (Desktop) -->
            <div class="oh-status-stat-col">
                <div class="oh-stat-col-label">Sisa Tagihan</div>
                <div class="oh-stat-col-val <?= (float)($visit['sisa_tagihan'] ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' ?>">
                    <?= Format::rupiah((float)($visit['sisa_tagihan'] ?? 0)) ?>
                </div>
                <div class="oh-stat-col-sub">
                    Total: <?= Format::rupiah((float)($visit['total_netto'] ?? $totalLakuRp)) ?>
                    <?php if ((float)($visit['total_dibayar'] ?? 0) > 0): ?>
                        &bull; Bayar: <?= Format::rupiah((float)$visit['total_dibayar']) ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Zone 2 (Mobile < 1024px) -->
            <div class="oh-status-stat-mobile">
                <div>
                    <span class="text-xs font-bold" style="color:var(--color-ink-mute);">SISA TAGIHAN: </span>
                    <strong class="font-mono <?= (float)($visit['sisa_tagihan'] ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' ?>">
                        <?= Format::rupiah((float)($visit['sisa_tagihan'] ?? 0)) ?>
                    </strong>
                </div>
                <div class="text-xs" style="color:var(--color-ink-secondary);">
                    Total: <strong><?= Format::rupiah((float)($visit['total_netto'] ?? $totalLakuRp)) ?></strong>
                </div>
            </div>

            <!-- Zone 3: Actions Column -->
            <div class="oh-status-actions-col">
                <a href="<?= Router::url('/consignment/opname/hasil/pdf?kunjungan_id=' . urlencode((string)$visit['id'])) ?>" 
                   target="_blank" 
                   class="btn btn-secondary btn-sm flex items-center gap-1.5"
                   title="Unduh Faktur Tagihan Resmi PDF (A4)">
                    <i data-lucide="file-down" class="w-4 h-4"></i>
                    <span>Unduh PDF</span>
                </a>

                <?php if (!empty($canManageTagihan) && (float)($visit['sisa_tagihan'] ?? 0) > 0): ?>
                    <button type="button" 
                            @click="openPaymentModal()" 
                            class="btn btn-success btn-sm flex items-center gap-1.5"
                            style="background:#10b981;border-color:#059669;color:#fff;"
                            title="Catat pelunasan atau cicilan pembayaran">
                        <i data-lucide="wallet" class="w-4 h-4"></i>
                        <span>Catat Bayar</span>
                    </button>
                <?php endif; ?>

                <a href="<?= Router::url('/consignment/tagihan?tab=daftar') ?>" 
                   class="btn btn-secondary btn-sm flex items-center gap-1.5"
                   title="Buka daftar tagihan konsinyasi">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                    <span>Daftar Tagihan</span>
                </a>
            </div>

        <?php else: ?>
            <!-- State 1: Belum Ditagih (Menunggu Tagihan) -->
            <!-- Zone 1: Context & Meta -->
            <div class="oh-status-info-col">
                <div class="oh-status-icon-box">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
                <div class="oh-status-meta">
                    <div class="oh-status-badge-row">
                        <span class="oh-status-pill is-pending">
                            MENUNGGU TAGIHAN
                        </span>
                        <span class="text-xs font-bold" style="color:var(--color-ink-mute);">Siap Diterbitkan Faktur</span>
                    </div>
                    <h3 class="oh-status-title">Faktur Konsinyasi Belum Diterbitkan</h3>
                    <p class="oh-status-sub">
                        Kunjungan fisik telah selesai diverifikasi dan siap ditagihkan ke toko mitra.
                    </p>
                    <?php if ($otherUnbilledExceptThis > 0): ?>
                        <div class="oh-status-chip-note">
                            <i data-lucide="layers" class="w-3.5 h-3.5 flex-shrink-0"></i>
                            <span>+ <?= $otherUnbilledExceptThis ?> kunjungan lainnya di toko ini juga belum ditagih</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Zone 2: Stat Highlight (Desktop) -->
            <div class="oh-status-stat-col">
                <div class="oh-stat-col-label">Total Siap Ditagih</div>
                <div class="oh-stat-col-val text-sky-600 dark:text-sky-400"><?= Format::rupiah($totalLakuRp) ?></div>
                <div class="oh-stat-col-sub"><?= $totalQtyLaku ?> pcs &bull; <?= $skuCount ?> SKU terjual</div>
            </div>

            <!-- Zone 2 (Mobile < 1024px) -->
            <div class="oh-status-stat-mobile">
                <div>
                    <span class="text-xs font-bold" style="color:var(--color-ink-mute);">TOTAL SIAP DITAGIH: </span>
                    <strong class="font-mono text-sky-600 dark:text-sky-400"><?= Format::rupiah($totalLakuRp) ?></strong>
                </div>
                <div class="text-xs" style="color:var(--color-ink-secondary);">
                    <?= $totalQtyLaku ?> pcs (<?= $skuCount ?> SKU)
                </div>
            </div>

            <!-- Zone 3: Actions Column -->
            <div class="oh-status-actions-col">
                <?php if (!empty($canManageTagihan)): ?>
                    <button type="button" 
                            @click="openGenerateModal()" 
                            class="btn btn-primary btn-sm flex items-center gap-1.5"
                            style="background:#0284c7;border-color:#0284c7;"
                            title="Terbitkan faktur tagihan resmi untuk kunjungan ini sekarang">
                        <i data-lucide="receipt" class="w-4 h-4"></i>
                        <span>Terbitkan Tagihan Sekarang</span>
                    </button>
                <?php endif; ?>

                <a href="<?= Router::url('/consignment/tagihan?tab=buat&pelanggan_id=' . urlencode((string)$visit['pelanggan_id'])) ?>" 
                   class="btn btn-secondary btn-sm flex items-center gap-1.5"
                   title="Buka menu Tagihan Konsinyasi untuk toko ini">
                    <i data-lucide="layers" class="w-4 h-4 text-sky-600 dark:text-sky-400"></i>
                    <span>Gabungkan di Menu Tagihan <?= $otherUnbilledCount > 1 ? '(' . $otherUnbilledCount . ')' : '' ?> &rarr;</span>
                </a>
            </div>
        <?php endif; ?>
    </div>


    <!-- ========================================================================= -->
    <!-- 3. KPI METRIC SUMMARY GRID (4 CARDS)                                      -->
    <!-- ========================================================================= -->
    <div class="oh-kpi-grid no-print">
        <!-- Card 1: Total Omzet Terjual -->
        <div class="oh-kpi-card">
            <div class="oh-kpi-header">
                <span class="oh-kpi-title">Total Penjualan</span>
                <i data-lucide="trending-up" class="w-4 h-4 text-emerald-500"></i>
            </div>
            <div class="oh-kpi-value text-emerald-600 dark:text-emerald-400"><?= Format::rupiah($totalLakuRp) ?></div>
            <div class="oh-kpi-subtitle"><?= $totalQtyLaku ?> pcs laku terjual</div>
        </div>

        <!-- Card 2: Total Qty Laku -->
        <div class="oh-kpi-card">
            <div class="oh-kpi-header">
                <span class="oh-kpi-title">Barang Laku</span>
                <i data-lucide="package-check" class="w-4 h-4 text-sky-500"></i>
            </div>
            <div class="oh-kpi-value text-sky-600 dark:text-sky-400"><?= $totalQtyLaku ?> pcs</div>
            <div class="oh-kpi-subtitle">Dari <?= count($details) ?> SKU terdaftar</div>
        </div>

        <!-- Card 3: Total Retur -->
        <div class="oh-kpi-card">
            <div class="oh-kpi-header">
                <span class="oh-kpi-title">Total Retur</span>
                <i data-lucide="undo-2" class="w-4 h-4 text-rose-500"></i>
            </div>
            <div class="oh-kpi-value <?= ($totalQtyRusak + $totalQtyBagus > 0) ? 'text-rose-600 dark:text-rose-400' : '' ?>">
                <?= $totalQtyRusak + $totalQtyBagus ?> pcs
            </div>
            <div class="oh-kpi-subtitle"><?= $totalQtyRusak ?> BS • <?= $totalQtyBagus ?> Bagus</div>
        </div>

        <!-- Card 4: Sisa Rak Baru -->
        <div class="oh-kpi-card">
            <div class="oh-kpi-header">
                <span class="oh-kpi-title">Sisa Rak Baru</span>
                <i data-lucide="layers" class="w-4 h-4 text-cyan-500"></i>
            </div>
            <div class="oh-kpi-value text-cyan-600 dark:text-cyan-400"><?= $totalSisaRak ?> pcs</div>
            <div class="oh-kpi-subtitle">Saldo rak fisik saat ini</div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. PRODUCT BREAKDOWN SECTION (TABLE & MOBILE CARDS WITH LIVE SEARCH)     -->
    <!-- ========================================================================= -->
    <div class="oh-card no-print">
        <div class="oh-card-header flex-col sm:flex-row sm:items-center gap-3">
            <div>
                <h3 class="font-bold text-sm sm:text-base flex items-center gap-2" style="color:var(--color-ink);">
                    <i data-lucide="boxes" class="w-5 h-5 text-sky-600"></i>
                    <span>Rincian Stok &amp; Penjualan Rak</span>
                </h3>
                <p class="text-xs mt-0.5" style="color:var(--color-ink-mute);">Mutasi barang titip awal, laku terjual, retur, dan saldo rak baru</p>
            </div>

            <!-- Real-time Filter Search Input -->
            <div class="w-full sm:w-64">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-mute"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="Cari item atau SKU..." 
                           class="form-input text-xs w-full pl-9 pr-3 py-1.5 rounded-xl">
                </div>
            </div>
        </div>

        <!-- 4A. MOBILE PRODUCT CARDS (< 768px) -->
        <div class="block md:hidden">
            <template x-for="item in filteredItems" :key="item.id">
                <div class="oh-mobile-card">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <strong class="text-sm block" style="color:var(--color-ink);" x-text="item.nama_item"></strong>
                            <span class="font-mono text-[10px]" style="color:var(--color-ink-mute);" x-text="item.kode_sku || '-'"></span>
                        </div>
                        <div class="text-right font-mono">
                            <span class="text-[10px] block" style="color:var(--color-ink-mute);">Subtotal</span>
                            <strong class="text-sm text-emerald-600 dark:text-emerald-400" x-text="formatRupiah(item.subtotal_laku)"></strong>
                        </div>
                    </div>

                    <div class="oh-flow-pills">
                        <div class="oh-flow-cell">
                            <span>Titip</span>
                            <strong x-text="item.stok_titip_awal"></strong>
                        </div>
                        <div class="oh-flow-cell">
                            <span style="color:#10b981;">Laku</span>
                            <strong style="color:#10b981;" x-text="item.jumlah_laku_terjual"></strong>
                        </div>
                        <div class="oh-flow-cell">
                            <span style="color:#f43f5e;">Retur</span>
                            <strong :style="(item.retur_rusak + item.retur_bagus > 0) ? 'color:#f43f5e;' : ''" x-text="item.retur_rusak + item.retur_bagus"></strong>
                        </div>
                        <div class="oh-flow-cell">
                            <span style="color:#0284c7;">Sisa Rak</span>
                            <strong style="color:#0284c7;" x-text="item.sisa_fisik_di_rak"></strong>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] pt-1.5 border-t border-hairline" style="color:var(--color-ink-mute);">
                        <span>Harga Deal: <span class="font-mono font-bold" x-text="formatRupiah(item.harga_satuan_deal)"></span></span>
                        <span x-text="`${item.jumlah_laku_terjual} pcs × ${formatRupiah(item.harga_satuan_deal)}`"></span>
                    </div>
                </div>
            </template>

            <div x-show="filteredItems.length === 0" x-cloak class="p-8 text-center text-mute text-xs">
                Tidak ada produk yang cocok dengan kata kunci pencarian.
            </div>
        </div>

        <!-- 4B. DESKTOP DATA TABLE (>= 768px) -->
        <div class="hidden md:block oh-table-wrap">
            <table class="oh-data-table">
                <thead>
                    <tr>
                        <th>Item Produk</th>
                        <th style="text-align:center;">Titip Awal</th>
                        <th style="text-align:center;color:#10b981;">Laku Terjual</th>
                        <th style="text-align:center;color:#f43f5e;">Retur Rusak (BS)</th>
                        <th style="text-align:center;color:#f59e0b;">Retur Bagus</th>
                        <th style="text-align:center;color:#0284c7;">Sisa Rak Baru</th>
                        <th style="text-align:right;">Harga Satuan</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in filteredItems" :key="item.id">
                        <tr>
                            <td>
                                <strong class="text-sm block" style="color:var(--color-ink);" x-text="item.nama_item"></strong>
                                <span class="font-mono text-[10px]" style="color:var(--color-ink-mute);" x-text="item.kode_sku || '-'"></span>
                            </td>
                            <td style="text-align:center;color:var(--color-ink-mute);" class="font-mono">
                                <span x-text="`${item.stok_titip_awal} ${item.satuan_dasar || 'pcs'}`"></span>
                            </td>
                            <td style="text-align:center;font-weight:900;" class="font-mono text-emerald-600 dark:text-emerald-400">
                                <span x-text="`${item.jumlah_laku_terjual} ${item.satuan_dasar || 'pcs'}`"></span>
                            </td>
                            <td style="text-align:center;font-weight:700;" class="font-mono" :class="item.retur_rusak > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-mute'">
                                <span x-text="item.retur_rusak"></span>
                            </td>
                            <td style="text-align:center;font-weight:700;" class="font-mono" :class="item.retur_bagus > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-mute'">
                                <span x-text="item.retur_bagus"></span>
                            </td>
                            <td style="text-align:center;font-weight:900;color:#0284c7;" class="font-mono">
                                <span x-text="`${item.sisa_fisik_di_rak} ${item.satuan_dasar || 'pcs'}`"></span>
                            </td>
                            <td style="text-align:right;color:var(--color-ink-mute);" class="font-mono">
                                <span x-text="formatRupiah(item.harga_satuan_deal)"></span>
                            </td>
                            <td style="text-align:right;font-weight:900;" class="font-mono text-emerald-600 dark:text-emerald-400">
                                <span x-text="formatRupiah(item.subtotal_laku)"></span>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredItems.length === 0" x-cloak>
                        <td colspan="8" class="text-center py-8 text-mute text-xs">
                            Tidak ada produk yang cocok dengan kata kunci pencarian.
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td>TOTAL RINGKASAN:</td>
                        <td style="text-align:center;" class="font-mono"><?= $totalStokAwal ?> pcs</td>
                        <td style="text-align:center;color:#10b981;font-weight:900;" class="font-mono"><?= $totalQtyLaku ?> pcs</td>
                        <td style="text-align:center;color:#f43f5e;font-weight:800;" class="font-mono"><?= $totalQtyRusak ?> pcs</td>
                        <td style="text-align:center;color:#f59e0b;font-weight:800;" class="font-mono"><?= $totalQtyBagus ?> pcs</td>
                        <td style="text-align:center;color:#0284c7;font-weight:900;" class="font-mono"><?= $totalSisaRak ?> pcs</td>
                        <td style="text-align:right;font-weight:800;">GRAND TOTAL:</td>
                        <td style="text-align:right;color:#10b981;font-size:14px;font-weight:900;font-family:var(--font-mono);"><?= Format::rupiah($totalLakuRp) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Catatan Kunjungan Jika Ada -->
    <?php if (!empty($visit['catatan'])): ?>
    <div class="oh-card no-print text-xs" style="background:var(--color-canvas-soft);">
        <strong style="color:var(--color-ink);display:flex;align-items:center;gap:6px;margin-bottom:4px;">
            <i data-lucide="message-square" class="w-4 h-4 text-sky-600"></i>
            <span>Catatan Kunjungan Petugas:</span>
        </strong>
        <p style="color:var(--color-ink-secondary);margin:0;line-height:1.5;"><?= nl2br(htmlspecialchars($visit['catatan'])) ?></p>
    </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- 5. BUKTI KUNJUNGAN & AKSI LAPANGAN (WHATSAPP & THERMAL PRINT)             -->
    <!-- ========================================================================= -->
    <div class="oh-card no-print space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-hairline">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(16,185,129,0.12);color:#10b981;">
                    <i data-lucide="share-2" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm sm:text-base" style="color:var(--color-ink);">Aksi &amp; Bukti Kunjungan Lapangan</h3>
                    <p class="text-xs" style="color:var(--color-ink-mute);">Kirim rincian ke WhatsApp mitra toko atau cetak struk kasir thermal</p>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <?php if (!empty($encodedWaUrl)): ?>
                <a href="<?= $encodedWaUrl ?>" target="_blank" class="btn btn-success btn-sm flex items-center gap-1.5" style="background:#10b981;border-color:#059669;color:#fff;border-radius:12px;font-weight:700;">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <span>Kirim WhatsApp</span>
                </a>
                <?php endif; ?>

                <button type="button" class="btn btn-secondary btn-sm flex items-center gap-1.5" @click="copyWhatsAppText()" style="border-radius:12px;font-weight:700;">
                    <i data-lucide="copy" class="w-4 h-4"></i>
                    <span x-text="copyBtnText">Salin Teks WA</span>
                </button>

                <button type="button" class="btn btn-secondary btn-sm flex items-center gap-1.5" onclick="window.print()" style="border-radius:12px;font-weight:700;">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Cetak Struk Thermal</span>
                </button>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between text-xs mb-1.5" style="color:var(--color-ink-mute);">
                <span>Preview Format Pesan WhatsApp:</span>
                <span class="text-[10.5px]">Format rapi siap kirim ke pemilik toko</span>
            </div>
            <div class="oh-wa-box" id="waMessageContent"><?= htmlspecialchars($fullWaMessage) ?></div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 6. MODAL 1: TERBITKAN FAKTUR TAGIHAN (Teleported ERP Modal)               -->
    <!-- ========================================================================= -->
    <?php if (!$hasInvoice && !$isNihil && !empty($canManageTagihan)): ?>
    <template x-teleport="body">
        <div x-show="showGenerateModal" 
             x-cloak 
             class="modal-backdrop" 
             @click.self="closeGenerateModal()"
             @keydown.escape.window="closeGenerateModal()">
            
            <div class="modal-box" style="max-width:480px;padding:22px;" @click.stop>
                <div class="modal-header">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(2,132,199,0.15);color:#0284c7;">
                            <i data-lucide="receipt" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="modal-title text-base font-bold">Terbitkan Faktur Tagihan</div>
                            <div class="text-xs" style="color:var(--color-ink-mute);">Konfirmasi penerbitan faktur konsinyasi resmi</div>
                        </div>
                    </div>
                    <button type="button" @click="closeGenerateModal()" class="btn btn-ghost btn-sm" style="padding:4px;">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <div class="p-3.5 rounded-xl text-xs space-y-2 mb-4" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <div class="flex justify-between">
                        <span style="color:var(--color-ink-mute);">Toko Mitra:</span>
                        <strong style="color:var(--color-ink);"><?= htmlspecialchars($visit['nama_toko']) ?></strong>
                    </div>
                    <div class="flex justify-between">
                        <span style="color:var(--color-ink-mute);">No. Kunjungan:</span>
                        <span class="font-mono font-bold text-sky-600"><?= htmlspecialchars($visit['nomor_kunjungan']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color:var(--color-ink-mute);">Barang Laku:</span>
                        <strong style="color:var(--color-ink);"><?= $totalQtyLaku ?> pcs (<?= count($details) ?> SKU)</strong>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-dashed border-hairline">
                        <span class="font-bold" style="color:var(--color-ink);">Total Tagihan:</span>
                        <strong class="font-mono text-sm text-emerald-600 dark:text-emerald-400 font-black"><?= Format::rupiah($totalLakuRp) ?></strong>
                    </div>
                </div>

                <p class="text-xs mb-4" style="color:var(--color-ink-secondary);line-height:1.5;">
                    Faktur resmi (<code class="font-mono">INV-KONSIN-...</code>) akan diterbitkan dan tercatat sebagai piutang toko mitra. Anda dapat langsung mengunduh PDF atau mencatat pembayaran setelah terbit.
                </p>

                <form method="POST" action="<?= Router::url('/consignment/tagihan/generate') ?>" class="flex gap-2">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="kunjungan_ids[]" value="<?= htmlspecialchars((string)$visit['id']) ?>">
                    <input type="hidden" name="redirect_url" value="<?= Router::url('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)$visit['id'])) ?>">
                    
                    <button type="button" @click="closeGenerateModal()" class="btn btn-secondary flex-1 py-2 text-xs font-bold rounded-xl">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary flex-1 py-2 text-xs font-bold rounded-xl flex items-center justify-center gap-1.5" style="background:#0284c7;border-color:#0284c7;">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Ya, Terbitkan</span>
                    </button>
                </form>
            </div>
        </div>
    </template>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- 7. MODAL 2: CATAT PEMBAYARAN TAGIHAN (Teleported ERP Modal)               -->
    <!-- ========================================================================= -->
    <?php if ($hasInvoice && (float)($visit['sisa_tagihan'] ?? $totalLakuRp) > 0 && !empty($canManageTagihan)): ?>
    <template x-teleport="body">
        <div x-show="showPaymentModal" 
             x-cloak 
             class="modal-backdrop" 
             @click.self="closePaymentModal()"
             @keydown.escape.window="closePaymentModal()">
            
            <div class="modal-box" style="max-width:480px;padding:22px;" @click.stop>
                <div class="modal-header">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(16,185,129,0.15);color:#10b981;">
                            <i data-lucide="wallet" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="modal-title text-base font-bold">Catat Pembayaran Tagihan</div>
                            <div class="text-xs font-mono" style="color:var(--color-ink-mute);"><?= htmlspecialchars($visit['nomor_nota']) ?></div>
                        </div>
                    </div>
                    <button type="button" @click="closePaymentModal()" class="btn btn-ghost btn-sm" style="padding:4px;">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <form method="POST" action="<?= Router::url('/consignment/tagihan/bayar') ?>" class="space-y-3.5">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="pesanan_id" value="<?= htmlspecialchars((string)$visit['pesanan_id']) ?>">
                    <input type="hidden" name="redirect_url" value="<?= Router::url('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)$visit['id'])) ?>">

                    <div class="p-3.5 rounded-xl text-xs space-y-1.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between">
                            <span style="color:var(--color-ink-mute);">Total Faktur:</span>
                            <strong class="font-mono text-ink"><?= Format::rupiah((float)($visit['total_netto'] ?? $totalLakuRp)) ?></strong>
                        </div>
                        <div class="flex justify-between">
                            <span style="color:var(--color-ink-mute);">Sudah Dibayar:</span>
                            <strong class="font-mono text-emerald-600"><?= Format::rupiah((float)($visit['total_dibayar'] ?? 0)) ?></strong>
                        </div>
                        <div class="flex justify-between pt-1.5 border-t border-dashed border-hairline">
                            <span class="font-bold" style="color:var(--color-ink);">Sisa Tagihan:</span>
                            <strong class="font-mono text-sm text-rose-600 font-black"><?= Format::rupiah((float)($visit['sisa_tagihan'] ?? $totalLakuRp)) ?></strong>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label text-xs font-bold">Tanggal Pembayaran <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_bayar" required value="<?= date('Y-m-d') ?>" class="form-input text-xs font-mono">
                    </div>

                    <div class="form-group">
                        <label class="form-label text-xs font-bold">Nominal Pembayaran (Rp) <span class="text-danger">*</span></label>
                        <input type="number" 
                               name="nominal" 
                               required 
                               min="1" 
                               max="<?= (float)($visit['sisa_tagihan'] ?? $totalLakuRp) ?>" 
                               value="<?= (float)($visit['sisa_tagihan'] ?? $totalLakuRp) ?>" 
                               class="form-input text-xs font-mono font-bold">
                    </div>

                    <div class="form-group">
                        <label class="form-label text-xs font-bold">Pilih Akun Kas / Bank Penerima <span class="text-danger">*</span></label>
                        <select name="akun_kas_id" required class="form-select text-xs">
                            <option value="">-- Pilih Rekening Kas --</option>
                            <?php foreach ($cashAccounts as $acc): ?>
                                <option value="<?= htmlspecialchars((string)$acc['id']) ?>" <?= (!empty($acc['is_default_pos']) ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($acc['nama_akun']) ?> (<?= strtoupper($acc['tipe_akun']) ?>) - Saldo: <?= Format::rupiah((float)$acc['saldo_saat_ini']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label text-xs font-bold">Catatan Pembayaran (Opsional)</label>
                        <input type="text" name="catatan" placeholder="Contoh: Titipan tunai via sales driver" class="form-input text-xs">
                    </div>

                    <div class="flex gap-2 pt-2 border-t border-hairline">
                        <button type="button" @click="closePaymentModal()" class="btn btn-secondary flex-1 py-2 text-xs font-bold rounded-xl">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-success flex-1 py-2 text-xs font-bold rounded-xl flex items-center justify-center gap-1.5" style="background:#10b981;border-color:#059669;color:#fff;">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Simpan Pembayaran</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- 8. THERMAL RECEIPT TEMPLATE (80MM MONOSPACE, PRINT ONLY)                  -->
    <!-- ========================================================================= -->
    <div id="printableReceipt" class="hidden">
        <div style="text-align:center;margin-bottom:8px;">
            <strong style="font-size:13px;display:block;">KEREN SNACK ERP</strong>
            <span style="font-size:10px;">Bukti Kunjungan &amp; Opname Rak Toko</span>
        </div>

        <div style="border-bottom:1px dashed #000;margin:6px 0;"></div>

        <div style="font-size:10px;line-height:1.4;">
            <div><strong>Toko:</strong> <?= htmlspecialchars($visit['nama_toko']) ?></div>
            <div><strong>No. Kunjungan:</strong> <?= htmlspecialchars($visit['nomor_kunjungan']) ?></div>
            <div><strong>Tanggal:</strong> <?= date('d/m/Y H:i', strtotime($visit['tanggal_kunjungan'])) ?></div>
            <div><strong>Sales:</strong> <?= htmlspecialchars($visit['sales_name']) ?></div>
            <?php if ($hasInvoice): ?>
                <div><strong>No. Nota:</strong> <?= htmlspecialchars($visit['nomor_nota']) ?></div>
            <?php endif; ?>
        </div>

        <div style="border-bottom:1px dashed #000;margin:6px 0;"></div>

        <table style="width:100%;font-size:10px;border-collapse:collapse;">
            <thead>
                <tr style="text-align:left;border-bottom:1px solid #000;">
                    <th>Item</th>
                    <th style="text-align:center;">Laku</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($details as $d): ?>
                <tr>
                    <td style="padding:2px 0;">
                        <?= htmlspecialchars($d['nama_item']) ?><br>
                        <span style="font-size:9px;">(Sisa: <?= $d['sisa_fisik_di_rak'] ?><?= ($d['retur_rusak'] > 0 ? ', BS:' . $d['retur_rusak'] : '') ?><?= ($d['retur_bagus'] > 0 ? ', Retur:' . $d['retur_bagus'] : '') ?>)</span>
                    </td>
                    <td style="text-align:center;vertical-align:top;padding:2px 0;"><?= $d['jumlah_laku_terjual'] ?></td>
                    <td style="text-align:right;vertical-align:top;padding:2px 0;"><?= Format::rupiah((float)$d['subtotal_laku']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="border-bottom:1px dashed #000;margin:6px 0;"></div>

        <div style="font-size:11px;font-weight:bold;display:flex;justify-content:space-between;">
            <span>TOTAL PENJUALAN:</span>
            <span><?= Format::rupiah($totalLakuRp) ?></span>
        </div>

        <?php if ($hasInvoice): ?>
        <div style="font-size:10px;display:flex;justify-content:space-between;margin-top:2px;">
            <span>Status Pembayaran:</span>
            <span><?= strtoupper(str_replace('_', ' ', $stBayar)) ?></span>
        </div>
        <?php else: ?>
        <div style="font-size:10px;display:flex;justify-content:space-between;margin-top:2px;">
            <span>Status Penagihan:</span>
            <span>MENUNGGU TAGIHAN</span>
        </div>
        <?php endif; ?>

        <div style="text-align:center;margin-top:12px;font-size:9px;">
            Terima kasih atas kerja samanya!<br>
            Simpan struk ini sebagai bukti kunjungan fisik.
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('opnameHasilApp', () => ({
        showGenerateModal: false,
        showPaymentModal: false,
        copyBtnText: 'Salin Teks WA',
        searchQuery: '',
        items: <?= json_encode(array_map(fn($d) => [
            'id' => $d['id'],
            'nama_item' => $d['nama_item'],
            'kode_sku' => $d['kode_sku'],
            'satuan_dasar' => $d['satuan_dasar'] ?? 'pcs',
            'stok_titip_awal' => (int)$d['stok_titip_awal'],
            'jumlah_laku_terjual' => (int)$d['jumlah_laku_terjual'],
            'retur_rusak' => (int)$d['retur_rusak'],
            'retur_bagus' => (int)$d['retur_bagus'],
            'sisa_fisik_di_rak' => (int)$d['sisa_fisik_di_rak'],
            'harga_satuan_deal' => (float)$d['harga_satuan_deal'],
            'subtotal_laku' => (float)$d['subtotal_laku'],
        ], $details)) ?>,

        get filteredItems() {
            if (!this.searchQuery.trim()) {
                return this.items;
            }
            const q = this.searchQuery.toLowerCase();
            return this.items.filter(item => {
                const name = (item.nama_item || '').toLowerCase();
                const sku = (item.kode_sku || '').toLowerCase();
                return name.includes(q) || sku.includes(q);
            });
        },

        init() {
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
                    lucide.createIcons();
                }
            });
        },

        openGenerateModal() {
            this.showGenerateModal = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        closeGenerateModal() {
            this.showGenerateModal = false;
            document.body.style.overflow = '';
        },

        openPaymentModal() {
            this.showPaymentModal = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        closePaymentModal() {
            this.showPaymentModal = false;
            document.body.style.overflow = '';
        },

        formatRupiah(num) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0
            }).format(num || 0);
        },

        copyWhatsAppText() {
            const el = document.getElementById('waMessageContent');
            const text = el ? el.innerText : '';
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    this.copyBtnText = 'Tersalin! ✓';
                    if (window.toast) window.toast.success('Format pesan WhatsApp berhasil disalin!');
                    setTimeout(() => { this.copyBtnText = 'Salin Teks WA'; }, 2500);
                }).catch(() => {
                    this.fallbackCopy(text);
                });
            } else {
                this.fallbackCopy(text);
            }
        },

        fallbackCopy(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            this.copyBtnText = 'Tersalin! ✓';
            if (window.toast) window.toast.success('Format pesan WhatsApp berhasil disalin!');
            setTimeout(() => { this.copyBtnText = 'Salin Teks WA'; }, 2500);
        }
    }));
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

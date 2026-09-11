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

// =========================================================================
// NAVIGASI KEMBALI CERDAS (UX OPTIMIZED)
// =========================================================================
$ref = strtolower(trim((string)($_GET['ref'] ?? '')));

// 1. Pemetaan fallback berdasarkan $ref eksplisit
$refMappings = [
    'kerugian'          => ['url' => Router::url('/consignment/kerugian-rusak'), 'title' => 'Laporan Kerugian & Rusak'],
    'kerugian-rusak'    => ['url' => Router::url('/consignment/kerugian-rusak'), 'title' => 'Laporan Kerugian & Rusak'],
    'riwayat'           => ['url' => Router::url('/consignment/riwayat-kunjungan'), 'title' => 'Riwayat Kunjungan'],
    'tagihan'           => ['url' => Router::url('/consignment/tagihan'), 'title' => 'Menu Tagihan'],
    'laporan'           => ['url' => Router::url('/consignment/laporan-penjualan'), 'title' => 'Laporan Penjualan'],
    'laporan-penjualan' => ['url' => Router::url('/consignment/laporan-penjualan'), 'title' => 'Laporan Penjualan'],
    'early-warning'     => ['url' => Router::url('/consignment/early-warning'), 'title' => 'Early Warning'],
    'warning'           => ['url' => Router::url('/consignment/early-warning'), 'title' => 'Early Warning'],
    'stok-rak'          => ['url' => Router::url('/consignment/stok-rak'), 'title' => 'Daftar Stok Rak'],
    'stok'              => ['url' => Router::url('/consignment/stok-rak'), 'title' => 'Daftar Stok Rak'],
    'dashboard'         => ['url' => Router::url('/consignment'), 'title' => 'Portal Konsinyasi'],
    'portal'            => ['url' => Router::url('/consignment'), 'title' => 'Portal Konsinyasi'],
    'opname'            => ['url' => Router::url('/consignment/opname?pelanggan_id=' . urlencode((string)$visit['pelanggan_id'])), 'title' => 'Form Opname'],
];

$backUrl = Router::url('/consignment/stok-rak');
$backTitle = 'Daftar Stok Rak';

if (isset($refMappings[$ref])) {
    $backUrl = $refMappings[$ref]['url'];
    $backTitle = $refMappings[$ref]['title'];
}

// 2. Analisis HTTP_REFERER jika tersedia dari domain yang sama
$httpReferer = (string)($_SERVER['HTTP_REFERER'] ?? '');
if (!empty($httpReferer)) {
    $parsedReferer = parse_url($httpReferer);
    $refererHost = $parsedReferer['host'] ?? '';
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    $refererHostClean = preg_replace('/:\d+$/', '', $refererHost);
    $currentHostClean = preg_replace('/:\d+$/', '', $currentHost);

    if ($refererHostClean === $currentHostClean && !empty($parsedReferer['path'])) {
        $refererPath = $parsedReferer['path'];
        $refererQuery = !empty($parsedReferer['query']) ? '?' . $parsedReferer['query'] : '';
        $fullRefererUrl = $refererPath . $refererQuery;

        // Abaikan jika referer adalah diri sendiri, form opname baru, atau aksi POST redirect
        $isSelf = str_contains($refererPath, '/consignment/opname/hasil');
        $isOpnameForm = str_contains($refererPath, '/consignment/opname') && !str_contains($refererPath, '/hasil');
        $isPostAction = str_contains($refererPath, '/submit') || str_contains($refererPath, '/generate') || str_contains($refererPath, '/bayar');

        if (!$isSelf && !$isOpnameForm && !$isPostAction) {
            // Gunakan referer lengkap agar filter pencarian, tab, dan pagination sebelumnya tetap terjaga
            $backUrl = $fullRefererUrl;

            // Sesuaikan judul jika belum spesifik dari $ref
            if (empty($ref)) {
                if (str_contains($refererPath, 'kerugian-rusak')) {
                    $backTitle = 'Laporan Kerugian & Rusak';
                } elseif (str_contains($refererPath, 'riwayat-kunjungan')) {
                    $backTitle = 'Riwayat Kunjungan';
                } elseif (str_contains($refererPath, 'tagihan')) {
                    $backTitle = 'Menu Tagihan';
                } elseif (str_contains($refererPath, 'laporan-penjualan')) {
                    $backTitle = 'Laporan Penjualan';
                } elseif (str_contains($refererPath, 'early-warning')) {
                    $backTitle = 'Early Warning';
                } elseif (str_contains($refererPath, 'stok-rak')) {
                    $backTitle = 'Daftar Stok Rak';
                } elseif (str_contains($refererPath, 'consignment')) {
                    $backTitle = 'Portal Konsinyasi';
                }
            }
        }
    }
}
$currentHasilUrl = Router::url('/consignment/opname/hasil?kunjungan_id=' . urlencode((string)$visit['id']) . (!empty($ref) ? '&ref=' . urlencode($ref) : ''));

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
    "*KEREN SNACK — BUKTI HASIL OPNAME KONSINYASI*",
    "Toko: " . $storeTitle,
    "Tanggal: " . date('d/m/Y', strtotime($visit['tanggal_kunjungan'] ?? 'now')),
    "No. Kunjungan: " . ($visit['nomor_kunjungan'] ?? '-'),
    "Petugas Sales: " . ($visit['sales_name'] ?? 'Petugas'),
];

if ($hasInvoice) {
    $waMessageLines[] = "No. Faktur Terbit: " . ($visit['nomor_nota'] ?? '-');
}

$waMessageLines[] = "----------------------------------------";
$waMessageLines[] = "*RINCIAN STOK RAK:*";

$totalSelisihVisit = (int)array_sum(array_column($details, 'selisih_qty'));

foreach ($details as $d) {
    $laku = (int)$d['jumlah_laku_terjual'];
    $rusak = (int)$d['retur_rusak'];
    $bagus = (int)$d['retur_bagus'];
    $sisa = (int)$d['sisa_fisik_di_rak'];
    $selisih = (int)($d['selisih_qty'] ?? 0);
    $nama = $d['nama_item'] ?? 'Produk';
    
    $itemLine = "• {$nama}: Laku {$laku} pcs (Sisa rak: {$sisa} pcs)";
    if ($rusak > 0) $itemLine .= " [BS: {$rusak} pcs]";
    if ($bagus > 0) $itemLine .= " [Retur: {$bagus} pcs]";
    if ($selisih < 0) {
        $itemLine .= " [Hilang: " . abs($selisih) . " pcs (Gantung)]";
    } elseif ($selisih > 0) {
        $itemLine .= " [Ketemu: +{$selisih} pcs]";
    }
    $waMessageLines[] = $itemLine;
}

if ($totalSelisihVisit < 0) {
    $waMessageLines[] = "Catatan Selisih: Hilang " . abs($totalSelisihVisit) . " pcs (Ditangguhkan/Gantung)";
} elseif ($totalSelisihVisit > 0) {
    $waMessageLines[] = "Catatan Selisih: Ditemukan kembali +{$totalSelisihVisit} pcs";
}

$waMessageLines[] = "----------------------------------------";
$waMessageLines[] = "*TOTAL TERJUAL (LAKU):* " . $totalQtyLaku . " pcs (" . Format::rupiah($totalLakuRp) . ")";
$waMessageLines[] = "*TOTAL SISA RAK TOKO:* " . $totalSisaRak . " pcs";
$waMessageLines[] = "";
$waMessageLines[] = "Terima kasih atas kerja samanya!";
$waMessageLines[] = "_Bukti hasil opname fisik resmi ERP Keren Snack_";

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
    gap: 18px;
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
/* ========================================================================= */
/* 1. AUDIT SESSION OVERVIEW CARD                                            */
/* ========================================================================= */
.oh-session-card {
    background-color: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 18px;
    padding: 18px 22px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    overflow: hidden;
}

@media (max-width: 640px) {
    .oh-session-card {
        padding: 16px;
        border-radius: 14px;
    }
}

.oh-session-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 16px;
}
@media (min-width: 640px) {
    .oh-session-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
}
@media (min-width: 1024px) {
    .oh-session-grid {
        grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.1fr) minmax(0, 1.15fr) minmax(0, 1.35fr);
        gap: 0;
    }
    .oh-session-col {
        padding: 0 10px;
        border-right: 1px solid var(--color-hairline);
        min-width: 0;
    }
    .oh-session-col:first-child {
        padding-left: 0;
    }
    .oh-session-col:last-child {
        padding-right: 0;
        border-right: none;
    }
}

.oh-icon-avatar {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.oh-icon-avatar i, .oh-icon-avatar svg {
    width: 15px !important;
    height: 15px !important;
}

.oh-avatar-sky {
    background: rgba(2, 132, 199, 0.1);
    color: #0284c7;
}
.dark .oh-avatar-sky {
    background: rgba(56, 189, 248, 0.15);
    color: #38bdf8;
}

.oh-avatar-indigo {
    background: rgba(99, 102, 241, 0.1);
    color: #6366f1;
}
.dark .oh-avatar-indigo {
    background: rgba(129, 140, 248, 0.15);
    color: #818cf8;
}

.oh-avatar-emerald {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}
.dark .oh-avatar-emerald {
    background: rgba(52, 211, 153, 0.15);
    color: #34d399;
}

.oh-avatar-slate {
    background: rgba(100, 116, 139, 0.1);
    color: #64748b;
}
.dark .oh-avatar-slate {
    background: rgba(148, 163, 184, 0.15);
    color: #94a3b8;
}

.oh-col-label {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--color-ink-mute);
    display: block;
    line-height: 1.2;
}

.oh-col-title {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--color-ink);
    display: block;
    margin-top: 3px;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.oh-col-sub {
    font-size: 11px;
    color: var(--color-ink-secondary);
    display: block;
    margin-top: 2px;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Auditor / Inputter Micro Meta */
.oh-auditor-meta {
    display: inline-flex;
    align-items: center;
    gap: 4.5px;
    margin-top: 6px;
    padding: 2.5px 7px;
    border-radius: 6px;
    background: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    font-size: 10.5px;
    font-family: var(--font-sans);
    color: var(--color-ink-mute);
    line-height: 1.25;
    max-width: 100%;
}
.oh-auditor-meta strong {
    color: var(--color-ink-secondary);
    font-weight: 700;
}
.oh-auditor-direct {
    background: rgba(16, 185, 129, 0.08);
    border-color: rgba(16, 185, 129, 0.2);
    color: #059669;
}
.dark .oh-auditor-direct {
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.3);
    color: #34d399;
}

/* Status Stack (Clean Vertical Gap) */
.oh-col-status-stack {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 6px;
    align-items: flex-start;
}

/* Status Badges */
.oh-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4.5px;
    padding: 3px 8.5px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 700;
    line-height: 1.25;
    white-space: nowrap;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.oh-status-verified {
    background: rgba(16, 185, 129, 0.09);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.25);
}
.dark .oh-status-verified {
    color: #34d399;
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.3);
}

.oh-status-nihil {
    background: var(--color-canvas-soft);
    color: var(--color-ink-mute);
    border: 1px solid var(--color-hairline);
}
.dark .oh-status-nihil {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.08);
    color: #94a3b8;
}

/* Invoice / Unbilled Link & Badges */
.oh-invoice-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2.5px 7.5px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: -0.01em;
    color: #4f46e5;
    background: rgba(99, 102, 241, 0.08);
    border: 1px solid rgba(99, 102, 241, 0.25);
    text-decoration: none;
    transition: all 0.15s ease;
    white-space: nowrap;
    max-width: 100%;
    overflow: hidden;
    box-sizing: border-box;
}
.oh-invoice-link:hover {
    background: rgba(99, 102, 241, 0.16);
    border-color: rgba(99, 102, 241, 0.45);
    color: #4338ca;
}
.dark .oh-invoice-link {
    color: #a5b4fc;
    background: rgba(99, 102, 241, 0.16);
    border-color: rgba(99, 102, 241, 0.35);
}
.oh-invoice-link .truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: inline-block;
    max-width: 100%;
}

.oh-unbilled-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2.5px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 700;
    color: var(--color-ink-mute);
    background: var(--color-canvas-soft);
    border: 1px solid var(--color-hairline);
    white-space: nowrap;
}

/* Minimalist Payment Badges */
.oh-pay-group {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
    max-width: 100%;
}

.oh-pay-badge {
    display: inline-flex;
    align-items: center;
    gap: 3.5px;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 700;
    line-height: 1.25;
    white-space: nowrap;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.oh-pay-lunas {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.3);
}
.dark .oh-pay-lunas {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.4);
}

.oh-pay-unpaid {
    background: rgba(244, 63, 94, 0.1);
    color: #e11d48;
    border: 1px solid rgba(244, 63, 94, 0.28);
}
.dark .oh-pay-unpaid {
    background: rgba(244, 63, 94, 0.18);
    color: #fb7185;
    border-color: rgba(244, 63, 94, 0.35);
}

.oh-pay-cicil {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.28);
}
.dark .oh-pay-cicil {
    background: rgba(245, 158, 11, 0.18);
    color: #fbbf24;
    border-color: rgba(245, 158, 11, 0.35);
}

.oh-pay-nihil {
    background: var(--color-canvas-soft);
    color: var(--color-ink-mute);
    border: 1px solid var(--color-hairline);
}

.oh-pay-badge i, .oh-pay-badge svg {
    width: 10px !important;
    height: 10px !important;
    flex-shrink: 0;
}

/* KPI Card Payment Status Badge */
.oh-kpi-pay-badge {
    display: inline-flex;
    align-items: center;
    gap: 4.5px;
    padding: 3px 8.5px;
    border-radius: 7px;
    font-size: 10.5px;
    font-weight: 700;
    line-height: 1.25;
    margin-top: 6px;
    white-space: nowrap;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}
.oh-kpi-pay-badge i, .oh-kpi-pay-badge svg {
    width: 11px !important;
    height: 11px !important;
    flex-shrink: 0;
}

.oh-kpi-pay-lunas {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.28);
}
.dark .oh-kpi-pay-lunas {
    background: rgba(16, 185, 129, 0.18);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.35);
}

.oh-kpi-pay-unpaid {
    background: rgba(244, 63, 94, 0.09);
    color: #e11d48;
    border: 1px solid rgba(244, 63, 94, 0.25);
}
.dark .oh-kpi-pay-unpaid {
    background: rgba(244, 63, 94, 0.18);
    color: #fb7185;
    border-color: rgba(244, 63, 94, 0.35);
}

.oh-kpi-pay-cicil {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.28);
}
.dark .oh-kpi-pay-cicil {
    background: rgba(245, 158, 11, 0.18);
    color: #fbbf24;
    border-color: rgba(245, 158, 11, 0.35);
}

.oh-kpi-pay-unbilled {
    background: rgba(100, 116, 139, 0.09);
    color: #64748b;
    border: 1px solid rgba(100, 116, 139, 0.22);
}
.dark .oh-kpi-pay-unbilled {
    background: rgba(148, 163, 184, 0.15);
    color: #94a3b8;
    border-color: rgba(148, 163, 184, 0.3);
}

.oh-kpi-pay-nihil {
    background: var(--color-canvas-soft);
    color: var(--color-ink-mute);
    border: 1px solid var(--color-hairline);
}

/* Session Note Strip */
.oh-session-note {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--color-hairline);
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12px;
    flex-wrap: wrap;
}
.oh-note-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8.5px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 700;
    background: rgba(2, 132, 199, 0.08);
    color: #0284c7;
    border: 1px solid rgba(2, 132, 199, 0.2);
    flex-shrink: 0;
}
.dark .oh-note-tag {
    background: rgba(56, 189, 248, 0.12);
    color: #38bdf8;
    border-color: rgba(56, 189, 248, 0.25);
}
.oh-note-text {
    color: var(--color-ink-secondary);
    line-height: 1.4;
}

/* ========================================================================= */
/* 2. KPI GRID & CARDS                                                       */
/* ========================================================================= */
.oh-kpi-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 12px;
}
@media (min-width: 640px) {
    .oh-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
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
    min-height: 120px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.oh-kpi-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.oh-kpi-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
}
.oh-kpi-label {
    font-size: 12px;
    font-weight: 700;
    line-height: 1.3;
}
.oh-kpi-tag {
    display: block;
    font-size: 9.5px;
    font-weight: 800;
    font-family: var(--font-mono);
    letter-spacing: 0.04em;
    margin-top: 2px;
    opacity: 0.8;
}
.oh-kpi-icon {
    width: 36px;
    height: 36px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.oh-kpi-icon i, .oh-kpi-icon svg {
    width: 18px !important;
    height: 18px !important;
}
.oh-kpi-value {
    font-size: 22px;
    font-weight: 900;
    font-family: var(--font-mono);
    line-height: 1.2;
    margin-top: 10px;
}
.oh-kpi-unit {
    font-size: 12px;
    font-weight: 600;
    font-family: var(--font-sans);
    color: var(--color-ink-mute);
    margin-left: 3px;
}
.oh-kpi-subtitle {
    font-size: 11px;
    color: var(--color-ink-secondary);
    margin-top: 4px;
}

/* ========================================================================= */
/* 3. CANONICAL SEARCH INPUT                                                 */
/* ========================================================================= */
.oh-search-wrap {
    position: relative;
    width: 100%;
}
.oh-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 15px !important;
    height: 15px !important;
    color: var(--color-ink-mute);
    pointer-events: none;
}
.oh-search-input {
    width: 100%;
    height: 36px;
    font-size: 12px;
    font-family: var(--font-sans);
    border-radius: 10px;
    border: 1px solid var(--color-hairline);
    background-color: var(--color-canvas-soft);
    color: var(--color-ink);
    padding: 0 30px 0 36px;
    transition: all 0.15s ease;
    outline: none;
    box-sizing: border-box;
}
.oh-search-input:focus {
    border-color: #6366f1;
    background-color: var(--color-canvas);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.14);
}
.oh-search-input::placeholder {
    color: var(--color-ink-mute);
}
.oh-search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-ink-mute);
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
}
.oh-search-clear:hover {
    color: var(--color-ink);
    background: rgba(0, 0, 0, 0.06);
}

/* ========================================================================= */
/* 4. PRODUCT BREAKDOWN TABLE & MOBILE CARDS                                 */
/* ========================================================================= */
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
    padding: 11px 14px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--color-ink-mute);
    border-bottom: 1px solid var(--color-hairline);
    white-space: nowrap;
}
.oh-data-table td {
    padding: 11px 14px;
    border-bottom: 1px solid var(--color-hairline);
    vertical-align: middle;
    color: var(--color-ink);
}
.oh-data-table tbody tr:hover td {
    background-color: rgba(99, 102, 241, 0.025);
}
.oh-data-table tfoot td {
    background: var(--color-canvas-soft);
    padding: 12px 14px;
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
    grid-template-columns: repeat(5, 1fr);
    gap: 4px;
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 10px;
    padding: 8px 4px;
    text-align: center;
    margin: 10px 0;
}
.oh-flow-cell span {
    display: block;
    font-size: 8.5px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--color-ink-mute);
    letter-spacing: -0.01em;
    white-space: nowrap;
}
.oh-flow-cell strong {
    display: block;
    font-size: 13px;
    font-family: var(--font-mono);
    color: var(--color-ink);
    margin-top: 1px;
}

/* 5. WhatsApp Preview Box */
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

/* 6. Thermal Receipt Print Styling */
@media print {
    body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    body * {
        visibility: hidden !important;
    }
    #printableReceipt, #printableReceipt * {
        visibility: visible !important;
    }
    #printableReceipt {
        display: block !important;
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
            <a href="<?= htmlspecialchars($backUrl) ?>" 
               class="btn btn-secondary btn-sm p-2 rounded-xl flex items-center justify-center transition-all hover:bg-slate-100 dark:hover:bg-slate-800" 
               title="Kembali ke <?= htmlspecialchars($backTitle) ?>"
               aria-label="Kembali ke <?= htmlspecialchars($backTitle) ?>"
               onclick="handleOpnameBackNavigation(event, '<?= htmlspecialchars($backUrl, ENT_QUOTES) ?>')">
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
            <a href="<?= Router::url('/consignment/opname/hasil/pdf?kunjungan_id=' . urlencode((string)$visit['id'])) ?>" 
               target="_blank" 
               class="btn btn-primary btn-sm flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl shadow-sm" 
               style="background:#6366f1;border-color:#6366f1;">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak Berita Acara PDF</span>
            </a>
            <a href="<?= Router::url('/consignment/stok-rak') ?>" class="btn btn-secondary btn-sm flex items-center gap-1.5 px-3 py-2 text-xs font-bold rounded-xl">
                <i data-lucide="boxes" class="w-4 h-4"></i>
                <span>Opname Toko Lain</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. AUDIT SESSION OVERVIEW CARD (Context, Store, Verifier & Notes)         -->
    <!-- ========================================================================= -->
    <div class="oh-session-card no-print">
        <div class="oh-session-grid">
            <!-- Col 1: Toko Mitra -->
            <div class="oh-session-col">
                <div class="flex items-start gap-2">
                    <div class="oh-icon-avatar oh-avatar-sky">
                        <i data-lucide="store"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="oh-col-label">Toko Mitra</span>
                        <strong class="oh-col-title" title="<?= htmlspecialchars($visit['nama_toko']) ?>">
                            <?= htmlspecialchars($visit['nama_toko']) ?>
                        </strong>
                        <span class="oh-col-sub" title="<?= htmlspecialchars($visit['alamat_lengkap'] ?? '') ?>">
                            <?= htmlspecialchars($visit['alamat_lengkap'] ?? 'Alamat toko tidak tersedia') ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Col 2: Nomor Kunjungan & Tanggal -->
            <div class="oh-session-col">
                <div class="flex items-start gap-2">
                    <div class="oh-icon-avatar oh-avatar-indigo">
                        <i data-lucide="calendar"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="oh-col-label">Sesi Kunjungan</span>
                        <span class="oh-col-title font-mono" style="color:#6366f1;" title="<?= htmlspecialchars($visit['nomor_kunjungan']) ?>">
                            <?= htmlspecialchars($visit['nomor_kunjungan']) ?>
                        </span>
                        <span class="oh-col-sub" title="<?= date('d M Y', strtotime($visit['tanggal_kunjungan'])) ?> &bull; <?= date('H:i', strtotime($visit['tanggal_kunjungan'])) ?> WIB">
                            <?= date('d M Y', strtotime($visit['tanggal_kunjungan'])) ?> &bull; <?= date('H:i', strtotime($visit['tanggal_kunjungan'])) ?> WIB
                        </span>
                    </div>
                </div>
            </div>

            <!-- Col 3: Petugas Kunjungan (Sales Lapangan & Auditor) -->
            <div class="oh-session-col">
                <div class="flex items-start gap-2">
                    <div class="oh-icon-avatar oh-avatar-emerald">
                        <i data-lucide="user-check"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="oh-col-label">Petugas Kunjungan</span>
                        <strong class="oh-col-title" title="Sales Lapangan: <?= htmlspecialchars($visit['sales_name']) ?>">
                            <?= htmlspecialchars($visit['sales_name']) ?>
                        </strong>
                        <span class="oh-col-sub" title="<?= htmlspecialchars(!empty($visit['sales_role']) ? ucfirst($visit['sales_role']) : 'Sales Lapangan') ?>">
                            <?= htmlspecialchars(!empty($visit['sales_role']) ? ucfirst($visit['sales_role']) : 'Sales Lapangan') ?>
                        </span>

                        <?php 
                        $hasAuditor = !empty($visit['auditor_name']);
                        $isSamePerson = $hasAuditor && (strcasecmp(trim($visit['sales_name']), trim($visit['auditor_name'])) === 0);
                        ?>
                        <?php if ($hasAuditor && !$isSamePerson): ?>
                            <div class="oh-auditor-meta" title="Data diopname oleh: <?= htmlspecialchars($visit['auditor_name']) ?><?= !empty($visit['auditor_role']) ? ' (' . ucfirst($visit['auditor_role']) . ')' : '' ?>">
                                <i data-lucide="clipboard-check" style="width:11px;height:11px;flex-shrink:0;"></i>
                                <span class="truncate">Diopname Oleh: <strong><?= htmlspecialchars($visit['auditor_name']) ?></strong></span>
                            </div>
                        <?php elseif ($isSamePerson): ?>
                            <div class="oh-auditor-meta oh-auditor-direct" title="Diopname mandiri oleh sales yang bersangkutan melalui sistem mobile">
                                <i data-lucide="smartphone" style="width:10px;height:10px;flex-shrink:0;"></i>
                                <span>Opname Mandiri</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Col 4: Status Audit & Info Tagihan -->
            <div class="oh-session-col">
                <div class="flex items-start gap-2">
                    <div class="oh-icon-avatar oh-avatar-slate">
                        <i data-lucide="clipboard-check"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="oh-col-label">Status Fisik &amp; Bayar</span>
                        <div class="oh-col-status-stack">
                            <?php if ($isNihil): ?>
                                <span class="oh-status-badge oh-status-nihil">
                                    <i data-lucide="minus-circle" style="width:12px;height:12px;"></i>
                                    <span>Stok Utuh (Nihil)</span>
                                </span>
                                <span class="oh-pay-badge oh-pay-nihil">
                                    <i data-lucide="info" style="width:10px;height:10px;"></i>
                                    <span>Tidak Ada Tagihan</span>
                                </span>
                            <?php elseif (!$hasInvoice): ?>
                                <span class="oh-status-badge oh-status-verified">
                                    <i data-lucide="check-circle-2" style="width:12px;height:12px;"></i>
                                    <span>Fisik Terverifikasi</span>
                                </span>
                                <span class="oh-unbilled-badge" title="Audit rak selesai, belum dibuatkan faktur di menu tagihan">
                                    <i data-lucide="clock" style="width:11px;height:11px;flex-shrink:0;"></i>
                                    <span>Belum Ditagih</span>
                                </span>
                            <?php else: ?>
                                <span class="oh-status-badge oh-status-verified">
                                    <i data-lucide="check-circle-2" style="width:12px;height:12px;"></i>
                                    <span>Fisik Terverifikasi</span>
                                </span>
                                <div class="oh-pay-group">
                                    <?php if ($stBayar === 'lunas'): ?>
                                        <span class="oh-pay-badge oh-pay-lunas" title="Faktur sudah lunas terbayar">
                                            <i data-lucide="check" style="width:10px;height:10px;"></i>
                                            <span>Lunas</span>
                                        </span>
                                    <?php elseif ($stBayar === 'sebagian'): ?>
                                        <span class="oh-pay-badge oh-pay-cicil" title="Bayar sebagian. Sisa piutang: <?= Format::rupiah((float)($visit['sisa_tagihan'] ?? 0)) ?>">
                                            <i data-lucide="pie-chart" style="width:10px;height:10px;"></i>
                                            <span>Cicil</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="oh-pay-badge oh-pay-unpaid" title="Faktur terbit, belum dibayar. Total: <?= Format::rupiah((float)($visit['sisa_tagihan'] ?? $totalLakuRp)) ?>">
                                            <i data-lucide="alert-circle" style="width:10px;height:10px;"></i>
                                            <span>Belum Lunas</span>
                                        </span>
                                    <?php endif; ?>

                                    <a href="<?= Router::url('/consignment/tagihan?kunjungan_id=' . urlencode((string)$visit['id'])) ?>" 
                                       class="oh-invoice-link" 
                                       title="Faktur: <?= htmlspecialchars($visit['nomor_nota']) ?> (Buka di Menu Tagihan)">
                                        <i data-lucide="receipt" style="width:10px;height:10px;flex-shrink:0;"></i>
                                        <span class="truncate font-mono"><?= htmlspecialchars($visit['nomor_nota']) ?></span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($visit['catatan'])): ?>
        <div class="oh-session-note">
            <span class="oh-note-tag">
                <i data-lucide="message-square" style="width:12px;height:12px;"></i>
                <span>Catatan Petugas:</span>
            </span>
            <span class="oh-note-text">
                <?= htmlspecialchars($visit['catatan']) ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. KPI METRIC SUMMARY GRID (4 CARDS: FISIK & STOK)                        -->
    <!-- ========================================================================= -->
    <div class="oh-kpi-grid no-print">
        <!-- Card 1: Sisa Fisik di Rak (Saldo Akhir) -->
        <div class="oh-kpi-card" style="border-left: 3.5px solid #0284c7;">
            <div class="oh-kpi-header">
                <div>
                    <span class="oh-kpi-label block" style="color:#0284c7;">Sisa Fisik di Rak</span>
                    <span class="oh-kpi-tag text-sky-600 dark:text-sky-400">SALDO AKHIR</span>
                </div>
                <div class="oh-kpi-icon bg-sky-500/10 text-sky-600 dark:text-sky-400">
                    <i data-lucide="layers"></i>
                </div>
            </div>
            <div>
                <div class="oh-kpi-value text-sky-600 dark:text-sky-400">
                    <?= number_format($totalSisaRak, 0, ',', '.') ?><span class="oh-kpi-unit">pcs</span>
                </div>
                <div class="oh-kpi-subtitle">Saldo fisik aktual terpajang di toko</div>
            </div>
        </div>

        <!-- Card 2: Fisik Terjual (Laku) -->
        <div class="oh-kpi-card" style="border-left: 3.5px solid #10b981;">
            <div class="oh-kpi-header">
                <div>
                    <span class="oh-kpi-label block" style="color:#059669;">Fisik Terjual (Laku)</span>
                    <span class="oh-kpi-tag text-emerald-600 dark:text-emerald-400">PENJUALAN RAK</span>
                </div>
                <div class="oh-kpi-icon bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <i data-lucide="package-check"></i>
                </div>
            </div>
            <div>
                <div class="oh-kpi-value text-emerald-600 dark:text-emerald-400">
                    <?= number_format($totalQtyLaku, 0, ',', '.') ?><span class="oh-kpi-unit">pcs</span>
                </div>
                <div class="oh-kpi-subtitle">
                    Estimasi Nilai: <strong class="text-slate-800 dark:text-slate-200"><?= Format::rupiah($totalLakuRp) ?></strong>
                </div>
                <div>
                    <?php if ($isNihil): ?>
                        <span class="oh-kpi-pay-badge oh-kpi-pay-nihil">
                            <i data-lucide="minus-circle"></i>
                            <span>Nihil (Rp 0)</span>
                        </span>
                    <?php elseif (!$hasInvoice): ?>
                        <span class="oh-kpi-pay-badge oh-kpi-pay-unbilled" title="Audit rak selesai, belum diterbitkan faktur">
                            <i data-lucide="clock"></i>
                            <span>Belum Ditagih</span>
                        </span>
                    <?php elseif ($stBayar === 'lunas'): ?>
                        <span class="oh-kpi-pay-badge oh-kpi-pay-lunas" title="Faktur sudah lunas terbayar">
                            <i data-lucide="check-circle-2"></i>
                            <span>Sudah Lunas</span>
                        </span>
                    <?php elseif ($stBayar === 'sebagian'): ?>
                        <span class="oh-kpi-pay-badge oh-kpi-pay-cicil" title="Sisa piutang: <?= Format::rupiah((float)($visit['sisa_tagihan'] ?? 0)) ?>">
                            <i data-lucide="pie-chart"></i>
                            <span>Cicil (Sisa: <?= Format::rupiah((float)($visit['sisa_tagihan'] ?? 0)) ?>)</span>
                        </span>
                    <?php else: ?>
                        <span class="oh-kpi-pay-badge oh-kpi-pay-unpaid" title="Faktur terbit, belum ada pembayaran">
                            <i data-lucide="alert-circle"></i>
                            <span>Belum Lunas</span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Retur Fisik -->
        <div class="oh-kpi-card" style="border-left: 3.5px solid #f43f5e;">
            <div class="oh-kpi-header">
                <div>
                    <span class="oh-kpi-label block" style="color:#e11d48;">Total Retur Ditarik</span>
                    <span class="oh-kpi-tag text-rose-600 dark:text-rose-400">FISIK RETUR</span>
                </div>
                <div class="oh-kpi-icon bg-rose-500/10 text-rose-600 dark:text-rose-400">
                    <i data-lucide="undo-2"></i>
                </div>
            </div>
            <div>
                <div class="oh-kpi-value <?= ($totalQtyRusak + $totalQtyBagus > 0) ? 'text-rose-600 dark:text-rose-400' : '' ?>">
                    <?= number_format($totalQtyRusak + $totalQtyBagus, 0, ',', '.') ?><span class="oh-kpi-unit">pcs</span>
                </div>
                <div class="oh-kpi-subtitle"><?= $totalQtyRusak ?> BS/Rusak &bull; <?= $totalQtyBagus ?> Retur Bagus</div>
            </div>
        </div>

        <!-- Card 4: Total SKU Diperiksa -->
        <div class="oh-kpi-card" style="border-left: 3.5px solid #6366f1;">
            <div class="oh-kpi-header">
                <div>
                    <span class="oh-kpi-label block" style="color:#4f46e5;">SKU Produk Diaudit</span>
                    <span class="oh-kpi-tag text-indigo-600 dark:text-indigo-400">VARIAN PRODUK</span>
                </div>
                <div class="oh-kpi-icon bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                    <i data-lucide="boxes"></i>
                </div>
            </div>
            <div>
                <div class="oh-kpi-value text-indigo-600 dark:text-indigo-400">
                    <?= count($details) ?><span class="oh-kpi-unit">SKU</span>
                </div>
                <div class="oh-kpi-subtitle"><?= number_format($totalStokAwal, 0, ',', '.') ?> pcs total stok awal</div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. PRODUCT BREAKDOWN SECTION (TABLE & MOBILE CARDS WITH LIVE SEARCH)     -->
    <!-- ========================================================================= -->
    <div class="oh-card no-print">
        <div class="oh-card-header flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(2,132,199,0.1);color:#0284c7;">
                    <i data-lucide="boxes" style="width:18px;height:18px;"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm sm:text-base leading-tight" style="color:var(--color-ink);">
                        Rincian Stok &amp; Penjualan Rak
                    </h3>
                    <p class="text-xs mt-0.5" style="color:var(--color-ink-mute);">
                        Mutasi barang titip awal, laku terjual, retur ditarik, dan saldo rak baru
                    </p>
                </div>
            </div>

            <!-- Real-time Canonical Search Input -->
            <div class="w-full sm:w-72">
                <div class="oh-search-wrap">
                    <i data-lucide="search" class="oh-search-icon"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="Cari nama produk atau SKU..." 
                           class="oh-search-input">
                    <button type="button" 
                            x-show="searchQuery" 
                            @click="searchQuery = ''" 
                            class="oh-search-clear"
                            title="Hapus pencarian">
                        <i data-lucide="x" style="width:13px;height:13px;"></i>
                    </button>
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
                            <span :style="(item.retur_rusak + item.retur_bagus > 0) ? 'color:#f43f5e;' : ''">Retur</span>
                            <strong :style="(item.retur_rusak + item.retur_bagus > 0) ? 'color:#f43f5e;' : 'color:var(--color-ink-mute);'" x-text="item.retur_rusak + item.retur_bagus"></strong>
                        </div>
                        <div class="oh-flow-cell">
                            <span :style="item.selisih_qty < 0 ? 'color:#d97706;' : (item.selisih_qty > 0 ? 'color:#10b981;' : '')">Selisih</span>
                            <strong :style="item.selisih_qty < 0 ? 'color:#d97706;' : (item.selisih_qty > 0 ? 'color:#10b981;' : 'color:var(--color-ink-mute);')" 
                                    x-text="item.selisih_qty < 0 ? `-${Math.abs(item.selisih_qty)}` : (item.selisih_qty > 0 ? `+${item.selisih_qty}` : '0')"></strong>
                        </div>
                        <div class="oh-flow-cell">
                            <span style="color:#0284c7;">Sisa Rak</span>
                            <strong style="color:#0284c7;" x-text="item.sisa_fisik_di_rak"></strong>
                        </div>
                    </div>

                    <!-- Detail Pecahan Retur (Jika ada) -->
                    <template x-if="item.retur_rusak > 0 || item.retur_bagus > 0">
                        <div class="mb-2 flex flex-wrap items-center gap-1.5 text-[10.5px]">
                            <template x-if="item.retur_rusak > 0">
                                <span class="inline-flex items-center gap-1 font-mono font-bold px-2 py-0.5 rounded-md" style="background:rgba(244,63,94,0.1);color:#e11d48;">
                                    <i data-lucide="alert-octagon" class="w-3 h-3"></i>
                                    <span>BS: <strong x-text="item.retur_rusak"></strong></span>
                                </span>
                            </template>
                            <template x-if="item.retur_bagus > 0">
                                <span class="inline-flex items-center gap-1 font-mono font-bold px-2 py-0.5 rounded-md" style="background:rgba(245,158,11,0.1);color:#d97706;">
                                    <i data-lucide="corner-down-left" class="w-3 h-3"></i>
                                    <span>Bagus: <strong x-text="item.retur_bagus"></strong></span>
                                </span>
                            </template>
                        </div>
                    </template>

                    <!-- Alert Penjelasan Selisih Barang Hilang / Ketemu -->
                    <template x-if="item.selisih_qty !== 0">
                        <div class="mb-2 text-[11px] font-bold px-2 py-1 rounded-lg flex items-center gap-1.5" 
                             :style="item.selisih_qty < 0 ? 'background:rgba(245,158,11,0.1);color:#d97706;' : 'background:rgba(16,185,129,0.1);color:#10b981;'">
                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 flex-shrink-0" x-show="item.selisih_qty < 0"></i>
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 flex-shrink-0" x-show="item.selisih_qty > 0"></i>
                            <span x-text="item.selisih_qty < 0 ? 'Selisih Kurang: Hilang ' + Math.abs(item.selisih_qty) + ' pcs (Ditangguhkan/Gantung)' : 'Ditemukan Kembali: +' + item.selisih_qty + ' pcs'"></span>
                        </div>
                    </template>

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
                        <th style="text-align:center;">Selisih Rak</th>
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
                            <td style="text-align:center;">
                                <template x-if="item.selisih_qty < 0">
                                    <span class="inline-flex items-center gap-1 font-mono font-bold text-[11px] px-2 py-0.5 rounded-lg" style="background:rgba(245,158,11,0.12);color:#d97706;" title="Barang hilang ditangguhkan/gantung">
                                        <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                        <span x-text="'Hilang ' + Math.abs(item.selisih_qty)"></span>
                                    </span>
                                </template>
                                <template x-if="item.selisih_qty > 0">
                                    <span class="inline-flex items-center gap-1 font-mono font-bold text-[11px] px-2 py-0.5 rounded-lg" style="background:rgba(16,185,129,0.12);color:#10b981;" title="Barang ditemukan kembali">
                                        <i data-lucide="sparkles" class="w-3 h-3"></i>
                                        <span x-text="'Ketemu +' + item.selisih_qty"></span>
                                    </span>
                                </template>
                                <template x-if="item.selisih_qty === 0">
                                    <span class="font-mono text-mute text-xs">0 (Pas)</span>
                                </template>
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
                        <td colspan="9" class="text-center py-8 text-mute text-xs">
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
                        <td style="text-align:center;" class="font-mono font-bold text-xs">
                            <?php
                            $totSel = (int)array_sum(array_column($details, 'selisih_qty'));
                            if ($totSel < 0) {
                                echo '<span class="text-amber-600 dark:text-amber-400">Hilang ' . abs($totSel) . '</span>';
                            } elseif ($totSel > 0) {
                                echo '<span class="text-emerald-600 dark:text-emerald-400">Ketemu +' . $totSel . '</span>';
                            } else {
                                echo '<span class="text-mute">0</span>';
                            }
                            ?>
                        </td>
                        <td style="text-align:center;color:#0284c7;font-weight:900;" class="font-mono"><?= $totalSisaRak ?> pcs</td>
                        <td style="text-align:right;font-weight:800;white-space:nowrap;">GRAND TOTAL:</td>
                        <td style="text-align:right;color:#10b981;font-size:14px;font-weight:900;font-family:var(--font-mono);white-space:nowrap;">
                            <div><?= Format::rupiah($totalLakuRp) ?></div>
                            <?php if ($isNihil): ?>
                                <div style="font-size:10px;font-family:var(--font-sans);font-weight:700;color:var(--color-ink-mute);margin-top:2px;">Rp 0</div>
                            <?php elseif (!$hasInvoice): ?>
                                <div style="font-size:10px;font-family:var(--font-sans);font-weight:700;color:#d97706;margin-top:2px;">Belum Ditagih</div>
                            <?php elseif ($stBayar === 'lunas'): ?>
                                <div style="font-size:10px;font-family:var(--font-sans);font-weight:700;color:#059669;margin-top:2px;">✓ Sudah Lunas</div>
                            <?php elseif ($stBayar === 'sebagian'): ?>
                                <div style="font-size:10px;font-family:var(--font-sans);font-weight:700;color:#d97706;margin-top:2px;">Cicil (Sisa: <?= Format::rupiah((float)($visit['sisa_tagihan'] ?? 0)) ?>)</div>
                            <?php else: ?>
                                <div style="font-size:10px;font-family:var(--font-sans);font-weight:700;color:#e11d48;margin-top:2px;">Belum Lunas</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>


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

        <?php if (!empty($visit['foto_kunjungan'])): ?>
        <div class="pt-3 border-t border-hairline">
            <div class="text-xs font-bold mb-2 flex items-center gap-1.5" style="color:var(--color-ink);">
                <i data-lucide="camera" class="w-4 h-4 text-sky-600"></i>
                <span>Foto Bukti Kunjungan / Barang Retur:</span>
            </div>
            <div class="inline-block rounded-xl overflow-hidden border border-hairline bg-slate-50 dark:bg-slate-800">
                <a href="<?= htmlspecialchars($visit['foto_kunjungan']) ?>" target="_blank" title="Klik untuk memperbesar gambar">
                    <img src="<?= htmlspecialchars($visit['foto_kunjungan']) ?>" alt="Bukti Kunjungan" style="max-height:220px;max-width:100%;object-fit:cover;display:block;" class="hover:opacity-90 transition-opacity">
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>



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

        <div style="font-size:10px;display:flex;justify-content:space-between;margin-top:2px;">
            <span>Status Audit Fisik:</span>
            <span>TERVERIFIKASI</span>
        </div>
        <?php if ($hasInvoice): ?>
        <div style="font-size:10px;display:flex;justify-content:space-between;margin-top:2px;">
            <span>No. Faktur Terbit:</span>
            <span><?= htmlspecialchars($visit['nomor_nota']) ?></span>
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
            'selisih_qty' => (int)($d['selisih_qty'] ?? 0),
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
            this.$watch('searchQuery', () => {
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
                        lucide.createIcons();
                    }
                });
            });
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

function handleOpnameBackNavigation(e, fallbackUrl) {
    if (window.history.length > 1 && document.referrer) {
        try {
            const refUrl = new URL(document.referrer);
            if (refUrl.origin === window.location.origin) {
                const p = refUrl.pathname;
                // Cegah kembali ke form input opname baru (menghindari resubmission), diri sendiri, atau endpoint aksi POST
                const isOpnameForm = p.includes('/consignment/opname') && !p.includes('/hasil');
                const isSelf = p.includes('/consignment/opname/hasil');
                const isAction = p.includes('/submit') || p.includes('/generate') || p.includes('/bayar');

                if (!isOpnameForm && !isSelf && !isAction) {
                    e.preventDefault();
                    window.history.back();
                    return;
                }
            }
        } catch (err) {
            // Biarkan navigasi default via href
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();

$totalLakuRp = (float)($visit['total_laku_nominal'] ?? 0);
$hasInvoice = !empty($visit['nomor_nota']) && $totalLakuRp > 0;
$totalQtyLaku = array_sum(array_column($details, 'jumlah_laku_terjual'));
$totalQtyRusak = array_sum(array_column($details, 'retur_rusak'));

// Susun teks WhatsApp untuk dikirim ke pemilik toko
$waPhone = !empty($visit['nomor_whatsapp']) ? preg_replace('/[^0-9]/', '', (string)$visit['nomor_whatsapp']) : '';
if (!empty($waPhone) && str_starts_with($waPhone, '0')) {
    $waPhone = '62' . substr($waPhone, 1);
}

$waMessageLines = [
    "*KEREN SNACK — BUKTI KUNJUNGAN KONSINYASI*",
    "Toko: " . ($visit['nama_toko'] ?? 'Toko Mitra'),
    "Tanggal: " . date('d/m/Y', strtotime($visit['tanggal_kunjungan'] ?? 'now')),
    "No. Kunjungan: " . ($visit['nomor_kunjungan'] ?? '-'),
    $hasInvoice ? "No. Nota: " . ($visit['nomor_nota'] ?? '-') : "Status: Tidak Ada Barang Laku",
    "----------------------------------------",
    "*RINCIAN PRODUK:*"
];

foreach ($details as $d) {
    $laku = (int)$d['jumlah_laku_terjual'];
    $rusak = (int)$d['retur_rusak'];
    $sisa = (int)$d['sisa_fisik_di_rak'];
    $nama = $d['nama_item'] ?? 'Produk';
    
    $itemLine = "• {$nama}: Laku {$laku} pcs (Sisa rak: {$sisa} pcs)";
    if ($rusak > 0) {
        $itemLine .= " [Retur Rusak: {$rusak} pcs]";
    }
    $waMessageLines[] = $itemLine;
}

$waMessageLines[] = "----------------------------------------";
$waMessageLines[] = "*TOTAL PENJUALAN: " . Format::rupiah($totalLakuRp) . "*";
if ($hasInvoice) {
    $sisaTagihan = (float)($visit['sisa_tagihan'] ?? $totalLakuRp);
    $waMessageLines[] = "Status Nota: " . strtoupper($visit['status_pembayaran'] ?? 'BELUM LUNAS') . " (Sisa: " . Format::rupiah($sisaTagihan) . ")";
}
$waMessageLines[] = "";
$waMessageLines[] = "Terima kasih atas kerja samanya! 🙏";

$encodedWaUrl = !empty($waPhone) ? "https://wa.me/{$waPhone}?text=" . urlencode(implode("\n", $waMessageLines)) : '';
?>

<!-- Print-Only Styling for Thermal Receipt / Standard Paper -->
<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printableReceipt, #printableReceipt * {
        visibility: visible;
    }
    #printableReceipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        color: #000 !important;
        background: #fff !important;
        padding: 10px;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<div class="space-y-4 sm:space-y-6 pb-24">

    <!-- PAGE HEADER -->
    <div class="page-header no-print">
        <div class="page-header-body">
            <div class="page-header-icon is-emerald">
                <i data-lucide="check-circle"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#10b981;"></span>
                    <span>Kunjungan Berhasil Diproses</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl">Hasil Kunjungan Konsinyasi</h1>
                <p class="page-subtitle text-xs sm:text-sm">Ringkasan settlement opname rak &amp; status penerbitan faktur penjualan.</p>
            </div>
        </div>
        <div class="page-header-actions flex items-center gap-2 flex-wrap w-full sm:w-auto">
            <a href="<?= Router::url('/consignment/opname') ?>" class="btn btn-secondary btn-sm flex-1 sm:flex-initial flex items-center justify-center gap-1.5">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Ke Daftar Toko</span>
            </a>
            <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary btn-sm flex-1 sm:flex-initial flex items-center justify-center gap-1.5">
                <i data-lucide="store" class="w-4 h-4"></i>
                <span>Ke Portal</span>
            </a>
        </div>
    </div>

    <!-- MAIN SUMMARY CARD & ACTIONS -->
    <div id="printableReceipt" class="card p-4 sm:p-6 rounded-3xl space-y-4 sm:space-y-6" style="border:1px solid var(--color-hairline);">

        <!-- HEADER STRUK -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 sm:pb-6 gap-3 sm:gap-4" style="border-bottom:1px solid var(--color-hairline);">
            <div>
                <span class="text-[10px] sm:text-xs font-bold uppercase tracking-widest block" style="color:var(--color-ink-mute);">BUKTI KUNJUNGAN KONSINYASI</span>
                <h2 class="text-xl sm:text-2xl font-black mt-1" style="color:var(--color-ink);"><?= htmlspecialchars($visit['nama_toko']) ?></h2>
                <p class="text-xs mt-0.5" style="color:var(--color-ink-mute);"><?= htmlspecialchars($visit['alamat_lengkap'] ?? '-') ?></p>
            </div>

            <div class="space-y-1 text-xs sm:text-right">
                <div>
                    <span style="color:var(--color-ink-mute);">No. Kunjungan:</span>
                    <strong class="font-mono ml-1" style="color:var(--color-ink);"><?= htmlspecialchars($visit['nomor_kunjungan']) ?></strong>
                </div>
                <div>
                    <span style="color:var(--color-ink-mute);">Tanggal:</span>
                    <strong class="ml-1" style="color:var(--color-ink);"><?= date('d F Y', strtotime($visit['tanggal_kunjungan'])) ?></strong>
                </div>
                <div>
                    <span style="color:var(--color-ink-mute);">Sales:</span>
                    <strong class="ml-1" style="color:var(--color-ink);"><?= htmlspecialchars($visit['sales_name']) ?></strong>
                </div>
            </div>
        </div>

        <!-- STATUS FAKTUR / LAKU BANNER -->
        <?php if ($hasInvoice): ?>
        <div class="p-3.5 sm:p-4 rounded-2xl flex items-center justify-between flex-wrap gap-3" style="background:rgba(16,185,129,0.08);border:1.5px solid rgba(16,185,129,0.3);">
            <div class="flex items-center gap-3">
                <i data-lucide="receipt" class="w-6 h-6 flex-shrink-0" style="color:#10b981;"></i>
                <div>
                    <div class="text-[10px] sm:text-xs font-bold uppercase tracking-wider" style="color:#10b981;">FAKTUR OTOMATIS TERBIT</div>
                    <div class="text-sm sm:text-base font-black font-mono" style="color:var(--color-ink);"><?= htmlspecialchars($visit['nomor_nota']) ?></div>
                </div>
            </div>

            <div class="text-right">
                <span class="text-[11px] block" style="color:var(--color-ink-mute);">Total Tagihan Laku:</span>
                <span class="text-xl sm:text-2xl font-black text-emerald-500"><?= Format::rupiah($totalLakuRp) ?></span>
            </div>
        </div>
        <?php else: ?>
        <div class="p-3.5 sm:p-4 rounded-2xl flex items-center gap-3" style="background:rgba(2,132,199,0.08);border:1.5px solid rgba(2,132,199,0.25);">
            <i data-lucide="info" class="w-6 h-6 flex-shrink-0" style="color:#0284c7;"></i>
            <div>
                <div class="text-xs sm:text-sm font-bold" style="color:var(--color-ink);">Tidak Ada Barang Laku pada Kunjungan Ini</div>
                <div class="text-[11px] sm:text-xs" style="color:var(--color-ink-mute);">Seluruh stok titipan rak tetap utuh atau nihil penjualan. Tidak ada faktur tagihan yang diterbitkan.</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- RINCIAN TABEL BARANG (RESPONSIVE SCROLL) -->
        <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
            <table class="w-full text-left text-xs min-w-[620px]">
                <thead>
                    <tr class="font-bold uppercase tracking-wider text-[10.5px]" style="border-bottom:1px solid var(--color-hairline);color:var(--color-ink-mute);">
                        <th class="py-2.5 px-2">Item Produk</th>
                        <th class="py-2.5 px-2 text-center">Titip Awal</th>
                        <th class="py-2.5 px-2 text-center text-emerald-600 dark:text-emerald-400">Laku</th>
                        <th class="py-2.5 px-2 text-center text-rose-600 dark:text-rose-400">Retur Rusak</th>
                        <th class="py-2.5 px-2 text-center text-amber-600 dark:text-amber-400">Retur Bagus</th>
                        <th class="py-2.5 px-2 text-center">Sisa Rak Baru</th>
                        <th class="py-2.5 px-2 text-right">Harga Deal</th>
                        <th class="py-2.5 px-2 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color:var(--color-hairline);">
                    <?php foreach ($details as $d): ?>
                    <tr class="hover:bg-slate-500/5 transition-colors">
                        <td class="py-2.5 px-2 font-bold" style="color:var(--color-ink);">
                            <?= htmlspecialchars($d['nama_item']) ?>
                            <span class="text-[9.5px] block font-mono font-normal" style="color:var(--color-ink-mute);"><?= htmlspecialchars($d['kode_sku'] ?? '') ?></span>
                        </td>
                        <td class="py-2.5 px-2 text-center" style="color:var(--color-ink-mute);"><?= $d['stok_titip_awal'] ?> <?= $d['satuan_dasar'] ?></td>
                        <td class="py-2.5 px-2 text-center font-black <?= (int)$d['jumlah_laku_terjual'] > 0 ? 'text-emerald-500 font-extrabold' : '' ?>" style="<?= (int)$d['jumlah_laku_terjual'] === 0 ? 'color:var(--color-ink-mute);' : '' ?>">
                            <?= $d['jumlah_laku_terjual'] ?> <?= $d['satuan_dasar'] ?>
                        </td>
                        <td class="py-2.5 px-2 text-center font-bold text-rose-500">
                            <?= $d['retur_rusak'] ?>
                        </td>
                        <td class="py-2.5 px-2 text-center font-bold text-amber-500">
                            <?= $d['retur_bagus'] ?>
                        </td>
                        <td class="py-2.5 px-2 text-center font-black text-sky-600 dark:text-sky-400">
                            <?= $d['sisa_fisik_di_rak'] ?> <?= $d['satuan_dasar'] ?>
                        </td>
                        <td class="py-2.5 px-2 text-right" style="color:var(--color-ink-mute);"><?= Format::rupiah((float)$d['harga_satuan_deal']) ?></td>
                        <td class="py-2.5 px-2 text-right font-black" style="color:var(--color-ink);"><?= Format::rupiah((float)$d['subtotal_laku']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="font-bold text-xs" style="border-top:2px solid var(--color-hairline);">
                        <td colspan="2" class="py-2.5 px-2" style="color:var(--color-ink-mute);">TOTAL:</td>
                        <td class="py-2.5 px-2 text-center text-emerald-500 font-black"><?= $totalQtyLaku ?> pcs</td>
                        <td class="py-2.5 px-2 text-center text-rose-500 font-bold"><?= $totalQtyRusak ?> pcs</td>
                        <td colspan="3" class="py-2.5 px-2 text-right" style="color:var(--color-ink-mute);">GRAND TOTAL:</td>
                        <td class="py-2.5 px-2 text-right text-emerald-500 text-sm font-black"><?= Format::rupiah($totalLakuRp) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if (!empty($visit['catatan'])): ?>
        <div class="p-3 rounded-xl text-xs" style="background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-ink-secondary);">
            <strong style="color:var(--color-ink);">Catatan Kunjungan:</strong> <?= htmlspecialchars($visit['catatan']) ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- ACTION BUTTONS: WHATSAPP SHARE & THERMAL PRINT (RESPONSIVE) -->
    <div class="card p-4 sm:p-5 rounded-3xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 no-print" style="border:1px solid var(--color-hairline);">
        <div class="flex items-center gap-3">
            <i data-lucide="share-2" class="w-6 h-6 flex-shrink-0" style="color:#10b981;"></i>
            <div>
                <div class="text-xs sm:text-sm font-bold" style="color:var(--color-ink);">Bagikan Bukti Kunjungan</div>
                <div class="text-[11px] sm:text-xs" style="color:var(--color-ink-mute);">Kirim rekap digital langsung ke pemilik toko atau cetak fisik.</div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
            <?php if (!empty($encodedWaUrl)): ?>
            <a href="<?= $encodedWaUrl ?>" target="_blank" class="btn btn-primary flex-1 sm:flex-initial flex items-center justify-center gap-2" style="background:#25D366;border-color:#25D366;color:#fff;font-weight:800;">
                <i data-lucide="message-circle" class="w-4 h-4"></i>
                <span>Kirim WhatsApp</span>
            </a>
            <?php endif; ?>

            <button type="button" onclick="window.print()" class="btn btn-secondary flex-1 sm:flex-initial flex items-center justify-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak Struk</span>
            </button>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();

$nilaiSelisihRp = (float)($opname['total_nilai_selisih_rp'] ?? 0);
$totalKatalog = !empty($opname['total_item_katalog']) ? (int)$opname['total_item_katalog'] : null;
?>

<div x-data="{ search: '', filterType: 'all' }" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-emerald" style="flex-shrink:0;">
                <i data-lucide="clipboard-check"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#10b981;"></span>
                    <span>Bukti Penyesuaian Stok Fisik</span>
                </div>
                <h1 class="page-title" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($opname['nomor_dokumen']) ?></h1>
                <p class="page-subtitle">Dicatat pada <?= date('d M Y, H:i', strtotime($opname['dibuat_pada'])) ?> WIB oleh <?= htmlspecialchars($opname['nama_pembuat'] ?? 'Petugas Gudang') ?></p>
            </div>
        </div>
        <div class="page-header-actions" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; flex-shrink:0;">
            <a href="<?= Router::url('/inventory') ?>" class="btn btn-secondary" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="warehouse" style="width:15px; height:15px;"></i>
                <span>Katalog Stok</span>
            </a>
            <a href="<?= Router::url('/inventory/opname/history') ?>" class="btn btn-secondary" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="history" style="width:15px; height:15px;"></i>
                <span>Riwayat Dokumen</span>
            </a>
            <a href="<?= Router::url('/inventory/opname/excel?id=' . urlencode($opname['id'])) ?>" class="btn btn-secondary" style="height:38px; background:#10b981; color:#fff; border-color:#059669; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="file-spreadsheet" style="width:15px; height:15px;"></i>
                <span>Export Excel</span>
            </a>
            <a href="<?= Router::url('/inventory/opname/pdf?id=' . urlencode($opname['id'])) ?>" target="_blank" class="btn btn-primary" style="height:38px; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="printer" style="width:15px; height:15px;"></i>
                <span>Cetak PDF</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SUMMARY CARDS                                                             -->
    <!-- ========================================================================= -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px;">
        <div class="card" style="padding:16px 18px; border-radius:16px; border:1px solid var(--color-hairline); background:var(--color-surface);">
            <div style="font-size:11px; text-transform:uppercase; color:var(--color-ink-mute); font-weight:700; letter-spacing:0.04em;">Nomor Dokumen</div>
            <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); color:var(--color-primary); margin-top:4px;">
                <?= htmlspecialchars($opname['nomor_dokumen']) ?>
            </div>
        </div>

        <div class="card" style="padding:16px 18px; border-radius:16px; border:1px solid var(--color-hairline); background:var(--color-surface);">
            <div style="font-size:11px; text-transform:uppercase; color:var(--color-ink-mute); font-weight:700; letter-spacing:0.04em;">Tanggal Opname</div>
            <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); color:var(--color-ink); margin-top:4px;">
                <?= date('d/m/Y', strtotime($opname['tanggal'])) ?>
            </div>
        </div>

        <div class="card" style="padding:16px 18px; border-radius:16px; border:1px solid var(--color-hairline); background:var(--color-surface);">
            <div style="font-size:11px; text-transform:uppercase; color:var(--color-ink-mute); font-weight:700; letter-spacing:0.04em;">Item Disesuaikan</div>
            <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); color:var(--color-primary); margin-top:4px;">
                <?= (int)$opname['total_item_selisih'] ?> Produk
                <?php if ($totalKatalog): ?>
                <span style="font-size:11px; font-weight:normal; color:var(--color-ink-mute); display:block;">dari <?= $totalKatalog ?> di katalog</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="padding:16px 18px; border-radius:16px; border:1px solid #a7f3d0; background:#ecfdf5;">
            <div style="font-size:11px; text-transform:uppercase; color:#047857; font-weight:700; letter-spacing:0.04em;">Total Stok Masuk (+)</div>
            <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); color:#059669; margin-top:4px;">
                +<?= Format::qty($opname['total_qty_masuk']) ?> pcs
            </div>
        </div>

        <div class="card" style="padding:16px 18px; border-radius:16px; border:1px solid #fecaca; background:#fef2f2;">
            <div style="font-size:11px; text-transform:uppercase; color:#b91c1c; font-weight:700; letter-spacing:0.04em;">Total Stok Keluar (-)</div>
            <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); color:#dc2626; margin-top:4px;">
                -<?= Format::qty($opname['total_qty_keluar']) ?> pcs
            </div>
        </div>

        <div class="card" style="padding:16px 18px; border-radius:16px; border:1px solid <?= $nilaiSelisihRp > 0 ? '#a7f3d0' : ($nilaiSelisihRp < 0 ? '#fecaca' : 'var(--color-hairline)') ?>; background:<?= $nilaiSelisihRp > 0 ? '#ecfdf5' : ($nilaiSelisihRp < 0 ? '#fef2f2' : 'var(--color-surface)') ?>;">
            <div style="font-size:11px; text-transform:uppercase; color:<?= $nilaiSelisihRp > 0 ? '#047857' : ($nilaiSelisihRp < 0 ? '#b91c1c' : 'var(--color-ink-mute)') ?>; font-weight:700; letter-spacing:0.04em;">Dampak Valuasi Selisih</div>
            <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); color:<?= $nilaiSelisihRp > 0 ? '#059669' : ($nilaiSelisihRp < 0 ? '#dc2626' : 'var(--color-ink)') ?>; margin-top:4px;">
                <?= ($nilaiSelisihRp > 0 ? '+' : '') . Format::rupiah($nilaiSelisihRp) ?>
            </div>
        </div>
    </div>

    <!-- Catatan Dokumen -->
    <?php if (!empty($opname['catatan'])): ?>
    <div style="background:var(--color-canvas-soft); padding:12px 18px; border-radius:12px; border:1px solid var(--color-hairline); font-size:13px; color:var(--color-ink);">
        <strong style="color:var(--color-ink-mute);">Keterangan Dokumen:</strong>
        <span style="margin-left:6px;"><?= htmlspecialchars($opname['catatan']) ?></span>
    </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- TOOLBAR CARI & FILTER RINCIAN                                             -->
    <!-- ========================================================================= -->
    <div class="card" style="padding:12px 18px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <div class="form-input-icon" style="flex:1; min-width:240px;">
            <i data-lucide="search" class="icon-left"></i>
            <input type="text" x-model="search" placeholder="Cari SKU, Nama Produk, atau Barcode dalam dokumen ini..." class="form-input" style="height:38px;">
        </div>
        <div style="min-width:180px;">
            <select x-model="filterType" class="form-select" style="height:38px; font-size:13px;">
                <option value="all">Semua Mutasi</option>
                <option value="in">Hanya Stok Masuk (+)</option>
                <option value="out">Hanya Stok Keluar (-)</option>
                <option value="zero">Hanya Tetap / Sesuai (0)</option>
            </select>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TABEL RINCIAN ITEM OPNAME                                                 -->
    <!-- ========================================================================= -->
    <div class="table-wrapper">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:45px; text-align:center;">No</th>
                        <th style="min-width:220px;">Produk &amp; SKU</th>
                        <th style="min-width:110px;">Barcode</th>
                        <th style="text-align:right; width:110px;">Stok Sistem</th>
                        <th style="text-align:right; width:110px;">Stok Fisik</th>
                        <th style="text-align:right; width:120px;">Selisih (+/-)</th>
                        <th style="text-align:center; width:110px;">Status</th>
                        <th style="text-align:right; width:120px;">HPP Satuan</th>
                        <th style="text-align:right; width:130px;">Subtotal Selisih</th>
                        <th style="min-width:160px;">Catatan Baris</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="10" style="text-align:center; padding:36px; color:var(--color-ink-mute);">
                            Tidak ada rincian item dalam dokumen opname ini.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php $no = 1; foreach ($items as $it): 
                        $selisih = (float)$it['selisih'];
                        $isMasuk = $selisih > 0;
                        $isKeluar = $selisih < 0;
                        $isTetap = abs($selisih) <= 0.0001;
                        $hpp = (float)($it['harga_pokok_saat_opname'] ?? $it['hpp_efektif'] ?? 0);
                        $subtotalRp = (float)($it['subtotal_nilai_selisih'] ?? ($selisih * $hpp));
                        $searchableText = strtolower(($it['kode_sku'] ?? '') . ' ' . ($it['nama_item'] ?? '') . ' ' . ($it['barcode'] ?? '') . ' ' . ($it['kode_grup'] ?? '') . ' ' . ($it['nama_grup'] ?? ''));
                    ?>
                    <tr data-search="<?= htmlspecialchars($searchableText, ENT_QUOTES, 'UTF-8') ?>"
                        x-show="(!search.trim() || ($el.getAttribute('data-search') && $el.getAttribute('data-search').includes(search.toLowerCase().trim()))) && (filterType === 'all' || (filterType === 'in' && <?= $isMasuk ? 'true' : 'false' ?>) || (filterType === 'out' && <?= $isKeluar ? 'true' : 'false' ?>) || (filterType === 'zero' && <?= $isTetap ? 'true' : 'false' ?>))">
                        <td style="text-align:center; font-family:var(--font-mono); font-size:12px; color:var(--color-ink-mute);"><?= $no++ ?></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <span class="badge badge-mono"><?= htmlspecialchars($it['kode_sku']) ?></span>
                                <span style="font-size:13px; font-weight:700; color:var(--color-ink);"><?= htmlspecialchars($it['nama_item']) ?></span>
                            </div>
                            <?php 
                            $grpDisplay = trim(($it['kode_grup'] ?? '') . (!empty($it['kode_grup']) && !empty($it['nama_grup']) ? ' • ' : '') . ($it['nama_grup'] ?? ''));
                            if ($grpDisplay !== ''): 
                            ?>
                            <div style="font-size:11px; font-family:var(--font-mono); color:var(--color-ink-mute); margin-top:2px;">
                                <?= htmlspecialchars($grpDisplay) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-family:var(--font-mono); font-size:11px; padding:2px 6px; border:1px solid var(--color-hairline); border-radius:4px; background:var(--color-canvas-soft);">
                                <?= htmlspecialchars($it['barcode'] ?? '—') ?>
                            </span>
                        </td>
                        <td style="text-align:right; font-family:var(--font-mono); font-size:13px; color:var(--color-ink);">
                            <?= Format::qty($it['stok_sistem']) ?> <?= htmlspecialchars($it['satuan_dasar'] ?? 'pcs') ?>
                        </td>
                        <td style="text-align:right; font-family:var(--font-mono); font-weight:700; font-size:13.5px; color:var(--color-ink);">
                            <?= Format::qty($it['stok_fisik']) ?> <?= htmlspecialchars($it['satuan_dasar'] ?? 'pcs') ?>
                        </td>
                        <td style="text-align:right; font-family:var(--font-mono); font-weight:800; font-size:13.5px; color:<?= $isMasuk ? '#059669' : ($isKeluar ? '#dc2626' : 'var(--color-ink-mute)') ?>;">
                            <?= ($isMasuk ? '+' : '') . Format::qty($selisih) ?> <?= htmlspecialchars($it['satuan_dasar'] ?? 'pcs') ?>
                        </td>
                        <td style="text-align:center;">
                            <?php if ($isMasuk): ?>
                            <span class="badge badge-success" style="font-weight:700;">
                                <i data-lucide="arrow-up-circle" style="width:12px;height:12px;margin-right:2px;"></i> Masuk
                            </span>
                            <?php elseif ($isKeluar): ?>
                            <span class="badge badge-danger" style="font-weight:700;">
                                <i data-lucide="arrow-down-circle" style="width:12px;height:12px;margin-right:2px;"></i> Keluar
                            </span>
                            <?php else: ?>
                            <span class="badge badge-muted" style="font-size:11px;">
                                Tetap
                            </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right; font-family:var(--font-mono); font-size:12.5px; color:var(--color-ink);">
                            <?= Format::rupiah($hpp) ?>
                        </td>
                        <td style="text-align:right; font-family:var(--font-mono); font-weight:700; font-size:12.5px; color:<?= $subtotalRp > 0 ? '#059669' : ($subtotalRp < 0 ? '#dc2626' : 'var(--color-ink-mute)') ?>;">
                            <?= ($subtotalRp > 0 ? '+' : '') . Format::rupiah($subtotalRp) ?>
                        </td>
                        <td style="font-size:12px; color:var(--color-ink-mute);">
                            <?= !empty($it['catatan_item']) ? htmlspecialchars($it['catatan_item']) : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($items)): ?>
                <tfoot>
                    <tr style="font-weight:700; background:var(--color-canvas-soft); border-top:2px solid var(--color-hairline);">
                        <td colspan="3" style="text-align:right; padding:10px 14px; font-size:12px; text-transform:uppercase; letter-spacing:0.04em; white-space:nowrap;">Total Dokumen:</td>
                        <td style="text-align:right; font-family:var(--font-mono); font-size:13px;"><?= Format::qty(array_sum(array_column($items, 'stok_sistem'))) ?></td>
                        <td style="text-align:right; font-family:var(--font-mono); font-size:13.5px;"><?= Format::qty(array_sum(array_column($items, 'stok_fisik'))) ?></td>
                        <td style="text-align:right; font-family:var(--font-mono); font-weight:800; font-size:13.5px; color:<?= (float)$opname['total_qty_masuk'] - (float)$opname['total_qty_keluar'] >= 0 ? '#059669' : '#dc2626' ?>;">
                            <?= ((float)$opname['total_qty_masuk'] - (float)$opname['total_qty_keluar'] > 0 ? '+' : '') . Format::qty((float)$opname['total_qty_masuk'] - (float)$opname['total_qty_keluar']) ?>
                        </td>
                        <td></td>
                        <td style="text-align:right; font-size:11.5px; font-weight:700; color:var(--color-ink-mute); text-transform:uppercase; letter-spacing:0.04em; white-space:nowrap;">Valuasi:</td>
                        <td style="text-align:right; font-family:var(--font-mono); font-weight:800; font-size:13px; white-space:nowrap; color:<?= $nilaiSelisihRp > 0 ? '#059669' : ($nilaiSelisihRp < 0 ? '#dc2626' : 'var(--color-ink)') ?>;">
                            <?= ($nilaiSelisihRp > 0 ? '+' : '') . Format::rupiah($nilaiSelisihRp) ?>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Bottom Actions -->
    <div class="opname-detail-actions">
        <a href="<?= Router::url('/inventory/bulk-opname') ?>" class="btn btn-primary opname-action-btn">
            <i data-lucide="plus-circle" style="width:16px; height:16px;"></i>
            <span>Buat Bulk Opname Baru</span>
        </a>
    </div>

    <style>
    .opname-detail-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding-top: 14px;
        margin-bottom: 12px;
        width: 100%;
        box-sizing: border-box;
    }
    .opname-detail-actions .opname-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 40px;
        font-weight: 600;
        white-space: nowrap;
    }
    @media (max-width: 640px) {
        .opname-detail-actions .opname-action-btn {
            width: 100% !important;
            justify-content: center !important;
            box-sizing: border-box !important;
        }
    }
    </style>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

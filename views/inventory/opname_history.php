<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();

$totalDocs = count($history);
$totalItemSelisih = array_sum(array_column($history, 'total_item_selisih'));
$totalMasuk = array_sum(array_column($history, 'total_qty_masuk'));
$totalKeluar = array_sum(array_column($history, 'total_qty_keluar'));
?>

<div x-data="opnameHistoryApp()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER                                                            -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-emerald" style="flex-shrink:0;">
                <i data-lucide="history"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#10b981;"></span>
                    <span>Arsip &amp; Audit Stok Fisik</span>
                </div>
                <h1 class="page-title" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= $pageTitle ?? 'Riwayat Dokumen Opname Gudang' ?>
                </h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Daftar Seluruh Sesi Penyesuaian Fisik &amp; Mutasi Stok Gudang Pusat' ?></p>
            </div>
        </div>
        <div class="page-header-actions" style="display:flex; gap:8px; align-items:center; flex-shrink:0;">
            <a href="<?= Router::url('/inventory') ?>" class="btn btn-secondary" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="warehouse" style="width:15px; height:15px;"></i>
                <span>Katalog Stok</span>
            </a>
            <?php if (Auth::can('inventory.opname')): ?>
            <a href="<?= Router::url('/inventory/bulk-opname') ?>" class="btn btn-primary" style="height:38px; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="layers" style="width:15px; height:15px;"></i>
                <span>Bulk Opname Baru</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. QUICK KPI SUMMARY CARDS (Reactive to active filters)                  -->
    <!-- ========================================================================= -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
        <!-- Total Dokumen -->
        <div class="card" style="padding:14px 16px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(99,102,241,0.1); color:var(--color-primary); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="file-check-2" style="width:20px; height:20px;"></i>
            </div>
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--color-ink-mute); letter-spacing:0.04em;">Dokumen Sesi</div>
                <div style="font-size:17px; font-weight:800; font-family:var(--font-mono); color:var(--color-ink); margin-top:2px;">
                    <span x-text="kpiTotalDocs"></span> <span style="font-size:11px; font-weight:600; color:var(--color-ink-mute);">Sesi</span>
                </div>
            </div>
        </div>

        <!-- Total Item Selisih -->
        <div class="card" style="padding:14px 16px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(139,92,246,0.1); color:#7c3aed; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="check-square" style="width:20px; height:20px;"></i>
            </div>
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--color-ink-mute); letter-spacing:0.04em;">Item Disesuaikan</div>
                <div style="font-size:17px; font-weight:800; font-family:var(--font-mono); color:#7c3aed; margin-top:2px;">
                    <span x-text="kpiTotalItemSelisih"></span> <span style="font-size:11px; font-weight:600; color:var(--color-ink-mute);">Baris</span>
                </div>
            </div>
        </div>

        <!-- Total Stok Masuk -->
        <div class="card" style="padding:14px 16px; border-radius:14px; border:1px solid #a7f3d0; background:rgba(16,185,129,0.06); display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(16,185,129,0.15); color:#059669; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="arrow-up-right" style="width:20px; height:20px;"></i>
            </div>
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:#047857; letter-spacing:0.04em;">Akumulasi Masuk (+)</div>
                <div style="font-size:17px; font-weight:800; font-family:var(--font-mono); color:#059669; margin-top:2px;">
                    +<span x-text="formatQty(kpiTotalMasuk)"></span> <span style="font-size:11px; font-weight:600; color:#047857;">pcs</span>
                </div>
            </div>
        </div>

        <!-- Total Stok Keluar -->
        <div class="card" style="padding:14px 16px; border-radius:14px; border:1px solid #fecaca; background:rgba(239,68,68,0.05); display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(239,68,68,0.12); color:#dc2626; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="arrow-down-right" style="width:20px; height:20px;"></i>
            </div>
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:#b91c1c; letter-spacing:0.04em;">Akumulasi Keluar (-)</div>
                <div style="font-size:17px; font-weight:800; font-family:var(--font-mono); color:#dc2626; margin-top:2px;">
                    -<span x-text="formatQty(kpiTotalKeluar)"></span> <span style="font-size:11px; font-weight:600; color:#b91c1c;">pcs</span>
                </div>
            </div>
        </div>

        <!-- Valuasi Selisih (Rp) -->
        <div class="card" style="padding:14px 16px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(16,185,129,0.1); color:#059669; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="badge-dollar-sign" style="width:20px; height:20px;"></i>
            </div>
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--color-ink-mute); letter-spacing:0.04em;">Net Dampak Valuasi</div>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); margin-top:2px;"
                     :style="kpiTotalNilaiSelisihRp > 0 ? 'color:#059669;' : (kpiTotalNilaiSelisihRp < 0 ? 'color:#dc2626;' : 'color:var(--color-ink);')"
                     x-text="(kpiTotalNilaiSelisihRp > 0 ? '+' : '') + formatRupiah(kpiTotalNilaiSelisihRp)">
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. FILTER & SEARCH TOOLBAR                                                -->
    <!-- ========================================================================= -->
    <div class="card" style="padding:14px 18px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <!-- Live Search -->
        <div class="form-input-icon" style="flex:1; min-width:240px;">
            <i data-lucide="search" class="icon-left"></i>
            <input type="text" x-model="searchQuery" @input="currentPage = 1"
                   placeholder="Cari Nomor Dokumen (OPN-...), Petugas, atau Catatan..."
                   class="form-input" style="height:38px;">
        </div>

        <!-- Date Range Filter -->
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="font-size:12px; color:var(--color-ink-mute); font-weight:600;">Dari:</span>
                <input type="date" x-model="tanggalMulai" @change="currentPage = 1" class="form-input" style="height:38px; width:145px; font-size:12.5px;">
            </div>
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="font-size:12px; color:var(--color-ink-mute); font-weight:600;">Sampai:</span>
                <input type="date" x-model="tanggalSelesai" @change="currentPage = 1" class="form-input" style="height:38px; width:145px; font-size:12.5px;">
            </div>
            <button type="button" @click="resetFilters()" x-show="searchQuery || tanggalMulai || tanggalSelesai" class="btn btn-ghost btn-sm" style="color:#dc2626; height:38px;">
                <i data-lucide="rotate-ccw" style="width:13px; height:13px;"></i>
                <span>Reset</span>
            </button>
        </div>

        <!-- Rows Selector -->
        <div style="display:flex; align-items:center; gap:6px;">
            <span style="font-size:12px; color:var(--color-ink-mute); white-space:nowrap;">Baris:</span>
            <select x-model="perPage" @change="currentPage = 1" class="form-select" style="height:38px; width:80px; font-size:12.5px; font-family:var(--font-mono);">
                <option value="15">15</option>
                <option value="30">30</option>
                <option value="50">50</option>
                <option value="all">Semua</option>
            </select>
        </div>

        <div style="font-size:12px; color:var(--color-ink-mute); font-family:var(--font-mono); margin-left:auto;">
            Menampilkan: <strong class="text-ink" x-text="filteredHistory.length"></strong> Dokumen
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. DATA TABLE                                                             -->
    <!-- ========================================================================= -->
    <div class="table-wrapper" x-ref="tableWrapper"
         style="overflow-anchor:none; scroll-behavior:auto !important;">

        <!-- EMPTY STATE 1: Database Kosong (Belum Pernah Ada Dokumen Opname) -->
        <template x-if="historyList.length === 0">
            <div style="padding: 56px 24px; text-align: center;">
                <div style="width: 56px; height: 56px; border-radius: 14px; background: rgba(99, 102, 241, 0.08); color: var(--color-primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/></svg>
                </div>
                <h3 style="font-size: 16px; font-weight: 700; color: var(--color-ink); margin: 0 0 6px 0;">Belum Ada Riwayat Dokumen Opname</h3>
                <p style="font-size: 13px; color: var(--color-ink-mute); max-width: 460px; margin: 0 auto 20px auto; line-height: 1.5;">
                    Seluruh sesi penyesuaian fisik dan mutasi stok gudang yang Anda simpan akan diarsipkan secara otomatis dan rapi di sini.
                </p>
                <?php if (Auth::can('inventory.opname')): ?>
                <a href="<?= Router::url('/inventory/bulk-opname') ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; height: 38px; padding: 0 18px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Mulai Bulk Opname Baru</span>
                </a>
                <?php endif; ?>
            </div>
        </template>

        <!-- EMPTY STATE 2: Hasil Pencarian Nihil -->
        <template x-if="historyList.length > 0 && filteredHistory.length === 0">
            <div style="padding: 48px 24px; text-align: center;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: var(--color-canvas-soft); color: var(--color-ink-mute); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <h4 style="font-size: 14px; font-weight: 700; color: var(--color-ink); margin: 0 0 4px 0;">Tidak Ada Dokumen yang Cocok</h4>
                <p style="font-size: 12.5px; color: var(--color-ink-mute); margin: 0 0 14px 0;">
                    Tidak ditemukan dokumen opname dengan kriteria filter saat ini.
                </p>
                <button type="button" @click="resetFilters()" class="btn btn-secondary btn-sm" style="height: 32px;">
                    Reset Filter
                </button>
            </div>
        </template>

        <!-- DATA TABLE: Hanya Ditampilkan jika Ada Data -->
        <div class="table-scroll"
             x-show="filteredHistory.length > 0"
             style="overflow-anchor:none;">
            <table class="data-table" style="overflow-anchor:none;">
                <thead>
                    <tr>
                        <th style="width:45px; text-align:center;">No</th>
                        <th style="width:160px;">Nomor Dokumen</th>
                        <th style="width:105px;">Tanggal</th>
                        <th style="width:145px;">Petugas / PIC</th>
                        <th style="text-align:center; width:100px;">Item Berubah</th>
                        <th style="text-align:right; width:110px;">Stok Masuk (+)</th>
                        <th style="text-align:right; width:110px;">Stok Keluar (-)</th>
                        <th style="text-align:right; width:130px;">Valuasi Selisih</th>
                        <th style="min-width:150px;">Keterangan</th>
                        <th style="text-align:center; width:110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(doc, idx) in paginatedHistory" :key="doc.id">
                        <tr>
                            <td style="text-align:center; font-family:var(--font-mono); font-size:12px; color:var(--color-ink-mute);"
                                x-text="getStartRowIndex() + idx + 1"></td>
                            <td>
                                <a :href="'<?= Router::url('/inventory/opname/detail') ?>?id=' + encodeURIComponent(doc.id)"
                                   style="font-family:var(--font-mono); font-weight:700; color:var(--color-primary); text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/></svg>
                                    <span x-text="doc.nomor_dokumen"></span>
                                </a>
                            </td>
                            <td style="font-family:var(--font-mono); font-size:12.5px; color:var(--color-ink);" x-text="formatDate(doc.tanggal)"></td>
                            <td>
                                <div style="font-weight:600; font-size:13px; color:var(--color-ink);" x-text="doc.nama_pembuat || '—'"></div>
                                <div style="font-size:11px; font-family:var(--font-mono); color:var(--color-ink-mute); margin-top:2px;" x-text="formatDateTime(doc.dibuat_pada)"></div>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge badge-mono" style="font-weight:700;" x-text="doc.total_item_selisih + ' Item'"></span>
                            </td>
                            <td style="text-align:right; font-family:var(--font-mono); font-weight:700; color:#059669;"
                                x-text="'+' + formatQty(doc.total_qty_masuk) + ' pcs'"></td>
                            <td style="text-align:right; font-family:var(--font-mono); font-weight:700; color:#dc2626;"
                                x-text="'-' + formatQty(doc.total_qty_keluar) + ' pcs'"></td>
                            <td style="text-align:right; font-family:var(--font-mono); font-weight:700; font-size:12.5px;"
                                :style="parseFloat(doc.total_nilai_selisih_rp || 0) > 0 ? 'color:#059669;' : (parseFloat(doc.total_nilai_selisih_rp || 0) < 0 ? 'color:#dc2626;' : 'color:var(--color-ink-mute);')"
                                x-text="(parseFloat(doc.total_nilai_selisih_rp || 0) > 0 ? '+' : '') + formatRupiah(doc.total_nilai_selisih_rp || 0)"></td>
                            <td style="font-size:12px; color:var(--color-ink); word-break:break-word;" x-text="doc.catatan || '—'"></td>
                            <td style="text-align:center;">
                                <div style="display:flex; align-items:center; justify-content:center; gap:6px;">
                                    <a :href="'<?= Router::url('/inventory/opname/detail') ?>?id=' + encodeURIComponent(doc.id)"
                                       class="btn btn-secondary btn-sm" title="Lihat Rincian">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    <a :href="'<?= Router::url('/inventory/opname/pdf') ?>?id=' + encodeURIComponent(doc.id)" target="_blank"
                                       class="btn btn-ghost btn-sm" style="border:1px solid var(--color-hairline);" title="Cetak PDF">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                                    </a>
                                    <a :href="'<?= Router::url('/inventory/opname/excel') ?>?id=' + encodeURIComponent(doc.id)"
                                       class="btn btn-ghost btn-sm" style="border:1px solid var(--color-hairline); color:#059669;" title="Export Excel">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h2"/><path d="M8 17h2"/><path d="M14 13h2"/><path d="M14 17h2"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Filler Row: Ensures Table Height Never Changes Across Pages -->
                    <tr class="filler-row"
                        x-show="perPage !== 'all' && totalPages > 1 && paginatedHistory.length < parseInt(perPage)"
                        style="border:none; background:transparent; pointer-events:none;">
                        <td colspan="10" style="border:none; padding:0; background:transparent;">
                            <div :style="'height:' + ((parseInt(perPage) - paginatedHistory.length) * (avgRowHeight || 48)) + 'px; pointer-events:none;'"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ===================================================================== -->
        <!-- 5. PAGINATION BAR (Hanya Tampil Jika Data Ada dan Halaman > 1)        -->
        <!-- ===================================================================== -->
        <div x-ref="paginationBar" class="pagination-bar"
             style="padding:12px 18px; border-top:1px solid var(--color-hairline); background:var(--color-surface); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;"
             x-show="filteredHistory.length > 0 && totalPages > 1">
            <div style="font-size:12px; color:var(--color-ink-mute); font-family:var(--font-mono);">
                Menampilkan <span class="text-ink font-semibold" x-text="getStartRowIndex() + 1"></span> - 
                <span class="text-ink font-semibold" x-text="Math.min(getStartRowIndex() + (perPage === 'all' ? filteredHistory.length : parseInt(perPage)), filteredHistory.length)"></span> 
                dari <span class="text-ink font-semibold" x-text="filteredHistory.length"></span> dokumen
            </div>

            <div style="display:flex; align-items:center; gap:6px;">
                <button type="button" @click.prevent="prevPage($event)"
                        class="btn btn-secondary btn-sm" style="height:32px; padding:0 12px; transition:none !important; transform:none !important;"
                        :disabled="currentPage <= 1"
                        :style="currentPage <= 1 ? 'opacity:0.35; cursor:not-allowed;' : 'cursor:pointer;'">
                    &larr; Prev
                </button>

                <span style="font-size:12px; font-family:var(--font-mono); color:var(--color-ink-mute); padding:0 6px; user-select:none;">
                    Hal <strong class="text-ink" x-text="currentPage"></strong> / <span x-text="totalPages"></span>
                </span>

                <button type="button" @click.prevent="nextPage($event)"
                        class="btn btn-secondary btn-sm" style="height:32px; padding:0 12px; transition:none !important; transform:none !important;"
                        :disabled="currentPage >= totalPages"
                        :style="currentPage >= totalPages ? 'opacity:0.35; cursor:not-allowed;' : 'cursor:pointer;'">
                    Next &rarr;
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function opnameHistoryApp() {
    return {
        historyList: <?= json_encode($history) ?>,
        searchQuery: '',
        tanggalMulai: '<?= htmlspecialchars($_GET['tanggal_mulai'] ?? '') ?>',
        tanggalSelesai: '<?= htmlspecialchars($_GET['tanggal_selesai'] ?? '') ?>',
        perPage: '15',
        currentPage: 1,
        avgRowHeight: 48,

        get kpiTotalDocs() {
            return this.filteredHistory.length;
        },

        get kpiTotalItemSelisih() {
            return this.filteredHistory.reduce((acc, d) => acc + (parseInt(d.total_item_selisih) || 0), 0);
        },

        get kpiTotalMasuk() {
            return this.filteredHistory.reduce((acc, d) => acc + (parseFloat(d.total_qty_masuk) || 0), 0);
        },

        get kpiTotalKeluar() {
            return this.filteredHistory.reduce((acc, d) => acc + (parseFloat(d.total_qty_keluar) || 0), 0);
        },

        get kpiTotalNilaiSelisihRp() {
            return this.filteredHistory.reduce((acc, d) => acc + (parseFloat(d.total_nilai_selisih_rp) || 0), 0);
        },

        updateRowHeight() {
            const row = this.$refs.tableWrapper?.querySelector('tbody tr:not(.filler-row)');
            if (row && row.offsetHeight > 0) {
                this.avgRowHeight = row.offsetHeight;
            }
        },

        init() {
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
                this.updateRowHeight();
            });
            setTimeout(() => this.updateRowHeight(), 100);
            this.$watch('searchQuery', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$watch('perPage', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
        },

        resetFilters() {
            this.searchQuery = '';
            this.tanggalMulai = '';
            this.tanggalSelesai = '';
            this.currentPage = 1;
        },

        get filteredHistory() {
            let res = this.historyList;

            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase();
                res = res.filter(d => {
                    return (d.nomor_dokumen && d.nomor_dokumen.toLowerCase().includes(q)) ||
                           (d.nama_pembuat && d.nama_pembuat.toLowerCase().includes(q)) ||
                           (d.catatan && d.catatan.toLowerCase().includes(q));
                });
            }

            if (this.tanggalMulai) {
                res = res.filter(d => d.tanggal >= this.tanggalMulai);
            }

            if (this.tanggalSelesai) {
                res = res.filter(d => d.tanggal <= this.tanggalSelesai);
            }

            return res;
        },

        get totalPages() {
            if (this.perPage === 'all') return 1;
            const size = parseInt(this.perPage) || 15;
            return Math.max(1, Math.ceil(this.filteredHistory.length / size));
        },

        get paginatedHistory() {
            if (this.perPage === 'all') return this.filteredHistory;
            const size = parseInt(this.perPage) || 15;
            const start = (this.currentPage - 1) * size;
            return this.filteredHistory.slice(start, start + size);
        },

        getStartRowIndex() {
            if (this.perPage === 'all') return 0;
            return (this.currentPage - 1) * parseInt(this.perPage);
        },

        goToPage(targetPage) {
            if (targetPage < 1 || targetPage > this.totalPages || targetPage === this.currentPage) return;

            const paginationEl = this.$refs.paginationBar;
            const tableEl = this.$refs.tableWrapper;

            // Pre-emptively lock current height so DOM cannot collapse during Alpine render
            if (tableEl && tableEl.offsetHeight > 0) {
                tableEl.style.minHeight = tableEl.offsetHeight + 'px';
            }

            const paginationTopBefore = paginationEl ? paginationEl.getBoundingClientRect().top : null;

            this.currentPage = targetPage;

            this.$nextTick(() => {
                // Instantly clear the temporary minHeight lock after Alpine finishes rendering the new page
                if (tableEl) {
                    tableEl.style.minHeight = '';
                }
                this.updateRowHeight();

                if (paginationTopBefore !== null && paginationEl) {
                    const paginationTopAfter = paginationEl.getBoundingClientRect().top;
                    const delta = paginationTopAfter - paginationTopBefore;

                    // Pixel-perfect compensation: neutralize any shift >= 1px
                    if (Math.abs(delta) >= 1) {
                        const scrollContainer = document.querySelector('.app-content');
                        if (scrollContainer && (scrollContainer.scrollHeight > scrollContainer.clientHeight)) {
                            scrollContainer.scrollBy({ top: delta, behavior: 'instant' });
                        } else {
                            window.scrollBy({ top: delta, behavior: 'instant' });
                        }
                    }
                }
            });
        },

        prevPage(evt) {
            if (this.currentPage <= 1) return;
            if (evt && evt.currentTarget) evt.currentTarget.blur();
            this.goToPage(this.currentPage - 1);
        },

        nextPage(evt) {
            if (this.currentPage >= this.totalPages) return;
            if (evt && evt.currentTarget) evt.currentTarget.blur();
            this.goToPage(this.currentPage + 1);
        },

        formatQty(val) {
            const num = parseFloat(val);
            if (isNaN(num)) return '0';
            return num % 1 === 0 ? num.toString() : num.toFixed(2);
        },

        formatRupiah(val) {
            const num = parseFloat(val);
            if (isNaN(num)) return 'Rp 0';
            const formatted = Math.abs(num).toLocaleString('id-ID', { maximumFractionDigits: 0 });
            return (num < 0 ? '-Rp ' : 'Rp ') + formatted;
        },

        formatDate(dtStr) {
            if (!dtStr) return '—';
            const d = new Date(dtStr);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        formatDateTime(dtStr) {
            if (!dtStr) return '—';
            const d = new Date(dtStr);
            return d.toLocaleDateString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

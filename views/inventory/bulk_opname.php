<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="bulkOpnameApp()" class="space-y-5">
    <style>
        /* Hilangkan spinner number bawaan browser & cegah gangguan scroll mouse */
        input[data-col="physical"]::-webkit-outer-spin-button,
        input[data-col="physical"]::-webkit-inner-spin-button,
        input[data-col="diff"]::-webkit-outer-spin-button,
        input[data-col="diff"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none !important;
            margin: 0 !important;
        }
        input[data-col="physical"],
        input[data-col="diff"],
        input[type="number"] {
            -moz-appearance: textfield !important;
        }
    </style>

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER                                                            -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-emerald" style="flex-shrink:0;">
                <i data-lucide="layers"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#10b981;"></span>
                    <span>Modul Inventaris &amp; Gudang</span>
                </div>
                <h1 class="page-title" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= $pageTitle ?? 'Bulk Opname Stok Gudang' ?>
                </h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Penyesuaian Massal Stok Fisik &amp; Audit Mutasi Buku Besar Gudang' ?></p>
            </div>
        </div>
        <div class="page-header-actions" style="display:flex; gap:8px; align-items:center; flex-shrink:0;">
            <a href="<?= Router::url('/inventory') ?>" class="btn btn-secondary" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="arrow-left" style="width:15px; height:15px;"></i>
                <span>Kembali ke Stok</span>
            </a>
            <a href="<?= Router::url('/inventory/opname/history') ?>" class="btn btn-secondary" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="history" style="width:15px; height:15px;"></i>
                <span>Riwayat Dokumen</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 1.5 DRAFT RECOVERY BANNER (Auto-Saved Session)                            -->
    <!-- ========================================================================= -->
    <template x-if="hasDraft && !draftDismissed">
        <div class="card" style="padding:14px 18px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:14px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:36px; height:36px; border-radius:10px; background:#dbeafe; display:flex; align-items:center; justify-content:center; color:#2563eb; flex-shrink:0;">
                    <i data-lucide="history" style="width:20px; height:20px;"></i>
                </div>
                <div>
                    <div style="font-size:13.5px; font-weight:700; color:#1e40af;">
                        Ditemukan Draf Opname Tersimpan Otomatis
                    </div>
                    <div style="font-size:12px; color:#3b82f6; margin-top:2px;">
                        Tersimpan pada <strong x-text="draftSavedAt"></strong> berisi <strong x-text="draftItemCount + ' item'"></strong> yang telah disesuaikan. Apakah ingin memulihkan draf ini?
                    </div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <button type="button" @click="restoreDraft()" class="btn btn-primary btn-sm" style="background:#2563eb; border-color:#2563eb; height:34px; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                    <i data-lucide="rotate-ccw" style="width:14px; height:14px;"></i>
                    <span>Pulihkan Draf</span>
                </button>
                <button type="button" @click="confirmClearDraft()" class="btn btn-ghost btn-sm" style="color:#64748b; height:34px;">
                    Buang Draf
                </button>
            </div>
        </div>
    </template>

    <!-- ========================================================================= -->
    <!-- 2. METADATA DOKUMEN & PENGATURAN SESI                                     -->
    <!-- ========================================================================= -->
    <div class="card" style="padding:16px 20px; background:var(--color-surface); border:1px solid var(--color-hairline); border-radius:16px;">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:14px; align-items:flex-end;">
            <!-- Tanggal Opname -->
            <div style="max-width:220px;">
                <label class="form-label font-semibold" style="font-size:12px; margin-bottom:4px;">Tanggal Opname *</label>
                <input type="date" x-model="formTanggal" @input="scheduleAutoSave()" class="form-input" style="height:38px; font-weight:700;">
            </div>

            <!-- Catatan Umum Dokumen -->
            <div style="flex:1;">
                <label class="form-label font-semibold" style="font-size:12px; margin-bottom:4px;">Keterangan / Catatan Sesi Opname *</label>
                <input type="text" x-model="formCatatan" @input="scheduleAutoSave()" placeholder="Contoh: Opname Fisik Rutin Akhir Bulan Gudang Pusat" class="form-input" style="height:38px;">
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. SUMMARY METRIC CARDS & ACTIONS (Clean Flow - No Sticky Gap)            -->
    <!-- ========================================================================= -->
    <div class="card" style="padding:14px 20px; background:var(--color-surface); border:1px solid var(--color-hairline); border-radius:16px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px;">
        <div style="display:flex; align-items:center; gap:18px; flex-wrap:wrap;">
            <div>
                <span style="font-size:10.5px; text-transform:uppercase; color:var(--color-ink-mute); font-weight:700; letter-spacing:0.04em;">Total Produk</span>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); color:var(--color-ink);" x-text="items.length + ' SKU'"></div>
            </div>

            <div style="height:24px; width:1px; background:var(--color-hairline);"></div>

            <div>
                <span style="font-size:10.5px; text-transform:uppercase; color:var(--color-ink-mute); font-weight:700; letter-spacing:0.04em;">Item Disesuaikan</span>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-mono);"
                     :style="totalModified > 0 ? 'color:var(--color-primary);' : 'color:var(--color-ink-mute);'"
                     x-text="totalModified + ' Item'"></div>
            </div>

            <div style="height:24px; width:1px; background:var(--color-hairline);"></div>

            <div>
                <span style="font-size:10.5px; text-transform:uppercase; color:#047857; font-weight:700; letter-spacing:0.04em;">Stok Masuk (+)</span>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); color:#059669;" x-text="'+' + formatQty(totalQtyIn) + ' pcs'"></div>
            </div>

            <div style="height:24px; width:1px; background:var(--color-hairline);"></div>

            <div>
                <span style="font-size:10.5px; text-transform:uppercase; color:#b91c1c; font-weight:700; letter-spacing:0.04em;">Stok Keluar (-)</span>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-mono); color:#dc2626;" x-text="'-' + formatQty(totalQtyOut) + ' pcs'"></div>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:10px;">
            <button type="button" @click="resetAllRows()" x-show="totalModified > 0" class="btn btn-ghost btn-sm" style="color:#dc2626; border:1px solid #fecaca; height:38px;">
                <i data-lucide="rotate-ccw" style="width:14px; height:14px;"></i>
                <span>Reset (<span x-text="totalModified"></span>)</span>
            </button>

            <button type="button" @click="openConfirmModal()"
                    :style="totalModified === 0 ? 'opacity:0.5; cursor:not-allowed;' : ''"
                    class="btn btn-primary" style="height:38px; padding:0 18px; font-weight:700; display:inline-flex; align-items:center; gap:8px;">
                <i data-lucide="check-circle-2" style="width:16px; height:16px;"></i>
                <span>Review &amp; Simpan (<span x-text="totalModified"></span>)</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. TOOLBAR FILTER & SEARCH                                                -->
    <!-- ========================================================================= -->
    <div class="card" style="padding:12px 18px; border-radius:14px; border:1px solid var(--color-hairline); background:var(--color-surface); display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <!-- Live Search -->
        <div class="form-input-icon" style="flex:1; min-width:240px;">
            <i data-lucide="search" class="icon-left"></i>
            <input type="text" x-model="searchQuery" @input="currentPage = 1"
                   placeholder="Cari SKU, Nama Produk, atau Barcode..." class="form-input" style="height:38px;">
        </div>

        <!-- Filter Grup Kemasan -->
        <div style="min-width:180px;">
            <select x-model="selectedGroup" @change="currentPage = 1" class="form-select" style="height:38px; font-size:13px;">
                <option value="">Semua Grup Kemasan</option>
                <template x-for="g in groups" :key="g.id">
                    <option :value="g.kode_grup" x-text="g.kode_grup + ' - ' + g.nama_grup"></option>
                </template>
            </select>
        </div>

        <!-- Filter Status Perubahan -->
        <div style="min-width:180px;">
            <select x-model="filterStatus" @change="currentPage = 1" class="form-select" style="height:38px; font-size:13px;">
                <option value="all">Semua Status</option>
                <option value="changed">Hanya yang Diubah (Selisih)</option>
                <option value="unmodified">Belum Dihitung / Belum Diperiksa</option>
                <option value="in">Hanya Stok Masuk (+)</option>
                <option value="out">Hanya Stok Keluar (-)</option>
            </select>
        </div>

        <!-- Per Page Selector -->
        <div style="display:flex; align-items:center; gap:6px;">
            <span style="font-size:12px; color:var(--color-ink-mute); white-space:nowrap;">Baris:</span>
            <select x-model="perPage" @change="currentPage = 1" class="form-select" style="height:38px; width:80px; font-size:12.5px; font-family:var(--font-mono);">
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="all">Semua</option>
            </select>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. TABEL FORM BULK OPNAME                                                 -->
    <!-- ========================================================================= -->
    <div class="table-wrapper" x-ref="tableWrapper"
         style="overflow-anchor:none;">
        <div class="table-scroll"
             style="overflow-anchor:none;">
            <table class="data-table" style="overflow-anchor:none;">
                <thead>
                    <tr>
                        <th style="width:45px; text-align:center;">No</th>
                        <th style="min-width:240px;">Produk &amp; SKU</th>
                        <th class="hide-sm" style="min-width:110px;">Barcode</th>
                        <th style="width:120px; text-align:right;">Stok Sistem</th>
                        <th style="width:150px; text-align:center; background:rgba(99,102,241,0.04);">
                            Stok Fisik Realita
                            <span style="display:block; font-size:10px; font-weight:normal; color:var(--color-ink-mute);">(Hasil Hitung)</span>
                        </th>
                        <th style="width:140px; text-align:center; background:rgba(99,102,241,0.04);">
                            Penyesuaian (+/-)
                            <span style="display:block; font-size:10px; font-weight:normal; color:var(--color-ink-mute);">(Selisih)</span>
                        </th>
                        <th style="width:110px; text-align:center;">Mutasi</th>
                        <th style="min-width:180px;">Catatan Baris (Opsional)</th>
                        <th style="width:80px; text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="paginatedItems.length === 0">
                        <tr>
                            <td colspan="9" style="text-align:center; padding:48px 16px; color:var(--color-ink-mute); font-size:13px;">
                                Tidak ada produk yang sesuai dengan filter atau pencarian.
                            </td>
                        </tr>
                    </template>

                    <template x-for="(it, idx) in paginatedItems" :key="it.id">
                        <tr :style="it.is_modified ? 'background:rgba(99, 102, 241, 0.05); border-left:3px solid var(--color-primary);' : ''">
                            <!-- No -->
                            <td style="text-align:center; font-family:var(--font-mono); font-size:12px; color:var(--color-ink-mute);"
                                x-text="getStartRowIndex() + idx + 1"></td>

                            <!-- Produk & SKU -->
                            <td>
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    <span class="badge badge-mono" x-text="it.kode_sku"></span>
                                    <span style="font-size:13px; font-weight:700; color:var(--color-ink);" x-text="it.nama_item"></span>
                                </div>
                                <div x-show="it.kode_grup || it.nama_grup"
                                     style="font-size:11px; font-family:var(--font-mono); color:var(--color-ink-mute); margin-top:2px;"
                                     x-text="(it.kode_grup ? it.kode_grup + (it.nama_grup ? ' • ' + it.nama_grup : '') : (it.nama_grup || ''))"></div>
                            </td>

                            <!-- Barcode -->
                            <td class="hide-sm">
                                <span style="font-family:var(--font-mono); font-size:11px; padding:2px 6px; border:1px solid var(--color-hairline); border-radius:4px; background:var(--color-canvas-soft); color:var(--color-ink-mute);"
                                      x-text="formatBarcode(it.barcode || it.barcode_universal)"></span>
                            </td>

                            <!-- Stok Sistem -->
                            <td style="text-align:right;">
                                <div style="font-family:var(--font-mono); font-weight:700; font-size:13.5px; color:var(--color-ink);"
                                     x-text="formatQty(it.stok_sistem) + ' ' + it.satuan_dasar"></div>
                            </td>

                            <!-- Input 1: Stok Fisik Realita -->
                            <td style="text-align:center; background:rgba(99,102,241,0.02);">
                                <input type="text" inputmode="decimal"
                                       data-col="physical"
                                       :value="it.stok_fisik_val !== null ? it.stok_fisik_val : ''"
                                       :placeholder="formatQty(it.stok_sistem)"
                                       @focus="$event.target.select()"
                                       @click="$event.target.select()"
                                       @wheel="$event.target.blur()"
                                       @keydown="onPhysicalKeydown($event)"
                                       @keydown.enter.prevent="focusNextRow($event, 'physical')"
                                       @input="onPhysicalInput(it, $event.target)"
                                       @blur="onPhysicalBlur(it, $event.target)"
                                       class="form-input font-mono"
                                       style="height:36px; text-align:right; font-weight:700; font-size:13.5px;"
                                       :style="it.is_modified ? 'border-color:var(--color-primary); background:#ffffff;' : ''">
                            </td>

                            <!-- Input 2: Penyesuaian (+/-) -->
                            <td style="text-align:center; background:rgba(99,102,241,0.02);">
                                <input type="text" inputmode="decimal"
                                       data-col="diff"
                                       :value="it.selisih_input_val || ''"
                                       placeholder="0"
                                       @focus="$event.target.select()"
                                       @click="$event.target.select()"
                                       @wheel="$event.target.blur()"
                                       @keydown="onDiffKeydown($event)"
                                       @keydown.enter.prevent="focusNextRow($event, 'diff')"
                                       @input="onDiffInput(it, $event.target)"
                                       @blur="onDiffBlur(it, $event.target)"
                                       class="form-input font-mono"
                                       style="height:36px; text-align:right; font-weight:700; font-size:13.5px;"
                                       :style="it.selisih_val > 0 ? 'color:#059669; border-color:#10b981; background:#ffffff;' : (it.selisih_val < 0 ? 'color:#dc2626; border-color:#ef4444; background:#ffffff;' : '')">
                            </td>

                            <!-- Status Mutasi -->
                            <td style="text-align:center;">
                                <span x-show="!it.is_modified" class="badge badge-muted" style="font-size:11px;">Tetap</span>
                                <span x-show="it.is_modified && it.selisih_val > 0" class="badge badge-success" style="font-size:11px; font-weight:700;">
                                    + Masuk
                                </span>
                                <span x-show="it.is_modified && it.selisih_val < 0" class="badge badge-danger" style="font-size:11px; font-weight:700;">
                                    - Keluar
                                </span>
                            </td>

                            <!-- Catatan Khusus Item -->
                            <td>
                                <input type="text" x-model="it.catatan_item"
                                       @input="scheduleAutoSave()"
                                       placeholder="Catatan khusus baris ini..."
                                       class="form-input" style="height:34px; font-size:12px;">
                            </td>

                            <!-- Aksi -->
                            <td style="text-align:center;">
                                <div style="display:flex; align-items:center; justify-content:center; gap:4px;">
                                    <!-- Intip Mutasi -->
                                    <button type="button" @click="openHistory(it)"
                                            class="btn btn-ghost btn-sm" style="padding:4px 6px; border:1px solid var(--color-hairline);"
                                            title="Intip Riwayat Kartu Stok">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-primary); display:inline-block;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                                    </button>

                                    <!-- Reset Row -->
                                    <button type="button" @click="resetRow(it)"
                                            x-show="it.is_modified"
                                            class="btn btn-ghost btn-sm" style="padding:4px 6px; color:#dc2626;"
                                            title="Reset Baris Ini">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Filler Row: always in DOM, height driven reactively by fillerHeight getter -->
                    <tr class="filler-row" aria-hidden="true"
                        style="border:none; background:transparent; pointer-events:none; visibility:hidden;">
                        <td colspan="9" style="border:none; padding:0; background:transparent;">
                            <div :style="'height:' + fillerHeight + 'px;'"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div x-ref="paginationBar" class="pagination-bar"
             style="padding:12px 18px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; border-top:1px solid var(--color-hairline); background:var(--color-surface);"
             x-show="perPage !== 'all' && totalPages > 1">
            <div style="font-size:12px; color:var(--color-ink-mute); font-family:var(--font-mono);">
                Menampilkan <strong class="text-ink" x-text="getStartRowIndex() + 1"></strong> -
                <strong class="text-ink" x-text="Math.min(getStartRowIndex() + parseInt(perPage), filteredItems.length)"></strong>
                dari <strong class="text-ink" x-text="filteredItems.length"></strong> produk
            </div>

            <div style="display:flex; align-items:center; gap:6px;">
                <button type="button" @click.prevent="prevPage($event)"
                        class="btn btn-secondary btn-sm"
                        :disabled="currentPage <= 1"
                        :style="currentPage <= 1 ? 'opacity:0.35; cursor:not-allowed;' : 'cursor:pointer;'"
                        style="padding:4px 12px; height:32px; transition:none !important; transform:none !important;">
                    &larr; Prev
                </button>
                <span style="font-size:12px; font-family:var(--font-mono); padding:0 8px; color:var(--color-ink); user-select:none;">
                    Hal <strong x-text="currentPage"></strong> / <span x-text="totalPages"></span>
                </span>
                <button type="button" @click.prevent="nextPage($event)"
                        class="btn btn-secondary btn-sm"
                        :disabled="currentPage >= totalPages"
                        :style="currentPage >= totalPages ? 'opacity:0.35; cursor:not-allowed;' : 'cursor:pointer;'"
                        style="padding:4px 12px; height:32px; transition:none !important; transform:none !important;">
                    Next &rarr;
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: INTIP RIWAYAT MUTASI (KARTU STOK MINI)                           -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showHistoryModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:760px; width:95%;">
            <div class="modal-header">
                <div>
                    <div class="modal-title" style="display:flex; align-items:center; gap:8px;">
                        <i data-lucide="activity" style="color:var(--color-primary); width:18px; height:18px;"></i>
                        <span>Kartu Stok &amp; Riwayat Mutasi Terakhir</span>
                    </div>
                    <div style="font-size:12px; font-family:var(--font-mono); color:var(--color-primary); margin-top:2px;"
                         x-text="(selectedItem.kode_sku || '') + ' — ' + (selectedItem.nama_item || '')"></div>
                </div>
            </div>

            <div x-show="historyLoading" style="padding:36px; text-align:center; color:var(--color-ink-mute);">
                <i data-lucide="loader" class="spin" style="width:24px; height:24px; margin:0 auto 8px auto;"></i>
                <div>Memuat riwayat pergerakan stok...</div>
            </div>

            <div x-show="!historyLoading" class="space-y-4" style="padding:16px 20px 20px 20px;">
                <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:var(--color-canvas-soft); border-radius:12px; border:1px solid var(--color-hairline);">
                    <div>
                        <div style="font-size:11px; color:var(--color-ink-mute); text-transform:uppercase; letter-spacing:0.04em;">Stok Fisik Saat Ini (Sistem)</div>
                        <div style="font-size:18px; font-weight:800; font-family:var(--font-mono); color:var(--color-ink);"
                             x-text="formatQty(selectedItem.stok_sistem) + ' ' + (selectedItem.satuan_dasar || 'pcs')"></div>
                    </div>
                    <div style="text-align:right;">
                        <span class="badge badge-mono" x-text="formatBarcode(selectedItem.barcode || selectedItem.barcode_universal) || 'No Barcode'"></span>
                    </div>
                </div>

                <div class="table-wrapper" style="max-height:360px; overflow-y:auto; border-radius:10px;">
                    <table class="data-table" style="font-size:12px;">
                        <thead>
                            <tr>
                                <th>Waktu &amp; Tanggal</th>
                                <th>Tipe Mutasi</th>
                                <th style="text-align:right;">Perubahan</th>
                                <th style="text-align:right;">Stok Akhir</th>
                                <th>Keterangan / Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="itemHistoryList.length === 0">
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:24px; color:var(--color-ink-mute);">
                                        Belum ada riwayat mutasi tercatat untuk produk ini.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="log in itemHistoryList" :key="log.id">
                                <tr>
                                    <td style="font-family:var(--font-mono); font-size:11px; color:var(--color-ink-mute);" x-text="formatDate(log.dibuat_pada)"></td>
                                    <td>
                                        <span class="badge" :class="getMutationBadgeClass(log.tipe_mutasi)" x-text="formatMutationType(log.tipe_mutasi)"></span>
                                    </td>
                                    <td style="text-align:right; font-family:var(--font-mono); font-weight:700;"
                                        :style="isStockIn(log.tipe_mutasi) ? 'color:#059669;' : 'color:#dc2626;'"
                                        x-text="(isStockIn(log.tipe_mutasi) ? '+' : '-') + formatQty(log.jumlah_perubahan) + ' pcs'"></td>
                                    <td style="text-align:right; font-family:var(--font-mono); color:var(--color-ink);" x-text="formatQty(log.stok_sesudah) + ' pcs'"></td>
                                    <td>
                                        <div style="font-size:11.5px; color:var(--color-ink);" x-text="log.keterangan || '—'"></div>
                                        <div x-show="log.nama_user" style="font-size:10px; color:var(--color-ink-mute); font-style:italic;" x-text="'Oleh: ' + log.nama_user"></div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div style="text-align:right; padding-top:4px;">
                    <button type="button" @click="showHistoryModal = false" class="btn btn-secondary">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL 2: KONFIRMASI RINGKASAN MUTASI SEBELUM SIMPAN                       -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showConfirmModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:820px; width:95%;">
            <div class="modal-header">
                <div>
                    <div class="modal-title" style="display:flex; align-items:center; gap:8px;">
                        <i data-lucide="clipboard-check" style="color:var(--color-primary); width:20px; height:20px;"></i>
                        <span>Konfirmasi Penyesuaian Mutasi Stok</span>
                    </div>
                    <div style="font-size:12px; color:var(--color-ink-mute); margin-top:2px;">
                        Periksa kembali ringkasan mutasi sebelum dicatat permanen ke buku besar gudang.
                    </div>
                </div>
                <button type="button" @click="showConfirmModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px; height:16px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/inventory/bulk-opname/store') ?>" method="POST"
                  @submit="onFormSubmit($event)"
                  data-action-text="Memproses dan menyimpan transaksi bulk opname..."
                  style="display:flex; flex-direction:column; gap:16px; padding:18px 22px 22px 22px;">
                
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="tanggal" :value="formTanggal">
                <input type="hidden" name="catatan" :value="formCatatan">
                <input type="hidden" name="total_katalog" :value="items.length">
                <input type="hidden" name="items_json" :value="JSON.stringify(getChangedItemsPayload())">

                <!-- Ringkasan Metrik Dokumen -->
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px;">
                    <div style="background:var(--color-canvas-soft); padding:12px 14px; border-radius:12px; border:1px solid var(--color-hairline);">
                        <span style="font-size:11px; text-transform:uppercase; color:var(--color-ink-mute); font-weight:700;">Tanggal Opname</span>
                        <div style="font-size:14px; font-weight:700; font-family:var(--font-mono); color:var(--color-ink); margin-top:2px;" x-text="formTanggal"></div>
                    </div>
                    <div style="background:var(--color-canvas-soft); padding:12px 14px; border-radius:12px; border:1px solid var(--color-hairline);">
                        <span style="font-size:11px; text-transform:uppercase; color:var(--color-ink-mute); font-weight:700;">Item Disesuaikan</span>
                        <div style="font-size:14px; font-weight:800; font-family:var(--font-mono); color:var(--color-primary); margin-top:2px;" x-text="totalModified + ' Produk'"></div>
                    </div>
                    <div style="background:#ecfdf5; padding:12px 14px; border-radius:12px; border:1px solid #a7f3d0;">
                        <span style="font-size:11px; text-transform:uppercase; color:#047857; font-weight:700;">Total Stok Masuk</span>
                        <div style="font-size:14px; font-weight:800; font-family:var(--font-mono); color:#059669; margin-top:2px;" x-text="'+' + formatQty(totalQtyIn) + ' pcs'"></div>
                    </div>
                    <div style="background:#fef2f2; padding:12px 14px; border-radius:12px; border:1px solid #fecaca;">
                        <span style="font-size:11px; text-transform:uppercase; color:#b91c1c; font-weight:700;">Total Stok Keluar</span>
                        <div style="font-size:14px; font-weight:800; font-family:var(--font-mono); color:#dc2626; margin-top:2px;" x-text="'-' + formatQty(totalQtyOut) + ' pcs'"></div>
                    </div>
                </div>

                <!-- Catatan Umum -->
                <div style="background:var(--color-canvas-soft); padding:10px 14px; border-radius:10px; font-size:12.5px; color:var(--color-ink);">
                    <strong style="color:var(--color-ink-mute);">Keterangan Dokumen:</strong>
                    <span x-text="formCatatan || '—'"></span>
                </div>

                <!-- Tabel Rincian Perubahan -->
                <div class="table-wrapper" style="max-height:300px; overflow-y:auto; border-radius:10px;">
                    <table class="data-table" style="font-size:12px;">
                        <thead>
                            <tr>
                                <th>Produk &amp; SKU</th>
                                <th style="text-align:right;">Stok Lama</th>
                                <th style="text-align:right;">Stok Baru</th>
                                <th style="text-align:right;">Mutasi (+/-)</th>
                                <th>Catatan Item</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in getChangedItemsPayload()" :key="row.item_id">
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:var(--color-ink);" x-text="row.nama_item"></div>
                                        <div style="font-size:11px; font-family:var(--font-mono); color:var(--color-ink-mute);" x-text="row.kode_sku"></div>
                                    </td>
                                    <td style="text-align:right; font-family:var(--font-mono);" x-text="formatQty(row.stok_sistem) + ' pcs'"></td>
                                    <td style="text-align:right; font-family:var(--font-mono); font-weight:700;" x-text="formatQty(row.stok_fisik) + ' pcs'"></td>
                                    <td style="text-align:right; font-family:var(--font-mono); font-weight:800;"
                                        :style="row.selisih > 0 ? 'color:#059669;' : 'color:#dc2626;'"
                                        x-text="(row.selisih > 0 ? '+' : '') + formatQty(row.selisih) + ' pcs'"></td>
                                    <td style="font-size:11.5px; color:var(--color-ink-mute);" x-text="row.catatan_item || '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Action Buttons -->
                <div style="display:flex; gap:10px; padding-top:6px;">
                    <button type="button" @click="showConfirmModal = false" :disabled="isSubmitting" class="btn btn-secondary" style="flex:1; justify-content:center;">
                        Periksa Kembali
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="isSubmitting"
                            :style="isSubmitting ? 'opacity:0.6; cursor:not-allowed;' : ''"
                            style="flex:1.5; justify-content:center; font-weight:700; display:inline-flex; align-items:center; gap:8px;">
                        <i data-lucide="check" x-show="!isSubmitting"></i>
                        <span x-text="isSubmitting ? 'Memproses Transaksi...' : 'Konfirmasi & Simpan Permanen'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

</div>

<script>
function bulkOpnameApp() {
    const rawItems = <?= json_encode($items) ?>;
    const groups = <?= json_encode($groups) ?>;

    const initializedItems = rawItems.map(it => {
        const sysStock = parseFloat(it.stok_fisik_saat_ini) || 0;
        return {
            id: it.id,
            kode_sku: it.kode_sku,
            barcode: it.barcode,
            barcode_universal: it.barcode_universal,
            nama_item: it.nama_item,
            varian_rasa: it.varian_rasa,
            nama_grup: it.nama_grup,
            kode_grup: it.kode_grup,
            satuan_dasar: it.satuan_dasar || 'pcs',
            stok_sistem: sysStock,
            stok_fisik_val: null,
            selisih_val: 0,
            selisih_input_val: '',
            is_modified: false,
            catatan_item: ''
        };
    });

    return {
        items: initializedItems,
        groups: groups,
        formTanggal: '<?= date('Y-m-d') ?>',
        formCatatan: 'Bulk Opname Stok Fisik Gudang Pusat',
        searchQuery: '',
        selectedGroup: '',
        filterStatus: 'all',
        perPage: '50',
        currentPage: 1,
        avgRowHeight: 44,
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',

        // Draft & Resilience State
        STORAGE_KEY: 'kerensnack_bulk_opname_draft_v1',
        hasDraft: false,
        draftDismissed: false,
        draftSavedAt: '',
        draftItemCount: 0,
        isSubmitting: false,
        autoSaveTimeout: null,

        // Modals
        showHistoryModal: false,
        showConfirmModal: false,
        historyLoading: false,
        itemHistoryList: [],
        selectedItem: {},

        // ── Scroll Helper ─────────────────────────────────────────────────────
        // Returns the real scroll container: on desktop (≥1024px) it is
        // `main.app-content` (overflow-y:auto, fixed height); on mobile it is
        // `window` (body scrolls).
        _getScrollContainer() {
            const el = document.querySelector('main.app-content') || document.querySelector('.app-content');
            if (el && (el.scrollHeight > el.clientHeight)) {
                return el;
            }
            return window;
        },

        _getScrollTop(el) {
            return el === window ? window.scrollY : el.scrollTop;
        },

        _setScrollTop(el, top) {
            if (el === window) {
                window.scrollTo({ top, behavior: 'instant' });
            } else {
                el.scrollTop = top;
            }
        },

        // ── Row Height ────────────────────────────────────────────────────────
        // Measures the median height of all real rows (excluding filler) for an
        // accurate fillerHeight calculation even when rows have varied content.
        updateRowHeight() {
            const rows = Array.from(
                this.$refs.tableWrapper?.querySelectorAll('tbody tr:not(.filler-row)') || []
            ).filter(r => r.offsetHeight > 0);

            if (rows.length === 0) return;

            const heights = rows.map(r => r.offsetHeight).sort((a, b) => a - b);
            const mid = Math.floor(heights.length / 2);
            this.avgRowHeight = heights.length % 2 !== 0
                ? heights[mid]
                : Math.round((heights[mid - 1] + heights[mid]) / 2);
        },

        init() {
            // Check for previous session auto-saved draft
            try {
                const draftRaw = localStorage.getItem(this.STORAGE_KEY);
                if (draftRaw) {
                    const draft = JSON.parse(draftRaw);
                    const count = Object.keys(draft.changes || {}).length;
                    if (count > 0) {
                        this.hasDraft = true;
                        this.draftSavedAt = draft.saved_at || 'Sesi Sebelumnya';
                        this.draftItemCount = count;
                    }
                }
            } catch (e) {
                console.warn("Gagal membaca draft opname:", e);
            }

            // Guard against accidental navigation / tab closure
            window.addEventListener('beforeunload', (e) => {
                if (this.totalModified > 0 && !this.isSubmitting) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
                this.updateRowHeight();
            });
            // Second pass after fonts/icons settle
            setTimeout(() => this.updateRowHeight(), 300);

            this.$watch('searchQuery', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$watch('selectedGroup', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$watch('filterStatus', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.$watch('perPage', () => {
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                    this.updateRowHeight();
                });
            });
        },

        get filteredItems() {
            let res = this.items;

            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase();
                res = res.filter(it =>
                    (it.nama_item && it.nama_item.toLowerCase().includes(q)) ||
                    (it.kode_sku && it.kode_sku.toLowerCase().includes(q)) ||
                    (it.barcode && it.barcode.toLowerCase().includes(q)) ||
                    (it.barcode_universal && it.barcode_universal.toLowerCase().includes(q)) ||
                    (it.nama_grup && it.nama_grup.toLowerCase().includes(q))
                );
            }

            if (this.selectedGroup) {
                res = res.filter(it => it.kode_grup === this.selectedGroup);
            }

            if (this.filterStatus === 'changed') {
                res = res.filter(it => it.is_modified);
            } else if (this.filterStatus === 'unmodified') {
                res = res.filter(it => !it.is_modified);
            } else if (this.filterStatus === 'in') {
                res = res.filter(it => it.is_modified && it.selisih_val > 0);
            } else if (this.filterStatus === 'out') {
                res = res.filter(it => it.is_modified && it.selisih_val < 0);
            }

            return res;
        },

        get totalPages() {
            if (this.perPage === 'all') return 1;
            const size = parseInt(this.perPage) || 50;
            return Math.max(1, Math.ceil(this.filteredItems.length / size));
        },

        get paginatedItems() {
            if (this.perPage === 'all') return this.filteredItems;
            const size = parseInt(this.perPage) || 50;
            const start = (this.currentPage - 1) * size;
            return this.filteredItems.slice(start, start + size);
        },

        getStartRowIndex() {
            if (this.perPage === 'all') return 0;
            return (this.currentPage - 1) * parseInt(this.perPage);
        },

        // Reactive computed filler height — pads tbody to always equal `perPage`
        // rows. Because fillerHeight is a getter, Alpine recomputes it in the
        // same synchronous batch as paginatedItems when currentPage changes,
        // so there is never a frame where the table is shorter than expected.
        get fillerHeight() {
            if (this.perPage === 'all' || this.totalPages <= 1) return 0;
            const size = parseInt(this.perPage) || 50;
            const missing = size - this.paginatedItems.length;
            if (missing <= 0) return 0;
            return missing * (this.avgRowHeight || 44);
        },

        // ── Page Navigation ───────────────────────────────────────────────────
        goToPage(targetPage) {
            if (targetPage < 1 || targetPage > this.totalPages || targetPage === this.currentPage) return;

            const paginationEl = this.$refs.paginationBar;
            const tableEl = this.$refs.tableWrapper;

            // 1. Pre-emptively lock current height so DOM cannot collapse during Alpine render
            if (tableEl && tableEl.offsetHeight > 0) {
                tableEl.style.minHeight = tableEl.offsetHeight + 'px';
            }

            const paginationTopBefore = paginationEl ? paginationEl.getBoundingClientRect().top : null;
            const scrollEl = this._getScrollContainer();

            // 2. Change page (triggers Alpine reactive re-render)
            this.currentPage = targetPage;

            // 3. Immediately after Alpine finishes DOM updates (synchronous nextTick before browser paint):
            this.$nextTick(() => {
                // Instantly clear the temporary minHeight lock after Alpine renders the new page
                if (tableEl) {
                    tableEl.style.minHeight = '';
                }
                this.updateRowHeight();

                if (paginationTopBefore !== null && paginationEl) {
                    const paginationTopAfter = paginationEl.getBoundingClientRect().top;
                    const delta = paginationTopAfter - paginationTopBefore;

                    if (Math.abs(delta) >= 1) {
                        if (scrollEl === window) {
                            window.scrollBy({ top: delta, behavior: 'instant' });
                        } else {
                            scrollEl.scrollBy({ top: delta, behavior: 'instant' });
                        }
                    }
                }

                if (window.lucide) lucide.createIcons();
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

        get totalModified() {
            return this.items.filter(it => it.is_modified).length;
        },

        get totalQtyIn() {
            return this.items
                .filter(it => it.is_modified && it.selisih_val > 0)
                .reduce((acc, it) => acc + (it.selisih_val || 0), 0);
        },

        get totalQtyOut() {
            return this.items
                .filter(it => it.is_modified && it.selisih_val < 0)
                .reduce((acc, it) => acc + Math.abs(it.selisih_val || 0), 0);
        },

        // ── Draft & Auto-Save Mechanics ───────────────────────────────────────
        scheduleAutoSave() {
            clearTimeout(this.autoSaveTimeout);
            this.autoSaveTimeout = setTimeout(() => {
                this.saveDraftNow();
            }, 500);
        },

        saveDraftNow() {
            if (this.isSubmitting) return;
            const changes = {};
            this.items.forEach(it => {
                if (it.is_modified || (it.catatan_item && it.catatan_item.trim() !== '')) {
                    changes[it.id] = {
                        stok_fisik_val: it.stok_fisik_val,
                        selisih_val: it.selisih_val,
                        is_modified: it.is_modified,
                        catatan_item: it.catatan_item
                    };
                }
            });

            if (Object.keys(changes).length > 0) {
                const now = new Date();
                const timeStr = now.toLocaleDateString('id-ID', { day:'2-digit', month:'short' }) + ' ' +
                                now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });
                const payload = {
                    saved_at: timeStr,
                    formTanggal: this.formTanggal,
                    formCatatan: this.formCatatan,
                    changes: changes
                };
                localStorage.setItem(this.STORAGE_KEY, JSON.stringify(payload));
            } else {
                localStorage.removeItem(this.STORAGE_KEY);
            }
        },

        restoreDraft() {
            try {
                const draftRaw = localStorage.getItem(this.STORAGE_KEY);
                if (!draftRaw) return;
                const draft = JSON.parse(draftRaw);
                if (draft.formTanggal) this.formTanggal = draft.formTanggal;
                if (draft.formCatatan) this.formCatatan = draft.formCatatan;
                if (draft.changes) {
                    this.items.forEach(it => {
                        const ch = draft.changes[it.id];
                        if (ch) {
                            it.stok_fisik_val = ch.stok_fisik_val;
                            it.selisih_val = ch.selisih_val;
                            it.is_modified = ch.is_modified;
                            it.catatan_item = ch.catatan_item || '';
                            if (it.selisih_val > 0) {
                                it.selisih_input_val = '+' + this.formatQty(it.selisih_val);
                            } else if (it.selisih_val < 0) {
                                it.selisih_input_val = '-' + this.formatQty(Math.abs(it.selisih_val));
                            } else {
                                it.selisih_input_val = '';
                            }
                        }
                    });
                }
                this.hasDraft = false;
                this.draftDismissed = true;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            } catch (e) {
                console.error("Gagal memulihkan draft:", e);
            }
        },

        clearDraft() {
            localStorage.removeItem(this.STORAGE_KEY);
            this.hasDraft = false;
            this.draftDismissed = true;
        },

        async confirmClearDraft() {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Buang Draf Opname?',
                message: 'Apakah Anda yakin ingin membuang draf opname otomatis ini? Data yang tersimpan di browser Anda akan dihapus permanen.',
                type: 'warning',
                confirmText: 'Ya, Buang Draf',
                cancelText: 'Batal'
            }) : confirm('Apakah Anda yakin ingin membuang draf opname otomatis ini?');

            if (confirmed) {
                this.clearDraft();
                if (window.toast && window.toast.info) {
                    window.toast.info('Draf opname berhasil dibuang.');
                }
            }
        },

        onFormSubmit(e) {
            this.isSubmitting = true;
            localStorage.removeItem(this.STORAGE_KEY);
        },

        // Navigasi Enter cepat ke baris bawahnya
        focusNextRow(evt, colType) {
            const td = evt.target.closest('td');
            const tr = td?.closest('tr');
            const nextTr = tr?.nextElementSibling;
            if (nextTr && !nextTr.classList.contains('filler-row')) {
                const targetInput = nextTr.querySelector(`input[data-col="${colType}"]`);
                if (targetInput) {
                    targetInput.focus();
                    targetInput.select();
                }
            }
        },

        // Two-way Binding 1: Saat user mengetik Stok Fisik Realita
        onPhysicalKeydown(evt) {
            if (evt.ctrlKey || evt.metaKey) return;
            const allowed = [
                'Backspace', 'Delete', 'Tab', 'Enter', 'Escape',
                'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                'Home', 'End'
            ];
            if (allowed.includes(evt.key)) return;

            // Hanya terima angka 0-9
            if (/^[0-9]$/.test(evt.key)) return;

            // Pemisah desimal (. atau ,) maksimal 1 kali
            if (evt.key === '.' || evt.key === ',') {
                const target = evt.target;
                const val = target.value || '';
                const hasDecimal = val.includes('.') || val.includes(',');
                const selectionHasDecimal = target.selectionStart !== target.selectionEnd &&
                    val.substring(target.selectionStart, target.selectionEnd).search(/[.,]/) !== -1;
                if (!hasDecimal || selectionHasDecimal) return;
            }

            // Blokir tombol selain angka dan desimal (termasuk -, +, huruf)
            evt.preventDefault();
        },

        onPhysicalInput(it, target) {
            let val = typeof target === 'object' && target ? target.value : target;
            if (val === '' || val === null || val === undefined) {
                it.stok_fisik_val = null;
                it.selisih_val = 0;
                it.selisih_input_val = '';
                it.is_modified = false;
                this.scheduleAutoSave();
                return;
            }

            // Bersihkan hanya angka dan satu desimal
            let raw = String(val).replace(/[^0-9.,]/g, '');
            let parts = raw.split(/[.,]/);
            if (parts.length > 2) {
                raw = parts[0] + '.' + parts.slice(1).join('');
            }
            if (typeof target === 'object' && target && target.value !== raw) {
                target.value = raw;
            }

            const clean = raw.replace(',', '.');
            let num = parseFloat(clean);
            if (isNaN(num)) return;
            if (num < 0) num = 0;

            it.stok_fisik_val = num;
            it.selisih_val = num - it.stok_sistem;
            it.is_modified = Math.abs(it.selisih_val) > 0.0001;

            if (it.selisih_val > 0) {
                it.selisih_input_val = '+' + this.formatQty(it.selisih_val);
            } else if (it.selisih_val < 0) {
                it.selisih_input_val = '-' + this.formatQty(Math.abs(it.selisih_val));
            } else {
                it.selisih_input_val = '';
            }

            this.scheduleAutoSave();
        },

        onPhysicalBlur(it, target) {
            let val = typeof target === 'object' && target ? target.value : target;
            if (val === '' || val === null || val === undefined) {
                this.resetRow(it);
                return;
            }
            const clean = String(val).replace(',', '.').replace(/\s+/g, '');
            let num = parseFloat(clean);
            if (isNaN(num) || Math.abs(num - it.stok_sistem) <= 0.0001) {
                this.resetRow(it);
            } else {
                if (num < 0) num = 0;
                it.stok_fisik_val = num;
                it.selisih_val = num - it.stok_sistem;
                it.is_modified = true;
                if (it.selisih_val > 0) {
                    it.selisih_input_val = '+' + this.formatQty(it.selisih_val);
                } else if (it.selisih_val < 0) {
                    it.selisih_input_val = '-' + this.formatQty(Math.abs(it.selisih_val));
                } else {
                    it.selisih_input_val = '';
                }
            }
            this.scheduleAutoSave();
        },

        // Two-way Binding 2: Saat user mengetik Selisih (+/-)
        onDiffKeydown(evt) {
            if (evt.ctrlKey || evt.metaKey) return;
            const allowed = [
                'Backspace', 'Delete', 'Tab', 'Enter', 'Escape',
                'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                'Home', 'End'
            ];
            if (allowed.includes(evt.key)) return;

            const target = evt.target;
            const val = target.value || '';
            const selStart = target.selectionStart;
            const selEnd = target.selectionEnd;

            // Izinkan tanda + dan -
            if (evt.key === '+' || evt.key === '-') {
                const hasExistingSign = /^[+-]/.test(val);
                const selectionReplacesSign = (selStart === 0 && (selEnd > 0 || !hasExistingSign));
                if (selStart === 0 || selectionReplacesSign) {
                    return;
                }
                // Jika kursor bukan di awal tapi user menekan +/-, gantikan tanda di awal
                evt.preventDefault();
                let withoutSign = val.replace(/^[+-]/, '');
                let newVal = evt.key + withoutSign;
                target.value = newVal;
                let newPos = Math.min(selStart + 1, newVal.length);
                target.setSelectionRange(newPos, newPos);
                target.dispatchEvent(new Event('input'));
                return;
            }

            // Hanya terima angka 0-9
            if (/^[0-9]$/.test(evt.key)) return;

            // Pemisah desimal (. atau ,) maksimal 1 kali
            if (evt.key === '.' || evt.key === ',') {
                const hasDecimal = val.includes('.') || val.includes(',');
                const selectionSpansDecimal = selStart !== selEnd &&
                    val.substring(selStart, selEnd).search(/[.,]/) !== -1;
                if (!hasDecimal || selectionSpansDecimal) return;
            }

            // Blokir tombol selain tanda +, -, angka, dan pemisah desimal
            evt.preventDefault();
        },

        sanitizeDiffInput(val) {
            if (val === null || val === undefined) return '';
            let str = String(val).trim();
            if (!str) return '';

            let sign = '';
            if (str.startsWith('+')) {
                sign = '+';
                str = str.substring(1);
            } else if (str.startsWith('-')) {
                sign = '-';
                str = str.substring(1);
            }

            // Hapus semua karakter yang BUKAN angka dan BUKAN pemisah desimal
            str = str.replace(/[^0-9.,]/g, '');

            // Maksimal satu pemisah desimal
            let parts = str.split(/[.,]/);
            if (parts.length > 2) {
                str = parts[0] + '.' + parts.slice(1).join('');
            }

            return sign + str;
        },

        notifyMaxReductionCapped(it, maxReduction) {
            const now = Date.now();
            if (this._lastCappedToastTime && (now - this._lastCappedToastTime < 2400)) {
                return;
            }
            this._lastCappedToastTime = now;

            const unit = it.satuan_dasar || 'pcs';
            if (maxReduction <= 0) {
                if (window.toast && window.toast.warning) {
                    window.toast.warning(`Stok sistem "${it.nama_item}" adalah 0 ${unit}. Pengurangan stok (-) tidak diizinkan.`);
                }
            } else {
                if (window.toast && window.toast.warning) {
                    window.toast.warning(`Pengurangan stok "${it.nama_item}" dibatasi maksimal -${this.formatQty(maxReduction)} ${unit} agar stok fisik tidak minus.`);
                }
            }
        },

        onDiffInput(it, target) {
            let rawVal = typeof target === 'object' && target ? target.value : target;
            let sanitized = this.sanitizeDiffInput(rawVal);
            if (typeof target === 'object' && target && target.value !== sanitized) {
                target.value = sanitized;
            }
            it.selisih_input_val = sanitized;

            if (sanitized === '' || sanitized === '-' || sanitized === '+' || sanitized === null) {
                // Biarkan user leluasa mengetik tanda + atau - terlebih dahulu
                return;
            }

            const clean = sanitized.replace(',', '.').replace(/\s+/g, '');
            let diff = parseFloat(clean);
            if (isNaN(diff)) return;

            // Pengaman: nominal minus (-) tidak boleh melebihi stok yang ada di sistem
            const systemStock = Number(it.stok_sistem) || 0;
            if (diff < 0) {
                const maxReduction = Math.max(0, systemStock);
                if (Math.abs(diff) > maxReduction) {
                    diff = -maxReduction;
                    let cappedStr = '-' + this.formatQty(maxReduction);
                    it.selisih_input_val = cappedStr;
                    if (typeof target === 'object' && target) {
                        target.value = cappedStr;
                    }
                    this.notifyMaxReductionCapped(it, maxReduction);
                }
            }

            let targetPhysical = systemStock + diff;
            if (targetPhysical < 0) {
                targetPhysical = 0;
                diff = -Math.max(0, systemStock);
            }

            it.selisih_val = diff;
            it.stok_fisik_val = targetPhysical;
            it.is_modified = Math.abs(it.selisih_val) > 0.0001;
            this.scheduleAutoSave();
        },

        onDiffBlur(it, target) {
            let rawVal = typeof target === 'object' && target ? target.value : target;
            if (rawVal === '' || rawVal === '-' || rawVal === '+' || rawVal === null || rawVal === undefined) {
                this.resetRow(it);
                return;
            }

            let sanitized = this.sanitizeDiffInput(rawVal);
            const clean = sanitized.replace(',', '.').replace(/\s+/g, '');
            let diff = parseFloat(clean);
            if (isNaN(diff) || Math.abs(diff) <= 0.0001) {
                this.resetRow(it);
                return;
            }

            const systemStock = Number(it.stok_sistem) || 0;
            if (diff < 0) {
                const maxReduction = Math.max(0, systemStock);
                if (Math.abs(diff) > maxReduction) {
                    diff = -maxReduction;
                    this.notifyMaxReductionCapped(it, maxReduction);
                }
            }

            let targetPhysical = systemStock + diff;
            if (targetPhysical < 0) {
                targetPhysical = 0;
                diff = -Math.max(0, systemStock);
            }

            it.selisih_val = diff;
            it.stok_fisik_val = targetPhysical;
            it.is_modified = Math.abs(it.selisih_val) > 0.0001;
            it.selisih_input_val = diff > 0 ? ('+' + this.formatQty(diff)) : ('-' + this.formatQty(Math.abs(diff)));
            this.scheduleAutoSave();
        },

        // Resets only the stock values. The note is intentionally preserved —
        // user may have typed a note before deciding to revert the quantity.
        resetRow(it) {
            it.stok_fisik_val = null;
            it.selisih_val = 0;
            it.selisih_input_val = '';
            it.is_modified = false;
            this.scheduleAutoSave();
        },

        async resetAllRows() {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Batalkan Semua Perubahan?',
                message: 'Apakah Anda yakin ingin membatalkan seluruh perubahan opname di tabel ini? Data yang belum disimpan akan dikembalikan.',
                type: 'danger',
                confirmText: 'Ya, Batalkan',
                cancelText: 'Kembali'
            }) : confirm('Apakah Anda yakin ingin membatalkan seluruh perubahan opname di tabel ini?');

            if (!confirmed) {
                return;
            }

            // Full reset including notes, because user explicitly requested a total wipe.
            this.items.forEach(it => {
                it.stok_fisik_val = null;
                it.selisih_val = 0;
                it.selisih_input_val = '';
                it.is_modified = false;
                it.catatan_item = '';
            });
            this.clearDraft();
            if (window.toast && window.toast.info) {
                window.toast.info('Semua perubahan opname berhasil dibatalkan.');
            }
        },

        getChangedItemsPayload() {
            return this.items
                .filter(it => it.is_modified)
                .map(it => ({
                    item_id: it.id,
                    kode_sku: it.kode_sku,
                    nama_item: it.nama_item,
                    stok_sistem: it.stok_sistem,
                    stok_fisik: it.stok_fisik_val !== null ? it.stok_fisik_val : it.stok_sistem,
                    selisih: it.selisih_val,
                    catatan_item: it.catatan_item
                }));
        },

        async openConfirmModal() {
            if (this.totalModified === 0) {
                if (window.AppAlert) {
                    await window.AppAlert({
                        title: 'Belum Ada Perubahan',
                        message: 'Tidak ada item yang diubah. Silakan masukkan stok fisik atau selisih minimal 1 produk sebelum menyimpan.',
                        type: 'info',
                        buttonText: 'Mengerti'
                    });
                } else {
                    alert('Tidak ada item yang diubah. Silakan masukkan stok fisik atau selisih minimal 1 produk.');
                }
                return;
            }
            this.showConfirmModal = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async openHistory(item) {
            this.selectedItem = item;
            this.showHistoryModal = true;
            this.historyLoading = true;
            this.itemHistoryList = [];
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });

            try {
                const res = await fetch('<?= Router::url('/inventory/api/item-history') ?>?item_id=' + encodeURIComponent(item.id));
                const data = await res.json();
                if (data.success) {
                    this.itemHistoryList = data.history || [];
                }
            } catch (err) {
                console.error("Gagal memuat kartu stok:", err);
            } finally {
                this.historyLoading = false;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            }
        },

        formatQty(val) {
            const num = parseFloat(val);
            if (isNaN(num)) return '0';
            return num % 1 === 0 ? num.toString() : num.toFixed(2);
        },

        formatBarcode(val) {
            if (!val) return '—';
            val = String(val).trim();
            if (!val || val === 'null' || val === '—') return '—';
            if (/^[0-9]+\.[0-9]+[eE]\+?[0-9]+$/i.test(val)) {
                try {
                    const num = Number(val);
                    if (!isNaN(num)) return num.toLocaleString('fullwide', { useGrouping: false });
                } catch (e) {}
            }
            if (/^[0-9]+\.0+$/.test(val)) {
                return val.split('.')[0];
            }
            return val;
        },

        isStockIn(type) {
            return ['produksi_masuk', 'pembelian_masuk', 'penyesuaian_opname_tambah', 'retur_pelanggan_masuk', 'konsinyasi_retur_masuk'].includes(type);
        },

        getMutationBadgeClass(type) {
            if (this.isStockIn(type)) return 'badge-success';
            if (['item_keluar_waste', 'konsinyasi_retur_rusak'].includes(type)) return 'badge-danger';
            return 'badge-warning';
        },

        formatMutationType(type) {
            const map = {
                'produksi_masuk': 'Produksi Masuk',
                'pembelian_masuk': 'Pembelian Vendor',
                'penyesuaian_opname_tambah': 'Opname (+ Masuk)',
                'penyesuaian_opname_kurang': 'Opname (- Keluar)',
                'penjualan_keluar': 'Penjualan Keluar',
                'konsinyasi_keluar': 'Titip Konsinyasi',
                'konsinyasi_retur_masuk': 'Retur Konsinyasi',
                'konsinyasi_retur_rusak': 'Retur Rusak Konsinyasi',
                'item_keluar_waste': 'Waste / Rusak',
                'bahan_terpakai_produksi': 'Pemakaian Bahan'
            };
            return map[type] || type;
        },

        formatDate(dtStr) {
            if (!dtStr) return '—';
            const d = new Date(dtStr);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

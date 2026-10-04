<?php
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<style>
.search-info-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
}
.search-info-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 8px;
    border: 1px solid var(--color-hairline);
    background: var(--color-canvas);
    color: var(--color-ink-mute);
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}
.search-info-btn:hover, .search-info-btn.is-active {
    color: var(--color-primary);
    border-color: var(--color-primary);
    background: rgba(136, 19, 55, 0.08);
}
.search-info-popover {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    z-index: 60;
    width: 320px;
    max-width: calc(100vw - 32px);
    padding: 14px 16px;
    border-radius: 12px;
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.18), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    font-size: 12px;
    line-height: 1.5;
    color: var(--color-ink);
    animation: searchPopIn 0.15s ease-out forwards;
}
@keyframes searchPopIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
.table-loading-bar {
    height: 3px;
    width: 100%;
    background: linear-gradient(90deg, #881337 0%, #fb7185 50%, #881337 100%);
    background-size: 200% 100%;
    animation: tableLoadingShimmer 1.1s infinite linear;
}
@keyframes tableLoadingShimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>

<div x-data="supplierApp()" x-init="init()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-violet">
                <i data-lucide="building-2"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#8b5cf6;"></span>
                    <span>Pengadaan &amp; Vendor</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Master Pemasok' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Kelola Data Supplier Bahan Baku Curah, Plastik &amp; Bumbu' ?></p>
            </div>
        </div>
        <?php if (Auth::can('master.suppliers_manage')): ?>
        <div class="page-header-actions">
            <button @click="openAddModal()" class="btn btn-primary" style="font-weight:700;">
                <i data-lucide="plus"></i>
                <span>Tambah Pemasok Baru</span>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- STATS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="building-2"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Pemasok Vendor</div>
                <div class="stat-card-value"><?= count($suppliers) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Mitra supplier bahan &amp; kemasan</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                <i data-lucide="check-circle"></i>
            </div>
            <div>
                <div class="stat-card-label">Pemasok Aktif</div>
                <div class="stat-card-value" style="color:#3b82f6;">
                    <?= count(array_filter($suppliers, fn($s) => !empty($s['status_aktif']))) ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Siap menerima pesanan PO</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(239,68,68,0.1);color:#ef4444;">
                <i data-lucide="map-pin"></i>
            </div>
            <div>
                <div class="stat-card-label">Terhubung Google Maps</div>
                <div class="stat-card-value" style="color:#ef4444;">
                    <?= count(array_filter($suppliers, fn($s) => !empty($s['link_google_maps']))) ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Navigasi presisi belanja armada driver</div>
            </div>
        </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card" style="padding:0;overflow:hidden;">

        <!-- FILTER & ACTION BAR -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 sm:p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="flex items-center gap-1.5 flex-1 sm:max-w-md w-full">
                <div class="form-input-icon flex-1 relative">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="search" x-model="searchQuery"
                           name="q"
                           id="supplierSearchInput"
                           @input.debounce.350ms="fetchSuppliers(1)"
                           @keydown.enter.prevent="fetchSuppliers(1)"
                           placeholder="Cari vendor, kode VND, PIC, WA, email, alamat, rek..."
                           autocomplete="off"
                           autocorrect="off"
                           autocapitalize="off"
                           spellcheck="false"
                           data-lpignore="true"
                           data-1p-ignore="true"
                           data-bwignore="true"
                           data-form-type="other"
                           inputmode="search"
                           class="form-input" style="height:38px;font-size:13px;padding-right:32px;">

                    <!-- Clear Button (✕) -->
                    <button type="button" x-cloak x-show="searchQuery" @click="clearSearch()" class="btn btn-ghost btn-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);padding:4px;display:flex;align-items:center;justify-content:center;cursor:pointer;" title="Bersihkan Pencarian">
                        <i data-lucide="x" style="width:14px;height:14px;"></i>
                    </button>
                </div>

                <!-- Info Popover Button -->
                <div class="search-info-wrapper" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button type="button" @click="open = !open" class="search-info-btn" :class="open ? 'is-active' : ''" title="Informasi Atribut Pencarian Vendor">
                        <i data-lucide="info" style="width:15px;height:15px;"></i>
                    </button>
                    <div x-show="open" x-cloak @click.away="open = false" class="search-info-popover">
                        <div style="font-weight:800;font-size:12.5px;color:var(--color-primary);display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <i data-lucide="building-2" style="width:14px;height:14px;"></i>
                            <span>Panduan Pencarian Vendor</span>
                        </div>
                        <p style="font-size:11px;color:var(--color-ink-mute);margin-bottom:8px;">Pencarian otomatis langsung (*live debounce*) mencakup <b>seluruh database vendor</b>:</p>
                        <div style="display:grid;grid-template-columns:1fr;gap:4px;font-size:11.5px;color:var(--color-ink);">
                            <div>• <b>Nama Pemasok &amp; Kode</b> (<code>VND-xxxx</code>)</div>
                            <div>• <b>Kontak PIC &amp; No. WhatsApp / Telepon</b></div>
                            <div>• <b>Email &amp; Alamat Lengkap / Maps</b></div>
                            <div>• <b>Wilayah / Rute Pengadaan Bahan</b></div>
                            <div>• <b>Nomor Rekening, Bank &amp; Atas Nama</b></div>
                            <div>• <b>Termin Pembayaran &amp; Catatan PO</b></div>
                        </div>
                        <div style="font-size:10.5px;color:var(--color-primary);margin-top:8px;padding-top:6px;border-top:1px dashed var(--color-hairline);font-weight:600;">
                            ⚡ Tips: Hasil muncul otomatis saat Anda mengetik (jeda 0.3 dtk) tanpa reload halaman.
                        </div>
                    </div>
                </div>

            </div>

            <div class="text-xs" style="color:var(--color-ink-mute);font-weight:600;white-space:nowrap;">
                Menampilkan <span class="font-mono" style="font-weight:800;color:var(--color-primary);" x-text="filteredSuppliers.length"></span> dari <span class="font-mono" style="font-weight:700;color:var(--color-ink);" x-text="serverPagination ? serverPagination.total : suppliers.length"></span> pemasok
            </div>
        </div>

        <!-- TABLE LIST -->
        <div class="relative overflow-x-auto custom-scrollbar">
            <!-- Shimmer Loading Bar on top of Table -->
            <div x-show="isSearching" class="table-loading-bar" style="display:none;"></div>
            <table class="data-table" style="min-width: 980px;">
                <thead>
                    <tr>
                        <th style="width:105px; min-width:90px;" class="cell-nowrap">Kode</th>
                        <th style="min-width:200px;">Pemasok &amp; Lokasi Maps</th>
                        <th style="min-width:180px;">Kontak &amp; PIC</th>
                        <th style="min-width:180px;">Wilayah / Alamat</th>
                        <th style="min-width:180px;">Ketentuan &amp; Rekening</th>
                        <?php if (Auth::can('master.suppliers_manage')): ?>
                        <th class="cell-center cell-nowrap" style="width:85px; min-width:80px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <!-- Skeleton Rows (Tampil saat isSearching aktif) -->
                <tbody x-show="isSearching" style="display:none;">
                    <?php for ($sk = 0; $sk < 5; $sk++): ?>
                    <tr>
                        <td class="cell-nowrap">
                            <div class="skeleton-shimmer skeleton-pill" style="width:75px;height:20px;"></div>
                        </td>
                        <td>
                            <div class="skeleton-shimmer skeleton-line" style="width:60%;height:14px;margin-bottom:6px;"></div>
                            <div class="skeleton-shimmer skeleton-line" style="width:40%;height:11px;"></div>
                        </td>
                        <td>
                            <div class="skeleton-shimmer skeleton-line" style="width:80px;height:13px;margin-bottom:4px;"></div>
                            <div class="skeleton-shimmer skeleton-line" style="width:95px;height:11px;"></div>
                        </td>
                        <td>
                            <div class="skeleton-shimmer skeleton-line" style="width:75px;height:13px;margin-bottom:4px;"></div>
                            <div class="skeleton-shimmer skeleton-line" style="width:110px;height:11px;"></div>
                        </td>
                        <td>
                            <div class="skeleton-shimmer skeleton-pill" style="width:80px;height:18px;margin-bottom:4px;"></div>
                            <div class="skeleton-shimmer skeleton-line" style="width:100px;height:11px;"></div>
                        </td>
                        <?php if (Auth::can('master.suppliers_manage')): ?>
                        <td class="cell-center cell-nowrap">
                            <div class="skeleton-shimmer skeleton-box" style="width:55px;height:26px;border-radius:6px;margin:0 auto;"></div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>

                <!-- Real Data Rows -->
                <tbody x-show="!isSearching">
                    <template x-for="s in filteredSuppliers" :key="s.id">
                        <tr :style="!s.status_aktif ? 'opacity:0.55;' : ''">
                            <td class="cell-nowrap">
                                <span class="badge badge-mono" x-text="s.kode_pemasok"></span>
                            </td>
                            <td>
                                <div style="font-weight:700;font-size:13.5px;color:var(--color-ink);" x-text="s.nama_pemasok"></div>
                                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:4px;">
                                    <span :class="s.status_aktif ? 'badge badge-success' : 'badge badge-secondary'" style="font-size:10px;padding:1px 6px;" x-text="s.status_aktif ? 'Aktif' : 'Nonaktif'"></span>
                                    
                                    <!-- Google Maps Precision Link -->
                                    <template x-if="s.link_google_maps">
                                        <a :href="s.link_google_maps" target="_blank" rel="noopener noreferrer" class="badge" style="background:rgba(239,68,68,0.08);color:#ef4444;border:1px solid rgba(239,68,68,0.25);padding:1px 6px;font-size:10px;font-weight:700;display:inline-flex;align-items:center;gap:3px;text-decoration:none;" title="Buka Titik Presisi Google Maps di Tab Baru">
                                            <i data-lucide="map-pin" style="width:11px;height:11px;"></i>
                                            <span>Titik Maps</span>
                                        </a>
                                    </template>
                                </div>
                                <template x-if="s.catatan">
                                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;line-height:1.35;font-style:italic;" x-text="'Catatan: ' + s.catatan"></div>
                                </template>
                            </td>
                            <td>
                                <template x-if="s.nama_kontak">
                                    <div style="font-weight:700;font-size:12px;color:var(--color-ink);display:flex;align-items:center;gap:4px;margin-bottom:2px;">
                                        <i data-lucide="user" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                                        <span x-text="s.nama_kontak"></span>
                                    </div>
                                </template>
                                <div style="display:flex;flex-direction:column;gap:3px;">
                                    <template x-if="s.nomor_whatsapp">
                                        <div>
                                            <a :href="'https://wa.me/' + cleanWa(s.nomor_whatsapp)" target="_blank" rel="noopener noreferrer" class="badge" style="background:rgba(16,185,129,0.1);color:#059669;border:1px solid rgba(16,185,129,0.25);padding:1px 6px;font-size:10.5px;font-weight:700;display:inline-flex;align-items:center;gap:4px;text-decoration:none;" title="Hubungi Vendor via WhatsApp">
                                                <i data-lucide="message-circle" style="width:11px;height:11px;"></i>
                                                <span x-text="s.nomor_whatsapp"></span>
                                            </a>
                                        </div>
                                    </template>
                                    <template x-if="s.email">
                                        <div>
                                            <a :href="'mailto:' + s.email" style="font-size:11px;color:var(--color-ink-mute);display:inline-flex;align-items:center;gap:3px;text-decoration:none;" title="Kirim Email PO">
                                                <i data-lucide="mail" style="width:11px;height:11px;"></i>
                                                <span x-text="s.email"></span>
                                            </a>
                                        </div>
                                    </template>
                                    <template x-if="!s.nama_kontak && !s.nomor_whatsapp && !s.email">
                                        <span style="color:var(--color-ink-mute);font-size:12px;">-</span>
                                    </template>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:700;font-size:12.5px;color:var(--color-ink);" x-text="s.nama_wilayah || '-'"></div>
                                <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.4;" x-text="s.alamat_lengkap"></div>
                            </td>
                            <td>
                                <div style="margin-bottom:4px;">
                                    <span :class="{
                                        'badge badge-success': s.termin_bayar === 'cash',
                                        'badge badge-info': s.termin_bayar === 'transfer',
                                        'badge badge-warning': s.termin_bayar && s.termin_bayar.startsWith('tempo_')
                                    }" style="font-size:10px;padding:1px 6px;font-weight:700;" x-text="formatTermin(s.termin_bayar)"></span>
                                </div>
                                <template x-if="s.nomor_rekening">
                                    <div>
                                        <div style="font-weight:600;font-size:12px;" x-text="(s.nama_bank || 'Bank') + ' - ' + s.nomor_rekening"></div>
                                        <div style="font-size:11px;color:var(--color-ink-mute);" x-text="'a/n ' + (s.atas_nama_rekening || '-')"></div>
                                    </div>
                                </template>
                                <template x-if="!s.nomor_rekening">
                                    <span style="color:var(--color-ink-mute);font-size:11.5px;">-</span>
                                </template>
                            </td>
                            <?php if (Auth::can('master.suppliers_manage')): ?>
                            <td class="cell-center cell-nowrap">
                                <div style="display:flex;align-items:center;justify-content:center;gap:4px;">
                                    <button @click="openCatalogModal(s)" class="btn btn-secondary btn-sm" style="padding:4px 8px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:4px;border-color:rgba(136,19,55,0.25);color:var(--color-primary);" title="Kelola Katalog Bahan &amp; Harga Vendor Ini">
                                        <i data-lucide="package-search" style="width:13px;height:13px;"></i>
                                        <span>Katalog</span>
                                    </button>
                                    <button @click="openEditModal(s)" class="btn btn-ghost btn-sm" style="padding:6px;" title="Edit Data Vendor Lengkap">
                                        <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                    </button>
                                    <button @click="deleteSupplier(s.id, s.nama_pemasok)" class="btn btn-ghost btn-sm" style="padding:6px;color:#ef4444;" title="Hapus Data Vendor">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    </template>

                    <template x-if="filteredSuppliers.length === 0">
                        <tr>
                            <td colspan="<?= Auth::can('master.suppliers_manage') ? 6 : 5 ?>" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="search-x" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Tidak ada data pemasok yang cocok</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION BAR (Reactive & AJAX Powered) -->
        <template x-if="serverPagination && serverPagination.totalPages > 1">
            <div style="padding:12px 16px;background:var(--color-canvas-soft, #f8fafc);border-top:1px solid var(--color-hairline);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <div style="font-size:12px;color:var(--color-ink-mute);">
                    Menampilkan Halaman <strong x-text="serverPagination.page"></strong> dari <strong x-text="serverPagination.totalPages"></strong> (Total <span x-text="Number(serverPagination.total).toLocaleString('id-ID')"></span> pemasok)
                </div>
                <div style="display:flex;gap:6px;">
                    <button type="button" x-show="serverPagination.page > 1" @click="fetchSuppliers(serverPagination.page - 1)" class="btn btn-secondary btn-sm" style="font-size:12px;">
                        &laquo; Sebelumnya
                    </button>
                    <button type="button" x-show="serverPagination.page < serverPagination.totalPages" @click="fetchSuppliers(serverPagination.page + 1)" class="btn btn-secondary btn-sm" style="font-size:12px;">
                        Selanjutnya &raquo;
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL TAMBAH / EDIT PEMASOK LENGKAP                                       -->
    <!-- ========================================================================= -->
    <?php if (Auth::can('master.suppliers_manage')): ?>
    <template x-teleport="body">
    <div x-show="showModal" x-cloak class="modal-backdrop" @click="showModal = false">
        <div class="modal-box modal-box-lg" style="max-width:640px;" @click.stop>
            <div class="modal-handle"><div class="modal-handle-bar"></div></div>
            <div class="modal-header">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(139,92,246,0.12);color:#8b5cf6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="building-2" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="modal-title" x-text="isEdit ? 'Edit Master Pemasok' : 'Tambah Pemasok Baru'"></div>
                        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:1px;">Lengkapi data vendor, titik presisi peta, kontak PIC, dan syarat pembayaran.</div>
                    </div>
                </div>
                <button type="button" @click="showModal = false" class="modal-close-x" title="Tutup Modal">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <form :action="isEdit ? '<?= Router::url('/suppliers/update') ?>' : '<?= Router::url('/suppliers/store') ?>'" method="POST">
                <div class="modal-body custom-scrollbar space-y-3.5">
                    <?= \App\Helpers\CSRF::field() ?>
                    <input type="hidden" name="id" :value="form.id">

                    <!-- SEKSI 1: IDENTITAS VENDOR & PIC -->
                    <div style="padding:14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;flex-direction:column;gap:12px;">
                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:var(--color-primary);display:flex;align-items:center;gap:6px;">
                            <i data-lucide="building" style="width:14px;height:14px;"></i>
                            <span>1. Identitas Vendor &amp; PIC</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Nama Pemasok / Badan Usaha <span style="color:var(--color-danger);">*</span></label>
                                <input type="text" name="nama_pemasok" x-model="form.nama_pemasok" required class="form-input" placeholder="Contoh: PT SUMBER PLASTIK">
                            </div>
                            <div>
                                <label class="form-label">Nama Kontak / PIC Vendor</label>
                                <input type="text" name="nama_kontak" x-model="form.nama_kontak" class="form-input" placeholder="Contoh: Pak Hendra (Sales)">
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI 2: KONTAK & KOMUNIKASI -->
                    <div style="padding:14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;flex-direction:column;gap:12px;">
                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:var(--color-primary);display:flex;align-items:center;gap:6px;">
                            <i data-lucide="phone-call" style="width:14px;height:14px;"></i>
                            <span>2. Kontak &amp; Komunikasi</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Nomor WhatsApp PIC</label>
                                <input type="text" name="nomor_whatsapp" x-model="form.nomor_whatsapp"
                                       @input="form.nomor_whatsapp = $event.target.value.replace(/[^0-9+]/g, '').slice(0, 25)"
                                       class="form-input font-mono" placeholder="081234567890">
                            </div>
                            <div>
                                <label class="form-label">Email Resmi Vendor</label>
                                <input type="email" name="email" x-model="form.email" class="form-input" placeholder="sales@vendor.com">
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI 3: ALAMAT & TITIK LOKASI GOOGLE MAPS -->
                    <div style="padding:14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;flex-direction:column;gap:12px;">
                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:var(--color-primary);display:flex;align-items:center;gap:6px;">
                            <i data-lucide="map-pin" style="width:14px;height:14px;color:#ef4444;"></i>
                            <span>3. Alamat &amp; Titik Lokasi Peta Presisi</span>
                        </div>

                        <div>
                            <label class="form-label">Wilayah / Rute Pengiriman</label>
                            <select name="wilayah_id" x-model="form.wilayah_id" class="form-input">
                                <option value="">-- Tanpa Wilayah Tertentu --</option>
                                <?php foreach ($territories as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_wilayah']) ?> (<?= htmlspecialchars($t['kode_rute']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Alamat Lengkap Gudang / Toko</label>
                            <textarea name="alamat_lengkap" x-model="form.alamat_lengkap" class="form-input" rows="2" placeholder="Contoh: Kawasan Industri Jababeka Blok C No. 12, Cikarang..."></textarea>
                        </div>

                        <!-- Link Google Maps Field -->
                        <div>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                <label class="form-label" style="margin-bottom:0;display:flex;align-items:center;gap:6px;">
                                    <i data-lucide="map-pin" style="width:14px;height:14px;color:#ef4444;"></i>
                                    <span>Link Google Maps Titik Presisi</span>
                                </label>
                                <template x-if="form.link_google_maps">
                                    <a :href="form.link_google_maps" target="_blank" rel="noopener noreferrer" style="font-size:11px;font-weight:700;color:#2563eb;display:inline-flex;align-items:center;gap:4px;text-decoration:none;">
                                        <i data-lucide="external-link" style="width:11px;height:11px;"></i>
                                        <span>Tes Buka Peta</span>
                                    </a>
                                </template>
                            </div>
                            <input type="text" name="link_google_maps" x-model="form.link_google_maps" class="form-input" placeholder="https://maps.app.goo.gl/... atau https://maps.google.com/?q=-6.2,106.8">
                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;line-height:1.4;">
                                <i data-lucide="info" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i>
                                Salin link titik Google Maps agar armada driver belanja PO dapat langsung membuka rute navigasi menuju lokasi vendor.
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI 4: SYARAT PEMBAYARAN & REKENING BANK -->
                    <div style="padding:14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;flex-direction:column;gap:12px;">
                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:var(--color-primary);display:flex;align-items:center;gap:6px;">
                            <i data-lucide="credit-card" style="width:14px;height:14px;"></i>
                            <span>4. Ketentuan Pembayaran &amp; Rekening Bank</span>
                        </div>

                        <div>
                            <label class="form-label">Termin Pembayaran Standar</label>
                            <select name="termin_bayar" x-model="form.termin_bayar" class="form-input">
                                <option value="cash">Tunai / COD (Cash on Delivery)</option>
                                <option value="transfer">Transfer Bank (Sebelum Kirim / CBD)</option>
                                <option value="tempo_7_hari">Tempo 7 Hari</option>
                                <option value="tempo_14_hari">Tempo 14 Hari</option>
                                <option value="tempo_30_hari">Tempo 30 Hari</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
                            <div>
                                <label class="form-label" style="font-size:11px;">Nama Bank</label>
                                <input type="text" name="bank_nama" x-model="form.bank_nama" list="list-bank-supplier" class="form-input" placeholder="BCA / Mandiri / BRI / BSI">
                                <datalist id="list-bank-supplier">
                                    <option value="BCA">
                                    <option value="BRI">
                                    <option value="Mandiri">
                                    <option value="BNI">
                                    <option value="BSI">
                                    <option value="CIMB Niaga">
                                    <option value="Permata">
                                    <option value="Danamon">
                                    <option value="Bank Jago">
                                    <option value="SeaBank">
                                </datalist>
                            </div>
                            <div>
                                <label class="form-label" style="font-size:11px;">No. Rekening</label>
                                <input type="text" name="bank_rekening" x-model="form.bank_rekening"
                                       @input="form.bank_rekening = $event.target.value.replace(/[^0-9-]/g, '').slice(0, 25)"
                                       maxlength="25" class="form-input font-mono" placeholder="1234567890">
                            </div>
                            <div>
                                <label class="form-label" style="font-size:11px;">Atas Nama Rekening <template x-if="form.bank_rekening"><span style="color:var(--color-danger);">*</span></template></label>
                                <input type="text" name="bank_atas_nama" x-model="form.bank_atas_nama"
                                       :required="!!form.bank_rekening"
                                       class="form-input" placeholder="Nama Pemilik">
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI 5: CATATAN TAMBAHAN -->
                    <div style="padding:14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;flex-direction:column;gap:8px;">
                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:var(--color-primary);display:flex;align-items:center;gap:6px;">
                            <i data-lucide="file-text" style="width:14px;height:14px;"></i>
                            <span>5. Catatan / Ketentuan Vendor (Opsional)</span>
                        </div>
                        <textarea name="catatan" x-model="form.catatan" class="form-input" rows="2" placeholder="Contoh: Jam operasional gudang 08:00 - 16:00 WIB, Minimal order 50 kg, konfirmasi H-1 sebelum muat."></textarea>
                    </div>

                    <!-- STATUS AKTIF (KHUSUS EDIT) -->
                    <template x-if="isEdit">
                        <div style="display:flex;align-items:center;gap:8px;padding:4px 2px;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;">
                                <input type="checkbox" name="status_aktif" x-model="form.status_aktif" style="width:16px;height:16px;accent-color:var(--color-primary);">
                                <span>Status Pemasok Aktif (Siap menerima transaksi pembelian)</span>
                            </label>
                        </div>
                    </template>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                    <button type="submit" class="btn btn-primary w-full sm:w-auto" style="display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                        <i data-lucide="save" style="width:16px;height:16px;"></i>
                        <span x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Pemasok'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL KATALOG BAHAN & HARGA VENDOR (CANONICAL KEREN ONE ERP DESIGN DNA)  -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showCatalogModal" x-cloak class="modal-backdrop" @click="showCatalogModal = false">
        <div class="modal-box modal-box-lg" style="max-width:920px; width:96vw;" @click.stop>
            <div class="modal-handle"><div class="modal-handle-bar"></div></div>
            
            <!-- Modal Header (Keren One Canonical) -->
            <div class="modal-header" style="padding:16px 20px; border-bottom:1px solid var(--color-hairline);">
                <div class="flex items-center gap-3.5 min-w-0 flex-1">
                    <div style="width:44px;height:44px;border-radius:12px;background:rgba(136,19,55,0.1);color:var(--color-primary);border:1px solid rgba(136,19,55,0.22);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="package-search" style="width:22px;height:22px;"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="modal-title text-base sm:text-lg font-bold" style="color:var(--color-ink);">Katalog Bahan &amp; Harga Vendor</span>
                            <span class="badge badge-mono text-xs font-bold" style="color:var(--color-primary);background:rgba(136,19,55,0.08);border:1px solid rgba(136,19,55,0.2);" x-text="catalogSupplier?.kode_pemasok"></span>
                        </div>
                        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <span class="font-bold" style="color:var(--color-ink);" x-text="catalogSupplier?.nama_pemasok"></span>
                            <span>&bull;</span>
                            <span x-text="'Syarat Bayar: ' + formatTermin(catalogSupplier?.termin_bayar)"></span>
                        </div>
                    </div>
                </div>
                <button type="button" @click="showCatalogModal = false" class="modal-close-x" title="Tutup Modal">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <div class="modal-body custom-scrollbar space-y-4" style="max-height:calc(85vh - 120px);overflow-y:auto;padding:16px 20px;">
                
                <!-- Skeleton Loading State (Keren One High-Fidelity Skeleton) -->
                <div x-show="loadingCatalog" class="space-y-4">
                    <!-- 1. Skeleton Stat Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="stat-card" style="padding:12px 14px;display:flex;align-items:center;gap:12px;border-radius:12px;">
                            <div class="skeleton-shimmer skeleton-box" style="width:38px;height:38px;min-width:38px;border-radius:10px;"></div>
                            <div style="flex:1;min-width:0;">
                                <div class="skeleton-shimmer skeleton-line" style="width:75px;height:10px;margin-bottom:6px;"></div>
                                <div class="skeleton-shimmer skeleton-line" style="width:45px;height:16px;"></div>
                            </div>
                        </div>
                        <div class="stat-card" style="padding:12px 14px;display:flex;align-items:center;gap:12px;border-radius:12px;">
                            <div class="skeleton-shimmer skeleton-box" style="width:38px;height:38px;min-width:38px;border-radius:10px;"></div>
                            <div style="flex:1;min-width:0;">
                                <div class="skeleton-shimmer skeleton-line" style="width:85px;height:10px;margin-bottom:6px;"></div>
                                <div class="skeleton-shimmer skeleton-line" style="width:45px;height:16px;"></div>
                            </div>
                        </div>
                        <div class="stat-card" style="padding:12px 14px;display:flex;align-items:center;gap:12px;border-radius:12px;">
                            <div class="skeleton-shimmer skeleton-box" style="width:38px;height:38px;min-width:38px;border-radius:10px;"></div>
                            <div style="flex:1;min-width:0;">
                                <div class="skeleton-shimmer skeleton-line" style="width:95px;height:10px;margin-bottom:6px;"></div>
                                <div class="skeleton-shimmer skeleton-line" style="width:45px;height:16px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Skeleton Form Card -->
                    <?php if (Auth::can('master.suppliers_manage')): ?>
                    <div style="padding:14px 16px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;flex-direction:column;gap:12px;">
                        <div class="flex items-center justify-between border-b pb-2" style="border-color:var(--color-hairline);">
                            <div class="skeleton-shimmer skeleton-line" style="width:160px;height:12px;"></div>
                            <div class="skeleton-shimmer skeleton-line" style="width:60px;height:10px;"></div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                            <div class="sm:col-span-5">
                                <div class="skeleton-shimmer skeleton-line" style="width:110px;height:10px;margin-bottom:6px;"></div>
                                <div class="skeleton-shimmer skeleton-box" style="width:100%;height:38px;border-radius:6px;"></div>
                            </div>
                            <div class="sm:col-span-3">
                                <div class="skeleton-shimmer skeleton-line" style="width:90px;height:10px;margin-bottom:6px;"></div>
                                <div class="skeleton-shimmer skeleton-box" style="width:100%;height:38px;border-radius:6px;"></div>
                            </div>
                            <div class="sm:col-span-2">
                                <div class="skeleton-shimmer skeleton-line" style="width:80px;height:10px;margin-bottom:6px;"></div>
                                <div class="skeleton-shimmer skeleton-box" style="width:100%;height:38px;border-radius:6px;"></div>
                            </div>
                            <div class="sm:col-span-2">
                                <div class="skeleton-shimmer skeleton-box" style="width:100%;height:38px;border-radius:6px;"></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 3. Skeleton Table Card -->
                    <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);overflow:hidden;">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 border-b" style="border-color:var(--color-hairline);background:var(--color-canvas-soft);">
                            <div class="skeleton-shimmer skeleton-line" style="width:170px;height:12px;"></div>
                            <div class="skeleton-shimmer skeleton-box" style="width:180px;height:32px;border-radius:8px;"></div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="data-table" style="width:100%;margin:0;">
                                <thead>
                                    <tr>
                                        <th style="min-width:180px;">Item / Bahan</th>
                                        <th style="width:120px;">Kategori</th>
                                        <th style="width:80px;" class="cell-center">Satuan</th>
                                        <th style="width:140px;text-align:right;">Harga Beli Vendor</th>
                                        <th style="min-width:140px;">Catatan</th>
                                        <?php if (Auth::can('master.suppliers_manage')): ?>
                                        <th class="cell-center" style="width:80px;">Aksi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($skm = 0; $skm < 3; $skm++): ?>
                                    <tr>
                                        <td>
                                            <div class="skeleton-shimmer skeleton-line" style="width:65%;height:13px;margin-bottom:5px;"></div>
                                            <div class="skeleton-shimmer skeleton-line" style="width:35%;height:10px;"></div>
                                        </td>
                                        <td>
                                            <div class="skeleton-shimmer skeleton-pill" style="width:80px;height:18px;"></div>
                                        </td>
                                        <td class="cell-center">
                                            <div class="skeleton-shimmer skeleton-pill" style="width:40px;height:18px;margin:0 auto;"></div>
                                        </td>
                                        <td style="text-align:right;">
                                            <div class="skeleton-shimmer skeleton-line" style="width:75px;height:13px;margin-left:auto;"></div>
                                        </td>
                                        <td>
                                            <div class="skeleton-shimmer skeleton-line" style="width:60%;height:12px;"></div>
                                        </td>
                                        <?php if (Auth::can('master.suppliers_manage')): ?>
                                        <td class="cell-center">
                                            <div class="skeleton-shimmer skeleton-box" style="width:50px;height:24px;border-radius:6px;margin:0 auto;"></div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Catalog Main Content -->
                <div x-show="!loadingCatalog" class="space-y-4">
                    
                    <!-- 1. Executive Stat Cards (Keren One Metric DNA) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="stat-card" style="padding:12px 14px;display:flex;align-items:center;gap:12px;border-radius:12px;">
                            <div class="stat-card-icon" style="width:38px;height:38px;min-width:38px;border-radius:10px;background:rgba(136,19,55,0.1);color:var(--color-primary);display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="boxes" style="width:18px;height:18px;"></i>
                            </div>
                            <div style="min-width:0;">
                                <div class="stat-card-label" style="font-size:11px;">Total Bahan Dipasok</div>
                                <div class="stat-card-value font-mono" style="font-size:16px;color:var(--color-ink);" x-text="catalogItems.length + ' Item'"></div>
                            </div>
                        </div>

                        <div class="stat-card" style="padding:12px 14px;display:flex;align-items:center;gap:12px;border-radius:12px;">
                            <div class="stat-card-icon" style="width:38px;height:38px;min-width:38px;border-radius:10px;background:rgba(59,130,246,0.1);color:#3b82f6;display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="archive" style="width:18px;height:18px;"></i>
                            </div>
                            <div style="min-width:0;">
                                <div class="stat-card-label" style="font-size:11px;">Bahan Mentah Curah</div>
                                <div class="stat-card-value font-mono" style="font-size:16px;color:#3b82f6;" x-text="catalogItems.filter(i => i.tipe_item === 'bahan_mentah').length + ' Item'"></div>
                            </div>
                        </div>

                        <div class="stat-card" style="padding:12px 14px;display:flex;align-items:center;gap:12px;border-radius:12px;">
                            <div class="stat-card-icon" style="width:38px;height:38px;min-width:38px;border-radius:10px;background:rgba(16,185,129,0.1);color:#10b981;display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="package" style="width:18px;height:18px;"></i>
                            </div>
                            <div style="min-width:0;">
                                <div class="stat-card-label" style="font-size:11px;">Bahan Kemasan &amp; Maklon</div>
                                <div class="stat-card-value font-mono" style="font-size:16px;color:#10b981;" x-text="catalogItems.filter(i => i.tipe_item !== 'bahan_mentah').length + ' Item'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Form Entri / Edit Cepat (Keren One Section Card) -->
                    <?php if (Auth::can('master.suppliers_manage')): ?>
                    <div style="padding:14px 16px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;flex-direction:column;gap:12px;">
                        <div class="flex items-center justify-between gap-2 border-b pb-2" style="border-color:var(--color-hairline);">
                            <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:var(--color-primary);display:flex;align-items:center;gap:6px;">
                                <i data-lucide="plus-circle" style="width:14px;height:14px;"></i>
                                <span x-text="catalogForm.item_id && catalogItems.find(x => x.item_id === catalogForm.item_id) ? 'Perbarui Harga / Ketentuan Bahan di Vendor' : '1. Daftarkan Bahan Baru ke Vendor Ini'"></span>
                            </div>
                            <template x-if="catalogForm.item_id">
                                <button type="button" @click="resetCatalogForm()" class="btn btn-ghost btn-sm" style="font-size:11px;padding:2px 8px;color:var(--color-ink-mute);">
                                    <i data-lucide="rotate-ccw" style="width:12px;height:12px;"></i>
                                    <span>Reset Form</span>
                                </button>
                            </template>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                            <!-- Dropdown Bahan (Searchable Combobox) (5 cols) -->
                            <div class="sm:col-span-5 relative" x-data="{
                                openDropdown: false,
                                itemSearchQuery: '',
                                get selectedItemObj() {
                                    return availableItems.find(x => x.id === catalogForm.item_id) || null;
                                }
                            }" @click.away="openDropdown = false">
                                <label class="form-label" style="font-size:11.5px;margin-bottom:4px;">
                                    Pilih Bahan Mentah / Kemasan / Maklon <span style="color:var(--color-danger);">*</span>
                                </label>
                                
                                <!-- Combobox Trigger Button -->
                                <div @click="if (!catalogFormSaving) { openDropdown = !openDropdown; if (openDropdown) { $nextTick(() => { $refs.itemSearchInput?.focus(); if (window.lucide) lucide.createIcons(); }); } }"
                                     class="form-input flex items-center justify-between cursor-pointer transition-all"
                                     :style="catalogFormSaving ? 'opacity:0.6;pointer-events:none;' : (openDropdown ? 'border-color:var(--color-primary);box-shadow:0 0 0 3px rgba(136,19,55,0.12);' : '')"
                                     style="height:38px;padding:6px 12px;font-size:12px;user-select:none;background:var(--color-canvas);border-radius:8px;">
                                    
                                    <div class="truncate flex items-center gap-2 flex-1 min-w-0 pr-1">
                                        <template x-if="selectedItemObj">
                                            <div class="flex items-center gap-1.5 truncate">
                                                <span class="badge badge-mono text-[10px]" style="padding:1px 6px;font-weight:700;" x-text="selectedItemObj.kode_sku"></span>
                                                <span class="font-bold truncate text-[12.5px]" style="color:var(--color-ink);" x-text="selectedItemObj.nama_item"></span>
                                                <span class="badge badge-secondary text-[10px]" style="padding:1px 5px;" x-text="selectedItemObj.satuan_dasar"></span>
                                            </div>
                                        </template>
                                        <template x-if="!selectedItemObj">
                                            <span style="color:var(--color-ink-mute);font-size:12px;font-weight:500;">-- Cari &amp; Pilih Item Bahan --</span>
                                        </template>
                                    </div>

                                    <div class="flex items-center gap-1.5 text-slate-400 flex-shrink-0">
                                        <template x-if="catalogForm.item_id">
                                            <button type="button" @click.stop="resetCatalogForm(); itemSearchQuery = '';" class="btn-ghost p-1 rounded hover:text-rose-600 dark:hover:text-rose-400 transition-colors" title="Hapus Pilihan">
                                                <i data-lucide="x" style="width:13px;height:13px;"></i>
                                            </button>
                                        </template>
                                        <i data-lucide="chevron-down" style="width:14px;height:14px;color:var(--color-ink-mute);" :style="openDropdown ? 'transform:rotate(180deg);transition:transform 0.2s;' : 'transition:transform 0.2s;'"></i>
                                    </div>
                                </div>

                                <!-- Combobox Popover (Executive UI) -->
                                <div x-show="openDropdown" x-cloak
                                     style="position:absolute;top:calc(100% + 6px);left:0;right:0;min-width:320px;z-index:90;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:12px;box-shadow:0 12px 30px -6px rgba(0,0,0,0.22), 0 8px 12px -6px rgba(0,0,0,0.12);max-height:300px;display:flex;flex-direction:column;overflow:hidden;">
                                    
                                    <!-- Search Input Header -->
                                    <div style="padding:10px 12px;border-bottom:1px solid var(--color-hairline);background:var(--color-canvas-soft);display:flex;align-items:center;gap:8px;">
                                        <div class="relative flex-1">
                                            <i data-lucide="search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--color-ink-mute);pointer-events:none;"></i>
                                            <input type="text" x-ref="itemSearchInput" x-model="itemSearchQuery"
                                                   placeholder="Ketik nama bahan atau kode SKU..."
                                                   @keydown.escape="openDropdown = false"
                                                   class="form-input text-xs"
                                                   style="height:32px;padding-left:30px;padding-right:26px;border-radius:8px;width:100%;font-size:12px;">
                                            <button type="button" x-show="itemSearchQuery" @click="itemSearchQuery = ''" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);color:var(--color-ink-mute);padding:2px;cursor:pointer;">
                                                <i data-lucide="x" style="width:12px;height:12px;"></i>
                                            </button>
                                        </div>
                                        <div class="text-[10.5px] font-mono font-bold text-slate-400 whitespace-nowrap" x-text="getAllSelectableItems(itemSearchQuery).length + ' opsi'"></div>
                                    </div>

                                    <!-- Options Scroll Area -->
                                    <div class="overflow-y-auto custom-scrollbar flex-1" style="max-height:240px;padding:8px;">
                                        
                                        <!-- Group 1: Bahan Mentah Curah -->
                                        <template x-if="getSelectableItems('bahan_mentah', itemSearchQuery).length > 0">
                                            <div style="margin-bottom:8px;">
                                                <div style="padding:6px 8px 6px 8px;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:var(--color-primary);display:flex;align-items:center;justify-content:space-between;">
                                                    <div class="flex items-center gap-1.5">
                                                        <i data-lucide="boxes" style="width:13px;height:13px;"></i>
                                                        <span>Bahan Mentah Curah</span>
                                                    </div>
                                                    <span class="badge badge-mono" style="font-size:9.5px;padding:1px 6px;background:rgba(136,19,55,0.08);color:var(--color-primary);" x-text="getSelectableItems('bahan_mentah', itemSearchQuery).length + ' bahan'"></span>
                                                </div>
                                                <div class="space-y-1">
                                                    <template x-for="it in getSelectableItems('bahan_mentah', itemSearchQuery)" :key="it.id">
                                                        <div @click="selectCatalogItem(it); openDropdown = false; itemSearchQuery = '';"
                                                             class="cursor-pointer flex items-center justify-between text-xs transition-all group"
                                                             style="padding:8px 12px;border-radius:8px;border:1px solid transparent;"
                                                             :style="catalogForm.item_id === it.id ? 'background:rgba(136,19,55,0.08);border-color:var(--color-primary);font-weight:700;' : 'background:transparent;'"
                                                             :class="catalogForm.item_id === it.id ? '' : 'hover:bg-rose-50/80 dark:hover:bg-slate-800/80 hover:border-rose-200 dark:hover:border-slate-700'">
                                                            <div class="truncate flex items-center gap-2.5 flex-1 min-w-0 pr-2">
                                                                <span class="badge badge-mono text-[10px] font-bold flex-shrink-0" style="padding:2px 6px;" x-text="it.kode_sku"></span>
                                                                <span class="font-semibold text-slate-800 dark:text-slate-100 truncate group-hover:text-rose-700 dark:group-hover:text-rose-400 text-[12.5px]" x-text="it.nama_item"></span>
                                                            </div>
                                                            <span class="badge badge-secondary text-[10px] flex-shrink-0 font-mono font-bold" style="padding:2px 7px;text-transform:uppercase;" x-text="it.satuan_dasar"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Group 2: Bahan Kemas & Perlengkapan -->
                                        <template x-if="getSelectableItems('bahan_kemas', itemSearchQuery).length > 0">
                                            <div style="margin-bottom:8px;">
                                                <div style="padding:6px 8px 6px 8px;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:#0284c7;display:flex;align-items:center;justify-content:space-between;">
                                                    <div class="flex items-center gap-1.5">
                                                        <i data-lucide="package" style="width:13px;height:13px;"></i>
                                                        <span>Bahan Kemasan &amp; Perlengkapan</span>
                                                    </div>
                                                    <span class="badge badge-mono" style="font-size:9.5px;padding:1px 6px;background:rgba(2,132,199,0.08);color:#0284c7;" x-text="getSelectableItems('bahan_kemas', itemSearchQuery).length + ' bahan'"></span>
                                                </div>
                                                <div class="space-y-1">
                                                    <template x-for="it in getSelectableItems('bahan_kemas', itemSearchQuery)" :key="it.id">
                                                        <div @click="selectCatalogItem(it); openDropdown = false; itemSearchQuery = '';"
                                                             class="cursor-pointer flex items-center justify-between text-xs transition-all group"
                                                             style="padding:8px 12px;border-radius:8px;border:1px solid transparent;"
                                                             :style="catalogForm.item_id === it.id ? 'background:rgba(136,19,55,0.08);border-color:var(--color-primary);font-weight:700;' : 'background:transparent;'"
                                                             :class="catalogForm.item_id === it.id ? '' : 'hover:bg-rose-50/80 dark:hover:bg-slate-800/80 hover:border-rose-200 dark:hover:border-slate-700'">
                                                            <div class="truncate flex items-center gap-2.5 flex-1 min-w-0 pr-2">
                                                                <span class="badge badge-mono text-[10px] font-bold flex-shrink-0" style="padding:2px 6px;" x-text="it.kode_sku"></span>
                                                                <span class="font-semibold text-slate-800 dark:text-slate-100 truncate group-hover:text-rose-700 dark:group-hover:text-rose-400 text-[12.5px]" x-text="it.nama_item"></span>
                                                            </div>
                                                            <span class="badge badge-secondary text-[10px] flex-shrink-0 font-mono font-bold" style="padding:2px 7px;text-transform:uppercase;" x-text="it.satuan_dasar"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Group 3: Barang Jadi / Maklon -->
                                        <template x-if="getSelectableItems('barang_jadi', itemSearchQuery).length > 0">
                                            <div style="margin-bottom:8px;">
                                                <div style="padding:6px 8px 6px 8px;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:#059669;display:flex;align-items:center;justify-content:space-between;">
                                                    <div class="flex items-center gap-1.5">
                                                        <i data-lucide="tag" style="width:13px;height:13px;"></i>
                                                        <span>Barang Jadi / Maklon Vendor</span>
                                                    </div>
                                                    <span class="badge badge-mono" style="font-size:9.5px;padding:1px 6px;background:rgba(5,150,105,0.08);color:#059669;" x-text="getSelectableItems('barang_jadi', itemSearchQuery).length + ' produk'"></span>
                                                </div>
                                                <div class="space-y-1">
                                                    <template x-for="it in getSelectableItems('barang_jadi', itemSearchQuery)" :key="it.id">
                                                        <div @click="selectCatalogItem(it); openDropdown = false; itemSearchQuery = '';"
                                                             class="cursor-pointer flex items-center justify-between text-xs transition-all group"
                                                             style="padding:8px 12px;border-radius:8px;border:1px solid transparent;"
                                                             :style="catalogForm.item_id === it.id ? 'background:rgba(136,19,55,0.08);border-color:var(--color-primary);font-weight:700;' : 'background:transparent;'"
                                                             :class="catalogForm.item_id === it.id ? '' : 'hover:bg-rose-50/80 dark:hover:bg-slate-800/80 hover:border-rose-200 dark:hover:border-slate-700'">
                                                            <div class="truncate flex items-center gap-2.5 flex-1 min-w-0 pr-2">
                                                                <span class="badge badge-mono text-[10px] font-bold flex-shrink-0" style="padding:2px 6px;" x-text="it.kode_sku"></span>
                                                                <span class="font-semibold text-slate-800 dark:text-slate-100 truncate group-hover:text-rose-700 dark:group-hover:text-rose-400 text-[12.5px]" x-text="it.nama_item"></span>
                                                            </div>
                                                            <span class="badge badge-secondary text-[10px] flex-shrink-0 font-mono font-bold" style="padding:2px 7px;text-transform:uppercase;" x-text="it.satuan_dasar"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Empty / No matches state -->
                                        <template x-if="getAllSelectableItems(itemSearchQuery).length === 0">
                                            <div style="padding:28px 16px;text-align:center;color:var(--color-ink-mute);font-size:12px;">
                                                <div style="width:36px;height:36px;border-radius:10px;background:var(--color-canvas-soft);color:var(--color-ink-mute);display:inline-flex;align-items:center;justify-content:center;margin-bottom:6px;">
                                                    <i data-lucide="inbox" style="width:18px;height:18px;"></i>
                                                </div>
                                                <template x-if="itemSearchQuery">
                                                    <div class="font-semibold text-slate-700 dark:text-slate-300">Tidak ada item yang cocok dengan "<b><span x-text="itemSearchQuery"></span></b>"</div>
                                                </template>
                                                <template x-if="!itemSearchQuery">
                                                    <div class="font-semibold text-slate-700 dark:text-slate-300">Semua item bahan &amp; produk sudah dikaitkan ke vendor ini.</div>
                                                </template>
                                                <div class="text-[11px] text-slate-400 mt-1">Gunakan tabel di bawah untuk mengubah harga atau menghapus bahan.</div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Input Harga Beli (3 cols) -->
                            <div class="sm:col-span-3">
                                <label class="form-label" style="font-size:11.5px;margin-bottom:4px;">
                                    Harga Beli Satuan (Rp) <span style="color:var(--color-danger);">*</span>
                                </label>
                                <div class="relative flex items-center">
                                    <span style="position:absolute;left:10px;font-size:11.5px;font-weight:700;color:var(--color-ink-mute);pointer-events:none;">Rp</span>
                                    <input type="number" step="any" min="0" x-model.number="catalogForm.harga_beli" class="form-input font-mono font-bold" style="height:38px;font-size:12.5px;padding-left:32px;text-align:right;" placeholder="0" :disabled="catalogFormSaving">
                                </div>
                            </div>

                            <!-- Input Catatan / Min Order (2 cols) -->
                            <div class="sm:col-span-2">
                                <label class="form-label" style="font-size:11.5px;margin-bottom:4px;">
                                    Catatan / Min Order
                                </label>
                                <input type="text" x-model="catalogForm.catatan" class="form-input" style="height:38px;font-size:12px;" placeholder="Misal: Min 5 bal" :disabled="catalogFormSaving">
                            </div>

                            <!-- Tombol Aksi Simpan (2 cols) -->
                            <div class="sm:col-span-2">
                                <button type="button" @click="saveCatalog()" class="btn btn-primary w-full flex items-center justify-center gap-1.5" style="height:38px;font-size:12px;font-weight:700;" :disabled="catalogFormSaving || !catalogForm.item_id" title="Simpan ke Katalog">
                                    <template x-if="!catalogFormSaving">
                                        <div class="flex items-center gap-1.5">
                                            <i data-lucide="check" style="width:15px;height:15px;"></i>
                                            <span x-text="catalogForm.item_id && catalogItems.find(x => x.item_id === catalogForm.item_id) ? 'Update' : 'Daftarkan'"></span>
                                        </div>
                                    </template>
                                    <template x-if="catalogFormSaving">
                                        <div class="flex items-center gap-1.5">
                                            <i data-lucide="loader-2" class="animate-spin" style="width:15px;height:15px;"></i>
                                            <span>Menyimpan...</span>
                                        </div>
                                    </template>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 3. Tabel Daftar Bahan Vendor (Keren One Data Table) -->
                    <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);overflow:hidden;">
                        
                        <!-- Table Top Controls -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 border-b" style="border-color:var(--color-hairline);background:var(--color-canvas-soft);">
                            <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:var(--color-primary);display:flex;align-items:center;gap:6px;">
                                <i data-lucide="list" style="width:14px;height:14px;"></i>
                                <span>2. Rincian Bahan yang Dipasok Vendor</span>
                            </div>
                            
                            <div class="relative flex-1 sm:max-w-xs">
                                <i data-lucide="search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--color-ink-mute);pointer-events:none;"></i>
                                <input type="text" x-model="catalogSearch" placeholder="Cari bahan di vendor ini..." class="form-input text-xs" style="height:32px;padding-left:30px;width:100%;border-radius:8px;">
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="overflow-x-auto">
                            <table class="data-table" style="min-width:100%;">
                                <thead>
                                    <tr>
                                        <th style="min-width:200px;">Nama Bahan Baku / Kemasan</th>
                                        <th style="width:105px;" class="cell-nowrap">Kategori</th>
                                        <th style="width:80px;" class="cell-nowrap cell-center">Satuan</th>
                                        <th style="width:140px;text-align:right;" class="cell-nowrap">Harga Beli Vendor</th>
                                        <th style="min-width:140px;">Catatan / Ketentuan</th>
                                        <?php if (Auth::can('master.suppliers_manage')): ?>
                                        <th class="cell-center cell-nowrap" style="width:80px;">Aksi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in filteredCatalogItems" :key="item.catalog_id">
                                        <tr>
                                            <td>
                                                <div class="font-bold text-xs" style="color:var(--color-ink);" x-text="item.nama_item"></div>
                                                <div class="text-[10.5px] font-mono text-slate-400" x-text="item.kode_sku"></div>
                                            </td>
                                            <td class="cell-nowrap">
                                                <span :class="{
                                                    'badge badge-warning': item.tipe_item === 'bahan_mentah',
                                                    'badge badge-secondary': item.tipe_item === 'bahan_kemas',
                                                    'badge badge-success': item.tipe_item === 'barang_jadi'
                                                }" style="font-size:10px;padding:1px 6px;font-weight:700;" x-text="item.tipe_item === 'bahan_mentah' ? 'Curah Mentah' : (item.tipe_item === 'bahan_kemas' ? 'Bahan Kemas' : 'Maklon / Jadi')"></span>
                                            </td>
                                            <td class="cell-nowrap cell-center">
                                                <span class="badge badge-mono text-[10px]" style="text-transform:uppercase;" x-text="item.satuan_dasar"></span>
                                            </td>
                                            <td style="text-align:right;" class="cell-nowrap">
                                                <span class="font-bold text-xs font-mono" style="color:var(--color-success);" x-text="'Rp ' + Number(item.harga_beli).toLocaleString('id-ID')"></span>
                                            </td>
                                            <td>
                                                <span class="text-xs" style="color:var(--color-ink-mute);" x-text="item.catatan || '-'"></span>
                                            </td>
                                            <?php if (Auth::can('master.suppliers_manage')): ?>
                                            <td class="cell-center cell-nowrap">
                                                <div class="flex items-center justify-center gap-1">
                                                    <button type="button" @click="editCatalogItem(item)" class="btn btn-ghost btn-sm" style="padding:5px;" title="Edit Harga / Catatan">
                                                        <i data-lucide="edit-3" style="width:13px;height:13px;"></i>
                                                    </button>
                                                    <button type="button" @click="deleteCatalog(item)" class="btn btn-ghost btn-sm" style="padding:5px;color:#ef4444;" title="Hapus dari Katalog">
                                                        <i data-lucide="trash-2" style="width:13px;height:13px;"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                    </template>

                                    <template x-if="filteredCatalogItems.length === 0">
                                        <tr>
                                            <td colspan="<?= Auth::can('master.suppliers_manage') ? 6 : 5 ?>" style="text-align:center;padding:36px 16px;color:var(--color-ink-mute);">
                                                <div style="width:44px;height:44px;border-radius:12px;background:var(--color-canvas-soft);color:var(--color-ink-mute);display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;">
                                                    <i data-lucide="inbox" style="width:22px;height:22px;"></i>
                                                </div>
                                                <div style="font-weight:700;font-size:13px;color:var(--color-ink);">Belum ada bahan di katalog vendor ini</div>
                                                <div style="font-size:11.5px;margin-top:2px;color:var(--color-ink-mute);">Gunakan formulir di atas untuk menghubungkan bahan baku atau kemasan yang dipasok oleh vendor.</div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Modal Footer (Keren One Canonical) -->
            <div class="modal-footer flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5" style="padding:14px 20px; border-top:1px solid var(--color-hairline); background:var(--color-canvas-soft);">
                <div class="flex items-center gap-2 text-[11.5px]" style="color:var(--color-ink-mute);">
                    <i data-lucide="info" style="width:14px;height:14px;color:var(--color-primary);flex-shrink:0;"></i>
                    <span>Harga beli di katalog ini akan otomatis muncul saat membuat PO Pembelian untuk vendor ini.</span>
                </div>
                <button type="button" @click="showCatalogModal = false" class="btn btn-secondary w-full sm:w-auto" style="font-weight:700;min-width:90px;">Tutup</button>
            </div>
        </div>
    </div>
    </template>

    <!-- FORM DELETE HIDDEN -->
    <form id="delete-supplier-form" action="<?= Router::url('/suppliers/delete') ?>" method="POST" style="display:none;">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="id" id="delete-supplier-id">
    </form>
    <?php endif; ?>

</div>

<script>
function supplierApp() {
    return {
        suppliers: <?= json_encode($suppliers) ?>,
        searchQuery: <?= json_encode($pagination['q'] ?? '') ?>,
        isSearching: false,
        serverPagination: <?= json_encode($pagination ?? ['page' => 1, 'totalPages' => 1, 'total' => count($suppliers), 'perPage' => 50, 'q' => '']) ?>,
        showModal: false,
        isEdit: false,
        form: {
            id: '',
            nama_pemasok: '',
            nama_kontak: '',
            nomor_whatsapp: '',
            email: '',
            wilayah_id: '',
            alamat_lengkap: '',
            link_google_maps: '',
            termin_bayar: 'cash',
            catatan: '',
            bank_nama: '',
            bank_rekening: '',
            bank_atas_nama: '',
            status_aktif: true
        },

        // Vendor Catalog State
        showCatalogModal: false,
        loadingCatalog: false,
        catalogSupplier: null,
        catalogItems: [],
        availableItems: [],
        catalogSearch: '',
        catalogFormSaving: false,
        catalogForm: {
            item_id: '',
            harga_beli: 0,
            kode_sku_vendor: '',
            catatan: '',
            status_aktif: true
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        cleanWa(num) {
            if (!num) return '';
            let clean = num.replace(/[^0-9]/g, '');
            if (clean.startsWith('0')) {
                clean = '62' + clean.substring(1);
            }
            return clean;
        },

        formatTermin(termin) {
            const map = {
                'cash': 'Tunai / COD',
                'transfer': 'Transfer Bank',
                'tempo_7_hari': 'Tempo 7 Hari',
                'tempo_14_hari': 'Tempo 14 Hari',
                'tempo_30_hari': 'Tempo 30 Hari'
            };
            return map[termin] || 'Tunai / COD';
        },

        async fetchSuppliers(page = 1) {
            this.isSearching = true;
            try {
                const q = (this.searchQuery || '').trim();
                const params = new URLSearchParams({
                    ajax_search: '1',
                    q: q,
                    page: String(page),
                    per_page: '50'
                });
                const res = await fetch('<?= Router::url("/suppliers") ?>?' + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.status === 'success') {
                        this.suppliers = data.suppliers || [];
                        this.serverPagination = data.pagination;
                        // Update URL bar secara silent tanpa refresh halaman
                        const url = new URL(window.location.href);
                        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
                        if (page > 1) url.searchParams.set('page', String(page)); else url.searchParams.delete('page');
                        window.history.replaceState(null, '', url.toString());
                    }
                }
            } catch (err) {
                console.error('Gagal memuat live search pemasok:', err);
            } finally {
                this.isSearching = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        clearSearch() {
            this.searchQuery = '';
            this.fetchSuppliers(1);
        },

        get filteredSuppliers() {
            return this.suppliers.filter(s => {
                const q = (this.searchQuery || '').toLowerCase().trim();
                if (!q) return true;
                return (s.nama_pemasok && s.nama_pemasok.toLowerCase().includes(q)) ||
                    (s.kode_pemasok && s.kode_pemasok.toLowerCase().includes(q)) ||
                    (s.nama_kontak && s.nama_kontak.toLowerCase().includes(q)) ||
                    (s.nomor_whatsapp && s.nomor_whatsapp.toLowerCase().includes(q)) ||
                    (s.email && s.email.toLowerCase().includes(q)) ||
                    (s.nama_bank && s.nama_bank.toLowerCase().includes(q)) ||
                    (s.nomor_rekening && s.nomor_rekening.toLowerCase().includes(q)) ||
                    (s.atas_nama_rekening && s.atas_nama_rekening.toLowerCase().includes(q)) ||
                    (s.nama_wilayah && s.nama_wilayah.toLowerCase().includes(q)) ||
                    (s.alamat_lengkap && s.alamat_lengkap.toLowerCase().includes(q)) ||
                    (s.catatan && s.catatan.toLowerCase().includes(q));
            });
        },

        openAddModal() {
            this.isEdit = false;
            this.form = {
                id: '',
                nama_pemasok: '',
                nama_kontak: '',
                nomor_whatsapp: '',
                email: '',
                wilayah_id: '',
                alamat_lengkap: '',
                link_google_maps: '',
                termin_bayar: 'cash',
                catatan: '',
                bank_nama: '',
                bank_rekening: '',
                bank_atas_nama: '',
                status_aktif: true
            };
            this.showModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditModal(s) {
            this.isEdit = true;
            this.form = {
                id: s.id,
                nama_pemasok: s.nama_pemasok || '',
                nama_kontak: s.nama_kontak || '',
                nomor_whatsapp: s.nomor_whatsapp || '',
                email: s.email || '',
                wilayah_id: s.wilayah_id || '',
                alamat_lengkap: s.alamat_lengkap || '',
                link_google_maps: s.link_google_maps || '',
                termin_bayar: s.termin_bayar || 'cash',
                catatan: s.catatan || '',
                bank_nama: s.nama_bank || '',
                bank_rekening: s.nomor_rekening || '',
                bank_atas_nama: s.atas_nama_rekening || '',
                status_aktif: Boolean(s.status_aktif)
            };
            this.showModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async deleteSupplier(id, name) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Vendor Pemasok',
                message: `Apakah Anda yakin ingin menghapus pemasok "${name}"? Pemasok yang memiliki riwayat transaksi faktur pembelian tidak dapat dihapus.`,
                type: 'danger',
                confirmText: 'Ya, Hapus'
            }) : confirm(`Hapus pemasok "${name}"?`);

            if (confirmed) {
                document.getElementById('delete-supplier-id').value = id;
                document.getElementById('delete-supplier-form').submit();
            }
        },

        // ==========================================
        // VENDOR CATALOG METHODS
        // ==========================================
        async openCatalogModal(supplier) {
            this.catalogSupplier = supplier;
            this.showCatalogModal = true;
            this.loadingCatalog = true;
            this.resetCatalogForm();
            this.$nextTick(() => lucide.createIcons());

            try {
                const res = await fetch('<?= Router::url("/suppliers/catalog") ?>?pemasok_id=' + encodeURIComponent(supplier.id), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.status === 'success') {
                        this.catalogItems = data.catalog || [];
                        this.availableItems = data.available_items || [];
                    }
                }
            } catch (err) {
                console.error('Gagal memuat katalog:', err);
                if (window.Toast) window.Toast.error('Gagal memuat katalog vendor');
            } finally {
                this.loadingCatalog = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        resetCatalogForm() {
            this.catalogForm = {
                item_id: '',
                harga_beli: 0,
                kode_sku_vendor: '',
                catatan: '',
                status_aktif: true
            };
        },

        getSelectableItems(type, search) {
            const linkedItemIds = this.catalogItems.map(c => c.item_id);
            const q = (search || '').toLowerCase().trim();
            
            return this.availableItems.filter(it => {
                if (it.tipe_item !== type) return false;
                
                // Item must NOT already be linked to this vendor (unless currently editing this item)
                const isCurrentlySelected = this.catalogForm.item_id === it.id;
                const isAlreadyLinked = linkedItemIds.includes(it.id);
                if (isAlreadyLinked && !isCurrentlySelected) return false;
                
                if (!q) return true;
                return (it.nama_item && it.nama_item.toLowerCase().includes(q)) ||
                       (it.kode_sku && it.kode_sku.toLowerCase().includes(q));
            });
        },

        getAllSelectableItems(search) {
            const linkedItemIds = this.catalogItems.map(c => c.item_id);
            const q = (search || '').toLowerCase().trim();
            
            return this.availableItems.filter(it => {
                const isCurrentlySelected = this.catalogForm.item_id === it.id;
                const isAlreadyLinked = linkedItemIds.includes(it.id);
                if (isAlreadyLinked && !isCurrentlySelected) return false;
                
                if (!q) return true;
                return (it.nama_item && it.nama_item.toLowerCase().includes(q)) ||
                       (it.kode_sku && it.kode_sku.toLowerCase().includes(q));
            });
        },

        selectCatalogItem(it) {
            this.catalogForm.item_id = it.id;
            this.onCatalogItemSelected();
        },

        onCatalogItemSelected() {
            const itemId = this.catalogForm.item_id;
            if (!itemId) return;

            // Jika item sudah ada di katalog vendor, load harga & catatannya
            const existingInCatalog = this.catalogItems.find(x => x.item_id === itemId);
            if (existingInCatalog) {
                this.catalogForm.harga_beli = parseFloat(existingInCatalog.harga_beli) || 0;
                this.catalogForm.kode_sku_vendor = existingInCatalog.kode_sku_vendor || '';
                this.catalogForm.catatan = existingInCatalog.catatan || '';
                return;
            }

            // Jika belum ada, gunakan HPP standar master item sebagai nilai default
            const masterItem = this.availableItems.find(x => x.id === itemId);
            if (masterItem) {
                this.catalogForm.harga_beli = parseFloat(masterItem.harga_pokok_pembelian) || 0;
            }
        },

        editCatalogItem(item) {
            this.catalogForm = {
                item_id: item.item_id,
                harga_beli: parseFloat(item.harga_beli) || 0,
                kode_sku_vendor: item.kode_sku_vendor || '',
                catatan: item.catatan || '',
                status_aktif: Boolean(item.status_aktif)
            };
        },

        get filteredCatalogItems() {
            const q = (this.catalogSearch || '').toLowerCase().trim();
            if (!q) return this.catalogItems;
            return this.catalogItems.filter(i => {
                return (i.nama_item && i.nama_item.toLowerCase().includes(q)) ||
                       (i.kode_sku && i.kode_sku.toLowerCase().includes(q)) ||
                       (i.catatan && i.catatan.toLowerCase().includes(q)) ||
                       (i.satuan_dasar && i.satuan_dasar.toLowerCase().includes(q));
            });
        },

        async saveCatalog() {
            if (!this.catalogSupplier || !this.catalogForm.item_id) return;

            this.catalogFormSaving = true;
            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= \App\Helpers\CSRF::token() ?>');
                formData.append('pemasok_id', this.catalogSupplier.id);
                formData.append('item_id', this.catalogForm.item_id);
                formData.append('harga_beli', this.catalogForm.harga_beli);
                formData.append('kode_sku_vendor', this.catalogForm.kode_sku_vendor || '');
                formData.append('catatan', this.catalogForm.catatan || '');
                formData.append('status_aktif', this.catalogForm.status_aktif ? '1' : '0');

                const res = await fetch('<?= Router::url("/suppliers/catalog/save") ?>', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await res.json();
                if (data.status === 'success') {
                    if (window.Toast) window.Toast.success(data.message || 'Katalog berhasil disimpan');
                    // Reload data katalog
                    await this.reloadCatalogItems();
                    this.resetCatalogForm();
                } else {
                    if (window.Toast) window.Toast.error(data.message || 'Gagal menyimpan katalog');
                }
            } catch (err) {
                console.error('Gagal simpan katalog:', err);
                if (window.Toast) window.Toast.error('Terjadi kesalahan saat menyimpan katalog');
            } finally {
                this.catalogFormSaving = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        async deleteCatalog(item) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus dari Katalog',
                message: `Hapus bahan "${item.nama_item}" dari katalog ${this.catalogSupplier.nama_pemasok}?`,
                type: 'warning',
                confirmText: 'Ya, Hapus'
            }) : confirm(`Hapus "${item.nama_item}" dari katalog?`);

            if (!confirmed) return;

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= \App\Helpers\CSRF::token() ?>');
                formData.append('id', item.catalog_id);

                const res = await fetch('<?= Router::url("/suppliers/catalog/delete") ?>', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await res.json();
                if (data.status === 'success') {
                    if (window.Toast) window.Toast.success(data.message || 'Item dihapus dari katalog');
                    await this.reloadCatalogItems();
                } else {
                    if (window.Toast) window.Toast.error(data.message || 'Gagal menghapus item');
                }
            } catch (err) {
                console.error('Gagal hapus item katalog:', err);
                if (window.Toast) window.Toast.error('Gagal menghapus item katalog');
            } finally {
                this.$nextTick(() => lucide.createIcons());
            }
        },

        async reloadCatalogItems() {
            if (!this.catalogSupplier) return;
            const res = await fetch('<?= Router::url("/suppliers/catalog") ?>?pemasok_id=' + encodeURIComponent(this.catalogSupplier.id), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (res.ok) {
                const data = await res.json();
                if (data.status === 'success') {
                    this.catalogItems = data.catalog || [];
                }
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

<?php
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

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
            <div class="form-input-icon flex-1 sm:max-w-md">
                <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                <input type="text" x-model="searchQuery" placeholder="Cari nama, PIC, nomor WhatsApp, email, alamat..." class="form-input" style="height:38px;font-size:13px;">
            </div>

            <div class="text-xs" style="color:var(--color-ink-mute);font-weight:600;white-space:nowrap;">
                Menampilkan <span class="font-mono" style="font-weight:800;color:var(--color-primary);" x-text="filteredSuppliers.length"></span> dari <span class="font-mono" style="font-weight:700;color:var(--color-ink);" x-text="suppliers.length"></span> pemasok
            </div>
        </div>

        <!-- TABLE LIST -->
        <div class="overflow-x-auto custom-scrollbar">
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
                <tbody>
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

        <!-- PAGINATION BAR -->
        <?php if (!empty($pagination) && $pagination['totalPages'] > 1): ?>
        <div style="padding:12px 16px;background:var(--color-canvas-soft, #f8fafc);border-top:1px solid var(--color-hairline);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <div style="font-size:12px;color:var(--color-ink-mute);">
                Menampilkan Halaman <strong><?= $pagination['page'] ?></strong> dari <strong><?= $pagination['totalPages'] ?></strong> (Total <?= number_format($pagination['total'], 0, ',', '.') ?> pemasok)
            </div>
            <div style="display:flex;gap:6px;">
                <?php if ($pagination['page'] > 1): ?>
                <a href="<?= Router::url('/suppliers?' . http_build_query(array_merge($_GET, ['page' => $pagination['page'] - 1]))) ?>" class="btn btn-secondary btn-sm" style="font-size:12px;">
                    &laquo; Sebelumnya
                </a>
                <?php endif; ?>
                <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                <a href="<?= Router::url('/suppliers?' . http_build_query(array_merge($_GET, ['page' => $pagination['page'] + 1]))) ?>" class="btn btn-secondary btn-sm" style="font-size:12px;">
                    Selanjutnya &raquo;
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL TAMBAH / EDIT PEMASOK LENGKAP                                       -->
    <!-- ========================================================================= -->
    <?php if (Auth::can('master.suppliers_manage')): ?>
    <template x-teleport="body">
    <div x-show="showModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:620px;max-height:90vh;overflow-y:auto;padding:24px;">
            <div class="modal-header" style="margin-bottom:18px;">
                <div>
                    <div class="modal-title" x-text="isEdit ? 'Edit Master Pemasok' : 'Tambah Pemasok Baru'"></div>
                    <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">Lengkapi data vendor, titik presisi peta, kontak PIC, dan syarat pembayaran</div>
                </div>
            </div>

            <form :action="isEdit ? '<?= Router::url('/suppliers/update') ?>' : '<?= Router::url('/suppliers/store') ?>'" method="POST" style="display:flex;flex-direction:column;gap:16px;">
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
                            <input type="text" name="bank_nama" x-model="form.bank_nama" class="form-input" placeholder="BCA / Mandiri / BRI">
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

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:10px;padding-top:12px;border-top:1px solid var(--color-hairline);">
                    <button type="button" @click="showModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight:700;">
                        <i data-lucide="save"></i>
                        <span x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Pemasok'"></span>
                    </button>
                </div>
            </form>
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
        searchQuery: '',
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

        get filteredSuppliers() {
            return this.suppliers.filter(s => {
                const q = this.searchQuery.toLowerCase();
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
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="deliveryApp()" x-init="init()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-blue">
                <i data-lucide="truck"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#3b82f6;"></span>
                    <span>Logistik &amp; Distribusi</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Status Pengiriman' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Manifest Rute Pengiriman &amp; Status Antar Toko' ?></p>
            </div>
        </div>
        <div class="page-header-actions">
            <a href="<?= Router::url('/guide#bab-8-logistik-pengiriman') ?>" 
               target="_blank"
               rel="noopener noreferrer"
               class="btn btn-secondary flex items-center gap-2"
               style="border-radius:12px; font-weight:700; text-decoration:none;"
               title="Buka Buku Panduan SOP Logistik & Surat Jalan di Tab Baru">
                <i data-lucide="book-open" class="w-4 h-4 text-blue-500"></i>
                <span>Panduan SOP Logistik</span>
            </a>
        </div>
    </div>

    <!-- STATS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="truck"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Surat Jalan</div>
                <div class="stat-card-value"><?= count($deliveries) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Manifest pengiriman</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                <i data-lucide="navigation"></i>
            </div>
            <div>
                <div class="stat-card-label">Sedang Dalam Rute</div>
                <div class="stat-card-value" style="color:#3b82f6;">
                    <?= count(array_filter($deliveries, fn($d) => in_array($d['status_surat_jalan'], ['siap_kirim', 'sedang_dikirim']))) ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Mobil delivery bergerak</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
                <i data-lucide="clock"></i>
            </div>
            <div>
                <div class="stat-card-label">Pesanan Belum Dikirim</div>
                <div class="stat-card-value" style="color:#f59e0b;"><?= count($pendingOrders) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Siap dibuatkan surat jalan</div>
            </div>
        </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card" style="padding:0;overflow:hidden;">

        <!-- FILTER & ACTION BAR -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="flex items-center gap-3 w-full sm:w-auto flex-1">
                <div class="form-input-icon flex-1 sm:max-w-xs">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="text" x-model="searchQuery" placeholder="Cari no SJ / toko / driver..." class="form-input" style="height:38px;font-size:13px;">
                </div>

                <select x-model="filterStatus" class="form-input" style="height:38px;font-size:13px;max-width:180px;">
                    <option value="all">Semua Status</option>
                    <option value="siap_kirim">Siap Kirim</option>
                    <option value="sedang_dikirim">Sedang Dikirim</option>
                    <option value="selesai_diterima">Selesai (Diterima)</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <?php if (Auth::can('deliveries.create')): ?>
                <button @click="openAddModal()" :disabled="pendingOrders.length === 0" class="btn btn-primary" style="height:38px;white-space:nowrap;">
                    <i data-lucide="plus"></i>
                    <span>Terbitkan Surat Jalan</span>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- TABLE LIST -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 860px;">
                <thead>
                    <tr>
                        <th style="width:160px; min-width:140px;" class="cell-nowrap">No. Surat Jalan</th>
                        <th style="min-width:180px;">Toko Tujuan</th>
                        <th style="min-width:160px;">Supir Pengantar</th>
                        <th style="min-width:130px;">Wilayah / Rute</th>
                        <?php if (Auth::can(['deliveries.view_all', 'deliveries.create', 'deliveries.update_all'])): ?>
                        <th class="cell-right cell-nowrap" style="width:140px; min-width:120px;">Total Nilai Nota</th>
                        <?php endif; ?>
                        <th class="cell-center cell-nowrap" style="width:140px; min-width:120px;">Status Pengiriman</th>
                        <th class="cell-center cell-nowrap" style="width:110px; min-width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="d in filteredDeliveries" :key="d.id">
                        <tr>
                            <td class="cell-nowrap">
                                <span class="badge badge-mono" x-text="d.nomor_surat_jalan"></span>
                                <div style="font-size:11px;font-family:var(--font-mono);color:var(--color-ink-mute);margin-top:2px;" x-text="d.nomor_nota"></div>
                                <div class="flex items-center gap-1.5" style="font-size:11px;color:var(--color-ink-secondary);margin-top:4px;" title="Tanggal Rencana Pengiriman">
                                    <i data-lucide="calendar" style="width:12px;height:12px;color:var(--color-primary);flex-shrink:0;"></i>
                                    <span style="font-weight:600;" x-text="formatDateIndo(d.tanggal_surat_jalan || d.dibuat_pada)"></span>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <div style="font-weight:700;color:var(--color-ink);" x-text="d.nama_toko"></div>
                                        <div style="font-size:11px;color:var(--color-ink-mute);" x-text="d.alamat_toko"></div>
                                    </div>
                                    <template x-if="d.nomor_whatsapp">
                                        <a :href="'https://wa.me/' + d.nomor_whatsapp.replace(/[^0-9]/g, '')" target="_blank" class="btn btn-ghost btn-xs text-emerald-400 p-1" title="Chat WhatsApp Toko">
                                            <i data-lucide="message-circle" style="width:14px;height:14px;"></i>
                                        </a>
                                    </template>
                                </div>
                            </td>
                            <td class="cell-nowrap">
                                <div style="font-weight:700;color:var(--color-ink);" x-text="d.nama_driver || 'Belum diassign'"></div>
                                <div style="display:flex;align-items:center;gap:6px;margin-top:2px;flex-wrap:wrap;">
                                    <span style="font-size:11px;font-family:var(--font-mono);color:var(--color-ink-mute);" x-text="d.telp_driver || ''"></span>
                                    <template x-if="d.nopol_driver">
                                        <span class="badge badge-mono flex items-center gap-1" style="font-size:10px;padding:1px 5px;color:#0284c7;background:rgba(2,132,199,0.08);border-color:rgba(2,132,199,0.3);">
                                            <i data-lucide="truck" style="width:10px;height:10px;"></i>
                                            <span x-text="d.nopol_driver"></span>
                                        </span>
                                    </template>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600;" x-text="d.nama_wilayah || '-'"></div>
                                <div style="font-size:10.5px;color:var(--color-primary-deep);" x-text="d.kode_rute || ''"></div>
                            </td>
                            <?php if (Auth::can(['deliveries.view_all', 'deliveries.create', 'deliveries.update_all'])): ?>
                            <td class="cell-currency cell-right cell-nowrap" style="color:var(--color-primary-deep);font-weight:700;">
                                <template x-if="d.tipe_pembayaran === 'konsinyasi' || d.is_konsinyasi">
                                    <span class="badge" style="background:rgba(225,29,72,0.08);color:#e11d48;border:1px solid rgba(225,29,72,0.2);font-weight:800;font-size:10.5px;padding:3px 8px;border-radius:6px;">Konsinyasi</span>
                                </template>
                                <template x-if="d.tipe_pembayaran !== 'konsinyasi' && !d.is_konsinyasi">
                                    <span x-text="formatRupiah(d.total_netto)"></span>
                                </template>
                            </td>
                            <?php endif; ?>
                            <td class="cell-center cell-nowrap">
                                <template x-if="d.status_surat_jalan === 'siap_kirim'">
                                    <span class="badge badge-primary" style="display:inline-flex;align-items:center;justify-content:center;gap:5px;font-weight:700;font-size:11px;padding:3.5px 10px;line-height:1;">
                                        <i data-lucide="package" style="width:12px;height:12px;flex-shrink:0;"></i>
                                        <span>Siap Berangkat</span>
                                    </span>
                                </template>
                                <template x-if="d.status_surat_jalan === 'sedang_dikirim'">
                                    <span class="badge badge-info" style="display:inline-flex;align-items:center;justify-content:center;gap:5px;font-weight:700;font-size:11px;padding:3.5px 10px;line-height:1;">
                                        <i data-lucide="truck" style="width:12px;height:12px;flex-shrink:0;"></i>
                                        <span>Sedang Dikirim</span>
                                    </span>
                                </template>
                                <template x-if="d.status_surat_jalan === 'selesai_diterima'">
                                    <span class="badge badge-success" style="display:inline-flex;align-items:center;justify-content:center;gap:5px;font-weight:700;font-size:11px;padding:3.5px 10px;line-height:1;">
                                        <i data-lucide="check-circle-2" style="width:12px;height:12px;flex-shrink:0;"></i>
                                        <span>Selesai Diterima</span>
                                    </span>
                                </template>
                                <template x-if="d.status_surat_jalan === 'gagal_kembali' || d.status_surat_jalan === 'gagal_kirim' || d.status_surat_jalan === 'dibatalkan'">
                                    <span class="badge badge-danger" style="display:inline-flex;align-items:center;justify-content:center;gap:5px;font-weight:700;font-size:11px;padding:3.5px 10px;line-height:1;">
                                        <i data-lucide="alert-triangle" style="width:12px;height:12px;flex-shrink:0;"></i>
                                        <span>Gagal Kirim / Retur</span>
                                    </span>
                                </template>
                                <template x-if="!['siap_kirim', 'sedang_dikirim', 'selesai_diterima', 'gagal_kembali', 'gagal_kirim', 'dibatalkan'].includes(d.status_surat_jalan)">
                                    <span class="badge badge-secondary" style="display:inline-flex;align-items:center;justify-content:center;gap:5px;font-weight:700;font-size:11px;padding:3.5px 10px;line-height:1;" x-text="(d.status_surat_jalan || 'Draft').replace(/_/g, ' ')"></span>
                                </template>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <div style="display:flex;align-items:center;justify-content:center;gap:4px;">

                                    <?php if (Auth::can(['deliveries.create', 'deliveries.update_all'])): ?>
                                    <template x-if="!['selesai_diterima', 'gagal_kirim', 'gagal_kembali', 'dibatalkan'].includes(d.status_surat_jalan)">
                                        <button type="button" @click="openEditModal(d)" class="btn btn-ghost btn-sm" style="padding:6px 8px;color:var(--color-primary);" title="Ubah Driver & Tanggal Pengiriman">
                                            <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                        </button>
                                    </template>
                                    <?php endif; ?>

                                    <!-- Foto Bukti Serah Terima (Selesai) -->
                                    <template x-if="(d.bukti_terima_urls && d.bukti_terima_urls.length > 0) || d.bukti_terima_foto">
                                        <button type="button" 
                                                @click="openPhotoViewer(d.bukti_terima_urls || [d.bukti_terima_foto], 'Bukti Serah Terima - #' + d.nomor_surat_jalan, (d.nama_toko || '') + (d.nama_driver ? ' • Driver: ' + d.nama_driver : ''))" 
                                                class="btn btn-ghost btn-sm relative" 
                                                style="padding:6px 8px;color:#059669;" 
                                                title="Lihat Foto Bukti Serah Terima">
                                            <i data-lucide="image" style="width:14px;height:14px;"></i>
                                            <template x-if="d.bukti_terima_urls && d.bukti_terima_urls.length > 1">
                                                <span class="receipt-counter-badge" x-text="d.bukti_terima_urls.length"></span>
                                            </template>
                                        </button>
                                    </template>

                                    <!-- Foto Bukti Gagal Kirim -->
                                    <template x-if="(d.foto_gagal_urls && d.foto_gagal_urls.length > 0) || d.foto_bukti_gagal">
                                        <button type="button" 
                                                @click="openPhotoViewer(d.foto_gagal_urls || [d.foto_bukti_gagal], 'Bukti Gagal Kirim - #' + d.nomor_surat_jalan, (d.nama_toko || '') + (d.alasan_gagal ? ' • Kendala: ' + d.alasan_gagal : ''))" 
                                                class="btn btn-ghost btn-sm relative" 
                                                style="padding:6px 8px;color:#e11d48;" 
                                                title="Lihat Foto Bukti Kendala">
                                            <i data-lucide="image" style="width:14px;height:14px;"></i>
                                            <template x-if="d.foto_gagal_urls && d.foto_gagal_urls.length > 1">
                                                <span class="receipt-counter-badge" x-text="d.foto_gagal_urls.length"></span>
                                            </template>
                                        </button>
                                    </template>

                                    <?php if (Auth::can('deliveries.print')): ?>
                                    <a :href="'<?= Router::url('/deliveries/print') ?>?id=' + d.id" class="btn btn-ghost btn-sm" style="padding:6px 8px;color:#0284c7;" title="Cetak Surat Jalan (Standar / Dot Matrix)">
                                        <i data-lucide="printer" style="width:14px;height:14px;"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="filteredDeliveries.length === 0">
                        <tr>
                            <td colspan="7" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="search-x" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Belum ada data surat jalan pengiriman</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL 1: BUAT SURAT JALAN -->
    <?php if (Auth::can('deliveries.create')): ?>
    <template x-teleport="body">
    <div x-show="showAddModal" x-cloak class="modal-backdrop" @click="showAddModal = false" style="z-index:9999;">
        <div class="modal-box" style="max-width:520px;" @click.stop>
            <!-- Mobile Pull Handle -->
            <div class="modal-handle">
                <div class="modal-handle-bar"></div>
            </div>

            <div class="modal-header">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(59,130,246,0.12);color:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="truck" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="modal-title">Terbitkan Surat Jalan</div>
                        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:1px;">Manifest Pengiriman Toko</div>
                    </div>
                </div>
                <button type="button" @click="showAddModal = false" class="modal-close-x" title="Tutup Modal">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/deliveries/store') ?>" method="POST">
                <?= \App\Helpers\CSRF::field() ?>
                <div class="modal-body custom-scrollbar space-y-4">
                    <div>
                        <label class="form-label font-bold">Pilih Nota Pesanan Toko *</label>
                        <select name="pesanan_id" x-model="addSelectedPesananId" @change="onPesananChange()" required class="form-input searchable-select">
                            <option value="">-- Pilih Pesanan Menunggu Kirim --</option>
                            <?php foreach ($pendingOrders as $po): 
                                $isKonsinyasiPo = ($po['tipe_pembayaran'] === 'konsinyasi') || !empty($po['is_konsinyasi']);
                            ?>
                            <option value="<?= $po['id'] ?>">
                                <?= htmlspecialchars($po['nomor_nota']) ?> - <?= htmlspecialchars($po['nama_toko']) ?> <?= $isKonsinyasiPo ? '(Konsinyasi)' : '(Rp ' . number_format((float)$po['total_netto'], 0, ',', '.') . ')' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label font-bold">Tanggal Kirim / SJ *</label>
                            <input type="date" name="tanggal_surat_jalan" x-model="defaultDeliveryDate" required class="form-input font-medium" style="height:40px;">
                        </div>
                        <div>
                            <label class="form-label font-bold">Driver / Petugas Pengantar *</label>
                            <select name="sales_driver_id" x-model="addSelectedDriverId" required class="form-input" style="height:40px;">
                                <option value="">-- Pilih Driver / Petugas Pengantar --</option>
                                <?php foreach ($drivers as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nama_karyawan']) ?><?= !empty($d['nomor_polisi_kendaraan']) ? ' (' . htmlspecialchars($d['nomor_polisi_kendaraan']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="font-size:11.5px;color:var(--color-ink-mute);display:flex;align-items:center;gap:6px;padding:0 2px;">
                        <i data-lucide="clock" style="width:13px;height:13px;color:var(--color-primary);flex-shrink:0;"></i>
                        <span x-text="isAfternoon ? 'Dibuat siang/sore (>= 12:00 WIB): default tanggal diset untuk BESOK.' : 'Dibuat pagi (< 12:00 WIB): default tanggal diset untuk HARI INI.'"></span>
                    </div>

                    <div style="padding:10px 14px;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:10px;font-size:12px;color:#1e40af;display:flex;align-items:center;gap:8px;">
                        <i data-lucide="info" style="width:16px;height:16px;flex-shrink:0;"></i>
                        <span>Surat Jalan otomatis berstatus <strong>Siap Dikirim</strong> dan langsung dialokasikan ke jadwal rute driver.</span>
                    </div>
                    <input type="hidden" name="status_surat_jalan" value="siap_kirim">
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showAddModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                    <button type="submit" class="btn btn-primary w-full sm:w-auto" style="display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                        <i data-lucide="save" style="width:16px;height:16px;"></i>
                        <span>Terbitkan Dokumen</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- 4. MODAL UBAH SURAT JALAN (DRIVER & TANGGAL PENGIRIMAN)                  -->
    <!-- ========================================================================= -->
    <?php if (Auth::can(['deliveries.create', 'deliveries.update_all'])): ?>
    <template x-teleport="body">
    <div x-show="showEditModal" x-cloak class="modal-backdrop" @click="showEditModal = false" @keydown.escape.window="showEditModal = false" style="z-index:9999;">
        <div class="modal-box" style="max-width:520px;" @click.stop>
            <!-- Mobile Pull Handle -->
            <div class="modal-handle">
                <div class="modal-handle-bar"></div>
            </div>

            <div class="modal-header">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(37,99,235,0.1);color:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="edit-3" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="modal-title">Ubah Surat Jalan</div>
                        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:1px;">Ubah Pengemudi/Sales &amp; Tanggal Pengiriman</div>
                    </div>
                </div>
                <button type="button" @click="showEditModal = false" class="modal-close-x" title="Tutup Modal">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/deliveries/update') ?>" method="POST">
                <?= \App\Helpers\CSRF::field() ?>
                <div class="modal-body custom-scrollbar space-y-4">
                    <input type="hidden" name="id" :value="editData.id">

                    <!-- Ringkasan Toko & Dokumen (Read-Only) -->
                    <div style="background:var(--color-canvas-soft);padding:12px 14px;border-radius:12px;border:1px solid var(--color-hairline);font-size:12px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span class="badge badge-mono font-bold" x-text="editData.nomor_surat_jalan"></span>
                            <span class="font-mono text-ink-mute" style="font-size:11.5px;font-weight:700;" x-text="editData.nomor_nota"></span>
                        </div>
                        <div style="font-weight:800;font-size:13.5px;color:var(--color-ink);margin-top:6px;" x-text="editData.nama_toko"></div>
                        <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;" x-text="editData.alamat_toko"></div>
                    </div>

                    <!-- Tanggal Pengiriman -->
                    <div>
                        <label class="form-label font-bold">Tanggal Pengiriman / Surat Jalan *</label>
                        <input type="date" name="tanggal_surat_jalan" x-model="editData.tanggal_surat_jalan" required class="form-input font-medium" style="height:40px;">
                    </div>

                    <!-- Driver / Petugas Pengantar -->
                    <div>
                        <label class="form-label font-bold">Driver / Petugas Pengantar *</label>
                        <select name="sales_driver_id" required class="form-input" x-model="editData.sales_driver_id" style="height:40px;">
                            <option value="">-- Pilih Driver / Petugas Pengantar --</option>
                            <?php foreach ($drivers as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nama_karyawan']) ?><?= !empty($d['nomor_polisi_kendaraan']) ? ' (' . htmlspecialchars($d['nomor_polisi_kendaraan']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showEditModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                    <button type="submit" class="btn btn-primary w-full sm:w-auto" style="display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                        <i data-lucide="save" style="width:16px;height:16px;"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- MODAL RESPONSIVE PREVIEW FOTO BUKTI PENGIRIMAN (CAROUSEL TOUCH PINCH & PAN) -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showPhotoModal" 
         x-cloak 
         class="receipt-backdrop" 
         @keydown.window="handleViewerKeydown($event)">
        
        <div class="receipt-container" @click.stop>
            <!-- Header Modal -->
            <div class="receipt-header" style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid var(--color-hairline);background:var(--color-surface, #ffffff);z-index:10;">
                <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                    <div style="width:34px;height:34px;border-radius:10px;background:rgba(59,130,246,0.12);display:flex;align-items:center;justify-content:center;color:#3b82f6;flex-shrink:0;">
                        <i data-lucide="image" style="width:18px;height:18px;"></i>
                    </div>
                    <div style="min-width:0;">
                        <div class="flex items-center gap-2">
                            <h3 style="font-size:14px;font-weight:700;color:var(--color-ink-primary);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="photoModalTitle">Foto Bukti Pengiriman</h3>
                            <template x-if="photoModalList.length > 1">
                                <span class="badge badge-primary text-xs" x-text="(photoModalIndex + 1) + ' / ' + photoModalList.length"></span>
                            </template>
                        </div>
                        <div style="font-size:11px;color:var(--color-ink-mute);font-family:monospace;" x-text="photoModalSubtitle"></div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                    <button type="button" @click="closePhotoViewer()" class="btn btn-ghost btn-sm" style="padding:6px;border-radius:8px;" title="Tutup">
                        <i data-lucide="x" style="width:20px;height:20px;"></i>
                    </button>
                </div>
            </div>

            <!-- Viewport Area Foto Gambar (Interactive Pinch & Pan Viewport) -->
            <div class="receipt-viewport relative" 
                 x-ref="photoViewport"
                 data-zoomable="true"
                 @wheel.prevent="handleWheel($event)"
                 @mousedown="handleMouseDown($event)"
                 @touchstart="handleTouchStart($event)"
                 @touchmove.prevent="handleTouchMove($event)"
                 @touchend="handleTouchEnd($event)"
                 @touchcancel="handleTouchEnd($event)"
                 @dblclick="toggleDoubleTap($event.clientX, $event.clientY)">

                <!-- Carousel Navigation Button Prev -->
                <template x-if="photoModalList.length > 1">
                    <button type="button" 
                            @click="prevPhoto()" 
                            class="receipt-carousel-nav is-prev" 
                            title="Foto Sebelumnya (Panah Kiri)">
                        <i data-lucide="chevron-left" style="width:22px;height:22px;"></i>
                    </button>
                </template>

                <!-- Carousel Navigation Button Next -->
                <template x-if="photoModalList.length > 1">
                    <button type="button" 
                            @click="nextPhoto()" 
                            class="receipt-carousel-nav is-next" 
                            title="Foto Selanjutnya (Panah Kanan)">
                        <i data-lucide="chevron-right" style="width:22px;height:22px;"></i>
                    </button>
                </template>

                <!-- State Error jika file fisik tidak ditemukan / dibersihkan -->
                <div x-show="photoLoadError" style="margin:auto;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;max-width:440px;width:100%;padding:32px 16px;z-index:5;">
                    <div style="width:56px;height:56px;border-radius:16px;background:rgba(239,68,68,0.15);display:flex;align-items:center;justify-content:center;color:#ef4444;margin:0 auto 16px auto;box-shadow:0 4px 12px rgba(239,68,68,0.12);">
                        <i data-lucide="image-off" style="width:28px;height:28px;display:block;"></i>
                    </div>
                    <div style="font-size:15px;font-weight:700;color:#f8fafc;margin-bottom:6px;text-align:center;width:100%;">Foto Bukti Tidak Ditemukan</div>
                    <div style="font-size:12.5px;color:#94a3b8;max-width:380px;line-height:1.6;margin:0 auto;text-align:center;width:100%;">
                        Berkas foto bukti ini tidak ditemukan di server atau Cloudflare Storage. Kemungkinan merupakan berkas lama yang telah dibersihkan atau belum berhasil terunggah.
                    </div>
                </div>

                <!-- Gambar Bukti Utama (Hardware-Accelerated CSS Transform) -->
                <template x-if="photoModalUrl">
                    <img :src="photoModalUrl" 
                         alt="Foto Bukti Pengiriman" 
                         loading="lazy"
                         decoding="async"
                         x-show="!photoLoadError"
                         @load="onPhotoImageLoaded()"
                         @error="photoLoadError = true; $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });"
                         draggable="false"
                         :style="{
                             display: photoLoadError ? 'none' : 'block',
                             maxWidth: '100%',
                             maxHeight: '100%',
                             objectFit: 'contain',
                             transform: 'translate3d(' + zoomPanX + 'px, ' + zoomPanY + 'px, 0) scale(' + zoomScale + ') rotate(' + zoomRotate + 'deg)',
                             transformOrigin: 'center center',
                             transition: isDragging ? 'none' : 'transform 0.18s cubic-bezier(0.2, 0, 1)',
                             cursor: zoomScale > 1.05 ? (isDragging ? 'grabbing' : 'grab') : 'zoom-in',
                             userSelect: 'none',
                             webkitUserDrag: 'none'
                         }">
                </template>

                <!-- Floating Glassmorphism Controls -->
                <div x-show="!photoLoadError" class="receipt-floating-toolbar">
                    <!-- Zoom Out -->
                    <button type="button" @click="zoomStep(-0.3)" class="receipt-tool-btn" title="Perkecil Zoom (-)" :disabled="zoomScale <= 0.6">
                        <i data-lucide="minus" style="width:16px;height:16px;"></i>
                    </button>

                    <!-- Persentase & Reset -->
                    <button type="button" @click="resetZoom()" class="receipt-tool-badge" title="Klik untuk Reset Tampilan Fit">
                        <span x-text="Math.round(zoomScale * 100) + '%'"></span>
                    </button>

                    <!-- Zoom In -->
                    <button type="button" @click="zoomStep(0.3)" class="receipt-tool-btn" title="Perbesar Zoom (+)" :disabled="zoomScale >= 5.0">
                        <i data-lucide="plus" style="width:16px;height:16px;"></i>
                    </button>

                    <div class="receipt-tool-divider"></div>

                    <!-- Rotate 90° Clockwise -->
                    <button type="button" @click="rotateClockwise()" class="receipt-tool-btn" title="Putar Posisi 90°">
                        <i data-lucide="rotate-cw" style="width:16px;height:16px;"></i>
                    </button>

                    <!-- Fit / Reset -->
                    <button type="button" @click="resetZoom()" class="receipt-tool-btn" title="Reset Ukuran Normal (Fit Layar)">
                        <i data-lucide="maximize-2" style="width:15px;height:15px;"></i>
                    </button>
                </div>
            </div>

            <!-- Thumbnail Carousel Strip (Multi-Foto) -->
            <template x-if="photoModalList.length > 1">
                <div class="receipt-thumb-strip custom-scrollbar">
                    <template x-for="(thumb, idx) in photoModalList" :key="idx">
                        <div class="receipt-thumb-item" 
                             :class="{'is-active': idx === photoModalIndex}"
                             @click="selectPhoto(idx)">
                            <img :src="resolvePhotoUrl(thumb)" alt="Thumbnail" loading="lazy">
                        </div>
                    </template>
                </div>
            </template>

            <!-- Footer Modal (Petunjuk Gestur) -->
            <div class="receipt-footer" style="display:flex;align-items:center;justify-content:center;padding:10px 18px;border-top:1px solid var(--color-hairline);background:var(--color-canvas-soft);font-size:11.5px;color:var(--color-ink-mute);z-index:10;text-align:center;">
                <div style="display:flex;align-items:center;gap:6px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <i data-lucide="info" style="width:14px;height:14px;flex-shrink:0;"></i>
                    <span class="hidden sm:inline">Panah Kiri/Kanan untuk ganti foto • Geser foto • Scroll mouse / Cubit 2 jari untuk zoom • Ketuk 2x zoom</span>
                    <span class="inline sm:hidden">Geser/panah ganti foto • Cubit 2 jari zoom • Ketuk 2x zoom</span>
                </div>
            </div>
        </div>
    </div>
    </template>

</div>

<script>
function deliveryApp() {
    return {
        deliveries: <?= json_encode($deliveries) ?>,
        pendingOrders: <?= json_encode($pendingOrders) ?>,
        searchQuery: '',
        filterStatus: 'all',
        showAddModal: false,
        showEditModal: false,
        addSelectedPesananId: '',
        addSelectedDriverId: '',
        defaultDeliveryDate: <?= json_encode($defaultDeliveryDate ?? date('Y-m-d')) ?>,
        isAfternoon: <?= json_encode($isAfternoon ?? false) ?>,
        editData: {
            id: '',
            nomor_surat_jalan: '',
            nomor_nota: '',
            nama_toko: '',
            alamat_toko: '',
            sales_driver_id: '',
            tanggal_surat_jalan: ''
        },

        onPesananChange() {
            const po = this.pendingOrders.find(o => o.id === this.addSelectedPesananId);
            if (po && po.sales_driver_id) {
                this.addSelectedDriverId = po.sales_driver_id;
            }
        },

        init() {
            const urlParams = new URLSearchParams(window.location.search);
            const autoOrderId = urlParams.get('create_for_order');
            if (autoOrderId) {
                this.showAddModal = true;
                this.addSelectedPesananId = autoOrderId;
                this.onPesananChange();
                this.$nextTick(() => {
                    const select = document.querySelector('select[name="pesanan_id"]');
                    if (select) {
                        select.value = autoOrderId;
                    }
                    if (typeof window.initSearchableSelects === 'function') {
                        window.initSearchableSelects();
                    }
                    lucide.createIcons();
                });
            } else {
                this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
            }

            this.$watch('searchQuery', () => this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }));
            this.$watch('filterStatus', () => this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }));
        },

        get filteredDeliveries() {
            return this.deliveries.filter(d => {
                const q = this.searchQuery.toLowerCase();
                const matchQuery = !q ||
                    d.nomor_surat_jalan.toLowerCase().includes(q) ||
                    d.nama_toko.toLowerCase().includes(q) ||
                    (d.nama_driver && d.nama_driver.toLowerCase().includes(q)) ||
                    (d.tanggal_surat_jalan && d.tanggal_surat_jalan.includes(q));

                const matchStatus = this.filterStatus === 'all' || d.status_surat_jalan === this.filterStatus;

                return matchQuery && matchStatus;
            });
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        formatDateIndo(dateStr) {
            if (!dateStr) return '-';
            const cleanStr = dateStr.substring(0, 10);
            const parts = cleanStr.split('-');
            if (parts.length === 3) {
                return `${parts[2]}/${parts[1]}/${parts[0]}`;
            }
            return cleanStr;
        },

        openAddModal() {
            this.showAddModal = true;
            this.$nextTick(() => {
                if (typeof window.initSearchableSelects === 'function') {
                    window.initSearchableSelects();
                }
                lucide.createIcons();
            });
        },

        openEditModal(item) {
            this.editData = {
                id: item.id,
                nomor_surat_jalan: item.nomor_surat_jalan,
                nomor_nota: item.nomor_nota,
                nama_toko: item.nama_toko,
                alamat_toko: item.alamat_toko,
                sales_driver_id: item.sales_driver_id || '',
                tanggal_surat_jalan: item.tanggal_surat_jalan || item.dibuat_pada?.substring(0, 10) || ''
            };
            this.showEditModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        // =====================================================================
        // PHOTO VIEWER CAROUSEL LIGHTBOX (DNA ALIGNED WITH ERP ECOSYSTEM)
        // =====================================================================
        showPhotoModal: false,
        photoModalList: [],
        photoModalIndex: 0,
        photoModalUrl: '',
        photoModalTitle: 'Foto Bukti Pengiriman',
        photoModalSubtitle: '',
        photoLoadError: false,
        zoomScale: 1.0,
        zoomPanX: 0,
        zoomPanY: 0,
        zoomRotate: 0,
        isDragging: false,
        isPinching: false,
        dragStartX: 0,
        dragStartY: 0,
        pinchStartDist: 0,
        pinchStartScale: 1.0,
        lastTapTime: 0,

        resolvePhotoUrl(url) {
            if (!url) return '';
            const cleanUrl = String(url).trim();
            if (cleanUrl.startsWith('http://') || cleanUrl.startsWith('https://') || cleanUrl.startsWith('data:') || cleanUrl.startsWith('blob:')) {
                return cleanUrl;
            } else if (cleanUrl.startsWith('/media/view') || cleanUrl.startsWith('media/view')) {
                return '<?= Router::url('/') ?>' + cleanUrl.replace(/^\//, '');
            } else if (cleanUrl.startsWith('/assets/') || cleanUrl.startsWith('assets/') || cleanUrl.startsWith('/favicon/')) {
                return '<?= Router::url('/') ?>' + cleanUrl.replace(/^\//, '');
            } else {
                const storagePath = cleanUrl.replace(/^\/?(public\/)?(uploads\/)?/, '');
                return '<?= Router::url('/media/view?path=') ?>' + encodeURIComponent(storagePath);
            }
        },

        openPhotoViewer(urls, title, subtitle, startIndex = 0) {
            if (!urls) return;
            const list = Array.isArray(urls) ? urls : [urls];
            const filteredList = list.filter(u => !!u);
            if (filteredList.length === 0) return;

            this.resetZoom();
            this.photoModalList = filteredList;
            this.photoModalIndex = (startIndex >= 0 && startIndex < filteredList.length) ? startIndex : 0;
            this.photoModalUrl = this.resolvePhotoUrl(this.photoModalList[this.photoModalIndex]);
            this.photoModalTitle = title || 'Foto Bukti Pengiriman';
            this.photoModalSubtitle = subtitle || '';
            this.photoLoadError = false;
            this.showPhotoModal = true;
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        selectPhoto(index) {
            if (index < 0 || index >= this.photoModalList.length) return;
            this.photoModalIndex = index;
            this.resetZoom();
            this.photoLoadError = false;
            this.photoModalUrl = this.resolvePhotoUrl(this.photoModalList[this.photoModalIndex]);
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        prevPhoto() {
            if (this.photoModalList.length <= 1) return;
            const newIndex = (this.photoModalIndex - 1 + this.photoModalList.length) % this.photoModalList.length;
            this.selectPhoto(newIndex);
        },

        nextPhoto() {
            if (this.photoModalList.length <= 1) return;
            const newIndex = (this.photoModalIndex + 1) % this.photoModalList.length;
            this.selectPhoto(newIndex);
        },

        closePhotoViewer() {
            this.showPhotoModal = false;
            this.photoModalList = [];
            this.photoModalIndex = 0;
            this.photoModalUrl = '';
            this.resetZoom();
            this.photoLoadError = false;
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        },

        resetZoom() {
            this.zoomScale = 1.0;
            this.zoomPanX = 0;
            this.zoomPanY = 0;
            this.zoomRotate = 0;
            this.isDragging = false;
            this.isPinching = false;
        },

        zoomStep(step) {
            const next = Math.min(5.0, Math.max(0.6, Number((this.zoomScale + step).toFixed(2))));
            this.zoomScale = next;
            if (next <= 1.0) {
                this.zoomPanX = 0;
                this.zoomPanY = 0;
            } else {
                this.clampPan();
            }
        },

        rotateClockwise() {
            this.zoomRotate = (this.zoomRotate + 90) % 360;
        },

        clampPan() {
            if (this.zoomScale <= 1.0) {
                this.zoomPanX = 0;
                this.zoomPanY = 0;
                return;
            }
            const bound = Math.max(100, 480 * (this.zoomScale - 0.7));
            this.zoomPanX = Math.max(-bound, Math.min(bound, this.zoomPanX));
            this.zoomPanY = Math.max(-bound, Math.min(bound, this.zoomPanY));
        },

        onPhotoImageLoaded() {
            this.photoLoadError = false;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        toggleDoubleTap(clientX, clientY) {
            if (this.photoLoadError) return;
            if (this.zoomScale > 1.2) {
                this.resetZoom();
            } else {
                this.zoomScale = 2.4;
                this.zoomPanX = 0;
                this.zoomPanY = 0;
            }
        },

        handleMouseDown(e) {
            if (e.button !== 0 || this.photoLoadError) return;
            this.isDragging = true;
            this.dragStartX = e.clientX - this.zoomPanX;
            this.dragStartY = e.clientY - this.zoomPanY;

            const onMouseMove = (ev) => {
                if (!this.isDragging) return;
                this.zoomPanX = ev.clientX - this.dragStartX;
                this.zoomPanY = ev.clientY - this.dragStartY;
                this.clampPan();
            };

            const onMouseUp = () => {
                this.isDragging = false;
                this.clampPan();
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
            };

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
        },

        handleTouchStart(e) {
            if (this.photoLoadError) return;
            if (e.touches.length === 2) {
                this.isPinching = true;
                this.isDragging = false;
                this.pinchStartDist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                this.pinchStartScale = this.zoomScale;
            } else if (e.touches.length === 1) {
                const now = Date.now();
                if (now - this.lastTapTime < 300) {
                    this.toggleDoubleTap(e.touches[0].clientX, e.touches[0].clientY);
                    this.lastTapTime = 0;
                    return;
                }
                this.lastTapTime = now;
                this.isDragging = true;
                this.dragStartX = e.touches[0].clientX - this.zoomPanX;
                this.dragStartY = e.touches[0].clientY - this.zoomPanY;
            }
        },

        handleTouchMove(e) {
            if (this.photoLoadError) return;
            if (this.isPinching && e.touches.length === 2) {
                const dist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                if (this.pinchStartDist > 0) {
                    const factor = dist / this.pinchStartDist;
                    this.zoomScale = Math.min(5.0, Math.max(0.6, Number((this.pinchStartScale * factor).toFixed(2))));
                }
            } else if (this.isDragging && e.touches.length === 1) {
                this.zoomPanX = e.touches[0].clientX - this.dragStartX;
                this.zoomPanY = e.touches[0].clientY - this.dragStartY;
                this.clampPan();
            }
        },

        handleTouchEnd(e) {
            if (e.touches.length < 2) {
                this.isPinching = false;
            }
            if (e.touches.length === 0) {
                this.isDragging = false;
                this.clampPan();
            }
        },

        handleWheel(e) {
            if (this.photoLoadError) return;
            const delta = e.deltaY < 0 ? 0.25 : -0.25;
            this.zoomStep(delta);
        },

        handleViewerKeydown(e) {
            if (!this.showPhotoModal) return;
            if (e.key === 'Escape') {
                this.closePhotoViewer();
            } else if (e.key === 'ArrowLeft') {
                this.prevPhoto();
            } else if (e.key === 'ArrowRight') {
                this.nextPhoto();
            } else if (e.key === '+' || e.key === '=') {
                this.zoomStep(0.3);
            } else if (e.key === '-' || e.key === '_') {
                this.zoomStep(-0.3);
            } else if (e.key === 'r' || e.key === 'R') {
                this.rotateClockwise();
            } else if (e.key === '0') {
                this.resetZoom();
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

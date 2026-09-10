<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<div x-data="employeeApp()" x-init="init()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-indigo">
                <i data-lucide="users"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#6366f1;"></span>
                    <span>SDM &amp; Tenaga Kerja</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Master Data Karyawan' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Kelola Data Pegawai Admin, Gudang, Pengemasan, Sales &amp; Driver' ?></p>
            </div>
        </div>
        <div class="page-header-actions">
            <button @click="openAddModal()" class="btn btn-primary" style="font-weight:700;">
                <i data-lucide="user-plus"></i>
                <span>Tambah Karyawan</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TOP STATS: 5 KEY EMPLOYEE METRIC CARDS                                    -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-4">
        
        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Total Karyawan</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="users" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:20px;font-weight:900;font-family:var(--font-mono);color:#10b981;line-height:1.2;">
                <?= $metrics['total'] ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Pegawai Operasional</div>
        </div>

        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Buruh Borongan</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(59,130,246,0.12);color:#3b82f6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="package" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:20px;font-weight:900;font-family:var(--font-mono);color:#3b82f6;line-height:1.2;">
                <?= $metrics['borongan'] ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Pengemasan &amp; Packing</div>
        </div>

        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Sales Toko</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(245,158,11,0.12);color:#f59e0b;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="store" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:20px;font-weight:900;font-family:var(--font-mono);color:#f59e0b;line-height:1.2;">
                <?= $metrics['sales'] ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Canvaser &amp; Komisi</div>
        </div>

        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Driver Logistik</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(2,132,199,0.12);color:#0284c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="truck" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:20px;font-weight:900;font-family:var(--font-mono);color:#0284c7;line-height:1.2;">
                <?= $metrics['driver'] ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Supir Pengantar</div>
        </div>

        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Admin &amp; Gudang</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(139,92,246,0.12);color:#8b5cf6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="building-2" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:20px;font-weight:900;font-family:var(--font-mono);color:#8b5cf6;line-height:1.2;">
                <?= $metrics['admin_gudang'] ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Kantor, Gudang &amp; Mandor</div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- MAIN DATA TABLE CARD                                                      -->
    <!-- ========================================================================= -->
    <div class="card p-0 overflow-hidden" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);">

        <!-- ACTION & FILTER BAR -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            
            <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto flex-1">
                <div class="form-input-icon flex-1 sm:max-w-xs">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="text" x-model="searchQuery" placeholder="Cari nama / NIK / HP..." class="form-input" style="height:38px;font-size:13px;">
                </div>

                <select x-model="filterPosition" class="form-input" style="height:38px;font-size:13px;max-width:220px;">
                    <option value="all">Semua Divisi / Posisi</option>
                    <option value="sales">💼 Sales Toko</option>
                    <option value="driver">🚚 Driver Logistik</option>
                    <option value="pengemasan">🍿 Pengemasan (Borongan)</option>
                    <option value="gudang">📦 Staff Gudang &amp; Logistik</option>
                    <option value="admin">👩‍💼 Admin &amp; Keuangan</option>
                    <option value="mandor">👷 Mandor / Supervisor</option>
                </select>
            </div>

            <div class="text-xs" style="color:var(--color-ink-mute);font-weight:600;white-space:nowrap;">
                Menampilkan <span class="font-mono" style="font-weight:800;color:var(--color-primary);" x-text="filteredEmployees.length"></span> dari <span class="font-mono" style="font-weight:700;color:var(--color-ink);" x-text="employees.length"></span> karyawan
            </div>
        </div>

        <!-- TABLE LIST -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 960px;">
                <thead>
                    <tr>
                        <th style="min-width:180px;">Nama Karyawan</th>
                        <th style="min-width:140px;">Divisi / Posisi</th>
                        <th class="cell-center cell-nowrap" style="width:110px; min-width:100px;">Tipe Gaji</th>
                        <th class="cell-right cell-nowrap" style="width:160px; min-width:140px;">Gaji Pokok / Komisi</th>
                        <th style="min-width:150px;">Uang Hadir &amp; Tunjangan</th>
                        <th style="min-width:160px;">Rekening Bank / Pembayaran</th>
                        <th class="cell-center cell-nowrap" style="width:90px; min-width:80px;">Status</th>
                        <th class="cell-center cell-nowrap" style="width:90px; min-width:80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="e in filteredEmployees" :key="e.id">
                        <tr :style="!e.status_aktif ? 'opacity:0.5;' : ''">
                            
                            <!-- Nama & Kontak -->
                            <td>
                                <div style="font-weight:800;font-size:13.5px;color:var(--color-ink);" x-text="e.nama_karyawan"></div>
                                <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                    <span x-text="e.nomor_telepon ? ('HP: ' + e.nomor_telepon) : (e.nik ? ('NIK: ' + e.nik) : 'Belum ada kontak')"></span>
                                    <template x-if="e.nomor_polisi_kendaraan">
                                        <span class="badge badge-mono" style="font-size:10.5px;padding:1px 6px;color:#0284c7;border-color:rgba(2,132,199,0.3);background:rgba(2,132,199,0.08);">
                                            &#x1F69A; <span x-text="e.nomor_polisi_kendaraan"></span>
                                        </span>
                                    </template>
                                </div>
                            </td>

                            <!-- Posisi Badge -->
                            <td class="cell-nowrap">
                                <template x-if="e.posisi === 'sales'">
                                    <span class="badge badge-warning" style="font-weight:700;">💼 Sales Toko</span>
                                </template>
                                <template x-if="e.posisi === 'driver'">
                                    <span class="badge badge-info" style="font-weight:700;">🚚 Driver Logistik</span>
                                </template>
                                <template x-if="e.posisi === 'pengemasan'">
                                    <span class="badge badge-info" style="font-weight:700;">🍿 Pengemasan</span>
                                </template>
                                <template x-if="e.posisi === 'gudang'">
                                    <span class="badge badge-secondary" style="font-weight:700;">📦 Staff Gudang</span>
                                </template>
                                <template x-if="e.posisi === 'admin'">
                                    <span class="badge badge-success" style="font-weight:700;">👩‍💼 Admin Kantor</span>
                                </template>
                                <template x-if="e.posisi === 'mandor'">
                                    <span class="badge badge-mono" style="font-weight:700;">👷 Mandor</span>
                                </template>
                            </td>

                            <!-- Tipe Gaji -->
                            <td class="cell-center cell-nowrap">
                                <span class="badge" :class="e.tipe_penggajian === 'borongan' ? 'badge-primary' : (e.tipe_penggajian === 'bulanan' ? 'badge-info' : 'badge-mono')" style="text-transform:capitalize;font-weight:700;" x-text="e.tipe_penggajian"></span>
                            </td>

                            <!-- Gaji Pokok & Komisi -->
                            <td class="cell-currency cell-right cell-nowrap">
                                <template x-if="e.posisi === 'sales'">
                                    <div>
                                        <div style="font-weight:800;" x-text="formatRupiah(e.gaji_pokok_bulanan)"></div>
                                        <template x-if="Number(e.persentase_komisi_sales) > 0">
                                            <div style="font-size:11px;color:#10b981;font-weight:700;" x-text="'Komisi: ' + e.persentase_komisi_sales + '%'"></div>
                                        </template>
                                        <template x-if="!Number(e.persentase_komisi_sales) || Number(e.persentase_komisi_sales) === 0">
                                            <div style="font-size:10.5px;color:var(--color-ink-mute);">Tanpa Komisi (0%)</div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="e.posisi !== 'sales'">
                                    <div style="font-weight:700;" x-text="formatRupiah(e.gaji_pokok_bulanan)"></div>
                                </template>
                            </td>

                            <!-- Uang Hadir & Tunjangan -->
                            <td class="cell-nowrap">
                                <div style="font-size:12px;">
                                    <span style="color:var(--color-ink-mute);">Hadir:</span> <strong class="cell-currency" x-text="formatRupiah(e.uang_kehadiran_harian)"></strong><span style="font-size:10.5px;color:var(--color-ink-mute);">/hari</span>
                                </div>
                                <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">
                                    <span>Tunj:</span> <span class="cell-currency" x-text="formatRupiah(e.tunjangan_bulanan)"></span><span style="font-size:10.5px;">/bln</span>
                                </div>
                            </td>

                            <!-- Rekening Bank -->
                            <td class="cell-nowrap">
                                <div style="font-size:12px;font-weight:600;" x-text="e.bank_nama || 'Tunai'"></div>
                                <template x-if="e.bank_nomor_rekening">
                                    <div style="font-size:11px;font-family:var(--font-mono);color:var(--color-ink-mute);" x-text="e.bank_nomor_rekening + (e.bank_atas_nama ? ' (a.n. ' + e.bank_atas_nama + ')' : '')"></div>
                                </template>
                            </td>

                            <!-- Status Aktif -->
                            <td class="cell-center cell-nowrap">
                                <span class="badge" :class="e.status_aktif ? 'badge-success' : 'badge-danger'" x-text="e.status_aktif ? 'Aktif' : 'Nonaktif'"></span>
                            </td>

                            <!-- Aksi -->
                            <td class="cell-center cell-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="openEditModal(e)" class="btn btn-ghost btn-sm" style="padding:6px 8px;" title="Edit Data Karyawan">
                                        <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    </template>

                    <template x-if="filteredEmployees.length === 0">
                        <tr>
                            <td colspan="8" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="search-x" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Tidak ada data karyawan yang cocok</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: TAMBAH / EDIT MASTER KARYAWAN                                     -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:580px;padding:24px;">
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(16,185,129,0.1);color:#10b981;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="user-plus" style="width:16px;height:16px;"></i>
                    </div>
                    <div class="modal-title" x-text="isEdit ? 'Edit Data Karyawan' : 'Tambah Karyawan Baru'"></div>
                </div>
                <button @click="showModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form :action="isEdit ? '<?= Router::url('/employees/update') ?>' : '<?= Router::url('/employees/store') ?>'" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="id" :value="form.id">

                <!-- SECTION 1: BIODATA -->
                <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-primary);border-bottom:1px solid var(--color-hairline);padding-bottom:4px;">
                    1. Identitas &amp; Kontak Pegawai
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nama Lengkap Karyawan *</label>
                        <input type="text" name="nama_karyawan" x-model="form.nama_karyawan" required class="form-input" placeholder="Contoh: Teh Ika">
                    </div>
                    <div>
                        <label class="form-label">Nomor WhatsApp / HP</label>
                        <input type="text" name="nomor_telepon" x-model="form.nomor_telepon" class="form-input font-mono" placeholder="08123456789">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">NIK / No. KTP (Opsional)</label>
                        <input type="text" name="nik" x-model="form.nik" 
                               @input="form.nik = $event.target.value.replace(/[^0-9]/g, '').slice(0, 16)"
                               maxlength="16" class="form-input font-mono" placeholder="3201xxxxxxxxxxxx (16 Digit)">
                    </div>
                    <div>
                        <label class="form-label">Alamat Domisili</label>
                        <input type="text" name="alamat" x-model="form.alamat" class="form-input" placeholder="Alamat tinggal karyawan">
                    </div>
                </div>

                <!-- SECTION 2: DIVISI & SKEMA GAJI -->
                <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-primary);border-bottom:1px solid var(--color-hairline);padding-bottom:4px;margin-top:6px;">
                    2. Penempatan Kerja &amp; Skema Remunerasi
                </div>

                <div>
                    <label class="form-label">Divisi / Posisi Kerja *</label>
                    <select name="posisi" x-model="form.posisi" @change="onPosisiChange()" required class="form-input" style="font-weight:600;">
                        <option value="sales">💼 Sales (Canvaser &amp; Komisi Toko)</option>
                        <option value="driver">🚚 Driver (Supir Logistik &amp; Pengantar)</option>
                        <option value="pengemasan">🍿 Pengemasan (Packing Borongan)</option>
                        <option value="gudang">📦 Staff Gudang &amp; Sortir</option>
                        <option value="admin">👩‍💼 Admin &amp; Kasir Kantor</option>
                        <option value="mandor">👷 Mandor / Supervisor</option>
                    </select>
                </div>

                <!-- DYNAMIC CASE 1: PENGEMASAN (BORONGAN) -->
                <template x-if="form.posisi === 'pengemasan'">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <input type="hidden" name="tipe_penggajian" value="borongan">
                        <input type="hidden" name="gaji_pokok_bulanan" value="0">
                        <input type="hidden" name="persentase_komisi_sales" value="0">

                        <div style="padding:10px 14px;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:var(--rounded-md);font-size:12px;color:var(--color-ink-secondary);display:flex;align-items:flex-start;gap:8px;">
                            <i data-lucide="info" style="width:16px;height:16px;color:#3b82f6;flex-shrink:0;margin-top:2px;"></i>
                            <div>
                                <strong style="color:var(--color-ink);">Skema Upah Borongan Murni:</strong>
                                <div>Upah pokok dihitung otomatis per bungkus produk snack yang dikemas saat input data hasil produksi packing.</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Uang Kehadiran Harian (Rp/hari) *</label>
                                <input type="text" name="uang_kehadiran_harian" x-model="form.uang_kehadiran_harian" required class="form-input font-mono input-rupiah" placeholder="10.000">
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">Diberikan setiap hari masuk kerja</div>
                            </div>
                            <div>
                                <label class="form-label">Tunjangan Bulanan (Rp/bln)</label>
                                <input type="text" name="tunjangan_bulanan" x-model="form.tunjangan_bulanan" class="form-input font-mono input-rupiah" placeholder="50.000">
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">Bonus / tunjangan tetap per bulan</div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- DYNAMIC CASE 2: SALES (DENGAN KOMISI) -->
                <template x-if="form.posisi === 'sales'">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Tipe Penggajian *</label>
                                <select name="tipe_penggajian" x-model="form.tipe_penggajian" required class="form-input">
                                    <option value="bulanan">Bulanan (Gaji Pokok + Komisi)</option>
                                    <option value="harian">Harian (Uang Harian + Komisi)</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Komisi Penjualan (%) *</label>
                                <input type="number" step="0.1" name="persentase_komisi_sales" x-model="form.persentase_komisi_sales" required class="form-input font-mono" placeholder="5.0">
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">Persentase komisi dari omzet toko binaan</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Gaji Pokok (Rp/bln)</label>
                                <input type="text" name="gaji_pokok_bulanan" x-model="form.gaji_pokok_bulanan" class="form-input font-mono input-rupiah" placeholder="1.000.000">
                            </div>
                            <div>
                                <label class="form-label">Uang Kehadiran / Hadir (Rp/hari)</label>
                                <input type="text" name="uang_kehadiran_harian" x-model="form.uang_kehadiran_harian" class="form-input font-mono input-rupiah" placeholder="15.000">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Tunjangan Bulanan (Rp/bln)</label>
                                <input type="text" name="tunjangan_bulanan" x-model="form.tunjangan_bulanan" class="form-input font-mono input-rupiah" placeholder="50.000">
                            </div>
                            <div>
                                <label class="form-label">Plat No. Kendaraan (Opsional)</label>
                                <input type="text" name="nomor_polisi_kendaraan" x-model="form.nomor_polisi_kendaraan" 
                                       @input="form.nomor_polisi_kendaraan = $event.target.value.toUpperCase().slice(0, 12)"
                                       maxlength="12" class="form-input font-mono uppercase" placeholder="Contoh: B 9876 KRS">
                            </div>
                        </div>
                    </div>
                </template>

                <!-- DYNAMIC CASE 3: DRIVER (MURNI PENGANTAR, TANPA KOMISI) -->
                <template x-if="form.posisi === 'driver'">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <input type="hidden" name="persentase_komisi_sales" value="0">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Tipe Penggajian *</label>
                                <select name="tipe_penggajian" x-model="form.tipe_penggajian" required class="form-input">
                                    <option value="bulanan">Bulanan (Gaji Tetap Per Bulan)</option>
                                    <option value="harian">Harian (Uang Jalan Harian)</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Plat No. Armada Truk / Mobil *</label>
                                <input type="text" name="nomor_polisi_kendaraan" x-model="form.nomor_polisi_kendaraan" 
                                       @input="form.nomor_polisi_kendaraan = $event.target.value.toUpperCase().slice(0, 12)"
                                       maxlength="12" class="form-input font-mono uppercase" placeholder="Contoh: B 1234 ABC" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Gaji Pokok (Rp/bln)</label>
                                <input type="text" name="gaji_pokok_bulanan" x-model="form.gaji_pokok_bulanan" class="form-input font-mono input-rupiah" placeholder="1.500.000">
                            </div>
                            <div>
                                <label class="form-label">Uang Jalan / Hadir (Rp/hari)</label>
                                <input type="text" name="uang_kehadiran_harian" x-model="form.uang_kehadiran_harian" class="form-input font-mono input-rupiah" placeholder="20.000">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Tunjangan Bulanan (Rp/bln)</label>
                            <input type="text" name="tunjangan_bulanan" x-model="form.tunjangan_bulanan" class="form-input font-mono input-rupiah" placeholder="50.000">
                        </div>
                    </div>
                </template>

                <!-- DYNAMIC CASE 3: GUDANG & ADMIN -->
                <template x-if="form.posisi === 'gudang' || form.posisi === 'admin'">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <input type="hidden" name="persentase_komisi_sales" value="0">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Tipe Penggajian *</label>
                                <select name="tipe_penggajian" x-model="form.tipe_penggajian" required class="form-input">
                                    <option value="bulanan">Bulanan (Gaji Tetap Per Bulan)</option>
                                    <option value="harian">Harian Lepas (Per Hari Kerja)</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Gaji Pokok (Rp/bln) *</label>
                                <input type="text" name="gaji_pokok_bulanan" x-model="form.gaji_pokok_bulanan" required class="form-input font-mono input-rupiah" placeholder="2.500.000">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Uang Kehadiran (Rp/hari)</label>
                                <input type="text" name="uang_kehadiran_harian" x-model="form.uang_kehadiran_harian" class="form-input font-mono input-rupiah" placeholder="15.000">
                            </div>
                            <div>
                                <label class="form-label">Tunjangan Bulanan (Rp/bln)</label>
                                <input type="text" name="tunjangan_bulanan" x-model="form.tunjangan_bulanan" class="form-input font-mono input-rupiah" placeholder="100.000">
                            </div>
                        </div>
                    </div>
                </template>

                <!-- DYNAMIC CASE 4: MANDOR / SUPERVISOR -->
                <template x-if="form.posisi === 'mandor'">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <input type="hidden" name="tipe_penggajian" value="bulanan">
                        <input type="hidden" name="persentase_komisi_sales" value="0">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Gaji Pokok Bulanan (Rp/bln) *</label>
                                <input type="text" name="gaji_pokok_bulanan" x-model="form.gaji_pokok_bulanan" required class="form-input font-mono input-rupiah" placeholder="3.000.000">
                            </div>
                            <div>
                                <label class="form-label">Tunjangan Jabatan Mandor (Rp/bln)</label>
                                <input type="text" name="tunjangan_bulanan" x-model="form.tunjangan_bulanan" class="form-input font-mono input-rupiah" placeholder="500.000">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Uang Kehadiran (Rp/hari)</label>
                            <input type="text" name="uang_kehadiran_harian" x-model="form.uang_kehadiran_harian" class="form-input font-mono input-rupiah" placeholder="20.000">
                        </div>
                    </div>
                </template>

                <!-- SECTION 3: REKENING BANK -->
                <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-primary);border-bottom:1px solid var(--color-hairline);padding-bottom:4px;margin-top:6px;">
                    3. Rekening Pembayaran Gaji
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="form-label">Metode / Bank</label>
                        <select name="bank_nama" x-model="form.bank_nama" class="form-input">
                            <option value="Tunai">Tunai (Cash)</option>
                            <option value="BCA">Bank BCA</option>
                            <option value="BRI">Bank BRI</option>
                            <option value="Mandiri">Bank Mandiri</option>
                            <option value="BNI">Bank BNI</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Nomor Rekening</label>
                        <input type="text" name="bank_nomor_rekening" x-model="form.bank_nomor_rekening"
                               @input="form.bank_nomor_rekening = $event.target.value.replace(/[^0-9-]/g, '').slice(0, 25)"
                               maxlength="25" class="form-input font-mono" placeholder="Nomor rekening">
                    </div>

                    <div>
                        <label class="form-label">Atas Nama Rekening <template x-if="form.bank_nomor_rekening"><span style="color:var(--color-danger);">*</span></template></label>
                        <input type="text" name="bank_atas_nama" x-model="form.bank_atas_nama"
                               :required="!!form.bank_nomor_rekening"
                               class="form-input" placeholder="Nama pemilik rek">
                    </div>
                </div>
                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:4px;">Opsional. Jika nomor rekening diisi, pemilik rekening wajib diisi.</div>

                <template x-if="isEdit">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12.5px;font-weight:600;margin-top:4px;">
                        <input type="checkbox" name="status_aktif" x-model="form.status_aktif" style="width:16px;height:16px;accent-color:var(--color-primary);">
                        <span>Karyawan Masih Aktif Bekerja</span>
                    </label>
                </template>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px;">
                    <button type="button" @click="showModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Karyawan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

</div>

<script>
function employeeApp() {
    return {
        employees: <?= json_encode($employees) ?>,
        searchQuery: '',
        filterPosition: 'all',
        showModal: false,
        isEdit: false,

        form: {
            id: '',
            nik: '',
            nama_karyawan: '',
            posisi: 'pengemasan',
            tipe_penggajian: 'borongan',
            gaji_pokok_bulanan: '0',
            uang_kehadiran_harian: '10.000',
            tunjangan_bulanan: '50.000',
            persentase_komisi_sales: 0,
            nomor_telepon: '',
            alamat: '',
            bank_nama: 'Tunai',
            bank_nomor_rekening: '',
            bank_atas_nama: '',
            status_aktif: true
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        get filteredEmployees() {
            return this.employees.filter(e => {
                const q = this.searchQuery.toLowerCase();
                const matchQuery = !q ||
                    e.nama_karyawan.toLowerCase().includes(q) ||
                    (e.nik && e.nik.toLowerCase().includes(q)) ||
                    (e.nomor_telepon && e.nomor_telepon.includes(q));

                const matchPos = this.filterPosition === 'all' || e.posisi === this.filterPosition;
                return matchQuery && matchPos;
            });
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        onPosisiChange() {
            if (this.form.posisi === 'pengemasan') {
                this.form.tipe_penggajian = 'borongan';
                if (!this.isEdit) {
                    this.form.gaji_pokok_bulanan = '0';
                    this.form.uang_kehadiran_harian = '10.000';
                    this.form.tunjangan_bulanan = '50.000';
                    this.form.persentase_komisi_sales = 0;
                }
            } else if (this.form.posisi === 'sales') {
                if (!this.isEdit) {
                    this.form.tipe_penggajian = 'bulanan';
                    this.form.gaji_pokok_bulanan = '1.000.000';
                    this.form.uang_kehadiran_harian = '15.000';
                    this.form.tunjangan_bulanan = '50.000';
                    this.form.persentase_komisi_sales = 2.0;
                }
            } else if (this.form.posisi === 'driver') {
                if (!this.isEdit) {
                    this.form.tipe_penggajian = 'bulanan';
                    this.form.gaji_pokok_bulanan = '2.500.000';
                    this.form.uang_kehadiran_harian = '15.000';
                    this.form.tunjangan_bulanan = '100.000';
                    this.form.persentase_komisi_sales = 0;
                }
            } else if (this.form.posisi === 'gudang') {
                if (!this.isEdit) {
                    this.form.tipe_penggajian = 'bulanan';
                    this.form.gaji_pokok_bulanan = '2.500.000';
                    this.form.uang_kehadiran_harian = '15.000';
                    this.form.tunjangan_bulanan = '100.000';
                    this.form.persentase_komisi_sales = 0;
                }
            } else if (this.form.posisi === 'admin') {
                if (!this.isEdit) {
                    this.form.tipe_penggajian = 'bulanan';
                    this.form.gaji_pokok_bulanan = '2.500.000';
                    this.form.uang_kehadiran_harian = '15.000';
                    this.form.tunjangan_bulanan = '100.000';
                    this.form.persentase_komisi_sales = 0;
                }
            } else if (this.form.posisi === 'mandor') {
                if (!this.isEdit) {
                    this.form.tipe_penggajian = 'bulanan';
                    this.form.gaji_pokok_bulanan = '3.000.000';
                    this.form.uang_kehadiran_harian = '20.000';
                    this.form.tunjangan_bulanan = '500.000';
                    this.form.persentase_komisi_sales = 0;
                }
            }
            this.$nextTick(() => lucide.createIcons());
        },

        openAddModal() {
            this.isEdit = false;
            this.form = {
                id: '',
                nik: '',
                nama_karyawan: '',
                posisi: 'pengemasan',
                tipe_penggajian: 'borongan',
                gaji_pokok_bulanan: '0',
                uang_kehadiran_harian: '10.000',
                tunjangan_bulanan: '50.000',
                persentase_komisi_sales: 0,
                nomor_telepon: '',
                alamat: '',
                nomor_polisi_kendaraan: '',
                bank_nama: 'Tunai',
                bank_nomor_rekening: '',
                bank_atas_nama: '',
                status_aktif: true
            };
            this.showModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditModal(e) {
            this.isEdit = true;
            this.form = {
                id: e.id,
                nik: e.nik || '',
                nama_karyawan: e.nama_karyawan,
                posisi: e.posisi || 'pengemasan',
                tipe_penggajian: e.tipe_penggajian || 'borongan',
                gaji_pokok_bulanan: window.formatRupiahNumber ? window.formatRupiahNumber(e.gaji_pokok_bulanan) : String(e.gaji_pokok_bulanan || 0),
                uang_kehadiran_harian: window.formatRupiahNumber ? window.formatRupiahNumber(e.uang_kehadiran_harian) : String(e.uang_kehadiran_harian || 0),
                tunjangan_bulanan: window.formatRupiahNumber ? window.formatRupiahNumber(e.tunjangan_bulanan) : String(e.tunjangan_bulanan || 0),
                persentase_komisi_sales: Number(e.persentase_komisi_sales || 0),
                nomor_telepon: e.nomor_telepon || '',
                alamat: e.alamat === '-' ? '' : (e.alamat || ''),
                nomor_polisi_kendaraan: e.nomor_polisi_kendaraan || '',
                bank_nama: e.bank_nama || 'Tunai',
                bank_nomor_rekening: e.bank_nomor_rekening || '',
                bank_atas_nama: e.bank_atas_nama || '',
                status_aktif: Boolean(e.status_aktif)
            };
            this.showModal = true;
            this.$nextTick(() => lucide.createIcons());
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

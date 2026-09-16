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
        <div class="page-header-actions" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <?php if (\App\Core\Auth::can('master.employees_manage')): ?>
            <button @click="openTierModal()" class="btn btn-secondary" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;">
                <i data-lucide="award" class="w-4 h-4 text-amber-500"></i>
                <span>Atur Skema Komisi Bertingkat</span>
                <span class="badge badge-warning text-[10px] py-0.5 px-1.5" x-text="editableTiers.length + ' Tier'"></span>
            </button>
            <?php endif; ?>
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
                                    <template x-if="e.status_aktif">
                                        <button @click="deactivateEmployee(e.id, e.nama_karyawan)" class="btn btn-ghost btn-sm" style="padding:6px 8px;color:#ef4444;" title="Nonaktifkan Karyawan">
                                            <i data-lucide="user-x" style="width:14px;height:14px;"></i>
                                        </button>
                                    </template>
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

    <!-- ========================================================================= -->
    <!-- MODAL PENGATURAN SKEMA KOMISI BERTINGKAT                                  -->
    <!-- ========================================================================= -->
    <template x-if="showTierModal">
    <div class="modal-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);z-index:999;display:flex;align-items:center;justify-content:center;padding:16px;" @click.self="showTierModal = false">
        <div class="card w-full max-w-4xl max-h-[92vh] flex flex-col p-0 overflow-hidden shadow-2xl rounded-2xl" style="background:var(--color-canvas);border:1px solid var(--color-hairline);" @click.stop>
            
            <!-- Modal Header -->
            <div class="p-4 sm:p-5 border-b flex items-center justify-between" style="border-color:var(--color-hairline);background:var(--color-canvas-soft);">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(245,158,11,0.15);color:#f59e0b;">
                        <i data-lucide="award" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black" style="color:var(--color-ink);">Pengaturan Skema Komisi Sales Bertingkat</h3>
                        <p class="text-xs" style="color:var(--color-ink-mute);">Konfigurasi ambang batas omzet bulanan terpusat &amp; simulasi komisi otomatis</p>
                    </div>
                </div>
                <button type="button" @click="showTierModal = false" class="btn btn-icon btn-secondary" title="Tutup">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="p-4 sm:p-6 overflow-y-auto space-y-6 flex-1 text-xs">

                <!-- Alert Penjelasan Sistem -->
                <div class="p-3.5 rounded-xl flex items-start gap-3" style="background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.25);">
                    <i data-lucide="info" class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5"></i>
                    <div class="space-y-1">
                        <div class="font-bold text-amber-600 dark:text-amber-400">Aturan Perhitungan Flat Retroaktif &amp; Kas Terbayar:</div>
                        <p class="text-slate-600 dark:text-slate-300 leading-relaxed text-[11.5px]">
                            • Omzet dihitung dari akumulasi <strong>faktur konsinyasi &amp; B2B yang sudah dibayar (uang masuk)</strong> dalam 1 bulan kalender.<br>
                            • Saat akumulasi omzet mencapai batas tier (misal Tier 3: 4%), seluruh omzet terbayar tersebut langsung dikalikan 4%.<br>
                            • Sisa hutang toko dan kunjungan tanpa faktur otomatis dikecualikan dari omzet sampai toko melunasi.
                        </p>
                    </div>
                </div>

                <!-- Tabel Konfigurasi Tier -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs uppercase tracking-wider" style="color:var(--color-ink-secondary);">
                            DAFTAR TINGKATAN TIER (URUTAN RENDAH KE TINGGI)
                        </span>
                        <button type="button" @click="addTierRow()" class="btn btn-secondary btn-sm" style="font-weight:700;font-size:11px;">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>Tambah Baris Tier</span>
                        </button>
                    </div>

                    <div class="table-responsive rounded-xl border overflow-hidden" style="border-color:var(--color-hairline);">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:60px;text-align:center;">#</th>
                                    <th style="min-width:140px;">Nama Tingkatan (Tier)</th>
                                    <th style="min-width:140px;">Omzet Min (Rp)</th>
                                    <th style="min-width:160px;">Omzet Maks (Rp)</th>
                                    <th style="width:110px;text-align:center;">Komisi (%)</th>
                                    <th style="width:60px;text-align:center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(tier, idx) in editableTiers" :key="idx">
                                    <tr>
                                        <!-- Urutan -->
                                        <td class="text-center font-bold" style="color:var(--color-ink-mute);" x-text="idx + 1"></td>

                                        <!-- Nama Tier -->
                                        <td>
                                            <input type="text" x-model="tier.nama_tier" class="form-input text-xs font-bold" placeholder="Misal: Tier 1 (Dasar)" required>
                                        </td>

                                        <!-- Omzet Min -->
                                        <td>
                                            <input type="text" 
                                                   :value="formatInputRupiah(tier.omzet_min)"
                                                   @input="tier.omzet_min = parseInputRupiah($event.target.value); $event.target.value = formatInputRupiah(tier.omzet_min); runSimulation();"
                                                   class="form-input text-xs font-mono" placeholder="0">
                                        </td>

                                        <!-- Omzet Maks & Checkbox Tanpa Batas -->
                                        <td>
                                            <div class="space-y-1.5">
                                                <input type="text" 
                                                       :disabled="tier.tanpa_batas"
                                                       :value="tier.tanpa_batas ? 'Tanpa Batas Atas (∞)' : formatInputRupiah(tier.omzet_maks)"
                                                       @input="tier.omzet_maks = parseInputRupiah($event.target.value); $event.target.value = formatInputRupiah(tier.omzet_maks); runSimulation();"
                                                       :class="tier.tanpa_batas ? 'form-input text-xs bg-slate-500/10 italic text-slate-400' : 'form-input text-xs font-mono'" 
                                                       placeholder="Batas atas">
                                                <label class="flex items-center gap-1.5 cursor-pointer text-[11px]" style="color:var(--color-ink-mute);">
                                                    <input type="checkbox" x-model="tier.tanpa_batas" @change="if(tier.tanpa_batas) tier.omzet_maks = null; runSimulation();" class="w-3.5 h-3.5 rounded">
                                                    <span>Tanpa Batas Atas</span>
                                                </label>
                                            </div>
                                        </td>

                                        <!-- Persentase Komisi -->
                                        <td class="text-center">
                                            <div class="flex items-center gap-1 justify-center">
                                                <input type="number" step="0.1" min="0" max="100" x-model.number="tier.persentase" @input="runSimulation()" class="form-input text-xs font-bold text-center w-16" placeholder="0">
                                                <span class="font-bold" style="color:var(--color-ink-mute);">%</span>
                                            </div>
                                        </td>

                                        <!-- Tombol Hapus -->
                                        <td class="text-center">
                                            <button type="button" @click="removeTierRow(idx)" class="btn btn-icon btn-ghost btn-sm text-rose-500 hover:bg-rose-500/10" :disabled="editableTiers.length <= 1" title="Hapus Baris">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- LIVE SIMULATOR / KALKULATOR CEPAT -->
                <div class="p-4 rounded-xl space-y-3" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calculator" class="w-4 h-4 text-emerald-500"></i>
                            <span class="font-bold text-xs" style="color:var(--color-ink);">KALKULATOR SIMULATOR OMZET CEPAT</span>
                        </div>
                        <span class="text-[11px]" style="color:var(--color-ink-mute);">Uji coba angka omzet dengan skema di atas</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                        <div>
                            <label class="form-label text-[11px]">Input Omzet Terbayar:</label>
                            <input type="text" 
                                   :value="formatInputRupiah(simulasiOmzet)" 
                                   @input="simulasiOmzet = parseInputRupiah($event.target.value); $event.target.value = formatInputRupiah(simulasiOmzet); runSimulation();"
                                   class="form-input font-mono font-bold text-xs" style="height:36px;">
                        </div>

                        <!-- Hasil Simulasi: Tier & Rate -->
                        <div class="p-2.5 rounded-lg border flex flex-col justify-center" style="background:var(--color-canvas);border-color:var(--color-hairline);min-height:54px;">
                            <div class="text-[10px] text-slate-500 font-bold uppercase">Tier &amp; Persentase Diraih:</div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="font-black text-xs text-amber-500" x-text="simResult.nama_tier"></span>
                                <span class="badge badge-success text-[10px] py-0.5 px-1.5 font-bold" x-text="simResult.persentase + '%'"></span>
                            </div>
                        </div>

                        <!-- Hasil Simulasi: Nominal Komisi -->
                        <div class="p-2.5 rounded-lg border flex flex-col justify-center" style="background:var(--color-canvas);border-color:var(--color-hairline);min-height:54px;">
                            <div class="text-[10px] text-slate-500 font-bold uppercase">Estimasi Nominal Komisi:</div>
                            <div class="font-black text-sm text-emerald-600 dark:text-emerald-400 mt-0.5" x-text="formatRupiah(simResult.nominal_komisi)"></div>
                        </div>
                    </div>

                    <!-- Progress / Gap ke Tier Berikutnya -->
                    <template x-if="simResult.has_next">
                        <div class="p-2.5 rounded-lg border text-[11px] flex items-center justify-between" style="background:rgba(59,130,246,0.06);border-color:rgba(59,130,246,0.25);color:var(--color-ink);">
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="target" class="w-3.5 h-3.5 text-blue-500"></i>
                                <span>Kurang <strong class="text-blue-600 dark:text-blue-400 font-mono" x-text="formatRupiah(simResult.gap_omzet)"></strong> untuk naik ke <strong x-text="simResult.next_tier_nama"></strong> (<span x-text="simResult.next_tier_persen + '%'"></span>)</span>
                            </div>
                        </div>
                    </template>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="p-4 border-t flex items-center justify-between" style="border-color:var(--color-hairline);background:var(--color-canvas-soft);">
                <div class="text-[11px]" style="color:var(--color-ink-mute);">
                    Perubahan langsung berlaku pada perhitungan di menu Rekap Komisi Sales.
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="showTierModal = false" class="btn btn-secondary text-xs" :disabled="isSavingTiers">
                        Batal
                    </button>
                    <button type="button" @click="saveTierConfig()" class="btn btn-primary text-xs font-bold" :disabled="isSavingTiers" style="background:#f59e0b;border-color:#f59e0b;color:#090d16;">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span x-text="isSavingTiers ? 'Menyimpan...' : 'Simpan Skema Komisi'"></span>
                    </button>
                </div>
            </div>

        </div>
    </div>
    </template>

    <!-- HIDDEN FORM FOR DEACTIVATING EMPLOYEE -->
    <form id="delete-employee-form" action="<?= Router::url('/employees/delete') ?>" method="POST" data-action-text="Menonaktifkan karyawan..." style="display:none;">
        <input type="hidden" name="id" id="delete-employee-id">
    </form>

</div>

<script>
function employeeApp() {
    return {
        employees: <?= json_encode($employees) ?>,
        searchQuery: '',
        filterPosition: 'all',
        showModal: false,
        isEdit: false,

        // Skema Komisi Bertingkat State
        showTierModal: false,
        isSavingTiers: false,
        simulasiOmzet: 35000000,
        simResult: {
            nama_tier: 'Tier 2 (Reguler)',
            persentase: 2.5,
            nominal_komisi: 875000,
            has_next: true,
            gap_omzet: 1,
            next_tier_nama: 'Tier 3 (Gold)',
            next_tier_persen: 4.0
        },
        editableTiers: (<?= json_encode($commissionTiers ?? []) ?>).map(t => ({
            id: t.id,
            urutan: Number(t.urutan),
            nama_tier: t.nama_tier,
            omzet_min: Number(t.omzet_min),
            omzet_maks: t.omzet_maks !== null ? Number(t.omzet_maks) : null,
            tanpa_batas: t.omzet_maks === null,
            persentase: Number(t.persentase),
            status_aktif: Boolean(t.status_aktif)
        })),

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
            this.runSimulation();
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

        formatInputRupiah(val) {
            if (val === null || val === undefined || val === '') return '';
            return Number(val).toLocaleString('id-ID');
        },

        parseInputRupiah(str) {
            if (!str) return 0;
            const cleaned = String(str).replace(/[^0-9]/g, '');
            return cleaned ? parseFloat(cleaned) : 0;
        },

        openTierModal() {
            this.showTierModal = true;
            this.runSimulation();
            this.$nextTick(() => lucide.createIcons());
        },

        addTierRow() {
            const lastTier = this.editableTiers[this.editableTiers.length - 1];
            let nextMin = 0;
            if (lastTier) {
                if (lastTier.tanpa_batas) {
                    lastTier.tanpa_batas = false;
                    lastTier.omzet_maks = lastTier.omzet_min + 20000000;
                }
                nextMin = (lastTier.omzet_maks || lastTier.omzet_min) + 1;
            }
            const nextUrutan = this.editableTiers.length + 1;
            this.editableTiers.push({
                id: null,
                urutan: nextUrutan,
                nama_tier: 'Tier ' + nextUrutan,
                omzet_min: nextMin,
                omzet_maks: null,
                tanpa_batas: true,
                persentase: lastTier ? Math.min(100, lastTier.persentase + 1) : 1,
                status_aktif: true
            });
            this.runSimulation();
            this.$nextTick(() => lucide.createIcons());
        },

        removeTierRow(idx) {
            if (this.editableTiers.length <= 1) {
                alert('Minimal harus ada 1 tier komisi.');
                return;
            }
            this.editableTiers.splice(idx, 1);
            this.runSimulation();
            this.$nextTick(() => lucide.createIcons());
        },

        runSimulation() {
            const omzet = Number(this.simulasiOmzet || 0);
            const tiers = this.editableTiers.slice().sort((a, b) => a.urutan - b.urutan);
            let matched = null;
            for (let i = tiers.length - 1; i >= 0; i--) {
                const t = tiers[i];
                if (omzet >= t.omzet_min && (t.tanpa_batas || t.omzet_maks === null || omzet <= t.omzet_maks)) {
                    matched = t;
                    break;
                }
            }

            if (!matched && tiers.length > 0) {
                matched = {
                    nama_tier: 'Di Bawah Minimum',
                    persentase: 0,
                    urutan: 0
                };
            }

            const persen = matched ? matched.persentase : 0;
            const nominal = Math.round(omzet * (persen / 100));

            // Next tier
            let nextTier = null;
            if (matched) {
                nextTier = tiers.find(t => t.urutan > matched.urutan);
            } else if (tiers.length > 0) {
                nextTier = tiers[0];
            }

            let gap = 0;
            if (nextTier) {
                gap = Math.max(0, nextTier.omzet_min - omzet);
            }

            this.simResult = {
                nama_tier: matched ? matched.nama_tier : 'Tanpa Tier',
                persentase: persen,
                nominal_komisi: nominal,
                has_next: Boolean(nextTier),
                gap_omzet: gap,
                next_tier_nama: nextTier ? nextTier.nama_tier : '',
                next_tier_persen: nextTier ? nextTier.persentase : 0
            };
        },

        async saveTierConfig() {
            this.isSavingTiers = true;
            try {
                const payload = {
                    tiers: this.editableTiers.map((t, i) => ({
                        id: t.id,
                        urutan: i + 1,
                        nama_tier: t.nama_tier,
                        omzet_min: t.omzet_min,
                        omzet_maks: t.tanpa_batas ? null : t.omzet_maks,
                        tanpa_batas: t.tanpa_batas,
                        persentase: t.persentase,
                        status_aktif: true
                    }))
                };

                const res = await fetch('<?= Router::url('/employees/commission-tiers/batch-save') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.message || 'Gagal menyimpan skema komisi');
                }

                alert(data.message || 'Skema komisi berhasil diperbarui!');
                window.location.reload();

            } catch (err) {
                alert(err.message || 'Terjadi kesalahan sistem saat menyimpan skema.');
            } finally {
                this.isSavingTiers = false;
            }
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
        },

        async deactivateEmployee(id, name) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Nonaktifkan Karyawan',
                message: `Apakah Anda yakin ingin menonaktifkan karyawan "${name}"? Seluruh data historis pesanan, pengiriman, dan penggajian tetap aman tersimpan.`,
                type: 'danger',
                confirmText: 'Ya, Nonaktifkan'
            }) : confirm(`Nonaktifkan karyawan "${name}"?`);

            if (confirmed) {
                document.getElementById('delete-employee-id').value = id;
                document.getElementById('delete-employee-form').submit();
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

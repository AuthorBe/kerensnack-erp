<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
$activeTab = $_GET['tab'] ?? 'customers';
?>

<div x-data="customerApp('<?= htmlspecialchars($activeTab) ?>')" x-init="init()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-cyan">
                <i data-lucide="store"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#06b6d4;"></span>
                    <span>Master Pelanggan &amp; Wilayah</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Master Toko Pelanggan &amp; Wilayah' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Kelola Data Toko, Tier Harga, Rute Logistik &amp; Item Khusus Toko' ?></p>
            </div>
        </div>
    </div>

    <!-- TOP STATS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="store"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Toko Terdaftar</div>
                <div class="stat-card-value"><?= count($customers) ?> Toko</div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Mitra Ritel &amp; Grosir</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(99,102,241,0.1);color:#6366f1;">
                <i data-lucide="users"></i>
            </div>
            <div>
                <div class="stat-card-label">Master Tier Pelanggan</div>
                <div class="stat-card-value" style="color:#6366f1;"><?= count($customerGroups) ?> Tier</div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Kategori toko &amp; level harga</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                <i data-lucide="map-pin"></i>
            </div>
            <div>
                <div class="stat-card-label">Master Wilayah &amp; Rute</div>
                <div class="stat-card-value" style="color:#3b82f6;"><?= count($territories) ?> Rute</div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Peta Distribusi &amp; Supplier</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
                <i data-lucide="clock"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Piutang Berjalan</div>
                <div class="stat-card-value" style="color:#f59e0b;font-size:17px;">
                    <?= Format::rupiah(array_sum(array_column($customers, 'total_piutang_berjalan'))) ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Tempo &amp; Konsinyasi Rak</div>
            </div>
        </div>
    </div>

    <!-- SUB-TABS NAVIGATION -->
    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-lg border border-hairline overflow-x-auto no-scrollbar w-full sm:w-auto">
        <button type="button"
                @click="activeTab = 'customers'"
                :class="activeTab === 'customers' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                style="font-size:12px;font-weight:700;white-space:nowrap;padding:6px 12px;">
            <i data-lucide="store" style="width:14px;height:14px;"></i>
            <span>1. Daftar Toko Pelanggan (<?= count($customers) ?>)</span>
        </button>

        <button type="button"
                @click="activeTab = 'customer_groups'"
                :class="activeTab === 'customer_groups' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                style="font-size:12px;font-weight:700;white-space:nowrap;padding:6px 12px;">
            <i data-lucide="users" style="width:14px;height:14px;"></i>
            <span>2. Master Grup &amp; Tier (<?= count($customerGroups) ?>)</span>
        </button>

        <button type="button"
                @click="activeTab = 'territories'"
                :class="activeTab === 'territories' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                style="font-size:12px;font-weight:700;white-space:nowrap;padding:6px 12px;">
            <i data-lucide="map-pin" style="width:14px;height:14px;"></i>
            <span>3. Master Wilayah &amp; Rute (<?= count($territories) ?>)</span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: DAFTAR TOKO PELANGGAN                                              -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'customers'" class="card" style="padding:0;overflow:hidden;">
        <!-- FILTER & ACTION BAR -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 sm:p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
                <!-- Search Input -->
                <div class="form-input-icon flex-1 sm:max-w-xs">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="text" x-model="searchQuery" placeholder="Cari nama toko / pemilik / kode..." class="form-input" style="height:38px;font-size:13px;">
                </div>

                <!-- Filter Wilayah -->
                <select x-model="filterTerritory" class="form-input" style="height:38px;font-size:13px;max-width:180px;">
                    <option value="all">Semua Wilayah</option>
                    <?php foreach ($territories as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_wilayah']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Filter Tipe Bayar -->
                <select x-model="filterType" class="form-input" style="height:38px;font-size:13px;width:150px;">
                    <option value="all">Semua Tipe Bayar</option>
                    <option value="cash">Tunai (Cash)</option>
                    <option value="tempo">Tempo (Kredit)</option>
                    <option value="konsinyasi">Konsinyasi (Rak)</option>
                </select>
            </div>

            <button @click="openAddModal()" class="btn btn-primary" style="height:38px;white-space:nowrap;">
                <i data-lucide="plus"></i>
                <span>Tambah Toko Baru</span>
            </button>
        </div>

        <!-- TABLE LIST -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 1080px;">
                <thead>
                    <tr>
                        <th style="width:105px; min-width:90px;" class="cell-nowrap">Kode</th>
                        <th style="min-width:180px;">Nama Toko &amp; Pemilik</th>
                        <th style="min-width:160px;" class="cell-nowrap">Grup &amp; Tier Harga</th>
                        <th style="min-width:140px;">Wilayah / Rute</th>
                        <th class="cell-center cell-nowrap" style="width:120px; min-width:110px;">Tipe Bayar</th>
                        <th class="cell-center cell-nowrap" style="width:150px; min-width:140px;">Item Khusus Toko</th>
                        <th class="cell-right cell-nowrap" style="width:130px; min-width:120px;">Plafon Kredit</th>
                        <th class="cell-right cell-nowrap" style="width:130px; min-width:120px;">Piutang Berjalan</th>
                        <th class="cell-center cell-nowrap" style="width:110px; min-width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="c in filteredCustomers" :key="c.id">
                        <tr :style="!c.status_aktif ? 'opacity:0.5;' : ''">
                            <td class="cell-nowrap">
                                <span class="badge badge-mono" x-text="c.kode_pelanggan"></span>
                            </td>
                            <td>
                                <div style="font-weight:700;color:var(--color-ink);" x-text="c.nama_toko"></div>
                                <div style="font-size:11px;color:var(--color-ink-mute);" x-text="c.nama_pemilik ? ('Pemilik: ' + c.nama_pemilik) : c.alamat_lengkap"></div>
                                <template x-if="c.nomor_rekening">
                                    <div style="font-size:10.5px;color:var(--color-ink-mute);font-family:var(--font-mono);margin-top:2px;">
                                        <i data-lucide="building" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-right:2px;margin-top:-2px;"></i>
                                        <span x-text="(c.nama_bank || 'Bank') + ' &bull; ' + c.nomor_rekening + ' a.n. ' + (c.atas_nama_rekening || '-')"></span>
                                    </div>
                                </template>
                            </td>
                            <td class="cell-nowrap">
                                <div style="font-weight:600;" x-text="c.nama_grup || 'Ritel'"></div>
                                <div style="font-size:11px;font-weight:700;color:var(--color-primary);" x-text="'Level ' + (c.override_level_harga || c.default_level_harga || 1)"></div>
                            </td>
                            <td>
                                <div style="font-weight:600;" x-text="c.nama_wilayah || '-'"></div>
                                <div style="font-size:10.5px;color:var(--color-ink-mute);" x-text="c.kode_rute || ''"></div>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <template x-if="c.is_konsinyasi">
                                    <span class="badge badge-info">Konsinyasi Rak</span>
                                </template>
                                <template x-if="!c.is_konsinyasi">
                                    <span class="badge badge-secondary" style="text-transform:capitalize;" x-text="c.tipe_pembayaran_default ? c.tipe_pembayaran_default.replace(/_/g, ' ') : 'Cash'"></span>
                                </template>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <template x-if="c.total_item_khusus > 0">
                                    <button @click="openCustomerItemsModal(c)" class="badge badge-success" style="cursor:pointer;" title="Klik untuk ubah daftar item khusus">
                                        <span x-text="c.total_item_khusus + ' Item Khusus'"></span>
                                    </button>
                                </template>
                                <template x-if="!c.total_item_khusus || c.total_item_khusus == 0">
                                    <button @click="openCustomerItemsModal(c)" class="badge badge-secondary" style="cursor:pointer;opacity:0.75;" title="Klik untuk atur item khusus toko ini">
                                        <span>Semua Produk (Default)</span>
                                    </button>
                                </template>
                            </td>
                            <td class="cell-currency cell-right cell-nowrap" x-text="formatRupiah(c.plafon_piutang)"></td>
                            <td class="cell-currency cell-right cell-nowrap" :style="Number(c.total_piutang_berjalan) > 0 ? 'color:var(--color-warning);font-weight:700;' : 'color:var(--color-ink-mute);'" x-text="formatRupiah(c.total_piutang_berjalan)"></td>
                            <td class="cell-center cell-nowrap">
                                <div style="display:flex;align-items:center;justify-content:center;gap:4px;">
                                    <button @click="openCustomerItemsModal(c)" class="btn btn-ghost btn-sm" style="padding:6px;" title="Atur Item Khusus Toko">
                                        <i data-lucide="list-checks" style="width:15px;height:15px;color:var(--color-ink-secondary);"></i>
                                    </button>
                                    <button @click="openEditModal(c)" class="btn btn-ghost btn-sm" style="padding:6px;" title="Edit Data Toko">
                                        <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                    </button>
                                    <button @click="deleteCustomer(c.id, c.nama_toko)" class="btn btn-ghost btn-sm" style="padding:6px;color:#ef4444;" title="Hapus Toko">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="filteredCustomers.length === 0">
                        <tr>
                            <td colspan="9" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="search-x" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Tidak ada data toko pelanggan yang sesuai filter</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: MASTER GRUP PELANGGAN & TIER HARGA                                 -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'customer_groups'" class="card" style="padding:0;overflow:hidden;">
        <!-- ACTION & FILTER BAR -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="form-input-icon flex-1 sm:max-w-xs">
                <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                <input type="text" x-model="searchCustomerGroup" placeholder="Cari nama grup / kode..." class="form-input" style="height:38px;font-size:13px;">
            </div>

            <button @click="openAddCustomerGroupModal()" class="btn btn-primary" style="height:38px;white-space:nowrap;">
                <i data-lucide="plus"></i>
                <span>Tambah Grup Pelanggan</span>
            </button>
        </div>

        <!-- TABLE LIST GRUP PELANGGAN -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 960px;">
                <thead>
                    <tr>
                        <th style="width:120px; min-width:100px;" class="cell-nowrap">Kode Grup</th>
                        <th style="min-width:180px;">Nama Grup Pelanggan</th>
                        <th class="cell-center cell-nowrap" style="width:150px;">Default Level Harga</th>
                        <th class="cell-right cell-nowrap" style="width:130px;">Diskon Persen</th>
                        <th class="cell-right cell-nowrap" style="width:150px;">Diskon Nominal</th>
                        <th class="cell-center cell-nowrap" style="width:120px;">Toko Terdaftar</th>
                        <th class="cell-center cell-nowrap" style="width:90px;">Status</th>
                        <th class="cell-center cell-nowrap" style="width:90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="cg in filteredCustomerGroups" :key="cg.id">
                        <tr :style="!cg.status_aktif ? 'opacity:0.5;' : ''">
                            <td class="cell-nowrap">
                                <span class="badge badge-mono" x-text="cg.kode_grup"></span>
                            </td>
                            <td>
                                <strong style="font-size:13px;color:var(--color-ink);" x-text="cg.nama_grup"></strong>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <span class="badge badge-success" style="font-weight:800;" x-text="'Level ' + cg.default_level_harga"></span>
                            </td>
                            <td class="cell-right cell-nowrap font-mono" x-text="cg.diskon_persen_default > 0 ? (cg.diskon_persen_default + ' %') : '0 %'"></td>
                            <td class="cell-right cell-currency" x-text="formatRupiah(cg.diskon_nominal_default)"></td>
                            <td class="cell-center cell-nowrap">
                                <span class="badge badge-secondary" x-text="(cg.total_pelanggan || 0) + ' Toko'"></span>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <span :class="cg.status_aktif ? 'badge badge-success' : 'badge badge-danger'"
                                      x-text="cg.status_aktif ? 'Aktif' : 'Nonaktif'"></span>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <div style="display:flex;align-items:center;justify-content:center;gap:4px;">
                                    <button @click="openEditCustomerGroupModal(cg)" class="btn btn-ghost btn-sm" style="padding:6px;" title="Edit Grup">
                                        <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                    </button>
                                    <button @click="deleteCustomerGroup(cg.id, cg.nama_grup)" class="btn btn-ghost btn-sm" style="padding:6px;color:#ef4444;" title="Hapus Grup">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="filteredCustomerGroups.length === 0">
                        <tr>
                            <td colspan="8" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="users" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Tidak ada data grup pelanggan</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 3: MASTER WILAYAH & RUTE LOGISTIK                                     -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'territories'" class="card" style="padding:0;overflow:hidden;">
        <!-- ACTION & FILTER BAR -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="form-input-icon flex-1 sm:max-w-xs">
                <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                <input type="text" x-model="searchTerritory" placeholder="Cari nama wilayah / kota / rute..." class="form-input" style="height:38px;font-size:13px;">
            </div>

            <button @click="openAddTerritoryModal()" class="btn btn-primary" style="height:38px;white-space:nowrap;">
                <i data-lucide="plus"></i>
                <span>Tambah Wilayah / Rute Baru</span>
            </button>
        </div>

        <!-- TABLE LIST WILAYAH -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 960px;">
                <thead>
                    <tr>
                        <th style="width:110px; min-width:90px;" class="cell-nowrap">Kode Rute</th>
                        <th style="min-width:160px;">Nama Wilayah / Jalur</th>
                        <th style="min-width:140px;">Kota / Kabupaten</th>
                        <th style="min-width:120px;">Provinsi</th>
                        <th style="min-width:160px;">Sub-Wilayah / Catatan</th>
                        <th class="cell-center cell-nowrap" style="width:110px;">Toko Terhubung</th>
                        <th class="cell-center cell-nowrap" style="width:110px;">Vendor Terhubung</th>
                        <th class="cell-center cell-nowrap" style="width:90px;">Status</th>
                        <th class="cell-center cell-nowrap" style="width:90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="t in filteredTerritories" :key="t.id">
                        <tr :style="!t.status_aktif ? 'opacity:0.5;' : ''">
                            <td class="cell-nowrap">
                                <span class="badge badge-mono" x-text="t.kode_rute"></span>
                            </td>
                            <td>
                                <strong style="font-size:13px;color:var(--color-ink);" x-text="t.nama_wilayah"></strong>
                            </td>
                            <td x-text="t.kota_kabupaten"></td>
                            <td x-text="t.provinsi"></td>
                            <td style="font-size:12px;color:var(--color-ink-mute);" x-text="t.sub_wilayah || '-'"></td>
                            <td class="cell-center cell-nowrap">
                                <span class="badge badge-secondary" x-text="(t.total_pelanggan || 0) + ' Toko'"></span>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <span class="badge badge-secondary" x-text="(t.total_pemasok || 0) + ' Vendor'"></span>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <span :class="t.status_aktif ? 'badge badge-success' : 'badge badge-danger'"
                                      x-text="t.status_aktif ? 'Aktif' : 'Nonaktif'"></span>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <div style="display:flex;align-items:center;justify-content:center;gap:4px;">
                                    <button @click="openEditTerritoryModal(t)" class="btn btn-ghost btn-sm" style="padding:6px;" title="Edit Wilayah">
                                        <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                    </button>
                                    <button @click="deleteTerritory(t.id, t.nama_wilayah)" class="btn btn-ghost btn-sm" style="padding:6px;color:#ef4444;" title="Hapus Wilayah">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="filteredTerritories.length === 0">
                        <tr>
                            <td colspan="9" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="map-pin-off" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Tidak ada data wilayah / rute logistik</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODALS SECTION                                                            -->
    <!-- ========================================================================= -->

    <!-- MODAL 1: TAMBAH / EDIT TOKO PELANGGAN -->
    <template x-teleport="body">
    <div x-show="showModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:560px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title" x-text="isEdit ? 'Edit Data Toko Pelanggan' : 'Tambah Toko Pelanggan Baru'"></div>
                <button @click="showModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form :action="isEdit ? '<?= Router::url('/customers/update') ?>' : '<?= Router::url('/customers/store') ?>'" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="id" :value="form.id">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nama Toko *</label>
                        <input type="text" name="nama_toko" x-model="form.nama_toko" required class="form-input" placeholder="Contoh: TOKO MAJU JAYA">
                    </div>
                    <div>
                        <label class="form-label">Nama Pemilik / PIC</label>
                        <input type="text" name="nama_pemilik" x-model="form.nama_pemilik" class="form-input" placeholder="Contoh: Bpk. H. Slamet">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Grup Harga (Tier Level 1–28) *</label>
                        <select name="grup_pelanggan_id" x-model="form.grup_pelanggan_id" required class="form-input">
                            <?php foreach ($groups as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= htmlspecialchars(trim(preg_replace('/\s*\([^)]*\)/', '', $g['nama_grup']))) ?> — Level <?= $g['default_level_harga'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Wilayah / Rute Logistik</label>
                        <select name="wilayah_id" x-model="form.wilayah_id" class="form-input">
                            <option value="">-- Tanpa Rute Tertentu --</option>
                            <?php foreach ($territories as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_wilayah']) ?> — <?= $t['kode_rute'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nomor WhatsApp / HP</label>
                        <input type="text" name="nomor_whatsapp" x-model="form.nomor_whatsapp" class="form-input font-mono" placeholder="081234567890">
                    </div>
                    <div>
                        <label class="form-label">Tipe Pembayaran Default *</label>
                        <select name="tipe_pembayaran_default" x-model="form.tipe_pembayaran_default" class="form-input">
                            <option value="cash">Tunai (Cash)</option>
                            <option value="tempo_7_hari">Tempo 7 Hari</option>
                            <option value="tempo_14_hari">Tempo 14 Hari</option>
                            <option value="tempo_30_hari">Tempo 30 Hari</option>
                            <option value="konsinyasi">Konsinyasi</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label">Plafon Maksimal Piutang (Rp)</label>
                    <input type="text" name="plafon_piutang" x-model="form.plafon_piutang" class="form-input font-mono input-rupiah" placeholder="5.000.000">
                </div>

                <div>
                    <label class="form-label">Alamat Lengkap Toko</label>
                    <textarea name="alamat_lengkap" x-model="form.alamat_lengkap" class="form-input" rows="2" placeholder="Jl. Raya Pasar..."></textarea>
                </div>

                <!-- REKENING BANK (OPSIONAL) -->
                <div style="padding:12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);">
                    <div style="font-size:11.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-primary);margin-bottom:10px;">Rekening Bank (Opsional)</div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div>
                            <label class="form-label" style="font-size:11px;">Nama Bank</label>
                            <input type="text" name="nama_bank" x-model="form.nama_bank" class="form-input" placeholder="BCA / Mandiri / BRI">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:11px;">Nomor Rekening</label>
                            <input type="text" name="nomor_rekening" x-model="form.nomor_rekening"
                                   @input="form.nomor_rekening = $event.target.value.replace(/[^0-9-]/g, '').slice(0, 25)"
                                   maxlength="25" class="form-input font-mono" placeholder="Nomor rekening">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:11px;">Atas Nama <template x-if="form.nomor_rekening"><span style="color:var(--color-danger);">*</span></template></label>
                            <input type="text" name="atas_nama_rekening" x-model="form.atas_nama_rekening"
                                   :required="!!form.nomor_rekening"
                                   class="form-input" placeholder="Nama pemilik rekening">
                        </div>
                    </div>
                    <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:6px;">Opsional. Jika nomor rekening diisi, pemilik rekening wajib diisi.</div>
                </div>

                <template x-if="isEdit">
                    <div style="display:flex;align-items:center;gap:8px;padding-top:4px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;">
                            <input type="checkbox" name="status_aktif" x-model="form.status_aktif" style="width:16px;height:16px;accent-color:var(--color-primary);">
                            <span>Status Toko Aktif (Dapat Bertransaksi)</span>
                        </label>
                    </div>
                </template>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Toko'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- MODAL 2: ATUR ITEM KHUSUS TOKO (WHITELIST) -->
    <template x-teleport="body">
    <div x-show="showItemsModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:620px;padding:24px;">
            <div class="modal-header">
                <div>
                    <div class="modal-title">Atur Daftar Item Khusus Toko</div>
                    <div style="font-size:12.5px;color:var(--color-ink-mute);margin-top:2px;" x-text="selectedCustomer?.nama_toko + ' (' + selectedCustomer?.kode_pelanggan + ')'"></div>
                </div>
                <button @click="showItemsModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/customers/save-items') ?>" method="POST" style="display:flex;flex-direction:column;gap:12px;">
                <input type="hidden" name="pelanggan_id" :value="selectedCustomer?.id">

                <div style="padding:10px 12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);font-size:12px;color:var(--color-ink-mute);">
                    💡 <em>Centang barang jadi yang biasa dibeli atau dipajang di toko ini. Jika <strong>tidak ada yang dicentang</strong>, maka toko ini diizinkan membeli <strong>semua produk (<?= count($finishedGoods) ?> SKU)</strong> secara default di POS.</em>
                </div>

                <!-- SEARCH & BULK ACTIONS -->
                <div class="flex items-center justify-between gap-2">
                    <input type="text" x-model="searchItemModal" placeholder="Cari nama snack / varian / SKU..." class="form-input" style="height:36px;font-size:12.5px;flex:1;">
                    <div style="display:flex;gap:6px;">
                        <button type="button" @click="selectAllItems()" class="btn btn-secondary btn-sm" style="font-size:11.5px;padding:4px 8px;">Pilih Semua</button>
                        <button type="button" @click="deselectAllItems()" class="btn btn-secondary btn-sm" style="font-size:11.5px;padding:4px 8px;">Kosongkan</button>
                    </div>
                </div>

                <!-- ITEM CHECKLIST CONTAINER -->
                <div style="max-height:300px;overflow-y:auto;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);padding:8px;" class="custom-scrollbar space-y-1">
                    <template x-for="item in filteredModalItems" :key="item.id">
                        <label style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-radius:var(--rounded-sm);cursor:pointer;transition:background 0.1s ease;"
                               :style="isItemSelected(item.id) ? 'background:rgba(62,207,142,0.1);' : 'background:var(--color-canvas);'">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <input type="checkbox" name="item_ids[]" :value="item.id" :checked="isItemSelected(item.id)" @change="toggleItemSelection(item.id)"
                                       style="width:16px;height:16px;accent-color:var(--color-primary);">
                                <div>
                                    <div style="font-weight:700;font-size:12.5px;color:var(--color-ink);" x-text="item.nama_item"></div>
                                    <div style="font-size:11px;color:var(--color-ink-mute);" x-text="'SKU: ' + item.kode_sku + ' | Grup: ' + (item.nama_grup || '-')"></div>
                                </div>
                            </div>
                            <span class="badge badge-mono" style="font-size:11px;" x-text="item.kode_sku"></span>
                        </label>
                    </template>

                    <template x-if="filteredModalItems.length === 0">
                        <div style="text-align:center;padding:20px;font-size:12px;color:var(--color-ink-mute);">
                            Tidak ada produk yang cocok dengan pencarian
                        </div>
                    </template>
                </div>

                <!-- SUMMARY FOOTER -->
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:4px;">
                    <span style="font-size:12px;font-weight:600;color:var(--color-ink-secondary);">
                        Terpilih: <strong style="color:var(--color-primary);" x-text="selectedItemIds.length"></strong> dari <?= count($finishedGoods) ?> Barang Jadi
                    </span>

                    <div style="display:flex;gap:8px;">
                        <button type="button" @click="showItemsModal = false" class="btn btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="save"></i>
                            <span>Simpan Daftar Item</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- MODAL 3: TAMBAH / EDIT GRUP PELANGGAN -->
    <template x-teleport="body">
    <div x-show="showCustomerGroupModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:460px;padding:24px;">
            <div class="modal-header">
                <div>
                    <div class="modal-title" x-text="isEditCustomerGroup ? 'Edit Grup Pelanggan' : 'Tambah Grup Pelanggan Baru'"></div>
                    <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">Konfigurasi tier toko dan diskon standar</div>
                </div>
                <button @click="showCustomerGroupModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form :action="isEditCustomerGroup ? '<?= Router::url('/customers/update-group') ?>' : '<?= Router::url('/customers/store-group') ?>'" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <template x-if="isEditCustomerGroup">
                    <input type="hidden" name="id" :value="customerGroupForm.id">
                </template>

                <div>
                    <label class="form-label">Nama Grup Pelanggan *</label>
                    <input type="text" name="nama_grup" x-model="customerGroupForm.nama_grup" required class="form-input" placeholder="Contoh: Grup Agen Distributor Grosir">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Kode Grup (ID) *</label>
                        <div class="input-group-addon">
                            <span class="addon-prefix">GRP-</span>
                            <input type="text" 
                                   x-model="customerGroupForm.kode_suffix" 
                                   @input="customerGroupForm.kode_suffix = $event.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 10)"
                                   maxlength="10" 
                                   class="form-input font-mono uppercase addon-input" 
                                   placeholder="AGEN-01 / MITRA">
                        </div>
                        <input type="hidden" name="kode_grup" :value="'GRP-' + (customerGroupForm.kode_suffix || '').trim()">
                        <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:3px;">Prefix <code>GRP-</code> otomatis. Maks. 10 huruf/angka.</div>
                    </div>
                    <div>
                        <label class="form-label">Default Level Harga (1–28) *</label>
                        <select name="default_level_harga" x-model.number="customerGroupForm.default_level_harga" class="form-input">
                            <?php for ($i = 1; $i <= 28; $i++): ?>
                            <option value="<?= $i ?>">Level <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Diskon Default (%)</label>
                        <input type="number" step="0.1" name="diskon_persen_default" x-model.number="customerGroupForm.diskon_persen_default" class="form-input font-mono" placeholder="0">
                    </div>
                    <div>
                        <label class="form-label">Diskon Default (Rp / Pcs)</label>
                        <input type="text" name="diskon_nominal_default" x-model="customerGroupForm.diskon_nominal_default" class="form-input font-mono input-rupiah" placeholder="0">
                    </div>
                </div>

                <template x-if="isEditCustomerGroup">
                    <div style="display:flex;align-items:center;gap:8px;padding-top:4px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;">
                            <input type="checkbox" name="status_aktif" x-model="customerGroupForm.status_aktif" style="width:16px;height:16px;accent-color:var(--color-primary);">
                            <span>Grup Pelanggan Aktif</span>
                        </label>
                    </div>
                </template>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
                    <button type="button" @click="showCustomerGroupModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span x-text="isEditCustomerGroup ? 'Simpan Perubahan' : 'Tambah Grup'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- MODAL 4: TAMBAH / EDIT MASTER WILAYAH -->
    <template x-teleport="body">
    <div x-show="showTerritoryModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:480px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title" x-text="isEditTerritory ? 'Edit Wilayah / Rute' : 'Tambah Wilayah / Rute Baru'"></div>
                <button @click="showTerritoryModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form :action="isEditTerritory ? '<?= Router::url('/customers/update-territory') ?>' : '<?= Router::url('/customers/store-territory') ?>'" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="id" :value="territoryForm.id">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Kode Rute (ID) *</label>
                        <div class="input-group-addon">
                            <span class="addon-prefix">RTE-</span>
                            <input type="text" 
                                   x-model="territoryForm.kode_suffix" 
                                   @input="territoryForm.kode_suffix = $event.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 8)"
                                   maxlength="8" 
                                   class="form-input font-mono uppercase addon-input" 
                                   placeholder="001 / BDG-01">
                        </div>
                        <input type="hidden" name="kode_rute" :value="'RTE-' + (territoryForm.kode_suffix || '').trim()">
                        <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:3px;">Prefix <code>RTE-</code> otomatis. Maks. 8 huruf/angka.</div>
                    </div>
                    <div>
                        <label class="form-label">Nama Wilayah / Jalur *</label>
                        <input type="text" name="nama_wilayah" x-model="territoryForm.nama_wilayah" required class="form-input" placeholder="Contoh: Bandung Timur">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Kota / Kabupaten *</label>
                        <input type="text" name="kota_kabupaten" x-model="territoryForm.kota_kabupaten" required class="form-input" placeholder="Contoh: Bandung">
                    </div>
                    <div>
                        <label class="form-label">Provinsi *</label>
                        <input type="text" name="provinsi" x-model="territoryForm.provinsi" required class="form-input" placeholder="Contoh: Jawa Barat">
                    </div>
                </div>

                <div>
                    <label class="form-label">Sub-Wilayah / Rincian Daerah / Catatan</label>
                    <textarea name="sub_wilayah" x-model="territoryForm.sub_wilayah" class="form-input" rows="2" placeholder="Contoh: Rancaekek, Cileunyi, Tanjungsari..."></textarea>
                </div>

                <template x-if="isEditTerritory">
                    <div>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;font-weight:600;">
                            <input type="checkbox" name="status_aktif" x-model="territoryForm.status_aktif" style="width:15px;height:15px;accent-color:var(--color-primary);">
                            <span>Status Wilayah Aktif</span>
                        </label>
                    </div>
                </template>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showTerritoryModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span x-text="isEditTerritory ? 'Simpan Perubahan' : 'Tambah Wilayah'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- HIDDEN FORMS FOR DELETING -->
    <form id="delete-customer-form" action="<?= Router::url('/customers/delete') ?>" method="POST" style="display:none;">
        <input type="hidden" name="id" id="delete-customer-id">
    </form>
    <form id="delete-group-form" action="<?= Router::url('/customers/delete-group') ?>" method="POST" style="display:none;">
        <input type="hidden" name="id" id="delete-group-id">
    </form>
    <form id="delete-territory-form" action="<?= Router::url('/customers/delete-territory') ?>" method="POST" style="display:none;">
        <input type="hidden" name="id" id="delete-territory-id">
    </form>

</div>

<script>
function customerApp(initialTab) {
    return {
        activeTab: initialTab || 'customers',
        customers: <?= json_encode($customers) ?>,
        groups: <?= json_encode($groups) ?>,
        customerGroups: <?= json_encode($customerGroups) ?>,
        territories: <?= json_encode($territories) ?>,
        finishedGoods: <?= json_encode($finishedGoods) ?>,
        customerItemsMap: <?= json_encode($customerItemsMap) ?>,

        searchQuery: '',
        filterTerritory: 'all',
        filterType: 'all',
        searchCustomerGroup: '',
        searchTerritory: '',
        searchItemModal: '',

        showModal: false,
        showItemsModal: false,
        showCustomerGroupModal: false,
        showTerritoryModal: false,

        isEdit: false,
        isEditCustomerGroup: false,
        isEditTerritory: false,

        selectedCustomer: null,
        selectedItemIds: [],

        form: {
            id: '',
            nama_toko: '',
            nama_pemilik: '',
            grup_pelanggan_id: '',
            wilayah_id: '',
            alamat_lengkap: '',
            nomor_telepon: '',
            nomor_whatsapp: '',
            tipe_pembayaran_default: 'cash',
            plafon_piutang: '5.000.000',
            is_konsinyasi: false,
            nama_bank: '',
            nomor_rekening: '',
            atas_nama_rekening: '',
            status_aktif: true
        },

        customerGroupForm: {
            id: '',
            nama_grup: '',
            kode_suffix: '',
            default_level_harga: 1,
            diskon_persen_default: 0,
            diskon_nominal_default: '0',
            status_aktif: true
        },

        territoryForm: {
            id: '',
            kode_rute: '',
            nama_wilayah: '',
            kota_kabupaten: 'Bandung',
            provinsi: 'Jawa Barat',
            sub_wilayah: '',
            status_aktif: true
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        get filteredCustomers() {
            return this.customers.filter(c => {
                const q = this.searchQuery.toLowerCase();
                const matchQuery = !q ||
                    c.nama_toko.toLowerCase().includes(q) ||
                    c.kode_pelanggan.toLowerCase().includes(q) ||
                    (c.nama_pemilik && c.nama_pemilik.toLowerCase().includes(q)) ||
                    (c.nama_grup && c.nama_grup.toLowerCase().includes(q)) ||
                    (c.nama_wilayah && c.nama_wilayah.toLowerCase().includes(q));

                const matchTerritory = this.filterTerritory === 'all' || c.wilayah_id === this.filterTerritory;

                const matchType = this.filterType === 'all' ||
                    (this.filterType === 'konsinyasi' && c.is_konsinyasi) ||
                    (this.filterType === 'cash' && !c.is_konsinyasi && c.tipe_pembayaran_default === 'cash') ||
                    (this.filterType === 'tempo' && !c.is_konsinyasi && c.tipe_pembayaran_default && c.tipe_pembayaran_default.startsWith('tempo'));

                return matchQuery && matchTerritory && matchType;
            });
        },

        get filteredCustomerGroups() {
            return this.customerGroups.filter(cg => {
                const q = this.searchCustomerGroup.toLowerCase();
                return !q ||
                    cg.nama_grup.toLowerCase().includes(q) ||
                    cg.kode_grup.toLowerCase().includes(q);
            });
        },

        get filteredTerritories() {
            return this.territories.filter(t => {
                const q = this.searchTerritory.toLowerCase();
                return !q ||
                    t.nama_wilayah.toLowerCase().includes(q) ||
                    t.kode_rute.toLowerCase().includes(q) ||
                    t.kota_kabupaten.toLowerCase().includes(q) ||
                    t.provinsi.toLowerCase().includes(q);
            });
        },

        get filteredModalItems() {
            const q = this.searchItemModal.toLowerCase();
            return this.finishedGoods.filter(i => {
                return !q ||
                    i.nama_item.toLowerCase().includes(q) ||
                    i.kode_sku.toLowerCase().includes(q) ||
                    (i.nama_grup && i.nama_grup.toLowerCase().includes(q));
            });
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        // --- TOKO PELANGGAN MODALS ---
        openAddModal() {
            this.showItemsModal = false;
            this.showCustomerGroupModal = false;
            this.showTerritoryModal = false;
            this.isEdit = false;
            this.form = {
                id: '',
                nama_toko: '',
                nama_pemilik: '',
                grup_pelanggan_id: this.groups[0]?.id || '',
                wilayah_id: this.territories[0]?.id || '',
                alamat_lengkap: '',
                nomor_telepon: '',
                nomor_whatsapp: '',
                tipe_pembayaran_default: 'cash',
                plafon_piutang: window.formatRupiahNumber ? window.formatRupiahNumber(5000000) : '5.000.000',
                is_konsinyasi: false,
                nama_bank: '',
                nomor_rekening: '',
                atas_nama_rekening: '',
                status_aktif: true
            };
            this.showModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditModal(c) {
            this.showItemsModal = false;
            this.showCustomerGroupModal = false;
            this.showTerritoryModal = false;
            this.isEdit = true;
            this.form = {
                id: c.id,
                nama_toko: c.nama_toko,
                nama_pemilik: c.nama_pemilik || '',
                grup_pelanggan_id: c.grup_pelanggan_id || '',
                wilayah_id: c.wilayah_id || '',
                alamat_lengkap: c.alamat_lengkap || '',
                nomor_telepon: c.nomor_telepon || '',
                nomor_whatsapp: c.nomor_whatsapp || '',
                tipe_pembayaran_default: Boolean(c.is_konsinyasi) ? 'konsinyasi' : (c.tipe_pembayaran_default || 'cash'),
                plafon_piutang: window.formatRupiahNumber ? window.formatRupiahNumber(c.plafon_piutang) : String(c.plafon_piutang || 0),
                nama_bank: c.nama_bank || '',
                nomor_rekening: c.nomor_rekening || '',
                atas_nama_rekening: c.atas_nama_rekening || '',
                status_aktif: Boolean(c.status_aktif)
            };
            this.showModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async deleteCustomer(id, name) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Toko Pelanggan',
                message: `Apakah Anda yakin ingin menghapus toko "${name}"?`,
                type: 'danger',
                confirmText: 'Ya, Hapus'
            }) : confirm(`Hapus toko "${name}"?`);

            if (confirmed) {
                document.getElementById('delete-customer-id').value = id;
                document.getElementById('delete-customer-form').submit();
            }
        },

        // --- CUSTOMER ITEMS WHITELIST ---
        openCustomerItemsModal(c) {
            this.showModal = false;
            this.showCustomerGroupModal = false;
            this.showTerritoryModal = false;
            this.selectedCustomer = c;
            this.selectedItemIds = Array.isArray(this.customerItemsMap[c.id]) ? [...this.customerItemsMap[c.id]] : [];
            this.searchItemModal = '';
            this.showItemsModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        isItemSelected(itemId) {
            return this.selectedItemIds.includes(itemId);
        },

        toggleItemSelection(itemId) {
            if (this.selectedItemIds.includes(itemId)) {
                this.selectedItemIds = this.selectedItemIds.filter(id => id !== itemId);
            } else {
                this.selectedItemIds.push(itemId);
            }
        },

        selectAllItems() {
            this.selectedItemIds = this.finishedGoods.map(i => i.id);
        },

        deselectAllItems() {
            this.selectedItemIds = [];
        },

        // --- CUSTOMER GROUPS MODALS ---
        openAddCustomerGroupModal() {
            this.showModal = false;
            this.showItemsModal = false;
            this.showTerritoryModal = false;
            this.isEditCustomerGroup = false;
            this.customerGroupForm = {
                id: '',
                nama_grup: '',
                kode_suffix: '',
                default_level_harga: 1,
                diskon_persen_default: 0,
                diskon_nominal_default: '0',
                status_aktif: true
            };
            this.showCustomerGroupModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditCustomerGroupModal(cg) {
            this.showModal = false;
            this.showItemsModal = false;
            this.showTerritoryModal = false;
            this.isEditCustomerGroup = true;
            this.customerGroupForm = {
                id: cg.id,
                nama_grup: cg.nama_grup,
                kode_suffix: (cg.kode_grup || '').replace(/^GRP-?/i, ''),
                default_level_harga: Number(cg.default_level_harga),
                diskon_persen_default: Number(cg.diskon_persen_default || 0),
                diskon_nominal_default: window.formatRupiahNumber ? window.formatRupiahNumber(cg.diskon_nominal_default) : String(cg.diskon_nominal_default || 0),
                status_aktif: Boolean(cg.status_aktif)
            };
            this.showCustomerGroupModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async deleteCustomerGroup(id, name) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Grup Pelanggan',
                message: `Apakah Anda yakin ingin menghapus "${name}"? Pastikan tidak ada toko pelanggan yang masih memakai grup ini.`,
                type: 'danger',
                confirmText: 'Ya, Hapus'
            }) : confirm(`Hapus grup pelanggan ${name}?`);

            if (confirmed) {
                document.getElementById('delete-group-id').value = id;
                document.getElementById('delete-group-form').submit();
            }
        },

        // --- TERRITORY MODALS ---
        openAddTerritoryModal() {
            this.showModal = false;
            this.showItemsModal = false;
            this.showCustomerGroupModal = false;
            this.isEditTerritory = false;
            this.territoryForm = {
                id: '',
                kode_suffix: '',
                nama_wilayah: '',
                kota_kabupaten: 'Bandung',
                provinsi: 'Jawa Barat',
                sub_wilayah: '',
                status_aktif: true
            };
            this.showTerritoryModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditTerritoryModal(t) {
            this.showModal = false;
            this.showItemsModal = false;
            this.showCustomerGroupModal = false;
            this.isEditTerritory = true;
            this.territoryForm = {
                id: t.id,
                kode_suffix: (t.kode_rute || '').replace(/^RTE-?/i, ''),
                nama_wilayah: t.nama_wilayah,
                kota_kabupaten: t.kota_kabupaten || 'Bandung',
                provinsi: t.provinsi || 'Jawa Barat',
                sub_wilayah: t.sub_wilayah || '',
                status_aktif: Boolean(t.status_aktif)
            };
            this.showTerritoryModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async deleteTerritory(id, name) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Wilayah / Rute',
                message: `Apakah Anda yakin ingin menghapus wilayah "${name}"?`,
                type: 'danger',
                confirmText: 'Ya, Hapus'
            }) : confirm(`Hapus wilayah "${name}"?`);

            if (confirmed) {
                document.getElementById('delete-territory-id').value = id;
                document.getElementById('delete-territory-form').submit();
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>


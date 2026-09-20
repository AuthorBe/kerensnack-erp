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
                <div class="stat-card-value"><?= number_format($totalGlobalCustomers ?? count($customers), 0, ',', '.') ?> Toko</div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">
                    <?= number_format($totalActiveCustomers ?? count($customers), 0, ',', '.') ?> Toko Aktif Bertransaksi
                </div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(99,102,241,0.1);color:#6366f1;">
                <i data-lucide="users"></i>
            </div>
            <div>
                <div class="stat-card-label">Master Grup Pelanggan</div>
                <div class="stat-card-value" style="color:#6366f1;"><?= count($customerGroups) ?> Grup</div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Kategori toko &amp; grup pelanggan</div>
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
            <div class="stat-card-icon" style="background:rgba(14,165,233,0.1);color:#0284c7;">
                <i data-lucide="boxes"></i>
            </div>
            <div>
                <div class="stat-card-label">Model Kerjasama Toko</div>
                <div class="stat-card-value" style="color:#0284c7;font-size:16px;">
                    <?= number_format($totalKonsinyasiCustomers ?? 0, 0, ',', '.') ?> Konsinyasi
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">
                    <?= number_format($totalRegulerCustomers ?? 0, 0, ',', '.') ?> Toko Putus / Tempo
                </div>
            </div>
        </div>
    </div>

    <!-- SUB-TABS NAVIGATION -->
    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-lg border border-hairline overflow-x-auto no-scrollbar w-full sm:w-auto">
        <button type="button"
                @click="switchTab('customers')"
                :class="activeTab === 'customers' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                style="font-size:12px;font-weight:700;white-space:nowrap;padding:6px 12px;">
            <i data-lucide="store" style="width:14px;height:14px;"></i>
            <span>1. Daftar Toko Pelanggan (<?= number_format($totalGlobalCustomers ?? count($customers), 0, ',', '.') ?>)</span>
        </button>

        <button type="button"
                @click="switchTab('customer_groups')"
                :class="activeTab === 'customer_groups' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                style="font-size:12px;font-weight:700;white-space:nowrap;padding:6px 12px;">
            <i data-lucide="users" style="width:14px;height:14px;"></i>
            <span>2. Master Grup Pelanggan (<?= count($customerGroups) ?>)</span>
        </button>

        <?php if (\App\Core\Auth::can('master.territories_manage')): ?>
        <button type="button"
                @click="switchTab('territories')"
                :class="activeTab === 'territories' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                style="font-size:12px;font-weight:700;white-space:nowrap;padding:6px 12px;">
            <i data-lucide="map-pin" style="width:14px;height:14px;"></i>
            <span>3. Master Wilayah &amp; Rute (<?= count($territories) ?>)</span>
        </button>
        <?php endif; ?>
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
                    <input type="text" x-model="searchQuery"
                           @keydown.enter.prevent="window.location.href = '<?= Router::url('/customers') ?>?q=' + encodeURIComponent(searchQuery)"
                           placeholder="Cari toko/kode... (Tekan Enter)"
                           class="form-input" style="height:38px;font-size:13px;">
                </div>

                <?php if (!empty($pagination['q'])): ?>
                <a href="<?= Router::url('/customers') ?>" class="btn btn-secondary btn-sm" style="height:38px;padding:0 10px;display:flex;align-items:center;gap:4px;" title="Reset Pencarian">
                    <i data-lucide="x" style="width:14px;height:14px;"></i>
                    <span style="font-size:12px;">Reset</span>
                </a>
                <?php endif; ?>

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

            <?php if (\App\Core\Auth::can('master.customers_manage')): ?>
            <button @click="openAddModal()" class="btn btn-primary" style="height:38px;white-space:nowrap;">
                <i data-lucide="plus"></i>
                <span>Tambah Toko Baru</span>
            </button>
            <?php endif; ?>
        </div>

        <!-- TABLE LIST -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 920px;">
                <thead>
                    <tr>
                        <th style="width:105px; min-width:90px;" class="cell-nowrap">Kode</th>
                        <th style="min-width:200px;">Nama Toko &amp; Pemilik</th>
                        <th style="min-width:150px;" class="cell-nowrap">Grup Pelanggan</th>
                        <th style="min-width:140px;">Wilayah / Rute</th>
                        <th class="cell-center cell-nowrap" style="width:130px; min-width:120px;">Tipe Bayar</th>
                        <th class="cell-center cell-nowrap" style="width:160px; min-width:140px;">Item Khusus Toko</th>
                        <th class="cell-center cell-nowrap" style="width:110px; min-width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="c in filteredCustomers" :key="c.id">
                        <tr :style="!c.status_aktif ? 'opacity:0.6;' : ''">
                            <td class="cell-nowrap">
                                <span class="badge badge-mono" x-text="c.kode_pelanggan"></span>
                                <template x-if="!c.status_aktif">
                                    <span class="badge badge-danger" style="font-size:9.5px;padding:1px 5px;margin-top:3px;display:block;">Nonaktif</span>
                                </template>
                            </td>
                            <td>
                                <div style="font-weight:700;color:var(--color-ink);" x-text="c.nama_toko"></div>
                                <div style="font-size:11px;color:var(--color-ink-mute);display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:2px;">
                                    <span x-text="c.nama_pemilik ? ('Pemilik: ' + c.nama_pemilik) : c.alamat_lengkap"></span>
                                    <template x-if="c.nomor_whatsapp">
                                        <a :href="'https://wa.me/' + cleanWa(c.nomor_whatsapp)" target="_blank" rel="noopener noreferrer" class="badge" style="background:rgba(16,185,129,0.1);color:#059669;border:1px solid rgba(16,185,129,0.25);padding:1px 6px;font-size:10px;font-weight:700;display:inline-flex;align-items:center;gap:3px;text-decoration:none;" title="Hubungi Toko via WhatsApp">
                                            <i data-lucide="phone" style="width:10px;height:10px;"></i>
                                            <span x-text="c.nomor_whatsapp"></span>
                                        </a>
                                    </template>
                                    <template x-if="c.link_google_maps">
                                        <a :href="c.link_google_maps" target="_blank" rel="noopener noreferrer" class="badge" style="background:rgba(37,99,235,0.08);color:#2563eb;border:1px solid rgba(37,99,235,0.2);padding:1px 6px;font-size:10px;font-weight:700;display:inline-flex;align-items:center;gap:3px;text-decoration:none;" title="Buka Titik Presisi Google Maps">
                                            <i data-lucide="map-pin" style="width:10px;height:10px;color:#ef4444;"></i>
                                            <span>Maps</span>
                                        </a>
                                    </template>
                                    <template x-if="c.nama_sales">
                                        <span class="badge badge-mono" style="font-size:10px;background:rgba(16,185,129,0.1);color:#059669;border:1px solid rgba(16,185,129,0.25);" x-text="'Sales: ' + c.nama_sales"></span>
                                    </template>
                                </div>
                                <template x-if="c.nomor_rekening">
                                    <div style="font-size:10.5px;color:var(--color-ink-mute);font-family:var(--font-mono);margin-top:2px;">
                                        <i data-lucide="building" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-right:2px;margin-top:-2px;"></i>
                                        <span x-text="(c.nama_bank || 'Bank') + ' &bull; ' + c.nomor_rekening + ' a.n. ' + (c.atas_nama_rekening || '-')"></span>
                                    </div>
                                </template>
                            </td>
                            <td class="cell-nowrap">
                                <div style="font-weight:600;color:var(--color-ink);" x-text="c.nama_grup || '-'"></div>
                                <template x-if="c.grup_status_aktif === false">
                                    <span class="badge badge-warning" style="font-size:9.5px;padding:0.5px 5px;margin-top:2px;display:inline-block;" title="Grup pelanggan ini telah dinonaktifkan di master grup">Grup Nonaktif</span>
                                </template>
                            </td>
                            <td>
                                <div style="font-weight:600;" x-text="c.nama_wilayah || '-'"></div>
                                <div style="font-size:10.5px;color:var(--color-ink-mute);display:flex;align-items:center;gap:4px;flex-wrap:wrap;margin-top:1px;">
                                    <span x-text="c.kode_rute || ''"></span>
                                    <template x-if="c.wilayah_status_aktif === false">
                                        <span class="badge badge-warning" style="font-size:9px;padding:0px 4px;" title="Rute wilayah ini telah dinonaktifkan di master rute">Rute Nonaktif</span>
                                    </template>
                                </div>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <template x-if="c.is_konsinyasi">
                                    <div style="display:inline-flex;flex-direction:column;align-items:center;gap:3px;">
                                        <span class="badge badge-info">Konsinyasi Rak</span>
                                        <template x-if="Number(c.stok_titip_aktif || 0) > 0">
                                            <span class="badge badge-amber" style="font-size:10px;padding:1px 6px;font-weight:700;" :title="'Ada ' + Number(c.stok_titip_aktif).toLocaleString('id-ID') + ' pcs snack titip di rak toko'" x-text="Number(c.stok_titip_aktif).toLocaleString('id-ID') + ' pcs di rak'"></span>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!c.is_konsinyasi">
                                    <span class="badge badge-secondary" style="text-transform:capitalize;" x-text="c.tipe_pembayaran_default ? c.tipe_pembayaran_default.replace(/_/g, ' ') : 'Cash'"></span>
                                </template>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <?php if (\App\Core\Auth::can('master.customers_manage')): ?>
                                <template x-if="c.total_item_khusus > 0">
                                    <button type="button" @click="openCustomerItemsModal(c)" class="badge badge-success" title="Klik untuk atur daftar item khusus toko ini">
                                        <span style="width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block;opacity:0.85;"></span>
                                        <span x-text="c.total_item_khusus + ' Item Khusus'"></span>
                                    </button>
                                </template>
                                <template x-if="!c.total_item_khusus || c.total_item_khusus == 0">
                                    <button type="button" @click="openCustomerItemsModal(c)" class="badge badge-secondary" title="Klik untuk atur item khusus toko ini">
                                        <span style="width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block;opacity:0.5;"></span>
                                        <span>Semua Produk (Default)</span>
                                    </button>
                                </template>
                                <?php else: ?>
                                <template x-if="c.total_item_khusus > 0">
                                    <span class="badge badge-success">
                                        <span style="width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block;opacity:0.85;"></span>
                                        <span x-text="c.total_item_khusus + ' Item Khusus'"></span>
                                    </span>
                                </template>
                                <template x-if="!c.total_item_khusus || c.total_item_khusus == 0">
                                    <span class="badge badge-secondary">
                                        <span style="width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block;opacity:0.5;"></span>
                                        <span>Semua Produk (Default)</span>
                                    </span>
                                </template>
                                <?php endif; ?>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <div style="display:flex;align-items:center;justify-content:center;gap:4px;">
                                    <a :href="'/customer-orders?pelanggan_id=' + c.id" class="btn btn-ghost btn-sm" style="padding:6px;color:#2563eb;" title="Lihat Faktur & Riwayat Pesanan Toko">
                                        <i data-lucide="receipt" style="width:14px;height:14px;"></i>
                                    </a>
                                    <?php if (\App\Core\Auth::can('master.customers_manage')): ?>
                                    <button @click="openCustomerItemsModal(c)" class="btn btn-ghost btn-sm" style="padding:6px;" title="Atur Item Khusus Toko">
                                        <i data-lucide="list-checks" style="width:15px;height:15px;color:var(--color-ink-secondary);"></i>
                                    </button>
                                    <button @click="openEditModal(c)" class="btn btn-ghost btn-sm" style="padding:6px;" title="Edit Data Toko">
                                        <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                    </button>
                                    <template x-if="c.kode_pelanggan === 'CUST-001' || (c.nama_toko && c.nama_toko.toLowerCase().includes('walk-in'))">
                                        <button type="button" class="btn btn-ghost btn-sm" style="padding:6px;color:var(--color-ink-mute);cursor:not-allowed;opacity:0.5;" title="Pelanggan Default POS (CUST-001 / Walk-in Cash) Terkunci & Tidak Dapat Dihapus" disabled>
                                            <i data-lucide="lock" style="width:14px;height:14px;"></i>
                                        </button>
                                    </template>
                                    <template x-if="!(c.kode_pelanggan === 'CUST-001' || (c.nama_toko && c.nama_toko.toLowerCase().includes('walk-in')))">
                                        <button @click="deleteCustomer(c.id, c.nama_toko, c.kode_pelanggan)" class="btn btn-ghost btn-sm" style="padding:6px;color:#ef4444;" title="Hapus Toko">
                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                        </button>
                                    </template>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="filteredCustomers.length === 0">
                        <tr>
                            <td colspan="7" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="search-x" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Tidak ada data toko pelanggan yang sesuai filter</div>
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
                Menampilkan Halaman <strong><?= $pagination['page'] ?></strong> dari <strong><?= $pagination['totalPages'] ?></strong> (Total <?= number_format($pagination['total'], 0, ',', '.') ?> toko)
            </div>
            <div style="display:flex;gap:6px;">
                <?php if ($pagination['page'] > 1): ?>
                <a href="<?= Router::url('/customers?' . http_build_query(array_merge($_GET, ['page' => $pagination['page'] - 1]))) ?>" class="btn btn-secondary btn-sm" style="font-size:12px;">
                    &laquo; Sebelumnya
                </a>
                <?php endif; ?>
                <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                <a href="<?= Router::url('/customers?' . http_build_query(array_merge($_GET, ['page' => $pagination['page'] + 1]))) ?>" class="btn btn-secondary btn-sm" style="font-size:12px;">
                    Selanjutnya &raquo;
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: MASTER GRUP PELANGGAN                                              -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'customer_groups'" class="card" style="padding:0;overflow:hidden;">
        <!-- ACTION & FILTER BAR -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="form-input-icon flex-1 sm:max-w-xs">
                <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                <input type="text" x-model="searchCustomerGroup" placeholder="Cari nama grup / kode..." class="form-input" style="height:38px;font-size:13px;">
            </div>

            <?php if (\App\Core\Auth::can('master.customers_manage')): ?>
            <button @click="openAddCustomerGroupModal()" class="btn btn-primary" style="height:38px;white-space:nowrap;">
                <i data-lucide="plus"></i>
                <span>Tambah Grup Pelanggan</span>
            </button>
            <?php endif; ?>
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
                        <?php if (\App\Core\Auth::can('master.customers_manage')): ?>
                        <th class="cell-center cell-nowrap" style="width:90px;">Aksi</th>
                        <?php endif; ?>
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
                            <?php if (\App\Core\Auth::can('master.customers_manage')): ?>
                            <td class="cell-center cell-nowrap">
                                <div style="display:flex;align-items:center;justify-content:center;gap:4px;">
                                    <button @click="openEditCustomerGroupModal(cg)" class="btn btn-ghost btn-sm" style="padding:6px;" title="Edit Grup">
                                        <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                    </button>
                                    <template x-if="(Number(cg.total_pelanggan) || 0) > 0 || customerGroups.length <= 1">
                                        <button type="button" disabled class="btn btn-ghost btn-sm" style="padding:6px;opacity:0.35;cursor:not-allowed;color:var(--color-ink-mute);" :title="Number(cg.total_pelanggan) > 0 ? ('Grup terhubung dengan ' + cg.total_pelanggan + ' toko pelanggan (tidak dapat dihapus)') : 'Sistem wajib memiliki minimal 1 grup pelanggan'">
                                            <i data-lucide="lock" style="width:14px;height:14px;"></i>
                                        </button>
                                    </template>
                                    <template x-if="(!cg.total_pelanggan || Number(cg.total_pelanggan) === 0) && customerGroups.length > 1">
                                        <button @click="deleteCustomerGroup(cg.id, cg.nama_grup)" class="btn btn-ghost btn-sm" style="padding:6px;color:#ef4444;" title="Hapus Grup">
                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                        </button>
                                    </template>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    </template>

                    <template x-if="filteredCustomerGroups.length === 0">
                        <tr>
                            <td colspan="<?= \App\Core\Auth::can('master.customers_manage') ? '8' : '7' ?>" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
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
    <?php if (\App\Core\Auth::can('master.territories_manage')): ?>
    <div x-show="activeTab === 'territories'" class="card" style="padding:0;overflow:hidden;">
        <!-- ACTION & FILTER BAR -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="form-input-icon flex-1 sm:max-w-xs">
                <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                <input type="text" x-model="searchTerritory" placeholder="Cari nama wilayah / kota / rute..." class="form-input" style="height:38px;font-size:13px;">
            </div>

            <?php if (\App\Core\Auth::can('master.territories_manage')): ?>
            <button @click="openAddTerritoryModal()" class="btn btn-primary" style="height:38px;white-space:nowrap;">
                <i data-lucide="plus"></i>
                <span>Tambah Wilayah / Rute Baru</span>
            </button>
            <?php endif; ?>
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
                        <?php if (\App\Core\Auth::can('master.territories_manage')): ?>
                        <th class="cell-center cell-nowrap" style="width:90px;">Aksi</th>
                        <?php endif; ?>
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
                            <?php if (\App\Core\Auth::can('master.territories_manage')): ?>
                            <td class="cell-center cell-nowrap">
                                <div style="display:flex;align-items:center;justify-content:center;gap:4px;">
                                    <button @click="openEditTerritoryModal(t)" class="btn btn-ghost btn-sm" style="padding:6px;" title="Edit Wilayah">
                                        <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                    </button>
                                    <template x-if="(Number(t.total_pelanggan) || 0) > 0 || (Number(t.total_pemasok) || 0) > 0">
                                        <button type="button" disabled class="btn btn-ghost btn-sm" style="padding:6px;opacity:0.35;cursor:not-allowed;color:var(--color-ink-mute);" :title="'Wilayah digunakan oleh ' + (Number(t.total_pelanggan) > 0 ? (t.total_pelanggan + ' toko') : '') + (Number(t.total_pelanggan) > 0 && Number(t.total_pemasok) > 0 ? ' & ' : '') + (Number(t.total_pemasok) > 0 ? (t.total_pemasok + ' vendor') : '') + ' (tidak dapat dihapus)'">
                                            <i data-lucide="lock" style="width:14px;height:14px;"></i>
                                        </button>
                                    </template>
                                    <template x-if="(!t.total_pelanggan || Number(t.total_pelanggan) === 0) && (!t.total_pemasok || Number(t.total_pemasok) === 0)">
                                        <button @click="deleteTerritory(t.id, t.nama_wilayah)" class="btn btn-ghost btn-sm" style="padding:6px;color:#ef4444;" title="Hapus Wilayah">
                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                        </button>
                                    </template>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    </template>

                    <template x-if="filteredTerritories.length === 0">
                        <tr>
                            <td colspan="<?= \App\Core\Auth::can('master.territories_manage') ? '9' : '8' ?>" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="map-pin-off" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Tidak ada data wilayah / rute logistik</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- MODALS SECTION                                                            -->
    <!-- ========================================================================= -->

    <!-- MODAL 1: TAMBAH / EDIT TOKO PELANGGAN -->
    <template x-teleport="body">
    <div x-show="showModal" x-cloak class="modal-backdrop">
        <div class="modal-box" :style="isChangingFromConsignment ? 'max-width:720px;padding:24px;' : 'max-width:560px;padding:24px;'" style="transition:max-width 0.2s ease;">
            <div class="modal-header">
                <div class="modal-title" x-text="isEdit ? 'Edit Data Toko Pelanggan' : 'Tambah Toko Pelanggan Baru'"></div>
            </div>

            <form id="customer-modal-form"
                  action="<?= Router::url('/customers/update') ?>"
                  :action="isEdit ? '<?= Router::url('/customers/update') ?>' : '<?= Router::url('/customers/store') ?>'"
                  method="POST"
                  @submit="submitCustomerForm($event)"
                  style="display:flex;flex-direction:column;gap:14px;">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="id" :value="form.id">
                <input type="hidden" name="is_konsinyasi" :value="form.tipe_pembayaran_default === 'konsinyasi' ? '1' : '0'">

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
                        <label class="form-label">Grup Pelanggan *</label>
                        <select name="grup_pelanggan_id" x-model="form.grup_pelanggan_id" required class="form-input">
                            <option value="" disabled>-- Pilih Grup Pelanggan --</option>
                            <?php foreach ($groups as $g): ?>
                            <option value="<?= $g['id'] ?>"
                                    <?= !$g['status_aktif'] ? 'style="color:#94a3b8;"' : '' ?>
                                    :disabled="!<?= $g['status_aktif'] ? 'true' : 'false' ?> && form.grup_pelanggan_id !== '<?= $g['id'] ?>'">
                                <?= htmlspecialchars($g['nama_grup']) ?><?= !$g['status_aktif'] ? ' (Nonaktif)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Wilayah / Rute Logistik</label>
                        <select name="wilayah_id" x-model="form.wilayah_id" class="form-input">
                            <option value="">-- Tanpa Rute Tertentu --</option>
                            <?php foreach ($territories as $t): ?>
                            <option value="<?= $t['id'] ?>"
                                    <?= !$t['status_aktif'] ? 'style="color:#94a3b8;"' : '' ?>
                                    :disabled="!<?= $t['status_aktif'] ? 'true' : 'false' ?> && form.wilayah_id !== '<?= $t['id'] ?>'">
                                <?= htmlspecialchars($t['nama_wilayah']) ?> — <?= $t['kode_rute'] ?><?= !$t['status_aktif'] ? ' (Nonaktif)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Sales Pembina / Penanggung Jawab Toko</label>
                        <select name="sales_driver_id" x-model="form.sales_driver_id" class="form-input">
                            <option value="">-- Tanpa Sales Pembina (Langsung Toko) --</option>
                            <?php foreach ($salesEmployees as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_karyawan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Nomor WhatsApp / HP</label>
                        <input type="text" name="nomor_whatsapp" x-model="form.nomor_whatsapp" class="form-input font-mono" placeholder="081234567890">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                    <div>
                        <label class="form-label">Plafon Maksimal Piutang (Rp)</label>
                        <input type="text" name="plafon_piutang" x-model="form.plafon_piutang" class="form-input font-mono input-rupiah" placeholder="5.000.000">
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Batas kredit maksimal (berlaku untuk transaksi Tempo)</div>
                    </div>
                </div>

                <!-- KOTAK RESOLUSI PERPINDAHAN TIPE KONSINYASI (RADIO BUTTON) -->
                <template x-if="isChangingFromConsignment">
                    <div style="background: linear-gradient(180deg, #fffdf7 0%, #fffbe8 100%); border: 1.5px solid #fcd34d; border-radius: 12px; padding: 18px; display: flex; flex-direction: column; gap: 16px; box-shadow: 0 4px 16px rgba(245, 158, 11, 0.07);">
                        
                        <!-- BANNER INFO: STOK KONSINYASI AKTIF -->
                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                            <div style="width: 38px; height: 38px; border-radius: 10px; background: #fef3c7; border: 1.5px solid #fde68a; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 1px 3px rgba(245, 158, 11, 0.1);">
                                <svg style="width: 20px; height: 20px; color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                            </div>
                            <div style="flex: 1; line-height: 1.5;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span style="font-weight: 800; font-size: 14px; color: #92400e;">
                                        Toko Masih Memiliki
                                    </span>
                                    <span style="background: #fef08a; color: #854d0e; font-family: var(--font-mono); font-size: 13px; font-weight: 800; padding: 2px 8px; border-radius: 6px; border: 1px solid #fde047;" x-text="totalShelfQty.toLocaleString('id-ID') + ' Pcs'"></span>
                                    <span style="font-weight: 800; font-size: 14px; color: #92400e;">
                                        Stok Konsinyasi Aktif di Rak
                                    </span>
                                </div>
                                <div style="font-size: 12px; color: #78350f; margin-top: 4px;">
                                    Perpindahan ke tipe toko non-konsinyasi mengharuskan saldo rak toko dinolkan. Silakan tentukan salah satu mekanisme penyelesaian di bawah ini:
                                </div>
                            </div>
                        </div>

                        <!-- OPSI RESOLUSI RADIO BUTTONS -->
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            
                            <!-- CARD 1: RETUR FISIK KE GUDANG -->
                            <div :style="form.konversi_konsinyasi_opsi === 'retur' ? 'border-color: var(--color-primary); background: #ffffff; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.08);' : 'border-color: #e2e8f0; background: #ffffff;'"
                                 style="border: 1.5px solid; border-radius: 10px; transition: all 0.2s ease; overflow: hidden;">
                                
                                <!-- Card Header (Clickable Radio Label) -->
                                <label style="display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px; cursor: pointer; user-select: none;">
                                    <input type="radio" name="konversi_konsinyasi_opsi" value="retur" x-model="form.konversi_konsinyasi_opsi" style="width: 18px; height: 18px; accent-color: var(--color-primary); margin-top: 2px; cursor: pointer;">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                                            <span style="font-size: 13.5px; font-weight: 800; color: var(--color-ink);">Opsi 1 — Tarik / Retur Fisik Seluruh Stok ke Gudang Pusat</span>
                                            <span class="badge badge-success" style="font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 9999px;">Rekomendasi</span>
                                        </div>
                                        <div style="font-size: 11.5px; color: var(--color-ink-mute); margin-top: 4px; line-height: 1.45;">
                                            Sisa barang di rak ditarik kembali oleh armada ke gudang pusat. Saldo rak toko dinolkan (<strong style="color: #059669;">0 pcs</strong>) dan stok fisik gudang bertambah otomatis (<span style="font-weight: 700; color: #059669;" x-text="'+' + totalShelfQty + ' pcs'"></span>).
                                        </div>
                                    </div>
                                </label>

                                <!-- Sub-Panel: Rincian Retur Barang -->
                                <template x-if="form.konversi_konsinyasi_opsi === 'retur'">
                                    <div style="padding: 14px 16px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 12px;">
                                        <div>
                                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                                <div style="font-size: 11.5px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 6px;">
                                                    <svg style="width: 14px; height: 14px; color: #0284c7; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/>
                                                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                                                    </svg>
                                                    <span>Daftar Barang yang Ditarik ke Gudang:</span>
                                                </div>
                                                <span style="font-size: 11px; color: #64748b; font-weight: 600;" x-text="currentShelfItems.length + ' Item Produk'"></span>
                                            </div>

                                            <div style="max-height: 170px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; background: #ffffff;" class="custom-scrollbar">
                                                <table style="width: 100%; font-size: 11.5px; border-collapse: collapse;">
                                                    <thead style="background: #f1f5f9; position: sticky; top: 0; border-bottom: 1px solid #e2e8f0; z-index: 1;">
                                                        <tr>
                                                            <th style="text-align: left; padding: 8px 12px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #475569;">Produk</th>
                                                            <th style="text-align: left; padding: 8px 12px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #475569; width: 110px;">SKU</th>
                                                            <th style="text-align: right; padding: 8px 12px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #475569; width: 95px;">Qty Rak</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="item in currentShelfItems" :key="item.item_id">
                                                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                                                <td style="padding: 7px 12px; color: var(--color-ink); font-weight: 600;" x-text="item.nama_item"></td>
                                                                <td style="padding: 7px 12px;">
                                                                    <span style="font-family: var(--font-mono); font-size: 10.5px; color: #64748b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;" x-text="item.kode_sku"></span>
                                                                </td>
                                                                <td style="text-align: right; padding: 7px 12px;">
                                                                    <span style="font-family: var(--font-mono); font-weight: 700; color: #059669; background: #ecfdf5; padding: 2px 8px; border-radius: 6px; font-size: 11.5px;" x-text="item.qty + ' pcs'"></span>
                                                                </td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                    <tfoot style="background: #f8fafc; font-weight: 800; border-top: 1.5px solid #cbd5e1;">
                                                        <tr>
                                                            <td colspan="2" style="padding: 8px 12px; text-align: right; color: #334155; font-size: 11.5px;">Total Penarikan Fisik:</td>
                                                            <td style="padding: 8px 12px; text-align: right; font-family: var(--font-mono); color: #059669; font-size: 12px;" x-text="totalShelfQty.toLocaleString('id-ID') + ' pcs'"></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Input BAST / Catatan -->
                                        <div>
                                            <label class="form-label" style="font-size: 11.5px; font-weight: 600; color: #334155; margin-bottom: 4px; display: flex; align-items: center; gap: 5px;">
                                                <svg style="width: 13px; height: 13px; color: #64748b; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <polyline points="14 2 14 8 20 8"/>
                                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                                    <polyline points="10 9 9 9 8 9"/>
                                                </svg>
                                                <span>Nomor Berita Acara / Catatan Serah Terima (Opsional)</span>
                                            </label>
                                            <input type="text" name="catatan_konversi" x-model="form.catatan_konversi" class="form-input" style="height: 36px; font-size: 12px; background: #ffffff; border-radius: 6px;" placeholder="Contoh: BAST-RTN-202609-01 / Diterima utuh oleh driver armada">
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- CARD 2: BELI PUTUS SISA BARANG -->
                            <div :style="form.konversi_konsinyasi_opsi === 'beli_putus' ? 'border-color: #2563eb; background: #ffffff; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.08);' : 'border-color: #e2e8f0; background: #ffffff;'"
                                 style="border: 1.5px solid; border-radius: 10px; transition: all 0.2s ease; overflow: hidden;">
                                
                                <!-- Card Header (Clickable Radio Label) -->
                                <label style="display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px; cursor: pointer; user-select: none;">
                                    <input type="radio" name="konversi_konsinyasi_opsi" value="beli_putus" x-model="form.konversi_konsinyasi_opsi" style="width: 18px; height: 18px; accent-color: #2563eb; margin-top: 2px; cursor: pointer;">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                                            <span style="font-size: 13.5px; font-weight: 800; color: var(--color-ink);">Opsi 2 — Beli Putus Sisa Barang di Rak (Terbitkan Faktur Penjualan)</span>
                                            <span class="badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px;">Faktur Resmi</span>
                                        </div>
                                        <div style="font-size: 11.5px; color: var(--color-ink-mute); margin-top: 4px; line-height: 1.45;">
                                            Pemilik toko sepakat membeli sisa barang titipan. Sistem otomatis menerbitkan Faktur Penjualan resmi, saldo rak dinolkan (<strong style="color: #2563eb;">0 pcs</strong>), dan memproses pembayarannya (Lunas Kasir/Bank atau Masuk Piutang Dagang).
                                        </div>
                                        <div style="margin-top: 8px; padding: 8px 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; font-size: 11.5px; color: #1e40af; display: flex; align-items: flex-start; gap: 8px;">
                                            <svg style="width: 15px; height: 15px; color: #2563eb; flex-shrink: 0; margin-top: 1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
                                            </svg>
                                            <div style="line-height: 1.4;">
                                                <strong>Lokasi Faktur:</strong> Faktur penjualan resmi akan otomatis masuk ke menu <strong>Pesanan Pelanggan (/customer-orders)</strong> dengan status <em>Selesai</em>. Anda dapat melihat, memfilter tipe <em>Beli Putus Rak</em>, mencetak, atau mengunduh invoice di menu tersebut kapan saja.
                                            </div>
                                        </div>
                                    </div>
                                </label>

                                <!-- Sub-Panel: Rincian Beli Putus & Pembayaran -->
                                <template x-if="form.konversi_konsinyasi_opsi === 'beli_putus'">
                                    <div style="padding: 14px 16px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 14px;">
                                        
                                        <!-- Tabel Kalkulasi Item Beli Putus -->
                                        <div>
                                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                                <div style="font-size: 11.5px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 6px;">
                                                    <svg style="width: 14px; height: 14px; color: #2563eb; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1z"/>
                                                        <line x1="8" y1="8" x2="16" y2="8"/>
                                                        <line x1="8" y1="12" x2="16" y2="12"/>
                                                        <line x1="8" y1="16" x2="12" y2="16"/>
                                                    </svg>
                                                    <span>Kalkulasi Item Faktur Beli Putus (Harga Netto Toko)</span>
                                                </div>
                                                <span style="font-size: 11px; color: #64748b; font-weight: 600;" x-text="currentShelfItems.length + ' Item Produk'"></span>
                                            </div>

                                            <div style="max-height: 170px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; background: #ffffff;" class="custom-scrollbar">
                                                <table style="width: 100%; font-size: 11.5px; border-collapse: collapse;">
                                                    <thead style="background: #f1f5f9; position: sticky; top: 0; border-bottom: 1px solid #e2e8f0; z-index: 1;">
                                                        <tr>
                                                            <th style="text-align: left; padding: 8px 12px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #475569;">Produk</th>
                                                            <th style="text-align: right; padding: 8px 12px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #475569; width: 75px;">Qty</th>
                                                            <th style="text-align: right; padding: 8px 12px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #475569; width: 105px;">Harga Netto</th>
                                                            <th style="text-align: right; padding: 8px 12px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #475569; width: 120px;">Subtotal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="item in currentShelfItems" :key="item.item_id">
                                                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                                                <td style="padding: 7px 12px; color: var(--color-ink); font-weight: 600;" x-text="item.nama_item"></td>
                                                                <td style="text-align: right; padding: 7px 12px; font-family: var(--font-mono); color: #334155;" x-text="item.qty + ' pcs'"></td>
                                                                <td style="text-align: right; padding: 7px 12px; font-family: var(--font-mono); color: #64748b;" x-text="formatRupiah(item.harga_pcs)"></td>
                                                                <td style="text-align: right; padding: 7px 12px; font-family: var(--font-mono); font-weight: 700; color: #0f172a;" x-text="formatRupiah(item.subtotal)"></td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                    <tfoot style="background: #eff6ff; font-weight: 800; border-top: 1.5px solid #bfdbfe;">
                                                        <tr>
                                                            <td style="padding: 8px 12px; color: #1e40af; font-size: 11.5px;">Total Nilai Faktur Beli Putus:</td>
                                                            <td style="padding: 8px 12px; text-align: right; font-family: var(--font-mono); color: #1e40af; font-size: 11.5px;" x-text="totalShelfQty.toLocaleString('id-ID') + ' pcs'"></td>
                                                            <td></td>
                                                            <td style="padding: 8px 12px; text-align: right; font-family: var(--font-mono); color: #1d4ed8; font-size: 13px; font-weight: 800;" x-text="formatRupiah(totalShelfNominal)"></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Sub-Pilihan Metode Pembayaran (Modern Interactive Choice Cards) -->
                                        <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; display: flex; flex-direction: column; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                                <div style="font-size: 12px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 7px;">
                                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 6px; background: #e0e7ff; color: #4338ca;">
                                                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <rect width="20" height="14" x="2" y="5" rx="2"/>
                                                            <line x1="2" x2="22" y1="10" y2="10"/>
                                                        </svg>
                                                    </span>
                                                    <span>Pilih Metode Pembayaran Faktur:</span>
                                                </div>
                                                <span style="font-size: 10.5px; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 9999px; border: 1px solid #e2e8f0;">Wajib Dipilih</span>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                                <!-- Option A: Lunas Langsung -->
                                                <label class="payment-choice-card"
                                                       :class="form.metode_beli_putus === 'lunas' ? 'is-selected-lunas' : ''">
                                                    <input type="radio" name="metode_beli_putus" value="lunas" x-model="form.metode_beli_putus" style="position: absolute; opacity: 0; width: 0; height: 0;">
                                                    
                                                    <!-- Minimal Icon Wrap -->
                                                    <div class="choice-icon-box" :style="form.metode_beli_putus === 'lunas' ? 'background: #dcfce7; color: #059669;' : 'background: #f1f5f9; color: #64748b;'">
                                                        <svg style="width: 17px; height: 17px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <rect width="20" height="12" x="2" y="6" rx="2"/>
                                                            <circle cx="12" cy="12" r="2"/>
                                                            <path d="M6 12h.01M18 12h.01"/>
                                                        </svg>
                                                    </div>

                                                    <!-- Content Info Minimalis -->
                                                    <div style="flex: 1; min-width: 0;">
                                                        <div style="font-size: 12.5px; font-weight: 700; line-height: 1.25;" :style="form.metode_beli_putus === 'lunas' ? 'color: #065f46;' : 'color: #1e293b;'">
                                                            Lunas Langsung
                                                        </div>
                                                        <div style="font-size: 11px; margin-top: 2px; line-height: 1.3;" :style="form.metode_beli_putus === 'lunas' ? 'color: #047857;' : 'color: #64748b;'">
                                                            Kasir / Rekening Bank (Tercatat Lunas)
                                                        </div>
                                                    </div>
                                                </label>

                                                <!-- Option B: Masuk Piutang Dagang -->
                                                <label class="payment-choice-card"
                                                       :class="form.metode_beli_putus === 'tempo' ? 'is-selected-tempo' : ''">
                                                    <input type="radio" name="metode_beli_putus" value="tempo" x-model="form.metode_beli_putus" style="position: absolute; opacity: 0; width: 0; height: 0;">
                                                    
                                                    <!-- Minimal Icon Wrap -->
                                                    <div class="choice-icon-box" :style="form.metode_beli_putus === 'tempo' ? 'background: #fef3c7; color: #d97706;' : 'background: #f1f5f9; color: #64748b;'">
                                                        <svg style="width: 17px; height: 17px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <circle cx="12" cy="12" r="10"/>
                                                            <polyline points="12 6 12 12 16 14"/>
                                                        </svg>
                                                    </div>

                                                    <!-- Content Info Minimalis -->
                                                    <div style="flex: 1; min-width: 0;">
                                                        <div style="font-size: 12.5px; font-weight: 700; line-height: 1.25;" :style="form.metode_beli_putus === 'tempo' ? 'color: #92400e;' : 'color: #1e293b;'">
                                                            Masuk Piutang Dagang
                                                        </div>
                                                        <div style="font-size: 11px; margin-top: 2px; line-height: 1.3;" :style="form.metode_beli_putus === 'tempo' ? 'color: #b45309;' : 'color: #64748b;'">
                                                            Tagihan Tempo (Menambah Saldo Piutang)
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>

                                            <!-- Detail Lunas: Akun Kas Dropdown -->
                                            <template x-if="form.metode_beli_putus === 'lunas'">
                                                <div style="margin-top: 2px; padding: 12px 14px; background: #f0fdf4; border: 1.5px solid #a7f3d0; border-radius: 8px; display: flex; flex-direction: column; gap: 8px;">
                                                    <label class="form-label" style="font-size: 11.5px; font-weight: 800; color: #166534; margin-bottom: 0; display: flex; align-items: center; gap: 6px;">
                                                        <svg style="width: 14px; height: 14px; color: #15803d;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                                        </svg>
                                                        <span>Akun Kas / Bank Penerima Pembayaran *</span>
                                                    </label>
                                                    <select name="akun_kas_id" x-model="form.akun_kas_id" class="form-input" style="height: 38px; font-size: 12.5px; background: #ffffff; border-color: #86efac; border-radius: 6px;">
                                                        <option value="" disabled>-- Pilih Akun Kas / Rekening Penerima --</option>
                                                        <?php foreach ($cashAccounts as $acc): ?>
                                                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['nama_akun']) ?> (Saldo: <?= \App\Helpers\Format::rupiah($acc['saldo_saat_ini']) ?>)</option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div style="font-size: 11px; color: #15803d; display: flex; align-items: center; gap: 6px;">
                                                        <svg style="width: 13px; height: 13px; flex-shrink: 0; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span>Saldo akun terpilih otomatis bertambah <strong x-text="formatRupiah(totalShelfNominal)"></strong> dan dicatat di buku kas.</span>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- Detail Tempo: Breakdown Piutang -->
                                            <template x-if="form.metode_beli_putus === 'tempo'">
                                                <div style="margin-top: 2px; padding: 12px 14px; background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 8px; font-size: 11.5px; color: #92400e; display: flex; flex-direction: column; gap: 8px;">
                                                    <div style="font-weight: 800; color: #b45309; display: flex; align-items: center; gap: 6px;">
                                                        <svg style="width: 14px; height: 14px; color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10"/>
                                                            <polyline points="12 6 12 12 16 14"/>
                                                        </svg>
                                                        <span>Kalkulasi Akumulasi Piutang Dagang Toko</span>
                                                    </div>
                                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; background: #fef9c3; padding: 8px 10px; border-radius: 6px; border: 1px solid #fef08a;">
                                                        <div>Piutang Berjalan Saat Ini: <strong style="display: block; font-size: 12px; color: #1e293b;" x-text="formatRupiah(selectedCustomer?.total_piutang_berjalan || 0)"></strong></div>
                                                        <div>Tagihan Beli Putus Baru: <strong style="display: block; font-size: 12px; color: #b45309;" x-text="'+ ' + formatRupiah(totalShelfNominal)"></strong></div>
                                                    </div>
                                                    <div style="padding-top: 6px; border-top: 1px dashed #fcd34d; display: flex; justify-content: space-between; align-items: center;">
                                                        <span style="font-weight: 700;">Total Piutang Toko Menjadi:</span>
                                                        <strong style="color: #b45309; font-size: 13.5px; font-family: var(--font-mono);" x-text="formatRupiah(Number(selectedCustomer?.total_piutang_berjalan || 0) + totalShelfNominal)"></strong>
                                                    </div>
                                                    <div style="font-size: 10.5px; color: #a16207; display: flex; align-items: center; gap: 4px;">
                                                        <span>Jatuh tempo faktur otomatis diset mengikuti tipe bayar:</span>
                                                        <span style="font-weight: 800; text-transform: uppercase; background: #fef08a; padding: 1px 5px; border-radius: 4px;" x-text="form.tipe_pembayaran_default.replace(/_/g, ' ')"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Catatan Faktur -->
                                        <div>
                                            <label class="form-label" style="font-size: 11.5px; font-weight: 600; color: #334155; margin-bottom: 4px; display: flex; align-items: center; gap: 5px;">
                                                <svg style="width: 13px; height: 13px; color: #64748b; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                <span>Catatan Faktur Beli Putus (Opsional)</span>
                                            </label>
                                            <input type="text" name="catatan_konversi" x-model="form.catatan_konversi" class="form-input" style="height: 36px; font-size: 12px; background: #ffffff; border-radius: 6px;" placeholder="Contoh: Kesepakatan beli putus sisa barang rak saat peralihan tipe toko">
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- TOMBOL BATALKAN PERUBAHAN TIPE (RESET ACTION) -->
                            <div style="display: flex; justify-content: flex-end; align-items: center; padding-top: 2px;">
                                <button type="button" @click="cancelConsignmentChange()"
                                        class="btn btn-ghost btn-sm"
                                        style="font-size: 12px; font-weight: 600; color: #64748b; display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; border: 1px solid #cbd5e1; background: #ffffff; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                                    <svg style="width: 14px; height: 14px; color: #64748b; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="1 4 1 10 7 10"/>
                                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                                    </svg>
                                    <span>Batalkan Perubahan & Tetap Toko Konsinyasi</span>
                                </button>
                            </div>

                        </div>
                    </div>
                </template>

                <div>
                    <label class="form-label">Alamat Lengkap Toko</label>
                    <textarea name="alamat_lengkap" x-model="form.alamat_lengkap" class="form-input" rows="2" style="padding: 8px 12px; line-height: 1.5; min-height: 64px;" placeholder="Jl. Raya Pasar..."></textarea>
                </div>

                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                        <label class="form-label" style="margin-bottom:0;display:flex;align-items:center;gap:6px;">
                            <i data-lucide="map-pin" style="width:14px;height:14px;color:#ef4444;"></i>
                            <span>Link Google Maps / Titik Presisi (Opsional)</span>
                        </label>
                        <template x-if="form.link_google_maps">
                            <a :href="form.link_google_maps" target="_blank" rel="noopener noreferrer" style="font-size:11px;font-weight:700;color:#2563eb;display:inline-flex;align-items:center;gap:3px;text-decoration:none;">
                                <i data-lucide="external-link" style="width:11px;height:11px;"></i>
                                <span>Tes Link</span>
                            </a>
                        </template>
                    </div>
                    <input type="text" name="link_google_maps" x-model="form.link_google_maps" class="form-input" placeholder="https://maps.app.goo.gl/... atau https://maps.google.com/?q=-6.2,106.8">
                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;">
                        Salin link dari Google Maps agar armada driver dapat membuka rute navigasi toko secara presisi.
                    </div>
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
                        <template x-if="form.kode_pelanggan === 'CUST-001'">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input type="hidden" name="status_aktif" value="1">
                                <span class="badge badge-success" style="font-size:11.5px;padding:4px 10px;display:inline-flex;align-items:center;gap:5px;">
                                    <i data-lucide="lock" style="width:13px;height:13px;"></i>
                                    Status Toko Aktif (Terkunci Otomatis - Pelanggan Default POS)
                                </span>
                            </div>
                        </template>
                        <template x-if="form.kode_pelanggan !== 'CUST-001'">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;">
                                <input type="hidden" name="status_aktif" value="0">
                                <input type="checkbox" name="status_aktif" value="1" x-model="form.status_aktif" style="width:16px;height:16px;accent-color:var(--color-primary);">
                                <span>Status Toko Aktif (Dapat Bertransaksi)</span>
                            </label>
                        </template>
                    </div>
                </template>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-customer">
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
            </div>

            <form action="<?= Router::url('/customers/save-items') ?>" method="POST" style="display:flex;flex-direction:column;gap:12px;">
                <?= \App\Helpers\CSRF::field() ?>
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
            </div>

            <form id="customer-group-modal-form"
                  action="<?= Router::url('/customers/store-group') ?>"
                  :action="isEditCustomerGroup ? '<?= Router::url('/customers/update-group') ?>' : '<?= Router::url('/customers/store-group') ?>'"
                  method="POST"
                  style="display:flex;flex-direction:column;gap:14px;">
                <?= \App\Helpers\CSRF::field() ?>
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
                        <label class="form-label">Default Level Harga (1–30) *</label>
                        <select name="default_level_harga" x-model.number="customerGroupForm.default_level_harga" class="form-input">
                            <?php if (!empty($masterLevels)): ?>
                                <?php foreach ($masterLevels as $ml): ?>
                                <option value="<?= $ml['level_nomor'] ?>"><?= htmlspecialchars($ml['nama_level']) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php for ($i = 1; $i <= 30; $i++): ?>
                                <option value="<?= $i ?>">Level <?= $i ?></option>
                                <?php endfor; ?>
                            <?php endif; ?>
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
    <?php if (\App\Core\Auth::can('master.territories_manage')): ?>
    <template x-teleport="body">
    <div x-show="showTerritoryModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:480px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title" x-text="isEditTerritory ? 'Edit Wilayah / Rute' : 'Tambah Wilayah / Rute Baru'"></div>
            </div>

            <form id="territory-modal-form"
                  action="<?= Router::url('/customers/store-territory') ?>"
                  :action="isEditTerritory ? '<?= Router::url('/customers/update-territory') ?>' : '<?= Router::url('/customers/store-territory') ?>'"
                  method="POST"
                  style="display:flex;flex-direction:column;gap:14px;">
                <?= \App\Helpers\CSRF::field() ?>
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
    <?php endif; ?>

    <!-- HIDDEN FORMS FOR DELETING -->
    <?php if (\App\Core\Auth::can('master.customers_manage')): ?>
    <form id="delete-customer-form" action="<?= Router::url('/customers/delete') ?>" method="POST" data-action-text="Menghapus toko pelanggan..." style="display:none;">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="id" id="delete-customer-id">
    </form>
    <form id="delete-group-form" action="<?= Router::url('/customers/delete-group') ?>" method="POST" data-action-text="Menghapus grup pelanggan..." style="display:none;">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="id" id="delete-group-id">
    </form>
    <?php endif; ?>
    <?php if (\App\Core\Auth::can('master.territories_manage')): ?>
    <form id="delete-territory-form" action="<?= Router::url('/customers/delete-territory') ?>" method="POST" data-action-text="Menghapus wilayah rute..." style="display:none;">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="id" id="delete-territory-id">
    </form>
    <?php endif; ?>

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
        salesEmployees: <?= json_encode($salesEmployees ?? []) ?>,
        cashAccounts: <?= json_encode($cashAccounts ?? []) ?>,
        shelfItemsMap: <?= json_encode($shelfItemsMap ?? []) ?>,

        searchQuery: <?= json_encode($pagination['q'] ?? '') ?>,
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
            sales_driver_id: '',
            alamat_lengkap: '',
            link_google_maps: '',
            nomor_whatsapp: '',
            tipe_pembayaran_default: 'cash',
            plafon_piutang: '5.000.000',
            is_konsinyasi: false,
            nama_bank: '',
            nomor_rekening: '',
            atas_nama_rekening: '',
            status_aktif: true,
            konversi_konsinyasi_opsi: '',
            metode_beli_putus: 'lunas',
            akun_kas_id: '',
            catatan_konversi: ''
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
            this.$watch('form.tipe_pembayaran_default', () => {
                this.$nextTick(() => lucide.createIcons());
            });
            this.$watch('form.konversi_konsinyasi_opsi', () => {
                this.$nextTick(() => lucide.createIcons());
            });
            this.$watch('showModal', (val) => {
                if (val) this.$nextTick(() => lucide.createIcons());
            });
        },

        switchTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location.href);
            if (tab === 'customers') {
                url.searchParams.delete('tab');
            } else {
                url.searchParams.set('tab', tab);
            }
            window.history.replaceState(null, '', url.toString());
            this.$nextTick(() => lucide.createIcons());
        },

        cleanWa(num) {
            let digits = String(num || '').replace(/[^0-9]/g, '');
            if (digits.startsWith('0')) {
                digits = '62' + digits.slice(1);
            }
            return digits;
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

        // --- KONVERSI KONSINYASI GETTERS & HELPERS ---
        get isChangingFromConsignment() {
            if (!this.isEdit || !this.selectedCustomer) return false;
            const wasConsignment = this.selectedCustomer.is_konsinyasi === true ||
                this.selectedCustomer.is_konsinyasi === 'true' ||
                this.selectedCustomer.is_konsinyasi === 1 ||
                this.selectedCustomer.is_konsinyasi === '1';
            const activeStock = Number(this.selectedCustomer.stok_titip_aktif || 0);
            return wasConsignment && activeStock > 0 && this.form.tipe_pembayaran_default !== 'konsinyasi';
        },

        get currentShelfItems() {
            if (!this.selectedCustomer || !this.shelfItemsMap[this.selectedCustomer.id]) {
                return [];
            }
            return this.shelfItemsMap[this.selectedCustomer.id];
        },

        get totalShelfQty() {
            return this.currentShelfItems.reduce((acc, item) => acc + Number(item.qty || 0), 0);
        },

        get totalShelfNominal() {
            return this.currentShelfItems.reduce((acc, item) => acc + Number(item.subtotal || 0), 0);
        },

        get canSubmitCustomer() {
            if (!this.isChangingFromConsignment) return true;
            if (!this.form.konversi_konsinyasi_opsi) return false;
            if (this.form.konversi_konsinyasi_opsi === 'retur') return true;
            if (this.form.konversi_konsinyasi_opsi === 'beli_putus') {
                if (this.form.metode_beli_putus === 'lunas') {
                    return Boolean(this.form.akun_kas_id);
                }
                return true;
            }
            return false;
        },

        cancelConsignmentChange() {
            this.form.tipe_pembayaran_default = 'konsinyasi';
            this.form.konversi_konsinyasi_opsi = '';
        },

        async submitCustomerForm(event) {
            const formEl = event.target || document.getElementById('customer-modal-form');
            try {
                if (formEl) {
                    formEl.action = this.isEdit ? '<?= Router::url('/customers/update') ?>' : '<?= Router::url('/customers/store') ?>';
                }

                // HANYA JIKA SEDANG BERALIH DARI KONSINYASI KE NON-KONSINYASI DENGAN STOK AKTIF:
                if (this.isChangingFromConsignment) {
                    // 1. Validasi opsi resolusi wajib dipilih
                    if (!this.form.konversi_konsinyasi_opsi) {
                        event.preventDefault();
                        if (window.AppAlert) {
                            window.AppAlert({
                                title: 'Pilih Mekanisme Penyelesaian',
                                message: `Toko ini masih memiliki ${this.totalShelfQty.toLocaleString('id-ID')} pcs stok titip konsinyasi aktif di rak. Harap pilih Opsi 1 (Retur ke Gudang) atau Opsi 2 (Beli Putus) sebelum menyimpan.`,
                                type: 'warning'
                            });
                        } else {
                            alert(`Toko ini masih memiliki ${this.totalShelfQty} pcs stok titip konsinyasi di rak. Harap pilih Opsi 1 atau Opsi 2 terlebih dahulu.`);
                        }
                        this.restoreSubmitButton(formEl);
                        return false;
                    }

                    // 2. Validasi akun kas jika beli putus lunas
                    if (this.form.konversi_konsinyasi_opsi === 'beli_putus' && this.form.metode_beli_putus === 'lunas' && !this.form.akun_kas_id) {
                        event.preventDefault();
                        if (window.AppAlert) {
                            window.AppAlert({
                                title: 'Pilih Akun Kas / Bank',
                                message: 'Harap pilih akun kas atau rekening bank penerima pembayaran untuk transaksi beli putus lunas.',
                                type: 'warning'
                            });
                        } else {
                            alert('Harap pilih akun kas atau rekening bank penerima pembayaran.');
                        }
                        this.restoreSubmitButton(formEl);
                        return false;
                    }

                    // 3. Konfirmasi aksi interaktif sebelum submit
                    event.preventDefault();
                    let confirmed = false;
                    if (this.form.konversi_konsinyasi_opsi === 'retur') {
                        confirmed = window.AppConfirm ? await window.AppConfirm({
                            title: 'Konfirmasi Penarikan Stok Konsinyasi',
                            message: `Anda akan menarik seluruh ${this.totalShelfQty.toLocaleString('id-ID')} pcs sisa stok konsinyasi ke gudang pusat dan menolkan saldo rak toko "${this.form.nama_toko}". Lanjutkan?`,
                            type: 'warning',
                            confirmText: 'Ya, Tarik Stok & Simpan'
                        }) : confirm(`Tarik seluruh ${this.totalShelfQty} pcs sisa stok konsinyasi ke gudang pusat dan simpan?`);
                    } else if (this.form.konversi_konsinyasi_opsi === 'beli_putus') {
                        if (this.form.metode_beli_putus === 'lunas') {
                            const acc = this.cashAccounts.find(a => a.id === this.form.akun_kas_id);
                            const accName = acc ? acc.nama_akun : 'Kasir/Bank';
                            confirmed = window.AppConfirm ? await window.AppConfirm({
                                title: 'Konfirmasi Beli Putus (Lunas)',
                                message: `Faktur Penjualan senilai ${this.formatRupiah(this.totalShelfNominal)} (${this.totalShelfQty.toLocaleString('id-ID')} pcs) akan diterbitkan secara LUNAS ke akun "${accName}". Saldo rak toko akan dinolkan. Lanjutkan?`,
                                type: 'warning',
                                confirmText: 'Ya, Terbitkan Faktur & Simpan'
                            }) : confirm(`Terbitkan faktur beli putus senilai ${this.formatRupiah(this.totalShelfNominal)} secara LUNAS ke akun ${accName}?`);
                        } else {
                            confirmed = window.AppConfirm ? await window.AppConfirm({
                                title: 'Konfirmasi Beli Putus (Tempo/Piutang)',
                                message: `Faktur Penjualan senilai ${this.formatRupiah(this.totalShelfNominal)} (${this.totalShelfQty.toLocaleString('id-ID')} pcs) akan dicatat sebagai PIUTANG DAGANG berjalan toko "${this.form.nama_toko}". Saldo rak toko akan dinolkan. Lanjutkan?`,
                                type: 'warning',
                                confirmText: 'Ya, Catat Piutang & Simpan'
                            }) : confirm(`Catat faktur beli putus senilai ${this.formatRupiah(this.totalShelfNominal)} sebagai PIUTANG DAGANG toko?`);
                        }
                    }

                    if (confirmed) {
                        if (formEl) {
                            formEl.action = '<?= Router::url('/customers/update') ?>';
                            HTMLFormElement.prototype.submit.call(formEl);
                        }
                    } else {
                        this.restoreSubmitButton(formEl);
                    }
                    return;
                }

                // KONDISI NORMAL: form submit POST standar browser dieksekusi secara mulus & responsif!
            } catch (err) {
                console.error('[customerApp] Error in submitCustomerForm:', err);
                this.restoreSubmitButton(formEl);
            }
        },

        restoreSubmitButton(formEl) {
            const submitBtn = (formEl || document).querySelector('#btn-submit-customer, button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('is-submitting', 'opacity-70', 'pointer-events-none');
                submitBtn.style.opacity = '';
                submitBtn.style.cursor = '';
            }
        },

        // --- TOKO PELANGGAN MODALS ---
        openAddModal() {
            this.showItemsModal = false;
            this.showCustomerGroupModal = false;
            this.showTerritoryModal = false;
            this.selectedCustomer = null;
            this.isEdit = false;
            this.form = {
                id: '',
                nama_toko: '',
                nama_pemilik: '',
                grup_pelanggan_id: this.groups[0]?.id || '',
                wilayah_id: this.territories[0]?.id || '',
                sales_driver_id: '',
                alamat_lengkap: '',
                link_google_maps: '',
                nomor_whatsapp: '',
                tipe_pembayaran_default: 'cash',
                plafon_piutang: window.formatRupiahNumber ? window.formatRupiahNumber(5000000) : '5.000.000',
                is_konsinyasi: false,
                nama_bank: '',
                nomor_rekening: '',
                atas_nama_rekening: '',
                status_aktif: true,
                konversi_konsinyasi_opsi: '',
                metode_beli_putus: 'lunas',
                akun_kas_id: (this.cashAccounts.find(a => a.is_default_pos) || this.cashAccounts[0])?.id || '',
                catatan_konversi: ''
            };
            this.showModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditModal(c) {
            this.showItemsModal = false;
            this.showCustomerGroupModal = false;
            this.showTerritoryModal = false;
            this.selectedCustomer = c;
            this.isEdit = true;
            const defaultAcc = this.cashAccounts.find(a => a.is_default_pos) || this.cashAccounts[0];
            this.form = {
                id: c.id,
                kode_pelanggan: c.kode_pelanggan || '',
                nama_toko: c.nama_toko,
                nama_pemilik: c.nama_pemilik || '',
                grup_pelanggan_id: c.grup_pelanggan_id || '',
                wilayah_id: c.wilayah_id || '',
                sales_driver_id: c.sales_driver_id || '',
                alamat_lengkap: c.alamat_lengkap || '',
                link_google_maps: c.link_google_maps || '',
                nomor_whatsapp: c.nomor_whatsapp || '',
                tipe_pembayaran_default: Boolean(c.is_konsinyasi) ? 'konsinyasi' : (c.tipe_pembayaran_default || 'cash'),
                plafon_piutang: window.formatRupiahNumber ? window.formatRupiahNumber(c.plafon_piutang) : String(c.plafon_piutang || 0),
                nama_bank: c.nama_bank || '',
                nomor_rekening: c.nomor_rekening || '',
                atas_nama_rekening: c.atas_nama_rekening || '',
                status_aktif: Boolean(c.status_aktif),
                konversi_konsinyasi_opsi: '',
                metode_beli_putus: 'lunas',
                akun_kas_id: defaultAcc ? defaultAcc.id : '',
                catatan_konversi: ''
            };
            this.showModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async deleteCustomer(id, name, code) {
            if (code === 'CUST-001' || (name && name.toLowerCase().includes('walk-in'))) {
                if (window.AppAlert) {
                    await window.AppAlert({
                        title: 'Pelanggan Terkunci',
                        message: 'Pelanggan default sistem (CUST-001 / Walk-in Cash) terkunci permanen dan tidak dapat dihapus.',
                        type: 'warning'
                    });
                } else {
                    alert('Pelanggan default sistem (CUST-001 / Walk-in Cash) terkunci permanen dan tidak dapat dihapus.');
                }
                return;
            }

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
            const group = this.customerGroups.find(g => g.id === id);
            const totalStores = group ? (Number(group.total_pelanggan) || 0) : 0;

            if (totalStores > 0) {
                if (window.AppAlert) {
                    window.AppAlert({
                        title: 'Proteksi Integritas Grup',
                        message: `Grup "${name}" saat ini masih digunakan oleh ${totalStores} toko pelanggan dan tidak dapat dihapus. Silakan alihkan toko ke grup lain terlebih dahulu jika ingin menghapus grup ini.`,
                        type: 'warning'
                    });
                } else {
                    alert(`Grup "${name}" masih digunakan oleh ${totalStores} toko pelanggan.`);
                }
                return;
            }

            if (this.customerGroups.length <= 1) {
                if (window.AppAlert) {
                    window.AppAlert({
                        title: 'Grup Wajib Ada',
                        message: 'Sistem wajib memiliki minimal 1 grup pelanggan aktif sebagai acuan harga.',
                        type: 'warning'
                    });
                } else {
                    alert('Sistem wajib memiliki minimal 1 grup pelanggan.');
                }
                return;
            }

            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Grup Pelanggan',
                message: `Apakah Anda yakin ingin menghapus "${name}"? Tindakan ini permanen dan tidak dapat dibatalkan.`,
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
            const t = this.territories.find(item => item.id === id);
            const totalStores = t ? (Number(t.total_pelanggan) || 0) : 0;
            const totalVendors = t ? (Number(t.total_pemasok) || 0) : 0;

            if (totalStores > 0 || totalVendors > 0) {
                const parts = [];
                if (totalStores > 0) parts.push(`${totalStores} toko pelanggan`);
                if (totalVendors > 0) parts.push(`${totalVendors} vendor pemasok`);
                const detail = parts.join(' dan ');

                if (window.AppAlert) {
                    window.AppAlert({
                        title: 'Proteksi Integritas Wilayah',
                        message: `Wilayah "${name}" saat ini masih digunakan oleh ${detail} dan tidak dapat dihapus. Silakan alihkan data tersebut atau ubah status wilayah menjadi nonaktif.`,
                        type: 'warning'
                    });
                } else {
                    alert(`Wilayah "${name}" masih digunakan oleh ${detail}.`);
                }
                return;
            }

            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Wilayah / Rute',
                message: `Apakah Anda yakin ingin menghapus wilayah "${name}"? Tindakan ini permanen dan tidak dapat dibatalkan.`,
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

<style>
.payment-choice-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease-in-out;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}
.payment-choice-card:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}
.payment-choice-card.is-selected-lunas {
    border-color: #10b981 !important;
    background: #f0fdf4 !important;
}
.payment-choice-card.is-selected-tempo {
    border-color: #f59e0b !important;
    background: #fffbeb !important;
}
.choice-icon-box {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.15s ease;
}
.dark .payment-choice-card {
    background: #1e293b;
    border-color: #334155;
}
.dark .payment-choice-card:hover {
    background: #263347;
    border-color: #475569;
}
.dark .payment-choice-card.is-selected-lunas {
    background: rgba(16, 185, 129, 0.12) !important;
    border-color: #10b981 !important;
}
.dark .payment-choice-card.is-selected-tempo {
    background: rgba(245, 158, 11, 0.12) !important;
    border-color: #f59e0b !important;
}
</style>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>


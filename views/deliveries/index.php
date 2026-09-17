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
                    <?= count(array_filter($deliveries, fn($d) => in_array($d['status_surat_jalan'], ['disetujui_owner', 'sedang_dikirim']))) ?>
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
                    <option value="disetujui_owner">Siap Kirim</option>
                    <option value="sedang_dikirim">Sedang Dikirim</option>
                    <option value="selesai_diterima">Selesai (Diterima)</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <a href="<?= Router::url('/deliveries/export/excel') ?>" class="btn btn-secondary" style="height:38px;white-space:nowrap;background:#10b981;color:#fff;border-color:#059669;font-weight:700;">
                    <i data-lucide="file-spreadsheet"></i>
                    <span>Export Excel</span>
                </a>
                <button @click="openAddModal()" :disabled="pendingOrders.length === 0" class="btn btn-primary" style="height:38px;white-space:nowrap;">
                    <i data-lucide="plus"></i>
                    <span>Terbitkan Surat Jalan</span>
                </button>
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
                                <template x-if="d.status_surat_jalan === 'siap_kirim'">
                                    <span class="badge" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;font-weight:700;font-size:11px;">
                                        📦 Siap Berangkat
                                    </span>
                                </template>
                                <template x-if="d.status_surat_jalan === 'sedang_dikirim'">
                                    <span class="badge" style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;font-weight:700;font-size:11px;">
                                        🚚 Sedang Dikirim
                                    </span>
                                </template>
                                <template x-if="d.status_surat_jalan === 'selesai_diterima'">
                                    <span class="badge" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;font-weight:700;font-size:11px;">
                                        ✅ Selesai Diterima
                                    </span>
                                </template>
                                <template x-if="d.status_surat_jalan === 'gagal_kembali' || d.status_surat_jalan === 'gagal_kirim' || d.status_surat_jalan === 'dibatalkan'">
                                    <span class="badge" style="background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;font-weight:700;font-size:11px;">
                                        ❌ Gagal Kirim / Retur
                                    </span>
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

                                    <a :href="'<?= Router::url('/deliveries/print') ?>?id=' + d.id" class="btn btn-ghost btn-sm" style="padding:6px 8px;color:#0284c7;" title="Cetak Surat Jalan (Standar / Dot Matrix)">
                                        <i data-lucide="printer" style="width:14px;height:14px;"></i>
                                    </a>

                                    <a :href="'<?= Router::url('/deliveries/pdf') ?>?id=' + d.id" target="_blank" class="btn btn-ghost btn-sm" style="padding:6px 8px;color:#dc2626;" title="Unduh PDF Surat Jalan">
                                        <i data-lucide="file-text" style="width:14px;height:14px;"></i>
                                    </a>
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
    <template x-teleport="body">
    <div x-show="showAddModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:500px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title">Terbitkan Surat Jalan Pengiriman</div>
                <button @click="showAddModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/deliveries/store') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <label class="form-label">Pilih Nota Pesanan Toko *</label>
                    <select name="pesanan_id" required class="form-input searchable-select">
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
                        <label class="form-label font-bold">Tanggal Kirim / Surat Jalan *</label>
                        <input type="date" name="tanggal_surat_jalan" x-model="defaultDeliveryDate" required class="form-input font-medium" style="height:40px;">
                    </div>
                    <div>
                        <label class="form-label font-bold">Driver / Petugas Pengantar *</label>
                        <select name="sales_driver_id" required class="form-input" style="height:40px;">
                            <option value="">-- Pilih Driver / Petugas Pengantar --</option>
                            <?php foreach ($drivers as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nama_karyawan']) ?><?= !empty($d['nomor_polisi_kendaraan']) ? ' (' . htmlspecialchars($d['nomor_polisi_kendaraan']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="font-size:11.5px;color:var(--color-ink-mute);display:flex;align-items:center;gap:6px;padding:0 2px;">
                    <i data-lucide="clock" style="width:13px;height:13px;color:var(--color-primary);flex-shrink:0;"></i>
                    <span x-text="isAfternoon ? 'Dibuat siang/sore (>= 12:00 WIB): default tanggal otomatis diset untuk pengiriman BESOK.' : 'Dibuat pagi (< 12:00 WIB): default tanggal otomatis diset untuk pengiriman HARI INI.'"></span>
                </div>

                <div style="padding:10px 14px;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:10px;font-size:12px;color:#1e40af;display:flex;align-items:center;gap:8px;">
                    <i data-lucide="info" style="width:16px;height:16px;flex-shrink:0;"></i>
                    <span>Surat Jalan otomatis berstatus <strong>Siap Dikirim</strong> dan langsung dialokasikan ke jadwal rute driver.</span>
                </div>
                <input type="hidden" name="status_surat_jalan" value="disetujui_owner">

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showAddModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span>Terbitkan Dokumen</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- 4. MODAL UBAH SURAT JALAN (DRIVER & TANGGAL PENGIRIMAN)                  -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showEditModal" x-cloak class="modal-backdrop" @click.self="showEditModal = false" @keydown.escape.window="showEditModal = false" style="z-index:9999;">
        <div class="modal-box" style="max-width:500px;padding:24px;">
            <div class="modal-header" style="margin-bottom:16px;">
                <div class="flex items-center gap-3">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(37,99,235,0.1);color:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="edit-3" style="width:18px;height:18px;"></i>
                    </div>
                    <div>
                        <div class="modal-title" style="font-size:16px;font-weight:800;color:var(--color-ink);">Ubah Surat Jalan</div>
                        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:1px;">Ubah Pengemudi/Sales &amp; Tanggal Pengiriman</div>
                    </div>
                </div>
                <button @click="showEditModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/deliveries/update') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
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

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
                    <button type="button" @click="showEditModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;font-weight:700;">
                        <i data-lucide="save" style="width:15px;height:15px;"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
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

        init() {
            const urlParams = new URLSearchParams(window.location.search);
            const autoOrderId = urlParams.get('create_for_order');
            if (autoOrderId) {
                this.showAddModal = true;
                this.$nextTick(() => {
                    const select = document.querySelector('select[name="pesanan_id"]');
                    if (select) {
                        select.value = autoOrderId;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    if (typeof window.initSearchableSelects === 'function') {
                        window.initSearchableSelects();
                    }
                    lucide.createIcons();
                });
            } else {
                this.$nextTick(() => lucide.createIcons());
            }
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
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

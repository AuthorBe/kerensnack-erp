<?php
use App\Core\Router;
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
        <div class="page-header-actions">
            <button @click="openAddModal()" class="btn btn-primary" style="font-weight:700;">
                <i data-lucide="plus"></i>
                <span>Tambah Pemasok Baru</span>
            </button>
        </div>
    </div>

    <!-- STATS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="building-2"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Pemasok Vendor</div>
                <div class="stat-card-value"><?= count($suppliers) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Mitra supplier bahan & kemasan</div>
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
    </div>

    <!-- MAIN CARD -->
    <div class="card" style="padding:0;overflow:hidden;">

        <!-- FILTER & ACTION BAR -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 sm:p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="form-input-icon flex-1 sm:max-w-xs">
                <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                <input type="text" x-model="searchQuery" placeholder="Cari nama pemasok / kode..." class="form-input" style="height:38px;font-size:13px;">
            </div>

            <button @click="openAddModal()" class="btn btn-primary" style="height:38px;white-space:nowrap;">
                <i data-lucide="plus"></i>
                <span>Tambah Pemasok Baru</span>
            </button>
        </div>

        <!-- TABLE LIST -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 850px;">
                <thead>
                    <tr>
                        <th style="width:110px; min-width:90px;" class="cell-nowrap">Kode</th>
                        <th style="min-width:180px;">Nama Pemasok</th>
                        <th style="min-width:140px;">Kontak Telepon</th>
                        <th style="min-width:180px;">Wilayah / Alamat</th>
                        <th style="min-width:160px;">Rekening Bank Vendor</th>
                        <th class="cell-center cell-nowrap" style="width:90px; min-width:80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="s in filteredSuppliers" :key="s.id">
                        <tr :style="!s.status_aktif ? 'opacity:0.5;' : ''">
                            <td class="cell-nowrap">
                                <span class="badge badge-mono" x-text="s.kode_pemasok"></span>
                            </td>
                            <td>
                                <div style="font-weight:700;color:var(--color-ink);" x-text="s.nama_pemasok"></div>
                            </td>
                            <td class="cell-nowrap">
                                <div style="font-family:var(--font-mono);font-size:12px;font-weight:600;" x-text="s.nomor_telepon || '-'"></div>
                            </td>
                            <td>
                                <div style="font-weight:600;" x-text="s.nama_wilayah || '-'"></div>
                                <div style="font-size:11px;color:var(--color-ink-mute);" x-text="s.alamat_lengkap"></div>
                            </td>
                            <td>
                                <template x-if="(s.detail_bank && s.detail_bank.length > 0) || s.nomor_rekening">
                                    <div>
                                        <div style="font-weight:600;font-size:12px;" x-text="((s.detail_bank && s.detail_bank[0] && s.detail_bank[0].bank) || s.nama_bank || 'Bank') + ' - ' + ((s.detail_bank && s.detail_bank[0] && s.detail_bank[0].nomor_rekening) || s.nomor_rekening)"></div>
                                        <div style="font-size:11px;color:var(--color-ink-mute);" x-text="'a/n ' + ((s.detail_bank && s.detail_bank[0] && s.detail_bank[0].atas_nama) || s.atas_nama_rekening || '-')"></div>
                                    </div>
                                </template>
                                <template x-if="(!s.detail_bank || s.detail_bank.length === 0) && !s.nomor_rekening">
                                    <span style="color:var(--color-ink-mute);">-</span>
                                </template>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <button @click="openEditModal(s)" class="btn btn-ghost btn-sm" style="padding:6px 10px;" title="Edit Data Vendor">
                                    <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                </button>
                            </td>
                        </tr>
                    </template>

                    <template x-if="filteredSuppliers.length === 0">
                        <tr>
                            <td colspan="6" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="search-x" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Tidak ada data pemasok yang cocok</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TAMBAH / EDIT PEMASOK -->
    <template x-teleport="body">
    <div x-show="showModal" x-cloak class="modal-backdrop">
        <div @click.away="showModal = false" class="modal-box" style="max-width:500px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title" x-text="isEdit ? 'Edit Data Pemasok' : 'Tambah Pemasok Baru'"></div>
                <button @click="showModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form :action="isEdit ? '<?= Router::url('/suppliers/update') ?>' : '<?= Router::url('/suppliers/store') ?>'" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="id" :value="form.id">

                <div>
                    <label class="form-label">Nama Pemasok / Vendor *</label>
                    <input type="text" name="nama_pemasok" x-model="form.nama_pemasok" required class="form-input" placeholder="Contoh: PT SUMBER PLASTIK">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nomor Telepon / WA</label>
                        <input type="text" name="nomor_telepon" x-model="form.nomor_telepon" class="form-input font-mono" placeholder="081234567890">
                    </div>
                    <div>
                        <label class="form-label">Wilayah</label>
                        <select name="wilayah_id" x-model="form.wilayah_id" class="form-input">
                            <option value="">-- Tanpa Wilayah --</option>
                            <?php foreach ($territories as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_wilayah']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label">Alamat Lengkap</label>
                    <textarea name="alamat_lengkap" x-model="form.alamat_lengkap" class="form-input" rows="2" placeholder="Kawasan Industri..."></textarea>
                </div>

                <!-- Rekening Bank -->
                <div style="padding:12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;flex-direction:column;gap:10px;">
                    <div style="font-size:11.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-primary);">Rekening Bank (Opsional)</div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div>
                            <label class="form-label" style="font-size:11px;">Nama Bank</label>
                            <input type="text" name="bank_nama" x-model="form.bank_nama" class="form-input" placeholder="BCA / Mandiri">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:11px;">No. Rekening</label>
                            <input type="text" name="bank_rekening" x-model="form.bank_rekening"
                                   @input="form.bank_rekening = $event.target.value.replace(/[^0-9-]/g, '').slice(0, 25)"
                                   maxlength="25" class="form-input font-mono" placeholder="1234567890">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:11px;">Atas Nama <template x-if="form.bank_rekening"><span style="color:var(--color-danger);">*</span></template></label>
                            <input type="text" name="bank_atas_nama" x-model="form.bank_atas_nama"
                                   :required="!!form.bank_rekening"
                                   class="form-input" placeholder="Nama Pemilik">
                        </div>
                    </div>
                    <div style="font-size:10.5px;color:var(--color-ink-mute);">Opsional. Jika nomor rekening diisi, pemilik rekening wajib diisi.</div>
                </div>

                <template x-if="isEdit">
                    <div style="display:flex;align-items:center;gap:8px;padding-top:4px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;">
                            <input type="checkbox" name="status_aktif" x-model="form.status_aktif" style="width:16px;height:16px;accent-color:var(--color-primary);">
                            <span>Status Pemasok Aktif</span>
                        </label>
                    </div>
                </template>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Pemasok'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

</div>

<script>
function supplierApp() {
    return {
        suppliers: <?= json_encode(array_map(function($s) {
            $s['detail_bank'] = is_string($s['detail_bank']) ? json_decode($s['detail_bank'], true) : $s['detail_bank'];
            return $s;
        }, $suppliers)) ?>,
        searchQuery: '',
        showModal: false,
        isEdit: false,
        form: {
            id: '',
            nama_pemasok: '',
            nomor_telepon: '',
            wilayah_id: '',
            alamat_lengkap: '',
            bank_nama: '',
            bank_rekening: '',
            bank_atas_nama: '',
            status_aktif: true
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        get filteredSuppliers() {
            return this.suppliers.filter(s => {
                const q = this.searchQuery.toLowerCase();
                return !q ||
                    s.nama_pemasok.toLowerCase().includes(q) ||
                    s.kode_pemasok.toLowerCase().includes(q) ||
                    (s.alamat_lengkap && s.alamat_lengkap.toLowerCase().includes(q));
            });
        },

        openAddModal() {
            this.isEdit = false;
            this.form = {
                id: '',
                nama_pemasok: '',
                nomor_telepon: '',
                wilayah_id: '',
                alamat_lengkap: '',
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
            const bank0 = (s.detail_bank && s.detail_bank.length > 0) ? s.detail_bank[0] : {};
            this.form = {
                id: s.id,
                nama_pemasok: s.nama_pemasok,
                nomor_telepon: s.nomor_telepon || '',
                wilayah_id: s.wilayah_id || '',
                alamat_lengkap: s.alamat_lengkap || '',
                bank_nama: bank0.bank || s.nama_bank || '',
                bank_rekening: bank0.nomor_rekening || s.nomor_rekening || '',
                bank_atas_nama: bank0.atas_nama || s.atas_nama_rekening || '',
                status_aktif: Boolean(s.status_aktif)
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

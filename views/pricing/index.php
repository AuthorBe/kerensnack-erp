<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<div class="space-y-5" x-data="pricingApp()">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-amber">
                <i data-lucide="layers"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#f59e0b;"></span>
                    <span>Manajemen Harga Jual</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Matriks Level Harga Produk' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Pengaturan 28 Tingkat Level Harga Jual Per Bungkus / Pcs' ?></p>
            </div>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="layers"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Grup Produk</div>
                <div class="stat-card-value"><?= count($groups) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:4px;">Grup kemasan aktif</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                <i data-lucide="tags"></i>
            </div>
            <div>
                <div class="stat-card-label">Tingkat Level Harga</div>
                <div class="stat-card-value" style="font-size:18px;">Level 1 – 28</div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:4px;">Multi-tier pricing dynamic</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-card-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
                <i data-lucide="users"></i>
            </div>
            <div>
                <div class="stat-card-label">Tier Grup Pelanggan</div>
                <div class="stat-card-value"><?= count($customerGroups) ?> Tier</div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:4px;">Kategori toko &amp; diskon</div>
            </div>
        </div>
    </div>

    <!-- MATRIKS HARGA PER GRUP PRODUK -->
    <div class="card">
        <div class="section-header" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <div class="section-title">Matriks Harga per Grup Produk (Level 1–28)</div>
                <div class="section-subtitle">Klik kartu level untuk mengubah harga, atau klik tombol tambah untuk menambah level baru.</div>
            </div>
            <div style="width:240px;">
                <input type="text" x-model="searchMatrix" class="form-input font-mono" style="font-size:12px;height:34px;" placeholder="Cari grup produk...">
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:14px;">
            <template x-for="g in filteredGroups" :key="g.id">
                <div style="padding:16px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);">
                    <!-- Grup Header -->
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;margin-bottom:12px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="badge badge-mono" x-text="g.kode_grup"></span>
                            <span style="font-size:14px;font-weight:700;color:var(--color-ink);" x-text="g.nama_grup"></span>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <button @click="openAddLevelModal(g)" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:12px;">
                                <i data-lucide="plus" style="width:13px;height:13px;"></i>
                                Atur / Tambah Level
                            </button>
                        </div>
                    </div>

                    <!-- Harga Level Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
                        <template x-if="!groupedPrices[g.id] || groupedPrices[g.id].length === 0">
                            <div style="grid-column:1 / -1;padding:12px;background:var(--color-canvas);border:1px dashed var(--color-hairline);border-radius:var(--rounded-md);font-size:12px;color:var(--color-ink-mute);text-align:center;">
                                Belum ada pengaturan level harga khusus. Menggunakan rumus kalkulasi default POS.
                            </div>
                        </template>

                        <template x-for="p in (groupedPrices[g.id] || [])" :key="p.id">
                            <div style="position:relative;padding:12px;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);box-shadow:var(--shadow-1);transition:border-color 0.15s ease;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                    <span class="badge badge-success" style="font-size:11px;font-weight:800;" x-text="'Level ' + p.level_harga"></span>
                                    <div style="display:flex;align-items:center;gap:4px;">
                                        <button @click="openEditLevelModal(g, p)" class="btn btn-ghost btn-sm" style="padding:2px 5px;font-size:11px;" title="Ubah Harga">
                                            <i data-lucide="edit-3" style="width:12px;height:12px;"></i>
                                        </button>
                                        <button @click="deleteLevel(p.id)" class="btn btn-ghost btn-sm" style="padding:2px 5px;color:var(--color-danger);font-size:11px;" title="Hapus Level">
                                            <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                                        </button>
                                    </div>
                                </div>
                                <div style="font-size:11.5px;font-weight:600;color:var(--color-ink-mute);margin-bottom:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="p.nama_level || ('Level ' + p.level_harga)"></div>

                                <div style="display:flex;justify-content:space-between;align-items:center;padding:4px 0;border-top:1px solid var(--color-hairline);font-size:12px;">
                                    <span style="color:var(--color-ink-mute);font-size:11px;">Harga Jual:</span>
                                    <strong class="cell-currency" style="color:var(--color-primary-deep);font-size:13px;" x-text="formatRupiah(p.harga_jual_pcs) + '/pcs'"></strong>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- MODAL: ATUR / TAMBAH LEVEL HARGA PRODUK -->
    <template x-teleport="body">
    <div x-show="showLevelModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:460px;padding:24px;">
            <div class="modal-header">
                <div>
                    <div class="modal-title" x-text="isEditLevel ? 'Ubah Level Harga' : 'Tambah Level Harga'"></div>
                    <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;" x-text="selectedGroup?.nama_grup"></div>
                </div>
                <button @click="showLevelModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/pricing/update-level') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="grup_produk_id" :value="selectedGroup?.id">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Tingkat Level Harga (1–28) *</label>
                        <select name="level_harga" x-model.number="levelForm.level_harga" @change="onLevelNumberChange()" class="form-input">
                            <?php for ($i = 1; $i <= 28; $i++): ?>
                            <option value="<?= $i ?>">Level <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Nama / Label Level *</label>
                        <input type="text" name="nama_level" x-model="levelForm.nama_level" required class="form-input" placeholder="Contoh: Level 8 - Grosir Mitra">
                    </div>
                </div>

                <div>
                    <label class="form-label">Harga Jual Satuan per Bungkus/Pcs (Rp) *</label>
                    <input type="text" name="harga_jual_pcs" x-model="levelForm.harga_jual_pcs" required class="form-input font-mono input-rupiah" placeholder="15.000">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
                    <button type="button" @click="showLevelModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span x-text="isEditLevel ? 'Simpan Perubahan' : 'Simpan Level Harga'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- FORM SUBMIT HIDDEN FOR DELETE -->
    <form id="delete-level-form" action="<?= Router::url('/pricing/delete-level') ?>" method="POST" data-action-text="Menghapus level harga..." style="display:none;">
        <input type="hidden" name="id" id="delete-level-id">
    </form>

</div>

<script>
function pricingApp() {
    return {
        searchMatrix: '',
        groups: <?= json_encode($groups) ?>,
        groupedPrices: <?= json_encode($groupedPrices) ?>,

        // Modal Level
        showLevelModal: false,
        isEditLevel: false,
        selectedGroup: null,
        levelForm: {
            id: '',
            level_harga: 1,
            nama_level: 'Level 1 - Ritel Standar',
            harga_jual_pcs: '15.000'
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        get filteredGroups() {
            if (!this.searchMatrix.trim()) return this.groups;
            const q = this.searchMatrix.toLowerCase();
            return this.groups.filter(g =>
                g.nama_grup.toLowerCase().includes(q) ||
                g.kode_grup.toLowerCase().includes(q) ||
                (g.barcode_universal && g.barcode_universal.includes(q))
            );
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        openAddLevelModal(g) {
            this.selectedGroup = g;
            this.isEditLevel = false;
            this.levelForm = {
                id: '',
                level_harga: 1,
                nama_level: 'Level 1 - Ritel Standar',
                harga_jual_pcs: '15.000'
            };
            this.showLevelModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditLevelModal(g, p) {
            this.selectedGroup = g;
            this.isEditLevel = true;
            this.levelForm = {
                id: p.id,
                level_harga: Number(p.level_harga),
                nama_level: p.nama_level || ('Level ' + p.level_harga),
                harga_jual_pcs: window.formatRupiahNumber ? window.formatRupiahNumber(p.harga_jual_pcs) : String(p.harga_jual_pcs)
            };
            this.showLevelModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        onLevelNumberChange() {
            const level = this.levelForm.level_harga;
            if (!this.isEditLevel) {
                const levelNames = {
                    1: 'Level 1 - Ritel Standar',
                    5: 'Level 5 - Konsinyasi Rak',
                    8: 'Level 8 - Grosir Mitra',
                    12: 'Level 12 - Agen Pasar',
                    15: 'Level 15 - Distributor Utama',
                    20: 'Level 20 - Super Grosir',
                    28: 'Level 28 - Tier Khusus Pabrik'
                };
                this.levelForm.nama_level = levelNames[level] || ('Level ' + level);
            }
        },

        async deleteLevel(id) {
            const confirmed = window.AppConfirm ? await window.AppConfirm({
                title: 'Hapus Level Harga',
                message: 'Apakah Anda yakin ingin menghapus level harga ini dari grup produk?',
                type: 'danger',
                confirmText: 'Ya, Hapus'
            }) : confirm('Hapus level harga ini?');

            if (confirmed) {
                document.getElementById('delete-level-id').value = id;
                document.getElementById('delete-level-form').submit();
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

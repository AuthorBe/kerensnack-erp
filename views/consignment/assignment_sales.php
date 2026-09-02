<?php
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="assignmentApp()" class="space-y-4 sm:space-y-6 pb-20">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-header-body">
            <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary btn-sm p-2 rounded-xl" title="Kembali ke Portal">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#8b5cf6;"></span>
                    <span>Master Penugasan • Admin &amp; Owner</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl">Assignment Sales ↔ Toko</h1>
                <p class="page-subtitle text-xs sm:text-sm">Penetapan penanggung jawab sales pemegang toko mitra konsinyasi tetap (basis komisi).</p>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="relative flex items-center w-full">
        <i data-lucide="search" class="absolute left-3.5 w-4 h-4 text-slate-400 pointer-events-none" style="color:var(--color-ink-mute);"></i>
        <input type="text" 
               x-model="searchQuery" 
               placeholder="Cari nama toko, kode pelanggan, atau sales..." 
               class="form-input w-full text-xs sm:text-sm"
               style="height:42px;padding-left:38px;padding-right:38px;border-radius:14px;background:var(--color-surface);border:1px solid var(--color-hairline);color:var(--color-ink);">
        <button type="button" 
                x-show="searchQuery" 
                @click="searchQuery = ''" 
                class="absolute right-3 p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors" 
                style="color:var(--color-ink-mute);"
                title="Reset Pencarian">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>

    <!-- TOKO ASSIGNMENT TABLE -->
    <?php if (empty($stores)): ?>
        <div class="card p-8 sm:p-12 text-center rounded-3xl" style="border:1px solid var(--color-hairline);">
            <i data-lucide="user-check" class="w-12 h-12 mx-auto mb-3" style="color:#8b5cf6;"></i>
            <h3 class="text-base sm:text-lg font-bold" style="color:var(--color-ink);">Belum Ada Toko Konsinyasi</h3>
            <p class="text-xs sm:text-sm mt-1 max-w-md mx-auto" style="color:var(--color-ink-mute);">
                Belum ada data pelanggan berstatus konsinyasi di sistem. Tambahkan toko baru di menu Toko Pelanggan.
            </p>
            <a href="<?= Router::url('/customers') ?>" class="btn btn-primary mt-4 inline-flex items-center gap-2">
                <i data-lucide="users" class="w-4 h-4"></i>
                <span>Buka Master Pelanggan</span>
            </a>
        </div>
    <?php else: ?>
        <div class="card rounded-3xl overflow-hidden" style="border:1px solid var(--color-hairline);">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[650px]">
                    <thead>
                        <tr class="font-bold uppercase tracking-wider text-[10.5px]" style="background:var(--color-canvas);border-bottom:1px solid var(--color-hairline);color:var(--color-ink-mute);">
                            <th class="py-3.5 px-4">Toko Mitra</th>
                            <th class="py-3.5 px-4">Pemilik &amp; Kontak</th>
                            <th class="py-3.5 px-4">Sales Penanggung Jawab</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color:var(--color-hairline);">
                        <?php foreach ($stores as $st): 
                            $hasSales = !empty($st['sales_driver_id']);
                        ?>
                        <tr class="hover:bg-slate-500/5 transition-colors"
                            x-show="!searchQuery || '<?= addslashes(strtolower($st['nama_toko'] . ' ' . ($st['nama_sales'] ?? '') . ' ' . $st['kode_pelanggan'])) ?>'.includes(searchQuery.toLowerCase().trim())">
                            
                            <td class="py-3.5 px-4 font-bold" style="color:var(--color-ink);">
                                <div class="flex items-center gap-1.5">
                                    <span style="font-size:10.5px;font-weight:800;color:#8b5cf6;letter-spacing:0.04em;">[<?= htmlspecialchars($st['kode_pelanggan'] ?? 'TOKO') ?>]</span>
                                    <span class="text-xs sm:text-sm font-black"><?= htmlspecialchars($st['nama_toko']) ?></span>
                                </div>
                                <span class="text-[10.5px] font-normal block mt-0.5" style="color:var(--color-ink-mute);"><?= htmlspecialchars($st['alamat_lengkap'] ?? '-') ?></span>
                            </td>

                            <td class="py-3.5 px-4" style="color:var(--color-ink-secondary);">
                                <div class="font-bold" style="color:var(--color-ink);"><?= htmlspecialchars($st['nama_pemilik'] ?? '-') ?></div>
                                <span class="text-[10.5px]" style="color:var(--color-ink-mute);"><?= htmlspecialchars($st['nomor_whatsapp'] ?? '-') ?></span>
                            </td>

                            <td class="py-3.5 px-4 font-bold">
                                <?php if ($hasSales): ?>
                                    <span style="color:var(--color-ink);"><?= htmlspecialchars($st['nama_sales']) ?></span>
                                    <span class="text-[9.5px] block font-normal" style="color:var(--color-ink-mute);"><?= htmlspecialchars($st['sales_telepon'] ?? '') ?></span>
                                <?php else: ?>
                                    <span class="text-amber-500 italic text-[11px]">Belum Di-assign</span>
                                <?php endif; ?>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <?php if ($hasSales): ?>
                                    <span style="background:rgba(16,185,129,0.12);color:#10b981;border:1px solid rgba(16,185,129,0.25);padding:2px 7px;border-radius:12px;font-weight:700;font-size:9.5px;">Terpasang</span>
                                <?php else: ?>
                                    <span style="background:rgba(245,158,11,0.12);color:#f59e0b;border:1px solid rgba(245,158,11,0.25);padding:2px 7px;border-radius:12px;font-weight:700;font-size:9.5px;">Belum Ada</span>
                                <?php endif; ?>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <button type="button" 
                                        @click="openAssignModal(<?= htmlspecialchars(json_encode($st)) ?>)"
                                        class="btn btn-secondary btn-sm flex items-center gap-1.5 py-1.5 px-3 rounded-xl text-xs font-bold">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5 text-violet-500"></i>
                                    <span>Ubah</span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL UBAH ASSIGNMENT -->
    <div x-show="showAssignModal" 
         x-cloak 
         class="modal-backdrop"
         @click.self="showAssignModal = false"
         @keydown.escape.window="showAssignModal = false">
        <div class="modal-box p-5 sm:p-6 rounded-3xl shadow-2xl max-h-[90vh] overflow-y-auto" style="max-width: 440px;width:100%;border:1px solid var(--color-hairline);background:var(--color-card);">
            <div class="flex items-center justify-between pb-3.5" style="border-bottom:1px solid var(--color-hairline);">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="user-check" class="w-6 h-6" style="color:#8b5cf6;"></i>
                    <div>
                        <h3 class="text-sm sm:text-base font-black" style="color:var(--color-ink);">Ubah Assignment Sales</h3>
                        <p class="text-[11px]" style="color:var(--color-ink-mute);" x-text="selectedStore.nama_toko"></p>
                    </div>
                </div>
                <button type="button" @click="showAssignModal = false" class="btn btn-ghost btn-xs">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="<?= Router::url('/consignment/assignment-sales/save') ?>" method="POST" class="space-y-3.5 mt-3.5">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="pelanggan_id" :value="selectedStore.id">

                <div>
                    <label class="block text-xs font-bold mb-1" style="color:var(--color-ink);">Pilih Sales Pemegang Toko:</label>
                    <select name="sales_driver_id" class="form-input w-full text-xs" style="height:42px;border-radius:12px;">
                        <option value="">-- Hapus Assignment (Unassigned) --</option>
                        <?php foreach ($salesList as $s): ?>
                        <option value="<?= $s['id'] ?>" :selected="selectedStore.sales_driver_id === '<?= $s['id'] ?>'">
                            <?= htmlspecialchars($s['nama_karyawan']) ?> (<?= htmlspecialchars($s['posisi']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="text-[10px] sm:text-[10.5px] mt-1 block" style="color:var(--color-ink-mute);">Komisi penjualan toko ini akan dialokasikan ke sales yang dipilih.</span>
                </div>

                <div class="grid grid-cols-2 gap-2.5 sm:gap-3 pt-3.5" style="border-top:1px solid var(--color-hairline);">
                    <button type="button" @click="showAssignModal = false" class="btn btn-secondary rounded-xl py-2.5 font-bold text-xs sm:text-sm">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary rounded-xl py-2.5 font-bold flex items-center justify-center gap-1.5 text-xs sm:text-sm" style="background:#8b5cf6;border-color:#8b5cf6;color:#fff;">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Simpan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function assignmentApp() {
    return {
        searchQuery: '',
        showAssignModal: false,
        selectedStore: {},

        openAssignModal(store) {
            this.selectedStore = store;
            this.showAssignModal = true;
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

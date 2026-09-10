<?php
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="assignmentApp()" x-init="init()" class="space-y-5 pb-20">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-violet">
                <i data-lucide="user-check"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#8b5cf6;"></span>
                    <span>Modul Konsinyasi &bull; Penugasan Mitra</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Assignment Sales ↔ Toko' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Kelola dan tetapkan toko mitra konsinyasi binaan untuk setiap Sales Lapangan.' ?></p>
            </div>
        </div>
        <div class="page-header-actions">
            <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary">
                <i data-lucide="arrow-left"></i>
                <span>Kembali ke Portal</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TOP STATS CARDS                                                           -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Card 1: Sales Lapangan Aktif -->
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6;">
                <i data-lucide="users"></i>
            </div>
            <div>
                <div class="stat-card-label">Sales Lapangan Aktif</div>
                <div class="stat-card-value"><?= (int)$totalSales ?> <span style="font-size:12px;font-weight:600;color:var(--color-ink-mute);">Orang</span></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Karyawan berposisi sales</div>
            </div>
        </div>

        <!-- Card 2: Toko Konsinyasi Terpasang -->
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(16,185,129,0.12);color:#10b981;">
                <i data-lucide="store"></i>
            </div>
            <div>
                <div class="stat-card-label">Toko Konsinyasi Terpasang</div>
                <div class="stat-card-value" style="color:#10b981;"><?= (int)$assignedStores ?> <span style="font-size:12px;font-weight:600;color:var(--color-ink-mute);">Toko</span></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Memiliki penanggung jawab tetap</div>
            </div>
        </div>

        <!-- Card 3: Toko Belum Di-assign (Interactive -> Modal) -->
        <div class="stat-card cursor-pointer transition-all hover:shadow-md group"
             @click="openUnassignedModal()"
             style="display:flex;align-items:center;justify-content:space-between;border-color:<?= $totalUnassigned > 0 ? 'rgba(245,158,11,0.45)' : 'var(--color-hairline)' ?>;"
             title="Klik untuk melihat daftar toko yang belum di-assign">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="stat-card-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b;">
                    <i data-lucide="alert-circle"></i>
                </div>
                <div>
                    <div class="stat-card-label" style="display:flex;align-items:center;gap:6px;">
                        <span style="color:<?= $totalUnassigned > 0 ? '#d97706' : 'var(--color-ink-mute)' ?>;">Belum Di-assign</span>
                        <span class="badge badge-warning" style="font-size:10px;padding:1px 6px;">Lihat &rarr;</span>
                    </div>
                    <div class="stat-card-value" style="color:<?= $totalUnassigned > 0 ? '#d97706' : 'var(--color-ink)' ?>;">
                        <?= (int)$totalUnassigned ?> <span style="font-size:12px;font-weight:600;color:var(--color-ink-mute);">Toko Bebas</span>
                    </div>
                    <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Perlu segera dialokasikan</div>
                </div>
            </div>
            <div class="transition-transform group-hover:translate-x-1" style="color:var(--color-ink-mute);">
                <i data-lucide="chevron-right" style="width:18px;height:18px;"></i>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- UNIFIED CARD: FILTER BAR & TABEL UTAMA SALES                               -->
    <!-- ========================================================================= -->
    <div class="card" style="padding:0;overflow:hidden;">
        <!-- FILTER & ACTION BAR -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 sm:p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
                <!-- Search Input -->
                <div class="form-input-icon flex-1 sm:max-w-sm">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="text" 
                           x-model="salesSearchQuery" 
                           placeholder="Cari nama sales, telepon, atau rute..." 
                           class="form-input" 
                           style="height:38px;font-size:13px;">
                </div>

                <button type="button" 
                        x-show="salesSearchQuery" 
                        @click="salesSearchQuery = ''" 
                        class="btn btn-ghost btn-sm" 
                        style="height:38px;color:var(--color-ink-mute);"
                        title="Reset Pencarian">
                    <i data-lucide="x"></i>
                    <span>Reset</span>
                </button>
            </div>

            <div style="font-size:12px;color:var(--color-ink-mute);white-space:nowrap;">
                Total: <strong style="color:var(--color-ink);"><?= count($salesList) ?></strong> Sales Terdaftar
            </div>
        </div>

        <!-- TABLE LIST -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 860px;">
                <thead>
                    <tr>
                        <th style="min-width:220px;">Sales Lapangan</th>
                        <th style="min-width:160px;" class="cell-nowrap">Kontak &amp; WhatsApp</th>
                        <th class="cell-center cell-nowrap" style="width:140px; min-width:130px;">Toko Binaan</th>
                        <th style="min-width:260px;">Cakupan Rute / Wilayah</th>
                        <th class="cell-right cell-nowrap" style="width:130px; min-width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($salesList)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center;padding:48px 20px;color:var(--color-ink-mute);">
                                <i data-lucide="users" style="width:40px;height:40px;margin:0 auto 10px auto;color:#8b5cf6;opacity:0.6;"></i>
                                <div style="font-weight:700;color:var(--color-ink);font-size:14px;">Belum Ada Karyawan Berposisi Sales</div>
                                <div style="font-size:12px;margin-top:4px;max-width:440px;margin-left:auto;margin-right:auto;">
                                    Sistem tidak menemukan karyawan aktif dengan posisi <code>sales</code>. Silakan sesuaikan jabatan karyawan di Master Karyawan.
                                </div>
                                <a href="<?= Router::url('/employees') ?>" class="btn btn-primary btn-sm" style="margin-top:14px;display:inline-flex;">
                                    <i data-lucide="user-plus"></i>
                                    <span>Buka Master Karyawan</span>
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($salesList as $s): ?>
                        <tr x-show="!salesSearchQuery || '<?= addslashes(strtolower($s['nama_karyawan'] . ' ' . ($s['nomor_telepon'] ?? '') . ' ' . ($s['wilayah_tercover'] ?? ''))) ?>'.includes(salesSearchQuery.toLowerCase().trim())">
                            
                            <!-- Sales Lapangan Info -->
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div style="width:36px;height:36px;border-radius:50%;background:rgba(139,92,246,0.12);color:#8b5cf6;font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <?= strtoupper(substr($s['nama_karyawan'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;color:var(--color-ink);font-size:13px;"><?= htmlspecialchars($s['nama_karyawan']) ?></div>
                                        <span class="badge" style="background:rgba(139,92,246,0.1);color:#8b5cf6;font-size:10px;padding:1px 6px;margin-top:2px;">
                                            <?= htmlspecialchars(strtoupper($s['posisi'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Kontak Telepon & WhatsApp -->
                            <td class="cell-nowrap">
                                <?php if (!empty($s['nomor_telepon'])): ?>
                                    <div style="display:flex;align-items:center;gap:6px;font-family:var(--font-mono);font-size:12px;font-weight:600;color:var(--color-ink);">
                                        <i data-lucide="phone" style="width:13px;height:13px;color:var(--color-ink-mute);"></i>
                                        <span><?= htmlspecialchars($s['nomor_telepon']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--color-ink-mute);font-style:italic;font-size:12px;">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Jumlah Toko Binaan -->
                            <td class="cell-center cell-nowrap">
                                <?php if ((int)$s['total_toko'] > 0): ?>
                                    <span class="badge badge-success" style="font-weight:700;font-size:11px;padding:3px 8px;">
                                        <i data-lucide="store" style="width:12px;height:12px;"></i>
                                        <span><?= (int)$s['total_toko'] ?> Toko</span>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning" style="font-size:10.5px;padding:2px 8px;">
                                        0 Toko
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Cakupan Rute & Wilayah -->
                            <td>
                                <?php if (!empty($s['wilayah_tercover'])): ?>
                                    <div style="font-size:12px;color:var(--color-ink-secondary);line-height:1.4;" class="line-clamp-2" title="<?= htmlspecialchars($s['wilayah_tercover']) ?>">
                                        <?= htmlspecialchars($s['wilayah_tercover']) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size:12px;color:var(--color-ink-mute);font-style:italic;">Belum ada toko yang dialokasikan</span>
                                <?php endif; ?>
                            </td>

                            <!-- Aksi Penugasan -->
                            <td class="cell-right cell-nowrap">
                                <button type="button" 
                                        @click="openAssignModal(<?= htmlspecialchars(json_encode($s)) ?>)"
                                        class="btn btn-primary btn-sm"
                                        style="height:32px;font-size:12px;font-weight:600;padding:0 12px;background:#8b5cf6;border-color:#8b5cf6;">
                                    <i data-lucide="store" style="width:13px;height:13px;"></i>
                                    <span>Atur Toko</span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: ATUR TOKO BINAAN SALES (CHECKBOX MULTI-SELECT TELEPORTED)         -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="showAssignModal" 
             x-cloak 
             class="modal-backdrop"
             @click.self="showAssignModal = false"
             @keydown.escape.window="showAssignModal = false">
            
            <div class="modal-box" style="max-width: 680px; width: 100%; padding: 24px;">
                
                <!-- Modal Header -->
                <div class="modal-header">
                    <div>
                        <div class="modal-title flex items-center gap-2">
                            <i data-lucide="user-check" style="width:18px;height:18px;color:#8b5cf6;"></i>
                            <span>Atur Toko Binaan Sales</span>
                        </div>
                        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:3px;">
                            Sales: <strong style="color:var(--color-ink);" x-text="activeSales.nama_karyawan"></strong> &bull;
                            <span class="font-bold" style="color:#8b5cf6;" x-text="selectedStoreIds.length + ' Toko Terpilih'"></span>
                        </div>
                    </div>
                    <button type="button" @click="showAssignModal = false" class="btn btn-ghost btn-sm" style="padding:4px;" title="Tutup Modal">
                        <i data-lucide="x" style="width:16px;height:16px;"></i>
                    </button>
                </div>

                <!-- Info Callout Banner -->
                <div class="p-3 rounded-xl mb-3 flex items-start gap-2.5 text-xs" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5" style="color:#8b5cf6;"></i>
                    <div style="color:var(--color-ink-mute);line-height:1.45;">
                        Centang toko konsinyasi yang menjadi binaan sales ini. Setiap toko hanya dapat dimiliki oleh 1 sales. Jika centang dilepas, toko akan berstatus belum di-assign.
                    </div>
                </div>

                <!-- Filter & Quick Action Bar -->
                <div class="space-y-2 mb-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <!-- Cari Toko -->
                        <div class="form-input-icon">
                            <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                            <input type="text" 
                                   x-model="storeSearchQuery" 
                                   placeholder="Cari toko / kode / alamat..." 
                                   class="form-input" 
                                   style="height:36px;font-size:12.5px;">
                        </div>

                        <!-- Filter Wilayah -->
                        <select x-model="selectedTerritory" class="form-input" style="height:36px;font-size:12.5px;">
                            <option value="">Semua Wilayah Rute</option>
                            <?php foreach ($territories as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_wilayah']) ?> (<?= htmlspecialchars($t['kode_rute']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Quick Buttons -->
                    <div class="flex items-center justify-between gap-2 pt-1 flex-wrap">
                        <div class="flex items-center gap-2">
                            <button type="button" 
                                    @click="selectAllUnassigned()" 
                                    class="btn btn-secondary btn-sm" 
                                    style="font-size:11px;padding:4px 10px;height:30px;"
                                    :title="'Otomatis centang seluruh toko yang belum memiliki penanggung jawab sales (' + unassignedCount() + ' toko)'">
                                <i data-lucide="check-check" style="width:14px;height:14px;color:#d97706;"></i>
                                <span>Pilih Semua Toko Bebas</span>
                                <span class="badge badge-warning" style="font-size:10px;padding:1px 6px;margin-left:2px;" x-text="unassignedCount()"></span>
                            </button>
                            <button type="button" 
                                    @click="selectedStoreIds = []" 
                                    class="btn btn-ghost btn-sm" 
                                    style="font-size:11px;padding:4px 10px;height:30px;color:#ef4444;"
                                    title="Kosongkan seluruh centang toko untuk sales ini">
                                <i data-lucide="x-circle" style="width:13px;height:13px;"></i>
                                <span>Lepas Semua</span>
                            </button>
                        </div>
                        <span style="font-size:11px;color:var(--color-ink-mute);">
                            Menampilkan <strong style="color:var(--color-ink);" x-text="filteredStores().length"></strong> toko
                        </span>
                    </div>
                </div>

                <!-- Form Assignment & Checklist Container -->
                <form id="formAssignment" action="<?= Router::url('/consignment/assignment-sales/save') ?>" method="POST" class="flex flex-col">
                    <?= \App\Helpers\CSRF::field() ?>
                    <input type="hidden" name="sales_id" :value="activeSales.id">

                    <!-- Hidden Inputs for Array Submission -->
                    <template x-for="sid in selectedStoreIds" :key="sid">
                        <input type="hidden" name="store_ids[]" :value="sid">
                    </template>

                    <!-- Scrollable Store Checklist (Flex Column with 10px Explicit Gap) -->
                    <div class="custom-scrollbar overflow-y-auto" 
                         style="max-height: 340px; border: 1px solid var(--color-hairline); border-radius: var(--rounded-lg); padding: 10px; background: var(--color-canvas); display: flex; flex-direction: column; gap: 10px;">
                        <template x-for="st in filteredStores()" :key="st.id">
                            <label class="flex items-start gap-3 p-3 rounded-xl border transition-all cursor-pointer select-none"
                                   :style="selectedStoreIds.includes(st.id) 
                                        ? 'background:rgba(139,92,246,0.06);border-color:rgba(139,92,246,0.4);box-shadow:0 1px 3px rgba(139,92,246,0.08);' 
                                        : 'background:var(--color-surface);border-color:var(--color-hairline);'">
                                
                                <!-- Checkbox -->
                                <input type="checkbox" 
                                       :value="st.id" 
                                       x-model="selectedStoreIds"
                                       class="w-4 h-4 rounded mt-0.5 cursor-pointer flex-shrink-0"
                                       style="accent-color: #8b5cf6;">

                                <!-- Store Details -->
                                <div class="flex-grow min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="badge badge-mono text-[10.5px]" x-text="st.kode_pelanggan"></span>
                                            <span class="font-bold text-xs truncate" style="color:var(--color-ink);" x-text="st.nama_toko"></span>
                                        </div>

                                        <!-- Status Badges -->
                                        <div>
                                            <!-- Toko milik sales aktif -->
                                            <template x-if="st.sales_driver_id === activeSales.id && selectedStoreIds.includes(st.id)">
                                                <span class="badge badge-success text-[10px]">Toko Sales Ini</span>
                                            </template>

                                            <!-- Toko dipindah dari sales lain -->
                                            <template x-if="st.sales_driver_id && st.sales_driver_id !== activeSales.id && selectedStoreIds.includes(st.id)">
                                                <span class="badge badge-warning text-[10px] flex items-center gap-1">
                                                    <i data-lucide="arrow-right-left" style="width:10px;height:10px;"></i>
                                                    <span>Pindah dari: <strong x-text="st.nama_sales"></strong></span>
                                                </span>
                                            </template>

                                            <!-- Toko dipegang sales lain (Belum dicentang) -> Opsi A: Minimalis "Sales: [Nama]" -->
                                            <template x-if="st.sales_driver_id && st.sales_driver_id !== activeSales.id && !selectedStoreIds.includes(st.id)">
                                                <span class="badge badge-muted text-[10px]" x-text="'Sales: ' + st.nama_sales"></span>
                                            </template>

                                            <!-- Toko belum di-assign -->
                                            <template x-if="!st.sales_driver_id">
                                                <span class="badge badge-warning text-[10px]">Belum Di-assign</span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Alamat & Wilayah -->
                                    <div class="flex items-center gap-2 mt-1 text-[11px]" style="color:var(--color-ink-mute);">
                                        <span class="truncate" x-text="st.alamat_lengkap || '-'"></span>
                                        <span>&bull;</span>
                                        <span class="flex-shrink-0 font-medium" style="color:var(--color-ink-secondary);" x-text="st.nama_wilayah"></span>
                                    </div>
                                </div>
                            </label>
                        </template>

                        <!-- Empty Filter State -->
                        <template x-if="filteredStores().length === 0">
                            <div class="py-8 text-center" style="color:var(--color-ink-mute);">
                                <i data-lucide="search-x" style="width:28px;height:28px;margin:0 auto 6px auto;opacity:0.5;"></i>
                                <p style="font-size:12px;">Tidak ada toko konsinyasi yang sesuai kriteria pencarian / filter.</p>
                            </div>
                        </template>
                    </div>

                    <!-- Modal Action Footer -->
                    <div style="display:flex;justify-content:space-between;align-items:center;padding-top:14px;margin-top:14px;border-top:1px solid var(--color-hairline);">
                        <span style="font-size:12px;font-weight:600;color:var(--color-ink-secondary);">
                            Terpilih: <strong style="color:#8b5cf6;" x-text="selectedStoreIds.length"></strong> Toko
                        </span>
                        <div style="display:flex;gap:8px;">
                            <button type="button" @click="showAssignModal = false" class="btn btn-secondary">
                                Batal
                            </button>
                            <button type="submit" class="btn btn-primary" style="background:#8b5cf6;border-color:#8b5cf6;">
                                <i data-lucide="check"></i>
                                <span>Simpan Penugasan</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL 2: DAFTAR TOKO BELUM DI-ASSIGN (TELEPORTED)                          -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="showUnassignedModal" 
             x-cloak 
             class="modal-backdrop"
             @click.self="showUnassignedModal = false"
             @keydown.escape.window="showUnassignedModal = false">
            
            <div class="modal-box" style="max-width: 580px; width: 100%; padding: 24px;">
                
                <!-- Modal Header -->
                <div class="modal-header">
                    <div>
                        <div class="modal-title flex items-center gap-2">
                            <i data-lucide="alert-circle" style="width:18px;height:18px;color:#d97706;"></i>
                            <span>Toko Belum Di-assign</span>
                        </div>
                        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:3px;">
                            Daftar toko mitra konsinyasi aktif yang belum memiliki penanggung jawab tetap.
                        </div>
                    </div>
                    <button type="button" @click="showUnassignedModal = false" class="btn btn-ghost btn-sm" style="padding:4px;" title="Tutup Modal">
                        <i data-lucide="x" style="width:16px;height:16px;"></i>
                    </button>
                </div>

                <!-- Search Unassigned -->
                <div class="mb-3">
                    <div class="form-input-icon">
                        <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                        <input type="text" 
                               x-model="unassignedSearchQuery" 
                               placeholder="Cari nama toko / kode / alamat..." 
                               class="form-input" 
                               style="height:36px;font-size:12.5px;">
                    </div>
                </div>

                <!-- Scrollable Unassigned Stores List (Flex Column with 10px Gap) -->
                <div class="custom-scrollbar overflow-y-auto" style="max-height: 380px; display: flex; flex-direction: column; gap: 10px; padding: 2px;">
                    <template x-for="st in filteredUnassignedStores()" :key="st.id">
                        <div class="p-3 rounded-xl border" style="border-color:var(--color-hairline);background:var(--color-surface);">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="badge badge-mono text-[10px]" x-text="st.kode_pelanggan"></span>
                                        <span class="text-xs font-bold" style="color:var(--color-ink);" x-text="st.nama_toko"></span>
                                    </div>
                                    <div class="text-[11px] mt-1" style="color:var(--color-ink-mute);" x-text="st.alamat_lengkap || '-'"></div>
                                </div>
                                <span class="badge badge-warning text-[10px] flex-shrink-0" x-text="st.nama_wilayah"></span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] mt-2 pt-2 border-t" style="border-color:var(--color-hairline);color:var(--color-ink-secondary);">
                                <div>Pemilik: <strong style="color:var(--color-ink);" x-text="st.nama_pemilik || '-'"></strong></div>
                                <div class="font-mono text-[10.5px]" x-text="st.nomor_whatsapp || '-'"></div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <template x-if="filteredUnassignedStores().length === 0">
                        <div class="py-10 text-center" style="color:var(--color-ink-mute);">
                            <i data-lucide="check-circle" style="width:36px;height:36px;margin:0 auto 8px auto;color:#10b981;"></i>
                            <div style="font-weight:700;color:var(--color-ink);font-size:13px;">Semua Toko Terpasang!</div>
                            <div style="font-size:11.5px;margin-top:2px;">Seluruh toko konsinyasi aktif telah memiliki sales penanggung jawab.</div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div style="display:flex;justify-content:flex-end;padding-top:14px;margin-top:14px;border-top:1px solid var(--color-hairline);">
                    <button type="button" @click="showUnassignedModal = false" class="btn btn-secondary">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>

<script>
function assignmentApp() {
    return {
        salesSearchQuery: '',
        showAssignModal: false,
        showUnassignedModal: false,
        activeSales: {},
        selectedStoreIds: [],
        storeSearchQuery: '',
        selectedTerritory: '',
        unassignedSearchQuery: '',

        // Data master dari PHP
        allStores: <?= json_encode($stores) ?>,
        unassignedStores: <?= json_encode($unassignedStores) ?>,

        init() {
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        openAssignModal(sales) {
            this.activeSales = sales;
            this.storeSearchQuery = '';
            this.selectedTerritory = '';
            
            // Inisialisasi: centang semua toko yang saat ini dipegang oleh sales ini
            this.selectedStoreIds = this.allStores
                .filter(s => s.sales_driver_id === sales.id)
                .map(s => s.id);
            
            this.showAssignModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        openUnassignedModal() {
            this.unassignedSearchQuery = '';
            this.showUnassignedModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        filteredStores() {
            return this.allStores.filter(s => {
                // Filter Wilayah
                if (this.selectedTerritory && s.wilayah_id !== this.selectedTerritory) {
                    return false;
                }
                // Filter Search
                if (this.storeSearchQuery) {
                    const q = this.storeSearchQuery.toLowerCase().trim();
                    const combined = (s.nama_toko + ' ' + s.kode_pelanggan + ' ' + (s.nama_pemilik || '') + ' ' + (s.alamat_lengkap || '') + ' ' + (s.nama_sales || '')).toLowerCase();
                    return combined.includes(q);
                }
                return true;
            });
        },

        filteredUnassignedStores() {
            if (!this.unassignedSearchQuery) {
                return this.unassignedStores;
            }
            const q = this.unassignedSearchQuery.toLowerCase().trim();
            return this.unassignedStores.filter(s => {
                const combined = (s.nama_toko + ' ' + s.kode_pelanggan + ' ' + (s.nama_pemilik || '') + ' ' + (s.alamat_lengkap || '')).toLowerCase();
                return combined.includes(q);
            });
        },

        selectAllUnassigned() {
            // Tambahkan semua toko yang belum memiliki sales ke dalam selectedStoreIds
            this.allStores.forEach(s => {
                if (!s.sales_driver_id && !this.selectedStoreIds.includes(s.id)) {
                    this.selectedStoreIds.push(s.id);
                }
            });
        },

        unassignedCount() {
            return this.allStores.filter(s => !s.sales_driver_id).length;
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

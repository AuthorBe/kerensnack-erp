<?php
/**
 * views/penggajian/preview.php
 * Tinjau, Koreksi, & Otorisasi Ledger Payroll Run Keren One ERP
 * 100% Selaras DNA Desain, Modern Enterprise ERP / Supabase Clean UI
 */
use App\Helpers\CSRF;
use App\Helpers\Format;
use App\Core\Auth;
use App\Core\Router;

ob_start();
?>

<style>
/* ==========================================================================
   Payroll Preview Specific Styles (Design System Harmonized)
   ========================================================================== */

/* Pulsing Status Dot */
.dot-pulse {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: currentColor;
    animation: pulseDot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulseDot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: .4; transform: scale(0.85); }
}

/* Tab Pills Filter Group */
.tab-pill-group {
    display: inline-flex;
    align-items: center;
    background: var(--color-canvas-soft, #f8fafc);
    border: 1px solid var(--color-hairline, #e2e8f0);
    border-radius: var(--rounded-md, 8px);
    padding: 3px;
    gap: 3px;
    overflow-x: auto;
    max-width: 100%;
}
.tab-pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: var(--rounded-sm, 6px);
    font-size: 12px;
    font-weight: 600;
    color: var(--color-ink-mute);
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    user-select: none;
}
.tab-pill-btn:hover {
    color: var(--color-ink);
    background: rgba(0, 0, 0, 0.04);
}
.dark .tab-pill-btn:hover {
    color: var(--color-ink);
    background: rgba(255, 255, 255, 0.06);
}
.tab-pill-btn.is-active-primary {
    background: var(--color-canvas, #ffffff) !important;
    color: var(--color-ink, #0f172a) !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    font-weight: 700;
}
.dark .tab-pill-btn.is-active-primary {
    background: #334155 !important;
    color: #f8fafc !important;
}
.tab-pill-btn.is-active-amber {
    background: #fef3c7 !important;
    color: #b45309 !important;
    border: 1px solid #fde68a;
    font-weight: 700;
}
.dark .tab-pill-btn.is-active-amber {
    background: rgba(245, 158, 11, 0.2) !important;
    color: #fbbf24 !important;
    border-color: rgba(245, 158, 11, 0.35);
}
.tab-pill-btn.is-active-sky {
    background: #eff6ff !important;
    color: #1d4ed8 !important;
    border: 1px solid #bfdbfe;
    font-weight: 700;
}
.dark .tab-pill-btn.is-active-sky {
    background: rgba(59, 130, 246, 0.2) !important;
    color: #60a5fa !important;
    border-color: rgba(59, 130, 246, 0.35);
}
.tab-pill-btn.is-active-rose {
    background: #fef2f2 !important;
    color: #b91c1c !important;
    border: 1px solid #fecaca;
    font-weight: 700;
}
.dark .tab-pill-btn.is-active-rose {
    background: rgba(239, 68, 68, 0.2) !important;
    color: #f87171 !important;
    border-color: rgba(239, 68, 68, 0.35);
}
.tab-pill-counter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 9999px;
    font-size: 10.5px;
    font-weight: 700;
    font-family: var(--font-mono);
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline, #e2e8f0);
    color: inherit;
}
.dark .tab-pill-counter {
    background: #0f172a;
    border-color: #334155;
}
</style>

<div x-data="payrollPreviewApp()" x-init="init()" class="space-y-5 pb-20">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER                                                            -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-rose">
                <i data-lucide="receipt"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#881337;"></span>
                    <span>Modul Penggajian</span>
                    <span class="badge badge-mono"><?= htmlspecialchars($run['nomor_referensi']) ?></span>
                    <?php if ($run['status'] === 'draf'): ?>
                        <span class="badge badge-warning" style="display:inline-flex; align-items:center; gap:5px;">
                            <span class="dot-pulse"></span>
                            <span>Draf Payroll</span>
                        </span>
                    <?php elseif ($run['status'] === 'disetujui'): ?>
                        <span class="badge badge-success" style="display:inline-flex; align-items:center; gap:4px;">
                            <i data-lucide="check" style="width:13px; height:13px;"></i>
                            <span>Disetujui</span>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-info" style="display:inline-flex; align-items:center; gap:4px;">
                            <i data-lucide="check-check" style="width:13px; height:13px;"></i>
                            <span>Dibayarkan</span>
                        </span>
                    <?php endif; ?>
                </div>
                <h1 class="page-title"><?= htmlspecialchars($run['nama_payroll'] ?: $run['nomor_referensi']) ?></h1>
                <p class="page-subtitle">
                    <span>Periode: <strong><?= Format::tanggalIndo($run['periode_awal']) ?></strong> s/d <strong><?= Format::tanggalIndo($run['periode_akhir']) ?></strong></span>
                    <span class="mx-1">&bull;</span>
                    <span>Tipe: <strong><?= ucfirst($run['tipe_penggajian']) ?></strong></span>
                    <?php if ($run['status'] !== 'draf' && !empty($run['disetujui_oleh'])): ?>
                        <span class="mx-1">&bull;</span>
                        <span>Disetujui oleh <?= htmlspecialchars($run['disetujui_oleh']) ?> (<?= Format::tanggalIndo($run['disetujui_pada'] ?? '') ?>)</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Page Header Actions -->
        <div class="page-header-actions" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <a href="<?= Router::url('/penggajian') ?>" class="btn btn-secondary" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="arrow-left" style="width:16px; height:16px;"></i>
                <span>Kembali</span>
            </a>

            <?php if ($run['status'] === 'draf'): ?>
                <!-- Actions saat DRAF -->
                <a href="<?= Router::url('/penggajian/create?edit_id=' . $run['id']) ?>" class="btn btn-secondary" style="height:38px; display:inline-flex; align-items:center; gap:6px;" title="Sesuaikan tanggal periode & karyawan sebelum menghitung ulang">
                    <i data-lucide="refresh-cw" style="width:15px; height:15px; color:#2563eb;"></i>
                    <span>Regenerasi Draf</span>
                </a>

                <button type="button" @click="showDeleteModal = true" class="btn btn-secondary text-rose-600 hover:text-rose-700" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                    <i data-lucide="trash-2" style="width:15px; height:15px;"></i>
                    <span>Hapus Draf</span>
                </button>

                <?php if (Auth::hasPermission('hr.payroll_approve')): ?>
                    <button type="button" @click="showApproveModal = true" class="btn btn-primary" style="height:38px; background:#059669; border-color:#047857; display:inline-flex; align-items:center; gap:6px;">
                        <i data-lucide="check-circle" style="width:16px; height:16px;"></i>
                        <span>Setujui &amp; Bayar</span>
                    </button>
                <?php endif; ?>

            <?php else: ?>
                <!-- Actions saat DISETUJUI / DIBAYARKAN -->
                <a href="<?= Router::url('/penggajian/slip-batch?run_id=' . $run['id']) ?>" target="_blank" class="btn btn-secondary" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                    <i data-lucide="file-text" style="width:16px; height:16px; color:#be123c;"></i>
                    <span>Slip Batch</span>
                </a>

                <a href="<?= Router::url('/penggajian/rekap-pdf?run_id=' . $run['id']) ?>" target="_blank" class="btn btn-secondary" style="height:38px; display:inline-flex; align-items:center; gap:6px;">
                    <i data-lucide="printer" style="width:16px; height:16px; color:#0284c7;"></i>
                    <span>Rekap PDF</span>
                </a>

                <?php if ($canCancelApprove && Auth::hasPermission('hr.payroll_approve')): ?>
                    <button type="button" @click="showCancelApproveModal = true" class="btn btn-secondary text-amber-600 hover:text-amber-700" style="height:38px; display:inline-flex; align-items:center; gap:6px;" title="Batalkan approval dalam batas 24 jam">
                        <i data-lucide="rotate-ccw" style="width:16px; height:16px;"></i>
                        <span>Batal Approval (24h)</span>
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. QUICK STATS KPI                                                        -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Kotor -->
        <div class="stat-card" style="display:flex; align-items:center; gap:14px; border-left:3.5px solid #6366f1;">
            <div class="stat-card-icon" style="background:rgba(99,102,241,0.12); color:#4f46e5;">
                <i data-lucide="layers"></i>
            </div>
            <div style="min-width:0; flex:1;">
                <div class="stat-card-label">Total Kotor (Bruto)</div>
                <div class="stat-card-value font-mono" style="font-size:20px;"><?= Format::rupiah($totalKotor) ?></div>
                <div style="font-size:11.5px; color:var(--color-ink-mute); margin-top:2px;">Bruto seluruh komponen</div>
            </div>
        </div>

        <!-- Card 2: Total Potongan -->
        <div class="stat-card" style="display:flex; align-items:center; gap:14px; border-left:3.5px solid #e11d48;">
            <div class="stat-card-icon" style="background:rgba(225,29,72,0.12); color:#be123c;">
                <i data-lucide="wallet"></i>
            </div>
            <div style="min-width:0; flex:1;">
                <div class="stat-card-label">Total Potongan</div>
                <div class="stat-card-value font-mono" style="font-size:20px; color:#e11d48;"><?= Format::rupiah($totalPotongan) ?></div>
                <div style="font-size:11.5px; color:var(--color-ink-mute); margin-top:2px;">Kasbon, tabungan &amp; lain</div>
            </div>
        </div>

        <!-- Card 3: Karyawan Terproses -->
        <div class="stat-card" style="display:flex; align-items:center; gap:14px; border-left:3.5px solid #0284c7;">
            <div class="stat-card-icon" style="background:rgba(2,132,199,0.12); color:#0284c7;">
                <i data-lucide="users"></i>
            </div>
            <div style="min-width:0; flex:1;">
                <div class="stat-card-label">Karyawan Terproses</div>
                <div class="stat-card-value font-mono" style="font-size:20px;">
                    <?= $includedCount ?> <span style="font-size:13px; font-weight:600; color:var(--color-ink-mute);">/ <?= count($items) ?> org</span>
                </div>
                <div style="font-size:11.5px; color:var(--color-ink-mute); margin-top:2px;">
                    <?= $excludedCount > 0 ? $excludedCount . ' org dikecualikan' : 'Semua karyawan disertakan' ?>
                </div>
            </div>
        </div>

        <!-- Card 4: Total Gaji Bersih -->
        <div class="stat-card" style="display:flex; align-items:center; gap:14px; border-left:3.5px solid #10b981;">
            <div class="stat-card-icon" style="background:rgba(16,185,129,0.12); color:#047857;">
                <i data-lucide="check-check"></i>
            </div>
            <div style="min-width:0; flex:1;">
                <div class="stat-card-label">Total Gaji Bersih (Net)</div>
                <div class="stat-card-value font-mono" style="font-size:20px; color:#10b981;"><?= Format::rupiah($totalGajiBersih) ?></div>
                <div style="font-size:11.5px; color:var(--color-ink-mute); margin-top:2px;">Beban kas yang dikeluarkan</div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. KARTU UTAMA & TABEL RINCIAN LEDGER                                     -->
    <!-- ========================================================================= -->
    <div class="card" style="padding:0; overflow:hidden;">
        <!-- Filter & Search Toolbar -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 sm:p-4 border-b"
             style="border-color:var(--color-hairline); background-color:var(--color-canvas);">
            
            <!-- Filter Tabs -->
            <div class="tab-pill-group">
                <button type="button" @click="statusFilter = 'all'" class="tab-pill-btn" :class="statusFilter === 'all' ? 'is-active-primary' : ''">
                    <span>Semua Karyawan</span>
                    <span class="tab-pill-counter"><?= count($items) ?></span>
                </button>
                <button type="button" @click="statusFilter = 'borongan'" class="tab-pill-btn" :class="statusFilter === 'borongan' ? 'is-active-amber' : ''">
                    <span>Borongan</span>
                    <span class="tab-pill-counter" x-text="boronganCount"></span>
                </button>
                <button type="button" @click="statusFilter = 'bulanan'" class="tab-pill-btn" :class="statusFilter === 'bulanan' ? 'is-active-sky' : ''">
                    <span>Bulanan</span>
                    <span class="tab-pill-counter" x-text="bulananCount"></span>
                </button>
                <button type="button" @click="statusFilter = 'excluded'" class="tab-pill-btn" :class="statusFilter === 'excluded' ? 'is-active-rose' : ''">
                    <span>Dikecualikan</span>
                    <span class="tab-pill-counter" x-text="excludedCount"></span>
                </button>
            </div>

            <!-- Instant Search Input Debounced -->
            <div class="flex items-center gap-2 flex-1 sm:max-w-xs w-full">
                <div class="form-input-icon flex-1 relative">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="search" x-model.debounce.250ms="searchQuery"
                           placeholder="Cari karyawan / jabatan..."
                           autocomplete="off" class="form-input" style="height:36px; font-size:12.5px; padding-right:30px;">
                    <button type="button" x-cloak x-show="searchQuery" @click="searchQuery = ''"
                            class="btn btn-ghost btn-xs text-slate-400 hover:text-slate-600"
                            style="position:absolute; right:6px; top:50%; transform:translateY(-50%);">
                        <i data-lucide="x" style="width:13px; height:13px;"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Subheader Table Counter -->
        <div style="padding:10px 18px; background:var(--color-canvas-soft); border-bottom:1px solid var(--color-hairline); display:flex; align-items:center; justify-content:space-between; font-size:12px; color:var(--color-ink-mute);">
            <div style="display:flex; align-items:center; gap:6px;">
                <i data-lucide="users" style="width:14px; height:14px;"></i>
                <span>Rincian Komponen Gaji per Karyawan</span>
            </div>
            <div class="font-mono">
                Menampilkan <strong style="color:var(--color-ink);" x-text="filteredItems.length"></strong> dari <?= count($items) ?> karyawan
            </div>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden sm:block relative overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 1100px; width: 100%;">
                <thead>
                    <tr>
                        <th style="width:44px;" class="cell-center cell-nowrap">#</th>
                        <th style="min-width:210px;">Karyawan</th>
                        <th style="width:80px;" class="cell-center cell-nowrap">Hadir</th>
                        <th style="width:135px;" class="cell-right cell-nowrap">Gaji Pokok / Upah</th>
                        <th style="width:115px;" class="cell-right cell-nowrap">Uang Hadir</th>
                        <th style="width:125px;" class="cell-right cell-nowrap">Lembur / Komisi</th>
                        <th style="width:120px;" class="cell-right cell-nowrap">Tunjangan</th>
                        <th style="width:125px;" class="cell-right cell-nowrap">Potongan</th>
                        <th style="width:140px;" class="cell-right cell-nowrap">Gaji Bersih</th>
                        <th style="width:100px;" class="cell-center cell-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, idx) in filteredItems" :key="item.id">
                        <tr :class="item.is_excluded ? 'opacity-40' : ''" :style="item.is_excluded ? 'background:rgba(239,68,68,0.03);' : ''">
                            <td class="cell-center cell-nowrap" style="color:var(--color-ink-mute); font-size:12px;" x-text="idx + 1"></td>
                            <td>
                                <div style="font-weight:700; font-size:13px; color:var(--color-ink);"
                                     :class="item.is_excluded ? 'line-through' : ''"
                                     x-text="item.nama_karyawan"></div>
                                <div style="display:flex; align-items:center; gap:5px; margin-top:3px; flex-wrap:wrap;">
                                    <template x-if="item.tipe_penggajian === 'borongan' || item.tipe_penggajian === 'mingguan'">
                                        <span class="badge badge-amber" style="font-size:10.5px; padding:1px 6px;">Borongan</span>
                                    </template>
                                    <template x-if="item.tipe_penggajian === 'bulanan'">
                                        <span class="badge badge-info" style="font-size:10.5px; padding:1px 6px;">Bulanan</span>
                                    </template>
                                    <template x-if="item.posisi && item.posisi !== '-'">
                                        <span style="font-size:11px; color:var(--color-ink-mute);" x-text="item.posisi"></span>
                                    </template>
                                    <template x-if="item.is_excluded">
                                        <span class="badge badge-danger" style="font-size:10px; padding:1px 6px;">Dikecualikan</span>
                                    </template>
                                    <template x-if="item.kasbon_adjusted">
                                        <span class="badge badge-warning" style="font-size:10px; padding:1px 6px;" title="Potongan kasbon disesuaikan agar sisa gaji tidak minus">⚠️ Kasbon Adjusted</span>
                                    </template>
                                </div>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <span class="badge badge-mono" x-text="item.hari_hadir + ' hr'"></span>
                            </td>
                            <td class="cell-right cell-currency" x-text="item.base_income_fmt"></td>
                            <td class="cell-right cell-currency" x-text="item.uang_hadir_fmt"></td>
                            <td class="cell-right cell-currency" x-text="item.lembur_komisi_fmt"></td>
                            <td class="cell-right cell-currency" x-text="item.tunjangan_total_fmt"></td>
                            <td class="cell-right cell-currency font-semibold" style="color:#e11d48;" x-text="item.potongan_total_fmt"></td>
                            <td class="cell-right cell-currency font-bold" style="color:#10b981; font-size:13.5px;" x-text="item.gaji_bersih_fmt"></td>
                            <td class="cell-center cell-nowrap">
                                <div style="display:flex; align-items:center; justify-content:center; gap:4px;">
                                    <?php if ($run['status'] === 'draf'): ?>
                                        <button type="button" @click="openEdit(item)" class="btn btn-ghost btn-sm" title="Edit Komponen Gaji" style="padding:4px 7px;">
                                            <i data-lucide="edit-3" style="width:14px; height:14px;"></i>
                                        </button>
                                        <form action="<?= Router::url('/penggajian/toggle-exclude') ?>" method="POST" style="display:inline; margin:0;">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="run_id" value="<?= $run['id'] ?>">
                                            <input type="hidden" name="item_id" :value="item.id">
                                            <button type="submit" class="btn btn-ghost btn-sm"
                                                    :class="item.is_excluded ? 'text-emerald-600 hover:text-emerald-700' : 'text-rose-600 hover:text-rose-700'"
                                                    :title="item.is_excluded ? 'Sertakan Kembali ke Payroll' : 'Kecualikan dari Payroll'"
                                                    style="padding:4px 7px;">
                                                <i data-lucide="plus" style="width:14px; height:14px;" x-show="item.is_excluded"></i>
                                                <i data-lucide="x" style="width:14px; height:14px;" x-show="!item.is_excluded"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a :href="'<?= Router::url('/penggajian/slip?run_id=' . $run['id'] . '&rincian_id=') ?>' + item.id" target="_blank"
                                           class="btn btn-secondary btn-sm" style="font-size:11px; padding:3px 8px; gap:4px;" title="Cetak Slip Gaji">
                                            <i data-lucide="file-text" style="width:13px; height:13px; color:#be123c;"></i>
                                            <span>Slip</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State Desktop -->
                    <template x-if="filteredItems.length === 0">
                        <tr>
                            <td colspan="10" style="text-align:center; padding:45px 20px; color:var(--color-ink-mute);">
                                <i data-lucide="inbox" style="width:36px; height:36px; margin:0 auto 8px auto; opacity:0.35;"></i>
                                <div style="font-weight:700; font-size:13px; color:var(--color-ink);">Tidak ada data karyawan yang cocok</div>
                                <div style="font-size:12px; margin-top:2px;">Sesuaikan filter tipe atau kata kunci pencarian</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View (block sm:hidden) -->
        <div class="block sm:hidden divide-y divide-slate-100 dark:divide-slate-800">
            <template x-for="(item, idx) in filteredItems" :key="'mob-' + item.id">
                <div class="p-3.5 space-y-2.5" :style="item.is_excluded ? 'background:rgba(239,68,68,0.03); opacity:0.5;' : ''">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-bold text-sm" style="color:var(--color-ink);" 
                                 :class="item.is_excluded ? 'line-through' : ''"
                                 x-text="item.nama_karyawan"></div>
                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                <template x-if="item.tipe_penggajian === 'borongan' || item.tipe_penggajian === 'mingguan'">
                                    <span class="badge badge-amber" style="font-size:10px; padding:1px 5px;">Borongan</span>
                                </template>
                                <template x-if="item.tipe_penggajian === 'bulanan'">
                                    <span class="badge badge-info" style="font-size:10px; padding:1px 5px;">Bulanan</span>
                                </template>
                                <span class="badge badge-mono" style="font-size:10px;" x-text="item.hari_hadir + ' hr'"></span>
                                <template x-if="item.is_excluded">
                                    <span class="badge badge-danger" style="font-size:9.5px; padding:1px 5px;">Dikecualikan</span>
                                </template>
                            </div>
                        </div>
                        <div class="text-right">
                            <div style="font-size:10.5px; color:var(--color-ink-mute);">Gaji Bersih</div>
                            <div class="font-mono font-bold text-sm" style="color:#10b981;" x-text="item.gaji_bersih_fmt"></div>
                        </div>
                    </div>

                    <!-- Breakdown Mini Grid -->
                    <div class="grid grid-cols-2 gap-2 p-2 rounded-lg border text-xs" style="background:var(--color-canvas-soft); border-color:var(--color-hairline);">
                        <div>
                            <span style="font-size:10.5px; color:var(--color-ink-mute);">Gaji/Upah:</span>
                            <span class="font-mono font-semibold block" style="color:var(--color-ink);" x-text="item.base_income_fmt"></span>
                        </div>
                        <div>
                            <span style="font-size:10.5px; color:var(--color-ink-mute);">Uang Hadir:</span>
                            <span class="font-mono font-semibold block" style="color:var(--color-ink);" x-text="item.uang_hadir_fmt"></span>
                        </div>
                        <div>
                            <span style="font-size:10.5px; color:var(--color-ink-mute);">Tunjangan:</span>
                            <span class="font-mono font-semibold block" style="color:var(--color-ink);" x-text="item.tunjangan_total_fmt"></span>
                        </div>
                        <div>
                            <span style="font-size:10.5px; color:var(--color-ink-mute);">Potongan:</span>
                            <span class="font-mono font-semibold block" style="color:#e11d48;" x-text="item.potongan_total_fmt"></span>
                        </div>
                    </div>

                    <!-- Mobile Action Row -->
                    <div class="flex items-center justify-end gap-2 pt-1">
                        <?php if ($run['status'] === 'draf'): ?>
                            <button type="button" @click="openEdit(item)" class="btn btn-secondary btn-sm" style="font-size:11.5px; padding:4px 9px;">
                                <i data-lucide="edit-3" style="width:13px; height:13px;"></i>
                                <span>Edit</span>
                            </button>
                            <form action="<?= Router::url('/penggajian/toggle-exclude') ?>" method="POST" style="display:inline; margin:0;">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="run_id" value="<?= $run['id'] ?>">
                                <input type="hidden" name="item_id" :value="item.id">
                                <button type="submit" class="btn btn-secondary btn-sm" style="font-size:11.5px; padding:4px 9px;"
                                        :class="item.is_excluded ? 'text-emerald-600' : 'text-rose-600'">
                                    <span x-text="item.is_excluded ? 'Sertakan' : 'Kecualikan'"></span>
                                </button>
                            </form>
                        <?php else: ?>
                            <a :href="'<?= Router::url('/penggajian/slip?run_id=' . $run['id'] . '&rincian_id=') ?>' + item.id" target="_blank"
                               class="btn btn-secondary btn-sm" style="font-size:11.5px; padding:4px 10px; gap:4px;">
                                <i data-lucide="file-text" style="width:13px; height:13px; color:#be123c;"></i>
                                <span>Lihat Slip</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </template>

            <template x-if="filteredItems.length === 0">
                <div style="text-align:center; padding:35px 20px; color:var(--color-ink-mute);">
                    <i data-lucide="inbox" style="width:32px; height:32px; margin:0 auto 8px auto; opacity:0.35;"></i>
                    <div style="font-weight:700; font-size:12.5px; color:var(--color-ink);">Tidak ada data karyawan yang cocok</div>
                </div>
            </template>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. MODALS (DNA POPUP STANDARD)                                            -->
    <!-- ========================================================================= -->

    <!-- MODAL 1: EDIT KOMPONEN GAJI (DRAF ONLY) -->
    <template x-teleport="body">
        <div x-show="showEditModal" x-cloak class="modal-backdrop" @click="showEditModal = false">
            <div class="modal-box modal-box-lg" style="max-width: 620px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(37,99,235,0.12); color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i data-lucide="sliders" style="width:20px; height:20px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title">Koreksi Komponen: <span x-text="editForm.karyawan_nama"></span></div>
                            <div style="font-size:12px; color:var(--color-ink-mute); margin-top:1px;">Sesuaikan tunjangan lain, potongan kasbon, tabungan, atau pembulatan.</div>
                        </div>
                    </div>
                    <button type="button" @click="showEditModal = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px; height:18px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/penggajian/update-item') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="run_id" value="<?= $run['id'] ?>">
                    <input type="hidden" name="item_id" :value="editForm.item_id">

                    <div class="modal-body custom-scrollbar space-y-3.5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Tunjangan Lain (Rp)</label>
                                <input type="number" name="tunjangan_lain" x-model.number="editForm.tunjangan_lain" class="form-input font-mono" placeholder="0">
                            </div>
                            <div>
                                <label class="form-label">Catatan Tunjangan Lain</label>
                                <input type="text" name="catatan_tunjangan_lain" x-model="editForm.catatan_tunjangan_lain" placeholder="Bonus kerajinan, transport, dll." class="form-input">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Potongan Lain (Rp)</label>
                                <input type="number" name="potongan_lain" x-model.number="editForm.potongan_lain" class="form-input font-mono" placeholder="0">
                            </div>
                            <div>
                                <label class="form-label">Catatan Potongan Lain</label>
                                <input type="text" name="catatan_potongan_lain" x-model="editForm.catatan_potongan_lain" placeholder="Ganti rugi, denda, dll." class="form-input">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="form-label" style="margin-bottom:0;">Potongan Kasbon (Rp)</label>
                                    <span class="text-xs text-slate-400 font-mono" x-show="editForm.max_kasbon > 0">
                                        Sisa: Rp <span x-text="editForm.max_kasbon.toLocaleString('id-ID')"></span>
                                    </span>
                                </div>
                                <input type="number" name="total_potongan_kasbon" x-model.number="editForm.total_potongan_kasbon" class="form-input font-mono" placeholder="0">
                            </div>
                            <div>
                                <label class="form-label">Setor Simpanan Tabungan (Rp)</label>
                                <input type="number" name="total_potongan_tabungan" x-model.number="editForm.total_potongan_tabungan" class="form-input font-mono" placeholder="0">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="form-label" style="margin-bottom:0;">Pencairan Tabungan (Rp)</label>
                                    <span class="text-xs text-slate-400 font-mono" x-show="editForm.saldo_tabungan > 0">
                                        Saldo: Rp <span x-text="editForm.saldo_tabungan.toLocaleString('id-ID')"></span>
                                    </span>
                                </div>
                                <input type="number" name="penarikan_tabungan" x-model.number="editForm.penarikan_tabungan" class="form-input font-mono" placeholder="0">
                            </div>
                            <div>
                                <label class="form-label">Pembulatan Nominal (Rp)</label>
                                <input type="number" name="nominal_pembulatan" x-model.number="editForm.nominal_pembulatan" placeholder="Contoh: 500 atau -500" class="form-input font-mono">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="showEditModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                        <button type="submit" class="btn btn-primary w-full sm:w-auto" style="display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                            <i data-lucide="save" style="width:16px; height:16px;"></i>
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL 2: OTORISASI & PEMBAYARAN PAYROLL -->
    <template x-teleport="body">
        <div x-show="showApproveModal" x-cloak class="modal-backdrop" @click="showApproveModal = false">
            <div class="modal-box modal-box-md" style="max-width: 540px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(16,185,129,0.12); color:#10b981; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i data-lucide="check-circle-2" style="width:20px; height:20px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title">Otorisasi &amp; Pembayaran Payroll</div>
                            <div style="font-size:12px; color:var(--color-ink-mute); margin-top:1px;">Konfirmasi pengeluaran kas dan pemotongan saldo kas otomatis.</div>
                        </div>
                    </div>
                    <button type="button" @click="showApproveModal = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px; height:18px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/penggajian/approve') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="penggajian_id" value="<?= $run['id'] ?>">

                    <div class="modal-body custom-scrollbar space-y-4">
                        <!-- Highlight & Breakdown Card -->
                        <div class="p-3.5 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/50 dark:bg-emerald-950/20 space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-semibold text-slate-700 dark:text-slate-300">Total Gaji Bersih (Net Dibayarkan):</span>
                                <span class="font-mono font-bold text-base text-emerald-700 dark:text-emerald-300"><?= Format::rupiah($totalGajiBersih) ?></span>
                            </div>
                            <?php if ($totalPotonganTabunganAll > 0): ?>
                            <div class="flex justify-between items-center text-xs border-t border-dashed border-emerald-200 dark:border-emerald-800/60 pt-1.5">
                                <span class="flex items-center gap-1 text-purple-700 dark:text-purple-300">
                                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                    <span>Transfer ke Kas Tabungan (Escrow):</span>
                                </span>
                                <span class="font-mono font-bold text-purple-700 dark:text-purple-300">+<?= Format::rupiah($totalPotonganTabunganAll) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($totalPenarikanTabunganAll > 0): ?>
                            <div class="flex justify-between items-center text-xs border-t border-dashed border-emerald-200 dark:border-emerald-800/60 pt-1.5">
                                <span class="flex items-center gap-1 text-amber-700 dark:text-amber-300">
                                    <i data-lucide="arrow-left" class="w-3 h-3"></i>
                                    <span>Reimbursement dari Kas Tabungan:</span>
                                </span>
                                <span class="font-mono font-bold text-amber-700 dark:text-amber-300">+<?= Format::rupiah($totalPenarikanTabunganAll) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex justify-between items-center text-[11px] text-slate-400 border-t border-slate-200 dark:border-slate-800 pt-1.5">
                                <span>Karyawan Disertakan:</span>
                                <span class="font-bold text-slate-700 dark:text-slate-300"><?= $includedCount ?> orang <span class="font-normal">(dari <?= count($items) ?> total)</span></span>
                            </div>
                        </div>

                        <!-- Cash Account Selector Cards -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-800 dark:text-slate-100 flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="wallet" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Pilih Sumber Akun Kas Operasional / Payroll</span>
                                    <span class="text-rose-500">*</span>
                                </span>
                                <span class="text-[11px] text-slate-400 font-normal">Wajib bukan akun escrow</span>
                            </label>

                            <input type="hidden" name="akun_kas_id" :value="selectedKasId" required>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-44 overflow-y-auto custom-scrollbar p-0.5">
                                <template x-for="acc in cashAccounts" :key="acc.id">
                                    <div @click="selectedKasId = acc.id"
                                         :class="{
                                             'border-emerald-600 dark:border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/40 dark:bg-emerald-950/20': selectedKasId === acc.id,
                                             'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 hover:border-slate-300 dark:hover:border-slate-600': selectedKasId !== acc.id,
                                             'opacity-60 cursor-not-allowed border-dashed': acc.saldo < (totalPayrollNet + <?= (float)$totalPotonganTabunganAll ?>)
                                         }"
                                         class="relative flex items-center justify-between p-2.5 rounded-lg border transition-all cursor-pointer select-none">
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            <div class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0"
                                                 :class="acc.tipe_akun === 'kas_tunai' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50' : 'bg-blue-50 text-blue-600 dark:bg-blue-950/50'">
                                                <template x-if="acc.tipe_akun === 'kas_tunai'">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                                </template>
                                                <template x-if="acc.tipe_akun !== 'kas_tunai'">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                                                </template>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-1">
                                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate" x-text="acc.nama_akun"></span>
                                                    <span x-show="acc.is_default_pos" class="px-1 py-0.2 text-[9px] font-bold rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300">POS</span>
                                                </div>
                                                <div class="text-[10.5px] font-mono text-slate-500 dark:text-slate-400 mt-0.5">
                                                    Saldo: <span :class="acc.saldo < (totalPayrollNet + <?= (float)$totalPotonganTabunganAll ?>) ? 'text-rose-600 font-bold' : 'text-slate-700 dark:text-slate-200'" x-text="formatRupiah(acc.saldo)"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ml-1.5 flex-shrink-0">
                                            <div class="w-3.5 h-3.5 rounded-full border flex items-center justify-center"
                                                 :class="selectedKasId === acc.id ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-300 dark:border-slate-600'">
                                                <svg x-show="selectedKasId === acc.id" class="w-2 h-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <p x-show="selectedKasId && kasBalances[selectedKasId] < (totalPayrollNet + <?= (float)$totalPotonganTabunganAll ?>)" x-cloak class="text-[11.5px] text-rose-600 font-bold flex items-center gap-1 mt-1">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                <span>Saldo akun kas tidak mencukupi untuk pembayaran payroll &amp; setoran tabungan.</span>
                            </p>
                        </div>

                        <!-- Checkpoint List -->
                        <div style="padding:10px 14px; background:var(--color-canvas-soft); border:1px solid var(--color-hairline); border-radius:var(--rounded-md); font-size:11.5px; color:var(--color-ink-mute); line-height:1.5;">
                            <div style="font-weight:700; color:var(--color-ink); margin-bottom:4px;">Dampak Otorisasi:</div>
                            <ul style="list-style-type:disc; padding-left:16px; margin:0;" class="space-y-1">
                                <li>Cicilan kasbon terpotong otomatis dari saldo pinjaman karyawan.</li>
                                <li>Tabungan karyawan bertambah atau dicairkan sesuai rincian payroll.</li>
                                <li>Slip gaji resmi diterbitkan dan siap dibagikan ke karyawan.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="showApproveModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                        <button type="submit" 
                                class="btn btn-primary w-full sm:w-auto transition-all" 
                                style="background:#059669; border-color:#047857; display:inline-flex; align-items:center; justify-content:center; gap:6px;"
                                :disabled="selectedKasId && kasBalances[selectedKasId] < totalPayrollNet"
                                :class="{'opacity-50 cursor-not-allowed': selectedKasId && kasBalances[selectedKasId] < totalPayrollNet}">
                            <i data-lucide="check-circle" style="width:16px; height:16px;"></i>
                            <span x-text="(selectedKasId && kasBalances[selectedKasId] < totalPayrollNet) ? 'Saldo Tidak Cukup' : 'Setujui &amp; Bayar Sekarang'">Setujui &amp; Bayar Sekarang</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL 3: HAPUS DRAF -->
    <template x-teleport="body">
        <div x-show="showDeleteModal" x-cloak class="modal-backdrop" @click="showDeleteModal = false">
            <div class="modal-box modal-box-md" style="max-width: 460px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(239,68,68,0.12); color:#dc2626; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i data-lucide="trash-2" style="width:20px; height:20px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title">Hapus Draf Penggajian</div>
                            <div style="font-size:12px; color:var(--color-ink-mute); margin-top:1px;">Membatalkan seluruh lembar draf payroll ini.</div>
                        </div>
                    </div>
                    <button type="button" @click="showDeleteModal = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px; height:18px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/penggajian/delete') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="penggajian_id" value="<?= $run['id'] ?>">

                    <div class="modal-body custom-scrollbar space-y-3">
                        <div style="padding:12px 14px; background:#fef2f2; border:1px solid #fecaca; border-radius:var(--rounded-md); font-size:12.5px; color:#991b1b; line-height:1.5;">
                            Apakah Anda yakin ingin menghapus draf penggajian <strong><?= htmlspecialchars($run['nomor_referensi']) ?></strong>? Seluruh data kehadiran, produksi harian, dan penarikan gaji pada periode ini akan dibuka kembali (unlocked).
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="showDeleteModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                        <button type="submit" class="btn btn-danger btn-solid w-full sm:w-auto" style="display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                            <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                            <span>Ya, Hapus Draf</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL 4: BATALKAN APPROVAL (24 JAM) -->
    <template x-teleport="body">
        <div x-show="showCancelApproveModal" x-cloak class="modal-backdrop" @click="showCancelApproveModal = false">
            <div class="modal-box modal-box-md" style="max-width: 480px;" @click.stop>
                <div class="modal-handle"><div class="modal-handle-bar"></div></div>
                <div class="modal-header">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(245,158,11,0.12); color:#d97706; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i data-lucide="rotate-ccw" style="width:20px; height:20px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="modal-title">Batalkan Persetujuan Payroll</div>
                            <div style="font-size:12px; color:var(--color-ink-mute); margin-top:1px;">Fitur darurat rollback dalam batas waktu 24 jam.</div>
                        </div>
                    </div>
                    <button type="button" @click="showCancelApproveModal = false" class="modal-close-x" title="Tutup Modal">
                        <i data-lucide="x" style="width:18px; height:18px;"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/penggajian/cancel-approve') ?>" method="POST">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="penggajian_id" value="<?= $run['id'] ?>">

                    <div class="modal-body custom-scrollbar space-y-3">
                        <div style="padding:12px 14px; background:#fffbeb; border:1px solid #fde68a; border-radius:var(--rounded-md); font-size:12.5px; color:#92400e; line-height:1.5;">
                            Pembatalan akan me-rollback transaksi arus kas, memulihkan saldo akun kas terpilih, mengembalikan cicilan pinjaman kasbon dan tabungan, serta mengembalikan status payroll menjadi <strong>DRAF</strong>.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="showCancelApproveModal = false" class="btn btn-secondary modal-btn-cancel-desktop">Batal</button>
                        <button type="submit" class="btn w-full sm:w-auto" style="background:#d97706; color:#ffffff; border:1px solid #b45309; display:inline-flex; align-items:center; justify-content:center; gap:6px; font-weight:700;">
                            <i data-lucide="rotate-ccw" style="width:16px; height:16px;"></i>
                            <span>Ya, Batalkan Approval</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>

<script>
function payrollPreviewApp() {
    return {
        searchQuery: '',
        statusFilter: 'all',
        showApproveModal: false,
        showEditModal: false,
        showDeleteModal: false,
        showCancelApproveModal: false,
        selectedKasId: '<?= !empty($akunKasList[0]['id']) ? (string)$akunKasList[0]['id'] : '' ?>',
        totalPayrollNet: <?= (float)$totalGajiBersih ?>,
        cashAccounts: <?= json_encode(array_map(function($a) {
            return [
                'id' => (string)$a['id'],
                'nama_akun' => (string)$a['nama_akun'],
                'tipe_akun' => (string)$a['tipe_akun'],
                'saldo' => (float)$a['saldo_saat_ini'],
                'is_default_pos' => (bool)($a['is_default_pos'] ?? false),
            ];
        }, $akunKasList ?? []), JSON_UNESCAPED_UNICODE) ?>,
        formatRupiah(val) {
            return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
        },
        kasBalances: {
            <?php foreach ($akunKasList as $ak): ?>
            "<?= $ak['id'] ?>": <?= (float)$ak['saldo_saat_ini'] ?>,
            <?php endforeach; ?>
        },
        selectedItem: null,
        itemsList: <?= json_encode(array_map(function($item) {
            $details = json_decode((string)$item['rincian_json'], true) ?: [];
            $isExc = (bool)$item['is_excluded'];
            $baseIncome = ($item['tipe_penggajian'] === 'bulanan') ? (float)$item['gaji_pokok'] : (float)$item['total_upah_borongan'];
            $lemburPlusKomisi = (float)$item['total_upah_lembur'] + (float)$item['total_komisi_sales'];
            $tunjanganTotal = (float)$item['tunjangan_bulanan'] + (float)$item['tunjangan_lain'] + (float)$item['penarikan_tabungan'];
            $potonganTotal = (float)$item['total_potongan_kasbon'] + (float)$item['potongan_lain'] + (float)$item['total_potongan_tabungan'] + (float)$item['total_penarikan_gaji'];
            $gajiBersih = (float)$item['gaji_bersih_diterima'];

            return [
                'id' => $item['id'],
                'karyawan_id' => $item['karyawan_id'],
                'nama_karyawan' => $item['nama_karyawan'],
                'posisi' => $item['posisi'] ?? '-',
                'tipe_penggajian' => $item['tipe_penggajian'],
                'is_excluded' => $isExc,
                'kasbon_adjusted' => $details['kasbon_adjusted_down'] ?? false,
                'hari_hadir' => (int)$item['hari_hadir'],
                'base_income' => $baseIncome,
                'base_income_fmt' => Format::rupiah($baseIncome),
                'uang_hadir' => (float)$item['total_uang_kehadiran'],
                'uang_hadir_fmt' => Format::rupiah((float)$item['total_uang_kehadiran']),
                'lembur_komisi' => $lemburPlusKomisi,
                'lembur_komisi_fmt' => Format::rupiah($lemburPlusKomisi),
                'tunjangan_total' => $tunjanganTotal,
                'tunjangan_total_fmt' => Format::rupiah($tunjanganTotal),
                'potongan_total' => $potonganTotal,
                'potongan_total_fmt' => Format::rupiah($potonganTotal),
                'gaji_bersih' => $gajiBersih,
                'gaji_bersih_fmt' => Format::rupiah($gajiBersih),
                // Data untuk modal edit
                'tunjangan_lain' => (int)($item['tunjangan_lain'] ?? 0),
                'catatan_tunjangan_lain' => $item['catatan_tunjangan_lain'] ?? '',
                'potongan_lain' => (int)($item['potongan_lain'] ?? 0),
                'catatan_potongan_lain' => $item['catatan_potongan_lain'] ?? '',
                'total_potongan_kasbon' => (int)($item['total_potongan_kasbon'] ?? 0),
                'total_potongan_tabungan' => (int)($item['total_potongan_tabungan'] ?? 0),
                'penarikan_tabungan' => (int)($item['penarikan_tabungan'] ?? 0),
                'nominal_pembulatan' => (int)($item['nominal_pembulatan'] ?? 0),
                'max_kasbon_aktif' => (int)($item['max_kasbon_aktif'] ?? 0),
                'saldo_tabungan_saat_ini' => (int)($item['saldo_tabungan_saat_ini'] ?? 0)
            ];
        }, $items), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,

        editForm: {
            item_id: '',
            karyawan_nama: '',
            tunjangan_lain: 0,
            catatan_tunjangan_lain: '',
            potongan_lain: 0,
            catatan_potongan_lain: '',
            total_potongan_kasbon: 0,
            total_potongan_tabungan: 0,
            penarikan_tabungan: 0,
            nominal_pembulatan: 0,
            max_kasbon: 0,
            saldo_tabungan: 0
        },

        init() {
            this.$nextTick(() => {
                if (typeof window.refreshIcons === 'function') {
                    window.refreshIcons();
                } else if (window.lucide && typeof lucide.createIcons === 'function') {
                    lucide.createIcons();
                }
            });

            this.$watch('statusFilter', () => {
                this.$nextTick(() => {
                    if (typeof window.refreshIcons === 'function') window.refreshIcons();
                });
            });

            this.$watch('searchQuery', () => {
                this.$nextTick(() => {
                    if (typeof window.refreshIcons === 'function') window.refreshIcons();
                });
            });
        },

        openEdit(item) {
            this.selectedItem = item;
            this.editForm = {
                item_id: item.id,
                karyawan_nama: item.nama_karyawan,
                tunjangan_lain: parseInt(item.tunjangan_lain || 0),
                catatan_tunjangan_lain: item.catatan_tunjangan_lain || '',
                potongan_lain: parseInt(item.potongan_lain || 0),
                catatan_potongan_lain: item.catatan_potongan_lain || '',
                total_potongan_kasbon: parseInt(item.total_potongan_kasbon || 0),
                total_potongan_tabungan: parseInt(item.total_potongan_tabungan || 0),
                penarikan_tabungan: parseInt(item.penarikan_tabungan || 0),
                nominal_pembulatan: parseInt(item.nominal_pembulatan || 0),
                max_kasbon: parseInt(item.max_kasbon_aktif || 0),
                saldo_tabungan: parseInt(item.saldo_tabungan_saat_ini || 0)
            };
            this.showEditModal = true;
            this.$nextTick(() => {
                if (typeof window.refreshIcons === 'function') window.refreshIcons();
            });
        },

        get filteredItems() {
            const q = (this.searchQuery || '').toLowerCase().trim();
            return this.itemsList.filter(item => {
                if (this.statusFilter === 'borongan' && item.tipe_penggajian !== 'borongan' && item.tipe_penggajian !== 'mingguan') return false;
                if (this.statusFilter === 'bulanan' && item.tipe_penggajian !== 'bulanan') return false;
                if (this.statusFilter === 'excluded' && !item.is_excluded) return false;

                if (q) {
                    const name = (item.nama_karyawan || '').toLowerCase();
                    const pos = (item.posisi || '').toLowerCase();
                    if (!name.includes(q) && !pos.includes(q)) return false;
                }
                return true;
            });
        },

        get boronganCount() {
            return this.itemsList.filter(i => i.tipe_penggajian === 'borongan' || i.tipe_penggajian === 'mingguan').length;
        },
        get bulananCount() {
            return this.itemsList.filter(i => i.tipe_penggajian === 'bulanan').length;
        },
        get excludedCount() {
            return this.itemsList.filter(i => i.is_excluded).length;
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

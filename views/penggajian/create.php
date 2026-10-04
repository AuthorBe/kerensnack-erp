<?php
/**
 * views/penggajian/create.php
 * Form Setup Generate Payroll Baru Keren One ERP
 * Mengikuti Pola Desain Kanonikal DESAIN.md (Zero Custom CSS, Modern Enterprise)
 */
use App\Helpers\CSRF;
use App\Helpers\Format;
use App\Core\Router;

ob_start();
?>

<div class="space-y-5 pb-24"
     x-data="{
        activeTab: <?= htmlspecialchars(json_encode($isEdit ? ((!empty($selectedBulananIds) && empty($selectedBoronganIds)) ? 'bulanan' : 'borongan') : 'borongan'), ENT_QUOTES, 'UTF-8') ?>,
        selectedBorongan: <?= htmlspecialchars(json_encode($isEdit ? ($selectedBoronganIds ?? []) : array_column($karyawanBorongan, 'id')), ENT_QUOTES, 'UTF-8') ?>,
        selectedBulanan: <?= htmlspecialchars(json_encode($isEdit ? ($selectedBulananIds ?? []) : array_column($karyawanBulanan, 'id')), ENT_QUOTES, 'UTF-8') ?>,
        includeMonthlyBase: true,
        boronganList: <?= htmlspecialchars(json_encode(array_column($karyawanBorongan, 'id')), ENT_QUOTES, 'UTF-8') ?>,
        bulananList: <?= htmlspecialchars(json_encode(array_column($karyawanBulanan, 'id')), ENT_QUOTES, 'UTF-8') ?>,
        
        periodeAwalBorongan: <?= htmlspecialchars(json_encode($mondayLastWeek), ENT_QUOTES, 'UTF-8') ?>,
        periodeAkhirBorongan: <?= htmlspecialchars(json_encode($sundayLastWeek), ENT_QUOTES, 'UTF-8') ?>,
        periodeAwalBulanan: <?= htmlspecialchars(json_encode($firstDayMonth), ENT_QUOTES, 'UTF-8') ?>,
        periodeAkhirBulanan: <?= htmlspecialchars(json_encode($lastDayMonth), ENT_QUOTES, 'UTF-8') ?>,
        payrollName: <?= htmlspecialchars(json_encode($initialPayrollName ?? ''), ENT_QUOTES, 'UTF-8') ?>,
        isEditMode: <?= $isEdit ? 'true' : 'false' ?>,

        get generatedSuggestion() {
            let type = 'Borongan';
            let start = this.periodeAwalBorongan;
            let end = this.periodeAkhirBorongan;
            
            if (this.selectedBorongan.length > 0 && this.selectedBulanan.length > 0) {
                type = 'Gabungan (Borongan & Bulanan)';
            } else if (this.selectedBulanan.length > 0 && this.selectedBorongan.length === 0) {
                type = 'Bulanan';
                start = this.periodeAwalBulanan;
                end = this.periodeAkhirBulanan;
            }

            if (!start || !end) return `Payroll ${type}`;
            
            const fmt = (d) => {
                const parts = d.split('-');
                if(parts.length !== 3) return d;
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
                return `${parts[2]} ${months[parseInt(parts[1])-1]} ${parts[0]}`;
            };
            
            return `Payroll ${type} Periode ${fmt(start)} s/d ${fmt(end)}`;
        },

        toggleSelectAllBorongan() {
            if (this.selectedBorongan.length === this.boronganList.length) {
                this.selectedBorongan = [];
            } else {
                this.selectedBorongan = [...this.boronganList];
            }
        },
        toggleSelectAllBulanan() {
            if (this.selectedBulanan.length === this.bulananList.length) {
                this.selectedBulanan = [];
            } else {
                this.selectedBulanan = [...this.bulananList];
            }
        },
        toggleBorongan(id) {
            const idx = this.selectedBorongan.indexOf(id);
            if (idx > -1) {
                this.selectedBorongan.splice(idx, 1);
            } else {
                this.selectedBorongan.push(id);
            }
        },
        toggleBulanan(id) {
            const idx = this.selectedBulanan.indexOf(id);
            if (idx > -1) {
                this.selectedBulanan.splice(idx, 1);
            } else {
                this.selectedBulanan.push(id);
            }
        },
        get totalSelected() {
            return this.selectedBorongan.length + this.selectedBulanan.length;
        },
        get detectedTypeBadge() {
            if (this.selectedBorongan.length > 0 && this.selectedBulanan.length > 0) {
                return { label: 'Payroll Gabungan', class: 'badge-primary' };
            }
            if (this.selectedBulanan.length > 0) {
                return { label: 'Payroll Bulanan', class: 'badge-info' };
            }
            if (this.selectedBorongan.length > 0) {
                return { label: 'Payroll Borongan', class: 'badge-secondary' };
            }
            return { label: 'Belum Ada Pilihan', class: 'badge-secondary' };
        },
        setBoronganPreset(mode) {
            const pad = (n) => String(n).padStart(2, '0');
            const fmt = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            const today = new Date();
            
            if (mode === 'last_week') {
                const day = today.getDay();
                const diffToLastMonday = (day === 0 ? 6 : day - 1) + 7;
                const lastMon = new Date(today);
                lastMon.setDate(today.getDate() - diffToLastMonday);
                const lastSun = new Date(lastMon);
                lastSun.setDate(lastMon.getDate() + 6);
                this.periodeAwalBorongan = fmt(lastMon);
                this.periodeAkhirBorongan = fmt(lastSun);
            } else if (mode === '7d') {
                const start = new Date(today);
                start.setDate(today.getDate() - 6);
                this.periodeAwalBorongan = fmt(start);
                this.periodeAkhirBorongan = fmt(today);
            }
        },
        setBulananPreset(mode) {
            const pad = (n) => String(n).padStart(2, '0');
            const fmt = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            const today = new Date();
            
            if (mode === 'this_month') {
                const start = new Date(today.getFullYear(), today.getMonth(), 1);
                const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                this.periodeAwalBulanan = fmt(start);
                this.periodeAkhirBulanan = fmt(end);
            } else if (mode === 'last_month') {
                const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const end = new Date(today.getFullYear(), today.getMonth(), 0);
                this.periodeAwalBulanan = fmt(start);
                this.periodeAkhirBulanan = fmt(end);
            }
        }
     }">

    <!-- 1. PAGE HEADER (Pola Kanonikal dari /owner) -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon <?= $isEdit ? 'is-blue' : 'is-emerald' ?>">
                <i data-lucide="<?= $isEdit ? 'refresh-cw' : 'calculator' ?>"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag" style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
                    <span style="display:inline-flex; align-items:center; gap:6px;">
                        <span class="tag-dot" style="background-color:<?= $isEdit ? '#2563eb' : 'var(--color-success)' ?>;"></span>
                        <span>Modul HR &bull; Sistem Penggajian</span>
                    </span>
                    <?php if ($isEdit && $editRun): ?>
                        <span class="badge badge-mono font-mono" style="white-space:nowrap;"><?= htmlspecialchars($editRun['nomor_referensi']) ?></span>
                    <?php endif; ?>
                </div>
                <h1 class="page-title"><?= $isEdit ? 'Regenerasi &amp; Edit Parameter Draf' : 'Setup Draft Payroll' ?></h1>
                <p class="page-subtitle" style="color:var(--color-slate-500);">
                    <?= $isEdit 
                        ? 'Sesuaikan rentang periode dan daftar karyawan draf <strong>' . htmlspecialchars($editRun['nomor_referensi']) . '</strong> sebelum menghitung ulang.' 
                        : 'Konfigurasi periode dan pilih karyawan untuk diproses pada siklus ini.' ?>
                </p>
            </div>
        </div>

        <div class="page-header-actions w-full sm:w-auto mt-3 sm:mt-0" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <?php if ($isEdit && $editRun): ?>
                <a href="<?= Router::url('/penggajian/preview?id=' . $editRun['id']) ?>" class="btn btn-secondary btn-sm w-full sm:w-auto justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;">
                    <i data-lucide="arrow-left" style="width:14px;height:14px;"></i>
                    <span>Batal &amp; Kembali ke Preview</span>
                </a>
            <?php else: ?>
                <a href="<?= Router::url('/penggajian') ?>" class="btn btn-secondary btn-sm w-full sm:w-auto justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;">
                    <i data-lucide="arrow-left" style="width:14px;height:14px;"></i>
                    <span>Kembali ke Daftar</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. NOTIFIKASI / BANNER MODE -->
    <?php if (!$isEdit && !empty($draftRun)): ?>
        <div class="card p-4" style="background:rgba(245, 158, 11, 0.08); border-color:rgba(245, 158, 11, 0.3); display:flex; gap:12px; align-items:flex-start;">
            <i data-lucide="alert-triangle" style="width:20px;height:20px;color:#d97706;flex-shrink:0;margin-top:2px;"></i>
            <div style="font-size:13px; color:#92400e; flex:1;">
                <strong style="font-weight:800;">Perhatian:</strong> Terdapat draf payroll yang sedang aktif (<strong><?= htmlspecialchars($draftRun['nomor_referensi']) ?> - <?= htmlspecialchars($draftRun['nama_payroll']) ?></strong>). Anda dapat membuka draf tersebut atau mengedit parameter perhitungannya.
                <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                    <a href="<?= Router::url('/penggajian/preview?id=' . $draftRun['id']) ?>" class="btn btn-primary btn-sm" style="height:28px; font-size:11px; padding:0 12px; gap:4px;">
                        <span>Buka Draf Aktif</span>
                        <i data-lucide="arrow-right" style="width:12px;height:12px;"></i>
                    </a>
                    <a href="<?= Router::url('/penggajian/create?edit_id=' . $draftRun['id']) ?>" class="btn btn-secondary btn-sm" style="height:28px; font-size:11px; padding:0 12px; gap:4px; color:#2563eb;">
                        <i data-lucide="refresh-cw" style="width:12px;height:12px;"></i>
                        <span>Edit / Regenerasi Draf</span>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 3. FORM UTAMA -->
    <form action="<?= Router::url('/penggajian/generate') ?>" method="POST" id="payrollGenerateForm">
        <?= CSRF::field() ?>
        <?php if ($isEdit && $editRun): ?>
            <input type="hidden" name="edit_run_id" value="<?= htmlspecialchars($editRun['id']) ?>">
        <?php endif; ?>
        <input type="hidden" name="include_monthly_base" :value="includeMonthlyBase ? '1' : '0'">

        <!-- TAB SWITCHER GAYA ENTERPRISE CLEAN (NO NEON) -->
        <div class="card p-3 sm:p-3.5 mb-5" style="background:var(--color-canvas); border:1px solid var(--color-hairline); box-shadow:var(--shadow-1); border-radius:12px;">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-xs font-bold" style="color:var(--color-ink);">
                    <i data-lucide="layers" style="width:15px;height:15px;color:#64748b;"></i>
                    <span style="margin-right:6px;">Tipe Karyawan:</span>
                    <span class="badge text-[10px] font-bold" :class="detectedTypeBadge.class" x-text="detectedTypeBadge.label" style="padding:2px 8px; border-radius:9999px;"></span>
                </div>

                <!-- Clean Segmented Tab Control -->
                <div class="p-1 rounded-xl flex items-center gap-1.5" style="background:rgba(0,0,0,0.04); border:1px solid var(--color-hairline);">
                    <!-- Tab Borongan -->
                    <button type="button"
                            @click="activeTab = 'borongan'"
                            class="transition-all duration-150"
                            :style="activeTab === 'borongan' 
                                ? 'background:#ffffff; color:#0f172a; font-weight:700; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.08); padding:6px 12px; font-size:12px; display:inline-flex; align-items:center; gap:8px; cursor:pointer; border:1px solid rgba(0,0,0,0.06);' 
                                : 'background:transparent; color:#64748b; font-weight:600; border-radius:8px; padding:6px 12px; font-size:12px; display:inline-flex; align-items:center; gap:8px; cursor:pointer; border:1px solid transparent;'">
                        <i data-lucide="package" style="width:14px;height:14px;" :style="activeTab === 'borongan' ? 'color:#059669;' : 'color:#94a3b8;'"></i>
                        <span>Borongan</span>
                        <span :style="activeTab === 'borongan' 
                                ? 'padding:2px 8px; border-radius:9999px; font-size:11px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; line-height:1; background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; font-feature-settings:\'tnum\';' 
                                : 'padding:2px 8px; border-radius:9999px; font-size:11px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; line-height:1; background:rgba(0,0,0,0.06); color:#64748b; border:1px solid transparent; font-feature-settings:\'tnum\';'"
                              x-text="`${selectedBorongan.length}/${boronganList.length}`"></span>
                    </button>

                    <!-- Tab Bulanan -->
                    <button type="button"
                            @click="activeTab = 'bulanan'"
                            class="transition-all duration-150"
                            :style="activeTab === 'bulanan' 
                                ? 'background:#ffffff; color:#0f172a; font-weight:700; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.08); padding:6px 12px; font-size:12px; display:inline-flex; align-items:center; gap:8px; cursor:pointer; border:1px solid rgba(0,0,0,0.06);' 
                                : 'background:transparent; color:#64748b; font-weight:600; border-radius:8px; padding:6px 12px; font-size:12px; display:inline-flex; align-items:center; gap:8px; cursor:pointer; border:1px solid transparent;'">
                        <i data-lucide="calendar" style="width:14px;height:14px;" :style="activeTab === 'bulanan' ? 'color:#0284c7;' : 'color:#94a3b8;'"></i>
                        <span>Bulanan</span>
                        <span :style="activeTab === 'bulanan' 
                                ? 'padding:2px 8px; border-radius:9999px; font-size:11px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; line-height:1; background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; font-feature-settings:\'tnum\';' 
                                : 'padding:2px 8px; border-radius:9999px; font-size:11px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; line-height:1; background:rgba(0,0,0,0.06); color:#64748b; border:1px solid transparent; font-feature-settings:\'tnum\';'"
                              x-text="`${selectedBulanan.length}/${bulananList.length}`"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- KONTEN TAB -->
        <div>

            <!-- ============================================================
                 TAB 1: BORONGAN
                 ============================================================ -->
            <div x-show="activeTab === 'borongan'" x-cloak class="space-y-5">
                
                <!-- Periode Card (Clean, No Neon Bar) -->
                <div class="card p-4 sm:p-5" style="border:1px solid var(--color-hairline); border-radius:12px;">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar-clock" style="width:18px;height:18px;color:#64748b;"></i>
                            <h3 style="font-size:14px;font-weight:700;color:var(--color-ink);">Rentang Periode Borongan (Mingguan)</h3>
                        </div>
                        <div style="display:flex; gap:6px;">
                            <button type="button" @click="setBoronganPreset('last_week')" class="btn btn-secondary btn-sm" style="font-size:11px; height:28px; border-radius:6px;">
                                Senin-Minggu Lalu
                            </button>
                            <button type="button" @click="setBoronganPreset('7d')" class="btn btn-secondary btn-sm" style="font-size:11px; height:28px; border-radius:6px;">
                                7 Hari Terakhir
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="periode_awal_borongan" class="form-label" style="font-size:12px; margin-bottom:6px;">Tanggal Mulai Borongan <span style="color:var(--color-danger);">*</span></label>
                            <input type="date" id="periode_awal_borongan" name="periode_awal_borongan" x-model="periodeAwalBorongan" class="form-input" required>
                        </div>
                        <div>
                            <label for="periode_akhir_borongan" class="form-label" style="font-size:12px; margin-bottom:6px;">Tanggal Selesai Borongan <span style="color:var(--color-danger);">*</span></label>
                            <input type="date" id="periode_akhir_borongan" name="periode_akhir_borongan" x-model="periodeAkhirBorongan" class="form-input" required>
                        </div>
                    </div>
                </div>

                <!-- Employee List Container (Desktop Table + Mobile Cards) -->
                <div class="card" style="padding:0; overflow:hidden; border:1px solid var(--color-hairline); border-radius:12px;">
                    <!-- Toolbar Header -->
                    <div class="flex items-center justify-between p-3.5 sm:p-4 border-b" style="border-color:var(--color-hairline); background-color:var(--color-canvas);">
                        <div class="flex items-center gap-2">
                            <i data-lucide="users" style="width:18px; height:18px; color:#059669;"></i>
                            <span style="font-weight:700; color:var(--color-ink); font-size:13.5px;">Master Borongan</span>
                            <span style="font-size:11px; padding:2px 8px; border-radius:9999px; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-weight:700;" x-text="`${selectedBorongan.length} Terpilih`"></span>
                        </div>
                        <div>
                            <button type="button" @click="toggleSelectAllBorongan()" class="btn btn-secondary btn-sm" style="height:32px; font-size:11.5px; border-radius:8px; display:inline-flex; align-items:center; gap:6px;">
                                <i data-lucide="check-square" style="width:14px; height:14px;"></i>
                                <span x-text="selectedBorongan.length === boronganList.length ? 'Kosongkan' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                    </div>

                    <?php if (empty($karyawanBorongan)): ?>
                        <div style="padding:32px; text-align:center; color:var(--color-slate-400);">Tidak ada master karyawan borongan.</div>
                    <?php else: ?>

                        <!-- 1. DESKTOP TABLE VIEW (Layar Komputer / Tablet) -->
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="w-full text-left" style="font-size:13px;">
                                <thead style="background:var(--color-slate-50); border-bottom:1px solid var(--color-hairline);">
                                    <tr>
                                        <th style="padding:12px 16px; width:48px; text-align:center;">
                                            <input type="checkbox" :checked="selectedBorongan.length === boronganList.length && boronganList.length > 0" @change="toggleSelectAllBorongan()" style="width:16px;height:16px;border-radius:4px;cursor:pointer;">
                                        </th>
                                        <th style="padding:12px 16px; font-weight:700; color:var(--color-slate-600);">Nama Karyawan</th>
                                        <th style="padding:12px 16px; font-weight:700; color:var(--color-slate-600);">Posisi / Divisi</th>
                                        <th style="padding:12px 16px; font-weight:700; color:var(--color-slate-600); text-align:right;">Uang Hadir / Hari</th>
                                        <th style="padding:12px 16px; font-weight:700; color:var(--color-slate-600); text-align:center;">Tipe Upah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($karyawanBorongan as $emp): ?>
                                    <tr @click="toggleBorongan('<?= $emp['id'] ?>')" 
                                        class="transition-colors border-b"
                                        style="border-color:var(--color-hairline); cursor:pointer;"
                                        :style="selectedBorongan.includes('<?= $emp['id'] ?>') ? 'background:rgba(16, 185, 129, 0.04);' : ''">
                                        <td style="padding:12px 16px; text-align:center;" @click.stop>
                                            <input type="checkbox" name="karyawan_borongan_ids[]" value="<?= htmlspecialchars($emp['id']) ?>" x-model="selectedBorongan" style="width:16px;height:16px;border-radius:4px;cursor:pointer;">
                                        </td>
                                        <td style="padding:12px 16px;">
                                            <div class="flex items-center gap-2.5">
                                                <div style="width:30px; height:30px; border-radius:8px; background:#d1fae5; color:#065f46; font-weight:800; font-size:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1px solid #a7f3d0;">
                                                    <?= strtoupper(substr($emp['nama_karyawan'] ?? 'K', 0, 2)) ?>
                                                </div>
                                                <div style="font-weight:700; color:var(--color-ink);"><?= htmlspecialchars($emp['nama_karyawan']) ?></div>
                                            </div>
                                        </td>
                                        <td style="padding:12px 16px; color:var(--color-slate-600);">
                                            <span class="badge badge-secondary" style="font-size:11px; padding:2px 8px; border-radius:6px;"><?= htmlspecialchars($emp['posisi'] ?? 'Produksi Borongan') ?></span>
                                        </td>
                                        <td style="padding:12px 16px; text-align:right; font-weight:700; color:var(--color-ink); font-feature-settings:'tnum';">
                                            <?= (float)$emp['uang_kehadiran_harian'] > 0 ? Format::rupiah((float)$emp['uang_kehadiran_harian']) : '-' ?>
                                        </td>
                                        <td style="padding:12px 16px; text-align:center;">
                                            <span style="font-size:10.5px; padding:2px 8px; border-radius:9999px; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-weight:700; display:inline-block;">Hasil / Pcs</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- 2. MOBILE CARDS VIEW (Khusus Layar HP / Layar Kecil) -->
                        <div class="block sm:hidden p-3 space-y-2.5" style="background:var(--color-slate-50);">
                            <?php foreach ($karyawanBorongan as $emp): ?>
                            <div @click="toggleBorongan('<?= $emp['id'] ?>')"
                                 class="p-3.5 rounded-xl border transition-all select-none cursor-pointer flex flex-col gap-2.5"
                                 :style="selectedBorongan.includes('<?= $emp['id'] ?>') 
                                    ? 'background:#ffffff; border:1.5px solid #10b981; box-shadow:0 2px 6px rgba(16, 185, 129, 0.12);' 
                                    : 'background:#ffffff; border:1px solid var(--color-hairline);'">
                               
                                <!-- Top Row: Checkbox + Avatar + Nama + Tipe -->
                                <div class="flex items-center justify-between gap-2.5">
                                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                        <div class="w-5 h-5 rounded-md border flex items-center justify-center flex-shrink-0 transition-colors"
                                             :style="selectedBorongan.includes('<?= $emp['id'] ?>') ? 'background:#10b981; border-color:#059669; color:#ffffff;' : 'background:#ffffff; border-color:#cbd5e1;'">
                                            <i data-lucide="check" style="width:13px;height:13px;" x-show="selectedBorongan.includes('<?= $emp['id'] ?>')"></i>
                                        </div>

                                        <div style="width:30px; height:30px; border-radius:8px; background:#d1fae5; color:#065f46; font-weight:800; font-size:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1px solid #a7f3d0;">
                                            <?= strtoupper(substr($emp['nama_karyawan'] ?? 'K', 0, 2)) ?>
                                        </div>
                                        
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate"><?= htmlspecialchars($emp['nama_karyawan']) ?></div>
                                            <div class="text-[10.5px] text-slate-500 truncate"><?= htmlspecialchars($emp['posisi'] ?? 'Produksi Borongan') ?></div>
                                        </div>
                                    </div>
                                    
                                    <span style="font-size:10px; padding:2px 8px; border-radius:9999px; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-weight:700;">Hasil Pcs</span>
                                </div>

                                <!-- Bottom Row: Uang Hadir / Hari -->
                                <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px]">
                                    <span class="text-slate-500 font-medium">Uang Hadir / Hari:</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200" style="font-feature-settings:'tnum';">
                                        <?= (float)$emp['uang_kehadiran_harian'] > 0 ? Format::rupiah((float)$emp['uang_kehadiran_harian']) : '<span class="text-slate-400 font-normal">-</span>' ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>
                </div>
            </div>


            <!-- ============================================================
                 TAB 2: BULANAN
                 ============================================================ -->
            <div x-show="activeTab === 'bulanan'" x-cloak class="space-y-5">
                
                <!-- Periode Card (Clean, No Neon Bar) -->
                <div class="card p-4 sm:p-5" style="border:1px solid var(--color-hairline); border-radius:12px;">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar-range" style="width:18px;height:18px;color:#64748b;"></i>
                            <h3 style="font-size:14px;font-weight:700;color:var(--color-ink);">Rentang Periode Bulanan</h3>
                        </div>
                        <div style="display:flex; gap:6px;">
                            <button type="button" @click="setBulananPreset('this_month')" class="btn btn-secondary btn-sm" style="font-size:11px; height:28px; border-radius:6px;">
                                Bulan Ini
                            </button>
                            <button type="button" @click="setBulananPreset('last_month')" class="btn btn-secondary btn-sm" style="font-size:11px; height:28px; border-radius:6px;">
                                Bulan Lalu
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="periode_awal_bulanan" class="form-label" style="font-size:12px; margin-bottom:6px;">Tanggal Mulai <span style="color:var(--color-danger);">*</span></label>
                            <input type="date" id="periode_awal_bulanan" name="periode_awal_bulanan" x-model="periodeAwalBulanan" class="form-input" required>
                        </div>
                        <div>
                            <label for="periode_akhir_bulanan" class="form-label" style="font-size:12px; margin-bottom:6px;">Tanggal Selesai <span style="color:var(--color-danger);">*</span></label>
                            <input type="date" id="periode_akhir_bulanan" name="periode_akhir_bulanan" x-model="periodeAkhirBulanan" class="form-input" required>
                        </div>
                    </div>
                </div>

                <!-- Toggle Switch Base Salary (Ultra Clean & Minimalist, No Badge) -->
                <div @click="includeMonthlyBase = !includeMonthlyBase"
                     role="button"
                     tabindex="0"
                     class="card p-3.5 sm:p-4 transition-all duration-200 select-none cursor-pointer"
                     :style="includeMonthlyBase 
                        ? 'background:rgba(16, 185, 129, 0.04); border:1.5px solid #a7f3d0; border-radius:14px;' 
                        : 'background:var(--color-canvas); border:1.5px solid var(--color-hairline); border-radius:14px;'">
                    
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:14px;">
                        
                        <!-- Header / Info Sisi Kiri (Clean, Tanpa Badge) -->
                        <div style="display:flex; align-items:center; gap:12px; min-width:0; flex:1;">
                            <!-- Icon Wallet -->
                            <div class="flex-shrink-0"
                                 :style="includeMonthlyBase 
                                    ? 'width:38px; height:38px; border-radius:10px; background:#dcfce7; color:#10b981; display:flex; align-items:center; justify-content:center; flex-shrink:0;' 
                                    : 'width:38px; height:38px; border-radius:10px; background:#f1f5f9; color:#64748b; display:flex; align-items:center; justify-content:center; flex-shrink:0;'">
                                <i data-lucide="wallet" style="width:18px; height:18px;"></i>
                            </div>

                            <!-- Text Info -->
                            <div style="min-width:0; flex:1;">
                                <div style="font-size:13.5px; font-weight:700; color:var(--color-ink); margin-bottom:2px;">
                                    Cairkan Gaji Pokok &amp; Tunjangan Bulanan
                                </div>
                                <div style="font-size:11.5px; color:var(--color-slate-500); line-height:1.4;">
                                    <span x-show="includeMonthlyBase" style="color:#059669; font-weight:500;">Gaji pokok &amp; tunjangan bulanan akan dicairkan (dilindungi proteksi <em>Anti-Double Pay</em>).</span>
                                    <span x-show="!includeMonthlyBase" style="color:#64748b;">Hanya menghitung uang kehadiran &amp; lembur (gaji pokok tidak dicairkan).</span>
                                </div>
                            </div>
                        </div>

                        <!-- Sisi Kanan: Switch Toggle ON / OFF Fisik -->
                        <div class="flex-shrink-0"
                             :style="includeMonthlyBase 
                                ? 'position:relative; width:60px; min-width:60px; height:30px; border-radius:9999px; background:#10b981; box-shadow:0 2px 8px rgba(16, 185, 129, 0.35); transition:all 0.25s ease; cursor:pointer; display:block;' 
                                : 'position:relative; width:60px; min-width:60px; height:30px; border-radius:9999px; background:#9ca3af; transition:all 0.25s ease; cursor:pointer; display:block;'">
                            
                            <!-- Label ON -->
                            <span x-show="includeMonthlyBase" 
                                  style="position:absolute; left:9px; top:50%; transform:translateY(-50%); font-size:10.5px; font-weight:900; color:#ffffff; font-family:sans-serif; letter-spacing:0.5px; line-height:1;">
                                ON
                            </span>

                            <!-- Label OFF -->
                            <span x-show="!includeMonthlyBase" 
                                  style="position:absolute; right:8px; top:50%; transform:translateY(-50%); font-size:10px; font-weight:900; color:#ffffff; font-family:sans-serif; letter-spacing:0.5px; line-height:1;">
                                OFF
                            </span>

                            <!-- Knob Putih -->
                            <div :style="includeMonthlyBase 
                                    ? 'position:absolute; top:3px; left:0; width:24px; height:24px; border-radius:50%; background:#ffffff; box-shadow:0 2px 5px rgba(0,0,0,0.25); transition:transform 0.25s cubic-bezier(0.4, 0, 0.2, 1); transform:translateX(33px);' 
                                    : 'position:absolute; top:3px; left:0; width:24px; height:24px; border-radius:50%; background:#ffffff; box-shadow:0 2px 5px rgba(0,0,0,0.25); transition:transform 0.25s cubic-bezier(0.4, 0, 0.2, 1); transform:translateX(3px);'">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Employee List Container (Desktop Table + Mobile Cards) -->
                <div class="card" style="padding:0; overflow:hidden; border:1px solid var(--color-hairline); border-radius:12px;">
                    <!-- Toolbar Header -->
                    <div class="flex items-center justify-between p-3.5 sm:p-4 border-b" style="border-color:var(--color-hairline); background-color:var(--color-canvas);">
                        <div class="flex items-center gap-2">
                            <i data-lucide="users" style="width:18px; height:18px; color:#0284c7;"></i>
                            <span style="font-weight:700; color:var(--color-ink); font-size:13.5px;">Master Bulanan</span>
                            <span class="badge badge-info" style="font-size:11px; padding:2px 8px; border-radius:9999px;" x-text="`${selectedBulanan.length} Terpilih`"></span>
                        </div>
                        <div>
                            <button type="button" @click="toggleSelectAllBulanan()" class="btn btn-secondary btn-sm" style="height:32px; font-size:11.5px; border-radius:8px; display:inline-flex; align-items:center; gap:6px;">
                                <i data-lucide="check-square" style="width:14px; height:14px;"></i>
                                <span x-text="selectedBulanan.length === bulananList.length ? 'Kosongkan' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                    </div>

                    <?php if (empty($karyawanBulanan)): ?>
                        <div style="padding:32px; text-align:center; color:var(--color-slate-400);">Tidak ada master karyawan bulanan.</div>
                    <?php else: ?>

                        <!-- 1. DESKTOP TABLE VIEW (Layar Komputer / Tablet) -->
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="w-full text-left" style="font-size:13px;">
                                <thead style="background:var(--color-slate-50); border-bottom:1px solid var(--color-hairline);">
                                    <tr>
                                        <th style="padding:12px 16px; width:48px; text-align:center;">
                                            <input type="checkbox" :checked="selectedBulanan.length === bulananList.length && bulananList.length > 0" @change="toggleSelectAllBulanan()" style="width:16px;height:16px;border-radius:4px;cursor:pointer;">
                                        </th>
                                        <th style="padding:12px 16px; font-weight:700; color:var(--color-slate-600);">Nama Karyawan</th>
                                        <th style="padding:12px 16px; font-weight:700; color:var(--color-slate-600);">Posisi / Jabatan</th>
                                        <th style="padding:12px 16px; font-weight:700; color:var(--color-slate-600); text-align:right;">Gaji Pokok</th>
                                        <th style="padding:12px 16px; font-weight:700; color:var(--color-slate-600); text-align:right;">Tunjangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($karyawanBulanan as $emp): ?>
                                    <tr @click="toggleBulanan('<?= $emp['id'] ?>')" 
                                        class="transition-colors border-b"
                                        style="border-color:var(--color-hairline); cursor:pointer;"
                                        :style="selectedBulanan.includes('<?= $emp['id'] ?>') ? 'background:rgba(2, 132, 199, 0.04);' : ''">
                                        <td style="padding:12px 16px; text-align:center;" @click.stop>
                                            <input type="checkbox" name="karyawan_bulanan_ids[]" value="<?= htmlspecialchars($emp['id']) ?>" x-model="selectedBulanan" style="width:16px;height:16px;border-radius:4px;cursor:pointer;">
                                        </td>
                                        <td style="padding:12px 16px;">
                                            <div class="flex items-center gap-2.5">
                                                <div style="width:30px; height:30px; border-radius:8px; background:#e0f2fe; color:#0369a1; font-weight:800; font-size:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                                    <?= strtoupper(substr($emp['nama_karyawan'] ?? 'K', 0, 2)) ?>
                                                </div>
                                                <div style="font-weight:700; color:var(--color-ink);"><?= htmlspecialchars($emp['nama_karyawan']) ?></div>
                                            </div>
                                        </td>
                                        <td style="padding:12px 16px; color:var(--color-slate-600);">
                                            <span class="badge badge-secondary" style="font-size:11px; padding:2px 8px; border-radius:6px;"><?= htmlspecialchars($emp['posisi'] ?? 'Staff / Manajemen') ?></span>
                                        </td>
                                        <td style="padding:12px 16px; text-align:right; font-weight:700; color:var(--color-ink); font-feature-settings:'tnum';">
                                            <?= Format::rupiah((float)$emp['gaji_pokok_bulanan']) ?>
                                        </td>
                                        <td style="padding:12px 16px; text-align:right; font-weight:600; color:var(--color-slate-600); font-feature-settings:'tnum';">
                                            <?= (float)$emp['tunjangan_bulanan'] > 0 ? Format::rupiah((float)$emp['tunjangan_bulanan']) : '-' ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- 2. MOBILE CARDS VIEW (Khusus Layar HP / Layar Kecil) -->
                        <div class="block sm:hidden p-3 space-y-2.5" style="background:var(--color-slate-50);">
                            <?php foreach ($karyawanBulanan as $emp): ?>
                            <div @click="toggleBulanan('<?= $emp['id'] ?>')"
                                 class="p-3.5 rounded-xl border transition-all select-none cursor-pointer flex flex-col gap-2.5"
                                 :style="selectedBulanan.includes('<?= $emp['id'] ?>') 
                                    ? 'background:#ffffff; border:1.5px solid #0284c7; box-shadow:0 2px 6px rgba(2, 132, 199, 0.12);' 
                                    : 'background:#ffffff; border:1px solid var(--color-hairline);'">
                               
                                <!-- Top Row: Checkbox + Avatar + Nama + Tipe -->
                                <div class="flex items-center justify-between gap-2.5">
                                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                        <div class="w-5 h-5 rounded-md border flex items-center justify-center flex-shrink-0 transition-colors"
                                             :style="selectedBulanan.includes('<?= $emp['id'] ?>') ? 'background:#0284c7; border-color:#0369a1; color:#ffffff;' : 'background:#ffffff; border-color:#cbd5e1;'">
                                            <i data-lucide="check" style="width:13px;height:13px;" x-show="selectedBulanan.includes('<?= $emp['id'] ?>')"></i>
                                        </div>

                                        <div style="width:30px; height:30px; border-radius:8px; background:#e0f2fe; color:#0369a1; font-weight:800; font-size:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                            <?= strtoupper(substr($emp['nama_karyawan'] ?? 'K', 0, 2)) ?>
                                        </div>
                                        
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate"><?= htmlspecialchars($emp['nama_karyawan']) ?></div>
                                            <div class="text-[10.5px] text-slate-500 truncate"><?= htmlspecialchars($emp['posisi'] ?? 'Staff / Manajemen') ?></div>
                                        </div>
                                    </div>
                                    
                                    <span class="badge badge-info text-[10px] px-2 py-0.5 rounded-full font-bold" style="border-radius: 9999px;">Bulanan</span>
                                </div>

                                <!-- Bottom Row: Gaji Pokok & Tunjangan -->
                                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px]">
                                    <div>
                                        <div class="text-[10px] text-slate-400 font-medium">Gaji Pokok:</div>
                                        <div class="font-bold text-slate-800 dark:text-slate-200" style="font-feature-settings:'tnum';">
                                            <?= Format::rupiah((float)$emp['gaji_pokok_bulanan']) ?>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[10px] text-slate-400 font-medium">Tunjangan:</div>
                                        <div class="font-semibold text-slate-600 dark:text-slate-400" style="font-feature-settings:'tnum';">
                                            <?= (float)$emp['tunjangan_bulanan'] > 0 ? Format::rupiah((float)$emp['tunjangan_bulanan']) : '<span class="text-slate-400 font-normal">-</span>' ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- ACTION BAR BAWAH (FLOATING STICKY & RESPONSIVE) -->
        <div class="card p-3 sm:p-4 mt-6" 
             style="position: sticky; bottom: 16px; z-index: 30; background: var(--color-canvas); border: 1px solid var(--color-hairline); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.05); border-radius: 16px;">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4">
                
                <!-- Input Nama Payroll (Wajib) -->
                <div class="flex-1 relative w-full sm:max-w-md">
                    <input type="text" 
                           id="nama_payroll" 
                           name="nama_payroll" 
                           x-model="payrollName" 
                           required
                           placeholder="Judul Payroll (Wajib Diisi - atau klik Auto)" 
                           class="form-input w-full font-medium" 
                           style="height: 42px; font-size: 13px; border-radius: 10px; background: var(--color-slate-50); padding-right: 85px;">
                    <button type="button" 
                            @click="payrollName = generatedSuggestion" 
                            class="btn btn-secondary btn-sm" 
                            title="Auto-Generate Nama dari Tipe dan Tanggal"
                            style="position: absolute; right: 4px; top: 4px; height: 34px; font-size: 11px; padding: 0 10px; border-radius: 7px; color: var(--color-primary); display: inline-flex; align-items: center; gap: 4px; font-weight: 700;">
                        <i data-lucide="wand-2" style="width: 14px; height: 14px;"></i>
                        <span>Auto</span>
                    </button>
                </div>

                <!-- Tombol Submit & Counter Terpilih -->
                <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto">
                    <div class="flex items-center gap-1.5 text-xs font-bold">
                        <span class="badge badge-primary text-xs px-2.5 py-1 font-bold" style="border-radius: 8px; font-feature-settings:'tnum';" x-text="totalSelected"></span>
                        <span style="color: var(--color-slate-600);">Orang Dipilih</span>
                    </div>
                    
                    <button type="submit" 
                            class="btn btn-primary flex-1 sm:flex-initial" 
                            style="height: 42px; border-radius: 10px; font-size: 14px; font-weight: 800; padding: 0 20px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-width: 170px;"
                            :disabled="totalSelected === 0 || !payrollName.trim()"
                            :style="(totalSelected === 0 || !payrollName.trim()) ? 'opacity: 0.5; cursor: not-allowed;' : ''">
                        <template x-if="isEditMode">
                            <span style="display:inline-flex; align-items:center; gap:6px;">
                                <i data-lucide="refresh-cw" style="width: 16px; height: 16px;"></i>
                                <span>Hitung Ulang &amp; Simpan</span>
                            </span>
                        </template>
                        <template x-if="!isEditMode">
                            <span style="display:inline-flex; align-items:center; gap:6px;">
                                <i data-lucide="zap" style="width: 16px; height: 16px;"></i>
                                <span>Generate Draf</span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

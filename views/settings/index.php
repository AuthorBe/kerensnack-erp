<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<style>
    .settings-grid {
        margin-top: 1rem;
    }

    .settings-card {
        background-color: var(--color-surface, #ffffff);
        border: 1px solid var(--color-hairline, #e2e8f0);
        border-radius: 1.25rem;
        padding: 1.25rem;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
        text-decoration: none;
    }

    @media (min-width: 640px) {
        .settings-card {
            padding: 1.5rem;
        }
    }
    
    .dark .settings-card {
        background-color: #0f172a;
        border-color: #1e293b;
    }

    .settings-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 3px;
        background: transparent;
        transition: all 0.2s ease;
    }

    .settings-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px -6px rgba(0, 0, 0, 0.08), 0 3px 6px -2px rgba(0, 0, 0, 0.03);
    }

    .settings-card:active {
        transform: scale(0.985);
    }

    /* M3 Color Roles & Top Accent Lines */
    .card-izin::before { background: linear-gradient(90deg, #f97316, #fdba74); }
    .card-izin:hover { border-color: #f97316; }
    .card-izin .icon-box { background: rgba(249, 115, 22, 0.1); color: #ea580c; }
    
    .card-user::before { background: linear-gradient(90deg, #10b981, #6ee7b7); }
    .card-user:hover { border-color: #10b981; }
    .card-user .icon-box { background: rgba(16, 185, 129, 0.1); color: #059669; }

    .card-ai::before { background: linear-gradient(90deg, #8b5cf6, #c084fc); }
    .card-ai:hover { border-color: #8b5cf6; }
    .card-ai .icon-box { background: rgba(139, 92, 246, 0.1); color: #7c3aed; }

    .card-log::before { background: linear-gradient(90deg, #0284c7, #38bdf8); }
    .card-log:hover { border-color: #0284c7; }
    .card-log .icon-box { background: rgba(2, 132, 199, 0.1); color: #0284c7; }

    .icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        margin-bottom: 1rem;
        flex-shrink: 0;
    }

    @media (min-width: 640px) {
        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            margin-bottom: 1.25rem;
        }
    }

    .settings-card:hover .icon-box {
        transform: scale(1.06);
    }

    .card-title {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
        line-height: 1.35;
    }

    @media (min-width: 640px) {
        .card-title {
            font-size: 1.1rem;
        }
    }

    .card-desc {
        font-size: 0.8rem;
        line-height: 1.5;
        margin-bottom: 1.25rem;
    }

    @media (min-width: 640px) {
        .card-desc {
            font-size: 0.85rem;
        }
    }

    .settings-link {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-weight: 700;
        font-size: 0.8rem;
        margin-top: auto;
        padding-top: 0.5rem;
        transition: all 0.2s ease;
    }

    @media (min-width: 640px) {
        .settings-link {
            font-size: 0.85rem;
            gap: 0.5rem;
        }
    }

    .settings-link i {
        transition: transform 0.2s ease;
    }
    
    .settings-card:hover .settings-link i {
        transform: translateX(4px);
    }
</style>

<div class="space-y-4 sm:space-y-6 pb-20 max-w-7xl mx-auto">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER (M3 APP BAR / BANNER)                                         -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon" style="background:rgba(59,130,246,0.12);color:#3b82f6;">
                <i data-lucide="settings-2"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#3b82f6;"></span>
                    <span>Pengaturan Sistem &bull; <?= htmlspecialchars(ucfirst(Auth::role())) ?></span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl"><?= htmlspecialchars($pageTitle ?? 'Pengaturan Sistem') ?></h1>
                <p class="page-subtitle text-xs sm:text-sm"><?= htmlspecialchars($pageSubtitle ?? 'Pusat Manajemen Konfigurasi Aplikasi, Hak Akses & Akun Pengguna') ?></p>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- QUICK STATS CHIPS (CLEAN M3 MINIMALIST KPI)                               -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <div class="card p-3.5 sm:p-4 rounded-xl sm:rounded-2xl flex items-center gap-3 sm:gap-3.5 shadow-sm" style="border:1px solid var(--color-hairline);">
            <div class="flex items-center justify-center flex-shrink-0" style="color:#10b981;">
                <i data-lucide="users-2" class="w-6 h-6 sm:w-7 sm:h-7" style="stroke:#10b981;"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] sm:text-[11px] font-bold truncate" style="color:var(--color-ink-mute);">Pengguna Terdaftar</div>
                <div class="text-sm sm:text-base font-black truncate" style="color:var(--color-ink);"><?= $totalUsers ?> Akun Aktif</div>
                <?php if (isset($totalEmployees) && $totalEmployees > 0): ?>
                    <div class="text-[10px] sm:text-[10.5px] truncate" style="color:var(--color-ink-mute);margin-top:1px;">dari <?= $totalEmployees ?> Total Karyawan</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card p-3.5 sm:p-4 rounded-xl sm:rounded-2xl flex items-center gap-3 sm:gap-3.5 shadow-sm" style="border:1px solid var(--color-hairline);">
            <div class="flex items-center justify-center flex-shrink-0" style="color:#8b5cf6;">
                <i data-lucide="shield" class="w-6 h-6 sm:w-7 sm:h-7" style="stroke:#8b5cf6;"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] sm:text-[11px] font-bold truncate" style="color:var(--color-ink-mute);">Master Peran</div>
                <div class="text-sm sm:text-base font-black truncate" style="color:var(--color-ink);"><?= $totalRoles ?> Peran Jabatan</div>
            </div>
        </div>

        <div class="card p-3.5 sm:p-4 rounded-xl sm:rounded-2xl flex items-center gap-3 sm:gap-3.5 shadow-sm" style="border:1px solid var(--color-hairline);">
            <div class="flex items-center justify-center flex-shrink-0" style="color:#f97316;">
                <i data-lucide="key" class="w-6 h-6 sm:w-7 sm:h-7" style="stroke:#f97316;"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] sm:text-[11px] font-bold truncate" style="color:var(--color-ink-mute);">Kamus Izin</div>
                <div class="text-sm sm:text-base font-black truncate" style="color:var(--color-ink);"><?= $totalPerms ?> Izin Granular</div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- GRID CARD NAVIGASI PENGATURAN (RESPONSIF MOBILE & MINIMALIS)              -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 <?= Auth::can('system.blueprint') ? 'lg:grid-cols-3' : 'lg:grid-cols-2' ?> gap-3.5 sm:gap-5 settings-grid">

        <!-- KARTU 1: Manajemen Hak Akses & Izin (Pusat RBAC) -->
        <?php if (Auth::can('rbac.permissions_manage')): ?>
        <div>
            <a href="<?= Router::url('/permissions') ?>" class="settings-card card-izin group">
                <div class="icon-box">
                    <i data-lucide="shield-check" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                </div>
                <h5 class="card-title text-slate-900 dark:text-white">Manajemen Hak Akses &amp; Izin</h5>
                <p class="card-desc text-slate-500 dark:text-slate-400">Pusat konfigurasi <?= $totalPerms ?> izin granular standar, matriks bawaan peran, kelola jabatan dinamis, dan audit perizinan sistem.</p>
                <div class="settings-link text-orange-600 dark:text-orange-400">
                    <span>Buka Manajemen Izin</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                </div>
            </a>
        </div>
        <?php endif; ?>

        <!-- KARTU 2: Manajemen User (Akun Login) -->
        <?php if (Auth::can(['rbac.users_view', 'rbac.users_manage'])): ?>
        <div>
            <a href="<?= Router::url('/users') ?>" class="settings-card card-user group">
                <div class="icon-box">
                    <i data-lucide="user-plus" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                </div>
                <h5 class="card-title text-slate-900 dark:text-white">Manajemen Pengguna</h5>
                <p class="card-desc text-slate-500 dark:text-slate-400">Kelola akun login sistem, penautan data pegawai/karyawan, reset kata sandi, dan kontrol status aktif/suspend secara terpadu.</p>
                <div class="settings-link text-emerald-600 dark:text-emerald-400">
                    <span>Kelola Pengguna</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                </div>
            </a>
        </div>
        <?php endif; ?>

        <!-- KARTU 3: System Blueprint & AI Studio (Developer / Superuser) -->
        <?php if (Auth::can('system.blueprint')): ?>
        <div>
            <a href="<?= Router::url('/developer/architecture') ?>" class="settings-card card-ai group">
                <div class="icon-box">
                    <i data-lucide="cpu" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                </div>
                <h5 class="card-title text-slate-900 dark:text-white">System Blueprint &amp; AI</h5>
                <p class="card-desc text-slate-500 dark:text-slate-400">Visualisasi arsitektur relasional database Postgres, kamus data terpadu, dan modul AI Prompt Studio.</p>
                <div class="settings-link text-purple-600 dark:text-purple-400">
                    <span>Buka Blueprint</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                </div>
            </a>
        </div>
        <?php endif; ?>

        <!-- KARTU 4: Log Aktivitas Sistem & Audit Trail -->
        <?php if (Auth::can('system.activity_log')): ?>
        <div>
            <a href="<?= Router::url('/settings/activity-logs') ?>" class="settings-card card-log group">
                <div class="icon-box">
                    <i data-lucide="activity" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                </div>
                <h5 class="card-title text-slate-900 dark:text-white">Audit Trail &amp; Log Aktivitas</h5>
                <p class="card-desc text-slate-500 dark:text-slate-400">Periksa rekam jejak audit staf: siapa yang mengubah harga, membatalkan faktur, waktu kejadian, dan detail data sebelum/sesudah.</p>
                <div class="settings-link text-sky-600 dark:text-sky-400">
                    <span>Lihat Log Aktivitas</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                </div>
            </a>
        </div>
        <?php endif; ?>

    </div>

</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

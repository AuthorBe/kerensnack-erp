<?php
use App\Core\Router;
use App\Helpers\Format;
use App\Helpers\CSRF;
use App\Helpers\ActivityLog;

ob_start();

// Helper waktu relatif ramah pengguna
$formatTimeAgo = function(string $datetime): string {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' mnt lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    return date('d/m/Y', $timestamp);
};

// Map warna kategori (mengikuti tone warna tema ERP)
$categoryThemes = [
    'keamanan_auth' => ['label' => 'Keamanan & Auth', 'bg' => '#fff1f2', 'color' => '#be123c', 'border' => '#fecdd3', 'icon' => 'shield-check'],
    'keuangan'      => ['label' => 'Keuangan',        'bg' => '#ecfdf5', 'color' => '#047857', 'border' => '#a7f3d0', 'icon' => 'dollar-sign'],
    'penjualan'     => ['label' => 'Penjualan / POS', 'bg' => '#f0f9ff', 'color' => '#0369a1', 'border' => '#bae6fd', 'icon' => 'shopping-cart'],
    'gudang_stok'   => ['label' => 'Gudang & Stok',   'bg' => '#fffbeb', 'color' => '#b45309', 'border' => '#fde68a', 'icon' => 'package'],
    'logistik'      => ['label' => 'Logistik',        'bg' => '#eef2ff', 'color' => '#4338ca', 'border' => '#c7d2fe', 'icon' => 'truck'],
    'produksi_bom'  => ['label' => 'Produksi (BOM)',  'bg' => '#fff7ed', 'color' => '#c2410c', 'border' => '#fed7aa', 'icon' => 'factory'],
    'hr_payroll'    => ['label' => 'HR & Payroll',    'bg' => '#f0fdfa', 'color' => '#0f766e', 'border' => '#99f6e4', 'icon' => 'users'],
    'master_data'   => ['label' => 'Master Data',     'bg' => '#f8fafc', 'color' => '#334155', 'border' => '#cbd5e1', 'icon' => 'database'],
    'ai_interaction'=> ['label' => 'AI / Otomasi',    'bg' => '#f5f3ff', 'color' => '#6d28d9', 'border' => '#ddd6fe', 'icon' => 'bot'],
];

// Map warna badge peran aktor
$roleThemes = [
    'developer' => ['label' => 'Developer', 'bg' => '#fff1f2', 'color' => '#be123c', 'border' => '#fecdd3'],
    'owner'     => ['label' => 'Owner',     'bg' => '#faf5ff', 'color' => '#7e22ce', 'border' => '#e9d5ff'],
    'admin'     => ['label' => 'Admin',     'bg' => '#eff6ff', 'color' => '#1d4ed8', 'border' => '#bfdbfe'],
    'mandor'    => ['label' => 'Mandor',    'bg' => '#fff7ed', 'color' => '#c2410c', 'border' => '#ffedd5'],
    'sales'     => ['label' => 'Sales',     'bg' => '#fefce8', 'color' => '#a16207', 'border' => '#fef08a'],
    'driver'    => ['label' => 'Driver',    'bg' => '#f0fdf4', 'color' => '#15803d', 'border' => '#bbf7d0'],
    'system'    => ['label' => 'System',    'bg' => '#f1f5f9', 'color' => '#475569', 'border' => '#cbd5e1'],
];
?>

<div class="space-y-5" x-data="activityLogApp()">

    <!-- ========================================================================= -->
    <!-- 1. STANDAR ERP PAGE HEADER                                                -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <a href="<?= Router::url('/settings') ?>" class="page-back-btn" title="Kembali ke Pengaturan">
                <i data-lucide="arrow-left"></i>
            </a>
            <div class="page-header-icon is-cyan" style="background:rgba(2,132,199,0.12);color:#0284c7;border:1px solid rgba(2,132,199,0.28);">
                <i data-lucide="activity"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#0284c7;"></span>
                    <span>Audit Trail &amp; Keamanan</span>
                </div>
                <h1 class="page-title">
                    <span><?= htmlspecialchars($pageTitle ?? 'Log Aktivitas Sistem') ?></span>
                    <span class="badge badge-mono text-xs" style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:6px;background:rgba(2,132,199,0.08);color:#0284c7;border:1px solid rgba(2,132,199,0.2);">
                        <?= number_format($totalLogs, 0, ',', '.') ?> Rekaman
                    </span>
                </h1>
                <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle ?? 'Pantau jejak mutasi data, perubahan harga, void transaksi, dan riwayat login') ?></p>
            </div>
        </div>

        <div class="page-header-actions">
            <!-- Storage Telemetry Chip -->
            <div class="header-chip" style="height:36px;padding:0 12px;display:inline-flex;align-items:center;gap:7px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:8px;font-size:12px;" title="Kapasitas Penyimpanan Tabel log_aktivitas di PostgreSQL">
                <i data-lucide="database" style="width:14px;height:14px;color:#0284c7;"></i>
                <span style="font-weight:700;font-family:var(--font-mono);color:var(--color-ink);"><?= htmlspecialchars($dbTelemetry['total_size'] ?? '0 kB') ?></span>
                <span style="color:var(--color-ink-mute);font-size:11px;">Storage DB</span>
            </div>

            <!-- Ekspor Excel Button -->
            <a href="<?= Router::url('/settings/activity-logs/export?' . http_build_query($_GET)) ?>" 
               class="btn btn-secondary btn-sm" 
               style="height:36px;font-size:12px;font-weight:700;border-radius:8px;padding:0 14px;"
               title="Unduh data audit trail ke berkas Excel (.xlsx) sesuai filter aktif">
                <i data-lucide="download" style="width:14px;height:14px;color:#10b981;"></i>
                <span>Ekspor Excel</span>
            </a>

            <!-- Developer Prune Modal Button -->
            <?php if ($isDeveloper): ?>
            <button type="button" 
                    @click="openPruneModal()" 
                    class="btn btn-sm" 
                    style="height:36px;font-size:12px;font-weight:700;border-radius:8px;padding:0 14px;background:rgba(225,29,72,0.1);color:#e11d48;border:1px solid rgba(225,29,72,0.25);"
                    title="Fitur Khusus Developer: Kelola Retensi &amp; Pembersihan Log Lama">
                <i data-lucide="shield-alert" style="width:14px;height:14px;"></i>
                <span>Kelola Retensi Log</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. 4 METRIC KPI STAT CARDS (Mengikuti Format Standard ERP)                -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        
        <!-- Card 1: Hari Ini -->
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;padding:16px;">
            <div class="stat-card-icon" style="background:rgba(2,132,199,0.1);color:#0284c7;">
                <i data-lucide="activity"></i>
            </div>
            <div>
                <div class="stat-card-label">Aktivitas Hari Ini</div>
                <div class="stat-card-value font-mono" style="font-size:22px;color:var(--color-ink);">
                    <?= number_format($kpi['today'], 0, ',', '.') ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Tercatat sejak 00:00 WIB</div>
            </div>
        </div>

        <!-- Card 2: Keamanan & Auth -->
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;padding:16px;">
            <div class="stat-card-icon" style="background:rgba(225,29,72,0.1);color:#e11d48;">
                <i data-lucide="shield-check"></i>
            </div>
            <div>
                <div class="stat-card-label">Keamanan &amp; Auth</div>
                <div class="stat-card-value font-mono" style="font-size:22px;color:#e11d48;">
                    <?= number_format($kpi['security'], 0, ',', '.') ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Login, logout &amp; hak akses</div>
            </div>
        </div>

        <!-- Card 3: Mutasi Bisnis Hari Ini -->
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;padding:16px;">
            <div class="stat-card-icon" style="background:rgba(245,158,11,0.1);color:#d97706;">
                <i data-lucide="layers"></i>
            </div>
            <div>
                <div class="stat-card-label">Mutasi Bisnis Hari Ini</div>
                <div class="stat-card-value font-mono" style="font-size:22px;color:#d97706;">
                    <?= number_format($kpi['mutations'], 0, ',', '.') ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Harga, kas, stok &amp; order</div>
            </div>
        </div>

        <!-- Card 4: Kondisi Storage DB -->
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;padding:16px;">
            <div class="stat-card-icon" style="background:rgba(16,185,129,0.1);color:#059669;">
                <i data-lucide="hard-drive"></i>
            </div>
            <div>
                <div class="stat-card-label">Kondisi Storage DB</div>
                <div class="stat-card-value font-mono" style="font-size:22px;color:#059669;">
                    <?= htmlspecialchars($kpi['totalSize']) ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;"><?= number_format($kpi['totalAll'], 0, ',', '.') ?> Baris • Sangat Ringan</div>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 3. COMPACT & ERGONOMIC FILTER TOOLBAR                                     -->
    <!-- ========================================================================= -->
    <div class="card p-3.5 sm:p-4 rounded-xl shadow-sm" style="border:1px solid var(--color-hairline);background:var(--color-canvas);">
        <form method="GET" action="<?= Router::url('/settings/activity-logs') ?>" id="filterForm" class="space-y-3">
            
            <!-- Row 1: Quick Presets & Advanced Filter Toggle -->
            <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b" style="border-color:var(--color-hairline);">
                <div class="flex items-center flex-wrap gap-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mr-1.5 flex items-center gap-1">
                        <i data-lucide="calendar" style="width:13px;height:13px;"></i>
                        <span>Periode Cepat:</span>
                    </span>

                    <a href="<?= Router::url('/settings/activity-logs?' . http_build_query(array_merge($_GET, ['preset' => '', 'start_date' => '', 'end_date' => '', 'page' => 1]))) ?>" 
                       class="badge <?= (empty($preset) && empty($startDate) && empty($endDate)) ? 'badge-primary' : 'badge-secondary' ?>"
                       style="padding:5px 10px;font-size:11.5px;text-decoration:none;cursor:pointer;">
                        Semua Waktu
                    </a>

                    <a href="<?= Router::url('/settings/activity-logs?' . http_build_query(array_merge($_GET, ['preset' => 'today', 'start_date' => '', 'end_date' => '', 'page' => 1]))) ?>" 
                       class="badge <?= ($preset === 'today') ? 'badge-primary' : 'badge-secondary' ?>"
                       style="padding:5px 10px;font-size:11.5px;text-decoration:none;cursor:pointer;">
                        Hari Ini
                    </a>

                    <a href="<?= Router::url('/settings/activity-logs?' . http_build_query(array_merge($_GET, ['preset' => '7days', 'start_date' => '', 'end_date' => '', 'page' => 1]))) ?>" 
                       class="badge <?= ($preset === '7days') ? 'badge-primary' : 'badge-secondary' ?>"
                       style="padding:5px 10px;font-size:11.5px;text-decoration:none;cursor:pointer;">
                        7 Hari Terakhir
                    </a>

                    <a href="<?= Router::url('/settings/activity-logs?' . http_build_query(array_merge($_GET, ['preset' => 'this_month', 'start_date' => '', 'end_date' => '', 'page' => 1]))) ?>" 
                       class="badge <?= ($preset === 'this_month') ? 'badge-primary' : 'badge-secondary' ?>"
                       style="padding:5px 10px;font-size:11.5px;text-decoration:none;cursor:pointer;">
                        Bulan Ini
                    </a>
                </div>

                <!-- Toggle Filter Lanjutan Button -->
                <button type="button" 
                        @click="showAdvancedFilter = !showAdvancedFilter" 
                        class="btn btn-ghost btn-sm" 
                        style="font-size:11.5px;font-weight:600;padding:4px 8px;gap:4px;">
                    <i data-lucide="sliders-horizontal" style="width:13px;height:13px;"></i>
                    <span>Filter Lanjutan</span>
                    <i data-lucide="chevron-down" style="width:13px;height:13px;transition:transform 0.2s;" :style="showAdvancedFilter ? 'transform:rotate(180deg)' : ''"></i>
                </button>
            </div>

            <!-- Row 2: Main Filter Row (Compact & Horizontal) -->
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-2.5">
                
                <!-- Search Input -->
                <div class="form-input-icon flex-1 min-w-[200px]">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Cari staf, kata kunci, IP, deskripsi aktivitas..." 
                           class="form-input" style="height:36px;font-size:12.5px;">
                </div>

                <!-- Kategori Dropdown -->
                <select name="kategori" class="form-input w-full lg:w-auto" style="height:36px;font-size:12px;min-width:150px;">
                    <option value="">-- Semua Kategori --</option>
                    <?php foreach ($categories as $cat): 
                        $catTitle = $categoryThemes[$cat]['label'] ?? ucfirst(str_replace('_', ' ', $cat));
                    ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= ($kategori === $cat) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($catTitle) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <!-- Jenis Aksi Dropdown -->
                <select name="jenis_aksi" class="form-input w-full lg:w-auto" style="height:36px;font-size:12px;min-width:145px;">
                    <option value="">-- Semua Aksi --</option>
                    <?php foreach ($standardActions as $actKey => $actLabel): ?>
                    <option value="<?= htmlspecialchars($actKey) ?>" <?= (strtoupper($jenisAksi) === strtoupper($actKey)) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($actLabel) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <!-- Aktor Dropdown -->
                <select name="aktor" class="form-input w-full lg:w-auto" style="height:36px;font-size:12px;min-width:140px;">
                    <option value="">-- Semua Aktor --</option>
                    <?php foreach ($actors as $actItem): ?>
                    <option value="<?= htmlspecialchars($actItem['nama_aktor']) ?>" <?= ($aktor === $actItem['nama_aktor']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($actItem['nama_aktor']) ?> (<?= htmlspecialchars(ucfirst($actItem['peran_aktor'] ?: 'staff')) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>

                <!-- Submit & Reset Action Buttons -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="submit" class="btn btn-primary btn-sm" style="height:36px;font-size:12px;font-weight:700;padding:0 14px;">
                        <i data-lucide="filter" style="width:13px;height:13px;"></i>
                        <span>Terapkan</span>
                    </button>
                    <a href="<?= Router::url('/settings/activity-logs') ?>" class="btn btn-secondary btn-sm" style="height:36px;width:36px;padding:0;display:flex;align-items:center;justify-content:center;" title="Reset Semua Filter">
                        <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i>
                    </a>
                </div>

            </div>

            <!-- Row 3: Collapsible Advanced Filter Details -->
            <div x-show="showAdvancedFilter || <?= (!empty($startDate) || !empty($endDate) || !empty($sumberAksi) || $perPage !== 25) ? 'true' : 'false' ?>" 
                 x-cloak
                 class="pt-3 border-t grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5" 
                 style="border-color:var(--color-hairline);">
                <div>
                    <label class="form-label text-[11px]">Dari Tanggal</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-input" style="height:34px;font-size:12px;">
                </div>
                <div>
                    <label class="form-label text-[11px]">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-input" style="height:34px;font-size:12px;">
                </div>
                <div>
                    <label class="form-label text-[11px]">Sumber Aksi</label>
                    <select name="sumber_aksi" class="form-input" style="height:34px;font-size:12px;">
                        <option value="">-- Semua Sumber --</option>
                        <option value="web_app" <?= ($sumberAksi === 'web_app') ? 'selected' : '' ?>>Web App (Browser)</option>
                        <option value="telegram_bot" <?= ($sumberAksi === 'telegram_bot') ? 'selected' : '' ?>>Telegram Bot</option>
                        <option value="whatsapp_bot" <?= ($sumberAksi === 'whatsapp_bot') ? 'selected' : '' ?>>WhatsApp Bot</option>
                        <option value="n8n_automation" <?= ($sumberAksi === 'n8n_automation') ? 'selected' : '' ?>>Otomasi n8n</option>
                        <option value="system_cron" <?= ($sumberAksi === 'system_cron') ? 'selected' : '' ?>>System Cron</option>
                    </select>
                </div>
                <div>
                    <label class="form-label text-[11px]">Baris Per Halaman</label>
                    <select name="per_page" class="form-input" style="height:34px;font-size:12px;" onchange="this.form.submit()">
                        <option value="25" <?= ($perPage === 25) ? 'selected' : '' ?>>25 Baris</option>
                        <option value="50" <?= ($perPage === 50) ? 'selected' : '' ?>>50 Baris</option>
                        <option value="100" <?= ($perPage === 100) ? 'selected' : '' ?>>100 Baris</option>
                    </select>
                </div>
            </div>

        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. TABEL AUDIT TRAIL LOG (Standar Material Card ERP)                      -->
    <!-- ========================================================================= -->
    <div class="card rounded-xl shadow-sm overflow-hidden" style="padding:0;border:1px solid var(--color-hairline);background:var(--color-canvas);">
        
        <!-- Table Header Toolbar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-3.5 sm:p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas-soft);">
            <div class="flex items-center gap-2">
                <i data-lucide="list-filter" style="width:15px;height:15px;color:var(--color-primary);"></i>
                <span style="font-weight:700;font-size:13px;color:var(--color-ink);">Daftar Rekaman Audit Trail</span>
                <span class="badge badge-mono text-xs">Halaman <?= $currentPage ?> dari <?= max(1, $totalPages) ?></span>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span>Menampilkan <strong><?= count($logs) ?></strong> dari <strong><?= number_format($totalLogs, 0, ',', '.') ?></strong> rekaman data</span>
            </div>
        </div>

        <!-- Table Responsive Container -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="table data-table" style="width:100%;font-size:12.5px;min-width:980px;">
                <thead>
                    <tr>
                        <th style="width:150px;padding:12px 14px;">WAKTU &amp; CLIENT</th>
                        <th style="width:200px;padding:12px 14px;">AKTOR &amp; PERAN</th>
                        <th style="width:140px;text-align:center;padding:12px 14px;">KATEGORI</th>
                        <th style="width:125px;text-align:center;padding:12px 14px;">AKSI</th>
                        <th style="padding:12px 14px;">DESKRIPSI AKTIVITAS</th>
                        <th style="width:105px;text-align:center;padding:12px 14px;">DETAIL DELTA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" style="padding:50px 20px;text-align:center;color:var(--color-ink-mute);">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center" style="background:var(--color-canvas-soft);color:var(--color-ink-mute);">
                                    <i data-lucide="file-x" style="width:24px;height:24px;"></i>
                                </div>
                                <span class="font-bold text-sm" style="color:var(--color-ink);">Tidak ada data log yang sesuai kriteria pencarian</span>
                                <span class="text-xs" style="color:var(--color-ink-mute);">Silakan coba sesuaikan kata kunci atau gunakan tombol reset filter</span>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): 
                        $jenis = strtoupper((string)($log['jenis_aksi'] ?? 'ACTION'));
                        $kat = strtolower((string)($log['kategori_aktivitas'] ?? 'master_data'));
                        $katTheme = $categoryThemes[$kat] ?? ['label' => ucfirst($kat), 'bg' => '#f8fafc', 'color' => '#334155', 'border' => '#cbd5e1', 'icon' => 'activity'];

                        $peran = strtolower((string)($log['peran_aktor'] ?? 'staff'));
                        $peranTheme = $roleThemes[$peran] ?? ['label' => ucfirst($peran), 'bg' => '#f1f5f9', 'color' => '#475569', 'border' => '#cbd5e1'];

                        // Dynamic Action Badge
                        $badgeStyle = 'background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;';
                        if (str_contains($jenis, 'CREATE') || str_contains($jenis, 'INSERT') || str_contains($jenis, 'TAMBAH')) {
                            $badgeStyle = 'background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;';
                        } elseif (str_contains($jenis, 'UPDATE') || str_contains($jenis, 'UBAH') || str_contains($jenis, 'EDIT')) {
                            $badgeStyle = 'background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;';
                        } elseif (str_contains($jenis, 'DELETE') || str_contains($jenis, 'HAPUS') || str_contains($jenis, 'VOID') || str_contains($jenis, 'CANCEL') || str_contains($jenis, 'BATAL')) {
                            $badgeStyle = 'background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;';
                        } elseif (str_contains($jenis, 'SYNC') || str_contains($jenis, 'IMPOR')) {
                            $badgeStyle = 'background:#f0fdfa;color:#0f766e;border:1px solid #99f6e4;';
                        } elseif (str_contains($jenis, 'PRICE') || str_contains($jenis, 'HARGA')) {
                            $badgeStyle = 'background:#fffbeb;color:#b45309;border:1px solid #fde68a;';
                        } elseif (str_contains($jenis, 'LOGIN')) {
                            $badgeStyle = 'background:#faf5ff;color:#7e22ce;border:1px solid #e9d5ff;';
                        } elseif (str_contains($jenis, 'APPROVE')) {
                            $badgeStyle = 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;';
                        } elseif (str_contains($jenis, 'PRUNE')) {
                            $badgeStyle = 'background:#fff1f2;color:#be123c;border:1px solid #fecdd3;';
                        }

                        $hasPayload = !empty($log['data_sebelum']) || !empty($log['data_sesudah']);
                        $timeWib = date('d/m/Y H:i:s', strtotime($log['waktu_kejadian'])) . ' WIB';
                        $timeAgo = $formatTimeAgo($log['waktu_kejadian']);
                        $client = $log['client_info'] ?? ActivityLog::parseUserAgent($log['user_agent'] ?? '');
                    ?>
                    <tr style="border-bottom:1px solid var(--color-hairline);">
                        
                        <!-- Col 1: Waktu & Sesi -->
                        <td class="cell-nowrap" style="vertical-align:top;padding:12px 14px;">
                            <div style="display:flex;align-items:center;gap:5px;font-weight:700;font-size:12.5px;color:var(--color-ink);" title="<?= $timeWib ?>">
                                <i data-lucide="clock" style="width:12px;height:12px;color:var(--color-primary);flex-shrink:0;"></i>
                                <span><?= $timeAgo ?></span>
                            </div>
                            <div style="font-family:var(--font-mono);font-size:10.5px;color:var(--color-ink-mute);margin-top:3px;" title="<?= $timeWib ?>">
                                <?= date('d/m/Y H:i:s', strtotime($log['waktu_kejadian'])) ?>
                            </div>
                            <!-- Client Badge Chip -->
                            <div style="display:inline-flex;align-items:center;gap:4.5px;font-size:10px;color:var(--color-ink-mute);margin-top:5px;background:var(--color-canvas-soft);padding:1px 6px;border-radius:4px;border:1px solid var(--color-hairline);" title="<?= htmlspecialchars($log['user_agent'] ?? '') ?>">
                                <i data-lucide="<?= $client['icon'] ?>" style="width:11px;height:11px;flex-shrink:0;"></i>
                                <span style="max-width:110px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($client['label']) ?></span>
                            </div>
                        </td>

                        <!-- Col 2: Aktor & Peran -->
                        <td style="vertical-align:top;padding:12px 14px;">
                            <div style="display:flex;align-items:flex-start;gap:9px;">
                                <!-- Avatar Lingkaran Elegan -->
                                <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:11.5px;flex-shrink:0;<?= $peran === 'developer' ? 'background:rgba(225,29,72,0.12);color:#e11d48;border:1px solid rgba(225,29,72,0.25);' : ($peran === 'owner' ? 'background:rgba(126,34,206,0.12);color:#7e22ce;border:1px solid rgba(126,34,206,0.25);' : 'background:rgba(2,132,199,0.12);color:#0284c7;border:1px solid rgba(2,132,199,0.25);') ?>">
                                    <?= strtoupper(mb_substr($log['nama_aktor'] ?: 'S', 0, 2)) ?>
                                </div>
                                <!-- Info Aktor -->
                                <div style="min-width:0;flex:1;">
                                    <a href="<?= Router::url('/settings/activity-logs?' . http_build_query(array_merge($_GET, ['aktor' => $log['nama_aktor'], 'page' => 1]))) ?>" 
                                       style="font-weight:700;font-size:12.5px;color:var(--color-ink);text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                       title="Filter aktivitas aktor: <?= htmlspecialchars($log['nama_aktor'] ?: 'Sistem') ?>">
                                        <?= htmlspecialchars($log['nama_aktor'] ?: 'Sistem') ?>
                                    </a>
                                    <div style="display:flex;align-items:center;gap:5px;margin-top:3px;flex-wrap:nowrap;">
                                        <span class="badge" style="<?= 'background:' . $peranTheme['bg'] . ';color:' . $peranTheme['color'] . ';border:1px solid ' . $peranTheme['border'] ?>;font-size:9.5px;font-weight:700;padding:1px 6px;border-radius:4px;white-space:nowrap;">
                                            <?= htmlspecialchars($peranTheme['label']) ?>
                                        </span>
                                        <?php if (!empty($log['ip_address'])): ?>
                                        <span style="font-family:var(--font-mono);font-size:10px;color:var(--color-ink-mute);white-space:nowrap;" title="IP Address: <?= htmlspecialchars($log['ip_address']) ?>">
                                            <?= htmlspecialchars($log['ip_address']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Col 3: Kategori -->
                        <td class="cell-center cell-nowrap" style="vertical-align:top;padding:12px 14px;">
                            <span class="badge" style="<?= 'background:' . $katTheme['bg'] . ';color:' . $katTheme['color'] . ';border:1px solid ' . $katTheme['border'] ?>;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:6px;display:inline-flex;align-items:center;gap:4.5px;">
                                <i data-lucide="<?= $katTheme['icon'] ?>" style="width:12px;height:12px;flex-shrink:0;"></i>
                                <span><?= htmlspecialchars($katTheme['label']) ?></span>
                            </span>
                        </td>

                        <!-- Col 4: Aksi -->
                        <td class="cell-center cell-nowrap" style="vertical-align:top;padding:12px 14px;">
                            <span class="badge" style="<?= $badgeStyle ?>font-size:10.5px;font-weight:800;padding:3px 8px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;letter-spacing:0.02em;">
                                <?= htmlspecialchars($jenis) ?>
                            </span>
                        </td>

                        <!-- Col 5: Deskripsi Aktivitas -->
                        <td style="vertical-align:top;padding:12px 14px;">
                            <div style="font-weight:500;font-size:12.5px;color:var(--color-ink);line-height:1.5;word-break:break-word;">
                                <?= htmlspecialchars($log['deskripsi_aktivitas'] ?? '-') ?>
                            </div>
                            <?php if (!empty($log['tabel_terdampak'])): ?>
                            <div style="display:flex;align-items:center;gap:6px;margin-top:5px;flex-wrap:wrap;">
                                <span style="display:inline-flex;align-items:center;gap:4px;font-family:var(--font-mono);font-size:10px;background:var(--color-canvas-soft);color:var(--color-ink-mute);padding:1px 6px;border-radius:4px;border:1px solid var(--color-hairline);">
                                    <i data-lucide="database" style="width:10px;height:10px;color:var(--color-primary);flex-shrink:0;"></i>
                                    <span>Target: <strong style="color:var(--color-ink);"><?= htmlspecialchars($log['tabel_terdampak']) ?></strong></span>
                                </span>
                                <?php if (!empty($log['id_referensi'])): ?>
                                <span style="font-family:var(--font-mono);font-size:10px;color:var(--color-ink-mute);" title="ID Referensi: <?= htmlspecialchars($log['id_referensi']) ?>">
                                    #<?= substr($log['id_referensi'], 0, 8) ?>...
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>

                        <!-- Col 6: Detail Delta Button -->
                        <td class="cell-center cell-nowrap" style="vertical-align:top;padding:12px 14px;">
                            <?php if ($hasPayload): ?>
                            <button type="button" 
                                    @click="openDiffModal(<?= htmlspecialchars(json_encode($log)) ?>)" 
                                    class="btn btn-secondary btn-sm" 
                                    style="height:30px;padding:0 10px;border-radius:6px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:4px;" 
                                    title="Buka Inspector Delta Perubahan">
                                <i data-lucide="eye" style="width:12px;height:12px;color:#0284c7;"></i>
                                <span>Lihat Diff</span>
                            </button>
                            <?php else: ?>
                            <span style="color:var(--color-ink-mute-2);font-size:11px;font-family:var(--font-mono);line-height:30px;">—</span>
                            <?php endif; ?>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Standard ERP Pagination Footer -->
        <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 border-t flex flex-col sm:flex-row items-center justify-between gap-3" style="border-color:var(--color-hairline);background-color:var(--color-canvas-soft);">
            <div style="font-size:12px;color:var(--color-ink-mute);font-family:var(--font-mono);">
                Menampilkan <strong style="color:var(--color-ink);"><?= ($currentPage - 1) * $perPage + 1 ?></strong> -
                <strong style="color:var(--color-ink);"><?= min($currentPage * $perPage, $totalLogs) ?></strong>
                dari <strong style="color:var(--color-ink);"><?= number_format($totalLogs, 0, ',', '.') ?></strong> log
                <span style="margin:0 4px;opacity:0.4;">•</span>
                <span>Hal <strong><?= $currentPage ?></strong> / <?= $totalPages ?></span>
            </div>
            <div class="flex items-center gap-1">
                <?php if ($currentPage > 1): ?>
                <a href="<?= Router::url('/settings/activity-logs?' . http_build_query(array_merge($_GET, ['page' => 1]))) ?>" 
                   class="btn btn-secondary btn-sm" style="height:30px;padding:0 8px;font-size:11px;" title="Halaman Pertama">&laquo;</a>
                <a href="<?= Router::url('/settings/activity-logs?' . http_build_query(array_merge($_GET, ['page' => $currentPage - 1]))) ?>" 
                   class="btn btn-secondary btn-sm" style="height:30px;padding:0 10px;font-size:11px;display:inline-flex;align-items:center;gap:3px;">
                    <i data-lucide="chevron-left" style="width:12px;height:12px;"></i>
                    <span>Sebelumnya</span>
                </a>
                <?php endif; ?>

                <?php 
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                for ($p = $startPage; $p <= $endPage; $p++): 
                ?>
                <a href="<?= Router::url('/settings/activity-logs?' . http_build_query(array_merge($_GET, ['page' => $p]))) ?>" 
                   class="btn btn-sm <?= ($p === $currentPage) ? 'btn-primary' : 'btn-ghost' ?>"
                   style="height:30px;min-width:30px;padding:0 8px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;">
                    <?= $p ?>
                </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                <a href="<?= Router::url('/settings/activity-logs?' . http_build_query(array_merge($_GET, ['page' => $currentPage + 1]))) ?>" 
                   class="btn btn-secondary btn-sm" style="height:30px;padding:0 10px;font-size:11px;display:inline-flex;align-items:center;gap:3px;">
                    <span>Selanjutnya</span>
                    <i data-lucide="chevron-right" style="width:12px;height:12px;"></i>
                </a>
                <a href="<?= Router::url('/settings/activity-logs?' . http_build_query(array_merge($_GET, ['page' => $totalPages]))) ?>" 
                   class="btn btn-secondary btn-sm" style="height:30px;padding:0 8px;font-size:11px;" title="Halaman Terakhir">&raquo;</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ========================================================================= -->
    <!-- 5. MODAL SIDE-BY-SIDE VISUAL DIFF INSPECTOR                               -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showDiffModal" x-cloak class="modal-backdrop" style="background:rgba(15,23,42,0.65);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:16px;z-index:9999;">
        <div class="modal-box modal-box-lg" style="max-width:760px;width:94vw;max-height:90vh;padding:0;border-radius:20px;overflow:hidden;display:flex;flex-direction:column;background:var(--color-canvas);border:1.5px solid var(--color-hairline);box-shadow:0 25px 60px -15px rgba(0,0,0,0.25);" @click.stop>
            
            <!-- Mobile Pull Handle -->
            <div class="sm:hidden w-full flex justify-center pt-3 pb-1 flex-shrink-0" style="background:var(--color-canvas);">
                <div style="width:40px;height:4px;border-radius:2px;background:var(--color-hairline);"></div>
            </div>

            <!-- 1. MODAL HEADER (Mengikuti Standar Modul Detail ERP) -->
            <div style="padding:18px 22px;border-bottom:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:space-between;background:var(--color-canvas);flex-shrink:0;gap:16px;">
                <div style="display:flex;align-items:center;gap:14px;min-width:0;flex:1;">
                    <!-- Ikon Modul -->
                    <div style="width:44px;height:44px;border-radius:12px;background:#eff6ff;color:#1e3a8a;border:1px solid rgba(30,58,138,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="git-commit" style="width:22px;height:22px;"></i>
                    </div>
                    
                    <!-- Info Aktivitas & Meta -->
                    <div style="min-width:0;flex:1;">
                        <!-- Baris 1: Action Badge & Target Chip -->
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                            <span class="badge" 
                                  :style="getActionBadgeStyle(selectedLog?.jenis_aksi)"
                                  style="font-size:10.5px;font-weight:800;padding:2px 8px;border-radius:6px;letter-spacing:0.02em;"
                                  x-text="selectedLog?.jenis_aksi || 'ACTION'"></span>
                            <template x-if="selectedLog?.tabel_terdampak">
                                <span style="font-family:var(--font-mono);font-size:10.5px;color:var(--color-ink-mute);background:var(--color-canvas-soft);padding:2px 7px;border-radius:5px;border:1px solid var(--color-hairline);display:inline-flex;align-items:center;gap:4px;">
                                    <i data-lucide="database" style="width:10px;height:10px;color:var(--color-primary);"></i>
                                    <span>Target: <strong style="color:var(--color-ink);" x-text="selectedLog?.tabel_terdampak"></strong></span>
                                </span>
                            </template>
                            <template x-if="selectedLog?.id_referensi">
                                <span style="font-family:var(--font-mono);font-size:10.5px;color:var(--color-ink-mute);" x-text="'#' + (selectedLog?.id_referensi || '').substring(0, 8) + '...'"></span>
                            </template>
                        </div>
                        
                        <!-- Baris 2: Deskripsi Aksi -->
                        <h4 style="font-size:14px;font-weight:700;color:var(--color-ink);margin:0;line-height:1.4;word-break:break-word;" x-text="selectedLog?.deskripsi_aktivitas || 'Inspeksi Detail Perubahan Data'"></h4>
                        
                        <!-- Baris 3: Meta Info Lengkap (Aktor, Waktu WIB, IP) -->
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:11.5px;color:var(--color-ink-mute);margin-top:5px;">
                            <span style="display:inline-flex;align-items:center;gap:4px;">
                                <i data-lucide="user" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                                <span>Aktor: <strong style="color:var(--color-ink);" x-text="selectedLog?.nama_aktor || 'Sistem'"></strong></span>
                            </span>
                            <span style="color:var(--color-hairline);">&bull;</span>
                            <span style="display:inline-flex;align-items:center;gap:4px;">
                                <i data-lucide="calendar" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                                <span class="font-mono" style="font-weight:600;color:var(--color-ink);" x-text="formatDateShort(selectedLog?.waktu_kejadian)"></span>
                            </span>
                            <template x-if="selectedLog?.ip_address">
                                <span style="display:inline-flex;align-items:center;gap:10px;">
                                    <span style="color:var(--color-hairline);">&bull;</span>
                                    <span style="display:inline-flex;align-items:center;gap:4px;">
                                        <i data-lucide="network" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                                        <span class="font-mono" x-text="selectedLog?.ip_address"></span>
                                    </span>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Header Actions: Salin JSON & Close (X) -->
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                    <button type="button" @click="copyJson()" 
                            class="btn btn-secondary btn-sm" 
                            style="height:36px;padding:0 12px;font-size:11.5px;font-weight:600;border-radius:10px;display:inline-flex;align-items:center;gap:5px;"
                            title="Salin rekaman ini dalam format JSON">
                        <i data-lucide="copy" style="width:13px;height:13px;"></i>
                        <span x-text="copiedJson ? 'Tersalin!' : 'Salin JSON'"></span>
                    </button>
                </div>
            </div>

            <!-- 2. TAB NAVIGATION BAR (Native ERP .modal-tab-nav tanpa border tombol jelek) -->
            <div class="modal-tab-nav custom-scrollbar">
                <button type="button" 
                        @click="activeDiffTab = 'table'; $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })" 
                        class="modal-tab-btn" 
                        :class="{ 'is-active': activeDiffTab === 'table' }">
                    <i data-lucide="columns-2" style="width:14px;height:14px;"></i>
                    <span>Tabel Perbandingan Delta</span>
                    <template x-if="diffRows.length > 0">
                        <span class="badge" style="font-size:10px;padding:1px 6px;border-radius:10px;" x-text="diffRows.length + ' Atribut'"></span>
                    </template>
                </button>
                <button type="button" 
                        @click="activeDiffTab = 'raw'; $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })" 
                        class="modal-tab-btn" 
                        :class="{ 'is-active': activeDiffTab === 'raw' }">
                    <i data-lucide="code-2" style="width:14px;height:14px;"></i>
                    <span>Raw JSON Lengkap</span>
                </button>
            </div>

            <!-- 3. MODAL BODY (Scrollable Container) -->
            <div style="padding:20px;overflow-y:auto;flex:1;background:var(--color-canvas);">
                
                <!-- Tab 1: Visual Diff Table -->
                <div x-show="activeDiffTab === 'table'">
                    <template x-if="diffRows.length === 0">
                        <div style="padding:48px 20px;text-align:center;color:var(--color-ink-mute);background:var(--color-canvas-soft);border-radius:12px;border:1px dashed var(--color-hairline);">
                            <i data-lucide="info" style="width:28px;height:28px;margin:0 auto 8px auto;opacity:0.6;color:#0284c7;"></i>
                            <div style="font-weight:700;font-size:13px;color:var(--color-ink);">Tidak ada delta atribut tersimpan</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Aksi ini tidak mengubah nilai kolom data atau merupakan aksi non-mutasi.</div>
                        </div>
                    </template>
                    
                    <template x-if="diffRows.length > 0">
                        <div class="card overflow-hidden" style="border:1px solid var(--color-hairline);border-radius:12px;background:var(--color-canvas);box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                            <div class="overflow-x-auto custom-scrollbar">
                                <table class="data-table" style="width:100%;font-size:12.5px;border-collapse:collapse;margin:0;">
                                    <thead>
                                        <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                                            <th style="padding:12px 16px;font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.05em;width:30%;border-right:1px solid var(--color-hairline);vertical-align:middle;">
                                                <div style="display:flex;align-items:center;gap:6px;">
                                                    <i data-lucide="tag" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                                                    <span>Kolom / Atribut</span>
                                                </div>
                                            </th>
                                            <th style="padding:12px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;width:35%;border-right:1px solid var(--color-hairline);vertical-align:middle;">
                                                <div style="display:flex;align-items:center;gap:6px;color:#be123c;">
                                                    <span style="width:7px;height:7px;border-radius:50%;background:#ef4444;flex-shrink:0;"></span>
                                                    <span>Nilai Sebelum (Lama)</span>
                                                </div>
                                            </th>
                                            <th style="padding:12px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;width:35%;vertical-align:middle;">
                                                <div style="display:flex;align-items:center;gap:6px;color:#047857;">
                                                    <span style="width:7px;height:7px;border-radius:50%;background:#10b981;flex-shrink:0;"></span>
                                                    <span>Nilai Sesudah (Baru)</span>
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="row in diffRows" :key="row.key">
                                            <tr style="border-bottom:1px solid var(--color-hairline);transition:background-color 0.15s ease;">
                                                
                                                <!-- Kolom 1: Kolom / Atribut -->
                                                <td style="padding:12px 16px;border-right:1px solid var(--color-hairline);vertical-align:middle;background:var(--color-canvas-soft);width:30%;">
                                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                                                        <span class="font-mono" style="font-weight:700;font-size:12px;color:var(--color-ink);" x-text="row.key"></span>
                                                        <template x-if="row.isProtected">
                                                            <span class="badge" style="font-size:9.5px;font-weight:700;padding:2px 6px;border-radius:4px;background:#fffbeb;color:#92400e;border:1px solid #fde68a;">
                                                                TERPROTEKSI
                                                            </span>
                                                        </template>
                                                        <template x-if="!row.isProtected && row.isChanged">
                                                            <span class="badge" style="font-size:9.5px;font-weight:700;padding:2px 6px;border-radius:4px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">
                                                                DIUBAH
                                                            </span>
                                                        </template>
                                                        <template x-if="!row.isChanged">
                                                            <span class="badge" style="font-size:9.5px;font-weight:600;padding:2px 6px;border-radius:4px;background:var(--color-canvas);color:var(--color-ink-mute);border:1px solid var(--color-hairline);">
                                                                SAMA
                                                            </span>
                                                        </template>
                                                    </div>
                                                </td>

                                                <!-- Kolom 2: Nilai Sebelum (Lama) -->
                                                <td style="padding:12px 16px;border-right:1px solid var(--color-hairline);vertical-align:middle;width:35%;">
                                                    <!-- Terproteksi -->
                                                    <template x-if="row.isProtected">
                                                        <span style="display:inline-flex;align-items:center;gap:5px;background:#fff1f2;color:#be123c;border:1px solid #fecdd3;padding:4px 9px;border-radius:6px;font-family:var(--font-mono);font-size:11.5px;font-weight:600;">
                                                            <i data-lucide="lock" style="width:11px;height:11px;"></i>
                                                            <span>[TERPROTEKSI] (Lama)</span>
                                                        </span>
                                                    </template>

                                                    <!-- Nilai Berubah Biasa -->
                                                    <template x-if="!row.isProtected && row.isChanged">
                                                        <span style="display:inline-flex;align-items:center;gap:6px;background:#fff1f2;color:#be123c;border:1px solid #fecdd3;padding:4px 10px;border-radius:6px;font-family:var(--font-mono);font-size:12px;word-break:break-all;max-width:100%;">
                                                            <i data-lucide="minus" style="width:11px;height:11px;flex-shrink:0;stroke-width:2.5;"></i>
                                                            <span x-text="(row.before === null || row.before === '' || row.before === 'null') ? '(kosong)' : row.before"></span>
                                                        </span>
                                                    </template>

                                                    <!-- Nilai Tidak Berubah -->
                                                    <template x-if="!row.isChanged">
                                                        <span class="font-mono" style="color:var(--color-ink-mute);font-size:12px;" x-text="row.before ?? '—'"></span>
                                                    </template>
                                                </td>

                                                <!-- Kolom 3: Nilai Sesudah (Baru) -->
                                                <td style="padding:12px 16px;vertical-align:middle;width:35%;">
                                                    <!-- Terproteksi -->
                                                    <template x-if="row.isProtected">
                                                        <span style="display:inline-flex;align-items:center;gap:5px;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;padding:4px 9px;border-radius:6px;font-family:var(--font-mono);font-size:11.5px;font-weight:600;">
                                                            <i data-lucide="shield-check" style="width:11px;height:11px;"></i>
                                                            <span>[TERPROTEKSI] (Diperbarui)</span>
                                                        </span>
                                                    </template>

                                                    <!-- Nilai Berubah Biasa -->
                                                    <template x-if="!row.isProtected && row.isChanged">
                                                        <span style="display:inline-flex;align-items:center;gap:6px;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;padding:4px 10px;border-radius:6px;font-family:var(--font-mono);font-size:12px;font-weight:600;word-break:break-all;max-width:100%;">
                                                            <i data-lucide="plus" style="width:11px;height:11px;flex-shrink:0;stroke-width:2.5;"></i>
                                                            <span x-text="(row.after === null || row.after === '' || row.after === 'null') ? '(dihapus / kosong)' : row.after"></span>
                                                        </span>
                                                    </template>

                                                    <!-- Nilai Tidak Berubah -->
                                                    <template x-if="!row.isChanged">
                                                        <span class="font-mono" style="color:var(--color-ink-mute);font-size:12px;" x-text="row.after ?? '—'"></span>
                                                    </template>
                                                </td>

                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Tab 2: Raw JSON -->
                <div x-show="activeDiffTab === 'raw'">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div style="padding:14px;border-radius:12px;border:1px solid var(--color-hairline);background:var(--color-canvas-soft);">
                            <div style="font-size:11.5px;font-weight:800;text-transform:uppercase;color:#e11d48;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                                <i data-lucide="history" style="width:14px;height:14px;"></i>
                                <span>Data Sebelum (Before Payload)</span>
                            </div>
                            <pre style="font-size:11.5px;font-family:var(--font-mono);background:var(--color-canvas);padding:12px;border-radius:8px;border:1px solid var(--color-hairline);overflow:auto;max-height:280px;color:var(--color-ink);white-space:pre-wrap;margin:0;line-height:1.5;" 
                                 x-text="formatJson(selectedLog?.data_sebelum) || 'Tidak ada data sebelum'"></pre>
                        </div>
                        <div style="padding:14px;border-radius:12px;border:1px solid rgba(16,185,129,0.3);background:rgba(16,185,129,0.03);">
                            <div style="font-size:11.5px;font-weight:800;text-transform:uppercase;color:#059669;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                                <i data-lucide="check-circle" style="width:14px;height:14px;"></i>
                                <span>Data Sesudah (After Payload)</span>
                            </div>
                            <pre style="font-size:11.5px;font-family:var(--font-mono);background:var(--color-canvas);padding:12px;border-radius:8px;border:1px solid rgba(16,185,129,0.3);overflow:auto;max-height:280px;color:#047857;white-space:pre-wrap;margin:0;line-height:1.5;" 
                                 x-text="formatJson(selectedLog?.data_sesudah) || 'Tidak ada data sesudah'"></pre>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 4. MODAL FOOTER -->
            <div style="padding:14px 22px;border-top:1px solid var(--color-hairline);background:var(--color-canvas-soft);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                <div style="font-size:11.5px;color:var(--color-ink-mute);display:flex;align-items:center;gap:6px;">
                    <i data-lucide="shield-check" style="width:14px;height:14px;color:#10b981;"></i>
                    <span>Audit Trail terverifikasi &amp; tersimpan aman di database</span>
                </div>
                <button type="button" @click="showDiffModal = false" class="btn btn-secondary btn-sm" style="height:36px;padding:0 20px;font-size:12px;font-weight:700;border-radius:8px;">
                    Tutup
                </button>
            </div>

        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- 6. MODAL PEMBERSIHAN & RETENSI LOG (KHUSUS DEVELOPER + PW AUTH)          -->
    <!-- ========================================================================= -->
    <?php if ($isDeveloper): ?>
    <template x-teleport="body">
    <div x-show="showPruneModal" x-cloak class="modal-backdrop" style="background:rgba(15,23,42,0.65);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:16px;z-index:9999;">
        <div class="modal-box" style="max-width:480px;width:94vw;border-radius:20px;padding:24px;background:var(--color-canvas);border:1.5px solid var(--color-hairline);box-shadow:0 25px 50px -12px rgba(15,23,42,0.25);" @click.stop>
            
            <form method="POST" action="<?= Router::url('/settings/activity-logs/prune') ?>">
                <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">

                <!-- Modal Header Standar ERP -->
                <div class="modal-header" style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px;padding-bottom:16px;border-bottom:1.5px solid var(--color-hairline);gap:12px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:42px;height:42px;border-radius:12px;background:#fff1f2;color:#e11d48;border:1px solid #fecdd3;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="shield-alert" style="width:22px;height:22px;"></i>
                        </div>
                        <div>
                            <h4 style="font-size:1.05rem;font-weight:700;color:var(--color-ink);margin:0 0 2px 0;">Pembersihan &amp; Retensi Log Usang</h4>
                            <p style="font-size:0.8rem;color:var(--color-ink-mute);margin:0;">Fitur proteksi developer untuk merampingkan database</p>
                        </div>
                    </div>
                </div>

                <!-- Alert Callout Box -->
                <div style="padding:12px 14px;border-radius:10px;background:#fffbeb;border:1px solid #fde68a;display:flex;align-items:flex-start;gap:10px;font-size:12px;color:#92400e;line-height:1.5;margin-bottom:16px;">
                    <i data-lucide="alert-triangle" style="width:16px;height:16px;color:#d97706;flex-shrink:0;margin-top:2px;"></i>
                    <div>
                        <strong>Penting:</strong> Seluruh rekaman audit trail yang lebih tua dari batas retensi yang dipilih akan <strong>dihapus permanen</strong> dari basis data PostgreSQL.
                    </div>
                </div>

                <!-- Pilihan Batas Hari (Concise & Mobile-friendly) -->
                <div style="margin-bottom:14px;">
                    <label class="form-label" style="font-size:12px;font-weight:600;display:block;margin-bottom:6px;">
                        Pilih Batas Usia Log yang Dihapus:
                    </label>
                    <select name="days" class="form-input" style="height:38px;font-size:12.5px;width:100%;border-radius:8px;">
                        <option value="30">Lebih dari 30 hari yang lalu</option>
                        <option value="90" selected>Lebih dari 90 hari (Direkomendasikan)</option>
                        <option value="180">Lebih dari 180 hari (6 bulan)</option>
                        <option value="365">Lebih dari 365 hari (1 tahun)</option>
                    </select>
                </div>

                <!-- Konfirmasi Kata Sandi Developer -->
                <div style="margin-bottom:20px;">
                    <label class="form-label" style="font-size:12px;font-weight:600;display:block;margin-bottom:6px;">
                        Konfirmasi Kata Sandi Developer: <span style="color:#e11d48;">*</span>
                    </label>
                    <div class="relative">
                        <i data-lucide="lock" style="width:14px;height:14px;color:var(--color-ink-mute);position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none;"></i>
                        <input type="password" 
                               name="developer_password" 
                               required 
                               placeholder="Masukkan kata sandi akun Developer..." 
                               class="form-input" 
                               style="height:38px;padding-left:36px;font-size:12.5px;width:100%;border-radius:8px;">
                    </div>
                    <span style="font-size:11px;color:var(--color-ink-mute);margin-top:5px;display:block;">
                        Kata sandi akan diverifikasi secara aman ke database sebelum perintah delete dieksekusi.
                    </span>
                </div>

                <!-- Actions Footer -->
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;padding-top:16px;border-top:1px solid var(--color-hairline);">
                    <button type="button" @click="showPruneModal = false" class="btn btn-secondary btn-sm" style="height:36px;padding:0 16px;font-weight:600;font-size:12px;border-radius:8px;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-sm" style="height:36px;padding:0 16px;font-weight:700;font-size:12px;border-radius:8px;background:#e11d48;color:#ffffff;border:1px solid #be123c;display:inline-flex;align-items:center;gap:6px;">
                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                        <span>Konfirmasi &amp; Bersihkan</span>
                    </button>
                </div>

            </form>

        </div>
    </div>
    </template>
    <?php endif; ?>

</div>

<script>
function activityLogApp() {
    return {
        showDiffModal: false,
        showPruneModal: false,
        showAdvancedFilter: false,
        activeDiffTab: 'table',
        selectedLog: null,
        diffRows: [],
        copiedJson: false,

        init() {
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        openDiffModal(log) {
            this.selectedLog = log;
            this.activeDiffTab = 'table';
            this.copiedJson = false;
            this.diffRows = this.buildDiffRows(log.data_sebelum, log.data_sesudah);
            this.showDiffModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        openPruneModal() {
            this.showPruneModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        buildDiffRows(rawBefore, rawAfter) {
            const before = this.parseObject(rawBefore) || {};
            const after = this.parseObject(rawAfter) || {};

            const keys = Array.from(new Set([...Object.keys(before), ...Object.keys(after)]));
            return keys.map(key => {
                const valBefore = before[key] !== undefined ? this.stringifyValue(before[key]) : null;
                const valAfter = after[key] !== undefined ? this.stringifyValue(after[key]) : null;
                const isProtected = (valBefore === '[TERPROTEKSI]' || valAfter === '[TERPROTEKSI]');
                const isChanged = (valBefore !== valAfter) || isProtected;
                return {
                    key: key,
                    before: valBefore,
                    after: valAfter,
                    isChanged: isChanged,
                    isProtected: isProtected
                };
            });
        },

        parseObject(val) {
            if (!val) return null;
            if (typeof val === 'object') return val;
            try {
                return JSON.parse(val);
            } catch (e) {
                return { value: val };
            }
        },

        stringifyValue(val) {
            if (val === null || val === undefined) return 'null';
            if (typeof val === 'object') return JSON.stringify(val);
            if (typeof val === 'boolean') return val ? 'true' : 'false';
            return String(val);
        },

        formatJson(raw) {
            if (!raw) return null;
            if (typeof raw === 'object') {
                return JSON.stringify(raw, null, 2);
            }
            try {
                const parsed = JSON.parse(raw);
                return JSON.stringify(parsed, null, 2);
            } catch (e) {
                return String(raw);
            }
        },

        formatDateShort(datetime) {
            if (!datetime) return '-';
            const str = String(datetime);
            const m = str.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2}):(\d{2})/);
            if (m) {
                return `${m[3]}/${m[2]}/${m[1]} ${m[4]}:${m[5]}:${m[6]} WIB`;
            }
            return str.substring(0, 19);
        },

        getActionBadgeStyle(action) {
            if (!action) return 'background:#f1f5f9;color:#475569;';
            const act = String(action).toUpperCase();
            if (act.includes('CREATE') || act.includes('INSERT') || act.includes('TAMBAH')) return 'background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;';
            if (act.includes('UPDATE') || act.includes('EDIT') || act.includes('UBAH')) return 'background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;';
            if (act.includes('DELETE') || act.includes('HAPUS') || act.includes('VOID') || act.includes('CANCEL') || act.includes('BATAL')) return 'background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;';
            if (act.includes('SYNC') || act.includes('IMPOR')) return 'background:#f0fdfa;color:#0f766e;border:1px solid #99f6e4;';
            if (act.includes('PRICE') || act.includes('HARGA')) return 'background:#fffbeb;color:#b45309;border:1px solid #fde68a;';
            if (act.includes('LOGIN')) return 'background:#faf5ff;color:#7e22ce;border:1px solid #e9d5ff;';
            if (act.includes('APPROVE')) return 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;';
            if (act.includes('PRUNE')) return 'background:#fff1f2;color:#be123c;border:1px solid #fecdd3;';
            return 'background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;';
        },

        copyJson() {
            const payload = {
                aktivitas: this.selectedLog?.deskripsi_aktivitas,
                aktor: this.selectedLog?.nama_aktor,
                peran: this.selectedLog?.peran_aktor,
                waktu: this.selectedLog?.waktu_kejadian,
                sebelum: this.parseObject(this.selectedLog?.data_sebelum),
                sesudah: this.parseObject(this.selectedLog?.data_sesudah)
            };
            navigator.clipboard.writeText(JSON.stringify(payload, null, 2)).then(() => {
                this.copiedJson = true;
                setTimeout(() => { this.copiedJson = false; }, 2500);
            });
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

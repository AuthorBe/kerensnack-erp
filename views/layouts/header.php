<?php
use App\Core\Auth;
use App\Core\Router;
use App\Helpers\Format;

// Dynamic page title & subtitle with route fallbacks
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$headerTitle = $pageTitle ?? match(true) {
    str_contains($uri, '/dashboard') || $uri === '/' => 'Dashboard Utama',
    str_contains($uri, '/pos')              => 'Kasir POS',
    str_contains($uri, '/customer-orders')  => 'Pesanan Pelanggan',
    str_contains($uri, '/pricing')          => 'Matriks Harga Jual & Tier',
    str_contains($uri, '/inventory')        => 'Katalog & Stok Fisik',
    str_contains($uri, '/products')         => 'Master Produk & Resep BOM',
    str_contains($uri, '/purchases')        => 'Pembelian & Faktur Vendor',
    str_contains($uri, '/deliveries')       => 'Logistik & Surat Jalan',
    str_contains($uri, '/consignment/sales')=> 'Sales Konsinyasi Mobile',
    str_contains($uri, '/consignment')      => 'Konsinyasi Rak Toko',
    str_contains($uri, '/cash/transactions')=> 'Kas Masuk & Kas Keluar',
    str_contains($uri, '/cash/reports')     => 'Laporan Arus Kas & Valuasi',
    str_contains($uri, '/cash')             => 'Buku Kas & Rekening Bank',
    str_contains($uri, '/customers')        => 'Master Toko Pelanggan',
    str_contains($uri, '/suppliers')        => 'Master Pemasok Vendor',
    str_contains($uri, '/employees')        => 'Master Data Karyawan',
    str_contains($uri, '/owner')            => 'Owner Executive Dashboard',
    str_contains($uri, '/developer/architecture') => 'System Blueprint',
    str_contains($uri, '/users')            => 'Manajemen Pengguna',
    str_contains($uri, '/permissions')      => 'Hak Akses & Izin RBAC',
    str_contains($uri, '/settings/company')  => 'Informasi Perusahaan',
    str_contains($uri, '/settings')         => 'Pengaturan Sistem',
    str_contains($uri, '/profile')          => 'Profil Pengguna',
    default                                 => 'Keren One',
};

$headerSub = $pageSubtitle ?? match(true) {
    str_contains($uri, '/dashboard') || $uri === '/' => 'Pusat Kerja Harian & Navigasi Cepat',
    str_contains($uri, '/pos')              => 'Layar Transaksi Kasir POS',
    str_contains($uri, '/customer-orders')  => 'Faktur Penjualan Reguler',
    str_contains($uri, '/pricing')          => '30 Level Harga & Grup Mitra',
    str_contains($uri, '/inventory')        => 'Monitoring Stok Gudang Realtime',
    str_contains($uri, '/products')         => 'Barang Jadi, Bahan & BOM',
    str_contains($uri, '/purchases')        => 'Pengadaan Bahan & PO Vendor',
    str_contains($uri, '/deliveries')       => 'Manifest Rute Pengiriman & Surat Jalan',
    str_contains($uri, '/consignment/sales')=> 'Kunjungan Toko, Opname Rak & Kiriman Titip',
    str_contains($uri, '/consignment/tagihan')    => 'Buat Tagihan & Kelola Piutang Konsinyasi',
    str_contains($uri, '/consignment')      => 'Titip Jual Rak & Opname',
    str_contains($uri, '/cash/transactions')=> 'Mutasi Operasional & Beban',
    str_contains($uri, '/cash/reports')     => 'Analisis Cash Flow Masuk-Keluar',
    str_contains($uri, '/cash')             => 'Kelola Saldo Kas & Bank',
    str_contains($uri, '/customers')        => 'Data Toko, Rute & Tier',
    str_contains($uri, '/suppliers')        => 'Vendor Bahan & Kemasan',
    str_contains($uri, '/employees')        => 'Data Pegawai & Tim Borongan',
    str_contains($uri, '/owner')            => 'Pusat Analisis Performa Finansial & Bisnis Perusahaan',
    str_contains($uri, '/developer/architecture') => 'Peta Arsitektur & AI Prompt Generator',
    str_contains($uri, '/users')            => 'Kelola Akun, Karyawan & Suspend Akses',
    str_contains($uri, '/permissions')      => 'Pusat Konfigurasi Izin & Matriks Role',
    str_contains($uri, '/settings/company')  => 'Konfigurasi Identitas Resmi Usaha, Kontak & Kop Dokumen Cetak',
    str_contains($uri, '/settings')         => 'Pusat Manajemen Konfigurasi Aplikasi & Hak Akses',
    str_contains($uri, '/profile')          => 'Pengaturan Akun & Keamanan',
    default                                 => '',
};
?>
<header class="app-header">
    <div class="header-left">
        <!-- Hamburger (Mobile) -->
        <button class="header-burger"
                @click="sidebarOpen ? closeNav() : openNav()"
                aria-label="Toggle navigation">
            <i data-lucide="menu"></i>
        </button>

        <!-- Page Title -->
        <div style="min-width:0;">
            <div class="header-page-title"><?= htmlspecialchars($headerTitle) ?></div>
            <?php if ($headerSub): ?>
            <div class="header-subtitle hide-mobile"><?= htmlspecialchars($headerSub) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="header-right">
        <?php if (\App\Core\Auth::isDeveloper()): 
            $dbInfo = \Database::getConnectionInfo();
            $isLocal = $dbInfo['is_local'];
        ?>
        <!-- Developer Database Status Pill & Popover (Khusus Role Developer) -->
        <div class="relative" x-data="{ openDbModal: false }" @click.outside="openDbModal = false" style="position:relative;">
            <button type="button" 
                    @click="openDbModal = !openDbModal"
                    class="header-db-badge <?= $isLocal ? 'db-badge-local' : 'db-badge-live' ?>"
                    title="Klik untuk melihat detail koneksi database (Khusus Developer)">
                <span class="db-badge-dot <?= $isLocal ? 'dot-local' : 'dot-live' ?>"></span>
                <span class="db-badge-text font-medium text-xs">
                    <?= $isLocal ? 'LOCAL DB' : 'LIVE SUPABASE' ?>
                </span>
                <span class="db-badge-ping text-[10px] opacity-75 hide-mobile">
                    (<?= $dbInfo['ping_ms'] >= 0 ? $dbInfo['ping_ms'] . 'ms' : 'ERR' ?>)
                </span>
            </button>

            <!-- Popover Details (Modern, Spacious & Responsive DNA Keren One) -->
            <div x-show="openDbModal" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                 class="header-db-popover">
                
                <!-- Popover Header Bar -->
                <div style="padding:12px 16px; display:flex; align-items:center; justify-content:space-between; gap:10px; <?= $isLocal ? 'background:rgba(16,185,129,0.08); border-bottom:1px solid rgba(16,185,129,0.22);' : 'background:rgba(225,29,72,0.08); border-bottom:1px solid rgba(225,29,72,0.22);' ?>">
                    <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                        <span class="db-badge-dot <?= $isLocal ? 'dot-local' : 'dot-live' ?>" style="flex-shrink:0;"></span>
                        <strong style="font-size:12px; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; <?= $isLocal ? 'color:#065f46;' : 'color:#9f1239;' ?>">
                            <?= $isLocal ? 'PostgreSQL Local Sandbox' : 'Supabase Production Live' ?>
                        </strong>
                    </div>
                    <span style="font-size:9.5px; font-weight:800; font-family:var(--font-mono, monospace); padding:2px 8px; border-radius:6px; flex-shrink:0; <?= $isLocal ? 'background:rgba(16,185,129,0.15); color:#065f46; border:1px solid rgba(16,185,129,0.3);' : 'background:rgba(225,29,72,0.15); color:#9f1239; border:1px solid rgba(225,29,72,0.3);' ?>">
                        <?= $dbInfo['is_healthy'] ? 'CONNECTED' : 'FAILED' ?>
                    </span>
                </div>

                <!-- Popover Info Body -->
                <div style="padding:14px 16px; display:flex; flex-direction:column; gap:9px;">
                    <!-- Host Row -->
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; font-size:12px;">
                        <span style="color:var(--color-ink-mute, #64748b); font-weight:600; flex-shrink:0;">Host:</span>
                        <span style="font-family:var(--font-mono, monospace); font-weight:700; color:var(--color-ink, #0f172a); font-size:11.5px; text-align:right; word-break:break-all;" title="<?= htmlspecialchars($dbInfo['host']) ?>">
                            <?= htmlspecialchars($dbInfo['host']) ?>
                        </span>
                    </div>

                    <!-- Database Row -->
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; font-size:12px;">
                        <span style="color:var(--color-ink-mute, #64748b); font-weight:600; flex-shrink:0;">Database:</span>
                        <span style="font-family:var(--font-mono, monospace); font-weight:800; color:var(--color-primary, #2563eb); font-size:12px; text-align:right; word-break:break-all;">
                            <?= htmlspecialchars($dbInfo['database']) ?>
                        </span>
                    </div>

                    <!-- Port Row -->
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; font-size:12px;">
                        <span style="color:var(--color-ink-mute, #64748b); font-weight:600; flex-shrink:0;">Port:</span>
                        <span style="font-family:var(--font-mono, monospace); font-weight:700; color:var(--color-ink, #0f172a); font-size:12px;">
                            <?= htmlspecialchars($dbInfo['port']) ?>
                        </span>
                    </div>

                    <!-- User Row -->
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; font-size:12px;">
                        <span style="color:var(--color-ink-mute, #64748b); font-weight:600; flex-shrink:0;">User:</span>
                        <span style="font-family:var(--font-mono, monospace); font-weight:600; color:var(--color-ink, #0f172a); font-size:11px; text-align:right; word-break:break-all;">
                            <?= htmlspecialchars($dbInfo['user']) ?>
                        </span>
                    </div>

                    <!-- Latency Row -->
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; font-size:12px; padding-top:4px; border-top:1px solid var(--color-hairline, #e2e8f0);">
                        <span style="color:var(--color-ink-mute, #64748b); font-weight:600; flex-shrink:0;">Latency / Ping:</span>
                        <span style="font-family:var(--font-mono, monospace); font-weight:700; font-size:11px; padding:2px 8px; border-radius:6px; background:var(--color-canvas-soft, #f1f5f9); color:var(--color-ink, #0f172a); border:1px solid var(--color-hairline, #e2e8f0);">
                            <?= $dbInfo['ping_ms'] >= 0 ? $dbInfo['ping_ms'] . ' ms' : 'Error' ?>
                        </span>
                    </div>

                    <!-- Status Warning Notice Box -->
                    <div style="margin-top:4px; padding:10px 12px; border-radius:10px; font-size:11.5px; line-height:1.5; display:flex; align-items:flex-start; gap:8px; <?= $isLocal ? 'background:rgba(16,185,129,0.06); border:1px solid rgba(16,185,129,0.25); color:#065f46;' : 'background:rgba(225,29,72,0.06); border:1px solid rgba(225,29,72,0.25); color:#9f1239;' ?>">
                        <i data-lucide="<?= $isLocal ? 'shield-check' : 'alert-triangle' ?>" style="width:16px; height:16px; flex-shrink:0; margin-top:1px; <?= $isLocal ? 'color:#10b981;' : 'color:#e11d48;' ?>"></i>
                        <div>
                            <?php if ($isLocal): ?>
                                <strong>SANDBOX MODE AMAN</strong>: Seluruh transaksi tersimpan di PostgreSQL lokal (0% menyentuh cloud).
                            <?php else: ?>
                                <strong>PERINGATAN LIVE</strong>: Terhubung ke Cloud Supabase. Segala transaksi adalah data riil operasional!
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Popover Footer Action Button -->
                <div style="padding:12px 16px; border-top:1px solid var(--color-hairline, #e2e8f0); background:var(--color-canvas-soft, #f8fafc);">
                    <a href="<?= \App\Core\Router::url('/developer/database') ?>" 
                       class="btn btn-primary" 
                       style="width:100%; display:flex; align-items:center; justify-content:center; gap:8px; font-size:12px; font-weight:700; padding:9px 14px; border-radius:8px; text-decoration:none;">
                        <i data-lucide="settings" style="width:14px; height:14px;"></i>
                        <span>Buka Database Manager</span>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Panduan SOP & Alur Kerja Lapangan (Buka di Tab Baru) -->
        <a href="<?= \App\Core\Router::url('/guide') ?>"
           target="_blank"
           rel="noopener noreferrer"
           class="header-theme-btn header-guide-btn"
           title="Buka Buku Panduan Operasional & SOP di Tab Baru (Shift + ?)"
           aria-label="Buka Panduan SOP">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
            </svg>
        </a>

        <!-- Theme Toggle Only -->
        <button type="button"
                class="header-theme-btn"
                @click="toggleTheme()"
                :title="isDark ? 'Switch ke Mode Terang' : 'Switch ke Mode Gelap'"
                aria-label="Toggle theme">
            <!-- Sun Icon (Tampil di Dark Mode) -->
            <svg class="theme-icon-sun" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="4"></circle>
                <path d="M12 2v2"></path>
                <path d="M12 20v2"></path>
                <path d="m4.93 4.93 1.41 1.41"></path>
                <path d="m17.66 17.66 1.41 1.41"></path>
                <path d="M2 12h2"></path>
                <path d="M20 12h2"></path>
                <path d="m6.34 17.66-1.41 1.41"></path>
                <path d="m19.07 4.93-1.41 1.41"></path>
            </svg>
            <!-- Moon Icon (Tampil di Light Mode) -->
            <svg class="theme-icon-moon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>
            </svg>
        </button>
    </div>
</header>

<style>
/* Developer DB Indicator Badge */
.header-db-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border-width: 1px;
    border-style: solid;
    outline: none;
    line-height: 1.2;
}
.header-db-badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
}
.db-badge-local {
    background-color: #ecfdf5;
    color: #065f46;
    border-color: #a7f3d0;
}
.dark .db-badge-local {
    background-color: rgba(6, 95, 70, 0.25);
    color: #34d399;
    border-color: rgba(52, 211, 153, 0.3);
}
.db-badge-live {
    background-color: #fff1f2;
    color: #9f1239;
    border-color: #fecdd3;
    animation: pulseLiveBadge 2.5s infinite ease-in-out;
}
.dark .db-badge-live {
    background-color: rgba(159, 18, 57, 0.25);
    color: #fb7185;
    border-color: rgba(251, 113, 133, 0.3);
}
.db-badge-dot {
    width: 7px;
    height: 7px;
    border-radius: 9999px;
    display: inline-block;
}
.dot-local {
    background-color: #10b981;
    box-shadow: 0 0 6px #10b981;
}
.dot-live {
    background-color: #e11d48;
    box-shadow: 0 0 6px #e11d48;
}
@keyframes pulseLiveBadge {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.82; }
}

/* Developer Database Popover (Desktop Default) */
.header-db-popover {
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    z-index: 999;
    width: 360px;
    max-width: 380px;
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline-strong, #cbd5e1);
    border-radius: 16px;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.18), 0 0 0 1px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    color: var(--color-ink, #1e293b);
}
.dark .header-db-popover {
    background: #1e1e2d;
    border-color: #334155;
    color: #f1f5f9;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05);
}

/* Mobile Popover Adaptive Viewport Positioning */
@media (max-width: 640px) {
    .header-db-popover {
        position: fixed !important;
        top: 58px !important;
        left: 12px !important;
        right: 12px !important;
        width: auto !important;
        min-width: 0 !important;
        max-width: 390px !important;
        margin: 0 auto !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4), 0 0 0 100vw rgba(15, 23, 42, 0.45) !important;
        z-index: 9999 !important;
    }
}
</style>


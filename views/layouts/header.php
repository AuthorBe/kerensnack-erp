<?php
use App\Core\Auth;
use App\Helpers\Format;

// Dynamic page title & subtitle with route fallbacks
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$headerTitle = $pageTitle ?? match(true) {
    str_contains($uri, '/pos')              => 'Kasir POS',
    str_contains($uri, '/customer-orders')  => 'Pesanan Pelanggan',
    str_contains($uri, '/sales-orders')     => 'Penjualan Toko',
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
    str_contains($uri, '/owner')            => 'Owner Command Center',
    str_contains($uri, '/developer/architecture') => 'System Blueprint',
    str_contains($uri, '/users')            => 'Manajemen Pengguna',
    str_contains($uri, '/permissions')      => 'Hak Akses & Izin RBAC',
    str_contains($uri, '/profile')          => 'Profil Pengguna',
    default                                 => 'Keren Snack ERP',
};

$headerSub = $pageSubtitle ?? match(true) {
    str_contains($uri, '/pos')              => 'Layar Transaksi Kasir POS',
    str_contains($uri, '/customer-orders')  => 'Faktur Penjualan B2B',
    str_contains($uri, '/sales-orders')     => 'Faktur Penjualan B2B',
    str_contains($uri, '/pricing')          => '28 Level Harga & Grup Mitra',
    str_contains($uri, '/inventory')        => 'Monitoring Stok Gudang Realtime',
    str_contains($uri, '/products')         => 'Barang Jadi, Bahan & BOM',
    str_contains($uri, '/purchases')        => 'Pengadaan Bahan & PO Vendor',
    str_contains($uri, '/deliveries')       => 'Manifest Rute Sales-Driver',
    str_contains($uri, '/consignment/sales')=> 'Kunjungan Toko, Opname Rak & Kiriman Titip',
    str_contains($uri, '/consignment')      => 'Titip Jual Rak & Opname',
    str_contains($uri, '/cash/transactions')=> 'Mutasi Operasional & Beban',
    str_contains($uri, '/cash/reports')     => 'Analisis Cash Flow Masuk-Keluar',
    str_contains($uri, '/cash')             => 'Kelola Saldo Kas & Bank',
    str_contains($uri, '/customers')        => 'Data Toko, Rute & Tier',
    str_contains($uri, '/suppliers')        => 'Vendor Bahan & Kemasan',
    str_contains($uri, '/employees')        => 'Data Pegawai & Tim Borongan',
    str_contains($uri, '/owner')            => 'Overview Operasional Bisnis',
    str_contains($uri, '/developer/architecture') => 'Peta Arsitektur & AI Prompt Generator',
    str_contains($uri, '/users')            => 'Kelola Akun, Karyawan & Suspend Akses',
    str_contains($uri, '/permissions')      => 'Pusat Konfigurasi Izin & Matriks Role',
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
        <!-- Date Chip -->
        <div class="header-chip hide-mobile" style="display:inline-flex;align-items:center;">
            <i data-lucide="calendar" style="width:14px;height:14px;flex-shrink:0;"></i>
            <span style="white-space:nowrap;line-height:1;"><?= Format::tanggal(date('Y-m-d'), false) ?></span>
        </div>

        <!-- Role Chip -->
        <div class="header-chip is-role">
            <i data-lucide="shield" style="width:14px;height:14px;flex-shrink:0;"></i>
            <span style="white-space:nowrap;line-height:1;"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', Auth::role() ?? 'guest'))) ?></span>
        </div>

        <!-- Theme Toggle -->
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


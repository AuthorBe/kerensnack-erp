<?php
use App\Core\Auth;
use App\Core\Router;

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$base        = Router::getBasePath();

// Helper: decide if a route is the active one
if (!function_exists('isActive')) {
    function isActive(string $route, string $currentPath, string $base): bool
    {
        $url = rtrim($base . $route, '/');
        return rtrim($currentPath, '/') === $url;
    }
}

// Helper: breadcrumb-style "active starts with"
if (!function_exists('isActiveSection')) {
    function isActiveSection(string $prefix, string $currentPath, string $base): bool
    {
        return str_starts_with(rtrim($currentPath, '/'), rtrim($base . $prefix, '/'));
    }
}
?>
<aside id="app-sidebar"
       class="sidebar"
       :class="{ 'is-open': sidebarOpen }">

    <!-- Brand Header -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon" style="background:transparent;border:none;box-shadow:none;display:flex;align-items:center;justify-content:center;">
            <img src="<?= Router::asset('/favicon/favicon-96x96.png') ?>" alt="Logo Keren Snack" style="width:28px;height:28px;object-fit:contain;border-radius:6px;display:block;">
        </div>
        <div>
            <div class="sidebar-brand-name">KEREN SNACK</div>
        </div>
        <span class="sidebar-brand-badge ml-auto">ERP</span>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav no-scrollbar">

        <!-- PENJUALAN & TRANSAKSI -->
        <?php if (Auth::can(['pos.pos', 'orders.po_view_all', 'orders.po_view_assigned', 'orders.view_all', 'orders.view_assigned', 'consignment.view_all', 'consignment.view_assigned'])): ?>
        <div class="sidebar-section-label">Penjualan &amp; Transaksi</div>

        <?php if (Auth::can('pos.pos')): ?>
        <a href="<?= Router::url('/pos') ?>"
           class="sidebar-link <?= isActive('/pos', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="shopping-cart"></i>
            <span>Kasir POS</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can(['orders.po_view_all', 'orders.po_view_assigned'])): ?>
        <a href="<?= Router::url('/customer-orders/po-list') ?>"
           class="sidebar-link <?= ($currentPath === $base . '/customer-orders/po-list' || $currentPath === '/customer-orders/po-list') ? 'is-active' : '' ?>">
            <i data-lucide="inbox"></i>
            <span>Daftar PO</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can(['orders.view_all', 'orders.view_assigned'])): ?>
        <a href="<?= Router::url('/customer-orders') ?>"
           class="sidebar-link <?= (($currentPath !== $base . '/customer-orders/po-list' && $currentPath !== '/customer-orders/po-list') && (isActiveSection('/customer-orders', $currentPath, $base) || isActiveSection('/sales-orders', $currentPath, $base))) ? 'is-active' : '' ?>">
            <i data-lucide="clipboard-list"></i>
            <span>Pesanan Pelanggan</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can(['consignment.view_all', 'consignment.view_assigned'])): ?>
        <a href="<?= Router::url('/consignment') ?>"
           class="sidebar-link <?= isActiveSection('/consignment', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="store"></i>
            <span>Konsinyasi</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- DELIVERY & LOGISTIK -->
        <?php if (Auth::can(['deliveries.view_all', 'deliveries.view_assigned'])): ?>
        <div class="sidebar-section-label">Delivery</div>

        <a href="<?= Router::url('/driver-deliveries') ?>"
           class="sidebar-link <?= isActiveSection('/driver-deliveries', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="truck"></i>
            <span>Pengiriman</span>
        </a>

        <a href="<?= Router::url('/deliveries') ?>"
           class="sidebar-link <?= (isActiveSection('/deliveries', $currentPath, $base) && !isActiveSection('/driver-deliveries', $currentPath, $base)) ? 'is-active' : '' ?>">
            <i data-lucide="file-text"></i>
            <span>Surat Jalan</span>
        </a>
        <?php endif; ?>

        <!-- GUDANG & PEMBELIAN -->
        <?php if (Auth::can(['inventory.view_all', 'purchases.view'])): ?>
        <div class="sidebar-section-label">Gudang &amp; Pembelian</div>

        <?php if (Auth::can('inventory.view_all')): ?>
        <a href="<?= Router::url('/inventory') ?>"
           class="sidebar-link <?= isActiveSection('/inventory', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="boxes"></i>
            <span>Stok &amp; Persediaan</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can('purchases.view')): ?>
        <a href="<?= Router::url('/purchases') ?>"
           class="sidebar-link <?= isActiveSection('/purchases', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="file-check"></i>
            <span>Pembelian Vendor</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- KEUANGAN & KAS -->
        <?php if (Auth::can(['cash.view_all', 'cash.reports'])): ?>
        <div class="sidebar-section-label">Keuangan &amp; Kas</div>

        <?php if (Auth::can('cash.view_all')): ?>
        <a href="<?= Router::url('/cash') ?>"
           class="sidebar-link <?= isActive('/cash', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="wallet"></i>
            <span>Buku Kas &amp; Valuasi</span>
        </a>

        <a href="<?= Router::url('/cash/transactions') ?>"
           class="sidebar-link <?= isActive('/cash/transactions', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="arrow-left-right"></i>
            <span>Kas Masuk &amp; Keluar</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can('cash.reports')): ?>
        <a href="<?= Router::url('/cash/reports') ?>"
           class="sidebar-link <?= isActive('/cash/reports', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="bar-chart-3"></i>
            <span>Laporan Arus Kas</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- MASTER DATA -->
        <?php if (Auth::can(['master.products_view', 'master.pricing_view', 'master.customers_view_all', 'master.suppliers_view', 'master.employees_view'])): ?>
        <div class="sidebar-section-label">Master Data</div>

        <?php if (Auth::can('master.products_view')): ?>
        <a href="<?= Router::url('/products') ?>"
           class="sidebar-link <?= isActiveSection('/products', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="package"></i>
            <span>Produk, Bahan &amp; BOM</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can('master.pricing_view')): ?>
        <a href="<?= Router::url('/pricing') ?>"
           class="sidebar-link <?= isActiveSection('/pricing', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="tags"></i>
            <span>Matriks Level Harga</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can('master.customers_view_all')): ?>
        <a href="<?= Router::url('/customers') ?>"
           class="sidebar-link <?= isActiveSection('/customers', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="users"></i>
            <span>Toko Pelanggan &amp; Wilayah</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can('master.suppliers_view')): ?>
        <a href="<?= Router::url('/suppliers') ?>"
           class="sidebar-link <?= isActiveSection('/suppliers', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="building-2"></i>
            <span>Pemasok (Vendor)</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can('master.employees_view')): ?>
        <a href="<?= Router::url('/employees') ?>"
           class="sidebar-link <?= isActiveSection('/employees', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="contact-2"></i>
            <span>Data Karyawan</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- MANAJEMEN EKSEKUTIF -->
        <?php if (Auth::can('owner.dashboard')): ?>
        <div class="sidebar-section-label">Manajemen</div>

        <a href="<?= Router::url('/owner') ?>"
           class="sidebar-link <?= isActive('/owner', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="layout-dashboard"></i>
            <span>Executive Dashboard</span>
        </a>
        <?php endif; ?>

        <!-- SISTEM & PENGATURAN -->
        <?php if (Auth::can(['rbac.users_view', 'rbac.users_manage', 'rbac.permissions_manage', 'rbac.roles_manage', 'system.activity_log', 'system.blueprint'])): ?>
        <div class="sidebar-section-label">Sistem</div>

        <a href="<?= Router::url('/settings') ?>"
           class="sidebar-link <?= (isActive('/settings', $currentPath, $base) || isActive('/pengaturan', $currentPath, $base) || isActiveSection('/users', $currentPath, $base) || isActiveSection('/permissions', $currentPath, $base) || isActiveSection('/settings/activity-logs', $currentPath, $base)) ? 'is-active' : '' ?>">
            <i data-lucide="settings-2"></i>
            <span>Pengaturan</span>
        </a>
        <?php endif; ?>

    </nav>

    <!-- Profile Footer (Pinned Bottom) -->
    <div class="sidebar-footer">
        <div class="sidebar-profile">
            <a href="<?= Router::url('/profile') ?>" class="flex items-center gap-2.5 min-w-0 flex-1 group" style="text-decoration:none;" title="Klik untuk Pengaturan Profil & Kata Sandi">
                <div class="sidebar-avatar group-hover:scale-105 transition-transform">
                    <i data-lucide="user"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="sidebar-user-name group-hover:text-slate-200 transition-colors">
                        <?= htmlspecialchars(Auth::user()['nama_lengkap'] ?? Auth::name() ?? 'Pengguna') ?>
                    </div>
                    <div class="sidebar-user-role">
                        <?= htmlspecialchars(Auth::role() ?? '—') ?>
                    </div>
                </div>
            </a>
            <a href="<?= Router::url('/logout') ?>"
               class="sidebar-logout-btn"
               title="Keluar dari Sistem">
                <i data-lucide="log-out"></i>
            </a>
        </div>
    </div>

</aside>

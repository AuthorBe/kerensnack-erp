<?php
use App\Core\Auth;
use App\Core\Router;

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$base        = Router::getBasePath();

// Helper: decide if a route is the active one
function isActive(string $route, string $currentPath, string $base): bool
{
    $url = rtrim($base . $route, '/');
    return rtrim($currentPath, '/') === $url;
}

// Helper: breadcrumb-style "active starts with"
function isActiveSection(string $prefix, string $currentPath, string $base): bool
{
    return str_starts_with(rtrim($currentPath, '/'), rtrim($base . $prefix, '/'));
}
?>
<aside id="app-sidebar"
       class="sidebar no-scrollbar"
       :class="{ 'is-open': sidebarOpen }">

    <!-- Brand Header -->
    <a href="<?= Router::url('/') ?>" class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i data-lucide="zap"></i>
        </div>
        <div>
            <div class="sidebar-brand-name">KEREN SNACK</div>
        </div>
        <span class="sidebar-brand-badge ml-auto">ERP</span>
    </a>

    <!-- Navigation -->
    <nav class="sidebar-nav no-scrollbar">

        <?php if (Auth::isAdmin() || Auth::isOwner()): ?>
        <div class="sidebar-section-label">Penjualan</div>
        <?php endif; ?>

        <a href="<?= Router::url('/pos') ?>"
           class="sidebar-link <?= isActive('/pos', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="shopping-cart"></i>
            <span>Kasir POS</span>
        </a>

        <?php if (Auth::isAdmin() || Auth::isOwner()): ?>
        <a href="<?= Router::url('/pricing') ?>"
           class="sidebar-link <?= isActive('/pricing', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="tags"></i>
            <span>Daftar Harga</span>
        </a>

        <div class="sidebar-section-label">Inventaris</div>

        <a href="<?= Router::url('/inventory') ?>"
           class="sidebar-link <?= isActiveSection('/inventory', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="package"></i>
            <span>Stok Produk</span>
        </a>

        <a href="<?= Router::url('/consignment') ?>"
           class="sidebar-link <?= isActiveSection('/consignment', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="truck"></i>
            <span>Konsinyasi</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::isOwner()): ?>
        <div class="sidebar-section-label">Manajemen</div>

        <a href="<?= Router::url('/owner') ?>"
           class="sidebar-link <?= isActive('/owner', $currentPath, $base) ? 'is-active' : '' ?>">
            <i data-lucide="layout-dashboard"></i>
            <span>Dashboard</span>
        </a>
        <?php endif; ?>

    </nav>

    <!-- Profile Footer (Pinned Bottom) -->
    <div class="sidebar-footer">
        <div class="sidebar-profile">
            <a href="<?= Router::url('/profile') ?>" class="flex items-center gap-3 min-w-0 flex-1 group" style="text-decoration:none;" title="Klik untuk Pengaturan Profil & Kata Sandi">
                <div class="sidebar-avatar group-hover:opacity-90 transition-opacity">
                    <i data-lucide="user"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="sidebar-user-name group-hover:text-emerald-400 transition-colors">
                        <?= htmlspecialchars(Auth::user()['nama_lengkap'] ?? Auth::name() ?? 'Pengguna') ?>
                    </div>
                    <div class="sidebar-user-role">
                        <?= htmlspecialchars(Auth::role() ?? '—') ?>
                    </div>
                </div>
            </a>
            <a href="<?= Router::url('/logout') ?>"
               class="sidebar-logout-btn"
               title="Keluar">
                <i data-lucide="log-out"></i>
            </a>
        </div>
    </div>

</aside>

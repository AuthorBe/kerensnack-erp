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
       :class="{ 'is-open': sidebarOpen, 'sidebar-collapsed': sidebarCollapsed }">

    <!-- Brand Header -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon" style="background:transparent;border:none;box-shadow:none;display:flex;align-items:center;justify-content:center;">
            <img src="<?= Router::asset('/favicon/favicon-96x96.png') ?>" alt="Logo Keren Snack" style="width:26px;height:26px;object-fit:contain;border-radius:6px;display:block;">
        </div>
        <div class="sidebar-brand-text">
            <div class="sidebar-brand-name">KEREN SNACK</div>
        </div>
        <span class="sidebar-brand-badge">ERP</span>

        <!-- Toggle button: desktop only (hidden on mobile via CSS) -->
        <button type="button"
                class="sidebar-toggle-btn"
                @click="toggleSidebarCollapsed()"
                :data-tooltip="sidebarCollapsed ? 'Perlebar Sidebar' : 'Perkecil Sidebar'"
                :aria-label="sidebarCollapsed ? 'Perlebar Sidebar' : 'Perkecil Sidebar'">
            <i data-lucide="panel-left-close" class="sidebar-icon-close"></i>
            <i data-lucide="panel-left-open" class="sidebar-icon-open"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav no-scrollbar">

        <!-- PENJUALAN & TRANSAKSI -->
        <?php if (Auth::can(['pos.pos', 'orders.po_view_all', 'orders.po_view_assigned', 'orders.view_all', 'orders.view_assigned', 'consignment.view_all', 'consignment.view_assigned'])): ?>
        <div class="sidebar-section-label">Penjualan &amp; Transaksi</div>

        <?php if (Auth::can('pos.pos')): ?>
        <a href="<?= Router::url('/pos') ?>"
           class="sidebar-link <?= isActive('/pos', $currentPath, $base) ? 'is-active' : '' ?>"
           data-tooltip="Kasir POS">
            <i data-lucide="scan-line"></i>
            <span>Kasir POS</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can(['orders.view_all', 'orders.view_assigned'])): ?>
        <a href="<?= Router::url('/customer-orders') ?>"
           class="sidebar-link <?= (($currentPath !== $base . '/customer-orders/po-list' && $currentPath !== '/customer-orders/po-list') && (isActiveSection('/customer-orders', $currentPath, $base) || isActiveSection('/sales-orders', $currentPath, $base))) ? 'is-active' : '' ?>"
           data-tooltip="Pesanan Pelanggan">
            <i data-lucide="shopping-bag"></i>
            <span>Pesanan Pelanggan</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can(['orders.po_view_all', 'orders.po_view_assigned'])): ?>
        <a href="<?= Router::url('/customer-orders/po-list') ?>"
           class="sidebar-link <?= ($currentPath === $base . '/customer-orders/po-list' || $currentPath === '/customer-orders/po-list') ? 'is-active' : '' ?>"
           data-tooltip="Daftar PO">
            <i data-lucide="file-input"></i>
            <span>Daftar PO</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can(['consignment.view_all', 'consignment.view_assigned'])): ?>
        <a href="<?= Router::url('/consignment') ?>"
           class="sidebar-link <?= isActiveSection('/consignment', $currentPath, $base) ? 'is-active' : '' ?>"
           data-tooltip="Konsinyasi">
            <i data-lucide="handshake"></i>
            <span>Konsinyasi</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- DELIVERY & LOGISTIK -->
        <?php if (Auth::can(['deliveries.view_all', 'deliveries.view_assigned'])): ?>
        <div class="sidebar-section-label">Delivery</div>

        <a href="<?= Router::url('/deliveries') ?>"
           class="sidebar-link <?= (isActiveSection('/deliveries', $currentPath, $base) && !isActiveSection('/driver-deliveries', $currentPath, $base)) ? 'is-active' : '' ?>"
           data-tooltip="Surat Jalan">
            <i data-lucide="clipboard-check"></i>
            <span>Surat Jalan</span>
        </a>

        <a href="<?= Router::url('/driver-deliveries') ?>"
           class="sidebar-link <?= isActiveSection('/driver-deliveries', $currentPath, $base) ? 'is-active' : '' ?>"
           data-tooltip="Pengiriman">
            <i data-lucide="truck"></i>
            <span>Pengiriman</span>
        </a>
        <?php endif; ?>

        <!-- GUDANG & PEMBELIAN -->
        <?php if (Auth::can(['inventory.view_all', 'inventory.opname', 'inventory.waste', 'purchases.view', 'purchases.create', 'purchases.edit'])): ?>
        <div class="sidebar-section-label">Gudang &amp; Pembelian</div>

        <?php if (Auth::can(['inventory.view_all', 'inventory.opname', 'inventory.waste'])): ?>
        <a href="<?= Router::url('/inventory') ?>"
           class="sidebar-link <?= isActiveSection('/inventory', $currentPath, $base) ? 'is-active' : '' ?>"
           data-tooltip="Stok &amp; Persediaan">
            <i data-lucide="warehouse"></i>
            <span>Stok &amp; Persediaan</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can(['purchases.view', 'purchases.create', 'purchases.edit'])): ?>
        <a href="<?= Router::url('/purchases') ?>"
           class="sidebar-link <?= isActiveSection('/purchases', $currentPath, $base) ? 'is-active' : '' ?>"
           data-tooltip="Pembelian Vendor">
            <i data-lucide="package-plus"></i>
            <span>Pembelian Vendor</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- KEUANGAN & KAS -->
        <?php if (Auth::can(['cash.view_all', 'cash.inflow', 'cash.outflow', 'cash.transfer', 'cash.reports', 'cash.manage_accounts'])): ?>
        <div class="sidebar-section-label">Keuangan &amp; Kas</div>

        <?php if (Auth::can(['cash.view_all', 'cash.manage_accounts'])): ?>
        <a href="<?= Router::url('/cash') ?>"
           class="sidebar-link <?= isActive('/cash', $currentPath, $base) ? 'is-active' : '' ?>"
           data-tooltip="Buku Kas &amp; Valuasi">
            <i data-lucide="landmark"></i>
            <span>Buku Kas &amp; Valuasi</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can(['cash.view_all', 'cash.inflow', 'cash.outflow', 'cash.transfer'])): ?>
        <a href="<?= Router::url('/cash/transactions') ?>"
           class="sidebar-link <?= isActive('/cash/transactions', $currentPath, $base) ? 'is-active' : '' ?>"
           data-tooltip="Kas Masuk &amp; Keluar">
            <i data-lucide="arrow-left-right"></i>
            <span>Kas Masuk &amp; Keluar</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can('cash.reports')): ?>
        <a href="<?= Router::url('/cash/reports') ?>"
           class="sidebar-link <?= isActive('/cash/reports', $currentPath, $base) ? 'is-active' : '' ?>"
           data-tooltip="Laporan Arus Kas">
            <i data-lucide="trending-up"></i>
            <span>Laporan Arus Kas</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- MASTER DATA -->
        <?php if (Auth::can(['master.products_view', 'master.products_manage', 'master.materials_manage', 'master.pricing_view', 'master.pricing_manage', 'master.customers_view_all', 'master.customers_view_assigned', 'master.customers_manage', 'master.suppliers_view', 'master.suppliers_manage', 'master.employees_view', 'master.employees_manage'])): ?>
        <div class="sidebar-section-label">Master Data</div>

        <?php if (Auth::can(['master.products_view', 'master.products_manage', 'master.materials_manage'])): ?>
        <a href="<?= Router::url('/products') ?>"
           class="sidebar-link <?= isActiveSection('/products', $currentPath, $base) ? 'is-active' : '' ?>"
           data-tooltip="Produk, Bahan &amp; BOM">
            <i data-lucide="layers"></i>
            <span>Produk, Bahan &amp; BOM</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can(['master.pricing_view', 'master.pricing_manage'])): ?>
        <a href="<?= Router::url('/pricing') ?>"
           class="sidebar-link <?= isActiveSection('/pricing', $currentPath, $base) ? 'is-active' : '' ?>"
           data-tooltip="Matriks Level Harga">
            <i data-lucide="receipt"></i>
            <span>Matriks Level Harga</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can(['master.customers_view_all', 'master.customers_view_assigned', 'master.customers_manage'])): ?>
        <a href="<?= Router::url('/customers') ?>"
           class="sidebar-link <?= isActiveSection('/customers', $currentPath, $base) ? 'is-active' : '' ?>"
           data-tooltip="Toko Pelanggan &amp; Wilayah">
            <i data-lucide="store"></i>
            <span>Toko Pelanggan &amp; Wilayah</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can(['master.suppliers_view', 'master.suppliers_manage'])): ?>
        <a href="<?= Router::url('/suppliers') ?>"
           class="sidebar-link <?= isActiveSection('/suppliers', $currentPath, $base) ? 'is-active' : '' ?>"
           data-tooltip="Pemasok (Vendor)">
            <i data-lucide="factory"></i>
            <span>Pemasok (Vendor)</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can(['master.employees_view', 'master.employees_manage'])): ?>
        <a href="<?= Router::url('/employees') ?>"
           class="sidebar-link <?= isActiveSection('/employees', $currentPath, $base) ? 'is-active' : '' ?>"
           data-tooltip="Data Karyawan">
            <i data-lucide="id-card"></i>
            <span>Data Karyawan</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- MANAJEMEN EKSEKUTIF -->
        <?php if (Auth::can('owner.dashboard')): ?>
        <div class="sidebar-section-label">Manajemen</div>

        <a href="<?= Router::url('/owner') ?>"
           class="sidebar-link <?= isActive('/owner', $currentPath, $base) ? 'is-active' : '' ?>"
           data-tooltip="Executive Dashboard">
            <i data-lucide="gauge"></i>
            <span>Executive Dashboard</span>
        </a>
        <?php endif; ?>

        <!-- SISTEM & PENGATURAN -->
        <?php if (Auth::can(['rbac.users_view', 'rbac.users_manage', 'rbac.permissions_manage', 'rbac.roles_manage', 'system.activity_log', 'system.blueprint', 'settings.company_manage', 'system.import_data']) || Auth::isDeveloper()): ?>
        <div class="sidebar-section-label">Sistem</div>

        <a href="<?= Router::url('/settings') ?>"
           class="sidebar-link <?= (isActiveSection('/settings', $currentPath, $base) || isActiveSection('/pengaturan', $currentPath, $base) || isActiveSection('/developer', $currentPath, $base) || isActiveSection('/users', $currentPath, $base) || isActiveSection('/permissions', $currentPath, $base)) ? 'is-active' : '' ?>"
           data-tooltip="Pengaturan">
            <i data-lucide="sliders-horizontal"></i>
            <span>Pengaturan</span>
        </a>
        <?php endif; ?>

    </nav>

    <!-- Instant Zero-Flicker Pre-Paint Scroll Restoration -->
    <script>
        (function() {
            try {
                var sidebar = document.getElementById('app-sidebar');
                var nav = sidebar ? sidebar.querySelector('.sidebar-nav') : null;
                if (!nav) return;
                var saved = sessionStorage.getItem('sidebar_scroll');
                if (saved !== null) {
                    nav.scrollTop = parseInt(saved, 10) || 0;
                } else {
                    var active = nav.querySelector('.sidebar-link.is-active');
                    if (active) {
                        active.scrollIntoView({ block: 'center' });
                    }
                }
            } catch (e) {}
        })();
    </script>

    <!-- Profile Footer (Pinned Bottom) -->
    <div class="sidebar-footer">

        <!-- EXPANDED: normal profile row with avatar + name + logout btn -->
        <div class="sidebar-profile sidebar-profile-expanded">
            <a href="<?= Router::url('/profile') ?>" class="sidebar-profile-link group" style="text-decoration:none;" title="Klik untuk Pengaturan Profil & Kata Sandi">
                <div class="sidebar-avatar">
                    <i data-lucide="user"></i>
                </div>
                <div class="sidebar-profile-info">
                    <div class="sidebar-user-name">
                        <?= htmlspecialchars(Auth::user()['nama_lengkap'] ?? Auth::name() ?? 'Pengguna') ?>
                    </div>
                    <div class="sidebar-user-role">
                        <?= htmlspecialchars(Auth::role() ?? '—') ?>
                    </div>
                </div>
            </a>
            <a href="<?= Router::url('/logout') ?>"
               class="sidebar-logout-btn logout-trigger"
               data-action="logout"
               title="Keluar dari Sistem">
                <i data-lucide="log-out"></i>
            </a>
        </div>

        <!-- COLLAPSED: avatar only, hover shows fixed popup with 2 actions -->
        <div class="sidebar-profile-collapsed"
             x-data="{
                open: false,
                bottom: 0,
                left: 0,
                _timer: null,
                showPopup(el) {
                    clearTimeout(this._timer);
                    const r = el.getBoundingClientRect();
                    const sb = document.getElementById('app-sidebar');
                    const sbRight = sb ? sb.getBoundingClientRect().right : r.right;
                    this.bottom = Math.max(16, window.innerHeight - r.bottom);
                    this.left = sbRight + 16;
                    this.open = true;
                },
                hidePopup() {
                    this._timer = setTimeout(() => { this.open = false; }, 180);
                },
                keepPopup() {
                    clearTimeout(this._timer);
                }
             }"
             @click.outside="open = false"
             @keydown.escape.window="open = false"
             @resize.window="open = false">

            <!-- Avatar trigger -->
            <div class="sidebar-avatar-wrap"
                 @mouseenter="showPopup($el)"
                 @mouseleave="hidePopup()">
                <div class="sidebar-avatar">
                    <i data-lucide="user"></i>
                </div>
            </div>

            <!-- Fixed-position popup (not clipped by sidebar overflow:hidden) -->
            <div class="sidebar-profile-popup-fixed"
                 :style="{ bottom: bottom + 'px', left: left + 'px' }"
                 x-show="open"
                 x-transition
                 @mouseenter="keepPopup()"
                 @mouseleave="hidePopup()"
                 x-cloak>

                <div class="sidebar-profile-popup-header">
                    <div class="sidebar-popup-name"><?= htmlspecialchars(Auth::user()['nama_lengkap'] ?? Auth::name() ?? 'Pengguna') ?></div>
                    <div class="sidebar-popup-role"><?= htmlspecialchars(Auth::role() ?? '—') ?></div>
                </div>

                <div class="sidebar-profile-popup-actions">
                    <a href="<?= Router::url('/profile') ?>" class="sidebar-popup-action">
                        <i data-lucide="user-cog"></i>
                        <span>Profil Saya</span>
                    </a>
                    <a href="<?= Router::url('/logout') ?>" class="sidebar-popup-action sidebar-popup-action-logout logout-trigger" data-action="logout">
                        <i data-lucide="log-out"></i>
                        <span>Keluar</span>
                    </a>
                </div>

            </div>
        </div>

    </div>

    <!-- Instant Fast Floating Tooltip (Outside sidebar, 0ms delay, follows fast cursor) -->
    <div id="sidebar-floating-tooltip" class="sidebar-floating-tooltip" aria-hidden="true"></div>

    <script>
        (function() {
            var tip = document.getElementById('sidebar-floating-tooltip');
            var sidebar = document.getElementById('app-sidebar');
            if (!tip || !sidebar) return;

            function isCollapsed() {
                return sidebar.classList.contains('sidebar-collapsed') ||
                       document.documentElement.classList.contains('sidebar-is-collapsed');
            }

            function getTooltipTarget(el) {
                if (!el || !sidebar.contains(el)) return null;
                return el.closest('.sidebar-link[data-tooltip], .sidebar-toggle-btn[data-tooltip]');
            }

            document.addEventListener('mouseover', function(e) {
                if (!isCollapsed()) return;
                var target = getTooltipTarget(e.target);
                if (!target) return;

                var text = target.getAttribute('data-tooltip');
                if (!text) return;

                tip.textContent = text;
                var r = target.getBoundingClientRect();
                var sbRight = sidebar.getBoundingClientRect().right;
                tip.style.top = (r.top + r.height / 2) + 'px';
                tip.style.left = (sbRight + 12) + 'px';
                tip.classList.add('is-visible');
            });

            document.addEventListener('mouseout', function(e) {
                var current = getTooltipTarget(e.target);
                if (!current) return;
                var next = getTooltipTarget(e.relatedTarget);
                // Only hide if mouse actually left the target element
                if (current !== next) {
                    tip.classList.remove('is-visible');
                }
            });

            window.addEventListener('scroll', function() {
                tip.classList.remove('is-visible');
            }, true);

            window.addEventListener('resize', function() {
                tip.classList.remove('is-visible');
            }, { passive: true });

            document.addEventListener('click', function(e) {
                if (e.target.closest('#app-sidebar')) {
                    tip.classList.remove('is-visible');
                }
            });
        })();
    </script>

</aside>


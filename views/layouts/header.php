<?php
use App\Core\Auth;
use App\Helpers\Format;

// Map route path to readable page title + subtitle
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$pageInfo = match(true) {
    str_contains($uri, '/pos')         => ['title' => 'Kasir POS',     'sub' => 'Transaksi penjualan'],
    str_contains($uri, '/pricing')     => ['title' => 'Daftar Harga',  'sub' => 'Kelola harga produk'],
    str_contains($uri, '/inventory')   => ['title' => 'Stok Produk',   'sub' => 'Manajemen inventaris'],
    str_contains($uri, '/consignment') => ['title' => 'Konsinyasi',    'sub' => 'Titip jual & retur'],
    str_contains($uri, '/owner')       => ['title' => 'Dashboard',     'sub' => 'Overview operasional'],
    str_contains($uri, '/profile')     => ['title' => 'Profil Pengguna', 'sub' => 'Pengaturan akun & kata sandi'],
    default                            => ['title' => $pageTitle ?? '', 'sub' => ''],
};
?>
<header class="app-header">
    <div class="header-left">
        <!-- Hamburger (Mobile) -->
        <button class="header-burger"
                @click="sidebarOpen = !sidebarOpen; sidebarOpen ? openSidebar() : closeSidebar()"
                aria-label="Toggle navigation">
            <i data-lucide="menu"></i>
        </button>

        <!-- Page Title -->
        <div>
            <div class="header-page-title"><?= htmlspecialchars($pageInfo['title']) ?></div>
            <?php if ($pageInfo['sub']): ?>
            <div class="header-subtitle hide-mobile"><?= htmlspecialchars($pageInfo['sub']) ?></div>
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
        <button class="header-theme-btn"
                @click="toggleTheme()"
                :title="isDark ? 'Switch ke Mode Terang' : 'Switch ke Mode Gelap'"
                aria-label="Toggle theme">
            <span x-show="isDark" style="display:flex;align-items:center;justify-content:center;">
                <i data-lucide="sun" style="width:15px;height:15px;"></i>
            </span>
            <span x-show="!isDark" style="display:flex;align-items:center;justify-content:center;" x-cloak>
                <i data-lucide="moon" style="width:15px;height:15px;"></i>
            </span>
        </button>
    </div>
</header>


<?php
use App\Core\Auth;
use App\Core\Router;

// Cache busting for static assets
$cssV = file_exists(ROOT_PATH . '/public/assets/css/app.css') ? filemtime(ROOT_PATH . '/public/assets/css/app.css') : '1';
$jsV  = file_exists(ROOT_PATH . '/public/assets/js/app.js')  ? filemtime(ROOT_PATH . '/public/assets/js/app.js')  : '1';
?>
<!DOCTYPE html>
<html lang="id" class="<?= (($_COOKIE['ksnack_theme'] ?? 'light') === 'dark') ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="<?= htmlspecialchars(\App\Helpers\CSRF::token()) ?>">
    <title><?= htmlspecialchars($pageTitle ?? 'Studio') ?> — KEREN SNACK ERP</title>

    <!-- PWA & Mobile Web App Meta Tags -->
    <meta name="theme-color" content="#e11d48">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Keren Snack">
    <meta name="application-name" content="Keren Snack ERP">

    <!-- Favicon & PWA Icons -->
    <link rel="icon" type="image/x-icon" href="<?= Router::asset('/favicon/favicon.ico') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= Router::asset('/favicon/favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= Router::asset('/favicon/favicon-96x96.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= Router::asset('/favicon/apple-touch-icon.png') ?>">
    <link rel="manifest" href="<?= Router::asset('/favicon/site.webmanifest') ?>">

    <!-- ⚡ ZERO-FLASH THEME ENGINE & BASE PATH (Inline, before CSS) -->
    <script>
        window.APP_BASE_PATH = '<?= Router::getBasePath() ?>';
        (function() {
            var t = localStorage.getItem('ksnack_theme') || 'light';
            if (t === 'dark') document.documentElement.classList.add('dark');
            else document.documentElement.classList.remove('dark');
        })();
    </script>

    <!-- Google Fonts: Inter + JetBrains Mono (Supabase design language) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Lucide Icons (100% Local Vendor Asset) -->
    <script src="<?= Router::asset('/js/lucide.min.js') ?>?v=<?= $jsV ?>"></script>

    <!-- Global App CSS (Supabase Design System + Standalone Utility Engine) -->
    <link rel="stylesheet" href="<?= Router::asset('/css/app.css') ?>?v=<?= $cssV ?>">

    <!-- Alpine.js (100% Local Vendor Asset - Instant Offline) -->
    <script defer src="<?= Router::asset('/js/alpine.min.js') ?>?v=<?= $jsV ?>"></script>

    <style>
        /* Alpine.js cloak */
        [x-cloak] { display: none !important; }

        /* Instant system font stack fallback (0ms font render delay) */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
    </style>
</head>
<body x-data="{
        sidebarOpen: false,
        isDark: (localStorage.getItem('ksnack_theme') || 'light') === 'dark',
        toggleTheme() {
            this.isDark = !this.isDark;
            const theme = this.isDark ? 'dark' : 'light';
            if (window.ThemeEngine) {
                window.ThemeEngine.set(theme);
            } else {
                localStorage.setItem('ksnack_theme', theme);
                document.cookie = 'ksnack_theme=' + theme + '; path=/; max-age=31536000';
                if (this.isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
        },
        openNav() {
            this.sidebarOpen = true;
            if (window.SidebarCtrl) SidebarCtrl.open();
            document.body.classList.add('sidebar-open');
            document.documentElement.classList.add('sidebar-open');
        },
        closeNav() {
            this.sidebarOpen = false;
            if (window.SidebarCtrl) SidebarCtrl.close();
            document.body.classList.remove('sidebar-open');
            document.documentElement.classList.remove('sidebar-open');
        }
    }"
    :class="{ 'sidebar-open': sidebarOpen }"
    class="app-shell">

    <!-- GLOBAL FLASH TOAST CONTAINER -->
    <?php require __DIR__ . '/flash.php'; ?>

    <!-- SIDEBAR (Always Dark Canvas-Night) -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- SIDEBAR OVERLAY (Mobile) -->
    <div id="sidebar-overlay"
         class="sidebar-overlay"
         @click="closeNav()"
         :class="{ 'is-visible': sidebarOpen }"></div>

    <!-- MAIN CONTENT AREA -->
    <div class="app-main">

        <!-- HEADER -->
        <?php require __DIR__ . '/header.php'; ?>

        <!-- PAGE CONTENT -->
        <main class="app-content custom-scrollbar">
            <?= $content ?? '' ?>

            <!-- FOOTER (Natural Bottom of Page) -->
            <?php require __DIR__ . '/footer.php'; ?>
        </main>

    </div>

    <!-- Global App JS -->
    <script src="<?= Router::asset('/js/app.js') ?>?v=<?= $jsV ?>"></script>
    <script src="<?= Router::asset('/js/searchable-select.js') ?>?v=<?= $jsV ?>"></script>
    <script src="<?= Router::asset('/js/keyboard-nav.js') ?>?v=<?= $jsV ?>"></script>

</body>
</html>

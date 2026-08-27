<?php
use App\Core\Auth;
use App\Core\Router;

// Cache busting for static assets
$cssV = file_exists(ROOT_PATH . '/public/assets/css/app.css') ? filemtime(ROOT_PATH . '/public/assets/css/app.css') : '1';
$jsV  = file_exists(ROOT_PATH . '/public/assets/js/app.js')  ? filemtime(ROOT_PATH . '/public/assets/js/app.js')  : '1';
?>
<!DOCTYPE html>
<html lang="id" class="<?= (($_COOKIE['ksnack_theme'] ?? 'dark') === 'light') ? '' : 'dark' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= htmlspecialchars($pageTitle ?? 'Studio') ?> — KEREN SNACK ERP</title>

    <!-- ⚡ ZERO-FLASH THEME ENGINE (Must be inline, before CSS) -->
    <script>
        (function() {
            var t = localStorage.getItem('ksnack_theme') || 'dark';
            if (t === 'light') document.documentElement.classList.remove('dark');
            else document.documentElement.classList.add('dark');
        })();
    </script>

    <!-- Google Fonts: Inter + JetBrains Mono (Supabase design language) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Global App CSS (Supabase Design System) -->
    <link rel="stylesheet" href="<?= Router::asset('/css/app.css') ?>?v=<?= $cssV ?>">

    <!-- Alpine.js (UI reactivity) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CDN (utility supplement — layout, spacing, responsive) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'Helvetica Neue', 'Helvetica', 'Arial', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'Menlo', 'Monaco', 'Consolas', 'monospace'],
                    }
                }
            }
        }
    </script>

    <style>
        /* Alpine.js cloak */
        [x-cloak] { display: none !important; }

        /* Custom scrollbar via app.css — apply here for Tailwind */
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body x-data="{
        sidebarOpen: false,
        isDark: localStorage.getItem('ksnack_theme') !== 'light',
        toggleTheme() {
            this.isDark = !this.isDark;
            const theme = this.isDark ? 'dark' : 'light';
            localStorage.setItem('ksnack_theme', theme);
            document.cookie = 'ksnack_theme=' + theme + '; path=/; max-age=31536000';
            if (this.isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }"
    :class="{ 'overflow-hidden': sidebarOpen }"
    class="app-shell">

    <!-- GLOBAL FLASH TOAST CONTAINER -->
    <?php require __DIR__ . '/flash.php'; ?>

    <!-- SIDEBAR (Always Dark Canvas-Night) -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- SIDEBAR OVERLAY (Mobile) -->
    <div id="sidebar-overlay"
         class="sidebar-overlay"
         @click="sidebarOpen = false; closeSidebar()"
         :class="{ 'is-visible': sidebarOpen }"></div>

    <!-- MAIN CONTENT AREA -->
    <div class="app-main">

        <!-- HEADER -->
        <?php require __DIR__ . '/header.php'; ?>

        <!-- PAGE CONTENT -->
        <main class="app-content custom-scrollbar">
            <?= $content ?? '' ?>
        </main>

        <!-- FOOTER (Watermark) -->
        <?php require __DIR__ . '/footer.php'; ?>

    </div>

    <!-- Global App JS -->
    <script src="<?= Router::asset('/js/app.js') ?>?v=<?= $jsV ?>"></script>

</body>
</html>

<?php
declare(strict_types=1);

/**
 * views/guide/index.php
 * Portal Dokumentasi & Buku Panduan Operasional Menyeluruh (Standalone Reader Portal)
 * Desain Bersih, Elegan, dan Mandiri (Zero Header/Sidebar ERP).
 */

use App\Core\Router;
use App\Helpers\CompanySetting;

$comp = $comp ?? CompanySetting::getAll();
$companyName = $comp['nama'] ?? 'KEREN SNACK';
$companyPhone = $comp['telepon'] ?? '';
$cssV = file_exists(ROOT_PATH . '/public/assets/css/app.css') ? (string)filemtime(ROOT_PATH . '/public/assets/css/app.css') : '1';
$jsV  = file_exists(ROOT_PATH . '/public/assets/js/app.js')  ? (string)filemtime(ROOT_PATH . '/public/assets/js/app.js')  : '1';
$favGuideFile = ROOT_PATH . '/public/assets/favicon/favicon_guide.svg';
$favGuideV = file_exists($favGuideFile) ? (string)filemtime($favGuideFile) : (string)time();
?>
<!DOCTYPE html>
<html lang="id" class="<?= (($_COOKIE['ksnack_theme'] ?? 'light') === 'dark') ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Buku Panduan Operasional &amp; SOP — <?= htmlspecialchars($companyName) ?></title>

    <!-- PWA & Mobile Web App Meta Tags (Keren One Ecosystem DNA) -->
    <meta name="theme-color" content="#881337">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Buku Panduan Keren One">
    <meta name="application-name" content="Buku Panduan Keren One">
    <meta name="apple-touch-fullscreen" content="yes">
    <meta name="format-detection" content="telephone=no">

    <!-- Dedicated Guide Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= Router::asset('/favicon/favicon_guide.svg') ?>?v=<?= $favGuideV ?>">
    <link rel="shortcut icon" type="image/svg+xml" href="<?= Router::asset('/favicon/favicon_guide.svg') ?>?v=<?= $favGuideV ?>">
    <link rel="apple-touch-icon" href="<?= Router::asset('/favicon/favicon_guide.svg') ?>?v=<?= $favGuideV ?>">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="<?= Router::asset('/js/lucide.min.js') ?>?v=<?= $jsV ?>"></script>

    <!-- Alpine.js -->
    <script defer src="<?= Router::asset('/js/alpine.min.js') ?>?v=<?= $jsV ?>"></script>

    <style>
        [x-cloak] { display: none !important; }
        
        :root {
            --guide-bg: #f8fafc;
            --guide-card-bg: #ffffff;
            --guide-border: #e2e8f0;
            --guide-text-primary: #0f172a;
            --guide-text-secondary: #475569;
            --guide-text-muted: #64748b;
            --guide-accent: #881337;
            --guide-accent-hover: #9f1239;
            --guide-accent-soft: rgba(136, 19, 55, 0.08);
            --guide-sidebar-bg: #ffffff;
            --guide-header-bg: #ffffff;
        }

        html.dark {
            --guide-bg: #090d16;
            --guide-card-bg: #0f172a;
            --guide-border: #1e293b;
            --guide-text-primary: #f8fafc;
            --guide-text-secondary: #cbd5e1;
            --guide-text-muted: #94a3b8;
            --guide-accent: #fb7185;
            --guide-accent-hover: #f43f5e;
            --guide-accent-soft: rgba(251, 113, 133, 0.15);
            --guide-sidebar-bg: #0b1120;
            --guide-header-bg: #0f172a;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        html {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
            scroll-behavior: smooth;
            scroll-padding-top: calc(76px + env(safe-area-inset-top, 0px));
        }

        html, body {
            overflow-x: hidden !important;
            max-width: 100vw;
            width: 100%;
            position: relative;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: var(--guide-bg);
            color: var(--guide-text-primary);
            line-height: 1.6;
            font-size: 14px;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-y;
        }

        /* 1. TOP NAVBAR (FIXED ON SCROLL & iOS SAFE AREA READY) */
        .guide-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 100;
            height: 60px;
            height: calc(60px + env(safe-area-inset-top, 0px));
            background: var(--guide-header-bg);
            border-bottom: 1px solid var(--guide-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: env(safe-area-inset-top, 0px);
            padding-bottom: 0;
            padding-left: max(16px, env(safe-area-inset-left, 0px));
            padding-right: max(16px, env(safe-area-inset-right, 0px));
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-sizing: border-box;
            gap: 12px;
            transform: translateZ(0);
            -webkit-transform: translateZ(0);
        }
        @media (min-width: 768px) {
            .guide-navbar {
                padding-left: max(24px, env(safe-area-inset-left, 0px));
                padding-right: max(24px, env(safe-area-inset-right, 0px));
            }
        }
        @media (max-width: 639px) {
            .guide-navbar {
                height: 52px;
                height: calc(52px + env(safe-area-inset-top, 0px));
                padding-left: max(8px, env(safe-area-inset-left, 0px));
                padding-right: max(8px, env(safe-area-inset-right, 0px));
                gap: 6px;
            }
        }

        .guide-navbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            flex-shrink: 0;
        }
        @media (max-width: 639px) {
            .guide-navbar-left {
                gap: 6px;
            }
        }

        .guide-brand-box {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: inherit;
            min-width: 0;
        }
        @media (max-width: 639px) {
            .guide-brand-box {
                display: none !important;
            }
        }
        .guide-brand-badge {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }
        .guide-brand-badge img,
        .guide-brand-logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            border-radius: 10px;
        }
        .guide-brand-text {
            min-width: 0;
        }
        .guide-brand-text h1 {
            font-size: 14px;
            font-weight: 900;
            line-height: 1.2;
            letter-spacing: -0.02em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .guide-brand-sub {
            font-size: 11px;
            color: var(--guide-text-muted);
            font-weight: 600;
            display: block;
            white-space: nowrap;
        }
        @media (max-width: 767px) {
            .guide-brand-sub {
                display: none !important;
            }
        }

        .guide-nav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            flex: 1;
            justify-content: flex-end;
        }
        @media (max-width: 639px) {
            .guide-nav-actions {
                gap: 5px;
                flex: 1;
                width: 100%;
            }
        }

        /* Search Input */
        .guide-search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            width: 200px;
            transition: width 0.2s ease;
        }
        @media (min-width: 768px) {
            .guide-search-wrapper {
                width: 250px;
            }
        }
        @media (max-width: 639px) {
            .guide-search-wrapper {
                flex: 1;
                max-width: 100%;
                min-width: 120px;
                width: 100%;
            }
            .guide-search-wrapper:focus-within {
                max-width: 100%;
                width: 100%;
            }
        }
        .guide-search-input {
            width: 100%;
            height: 34px;
            padding: 0 54px 0 32px;
            border-radius: 10px;
            border: 1px solid var(--guide-border);
            background: var(--guide-bg);
            color: var(--guide-text-primary);
            font-size: 13px;
            outline: none;
            transition: all 0.15s ease;
            -webkit-appearance: none;
            appearance: none;
        }
        @media (max-width: 639px) {
            .guide-search-input {
                height: 32px;
                padding: 0 54px 0 26px;
                font-size: 16px; /* Mencegah auto-zoom default iOS Safari saat focus input */
                border-radius: 8px;
            }
        }
        .guide-search-input:focus {
            border-color: var(--guide-accent);
            box-shadow: 0 0 0 3px var(--guide-accent-soft);
        }
        .guide-search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            color: var(--guide-text-muted);
            pointer-events: none;
        }
        @media (max-width: 639px) {
            .guide-search-icon {
                left: 8px;
                width: 12px;
                height: 12px;
            }
        }
        .guide-search-controls {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .guide-search-count {
            font-size: 9.5px;
            font-weight: 800;
            color: var(--guide-accent);
            background: var(--guide-accent-soft);
            padding: 2px 5px;
            border-radius: 5px;
            white-space: nowrap;
        }
        @media (max-width: 639px) {
            .guide-search-count {
                font-size: 8.5px;
                padding: 1px 4px;
            }
        }
        .guide-search-clear {
            background: none;
            border: none;
            color: var(--guide-text-muted);
            cursor: pointer;
            padding: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: color 0.15s ease;
        }
        .guide-search-clear:hover {
            color: var(--guide-accent);
        }

        /* In-Page Text Highlights (Stabilo Kuning) */
        mark.guide-highlight {
            background-color: #fef08a;
            color: #713f12;
            padding: 1px 4px;
            border-radius: 4px;
            font-weight: 700;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: all 0.15s ease;
        }
        mark.guide-highlight.is-active {
            background-color: #f59e0b;
            color: #ffffff;
            outline: 2px solid #d97706;
            box-shadow: 0 2px 10px rgba(217, 119, 6, 0.35);
        }
        html.dark mark.guide-highlight {
            background-color: rgba(234, 179, 8, 0.35);
            color: #fef08a;
        }
        html.dark mark.guide-highlight.is-active {
            background-color: #d97706;
            color: #ffffff;
            outline: 2px solid #f59e0b;
        }

        /* Nav Icon Buttons */
        .guide-btn-icon {
            height: 34px;
            width: 34px;
            border-radius: 10px;
            border: 1px solid var(--guide-border);
            background: var(--guide-card-bg);
            color: var(--guide-text-secondary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            flex-shrink: 0;
        }
        @media (max-width: 639px) {
            .guide-btn-icon {
                height: 32px;
                width: 32px;
                border-radius: 8px;
            }
        }
        .guide-btn-icon:hover {
            color: var(--guide-accent);
            border-color: var(--guide-accent);
        }
        .guide-btn-icon svg {
            width: 15px;
            height: 15px;
        }

        .guide-btn-close {
            height: 34px;
            padding: 0 12px;
            border-radius: 10px;
            border: 1px solid var(--guide-border);
            background: var(--guide-card-bg);
            color: var(--guide-text-primary);
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            flex-shrink: 0;
            white-space: nowrap;
        }
        @media (max-width: 639px) {
            .guide-btn-close {
                height: 32px;
                padding: 0 8px;
                font-size: 11px;
                border-radius: 8px;
                gap: 4px;
            }
        }
        @media (max-width: 480px) {
            .guide-btn-close-text {
                display: none;
            }
            .guide-btn-close {
                width: 32px;
                padding: 0;
                justify-content: center;
            }
        }
        .guide-btn-close:hover {
            background: #e11d48;
            color: #ffffff;
            border-color: #e11d48;
        }

        @media (max-width: 639px) {
            .hide-mobile {
                display: none !important;
            }
        }

        /* 2. MAIN LAYOUT CONTAINER (FIXED HEADER OFFSET & iOS SAFE AREA) */
        .guide-layout {
            display: block;
            min-height: 100vh;
            min-height: 100dvh;
            max-width: 960px;
            margin: 0 auto;
            padding-top: calc(60px + env(safe-area-inset-top, 0px));
            box-sizing: border-box;
        }
        @media (max-width: 639px) {
            .guide-layout {
                padding-top: calc(52px + env(safe-area-inset-top, 0px));
            }
        }

        /* 3. SINGLE OFF-CANVAS SIDEBAR (TOC DRAWER) & BACKDROP */
        .guide-drawer-backdrop {
            position: fixed;
            inset: 0;
            z-index: 999;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            will-change: opacity;
        }

        .guide-drawer {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            height: 100vh;
            height: 100dvh;
            width: min(85vw, 320px);
            background: var(--guide-sidebar-bg);
            z-index: 1000;
            padding-top: max(18px, env(safe-area-inset-top, 0px));
            padding-bottom: max(18px, env(safe-area-inset-bottom, 0px));
            padding-left: max(16px, env(safe-area-inset-left, 0px));
            padding-right: 16px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            box-shadow: 10px 0 30px rgba(0, 0, 0, 0.3);
            border-right: 1px solid var(--guide-border);
            display: flex;
            flex-direction: column;
            gap: 14px;
            will-change: transform;
        }
        .guide-drawer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--guide-border);
        }
        .guide-drawer-title {
            font-size: 13.5px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--guide-text-primary);
        }

        .toc-header {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--guide-text-muted);
            margin-bottom: 10px;
            padding: 0 6px;
        }

        .toc-nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .toc-link {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--guide-text-secondary);
            text-decoration: none;
            transition: all 0.15s ease;
            line-height: 1.35;
        }
        .toc-link svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
            color: var(--guide-text-muted);
            transition: color 0.15s ease;
        }
        .toc-link:hover {
            background: var(--guide-accent-soft);
            color: var(--guide-accent);
        }
        .toc-link:hover svg {
            color: var(--guide-accent);
        }
        .toc-link.is-active {
            background: var(--guide-accent);
            color: #ffffff;
            font-weight: 700;
        }
        .toc-link.is-active svg {
            color: #ffffff;
        }

        /* 4. CONTENT MAIN CONTAINER */
        .guide-content-wrapper {
            width: 100%;
            padding: 24px max(16px, env(safe-area-inset-right, 0px)) calc(80px + env(safe-area-inset-bottom, 0px)) max(16px, env(safe-area-inset-left, 0px));
            min-width: 0;
            box-sizing: border-box;
            -webkit-overflow-scrolling: touch;
        }
        @media (min-width: 768px) {
            .guide-content-wrapper {
                padding: 36px max(24px, env(safe-area-inset-right, 0px)) calc(100px + env(safe-area-inset-bottom, 0px)) max(24px, env(safe-area-inset-left, 0px));
            }
        }

        .guide-article {
            width: 100%;
            margin: 0 auto;
        }

        /* 5. CLEAN HERO SECTION */
        .guide-main-hero {
            background: var(--guide-card-bg);
            border: 1px solid var(--guide-border);
            border-radius: 16px;
            padding: 24px 20px;
            margin-bottom: 24px;
        }
        @media (min-width: 768px) {
            .guide-main-hero {
                padding: 28px 30px;
            }
        }
        .hero-header-flex {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }
        .hero-brand-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
            overflow: hidden;
        }
        .hero-brand-icon img,
        .hero-brand-logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            border-radius: 12px;
        }
        .hero-header-body {
            flex: 1;
            min-width: 0;
        }
        .hero-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 6px;
        }
        .hero-tag-wrap {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 11px;
            font-weight: 800;
            color: var(--guide-accent);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .hero-tag-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--guide-accent);
            display: inline-block;
        }
        .hero-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 9px;
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            font-size: 10.5px;
            font-weight: 800;
            color: #059669;
            letter-spacing: 0.03em;
        }
        html.dark .hero-status-pill {
            color: #34d399;
            background: rgba(52, 211, 153, 0.12);
            border-color: rgba(52, 211, 153, 0.3);
        }
        .hero-main-title {
            font-size: 22px;
            font-weight: 900;
            line-height: 1.25;
            letter-spacing: -0.025em;
            color: var(--guide-text-primary);
            margin-bottom: 6px;
        }
        @media (min-width: 768px) {
            .hero-main-title {
                font-size: 26px;
            }
        }
        .hero-main-desc {
            font-size: 13.5px;
            color: var(--guide-text-secondary);
            line-height: 1.6;
        }

        /* Harmonious Multi-Color Themes for Chapters */
        .guide-chapter.theme-indigo  { --chap-color: #6366f1; --chap-bg: rgba(99, 102, 241, 0.1); }
        .guide-chapter.theme-emerald { --chap-color: #059669; --chap-bg: rgba(16, 185, 129, 0.1); }
        .guide-chapter.theme-amber   { --chap-color: #d97706; --chap-bg: rgba(245, 158, 11, 0.1); }
        .guide-chapter.theme-blue    { --chap-color: #0284c7; --chap-bg: rgba(2, 132, 199, 0.1); }
        .guide-chapter.theme-rose    { --chap-color: #e11d48; --chap-bg: rgba(225, 29, 72, 0.1); }
        .guide-chapter.theme-purple  { --chap-color: #9333ea; --chap-bg: rgba(147, 51, 234, 0.1); }
        .guide-chapter.theme-teal    { --chap-color: #0d9488; --chap-bg: rgba(13, 148, 136, 0.1); }
        .guide-chapter.theme-cyan    { --chap-color: #0891b2; --chap-bg: rgba(8, 145, 178, 0.1); }
        .guide-chapter.theme-green   { --chap-color: #16a34a; --chap-bg: rgba(22, 163, 74, 0.1); }
        .guide-chapter.theme-orange  { --chap-color: #ea580c; --chap-bg: rgba(234, 88, 12, 0.1); }
        .guide-chapter.theme-violet  { --chap-color: #7c3aed; --chap-bg: rgba(124, 58, 237, 0.1); }

        html.dark .guide-chapter.theme-indigo  { --chap-color: #818cf8; --chap-bg: rgba(129, 140, 248, 0.15); }
        html.dark .guide-chapter.theme-emerald { --chap-color: #34d399; --chap-bg: rgba(52, 211, 153, 0.15); }
        html.dark .guide-chapter.theme-amber   { --chap-color: #fbbf24; --chap-bg: rgba(251, 191, 36, 0.15); }
        html.dark .guide-chapter.theme-blue    { --chap-color: #38bdf8; --chap-bg: rgba(56, 189, 248, 0.15); }
        html.dark .guide-chapter.theme-rose    { --chap-color: #fb7185; --chap-bg: rgba(251, 113, 133, 0.15); }
        html.dark .guide-chapter.theme-purple  { --chap-color: #c084fc; --chap-bg: rgba(192, 132, 252, 0.15); }
        html.dark .guide-chapter.theme-teal    { --chap-color: #2dd4bf; --chap-bg: rgba(45, 212, 191, 0.15); }
        html.dark .guide-chapter.theme-cyan    { --chap-color: #22d3ee; --chap-bg: rgba(34, 211, 238, 0.15); }
        html.dark .guide-chapter.theme-green   { --chap-color: #4ade80; --chap-bg: rgba(74, 222, 128, 0.15); }
        html.dark .guide-chapter.theme-orange  { --chap-color: #fb923c; --chap-bg: rgba(251, 146, 60, 0.15); }
        html.dark .guide-chapter.theme-violet  { --chap-color: #a78bfa; --chap-bg: rgba(167, 139, 250, 0.15); }

        /* Chapter Cards */
        .guide-chapter {
            background: var(--guide-card-bg);
            border: 1px solid var(--guide-border);
            border-left: 4px solid var(--chap-color, var(--guide-accent));
            border-radius: 18px;
            padding: 22px 18px;
            margin-bottom: 28px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            scroll-margin-top: 80px;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .guide-chapter:hover {
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
        }
        @media (min-width: 768px) {
            .guide-chapter {
                padding: 28px 30px;
            }
        }

        .chapter-header {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--guide-border);
        }
        .chapter-icon-badge {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--chap-bg, var(--guide-accent-soft));
            color: var(--chap-color, var(--guide-accent));
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 18px;
            transition: transform 0.15s ease;
        }
        .guide-chapter:hover .chapter-icon-badge {
            transform: scale(1.05);
        }
        .chapter-icon-badge svg {
            width: 22px;
            height: 22px;
        }
        .chapter-title-wrap {
            flex: 1;
            min-width: 0;
        }
        .chapter-number {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--chap-color, var(--guide-accent));
        }
        .chapter-title {
            font-size: 17px;
            font-weight: 900;
            color: var(--guide-text-primary);
            line-height: 1.3;
            letter-spacing: -0.01em;
            margin-top: 2px;
        }
        @media (min-width: 768px) {
            .chapter-title {
                font-size: 19px;
            }
        }
        .chapter-roles {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }
        .role-pill {
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 5px;
            background: var(--guide-bg);
            border: 1px solid var(--guide-border);
            color: var(--guide-text-secondary);
        }

        /* Step Timeline */
        .step-timeline {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin: 16px 0;
        }
        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .step-circle {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--chap-color, var(--guide-accent));
            color: #ffffff;
            font-weight: 900;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        }
        .step-content {
            flex: 1;
            min-width: 0;
        }
        .step-title {
            font-size: 13.5px;
            font-weight: 800;
            color: var(--guide-text-primary);
            margin-bottom: 4px;
        }
        .step-desc {
            font-size: 12.5px;
            color: var(--guide-text-secondary);
            line-height: 1.5;
        }

        /* Callout Boxes */
        .guide-box {
            border-radius: 12px;
            padding: 12px 14px;
            margin: 14px 0;
            font-size: 12.5px;
            line-height: 1.5;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .guide-box svg {
            width: 17px;
            height: 17px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .box-info    { background: rgba(2, 132, 199, 0.08); color: #0284c7; border: 1px solid rgba(2, 132, 199, 0.2); }
        .box-success { background: rgba(16, 185, 129, 0.08); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); }
        .box-warning { background: rgba(217, 119, 6, 0.08); color: #d97706; border: 1px solid rgba(217, 119, 6, 0.2); }
        .box-danger  { background: rgba(225, 29, 72, 0.08); color: #e11d48; border: 1px solid rgba(225, 29, 72, 0.2); }

        html.dark .box-info    { background: rgba(56, 189, 248, 0.12); color: #38bdf8; border-color: rgba(56, 189, 248, 0.25); }
        html.dark .box-success { background: rgba(52, 211, 153, 0.12); color: #34d399; border-color: rgba(52, 211, 153, 0.25); }
        html.dark .box-warning { background: rgba(251, 191, 36, 0.12); color: #fbbf24; border-color: rgba(251, 191, 36, 0.25); }
        html.dark .box-danger  { background: rgba(251, 113, 133, 0.12); color: #fb7185; border-color: rgba(251, 113, 133, 0.25); }

        .formula-card {
            background: var(--guide-bg);
            border: 1px dashed var(--guide-border);
            border-radius: 12px;
            padding: 12px 14px;
            margin: 12px 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-variant-numeric: tabular-nums;
            font-size: 12px;
            font-weight: 600;
            color: var(--guide-text-primary);
        }

        /* Navigation Path Pills */
        .guide-nav-step {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 7px;
            background: var(--guide-bg);
            border: 1px solid var(--guide-border);
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            color: var(--guide-text-primary);
            white-space: nowrap;
        }
        .guide-nav-step svg {
            width: 12px;
            height: 12px;
            color: var(--guide-accent);
        }

        /* FAQ Card System (Modern, Structured, Polished) */
        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 14px;
        }
        .faq-card {
            background: var(--guide-bg);
            border: 1px solid var(--guide-border);
            border-radius: 14px;
            padding: 16px;
            transition: all 0.2s ease;
        }
        .faq-card:hover {
            border-color: rgba(234, 88, 12, 0.35);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);
        }
        .faq-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 8px;
        }
        .faq-badge-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: rgba(234, 88, 12, 0.12);
            color: #ea580c;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }
        html.dark .faq-badge-icon {
            background: rgba(251, 146, 60, 0.15);
            color: #fb923c;
        }
        .faq-badge-icon svg {
            width: 16px;
            height: 16px;
        }
        .faq-question {
            font-size: 13.5px;
            font-weight: 800;
            color: var(--guide-text-primary);
            line-height: 1.35;
            margin: 0;
            padding-top: 5px;
        }
        .faq-body {
            font-size: 12.5px;
            color: var(--guide-text-secondary);
            line-height: 1.6;
            padding-left: 44px;
        }

        /* 5.1 CODE BLOCKS & TYPOGRAPHY */
        code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.88em;
            background: var(--guide-accent-soft);
            color: var(--guide-accent);
            padding: 2px 6px;
            border-radius: 6px;
            border: 1px solid rgba(136, 19, 55, 0.15);
            word-break: break-word;
            overflow-wrap: break-word;
        }
        html.dark code {
            border-color: rgba(251, 113, 133, 0.25);
            background: rgba(251, 113, 133, 0.12);
        }

        /* 5.2 CUSTOM SCROLLBAR */
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: var(--guide-border);
            border-radius: 999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: var(--guide-text-muted);
        }
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: var(--guide-border) transparent;
        }

        /* 5.3 OFF-CANVAS DRAWER ANIMATIONS */
        .transition-opacity { transition: opacity 0.2s ease; }
        .transition { transition: all 0.2s ease; }
        .ease-out { transition-timing-function: cubic-bezier(0, 0, 0.2, 1); }
        .ease-in { transition-timing-function: cubic-bezier(0.4, 0, 1, 1); }
        .duration-200 { transition-duration: 200ms; }
        .opacity-0 { opacity: 0; }
        .opacity-100 { opacity: 1; }
        .transform { transform: translateZ(0); }
        .-translate-x-full { transform: translateX(-100%); }
        .translate-x-0 { transform: translateX(0); }
        .translate-y-4 { transform: translateY(16px); }
        .translate-y-0 { transform: translateY(0); }

        /* 5.4 SUB-CHAPTER HEADINGS */
        .sub-chapter-block {
            margin-bottom: 20px;
        }
        .sub-chapter-title {
            font-size: 14.5px;
            font-weight: 800;
            color: var(--guide-text-primary);
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 8px;
            line-height: 1.35;
        }
        .sub-chapter-title svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            margin-top: 2px;
            color: var(--guide-accent);
        }

        /* 5.5 GUIDE TABLES (RESPONSIVE COMPARISON & AUDIT TABLES) */
        .guide-table-wrapper,
        .guide-article .table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 16px 0;
            border-radius: 14px;
            border: 1px solid var(--guide-border);
            background: var(--guide-card-bg);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }
        .guide-table,
        .guide-article table.data-table,
        .guide-article .table-wrapper table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
            text-align: left;
            min-width: 620px;
        }
        .guide-table thead tr,
        .guide-article .table-wrapper thead tr {
            background: var(--guide-bg);
            border-bottom: 1.5px solid var(--guide-border);
        }
        .guide-table th,
        .guide-article .table-wrapper th {
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 800;
            color: var(--guide-text-primary);
            border-bottom: 1.5px solid var(--guide-border);
            border-right: 1px solid var(--guide-border);
            line-height: 1.4;
            vertical-align: middle;
        }
        .guide-table th:last-child,
        .guide-article .table-wrapper th:last-child {
            border-right: none;
        }
        .guide-table tbody tr,
        .guide-article .table-wrapper tbody tr {
            border-bottom: 1px solid var(--guide-border);
            transition: background 0.15s ease;
        }
        .guide-table tbody tr:last-child,
        .guide-article .table-wrapper tbody tr:last-child {
            border-bottom: none;
        }
        .guide-table tbody tr:hover,
        .guide-article .table-wrapper tbody tr:hover {
            background: var(--guide-accent-soft);
        }
        .guide-table td,
        .guide-article .table-wrapper td {
            padding: 12px 16px;
            color: var(--guide-text-secondary);
            border-right: 1px solid var(--guide-border);
            line-height: 1.55;
            vertical-align: top;
        }
        .guide-table td:last-child,
        .guide-article .table-wrapper td:last-child {
            border-right: none;
        }
        /* Sticky/Distinct first column for comparison aspects */
        .guide-table th:first-child,
        .guide-table td:first-child,
        .guide-article .table-wrapper th:first-child,
        .guide-article .table-wrapper td:first-child {
            width: 170px;
            min-width: 150px;
            font-weight: 700;
            color: var(--guide-text-primary);
            background: var(--guide-bg);
            white-space: nowrap;
        }
        /* Header badge pills for comparison columns */
        .guide-th-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 9999px;
            font-weight: 800;
            font-size: 11.5px;
            line-height: 1;
            white-space: nowrap;
        }
        .guide-th-pill svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }
        .guide-th-pill.pill-waste {
            background: rgba(225, 29, 72, 0.12);
            color: #be123c;
            border: 1px solid rgba(225, 29, 72, 0.25);
        }
        html.dark .guide-th-pill.pill-waste {
            background: rgba(251, 113, 133, 0.18);
            color: #fca5a5;
            border-color: rgba(251, 113, 133, 0.35);
        }
        .guide-th-pill.pill-opname {
            background: rgba(2, 132, 199, 0.12);
            color: #0369a1;
            border: 1px solid rgba(2, 132, 199, 0.25);
        }
        html.dark .guide-th-pill.pill-opname {
            background: rgba(56, 189, 248, 0.18);
            color: #7dd3fc;
            border-color: rgba(56, 189, 248, 0.35);
        }
        @media (max-width: 639px) {
            .guide-table,
            .guide-article table.data-table,
            .guide-article .table-wrapper table {
                font-size: 11.5px;
                min-width: 540px;
            }
            .guide-table th,
            .guide-table td,
            .guide-article .table-wrapper th,
            .guide-article .table-wrapper td {
                padding: 10px 12px;
            }
            .guide-table th:first-child,
            .guide-table td:first-child,
            .guide-article .table-wrapper th:first-child,
            .guide-article .table-wrapper td:first-child {
                width: 140px;
                min-width: 130px;
            }
            .guide-th-pill {
                font-size: 10.5px;
                padding: 4px 9px;
            }
        }

        /* 5.6 FEATURE CARDS GRID (Universal Search & High-Density Cards) */
        .feature-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
            margin: 16px 0;
        }
        @media (max-width: 639px) {
            .feature-cards-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }
        .feature-card {
            background: var(--guide-card-bg);
            border: 1px solid var(--guide-border);
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
        }
        .feature-card:hover {
            border-color: var(--guide-accent);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
        }
        html.dark .feature-card:hover {
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
        }
        .feature-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 13px;
            color: var(--guide-text-primary);
        }
        .feature-card-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .feature-card-icon svg {
            width: 16px;
            height: 16px;
        }
        .feature-card-body {
            font-size: 12px;
            color: var(--guide-text-secondary);
            line-height: 1.55;
        }

        /* 5.6 FLOATING BACK TO TOP BUTTON */
        .guide-btn-back-to-top {
            position: fixed;
            right: max(16px, env(safe-area-inset-right, 0px));
            bottom: max(20px, env(safe-area-inset-bottom, 0px));
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--guide-card-bg);
            border: 1px solid var(--guide-border);
            color: var(--guide-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            z-index: 90;
            transition: all 0.2s ease;
        }
        .guide-btn-back-to-top:hover {
            background: var(--guide-accent);
            color: #ffffff;
            border-color: var(--guide-accent);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(136, 19, 55, 0.25);
        }
        html.dark .guide-btn-back-to-top {
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.4);
        }
        @media (max-width: 639px) {
            .guide-btn-back-to-top {
                width: 36px;
                height: 36px;
                border-radius: 10px;
                right: max(12px, env(safe-area-inset-right, 0px));
                bottom: max(16px, env(safe-area-inset-bottom, 0px));
            }
            .guide-btn-back-to-top svg {
                width: 16px;
                height: 16px;
            }
            .sub-chapter-title {
                font-size: 13.5px;
            }
        }

        /* 6. MOBILE RESPONSIVENESS OVERRIDES */
        @media (max-width: 639px) {
            .guide-content-wrapper {
                padding: 14px 10px 60px 10px;
            }
            .guide-main-hero {
                padding: 16px 14px;
                border-radius: 16px;
                margin-bottom: 20px;
            }
            .hero-header-flex {
                gap: 12px;
            }
            .hero-brand-icon {
                width: 38px;
                height: 38px;
                border-radius: 10px;
            }
            .hero-brand-icon img,
            .hero-brand-logo-img {
                border-radius: 10px;
            }
            .hero-tag-wrap {
                font-size: 9.5px;
            }
            .hero-status-pill {
                font-size: 9px;
                padding: 2px 7px;
            }
            .hero-main-title {
                font-size: 17px;
                line-height: 1.3;
                margin-bottom: 4px;
            }
            .hero-main-desc {
                font-size: 11.5px;
                line-height: 1.5;
            }
            .guide-chapter {
                padding: 16px 12px;
                border-radius: 14px;
                margin-bottom: 16px;
            }
            .chapter-header {
                gap: 10px;
                margin-bottom: 12px;
                padding-bottom: 10px;
            }
            .chapter-icon-badge {
                width: 34px;
                height: 34px;
                border-radius: 10px;
            }
            .chapter-icon-badge svg {
                width: 18px;
                height: 18px;
            }
            .chapter-number {
                font-size: 10px;
            }
            .chapter-title {
                font-size: 14.5px;
                line-height: 1.25;
            }
            .role-pill {
                font-size: 9px;
                padding: 1px 5px;
            }
            .step-timeline {
                gap: 12px;
                margin: 12px 0;
            }
            .step-circle {
                width: 22px;
                height: 22px;
                font-size: 10px;
            }
            .step-title {
                font-size: 12.5px;
            }
            .step-desc {
                font-size: 11.5px;
            }
            .guide-box {
                padding: 10px 12px;
                font-size: 11.5px;
                gap: 8px;
            }
            .formula-card {
                font-size: 10.5px;
                padding: 10px;
                word-break: break-word;
            }
            .faq-card {
                padding: 12px;
                border-radius: 12px;
            }
            .faq-header {
                gap: 10px;
                margin-bottom: 6px;
            }
            .faq-badge-icon {
                width: 26px;
                height: 26px;
                border-radius: 8px;
            }
            .faq-badge-icon svg {
                width: 14px;
                height: 14px;
            }
            .faq-question {
                font-size: 12.5px;
                padding-top: 3px;
            }
            .faq-body {
                font-size: 11.5px;
                padding-left: 36px;
            }
        }

        /* Print Media */
        @media print {
            .guide-navbar,
            .guide-drawer,
            .guide-drawer-backdrop,
            .guide-btn-icon,
            .guide-search-wrapper {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 10pt;
            }
            .guide-chapter {
                border: 1px solid #cccccc !important;
                box-shadow: none !important;
                page-break-inside: avoid;
                margin-bottom: 20px !important;
            }
            .guide-main-hero {
                background: #f1f5f9 !important;
                color: #000000 !important;
                border: 1px solid #cccccc;
            }
        }
    </style>
    <script>
        function guideApp() {
            return {
                sidebarOpen: false,
                searchQuery: '',
                matchCount: 0,
                currentMatchIndex: 0,
                isDark: (function() {
                    var m = document.cookie.match(/(?:^|; )ksnack_theme=([^;]*)/);
                    return (m ? decodeURIComponent(m[1]) : 'light') === 'dark';
                })(),
                isPWA: (function() {
                    try {
                        return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) 
                            || (window.matchMedia && window.matchMedia('(display-mode: fullscreen)').matches)
                            || (window.matchMedia && window.matchMedia('(display-mode: minimal-ui)').matches)
                            || Boolean(window.navigator.standalone)
                            || Boolean(document.referrer && document.referrer.includes('android-app://'));
                    } catch(e) {
                        return false;
                    }
                })(),
                showBackToTop: false,
                scrollToTop() {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
                init() {
                    window.addEventListener('scroll', () => {
                        this.showBackToTop = (window.pageYOffset || document.documentElement.scrollTop || 0) > 400;
                    }, { passive: true });
                    this.$watch('searchQuery', (val) => {
                        if (window.guideSearchEngine) {
                            const res = window.guideSearchEngine.highlight(val);
                            this.matchCount = res.total;
                            this.currentMatchIndex = res.current;
                        }
                    });
                    this.$watch('sidebarOpen', (val) => {
                        if (val) {
                            document.body.style.overflow = 'hidden';
                            document.documentElement.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                            document.documentElement.style.overflow = '';
                        }
                    });
                },
                clearSearch() {
                    this.searchQuery = '';
                    if (window.guideSearchEngine) {
                        window.guideSearchEngine.clear();
                    }
                    this.matchCount = 0;
                    this.currentMatchIndex = 0;
                },
                nextMatch() {
                    if (window.guideSearchEngine && this.matchCount > 0) {
                        this.currentMatchIndex = window.guideSearchEngine.next();
                    }
                },
                prevMatch() {
                    if (window.guideSearchEngine && this.matchCount > 0) {
                        this.currentMatchIndex = window.guideSearchEngine.prev();
                    }
                },
                toggleTheme() {
                    this.isDark = !this.isDark;
                    const theme = this.isDark ? 'dark' : 'light';
                    document.documentElement.classList.toggle('dark', this.isDark);
                    document.cookie = 'ksnack_theme=' + theme + '; path=/; max-age=31536000';
                },
                closeOrReturn() {
                    const isStandalone = this.isPWA;
                    const referrer = document.referrer || '';
                    const isSameOriginReferrer = Boolean(referrer && referrer.startsWith(window.location.origin) && !referrer.includes('/guide') && !referrer.includes('/panduan'));

                    // 1. Skenario PWA (Progressive Web App Standalone di HP):
                    // DILARANG memanggil window.close() karena akan langsung menutup aplikasi PWA (keluar ke Home Screen HP)!
                    if (isStandalone) {
                        if (isSameOriginReferrer) {
                            window.location.href = referrer;
                        } else if (window.history.length > 1) {
                            window.history.back();
                        } else {
                            window.location.href = '<?= Router::url('/dashboard') ?>';
                        }
                        return;
                    }

                    // 2. Skenario Browser Biasa dengan window.opener (Popup / tab baru via window.open):
                    if (window.opener && !window.opener.closed) {
                        try {
                            window.close();
                        } catch (e) {}
                        setTimeout(() => {
                            if (isSameOriginReferrer) {
                                window.location.href = referrer;
                            } else {
                                window.location.href = '<?= Router::url('/dashboard') ?>';
                            }
                        }, 250);
                        return;
                    }

                    // 3. Skenario Browser Mobile / Tab Biasa (target="_blank" atau navigasi internal):
                    // Jika ada referrer internal dari origin yang sama, prioritaskan kembali ke sesi transaksi kerjaan
                    if (isSameOriginReferrer) {
                        try {
                            window.close();
                        } catch (e) {}
                        setTimeout(() => {
                            window.location.href = referrer;
                        }, 150);
                        return;
                    }

                    // 4. Jika dibuka langsung tanpa referrer internal (misal URL direct/bookmark/new tab):
                    try {
                        window.close();
                    } catch (e) {}

                    setTimeout(() => {
                        if (window.history.length > 1) {
                            window.history.back();
                        } else {
                            window.location.href = '<?= Router::url('/dashboard') ?>';
                        }
                    }, 200);
                }
            };
        }
    </script>
</head>
<body x-data="guideApp()" @keydown.window.escape="sidebarOpen = false">

    <!-- ===================================================================== -->
    <!-- 1. TOP NAVBAR                                                         -->
    <!-- ===================================================================== -->
    <header class="guide-navbar">
        <div class="guide-navbar-left">
            <!-- Hamburger Button (Opens Single Sidebar Drawer) -->
            <button type="button" 
                    class="guide-btn-icon" 
                    @click="sidebarOpen = !sidebarOpen" 
                    title="Daftar Isi Panduan (13 Bab)">
                <i data-lucide="menu"></i>
            </button>

            <!-- Brand Info (Main App Logo Badge) -->
            <div class="guide-brand-box">
                <div class="guide-brand-badge">
                    <img src="<?= Router::asset('/favicon/favicon-96x96.png') ?>" alt="<?= htmlspecialchars($companyName) ?>" class="guide-brand-logo-img">
                </div>
                <div class="guide-brand-text">
                    <h1><?= htmlspecialchars($companyName) ?></h1>
                    <span class="guide-brand-sub">Portal Panduan &amp; SOP Operasional</span>
                </div>
            </div>
        </div>

        <!-- Right Action Controls -->
        <div class="guide-nav-actions">
            <!-- Live In-Page Search -->
            <div class="guide-search-wrapper">
                <i data-lucide="search" class="guide-search-icon"></i>
                <input type="text" 
                       x-model="searchQuery" 
                       @keydown.enter.prevent="nextMatch()"
                       @keydown.escape.prevent="clearSearch()"
                       placeholder="Cari topik..." 
                       class="guide-search-input">
                <div class="guide-search-controls" x-show="searchQuery.trim().length > 0" x-cloak>
                    <!-- Match Counter -->
                    <span x-show="matchCount > 0" 
                          x-text="currentMatchIndex + '/' + matchCount" 
                          class="guide-search-count"
                          title="Tekan Enter untuk hasil berikutnya"></span>
                    <span x-show="searchQuery.trim().length >= 2 && matchCount === 0" 
                          class="guide-search-count" 
                          style="background:rgba(239, 68, 68, 0.15); color:#ef4444;"
                          title="Tidak ada kecocokan">0</span>
                    <!-- Clear Button -->
                    <button type="button" 
                            @click="clearSearch()" 
                            class="guide-search-clear" 
                            title="Bersihkan Pencarian (Esc)">
                        <i data-lucide="x" style="width:12px;height:12px;"></i>
                    </button>
                </div>
            </div>

            <!-- Print Button (Hidden on Mobile) -->
            <button type="button" 
                    class="guide-btn-icon hide-mobile" 
                    onclick="window.print()" 
                    title="Cetak Buku Panduan (Ctrl + P)">
                <i data-lucide="printer"></i>
            </button>

            <!-- Theme Toggle -->
            <button type="button" 
                    class="guide-btn-icon" 
                    @click="toggleTheme()" 
                    :title="isDark ? 'Mode Terang' : 'Mode Gelap'">
                <template x-if="isDark">
                    <i data-lucide="sun"></i>
                </template>
                <template x-if="!isDark">
                    <i data-lucide="moon"></i>
                </template>
            </button>

            <!-- Smart Close / Return Button -->
            <button type="button" 
                    class="guide-btn-close" 
                    @click="closeOrReturn()" 
                    :title="isPWA ? 'Kembali ke Aplikasi (Tutup Panduan)' : 'Tutup Panduan (Tutup Tab)'">
                <span x-show="isPWA" style="display:inline-flex;align-items:center;">
                    <i data-lucide="arrow-left" style="width:14px;height:14px;"></i>
                </span>
                <span x-show="!isPWA" style="display:inline-flex;align-items:center;">
                    <i data-lucide="x" style="width:14px;height:14px;"></i>
                </span>
                <span class="guide-btn-close-text" x-text="isPWA ? 'Kembali' : 'Tutup Panduan'">Tutup Panduan</span>
            </button>
        </div>
    </header>

    <!-- ===================================================================== -->
    <!-- 2. MAIN LAYOUT & SINGLE OFF-CANVAS SIDEBAR (TOC)                      -->
    <!-- ===================================================================== -->
    <div class="guide-layout">

        <!-- SINGLE OFF-CANVAS SIDEBAR (TOC - DEFAULT HIDDEN) -->
        <div x-show="sidebarOpen" 
             x-cloak 
             class="guide-drawer-backdrop" 
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"></div>

        <aside x-show="sidebarOpen" 
               x-cloak 
               class="guide-drawer custom-scrollbar"
               x-transition:enter="transition ease-out duration-200 transform"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-200 transform"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full">
            <div class="guide-drawer-header">
                <div class="guide-drawer-title">
                    <i data-lucide="book-open" style="width:16px;height:16px;color:var(--guide-accent);"></i>
                    <span>Daftar Isi Panduan</span>
                </div>
                <button type="button" class="guide-btn-icon" @click="sidebarOpen = false" title="Tutup Menu (Esc)">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <nav class="guide-drawer-nav">
                <div class="toc-header">14 Bab Standar Operasional</div>
                <ul class="toc-nav-list">
                    <li><a href="#bab-1-peran" class="toc-link" @click="sidebarOpen = false"><i data-lucide="shield"></i> <span>1. Peran &amp; Hak Akses</span></a></li>
                    <li><a href="#bab-2-master-harga" class="toc-link" @click="sidebarOpen = false"><i data-lucide="tag"></i> <span>2. Master Produk &amp; Harga</span></a></li>
                    <li><a href="#bab-3-pos-kasir" class="toc-link" @click="sidebarOpen = false"><i data-lucide="shopping-cart"></i> <span>3. Kasir POS Outlet</span></a></li>
                    <li><a href="#bab-4-b2b-hybrid" class="toc-link" @click="sidebarOpen = false"><i data-lucide="file-text"></i> <span>4. B2B &amp; Dokumen Hybrid</span></a></li>
                    <li><a href="#bab-5-konsinyasi-rolling" class="toc-link" @click="sidebarOpen = false"><i data-lucide="refresh-cw"></i> <span>5. Konsinyasi: Rolling Nota</span></a></li>
                    <li><a href="#bab-6-konsinyasi-tagihan" class="toc-link" @click="sidebarOpen = false"><i data-lucide="receipt"></i> <span>6. Konsinyasi: Kolektif Tagihan</span></a></li>
                    <li><a href="#bab-7-pembelian-vendor" class="toc-link" @click="sidebarOpen = false"><i data-lucide="shopping-bag"></i> <span>7. Pengadaan &amp; Multi-Vendor</span></a></li>
                    <li><a href="#bab-inventori-opname" class="toc-link" @click="sidebarOpen = false"><i data-lucide="warehouse"></i> <span>8. Inventaris &amp; Bulk Opname</span></a></li>
                    <li><a href="#bab-8-logistik-pengiriman" class="toc-link" @click="sidebarOpen = false"><i data-lucide="truck"></i> <span>9. Logistik &amp; Pengiriman</span></a></li>
                    <li><a href="#bab-9-keuangan-kas" class="toc-link" @click="sidebarOpen = false"><i data-lucide="wallet"></i> <span>10. Kas Tertutup &amp; Rekening Escrow</span></a></li>
                    <li><a href="#bab-10-hr-penggajian" class="toc-link" @click="sidebarOpen = false"><i data-lucide="users"></i> <span>11. SDM, Kasbon &amp; Penggajian</span></a></li>
                    <li><a href="#bab-11-faq-masalah" class="toc-link" @click="sidebarOpen = false"><i data-lucide="help-circle"></i> <span>12. Solusi Masalah Lapangan</span></a></li>
                    <li><a href="#bab-12-setup-perusahaan" class="toc-link" @click="sidebarOpen = false"><i data-lucide="building"></i> <span>13. Profil Usaha &amp; Impor Excel</span></a></li>
                    <li><a href="#bab-13-tips-navigasi" class="toc-link" @click="sidebarOpen = false"><i data-lucide="sparkles"></i> <span>14. Tips Navigasi &amp; PWA HP</span></a></li>
                </ul>
            </nav>
        </aside>

        <!-- MAIN ARTICLE BODY -->
        <main class="guide-content-wrapper">
            <article class="guide-article">

                <!-- 1. HERO BANNER -->
                <div class="guide-main-hero">
                    <div class="hero-header-flex">
                        <div class="hero-brand-icon">
                            <img src="<?= Router::asset('/favicon/favicon_guide.svg') ?>?v=<?= $favGuideV ?>" alt="<?= htmlspecialchars($companyName) ?>" class="hero-brand-logo-img">
                        </div>
                        <div class="hero-header-body">
                            <div class="hero-top-row">
                                <div class="hero-tag-wrap">
                                    <span class="hero-tag-dot"></span>
                                    <span>Standar Operasional Prosedur (SOP) Resmi</span>
                                </div>
                                <div class="hero-status-pill">
                                    <span>TERVERIFIKASI</span>
                                </div>
                            </div>
                            <h2 class="hero-main-title">Buku Panduan Kerja &amp; Standar Mutu ERP</h2>
                            <p class="hero-main-desc">
                                Standar kanonikal alur operasional terpadu untuk menyelaraskan Manajemen, Admin Kantor, Sales Lapangan, Driver Logistik, Petugas Gudang, dan Kasir POS di <strong><?= htmlspecialchars($companyName) ?></strong>.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- BAB 1: PERAN & HAK AKSES                                  -->
                <!-- ========================================================= -->
                <section id="bab-1-peran" class="guide-chapter theme-indigo">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="shield"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 1</span>
                            <h3 class="chapter-title">Struktur Peran, Wewenang &amp; Hak Akses Karyawan</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Semua Staf</span>
                                <span class="role-pill">Kepala Divisi</span>
                                <span class="role-pill">Owner</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Aplikasi ERP ini dirancang dengan pembagian wewenang yang tegas untuk setiap bagian kerja. Tujuannya adalah agar data keuangan terlindungi, operasional berjalan tertib, dan setiap staf dapat fokus pada tugas pekerjaannya masing-masing tanpa kebingungan:
                    </p>
                    <div class="step-timeline">
                        <div class="step-item">
                            <div class="step-circle">1</div>
                            <div class="step-content">
                                <h4 class="step-title">Owner &amp; Manajemen Eksekutif</h4>
                                <p class="step-desc">Memegang wewenang pengawasan tertinggi. Mengakses <strong>Executive Dashboard</strong> untuk memantau grafik omzet penjualan riil, laba kotor harian, perputaran aset inventori gudang, persetujuan batas piutang besar, serta persetujuan akhir pembayaran gaji karyawan (Payroll Approval).</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <div class="step-content">
                                <h4 class="step-title">Kepala Kantor / Admin Operasional</h4>
                                <p class="step-desc">Mengatur jalannya operasional kantor: master data produk, level harga jual, pesanan grosir B2B, penjadwalan rute pengiriman armada, penagihan tempo konsinyasi, dan koordinasi kelancaran antar bagian.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-content">
                                <h4 class="step-title">Admin Keuangan &amp; Kasir Kantor (Finance)</h4>
                                <p class="step-desc">Bertanggung jawab atas arus kas perusahaan: mencatat biaya pengeluaran operasional (listrik, bensin, konsumsi), transfer saldo bank, menerima uang setoran fisik dari driver di sore hari, memverifikasi permohonan kasbon, serta menyiapkan rekapitulasi penggajian.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">4</div>
                            <div class="step-content">
                                <h4 class="step-title">Kasir POS Outlet (Penjualan Ritel Langsung)</h4>
                                <p class="step-desc">Melayani pembeli langsung di toko/outlet dengan menu Kasir POS. Bertugas melakukan scan barcode produk, menerima pembayaran (Tunai, QRIS, atau Transfer Bank), mencetak struk belanja, dan menghitung uang fisik kasir saat pergantian jam kerja (tutup shift).</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">5</div>
                            <div class="step-content">
                                <h4 class="step-title">Sales Lapangan &amp; Pemasaran</h4>
                                <p class="step-desc">Ujung tombak relasi dengan toko mitra: membuka pesanan baru, melakukan kunjungan berkala, mengecek sisa stok di rak konsinyasi (opname fisik), serta membina kemitraan toko di wilayah tugasnya.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">6</div>
                            <div class="step-content">
                                <h4 class="step-title">Driver &amp; Tim Logistik Pengiriman</h4>
                                <p class="step-desc">Mengantar pesanan barang ke toko pelanggan sesuai Surat Jalan, menjalankan tugas belanja bahan baku ke supplier (dengan memfoto nota/bon struk belanja), menagih pembayaran tunai di toko mitra rolling nota, dan menyetorkan seluruh uang tagihan ke kasir kantor di sore hari.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">7</div>
                            <div class="step-content">
                                <h4 class="step-title">Petugas Gudang &amp; Pengadaan</h4>
                                <p class="step-desc">Menjaga keamanan fisik persediaan: memeriksa dan menimbang bahan baku yang baru tiba dari supplier, menyiapkan dan membungkus barang sesuai lembar <em>Picking List</em> pesanan, memantau batas stok minimum di katalog inventori, serta melakukan audit stok massal berkala melalui modul <strong>Bulk Opname Gudang</strong> (dengan tab kategori Bahan Mentah, Bahan Kemas, dan Barang Jadi) untuk memastikan angka buku besar sistem 100% cocok dengan fisik di rak.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">8</div>
                            <div class="step-content">
                                <h4 class="step-title">Mandor Produksi &amp; Bagian HR</h4>
                                <p class="step-desc">Mencatat kehadiran harian staf (absensi), mencatat hasil kerja harian karyawan borongan per jenis kemasan, menginput pengambilan uang harian tenaga borongan, dan memastikan data kerja harian siap diproses saat jadwal gajian.</p>
                            </div>
                        </div>
                    </div>

                    <div class="guide-box box-info">
                        <i data-lucide="shield-check"></i>
                        <div>
                            <strong>Prinsip Keamanan Akun Pengguna:</strong><br>
                            Setiap karyawan memiliki akun login unik sendiri. Dilarang saling meminjamkan akun atau kata sandi (*password*) karena setiap aktivitas input transaksi, perubahan data, dan penghapusan otomatis terekam jejak auditnya (*Audit Trail*) secara permanen di sistem.
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 2: MASTER DATA & FITUR PENCARIAN PINTAR                -->
                <!-- ========================================================= -->
                <section id="bab-2-master-harga" class="guide-chapter theme-emerald">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="database"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 2</span>
                            <h3 class="chapter-title">Master Data &amp; Fitur Pencarian Pintar (Universal Global Search)</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Admin</span>
                                <span class="role-pill">Sales</span>
                                <span class="role-pill">Logistik</span>
                                <span class="role-pill">Owner</span>
                            </div>
                        </div>
                    </div>

                    <!-- SUB-BAB 2.1: UNIVERSAL SEARCH EXPLANATION -->
                    <div class="sub-chapter-block">
                        <h4 class="sub-chapter-title">
                            <i data-lucide="search"></i>
                            <span>2.1 Fitur Kolom Pencarian Pintar (Live Debounce &amp; Universal Multi-Field Search)</span>
                        </h4>
                        <p class="step-desc">
                            Seluruh halaman pada menu <strong>Master Data</strong> (<em>Toko Pelanggan, Produk &amp; Bahan, Matriks Level Harga, Pemasok Vendor, dan Data Karyawan</em>) telah dilengkapi dengan teknologi <strong>Live Debounce &amp; Universal Global Search</strong>:
                        </p>

                        <div class="feature-cards-grid">
                            <div class="feature-card">
                                <div class="feature-card-header">
                                    <div class="feature-card-icon" style="background:rgba(16,185,129,0.12);color:#10b981;">
                                        <i data-lucide="zap"></i>
                                    </div>
                                    <span>Pencarian Otomatis (Live Debounce)</span>
                                </div>
                                <div class="feature-card-body">
                                    Cukup ketik kata kunci, sistem secara otomatis mengeksekusi pencarian setelah jeda mengetik 0,3 detik (350 ms) <strong>tanpa perlu menekan tombol Cari atau Enter</strong>.
                                </div>
                            </div>

                            <div class="feature-card">
                                <div class="feature-card-header">
                                    <div class="feature-card-icon" style="background:rgba(14,165,233,0.12);color:#0284c7;">
                                        <i data-lucide="loader-2"></i>
                                    </div>
                                    <span>Loading Cepat Hanya di Tabel</span>
                                </div>
                                <div class="feature-card-body">
                                    Tidak ada kedip layar (<em>zero full-page reload</em>). Hanya area tabel data yang memuat animasi <em>shimmer bar</em> halus saat mengambil data, kursor tetap fokus di kolom input.
                                </div>
                            </div>

                            <div class="feature-card">
                                <div class="feature-card-header">
                                    <div class="feature-card-icon" style="background:rgba(99,102,241,0.12);color:#6366f1;">
                                        <i data-lucide="globe"></i>
                                    </div>
                                    <span>Pencarian Database Menyeluruh</span>
                                </div>
                                <div class="feature-card-body">
                                    Mencakup 100% basis data dari 1 kolom pencarian tunggal: nama, kode unik, nomor WA/telepon, PIC, alamat, sales pembina, rute, rekening bank, hingga tipe pembayaran.
                                </div>
                            </div>
                        </div>

                        <!-- Info Popover Explanation -->
                        <div class="guide-box box-info" style="margin:12px 0;">
                            <i data-lucide="info"></i>
                            <div>
                                <strong>Ikon Info Bantuan ℹ️ di Samping Kolom Search:</strong><br>
                                Arahkan kursor (*hover*) pada komputer atau tap ikon <code>ℹ️</code> pada layar sentuh/HP untuk melihat kartu popover panduan atribut lengkap apa saja yang dapat dicari pada halaman tersebut.
                            </div>
                        </div>

                        <!-- Search Tips -->
                        <div style="background:var(--guide-bg);border:1px solid var(--guide-border);border-radius:12px;padding:12px 14px;margin-top:12px;">
                            <div style="font-weight:800;font-size:12px;color:var(--guide-text-primary);margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                                <i data-lucide="zap" style="width:14px;height:14px;color:#f59e0b;"></i>
                                <span>Tips Efisiensi Pencarian Cepat:</span>
                            </div>
                            <ul style="font-size:11.5px;color:var(--guide-text-secondary);line-height:1.6;padding-left:18px;margin:0;">
                                <li>• <strong>Pencarian Kode Cepat:</strong> Cukup ketik prefix kode seperti <code>CUST-</code> (toko), <code>FG-</code> (barang jadi), <code>RAW-</code>/<code>PKG-</code> (bahan baku), <code>SUP-</code> (vendor), atau <code>RTE-</code> (rute).</li>
                                <li>• <strong>Pencarian No. HP / WhatsApp / Rekening:</strong> Cukup ketik beberapa digit nomor kontak atau rekening untuk menemukan data yang bersangkutan seketika.</li>
                                <li>• <strong>Tombol Reset ✕ Instan:</strong> Klik tombol <code>✕</code> pada ujung kanan kolom input untuk mengosongkan pencarian dan mereset tabel kembali ke daftar awal secara instan.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- SUB-BAB 2.2: MASTER PRODUK & MATRIKS HARGA -->
                    <div class="sub-chapter-block">
                        <h4 class="sub-chapter-title">
                            <i data-lucide="tag"></i>
                            <span>2.2 Matriks 30 Tingkat Level Harga &amp; Resep BOM</span>
                        </h4>
                        <p class="step-desc">
                            Penetapan harga jual di sistem dikelola melalui menu <strong>Matriks Level Harga</strong> (Sidebar: <em>Master Data &rarr; Matriks Level Harga</em>) menggunakan <strong>Matriks Level Harga Multi-Tier (Level 1 s/d Level 30)</strong> dan diskon dinamis berbasis grup mitra toko:
                        </p>
                        <div class="guide-box box-success">
                            <i data-lucide="check-circle-2"></i>
                            <div>
                                <strong>Level 1 (Default Ritel):</strong> Digunakan untuk transaksi tunai langsung di menu Kasir POS.<br>
                                <strong>Level 2 - 30 (Grosir &amp; Mitra):</strong> Diberikan secara khusus ke toko mitra pelanggan (agen, reseller, minimarket, konsinyasi) dengan margin khusus.<br>
                                <strong>Resep BOM (Bill of Materials):</strong> Mengikat bahan mentah, bumbu, dan plastik ke produk jadi sehingga saat batch produksi dibuat, bahan baku berkurang otomatis dan HPP terhitung presisi.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 3: KASIR POS                                          -->
                <!-- ========================================================= -->
                <section id="bab-3-pos-kasir" class="guide-chapter theme-amber">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="shopping-cart"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 3</span>
                            <h3 class="chapter-title">Kasir POS (Penjualan Outlet / Ritel Langsung)</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Kasir</span>
                                <span class="role-pill">Admin</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Layar transaksi kasir POS dapat dibuka melalui menu <strong>Kasir POS</strong> (Sidebar: <em>Penjualan &amp; Transaksi &rarr; Kasir POS</em>), dirancang dengan navigasi keyboard cepat dan scan barcode otomatis untuk penjualan tunai/QRIS di outlet:
                    </p>
                    <ul class="step-desc" style="padding-left:20px; margin:10px 0;">
                        <li>Scan Barcode produk atau ketik nama SKU pada kolom pencarian cepat.</li>
                        <li>Pilih metode pembayaran (Tunai, Debit, Transfer, atau QRIS).</li>
                        <li>Cetak struk nota belanja kasir atau kirim digital.</li>
                        <li>Setiap checkout kasir otomatis memotong stok fisik gudang dan membukukan pendapatan ke Akun Kas Kasir.</li>
                    </ul>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 4: B2B & DOKUMEN HYBRID                               -->
                <!-- ========================================================= -->
                <section id="bab-4-b2b-hybrid" class="guide-chapter theme-blue">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="file-text"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 4</span>
                            <h3 class="chapter-title">Pesanan B2B, Logistik &amp; Dokumen Hybrid Kanonikal</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Admin</span>
                                <span class="role-pill">Gudang</span>
                                <span class="role-pill">Driver</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Sistem mendukung 2 skenario pemesanan di menu <strong>Pesanan Pelanggan</strong> (Sidebar: <em>Penjualan &amp; Transaksi &rarr; Pesanan Pelanggan</em>):
                    </p>

                    <!-- Sub-Seksi 1: Penjualan Reguler -->
                    <div style="margin-top:14px; margin-bottom:8px;">
                        <h4 style="font-size:13.5px; font-weight:800; color:var(--guide-text-primary); margin-bottom:6px;">
                            A. Alur Penjualan Reguler (Jual Putus / Grosir)
                        </h4>
                    </div>
                    <div class="step-timeline">
                        <div class="step-item">
                            <div class="step-circle">1</div>
                            <div class="step-content">
                                <h4 class="step-title">Penerbitan PO Masuk (Draf Antrean)</h4>
                                <p class="step-desc">Admin/Sales membuat pesanan baru. Status awal adalah <code>PO</code>. Pada fase ini, <strong>stok fisik gudang belum terpotong</strong>, sehingga data pesanan masih bebas diedit atau dibatalkan.</p>
                                <div class="guide-box box-warning">
                                    <i data-lucide="lock"></i>
                                    <div><strong>PO Locked State:</strong> Saat berstatus PO, tombol cetak dan PDF terkunci demi mencegah pengiriman barang sebelum dipacking gudang.</div>
                                </div>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <div class="step-content">
                                <h4 class="step-title">Proses Gudang &amp; Terbit Surat Jalan (Stok Terpotong)</h4>
                                <p class="step-desc">Petugas gudang menyiapkan barang fisik. Setelah status diubah menjadi <code>disiapkan</code> atau <code>siap kirim</code>, sistem otomatis <strong>memotong stok fisik gudang</strong> dan menerbitkan <strong>Nomor Surat Jalan resmi (<code>SJ-...</code>)</strong>.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-content">
                                <h4 class="step-title">Cetak Dokumen Hybrid &amp; Serah Terima</h4>
                                <p class="step-desc">Cetak dokumen via tombol <em>Cetak Faktur &amp; SJ</em>. Dokumen hybrid menyatukan No. Faktur <code>NOTA-...</code> dan No. Surat Jalan <code>SJ-...</code> dalam satu lembar dengan <strong>3 Blok Tanda Tangan Resmi</strong> (Petugas Gudang, Driver, Penerima Toko). Setelah status diubah ke <code>selesai</code>, pelunasan tercatat di Buku Kas.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sub-Seksi 2: Titip Jual Konsinyasi via PO -->
                    <div style="margin-top:20px; margin-bottom:8px;">
                        <h4 style="font-size:13.5px; font-weight:800; color:var(--guide-text-primary); margin-bottom:6px;">
                            B. Alur Distribusi Titipan Konsinyasi (Non-Tagihan)
                        </h4>
                    </div>
                    <div class="step-timeline">
                        <div class="step-item">
                            <div class="step-circle">1</div>
                            <div class="step-content">
                                <h4 class="step-title">Distribusi Barang Titipan (Rp 0)</h4>
                                <p class="step-desc">Barang dikirim ke rak toko mitra murni sebagai titipan. <strong>Tidak ada kewajiban pembayaran tunai</strong> maupun pencatatan piutang di awal pengiriman.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <div class="step-content">
                                <h4 class="step-title">Mutasi ke Stok Rak Toko Mitra</h4>
                                <p class="step-desc">Setelah barang dikonfirmasi sampai oleh driver, kuantitas fisik otomatis dipindahkan dari Gudang Utama menuju saldo <strong>Stok Rak Toko Mitra</strong> secara sistem.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-content">
                                <h4 class="step-title">Penagihan Berkala via Opname</h4>
                                <p class="step-desc">Saat sales berkunjung rutin untuk opname, sistem menghitung barang laku dan menerbitkan Faktur Tagihan hanya dari selisih barang yang terjual.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 5: KONSINYASI ROLLING NOTA                            -->
                <!-- ========================================================= -->
                <section id="bab-5-konsinyasi-rolling" class="guide-chapter theme-rose">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="refresh-cw"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 5</span>
                            <h3 class="chapter-title">Konsinyasi — Alur Rolling Nota &amp; 8 Kolom Opname Rak</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Sales Lapangan</span>
                                <span class="role-pill">Driver</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Sistem konsinyasi KEREN ONE menerapkan <strong>Alur Rolling Antar-Nota</strong> (ditetapkan pada Master Toko Pelanggan dengan <em>Model: Konsinyasi</em> dan <em>Tipe Konsinyasi: Rolling Nota</em>) di menu <strong>Konsinyasi</strong> (Sidebar: <em>Penjualan &amp; Transaksi &rarr; Konsinyasi &rarr; Tab Form Opname</em>):
                    </p>
                    
                    <div class="step-timeline">
                        <div class="step-item">
                            <div class="step-circle">1</div>
                            <div class="step-content">
                                <h4 class="step-title">Buka Form Opname di Toko Mitra</h4>
                                <p class="step-desc">Pilih toko mitra pada aplikasi HP. Sistem otomatis menarik sisa rak dari nota kunjungan sebelumnya ke kolom <strong>"Sisa Stok Lalu"</strong> tanpa perlu input ulang!</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <div class="step-content">
                                <h4 class="step-title">Input Fisik Rak (8 Kolom Standar)</h4>
                                <p class="step-desc">Sales/Driver memeriksa rak dan mengisi formulir 8 kolom:</p>
                                <ul style="padding-left:18px; margin:8px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.6;">
                                    <li><strong>1. Sisa Stok Lalu:</strong> Sisa fisik di rak dari nota sebelumnya (otomatis).</li>
                                    <li><strong>2. Kirim Hari Ini:</strong> Kuantitas barang segar yang dibawa (dari PO titipan).</li>
                                    <li><strong>3. Jumlah Titip:</strong> Total modal rak = <em>Sisa Lalu + Kirim Hari Ini</em>.</li>
                                    <li><strong>4. Retur Rusak (BS):</strong> Bungkus bocor/cacat (ditarik dan tidak ditagih ke toko).</li>
                                    <li><strong>5. Sisa di Rak:</strong> Fisik aktual yang tersisa di rak saat dihitung.</li>
                                    <li><strong>6. Laku Terjual:</strong> <code>Titip - (Retur Rusak + Sisa Rak)</code>.</li>
                                    <li><strong>7. Retur Bagus:</strong> Produk ditarik kembali ke gudang pusat bila overstock.</li>
                                    <li><strong>8. Selisih Rak:</strong> Minus = barang hilang (gantung); Plus = surplus rak.</li>
                                </ul>
                                <div class="formula-card">
                                    Laku Terjual = (Sisa Lalu + Kirim Hari Ini) &minus; (Retur Rusak + Sisa Rak)<br>
                                    Total Tagihan = Laku Terjual &times; Harga Satuan Kesepakatan
                                </div>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-content">
                                <h4 class="step-title">Terima Pembayaran &amp; Cetak Nota Nusantara</h4>
                                <p class="step-desc">Terima uang tunai atau bukti transfer, lalu cetak <strong>Nota Khusus Supplier Konsinyasi</strong> lengkap dengan rincian perputaran rak dan 3 tanda tangan.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 6: KONSINYASI KOLEKTIF TAGIHAN                        -->
                <!-- ========================================================= -->
                <section id="bab-6-konsinyasi-tagihan" class="guide-chapter theme-purple">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="receipt"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 6</span>
                            <h3 class="chapter-title">Konsinyasi — Alur Kolektif Tagihan (Modern Trade &amp; Tempo)</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Admin</span>
                                <span class="role-pill">Finance</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        SOP penagihan konsolidasi untuk jaringan minimarket atau toko mitra tempo (ditetapkan pada Master Toko Pelanggan dengan <em>Model: Konsinyasi</em> dan <em>Tipe Konsinyasi: Kolektif Tagihan</em>) melalui menu <strong>Konsinyasi</strong> (Sidebar: <em>Penjualan &amp; Transaksi &rarr; Konsinyasi &rarr; Tab Kolektif Tagihan</em>):
                    </p>
                    <div class="step-timeline">
                        <div class="step-item">
                            <div class="step-circle">1</div>
                            <div class="step-content">
                                <h4 class="step-title">Kunjungan Opname Non-Tunai</h4>
                                <p class="step-desc">Sales melakukan kunjungan opname fisik seperti biasa tanpa menerima uang tunai di tempat.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <div class="step-content">
                                <h4 class="step-title">Batch Invoicing (Penerbitan Faktur Kolektif)</h4>
                                <p class="step-desc">Admin membuka Sub-Tab <strong>"Kunjungan Siap Ditagih"</strong>, memilih toko mitra, mencentang 1 atau beberapa kunjungan yang ingin digabungkan, lalu klik <strong>"Buat Tagihan Konsinyasi"</strong>. Faktur resmi berformat <code>INV-KONSIN-YYYYMMDD-HHMMSS</code> otomatis terbit.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-content">
                                <h4 class="step-title">Kirim Faktur &amp; Piutang Berjalan</h4>
                                <p class="step-desc">Faktur dikirimkan ke manajemen toko mitra. Nilai faktur otomatis menambah saldo <em>Piutang Berjalan</em> toko di database.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">4</div>
                            <div class="step-content">
                                <h4 class="step-title">Pencatatan Pembayaran (Lunas / Cicil)</h4>
                                <p class="step-desc">Saat toko membayar via transfer/tunai, buka tab <strong>Daftar Tagihan</strong> dan klik <em>Catat Pembayaran</em>. Sistem mendukung pelunasan 100% penuh atau bertahap (cicil). Uang otomatis masuk ke Buku Kas dan memotong piutang toko.</p>
                            </div>
                        </div>
                    </div>

                    <div class="guide-box box-info">
                        <i data-lucide="shield-check"></i>
                        <div>
                            <strong>4 Aturan Mutlak Sistem Penagihan Konsinyasi:</strong><br>
                            1. <em>Single-Partner Rule:</em> Seluruh kunjungan dalam 1 faktur wajib berasal dari toko yang sama.<br>
                            2. <em>Anti Double-Billing:</em> Kunjungan yang sudah difakturkan dikunci permanen agar tidak tertagih dobel.<br>
                            3. <em>Syarat Penjualan Riil:</em> Kunjungan bernilai Rp 0 tidak dapat dijadikan faktur piutang.<br>
                            4. <em>Proteksi Overpayment:</em> Database menolak pencatatan bayar yang melebihi sisa piutang faktur.
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 7: PENGADAAN & MULTI-VENDOR                           -->
                <!-- ========================================================= -->
                <section id="bab-7-pembelian-vendor" class="guide-chapter theme-teal">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="shopping-bag"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 7</span>
                            <h3 class="chapter-title">Pengadaan Bahan Baku &amp; Katalog Multi-Vendor</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Admin Gudang</span>
                                <span class="role-pill">Driver Pengadaan</span>
                                <span class="role-pill">Finance</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Pengelolaan pembelian bahan baku mentah, bumbu racik, dan bahan kemasan plastik di menu <strong>Pembelian Vendor</strong> (Sidebar: <em>Gudang &amp; Pembelian &rarr; Pembelian Vendor</em>):
                    </p>

                    <div class="step-timeline">
                        <div class="step-item">
                            <div class="step-circle">1</div>
                            <div class="step-content">
                                <h4 class="step-title">Katalog Multi-Vendor &amp; Riwayat Pemasok</h4>
                                <p class="step-desc">
                                    Sistem ERP mendukung <strong>Katalog Multi-Vendor</strong>, yaitu 1 jenis bahan baku (misal: Singkong Basah, Minyak Goreng, Bumbu Balado, atau Plastik 250g) dapat memiliki beberapa supplier rekanan sekaligus. Setiap supplier tercatat dengan nomor part/SKU vendor, harga beli kesepakatan, dan catatan mutu pasokan. Saat admin membuat pesanan, sistem otomatis menyajikan pilihan vendor rekanan lengkap dengan harga beli terakhir.
                                </p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <div class="step-content">
                                <h4 class="step-title">Dua Pilihan Mode Input Pengadaan</h4>
                                <ul style="padding-left:18px; margin:4px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.6;">
                                    <li><strong>+ Catat Faktur Langsung:</strong> Digunakan jika barang sudah dibeli secara tunai dan barang fisik telah berada di gudang. Stok gudang langsung bertambah dan kas langsung terpotong saat form disimpan.</li>
                                    <li><strong>+ Buat PO Pembelian:</strong> Digunakan untuk rencana pemesanan ke supplier atau menugaskan belanja ke driver. Stok gudang dan saldo kas <em>belum berubah</em> sampai fisik barang tiba dan diverifikasi di gudang.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-content">
                                <h4 class="step-title">Dua Metode Logistik Pengadaan Barang</h4>
                                <ul style="padding-left:18px; margin:4px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.6;">
                                    <li><strong>Diambil Driver Toko (Armada Belanja):</strong> Tugas belanja otomatis muncul di aplikasi HP Driver yang ditugaskan. Driver datang ke lokasi vendor, membeli barang, memfoto bon struk belanja melalui HP, dan membawa barang ke gudang pabrik.</li>
                                    <li><strong>Diantar oleh Pemasok:</strong> Pihak supplier rekanan atau jasa ekspedisi mengantarkan barang langsung ke gudang sesuai tanggal perkiraan tiba.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">4</div>
                            <div class="step-content">
                                <h4 class="step-title">SOP Verifikasi Fisik &amp; Penerimaan Barang Gudang</h4>
                                <p class="step-desc">
                                    Setiap barang PO yang tiba wajib dicek fisik dan ditimbang oleh Petugas Gudang dengan menekan tombol <strong>[📦 Terima]</strong> pada tabel pembelian &rarr; periksa kesesuaian kuantitas fisik dan foto bon struk belanja &rarr; klik <strong>"Konfirmasi Terima &amp; Tambah Stok"</strong>. Stok resmi bertambah ke kartu stok gudang dan nilai HPP bahan baku diperbarui otomatis.
                                </p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">5</div>
                            <div class="step-content">
                                <h4 class="step-title">Pelunasan Hutang Dagang Supplier</h4>
                                <p class="step-desc">
                                    Jika pembelian dilakukan secara tempo, nilai faktur otomatis masuk ke buku hutang dagang supplier. Pembayaran dicatat pada tab <em>Daftar Pembelian</em> &rarr; klik <em>Catat Bayar Hutang</em> &rarr; pilih Akun Kas atau Rekening Bank sumber pembayaran.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="guide-box box-danger">
                        <i data-lucide="alert-triangle"></i>
                        <div>
                            <strong>Penanganan Kendala Driver Belanja:</strong> Apabila driver di lapangan menemukan supplier tutup atau stok bahan habis, driver memilih opsi <em>"Laporkan Kendala"</em> di HP. Status PO otomatis berubah menjadi <code>KENDALA / BATAL</code> dan tombol terima terkunci demi mencegah salah input stok fiktif. Admin kantor dapat membuka Detail PO untuk menjadwalkan ulang atau membatalkan PO.
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 8: INVENTARIS GUDANG, KARTU STOK & BULK OPNAME FISIK  -->
                <!-- ========================================================= -->
                <section id="bab-inventori-opname" class="guide-chapter theme-emerald">
                    <a id="bab-inventaris-opname" href="#bab-inventaris-opname" style="display:none;" aria-hidden="true"></a>
                    <a id="bab-bulk-opname" href="#bab-bulk-opname" style="display:none;" aria-hidden="true"></a>
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="warehouse"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 8</span>
                            <h3 class="chapter-title">Inventaris Gudang, Kartu Stok &amp; Bulk Opname Fisik</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Petugas Gudang</span>
                                <span class="role-pill">Admin Operasional</span>
                                <span class="role-pill">Super Admin</span>
                                <span class="role-pill">Owner</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Pengelolaan saldo stok fisik persediaan, audit riwayat mutasi keluar-masuk, penyesuaian cepat, dan tata cara pelaksanaan audit opname massal berkecepatan tinggi di menu <strong>Inventaris</strong> (Sidebar: <em>Gudang &amp; Pembelian &rarr; Inventaris</em> dan tombol <em>Bulk Opname Gudang</em>):
                    </p>

                    <!-- SUB-BAB 8.1: MONITORING STOK REALTIME & 5 KARTU KPI GUDANG -->
                    <div class="sub-chapter-block">
                        <h4 class="sub-chapter-title">
                            <i data-lucide="layout-grid"></i>
                            <span>8.1 Monitoring Stok Real-Time &amp; 5 Kartu Indikator KPI Gudang</span>
                        </h4>
                        <p class="step-desc">
                            Halaman utama <strong>Inventaris Stok</strong> menyajikan ringkasan visual metrik persediaan secara komprehensif tanpa perlu kalkulasi manual:
                        </p>

                        <div class="feature-cards-grid">
                            <div class="feature-card">
                                <div class="feature-card-header">
                                    <div class="feature-card-icon" style="background:rgba(99,102,241,0.12);color:#6366f1;">
                                        <i data-lucide="package"></i>
                                    </div>
                                    <span>Total Katalog (SKU)</span>
                                </div>
                                <div class="feature-card-body">
                                    Menghitung seluruh varian produk jadi, bahan baku mentah, dan bahan kemas yang aktif terdaftar dalam database sistem.
                                </div>
                            </div>

                            <div class="feature-card">
                                <div class="feature-card-header">
                                    <div class="feature-card-icon" style="background:rgba(16,185,129,0.12);color:#059669;">
                                        <i data-lucide="boxes"></i>
                                    </div>
                                    <span>Total Fisik Gudang</span>
                                </div>
                                <div class="feature-card-body">
                                    Akumulasi jumlah fisik seluruh persediaan yang berada di dalam gudang dalam satuan dasar (pcs, kg, atau rol).
                                </div>
                            </div>

                            <div class="feature-card">
                                <div class="feature-card-header">
                                    <div class="feature-card-icon" style="background:rgba(37,99,235,0.12);color:#2563eb;">
                                        <i data-lucide="coins"></i>
                                    </div>
                                    <span>Valuasi Aset Gudang (HPP)</span>
                                </div>
                                <div class="feature-card-body">
                                    Nilai total kapital modal persediaan yang tersimpan di gudang berdasarkan Harga Pokok Pembelian (HPP) riil yang dihitung otomatis.
                                </div>
                            </div>

                            <div class="feature-card">
                                <div class="feature-card-header">
                                    <div class="feature-card-icon" style="background:rgba(217,119,6,0.12);color:#d97706;">
                                        <i data-lucide="alert-triangle"></i>
                                    </div>
                                    <span>Stok Menipis (Peringatan)</span>
                                </div>
                                <div class="feature-card-body">
                                    Jumlah SKU yang kuantitasnya telah menyentuh atau di bawah batas minimum peringatan (*reorder point*), menjadi sinyal bagi tim belanja/produksi untuk segera restock.
                                </div>
                            </div>

                            <div class="feature-card">
                                <div class="feature-card-header">
                                    <div class="feature-card-icon" style="background:rgba(220,38,38,0.12);color:#dc2626;">
                                        <i data-lucide="x-circle"></i>
                                    </div>
                                    <span>Stok Habis / Kosong (0)</span>
                                </div>
                                <div class="feature-card-body">
                                    Daftar produk atau bahan yang persediaannya telah habis total (0 pcs), otomatis mengunci transaksi penjualan agar tidak terjadi stok negatif fiktif.
                                </div>
                            </div>
                        </div>

                        <!-- Tab Filter Kategori Item Explanation -->
                        <div class="guide-box box-info" style="margin:14px 0;">
                            <i data-lucide="layers"></i>
                            <div>
                                <strong>Tab Filter Kategori Cepat (4 Kategori dengan Counter Live):</strong><br>
                                Tabel stok dilengkapi tab filter visual untuk menyortir jenis item secara instan:
                                <ul style="padding-left:18px; margin:6px 0 0 0; font-size:12px; line-height:1.6;">
                                    <li><strong>Semua Stok (Burgundy):</strong> Menampilkan seluruh SKU tanpa batasan kategori.</li>
                                    <li><strong>Barang Jadi (Biru):</strong> Hanya menampilkan snack kemasan siap jual (Keripik Singkong, Basreng, Stik Bawang, dll).</li>
                                    <li><strong>Bahan Mentah (Amber):</strong> Menampilkan komoditas mentah produksi (Singkong mentah, Minyak goreng curah/kemasan, Bumbu balado, Cabai bubuk).</li>
                                    <li><strong>Bahan Kemas (Teal):</strong> Menampilkan material pengemasan (Plastik standing pouch, Dus karton luar, Lakban segel, Stiker label barcode).</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Live Search & Keyboard Shortcut -->
                        <div style="background:var(--guide-bg);border:1px solid var(--guide-border);border-radius:12px;padding:12px 14px;margin-top:12px;">
                            <div style="font-weight:800;font-size:12px;color:var(--guide-text-primary);margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                                <i data-lucide="zap" style="width:14px;height:14px;color:#f59e0b;"></i>
                                <span>Pencarian Cepat &amp; Shortcut Keyboard:</span>
                            </div>
                            <ul style="font-size:11.5px;color:var(--guide-text-secondary);line-height:1.6;padding-left:18px;margin:0;">
                                <li>• <strong>Shortcut Tombol <code>/</code>:</strong> Tekan tombol garis miring <code>/</code> pada keyboard komputer dari posisi mana pun untuk langsung memfokuskan kursor ke kolom pencarian tanpa perlu menyentuh mouse.</li>
                                <li>• <strong>Filter Status Ketersediaan:</strong> Dropdown filter untuk menyaring item berdasarkan status <em>Stok Aman</em>, <em>Stok Menipis</em>, atau <em>Stok Kosong</em>.</li>
                                <li>• <strong>Tombol Reset Filter:</strong> Mengembalikan seluruh tab, status ketersediaan, grup kemasan, dan kata kunci pencarian ke tampilan default dalam 1 kali klik.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- SUB-BAB 8.2: KARTU STOK & PENYESUAIAN CEPAT -->
                    <div class="sub-chapter-block">
                        <h4 class="sub-chapter-title">
                            <i data-lucide="activity"></i>
                            <span>8.2 Audit Kartu Stok &amp; Penyesuaian Cepat (Quick Stock Adjustment)</span>
                        </h4>
                        <p class="step-desc">
                            Untuk menjaga integritas dan ketelusuran pergerakan persediaan, setiap pergeseran stok di sistem KEREN ONE dicatat secara permanen di buku besar mutasi inventori:
                        </p>

                        <div class="step-timeline">
                            <div class="step-item">
                                <div class="step-circle">1</div>
                                <div class="step-content">
                                    <h4 class="step-title">Intip Riwayat Kartu Stok Mini (Ikon Activity)</h4>
                                    <p class="step-desc">
                                        Klik tombol ikon grafik mutasi pada kolom aksi baris produk untuk membuka modal <strong>Kartu Stok &amp; Riwayat Mutasi Terakhir</strong>. Modal ini merinci kronologi mutasi: waktu kejadian, nomor referensi dokumen, tipe pergerakan (Penerimaan PO Pembelian, Penjualan Kasir POS, Pengiriman Surat Jalan B2B, Penyesuaian Opname, Retur), jumlah penambahan (+) atau pengurangan (-), saldo stok akhir sesudah mutasi, serta nama staf pengguna yang mengeksekusi transaksi.
                                    </p>
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="step-circle">2</div>
                                <div class="step-content">
                                    <h4 class="step-title">Penyesuaian Cepat &amp; Pencatatan Kerusakan (Quick Adjustment &amp; Waste)</h4>
                                    <p class="step-desc">
                                        Untuk pergerakan stok insidental pada baris produk di luar audit massal, sistem menyediakan dua aksi independen:
                                    </p>
                                    <ul style="padding-left:18px; margin:6px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.6;">
                                        <li><strong>Tombol [Opname] (Penyesuaian Fisik):</strong> Membuka 3 mode aksi (<em>Opname Fisik</em> untuk memasukkan hasil hitung riil rak, <em>Item Masuk</em> untuk koreksi penambahan, dan <em>Item Keluar</em> untuk koreksi pengurangan administratif).</li>
                                        <li><strong>Tombol Merah [Waste] (Barang Rusak &amp; Sampel):</strong> Aksi khusus untuk memotong stok barang yang fisik nyatanya rusak, remuk, kemasan bocor, kadaluarsa, atau diambil untuk sampel promosi/uji rasa.</li>
                                    </ul>
                                    <p class="step-desc">
                                        Setiap aksi wajib menyertakan <em>Alasan / Keterangan</em> yang jelas sebagai jejak rekam audit digital bagi manajemen dan tim akuntansi.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SUB-BAB 8.3: PANDUAN KRUSIAL MEMBEDAKAN WASTE VS OPNAME ITEM KELUAR -->
                    <div class="sub-chapter-block">
                        <h4 class="sub-chapter-title">
                            <i data-lucide="scale"></i>
                            <span>8.3 Panduan Krusial: Membedakan Barang Rusak (Waste) vs Opname Item Keluar (Dampak Akuntansi &amp; Laporan)</span>
                        </h4>
                        <p class="step-desc">
                            Banyak staf operasional sering bertanya: <em>"Sama-sama mengurangi stok fisik di gudang, mengapa ada tombol Waste dan tombol Opname (Item Keluar)?"</em>. Pemilihan fitur yang tepat adalah <strong>kunci utama keakuratan laporan keuangan, perhitungan HPP, dan evaluasi efisiensi operasional pabrik</strong>:
                        </p>

                        <!-- Tabel Perbandingan Komprehensif -->
                        <div class="guide-table-wrapper custom-scrollbar">
                            <table class="guide-table">
                                <thead>
                                    <tr>
                                        <th style="width:160px; min-width:140px;">Aspek Pembeda</th>
                                        <th style="width:50%;">
                                            <span class="guide-th-pill pill-waste">
                                                <i data-lucide="trash-2"></i>
                                                <span>Catat Barang Rusak (Waste)</span>
                                            </span>
                                        </th>
                                        <th style="width:50%;">
                                            <span class="guide-th-pill pill-opname">
                                                <i data-lucide="clipboard-list"></i>
                                                <span>Opname: Item Keluar / Koreksi</span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Kondisi Fisik Barang</td>
                                        <td>
                                            <div style="font-weight:700; color:#e11d48; margin-bottom:3px;">Wujud fisik barang nyata ada</div>
                                            <span>Terbukti rusak, bocor, remuk, kadaluarsa, atau dicicipi untuk sampel uji rasa mitra.</span>
                                        </td>
                                        <td>
                                            <div style="font-weight:700; color:#0284c7; margin-bottom:3px;">Fisik barang tidak ada / selisih hitung</div>
                                            <span>Barang hilang atau kurang di rak audit gudang, tidak ada bangkai atau bukti fisik kerusakan.</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Kapan Digunakan?</td>
                                        <td>
                                            <div style="font-weight:700; color:var(--guide-text-primary); margin-bottom:2px;">Insidental harian saat kejadian</div>
                                            <span>Plastik sobek terkena cutter saat packing, dus jatuh, tanggal kadaluarsa habis, atau sampel buyer.</span>
                                        </td>
                                        <td>
                                            <div style="font-weight:700; color:var(--guide-text-primary); margin-bottom:2px;">Saat Stok Opname Berkala</div>
                                            <span>Pelaksanaan audit fisik mingguan/bulanan atau koreksi administratif non-waste.</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Input yang Diminta</td>
                                        <td>
                                            <div style="font-weight:700; color:var(--guide-text-primary); margin-bottom:2px;">Kategori Kerusakan + Kronologi</div>
                                            <span>Wajib memilih Kategori Kerusakan (Kemasan Rusak, Remuk, Expired, Sampel) &amp; kronologi kejadian.</span>
                                        </td>
                                        <td>
                                            <div style="font-weight:700; color:var(--guide-text-primary); margin-bottom:2px;">Alasan Audit / Koreksi</div>
                                            <span>Murni catatan audit fisik (contoh: <em>"Selisih hitung rak tengah"</em> atau <em>"Koreksi salah catat nota"</em>).</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Kartu Stok (Mutasi)</td>
                                        <td>
                                            <code style="font-size:11px;">item_keluar_waste</code>
                                            <div style="font-size:11px; margin-top:3px; opacity:0.85;">Referensi: <code>waste_manual</code></div>
                                        </td>
                                        <td>
                                            <code style="font-size:11px;">penyesuaian_opname_kurang</code>
                                            <div style="font-size:11px; margin-top:3px; opacity:0.85;">Referensi: <code>penyesuaian_stok</code></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Pos Akuntansi &amp; Laba Rugi</td>
                                        <td>
                                            <div style="font-weight:700; color:var(--guide-text-primary); margin-bottom:2px;">Beban Kerusakan Barang (Spoilage)</div>
                                            <span>Atau <strong>Beban Promosi</strong> (jika sampel tester). Mengurangi laba operasional sebagai beban terukur pabrik.</span>
                                        </td>
                                        <td>
                                            <div style="font-weight:700; color:var(--guide-text-primary); margin-bottom:2px;">Selisih Persediaan (Inventory Shrinkage)</div>
                                            <span>Dibukukan ke akun selisih audit persediaan gudang untuk ditelusuri riwayatnya.</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Analitik Manajemen</td>
                                        <td>
                                            Mengevaluasi kualitas kemasan supplier karton/plastik, kehati-hatian staf packing, dan efektivitas mesin segel.
                                        </td>
                                        <td>
                                            Menelusuri potensi kebocoran stok, kelalaian pencatatan penjualan kasir, atau selisih administrasi gudang.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Contoh Kasus Nyata di Lapangan Kerja -->
                        <div class="space-y-3" style="margin-top:16px;">
                            <div class="guide-box box-danger">
                                <i data-lucide="alert-triangle"></i>
                                <div style="flex:1; min-width:0;">
                                    <strong style="color:currentColor; font-size:13px;">Contoh Kasus 1 (Gunakan Tombol Waste):</strong>
                                    <p style="margin:4px 0 6px 0; font-size:12px; line-height:1.5;">
                                        Staf gudang sedang merapikan karton <em>Berondong Beras 2 Susun</em>. Ditemukan 2 bungkus plastik robek tersayat cutter dan 1 bungkus remuk terinjak saat pemindahan dus. Selain itu, staf mengambil 2 bungkus untuk sampel uji rasa pembeli supermarket mitra.
                                    </p>
                                    <div style="background:var(--guide-card-bg); border-radius:10px; padding:10px 14px; font-size:12px; border:1px solid var(--guide-border); margin-top:8px;">
                                        👉 <strong>Tindakan:</strong> Klik tombol merah <strong>[Waste]</strong> &rarr; Masukkan kuantitas (misal 3) &rarr; Pilih kategori <em>Kemasan Rusak / Gagal Segel</em> &rarr; Kronologi: <em>"Sobek cutter dan remuk saat unboxing dus"</em>.<br>
                                        📊 <strong>Hasil Laporan:</strong> Stok berkurang 3 pcs, dan tercatat resmi sebagai <strong>Beban Kerusakan Barang</strong>, sehingga manajemen dapat mengevaluasi SOP kerja atau mengklaim ganti rugi vendor.
                                    </div>
                                </div>
                            </div>

                            <div class="guide-box box-info">
                                <i data-lucide="clipboard-check"></i>
                                <div style="flex:1; min-width:0;">
                                    <strong style="color:currentColor; font-size:13px;">Contoh Kasus 2 (Gunakan Tombol Opname / Bulk Opname):</strong>
                                    <p style="margin:4px 0 6px 0; font-size:12px; line-height:1.5;">
                                        Pada jadwal audit opname fisik akhir bulan, sistem mencatat stok <em>Stik Bawang Gurih</em> ada 20 pcs. Setelah seluruh rak gudang dihitung teliti, barang yang ada hanya 18 pcs (kurang 2 pcs). Di area gudang dan tempat sampah <strong>tidak ada bungkus rusak ataupun sisa remukan produk</strong>. Barangnya murni selisih hitung fisik.
                                    </p>
                                    <div style="background:var(--guide-card-bg); border-radius:10px; padding:10px 14px; font-size:12px; border:1px solid var(--guide-border); margin-top:8px;">
                                        👉 <strong>Tindakan:</strong> Klik tombol <strong>[Opname]</strong> (masukkan hasil hitung fisik <code>18</code> atau pilih mode <em>Item Keluar</em> kuantitas <code>2</code>) atau gunakan modul <strong>Bulk Opname</strong> &rarr; Alasan: <em>"Selisih hitung fisik opname rak tengah"</em>.<br>
                                        📊 <strong>Hasil Laporan:</strong> Stok sistem diselaraskan menjadi 18 pcs agar kasir tidak menjual barang fiktif, dan selisih -2 pcs masuk pos <strong>Selisih Persediaan (Inventory Shrinkage)</strong> untuk ditelusuri riwayat nota penjualannya.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Golden Rule Box -->
                        <div style="background:var(--guide-accent-soft); border:1px solid var(--guide-accent); border-radius:12px; padding:12px 16px; margin-top:14px;">
                            <div style="font-weight:800; font-size:12.5px; color:var(--guide-accent); margin-bottom:4px; display:flex; align-items:center; gap:6px;">
                                <i data-lucide="check-circle-2" style="width:16px; height:16px;"></i>
                                <span>Aturan Emas Operasional (Golden Rule):</span>
                            </div>
                            <div style="font-size:12px; color:var(--guide-text-primary); line-height:1.5;">
                                • <strong>Ada wujud fisik bangkai barangnya yang rusak / terbuang / dicicipi sampel?</strong> &rarr; Selalu gunakan <strong>[Waste]</strong>.<br>
                                • <strong>Barangnya tidak ada di rak / selisih hitung saat audit berkala?</strong> &rarr; Selalu gunakan <strong>[Opname Fisik]</strong> atau <strong>[Bulk Opname]</strong>.
                            </div>
                        </div>
                    </div>

                    <!-- SUB-BAB 8.4: ALUR KERJA BULK OPNAME GUDANG -->
                    <div class="sub-chapter-block">
                        <h4 class="sub-chapter-title">
                            <i data-lucide="clipboard-check"></i>
                            <span>8.4 Modul Bulk Opname Stok Gudang (Audit Massal Berkecepatan Tinggi)</span>
                        </h4>
                        <p class="step-desc">
                            Untuk audit stok periodik (opname akhir bulan atau audit fisik serentak seluruh gudang), gunakan fitur <strong>Bulk Opname Gudang</strong> (diakses via tombol hijau di pojok kanan atas halaman Inventaris atau URL <code>/inventory/bulk-opname</code>):
                        </p>

                        <div class="step-timeline">
                            <div class="step-item">
                                <div class="step-circle">1</div>
                                <div class="step-content">
                                    <h4 class="step-title">Pengisian Metadata Dokumen Sesi Opname</h4>
                                    <p class="step-desc">
                                        Tentukan <strong>Tanggal Opname</strong> (tanggal fisik cut-off penghitungan) dan tuliskan <strong>Keterangan / Catatan Sesi Opname</strong> (misal: <em>"Opname Fisik Tutup Buku Akhir Bulan Gudang Pusat"</em>). Metadata ini akan tercatat resmi pada nomor dokumen audit <code>OPN-YYYYMMDD-XXXX</code>.
                                    </p>
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="step-circle">2</div>
                                <div class="step-content">
                                    <h4 class="step-title">Tab Kategori Fleksibel: Pisahkan Fokus Area Hitung</h4>
                                    <p class="step-desc">
                                        Manfaatkan tab kategori di atas tabel (<em>Semua Item</em>, <em>Barang Jadi</em>, <em>Bahan Mentah</em>, dan <em>Bahan Kemas</em>). Petugas dapat menyelesaikan penghitungan bahan mentah terlebih dahulu di area gudang bahan basah, lalu berpindah ke tab barang jadi tanpa khawatir angka yang sudah diinput hilang atau ter-reset.
                                    </p>
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="step-circle">3</div>
                                <div class="step-content">
                                    <h4 class="step-title">Mesin Input Dua-Arah Cepat (Two-Way Binding @ 60 FPS)</h4>
                                    <p class="step-desc">
                                        Sistem menyediakan dua metode fleksibel untuk memasukkan data yang saling terkalkulasi otomatis secara seketika (*zero-lag*):
                                    </p>
                                    <ul style="padding-left:18px; margin:6px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.6;">
                                        <li><strong>Metode A (Input Stok Fisik Realita):</strong> Masukkan total angka riil hasil hitung fisik di rak gudang. Sistem secara instan menghitung selisihnya terhadap stok sistem dan mengisi kolom penyesuaian (warna hijau untuk stok masuk, warna merah untuk stok keluar).</li>
                                        <li><strong>Metode B (Input Penyesuaian Langsung +/-):</strong> Jika petugas sudah mengetahui jumlah selisihnya, cukup ketik di kolom Penyesuaian (misal <code>+10</code> atau <code>-5</code>). Sistem otomatis memperbarui kolom Stok Fisik Realita yang bersesuaian.</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="step-circle">4</div>
                                <div class="step-content">
                                    <h4 class="step-title">Aturan Ketat Validasi Anti-Minus (Zero Negative Stock Safety)</h4>
                                    <p class="step-desc">
                                        Untuk mencegah data persediaan menjadi tidak masuk akal atau minus di buku besar:
                                    </p>
                                    <ul style="padding-left:18px; margin:6px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.6;">
                                        <li>Kolom <em>Stok Fisik Realita</em> hanya menerima angka positif &ge; 0 (tanda minus <code>-</code> otomatis diblokir keyboard).</li>
                                        <li>Kolom <em>Penyesuaian (+/-)</em> otomatis membatasi nilai pengurangan maksimal sebesar sisa stok sistem yang ada. Contoh: jika stok sistem tercatat 15 pcs, pengurangan maksimal adalah <code>-15</code>. Pengurangan lebih dari itu otomatis dibatasi dan sistem menampilkan pop-up toast peringatan bahwa stok fisik tidak boleh minus.</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="step-circle">5</div>
                                <div class="step-content">
                                    <h4 class="step-title">Review Ringkasan &amp; Simpan Dokumen</h4>
                                    <p class="step-desc">
                                        Setelah seluruh fisik terdata, klik tombol <strong>"Review &amp; Simpan"</strong>. Sistem menampilkan modal pop-up konfirmasi yang menyajikan perbandingan detail stok awal, penyesuaian (+/-), dan stok akhir untuk seluruh item yang diubah. Klik <strong>"Simpan Permanen Opname"</strong> untuk membukukan mutasi stok ke sistem secara atomik dan aman.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SUB-BAB 8.5: KEAMANAN DATA & SHORTCUT PRODUKTIVITAS -->
                    <div class="sub-chapter-block">
                        <h4 class="sub-chapter-title">
                            <i data-lucide="shield-check"></i>
                            <span>8.5 Keamanan Data Input (Anti-Hilang Data) &amp; Shortcut Staf Gudang</span>
                        </h4>

                        <div class="feature-cards-grid">
                            <div class="feature-card">
                                <div class="feature-card-header">
                                    <div class="feature-card-icon" style="background:rgba(16,185,129,0.12);color:#10b981;">
                                        <i data-lucide="save"></i>
                                    </div>
                                    <span>Auto-Save Draf ke Perangkat</span>
                                </div>
                                <div class="feature-card-body">
                                    Setiap angka yang Anda ketik otomatis tersimpan di memori browser lokal (*Local Storage*). Jika koneksi internet terputus, laptop kehabisan baterai, atau halaman ter-refresh secara mendadak, draf data Anda 100% aman dan akan langsung dipulihkan secara otomatis saat membuka kembali halaman.
                                </div>
                            </div>

                            <div class="feature-card">
                                <div class="feature-card-header">
                                    <div class="feature-card-icon" style="background:rgba(37,99,235,0.12);color:#2563eb;">
                                        <i data-lucide="check-square"></i>
                                    </div>
                                    <span>Preservasi Data saat Filter &amp; Paging</span>
                                </div>
                                <div class="feature-card-body">
                                    Berpindah tab kategori, mengetik pencarian nama produk, mengganti filter status periksa, atau berpindah halaman pagination <strong>tidak akan menghapus atau me-reset</strong> angka yang telah Anda ketik sebelumnya.
                                </div>
                            </div>

                            <div class="feature-card">
                                <div class="feature-card-header">
                                    <div class="feature-card-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b;">
                                        <i data-lucide="alert-circle"></i>
                                    </div>
                                    <span>Konfirmasi Navigasi Aman (AppConfirm)</span>
                                </div>
                                <div class="feature-card-body">
                                    Jika Anda mengklik menu lain di sidebar atau tombol navigasi keluar saat masih ada perubahan opname yang belum disimpan, sistem memunculkan pop-up dialog peringatan yang elegan untuk mencegah Anda keluar secara tidak sengaja.
                                </div>
                            </div>
                        </div>

                        <!-- Warehouse Staff Productivity Tips -->
                        <div class="guide-box box-success" style="margin-top:14px;">
                            <i data-lucide="sparkles"></i>
                            <div>
                                <strong>Tips Kerja Cepat dengan Keyboard (10-Key Numpad &amp; Scanner):</strong><br>
                                Staf gudang yang menggunakan keyboard numerik atau barcode scanner dapat menginput dengan sangat cepat tanpa perlu menyentuh mouse:
                                <ul style="padding-left:18px; margin:6px 0 0 0; font-size:12px; line-height:1.6;">
                                    <li>Tekan tombol <code>/</code> untuk langsung mencari produk atau barcode.</li>
                                    <li>Ketik angka stok fisik pada baris produk yang sesuai.</li>
                                    <li>Tekan tombol <code>Enter</code> atau <code>Panah Bawah (&darr;)</code> pada keyboard untuk langsung loncat ke kolom input baris berikutnya secara otomatis!</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 9: LOGISTIK & PENGIRIMAN                              -->
                <!-- ========================================================= -->
                <section id="bab-8-logistik-pengiriman" class="guide-chapter theme-cyan">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="truck"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 9</span>
                            <h3 class="chapter-title">Operasional Logistik, Armada Driver &amp; Surat Jalan</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Driver</span>
                                <span class="role-pill">Koordinator Logistik</span>
                                <span class="role-pill">Admin Kantor</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Pengelolaan rute pengiriman pesanan dan pergerakan armada harian di menu <strong>Pengiriman</strong> (Sidebar: <em>Delivery &rarr; Pengiriman</em>):
                    </p>

                    <div class="step-timeline">
                        <div class="step-item">
                            <div class="step-circle">1</div>
                            <div class="step-content">
                                <h4 class="step-title">Penerbitan Surat Jalan Resmi</h4>
                                <p class="step-desc">
                                    Setelah pesanan pelanggan B2B selesai disiapkan oleh gudang, sistem menerbitkan Surat Jalan resmi berformat <code>SJ-YYYYMMDD-XXXX</code> yang memuat daftar nama toko, alamat tujuan, nomor telepon/WhatsApp, rincian barang muatan, dan nama driver yang bertugas.
                                </p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <div class="step-content">
                                <h4 class="step-title">Tampilan Mobile Rute Harian Driver di HP</h4>
                                <p class="step-desc">
                                    Driver cukup membuka menu <em>Pengiriman Driver</em> di browser HP. Seluruh toko yang harus dikunjungi hari ini tersusun rapi berdasarkan rute wilayah (<code>RTE-</code>). Terdapat tombol praktis untuk langsung menghubungi WhatsApp pemilik toko atau membuka panduan navigasi peta.
                                </p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-content">
                                <h4 class="step-title">Pembaruan Status Pengiriman Real-Time</h4>
                                <ul style="padding-left:18px; margin:4px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.6;">
                                    <li><strong>Siap Kirim:</strong> Barang sudah selesai dimuat ke dalam armada kendaraan.</li>
                                    <li><strong>Sedang Dikirim:</strong> Driver telah berangkat dan sedang dalam perjalanan rute.</li>
                                    <li><strong>Selesai Terkirim:</strong> Barang telah diterima oleh pemilik toko dengan bukti tanda tangan fisik pada lembar surat jalan.</li>
                                    <li><strong>Gagal Kirim / Reschedule:</strong> Digunakan jika toko mitra tutup atau jalan terhalang, disertai keterangan alasan yang jelas agar admin kantor dapat menjadwalkan ulang.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 10: KEUANGAN, KAS & REKENING ESCROW                   -->
                <!-- ========================================================= -->
                <section id="bab-9-keuangan-kas" class="guide-chapter theme-green">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="wallet"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 10</span>
                            <h3 class="chapter-title">Tata Kelola Kas Tertutup &amp; Rekening Kas Tabungan (Escrow)</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Kasir Kantor</span>
                                <span class="role-pill">Admin Keuangan</span>
                                <span class="role-pill">Owner</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Sistem keuangan Keren One menerapkan standar <strong>Arsitektur Kas Tertutup (Closed-Loop Cashflow)</strong>. Setiap aliran dana masuk dan keluar wajib terikat pada akun kas penampung resmi agar pembukuan tidak bocor dan dapat diaudit secara akurat:
                    </p>

                    <div class="step-timeline">
                        <div class="step-item">
                            <div class="step-circle">1</div>
                            <div class="step-content">
                                <h4 class="step-title">Struktur Akun Kas Perusahaan</h4>
                                <p class="step-desc">Dikelola melalui menu <strong>Akun Kas &amp; Bank</strong> (Sidebar: <em>Keuangan &amp; Kas &rarr; Akun Kas</em>):</p>
                                <ul style="padding-left:18px; margin:4px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.6;">
                                    <li><strong>Kasir Utama / Kas Toko:</strong> Kas operasional cair untuk transaksi penjualan langsung dan kebutuhan harian outlet.</li>
                                    <li><strong>Brankas Kantor:</strong> Tempat penyimpanan uang tunai cadangan kantor yang disimpan di brankas utama.</li>
                                    <li><strong>Rekening Bank (BCA, Mandiri, BRI, dll):</strong> Rekening perbankan resmi perusahaan untuk transfer masuk pelanggan dan pembayaran ke supplier.</li>
                                    <li><strong>Kas Driver (Pegawai):</strong> Akun kas sementara yang dipegang driver saat keliling di jalan untuk menampung uang tunai hasil setoran rolling nota toko.</li>
                                    <li><strong>Akun Kas Tabungan Karyawan (Rekening Terkunci / Escrow):</strong> Akun kas khusus yang difungsikan semata-mata untuk menyimpan dana tabungan milik seluruh karyawan.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <div class="step-content">
                                <h4 class="step-title">Proteksi Ketat Akun Kas Tabungan Karyawan (Rekening Terkunci)</h4>
                                <p class="step-desc">
                                    Demi menjamin keamanan dana tabungan karyawan agar tidak terpakai atau tercampur dengan likuiditas operasional bisnis, sistem memberikan proteksi otomatis:
                                </p>
                                <div class="guide-box box-warning" style="margin:8px 0;">
                                    <i data-lucide="lock"></i>
                                    <div>
                                        <strong>4 Proteksi Mutlak Rekening Kas Tabungan:</strong><br>
                                        • <strong>Dilarang untuk Belanja Bahan / PO:</strong> Akun kas tabungan diblokir dari pilihan metode bayar pembelian supplier.<br>
                                        • <strong>Dilarang untuk Biaya Kantor:</strong> Tidak dapat digunakan untuk membayar pengeluaran operasional harian kantor.<br>
                                        • <strong>Dilarang untuk Transaksi Kasir POS:</strong> Kasir toko tidak dapat memilih kas tabungan saat transaksi penjualan.<br>
                                        • <strong>Anti-Nonaktif Saldo Positif:</strong> Akun kas tabungan tidak dapat dinonaktifkan atau dihapus jika masih terdapat saldo simpanan karyawan di dalamnya.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-content">
                                <h4 class="step-title">Pencatatan Biaya Operasional &amp; Pemasukan Lain</h4>
                                <p class="step-desc">
                                    Pada menu <strong>Kas Masuk &amp; Keluar</strong>, admin dapat mencatat pengeluaran biaya perusahaan (bensin armada, listrik pabrik, makan lembur, perawatan kendaraan) dengan memilih kategori biaya yang tepat dan menentukan akun kas operasional sumber pembayaran.
                                </p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">4</div>
                            <div class="step-content">
                                <h4 class="step-title">SOP Rekonsiliasi &amp; Serah Terima Kas Driver di Sore Hari</h4>
                                <p class="step-desc">
                                    Saat armada driver kembali ke kantor di sore hari:
                                </p>
                                <div class="guide-box box-success" style="margin:8px 0;">
                                    <i data-lucide="check-circle-2"></i>
                                    <div>
                                        1. Driver menyerahkan fisik uang tunai hasil rolling nota ke Kasir Kantor.<br>
                                        2. Kasir membuka menu <strong>Transfer Antar Kas</strong> (Sidebar: <em>Keuangan &amp; Kas &rarr; Kas Masuk &amp; Keluar &rarr; Tab Transfer</em>):<br>
                                        &bull; <em>Dari Akun:</em> <strong>Kas Driver (Pegawai)</strong><br>
                                        &bull; <em>Ke Akun:</em> <strong>Kasir Utama / Brankas Kantor</strong><br>
                                        3. Setelah disimpan, tanggung jawab saldo kas driver otomatis kembali Rp 0 dan kas kantor bertambah resmi.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 11: MANAJEMEN SDM, KASBON & PENGGAJIAN               -->
                <!-- ========================================================= -->
                <section id="bab-10-hr-penggajian" class="guide-chapter theme-indigo">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="users"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 11</span>
                            <h3 class="chapter-title">Manajemen SDM, Kasbon &amp; Penggajian Terpadu (HR &amp; Payroll)</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Mandor Produksi</span>
                                <span class="role-pill">Admin HR / Personalia</span>
                                <span class="role-pill">Finance</span>
                                <span class="role-pill">Owner</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Modul SDM Keren One mengintegrasikan absensi kehadiran, hasil produksi borongan, kasbon, tabungan, hingga penerbitan slip gaji resmi secara otomatis:
                    </p>

                    <div class="step-timeline">
                        <div class="step-item" id="panduan-absensi" style="scroll-margin-top: 85px;">
                            <div class="step-circle">1</div>
                            <div class="step-content">
                                <h4 class="step-title">11.1 Absensi Kehadiran, Pencairan Kas Harian &amp; Proteksi Saldo</h4>
                                <p class="step-desc">
                                    Dikelola di menu <strong>Absensi Karyawan</strong> (<span class="guide-nav-step"><i data-lucide="users"></i> HR &amp; Personalia <i data-lucide="chevron-right"></i> Absensi</span>). Modul ini berfungsi mencatat presensi harian seluruh staf, menyinkronkan penarikan uang kehadiran &amp; lembur tunai dari kas fisik kantor, serta mengunci proteksi saldo kas agar tidak terjadi kebocoran atau selisih pembukuan:
                                </p>

                                <!-- A. Dual-Sistem Borongan vs Bulanan -->
                                <div style="margin: 12px 0;">
                                    <div style="font-weight:700; font-size:12.5px; color:var(--guide-text-primary); margin-bottom:4px; display:flex; align-items:center; gap:6px;">
                                        <i data-lucide="layers" style="width:14px; height:14px; color:var(--guide-accent);"></i>
                                        <span>A. Klasifikasi Karyawan: Tenaga Borongan vs Tenaga Bulanan</span>
                                    </div>
                                    <ul style="padding-left:18px; margin:4px 0 8px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.65;">
                                        <li><strong>Karyawan Bulanan:</strong> Memiliki gaji pokok bulanan dan tunjangan tetap. Status kehadiran (<code>Hadir</code>, <code>Sakit</code>, <code>Izin</code>, <code>Alpa</code>) menjadi dasar akumulasi rekapitulasi bulanan untuk perhitungan tunjangan kehadiran atau pemotongan keterlambatan/mangkir pada slip gaji resmi.</li>
                                        <li><strong>Karyawan Borongan:</strong> Hak upah pokok dihitung murni berdasarkan volume kemasan/output produk yang dicatat pada menu <em>Produksi Borongan</em>. Namun, tenaga borongan tetap <strong>wajib dipresensi setiap hari</strong> untuk mencatat uang kehadiran harian, lembur tunai, serta evaluasi kedisiplinan kerja harian.</li>
                                    </ul>
                                </div>

                                <!-- B. Mekanisme Pencairan Uang Kas Harian -->
                                <div style="margin: 12px 0;">
                                    <div style="font-weight:700; font-size:12.5px; color:var(--guide-text-primary); margin-bottom:4px; display:flex; align-items:center; gap:6px;">
                                        <i data-lucide="banknote" style="width:14px; height:14px; color:var(--guide-accent);"></i>
                                        <span>B. Mekanisme Pengambilan Uang Harian &amp; Lembur Tunai</span>
                                    </div>
                                    <p class="step-desc" style="margin-bottom:6px;">
                                        Karyawan berhak mengambil uang kehadiran harian dan uang lembur secara tunai langsung di meja kasir presensi:
                                    </p>
                                    <ul style="padding-left:18px; margin:4px 0 8px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.65;">
                                        <li><strong>Centang "Ambil Uang":</strong> Hanya aktif jika status karyawan <code>Hadir</code>. Nominal uang harian ditarik otomatis dari master data karyawan. Jika status diubah ke Sakit/Izin/Alpa, opsi ini otomatis terkunci nonaktif.</li>
                                        <li><strong>Uang Lembur (Jam &amp; Nominal):</strong> Mandor dapat menginput durasi lembur (jam) beserta nominal kompensasi lembur harian yang disetujui untuk dibayarkan hari itu.</li>
                                        <li><strong>Total Tarik Kas = Uang Kehadiran + Uang Lembur:</strong> Sistem otomatis menjumlahkan seluruh dana tunai yang harus diserahkan fisik oleh kasir kepada masing-masing karyawan hari itu.</li>
                                    </ul>
                                </div>

                                <!-- C. Alur Pop-up Kas & Direct-Save Cerdas -->
                                <div style="margin: 12px 0;">
                                    <div style="font-weight:700; font-size:12.5px; color:var(--guide-text-primary); margin-bottom:4px; display:flex; align-items:center; gap:6px;">
                                        <i data-lucide="arrow-right-circle" style="width:14px; height:14px; color:var(--guide-accent);"></i>
                                        <span>C. Alur Konfirmasi Kas &amp; Direct-Save Cerdas</span>
                                    </div>
                                    <p class="step-desc" style="margin-bottom:6px;">
                                        Saat tombol <strong>Simpan Presensi</strong> ditekan, sistem menjalankan verifikasi pintar dua jalur:
                                    </p>
                                    <div class="guide-box box-info" style="margin:8px 0;">
                                        <i data-lucide="check-circle-2"></i>
                                        <div>
                                            <strong>1. Direct-Save Otomatis (Tanpa Pop-up):</strong><br>
                                            Jika <strong>tidak ada penarikan uang kas baru</strong> (total penarikan = Rp 0, atau saat mengedit presensi karyawan lain yang tidak mencairkan uang kas baru), sistem <strong>langsung menyimpan data</strong> secara instan ke database tanpa memunculkan modal pop-up konfirmasi kas.
                                        </div>
                                    </div>
                                    <div class="guide-box box-warning" style="margin:8px 0;">
                                        <i data-lucide="wallet"></i>
                                        <div>
                                            <strong>2. Modal Pop-up Konfirmasi Kas Wajib:</strong><br>
                                            Hanya muncul jika terdapat <strong>penarikan uang kas tunai baru (Delta &gt; Rp 0)</strong>. Admin kasir wajib memilih <strong>Akun Kas Aktif</strong> (misal Kasir Utama atau Brankas) dengan saldo yang mencukupi. <em>Opsi pencatatan tanpa kas telah ditiadakan 100%</em> demi mencegah mutasi kas gantung atau tidak seimbang.
                                        </div>
                                    </div>
                                </div>

                                <!-- D. 4 Lapis Proteksi Anti-Kebocoran Kas -->
                                <div style="margin: 12px 0;">
                                    <div style="font-weight:700; font-size:12.5px; color:var(--guide-text-primary); margin-bottom:4px; display:flex; align-items:center; gap:6px;">
                                        <i data-lucide="shield-check" style="width:14px; height:14px; color:var(--guide-accent);"></i>
                                        <span>D. 4 Lapis Proteksi Anti-Kebocoran &amp; Selisih Saldo Kas</span>
                                    </div>
                                    <div class="guide-box box-success" style="margin:8px 0;">
                                        <i data-lucide="shield"></i>
                                        <div>
                                            <strong>1. Delta-Based Cash Verification:</strong> Mencegah pemotongan kas ganda saat merevisi lembar presensi. Jika mengedit absensi karyawan lain, sistem tidak akan memotong kas lagi ataupun menampilkan pop-up kas jika tidak ada uang tambahan yang ditarik.<br><br>
                                            <strong>2. Badge Status "Cair":</strong> Karyawan yang uang fisiknya telah diambil diberi penanda tegas <code>[Ambil Rp ... (Cair)]</code>, sehingga mandor dan kasir langsung mengetahui bahwa dana tersebut telah diserahkan fisik.<br><br>
                                            <strong>3. Konfirmasi Fisik Pembatalan / Revisi:</strong> Jika karyawan yang uangnya sudah berstatus <em>Cair</em> diubah statusnya menjadi Sakit/Izin/Alpa atau centang uangnya dilepas, sistem memicu konfirmasi dialog peringatan agar kasir <strong>wajib menarik kembali fisik uang tunai</strong> yang terlanjur diserahkan ke karyawan.<br><br>
                                            <strong>4. Banner Rekonsiliasi Real-Time:</strong> Panel ringkasan kas di bagian atas halaman absensi menampilkan akumulasi uang yang telah dicairkan pada tanggal tersebut untuk dicocokkan langsung dengan fisik uang di laci kasir.
                                        </div>
                                    </div>
                                </div>

                                <!-- E. Integrasi Slip Gaji Bulanan (Anti-Dobel Bayar) -->
                                <div style="margin: 12px 0;">
                                    <div style="font-weight:700; font-size:12.5px; color:var(--guide-text-primary); margin-bottom:4px; display:flex; align-items:center; gap:6px;">
                                        <i data-lucide="calculator" style="width:14px; height:14px; color:var(--guide-accent);"></i>
                                        <span>E. Integrasi Slip Gaji Bulanan (Anti-Dobel Bayar)</span>
                                    </div>
                                    <p class="step-desc">
                                        Seluruh penarikan uang kehadiran harian yang dicairkan otomatis tercatat sebagai mutasi kas keluar operasional dan terhubung ke buku besar penarikan karyawan. Pada saat <strong>Tutup Payroll Bulanan</strong> (Sidebar: <em>HR &amp; Personalia &rarr; Penggajian</em>):
                                    </p>
                                    <div class="formula-card">
                                        Take Home Pay (Gaji Bersih) = (Gaji Pokok + Total Upah Borongan + Total Tunjangan) - (Kasbon + Tabungan + Potongan Penarikan Uang Harian Presensi)
                                    </div>
                                    <p class="step-desc" style="font-size:11.5px; color:var(--guide-text-muted); margin-top:4px;">
                                        Dengan integrasi otomatis ini, uang kehadiran yang telah diambil tunai di hari kerja langsung memotong hak gaji bulanan sehingga perusahaan <strong>terlindungi 100% dari risiko dobel bayar</strong>.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <div class="step-content">
                                <h4 class="step-title">11.2 Pencatatan Produksi Harian Borongan</h4>
                                <p class="step-desc">
                                    Dikelola di menu <strong>Produksi Borongan</strong> (Sidebar: <em>HR &amp; Personalia &rarr; Produksi</em>). Mandor mencatat hasil kerja harian tenaga borongan dengan memilih nama karyawan, jenis produk/kemasan, dan jumlah unit yang berhasil diselesaikan hari itu. Sistem otomatis mengalikan unit tersebut dengan tarif upah kemasan (<em>Kelompok Upah Borongan</em>) sehingga total hak upah borongan terkumpul akurat.
                                </p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-content">
                                <h4 class="step-title">11.3 Kasbon Karyawan &amp; Cicilan Terjadwal</h4>
                                <p class="step-desc">
                                    Dikelola di menu <strong>Kasbon Karyawan</strong> (Sidebar: <em>HR &amp; Personalia &rarr; Kasbon</em>):
                                </p>
                                <ul style="padding-left:18px; margin:4px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.6;">
                                    <li><strong>Pengajuan Kasbon Baru:</strong> Pilih karyawan, nominal pinjaman, jumlah tenor cicilan, dan Akun Kas Sumber Pembayaran (misal Kasir Utama atau Brankas). Saldo kas yang dipilih akan otomatis berkurang sesuai nominal pinjaman.</li>
                                    <li><strong>Opsi Bypass Kas:</strong> Disediakan khusus jika pencatatan kasbon merupakan pemindahan sisa hutang lama karyawan sebelum sistem dipakai, sehingga tidak mengurangi fisik kas hari ini.</li>
                                    <li><strong>Pelunasan Cicilan Kasbon:</strong> Sistem otomatis memotong cicilan saat tutup penggajian (Payroll), atau karyawan dapat melunasi secara tunai langsung di menu kasbon yang otomatis menambah saldo akun kas perusahaan.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">4</div>
                            <div class="step-content">
                                <h4 class="step-title">11.4 Penarikan Gaji / Kasbon Harian</h4>
                                <p class="step-desc">
                                    Dikelola di menu <strong>Penarikan Gaji</strong> (Sidebar: <em>HR &amp; Personalia &rarr; Penarikan Gaji</em>). Fasilitas ini digunakan jika tenaga kerja harian atau borongan mengambil uang saku harian di tengah periode kerja. Pengambilan uang harian memotong kas operasional kantor yang dipilih dan otomatis menjadi komponen potongan pada slip gaji periode tersebut.
                                </p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">5</div>
                            <div class="step-content">
                                <h4 class="step-title">11.5 Tabungan Karyawan</h4>
                                <p class="step-desc">
                                    Dikelola di menu <strong>Tabungan Karyawan</strong> (Sidebar: <em>HR &amp; Personalia &rarr; Tabungan</em>):
                                </p>
                                <ul style="padding-left:18px; margin:4px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.6;">
                                    <li><strong>Setor Tabungan Sukarela:</strong> Karyawan menitipkan tabungan sukarela, uang fisik masuk ke Akun Kas Tabungan Escrow dan buku tabungan karyawan bertambah.</li>
                                    <li><strong>Penarikan Tabungan:</strong> Saat karyawan membutuhkan dana tabungannya, penarikan hanya dapat dicairkan dari Akun Kas Tabungan Escrow dan sistem menolak penarikan yang melebihi sisa saldo tabungan karyawan tersebut.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">6</div>
                            <div class="step-content">
                                <h4 class="step-title">11.6 Penggajian Otomatis (Payroll Engine) &amp; Approval Pembayaran</h4>
                                <p class="step-desc">
                                    Dikelola di menu <strong>Penggajian</strong> (Sidebar: <em>HR &amp; Personalia &rarr; Penggajian</em>):
                                </p>
                                <div class="formula-card">
                                    Gaji Bersih = (Gaji Pokok / Total Upah Borongan + Tunjangan/Uang Makan) - Potongan Kasbon - Potongan Ambil Harian - Setoran Tabungan Wajib - Potongan Absen
                                </div>
                                <div class="guide-box box-info" style="margin:8px 0;">
                                    <i data-lucide="calculator"></i>
                                    <div>
                                        <strong>SOP Persetujuan Penggajian (Approval Owner &amp; Finance):</strong><br>
                                        1. Buka menu <em>Penggajian &rarr; Buat Periode Gaji</em> (Pilih tipe Mingguan Borongan atau Bulanan Staff).<br>
                                        2. Sistem menghitung seluruh rekap absensi, hasil borongan, potongan kasbon, dan uang makan secara otomatis.<br>
                                        3. Saat Pimpinan/Finance menekan <strong>"Setujui &amp; Bayar Payroll"</strong>:<br>
                                        &bull; Admin memilih Akun Kas Operasional untuk pembayaran Gaji Bersih.<br>
                                        &bull; <strong>Otomatisasi Escrow:</strong> Sistem otomatis memotong kas operasional untuk gaji bersih, dan <em>secara otomatis mentransfer</em> dana potongan tabungan wajib karyawan dari Kas Operasional ke Akun Kas Tabungan Escrow!<br>
                                        4. Klik <strong>"Cetak Slip Gaji (PDF)"</strong> untuk membagikan bukti rincian gaji resmi berlogo perusahaan kepada masing-masing karyawan.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 12: SOLUSI MASALAH LAPANGAN & FAQ                     -->
                <!-- ========================================================= -->
                <section id="bab-11-faq-masalah" class="guide-chapter theme-orange">
                    <a id="bab-10-faq-masalah" href="#bab-10-faq-masalah" style="display:none;" aria-hidden="true"></a>
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="help-circle"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 12</span>
                            <h3 class="chapter-title">Penanganan Masalah Lapangan (Troubleshooting &amp; FAQ)</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Semua Staf</span>
                                <span class="role-pill">Admin</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="faq-list">
                        <div class="faq-card">
                            <div class="faq-header">
                                <div class="faq-badge-icon"><i data-lucide="store"></i></div>
                                <h4 class="faq-question">1. Bagaimana jika Toko Tutup saat Driver Datang Mengantar Barang?</h4>
                            </div>
                            <div class="faq-body">
                                <strong>Jangan submit form opname atau selesaikan surat jalan!</strong> Pada menu <strong>Pengiriman</strong> (Sidebar: <em>Delivery &rarr; Pengiriman</em>), pilih status <em>"Gagal Kirim / Reschedule"</em> dengan catatan <em>"Toko tutup"</em>. Saldo rak toko dan posisi barang di sistem tetap aman pada data terakhir tanpa ada tagihan palsu.
                            </div>
                        </div>

                        <div class="faq-card">
                            <div class="faq-header">
                                <div class="faq-badge-icon"><i data-lucide="plus-circle"></i></div>
                                <h4 class="faq-question">2. Toko Mitra Ingin Menambah Varian Rasa / Produk Baru di Rak?</h4>
                            </div>
                            <div class="faq-body">
                                Admin/Sales membuat pesanan PO Konsinyasi yang memuat produk baru tersebut melalui menu <strong>Konsinyasi</strong>. Saat form opname dibuka oleh driver/sales, sistem otomatis menggabungkan produk baru tersebut dengan <em>Stok Kirim Lalu = 0</em> dan <em>Tambah Baru = Jumlah PO</em>. Setelah disimpan, produk baru resmi tercatat di rak toko tersebut untuk kunjungan selanjutnya.
                            </div>
                        </div>

                        <div class="faq-card">
                            <div class="faq-header">
                                <div class="faq-badge-icon"><i data-lucide="alert-triangle"></i></div>
                                <h4 class="faq-question">3. Ada Barang Hilang di Rak Toko &amp; Pemilik Toko Menolak Bayar?</h4>
                            </div>
                            <div class="faq-body">
                                Sales/Driver memasukkan selisih di kolom <em>"Selisih Qty / Stok Hilang Pending"</em> (misal: <code>-2</code>). Sistem mencatatnya sebagai audit kehilangan barang tanpa membebankan tagihan pada nota hari itu demi menjaga hubungan baik dengan toko mitra.
                            </div>
                        </div>

                        <div class="faq-card">
                            <div class="faq-header">
                                <div class="faq-badge-icon"><i data-lucide="smartphone"></i></div>
                                <h4 class="faq-question">4. Bagaimana Cara Menginstal Aplikasi Keren One di Layar HP Android / iPhone?</h4>
                            </div>
                            <div class="faq-body">
                                Buka browser Chrome (Android) atau Safari (iPhone), akses alamat web ERP Keren One, lalu buka menu browser (titik tiga di kanan atas Chrome, atau ikon Bagikan/Share di Safari) dan pilih <strong>"Tambahkan ke Layar Utama / Install App"</strong>. Aplikasi langsung terpasang sebagai ikon mandiri (PWA) di layar utama HP, berjalan cepat tanpa baris URL browser, dan siap digunakan di lapangan.
                            </div>
                        </div>

                        <div class="faq-card">
                            <div class="faq-header">
                                <div class="faq-badge-icon"><i data-lucide="rotate-ccw"></i></div>
                                <h4 class="faq-question">5. Salah Input Kas atau Kasbon, Bagaimana Prosedur Koreksinya?</h4>
                            </div>
                            <div class="faq-body">
                                Setiap mutasi kas yang salah input dapat dikoreksi melalui pembatalan transaksi berotorisasi oleh Admin Keuangan atau Kepala Kantor. Khusus transaksi kasbon yang salah input, admin dapat menghapus pengajuan kasbon selama belum ada cicilan yang berjalan, dan saldo kas operasional yang sempat berkurang akan otomatis dipulihkan kembali (*refund*).
                            </div>
                        </div>

                        <div class="faq-card">
                            <div class="faq-header">
                                <div class="faq-badge-icon"><i data-lucide="key"></i></div>
                                <h4 class="faq-question">6. Karyawan Membutuhkan Akses Menu Tambahan, Bagaimana Prosedurnya?</h4>
                            </div>
                            <div class="faq-body">
                                Sampaikan permohonan kepada Kepala Kantor atau Super Admin. Admin dapat membuka menu <strong>Hak Akses &amp; Peran</strong> (Sidebar: <em>Manajemen &rarr; Hak Akses &rarr; Tab Override Pengguna</em>) untuk mengaktifkan izin menu khusus pada akun karyawan yang bersangkutan tanpa harus mengubah peran dasarnya.
                            </div>
                        </div>

                        <div class="faq-card">
                            <div class="faq-header">
                                <div class="faq-badge-icon"><i data-lucide="save"></i></div>
                                <h4 class="faq-question">7. Tidak Sengaja Menutup Halaman Opname / Browser Tertutup, Apakah Data Input Hilang?</h4>
                            </div>
                            <div class="faq-body">
                                <strong>Tidak hilang!</strong> Modul <em>Bulk Opname Gudang</em> dilengkapi fitur <strong>Auto-Save Draf Lokal</strong> yang menyimpan setiap angka ketikan Anda ke memori browser secara berkala. Saat Anda kembali membuka halaman opname, sistem secara otomatis memulihkan seluruh draf angka yang pernah Anda sesuaikan disertai notifikasi konfirmasi di bagian atas tabel.
                            </div>
                        </div>

                        <div class="faq-card">
                            <div class="faq-header">
                                <div class="faq-badge-icon"><i data-lucide="shield-alert"></i></div>
                                <h4 class="faq-question">8. Mengapa Input Penyesuaian (-) Membatasi Angka dan Tidak Bisa Minus dari Stok Sistem?</h4>
                            </div>
                            <div class="faq-body">
                                Sistem ERP menerapkan <strong>Zero Negative Stock Safety</strong>. Dalam operasional fisik riil, sebuah barang di gudang tidak mungkin berjumlah negatif (kurang dari 0). Jika stok sistem saat ini adalah 10 unit, pengurangan maksimal yang diizinkan adalah <code>-10</code> (stok fisik habis). Apabila pengguna mencoba memasukkan angka pengurangan yang lebih besar (misal <code>-15</code>), sistem otomatis membatasi ke <code>-10</code> dan memberikan peringatan agar saldo buku besar mutasi inventaris tetap sah dan tidak cacat.
                            </div>
                        </div>

                        <div class="faq-card">
                            <div class="faq-header">
                                <div class="faq-badge-icon"><i data-lucide="keyboard"></i></div>
                                <h4 class="faq-question">9. Bagaimana Cara Memasukkan Ratusan Data Opname Cepat Menggunakan Numpad / Scanner?</h4>
                            </div>
                            <div class="faq-body">
                                Cukup gunakan tombol keyboard tanpa perlu menyentuh mouse: tekan tombol <code>/</code> untuk mencari produk/SKU/barcode &rarr; masukkan angka stok fisik pada kolom &rarr; tekan tombol <code>Enter</code> atau <code>Panah Bawah (&darr;)</code> untuk langsung loncat ke kolom input baris berikutnya secara otomatis.
                            </div>
                        </div>

                        <div class="faq-card">
                            <div class="faq-header">
                                <div class="faq-badge-icon" style="background:rgba(239,68,68,0.12); color:#dc2626;"><i data-lucide="scale"></i></div>
                                <h4 class="faq-question">10. Kapan Harus Menggunakan Tombol Waste dan Kapan Menggunakan Opname Item Keluar? Mengapa Berpengaruh Besar pada Laporan Keuangan?</h4>
                            </div>
                            <div class="faq-body">
                                <strong>Kunci pembedanya terletak pada wujud fisik dan tujuan audit:</strong>
                                <ul style="padding-left:18px; margin:6px 0; font-size:12px; line-height:1.6;">
                                    <li><strong>Gunakan Tombol [Waste]:</strong> Jika wujud fisik barang nyata ada dan diketahui penyebab rusaknya/dibuangnya (plastik bocor/terkena cutter, remuk saat unboxing dus, expired, atau diambil untuk sampel promosi uji rasa buyer). Mutasi ini dibukukan sebagai pos <strong>Beban Kerusakan Barang / Beban Promosi</strong>. Manajemen dapat memantau tingkat spoilage rate dan mengevaluasi kualitas kemasan supplier.</li>
                                    <li><strong>Gunakan [Opname / Item Keluar]:</strong> Jika fisik barang tidak ada di rak / selisih hitung saat audit berkala tanpa ditemukan bangkai kemasan rusak, atau koreksi administratif. Mutasi ini dibukukan ke pos <strong>Selisih Persediaan (Inventory Variance / Shrinkage)</strong> untuk ditelusuri riwayat nota penjualan sebelumnya.</li>
                                </ul>
                                <em>Aturan Emas: Ada bangkai fisik barang yang rusak/dibuang/sampel? &rarr; <strong>WASTE</strong>. Barang tidak ada di tempat / selisih audit rak? &rarr; <strong>OPNAME</strong>.</em>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 13: PROFIL PERUSAHAAN & IMPOR DATA MASTER            -->
                <!-- ========================================================= -->
                <section id="bab-12-setup-perusahaan" class="guide-chapter theme-violet">
                    <a id="bab-11-impor-master" href="#bab-11-impor-master" style="display:none;" aria-hidden="true"></a>
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="building"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 13</span>
                            <h3 class="chapter-title">Profil Perusahaan &amp; Setup Impor Data Master Excel</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Super Admin</span>
                                <span class="role-pill">Admin Operasional</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Pengaturan identitas resmi perusahaan dan tata cara impor data massal dari berkas Excel:
                    </p>

                    <div class="step-timeline">
                        <div class="step-item">
                            <div class="step-circle">1</div>
                            <div class="step-content">
                                <h4 class="step-title">13.1 Pengaturan Profil Perusahaan &amp; Nota</h4>
                                <p class="step-desc">
                                    Dikelola di menu <strong>Profil Perusahaan</strong> (Sidebar: <em>Pengaturan &rarr; Profil Perusahaan</em>). Di halaman ini, admin dapat memperbarui nama resmi perusahaan, alamat kantor/pabrik, nomor WhatsApp resmi layanan pelanggan, mengunggah logo perusahaan (yang otomatis muncul di struk kasir, invoice faktur, surat jalan, dan slip gaji), serta mengatur catatan kaki (*footer*) faktur B2B.
                                </p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <div class="step-content">
                                <h4 class="step-title">13.2 Roadmap Impor Data Master (Urutan 4 Fase)</h4>
                                <p class="step-desc">
                                    Saat menyiapkan data awal perusahaan di menu <strong>Impor Data</strong> (Sidebar: <em>Pengaturan &rarr; Impor Data</em>), ikuti urutan 4 fase baku agar data saling terhubung tanpa error relasi:
                                </p>
                                <ul style="padding-left:18px; margin:6px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.6;">
                                    <li><strong>Fase 1 (Pondasi Utama):</strong> 1. Merek Produk, 2. Wilayah &amp; Rute Distribusi, 3. Grup Kemasan Produk, dan 4. Kelompok Upah Borongan.</li>
                                    <li><strong>Fase 2 (Sumber Daya &amp; Mitra):</strong> 5. Data Karyawan (Sales &amp; Driver), 6. Pemasok Vendor, 7. Matriks Harga 30 Level, dan 8. Grup Pelanggan.</li>
                                    <li><strong>Fase 3 (Inventori &amp; Resep):</strong> 9. Bahan Baku &amp; Bahan Kemas, dan 10. Barang Jadi Siap Jual (mengikat kemasan dan resep bahan).</li>
                                    <li><strong>Fase 4 (Jaringan Toko):</strong> 11. Toko Pelanggan (mengikat wilayah, grup harga, dan sales pembina).</li>
                                </ul>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-content">
                                <h4 class="step-title">13.3 Panduan Penggunaan Template Excel Resmi</h4>
                                <p class="step-desc">
                                    Setiap template Excel yang diunduh dari sistem dilengkapi dengan <strong>Sheet 2 (Kamus &amp; Referensi Data)</strong>. Sheet ini memuat daftar kode wilayah, grup pelanggan, dan nama pemasok yang sah di sistem. Pengguna tinggal menyalin data dari Sheet 2 agar tidak terjadi salah ketik (*typo*) saat pengisian.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="guide-box box-info">
                        <i data-lucide="shield-alert"></i>
                        <div>
                            <strong>Pemeriksaan Pratinjau (Data Preview) Sebelum Disimpan:</strong><br>
                            Saat berkas Excel diunggah, sistem tidak langsung memasukkan data ke database, melainkan menampilkan tabel pratinjau perbandingan (*Diffing Table*). Staf dapat memeriksa baris yang akan ditambah (*INSERT*), diubah (*UPDATE*), atau ditolak (*ERROR*) terlebih dahulu untuk memastikan data 100% benar sebelum klik Simpan Permanen.
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 14: TIPS NAVIGASI & PWA HP                            -->
                <!-- ========================================================= -->
                <section id="bab-13-tips-navigasi" class="guide-chapter theme-rose">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="sparkles"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 14</span>
                            <h3 class="chapter-title">Tips Navigasi Cepat, Kenyamanan &amp; Performa Aplikasi</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Semua Staf</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Panduan praktis agar penggunaan aplikasi sehari-hari lebih nyaman, cepat, dan lancar di komputer maupun di HP smartphone:
                    </p>

                    <div class="step-timeline">
                        <div class="step-item">
                            <div class="step-circle">1</div>
                            <div class="step-content">
                                <h4 class="step-title">Tombol Kembali Pintar (Smart Navigation)</h4>
                                <p class="step-desc">
                                    Saat membuka panduan ini atau dokumen cetak, tombol <strong>"Kembali / Tutup Panduan"</strong> di pojok kanan atas telah dilengkapi navigasi pintar: jika dibuka di HP (aplikasi PWA), tombol akan mengembalikan Anda ke halaman kerja transaksi terakhir tanpa menutup aplikasi ke home screen HP.
                                </p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <div class="step-content">
                                <h4 class="step-title">Pencarian Cepat di Semua Tabel Data</h4>
                                <p class="step-desc">
                                    Gunakan kolom pencarian di bagian atas tabel. Cukup ketik beberapa huruf nama toko, kode barang, nomor WhatsApp, atau nomor rekening bank, sistem otomatis menyaring data dalam hitungan 0,3 detik tanpa perlu menekan tombol Enter. Tekan tombol ✕ di dalam kolom untuk mereset tabel seketika.
                                </p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-content">
                                <h4 class="step-title">Mode Gelap (Dark Mode) untuk Kerja Malam Hari</h4>
                                <p class="step-desc">
                                    Klik ikon bulan/matahari di bagian atas untuk beralih antara Mode Terang dan Mode Gelap. Mode gelap dirancang khusus untuk kenyamanan mata kasir dan driver saat bertugas di malam hari, sekaligus menghemat pemakaian baterai layar HP.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

            </article>
        </main>
    </div>

    <!-- 3. FLOATING BACK TO TOP BUTTON -->
    <button type="button" 
            x-show="showBackToTop" 
            x-cloak 
            x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            @click="scrollToTop()" 
            class="guide-btn-back-to-top" 
            title="Kembali ke Bagian Atas">
        <i data-lucide="arrow-up"></i>
    </button>

    <!-- JavaScript Search Engine & Lucide Initialization -->
    <script>
        (function() {
            let matches = [];
            let currentIndex = -1;

            function removeHighlights() {
                const article = document.querySelector('.guide-article');
                if (!article) return;
                const marks = article.querySelectorAll('mark.guide-highlight');
                marks.forEach(mark => {
                    const parent = mark.parentNode;
                    if (parent) {
                        parent.replaceChild(document.createTextNode(mark.textContent), mark);
                        parent.normalize();
                    }
                });
                matches = [];
                currentIndex = -1;
            }

            function highlightText(query) {
                removeHighlights();
                const trimmed = query ? query.trim() : '';
                if (trimmed.length < 2) {
                    return { total: 0, current: 0 };
                }

                const article = document.querySelector('.guide-article');
                if (!article) return { total: 0, current: 0 };

                const escaped = trimmed.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const regex = new RegExp(`(${escaped})`, 'gi');

                const walker = document.createTreeWalker(
                    article,
                    NodeFilter.SHOW_TEXT,
                    {
                        acceptNode: function(node) {
                            if (!node.nodeValue || !node.nodeValue.trim()) {
                                return NodeFilter.FILTER_SKIP;
                            }
                            const parentTag = node.parentNode ? node.parentNode.nodeName.toUpperCase() : '';
                            if (['SCRIPT', 'STYLE', 'SVG', 'BUTTON', 'INPUT', 'TEXTAREA'].includes(parentTag)) {
                                return NodeFilter.FILTER_REJECT;
                            }
                            if (regex.test(node.nodeValue)) {
                                regex.lastIndex = 0;
                                return NodeFilter.FILTER_ACCEPT;
                            }
                            return NodeFilter.FILTER_SKIP;
                        }
                    }
                );

                const nodesToReplace = [];
                while (walker.nextNode()) {
                    nodesToReplace.push(walker.currentNode);
                }

                nodesToReplace.forEach(node => {
                    const parent = node.parentNode;
                    if (!parent) return;

                    const text = node.nodeValue;
                    const fragment = document.createDocumentFragment();
                    let lastIndex = 0;

                    text.replace(regex, (match, p1, offset) => {
                        if (offset > lastIndex) {
                            fragment.appendChild(document.createTextNode(text.substring(lastIndex, offset)));
                        }
                        const mark = document.createElement('mark');
                        mark.className = 'guide-highlight';
                        mark.textContent = match;
                        fragment.appendChild(mark);
                        matches.push(mark);

                        lastIndex = offset + match.length;
                        return match;
                    });

                    if (lastIndex < text.length) {
                        fragment.appendChild(document.createTextNode(text.substring(lastIndex)));
                    }

                    parent.replaceChild(fragment, node);
                });

                if (matches.length > 0) {
                    currentIndex = 0;
                    activateMatch(currentIndex);
                }

                return {
                    total: matches.length,
                    current: matches.length > 0 ? currentIndex + 1 : 0
                };
            }

            function activateMatch(index) {
                matches.forEach((m, idx) => {
                    if (idx === index) {
                        m.classList.add('is-active');
                        m.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
                        if (window.scrollX !== 0) {
                            window.scrollTo(0, window.scrollY);
                        }
                    } else {
                        m.classList.remove('is-active');
                    }
                });
            }

            function nextMatch() {
                if (matches.length === 0) return 0;
                currentIndex = (currentIndex + 1) % matches.length;
                activateMatch(currentIndex);
                return currentIndex + 1;
            }

            function prevMatch() {
                if (matches.length === 0) return 0;
                currentIndex = (currentIndex - 1 + matches.length) % matches.length;
                activateMatch(currentIndex);
                return currentIndex + 1;
            }

            window.guideSearchEngine = {
                highlight: highlightText,
                clear: removeHighlights,
                next: nextMatch,
                prev: prevMatch
            };
        })();

        // =========================================================================
        // SCROLLSPY & AUTO-SCROLL DEEP LINK SYNC
        // =========================================================================
        (function() {
            let isClickScrolling = false;
            let clickScrollTimer = null;
            let scrollTicking = false;

            function getChapters() {
                return Array.from(document.querySelectorAll('.guide-chapter[id]'));
            }

            function getTocLinks() {
                return Array.from(document.querySelectorAll('.toc-link'));
            }

            function getActiveChapter() {
                const scrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
                
                // If at the very top (hero banner area), no chapter is active
                if (scrollY < 180) {
                    return null;
                }

                const chapters = getChapters();
                const headerOffset = 100;
                let currentActive = null;

                for (let i = 0; i < chapters.length; i++) {
                    const el = chapters[i];
                    const top = el.getBoundingClientRect().top;
                    // If the element's top is near or above header offset
                    if (top <= headerOffset + 60) {
                        currentActive = el;
                    } else {
                        break;
                    }
                }

                return currentActive || chapters[0] || null;
            }

            function updateActiveState() {
                if (isClickScrolling) return;

                const activeChapter = getActiveChapter();
                const tocLinks = getTocLinks();

                if (activeChapter) {
                    const activeId = activeChapter.id;
                    const newHash = '#' + activeId;

                    // Only replace state if hash actually changed
                    if (window.location.hash !== newHash) {
                        history.replaceState(null, '', newHash);
                    }

                    // Update TOC active state
                    tocLinks.forEach(link => {
                        const href = link.getAttribute('href');
                        if (href === newHash) {
                            link.classList.add('is-active');
                        } else {
                            link.classList.remove('is-active');
                        }
                    });
                } else {
                    // At top hero banner: clean hash from address bar
                    if (window.location.hash && window.location.hash.length > 1) {
                        history.replaceState(null, '', window.location.pathname + window.location.search);
                    }
                    tocLinks.forEach(link => link.classList.remove('is-active'));
                }
            }

            function onScroll() {
                if (!scrollTicking) {
                    window.requestAnimationFrame(() => {
                        updateActiveState();
                        scrollTicking = false;
                    });
                    scrollTicking = true;
                }
            }

            window.addEventListener('scroll', onScroll, { passive: true });

            // Initial scroll on page load if URL has hash
            function initialHashScroll() {
                const hash = window.location.hash;
                if (hash && hash.length > 1) {
                    const target = document.querySelector(hash);
                    if (target) {
                        isClickScrolling = true;
                        setTimeout(() => {
                            const headerOffset = window.innerWidth <= 639 ? 60 : 76;
                            const elementPos = target.getBoundingClientRect().top;
                            const offsetPos = elementPos + window.pageYOffset - headerOffset;
                            window.scrollTo({
                                top: Math.max(0, offsetPos),
                                behavior: 'smooth'
                            });
                            
                            setTimeout(() => {
                                isClickScrolling = false;
                                updateActiveState();
                            }, 600);
                        }, 120);
                    }
                } else {
                    updateActiveState();
                }
            }

            // Intercept TOC link clicks for smooth animated scroll + state update
            function initTocClicks() {
                const tocLinks = getTocLinks();
                tocLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        const href = this.getAttribute('href');
                        if (href && href.startsWith('#')) {
                            e.preventDefault();
                            const target = document.querySelector(href);
                            if (target) {
                                isClickScrolling = true;
                                if (clickScrollTimer) clearTimeout(clickScrollTimer);

                                const headerOffset = window.innerWidth <= 639 ? 60 : 76;
                                const elementPos = target.getBoundingClientRect().top;
                                const offsetPos = elementPos + window.pageYOffset - headerOffset;

                                window.scrollTo({
                                    top: Math.max(0, offsetPos),
                                    behavior: 'smooth'
                                });

                            history.replaceState(null, '', href);

                            tocLinks.forEach(l => l.classList.remove('is-active'));
                            this.classList.add('is-active');

                            clickScrollTimer = setTimeout(() => {
                                isClickScrolling = false;
                                updateActiveState();
                            }, 600);
                        }
                    }
                });
            });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    initTocClicks();
                    initialHashScroll();
                });
            } else {
                initTocClicks();
                initialHashScroll();
            }
        })();

        document.addEventListener('DOMContentLoaded', function() {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }

            // Shield against unwanted zoom in iOS Safari & desktop browsers
            document.addEventListener('gesturestart', (e) => { if (e.cancelable) e.preventDefault(); }, { passive: false });
            document.addEventListener('gesturechange', (e) => { if (e.cancelable) e.preventDefault(); }, { passive: false });
            document.addEventListener('gestureend', (e) => { if (e.cancelable) e.preventDefault(); }, { passive: false });
            document.addEventListener('touchmove', (e) => {
                if (e.touches && e.touches.length > 1 && e.cancelable) e.preventDefault();
            }, { passive: false });
            window.addEventListener('wheel', (e) => {
                if (e.ctrlKey && e.cancelable) e.preventDefault();
            }, { passive: false });
        });
    </script>
</body>
</html>

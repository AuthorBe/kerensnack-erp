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
$favGuideFile = ROOT_PATH . '/public/assets/favicon_guide.svg';
$favGuideV = file_exists($favGuideFile) ? (string)filemtime($favGuideFile) : (string)time();
?>
<!DOCTYPE html>
<html lang="id" class="<?= (($_COOKIE['ksnack_theme'] ?? 'light') === 'dark') ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Buku Panduan Operasional &amp; SOP — <?= htmlspecialchars($companyName) ?></title>

    <!-- PWA, iOS & Meta Tags -->
    <meta name="theme-color" content="#E30613">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-touch-fullscreen" content="yes">
    <meta name="format-detection" content="telephone=no">

    <!-- Dedicated Guide Favicon (Khusus Tab Guide: public/assets/favicon_guide.svg) -->
    <link rel="icon" type="image/svg+xml" href="<?= Router::asset('/favicon_guide.svg') ?>?v=<?= $favGuideV ?>">
    <link rel="alternate icon" href="<?= Router::asset('/favicon_guide.svg') ?>?v=<?= $favGuideV ?>">
    <link rel="apple-touch-icon" href="<?= Router::asset('/favicon_guide.svg') ?>?v=<?= $favGuideV ?>">
    <link rel="shortcut icon" href="<?= Router::asset('/favicon_guide.svg') ?>?v=<?= $favGuideV ?>">

    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;800&display=swap" rel="stylesheet">

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
            --guide-accent: #e11d48;
            --guide-accent-soft: #ffe4e6;
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
            --guide-accent: #f43f5e;
            --guide-accent-soft: rgba(244, 63, 94, 0.15);
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
                padding: 0 46px 0 24px;
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
                left: 6px;
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

        /* 3. SINGLE OFF-CANVAS SIDEBAR (TOC DRAWER) & FROZEN BLUR EFFECT */
        .guide-drawer-backdrop {
            position: fixed;
            inset: 0;
            z-index: 999;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: opacity 0.25s ease, backdrop-filter 0.25s ease;
        }

        .guide-navbar,
        .guide-content-wrapper {
            transition: filter 0.25s ease, opacity 0.25s ease;
        }

        .guide-bg-frozen-blur {
            filter: blur(6px) brightness(0.85);
            -webkit-filter: blur(6px) brightness(0.85);
            pointer-events: none !important;
            user-select: none !important;
            touch-action: none !important;
        }
        html.dark .guide-bg-frozen-blur {
            filter: blur(6px) brightness(0.7);
            -webkit-filter: blur(6px) brightness(0.7);
        }

        .guide-drawer {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            height: 100vh;
            height: 100dvh;
            height: -webkit-fill-available;
            width: 85%;
            max-width: 320px;
            background: var(--guide-sidebar-bg);
            z-index: 1000;
            padding-top: max(18px, env(safe-area-inset-top, 0px));
            padding-bottom: max(18px, env(safe-area-inset-bottom, 0px));
            padding-left: max(16px, env(safe-area-inset-left, 0px));
            padding-right: 16px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            box-shadow: 10px 0 30px rgba(0, 0, 0, 0.25);
            border-right: 1px solid var(--guide-border);
            display: flex;
            flex-direction: column;
            gap: 14px;
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

        .formula-card {
            background: var(--guide-bg);
            border: 1px dashed var(--guide-border);
            border-radius: 12px;
            padding: 12px 14px;
            margin: 12px 0;
            font-family: 'JetBrains Mono', monospace;
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
</head>
<body x-data="{
    sidebarOpen: false,
    searchQuery: '',
    matchCount: 0,
    currentMatchIndex: 0,
    isDark: (function() {
        var m = document.cookie.match(/(?:^|; )ksnack_theme=([^;]*)/);
        return (m ? decodeURIComponent(m[1]) : 'light') === 'dark';
    })(),
    init() {
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
    }
}" @keydown.window.escape="sidebarOpen = false">

    <!-- ===================================================================== -->
    <!-- 1. TOP NAVBAR                                                         -->
    <!-- ===================================================================== -->
    <header class="guide-navbar" :class="{ 'guide-bg-frozen-blur': sidebarOpen }">
        <div class="guide-navbar-left">
            <!-- Hamburger Button (Opens Single Sidebar Drawer) -->
            <button type="button" 
                    class="guide-btn-icon" 
                    @click="sidebarOpen = !sidebarOpen" 
                    title="Daftar Isi Panduan (10 Bab)">
                <i data-lucide="menu"></i>
            </button>

            <!-- Brand Info (Main App Logo Badge) -->
            <div class="guide-brand-box">
                <div class="guide-brand-badge">
                    <img src="<?= Router::asset('/favicon/apple-touch-icon.png') ?>" alt="<?= htmlspecialchars($companyName) ?>" class="guide-brand-logo-img">
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

            <!-- Close Guide Tab Button -->
            <button type="button" 
                    class="guide-btn-close" 
                    onclick="window.close(); setTimeout(() => { window.location.href = '<?= Router::url('/dashboard') ?>'; }, 200);" 
                    title="Tutup Panduan (Tutup Tab)">
                <i data-lucide="x" style="width:14px;height:14px;"></i>
                <span class="guide-btn-close-text">Tutup Panduan</span>
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
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"></div>

        <aside x-show="sidebarOpen" 
               x-cloak 
               class="guide-drawer custom-scrollbar"
               x-transition:enter="transition ease-out duration-250 transform"
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
                <div class="toc-header">11 Bab Standar Operasional</div>
                <ul class="toc-nav-list">
                    <li><a href="#bab-1-peran" class="toc-link" @click="sidebarOpen = false"><i data-lucide="shield"></i> <span>1. Peran &amp; Hak Akses</span></a></li>
                    <li><a href="#bab-2-master-harga" class="toc-link" @click="sidebarOpen = false"><i data-lucide="tag"></i> <span>2. Master Produk &amp; Harga</span></a></li>
                    <li><a href="#bab-3-pos-kasir" class="toc-link" @click="sidebarOpen = false"><i data-lucide="shopping-cart"></i> <span>3. Kasir POS Outlet</span></a></li>
                    <li><a href="#bab-4-b2b-hybrid" class="toc-link" @click="sidebarOpen = false"><i data-lucide="file-text"></i> <span>4. B2B &amp; Dokumen Hybrid</span></a></li>
                    <li><a href="#bab-5-konsinyasi-rolling" class="toc-link" @click="sidebarOpen = false"><i data-lucide="refresh-cw"></i> <span>5. Konsinyasi: Rolling Nota</span></a></li>
                    <li><a href="#bab-6-konsinyasi-tagihan" class="toc-link" @click="sidebarOpen = false"><i data-lucide="receipt"></i> <span>6. Konsinyasi: Kolektif Tagihan</span></a></li>
                    <li><a href="#bab-7-pembelian-vendor" class="toc-link" @click="sidebarOpen = false"><i data-lucide="shopping-bag"></i> <span>7. Pembelian &amp; Vendor</span></a></li>
                    <li><a href="#bab-8-logistik-pengiriman" class="toc-link" @click="sidebarOpen = false"><i data-lucide="truck"></i> <span>8. Logistik &amp; Pengiriman</span></a></li>
                    <li><a href="#bab-9-keuangan-kas" class="toc-link" @click="sidebarOpen = false"><i data-lucide="wallet"></i> <span>9. Buku Kas &amp; Setoran Sore</span></a></li>
                    <li><a href="#bab-10-faq-masalah" class="toc-link" @click="sidebarOpen = false"><i data-lucide="help-circle"></i> <span>10. Solusi Masalah Lapangan</span></a></li>
                    <li><a href="#bab-11-impor-master" class="toc-link" @click="sidebarOpen = false"><i data-lucide="database"></i> <span>11. Setup &amp; Sinkronisasi Master</span></a></li>
                </ul>
            </nav>
        </aside>

        <!-- MAIN ARTICLE BODY -->
        <main class="guide-content-wrapper" :class="{ 'guide-bg-frozen-blur': sidebarOpen }">
            <article class="guide-article">

                <!-- 1. HERO BANNER -->
                <div class="guide-main-hero">
                    <div class="hero-header-flex">
                        <div class="hero-brand-icon">
                            <img src="<?= Router::asset('/favicon_guide.svg') ?>?v=<?= $favGuideV ?>" alt="<?= htmlspecialchars($companyName) ?>" class="hero-brand-logo-img">
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
                            <h3 class="chapter-title">Matriks Peran Pengguna &amp; Keamanan Sistem</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Semua Pengguna</span>
                                <span class="role-pill">Super Admin</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Sistem ERP mengadopsi kontrol akses berbasis peran (<em>Role-Based Access Control / RBAC</em>). Setiap pengguna memiliki batasan menu yang tegas demi menjamin keamanan data finansial dan integritas transaksi:
                    </p>
                    <div class="step-timeline">
                        <div class="step-item">
                            <div class="step-circle">1</div>
                            <div class="step-content">
                                <h4 class="step-title">Owner &amp; Eksekutif</h4>
                                <p class="step-desc">Akses penuh ke menu <strong>Executive Dashboard</strong> (Sidebar: <em>Manajemen &rarr; Executive Dashboard</em>), valuasi aset stok, margin laba kotor, performa sales, dan persetujuan kebijakan strategis.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <div class="step-content">
                                <h4 class="step-title">Admin Operasional</h4>
                                <p class="step-desc">Mengelola master data produk, level harga, pesanan B2B, penjadwalan pengiriman, penagihan konsinyasi, dan mutasi kas bank.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-content">
                                <h4 class="step-title">Sales Lapangan &amp; Driver</h4>
                                <p class="step-desc">Mengakses form opname rak toko mitra, pencatatan titipan baru, rute pengiriman harian, penerimaan setoran tunai, dan pelaporan kunjungan.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">4</div>
                            <div class="step-content">
                                <h4 class="step-title">Petugas Gudang</h4>
                                <p class="step-desc">Memproses persiapan packing pesanan (<em>PO Preparation</em>), penerimaan bahan baku vendor, dan pencatatan retur rusak.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 2: MASTER PRODUK & MATRIKS HARGA                      -->
                <!-- ========================================================= -->
                <section id="bab-2-master-harga" class="guide-chapter theme-emerald">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="tag"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 2</span>
                            <h3 class="chapter-title">Master Data Produk, Resep BOM &amp; Matriks Harga</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Admin</span>
                                <span class="role-pill">Sales</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Penetapan harga jual di sistem dikelola melalui menu <strong>Matriks Level Harga</strong> (Sidebar: <em>Master Data &rarr; Matriks Level Harga</em>) menggunakan <strong>Matriks Level Harga Multi-Tier (Level 1 s/d Level 30)</strong> dan diskon dinamis berbasis grup mitra toko:
                    </p>
                    <div class="guide-box box-info">
                        <i data-lucide="info"></i>
                        <div>
                            <strong>Level 1 (Default Ritel):</strong> Digunakan untuk penjualan umum di menu Kasir POS.<br>
                            <strong>Level 2 - 30 (Grosir &amp; Mitra):</strong> Diberikan secara khusus ke toko mitra pelanggan (agen, reseller, minimarket, konsinyasi) dengan margin khusus.<br>
                            <strong>Resep BOM (Bill of Materials):</strong> Mengikat bahan mentah, bumbu, dan plastik ke produk jadi sehingga saat batch produksi dibuat, bahan baku berkurang otomatis dan HPP terhitung presisi.
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
                        Sistem konsinyasi KEREN ONE menerapkan <strong>Alur Rolling Antar-Nota</strong> di menu <strong>Konsinyasi</strong> (Sidebar: <em>Penjualan &amp; Transaksi &rarr; Konsinyasi &rarr; Tab Form Opname</em>):
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
                        SOP penagihan konsolidasi untuk jaringan minimarket atau toko mitra tempo melalui menu <strong>Konsinyasi</strong> (Sidebar: <em>Penjualan &amp; Transaksi &rarr; Konsinyasi &rarr; Tab Kolektif Tagihan</em>):
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
                <!-- BAB 7: PEMBELIAN & VENDOR                                 -->
                <!-- ========================================================= -->
                <section id="bab-7-pembelian-vendor" class="guide-chapter theme-teal">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="shopping-bag"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 7</span>
                            <h3 class="chapter-title">Pengadaan Bahan &amp; Pembelian Vendor (PO &amp; Penerimaan)</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Admin Gudang</span>
                                <span class="role-pill">Driver</span>
                                <span class="role-pill">Finance</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Pengelolaan pengadaan bahan baku, bumbu, dan kemasan di menu <strong>Pembelian Vendor</strong> (Sidebar: <em>Gudang &amp; Pembelian &rarr; Pembelian Vendor</em>):
                    </p>

                    <div class="step-timeline">
                        <div class="step-item">
                            <div class="step-circle">1</div>
                            <div class="step-content">
                                <h4 class="step-title">Pilih Mode Input Sesuai Kebutuhan</h4>
                                <ul style="padding-left:18px; margin:4px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.6;">
                                    <li><strong>+ Catat Faktur Langsung:</strong> Digunakan jika barang sudah dibeli dan telah tiba di gudang. Stok gudang langsung bertambah dan kas langsung terpotong.</li>
                                    <li><strong>+ Buat PO Pembelian:</strong> Digunakan untuk memesan ke supplier atau menugaskan belanja ke driver. Stok dan kas <em>belum berubah</em> sampai fisik barang diverifikasi di gudang.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <div class="step-content">
                                <h4 class="step-title">Dua Metode Logistik Pengadaan</h4>
                                <ul style="padding-left:18px; margin:4px 0; font-size:12px; color:var(--guide-text-secondary); line-height:1.6;">
                                    <li><strong>Diambil Driver Toko:</strong> Tugas belanja otomatis muncul di aplikasi HP Driver. Driver berbelanja di vendor, mengunggah foto bon fisik/struk, dan membawa barang ke toko.</li>
                                    <li><strong>Diantar oleh Supplier:</strong> Pihak vendor atau ekspedisi mengirimkan barang langsung ke gudang sesuai tanggal perkiraan tiba.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-content">
                                <h4 class="step-title">Verifikasi Penerimaan Barang (Wajib Terima)</h4>
                                <p class="step-desc">Setiap barang PO yang tiba wajib diverifikasi oleh admin gudang dengan menekan tombol <strong>[&#x1F4E6; Terima]</strong> pada tabel pembelian &rarr; cek kesesuaian fisik dan foto bon struk &rarr; klik <strong>"Konfirmasi Terima &amp; Tambah Stok"</strong>. Stok resmi masuk gudang dan HPP terhitung otomatis.</p>
                            </div>
                        </div>
                    </div>

                    <div class="guide-box box-danger">
                        <i data-lucide="alert-triangle"></i>
                        <div>
                            <strong>Penanganan Kendala Driver:</strong> Jika driver melaporkan toko vendor tutup atau barang habis, status PO otomatis berubah menjadi <code>KENDALA / BATAL</code> dan tombol terima terkunci. Admin dapat membuka Detail PO untuk klik <em>[Jadwalkan Ulang / Ganti Driver]</em> atau <em>[Batalkan PO]</em>.
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 8: LOGISTIK & PENGIRIMAN                              -->
                <!-- ========================================================= -->
                <section id="bab-8-logistik-pengiriman" class="guide-chapter theme-cyan">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="truck"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 8</span>
                            <h3 class="chapter-title">Operasional Logistik &amp; Manifest Pengiriman</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Driver</span>
                                <span class="role-pill">Koordinator Logistik</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Driver membuka menu <strong>Pengiriman</strong> (Sidebar: <em>Delivery &rarr; Pengiriman</em> atau <em>Delivery &rarr; Surat Jalan</em>) untuk melihat rute harian pengantaran, kontak toko, alamat, dan memperbarui status surat jalan (Siap Kirim, Sedang Dikirim, Selesai Terkirim, atau Gagal Kirim / Reschedule).
                    </p>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 9: BUKU KAS & SETORAN SORE                            -->
                <!-- ========================================================= -->
                <section id="bab-9-keuangan-kas" class="guide-chapter theme-green">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="wallet"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 9</span>
                            <h3 class="chapter-title">Buku Kas, Rekening Bank &amp; Rekonsiliasi Sore Hari</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Kasir Kantor</span>
                                <span class="role-pill">Driver</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        <strong>SOP Serah Terima Uang Tunai Driver ke Kantor (Sore Hari):</strong>
                    </p>
                    <div class="guide-box box-success">
                        <i data-lucide="check-circle-2"></i>
                        <div>
                            Driver menyerahkan fisik uang tunai hasil rolling nota ke Kasir Kantor.<br>
                            Kasir membuka menu <strong>Kas Masuk &amp; Keluar</strong> (Sidebar: <em>Keuangan &amp; Kas &rarr; Kas Masuk &amp; Keluar &rarr; Tab Transfer Antar Kas</em>):<br>
                            • <em>Dari Akun:</em> <strong>Kas Driver (Pegawai)</strong><br>
                            • <em>Ke Akun:</em> <strong>Kasir Utama / Brankas Kantor</strong><br>
                            Tanggung jawab kas driver otomatis kembali nol dan kas kantor bertambah resmi.
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 10: SOLUSI MASALAH LAPANGAN & FAQ                     -->
                <!-- ========================================================= -->
                <section id="bab-10-faq-masalah" class="guide-chapter theme-orange">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="help-circle"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 10</span>
                            <h3 class="chapter-title">Penanganan Masalah Lapangan (Troubleshooting &amp; FAQ)</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Semua Tim</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="faq-list">
                        <div class="faq-card">
                            <div class="faq-header">
                                <div class="faq-badge-icon"><i data-lucide="store"></i></div>
                                <h4 class="faq-question">1. Bagaimana jika Toko Tutup saat Driver Datang?</h4>
                            </div>
                            <div class="faq-body">
                                <strong>Jangan submit form opname!</strong> Pada menu <strong>Pengiriman</strong> (Sidebar: <em>Delivery &rarr; Pengiriman</em>), pilih status <em>"Gagal Kirim / Reschedule"</em> dengan catatan <em>"Toko tutup"</em>. Saldo rak toko di database tetap aman pada posisi terakhir.
                            </div>
                        </div>

                        <div class="faq-card">
                            <div class="faq-header">
                                <div class="faq-badge-icon"><i data-lucide="plus-circle"></i></div>
                                <h4 class="faq-question">2. Toko Minta Tambah Varian Rasa / SKU Baru di Rak?</h4>
                            </div>
                            <div class="faq-body">
                                Admin/Sales membuat PO Konsinyasi yang memuat SKU baru melalui menu <strong>Konsinyasi</strong>. Saat form opname dibuka, sistem otomatis menggabungkan SKU baru tersebut dengan <em>Stok Kirim Lalu = 0</em> dan <em>Tambah Baru = Qty PO</em>. Setelah disubmit, SKU baru resmi tercatat permanen di rak toko tersebut.
                            </div>
                        </div>

                        <div class="faq-card">
                            <div class="faq-header">
                                <div class="faq-badge-icon"><i data-lucide="alert-triangle"></i></div>
                                <h4 class="faq-question">3. Ada Barang Hilang di Rak Toko &amp; Toko Menolak Bayar?</h4>
                            </div>
                            <div class="faq-body">
                                Sales memasukkan selisih di kolom <em>"Selisih Qty / Stok Hilang Pending"</em> (misal: <code>-2</code>). Sistem mencatatnya sebagai audit kehilangan tanpa membebankan tagihan pada nota hari itu demi menjaga hubungan kemitraan.
                            </div>
                        </div>

                        <div class="faq-card">
                            <div class="faq-header">
                                <div class="faq-badge-icon"><i data-lucide="smartphone"></i></div>
                                <h4 class="faq-question">4. Bagaimana Menginstal Aplikasi Keren One di HP Android/iOS?</h4>
                            </div>
                            <div class="faq-body">
                                Buka browser Chrome/Safari, akses alamat ERP, lalu klik tombol menu browser (titik tiga atau tombol share) dan pilih <strong>"Tambahkan ke Layar Utama / Install App"</strong>. Aplikasi langsung terpasang sebagai Progressive Web App (PWA) tanpa perlu PlayStore.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- BAB 11: SETUP & SINKRONISASI MASTER DATA                  -->
                <!-- ========================================================= -->
                <section id="bab-11-impor-master" class="guide-chapter theme-violet">
                    <div class="chapter-header">
                        <div class="chapter-icon-badge"><i data-lucide="database"></i></div>
                        <div class="chapter-title-wrap">
                            <span class="chapter-number">Bab 11</span>
                            <h3 class="chapter-title">Setup &amp; Sinkronisasi Master Data (Roadmap 4 Fase)</h3>
                            <div class="chapter-roles">
                                <span class="role-pill">Super Admin</span>
                                <span class="role-pill">Developer</span>
                            </div>
                        </div>
                    </div>
                    <p class="step-desc">
                        Panduan setup awal dan impor massal database di menu <strong>Pengaturan &rarr; Impor Data</strong> (Sidebar: <em>Sistem &rarr; Pengaturan &rarr; Impor Data</em>):
                    </p>

                    <div class="step-timeline">
                        <div class="step-item">
                            <div class="step-circle">1</div>
                            <div class="step-content">
                                <h4 class="step-title">Fase 1: Master Pondasi Independen &amp; Kemasan Produk</h4>
                                <p class="step-desc">Wajib diimpor pertama kali: <strong>1. Merek Produk</strong> (Brand), <strong>2. Wilayah &amp; Rute Distribusi</strong>, <strong>3. Grup Kemasan Produk</strong> (Gramasi, Bal to Pcs), dan <strong>4. Kelompok Upah Borongan</strong>.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <div class="step-content">
                                <h4 class="step-title">Fase 2: Sumber Daya, Vendor, Matriks Harga &amp; Grup Pelanggan</h4>
                                <p class="step-desc">Langkah kedua: <strong>5. Data Karyawan</strong> (khususnya posisi Sales &amp; Driver), <strong>6. Pemasok Vendor</strong>, <strong>7. Matriks Harga Jual 30 Level</strong> (wajib diinput sebelum grup pelanggan agar tier harga tersedia), dan <strong>8. Grup Pelanggan</strong> (mengikat default level harga dan diskon brand dari matriks).</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-content">
                                <h4 class="step-title">Fase 3: Katalog Inventori &amp; Produksi</h4>
                                <p class="step-desc">Langkah ketiga: <strong>9. Bahan Baku &amp; Kemas</strong> (singkong, bumbu, plastik vendor), dan <strong>10. Barang Jadi Siap Jual</strong> (SKU produk jadi yang mengikat Merek, Grup Kemasan, dan Upah Borongan).</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-circle">4</div>
                            <div class="step-content">
                                <h4 class="step-title">Fase 4: Jaringan Toko Pelanggan (Puncak Relasi)</h4>
                                <p class="step-desc">Langkah puncak: <strong>11. Toko Pelanggan</strong> (diimpor paling akhir karena mengikat Wilayah, Grup Pelanggan, dan Sales Pembina sekaligus).</p>
                            </div>
                        </div>
                    </div>

                    <div class="guide-box box-info">
                        <i data-lucide="shield-alert"></i>
                        <div>
                            <strong>Dua Kebijakan Impor &amp; Mesin SmartReader:</strong><br>
                            • <em>Mode Aman (Upsert):</em> Menambah data baru &amp; menimpa data yang berubah. Data database lain dijamin aman.<br>
                            • <em>Sinkronisasi Penuh (Single Truth):</em> Berkas Excel menjadi acuan mutlak; entri lama yang tidak terdaftar akan otomatis di-soft delete atau dinonaktifkan bila memiliki riwayat transaksi agar integritas finansial tetap utuh.
                        </div>
                    </div>
                </section>

            </article>
        </main>
    </div>

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
        });
    </script>
</body>
</html>

<?php
/**
 * views/errors/restricted.php
 * Halaman Akses Dibatasi / Restricted Route & Security Guard
 * KEREN SNACK ERP — Clean, Modern, and Professional Design
 */

use App\Core\Router;

$pageTitle = $title ?? 'Akses Dibatasi — KEREN SNACK ERP';
$requestedUri = $_SERVER['REQUEST_URI'] ?? '';
$cleanUri = htmlspecialchars(parse_url($requestedUri, PHP_URL_PATH) ?? $requestedUri, ENT_QUOTES, 'UTF-8');
$fallbackHomeUrl = class_exists('\App\Core\Router') ? Router::url('/pos') : '/pos';
$faviconUrl = class_exists('\App\Core\Router') ? Router::asset('/favicon/favicon-96x96.png') : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="robots" content="noindex, nofollow">

    <?php if ($faviconUrl): ?>
    <link rel="icon" type="image/png" href="<?= $faviconUrl ?>">
    <?php endif; ?>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">

    <style>
        :root {
            --bg: #090d16;
            --surface: #0f172a;
            --surface-card: rgba(15, 23, 42, 0.85);
            --border: rgba(255, 255, 255, 0.08);
            --border-hover: rgba(255, 255, 255, 0.16);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-dim: #64748b;
            --primary: #e11d48;
            --primary-glow: rgba(225, 29, 72, 0.25);
            --accent-amber: #f59e0b;
            --accent-cyan: #38bdf8;
            --accent-indigo: #6366f1;
            --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-display: 'Outfit', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            position: relative;
            overflow-x: hidden;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.08) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        .container {
            width: 100%;
            max-width: 540px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 10;
        }

        /* Glass Card */
        .error-card {
            background: var(--surface-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: clamp(28px, 6vw, 44px) clamp(20px, 5vw, 36px);
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.03) inset;
            transition: border-color 0.3s ease, transform 0.3s ease;
        }

        .error-card:hover {
            border-color: var(--border-hover);
        }

        /* Top Security Badge */
        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(225, 29, 72, 0.12);
            border: 1px solid rgba(225, 29, 72, 0.3);
            color: #fb7185;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 9999px;
            margin-bottom: 20px;
        }

        .security-badge i {
            font-size: 11px;
            animation: pulse-icon 2s infinite ease-in-out;
        }

        /* ==========================================================================
           VECTOR ANIMATION: PERSON SEARCHING / CONFUSED (CLEAN & LIGHTWEIGHT)
           ========================================================================== */
        .illustration-wrap {
            position: relative;
            width: 200px;
            height: 180px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .svg-character {
            width: 100%;
            height: 100%;
            overflow: visible;
        }

        /* Animated Body & Head Bobbing */
        .char-head {
            transform-origin: 100px 78px;
            animation: head-tilt 4.5s ease-in-out infinite;
        }

        @keyframes head-tilt {
            0%, 100% { transform: rotate(0deg); }
            20% { transform: rotate(-9deg) translateY(-2px); }
            45% { transform: rotate(0deg); }
            70% { transform: rotate(11deg) translateY(-1px); }
            85% { transform: rotate(0deg); }
        }

        /* Eyes Looking Around */
        .char-eyes {
            animation: eyes-look 4.5s ease-in-out infinite;
        }

        @keyframes eyes-look {
            0%, 100% { transform: translate(0, 0); }
            20% { transform: translate(-3.5px, 0); }
            45% { transform: translate(0, 0); }
            70% { transform: translate(4px, 0); }
            85% { transform: translate(0, 0); }
        }

        /* Magnifying Glass Sway & Hover Search */
        .magnifier-arm {
            transform-origin: 65px 105px;
            animation: search-arm 4s ease-in-out infinite;
        }

        @keyframes search-arm {
            0%, 100% { transform: rotate(0deg); }
            30% { transform: rotate(-12deg) translateY(-3px); }
            65% { transform: rotate(8deg) translateY(2px); }
        }

        .magnifier-glass {
            transform-origin: 145px 75px;
            animation: glass-pulse 3s ease-in-out infinite alternate;
        }

        @keyframes glass-pulse {
            0% { transform: scale(1); filter: drop-shadow(0 0 4px rgba(56, 189, 248, 0.3)); }
            100% { transform: scale(1.06); filter: drop-shadow(0 0 12px rgba(56, 189, 248, 0.7)); }
        }

        /* Floating Question Marks */
        .qmark-1 {
            animation: float-qmark-1 3.5s ease-in-out infinite;
        }

        @keyframes float-qmark-1 {
            0%, 100% { transform: translate(0, 0) scale(0.9); opacity: 0.3; }
            50% { transform: translate(-4px, -10px) scale(1.1); opacity: 0.9; }
        }

        .qmark-2 {
            animation: float-qmark-2 4s ease-in-out infinite 0.8s;
        }

        @keyframes float-qmark-2 {
            0%, 100% { transform: translate(0, 0) scale(0.85); opacity: 0.25; }
            50% { transform: translate(6px, -12px) scale(1.15); opacity: 0.85; }
        }

        /* Floor Ripple / Radar Scan */
        .radar-ring {
            transform-origin: 100px 158px;
            animation: radar-expand 3s ease-out infinite;
        }

        @keyframes radar-expand {
            0% { r: 18; opacity: 0.8; stroke-width: 1.5; }
            100% { r: 52; opacity: 0; stroke-width: 0.5; }
        }

        /* ==========================================================================
           TYPOGRAPHY & CONTENT
           ========================================================================== */
        .error-title {
            font-family: var(--font-display);
            font-size: clamp(22px, 5vw, 28px);
            font-weight: 800;
            line-height: 1.25;
            color: var(--text-main);
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }

        .error-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 22px;
            max-width: 440px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Requested Path Snippet */
        .path-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 8px 14px;
            margin-bottom: 26px;
            max-width: 100%;
            overflow: hidden;
            font-family: 'JetBrains Mono', Consolas, monospace;
            font-size: 12px;
            color: #e2e8f0;
        }

        .path-pill i {
            color: var(--accent-amber);
            flex-shrink: 0;
            font-size: 11px;
        }

        .path-pill span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 320px;
        }

        /* Action Buttons */
        .actions-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }

        @media (min-width: 440px) {
            .actions-group {
                flex-direction: row;
                justify-content: center;
            }
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: var(--font-sans);
            font-size: 13.5px;
            font-weight: 600;
            padding: 11px 22px;
            border-radius: 12px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
            color: #ffffff;
            box-shadow: 0 4px 16px rgba(225, 29, 72, 0.35);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(225, 29, 72, 0.5);
            color: #ffffff;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--border-hover);
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* Footer Info */
        .footer-note {
            margin-top: 24px;
            font-size: 11.5px;
            color: var(--text-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .footer-note i {
            color: #10b981;
            font-size: 9px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="error-card">
            <!-- Security Badge -->
            <div class="security-badge">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Akses Dibatasi &bull; 403 Forbidden</span>
            </div>

            <!-- Vector Character Illustration: Person Searching / Confused -->
            <div class="illustration-wrap" aria-hidden="true">
                <svg class="svg-character" viewBox="0 0 200 180" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <!-- Radar / Floor Gradient -->
                        <radialGradient id="floorGrad" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.25"/>
                            <stop offset="100%" stop-color="#38bdf8" stop-opacity="0"/>
                        </radialGradient>

                        <!-- Character Skin / Cloth Gradients -->
                        <linearGradient id="bodyGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#3b82f6"/>
                            <stop offset="100%" stop-color="#1d4ed8"/>
                        </linearGradient>

                        <linearGradient id="skinGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#fed7aa"/>
                            <stop offset="100%" stop-color="#fdba74"/>
                        </linearGradient>

                        <linearGradient id="hairGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#334155"/>
                            <stop offset="100%" stop-color="#0f172a"/>
                        </linearGradient>

                        <linearGradient id="glassGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="rgba(56, 189, 248, 0.45)"/>
                            <stop offset="100%" stop-color="rgba(14, 165, 233, 0.15)"/>
                        </linearGradient>
                    </defs>

                    <!-- Floor Base Shadow & Ripple -->
                    <ellipse cx="100" cy="158" rx="55" ry="11" fill="url(#floorGrad)"/>
                    <circle class="radar-ring" cx="100" cy="158" r="28" stroke="#38bdf8" fill="none"/>

                    <!-- Floating Question Mark Left -->
                    <g class="qmark-1" transform="translate(58, 48)">
                        <path d="M4 6C4 3.5 5.8 1.5 8.5 1.5C11.2 1.5 13 3.5 13 5.8C13 7.8 11.5 9 10 10.2C9 11 8.5 12 8.5 13.5M8.5 17.5H8.55" stroke="#f59e0b" stroke-width="2.2" stroke-linecap="round"/>
                    </g>

                    <!-- Floating Question Mark Right -->
                    <g class="qmark-2" transform="translate(132, 40)">
                        <path d="M4 6C4 3.5 5.8 1.5 8.5 1.5C11.2 1.5 13 3.5 13 5.8C13 7.8 11.5 9 10 10.2C9 11 8.5 12 8.5 13.5M8.5 17.5H8.55" stroke="#fb7185" stroke-width="2" stroke-linecap="round"/>
                    </g>

                    <!-- Body / Torso -->
                    <g id="character-body">
                        <!-- Shoulders & Shirt -->
                        <path d="M72 152C70 126 76 112 88 108C94 106 106 106 112 108C124 112 130 126 128 152H72Z" fill="url(#bodyGrad)"/>
                        <!-- Collar Accent -->
                        <path d="M94 107L100 116L106 107" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" opacity="0.6"/>
                    </g>

                    <!-- Head & Face (Animated Group) -->
                    <g class="char-head" id="character-head">
                        <!-- Neck -->
                        <rect x="95" y="96" width="10" height="13" rx="4" fill="url(#skinGrad)"/>
                        <!-- Head -->
                        <circle cx="100" cy="78" r="19" fill="url(#skinGrad)"/>

                        <!-- Hair -->
                        <path d="M81 76C81 65 89 57 100 57C111 57 119 65 119 76C119 78 117 76 115 74C111 70 106 72 101 69C96 66 90 71 85 75C83 77 81 77 81 76Z" fill="url(#hairGrad)"/>

                        <!-- Face Details (Eyes & Mouth) -->
                        <g class="char-eyes">
                            <!-- Eyebrows (Confused arch) -->
                            <path d="M89 71C91 69 94 70 96 71" stroke="#334155" stroke-width="1.4" stroke-linecap="round"/>
                            <path d="M104 70C106 68 109 69 111 72" stroke="#334155" stroke-width="1.4" stroke-linecap="round"/>
                            <!-- Eye Left -->
                            <circle cx="93" cy="77" r="2.2" fill="#0f172a"/>
                            <circle cx="92.2" cy="76.2" r="0.7" fill="#ffffff"/>
                            <!-- Eye Right -->
                            <circle cx="107" cy="77" r="2.2" fill="#0f172a"/>
                            <circle cx="106.2" cy="76.2" r="0.7" fill="#ffffff"/>
                        </g>

                        <!-- Mouth (Confused 'o') -->
                        <ellipse cx="100" cy="88" rx="2.5" ry="3" fill="#e11d48" opacity="0.8"/>
                    </g>

                    <!-- Arm Holding Magnifying Glass (Animated) -->
                    <g class="magnifier-arm">
                        <!-- Arm Sleeve -->
                        <path d="M78 116C72 122 68 132 82 135" stroke="url(#bodyGrad)" stroke-width="8" stroke-linecap="round"/>
                        <!-- Hand -->
                        <circle cx="82" cy="134" r="5" fill="url(#skinGrad)"/>

                        <!-- Magnifier Handle -->
                        <line x1="84" y1="133" x2="114" y2="108" stroke="#f59e0b" stroke-width="4.5" stroke-linecap="round"/>
                        <!-- Magnifier Rim -->
                        <circle class="magnifier-glass" cx="127" cy="97" r="16" stroke="#f59e0b" stroke-width="3.5" fill="url(#glassGrad)"/>
                        <!-- Glass Glare Reflex -->
                        <path d="M118 90C121 86 127 85 132 87" stroke="#ffffff" stroke-width="2" stroke-linecap="round" opacity="0.75"/>
                    </g>
                </svg>
            </div>

            <!-- Headline -->
            <h1 class="error-title">Halaman Tidak Dapat Diakses</h1>

            <!-- Explanatory Text -->
            <p class="error-desc">
                Halaman, rute, atau berkas yang Anda tuju berada di bawah kebijakan proteksi keamanan sistem (Access Control / <code>.htaccess</code>).
            </p>

            <!-- Current Path Display -->
            <?php if (!empty($cleanUri) && $cleanUri !== '/'): ?>
            <div class="path-pill">
                <i class="fa-solid fa-link-slash"></i>
                <span title="<?= $cleanUri ?>"><?= $cleanUri ?></span>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="actions-group">
                <button onclick="handleGoBack()" class="btn btn-secondary" type="button">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Halaman Sebelumnya</span>
                </button>
            </div>

            <!-- Footer Compliance Note -->
            <div class="footer-note">
                <i class="fa-solid fa-shield-check"></i>
                <span>Sistem Keamanan Terpadu &bull; KEREN SNACK ERP</span>
            </div>
        </div>
    </div>

    <script>
        function handleGoBack() {
            if (document.referrer && document.referrer !== window.location.href && document.referrer.indexOf(window.location.host) !== -1) {
                window.location.href = document.referrer;
            } else if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = <?= json_encode($fallbackHomeUrl) ?>;
            }
        }
    </script>
</body>
</html>

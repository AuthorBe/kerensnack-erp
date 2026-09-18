<?php
/**
 * views/errors/403.php — Halaman Access Denied Interaktif (Kado Kejutan)
 * KEREN SNACK ERP — Enterprise Architecture
 */
use App\Core\Router;
use App\Helpers\CSRF;

$pageTitle = $title ?? '403 – Akses Ditolak | KEREN SNACK ERP';
$logoutUrl = Router::url('/logout');
$csrfToken = CSRF::token();
$reasonDetail = $reason ?? null;
$isDeveloper = \App\Core\Auth::isDeveloper();
$isPreview = (!empty($isPreview) || (isset($_GET['preview']) && $_GET['preview'] === '1')) && $isDeveloper;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="robots" content="noindex, nofollow">

    <?php if (!$isPreview): ?>
    <!-- Auto-Logout Fallback jika JavaScript dinonaktifkan di browser pengguna lain -->
    <noscript><meta http-equiv="refresh" content="10;url=<?= htmlspecialchars($logoutUrl, ENT_QUOTES, 'UTF-8') ?>"></noscript>
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= Router::asset('/favicon/favicon-96x96.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= Router::asset('/favicon/apple-touch-icon.png') ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

    <style>
        :root {
            --bg-light: #f8fafc;
            --text-main: #334155;
            --text-muted: #64748b;
            --primary-accent: #f43f5e;
            --primary-light: #fff1f2;
            --primary-border: #fecdd3;
            --gold-accent: #f59e0b;
            --glass-bg: rgba(255, 255, 255, 0.90);
            --glass-border: rgba(255, 255, 255, 0.7);
            --font-display: 'Outfit', sans-serif;
            --font-sans: 'Plus Jakarta Sans', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body.error-body {
            background-color: var(--bg-light);
            background-image: 
                radial-gradient(circle at 5% 5%, #fef2f2 0%, transparent 35%),
                radial-gradient(circle at 95% 95%, #eff6ff 0%, transparent 35%),
                radial-gradient(circle at 50% 50%, #faf5ff 0%, transparent 40%);
            font-family: var(--font-sans);
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            perspective: 1200px;
            transition: background-color 0.6s ease;
            overflow-x: hidden;
            overflow-y: auto;
            min-height: 100vh;
            min-height: 100dvh;
            padding: clamp(1rem, 3vw, 2rem) clamp(0.75rem, 2vw, 1.5rem);
            width: 100%;
        }

        /* Alarm visual lembut saat kado dibuka */
        body.error-body.opened {
            animation: soft-alarm-pulse 3s infinite alternate;
        }

        .main-container {
            width: 100%;
            max-width: 520px;
            padding: 0.5rem;
            text-align: center;
            z-index: 10;
            position: relative;
            margin: auto;
            box-sizing: border-box;
        }

        /* ==========================================
           1. DESAIN KADO MINIMALIS & ELEGAN (LIGHT)
           ========================================== */
        .gift-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            width: 100%;
        }

        .glass-card-pre {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-radius: clamp(22px, 4vw, 32px);
            padding: clamp(2rem, 5vw, 3rem) clamp(1.25rem, 4vw, 2rem);
            box-shadow: 
                0 10px 30px -10px rgba(148, 163, 184, 0.12),
                0 30px 60px -15px rgba(148, 163, 184, 0.18);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: clamp(1.25rem, 3vw, 2rem);
            width: 100%;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
        }

        /* Pantulan cahaya soft di kartu */
        .glass-card-pre::before {
            content: '';
            position: absolute;
            top: 0; 
            left: -100%;
            width: 300%; 
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.45), transparent);
            transform: skewX(-25deg);
            animation: shine-slow 6s infinite ease-in-out;
            pointer-events: none;
        }

        .gift-title {
            font-family: var(--font-display);
            font-size: clamp(1.15rem, 4vw, 1.45rem);
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.5px;
            line-height: 1.35;
        }

        /* Animasi Kado Pastel Melayang */
        .gift-container {
            position: relative;
            width: 140px;
            height: 140px;
            cursor: pointer;
            transition: transform 0.3s ease;
            animation: float-soft 4s ease-in-out infinite;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .gift-container:hover {
            animation: shake-gentle 0.4s infinite;
        }

        /* Bagian Kotak Kado Pastel */
        .gift-lid { 
            position: absolute; 
            width: 140px; 
            height: 38px; 
            background: linear-gradient(135deg, #fda4af, #f43f5e); 
            border-radius: 6px 6px 2px 2px; 
            top: 32px; 
            left: 0; 
            z-index: 3; 
            box-shadow: 0 4px 10px rgba(244, 63, 94, 0.15);
        }
        
        .gift-box { 
            position: absolute; 
            width: 126px; 
            height: 82px; 
            background: linear-gradient(135deg, #f43f5e, #e11d48); 
            border-radius: 0 0 10px 10px; 
            bottom: 0; 
            left: 7px; 
            z-index: 2; 
            box-shadow: inset 0 6px 12px rgba(0,0,0,0.06);
        }
        
        .ribbon-vertical { 
            position: absolute; 
            width: 18px; 
            height: 96px; 
            background: linear-gradient(to bottom, #fef08a, #fde047); 
            top: 32px; 
            left: 61px; 
            z-index: 4; 
            border-radius: 1px; 
        }
        
        .bow { 
            position: absolute; 
            width: 38px; 
            height: 30px; 
            background: #fde047; 
            border-radius: 50%; 
            top: 14px; 
            left: 51px; 
            z-index: 5; 
        }
        
        .bow::before, .bow::after { 
            content: ''; 
            position: absolute; 
            width: 28px; 
            height: 28px; 
            background: #fef08a; 
            border-radius: 50%; 
            top: -4px; 
        }
        
        .bow::before { left: -14px; transform: rotate(-30deg); }
        .bow::after { right: -14px; transform: rotate(30deg); }

        .click-instruction { 
            font-size: 0.85rem; 
            color: var(--text-muted); 
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(148, 163, 184, 0.08);
            padding: 8px 18px;
            border-radius: 50px;
            border: 1px solid rgba(148, 163, 184, 0.15);
            animation: pulse-soft 2.5s infinite; 
            cursor: pointer;
        }

        .click-instruction svg,
        .click-instruction i {
            color: var(--primary-accent);
            width: 16px;
            height: 16px;
        }

        /* ==========================================
           2. TAMPILAN INTERAKTIF TROLL / PERINGATAN
           ========================================== */
        .error-card-wrapper {
            opacity: 0;
            transform: scale(0.92) translateY(15px);
            transition: all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: none;
            width: 100%;
        }

        .glass-card-post {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(35px);
            -webkit-backdrop-filter: blur(35px);
            border-radius: clamp(22px, 4vw, 36px);
            padding: clamp(2rem, 5vw, 3rem) clamp(1.25rem, 4vw, 2rem);
            box-shadow: 
                0 20px 50px -10px rgba(15, 23, 42, 0.08),
                0 40px 80px -20px rgba(15, 23, 42, 0.12);
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            width: 100%;
        }

        .troll-emoji {
            font-size: clamp(3.5rem, 12vw, 5.5rem);
            line-height: 1;
            display: inline-block;
            margin-bottom: clamp(0.75rem, 2vw, 1.25rem);
            transform-origin: center bottom;
            animation: emoji-playful 2s infinite ease-in-out alternate;
            user-select: none;
        }

        .error-title {
            font-family: var(--font-display);
            font-size: clamp(1.4rem, 5vw, 2.1rem);
            font-weight: 800;
            line-height: 1.2;
            color: #1e293b;
            margin-bottom: 0.75rem;
            letter-spacing: -0.75px;
        }

        .error-message {
            font-size: clamp(0.85rem, 2.8vw, 0.98rem);
            color: var(--text-muted);
            line-height: 1.55;
            max-width: 420px;
            margin: 0 auto 1.75rem auto;
        }

        .error-message strong {
            color: var(--primary-accent);
            font-weight: 700;
        }

        /* Desain Timer */
        .timer-container {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            background: var(--primary-light);
            border: 1px solid var(--primary-border);
            padding: clamp(0.75rem, 2.5vw, 1.2rem) clamp(1.5rem, 5vw, 3rem);
            border-radius: clamp(16px, 3vw, 24px);
            position: relative;
            margin-bottom: 1.25rem;
            max-width: 100%;
            box-sizing: border-box;
        }

        .countdown-label {
            font-size: clamp(0.65rem, 2vw, 0.72rem);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #f43f5e;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }

        .countdown-digits {
            font-family: var(--font-display);
            font-size: clamp(2.2rem, 7vw, 2.75rem);
            font-weight: 800;
            color: var(--primary-accent);
            display: flex;
            align-items: center;
            gap: 1px;
            line-height: 1;
        }

        .digits-desc {
            font-size: clamp(0.95rem, 3vw, 1.2rem);
            color: #fb7185;
            margin-left: 2px;
        }

        /* Tombol Cepat Keluar */
        .btn-instant-exit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            padding: 6px 14px;
            border-radius: 8px;
            transition: all 0.2s ease;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .btn-instant-exit:hover {
            color: #f43f5e;
        }

        /* ==========================================
           3. EMOJI PARTIKEL JATUH LAMBAT DAN BANYAK
           ========================================== */
        .emoji-particle {
            position: fixed;
            font-size: 2rem;
            user-select: none;
            pointer-events: none;
            opacity: 0;
            z-index: 1000;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.06));
        }

        /* Kecepatan lambat & gerakan mengayun halus */
        @keyframes emoji-fall-slow {
            0% {
                transform: translateY(-80px) translateX(0) rotate(0deg) scale(0.6);
                opacity: 0;
            }
            10% {
                opacity: 0.85;
            }
            90% {
                opacity: 0.85;
            }
            100% {
                transform: translateY(105vh) translateX(var(--drift-width)) rotate(var(--spin-degree)) scale(1.1);
                opacity: 0;
            }
        }

        /* KEYFRAMES & KONDISI TRANSISI */
        body.error-body.opened .gift-wrapper {
            transform: translateY(-20px) scale(0.85);
            opacity: 0;
            pointer-events: none;
            display: none;
        }

        body.error-body.opened .error-card-wrapper {
            opacity: 1;
            transform: scale(1) translateY(0);
            display: block;
        }

        @keyframes float-soft {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        @keyframes shine-slow {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        @keyframes pulse-soft {
            0%, 100% { opacity: 0.75; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.02); }
        }

        @keyframes soft-alarm-pulse {
            0% { background-color: var(--bg-light); }
            100% { background-color: #fff8f8; }
        }

        @keyframes emoji-playful {
            0% { transform: rotate(-5deg) translateY(0); }
            100% { transform: rotate(5deg) translateY(-8px); }
        }

        @keyframes shake-gentle {
            0%, 100% { transform: translateX(0) rotate(0); }
            25% { transform: translateX(-4px) rotate(-1.5deg); }
            75% { transform: translateX(4px) rotate(1.5deg); }
        }

        @keyframes shake-timer {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        /* ==========================================
           4. MODAL DIALOG PREVIEW ELEGAN (GLASSMORPHISM)
           ========================================== */
        .preview-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .preview-modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .preview-modal-card {
            background: #ffffff;
            border-radius: 28px;
            max-width: 480px;
            width: 100%;
            padding: 2.25rem 2rem 2rem;
            box-shadow: 
                0 25px 50px -12px rgba(15, 23, 42, 0.35),
                0 0 0 1px rgba(226, 232, 240, 0.9);
            text-align: center;
            position: relative;
            overflow: hidden;
            transform: scale(0.92) translateY(20px);
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .preview-modal-overlay.active .preview-modal-card {
            transform: scale(1) translateY(0);
        }

        .modal-top-accent {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #38bdf8, #818cf8, #f43f5e);
        }

        .modal-icon-badge {
            width: 68px;
            height: 68px;
            border-radius: 22px;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border: 1.5px solid #bfdbfe;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 8px 20px -6px rgba(59, 130, 246, 0.35);
        }

        .modal-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            background: rgba(37, 99, 235, 0.08);
            color: #2563eb;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
        }

        .modal-title {
            font-family: var(--font-display);
            font-size: 1.55rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.25;
            margin-bottom: 0.75rem;
            letter-spacing: -0.5px;
        }

        .modal-desc {
            font-size: 0.92rem;
            color: #64748b;
            line-height: 1.55;
            margin-bottom: 1.75rem;
        }

        .modal-desc strong {
            color: #1e293b;
        }

        .modal-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-modal-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 12px 20px;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            transition: all 0.2s ease;
        }

        .btn-modal-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
        }

        .btn-modal-danger {
            background: #fff1f2;
            color: #e11d48;
            border: 1px solid #fecdd3;
            font-size: 0.88rem;
            font-weight: 700;
            padding: 10px 18px;
            border-radius: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-modal-danger:hover {
            background: #ffe4e6;
            border-color: #fda4af;
            color: #be123c;
        }

        .btn-modal-close {
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 8px;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .btn-modal-close:hover {
            color: #475569;
        }

        /* Banner Preview Pengembang Responsif */
        .dev-preview-banner {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            background: rgba(15, 23, 42, 0.94);
            color: #38bdf8;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 11.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(56, 189, 248, 0.4);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            max-width: calc(100vw - 20px);
            box-sizing: border-box;
            white-space: nowrap;
        }

        /* ==========================================
           5. MEDIA QUERIES RESPONSIVE MOBILE & SHORT SCREENS
           ========================================== */
        @media (max-width: 580px) {
            .dev-preview-banner .dev-banner-note {
                display: none;
            }
            .dev-preview-banner {
                padding: 5px 12px;
                font-size: 11px;
                gap: 8px;
                top: 8px;
            }
            .dev-preview-banner a {
                padding: 3px 8px !important;
                font-size: 10px !important;
            }
        }

        @media (max-width: 480px) {
            body.error-body {
                padding: 0.75rem 0.5rem;
            }

            .main-container {
                padding: 0.25rem;
            }

            .gift-container {
                transform: scale(0.92);
                margin: -4px 0;
            }

            .click-instruction {
                font-size: 0.78rem;
                padding: 6px 14px;
                width: auto;
            }

            .preview-modal-card {
                padding: 1.5rem 1.25rem 1.25rem;
                border-radius: 22px;
            }

            .modal-icon-badge {
                width: 56px;
                height: 56px;
                font-size: 1.6rem;
                border-radius: 18px;
                margin-bottom: 0.85rem;
            }

            .modal-title {
                font-size: 1.35rem;
                margin-bottom: 0.5rem;
            }

            .modal-desc {
                font-size: 0.85rem;
                line-height: 1.5;
                margin-bottom: 1.25rem;
            }

            .btn-modal-primary {
                font-size: 0.86rem;
                padding: 10px 14px;
            }

            .btn-modal-danger {
                font-size: 0.82rem;
                padding: 9px 12px;
            }
        }

        /* Layar Pendek (Landscape HP atau Netbook) */
        @media (max-height: 700px) {
            body.error-body {
                justify-content: flex-start;
                padding-top: 55px;
            }

            .glass-card-pre,
            .glass-card-post {
                padding: 1.5rem 1.25rem;
                gap: 1rem;
            }

            .troll-emoji {
                font-size: 3.5rem;
                margin-bottom: 0.5rem;
            }

            .timer-container {
                padding: 0.6rem 1.5rem;
                margin-bottom: 0.75rem;
            }

            .countdown-digits {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="error-body">

<?php if ($isPreview): ?>
<div class="dev-preview-banner">
    <span style="display:inline-flex;align-items:center;gap:6px;">
        <span style="width:7px;height:7px;border-radius:50%;background:#38bdf8;box-shadow:0 0 8px #38bdf8;display:inline-block;"></span>
        MODE PREVIEW PENGEMBANG
    </span>
    <span class="dev-banner-note" style="color:#64748b;">|</span>
    <span class="dev-banner-note" style="color:#cbd5e1;font-weight:500;">(Auto-logout disimulasikan)</span>
    <a href="<?= Router::url('/developer') ?>" style="color:#fff;text-decoration:none;font-size:11px;background:#3b82f6;padding:4px 10px;border-radius:6px;font-weight:700;">Kembali</a>
</div>
<?php endif; ?>

<div class="main-container">
    
    <!-- BAGIAN 1: WRAPPER KADO ELEGAN -->
    <div class="gift-wrapper" id="giftWrapper">
        <div class="glass-card-pre">
            <h2 class="gift-title">Aduh, Ada Kado Kejutan Untukmu! 🎁</h2>
            <div class="gift-container" id="giftBox" role="button" aria-label="Buka Kado Kejutan" tabindex="0">
                <div class="bow"></div>
                <div class="gift-lid"></div>
                <div class="ribbon-vertical"></div>
                <div class="gift-box"></div>
            </div>
            <div class="click-instruction" id="instructionTrigger">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"></path>
                    <path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v2"></path>
                    <path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"></path>
                    <path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"></path>
                </svg>
                Klik pelan untuk membuka kado
            </div>
        </div>
    </div>

    <!-- BAGIAN 2: SCREEN TROLL / MENGEJEK HALUS -->
    <div class="error-card-wrapper" id="errorCard">
        <div class="glass-card-post">
            <div class="troll-emoji">🤭</div>
            <h1 class="error-title">Aduh, Ketahuan Deh... 🤭</h1>
            <p class="error-message">
                Sayang sekali, sistem mendeteksi tindakan <strong>bypass akses ilegal</strong> yang Anda lakukan. Hadiah spesial buat Anda yang kreatif: <strong>Logout Otomatis!</strong>
            </p>
            
            <!-- Countdown Timer Box -->
            <div class="timer-container" id="timerBox">
                <span class="countdown-label">Sesi Anda Selesai Dalam</span>
                <div class="countdown-digits">
                    <span id="countdown">10</span>
                    <span class="digits-desc">s</span>
                </div>
            </div>

            <div>
                <button type="button" class="btn-instant-exit" id="btnInstantLogout">
                    Keluar Sekarang
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Hidden Auto-Logout Form -->
<form id="logoutForm" method="POST" action="<?= htmlspecialchars($logoutUrl, ENT_QUOTES, 'UTF-8') ?>" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
</form>

<?php if ($isPreview): ?>
<!-- Modal Dialog Simulasi Preview Elegan -->
<div class="preview-modal-overlay" id="previewModalOverlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="preview-modal-card">
        <div class="modal-top-accent"></div>
        <div class="modal-icon-badge">🎉</div>
        <div>
            <div class="modal-pill">
                <span>SIMULASI PREVIEW SELESAI</span>
            </div>
            <h3 class="modal-title" id="modalTitle">Hitung Mundur 10s Selesai!</h3>
            <p class="modal-desc">
                Pada skenario pengguna riil yang mencoba bypass akses ilegal, sistem akan <strong>langsung mengeksekusi logout otomatis</strong> ke <code>/logout</code>, membersihkan memori browser (Zero-Trash Cleanup), dan me-redirect ke login.
            </p>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-primary" id="btnModalReplay">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                Ulangi Animasi Kado
            </button>
            <button type="button" class="btn-modal-danger" id="btnModalRealLogout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Uji Logout Riil Sekarang
            </button>
            <button type="button" class="btn-modal-close" id="btnModalClose">
                Tutup Notifikasi
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    'use strict';

    const giftBox = document.getElementById('giftBox');
    const instructionTrigger = document.getElementById('instructionTrigger');
    const countdownElement = document.getElementById('countdown');
    const timerBox = document.getElementById('timerBox');
    const logoutForm = document.getElementById('logoutForm');
    const btnInstantLogout = document.getElementById('btnInstantLogout');

    const btnModalReplay = document.getElementById('btnModalReplay');
    const btnModalRealLogout = document.getElementById('btnModalRealLogout');
    const btnModalClose = document.getElementById('btnModalClose');

    let hasBeenClicked = false;
    let countdownInterval = null;

    const isPreviewMode = <?= $isPreview ? 'true' : 'false' ?>;

    function showPreviewModal() {
        const modal = document.getElementById('previewModalOverlay');
        if (modal) {
            modal.classList.add('active');
        }
    }

    function closePreviewModal() {
        const modal = document.getElementById('previewModalOverlay');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    function resetGiftAnimation() {
        closePreviewModal();
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
        hasBeenClicked = false;
        document.body.classList.remove('opened');
        if (countdownElement) {
            countdownElement.textContent = '10';
        }
        if (timerBox) {
            timerBox.style.animation = '';
        }
    }

    function executeLogout(force = false) {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
        // Mode Preview: tampilkan dialog popup elegan pengganti alert bawaan browser
        if (isPreviewMode && !force) {
            showPreviewModal();
            return;
        }
        // Untuk pengguna lain atau force logout: langsung kirim form POST /logout
        logoutForm.submit();
    }

    // Jaminan keamanan untuk pengguna lain yang bukan developer:
    // Jika halaman terbuka selama 20 detik tanpa mengklik kado, paksa logout otomatis
    if (!isPreviewMode) {
        setTimeout(() => {
            if (!hasBeenClicked) {
                executeLogout(true);
            }
        }, 20000);
    }

    function openGift() {
        if (hasBeenClicked) return;
        hasBeenClicked = true;

        // 1. Efek nada audio Web Audio API: Ding-Dong gembira + Sad Trombone cempreng
        playPoliteTrollSound();

        // 2. Aktifkan visual alarm pastel lembut
        document.body.classList.add('opened');
        
        // 3. Picu hujan emoji yang BANYAK dan LAMBAT jatuh ke bawah
        triggerSlowEmojiRain();
        
        // 4. Logika countdown 10 detik
        let countdown = 10;
        countdownInterval = setInterval(() => {
            countdown--;
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }
            
            if (countdown <= 3 && timerBox) {
                timerBox.style.animation = 'shake-timer 0.2s infinite';
            }

            if (countdown <= 0) {
                executeLogout(false);
            }
        }, 1000);
    }

    giftBox.addEventListener('click', openGift);
    giftBox.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openGift();
        }
    });

    if (instructionTrigger) {
        instructionTrigger.addEventListener('click', openGift);
    }

    if (btnInstantLogout) {
        btnInstantLogout.addEventListener('click', () => {
            if (isPreviewMode) {
                showPreviewModal();
            } else {
                executeLogout(true);
            }
        });
    }

    if (btnModalReplay) {
        btnModalReplay.addEventListener('click', resetGiftAnimation);
    }

    if (btnModalRealLogout) {
        btnModalRealLogout.addEventListener('click', () => {
            executeLogout(true);
        });
    }

    if (btnModalClose) {
        btnModalClose.addEventListener('click', closePreviewModal);
    }

    // =======================================================
    // 🎼 EFEK SUARA SINTETIS (DING-DONG GEMBIRA + SAD TROMBONE)
    // =======================================================
    function playPoliteTrollSound() {
        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) return;

            const audioCtx = new AudioContextClass();
            let time = audioCtx.currentTime;
            
            // A. NADA DING-DONG GEMBIRA (Menarik perhatian seolah berhasil)
            const dingNotes = [587.33, 880.00]; // D5 (Tinggi), A5 (Lebih Tinggi)
            const dingTimes = [0.0, 0.15];
            
            dingNotes.forEach((freq, i) => {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                
                osc.type = 'sine'; // Suara bel murni
                osc.frequency.setValueAtTime(freq, time + dingTimes[i]);
                
                gain.gain.setValueAtTime(0.12, time + dingTimes[i]);
                gain.gain.exponentialRampToValueAtTime(0.002, time + dingTimes[i] + 0.3);
                
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start(time + dingTimes[i]);
                osc.stop(time + dingTimes[i] + 0.35);
            });

            // B. NADA WAH-WAH SAD TROMBONE (Dimulai setelah Ding-Dong selesai)
            const tromboneNotes = [293.66, 277.18, 261.63, 220.00]; // Nada sumbang turun
            const tromboneDurations = [0.25, 0.25, 0.25, 0.75];
            let tromboneStart = time + 0.5; // Mulai di detik 0.5
            
            tromboneNotes.forEach((freq, i) => {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                
                osc.type = 'triangle'; // Nada lembut meliuk
                osc.frequency.setValueAtTime(freq, tromboneStart);
                osc.frequency.exponentialRampToValueAtTime(freq * 0.88, tromboneStart + tromboneDurations[i]); // Efek perosotan (pitch bend)
                
                gain.gain.setValueAtTime(0.15, tromboneStart);
                gain.gain.exponentialRampToValueAtTime(0.002, tromboneStart + tromboneDurations[i] - 0.05);
                
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                
                osc.start(tromboneStart);
                osc.stop(tromboneStart + tromboneDurations[i]);
                
                tromboneStart += tromboneDurations[i];
            });
            
        } catch (e) {
            console.warn("[Audio] Web Audio API diblokir browser atau tidak didukung:", e);
        }
    }

    // =======================================================
    // 🌧️ HUJAN EMOJI PELAN (SLOW DRIFT) DAN BANYAK (350 PARTIKEL)
    // =======================================================
    function triggerSlowEmojiRain() {
        const emojis = ['🤭', '🤫', '🤓', '🙅‍♂️', '🔒', '🎈', '🌸', '✨', '🎈', '🧸'];
        const screenWidth = window.innerWidth;
        const body = document.body;
        
        // Generate 350 emoji agar berjatuhan sangat banyak
        for (let i = 0; i < 350; i++) {
            const particle = document.createElement('div');
            particle.className = 'emoji-particle';
            particle.innerText = emojis[Math.floor(Math.random() * emojis.length)];
            
            // Penempatan posisi horizontal acak
            particle.style.left = (Math.random() * screenWidth) + 'px';
            particle.style.top = '-40px';
            
            // Hitung variabel acak untuk di-passing ke CSS keyframe
            const driftWidth = (Math.random() * 200 - 100) + 'px'; // Ayunan kiri-kanan -100px s/d 100px
            const spinDegree = (Math.random() * 720 - 360) + 'deg'; // Derajat putaran -360 s/d 360
            
            particle.style.setProperty('--drift-width', driftWidth);
            particle.style.setProperty('--spin-degree', spinDegree);
            
            // Durasi jatuhnya sangat lambat (8 s/d 16 detik)
            const duration = Math.random() * 8 + 8;
            // Penundaan acak berkisar 0 s/d 4 detik agar jatuh bergantian secara alami
            const delay = Math.random() * 4;
            
            particle.style.animation = `emoji-fall-slow ${duration}s ease-in-out ${delay}s forwards`;
            
            body.appendChild(particle);
            
            // Hapus elemen setelah selesai jatuh untuk mencegah beban DOM
            setTimeout(() => particle.remove(), (duration + delay) * 1000);
        }
    }
</script>
</body>
</html>
